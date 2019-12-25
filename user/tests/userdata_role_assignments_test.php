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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package core_user
 */

use core_user\userdata\role_assignments;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Test purge, export and count of role_assignments user data item.
 *
 * @group totara_userdata
 */
class core_user_userdata_role_assignments_test extends advanced_testcase {

    /**
     * Set up tests.
     */
    protected function setUp() {
        parent::setUp();

        $this->resetAfterTest(true);
    }

    /**
     * Test some general settings for this item.
     */
    public function test_general() {
        $contextlevels = role_assignments::get_compatible_context_levels();

        $this->assertCount(7, $contextlevels);
        foreach ([
                     CONTEXT_SYSTEM,
                     CONTEXT_USER,
                     CONTEXT_COURSECAT,
                     CONTEXT_PROGRAM,
                     CONTEXT_COURSE,
                     CONTEXT_MODULE,
                     CONTEXT_BLOCK,
                ] as $contextlevel) {
            $this->assertContains($contextlevel, $contextlevels);
        }

        $this->assertTrue(role_assignments::is_exportable());
        $this->assertTrue(role_assignments::is_countable());
        $this->assertTrue(role_assignments::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(role_assignments::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertFalse(role_assignments::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * @param array $user_properties
     * @return stdClass
     */
    private function setup_data(array $user_properties = []) {
        global $DB;

        $data = new stdClass();

        // Create users.
        $data->user1 = $this->getDataGenerator()->create_user($user_properties);
        $data->user2 = $this->getDataGenerator()->create_user($user_properties);

        // Make sure they don't come with role assignments.
        $this->assertCount(0, $DB->get_records('role_assignments', ['userid' => $data->user1->id]));
        $this->assertCount(0, $DB->get_records('role_assignments', ['userid' => $data->user2->id]));

        // Create roles.
        $role1_id = $this->getDataGenerator()->create_role();
        $role2_id = $this->getDataGenerator()->create_role();

        // Create categories.
        $data->category1 = $this->getDataGenerator()->create_category(['name' => 'Test cat 1']);
        $data->category2 = $this->getDataGenerator()->create_category(['name' => 'Test cat 2']);

        // Create courses.
        $data->course1 = $this->getDataGenerator()->create_course(['category' => $data->category1->id, 'fullname' => 'Test course 1']);
        $data->course2 = $this->getDataGenerator()->create_course(['category' => $data->category2->id, 'fullname' => 'Test course 2']);

        // Create programs
        /** @var totara_program_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $data->program1 = $programgenerator->create_program(['fullname' => 'Test prog 1']);
        $data->program2 = $programgenerator->create_program(['fullname' => 'Test prog 2']);

        // Create modules
        /** @var mod_glossary_generator $glossary_generator */
        $glossary_generator = self::getDataGenerator()->get_plugin_generator('mod_glossary');
        $data->module1 = $glossary_generator->create_instance(['course' => $data->course1->id, 'name' => 'Test module 1']);
        $data->module2 = $glossary_generator->create_instance(['course' => $data->course2->id, 'name' => 'Test module 2']);

        $context_module1 = context_module::instance($data->module1->cmid);
        $context_module2 = context_module::instance($data->module2->cmid);

        // Create blocks
        $data->block1 = $this->getDataGenerator()->create_block('online_users', ['parentcontextid' => $context_module1->id]);
        $data->block2 = $this->getDataGenerator()->create_block('totara_featured_links', ['parentcontextid' => $context_module2->id]);

        $context_system = context_system::instance();
        $context_course1 = context_course::instance($data->course1->id);
        $context_course2 = context_course::instance($data->course2->id);
        $context_category1 = context_coursecat::instance($data->category1->id);
        $context_category2 = context_coursecat::instance($data->category2->id);
        $context_user1 = context_user::instance($data->user1->id);
        $context_user2 = context_user::instance($data->user2->id);
        $context_program1 = context_program::instance($data->program1->id);
        $context_program2 = context_program::instance($data->program2->id);
        $context_block1 = context_block::instance($data->block1->id);
        $context_block2 = context_block::instance($data->block2->id);

        // Create role assignments for the user to purge data for.
        $data->ra = [];
        $data->ra['system'] = role_assign($role1_id, $data->user1->id, $context_system->id);
        $data->ra['course1'] = role_assign($role1_id, $data->user1->id, $context_course1->id);
        // Mix in an assignment for a different role as well.
        $data->ra['course1_role2'] = role_assign($role2_id, $data->user1->id, $context_course1->id);
        $data->ra['course2'] = role_assign($role1_id, $data->user1->id, $context_course2->id);
        $data->ra['category1'] = role_assign($role1_id, $data->user1->id, $context_category1->id);
        $data->ra['category2'] = role_assign($role1_id, $data->user1->id, $context_category2->id);
        $data->ra['user'] = role_assign($role1_id, $data->user1->id, $context_user2->id);
        $data->ra['program1'] = role_assign($role1_id, $data->user1->id, $context_program1->id);
        $data->ra['program2'] = role_assign($role1_id, $data->user1->id, $context_program2->id);
        $data->ra['module1'] = role_assign($role1_id, $data->user1->id, $context_module1->id);
        $data->ra['module2'] = role_assign($role1_id, $data->user1->id, $context_module2->id);
        $data->ra['block1'] = role_assign($role1_id, $data->user1->id, $context_block1->id);
        $data->ra['block2'] = role_assign($role1_id, $data->user1->id, $context_block2->id);

        $data->expectedcontextnames[$data->ra['system']] = 'System';
        $data->expectedcontextnames[$data->ra['course1']] = 'Course: Test course 1';
        $data->expectedcontextnames[$data->ra['course1_role2']] = 'Course: Test course 1';
        $data->expectedcontextnames[$data->ra['course2']] = 'Course: Test course 2';
        $data->expectedcontextnames[$data->ra['category1']] = 'Category: Test cat 1';
        $data->expectedcontextnames[$data->ra['category2']] = 'Category: Test cat 2';
        $data->expectedcontextnames[$data->ra['user']] = 'User: ' . fullname($data->user2);
        $data->expectedcontextnames[$data->ra['program1']] = 'Program: Test prog 1';
        $data->expectedcontextnames[$data->ra['program2']] = 'Program: Test prog 2';
        $data->expectedcontextnames[$data->ra['module1']] = 'Glossary: Test module 1';
        $data->expectedcontextnames[$data->ra['module2']] = 'Glossary: Test module 2';
        $data->expectedcontextnames[$data->ra['block1']] = 'Block: Online users';
        $data->expectedcontextnames[$data->ra['block2']] = 'Block: Featured Links';
        
        // Create role assignments for the control user.
        role_assign($role1_id, $data->user2->id, $context_system->id);
        role_assign($role1_id, $data->user2->id, $context_course1->id);
        role_assign($role1_id, $data->user2->id, $context_course2->id);
        role_assign($role1_id, $data->user2->id, $context_category1->id);
        role_assign($role1_id, $data->user2->id, $context_category2->id);
        role_assign($role1_id, $data->user2->id, $context_user1->id);
        role_assign($role1_id, $data->user2->id, $context_program1->id);
        role_assign($role1_id, $data->user2->id, $context_program2->id);
        role_assign($role1_id, $data->user2->id, $context_module1->id);
        role_assign($role1_id, $data->user2->id, $context_module2->id);
        role_assign($role1_id, $data->user2->id, $context_block1->id);
        role_assign($role1_id, $data->user2->id, $context_block2->id);

        // Check expected amount of generated role assignments.
        $this->assertCount(13, $DB->get_records('role_assignments', ['userid' => $data->user1->id]));
        $this->assertCount(12, $DB->get_records('role_assignments', ['userid' => $data->user2->id]));

        return $data;
    }

    /**
     * Make sure trying to purge for deleted user results in error.
     */
    public function test_purge_deleted_user() {
        $user = $this->getDataGenerator()->create_user(['deleted' => 1]);
        $targetuser = new target_user($user);
        $status = role_assignments::execute_purge($targetuser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_ERROR, $status);
    }

    /**
     * Test if data is correctly purged in system context for active user.
     */
    public function test_purge_in_system_context_active_user() {
        $this->purge_in_system_context([]);
    }

    /**
     * Test if data is correctly purged in system context for suspended user.
     */
    public function test_purge_in_system_context_suspended_user() {
        $this->purge_in_system_context(['suspended' => 1]);
    }

    /**
     * Test if data is correctly purged in system context.
     *
     * @param array $user_properties
     */
    private function purge_in_system_context(array $user_properties) {
        global $DB;

        $data = $this->setup_data($user_properties);

        // Purge data for user1.
        $targetuser = new target_user($data->user1);
        $status = role_assignments::execute_purge($targetuser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        // Verify the expected data.
        $this->assertCount(0, $DB->get_records('role_assignments', ['userid' => $data->user1->id]));
        $this->assertCount(12, $DB->get_records('role_assignments', ['userid' => $data->user2->id]));
    }

    /**
     * Test if data is correctly purged in course context for active user.
     */
    public function test_purge_in_course_context_active_user() {
        $this->purge_in_course_context([]);
    }

    /**
     * Test if data is correctly purged in course context for suspended user.
     */
    public function test_purge_in_course_context_suspended_user() {
        $this->purge_in_course_context(['suspended' => 1]);
    }

    /**
     * Test if data is correctly purged in course context.
     *
     * @param array $user_properties
     */
    private function purge_in_course_context(array $user_properties) {
        global $DB;

        $data = $this->setup_data($user_properties);

        // Purge data in course context.
        $targetuser = new target_user($data->user1);
        $status = role_assignments::execute_purge($targetuser, context_course::instance($data->course1->id));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        // Verify the expected data.
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['system']]));
        $this->assertFalse($DB->record_exists('role_assignments', ['id' => $data->ra['course1']]));
        $this->assertFalse($DB->record_exists('role_assignments', ['id' => $data->ra['course1_role2']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['course2']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['category1']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['category2']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['user']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['program1']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['program2']]));
        $this->assertFalse($DB->record_exists('role_assignments', ['id' => $data->ra['module1']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['module2']]));
        $this->assertFalse($DB->record_exists('role_assignments', ['id' => $data->ra['block1']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['block2']]));

        // Make sure the control user's data wasn't affected.
        $this->assertCount(12, $DB->get_records('role_assignments', ['userid' => $data->user2->id]));
    }

    /**
     * Test if data is correctly purged in program context for active user.
     */
    public function test_purge_in_program_context_active_user() {
        $this->purge_in_program_context([]);
    }

    /**
     * Test if data is correctly purged in program context for suspended user.
     */
    public function test_purge_in_program_context_suspended_user() {
        $this->purge_in_program_context(['suspended' => 1]);
    }

    /**
     * Test if data is correctly purged in program context.
     *
     * @param array $user_properties
     */
    private function purge_in_program_context(array $user_properties) {
        global $DB;

        $data = $this->setup_data($user_properties);

        // Purge data in program context.
        $targetuser = new target_user($data->user1);
        $status = role_assignments::execute_purge($targetuser, context_program::instance($data->program1->id));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        // Verify the expected data.
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['system']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['course1']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['course1_role2']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['course2']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['category1']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['category2']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['user']]));
        $this->assertFalse($DB->record_exists('role_assignments', ['id' => $data->ra['program1']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['program2']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['module1']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['module2']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['block1']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['block2']]));

