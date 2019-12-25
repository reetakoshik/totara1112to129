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

use totara_cohort\userdata\set_membership;
use totara_userdata\userdata\target_user;

/**
 * Class totara_cohort_userdata_set_membership_testcase
 *
 * Tests functionality of the \totara_cohort\userdata\set_membership class.
 *
 * @group totara_userdata
 */
class totara_cohort_userdata_set_membership_testcase extends advanced_testcase {

    /**
     * Confirm that no errors are thrown and that correct data is returned when no audiences (or memberships) exist
     * within the system.
     */
    public function test_with_no_data() {
        $this->resetAfterTest(true);
        global $DB;

        $user = $this->getDataGenerator()->create_user();

        $export = set_membership::execute_export(
            new target_user($user),
            context_system::instance()
        );
        $this->assertEmpty($export->files);
        $this->assertEmpty($export->data);

        $count = set_membership::execute_count(
            new target_user($user),
            context_system::instance()
        );
        $this->assertEquals(0, $count);

        $result = set_membership::execute_purge(
            new target_user($user),
            context_system::instance()
        );
        $this->assertEquals(set_membership::RESULT_STATUS_SUCCESS, $result);

        // Do it again using a category context.
        // The first category will the Miscellaneous category which is on a site by default.
        $miscellaneous_context = context_coursecat::instance($DB->get_field('course_categories', 'id', []));

        $export = set_membership::execute_export(
            new target_user($user),
            $miscellaneous_context
        );
        $this->assertEmpty($export->files);
        $this->assertEmpty($export->data);

        $count = set_membership::execute_count(
            new target_user($user),
            $miscellaneous_context
        );
        $this->assertEquals(0, $count);

        $result = set_membership::execute_purge(
            new target_user($user),
            $miscellaneous_context
        );
        $this->assertEquals(set_membership::RESULT_STATUS_SUCCESS, $result);
    }

    /**
     * Create users, categories, audiences and assign users to audiences.
     *
     * - Only set audiences are created in this method.
     * - Roles are assigned to the audiences and assertions ensure that these are assigned to the audience members.
     *
     * @return array of data created.
     */
    private function create_audience_data() {
        global $DB;

        $this->setAdminUser();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();

        $systemcontext = context_system::instance();
        $category1context = context_coursecat::instance($category1->id);
        $category2context = context_coursecat::instance($category2->id);

        $system_audience = $this->getDataGenerator()->create_cohort(['name' => 'sys aud1']);
        $category1_audience = $this->getDataGenerator()->create_cohort(['contextid' => $category1context->id, 'name' => 'cat1 aud1']);
        $category2_audience1 = $this->getDataGenerator()->create_cohort(['contextid' => $category2context->id, 'name' => 'cat2 aud1']);
        $category2_audience2 = $this->getDataGenerator()->create_cohort(['contextid' => $category2context->id, 'name' => 'cat2 aud2']);

        /* @var totara_cohort_generator $cohortgenerator */
        $cohortgenerator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');

        $cohortgenerator->cohort_assign_users($system_audience->id, [$user1->id, $user2->id]);
        $cohortgenerator->cohort_assign_users($category1_audience->id, [$user1->id, $user2->id]);
        $cohortgenerator->cohort_assign_users($category2_audience1->id, [$user1->id]);
        $cohortgenerator->cohort_assign_users($category2_audience2->id, [$user2->id]);

        // Create role assignments.
        $coursecreatorid = $DB->get_field('role', 'id', ['shortname' => 'coursecreator']);
        // Within the interface. Roles that are assigned to audiences use the same context that the audience is in.
        totara_cohort_process_assigned_roles($system_audience->id, [$coursecreatorid => $systemcontext->id]);
        totara_cohort_process_assigned_roles($category1_audience->id, [$coursecreatorid => $category1context->id]);
        totara_cohort_process_assigned_roles($category2_audience1->id, [$coursecreatorid => $category2context->id]);
        totara_cohort_process_assigned_roles($category2_audience2->id, [$coursecreatorid => $category2context->id]);

        // Both users should now be assigned to the coursecreator role in each context.
        $roles = get_user_roles($systemcontext, $user1->id, false);
        $this->assertCount(1, $roles);
        $this->assertEquals('coursecreator', reset($roles)->shortname);
        $roles = get_user_roles($category1context, $user1->id, false);
        $this->assertCount(1, $roles);
        $this->assertEquals('coursecreator', reset($roles)->shortname);
        $roles = get_user_roles($category2context, $user1->id, false);
        $this->assertCount(1, $roles);
        $this->assertEquals('coursecreator', reset($roles)->shortname);
        $roles = get_user_roles($systemcontext, $user2->id, false);
        $this->assertCount(1, $roles);
        $this->assertEquals('coursecreator', reset($roles)->shortname);
        $roles = get_user_roles($category1context, $user2->id, false);
        $this->assertCount(1, $roles);
        $this->assertEquals('coursecreator', reset($roles)->shortname);
        $roles = get_user_roles($category2context, $user2->id, false);
        $this->assertCount(1, $roles);
        $this->assertEquals('coursecreator', reset($roles)->shortname);

        return [
            'user1' => $user1,
            'user2' => $user2,
            'category1' => $category1,
            'category2' => $category2,
            'system_audience' => $system_audience,
            'category1_audience' => $category1_audience,
            'category2_audience1' => $category2_audience1,
            'category2_audience2' => $category2_audience2,
        ];
    }

