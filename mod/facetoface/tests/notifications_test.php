<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * facetoface module PHPUnit archive test class
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit mod_facetoface_notifications_testcase mod/facetoface/tests/notifications_test.php
 *
 * @package    mod_facetoface
 * @subpackage phpunit
 * @author     Oleg Demeshev <oleg.demeshev@totaralms.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once($CFG->dirroot . '/totara/hierarchy/prefix/position/lib.php');
require_once($CFG->dirroot . '/totara/customfield/field/datetime/define.class.php');
require_once($CFG->dirroot . '/totara/customfield/field/datetime/field.class.php');

class mod_facetoface_notifications_testcase extends advanced_testcase {
    /**
     * PhpUnit fixture method that runs before the test method executes.
     */
    public function setUp() {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Create user
     *
     * @param null|array|\stdClass $record
     * @param null|array|\stdClass $options
     * @return stdClass
     */
    private function createUser($record = null, $options = null) {
        return $this->getDataGenerator()->create_user($record, $options);
    }

    /**
     * Create new course
     *
     * @param null|array|\stdClass $record
     * @param null|array|\stdClass $options
     * @return \stdClass
     */
    private function createCourse($record = null, $options = null)
    {
        return $this->getDataGenerator()->create_course($record, $options);
    }

    /**
     * New seminar date object
     *
     * @param null|int $start Timestamp start
     * @param null $finish Timestamp finish
     * @param int $room Int seminar room id
     * @param string $timezone timezone
     * @return \stdClass
     */
    private function createSeminarDate($start = null, $finish = null, $room = 0, $timezone = 'Pacific/Auckland') {
        $start = $start ?: time();
        $finish = $finish ?: $start + 3600;

        return (object) [
            'sessiontimezone' => $timezone,
            'timestart' => $start,
            'timefinish' => $finish,
            'roomid' => $room,
        ];
    }

    /**
     * Enrol user to a course
     *
     * @param int|\stdClass $user User to enrol
     * @param int|\stdClass $course Course to enrol
     * @param string $role Role to enrol
     * @param null|boolean $success The success of the operation
     * @return $this
     */
    private function enrolUser($user, $course, $role = 'student', &$success = null) {
        $generator = $this->getDataGenerator();

        if (is_object($user)) {
            $user = $user->id;
        }

        if (is_object($course)) {
            $course = $course->id;
        }

        $success = $generator->enrol_user($user, $course, $role);

        return $this;
    }

    /**
     * Returns facetoface plugin generator.
     *
     * @return mod_facetoface_generator
     * @throws coding_exception
     */
    private function getSeminarGenerator() {
        return $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
    }

    /**
     * Create a new seminar
     *
     * @param null|\stdClass|int $course Course object or id (null to create a new course
     * @param string|array $record Record array or seminar name
     * @param null|array $options Options
     * @return \stdClass Seminar object
     * @throws coding_exception
     */
    private function createSeminar($course = null, $record = 'facetoface', $options = null) {
        if (is_null($course)) {
            $course = $this->createCourse();
        }

        if (is_object($course)) {
            $course = $course->id;
        }

        if (is_string($record)) {
            $record = [
                'name' => $record
            ];
        }

        $record = array_merge([
            'course' => $course,
        ], $record);

        return $this->getSeminarGenerator()->create_instance($record, $options);
    }

    /**
     * Add a new seminar room
     *
     * @param \stdClass|int $seminar Seminar object or id
     * @param \stdClass|null $dates Seminar dates
     * @param array $params Parameters ($record) for the created seminar, doesn't require default values
     * @param null|array $options
     * @return mixed
     * @throws coding_exception
     */
    private function addSeminarSession($seminar, $dates = null, array $params = [], $options = null) {
        if (is_object($seminar)) {
            $seminar = $seminar->id;
        }

        $params = array_merge([
            'facetoface' => $seminar,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => $dates ?: [$this->createSeminarDate()],
            'datetimeknown' => '1',
            'mincapacity' => '1',
            'cutoff' => DAYSECS - 60
        ], $params);

        return facetoface_get_session($this->getSeminarGenerator()->add_session($params, $options));
    }

    /**
     * Create site wide seminar room
     *
     * @param string|array $record Record array or a string name
     * @param array $customfields Customfields key value pair array, w\o customfield_ prefix
     * @return stdClass Seminar room
     * @throws coding_exception
     */
    private function createSeminarRoom($record = null, $customfields = []) {
        if (is_null($record)) {
            $record = 'New room ' . rand(1,100000);
        }

        if (is_string($record)) {
            $record = [
                'name' => $record,
            ];
        }

        $room = $this->getSeminarGenerator()
            ->add_site_wide_room($record);

        if (!empty($customfields)) {
            foreach ($customfields as $key => $value) {
                $name = "customfield_$key";
                $room->$name = $value;
            }
        }

        customfield_save_data($room, 'facetofaceroom', 'facetoface_room');
        return $room;
    }

    public function test_cancellation_send_delete_session() {

        $session = $this->f2f_generate_data();

        // Call facetoface_delete_session function for session1.
        $emailsink = $this->redirectMessages();
        facetoface_delete_session($session);
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(4, $emails, 'Wrong no of cancellation notifications sent out.');
    }

    public function test_cancellation_nonesend_delete_session() {

        $session = $this->f2f_generate_data(false);

        // Call facetoface_delete_session function for session1.
        $emailsink = $this->redirectMessages();
        facetoface_delete_session($session);
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(0, $emails, 'Error: cancellation notifications should not be sent out.');
    }

    /**
     * Create course, users, face-to-face, session
     *
     * @param bool $future, time status: future or past, to test cancellation notifications
     * @return \stdClass $session
     */
    private function f2f_generate_data($future = true) {
        global $DB;

        $this->setAdminUser();

        $teacher1 = $this->getDataGenerator()->create_user();
        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();

        $managerja = \totara_job\job_assignment::create_default($manager->id);
        \totara_job\job_assignment::create_default($student1->id, array('managerjaid' => $managerja->id));
        \totara_job\job_assignment::create_default($student2->id, array('managerjaid' => $managerja->id));

        $course = $this->getDataGenerator()->create_course();

        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        $this->getDataGenerator()->enrol_user($teacher1->id, $course->id, $teacherrole->id);
        $this->getDataGenerator()->enrol_user($student1->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course->id, $studentrole->id);

        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $facetofacedata = array(
            'name' => 'facetoface',
            'course' => $course->id
        );
        $facetoface = $facetofacegenerator->create_instance($facetofacedata);

        $sessiondate = new stdClass();
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        if ($future) {
            $sessiondate->timestart = time() + WEEKSECS;
            $sessiondate->timefinish = time() + WEEKSECS + 60;
        } else {
            $sessiondate->timestart = time() - WEEKSECS;
            $sessiondate->timefinish = time() - WEEKSECS + 60;
        }
        $sessiondate->assetids = array();

        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate),
            'mincapacity' => '1',
            'cutoff' => DAYSECS - 60
        );

        $sessionid = $facetofacegenerator->add_session($sessiondata);
        $session = facetoface_get_session($sessionid);
        $session->sessiondates = facetoface_get_session_dates($session->id);

        $discountcode = 'GET15OFF';
        $notificationtype = 1;
        $statuscode = MDL_F2F_STATUS_REQUESTED;

        // Signup user1.
        $emailsink = $this->redirectMessages();
        $this->setUser($student1);
        facetoface_user_signup($session, $facetoface, $course, $discountcode, $notificationtype, $statuscode);
        $emailsink->close();

        // Signup user2.
        $emailsink = $this->redirectMessages();
        $this->setUser($student2);
        facetoface_user_signup($session, $facetoface, $course, $discountcode, $notificationtype, $statuscode);
        $emailsink->close();

