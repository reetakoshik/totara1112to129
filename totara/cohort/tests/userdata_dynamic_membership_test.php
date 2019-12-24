<?php
/*
 * This file is part of Totara Learn
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_cohort
 */

defined('MOODLE_INTERNAL') || die();

use totara_cohort\userdata\dynamic_membership;
use totara_userdata\userdata\target_user;

/**
 * Class totara_cohort_userdata_dynamic_membership_testcase
 *
 * Tests functionality of the \totara_cohort\userdata\dynamic_membership class.
 *
 * More comprehensive tests are done in the userdata_set_membership_testcase, which covers functionality shared
 * between set and dynamic audience items (via the membership_base class).
 *
 * @group totara_userdata
 */
class totara_cohort_userdata_dynamic_membership_testcase extends advanced_testcase {

    /**
     * Confirm that no errors are thrown and that correct data is returned when no audiences (or memberships) exist
     * within the system.
     */
    public function test_with_no_data() {
        $this->resetAfterTest(true);
        global $DB;

        $user = $this->getDataGenerator()->create_user();

        $export = dynamic_membership::execute_export(
            new target_user($user),
            context_system::instance()
        );
        $this->assertEmpty($export->files);
        $this->assertEmpty($export->data);

        $count = dynamic_membership::execute_count(
            new target_user($user),
            context_system::instance()
        );
        $this->assertEquals(0, $count);

        $result = dynamic_membership::execute_purge(
            new target_user($user),
            context_system::instance()
        );
        $this->assertEquals(dynamic_membership::RESULT_STATUS_SUCCESS, $result);

        // Do it again using a category context.
        // The first category will the Miscellaneous category which is on a site by default.
        $miscellaneous_context = context_coursecat::instance($DB->get_field('course_categories', 'id', []));

        $export = dynamic_membership::execute_export(
            new target_user($user),
            $miscellaneous_context
        );
        $this->assertEmpty($export->files);
        $this->assertEmpty($export->data);

        $count = dynamic_membership::execute_count(
            new target_user($user),
            $miscellaneous_context
        );
        $this->assertEquals(0, $count);

        $result = dynamic_membership::execute_purge(
            new target_user($user),
            $miscellaneous_context
        );
        $this->assertEquals(dynamic_membership::RESULT_STATUS_SUCCESS, $result);
    }

    /**
     * As some different steps need to be taken when purging membership within dynamic audiences, we run a separate
     * test for these.
     */
    public function test_with_rulesets() {
        $this->resetAfterTest(true);
        global $CFG;
        require_once($CFG->dirroot . '/totara/cohort/lib.php');

        $user1 = $this->getDataGenerator()->create_user(['firstname' => 'John', 'lastname' => 'Jones']);
        $user2 = $this->getDataGenerator()->create_user(['firstname' => 'Joanne', 'lastname' => 'Jones']);

        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();

        $category1context = context_coursecat::instance($category1->id);
        $category2context = context_coursecat::instance($category2->id);

        /* @var totara_cohort_generator $cohortgenerator */
        $cohortgenerator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');

        $category1_audience = $this->getDataGenerator()->create_cohort(
            ['contextid' => $category1context->id, 'cohorttype' => cohort::TYPE_DYNAMIC, 'name' => 'cat1 aud1']
        );
        $ruleset1 = cohort_rule_create_ruleset($category1_audience->draftcollectionid);
        $cohortgenerator->create_cohort_rule_params(
            $ruleset1,
            'user',
            'firstname',
            ['equal' => COHORT_RULES_OP_IN_CONTAINS],
            ['Jo']
        );
        cohort_rules_approve_changes($category1_audience);

        $category2_audience = $this->getDataGenerator()->create_cohort(
            ['contextid' => $category2context->id, 'cohorttype' => cohort::TYPE_DYNAMIC, 'name' => 'cat2 aud1']
        );
        $ruleset2 = cohort_rule_create_ruleset($category2_audience->draftcollectionid);
        $cohortgenerator->create_cohort_rule_params(
            $ruleset2,
            'user',
            'lastname',
            ['equal' => COHORT_RULES_OP_IN_ISEQUALTO],
            ['Jones']
        );
        cohort_rules_approve_changes($category2_audience);

        $category1_members = totara_get_members_cohort($category1_audience->id);
        $this->assertCount(2, $category1_members);

        $category2_members = totara_get_members_cohort($category2_audience->id);
        $this->assertCount(2, $category2_members);

        // Test export. Just in category context is fine. Testing the underlying logic getting audience memberships
        // is done in the tests for set audiences.
        $export = dynamic_membership::execute_export(
            new target_user($user1),
            $category1context
        );
        $this->assertEmpty($export->files);
        $this->assertCount(1, $export->data);
        $record = reset($export->data);
        $this->assertEquals($user1->id, $record->userid);
        $this->assertEquals($category1_audience->id, $record->cohortid);
        $this->assertEquals('cat1 aud1', $record->name);

        // Test count.
        $count = dynamic_membership::execute_count(
            new target_user($user1),
            $category1context
        );
        $this->assertEquals(1, $count);

        $eventsink = $this->redirectEvents();

        // Just apply the purge on category1 for user1.
        $result = dynamic_membership::execute_purge(
            new target_user($user1),
            $category1context
        );

        $this->assertEquals(1, $eventsink->count());
        $this->assertContainsOnlyInstancesOf(\totara_cohort\event\members_updated::class, $eventsink->get_events());

        $this->assertEquals(dynamic_membership::RESULT_STATUS_SUCCESS, $result);

        $category1_members = totara_get_members_cohort($category1_audience->id);
        $this->assertCount(1, $category1_members);
        $this->assertEquals($user2->id, reset($category1_members)->userid);

        $category2_members = totara_get_members_cohort($category2_audience->id);
        $this->assertCount(2, $category2_members);

        $eventsink->clear();

        // Now let's test the purge with the system context. This time on user 2.
        $result = dynamic_membership::execute_purge(
            new target_user($user2),
            context_system::instance()
        );

        $this->assertEquals(2, $eventsink->count());
        $this->assertContainsOnlyInstancesOf(\totara_cohort\event\members_updated::class, $eventsink->get_events());
        $eventsink->close();

        $this->assertEquals(dynamic_membership::RESULT_STATUS_SUCCESS, $result);

        $category1_members = totara_get_members_cohort($category1_audience->id);
        $this->assertCount(0, $category1_members);

        $category2_members = totara_get_members_cohort($category2_audience->id);
        $this->assertCount(1, $category2_members);
        $this->assertEquals($user1->id, reset($category2_members)->userid);

        // If no other changes were made to the above users or the audience rules, the user's
        // will be added back to the dynamic audiences when cron runs an update of the dynamic audiences.
        // That's fine and expected. Let's even run the function that updates dynamic audience membership.

        totara_cohort_update_dynamic_cohort_members($category1_audience->id);

        $category1_members = totara_get_members_cohort($category1_audience->id);
        $this->assertCount(2, $category1_members);

        totara_cohort_update_dynamic_cohort_members($category2_audience->id);

        $category2_members = totara_get_members_cohort($category2_audience->id);
        $this->assertCount(2, $category2_members);
    }
}