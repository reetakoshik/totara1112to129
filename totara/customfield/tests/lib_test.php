<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @package totara_customfield
 */

defined('MOODLE_INTERNAL') || die();

class totara_customfield_lib_testcase extends advanced_testcase {
    public function test_customfield_get_record_by_id() {
        global $CFG;
        require_once($CFG->dirroot . '/totara/customfield/lib.php');
        $this->resetAfterTest();

        /** @var totara_customfield_generator $cfgenerator */
        $cfgenerator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');
        $textids = $cfgenerator->create_text('course', array('text1', 'text2'));

        $field = customfield_get_record_by_id('course', $textids['text1'], 'text');
        $this->assertSame('text', $field->datatype);
        $this->assertEquals($field, customfield_get_record_by_id('course', $textids['text1'], ''));
        try {
            customfield_get_record_by_id('course', $textids['text1'], 'date');
            $this->fail('Exception expected when invalid datatype specified');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('dml_missing_record_exception', $e);
        }

        $field = customfield_get_record_by_id('course', 0, 'text');
        $this->assertSame(0, $field->id);
        $this->assertSame('', $field->description);
        $this->assertSame('', $field->defaultdata);
        $this->assertSame(0, $field->forceunique);

        try {
            customfield_get_record_by_id('course', 0, 'xyz');
            $this->fail('Exception expected when invalid datatype specified');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('invalid_parameter_exception', $e);
        }
        try {
            customfield_get_record_by_id('course', 0, '');
            $this->fail('Exception expected when invalid datatype specified');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('invalid_parameter_exception', $e);
        }
    }

    public function test_totara_customfield_set_hidden_by_id() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/totara/customfield/lib.php');
        $this->resetAfterTest();

        $tblprefix = 'facetoface_room';
        $shortname = 'location';

        $rs = $DB->get_record($tblprefix.'_info_field', ['shortname' => $shortname], 'id');
        $this->assertNotEmpty($rs, 'Id should exists');

        // Check the location is visible.
        $field = customfield_get_record_by_id($tblprefix, $rs->id, $shortname);
        $this->assertEquals($field->hidden, 0);
        // Change the location visibility to hidden.
        totara_customfield_set_hidden_by_id($tblprefix, $rs->id, $shortname);
        // Visibility should be hidden.
        $field = customfield_get_record_by_id($tblprefix, $rs->id, $shortname);
        $this->assertEquals($field->hidden, 1);
        // Change it back to visible.
        totara_customfield_set_hidden_by_id($tblprefix, $rs->id, $shortname);
        // Check it is visible.
        $field = customfield_get_record_by_id($tblprefix, $rs->id, $shortname);
        $this->assertEquals($field->hidden, 0);
        // Test for invalid id number.
        try {
            totara_customfield_set_hidden_by_id($tblprefix, 0, $shortname);
            $this->fail('Exception expected when invalid id specified');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('invalid_parameter_exception', $e);
        }
        try {
            totara_customfield_set_hidden_by_id($tblprefix, -1, $shortname);
            $this->fail('Exception expected when invalid id specified');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('invalid_parameter_exception', $e);
        }
    }
}