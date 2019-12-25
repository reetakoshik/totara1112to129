<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package auth_connect
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the auth plugin class.
 */
class auth_connect_plugin_testcase extends advanced_testcase {
    public function test_edit_profile_url() {
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');

        // Delete everything, but suspend users only.
        $server = $generator->create_server();
        $user1 = $this->getDataGenerator()->create_user(array('auth' => 'connect'));
        $generator->migrate_server_user($server, $user1, 777);
        $user2 = $this->getDataGenerator()->create_user(array('auth' => 'connect'));
        $generator->migrate_server_user($server, $user2, 778);

        $this->setUser($user2);
        $connectauth = get_auth_plugin('connect');

        $expected = new moodle_url('/auth/connect/user_edit.php', array('userid' => $user2->id));
        $this->assertSame((string)$expected, (string)$connectauth->edit_profile_url());

        $expected = new moodle_url('/auth/connect/user_edit.php', array('userid' => $user1->id));
        $this->assertSame((string)$expected, (string)$connectauth->edit_profile_url($user1->id));
    }

    public function test_auth_forcepasswordchange() {
        $this->resetAfterTest();

        $olderrorlog = ini_get('error_log');
        $errorlog = make_request_directory() . '/log';
        ini_set('error_log', $errorlog);

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');

        // Delete everything, but suspend users only.
        $server = $generator->create_server();
        $user = $this->getDataGenerator()->create_user(array('auth' => 'connect'));
        $generator->migrate_server_user($server, $user, 777);

        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $connectauth = get_auth_plugin('connect');
        $this->assertFalse($connectauth->can_change_password());

        $this->setUser($user);
        $this->assertFalse(get_user_preferences('auth_forcepasswordchange', false));
        require_login($course);

        set_user_preference('auth_forcepasswordchange', '1');
        require_login($course);
        $this->assertFalse(get_user_preferences('auth_forcepasswordchange', false));

        $this->setUser(null);
        set_user_preference('auth_forcepasswordchange', '1');
        @complete_user_login($user); // Silence session warnings.
        $this->assertFalse(get_user_preferences('auth_forcepasswordchange', false));

        ini_set('error_log', $olderrorlog);
    }
}
