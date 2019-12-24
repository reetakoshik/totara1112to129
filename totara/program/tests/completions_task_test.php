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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_program
*/

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the program completions task.
 */
class totara_program_completions_task_testcase extends advanced_testcase {

    /**
     * @var testing_data_generator
     */
    protected $generator;

    /**
     * @var totara_program_generator
     */
    protected $generator_program;

    protected function tearDown() {
        $this->generator = null;
        $this->generator_program = null;
        parent::tearDown();
    }

    /**
     * Prepares the environment prior to each test case.
     */
    public function setUp() {
        // Make each generator more easily accessible.
        $this->generator = $this->getDataGenerator();
        $this->generator_program = $this->generator->get_plugin_generator('totara_program');
    }

    /**
     * Tests the task still runs when only programs are disabled.
     */
    public function test_task_having_disabled_programs() {
        global $CFG;

        $this->assertFalse(totara_feature_disabled('programs'));

        $original = $CFG->enableprograms;
        $CFG->enableprograms = TOTARA_DISABLEFEATURE;

        $this->assertTrue(totara_feature_disabled('programs'));

        $task = new \totara_program\task\completions_task();
        $this->assertTrue($task->execute());

        $CFG->enableprograms = $original;
        $this->assertFalse(totara_feature_disabled('programs'));
        $this->assertTrue($task->execute());
    }

    /**
     * Tests the task still runs when only programs are disabled.
     */
    public function test_task_having_disabled_certifications() {
        global $CFG;

        $this->assertFalse(totara_feature_disabled('certifications'));

        $original = $CFG->enablecertifications;
        $CFG->enablecertifications = TOTARA_DISABLEFEATURE;

        $this->assertTrue(totara_feature_disabled('certifications'));

        $task = new \totara_program\task\completions_task();
        $this->assertTrue($task->execute());

        $CFG->enablecertifications = $original;
        $this->assertFalse(totara_feature_disabled('certifications'));
        $this->assertTrue($task->execute());
    }

    /**
     * Tests the task does not run when both programs and certifications have been disabled.
     */
    public function test_task_having_disabled_programs_and_certifications() {
        global $CFG;

        $this->assertFalse(totara_feature_disabled('programs'));
        $this->assertFalse(totara_feature_disabled('certifications'));

        $original_programs = $CFG->enableprograms;
        $original_certifications = $CFG->enablecertifications;
        $CFG->enableprograms = TOTARA_DISABLEFEATURE;
        $CFG->enablecertifications = TOTARA_DISABLEFEATURE;

        $this->assertTrue(totara_feature_disabled('programs'));
        $this->assertTrue(totara_feature_disabled('certifications'));

        $task = new \totara_program\task\completions_task();
        $this->assertFalse($task->execute());

        $CFG->enableprograms = $original_programs;
        $CFG->enablecertifications = $original_certifications;
        $this->assertFalse(totara_feature_disabled('programs'));
        $this->assertFalse(totara_feature_disabled('certifications'));
        $this->assertTrue($task->execute());
    }

