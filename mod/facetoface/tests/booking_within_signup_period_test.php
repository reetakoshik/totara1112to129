<?php
/*
* This file is part of Totara Learn
*
* Copyright (C) 2019 onwards Totara Learning Solutions LTD
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

defined('MOODLE_INTERNAL') || die();

use mod_facetoface\seminar_event;
use mod_facetoface\seminar_session;
use mod_facetoface\signup;
use mod_facetoface\signup\state\booked;
use mod_facetoface\signup_helper;

/**
 * Test booking outside Sign-up registration period.
 */
class mod_facetoface_booking_within_signup_period_testcase extends advanced_testcase {

    /**
     * Test attendee can sing-up when Sign-up registration period is still open.
     */
    public function test_signup_registration_period_open() {

        $this->resetAfterTest();

        ['learner2' => $learner2, 'course' => $course, 'seminarevent' => $seminarevent] = $this->prepare_data();

        $seminarevent
            ->set_registrationtimestart(time() - DAYSECS)
            ->set_registrationtimefinish(time() + DAYSECS)
            ->save();

        $this->setUser($learner2);
        $signup2 = signup_helper::signup(signup::create($learner2->id, $seminarevent));
        $this->assertInstanceOf(booked::class, $signup2->get_state());
    }

    /**
     * Test add attendee as manager when Sign-up registration period is still open.
     */
    public function test_teacher_signup_registration_period_open() {
        global $DB;

        $this->resetAfterTest();

        ['learner2' => $learner2, 'course' => $course, 'seminarevent' => $seminarevent] = $this->prepare_data();

        $seminarevent
            ->set_registrationtimestart(time() - DAYSECS)
            ->set_registrationtimefinish(time() + DAYSECS)
            ->save();

        // Create teacher.
        $trainer = $this->getDataGenerator()->create_user();
        $trainerrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $context = context_course::instance($course->id);
        $this->getDataGenerator()->role_assign($trainerrole->id, $trainer->id, $context->id);

        // Confirm that trainer can cancel.
        $this->setUser($trainer);
        $signup2 = signup_helper::signup(signup::create($learner2->id, $seminarevent));
        $this->assertInstanceOf(booked::class, $signup2->get_state());
    }

    /**
     * Test attendee can sign-up when Sign-up registration period is closed.
     */
    public function test_signup_registration_period_closed() {

        $this->resetAfterTest();

        ['learner2' => $learner2, 'course' => $course, 'seminarevent' => $seminarevent] = $this->prepare_data();

        $seminarevent
            ->set_registrationtimestart(time() - DAYSECS - DAYSECS)
            ->set_registrationtimefinish(time() - DAYSECS)
            ->save();

        $this->setUser($learner2);
        $signup2 = signup::create($learner2->id, $seminarevent);
        $this->assertFalse($signup2->can_switch(booked::class));
    }

    /**
     * Test add attendee as a manager when Sign-up registration period is closed and teacher does not have the right capability.
     */
    public function test_teacher_signup_registration_period_closed() {
        global $DB;

        $this->resetAfterTest();

        ['learner2' => $learner2, 'course' => $course, 'seminarevent' => $seminarevent] = $this->prepare_data();

        $seminarevent
            ->set_registrationtimestart(time() - DAYSECS - DAYSECS)
            ->set_registrationtimefinish(time() - DAYSECS)
            ->save();

        // Create teacher.
        $trainer = $this->getDataGenerator()->create_user();
        $trainerrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $context = context_course::instance($course->id);
        $this->getDataGenerator()->role_assign($trainerrole->id, $trainer->id, $context->id);

        // Confirm that trainer can cancel.
        $this->setUser($trainer);
        $signup2 = signup::create($learner2->id, $seminarevent);
        $this->assertFalse($signup2->can_switch(booked::class));
    }

    /**
     * Test add attendee as a manager when Sign-up registration period is closed and teacher does have the right capability.
     */
    public function test_teacher_signup_registration_period_closed_with_capability() {
        global $DB;

        $this->resetAfterTest();

        ['learner2' => $learner2, 'course' => $course, 'seminarevent' => $seminarevent] = $this->prepare_data();

        $seminarevent
            ->set_registrationtimestart(time() - DAYSECS - DAYSECS)
            ->set_registrationtimefinish(time() - DAYSECS)
            ->save();

        // Create teacher.
        $trainer = $this->getDataGenerator()->create_user();
        $trainerrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $context = context_course::instance($course->id);
        assign_capability('mod/facetoface:surpasssignupperiod', CAP_ALLOW, $trainerrole->id, $context);
        $this->getDataGenerator()->role_assign($trainerrole->id, $trainer->id, $context->id);

        // Confirm that trainer can cancel.
        $this->setUser($trainer);
        $signup2 = signup_helper::signup(signup::create($learner2->id, $seminarevent));
        $this->assertInstanceOf(booked::class, $signup2->get_state());
    }

    /**
     * Prepare users, course, seminar, and book users on seminar.
     * @return array of instances
     */
    private function prepare_data() {
        $learner1 = $this->getDataGenerator()->create_user();
        $learner2 = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($learner1->id, $course->id);
        $this->getDataGenerator()->enrol_user($learner2->id, $course->id);

        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $facetoface = $facetofacegenerator->create_instance(['course' => $course->id]);

        $seminarevent = new seminar_event();
        $seminarevent
            ->set_facetoface($facetoface->id)
            ->set_capacity(2)
            ->set_registrationtimefinish(time() + DAYSECS)
            ->save();
        $seminarsession = new seminar_session();
        $seminarsession->set_sessionid($seminarevent->get_id())
            ->set_timestart(time() + WEEKSECS)
            ->set_timefinish(time() + WEEKSECS + DAYSECS)
            ->save();
        $signup1 = signup_helper::signup(signup::create($learner1->id, $seminarevent));

        // Confirm users booked.
        $this->assertInstanceOf(booked::class, $signup1->get_state());

        return ['learner2' => $learner2, 'course' => $course, 'seminarevent' => $seminarevent];
    }
}