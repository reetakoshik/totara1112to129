<?php
/**
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package auth_approved
 */

class auth_approved_auth_testcase extends advanced_testcase {

    public function test_basic_auth_structure() {
        global $CFG;
        require_once($CFG->dirroot . '/auth/approved/auth.php');

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user([
            'username' => 'test',
            'password' => 'test'
        ]);

        $auth = new auth_plugin_approved();

        $this->assertFalse($auth->user_login('false', 'false'));
        $this->assertTrue($auth->user_login('test', 'test'));
        $this->assertTrue($auth->user_update_password($user, 'newtest'));
        $this->assertTrue($auth->can_signup());
        $this->assertInstanceOf('\auth_approved\form\signup', $auth->signup_form());
        $this->assertFalse($auth->can_confirm());
        $this->assertFalse($auth->prevent_local_passwords());
        $this->assertTrue($auth->is_internal());
        $this->assertTrue($auth->can_change_password());
        $this->assertSame(null, $auth->change_password_url());
        $this->assertTrue($auth->can_reset_password());
        $this->assertTrue($auth->can_be_manually_set());

    }

    /**
     * Test user_update_password method.
     */
    public function test_user_update_password() {
        $this->resetAfterTest();

        /** @var auth_plugin_approved $authplugin */
        $authplugin = get_auth_plugin('approved');

        $user = $this->getDataGenerator()->create_user(array('auth' => 'approved'));
        $this->assertEquals(0, get_user_preferences('auth_approved_passwordupdatetime', 0, $user->id));

        $this->setCurrentTimeStart();
        $passwordisupdated = $authplugin->user_update_password($user, 'MyNewPassword*');
        $this->assertTimeCurrent(get_user_preferences('auth_approved_passwordupdatetime', 0, $user->id));
        $this->assertTrue($passwordisupdated);
    }

    /**
     * Test test_password_expire method.
     */
    public function test_password_expire() {
        $this->resetAfterTest();

        set_config('expiration', '1', 'auth_approved');
        set_config('expiration_warning', '2', 'auth_approved');
        set_config('expirationtime', '30', 'auth_approved');

        /** @var auth_plugin_approved $authplugin */
        $authplugin = get_auth_plugin('approved');

        $expirationtime = 31 * DAYSECS;
        $user1 = $this->getDataGenerator()->create_user(array('auth' => 'approved', 'timecreated' => time() - $expirationtime));
        $user2 = $this->getDataGenerator()->create_user(array('auth' => 'approved'));


        // The user 1 was created 31 days ago and has not changed his password yet, so the password has expired.
        $this->assertLessThanOrEqual(-1, $authplugin->password_expire($user1->username));

        // The user 2 just came to be created and has not changed his password yet, so the password has not expired.
        $this->assertEquals(30, $authplugin->password_expire($user2->username));

        $authplugin->user_update_password($user1, 'MyNewPassword*');

        // The user 1 just updated his password so the password has not expired.
        $this->assertEquals(30, $authplugin->password_expire($user1->username));

        set_user_preference('auth_approved_passwordupdatetime', time() - 31 * DAYSECS, $user2);
        $this->assertLessThanOrEqual(-1, $authplugin->password_expire($user2->username));

        set_user_preference('auth_approved_passwordupdatetime', time() - 29 * DAYSECS, $user2);
        $this->assertLessThanOrEqual(1, $authplugin->password_expire($user2->username));

        set_user_preference('auth_approved_passwordupdatetime', time(), $user2);
        $this->assertLessThanOrEqual(30, $authplugin->password_expire($user2->username));
    }

}