    /**
     * Test the task with programs.
     */
    public function test_task_with_programs() {
        global $CFG, $DB;

        $this->resetAfterTest();

        $CFG->enablecompletion = true;

        // For this test I want the following initial state.
        //  - 2x programs
        //  - 2x coursesets in each program
        //  - 4x courses
        //  - 1x course per course set
        //  - 14x users
        //  - 6x users enrolled in each program
        //  - 3x users in "not started"
        //  - 2x users in "in progress"
        //  - 1x users in "complete"
        // The users complete the required courses
        // We run the task and verify that everyone is in the right state
        // Then we do the following:
        //  - 1x unassigned user gets assigned
        //  - 1x "not started" user completes the first course set in each program
        //  - 1x "not started" user completes both courses in the program
        //  - 1x "in progress" user in each program completes the final course to complete the program.
        // We run the task again.
        // Finally we check that we have:
        //  - 2x user in "not started" for each program
        //  - 2x user in "in progress" for each program
        //  - 3x user in "complete" for each program

        $program_one = $this->generator_program->create_program(['idnumber' => 'p1']);
        $program_two = $this->generator_program->create_program(['idnumber' => 'p2']);

        // Course assignments as follows:
        //  - program_one/courseset 1:   c1
        //  - program_one/courseset 2:   c3
        //  - program_two/courseset 1:   c2
        //  - program_two/courseset 2:   c4
        $courses = [];
        $modules = [];
        for ($i = 1; $i <= 6; $i++) {
            $idnumber = 'c'.$i;
            $courses[$idnumber] = $this->generator->create_course([
                'idnumber' => $idnumber,
                'enablecompletion' => COMPLETION_ENABLED,
                'completionstartonenrol' => 1,
                'completionprogressonview' => 1
            ], array('createsections' => true));
            $modules[$idnumber] = $this->prepare_course_completion_for_module_view($courses[$idnumber]);
        }
        $this->generator_program->add_courses_and_courseset_to_program($program_one, [ [$courses['c1']], [$courses['c3']] ]);
        $this->generator_program->add_courses_and_courseset_to_program($program_two, [ [$courses['c2']], [$courses['c4']] ]);

        // User assignments as follows:
        //  - unassigned:                  u1,u2
        //  - program_one/not started:     u3,u4,u5
        //  - program_one/in progress:     u6,u7
        //  - program_one/complete:        u8
        //  - program_two/not started:     u9,u10,u11
        //  - program_two/in progress:     u12,u13
        //  - program_two/complete:        u14
        $users = [];
        $useridmap = [];
        for ($i = 1; $i <= 14; $i++) {
            $idnumber = 'u'.$i;
            $users[$idnumber] = $this->generator->create_user(['idnumber' => $idnumber]);
            $useridmap[$idnumber] = $users[$idnumber]->id;
        }
        $this->generator_program->assign_program($program_one->id, [
            $useridmap['u3'], $useridmap['u4'], $useridmap['u5'],
            $useridmap['u6'], $useridmap['u7'],
            $useridmap['u8'],
        ]);
        $this->generator_program->assign_program($program_two->id, [
            $useridmap['u9'], $useridmap['u10'], $useridmap['u11'],
            $useridmap['u12'], $useridmap['u13'],
            $useridmap['u14'],
        ]);

        // Refresh the programs at this point to ensure they are up to new.
        $program_one = new program($program_one->id);
        $program_two = new program($program_two->id);

        $this->assertCount(2, $program_one->get_content()->get_course_sets());
        $this->assertCount(2, $program_two->get_content()->get_course_sets());

        // Enrol and complete the course for those in progress and complete.
        $this->access_and_complete_course($users['u6'], $courses['c1'], $modules['c1']);
        $this->access_and_complete_course($users['u7'], $courses['c1'], $modules['c1']);
        $this->access_and_complete_course($users['u8'], $courses['c1'], $modules['c1']);
        $this->access_and_complete_course($users['u12'], $courses['c2'], $modules['c2']);
        $this->access_and_complete_course($users['u13'], $courses['c2'], $modules['c2']);
        $this->access_and_complete_course($users['u14'], $courses['c2'], $modules['c2']);

        // Now complete those who should be complete.
        $this->check_courseset_complete_for_user($program_one, 1, $users['u7']);
        $this->check_courseset_complete_for_user($program_one, 1, $users['u8']);
        $this->check_courseset_complete_for_user($program_two, 1, $users['u13']);
        $this->check_courseset_complete_for_user($program_two, 1, $users['u14']);

        $this->access_and_complete_course($users['u8'], $courses['c3'], $modules['c3']);
        $this->access_and_complete_course($users['u14'], $courses['c4'], $modules['c4']);

        // Finally make sure that u14 has already completed the program.
        $this->check_courseset_complete_for_user($program_two, 2, $users['u14']);

        // SETUP COMPLETE
        $this->verify_users_assigned($program_one, $users, ['u3', 'u4', 'u5', 'u6', 'u7', 'u8']);
        $this->verify_users_assigned($program_two, $users, ['u9', 'u10', 'u11', 'u12', 'u13', 'u14']);

        // Note: You aren't in progress until you do something anymore.
        $this->verify_program_completion_state($program_one, $users, ['u8'], ['u6', 'u7']);
        $this->verify_program_completion_state($program_two, $users, ['u14'], ['u12', 'u13']);

        // Run for the first time and check that we are where we expect to be.
        $task = new \totara_program\task\completions_task();
        $this->assertTrue($task->execute());

        $this->verify_program_completion_state($program_one, $users, ['u8'], ['u6', 'u7']);
        $this->verify_program_completion_state($program_two, $users, ['u14'], ['u12', 'u13']);

        // START PROGRESSING USERS.

        // Assign the two new users.
        $this->generator_program->assign_program($program_one->id, [
            $useridmap['u1'], $useridmap['u3'], $useridmap['u4'], $useridmap['u5'],
            $useridmap['u6'], $useridmap['u7'],
            $useridmap['u8'],
        ]);
        $this->generator_program->assign_program($program_two->id, [
            $useridmap['u2'], $useridmap['u9'], $useridmap['u10'], $useridmap['u11'],
            $useridmap['u12'], $useridmap['u13'],
            $useridmap['u14'],
        ]);

        $this->verify_users_assigned($program_one, $users, ['u1', 'u3', 'u4', 'u5', 'u6', 'u7', 'u8']);
        $this->verify_users_assigned($program_two, $users, ['u2', 'u9', 'u10', 'u11', 'u12', 'u13', 'u14']);

        // Not started users progressing to in progress.
        $this->access_and_complete_course($users['u3'], $courses['c1'], $modules['c1']);
        $this->access_and_complete_course($users['u9'], $courses['c2'], $modules['c2']);
        $this->check_courseset_complete_for_user($program_one, 1, $users['u3']);

        // Not started users progress to complete.
        $this->access_and_complete_course($users['u4'], $courses['c1'], $modules['c1']);
        $this->access_and_complete_course($users['u10'], $courses['c2'], $modules['c2']);
        $this->check_courseset_complete_for_user($program_one, 1, $users['u4']);
        $this->check_courseset_complete_for_user($program_two, 1, $users['u10']);
        $this->access_and_complete_course($users['u4'], $courses['c3'], $modules['c3']);
        $this->access_and_complete_course($users['u10'], $courses['c4'], $modules['c4']);
        $this->check_courseset_complete_for_user($program_two, 2, $users['u10']);

        // In progress users to complete.
        $this->access_and_complete_course($users['u6'], $courses['c3'], $modules['c3']);
        $this->access_and_complete_course($users['u12'], $courses['c4'], $modules['c4']);
        $this->check_courseset_complete_for_user($program_two, 2, $users['u12']);

        $this->verify_program_completion_state($program_one, $users, ['u4', 'u6', 'u8'], ['u3', 'u7']);
        $this->verify_program_completion_state($program_two, $users, ['u10', 'u12', 'u14'], ['u9', 'u13']);

        // VERIFY FINAL POSITION.
        // Run for the second time and check that we are where we expect to be.
        $task = new \totara_program\task\completions_task();
        $this->assertTrue($task->execute());

        $this->verify_program_completion_state($program_one, $users, ['u4', 'u6', 'u8'], ['u3', 'u7']);
        $this->verify_program_completion_state($program_two, $users, ['u10', 'u12', 'u14'], ['u9', 'u13']);

        // Force the position back to incomplete for all assigned users.
        $DB->execute('UPDATE {prog_completion} SET status = :status, timestarted = 0', ['status' => STATUS_PROGRAM_INCOMPLETE]);

        $this->verify_program_completion_state($program_one, $users, [], []);
        $this->verify_program_completion_state($program_two, $users, [], []);

        // VERIFY FORCED POSITION FIXED.
        // Run for the second time and check that we are where we expect to be.
        $task = new \totara_program\task\completions_task();
        $this->assertTrue($task->execute());

        $this->verify_program_completion_state($program_one, $users, ['u4', 'u6', 'u8'], ['u3', 'u7']);
        $this->verify_program_completion_state($program_two, $users, ['u10', 'u12', 'u14'], ['u9', 'u13']);
    }

