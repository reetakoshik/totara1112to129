<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_program
 */

class totara_program_assignment_individuals_test extends advanced_testcase {

    private $generator = null;
    private $programgenerator = null;


    public function tearDown() {
        $this->generator = null;
        $this->programgenerator = null;

        $this->programs = [];
        $this->users = [];

        parent::tearDown();
    }

    private function create_individual_data() {
        $this->generator = $this->getDataGenerator();
        $this->programgenerator = $this->generator->get_plugin_generator('totara_program');
        $this->programs[1] = $this->programgenerator->create_program();
        $this->users[1] = $this->generator->create_user();
    }

    public function test_create_from_instance_id() {
        global $DB, $CFG;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $this->create_individual_data();

        $assignment = \totara_program\assignment\base::create_from_instance_id($this->programs[1]->id, ASSIGNTYPE_INDIVIDUAL, $this->users[1]->id);
        $assignment->save();

        $this->assertInstanceOf('\totara_program\assignment\individual', $assignment);

        $reflection = new ReflectionClass('\totara_program\assignment\individual');
        $property = $reflection->getProperty('instanceid');
        $property->setAccessible(true);
        $this->assertEquals($this->users[1]->id, $property->getValue($assignment));

        $assignment_record = $DB->get_record('prog_assignment', ['programid' => $this->programs[1]->id, 'assignmenttype' => ASSIGNTYPE_INDIVIDUAL, 'assignmenttypeid' => $this->users[1]->id]);
        $this->assertEquals('-1', $assignment_record->completiontime);
        $this->assertEquals(0, $assignment_record->completionevent);
        $this->assertEquals(0, $assignment_record->completioninstance);

        $completion_record = $DB->get_records('prog_completion', ['programid' => $this->programs[1]->id, 'userid' => $this->users[1]->id, 'coursesetid' => 0]);
        $record = reset($completion_record);
        $this->assertEquals('-1', $record->timedue);
        $this->assertEquals(0, $record->status);
    }


    public function test_create_from_id() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $programgenerator = $generator->get_plugin_generator('totara_program');

