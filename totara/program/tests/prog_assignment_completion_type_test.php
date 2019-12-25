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
 * @author Nathan Lewis <nathan.lewis@totaralms.com>
 * @package totara_program
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot . '/totara/program/lib.php');
require_once($CFG->dirroot . '/totara/program/program.class.php');

/**
 * Test prog_assignment_completion_type subclasses.
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit totara_program_prog_assignment_completion_type_testcase totara/program/tests/prog_assignment_completion_type_test.php
 *
 */
class totara_program_prog_assignment_completion_type_testcase extends reportcache_advanced_testcase {

    private $users, $dates, $userfields;
    private $programgenerator, $programs, $positiongenerator, $positions, $courses;
    private $beforesetuptime, $aftersetuptime;

    protected function tearDown() {
        $this->users = null;
        $this->dates = null;
        $this->userfields = null;
        $this->programgenerator = null;
        $this->programs = null;
        $this->positiongenerator = null;
        $this->positions = null;
        $this->courses = null;
        $this->beforesetuptime = null;
        $this->aftersetuptime = null;

        parent::tearDown();
    }

    /**
     * Setup.
     *
     * Create all data for all test cases, then test individually, to ensure there is no crossover occurring.
     */
    public function test_prog_assignment_completion_type_subclasses() {
        global $DB;

        parent::setup();

        $this->resetAfterTest(true);

        $this->beforesetuptime = time();
        $this->waitForSecond();

        $this->programgenerator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $this->programs[0] = $this->programgenerator->create_program();
        $this->programs[1] = $this->programgenerator->create_program();
        $this->programs[2] = $this->programgenerator->create_program();
        $this->programs[3] = $this->programgenerator->create_program();
        $this->programs[4] = $this->programgenerator->create_program();
        $this->programs[5] = $this->programgenerator->create_program();

        $this->courses[0] = $this->getDataGenerator()->create_course();
        $this->courses[1] = $this->getDataGenerator()->create_course();
        $this->courses[2] = $this->getDataGenerator()->create_course();
        $this->courses[3] = $this->getDataGenerator()->create_course();
        $this->courses[4] = $this->getDataGenerator()->create_course();
        $this->courses[5] = $this->getDataGenerator()->create_course();

        // Add the courses to the programs. Funky numbers to try to prevent tests passing due to luck.
        $this->getDataGenerator()->add_courseset_program($this->programs[0]->id, array($this->courses[0]->id));
        $this->getDataGenerator()->add_courseset_program($this->programs[1]->id, array($this->courses[1]->id));
        $this->getDataGenerator()->add_courseset_program($this->programs[2]->id, array($this->courses[2]->id));
        $this->getDataGenerator()->add_courseset_program($this->programs[3]->id, array($this->courses[5]->id)); // Note numbers!
        $this->getDataGenerator()->add_courseset_program($this->programs[4]->id, array($this->courses[4]->id));
        $this->getDataGenerator()->add_courseset_program($this->programs[5]->id, array($this->courses[3]->id)); // Note numbers!

        $this->positiongenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $posfw = $this->positiongenerator->create_framework('position');
        $this->positions[0] = $this->positiongenerator->create_hierarchy($posfw->id, 'position');
        $this->positions[1] = $this->positiongenerator->create_hierarchy($posfw->id, 'position');
        $this->positions[2] = $this->positiongenerator->create_hierarchy($posfw->id, 'position');
        $this->positions[3] = $this->positiongenerator->create_hierarchy($posfw->id, 'position');
        $this->positions[4] = $this->positiongenerator->create_hierarchy($posfw->id, 'position');
        $this->positions[5] = $this->positiongenerator->create_hierarchy($posfw->id, 'position');

        $this->dates = array();

        $this->users = array();
        $userids = array();
        for ($i = 0; $i < 25; $i++) {
            $this->users[$i] = $this->getDataGenerator()->create_user();
            $userids[] = $this->users[$i]->id;

            // Assign all test users to all courses.
            foreach ($this->courses as $course) {
                $this->getDataGenerator()->enrol_user($this->users[$i]->id, $course->id);
            }
        }

        // Assign all test users to all programs.
        foreach ($this->programs as $program) {
            $this->programgenerator->assign_program($program->id, $userids);
        }

        // Add another custom field, this time of normal text type.
        $this->fieldids[0] = $DB->insert_record('user_info_field', (object)array('shortname' => 'text1', 'name' => 'text1',
            'categoryid' => 1, 'datatype' => 'text'));
        $this->fieldids[1] = $DB->insert_record('user_info_field', (object)array('shortname' => 'date1', 'name' => 'date1',
            'categoryid' => 1, 'datatype' => 'date'));

        // Data for prog_assigment_completion_first_login.
        $this->dates[0] = strtotime("-10 day");
        $this->dates[1] = strtotime("-3 day");
        $DB->set_field('user', 'firstaccess', $this->dates[0], array('id' => $this->users[0]->id));
        $DB->set_field('user', 'lastaccess',  $this->dates[1], array('id' => $this->users[1]->id));
        $DB->set_field('user', 'firstaccess', $this->dates[0], array('id' => $this->users[2]->id));
        $DB->set_field('user', 'lastaccess',  $this->dates[1], array('id' => $this->users[2]->id));

        // Data for prog_assigment_completion_job_assignment_position_date_assigned.
        \totara_job\job_assignment::get_first($this->users[3]->id)->update(array('positionid' => $this->positions[2]->id));
        \totara_job\job_assignment::get_first($this->users[4]->id)->update(array('positionid' => $this->positions[2]->id));
        \totara_job\job_assignment::get_first($this->users[5]->id)->update(array('positionid' => $this->positions[3]->id));

        // Data for prog_assigment_completion_job_assignment_start_date.
        $this->dates[6] = strtotime("-10 day");
        $this->dates[7] = strtotime("10 day");
        $this->dates[8] = strtotime("-5 day");
        \totara_job\job_assignment::get_first($this->users[6]->id)->update(
            array('positionid' => $this->positions[1]->id, 'startdate' => $this->dates[6]));
        \totara_job\job_assignment::get_first($this->users[7]->id)->update(
            array('positionid' => $this->positions[1]->id, 'startdate' => $this->dates[7]));
        \totara_job\job_assignment::get_first($this->users[8]->id)->update(
            array('positionid' => $this->positions[2]->id, 'startdate' => $this->dates[8]));

        // Data for prog_assigment_completion_program_completion.
        $this->dates[9]  = strtotime("-10 day");
        $this->dates[10] = strtotime("10 day");
        $this->dates[11] = strtotime("-5 day");
        $completion9 = new completion_completion(array('userid' => $this->users[9]->id, 'course' => $this->courses[2]->id));
        $completion9->mark_complete($this->dates[9]);
        $completion10 = new completion_completion(array('userid' => $this->users[10]->id, 'course' => $this->courses[5]->id));
        $completion10->mark_complete($this->dates[10]);
        $completion11 = new completion_completion(array('userid' => $this->users[11]->id, 'course' => $this->courses[2]->id));
        $completion11->mark_complete($this->dates[11]);

        // Data for prog_assigment_completion_course_completion.
        $this->dates[12]  = strtotime("-10 day");
        $this->dates[13] = strtotime("10 day");
        $this->dates[14] = strtotime("-5 day");
        $completion9 = new completion_completion(array('userid' => $this->users[12]->id, 'course' => $this->courses[5]->id));
        $completion9->mark_complete($this->dates[12]);
        $completion10 = new completion_completion(array('userid' => $this->users[13]->id, 'course' => $this->courses[2]->id));
        $completion10->mark_complete($this->dates[13]);
        $completion11 = new completion_completion(array('userid' => $this->users[14]->id, 'course' => $this->courses[5]->id));
        $completion11->mark_complete($this->dates[14]);

        // Data for prog_assigment_completion_profile_field_date.
        $this->dates[15] = strtotime("01/01/2001");
        $this->dates[16] = strtotime("-10 day");
        $this->dates[17] = strtotime("-5 day");
        $DB->insert_record('user_info_data', (object)array('userid' => $this->users[15]->id, 'fieldid' => $this->fieldids[0],
            'data' => "01/01/2001", 'dataformat' => 0));
        $DB->insert_record('user_info_data', (object)array('userid' => $this->users[16]->id, 'fieldid' => $this->fieldids[1],
            'data' => $this->dates[16], 'dataformat' => 0));
        $DB->insert_record('user_info_data', (object)array('userid' => $this->users[17]->id, 'fieldid' => $this->fieldids[1],
            'data' => $this->dates[17], 'dataformat' => 0));

        // Data for prog_assigment_completion_enrollment_date.
        $this->users[1000] = $this->getDataGenerator()->create_user();

        $this->waitForSecond();
        $this->aftersetuptime = time();

        $this->prog_assigment_completion_first_login();
        $this->prog_assigment_completion_position_assigned_date();
        $this->prog_assigment_completion_position_start_date();
        $this->prog_assigment_completion_program_completion();
        $this->prog_assigment_completion_course_completion();
        $this->prog_assigment_completion_profile_field_date();
        $this->prog_assigment_completion_enrollment_date();
    }

