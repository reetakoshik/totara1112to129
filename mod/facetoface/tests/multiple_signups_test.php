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
 * @author David Curry <david.curry@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface;

defined('MOODLE_INTERNAL') || die();

use mod_facetoface\seminar;
use mod_facetoface\seminar_event;
use mod_facetoface\seminar_session;

use mod_facetoface\signup;
use mod_facetoface\signup_list;
use mod_facetoface\signup_helper;
use mod_facetoface\signup\state\{not_set, waitlisted, booked, no_show, partially_attended, fully_attended};

class mod_facetoface_multiple_signups_testcase extends \advanced_testcase {

    /**
     * Set up tests.
     */
    protected function setUp() {
        global $DB;

        parent::setUp();
        $this->resetAfterTest(true);
        $this->preventResetByRollback();
    }

    /**
     * Create a basic user, course, seminar, event, and session.
     * @return [users[], course, seminar1, $seminarevents1[], $seminar2, $seminarevents2[]]
     */
    public function basic_seminar_setup() : array {
        $now = time();
        $generator = $this->getDataGenerator();
        $f2fgenerator = $generator->get_plugin_generator("mod_facetoface");

        // Create a course.
        $course = $generator->create_course();

        // Create and enrol some users.
        $users = [];
        for ($i = 0; $i < 8; $i++) {
            $users[$i] = $generator->create_user();
            $this->getDataGenerator()->enrol_user($users[$i]->id, $course->id);
        }

        // Create a basic seminars.
        $f2f1 = $f2fgenerator->create_instance(['course' => $course->id]);
        $seminar1 = new seminar($f2f1->id);

        // Create 4 events per seminar, spaced 1 per hour.
        $s1events = [];
        $timebase = $now + DAYSECS;
        for ($i = 0; $i < 4; $i++) {
            $seminarevent = new seminar_event();
            $seminarevent->set_facetoface($seminar1->get_id());
            $seminarevent->save();

            $timestart = $timebase + ($i * HOURSECS);
            $seminarsession = new seminar_session();
            $seminarsession->set_sessionid($seminarevent->get_id());
            $seminarsession->set_timestart($timestart);
            $seminarsession->set_timefinish($timestart + HOURSECS - 60);
            $seminarsession->save();

            $s1events[$i] = $seminarevent;
        }

        // Create a second seminar to be used as control.
        $f2f2 = $f2fgenerator->create_instance(['course' => $course->id]);
        $seminar2 = new seminar($f2f2->id);

        // Create 4 events per seminar, spaced 1 per hour.
        $s2events = [];
        $timebase = $now + (2 * DAYSECS);
        for ($i = 0; $i < 4; $i++) {
            $seminarevent = new seminar_event();
            $seminarevent->set_facetoface($seminar2->get_id());
            $seminarevent->save();

            $timestart = $timebase + ($i * HOURSECS);
            $seminarsession = new seminar_session();
            $seminarsession->set_sessionid($seminarevent->get_id());
            $seminarsession->set_timestart($timestart);
            $seminarsession->set_timefinish($timestart + HOURSECS - 60);
            $seminarsession->save();

            $s2events[$i] = $seminarevent;
        }

        return [$users, $course, $seminar1, $s1events, $seminar2, $s2events];
    }

