<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_core
 * @category test
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Totara generator.
 *
 * @package totara_core
 * @category test
 */
class totara_core_generator extends component_generator_base {
    /** @var number of created profile category instances */
    protected $userfieldcount = 0;
    /** @var number of created profile field instances */
    protected $userfieldcategorycount = 0;
    /** @var number of created totara field instances */
    protected $totarafieldcount = 0;

    /**
     * To be called from data reset code only, do not use in tests.
     *
     * @return void
     */
    public function reset() {
        $this->userfieldcount = 0;
        $this->userfieldcategorycount = 0;
        $this->totarafieldcount = 0;
    }

    /**
     * Create custom user profile field category.
     *
     * @param array|stdClass $record
     * @return stdClass the created category record
     */
    public function create_custom_profile_category($record = null) {
        global $CFG, $DB;
        require_once("$CFG->dirroot/user/profile/definelib.php");

        $record = (object)(array)$record;
        $this->userfieldcategorycount++;

        if (!isset($record->name)) {
            $record->name = 'Custom profile category ' . $this->userfieldcategorycount;
        }
        if (!isset($record->sortorder)) {
            // Add at the end.
            $record->sortorder = 999999 + $this->userfieldcategorycount;
        }

        $id = $DB->insert_record('user_info_category', $record);
        profile_reorder_categories();
        profile_reorder_fields();

        return $DB->get_record('user_info_category', array('id' => $id));
    }

    /**
     * Create custom user profile field.
     *
     * NOTE: the menu options may be separated by "/" instead of new lines.
     *
     * @param array|stdClass $record
     * @return stdClass the created field record
     */
    public function create_custom_profile_field($record) {
        global $CFG, $DB;
        require_once("$CFG->dirroot/user/profile/definelib.php");

        $record = (object)(array)$record;
        $this->userfieldcount++;
        $data = new stdClass();
        $data->id = 0;

        if (empty($record->datatype)) {
            throw new coding_exception('Must specify custom profile field data type in $record->datatype');
        }
        $data->datatype = $record->datatype;
        $data->shortname = isset($record->shortname) ? $record->shortname : 'field' . $this->userfieldcount;
        if (clean_param($data->shortname, PARAM_ALPHANUM) !== $data->shortname) {
            throw new coding_exception('Invalid custom profile field shortname in $record->shortname: ' . $data->shortname);
        }
        if ($DB->record_exists('user_info_field', array('shortname' => $data->shortname))) {
            throw new coding_exception('Duplicate custom profile field shortname in $record->shortname: ' . $data->shortname);
        }
        $data->name = !empty($record->name) ? $record->name : 'Custom profile field ' . $this->userfieldcount;
        $data->description = !empty($record->description) ? $record->description : 'Some description ' . $this->userfieldcount;
        $data->descriptionformat = isset($record->descriptionformat) ? $record->descriptionformat : FORMAT_HTML;
        $data->required = empty($record->required) ? 0 : 1;
        $data->locked = empty($record->locked) ? 0 : 1;
        $data->forceunique = empty($record->forceunique) ? 0 : 1;
        $data->signup = empty($record->signup) ? 0 : 1;
        $data->visible = (isset($record->visible) && $record->visible !== '') ? (int)$record->visible : 2;
        if (isset($record->categoryid)) {
            $data->categoryid = $record->categoryid;
        } else {
            $categories = $DB->get_records('user_info_category', null, 'sortorder ASC', '*', 0, 1);
            if ($categories) {
                $category = reset($categories);
            } else {
                $category = $this->create_custom_profile_category(array('name' => get_string('profiledefaultcategory', 'admin'), 'sortorder' => 1));
            }
            $data->categoryid = $category->id;
        }

        // Field type specific stuff - mangle the data so that we sidestep all hacks in field saving.

        if ($record->datatype === 'checkbox') {
            $data->defaultdata = empty($record->defaultdata) ? 0 : 1;

        } else if ($record->datatype === 'date') {
            $data->defaultdata = 0;  // No defaults.

        } else if ($record->datatype === 'datetime') {
            $data->defaultdata = 0;  // No defaults.
            $data->param1 = !empty($record->param1) ? (int)$record->param1 : strftime('%Y');
            $data->startyear = null;
            $data->param2 = !empty($record->param2) ? (int)$record->param2 : strftime('%Y');
            $data->endyear = null;
            $data->param3 = empty($record->param3) ? 0 : 1; // Show date.
            if ($data->param1 > $data->param2) {
                throw new coding_exception('Start date cannot be later than end date in date field');
            }

        } else if ($record->datatype === 'menu') {
            if (!isset($record->param1)) {
                throw new coding_exception('Menu field requires options in $record->param1');
            }
            if (is_array($record->param1)) {
                $data->param1 = implode("\n", $record->param1);
            } else {
                // Use "/" instead of newline character in behat to separate menu options.
                $data->param1 = str_replace('/', "\n", $record->param1);
            }
            $options = explode("\n", $data->param1);
            if (count($options) < 2) {
                throw new coding_exception('Menu field requires at least 2 options in $record->param1');
            }
            $data->defaultdata = !isset($record->defaultdata) ? '' : $record->defaultdata;
            if ($data->defaultdata !== '') {
                if (!in_array($data->defaultdata, $options)) {
                    throw new coding_exception('Menu field requires default to be one of the options in $record->param1');
                }
            }

        } else if ($record->datatype === 'text') {
            $data->defaultdata = isset($record->defaultdata) ? clean_param($record->defaultdata, PARAM_TEXT) : '';
            $data->param1 = (isset($record->param1) && $record->param1 !== '') ? (int)$record->param1 : 30;
            $data->param2 = (isset($record->param2) && $record->param2 !== '') ? (int)$record->param2 : 2048;
            $data->param3 = isset($record->param3) ? (int)(bool)$record->param3 : 0;
            $data->param4 = isset($record->param4) ? clean_param($record->param4, PARAM_URL) : '';
            $data->param5 = isset($record->param5) ? $record->param5 : '';

        } else if ($record->datatype === 'textarea') {
            $data->defaultdata = isset($record->defaultdata) ? $record->defaultdata : '';
            $data->defaultdataformat = 1;

        } else {
            throw new coding_exception('Invalid custom profile field type in $record->datatype: ' . $record->datatype);
        }

        require_once($CFG->dirroot.'/user/profile/field/' . $record->datatype. '/define.class.php');
        $newfield = 'profile_define_' . $record->datatype;
        /** @var profile_define_base $field */
        $field = new $newfield();
        $field->define_save($data);
        profile_reorder_fields();

        return $DB->get_record('user_info_field', array('id' => $data->id), '*', MUST_EXIST);
    }

