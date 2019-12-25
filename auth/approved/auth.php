<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/authlib.php');


final class auth_plugin_approved extends auth_plugin_base {
    /**
     * Constructor.
     */
    public function __construct() {
        $this->authtype = 'approved';
        $this->config = get_config('auth_approved');
    }

    /**
     * {@inheritdoc}
     */
    public function user_login($username, $password) {
        global $CFG, $DB;
        if ($user = $DB->get_record('user', array('username' => $username, 'mnethostid' => $CFG->mnet_localhost_id))) {
            return validate_internal_user_password($user, $password);
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function user_update_password($user, $newpassword) {
        $user = \get_complete_user_data('id', $user->id);
        set_user_preference('auth_approved_passwordupdatetime', time(), $user->id);
        return \update_internal_user_password($user, $newpassword);
    }

    /**
     * Return number of days to user password expires.
     *
     * If user password does not expire, it should return 0 or a positive value.
     * If user password is already expired, it should return negative value.
     *
     * @param string $username username
     * @return integer number of days
     */
    public function password_expire($username) {
        $expirationtime = get_config('auth_approved', 'expirationtime');
        if (!$expirationtime) {
            return 0;
        }
        $user = core_user::get_user_by_username($username, 'id,timecreated');
        if (!$user) {
            return 0;
        }

        $lastpasswordupdatetime = get_user_preferences('auth_approved_passwordupdatetime', $user->timecreated, $user->id);
        $expiretime = $lastpasswordupdatetime + $expirationtime * DAYSECS;
        $now = time();
        $result = ($expiretime - $now) / DAYSECS;
        if ($expiretime > $now) {
            return ceil($result);
        } else {
            return floor($result);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function can_signup() {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function signup_form() {
        global $CFG;
        $defaults = array(
            'requestid' => '0',
            'positionid' => '0',
            'organisationid' => '0',
            'city' => '',
            'country' => ''
        );
        if (!empty($CFG->defaultcity)) {
            $defaults['city'] = $CFG->defaultcity;
        }
        if (!empty($CFG->country) ){
            $defaults['country'] = $CFG->country;
        }
        if (!empty($CFG->country) ){
            $defaults['country'] = $CFG->country;
        }
        $defaults['lang'] = current_language();

        // Do we have external defaults?
        if (get_config('auth_approved', 'allowexternaldefaults')) {
            // These "defaults" are a feature, it allows the client to give users a URL that has pre-filled data.
            $positionid = optional_param('positionid', 0, PARAM_INT);
            if (!\auth_approved\request::is_valid_signup_positionid($positionid)) {
                $positionid = 0;
            }

            $organisationid = optional_param('organisationid', 0, PARAM_INT);
            if (!\auth_approved\request::is_valid_signup_organisationid($organisationid)) {
                $organisationid = 0;
            }

            $defaults['username'] = optional_param('username', '', PARAM_USERNAME);
            $defaults['firstname'] = optional_param('firstname', '', PARAM_NOTAGS);
            $defaults['lastname'] = optional_param('lastname', '', PARAM_NOTAGS);
            $defaults['email'] = optional_param('email', '', PARAM_EMAIL);
            $defaults['city'] = optional_param('city', $defaults['city'], PARAM_NOTAGS);
            $defaults['country'] = optional_param('country', '', PARAM_ALPHANUM);
            $defaults['positionid'] = $positionid;
            $defaults['positionfreetext'] = optional_param('positionfreetext', '', PARAM_NOTAGS);
            $defaults['organisationid'] = $organisationid;
            $defaults['organisationfreetext'] = optional_param('organisationfreetext', '', PARAM_NOTAGS);
            $defaults['managerfreetext'] = optional_param('managerfreetext', '', PARAM_NOTAGS);
        }

        // Regardless we need to pick up the managerjaid and translate it to an option if it is set and valid.
        $managerjaid = optional_param('managerjaid', null, PARAM_INT);
        if (!\auth_approved\request::is_valid_signup_mgrjaid($managerjaid)) {
            $managerjaid = null;
        }
        $defaults['managerjaid'] = $managerjaid;

        // Does anything want to alter the defaults? Please tread lightly, safety is off in hooks.
        $hook = new \auth_approved\hook\request_defaults($defaults);
        $hook->execute();
        $defaults = $hook->defaults;

        $options = [];
        if (!empty($defaults['managerjaid'])) {
            $title = auth_approved\util::get_manager_job_assignment_option($defaults['managerjaid']);
            if ($title) {
                $options[$defaults['managerjaid']] = $title;
            }
        }

        $form = new \auth_approved\form\signup(null, array('stage' => \auth_approved\request::STAGE_SIGNUP, 'managerjaoptions' => $options));
        $form->set_data($defaults);
        return $form;
    }

    /**
     * Process user sign up request coming from \auth_approved\form\signup form
     * via /login/signup.php page.
     *
     * @param $user \stdClass data from sign up form
     * @param $notify bool print notice with link and terminate, use false in tests only
     * @return bool success
     */
    public function user_signup($user, $notify = true) {
        global $PAGE, $OUTPUT;

        \auth_approved\request::add_request($user);

        if ($notify) {
            $PAGE->set_pagelayout('login');
            $PAGE->set_title(get_string('emailconfirm', 'auth_approved'));
            echo $OUTPUT->header();
            echo $OUTPUT->notification(get_string('emailconfirmsent', 'auth_approved', $user->email), 'notifysuccess');
            echo $OUTPUT->footer();
            die;
        }

        return true;
    }

    /**
     * Returns true if plugin allows confirming of new users.
     *
     * @return bool
     */
    public function can_confirm() {
        // The approval here is the ultimate confirmation,
        // the sing up table has a separate email confirmation mechanism.
        return false;
    }

    /**
     * Indicates if password hashes should be stored in local moodle database.
     *
     * @return bool true means md5 password hash stored in user table, false means flag 'not_cached' stored there instead
     */
    public function prevent_local_passwords() {
        return false;
    }

    /**
     * Returns true if this authentication plugin is 'internal'.
     *
     * @return bool
     */
    public function is_internal() {
        return true;
    }

    /**
     * Returns true if this authentication plugin can change the user's
     * password.
     *
     * @return bool
     */
    public function can_change_password() {
        return true;
    }

    /**
     * Returns the URL for changing the user's pw, or empty if the default can
     * be used.
     *
     * @return moodle_url
     */
    public function change_password_url() {
        return null; // use default internal method
    }

    /**
     * Returns true if plugin allows resetting of internal password.
     *
     * @return bool
     */
    public function can_reset_password() {
        return true;
    }

    /**
     * Returns true if plugin can be manually set.
     *
     * @return bool
     */
    public function can_be_manually_set() {
        return true;
    }

    /**
     * Prints a form for configuring this authentication plugin.
     *
     * This function is called from admin/auth.php, and outputs a full page with
     * a form for configuring this plugin.
     *
     * @codeCoverageIgnore
     */
    public function config_form($config, $err, $user_fields) {
        // Nothing to do we use settings.php now!
    }

    /**
     * Processes and stores configuration data for this authentication plugin.
     *
     * @codeCoverageIgnore
     */
    public function process_config($config) {
        // Nothing to do we use settings.php now!
        return false;
    }

    /**
     * Returns whether or not the captcha element is enabled, and the admin settings fulfil its requirements.
     * @return bool
     */
    public function is_captcha_enabled() {
        global $CFG;
        return !empty($CFG->recaptchapublickey) && !empty($CFG->recaptchaprivatekey) && get_config("auth_approved", 'recaptcha');
    }
}
