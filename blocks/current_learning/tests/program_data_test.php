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

class block_current_learning_program_data_testcase extends block_current_learning_testcase_base {

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

    public function test_program_assignment() {

        // Assign user to a program.
        $this->program_generator->assign_program($this->program1->id, array($this->user1->id));

        // The program should not appear in the learning data, there is no content.
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertNotTrue($this->program_in_learning_data($this->program1, $learning_data));

        // Now lets add some content to the program.
        $progcontent = new prog_content($this->program1->id);
        $progcontent->add_set(CONTENTTYPE_MULTICOURSE);

        $coursesets = $progcontent->get_course_sets();

        // Add course.
        $coursedata = new stdClass();
        $coursedata->{$coursesets[0]->get_set_prefix() . 'courseid'} = $this->course1->id;
        $progcontent->add_course(1, $coursedata);

        $progcontent->save_content();

        // The program should appear in the learning data.
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertTrue($this->program_in_learning_data($this->program1, $learning_data));
    }

    public function test_programs_disabled() {
        global $CFG;

        // Add some content to the program.
        $progcontent = new prog_content($this->program1->id);
        $progcontent->add_set(CONTENTTYPE_MULTICOURSE);

        $coursesets = $progcontent->get_course_sets();

        // Add course.
        $coursedata = new stdClass();
        $coursedata->{$coursesets[0]->get_set_prefix() . 'courseid'} = $this->course1->id;
        $progcontent->add_course(1, $coursedata);

        $progcontent->save_content();

        // Assign user to a program.
        $this->program_generator->assign_program($this->program1->id, array($this->user1->id));

        // The program should appear in the learning data.
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertTrue($this->program_in_learning_data($this->program1, $learning_data));

        // Now disable programs in advanced features.
        $CFG->enableprograms = 3;

        // The program should not appear in the learning data.
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertNotTrue($this->program_in_learning_data($this->program1, $learning_data));

    }

    public function test_course_in_program() {

        // Add content to program 1.
        $progcontent = new prog_content($this->program1->id);
        $progcontent->add_set(CONTENTTYPE_MULTICOURSE);

        $coursesets = $progcontent->get_course_sets();

        // Set completion type.
        $coursesets[0]->completiontype = COMPLETIONTYPE_ALL;

        // Set certifpath.
        $coursesets[0]->certifpath = CERTIFPATH_STD;

        // Add courses.
        $coursedata = new stdClass();
        $coursedata->{$coursesets[0]->get_set_prefix() . 'courseid'} = $this->course1->id;
        $progcontent->add_course(1, $coursedata);

        $coursedata->{$coursesets[0]->get_set_prefix() . 'courseid'} = $this->course2->id;
        $progcontent->add_course(1, $coursedata);

        $progcontent->save_content();

        // Assign user to the program.
        $this->program_generator->assign_program($this->program1->id, array($this->user1->id));

        // Get the learning data.
        $learning_data = $this->get_learning_data($this->user1->id);

        // The program should appear in the learning data.
        $this->assertTrue($this->program_in_learning_data($this->program1, $learning_data));

        // Course 1 and 2 should appear in the learning data.
        $this->assertTrue($this->course_program_in_learning_data($this->program1, $this->course1, $learning_data));
        $this->assertTrue($this->course_program_in_learning_data($this->program1, $this->course2, $learning_data));

        // Lets complete course 1.
        $this->generator->enrol_user($this->user1->id, $this->course1->id, null, 'manual');
        $this->completion_generator->complete_course($this->course1, $this->user1);

        // Only course 2 should appear in the learning data.
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertNotTrue($this->course_program_in_learning_data($this->program1, $this->course1, $learning_data));
        $this->assertTrue($this->course_program_in_learning_data($this->program1, $this->course2, $learning_data));

        // Lets complete course 2.
        $this->generator->enrol_user($this->user1->id, $this->course2->id, null, 'manual');
        $this->completion_generator->complete_course($this->course2, $this->user1);

        // The program should not appear in the learning data.
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertNotTrue($this->program_in_learning_data($this->program1, $learning_data));
    }

