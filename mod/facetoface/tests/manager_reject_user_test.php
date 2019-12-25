<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
global $CFG;
require_once("{$CFG->dirroot}/totara/message/workflow/lib.php");
require_once("{$CFG->dirroot}/totara/message/workflow/plugins/facetoface/workflow_facetoface.php");

use mod_facetoface\seminar;
use mod_facetoface\seminar_event;
use mod_facetoface\signup;
use mod_facetoface\signup\state\booked;
use mod_facetoface\seminar_session;
use mod_facetoface\signup_list;


/**
 * Class mod_facetoface_manager_reject_user_testcase
 * @group mod_facetoface
 */
class mod_facetoface_manager_reject_user_testcase extends advanced_testcase {

    /**
     * @return seminar
     */
    private function create_facetoface(): seminar {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        /** @var mod_facetoface_generator $f2fgenerator */
        $f2fgenerator = $generator->get_plugin_generator('mod_facetoface');
        $f2f = $f2fgenerator->create_instance(['course' => $course->id]);

        return new seminar($f2f->id);
    }

    /**
     * @param int $numberofsignup
     */
    private function create_signups(int $numberofsignup, seminar $seminar): void {
        $generator = $this->getDataGenerator();

        /** @var seminar_event $seminarevent */
        $seminarevent = $seminar->get_events()->current();
        for ($i = 0; $i < $numberofsignup; $i++) {
            $user = $generator->create_user();
            $generator->enrol_user($user->id, $seminar->get_course());
            $signup = new signup();
            $signup->set_sessionid($seminarevent->get_id())->set_userid($user->id);
            $signup->save();
            $signup->switch_state(booked::class);
        }
    }

    /**
     * A short summary about the test suite, when user is at the state of booked, and the approval manager is
     * able to reject the request from signed up user. However, the approval manager is not longer be able to move
     * the state of sign up user (for e.g from booked to rejected). Therefore, the email should not be sending out
     * to the sign-up user.
     *
     * @return void
     */
    public function test_booked_user_should_not_received_rejected_email(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $f2f = $this->create_facetoface();
        // Creating event here
        $seminarevent = (new seminar_event())->set_facetoface($f2f->get_id());
        $seminarevent->save();

        // Creating the session here
        $time = time() + 3600;
        $session = new seminar_session();
        $session->set_timestart($time)->set_timefinish($time + 7200)->set_sessionid($seminarevent->get_id())->save();
        $this->create_signups(2, $f2f);

        // Retrieving pure session and pure facetoface record here
        $event = $DB->get_record('facetoface_sessions', ['id' => $seminarevent->get_id()], "*", MUST_EXIST);
        $stdf2f = $DB->get_record('facetoface', ['id' => $f2f->get_id()], "*", MUST_EXIST);

        /** @var totara_message_workflow_facetoface $plugin */
        $plugin = tm_message_workflow_object('facetoface');
        if (!$plugin) {
            $this->fail("No plugin found here");
        }

        // Test suite starts from here
        $sink = phpunit_util::start_message_redirection();
        $signups = new signup_list(['sessionid' => $seminarevent->get_id()]);
        /** @var signup $signup */
        foreach ($signups as $signup) {
            $eventdata = [
                'userid' => $signup->get_userid(),
                'session' => $event,
                'facetoface' => $stdf2f
            ];

            $plugin->onreject($eventdata, "Hell world");
        }

        // Since the signup users are in booked state, which means that any rejection should not trigger any email
        // to the signup user. And as result, we are expecting 0 messages out here
        $messages = $sink->get_messages();
        $this->assertCount(0, $messages);
    }
}