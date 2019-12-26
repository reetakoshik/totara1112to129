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

use logstore_standard\userdata\log;
use logstore_standard\event\unittest_executed;
use totara_userdata\userdata\target_user;
use totara_userdata\userdata\item;

defined('MOODLE_INTERNAL') || die();

/**
 * Test purging, exporting and counting of standard logs
 * @group totara_userdata
 * @group logstore_standard
 */
class logstore_standard_userdata_log_testcase extends advanced_testcase {

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        require_once(__DIR__ . '/fixtures/event.php');
    }

    /**
     * Test the abilities to purge, export and count
     */
    public function test_abilities() {
        $this->assertTrue(log::is_countable());
        $this->assertTrue(log::is_exportable());
        $this->assertTrue(log::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(log::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(log::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * test count when user has log records
     */
    public function test_count_when_user_has_log_records() {
        global $DB;

        $this->resetAfterTest();
        $this->enable_standards_log();

        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $this->create_test_log($controluser);

        // init user
        $user = $this->getDataGenerator()->create_user();
        $this->create_test_log($user);
        $targetuser = new target_user($user);

        // prove that both users have records
        $this->assertCount(13, $DB->get_records('logstore_standard_log', ['userid' => $controluser->id]));
        $this->assertCount(13, $DB->get_records('logstore_standard_log', ['userid' => $targetuser->id]));

        // check count
        $result = log::execute_count($targetuser, context_system::instance());
        $this->assertEquals(13, $result);
    }

    /**
     * test export when user has no log records
     */
    public function test_export_when_user_has_no_log_records() {
        $this->resetAfterTest();
        $this->enable_standards_log();

        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $this->create_test_log($controluser);

        // init user
        $user = $this->getDataGenerator()->create_user();
        $targetuser = new target_user($user);

        // check export data for user
        $result = log::execute_export($targetuser, context_system::instance());
        $this->assertEmpty($result->data);
        $this->assertEmpty($result->files);
    }

    /**
     * test export when user has log records
     */
    public function test_export_when_user_has_log_records() {
        $this->resetAfterTest();
        $this->enable_standards_log();

        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $this->create_test_log($controluser);

        // init user
        $user = $this->getDataGenerator()->create_user();
        $this->create_test_log($user);
        $targetuser = new target_user($user);

        // check export data for user
        $result = log::execute_export($targetuser, context_system::instance());
        $this->assertCount(13, $result->data);
        $this->assertEmpty($result->files);
        foreach ($result->data as $exportitem) {
            $this->assertEquals($targetuser->id, $exportitem->userid);
            foreach (['id', 'userid', 'timecreated', 'ip', 'eventname', 'component', 'action', 'target', 'objecttable', 'crud'] as $attribute) {
                $this->assertObjectHasAttribute($attribute, $exportitem);
            }
        }
    }

    /**
     * test purge when active user has log records
     */
    public function test_purge_for_active_user() {
        global $DB;

        $this->resetAfterTest();
        $this->enable_standards_log();

        $activeuser = $this->getDataGenerator()->create_user();
        $suspendeduser = $this->getDataGenerator()->create_user(['suspended' => 1]);

        $this->create_test_log($activeuser);
        $this->create_test_log($suspendeduser);

        $targetactiveuser = new target_user($activeuser);

        // before purge
        $logcount = log::execute_count($targetactiveuser, context_system::instance());
        $this->assertEquals(13, $logcount);

        // purge log records
        $result = log::execute_purge($targetactiveuser, context_system::instance());

        // after purge
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $purgelogdata = $DB->get_records('logstore_standard_log', ['userid' => $activeuser->id]);
        foreach ($purgelogdata as $logitem) {
            $this->assertEmpty($logitem->ip);
            $this->assertEmpty($logitem->other);
        }

        // Check suspended users not affected
        $logdata = $DB->get_records('logstore_standard_log', ['userid' => $suspendeduser->id]);
        foreach ($logdata as $logitem) {
            $this->assertNotEmpty($logitem->other);
        }
    }

    /**
     * test purge when suspended user has log records
     */
    public function test_purge_for_suspended_user() {
        global $DB;

        $this->resetAfterTest();
        $this->enable_standards_log();

        $suspendeduser = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $activeuser = $this->getDataGenerator()->create_user();

        $this->create_test_log($suspendeduser);
        $this->create_test_log($activeuser);

        $targetsuspendeduser = new target_user($suspendeduser);

        // before purge
        $logcount = log::execute_count($targetsuspendeduser, context_system::instance());
        $this->assertEquals(13, $logcount);

        // purge log records
        $result = log::execute_purge($targetsuspendeduser, context_system::instance());

        // after purge
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $purgelogdata = $DB->get_records('logstore_standard_log', ['userid' => $suspendeduser->id]);
        foreach ($purgelogdata as $logitem) {
            $this->assertEmpty($logitem->ip);
            $this->assertEmpty($logitem->other);
        }

        // Check active users not affected
        $logdata = $DB->get_records('logstore_standard_log', ['userid' => $activeuser->id]);

        foreach ($logdata as $logitem) {
            $this->assertNotEmpty($logitem->other);
        }
    }

    /**
     * test purge when deleted user has log records
     */
    public function test_purge_for_deleted_user() {
        global $DB;

        $this->resetAfterTest();
        $this->enable_standards_log();

        $deleteduser = $this->getDataGenerator()->create_user();
        $activeuser = $this->getDataGenerator()->create_user();

        $this->create_test_log($deleteduser);
        $this->create_test_log($activeuser);

        $deleteduser->deleted = 1;
        $targetdeleteduser = new target_user($deleteduser);

        // before purge
        $logcount = log::execute_count($targetdeleteduser, context_system::instance());
        $this->assertEquals(13, $logcount);

        // purge log records
        $result = log::execute_purge($targetdeleteduser, context_system::instance());

        // after purge
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $purgelogdata = $DB->get_records('logstore_standard_log', ['userid' => $deleteduser->id]);
        foreach ($purgelogdata as $logitem) {
            $this->assertEmpty($logitem->ip);
            $this->assertEmpty($logitem->other);
        }

        // Check active users not affected
        $logdata = $DB->get_records('logstore_standard_log', ['userid' => $activeuser->id]);
        foreach ($logdata as $logitem) {
            $this->assertNotEmpty($logitem->other);
        }
    }

    /**
     * test purge when user has log records
     */
    public function test_purge_with_empty_log_records() {
        $this->resetAfterTest();
        $this->enable_standards_log();

        $user = $this->getDataGenerator()->create_user();
        $targetuser = new target_user($user);

        // before purge
        $logcount = log::execute_count($targetuser, context_system::instance());
        $this->assertEquals(0, $logcount);

        // purge log records
        $result = log::execute_purge($targetuser, context_system::instance());

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

        // TOTARA: Add missing require.
        require_once($CFG->dirroot.'/admin/tool/log/store/legacy/tests/fixtures/event.php');

        $event = unittest_executed::create(
            array('context' => context_module::instance($module->cmid), 'other' => array('sample' => 1)));
        $event->trigger();
    }

    /**
     * Enable Legacy Log module
     */
    private function enable_standards_log() {
        set_config('enabled_stores', 'logstore_standard', 'tool_log');
        set_config('buffersize', 0, 'logstore_standard');
        set_config('logguests', 1, 'logstore_standard');
    }
}
