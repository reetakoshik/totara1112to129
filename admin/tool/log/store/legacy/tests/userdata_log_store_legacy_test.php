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
 * @package logstore_legacy
 */

use logstore_legacy\userdata\legacy_log;
use logstore_legacy\event\unittest_executed;
use totara_userdata\userdata\target_user;
use totara_userdata\userdata\item;

defined('MOODLE_INTERNAL') || die();

/**
 * Test purging, exporting and counting of legacy logs
 * @group totara_userdata
 * @group logstore_legacy
 */
class logstore_legacy_userdata_log_testcase extends advanced_testcase {

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        require_once(__DIR__ . '/fixtures/event.php');
    }

    /**
     * Test the abilities to purge, export and count
     */
    public function test_abilities() {
        $this->assertTrue(legacy_log::is_countable());
        $this->assertTrue(legacy_log::is_exportable());
        $this->assertTrue(legacy_log::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(legacy_log::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(legacy_log::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * test count when user has log records
     */
    public function test_count_when_user_has_log_records() {
        global $DB;

        $this->resetAfterTest(true);
        $this->enable_legacy_log();

        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $this->create_test_log($controluser);

        // init user
        $user = $this->getDataGenerator()->create_user();
        $this->create_test_log($user);
        $targetuser = new target_user($user);

        // prove that both users have records
        $this->assertCount(6, $DB->get_records('log', ['userid' => $controluser->id]));
        $this->assertCount(6, $DB->get_records('log', ['userid' => $targetuser->id]));

        // check count
        $result = legacy_log::execute_count($targetuser, context_system::instance());
        $this->assertEquals(6, $result);
    }

    /**
     * test export when user has no log records
     */
    public function test_export_when_user_has_no_log_records() {
        $this->resetAfterTest(true);
        $this->enable_legacy_log();

        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $this->create_test_log($controluser);

        // init user
        $targetuser = new target_user($this->getDataGenerator()->create_user());

        //check export data for user
        $result = legacy_log::execute_export($targetuser, context_system::instance());
        $this->assertEmpty($result->data);
        $this->assertEmpty($result->files);
    }

    /**
     * test export when user has log records
     */
    public function test_export_when_user_has_log_records() {
        $this->resetAfterTest();
        $this->enable_legacy_log();

        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $this->create_test_log($controluser);

        // init user
        $user = $this->getDataGenerator()->create_user();
        $this->create_test_log($user);
        $targetuser = new target_user($user);

        // check export data for user
        $result = legacy_log::execute_export($targetuser, context_system::instance());
        $this->assertCount(6, $result->data);
        $this->assertEmpty($result->files);
        foreach ($result->data as $exportitem) {
            $this->assertEquals($targetuser->id, $exportitem->userid);
            foreach (['id', 'userid', 'time', 'ip', 'course', 'module', 'action', 'url', 'info'] as $attribute) {
                $this->assertObjectHasAttribute($attribute, $exportitem);
            }
        }
    }

    /**
     * test purge when active user has log records
     */
    public function test_purge_when_active_user_has_log_records() {
        global $DB;

        $this->resetAfterTest();
        $this->enable_legacy_log();

        // init users
        $activeuser = $this->getDataGenerator()->create_user();
        $suspendeduser = $this->getDataGenerator()->create_user(['suspended' => 1]);

        $this->create_test_log($activeuser);
        $this->create_test_log($suspendeduser);

        $targetactiveuser = new target_user($activeuser);

        // before purge
        $logcount = legacy_log::execute_count($targetactiveuser, context_system::instance());
        $this->assertEquals(6, $logcount);

        // purge log records
        $result = legacy_log::execute_purge($targetactiveuser, context_system::instance());

        // after purge
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $purgelogdata = $DB->get_records('log', ['userid' => $activeuser->id]);
        foreach ($purgelogdata as $logitem) {
            $this->assertEmpty($logitem->ip);
            $this->assertEmpty($logitem->info);
        }

        // Check suspended users not affected
        $logdata = $DB->get_records('log', ['userid' => $suspendeduser->id]);
        foreach ($logdata as $logitem) {
            $this->assertNotEmpty($logitem->ip);
            $this->assertNotEmpty($logitem->info);
        }
    }

    /**
     * test purge when suspended user has log records
     */
    public function test_purge_when_suspended_user_has_log_records() {
        global $DB;

        $this->resetAfterTest();
        $this->enable_legacy_log();

        // init users
        $suspendeduser = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $activeuser = $this->getDataGenerator()->create_user();

        $this->create_test_log($suspendeduser);
        $this->create_test_log($activeuser);

        $targetsuspendeduser = new target_user($suspendeduser);

        // before purge
        $logcount = legacy_log::execute_count($targetsuspendeduser, context_system::instance());
        $this->assertEquals(6, $logcount);

        // purge log records
        $result = legacy_log::execute_purge($targetsuspendeduser, context_system::instance());

        // after purge
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $purgelogdata = $DB->get_records('log', ['userid' => $suspendeduser->id]);
        foreach ($purgelogdata as $logitem) {
            $this->assertEmpty($logitem->ip);
            $this->assertEmpty($logitem->info);
        }

        // Check active users not affected
        $logdata = $DB->get_records('log', ['userid' => $activeuser->id]);
        foreach ($logdata as $logitem) {
            $this->assertNotEmpty($logitem->ip);
            $this->assertNotEmpty($logitem->info);
        }
    }

    /**
     * test purge when deleted user has log records
     */
    public function test_purge_when_deleted_user_has_log_records() {
        global $DB;

        $this->resetAfterTest();
        $this->enable_legacy_log();

        // init users
        $deleteduser = $this->getDataGenerator()->create_user();
        $activeuser = $this->getDataGenerator()->create_user();

        $this->create_test_log($deleteduser);
        $this->create_test_log($activeuser);

        $deleteduser->deleted = 1;
        $targetdeleteduser = new target_user($deleteduser);

        // before purge
        $logcount = legacy_log::execute_count($targetdeleteduser, context_system::instance());
        $this->assertEquals(6, $logcount);

        // purge log records
        $result = legacy_log::execute_purge($targetdeleteduser, context_system::instance());

        // after purge
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $purgelogdata = $DB->get_records('log', ['userid' => $deleteduser->id]);
        foreach ($purgelogdata as $logitem) {
            $this->assertEmpty($logitem->ip);
            $this->assertEmpty($logitem->info);
        }

        // Check active users not affected
        $logdata = $DB->get_records('log', ['userid' => $activeuser->id]);
        foreach ($logdata as $logitem) {
            $this->assertNotEmpty($logitem->ip);
            $this->assertNotEmpty($logitem->info);
        }
    }

    /**
     * test purge when user has log records
     */
    public function test_purge_with_empty_log_records() {
        $this->resetAfterTest();
        $this->enable_legacy_log();

        $user = $this->getDataGenerator()->create_user();
        $targetuser = new target_user($user);

        // before purge
        $logcount = legacy_log::execute_count($targetuser, context_system::instance());
        $this->assertEquals(0, $logcount);

        // purge log records
        $result = legacy_log::execute_purge($targetuser, context_system::instance());

        // after purge
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
    }

    /**
     * Create Test log
     *
     * @param $user
     */
    private function create_test_log($user) {
        global $CFG;

        $this->setUser($user);
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('resource', array('course' => $course));

        // TOTARA: add missing require.
        require_once($CFG->dirroot.'/admin/tool/log/store/legacy/tests/fixtures/event.php');

        $event = unittest_executed::create(
            array('context' => context_module::instance($module->cmid), 'other' => array('sample' => 1)));
        $event->trigger();
    }

    /**
     * Enable Legacy Log module
     */
    private function enable_legacy_log() {
        set_config('enabled_stores', 'logstore_legacy', 'tool_log');
        set_config('loglegacy', 1, 'logstore_legacy');
    }
}
