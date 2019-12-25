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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

use mod_facetoface\seminar;
use mod_facetoface\seminar_event;
use mod_facetoface\seminar_session;
use mod_facetoface\signup_helper;
use mod_facetoface\signup;
use mod_facetoface\signup\state\booked;
use mod_facetoface\signup\state\partially_attended;
use mod_facetoface\signup\state\fully_attended;

defined('MOODLE_INTERNAL') || die();

/**
 * Class mod_facetoface_signup_helper_testcase
 */
class mod_facetoface_signup_helper_testcase extends advanced_testcase {
    /**
     * set up for test_compute_final_grade
     *
     * @return \stdClass containing seminar, user1, user2, event1, event2, signup1, signup2
     */
    protected function setup_compute_final_grade() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $that = new \stdClass();
        $gen = $this->getDataGenerator();
        $that->course = $gen->create_course();

        $f2fgen = $gen->get_plugin_generator('mod_facetoface');
        $f2f = $f2fgen->create_instance(['course' => $that->course->id]);

        $that->seminar = new seminar($f2f->id);
        $that->seminar
            ->set_multisignupfully(true)
            ->set_multisignuppartly(true)
            ->set_multiplesessions(1)
            ->set_multisignupmaximum(2)
            ->save();

        $that->user1 = $gen->create_user();
        $that->user2 = $gen->create_user();
        $gen->enrol_user($that->user1->id, $that->course->id);

        $creator = function ($timediff, $user, $status, &$evt, &$sup) use ($f2f) {
            $evt = new seminar_event();
            $evt->set_facetoface($f2f->id)->save();
            $time = time() + $timediff;
            $sess = new seminar_session();
            $sess->set_timestart($time)->set_timefinish($time + HOURSECS)->set_sessionid($evt->get_id())->save();
            $sup = signup::create($user->id, $evt);
            $sup->save();
            $sup->switch_state(booked::class);
            $sess->set_timestart($time - YEARSECS)->set_timefinish($time - YEARSECS + HOURSECS)->save();
            return signup_helper::process_attendance($evt, [ $sup->get_id() => $status ]);
        };

        $result = $creator(DAYSECS, $that->user1, partially_attended::get_code(), $that->event1, $that->signup1);
        $this->assertTrue($result);
        $result = $creator(DAYSECS * 2, $that->user1, partially_attended::get_code(), $that->event2, $that->signup2);
        $this->assertTrue($result);
        $result = signup_helper::process_attendance($that->event1, [ $that->signup1->get_id() => fully_attended::get_code() ]);
        $this->assertTrue($result);

        return $that;
    }

    public function test_compute_final_grade() {
        global $DB;
        $that = $this->setup_compute_final_grade();

        $grade = signup_helper::compute_final_grade($that->seminar, $that->user1->id);
        $this->assertSame(100., $grade);

        $grade = signup_helper::compute_final_grade($that->seminar, $that->user2->id);
        $this->assertSame(null, $grade);

        $f2f = (object)[ 'id' => $that->seminar->get_id() ];
        $grade = signup_helper::compute_final_grade($f2f, $that->user1->id);
        $this->assertSame(100., $grade);

        $grade = signup_helper::compute_final_grade($f2f, $that->user2->id);
        $this->assertSame(null, $grade);

        $bogusf2fid = $f2f->id + 42;
        $this->assertEquals(0, $DB->count_records('facetoface', [ 'id' => $bogusf2fid ]));

        $seminar = new seminar();
        $rc = new ReflectionClass($seminar);
        $pr = $rc->getProperty('id');
        $pr->setAccessible(true);
        $pr->setValue($seminar, $bogusf2fid); // invalidate seminar->id
        $grade = signup_helper::compute_final_grade($seminar, $that->user1->id);
        $this->assertSame(null, $grade);

        $f2f->id += 42; // invalidate f2f->id
        $this->assertEquals(0, $DB->count_records('facetoface', [ 'id' => $f2f->id ]));
        $grade = signup_helper::compute_final_grade($f2f, $that->user1->id);
        $this->assertSame(null, $grade);

        try {
            signup_helper::compute_final_grade($f2f->id, $that->user1->id);
            $this->fail('Must fail when first argument is neither seminar nor stdClass');
        } catch (\coding_exception $e) {
        }
    }
}