    /**
     * Test the waitlist auto cleaner.
     */
    public function test_waitlist_autoclean() {
        global $DB;

        $emailsink = $this->redirectEmails();
        $this->assertTrue(\phpunit_util::is_redirecting_phpmailer());

        list($users, $course, $seminar1, $s1events, $seminar2, $s2events) = $this->basic_seminar_setup();

        // Make sure waitlist autoclean is on for the first seminar.
        $seminar1->set_waitlistautoclean(true);
        $seminar1->save();

        $seminarevent11 = array_pop($s1events);
        $seminarevent11->set_waitlisteveryone(1); // Enable waitlist.
        $seminarevent11->set_allowoverbook(1); // Enable waitlist.
        $seminarevent11->set_capacity(2);
        $seminarevent11->save();

        // Assign users - Booked 2, Waitlisted 2
        for ($i = 0; $i < 4; $i++) {
            $user = $users[$i];
            $signup = signup::create($user->id, $seminarevent11);

            $this->assertTrue(signup_helper::can_signup($signup), "User({$i}) failed can signup checks");
            signup_helper::signup($signup);
        }

        $seminarevent12 = array_pop($s1events);
        $seminarevent12->set_waitlisteveryone(1); // Enable waitlist.
        $seminarevent12->set_allowoverbook(1); // Enable waitlist.
        $seminarevent12->set_capacity(1);

        // Assign remaining users - Booked 1, Waitlisted 1
        for ($i = 4; $i < 6; $i++) {
            $user = $users[$i];

            $signup = signup::create($user->id, $seminarevent12);
            signup_helper::signup($signup);
        }

        // Make sure waitlist autoclean is off for the second seminar.
        $seminar2->set_waitlistautoclean(false);
        $seminar2->save();

        $seminarevent21 = array_pop($s2events);
        $seminarevent21->set_waitlisteveryone(1); // Enable waitlist.
        $seminarevent21->set_allowoverbook(1); // Enable waitlist.
        $seminarevent21->set_capacity(3);

        // Assign users - Booked 3, Waitlisted 5
        foreach ($users as $user) {
            $signup = signup::create($user->id, $seminarevent21);
            signup_helper::signup($signup);
        }

        // Move event11 and event21 into the past.
        $now = time();
        $date11 = $DB->get_record('facetoface_sessions_dates', ['sessionid' => $seminarevent11->get_id()]);
        $date11->timestart = $now - DAYSECS;
        $date11->timefinish = $now - DAYSECS + HOURSECS;
        $DB->update_record('facetoface_sessions_dates', $date11);
        $this->assertTrue($seminarevent11->is_started());

        $date21 = $DB->get_record('facetoface_sessions_dates', ['sessionid' => $seminarevent21->get_id()]);
        $date21->timestart = $now - DAYSECS;
        $date21->timefinish = $now - DAYSECS + HOURSECS;
        $DB->update_record('facetoface_sessions_dates', $date21);
        $this->assertTrue($seminarevent21->is_started());

        // Do some pre-task checks.
        $this->assertEquals(2, signup_list::signups_by_statuscode_for_event($seminarevent11->get_id(), waitlisted::get_code())->count());
        $this->assertEquals(2, signup_list::signups_by_statuscode_for_event($seminarevent11->get_id(), booked::get_code())->count());
        $this->assertEquals(1, signup_list::signups_by_statuscode_for_event($seminarevent12->get_id(), waitlisted::get_code())->count());
        $this->assertEquals(1, signup_list::signups_by_statuscode_for_event($seminarevent12->get_id(), booked::get_code())->count());
        $this->assertEquals(5, signup_list::signups_by_statuscode_for_event($seminarevent21->get_id(), waitlisted::get_code())->count());
        $this->assertEquals(3, signup_list::signups_by_statuscode_for_event($seminarevent21->get_id(), booked::get_code())->count());

        $this->execute_adhoc_tasks(); // Makes sure the emails have sent.
        $emailsink->clear();

        // Execute the waitlist autoclean task.
        $task = new \mod_facetoface\task\waitlist_autoclean_task();
        $task->execute();

        $this->execute_adhoc_tasks(); // Makes sure the emails have sent.

        // Check the auto clean messages.
        $emails = $emailsink->get_messages();
        $this->assertCount(2, $emails);

        // Check subject line to make sure these are auto-cancellation emails.
        foreach ($emails as $email) {
            $this->assertEquals('Waitlisted signup expired', $email->subject);
        }

        // Make sure event11 gets waitlisted signups cleaned, but booked ones are left alone.
        $this->assertEquals(0, signup_list::signups_by_statuscode_for_event($seminarevent11->get_id(), waitlisted::get_code())->count());
        $this->assertEquals(2, signup_list::signups_by_statuscode_for_event($seminarevent11->get_id(), booked::get_code())->count());

        // Make sure event 12 does not get cleaned, it's still in the future.
        $this->assertEquals(1, signup_list::signups_by_statuscode_for_event($seminarevent12->get_id(), waitlisted::get_code())->count());
        $this->assertEquals(1, signup_list::signups_by_statuscode_for_event($seminarevent12->get_id(), booked::get_code())->count());

        // Make sure event 21 does not get cleaned, waitlist autoclean is disabled.
        $this->assertEquals(5, signup_list::signups_by_statuscode_for_event($seminarevent21->get_id(), waitlisted::get_code())->count());
        $this->assertEquals(3, signup_list::signups_by_statuscode_for_event($seminarevent21->get_id(), booked::get_code())->count());

        $emailsink->close();
    }

