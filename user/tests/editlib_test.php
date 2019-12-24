<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for user/editlib.php.
 *
 * @package    core_user
 * @category   phpunit
 * @copyright  2013 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/user/editlib.php');

/**
 * Unit tests for user editlib api.
 *
 * @package    core_user
 * @category   phpunit
 * @copyright  2013 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_user_editlib_testcase extends advanced_testcase {

    /**
     * Test that the required fields are returned in the correct order.
     */
    function test_useredit_get_required_name_fields() {
        global $CFG;
        // Back up config settings for restore later.
        $originalcfg = new stdClass();
        $originalcfg->fullnamedisplay = $CFG->fullnamedisplay;

        $CFG->fullnamedisplay = 'language';
        $expectedresult = array(5 => 'firstname', 21 => 'lastname');
        $this->assertEquals(useredit_get_required_name_fields(), $expectedresult);
        $CFG->fullnamedisplay = 'firstname';
        $expectedresult = array(5 => 'firstname', 21 => 'lastname');
        $this->assertEquals(useredit_get_required_name_fields(), $expectedresult);
        $CFG->fullnamedisplay = 'lastname firstname';
        $expectedresult = array('lastname', 9 => 'firstname');
        $this->assertEquals(useredit_get_required_name_fields(), $expectedresult);
        $CFG->fullnamedisplay = 'firstnamephonetic lastnamephonetic';
        $expectedresult = array(5 => 'firstname', 21 => 'lastname');
        $this->assertEquals(useredit_get_required_name_fields(), $expectedresult);

        // Tidy up after we finish testing.
        $CFG->fullnamedisplay = $originalcfg->fullnamedisplay;
    }

    /**
     * Test that the enabled fields are returned in the correct order.
     */
    function test_useredit_get_enabled_name_fields() {
        global $CFG;
        // Back up config settings for restore later.
        $originalcfg = new stdClass();
        $originalcfg->fullnamedisplay = $CFG->fullnamedisplay;

        $CFG->fullnamedisplay = 'language';
        $expectedresult = array();
        $this->assertEquals(useredit_get_enabled_name_fields(), $expectedresult);
        $CFG->fullnamedisplay = 'firstname lastname firstnamephonetic';
        $expectedresult = array(19 => 'firstnamephonetic');
        $this->assertEquals(useredit_get_enabled_name_fields(), $expectedresult);
        $CFG->fullnamedisplay = 'firstnamephonetic, lastname lastnamephonetic (alternatename)';
        $expectedresult = array('firstnamephonetic', 28 => 'lastnamephonetic', 46 => 'alternatename');
        $this->assertEquals(useredit_get_enabled_name_fields(), $expectedresult);
        $CFG->fullnamedisplay = 'firstnamephonetic lastnamephonetic alternatename middlename';
        $expectedresult = array('firstnamephonetic', 18 => 'lastnamephonetic', 35 => 'alternatename', 49 => 'middlename');
        $this->assertEquals(useredit_get_enabled_name_fields(), $expectedresult);

        // Tidy up after we finish testing.
        $CFG->fullnamedisplay = $originalcfg->fullnamedisplay;
    }

    /**
     * Test that the disabled fields are returned.
     */
    function test_useredit_get_disabled_name_fields() {
        global $CFG;
        // Back up config settings for restore later.
        $originalcfg = new stdClass();
        $originalcfg->fullnamedisplay = $CFG->fullnamedisplay;

        $CFG->fullnamedisplay = 'language';
        $expectedresult = array('firstnamephonetic' => 'firstnamephonetic', 'lastnamephonetic' => 'lastnamephonetic',
                'middlename' => 'middlename', 'alternatename' => 'alternatename');
        $this->assertEquals(useredit_get_disabled_name_fields(), $expectedresult);
        $CFG->fullnamedisplay = 'firstname lastname firstnamephonetic';
        $expectedresult = array('lastnamephonetic' => 'lastnamephonetic', 'middlename' => 'middlename', 'alternatename' => 'alternatename');
        $this->assertEquals(useredit_get_disabled_name_fields(), $expectedresult);
        $CFG->fullnamedisplay = 'firstnamephonetic, lastname lastnamephonetic (alternatename)';
        $expectedresult = array('middlename' => 'middlename');
        $this->assertEquals(useredit_get_disabled_name_fields(), $expectedresult);
        $CFG->fullnamedisplay = 'firstnamephonetic lastnamephonetic alternatename middlename';
        $expectedresult = array();
        $this->assertEquals(useredit_get_disabled_name_fields(), $expectedresult);

        // Tidy up after we finish testing.
        $CFG->fullnamedisplay = $originalcfg->fullnamedisplay;
    }

    public function test_useredit_get_return_url() {
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $deleteduser = $this->getDataGenerator()->create_user(array('deleted' => 1));
        $course = $this->getDataGenerator()->create_course();
        $site = get_site();
        $newuser = new stdClass();
        $newuser->id = -1;


        $this->setAdminUser();

        $result = useredit_get_return_url($newuser, '', $site);
        $expected = new moodle_url('/admin/user.php');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($newuser, '', null);
        $expected = new moodle_url('/admin/user.php');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($newuser, 'profile', $site);
        $expected = new moodle_url('/admin/user.php');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($newuser, 'profile', null);
        $expected = new moodle_url('/admin/user.php');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($newuser, 'preferences', $site);
        $expected = new moodle_url('/admin/user.php');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($newuser, 'preferences', null);
        $expected = new moodle_url('/admin/user.php');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($newuser, 'allusers', $site);
        $expected = new moodle_url('/admin/user.php');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($newuser, 'allusers', null);
        $expected = new moodle_url('/admin/user.php');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($user1, '', $site);
        $expected = new moodle_url('/user/preferences.php', array('userid' => $user1->id));
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($user1, '', $course);
        $expected = new moodle_url('/user/preferences.php', array('userid' => $user1->id));
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($user1, 'profile', $site);
        $expected = new moodle_url('/user/profile.php', array('id' => $user1->id));
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($user1, 'profile', $course);
        $expected = new moodle_url('/user/view.php', array('id' => $user1->id, 'course' => $course->id));
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($user1, 'preferences', $site);
        $expected = new moodle_url('/user/preferences.php', array('userid' => $user1->id));
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($user1, 'preferences', $course);
        $expected = new moodle_url('/user/preferences.php', array('userid' => $user1->id));
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($user1, 'allusers', $site);
        $expected = new moodle_url('/admin/user.php');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($deleteduser, '', $site);
        $expected = new moodle_url('/admin/user.php');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($deleteduser, '', null);
        $expected = new moodle_url('/admin/user.php');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($deleteduser, 'profile', $site);
        $expected = new moodle_url('/admin/user.php');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($deleteduser, 'profile', null);
        $expected = new moodle_url('/admin/user.php');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($deleteduser, 'preferences', $site);
        $expected = new moodle_url('/admin/user.php');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($deleteduser, 'preferences', null);
        $expected = new moodle_url('/admin/user.php');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($deleteduser, 'allusers', $site);
        $expected = new moodle_url('/admin/user.php');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($deleteduser, 'allusers', null);
        $expected = new moodle_url('/admin/user.php');
        $this->assertSame((string)$expected, (string)$result);


        $this->setUser($user2);

        $result = useredit_get_return_url($newuser, '', $site);
        $expected = new moodle_url('/');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($newuser, '', null);
        $expected = new moodle_url('/');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($newuser, 'profile', $site);
        $expected = new moodle_url('/');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($newuser, 'profile', null);
        $expected = new moodle_url('/');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($newuser, 'preferences', $site);
        $expected = new moodle_url('/');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($newuser, 'preferences', null);
        $expected = new moodle_url('/');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($newuser, 'allusers', $site);
        $expected = new moodle_url('/admin/user.php');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($newuser, 'allusers', null);
        $expected = new moodle_url('/admin/user.php');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($deleteduser, '', $site);
        $expected = new moodle_url('/');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($deleteduser, '', null);
        $expected = new moodle_url('/');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($deleteduser, 'profile', $site);
        $expected = new moodle_url('/');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($deleteduser, 'profile', null);
        $expected = new moodle_url('/');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($deleteduser, 'preferences', $site);
        $expected = new moodle_url('/');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($deleteduser, 'preferences', null);
        $expected = new moodle_url('/');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($deleteduser, 'allusers', $site);
        $expected = new moodle_url('/admin/user.php');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($deleteduser, 'allusers', null);
        $expected = new moodle_url('/admin/user.php');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($user1, '', $site);
        $expected = new moodle_url('/user/preferences.php', array('userid' => $user1->id));
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($user1, '', $course);
        $expected = new moodle_url('/user/preferences.php', array('userid' => $user1->id));
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($user1, 'profile', $site);
        $expected = new moodle_url('/user/profile.php', array('id' => $user1->id));
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($user1, 'profile', $course);
        $expected = new moodle_url('/user/view.php', array('id' => $user1->id, 'course' => $course->id));
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($user1, 'preferences', $site);
        $expected = new moodle_url('/user/preferences.php', array('userid' => $user1->id));
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($user1, 'preferences', $course);
        $expected = new moodle_url('/user/preferences.php', array('userid' => $user1->id));
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($user1, 'allusers', $site);
        $expected = new moodle_url('/admin/user.php');
        $this->assertSame((string)$expected, (string)$result);

        // Custom URL.

        $result = useredit_get_return_url($newuser, '', $site, '/grrr.php');
        $expected = new moodle_url('/grrr.php');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($newuser, 'profile', $site, '/grrr.php');
        $expected = new moodle_url('/grrr.php');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($newuser, 'allusers', $site, '/grrr.php');
        $expected = new moodle_url('/grrr.php');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($newuser, '', $site, '/grrr.php');
        $expected = new moodle_url('/grrr.php');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($deleteduser, '', $site, '/grrr.php');
        $expected = new moodle_url('/grrr.php');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($deleteduser, 'profile', $site, '/grrr.php');
        $expected = new moodle_url('/grrr.php');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($deleteduser, 'allusers', $site, '/grrr.php');
        $expected = new moodle_url('/grrr.php');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($deleteduser, '', $site, '/grrr.php');
        $expected = new moodle_url('/grrr.php');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($user1, '', $site, '/grrr.php');
        $expected = new moodle_url('/grrr.php');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($user1, 'profile', $site, '/grrr.php');
        $expected = new moodle_url('/grrr.php');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($user1, 'allusers', $site, '/grrr.php');
        $expected = new moodle_url('/grrr.php');
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($user1, '', $site, '/grrr.php');
        $expected = new moodle_url('/grrr.php');
        $this->assertSame((string)$expected, (string)$result);

    }
}
