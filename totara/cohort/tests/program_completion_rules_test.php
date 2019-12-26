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
 * @author Maria Torres <maria.torres@totaralms.com>
 * @package totara
 * @subpackage cohort
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot . '/totara/cohort/lib.php');

/**
 * Test program completion rules.
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit totara_cohort_program_completion_rules_testcase
 *
 */
class totara_cohort_program_completion_rules_testcase extends reportcache_advanced_testcase {

    private $userprograms = array();
    /** @var totara_cohort_generator $cohort_generator */
    private $cohort_generator = null;
    private $cohort = null;
    private $ruleset = 0;
    private $program1 = null;
    private $program2 = null;
    private $program3 = null;
    private $program4 = null;
    private $user1;
    private $user2;
    private $user3;
    private $user4;
    private $user5;
    private $user6;
    private $user7;
    private $teststart = null;

    protected function tearDown() {
        $this->userprograms = null;
        $this->cohort_generator = null;
        $this->cohort = null;
        $this->ruleset = null;
        $this->program1 = null;
        $this->program2 = null;
        $this->program3 = null;
        $this->program4 = null;
        $this->user1 = null;
        $this->user2 = null;
        $this->user3 = null;
        $this->user4 = null;
        $this->user5 = null;
        $this->user6 = null;
        $this->user7 = null;
        $this->teststart = null;
        parent::tearDown();
    }