    /**
     * Test the task with programs.
     */
    public function test_task_with_certifications() {
        global $CFG, $DB;

        $this->resetAfterTest();

        $CFG->enablecompletion = true;

        // For this test I want the following initial state.
        //  - 2x programs
        //  - 2x coursesets in each program
        //  - 4x courses
        //  - 1x course per course set
        //  - 14x users
        //  - 6x users enrolled in each program
        //  - 3x users in "not started"
        //  - 2x users in "in progress"
        //  - 1x users in "complete"
        // The users complete the required courses
        // We run the task and verify that everyone is in the right state
        // Then we do the following:
        //  - 1x unassigned user gets assigned
        //  - 1x "not started" user completes the first course set in each program
        //  - 1x "not started" user completes both courses in the program
        //  - 1x "in progress" user in each program completes the final course to complete the program.
        // We run the task again.
        // Finally we check that we have:
        //  - 2x user in "not started" for each program
        //  - 2x user in "in progress" for each program
        //  - 3x user in "complete" for each program

        $certification_one_id = $this->generator_program->create_certification(['idnumber' => 'cert1']);
        $certification_two_id = $this->generator_program->create_certification(['idnumber' => 'cert2']);
        $certification_one = new program($certification_one_id);
        $certification_two = new program($certification_two_id);

        // Course assignments as follows:
        //  - program_one/courseset 1:   c1
        //  - program_one/courseset 2:   c3
        //  - program_two/courseset 1:   c2
        //  - program_two/courseset 2:   c4
        $courses = [];
        $modules = [];
        for ($i = 1; $i <= 6; $i++) {
            $idnumber = 'c'.$i;
            $courses[$idnumber] = $this->generator->create_course([
                'idnumber' => $idnumber,
                'enablecompletion' => COMPLETION_ENABLED,
                'completionstartonenrol' => 1,
                'completionprogressonview' => 1
            ], array('createsections' => true));
            $modules[$idnumber] = $this->prepare_course_completion_for_module_view($courses[$idnumber]);
        }
        $this->generator_program->add_courses_and_courseset_to_program($certification_one, [ [$courses['c1']], [$courses['c3']] ]);
        $this->generator_program->add_courses_and_courseset_to_program($certification_two, [ [$courses['c2']], [$courses['c4']] ]);

        // User assignments as follows:
        //  - unassigned:                  u1,u2
        //  - program_one/not started:     u3,u4,u5
        //  - program_one/in progress:     u6,u7
        //  - program_one/complete:        u8
        //  - program_two/not started:     u9,u10,u11
        //  - program_two/in progress:     u12,u13
        //  - program_two/complete:        u14
        $users = [];
        $useridmap = [];
        for ($i = 1; $i <= 14; $i++) {
            $idnumber = 'u'.$i;
            $users[$idnumber] = $this->generator->create_user(['idnumber' => $idnumber]);
            $useridmap[$idnumber] = $users[$idnumber]->id;
        }
        $this->generator_program->assign_program($certification_one->id, [
            $useridmap['u3'], $useridmap['u4'], $useridmap['u5'],
            $useridmap['u6'], $useridmap['u7'],
            $useridmap['u8'],
        ]);
        $this->generator_program->assign_program($certification_two->id, [
            $useridmap['u9'], $useridmap['u10'], $useridmap['u11'],
            $useridmap['u12'], $useridmap['u13'],
            $useridmap['u14'],
        ]);

        // Refresh the programs at this point to ensure they are up to new.
        $certification_one = new program($certification_one->id);
        $certification_two = new program($certification_two->id);

        $this->assertCount(2, $certification_one->get_content()->get_course_sets());
        $this->assertCount(2, $certification_two->get_content()->get_course_sets());

        // Enrol and complete the course for those in progress and complete.
        $this->access_and_complete_course($users['u6'], $courses['c1'], $modules['c1']);
        $this->access_and_complete_course($users['u7'], $courses['c1'], $modules['c1']);
        $this->access_and_complete_course($users['u8'], $courses['c1'], $modules['c1']);
        $this->access_and_complete_course($users['u12'], $courses['c2'], $modules['c2']);
        $this->access_and_complete_course($users['u13'], $courses['c2'], $modules['c2']);
        $this->access_and_complete_course($users['u14'], $courses['c2'], $modules['c2']);

        // Now complete those who should be complete.
        $this->check_courseset_complete_for_user($certification_one, 1, $users['u7']);
        $this->check_courseset_complete_for_user($certification_one, 1, $users['u8']);
        $this->check_courseset_complete_for_user($certification_two, 1, $users['u13']);
        $this->check_courseset_complete_for_user($certification_two, 1, $users['u14']);

        $this->access_and_complete_course($users['u8'], $courses['c3'], $modules['c3']);
        $this->access_and_complete_course($users['u14'], $courses['c4'], $modules['c4']);

        // Finally make sure that u14 has already completed the program.
        $this->check_courseset_complete_for_user($certification_two, 2, $users['u14']);

        // SETUP COMPLETE
        $this->verify_users_assigned($certification_one, $users, ['u3', 'u4', 'u5', 'u6', 'u7', 'u8']);
        $this->verify_users_assigned($certification_two, $users, ['u9', 'u10', 'u11', 'u12', 'u13', 'u14']);

        // Note: You aren't in progress until you do something anymore.
        $this->verify_program_completion_state($certification_one, $users, ['u8'], ['u6', 'u7']);
        $this->verify_program_completion_state($certification_two, $users, ['u14'], ['u12', 'u13']);

        $this->verify_certification_completion_state($certification_one, $users, ['u3', 'u4', 'u5'], ['u6', 'u7'], ['u8'], [], []);
        $this->verify_certification_completion_state($certification_two, $users, ['u9', 'u10', 'u11'], ['u12', 'u13'], ['u14'], [], []);

        // Run for the first time and check that we are where we expect to be.
        $task = new \totara_program\task\completions_task();
        $this->assertTrue($task->execute());

        $this->verify_program_completion_state($certification_one, $users, ['u8'], ['u6', 'u7']);
        $this->verify_program_completion_state($certification_two, $users, ['u14'], ['u12', 'u13']);

        $this->verify_certification_completion_state($certification_one, $users, ['u3', 'u4', 'u5'], ['u6', 'u7'], ['u8'], [], []);
        $this->verify_certification_completion_state($certification_two, $users, ['u9', 'u10', 'u11'], ['u12', 'u13'], ['u14'], [], []);

        // START PROGRESSING USERS.

        // Assign the two new users.
        $this->generator_program->assign_program($certification_one->id, [
            $useridmap['u1'], $useridmap['u3'], $useridmap['u4'], $useridmap['u5'],
            $useridmap['u6'], $useridmap['u7'],
            $useridmap['u8'],
        ]);
        $this->generator_program->assign_program($certification_two->id, [
            $useridmap['u2'], $useridmap['u9'], $useridmap['u10'], $useridmap['u11'],
            $useridmap['u12'], $useridmap['u13'],
            $useridmap['u14'],
        ]);

        $this->verify_users_assigned($certification_one, $users, ['u1', 'u3', 'u4', 'u5', 'u6', 'u7', 'u8']);
        $this->verify_users_assigned($certification_two, $users, ['u2', 'u9', 'u10', 'u11', 'u12', 'u13', 'u14']);

        // Not started users progressing to in progress.
        $this->access_and_complete_course($users['u3'], $courses['c1'], $modules['c1']);
        $this->access_and_complete_course($users['u9'], $courses['c2'], $modules['c2']);
        $this->check_courseset_complete_for_user($certification_one, 1, $users['u3']);

        // Not started users progress to complete.
        $this->access_and_complete_course($users['u4'], $courses['c1'], $modules['c1']);
        $this->access_and_complete_course($users['u10'], $courses['c2'], $modules['c2']);
        $this->check_courseset_complete_for_user($certification_one, 1, $users['u4']);
        $this->check_courseset_complete_for_user($certification_two, 1, $users['u10']);
        $this->access_and_complete_course($users['u4'], $courses['c3'], $modules['c3']);
        $this->access_and_complete_course($users['u10'], $courses['c4'], $modules['c4']);
        $this->check_courseset_complete_for_user($certification_two, 2, $users['u10']);

        // In progress users to complete.
        $this->access_and_complete_course($users['u6'], $courses['c3'], $modules['c3']);
        $this->access_and_complete_course($users['u12'], $courses['c4'], $modules['c4']);
        $this->check_courseset_complete_for_user($certification_two, 2, $users['u12']);

        $this->verify_program_completion_state($certification_one, $users, ['u4', 'u6', 'u8'], ['u3', 'u7']);
        $this->verify_program_completion_state($certification_two, $users, ['u10', 'u12', 'u14'], ['u9', 'u13']);

        $this->verify_certification_completion_state($certification_one, $users, ['u1', 'u5'], ['u3', 'u7'], ['u4', 'u6', 'u8'], [], []);
        $this->verify_certification_completion_state($certification_two, $users, ['u2', 'u11'], ['u9', 'u13'], ['u10', 'u12', 'u14'], [], []);

        // VERIFY FINAL POSITION.
        // Run for the second time and check that we are where we expect to be.
        $task = new \totara_program\task\completions_task();
        $this->assertTrue($task->execute());

        $this->verify_certification_completion_state($certification_one, $users, ['u1', 'u5'], ['u3', 'u7'], ['u4', 'u6', 'u8'], [], []);
        $this->verify_certification_completion_state($certification_two, $users, ['u2', 'u11'], ['u9', 'u13'], ['u10', 'u12', 'u14'], [], []);
    }

