<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Profile field API library file.
 *
 * @package core_user
 * @copyright  2007 onwards Shane Elliot {@link http://pukunui.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Custom profile fields are visible to everybody who may view profile.
 */
define ('PROFILE_VISIBLE_ALL',     '2');
/**
 * All users may view own private profile fields, other users need
 * "moodle/user:viewalldetails" capability in user context
 * or "moodle/course:viewhiddenuserfields" capability in course context.
 */
define ('PROFILE_VISIBLE_PRIVATE', '1');
/**
 * Only users with "moodle/user:viewalldetails" capability in user context
 * or "moodle/course:viewhiddenuserfields" capability in course context
 * may see this field value.
 *
 * The value can be updated only by users with moodle/user:update capability
 * in user context.
 */
define ('PROFILE_VISIBLE_NONE',    '0');

/**
 * Base class for the customisable profile fields.
 *
 * @package core_user
 * @copyright  2007 onwards Shane Elliot {@link http://pukunui.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_field_base {

    // These 2 variables are really what we're interested in.
    // Everything else can be extracted from them.

    /** @var int */
    public $fieldid;

    /** @var int */
    public $userid;

    /** @var stdClass */
    public $field;

    /** @var string */
    public $inputname;

    /** @var mixed */
    public $data;

    /** @var string */
    public $dataformat;

    /**  @var bool Do not enforce field as required */
    protected $skiprequired = false;

    /**
     * Constructor method.
     * @param int $fieldid id of the profile from the user_info_field table
     * @param int $userid id of the user for whom we are displaying data
     */
    public function __construct($fieldid=0, $userid=0) {
        global $USER;

        $this->set_fieldid($fieldid);
        $this->set_userid($userid);
        $this->load_data();
    }

    /**
     * Old syntax of class constructor. Deprecated in PHP7.
     *
     * @deprecated since Moodle 3.1
     */
    public function profile_field_base($fieldid=0, $userid=0) {
        debugging('Use of class name as constructor is deprecated', DEBUG_DEVELOPER);
        self::__construct($fieldid, $userid);
    }

    /**
     * Abstract method: Adds the profile field to the moodle form class
     * @abstract The following methods must be overwritten by child classes
     * @param moodleform $mform instance of the moodleform class
     */
    public function edit_field_add($mform) {
        print_error('mustbeoveride', 'debug', '', 'edit_field_add');
    }

    /**
     * Display the data for this field
     * @return string
     */
    public function display_data() {
        $options = new stdClass();
        $options->para = false;
        return format_text($this->data, FORMAT_MOODLE, $options);
    }

    /**
     * Print out the form field in the edit profile page
     * @param moodleform $mform instance of the moodleform class
     * @return bool
     */
    public function edit_field($mform) {
        if ($this->field->visible != PROFILE_VISIBLE_NONE
          or has_capability('moodle/user:update', context_system::instance())) {

            $this->edit_field_add($mform);
            $this->edit_field_set_default($mform);
            $this->edit_field_set_required($mform);
            return true;
        }
        return false;
    }

    /**
     * Tweaks the edit form
     * @param moodleform $mform instance of the moodleform class
     * @return bool
     */
    public function edit_after_data($mform) {
        if ($this->field->visible != PROFILE_VISIBLE_NONE
          or has_capability('moodle/user:update', context_system::instance())) {
            $this->edit_field_set_locked($mform);
            return true;
        }
        return false;
    }

    /**
     * Saves the data coming from form
     * @param stdClass $usernew data coming from the form
     * @return mixed returns data id if success of db insert/update, false on fail, 0 if not permitted
     */
    public function edit_save_data($usernew) {
        global $DB;

        if (!isset($usernew->{$this->inputname})) {
            // Field not present in form, probably locked and invisible - skip it.
            return;
        }

        $data = new stdClass();

        $usernew->{$this->inputname} = $this->edit_save_data_preprocess($usernew->{$this->inputname}, $data);

        $data->userid  = $usernew->id;
        $data->fieldid = $this->field->id;
        $data->data    = $usernew->{$this->inputname};

        if ($dataid = $DB->get_field('user_info_data', 'id', array('userid' => $data->userid, 'fieldid' => $data->fieldid))) {
            $data->id = $dataid;
            $DB->update_record('user_info_data', $data);
        } else {
            $DB->insert_record('user_info_data', $data);
        }
    }

    /**
     * Validate the form field from profile page
     *
     * @param stdClass $usernew
     * @return  string  contains error message otherwise null
     */
    public function edit_validate_field($usernew) {
        global $DB;

        $errors = array();
        // Get input value.
        if (isset($usernew->{$this->inputname})) {
            $value = $usernew->{$this->inputname};
        } else {
            $value = '';
        }

        // Check for uniqueness of data if required.
        if ($this->is_unique() && ($value !== '')) {
            $sql = 'SELECT id, userid
                      FROM {user_info_data}
                     WHERE fieldid = :fieldid
                       AND userid != :userid
                       AND ' . $DB->sql_compare_text('data', 255) . ' = ' . $DB->sql_compare_text(':value', 255);
            $params = array('fieldid' => $this->field->id, 'userid' => $usernew->id, 'value' => $value);
            $data = $DB->get_records_sql($sql, $params);

            if ($data) {
                $errors[$this->inputname] = get_string('valuealreadyused');
            }
        }
        return $errors;
    }

    /**
     * Sets the default data for the field in the form object
     * @param  moodleform $mform instance of the moodleform class
     */
    public function edit_field_set_default($mform) {
        if (!empty($this->field->defaultdata)) {
            $mform->setDefault($this->inputname, $this->field->defaultdata);
        }
    }

    /**
     * Totara-specific function.
     * Does some extra pre-processing for totara sync uploads.
     * Only required for custom fields with several options
     * like menu of choices
     *
     * @param  stdClass $itemnew    The original syncitem to be processed, an incomplete user record from sync.
     * @return stdClass             The same $itemnew record after processing the customfield.
     */
    public function totara_sync_data_preprocess($itemnew) {
        return $itemnew;
    }

    /**
     * Sets the required flag for the field in the form object
     *
     * @param moodleform $mform instance of the moodleform class
     */
    public function edit_field_set_required($mform) {
        global $USER;
        if ($this->skiprequired) {
            return;
        }
        if ($this->is_required() && ($this->userid == $USER->id || isguestuser())) {
            $mform->addRule($this->inputname, get_string('required'), 'required', null, 'client');
        }
    }

    /**
     * HardFreeze the field if locked.
     * @param moodleform $mform instance of the moodleform class
     */
    public function edit_field_set_locked($mform) {
        if (!$mform->elementExists($this->inputname)) {
            return;
        }
        if ($this->is_locked() and !has_capability('moodle/user:update', context_system::instance())) {
            $mform->hardFreeze($this->inputname);
            $mform->setConstant($this->inputname, $this->data);
        }
    }

    /**
     * Hook for child classess to process the data before it gets saved in database
     * @param stdClass $data
     * @param stdClass $datarecord The object that will be used to save the record
     * @return  mixed
     */
    public function edit_save_data_preprocess($data, $datarecord) {
        return $data;
    }

    /**
     * Loads a user object with data for this field ready for the edit profile
     * form
     * @param stdClass $user a user object
     */
    public function edit_load_user_data($user) {
        if ($this->data !== null) {
            $user->{$this->inputname} = $this->data;
        }
    }

    /**
     * Loads a user object with data for this field ready for the export, such as a spreadsheet.
     *
     * @param object a user object
     */
    function export_load_user_data($user) {
        if ($this->data !== NULL) {
            $user->{$this->inputname} = $this->data;
        }
    }

    /**
     * Check if the field data should be loaded into the user object
     * By default it is, but for field types where the data may be potentially
     * large, the child class should override this and return false
     * @return bool
     */
    public function is_user_object_data() {
        return true;
    }

    /**
     * Accessor method: set the userid for this instance
     * @internal This method should not generally be overwritten by child classes.
     * @param integer $userid id from the user table
     */
    public function set_userid($userid) {
        $this->userid = $userid;
    }

    /**
     * Accessor method: set the fieldid for this instance
     * @internal This method should not generally be overwritten by child classes.
     * @param integer $fieldid id from the user_info_field table
     */
    public function set_fieldid($fieldid) {
        $this->fieldid = $fieldid;
    }

    /**
     * Accessor method: Load the field record and user data associated with the
     * object's fieldid and userid
     * @internal This method should not generally be overwritten by child classes.
     */
    public function load_data() {
        global $DB;

        // Load the field object.
        if (($this->fieldid == 0) or (!($field = $DB->get_record('user_info_field', array('id' => $this->fieldid))))) {
            $this->field = null;
            $this->inputname = '';
        } else {
            $this->field = $field;
            $this->inputname = 'profile_field_'.$field->shortname;
        }

        if (!empty($this->field)) {
            $params = array('userid' => $this->userid, 'fieldid' => $this->fieldid);
            if ($data = $DB->get_record('user_info_data', $params, 'data, dataformat')) {
                $this->data = $data->data;
                $this->dataformat = $data->dataformat;
            } else {
                $this->data = $this->field->defaultdata;
                $this->dataformat = FORMAT_HTML;
            }
        } else {
            $this->data = null;
        }
    }

    /**
     * Check if the field data is visible to the current user
     * @internal This method should not generally be overwritten by child classes.
     * @return bool
     */
    public function is_visible() {
        global $USER;

        switch ($this->field->visible) {
            case PROFILE_VISIBLE_ALL:
                return true;
            case PROFILE_VISIBLE_PRIVATE:
                if ($this->userid == $USER->id) {
                    return true;
                } else {
                    return has_capability('moodle/user:viewalldetails',
                            context_user::instance($this->userid));
                }
            case PROFILE_VISIBLE_NONE:
            default:
                return has_capability('moodle/user:viewalldetails',
                        context_user::instance($this->userid));
        }
    }

    /**
     * Check if the field data is considered empty
     * @internal This method should not generally be overwritten by child classes.
     * @return boolean
     */
    public function is_empty() {
        return ( ($this->data != '0') and empty($this->data));
    }

    /**
     * Check if the field is required on the edit profile page
     * @internal This method should not generally be overwritten by child classes.
     * @return bool
     */
    public function is_required() {
        return (boolean)$this->field->required;
    }

    /**
     * Check if the field is locked on the edit profile page
     * @internal This method should not generally be overwritten by child classes.
     * @return bool
     */
    public function is_locked() {
        return (boolean)$this->field->locked;
    }

    /**
     * Check if the field data should be unique
     * @internal This method should not generally be overwritten by child classes.
     * @return bool
     */
    public function is_unique() {
        return (boolean)$this->field->forceunique;
    }

    /**
     * Check if the field should appear on the signup page
     * @internal This method should not generally be overwritten by child classes.
     * @return bool
     */
    public function is_signup_field() {
        return (boolean)$this->field->signup;
    }

    /**
     * Do not enforce field as required even if it is defined as required
     * @param bool $skip
     */
    public function skip_required($skip = false) {
        $this->skiprequired = $skip;
    }
}

