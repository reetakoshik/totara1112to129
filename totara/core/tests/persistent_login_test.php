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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_core
 */

use \totara_core\persistent_login;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests persistent_login class.
 *
 * NOTE: we cannot test much here because sessions do not work in PHPUNIT.
 */
class totara_core_persistent_login_testcase extends advanced_testcase {
    public function test_get_cookie_name() {
        global $CFG;
        $this->resetAfterTest();

        $name = persistent_login::get_cookie_name();
        $this->assertSame('TOTARAPL', $name);

        $CFG->sessioncookie = 'xxx';
        $name = persistent_login::get_cookie_name();
        $this->assertSame('TOTARAPL_xxx', $name);
    }

    public function test_get_cookie_lifetime() {
        $lifetime = persistent_login::get_cookie_lifetime();
        $this->assertGreaterThan(10, $lifetime);
    }

    public function test_is_cookie_secure() {
        global $CFG;
        $this->resetAfterTest();

        $CFG->wwwroot = 'http://www.xample.com/totara';
        $this->assertFalse(persistent_login::is_cookie_secure());

        $CFG->wwwroot = 'https://www.xample.com/totara';
        $this->assertTrue(persistent_login::is_cookie_secure());
    }

    public function test_kill() {
        global $DB, $CFG;
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $login1 = $this->fake_start($user1);
        $login2 = $this->fake_start($user2);
        $login3 = $this->fake_start($user3);
        $this->assertCount(3, $DB->get_records('persistent_login', array()));

        persistent_login::kill($login1->sid);
        $this->assertCount(3, $DB->get_records('persistent_login', array()));

        $CFG->persistentloginenable = true;
        persistent_login::kill($login1->sid);
        $logins = $DB->get_records('persistent_login', array());
        $this->assertCount(2, $logins);
        $this->assertArrayHasKey($login2->id, $logins);
        $this->assertArrayHasKey($login3->id, $logins);
    }

    public function test_kill_user() {
        global $DB, $CFG;
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $login1 = $this->fake_start($user1);
        $login2 = $this->fake_start($user2);
        $login3 = $this->fake_start($user3);
        $login4 = $this->fake_start($user3);
        $this->assertCount(4, $DB->get_records('persistent_login', array()));

        persistent_login::kill_user($user3->id);
        $this->assertCount(4, $DB->get_records('persistent_login', array()));

        $CFG->persistentloginenable = true;
        persistent_login::kill_user($user3->id);
        $logins = $DB->get_records('persistent_login', array());
        $this->assertCount(2, $logins);
        $this->assertArrayHasKey($login1->id, $logins);
        $this->assertArrayHasKey($login2->id, $logins);
    }

    public function test_kill_all() {
        global $DB, $CFG;
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $login1 = $this->fake_start($user1);
        $login2 = $this->fake_start($user2);
        $login3 = $this->fake_start($user3);
        $login4 = $this->fake_start($user3);
        $this->assertCount(4, $DB->get_records('persistent_login', array()));
        $CFG->persistentloginenable = false;
        persistent_login::kill_all();
        $this->assertCount(0, $DB->get_records('persistent_login', array()));

        $login1 = $this->fake_start($user1);
        $login2 = $this->fake_start($user2);
        $login3 = $this->fake_start($user3);
        $login4 = $this->fake_start($user3);
        $CFG->persistentloginenable = true;
        persistent_login::kill_all();
        $logins = $DB->get_records('persistent_login', array());
        $this->assertCount(0, $logins);
    }

    public function test_session_timeout() {
        global $DB, $CFG;
        $this->resetAfterTest();

        $CFG->persistentloginenable = true;

        $sessiontimeout = $CFG->sessiontimeout;

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $session1 = $this->fake_session($user1, time() - $sessiontimeout - 1000, time() - $sessiontimeout - 100);
        $session2 = $this->fake_session($user1, time() - $sessiontimeout - 100, time() - $sessiontimeout + 100);
        $session3 = $this->fake_session($user1, time() - 1000 - 60*60*24*3, time() - 100 - 60*60*24*3);

        $login1 = $this->fake_start($user1, $session1);
        $login2 = $this->fake_start($user2, $session2);
        $login3 = $this->fake_start($user2, $session3);
        $login4 = $this->fake_start($user3);

        $this->assertCount(4, $DB->get_records('persistent_login', array()));
        $this->assertCount(3, $DB->get_records('sessions', array()));
        $this->assertNull($login1->lastip);
        $this->assertNull($login1->lastaccess);

        \core\session\manager::gc();

        $logins = $DB->get_records('persistent_login', array(), 'id ASC');
        $sessions = $DB->get_records('sessions', array(), 'id ASC');
        $this->assertCount(4, $logins);
        $this->assertCount(2, $sessions);
        $this->assertArrayHasKey($session1->id, $sessions);
        $this->assertArrayHasKey($session2->id, $sessions);
        $this->assertEquals($login2, $logins[$login2->id]);
        $this->assertEquals($login1, $logins[$login1->id]);
        $this->assertEquals($login4, $logins[$login4->id]);
        $this->assertSame($session3->lastip, $logins[$login3->id]->lastip);
        $this->assertSame($session3->timemodified, $logins[$login3->id]->lastaccess);
    }

    public function test_gc() {
        global $DB;
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $login1 = $this->fake_start($user1);
        $login2 = $this->fake_start($user2);
        $login3 = $this->fake_start($user3);
        $login1->timecreated = time() - persistent_login::get_cookie_lifetime() - 10;
        $DB->update_record('persistent_login', $login1);
        $this->assertCount(3, $DB->get_records('persistent_login', array()));

        persistent_login::gc();

        $logins = $DB->get_records('persistent_login', array());
        $this->assertCount(2, $logins);
        $this->assertArrayHasKey($login2->id, $logins);
        $this->assertArrayHasKey($login3->id, $logins);
    }

    protected function fake_session($user, $timecreated, $timemodified) {
        global $DB;

        $record = new \stdClass();
        $record->state = 0;
        $record->sid = random_string(32);
        $record->userid = $user->id;
        $record->sessdata = 'xyz';
        $record->timecreated = $timecreated;
        $record->timemodified = $timemodified;
        $record->firstip = '192.168.0.1';
        $record->lastip = '192.168.0.2';
        $id = $DB->insert_record('sessions', $record);
        return $DB->get_record('sessions', array('id' => $id));
    }

    protected function fake_start($user, $session = null) {
        global $DB;

        $record = new \stdClass();
        $record->userid = $user->id;
        $record->cookie = random_string(96);
        $record->timecreated = empty($session) ? time() : $session->timecreated + 10;
        $record->timeautologin = null;
        $record->useragent = 'browser';
        $record->sid = empty($session) ? random_string(32) : $session->sid;
        $id = $DB->insert_record('persistent_login', $record);
        return $DB->get_record('persistent_login', array('id' => $id));
    }
}
