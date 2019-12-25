<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 *
 * @package auth_approved
 */

namespace auth_approved;

/**
 * Collection of methods for manipulating of request records in
 * auth_approved_request database table.
 */
final class request {
    /** Freshly submitted sign up request */
    const STATUS_PENDING = 0;
    /** Approved sign up request, the userid field links the created user */
    const STATUS_APPROVED = 1;
    /** Rejected sign up requests */
    const STATUS_REJECTED = 2;

    /** Not-logged-in user is submitting a new sign up request */
    const STAGE_SIGNUP = 1;
    /** Approver is updating or approving the request */
    const STAGE_APPROVAL = 2;

    /**
     * Returns a localised list of all available request statuses.
     *
     * @return string[]
     */
    public static function get_statuses() {
        return array(
            self::STATUS_PENDING => get_string('requeststatuspending', 'auth_approved'),
            self::STATUS_APPROVED => get_string('requeststatusapproved', 'auth_approved'),
            self::STATUS_REJECTED => get_string('requeststatusrejected', 'auth_approved'),
        );
    }

    /**
     * Save request data submitted by not-logged-in user via the sign up form.
     *
     * Note: validation is supposed to be done in form,
     *       any remaining problems will have to be resolved later by the person
     *       approving this request.
     *
     * @param \stdClass $data data object return from the sign up form
     * @return int new request id
     */
    public static function add_request(\stdClass $data) {
        global $DB;

        $data = clone($data);

        // Does anything want to alter the data received from user? Please tread lightly, safety is off in hooks.
        $hook = new \auth_approved\hook\add_request($data);
        $hook->execute();
        $data = $hook->data;

        $trans = $DB->start_delegated_transaction();

        $record = self::encode_signup_form_data($data);
        unset($record->id);
        $record->status = self::STATUS_PENDING;
        $record->password = hash_internal_user_password($data->password);
        $record->confirmed = 0;
        do {
            $record->confirmtoken = strtolower(random_string(32));
        } while ($DB->record_exists('auth_approved_request', array('confirmtoken' => $record->confirmtoken)));
        $record->timecreated = time();
        $record->timemodified = $record->timecreated;

        $id = $DB->insert_record('auth_approved_request', $record);
        $request = self::snapshot_request($id);

        $trans->allow_commit();

        \auth_approved\event\request_added::create_from_request($request)->trigger();

        // Send all emails and notifications.
        comms::email_request_confirmation($request);
        comms::notify_new_request($request);

        return $id;
    }

    /**
     * Save updated request.
     *
     * Note: validation is supposed to be done in form,
     *       any remaining problems will have to be resolved later by the person
     *       approving this request.
     *
     * @param \stdClass $data form data from the sign up form.
     */
    public static function update_request(\stdClass $data) {
        global $DB;

        $data = clone($data);

        // Does anything want to alter the data? Please tread lightly, safety is off in hooks.
        $hook = new \auth_approved\hook\update_request($data);
        $hook->execute();
        $data = $hook->data;

        $record = self::encode_signup_form_data($data);
        $record->timemodified = time();

        $oldrecord = $DB->get_record('auth_approved_request', array('id' => $record->id), '*', MUST_EXIST);
        if ($oldrecord->status != self::STATUS_PENDING) {
            throw new \coding_exception('Cannot update resolved request!');
        }

        $trans = $DB->start_delegated_transaction();
        $DB->update_record('auth_approved_request', $record);
        self::snapshot_request($record->id);
        $trans->allow_commit();
    }

