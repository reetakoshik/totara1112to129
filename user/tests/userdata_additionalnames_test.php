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

use core_user\userdata\additionalnames;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Test purging, exporting and counting of user's additional names, like middlename, alternatename, etc.
 *
 * @group totara_userdata
 */
class core_user_userdata_additionalnames_testcase extends advanced_testcase {

    /**
     * test compatible context levels
     */
    public function test_compatible_context_levels() {
        $expectedcontextlevels = [CONTEXT_SYSTEM];
        $this->assertEquals($expectedcontextlevels, additionalnames::get_compatible_context_levels());
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge() {
        global $DB;

        $this->resetAfterTest(true);

        /******************************
         * PREPARE USERS
         *****************************/

        // Control user.
        $controluser = $this->getDataGenerator()->create_user();
        // Active user with all names.
        $user = new target_user($this->getDataGenerator()->create_user());
        // Deleted user with all names.
        $deleteduser = new target_user($this->getDataGenerator()->create_user(['deleted' => 1]));
        // Suspended user with all names.
        $suspendeduser = new target_user($this->getDataGenerator()->create_user(['suspended' => 1]));

        $this->assertTrue(additionalnames::is_purgeable($user->status));
        $this->assertTrue(additionalnames::is_purgeable($deleteduser->status));
        $this->assertTrue(additionalnames::is_purgeable($suspendeduser->status));

        // To test if timemodified changed we need to pause for a second.
        sleep(1);

        // Triggering and capturing the event.
        $sink = $this->redirectEvents();

        /******************************
         * PURGE user
         *****************************/

        $result = additionalnames::execute_purge($user, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        // Checking that the correct event was fired.
        $this->assertInstanceOf(\core\event\user_updated::class, reset($events));
        $sink->clear();

        $userreloaded = $DB->get_record('user', ['id' => $user->id]);

        // All names are purged.
        $this->assertEquals('', $userreloaded->firstnamephonetic);
        $this->assertEquals('', $userreloaded->lastnamephonetic);
        $this->assertEquals('', $userreloaded->middlename);
        $this->assertEquals('', $userreloaded->alternatename);
        // Time modified was updated.
        $this->assertGreaterThan($user->timemodified, $userreloaded->timemodified);

        /******************************
         * PURGE deleteduser
         *****************************/

        $result = additionalnames::execute_purge($deleteduser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        // For a deleted user there shouldn't be an event fired.
        $events = $sink->get_events();
        $this->assertCount(0, $events);

        $deleteduserreloaded = $DB->get_record('user', ['id' => $deleteduser->id]);

        // All names are purged.
        $this->assertEquals('', $deleteduserreloaded->firstnamephonetic);
        $this->assertEquals('', $deleteduserreloaded->lastnamephonetic);
        $this->assertEquals('', $deleteduserreloaded->middlename);
        $this->assertEquals('', $deleteduserreloaded->alternatename);
        // Time modified should not have changed.
        $this->assertEquals($deleteduser->timemodified, $deleteduserreloaded->timemodified);

        /******************************
         * PURGE suspendeduser
         *****************************/

        $result = additionalnames::execute_purge($suspendeduser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        // Checking that the correct event was fired.
        $this->assertInstanceOf(\core\event\user_updated::class, reset($events));
        $sink->clear();

        $suspendeduserreloaded = $DB->get_record('user', ['id' => $suspendeduser->id]);

        // All names are purged.
        $this->assertEquals('', $suspendeduserreloaded->firstnamephonetic);
        $this->assertEquals('', $suspendeduserreloaded->lastnamephonetic);
        $this->assertEquals('', $suspendeduserreloaded->middlename);
        $this->assertEquals('', $suspendeduserreloaded->alternatename);
        // Time modified was updated.
        $this->assertGreaterThan($suspendeduser->timemodified, $suspendeduserreloaded->timemodified);

        /******************************
         * CHECK controluser
         *****************************/

        $controluserreloaded = $DB->get_record('user', ['id' => $controluser->id]);

        // Names of control user are untouched.
        $this->assertEquals($controluser->firstnamephonetic, $controluserreloaded->firstnamephonetic);
        $this->assertEquals($controluser->lastnamephonetic, $controluserreloaded->lastnamephonetic);
        $this->assertEquals($controluser->middlename, $controluserreloaded->middlename);
        $this->assertEquals($controluser->alternatename, $controluserreloaded->alternatename);
    }

    /**
     * test if data is correctly counted
     */
    public function test_count() {
        $this->resetAfterTest(true);

        // Set up users.
        // 4 names.
        $user1 = $this->getDataGenerator()->create_user();
        // 3 names.
        $user2 = $this->getDataGenerator()->create_user([
            'middlename' => ''
        ]);
        // 2 names.
        $user3 = $this->getDataGenerator()->create_user([
            'middlename' => '',
            'alternatename' => ''
        ]);
        // 1 name.
        $user4 = $this->getDataGenerator()->create_user([
            'firstnamephonetic' => '',
            'middlename' => '',
            'alternatename' => ''
        ]);
        // 0 names.
        $user5 = $this->getDataGenerator()->create_user([
            'lastnamephonetic' => '',
            'firstnamephonetic' => '',
            'middlename' => '',
            'alternatename' => ''
        ]);

        // Do the count.
        $result = additionalnames::execute_count(new target_user($user1), context_system::instance());
        $this->assertEquals(4, $result);
        $result = additionalnames::execute_count(new target_user($user2), context_system::instance());
        $this->assertEquals(3, $result);
        $result = additionalnames::execute_count(new target_user($user3), context_system::instance());
        $this->assertEquals(2, $result);
        $result = additionalnames::execute_count(new target_user($user4), context_system::instance());
        $this->assertEquals(1, $result);
        $result = additionalnames::execute_count(new target_user($user5), context_system::instance());
        $this->assertEquals(0, $result);
    }


    /**
     * test if data is correctly counted
     */
    public function test_export() {
        $this->resetAfterTest(true);

        // Set up users.
        // 4 names.
        $user1 = $this->getDataGenerator()->create_user();
        // 3 names.
        $user2 = $this->getDataGenerator()->create_user([
            'middlename' => ''
        ]);
        // 2 names.
        $user3 = $this->getDataGenerator()->create_user([
            'middlename' => '',
            'alternatename' => ''
        ]);
        // 1 name.
        $user4 = $this->getDataGenerator()->create_user([
            'firstnamephonetic' => '',
            'middlename' => '',
            'alternatename' => ''
        ]);
        // 0 names.
        $user5 = $this->getDataGenerator()->create_user([
            'lastnamephonetic' => '',
            'firstnamephonetic' => '',
            'middlename' => '',
            'alternatename' => ''
        ]);

        // Export data.
        $result = additionalnames::execute_export(new target_user($user1), context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertEquals([
            'lastnamephonetic' => $user1->lastnamephonetic,
            'firstnamephonetic' => $user1->firstnamephonetic,
            'middlename' => $user1->middlename,
            'alternatename' => $user1->alternatename
        ], $result->data);

        $result = additionalnames::execute_export(new target_user($user2), context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertEquals([
            'lastnamephonetic' => $user2->lastnamephonetic,
            'firstnamephonetic' => $user2->firstnamephonetic,
            'middlename' => '',
            'alternatename' => $user2->alternatename
        ], $result->data);

        $result = additionalnames::execute_export(new target_user($user3), context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertEquals([
            'lastnamephonetic' => $user3->lastnamephonetic,
            'firstnamephonetic' => $user3->firstnamephonetic,
            'middlename' => '',
            'alternatename' => ''
        ], $result->data);

        $result = additionalnames::execute_export(new target_user($user4), context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertEquals([
            'lastnamephonetic' => $user4->lastnamephonetic,
            'firstnamephonetic' => '',
            'middlename' => '',
            'alternatename' => ''
        ], $result->data);

        $result = additionalnames::execute_export(new target_user($user5), context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertEquals([
            'lastnamephonetic' => '',
            'firstnamephonetic' => '',
            'middlename' => '',
            'alternatename' => ''
        ], $result->data);
    }

}