        return $session;
    }

    /**
     * @return array of timestamps for use in testing.
     */
    private function create_array_of_times() {
        $times = array(
            'start1' => time() + 1 * DAYSECS,
            'end1' => time() + 1 * DAYSECS + 2 * HOURSECS,
            'other1' => time() + 5 * DAYSECS,
            'start2' => time() + 3 * DAYSECS + 30 * MINSECS,
            'end2' => time() + 4 * DAYSECS + 6 * HOURSECS,
            'other2' => time() - 4 * DAYSECS
        );
        if (date('G', $times['other1']) == 0) {
            $times['other1'] += 1; // Otherwise a different display format will be used for customfield_datetime.
        }
        if (date('G', $times['other2']) == 0) {
            $times['other2'] += 1; // Otherwise a different display format will be used for customfield_datetime.
        }

        return $times;
    }

    /**
     * Test iCal generation.
     */
    public function test_ical_generation() {

        // Reusable human error messages.
        $errors = [
            'dates_dont_match' => 'Session dates don\'t match to the iCal generated dates.',
            'uids_dont_match' => 'iCal UID doesn\'t match to an earlier generated iCal for this date',
            'uids_match' => 'Two different dates have matching UIDs',
            'location_doesnt_match' => 'iCal location doesn\'t match predefined seminar room location',
            'cancelled_count_dont_match' => 'The number of cancelled dates doesn\'t match',
            'description_not_found' => 'iCal description does not contain expected string'
        ];

        $icals = [];

        $students = [
            $this->createUser(),
            $this->createUser(),
        ];

        $course = $this->createCourse();

        foreach ($students as $student) {
            $this->enrolUser($student, $course);
        }

        $seminar = $this->createSeminar($course, 'f2f');

        $room = $this->createSeminarRoom('Site x 1', [
            'locationaddress' => "Address\nTest\nTest2",
        ]);

        $dates = [
            $this->createSeminarDate(WEEKSECS, null, $room->id),
            $this->createSeminarDate(WEEKSECS + DAYSECS * 2),
            $this->createSeminarDate(WEEKSECS + DAYSECS * 5),
        ];

        $session = $this->addSeminarSession($seminar, $dates);

        $icals['original'] = [
            $this->dissect_ical(facetoface_generate_ical($seminar,
                $session,
                MDL_F2F_INVITE,
                $students[0],
                null,[],
                'iCal description must have this text')->content, ['location', 'uid', 'sequence', 'dtstart', 'dtend', 'description']),
            $this->dissect_ical(facetoface_generate_ical($seminar,
                $session,
                MDL_F2F_INVITE,
                $students[1])->content, ['location', 'uid', 'sequence', 'dtstart', 'dtend']),
        ];

        // Checking that iCal contains custom description.
        $this->assertRegExp('/.*iCal description must have this text.*/',
            implode('', $icals['original'][0]->description), $errors['description_not_found']);

        // Checking that dates match for both events.
        $this->assertTrue($this->ical_date_match($icals['original'][0], $session->sessiondates),
            $errors['dates_dont_match']);

        $this->assertTrue($this->ical_date_match($icals['original'][1], $session->sessiondates),
            $errors['dates_dont_match']);

        // UIDs are different.
        $this->assertNotEquals($icals['original'][0]->uid[0], $icals['original'][0]->uid[1],
            $errors['uids_match']);

        $this->assertNotEquals($icals['original'][0]->uid[ 1], $icals['original'][0]->uid[2],
        $errors['uids_match']);

        $this->assertNotEquals($icals['original'][0]->uid[0], $icals['original'][0]->uid[2],
            $errors['uids_match']);

        // Location matches the generated room location.
        $this->assertEquals('Site x 1\, Address,Test,Test2', $icals['original'][0]->location[0],
            $errors['location_doesnt_match']);

        // Need to cancel seminar date, in the middle!
        facetoface_save_dates($session, [$session->sessiondates[0], $session->sessiondates[2]]);
        $old = $session->sessiondates;
        $session = facetoface_get_session($session->id);

        $icals['session_date_removed'] = [
            $this->dissect_ical(facetoface_generate_ical($seminar,
                $session,
                MDL_F2F_INVITE,
                $students[0],
                null,
                $old)->content, ['location', 'uid', 'sequence', 'dtstart', 'dtend', 'status']),
            $this->dissect_ical(facetoface_generate_ical($seminar,
                $session,
                MDL_F2F_INVITE,
                $students[1],
                null,
                $old)->content, ['location', 'uid', 'sequence', 'dtstart', 'dtend', 'status']),
        ];

        // Will match old dates as it will include notification for a cancelled date.
        $this->assertTrue($this->ical_date_match($icals['session_date_removed'][0], $old),
            $errors['dates_dont_match']);

        $this->assertTrue($this->ical_date_match($icals['session_date_removed'][1], $old),
            $errors['dates_dont_match']);

        // Must include ONE cancelled date.
        $this->assertCount(1, $icals['session_date_removed'][0]->status,
            $errors['cancelled_count_dont_match']);

        // Match that uids are the same, however order is different as it first includes dates to create or
        // update and then cancelled dates.
        $this->assertEquals($icals['session_date_removed'][0]->uid[0], $icals['original'][0]->uid[0],
            $errors['uids_dont_match']);
        $this->assertEquals($icals['session_date_removed'][0]->uid[1], $icals['original'][0]->uid[2],
            $errors['uids_dont_match']);
        $this->assertEquals($icals['session_date_removed'][0]->uid[2], $icals['original'][0]->uid[1],
            $errors['uids_dont_match']);

        // Adding a date and removing a date and modifying a date.
        $old = $session->sessiondates;
        array_shift($session->sessiondates);
        facetoface_save_dates($session, array_merge($session->sessiondates, [
            $added = $this->createSeminarDate(time() + YEARSECS, null, $room->id)
        ]));
        $session = facetoface_get_session($session->id);

        $icals['session_date_removed_and_added'] = [
            $this->dissect_ical(facetoface_generate_ical($seminar,
                $session,
                MDL_F2F_INVITE,
                $students[0],
                null,
                $old)->content, ['location', 'uid', 'sequence', 'dtstart', 'dtend', 'status']),
            $this->dissect_ical(facetoface_generate_ical($seminar,
                $session,
                MDL_F2F_INVITE,
                $students[1],
                null,
                $old)->content, ['location', 'uid', 'sequence', 'dtstart', 'dtend', 'status']),
        ];

        // Will match old dates and a new date as it will include notification for a cancelled and added dates.
        $this->assertTrue($this->ical_date_match($icals['session_date_removed_and_added'][0],
            array_merge($old, [$added])), $errors['dates_dont_match']);

        $this->assertTrue($this->ical_date_match($icals['session_date_removed_and_added'][1],
            array_merge($old, [$added])), $errors['dates_dont_match']);

        // Must include ONE cancelled date.
        $this->assertCount(1, $icals['session_date_removed_and_added'][0]->status,
            $errors['cancelled_count_dont_match']);

        // Match that uids are the same, however order is different as it first includes dates to create or
        // update and then cancelled dates. UID[1] should be unique and not match anything before.
        $this->assertEquals($icals['session_date_removed_and_added'][0]->uid[0],
            $icals['session_date_removed'][0]->uid[1], $errors['uids_dont_match']);

        $this->assertEquals($icals['session_date_removed_and_added'][0]->uid[2],
            $icals['session_date_removed'][0]->uid[0], $errors['uids_dont_match']);

        // Location matches the generated room location.
        $this->assertEquals('Site x 1\, Address,Test,Test2',
            $icals['session_date_removed_and_added'][0]->location[2], $errors['location_doesnt_match']);

        // User 1 cancelled.
        $this->mock_status_change($students[0]->id, $session->id);

        $icals['first_user_status_changed'] = [
            $this->dissect_ical(facetoface_generate_ical($seminar,
                $session,
                MDL_F2F_CANCEL,
                $students[0])->content, ['location', 'uid', 'sequence', 'dtstart', 'dtend', 'status']),
            $this->dissect_ical(facetoface_generate_ical($seminar,
                $session,
                MDL_F2F_INVITE,
                $students[1])->content, ['location', 'uid', 'sequence', 'dtstart', 'dtend']),
        ];

        // Will match session dates as the dates shouldn't have changed.
        $this->assertTrue($this->ical_date_match($icals['first_user_status_changed'][0],
            $session->sessiondates), $errors['dates_dont_match']);

        $this->assertTrue($this->ical_date_match($icals['first_user_status_changed'][1],
            $session->sessiondates), $errors['dates_dont_match']);

        // Uids shoud stay the same.
        $this->assertEquals($icals['first_user_status_changed'][0]->uid[0],
            $icals['session_date_removed_and_added'][0]->uid[0], $errors['uids_dont_match']);

        $this->assertEquals($icals['first_user_status_changed'][0]->uid[1],
            $icals['session_date_removed_and_added'][0]->uid[1], $errors['uids_dont_match']);

        // Both dates must be cancelled.
        $this->assertCount(2, $icals['first_user_status_changed'][0]->status,
            $errors['cancelled_count_dont_match']);

        $this->resetAfterTest();
    }

    /**
     * Test sending notifications when "facetoface_oneemailperday" is enabled
     */
    public function test_oneperday_ical_generation() {
        // Reusable human error messages.
        $errors = [
            'dates_dont_match' => 'Session dates don\'t match to the iCal generated dates.',
            'uids_dont_match' => 'iCal UID doesn\'t match to an earlier generated iCal for this date',
            'uids_match' => 'Two different dates have matching UIDs',
            'cancelled_count_dont_match' => 'The number of cancelled dates doesn\'t match',
        ];

        $this->setAdminUser();
        set_config('facetoface_oneemailperday', true);

        $this->enrolUser($student = $this->createUser(),
                         $course = $this->createCourse());

        $seminar = $this->createSeminar($course);

        $session = $this->addSeminarSession($seminar, $dates = [
            $this->createSeminarDate(time() + WEEKSECS),
            $this->createSeminarDate(time() + WEEKSECS + DAYSECS),
        ]);

        $emailsink = $this->redirectMessages();
        facetoface_user_import($course, $seminar, $session, $student->id);
        $emailsink->close();

        $preemails = $emailsink->get_messages();

        foreach($preemails as $preemail) {
            $this->assertContains("This is to confirm that you are now booked", $preemail->fullmessagehtml);
        }

        $icals = [
            'original' => [
                $this->dissect_ical(facetoface_generate_ical($seminar, $session, MDL_F2F_INVITE,
                    $student, $session->sessiondates[0])->content,
                    ['location', 'uid', 'sequence', 'dtstart', 'dtend']),
                $this->dissect_ical(facetoface_generate_ical($seminar, $session, MDL_F2F_INVITE,
                    $student, $session->sessiondates[1])->content,
                    ['location', 'uid', 'sequence', 'dtstart', 'dtend']),
            ]
        ];

        // Dates match to seminar dates.
        $this->assertTrue($this->ical_date_match($icals['original'][0], $session->sessiondates[0]),
            $errors['dates_dont_match']);
        $this->assertTrue($this->ical_date_match($icals['original'][1], $session->sessiondates[1]),
            $errors['dates_dont_match']);

        // Uids do not match.
        $this->assertNotEquals($icals['original'][0]->uid[0], $icals['original'][1]->uid[0], $errors['uids_match']);

        // Editing one date and cancelling the second one.
        $dates = $session->sessiondates;
        $new = [$this->createSeminarDate(time() + 2 * WEEKSECS)];

        // Preserving the id of the edited date, otherwise it will be treated as a new date.
        $new[0]->id = $dates[0]->id;

        $emailsink = $this->redirectMessages();
        facetoface_update_session($session, $new);
        $session = facetoface_get_session($session->id);

        // Send message.
        facetoface_send_datetime_change_notice($seminar, $session, $student->id, $dates);
        $emailsink->close();

        $icals['date_edited_and_cancelled'] = [
            $this->dissect_ical(facetoface_generate_ical($seminar, $session, MDL_F2F_INVITE,
                $student, $new[0])->content,
                ['location', 'uid', 'sequence', 'dtstart', 'dtend']),
            $this->dissect_ical(facetoface_generate_ical($seminar, $session, MDL_F2F_CANCEL,
                $student, $dates[1])->content,
                ['location', 'uid', 'sequence', 'dtstart', 'dtend', 'status']),
        ];

        // Dates match to seminar dates.
        $this->assertTrue($this->ical_date_match($icals['date_edited_and_cancelled'][0], $new[0]),
            $errors['dates_dont_match']);
        $this->assertTrue($this->ical_date_match($icals['date_edited_and_cancelled'][1], $dates[1]),
            $errors['dates_dont_match']);

        // Checking that UIDs haven't changed.
        $this->assertEquals($icals['original'][0]->uid[0],$icals['date_edited_and_cancelled'][0]->uid[0],
            $errors['uids_dont_match']);
        $this->assertEquals($icals['original'][1]->uid[0],$icals['date_edited_and_cancelled'][1]->uid[0],
            $errors['uids_dont_match']);

        // Second date actually has been cancelled.
        $this->assertCount(1, $icals['date_edited_and_cancelled'][1]->status,
            $errors['cancelled_count_dont_match']);

        $emails = $emailsink->get_messages();
        $emailsink->close();
        $this->assertContains("The session you are booked on (or on the waitlist) has changed:", $emails[0]->fullmessagehtml);
        $this->assertContains("BOOKING CANCELLED", $emails[1]->fullmessagehtml);

        // Now test cancelling the session.
        $emailsink = $this->redirectMessages();
        $result = facetoface_cancel_session($session = facetoface_get_session($session->id), null);
        $this->assertTrue($result);

        // One email has been sent and it contains all the required data.
        $this->assertCount(1, $messages = $emailsink->get_messages());
        $message = $messages[0];

        $this->assertContains('Seminar event cancellation', $message->subject);
        $this->assertContains('This is to advise that the following session has been cancelled',
            $message->fullmessagehtml);
        $this->assertContains('Course:   Test course 1', $message->fullmessagehtml);
        $this->assertContains('Seminar:   facetoface', $message->fullmessagehtml);
        $this->assertContains('Date(s) and location(s):', $message->fullmessagehtml);

        $session = facetoface_get_session($session->id);
        $this->assertEquals(1, $session->cancelledstatus);

        $this->resetAfterTest(true);
    }

    /**
     * Simplified parse $ical content and return values of requested property
     * @param string $content
     * @param string $name
     * @return array of values
     */
    private function get_ical_values($content, $name) {
        $strings = explode("\n", $content);
        $result = array();
        $isdecription = false;
        foreach($strings as $string) {
            // Multi-line description workaround.
            if ($isdecription) {
                if (strpos($string, 'SUMMARY:') !== 0) {
                    $result[] = trim($string);
                    continue;
                }
                $isdecription = false;
            }

            if (strpos($string, $name.':') === 0) {
                $result[] = trim(substr($string, strlen($name)+1));
                // Multi-line description workaround.
                if ($name == 'DESCRIPTION') {
                    $isdecription = true;
                }
            }
        }
        return $result;
    }

    /**
     * Search for a matching date from an ical file in the array of seminar event dates.
     *
     * @param \stdClass $needle dissected ical \stdClass
     * @param array|\stdClass $haystack seminar event date(s)
     * @return bool
     */
    private function ical_date_match($needle, $haystack) {

        // Normalizing needle(s).
        if (!isset($needle->dtstart) || !isset($needle->dtend)
            || count($needle->dtstart) != count($needle->dtend)) {
            return false;
        }

        $dates = [];

        for ($i = 0; $i < count($needle->dtstart); $i++) {
            $dates[] = (object) [
                'dtstart' => $needle->dtstart[$i],
                'dtend' => $needle->dtend[$i],
            ];
        }

        // Normalizing haystack.
        $haystack = array_map(function($item) {
            // We are expecting a seminar date to be passed here, so keys will be different.
            return (object) [
                'dtstart' => facetoface_ical_generate_timestamp($item->timestart),
                'dtend' => facetoface_ical_generate_timestamp($item->timefinish),
            ];
        }, !is_array($haystack) ? [$haystack] : $haystack);

        // Looking that all dates present in the haystack.
        $dates = array_filter($dates, function ($date) use (&$haystack) {
            foreach ($haystack as $key => $piece) {
                if ($date->dtstart == $piece->dtstart &&
                    $date->dtend == $piece->dtend) {
                    unset($haystack[$key]);
                    return false;
                }
            }

            return true;
        });

        // Return true only if we matched all needles to the haystack and there is no more needles (dates) left there.
        return !!(empty($dates) & empty($haystack));
    }

    /**
     * Convert iCal file to a nice readable object of arrays.
     *
     * @param string $ical iCal file content
     * @param array $filter filter returned iCal items
     * @param bool $asobj return as object with lower-cased properties or as array of arrays
     * @return array|\stdClass
     */
    private function dissect_ical($ical, $filter = [], $asobj = true) {
        $keys = [
            'BEGIN',
            'METHOD',
            'PRODID',
            'VERSION',
            'UID',
            'SEQUENCE',
            'LOCATION',
            'STATUS',
            'SUMMARY',
            'DESCRIPTION',
            'CLASS',
            'LAST-MODIFIED',
            'DTSTAMP',
            'DTSTART',
            'DTEND',
            'CATEGORIES',
            'END',
        ];

        if (!empty($filter)) {
            $filter = array_map('strtoupper', $filter);
            $keys = array_filter($keys, function($item) use ($filter) {
                return in_array($item, $filter);
            });
        }

        // Converting the keys array to the format [$key[0]=>$key[0], ...]
        $keys = array_combine($asobj ? array_map('strtolower', $keys) : $keys, $keys);

        $keys = array_map(function($item) use ($ical) {
            return $this->get_ical_values($ical, $item);
        }, $keys);

        return $asobj ? (object) $keys : $keys;
    }

    /**
     * Add superceeded record to signup status to mock user status change
     * @param int $userid
     * @param int $sessionid
     */
    private function mock_status_change($userid, $sessionid) {
        global $DB;

        $signupid = $DB->get_field('facetoface_signups', 'id', array('userid' => $userid, 'sessionid' => $sessionid));
        if (!$signupid) {
            $signupmock = new stdClass();
            $signupmock->userid = $userid;
            $signupmock->sessionid = $sessionid;
            $signupmock->notificationtype = 3;
            $signupmock->bookedby = 2;
            $signupid = $DB->insert_record('facetoface_signups', $signupmock);
        }

        $mock = new stdClass();
        $mock->superceded = 1;
        $mock->statuscode = 0;
        $mock->signupid = $signupid;
        $mock->createdby = 2;
        $mock->timecreated = time();
        $DB->insert_record('facetoface_signups_status', $mock);
    }

    /**
     * Tests the facetoface_notification_loop_session_placeholders function alone, without relying on proper working
     * of functions for saving to and retrieving from database.
     */
    public function test_facetoface_notification_loop_session_placeholders() {
        $this->resetAfterTest(true);

        // We'll use the server timezone otherwise this test will fail in some parts of the world and not others.
        $timezone = core_date::get_server_timezone();

        $times = $this->create_array_of_times();

        $msg = "Testing with non-saved session.[#sessions] Start time is [session:starttime]. Finish time is [session:finishtime].[/sessions] That is all.";
        $dataset['sessions'] = array();
        $session = new stdClass();
        $sessiondate = new stdClass();
        $sessiondate->sessiontimezone = $timezone;
        $sessiondate->timestart = $times['start1'];
        $sessiondate->timefinish = $times['end1'];
        $session->sessiondates = array($sessiondate);
        $replacedmsg = facetoface_notification_loop_session_placeholders($msg, $session);
        $expectedstart = userdate($times['start1'], get_string('strftimetime', 'langconfig'), $timezone);
        $expectedend = userdate($times['end1'], get_string('strftimetime', 'langconfig'), $timezone);
        $this->assertEquals("Testing with non-saved session. Start time is ".$expectedstart.". Finish time is ".$expectedend.". That is all.", $replacedmsg);
    }

    /**
     * Tests the facetoface_notification_loop_session_placeholders function alone, without relying on proper working
     * of functions for saving to and retrieving from database. In this case, there are two lots of tags.
     */
    public function test_facetoface_notification_loop_session_placeholders_double() {
        $this->resetAfterTest(true);

        // We'll use the server timezone otherwise this test will fail in some parts of the world and not others.
        $timezone = core_date::get_server_timezone();

        $times = $this->create_array_of_times();

        $msg = "Testing with non-saved session.[#sessions]Start time is [session:starttime]. Finish time is [session:finishtime].\n[/sessions]";
        $msg .= "[#sessions]Start date is [session:startdate]. Finish date is [session:finishdate].\n[/sessions]";
        $msg .= "That is all.";
        $dataset['sessions'] = array();
        $session = new stdClass();
        $sessiondate1 = new stdClass();
        $sessiondate1->sessiontimezone = $timezone;
        $sessiondate1->timestart = $times['start1'];
        $sessiondate1->timefinish = $times['end1'];
        $sessiondate2 = new stdClass();
        $sessiondate2->sessiontimezone = $timezone;
        $sessiondate2->timestart = $times['start2'];;
        $sessiondate2->timefinish = $times['end2'];
        $session->sessiondates = array($sessiondate1, $sessiondate2);
        $replacedmsg = facetoface_notification_loop_session_placeholders($msg, $session);

        // Get strings for display of dates and times in email.
        $startdate1 = userdate($times['start1'], get_string('strftimedate', 'langconfig'), $timezone);
        $starttime1 = userdate($times['start1'], get_string('strftimetime', 'langconfig'), $timezone);
        $enddate1 = userdate($times['end1'], get_string('strftimedate', 'langconfig'), $timezone);
        $endtime1 = userdate($times['end1'], get_string('strftimetime', 'langconfig'), $timezone);
        $startdate2 = userdate($times['start2'], get_string('strftimedate', 'langconfig'), $timezone);
        $starttime2 = userdate($times['start2'], get_string('strftimetime', 'langconfig'), $timezone);
        $enddate2 = userdate($times['end2'], get_string('strftimedate', 'langconfig'), $timezone);
        $endtime2 = userdate($times['end2'], get_string('strftimetime', 'langconfig'), $timezone);

        $expectedmsg = "Testing with non-saved session.";
        $expectedmsg .= "Start time is ".$starttime1.". Finish time is ".$endtime1.".\n";
        $expectedmsg .= "Start time is ".$starttime2.". Finish time is ".$endtime2.".\n";
        $expectedmsg .= "Start date is ".$startdate1.". Finish date is ".$enddate1.".\n";
        $expectedmsg .= "Start date is ".$startdate2.". Finish date is ".$enddate2.".\n";
        $expectedmsg .= "That is all.";
        $this->assertEquals($expectedmsg, $replacedmsg);
    }
 public function test_facetoface_notification_loop_session_placeholders_no_session() {
        $this->resetAfterTest(true);

        $msg = "Testing with non-saved session. A[#sessions]Start time is [session:starttime]. Finish time is [session:finishtime].\n[/sessions]A";
        $msg .= " I repeat: [#sessions]Start date is [session:startdate]. Finish date is [session:finishdate].\n[/sessions]";
        $msg .= " That is all.";

        $session = new stdClass();
        $session->sessiondates = array();
        $replacedmsg = facetoface_notification_loop_session_placeholders($msg, $session);
        $expectedmsg = "Testing with non-saved session. ALocation and time to be announced later.A I repeat: Location and time to be announced later. That is all.";
        $this->assertEquals($expectedmsg, $replacedmsg);
    }

    /**
     * Tests facetoface_notification_loop_session_placeholders function with data returned by functions generally used
     * to retrieve facetoface session data.
     */
    public function test_facetoface_notification_loop_session_placeholders_with_session() {
        $this->resetAfterTest(true);
        global $DB;

        // We'll use the server timezone otherwise this test will fail in some parts of the world and not others.
        $timezone = core_date::get_server_timezone();

        $times = $this->create_array_of_times();

        $course = $this->getDataGenerator()->create_course();

        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $facetofacedata = array(
            'name' => 'facetoface',
            'course' => $course->id
        );
        $facetoface = $facetofacegenerator->create_instance($facetofacedata);

        // Create a room to add to a session date. Ideally this would use an existing function rather than
        // a direct db insert - none exists while writing this test.
        $room = new stdClass();
        $room->name = 'Room One';
        $room->capacity = 20;
        $room->timemodified = time();
        $room->timecreated = $room->timemodified;
        $room->id = $DB->insert_record('facetoface_room', $room);

        $sessiondate1 = new stdClass();
        $sessiondate1->sessiontimezone = $timezone;
        $sessiondate1->timestart = $times['start1'];
        $sessiondate1->timefinish = $times['end1'];
        $sessiondate1->roomid = $room->id;
        $sessiondate1->assetids = array();

        $sessiondate2 = new stdClass();
        $sessiondate2->sessiontimezone = $timezone;
        $sessiondate2->timestart = $times['start2'];
        $sessiondate2->timefinish = $times['end2'];
        $sessiondate2->assetids = array();

        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate1, $sessiondate2),
            'mincapacity' => '1',
            'cutoff' => DAYSECS - 60
        );

        $sessionid = $facetofacegenerator->add_session($sessiondata);

        $session = $DB->get_record('facetoface_sessions', array('id' => $sessionid));
        $session->sessiondates = facetoface_get_session_dates($session->id);
        $rooms = facetoface_get_session_rooms($session->id);

        $msg = "The details for each session:\n";
        $msg .= "[#sessions]* Start time of [session:startdate] at [session:starttime]";
        $msg .= " and end time of [session:finishdate] at [session:finishtime] ([session:timezone]).\n";
        $msg .= "  Location is [session:room:name].\n";
        $msg .= "[/sessions]";
        $msg .= "Those are all the details.";

        $replacedmsg = facetoface_notification_loop_session_placeholders($msg, $session, $rooms);

        // Get strings for display of dates and times in email.
        $startdate1 = userdate($times['start1'], get_string('strftimedate', 'langconfig'), $timezone);
        $starttime1 = userdate($times['start1'], get_string('strftimetime', 'langconfig'), $timezone);
        $enddate1 = userdate($times['end1'], get_string('strftimedate', 'langconfig'), $timezone);
        $endtime1 = userdate($times['end1'], get_string('strftimetime', 'langconfig'), $timezone);
        $startdate2 = userdate($times['start2'], get_string('strftimedate', 'langconfig'), $timezone);
        $starttime2 = userdate($times['start2'], get_string('strftimetime', 'langconfig'), $timezone);
        $enddate2 = userdate($times['end2'], get_string('strftimedate', 'langconfig'), $timezone);
        $endtime2 = userdate($times['end2'], get_string('strftimetime', 'langconfig'), $timezone);

        $expectedmsg = "The details for each session:\n";
        $expectedmsg .= "* Start time of ".$startdate1." at ".$starttime1." and end time of ".$enddate1." at ".$endtime1." (".$timezone.").\n";
        $expectedmsg .= "  Location is Room One.\n";
        $expectedmsg .= "* Start time of ".$startdate2." at ".$starttime2." and end time of ".$enddate2." at ".$endtime2." (".$timezone.").\n";
        $expectedmsg .= "  Location is .\n";
        $expectedmsg .= "Those are all the details.";

        $this->assertEquals($expectedmsg, $replacedmsg);
    }

    public function test_facetoface_notification_loop_session_placeholders_room_customfields() {
        $this->resetAfterTest(true);
        global $DB;

        // We'll use the server timezone otherwise this test will fail in some parts of the world and not others.
        $timezone = core_date::get_server_timezone();

        $times = $this->create_array_of_times();

        $course = $this->getDataGenerator()->create_course();

        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $facetofacedata = array(
            'name' => 'facetoface',
            'course' => $course->id
        );
        $facetoface = $facetofacegenerator->create_instance($facetofacedata);

        /** @var totara_customfield_generator $customfieldgenerator */
        $customfieldgenerator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');

        $customfields = array();

        // Create a datetime customfield.
        $cfsettings = array('Room Date' => array('shortname' => 'roomdate', 'startyear' => 2015, 'endyear' => 2030));
        $customfields += $customfieldgenerator->create_datetime('facetoface_room', $cfsettings);

        // Create a text customfield.
        $cfsettings = array('Room Text'); // Will have the shortname of RoomText
        $customfields += $customfieldgenerator->create_text('facetoface_room', $cfsettings);

        // Create a location customfield.
        $cfsettings = array('Room Location' => array('shortname' => 'roomlocation')); // Will have the shortname of RoomText
        $customfields += $customfieldgenerator->create_location('facetoface_room', $cfsettings);

        // Create a room to add to a session date.
        $room1 = new stdClass();
        $room1->name = 'Room One';
        $room1->capacity = 20;
        $room1->timemodified = time();
        $room1->timecreated = $room1->timemodified;
        $room1->id = $DB->insert_record('facetoface_room', $room1);

        $customfieldgenerator->set_datetime($room1, $customfields['Room Date'], $times['other1'], 'facetofaceroom', 'facetoface_room');
        $customfieldgenerator->set_text($room1, $customfields['Room Text'], 'Details about the room', 'facetofaceroom', 'facetoface_room');
        $location1 = new stdClass();
        $customfieldgenerator->set_location_address($room1, $customfields['Room Location'], '150 Willis Street', 'facetofaceroom', 'facetoface_room');

        // Create another room to add to a session date.
        $room2 = new stdClass();
        $room2->name = 'Room Two';
        $room2->capacity = 40;
        $room2->timemodified = time();
        $room2->timecreated = $room2->timemodified;
        $room2->id = $DB->insert_record('facetoface_room', $room2);

        $customfieldgenerator->set_datetime($room2, $customfields['Room Date'], $times['other2'], 'facetofaceroom', 'facetoface_room');

        // Set up the face-to-face session.
        $sessiondate1 = new stdClass();
        $sessiondate1->sessiontimezone = $timezone;
        $sessiondate1->timestart = $times['start1'];
        $sessiondate1->timefinish = $times['end1'];
        $sessiondate1->roomid = $room1->id;
        $sessiondate1->assetids = array();

        $sessiondate2 = new stdClass();
        $sessiondate2->sessiontimezone = $timezone;
        $sessiondate2->timestart = $times['start2'];
        $sessiondate2->timefinish = $times['end2'];
        $sessiondate2->roomid = $room2->id;
        $sessiondate2->assetids = array();

        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate1, $sessiondate2),
            'mincapacity' => '1',
            'cutoff' => DAYSECS - 60
        );

        $sessionid = $facetofacegenerator->add_session($sessiondata);

        // Now get all the date we've created.
        $session = $DB->get_record('facetoface_sessions', array('id' => $sessionid));
        $session->sessiondates = facetoface_get_session_dates($session->id);
        $rooms = facetoface_get_session_rooms($session->id);
        // Get data for room custom fields.
        $roomcustomfields = array();
        foreach($rooms as $room) {
            $roomcustomfields[$room->id] = customfield_get_data($room, 'facetoface_room', 'facetofaceroom', false);
        }

        $msg = "The details for each session:\n";
        $msg .= "[#sessions]";
        $msg .= "[session:room:name] has custom date of [session:room:cf_roomdate].\n";
        $msg .= "[session:room:name] has custom text of [session:room:cf_RoomText].\n";
        $msg .= "[session:room:name] has a custom location of [session:room:cf_roomlocation].\n";
        $msg .= "[/sessions]";
        $msg .= "Those are all the details.";

        $replacedmsg = facetoface_notification_loop_session_placeholders($msg, $session, $rooms, $roomcustomfields);

        $expectedmsg = "The details for each session:\n";
        $expectedmsg .= "Room One has custom date of ".userdate($times['other1'], get_string('strftimedaydatetime', 'langconfig'), $timezone).".\n";
        $expectedmsg .= "Room One has custom text of Details about the room.\n";
        $expectedmsg .= "Room One has a custom location of 150 Willis Street.\n";
        $expectedmsg .= "Room Two has custom date of ".userdate($times['other2'], get_string('strftimedaydatetime', 'langconfig'), $timezone).".\n";
        $expectedmsg .= "Room Two has custom text of .\n";
        $expectedmsg .= "Room Two has a custom location of .\n";
        $expectedmsg .= "Those are all the details.";

        $this->assertEquals($expectedmsg, $replacedmsg);
    }

    /**
     * Tests the function facetoface_notification_substitute_deprecated_placeholders, ensuring that the values within
     * the 'location' and 'building' custom fields are substituted where the [session:location]
     * and [session:venue] placeholders are found.
     */
    public function test_facetoface_notification_substitute_deprecated_placeholders_with_customfield_values() {
        $this->resetAfterTest(true);
        global $DB;

        // We'll use the server timezone otherwise this test will fail in some parts of the world and not others.
        $timezone = core_date::get_server_timezone();

        $times = $this->create_array_of_times();

        $course = $this->getDataGenerator()->create_course();

        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        /** @var totara_customfield_generator $customfieldgenerator */
        $customfieldgenerator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');

        $locationfieldid = $DB->get_field('facetoface_room_info_field', 'id', array('shortname' => 'location'));
        $buildingfieldid = $DB->get_field('facetoface_room_info_field', 'id', array('shortname' => 'building'));

        $facetofacedata = array(
            'name' => 'facetoface',
            'course' => $course->id
        );
        $facetoface = $facetofacegenerator->create_instance($facetofacedata);

        // Create a room to add to a session date. Ideally this would use an existing function rather than
        // a direct db insert - none exists while writing this test.
        $room1 = new stdClass();
        $room1->name = 'Room One';
        $room1->capacity = 20;
        $room1->timemodified = time();
        $room1->timecreated = $room1->timemodified;
        $room1->id = $DB->insert_record('facetoface_room', $room1);

        $customfieldgenerator->set_location_address($room1, $locationfieldid, '150 Willis Street', 'facetofaceroom', 'facetoface_room');
        $customfieldgenerator->set_text($room1, $buildingfieldid, 'Building One', 'facetofaceroom', 'facetoface_room');

        // Create a room to add to a session date. Ideally this would use an existing function rather than
        // a direct db insert - none exists while writing this test.
        $room2 = new stdClass();
        $room2->name = 'Room Two';
        $room2->capacity = 20;
        $room2->timemodified = time();
        $room2->timecreated = $room2->timemodified;
        $room2->id = $DB->insert_record('facetoface_room', $room2);

        $sessiondate1 = new stdClass();
        $sessiondate1->sessiontimezone = $timezone;
        $sessiondate1->timestart = $times['start1'];
        $sessiondate1->timefinish = $times['end1'];
        $sessiondate1->roomid = $room1->id;
        $sessiondate1->assetids = array();

        $sessiondate2 = new stdClass();
        $sessiondate2->sessiontimezone = $timezone;
        $sessiondate2->timestart = $times['start2'];
        $sessiondate2->timefinish = $times['end2'];
        $sessiondate2->roomid = $room2->id;
        $sessiondate2->assetids = array();

        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate1, $sessiondate2),
            'mincapacity' => '1',
            'cutoff' => DAYSECS - 60
        );

        $sessionid = $facetofacegenerator->add_session($sessiondata);

        $session = $DB->get_record('facetoface_sessions', array('id' => $sessionid));
        $session->sessiondates = facetoface_get_session_dates($session->id);
        $rooms = facetoface_get_session_rooms($session->id);
        $roomcustomfields = array();
        foreach($rooms as $room) {
            $roomcustomfields[$room->id] = customfield_get_data($room, 'facetoface_room', 'facetofaceroom', false);
        }

        $msg = "Using the old deprecated subsitutions ";
        $msg .= "Room name: [session:room] ";
        $msg .= "Building name: [session:venue]. ";
        $msg .= "Location: [session:location]. ";
        $msg .= "Those are all the details.";

        $replacedmsg = facetoface_notification_substitute_deprecated_placeholders($msg, $session, $rooms, $roomcustomfields);

        $expectedmsg = "Using the old deprecated subsitutions ";
        $expectedmsg .= "Room name: Room One ";
        $expectedmsg .= "Building name: Building One. ";
        $expectedmsg .= "Location: 150 Willis Street. ";
        $expectedmsg .= "Those are all the details.";

        $this->assertEquals($expectedmsg, $replacedmsg);
    }

    /**
     * Tests the function facetoface_notification_substitute_deprecated_placeholders where there are no values for
     * the 'location' and 'building' custom fields.  In these cases, the [session:location] and
     * [session:venue] placeholders should be replaced with empty strings.
     */
    public function test_facetoface_notification_substitute_deprecated_placeholders_with_customfields_empty() {
        $this->resetAfterTest(true);
        global $DB;

        // We'll use the server timezone otherwise this test will fail in some parts of the world and not others.
        $timezone = core_date::get_server_timezone();

        $times = $this->create_array_of_times();

        $course = $this->getDataGenerator()->create_course();

        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        /** @var totara_customfield_generator $customfieldgenerator */
        $customfieldgenerator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');

        $facetofacedata = array(
            'name' => 'facetoface',
            'course' => $course->id
        );
        $facetoface = $facetofacegenerator->create_instance($facetofacedata);

        // Create a room to add to a session date. Ideally this would use an existing function rather than
        // a direct db insert - none exists while writing this test.
        $room1 = new stdClass();
        $room1->name = 'Room One';
        $room1->capacity = 20;
        $room1->timemodified = time();
        $room1->timecreated = $room1->timemodified;
        $room1->id = $DB->insert_record('facetoface_room', $room1);

        // Create a room to add to a session date. Ideally this would use an existing function rather than
        // a direct db insert - none exists while writing this test.
        $room2 = new stdClass();
        $room2->name = 'Room Two';
        $room2->capacity = 20;
        $room2->timemodified = time();
        $room2->timecreated = $room2->timemodified;
        $room2->id = $DB->insert_record('facetoface_room', $room2);

        $sessiondate1 = new stdClass();
        $sessiondate1->sessiontimezone = $timezone;
        $sessiondate1->timestart = $times['start1'];
        $sessiondate1->timefinish = $times['end1'];
        $sessiondate1->roomid = $room1->id;
        $sessiondate1->assetids = array();

        $sessiondate2 = new stdClass();
        $sessiondate2->sessiontimezone = $timezone;
        $sessiondate2->timestart = $times['start2'];
        $sessiondate2->timefinish = $times['end2'];
        $sessiondate2->roomid = $room2->id;
        $sessiondate2->assetids = array();

        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate1, $sessiondate2),
            'mincapacity' => '1',
            'cutoff' => DAYSECS - 60
        );

        $sessionid = $facetofacegenerator->add_session($sessiondata);

        $session = $DB->get_record('facetoface_sessions', array('id' => $sessionid));
        $session->sessiondates = facetoface_get_session_dates($session->id);
        $rooms = facetoface_get_session_rooms($session->id);
        $roomcustomfields = array();
        foreach($rooms as $room) {
            $roomcustomfields[$room->id] = customfield_get_data($room, 'facetoface_room', 'facetofaceroom', false);
        }

        $msg = "Using the old deprecated subsitutions ";
        $msg .= "Room name: [session:room] ";
        $msg .= "Building name: [session:venue]. ";
        $msg .= "Location: [session:location]. ";
        $msg .= "Those are all the details.";

        $replacedmsg = facetoface_notification_substitute_deprecated_placeholders($msg, $session, $rooms, $roomcustomfields);

        $expectedmsg = "Using the old deprecated subsitutions ";
        $expectedmsg .= "Room name: Room One ";
        $expectedmsg .= "Building name: . ";
        $expectedmsg .= "Location: . ";
        $expectedmsg .= "Those are all the details.";

        $this->assertEquals($expectedmsg, $replacedmsg);
    }

    /**
     * Tests the output of facetoface_get_default_notifications.
     */
    public function test_facetoface_get_default_notifications() {
        $this->resetAfterTest(true);
        global $DB;

        $course = $this->getDataGenerator()->create_course();

        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $facetofacedata = array(
            'name' => 'facetoface',
            'course' => $course->id
        );
        $facetoface = $facetofacegenerator->create_instance($facetofacedata);

        list($notifications, $missing) = facetoface_get_default_notifications($facetoface->id);

        // Get templates.
        $templaterecords = $DB->get_records('facetoface_notification_tpl');

        // There should be no missing notifications.
        $this->assertEmpty($missing);

        // The number of default notifications should equal the number of templates.
        $this->assertEquals(count($templaterecords), count($notifications));
    }

    /**
     * Tests values returned by facetoface_notification_get_templates_with_old_placeholders.
     */
    public function test_facetoface_notification_get_templates_with_old_placeholders() {
        $this->resetAfterTest(true);
        global $DB;

        $oldnotifications = facetoface_notification_get_templates_with_old_placeholders();
        // There should be no oldplaceholders in templates on a newly installed 9.0 site.
        // We expect an empty array, rather than false or null.
        $this->assertEquals(array(), $oldnotifications);

        // A template with the placeholder in the title.
        $newtemplate1 = new stdClass();
        $newtemplate1->title = 'Sometitle with an old placeholder [session:location] ...';
        $newtemplate1->body = 'A body with a new placeholder [session:room:location] ...';
        $newtemplate1->managerprefix = 'A managerprefix with a new placeholder [session:room:link] ...';
        $newtemplate1->status = 1;
        $newtemplate1->id = $DB->insert_record('facetoface_notification_tpl', $newtemplate1);

        // A template with the placeholder in the body.
        $newtemplate2 = new stdClass();
        $newtemplate2->title = 'Sometitle with an no placeholders';
        $newtemplate2->body = 'A body with a new placeholder [session:venue] ...';
        $newtemplate2->managerprefix = null; // Managerprefix field can be null.
        $newtemplate2->status = 1;
        $newtemplate2->id = $DB->insert_record('facetoface_notification_tpl', $newtemplate2);

        // A template with the placeholder in the managerprefix.
        $newtemplate3 = new stdClass();
        $newtemplate3->title = 'Sometitle with a new placeholder [session:room:name] ...';
        $newtemplate3->body = 'A body with no placeholders ...';
        $newtemplate3->managerprefix = 'A managerprefix with two old placeholders [session:room] and [alldates]...';
        $newtemplate3->status = 1;
        $newtemplate3->id = $DB->insert_record('facetoface_notification_tpl', $newtemplate3);

        // Another new template with no old placeholders.
        $newtemplate4 = new stdClass();
        $newtemplate4->title = 'Sometitle with a new placeholder [session:room:location] ...';
        $newtemplate4->body = 'A body with a placeholders that works before and after 9.0 [startdate] ...';
        $newtemplate4->managerprefix = 'A managerprefix with no placeholders...';
        $newtemplate4->status = 1;
        $newtemplate4->id = $DB->insert_record('facetoface_notification_tpl', $newtemplate4);

        // Let's edit an existing template to include an old placeholder.
        $existingtemplate = $DB->get_record('facetoface_notification_tpl', array('reference' => 'confirmation'));
        $existingtemplate->body = 'Overwriting the body with a message the includes an old template [session:room] ...';
        $DB->update_record('facetoface_notification_tpl', $existingtemplate);

        // We need to clear the cache.
        $cacheoptions = array(
            'simplekeys' => true,
            'simpledata' => true
        );
        $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_facetoface', 'notificationtpl', array(), $cacheoptions);
        $cache->delete('oldnotifications');

        $oldnotifications = facetoface_notification_get_templates_with_old_placeholders();

        $expected = array(
            $newtemplate1->id,
            $newtemplate2->id,
            $newtemplate3->id,
            $existingtemplate->id
        );

        // Order does not matter. Sorting both should set the orders in each to be the same.
        sort($expected);
        sort($oldnotifications);
        $this->assertEquals($expected, $oldnotifications);
    }

    /**
     * Check auto notifications duplicates recovery code
     */
    public function test_notification_duplicates() {
        global $DB;
        $sessionok = $this->f2f_generate_data(false);
        $sessionbad = $session = $this->f2f_generate_data(false);

        // Make duplicate.
        $duplicate = $DB->get_record('facetoface_notification', array(
            'facetofaceid' => $sessionbad->facetoface,
            'type' => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION
        ));
        $duplicate->id = null;
        $DB->insert_record('facetoface_notification', $duplicate);

        $noduplicate = $DB->get_record('facetoface_notification', array(
            'facetofaceid' => $sessionok->facetoface,
            'type' => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION
        ));
        $noduplicate->id = null;
        $noduplicate->type = 1;
        $DB->insert_record('facetoface_notification', $noduplicate);

        // Check duplicates detection.
        $this->assertTrue(facetoface_notification::has_auto_duplicates($sessionbad->facetoface));
        $this->assertFalse(facetoface_notification::has_auto_duplicates($sessionok->facetoface));

        // Check that it will not fail when attempted to send duplicate.
        $facetoface = $DB->get_record('facetoface', array('id' => $sessionbad->facetoface));
        $course = $DB->get_record("course", array('id' => $facetoface->course));
        $student = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);
        facetoface_user_signup($session, $facetoface, $course, '', MDL_F2F_NOTIFICATION_AUTO, MDL_F2F_STATUS_BOOKED, $student->id);

        facetoface_send_cancellation_notice($facetoface, $sessionbad, $student->id);
        $this->assertDebuggingCalled();

        // Check duplicates prevention.
        $allbefore = $DB->get_records('facetoface_notification', array('facetofaceid' => $sessionok->facetoface));

        $note = new facetoface_notification(array(
            'facetofaceid'  => $sessionok->facetoface,
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION
        ));
        $note->id = null;
        $note->save();
        $this->assertDebuggingCalled();

        $allafter = $DB->get_records('facetoface_notification', array('facetofaceid' => $sessionok->facetoface));
        $this->assertEquals(count($allbefore), count($allafter));
    }

    public function f2fsession_generate_data($future = true) {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $teacher1 = $this->getDataGenerator()->create_user();
        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();
        $manager  = $this->getDataGenerator()->create_user();

        $managerja = \totara_job\job_assignment::create_default($manager->id);
        \totara_job\job_assignment::create_default($student1->id, array('managerjaid' => $managerja->id));
        \totara_job\job_assignment::create_default($student2->id, array('managerjaid' => $managerja->id));

        $course = $this->getDataGenerator()->create_course();

        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        $this->getDataGenerator()->enrol_user($teacher1->id, $course->id, $teacherrole->id);
        $this->getDataGenerator()->enrol_user($student1->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course->id, $studentrole->id);

        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $facetofacedata = array(
            'name' => 'facetoface',
            'course' => $course->id
        );
        $facetoface = $facetofacegenerator->create_instance($facetofacedata);

        $sessiondate = new stdClass();
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $sessiondate->timestart = time() + WEEKSECS;
        $sessiondate->timefinish = time() + WEEKSECS + 60;

        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate),
            'datetimeknown' => '1',
            'mincapacity' => '1',
            'cutoff' => DAYSECS - 60
        );

        $sessionid = $facetofacegenerator->add_session($sessiondata);
        $session = facetoface_get_session($sessionid);

        return array($session, $facetoface, $course, $student1, $student2, $teacher1, $manager);
    }

    public function test_booking_confirmation_default() {

        // Default test Manager copy is enable and suppressccmanager is disabled.
        list($session, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->f2fsession_generate_data();

        $emailsink = $this->redirectMessages();
        facetoface_user_import($course, $facetoface, $session, $student1->id);
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(2, $emails, 'Wrong booking confirmation for Default test Manager copy is enable and suppressccmanager is disabled.');
    }

    public function test_booking_confirmation_suppress_ccmanager() {

        // Test Manager copy is enable and suppressccmanager is enabled(do not send a copy to manager).
        list($session, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->f2fsession_generate_data();

        $suppressccmanager = true;

        $params = array();
        if ($suppressccmanager) {
            $params['ccmanager'] = 0;
        }
        $emailsink = $this->redirectMessages();
        facetoface_user_import($course, $facetoface, $session, $student1->id, $params);
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(1, $emails, 'Wrong booking confirmation for Test Manager copy is enable and suppressccmanager is enabled(do not send a copy to manager).');
    }

    public function test_booking_confirmation_no_ccmanager() {

        // Test Manager copy is disabled and suppressccmanager is disbaled.
        list($session, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->f2fsession_generate_data();

        $params = array(
            'facetofaceid'  => $facetoface->id,
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_BOOKING_CONFIRMATION
        );
        $this->update_f2f_notification($params, 0);

        $emailsink = $this->redirectMessages();
        facetoface_user_import($course, $facetoface, $session, $student1->id);
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(1, $emails, 'Wrong booking confirmation for Test Manager copy is disabled and suppressccmanager is disbaled.');
    }

    public function test_booking_confirmation_no_ccmanager_and_suppress_ccmanager() {

        // Test Manager copy is disabled and suppressccmanager is disbaled.
        list($session, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->f2fsession_generate_data();

        $suppressccmanager = true;

        $params = array(
            'facetofaceid'  => $facetoface->id,
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_BOOKING_CONFIRMATION
        );
        $this->update_f2f_notification($params, 0);

        $data = array();
        if ($suppressccmanager) {
            $data['ccmanager'] = 0;
        }
        $emailsink = $this->redirectMessages();
        facetoface_user_import($course, $facetoface, $session, $student1->id, $data);
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(1, $emails, 'Wrong booking confirmation for Test Manager copy is disabled and suppressccmanager is disbaled.');
    }

    public function test_booking_cancellation_default() {

        // Default test Manager copy is enable and suppressccmanager is disabled.
        list($session, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->f2fsession_generate_data();

        $emailsink = $this->redirectMessages();
        facetoface_user_import($course, $facetoface, $session, $student1->id);
        $emailsink->close();

        $attendees = facetoface_get_attendees($session->id, array(MDL_F2F_STATUS_BOOKED));

        $emailsink = $this->redirectMessages();
        foreach ($attendees as $attendee) {
            if (facetoface_user_cancel($session, $attendee->id)) {
                facetoface_send_cancellation_notice($facetoface, $session, $attendee->id);
            }
        }
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(2, $emails, 'Wrong booking cancellation for Default test Manager copy is enable and suppressccmanager is disabled.');
    }

    public function test_booking_cancellation_suppress_ccmanager() {

        // Test Manager copy is enable and suppressccmanager is enabled.
        list($session, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->f2fsession_generate_data();

        $suppressccmanager = true;

        $emailsink = $this->redirectMessages();
        facetoface_user_import($course, $facetoface, $session, $student1->id);
        $emailsink->close();

        $attendees = facetoface_get_attendees($session->id, array(MDL_F2F_STATUS_BOOKED));

        $emailsink = $this->redirectMessages();
        foreach ($attendees as $attendee) {
            if (facetoface_user_cancel($session, $attendee->id)) {
                if ($suppressccmanager) {
                    $facetoface->ccmanager = 0;
                }
                facetoface_send_cancellation_notice($facetoface, $session, $attendee->id);
            }
        }
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(1, $emails, 'Wrong booking cancellation for Test Manager copy is enable and suppressccmanager is enabled.');
    }

    public function test_booking_cancellation_only_ccmanager() {

        // Test Manager copy is disabled and suppressccmanager is disbaled.
        list($session, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->f2fsession_generate_data();

        $emailsink = $this->redirectMessages();
        facetoface_user_import($course, $facetoface, $session, $student1->id);
        $emailsink->close();

        $attendees = facetoface_get_attendees($session->id, array(MDL_F2F_STATUS_BOOKED));

        $params = array(
            'facetofaceid'  => $facetoface->id,
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION
        );
        $this->update_f2f_notification($params, 1);

        $emailsink = $this->redirectMessages();
        foreach ($attendees as $attendee) {
            if (facetoface_user_cancel($session, $attendee->id)) {
                $facetoface->ccmanager = 1;
                $session->notifyuser = 0;
                facetoface_send_cancellation_notice($facetoface, $session, $attendee->id);
            }
        }
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(1, $emails, 'Only one message is expected');
        $this->assertEquals($manager->id, $emails[0]->useridto);
        $joinedbody = str_replace("=\n", "", $emails[0]->fullmessagehtml);
        $this->assertContains('you as their Team Leader', $joinedbody);
    }

    public function test_booking_cancellation_no_ccmanager() {

        // Test Manager copy is disabled and suppressccmanager is disbaled.
        list($session, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->f2fsession_generate_data();

        $emailsink = $this->redirectMessages();
        facetoface_user_import($course, $facetoface, $session, $student1->id);
        $emailsink->close();

        $attendees = facetoface_get_attendees($session->id, array(MDL_F2F_STATUS_BOOKED));

        $params = array(
            'facetofaceid'  => $facetoface->id,
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION
        );
        $this->update_f2f_notification($params, 0);

        $emailsink = $this->redirectMessages();
        foreach ($attendees as $attendee) {
            if (facetoface_user_cancel($session, $attendee->id)) {
                facetoface_send_cancellation_notice($facetoface, $session, $attendee->id);
            }
        }
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(1, $emails, 'Wrong booking cancellation for Test Manager copy is disabled and suppressccmanager is disbaled.');
    }

    public function test_booking_cancellation_no_ccmanager_and_suppress_ccmanager() {

        // Test Manager copy is disabled and suppressccmanager is disbaled.
        list($session, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->f2fsession_generate_data();

        $suppressccmanager = true;

        $emailsink = $this->redirectMessages();
        facetoface_user_import($course, $facetoface, $session, $student1->id);
        $emailsink->close();

        $attendees = facetoface_get_attendees($session->id, array(MDL_F2F_STATUS_BOOKED));

        $params = array(
            'facetofaceid'  => $facetoface->id,
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION
        );
        $this->update_f2f_notification($params, 0);

        $emailsink = $this->redirectMessages();
        foreach ($attendees as $attendee) {
            if (facetoface_user_cancel($session, $attendee->id)) {
                if ($suppressccmanager) {
                    $facetoface->ccmanager = 0;
                }
                facetoface_send_cancellation_notice($facetoface, $session, $attendee->id);
            }
        }
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(1, $emails, 'Wrong booking cancellation for Test Manager copy is disabled and suppressccmanager is disbaled.');
    }

    private function update_f2f_notification($params, $ccmanager) {
        global $DB;

        $notification = new facetoface_notification($params);

        $notice = new stdClass();
        $notice->id = $notification->id;
        $notice->ccmanager = $ccmanager;

        return $DB->update_record('facetoface_notification', $notice);
    }

    public function test_user_timezone() {
        global $DB;

        $emailsink = $this->redirectMessages();
        list($sessiondate, $student1, $student2, $student3) = $this->f2fsession_generate_timezone(99);
        $emailsink->close();

        // Test we are getting F2F booking confirmation email.
        $haystack = $emailsink->get_messages();
        $this->notification_content_test(
            'This is to confirm that you are now booked on the following course',
            $haystack,
            'Wrong notification, must be Face-to-face booking confirmation');

        $alldates = $this->get_user_date($sessiondate, $student1);
        // Test user timezone date with session timezone date.
        $this->assertContains(
            $alldates,
            $haystack[0]->fullmessagehtml,
            'Wrong session timezone date for student 1 Face-to-face booking confirmation notification');

        $alldates = $this->get_user_date($sessiondate, $student2);
        // Test user timezone date with session timezone date.
        $this->assertContains(
            $alldates,
            $haystack[1]->fullmessagehtml,
            'Wrong session timezone date for student 2 Face-to-face booking confirmation notification');

        $alldates = $this->get_user_date($sessiondate, $student3);
        // Test user timezone date with session timezone date.
        $this->assertContains(
            $alldates,
            $haystack[2]->fullmessagehtml,
            'Wrong session timezone date for student 3 Face-to-face booking confirmation notification');

        $scheduled = $DB->get_records_select('facetoface_notification', 'conditiontype = ?', array(MDL_F2F_CONDITION_BEFORE_SESSION));
        $this->assertCount(1, $scheduled);
        $notify = reset($scheduled);
        $emailsink = $this->redirectMessages();
        $notification = new \facetoface_notification((array)$notify, false);
        $notification->send_scheduled();
        $emailsink->close();
        // Test we are getting F2F booking reminder email.
        $haystack = $emailsink->get_messages();
        $this->notification_content_test(
            'This is a reminder that you are booked on the following course',
            $haystack,
            'Wrong notification, must be Face-to-face booking reminder');

        $alldates = $this->get_user_date($sessiondate, $student1);
        // Test user timezone date with session timezone date.
        $this->assertContains(
            $alldates,
            $haystack[0]->fullmessagehtml,
            'Wrong session timezone date for student 1 of Face-to-face booking reminder notification');

        $alldates = $this->get_user_date($sessiondate, $student2);
        // Test user timezone date with session timezone date.
        $this->assertContains(
            $alldates,
            $haystack[1]->fullmessagehtml,
            'Wrong session timezone date for student 2 of Face-to-face booking reminder notification');

        $alldates = $this->get_user_date($sessiondate, $student3);
        // Test user timezone date with session timezone date.
        $this->assertContains(
            $alldates,
            $haystack[2]->fullmessagehtml,
            'Wrong session timezone date for student 3 of Face-to-face booking reminder notification');
    }

    public function test_session_timezone() {
        global $DB;

        $test = new stdClass();
        $test->timezone = 'America/New_York';

        $emailsink = $this->redirectMessages();
        list($sessiondate, $student1, $student2, $student3) = $this->f2fsession_generate_timezone($test->timezone);
        $emailsink->close();

        // Test we are getting F2F booking confirmation email.
        $haystack = $emailsink->get_messages();
        $this->notification_content_test(
            'This is to confirm that you are now booked on the following course',
            $haystack,
            'Wrong notification, must be Face-to-face booking confirmation');

        $alldates = $this->get_user_date($sessiondate, $test);

        // Test user timezone date with session timezone date.
        $this->assertContains(
            $alldates,
            $haystack[0]->fullmessagehtml,
            'Wrong session timezone date for student 1 Face-to-face booking confirmation notification');

        // Test user timezone date with session timezone date.
        $this->assertContains(
            $alldates,
            $haystack[1]->fullmessagehtml,
            'Wrong session timezone date for student 2 Face-to-face booking confirmation notification');

        // Test user timezone date with session timezone date.
        $this->assertContains(
            $alldates,
            $haystack[2]->fullmessagehtml,
            'Wrong session timezone date for student 3 Face-to-face booking confirmation notification');

        $scheduled = $DB->get_records_select('facetoface_notification', 'conditiontype = ?', array(MDL_F2F_CONDITION_BEFORE_SESSION));
        $this->assertCount(1, $scheduled);
        $notify = reset($scheduled);
        $emailsink = $this->redirectMessages();
        $notification = new \facetoface_notification((array)$notify, false);
        $notification->send_scheduled();
        $emailsink->close();
        // Test we are getting F2F booking reminder email.
        $haystack = $emailsink->get_messages();
        $this->notification_content_test(
            'This is a reminder that you are booked on the following course',
            $haystack,
            'Wrong notification, must be Face-to-face booking reminder');

        // Test user timezone date with session timezone date.
        $this->assertContains(
            $alldates,
            $haystack[0]->fullmessagehtml,
            'Wrong session timezone date for student 1 of Face-to-face booking reminder notification');

        // Test user timezone date with session timezone date.
        $this->assertContains(
            $alldates,
            $haystack[1]->fullmessagehtml,
            'Wrong session timezone date for student 2 of Face-to-face booking reminder notification');

        // Test user timezone date with session timezone date.
        $this->assertContains(
            $alldates,
            $haystack[2]->fullmessagehtml,
            'Wrong session timezone date for student 3 of Face-to-face booking reminder notification');
    }

    /**
     * Test facetoface cancel session notification
     */
    public function test_facetoface_cancel_session() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        /** @var mod_facetoface_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $course = $this->getDataGenerator()->create_course();

        $facetoface = $generator->create_instance(array('course' => $course->id, 'approvaltype' => 0));
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + DAYSECS;
        $sessiondate->timefinish = $sessiondate->timestart + (DAYSECS * 2);
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $sessionid = $generator->add_session(array('facetoface' => $facetoface->id, 'sessiondates' => array($sessiondate)));

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();

        $managerja = \totara_job\job_assignment::create_default($manager->id);
        \totara_job\job_assignment::create_default($user4->id, array('managerjaid' => $managerja->id));

        $session = facetoface_get_session($sessionid);

        facetoface_user_signup($session, $facetoface, $course, '', MDL_F2F_NONE, MDL_F2F_STATUS_APPROVED, $user1->id, false);
        facetoface_cancel_attendees($sessionid, array($user1->id));
        facetoface_user_signup($session, $facetoface, $course, '', MDL_F2F_NONE, MDL_F2F_STATUS_APPROVED, $user2->id, false);
        facetoface_user_signup($session, $facetoface, $course, '', MDL_F2F_NONE, MDL_F2F_STATUS_BOOKED, $user3->id, false);
        facetoface_user_signup($session, $facetoface, $course, '', MDL_F2F_NONE, MDL_F2F_STATUS_REQUESTED, $user4->id, false);
        $attendee = facetoface_get_attendee($session->id, $user4->id);
        facetoface_update_signup_status($attendee->submissionid, MDL_F2F_STATUS_DECLINED,$user4->id);

        $sql = "SELECT ss.statuscode
                  FROM {facetoface_signups} s
                  JOIN {facetoface_signups_status} ss ON ss.signupid = s.id
                 WHERE s.sessionid = :sid AND ss.superceded = 0 AND s.userid = :uid";

        $this->assertEquals(MDL_F2F_STATUS_USER_CANCELLED, $DB->get_field_sql($sql, array('sid' => $session->id, 'uid' => $user1->id)));
        $this->assertEquals(MDL_F2F_STATUS_APPROVED, $DB->get_field_sql($sql, array('sid' => $session->id, 'uid' => $user2->id)));
        $this->assertEquals(MDL_F2F_STATUS_BOOKED, $DB->get_field_sql($sql, array('sid' => $session->id, 'uid' => $user3->id)));
        $this->assertEquals(MDL_F2F_STATUS_DECLINED, $DB->get_field_sql($sql, array('sid' => $session->id, 'uid' => $user4->id)));

        // Now test cancelling the session.
        $emailsink = $this->redirectMessages();
        $result = facetoface_cancel_session($session, null);
        $this->assertTrue($result);
        $emailsink->close();

        $messages = $emailsink->get_messages();
        $this->assertCount(2, $messages);

        // Users that have cancelled their session or their request have been declined should not being affected when a
        // session is cancelled.
        $affectedusers = array($user2->id, $user3->id);
        foreach ($messages as $message) {
            $this->assertContains('Seminar event cancellation', $message->subject);
            $this->assertContains('This is to advise that the following session has been cancelled', $message->fullmessagehtml);
            $this->assertContains('Course:   Test course 1', $message->fullmessagehtml);
            $this->assertContains('Seminar:   Seminar 1', $message->fullmessagehtml);
            $this->assertContains($message->useridto, $affectedusers);
        }
    }

    private function f2fsession_generate_timezone($sessiontimezone) {
        global $DB, $CFG;

        $this->setAdminUser();

        // Server timezone is Australia/Perth = $CFG->timezone.
        $student1 = $this->getDataGenerator()->create_user(array('timezone' => 'Europe/London'));
        $student2 = $this->getDataGenerator()->create_user(array('timezone' => 'Pacific/Auckland'));
        $student3 = $this->getDataGenerator()->create_user(array('timezone' => $CFG->timezone));
        $this->assertEquals($student1->timezone, 'Europe/London');
        $this->assertEquals($student2->timezone, 'Pacific/Auckland');
        $this->assertEquals($student3->timezone, $CFG->timezone);

        \totara_job\job_assignment::create_default($student1->id);
        \totara_job\job_assignment::create_default($student2->id);
        \totara_job\job_assignment::create_default($student3->id);

        $course = $this->getDataGenerator()->create_course();

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($student1->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($student3->id, $course->id, $studentrole->id);

        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $facetofacedata = array(
            'name' => 'facetoface',
            'course' => $course->id
        );
        $facetoface = $facetofacegenerator->create_instance($facetofacedata);

        $sessiondate = new stdClass();
        $sessiondate->sessiontimezone = $sessiontimezone;
        $sessiondate->timestart = time() + DAYSECS;
        $sessiondate->timefinish = time() + DAYSECS + (4 * HOURSECS);

        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 5,
            'sessiondates' => array($sessiondate),
            'datetimeknown' => '1',
        );

        $sessionid = $facetofacegenerator->add_session($sessiondata);
        $session = $DB->get_record('facetoface_sessions', array('id' => $sessionid));
        $session->sessiondates = facetoface_get_session_dates($session->id);

        facetoface_user_signup($session, $facetoface, $course, '', MDL_F2F_TEXT, MDL_F2F_STATUS_BOOKED, $student1->id);
        facetoface_user_signup($session, $facetoface, $course, '', MDL_F2F_TEXT, MDL_F2F_STATUS_BOOKED, $student2->id);
        facetoface_user_signup($session, $facetoface, $course, '', MDL_F2F_TEXT, MDL_F2F_STATUS_BOOKED, $student3->id);

        return array($sessiondate, $student1, $student2, $student3);
    }

    private function notification_content_test($needlebody, $emails, $message) {

        $this->assertContains($needlebody, $emails[0]->fullmessagehtml, $message);
        $this->assertContains($needlebody, $emails[1]->fullmessagehtml, $message);
        $this->assertContains($needlebody, $emails[2]->fullmessagehtml, $message);
    }

    private function get_user_date($sessiondate, $date) {
        // Get user settings.
        $alldates = '';
        $strftimedate = get_string('strftimedate');
        $strftimetime = get_string('strftimetime');

        $startdate  = userdate($sessiondate->timestart, $strftimedate, $date->timezone);
        $startime   = userdate($sessiondate->timestart, $strftimetime, $date->timezone);

        $finishdate = userdate($sessiondate->timefinish, $strftimedate, $date->timezone);
        $finishtime = userdate($sessiondate->timefinish, $strftimetime, $date->timezone);

        // Template example: [session:startdate], [session:starttime] - [session:finishdate], [session:finishtime] [session:timezone]
        $alldates .= $startdate .', '.$startime .' - '. $finishdate .', '. $finishtime . ' '. $date->timezone;

        return $alldates;
    }

    /**
     * Test sending notifications when "facetoface_oneemailperday" is enabled,
     * with a event without a date and the learner is waitlisted.
     */
    public function test_oneperday_waitlisted_no_events() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        set_config('facetoface_oneemailperday', true);

        $student1 = $this->getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($student1->id, $course->id, $studentrole->id);

        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $facetofacedata = array(
            'name' => 'facetoface',
            'course' => $course->id
        );
        $facetoface = $facetofacegenerator->create_instance($facetofacedata);

        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => [],
            'datetimeknown' => '0',
            'mincapacity' => '1'
        );

        $session = facetoface_get_session($facetofacegenerator->add_session($sessiondata));

        $emailsink = $this->redirectMessages();
        facetoface_user_import($course, $facetoface, $session, $student1->id);
        $emailsink->close();

        $preemails = $emailsink->get_messages();
        foreach($preemails as $preemail) {
            $this->assertContains("This is to advise that you have been added to the waitlist", $preemail->fullmessagehtml);
        }
    }

    /**
     * Test facetoface_is_notification_active function works correctly with all the available seminar notification.
     */
    public function test_facetoface_is_notification_active() {
        global $DB,$CFG;

        // Seeding initial data.
        $f2f = $this->getDataGenerator()
            ->get_plugin_generator('mod_facetoface')
            ->create_instance([
                'name' => 'facetoface',
                'course' => $this->getDataGenerator()->create_course()->id
            ]);

        $states = [true, false];

        $get_notification = function ($f2fid, $type) use ($DB) {
            return facetoface_notification::fetch([
                'facetofaceid' => $f2fid,
                'conditiontype' => $type,
            ]);
        };

        foreach (facetoface_notification::get_references() as $notification => $type) {

            $local = $get_notification($f2f->id, $type);

            foreach ($states as $state) {
                $DB->update_record('facetoface_notification', (object) [
                    'id' => $local->id,
                    'status' => (int) $state,
                ]);

                // Calling twice as it supports 'overload' where either notification type (int) or object can be passed.
                $this->assertEquals(facetoface_is_notification_active($get_notification($f2f->id, $type)), $state);
                $this->assertEquals(facetoface_is_notification_active($type, $f2f), $state);

                // Check it works with the global flag.
                $CFG->facetoface_notificationdisable = 1;
                $this->assertFalse(facetoface_is_notification_active($type, $f2f, true));
                $this->assertEquals(facetoface_is_notification_active($type, $f2f), $state);
                unset($CFG->facetoface_notificationdisable);
            }
        }

        $this->resetAfterTest();
    }

    /**
     * Test to restore missing default notification templates for existing seminars,
     * this is happening when upgrade from t2.9 to t9.
     */
    public function test_restore_missing_default_notifications() {
        global $DB;

        // Seeding initial data.
        $f2f1 = $this->getDataGenerator()
            ->get_plugin_generator('mod_facetoface')
            ->create_instance([
                'name' => 'Seminar 17288A',
                'course' => $this->getDataGenerator()->create_course()->id
            ]);
        $f2f2 = $this->getDataGenerator()
            ->get_plugin_generator('mod_facetoface')
            ->create_instance([
                'name' => 'Seminar 17288B',
                'course' => $this->getDataGenerator()->create_course()->id
            ]);
        $f2f3 = $this->getDataGenerator()
            ->get_plugin_generator('mod_facetoface')
            ->create_instance([
                'name' => 'Seminar 17288C',
                'course' => $this->getDataGenerator()->create_course()->id
            ]);

        // Get a count default notification templates.
        $counttpl = $DB->count_records('facetoface_notification_tpl');
        // Get total amount all notifications for 3 seminars.
        $countnote = $DB->count_records('facetoface_notification');

        // Multiply default count by 3 as we have 3 seminars created.
        $this->assertEquals($countnote, $counttpl * 3);

        // Test the facetoface_notification_get_missing_templates() function there are no missing templates.
        $this->assertEmpty(facetoface_notification_get_missing_templates());

        // Test facetoface_notification_restore_missing_template function there are nothing to restore.
        $affectedrows = facetoface_notification_restore_missing_template(MDL_F2F_CONDITION_SESSION_CANCELLATION);
        $this->assertEquals(0, $affectedrows);

        // This a hack to pretend that the 'Seminar event cancellation' default template is missing.
        $DB->delete_records('facetoface_notification', ['type' => MDL_F2F_NOTIFICATION_AUTO, 'conditiontype' => MDL_F2F_CONDITION_SESSION_CANCELLATION]);
        // Test we deleted 3 records.
        $this->assertEquals($countnote - 3, $DB->count_records('facetoface_notification'));

        // Test the facetoface_notification_get_missing_templates() function there are missing templates.
        // MDL_F2F_CONDITION_SESSION_CANCELLATION is missing template.
        $this->assertCount(1, facetoface_notification_get_missing_templates());

        // Restore templates.
        $affectedrows = facetoface_notification_restore_missing_template(MDL_F2F_CONDITION_SESSION_CANCELLATION);
        $this->assertEquals(3, $affectedrows);
    }

    /**
     * Test that under capacity notifications are not sent for cancelled notifications.
     * @dataProvider status_provider
     */
    public function test_facetoface_notify_under_capacity_not_sent_for_cancelled_events($cancelled) {
        global $CFG, $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        /**
         * @var \mod_facetoface_generator $seminargen
         */
        $seminargen = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $seminarrec = $seminargen->create_instance([
            'name' => 'Seminar 1',
            'course' => $course->id
        ]);

        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + DAYSECS;
        $sessiondate->timefinish = $sessiondate->timestart + (DAYSECS * 2);
        $sessiondate->sessiontimezone = 'Pacific/Auckland';

        $seminareventid = $seminargen->add_session([
            'facetoface' => $seminarrec->id,
            'cutoff' => DAYSECS+1,
            'mincapacity' => 1,
            'cancelledstatus' => $cancelled,
            'sessiondates' => [$sessiondate]
        ]);

        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $CFG->facetoface_session_rolesnotify = $teacherrole->id;
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $teacherrole->id);

        $sessrole = new stdClass();
        $sessrole->roleid = $teacherrole->id;
        $sessrole->sessionid = $seminareventid;
        $sessrole->userid = $user->id;
        $DB->insert_record('facetoface_session_roles', $sessrole);

        $emailsink = $this->redirectMessages();

        ob_start();
        facetoface_notify_under_capacity();
        ob_end_clean();

        $messages = $emailsink->get_messages();
        $emailsink->close();

        $CFG->facetoface_session_rolesnotify = '';

        if ($cancelled) {
            $this->assertCount(0, $messages);
        } else {
            $this->assertCount(1, $messages);
            $this->assertContains('Event under minimum bookings', current($messages)->subject);
        }
    }

    /**
     * Provider for test_facetoface_notify_under_capacity_not_sent_for_cancelled_events
     * @return array
     */
    public function status_provider() {
        return [
            [0],
            [1]
        ];
    }
}