    /**
     * Confirm that user owns the email address used in request.
     *
     * @param string $token
     * @return array (bool success, string notification, continue button or null)
     */
    public static function confirm_request($token) {
        global $DB;

        if (strlen($token) !== 32) {
            // Cannot be a correct token, better not rely on database value comparison only.
            return array(false, get_string('confirmtokeninvalid', 'auth_approved'), null);
        }

        $request = $DB->get_record('auth_approved_request', array('confirmtoken' => $token));
        if (!$request) {
            return array(false, get_string('confirmtokeninvalid', 'auth_approved'), null);
        }
        if ($request->status == self::STATUS_APPROVED) {
            return array(false, get_string('confirmtokenapproved', 'auth_approved'), null);
        }
        if ($request->status == self::STATUS_REJECTED) {
            return array(false, get_string('confirmtokenrejected', 'auth_approved'), null);
        }
        if ($request->status != self::STATUS_PENDING) {
            throw new \coding_exception('Unknown request status');
        }
        if ($request->confirmed) {
            return array(false, get_string('confirmtokenconfirmed', 'auth_approved'), null);
        }

        $trans = $DB->start_delegated_transaction();
        $record = new \stdClass();
        $record->id = $request->id;
        $record->confirmed = 1;
        $record->timemodified = time();
        $DB->update_record('auth_approved_request', $record);
        $request = self::snapshot_request($record->id);
        $trans->allow_commit();

        \auth_approved\event\request_confirmed::create_from_request($request)->trigger();

        $approved = false;

        $hasfreeformentry = !empty($request->positionfreetext)
                            || !empty($request->organisationfreetext)
                            || !empty($request->managerfreetext);

        // Can we auto-approve this request?
        $autoapprove = false;
        if (!get_config('auth_approved', 'requireapproval')) {
            $autoapprove = true;
        }
        else if ($hasfreeformentry) {
            // Auto approve cannot be done even for whitelisted emails if a free
            // form entry field is filled.
        }
        else {
            $domainlist = get_config('auth_approved', 'domainwhitelist');
            if (util::email_matches_domain_list($request->email, $domainlist)) {
                $autoapprove = true;
            }
        }

        if ($autoapprove) {
            $data = self::decode_signup_form_data($request);
            $errors = self::validate_signup_form_data($data, self::STAGE_APPROVAL);
            if (!$errors) {
                self::approve_request($request->id, '', true);
                $request = $DB->get_record('auth_approved_request', array('id' => $request->id), '*', MUST_EXIST);
                $approved = true;
            }
        }

        if ($approved) {
            // Do not send any confirmation about approved email.
            comms::notify_auto_approved_request($request);
            $loginbutton = new \single_button(new \moodle_url(get_login_url()), get_string('login'), 'get');
            return array(true, get_string('confirmtokenacceptedapproved', 'auth_approved', s($request->username)), $loginbutton);
        } else {
            comms::email_approval_info($request);
            comms::notify_confirmed_request($request);
            return array(true, get_string('confirmtokenaccepted', 'auth_approved', $request->email), null);
        }
    }

    /**
     * Approve sign up request.
     *
     * @param int $id id of record from auth_approved_request table
     * @param string $custommessage custom message for user
     * @param bool $autoapprove true for automatic approval
     * @return int The new users id
     */
    public static function approve_request($id, $custommessage, $autoapprove) {
        global $DB, $USER, $CFG;
        require_once("$CFG->dirroot/user/lib.php");
        require_once("$CFG->dirroot/user/profile/lib.php");
        require_once($CFG->dirroot . '/totara/hierarchy/prefix/position/lib.php');

        $record = $DB->get_record('auth_approved_request', array('id' => $id), '*', MUST_EXIST);

        if ($record->status == self::STATUS_APPROVED) {
            // Nothing to do!
            return true;
        }
        if ($record->status != self::STATUS_PENDING) {
            // Only pending requests can be approved!
            return false;
        }

        $user = self::decode_signup_form_data($record);

        $errors = self::validate_signup_form_data($user, self::STAGE_APPROVAL);
        if ($errors) {
            return false;
        }

        // Does anything want to alter the data? Please tread lightly, safety is off in hooks.
        $hook = new \auth_approved\hook\approve_request($user);
        $hook->execute();
        $user = $hook->data;

        unset($user->id);
        $user->auth = 'approved';
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->confirmed = 1; // Approval supersedes email confirmation!
        $user->deleted = 0;
        $user->suspended = 0;

        $trans = $DB->start_delegated_transaction();

        $user->id = user_create_user($user, false, false);
        $DB->set_field('user', 'password', $record->password, array('id' => $user->id));

        // Save any custom profile field information.
        profile_save_data($user);

        // Create job assignment if requested.
        if ($user->organisationid or $user->positionid or $user->managerjaid) {
            $data = (object)array(
                'organisationid' => $user->organisationid ? $user->organisationid : null,
                'positionid' => $user->positionid ? $user->positionid : null,
                'managerjaid' => $user->managerjaid ? $user->managerjaid : null,
            );
            $data->userid = $user->id;
            \totara_job\job_assignment::create_default($data->userid, $data);
        }

        $user = $DB->get_record('user', array('id' => $user->id), '*', MUST_EXIST);
        \core\event\user_created::create_from_userid($user->id)->trigger();

        // Update the info in sign up request table,
        // but keep the modified date because we have a separate timestamp for that.
        $record->status = self::STATUS_APPROVED;
        $record->timeresolved = time();
        if ($autoapprove) {
            $record->resolvedby = null;
        } else {
            $record->resolvedby = $USER->id;
        }
        $record->userid = $user->id;
        $DB->update_record('auth_approved_request', $record);
        $record = self::snapshot_request($record->id);
        $trans->allow_commit();

        \auth_approved\event\request_approved::create_from_request($record, $user)->trigger();

        comms::email_request_approved($record, $user, $custommessage);

        return (int)$user->id;
    }

