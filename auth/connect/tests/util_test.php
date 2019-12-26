<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package auth_connect
 */

use \auth_connect\util;
use \totara_core\jsend;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests util class.
 */
class auth_connect_util_testcase extends advanced_testcase {
    public function test_create_unique_hash() {
        $hash = util::create_unique_hash('user', 'secret');
        $this->assertSame(40, strlen($hash));
        $this->assertNotSame($hash, util::create_unique_hash('user', 'secret'));
    }

    public function test_get_sep_url() {
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');

        $record = array(
            'serverurl' => 'http://example.com/lms',
        );
        $server = $generator->create_server($record);
        $this->assertSame('http://example.com/lms/totara/connect/sep.php', util::get_sep_url($server));
    }

    public function get_sso_request_url() {
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');

        $record = array(
            'serverurl' => 'http://example.com/lms',
        );
        $server = $generator->create_server($record);
        $this->assertSame('http://example.com/lms/totara/connect/sso_request.php', util::get_sso_request_url($server));
    }

    public function test_enable_registration() {
        $this->resetAfterTest();

        $prev = get_config('auth_connect', 'setupsecret');
        $this->assertFalse($prev);

        util::enable_registration();
        $result = get_config('auth_connect', 'setupsecret');
        $this->assertSame(40, strlen($result));
    }

    public function test_cancel_registration() {
        $this->resetAfterTest();

        util::enable_registration();
        $prev = get_config('auth_connect', 'setupsecret');
        $this->assertSame(40, strlen($prev));

        util::cancel_registration();
        $result = get_config('auth_connect', 'setupsecret');
        $this->assertFalse($result);
    }

    public function test_get_setup_secret() {
        $this->resetAfterTest();

        util::enable_registration();
        $prev = get_config('auth_connect', 'setupsecret');

        $result = util::get_setup_secret();
        $this->assertSame($prev, $result);
    }

    public function test_verify_setup_secret() {
        $this->resetAfterTest();

        util::enable_registration();
        $prev = get_config('auth_connect', 'setupsecret');

        // Auth not enabled.
        $result = util::verify_setup_secret($prev);
        $this->assertFalse($result);

        // Auth enabled.
        $this->set_auth_enabled(true);
        $result = util::verify_setup_secret($prev);
        $this->assertTrue($result);

        // Wrong secret.
        $result = util::verify_setup_secret($prev . 'xxx');
        $this->assertFalse($result);

        // No registration.
        util::cancel_registration();
        $result = util::verify_setup_secret($prev);
        $this->assertFalse($result);
        $result = util::verify_setup_secret('');
        $this->assertFalse($result);
        $result = util::verify_setup_secret(false);
        $this->assertFalse($result);
        $result = util::verify_setup_secret(null);
        $this->assertFalse($result);
    }

    public function test_select_api_version() {
        // Valid ranges - hardcoded to current min max.
        $this->assertSame(1, util::select_api_version(-1, 1));
        $this->assertSame(1, util::select_api_version(1, 1));
        $this->assertSame(2, util::select_api_version(0, 2));
        $this->assertSame(2, util::select_api_version(1, 2));
        $this->assertSame(2, util::select_api_version(2, 2));
        $this->assertSame(2, util::select_api_version(2, 3));

        // Now problems.
        $this->assertSame(0, util::select_api_version(2, 1));
        $this->assertSame(0, util::select_api_version(0, 0));
        $this->assertSame(0, util::select_api_version(3, 3));
        $this->assertSame(0, util::select_api_version(3, 4));
    }

    public function test_edit_server() {
        global $DB;
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');
        $server = $generator->create_server();

        $data = new stdClass();
        $data->id = $server->id;
        $data->servercomment = 'lalalala';

        $this->setCurrentTimeStart();
        util::edit_server($data);

        $newserver = $DB->get_record('auth_connect_servers', array('id' => $server->id));
        $this->assertSame('lalalala', $newserver->servercomment);
        $this->assertTimeCurrent($newserver->timemodified);
    }

