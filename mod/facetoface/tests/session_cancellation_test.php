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
        $session = facetoface_get_session($sessionid);
        $this->assertEquals(0, $session->cancelledstatus);

        $eventsink = $this->redirectEvents();
        $result = facetoface_cancel_session($session, null);
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
        $result = facetoface_cancel_session($session, null);
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
        $session = facetoface_get_session($sessionid);
        $this->assertEquals(0, $session->cancelledstatus);

        $eventsink = $this->redirectEvents();
        $result = facetoface_cancel_session($session, null);
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

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();

        $managerja = \totara_job\job_assignment::create_default($manager->id);
        \totara_job\job_assignment::create_default($user4->id, array('managerjaid' => $managerja->id));

        $session = facetoface_get_session($sessionid);

        facetoface_user_signup($session, $facetoface, $course, '', MDL_F2F_NONE, MDL_F2F_STATUS_APPROVED, $user1->id, false);
        facetoface_cancel_attendees($sessionid, array($user1->id));
        facetoface_user_signup($session, $facetoface, $course, '', MDL_F2F_NONE, MDL_F2F_STATUS_APPROVED, $user2->id, false);
        facetoface_user_signup($session, $facetoface, $course, '', MDL_F2F_NONE, MDL_F2F_STATUS_BOOKED, $user3->id, false);
        facetoface_user_signup($session, $facetoface, $course, '', MDL_F2F_NONE, MDL_F2F_STATUS_REQUESTED, $user4->id, false);
        $attendee = facetoface_get_attendee($session->id, $user4->id);
        facetoface_update_signup_status($attendee->submissionid, MDL_F2F_STATUS_DECLINED,$user4->id);

        $sql = "SELECT ss.statuscode
                  FROM {facetoface_signups} s
                  JOIN {facetoface_signups_status} ss ON ss.signupid = s.id
                 WHERE s.sessionid = :sid AND ss.superceded = 0 AND s.userid = :uid";

        $this->assertEquals(MDL_F2F_STATUS_USER_CANCELLED, $DB->get_field_sql($sql, array('sid' => $session->id, 'uid' => $user1->id)));
        $this->assertEquals(MDL_F2F_STATUS_APPROVED, $DB->get_field_sql($sql, array('sid' => $session->id, 'uid' => $user2->id)));
        $this->assertEquals(MDL_F2F_STATUS_BOOKED, $DB->get_field_sql($sql, array('sid' => $session->id, 'uid' => $user3->id)));
        $this->assertEquals(MDL_F2F_STATUS_DECLINED, $DB->get_field_sql($sql, array('sid' => $session->id, 'uid' => $user4->id)));

        $result = facetoface_cancel_session($session, null);
        $this->assertTrue($result);

        // Users that have cancelled their session or their request have been declined should not being affected when a
        // session is cancelled.
        $this->assertEquals(MDL_F2F_STATUS_USER_CANCELLED, $DB->get_field_sql($sql, array('sid' => $session->id, 'uid' => $user1->id)));
        $this->assertEquals(MDL_F2F_STATUS_SESSION_CANCELLED, $DB->get_field_sql($sql, array('sid' => $session->id, 'uid' => $user2->id)));
        $this->assertEquals(MDL_F2F_STATUS_SESSION_CANCELLED, $DB->get_field_sql($sql, array('sid' => $session->id, 'uid' => $user3->id)));
        $this->assertEquals(MDL_F2F_STATUS_DECLINED, $DB->get_field_sql($sql, array('sid' => $session->id, 'uid' => $user4->id)));

        $newsession = $DB->get_record('facetoface_sessions', array('id' => $sessionid));
        $this->assertEquals(1, $newsession->cancelledstatus);
    }
}