    /**
     * Ensures that the roles of user2 are unchanged from what they should be when created by create_audience_data().
     *
     * To be run following a purge of user1 data.
     *
     * @param array $data as returned by create_audience_data().
     */
    private function assert_user2_roles_unaffected($data) {
        $roles = get_user_roles(context_system::instance(), $data['user2']->id, false);
        $this->assertCount(1, $roles);
        $this->assertEquals('coursecreator', reset($roles)->shortname);
        $roles = get_user_roles(context_coursecat::instance($data['category1']->id), $data['user2']->id, false);
        $this->assertCount(1, $roles);
        $this->assertEquals('coursecreator', reset($roles)->shortname);
        $roles = get_user_roles(context_coursecat::instance($data['category2']->id), $data['user2']->id, false);
        $this->assertCount(1, $roles);
        $this->assertEquals('coursecreator', reset($roles)->shortname);
    }

    public function test_export_system_context() {
        $data = $this->create_audience_data();

        $export = set_membership::execute_export(
            new target_user($data['user1']),
            context_system::instance()
        );
        $this->assertEmpty($export->files);

        $this->assertCount(3, $export->data);
        foreach($export->data as $record) {
            $this->assertEquals($data['user1']->id, $record->userid);
            $this->assertNotEquals($data['category2_audience2']->id, $record->cohortid);
            $this->assertNotEquals('cat2 aud2', $record->name);
        }
    }

    public function test_count_system_context() {
        $data = $this->create_audience_data();

        $count = set_membership::execute_count(
            new target_user($data['user1']),
            context_system::instance()
        );

        $this->assertEquals(3, $count);

        return $data;
    }

    public function test_purge_system_context() {
        global $DB;

        $data = $this->create_audience_data();

        $eventsink = $this->redirectEvents();

        $result = set_membership::execute_purge(
            new target_user($data['user1']),
            context_system::instance()
        );

        $this->assertEquals(6, $eventsink->count());

        $role_unassigned_count = 0;
        $cohort_member_removed_count = 0;
        foreach ($eventsink->get_events() as $event) {
            if ($event instanceof \core\event\role_unassigned) {
                $role_unassigned_count++;
            } else if ($event instanceof \core\event\cohort_member_removed) {
                $cohort_member_removed_count++;
            } else {
                $this->fail('Unexpected event was triggered of type ' . get_class($event));
            }
        }
        $this->assertEquals(3, $role_unassigned_count);
        $this->assertEquals(3, $cohort_member_removed_count);
        $eventsink->close();

        $this->assertEquals(set_membership::RESULT_STATUS_SUCCESS, $result);

        // Nothing should exist for user1 in cohort members anymore.
        $this->assertEquals(0, $DB->count_records('cohort_members', ['userid' => $data['user1']->id]));

        $system_members = totara_get_members_cohort($data['system_audience']->id);
        $this->assertCount(1, $system_members);
        $this->assertEquals($data['user2']->id, reset($system_members)->userid);

        $category1_members = totara_get_members_cohort($data['category1_audience']->id);
        $this->assertCount(1, $category1_members);
        $this->assertEquals($data['user2']->id, reset($category1_members)->userid);

        $category2_1_members = totara_get_members_cohort($data['category2_audience1']->id);
        $this->assertCount(0, $category2_1_members);

        $category2_2_members = totara_get_members_cohort($data['category2_audience2']->id);
        $this->assertCount(1, $category2_2_members);
        $this->assertEquals($data['user2']->id, reset($category2_2_members)->userid);

        $roles = get_user_roles(context_system::instance(), $data['user1']->id, false);
        $this->assertCount(0, $roles);
        $roles = get_user_roles(context_coursecat::instance($data['category1']->id), $data['user1']->id, false);
        $this->assertCount(0, $roles);
        $roles = get_user_roles(context_coursecat::instance($data['category2']->id), $data['user1']->id, false);
        $this->assertCount(0, $roles);

        $this->assert_user2_roles_unaffected($data);
    }

    public function test_export_category_context() {
        $data = $this->create_audience_data();

        $export = set_membership::execute_export(
            new target_user($data['user1']),
            context_coursecat::instance($data['category1']->id)
        );
        $this->assertEmpty($export->files);

        $this->assertCount(1, $export->data);
        $record = reset($export->data);

        $this->assertEquals($data['user1']->id, $record->userid);
        $this->assertEquals($data['category1_audience']->id, $record->cohortid);
        $this->assertEquals('cat1 aud1', $record->name);
    }