    /**
     * Verifies the users are assigned to this program.
     *
     * @param program $program
     * @param stdClass[] $users
     * @param string[] $assigned An array of assigned user idnumbers.
     */
    private function verify_users_assigned(program $program, array &$users, array $assigned) {
        foreach ($users as $idnumber => $user) {
            if (in_array($idnumber, $assigned)) {
                $this->assertTrue($program->user_is_assigned($user->id), 'User "'.$idnumber.'" is not assigned to "'.$program->idnumber.'"');
            } else {
                $this->assertFalse($program->user_is_assigned($user->id), 'User "'.$idnumber.'" is unexpectedly assigned to "'.$program->idnumber.'"');
            }
        }
    }

    /**
     * Verifies the users are in the expected states.
     *
     * @param program $program
     * @param stdClass[] $users
     * @param string[] $complete
     * @param string[] $inprogress
     */
    private function verify_certification_completion_state(program $certification, array &$users, array $assigned_not_started, array $assigned_in_progress, array $certified, array $certified_recertifying, array $expired) {
        global $DB;

        foreach ($users as $idnumber => $user) {

            $state = CERTIFCOMPLETIONSTATE_INVALID;
            $status = CERTIFSTATUS_UNSET;

            $sql = 'SELECT cc.*
                      FROM {certif_completion} cc
                      JOIN {certif} c ON c.id = cc.certifid
                      JOIN {prog} p ON p.certifid = c.id
                      JOIN {user} u ON u.id = cc.userid
                     WHERE p.id = :programid
                       AND u.id = :userid';
            $params = ['programid' => $certification->id, 'userid' => $user->id];

            $record = $DB->get_record_sql($sql, $params, IGNORE_MISSING);
            if ($record) {
                $state = certif_get_completion_state($record);
                $status = $record->status;
            }

            switch ($state) {

                case CERTIFCOMPLETIONSTATE_ASSIGNED:
                    if (!in_array($idnumber, $assigned_not_started) && !in_array($idnumber, $assigned_in_progress)) {
                        $this->assertFalse(true, "User '$idnumber' is assigned, but was not expected to be assigned");
                    }
                    if ($status == CERTIFSTATUS_INPROGRESS && !in_array($idnumber, $assigned_in_progress)) {
                        $this->assertFalse(true, "User '$idnumber' is assigned and in progress, but was not expected to be in progress");
                    }
                    if ($status != CERTIFSTATUS_INPROGRESS && !in_array($idnumber, $assigned_not_started)) {
                        $this->assertFalse(true, "User '$idnumber' is assigned but not started, but was expected to be in progress");
                    }
                    break;
                case CERTIFCOMPLETIONSTATE_CERTIFIED:
                    if (!in_array($idnumber, $certified)) {
                        $this->assertFalse(true, "User '$idnumber' is certified, but was not expected to be certified");
                    }
                    if ($status == CERTIFSTATUS_INPROGRESS) {
                        $this->assertFalse(true, "User '$idnumber' is certified and is unexpectedly recertifying");
                    }
                    break;
                case CERTIFCOMPLETIONSTATE_WINDOWOPEN:
                    if (!in_array($idnumber, $certified_recertifying)) {
                        $this->assertFalse(true, "User '$idnumber' is recertifying, but was not expected to be recertifying");
                    }
                    break;
                case CERTIFCOMPLETIONSTATE_EXPIRED:
                    if (!in_array($idnumber, $expired)) {
                        $this->assertFalse(true, "User '$idnumber' is expired, but was not expected to be expired");
                    }
                    break;
                case CERTIFCOMPLETIONSTATE_INVALID:
                default:
                    if (in_array($idnumber, $assigned_not_started) || in_array($idnumber, $assigned_in_progress) || in_array($idnumber, $certified) || in_array($idnumber, $certified_recertifying) || in_array($idnumber, $expired)) {
                        $this->assertFalse(true, "User '$idnumber' was specified in a state, but was found to be invalid");
                    }
                    break;
            }
        }
    }

