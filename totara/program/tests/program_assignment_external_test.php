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

use totara_program\assignment\external;

class totara_program_assignment_external_testcase extends advanced_testcase {

    private function basic_setup() {
        global $DB;

        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();

        $user1 = $generator->create_user(['firstname' => 'Bob', 'lastname' => 'Smith']);
        $user2 = $generator->create_user(['firstname' => 'Joe', 'lastname' => 'Blogs']);
        $user3 = $generator->create_user();
        $user4 = $generator->create_user();

        $programgenerator = $generator->get_plugin_generator('totara_program');

        $program1 = $programgenerator->create_program();
        // Assign users to program
        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);
        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);

        $cohortgenerator = $generator->get_plugin_generator('totara_cohort');
        $audience1 = $this->getDataGenerator()->create_cohort(['name' => 'Audience 1']);
        $cohortgenerator->cohort_assign_users($audience1->id, [$user3->id, $user4->id]);

        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_COHORT, $audience1->id, null, true);

        $data = new \stdClass();
        $data->programs[1] = $program1;
        $data->users[1] = $user1;
        $data->users[2] = $user2;
        $data->users[3] = $user3;
        $data->users[4] = $user4;
        $data->audiences[1] = $audience1;

        $assignments = $DB->get_records('prog_assignment', ['programid' => $program1->id]);

        foreach ($assignments as $assignment) {
            if ($assignment->assignmenttype == 5 && $assignment->assignmenttypeid == $user1->id) {
                $data->assignments[1] = $assignment;
            } else if ($assignment->assignmenttype == 5 && $assignment->assignmenttypeid == $user2->id) {
                $data->assignments[2] = $assignment;
            } else if ($assignment->assignmenttype == 3 && $assignment->assignmenttypeid == $audience1->id) {
                $data->assignments[3] = $assignment;
            }
        }

        return $data;
    }

    public function test_ensure_user_can_manage_programs() {
        global $DB;

        $this->resetAfterTest(true);
        $setup = $this->basic_setup();

        $roleid = $this->getDataGenerator()->create_role();
        $manager = $this->getDataGenerator()->create_user(['firstname' => 'Manager', 'lastname' => 'One']);
        $capabilities = ['totara/program:configureassignments'];

        foreach ($capabilities as $cap) {
            role_change_permission($roleid, context_system::instance(), $cap, CAP_ALLOW);
        }

        $this->setUser($manager);

        // Check that manager doesn't have permission without role
        $reflection = new ReflectionClass('\totara_program\assignment\external');
        $method = $reflection->getMethod('ensure_user_can_manage_program_assignments');
        $method->setAccessible(true);
        $this->expectException('required_capability_exception');
        $this->expectExceptionMessage('Sorry, but you do not currently have permissions to do that (Configure program assignments)');
        $result = $method->invokeArgs(null, [$setup->programs[1]->id]);
        $this->assertFalse($result);

        $this->getDataGenerator()->role_assign($roleid, $manager->id);

        // Now with the role the manager should have permission
        $reflection = new ReflectionClass('\totara_program\assignment\external');
        $method = $reflection->getMethod('ensure_user_can_manage_program_assignments');
        $method->setAccessible(true);
        $result = $method->invokeArgs(null, [$setup->programs[1]->id]);
        $this->assertTrue($result);
    }

    public function test_add_assignments() {
        global $DB;

        $this->setAdminUser();
        $setup = $this->basic_setup();

        $typeid = 5;
        $items = [$setup->users[3]->id, $setup->users[4]->id];

        $this->assertEquals(3, $DB->count_records('prog_assignment', ['programid' => $setup->programs[1]->id]));

        $return = external::add_assignments($setup->programs[1]->id, $typeid, $items);

        $records = $DB->get_records('prog_assignment', ['programid' => $setup->programs[1]->id]);
        $this->assertEquals(5, count($records));
    }

    public function test_filter_assignments() {
        $this->setAdminUser();
        $setup = $this->basic_setup();

        $categories = [5]; // IDs of the categories to show
        $recent = false;
        $term = 'joe';

        $return = external::filter_assignments($categories, $recent, $term, $setup->programs[1]->id);

        $expected = [
            'items' => [
                [
                    'id' => (int)$setup->assignments[2]->id,
                    'name' => 'Joe Blogs',
                    'type' => 'Individual',
                    'type_id' => 5,
                    'checkbox' => false,
                    'dropdown' => false,
                    'includechildren' => 0,
                    'duedate' => '',
                    'actualduedate' => 'No due date',
                    'learnercount' => 1,
                    'duedateupdatable' => true
                ],
            ],
            'count' => 1,
            'toomany' => false
        ];

        $this->assertEquals($expected, $return);

        $term = '';
        $return = external::filter_assignments($categories, $recent, $term, $setup->programs[1]->id);

        $expected2 = [
            'items' => [
                [
                    'id' => (int)$setup->assignments[1]->id,
                    'name' => 'Bob Smith',
                    'type' => 'Individual',
                    'type_id' => 5,
                    'checkbox' => false,
                    'dropdown' => false,
                    'includechildren' => 0,
                    'duedate' => '',
                    'actualduedate' => 'No due date',
                    'learnercount' => 1,
                    'duedateupdatable' => true
                ],
                [
                    'id' => (int)$setup->assignments[2]->id,
                    'name' => 'Joe Blogs',
                    'type' => 'Individual',
                    'type_id' => 5,
                    'checkbox' => false,
                    'dropdown' => false,
                    'includechildren' => 0,
                    'duedate' => '',
                    'actualduedate' => 'No due date',
                    'learnercount' => 1,
                    'duedateupdatable' => true
                ],
            ],
            'count' => 2,
            'toomany' => false

        ];

        $this->assertEquals($expected2, $return);
    }

    public function test_remove_assignment() {
        global $DB;

        $this->setAdminUser();
        $setup = $this->basic_setup();

        $records = $DB->count_records('prog_assignment', ['programid' => $setup->programs[1]->id]);
        $this->assertEquals(3, $records);

        $return = external::remove_assignment($setup->assignments[1]->id);
        $expected = [
            'status' => [
                'status_string' => 'Caution: Program is live - there are learners who will see or be affected by changes you make<br /><span class="assignmentcount">3 learner(s) assigned: 3 active, 0 exception(s).</span><br /><span></span>',
                'state' => 'warning',
                'exception_count' => 0,
            ],
            'success' => true
        ];
        $this->assertEquals($expected, $return);

        $records = $DB->get_records('prog_assignment', ['programid' => $setup->programs[1]->id]);
        $this->assertCount(2, $records);
        $this->assertArrayNotHasKey($setup->assignments[1]->id, $records);
    }

    public function test_set_fixed_due_date() {
        global $DB;

        $this->setAdminUser();
        $setup = $this->basic_setup();

        $date = '22/02/2042';
        $hour = '13';
        $minute = '30';

        $return = external::set_fixed_due_date($setup->assignments[1]->id, $date, $hour, $minute);

        $expected = [
            'duedate' => '',
            'actualduedate' => '22 Feb 2042 at 13:30',
            'status' => [
                'status_string' => 'Caution: Program is live - there are learners who will see or be affected by changes you make<br /><span class="assignmentcount">4 learner(s) assigned: 4 active, 0 exception(s).</span><br /><span></span>',
                'state' => 'warning',
                'exception_count' => 0
            ],
            'duedateupdatable' => true
        ];

        $this->assertEquals($expected, $return);

        $completion = $DB->get_record('prog_completion', ['programid' => $setup->programs[1]->id, 'userid' => $setup->users[1]->id]);
        $timedue = \DateTime::createFromFormat('d/m/Y G:i', $date . ' ' . $hour . ':' . $minute);
        $this->assertEquals($timedue->getTimestamp(), $completion->timedue);
    }

    public function test_set_relative_due_date() {
        global $DB, $CFG;

        $this->setAdminUser();
        $CFG->enablecompletion = true;
        $setup = $this->basic_setup();

        $datagenerator = $this->getDataGenerator();
        $completion_generator = $datagenerator->get_plugin_generator('core_completion');

        // Create course
        $coursedefaults = [
            'enablecompletion' => COMPLETION_ENABLED,
            'completionstartonenrol' => 1,
            'completionprogressonview' => 1
        ];

        $course1 = $datagenerator->create_course($coursedefaults);
        $datagenerator->enrol_user($setup->users[1]->id, $course1->id);
        //$completion_generator->complete_course($course1, $setup->users[1]);

        $num = 5; // Number of weeks
        $period = 3; // TIME_SELECTOR_WEEKS
        $event = 4; // Course completion event
        $eventinstanceid = $course1->id;

        $return = external::set_relative_due_date($setup->assignments[1]->id, $num, $period, $event, $eventinstanceid);

        $expected = [
            'duedate' => 'Complete within 5 Week(s) of completion of course \'Test course 1\'',
            'actualduedate' => 'Not yet known',
            'status' => [
                'status_string' => 'Caution: Program is live - there are learners who will see or be affected by changes you make<br /><span class="assignmentcount">4 learner(s) assigned: 3 active, 1 exception(s).</span><br /><span></span>',
                'state' => 'warning',
                'exception_count' => 1
            ],
            'duedateupdatable' => true
        ];

        $this->assertEquals($expected, $return);

        $actual = $DB->get_record('prog_assignment', ['id' => $setup->assignments[1]->id]);
        $this->assertEquals($event, $actual->completionevent);
        $this->assertEquals($eventinstanceid, $actual->completioninstance);
        $expectedcompletiontime = (DAYSECS * 7) * 5;
        $this->assertEquals($expectedcompletiontime, $actual->completiontime);
    }

    public function test_set_includechildren() {
        global $DB;

        $this->setAdminUser();
        $setup = $this->basic_setup();

        $assignmentid = $setup->assignments[1]->id;
        $assignmentrecord = $DB->get_record('prog_assignment', ['id' => $assignmentid]);
        $this->assertEquals(0, $assignmentrecord->includechildren);

        external::set_includechildren($assignmentid, 1);

        $assignmentrecord = $DB->get_record('prog_assignment', ['id' => $assignmentid]);
        $this->assertEquals(1, $assignmentrecord->includechildren);

        external::set_includechildren($assignmentid, 0);

        $assignmentrecord = $DB->get_record('prog_assignment', ['id' => $assignmentid]);
        $this->assertEquals(0, $assignmentrecord->includechildren);
    }

    public function test_search_assignments() {
        global $DB;

        $setup = $this->basic_setup();

        $assignments[] = \totara_program\assignment\individual::create_from_id($setup->assignments[1]->id);
        $assignments[] = \totara_program\assignment\individual::create_from_id($setup->assignments[2]->id);

        $this->assertEquals(2, count($assignments));

        $result = external::search_assignments($assignments, 'smi');
        $this->assertEquals(1, count($result));

        $assignment = reset($result);
        $this->assertEquals('Bob Smith', $assignment->get_name());
    } 
}