    /**
     * Test the unrestricted multiple signup setting.
     */
    public function test_multisignup_unrestricted() {
        list($users, $course, $seminar1, $s1events, $seminar2, $s2events) = $this->basic_seminar_setup();

        // Make sure the first seminar has multiple signups enabled.
        $seminar1->set_multiplesessions(1); // Note: Multiple sign ups enabled.
        $seminar1->set_multisignupmaximum(0);
        $seminar1->save();

        // Check the settings are what we are expecting.
        $this->assertEquals(1, $seminar1->get_multiplesessions());
        $this->assertEquals(0, $seminar1->get_multisignup_maximum());
        $this->assertEquals([], $seminar1->get_multisignup_states());

        // Check that a user has unrestricted access to the events.
        $user1 = array_pop($users);
        foreach ($s1events as $seminarevent) {
            $signup = signup::create($user1->id, $seminarevent);
            $this->assertInstanceOf(not_set::class, $signup->get_state());
            $this->assertTrue(signup_helper::can_signup($signup));

            signup_helper::signup($signup);
        }

        // Now test with the setting off.
        $seminar1->set_multiplesessions(0); // Note: Multiple sign ups disabled.
        $seminar1->save();

        // Check the settings are what we are expecting.
        $this->assertEquals(0, $seminar1->get_multiplesessions());
        $this->assertEquals(0, $seminar1->get_multisignup_maximum());
        $this->assertEquals([], $seminar1->get_multisignup_states());

        // Check that a different user can now only sign up to one event.
        $first = true;
        $user2 = array_pop($users);
        foreach ($s1events as $seminarevent) {
            if ($first) {
                $signup = signup::create($user2->id, $seminarevent);
                $this->assertInstanceOf(not_set::class, $signup->get_state());
                $this->assertTrue(signup_helper::can_signup($signup));

                signup_helper::signup($signup);
                $first = false;
            } else {
                $signup = signup::create($user2->id, $seminarevent);
                $this->assertInstanceOf(not_set::class, $signup->get_state());
                $this->assertFalse(signup_helper::can_signup($signup));
            }
        }
    }

