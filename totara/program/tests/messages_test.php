<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Maria Torres <maria.torres@totaralms.com>
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_program
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot . '/totara/program/lib.php');

/**
 * Test messages in programs.
 *
 * Includes:
 * enrolment and unenrolment (including re-enrolment and re-unenrolment)
 * program completed
 * program due
 * program overdue
 * course set completed
 * course set due
 * course set overdue
 * learner follow-up
 *
 * Does not currently include Exceptions report.
 *
 * Does not currently check what happens when multiple messages of a single type are set up.
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit totara_program_messages_testcase
 */
class totara_program_messages_testcase extends reportcache_advanced_testcase {

    private $program_generator = null;
    private $program1, $program2;
    private $user1, $user2, $user3, $user4, $user5, $user6;
    private $manager, $managerja;
    /** @var phpunit_message_sink */
    private $sink;

    protected function tearDown() {
        $this->program_generator = null;
        $this->program1 = $this->program2 = null;
        $this->user1 = $this->user2 = $this->user3 = $this->user4 = $this->user5 = $this->user6 = null;
        $this->manager = null;
        $this->managerja = null;

        $this->sink->clear();
        $this->sink = null;

        parent::tearDown();
    }

    public function setUp() {
        global $DB;

        parent::setup();

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Turn off certifications. This is to test that it doesn't interfere with program completion.
        set_config('enablecertifications', TOTARA_DISABLEFEATURE);

        $this->program_generator = $this->getDataGenerator()->get_plugin_generator('totara_program');

        // Create users.
        $this->assertEquals(2, $DB->count_records('user'));
        $this->manager = $this->getDataGenerator()->create_user();
        for ($i = 1; $i <= 6; $i++) {
            $this->{'user'.$i} = $this->getDataGenerator()->create_user(array('managerid' => $this->manager->id));
        }
        $this->assertEquals(6 + 3, $DB->count_records('user'));

        // Create two programs.
        $this->assertEquals(0, $DB->count_records('prog'));
        $this->program1 = $this->program_generator->create_program();
        $this->program2 = $this->program_generator->create_program();
        $this->assertEquals(2, $DB->count_records('prog'));

        $this->sink = $this->redirectMessages();
    }