    public function test_course_direct_enrol_and_in_program() {

        // If a user in directly enrolled into a course and the course is also within a program, the course should not show
        // as a standalone course, unless the courseset is complete or unavailable.

        // Enrol user to course1, course2, course3 and course4.
        $this->generator->enrol_user($this->user1->id, $this->course1->id);
        $this->generator->enrol_user($this->user1->id, $this->course2->id);
        $this->generator->enrol_user($this->user1->id, $this->course3->id);
        $this->generator->enrol_user($this->user1->id, $this->course4->id);
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertTrue($this->course_in_learning_data($this->course1->id, $learning_data));
        $this->assertTrue($this->course_in_learning_data($this->course2->id, $learning_data));
        $this->assertTrue($this->course_in_learning_data($this->course3->id, $learning_data));
        $this->assertTrue($this->course_in_learning_data($this->course4->id, $learning_data));

        // Create a program and set the program content as follows:
        // CS1 (must complete one of Course A or B) THEN CS2 (must complete all of Course C) THEN CS3 (must complete course D)
        $progcontent = new prog_content($this->program1->id);
        $progcontent->add_set(CONTENTTYPE_MULTICOURSE);
        $progcontent->add_set(CONTENTTYPE_MULTICOURSE);
        $progcontent->add_set(CONTENTTYPE_MULTICOURSE);

        $coursesets = $progcontent->get_course_sets();

        // Set completion type.
        $coursesets[0]->completiontype = COMPLETIONTYPE_ANY;
        $coursesets[1]->completiontype = COMPLETIONTYPE_ALL;
        $coursesets[2]->completiontype = COMPLETIONTYPE_ALL;

        // Set certifpath.
        $coursesets[0]->certifpath = CERTIFPATH_STD;
        $coursesets[1]->certifpath = CERTIFPATH_STD;
        $coursesets[2]->certifpath = CERTIFPATH_STD;

        $coursedata = new stdClass();

        // Set 1.
        $coursedata->{$coursesets[0]->get_set_prefix() . 'courseid'} = $this->course1->id;
        $progcontent->add_course(1, $coursedata);

        $coursedata->{$coursesets[0]->get_set_prefix() . 'courseid'} = $this->course2->id;
        $progcontent->add_course(1, $coursedata);

        // Set the operator for Set 1 to be THEN.
        $coursesets[0]->nextsetoperator = NEXTSETOPERATOR_THEN;

        // Set 2.
        $coursedata->{$coursesets[1]->get_set_prefix() . 'courseid'} = $this->course3->id;
        $progcontent->add_course(2, $coursedata);

        // Set the operator for Set 2 to be THEN.
        $coursesets[1]->nextsetoperator = NEXTSETOPERATOR_THEN;

        // Set 3.
        $coursedata->{$coursesets[2]->get_set_prefix() . 'courseid'} = $this->course4->id;
        $progcontent->add_course(3, $coursedata);

        $progcontent->save_content();

        // Assign user to the program.
        $this->program_generator->assign_program($this->program1->id, array($this->user1->id));

        // Get the learning data.
        $learning_data = $this->get_learning_data($this->user1->id);

        // Only course1 and course2 should appear in the program.
        $this->assertTrue($this->course_program_in_learning_data($this->program1, $this->course1, $learning_data));
        $this->assertTrue($this->course_program_in_learning_data($this->program1, $this->course2, $learning_data));
        $this->assertNotTrue($this->course_program_in_learning_data($this->program1, $this->course3, $learning_data));
        $this->assertNotTrue($this->course_program_in_learning_data($this->program1, $this->course4, $learning_data));

        // Only course3 and course4 should appear as standalone courses.
        $this->assertNotTrue($this->course_in_learning_data($this->course1->id, $learning_data));
        $this->assertNotTrue($this->course_in_learning_data($this->course2->id, $learning_data));
        $this->assertTrue($this->course_in_learning_data($this->course3->id, $learning_data));
        $this->assertTrue($this->course_in_learning_data($this->course4->id, $learning_data));

        // Now lets complete course2.
        $this->completion_generator->complete_course($this->course2, $this->user1);

        // Get the learning data.
        $learning_data = $this->get_learning_data($this->user1->id);

        // Only course3 should appear in the program.
        $this->assertNotTrue($this->course_program_in_learning_data($this->program1, $this->course1, $learning_data));
        $this->assertNotTrue($this->course_program_in_learning_data($this->program1, $this->course2, $learning_data));
        $this->assertTrue($this->course_program_in_learning_data($this->program1, $this->course3, $learning_data));
        $this->assertNotTrue($this->course_program_in_learning_data($this->program1, $this->course4, $learning_data));

        // Only course1 and course4 should appear as standalone courses.
        $this->assertTrue($this->course_in_learning_data($this->course1->id, $learning_data));
        $this->assertNotTrue($this->course_in_learning_data($this->course2->id, $learning_data));
        $this->assertNotTrue($this->course_in_learning_data($this->course3->id, $learning_data));
        $this->assertTrue($this->course_in_learning_data($this->course4->id, $learning_data));
    }