    /*
     * Program completion data:
     *-----------------*---------------------*---------------*--------------*----------------*------------------*-----------------*
     |      users      |       programs      | time assigned | time started | time completed | DurationAssigned | DurationStarted |
     |-----------------*---------------------*---------------*--------------*----------------*------------------*-----------------*
     |      user1      | program1            |  -5 days      |  -3 days     |    -1 day      |     4 days       |     2 days      |
     |-----------------*---------------------*---------------*--------------*----------------*------------------*-----------------*
     |      user2      | program1            |  -5 days      |  -5 days     |    -2 days     |     3 days       |     3 days      |
     |-----------------*---------------------*---------------*--------------*----------------*------------------*-----------------*
     |      user3      | program1 - program2 |  -3 days      |  -2 days     |    -1 day      |     2 days       |     1 days      |
     |-----------------*---------------------*---------------*--------------*----------------*------------------*-----------------*
     |      user4      | program2            |  -2 days      |  -4 days     |    -1 day      |     1 days       |     3 days      |
     |-----------------*---------------------*---------------*--------------*----------------*------------------*-----------------*
     |      user5      | program2            |  -4 days      |  -3 days     |    -2 days     |     2 days       |     1 days      |
     |-----------------*---------------------*---------------*--------------*----------------*------------------*-----------------*
     |      user6      | program2            |  -3 days      |  -5 days     | +1 day(future) |     4 days       |     6 days      |
     |-----------------*---------------------*---------------*--------------*----------------*------------------*-----------------*
     |      user7      | program2            |  -7 days      |  -5 days     |    -1 day      |     6 days       |     4 days      |
     |-----------------*---------------------*---------------*--------------*----------------*------------------*-----------------*
     |      user8      |  -----------------  |  -----------  |  ----------  |  ------------  |  --------------  |  -------------  |
     *-----------------*---------------------*---------------*--------------*----------------*------------------*-----------------*
     |  user1 - user7  | program3 - program4 |     now       |     now      |  ------------  |  --------------  |  -------------  |
     *-----------------*---------------------*---------------*--------------*----------------*------------------*-----------------*
    */
    public function setUp() {
        global $DB, $CFG;

        parent::setup();
        set_config('enablecompletion', 1);
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Prevent random failures by using the same now timestamps for all test data.
        $this->teststart = time();

        // Set totara_cohort generator.
        $this->cohort_generator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');

        // Create 8 users.
        $this->assertEquals(2, $DB->count_records('user'));
        for ($i = 1; $i <= 8; $i++) {
            $this->{'user'.$i} = $this->getDataGenerator()->create_user();
        }
        $this->assertEquals(10, $DB->count_records('user'));

        // Create a couple of courses.
        $this->assertEquals(1, $DB->count_records('course'));
        $setting = array('enablecompletion' => 1, 'completionstartonenrol' => 1);
        $course1 = $this->getDataGenerator()->create_course($setting);
        $course2 = $this->getDataGenerator()->create_course($setting);
        $course3 = $this->getDataGenerator()->create_course($setting);
        $course4 = $this->getDataGenerator()->create_course($setting);
        $this->assertEquals(5, $DB->count_records('course'));

        // Create two programs.
        $this->assertEquals(0, $DB->count_records('prog'));
        $this->program1 = $this->getDataGenerator()->create_program();
        $this->program2 = $this->getDataGenerator()->create_program();
        $this->program3 = $this->getDataGenerator()->create_program();
        $this->program4 = $this->getDataGenerator()->create_program();
        $this->assertEquals(4, $DB->count_records('prog'));

        // Assign courses to programs.
        $this->getDataGenerator()->add_courseset_program($this->program1->id, array($course1->id));
        $this->getDataGenerator()->add_courseset_program($this->program2->id, array($course2->id));
        $this->getDataGenerator()->add_courseset_program($this->program3->id, array($course3->id));
        $this->getDataGenerator()->add_courseset_program($this->program4->id, array($course4->id));

        // Assign users to programs.
        $usersprogram1 = array($this->user1->id, $this->user2->id, $this->user3->id);
        $this->getDataGenerator()->assign_program($this->program1->id, $usersprogram1);

        $usersprogram2 = array($this->user3->id, $this->user4->id, $this->user5->id, $this->user6->id, $this->user7->id);
        $this->getDataGenerator()->assign_program($this->program2->id, $usersprogram2);

        $this->userprograms[$this->program1->id] = $usersprogram1;
        $this->userprograms[$this->program2->id] = $usersprogram2;

        $usersprogram3 = array($this->user1->id, $this->user2->id, $this->user3->id, $this->user4->id, $this->user5->id, $this->user6->id, $this->user7->id);
        $this->getDataGenerator()->assign_program($this->program3->id, $usersprogram3);
        $this->getDataGenerator()->assign_program($this->program4->id, $usersprogram3);

        // Create timecreated for each user.
        $timecreated = array();
        $timecreated[$this->user1->id] = $this->teststart - (5 * DAYSECS);
        $timecreated[$this->user2->id] = $this->teststart - (5 * DAYSECS);
        $timecreated[$this->user3->id] = $this->teststart - (3 * DAYSECS);
        $timecreated[$this->user4->id] = $this->teststart - (2 * DAYSECS);
        $timecreated[$this->user5->id] = $this->teststart - (4 * DAYSECS);
        $timecreated[$this->user6->id] = $this->teststart - (3 * DAYSECS);
        $timecreated[$this->user7->id] = $this->teststart - (7 * DAYSECS);

        // Create timestarted for each user.
        $timestarted = array();
        $timestarted[$this->user1->id] = $this->teststart - (3 * DAYSECS);
        $timestarted[$this->user2->id] = $this->teststart - (5 * DAYSECS);
        $timestarted[$this->user3->id] = $this->teststart - (2 * DAYSECS);
        $timestarted[$this->user4->id] = $this->teststart - (4 * DAYSECS);
        $timestarted[$this->user5->id] = $this->teststart - (3 * DAYSECS);
        $timestarted[$this->user6->id] = $this->teststart - (5 * DAYSECS);
        $timestarted[$this->user7->id] = $this->teststart - (5 * DAYSECS);

        // Create timecompleted for each user.
        $timecompleted = array();
        $timecompleted[$this->user1->id] = $this->teststart - (1 * DAYSECS);
        $timecompleted[$this->user2->id] = $this->teststart - (2 * DAYSECS);
        $timecompleted[$this->user3->id] = $this->teststart - (1 * DAYSECS);
        $timecompleted[$this->user4->id] = $this->teststart - (1 * DAYSECS);
        $timecompleted[$this->user5->id] = $this->teststart - (2 * DAYSECS);
        $timecompleted[$this->user6->id] = $this->teststart + (1 * DAYSECS);
        $timecompleted[$this->user7->id] = $this->teststart - (1 * DAYSECS);

        // Make completion for programs.
        foreach ($this->userprograms as $programid => $users) {
            $program = new program($programid);
            foreach ($users as $userid) {
                $completionsettings = array(
                    'status'        => STATUS_PROGRAM_COMPLETE,
                    'timecreated'   => $timecreated[$userid],
                    'timestarted'   => $timestarted[$userid],
                    'timecompleted' => $timecompleted[$userid],
                );
                $program->update_program_complete($userid, $completionsettings);
            }
        }

        $program = new program($this->program3->id);
        $j = 1;
        foreach ($usersprogram3 as $userid) {
            if ($j <= 4) {
                $completionsettings = array(
                    'status' => STATUS_PROGRAM_COMPLETE,
                    'timecreated' => $timecreated[$userid],
                    'timestarted'   => $timestarted[$userid],
                    'timecompleted' => $timecompleted[$userid],
                );
            } else {
                $completionsettings = array(
                    'status' => STATUS_PROGRAM_INCOMPLETE,
                    'timecreated' => $timecreated[$userid],
                    'timestarted'   => $timestarted[$userid],
                    'timecompleted' => 0,
                );
            }
            $program->update_program_complete($userid, $completionsettings);
            $j++;
        }
        $program = new program($this->program4->id);
        $j = 1;
        foreach ($usersprogram3 as $userid) {
            if ($j <= 2) {
                $completionsettings = array(
                    'status' => STATUS_PROGRAM_COMPLETE,
                    'timecreated' => $timecreated[$userid],
                    'timestarted'   => $timestarted[$userid],
                    'timecompleted' => $timecompleted[$userid],
                );
            } else {
                $completionsettings = array(
                    'status' => STATUS_PROGRAM_INCOMPLETE,
                    'timecreated' => $timecreated[$userid],
                    'timestarted'   => $timestarted[$userid],
                    'timecompleted' => 0,
                );
            }
            $program->update_program_complete($userid, $completionsettings);
            $j++;
        }

        // Create a dynamic cohort.
        $this->cohort = $this->cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $this->assertTrue($DB->record_exists('cohort', array('id' => $this->cohort->id)));
        $this->assertEquals(0, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Create a ruleset.
        $this->ruleset = cohort_rule_create_ruleset($this->cohort->draftcollectionid);
        $this->assertEquals(0, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));
    }

