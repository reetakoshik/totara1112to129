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

use core_user\userdata\systemaccess;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Test purging, exporting and counting of username
 *
 * @group totara_userdata
 */
class core_user_userdata_systemaccess_testcase extends advanced_testcase {

    /**
     * test compatible context levels
     */
    public function test_compatible_context_levels() {
        $expectedcontextlevels = [CONTEXT_SYSTEM];
        $this->assertEquals($expectedcontextlevels, systemaccess::get_compatible_context_levels());
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge() {
        global $DB;

        $this->resetAfterTest(true);

        $activeuser = $this->getDataGenerator()->create_user();
        $suspendeduser = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $deleteduser = $this->getDataGenerator()->create_user(['deleted' => 1]);

        $suspendeduser->lastaccess = time();
        $suspendeduser->firstaccess = time() - 3;
        $suspendeduser->lastlogin = time() - 2;
        $suspendeduser->currentlogin = time() - 1;
        $suspendeduser->lastip = '192.168.178.1';
        $DB->update_record('user', $suspendeduser);

        $deleteduser->lastaccess = time();
        $deleteduser->firstaccess = time() - 3;
        $deleteduser->lastlogin = time() - 2;
        $deleteduser->currentlogin = time() - 1;
        $deleteduser->lastip = '192.168.178.1';
        $DB->update_record('user', $deleteduser);

        $DB->insert_record('user_lastaccess', (object)[
            'courseid' => 1,
            'userid' => $deleteduser->id,
            'timeaccess' => time()
        ]);
        $DB->insert_record('user_lastaccess', (object)[
            'courseid' => 2,
            'userid' => $deleteduser->id,
            'timeaccess' => time()
        ]);

        $activeuser = new target_user($activeuser);
        $suspendeduser = new target_user($suspendeduser);
        $deleteduser = new target_user($deleteduser);

        $this->assertFalse(systemaccess::is_purgeable($activeuser->status));
        $this->assertFalse(systemaccess::is_purgeable($suspendeduser->status));
        $this->assertTrue(systemaccess::is_purgeable($deleteduser->status));

        // Purge data.
        $result = systemaccess::execute_purge($deleteduser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $deleteduserreloaded = $DB->get_record('user', ['id' => $deleteduser->id]);
        $this->assertEquals('0', $deleteduserreloaded->lastaccess);
        $this->assertEquals('0', $deleteduserreloaded->firstaccess);
        $this->assertEquals('0', $deleteduserreloaded->lastlogin);
        $this->assertEquals('0', $deleteduserreloaded->currentlogin);
        $this->assertEquals('', $deleteduserreloaded->lastip);

        // Usernames of control users are untouched.
        $activeuserreloaded = $DB->get_record('user', ['id' => $activeuser->id]);
        $this->assertEquals($activeuser->lastaccess, $activeuserreloaded->lastaccess);
        $this->assertEquals($activeuser->firstaccess, $activeuserreloaded->firstaccess);
        $this->assertEquals($activeuser->lastlogin, $activeuserreloaded->lastlogin);
        $this->assertEquals($activeuser->currentlogin, $activeuserreloaded->currentlogin);
        $this->assertEquals($activeuser->lastip, $activeuserreloaded->lastip);

        $suspendeduserreloaded = $DB->get_record('user', ['id' => $suspendeduser->id]);
        $this->assertEquals($suspendeduser->lastaccess, $suspendeduserreloaded->lastaccess);
        $this->assertEquals($suspendeduser->firstaccess, $suspendeduserreloaded->firstaccess);
        $this->assertEquals($suspendeduser->lastlogin, $suspendeduserreloaded->lastlogin);
        $this->assertEquals($suspendeduser->currentlogin, $suspendeduserreloaded->currentlogin);
        $this->assertEquals($suspendeduser->lastip, $suspendeduserreloaded->lastip);
    }

    /**
     * test if data is correctly counted
     */
    public function test_count() {
        global $DB;

        $this->resetAfterTest(true);

        $activeuser = $this->getDataGenerator()->create_user();
        $suspendeduser = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $deleteduser = $this->getDataGenerator()->create_user(['deleted' => 1]);

        $suspendeduser->lastaccess = time();
        $suspendeduser->firstaccess = time() - 3;
        $suspendeduser->lastlogin = time() - 2;
        $suspendeduser->currentlogin = time() - 1;
        $suspendeduser->lastip = '192.168.178.1';
        $DB->update_record('user', $suspendeduser);

        $deleteduser->lastaccess = time();
        $deleteduser->firstaccess = time() - 3;
        $deleteduser->lastlogin = time() - 2;
        $deleteduser->currentlogin = time() - 1;
        $deleteduser->lastip = '192.168.178.1';
        $DB->update_record('user', $deleteduser);

        $DB->insert_record('user_lastaccess', (object)[
            'courseid' => 1,
            'userid' => $deleteduser->id,
            'timeaccess' => time()
        ]);
        $DB->insert_record('user_lastaccess', (object)[
            'courseid' => 2,
            'userid' => $deleteduser->id,
            'timeaccess' => time()
        ]);

        // Do the count.
        $result = systemaccess::execute_count(new target_user($deleteduser), context_system::instance());
        $this->assertEquals(7, $result);

        $result = systemaccess::execute_count(new target_user($suspendeduser), context_system::instance());
        $this->assertEquals(5, $result);

        $result = systemaccess::execute_count(new target_user($activeuser), context_system::instance());
        $this->assertEquals(0, $result);

        // Purge data.
        systemaccess::execute_purge(new target_user($deleteduser), context_system::instance());

        // Reload user.
        $deleteduserreloaded = $DB->get_record('user', ['id' => $deleteduser->id]);

        $result = systemaccess::execute_count(new target_user($deleteduserreloaded), context_system::instance());
        $this->assertEquals(0, $result);
    }


    /**
     * test if data is correctly exported
     */
    public function test_export() {
        global $DB;

        $this->resetAfterTest(true);

        // Set up users.
        $activeuser = $this->getDataGenerator()->create_user();
        $suspendeduser = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $deleteduser = $this->getDataGenerator()->create_user(['deleted' => 1]);

        $suspendeduser->lastaccess = time();
        $suspendeduser->firstaccess = time() - 3;
        $suspendeduser->lastlogin = time() - 2;
        $suspendeduser->currentlogin = time() - 1;
        $suspendeduser->lastip = '192.168.178.1';
        $DB->update_record('user', $suspendeduser);

        $deleteduser->lastaccess = time();
        $deleteduser->firstaccess = time() - 3;
        $deleteduser->lastlogin = time() - 2;
        $deleteduser->currentlogin = time() - 1;
        $deleteduser->lastip = '192.168.178.1';
        $DB->update_record('user', $deleteduser);

        $time = time();

        $DB->insert_record('user_lastaccess', (object)[
            'courseid' => 1,
            'userid' => $deleteduser->id,
            'timeaccess' => $time
        ]);
        $DB->insert_record('user_lastaccess', (object)[
            'courseid' => 2,
            'userid' => $deleteduser->id,
            'timeaccess' => $time
        ]);

        $DB->insert_record('user_lastaccess', (object)[
            'courseid' => 3,
            'userid' => $activeuser->id,
            'timeaccess' => $time
        ]);

        // Export data.
        $result = systemaccess::execute_export(new target_user($activeuser), context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertEquals([
            'firstaccess' => '',
            'lastaccess' => '',
            'lastlogin' => '',
            'currentlogin' => '',
            'lastip' => '',
            'courses' => [
                3 => $time
            ]
        ], $result->data);

        $result = systemaccess::execute_export(new target_user($suspendeduser), context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertEquals([
            'firstaccess' => $suspendeduser->firstaccess,
            'lastaccess' => $suspendeduser->lastaccess,
            'lastlogin' => $suspendeduser->lastlogin,
            'currentlogin' => $suspendeduser->currentlogin,
            'lastip' => $suspendeduser->lastip,
            'courses' => []
        ], $result->data);

        $result = systemaccess::execute_export(new target_user($deleteduser), context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertEquals([
            'firstaccess' => $deleteduser->firstaccess,
            'lastaccess' => $deleteduser->lastaccess,
            'lastlogin' => $deleteduser->lastlogin,
            'currentlogin' => $deleteduser->currentlogin,
            'lastip' => $deleteduser->lastip,
            'courses' => [
                1 => $time,
                2 => $time
            ]
        ], $result->data);
    }

}