    public function prog_assigment_completion_first_login() {
        global $DB;

        $completionobject = new prog_assigment_completion_first_login();

        $assignment = $DB->get_record('prog_assignment', array('programid' => $this->programs[0]->id,
            'assignmenttype' => ASSIGNTYPE_INDIVIDUAL,
            'assignmenttypeid' => $this->users[0]->id));
        $assignment->completioninstance = 0;
        $timestamp = $completionobject->get_timestamp($this->users[0]->id, $assignment);
        $this->assertEquals($this->dates[0], $timestamp);

        $assignment = $DB->get_record('prog_assignment', array('programid' => $this->programs[0]->id,
            'assignmenttype' => ASSIGNTYPE_INDIVIDUAL,
            'assignmenttypeid' => $this->users[1]->id));
        $assignment->completioninstance = 0;
        $timestamp = $completionobject->get_timestamp($this->users[1]->id, $assignment);
        $this->assertEquals($this->dates[1], $timestamp);

        $assignment = $DB->get_record('prog_assignment', array('programid' => $this->programs[0]->id,
            'assignmenttype' => ASSIGNTYPE_INDIVIDUAL,
            'assignmenttypeid' => $this->users[2]->id));
        $assignment->completioninstance = 0;
        $timestamp = $completionobject->get_timestamp($this->users[2]->id, $assignment);
        $this->assertEquals($this->dates[0], $timestamp);
    }