        // Make sure the control user's data wasn't affected.
        $this->assertCount(12, $DB->get_records('role_assignments', ['userid' => $data->user2->id]));
    }
    
    /**
     * Test if data is correctly purged in course category context for active user.
     */
    public function test_purge_in_course_category_context_active_user() {
        $this->purge_in_course_category_context([]);
    }

    /**
     * Test if data is correctly purged in course category context for suspended user.
     */
    public function test_purge_in_course_category_context_suspended_user() {
        $this->purge_in_course_category_context(['suspended' => 1]);
    }

    /**
     * Test if data is correctly purged in course category context.
     *
     * @param array $user_properties
     */
    private function purge_in_course_category_context(array $user_properties) {
        global $DB;

        $data = $this->setup_data($user_properties);

        // Purge data in course category context.
        $targetuser = new target_user($data->user1);
        $status = role_assignments::execute_purge($targetuser, context_coursecat::instance($data->category1->id));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        // Get the expected data.
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['system']]));
        $this->assertFalse($DB->record_exists('role_assignments', ['id' => $data->ra['course1']]));
        $this->assertFalse($DB->record_exists('role_assignments', ['id' => $data->ra['course1_role2']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['course2']]));
        $this->assertFalse($DB->record_exists('role_assignments', ['id' => $data->ra['category1']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['category2']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['user']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['program1']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['program2']]));
        $this->assertFalse($DB->record_exists('role_assignments', ['id' => $data->ra['module1']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['module2']]));
        $this->assertFalse($DB->record_exists('role_assignments', ['id' => $data->ra['block1']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['block2']]));

        // Make sure control user's data wasn't affected.
        $this->assertCount(12, $DB->get_records('role_assignments', ['userid' => $data->user2->id]));
    }

    /**
     * Test if data is correctly purged in user context for active user.
     */
    public function test_purge_in_user_context_active_user() {
        $this->purge_in_user_context([]);
    }

    /**
     * Test if data is correctly purged in user context for suspended user.
     */
    public function test_purge_in_user_context_suspended_user() {
        $this->purge_in_user_context(['suspended' => 1]);
    }

    /**
     * Test if data is correctly purged in user context.
     *
     * @param array $user_properties
     */
    private function purge_in_user_context(array $user_properties) {
        global $DB;

        $data = $this->setup_data($user_properties);

        // Purge data in user context.
        $targetuser = new target_user($data->user1);
        $status = role_assignments::execute_purge($targetuser, context_user::instance($data->user2->id));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        // Verify the expected data.
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['system']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['course1']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['course1_role2']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['course2']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['category1']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['category2']]));
        $this->assertFalse($DB->record_exists('role_assignments', ['id' => $data->ra['user']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['program1']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['program2']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['module1']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['module2']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['block1']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['block2']]));

        // Make sure control user's data wasn't affected.
        $this->assertCount(12, $DB->get_records('role_assignments', ['userid' => $data->user2->id]));
    }

    /**
     * Test if data is correctly purged in module context for active user.
     */
    public function test_purge_in_module_context_active_user() {
        $this->purge_in_module_context([]);
    }

    /**
     * Test if data is correctly purged in module context for suspended user.
     */
    public function test_purge_in_module_context_suspended_user() {
        $this->purge_in_module_context(['suspended' => 1]);
    }

    /**
     * Test if data is correctly purged in module context.
     *
     * @param array $user_properties
     */
    private function purge_in_module_context(array $user_properties) {
        global $DB;

        $data = $this->setup_data($user_properties);

        // Purge data in module context.
        $targetuser = new target_user($data->user1);
        $status = role_assignments::execute_purge($targetuser, context_module::instance($data->module1->cmid));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        // Verify the expected data.
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['system']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['course1']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['course1_role2']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['course2']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['category1']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['category2']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['user']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['program1']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['program2']]));
        $this->assertFalse($DB->record_exists('role_assignments', ['id' => $data->ra['module1']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['module2']]));
        $this->assertFalse($DB->record_exists('role_assignments', ['id' => $data->ra['block1']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['block2']]));

        // Make sure the control user's data wasn't affected.
        $this->assertCount(12, $DB->get_records('role_assignments', ['userid' => $data->user2->id]));
    }

    /**
     * Test if data is correctly purged in block context for active user.
     */
    public function test_purge_in_block_context_active_user() {
        $this->purge_in_block_context([]);
    }

    /**
     * Test if data is correctly purged in block context for suspended user.
     */
    public function test_purge_in_block_context_suspended_user() {
        $this->purge_in_block_context(['suspended' => 1]);
    }

    /**
     * Test if data is correctly purged in block context.
     *
     * @param array $user_properties
     */
    private function purge_in_block_context(array $user_properties) {
        global $DB;

        $data = $this->setup_data($user_properties);

        // Purge data in block context.
        $targetuser = new target_user($data->user1);
        $status = role_assignments::execute_purge($targetuser, context_block::instance($data->block1->id));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        // Verify the expected data.
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['system']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['course1']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['course1_role2']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['course2']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['category1']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['category2']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['user']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['program1']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['program2']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['module1']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['module2']]));
        $this->assertFalse($DB->record_exists('role_assignments', ['id' => $data->ra['block1']]));
        $this->assertTrue($DB->record_exists('role_assignments', ['id' => $data->ra['block2']]));

        // Make sure the control user's data wasn't affected.
        $this->assertCount(12, $DB->get_records('role_assignments', ['userid' => $data->user2->id]));
    }

    /**
     * Test if data is correctly counted for active user.
     */
    public function test_count_active_user() {
        $this->verify_count([]);
    }

    /**
     * Test if data is correctly counted for active user.
     */
    public function test_count_suspended_user() {
        $this->verify_count(['suspended' => 1]);
    }

    /**
     * Test if data can be counted for deleted user.
     */
    public function test_count_deleted_user() {
        $data = $this->setup_data();
        $data->user1->deleted = 1;
        $targetuser = new target_user($data->user1);
        // We only want to test that counting works for a target_user with deleted status. Code for user deletion actually removes
        // role assignments, but we don't want to call delete_user() here because it's expensive and not the point of the test.
        $this->assertEquals(13, role_assignments::execute_count($targetuser, context_system::instance()));
    }

    /**
     * Test if data is correctly counted.
     *
     * @param array $user_properties
     */
    private function verify_count(array $user_properties) {
        $data = $this->setup_data($user_properties);

        $targetuser = new target_user($data->user1);
        $this->assertEquals(13, role_assignments::execute_count($targetuser, context_system::instance()));
        $this->assertEquals(5, role_assignments::execute_count($targetuser, context_coursecat::instance($data->category1->id)));
        $this->assertEquals(4, role_assignments::execute_count($targetuser, context_coursecat::instance($data->category2->id)));
        $this->assertEquals(4, role_assignments::execute_count($targetuser, context_course::instance($data->course1->id)));
        $this->assertEquals(3, role_assignments::execute_count($targetuser, context_course::instance($data->course2->id)));
        $this->assertEquals(2, role_assignments::execute_count($targetuser, context_module::instance($data->module1->cmid)));
        $this->assertEquals(2, role_assignments::execute_count($targetuser, context_module::instance($data->module2->cmid)));
        $this->assertEquals(1, role_assignments::execute_count($targetuser, context_block::instance($data->block1->id)));
        $this->assertEquals(1, role_assignments::execute_count($targetuser, context_block::instance($data->block2->id)));
        $this->assertEquals(1, role_assignments::execute_count($targetuser, context_program::instance($data->program1->id)));
        $this->assertEquals(1, role_assignments::execute_count($targetuser, context_program::instance($data->program1->id)));
        $this->assertEquals(1, role_assignments::execute_count($targetuser, context_user::instance($data->user2->id)));

        $targetuser = new target_user($data->user2);
        $this->assertEquals(12, role_assignments::execute_count($targetuser, context_system::instance()));
        $this->assertEquals(4, role_assignments::execute_count($targetuser, context_coursecat::instance($data->category1->id)));
        $this->assertEquals(4, role_assignments::execute_count($targetuser, context_coursecat::instance($data->category2->id)));
        $this->assertEquals(3, role_assignments::execute_count($targetuser, context_course::instance($data->course1->id)));
        $this->assertEquals(3, role_assignments::execute_count($targetuser, context_course::instance($data->course2->id)));
        $this->assertEquals(2, role_assignments::execute_count($targetuser, context_module::instance($data->module1->cmid)));
        $this->assertEquals(2, role_assignments::execute_count($targetuser, context_module::instance($data->module2->cmid)));
        $this->assertEquals(1, role_assignments::execute_count($targetuser, context_program::instance($data->program1->id)));
        $this->assertEquals(1, role_assignments::execute_count($targetuser, context_program::instance($data->program1->id)));
        $this->assertEquals(1, role_assignments::execute_count($targetuser, context_block::instance($data->block1->id)));
        $this->assertEquals(1, role_assignments::execute_count($targetuser, context_block::instance($data->block2->id)));
        $this->assertEquals(1, role_assignments::execute_count($targetuser, context_user::instance($data->user1->id)));
    }

    /**
     * Test if data is correctly exported for active user.
     */
    public function test_export_active_user() {
        $this->verify_export([]);
    }

    /**
     * Test if data is correctly exported for active user.
     */
    public function test_export_suspended_user() {
        $this->verify_export(['suspended' => 1]);
    }
    
    /**
     * Test if data is correctly exported.
     *
     * @param array $user_properties
     */
    private function verify_export(array $user_properties) {
        $data = $this->setup_data($user_properties);

        $targetuser = new target_user($data->user1);

        $export = role_assignments::execute_export($targetuser, context_system::instance());
        $this->assert_export(array_values($data->ra), $export);

        $export = role_assignments::execute_export($targetuser, context_coursecat::instance($data->category1->id));
        $this->assert_export([$data->ra['category1'], $data->ra['course1'], $data->ra['course1_role2'], $data->ra['module1'], $data->ra['block1']], $export);

        $export = role_assignments::execute_export($targetuser, context_coursecat::instance($data->category2->id));
        $this->assert_export([$data->ra['category2'], $data->ra['course2'], $data->ra['module2'], $data->ra['block2']], $export);

        $export = role_assignments::execute_export($targetuser, context_course::instance($data->course1->id));
        $this->assert_export([$data->ra['course1'], $data->ra['course1_role2'], $data->ra['module1'], $data->ra['block1']], $export);

        $export = role_assignments::execute_export($targetuser, context_course::instance($data->course2->id));
        $this->assert_export([$data->ra['course2'], $data->ra['module2'], $data->ra['block2']], $export);

        $export = role_assignments::execute_export($targetuser, context_module::instance($data->module1->cmid));
        $this->assert_export([$data->ra['module1'], $data->ra['block1']], $export);

        $export = role_assignments::execute_export($targetuser, context_module::instance($data->module2->cmid));
        $this->assert_export([$data->ra['module2'], $data->ra['block2']], $export);

        $export = role_assignments::execute_export($targetuser, context_block::instance($data->block1->id));
        $this->assert_export([$data->ra['block1']], $export);

        $export = role_assignments::execute_export($targetuser, context_block::instance($data->block2->id));
        $this->assert_export([$data->ra['block2']], $export);

        $export = role_assignments::execute_export($targetuser, context_program::instance($data->program1->id));
        $this->assert_export([$data->ra['program1']], $export);

        $export = role_assignments::execute_export($targetuser, context_program::instance($data->program2->id));
        $this->assert_export([$data->ra['program2']], $export);

        $export = role_assignments::execute_export($targetuser, context_user::instance($data->user2->id));
        $this->assert_export([$data->ra['user']], $export);
    }

    /**
     * Test that the export contains the expected context names.
     */
    public function test_export_context_names() {
        $data = $this->setup_data();
        $targetuser = new target_user($data->user1);
        $export = role_assignments::execute_export($targetuser, context_system::instance());
        foreach ($data->ra as $role_assignment_id) {
            $this->assertEquals($data->expectedcontextnames[$role_assignment_id], $export->data[$role_assignment_id]->contextname);
        }
    }

    /**
     * @param array $expected_ra_ids
     * @param export $export
     */
    private function assert_export(array $expected_ra_ids, export $export) {
        // Assert that the export contains the expected record ids.
        $this->assertCount(count($expected_ra_ids), $export->data);
        $actual_ra_ids = array_keys($export->data);
        sort($expected_ra_ids);
        sort($actual_ra_ids);
        $this->assertEquals($expected_ra_ids, $actual_ra_ids);

        // Assert record structure.
        foreach ($export->data as $record) {
            foreach (['id', 'roleid', 'name', 'shortname', 'description', 'archetype', 'timemodified', 'contextname'] as $attribute) {
                $this->assertObjectHasAttribute($attribute, $record);
            }
        }
    }
}
