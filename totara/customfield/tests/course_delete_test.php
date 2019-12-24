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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_customfield
 */

defined('MOODLE_INTERNAL') || die();

class totara_customfield_course_delete_testcase extends advanced_testcase {

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
        $multids = $cfgenerator->create_multiselect('course', array('multi1'=>array('opt1', 'opt2')));

        // Create course 1.
        $this->course1 = $this->getDataGenerator()->create_course(array('fullname'=> 'Course 1'));
        // Add customfields data to course 1.
        $cfgenerator->set_text($this->course1, $textids['text1'], 'value1', 'course', 'course');
        $cfgenerator->set_multiselect($this->course1, $multids['multi1'], array('opt1', 'opt2'), 'course', 'course');

        // Create course 2.
        $this->course2 = $this->getDataGenerator()->create_course(array('fullname'=> 'Course 2'));
        // Add customfields data to course 2.
        $cfgenerator->set_text($this->course2, $textids['text1'], 'value1', 'course', 'course');
        $cfgenerator->set_multiselect($this->course2, $multids['multi1'], array('opt1', 'opt2'), 'course', 'course');
    }

    /**
     * Test that customfield data removed with the course
     */
    public function test_customfield_deleted_on_event() {
        global $DB;
        $this->resetAfterTest();

        // Assert that records exist.
        $before = $DB->get_records('course_info_data', array('courseid' => $this->course1->id));
        $this->assertCount(2, $before);

        // Get data_param before deletion.
        list($sqlin, $paramin) = $DB->get_in_or_equal(array_keys($before));
        $parambefore = $DB->get_records_sql('SELECT id FROM {course_info_data_param} WHERE dataid ' . $sqlin, $paramin);
        $this->assertCount(2, $parambefore);

        // Delete course 1.
        ob_start();
        delete_course($this->course1);
        ob_end_clean();

        // Check that data of customfields for course 1 are deleted.
        $afterc1 = $DB->get_records('course_info_data', array('courseid' => $this->course1->id));
        $this->assertCount(0, $afterc1);

        // Check that data of customfields for course 2 are still exist.
        $afterc2 = $DB->get_records('course_info_data', array('courseid' => $this->course2->id));
        $this->assertCount(2, $afterc2);

        // Check that data_param of customfield for course 1 are deleted.
        $paramsafter = $DB->get_records_sql('SELECT id FROM {course_info_data_param} WHERE dataid ' . $sqlin, $paramin);
        $this->assertEmpty($paramsafter);

        // Check that data_param of customfield for course 2 still exist.
        $course2data =  $DB->get_records('course_info_data', array('courseid' => $this->course2->id));
        list($sql2in, $param2in) = $DB->get_in_or_equal(array_keys($course2data));
        $course2dataparam = $DB->get_records_sql('SELECT id FROM {course_info_data_param} WHERE dataid ' . $sql2in, $param2in);
        $this->assertCount(2, $course2dataparam);
    }
}