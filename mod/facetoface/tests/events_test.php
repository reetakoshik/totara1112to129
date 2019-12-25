<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package facetoface
 */

defined('MOODLE_INTERNAL') || die();

class facetoface_events_testcase extends advanced_testcase {

    protected $facetofacegenerator = null;
    protected $facetoface = null;
    protected $course = null;
    protected $context = null;
    protected $session = null;


    protected function tearDown() {
        $this->facetofacegenerator = null;
        $this->facetoface = null;
        $this->course = null;
        $this->context = null;
        $this->session = null;
        parent::tearDown();
    }

    public function setUp() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $this->course = $this->getDataGenerator()->create_course();
        $this->facetoface = $this->getDataGenerator()->create_module('facetoface', array('course' => $this->course->id));
        $sid = $this->facetofacegenerator->add_session(array('facetoface' => $this->facetoface->id, 'sessiondates' => array()));
        $this->session = $DB->get_record('facetoface_sessions', array('id' => $sid));
        $this->context = context_module::instance($this->facetoface->cmid);
    }

    public function test_session_created_event() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $event = \mod_facetoface\event\session_created::create_from_session($this->session, $this->context);
        $event->trigger();

        $this->assertEquals($this->context, $event->get_context());
        $this->assertSame($this->session, $event->get_session());
        $this->assertSame($this->session->id, $event->objectid);
        $this->assertSame('c', $event->crud);
        $this->assertEventContextNotUsed($event);
        $this->assertEventLegacyLogData(array($this->course->id, 'facetoface', 'added session',
            "events/add.php?s={$this->session->id}", $this->session->id, $this->facetoface->cmid), $event);
    }

    public function test_session_updated_event() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $event = \mod_facetoface\event\session_updated::create_from_session($this->session, $this->context);
        $event->trigger();

        // Check that the event contains the expected values.
        $this->assertEquals($this->context, $event->get_context());
        $this->assertSame($this->session, $event->get_session());
        $this->assertSame($this->session->id, $event->objectid);
        $this->assertSame('u', $event->crud);
        $this->assertEventContextNotUsed($event);
        $this->assertEventLegacyLogData(array($this->course->id, 'facetoface', 'updated session',
            "events/edit.php?s={$this->session->id}", $this->session->id, $this->facetoface->cmid), $event);
    }

    public function test_session_deleted_event() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $event = \mod_facetoface\event\session_deleted::create_from_session($this->session, $this->context);
        $event->trigger();

        // Check that the event contains the expected values.
        $this->assertEquals($this->context, $event->get_context());
        $this->assertEquals($this->session, $event->get_record_snapshot('facetoface_sessions', $this->session->id));
        $this->assertSame($this->session->id, $event->objectid);
        $this->assertSame('d', $event->crud);
        $this->assertEventContextNotUsed($event);
        $this->assertEventLegacyLogData(array($this->course->id, 'facetoface', 'delete session',
            "events/delete.php?s={$this->session->id}", $this->session->id, $this->facetoface->cmid), $event);
    }

    public function test_booking_cancelled_event() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $event = \mod_facetoface\event\booking_cancelled::create_from_session($this->session, $this->context);
        $event->trigger();

        $this->assertEquals($this->context, $event->get_context());
        $this->assertSame('u', $event->crud);
        $this->assertEventContextNotUsed($event);
        $this->assertEventLegacyLogData(array($this->course->id, 'facetoface', 'cancel booking',
            "cancelsignup.php?s={$this->session->id}", $this->session->id, $this->facetoface->cmid), $event);
    }

    public function test_signup_status_updated_event() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create user.
        $user1 = $this->getDataGenerator()->create_user();

        // Update session.
        $this->session->sessiondates = array();

        // Sign user up.
        facetoface_user_signup($this->session, $this->facetoface, $this->course, '', MDL_F2F_NONE, MDL_F2F_STATUS_BOOKED, $user1->id, true, '');
        $signup = $DB->get_record('facetoface_signups', array('sessionid' => $this->session->id, 'userid' => $user1->id));
        $signupstatus = $DB->get_record('facetoface_signups_status', array('signupid' => $signup->id, 'superceded' => 0));

        $event = \mod_facetoface\event\signup_status_updated::create_from_signup($signupstatus, $this->context, $signup);
        $event->trigger();
        $data = $event->get_data();

        $this->assertEquals($this->context, $event->get_context());
        $this->assertSame($signupstatus, $event->get_signupstatus());
        $this->assertSame($signupstatus->id, $event->objectid);
        $this->assertSame('u', $data['crud']);
        $this->assertEventContextNotUsed($event);
    }

    public function test_attendee_note_updated_event() {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();
        $user1 = $this->getDataGenerator()->create_user();

        // Update session.
        $this->session->sessiondates = array();

        facetoface_user_signup($this->session, $this->facetoface, $this->course, '', MDL_F2F_NONE, MDL_F2F_STATUS_BOOKED, $user1->id);
        $attendee_note = facetoface_get_attendee($this->session->id, $user1->id);
        $attendee_note->userid = $attendee_note->id;
        $attendee_note->id = $attendee_note->submissionid;
        $attendee_note->sessionid = $this->session->id;

        $event = \mod_facetoface\event\attendee_note_updated::create_from_instance($attendee_note, $this->context);
        $event->trigger();
        $data = $event->get_data();

        $this->assertEquals($this->context, $event->get_context());
        $this->assertSame($attendee_note, $event->get_instance());
        $this->assertSame($attendee_note->id, $event->objectid);
        $this->assertSame('u', $data['crud']);
        $this->assertEventContextNotUsed($event);
        $this->assertEventLegacyLogData(array($this->course->id, 'facetoface', 'update attendee note',
            "attendee_note.php?id={$user1->id}&s={$this->session->id}", $this->session->id, $this->facetoface->cmid), $event);
    }

    public function test_booking_requests_approved_event() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $users = array();
        $users[$user1->id] = $user1->id;
        $users[$user2->id] = $user2->id;

        $data = array('sessionid' => $this->session->id, 'userids' => array($users));
        $event = \mod_facetoface\event\booking_requests_approved::create_from_data($data, $this->context);
        $event->trigger();

        $this->assertSame($event::LEVEL_TEACHING, $event->edulevel);
        $this->assertSame('u', $event->crud);
        $this->assertEventContextNotUsed($event);
        $this->assertEventLegacyLogData(array($this->course->id, 'facetoface', 'approve requests',
            "attendance.php?s={$this->session->id}", $this->session->id, $this->facetoface->cmid), $event);
    }

    public function test_booking_requests_rejected_event() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $users = array();
        $users[$user1->id] = $user1->id;
        $users[$user2->id] = $user2->id;

        $data = array('sessionid' => $this->session->id, 'userids' => array($users));
        $event = \mod_facetoface\event\booking_requests_rejected::create_from_data($data, $this->context);
        $event->trigger();
        $data = $event->get_data();

        $this->assertSame($event::LEVEL_TEACHING, $event->edulevel);
        $this->assertSame('u', $data['crud']);
        $this->assertEventContextNotUsed($event);
    }

    public function test_attendance_updated_event() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $event = \mod_facetoface\event\attendance_updated::create_from_session($this->session, $this->context);
        $event->trigger();

        $this->assertSame($event::LEVEL_TEACHING, $event->edulevel);
        $this->assertSame('u', $event->crud);
        $this->assertEventContextNotUsed($event);
        $this->assertEventLegacyLogData(array($this->course->id, 'facetoface', 'take attendance',
            "view.php?id={$this->facetoface->cmid}", $this->session->id, $this->facetoface->cmid), $event);
    }

    public function test_attendees_viewed_event() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $event = \mod_facetoface\event\attendees_viewed::create_from_session($this->session, $this->context, 'cancellations');
        $event->trigger();

        $this->assertSame('r', $event->crud);
        $this->assertEventContextNotUsed($event);
        $this->assertEventLegacyLogData(array($this->course->id, 'facetoface', 'view attendance',
            "view.php?id={$this->facetoface->cmid}", $this->session->id, $this->facetoface->cmid), $event);
    }

    public function test_attendees_updated_event() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $event = \mod_facetoface\event\attendees_updated::create_from_session($this->session, $this->context);
        $event->trigger();
        $data = $event->get_data();

        $this->assertSame($event::LEVEL_TEACHING, $event->edulevel);
        $this->assertSame('u', $data['crud']);
        $this->assertEventContextNotUsed($event);
        $this->assertEventLegacyLogData(array($this->course->id, 'facetoface', 'Add/remove attendees',
            "attendees.php?s={$this->session->id}", $this->session->id, $this->facetoface->cmid), $event);
    }

    public function test_attendee_position_updated() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $user1 = $this->getDataGenerator()->create_user();

        // Sign-up user1 in the facetoface.
        $usersignup = new stdClass();
        $usersignup->sessionid = $this->session->id;
        $usersignup->userid = $user1->id;
        $usersignup->jobassignmentid = null;
        $usersignup->bookedby = $user1->id;
        $usersignup->mailedreminder = 0;
        $usersignup->notificationtype = 1;
        $usersignup->id = $DB->insert_record('facetoface_signups', $usersignup);

        $event = \mod_facetoface\event\attendee_job_assignment_updated::create(
            array(
                'objectid' => $usersignup->id,
                'context' => $this->context,
                'other' => array(
                    'sessionid'  => $this->session->id,
                    'attendeeid' => $usersignup->userid,
                )
            )
        );
        $event->trigger();
        $data = $event->get_data();
        $this->assertSame($usersignup->id, $event->objectid);
        $this->assertEquals('facetoface_signups', $event->objecttable);
        $this->assertSame('u', $data['crud']);
    }

    public function test_interest_declared() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $interestid = facetoface_declare_interest($this->facetoface, 'my reason');
        $interestobj = $DB->get_record('facetoface_interest', array('id' => $interestid));

        $event = \mod_facetoface\event\interest_declared::create_from_instance($interestobj, $this->context);
        $event->trigger();
        $data = $event->get_data();

        $this->assertSame($interestobj, $event->get_instance());
        $this->assertEquals('facetoface_interest', $event->objecttable);
        $this->assertSame($event::LEVEL_PARTICIPATING, $event->edulevel);
        $this->assertSame($interestobj->id, $event->objectid);
        $this->assertSame('c', $data['crud']);
    }

    public function test_interest_withdrawn() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $interestid = facetoface_declare_interest($this->facetoface, 'my reason');
        $interestobj = $DB->get_record('facetoface_interest', array('id' => $interestid));

        $event = \mod_facetoface\event\interest_withdrawn::create_from_instance($interestobj, $this->context);
        $event->trigger();
        $data = $event->get_data();

        $this->assertSame($interestobj, $event->get_instance());
        $this->assertEquals('facetoface_interest', $event->objecttable);
        $this->assertSame($event::LEVEL_PARTICIPATING, $event->edulevel);
        $this->assertSame($interestobj->id, $event->objectid);
        $this->assertSame('d', $data['crud']);

    }

    public function test_interest_report_viewed() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $event = \mod_facetoface\event\interest_report_viewed::create_from_facetoface($this->facetoface, $this->context);
        $event->trigger();
        $data = $event->get_data();

        $this->assertSame('r', $data['crud']);
    }
}