/**
 * Loads user profile field data into the user object.
 * @param stdClass $user
 */
function profile_load_data($user, $export = false) {
    global $CFG, $DB;

    if ($fields = $DB->get_records('user_info_field')) {
        foreach ($fields as $field) {
            require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
            $newfield = 'profile_field_'.$field->datatype;
            $formfield = new $newfield($field->id, $user->id);
            if ($export) {
                $formfield->export_load_user_data($user);
            } else {
                $formfield->edit_load_user_data($user);
            }
        }
    }
}

/**
 * Print out the customisable categories and fields for a users profile
 *
 * @param moodleform $mform instance of the moodleform class
 * @param int $userid id of user whose profile is being edited.
 */
function profile_definition($mform, $userid = 0) {
    global $CFG, $DB;

    // If user is "admin" fields are displayed regardless.
    $update = has_capability('moodle/user:update', context_system::instance());

    if ($categories = $DB->get_records('user_info_category', null, 'sortorder ASC')) {
        foreach ($categories as $category) {
            if ($fields = $DB->get_records('user_info_field', array('categoryid' => $category->id), 'sortorder ASC')) {

                // Check first if *any* fields will be displayed.
                $display = false;
                foreach ($fields as $field) {
                    if ($field->visible != PROFILE_VISIBLE_NONE) {
                        $display = true;
                    }
                }

                // Display the header and the fields.
                if ($display or $update) {
                    $mform->addElement('header', 'category_'.$category->id, format_string($category->name));
                    foreach ($fields as $field) {
                        require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
                        $newfield = 'profile_field_'.$field->datatype;
                        $formfield = new $newfield($field->id, $userid);
                        // Do not force entry of required fields when admin login in as a user
                        // and if the user is login in, the fields are required.
                        if (\core\session\manager::is_loggedinas()) {
                            $formfield->skip_required(true);
                        }
                        $formfield->edit_field($mform);
                    }
                }
            }
        }
    }
}