    public function test_program_enrolment_and_unenrollment_messages() {
        global $DB;

        // Set up the messages.
        $programmessagemanager = $this->program1->get_messagesmanager();
        $programmessagemanager->add_message(MESSAGETYPE_UNENROLMENT);
        $programmessagemanager->save_messages();
        prog_messages_manager::get_program_messages_manager($this->program1->id, true); // Causes static cache to be reset.

        $enrolmentmessage = $DB->get_record('prog_message', array('programid' => $this->program1->id, 'messagetype' => MESSAGETYPE_ENROLMENT));
        $unenrolmentmessage = $DB->get_record('prog_message', array('programid' => $this->program1->id, 'messagetype' => MESSAGETYPE_UNENROLMENT));

        // Some quick edits to the enrolment message content.
        $enrolmentmessage->managersubject = '';
        $enrolmentmessage->managermessage = 'Staff Program Assignment';
        $enrolmentmessage->notifymanager = 1;
        $DB->update_record('prog_message', $enrolmentmessage);
        prog_messages_manager::get_program_messages_manager($this->program1->id, true); // Causes static cache to be reset.

        // Assign users to program1.
        $usersprogram1 = array($this->user1->id, $this->user2->id, $this->user3->id);
        $this->program_generator->assign_program($this->program1->id, $usersprogram1);

        // Attempt to send any program messages.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check the right amount of messages were caught.
        $emails = $this->sink->get_messages();
        $this->assertCount(6, $emails);
        $this->sink->clear();

        // Check the emails content.
        $managercount = 0;
        $learnercount = 0;
        foreach ($emails as $email) {
            if (in_array($email->useridto, $usersprogram1)) {
                $learnercount++;
                $this->assertEquals($email->subject, 'You have been enrolled on program Program Fullname', 'unexpected default learner enrolment subject');
                $this->assertEquals($email->fullmessage, 'You are now enrolled on program Program Fullname.', 'unexpected default learner enrolment message');
            } else {
                $managercount++;
                $this->assertEquals($email->useridto, $this->manager->id, 'unexpected user recieving message');
                $this->assertEquals($email->subject, 'Learner enrolled', 'unexpected default manager enrolment subject');
                $this->assertEquals($email->fullmessage, 'Staff Program Assignment', 'unexpected custom manager enrolment message');
            }

            $this->assertEquals($email->fromemail, 'noreply@www.example.com', 'unexpected default userfrom email address');
        }
        $this->assertEquals(3, $managercount);
        $this->assertEquals(3, $learnercount);

        // Check that they all had logs created.
        $this->assertEquals(3, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $enrolmentmessage->id)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $enrolmentmessage->id)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user3->id, 'messageid' => $enrolmentmessage->id)));

        // Now edit the subject lines to make sure they've changed.
        $enrolmentmessage->messagesubject = 'Learner Program Assignment';
        $enrolmentmessage->mainmessage = 'You have been assigned to the program';
        $enrolmentmessage->managersubject = 'Staff Program Assignment';
        $enrolmentmessage->managermessage = 'Your staffmember has been assigned to the program';
        $DB->update_record('prog_message', $enrolmentmessage);
        prog_messages_manager::get_program_messages_manager($this->program1->id, true); // Causes static cache to be reset.

        // Assign users to program1 and make sure only the new users get the message.
        $usersprogram1 = array($this->user1->id, $this->user2->id, $this->user3->id, $this->user4->id, $this->user5->id);
        $this->program_generator->assign_program($this->program1->id, $usersprogram1);

        // Attempt to send any program messages.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check the right amount of messages were caught.
        $emails = $this->sink->get_messages();
        $this->assertCount(4, $emails);
        $this->sink->clear();

        // Check the emails content.
        $managercount = $learnercount = 0;
        foreach ($emails as $email) {
            if (in_array($email->useridto, $usersprogram1)) {
                $learnercount++;
                $this->assertEquals($email->subject, 'Learner Program Assignment', 'unexpected custom learner enrolment subject');
                $this->assertEquals($email->fullmessage, 'You have been assigned to the program', 'unexpected custom learner enrolment message');
            } else {
                $managercount++;
                $this->assertEquals($email->useridto, $this->manager->id, 'unexpected user recieving message');
                $this->assertEquals($email->subject, 'Staff Program Assignment', 'unexpected custom manager enrolment subject');
                $this->assertEquals($email->fullmessage, 'Your staffmember has been assigned to the program', 'unexpected custom manager enrolment message');
            }

            $this->assertEquals($email->fromemail, 'noreply@www.example.com', 'unexpected default userfrom email address');
        }
        $this->assertEquals(2, $learnercount);
        $this->assertEquals(2, $managercount);

        // Check that they all had logs created.
        $this->assertEquals(5, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user4->id, 'messageid' => $enrolmentmessage->id)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user5->id, 'messageid' => $enrolmentmessage->id)));

        // Remove users from the program.
        $usersprogram1 = array();
        $this->program_generator->assign_program($this->program1->id, $usersprogram1);

        // Attempt to send any program messages.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check the right amount of messages were caught.
        $emails = $this->sink->get_messages();
        $this->assertCount(5, $emails);
        $this->sink->clear();

        // Check that they all had logs created.
        $this->assertEquals(5, $DB->count_records('prog_messagelog')); // 5 enrolment messages were deleted.
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $unenrolmentmessage->id)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $unenrolmentmessage->id)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user3->id, 'messageid' => $unenrolmentmessage->id)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user4->id, 'messageid' => $unenrolmentmessage->id)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user5->id, 'messageid' => $unenrolmentmessage->id)));

        // Assign users to program1 (second assignment).
        $usersprogram1 = array($this->user1->id, $this->user2->id, $this->user3->id, $this->user4->id);
        $this->program_generator->assign_program($this->program1->id, $usersprogram1);

        // Attempt to send any program messages.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check the right amount of messages were caught.
        $emails = $this->sink->get_messages();
        $this->assertCount(8, $emails);
        $this->sink->clear();

        // Check that they all had logs created.
        $this->assertEquals(5, $DB->count_records('prog_messagelog')); // 4 unenrolment messages were deleted.
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $enrolmentmessage->id)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $enrolmentmessage->id)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user3->id, 'messageid' => $enrolmentmessage->id)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user4->id, 'messageid' => $enrolmentmessage->id)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user5->id, 'messageid' => $unenrolmentmessage->id)));

        // Remove users from the program (second unassignment).
        $usersprogram1 = array($this->user1->id);
        $this->program_generator->assign_program($this->program1->id, $usersprogram1);

        // Attempt to send any program messages.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check the right amount of messages were caught.
        $emails = $this->sink->get_messages();
        $this->assertCount(3, $emails);
        $this->sink->clear();

        // Check that they all had logs created.
        $this->assertEquals(5, $DB->count_records('prog_messagelog')); // 3 enrolment messages were deleted.
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $enrolmentmessage->id)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $unenrolmentmessage->id)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user3->id, 'messageid' => $unenrolmentmessage->id)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user4->id, 'messageid' => $unenrolmentmessage->id)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user5->id, 'messageid' => $unenrolmentmessage->id)));
    }

    /**
     * Make sure that program completed messages are sent when a user completes the content.
     *
     * Also checks that messages are sent when user is assigned, if the user completed the content before assignment.
     */
    public function test_program_completed_messages() {
        global $DB;

        // Set up the messages.
        $programmessagemanager = $this->program1->get_messagesmanager();
        $programmessagemanager->delete();
        $programmessagemanager->add_message(MESSAGETYPE_PROGRAM_COMPLETED);
        $programmessagemanager->save_messages();
        prog_messages_manager::get_program_messages_manager($this->program1->id, true); // Causes static cache to be reset.
        $messageid = $DB->get_field('prog_message', 'id',
            array('programid' => $this->program1->id, 'messagetype' => MESSAGETYPE_PROGRAM_COMPLETED));

        // Create two courses.
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        // Assign courses to program.
        $coursesetdata = array(
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'courses' => array($course1)
            ),
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'courses' => array($course2)
            ),
        );
        $this->getDataGenerator()->create_coursesets_in_program($this->program1, $coursesetdata);

        // Assign users to courses.
        $this->getDataGenerator()->enrol_user($this->user2->id, $course1->id);
        $this->getDataGenerator()->enrol_user($this->user2->id, $course2->id);
        $this->getDataGenerator()->enrol_user($this->user3->id, $course1->id);
        $this->getDataGenerator()->enrol_user($this->user4->id, $course2->id);

        // Complete courses.
        $completion = new completion_completion(array('userid' => $this->user2->id, 'course' => $course1->id));
        $completion->mark_complete();
        $completion = new completion_completion(array('userid' => $this->user2->id, 'course' => $course2->id));
        $completion->mark_complete();
        $completion = new completion_completion(array('userid' => $this->user3->id, 'course' => $course1->id));
        $completion->mark_complete(); // User3 has only completed one course, so hasn't completed the program.
        $completion = new completion_completion(array('userid' => $this->user4->id, 'course' => $course2->id));
        $completion->mark_complete(); // User4 has only completed THE SECOND course, so hasn't completed the program.

        // Assign users to program.
        $usersprogram = array($this->user1->id, $this->user2->id, $this->user3->id, $this->user4->id);
        $this->program_generator->assign_program($this->program1->id, $usersprogram);

        // Check the right amount of messages were caught.
        $emails = $this->sink->get_messages();
        $this->assertCount(1, $emails);
        $this->sink->clear();

        // Check that they all had logs created.
        $this->assertEquals(1, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Complete the program for the other two users.
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course2->id));
        $completion->mark_complete();
        $completion = new completion_completion(array('userid' => $this->user3->id, 'course' => $course2->id));
        $completion->mark_complete();
        $completion = new completion_completion(array('userid' => $this->user4->id, 'course' => $course1->id));
        $completion->mark_complete();

        // Check the right amount of messages were caught.
        $emails = $this->sink->get_messages();
        $this->assertCount(3, $emails);
        $this->sink->clear();

        // Check that they all had logs created.
        $this->assertEquals(4, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user3->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user4->id, 'messageid' => $messageid)));
    }

    /**
     * Make sure that program due messages are sent when a user's due date is nearly reached.
     *
     * Note that user6 has completed the courses but their program completion record indicates that they are due.
     */
    public function test_program_due_messages() {
        global $DB;

        // Set up the messages.
        $programmessagemanager = $this->program1->get_messagesmanager();
        $programmessagemanager->delete();
        $programmessagemanager->add_message(MESSAGETYPE_PROGRAM_DUE);
        $programmessagemanager->save_messages();
        // Update the message record to be triggered 100 days before due.
        $duemessage = $DB->get_record('prog_message', array('programid' => $this->program1->id, 'messagetype' => MESSAGETYPE_PROGRAM_DUE));
        // Some quick edits to the enrolment message content.
        $duemessage->messagesubject = 'Learner ProgDue Message';
        $duemessage->mainmessage = 'Hey dude, do your program';
        $duemessage->managersubject = 'Manager ProgDue Message';
        $duemessage->managermessage = 'Go tell your staff member to finish their program';
        $duemessage->notifymanager = 1;
        $DB->update_record('prog_message', $duemessage);

        $messageid = $duemessage->id;
        prog_messages_manager::get_program_messages_manager($this->program1->id, true); // Causes static cache to be reset.

        $DB->set_field('prog_message', 'triggertime', DAYSECS * 100, array('id' => $messageid));
        $DB->set_field('prog_message', 'notifymanager', "1", array('id' => $messageid));
        prog_messages_manager::get_program_messages_manager($this->program1->id, true); // Causes static cache to be reset.

        // Create two courses.
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        // Assign courses to program.
        $coursesetdata = array(
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'courses' => array($course1)
            ),
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'courses' => array($course2)
            ),
        );
        $this->getDataGenerator()->create_coursesets_in_program($this->program1, $coursesetdata);

        // Assign users to program.
        $usersprogram = array($this->user1->id, $this->user2->id, $this->user3->id, $this->user4->id, $this->user5->id, $this->user6->id);
        $this->program_generator->assign_program($this->program1->id, $usersprogram);

        // Hack the due dates, 10 days from now.
        $duedate = time() + DAYSECS * 10;
        $progcompl1 = prog_load_completion($this->program1->id, $this->user1->id);
        $progcompl1->timedue = $duedate;
        $this->assertTrue(prog_write_completion($progcompl1));
        $progcompl2 = prog_load_completion($this->program1->id, $this->user2->id);
        $progcompl2->timedue = $duedate;
        $this->assertTrue(prog_write_completion($progcompl2));
        $progcompl3 = prog_load_completion($this->program1->id, $this->user3->id);
        $progcompl3->timedue = $duedate;
        $this->assertTrue(prog_write_completion($progcompl3));
        $progcompl4 = prog_load_completion($this->program1->id, $this->user4->id);
        $progcompl4->timedue = time() - DAYSECS * 10; // Overdue (but should still send due).
        $this->assertTrue(prog_write_completion($progcompl4));
        $progcompl5 = prog_load_completion($this->program1->id, $this->user5->id);
        $progcompl5->timedue = time() + DAYSECS * 200; // Not yet due (so no message sent).
        $this->assertTrue(prog_write_completion($progcompl5));
        $progcompl6 = prog_load_completion($this->program1->id, $this->user6->id);
        $progcompl6->timedue = $duedate;
        $this->assertTrue(prog_write_completion($progcompl6));

        // Assign users and complete some courses. 0 for user1, 1 for user2, 2 for user3, 2 for user6.
        $this->getDataGenerator()->enrol_user($this->user2->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user2->id, 'course' => $course1->id));
        $completion->mark_complete();
        $this->getDataGenerator()->enrol_user($this->user3->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user3->id, 'course' => $course1->id));
        $completion->mark_complete();
        $this->getDataGenerator()->enrol_user($this->user3->id, $course2->id);
        $completion = new completion_completion(array('userid' => $this->user3->id, 'course' => $course2->id));
        $completion->mark_complete();
        $this->getDataGenerator()->enrol_user($this->user6->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user6->id, 'course' => $course1->id));
        $completion->mark_complete();
        $this->getDataGenerator()->enrol_user($this->user6->id, $course2->id);
        $completion = new completion_completion(array('userid' => $this->user6->id, 'course' => $course2->id));
        $completion->mark_complete();

        // Re-save user6's original program completion record to indicate that it's not complete, even though the courses are.
        $this->assertTrue(prog_write_completion($progcompl6));

        // Attempt to send any program messages.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check the right amount of messages were caught.
        $emails = $this->sink->get_messages();
        $this->assertCount(6, $emails);
        $this->sink->clear();

        // Check the emails content.
        $managercount = 0;
        $learnercount = 0;
        foreach ($emails as $email) {
            if (in_array($email->useridto, $usersprogram)) {
                $learnercount++;
                $this->assertEquals($email->subject, 'Learner ProgDue Message', 'unexpected default learner enrolment subject');
                $this->assertEquals($email->fullmessage, 'Hey dude, do your program', 'unexpected default learner enrolment message');
            } else {
                $managercount++;
                $this->assertEquals($email->useridto, $this->manager->id, 'unexpected user recieving message');
                $this->assertEquals($email->subject, 'Manager ProgDue Message', 'unexpected default manager enrolment subject');
                $this->assertEquals($email->fullmessage, 'Go tell your staff member to finish their program', 'unexpected custom manager enrolment message');
            }

            $this->assertEquals($email->fromemail, 'noreply@www.example.com', 'unexpected default userfrom email address');
        }
        $this->assertEquals(3, $managercount);
        $this->assertEquals(3, $learnercount);

        // Check that they all had logs created.
        $this->assertEquals(3, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user4->id, 'messageid' => $messageid)));

        // Complete the program for one more user.
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course2->id));
        $completion->mark_complete();

        // Attempt to send any program messages.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check the right amount of messages were caught.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();
    }

    /**
     * Make sure that program overdue messages are sent when a user's due date has been passed.
     *
     * Note that user6 has completed the courses but their program completion record indicates that they are overdue.
     */
    public function test_program_overdue_messages() {
        global $DB;

        // Set up the messages.
        $programmessagemanager = $this->program1->get_messagesmanager();
        $programmessagemanager->delete();
        $programmessagemanager->add_message(MESSAGETYPE_PROGRAM_OVERDUE);
        $programmessagemanager->save_messages();
        prog_messages_manager::get_program_messages_manager($this->program1->id, true); // Causes static cache to be reset.
        $messageid = $DB->get_field('prog_message', 'id',
            array('programid' => $this->program1->id, 'messagetype' => MESSAGETYPE_PROGRAM_OVERDUE));
        // Hack the message record to be triggered 10 days after overdue.
        $DB->set_field('prog_message', 'triggertime', DAYSECS * 10, array('id' => $messageid));

        // Create two courses.
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        // Assign courses to program.
        $coursesetdata = array(
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'courses' => array($course1)
            ),
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'courses' => array($course2)
            ),
        );
        $this->getDataGenerator()->create_coursesets_in_program($this->program1, $coursesetdata);

        // Assign users to program.
        $usersprogram = array($this->user1->id, $this->user2->id, $this->user3->id, $this->user4->id, $this->user5->id, $this->user6->id);
        $this->program_generator->assign_program($this->program1->id, $usersprogram);

        // Hack the due dates, 20 days ago.
        $duedate = time() - DAYSECS * 20;
        $progcompl1 = prog_load_completion($this->program1->id, $this->user1->id);
        $progcompl1->timedue = $duedate;
        $this->assertTrue(prog_write_completion($progcompl1));
        $progcompl2 = prog_load_completion($this->program1->id, $this->user2->id);
        $progcompl2->timedue = $duedate;
        $this->assertTrue(prog_write_completion($progcompl2));
        $progcompl3 = prog_load_completion($this->program1->id, $this->user3->id);
        $progcompl3->timedue = $duedate;
        $this->assertTrue(prog_write_completion($progcompl3));
        $progcompl4 = prog_load_completion($this->program1->id, $this->user4->id);
        $progcompl4->timedue = time() - DAYSECS * 5; // Overdue but not yet due to be sent.
        $this->assertTrue(prog_write_completion($progcompl4));
        $progcompl5 = prog_load_completion($this->program1->id, $this->user5->id);
        $progcompl5->timedue = time() + DAYSECS * 20; // Not yet due (so no message sent).
        $this->assertTrue(prog_write_completion($progcompl5));
        $progcompl6 = prog_load_completion($this->program1->id, $this->user6->id);
        $progcompl6->timedue = $duedate;
        $this->assertTrue(prog_write_completion($progcompl6));

        // Assign users and complete some courses. 0 for user1, 1 for user2, 2 for user3, 2 for user6.
        $this->getDataGenerator()->enrol_user($this->user2->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user2->id, 'course' => $course1->id));
        $completion->mark_complete();
        $this->getDataGenerator()->enrol_user($this->user3->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user3->id, 'course' => $course1->id));
        $completion->mark_complete();
        $this->getDataGenerator()->enrol_user($this->user3->id, $course2->id);
        $completion = new completion_completion(array('userid' => $this->user3->id, 'course' => $course2->id));
        $completion->mark_complete();
        $this->getDataGenerator()->enrol_user($this->user6->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user6->id, 'course' => $course1->id));
        $completion->mark_complete();
        $this->getDataGenerator()->enrol_user($this->user6->id, $course2->id);
        $completion = new completion_completion(array('userid' => $this->user6->id, 'course' => $course2->id));
        $completion->mark_complete();

        // Re-save user6's original program completion record to indicate that it's not complete, even though the courses are.
        $this->assertTrue(prog_write_completion($progcompl6));

        // Attempt to send any program messages.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check the right amount of messages were caught.
        $emails = $this->sink->get_messages();
        $this->assertCount(2, $emails);
        $this->sink->clear();

        // Check that they all had logs created.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Complete the program for one more user.
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course2->id));
        $completion->mark_complete();

        // Attempt to send any program messages.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check the right amount of messages were caught.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();
    }

    /**
     * Make sure that courseset completed messages are sent when a user completes the content.
     *
     * Also checks that messages are sent when user is assigned, if the user completed the content before assignment.
     * Also checks that multiple messages are triggered for multiple course sets within a program.
     */
    public function test_courseset_completed_messages() {
        global $DB;

        // Set up the messages.
        $programmessagemanager = $this->program1->get_messagesmanager();
        $programmessagemanager->delete();
        $programmessagemanager->add_message(MESSAGETYPE_COURSESET_COMPLETED);
        $programmessagemanager->save_messages();
        prog_messages_manager::get_program_messages_manager($this->program1->id, true); // Causes static cache to be reset.
        $messageid = $DB->get_field('prog_message', 'id',
            array('programid' => $this->program1->id, 'messagetype' => MESSAGETYPE_COURSESET_COMPLETED));

        // Create two courses.
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        // Assign courses to program.
        $coursesetdata = array(
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'courses' => array($course1)
            ),
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'courses' => array($course2)
            ),
        );
        $this->getDataGenerator()->create_coursesets_in_program($this->program1, $coursesetdata);

        // Assign user2 to courses.
        $this->getDataGenerator()->enrol_user($this->user2->id, $course1->id);
        $this->getDataGenerator()->enrol_user($this->user2->id, $course2->id);
        $this->getDataGenerator()->enrol_user($this->user3->id, $course1->id);
        $this->getDataGenerator()->enrol_user($this->user4->id, $course2->id);

        // Complete courses.
        $completion = new completion_completion(array('userid' => $this->user2->id, 'course' => $course1->id));
        $completion->mark_complete();
        $completion = new completion_completion(array('userid' => $this->user2->id, 'course' => $course2->id));
        $completion->mark_complete();
        $completion = new completion_completion(array('userid' => $this->user3->id, 'course' => $course1->id));
        $completion->mark_complete(); // User3 has completed one course set.
        $completion = new completion_completion(array('userid' => $this->user4->id, 'course' => $course2->id));
        $completion->mark_complete(); // User4 has completed THE SECOND course set.

        // Assign users to program.
        $usersprogram = array($this->user1->id, $this->user2->id, $this->user3->id, $this->user4->id);
        $this->program_generator->assign_program($this->program1->id, $usersprogram);

        // Check the right amount of messages were caught.
        $emails = $this->sink->get_messages();
        $this->assertCount(3, $emails);
        $this->sink->clear();

        // Check that they all had logs created.
        $this->assertEquals(3, $DB->count_records('prog_messagelog'));
        $this->assertEquals(2, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user3->id, 'messageid' => $messageid)));

        // Complete the program for some more users.
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course2->id));
        $completion->mark_complete();
        $completion = new completion_completion(array('userid' => $this->user3->id, 'course' => $course2->id));
        $completion->mark_complete();
        $completion = new completion_completion(array('userid' => $this->user4->id, 'course' => $course1->id));
        $completion->mark_complete(); // Triggers two messages.

        // Check the right amount of messages were caught.
        $emails = $this->sink->get_messages();
        $this->assertCount(5, $emails);
        $this->sink->clear();

        // Check that they all had logs created.
        $this->assertEquals(8, $DB->count_records('prog_messagelog'));
        $this->assertEquals(2, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(2, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));
        $this->assertEquals(2, $DB->count_records('prog_messagelog',
            array('userid' => $this->user3->id, 'messageid' => $messageid)));
        $this->assertEquals(2, $DB->count_records('prog_messagelog',
            array('userid' => $this->user4->id, 'messageid' => $messageid)));
    }

    /**
     * Make sure that courseset due messages are sent when a user's due date is nearly reached.
     *
     * Note that user6 has completed the courses but their program completion records indicate that they are due.
     */
    public function test_courseset_due_messages() {
        global $DB;

        // Set up the messages.
        $programmessagemanager = $this->program1->get_messagesmanager();
        $programmessagemanager->delete();
        $programmessagemanager->add_message(MESSAGETYPE_COURSESET_DUE);
        $programmessagemanager->save_messages();
        prog_messages_manager::get_program_messages_manager($this->program1->id, true); // Causes static cache to be reset.
        $messageid = $DB->get_field('prog_message', 'id',
            array('programid' => $this->program1->id, 'messagetype' => MESSAGETYPE_COURSESET_DUE));
        // Hack the message record to be triggered 100 days before due.
        $DB->set_field('prog_message', 'triggertime', DAYSECS * 100, array('id' => $messageid));

        // Create two courses.
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        // Assign courses to program.
        $coursesetdata = array(
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'timeallowed' => DAYSECS * 3,
                'courses' => array($course1)
            ),
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'timeallowed' => DAYSECS * 3,
                'courses' => array($course2)
            ),
        );
        $this->getDataGenerator()->create_coursesets_in_program($this->program1, $coursesetdata);

        // Assign users to program.
        $usersprogram = array($this->user1->id, $this->user2->id, $this->user3->id, $this->user4->id, $this->user5->id, $this->user6->id);
        $this->program_generator->assign_program($this->program1->id, $usersprogram);

        // Hack the due dates, 10 days from now.
        $duedate = time() + DAYSECS * 10;
        $DB->set_field('prog_completion', 'timedue', $duedate,
            array('programid' => $this->program1->id, 'userid' => $this->user1->id));
        $DB->set_field('prog_completion', 'timedue', $duedate,
            array('programid' => $this->program1->id, 'userid' => $this->user2->id));
        $DB->set_field('prog_completion', 'timedue', $duedate,
            array('programid' => $this->program1->id, 'userid' => $this->user3->id));
        $DB->set_field('prog_completion', 'timedue', time() - DAYSECS * 10, // Overdue (but should still send due).
            array('programid' => $this->program1->id, 'userid' => $this->user4->id));
        $DB->set_field('prog_completion', 'timedue', time() + DAYSECS * 200, // Not yet due (so no message sent).
            array('programid' => $this->program1->id, 'userid' => $this->user5->id));
        $DB->set_field('prog_completion', 'timedue', $duedate,
            array('programid' => $this->program1->id, 'userid' => $this->user6->id));

        // Load user6's prog completion record, which is currently INCOMPLETE.
        $progcompl6 = prog_load_completion($this->program1->id, $this->user6->id);

        // Assign users and complete some courses. 0 for user1, 1 for user2, 2 for user3, 2 for user6.
        $this->getDataGenerator()->enrol_user($this->user2->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user2->id, 'course' => $course1->id));
        $completion->mark_complete();
        $this->getDataGenerator()->enrol_user($this->user3->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user3->id, 'course' => $course1->id));
        $completion->mark_complete();
        $this->getDataGenerator()->enrol_user($this->user3->id, $course2->id);
        $completion = new completion_completion(array('userid' => $this->user3->id, 'course' => $course2->id));
        $completion->mark_complete();
        $this->getDataGenerator()->enrol_user($this->user6->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user6->id, 'course' => $course1->id));
        $completion->mark_complete();
        $this->getDataGenerator()->enrol_user($this->user6->id, $course2->id);
        $completion = new completion_completion(array('userid' => $this->user6->id, 'course' => $course2->id));
        $completion->mark_complete();

        // Save user6's original program completion records to indicate that they're not complete, even though the courses are.
        $this->assertTrue(prog_write_completion($progcompl6));
        $sql = "SELECT MAX(coursesetid)
                  FROM {prog_completion}
                 WHERE programid = :programid
                   AND userid = :userid";
        $params = array(
            'programid' => $this->program1->id,
            'userid' => $this->user6->id,
            'statuscoursesetincomplete' => STATUS_COURSESET_INCOMPLETE
        );
        $secondcoursesetid = $DB->get_field_sql($sql, $params);
        $sql = "UPDATE {prog_completion}
                   SET status = :statuscoursesetincomplete,
                       timecompleted = 0
                 WHERE programid = :programid
                   AND userid = :userid
                   AND coursesetid = :secondcoursesetid";
        $params = array(
            'programid' => $this->program1->id,
            'userid' => $this->user6->id,
            'secondcoursesetid' => $secondcoursesetid,
            'statuscoursesetincomplete' => STATUS_COURSESET_INCOMPLETE
        );
        $DB->execute($sql, $params);

        // Attempt to send any program messages.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check the right amount of messages were caught.
        $emails = $this->sink->get_messages();
        $this->assertCount(3, $emails);
        $this->sink->clear();

        // Check that they all had logs created.
        $this->assertEquals(3, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user4->id, 'messageid' => $messageid)));

        // Complete the program for one more user.
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course2->id));
        $completion->mark_complete();

        // Attempt to send any program messages.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check the right amount of messages were caught.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();
    }

    /**
     * Make sure that courseset overdue messages are sent when a user's due date is nearly reached.
     *
     * Note that user6 has completed the courses but their program completion records indicate that they are overdue.
     */
    public function test_courseset_overdue_messages() {
        global $DB;

        // Set up the messages.
        $programmessagemanager = $this->program1->get_messagesmanager();
        $programmessagemanager->delete();
        $programmessagemanager->add_message(MESSAGETYPE_COURSESET_OVERDUE);
        $programmessagemanager->save_messages();
        prog_messages_manager::get_program_messages_manager($this->program1->id, true); // Causes static cache to be reset.
        $messageid = $DB->get_field('prog_message', 'id',
            array('programid' => $this->program1->id, 'messagetype' => MESSAGETYPE_COURSESET_OVERDUE));
        // Hack the message record to be triggered 10 days after overdue.
        $DB->set_field('prog_message', 'triggertime', DAYSECS * 10, array('id' => $messageid));

        // Create two courses.
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        // Assign courses to program.
        $coursesetdata = array(
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'timeallowed' => DAYSECS * 3,
                'courses' => array($course1)
            ),
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'timeallowed' => DAYSECS * 3,
                'courses' => array($course2)
            ),
        );
        $this->getDataGenerator()->create_coursesets_in_program($this->program1, $coursesetdata);

        // Assign users to program.
        $usersprogram = array($this->user1->id, $this->user2->id, $this->user3->id, $this->user4->id, $this->user5->id, $this->user6->id);
        $this->program_generator->assign_program($this->program1->id, $usersprogram);

        // Load user6's prog completion record, which is currently INCOMPLETE.
        $progcompl6 = prog_load_completion($this->program1->id, $this->user6->id);

        // Assign users and complete some courses. 0 for user1, 1 for user2, 2 for user3.
        $this->getDataGenerator()->enrol_user($this->user2->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user2->id, 'course' => $course1->id));
        $completion->mark_complete();
        $this->getDataGenerator()->enrol_user($this->user3->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user3->id, 'course' => $course1->id));
        $completion->mark_complete();
        $this->getDataGenerator()->enrol_user($this->user3->id, $course2->id);
        $completion = new completion_completion(array('userid' => $this->user3->id, 'course' => $course2->id));
        $completion->mark_complete();
        $this->getDataGenerator()->enrol_user($this->user6->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user6->id, 'course' => $course1->id));
        $completion->mark_complete();
        $this->getDataGenerator()->enrol_user($this->user6->id, $course2->id);
        $completion = new completion_completion(array('userid' => $this->user6->id, 'course' => $course2->id));
        $completion->mark_complete();

        // Hack the due dates, 30 days ago.
        $duedate = time() - DAYSECS * 30;
        $DB->set_field('prog_completion', 'timedue', $duedate,
            array('programid' => $this->program1->id, 'userid' => $this->user1->id));
        $DB->set_field('prog_completion', 'timedue', $duedate,
            array('programid' => $this->program1->id, 'userid' => $this->user2->id));
        $DB->set_field('prog_completion', 'timedue', $duedate,
            array('programid' => $this->program1->id, 'userid' => $this->user3->id));
        $DB->set_field('prog_completion', 'timedue', time() - DAYSECS * 5, // Overdue but not yet due to be sent.
            array('programid' => $this->program1->id, 'userid' => $this->user4->id));
        $DB->set_field('prog_completion', 'timedue', time() + DAYSECS * 20, // Not yet due (so no message sent).
            array('programid' => $this->program1->id, 'userid' => $this->user5->id));
        $DB->set_field('prog_completion', 'timedue', $duedate,
            array('programid' => $this->program1->id, 'userid' => $this->user6->id));

        // Save user6's original program completion records to indicate that they're not complete, even though the courses are.
        $this->assertTrue(prog_write_completion($progcompl6));
        $sql = "SELECT MAX(coursesetid)
                  FROM {prog_completion}
                 WHERE programid = :programid
                   AND userid = :userid";
        $params = array(
            'programid' => $this->program1->id,
            'userid' => $this->user6->id,
            'statuscoursesetincomplete' => STATUS_COURSESET_INCOMPLETE
        );
        $secondcoursesetid = $DB->get_field_sql($sql, $params);
        $sql = "UPDATE {prog_completion}
                   SET status = :statuscoursesetincomplete,
                       timecompleted = 0
                 WHERE programid = :programid
                   AND userid = :userid
                   AND coursesetid = :secondcoursesetid";
        $params = array(
            'programid' => $this->program1->id,
            'userid' => $this->user6->id,
            'secondcoursesetid' => $secondcoursesetid,
            'statuscoursesetincomplete' => STATUS_COURSESET_INCOMPLETE
        );
        $DB->execute($sql, $params);

        // Attempt to send any program messages.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check the right amount of messages were caught.
        $emails = $this->sink->get_messages();
        $this->assertCount(2, $emails);
        $this->sink->clear();

        // Check that they all had logs created.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Complete the program for one more user.
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course2->id));
        $completion->mark_complete();

        // Attempt to send any program messages.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check the right amount of messages were caught.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();
    }

    /**
     * Make sure that program learner follow-up messages are sent after learner has completed the program and time has passed.
     */
    public function test_program_learner_followup() {
        global $DB;

        // Set up the messages.
        $programmessagemanager = $this->program1->get_messagesmanager();
        $programmessagemanager->delete();
        $programmessagemanager->add_message(MESSAGETYPE_LEARNER_FOLLOWUP);
        $programmessagemanager->save_messages();
        prog_messages_manager::get_program_messages_manager($this->program1->id, true); // Causes static cache to be reset.
        $messageid = $DB->get_field('prog_message', 'id',
            array('programid' => $this->program1->id, 'messagetype' => MESSAGETYPE_LEARNER_FOLLOWUP));
        // Hack the message record to be triggered 10 days after completion.
        $DB->set_field('prog_message', 'triggertime', DAYSECS * 10, array('id' => $messageid));

        // Create two courses.
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        // Assign courses to program.
        $coursesetdata = array(
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'courses' => array($course1)
            ),
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'courses' => array($course2)
            ),
        );
        $this->getDataGenerator()->create_coursesets_in_program($this->program1, $coursesetdata);

        // Assign users to program.
        $usersprogram = array($this->user1->id, $this->user2->id, $this->user3->id, $this->user4->id, $this->user5->id);
        $this->program_generator->assign_program($this->program1->id, $usersprogram);

        // Assign users and complete some courses. 0 for user1, 1 for user2, 2 for user3.
        $this->getDataGenerator()->enrol_user($this->user2->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user2->id, 'course' => $course1->id));
        $completion->mark_complete();
        $this->getDataGenerator()->enrol_user($this->user3->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user3->id, 'course' => $course1->id));
        $completion->mark_complete();
        $this->getDataGenerator()->enrol_user($this->user3->id, $course2->id);
        $completion = new completion_completion(array('userid' => $this->user3->id, 'course' => $course2->id));
        $completion->mark_complete();

        // Attempt to send any program messages.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check the right amount of messages were caught.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that there are no logs.
        $this->assertEquals(0, $DB->count_records('prog_messagelog'));

        // Hack the timecomplete, 30 days ago.
        $timecompleted = time() - DAYSECS * 30;
        $DB->set_field('prog_completion', 'timecompleted', $timecompleted,
            array('programid' => $this->program1->id, 'userid' => $this->user3->id));

        // Attempt to send any program messages.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check the right amount of messages were caught.
        $emails = $this->sink->get_messages();
        $this->assertCount(1, $emails);
        $this->sink->clear();

        // Check that only user3 has a message log.
        $this->assertEquals(1, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user3->id, 'messageid' => $messageid)));

        // Complete the program for one more user.
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course2->id));
        $completion->mark_complete();

        // Hack the timecomplete, 30 days ago.
        $timecompleted = time() - DAYSECS * 30;
        $DB->set_field('prog_completion', 'timecompleted', $timecompleted,
            array('programid' => $this->program1->id, 'userid' => $this->user1->id));

        // Attempt to send any program messages.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check the right amount of messages were caught.
        $emails = $this->sink->get_messages();
        $this->assertCount(1, $emails);
        $this->sink->clear();

        // Check that user1 and user3 have a message log.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user3->id, 'messageid' => $messageid)));
    }


    /**
     * Test messages to managers and staff members when the staff member is suspended
     */
    public function test_program_suspended_enrolment_messages() {
        global $DB;

        $this->sink->clear();
        // User 1 is not suspended, user 2 is suspended, and the manager for both is not suspended.
        $this->user1->suspended = 0;
        $DB->update_record('user', $this->user1);
        $this->user2->suspended = 1;
        $DB->update_record('user', $this->user2);

        // Set up the messages.
        $programmessagemanager = $this->program1->get_messagesmanager();
        $programmessagemanager->add_message(MESSAGETYPE_UNENROLMENT);
        $programmessagemanager->save_messages();
        prog_messages_manager::get_program_messages_manager($this->program1->id, true); // Causes static cache to be reset.

        $enrolmentmessage = $DB->get_record('prog_message', array('programid' => $this->program1->id, 'messagetype' => MESSAGETYPE_ENROLMENT));
        $unenrolmentmessage = $DB->get_record('prog_message', array('programid' => $this->program1->id, 'messagetype' => MESSAGETYPE_UNENROLMENT));

        // Some quick edits to the enrolment message content.
        $enrolmentmessage->managersubject = '';
        $enrolmentmessage->managermessage = 'Staff Program Assignment';
        $enrolmentmessage->notifymanager = 1;
        $DB->update_record('prog_message', $enrolmentmessage);
        prog_messages_manager::get_program_messages_manager($this->program1->id, true); // Causes static cache to be reset.

        // Assign users to program1.
        $usersprogram1 = array($this->user1->id, $this->user2->id);
        $this->program_generator->assign_program($this->program1->id, $usersprogram1);

        // Attempt to send any program messages.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        /*
         * Expectations is that user 1 will get a message, and the manager will recieve a copy.
         * However user 2 will not get a message, and the manager will not recieve a copy.
         *
         */
        $emails = $this->sink->get_messages();

        // Annoyingly the sink catches the email to the suspended learner before it gets stopped.
        $this->assertCount(3, $emails);
        $this->sink->clear();

        // Check the emails content.
        $managercount = 0;
        $learnercount = 0;
        foreach ($emails as $email) {
            if (in_array($email->useridto, $usersprogram1)) {
                $learnercount++;
                $this->assertEquals($email->subject, 'You have been enrolled on program Program Fullname', 'unexpected default learner enrolment subject');
                $this->assertEquals($email->fullmessage, 'You are now enrolled on program Program Fullname.', 'unexpected default learner enrolment message');
            } else {
                $managercount++;
                $this->assertEquals($email->useridto, $this->manager->id, 'unexpected user recieving message');
                $this->assertEquals($email->subject, 'Learner enrolled', 'unexpected default manager enrolment subject');
                $this->assertEquals($email->fullmessage, 'Staff Program Assignment', 'unexpected custom manager enrolment message');
            }

            $this->assertEquals($email->fromemail, 'noreply@www.example.com', 'unexpected default userfrom email address');
        }
        $this->assertEquals(1, $managercount);
        $this->assertEquals(2, $learnercount);

        // Check that they all had logs created.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $enrolmentmessage->id)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $enrolmentmessage->id)));
    }
}
