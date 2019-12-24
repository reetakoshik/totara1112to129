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

use core_user\userdata\names;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Test purging, exporting and counting of user first and lastname
 *
 * @group totara_userdata
 */
class core_user_userdata_names_testcase extends advanced_testcase {

    /**
     * test compatible context levels
     */
    public function test_compatible_context_levels() {
        $expectedcontextlevels = [CONTEXT_SYSTEM];
        $this->assertEquals($expectedcontextlevels, names::get_compatible_context_levels());
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge() {
        global $DB;

        $this->resetAfterTest(true);

        $activeuser = new target_user($this->getDataGenerator()->create_user());
        $suspendeduser = new target_user($this->getDataGenerator()->create_user(['suspended' => 1]));
        $deleteduser = new target_user($this->getDataGenerator()->create_user(['deleted' => 1]));

        $this->assertFalse(names::is_purgeable($activeuser->status));
        $this->assertFalse(names::is_purgeable($suspendeduser->status));
        $this->assertTrue(names::is_purgeable($deleteduser->status));

        // Purge data.
        $result = names::execute_purge($deleteduser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $actualrecord = $DB->get_record('user', ['id' => $deleteduser->id]);
        $this->assertEquals('?', $actualrecord->firstname);
        $this->assertEquals('?', $actualrecord->lastname);

        // Names of control users are untouched.
        $actualrecord = $DB->get_record('user', ['id' => $activeuser->id]);
        $this->assertEquals($activeuser->firstname, $actualrecord->firstname);
        $this->assertEquals($activeuser->lastname, $actualrecord->lastname);

        $actualrecord = $DB->get_record('user', ['id' => $suspendeduser->id]);
        $this->assertEquals($suspendeduser->firstname, $actualrecord->firstname);
        $this->assertEquals($suspendeduser->lastname, $actualrecord->lastname);
    }

    /**
     * test if data is correctly counted
     */
    public function test_count() {
        global $DB;

        $this->resetAfterTest(true);

        // Set up users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user(['deleted' => 1]);

        // Do the count.
        $result = names::execute_count(new target_user($user1), context_system::instance());
        $this->assertEquals(1, $result);
        $result = names::execute_count(new target_user($user2), context_system::instance());
        $this->assertEquals(1, $result);

        // Purge data.
        names::execute_purge(new target_user($user2), context_system::instance());

        // Reload user from DB.
        $user2 = $DB->get_record('user', ['id' => $user2->id]);

        // After purge count should be 0.
        $result = names::execute_count(new target_user($user2), context_system::instance());
        $this->assertEquals(0, $result);
    }


    /**
     * test if data is correctly counted
     */
    public function test_export() {
        global $DB;

        $this->resetAfterTest(true);

        // Set up users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user(['deleted' => 1]);

        // Export data.
        $result = names::execute_export(new target_user($user1), context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertEquals(['firstname' => $user1->firstname, 'lastname' => $user1->lastname], $result->data);

        // User 2.
        $result = names::execute_export(new target_user($user2), context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertEquals(['firstname' => $user2->firstname, 'lastname' => $user2->lastname], $result->data);

        // Purge data.
        names::execute_purge(new target_user($user2), context_system::instance());

        // Reload user from DB.
        $user2 = $DB->get_record('user', ['id' => $user2->id]);

        $result = names::execute_export(new target_user($user2), context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertEquals(['firstname' => '', 'lastname' => ''], $result->data);
    }

}