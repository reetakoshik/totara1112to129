<?php
/*
 * This file is part of Totara LMS
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface;

defined('MOODLE_INTERNAL') || die();

class mod_facetoface_seminar_testcase extends \advanced_testcase {

    /**
     * Set up tests.
     */
    protected function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_seminar_instance() {

        $course = $this->getDataGenerator()->create_course();
        $facetoface = $this->getDataGenerator()->create_module('facetoface', array('course' => $course->id, 'name' => 'Seminar 1'));

        $seminar = new seminar($facetoface->id);

        $this->assertInstanceOf('\mod_facetoface\seminar', $seminar);

        // Test for new-empty instance.
        $this->assertNotEquals(0, $seminar->get_id());
        $this->assertEquals($facetoface->id, $seminar->get_id());
        $this->assertEquals($course->id, $seminar->get_course());
        $this->assertEquals('Seminar 1', $seminar->get_name());

        // Test update instance.
        $sameid = $seminar->get_id();
        $seminar->set_name('Seminar 1.1');
        $seminar->set_intro('Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.');
        $seminar->save();

        $this->assertEquals($sameid, $seminar->get_id());
        $this->assertEquals('Seminar 1.1', $seminar->get_name());
        $this->assertEquals(
            'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            $seminar->get_intro()
        );

        // Test delete this record.
        $seminar->delete();
        $this->assertEquals(0, $seminar->get_id());
    }

    public function test_seminar_event_instance() {

        $course = $this->getDataGenerator()->create_course();
        $facetoface = $this->getDataGenerator()->create_module('facetoface', array('course' => $course->id, 'name' => 'Seminar 1'));

        $seminar = new seminar($facetoface->id);

        $seminarevent = new seminar_event();
        // Test for new-empty instance.
        $this->assertEquals(0, $seminarevent->get_id());

        $seminarevent->set_facetoface($seminar->get_id())->save();
        $this->assertNotEquals(0, $seminarevent->get_id());

        $sameid = $seminarevent->get_id();

        // Test update instance.
        $seminarevent->set_details('Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.')
                     ->set_capacity(20)
                     ->save();

        $this->assertEquals($sameid, $seminarevent->get_id());
        $this->assertEquals(20, $seminarevent->get_capacity());
        $this->assertEquals(
            'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            $seminarevent->get_details()
        );

        // Test delete this record.
        $seminarevent->delete();
        $this->assertEquals(0, $seminarevent->get_id());

    }

    public function test_seminar_session_instance() {

        $course = $this->getDataGenerator()->create_course();
        $facetoface = $this->getDataGenerator()->create_module('facetoface', array('course' => $course->id, 'name' => 'Seminar 1'));

        $seminar = new seminar($facetoface->id);

        $seminarevent = new seminar_event();
        // Test for new-empty instance.
        $this->assertEquals(0, $seminarevent->get_id());

        $seminarevent->set_facetoface($seminar->get_id())->save();

        $seminarsession = new seminar_session();
        $this->assertEquals(0, $seminarsession->get_id());

        $now = time();
        $seminarsession->set_sessionid($seminarevent->get_id())
                       ->set_timestart($now)
                       ->set_timefinish($now + 3600)
                       ->save();
        $this->assertNotEquals(0, $seminarsession->get_id());

        $sameid = $seminarsession->get_id();
        $seminarsession->set_timestart($now + 3600)
            ->set_timefinish($now + 3600*2)
            ->save();

        $this->assertEquals($sameid, $seminarsession->get_id());
        $this->assertEquals($now + 3600, $seminarsession->get_timestart());
        $this->assertEquals($now + 3600*2, $seminarsession->get_timefinish());

        // Test delete this record.
        $seminarsession->delete();
        $this->assertEquals(0, $seminarsession->get_id());

    }

    public function test_seminar_interest_instance() {
        global $USER;

        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $facetoface = $this->getDataGenerator()->create_module('facetoface', array('course' => $course->id, 'name' => 'Seminar 1'));

        $interest = new interest();
        // Test for new-empty instance.
        $this->assertEquals(0, $interest->get_id());

        $interest->set_facetoface($facetoface->id)
            ->set_userid($USER->id)
            ->set_timedeclared($now)
            ->declare();
        $this->assertNotEquals(0, $interest->get_id());

        $sameid = $interest->get_id();
        $interest->set_timedeclared($now + 3600)
            ->set_reason('Lorem ipsum dolor sit amet')
            ->declare();

        $this->assertEquals($sameid, $interest->get_id());
        $this->assertEquals($now + 3600, $interest->get_timedeclared());
        $this->assertEquals('Lorem ipsum dolor sit amet', $interest->get_reason());

        // Test delete this record.
        $interest->withdraw();
        $this->assertEquals(0, $interest->get_id());
    }

    public function test_seminar_role_instance() {
        global $USER;

        $course = $this->getDataGenerator()->create_course();
        $facetoface = $this->getDataGenerator()->create_module('facetoface', array('course' => $course->id, 'name' => 'Seminar 1'));

        $seminarevent = new seminar_event();
        $seminarevent->set_facetoface($facetoface->id)->save();

        $seminarsession = new seminar_session();

        $now = time();
        $seminarsession->set_sessionid($seminarevent->get_id())
            ->set_timestart($now)
            ->set_timefinish($now + 3600)
            ->save();


        $role = new role();
        // Test for new-empty instance.
        $this->assertEquals(0, $role->get_id());

        $role->set_sessionid($seminarsession->get_id())
            ->set_roleid(2)
            ->set_userid($USER->id)
            ->save();
        $this->assertNotEquals(0, $role->get_id());

        $sameid = $role->get_id();
        $role->set_roleid(3)
            ->save();

        $this->assertEquals($sameid, $role->get_id());
        $this->assertEquals(3, $role->get_roleid());

        // Test delete this record.
        $role->delete();
        $this->assertEquals(0, $role->get_id());
    }

    public function test_seminar_signup_instance() {
        global $USER;

        $course = $this->getDataGenerator()->create_course();
        $facetoface = $this->getDataGenerator()->create_module('facetoface', array('course' => $course->id, 'name' => 'Seminar 1'));

        $seminarevent = new seminar_event();
        $seminarevent->set_facetoface($facetoface->id)->save();

        $seminarsession = new seminar_session();

        $now = time();
        $seminarsession->set_sessionid($seminarevent->get_id())
            ->set_timestart($now)
            ->set_timefinish($now + 3600)
            ->save();


        $signup = new signup();
        // Test for new-empty instance.
        $this->assertEquals(0, $signup->get_id());

        $signup->set_sessionid($seminarsession->get_id())
            ->set_userid($USER->id)
            ->set_discountcode(1)
            ->set_archived(1)
            ->set_bookedby(2)
            ->save();
        $this->assertNotEquals(0, $signup->get_id());

        $sameid = $signup->get_id();
        $signup->set_archived(2)
            ->set_bookedby(1)
            ->save();

        $this->assertEquals($sameid, $signup->get_id());
        $this->assertEquals(2, $signup->get_archived());
        $this->assertEquals(1, $signup->get_bookedby());

        // Test delete this record.
        $signup->delete();
        $this->assertEquals(0, $signup->get_id());
    }

    public function test_seminar_signupstatus_instance() {
        global $USER;

        $course = $this->getDataGenerator()->create_course();
        $facetoface = $this->getDataGenerator()->create_module('facetoface', array('course' => $course->id, 'name' => 'Seminar 1'));

        $seminarevent = new seminar_event();
        $seminarevent->set_facetoface($facetoface->id)->save();

        $seminarsession = new seminar_session();

        $now = time();
        $seminarsession->set_sessionid($seminarevent->get_id())
            ->set_timestart($now)
            ->set_timefinish($now + 3600)
            ->save();


        $signup = new signup();

        $signup->set_sessionid($seminarsession->get_id())
            ->set_userid($USER->id)
            ->set_discountcode(1)
            ->set_archived(1)
            ->set_bookedby(2)
            ->save();


        $status = new signup_status();
        // Test for new-empty instance.
        $this->assertEquals(0, $status->get_id());

        $status->set_signupid($signup->get_id())
            ->set_statuscode(20)
            ->set_grade(2.2)
            ->save();
        $this->assertNotEquals(0, $status->get_id());

        // Test delete this record.
        $status->delete();
        $this->assertEquals(0, $status->get_id());

    }

}
