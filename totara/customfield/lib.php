<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @package totara
 * @subpackage totara_customfield
 */

/**
 * Serves customfield file type files. Required for M2 File API
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return void
 */
function totara_customfield_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options = array()) {
    // Area management classes were added in Totara 9.7, and did not exist previously.
    // These allow us to check on valid fileareas, however they may not exist for custom code.
    // If they do not we use the previous behaviour which is to just serve everything.... its a bit loose!
    $helper = \totara_customfield\helper::get_instance();
    if (!$helper->check_if_filearea_recognised($filearea)) {
        // If you get here because of the following error log then there is a Totara customfield area that does not have a management
        // class. The consequence of which is that file security cannot be properly managed.
        // We call error log and not debugging as we are serving a file.
        $helper->legacy_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options);
    }

    // The management class exists, we can use it to server the file.
    $class = $helper->get_area_class_by_filearea($filearea);
    $class::pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options);

    // We shouldn't get here.... but just in case.
    send_file_not_found();
}

/**
 * Retrieve a list of all the available data types
 * @return   array   a list of the datatypes suitable to use in a select statement
 */
function customfield_list_datatypes() {
    global $CFG;

    $datatypes = array();

    if ($dirlist = get_directory_list($CFG->dirroot.'/totara/customfield/field', '', false, true, false)) {
        foreach ($dirlist as $type) {
            $datatypes[$type] = get_string('customfieldtype'.$type, 'totara_customfield');
            if (strpos($datatypes[$type], '[[') !== false) {
                $datatypes[$type] = get_string('customfieldtype'.$type, 'admin');
            }
        }
    }
    asort($datatypes);

    return $datatypes;
}

/**
 * Get custom field record based on it's id.
 *
 * @param string $tableprefix The table prefix where the custom field should be
 * @param int $id The ID of the customfield we want to find
 * @param string $datatype Custom field type
 * @return stdClass $field an instance of the custom field. If it's not found, a new instance is create with default values
 */
function customfield_get_record_by_id($tableprefix, $id, $datatype) {
    global $DB;

    if ($id) {
        if ($datatype !== '') {
            $field = $DB->get_record($tableprefix.'_info_field', array('id' => $id, 'datatype' => $datatype), '*', MUST_EXIST);
        } else {
            $field = $DB->get_record($tableprefix.'_info_field', array('id' => $id), '*', MUST_EXIST);
        }
    } else {
        $datatypes = customfield_list_datatypes();
        if (!isset($datatypes[$datatype])) {
            throw new invalid_parameter_exception('Unkwnown datatype');
        }

        $field = new stdClass();
        $field->id = 0;
        $field->datatype = $datatype;
        $field->description = '';
        $field->defaultdata = '';
        $field->forceunique = 0;
    }

    return $field;
}

/**
 * Get an instance of a custom field type. Used when creating a new custom field.
 *
 * @param string $prefix The custom field prefix
 * @param \context $sitecontext The context
 * @param array $extrainfo Array with extra info to create the custom field instance
 * @return a custom field instance based on the information provided
 */
function get_customfield_type_instace($prefix, $sitecontext, $extrainfo) {
    if (isset($extrainfo["class"]) && $extrainfo["class"] == 'personal') {
        $classname = 'totara_customfield\\prefix\\'. $prefix . '_user';
    } else {
        $classname = 'totara_customfield\\prefix\\'. $prefix . '_type';
    }

    if (!class_exists($classname)) {
        print_error('prefixtypeclassnotfound', 'totara_customfield');
    }

    return new $classname($prefix, $sitecontext, $extrainfo);
}

/**
 * Toggles custom field hidden property depending on its current value.
 *
 * @param string $tableprefix The database table prefix
 * @param int $id The record id to update the data
 * @param string $datatype The custom field data type
 */
function totara_customfield_set_hidden_by_id($tableprefix, $id, $datatype = '') {
    global $DB;

    if ((int)$id <= 0) {
        throw new invalid_parameter_exception('Unkwnown id number');
    }
    $field = customfield_get_record_by_id($tableprefix, $id, $datatype);
    $hidden = (int)!$field->hidden;
    $DB->set_field($tableprefix.'_info_field', 'hidden', $hidden, ['id' => $id]);
}
