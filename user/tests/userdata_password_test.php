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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package core_user
 */

use core_user\userdata\password;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose core_user_userdata_password_testcase user/tests/userdata_password_test.php
 *
 * @group totara_userdata
 */
class core_user_userdata_password_testcase extends \advanced_testcase {

    /**
     * Set up some stuff.
     */
    public static function setUpBeforeClass() {
        global $CFG;

        parent::setUpBeforeClass();

        require_once($CFG->dirroot . '/login/lib.php'); // For core_login_generate_password_reset.
    }

    /**
     * Test function is_compatible_context_level with all possible contexts.
     */
    public function test_get_compatible_context_levels() {
        $this->assertEquals(array(CONTEXT_SYSTEM), password::get_compatible_context_levels());
    }

    /**
     * Test function is_purgeable.
     */
    public function test_is_purgeable() {
        $this->assertTrue(password::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(password::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(password::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * Test function is_exportable.
     */
    public function test_is_exportable() {
        $this->assertFalse(password::is_exportable());
    }

    /**
     * Test function is_countable.
     */
    public function test_is_countable() {
        $this->assertTrue(password::is_countable());
    }

    /**
     * Set up data that'll be purged.
     */
    private function setup_data() {
        $data = new class() {
            /** @var \stdClass */
            public $user1, $user2;

            /** @var target_user */
            public $targetuser;
        };

        $this->resetAfterTest(true);

        // Set up users with passwords.
        $data->user1 = $this->getDataGenerator()->create_user();
        $data->user2 = $this->getDataGenerator()->create_user();

        // Set up users with password reset tokens.
        core_login_generate_password_reset($data->user1);
        core_login_generate_password_reset($data->user2);

        // Set up the target user.
        $data->targetuser = new target_user($data->user1);

        return $data;
    }

    /**
     * Test the purge function. Make sure that the control data is not affected.
     */
    public function test_purge() {
        global $DB;

        $data = $this->setup_data();

        // Get the expected data, by modifying the actual data.

        $expectedusers = $DB->get_records('user', array(), 'id');
        foreach ($expectedusers as $user) {
            $this->assertNotEmpty($user->password);
        }
        $expectedusers[$data->targetuser->id]->password = '';

        $expectedtokens = $DB->get_records('user_password_resets', array(), 'id');
        $this->assertCount(2, $expectedtokens);
        foreach ($expectedtokens as $key => $token) {
            if ($token->userid == $data->user1->id) {
                unset($expectedtokens[$key]);
            }
        }
        $this->assertCount(1, $expectedtokens);

        // Execute the purge.
        $status = password::execute_purge($data->targetuser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        // Check the results.
        $this->assertEquals($expectedusers, $DB->get_records('user', array(), 'id'));
        $this->assertEquals($expectedtokens, $DB->get_records('user_password_resets', array(), 'id'));
    }

    /**
     * Test the count function.
     */
    public function test_count() {
        $data = $this->setup_data();

        $this->assertEquals(1, password::execute_count($data->targetuser, context_system::instance()));

        // Execute the purge.
        $status = password::execute_purge($data->targetuser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        $this->assertEquals(0, password::execute_count($data->targetuser, context_system::instance()));
    }
}