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

defined('MOODLE_INTERNAL') || die();

/**
 * Tests util class.
 */
class auth_connect_generator_testcase extends advanced_testcase {
    public function test_create_server() {
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');

        $this->setCurrentTimeStart();
        $server = $generator->create_server();
        $this->assertEquals(util::SERVER_STATUS_OK, $server->status);
        $this->assertSame(40, strlen($server->serveridnumber));
        $this->assertSame(40, strlen($server->serversecret));
        $this->assertSame('https://www.example.com/tcc', $server->serverurl);
        $this->assertStringStartsWith('TC server ', $server->servername);
        $this->assertSame('', $server->servercomment);
        $this->assertSame(40, strlen($server->clientidnumber));
        $this->assertSame(40, strlen($server->clientsecret));
        $this->assertSame('1', $server->apiversion);
        $this->assertTimeCurrent($server->timecreated);
        $this->assertSame($server->timecreated, $server->timemodified);

        $record = array(
            'serverurl' => 'http://example.net',
            'servername' => 'My name',
            'apiversion' => '2',
        );
        $server2 = $generator->create_server($record);
        foreach ($record as $k => $v) {
            $this->assertSame($v, $server2->$k);
        }
    }

    public function test_migrate_user() {
        global $DB;
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');

        $server = $generator->create_server();
        $user = $this->getDataGenerator()->create_user();

        $this->setCurrentTimeStart();
        $generator->migrate_server_user($server, $user, 666);
        $this->assertSame('connect', $user->auth);
        $userrecord = $DB->get_record('user', array('id' => $user->id));
        $this->assertEquals($user, $userrecord);

        $record = $DB->get_record('auth_connect_users', array('userid' => $user->id, 'serverid' => $server->id));
        $this->assertEquals(666, $record->serveruserid);
        $this->assertTimeCurrent($record->timecreated);
    }

    public function test_get_fake_server_user() {
        global $DB;
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');

        $this->assertCount(2, $DB->get_records('user', array()));
        $serveruser = $generator->get_fake_server_user();
        $this->assertCount(2, $DB->get_records('user', array()));
        $this->assertInternalType('array', $serveruser);
        $serveruser = (object)$serveruser;
        $this->assertGreaterThan(10000 + get_admin()->id, $serveruser->id);
        $this->assertObjectHasAttribute('username', $serveruser);
        $this->assertObjectHasAttribute('email', $serveruser);
        $this->assertNull($serveruser->password);
        $this->assertSame('0', $serveruser->deleted);
        $this->assertSame('1', $serveruser->confirmed);
        $this->assertSame('0', $serveruser->suspended);
    }
}
