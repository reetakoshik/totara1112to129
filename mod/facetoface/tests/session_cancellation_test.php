<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author  Petr Skoda <petr.skoda@totaralms.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

use \mod_facetoface\seminar_event;
use \mod_facetoface\signup;
use \mod_facetoface\signup_helper;
use \mod_facetoface\signup\state\{declined, user_cancelled};

class mod_facetoface_session_cancellation_testcase extends advanced_testcase {
    public function test_facetoface_cancel_session_basic() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/facetoface/lib.php');

        $this->resetAfterTest();
        $this->setAdminUser();

        /** @var mod_facetoface_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $course = $this->getDataGenerator()->create_course();

        $facetoface = $generator->create_instance(array('course' => $course->id));
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + DAYSECS;
        $sessiondate->timefinish = $sessiondate->timestart + (DAYSECS * 2);
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $sessionid = $generator->add_session(array('facetoface' => $facetoface->id, 'sessiondates' => array($sessiondate)));
        $seminarevent = new seminar_event($sessionid);
        $this->assertEquals(0, $seminarevent->get_cancelledstatus());

        $eventsink = $this->redirectEvents();
        $result = $seminarevent->cancel();
        $this->assertTrue($result);
        $events = $eventsink->get_events();
        $eventsink->close();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf('mod_facetoface\event\session_cancelled', $event);
        $newsession = $DB->get_record('facetoface_sessions', array('id' => $sessionid));
        $this->assertEquals(1, $newsession->cancelledstatus);

        // Second call should do nothing.
        $session = facetoface_get_session($sessionid);
        $result = $seminarevent->cancel();
        $this->assertFalse($result);
        $newsession = $DB->get_record('facetoface_sessions', array('id' => $sessionid));
        $this->assertEquals(1, $newsession->cancelledstatus);
    }

    public function test_facetoface_cancel_session_started() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/facetoface/lib.php');

        $this->resetAfterTest();
        $this->setAdminUser();

        /** @var mod_facetoface_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $course = $this->getDataGenerator()->create_course();

        $facetoface = $generator->create_instance(array('course' => $course->id));
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() - DAYSECS;
        $sessiondate->timefinish = $sessiondate->timestart + (DAYSECS * 2);
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $sessionid = $generator->add_session(array('facetoface' => $facetoface->id, 'sessiondates' => array($sessiondate)));
        $seminarevent = new seminar_event($sessionid);
        $this->assertEquals(0, $seminarevent->get_cancelledstatus());

        $eventsink = $this->redirectEvents();
        $result = $seminarevent->cancel();
        $this->assertFalse($result);
        $events = $eventsink->get_events();
        $eventsink->close();
        $this->assertCount(0, $events);
        $newsession = $DB->get_record('facetoface_sessions', array('id' => $sessionid));
        $this->assertEquals(0, $newsession->cancelledstatus);
    }

    public function test_facetoface_cancel_session_status() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/facetoface/lib.php');

        $this->resetAfterTest();
        $this->setAdminUser();

        /** @var mod_facetoface_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $course = $this->getDataGenerator()->create_course();

        $facetoface = $generator->create_instance(array('course' => $course->id, 'approvaltype' => 0));

        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + DAYSECS;
        $sessiondate->timefinish = $sessiondate->timestart + (DAYSECS * 2);
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $sessionid = $generator->add_session(array('facetoface' => $facetoface->id, 'sessiondates' => array($sessiondate)));
        $seminarevent1 = new seminar_event($sessionid);

        $facetoface2 = $generator->create_instance(array('course' => $course->id, 'approvaltype' => \mod_facetoface\seminar::APPROVAL_ADMIN));
        $session2id = $generator->add_session(array('facetoface' => $facetoface2->id, 'sessiondates' => array($sessiondate)));
        $seminarevent2 = new seminar_event($session2id);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course->id);
        $this->getDataGenerator()->enrol_user($user4->id, $course->id);

        $managerja = \totara_job\job_assignment::create_default($manager->id);
        \totara_job\job_assignment::create_default($user4->id, array('managerjaid' => $managerja->id));
        \totara_job\job_assignment::create_default($user2->id, array('managerjaid' => $managerja->id));

        $managerja = \totara_job\job_assignment::create_default($manager->id);
        \totara_job\job_assignment::create_default($user2->id, array('managerjaid' => $managerja->id));

        $signup11 = signup_helper::signup(signup::create($user1->id, $seminarevent1));
        $this->assertTrue($signup11->can_switch(user_cancelled::class));
        signup_helper::user_cancel($signup11);

        $signup31 = signup_helper::signup(signup::create($user3->id, $seminarevent1));
        $signup22 = signup_helper::signup(signup::create($user2->id, $seminarevent2));
        $signup42 = signup_helper::signup(signup::create($user4->id, $seminarevent2));

        $this->assertTrue($signup42->can_switch(declined::class));
        $signup42->switch_state(declined::class);

        $sql = "SELECT ss.statuscode
                  FROM {facetoface_signups} s
                  JOIN {facetoface_signups_status} ss ON ss.signupid = s.id
                 WHERE s.sessionid = :sid AND ss.superceded = 0 AND s.userid = :uid";

        $this->assertEquals(\mod_facetoface\signup\state\user_cancelled::get_code(), $DB->get_field_sql($sql, array('sid' => $sessionid, 'uid' => $user1->id)));
        $this->assertEquals(\mod_facetoface\signup\state\requested::get_code(), $DB->get_field_sql($sql, array('sid' => $session2id, 'uid' => $user2->id)));
        $this->assertEquals(\mod_facetoface\signup\state\booked::get_code(), $DB->get_field_sql($sql, array('sid' => $sessionid, 'uid' => $user3->id)));
        $this->assertEquals(\mod_facetoface\signup\state\declined::get_code(), $DB->get_field_sql($sql, array('sid' => $session2id, 'uid' => $user4->id)));

        $result = $seminarevent1->cancel();
        $result2 = $seminarevent2->cancel();
        $this->assertTrue($result);
        $this->assertTrue($result2);

        // Users that have cancelled their session or their request have been declined should not being affected when a
        // session is cancelled.
        $this->assertEquals(\mod_facetoface\signup\state\user_cancelled::get_code(), $DB->get_field_sql($sql, array('sid' => $sessionid, 'uid' => $user1->id)));
        $this->assertEquals(\mod_facetoface\signup\state\event_cancelled::get_code(), $DB->get_field_sql($sql, array('sid' => $session2id, 'uid' => $user2->id)));
        $this->assertEquals(\mod_facetoface\signup\state\event_cancelled::get_code(), $DB->get_field_sql($sql, array('sid' => $sessionid, 'uid' => $user3->id)));
        $this->assertEquals(\mod_facetoface\signup\state\declined::get_code(), $DB->get_field_sql($sql, array('sid' => $session2id, 'uid' => $user4->id)));

        $newsession = $DB->get_record('facetoface_sessions', array('id' => $sessionid));
        $this->assertEquals(1, $newsession->cancelledstatus);
    }
}
