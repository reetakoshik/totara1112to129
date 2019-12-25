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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core_user
 */

use core_user\userdata\private_keys;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Test setting to keep or user_private_keys
 *
 * @group totara_userdata
 */
class core_user_userdata_private_keys_test extends advanced_testcase {

    /**
     * Include necessary file.
     */
    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        global $CFG;
        require_once($CFG->dirroot . '/webservice/lib.php');
    }

    /**
     * set up tests
     */
    protected function setUp() {
        parent::setUp();

        $this->resetAfterTest(true);
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge_in_system_context() {
        global $DB;

        // Set up users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Create courses.
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        // Create user keys.
        create_user_key('test1', $user1->id, $course1->id);
        create_user_key('test2', $user1->id, $course2->id);
        create_user_key('test', $user2->id, $course1->id);

        // Get the expected data.
        $this->assertCount(2, $DB->get_records('user_private_key', ['userid' => $user1->id]));
        // Check if second users record is untouched.
        $this->assertCount(1, $DB->get_records('user_private_key', ['userid' => $user2->id]));

        // Purge data in System context.
        $targetuser = new target_user($user1);
        $status = private_keys::execute_purge($targetuser, context_system::instance());

        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        // Get the expected data.
        $this->assertCount(0, $DB->get_records('user_private_key', ['userid' => $user1->id]));
        // Check if second users record is untouched.
        $this->assertCount(1, $DB->get_records('user_private_key', ['userid' => $user2->id]));
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge_in_course_context() {
        global $DB;

        // Set up users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Create courses.
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        // Create user keys.
        create_user_key('test1', $user1->id, $course1->id);
        create_user_key('test2', $user1->id, $course2->id);
        create_user_key('test3', $user2->id, $course1->id);

        // Check the expected data which should be there before the purge.
        $actualkey1 = $DB->get_records('user_private_key', ['userid' => $user1->id, 'instance' => $course1->id]);
        $actualkey2 = $DB->get_records('user_private_key', ['userid' => $user1->id, 'instance' => $course2->id]);
        $actualkey3 = $DB->get_records('user_private_key', ['userid' => $user2->id, 'instance' => $course1->id]);

        $this->assertCount(1, $actualkey1);
        $this->assertCount(1, $actualkey2);
        $this->assertCount(1, $actualkey3);

        // Purge data in course context.
        $targetuser = new target_user($user1);
        $status = private_keys::execute_purge($targetuser, context_course::instance($course2->id));

        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        // Get the expected data.
        $actualkey1 = $DB->get_records('user_private_key', ['userid' => $user1->id, 'instance' => $course1->id]);
        $actualkey2 = $DB->get_records('user_private_key', ['userid' => $user1->id, 'instance' => $course2->id]);
        $actualkey3 = $DB->get_records('user_private_key', ['userid' => $user2->id, 'instance' => $course1->id]);

        $this->assertCount(1, $actualkey1);
        $this->assertCount(0, $actualkey2);
        $this->assertCount(1, $actualkey3);
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge_in_course_category_context() {
        global $DB;

        // Set up users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();

        // Create courses.
        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $category2->id]);

        // Create user keys.
        create_user_key('test1', $user1->id, $course1->id);
        create_user_key('test2', $user1->id, $course2->id);
        create_user_key('test3', $user2->id, $course1->id);

        // Check the expected data which should be there before the purge.
        $actualkey1 = $DB->get_records('user_private_key', ['userid' => $user1->id, 'instance' => $course1->id]);
        $actualkey2 = $DB->get_records('user_private_key', ['userid' => $user1->id, 'instance' => $course2->id]);
        $actualkey3 = $DB->get_records('user_private_key', ['userid' => $user2->id, 'instance' => $course1->id]);

        $this->assertCount(1, $actualkey1);
        $this->assertCount(1, $actualkey2);
        $this->assertCount(1, $actualkey3);

        // Purge data in course context.
        $targetuser = new target_user($user1);
        $status = private_keys::execute_purge($targetuser, context_coursecat::instance($category1->id));

        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        // Get the expected data.
        $actualkey1 = $DB->get_records('user_private_key', ['userid' => $user1->id, 'instance' => $course1->id]);
        $actualkey2 = $DB->get_records('user_private_key', ['userid' => $user1->id, 'instance' => $course2->id]);
        $actualkey3 = $DB->get_records('user_private_key', ['userid' => $user2->id, 'instance' => $course1->id]);

        $this->assertCount(0, $actualkey1);
        $this->assertCount(1, $actualkey2);
        $this->assertCount(1, $actualkey3);
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge_no_entries_related_to_course() {
        global $DB;

        // Set up users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Create courses.
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        // Create user keys.
        create_user_key('test1', $user1->id, $course1->id);
        create_user_key('test2', $user1->id, $course2->id);
        create_user_key('test3', $user2->id, $course1->id);

        // Check the expected data which should be there before the purge.
        $actualkey1 = $DB->get_records('user_private_key', ['userid' => $user1->id, 'instance' => $course1->id]);
        $actualkey2 = $DB->get_records('user_private_key', ['userid' => $user1->id, 'instance' => $course2->id]);
        $actualkey3 = $DB->get_records('user_private_key', ['userid' => $user2->id, 'instance' => $course1->id]);

        $this->assertCount(1, $actualkey1);
        $this->assertCount(1, $actualkey2);
        $this->assertCount(1, $actualkey3);

        $targetuser = new target_user($user1);

        // Purge data in course context.
        $status = private_keys::execute_purge($targetuser, context_course::instance($course3->id));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        // Data is still there.
        $this->assertCount(3, $DB->get_records('user_private_key'));
    }

    /**
     * test purge in invalid contexts
     */
    public function test_general() {
        $contextlevels = private_keys::get_compatible_context_levels();

        $this->assertCount(3, $contextlevels);
        $this->assertContains(CONTEXT_SYSTEM, $contextlevels);
        $this->assertContains(CONTEXT_COURSE, $contextlevels);
        $this->assertContains(CONTEXT_COURSECAT, $contextlevels);

        $this->assertFalse(private_keys::is_exportable());
        $this->assertTrue(private_keys::is_countable());
        $this->assertTrue(private_keys::is_purgeable(target_user::STATUS_ACTIVE));
    }

    /**
     * test if data is correctly exported
     */
    public function test_count() {
        // Set up users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();

        // Create courses.
        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $category2->id]);
        $course3 = $this->getDataGenerator()->create_course(['category' => $category2->id]);

        // Create user keys.
        create_user_key('test1', $user1->id, $course1->id);
        create_user_key('test2', $user1->id, $course2->id);
        create_user_key('test3', $user2->id, $course1->id);

        $targetuser = new target_user($user1);

        // Export data with system context.
        $count = private_keys::execute_count($targetuser, context_system::instance());
        $this->assertEquals(2, $count);

        // Export data with course context.
        $count = private_keys::execute_count($targetuser, context_course::instance($course2->id));
        $this->assertEquals(1, $count);

        // Export data with course category context.
        $count = private_keys::execute_count($targetuser, context_coursecat::instance($category1->id));
        $this->assertEquals(1, $count);

        // Export data with no data in context.
        $count = private_keys::execute_count($targetuser, context_course::instance($course3->id));
        $this->assertEquals(0, $count);
    }

}