        $user1 = $generator->create_user();
        $program1 = $programgenerator->create_program();

        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);

        // There should be only one record
        $this->assertEquals(1, $DB->count_records('prog_assignment'));

        $assignments = $DB->get_records('prog_assignment', ['programid' => $program1->id]);
        $record = reset($assignments);

        $assignment = \totara_program\assignment\individual::create_from_id($record->id);

        $this->assertInstanceOf(\totara_program\assignment\individual::class, $assignment);

        $reflection = new ReflectionClass('\totara_program\assignment\individual');
        $property = $reflection->getProperty('typeid');
        $property->setAccessible(true);
        $this->assertEquals(5, $property->getValue($assignment));

        $property = $reflection->getProperty('instanceid');
        $property->setAccessible(true);
        $this->assertEquals($user1->id, $property->getValue($assignment));

        // Fix once generator is fixed
        $completion_record = $DB->get_records('prog_completion');
    }

    public function test_get_type() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $programgenerator = $generator->get_plugin_generator('totara_program');

        $user1 = $generator->create_user();
        $program1 = $programgenerator->create_program();

        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);

        $assignments = $DB->get_records('prog_assignment', ['programid' => $program1->id]);
        $record = reset($assignments);
        $assignment = \totara_program\assignment\individual::create_from_id($record->id);
        $this->assertEquals(5, $assignment->get_type());
    }

    public function test_get_name() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $programgenerator = $generator->get_plugin_generator('totara_program');

        $user1 = $generator->create_user();
        $program1 = $programgenerator->create_program();

        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);

        $assignments = $DB->get_records('prog_assignment', ['programid' => $program1->id]);
        $record = reset($assignments);
        $assignment = \totara_program\assignment\individual::create_from_id($record->id);

        $userfullname = fullname($user1);
        $this->assertEquals($userfullname, $assignment->get_name());
    }

    public function test_get_programid() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $programgenerator = $generator->get_plugin_generator('totara_program');

        $user1 = $generator->create_user();
        $program1 = $programgenerator->create_program();

        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);

        $assignments = $DB->get_records('prog_assignment', ['programid' => $program1->id]);
        $record = reset($assignments);
        $assignment = \totara_program\assignment\individual::create_from_id($record->id);

        $userfullname = fullname($user1);
        $this->assertEquals($program1->id, $assignment->get_programid());
    }

    public function test_includechildren() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $programgenerator = $generator->get_plugin_generator('totara_program');

        $user1 = $generator->create_user();
        $program1 = $programgenerator->create_program();

        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);

        $assignments = $DB->get_records('prog_assignment', ['programid' => $program1->id]);
        $record = reset($assignments);
        $assignment = \totara_program\assignment\individual::create_from_id($record->id);

        $userfullname = fullname($user1);
        $this->assertEquals($program1->id, $assignment->get_programid());
    }

    /**
     *
     */
    public function test_get_duedate_fixed_date() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $programgenerator = $generator->get_plugin_generator('totara_program');

        $user1 = $generator->create_user();
        $program1 = $programgenerator->create_program();

        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);

        $hour = 13;
        $minute = 30;
        $timedue = new DateTime('2 weeks'); // 2 weeks from now
        $timedue->setTime($hour, $minute); // Set time to 1:30pm

        // Set completion values.assignment->
        $completiontime = $timedue->getTimestamp();
        $completiontimestring = $timedue->format('d/m/Y');
        $completionevent = COMPLETION_EVENT_NONE;
        $completioninstance = 0;
        $includechildren = null;

        $individualtype = 5;

        $data = new stdClass();
        $data->id = $program1->id;
        $data->item = array($individualtype => array($user1->id => 1));
        $data->completiontime = array($individualtype => array($user1->id => $completiontimestring));
        $data->completiontimehour = array($individualtype => array($user1->id => $hour));
        $data->completiontimeminute = array($individualtype => array($user1->id => $minute));
        $data->completionevent = array($individualtype => array($user1->id => $completionevent));
        $data->completioninstance = array($individualtype => array($user1->id => $completioninstance));
        $data->includechildren = array ($individualtype => array($user1->id => $includechildren));

        $assignmenttoprog = prog_assignments::factory($individualtype);
        $assignmenttoprog->update_assignments($data, false);

        // Set time due in completion record
        $program1->set_timedue($user1->id, $completiontime);

        $program = new \program($program1->id);
        $program->update_learner_assignments(true);

        // Get assignment
        $assignments = $DB->get_records('prog_assignment', ['programid' => $program1->id]);
        $record = reset($assignments);
        $assignment = \totara_program\assignment\individual::create_from_id($record->id);

        /*$expecteddate = $timedue->format('j M Y');
        $expectedtime = $timedue->format('H:i');
        $expectedstring = 'Complete by ' . $expecteddate . ' at ' . $expectedtime;
         */
        $result = $assignment->get_duedate();
        // This column for individuals behaves weridly (in existing code as well)
        // For a set due date it returns an empty string however for a relative
        // due date it returns the expected string see 'test_get_duedate_first_login'
        $expected = new \stdClass();
        $expected->string = '';
        $expected->changeable = true;
        $this->assertEquals($expected, $result);
    }

    public function test_get_duedate_first_login() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $this->create_individual_data();

        $this->programgenerator->assign_to_program($this->programs[1]->id, ASSIGNTYPE_INDIVIDUAL, $this->users[1]->id);

        $assignment_record = $DB->get_record('prog_assignment', ['programid' => $this->programs[1]->id, 'assignmenttype' => ASSIGNTYPE_INDIVIDUAL, 'assignmenttypeid' => $this->users[1]->id]);

        $individualtype = 5;
        $completionevent = COMPLETION_EVENT_FIRST_LOGIN;
        // This format is totally crazy but means 14 days;
        $completiontime = '14 ' . TIME_SELECTOR_DAYS;

        $data = new stdClass();
        $data->id = $this->programs[1]->id;
        $data->item = array($individualtype => array($this->users[1]->id => 1));
        $data->completiontime = array($individualtype => array($this->users[1]->id => $completiontime));
        $data->completionevent = array($individualtype => array($this->users[1]->id => $completionevent));

        $assignmenttoprog = prog_assignments::factory($individualtype);
        $assignmenttoprog->update_assignments($data, false);

        $program = new \program($this->programs[1]->id);
        $program->update_learner_assignments(true);

        $assignment = \totara_program\assignment\base::create_from_id($assignment_record->id);
        // We need to trim result since first login doesn't have and instance

        $result = $assignment->get_duedate();
        $result->string = trim($result->string);

        $expected = new \stdClass();
        $expected->string = 'Complete within 2 Week(s) of First login';
        $expected->changeable = true;
        $this->assertEquals($expected, $result);
    }

    public function test_set_duedate_static_date() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $programgenerator = $generator->get_plugin_generator('totara_program');

        $user1 = $generator->create_user();
        $program1 = $programgenerator->create_program();

        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);

        $timedue = new DateTime('2 weeks'); // 2 weeks from now
        $timedue->setTime(13, 30); // Set time to 1:30pm

        // Set completion values.
        $completiontime = $timedue->getTimestamp();

        // Get assignment
        $assignments = $DB->get_records('prog_assignment', ['programid' => $program1->id]);
        $record = reset($assignments);
        $assignment = \totara_program\assignment\individual::create_from_id($record->id);

        //$assignment->save();

        $progassign_record = $DB->get_record('prog_assignment', ['id' => $assignment->get_id()]);
        $progcompletion_record = $DB->get_record('prog_completion', ['programid' => $program1->id, 'userid' => $user1->id, 'coursesetid' => 0]);
        $this->assertEquals(0, $progassign_record->completionevent);
        $this->assertEquals(0, $progassign_record->completioninstance);
        $this->assertEquals(-1, $progassign_record->completiontime);

        // Set fixed due date first
        $assignment->set_duedate($completiontime);

        $progassign_record = $DB->get_record('prog_assignment', ['id' => $assignment->get_id()]);
        $progcompletion_record = $DB->get_record('prog_completion', ['programid' => $program1->id, 'userid' => $user1->id, 'coursesetid' => 0]);
        $this->assertEquals(0, $progassign_record->completionevent);
        $this->assertEquals(0, $progassign_record->completioninstance);
        $this->assertEquals($completiontime, $progassign_record->completiontime);

        $this->assertEquals($completiontime, $progcompletion_record->timedue);

        // Set relative date on Course completion
        $course1 = $generator->create_course();

        $assignment->set_duedate($completiontime, 4, $course1->id);
        $progassign_record = $DB->get_record('prog_assignment', ['id' => $assignment->get_id()]);
        $progcompletion_record = $DB->get_record('prog_completion', ['programid' => $program1->id, 'userid' => $user1->id, 'coursesetid' => 0]);

        $this->assertEquals(4, $progassign_record->completionevent);
        $this->assertEquals($course1->id, $progassign_record->completioninstance);
        // Check the completion record for the user
        $this->assertEquals($completiontime, $progcompletion_record->timedue);
    }

    public function test_set_due_date_based_on_first_login() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $this->create_individual_data();

        $this->programgenerator->assign_to_program($this->programs[1]->id, ASSIGNTYPE_INDIVIDUAL, $this->users[1]->id, null, true);

        $completionperiod = 1209600; // Duration of 2 weeks
        $completionevent = COMPLETION_EVENT_FIRST_LOGIN;

        $assignment_record = $DB->get_record('prog_assignment', ['programid' => $this->programs[1]->id, 'assignmenttype' => ASSIGNTYPE_INDIVIDUAL, 'assignmenttypeid' => $this->users[1]->id]);
        $assignment = \totara_program\assignment\individual::create_from_id($assignment_record->id);

        $this->assertEquals(-1, $assignment_record->completiontime);
        $this->assertEquals(0, $assignment_record->completionevent);
        $this->assertEquals(0, $assignment_record->completioninstance);

        $completion_record = $DB->get_record('prog_completion', ['programid' => $this->programs[1]->id, 'userid' => $this->users[1]->id, 'coursesetid' => 0]);
        $this->assertEquals(-1, $completion_record->timedue);

        $assignment->set_duedate($completionperiod, $completionevent);

        $assignment_record = $DB->get_record('prog_assignment', ['programid' => $this->programs[1]->id, 'assignmenttype' => ASSIGNTYPE_INDIVIDUAL, 'assignmenttypeid' => $this->users[1]->id]);
        $this->assertEquals($completionperiod, $assignment_record->completiontime);
        $this->assertEquals(COMPLETION_EVENT_FIRST_LOGIN, $assignment_record->completionevent);
        $this->assertEquals(0, $assignment_record->completioninstance);

        $completion_record = $DB->get_record('prog_completion', ['programid' => $this->programs[1]->id, 'userid' => $this->users[1]->id, 'coursesetid' => 0]);
        $this->assertEquals(-1, $completion_record->timedue);

        // Create a second program this time with some content
        $this->programs[2] = $this->programgenerator->create_program();

        $course1 = $this->generator->create_course();
        $course2 = $this->generator->create_course();

        $progcontent = new prog_content($this->programs[2]->id);
        $progcontent->add_set(CONTENTTYPE_MULTICOURSE);

        $coursesets = $progcontent->get_course_sets();

        $coursedata = new stdClass();
        $coursedata->{$coursesets[0]->get_set_prefix() . 'courseid'} = $course1->id;
        $progcontent->add_course(1, $coursedata);
        $coursedata->{$coursesets[0]->get_set_prefix() . 'courseid'} = $course2->id;
        $progcontent->add_course(1, $coursedata);

        $coursesets[0]->timeallowed = 1209600; // 2 Weeks
        $coursesets[0]->save_set();

        $this->programgenerator->assign_to_program($this->programs[2]->id, ASSIGNTYPE_INDIVIDUAL, $this->users[1]->id, null, true);

        $completionperiod = 604800; // 1 Week
        $completionevent = COMPLETION_EVENT_FIRST_LOGIN;

        $assignment_record = $DB->get_record('prog_assignment', ['programid' => $this->programs[2]->id, 'assignmenttype' => ASSIGNTYPE_INDIVIDUAL, 'assignmenttypeid' => $this->users[1]->id]);
        $assignment = \totara_program\assignment\individual::create_from_id($assignment_record->id);

        $this->assertEquals(-1, $assignment_record->completiontime);
        $this->assertEquals(0, $assignment_record->completionevent);
        $this->assertEquals(0, $assignment_record->completioninstance);

        $completion_record = $DB->get_record('prog_completion', ['programid' => $this->programs[2]->id, 'userid' => $this->users[1]->id, 'coursesetid' => 0]);
        $this->assertEquals(-1, $completion_record->timedue);

        $assignment->set_duedate($completionperiod, $completionevent);

        $assignment_record = $DB->get_record('prog_assignment', ['programid' => $this->programs[2]->id, 'assignmenttype' => ASSIGNTYPE_INDIVIDUAL, 'assignmenttypeid' => $this->users[1]->id]);
        $this->assertEquals($completionperiod, $assignment_record->completiontime);
        $this->assertEquals(COMPLETION_EVENT_FIRST_LOGIN, $assignment_record->completionevent);
        $this->assertEquals(0, $assignment_record->completioninstance);

        $completion_record = $DB->get_record('prog_completion', ['programid' => $this->programs[2]->id, 'userid' => $this->users[1]->id, 'coursesetid' => 0]);
        $this->assertEquals(-1, $completion_record->timedue);
    }

    public function test_set_due_date_based_on_prog_enrolment() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $this->create_individual_data();

        $this->programgenerator->assign_to_program($this->programs[1]->id, ASSIGNTYPE_INDIVIDUAL, $this->users[1]->id, null, true);

        $completionperiod = 86400; // Duration of 2 weeks
        $completionevent = COMPLETION_EVENT_ENROLLMENT_DATE;

        $assignment_record = $DB->get_record('prog_assignment', ['programid' => $this->programs[1]->id, 'assignmenttype' => ASSIGNTYPE_INDIVIDUAL, 'assignmenttypeid' => $this->users[1]->id]);
        $assignment = \totara_program\assignment\individual::create_from_id($assignment_record->id);

        $course1 = $this->generator->create_course();
        $course2 = $this->generator->create_course();

        $progcontent = new prog_content($this->programs[1]->id);
        $progcontent->add_set(CONTENTTYPE_MULTICOURSE);

        $uniqueid = 'multiset';
        $multicourseset1 = new multi_course_set($this->programs[1]->id, null, $uniqueid);

        $coursedata = new stdClass();
        $coursedata->{$uniqueid . 'courseid'} = $course1->id;
        $multicourseset1->add_course($coursedata);
        $coursedata->{$uniqueid . 'courseid'} = $course2->id;
        $multicourseset1->add_course($coursedata);

        // Set certifpath so exceptions are calculated correctly
        $multicourseset1->certifpath = CERTIFPATH_STD;
        $multicourseset1->timeallowed = 1209600; // 2 Weeks
        $multicourseset1->save_set();

        $user_assignment_record = $DB->get_record('prog_user_assignment', ['programid' => $this->programs[1]->id, 'userid' => $this->users[1]->id, 'assignmentid' => $assignment->get_id()]);

        $assignment->set_duedate($completionperiod, $completionevent);

        $assignment_record = $DB->get_record('prog_assignment', ['programid' => $this->programs[1]->id, 'assignmenttype' => ASSIGNTYPE_INDIVIDUAL, 'assignmenttypeid' => $this->users[1]->id]);
        $user_assignment_record = $DB->get_record('prog_user_assignment', ['programid' => $this->programs[1]->id, 'userid' => $this->users[1]->id, 'assignmentid' => $assignment_record->id]);
        $completion_record = $DB->get_record('prog_completion', ['programid' => $this->programs[1]->id, 'userid' => $this->users[1]->id, 'coursesetid' => 0]);
        $coursesets = $DB->get_record('prog_courseset', ['programid' => $this->programs[1]->id]);

        $this->assertEquals(1, $user_assignment_record->exceptionstatus);
    }

    public function test_get_actual_duedate() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $programgenerator = $generator->get_plugin_generator('totara_program');

        $user1 = $generator->create_user();
        $program1 = $programgenerator->create_program();

        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);

        $timedue = new DateTime('2 weeks'); // 2 weeks from now
        $timedue->setTime(13, 30); // Set time to 1:30pm

        // Set completion values.
        $completiontime = $timedue->getTimestamp();
        $completionevent = COMPLETION_EVENT_NONE;
        $completioninstance = 0;
        $includechildren = null;

        $individualtype = 5;

        $data = new stdClass();
        $data->id = $program1->id;
        $data->item = array($individualtype => array($user1->id => 1));
        $data->completiontime = array($individualtype => array($user1->id => $completiontime));
        $data->completionevent = array($individualtype => array($user1->id => $completionevent));
        $data->completioninstance = array($individualtype => array($user1->id => $completioninstance));
        $data->includechildren = array ($individualtype => array($user1->id => $includechildren));

        $assignmenttoprog = prog_assignments::factory($individualtype);
        $assignmenttoprog->update_assignments($data, false);

        $program = new \program($program1->id);
        $program->update_learner_assignments(true);

        // Get assignment
        $assignments = $DB->get_records('prog_assignment', ['programid' => $program1->id]);
        $record = reset($assignments);
        $assignment = \totara_program\assignment\individual::create_from_id($record->id);

        $acutaldate = $assignment->get_actual_duedate();
    }

    public function test_remove() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $programgenerator = $generator->get_plugin_generator('totara_program');

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $program1 = $programgenerator->create_program();

        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);
        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);
        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $user3->id, null, true);

        $record = $DB->get_record('prog_assignment', ['programid' => $program1->id, 'assignmenttype' => 5, 'assignmenttypeid' => $user1->id]);
        $assignment = \totara_program\assignment\individual::create_from_id($record->id);

        // Check we have some db records
        $this->assertCount(3, $DB->get_records('prog_assignment', ['programid' => $program1->id]));
        $this->assertCount(3, $DB->get_records('prog_completion', ['programid' => $program1->id]));

        $assignment->remove();

        $this->assertCount(2, $DB->get_records('prog_assignment', ['programid' => $program1->id]));
    }


    public function test_update_user_assignments() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $programgenerator = $generator->get_plugin_generator('totara_program');

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $program1 = $programgenerator->create_program();

        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);
        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);
        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $user3->id, null, true);

        $record = $DB->get_record('prog_assignment', ['programid' => $program1->id, 'assignmenttype' => 5, 'assignmenttypeid' => $user1->id]);
        $assignment = \totara_program\assignment\individual::create_from_id($record->id);

        $completion_record = $DB->get_record('prog_completion', ['programid' => $program1->id, 'coursesetid' => 0, 'userid' => $user1->id]);
        // Uncomment this once generator is fixed and correctly creates completion record
        $this->assertNotFalse($completion_record);
        $this->assertEquals(-1, $completion_record->timedue);
    }

    public function test_user_assignment_records() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $programgenerator = $generator->get_plugin_generator('totara_program');

        $user1 = $generator->create_user();
        $program1 = $programgenerator->create_program();

        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);

        $record = $DB->get_record('prog_assignment', ['programid' => $program1->id, 'assignmenttype' => 5, 'assignmenttypeid' => $user1->id]);
        $assignment = \totara_program\assignment\individual::create_from_id($record->id);

        $this->assertCount(0, $DB->get_records('prog_completion', ['programid' => $program1->id]));

        $reflection = new ReflectionClass('\totara_program\assignment\individual');
        $method = $reflection->getMethod('create_user_assignment_records');
        $method->setAccessible(true);
        $method->invokeArgs($assignment, []);

        $this->assertCount(1, $DB->get_records('prog_completion', ['programid' => $program1->id]));

        // The function assign_learners_bulk will die in a fire if called a second
        // time so we can't test this function will run again without issue.
    }

    public function test_ensure_category_loaded() {
        global $DB, $CFG;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $programgenerator = $generator->get_plugin_generator('totara_program');

        $user1 = $generator->create_user();
        $program1 = $programgenerator->create_program();

        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);

        $record = $DB->get_record('prog_assignment', ['programid' => $program1->id, 'assignmenttype' => 5, 'assignmenttypeid' => $user1->id]);
        $assignment = \totara_program\assignment\individual::create_from_id($record->id);

        $reflection = new ReflectionClass('\totara_program\assignment\individual');
        $property = $reflection->getProperty('program');
        $property->setAccessible(true);
        $this->assertNull($property->getValue($assignment));

        $reflection = new ReflectionClass('\totara_program\assignment\individual');
        $method = $reflection->getMethod('ensure_program_loaded');
        $method->setAccessible(true);
        $method->invokeArgs($assignment, []);

        $actual = $property->getValue($assignment);
        $this->assertNotNull($actual);
        $this->assertEquals($program1->id, $actual->id);
        $this->assertEquals($program1->fullname, $actual->fullname);
    }

    public function test_ensure_program_loaded() {
        global $DB, $CFG;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $programgenerator = $generator->get_plugin_generator('totara_program');

        $user1 = $generator->create_user();
        $program1 = $programgenerator->create_program();

        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);

        $record = $DB->get_record('prog_assignment', ['programid' => $program1->id, 'assignmenttype' => 5, 'assignmenttypeid' => $user1->id]);
        $assignment = \totara_program\assignment\individual::create_from_id($record->id);

        $reflection = new ReflectionClass('\totara_program\assignment\individual');
        $property = $reflection->getProperty('category');
        $property->setAccessible(true);
        $this->assertNull($property->getValue($assignment));

        $reflection = new ReflectionClass('\totara_program\assignment\individual');
        $method = $reflection->getMethod('ensure_category_loaded');
        $method->setAccessible(true);
        $method->invokeArgs($assignment, []);

        $actual = $property->getValue($assignment);
        $this->assertNotNull($actual);
        $this->assertInstanceOf('individuals_category', $actual);

        $assignment = \totara_program\assignment\individual::create_from_id($record->id);

        $this->assertEquals(1, $assignment->get_user_count());
        $this->assertEquals(5, $actual->id);
    }

    public function test_get_user_count() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $programgenerator = $generator->get_plugin_generator('totara_program');

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $program1 = $programgenerator->create_program();

        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);
        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $user2->id);
        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $user3->id);

        $assignid = $DB->get_field('prog_assignment', 'id', ['programid' => $program1->id, 'assignmenttype' => 5, 'assignmenttypeid' => $user1->id]);
        $assignment = \totara_program\assignment\individual::create_from_id($assignid);

        $this->assertEquals(1, $assignment->get_user_count());
    }
}
