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
 * @author Carl Anderson <carl.anderson@totaralearning.com>
 * @package auth_approved
 */

use auth_approved\userdata\approval_request;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

class auth_approved_userdata_approval_request_testcase extends advanced_testcase {

    private function create_request($key = 1) {
        global $DB;
        $data = new \stdClass;
        $data->requestid = 0;
        $data->username = 'test' . $key;
        $data->firstname = 'test' . $key;
        $data->lastname = 'test' . $key;
        $data->password = 'monkey';
        $data->email = 'test_'.$key.'@example.com';
        $data->city = 'test'.$key;
        $data->country = 'NZ';
        $data->lang = 'en';
        $requestid = \auth_approved\request::add_request($data);
        return $DB->get_record('auth_approved_request', ['id' => $requestid], '*', MUST_EXIST);
    }

    /**
     * Test function is_compatible_context_level with all possible contexts.
     */
    public function test_get_compatible_context_levels() {
        //Only runs at system context
        $this->assertEquals(
            [CONTEXT_SYSTEM],
            approval_request::get_compatible_context_levels()
        );
    }

    /**
     * Test function is_purgeable.
     */
    public function test_is_purgeable() {
        $this->assertTrue(approval_request::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(approval_request::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(approval_request::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * Test function is_exportable.
     */
    public function test_is_exportable() {
        $this->assertTrue(approval_request::is_exportable());
    }

    /**
     * Test function is_countable.
     */
    public function test_is_countable() {
        $this->assertTrue(approval_request::is_countable());
    }

    /**
     * Test counts of certificate issues for each user at the system context.
     */
    public function test_count() {
        global $DB;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $user1 = $generator->create_user();
        $request = $this->create_request();

        $userid = \auth_approved\request::approve_request($request->id, 'A custom approval message', true);
        $user2 = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

        $target_user1 = new target_user($user1);
        $target_user2 = new target_user($user2);

        $count = approval_request::execute_count($target_user1, context_system::instance());
        $this->assertEquals(0, $count);

        $count = approval_request::execute_count($target_user2, context_system::instance());
        $this->assertEquals(1, $count);
    }

    public function test_purge() {
        global $DB;

        $this->resetAfterTest();

        $request1 = $this->create_request(1);
        $request2 = $this->create_request(2);

        $userid1 = \auth_approved\request::approve_request($request1->id, 'A custom approval message', true);
        $userid2 = \auth_approved\request::approve_request($request2->id, 'Another custom approval message', true);

        $user1 = $DB->get_record('user', ['id' => $userid1], '*', MUST_EXIST);
        $user2 = $DB->get_record('user', ['id' => $userid2], '*', MUST_EXIST);

        $target_user = new target_user($user1);

        //Purge the first user record
        $res = approval_request::execute_purge($target_user, context_system::instance());
        $this->assertEquals(approval_request::RESULT_STATUS_SUCCESS, $res);

        //Check the record has been deleted
        $this->assertFalse($DB->record_exists('auth_approved_request', ['userid' => $user1->id]));
        $this->assertTrue($DB->record_exists('auth_approved_request', ['userid' => $user2->id]));
    }

    public function test_export() {
        global $DB;

        $this->resetAfterTest();
        $request = $this->create_request();

        $userid = \auth_approved\request::approve_request($request->id, 'A custom approval message', true);
        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

        $target_user = new target_user($user);

        $export = approval_request::execute_export($target_user, context_system::instance());
        $this->assertCount(1, $export->data);

        foreach($export->data as $data) {
            $this->assertEquals('test1', $data->username);
            $this->assertEquals('test1', $data->firstname);
            $this->assertEquals('test1', $data->lastname);
            $this->assertEquals('test_1@example.com', $data->email);
            $this->assertEquals('test1', $data->city);
            $this->assertEquals('NZ', $data->country);
            $this->assertEquals('en', $data->lang);
        }
    }
}