    /**
     * Verifies the users are in the expected states.
     *
     * @param program $program
     * @param stdClass[] $users
     * @param string[] $complete
     * @param string[] $inprogress
     */
    private function verify_program_completion_state(program $program, array &$users, array $complete, array $inprogress) {
        foreach ($users as $idnumber => $user) {
            if (in_array($idnumber, $complete)) {
                $this->assertTrue(prog_is_complete($program->id, $user->id), 'User "'.$idnumber.'" is not complete for "'.$program->idnumber.'"');
                $this->assertFalse(prog_is_inprogress($program->id, $user->id), 'User "'.$idnumber.'" is unexpectedly in progress in "'.$program->idnumber.'"');
            } else if (in_array($idnumber, $inprogress)) {
                $this->assertFalse(prog_is_complete($program->id, $user->id), 'User "'.$idnumber.'" is unexpectedly complete for "'.$program->idnumber.'"');
                $this->assertTrue(prog_is_inprogress($program->id, $user->id), 'User "'.$idnumber.'" is not in progress in "'.$program->idnumber.'"');
            } else {
                $this->assertFalse(prog_is_complete($program->id, $user->id), 'User "'.$idnumber.'" is unexpectedly complete for "'.$program->idnumber.'"');
                $this->assertFalse(prog_is_inprogress($program->id, $user->id), 'User "'.$idnumber.'" is unexpectedly in progress in "'.$program->idnumber.'"');
            }
        }
    }

