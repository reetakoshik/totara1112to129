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
 * Menu profile field.
 *
 * @package    profilefield_menu
 * @copyright  2007 onwards Shane Elliot {@link http://pukunui.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class profile_field_menu
 *
 * @copyright  2007 onwards Shane Elliot {@link http://pukunui.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_field_menu extends profile_field_base {

    /**
     * Options as they defined in database
     * @var string[] $options
     */
    public $options;

    /**
     * Options to be used in form select element (formatted)
     * @var string[] $options
     */
    public $formoptions;

    public static $optionscache = array();

    /** @var int $datakey */
    public $datakey;

    /**
     * Constructor method.
     *
     * Pulls out the options for the menu from the database and sets the the corresponding key for the data if it exists.
     *
     * @param int $fieldid
     * @param int $userid
     */
    public function __construct($fieldid = 0, $userid = 0) {
        // First call parent constructor.
        parent::__construct($fieldid, $userid);

        // Param 1 for menu type is the options.
        if (isset($this->field->param1)) {
            $this->options = explode("\n", $this->field->param1);
        } else {
            $this->options = array();
        }

        // Static cache used for performance
        if (!isset(self::$optionscache[$fieldid]) or PHPUNIT_TEST) {
            self::$optionscache[$fieldid] = array();

            // TOTARA - changed to always use choosedots as it never makes sense to blindly save the first value.
            self::$optionscache[$fieldid][''] = get_string('choosedots');
            foreach ($this->options as $key => $option) {
                self::$optionscache[$fieldid][$key] = format_string($option, true, ['context' => context_system::instance()]); // Multilang formatting.
            }
        }

        /// Set the data key.
        $this->datakey = '';
        if ($this->data !== null) {
            // Returns false if no match found, so we can't just
            // cast to an integer.
            $match = array_search($this->data, $this->options);
            if ($match !== false) {
                $this->datakey = (int)$match;
            }
        }

        $this->formoptions = self::$optionscache[$fieldid];
    }

    /**
     * Old syntax of class constructor. Deprecated in PHP7.
     *
     * @deprecated since Moodle 3.1
     */
    public function profile_field_menu($fieldid=0, $userid=0) {
        debugging('Use of class name as constructor is deprecated', DEBUG_DEVELOPER);
        self::__construct($fieldid, $userid);
    }

    /**
     * Create the code snippet for this field instance
     * Overwrites the base class method
     * @param moodleform $mform Moodle form instance
     */
    public function edit_field_add($mform) {
        $mform->addElement('select', $this->inputname, format_string($this->field->name), $this->formoptions);
    }

    /**
     * Set the default value for this field instance
     * Overwrites the base class method.
     * @param moodleform $mform Moodle form instance
     */
    public function edit_field_set_default($mform) {
        if (false !== array_search($this->field->defaultdata, $this->options)) {
            $defaultkey = (int)array_search($this->field->defaultdata, $this->options);
        } else {
            $defaultkey = '';
        }
        $mform->setDefault($this->inputname, $defaultkey);
    }

    /**
     * Totara-specific function.
     * Changes the customfield value from a string to the key that matches
     * the string in the array of options.
     *
     * @param  stdClass $itemnew    The original sync record to be processed, an incomplete user record.
     * @return stdClass             The same $itemnew record after processing the customfield.
     */
    public function totara_sync_data_preprocess($itemnew) {
        // Get the sync value out of the item.
        $fieldname = $this->inputname;

        if (isset($itemnew->$fieldname)) {
            $value = $itemnew->$fieldname;
        } else {
            // No point preprocessing a non-existant value.
            return $itemnew;
        }

        if ($itemnew->$fieldname === "") {
            return $itemnew;
        }

        // Now get the corresponding option for that value.
        $selected = null;
        foreach ($this->options as $key => $option) {
            // Totara Sync matches with case insensitivity, do the same in sync preprocess for consistency.
            // However, all other information (for example Multi-Language tags) must be exactly the same as defined in customfield).
            if (core_text::strtolower($option) === core_text::strtolower($value)) {
                $selected = $key;
                break;
            }
        }

        $itemnew->$fieldname = $selected;
        return $itemnew;
    }

    /**
     * The data from the form returns the key.
     *
     * This should be converted to the respective option string to be saved in database
     * Overwrites base class accessor method.
     *
     * @param mixed $data The key returned from the select input in the form
     * @param stdClass $datarecord The object that will be used to save the record
     * @return mixed Data or null
     */
    public function edit_save_data_preprocess($data, $datarecord) {
        return (isset($this->options[$data]) && $data !== '') ? $this->options[$data] : '';
    }

    /**
     * When passing the user object to the form class for the edit profile page
     * we should load the key for the saved data
     *
     * Overwrites the base class method.
     *
     * @param stdClass $user User object.
     */
    public function edit_load_user_data($user) {
        $user->{$this->inputname} = $this->datakey;
    }

    /**
     * Function export the user readable value of the selected
     * menu item
     *
     * @param stdClass $user
     * @return string selected item
     */
    public function export_load_user_data($user) {
        if (empty($this->data)) {
            $user->{$this->inputname} = '';
        } else {
            $user->{$this->inputname} = clean_text($this->options[$this->datakey]);
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
            $mform->setConstant($this->inputname, $this->datakey);
        }
    }
    /**
     * Convert external data (csv file) from value to key for processing later by edit_save_data_preprocess
     *
     * @param string $value one of the values in menu options.
     * @return int options key for the menu
     */
    public function convert_external_data($value) {
        $retval = array_search($value, $this->options);

        // If value is not found in options then return null, so that it can be handled
        // later by edit_save_data_preprocess.
        if ($retval === false) {
            $retval = null;
        }
        return $retval;
    }

    /**
     * Validate the form field from profile page.
     *
     * @param stdClass $usernew
     * @return string contains error message otherwise null
     */
    public function edit_validate_field($usernew) {
        if (isset($usernew->{$this->inputname}) && isset($this->options[$usernew->{$this->inputname}])) {
            // Make sure the text of the selection option is used and not the value.
            $usernew->{$this->inputname} = $this->options[$usernew->{$this->inputname}];
        }

        return parent::edit_validate_field($usernew);
    }

}


