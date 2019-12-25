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

defined('MOODLE_INTERNAL') || die();

class hierarchy_customfield_testcase extends advanced_testcase {

    /**
     * Tests the interface when dealing with a single customfield instance using some mock data.
     *
     * The normal way to get the instance would be with a call to get_all(), but this should be
     * tested with tests of each hierarchy types custom field processing.
     */
    public function test_single_instance() {
        $customfield = new \tool_totara_sync\internal\hierarchy\customfield();

        $type_record = new stdClass();
        $type_record->fullname = 'Type full name';
        $type_record->idnumber = 'Type ID number';
        $customfield->set_type($type_record);

        $customfield_info_record = new stdClass();
        $customfield_info_record->shortname = 'customfieldshortname';
        $customfield_info_record->fullname = 'Custom field full name';
        $customfield_info_record->typeid = 123;
        $customfield_info_record->datatype = 'mockdatatype';
        $customfield->set_info_field($customfield_info_record);

        $this->assertEquals('customfield_123_customfieldshortname', $customfield->get_key());
        $this->assertEquals('Custom field full name (Type full name)', $customfield->get_title());
        $this->assertEquals('customfieldshortname (Type ID number)', $customfield->get_shortname_with_type());
        $this->assertEquals('customfield_customfieldshortname', $customfield->get_default_fieldname());
        $this->assertEquals('import_customfield_123_customfieldshortname', $customfield->get_import_setting_name());
        $this->assertEquals('fieldmapping_customfield_123_customfieldshortname', $customfield->get_fieldmapping_setting_name());
        $this->assertEquals('mockdatatype', $customfield->get_datatype());
    }
}