    /**
     * Reject sign up request.
     *
     * @param int $id id of record from auth_approved_request table
     * @param string $custommessage custom message for user
     * @return bool success
     */
    public static function reject_request($id, $custommessage) {
        global $DB, $USER;

        $record = $DB->get_record('auth_approved_request', array('id' => $id), '*', MUST_EXIST);

        if ($record->status == self::STATUS_REJECTED) {
            // Nothing to do!
            return true;
        }
        if ($record->status != self::STATUS_PENDING) {
            // Only pending requests can be approved!
            return false;
        }

        $trans = $DB->start_delegated_transaction();
        // Update the info in sign up request table,
        // but keep the modified date because we have a separate timestamp for that.
        $record->status = self::STATUS_REJECTED;
        $record->timeresolved = time();
        $record->resolvedby = $USER->id;
        $DB->update_record('auth_approved_request', $record);
        $record = self::snapshot_request($record->id);
        $trans->allow_commit();

        \auth_approved\event\request_rejected::create_from_request($record)->trigger();

        comms::email_request_rejected($record, $custommessage);

        return true;
    }

    /**
     * Converts sign up form data to database record format.
     *
     * @param \stdClass $data
     * @return \stdClass record
     */
    public static function encode_signup_form_data(\stdClass $data) {
        $record = new \stdClass();
        $record->id = $data->requestid;
        $record->username = $data->username;
        $record->firstname = $data->firstname;
        $record->lastname = $data->lastname;
        $record->lastnamephonetic = !isset($data->lastnamephonetic) ? null : (trim($data->lastnamephonetic) === '' ? null : $data->lastnamephonetic);
        $record->firstnamephonetic = !isset($data->firstnamephonetic) ? null : (trim($data->firstnamephonetic) === '' ? null : $data->firstnamephonetic);
        $record->middlename = !isset($data->middlename) ? null : (trim($data->middlename) === '' ? null : $data->middlename);
        $record->alternatename = !isset($data->alternatename) ? null : (trim($data->alternatename) === '' ? null : $data->alternatename);
        $record->email = $data->email;
        $record->city = $data->city;
        $record->country = $data->country;
        $record->lang = $data->lang;

        $record->positionid = empty($data->positionid) ? 0 : $data->positionid;
        if (property_exists($data, 'positionfreetext')) {
            $record->positionfreetext = trim($data->positionfreetext);
            if ($record->positionfreetext === '') {
                $record->positionfreetext = null;
            }
        }
        $record->organisationid = empty($data->organisationid) ? 0 : $data->organisationid;
        if (property_exists($data, 'organisationfreetext')) {
            $record->organisationfreetext = trim($data->organisationfreetext);
            if ($record->organisationfreetext === '') {
                $record->organisationfreetext = null;
            }
        }
        $record->managerjaid = empty($data->managerjaid) ? 0 : $data->managerjaid;
        if (property_exists($data, 'managerfreetext')) {
            $record->managerfreetext = trim($data->managerfreetext);
            if ($record->managerfreetext === '') {
                $record->managerfreetext = null;
            }
        }

        // Add profile fields.
        $profilefields = array();
        foreach ((array)$data as $k => $v) {
            if (strpos($k, 'profile_field_') === 0) {
                $profilefields[$k] = $v;
            }
        }
        $record->profilefields = json_encode($profilefields);

        // Add extra data in json format.
        $record->extradata = empty($data->extradata) ? '' : json_encode($data->extradata);

        return $record;
    }


