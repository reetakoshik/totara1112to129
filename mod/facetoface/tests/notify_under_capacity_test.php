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
require_once("{$CFG->dirroot}/mod/facetoface/lib.php");

use totara_job\job_assignment;

/**
 * Unit test of sending a notification email should not add up the message content and the $eventdata should be reset
 * everytime one message got sent out to a single recipient
 *
 * Class mod_facetoface_notify_under_capacity_testcase
 */
class mod_facetoface_notify_under_capacity_testcase extends advanced_testcase {
    /**
     * The method of setting up the environment for the unit test, and the steps are:
     * + Create course
     * + Create facetoface
     * + Create users
     * + Enrol users to course created
     *
     * @return void
     */
    private function create_facetoface_with_session(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course([], ['createsections' => true]);

        /** @var mod_facetoface_generator $f2fgenerator */
        $f2fgenerator = $generator->get_plugin_generator('mod_facetoface');
        $f2f = $f2fgenerator->create_instance((object)[
            'course' => $course->id
        ]);

        $time = time() + 3600;
        $session = (object)[
            'facetoface' => $f2f->id,
            'sessiondates' => [
                (object)[
                    'timestart' => $time,
                    'timefinish' => $time + 3600,
                    'sessiontimezone' => 'Pacific/Auckland',
                    'roomid' => 0,
                    'assetids' => [],
                ]
            ],
            'mincapacity' => 3,
            'cutoff' => 3620,
            'sendcapacityemail' => 1
        ];

        $session->id = $f2fgenerator->add_session($session);
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();

        $generator->enrol_user($user1->id, $course->id);
        $generator->enrol_user($user2->id, $course->id);

        // Signing up users to the session here
        $this->sign_up_user_to_session($user1, $session, $course, $f2f);
        $this->sign_up_user_to_session($user2, $session, $course, $f2f);

        // Clean messages stack.
        $sink = $this->redirectMessages();
        $this->execute_adhoc_tasks();
        $sink->close();

        // Set the session date back an hour, this is enough for facetoface_notify_under_capacity to find this session.
        $sql = 'UPDATE {facetoface_sessions_dates} SET timestart = (timestart - 360) WHERE sessionid = :sessionid';
        $DB->execute($sql, array('sessionid' => $session->id));
    }

    /**
     * A method of sign-up user to a facetoface session
     * @param stdClass $user        The user that is being signed up to the session
     * @param stdClass $session     The target session
     * @param stdClass $course
     * @param stdClass $f2f
     *
     * @return void
     */
    private function sign_up_user_to_session(stdClass $user, stdClass $session, stdClass $course, stdClass $f2f): void {
        global $DB;

        if (!$DB->record_exists("job_assignment", ['userid' => $user->id])) {
            $manager = $this->getDataGenerator()->create_user();
            $managerja = job_assignment::create_default($manager->id);

            $data = [
                'userid' => $user->id,
                'fullname' => 'studentja',
                'shortname' => 'studentja',
                'idnumber' => 'studentja',
                'managerjaid' => $managerja->id,
            ];

            job_assignment::create($data);
        }

        $seminarevent = new \mod_facetoface\seminar_event($session->id);
        $signup = \mod_facetoface\signup::create($user->id, $seminarevent);
        \mod_facetoface\signup_helper::signup($signup);
    }

    /**
     * Test suite of sending capacity notification with the event data reset for every message get sent out to a
     * single recipient
     *
     * @return void
     */
    public function test_sending_capacity_notification_email_with_eventdata_reset_for_multiple_recipients(): void {
        global $CFG, $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $studentrole = $DB->get_record("role", ['shortname' => 'student']);
        if (is_null($studentrole)) {
            $this->fail("There is no student role found within test environment");
        }

        // Re-assign the student role here, so that the test suite could intercept the message and perform the test on
        // the messages
        $CFG->facetoface_session_rolesnotify = $studentrole->id;
        $this->create_facetoface_with_session();

        $sink = $this->redirectMessages();
        ob_start();
        facetoface_notify_under_capacity();
        ob_end_clean();

        $this->execute_adhoc_tasks();

        $messages = $sink->get_messages();
        $this->assertCount(2, $messages, "The test suite was expecting only 2 messages sent out in the environment setup");

        $message1 = $messages[0];
        $message2 = $messages[1];


        // At this point, when the email sent out to each learner, the event data should be reset after email sent,
        // therefore, the second email that sent after first one should not had added up information, and they should
        // be similar to each other.
        $this->assertEquals($message1->fullmessagehtml, $message2->fullmessagehtml);
    }
}