    public function prog_assigment_completion_position_assigned_date() {
        global $DB;

        $completionobject = new prog_assigment_completion_position_assigned_date();

        $assignment = $DB->get_record('prog_assignment', array(
            'programid' => $this->programs[0]->id,
            'assignmenttype' => ASSIGNTYPE_INDIVIDUAL,
            'assignmenttypeid' => $this->users[3]->id
        ));
        $assignment->completioninstance = $this->positions[2]->id;
        $timestamp = $completionobject->get_timestamp($this->users[3]->id, $assignment);
        $this->assertGreaterThan($this->beforesetuptime, $timestamp);
        $this->assertLessThan($this->aftersetuptime, $timestamp);

        $assignment = $DB->get_record('prog_assignment', array('programid' => $this->programs[0]->id,
            'assignmenttype' => ASSIGNTYPE_INDIVIDUAL,
            'assignmenttypeid' => $this->users[4]->id));
        $assignment->completioninstance = $this->positions[2]->id;
        $timestamp = $completionobject->get_timestamp($this->users[4]->id, $assignment);
        $this->assertGreaterThan($this->beforesetuptime, $timestamp);
        $this->assertLessThan($this->aftersetuptime, $timestamp);

        $assignment = $DB->get_record('prog_assignment', array('programid' => $this->programs[0]->id,
            'assignmenttype' => ASSIGNTYPE_INDIVIDUAL,
            'assignmenttypeid' => $this->users[5]->id));
        $assignment->completioninstance = $this->positions[3]->id;
        $timestamp = $completionobject->get_timestamp($this->users[5]->id, $assignment);
        $this->assertGreaterThan($this->beforesetuptime, $timestamp);
        $this->assertLessThan($this->aftersetuptime, $timestamp);
    }

