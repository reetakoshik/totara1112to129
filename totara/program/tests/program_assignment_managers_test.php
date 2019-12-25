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

class totara_program_assignment_managers_test extends advanced_testcase {


    private $generator = null;
    private $programgenerator = null;
    private $programs = [];
    private $users = [];
    private $managers = [];
    private $managerjas = [];

    /**
     * Unset all class variables
     */
    public function tearDown() {
        $this->generator = null;
        $this->programgenerator = null;
        $this->programs = [];
        $this->users = [];
        $this->managers = [];
        $this->managerjas = [];
        parent::tearDown();
    }

    /**
     * Create data needed for tests
     */
    private function create_jobassignment_data() {
        $this->generator = $this->getDataGenerator();
        $this->programgenerator = $this->generator->get_plugin_generator('totara_program');

        $this->programs[1] = $this->programgenerator->create_program();

        $this->users[1] = $this->generator->create_user();
        $this->users[2] = $this->generator->create_user();
        $this->users[3] = $this->generator->create_user();
        $this->users[4] = $this->generator->create_user();
        $this->users[5] = $this->generator->create_user();

        // Create some managers
        $this->managers[1] = $this->generator->create_user(['firstname' => 'Manager', 'lastname' => 'One']);
        $this->managers[2] = $this->generator->create_user(['firstname' => 'Manager', 'lastname' => 'Two']);
        $this->managers[3] = $this->generator->create_user(['firstname' => 'Manager', 'lastname' => 'Three']);

        // Set manager for managers[1] to be managers[2]
        $this->managerjas[1] = \totara_job\job_assignment::create_default($this->managers[1]->id, ['fullname' => 'Main Job']);
        $this->managerjas[2] = \totara_job\job_assignment::create_default($this->managers[2]->id, ['managerjaid' => $this->managerjas[1]->id]);

        // Set up job assignments
        $user1ja1 = \totara_job\job_assignment::create_default($this->users[1]->id, ['managerjaid' => $this->managerjas[1]->id]);
        $user2ja1 = \totara_job\job_assignment::create_default($this->users[2]->id, ['managerjaid' => $this->managerjas[1]->id]);
        $user3ja1 = \totara_job\job_assignment::create_default($this->users[3]->id, ['managerjaid' => $this->managerjas[2]->id]);
        $user4ja1 = \totara_job\job_assignment::create_default($this->users[4]->id, ['managerjaid' => $this->managerjas[2]->id]);
        $user5ja1 = \totara_job\job_assignment::create_default($this->users[5]->id, ['managerjaid' => $this->managerjas[2]->id]);
    }

    public function test_create_from_id() {
        global $DB;
        $this->resetAfterTest(true);

        $this->create_jobassignment_data();
        $this->programgenerator->assign_to_program($this->programs[1]->id, ASSIGNTYPE_MANAGERJA, $this->managerjas[1]->id);

        $params = [
            'programid' => $this->programs[1]->id,
            'assignmenttype' => ASSIGNTYPE_MANAGERJA,
            'assignmenttypeid' => $this->managerjas[1]->id
        ];
        $assignmentrecord = $DB->get_record('prog_assignment', $params);
        $assignment = \totara_program\assignment\manager::create_from_id($assignmentrecord->id);

        $this->assertEquals('Manager One - Main Job', $assignment->get_name());
        $this->assertEquals($this->programs[1]->id, $assignment->get_programid());
    }

    public function test_create_from_instance_id() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $this->create_jobassignment_data();

        $assignment = \totara_program\assignment\base::create_from_instance_id($this->programs[1]->id, ASSIGNTYPE_MANAGERJA, $this->managerjas[1]->id);
        $assignment->save();

        $reflection = new ReflectionClass('\totara_program\assignment\manager');
        $property = $reflection->getProperty('typeid');
        $property->setAccessible(true);
        $this->assertEquals(ASSIGNTYPE_MANAGERJA, $property->getValue($assignment));

        $property = $reflection->getProperty('instanceid');
        $property->setAccessible(true);
        $this->assertEquals($this->managerjas[1]->id, $property->getValue($assignment));

        // We should have 3 record for prog_completion prog_user_assignment (user1, user2 and manager2)
        $this->assertEquals(3, $DB->count_records('prog_completion', ['programid' => $this->programs[1]->id]));
        $this->assertEquals(3, $DB->count_records('prog_user_assignment', ['programid' => $this->programs[1]->id]));

