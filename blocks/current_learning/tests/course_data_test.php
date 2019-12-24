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

class block_current_learning_course_data_testcase extends block_current_learning_testcase_base {

    // We want to test that 'stand-alone' courses adhere to the blocks whitelist / blacklist rules.
    // 'Stand-alone' courses are those that are not within a program or certifications.

    /**
     * @var testing_data_generator
     */
    private $generator;

    /**
     * @var totara_program_generator
     */
    private $program_generator;

    private $user1;
    private $user2;
    private $user3;
    private $user4;
    private $course1;
    private $course2;

    protected function tearDown() {
        $this->generator = null;
        $this->program_generator = null;
        $this->user1 = null;
        $this->user2 = null;
        $this->user3 = null;
        $this->user4 = null;
        $this->course1 = null;
        $this->course2 = null;
        parent::tearDown();
    }

    protected function setUp() {
        global $CFG;
        parent::setUp();

        $this->generator = $this->getDataGenerator();
        $this->program_generator = $this->generator->get_plugin_generator('totara_program');

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
    }

    public function test_course_enrollment() {

        // Enrolled user.
        $this->generator->enrol_user($this->user1->id, $this->course1->id);
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertTrue($this->course_in_learning_data($this->course1->id, $learning_data));


        // Enrol user with future enrollment start date.
        $this->generator->enrol_user($this->user2->id, $this->course1->id, null, 'manual', time() + 604800);
        $learning_data = $this->get_learning_data($this->user2->id);
        $this->assertNotTrue($this->course_in_learning_data($this->course1->id, $learning_data));

        // User previously enrolled.
        $this->generator->enrol_user($this->user3->id, $this->course1->id, null, 'manual', time() - 864000, time() - 604800);
        $learning_data = $this->get_learning_data($this->user3->id);
        $this->assertNotTrue($this->course_in_learning_data($this->course1->id, $learning_data));

        // Enrolment suspended
        $this->generator->enrol_user($this->user4->id, $this->course1->id, null, 'manual', 0, 0, ENROL_USER_SUSPENDED);
        $learning_data = $this->get_learning_data($this->user4->id);
        $this->assertNotTrue($this->course_in_learning_data($this->course1->id, $learning_data));

        // Not enrolled.
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertNotTrue($this->course_in_learning_data($this->course2->id, $learning_data));
    }

    public function test_course_gradedrolesonly() {
        global $CFG, $DB;

        // First, check the course is included as it should be.
        $this->generator->enrol_user($this->user1->id, $this->course1->id);
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertTrue($this->course_in_learning_data($this->course1->id, $learning_data));

        // Now Change graded roles.
        $CFG->gradebookroles = '-1';  // -1 should never exists as a actual role id which is what we're after :)

        // The course should not be included.
        $this->generator->enrol_user($this->user1->id, $this->course1->id);
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertNotTrue($this->course_in_learning_data($this->course1->id, $learning_data));

        $CFG->gradebookroles = '5'; // Set graded roles to be only the Learner.

        // Ensure that if a user is enrolled in a non-graded role they do no see the course.
        $this->generator->enrol_user($this->user2->id, $this->course1->id, 3); // Enrol as Editing Trainer.
        $learning_data = $this->get_learning_data($this->user2->id);
        $this->assertNotTrue($this->course_in_learning_data($this->course1->id, $learning_data));

        // Ensure the course shows for a user who is in a graded role.
        $this->generator->enrol_user($this->user3->id, $this->course1->id, 5); // Enrol as Editing Trainer.
        $learning_data = $this->get_learning_data($this->user3->id);
        $this->assertTrue($this->course_in_learning_data($this->course1->id, $learning_data));
    }

    public function test_completed_courses() {

        // If the course completion status is either 'Complete' or 'Complete via RPL' then the course is blacklisted.

        // Enrol user and test the course is in the learning data.
        $this->generator->enrol_user($this->user1->id, $this->course1->id);
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertTrue($this->course_in_learning_data($this->course1->id, $learning_data));

        // Complete the course.
        $completion_generator = $this->generator->get_plugin_generator('core_completion');
        $completion_generator->enable_completion_tracking($this->course1);
        $completion_generator->complete_course($this->course1, $this->user1);

        // Check the course is not in the learning data.
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertNotTrue($this->course_in_learning_data($this->course1->id, $learning_data));
    }

    public function test_course_where_also_added_to_program() {
        // If the course is part of an active course set (not completed or unavailable one), within one of the user's
        // current programs (as determined by the current program criteria below) then the course is blacklisted
        // (because showing it would lead to duplication within the block).

        // Enrol user and test the course is in the learning data.
        $this->generator->enrol_user($this->user1->id, $this->course1->id);
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertTrue($this->course_in_learning_data($this->course1->id, $learning_data));

        // Create a program.
        $program1 = $this->program_generator->create_program(array('fullname' => 'Program 1'));

        // Assign user to a program.
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

        // The program should appear in the learning data.
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertTrue($this->program_in_learning_data($program1, $learning_data));

        // The course should not appears, outside of the program.
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertNotTrue($this->course_in_learning_data($this->course1->id, $learning_data));
    }

    public function test_course_where_also_added_to_certification() {
        // If the course is part of an active course set (not completed or unavailable one), within one of the user's
        // current certifications (as determined by the current program criteria below) then the course is blacklisted
        // (because showing it would lead to duplication within the block).

        // Enrol user and test the course is in the learning data.
        $this->generator->enrol_user($this->user1->id, $this->course1->id);
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertTrue($this->course_in_learning_data($this->course1->id, $learning_data));

        // Create a program.
        $program1 = $this->program_generator->create_program(array('fullname' => 'Program 1'));

        // Assign user to a program.
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

        // Create certification from the program.
        list($actperiod, $winperiod, $recerttype) = $this->program_generator->get_random_certification_setting();
        $this->program_generator->create_certification_settings($program1->id, $actperiod, $winperiod, $recerttype);

        certif_create_completion($program1->id, $this->user1->id);

        // The course should appear in the certification.
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertTrue($this->course_certification_in_learning_data($program1, $this->course1, $learning_data));

        // The course should not appear outside of the certification.
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertNotTrue($this->course_in_learning_data($this->course1->id, $learning_data));
    }

}
