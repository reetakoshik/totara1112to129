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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package totara_customfield
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/customfield/lib.php');

class totara_customfield_delete_testcase extends advanced_testcase {

    protected $course1 = null;
    protected $course2 = null;

    protected function tearDown() {
        $this->course1 = null;
        $this->course2 = null;
        parent::tearDown();
    }

    public function setUp() {
        parent::setUp();

        // Create course customfields.
        $cfgenerator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');
        $textids = $cfgenerator->create_text('course', array('text1'));
        $multids = $cfgenerator->create_multiselect('course', array('multi1' => array('opt1', 'opt2')));

        // Create course 1.
        $this->course1 = $this->getDataGenerator()->create_course(array('fullname' => 'Course 1'));
        // Add customfields data to course 1.
        $cfgenerator->set_text($this->course1, $textids['text1'], 'value1', 'course', 'course');
        $cfgenerator->set_multiselect($this->course1, $multids['multi1'], array('opt1', 'opt2'), 'course', 'course');

        // Create course 2.
        $this->course2 = $this->getDataGenerator()->create_course(array('fullname' => 'Course 2'));
        // Add customfields data to course 2.
        $cfgenerator->set_text($this->course2, $textids['text1'], 'value1', 'course', 'course');
        $cfgenerator->set_multiselect($this->course2, $multids['multi1'], array('opt1', 'opt2'), 'course', 'course');
    }

    public function test_customfield_delete() {
        global $DB;

        $this->resetAfterTest();

        $contextsystem = context_system::instance();
        // Assert that records exist.
        $cif = $DB->get_records('course_info_field');
        $this->assertCount(2, $cif);

        list($sqlin, $paramin) = $DB->get_in_or_equal(array_keys($cif));
        $cid = $DB->get_records_sql('SELECT id FROM {course_info_data} WHERE fieldid ' . $sqlin, $paramin);
        $this->assertCount(4, $cid);

        list($sqlin, $paramin) = $DB->get_in_or_equal(array_keys($cid));
        $records = $DB->get_records_sql('SELECT id FROM {course_info_data_param} WHERE dataid ' . $sqlin, $paramin);
        $this->assertCount(4, $records);

        foreach ($cif as $customfield) {

            $prefix = 'course';
            $extra = array('prefix' => $prefix, 'id' => $customfield->id, 'action' => 'deletefield');
            $customfieldtype = get_customfield_type_instace($prefix, $contextsystem, $extra);
            $customfieldtype->delete($customfield->id);

            // Check course_info_data after text and multi-select custom fields are deleted.
            $records = $DB->get_records('course_info_data', array('fieldid' => $customfield->id));
            $this->assertCount(0, $records);

            // Check course_info_field after text and multi-select custom fields are deleted.
            $records = $DB->get_records('course_info_field', array('id' => $customfield->id));
            $this->assertCount(0, $records);
        }

        // Check course_info_data_param after multi-select custom field is deleted.
        list($sqlin, $paramin) = $DB->get_in_or_equal(array_keys($cid));
        $records = $DB->get_records_sql('SELECT id FROM {course_info_data_param} WHERE dataid ' . $sqlin, $paramin);
        $this->assertCount(0, $records);
    }
}