/**
 * Adds profile fields to user edit forms.
 * @param moodleform $mform
 * @param int $userid
 */
function profile_definition_after_data($mform, $userid) {
    global $CFG, $DB;

    $userid = ($userid < 0) ? 0 : (int)$userid;

    if ($fields = $DB->get_records('user_info_field')) {
        foreach ($fields as $field) {
            require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
            $newfield = 'profile_field_'.$field->datatype;
            $formfield = new $newfield($field->id, $userid);
            $formfield->edit_after_data($mform);
        }
    }
}

/**
 * Validates profile data.
 * @param stdClass $usernew
 * @param array $files
 * @return array
 */
function profile_validation($usernew, $files) {
    global $CFG, $DB;

    $err = array();
    if ($fields = $DB->get_records('user_info_field')) {
        foreach ($fields as $field) {
            require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
            $newfield = 'profile_field_'.$field->datatype;
            $formfield = new $newfield($field->id, $usernew->id);
            $err += $formfield->edit_validate_field($usernew, $files);
        }
    }
    return $err;
}

/**
 * Totara: Saves job assignment data specified from a self-registration.
 *
 * It can only account for a single job assignment. If a position, organisation or
 * manager job assignment are specified it will add the to the first job assignment (according
 * to sortorder) if there is one, or create a new job assignment.
 *
 * The settings for each data type (e.g. for positions: 'allowsignupposition') must be
 * enabled for this function to process that data.
 *
 * We would have checked that managerjaid is correct for the managerid in the signup_form validation.
 * If this function is being used elsewhere, make sure there is similar validation taking place.
 *
 * @param stdClass $userprofile - this might be data received from a form.
 */
