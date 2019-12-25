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

use auth_oauth2\userdata\email;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;
use \auth_oauth2\api as oauth2_api;

defined('MOODLE_INTERNAL') || die();


/**
 * Test purging, exporting and counting of user email
 *
 * @group totara_userdata
 */
class auth_oauth2_userdata_email_testcase extends advanced_testcase {

    /**
     * Test compatible context levels.
     */
    public function test_compatible_context_levels() {
        $expectedcontextlevels = [CONTEXT_SYSTEM];
        $this->assertEquals($expectedcontextlevels, email::get_compatible_context_levels());
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
        $activeuser = $this->getDataGenerator()->create_user();
        $suspendeduser = $this->getDataGenerator()->create_user();
        $deleteduser = $this->getDataGenerator()->create_user();

        // Create corresponding linked logins.
        oauth2_api::link_login(['username' => $activeuser->username, 'email' => $activeuser->email], $issuer, $activeuser->id, false);
        oauth2_api::link_login(['username' => $suspendeduser->username, 'email' => $suspendeduser->email], $issuer, $suspendeduser->id, false);
        oauth2_api::link_login(['username' => $deleteduser->username, 'email' => $deleteduser->email], $issuer, $deleteduser->id, false);

        // Set status of suspended and deleted user.
        $suspendeduser = $this->suspend_user_for_testing($suspendeduser);
        $deleteduser = $this->delete_user_for_testing($deleteduser);

        // Userdata objects for each user.
        $activeuser = new target_user($activeuser);
        $suspendeduser = new target_user($suspendeduser);
        $deleteduser = new target_user($deleteduser);

        // Check whether email is purgeable.
        $this->assertFalse(email::is_purgeable($activeuser->status));
        $this->assertFalse(email::is_purgeable($suspendeduser->status));
        $this->assertTrue(email::is_purgeable($deleteduser->status));

        // Purge data.
        $result = email::execute_purge($deleteduser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $this->assertEquals('', $DB->get_field('auth_oauth2_linked_login', 'email', ['userid' => $deleteduser->id]));

        // Email of control users still present.
        $this->assertEquals($activeuser->email, $DB->get_field('auth_oauth2_linked_login', 'email', ['userid' => $activeuser->id]));
        $this->assertEquals($suspendeduser->email, $DB->get_field('auth_oauth2_linked_login', 'email', ['userid' => $suspendeduser->id]));
    }

    /**
     * Test that data is correctly counted.
     */
    public function test_count() {
        $this->setAdminUser();
        $this->resetAfterTest(true);

        // Create identity provider.
        $issuer = \core\oauth2\api::create_standard_issuer('google');

        // Set up users.
        $activeuser = $this->getDataGenerator()->create_user();
        $deleteduser = $this->getDataGenerator()->create_user();

        // Create corresponding linked logins.
        oauth2_api::link_login(['username' => $activeuser->username, 'email' => $activeuser->email], $issuer, $activeuser->id, false);
        oauth2_api::link_login(['username' => $deleteduser->username, 'email' => $deleteduser->email], $issuer, $deleteduser->id, false);

        // Flag user as deleted.
        $deleteduser = $this->delete_user_for_testing($deleteduser);
        $deleteduser = new target_user($deleteduser);
        email::execute_purge($deleteduser, context_system::instance());

        // Do the count.
        $result = email::execute_count(new target_user($activeuser), context_system::instance());
        $this->assertEquals(1, $result);

        // Deleted users email address is not a valid email address.
        $result = email::execute_count(new target_user($deleteduser), context_system::instance());
        $this->assertEquals(0, $result);
    }


    /**
     * Test that data is correctly exported.
     */
    public function test_export() {
        $this->setAdminUser();
        $this->resetAfterTest(true);

        // Create identity provider.
        $issuer = \core\oauth2\api::create_standard_issuer('google');

        // Set up users.
        $activeuser = $this->getDataGenerator()->create_user();
        $deleteduser = $this->getDataGenerator()->create_user();

        // Create corresponding linked logins.
        oauth2_api::link_login(['username' => $activeuser->username, 'email' => $activeuser->email], $issuer, $activeuser->id, false);
        oauth2_api::link_login(['username' => $deleteduser->username, 'email' => $deleteduser->email], $issuer, $deleteduser->id, false);

        // Flag user as deleted and purge data.
        $deleteduser = $this->delete_user_for_testing($deleteduser);
        $deleteduser = new target_user($deleteduser);
        email::execute_purge($deleteduser, context_system::instance());

        // Export data.
        $result = email::execute_export(new target_user($activeuser), context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertEquals(['email' => $activeuser->email], $result->data);

        $result = email::execute_export(new target_user($deleteduser), context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertEmpty($result->data);
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