    /**
     * Marks the user as complete in the course and assert it was done successfully.
     *
     * @param stdClass $user
     * @param stdClass $course
     */
    private function access_and_complete_course(stdClass $user, stdClass $course, array $activity) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/certificate/locallib.php');

        $module = $activity['module'];
        $cm = $activity['cm'];

        // First enrol.
        $result = prog_can_enter_course($user, $course);
        $this->assertObjectHasAttribute('enroled', $result);
        $this->assertTrue($result->enroled, 'User "'.$user->idnumber.'" could not be enroled in "'.$course->idnumber.'"');

        // Create a certificate for the user - this replicates a user going to mod/certificate/view.php.
        certificate_get_issue($course, $user, $module, $cm);
        $params = array('userid' => $user->id, 'coursemoduleid' => $cm->id);

        // Check it isn't complete.
        $completionstate = $DB->get_field('course_modules_completion', 'completionstate', $params);
        $this->assertEmpty($completionstate);

        $compparams = array('userid' => $user->id, 'course' => $course->id);
        $completion = new completion_completion($compparams);
        $completion->mark_inprogress();

        // Complete the certificate.
        $completion = new completion_info($course);
        $completion->set_module_viewed($cm, $user->id);

        // Check its completed.
        $completionstate = $DB->get_field('course_modules_completion', 'completionstate', $params, MUST_EXIST);
        $this->assertEquals(COMPLETION_COMPLETE, $completionstate);