function position_save_data($userprofile) {
    global $CFG;
    require_once($CFG->dirroot . '/totara/hierarchy/prefix/position/lib.php');

    $allowsignupposition = get_config('totara_job', 'allowsignupposition');
    $allowsignuporganisation = get_config('totara_job', 'allowsignuporganisation');
    $allowsignupmanager = get_config('totara_job', 'allowsignupmanager');

    if (!$allowsignupposition && !$allowsignuporganisation && !$allowsignupmanager) {
        return;
    }

    $data = new stdClass();
    if ($allowsignupmanager) {
        $data->managerjaid = isset($userprofile->managerjaid) ? $userprofile->managerjaid : null;
    }

    if ($allowsignuporganisation) {
        $data->organisationid = isset($userprofile->organisationid) ? $userprofile->organisationid : null;
    }

    if ($allowsignupposition) {
        $data->positionid = isset($userprofile->positionid) ? $userprofile->positionid : null;
    }

    if (empty($data->managerjaid) && empty($data->organisationid) && empty($data->positionid)) {
        // If nothing that uses a job assignment is to be added, then don't create a new job assignment.
        return;
    }

    $jobassignment = \totara_job\job_assignment::get_first($userprofile->id, false);
    if (empty($jobassignment)) {
        $data->userid = $userprofile->id;
        \totara_job\job_assignment::create_default($data->userid, $data);
    } else {
        $jobassignment->update($data);
    }
}

/**
 * Display current primary hierarchy information for the given user
 * @param  integer  userid
 * @return  void
 */
function profile_display_hierarchy_fields($userid) {
    global $DB;
    $sql = "SELECT p.fullname AS pos, o.fullname AS org, manager.id AS manid, " . get_all_user_name_fields(true, 'manager') . "
              FROM {job_assignment} staffja
         LEFT JOIN {job_assignment} managerja ON staffja.managerjaid = managerja.id
         LEFT JOIN {pos} p ON staffja.positionid = p.id
         LEFT JOIN {org} o ON staffja.organisationid = o.id
         LEFT JOIN {user} manager ON manager.id = managerja.userid
             WHERE staffja.userid = ?";

    $record = $DB->get_record_sql($sql, array($userid), IGNORE_MULTIPLE);

    if (isset($record->pos)) {
        echo html_writer::tag('dt', get_string('position', 'totara_job'));
        echo html_writer::tag('dd', $record->pos);
    }

    if (isset($record->org)) {
        echo html_writer::tag('dt', get_string('organisation', 'totara_job'));
        echo html_writer::tag('dd', $record->org);
    }

    if (isset($record->manid)) {
        $manurl = html_writer::link(new moodle_url('/user/profile.php', array("id" => $record->manid)), fullname($record));
        echo html_writer::tag('dt', get_string('manager', 'totara_job'));
        echo html_writer::tag('dd', $manurl);
    }
}

