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
require_once("{$CFG->dirroot}/mod/facetoface/notification/lib.php");

/**
 * Class mod_facetoface_send_decline_testcase
 */
class mod_facetoface_send_decline_testcase extends advanced_testcase {
	/**
	 * Setting up the database environment for the
	 * test case. The steps are:
	 * + Create a course
	 * + Create facetoface
	 * + Create facetoface's event
	 * + Create user
	 * + Enrol user
	 * + Sign-up user to
	 *
     * Returning array of $facetoface, $session (event) and user
     * @return array
	 */
	private function create_facetoface_with_session_signup(): array {
		global $DB;

	    $course = $this->getDataGenerator()->create_course([], [
			'createsections' => true
		]);

		/** @var mod_facetoface_generator $generator*/
		$generator = $this->getDataGenerator()->get_plugin_generator("mod_facetoface");
		$facetoface = $generator->create_instance((object)[
			'course' => $course->id,
            'approvaloptions' => 'approval_manager'
		]);

		$time = time() + (DAYSECS * 2);
		$session = (object)[
		    'facetoface' => $facetoface->id,
            'sessiondates' => [
                (object)[
                    'timestart' => $time,
                    'timefinish' => $time + 3600,
                    'sessiontimezone' => 'Pacific/Auckland',
                    'roomid' => 0,
                    'assertids' => [],
                ]
            ],
            'timecreated' => time(),
        ];

		$session->id = $generator->add_session($session);

		// once adding the session, re-populate the session date attributes of session
		$sessiondate = $DB->get_record("facetoface_sessions_dates", ['sessionid' => $session->id]);
	    if (!empty($sessiondate)) {
	        $session->sessiondates = [$sessiondate];
        }

		$user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $this->create_signup($user, $session, $facetoface, $course);
        return [$facetoface, $session, $user];
	}

    /**
     * @param stdClass $learner
     * @param stdClass $event
     * @param stdClass $facetoface
     * @param stdClass $course
     */
	private function create_signup(stdClass $learner, stdClass $event, stdClass $facetoface, stdClass $course): void {
	    /** @var mod_facetoface_generator $generator */
	    $generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
	    $refClass = new ReflectionClass($generator);

	    $method = $refClass->getMethod("create_job_assignment_if_not_exists");
	    $method->setAccessible(true);
	    $method->invoke($generator, $learner);

	    $discountcode = 'disc1';
	    $notificationtype = MDL_F2F_BOTH;
	    $statuscode = MDL_F2F_STATUS_REQUESTED;
	    facetoface_user_signup($event, $facetoface, $course, $discountcode, $notificationtype, $statuscode, $learner->id);
	}

    /**
     * Test suite of checking whether there is ical attachment
     * get sent through email or not, when a sign-up request
     * get declined. Since all the ical attachment of an email
     * got logged within table 'facetoface_notification_hist',
     * therefore once all the steps had been finished, the assertion
     * should be looking for the record under the history table and
     * the result should be an empty list of records.
     *
     * @return void
     */
	public function test_sending_decline_email_without_attachment(): void {
		global $USER, $DB;

		$this->setAdminUser();
		$this->resetAfterTest(true);

        list($facetoface, $session, $user) = $this->create_facetoface_with_session_signup();

		$attendee = facetoface_get_attendee($session->id, $user->id);
		facetoface_update_signup_status(
			$attendee->submissionid,
			MDL_F2F_STATUS_DECLINED,
			$USER->id
		);

		$this->redirectMessages();
		facetoface_send_decline_notice($facetoface, $session, $user->id);
		$records = $DB->get_records("facetoface_notification_hist", ['sessionid' => $session->id, 'ical_method' => 'REQUEST']);

		$this->assertEmpty($records);
	}
}