        \completion_criteria_activity::invalidatecache();
        require_once('completion/cron.php');

        // Confirm the course is now complete.
        $params = array('userid' => $user->id, 'course' => $course->id);
        $completion = new completion_completion($params);
        $this->assertTrue($completion->is_complete());
    }

    private function check_courseset_complete_for_user(program $program, $coursesetnumber, stdClass $user) {
        // Lets test completing the first course set.
        // Now we want to mark the incomplete user complete in courses in the first courseset.
        $coursesets = $program->get_content()->get_course_sets();
        foreach ($coursesets as $courseset) {
            /** @var multi_course_set $courseset */
            if ($courseset->label === "Course Set {$coursesetnumber}") {
                $courseset->check_courseset_complete($user->id);
                return true;
            }
        }
        throw new coding_exception('Attempting to check the completion of a course set that does not exist.', $coursesetnumber);
    }

    /**
     * Prepares the course for completion by viewing a module.
     *
     * @param stdClass $course
     * @return array
     */
    private function prepare_course_completion_for_module_view($course) {
        global $CFG;

        require_once($CFG->dirroot . '/completion/criteria/completion_criteria.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_activity.php');

        // Handle course aggregation.
        $aggdata = array(
            'course'        => $course->id,
            'criteriatype'  => COMPLETION_CRITERIA_TYPE_ACTIVITY
        );
        $aggregation = new completion_aggregation($aggdata);
        $aggregation->setMethod(COMPLETION_AGGREGATION_ANY);
        $aggregation->save();

        $completion = new completion_info($course);

        // Assign a certificate activity to each course. Could be any other activity. It's necessary for the criteria completion.
        $module = $this->generator->create_module(
            'certificate',
            ['course' => $course->id],
            [
                'completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completionview' => COMPLETION_VIEW_REQUIRED
            ]
        );
        $cm = get_coursemodule_from_instance('certificate', $module->id, $course->id);
        $this->assertEquals(COMPLETION_TRACKING_AUTOMATIC, $completion->is_enabled($cm));

        $data = new stdClass;
        $data->id = $course->id;
        $data->criteria_activity_value = array($cm->id => 1);
        $data->module = 'certificate';
        $criterion = new completion_criteria_activity();
        $criterion->update_config($data);

        $this->assertCount(1, $completion->get_criteria(), 'Failed to create an activity completion criteria for the course.');

        return ['module' => $module, 'cm' => $cm];
    }

    public function test_prog_mark_started() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/lib/completionlib.php');

        $CFG->enablecompletion = true;
        $this->resetAfterTest();

        // Create a couple of users.
        $users = [];
        for ($i = 1; $i <= 2; $i++) {
            $idnumber = 'u'.$i;
            $users[$idnumber] = $this->generator->create_user(['idnumber' => $idnumber]);
        }

        // Create a course.
        $course = $this->generator->create_course([
            'idnumber' => 'c1',
            'enablecompletion' => COMPLETION_ENABLED,
            'completionstartonenrol' => 1,
            'completionprogressonview' => 1
        ], array('createsections' => true));
        $module = $this->prepare_course_completion_for_module_view($course);

        // Add the courses to a program and assign some users.
        $prog = $this->generator_program->create_program(['idnumber' => 'p1']);
        $this->generator_program->add_courses_and_courseset_to_program($prog, [[$course]]);
        $this->generator_program->assign_program($prog->id, [$users['u1']->id, $users['u2']->id]);

        $progcomps = $DB->get_records('prog_completion', array('userid' => $users['u1']->id));
        foreach ($progcomps as $progcomp) {
            $this->assertEmpty($progcomp->timestarted);
        }

        // Start the course and check the timestarted has been set.
        $this->access_and_complete_course($users['u1'], $course, $module);
        $crscomp1 = $DB->get_record('course_completions', array('userid' => $users['u1']->id, 'course' => $course->id));
        $progcomps = $DB->get_records('prog_completion', array('userid' => $users['u1']->id));
        foreach ($progcomps as $progcomp) {
            $this->assertGreaterThanOrEqual($crscomp1->timestarted, $progcomp->timestarted);
        }

        $progcomps = $DB->get_records('prog_completion', array('userid' => $users['u2']->id));
        foreach ($progcomps as $progcomp) {
            $this->assertEquals(0, $progcomp->timestarted);
        }

        // Enrol the second user and set them to in progress, with a different timestarted.
        $result = prog_can_enter_course($users['u2'], $course);
        $this->assertObjectHasAttribute('enroled', $result);
        $this->assertTrue($result->enroled, "User({$users['u2']->idnumber}) could not be enroled in course({$course->idnumber})");

        $crscomp2 = $DB->get_record('course_completions', array('userid' => $users['u2']->id));
        $crscomp2->timestarted = 1234567890;
        $DB->update_record('course_completions', $crscomp2);
        cache_helper::purge_all(); // The completions cache causes some problems with manual edits here.

        // Run the task and check the cron has put the new time in the timestarted.
        $task = new \totara_program\task\completions_task();
        $this->assertTrue($task->execute());

        $progcomps = $DB->get_records('prog_completion', array('userid' => $users['u1']->id));
        foreach ($progcomps as $progcomp) {
            $this->assertGreaterThanOrEqual($crscomp1->timestarted, $progcomp->timestarted);
        }

        $progcomps = $DB->get_records('prog_completion', array('userid' => $users['u2']->id));
        foreach ($progcomps as $progcomp) {
            $this->assertGreaterThanOrEqual($crscomp2->timestarted, $progcomp->timestarted);
        }
    }
}
