<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package tool_totara_sync
 */

namespace tool_totara_sync\internal\hierarchy;
use hierarchy;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/hierarchy/lib.php');

/**
 * Class customfield
 *
 * Represents the settings of custom fields that are used within hierarchies.
 */
class customfield {

    /**
     * Identifier for this customfield. E.g. would be the key in the array when returned by get_all.
     *
     * @var string
     */
    private $key;

    /**
     * The short name of this hierarchy custom field.
     *
     * This should be unique within an individual type. But can be duplicated when looking across different types.
     *
     * @var string
     */
    private $shortname;

    /**
     * The full name of this hierarchy custom field.
     *
     * Not unique.
     *
     * @var string
     */
    private $fullname;

    /**
     * The database id of the type that the hierarchy custom field belongs to.
     *
     * @var int
     */
    private $typeid;

    /**
     * The full name of the type that the hierarchy custom field belongs to.
     *
     * Not unique.
     *
     * @var string
     */
    private $typefullname;

    /**
     * The id number of the type that the hierarchy custom field belongs to.
     *
     * Should be unique.
     *
     * @var string
     */
    private $typeidnumber;

    /**
     * The data type of the hierarchy custom field. e.g. 'date' or 'checkbox'.
     *
     * See the directory names in totata/customfield/field for possible types.
     *
     * @var string
     */
    private $datatype;

    /**
     * Set the information about the custom field from the {$prefix}_info_field table.
     *
     * @param \stdClass $info_field_record Record from the {$prefix}_info_field table.
     */
    public function set_info_field($info_field_record) {
        $this->shortname = $info_field_record->shortname;
        $this->fullname = $info_field_record->fullname;
        $this->typeid = $info_field_record->typeid;
        $this->datatype = $info_field_record->datatype;

        $this->key = 'customfield_' . $this->typeid . '_' . $this->shortname;
    }

    /**
     * Set the information about the hierarchy type from the type table.
     *
     * @param \stdClass $type_record Record from the type table.
     */
    public function set_type($type_record) {
        $this->typefullname = $type_record->fullname;
        $this->typeidnumber = $type_record->idnumber;
    }

    /**
     * Get an array of customfield instances that will relate to all custom fields for the given type.
     *
     * @param hierarchy $hierarchy Instance of the relevant hierarchy to retrieve custom field definitions for.
     *   The hierarchy instance can be empty as such, i.e. nothing needs to be loaded from the database for it.
     *   We simply take this object to confirm we are dealing with a genuine hierarchy and so that we can retrieve
     *   info such as its shortprefix value.
     * @return customfield[]
     */
    public static function get_all(hierarchy $hierarchy) {
        global $DB;

        $customfields = [];

        $prefix = $hierarchy->shortprefix . '_type';

        $types = $DB->get_records($prefix);

        $customfieldrecords = $DB->get_records($prefix . '_info_field');
        foreach ($customfieldrecords as $customfieldrecord) {

            // TODO - Implement sync for file custom fields.
            if ($customfieldrecord->datatype == 'file') {
                continue;
            }

            $customfield = new customfield();
            $customfield->set_info_field($customfieldrecord);
            $customfield->set_type($types[$customfield->typeid]);
            $customfields[$customfield->get_key()] = $customfield;
        }

        return $customfields;
    }

    /**
     * The identifier for the customfield from within the array returned by the get_all method.
     *
     * It's best to keep use of this key to only be internal within the hierarchy customfield code itself
     * if possible. Uses of it can be wrapped in methods that provide a clearer API,
     * e.g. the get_import_setting_name() method.
     *
     * @return string
     */
    public function get_key() {
        return $this->key;
    }

    /**
     * Get the text to describe this custom field and it's type. i.e. the full names.
     *
     * @return string
     */
    public function get_title() {
        $a = new \stdClass();
        $a->customfield_fullname = $this->fullname;
        $a->type_fullname = $this->typefullname;

        return get_string('customfieldfullnamewithtype', 'tool_totara_sync', $a);
    }

    /**
     * Get the short and unique text to describe this custom field and it's type. i.e. the shortname and id number.
     *
     * Be aware that the shortname itself may not be unique. It only needs to be unique within its type.
     *
     * @return string
     */
    public function get_shortname_with_type() {
        $a = new \stdClass();
        $a->customfield_shortname = $this->shortname;
        $a->type_idnumber = $this->typeidnumber;

        return get_string('customfieldshortnamewithtype', 'tool_totara_sync', $a);
    }

    /**
     * Get the default field heading when importing a custom field.
     *
     * @return string
     */
    public function get_default_fieldname() {
        return 'customfield_' . $this->shortname;
    }

    /**
     * Get the name of the config setting that tells us whether this custom field should be imported.
     *
     * @return string
     */
    public function get_import_setting_name() {
        return 'import_' . $this->get_key();
    }

    /**
     * Get the name of the config setting that tells us of any custom heading for this field.
     *
     * @return string
     */
    public function get_fieldmapping_setting_name() {
        return 'fieldmapping_' . $this->get_key();
    }

    /**
     * Get the data type for this custom field.
     *
     * @return string
     */
    public function get_datatype() {
        return $this->datatype;
    }

    /**
     * Get the id number for this custom field type.
     *
     * @return string
     */
    public function get_typeidnumber() {
        return $this->typeidnumber;
    }
}