    public function test_count_category_context() {
        $data = $this->create_audience_data();

        $count = set_membership::execute_count(
            new target_user($data['user1']),
            context_coursecat::instance($data['category1']->id)
        );

        $this->assertEquals(1, $count);
    }

    public function test_purge_category_context() {
        global $DB;

        $data = $this->create_audience_data();

        $eventsink = $this->redirectEvents();

        $result = set_membership::execute_purge(
            new target_user($data['user1']),
            context_coursecat::instance($data['category1']->id)
        );

        $this->assertEquals(2, $eventsink->count());

        $role_unassigned_count = 0;
        $cohort_member_removed_count = 0;
        foreach ($eventsink->get_events() as $event) {
            if ($event instanceof \core\event\role_unassigned) {
                $role_unassigned_count++;
            } else if ($event instanceof \core\event\cohort_member_removed) {
                $cohort_member_removed_count++;
            } else {
                $this->fail('Unexpected event was triggered of type ' . get_class($event));
            }
        }
        $this->assertEquals(1, $role_unassigned_count);
        $this->assertEquals(1, $cohort_member_removed_count);
        $eventsink->close();

        $this->assertEquals(set_membership::RESULT_STATUS_SUCCESS, $result);

        // User1 will still be a member of the system audience and category2 audience 1.
        $this->assertEquals(2, $DB->count_records('cohort_members', ['userid' => $data['user1']->id]));

        $system_members = totara_get_members_cohort($data['system_audience']->id);
        $this->assertCount(2, $system_members);

        $category1_members = totara_get_members_cohort($data['category1_audience']->id);
        $this->assertCount(1, $category1_members);
        $this->assertEquals($data['user2']->id, reset($category1_members)->userid);

        $category2_1_members = totara_get_members_cohort($data['category2_audience1']->id);
        $this->assertCount(1, $category2_1_members);
        $this->assertEquals($data['user1']->id, reset($category2_1_members)->userid);

        $category2_2_members = totara_get_members_cohort($data['category2_audience2']->id);
        $this->assertCount(1, $category2_2_members);
        $this->assertEquals($data['user2']->id, reset($category2_2_members)->userid);

        // Only user1's category1 role should have been removed.
        $roles = get_user_roles(context_system::instance(), $data['user1']->id, false);
        $this->assertCount(1, $roles);
        $this->assertEquals('coursecreator', reset($roles)->shortname);
        $roles = get_user_roles(context_coursecat::instance($data['category1']->id), $data['user1']->id, false);
        $this->assertCount(0, $roles);
        $roles = get_user_roles(context_coursecat::instance($data['category2']->id), $data['user1']->id, false);
        $this->assertCount(1, $roles);
        $this->assertEquals('coursecreator', reset($roles)->shortname);

        $this->assert_user2_roles_unaffected($data);
    }

    /**
     * Confirms that running purge on a deleted user does not raise any errors and that any existing data is still purged.
     */
    public function test_purge_with_deleted_user() {
        global $DB;

        $data = $this->create_audience_data();

        delete_user($data['user1']);

        // Reload so that the deleted field = 1 and any other fields represent the new state.
        $deleteduser = $DB->get_record('user', ['id' => $data['user1']->id]);

        // First of all, user1's audience membership was removed by the delete_user() function. Roles were also deleted.
        $this->assertEquals(0, $DB->count_records('cohort_members', ['userid' => $deleteduser->id]));
        $this->assertEquals(0, $DB->count_records('role_assignments', ['userid' => $deleteduser->id]));

        $eventsink = $this->redirectEvents();

        // Run purge with all the data deleted. Does anything break, perhaps due to some sort of unfinished deletion process.
        $result = set_membership::execute_purge(
            new target_user($deleteduser),
            context_system::instance()
        );

        $this->assertEquals(0, $eventsink->count());

        $this->assertEquals(set_membership::RESULT_STATUS_SUCCESS, $result);

        // Recreate some membership data. Who knows how this might happen. But it should still be deleted without
        // error if it exists.
        $membership = new stdClass();
        $membership->userid = $deleteduser->id;
        $membership->cohortid = $data['system_audience']->id;
        $DB->insert_record('cohort_members', $membership);

        $eventsink->clear();

        $result = set_membership::execute_purge(
            new target_user($deleteduser),
            context_system::instance()
        );

        $this->assertEquals(1, $eventsink->count());

        // There was no role to unassign. So only the cohort_member_removed event was triggered.
        $this->assertContainsOnlyInstancesOf(\core\event\cohort_member_removed::class, $eventsink->get_events());
        $eventsink->close();

        $this->assertEquals(set_membership::RESULT_STATUS_SUCCESS, $result);

        // There should again be nothing left for this user in cohort_members.
        $this->assertEquals(0, $DB->count_records('cohort_members', ['userid' => $deleteduser->id]));
    }
}