    /**
     * Converts database record to format suitable for request updates
     * via sign up form and user_create_user().
     *
     * @param \stdClass $record
     * @return \stdClass
     */
    public static function decode_signup_form_data(\stdClass $record) {
        $data = new \stdClass();
        $data->id = 0;
        $data->requestid = $record->id;
        $data->username = $record->username;
        $data->firstname = $record->firstname;
        $data->lastname = $record->lastname;
        $data->lastnamephonetic = $record->lastnamephonetic;
        $data->firstnamephonetic = $record->firstnamephonetic;
        $data->middlename = $record->middlename;
        $data->alternatename = $record->alternatename;
        $data->email = $record->email;
        $data->city = $record->city;
        $data->country = $record->country;
        $data->lang = $record->lang;
        $data->positionid = $record->positionid;
        $data->positionfreetext = $record->positionfreetext;
        $data->organisationid = $record->organisationid;
        $data->organisationfreetext = $record->organisationfreetext;
        $data->managerjaid = $record->managerjaid;
        $data->managerfreetext = $record->managerfreetext;

        // It is safe to decode without checks here because the data is encoded via encode_signup_form_data() only.
        $profilefields = json_decode($record->profilefields);
        foreach ((array)$profilefields as $k => $v) {
            // Convert object to array to support textarea user profile fields.
            $data->$k = is_object($v) ? (array)$v : $v;
        }

        return $data;
    }

