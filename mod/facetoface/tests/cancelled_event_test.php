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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

use mod_facetoface\{seminar_event, room, seminar_session, signup};
use mod_facetoface\signup\state\booked;

class mod_facetoface_cancelled_event_testcase extends advanced_testcase {
    /**
     * This is a test suite of sending an email out of a cancelled event, to check whether the custom room's detail is still
     * included in the email or not.
     *
     * @return void
     */
    public function test_sending_cancellation_with_room_info(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $gen = $this->getDataGenerator();
        $course = $gen->create_course([]);

        /** @var mod_facetoface_generator $f2fgen */
        $f2fgen = $gen->get_plugin_generator('mod_facetoface');
        $f2f = $f2fgen->create_instance(['course' => $course->id]);

        $room = room::create_custom_room();
        $room->set_name('This is custom room');
        $room->set_capacity(2);
        $room->save();

        $e = new seminar_event();
        $e->set_facetoface($f2f->id);
        $e->set_capacity($room->get_capacity());
        $e->save();

        $ss = new seminar_session();
        $ss->set_timestart(time() + 3600);
        $ss->set_timefinish(time() + 7200);
        $ss->set_sessionid($e->get_id());
        $ss->set_roomid($room->get_id());
        $ss->save();

        // Adding signup here, so that we do have users to receive the email with the room information in it.
        for ($i = 0; $i < 2; $i ++) {
            $user = $gen->create_user();
            $gen->enrol_user($user->id, $course->id);

            $signup = signup::create($user->id, $e);
            $signup->save();

            $signup->switch_state(booked::class);
        }

        $sink = phpunit_util::start_message_redirection();

        // Execute those booking confirmation to the users first, so that we can start cancelling the event, and perform the
        // assertion way easier.
        $this->execute_adhoc_tasks();
        $sink->clear();

        // Cancelling event here.
        $e->cancel();
        $this->execute_adhoc_tasks();
        $messages = $sink->get_messages();

        // Start assertion to locate that whether the room name is included in the cancellation message.
        foreach ($messages as $msg) {
            $this->assertContains('This is custom room', $msg->fullmessage);
        }
    }
}