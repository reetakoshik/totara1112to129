<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Vernon Denny <vernon.denny@totaralearning.com>
 * @package auth_oauth2
 */

use auth_oauth2\userdata\username;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;
use \auth_oauth2\api as oauth2_api;

defined('MOODLE_INTERNAL') || die();

/**
 * Test purging, exporting and counting of username
 *
 * @group totara_userdata
 */
class auth_oauth2_userdata_username_testcase extends advanced_testcase {

    /**
     * Test compatible context levels.
     */
    public function test_compatible_context_levels() {
        $expectedcontextlevels = [CONTEXT_SYSTEM];
        $this->assertEquals($expectedcontextlevels, username::get_compatible_context_levels());
    }

    /**
     * Test that data is correctly purged.
     */
    public function test_purge() {
        global $DB;
        $this->setAdminUser();
        $this->resetAfterTest(true);

        // Create identity provider.
        $issuer = \core\oauth2\api::create_standard_issuer('google');

        // Create users.
        $activeuser = $this->getDataGenerator()->create_user(['deleted' => 0, 'suspended' => 0]);
        $suspendeduser = $this->getDataGenerator()->create_user(['deleted' => 0, 'suspended' => 0]);
        $deleteduser = $this->getDataGenerator()->create_user(['deleted' => 0, 'suspended' => 0]);

        // Create corresponding linked logins.
        oauth2_api::link_login(['username' => 'user1', 'email' => $activeuser->email], $issuer, $activeuser->id, false);
        oauth2_api::link_login(['username' => 'user2', 'email' => $suspendeduser->email], $issuer, $suspendeduser->id, false);
        oauth2_api::link_login(['username' => 'user3', 'email' => $deleteduser->email], $issuer, $deleteduser->id, false);

        // Set status of suspended and deleted user.
        $suspendeduser = $this->suspend_user_for_testing($suspendeduser);
        $deleteduser = $this->delete_user_for_testing($deleteduser);

        // Userdata objects for each user.
        $activeuser = new target_user($activeuser);
        $suspendeduser = new target_user($suspendeduser);
        $deleteduser = new target_user($deleteduser);

        // Check whether username is purgeable.
        $this->assertFalse(username::is_purgeable($activeuser->status));
        $this->assertFalse(username::is_purgeable($suspendeduser->status));
        $this->assertTrue(username::is_purgeable($deleteduser->status));

        // Purge data.
        $result = username::execute_purge($deleteduser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $this->assertRegExp('/^deleted_[a-z0-9]+$/', $DB->get_field('auth_oauth2_linked_login', 'username', ['userid' => $deleteduser->id]));

        // Usernames of control users are present.
        $this->assertNotEquals($activeuser->username, $DB->get_field('auth_oauth2_linked_login', 'username', ['userid' => $activeuser->id]));
        $this->assertNotEquals($suspendeduser->username, $DB->get_field('auth_oauth2_linked_login', 'username', ['userid' => $suspendeduser->id]));
    }

    /**
     * Test that data is correctly counted.
     */
    public function test_count() {
        global $DB;
        $this->setAdminUser();
        $this->resetAfterTest(true);

        // Set up users.
        $activeuser = $this->getDataGenerator()->create_user(['username' => 'user1']);
        $deleteduser = $this->getDataGenerator()->create_user(['username' => 'user2']);

        // Create corresponding linked logins.
        $issuer = \core\oauth2\api::create_standard_issuer('google');
        oauth2_api::link_login(['username' => $activeuser->username, 'email' => $activeuser->email], $issuer, $activeuser->id, false);
        oauth2_api::link_login(['username' => $deleteduser->username, 'email' => $deleteduser->email], $issuer, $deleteduser->id, false);
        $deleteduser = $this->delete_user_for_testing($deleteduser);

        // Do the count.
        $result = username::execute_count(new target_user($activeuser), context_system::instance());
        $this->assertEquals(1, $result);

        $result = username::execute_count(new target_user($deleteduser), context_system::instance());
        $this->assertEquals(1, $result);

        // Purge data.
        username::execute_purge(new target_user($deleteduser), context_system::instance());

        // Reload user.
        $deleteduser = $DB->get_record('user', ['id' => $deleteduser->id]);

        $result = username::execute_count(new target_user($deleteduser), context_system::instance());
        $this->assertEquals(0, $result);
    }


    /**
     * Test that data is correctly counted.
     */
    public function test_export() {
        global $DB;
        $this->setAdminUser();
        $this->resetAfterTest(true);

        // Set up users.
        $activeuser = $this->getDataGenerator()->create_user(['username' => 'user1']);
        $deleteduser = $this->getDataGenerator()->create_user(['username' => 'user2']);

        // Create corresponding linked logins.
        $issuer = \core\oauth2\api::create_standard_issuer('google');
        oauth2_api::link_login(['username' => $activeuser->username, 'email' => $activeuser->email], $issuer, $activeuser->id, false);
        oauth2_api::link_login(['username' => $deleteduser->username, 'email' => $deleteduser->email], $issuer, $deleteduser->id, false);
        $deleteduser = $this->delete_user_for_testing($deleteduser);

        // Export data.
        $result = username::execute_export(new target_user($activeuser), context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertEquals(['username' => $activeuser->username], $result->data);

        $result = username::execute_export(new target_user($deleteduser), context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertEquals(['username' => $deleteduser->username], $result->data);

        // Purge data.
        username::execute_purge(new target_user($deleteduser), context_system::instance());

        // Reload user.
        $deleteduser = $DB->get_record('user', ['id' => $deleteduser->id]);

        $result = username::execute_export(new target_user($deleteduser), context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertEquals(['username' => ''], $result->data);
    }

    /**
     * DO NOT COPY THIS TO PRODUCTION CODE!
     *
     * See user/action.php
     *
     * @param object $user
     * @return \stdClass The updated user object.
     */
    private function suspend_user_for_testing($user) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/user/lib.php');

        $user->suspended = 1;
        user_update_user($user, false);
        \totara_core\event\user_suspended::create_from_user($user)->trigger();
        return $DB->get_record('user', array('id' => $user->id), '*', MUST_EXIST);
    }

    /**
     * DO NOT COPY THIS TO PRODUCTION CODE!
     *
     * See user/action.php
     *
     * @param object $user
     * @return \stdClass The updated user object.
     */
    private function delete_user_for_testing($user) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/user/lib.php');

        $user->deleted = 1;
        user_update_user($user, false);
        return $DB->get_record('user', array('id' => $user->id), '*', MUST_EXIST);
    }
}
