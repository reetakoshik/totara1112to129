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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package block_recent_activity
 */

use block_recent_activity\userdata\recent_activity;
use logstore_legacy\event\unittest_executed;
use totara_userdata\userdata\target_user;
use totara_userdata\userdata\item;

defined('MOODLE_INTERNAL') || die();

/**
 * Test purging, exporting and counting of block recent activities
 * @group totara_userdata
 * @group block_recent_activity
 */
class userdata_block_recent_activity_test extends advanced_testcase {

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        global $CFG;

        require_once($CFG->dirroot . '/admin/tool/log/store/legacy/tests/fixtures/event.php');
    }

    /**
     * Test the abilities to purge, export and count
     */
    public function test_abilities() {
        $this->assertTrue(recent_activity::is_countable());
        $this->assertTrue(recent_activity::is_exportable());
        $this->assertTrue(recent_activity::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(recent_activity::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(recent_activity::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * test count when user has recent activity block
     */
    public function test_count_with_recent_activity_block() {
        global $DB;

        $this->resetAfterTest();

        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $this->create_recent_activity_block($controluser);

        // init user
        $user = $this->getDataGenerator()->create_user();
        $this->create_recent_activity_block($user);
        $targetuser = new target_user($user);

        // prove that both users have records
        $this->assertCount(2, $DB->get_records('block_recent_activity', ['userid' => $controluser->id]));
        $this->assertCount(2, $DB->get_records('block_recent_activity', ['userid' => $targetuser->id]));

        // check count
        $result = recent_activity::execute_count($targetuser, context_system::instance());
        $this->assertEquals(2, $result);
    }

    /**
     * test export when user has no recent activity block
     */
    public function test_export_with_empty_recent_activity_block() {
        $this->resetAfterTest();

        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $this->create_recent_activity_block($controluser);

        // init user
        $targetuser = new target_user($this->getDataGenerator()->create_user());

        //check export data for user
        $result = recent_activity::execute_export($targetuser, context_system::instance());
        $this->assertEmpty($result->data);
        $this->assertEmpty($result->files);
    }

    /**
     * test export when user has recent activity block
     */
    public function test_export_when_user_has_recent_activity_block() {
        $this->resetAfterTest();

        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $targetcontroluser = new target_user($controluser, context_system::instance());

        // init user
        $user = $this->getDataGenerator()->create_user();
        $this->create_recent_activity_block($user);
        $targetuser = new target_user($user);

        // check export data for user
        $result = recent_activity::execute_export($targetuser, context_system::instance());
        $this->assertCount(2, $result->data);
        $this->assertEmpty($result->files);
        foreach ($result->data as $exportitem) {
            $this->assertEquals($targetuser->id, $exportitem->userid);
            foreach (['id', 'userid', 'courseid', 'cmid', 'timecreated', 'action', 'modname'] as $attribute) {
                $this->assertObjectHasAttribute($attribute, $exportitem);
            }
        }
    }

    /**
     * test purge when active user has recent_activity_block
     */
    public function test_purge_recent_activity_block_for_active_user() {
        global $DB;

        $this->resetAfterTest();

        $activeuser = $this->getDataGenerator()->create_user();
        $suspendeduser = $this->getDataGenerator()->create_user(['suspended' => 1]);

        $this->create_recent_activity_block($activeuser);
        $this->create_recent_activity_block($suspendeduser);

        $targetactiveuser = new target_user($activeuser);

        // before purge
        $logcount = recent_activity::execute_count($targetactiveuser, context_system::instance());
        $this->assertEquals(2, $logcount);

        // purge log records
        $result = recent_activity::execute_purge($targetactiveuser, context_system::instance());

        // after purge
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $activitycount = recent_activity::execute_count($targetactiveuser, context_system::instance());
        $this->assertEquals(0, $activitycount);

        $activityrecords = $DB->get_records('block_recent_activity', ['userid' => $activeuser->id]);
        $this->assertEmpty($activityrecords);

        // Check suspended users not affected
        $suspendedactivitycount = recent_activity::execute_count(new target_user($suspendeduser), context_system::instance());
        $this->assertEquals(2, $suspendedactivitycount);
    }

    /**
     * test purge when suspended user has recent_activity_block
     */
    public function test_purge_for_suspended_user() {
        global $DB;

        $this->resetAfterTest();

        $suspendeduser = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $activeuser = $this->getDataGenerator()->create_user();

        $this->create_recent_activity_block($suspendeduser);
        $this->create_recent_activity_block($activeuser);

        $targetsuspendeduser = new target_user($suspendeduser);

        // before purge
        $logcount = recent_activity::execute_count($targetsuspendeduser, context_system::instance());
        $this->assertEquals(2, $logcount);

        // purge log records
        $result = recent_activity::execute_purge($targetsuspendeduser, context_system::instance());

        // after purge
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $activitycount = recent_activity::execute_count($targetsuspendeduser, context_system::instance());
        $this->assertEquals(0, $activitycount);

        $activityrecords = $DB->get_records('block_recent_activity', ['userid' => $suspendeduser->id]);
        $this->assertEmpty($activityrecords);

        // Check active users not affected
        $activeactivitycount = recent_activity::execute_count(new target_user($activeuser), context_system::instance());
        $this->assertEquals(2, $activeactivitycount);
    }

    /**
     * test purge when deleted user has recent_activity_block
     */
    public function test_purge_for_deleted_user() {
        global $DB;

        $this->resetAfterTest();

        $deleteduser = $this->getDataGenerator()->create_user();
        $activeuser = $this->getDataGenerator()->create_user();

        $this->create_recent_activity_block($deleteduser);
        $this->create_recent_activity_block($activeuser);

        $deleteduser->deleted = 1;
        $targetdeleteduser = new target_user($deleteduser);

        // before purge
        $logcount = recent_activity::execute_count($targetdeleteduser, context_system::instance());
        $this->assertEquals(2, $logcount);

        // purge log records
        $result = recent_activity::execute_purge($targetdeleteduser, context_system::instance());

        // after purge
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $activitycount = recent_activity::execute_count($targetdeleteduser, context_system::instance());
        $this->assertEquals(0, $activitycount);

        $activityrecords = $DB->get_records('block_recent_activity', ['userid' => $deleteduser->id]);
        $this->assertEmpty($activityrecords);

        // Check active users not affected
        $activeactivitycount = recent_activity::execute_count(new target_user($activeuser), context_system::instance());
        $this->assertEquals(2, $activeactivitycount);
    }

    /**
     * Create Test Recent activity block
     *
     * @param $user
     */
    private function create_recent_activity_block($user) {
        global $CFG;

        $this->setUser($user);
        $this->getDataGenerator()->create_block('recent_activity');
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('resource', array('course' => $course));

        // TOTARA: Add missing require.
        require_once($CFG->dirroot.'/admin/tool/log/store/legacy/tests/fixtures/event.php');

        $event = unittest_executed::create(
            array('context' => context_module::instance($module->cmid), 'other' => array('sample' => 1)));
        $event->trigger();
    }
}
