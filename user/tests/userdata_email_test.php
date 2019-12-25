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

use core_user\userdata\email;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Test purging, exporting and counting of user email
 *
 * @group totara_userdata
 */
class core_user_userdata_email_testcase extends advanced_testcase {

    /**
     * test compatible context levels
     */
    public function test_compatible_context_levels() {
        $expectedcontextlevels = [CONTEXT_SYSTEM];
        $this->assertEquals($expectedcontextlevels, email::get_compatible_context_levels());
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

        $this->assertFalse(email::is_purgeable($activeuser->status));
        $this->assertFalse(email::is_purgeable($suspendeduser->status));
        $this->assertTrue(email::is_purgeable($deleteduser->status));

        // Purge data.
        $result = email::execute_purge($deleteduser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $this->assertEquals('', $DB->get_field('user', 'email', ['id' => $deleteduser->id]));
        // Email of control users are untouched.
        $this->assertEquals($activeuser->email, $DB->get_field('user', 'email', ['id' => $activeuser->id]));
        $this->assertEquals($suspendeduser->email, $DB->get_field('user', 'email', ['id' => $suspendeduser->id]));
    }

    /**
     * test if data is correctly counted
     */
    public function test_count() {
        $this->resetAfterTest(true);

        // Set up users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user(['deleted' => 1]);

        // Do the count.
        $result = email::execute_count(new target_user($user1), context_system::instance());
        $this->assertEquals(1, $result);

        // Deleted users email address is not a valid email address.
        $result = email::execute_count(new target_user($user2), context_system::instance());
        $this->assertEquals(0, $result);
    }


    /**
     * test if data is correctly counted
     */
    public function test_export() {
        $this->resetAfterTest(true);

        // Set up users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user(['deleted' => 1]);

        // Export data.
        $result = email::execute_export(new target_user($user1), context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertEquals(['email' => $user1->email], $result->data);

        $result = email::execute_export(new target_user($user2), context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertEmpty($result->data);
    }

}