    /**
     * Validate form data - this maybe used on real form data
     * or data returned from self::decode_signup_form_data().
     *
     * @param \stdClass|array $data
     * @param int $stage
     * @return array of errors, empty array means no errors.
     */
    public static function validate_signup_form_data($data, $stage) {
        global $DB, $CFG;
        require_once("$CFG->dirroot/user/profile/lib.php");

        $errors = array();
        $data = (array)$data;

        if (!validate_email($data['email'])) {
            $errors['email'] = get_string('invalidemail');
        } else {
            if ($DB->record_exists_select('user', "LOWER(email) = LOWER(:email)", array('email'=>$data['email']))) {
                if ($stage == request::STAGE_SIGNUP) {
                    $errors['email'] = get_string('emailexists') . ' ' .
                        get_string('emailexistssignuphint', 'moodle',
                            \html_writer::link(new \moodle_url('/login/forgot_password.php'), get_string('emailexistshintlink')));
                } else {
                    $errors['email'] = get_string('emailexists');
                }
            }
        }

        if ($stage == request::STAGE_SIGNUP) {
            // Somebody requests a new account.

            if (empty($data['password'])) {
                $errors['password'] = get_string('required');
            } else {
                $errmsg = '';
                if (!check_password_policy($data['password'], $errmsg)) {
                    $errors['password'] = $errmsg;
                }
            }

            // Prevent duplicate username requests only when creating request.
            if ($DB->record_exists_select('auth_approved_request', "LOWER(username) = LOWER(:username) AND status = :status", array('username' => $data['username'], 'status' => self::STATUS_PENDING))) {
                $errors['username'] = get_string('requestusernameexists', 'auth_approved');
            }


            // Prevent duplicate email requests only when creating request.
            if (empty($errors['email'])) {
                if ($err = email_is_not_allowed($data['email'])) {
                    $errors['email'] = $err;
                } else if ($DB->record_exists_select('auth_approved_request', "LOWER(email) = LOWER(:email) AND status = :status", array('email' => $data['email'], 'status' => self::STATUS_PENDING))) {
                    $errors['email'] = get_string('requestemailexists', 'auth_approved');
                }
            }
        }

        if (empty($errors['username'])) {
            // check allowed characters
            if ($data['username'] !== \core_text::strtolower($data['username'])) {
                $errors['username'] = get_string('usernamelowercase');
            }
            else if ($data['username'] !== clean_param($data['username'], PARAM_USERNAME)) {
                $errors['username'] = get_string('invalidusername');
            }
            else if ($DB->record_exists_select('user', "LOWER(username) = LOWER(:username) AND mnethostid = :mnethostid", array('username' => $data['username'], 'mnethostid' => $CFG->mnet_localhost_id))) {
                $errors['username'] = get_string('usernameexists');
            }
        }

        // Validate organisation selection.
        if ($stage == self::STAGE_APPROVAL) {
            if (!empty($data['organisationid']) and !$DB->record_exists('org', array('id' => $data['organisationid']))) {
                $errors['organisationid'] = get_string('errorunknownorganisationid', 'auth_approved', (object)$data);
            }
            // Do we have required value? Ignore organisationfreetext,
            // it was just a hint for approver to pick the right organisation.
            if (get_config('auth_approved', 'requireorganisation')) {
                if (empty($data['organisationid'])) {
                    $errors['organisationid'] = get_string('errormissingorg', 'auth_approved');
                    $errors['organisationselector'] = get_string('errormissingorg', 'auth_approved');
                }
            }
        } else {
            // We rely on the forms to validate the values, it is a simple select element.
            // Is this the special case with two separate settings?
            if (get_config('auth_approved', 'requireorganisation')) {
                if (empty($data['organisationid']) and empty($data['organisationfreetext'])) {
                    if (get_config('auth_approved', 'alloworganisation')) {
                        $errors['organisationid'] = get_string('errormissingorg', 'auth_approved');
                    }
                    if (get_config('auth_approved', 'alloworganisationfreetext')) {
                        $errors['organisationfreetext'] = get_string('errormissingorg', 'auth_approved');
                    }
                }
            }
        }

        // Validate position selection.
        if ($stage == self::STAGE_APPROVAL) {
            if (!empty($data['positionid']) and !$DB->record_exists('pos', array('id' => $data['positionid']))) {
                $errors['positionid'] = get_string('errorunknownpositionid', 'auth_approved', (object)$data);
            }
            // Do we have required value? Ignore positionfreetext,
            // it was just a hint for approver to pick the right position.
            if (get_config('auth_approved', 'requireposition')) {
                if (empty($data['positionid'])) {
                    $errors['positionid'] = get_string('errormissingpos', 'auth_approved');
                    $errors['positionselector'] = get_string('errormissingpos', 'auth_approved');
                }
            }
        } else {
            // We rely on the forms to validate the values, it is a simple select element.
            // Is this the special case with two separate settings?
            if (get_config('auth_approved', 'requireposition')) {
                if (empty($data['positionid']) and empty($data['positionfreetext'])) {
                    if (get_config('auth_approved', 'allowposition')) {
                        $errors['positionid'] = get_string('errormissingpos', 'auth_approved');
                    }
                    if (get_config('auth_approved', 'allowpositionfreetext')) {
                        $errors['positionfreetext'] = get_string('errormissingpos', 'auth_approved');
                    }
                }
            }
        }

        // Validate manager selection.
        if ($stage == self::STAGE_APPROVAL) {
            if (!empty($data['managerjaid']) and !$DB->record_exists('job_assignment', array('id' => $data['managerjaid']))) {
                $errors['managerjaid'] = get_string('errorunknownmanagerjaid', 'auth_approved', (object)$data);
            }
            // Do we have required value? Ignore managerfreetext,
            // it was just a hint for approver to pick the right manager.
            if (get_config('auth_approved', 'requiremanager')) {
                if (empty($data['managerjaid'])) {
                    $errors['managerjaid'] = get_string('errormissingmgr', 'auth_approved');
                    $errors['managerselector'] = get_string('errormissingmgr', 'auth_approved');
                }
            }
        } else {
            // In the signup phase, the manager selection is an autocomplete field. This returns an
            // array instead of a single value. Hence this part here.
            $managerjaid = null;
            if (!empty($data['managerjaid'])) {
                if (is_array($data['managerjaid'])) {
                    // Alert to the autocomplete element returning an array sometimes.
                    debugging('Unexpected managerid form, it should be an int but got an array from the autocomplete element.', DEBUG_DEVELOPER);
                    $data['managerjaid'] = reset($data['managerjaid']);
                }
                $managerjaid = $data['managerjaid'];

                if (!$DB->record_exists('job_assignment', array('id' => $managerjaid))) {
                    $a = new \stdClass;
                    $a->email = $data['email'];
                    $a->managerjaid = $managerjaid;
                    $errors['managerjaid'] = get_string('errorunknownmanagerjaid', 'auth_approved', $a);
                }
            }

            if (get_config('auth_approved', 'requiremanager')) {
                if (empty($managerjaid) and empty($data['managerfreetext'])) {
                    if (get_config('auth_approved', 'allowmanager')) {
                        $errors['managerjaid'] = get_string('errormissingmgr', 'auth_approved');
                    }
                    if (get_config('auth_approved', 'allowmanagerfreetext')) {
                        $errors['managerfreetext'] = get_string('errormissingmgr', 'auth_approved');
                    }
                }
            }
        }

        // Validate customisable profile fields. (profile_validation expects an object as the parameter with userid set)
        $dataobject = (object)$data;
        $dataobject->id = 0;
        $errors += profile_validation($dataobject, array());

        return $errors;
    }