/**
 * Saves profile data for a user.
 * @param stdClass $usernew
 * @param boolean $sync     Totara - Whether this is being called from sync and needs pre-preprocessing.
 */
function profile_save_data($usernew, $sync = false) {
    global $CFG, $DB;

    if ($fields = $DB->get_records('user_info_field')) {
        foreach ($fields as $field) {
            require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
            $newfield = 'profile_field_'.$field->datatype;
            $formfield = new $newfield($field->id, $usernew->id);
            if ($sync) {
                $usernew = $formfield->totara_sync_data_preprocess($usernew);
            }
            $formfield->edit_save_data($usernew);
        }
    }
}

/**
 * Display profile fields.
 * @param int $userid
 */
function profile_display_fields($userid) {
    global $CFG, $USER, $DB;

    if ($categories = $DB->get_records('user_info_category', null, 'sortorder ASC')) {
        foreach ($categories as $category) {
            if ($fields = $DB->get_records('user_info_field', array('categoryid' => $category->id), 'sortorder ASC')) {
                foreach ($fields as $field) {
                    require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
                    $newfield = 'profile_field_'.$field->datatype;
                    $formfield = new $newfield($field->id, $userid);
                    if ($formfield->is_visible() and !$formfield->is_empty()) {
                        echo html_writer::tag('dt', format_string($formfield->field->name));
                        echo html_writer::tag('dd', $formfield->display_data());
                    }
                }
            }
        }
    }
}

/**
 * Adds code snippet to a moodle form object for custom profile fields that
 * should appear on the signup page
 * @param moodleform $mform moodle form object
 */
function profile_signup_fields($mform) {
    global $CFG, $DB;

    // Only retrieve required custom fields (with category information)
    // results are sort by categories, then by fields.
    $sql = "SELECT uf.id as fieldid, ic.id as categoryid, ic.name as categoryname, uf.datatype
                FROM {user_info_field} uf
                JOIN {user_info_category} ic
                ON uf.categoryid = ic.id AND uf.signup = 1 AND uf.visible<>0
                ORDER BY ic.sortorder ASC, uf.sortorder ASC";

    if ( $fields = $DB->get_records_sql($sql)) {
        foreach ($fields as $field) {
            // Check if we change the categories.
            if (!isset($currentcat) || $currentcat != $field->categoryid) {
                 $currentcat = $field->categoryid;
                 $mform->addElement('header', 'category_'.$field->categoryid, format_string($field->categoryname));
            }
            require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
            $newfield = 'profile_field_'.$field->datatype;
            $formfield = new $newfield($field->fieldid);
            $formfield->edit_field($mform);
        }
    }
}

function profile_signup_position($mform, $nojs, $positionid = 0) {
    global $DB;

    // Get the position title.
    $positiontitle = '';
    if ($positionid) {
        $positiontitle = $DB->get_field('pos', 'fullname', array('id' => $positionid));
    }

    // Position details.
    if ($nojs) {
        $allpositions = $DB->get_records_menu('pos', array('visible' => '1'), 'frameworkid,sortthread', 'id,fullname');
        if (is_array($allpositions) && !empty($allpositions)) {
            $mform->addElement('select', 'positionid', get_string('chooseposition', 'totara_job'),
                array(0 => get_string('chooseposition', 'totara_job')) + $allpositions);
        } else {
            $mform->addElement('static', 'positionid', get_string('chooseposition', 'totara_job'), get_string('noposition', 'totara_job') );
        }
        $mform->addHelpButton('positionid', 'chooseposition', 'totara_job');
    } else {
        $class = strlen($positiontitle) ? 'nonempty' : '';
        $mform->addElement('static', 'positionselector', get_string('position', 'totara_job'),
            html_writer::tag('span', $positiontitle, array('class' => $class, 'id' => 'positiontitle')).
            html_writer::empty_tag('input', array('type' => 'button', 'value' => get_string('chooseposition', 'totara_job'), 'id' => 'show-position-dialog'))
        );
        $mform->addElement('hidden', 'positionid');
        $mform->setType('positionid', PARAM_INT);
        $mform->setDefault('positionid', 0);
        $mform->addHelpButton('positionselector', 'chooseposition', 'totara_job');
    }
}