        $userids = [$this->users[1]->id, $this->users[2]->id, $this->managers[1]->id];
        list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        // Check completion records
        $sql = "SELECT * FROM {prog_completion} WHERE programid = :programid AND userid $insql";
        $params = array_merge(['programid' => $this->programs[1]->id], $inparams);
        $progcompletionrecords = $DB->get_records_sql($sql, $params);

        foreach ($progcompletionrecords as $completionrecord) {
            $this->assertEquals('-1', $completionrecord->timedue);
            $this->assertEquals(0, $completionrecord->status);
        }

        // Check user assignment records
        $sql = "SELECT * FROM {prog_user_assignment} WHERE programid = :programid AND userid $insql";
        $params = array_merge(['programid' => $this->programs[1]->id], $inparams);
        $proguserassignrecords = $DB->get_records_sql($sql, $params);

        foreach ($proguserassignrecords as $userassignrecord) {
            $this->assertEquals($assignment->get_id(), $userassignrecord->assignmentid);
            $this->assertEquals(0, $userassignrecord->exceptionstatus);
        }
    }


    public function test_get_user_count() {
        global $DB;
        $this->resetAfterTest(true);

        $this->create_jobassignment_data();

        $this->programgenerator->assign_to_program($this->programs[1]->id, ASSIGNTYPE_MANAGERJA, $this->managerjas[1]->id);

        $params = [
            'programid' => $this->programs[1]->id,
            'assignmenttype' => ASSIGNTYPE_MANAGERJA,
            'assignmenttypeid' => $this->managerjas[1]->id
        ];
        $assignmentrecord = $DB->get_record('prog_assignment', $params);
        $assignment = \totara_program\assignment\manager::create_from_id($assignmentrecord->id);

        $this->assertEquals(3, $assignment->get_user_count());

        $assignment->set_includechildren(1);
        $assignment->save();

        // This should now include all users assigned to pos3 too
        $this->assertEquals(6, $assignment->get_user_count());
    }

    public function test_get_duedate() {
        global $DB;
        $this->resetAfterTest(true);
        $this->create_jobassignment_data();

        $this->programgenerator->assign_to_program($this->programs[1]->id, ASSIGNTYPE_MANAGERJA, $this->managerjas[1]->id);
        
        $timedue = new DateTime('2 weeks'); // 2 weeks from now
        $hour = 13;
        $minute = 30;
        $timedue->setTime($hour, $minute); // Set time to 1:30pm
        $completiontime = $timedue->getTimestamp();
        $completiontimestring = $timedue->format('d/m/Y');

        // Set completion values.
        $completionevent = COMPLETION_EVENT_NONE;
        $completioninstance = 0;
        $includechildren = null;

        $managertype = 6;

        $data = new stdClass();
        $data->id = $this->programs[1]->id;
        $data->completiontime = array($managertype => array($this->managerjas[1]->id => $completiontimestring));
        $data->completiontimehour = array($managertype => array($this->managerjas[1]->id => $hour));
        $data->completiontimeminute = array($managertype => array($this->managerjas[1]->id => $minute));
        $data->item = array($managertype => array($this->managerjas[1]->id => 1));
        $data->completionevent = array($managertype => array($this->managerjas[1]->id => $completionevent));
        $data->completioninstance = array($managertype => array($this->managerjas[1]->id => $completioninstance));
        $data->includechildren = array($managertype => array($this->managerjas[1]->id => $includechildren));

        $assignmenttoprog = prog_assignments::factory($managertype);
        $assignmenttoprog->update_assignments($data, false);

        // Get assignment record
        $assignmentrecord = $DB->get_record('prog_assignment', ['programid' => $this->programs[1]->id, 'assignmenttype' => ASSIGNTYPE_MANAGERJA, 'assignmenttypeid' => $this->managerjas[1]->id]);
        $assignment = \totara_program\assignment\manager::create_from_id($assignmentrecord->id);

        // Check due date is correct
        $expected = new \stdClass();
        $expected->string = 'Complete by ' . $timedue->format('j M Y') . ' at ' . $timedue->format('G:i');
        $expected->changeable = true;
        $this->assertEquals($expected, $assignment->get_duedate());

        // Set a realative due date and check again
        $course1 = $this->generator->create_course();
        $completionevent = COMPLETION_EVENT_COURSE_COMPLETION;
        $completioninstance = $course1->id;
        $includechildren = null;

        $data->item = array($managertype => array($this->managerjas[1]->id => 1));
        $data->completionevent = array($managertype => array($this->managerjas[1]->id => $completionevent));
        $data->completioninstance = array($managertype => array($this->managerjas[1]->id => $completioninstance));
        $data->includechildren = array($managertype => array($this->managerjas[1]->id => $includechildren));

        // Completion time needs to be in a stupid format (num and period concatinated with a space...)
        $completiontime = '3 ' . TIME_SELECTOR_WEEKS;
        $data->completiontime = array($managertype => array($this->managerjas[1]->id => $completiontime));

        $assignmenttoprog = prog_assignments::factory($managertype);
        $assignmenttoprog->update_assignments($data, false);

        // Reload assignment
        $assignment = \totara_program\assignment\manager::create_from_id($assignmentrecord->id);
        $expected = new \stdClass();
        $expected->string = "Complete within 3 Week(s) of completion of course '$course1->fullname'";
        $expected->changeable = true;
        $this->assertEquals($expected, $assignment->get_duedate());
    }

    public function test_set_duedate() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $this->create_jobassignment_data();

        $this->programgenerator->assign_to_program($this->programs[1]->id, ASSIGNTYPE_MANAGERJA, $this->managerjas[1]->id);

        $timedue = new DateTime('2 weeks'); // 2 weeks from now
        $timedue->setTime(13, 30); // Set time to 1:30pm
        $completiontime = $timedue->getTimestamp();

        $assignmentrecord = $DB->get_record('prog_assignment', ['programid' => $this->programs[1]->id, 'assignmenttype' => ASSIGNTYPE_MANAGERJA, 'assignmenttypeid' => $this->managerjas[1]->id]);
        $assignment = \totara_program\assignment\manager::create_from_id($assignmentrecord->id);

        // Set a fixed due date
        $assignment->set_duedate($completiontime);

        $progassign_record = $DB->get_record('prog_assignment', ['id' => $assignment->get_id()]);
        $progcompletion_record = $DB->get_record('prog_completion', ['programid' => $this->programs[1]->id, 'userid' => $this->users[1]->id, 'coursesetid' => 0]);
        $this->assertEquals(0, $progassign_record->completionevent);
        $this->assertEquals(0, $progassign_record->completioninstance);
        $this->assertEquals($completiontime, $progassign_record->completiontime);

        // Check all comletion records for users
        $completionrecord1 = $DB->get_record('prog_completion', ['programid' => $this->programs[1]->id, 'userid' => $this->users[1]->id, 'coursesetid' => 0]);
        $this->assertEquals($completiontime, $completionrecord1->timedue);

        $completionrecord2 = $DB->get_record('prog_completion', ['programid' => $this->programs[1]->id, 'userid' => $this->users[2]->id, 'coursesetid' => 0]);
        $this->assertEquals($completiontime, $completionrecord2->timedue);
    }

    public function test_create_user_assignment_records() {
        global $DB;
        $this->resetAfterTest(true);
        $this->create_jobassignment_data();

        $this->programgenerator->assign_to_program($this->programs[1]->id, ASSIGNTYPE_MANAGERJA, $this->managerjas[1]->id);

        $this->assertEquals(0, $DB->count_records('prog_completion', ['programid' => $this->programs[1]->id]));

        $assignmentrecord = $DB->get_record('prog_assignment', ['programid' => $this->programs[1]->id, 'assignmenttype' => ASSIGNTYPE_MANAGERJA, 'assignmenttypeid' => $this->managerjas[1]->id]);
        $assignment = \totara_program\assignment\manager::create_from_id($assignmentrecord->id);

        $reflection = new ReflectionClass('\totara_program\assignment\individual');
        $method = $reflection->getMethod('create_user_assignment_records');
        $method->setAccessible(true);
        $method->invokeArgs($assignment, []);

        // We should now have 2 completion records one for each user
        $this->assertEquals(3, $DB->count_records('prog_completion', ['programid' => $this->programs[1]->id]));
        $this->assertEquals(1, $DB->count_records('prog_completion', ['programid' => $this->programs[1]->id, 'userid' => $this->users[1]->id]));
        $this->assertEquals(1, $DB->count_records('prog_completion', ['programid' => $this->programs[1]->id, 'userid' => $this->users[2]->id]));
        $this->assertEquals(1, $DB->count_records('prog_completion', ['programid' => $this->programs[1]->id, 'userid' => $this->managers[2]->id]));
    }
}