    public function test_courseset_and_operator() {

        // Add content to program 1.
        $progcontent = new prog_content($this->program1->id);
        $progcontent->add_set(CONTENTTYPE_MULTICOURSE);
        $progcontent->add_set(CONTENTTYPE_MULTICOURSE);

        $coursesets = $progcontent->get_course_sets();

        // Set completion type.
        $coursesets[0]->completiontype = COMPLETIONTYPE_ALL;
        $coursesets[1]->completiontype = COMPLETIONTYPE_ALL;

        // Set certifpath.
        $coursesets[0]->certifpath = CERTIFPATH_STD;
        $coursesets[1]->certifpath = CERTIFPATH_STD;

        // Enrol user 1 to courses 1,2,3,4
        $this->generator->enrol_user($this->user1->id, $this->course1->id, null, 'manual');
        $this->generator->enrol_user($this->user1->id, $this->course2->id, null, 'manual');
        $this->generator->enrol_user($this->user1->id, $this->course3->id, null, 'manual');
        $this->generator->enrol_user($this->user1->id, $this->course4->id, null, 'manual');

        $coursedata = new stdClass();
        $coursedata->{$coursesets[0]->get_set_prefix() . 'courseid'} = $this->course1->id;
        $progcontent->add_course(1, $coursedata);

        $coursedata->{$coursesets[0]->get_set_prefix() . 'courseid'} = $this->course2->id;
        $progcontent->add_course(1, $coursedata);

        // Set the operator for Set 1 to be AND.
        $coursesets[0]->nextsetoperator = NEXTSETOPERATOR_AND;

        $coursedata->{$coursesets[1]->get_set_prefix() . 'courseid'} = $this->course3->id;
        $progcontent->add_course(2, $coursedata);

        $coursedata->{$coursesets[1]->get_set_prefix() . 'courseid'} = $this->course4->id;
        $progcontent->add_course(2, $coursedata);

        $progcontent->save_content();

        // Assign user to the program.
        $this->program_generator->assign_program($this->program1->id, array($this->user1->id));

        // Get the learning data.
        $learning_data = $this->get_learning_data($this->user1->id);

        // The program should appear in the learning data.
        $this->assertTrue($this->program_in_learning_data($this->program1, $learning_data));

        // All 4 courses should be in the learning data.
        $this->assertTrue($this->course_program_in_learning_data($this->program1, $this->course1, $learning_data));
        $this->assertTrue($this->course_program_in_learning_data($this->program1, $this->course2, $learning_data));
        $this->assertTrue($this->course_program_in_learning_data($this->program1, $this->course3, $learning_data));
        $this->assertTrue($this->course_program_in_learning_data($this->program1, $this->course4, $learning_data));

        // Lets complete course 1 and 2.
        $this->completion_generator->complete_course($this->course1, $this->user1);
        $this->completion_generator->complete_course($this->course2, $this->user1);

        // Get the learning data.
        $learning_data = $this->get_learning_data($this->user1->id);

        // Courses 1 & 2 should not be in the learning data, and courses 3 & 4 should be.
        $this->assertNotTrue($this->course_program_in_learning_data($this->program1, $this->course1, $learning_data));
        $this->assertNotTrue($this->course_program_in_learning_data($this->program1, $this->course2, $learning_data));
        $this->assertTrue($this->course_program_in_learning_data($this->program1, $this->course3, $learning_data));
        $this->assertTrue($this->course_program_in_learning_data($this->program1, $this->course4, $learning_data));

        // Courseset 1 should not be in learning data, but courseset 2 should be.
        $this->assertNotTrue($this->courseset_program_in_learning_data($this->program1, 'Course set 1', $learning_data));
        $this->assertTrue($this->courseset_program_in_learning_data($this->program1, 'Course set 2', $learning_data));

        // Lets complete course 3 and 4.
        $this->completion_generator->complete_course($this->course3, $this->user1);
        $this->completion_generator->complete_course($this->course4, $this->user1);

        // Get the learning data.
        $learning_data = $this->get_learning_data($this->user1->id);

        // The program should now be complete, and should not appear in the learning data.
        $this->assertNotTrue($this->program_in_learning_data($this->program1, $learning_data));
    }
}
