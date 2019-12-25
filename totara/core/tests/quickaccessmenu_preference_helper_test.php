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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_core
 */

global $CFG;
require_once($CFG->dirroot . '/lib/adminlib.php');

use totara_core\quickaccessmenu\preference_helper;

/**
 * @group totara_core
 */
class totara_core_quickaccessmenu_preference_helper_testcase extends advanced_testcase {

    public function test_set_quickaccess_preference() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $otheruser = $this->getDataGenerator()->create_user();
        $otheruserid = $otheruser->id;

        $DB->delete_records('quickaccess_preferences', array('userid' => $otheruserid));
        // Clear cache
        $cache = \cache::make('totara_core', 'quickaccessmenu');
        $cache->delete($otheruserid);

        preference_helper::set_preference($otheruserid, 'aaa', 'bbb');
        $this->assertSame(json_encode('bbb'), $DB->get_field('quickaccess_preferences', 'value', array('userid' => $otheruserid, 'name' => 'aaa')));
        $this->assertSame('bbb', preference_helper::get_preference($otheruserid, 'aaa', null));

        preference_helper::set_preference($otheruserid, 'xxx', 'yyy');
        $this->assertSame(json_encode('yyy'), $DB->get_field('quickaccess_preferences', 'value', array('userid' => $otheruserid, 'name' => 'xxx')));
        $newprefs = preference_helper::get_preference($otheruserid, 'xxx', null);
        $this->assertSame('yyy', $newprefs);
        $actual = preference_helper::get_preference($otheruserid, null, null);
        $this->assertTrue(is_array($actual));
        $this->assertSame('bbb', $actual['aaa']);
        $this->assertSame('yyy', $actual['xxx']);

        preference_helper::set_preference($otheruserid, 'xxx', null);
        $this->assertFalse($DB->get_field('quickaccess_preferences', 'value', array('userid' => $otheruserid, 'name' => 'xxx')));
        $this->assertNull(preference_helper::get_preference($otheruserid, 'xxx', null));

        preference_helper::set_preference($otheruserid, 'ooo', true);
        $prefs = preference_helper::get_preference($otheruserid, null, null);

        $this->assertSame('bbb', $prefs['aaa']);
        $this->assertSame(true, $prefs['ooo']);

        preference_helper::set_preference($otheruserid, 'null', 0);
        $this->assertSame(0, preference_helper::get_preference($otheruserid, 'null', null));

        $this->assertSame('lala', preference_helper::get_preference($otheruserid, 'undefined', 'lala'));
    }

    public function test_reset_for_user() {
        global $DB;
        $this->resetAfterTest();
        $userid = get_admin()->id;
        $this->assertSame(0, $DB->count_records('quickaccess_preferences'));
        preference_helper::set_preference($userid, 'whatever', 'something');
        $this->assertSame(1, $DB->count_records('quickaccess_preferences'));
        preference_helper::reset_for_user($userid);
        $this->assertSame(0, $DB->count_records('quickaccess_preferences'));
    }

    public function test_reset_for_user_guest_user() {
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('Preferences cannot be set for the guest user.');
        preference_helper::reset_for_user(guest_user()->id);
    }

    public function test_set_preference_guest_user() {
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('Preferences cannot be set for the guest user.');
        preference_helper::set_preference(guest_user()->id, 'whatever', 'something');
    }

    public function test_get_preference_guest_user() {
        $data = preference_helper::get_preference(guest_user()->id);
        self::assertSame(array(), $data);
    }

    public function test_get_preference_with_name_guest_user() {
        $data = preference_helper::get_preference(guest_user()->id, 'whatever');
        self::assertSame(null, $data);
    }

    public function test_unset_preference_guest_user() {
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('Preferences cannot be set for the guest user.');
        preference_helper::unset_preference(guest_user()->id, 'test');
    }
}