function profile_signup_organisation($mform, $nojs, $organisationid = 0) {
    global $DB;

    // Get the organisation title.
    $organisationtitle = '';
    if ($organisationid) {
        $organisationtitle = $DB->get_field('org', 'fullname', array('id' => $organisationid));
    }

    // Organisation details.
    if ($nojs) {
        $allorgs = $DB->get_records_menu('org', array('visible' => '1'), 'frameworkid,sortthread', 'id,fullname');
        if (is_array($allorgs) && !empty($allorgs)) {
            $mform->addElement('select', 'organisationid', get_string('chooseorganisation', 'totara_job'),
                array(0 => get_string('chooseorganisation', 'totara_job')) + $allorgs);
        } else {
            $mform->addElement('static', 'organisationid', get_string('chooseorganisation', 'totara_job'), get_string('noorganisation', 'totara_job') );
        }
        $mform->addHelpButton('organisationid', 'chooseorganisation', 'totara_job');
    } else {
        $class = strlen($organisationtitle) ? 'nonempty' : '';
        $mform->addElement('static', 'organisationselector', get_string('organisation', 'totara_job'),
            html_writer::tag('span', $organisationtitle, array('class' => $class, 'id' => 'organisationtitle')) .
            html_writer::empty_tag('input', array('type' => 'button', 'value' => get_string('chooseorganisation', 'totara_job'), 'id' => 'show-organisation-dialog'))
        );

        $mform->addElement('hidden', 'organisationid');
        $mform->setType('organisationid', PARAM_INT);
        $mform->setDefault('organisationid', 0);
        $mform->addHelpButton('organisationselector', 'chooseorganisation', 'totara_job');
    }
}

function profile_signup_manager($mform, $nojs, $managerjaid = 0) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/totara/job/lib.php');

    // Get the managers name.
    $managername = '';
    if ($managerjaid) {
        $managerja = \totara_job\job_assignment::get_with_id($managerjaid);
        // Get the fields required to display the name of a user.
        $usernamefields = get_all_user_name_fields(true);
        $manager = $DB->get_record('user', array('id' => $managerja->userid), $usernamefields);
        // Get the manager name.
        $canviewemail = in_array('email', get_extra_user_fields(context_system::instance()));
        $managername = totara_job_display_user_job($manager, $managerja, $canviewemail);
    }

    // Manager details.
    if ($nojs) {
        $allmanagers = $DB->get_records_sql_menu("
                    SELECT
                        u.id,
                        " . $DB->sql_fullname('u.firstname', 'u.lastname') . " AS fullname
                    FROM
                        {user} u
                    ORDER BY
                        u.firstname,
                        u.lastname");
        if (is_array($allmanagers) && !empty($allmanagers)) {
            // Manager.
            $mform->addElement('select', 'managerid', get_string('choosemanager', 'totara_job'),
                array(0 => get_string('choosemanager', 'totara_job')) + $allmanagers);
            $mform->setType('managerid', PARAM_INT);
        } else {
            $mform->addElement('static', 'managerid', get_string('choosemanager', 'totara_job'),
                get_string('error:dialognotreeitems', 'totara_core'));

        }
        $mform->addHelpButton('managerid', 'choosemanager', 'totara_job');
    } else {
        $class = strlen($managername) ? 'nonempty' : '';
        // Show manager
        $mform->addElement(
            'static',
            'managerselector',
            get_string('manager', 'totara_job'),
            html_writer::tag('span', $managername, array('class' => $class, 'id' => 'managertitle')) .
            html_writer::empty_tag('input', array('type' => 'button', 'value' => get_string('choosemanager', 'totara_job'), 'id' => 'show-manager-dialog'))
        );

        $mform->addElement('hidden', 'managerid');
        $mform->setType('managerid', PARAM_INT);
        $mform->addHelpButton('managerselector', 'choosemanager', 'totara_job');
        $mform->addElement('hidden', 'managerjaid');
        $mform->setType('managerjaid', PARAM_INT);
        $mform->setDefault('managerjaid', $managerjaid);
    }
}