    public function test_delete_server() {
        global $DB;
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');

        // Some extra stuff that should not be touched.
        $otheruser = $this->getDataGenerator()->create_user();
        $otherserver = $generator->create_server();
        $generator->migrate_server_user($otherserver, $otheruser, 111);

        // Delete everything including users.
        $server = $generator->create_server();
        $this->assertEquals(util::SERVER_STATUS_OK, $server->status);

        $serverusers = array();
        $serverusers[] = $generator->get_fake_server_user();
        $serverusers[] = $generator->get_fake_server_user();
        $serverusers[] = $generator->get_fake_server_user();
        util::update_local_users($server, $serverusers);
        $users = $this->fetch_local_server_users($server, $serverusers);
        $user = reset($users);
        delete_user($users[2]);

        $servercohorts = array();
        $servercohorts[] = $generator->get_fake_server_cohort();
        $servercohorts[0]['members'] = array(array('id' => $serverusers[0]['id']), array('id' => $serverusers[1]['id']));

        $servercourses = array();
        $servercourses[] = $generator->get_fake_server_course();
        $servercourses[0]['members'] = array(array('id' => $serverusers[0]['id']), array('id' => $serverusers[1]['id']));

        $collections = array('cohort' => $servercohorts, 'course' => $servercourses);
        util::update_local_user_collections($server, $collections);
        $cohorts1 = $this->fetch_local_server_cohorts($server, 'cohort', $servercohorts);
        $cohort1 = reset($cohorts1);
        $cohorts2 = $this->fetch_local_server_cohorts($server, 'course', $servercourses);
        $cohort2 = reset($cohorts2);

        $data = new stdClass();
        $data->id = $server->id;
        $data->removeuser = 'delete';
        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array())));
        util::delete_server($data);
        $this->assertFalse($DB->record_exists('auth_connect_servers', array('id' => $server->id)));
        $user = $DB->get_record('user', array('id' => $user->id));
        $this->assertSame('1', $user->deleted);
        $this->assertSame('0', $user->suspended);
        $this->assertSame(1, $DB->count_records('auth_connect_users', array()));
        $this->assertFalse($DB->record_exists('auth_connect_user_collections', array()));
        $this->assertFalse($DB->record_exists('auth_connect_sso_requests', array()));
        $this->assertFalse($DB->record_exists('auth_connect_sso_sessions', array()));
        $localcohort1 = $DB->get_record('cohort', array('id' => $cohort1->id), '*', MUST_EXIST);
        $this->assertSame('', $localcohort1->component);
        $localcohort2 = $DB->get_record('cohort', array('id' => $cohort2->id), '*', MUST_EXIST);
        $this->assertSame('', $localcohort2->component);

        // Delete everything, but suspend users only.
        $server = $generator->create_server();
        $this->assertEquals(util::SERVER_STATUS_OK, $server->status);
        $user = $this->getDataGenerator()->create_user();
        $generator->migrate_server_user($server, $user, 777);
        $userx = $this->getDataGenerator()->create_user();
        $generator->migrate_server_user($server, $userx, 778);
        delete_user($userx);

        $data = new stdClass();
        $data->id = $server->id;
        $data->removeuser = 'suspend';
        $data->newauth    = 'manual';
        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array())));
        util::delete_server($data);
        $this->assertFalse($DB->record_exists('auth_connect_servers', array('id' => $server->id)));
        $user = $DB->get_record('user', array('id' => $user->id));
        $this->assertSame('0', $user->deleted);
        $this->assertSame('1', $user->suspended);
        $this->assertSame('manual', $user->auth);
        $this->assertSame(1, $DB->count_records('auth_connect_users', array()));
        $this->assertFalse($DB->record_exists('auth_connect_user_collections', array()));
        $this->assertFalse($DB->record_exists('auth_connect_sso_requests', array()));
        $this->assertFalse($DB->record_exists('auth_connect_sso_sessions', array()));

        // Deleting on server fails.
        $server = $generator->create_server();
        $this->assertEquals(util::SERVER_STATUS_OK, $server->status);
        $user = $this->getDataGenerator()->create_user();
        $generator->migrate_server_user($server, $user, 666);

        $data = new stdClass();
        $data->id = $server->id;
        $data->removeuser = 'delete';
        jsend::set_phpunit_testdata(array(array('status' => 'error', 'message' => 'xxx')));
        util::delete_server($data);
        $server = $DB->get_record('auth_connect_servers', array('id' => $server->id));
        $this->assertEquals(util::SERVER_STATUS_DELETING, $server->status);
        $user = $DB->get_record('user', array('id' => $user->id));
        $this->assertSame('1', $user->deleted);
        $this->assertSame('0', $user->suspended);
        $this->assertSame(1, $DB->count_records('auth_connect_users', array()));
        $this->assertFalse($DB->record_exists('auth_connect_user_collections', array()));
        $this->assertFalse($DB->record_exists('auth_connect_sso_requests', array()));
        $this->assertFalse($DB->record_exists('auth_connect_sso_sessions', array()));
    }

    public function test_force_sso_logout() {
        global $DB;
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');

        $server = $generator->create_server();
        $user1 = $this->getDataGenerator()->create_user();
        $generator->migrate_server_user($server, $user1, 111);
        $user2 = $this->getDataGenerator()->create_user();
        $generator->migrate_server_user($server, $user2, 222);

        $session1 = new stdClass();
        $session1->sid          = md5('xxx');
        $session1->ssotoken     = sha1('uuu');
        $session1->serverid     = $server->id;
        $session1->serveruserid = 111;
        $session1->userid       = $user1->id;
        $session1->timecreated  = time() - 600;
        $session1->id = $DB->insert_record('auth_connect_sso_sessions', $session1);
        $session1 = $DB->get_record('auth_connect_sso_sessions', array('id' => $session1->id));

        $session2 = new stdClass();
        $session2->sid          = md5('ytytr');
        $session2->ssotoken     = sha1('dsffdsfds');
        $session2->serverid     = $server->id;
        $session2->serveruserid = 222;
        $session2->userid       = $user2->id;
        $session2->timecreated  = time() - 10;
        $session2->id = $DB->insert_record('auth_connect_sso_sessions', $session2);
        $session2 = $DB->get_record('auth_connect_sso_sessions', array('id' => $session2->id));

        // Normal cleanup on server.
        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array())));
        util::force_sso_logout($session1);
        $this->assertFalse($DB->record_exists('auth_connect_sso_sessions', array('id' => $session1->id)));
        $this->assertTrue($DB->record_exists('auth_connect_sso_sessions', array('id' => $session2->id)));

        // Ignore server errors.
        jsend::set_phpunit_testdata(array(array('status' => 'error', 'message' => 'xxxx')));
        util::force_sso_logout($session2);
        $this->assertFalse($DB->record_exists('auth_connect_sso_sessions', array('id' => $session1->id)));
        $this->assertFalse($DB->record_exists('auth_connect_sso_sessions', array('id' => $session2->id)));
    }

    public function test_sync_users() {
        global $DB;
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');
        $server = $generator->create_server();

        $serverusers = array();
        $serverusers[] = $generator->get_fake_server_user();
        $serverusers[] = $generator->get_fake_server_user();
        $serverusers[] = $generator->get_fake_server_user();

        $this->assertEquals(2, $DB->count_records('user', array()));
        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array('users' => $serverusers))));
        $result = util::sync_users($server);
        $this->assertTrue($result);
        $this->assertEquals(5, $DB->count_records('user', array()));

        jsend::set_phpunit_testdata(array(array('status' => 'error', 'message' => 'some error')));
        $result = util::sync_users($server);
        $this->assertFalse($result);
        $this->assertEquals(5, $DB->count_records('user', array()));
    }

    public function test_sync_user() {
        global $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');
        $server = $generator->create_server();

        $serveruser1 = $generator->get_fake_server_user(array('email' => 'xxx@example.com'));
        $serveruser2 = $generator->get_fake_server_user(array('email' => 'yyy@example.com'));

        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array('users' => array($serveruser1, $serveruser2)))));
        $result = util::sync_users($server);
        $this->assertTrue($result);

        $user1 = $DB->get_record('user', array('email' => $serveruser1['email']), '*', MUST_EXIST);
        $user2 = $DB->get_record('user', array('email' => $serveruser2['email']), '*', MUST_EXIST);

        $serveruser1['firstname'] = 'Somethingunique';
        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array('user' => $serveruser1))));
        $result = util::sync_user($user1->id);
        $this->assertFalse($result);

        $this->set_auth_enabled(true);
        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array('user' => $serveruser1))));
        $result = util::sync_user($user1->id);
        $this->assertTrue($result);
        $updateduser = $DB->get_record('user', array('id' => $user1->id), '*', MUST_EXIST);
        $this->assertSame($serveruser1['firstname'], $updateduser->firstname);
        $this->assertNotSame($USER->firstname, $updateduser->firstname);

        $this->setUser($user2);
        $serveruser2['firstname'] = 'XXXXXXX';
        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array('user' => $serveruser2))));
        $result = util::sync_user($user2->id);
        $this->assertTrue($result);
        $updateduser = $DB->get_record('user', array('id' => $user2->id), '*', MUST_EXIST);
        $this->assertSame($serveruser2['firstname'], $updateduser->firstname);
        $this->assertSame($USER->firstname, $updateduser->firstname);
    }

    public function test_update_local_users_basic() {
        global $DB;
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');
        $server = $generator->create_server();

        $serverusers = array();
        $serverusers[] = $generator->get_fake_server_user();
        $serverusers[] = $generator->get_fake_server_user();
        $serverusers[] = $generator->get_fake_server_user();

        // Adding users.
        $this->assertEquals(2, $DB->count_records('user', array()));
        util::update_local_users($server, $serverusers);
        $this->assertEquals(5, $DB->count_records('user', array()));

        $users = array();
        foreach ($serverusers as $su) {
            $su = (object)$su;
            $sql = "SELECT u.*
                      FROM {user} u
                      JOIN {auth_connect_users} cu ON cu.userid = u.id
                     WHERE cu.serverid = :serverid AND cu.serveruserid =:serveruserid";
            $params = array('serverid' => $server->id, 'serveruserid' => $su->id);
            $user = $DB->get_record_sql($sql, $params, MUST_EXIST);
            $this->assertSame('0', $user->deleted);
            $this->assertSame('0', $user->suspended);
            $this->assertSame('1', $user->confirmed);
            $this->assertSame('tc_' . $su->id . '_' . $server->serveridnumber, $user->username);
            $users[] = $user;
        }

        // Updating and deleting users deleted from server.
        $this->assertEquals(AUTH_REMOVEUSER_SUSPEND, get_config('auth_connect', 'removeuser'));
        $serverusers[0]['deleted'] = '1';
        $serverusers[1]['firstname'] = 'XxX';
        util::update_local_users($server, $serverusers);
        $this->assertEquals(5, $DB->count_records('user', array()));

        $newusers = array();
        foreach ($serverusers as $su) {
            $su = (object)$su;
            $sql = "SELECT u.*
                      FROM {user} u
                      JOIN {auth_connect_users} cu ON cu.userid = u.id
                     WHERE cu.serverid = :serverid AND cu.serveruserid =:serveruserid";
            $params = array('serverid' => $server->id, 'serveruserid' => $su->id);
            $user = $DB->get_record_sql($sql, $params, MUST_EXIST);
            if ($su->deleted == 0) {
                $this->assertSame('tc_' . $su->id . '_' . $server->serveridnumber, $user->username);
            }
            $newusers[] = $user;
        }

        $this->assertSame('1', $newusers[0]->deleted);
        $this->assertSame('0', $newusers[0]->suspended);
        $this->assertSame('0', $newusers[1]->deleted);
        $this->assertSame('0', $newusers[1]->suspended);
        $this->assertSame('0', $newusers[2]->deleted);
        $this->assertSame('0', $newusers[2]->suspended);
        $this->assertSame('XxX', $newusers[1]->firstname);

        // Suspending if server user missing.
        set_config('removeuser', AUTH_REMOVEUSER_SUSPEND, 'auth_connect');
        $smallerserverusers = array($serverusers[0], $serverusers[1]);
        util::update_local_users($server, $smallerserverusers);
        $this->assertEquals(5, $DB->count_records('user', array()));

        $newusers = array();
        foreach ($serverusers as $su) {
            $su = (object)$su;
            $sql = "SELECT u.*
                      FROM {user} u
                      JOIN {auth_connect_users} cu ON cu.userid = u.id
                     WHERE cu.serverid = :serverid AND cu.serveruserid =:serveruserid";
            $params = array('serverid' => $server->id, 'serveruserid' => $su->id);
            $user = $DB->get_record_sql($sql, $params, MUST_EXIST);
            if ($su->deleted == 0) {
                $this->assertSame('tc_' . $su->id . '_' . $server->serveridnumber, $user->username);
            } else {
                $this->assertNotSame('tc_' . $su->id . '_' . $server->serveridnumber, $user->username);
            }
            $newusers[] = $user;
        }
        $this->assertSame('1', $newusers[0]->deleted);
        $this->assertSame('0', $newusers[0]->suspended);
        $this->assertSame('0', $newusers[1]->deleted);
        $this->assertSame('0', $newusers[1]->suspended);
        $this->assertSame('0', $newusers[2]->deleted);
        $this->assertSame('1', $newusers[2]->suspended);

        // Unsuspending when user reappears.
        util::update_local_users($server, $serverusers);
        $this->assertEquals(5, $DB->count_records('user', array()));

        $newusers = array();
        foreach ($serverusers as $su) {
            $su = (object)$su;
            $sql = "SELECT u.*
                      FROM {user} u
                      JOIN {auth_connect_users} cu ON cu.userid = u.id
                     WHERE cu.serverid = :serverid AND cu.serveruserid =:serveruserid";
            $params = array('serverid' => $server->id, 'serveruserid' => $su->id);
            $user = $DB->get_record_sql($sql, $params, MUST_EXIST);
            if ($su->deleted == 0) {
                $this->assertSame('tc_' . $su->id . '_' . $server->serveridnumber, $user->username);
            } else {
                $this->assertNotSame('tc_' . $su->id . '_' . $server->serveridnumber, $user->username);
            }
            $newusers[] = $user;
        }
        $this->assertSame('1', $newusers[0]->deleted);
        $this->assertSame('0', $newusers[0]->suspended);
        $this->assertSame('0', $newusers[1]->deleted);
        $this->assertSame('0', $newusers[1]->suspended);
        $this->assertSame('0', $newusers[2]->deleted);
        $this->assertSame('0', $newusers[2]->suspended);

        // Doing nothing when missing on server.
        set_config('removeuser', AUTH_REMOVEUSER_KEEP, 'auth_connect');
        $smallerserverusers = array($serverusers[0], $serverusers[1]);
        util::update_local_users($server, $smallerserverusers);
        $this->assertEquals(5, $DB->count_records('user', array()));

        $newusers = array();
        foreach ($serverusers as $su) {
            $su = (object)$su;
            $sql = "SELECT u.*
                      FROM {user} u
                      JOIN {auth_connect_users} cu ON cu.userid = u.id
                     WHERE cu.serverid = :serverid AND cu.serveruserid =:serveruserid";
            $params = array('serverid' => $server->id, 'serveruserid' => $su->id);
            $user = $DB->get_record_sql($sql, $params, MUST_EXIST);
            $newusers[] = $user;
        }
        $this->assertSame('1', $newusers[0]->deleted);
        $this->assertSame('0', $newusers[0]->suspended);
        $this->assertSame('0', $newusers[1]->deleted);
        $this->assertSame('0', $newusers[1]->suspended);
        $this->assertSame('0', $newusers[2]->deleted);
        $this->assertSame('0', $newusers[2]->suspended);

        // Suspended flag not updated when unsuspended user reappears
        $sql = "SELECT u.*
                  FROM {user} u
                  JOIN {auth_connect_users} cu ON cu.userid = u.id
                 WHERE cu.serverid = :serverid AND cu.serveruserid =:serveruserid";
        $params = array('serverid' => $server->id, 'serveruserid' => $serverusers[2]['id']);
        $user2 = $DB->get_record_sql($sql, $params, MUST_EXIST);
        $user2->suspended = 1;
        $DB->update_record('user', $user2);

        util::update_local_users($server, $serverusers);
        $this->assertEquals(5, $DB->count_records('user', array()));

        $newusers = array();
        foreach ($serverusers as $su) {
            $su = (object)$su;
            $sql = "SELECT u.*
                      FROM {user} u
                      JOIN {auth_connect_users} cu ON cu.userid = u.id
                     WHERE cu.serverid = :serverid AND cu.serveruserid =:serveruserid";
            $params = array('serverid' => $server->id, 'serveruserid' => $su->id);
            $user = $DB->get_record_sql($sql, $params, MUST_EXIST);
            if ($su->deleted == 0) {
                $this->assertSame('tc_' . $su->id . '_' . $server->serveridnumber, $user->username);
            } else {
                $this->assertNotSame('tc_' . $su->id . '_' . $server->serveridnumber, $user->username);
            }
            $newusers[] = $user;
        }
        $this->assertSame('1', $newusers[0]->deleted);
        $this->assertSame('0', $newusers[0]->suspended);
        $this->assertSame('0', $newusers[1]->deleted);
        $this->assertSame('0', $newusers[1]->suspended);
        $this->assertSame('0', $newusers[2]->deleted);
        $this->assertSame('1', $newusers[2]->suspended);

        // Deleting via delete when missing.
        set_config('removeuser', AUTH_REMOVEUSER_FULLDELETE, 'auth_connect');
        $smallerserverusers = array($serverusers[0], $serverusers[1]);
        util::update_local_users($server, $smallerserverusers);
        $this->assertEquals(5, $DB->count_records('user', array()));

        $newusers = array();
        foreach ($serverusers as $su) {
            $su = (object)$su;
            $sql = "SELECT u.*
                      FROM {user} u
                      JOIN {auth_connect_users} cu ON cu.userid = u.id
                     WHERE cu.serverid = :serverid AND cu.serveruserid =:serveruserid";
            $params = array('serverid' => $server->id, 'serveruserid' => $su->id);
            $user = $DB->get_record_sql($sql, $params, MUST_EXIST);
            $newusers[] = $user;
        }
        $this->assertSame('1', $newusers[0]->deleted);
        $this->assertSame('0', $newusers[0]->suspended);
        $this->assertSame('0', $newusers[1]->deleted);
        $this->assertSame('0', $newusers[1]->suspended);
        $this->assertSame('1', $newusers[2]->deleted);
        $this->assertSame('1', $newusers[2]->suspended);

        // Bloody undelete on server and reappeared user.
        set_config('removeuser', AUTH_REMOVEUSER_FULLDELETE, 'auth_connect');
        $serverusers[0]['deleted'] = '0';
        util::update_local_users($server, $serverusers);
        $this->assertEquals(5, $DB->count_records('user', array()));

        $newusers = array();
        foreach ($serverusers as $su) {
            $su = (object)$su;
            $sql = "SELECT u.*
                      FROM {user} u
                      JOIN {auth_connect_users} cu ON cu.userid = u.id
                     WHERE cu.serverid = :serverid AND cu.serveruserid =:serveruserid";
            $params = array('serverid' => $server->id, 'serveruserid' => $su->id);
            $user = $DB->get_record_sql($sql, $params, MUST_EXIST);
            $newusers[] = $user;
        }
        $this->assertSame('0', $newusers[0]->deleted);
        $this->assertSame('0', $newusers[0]->suspended);
        $this->assertSame('0', $newusers[1]->deleted);
        $this->assertSame('0', $newusers[1]->suspended);
        $this->assertSame('0', $newusers[2]->deleted);
        $this->assertSame('0', $newusers[2]->suspended);
    }

    public function test_update_local_users_deleting() {
        global $CFG, $DB;

        require_once($CFG->libdir . '/authlib.php');

        $this->resetAfterTest();

        // Verify default settings.
        $this->assertEquals(AUTH_REMOVEUSER_SUSPEND, get_config('auth_connect', 'removeuser'));

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');
        $server = $generator->create_server();

        $serverusers = array();
        $serverusers[] = $generator->get_fake_server_user();
        $serverusers[] = $generator->get_fake_server_user();
        $serverusers[] = $generator->get_fake_server_user();

        $this->assertEquals(2, $DB->count_records('user', array()));
        util::update_local_users($server, $serverusers);
        $this->assertEquals(5, $DB->count_records('user', array()));

        // Keep one removed user and delete other.
        set_config('removeuser', AUTH_REMOVEUSER_KEEP, 'auth_connect');
        $serverusers['0']['deleted'] = '1';
        util::update_local_users($server, array($serverusers[0], $serverusers[2]));

        $users = $this->fetch_local_server_users($server, $serverusers);
        $this->assertSame('1', $users[0]->deleted);
        $this->assertSame('0', $users[0]->suspended);
        $this->assertSame('0', $users[1]->deleted);
        $this->assertSame('0', $users[1]->suspended);
        $this->assertSame('0', $users[2]->deleted);
        $this->assertSame('0', $users[2]->suspended);
        $this->assertEquals(5, $DB->count_records('user', array()));

        // Suspend missing users.
        set_config('removeuser', AUTH_REMOVEUSER_SUSPEND, 'auth_connect');
        $serverusers['0']['deleted'] = '1';
        util::update_local_users($server, array($serverusers[0], $serverusers[2]));

        $users = $this->fetch_local_server_users($server, $serverusers);
        $this->assertSame('1', $users[0]->deleted);
        $this->assertSame('0', $users[0]->suspended);
        $this->assertSame('0', $users[1]->deleted);
        $this->assertSame('1', $users[1]->suspended);
        $this->assertSame('0', $users[2]->deleted);
        $this->assertSame('0', $users[2]->suspended);
        $this->assertEquals(5, $DB->count_records('user', array()));

        // Delete missing users.
        set_config('removeuser', AUTH_REMOVEUSER_FULLDELETE, 'auth_connect');
        $serverusers['0']['deleted'] = '1';
        util::update_local_users($server, array($serverusers[0], $serverusers[2]));

        $users = $this->fetch_local_server_users($server, $serverusers);
        $this->assertSame('1', $users[0]->deleted);
        $this->assertSame('0', $users[0]->suspended);
        $this->assertSame('1', $users[1]->deleted);
        $this->assertSame('1', $users[1]->suspended);
        $this->assertSame('0', $users[2]->deleted);
        $this->assertSame('0', $users[2]->suspended);
        $this->assertEquals(5, $DB->count_records('user', array()));

        // Undelete everything.
        $serverusers['0']['deleted'] = '0';
        util::update_local_users($server, array($serverusers[0], $serverusers[1], $serverusers[2]));

        $users = $this->fetch_local_server_users($server, $serverusers);
        $this->assertSame('0', $users[0]->deleted);
        $this->assertSame('0', $users[0]->suspended);
        $this->assertSame('0', $users[1]->deleted);
        $this->assertSame('0', $users[1]->suspended);
        $this->assertSame('0', $users[2]->deleted);
        $this->assertSame('0', $users[2]->suspended);
        $this->assertEquals(5, $DB->count_records('user', array()));
    }

    public function test_update_local_user() {
        global $DB;
        $this->resetAfterTest();

        set_config('syncpasswords', 0, 'totara_connect');

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');
        $server = $generator->create_server();
        $this->assertEquals(2, $DB->count_records('user', array()));

        // Add new user from server.
        $serveruser1 = $generator->get_fake_server_user(array('password' => 'testpw'));
        $this->assertNull($serveruser1['password']);
        $user1 = util::update_local_user($server, $serveruser1);
        $this->assertEquals(3, $DB->count_records('user', array()));
        $this->assertTrue($DB->record_exists('auth_connect_users', array('serverid' => $server->id, 'serveruserid' => $serveruser1['id'])));
        $this->assertSame('connect', $user1->auth);
        $this->assertSame('tc_' . $serveruser1['id'] . '_' . $server->serveridnumber, $user1->username);
        $this->assertSame('not cached', $user1->password);
        $this->assertSame('0', $user1->deleted);
        $this->assertSame('0', $user1->suspended);
        $this->assertSame($serveruser1['firstname'], $user1->firstname);
        $this->assertSame($serveruser1['lastname'], $user1->lastname);
        $this->assertSame($serveruser1['email'], $user1->email);
        $this->assertSame('en', $user1->lang);

        // Update existing user.
        $serveruser1['username']  = 'xxx';
        $serveruser1['firstname'] = 'XX';
        $serveruser1['lastname']  = 'ZZ';
        $serveruser1['email']     = 'xx@example.com';
        $serveruser1['lang']      = 'cs';
        $user1b = util::update_local_user($server, $serveruser1);
        $this->assertEquals(3, $DB->count_records('user', array()));
        $this->assertTrue($DB->record_exists('auth_connect_users', array('serverid' => $server->id, 'serveruserid' => $serveruser1['id'])));
        $this->assertSame($user1->id, $user1b->id);
        $this->assertSame('connect', $user1b->auth);
        $this->assertSame('tc_' . $serveruser1['id'] . '_' . $server->serveridnumber, $user1b->username);
        $this->assertSame('not cached', $user1b->password);
        $this->assertSame('0', $user1b->deleted);
        $this->assertSame('0', $user1b->suspended);
        $this->assertSame($serveruser1['firstname'], $user1b->firstname);
        $this->assertSame($serveruser1['lastname'], $user1b->lastname);
        $this->assertSame($serveruser1['email'], $user1b->email);
        $this->assertSame($user1->lang, $user1b->lang);
    }

    public function test_update_local_user_with_password() {
        global $DB;
        $this->resetAfterTest();

        set_config('syncpasswords', 1, 'totara_connect');

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');
        $server = $generator->create_server();
        $this->assertEquals(2, $DB->count_records('user', array()));

        // Add new user from server.
        $serveruser1 = $generator->get_fake_server_user(array('password' => 'testpw'));
        $this->assertNotEmpty($serveruser1['password']);
        $user1 = util::update_local_user($server, $serveruser1);
        $this->assertEquals(3, $DB->count_records('user', array()));
        $this->assertTrue($DB->record_exists('auth_connect_users', array('serverid' => $server->id, 'serveruserid' => $serveruser1['id'])));
        $this->assertSame('connect', $user1->auth);
        $this->assertSame('tc_' . $serveruser1['id'] . '_' . $server->serveridnumber, $user1->username);
        $this->assertNotSame('not cached', $user1->password);
        $this->assertSame($serveruser1['password'], $user1->password);
        $this->assertSame('0', $user1->deleted);
        $this->assertSame('0', $user1->suspended);
        $this->assertSame($serveruser1['firstname'], $user1->firstname);
        $this->assertSame($serveruser1['lastname'], $user1->lastname);
        $this->assertSame($serveruser1['email'], $user1->email);

        // Update existing user.
        $serveruser1['username']  = 'xxx';
        $serveruser1['firstname'] = 'XX';
        $serveruser1['lastname']  = 'ZZ';
        $serveruser1['email']     = 'xx@example.com';
        $serveruser1['password']  = hash_internal_user_password('lalala');
        $user1b = util::update_local_user($server, $serveruser1);
        $this->assertEquals(3, $DB->count_records('user', array()));
        $this->assertTrue($DB->record_exists('auth_connect_users', array('serverid' => $server->id, 'serveruserid' => $serveruser1['id'])));
        $this->assertSame($user1->id, $user1b->id);
        $this->assertSame('connect', $user1b->auth);
        $this->assertSame('tc_' . $serveruser1['id'] . '_' . $server->serveridnumber, $user1b->username);
        $this->assertSame($serveruser1['password'], $user1b->password);
        $this->assertSame('0', $user1b->deleted);
        $this->assertSame('0', $user1b->suspended);
        $this->assertSame($serveruser1['firstname'], $user1b->firstname);
        $this->assertSame($serveruser1['lastname'], $user1b->lastname);
        $this->assertSame($serveruser1['email'], $user1b->email);
    }

    public function test_update_local_user_migration() {
        global $DB;
        $this->resetAfterTest();

        // Verify default settings.
        $this->assertSame('0', get_config('auth_connect', 'migrateusers'));
        $this->assertSame('username', get_config('auth_connect', 'migratemap'));

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');
        $server = $generator->create_server();

        $user1 = $this->getDataGenerator()->create_user(
            array('username' => 'xusername1', 'email' => 'xemail1@example.com', 'idnumber' => 'xidnumber1')
        );
        $user2 = $this->getDataGenerator()->create_user(
            array('username' => 'xusername2', 'email' => 'xemail2@example.com', 'idnumber' => 'xidnumber2')
        );
        $user3 = $this->getDataGenerator()->create_user(
            array('username' => 'xusername3', 'email' => 'xemail3@example.com', 'idnumber' => 'xidnumber3')
        );
        $user4 = $this->getDataGenerator()->create_user(
            array('username' => 'xusername4', 'email' => 'xemail4@example.com', 'idnumber' => 'xidnumber4')
        );
        $this->assertCount(6, $DB->get_records('user', array()));

        // No migration.
        set_config('migrateusers', '0', 'auth_connect');
        $serveruser1 = $generator->get_fake_server_user();
        $serveruser1['username'] = $user1->username;
        $serveruser1['email'] = $user1->email;
        $serveruser1['idnumber'] = $user1->idnumber;

        $newuser1 = util::update_local_user($server, $serveruser1);
        $this->assertInstanceOf('stdClass', $newuser1);
        $this->assertGreaterThan($user3->id, $newuser1->id);
        $this->assertStringStartsWith('tc_', $newuser1->username);
        $this->assertSame($user1->email, $newuser1->email);
        $this->assertSame('', $newuser1->idnumber);
        $this->assertCount(7, $DB->get_records('user', array()));

        // Migrate via username.
        set_config('migrateusers', '1', 'auth_connect');
        set_config('migratemap', 'username', 'auth_connect');
        $serveruser2 = $generator->get_fake_server_user();
        $serveruser2['username'] = $user2->username;

        $newuser2 = util::update_local_user($server, $serveruser2);
        $this->assertSame($user2->id, $newuser2->id);
        $this->assertSame($user2->username, $newuser2->username); // Always keep username for existing accounts.
        $this->assertSame($serveruser2['email'], $newuser2->email);
        $this->assertSame($serveruser2['idnumber'], $newuser2->idnumber);
        $this->assertCount(7, $DB->get_records('user', array()));

        // Migrate via email.
        set_config('migrateusers', '1', 'auth_connect');
        set_config('migratemap', 'email', 'auth_connect');
        $serveruser3 = $generator->get_fake_server_user();
        $serveruser3['email'] = $user3->email;

        $newuser3 = util::update_local_user($server, $serveruser3);
        $this->assertSame($user3->id, $newuser3->id);
        $this->assertSame($user3->email, $newuser3->email);
        $this->assertSame($user3->username, $newuser3->username); // Always keep username for existing accounts.
        $this->assertSame($serveruser3['idnumber'], $newuser3->idnumber);
        $this->assertCount(7, $DB->get_records('user', array()));

        // Migrate via idnumber.
        set_config('migrateusers', '1', 'auth_connect');
        set_config('migratemap', 'idnumber', 'auth_connect');
        $serveruser4 = $generator->get_fake_server_user();
        $serveruser4['idnumber'] = $user4->idnumber;

        $newuser4 = util::update_local_user($server, $serveruser4);
        $this->assertSame($user4->id, $newuser4->id);
        $this->assertSame($user4->idnumber, $newuser4->idnumber);
        $this->assertSame($user4->username, $newuser4->username); // Always keep username for existing accounts.
        $this->assertSame($serveruser4['email'], $newuser4->email);
        $this->assertCount(7, $DB->get_records('user', array()));

        // Migrate via TC unique id.
        set_config('migrateusers', '1', 'auth_connect');
        set_config('migratemap', 'uniqueid', 'auth_connect');
        $serveruser5 = $generator->get_fake_server_user();
        $uniqueid = 'tc_' . $serveruser5['id'] . '_' . $server->serveridnumber;
        $user5 = $this->getDataGenerator()->create_user(array('username' => $uniqueid));

        $newuser5 = util::update_local_user($server, $serveruser5);
        $this->assertSame($user5->id, $newuser5->id);
        $this->assertSame($uniqueid, $newuser5->username);
        $this->assertSame($serveruser5['email'], $newuser5->email);
        $this->assertSame($serveruser5['idnumber'], $newuser5->idnumber);
        $this->assertCount(8, $DB->get_records('user', array()));

        // Do not migrate accounts from connect auth.
        set_config('migrateusers', '1', 'auth_connect');
        set_config('migratemap', 'username', 'auth_connect');
        $user6 = $this->getDataGenerator()->create_user(
            array('username' => 'xusername6', 'email' => 'xemail6@example.com', 'idnumber' => 'xidnumber6', 'auth' => 'connect')
        );
        $serveruser6 = $generator->get_fake_server_user();
        $serveruser6['username'] = $user6->username;

        $newuser6 = util::update_local_user($server, $serveruser6);
        $this->assertGreaterThan($user6->id, $newuser6->id);
        $this->assertStringStartsWith('tc_', $newuser6->username);
        $this->assertSame($serveruser6['email'], $newuser6->email);
        $this->assertSame($serveruser6['idnumber'], $newuser6->idnumber);
        $this->assertCount(10, $DB->get_records('user', array()));
    }

    public function test_sync_user_collections() {
        global $DB;
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');
        $server = $generator->create_server();

        $servercohorts = array();
        $servercohorts[] = $generator->get_fake_server_cohort();
        $servercohorts[] = $generator->get_fake_server_cohort();
        $servercourses = array();
        $servercourses[] = $generator->get_fake_server_course();
        $servercourses[] = $generator->get_fake_server_course();
        $collections = array('cohort' => $servercohorts, 'course' => $servercourses);

        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => $collections)));
        $result = util::sync_user_collections($server);
        $this->assertTrue($result);
        $this->assertCount(4, $DB->get_records('cohort', array()));

        jsend::set_phpunit_testdata(array(array('status' => 'error', 'message' => 'xxx')));
        $result = util::sync_user_collections($server);
        $this->assertFalse($result);
        $this->assertCount(4, $DB->get_records('cohort', array()));
    }

    public function test_update_local_user_collections() {
        global $DB;
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');
        $server = $generator->create_server();

        $servercohorts = array();
        $servercohorts[] = $generator->get_fake_server_cohort();
        $servercohorts[] = $generator->get_fake_server_cohort();

        $servercourses = array();
        $servercourses[] = $generator->get_fake_server_course();
        $servercourses[] = $generator->get_fake_server_course();

        // Adding.
        $collections = array('cohort' => $servercohorts, 'course' => $servercourses);
        $this->assertCount(0, $DB->get_records('cohort', array()));
        util::update_local_user_collections($server, $collections);
        $this->assertCount(4, $DB->get_records('cohort', array()));
        $cohorts = $this->fetch_local_server_cohorts($server, 'cohort', $servercohorts);
        $this->assertNotNull($cohorts[0]);
        $this->assertNotNull($cohorts[1]);
        $courses = $this->fetch_local_server_cohorts($server, 'course', $servercourses);
        $this->assertNotNull($courses[0]);
        $this->assertNotNull($courses[1]);

        // Deleting
        $collections = array('cohort' => array($servercohorts[0]), 'course' => array($servercourses[0]));
        util::update_local_user_collections($server, $collections);
        $this->assertCount(2, $DB->get_records('cohort', array()));
        $cohorts = $this->fetch_local_server_cohorts($server, 'cohort', $servercohorts);
        $this->assertNotNull($cohorts[0]);
        $this->assertNull($cohorts[1]);
        $courses = $this->fetch_local_server_cohorts($server, 'course', $servercourses);
        $this->assertNotNull($courses[0]);
        $this->assertNull($courses[1]);
    }

    public function test_update_local_user_collection_cohort_properties() {
        global $DB;
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');
        $server = $generator->create_server();

        $servercohorts = array();
        $servercohorts[] = $generator->get_fake_server_cohort();

        // Test adding.
        $this->assertCount(0, $DB->get_records('cohort', array()));
        util::update_local_user_collection($server, 'cohort', $servercohorts[0]);
        $this->assertCount(1, $DB->get_records('cohort', array()));
        $cohorts = $this->fetch_local_server_cohorts($server, 'cohort', $servercohorts);
        $this->assertSame($servercohorts[0]['name'], $cohorts[0]->name);
        $this->assertSame('tc_cohort_' . $servercohorts[0]['id'] . '_' . $server->serveridnumber, $cohorts[0]->idnumber);
        $this->assertSame($servercohorts[0]['description'], $cohorts[0]->description);
        $this->assertSame($servercohorts[0]['descriptionformat'], $cohorts[0]->descriptionformat);
        $this->assertSame('auth_connect', $cohorts[0]->component);

        // Test updating.
        $servercohorts[0]['name'] = 'xxxx';
        $servercohorts[0]['description'] = 'aassasa';
        $servercohorts[0]['component'] = 'auth_ldap';
        util::update_local_user_collection($server, 'cohort', $servercohorts[0]);
        $this->assertCount(1, $DB->get_records('cohort', array()));
        $cohorts = $this->fetch_local_server_cohorts($server, 'cohort', $servercohorts);
        $this->assertSame($servercohorts[0]['name'], $cohorts[0]->name);
        $this->assertSame('tc_cohort_' . $servercohorts[0]['id'] . '_' . $server->serveridnumber, $cohorts[0]->idnumber);
        $this->assertSame($servercohorts[0]['description'], $cohorts[0]->description);
        $this->assertSame($servercohorts[0]['descriptionformat'], $cohorts[0]->descriptionformat);
        $this->assertSame('auth_connect', $cohorts[0]->component);
    }

    public function test_update_local_user_collection_cohort_membership() {
        global $DB;
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');
        $server = $generator->create_server();

        $serverusers = array();
        $serverusers[] = $generator->get_fake_server_user();
        $serverusers[] = $generator->get_fake_server_user();
        $serverusers[] = $generator->get_fake_server_user();
        util::update_local_users($server, $serverusers);
        $users = $this->fetch_local_server_users($server, $serverusers);

        $servercohorts = array();
        $servercohorts[] = $generator->get_fake_server_cohort();

        // Test user membership sync.
        $this->assertCount(0, $DB->get_records('cohort', array()));
        $this->assertCount(0, $DB->get_records('cohort_members', array()));

        $servercohorts[0]['members'] = array(array('id' => $serverusers[0]['id']), array('id' => $serverusers[1]['id']));
        util::update_local_user_collection($server, 'cohort', $servercohorts[0]);
        $this->assertCount(1, $DB->get_records('cohort', array()));
        $this->assertCount(2, $DB->get_records('cohort_members', array()));

        $cohorts = $this->fetch_local_server_cohorts($server, 'cohort', $servercohorts);
        $this->assertTrue(cohort_is_member($cohorts[0]->id, $users[0]->id));
        $this->assertTrue(cohort_is_member($cohorts[0]->id, $users[1]->id));

        $servercohorts[0]['members']= array(array('id' => $serverusers[0]['id']));
        util::update_local_user_collection($server, 'cohort', $servercohorts[0]);
        $this->assertCount(1, $DB->get_records('cohort', array()));
        $this->assertCount(1, $DB->get_records('cohort_members', array()));

        $cohorts = $this->fetch_local_server_cohorts($server, 'cohort', $servercohorts);
        $this->assertTrue(cohort_is_member($cohorts[0]->id, $users[0]->id));
        $this->assertFalse(cohort_is_member($cohorts[0]->id, $users[1]->id));
    }

    public function test_update_local_user_collection_course_properties() {
        global $DB;
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');
        $server = $generator->create_server();

        $servercourses = array();
        $servercourses[] = $generator->get_fake_server_course();

        // Test adding.
        $this->assertCount(0, $DB->get_records('cohort', array()));
        util::update_local_user_collection($server, 'course', $servercourses[0]);
        $this->assertCount(1, $DB->get_records('cohort', array()));
        $cohorts = $this->fetch_local_server_cohorts($server, 'course', $servercourses);
        $this->assertSame($servercourses[0]['fullname'], $cohorts[0]->name);
        $this->assertSame('tc_course_' . $servercourses[0]['id'] . '_' . $server->serveridnumber, $cohorts[0]->idnumber);
        $this->assertSame($servercourses[0]['summary'], $cohorts[0]->description);
        $this->assertSame($servercourses[0]['summaryformat'], $cohorts[0]->descriptionformat);
        $this->assertSame('auth_connect', $cohorts[0]->component);

        // Test updating.
        $servercourses[0]['fullname'] = 'xxxx';
        $servercourses[0]['summary'] = 'aassasa';
        $servercourses[0]['component'] = 'auth_ldap';
        util::update_local_user_collection($server, 'course', $servercourses[0]);
        $this->assertCount(1, $DB->get_records('cohort', array()));
        $cohorts = $this->fetch_local_server_cohorts($server, 'course', $servercourses);
        $this->assertSame($servercourses[0]['fullname'], $cohorts[0]->name);
        $this->assertSame('tc_course_' . $servercourses[0]['id'] . '_' . $server->serveridnumber, $cohorts[0]->idnumber);
        $this->assertSame($servercourses[0]['summary'], $cohorts[0]->description);
        $this->assertSame($servercourses[0]['summaryformat'], $cohorts[0]->descriptionformat);
        $this->assertSame('auth_connect', $cohorts[0]->component);
    }

    public function test_update_local_user_collection_course_membership() {
        global $DB;
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');
        $server = $generator->create_server();

        $serverusers = array();
        $serverusers[] = $generator->get_fake_server_user();
        $serverusers[] = $generator->get_fake_server_user();
        $serverusers[] = $generator->get_fake_server_user();
        util::update_local_users($server, $serverusers);
        $users = $this->fetch_local_server_users($server, $serverusers);

        $servercourses = array();
        $servercourses[] = $generator->get_fake_server_course();

        // Test user membership sync.
        $this->assertCount(0, $DB->get_records('cohort', array()));
        $this->assertCount(0, $DB->get_records('cohort_members', array()));

        $servercourses[0]['members'] = array(array('id' => $serverusers[0]['id']), array('id' => $serverusers[1]['id']));
        util::update_local_user_collection($server, 'course', $servercourses[0]);
        $this->assertCount(1, $DB->get_records('cohort', array()));
        $this->assertCount(2, $DB->get_records('cohort_members', array()));

        $cohorts = $this->fetch_local_server_cohorts($server, 'course', $servercourses);
        $this->assertTrue(cohort_is_member($cohorts[0]->id, $users[0]->id));
        $this->assertTrue(cohort_is_member($cohorts[0]->id, $users[1]->id));

        $servercourses[0]['members']= array(array('id' => $serverusers[0]['id']));
        util::update_local_user_collection($server, 'course', $servercourses[0]);
        $this->assertCount(1, $DB->get_records('cohort', array()));
        $this->assertCount(1, $DB->get_records('cohort_members', array()));

        $cohorts = $this->fetch_local_server_cohorts($server, 'course', $servercourses);
        $this->assertTrue(cohort_is_member($cohorts[0]->id, $users[0]->id));
        $this->assertFalse(cohort_is_member($cohorts[0]->id, $users[1]->id));
    }

    public function test_update_local_user_pictures() {
        global $DB;
        $this->resetAfterTest();

        set_config('syncpasswords', 0, 'totara_connect');
        $fs = get_file_storage();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');
        $server = $generator->create_server();
        $this->assertEquals(2, $DB->count_records('user', array()));

        // Add new user from server.
        $serveruser1 = $generator->get_fake_server_user(array('password' => 'testpw'));
        $serveruser1['picture'] = '100';
        $serveruser1['pictures'] = array(
            'f1.png' => base64_encode('xx'),
            'f2.png' => base64_encode('xxx'),
            'f3.png' => base64_encode('xxxx'),
        );
        $user1 = util::update_local_user($server, $serveruser1, true);
        $this->assertSame($serveruser1['firstname'], $user1->firstname);
        $this->assertSame($serveruser1['lastname'], $user1->lastname);
        $this->assertSame($serveruser1['picture'], $user1->picture);
        $this->assertObjectNotHasAttribute('pictures', $user1);
        $this->assertTrue($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f1.png'));
        $this->assertTrue($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f2.png'));
        $this->assertTrue($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f3.png'));

        // Update pictures only if picture flag changes.
        $serveruser1['picture'] = '100';
        $serveruser1['pictures'] = array(
            'f1.jpg' => base64_encode('xx'),
            'f2.jpg' => base64_encode('xxx'),
            'f3.jpg' => base64_encode('xxxx'),
        );
        $user1 = util::update_local_user($server, $serveruser1, true);
        $this->assertSame($serveruser1['firstname'], $user1->firstname);
        $this->assertSame($serveruser1['lastname'], $user1->lastname);
        $this->assertSame($serveruser1['picture'], $user1->picture);
        $this->assertObjectNotHasAttribute('pictures', $user1);
        $this->assertTrue($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f1.png'));
        $this->assertTrue($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f2.png'));
        $this->assertTrue($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f3.png'));
        $this->assertFalse($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f1.jpg'));
        $this->assertFalse($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f2.jpg'));
        $this->assertFalse($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f3.jpg'));

        $serveruser1['picture'] = '101';
        $serveruser1['pictures'] = array(
            'f1.jpg' => base64_encode('xx'),
            'f2.jpg' => base64_encode('xxx'),
            'f3.jpg' => base64_encode('xxxx'),
        );
        $user1 = util::update_local_user($server, $serveruser1, true);
        $this->assertSame($serveruser1['firstname'], $user1->firstname);
        $this->assertSame($serveruser1['lastname'], $user1->lastname);
        $this->assertSame($serveruser1['picture'], $user1->picture);
        $this->assertObjectNotHasAttribute('pictures', $user1);
        $this->assertFalse($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f1.png'));
        $this->assertFalse($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f2.png'));
        $this->assertFalse($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f3.png'));
        $this->assertTrue($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f1.jpg'));
        $this->assertTrue($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f2.jpg'));
        $this->assertTrue($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f3.jpg'));

        // Delete pictures.
        $serveruser1['picture'] = '0';
        $serveruser1['pictures'] = array();
        $user1 = util::update_local_user($server, $serveruser1, true);
        $this->assertSame($serveruser1['firstname'], $user1->firstname);
        $this->assertSame($serveruser1['lastname'], $user1->lastname);
        $this->assertSame($serveruser1['picture'], $user1->picture);
        $this->assertObjectNotHasAttribute('pictures', $user1);
        $this->assertFalse($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f1.png'));
        $this->assertFalse($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f2.png'));
        $this->assertFalse($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f3.png'));
        $this->assertFalse($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f1.jpg'));
        $this->assertFalse($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f2.jpg'));
        $this->assertFalse($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f3.jpg'));
    }

    public function test_update_local_users_profile_fields() {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        /** @var auth_connect_generator $connectgenerator */
        $connectgenerator = $this->getDataGenerator()->get_plugin_generator('auth_connect');

        $DB->insert_record('user_info_field', (object)array('shortname' => 'n1', 'name' => 'n 1', 'categoryid' => 1, 'datatype' => 'checkbox'));
        $DB->insert_record('user_info_field', (object)array('shortname' => 't1', 'name' => 't 1', 'categoryid' => 1, 'datatype' => 'text'));
        $fields = $DB->get_records('user_info_field', array(), 'shortname ASC');
        $fields = array_values($fields);
        $this->assertSame('checkbox', $fields[0]->datatype);
        $this->assertSame('text', $fields[1]->datatype);

        $server = $connectgenerator->create_server(array('apiversion' => 2));
        set_config('syncprofilefields', 1, 'auth_connect');

        $serverusers = array();
        $serverusers[0] = $connectgenerator->get_fake_server_user();
        $serverusers[0]['profile_fields'] = array();
        $serverusers[1] = $connectgenerator->get_fake_server_user();
        $serverusers[1]['profile_fields'] = array(
            array(
                'shortname' => $fields[0]->shortname,
                'datatype' => $fields[0]->datatype,
                'data' => '1',
            ),
            array(
                'shortname' => $fields[1]->shortname,
                'datatype' => $fields[1]->datatype,
                'data' => 'some small text',
            ),
        );

        // Test adding matching data.
        util::update_local_users($server, $serverusers);
        $newfielddatas = $DB->get_records('user_info_data', array(), 'id ASC');
        $this->assertCount(2, $newfielddatas);
        $newfielddatas = array_values($newfielddatas);
        $this->assertSame($fields[0]->id, $newfielddatas[0]->fieldid);
        $this->assertSame($serverusers[1]['profile_fields'][0]['data'], $newfielddatas[0]->data);
        $this->assertSame($fields[1]->id, $newfielddatas[1]->fieldid);
        $this->assertSame($serverusers[1]['profile_fields'][1]['data'], $newfielddatas[1]->data);

        // Test adding mismatched data.
        $DB->delete_records('pos');
        $DB->delete_records('user_info_data');
        $serverusers[1]['profile_fields'] = array(
            array(
                'shortname' => $fields[0]->shortname. 'xxx',
                'datatype' => $fields[0]->datatype,
                'data' => '1',
            ),
            array(
                'shortname' => $fields[1]->shortname,
                'datatype' => $fields[1]->datatype . 'xxx',
                'data' => 'some small text',
            ),
        );
        util::update_local_users($server, $serverusers);
        $newfielddatas = $DB->get_records('user_info_data', array(), 'id ASC');
        $this->assertCount(0, $newfielddatas);

        // Test updating data.
        $DB->delete_records('pos');
        $DB->delete_records('user_info_data');
        $serverusers[1]['profile_fields'] = array(
            array(
                'shortname' => $fields[0]->shortname,
                'datatype' => $fields[0]->datatype,
                'data' => '1',
            ),
            array(
                'shortname' => $fields[1]->shortname,
                'datatype' => $fields[1]->datatype,
                'data' => 'some small text',
            ),
        );
        util::update_local_users($server, $serverusers);
        $newfielddatas = $DB->get_records('user_info_data', array(), 'id ASC');
        $this->assertCount(2, $newfielddatas);

        $serverusers[1]['profile_fields'] = array(
            array(
                'shortname' => $fields[0]->shortname,
                'datatype' => $fields[0]->datatype,
                'data' => '0',
            ),
            array(
                'shortname' => $fields[1]->shortname,
                'datatype' => $fields[1]->datatype,
                'data' => 'some small text 2',
            ),
        );
        util::update_local_users($server, $serverusers);
        $newfielddatas = $DB->get_records('user_info_data', array(), 'id ASC');
        $this->assertCount(2, $newfielddatas);
        $newfielddatas = array_values($newfielddatas);
        $this->assertSame($fields[0]->id, $newfielddatas[0]->fieldid);
        $this->assertSame($serverusers[1]['profile_fields'][0]['data'], $newfielddatas[0]->data);
        $this->assertSame($fields[1]->id, $newfielddatas[1]->fieldid);
        $this->assertSame($serverusers[1]['profile_fields'][1]['data'], $newfielddatas[1]->data);

        // Test updating mismatched data.
        $serverusers[1]['profile_fields'] = array(
            array(
                'shortname' => $fields[0]->shortname. 'xxx',
                'datatype' => $fields[0]->datatype,
                'data' => '1',
            ),
            array(
                'shortname' => $fields[1]->shortname,
                'datatype' => $fields[1]->datatype . 'xxx',
                'data' => 'some small text',
            ),
        );

        util::update_local_users($server, $serverusers);
        $newfielddatas = $DB->get_records('user_info_data', array(), 'id ASC');
        $this->assertCount(0, $newfielddatas);
    }

    public function test_update_local_user_jobs() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        /** @var auth_connect_generator $connectgenerator */
        $connectgenerator = $this->getDataGenerator()->get_plugin_generator('auth_connect');

        $server = $connectgenerator->create_server(array('apiversion' => 2));

        set_config('syncpositions', 1, 'auth_connect');
        set_config('syncorganisations', 1, 'auth_connect');

        $serverusers = array();
        $serverusers[0] = $connectgenerator->get_fake_server_user();
        $serverusers[1] = $connectgenerator->get_fake_server_user();
        $serverusers[2] = $connectgenerator->get_fake_server_user();
        $serverusers[3] = $connectgenerator->get_fake_server_user();

        $posframeworks = array();
        $posframeworks[0] = array();
        $posframeworks[0]['id'] = '11';
        $posframeworks[0]['shortname'] = 'fw3';
        $posframeworks[0]['idnumber'] = 'fwid3';
        $posframeworks[0]['description'] = 'a b c';
        $posframeworks[0]['sortorder'] = '1';
        $posframeworks[0]['timecreated'] = '400';
        $posframeworks[0]['timeupdated'] = '500';
        $posframeworks[0]['usermodified'] = '66';
        $posframeworks[0]['visible'] = '1';
        $posframeworks[0]['hidecustomfields'] = '0';
        $posframeworks[0]['fullname'] = 'framework 3';
        $positions = array();
        $positions[0] = array();
        $positions[0]['id'] = '19';
        $positions[0]['shortname'] = 'isn0';
        $positions[0]['idnumber'] = 'iid0';
        $positions[0]['description'] = 'abc';
        $positions[0]['frameworkid'] = $posframeworks[0]['id'];
        $positions[0]['path'] = '/19';
        $positions[0]['visible'] = '1';
        $positions[0]['timevalidfrom'] = null;
        $positions[0]['timevalidto'] = null;
        $positions[0]['timecreated'] = '100';
        $positions[0]['timemodified'] = '200';
        $positions[0]['usermodified'] = '77';
        $positions[0]['fullname'] = 'item full name 0';
        $positions[0]['parentid'] = '0';
        $positions[0]['depthlevel'] = '1';
        $positions[0]['typeid'] = '0';
        $positions[0]['typeidnumber'] = null;
        $positions[0]['sortthread'] = '01';
        $positions[0]['totarasync'] = '0';
        $positions[1]['id'] = '30';
        $positions[1]['shortname'] = 'otherpos';
        $positions[1]['idnumber'] = '';
        $positions[1]['description'] = 'Another position';
        $positions[1]['frameworkid'] = $posframeworks[0]['id'];
        $positions[1]['path'] = '/19/30';
        $positions[1]['visible'] = '1';
        $positions[1]['timevalidfrom'] = null;
        $positions[1]['timevalidto'] = null;
        $positions[1]['timecreated'] = '100';
        $positions[1]['timemodified'] = '200';
        $positions[1]['usermodified'] = '77';
        $positions[1]['fullname'] = 'item full name 0';
        $positions[1]['parentid'] = '19';
        $positions[1]['depthlevel'] = '2';
        $positions[1]['typeid'] = '0';
        $positions[1]['typeidnumber'] = null;
        $positions[1]['sortthread'] = '01.01';
        $positions[1]['totarasync'] = '0';
        util::update_local_hierarchy($server, 'pos', $posframeworks, $positions);

        $orgframeworks = array();
        $orgframeworks[0] = array();
        $orgframeworks[0]['id'] = '11';
        $orgframeworks[0]['shortname'] = 'fw3';
        $orgframeworks[0]['idnumber'] = 'fwid3';
        $orgframeworks[0]['description'] = 'a b c';
        $orgframeworks[0]['sortorder'] = '1';
        $orgframeworks[0]['timecreated'] = '400';
        $orgframeworks[0]['timeupdated'] = '500';
        $orgframeworks[0]['usermodified'] = '66';
        $orgframeworks[0]['visible'] = '1';
        $orgframeworks[0]['hidecustomfields'] = '0';
        $orgframeworks[0]['fullname'] = 'framework 3';
        $organisations = array();
        $organisations[0] = array();
        $organisations[0]['id'] = '19';
        $organisations[0]['shortname'] = 'isn0';
        $organisations[0]['idnumber'] = 'iid0';
        $organisations[0]['description'] = 'abc';
        $organisations[0]['frameworkid'] = $orgframeworks[0]['id'];
        $organisations[0]['path'] = '/19';
        $organisations[0]['visible'] = '1';
        $organisations[0]['timevalidfrom'] = null;
        $organisations[0]['timevalidto'] = null;
        $organisations[0]['timecreated'] = '100';
        $organisations[0]['timemodified'] = '200';
        $organisations[0]['usermodified'] = '77';
        $organisations[0]['fullname'] = 'item full name 0';
        $organisations[0]['parentid'] = '0';
        $organisations[0]['depthlevel'] = '1';
        $organisations[0]['typeid'] = '0';
        $organisations[0]['typeidnumber'] = null;
        $organisations[0]['sortthread'] = '01';
        $organisations[0]['totarasync'] = '0';
        util::update_local_hierarchy($server, 'org', $orgframeworks, $organisations);

        $serverusers[0]['jobs'][0] = array(
            'id' => '334000',
            'fullname' => 'full name 1',
            'shortname' => 'short 1',
            'idnumber' => 'idn1',
            'description' => 'desc 1',
            'startdate' => '1476069241',
            'enddate' => '1476069241',
            'timecreated' => '1477005241',
            'timemodified' => '1477005241',
            'usermodified' => '0',
            'positionid' => $positions[0]['id'],
            'positionassignmentdate' => '1477005241',
            'organisationid' => $organisations[0]['id'],
            'sortorder' => '1',
        );
        $serverusers[0]['jobs'][1] = array(
            'id' => '334001',
            'fullname' => null,
            'shortname' => null,
            'idnumber' => 'idn2',
            'description' => null,
            'startdate' => null,
            'enddate' => null,
            'timecreated' => '1477005434',
            'timemodified' => '1477005434',
            'usermodified' => $serverusers[3]['id'],
            'positionid' => null,
            'positionassignmentdate' => '1477005434',
            'organisationid' => null,
            'sortorder' => '2',
        );
        $serverusers[1]['jobs'][0] = array(
            'id' => '334002',
            'fullname' => 'full name 1',
            'shortname' => 'short 1',
            'idnumber' => 'idn1',
            'description' => 'desc 1',
            'startdate' => '1476069560',
            'enddate' => '1476069560',
            'timecreated' => '1477005560',
            'timemodified' => '1477005560',
            'usermodified' => '0',
            'positionid' => $positions[1]['id'],
            'positionassignmentdate' => '1477005560',
            'organisationid' => $organisations[0]['id'],
            'sortorder' => '1',
        );
        $serverusers[2]['jobs'] = array();
        $serverusers[3]['jobs'] = array();

        $posrecords = $DB->get_records('pos', array(), 'id ASC');
        $posrecords = array_values($posrecords);
        $this->assertCount(2, $posrecords);

        $orgrecords = $DB->get_records('org', array(), 'id ASC');
        $orgrecords = array_values($orgrecords);
        $this->assertCount(1, $orgrecords);

        set_config('syncjobs', 1, 'auth_connect');
        $this->setCurrentTimeStart();
        util::update_local_users($server, $serverusers);
        $users = $this->fetch_local_server_users($server, $serverusers);
        $this->assertCount(3, $DB->get_records('job_assignment'));
        $ja1a = $DB->get_record('job_assignment', array('userid' => $users[0]->id, 'idnumber' => $serverusers[0]['jobs'][0]['idnumber']), '*', MUST_EXIST);
        $this->assertSame($serverusers[0]['jobs'][0]['fullname'], $ja1a->fullname);
        $this->assertSame($serverusers[0]['jobs'][0]['shortname'], $ja1a->shortname);
        $this->assertSame($serverusers[0]['jobs'][0]['description'], $ja1a->description);
        $this->assertSame($serverusers[0]['jobs'][0]['startdate'], $ja1a->startdate);
        $this->assertSame($serverusers[0]['jobs'][0]['enddate'], $ja1a->enddate);
        $this->assertTimeCurrent($ja1a->timecreated);
        $this->assertTimeCurrent($ja1a->timemodified);
        $this->assertSame('0', $ja1a->usermodified);
        $this->assertSame($posrecords[0]->id, $ja1a->positionid);
        $this->assertSame($orgrecords[0]->id, $ja1a->organisationid);
        $this->assertSame('1', $ja1a->sortorder);
        $ja1b = $DB->get_record('job_assignment', array('userid' => $users[0]->id, 'idnumber' => $serverusers[0]['jobs'][1]['idnumber']), '*', MUST_EXIST);
        $this->assertSame($serverusers[0]['jobs'][1]['fullname'], $ja1b->fullname);
        $this->assertSame($serverusers[0]['jobs'][1]['shortname'], $ja1b->shortname);
        $this->assertSame($serverusers[0]['jobs'][1]['description'], $ja1b->description);
        $this->assertSame($serverusers[0]['jobs'][1]['startdate'], $ja1b->startdate);
        $this->assertSame($serverusers[0]['jobs'][1]['enddate'], $ja1b->enddate);
        $this->assertTimeCurrent($ja1b->timecreated);
        $this->assertTimeCurrent($ja1b->timemodified);
        $this->assertSame('0', $ja1b->usermodified);
        $this->assertSame(null, $ja1b->positionid);
        $this->assertSame(null, $ja1b->organisationid);
        $this->assertSame('2', $ja1b->sortorder);
        $ja2 = $DB->get_record('job_assignment', array('userid' => $users[1]->id, 'idnumber' => $serverusers[1]['jobs'][0]['idnumber']), '*', MUST_EXIST);
        $this->assertSame($serverusers[1]['jobs'][0]['fullname'], $ja2->fullname);
        $this->assertSame($serverusers[1]['jobs'][0]['shortname'], $ja2->shortname);
        $this->assertSame($serverusers[1]['jobs'][0]['description'], $ja2->description);
        $this->assertSame($serverusers[1]['jobs'][0]['startdate'], $ja2->startdate);
        $this->assertSame($serverusers[1]['jobs'][0]['enddate'], $ja2->enddate);
        $this->assertTimeCurrent($ja2->timecreated);
        $this->assertTimeCurrent($ja2->timemodified);
        $this->assertSame('0', $ja2->usermodified);
        $this->assertSame($posrecords[1]->id, $ja2->positionid);
        $this->assertSame($orgrecords[0]->id, $ja2->organisationid);
        $this->assertSame('1', $ja2->sortorder);

        // Change assignments.

        $serverusers[0]['jobs'][0] = array( // Unchanged.
            'id' => '334000',
            'fullname' => 'full name 1',
            'shortname' => 'short 1',
            'idnumber' => 'idn1',
            'description' => 'desc 1',
            'startdate' => '1476069241',
            'enddate' => '1476069241',
            'timecreated' => '1477005241',
            'timemodified' => '1477005241',
            'usermodified' => '0',
            'positionid' => $positions[0]['id'],
            'positionassignmentdate' => '1477005241',
            'organisationid' => $organisations[0]['id'],
            'sortorder' => '1',
        );
        $serverusers[0]['jobs'][1] = array( // New.
            'id' => '111111',
            'fullname' => null,
            'shortname' => null,
            'idnumber' => 'idn3',
            'description' => null,
            'startdate' => null,
            'enddate' => null,
            'timecreated' => '1477005430',
            'timemodified' => '1477005430',
            'usermodified' => $serverusers[3]['id'],
            'positionid' => null,
            'positionassignmentdate' => '1477005434',
            'organisationid' => null,
            'sortorder' => '2',
        );
        $serverusers[0]['jobs'][2] = array( // Updated.
            'id' => '334001',
            'fullname' => 'lala',
            'shortname' => null,
            'idnumber' => 'idn2',
            'description' => null,
            'startdate' => null,
            'enddate' => null,
            'timecreated' => '1477005434',
            'timemodified' => '1477005434',
            'usermodified' => $serverusers[3]['id'],
            'positionid' => $positions[1]['id'],
            'positionassignmentdate' => '1477005434',
            'organisationid' => $organisations[0]['id'],
            'sortorder' => '3',
        );
        $serverusers[1]['jobs'] = array();

        $this->setCurrentTimeStart();
        util::update_local_users($server, $serverusers);
        $users = $this->fetch_local_server_users($server, $serverusers);
        $this->assertCount(3, $DB->get_records('job_assignment'));
        $ja1ax = $DB->get_record('job_assignment', array('userid' => $users[0]->id, 'idnumber' => $serverusers[0]['jobs'][0]['idnumber']), '*', MUST_EXIST);
        $this->assertSame($serverusers[0]['jobs'][0]['fullname'], $ja1ax->fullname);
        $this->assertSame($serverusers[0]['jobs'][0]['shortname'], $ja1ax->shortname);
        $this->assertSame($serverusers[0]['jobs'][0]['description'], $ja1ax->description);
        $this->assertSame($serverusers[0]['jobs'][0]['startdate'], $ja1ax->startdate);
        $this->assertSame($serverusers[0]['jobs'][0]['enddate'], $ja1ax->enddate);
        $this->assertSame($ja1a->timecreated, $ja1ax->timecreated);
        $this->assertSame($ja1a->timemodified, $ja1ax->timemodified);
        $this->assertSame('0', $ja1ax->usermodified);
        $this->assertSame($posrecords[0]->id, $ja1ax->positionid);
        $this->assertSame($orgrecords[0]->id, $ja1ax->organisationid);
        $this->assertSame('1', $ja1ax->sortorder);

        $ja1cx = $DB->get_record('job_assignment', array('userid' => $users[0]->id, 'idnumber' => $serverusers[0]['jobs'][1]['idnumber']), '*', MUST_EXIST);
        $this->assertSame($serverusers[0]['jobs'][1]['fullname'], $ja1cx->fullname);
        $this->assertSame($serverusers[0]['jobs'][1]['shortname'], $ja1cx->shortname);
        $this->assertSame($serverusers[0]['jobs'][1]['description'], $ja1cx->description);
        $this->assertSame($serverusers[0]['jobs'][1]['startdate'], $ja1cx->startdate);
        $this->assertSame($serverusers[0]['jobs'][1]['enddate'], $ja1cx->enddate);
        $this->assertTimeCurrent($ja1cx->timecreated);
        $this->assertTimeCurrent($ja1cx->timemodified);
        $this->assertSame('0', $ja1cx->usermodified);
        $this->assertSame(null, $ja1cx->positionid);
        $this->assertSame(null, $ja1cx->organisationid);
        $this->assertSame('2', $ja1cx->sortorder);

        $ja1bx = $DB->get_record('job_assignment', array('userid' => $users[0]->id, 'idnumber' => $serverusers[0]['jobs'][2]['idnumber']), '*', MUST_EXIST);
        $this->assertSame($serverusers[0]['jobs'][2]['fullname'], $ja1bx->fullname);
        $this->assertSame($serverusers[0]['jobs'][2]['shortname'], $ja1bx->shortname);
        $this->assertSame($serverusers[0]['jobs'][2]['description'], $ja1bx->description);
        $this->assertSame($serverusers[0]['jobs'][2]['startdate'], $ja1bx->startdate);
        $this->assertSame($serverusers[0]['jobs'][2]['enddate'], $ja1bx->enddate);
        $this->assertSame($ja1b->timecreated, $ja1bx->timecreated);
        $this->assertTimeCurrent($ja1bx->timemodified);
        $this->assertSame('0', $ja1bx->usermodified);
        $this->assertSame($posrecords[1]->id, $ja1bx->positionid);
        $this->assertSame($orgrecords[0]->id, $ja1bx->organisationid);
        $this->assertSame('3', $ja1bx->sortorder);

        // Verify manual job assignments are not removed.
        \totara_job\job_assignment::create_default($users[0]->id, array('idnumber' => 'pokus'));
        $this->setCurrentTimeStart();
        util::update_local_users($server, $serverusers);
        $users = $this->fetch_local_server_users($server, $serverusers);
        $this->assertCount(4, $DB->get_records('job_assignment'));
        $jap = $DB->get_record('job_assignment', array('userid' => $users[0]->id, 'idnumber' => 'pokus'), '*', MUST_EXIST);
        $this->assertSame('4', $jap->sortorder);
        $ja1ax = $DB->get_record('job_assignment', array('userid' => $users[0]->id, 'idnumber' => $serverusers[0]['jobs'][0]['idnumber']), '*', MUST_EXIST);
        $this->assertSame('1', $ja1ax->sortorder);
        $ja1cx = $DB->get_record('job_assignment', array('userid' => $users[0]->id, 'idnumber' => $serverusers[0]['jobs'][1]['idnumber']), '*', MUST_EXIST);
        $this->assertSame('2', $ja1cx->sortorder);
        $ja1bx = $DB->get_record('job_assignment', array('userid' => $users[0]->id, 'idnumber' => $serverusers[0]['jobs'][2]['idnumber']), '*', MUST_EXIST);
        $this->assertSame('3', $ja1bx->sortorder);

        // Make sure positions and organisations are not synced when disabled.
        set_config('syncpositions', 0, 'auth_connect');
        set_config('syncorganisations', 0, 'auth_connect');
        $this->setCurrentTimeStart();
        util::update_local_users($server, $serverusers);
        $users = $this->fetch_local_server_users($server, $serverusers);
        $this->assertCount(4, $DB->get_records('job_assignment'));
        $ja1ax = $DB->get_record('job_assignment', array('userid' => $users[0]->id, 'idnumber' => $serverusers[0]['jobs'][0]['idnumber']), '*', MUST_EXIST);
        $this->assertSame(null, $ja1ax->positionid);
        $this->assertSame(null, $ja1ax->organisationid);

        $ja1cx = $DB->get_record('job_assignment', array('userid' => $users[0]->id, 'idnumber' => $serverusers[0]['jobs'][1]['idnumber']), '*', MUST_EXIST);
        $this->assertSame(null, $ja1cx->positionid);
        $this->assertSame(null, $ja1cx->organisationid);
        $this->assertSame('2', $ja1cx->sortorder);

        $ja1bx = $DB->get_record('job_assignment', array('userid' => $users[0]->id, 'idnumber' => $serverusers[0]['jobs'][2]['idnumber']), '*', MUST_EXIST);
        $this->assertSame(null, $ja1bx->positionid);
        $this->assertSame(null, $ja1bx->organisationid);
        $this->assertSame('3', $ja1bx->sortorder);

        // Make sure nothing is removed when sync disabled.
        set_config('syncjobs', 0, 'auth_connect');
        $serverusers[0]['jobs'] = array();
        util::update_local_users($server, $serverusers);
        $this->assertCount(4, $DB->get_records('job_assignment'));
    }

    public function test_update_local_hierarchy_pos() {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $admin = get_admin();

        /** @var totara_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        /** @var auth_connect_generator $connectgenerator */
        $connectgenerator = $this->getDataGenerator()->get_plugin_generator('auth_connect');

        $server = $connectgenerator->create_server(array('apiversion' => 2));

        // Add some preexisting frameworks.
        $pos_framework0 = $hierarchygenerator->create_pos_frame(array('idnumber' => 'previd1', 'sortorder' => 1));

        // Nothing should change with no sync data.
        util::update_local_hierarchy($server, 'pos', array(), array());
        $result = $DB->get_records('pos_framework', array(), 'sortorder ASC');
        $this->assertCount(1, $result);
        $result = array_values($result);
        $this->assertEquals($pos_framework0, $result[0]);

        // Add and convert frameworks.
        $frameworks = array();
        $frameworks[1] = array();
        $frameworks[1]['id'] = '10';
        $frameworks[1]['shortname'] = 'fw1';
        $frameworks[1]['idnumber'] = 'fwid1';
        $frameworks[1]['description'] = 'a b c';
        $frameworks[1]['sortorder'] = '1';
        $frameworks[1]['timecreated'] = '100';
        $frameworks[1]['timeupdated'] = '200';
        $frameworks[1]['usermodified'] = '66';
        $frameworks[1]['visible'] = '1';
        $frameworks[1]['hidecustomfields'] = '0';
        $frameworks[1]['fullname'] = 'framework 1';
        $frameworks[2] = array();
        $frameworks[2]['id'] = '7';
        $frameworks[2]['shortname'] = 'fw2';
        $frameworks[2]['idnumber'] = 'fwid2';
        $frameworks[2]['description'] = 'a b c d';
        $frameworks[2]['sortorder'] = '2';
        $frameworks[2]['timecreated'] = '1000';
        $frameworks[2]['timeupdated'] = '2000';
        $frameworks[2]['usermodified'] = '666';
        $frameworks[2]['visible'] = '0';
        $frameworks[2]['hidecustomfields'] = '1';
        $frameworks[2]['fullname'] = 'framework 2';

        $this->setCurrentTimeStart();
        util::update_local_hierarchy($server, 'pos', $frameworks, array());
        $result = $DB->get_records('pos_framework', array(), 'sortorder ASC');
        $this->assertCount(3, $result);
        $result = array_values($result);

        $this->assertEquals($pos_framework0, $result[0]);

        $this->assertSame($frameworks[1]['shortname'], $result[1]->shortname);
        $this->assertSame($frameworks[1]['idnumber'], $result[1]->idnumber);
        $this->assertSame($frameworks[1]['description'], $result[1]->description);
        $this->assertSame('2', $result[1]->sortorder);
        $this->assertTimeCurrent($result[1]->timecreated);
        $this->assertTimeCurrent($result[1]->timemodified);
        $this->assertSame($user->id, $result[1]->usermodified);
        $this->assertSame($frameworks[1]['visible'], $result[1]->visible);
        $this->assertSame($frameworks[1]['hidecustomfields'], $result[1]->hidecustomfields);
        $this->assertSame($frameworks[1]['fullname'], $result[1]->fullname);

        $this->assertSame($frameworks[2]['shortname'], $result[2]->shortname);
        $this->assertSame($frameworks[2]['idnumber'], $result[2]->idnumber);
        $this->assertSame($frameworks[2]['description'], $result[2]->description);
        $this->assertSame('3', $result[2]->sortorder);
        $this->assertTimeCurrent($result[2]->timecreated);
        $this->assertTimeCurrent($result[2]->timemodified);
        $this->assertSame($user->id, $result[2]->usermodified);
        $this->assertSame($frameworks[2]['visible'], $result[2]->visible);
        $this->assertSame($frameworks[2]['hidecustomfields'], $result[2]->hidecustomfields);
        $this->assertSame($frameworks[2]['fullname'], $result[2]->fullname);

        // Run again should change nothing.
        util::update_local_hierarchy($server, 'pos', $frameworks, array());
        $resultframeworks = $DB->get_records('pos_framework', array(), 'sortorder ASC');
        $this->assertCount(3, $resultframeworks);
        $resultframeworks = array_values($resultframeworks);
        $this->assertEquals($result, $resultframeworks);

        // Test adding updating and removing items.
        $tid = $hierarchygenerator->create_pos_type(array('idnumber' => 'idnum1'));
        $pos_type1 = $DB->get_record('pos_type', array('id' => $tid));
        $tid =  $hierarchygenerator->create_pos_type(array('idnumber' => 'idnum2'));
        $pos_type2 = $DB->get_record('pos_type', array('id' => $tid));
        $tid = $hierarchygenerator->create_pos_type(array('idnumber' => ''));
        $pos_type3 = $DB->get_record('pos_type', array('id' => $tid));
        $pos0 = $hierarchygenerator->create_pos(array('frameworkid' => $pos_framework0->id, 'typeid' => $pos_type2->id, 'idnumber' => 'iidxxx')); // To be ignored.
        $pos1 = $hierarchygenerator->create_pos(array('frameworkid' => $result[1]->id, 'idnumber' => 'itemid3')); // To be deleted.

        $positions = array();
        $positions[1] = array();
        $positions[1]['id'] = '19';
        $positions[1]['shortname'] = 'isn0';
        $positions[1]['idnumber'] = 'iid0';
        $positions[1]['description'] = 'abc';
        $positions[1]['frameworkid'] = $frameworks[1]['id'];
        $positions[1]['path'] = '/19';
        $positions[1]['visible'] = '1';
        $positions[1]['timevalidfrom'] = null;
        $positions[1]['timevalidto'] = null;
        $positions[1]['timecreated'] = '100';
        $positions[1]['timemodified'] = '100';
        $positions[1]['usermodified'] = '66';
        $positions[1]['fullname'] = 'item full name 0';
        $positions[1]['parentid'] = '0';
        $positions[1]['depthlevel'] = '1';
        $positions[1]['typeid'] = '33';
        $positions[1]['typeidnumber'] = $pos_type1->idnumber;
        $positions[1]['sortthread'] = '01';
        $positions[1]['totarasync'] = '1';
        $positions[2] = array();
        $positions[2]['id'] = '20';
        $positions[2]['shortname'] = 'isn2';
        $positions[2]['idnumber'] = 'iid2';
        $positions[2]['description'] = 'abce';
        $positions[2]['frameworkid'] = $frameworks[2]['id'];
        $positions[2]['path'] = '/20';
        $positions[2]['visible'] = '0';
        $positions[2]['timevalidfrom'] = null;
        $positions[2]['timevalidto'] = null;
        $positions[2]['timecreated'] = '1000';
        $positions[2]['timemodified'] = '1000';
        $positions[2]['usermodified'] = '66';
        $positions[2]['fullname'] = 'item full name 2';
        $positions[2]['parentid'] = '0';
        $positions[2]['depthlevel'] = '1';
        $positions[2]['typeid'] = '33';
        $positions[2]['typeidnumber'] = $pos_type1->idnumber;
        $positions[2]['sortthread'] = '01';
        $positions[2]['totarasync'] = '1';
        $positions[3] = array();
        $positions[3]['id'] = '21';
        $positions[3]['shortname'] = 'isn33';
        $positions[3]['idnumber'] = 'iid33';
        $positions[3]['description'] = 'xyz';
        $positions[3]['frameworkid'] = $frameworks[2]['id'];
        $positions[3]['path'] = '/21';
        $positions[3]['visible'] = '1';
        $positions[3]['timevalidfrom'] = null;
        $positions[3]['timevalidto'] = null;
        $positions[3]['timecreated'] = '1002';
        $positions[3]['timemodified'] = '2002';
        $positions[3]['usermodified'] = '66';
        $positions[3]['fullname'] = 'item full name 3';
        $positions[3]['parentid'] = '0';
        $positions[3]['depthlevel'] = '1';
        $positions[3]['typeid'] = '66';
        $positions[3]['typeidnumber'] = 'jhgjghgjhjgh';
        $positions[3]['sortthread'] = '02';
        $positions[3]['totarasync'] = '1';
        $positions[4] = array();
        $positions[4]['id'] = '23';
        $positions[4]['shortname'] = 'isn34';
        $positions[4]['idnumber'] = 'iid34';
        $positions[4]['description'] = 'xyz r';
        $positions[4]['frameworkid'] = $frameworks[2]['id'];
        $positions[4]['path'] = '/21/23';
        $positions[4]['visible'] = '1';
        $positions[4]['timevalidfrom'] = null;
        $positions[4]['timevalidto'] = null;
        $positions[4]['timecreated'] = '1001';
        $positions[4]['timemodified'] = '1001';
        $positions[4]['usermodified'] = '666';
        $positions[4]['fullname'] = 'item full name 4';
        $positions[4]['parentid'] = '21';
        $positions[4]['depthlevel'] = '2';
        $positions[4]['typeid'] = '0';
        $positions[4]['typeidnumber'] = null;
        $positions[4]['sortthread'] = '02.01';
        $positions[4]['totarasync'] = '1';
        $positions[5] = array();
        $positions[5]['id'] = '667';
        $positions[5]['shortname'] = 'isn5';
        $positions[5]['idnumber'] = '';
        $positions[5]['description'] = 'xyz r h';
        $positions[5]['frameworkid'] = $frameworks[2]['id'];
        $positions[5]['path'] = '/666/667';
        $positions[5]['visible'] = '1';
        $positions[5]['timevalidfrom'] = null;
        $positions[5]['timevalidto'] = null;
        $positions[5]['timecreated'] = '1001';
        $positions[5]['timemodified'] = '1001';
        $positions[5]['usermodified'] = '666';
        $positions[5]['fullname'] = 'item full name 5';
        $positions[5]['parentid'] = '666';
        $positions[5]['depthlevel'] = '3';
        $positions[5]['typeid'] = '0';
        $positions[5]['typeidnumber'] = null;
        $positions[5]['sortthread'] = '02.01.01';
        $positions[5]['totarasync'] = '1';

        $this->setCurrentTimeStart();
        util::update_local_hierarchy($server, 'pos', $frameworks, $positions);
        $resultpositions = $DB->get_records('pos', array(), 'id ASC');
        $this->assertCount(5, $resultpositions);
        $this->assertFalse($DB->record_exists('pos', array('id' =>$pos1->id)));
        $resultpositions = array_values($resultpositions);

        $this->assertEquals($pos0, $resultpositions[0]);

        $this->assertSame($positions[1]['shortname'], $resultpositions[1]->shortname);
        $this->assertSame($positions[1]['idnumber'], $resultpositions[1]->idnumber);
        $this->assertSame($positions[1]['description'], $resultpositions[1]->description);
        $this->assertSame($resultframeworks[1]->id, $resultpositions[1]->frameworkid);
        $this->assertSame('/' . $resultpositions[1]->id, $resultpositions[1]->path);
        $this->assertSame($positions[1]['visible'], $resultpositions[1]->visible);
        $this->assertSame($positions[1]['timevalidfrom'], $resultpositions[1]->timevalidfrom);
        $this->assertSame($positions[1]['timevalidto'], $resultpositions[1]->timevalidto);
        $this->assertTimeCurrent($resultpositions[1]->timecreated);
        $this->assertTimeCurrent($resultpositions[1]->timemodified);
        $this->assertSame($user->id, $resultpositions[1]->usermodified);
        $this->assertSame($positions[1]['fullname'], $resultpositions[1]->fullname);
        $this->assertSame('0', $resultpositions[1]->parentid);
        $this->assertSame($positions[1]['depthlevel'], $resultpositions[1]->depthlevel);
        $this->assertSame($pos_type1->id, $resultpositions[1]->typeid);
        $this->assertSame($positions[1]['sortthread'], $resultpositions[1]->sortthread);
        $this->assertSame('0', $resultpositions[1]->totarasync);

        $this->assertSame($positions[2]['shortname'], $resultpositions[2]->shortname);
        $this->assertSame($positions[2]['idnumber'], $resultpositions[2]->idnumber);
        $this->assertSame($positions[2]['description'], $resultpositions[2]->description);
        $this->assertSame($resultframeworks[2]->id, $resultpositions[2]->frameworkid);
        $this->assertSame('/' . $resultpositions[2]->id, $resultpositions[2]->path);
        $this->assertSame($positions[2]['visible'], $resultpositions[2]->visible);
        $this->assertSame($positions[2]['timevalidfrom'], $resultpositions[2]->timevalidfrom);
        $this->assertSame($positions[2]['timevalidto'], $resultpositions[2]->timevalidto);
        $this->assertTimeCurrent($resultpositions[2]->timecreated);
        $this->assertTimeCurrent($resultpositions[2]->timemodified);
        $this->assertSame($user->id, $resultpositions[2]->usermodified);
        $this->assertSame($positions[2]['fullname'], $resultpositions[2]->fullname);
        $this->assertSame('0', $resultpositions[2]->parentid);
        $this->assertSame($positions[2]['depthlevel'], $resultpositions[2]->depthlevel);
        $this->assertSame($pos_type1->id, $resultpositions[2]->typeid);
        $this->assertSame($positions[2]['sortthread'], $resultpositions[2]->sortthread);
        $this->assertSame('0', $resultpositions[2]->totarasync);

        $this->assertSame($positions[3]['shortname'], $resultpositions[3]->shortname);
        $this->assertSame($positions[3]['idnumber'], $resultpositions[3]->idnumber);
        $this->assertSame($positions[3]['description'], $resultpositions[3]->description);
        $this->assertSame($resultframeworks[2]->id, $resultpositions[3]->frameworkid);
        $this->assertSame('/' . $resultpositions[3]->id, $resultpositions[3]->path);
        $this->assertSame($positions[3]['visible'], $resultpositions[3]->visible);
        $this->assertSame($positions[3]['timevalidfrom'], $resultpositions[3]->timevalidfrom);
        $this->assertSame($positions[3]['timevalidto'], $resultpositions[3]->timevalidto);
        $this->assertTimeCurrent($resultpositions[3]->timecreated);
        $this->assertTimeCurrent($resultpositions[3]->timemodified);
        $this->assertSame($user->id, $resultpositions[3]->usermodified);
        $this->assertSame($positions[3]['fullname'], $resultpositions[3]->fullname);
        $this->assertSame('0', $resultpositions[3]->parentid);
        $this->assertSame($positions[3]['depthlevel'], $resultpositions[3]->depthlevel);
        $this->assertSame('0', $resultpositions[3]->typeid);
        $this->assertSame($positions[3]['sortthread'], $resultpositions[3]->sortthread);
        $this->assertSame('0', $resultpositions[3]->totarasync);

        $this->assertSame($positions[4]['shortname'], $resultpositions[4]->shortname);
        $this->assertSame($positions[4]['idnumber'], $resultpositions[4]->idnumber);
        $this->assertSame($positions[4]['description'], $resultpositions[4]->description);
        $this->assertSame($resultframeworks[2]->id, $resultpositions[4]->frameworkid);
        $this->assertSame('/' . $resultpositions[3]->id . '/' . $resultpositions[4]->id, $resultpositions[4]->path);
        $this->assertSame($positions[4]['visible'], $resultpositions[4]->visible);
        $this->assertSame($positions[4]['timevalidfrom'], $resultpositions[4]->timevalidfrom);
        $this->assertSame($positions[4]['timevalidto'], $resultpositions[4]->timevalidto);
        $this->assertTimeCurrent($resultpositions[4]->timecreated);
        $this->assertTimeCurrent($resultpositions[4]->timemodified);
        $this->assertSame($user->id, $resultpositions[4]->usermodified);
        $this->assertSame($positions[4]['fullname'], $resultpositions[4]->fullname);
        $this->assertSame($resultpositions[3]->id, $resultpositions[4]->parentid);
        $this->assertSame($positions[4]['depthlevel'], $resultpositions[4]->depthlevel);
        $this->assertSame('0', $resultpositions[4]->typeid);
        $this->assertSame($positions[4]['sortthread'], $resultpositions[4]->sortthread);
        $this->assertSame('0', $resultpositions[4]->totarasync);

        // And finally some updates.
        $frameworks = array();
        $frameworks[1] = array();
        $frameworks[1]['id'] = '11';
        $frameworks[1]['shortname'] = 'fw3';
        $frameworks[1]['idnumber'] = 'fwid3';
        $frameworks[1]['description'] = 'a b c';
        $frameworks[1]['sortorder'] = '1';
        $frameworks[1]['timecreated'] = '400';
        $frameworks[1]['timeupdated'] = '500';
        $frameworks[1]['usermodified'] = '66';
        $frameworks[1]['visible'] = '1';
        $frameworks[1]['hidecustomfields'] = '0';
        $frameworks[1]['fullname'] = 'framework 3';
        $frameworks[2] = array();
        $frameworks[2]['id'] = '7';
        $frameworks[2]['shortname'] = 'fw2';
        $frameworks[2]['idnumber'] = 'fwid2x';
        $frameworks[2]['description'] = 'a b c d f';
        $frameworks[2]['sortorder'] = '2';
        $frameworks[2]['timecreated'] = '1000';
        $frameworks[2]['timeupdated'] = '2000';
        $frameworks[2]['usermodified'] = '666';
        $frameworks[2]['visible'] = '0';
        $frameworks[2]['hidecustomfields'] = '1';
        $frameworks[2]['fullname'] = 'framework 2';

        $positions = array();
        $positions[1] = array();
        $positions[1]['id'] = '19';
        $positions[1]['shortname'] = 'isn0';
        $positions[1]['idnumber'] = 'iid0';
        $positions[1]['description'] = 'abc';
        $positions[1]['frameworkid'] = $frameworks[1]['id'];
        $positions[1]['path'] = '/19';
        $positions[1]['visible'] = '1';
        $positions[1]['timevalidfrom'] = null;
        $positions[1]['timevalidto'] = null;
        $positions[1]['timecreated'] = '100';
        $positions[1]['timemodified'] = '200';
        $positions[1]['usermodified'] = '77';
        $positions[1]['fullname'] = 'item full name 0';
        $positions[1]['parentid'] = '0';
        $positions[1]['depthlevel'] = '1';
        $positions[1]['typeid'] = '0';
        $positions[1]['typeidnumber'] = null;
        $positions[1]['sortthread'] = '01';
        $positions[1]['totarasync'] = '0';
        $positions[2] = array();
        $positions[2]['id'] = '99';
        $positions[2]['shortname'] = 'isn99';
        $positions[2]['idnumber'] = 'iid99';
        $positions[2]['description'] = 'abce';
        $positions[2]['frameworkid'] = $frameworks[2]['id'];
        $positions[2]['path'] = '/20';
        $positions[2]['visible'] = '0';
        $positions[2]['timevalidfrom'] = null;
        $positions[2]['timevalidto'] = null;
        $positions[2]['timecreated'] = '11111';
        $positions[2]['timemodified'] = '2222';
        $positions[2]['usermodified'] = '444';
        $positions[2]['fullname'] = 'item full name 99';
        $positions[2]['parentid'] = '0';
        $positions[2]['depthlevel'] = '1';
        $positions[2]['typeid'] = '44';
        $positions[2]['typeidnumber'] = $pos_type2->idnumber;
        $positions[2]['sortthread'] = '01';
        $positions[2]['totarasync'] = '1';
        $positions[3] = array();
        $positions[3]['id'] = '21';
        $positions[3]['shortname'] = 'isn21';
        $positions[3]['idnumber'] = 'iid21';
        $positions[3]['description'] = 'xyz';
        $positions[3]['frameworkid'] = $frameworks[2]['id'];
        $positions[3]['path'] = '/99/21';
        $positions[3]['visible'] = '1';
        $positions[3]['timevalidfrom'] = null;
        $positions[3]['timevalidto'] = null;
        $positions[3]['timecreated'] = '1002';
        $positions[3]['timemodified'] = '3333';
        $positions[3]['usermodified'] = '66';
        $positions[3]['fullname'] = 'item full name 3';
        $positions[3]['parentid'] = '0';
        $positions[3]['depthlevel'] = '1';
        $positions[3]['typeid'] = '66';
        $positions[3]['typeidnumber'] = 'jhgjghgjhjgh';
        $positions[3]['sortthread'] = '02';
        $positions[3]['totarasync'] = '1';

        $this->setUser();
        $this->setCurrentTimeStart();
        $oldframeworks = $DB->get_records('pos_framework', array(), 'sortorder ASC');
        $oldframeworks = array_values($oldframeworks);
        $oldpositions = $DB->get_records('pos', array(), 'id ASC');
        $oldpositions = array_values($oldpositions);
        util::update_local_hierarchy($server, 'pos', $frameworks, $positions);
        $resultframeworks = $DB->get_records('pos_framework', array(), 'sortorder ASC');
        $this->assertCount(3, $resultframeworks);
        $resultframeworks = array_values($resultframeworks);
        $resultpositions = $DB->get_records('pos', array(), 'id ASC');
        $this->assertCount(4, $resultpositions);
        $resultpositions = array_values($resultpositions);

        $this->assertEquals($pos_framework0, $resultframeworks[0]);

        $this->assertSame($oldframeworks[2]->id, $resultframeworks[1]->id);
        $this->assertSame($frameworks[2]['shortname'], $resultframeworks[1]->shortname);
        $this->assertSame($frameworks[2]['idnumber'], $resultframeworks[1]->idnumber);
        $this->assertSame($frameworks[2]['description'], $resultframeworks[1]->description);
        $this->assertSame('2', $resultframeworks[1]->sortorder);
        $this->assertSame($oldframeworks[2]->timecreated, $resultframeworks[1]->timecreated);
        $this->assertTimeCurrent($resultframeworks[1]->timemodified);
        $this->assertSame($admin->id, $resultframeworks[1]->usermodified);
        $this->assertSame($frameworks[2]['visible'], $resultframeworks[1]->visible);
        $this->assertSame($frameworks[2]['hidecustomfields'], $resultframeworks[1]->hidecustomfields);
        $this->assertSame($frameworks[2]['fullname'], $resultframeworks[1]->fullname);

        $this->assertSame($frameworks[1]['shortname'], $resultframeworks[2]->shortname);
        $this->assertSame($frameworks[1]['idnumber'], $resultframeworks[2]->idnumber);
        $this->assertSame($frameworks[1]['description'], $resultframeworks[2]->description);
        $this->assertSame('3', $resultframeworks[2]->sortorder);
        $this->assertTimeCurrent($resultframeworks[2]->timecreated);
        $this->assertTimeCurrent($resultframeworks[2]->timemodified);
        $this->assertSame($admin->id, $resultframeworks[2]->usermodified);
        $this->assertSame($frameworks[1]['visible'], $resultframeworks[2]->visible);
        $this->assertSame($frameworks[1]['hidecustomfields'], $resultframeworks[2]->hidecustomfields);
        $this->assertSame($frameworks[1]['fullname'], $resultframeworks[2]->fullname);

        $this->assertEquals($pos0, $resultpositions[0]);

        $this->assertSame($positions[1]['shortname'], $resultpositions[1]->shortname);
        $this->assertSame($positions[1]['idnumber'], $resultpositions[1]->idnumber);
        $this->assertSame($positions[1]['description'], $resultpositions[1]->description);
        $this->assertSame($resultframeworks[2]->id, $resultpositions[1]->frameworkid);
        $this->assertSame('/' . $resultpositions[1]->id, $resultpositions[1]->path);
        $this->assertSame($positions[1]['visible'], $resultpositions[1]->visible);
        $this->assertSame($positions[1]['timevalidfrom'], $resultpositions[1]->timevalidfrom);
        $this->assertSame($positions[1]['timevalidto'], $resultpositions[1]->timevalidto);
        $this->assertTimeCurrent($resultpositions[1]->timemodified);
        $this->assertSame($admin->id, $resultpositions[1]->usermodified);
        $this->assertSame($positions[1]['fullname'], $resultpositions[1]->fullname);
        $this->assertSame('0', $resultpositions[1]->parentid);
        $this->assertSame($positions[1]['depthlevel'], $resultpositions[1]->depthlevel);
        $this->assertSame('0', $resultpositions[1]->typeid);
        $this->assertSame($positions[1]['sortthread'], $resultpositions[1]->sortthread);
        $this->assertSame('0', $resultpositions[1]->totarasync);

        $this->assertSame($positions[3]['shortname'], $resultpositions[2]->shortname);
        $this->assertSame($positions[3]['idnumber'], $resultpositions[2]->idnumber);
        $this->assertSame($positions[3]['description'], $resultpositions[2]->description);
        $this->assertSame($resultframeworks[1]->id, $resultpositions[2]->frameworkid);
        $this->assertSame('/' . $resultpositions[2]->id, $resultpositions[2]->path);
        $this->assertSame($positions[3]['visible'], $resultpositions[2]->visible);
        $this->assertSame($positions[3]['timevalidfrom'], $resultpositions[2]->timevalidfrom);
        $this->assertSame($positions[3]['timevalidto'], $resultpositions[2]->timevalidto);
        $this->assertTimeCurrent($resultpositions[2]->timemodified);
        $this->assertSame($admin->id, $resultpositions[2]->usermodified);
        $this->assertSame($positions[3]['fullname'], $resultpositions[2]->fullname);
        $this->assertSame('0', $resultpositions[2]->parentid);
        $this->assertSame($positions[3]['depthlevel'], $resultpositions[2]->depthlevel);
        $this->assertSame('0', $resultpositions[2]->typeid);
        $this->assertSame($positions[3]['sortthread'], $resultpositions[2]->sortthread);
        $this->assertSame('0', $resultpositions[2]->totarasync);

        $this->assertSame($positions[2]['shortname'], $resultpositions[3]->shortname);
        $this->assertSame($positions[2]['idnumber'], $resultpositions[3]->idnumber);
        $this->assertSame($positions[2]['description'], $resultpositions[3]->description);
        $this->assertSame($resultframeworks[1]->id, $resultpositions[3]->frameworkid);
        $this->assertSame('/' . $resultpositions[3]->id, $resultpositions[3]->path);
        $this->assertSame($positions[2]['visible'], $resultpositions[3]->visible);
        $this->assertSame($positions[2]['timevalidfrom'], $resultpositions[3]->timevalidfrom);
        $this->assertSame($positions[2]['timevalidto'], $resultpositions[3]->timevalidto);
        $this->assertTimeCurrent($resultpositions[3]->timemodified);
        $this->assertSame($admin->id, $resultpositions[3]->usermodified);
        $this->assertSame($positions[2]['fullname'], $resultpositions[3]->fullname);
        $this->assertSame('0', $resultpositions[3]->parentid);
        $this->assertSame($positions[2]['depthlevel'], $resultpositions[3]->depthlevel);
        $this->assertSame($pos_type2->id, $resultpositions[3]->typeid);
        $this->assertSame($positions[2]['sortthread'], $resultpositions[3]->sortthread);
        $this->assertSame('0', $resultpositions[3]->totarasync);

        // Make sure one server does not affect others.
        $server2 = $connectgenerator->create_server(array('apiversion' => 2));
        util::update_local_hierarchy($server2, 'pos', $frameworks, $positions);
        $resultframeworks = $DB->get_records('pos_framework', array(), 'sortorder ASC');
        $this->assertCount(5, $resultframeworks);
        $resultpositions = $DB->get_records('pos', array(), 'id ASC');
        $this->assertCount(7, $resultpositions);
    }

    public function test_update_local_hierarchy_pos_fields() {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        /** @var totara_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        /** @var auth_connect_generator $connectgenerator */
        $connectgenerator = $this->getDataGenerator()->get_plugin_generator('auth_connect');

        $pos_type1 = $DB->get_record('pos_type', array('id' => $hierarchygenerator->create_pos_type(array('idnumber' => 'tidnum1'))));
        $hierarchygenerator->create_hierarchy_type_checkbox(array('hierarchy' => 'position', 'typeidnumber' => 'tidnum1', 'value' => ''));
        $hierarchygenerator->create_hierarchy_type_text(array('hierarchy' => 'position', 'typeidnumber' => 'tidnum1', 'value' => ''));
        $fields1 = $DB->get_records('pos_type_info_field', array('typeid' => $pos_type1->id), 'shortname ASC');
        $fields1 = array_values($fields1);
        $this->assertSame('checkbox', $fields1[0]->datatype);
        $this->assertSame('text', $fields1[1]->datatype);
        $pos_type2 = $DB->get_record('pos_type', array('id' => $hierarchygenerator->create_pos_type(array('idnumber' => 'tidnum2'))));

        $server = $connectgenerator->create_server(array('apiversion' => 2));
        set_config('syncpositions', 1, 'auth_connect');

        $frameworks = array();
        $frameworks[0] = array();
        $frameworks[0]['id'] = '11';
        $frameworks[0]['shortname'] = 'fw3';
        $frameworks[0]['idnumber'] = 'fwid3';
        $frameworks[0]['description'] = 'a b c';
        $frameworks[0]['sortorder'] = '1';
        $frameworks[0]['timecreated'] = '400';
        $frameworks[0]['timeupdated'] = '500';
        $frameworks[0]['usermodified'] = '66';
        $frameworks[0]['visible'] = '1';
        $frameworks[0]['hidecustomfields'] = '0';
        $frameworks[0]['fullname'] = 'framework 3';

        $positions = array();
        $positions[0] = array();
        $positions[0]['id'] = '19';
        $positions[0]['shortname'] = 'isn0';
        $positions[0]['idnumber'] = 'iid0';
        $positions[0]['description'] = 'abc';
        $positions[0]['frameworkid'] = $frameworks[0]['id'];
        $positions[0]['path'] = '/19';
        $positions[0]['visible'] = '1';
        $positions[0]['timevalidfrom'] = null;
        $positions[0]['timevalidto'] = null;
        $positions[0]['timecreated'] = '100';
        $positions[0]['timemodified'] = '200';
        $positions[0]['usermodified'] = '77';
        $positions[0]['fullname'] = 'item full name 0';
        $positions[0]['parentid'] = '0';
        $positions[0]['depthlevel'] = '1';
        $positions[0]['typeid'] = '0';
        $positions[0]['typeidnumber'] = null;
        $positions[0]['sortthread'] = '01';
        $positions[0]['totarasync'] = '0';
        $positions[0]['custom_fields'] = array();
        $positions[1] = array();
        $positions[1]['id'] = '99';
        $positions[1]['shortname'] = 'isn99';
        $positions[1]['idnumber'] = 'iid99';
        $positions[1]['description'] = 'abce';
        $positions[1]['frameworkid'] = $frameworks[0]['id'];
        $positions[1]['path'] = '/20';
        $positions[1]['visible'] = '0';
        $positions[1]['timevalidfrom'] = null;
        $positions[1]['timevalidto'] = null;
        $positions[1]['timecreated'] = '11111';
        $positions[1]['timemodified'] = '2222';
        $positions[1]['usermodified'] = '444';
        $positions[1]['fullname'] = 'item full name 99';
        $positions[1]['parentid'] = '0';
        $positions[1]['depthlevel'] = '1';
        $positions[1]['typeid'] = '33';
        $positions[1]['typeidnumber'] = $pos_type1->idnumber;
        $positions[1]['sortthread'] = '01';
        $positions[1]['totarasync'] = '1';
        $positions[1]['custom_fields'] = array(
            array(
                'shortname' => $fields1[0]->shortname,
                'datatype' => $fields1[0]->datatype,
                'data' => '1',
                'value' => null,
            ),
            array(
                'shortname' => $fields1[1]->shortname,
                'datatype' => $fields1[1]->datatype,
                'data' => 'some small text',
                'value' => '1',
            ),
        );

        // Test adding matching data.
        util::update_local_hierarchy($server, 'pos', $frameworks, $positions);
        $newfielddatas = $DB->get_records('pos_type_info_data', array(), 'id ASC');
        $this->assertCount(2, $newfielddatas);
        $newfielddatas = array_values($newfielddatas);
        $this->assertSame($fields1[0]->id, $newfielddatas[0]->fieldid);
        $this->assertSame($positions[1]['custom_fields'][0]['data'], $newfielddatas[0]->data);
        $this->assertSame($fields1[1]->id, $newfielddatas[1]->fieldid);
        $this->assertSame($positions[1]['custom_fields'][1]['data'], $newfielddatas[1]->data);
        $newfieldparams = $DB->get_records('pos_type_info_data_param', array(), 'id ASC');
        $this->assertCount(1, $newfieldparams);
        $newfieldparams = array_values($newfieldparams);
        $this->assertSame($newfielddatas[1]->id, $newfieldparams[0]->dataid);
        $this->assertSame($positions[1]['custom_fields'][1]['value'], $newfieldparams[0]->value);

        // Test adding mismatched data.
        $DB->delete_records('pos');
        $DB->delete_records('pos_type_info_data');
        $DB->delete_records('pos_type_info_data_param');
        $positions[1]['custom_fields'] = array(
            array(
                'shortname' => $fields1[0]->shortname. 'xxx',
                'datatype' => $fields1[0]->datatype,
                'data' => '1',
                'value' => null,
            ),
            array(
                'shortname' => $fields1[1]->shortname,
                'datatype' => $fields1[1]->datatype . 'xxx',
                'data' => 'some small text',
                'value' => '1',
            ),
        );
        util::update_local_hierarchy($server, 'pos', $frameworks, $positions);
        $newfielddatas = $DB->get_records('pos_type_info_data', array(), 'id ASC');
        $this->assertCount(0, $newfielddatas);
        $newfieldparams = $DB->get_records('pos_type_info_data_param', array(), 'id ASC');
        $this->assertCount(0, $newfieldparams);

        // Test updating data.
        $DB->delete_records('pos');
        $DB->delete_records('pos_type_info_data');
        $DB->delete_records('pos_type_info_data_param');
        $positions[1]['custom_fields'] = array(
            array(
                'shortname' => $fields1[0]->shortname,
                'datatype' => $fields1[0]->datatype,
                'data' => '1',
                'value' => null,
            ),
            array(
                'shortname' => $fields1[1]->shortname,
                'datatype' => $fields1[1]->datatype,
                'data' => 'some small text',
                'value' => '1',
            ),
        );
        util::update_local_hierarchy($server, 'pos', $frameworks, $positions);
        $newfielddatas = $DB->get_records('pos_type_info_data', array(), 'id ASC');
        $this->assertCount(2, $newfielddatas);
        $newfieldparams = $DB->get_records('pos_type_info_data_param', array(), 'id ASC');
        $this->assertCount(1, $newfieldparams);

        $positions[1]['custom_fields'] = array(
            array(
                'shortname' => $fields1[0]->shortname,
                'datatype' => $fields1[0]->datatype,
                'data' => '0',
                'value' => '1',
            ),
            array(
                'shortname' => $fields1[1]->shortname,
                'datatype' => $fields1[1]->datatype,
                'data' => 'some small text 2',
                'value' => null,
            ),
        );
        util::update_local_hierarchy($server, 'pos', $frameworks, $positions);
        $newfielddatas = $DB->get_records('pos_type_info_data', array(), 'id ASC');
        $this->assertCount(2, $newfielddatas);
        $newfielddatas = array_values($newfielddatas);
        $this->assertSame($fields1[0]->id, $newfielddatas[0]->fieldid);
        $this->assertSame($positions[1]['custom_fields'][0]['data'], $newfielddatas[0]->data);
        $this->assertSame($fields1[1]->id, $newfielddatas[1]->fieldid);
        $this->assertSame($positions[1]['custom_fields'][1]['data'], $newfielddatas[1]->data);
        $newfieldparams = $DB->get_records('pos_type_info_data_param', array(), 'id ASC');
        $this->assertCount(1, $newfieldparams);
        $newfieldparams = array_values($newfieldparams);
        $this->assertSame($newfielddatas[0]->id, $newfieldparams[0]->dataid);
        $this->assertSame($positions[1]['custom_fields'][0]['value'], $newfieldparams[0]->value);

        // Test updating mismatched data.
        $positions[1]['custom_fields'] = array(
            array(
                'shortname' => $fields1[0]->shortname. 'xxx',
                'datatype' => $fields1[0]->datatype,
                'data' => '1',
                'value' => null,
            ),
            array(
                'shortname' => $fields1[1]->shortname,
                'datatype' => $fields1[1]->datatype . 'xxx',
                'data' => 'some small text',
                'value' => '1',
            ),
        );

        util::update_local_hierarchy($server, 'pos', $frameworks, $positions);
        $newfielddatas = $DB->get_records('pos_type_info_data', array(), 'id ASC');
        $this->assertCount(0, $newfielddatas);
        $newfieldparams = $DB->get_records('pos_type_info_data_param', array(), 'id ASC');
        $this->assertCount(0, $newfieldparams);
    }

    public function test_update_local_hierarchy_org() {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $admin = get_admin();

        /** @var totara_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        /** @var auth_connect_generator $connectgenerator */
        $connectgenerator = $this->getDataGenerator()->get_plugin_generator('auth_connect');

        $server = $connectgenerator->create_server(array('apiversion' => 2));

        // Add some preexisting frameworks.
        $org_framework0 = $hierarchygenerator->create_org_frame(array('idnumber' => 'previd1', 'sortorder' => 1));

        // Nothing should change with no sync data.
        util::update_local_hierarchy($server, 'org', array(), array());
        $result = $DB->get_records('org_framework', array(), 'sortorder ASC');
        $this->assertCount(1, $result);
        $result = array_values($result);
        $this->assertEquals($org_framework0, $result[0]);

        // Add and convert frameworks.
        $frameworks = array();
        $frameworks[1] = array();
        $frameworks[1]['id'] = '10';
        $frameworks[1]['shortname'] = 'fw1';
        $frameworks[1]['idnumber'] = 'fwid1';
        $frameworks[1]['description'] = 'a b c';
        $frameworks[1]['sortorder'] = '1';
        $frameworks[1]['timecreated'] = '100';
        $frameworks[1]['timeupdated'] = '200';
        $frameworks[1]['usermodified'] = '66';
        $frameworks[1]['visible'] = '1';
        $frameworks[1]['hidecustomfields'] = '0';
        $frameworks[1]['fullname'] = 'framework 1';
        $frameworks[2] = array();
        $frameworks[2]['id'] = '7';
        $frameworks[2]['shortname'] = 'fw2';
        $frameworks[2]['idnumber'] = 'fwid2';
        $frameworks[2]['description'] = 'a b c d';
        $frameworks[2]['sortorder'] = '2';
        $frameworks[2]['timecreated'] = '1000';
        $frameworks[2]['timeupdated'] = '2000';
        $frameworks[2]['usermodified'] = '666';
        $frameworks[2]['visible'] = '0';
        $frameworks[2]['hidecustomfields'] = '1';
        $frameworks[2]['fullname'] = 'framework 2';

        $this->setCurrentTimeStart();
        util::update_local_hierarchy($server, 'org', $frameworks, array());
        $result = $DB->get_records('org_framework', array(), 'sortorder ASC');
        $this->assertCount(3, $result);
        $result = array_values($result);

        $this->assertEquals($org_framework0, $result[0]);

        $this->assertSame($frameworks[1]['shortname'], $result[1]->shortname);
        $this->assertSame($frameworks[1]['idnumber'], $result[1]->idnumber);
        $this->assertSame($frameworks[1]['description'], $result[1]->description);
        $this->assertSame('2', $result[1]->sortorder);
        $this->assertTimeCurrent($result[1]->timecreated);
        $this->assertTimeCurrent($result[1]->timemodified);
        $this->assertSame($user->id, $result[1]->usermodified);
        $this->assertSame($frameworks[1]['visible'], $result[1]->visible);
        $this->assertSame($frameworks[1]['hidecustomfields'], $result[1]->hidecustomfields);
        $this->assertSame($frameworks[1]['fullname'], $result[1]->fullname);

        $this->assertSame($frameworks[2]['shortname'], $result[2]->shortname);
        $this->assertSame($frameworks[2]['idnumber'], $result[2]->idnumber);
        $this->assertSame($frameworks[2]['description'], $result[2]->description);
        $this->assertSame('3', $result[2]->sortorder);
        $this->assertTimeCurrent($result[2]->timecreated);
        $this->assertTimeCurrent($result[2]->timemodified);
        $this->assertSame($user->id, $result[2]->usermodified);
        $this->assertSame($frameworks[2]['visible'], $result[2]->visible);
        $this->assertSame($frameworks[2]['hidecustomfields'], $result[2]->hidecustomfields);
        $this->assertSame($frameworks[2]['fullname'], $result[2]->fullname);

        // Run again should change nothing.
        util::update_local_hierarchy($server, 'org', $frameworks, array());
        $resultframeworks = $DB->get_records('org_framework', array(), 'sortorder ASC');
        $this->assertCount(3, $resultframeworks);
        $resultframeworks = array_values($resultframeworks);
        $this->assertEquals($result, $resultframeworks);

        // Test adding updating and removing items.
        $tid = $hierarchygenerator->create_org_type(array('idnumber' => 'idnum1'));
        $org_type1 = $DB->get_record('org_type', array('id' => $tid));
        $tid =  $hierarchygenerator->create_org_type(array('idnumber' => 'idnum2'));
        $org_type2 = $DB->get_record('org_type', array('id' => $tid));
        $tid = $hierarchygenerator->create_org_type(array('idnumber' => ''));
        $org_type3 = $DB->get_record('org_type', array('id' => $tid));
        $org0 = $hierarchygenerator->create_org(array('frameworkid' => $org_framework0->id, 'typeid' => $org_type2->id, 'idnumber' => 'iidxxx')); // To be ignored.
        $org1 = $hierarchygenerator->create_org(array('frameworkid' => $result[1]->id, 'idnumber' => 'itemid3')); // To be deleted.

        $organisations = array();
        $organisations[1] = array();
        $organisations[1]['id'] = '19';
        $organisations[1]['shortname'] = 'isn0';
        $organisations[1]['idnumber'] = 'iid0';
        $organisations[1]['description'] = 'abc';
        $organisations[1]['frameworkid'] = $frameworks[1]['id'];
        $organisations[1]['path'] = '/19';
        $organisations[1]['visible'] = '1';
        $organisations[1]['timecreated'] = '100';
        $organisations[1]['timemodified'] = '100';
        $organisations[1]['usermodified'] = '66';
        $organisations[1]['fullname'] = 'item full name 0';
        $organisations[1]['parentid'] = '0';
        $organisations[1]['depthlevel'] = '1';
        $organisations[1]['typeid'] = '33';
        $organisations[1]['typeidnumber'] = $org_type1->idnumber;
        $organisations[1]['sortthread'] = '01';
        $organisations[1]['totarasync'] = '1';
        $organisations[2] = array();
        $organisations[2]['id'] = '20';
        $organisations[2]['shortname'] = 'isn2';
        $organisations[2]['idnumber'] = 'iid2';
        $organisations[2]['description'] = 'abce';
        $organisations[2]['frameworkid'] = $frameworks[2]['id'];
        $organisations[2]['path'] = '/20';
        $organisations[2]['visible'] = '0';
        $organisations[2]['timecreated'] = '1000';
        $organisations[2]['timemodified'] = '1000';
        $organisations[2]['usermodified'] = '66';
        $organisations[2]['fullname'] = 'item full name 2';
        $organisations[2]['parentid'] = '0';
        $organisations[2]['depthlevel'] = '1';
        $organisations[2]['typeid'] = '33';
        $organisations[2]['typeidnumber'] = $org_type1->idnumber;
        $organisations[2]['sortthread'] = '01';
        $organisations[2]['totarasync'] = '1';
        $organisations[3] = array();
        $organisations[3]['id'] = '21';
        $organisations[3]['shortname'] = 'isn33';
        $organisations[3]['idnumber'] = 'iid33';
        $organisations[3]['description'] = 'xyz';
        $organisations[3]['frameworkid'] = $frameworks[2]['id'];
        $organisations[3]['path'] = '/21';
        $organisations[3]['visible'] = '1';
        $organisations[3]['timecreated'] = '1002';
        $organisations[3]['timemodified'] = '2002';
        $organisations[3]['usermodified'] = '66';
        $organisations[3]['fullname'] = 'item full name 3';
        $organisations[3]['parentid'] = '0';
        $organisations[3]['depthlevel'] = '1';
        $organisations[3]['typeid'] = '66';
        $organisations[3]['typeidnumber'] = 'jhgjghgjhjgh';
        $organisations[3]['sortthread'] = '02';
        $organisations[3]['totarasync'] = '1';
        $organisations[4] = array();
        $organisations[4]['id'] = '23';
        $organisations[4]['shortname'] = 'isn34';
        $organisations[4]['idnumber'] = 'iid34';
        $organisations[4]['description'] = 'xyz r';
        $organisations[4]['frameworkid'] = $frameworks[2]['id'];
        $organisations[4]['path'] = '/21/23';
        $organisations[4]['visible'] = '1';
        $organisations[4]['timecreated'] = '1001';
        $organisations[4]['timemodified'] = '1001';
        $organisations[4]['usermodified'] = '666';
        $organisations[4]['fullname'] = 'item full name 4';
        $organisations[4]['parentid'] = '21';
        $organisations[4]['depthlevel'] = '2';
        $organisations[4]['typeid'] = '0';
        $organisations[4]['typeidnumber'] = null;
        $organisations[4]['sortthread'] = '02.01';
        $organisations[4]['totarasync'] = '1';
        $organisations[5] = array();
        $organisations[5]['id'] = '667';
        $organisations[5]['shortname'] = 'isn5';
        $organisations[5]['idnumber'] = '';
        $organisations[5]['description'] = 'xyz r h';
        $organisations[5]['frameworkid'] = $frameworks[2]['id'];
        $organisations[5]['path'] = '/666/667';
        $organisations[5]['visible'] = '1';
        $organisations[5]['timecreated'] = '1001';
        $organisations[5]['timemodified'] = '1001';
        $organisations[5]['usermodified'] = '666';
        $organisations[5]['fullname'] = 'item full name 5';
        $organisations[5]['parentid'] = '666';
        $organisations[5]['depthlevel'] = '3';
        $organisations[5]['typeid'] = '0';
        $organisations[5]['typeidnumber'] = null;
        $organisations[5]['sortthread'] = '02.01.01';
        $organisations[5]['totarasync'] = '1';

        $this->setCurrentTimeStart();
        util::update_local_hierarchy($server, 'org', $frameworks, $organisations);
        $resultorganisations = $DB->get_records('org', array(), 'id ASC');
        $this->assertCount(5, $resultorganisations);
        $this->assertFalse($DB->record_exists('org', array('id' =>$org1->id)));
        $resultorganisations = array_values($resultorganisations);

        $this->assertEquals($org0, $resultorganisations[0]);

        $this->assertSame($organisations[1]['shortname'], $resultorganisations[1]->shortname);
        $this->assertSame($organisations[1]['idnumber'], $resultorganisations[1]->idnumber);
        $this->assertSame($organisations[1]['description'], $resultorganisations[1]->description);
        $this->assertSame($resultframeworks[1]->id, $resultorganisations[1]->frameworkid);
        $this->assertSame('/' . $resultorganisations[1]->id, $resultorganisations[1]->path);
        $this->assertSame($organisations[1]['visible'], $resultorganisations[1]->visible);
        $this->assertTimeCurrent($resultorganisations[1]->timecreated);
        $this->assertTimeCurrent($resultorganisations[1]->timemodified);
        $this->assertSame($user->id, $resultorganisations[1]->usermodified);
        $this->assertSame($organisations[1]['fullname'], $resultorganisations[1]->fullname);
        $this->assertSame('0', $resultorganisations[1]->parentid);
        $this->assertSame($organisations[1]['depthlevel'], $resultorganisations[1]->depthlevel);
        $this->assertSame($org_type1->id, $resultorganisations[1]->typeid);
        $this->assertSame($organisations[1]['sortthread'], $resultorganisations[1]->sortthread);
        $this->assertSame('0', $resultorganisations[1]->totarasync);

        $this->assertSame($organisations[2]['shortname'], $resultorganisations[2]->shortname);
        $this->assertSame($organisations[2]['idnumber'], $resultorganisations[2]->idnumber);
        $this->assertSame($organisations[2]['description'], $resultorganisations[2]->description);
        $this->assertSame($resultframeworks[2]->id, $resultorganisations[2]->frameworkid);
        $this->assertSame('/' . $resultorganisations[2]->id, $resultorganisations[2]->path);
        $this->assertSame($organisations[2]['visible'], $resultorganisations[2]->visible);
        $this->assertTimeCurrent($resultorganisations[2]->timecreated);
        $this->assertTimeCurrent($resultorganisations[2]->timemodified);
        $this->assertSame($user->id, $resultorganisations[2]->usermodified);
        $this->assertSame($organisations[2]['fullname'], $resultorganisations[2]->fullname);
        $this->assertSame('0', $resultorganisations[2]->parentid);
        $this->assertSame($organisations[2]['depthlevel'], $resultorganisations[2]->depthlevel);
        $this->assertSame($org_type1->id, $resultorganisations[2]->typeid);
        $this->assertSame($organisations[2]['sortthread'], $resultorganisations[2]->sortthread);
        $this->assertSame('0', $resultorganisations[2]->totarasync);

        $this->assertSame($organisations[3]['shortname'], $resultorganisations[3]->shortname);
        $this->assertSame($organisations[3]['idnumber'], $resultorganisations[3]->idnumber);
        $this->assertSame($organisations[3]['description'], $resultorganisations[3]->description);
        $this->assertSame($resultframeworks[2]->id, $resultorganisations[3]->frameworkid);
        $this->assertSame('/' . $resultorganisations[3]->id, $resultorganisations[3]->path);
        $this->assertSame($organisations[3]['visible'], $resultorganisations[3]->visible);
        $this->assertTimeCurrent($resultorganisations[3]->timecreated);
        $this->assertTimeCurrent($resultorganisations[3]->timemodified);
        $this->assertSame($user->id, $resultorganisations[3]->usermodified);
        $this->assertSame($organisations[3]['fullname'], $resultorganisations[3]->fullname);
        $this->assertSame('0', $resultorganisations[3]->parentid);
        $this->assertSame($organisations[3]['depthlevel'], $resultorganisations[3]->depthlevel);
        $this->assertSame('0', $resultorganisations[3]->typeid);
        $this->assertSame($organisations[3]['sortthread'], $resultorganisations[3]->sortthread);
        $this->assertSame('0', $resultorganisations[3]->totarasync);

        $this->assertSame($organisations[4]['shortname'], $resultorganisations[4]->shortname);
        $this->assertSame($organisations[4]['idnumber'], $resultorganisations[4]->idnumber);
        $this->assertSame($organisations[4]['description'], $resultorganisations[4]->description);
        $this->assertSame($resultframeworks[2]->id, $resultorganisations[4]->frameworkid);
        $this->assertSame('/' . $resultorganisations[3]->id . '/' . $resultorganisations[4]->id, $resultorganisations[4]->path);
        $this->assertSame($organisations[4]['visible'], $resultorganisations[4]->visible);
        $this->assertTimeCurrent($resultorganisations[4]->timecreated);
        $this->assertTimeCurrent($resultorganisations[4]->timemodified);
        $this->assertSame($user->id, $resultorganisations[4]->usermodified);
        $this->assertSame($organisations[4]['fullname'], $resultorganisations[4]->fullname);
        $this->assertSame($resultorganisations[3]->id, $resultorganisations[4]->parentid);
        $this->assertSame($organisations[4]['depthlevel'], $resultorganisations[4]->depthlevel);
        $this->assertSame('0', $resultorganisations[4]->typeid);
        $this->assertSame($organisations[4]['sortthread'], $resultorganisations[4]->sortthread);
        $this->assertSame('0', $resultorganisations[4]->totarasync);

        // And finally some updates.
        $frameworks = array();
        $frameworks[1] = array();
        $frameworks[1]['id'] = '11';
        $frameworks[1]['shortname'] = 'fw3';
        $frameworks[1]['idnumber'] = 'fwid3';
        $frameworks[1]['description'] = 'a b c';
        $frameworks[1]['sortorder'] = '1';
        $frameworks[1]['timecreated'] = '400';
        $frameworks[1]['timeupdated'] = '500';
        $frameworks[1]['usermodified'] = '66';
        $frameworks[1]['visible'] = '1';
        $frameworks[1]['hidecustomfields'] = '0';
        $frameworks[1]['fullname'] = 'framework 3';
        $frameworks[2] = array();
        $frameworks[2]['id'] = '7';
        $frameworks[2]['shortname'] = 'fw2';
        $frameworks[2]['idnumber'] = 'fwid2x';
        $frameworks[2]['description'] = 'a b c d f';
        $frameworks[2]['sortorder'] = '2';
        $frameworks[2]['timecreated'] = '1000';
        $frameworks[2]['timeupdated'] = '2000';
        $frameworks[2]['usermodified'] = '666';
        $frameworks[2]['visible'] = '0';
        $frameworks[2]['hidecustomfields'] = '1';
        $frameworks[2]['fullname'] = 'framework 2';

        $organisations = array();
        $organisations[1] = array();
        $organisations[1]['id'] = '19';
        $organisations[1]['shortname'] = 'isn0';
        $organisations[1]['idnumber'] = 'iid0';
        $organisations[1]['description'] = 'abc';
        $organisations[1]['frameworkid'] = $frameworks[1]['id'];
        $organisations[1]['path'] = '/19';
        $organisations[1]['visible'] = '1';
        $organisations[1]['timecreated'] = '100';
        $organisations[1]['timemodified'] = '200';
        $organisations[1]['usermodified'] = '77';
        $organisations[1]['fullname'] = 'item full name 0';
        $organisations[1]['parentid'] = '0';
        $organisations[1]['depthlevel'] = '1';
        $organisations[1]['typeid'] = '0';
        $organisations[1]['typeidnumber'] = null;
        $organisations[1]['sortthread'] = '01';
        $organisations[1]['totarasync'] = '0';
        $organisations[2] = array();
        $organisations[2]['id'] = '99';
        $organisations[2]['shortname'] = 'isn99';
        $organisations[2]['idnumber'] = 'iid99';
        $organisations[2]['description'] = 'abce';
        $organisations[2]['frameworkid'] = $frameworks[2]['id'];
        $organisations[2]['path'] = '/20';
        $organisations[2]['visible'] = '0';
        $organisations[2]['timecreated'] = '11111';
        $organisations[2]['timemodified'] = '2222';
        $organisations[2]['usermodified'] = '444';
        $organisations[2]['fullname'] = 'item full name 99';
        $organisations[2]['parentid'] = '0';
        $organisations[2]['depthlevel'] = '1';
        $organisations[2]['typeid'] = '44';
        $organisations[2]['typeidnumber'] = $org_type2->idnumber;
        $organisations[2]['sortthread'] = '01';
        $organisations[2]['totarasync'] = '1';
        $organisations[3] = array();
        $organisations[3]['id'] = '21';
        $organisations[3]['shortname'] = 'isn21';
        $organisations[3]['idnumber'] = 'iid21';
        $organisations[3]['description'] = 'xyz';
        $organisations[3]['frameworkid'] = $frameworks[2]['id'];
        $organisations[3]['path'] = '/99/21';
        $organisations[3]['visible'] = '1';
        $organisations[3]['timecreated'] = '1002';
        $organisations[3]['timemodified'] = '3333';
        $organisations[3]['usermodified'] = '66';
        $organisations[3]['fullname'] = 'item full name 3';
        $organisations[3]['parentid'] = '0';
        $organisations[3]['depthlevel'] = '1';
        $organisations[3]['typeid'] = '66';
        $organisations[3]['typeidnumber'] = 'jhgjghgjhjgh';
        $organisations[3]['sortthread'] = '02';
        $organisations[3]['totarasync'] = '1';

        $this->setUser();
        $this->setCurrentTimeStart();
        $oldframeworks = $DB->get_records('org_framework', array(), 'sortorder ASC');
        $oldframeworks = array_values($oldframeworks);
        $oldorganisations = $DB->get_records('org', array(), 'id ASC');
        $oldorganisations = array_values($oldorganisations);
        util::update_local_hierarchy($server, 'org', $frameworks, $organisations);
        $resultframeworks = $DB->get_records('org_framework', array(), 'sortorder ASC');
        $this->assertCount(3, $resultframeworks);
        $resultframeworks = array_values($resultframeworks);
        $resultorganisations = $DB->get_records('org', array(), 'id ASC');
        $this->assertCount(4, $resultorganisations);
        $resultorganisations = array_values($resultorganisations);

        $this->assertEquals($org_framework0, $resultframeworks[0]);

        $this->assertSame($oldframeworks[2]->id, $resultframeworks[1]->id);
        $this->assertSame($frameworks[2]['shortname'], $resultframeworks[1]->shortname);
        $this->assertSame($frameworks[2]['idnumber'], $resultframeworks[1]->idnumber);
        $this->assertSame($frameworks[2]['description'], $resultframeworks[1]->description);
        $this->assertSame('2', $resultframeworks[1]->sortorder);
        $this->assertSame($oldframeworks[2]->timecreated, $resultframeworks[1]->timecreated);
        $this->assertTimeCurrent($resultframeworks[1]->timemodified);
        $this->assertSame($admin->id, $resultframeworks[1]->usermodified);
        $this->assertSame($frameworks[2]['visible'], $resultframeworks[1]->visible);
        $this->assertSame($frameworks[2]['hidecustomfields'], $resultframeworks[1]->hidecustomfields);
        $this->assertSame($frameworks[2]['fullname'], $resultframeworks[1]->fullname);

        $this->assertSame($frameworks[1]['shortname'], $resultframeworks[2]->shortname);
        $this->assertSame($frameworks[1]['idnumber'], $resultframeworks[2]->idnumber);
        $this->assertSame($frameworks[1]['description'], $resultframeworks[2]->description);
        $this->assertSame('3', $resultframeworks[2]->sortorder);
        $this->assertTimeCurrent($resultframeworks[2]->timecreated);
        $this->assertTimeCurrent($resultframeworks[2]->timemodified);
        $this->assertSame($admin->id, $resultframeworks[2]->usermodified);
        $this->assertSame($frameworks[1]['visible'], $resultframeworks[2]->visible);
        $this->assertSame($frameworks[1]['hidecustomfields'], $resultframeworks[2]->hidecustomfields);
        $this->assertSame($frameworks[1]['fullname'], $resultframeworks[2]->fullname);

        $this->assertEquals($org0, $resultorganisations[0]);

        $this->assertSame($organisations[1]['shortname'], $resultorganisations[1]->shortname);
        $this->assertSame($organisations[1]['idnumber'], $resultorganisations[1]->idnumber);
        $this->assertSame($organisations[1]['description'], $resultorganisations[1]->description);
        $this->assertSame($resultframeworks[2]->id, $resultorganisations[1]->frameworkid);
        $this->assertSame('/' . $resultorganisations[1]->id, $resultorganisations[1]->path);
        $this->assertSame($organisations[1]['visible'], $resultorganisations[1]->visible);
        $this->assertTimeCurrent($resultorganisations[1]->timemodified);
        $this->assertSame($admin->id, $resultorganisations[1]->usermodified);
        $this->assertSame($organisations[1]['fullname'], $resultorganisations[1]->fullname);
        $this->assertSame('0', $resultorganisations[1]->parentid);
        $this->assertSame($organisations[1]['depthlevel'], $resultorganisations[1]->depthlevel);
        $this->assertSame('0', $resultorganisations[1]->typeid);
        $this->assertSame($organisations[1]['sortthread'], $resultorganisations[1]->sortthread);
        $this->assertSame('0', $resultorganisations[1]->totarasync);

        $this->assertSame($organisations[3]['shortname'], $resultorganisations[2]->shortname);
        $this->assertSame($organisations[3]['idnumber'], $resultorganisations[2]->idnumber);
        $this->assertSame($organisations[3]['description'], $resultorganisations[2]->description);
        $this->assertSame($resultframeworks[1]->id, $resultorganisations[2]->frameworkid);
        $this->assertSame('/' . $resultorganisations[2]->id, $resultorganisations[2]->path);
        $this->assertSame($organisations[3]['visible'], $resultorganisations[2]->visible);
        $this->assertTimeCurrent($resultorganisations[2]->timemodified);
        $this->assertSame($admin->id, $resultorganisations[2]->usermodified);
        $this->assertSame($organisations[3]['fullname'], $resultorganisations[2]->fullname);
        $this->assertSame('0', $resultorganisations[2]->parentid);
        $this->assertSame($organisations[3]['depthlevel'], $resultorganisations[2]->depthlevel);
        $this->assertSame('0', $resultorganisations[2]->typeid);
        $this->assertSame($organisations[3]['sortthread'], $resultorganisations[2]->sortthread);
        $this->assertSame('0', $resultorganisations[2]->totarasync);

        $this->assertSame($organisations[2]['shortname'], $resultorganisations[3]->shortname);
        $this->assertSame($organisations[2]['idnumber'], $resultorganisations[3]->idnumber);
        $this->assertSame($organisations[2]['description'], $resultorganisations[3]->description);
        $this->assertSame($resultframeworks[1]->id, $resultorganisations[3]->frameworkid);
        $this->assertSame('/' . $resultorganisations[3]->id, $resultorganisations[3]->path);
        $this->assertSame($organisations[2]['visible'], $resultorganisations[3]->visible);
        $this->assertTimeCurrent($resultorganisations[3]->timemodified);
        $this->assertSame($admin->id, $resultorganisations[3]->usermodified);
        $this->assertSame($organisations[2]['fullname'], $resultorganisations[3]->fullname);
        $this->assertSame('0', $resultorganisations[3]->parentid);
        $this->assertSame($organisations[2]['depthlevel'], $resultorganisations[3]->depthlevel);
        $this->assertSame($org_type2->id, $resultorganisations[3]->typeid);
        $this->assertSame($organisations[2]['sortthread'], $resultorganisations[3]->sortthread);
        $this->assertSame('0', $resultorganisations[3]->totarasync);

        // Make sure one server does not affect others.
        $server2 = $connectgenerator->create_server(array('apiversion' => 2));
        util::update_local_hierarchy($server2, 'org', $frameworks, $organisations);
        $resultframeworks = $DB->get_records('org_framework', array(), 'sortorder ASC');
        $this->assertCount(5, $resultframeworks);
        $resultorganisations = $DB->get_records('org', array(), 'id ASC');
        $this->assertCount(7, $resultorganisations);
    }

    public function test_update_local_hierarchy_org_fields() {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        /** @var totara_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        /** @var auth_connect_generator $connectgenerator */
        $connectgenerator = $this->getDataGenerator()->get_plugin_generator('auth_connect');

        $org_type1 = $DB->get_record('org_type', array('id' => $hierarchygenerator->create_org_type(array('idnumber' => 'tidnum1'))));
        $hierarchygenerator->create_hierarchy_type_checkbox(array('hierarchy' => 'organisation', 'typeidnumber' => 'tidnum1', 'value' => ''));
        $hierarchygenerator->create_hierarchy_type_text(array('hierarchy' => 'organisation', 'typeidnumber' => 'tidnum1', 'value' => ''));
        $fields1 = $DB->get_records('org_type_info_field', array('typeid' => $org_type1->id), 'shortname ASC');
        $fields1 = array_values($fields1);
        $this->assertSame('checkbox', $fields1[0]->datatype);
        $this->assertSame('text', $fields1[1]->datatype);
        $org_type2 = $DB->get_record('org_type', array('id' => $hierarchygenerator->create_org_type(array('idnumber' => 'tidnum2'))));

        $server = $connectgenerator->create_server(array('apiversion' => 2));
        set_config('syncorganisations', 1, 'auth_connect');

        $frameworks = array();
        $frameworks[0] = array();
        $frameworks[0]['id'] = '11';
        $frameworks[0]['shortname'] = 'fw3';
        $frameworks[0]['idnumber'] = 'fwid3';
        $frameworks[0]['description'] = 'a b c';
        $frameworks[0]['sortorder'] = '1';
        $frameworks[0]['timecreated'] = '400';
        $frameworks[0]['timeupdated'] = '500';
        $frameworks[0]['usermodified'] = '66';
        $frameworks[0]['visible'] = '1';
        $frameworks[0]['hidecustomfields'] = '0';
        $frameworks[0]['fullname'] = 'framework 3';

        $organisations = array();
        $organisations[0] = array();
        $organisations[0]['id'] = '19';
        $organisations[0]['shortname'] = 'isn0';
        $organisations[0]['idnumber'] = 'iid0';
        $organisations[0]['description'] = 'abc';
        $organisations[0]['frameworkid'] = $frameworks[0]['id'];
        $organisations[0]['path'] = '/19';
        $organisations[0]['visible'] = '1';
        $organisations[0]['timecreated'] = '100';
        $organisations[0]['timemodified'] = '200';
        $organisations[0]['usermodified'] = '77';
        $organisations[0]['fullname'] = 'item full name 0';
        $organisations[0]['parentid'] = '0';
        $organisations[0]['depthlevel'] = '1';
        $organisations[0]['typeid'] = '0';
        $organisations[0]['typeidnumber'] = null;
        $organisations[0]['sortthread'] = '01';
        $organisations[0]['totarasync'] = '0';
        $organisations[0]['custom_fields'] = array();
        $organisations[1] = array();
        $organisations[1]['id'] = '99';
        $organisations[1]['shortname'] = 'isn99';
        $organisations[1]['idnumber'] = 'iid99';
        $organisations[1]['description'] = 'abce';
        $organisations[1]['frameworkid'] = $frameworks[0]['id'];
        $organisations[1]['path'] = '/20';
        $organisations[1]['visible'] = '0';
        $organisations[1]['timecreated'] = '11111';
        $organisations[1]['timemodified'] = '2222';
        $organisations[1]['usermodified'] = '444';
        $organisations[1]['fullname'] = 'item full name 99';
        $organisations[1]['parentid'] = '0';
        $organisations[1]['depthlevel'] = '1';
        $organisations[1]['typeid'] = '33';
        $organisations[1]['typeidnumber'] = $org_type1->idnumber;
        $organisations[1]['sortthread'] = '01';
        $organisations[1]['totarasync'] = '1';
        $organisations[1]['custom_fields'] = array(
            array(
                'shortname' => $fields1[0]->shortname,
                'datatype' => $fields1[0]->datatype,
                'data' => '1',
                'value' => null,
            ),
            array(
                'shortname' => $fields1[1]->shortname,
                'datatype' => $fields1[1]->datatype,
                'data' => 'some small text',
                'value' => '1',
            ),
        );

        // Test adding matching data.
        util::update_local_hierarchy($server, 'org', $frameworks, $organisations);
        $newfielddatas = $DB->get_records('org_type_info_data', array(), 'id ASC');
        $this->assertCount(2, $newfielddatas);
        $newfielddatas = array_values($newfielddatas);
        $this->assertSame($fields1[0]->id, $newfielddatas[0]->fieldid);
        $this->assertSame($organisations[1]['custom_fields'][0]['data'], $newfielddatas[0]->data);
        $this->assertSame($fields1[1]->id, $newfielddatas[1]->fieldid);
        $this->assertSame($organisations[1]['custom_fields'][1]['data'], $newfielddatas[1]->data);
        $newfieldparams = $DB->get_records('org_type_info_data_param', array(), 'id ASC');
        $this->assertCount(1, $newfieldparams);
        $newfieldparams = array_values($newfieldparams);
        $this->assertSame($newfielddatas[1]->id, $newfieldparams[0]->dataid);
        $this->assertSame($organisations[1]['custom_fields'][1]['value'], $newfieldparams[0]->value);

        // Test adding mismatched data.
        $DB->delete_records('org');
        $DB->delete_records('org_type_info_data');
        $DB->delete_records('org_type_info_data_param');
        $organisations[1]['custom_fields'] = array(
            array(
                'shortname' => $fields1[0]->shortname. 'xxx',
                'datatype' => $fields1[0]->datatype,
                'data' => '1',
                'value' => null,
            ),
            array(
                'shortname' => $fields1[1]->shortname,
                'datatype' => $fields1[1]->datatype . 'xxx',
                'data' => 'some small text',
                'value' => '1',
            ),
        );
        util::update_local_hierarchy($server, 'org', $frameworks, $organisations);
        $newfielddatas = $DB->get_records('org_type_info_data', array(), 'id ASC');
        $this->assertCount(0, $newfielddatas);
        $newfieldparams = $DB->get_records('org_type_info_data_param', array(), 'id ASC');
        $this->assertCount(0, $newfieldparams);

        // Test updating data.
        $DB->delete_records('org');
        $DB->delete_records('org_type_info_data');
        $DB->delete_records('org_type_info_data_param');
        $organisations[1]['custom_fields'] = array(
            array(
                'shortname' => $fields1[0]->shortname,
                'datatype' => $fields1[0]->datatype,
                'data' => '1',
                'value' => null,
            ),
            array(
                'shortname' => $fields1[1]->shortname,
                'datatype' => $fields1[1]->datatype,
                'data' => 'some small text',
                'value' => '1',
            ),
        );
        util::update_local_hierarchy($server, 'org', $frameworks, $organisations);
        $newfielddatas = $DB->get_records('org_type_info_data', array(), 'id ASC');
        $this->assertCount(2, $newfielddatas);
        $newfieldparams = $DB->get_records('org_type_info_data_param', array(), 'id ASC');
        $this->assertCount(1, $newfieldparams);

        $organisations[1]['custom_fields'] = array(
            array(
                'shortname' => $fields1[0]->shortname,
                'datatype' => $fields1[0]->datatype,
                'data' => '0',
                'value' => '1',
            ),
            array(
                'shortname' => $fields1[1]->shortname,
                'datatype' => $fields1[1]->datatype,
                'data' => 'some small text 2',
                'value' => null,
            ),
        );
        util::update_local_hierarchy($server, 'org', $frameworks, $organisations);
        $newfielddatas = $DB->get_records('org_type_info_data', array(), 'id ASC');
        $this->assertCount(2, $newfielddatas);
        $newfielddatas = array_values($newfielddatas);
        $this->assertSame($fields1[0]->id, $newfielddatas[0]->fieldid);
        $this->assertSame($organisations[1]['custom_fields'][0]['data'], $newfielddatas[0]->data);
        $this->assertSame($fields1[1]->id, $newfielddatas[1]->fieldid);
        $this->assertSame($organisations[1]['custom_fields'][1]['data'], $newfielddatas[1]->data);
        $newfieldparams = $DB->get_records('org_type_info_data_param', array(), 'id ASC');
        $this->assertCount(1, $newfieldparams);
        $newfieldparams = array_values($newfieldparams);
        $this->assertSame($newfielddatas[0]->id, $newfieldparams[0]->dataid);
        $this->assertSame($organisations[1]['custom_fields'][0]['value'], $newfieldparams[0]->value);

        // Test updating mismatched data.
        $organisations[1]['custom_fields'] = array(
            array(
                'shortname' => $fields1[0]->shortname. 'xxx',
                'datatype' => $fields1[0]->datatype,
                'data' => '1',
                'value' => null,
            ),
            array(
                'shortname' => $fields1[1]->shortname,
                'datatype' => $fields1[1]->datatype . 'xxx',
                'data' => 'some small text',
                'value' => '1',
            ),
        );

        util::update_local_hierarchy($server, 'org', $frameworks, $organisations);
        $newfielddatas = $DB->get_records('org_type_info_data', array(), 'id ASC');
        $this->assertCount(0, $newfielddatas);
        $newfieldparams = $DB->get_records('org_type_info_data_param', array(), 'id ASC');
        $this->assertCount(0, $newfieldparams);
    }

    public function test_sync_positions() {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        /** @var auth_connect_generator $connectgenerator */
        $connectgenerator = $this->getDataGenerator()->get_plugin_generator('auth_connect');

        $frameworks = array();
        $frameworks[0] = array();
        $frameworks[0]['id'] = '10';
        $frameworks[0]['shortname'] = 'fw1';
        $frameworks[0]['idnumber'] = 'fwid1';
        $frameworks[0]['description'] = 'a b c';
        $frameworks[0]['sortorder'] = '1';
        $frameworks[0]['timecreated'] = '100';
        $frameworks[0]['timeupdated'] = '200';
        $frameworks[0]['usermodified'] = '66';
        $frameworks[0]['visible'] = '1';
        $frameworks[0]['hidecustomfields'] = '0';
        $frameworks[0]['fullname'] = 'framework 1';

        $positions = array();
        $positions[0] = array();
        $positions[0]['id'] = '19';
        $positions[0]['shortname'] = 'isn0';
        $positions[0]['idnumber'] = 'iid0';
        $positions[0]['description'] = 'abc';
        $positions[0]['frameworkid'] = $frameworks[0]['id'];
        $positions[0]['path'] = '/19';
        $positions[0]['visible'] = '1';
        $positions[0]['timevalidfrom'] = null;
        $positions[0]['timevalidto'] = null;
        $positions[0]['timecreated'] = '100';
        $positions[0]['timemodified'] = '100';
        $positions[0]['usermodified'] = '66';
        $positions[0]['fullname'] = 'item full name 0';
        $positions[0]['parentid'] = '0';
        $positions[0]['depthlevel'] = '1';
        $positions[0]['typeid'] = '0';
        $positions[0]['typeidnumber'] = null;
        $positions[0]['sortthread'] = '01';
        $positions[0]['totarasync'] = '1';

        $server1 = $connectgenerator->create_server(array('apiversion' => 1));
        $result = util::sync_positions($server1);
        $this->assertTrue($result);
        $this->assertCount(0, $DB->get_records('pos_framework'));
        $this->assertCount(0, $DB->get_records('pos'));

        $server2 = $connectgenerator->create_server(array('apiversion' => 2));
        set_config('syncpositions', 0, 'auth_connect');
        $result = util::sync_positions($server2);
        $this->assertTrue($result);
        $this->assertCount(0, $DB->get_records('pos_framework'));
        $this->assertCount(0, $DB->get_records('pos'));

        set_config('syncpositions', 1, 'auth_connect');
        jsend::set_phpunit_testdata(array(array('status' => 'error', 'message' => 'error')));
        $result = util::sync_positions($server2);
        $this->assertFalse($result);
        $this->assertCount(0, $DB->get_records('pos_framework'));
        $this->assertCount(0, $DB->get_records('pos'));

        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array('frameworks' => null, 'positions' => null))));
        $result = util::sync_positions($server2);
        $this->assertTrue($result);
        $this->assertCount(0, $DB->get_records('pos_framework'));
        $this->assertCount(0, $DB->get_records('pos'));

        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array('frameworks' => $frameworks, 'positions' => $positions))));
        $result = util::sync_positions($server2);
        $this->assertTrue($result);
        $this->assertCount(1, $DB->get_records('pos_framework'));
        $this->assertCount(1, $DB->get_records('pos'));
    }

    public function test_sync_organisations() {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        /** @var auth_connect_generator $connectgenerator */
        $connectgenerator = $this->getDataGenerator()->get_plugin_generator('auth_connect');

        $frameworks = array();
        $frameworks[0] = array();
        $frameworks[0]['id'] = '10';
        $frameworks[0]['shortname'] = 'fw1';
        $frameworks[0]['idnumber'] = 'fwid1';
        $frameworks[0]['description'] = 'a b c';
        $frameworks[0]['sortorder'] = '1';
        $frameworks[0]['timecreated'] = '100';
        $frameworks[0]['timeupdated'] = '200';
        $frameworks[0]['usermodified'] = '66';
        $frameworks[0]['visible'] = '1';
        $frameworks[0]['hidecustomfields'] = '0';
        $frameworks[0]['fullname'] = 'framework 1';

        $organisations = array();
        $organisations[0] = array();
        $organisations[0]['id'] = '19';
        $organisations[0]['shortname'] = 'isn0';
        $organisations[0]['idnumber'] = 'iid0';
        $organisations[0]['description'] = 'abc';
        $organisations[0]['frameworkid'] = $frameworks[0]['id'];
        $organisations[0]['path'] = '/19';
        $organisations[0]['visible'] = '1';
        $organisations[0]['timecreated'] = '100';
        $organisations[0]['timemodified'] = '100';
        $organisations[0]['usermodified'] = '66';
        $organisations[0]['fullname'] = 'item full name 0';
        $organisations[0]['parentid'] = '0';
        $organisations[0]['depthlevel'] = '1';
        $organisations[0]['typeid'] = '0';
        $organisations[0]['typeidnumber'] = null;
        $organisations[0]['sortthread'] = '01';
        $organisations[0]['totarasync'] = '1';

        $server1 = $connectgenerator->create_server(array('apiversion' => 1));
        $result = util::sync_organisations($server1);
        $this->assertTrue($result);
        $this->assertCount(0, $DB->get_records('org_framework'));
        $this->assertCount(0, $DB->get_records('org'));

        $server2 = $connectgenerator->create_server(array('apiversion' => 2));
        set_config('syncorganisations', 0, 'auth_connect');
        $result = util::sync_organisations($server2);
        $this->assertTrue($result);
        $this->assertCount(0, $DB->get_records('org_framework'));
        $this->assertCount(0, $DB->get_records('org'));

        set_config('syncorganisations', 1, 'auth_connect');
        jsend::set_phpunit_testdata(array(array('status' => 'error', 'message' => 'error')));
        $result = util::sync_organisations($server2);
        $this->assertFalse($result);
        $this->assertCount(0, $DB->get_records('org_framework'));
        $this->assertCount(0, $DB->get_records('org'));

        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array('frameworks' => null, 'organisations' => null))));
        $result = util::sync_organisations($server2);
        $this->assertTrue($result);
        $this->assertCount(0, $DB->get_records('org_framework'));
        $this->assertCount(0, $DB->get_records('org'));

        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array('frameworks' => $frameworks, 'organisations' => $organisations))));
        $result = util::sync_organisations($server2);
        $this->assertTrue($result);
        $this->assertCount(1, $DB->get_records('org_framework'));
        $this->assertCount(1, $DB->get_records('org'));
    }

    public function test_update_local_user_embedded_preferences() {
        global $DB;
        $this->resetAfterTest();

        set_config('syncpasswords', 0, 'totara_connect');
        $fs = get_file_storage();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');
        $server = $generator->create_server();
        $this->assertEquals(2, $DB->count_records('user', array()));

        // Add new users from server. One has emailstop and suspended set on the server
        $serverusers = array();
        $serverusers[] = $generator->get_fake_server_user();
        $serverusers[] = $generator->get_fake_server_user();
        $serverusers[1]['emailstop'] = '1';
        $serverusers[1]['suspended'] = '1';

        foreach ($serverusers as $serveruser) {
            $user = util::update_local_user($server, $serveruser, true);
            $this->assertSame($serveruser['firstname'], $user->firstname);
            $this->assertSame($serveruser['lastname'], $user->lastname);
            $this->assertSame($serveruser['emailstop'], $user->emailstop);
            $this->assertSame($serveruser['suspended'], $user->emailstop);
        }

        // Update emailstop attribute on client
        $sql = "UPDATE {user}
                   SET emailstop = 1, suspended = 1";
        $DB->execute($sql);

        // Check that users' emailstop is still set
        foreach ($serverusers as $serveruser) {
            $user = util::update_local_user($server, $serveruser, true);
            $this->assertSame($serveruser['firstname'], $user->firstname);
            $this->assertSame($serveruser['lastname'], $user->lastname);
            $this->assertEquals(1, $user->emailstop);
            $this->assertEquals(1, $user->suspended);
        }
    }

    public function test_finish_sso() {
        global $DB, $SESSION, $USER;
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');

        $server = $generator->create_server();
        $serveruser = $generator->get_fake_server_user();
        util::update_local_user($server, $serveruser);

        $ssotoken = sha1('fogpigfd');
        $sid = md5('xxxzfzfdz');

        @session_id($sid);
        if ($sid !== session_id()) {
            $this->markTestSkipped('This PHP does not support changing of session id from CLI');
        }

        $this->setUser(null);
        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => $serveruser)));

        $this->setCurrentTimeStart();
        try {
            @util::finish_sso($server, $ssotoken);
            $this->fail('redirect expected on successful sso');
        } catch (moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
        }
        $session = $DB->get_record('auth_connect_sso_sessions', array('ssotoken' => $ssotoken), '*', MUST_EXIST);
        $this->assertSame($server->id, $session->serverid);
        $this->assertSame($sid, $session->sid);
        $this->assertSame($sid, $session->sid);
        $this->assertSame($ssotoken, $session->ssotoken);
        $this->assertSame($server->id, $session->serverid);
        $this->assertSame($serveruser['id'], $session->serveruserid);
        $this->assertSame($USER->id, $session->userid);
        $this->assertTimeCurrent($session->timecreated);
        $expected = new stdClass();
        $expected->justloggedin = true;
        $this->assertEquals($expected, $SESSION);

        // Verify guest user may log in too.
        $DB->delete_records('auth_connect_sso_sessions', array());

        $ssotoken = sha1('aaaa');
        $sid = md5('rerereer');

        session_id($sid);
        $this->setGuestUser();
        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => $serveruser)));

        try {
            @util::finish_sso($server, $ssotoken);
            $this->fail('redirect expected on successful sso');
        } catch (moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
        }
        $session = $DB->get_record('auth_connect_sso_sessions', array('ssotoken' => $ssotoken), '*', MUST_EXIST);
        $expected = new stdClass();
        $expected->justloggedin = true;
        $this->assertEquals($expected, $SESSION);

        // Set session flag on failure.
        $ssotoken = sha1('wwww');
        $sid = md5('qqqqq');

        session_id($sid);
        $this->setUser(null);
        jsend::set_phpunit_testdata(array(array('status' => 'error', 'message' => 'no way')));

        $this->setCurrentTimeStart();
        try {
            @util::finish_sso($server, $ssotoken);
            $this->fail('redirect expected on falied sso');
        } catch (moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
            $this->assertFalse($DB->record_exists('auth_connect_sso_sessions', array('ssotoken' => $ssotoken)));
            $this->assertFalse($DB->record_exists('auth_connect_sso_sessions', array('ssotoken' => $ssotoken)));
        }

        // Make sure logged in users cannot start SSO.
        $DB->delete_records('auth_connect_sso_sessions', array());

        $ssotoken = sha1('ppp');
        $sid = md5('yyyyy');

        session_id($sid);
        $admin = get_admin();
        \core\session\manager::set_user($admin); // Note setAdminUser() clears session now.
        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => $serveruser)));

        try {
            @util::finish_sso($server, $ssotoken);
            $this->fail('coding exception expected whe nuser already logged in');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: user must not be logged in yet', $ex->getMessage());
            $this->assertFalse($DB->record_exists('auth_connect_sso_sessions', array('ssotoken' => $ssotoken)));
        }
    }

    public function test_create_sso_request() {
        global $DB;
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');
        $server = $generator->create_server();

        $sid1 = md5('xxx');
        $sid2 = md5('yyy');
        $sid3 = md5('zzz');

        $this->set_auth_enabled(true);

        @session_id($sid1);
        if ($sid1 !== session_id()) {
            $this->markTestSkipped('This PHP does not support changing of session id from CLI');
        }

        $this->setUser(null);

        // Valid first.
        $this->setCurrentTimeStart();
        $result1 = util::create_sso_request($server);
        $this->assertInstanceOf('moodle_url', $result1);
        $request1 = $DB->get_record('auth_connect_sso_requests', array('sid' => $sid1));
        $this->assertTimeCurrent($request1->timecreated);
        $this->assertSame($server->id, $request1->serverid);
        $this->assertSame(40, strlen($request1->requesttoken));
        $this->assertCount(1, $DB->get_records('auth_connect_sso_requests'));

        // Allow repeated request.
        $result2 = util::create_sso_request($server);
        $this->assertInstanceOf('moodle_url', $result2);
        $this->assertEquals($result1, $result2);
        $this->assertCount(1, $DB->get_records('auth_connect_sso_requests'));

        $request1->timecreated = $request1->timecreated - util::REQUEST_LOGIN_TIMEOUT + 10;
        $DB->update_record('auth_connect_sso_requests', $request1);
        $result2b = util::create_sso_request($server);
        $this->assertInstanceOf('moodle_url', $result2b);
        $this->assertEquals($result2, $result2b);
        $this->assertCount(1, $DB->get_records('auth_connect_sso_requests'));
        $this->assertTrue($DB->record_exists('auth_connect_sso_requests', array('id' => $request1->id)));

        // Next request as guest.
        $this->setGuestUser();
        session_id($sid2);
        $result3 = util::create_sso_request($server);
        $this->assertInstanceOf('moodle_url', $result3);
        $this->assertNotEquals($result1, $result3);
        $this->assertCount(2, $DB->get_records('auth_connect_sso_requests'));
        $request2 = $DB->get_record('auth_connect_sso_requests', array('sid' => $sid2));

        // Create new if expired.
        $request2->timecreated = $request2->timecreated - util::REQUEST_LOGIN_TIMEOUT - 1;
        $DB->update_record('auth_connect_sso_requests', $request2);
        $result4 = util::create_sso_request($server);
        $this->assertInstanceOf('moodle_url', $result3);
        $this->assertNotEquals($result3, $result4);
        $this->assertCount(2, $DB->get_records('auth_connect_sso_requests'));
        $this->assertFalse($DB->record_exists('auth_connect_sso_requests', array('id' => $request2->id)));

        // Not enabled.
        $this->set_auth_enabled(false);
        session_id($sid3);
        $this->setUser(null);
        $result = util::create_sso_request($server);
        $this->assertNull($result);
        $this->assertCount(2, $DB->get_records('auth_connect_sso_requests'));
        $this->set_auth_enabled(true);

        // Is logged in.
        $this->setAdminUser();
        session_id($sid3);
        $result = util::create_sso_request($server);
        $this->assertNull($result);
        $this->assertCount(2, $DB->get_records('auth_connect_sso_requests'));
        $this->setUser(null);

        // No session.
        session_id('');
        $result = util::create_sso_request($server);
        $this->assertNull($result);
        $this->assertCount(2, $DB->get_records('auth_connect_sso_requests'));

        // Deleting.
        session_id($sid3);
        $server->status = util::SERVER_STATUS_DELETING;
        $this->assertNull($result);
        $this->assertCount(2, $DB->get_records('auth_connect_sso_requests'));
    }

    public function test_warn_if_not_https() {
        global $CFG;
        $this->resetAfterTest();

        // No warning expected on HTTPS sites.
        $CFG->wwwroot = 'https://example.com/lms';
        $this->assertSame('', util::warn_if_not_https());

        // Some warning expected on https.
        $CFG->wwwroot = 'http://example.com/lms';
        $this->assertSame("!! For security reasons all Totara Connect clients should be hosted via a secure protocol (https). !!\n", util::warn_if_not_https());
    }

    /**
     * Enable/disable auth_connect plugin.
     *
     * @param bool $enabled
     */
    protected function set_auth_enabled($enabled) {
        global $CFG;
        $authsenabled = explode(',', $CFG->auth);

        if ($enabled) {
            $authsenabled[] = 'connect';
            $authsenabled = array_unique($authsenabled);
            set_config('auth', implode(',', $authsenabled));
        } else {
            $key = array_search('connect', $authsenabled);
            if ($key !== false) {
                unset($authsenabled[$key]);
                set_config('auth', implode(',', $authsenabled));
            }
        }
    }

    /**
     * Gets list of local users linked to $serverusers, keep the same array order.
     * @param stdClass $server
     * @param array $serverusers
     * @return array of local users with keys matching the original array
     */
    protected function fetch_local_server_users($server, array $serverusers) {
        global $DB;

        $users = array();
        foreach ($serverusers as $k => $suser) {
            $mapping = $DB->get_record('auth_connect_users', array('serverid' => $server->id, 'serveruserid' => $suser['id']));
            if (!$mapping) {
                $users[$k] = null;
                continue;
            }
            $users[$k] = $DB->get_record('user', array('id' => $mapping->userid));
        }

        return $users;
    }

    /**
     * Gets list of local cohorts linked to $servercohorts, keep the same array order.
     * @param stdClass $server
     * @param string $type 'cohort' or 'course'
     * @param array $servercollections
     * @return array of local cohorts with keys matching the original array
     */
    protected function fetch_local_server_cohorts($server, $type, array $servercollections) {
        global $DB;

        $cohorts = array();
        foreach ($servercollections as $k => $col) {
            $mapping = $DB->get_record('auth_connect_user_collections', array('serverid' => $server->id, 'collectiontype' => $type, 'collectionid' => $col['id']));
            if (!$mapping) {
                $cohorts[$k] = null;
                continue;
            }
            $cohorts[$k] = $DB->get_record('cohort', array('id' => $mapping->cohortid));
        }

        return $cohorts;
    }
}
