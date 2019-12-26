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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

use mod_facetoface\{seminar_event, seminar, seminar_session, seminar_event_list, signup};
use mod_facetoface\signup\state\{booked, waitlisted};

class mod_facetoface_pending_waitlist_clear_testcase extends advanced_testcase {
    /**
     * A test suite to detect the debugging message on query getting event with multiple session
     * dates and has wait-listed user to be cleaned up.
     *
     * @return void
     */
    public function test_getting_pending_waitlist_to_be_clear(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $gen = $this->getDataGenerator();
        $course = $gen->create_course();

        $f2fgen = $gen->get_plugin_generator('mod_facetoface');
        $f2f = $f2fgen->create_instance(['course' => $course->id]);

        $s = new seminar($f2f->id);
        $s->set_waitlistautoclean(1);
        $s->save();

        $e = new seminar_event();
        $e->set_capacity(2);
        $e->set_facetoface($f2f->id);
        $e->set_waitlisteveryone(1);
        $e->set_allowoverbook(1);
        $e->save();

        $time = time();
        $times = [
            ['start' => ($time + 3600), 'finish' => $time + (3600 * 2)],
            ['start' => $time + (3600 * 4), 'finish' => $time + (3600 * 5)]
        ];

        foreach ($times as $t) {
            $ss = new seminar_session();
            $ss->set_sessionid($e->get_id());
            $ss->set_timestart($t['start']);
            $ss->set_timefinish($t['finish']);
            $ss->save();
        }

        for ($i = 0; $i < 5; $i++) {
            $user = $gen->create_user();
            $gen->enrol_user($user->id, $course->id);

            $signup = new signup();
            $signup->set_sessionid($e->get_id());
            $signup->set_userid($user->id);
            $signup->save();

            if ($i >= 2) {
                $signup->switch_state(waitlisted::class);
            } else {
                $signup->switch_state(booked::class);
            }
        }

            // There should be only one element here
        $events = seminar_event_list::pending_waitlist_clear($time + 3900);
        $this->assertCount(1, $events);
    }
}