    /**
     * Save a snapshot of inserted or updated record in auth_approved_request table.
     *
     * @param int $id id of request record
     * @return \stdClass the request record
     */
    protected static function snapshot_request($id) {
        global $DB, $USER;

        $record = $DB->get_record('auth_approved_request', array('id' => $id), '*', MUST_EXIST);

        $snapshot = clone($record);
        $snapshot->requestid = $snapshot->id;
        unset($snapshot->id);
        $snapshot->timesnapshot = time();
        $snapshot->usersnapshot = $USER->id;

        $DB->insert_record('auth_approved_request_snapshots', $snapshot);

        return $record;
    }

    /**
     * Sends a custom email message
     *
     * @param int $id id of record from auth_approved_request table
     * @param string $subject email subject
     * @param string $body email body
     * @return bool success
     */
    public static function send_message($id, $subject, $body) {
        global $DB;
        $record = $DB->get_record('auth_approved_request', array('id' => $id), '*', MUST_EXIST);
        return comms::email_custom_message($record->email, $subject, $body);
    }

    /**
     * Checks if the specified signup position id is a valid one.
     *
     * @param int $id position id to check.
     *
     * @return true if the position is valid.
     */
    public static function is_valid_signup_positionid($id) {
        return self::is_valid_signup_hierarchyid($id, 'position', 'allowposition', 'positionframeworks');
    }

    /**
     * Checks if the specified signup organisation id is a valid one.
     *
     * @param int $id organisation id to check.
     *
     * @return true if the organisation is valid.
     */
    public static function is_valid_signup_organisationid($id) {
        return self::is_valid_signup_hierarchyid($id, 'organisation', 'alloworganisation', 'organisationframeworks');
    }

    /**
     * Checks if the specified signup position/organization id is a valid one.
     *
     * @param int $id hierarchy id to check.
     * @param string $table table to lookup.
     * @param string $cfgidreqdkey key to use when looking up configuration to
     *        see if the hierarchy id field is required.
     * @param string $cfgfwkey key to use when looking up configuration to find
     *        the list of allowed hierarchy frameworks.
     *
     * @return true if the hierarchy is valid.
     */
    private static function is_valid_signup_hierarchyid($id, $table, $cfgidreqdkey, $cfgfwkey) {
        if (!get_config('auth_approved', $cfgidreqdkey)) {
            return false;
        }

        if (empty($id)) {
            return false;
        }

        $hierarchy = \hierarchy::load_hierarchy($table)->get_item($id);
        if (!$hierarchy) {
            return false;
        }

        $frameworks = get_config('auth_approved', $cfgfwkey);
        if (empty($frameworks) || strpos($frameworks, '-1') !== false) {
            return true;
        }

        $frameworkid = $hierarchy->frameworkid;
        return strpos($frameworks, "$frameworkid") !== false;
    }

    /**
     * Checks if the specified signup manager assignment id is a valid one.
     *
     * @param int $id manager job assignment id to check.
     *
     * @return true if the hierarchy is valid.
     */
    public static function is_valid_signup_mgrjaid($id) {
        if (!get_config('auth_approved', 'allowmanager')) {
            return false;
        }

        if (empty($id)) {
            return false;
        }

        $orgframeworks = get_config('auth_approved', 'managerorganisationframeworks');
        $constrainedbyorg = !empty($orgframeworks) && (strpos($orgframeworks, '-1') === false);

        $posframeworks = get_config('auth_approved', 'managerpositionframeworks');
        $constrainedbypos = !empty($posframeworks) && (strpos($posframeworks, '-1') === false);

        if (!$constrainedbyorg && !$constrainedbypos) {
            return true;
        }

        $job = \totara_job\job_assignment::get_with_id($id, false);
        if (!$job) {
            return false;
        }

        if ($constrainedbyorg && $job->organisationid) {
            $hierarchy = \hierarchy::load_hierarchy('organisation')->get_item($job->organisationid);
            $fwid = $hierarchy ? $hierarchy->frameworkid : -1;

            if (strpos($orgframeworks, "$fwid") !== false) {
                return true;
            }
        }

        if ($constrainedbypos && $job->positionid) {
            $hierarchy = \hierarchy::load_hierarchy('position')->get_item($job->positionid);
            $fwid = $hierarchy ? $hierarchy->frameworkid : -1;

            if (strpos($posframeworks, "$fwid") !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Deserialise the extradata field.
     *
     * @param int $id
     * @return array
     */
    public static function get_extradata($id): array {
        global $DB;

        $extradata = $DB->get_field('auth_approved_request', 'extradata', ['id' => $id]);
        if (empty($extradata)) {
            return [];
        }

        return json_decode($extradata, true);
    }
}