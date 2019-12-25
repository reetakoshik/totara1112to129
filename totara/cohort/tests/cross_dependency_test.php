<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package totara_cohort
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/report/reportlib.php');
require_once("$CFG->dirroot/totara/cohort/lib.php");

/**
 * Test that dynamic cohorts that depend on another cohorts are processed in correct order
 */
class totara_cohort_cross_dependency_testcase extends advanced_testcase {
    /**
     * Test cohorts processing
     * Cohorts 1-5 each include only one rule that includes users with userX name, where X = 1-5
     * Cohort tree:
     * 23 -> 2,3
     * 14 -> 1,4
     * 15 -> 1,5
     * 123 -> 23,1,2
     * 1234 -> 23,14
     * 12345 -> 1234, 15
     */
    public function test_cohort_dependency() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/totara/cohort/lib.php');

        $this->resetAfterTest();

        $cohort1 = $this->create_username_cohort('1', 'user1');
        $cohort2 = $this->create_username_cohort('2', 'user2');
        $cohort3 = $this->create_username_cohort('3', 'user3');
        $cohort4 = $this->create_username_cohort('4', 'user4');
        $cohort5 = $this->create_username_cohort('5', 'user5');

        // Create cohorts that depend on another cohorts.
        $cohort23 = $this->create_cohort_cohort('23', [$cohort2->id, $cohort3->id]);
        $cohort14 = $this->create_cohort_cohort('14', [$cohort1->id, $cohort4->id]);
        $cohort15 = $this->create_cohort_cohort('15', [$cohort1->id, $cohort5->id]);
        $cohort123 = $this->create_cohort_cohort('123', [$cohort23->id, $cohort1->id, $cohort2->id]);
        $cohort1234 = $this->create_cohort_cohort('1234', [$cohort23->id, $cohort14->id]);
        $cohort12345 = $this->create_cohort_cohort('12345', [$cohort1234->id, $cohort15->id]);

        // Create ciruclar dependency on cohorts
        $gen = $this->getDataGenerator();
        /**
         * @var totara_cohort_generator $cohortgen
         */
        $cohortgen = $gen->get_plugin_generator('totara_cohort');

        // Cohort A (will depend on C)
        $cohorta = $gen->create_cohort(
            [
                'name' => 'Cohort A',
                'cohorttype' => cohort::TYPE_DYNAMIC
            ]
        );

        // Cohort B -> Cohort A, Cohort 1
        $cohortb = $this->create_cohort_cohort('B', [$cohorta->id, $cohort1->id]);

        // Cohort C -> Cohort B
        $cohortc = $this->create_cohort_cohort('C', [$cohortb->id]);

        // Cohort A -> Cohort C
        $cohortgen->create_cohort_rule_params(
            cohort_rule_create_ruleset($cohorta->draftcollectionid),
            'cohort',
            'cohortmember',
            ['incohort' => 1],
            [$cohortc->id],
            'cohortids'
        );
        cohort_rules_approve_changes($cohorta);

        // Create users after approving cohorts.
        $user1 = $gen->create_user(['username' => 'user1']);
        $user2 = $gen->create_user(['username' => 'user2']);
        $user3 = $gen->create_user(['username' => 'user3']);
        $user4 = $gen->create_user(['username' => 'user4']);
        $user5 = $gen->create_user(['username' => 'user5']);

        // Test that cohort selected in correct order and all included
        $allcohorts = $DB->get_records('cohort', array('cohorttype' => cohort::TYPE_DYNAMIC), 'name');
        $sortedcohorts = \totara_cohort\cohort_dependency_helper::order_cohorts($allcohorts);

        $this->assertCount(14, $sortedcohorts);

        // No dependencies
        $nodeps = array_map(
            function ($elem) {
                return $elem->name;
            },
            array_slice($sortedcohorts, 0, 5)
        );
        $this->assertContains('Cohort 1', $nodeps);
        $this->assertContains('Cohort 2', $nodeps);
        $this->assertContains('Cohort 3', $nodeps);
        $this->assertContains('Cohort 4', $nodeps);
        $this->assertContains('Cohort 5', $nodeps);

        // First level of dependency
        $firstdeps = array_map(
            function ($elem) {
                return $elem->name;
            },
            array_slice($sortedcohorts, 5, 6)
        );
        $this->assertContains('Cohort A', $firstdeps); // B
        $this->assertContains('Cohort C', $firstdeps); // A
        $this->assertContains('Cohort 23', $firstdeps); // 2, 3
        $this->assertContains('Cohort 14', $firstdeps); // 1, 4
        $this->assertContains('Cohort B', $firstdeps); // C
        $this->assertContains('Cohort 15', $firstdeps); // 1, 5

        // Second level
        $seconddeps = array_map(
            function ($elem) {
                return $elem->name;
            },
            array_slice($sortedcohorts, 11, 3)
        );
        $this->assertContains('Cohort 1234', $seconddeps); // 23, 14
        $this->assertContains('Cohort 12345', $seconddeps); // 1234, 15
        $this->assertContains('Cohort 123', $seconddeps); // 23, 1 ,2

        // Initially cohorts should be empty.
        $this->assertEmpty($DB->get_records('cohort_members'));