/**
 * Returns an object with the custom profile fields set for the given user
 * @param integer $userid
 * @param bool $onlyinuserobject True if you only want the ones in $USER.
 * @return stdClass
 */
function profile_user_record($userid, $onlyinuserobject = true) {
    global $CFG, $DB;

    $usercustomfields = new stdClass();

    if ($fields = $DB->get_records('user_info_field')) {
        foreach ($fields as $field) {
            require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
            $newfield = 'profile_field_'.$field->datatype;
            $formfield = new $newfield($field->id, $userid);
            if (!$onlyinuserobject || $formfield->is_user_object_data()) {
                $usercustomfields->{$field->shortname} = $formfield->data;
            }
        }
    }

    return $usercustomfields;
}

/**
 * Obtains a list of all available custom profile fields, indexed by id.
 *
 * Some profile fields are not included in the user object data (see
 * profile_user_record function above). Optionally, you can obtain only those
 * fields that are included in the user object.
 *
 * To be clear, this function returns the available fields, and does not
 * return the field values for a particular user.
 *
 * @param bool $onlyinuserobject True if you only want the ones in $USER
 * @return array Array of field objects from database (indexed by id)
 * @since Moodle 2.7.1
 */
function profile_get_custom_fields($onlyinuserobject = false) {
    global $DB, $CFG;

    // Get all the fields.
    $fields = $DB->get_records('user_info_field', null, 'id ASC');

    // If only doing the user object ones, unset the rest.
    if ($onlyinuserobject) {
        foreach ($fields as $id => $field) {
            require_once($CFG->dirroot . '/user/profile/field/' .
                    $field->datatype . '/field.class.php');
            $newfield = 'profile_field_' . $field->datatype;
            $formfield = new $newfield();
            if (!$formfield->is_user_object_data()) {
                unset($fields[$id]);
            }
        }
    }

    return $fields;
}

/**
 * Load custom profile fields into user object
 *
 * Please note originally in 1.9 we were using the custom field names directly,
 * but it was causing unexpected collisions when adding new fields to user table,
 * so instead we now use 'profile_' prefix.
 *
 * @param stdClass $user user object
 */
function profile_load_custom_fields($user) {
    $user->profile = (array)profile_user_record($user->id);
}

/**
 * Trigger a user profile viewed event.
 *
 * @param stdClass  $user user  object
 * @param stdClass  $context  context object (course or user)
 * @param stdClass  $course course  object
 * @since Moodle 2.9
 */
function profile_view($user, $context, $course = null) {

    $eventdata = array(
        'objectid' => $user->id,
        'relateduserid' => $user->id,
        'context' => $context
    );

    if (!empty($course)) {
        $eventdata['courseid'] = $course->id;
        $eventdata['other'] = array(
            'courseid' => $course->id,
            'courseshortname' => $course->shortname,
            'coursefullname' => $course->fullname
        );
    }

    $event = \core\event\user_profile_viewed::create($eventdata);
    $event->add_record_snapshot('user', $user);
    $event->trigger();
}

/**
 * Does the user have all required custom fields set?
 *
 * Internal, to be exclusively used by {@link user_not_fully_set_up()} only.
 *
 * Note that if users have no way to fill a required field via editing their
 * profiles (e.g. the field is not visible or it is locked), we still return true.
 * So this is actually checking if we should redirect the user to edit their
 * profile, rather than whether there is a value in the database.
 *
 * @param int $userid
 * @return bool
 */
function profile_has_required_custom_fields_set($userid) {
    global $DB;

    $sql = "SELECT f.id
              FROM {user_info_field} f
         LEFT JOIN {user_info_data} d ON (d.fieldid = f.id AND d.userid = ?)
             WHERE f.required = 1 AND f.visible > 0 AND f.locked = 0 AND (d.id IS NULL OR d.data = '')";

    if ($DB->record_exists_sql($sql, [$userid])) {
        return false;
    }

    return true;
}
