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
 * @author David Curry <david.curry@totaralms.com>
 * @package totara
 * @subpackage certification
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/lib/moodlelib.php');
require_once($CFG->dirroot . '/totara/certification/lib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');

/**
 * Certification module PHPUnit archive test class
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit --verbose totara_certification_reassignments_testcase totara/certification/tests/reassignment_test.php
 */
class totara_certification_reassignments_testcase extends reportcache_advanced_testcase {

    protected $monthsecs;
    protected $program;
    protected $courses;
    protected $users;
    protected $task;

    protected function tearDown() {
        $this->monthsecs = null;
        $this->program = null;
        $this->courses = null;
        $this->users = null;
        $this->task = null;
        parent::tearDown();
    }

    /**
     * Setup.
     */
    public function setUp() {
        parent::setup();

        $this->resetAfterTest(true);

        // Turn off programs. This is to test that it doesn't interfere with certification completion.
        set_config('enableprograms', TOTARA_DISABLEFEATURE);

        $this->task = new \totara_certification\task\update_certification_task();

        $this->monthsecs = 4 * WEEKSECS;

        // Create a certification.
        $certdata = array(
            'cert_activeperiod' => '6 Months',
            'cert_windowperiod' => '2 Months',
        );
        $this->program = $this->getDataGenerator()->create_certification($certdata);

        $course1 = $this->getDataGenerator()->create_course(array('fullname' => 'course1'));
        $this->courses[1] = $course1;
        $course2 = $this->getDataGenerator()->create_course(array('fullname' => 'course2'));
        $this->courses[2] = $course2;
        $course3 = $this->getDataGenerator()->create_course(array('fullname' => 'course3'));
        $this->courses[3] = $course3;

        $this->getDataGenerator()->add_courseset_program($this->program->id, array($course1->id, $course2->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($this->program->id, array($course3->id), CERTIFPATH_RECERT);

        // Create some test users and store them in an array.
        // User 1 is our guinea pig.
        $user1 = $this->getDataGenerator()->create_user(array('fullname' => 'user1'));
        $this->users[1] = $user1;

        // User 2 is assigned but left alone.
        $user2 = $this->getDataGenerator()->create_user(array('fullname' => 'user2'));
        $this->users[2] = $user2;

        // User 3 is not assigned.
        $user3 = $this->getDataGenerator()->create_user(array('fullname' => 'user3'));
        $this->users[3] = $user3;
    }

    private function setup_certified_state($completetime) {
        global $DB;

        $windowtime = $completetime + (4 * $this->monthsecs);
        $expiretime = $completetime + (6 * $this->monthsecs);

        // Assign the program to users.
        $this->getDataGenerator()->assign_program($this->program->id, array($this->users[1]->id, $this->users[2]->id));

        // Complete the courses in the certification path.
        $completion = new completion_completion(array('userid' => $this->users[1]->id, 'course' => $this->courses[1]->id));
        $completion->mark_complete($completetime);

        $certcomprec = $DB->get_record('course_completions', array('course' => $this->courses[1]->id, 'userid' => $this->users[1]->id));
        $this->assertEquals($certcomprec->timecompleted, $completetime);

        $completion = new completion_completion(array('userid' => $this->users[1]->id, 'course' => $this->courses[2]->id));
        $completion->mark_complete($completetime);

        $certcomprec = $DB->get_record('course_completions', array('course' => $this->courses[2]->id, 'userid' => $this->users[1]->id));
        $this->assertEquals($certcomprec->timecompleted, $completetime);

        // Get the completion record for user 1 and update the times.
        $certcomprec = $DB->get_record('certif_completion', array('userid' => $this->users[1]->id));
        $certcomprec->timecompleted = $completetime;
        $certcomprec->timewindowopens = $windowtime;
        $certcomprec->timeexpires = $expiretime;
        $DB->update_record('certif_completion', $certcomprec);

        return array('complete' => $completetime, 'window' => $windowtime, 'expire' => $expiretime);
    }

    /**
     * Test restoration of certification completion
     * when a user was unassigned pre-window and restored straight away.
     */
    public function test_restoration_certified_prewindow() {
        global $DB, $CFG;
        $this->resetAfterTest(true);

        // Set the restoration setting.
        $CFG->restorecertifenrolments = 1;

        $progid = $this->program->id;
        $times = $this->setup_certified_state(time() - $this->monthsecs); // Setup with completion 1 month ago.

        // Unassign.
        $this->getDataGenerator()->assign_program($progid, array($this->users[2]->id));

        // Run the certification task.
        $this->task->execute();

        // Check that the unassignment has created the expected history record.
        $certhistrec = $DB->get_record('certif_completion_history', array('userid' => $this->users[1]->id));
        $this->assertEquals($certhistrec->timecompleted, $times['complete']);
        $this->assertEquals($certhistrec->timewindowopens, $times['window']);
        $this->assertEquals($certhistrec->timeexpires, $times['expire']);
        $this->assertEquals($certhistrec->unassigned, 1);

        $certcomprec = $DB->record_exists('certif_completion', array('userid' => $this->users[1]->id));
        $this->assertEquals($certcomprec, false);

         // Reassign.
        $this->getDataGenerator()->assign_program($progid, array($this->users[1]->id, $this->users[2]->id));

        // Run the certificationtrue task.
        $this->task->execute();

        // Check the restored certification completion record.
        $certcomprec = $DB->get_record('certif_completion', array('userid' => $this->users[1]->id));
        $this->assertEquals($certcomprec->timecompleted, $times['complete']);
        $this->assertEquals($certcomprec->timewindowopens, $times['window']);
        $this->assertEquals($certcomprec->timeexpires, $times['expire']);
        $this->assertEquals($certcomprec->status, CERTIFSTATUS_COMPLETED); // Check they are still certified.
        $this->assertEquals($certcomprec->renewalstatus, CERTIFRENEWALSTATUS_NOTDUE); // Check the window has not opened.

        // Check the history record no longer exists, it will be created when the window opens.
        $this->assertFalse($DB->record_exists('certif_completion_history', array('userid' => $this->users[1]->id)));

        // Check the course completions are still there and still marked as complete.
        $comprecs = $DB->get_records('course_completions', array('userid' => $this->users[1]->id, 'timecompleted' => $times['complete']));
        $this->assertEquals(count($comprecs), 2);
    }

    /**
     * Test restoration of certification completion
     * when a user was unassigned pre-window and restored post-window
     */
    public function test_restoration_certified_postwindow() {
        global $DB, $CFG;
        $this->resetAfterTest(true);

        // Set the restoration setting.
        $CFG->restorecertifenrolments = 1;

        $progid = $this->program->id;
        $times = $this->setup_certified_state(time() - (5 * $this->monthsecs)); // Setup with completion 5 months ago.

        // Unassign.
        $this->getDataGenerator()->assign_program($progid, array($this->users[2]->id));

        // Run the certification task.
        $this->task->execute();

        // Check that the unassignment has created the expected history record.
        $certhistrec = $DB->get_record('certif_completion_history', array('userid' => $this->users[1]->id));
        $this->assertEquals($certhistrec->timecompleted, $times['complete']);
        $this->assertEquals($certhistrec->timewindowopens, $times['window']);
        $this->assertEquals($certhistrec->timeexpires, $times['expire']);
        $this->assertEquals($certhistrec->unassigned, 1);

        $certcomprec = $DB->record_exists('certif_completion', array('userid' => $this->users[1]->id));
        $this->assertEquals($certcomprec, false);

         // Reassign.
        $this->getDataGenerator()->assign_program($progid, array($this->users[1]->id, $this->users[2]->id));

        // Check the restored certification completion record.
        $certcomprec = $DB->get_record('certif_completion', array('userid' => $this->users[1]->id));
        $this->assertEquals($certcomprec->timecompleted, $times['complete']);
        $this->assertEquals($certcomprec->timewindowopens, $times['window']);
        $this->assertEquals($certcomprec->timeexpires, $times['expire']);

        // Check the history record no longer exists, it will be created when the window opens.
        $this->assertFalse($DB->record_exists('certif_completion_history', array('userid' => $this->users[1]->id)));

        // Check the course completions are still there and still marked as complete.
        $comprecs = $DB->get_records('course_completions', array('userid' => $this->users[1]->id, 'timecompleted' => $times['complete']));
        $this->assertEquals(count($comprecs), 2);

        // Run the certification task.
        $this->task->execute();

        // Check the restored certification completion record.
        $certcomprec = $DB->get_record('certif_completion', array('userid' => $this->users[1]->id));
        $this->assertEquals($certcomprec->timecompleted, $times['complete']);
        $this->assertEquals($certcomprec->timewindowopens, $times['window']);
        $this->assertEquals($certcomprec->timeexpires, $times['expire']);
        $this->assertEquals($certcomprec->status, CERTIFSTATUS_COMPLETED); // Check they are still certified.
        $this->assertEquals($certcomprec->renewalstatus, CERTIFRENEWALSTATUS_DUE); // Check the window has opened.

        // Check the history record is still there but no longer marked as unassigned.
        $certhistrec = $DB->get_record('certif_completion_history', array('userid' => $this->users[1]->id));
        $this->assertEquals($certhistrec->timecompleted, $times['complete']);
        $this->assertEquals($certhistrec->timewindowopens, $times['window']);
        $this->assertEquals($certhistrec->timeexpires, $times['expire']);
        $this->assertEquals($certhistrec->unassigned, 0);

        // Check the course completions are still there and still marked as complete.
        $comprecs = $DB->get_records('course_completions', array('userid' => $this->users[1]->id, 'timecompleted' => $times['complete']));
        $this->assertEquals(count($comprecs), 0);

        $comphistrecs = $DB->get_records('course_completion_history', array('userid' => $this->users[1]->id, 'timecompleted' => $times['complete']));
        $this->assertEquals(count($comphistrecs), 2);
    }

    /**
     * Test restoration of certification completion
     * when a user was unassigned pre-window and restored after expiry
     */
    public function test_restoration_certified_expired() {
        global $DB, $CFG;
        $this->resetAfterTest(true);

        // Set the restoration setting.
        $CFG->restorecertifenrolments = 1;

        $progid = $this->program->id;
        $times = $this->setup_certified_state(time() - (7 * $this->monthsecs)); // Setup with completion 7 months ago.

        // Unassign.
        $this->getDataGenerator()->assign_program($progid, array($this->users[2]->id));

        // Run the certification task.
        $this->task->execute();

        // Check that the unassignment has created the expected history record.
        $certhistrec = $DB->get_record('certif_completion_history', array('userid' => $this->users[1]->id));
        $this->assertEquals($certhistrec->timecompleted, $times['complete']);
        $this->assertEquals($certhistrec->timewindowopens, $times['window']);
        $this->assertEquals($certhistrec->timeexpires, $times['expire']);
        $this->assertEquals($certhistrec->unassigned, 1);

        $certcomprec = $DB->record_exists('certif_completion', array('userid' => $this->users[1]->id));
        $this->assertEquals($certcomprec, false);

        // Reassign.
        $this->getDataGenerator()->assign_program($progid, array($this->users[1]->id, $this->users[2]->id));

        $certcomprec = $DB->record_exists('certif_completion', array('userid' => $this->users[1]->id));
        $this->assertEquals($certcomprec, true);

        // Check the restored certification completion record.
        $certcomprec = $DB->get_record('certif_completion', array('userid' => $this->users[1]->id));
        $this->assertEquals($certcomprec->timecompleted, $times['complete']);
        $this->assertEquals($certcomprec->timewindowopens, $times['window']);
        $this->assertEquals($certcomprec->timeexpires, $times['expire']);

        // Check the history record no longer exists, it will be created when the window opens.
        $this->assertFalse($DB->record_exists('certif_completion_history', array('userid' => $this->users[1]->id)));

        // Check the course completions are still there and still marked as complete.
        $comprecs = $DB->get_records('course_completions', array('userid' => $this->users[1]->id, 'timecompleted' => $times['complete']));
        $this->assertEquals(count($comprecs), 2);

        // Run the certification task.
        $this->task->execute();

        // Check the restored certification completion record.
        $certcomprec = $DB->get_record('certif_completion', array('userid' => $this->users[1]->id));
        $this->assertEquals($certcomprec->timecompleted, 0);
        $this->assertEquals($certcomprec->timewindowopens, 0);
        $this->assertEquals($certcomprec->timeexpires, 0);
        $this->assertEquals($certcomprec->status, CERTIFSTATUS_EXPIRED); // Check they are still certified.
        $this->assertEquals($certcomprec->renewalstatus, CERTIFRENEWALSTATUS_EXPIRED); // Check the window has opened.

        // Check the history record is still there but no longer marked as unassigned.
        $certhistrec = $DB->get_record('certif_completion_history', array('userid' => $this->users[1]->id));
        $this->assertEquals($certhistrec->timecompleted, $times['complete']);
        $this->assertEquals($certhistrec->timewindowopens, $times['window']);
        $this->assertEquals($certhistrec->timeexpires, $times['expire']);
        $this->assertEquals($certhistrec->unassigned, 0);

        // Check the course completions are still there and still marked as complete.
        $comprecs = $DB->get_records('course_completions', array('userid' => $this->users[1]->id, 'timecompleted' => $times['complete']));
        $this->assertEquals(count($comprecs), 0);

        $comphistrecs = $DB->get_records('course_completion_history', array('userid' => $this->users[1]->id, 'timecompleted' => $times['complete']));
        $this->assertEquals(count($comphistrecs), 2);
    }

    private function setup_recertified_state($recompletetime) {
        global $DB;

        // Set up the initial completions.
        $times = $this->setup_certified_state($recompletetime - (5 * $this->monthsecs));

        // Run the window opening to move things around without triggering expiry.
        recertify_window_opens_stage();

        $completetime = $recompletetime;
        $windowtime = $completetime + (4 * $this->monthsecs);
        $expiretime = $completetime + (6 * $this->monthsecs);

        // Complete the courses in the recertification path.
        $completion = new completion_completion(array('userid' => $this->users[1]->id, 'course' => $this->courses[3]->id));
        $completion->mark_complete($completetime);

        $comprec = $DB->record_exists('course_completions', array('course' => $this->courses[1]->id, 'userid' => $this->users[1]->id));
        $this->assertEquals($comprec, false);
        $comprec = $DB->record_exists('course_completions', array('course' => $this->courses[2]->id, 'userid' => $this->users[1]->id));
        $this->assertEquals($comprec, false);
        $comprec = $DB->get_record('course_completions', array('course' => $this->courses[3]->id, 'userid' => $this->users[1]->id));
        $this->assertEquals($comprec->timecompleted, $completetime);

        // Get the completion record for user 1 and update the times.
        $certcomprec = $DB->get_record('certif_completion', array('userid' => $this->users[1]->id));
        $certcomprec->timecompleted = $completetime;
        $certcomprec->timewindowopens = $windowtime;
        $certcomprec->timeexpires = $expiretime;
        $DB->update_record('certif_completion', $certcomprec);

        // Run the window opening to move things around without triggering expiry.
        recertify_window_opens_stage();

        $data = array(
            'complete' => $completetime,
            'window' => $windowtime,
            'expire' => $expiretime,
            'oldcomplete' => $times['complete'],
            'oldwindow' => $times['window'],
            'oldexpire' => $times['expire'],
        );

        return $data;
    }

    /**
     * Test restoration of recertified completion
     * when a user was unassigned pre-window and restored straight away.
     */
    public function test_restoration_recertified_prewindow() {
        global $DB, $CFG;
        $this->resetAfterTest(true);

        // Set the restoration setting.
        $CFG->restorecertifenrolments = 1;

        $progid = $this->program->id;
        $times = $this->setup_recertified_state(time() - (3 * $this->monthsecs)); // Setup with recertification 3 months ago.

        // Unassign.
        $this->getDataGenerator()->assign_program($progid, array($this->users[2]->id));

        // Run the certification task.
        $this->task->execute();

        // Check that the unassignment has created the expected history record.
        $certhistrecs = $DB->get_records('certif_completion_history', array('userid' => $this->users[1]->id));
        $this->assertEquals(count($certhistrecs), 2);

        $certhistrecnew = null;
        $certhistrecold = null;
        // Figure out which is the old history record and which is the new.
        foreach ($certhistrecs as $certhistrec) {
            if (empty($certhistrecnew) || $certhistrec->timecompleted > $certhistrecnew->timecompleted) {
                $certhistrecold = $certhistrecnew;
                $certhistrecnew = $certhistrec;
            } else {
                $certhistrecold = $certhistrec;
            }
        }

        // Then check them both.
        $this->assertEquals($certhistrecnew->timecompleted, $times['complete']);
        $this->assertEquals($certhistrecnew->timewindowopens, $times['window']);
        $this->assertEquals($certhistrecnew->timeexpires, $times['expire']);
        $this->assertEquals($certhistrecnew->unassigned, 1);

        $this->assertEquals($certhistrecold->timecompleted, $times['oldcomplete']);
        $this->assertEquals($certhistrecold->timewindowopens, $times['oldwindow']);
        $this->assertEquals($certhistrecold->timeexpires, $times['oldexpire']);
        $this->assertEquals($certhistrecold->unassigned, 0);

        $certcomprec = $DB->record_exists('certif_completion', array('userid' => $this->users[1]->id));
        $this->assertEquals($certcomprec, false);

         // Reassign.
        $this->getDataGenerator()->assign_program($progid, array($this->users[1]->id, $this->users[2]->id));

        // Run the certification task.
        $this->task->execute();

        // Check the restored certification completion record.
        $certcomprec = $DB->get_record('certif_completion', array('userid' => $this->users[1]->id));
        $this->assertEquals($certcomprec->timecompleted, $times['complete']);
        $this->assertEquals($certcomprec->timewindowopens, $times['window']);
        $this->assertEquals($certcomprec->timeexpires, $times['expire']);
        $this->assertEquals($certcomprec->certifpath, CERTIFPATH_RECERT);
        $this->assertEquals($certcomprec->status, CERTIFSTATUS_COMPLETED); // Check they are still certified.
        $this->assertEquals($certcomprec->renewalstatus, CERTIFRENEWALSTATUS_NOTDUE); // Check the window has not opened.

        // Check the history record no longer exists, it will be created when the window opens.
        $this->assertFalse($DB->record_exists('certif_completion_history', array('userid' => $this->users[1]->id, 'timecompleted' => $times['complete'])));

        // Check the course completions are still there and still marked as complete.
        $comprecs = $DB->get_records('course_completions', array('userid' => $this->users[1]->id, 'timecompleted' => $times['complete']));
        $this->assertEquals(count($comprecs), 1);
    }

    /**
     * Test restoration of recertified completion
     * when a user was unassigned pre-window and restored post window.
     */
    public function test_restoration_recertified_postwindow() {
        global $DB, $CFG;
        $this->resetAfterTest(true);

        // Set the restoration setting.
        $CFG->restorecertifenrolments = 1;

        $progid = $this->program->id;
        $times = $this->setup_recertified_state(time() - (5 * $this->monthsecs)); // Setup with recertification 5 months ago.

        // Unassign.
        $this->getDataGenerator()->assign_program($progid, array($this->users[2]->id));

        // Run the certification task.
        $this->task->execute();

        // Check that the unassignment has created the expected history record.
        $certhistrecs = $DB->get_records('certif_completion_history', array('userid' => $this->users[1]->id));
        $this->assertEquals(count($certhistrecs), 2);

        $certhistrecnew = null;
        $certhistrecold = null;
        // Figure out which is the old history record and which is the new.
        foreach ($certhistrecs as $certhistrec) {
            if (empty($certhistrecnew) || $certhistrec->timecompleted > $certhistrecnew->timecompleted) {
                $certhistrecold = $certhistrecnew;
                $certhistrecnew = $certhistrec;
            } else {
                $certhistrecold = $certhistrec;
            }
        }

        // Then check them both.
        $this->assertEquals($certhistrecnew->timecompleted, $times['complete']);
        $this->assertEquals($certhistrecnew->timewindowopens, $times['window']);
        $this->assertEquals($certhistrecnew->timeexpires, $times['expire']);
        $this->assertEquals($certhistrecnew->unassigned, 1);

        $this->assertEquals($certhistrecold->timecompleted, $times['oldcomplete']);
        $this->assertEquals($certhistrecold->timewindowopens, $times['oldwindow']);
        $this->assertEquals($certhistrecold->timeexpires, $times['oldexpire']);
        $this->assertEquals($certhistrecold->unassigned, 0);

        $certcomprec = $DB->record_exists('certif_completion', array('userid' => $this->users[1]->id));
        $this->assertEquals($certcomprec, false);

         // Reassign.
        $this->getDataGenerator()->assign_program($progid, array($this->users[1]->id, $this->users[2]->id));

        // Run the certification task.
        $this->task->execute();

        // Check the restored certification completion record.
        $certcomprec = $DB->get_record('certif_completion', array('userid' => $this->users[1]->id));
        $this->assertEquals($certcomprec->timecompleted, $times['complete']);
        $this->assertEquals($certcomprec->timewindowopens, $times['window']);
        $this->assertEquals($certcomprec->timeexpires, $times['expire']);
        $this->assertEquals($certcomprec->certifpath, CERTIFPATH_RECERT); // Check they are still on the recert path.
        $this->assertEquals($certcomprec->status, CERTIFSTATUS_COMPLETED); // Check they are still certified.
        $this->assertEquals($certcomprec->renewalstatus, CERTIFRENEWALSTATUS_DUE); // Check the window has opened.

        // Check the history record is still there but no longer marked as unassigned.
        $certhistrec = $DB->get_record('certif_completion_history', array('userid' => $this->users[1]->id, 'timecompleted' => $times['complete']));
        $this->assertEquals($certhistrec->timecompleted, $times['complete']);
        $this->assertEquals($certhistrec->timewindowopens, $times['window']);
        $this->assertEquals($certhistrec->timeexpires, $times['expire']);
        $this->assertEquals($certhistrec->unassigned, 0);

        // Check the course completions are still there and still marked as complete.
        $comprecs = $DB->get_records('course_completions', array('userid' => $this->users[1]->id, 'timecompleted' => $times['complete']));
        $this->assertEquals(count($comprecs), 0);

        $comphistrecs = $DB->get_records('course_completion_history', array('userid' => $this->users[1]->id, 'timecompleted' => $times['complete']));
        $this->assertEquals(count($comphistrecs), 1);

        $comphistrecs = $DB->get_records('course_completion_history', array('userid' => $this->users[1]->id, 'timecompleted' => $times['oldcomplete']));
        $this->assertEquals(count($comphistrecs), 2);
    }

    /**
     * Test restoration of recertified completion
     * when a user was unassigned pre-window and restored after they assignment has expired.
     */
    public function test_restoration_recertified_expired() {
        global $DB, $CFG;
        $this->resetAfterTest(true);

        // Set the restoration setting.
        $CFG->restorecertifenrolments = 1;

        $progid = $this->program->id;
        $times = $this->setup_recertified_state(time() - (7 * $this->monthsecs)); // Setup with recertification 7 months ago.

        // Unassign.
        $this->getDataGenerator()->assign_program($progid, array($this->users[2]->id));

        // Run the certification task.
        $this->task->execute();

        // Check that the unassignment has created the expected history record.
        $certhistrecs = $DB->get_records('certif_completion_history', array('userid' => $this->users[1]->id));
        $this->assertEquals(count($certhistrecs), 2);

        $certhistrecnew = null;
        $certhistrecold = null;
        // Figure out which is the old history record and which is the new.
        foreach ($certhistrecs as $certhistrec) {
            if (empty($certhistrecnew) || $certhistrec->timecompleted > $certhistrecnew->timecompleted) {
                $certhistrecold = $certhistrecnew;
                $certhistrecnew = $certhistrec;
            } else {
                $certhistrecold = $certhistrec;
            }
        }

        // Then check them both.
        $this->assertEquals($certhistrecnew->timecompleted, $times['complete']);
        $this->assertEquals($certhistrecnew->timewindowopens, $times['window']);
        $this->assertEquals($certhistrecnew->timeexpires, $times['expire']);
        $this->assertEquals($certhistrecnew->unassigned, 1);

        $this->assertEquals($certhistrecold->timecompleted, $times['oldcomplete']);
        $this->assertEquals($certhistrecold->timewindowopens, $times['oldwindow']);
        $this->assertEquals($certhistrecold->timeexpires, $times['oldexpire']);
        $this->assertEquals($certhistrecold->unassigned, 0);

        $certcomprec = $DB->record_exists('certif_completion', array('userid' => $this->users[1]->id));
        $this->assertEquals($certcomprec, false);

         // Reassign.
        $this->getDataGenerator()->assign_program($progid, array($this->users[1]->id, $this->users[2]->id));

        // Run the certification task.
        $this->task->execute();

        // Check the restored certification completion record.
        $certcomprec = $DB->get_record('certif_completion', array('userid' => $this->users[1]->id));
        $this->assertEquals($certcomprec->timecompleted, 0); // Zero'd out on expiry.
        $this->assertEquals($certcomprec->timewindowopens, 0); // Zero'd out on expiry.
        $this->assertEquals($certcomprec->timeexpires, 0); // Zero'd out on expiry.
        $this->assertEquals($certcomprec->certifpath, CERTIFPATH_CERT); // Check they have been put back on the CERT path.
        $this->assertEquals($certcomprec->status, CERTIFSTATUS_EXPIRED); // Check they are still certified.
        $this->assertEquals($certcomprec->renewalstatus, CERTIFRENEWALSTATUS_EXPIRED); // Check the window has opened.

        // There should still be 2 history records.
        $certhistcount = $DB->count_records('certif_completion_history', array('userid' => $this->users[1]->id));
        $this->assertEquals($certhistcount, 2);

        // Check the history record is still there but no longer marked as unassigned.
        $certhistrec1 = $DB->get_record('certif_completion_history', array('userid' => $this->users[1]->id, 'timecompleted' => $times['complete']));
        $this->assertEquals($certhistrec1->timecompleted, $times['complete']);
        $this->assertEquals($certhistrec1->timewindowopens, $times['window']);
        $this->assertEquals($certhistrec1->timeexpires, $times['expire']);
        $this->assertEquals($certhistrec1->unassigned, 0);

        // Check the history record is still there but no longer marked as unassigned.
        $certhistrec2 = $DB->get_record('certif_completion_history', array('userid' => $this->users[1]->id, 'timecompleted' => $times['oldcomplete']));
        $this->assertEquals($certhistrec2->timecompleted, $times['oldcomplete']);
        $this->assertEquals($certhistrec2->timewindowopens, $times['oldwindow']);
        $this->assertEquals($certhistrec2->timeexpires, $times['oldexpire']);
        $this->assertEquals($certhistrec2->unassigned, 0);

        // Check the course completions are still there and still marked as complete.
        $comprecs = $DB->get_records('course_completions', array('userid' => $this->users[1]->id, 'timecompleted' => $times['complete']));
        $this->assertEquals(count($comprecs), 0);

        $comphistrecs = $DB->get_records('course_completion_history', array('userid' => $this->users[1]->id, 'timecompleted' => $times['complete']));
        $this->assertEquals(count($comphistrecs), 1);

        $comphistrecs = $DB->get_records('course_completion_history', array('userid' => $this->users[1]->id, 'timecompleted' => $times['oldcomplete']));
        $this->assertEquals(count($comphistrecs), 2);
    }

    /**
     * Test restoration of an expired recertification completion
     * when a user was unassigned after expiry and then reassigned.
     */
    public function test_restoration_expired_expired() {
        global $DB, $CFG;
        $this->resetAfterTest(true);

        // Set the restoration setting.
        $CFG->restorecertifenrolments = 1;

        $progid = $this->program->id;

        $times = $this->setup_recertified_state(time() - (7 * $this->monthsecs)); // Setup with recertification 7 months ago.

        // Run the certification task.
        $this->task->execute();

        // Unassign.
        $this->getDataGenerator()->assign_program($progid, array($this->users[2]->id));

        // Run the certification task.
        $this->task->execute();

        // Check that the unassignment has created the expected history record.
        $certhistrecs = $DB->get_records('certif_completion_history', array('userid' => $this->users[1]->id));
        $this->assertEquals(count($certhistrecs), 3); // There are 3, one for the cert, one for the recert, and one expired one created for unassignment.

        $certhistrec1 = $DB->get_record('certif_completion_history', array('userid' => $this->users[1]->id, 'timecompleted' => 0));
        $this->assertEquals($certhistrec1->timecompleted, 0);
        $this->assertEquals($certhistrec1->timewindowopens, 0);
        $this->assertEquals($certhistrec1->timeexpires, 0);
        $this->assertEquals($certhistrec1->unassigned, 1);

        // Then check them both.
        $certhistrec2 = $DB->get_record('certif_completion_history', array('userid' => $this->users[1]->id, 'timecompleted' => $times['complete']));
        $this->assertEquals($certhistrec2->timecompleted, $times['complete']);
        $this->assertEquals($certhistrec2->timewindowopens, $times['window']);
        $this->assertEquals($certhistrec2->timeexpires, $times['expire']);
        $this->assertEquals($certhistrec2->unassigned, 0);

        $certhistrec3 = $DB->get_record('certif_completion_history', array('userid' => $this->users[1]->id, 'timecompleted' => $times['oldcomplete']));
        $this->assertEquals($certhistrec3->timecompleted, $times['oldcomplete']);
        $this->assertEquals($certhistrec3->timewindowopens, $times['oldwindow']);
        $this->assertEquals($certhistrec3->timeexpires, $times['oldexpire']);
        $this->assertEquals($certhistrec3->unassigned, 0);

        $certcomprec = $DB->record_exists('certif_completion', array('userid' => $this->users[1]->id));
        $this->assertEquals($certcomprec, false);

         // Reassign.
        $this->getDataGenerator()->assign_program($progid, array($this->users[1]->id, $this->users[2]->id));

        // Run the certification task.
        $this->task->execute();

        // Check the restored certification completion record.
        $certcomprec = $DB->get_record('certif_completion', array('userid' => $this->users[1]->id));
        $this->assertEquals($certcomprec->timecompleted, 0); // Zero'd out on expiry.
        $this->assertEquals($certcomprec->timewindowopens, 0); // Zero'd out on expiry.
        $this->assertEquals($certcomprec->timeexpires, 0); // Zero'd out on expiry.
        $this->assertEquals($certcomprec->certifpath, CERTIFPATH_CERT); // Check they have been put back on the CERT path.
        $this->assertEquals($certcomprec->status, CERTIFSTATUS_EXPIRED); // Check they are expired.
        $this->assertEquals($certcomprec->renewalstatus, CERTIFRENEWALSTATUS_EXPIRED); // Check they are expired.

        // Check the history record was deleted.
        $certhistrec = $DB->get_record('certif_completion_history', array('userid' => $this->users[1]->id, 'timecompleted' => 0));
        $this->assertEquals($certhistrec, false);

        // Check the course completions are still there and still marked as complete.
        $comprecs = $DB->get_records('course_completions', array('userid' => $this->users[1]->id, 'timecompleted' => $times['complete']));
        $this->assertEquals(count($comprecs), 0);

        $comphistrecs = $DB->get_records('course_completion_history', array('userid' => $this->users[1]->id, 'timecompleted' => $times['complete']));
        $this->assertEquals(count($comphistrecs), 1);

        $comphistrecs = $DB->get_records('course_completion_history', array('userid' => $this->users[1]->id, 'timecompleted' => $times['oldcomplete']));
        $this->assertEquals(count($comphistrecs), 2);

        // Quick test that a new history record is created if we remove the user again.
        $this->getDataGenerator()->assign_program($progid, array($this->users[2]->id));
        $this->task->execute();
        $postrecord = $DB->get_record('certif_completion_history', array('timecompleted' => 0, 'userid' => $this->users[1]->id));
        $this->assertEquals($postrecord->unassigned, 1);
    }

    /**
     * Test that restoration of certification completion with an old expiry date does not lead to a time allowance exception
     */
    public function test_restoration_exceptions_timeallowance() {
        global $DB, $CFG;
        $this->resetAfterTest(true);

        // Set the restoration setting.
        $CFG->restorecertifenrolments = 1;

        $now = time();
        $progid = $this->program->id;
        $times = $this->setup_certified_state($now - (7 * $this->monthsecs)); // Setup with completion 7 months ago.

        // Unassign.
        $this->getDataGenerator()->assign_program($progid, array($this->users[2]->id));

        // Run the certification task.
        $this->task->execute();

        // Check that the unassignment has created the expected history record.
        $certhistrec = $DB->get_record('certif_completion_history', array('userid' => $this->users[1]->id));
        $this->assertEquals($certhistrec->timecompleted, $times['complete']);
        $this->assertEquals($certhistrec->timewindowopens, $times['window']);
        $this->assertEquals($certhistrec->timeexpires, $times['expire']);
        $this->assertEquals($certhistrec->unassigned, 1);

        $certcomprec = $DB->record_exists('certif_completion', array('userid' => $this->users[1]->id));
        $this->assertEquals($certcomprec, false);

        // Reassign.
        $this->getDataGenerator()->assign_program($progid, array($this->users[1]->id, $this->users[2]->id));

        // Check that user 1 does not have a time allowance exception.
        $userassignment = $DB->get_record('prog_user_assignment', array('userid' => $this->users[1]->id));
        $this->assertEquals($userassignment->exceptionstatus, 0);

        $exceptions = $DB->get_records('prog_exception');
        $this->assertEmpty($exceptions);

        // Check the timedue = now-1month.
        $compl = $DB->get_record('prog_completion', array('userid' => $this->users[1]->id, 'programid' => $progid, 'coursesetid' => 0));
        $this->assertEquals($compl->timedue, $now - $this->monthsecs);
    }

    /**
     * Test that restoration of certification completion, after assignment to a certification with matching courses,
     * does not lead to a duplicate courses exception
     */
    public function test_restoration_exceptions_dupcourse() {
        global $DB, $CFG;
        $this->resetAfterTest(true);

        // Set the restoration setting.
        $CFG->restorecertifenrolments = 1;

        $now = time();
        $progid = $this->program->id;
        $times = $this->setup_certified_state($now - (3 * $this->monthsecs)); // Setup with completion 3 months ago.

        // Duplicate the certification.
        $certdata = array(
            'cert_activeperiod' => '6 Months',
            'cert_windowperiod' => '2 Months',
        );
        $dupcert = $this->getDataGenerator()->create_certification($certdata);

        $this->getDataGenerator()->add_courseset_program($dupcert->id, array($this->courses[1]->id, $this->courses[2]->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($dupcert->id, array($this->courses[3]->id), CERTIFPATH_RECERT);


        // Unassign.
        $this->getDataGenerator()->assign_program($progid, array($this->users[2]->id));

        // Assign user 1 to the dupcert.
        $this->getDataGenerator()->assign_program($dupcert->id, array($this->users[1]->id));

        // Reassign.
        $this->getDataGenerator()->assign_program($progid, array($this->users[1]->id, $this->users[2]->id));

        // Check that user 1 has a duplicate course exception.
        $userassignment = $DB->get_record('prog_user_assignment', array('userid' => $this->users[1]->id, 'programid' => $progid));
        $this->assertEquals($userassignment->exceptionstatus, 1);

        $exceptions = $DB->get_records('prog_exception');
        $this->assertEquals(count($exceptions), 1);

        $exception = array_shift($exceptions);
        $this->assertEquals($exception->exceptiontype, EXCEPTIONTYPE_DUPLICATE_COURSE);
        $this->assertEquals($exception->programid, $progid);
        $this->assertEquals($exception->userid, $this->users[1]->id);
    }
}