        // Test that cohort processed correctly
        $notrace = new \null_progress_trace();
        totara_cohort_check_and_update_dynamic_cohort_members(null, $notrace);

        $this->assert_users_in_cohort($cohort1, [$user1->id]);
        $this->assert_users_in_cohort($cohort2, [$user2->id]);
        $this->assert_users_in_cohort($cohort3, [$user3->id]);
        $this->assert_users_in_cohort($cohort4, [$user4->id]);
        $this->assert_users_in_cohort($cohort5, [$user5->id]);
        $this->assert_users_in_cohort($cohort23, [$user2->id, $user3->id]);
        $this->assert_users_in_cohort($cohort14, [$user1->id, $user4->id]);
        $this->assert_users_in_cohort($cohort15, [$user1->id, $user5->id]);
        $this->assert_users_in_cohort($cohort123, [$user1->id, $user2->id, $user3->id]);
        $this->assert_users_in_cohort($cohort1234, [$user1->id, $user2->id, $user3->id, $user4->id]);
        $this->assert_users_in_cohort($cohort12345, [$user1->id, $user2->id, $user3->id, $user4->id, $user5->id]);
        $this->assert_users_in_cohort($cohortb, [$user1->id]);

        // Cohort C and A - is known limitation. Due to circular dependency they will be addressed only on second and third run.
        totara_cohort_check_and_update_dynamic_cohort_members(null, $notrace);
        $this->assert_users_in_cohort($cohortc, [$user1->id]);
        totara_cohort_check_and_update_dynamic_cohort_members(null, $notrace);
        $this->assert_users_in_cohort($cohorta, [$user1->id]);

        // Also check that second run didn't break anything:
        $this->assert_users_in_cohort($cohort1, [$user1->id]);
        $this->assert_users_in_cohort($cohort2, [$user2->id]);
        $this->assert_users_in_cohort($cohort3, [$user3->id]);
        $this->assert_users_in_cohort($cohort4, [$user4->id]);
        $this->assert_users_in_cohort($cohort5, [$user5->id]);
        $this->assert_users_in_cohort($cohort23, [$user2->id, $user3->id]);
        $this->assert_users_in_cohort($cohort14, [$user1->id, $user4->id]);
        $this->assert_users_in_cohort($cohort15, [$user1->id, $user5->id]);
        $this->assert_users_in_cohort($cohort123, [$user1->id, $user2->id, $user3->id]);
        $this->assert_users_in_cohort($cohort1234, [$user1->id, $user2->id, $user3->id, $user4->id]);
        $this->assert_users_in_cohort($cohort12345, [$user1->id, $user2->id, $user3->id, $user4->id, $user5->id]);
        $this->assert_users_in_cohort($cohortb, [$user1->id]);
    }

    /**
     * Assert that exact users (and only those) are in cohort.
     * @param stdClass $cohort
     * @param array $userids
     */
    protected function assert_users_in_cohort(stdClass $cohort, array $userids) {
        global $DB;
        $this->assertEquals(
            count($userids),
            $DB->count_records('cohort_members', ['cohortid' => $cohort->id]),
            'There incorrect number of users in cohort ' . $cohort->name
        );
        foreach ($userids as $userid) {
            $this->assertTrue($DB->record_exists('cohort_members', ['cohortid' => $cohort->id, 'userid' => $userid]));
        }
    }

    /**
     * Create cohort that includes user by username
     * @param string $suffix
     * @param string $username
     * @return stdClass
     */
    protected function create_username_cohort(string $suffix, string $username): stdClass {
        $gen = $this->getDataGenerator();
        /**
         * @var totara_cohort_generator $cohortgen
         */
        $cohortgen = $gen->get_plugin_generator('totara_cohort');

        $cohort = $gen->create_cohort(
            [
                'name' => 'Cohort ' . $suffix,
                'cohorttype' => cohort::TYPE_DYNAMIC
            ]
        );
        $cohortgen->create_cohort_rule_params(
            cohort_rule_create_ruleset($cohort->draftcollectionid),
            'user',
            'username',
            ['equal' => COHORT_RULES_OP_IN_ISEQUALTO],
            [$username]
        );
        cohort_rules_approve_changes($cohort);
        return $cohort;
    }

    /**
     * Create cohort that consist of another cohorts
     * @param string $suffix
     * @param int[] $cohortids
     * @return stdClass
     */
    protected function create_cohort_cohort(string $suffix, array $cohortids): stdClass {
        $gen = $this->getDataGenerator();
        /**
         * @var totara_cohort_generator $cohortgen
         */
        $cohortgen = $gen->get_plugin_generator('totara_cohort');

        $cohort = $gen->create_cohort(
            [
                'name' => 'Cohort ' . $suffix,
                'cohorttype' => cohort::TYPE_DYNAMIC
            ]
        );
        $cohortgen->create_cohort_rule_params(
            cohort_rule_create_ruleset($cohort->draftcollectionid),
            'cohort',
            'cohortmember',
            ['incohort' => 1],
            $cohortids,
            'cohortids'
        );
        cohort_rules_approve_changes($cohort);
        return $cohort;
    }
}