<?php
/*
 * This file is part of Totara LMS
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
 * @author  Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package mod_facetoface
 */

use mod_facetoface\seminar_event;
use mod_facetoface\seminar_session;
use mod_facetoface\signup;
use mod_facetoface\signup\state\booked;
use mod_facetoface\signup\state\user_cancelled;
use mod_facetoface\signup_helper;

/**
 * Test booking cancellations workflows
 */
class mod_facetoface_booking_cancellation_testcase extends advanced_testcase {
    /**
     * Test that booking can be cancelled when event is in future and allows cancellations
     */
    public function test_simple_cancel() {
        $this->resetAfterTest();
        /**
         * @var signup $signup1
         * @var signup $signup2
         */
        ['learner1'=> $learner1, 'signup1' => $signup1, 'signup2' => $signup2] = $this->prepare_data();

        // Cancel user.
        $this->setUser($learner1);
        $signup1->switch_state(user_cancelled::class);

        // Reload singups, to be sure.
        $signup1 = new signup($signup1->get_id());
        $signup2 = new signup($signup2->get_id());

        $this->assertInstanceOf(user_cancelled::class, $signup1->get_state());
        $this->assertInstanceOf(booked::class, $signup2->get_state());
    }

    /**
     * Test booking can be cancelled only by user who has "signuppastevents" capability
     */
    public function test_cancel_past_event() {
        global $DB;
        $this->resetAfterTest();
        /**
         * @var signup $signup1
         * @var signup $signup2
         * @var seminar_session $seminarsession
         */
        ['learner1' => $learner1, 'course' => $course, 'seminarsession'=> $seminarsession, 'signup1' => $signup1,
            'signup2' => $signup2] = $this->prepare_data();

        // Move event to past.
        $seminarsession->set_timestart(time() - 100)->set_timefinish(time() - 10)->save();

        // Confirm that learner cannot cancel.
        $this->setUser($learner1);
        $this->assertFalse($signup1->can_switch(user_cancelled::class));

        // Create trainer and assign capability.
        $trainer = $this->getDataGenerator()->create_user();
        $trainerrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $context = context_course::instance($course->id);
        assign_capability('mod/facetoface:signuppastevents', CAP_ALLOW, $trainerrole->id, $context);
        $this->getDataGenerator()->role_assign($trainerrole->id, $trainer->id, $context->id);

        // Confirm that trainer can cancel.
        $this->setUser($trainer);
        $this->assertTrue($signup1->can_switch(user_cancelled::class));
        $signup1->switch_state(user_cancelled::class);

        // Reload singups, to be sure.
        $signup1 = new signup($signup1->get_id());
        $signup2 = new signup($signup2->get_id());

        $this->assertInstanceOf(user_cancelled::class, $signup1->get_state());
        $this->assertInstanceOf(booked::class, $signup2->get_state());
    }
    /**
     * Test booking can be cancelled only by user who has "removeattendees" capability
     */
    public function test_cancel_event_not_allow_cancellations() {
        global $DB;
        $this->resetAfterTest();
        /**
         * @var signup $signup1
         * @var signup $signup2
         * @var seminar_event $seminarevent
         */
        ['learner1' => $learner1, 'course' => $course, 'seminarevent'=> $seminarevent, 'signup1' => $signup1,
            'signup2' => $signup2] = $this->prepare_data();

        // Prohibit cancellations.
        $seminarevent->set_allowcancellations(seminar_event::ALLOW_CANCELLATION_NEVER)->save();

        // Confirm that learner cannot cancel.
        $this->setUser($learner1);
        $this->assertFalse($signup1->can_switch(user_cancelled::class));

        // Create trainer and assign capability.
        $trainer = $this->getDataGenerator()->create_user();
        $trainerrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $context = context_course::instance($course->id);
        assign_capability('mod/facetoface:removeattendees', CAP_ALLOW, $trainerrole->id, $context);
        $this->getDataGenerator()->role_assign($trainerrole->id, $trainer->id, $context->id);

        // Confirm that trainer can cancel.
        $this->setUser($trainer);
        $this->assertTrue($signup1->can_switch(user_cancelled::class));
        $signup1->switch_state(user_cancelled::class);

        // Reload singups, to be sure.
        $signup1 = new signup($signup1->get_id());
        $signup2 = new signup($signup2->get_id());

        $this->assertInstanceOf(user_cancelled::class, $signup1->get_state());
        $this->assertInstanceOf(booked::class, $signup2->get_state());
    }

    /**
     * Test booking can be cancelled only by user who has both "signuppastevents" and "removeattendees" capabilities
     */
    public function test_cancel_past_event_not_allow_cancellations() {
        global $DB;
        $this->resetAfterTest();
        /**
         * @var signup $signup1
         * @var signup $signup2
         * @var seminar_event $seminarevent
         * @var seminar_session $seminarsession
         */
        ['learner1' => $learner1, 'course' => $course, 'seminarevent'=> $seminarevent, 'signup1' => $signup1,
            'seminarsession'=> $seminarsession, 'signup2' => $signup2] = $this->prepare_data();

        // Move event to past.
        $seminarsession->set_timestart(time() - 100)->set_timefinish(time() - 10)->save();

        // Prohibit cancellations.
        $seminarevent->set_allowcancellations(seminar_event::ALLOW_CANCELLATION_NEVER)->save();

        // Confirm that learner cannot cancel.
        $this->setUser($learner1);
        $this->assertFalse($signup1->can_switch(user_cancelled::class));

        // Create trainer and assign capability.
        $trainer = $this->getDataGenerator()->create_user();
        $trainerrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $context = context_course::instance($course->id);
        assign_capability('mod/facetoface:removeattendees', CAP_ALLOW, $trainerrole->id, $context);
        assign_capability('mod/facetoface:signuppastevents', CAP_ALLOW, $trainerrole->id, $context);
        $this->getDataGenerator()->role_assign($trainerrole->id, $trainer->id, $context->id);

        // Confirm that trainer can cancel.
        $this->setUser($trainer);
        $this->assertTrue($signup1->can_switch(user_cancelled::class));
        $signup1->switch_state(user_cancelled::class);

        // Reload singups, to be sure.
        $signup1 = new signup($signup1->get_id());
        $signup2 = new signup($signup2->get_id());

        $this->assertInstanceOf(user_cancelled::class, $signup1->get_state());
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
        $seminarevent->set_facetoface($facetoface->id)
            ->set_capacity(2)
            ->save();
        $seminarsession = new seminar_session();
        $seminarsession->set_sessionid($seminarevent->get_id())
            ->set_timestart(time() + WEEKSECS)
            ->set_timefinish(time() + WEEKSECS + 60)
            ->save();
        $signup1 = signup_helper::signup(signup::create($learner1->id, $seminarevent));
        $signup2 = signup_helper::signup(signup::create($learner2->id, $seminarevent));

        // Confirm users booked.
        $this->assertInstanceOf(booked::class, $signup1->get_state());
        $this->assertInstanceOf(booked::class, $signup2->get_state());

        return ['learner1' => $learner1, 'learner2' => $learner2, 'course' => $course, 'seminarevent' => $seminarevent,
            'seminarsession' => $seminarsession, 'signup1' => $signup1, 'signup2' => $signup2];
    }
}