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

use totara_job\job_assignment;
use mod_facetoface\seminar;
use mod_facetoface\seminar_event;
use mod_facetoface\signup;
use mod_facetoface\signup\state\booked;
use mod_facetoface\seminar_session;
use mod_facetoface\attendees_list_helper;
use mod_facetoface\bulk_list;
use mod_facetoface\task\send_user_message_adhoc_task;
use core\task\manager;

/**
 * Unit test of checking whether an email should be sending to the third party or not when both of the options
 * `notifyuser` and `notifymanager` are being disabled
 *
 * Class mod_facetoface_send_thirdparty_testcase
 */
class mod_facetoface_send_thirdparty_testcase extends advanced_testcase {
    /**
     * Creating the number of users with one manager
     * @param int $numberofusers
     * @return stdClass[]
     */
    private function create_users(int $numberofusers): array {
        $generator = $this->getDataGenerator();
        $manager = $generator->create_user();
        $ja = job_assignment::create_default($manager->id);
        $users = [];
        // $time is for any field that needed
        $time = time();
        for ($i = 0; $i < $numberofusers; $i++) {
            $user = $generator->create_user([
                'firstname' => "firstname{$i}",
                'lastname' => "lastname{$i}"
            ]);

            $jobassignment = job_assignment::create([
                'userid' => $user->id,
                'idnumber' => "idnumber{$i}",
                'fullname' => "fullname{$i}",
                'shortname' => "shortname{$i}",
                'description' => "description{$i}",
                'description_editor' => '',
                'positionid' => null,
                'organisationid' => null,
                'startdate' => null,
                'enddate' => null,
                'managerjaid' => $ja->id,
                'tempmanagerjaid' => null,
                'tempmanagerexpirydate' => null,
                'appraiserid' => null,
                'totarasync' => 0,
                'synctimemodified' => $time,
            ]);

            $user->jobassignment = $jobassignment;
            $users[] = $user;
        }

        return $users;
    }

    /**
     * @return seminar
     */
    private function create_facetoface_with_manager_approval(): seminar {
        $generator = $this->getDataGenerator();

        $course = $generator->create_course(null, [
            'createsections' => 1
        ]);

        /** @var mod_facetoface_generator $f2fgenerator */
        $f2fgenerator = $generator->get_plugin_generator('mod_facetoface');
        $f2f = $f2fgenerator->create_instance([
            'course'        => $course->id,
            'approvaltype'  => seminar::APPROVAL_MANAGER,
            'thirdparty'    => 'bomba@example.com'
        ]);

        $f2f = new seminar($f2f->id);
        return $f2f;
    }

    /**
     * @return seminar_event
     */
    private function create_facetoface_event(seminar $seminar): seminar_event {
        // Creating event here
        $event = new seminar_event();
        $event->set_facetoface($seminar->get_id())->save();

        // Creating event's session here, but we need time
        $time = time();

        $session = new seminar_session();
        $session->set_sessionid($event->get_id())->set_timestart($time + 2400)->set_timefinish($time + 7200)->save();
        return $event;
    }

    /**
     * @param stdClass[] $users
     * @return bulk_list
     */
    private function build_bulklist(array $users): bulk_list {
        $bulklist = new bulk_list(uniqid('f2f'), new moodle_url("/"), 'remove');

        $user = array_shift($users);
        $bulklist->set_user_ids([$user->id]);
        return $bulklist;
    }

    /**
     * @return void
     */
    private function set_up_test_environment(): void {
        $users = $this->create_users(2);
        $f2f = $this->create_facetoface_with_manager_approval();
        $event = $this->create_facetoface_event($f2f);

        foreach ($users as $user) {
            // Enrolling user here, before signing them up into the event
            $this->getDataGenerator()->enrol_user($user->id, $f2f->get_course());

            $signup = signup::create($user->id, $event);
            $signup->set_skipapproval();
            $signup->save();
            $signup->switch_state(booked::class);
        }

        // Removing the user here from the seminar event, so that it add up the adhoc task data
        // and entries
        $bulklist = $this->build_bulklist($users);
        attendees_list_helper::remove((object)[
            's' => $event->get_id(),
            'notifyuser' => 0,
            'notifymanager' => 0,
            'listid' => $bulklist->get_list_id(),
            'users' => array_flip($bulklist->get_user_ids())
        ]);
    }

    /**
     * A short summary about environment, where a few of user's were booked into the seminar event, whereas
     * seminar is having a third party email address added. Then one of the user is removed from booked event. When
     * that user is removed, the notification to user should be disabled nor notification to manager, the third party
     * email should not received any email about cancellation.
     * As the expectation of the test result, there should have no email cancellations sending out.
     *
     * @throws moodle_exception
     */
    public function test_sending_thirdparty_message(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $this->set_up_test_environment();

        $sink = phpunit_util::start_message_redirection();
        $this->execute_adhoc_tasks();

        $messages = $sink->get_messages();
        // Expecting the $messages to have more than 0 messages, as there were users were signed up into the event
        $this->greaterThan(0, count($messages));

        // We need to going through the $messages to detect wheter there is an email/message sending out about the
        // canellation or not. And also, if there are no cancellation email, then the third party should not receive
        // any email/message as well
        foreach ($messages as $message) {
            $subject = $message->subject;
            if (stripos($subject, "cancellation") !== false) {
                $this->fail("Not expect any cancellation email from the adhoc task");
            }
        }
    }
}
