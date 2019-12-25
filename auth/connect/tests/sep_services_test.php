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

use \auth_connect\sep_services;
use \auth_connect\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests sep services class.
 */
class auth_connect_sep_services_testcase extends advanced_testcase {
    public function test_validate_sso_request_token() {
        global $DB;
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');

        $server = $generator->create_server();

        $record = new stdClass();
        $record->serverid     = $server->id;
        $record->requesttoken = sha1('sometoken');
        $record->sid          = md5('somesid');
        $record->timecreated  = time();
        $record->id = $DB->insert_record('auth_connect_sso_requests', $record);

        // Correct request.
        $result = sep_services::validate_sso_request_token($server, array('requesttoken' => $record->requesttoken));
        $this->assertSame(array('status' => 'success', 'data' => array()), $result);
        $this->assertTrue($DB->record_exists('auth_connect_sso_requests', array('id' => $record->id)));

        // Outdated request.
        $DB->set_field('auth_connect_sso_requests', 'timecreated', time() - util::REQUEST_LOGIN_TIMEOUT - 1, array('id' => $record->id));
        $result = sep_services::validate_sso_request_token($server, array('requesttoken' => $record->requesttoken));
        $this->assertSame(array('status' => 'error', 'message' => 'sso request timed out'), $result);
        $this->assertFalse($DB->record_exists('auth_connect_sso_requests', array('id' => $record->id)));

        // Missing request.
        $result = sep_services::validate_sso_request_token($server, array('requesttoken' => sha1('abc')));
        $this->assertSame(array('status' => 'error', 'message' => 'sso request timed out'), $result);

        // Wrong parameters.
        $result = sep_services::validate_sso_request_token($server, array('requesttokenxxx' => 'abc'));
        $this->assertSame(array('status' => 'fail', 'data' => array('requesttoken' => 'missing sso request token')), $result);
    }

    public function test_is_sso_user_active() {
        global $DB, $CFG;
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');

        $user = $this->getDataGenerator()->create_user();
        $server = $generator->create_server();

        $sid = md5('hokus');

        $sessionrecord = new \stdClass();
        $sessionrecord->state        = 0;
        $sessionrecord->sid          = $sid;
        $sessionrecord->sessdata     = null;
        $sessionrecord->userid       = $user->id;
        $sessionrecord->timecreated  = time() - 60*60;
        $sessionrecord->timemodified = time() - 30;
        $sessionrecord->firstip      = $sessionrecord->lastip = '10.0.0.1';
        $sessionrecord->id = $DB->insert_record('sessions', $sessionrecord);

        $sso = new stdClass();
        $sso->sid          = $sid;
        $sso->ssotoken     = util::create_unique_hash('auth_connect_sso_sessions', 'ssotoken');
        $sso->serverid     = $server->id;
        $sso->serveruserid = 666;
        $sso->userid       = $user->id;
        $sso->timecreated  = $sessionrecord->timecreated;
        $sso->id = $DB->insert_record('auth_connect_sso_sessions', $sso);

        // Active.
        $result = sep_services::is_sso_user_active($server, array('ssotoken' => $sso->ssotoken));
        $this->assertSame(array('status' => 'success', 'data' => array('active' => true)), $result);
        $this->assertTrue($DB->record_exists('sessions', array('id' => $sessionrecord->id)));
        $this->assertTrue($DB->record_exists('auth_connect_sso_sessions', array('id' => $sso->id)));

        // Do the timing out.
        $DB->set_field('sessions', 'timemodified', time() - $CFG->sessiontimeout - 1, array('id' => $sessionrecord->id));
        $result = sep_services::is_sso_user_active($server, array('ssotoken' => $sso->ssotoken));
        $this->assertSame(array('status' => 'success', 'data' => array('active' => false)), $result);
        $this->assertFalse($DB->record_exists('sessions', array('id' => $sessionrecord->id)));
        $this->assertFalse($DB->record_exists('auth_connect_sso_sessions', array('id' => $sso->id)));

        // After timeout.
        $result = sep_services::is_sso_user_active($server, array('ssotoken' => $sso->ssotoken));
        $this->assertSame(array('status' => 'success', 'data' => array('active' => false)), $result);
        $this->assertFalse($DB->record_exists('sessions', array('id' => $sessionrecord->id)));
        $this->assertFalse($DB->record_exists('auth_connect_sso_sessions', array('id' => $sso->id)));

        // Invalid ssotoken.
        $result = sep_services::is_sso_user_active($server, array('ssotoken' => sha1('xxx')));
        $this->assertSame(array('status' => 'success', 'data' => array('active' => false)), $result);

        // Missing ssotoken.
        $result = sep_services::is_sso_user_active($server, array('ssotokenxxx' => 'xxx'));
        $this->assertSame(array('status' => 'fail', 'data' => array('ssotoken' => 'missing sso token')), $result);
    }

    public function test_kill_sso_user() {
        global $DB;
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');

        $user = $this->getDataGenerator()->create_user();
        $server = $generator->create_server();

        $sid = md5('hokus');

        $sessionrecord = new \stdClass();
        $sessionrecord->state        = 0;
        $sessionrecord->sid          = $sid;
        $sessionrecord->sessdata     = null;
        $sessionrecord->userid       = $user->id;
        $sessionrecord->timecreated  = time() - 60*60;
        $sessionrecord->timemodified = time() - 30;
        $sessionrecord->firstip      = $sessionrecord->lastip = '10.0.0.1';
        $sessionrecord->id = $DB->insert_record('sessions', $sessionrecord);

        $sso = new stdClass();
        $sso->sid          = $sid;
        $sso->ssotoken     = util::create_unique_hash('auth_connect_sso_sessions', 'ssotoken');
        $sso->serverid     = $server->id;
        $sso->serveruserid = 666;
        $sso->userid       = $user->id;
        $sso->timecreated  = $sessionrecord->timecreated;
        $sso->id = $DB->insert_record('auth_connect_sso_sessions', $sso);

        // Kill existing.
        $result = sep_services::kill_sso_user($server, array('ssotoken' => $sso->ssotoken));
        $this->assertSame(array('status' => 'success', 'data' => array()), $result);
        $this->assertFalse($DB->record_exists('sessions', array('id' => $sessionrecord->id)));
        $this->assertFalse($DB->record_exists('auth_connect_sso_sessions', array('id' => $sso->id)));

        // Kill non-existing.
        $result = sep_services::kill_sso_user($server, array('ssotoken' => sha1('abc')));
        $this->assertSame(array('status' => 'success', 'data' => array()), $result);

        // Missing ssotoken.
        $result = sep_services::kill_sso_user($server, array('ssotokenxxx' => 'xxx'));
        $this->assertSame(array('status' => 'fail', 'data' => array('ssotoken' => 'missing sso token')), $result);
    }
}