    public function prog_assigment_completion_position_start_date() {
        global $DB;

        $completionobject = new prog_assigment_completion_position_start_date();

        $assignment = $DB->get_record('prog_assignment', array('programid' => $this->programs[0]->id,
            'assignmenttype' => ASSIGNTYPE_INDIVIDUAL,
            'assignmenttypeid' => $this->users[6]->id));
        $assignment->completioninstance = $this->positions[1]->id;
        $timestamp = $completionobject->get_timestamp($this->users[6]->id, $assignment);
        $this->assertEquals($this->dates[6], $timestamp);

        $assignment = $DB->get_record('prog_assignment', array('programid' => $this->programs[0]->id,
            'assignmenttype' => ASSIGNTYPE_INDIVIDUAL,
            'assignmenttypeid' => $this->users[7]->id));
        $assignment->completioninstance = $this->positions[1]->id;
        $timestamp = $completionobject->get_timestamp($this->users[7]->id, $assignment);
        $this->assertEquals($this->dates[7], $timestamp);

        $assignment = $DB->get_record('prog_assignment', array('programid' => $this->programs[0]->id,
            'assignmenttype' => ASSIGNTYPE_INDIVIDUAL,
            'assignmenttypeid' => $this->users[8]->id));
        $assignment->completioninstance = $this->positions[2]->id;
        $timestamp = $completionobject->get_timestamp($this->users[8]->id, $assignment);
        $this->assertEquals($this->dates[8], $timestamp);
    }

    public function prog_assigment_completion_program_completion() {
        global $DB;

        $completionobject = new prog_assigment_completion_program_completion();

        $assignment = $DB->get_record('prog_assignment', array('programid' => $this->programs[0]->id,
            'assignmenttype' => ASSIGNTYPE_INDIVIDUAL,
            'assignmenttypeid' => $this->users[9]->id));
        $assignment->completioninstance = $this->programs[2]->id;
        $timestamp = $completionobject->get_timestamp($this->users[9]->id, $assignment);
        $this->assertEquals($this->dates[9], $timestamp);

        $assignment = $DB->get_record('prog_assignment', array('programid' => $this->programs[0]->id,
            'assignmenttype' => ASSIGNTYPE_INDIVIDUAL,
            'assignmenttypeid' => $this->users[10]->id));
        $assignment->completioninstance = $this->programs[3]->id;
        $timestamp = $completionobject->get_timestamp($this->users[10]->id, $assignment);
        $this->assertEquals($this->dates[10], $timestamp);

        $assignment = $DB->get_record('prog_assignment', array('programid' => $this->programs[0]->id,
            'assignmenttype' => ASSIGNTYPE_INDIVIDUAL,
            'assignmenttypeid' => $this->users[11]->id));
        $assignment->completioninstance = $this->programs[2]->id;
        $timestamp = $completionobject->get_timestamp($this->users[11]->id, $assignment);
        $this->assertEquals($this->dates[11], $timestamp);
    }

    public function prog_assigment_completion_course_completion() {
        global $DB;

        $completionobject = new prog_assigment_completion_course_completion();

        $assignment = $DB->get_record('prog_assignment', array('programid' => $this->programs[0]->id,
            'assignmenttype' => ASSIGNTYPE_INDIVIDUAL,
            'assignmenttypeid' => $this->users[12]->id));
        $assignment->completioninstance = $this->courses[5]->id;
        $timestamp = $completionobject->get_timestamp($this->users[12]->id, $assignment);
        $this->assertEquals($this->dates[12], $timestamp);

        $assignment = $DB->get_record('prog_assignment', array('programid' => $this->programs[0]->id,
            'assignmenttype' => ASSIGNTYPE_INDIVIDUAL,
            'assignmenttypeid' => $this->users[13]->id));
        $assignment->completioninstance = $this->courses[2]->id;
        $timestamp = $completionobject->get_timestamp($this->users[13]->id, $assignment);
        $this->assertEquals($this->dates[13], $timestamp);

        $assignment = $DB->get_record('prog_assignment', array('programid' => $this->programs[0]->id,
            'assignmenttype' => ASSIGNTYPE_INDIVIDUAL,
            'assignmenttypeid' => $this->users[14]->id));
        $assignment->completioninstance = $this->courses[5]->id;
        $timestamp = $completionobject->get_timestamp($this->users[14]->id, $assignment);
        $this->assertEquals($this->dates[14], $timestamp);
    }