    /**
     * Create custom course field.
     *
     * NOTE: the menu options may be separated by "/" instead of new lines.
     *
     * @param array|stdClass $record
     * @return stdClass the created field record
     */
    public function create_custom_course_field($record) {
        return $this->create_custom_field('course', $record);
    }

    /**
     * Create custom program/certification field.
     *
     * NOTE: the menu options may be separated by "/" instead of new lines.
     *
     * @param array|stdClass $record
     * @return stdClass the created field record
     */
    public function create_custom_program_field($record) {
        return $this->create_custom_field('prog', $record);
    }

    /**
     * Create general custom Totara field.
     *
     * @param string $prefix the short prefix used in db table names
     * @param array|stdClass $record
     * @return stdClass the created field record
     */
    public function create_custom_field($prefix, $record) {
        global $CFG, $DB;
        require_once("$CFG->dirroot/totara/customfield/definelib.php");
        require_once("$CFG->libdir/filelib.php");
        require_once("$CFG->libdir/formslib.php");

        $record = (object)(array)$record;
        $this->totarafieldcount++;
        $data = new stdClass();
        $data->id = 0;

        if (empty($prefix)) {
            throw new coding_exception('Must specify custom Totara field type in $type');
        }
        if (in_array($prefix, array('course', 'prog', 'facetoface_session', 'facetoface_signup', 'facetoface_cancellation'))) {
            if (!empty($record->typeid)) {
                throw new coding_exception('Cannot use typeid with $prefix: ' . $prefix);
            }
        } else if (in_array($prefix, array('comp_type', 'pos_type', 'org_type', 'goal_type', 'goal_user'))) {
            if (empty($record->typeid) or !is_numeric($record->typeid)) {
                throw new coding_exception('Generator requires typeid with $prefix: ' . $prefix);
            }
            $data->typeid = $record->typeid;

            // Set a sortorder here, we need to ensure its correct to the type. This API SUCKS!
            $sql = "SELECT id, sortorder
                  FROM {{$prefix}_info_field}
                 WHERE typeid = :typeid
              ORDER BY sortorder DESC";
            $result = $DB->get_records_sql($sql, ['typeid' => $data->typeid], 0, 1);
            if (empty($result)) {
                $data->sortorder = 1;
            } else {
                $record = reset($result);
                $data->sortorder = $record->sortorder + 1;
            }
        } else {
            throw new coding_exception('Prefix not supported in generator: ' . $prefix);
        }

        if (empty($record->datatype)) {
            throw new coding_exception('Must specify custom Totara field data type in $record->datatype');
        }
        $data->datatype = $record->datatype;
        $data->shortname = isset($record->shortname) ? $record->shortname : 'field' . $this->totarafieldcount;
        if (clean_param($data->shortname, PARAM_ALPHANUM) !== $data->shortname) {
            throw new coding_exception('Invalid custom Totara field shortname in $record->shortname: ' . $data->shortname);
        }
        if (empty($data->typeid)) {
            if ($DB->record_exists($prefix . '_info_field', array('shortname' => $data->shortname))) {
                throw new coding_exception('Duplicate custom Totara field shortname in $record->shortname: ' . $data->shortname);
            }
        } else {
            if ($DB->record_exists($prefix . '_info_field', array('shortname' => $data->shortname, 'typeid' => $data->typeid))) {
                throw new coding_exception('Duplicate custom Totara field shortname in $record->shortname: ' . $data->shortname);
            }
        }
        $data->fullname = !empty($record->fullname) ? $record->fullname : 'Custom field ' . $this->totarafieldcount;
        $data->description_editor = array();
        $data->description_editor['text'] = !empty($record->description) ? $record->description : 'Some description ' . $this->totarafieldcount;
        $data->description_editor['format'] = FORMAT_HTML;
        $data->hidden = empty($record->hidden) ? 0 : 1;
        $data->locked = empty($record->locked) ? 0 : 1;
        $data->required = empty($record->required) ? 0 : 1;
        $data->forceunique = empty($record->forceunique) ? 0 : 1;

        // Field type specific stuff - mangle the data so that we sidestep all hacks in field saving.

        if ($record->datatype === 'checkbox') {
            $data->defaultdata = empty($record->defaultdata) ? 0 : 1;

        } else if ($record->datatype === 'datetime') {
            $data->defaultdata = 0;  // No defaults.
            $data->param1 = !empty($record->param1) ? (int)$record->param1 : strftime('%Y');
            $data->startyear = null;
            $data->param2 = !empty($record->param2) ? (int)$record->param2 : strftime('%Y');
            $data->endyear = null;
            $data->param3 = empty($record->param3) ? 0 : 1; // Show date.
            if ($data->param1 > $data->param2) {
                throw new coding_exception('Start date cannot be later than end date in date field');
            }

        } else if ($record->datatype === 'file') {
            // No additional settings required for file custom field type.
        } else if ($record->datatype === 'menu') {
            if (!isset($record->param1)) {
                throw new coding_exception('Menu field requires options in $record->param1');
            }
            if (is_array($record->param1)) {
                $data->param1 = implode("\n", $record->param1);
            } else {
                // Use "/" instead of newline character in behat to separate menu options.
                $data->param1 = str_replace('/', "\n", $record->param1);
            }
            $options = explode("\n", $data->param1);
            if (count($options) < 2) {
                throw new coding_exception('Menu field requires at least 2 options in $record->param1');
            }
            $data->defaultdata = !isset($record->defaultdata) ? '' : $record->defaultdata;
            if (!empty($data->defaultdata)) {
                if (!in_array($data->defaultdata, $options)) {
                    throw new coding_exception('Menu field requires default to be one of the options in $record->param1');
                }
            }

        } else if ($record->datatype === 'multiselect') {
            if (empty($record->param1)) {
                throw new coding_exception('Multi-select field requires options in $record->param1');
            }
            if (is_array($record->param1)) {
                $data->multiselectitem = $record->param1;
            } else if (is_string($record->param1)) {
                $data->multiselectitem = json_decode($record->param1, true);
            } else {
                throw new coding_exception('Multi-select field requires options in $record->param1');
            }

        } else if ($record->datatype === 'text') {
            $data->defaultdata = isset($record->defaultdata) ? clean_param($record->defaultdata, PARAM_TEXT) : '';
            $data->param1 = (isset($record->param1) && $record->param1 !== '') ? (int)$record->param1 : 30;
            $data->param2 = (isset($record->param2) && $record->param2 !== '') ? (int)$record->param2 : 2048;

        } else if ($record->datatype === 'textarea') {
            $data->defaultdata_editor = array();
            $data->defaultdata_editor['text'] = isset($record->defaultdata) ? clean_param($record->defaultdata, PARAM_CLEANHTML) : '';
            $data->defaultdata_editor['format'] = FORMAT_HTML;
            $data->param1 = (isset($record->param1) && $record->param1 !== '') ? (int)$record->param1 : 30;
            $data->param2 = (isset($record->param2) && $record->param2 !== '') ? (int)$record->param2 : 10;

        } else {
            throw new coding_exception('Invalid custom Totara field type in $record->datatype: ' . $record->datatype);
        }

        require_once($CFG->dirroot.'/totara/customfield/field/' . $record->datatype. '/define.class.php');
        $newfield = 'customfield_define_' . $record->datatype;
        /** @var customfield_define_base $field */
        $field = new $newfield();
        $field->define_save($data, $prefix);

        return $DB->get_record($prefix . '_info_field', array('id' => $data->id), '*', MUST_EXIST);
    }

    /**
     * Assigns user profile custom fields.
     *
     * @param array $record custom field attributes.
     */
    public function create_profile_custom_field_assignment(array $record) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/user/profile/lib.php');

        $uid = $DB->get_field('user', 'id', array('username' => $record['username']));
        $user = new stdClass;
        $user->id = $uid;

        $custom_field = 'profile_field_' . $record['fieldname'];
        $user->$custom_field = trim($record['value']);

        profile_save_data($user);
    }
}