    /**
     * Test the setting for state restrictions on subsequent signups.
     */
    public function test_multisignup_restrictions() {
        global $DB;

        list($users, $course, $seminar1, $s1events, $seminar2, $s2events) = $this->basic_seminar_setup();

        // Make sure that the first seminar has multiple signups enabled but restricted to noshows.
        $seminar1->set_multiplesessions(1); // Note: Multiple sign ups enabled.
        $seminar1->set_multisignupnoshow(true);
        $seminar1->set_multisignuppartly(false);
        $seminar1->set_multisignupfully(false);
        $seminar1->save();

        // Check the settings are what we are expecting.
        $this->assertEquals(1, $seminar1->get_multiplesessions());
        $this->assertEquals(0, $seminar1->get_multisignup_maximum());
        $this->assertEquals([no_show::get_code() => no_show::class], $seminar1->get_multisignup_states());

        // Sign all the users up to the event so we can move them into testable states.
        $seminarevent = array_pop($s1events);
        $seminarevent->set_waitlisteveryone(1); // Enable waitlist.
        $seminarevent->set_allowoverbook(1); // Enable waitlist.
        $seminarevent->set_capacity(7);
        $seminarevent->save();

        foreach ($users as $user) {
            $signup = signup::create($user->id, $seminarevent);
            $this->assertInstanceOf(not_set::class, $signup->get_state());
            $this->assertTrue(signup_helper::can_signup($signup));

            signup_helper::signup($signup);
        }

        // Move the event back in time so we can transition users to the necessary states.
        $now = time();
        $eventsession = $DB->get_record('facetoface_sessions_dates', ['sessionid' => $seminarevent->get_id()]);
        $eventsession->timestart = $now - DAYSECS;
        $eventsession->timefinish = $now - DAYSECS + HOURSECS;
        $DB->update_record('facetoface_sessions_dates', $eventsession);
        $this->assertTrue($seminarevent->is_started());

        $fully = [];
        $partly = [];
        $noshow = [];
        $booked = [];
        $waitlst = [];
        $signuplist = signup_list::signups_for_event($seminarevent->get_id());
        foreach ($signuplist as $signup) {
            // Keep the existing waitlisted signup as is.
            if ($signup->get_state() instanceof waitlisted) {
                $waitlst[] = $signup->get_userid();
                continue;
            }

            if (count($noshow) < 2) {
                $signup->switch_state(no_show::class);
                $signup->save();

                $noshow[] = $signup->get_userid();
                continue;
            }

            if (count($partly) < 2) {
                $signup->switch_state(partially_attended::class);
                $signup->save();

                $partly[] = $signup->get_userid();
                continue;
            }

            if (count($fully) < 2) {
                $signup->switch_state(fully_attended::class);
                $signup->save();

                $fully[] = $signup->get_userid();
                continue;
            }

            // Finally keep the last one in the booked state.
            $booked[] = $signup->get_userid();
        }

        // Double check the signups are all in the expected states now.
        $this->assertEquals(1, signup_list::signups_by_statuscode_for_event($seminarevent->get_id(), booked::get_code())->count());
        $this->assertEquals(1, signup_list::signups_by_statuscode_for_event($seminarevent->get_id(), waitlisted::get_code())->count());
        $this->assertEquals(2, signup_list::signups_by_statuscode_for_event($seminarevent->get_id(), no_show::get_code())->count());
        $this->assertEquals(2, signup_list::signups_by_statuscode_for_event($seminarevent->get_id(), partially_attended::get_code())->count());
        $this->assertEquals(2, signup_list::signups_by_statuscode_for_event($seminarevent->get_id(), fully_attended::get_code())->count());

        // Get a second event in the same seminar.
        $seminarevent2 = array_pop($s1events);

        // The fully attended user should not be able to signup a second time.
        $ufully = array_pop($fully);
        $sfully = signup::create($ufully, $seminarevent2);
        $this->assertFalse(signup_helper::can_signup($sfully));

        // The fully attended user should not be able to signup a second time.
        $upartly = array_pop($partly);
        $spartly = signup::create($upartly, $seminarevent2);
        $this->assertFalse(signup_helper::can_signup($spartly));

        // The No show user should be able to signup a second time.
        $unoshow = array_pop($noshow);
        $snoshow = signup::create($unoshow, $seminarevent2);
        $this->assertTrue(signup_helper::can_signup($snoshow));
        signup_helper::signup($snoshow); // Complete the sign-up to make sure it all works.
        $this->assertInstanceOf(booked::class, $snoshow->get_state());

        // The fully attended user should not be able to signup a second time.
        $ubooked = array_pop($booked);
        $sbooked = signup::create($ubooked, $seminarevent2);
        $this->assertFalse(signup_helper::can_signup($sbooked));

        // The fully attended user should not be able to signup a second time.
        $uwaitlst = array_pop($waitlst);
        $swaitlst = signup::create($uwaitlst, $seminarevent2);
        $this->assertFalse(signup_helper::can_signup($swaitlst));

        // Change the state restriction of the seminar to partially attended.
        $seminar1->set_multisignupnoshow(false);
        $seminar1->set_multisignuppartly(true);
        $seminar1->set_multisignupfully(false);
        $seminar1->save();

        // Get a new user in the noshow state, and recheck the states which can signup.
        $unoshow = array_pop($noshow);
        $snoshow = signup::create($unoshow, $seminarevent2);
        $this->assertFalse(signup_helper::can_signup($sfully));
        $this->assertFalse(signup_helper::can_signup($snoshow));
        $this->assertFalse(signup_helper::can_signup($swaitlst));
        $this->assertFalse(signup_helper::can_signup($sbooked));
        $this->assertTrue(signup_helper::can_signup($spartly));
        signup_helper::signup($spartly); // Complete the sign-up to make sure it all works.
        $this->assertInstanceOf(booked::class, $spartly->get_state());

        // Change the state restriction of the seminar to fully attended.
        $seminar1->set_multisignupnoshow(false);
        $seminar1->set_multisignuppartly(false);
        $seminar1->set_multisignupfully(true);
        $seminar1->save();

        // Get a new user in the partially attended state, and recheck the states which can signup.
        $upartly = array_pop($partly);
        $spartly = signup::create($upartly, $seminarevent2);
        $this->assertFalse(signup_helper::can_signup($spartly));
        $this->assertFalse(signup_helper::can_signup($snoshow));
        $this->assertFalse(signup_helper::can_signup($swaitlst));
        $this->assertFalse(signup_helper::can_signup($sbooked));
        $this->assertTrue(signup_helper::can_signup($sfully));
        signup_helper::signup($sfully); // Complete the sign-up to make sure it all works.
        $this->assertInstanceOf(booked::class, $sfully->get_state());

        // Change the state restriction of the seminar to any of the states, and recheck the booked and waitlisted still can't resignup.
        $seminar1->set_multisignupnoshow(true);
        $seminar1->set_multisignuppartly(true);
        $seminar1->set_multisignupfully(true);
        $seminar1->save();

        // Get a new user in the partially attended state, and recheck the states which can signup.
        $ufully = array_pop($fully);
        $sfully = signup::create($ufully, $seminarevent2);
        $this->assertTrue(signup_helper::can_signup($spartly));
        $this->assertTrue(signup_helper::can_signup($snoshow));
        $this->assertTrue(signup_helper::can_signup($sfully));
        $this->assertFalse(signup_helper::can_signup($swaitlst));
        $this->assertFalse(signup_helper::can_signup($sbooked));

        // Change the state restriction of the seminar to any of the states, and recheck the booked and waitlisted still can't resignup.
        $seminar1->set_multisignupnoshow(false);
        $seminar1->set_multisignuppartly(false);
        $seminar1->set_multisignupfully(false);
        $seminar1->save();

        // Get a new user in the partially attended state, and recheck the states which can signup.
        $this->assertTrue(signup_helper::can_signup($spartly));
        $this->assertTrue(signup_helper::can_signup($snoshow));
        $this->assertTrue(signup_helper::can_signup($sfully));
        $this->assertTrue(signup_helper::can_signup($swaitlst));
        $this->assertTrue(signup_helper::can_signup($sbooked));
    }

