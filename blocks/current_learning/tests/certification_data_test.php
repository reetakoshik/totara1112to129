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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package block_current_learning
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/blocks/current_learning/tests/fixtures/block_current_learning_testcase_base.php');
require_once($CFG->dirroot . '/totara/program/lib.php');

class block_current_learning_certification_data_testcase extends block_current_learning_testcase_base {

    private $generator;
    private $program_generator;
    private $completion_generator;

    private $user1, $user2, $user3, $user4;
    private $course1, $course2, $course3, $course4;
    private $program1, $program2;

    protected function tearDown() {
        $this->generator = null;
        $this->program_generator = null;
        $this->completion_generator = null;
        $this->user1 = $this->user2 = $this->user3 = $this->user4 = null;
        $this->course1 = $this->course2 = $this->course3 = $this->course4 = null;
        $this->program1 = $this->program2 = null;

        parent::tearDown();
    }

    protected function setUp() {
        global $CFG, $DB;
        parent::setUp();

        $this->generator = $this->getDataGenerator();
        $this->program_generator = $this->generator->get_plugin_generator('totara_program');
        $this->completion_generator = $this->getDataGenerator()->get_plugin_generator('core_completion');

        $this->resetAfterTest();
        $CFG->enablecompletion = true;

        // Create some users.
        $this->user1 = $this->generator->create_user();
        $this->user2 = $this->generator->create_user();
        $this->user3 = $this->generator->create_user();
        $this->user4 = $this->generator->create_user();

        // Create some courses.
        $this->course1 = $this->generator->create_course();
        $this->course2 = $this->generator->create_course();
        $this->course3 = $this->generator->create_course();
        $this->course4 = $this->generator->create_course();

        // Create some programs.
        $this->program1 = $this->program_generator->create_program(array('fullname' => 'Program 1'));
        $this->program2 = $this->program_generator->create_program(array('fullname' => 'Program 2'));

        // Reload courses to get accurate data.
        // See note in totara/program/tests/program_content_test.php for more info.
        $this->course1 = $DB->get_record('course', array('id' => $this->course1->id));
        $this->course2 = $DB->get_record('course', array('id' => $this->course2->id));
        $this->course3 = $DB->get_record('course', array('id' => $this->course3->id));
        $this->course4 = $DB->get_record('course', array('id' => $this->course4->id));

        // Enable completion for courses.
        $this->completion_generator->enable_completion_tracking($this->course1);
        $this->completion_generator->enable_completion_tracking($this->course2);
        $this->completion_generator->enable_completion_tracking($this->course3);
        $this->completion_generator->enable_completion_tracking($this->course4);
    }

    public function test_certifications_disabled() {
        global $CFG;

        // Enrol user and test the course is in the learning data.
        $this->generator->enrol_user($this->user1->id, $this->course1->id);
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertTrue($this->course_in_learning_data($this->course1->id, $learning_data));

        // Create a certification and assign a user.
        list($actperiod, $winperiod, $recerttype) = $this->program_generator->get_random_certification_setting();
        $data = [
            'fullname' => 'Program 1',
            'certifid' => $this->program_generator->create_certification_settings(0, $actperiod, $winperiod, $recerttype),
        ];
        $program1 = $this->program_generator->create_program($data);
        $this->program_generator->assign_program($program1->id, array($this->user1->id));

        // Add content to the program.
        $progcontent = new prog_content($program1->id);
        $progcontent->add_set(CONTENTTYPE_MULTICOURSE);

        $coursesets = $progcontent->get_course_sets();

        // Set completion type.
        $coursesets[0]->completiontype = COMPLETIONTYPE_ALL;

        // Set certifpath.
        $coursesets[0]->certifpath = CERTIFPATH_STD;

        // Add a course.
        $coursedata = new stdClass();
        $coursedata->{$coursesets[0]->get_set_prefix() . 'courseid'} = $this->course1->id;
        $progcontent->add_course(1, $coursedata);

        $progcontent->save_content();

        certif_create_completion($program1->id, $this->user1->id);

        // The certification and it's course should appear in the learning data.
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertTrue($this->certification_in_learning_data($program1, $learning_data));
        $this->assertTrue($this->course_certification_in_learning_data($program1, $this->course1, $learning_data));

        // Now disable certifications in advanced features.
        $CFG->enablecertifications = 3;

        // The certification should not appear in the learning data.
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertNotTrue($this->certification_in_learning_data($program1, $learning_data));
    }

}