    /**
     * Data provider for program completion date rule.
     */
    public function data_program_completion_date() {
        // NOTE: we cannot use $this->>timestart here because it is in different instance.
        $data = array(
            array(array('operator' => COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN, 'date' => 'yesterday'),  array('program1'), 1),
            array(array('operator' => COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN, 'date' => 'yesterday'), array('program1'), 2),
            array(array('operator' => COHORT_RULE_COMPLETION_OP_BEFORE_PAST_DURATION, 'date' => 1),  array('program2'), 4),
            array(array('operator' => COHORT_RULE_COMPLETION_OP_WITHIN_PAST_DURATION, 'date' => 3), array('program2'), 4),
            array(array('operator' => COHORT_RULE_COMPLETION_OP_WITHIN_FUTURE_DURATION, 'date' => 1), array('program2'), 1),
            array(array('operator' => COHORT_RULE_COMPLETION_OP_AFTER_FUTURE_DURATION, 'date' => 6), array('program2'), 2),
        );
        return $data;
    }

    /**
     * Test program completion date rule.
     * @dataProvider data_program_completion_date
     */
    public function test_programcompletion_date($params, $programs, $usercount) {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Process listofids.
        $listofids = array();
        foreach ($programs as $program) {
            $listofids[] = $this->{$program}->id;
        }

        if ($params['date'] === 'yesterday') {
            // NOTE: the test dates are wrong, we have to ask for a bit later than exactly one day ago.
            $params['date'] = $this->teststart - DAYSECS - HOURSECS;
        }

        /**
         * Program completion data per users in program2:
         * user3 -> time started: -1day  - time completed = +1 day(future)  - completion time: 2 days
         * user4 -> time started: -3days - time completed = +3 days(future) - completion time: 6 days
         * user5 -> time started: -5days - time completed = +5 days(future) - completion time: 10 days
         * user6 -> time started: -7days - time completed = +7 days(future) - completion time: 14 days
         * user7 -> time started: -9days - time completed = +9 days(future) - completion time: 18 days
         */
        if ($params['operator'] === COHORT_RULE_COMPLETION_OP_AFTER_FUTURE_DURATION) {
            // Make completion in the future to test this rule.
            $days = 1;
            $users = $this->userprograms[$this->program2->id];
            foreach ($users as $user) {
                $timecreated = $this->teststart - ($days * DAYSECS);
                $timecompleted = $this->teststart + ($days * DAYSECS);
                $program = new program($this->program2->id);
                $completionsettings = array(
                    'status'        => STATUS_PROGRAM_COMPLETE,
                    'timecreated'   => $timecreated,
                    'timecompleted' => $timecompleted
                );
                $program->update_program_complete($user, $completionsettings);
                $days= $days + 2;
            }
        }

        // Create a program completion date rule.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'learning', 'programcompletiondate', $params, $listofids, 'listofids');
        cohort_rules_approve_changes($this->cohort);

        // It should match:
        // 1. data1: 1 (users who complete program1 before yesterday).
        // 2. data2: 2 (users who have complete the list of programs after the date specified).
        // 3. data3: 4 (users who complete the program in the past 1 day).
        // 4. data4: 4 (users who finish program2 in within the past 3 days).
        // 5. data5: 1 (users who will finish program2 within the upcoming 1 days).
        // 6. data6: 2 (users who will finish program2 after the upcoming 6 days).
        $this->assertEquals($usercount, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));
    }

    /**
     * Data provider for program completion duration rule.
     */
    public function data_program_completion_assigned_duration() {
        $data = array(
            array(array('operator' => COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN, 'date' => 2),  array('program1'), 1),
            array(array('operator' => COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN, 'date' => 3), array('program1'), 2),
            array(array('operator' => COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN, 'date' => 2),  array('program1', 'program2'), 1),
            array(array('operator' => COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN, 'date' => 3), array('program2'), 2),
        );
        return $data;
    }

    /**
     * Test program completion duration rule.
     * @dataProvider data_program_completion_assigned_duration
     */
    public function test_programcompletion_assigned_duration($params, $programs, $usercount) {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Process listofids.
        $listofids = array();
        foreach ($programs as $program) {
            $listofids[] = $this->{$program}->id;
        }

        // Create a completion duration rule.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'learning', 'programcompletiondurationassigned', $params, $listofids, 'listofids');
        cohort_rules_approve_changes($this->cohort);

        // It should match:
        // 1. data1: 1 (users who have completed program1 in a period less than 2 days).
        // 2. data2: 2 (users who have completed program1 in a period less than 3 days).
        // 3. data3: 1 (users that had completed program1 and program2 within duration of more than 2 days).
        // 4. data4: 2 (users that had completed program2 in a period grater than 3 days).
        $this->assertEquals($usercount, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));
    }

    /**
     * Data provider for program completion uration rule.
     * Program completion data:
     */
    public function data_program_completion_started_duration() {
        $data = array(
            array(array('operator' => COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN, 'date' => 2),  array('program1'), 2),
            array(array('operator' => COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN, 'date' => 3), array('program1'), 3),
            array(array('operator' => COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN, 'date' => 1),  array('program1', 'program2'), 1),
            array(array('operator' => COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN, 'date' => 3), array('program2'), 3),
        );
        return $data;
    }

    /**
     * Test program completion duration rule.
     * @dataProvider data_program_completion_started_duration
     */
    public function test_programcompletion_started_duration($params, $programs, $usercount) {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Process listofids.
        $listofids = array();
        foreach ($programs as $program) {
            $listofids[] = $this->{$program}->id;
        }

        // Create a completion duration rule.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'learning', 'programcompletiondurationstarted', $params, $listofids, 'listofids');
        cohort_rules_approve_changes($this->cohort);

        // It should match:
        // 1. data1: 1 (users who have completed program1 in a period less than 2 days).
        // 2. data2: 2 (users who have completed program1 in a period less than 3 days).
        // 3. data3: 1 (users that had completed program1 and program2 within duration of more than 2 days).
        // 4. data4: 2 (users that had completed program2 in a period grater than 3 days).
        $this->assertEquals($usercount, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));
    }

    /**
     * Data provider for program completion list rule.
     */
    public function data_program_completion_list() {
        $data = array(
            // If user has NOT completed ANY of program
            array(array('operator' => COHORT_RULE_COMPLETION_OP_NONE),  array('program3', 'program4'), 5),
            // If user has completed ANY of program.
            array(array('operator' => COHORT_RULE_COMPLETION_OP_ANY), array('program3', 'program4'), 4),
            // If user has NOT completed ALL of program
            array(array('operator' => COHORT_RULE_COMPLETION_OP_NOTALL),  array('program3', 'program4'), 7),
            // If user has completed ALL of program.
            array(array('operator' => COHORT_RULE_COMPLETION_OP_ALL), array('program3', 'program4'), 2),
        );
        return $data;
    }

    /**
     * Test program completion list rule.
     * @dataProvider data_program_completion_list
     */
    public function test_programcompletion_list($params, $programs, $usercount) {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Process listofids.
        $listofids = array();
        foreach ($programs as $program) {
            $listofids[] = $this->{$program}->id;
        }

        // Create a program completion list rule.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'learning', 'programcompletionlist', $params, $listofids, 'listofids');
        cohort_rules_approve_changes($this->cohort);

        // It should match:
        // 1. data1: 5 (users that had not completed program3 or program4).
        // 2. data2: 4 (users who completed one of the programs).
        // 3. data3: 7 (users who have not completed all programs).
        // 4. data4: 2 (users who has completed both programs).
        $this->assertEquals($usercount, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));
    }
}
