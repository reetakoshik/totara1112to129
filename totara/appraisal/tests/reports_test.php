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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_appraisal
 */

global $CFG;
require_once($CFG->dirroot . '/totara/appraisal/tests/appraisal_testcase.php');

class test_appraisal_reports extends appraisal_testcase {

    /**
     * Test with no complete users.
     */
    public function test_get_active_with_stats_none_complete() {
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $users = array($user1, $user2, $user3);

        list($appraisal) = $this->prepare_appraisal_with_users(array(), $users);

        list($errors, $warnings) = $appraisal->validate();

        $this->assertEmpty($errors);
        $this->assertEmpty($warnings);

        $appraisal->activate();

        $stats = $appraisal->get_active_with_stats();
        $stats = reset($stats);

        // No users have completed an appraisal so we should have 3 incomplete appraisals.
        $this->assertEquals(3, $appraisal->count_incomplete_userassignments());

        // Check all the values are as expected.
        $this->assertEquals(3, $stats->userstotal);
        $this->assertEmpty($stats->userscomplete);
        $this->assertEmpty($stats->userscancelled);
        $this->assertEmpty($stats->usersoverdue);
    }

    /**
     * Test with several complete users.
     */
    public function test_get_active_with_stats_completed() {
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $users = array($user1, $user2, $user3);

        list($appraisal) = $this->prepare_appraisal_with_users(array(), $users);

        list($errors, $warnings) = $appraisal->validate();

        $this->assertEmpty($errors);
        $this->assertEmpty($warnings);

        $appraisal->activate();

        // Complete for user1.
        $roleassignment1 = appraisal_role_assignment::get_role($appraisal->id, $users[0]->id, $users[0]->id,
                appraisal::ROLE_LEARNER);
        $this->answer_question($appraisal, $roleassignment1, 0, 'completestage');

        // Complete for user2.
        $roleassignment2 = appraisal_role_assignment::get_role($appraisal->id, $users[1]->id, $users[1]->id,
                appraisal::ROLE_LEARNER);
        $this->answer_question($appraisal, $roleassignment2, 0, 'completestage');

        $stats = $appraisal->get_active_with_stats();
        $stats = reset($stats);

        // Two users have completed an appraisal so we should have 1 incomplete appraisal.
        $this->assertEquals(1, $appraisal->count_incomplete_userassignments());

        // Check all the values are as expected.
        $this->assertEquals(3, $stats->userstotal);
        $this->assertEquals(2, $stats->userscomplete);
        $this->assertEmpty($stats->userscancelled);
        $this->assertEmpty($stats->usersoverdue);
    }


    /**
     * Testing with dynamic appraisals
     */
    public function test_get_active_with_stats_dynamic() {
        global $CFG, $DB;

        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $users = array($user1, $user2, $user3);

        // Enable dynamic appraisals.
        $CFG->dynamicappraisals = 1;

        list($appraisal) = $this->prepare_appraisal_with_users(array(), $users);

        list($errors, $warnings) = $appraisal->validate();

        $this->assertEmpty($errors);
        $this->assertEmpty($warnings);

        $appraisal->activate();

        // Complete for user2.
        $roleassignment2 = appraisal_role_assignment::get_role($appraisal->id, $users[1]->id, $users[1]->id,
                appraisal::ROLE_LEARNER);
        $this->answer_question($appraisal, $roleassignment2, 0, 'completestage');

        // Get stats.
        $stats = $appraisal->get_active_with_stats();
        $stats = reset($stats);

        // Check all the values are as expected before we remove the user.
        $this->assertEquals(3, $stats->userstotal);
        $this->assertEquals(1, $stats->userscomplete);
        $this->assertEquals(0, $stats->userscancelled);
        $this->assertEmpty($stats->usersoverdue);

        // Remove user from audience.
        $cohort = $DB->get_records('cohort');
        $cohort = reset($cohort);
        cohort_remove_member($cohort->id, $user2->id);

        // Update appraisal members with audience changes.
        $appraisal->check_assignment_changes();

        // Get stats.
        $stats = $appraisal->get_active_with_stats();
        $stats = reset($stats);

        // Check all the values are as expected still.
        $this->assertEquals(3, $stats->userstotal);
        $this->assertEquals(0, $stats->userscomplete);
        $this->assertEquals(1, $stats->userscancelled);
        $this->assertEmpty($stats->usersoverdue);
    }
}