    /**
     * Test the setting for maximum number of signups.
     */
    public function test_multisignup_limitations() {
        list($users, $course, $seminar1, $s1events, $seminar2, $s2events) = $this->basic_seminar_setup();

        // Make sure the first seminar has multiple signups enabled but limited to 3 signups.
        $seminar1->set_multiplesessions(1); // Note: Multiple sign ups enabled.
        $seminar1->set_multisignupmaximum(2);
        $seminar1->save();

        // Check the settings are what we are expecting.
        $this->assertEquals(1, $seminar1->get_multiplesessions());
        $this->assertEquals(2, $seminar1->get_multisignup_maximum());
        $this->assertEquals([], $seminar1->get_multisignup_states());

        // Do the same for the second seminar.
        $seminar2->set_multiplesessions(1); // Note: Multiple sign ups enabled.
        $seminar2->set_multisignupmaximum(3);
        $seminar2->save();

        // Check the settings are what we are expecting.
        $this->assertEquals(1, $seminar2->get_multiplesessions());
        $this->assertEquals(3, $seminar2->get_multisignup_maximum());
        $this->assertEquals([], $seminar2->get_multisignup_states());

        // Make sure that you can sign up to both 3 times (i.e. the counts are independant of each other)
        $user1 = array_pop($users);
        $user2 = array_pop($users);

        // First signup both users up to an event in seminar1.
        $s1e1 = array_pop($s1events);
        $signup111 = signup::create($user1->id, $s1e1);
        $this->assertTrue(signup_helper::can_signup($signup111));
        signup_helper::signup($signup111);
        $signup211 = signup::create($user2->id, $s1e1);
        $this->assertTrue(signup_helper::can_signup($signup211));
        signup_helper::signup($signup211);

        // Next signup both users up to an event in seminar2.
        $s2e1 = array_pop($s2events);
        $signup121 = signup::create($user1->id, $s2e1);
        $this->assertTrue(signup_helper::can_signup($signup121));
        signup_helper::signup($signup121);
        $signup221 = signup::create($user2->id, $s2e1);
        $this->assertTrue(signup_helper::can_signup($signup221));
        signup_helper::signup($signup221);

        // Sign the first user up to a second event in the first seminar. This means:
        // - Signups in a second seminar don't affect signups to the first seminar.
        // - Signups for a second user don't affect the first users signups.
        $s1e2 = array_pop($s1events);
        $signup112 = signup::create($user1->id, $s1e2);
        $this->assertTrue(signup_helper::can_signup($signup112));
        signup_helper::signup($signup112);
        $signup212 = signup::create($user2->id, $s1e2);
        $this->assertTrue(signup_helper::can_signup($signup212));
        signup_helper::signup($signup212);

        // Sign the second user up to the second event.
        $s2e2 = array_pop($s2events);
        $signup122 = signup::create($user1->id, $s2e2);
        $this->assertTrue(signup_helper::can_signup($signup122));
        signup_helper::signup($signup122);
        $signup222 = signup::create($user2->id, $s2e2);
        $this->assertTrue(signup_helper::can_signup($signup222));
        signup_helper::signup($signup222);

        // Get a third user for control testing.
        $user3 = array_pop($users);

        // Both users should no longer be able to signup to the first seminar.
        $s1e3 = array_pop($s1events);
        $signup113 = signup::create($user1->id, $s1e3);
        $this->assertFalse(signup_helper::can_signup($signup113));
        $signup213 = signup::create($user2->id, $s1e3);
        $this->assertFalse(signup_helper::can_signup($signup113));
        // The third user should be able to.
        $signup313 = signup::create($user3->id, $s1e3);
        $this->assertTrue(signup_helper::can_signup($signup313));

        // Everyone should still be able to sign up to the second seminar.
        $s2e3 = array_pop($s2events);
        $signup123 = signup::create($user1->id, $s2e3);
        $this->assertTrue(signup_helper::can_signup($signup123));
        $signup223 = signup::create($user2->id, $s2e3);
        $this->assertTrue(signup_helper::can_signup($signup223));
        // A third user should be able to.
        $user3 = array_pop($users);
        $signup323 = signup::create($user3->id, $s2e3);
        $this->assertTrue(signup_helper::can_signup($signup323));
    }
}
