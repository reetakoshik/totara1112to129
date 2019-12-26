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
 * @package totara_core
 */

use totara_core\userdata\course_enrolments;
use totara_userdata\userdata\target_user;

/**
 * Class totara_core_userdata_course_enrolments_testcase
 *
 * @group totara_userdata
 */
class totara_core_userdata_course_enrolments_testcase extends advanced_testcase {

    /**
     * Creates data for testing enrolments, including the courses, users and groups.
     *
     * @param string $pluginname
     * @return array containing data within related keys
     */
    private function create_test_data(string $pluginname) : array {
        global $DB;

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();

        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $category2->id]);
        $course3 = $this->getDataGenerator()->create_course(['category' => $category2->id]);

        $group1a = $this->getDataGenerator()->create_group(['courseid' => $course1->id]);
        $group2a = $this->getDataGenerator()->create_group(['courseid' => $course2->id]);
        $group3a = $this->getDataGenerator()->create_group(['courseid' => $course3->id]);
        $group3b = $this->getDataGenerator()->create_group(['courseid' => $course3->id]);

        // Enable the plugin if necessary.
        $enabled = enrol_get_plugins(true);
        if (!isset($enabled[$pluginname])) {
            $enabled = array_keys($enabled);
            $enabled[] = $pluginname;
            set_config('enrol_plugins_enabled', implode(',', $enabled));
        }
        // Add instance of the plugin to the courses if none is added by default.
        $plugin = enrol_get_plugin($pluginname);
        if (!$DB->record_exists('enrol', ['enrol' => $pluginname])) {
            $plugin->add_instance($course1);
            $plugin->add_instance($course2);
            $plugin->add_instance($course3);
        }

        // Users 1 and 2 are assigned with the default role, which will be learner.
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id, 'student', $pluginname);
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id, 'student', $pluginname);
        $this->getDataGenerator()->enrol_user($user1->id, $course3->id, 'student', $pluginname);
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id, 'student', $pluginname);
        $this->getDataGenerator()->enrol_user($user2->id, $course2->id, 'student', $pluginname);

        // User 3 will be a trainer (short name for that while running phpunit is 'teacher').
        $this->getDataGenerator()->enrol_user($user3->id, $course1->id, 'teacher', $pluginname);
        $this->getDataGenerator()->enrol_user($user3->id, $course3->id, 'teacher', $pluginname);

        // User 3 gets a second enrolment for course 3.
        if ($pluginname === 'manual') {
            $plugin = enrol_get_plugin('cohort');
            $plugin->add_instance($course3);
            $this->getDataGenerator()->enrol_user($user3->id, $course3->id, 'manager', 'cohort');
        } else {
            $this->getDataGenerator()->enrol_user($user3->id, $course3->id, 'manager', 'manual');
        }

        groups_add_member($group1a->id, $user1->id);
        groups_add_member($group2a->id, $user1->id);
        groups_add_member($group3a->id, $user1->id);

        groups_add_member($group1a->id, $user2->id);
        groups_add_member($group2a->id, $user2->id);

        groups_add_member($group1a->id, $user3->id);
        groups_add_member($group3a->id, $user3->id);
        groups_add_member($group3b->id, $user3->id);

        return [
            'user1' => $user1,
            'user2' => $user2,
            'user3' => $user3,
            'category1' => $category1,
            'category2' => $category2,
            'course1' => $course1,
            'course2' => $course2,
            'course3' => $course3,
            'group1a' => $group1a,
            'group2a' => $group2a,
            'group3a' => $group3a,
            'group3b' => $group3b,
        ];

    }

    public function test_export_system_context() {
        $data = $this->create_test_data('manual');

        $export = course_enrolments::execute_export(
            new target_user($data['user1']),
            context_system::instance()
        );

        $this->assertEmpty($export->files);
        $this->assertCount(3, $export->data['enrolments']);
        foreach ($export->data['enrolments'] as $enrolment) {
            $this->assertEquals($data['user1']->id, $enrolment->userid);
        }
        $this->assertCount(3, $export->data['groups']);
        // The groups array is sorted by groupings, starting with all groups represented by 0.
        // This is as returned by groups_get_user_groups().
        $this->assertContains($data['group1a']->id, $export->data['groups'][$data['course1']->id][0]);
        $this->assertContains($data['group2a']->id, $export->data['groups'][$data['course2']->id][0]);
        $this->assertContains($data['group3a']->id, $export->data['groups'][$data['course3']->id][0]);
    }

    public function test_export_multiple_enrolments_system_context() {
        $data = $this->create_test_data('manual');

        $export = course_enrolments::execute_export(
            new target_user($data['user3']),
            context_system::instance()
        );

        $this->assertEmpty($export->files);
        $this->assertCount(3, $export->data['enrolments']);
        foreach ($export->data['enrolments'] as $enrolment) {
            $this->assertEquals($data['user3']->id, $enrolment->userid);
        }

        // User 3 is a member of groups in 2 courses.
        $this->assertCount(2, $export->data['groups']);

        $this->assertContains($data['group1a']->id, $export->data['groups'][$data['course1']->id][0]);

        $this->assertCount(2, $export->data['groups'][$data['course3']->id][0]);
        $this->assertContains($data['group3a']->id, $export->data['groups'][$data['course3']->id][0]);
        $this->assertContains($data['group3b']->id, $export->data['groups'][$data['course3']->id][0]);
    }

    public function test_count_system_context() {
        $data = $this->create_test_data('manual');

        $count = course_enrolments::execute_count(
            new target_user($data['user1']),
            context_system::instance()
        );

        $this->assertEquals(3, $count);
    }

    public function test_count_multiple_enrolments_system_context() {
        $data = $this->create_test_data('manual');

        $count = course_enrolments::execute_count(
            new target_user($data['user3']),
            context_system::instance()
        );

        $this->assertEquals(3, $count);
    }

    public function test_purge_system_context() {
        $data = $this->create_test_data('manual');

        $result = course_enrolments::execute_purge(
            new target_user($data['user1']),
            context_system::instance()
        );

        $this->assertEquals(course_enrolments::RESULT_STATUS_SUCCESS, $result);

        $course1context = context_course::instance($data['course1']->id);
        $course2context = context_course::instance($data['course2']->id);
        $course3context = context_course::instance($data['course3']->id);

        // Check if enrolled.
        $this->assertCount(0, enrol_get_all_users_courses($data['user1']->id));
        $this->assertCount(2, enrol_get_all_users_courses($data['user2']->id));
        $this->assertCount(2, enrol_get_all_users_courses($data['user3']->id));

        // Check users roles.
        $this->assertCount(0, get_user_roles($course1context, $data['user1']->id, false));
        $this->assertCount(0, get_user_roles($course2context, $data['user1']->id, false));
        $this->assertCount(0, get_user_roles($course3context, $data['user1']->id, false));
        $this->assertCount(1, get_user_roles($course1context, $data['user2']->id, false));
        $this->assertCount(1, get_user_roles($course2context, $data['user2']->id, false));
        $this->assertCount(1, get_user_roles($course1context, $data['user3']->id, false));
        $this->assertCount(2, get_user_roles($course3context, $data['user3']->id, false));

        // Check group memberships.
        $this->assertFalse(groups_is_member($data['group1a']->id, $data['user1']->id));
        $this->assertFalse(groups_is_member($data['group2a']->id, $data['user1']->id));
        $this->assertFalse(groups_is_member($data['group3a']->id, $data['user1']->id));
        $this->assertTrue(groups_is_member($data['group1a']->id, $data['user2']->id));
        $this->assertTrue(groups_is_member($data['group2a']->id, $data['user2']->id));
        $this->assertTrue(groups_is_member($data['group1a']->id, $data['user3']->id));
        $this->assertTrue(groups_is_member($data['group3a']->id, $data['user3']->id));
        $this->assertTrue(groups_is_member($data['group3b']->id, $data['user3']->id));
    }

    public function test_purge_multiple_enrolments_system_context() {
        $data = $this->create_test_data('manual');

        $result = course_enrolments::execute_purge(
            new target_user($data['user3']),
            context_system::instance()
        );

        $course1context = context_course::instance($data['course1']->id);
        $course3context = context_course::instance($data['course3']->id);

        // We only need to check user3 here. It's a test of whether their data was still deleted despite
        // having a non-learner role. The test of the correct user's data being deleted was done with user1.
        $this->assertEquals(course_enrolments::RESULT_STATUS_SUCCESS, $result);
        $this->assertCount(0, enrol_get_all_users_courses($data['user3']->id));
        $this->assertCount(0, get_user_roles($course1context, $data['user3']->id, false));
        $this->assertCount(0, get_user_roles($course3context, $data['user3']->id, false));
        $this->assertFalse(groups_is_member($data['group1a']->id, $data['user3']->id));
        $this->assertFalse(groups_is_member($data['group3a']->id, $data['user3']->id));
        $this->assertFalse(groups_is_member($data['group3b']->id, $data['user3']->id));
    }

    public function test_export_category_context() {
        $data = $this->create_test_data('manual');

        $export = course_enrolments::execute_export(
            new target_user($data['user1']),
            context_coursecat::instance($data['category2']->id)
        );

        $this->assertEmpty($export->files);
        $this->assertCount(2, $export->data['enrolments']);
        foreach ($export->data['enrolments'] as $enrolment) {
            $this->assertEquals($data['user1']->id, $enrolment->userid);
        }
        $this->assertCount(2, $export->data['groups']);
        // The groups array is sorted by groupings, starting with all groups represented by 0.
        // This is as returned by groups_get_user_groups().
        $this->assertArrayNotHasKey($data['course1']->id, $export->data['groups']);
        $this->assertContains($data['group2a']->id, $export->data['groups'][$data['course2']->id][0]);
        $this->assertContains($data['group3a']->id, $export->data['groups'][$data['course3']->id][0]);
    }

    public function test_export_multiple_enrolments_category_context() {
        $data = $this->create_test_data('manual');

        $export = course_enrolments::execute_export(
            new target_user($data['user3']),
            context_coursecat::instance($data['category2']->id)
        );

        $this->assertEmpty($export->files);
        $this->assertCount(2, $export->data['enrolments']);
        foreach ($export->data['enrolments'] as $enrolment) {
            $this->assertEquals($data['user3']->id, $enrolment->userid);
        }

        // User 3 is a member of groups in 1 course in this category.
        $this->assertCount(1, $export->data['groups']);

        $this->assertArrayNotHasKey($data['course1']->id, $export->data['groups']);

        $this->assertCount(2, $export->data['groups'][$data['course3']->id][0]);
        $this->assertContains($data['group3a']->id, $export->data['groups'][$data['course3']->id][0]);
        $this->assertContains($data['group3b']->id, $export->data['groups'][$data['course3']->id][0]);
    }

    public function test_count_category_context() {
        $data = $this->create_test_data('manual');

        $count = course_enrolments::execute_count(
            new target_user($data['user1']),
            context_coursecat::instance($data['category2']->id)
        );

        $this->assertEquals(2, $count);

        return $data;
    }

    public function test_count_multiple_enrolments_category_context() {
        $data = $this->create_test_data('manual');

        $count = course_enrolments::execute_count(
            new target_user($data['user3']),
            context_coursecat::instance($data['category2']->id)
        );

        $this->assertEquals(2, $count);

        return $data;
    }

    public function test_purge_category_context() {
        $data = $this->create_test_data('manual');

        $result = course_enrolments::execute_purge(
            new target_user($data['user1']),
            context_coursecat::instance($data['category2']->id)
        );

        $this->assertEquals(course_enrolments::RESULT_STATUS_SUCCESS, $result);

        $course1context = context_course::instance($data['course1']->id);
        $course2context = context_course::instance($data['course2']->id);
        $course3context = context_course::instance($data['course3']->id);

        // Check if enrolled.
        $user1courses = enrol_get_all_users_courses($data['user1']->id);
        $this->assertCount(1, $user1courses);
        $this->assertEquals($data['course1']->id, reset($user1courses)->id);
        $this->assertCount(2, enrol_get_all_users_courses($data['user2']->id));
        $this->assertCount(2, enrol_get_all_users_courses($data['user3']->id));

        // Check users roles.
        $this->assertCount(1, get_user_roles($course1context, $data['user1']->id, false));
        $this->assertCount(0, get_user_roles($course2context, $data['user1']->id, false));
        $this->assertCount(0, get_user_roles($course3context, $data['user1']->id, false));
        $this->assertCount(1, get_user_roles($course1context, $data['user2']->id, false));
        $this->assertCount(1, get_user_roles($course2context, $data['user2']->id, false));
        $this->assertCount(1, get_user_roles($course1context, $data['user3']->id, false));
        $this->assertCount(2, get_user_roles($course3context, $data['user3']->id, false));

        // Check group memberships.
        $this->assertTrue(groups_is_member($data['group1a']->id, $data['user1']->id));
        $this->assertFalse(groups_is_member($data['group2a']->id, $data['user1']->id));
        $this->assertFalse(groups_is_member($data['group3a']->id, $data['user1']->id));
        $this->assertTrue(groups_is_member($data['group1a']->id, $data['user2']->id));
        $this->assertTrue(groups_is_member($data['group2a']->id, $data['user2']->id));
        $this->assertTrue(groups_is_member($data['group1a']->id, $data['user3']->id));
        $this->assertTrue(groups_is_member($data['group3a']->id, $data['user3']->id));
        $this->assertTrue(groups_is_member($data['group3b']->id, $data['user3']->id));
    }

    public function test_purge_multiple_enrolments_category_context() {
        $data = $this->create_test_data('manual');

        $result = course_enrolments::execute_purge(
            new target_user($data['user3']),
            context_coursecat::instance($data['category2']->id)
        );

        $course1context = context_course::instance($data['course1']->id);
        $course3context = context_course::instance($data['course3']->id);

        // We only need to check user3 here. It's a test of whether their data was still deleted despite
        // having a non-learner role. The test of the correct user's data being deleted was done with user1.
        $this->assertEquals(course_enrolments::RESULT_STATUS_SUCCESS, $result);
        $user3courses = enrol_get_all_users_courses($data['user3']->id);
        $this->assertCount(1, $user3courses);
        $this->assertEquals($data['course1']->id, reset($user3courses)->id);
        $this->assertCount(1, get_user_roles($course1context, $data['user3']->id, false));
        $this->assertCount(0, get_user_roles($course3context, $data['user3']->id, false));
        $this->assertTrue(groups_is_member($data['group1a']->id, $data['user3']->id));
        $this->assertFalse(groups_is_member($data['group3a']->id, $data['user3']->id));
        $this->assertFalse(groups_is_member($data['group3b']->id, $data['user3']->id));
    }

    public function test_export_course_context() {
        $data = $this->create_test_data('manual');

        $export = course_enrolments::execute_export(
            new target_user($data['user1']),
            context_course::instance($data['course3']->id)
        );

        $this->assertEmpty($export->files);
        $this->assertCount(1, $export->data['enrolments']);
        foreach ($export->data['enrolments'] as $enrolment) {
            $this->assertEquals($data['user1']->id, $enrolment->userid);
        }
        $this->assertCount(1, $export->data['groups']);
        // The groups array is sorted by groupings, starting with all groups represented by 0.
        // This is as returned by groups_get_user_groups().
        $this->assertArrayNotHasKey($data['course1']->id, $export->data['groups']);
        $this->assertArrayNotHasKey($data['course2']->id, $export->data['groups']);
        $this->assertContains($data['group3a']->id, $export->data['groups'][$data['course3']->id][0]);
    }

    public function test_export_multiple_enrolments_course_context() {
        $data = $this->create_test_data('manual');

        $export = course_enrolments::execute_export(
            new target_user($data['user3']),
            context_course::instance($data['course3']->id)
        );

        $this->assertEmpty($export->files);
        $this->assertCount(2, $export->data['enrolments']);
        foreach ($export->data['enrolments'] as $enrolment) {
            $this->assertEquals($data['user3']->id, $enrolment->userid);
        }

        // User 3 is a member of groups in 1 course in this category.
        $this->assertCount(1, $export->data['groups']);

        $this->assertArrayNotHasKey($data['course1']->id, $export->data['groups']);

        $this->assertCount(2, $export->data['groups'][$data['course3']->id][0]);
        $this->assertContains($data['group3a']->id, $export->data['groups'][$data['course3']->id][0]);
        $this->assertContains($data['group3b']->id, $export->data['groups'][$data['course3']->id][0]);
    }

    public function test_count_course_context() {
        $data = $this->create_test_data('manual');

        $count = course_enrolments::execute_count(
            new target_user($data['user1']),
            context_course::instance($data['course3']->id)
        );

        $this->assertEquals(1, $count);
    }

    public function test_count_multiple_enrolments_course_context() {
        $data = $this->create_test_data('manual');

        $count = course_enrolments::execute_count(
            new target_user($data['user3']),
            context_course::instance($data['course3']->id)
        );

        $this->assertEquals(2, $count);
    }

    public function test_purge_course_context() {
        $data = $this->create_test_data('manual');

        $result = course_enrolments::execute_purge(
            new target_user($data['user1']),
            context_course::instance($data['course3']->id)
        );

        $this->assertEquals(course_enrolments::RESULT_STATUS_SUCCESS, $result);

        $course1context = context_course::instance($data['course1']->id);
        $course2context = context_course::instance($data['course2']->id);
        $course3context = context_course::instance($data['course3']->id);

        // Check if enrolled.
        $user1courses = enrol_get_all_users_courses($data['user1']->id);
        $this->assertCount(2, $user1courses);
        foreach ($user1courses as $user1course) {
            $this->assertNotEquals($data['course3']->id, $user1course->id);
        }
        $this->assertCount(2, enrol_get_all_users_courses($data['user2']->id));
        $this->assertCount(2, enrol_get_all_users_courses($data['user3']->id));

        // Check users roles.
        $this->assertCount(1, get_user_roles($course1context, $data['user1']->id, false));
        $this->assertCount(1, get_user_roles($course2context, $data['user1']->id, false));
        $this->assertCount(0, get_user_roles($course3context, $data['user1']->id, false));
        $this->assertCount(1, get_user_roles($course1context, $data['user2']->id, false));
        $this->assertCount(1, get_user_roles($course2context, $data['user2']->id, false));
        $this->assertCount(1, get_user_roles($course1context, $data['user3']->id, false));
        $this->assertCount(2, get_user_roles($course3context, $data['user3']->id, false));

        // Check group memberships.
        $this->assertTrue(groups_is_member($data['group1a']->id, $data['user1']->id));
        $this->assertTrue(groups_is_member($data['group2a']->id, $data['user1']->id));
        $this->assertFalse(groups_is_member($data['group3a']->id, $data['user1']->id));
        $this->assertTrue(groups_is_member($data['group1a']->id, $data['user2']->id));
        $this->assertTrue(groups_is_member($data['group2a']->id, $data['user2']->id));
        $this->assertTrue(groups_is_member($data['group1a']->id, $data['user3']->id));
        $this->assertTrue(groups_is_member($data['group3a']->id, $data['user3']->id));
        $this->assertTrue(groups_is_member($data['group3b']->id, $data['user3']->id));
    }

    public function test_purge_multiple_enrolments_course_context() {
        $data = $this->create_test_data('manual');

        $result = course_enrolments::execute_purge(
            new target_user($data['user3']),
            context_course::instance($data['course3']->id)
        );

        $course1context = context_course::instance($data['course1']->id);
        $course3context = context_course::instance($data['course3']->id);

        // We only need to check user3 here. It's a test of whether their data was still deleted despite
        // having a non-learner role. The test of the correct user's data being deleted was done with user1.
        $this->assertEquals(course_enrolments::RESULT_STATUS_SUCCESS, $result);
        $user3courses = enrol_get_all_users_courses($data['user3']->id);
        $this->assertCount(1, $user3courses);
        $this->assertEquals($data['course1']->id, reset($user3courses)->id);
        $this->assertCount(1, get_user_roles($course1context, $data['user3']->id, false));
        $this->assertCount(0, get_user_roles($course3context, $data['user3']->id, false));
        $this->assertTrue(groups_is_member($data['group1a']->id, $data['user3']->id));
        $this->assertFalse(groups_is_member($data['group3a']->id, $data['user3']->id));
        $this->assertFalse(groups_is_member($data['group3b']->id, $data['user3']->id));
    }

    public function data_provider_enrol_plugins() {
        // Because we are purging the core enrolment data, the plugin being called shouldn't matter.
        // Ideally, we make sure of that, so testing across most of the plugins included with core.

        // This list should contain all enrolment plugins. Any that either aren't relevant or require more complicated
        // set up prior to testing can be commented out.
        return [
            ['category'],
            ['cohort'],
            ['database'],
            ['flatfile'],
            // The user should not be enrolled via the guest plugin. If they are, then we don't know how that happened,
            // they also wouldn't be unenrolled, so not valid to test for here.
            // ['guest'],
            ['imsenterprise'],
            ['ldap'],
            // ['lti'], - Requires more complex setup to test.
            ['manual'],
            ['meta'],
            ['mnet'],
            ['paypal'],
            ['self'],
            ['totara_facetoface'],
            ['totara_learningplan'],
            ['totara_program'],
        ];
    }

    /**
     * @dataProvider data_provider_enrol_plugins
     * @param $pluginname
     */
    public function test_purge_course_context_different_plugins($pluginname) {
        global $DB;

        $data = $this->create_test_data($pluginname);

        $result = course_enrolments::execute_purge(
            new target_user($data['user1']),
            context_course::instance($data['course3']->id)
        );

        $this->assertEquals(course_enrolments::RESULT_STATUS_SUCCESS, $result);

        $user1courses = enrol_get_all_users_courses($data['user1']->id);
        $this->assertCount(2, $user1courses);
        foreach ($user1courses as $user1course) {
            $this->assertNotEquals($data['course3']->id, $user1course->id);
        }
        $this->assertCount(2, enrol_get_all_users_courses($data['user2']->id));
        $this->assertCount(2, enrol_get_all_users_courses($data['user3']->id));

        $course1context = context_course::instance($data['course1']->id);
        $course2context = context_course::instance($data['course2']->id);
        $course3context = context_course::instance($data['course3']->id);

        // Check users roles.
        $this->assertCount(1, get_user_roles($course1context, $data['user1']->id, false));
        $this->assertCount(1, get_user_roles($course2context, $data['user1']->id, false));
        $this->assertCount(0, get_user_roles($course3context, $data['user1']->id, false));
        $this->assertCount(1, get_user_roles($course1context, $data['user2']->id, false));
        $this->assertCount(1, get_user_roles($course2context, $data['user2']->id, false));
        $this->assertCount(1, get_user_roles($course1context, $data['user3']->id, false));
        $this->assertCount(2, get_user_roles($course3context, $data['user3']->id, false));

        // Check group memberships.
        $this->assertTrue(groups_is_member($data['group1a']->id, $data['user1']->id));
        $this->assertTrue(groups_is_member($data['group2a']->id, $data['user1']->id));
        $this->assertFalse(groups_is_member($data['group3a']->id, $data['user1']->id));
        $this->assertTrue(groups_is_member($data['group1a']->id, $data['user2']->id));
        $this->assertTrue(groups_is_member($data['group2a']->id, $data['user2']->id));
        $this->assertTrue(groups_is_member($data['group1a']->id, $data['user3']->id));
        $this->assertTrue(groups_is_member($data['group3a']->id, $data['user3']->id));
        $this->assertTrue(groups_is_member($data['group3b']->id, $data['user3']->id));

        // Set user 2 to deleted. We want the data to remain but we want to make sure
        // the plugin doesn't fall over if the deleted flag is set.
        // Setting the deleted flag rather than using delete_user is edge case behaviour that can be found in some plugins.
        $DB->set_field('user', 'deleted', 1, ['id' => $data['user2']->id]);
        $result = course_enrolments::execute_purge(
            new target_user($data['user2']),
            context_system::instance()
        );
        // No need to check data here, we're just making sure no exceptions are thrown.
        $this->assertEquals(course_enrolments::RESULT_STATUS_SUCCESS, $result);

        // Now disable the plugin.
        $enabled = enrol_get_plugins(true);
        $enabled = array_keys($enabled);
        $enabled = array_values(array_diff($enabled, [$pluginname]));
        set_config('enrol_plugins_enabled', implode(',', $enabled));

        $result = course_enrolments::execute_purge(
            new target_user($data['user3']),
            context_system::instance()
        );
        $this->assertEquals(course_enrolments::RESULT_STATUS_SUCCESS, $result);
        $user3courses = enrol_get_all_users_courses($data['user3']->id);
        $this->assertEmpty($user3courses);
        $this->assertCount(0, get_user_roles($course1context, $data['user3']->id, false));
        $this->assertCount(0, get_user_roles($course3context, $data['user3']->id, false));
        $this->assertFalse(groups_is_member($data['group1a']->id, $data['user3']->id));
        $this->assertFalse(groups_is_member($data['group3a']->id, $data['user3']->id));
        $this->assertFalse(groups_is_member($data['group3b']->id, $data['user3']->id));
    }
}