    public function prog_assigment_completion_profile_field_date() {
        global $DB;

        $completionobject = new prog_assigment_completion_profile_field_date();

        $assignment = $DB->get_record('prog_assignment', array('programid' => $this->programs[0]->id,
            'assignmenttype' => ASSIGNTYPE_INDIVIDUAL,
            'assignmenttypeid' => $this->users[15]->id));
        $assignment->completioninstance = $this->fieldids[0];
        $timestamp = $completionobject->get_timestamp($this->users[15]->id, $assignment);
        $this->assertEquals($this->dates[15], $timestamp);

        $assignment = $DB->get_record('prog_assignment', array('programid' => $this->programs[0]->id,
            'assignmenttype' => ASSIGNTYPE_INDIVIDUAL,
            'assignmenttypeid' => $this->users[16]->id));
        $assignment->completioninstance = $this->fieldids[1];
        $timestamp = $completionobject->get_timestamp($this->users[16]->id, $assignment);
        $this->assertEquals($this->dates[16], $timestamp);

        $assignment = $DB->get_record('prog_assignment', array('programid' => $this->programs[0]->id,
            'assignmenttype' => ASSIGNTYPE_INDIVIDUAL,
            'assignmenttypeid' => $this->users[17]->id));
        $assignment->completioninstance = $this->fieldids[1];
        $timestamp = $completionobject->get_timestamp($this->users[17]->id, $assignment);
        $this->assertEquals($this->dates[17], $timestamp);
    }

    public function prog_assigment_completion_enrollment_date() {
        global $DB;

        $completionobject = new prog_assigment_completion_enrollment_date();

        $assignment = $DB->get_record('prog_assignment', array('programid' => $this->programs[0]->id,
            'assignmenttype' => ASSIGNTYPE_INDIVIDUAL,
            'assignmenttypeid' => $this->users[18]->id));
        $assignment->completioninstance = $assignment->id;
        $timestamp = $completionobject->get_timestamp($this->users[18]->id, $assignment);
        $this->assertGreaterThan($this->beforesetuptime, $timestamp);
        $this->assertLessThan($this->aftersetuptime, $timestamp);

        $assignment = $DB->get_record('prog_assignment', array('programid' => $this->programs[0]->id,
            'assignmenttype' => ASSIGNTYPE_INDIVIDUAL,
            'assignmenttypeid' => $this->users[19]->id));
        $assignment->completioninstance = $assignment->id;
        $timestamp = $completionobject->get_timestamp($this->users[19]->id, $assignment);
        $this->assertGreaterThan($this->beforesetuptime, $timestamp);
        $this->assertLessThan($this->aftersetuptime, $timestamp);

        $assignment = $DB->get_record('prog_assignment', array('programid' => $this->programs[0]->id,
            'assignmenttype' => ASSIGNTYPE_INDIVIDUAL,
            'assignmenttypeid' => $this->users[20]->id));
        $assignment->completioninstance = $assignment->id;
        $timestamp = $completionobject->get_timestamp($this->users[20]->id, $assignment);
        $this->assertGreaterThan($this->beforesetuptime, $timestamp);
        $this->assertLessThan($this->aftersetuptime, $timestamp);

        // User who is not yet assigned.
        $assignment = new stdClass();
        $assignment->id = 0;
        $this->waitForSecond();
        $before = time();
        $timestamp = $completionobject->get_timestamp($this->users[1000]->id, $assignment);
        $after = time();
        $this->assertGreaterThanOrEqual($before, $timestamp);
        $this->assertLessThanOrEqual($after, $timestamp);
    }
}
