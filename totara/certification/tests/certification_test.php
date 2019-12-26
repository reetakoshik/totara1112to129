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
 * @author Nathan Lewis <nathan.lewis@totaralms.com>
 * @package totara_certification
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot . '/totara/certification/lib.php');

define('CERTIFICATION_USERS', 10); // Assigned to each certification which is tested.
define('CERTIFICATION_PART_2_USERS', 8);
define('CERTIFICATION_PART_4_USERS', 6);

/**
 * Certification module PHPUnit archive test class.
 *
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose totara_certification_certification_testcase totara/certification/tests/certification_test.php
 */
class totara_certification_certification_testcase extends reportcache_advanced_testcase {

    private $setuptimeminimum, $setuptimemaximum, $certprograms, $userswithassignmentduedate, $users, $assignmentduedate,
        $certprogram1, $certprogram2, $certprogram3, $certprogram4, $certprogram5, $certprogram6,
        $courses, $coursesforcompletion, $certsforcompletion;

    private $firstcompletiontimeminimum, $firstcompletiontimemaximum, $firstcompletiontime;

    private $secondcompletiontime, $secondcompletiontimeminimum, $secondcompletiontimemaximum;

    private $secondcrontime, $secondcrontimeminimum, $secondcrontimemaximum;

    private $thirdcompletiontime, $thirdcompletiontimeminimum, $thirdcompletiontimemaximum;

    protected function tearDown() {
        $this->setuptimeminimum = null;
        $this->setuptimemaximum = null;
        $this->certprograms = null;
        $this->userswithassignmentduedate = null;
        $this->users = null;
        $this->assignmentduedate = null;
        $this->certprogram1 = null;
        $this->certprogram2 = null;
        $this->certprogram3 = null;
        $this->certprogram4 = null;
        $this->certprogram5 = null;
        $this->certprogram6 = null;
        $this->courses = null;
        $this->coursesforcompletion = null;
        $this->certsforcompletion = null;
        $this->firstcompletiontimeminimum = null;
        $this->firstcompletiontimemaximum = null;
        $this->firstcompletiontime = null;
        $this->secondcompletiontime = null;
        $this->secondcompletiontimeminimum = null;
        $this->secondcompletiontimemaximum = null;
        $this->secondcrontime = null;
        $this->secondcrontimeminimum = null;
        $this->secondcrontimemaximum = null;
        $this->thirdcompletiontime = null;
        $this->thirdcompletiontimeminimum = null;
        $this->thirdcompletiontimemaximum = null;

        parent::tearDown();
    }

    /**
     * The expiry date is calculated depending on the recertifydatetype property as follows:
     * CERTIFRECERT_EXPIRY
     *      To calculate the next expiry date based on previous expiry date, add the period to the base, where base is the
     *      first available / non-zero value of
     *      1) previous expiry date
     *      2) assignment due date (completiontime in prog_assignment)
     *      3) time completed
     *      except when the certification is overdue (primary certification is past the due date or recertification has expired),
     *      in which case the base is just time completed.
     * CERTIFRECERT_COMPLETION
     *      Just add the period to the time completed.
     * CERTIFRECERT_FIXED
     *      Not tested here, because get_certiftimebase uses time(), and this test involves strange time bending operations.
     *      The core functionality of this option is tested in recertdates_test.
     */
    public function test_certification_recertification() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Turn off programs. This is to test that it doesn't interfere with certification completion.
        set_config('enableprograms', TOTARA_DISABLEFEATURE);

        $this->actions_stage_1(); // Initial setup.
        $this->check_stage_1();
        $this->actions_stage_2(); // First completion / primary certification.
        $this->check_stage_2();
        $this->actions_stage_3(); // Recertification window opens.
        $this->check_stage_3();
        $this->actions_stage_4(); // Second completion / recertification.
        $this->check_stage_4();
        $this->actions_stage_5(); // Recertification window opens again.
        $this->check_stage_5();
        $this->actions_stage_6(); // Third completion / recertification.
        $this->check_stage_6();
    }

    /**
     * Testing part 1 - Initial setup.
     *
     * Create some users.
     * Create some certifications.
     * Add the users to the certifications.
     * Create some courses and add them to the certifications.
     */
    private function actions_stage_1() {
        global $DB;

        $setuptime = time();
        $this->setuptimeminimum = $setuptime;
        // By waiting a little bit, we ensure that our time asserts are correct, and not just happening
        // within a single second, which could cause intermittent testing failures.
        $this->waitForSecond();
        $startoftoday = totara_date_parse_from_format('d/m/Y', date('d/m/Y', $setuptime));

        // Create users.
        $this->assertEquals(2, $DB->count_records('user')); // Guest + Admin.
        $this->users = array();
        for ($i = 1; $i <= CERTIFICATION_USERS; $i++) {
            $this->users[$i] = $this->getDataGenerator()->create_user();
        }
        $this->assertEquals(CERTIFICATION_USERS + 2, $DB->count_records('user'),
            'Record count mismatch for users'); // Guest + Admin + generated users.

        // Create four certifications. We will experiment with 1 and 2 and leave the other two alone.
        // Certs 1 and 3 use CERTIFRECERT_EXPIRY, 2 and 4 use CERTIFRECERT_COMPLETION.
        $this->assertEquals(0, $DB->count_records('prog'), "Programs table isn't empty");
        $this->assertEquals(0, $DB->count_records('certif'), "Certif table isn't empty");
        $cert1data = array(
            'cert_learningcomptype' => CERTIFTYPE_PROGRAM,
            'cert_activeperiod' => '365 day',
            'cert_windowperiod' => '90 day',
            'cert_recertifydatetype' => CERTIFRECERT_EXPIRY,
        );
        $this->certprogram1 = $this->getDataGenerator()->create_certification($cert1data);
        $cert2data = array(
            'cert_learningcomptype' => CERTIFTYPE_PROGRAM,
            'cert_activeperiod' => '365 day',
            'cert_windowperiod' => '90 day',
            'cert_recertifydatetype' => CERTIFRECERT_COMPLETION,
        );
        $this->certprogram2 = $this->getDataGenerator()->create_certification($cert2data);
        $cert3data = array(
            'cert_learningcomptype' => CERTIFTYPE_PROGRAM,
            'cert_activeperiod' => '365 day',
            'cert_windowperiod' => '90 day',
            'cert_minimumactiveperiod' => '90 day',
            'cert_recertifydatetype' => CERTIFRECERT_FIXED,
        );
        $this->certprogram3 = $this->getDataGenerator()->create_certification($cert3data);
        $cert4data = array(
            'cert_learningcomptype' => CERTIFTYPE_PROGRAM,
            'cert_activeperiod' => '365 day',
            'cert_windowperiod' => '90 day',
            'cert_recertifydatetype' => CERTIFRECERT_EXPIRY,
        );
        $this->certprogram4 = $this->getDataGenerator()->create_certification($cert4data);
        $cert5data = array(
            'cert_learningcomptype' => CERTIFTYPE_PROGRAM,
            'cert_activeperiod' => '365 day',
            'cert_windowperiod' => '90 day',
            'cert_recertifydatetype' => CERTIFRECERT_COMPLETION,
        );
        $this->certprogram5 = $this->getDataGenerator()->create_certification($cert5data);
        $cert6data = array(
            'cert_learningcomptype' => CERTIFTYPE_PROGRAM,
            'cert_activeperiod' => '365 day',
            'cert_windowperiod' => '90 day',
            'cert_minimumactiveperiod' => '180 day',
            'cert_recertifydatetype' => CERTIFRECERT_FIXED,
        );
        $this->certprogram6 = $this->getDataGenerator()->create_certification($cert6data);
        $this->certprograms = array($this->certprogram1, $this->certprogram2, $this->certprogram3,
                                    $this->certprogram4, $this->certprogram5, $this->certprogram6);
        $this->certsforcompletion = array($this->certprogram1, $this->certprogram2, $this->certprogram3);
        $this->assertEquals(6, $DB->count_records('prog'), 'Record count mismatch in program table');
        $this->assertEquals(6, $DB->count_records('certif'), 'Record count mismatch for certif');

        // Assign users to the certification as individuals.
        // Assignment due date is at the start of the day, 14 days from $now.
        $this->assignmentduedate = strtotime("14 day", $startoftoday);
        $this->userswithassignmentduedate = array();
        foreach ($this->certprograms as $certprogram) {
            for ($i = 1; $i <= CERTIFICATION_USERS; $i++) {
                if ($i % 2) { // Half of the users (with odd $i) have an assignment due date.
                    $record = array('completiontime' => date('d/m/Y', $this->assignmentduedate),
                        'completionevent' => COMPLETION_EVENT_NONE);
                    $this->getDataGenerator()->assign_to_program($certprogram->id,
                        ASSIGNTYPE_INDIVIDUAL, $this->users[$i]->id, $record);
                    $this->userswithassignmentduedate[] = $this->users[$i];
                } else {
                    $this->getDataGenerator()->assign_to_program($certprogram->id,
                        ASSIGNTYPE_INDIVIDUAL, $this->users[$i]->id);
                }
            }
        }

        // Create some courses and add them to the certification and recertification paths.
        $this->courses = array();
        foreach ($this->certprograms as $certprogram) {
            $course = $this->getDataGenerator()->create_course();
            $this->courses[$certprogram->id] = $course;
            $this->getDataGenerator()->add_courseset_program($certprogram->id, array($course->id), CERTIFPATH_CERT);
            $this->getDataGenerator()->add_courseset_program($certprogram->id, array($course->id), CERTIFPATH_RECERT);
        }

        // Figure out which courses we want to do the completion actions on, in stages 2, 4 and 6.
        $this->coursesforcompletion = array();
        foreach ($this->certsforcompletion as $certprogram) {
            $this->coursesforcompletion[] = $this->courses[$certprogram->id];
        }

        $this->waitForSecond();
        $this->setuptimemaximum = time();
    }

    /**
     * Testing part 2 - First completion / primary certification.
     *
     * Mark some users as completion in some courses.
     */
    private function actions_stage_2() {
        // Complete some users in the first three certifications.
        $this->firstcompletiontime = time();
        $this->firstcompletiontimeminimum = $this->firstcompletiontime;
        $this->waitForSecond();
        foreach ($this->coursesforcompletion as $course) {
            for ($i = 1; $i <= CERTIFICATION_PART_2_USERS; $i++) {
                $completion = new completion_completion(array('userid' => $this->users[$i]->id, 'course' => $course->id));
                $completion->mark_inprogress($this->firstcompletiontime);
                $completion->mark_complete($this->firstcompletiontime);
            }
        }
        $this->waitForSecond();
        $this->firstcompletiontimemaximum = time();
    }

    /**
     * Testing part 3 - Recertification window opens.
     *
     * To do this, we're going to move everything back in time. How far? We want to choose a time where the
     * recertification window is open for all users who completed the initial certification stage. Those with
     * CERTIFRECERT_COMPLETION will be open from 9 months to 12 months from $firstcompletiontime (a few seconds
     * ago). Those with CERTIFRECERT_EXPIRY and no assignment due date will be the same as above, otherwise they
     * will be open from 9 months to 12 months from $completiontime (= 9m, 2w from now). So they should all be
     * open 11 months (365 - 30 days) from today.
     * Run cron to open the recertification windows.
     */
    private function actions_stage_3() {
        global $DB;

        // Move everything back in time.
        $records = $DB->get_records('certif_completion');
        foreach ($records as $record) {
            if ($record->timewindowopens > 0) {
                $record->timewindowopens = strtotime("-335 day", $record->timewindowopens);
            }
            if ($record->timeexpires > 0) {
                $record->timeexpires = strtotime("-335 day", $record->timeexpires);
                $record->baselinetimeexpires = $record->timeexpires;
            }
            if ($record->timecompleted > 0) {
                $record->timecompleted = strtotime("-335 day", $record->timecompleted);
            }
            if ($record->timemodified > 0) {
                $record->timemodified = strtotime("-335 day", $record->timemodified);
            }
            $DB->update_record('certif_completion', $record);
        }

        $records = $DB->get_records('prog_assignment');
        foreach ($records as $record) {
            if ($record->completiontime > 0) {
                $record->completiontime = strtotime("-335 day", $record->completiontime);
            }
            $DB->update_record('prog_assignment', $record);
        }

        $records = $DB->get_records('prog_completion');
        foreach ($records as $record) {
            if ($record->timecreated > 0) {
                $record->timecreated = strtotime("-335 day", $record->timecreated);
            }
            if ($record->timestarted > 0) {
                $record->timestarted = strtotime("-335 day", $record->timestarted);
            }
            if ($record->timedue > 0) {
                $record->timedue = strtotime("-335 day", $record->timedue);
            }
            if ($record->timecompleted > 0) {
                $record->timecompleted = strtotime("-335 day", $record->timecompleted);
            }
            $DB->update_record('prog_completion', $record);
        }

        $records = $DB->get_records('prog_user_assignment');
        foreach ($records as $record) {
            if ($record->timeassigned > 0) {
                $record->timeassigned = strtotime("-335 day", $record->timeassigned);
            }
            $DB->update_record('prog_user_assignment', $record);
        }

        $this->assignmentduedate = strtotime("-335 day", $this->assignmentduedate);
        $this->setuptimeminimum = strtotime("-335 day", $this->setuptimeminimum);
        $this->setuptimemaximum = strtotime("-335 day", $this->setuptimemaximum);
        $this->firstcompletiontime = strtotime("-335 day", $this->firstcompletiontime);
        $this->firstcompletiontimeminimum = strtotime("-335 day", $this->firstcompletiontimeminimum);
        $this->firstcompletiontimemaximum = strtotime("-335 day", $this->firstcompletiontimemaximum);

        // Run cron.
        ob_start();
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();
        $assignmentscron = new \totara_program\task\user_assignments_task();
        $assignmentscron->execute();
        ob_end_clean();
    }

    /**
     * Testing part 4 - Second completion / recertification.
     *
     * Mark some users as complete in some courses.
     */
    private function actions_stage_4() {
        global $DB;

        // Complete some users in the first two certifications.
        $this->secondcompletiontime = time();
        $this->secondcompletiontimeminimum = $this->secondcompletiontime;
        $this->waitForSecond();
        foreach ($this->coursesforcompletion as $course) {
            for ($i = 1; $i <= CERTIFICATION_PART_4_USERS; $i++) {
                $completion = new completion_completion(array('userid' => $this->users[$i]->id, 'course' => $course->id));
                $completion->mark_inprogress($this->secondcompletiontime);
                $completion->mark_complete($this->secondcompletiontime);
            }
        }
        $this->waitForSecond();
        $this->secondcompletiontimemaximum = time();
    }

    /**
     * Testing part 5 - Recertification window opens again.
     *
     * To do this, we're going to move everything back in time again. How far? We want to choose a time where the
     * recertification window is open for all users who completed the recertification. 11 months from the previous
     * recertification is ideal for all users, as it is 1 year and 10 months from the initial certification date.
     * For CERTIFRECERT_COMPLETION it is 11 months from the previous completion. For CERTIFRECERT_EXPIRY it is
     * 1 year and 10 months from the first completion. Both of these are inside the three month recertification window.
     * Run cron to open recertification windows. This also clears certification for those who didn't complete
     * recertification - these users will be back on the primary certification path.
     */
    private function actions_stage_5() {
        global $DB;

        // Move everything back in time.
        $records = $DB->get_records('certif_completion');
        foreach ($records as $record) {
            if ($record->timewindowopens > 0) {
                $record->timewindowopens = strtotime("-335 day", $record->timewindowopens);
            }
            if ($record->timeexpires > 0) {
                $record->timeexpires = strtotime("-335 day", $record->timeexpires);
                $record->baselinetimeexpires = $record->timeexpires;
            }
            if ($record->timecompleted > 0) {
                $record->timecompleted = strtotime("-335 day", $record->timecompleted);
            }
            if ($record->timemodified > 0) {
                $record->timemodified = strtotime("-335 day", $record->timemodified);
            }
            $DB->update_record('certif_completion', $record);
        }

        $records = $DB->get_records('prog_assignment');
        foreach ($records as $record) {
            if ($record->completiontime > 0) {
                $record->completiontime = strtotime("-335 day", $record->completiontime);
            }
            $DB->update_record('prog_assignment', $record);
        }

        $records = $DB->get_records('prog_completion');
        foreach ($records as $record) {
            if ($record->timecreated > 0) {
                $record->timecreated = strtotime("-335 day", $record->timecreated);
            }
            if ($record->timestarted > 0) {
                $record->timestarted = strtotime("-335 day", $record->timestarted);
            }
            if ($record->timedue > 0) {
                $record->timedue = strtotime("-335 day", $record->timedue);
            }
            if ($record->timecompleted > 0) {
                $record->timecompleted = strtotime("-335 day", $record->timecompleted);
            }
            $DB->update_record('prog_completion', $record);
        }

        $records = $DB->get_records('prog_user_assignment');
        foreach ($records as $record) {
            if ($record->timeassigned > 0) {
                $record->timeassigned = strtotime("-335 day", $record->timeassigned);
            }
            $DB->update_record('prog_user_assignment', $record);
        }

        $this->assignmentduedate = strtotime("-335 day", $this->assignmentduedate);
        $this->setuptimeminimum = strtotime("-335 day", $this->setuptimeminimum);
        $this->setuptimemaximum = strtotime("-335 day", $this->setuptimemaximum);
        $this->firstcompletiontime = strtotime("-335 day", $this->firstcompletiontime);
        $this->firstcompletiontimeminimum = strtotime("-335 day", $this->firstcompletiontimeminimum);
        $this->firstcompletiontimemaximum = strtotime("-335 day", $this->firstcompletiontimemaximum);
        $this->secondcompletiontime = strtotime("-335 day", $this->secondcompletiontime);
        $this->secondcompletiontimeminimum = strtotime("-335 day", $this->secondcompletiontimeminimum);
        $this->secondcompletiontimemaximum = strtotime("-335 day", $this->secondcompletiontimemaximum);

        // Run cron.
        $this->secondcrontime = time();
        $this->secondcrontimeminimum = $this->secondcrontime;
        $this->waitForSecond();
        ob_start();
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();
        $assignmentscron = new \totara_program\task\user_assignments_task();
        $assignmentscron->execute();
        ob_end_clean();
        $this->waitForSecond();
        $this->secondcrontimemaximum = time();
    }

    private function actions_stage_6() {

        // Testing part 6 - Third completion / recertification.
        // Mark all users as completion in some courses. It should not matter if they are recertifying for the second time,
        // primary certifying for the first time, or certified, failed to recertify and now did primary certification again.

        // Complete all users in the first two certifications.
        $this->thirdcompletiontime = time();
        $this->thirdcompletiontimeminimum = $this->thirdcompletiontime;
        $this->waitForSecond();
        foreach ($this->coursesforcompletion as $course) {
            for ($i = 1; $i <= CERTIFICATION_USERS; $i++) {
                $completion = new completion_completion(array('userid' => $this->users[$i]->id, 'course' => $course->id));
                $completion->mark_inprogress($this->thirdcompletiontime);
                $completion->mark_complete($this->thirdcompletiontime);
            }
        }
        $this->waitForSecond();
        $this->thirdcompletiontimemaximum = time();

    }

    public function check_stage_1() {
        global $DB;

        // Check the status of all users.
        foreach ($this->certprograms as $certprogram) {
            for ($i = 1; $i <= CERTIFICATION_USERS; $i++) {
                $user = $this->users[$i];
                $hasassignmentduedate = in_array($user, $this->userswithassignmentduedate);

                // Program assignment and program user assignment records should exist for all users.
                // We get all prog_user_assignment records and their matching prog_assignment records.
                $sql = "SELECT pa.*, pua.programid, pua.userid, pua.timeassigned, exceptionstatus
                          FROM {prog_assignment} pa
                          JOIN {prog_user_assignment} pua ON pua.assignmentid = pa.id
                         WHERE pua.programid = :programid AND pua.userid = :userid";
                $progassignments = $DB->get_records_sql($sql,
                    array('programid' => $certprogram->id,
                          'userid' => $user->id));
                $this->assertCount(1, $progassignments); // Just one.
                $progassignment = reset($progassignments);
                $this->assertEquals(ASSIGNTYPE_INDIVIDUAL, $progassignment->assignmenttype);
                $this->assertEquals($user->id, $progassignment->assignmenttypeid);
                $this->assertEquals(0, $progassignment->includechildren);
                if ($hasassignmentduedate) {
                    // Has assignment due date.
                    $this->assertEquals(userdate($this->assignmentduedate), userdate($progassignment->completiontime));
                } else {
                    $this->assertEquals(-1, $progassignment->completiontime); // No assignment due date.
                }
                $this->assertEquals(0, $progassignment->completionevent); // No completion event.
                $this->assertEquals(0, $progassignment->completioninstance); // No completion instance.
                // Time assigned.
                $this->assertGreaterThan($this->setuptimeminimum, $progassignment->timeassigned);
                $this->assertLessThan($this->setuptimemaximum, $progassignment->timeassigned);
                $this->assertEquals(PROGRAM_EXCEPTION_NONE, $progassignment->exceptionstatus); // No exceptions.

                // Program completion records should exist for all users.
                $progcompletions = $DB->get_records('prog_completion',
                    array('programid' => $certprogram->id,
                        'userid' => $user->id,
                        'coursesetid' => 0));
                $this->assertCount(1, $progcompletions); // Just one.
                $progcompletion = reset($progcompletions);
                $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status); // Status is incomplete.
                // When it was started.
                $this->assertEquals(0, $progcompletion->timestarted); // Because of how this is set up if it is incomplete it isn't started.
                $this->assertGreaterThan($this->setuptimeminimum, $progcompletion->timecreated);
                $this->assertLessThan($this->setuptimemaximum, $progcompletion->timecreated);
                if ($hasassignmentduedate) {
                    $this->assertEquals(userdate($this->assignmentduedate), userdate($progcompletion->timedue)); // Has timedue.
                } else {
                    $this->assertEquals(-1, $progcompletion->timedue); // No timedue.
                }

                // Certification completion records should exist for all users.
                $certifcompletions = $DB->get_records('certif_completion',
                    array('certifid' => $certprogram->certifid,
                          'userid' => $user->id));
                $this->assertCount(1, $certifcompletions); // Just one.
                $certifcompletion = reset($certifcompletions);
                $this->assertEquals(CERTIFPATH_CERT, $certifcompletion->certifpath); // Primary certification path.
                $this->assertEquals(CERTIFSTATUS_ASSIGNED, $certifcompletion->status); // Status assigned.
                $this->assertEquals(CERTIFRENEWALSTATUS_NOTDUE, $certifcompletion->renewalstatus); // Not due for renewal.
                $this->assertEquals(0, $certifcompletion->timeexpires); // No expiry.
                $this->assertEquals(0, $certifcompletion->baselinetimeexpires); // No expiry.
                $this->assertEquals(0, $certifcompletion->timewindowopens); // No window.
                $this->assertEquals(0, $certifcompletion->timecompleted); // Not completed.
                // When record was created.
                $this->assertGreaterThan($this->setuptimeminimum, $certifcompletion->timemodified);
                $this->assertLessThan($this->setuptimemaximum, $certifcompletion->timemodified);
            }
        }
    }

    public function check_stage_2() {
        global $DB;

        // Check the status of all users.
        foreach ($this->certprograms as $certprogram) {
            for ($i = 1; $i <= CERTIFICATION_USERS; $i++) {
                $user = $this->users[$i];
                $hasassignmentduedate = in_array($user, $this->userswithassignmentduedate);
                $didfirstcompletion = in_array($certprogram, $this->certsforcompletion) && $i <= CERTIFICATION_PART_2_USERS;
                if ($didfirstcompletion) {
                    if ($certprogram == $this->certprogram1 || $certprogram == $this->certprogram3) {
                        /*
                         * CERTIFRECERT_EXPIRY and CERTIFRECERT_FIXED.
                         * At this point, no users have previous expiry date.
                         */
                        if ($hasassignmentduedate) {
                            // These users have assignment due date (completiontime) in prog_assignment.
                            $basetime = $this->assignmentduedate;
                        } else {
                            // These users have no assignment due date (completiontime) in prog_assignment,
                            // so need to use time completed.
                            $basetime = $this->firstcompletiontime;
                        }
                    } else if ($certprogram == $this->certprogram2) {
                        // CERTIFRECERT_COMPLETION.
                        $basetime = $this->firstcompletiontime;
                    }
                    $timeexpires = get_timeexpires($basetime, '365 day');
                    $timewindowopens = get_timewindowopens($timeexpires, '90 day');
                } else {
                    // Not applicable if they haven't certified.
                    $timeexpires = 'invalid';
                    $timewindowopens = 'invalid';
                }

                // Program assignment and program user assignment records should exist for all users.
                // We get all prog_user_assignment records and their matching prog_assignment records.
                $sql = "SELECT pa.*, pua.programid, pua.userid, pua.timeassigned, exceptionstatus
                          FROM {prog_assignment} pa
                          JOIN {prog_user_assignment} pua ON pua.assignmentid = pa.id
                         WHERE pua.programid = :programid AND pua.userid = :userid";
                $progassignments = $DB->get_records_sql($sql,
                    array('programid' => $certprogram->id,
                        'userid' => $user->id));
                $this->assertCount(1, $progassignments); // Just one.
                $progassignment = reset($progassignments);
                $this->assertEquals(ASSIGNTYPE_INDIVIDUAL, $progassignment->assignmenttype);
                $this->assertEquals($user->id, $progassignment->assignmenttypeid);
                $this->assertEquals(0, $progassignment->includechildren);
                if ($hasassignmentduedate) {
                    // Has assignment due date.
                    $this->assertEquals(userdate($this->assignmentduedate), userdate($progassignment->completiontime));
                } else {
                    $this->assertEquals(-1, $progassignment->completiontime); // No assignment due date.
                }
                $this->assertEquals(0, $progassignment->completionevent); // No completion event.
                $this->assertEquals(0, $progassignment->completioninstance); // No completion instance.
                // Time assigned.
                $this->assertGreaterThan($this->setuptimeminimum, $progassignment->timeassigned);
                $this->assertLessThan($this->setuptimemaximum, $progassignment->timeassigned);
                $this->assertEquals(PROGRAM_EXCEPTION_NONE, $progassignment->exceptionstatus); // No exceptions.

                // Program completion records should exist for all users.
                $progcompletions = $DB->get_records('prog_completion',
                    array('programid' => $certprogram->id,
                        'userid' => $user->id,
                        'coursesetid' => 0));
                $this->assertCount(1, $progcompletions); // Just one.
                $progcompletion = reset($progcompletions);
                if ($didfirstcompletion) {
                    $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status); // Status complete!
                    $this->assertGreaterThanOrEqual($this->firstcompletiontimeminimum, $progcompletion->timestarted);
                    $this->assertLessThanOrEqual($this->firstcompletiontimemaximum, $progcompletion->timestarted);
                } else {
                    $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status); // Status is incomplete.
                    $this->assertEquals(0, $progcompletion->timestarted);
                }
                // When it was started.
                $this->assertGreaterThan($this->setuptimeminimum, $progcompletion->timecreated);
                $this->assertLessThan($this->setuptimemaximum, $progcompletion->timecreated);
                if ($didfirstcompletion) {
                    $this->assertEquals(userdate($timeexpires), userdate($progcompletion->timedue)); // Set when certified.
                } else {
                    if ($hasassignmentduedate) {
                        $this->assertEquals(userdate($this->assignmentduedate), userdate($progcompletion->timedue)); // Has timedue.
                    } else {
                        $this->assertEquals(-1, $progcompletion->timedue); // No timedue.
                    }
                }

                // Certification completion records should exist for all users.
                $certifcompletions = $DB->get_records('certif_completion',
                    array('certifid' => $certprogram->certifid,
                        'userid' => $user->id));
                $this->assertCount(1, $certifcompletions); // Just one.
                $certifcompletion = reset($certifcompletions);
                $this->assertEquals(CERTIFRENEWALSTATUS_NOTDUE, $certifcompletion->renewalstatus); // Not due for renewal.
                if ($didfirstcompletion) {
                    $this->assertEquals(CERTIFPATH_RECERT, $certifcompletion->certifpath); // Recertification path.
                    $this->assertEquals(CERTIFSTATUS_COMPLETED, $certifcompletion->status); // Status completed.
                    $this->assertEquals(userdate($timeexpires), userdate($certifcompletion->timeexpires));
                    $this->assertEquals(userdate($timeexpires), userdate($certifcompletion->baselinetimeexpires));
                    $this->assertEquals(userdate($timewindowopens), userdate($certifcompletion->timewindowopens));
                    $this->assertEquals(userdate($this->firstcompletiontime), userdate($certifcompletion->timecompleted)); // Completed.
                    // When record was modified.
                    $this->assertGreaterThan($this->firstcompletiontimeminimum, $certifcompletion->timemodified);
                    $this->assertLessThan($this->firstcompletiontimemaximum, $certifcompletion->timemodified);
                } else {
                    $this->assertEquals(CERTIFPATH_CERT, $certifcompletion->certifpath); // Primary certification path.
                    $this->assertEquals(CERTIFSTATUS_ASSIGNED, $certifcompletion->status); // Status assigned.
                    $this->assertEquals(0, $certifcompletion->timeexpires); // No expiry.
                    $this->assertEquals(0, $certifcompletion->baselinetimeexpires); // No expiry.
                    $this->assertEquals(0, $certifcompletion->timewindowopens); // No window.
                    $this->assertEquals(0, $certifcompletion->timecompleted); // Not completed.
                    // When record was created.
                    $this->assertGreaterThan($this->setuptimeminimum, $certifcompletion->timemodified);
                    $this->assertLessThan($this->setuptimemaximum, $certifcompletion->timemodified);
                }
            }
        }
    }

    public function check_stage_3() {
        global $DB;

        // Check the status of all users.
        foreach ($this->certprograms as $certprogram) {
            for ($i = 1; $i <= CERTIFICATION_USERS; $i++) {
                $user = $this->users[$i];
                $hasassignmentduedate = in_array($user, $this->userswithassignmentduedate);
                $didfirstcompletion = in_array($certprogram, $this->certsforcompletion) && $i <= CERTIFICATION_PART_2_USERS;
                if ($didfirstcompletion) {
                    if ($certprogram == $this->certprogram1 || $certprogram == $this->certprogram3) {
                        /*
                         * CERTIFRECERT_EXPIRY and CERTIFRECERT_FIXED.
                         * At this point, no users have previous expiry date.
                         */
                        if ($hasassignmentduedate) {
                            // These users have assignment due date (completiontime) in prog_assignment.
                            $basetime = $this->assignmentduedate;
                        } else {
                            // These users have no assignment due date (completiontime) in prog_assignment,
                            // so need to use time completed.
                            $basetime = $this->firstcompletiontime;
                        }
                    } else if ($certprogram == $this->certprogram2) {
                        // CERTIFRECERT_COMPLETION.
                        $basetime = $this->firstcompletiontime;
                    }
                    $timeexpires = get_timeexpires($basetime, '365 day');
                    $timewindowopens = get_timewindowopens($timeexpires, '90 day');
                } else {
                    // Not applicable if they haven't certified.
                    $timeexpires = 'invalid';
                    $timewindowopens = 'invalid';
                }

                // Program assignment and program user assignment records should exist for all users.
                // We get all prog_user_assignment records and their matching prog_assignment records.
                $sql = "SELECT pa.*, pua.programid, pua.userid, pua.timeassigned, exceptionstatus
                          FROM {prog_assignment} pa
                          JOIN {prog_user_assignment} pua ON pua.assignmentid = pa.id
                         WHERE pua.programid = :programid AND pua.userid = :userid";
                $progassignments = $DB->get_records_sql($sql,
                    array('programid' => $certprogram->id,
                        'userid' => $user->id));
                $this->assertCount(1, $progassignments); // Just one.
                $progassignment = reset($progassignments);
                $this->assertEquals(ASSIGNTYPE_INDIVIDUAL, $progassignment->assignmenttype);
                $this->assertEquals($user->id, $progassignment->assignmenttypeid);
                $this->assertEquals(0, $progassignment->includechildren);
                if ($hasassignmentduedate) {
                    // Has assignment due date.
                    $this->assertEquals(userdate($this->assignmentduedate), userdate($progassignment->completiontime));
                } else {
                    $this->assertEquals(-1, $progassignment->completiontime); // No assignment due date.
                }
                $this->assertEquals(0, $progassignment->completionevent); // No completion event.
                $this->assertEquals(0, $progassignment->completioninstance); // No completion instance.
                // Time assigned.
                $this->assertGreaterThan($this->setuptimeminimum, $progassignment->timeassigned);
                $this->assertLessThan($this->setuptimemaximum, $progassignment->timeassigned);
                $this->assertEquals(PROGRAM_EXCEPTION_NONE, $progassignment->exceptionstatus); // No exceptions.

                // Program completion records should exist for all users.
                $progcompletions = $DB->get_records('prog_completion',
                    array('programid' => $certprogram->id,
                        'userid' => $user->id,
                        'coursesetid' => 0));
                $this->assertCount(1, $progcompletions); // Just one.
                $progcompletion = reset($progcompletions);
                // When it was started.
                $this->assertGreaterThan($this->setuptimeminimum, $progcompletion->timecreated);
                $this->assertLessThan($this->setuptimemaximum, $progcompletion->timecreated);
                if ($didfirstcompletion) {
                    $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status); // Set during window opening.
                    $this->assertEquals(userdate($timeexpires), userdate($progcompletion->timedue)); // Set when certified.
                    $this->assertGreaterThanOrEqual($this->firstcompletiontimeminimum, $progcompletion->timestarted);
                    $this->assertLessThanOrEqual($this->firstcompletiontimemaximum, $progcompletion->timestarted);
                } else {
                    $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status); // Status is incomplete.
                    $this->assertEquals(0, $progcompletion->timestarted);
                    if ($hasassignmentduedate) {
                        $this->assertEquals(userdate($this->assignmentduedate), userdate($progcompletion->timedue)); // Has timedue.
                    } else {
                        $this->assertEquals(-1, $progcompletion->timedue); // No timedue.
                    }
                }

                // Certification completion records should exist for all users.
                $certifcompletions = $DB->get_records('certif_completion',
                    array('certifid' => $certprogram->certifid,
                        'userid' => $user->id));
                $this->assertCount(1, $certifcompletions); // Just one.
                $certifcompletion = reset($certifcompletions);
                if ($didfirstcompletion) {
                    $this->assertEquals(CERTIFRENEWALSTATUS_DUE, $certifcompletion->renewalstatus); // Due for renewal.
                    $this->assertEquals(CERTIFPATH_RECERT, $certifcompletion->certifpath); // Recertification path.
                    $this->assertEquals(CERTIFSTATUS_COMPLETED, $certifcompletion->status); // Status completed.
                    $this->assertEquals(userdate($timeexpires), userdate($certifcompletion->timeexpires));
                    $this->assertEquals(userdate($timeexpires), userdate($certifcompletion->baselinetimeexpires));
                    $this->assertEquals(userdate($timewindowopens), userdate($certifcompletion->timewindowopens));
                    $this->assertEquals(userdate($this->firstcompletiontime), userdate($certifcompletion->timecompleted)); // Completed.
                    // When record was modified.
                    $this->assertGreaterThan($this->firstcompletiontimeminimum, $certifcompletion->timemodified);
                    $this->assertLessThan($this->firstcompletiontimemaximum, $certifcompletion->timemodified);
                } else {
                    $this->assertEquals(CERTIFRENEWALSTATUS_NOTDUE, $certifcompletion->renewalstatus); // Not due for renewal.
                    $this->assertEquals(CERTIFPATH_CERT, $certifcompletion->certifpath); // Primary certification path.
                    $this->assertEquals(CERTIFSTATUS_ASSIGNED, $certifcompletion->status); // Status assigned.
                    $this->assertEquals(0, $certifcompletion->timeexpires); // No expiry.
                    $this->assertEquals(0, $certifcompletion->baselinetimeexpires); // No expiry.
                    $this->assertEquals(0, $certifcompletion->timewindowopens); // No window.
                    $this->assertEquals(0, $certifcompletion->timecompleted); // Not completed.
                    // When record was created.
                    $this->assertGreaterThan($this->setuptimeminimum, $certifcompletion->timemodified);
                    $this->assertLessThan($this->setuptimemaximum, $certifcompletion->timemodified);
                }
            }
        }
    }

    public function check_stage_4() {
        global $DB;

        // Check the status of all users.
        foreach ($this->certprograms as $certprogram) {
            for ($i = 1; $i <= CERTIFICATION_USERS; $i++) {
                $user = $this->users[$i];
                $hasassignmentduedate = in_array($user, $this->userswithassignmentduedate);
                $didfirstcompletion = in_array($certprogram, $this->certsforcompletion) && $i <= CERTIFICATION_PART_2_USERS;
                $didsecondcompletion = in_array($certprogram, $this->certsforcompletion) && $i <= CERTIFICATION_PART_4_USERS;
                if ($didsecondcompletion) {
                    if ($certprogram == $this->certprogram1 || $certprogram == $this->certprogram3) {
                        /*
                         * CERTIFRECERT_EXPIRY and CERTIFRECERT_FIXED.
                         * At this point, these users do have previous expiry date.
                         */
                        if ($hasassignmentduedate) {
                            // These users have assignment due date (completiontime) in prog_assignment, so their previous
                            // timeexpires was $completiontime + 365 days.
                            $basetime = strtotime("365 day", $this->assignmentduedate);
                        } else {
                            // These users have no assignment due date (completiontime) in prog_assignment, so their previous
                            // timeexpires was $firstcompletiontime + 365 days.
                            $basetime = strtotime("365 day", $this->firstcompletiontime);
                        }
                    } else if ($certprogram == $this->certprogram2) {
                        // CERTIFRECERT_COMPLETION.
                        $basetime = $this->secondcompletiontime;
                    }
                    $timeexpires = get_timeexpires($basetime, '365 day');
                    $timewindowopens = get_timewindowopens($timeexpires, '90 day');
                } else if ($didfirstcompletion) { // But hasn't recertified.
                    if ($certprogram == $this->certprogram1 || $certprogram == $this->certprogram3) {
                        /*
                         * CERTIFRECERT_EXPIRY and CERTIFRECERT_FIXED.
                         * At this point, these users do have previous expiry date.
                         */
                        if ($hasassignmentduedate) {
                            // These users have assignment due date (completiontime) in prog_assignment.
                            $basetime = $this->assignmentduedate;
                        } else {
                            // These users have no assignment due date (completiontime), so need to use time completed.
                            $basetime = $this->firstcompletiontime;
                        }
                    } else if ($certprogram == $this->certprogram2) {
                        // CERTIFRECERT_COMPLETION.
                        $basetime = $this->firstcompletiontime;
                    }
                    $timeexpires = get_timeexpires($basetime, '365 day');
                    $timewindowopens = get_timewindowopens($timeexpires, '90 day');
                } else {
                    // Not applicable if they haven't certified.
                    $timeexpires = 'invalid';
                    $timewindowopens = 'invalid';
                }

                // Program assignment and program user assignment records should exist for all users.
                // We get all prog_user_assignment records and their matching prog_assignment records.
                $sql = "SELECT pa.*, pua.programid, pua.userid, pua.timeassigned, exceptionstatus
                          FROM {prog_assignment} pa
                          JOIN {prog_user_assignment} pua ON pua.assignmentid = pa.id
                         WHERE pua.programid = :programid AND pua.userid = :userid";
                $progassignments = $DB->get_records_sql($sql,
                    array('programid' => $certprogram->id,
                        'userid' => $user->id));
                $this->assertCount(1, $progassignments); // Just one.
                $progassignment = reset($progassignments);
                $this->assertEquals(ASSIGNTYPE_INDIVIDUAL, $progassignment->assignmenttype);
                $this->assertEquals($user->id, $progassignment->assignmenttypeid);
                $this->assertEquals(0, $progassignment->includechildren);
                if ($hasassignmentduedate) {
                    // Has assignment due date.
                    $this->assertEquals(userdate($this->assignmentduedate), userdate($progassignment->completiontime));
                } else {
                    $this->assertEquals(-1, $progassignment->completiontime); // No assignment due date.
                }
                $this->assertEquals(0, $progassignment->completionevent); // No completion event.
                $this->assertEquals(0, $progassignment->completioninstance); // No completion instance.
                // Time assigned.
                $this->assertGreaterThan($this->setuptimeminimum, $progassignment->timeassigned);
                $this->assertLessThan($this->setuptimemaximum, $progassignment->timeassigned);
                $this->assertEquals(PROGRAM_EXCEPTION_NONE, $progassignment->exceptionstatus); // No exceptions.

                // Program completion records should exist for all users.
                $progcompletions = $DB->get_records('prog_completion',
                    array('programid' => $certprogram->id,
                        'userid' => $user->id,
                        'coursesetid' => 0));
                $this->assertCount(1, $progcompletions); // Just one.
                $progcompletion = reset($progcompletions);
                // When it was started.
                $this->assertGreaterThan($this->setuptimeminimum, $progcompletion->timecreated);
                $this->assertLessThan($this->setuptimemaximum, $progcompletion->timecreated);
                if ($didsecondcompletion) {
                    $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status); // Set when completed.
                    $this->assertEquals(userdate($timeexpires), userdate($progcompletion->timedue)); // Set when certified.
                } else if ($didfirstcompletion) {
                    $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status); // Set during window opening.
                    $this->assertEquals(userdate($timeexpires), userdate($progcompletion->timedue)); // Set when certified.
                } else {
                    $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status); // Status is incomplete.
                    if ($i % 2) {
                        $this->assertEquals(userdate($this->assignmentduedate), userdate($progcompletion->timedue)); // Has timedue.
                    } else {
                        $this->assertEquals(-1, $progcompletion->timedue); // No timedue.
                    }
                }

                // Check the time started.
                if ($didfirstcompletion) {
                    $this->assertGreaterThanOrEqual($this->firstcompletiontimeminimum, $progcompletion->timestarted);
                    $this->assertLessThanOrEqual($this->firstcompletiontimemaximum, $progcompletion->timestarted);
                } else if ($didsecondcompletion) {
                    $this->assertGreaterThanOrEqual($this->secondcompletiontimeminimum, $progcompletion->timestarted);
                    $this->assertLessThanOrEqual($this->secondcompletiontimemaximum, $progcompletion->timestarted);
                } else {
                    $this->assertEquals(0, $progcompletion->timestarted);
                }

                // Certification completion records should exist for all users.
                $certifcompletions = $DB->get_records('certif_completion',
                    array('certifid' => $certprogram->certifid,
                        'userid' => $user->id));
                $this->assertCount(1, $certifcompletions); // Just one.
                $certifcompletion = reset($certifcompletions);
                if ($didsecondcompletion) {
                    $this->assertEquals(CERTIFRENEWALSTATUS_NOTDUE, $certifcompletion->renewalstatus); // Not due for renewal.
                    $this->assertEquals(CERTIFPATH_RECERT, $certifcompletion->certifpath); // Recertification path.
                    $this->assertEquals(CERTIFSTATUS_COMPLETED, $certifcompletion->status); // Status completed.
                    $this->assertEquals(userdate($timeexpires), userdate($certifcompletion->timeexpires));
                    $this->assertEquals(userdate($timeexpires), userdate($certifcompletion->baselinetimeexpires));
                    $this->assertEquals(userdate($timewindowopens), userdate($certifcompletion->timewindowopens));
                    $this->assertEquals(userdate($this->secondcompletiontime), userdate($certifcompletion->timecompleted)); // Completed.
                    // When record was modified.
                    $this->assertGreaterThan($this->secondcompletiontimeminimum, $certifcompletion->timemodified);
                    $this->assertLessThan($this->secondcompletiontimemaximum, $certifcompletion->timemodified);
                } else if ($didfirstcompletion) { // But haven't recertified.
                    $this->assertEquals(CERTIFRENEWALSTATUS_DUE, $certifcompletion->renewalstatus); // Due for renewal.
                    $this->assertEquals(CERTIFPATH_RECERT, $certifcompletion->certifpath); // Recertification path.
                    $this->assertEquals(CERTIFSTATUS_COMPLETED, $certifcompletion->status); // Status completed.
                    $this->assertEquals(userdate($timeexpires), userdate($certifcompletion->timeexpires));
                    $this->assertEquals(userdate($timeexpires), userdate($certifcompletion->baselinetimeexpires));
                    $this->assertEquals(userdate($timewindowopens), userdate($certifcompletion->timewindowopens));
                    $this->assertEquals(userdate($this->firstcompletiontime), userdate($certifcompletion->timecompleted)); // Completed.
                    // When record was modified.
                    $this->assertGreaterThan($this->firstcompletiontimeminimum, $certifcompletion->timemodified);
                    $this->assertLessThan($this->firstcompletiontimemaximum, $certifcompletion->timemodified);
                } else {
                    $this->assertEquals(CERTIFRENEWALSTATUS_NOTDUE, $certifcompletion->renewalstatus); // Not due for renewal.
                    $this->assertEquals(CERTIFPATH_CERT, $certifcompletion->certifpath); // Primary certification path.
                    $this->assertEquals(CERTIFSTATUS_ASSIGNED, $certifcompletion->status); // Status assigned.
                    $this->assertEquals(0, $certifcompletion->timeexpires); // No expiry.
                    $this->assertEquals(0, $certifcompletion->baselinetimeexpires); // No expiry.
                    $this->assertEquals(0, $certifcompletion->timewindowopens); // No window.
                    $this->assertEquals(0, $certifcompletion->timecompleted); // Not completed.
                    // When record was created.
                    $this->assertGreaterThan($this->setuptimeminimum, $certifcompletion->timemodified);
                    $this->assertLessThan($this->setuptimemaximum, $certifcompletion->timemodified);
                }
            }
        }
    }

    public function check_stage_5() {
        global $DB;

        // Check the status of all users.
        foreach ($this->certprograms as $certprogram) {
            for ($i = 1; $i <= CERTIFICATION_USERS; $i++) {
                $user = $this->users[$i];
                $hasassignmentduedate = in_array($user, $this->userswithassignmentduedate);
                $didfirstcompletion = in_array($certprogram, $this->certsforcompletion) && $i <= CERTIFICATION_PART_2_USERS;
                $didsecondcompletion = in_array($certprogram, $this->certsforcompletion) && $i <= CERTIFICATION_PART_4_USERS;
                if ($didsecondcompletion) {
                    if ($certprogram == $this->certprogram1 || $certprogram == $this->certprogram3) {
                        /*
                         * CERTIFRECERT_EXPIRY and CERTIFRECERT_FIXED.
                         * At this point, these users do have previous expiry date.
                         */
                        if ($hasassignmentduedate) {
                            // These users have assignment due date (completiontime) in prog_assignment, so their previous
                            // timeexpires was $completiontime + 365 days.
                            $basetime = strtotime("365 day", $this->assignmentduedate);
                        } else {
                            // These users have no assignment due date (completiontime) in prog_assignment, so their previous
                            // timeexpires was $firstcompletiontime + 365 days.
                            $basetime = strtotime("365 day", $this->firstcompletiontime);
                        }
                    } else if ($certprogram == $this->certprogram2) {
                        // CERTIFRECERT_COMPLETION.
                        $basetime = $this->secondcompletiontime;
                    }
                    $timeexpires = get_timeexpires($basetime, '365 day');
                    $timewindowopens = get_timewindowopens($timeexpires, '90 day');
                } else if ($didfirstcompletion) { // But didn't recertify, so is now expired and back on primary cert path, overdue.
                    if ($certprogram == $this->certprogram1 || $certprogram == $this->certprogram3) {
                        /*
                         * CERTIFRECERT_EXPIRY and CERTIFRECERT_FIXED.
                         * At this point, these users do have previous expiry date.
                         */
                        if ($hasassignmentduedate) {
                            // These users have assignment due date (completiontime) in prog_assignment.
                            $basetime = $this->assignmentduedate;
                        } else {
                            // These users have no assignment due date (completiontime), so need to use time completed.
                            $basetime = $this->firstcompletiontime;
                        }
                    } else if ($certprogram == $this->certprogram2) {
                        // CERTIFRECERT_COMPLETION.
                        $basetime = $this->firstcompletiontime;
                    }
                    $timeexpires = get_timeexpires($basetime, '365 day');
                    $timewindowopens = get_timewindowopens($timeexpires, '90 day');
                } else {
                    // Not applicable if they haven't certified.
                    $timeexpires = 'invalid';
                    $timewindowopens = 'invalid';
                }

                // Program assignment and program user assignment records should exist for all users.
                // We get all prog_user_assignment records and their matching prog_assignment records.
                $sql = "SELECT pa.*, pua.programid, pua.userid, pua.timeassigned, exceptionstatus
                          FROM {prog_assignment} pa
                          JOIN {prog_user_assignment} pua ON pua.assignmentid = pa.id
                         WHERE pua.programid = :programid AND pua.userid = :userid";
                $progassignments = $DB->get_records_sql($sql,
                    array('programid' => $certprogram->id,
                        'userid' => $user->id));
                $this->assertCount(1, $progassignments); // Just one.
                $progassignment = reset($progassignments);
                $this->assertEquals(ASSIGNTYPE_INDIVIDUAL, $progassignment->assignmenttype);
                $this->assertEquals($user->id, $progassignment->assignmenttypeid);
                $this->assertEquals(0, $progassignment->includechildren);
                if ($hasassignmentduedate) {
                    // Has assignment due date.
                    $this->assertEquals(userdate($this->assignmentduedate), userdate($progassignment->completiontime));
                } else {
                    $this->assertEquals(-1, $progassignment->completiontime); // No assignment due date.
                }
                $this->assertEquals(0, $progassignment->completionevent); // No completion event.
                $this->assertEquals(0, $progassignment->completioninstance); // No completion instance.
                // Time assigned.
                $this->assertGreaterThan($this->setuptimeminimum, $progassignment->timeassigned);
                $this->assertLessThan($this->setuptimemaximum, $progassignment->timeassigned);
                $this->assertEquals(PROGRAM_EXCEPTION_NONE, $progassignment->exceptionstatus); // No exceptions.

                // Program completion records should exist for all users.
                $progcompletions = $DB->get_records('prog_completion',
                    array('programid' => $certprogram->id,
                        'userid' => $user->id,
                        'coursesetid' => 0));
                $this->assertCount(1, $progcompletions); // Just one.
                $progcompletion = reset($progcompletions);
                // Check the time started.
                if ($didfirstcompletion) {
                    $this->assertGreaterThanOrEqual($this->firstcompletiontimeminimum, $progcompletion->timestarted);
                    $this->assertLessThanOrEqual($this->firstcompletiontimemaximum, $progcompletion->timestarted);
                } else if ($didsecondcompletion) {
                    $this->assertGreaterThanOrEqual($this->secondcompletiontimeminimum, $progcompletion->timestarted);
                    $this->assertLessThanOrEqual($this->secondcompletiontimemaximum, $progcompletion->timestarted);
                } else {
                    $this->assertEquals(0, $progcompletion->timestarted);
                }
                $this->assertGreaterThan($this->setuptimeminimum, $progcompletion->timecreated);
                $this->assertLessThan($this->setuptimemaximum, $progcompletion->timecreated);
                if ($didsecondcompletion) {
                    $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status); // Set during window opening.
                    $this->assertEquals(userdate($timeexpires), userdate($progcompletion->timedue)); // Set when certified.
                } else if ($didfirstcompletion) {
                    $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status); // Set during window opening.
                    $this->assertEquals(userdate($timeexpires), userdate($progcompletion->timedue)); // Set when certified.
                } else {
                    $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status); // Status is incomplete.
                    if ($hasassignmentduedate) {
                        $this->assertEquals(userdate($this->assignmentduedate), userdate($progcompletion->timedue)); // Has timedue.
                    } else {
                        $this->assertEquals(-1, $progcompletion->timedue); // No timedue.
                    }
                }

                // Certification completion records should exist for all users.
                $certifcompletions = $DB->get_records('certif_completion',
                    array('certifid' => $certprogram->certifid,
                        'userid' => $user->id));
                $this->assertCount(1, $certifcompletions); // Just one.
                $certifcompletion = reset($certifcompletions);
                if ($didsecondcompletion) {
                    $this->assertEquals(CERTIFRENEWALSTATUS_DUE, $certifcompletion->renewalstatus); // Due for renewal.
                    $this->assertEquals(CERTIFPATH_RECERT, $certifcompletion->certifpath); // Recertification path.
                    $this->assertEquals(CERTIFSTATUS_COMPLETED, $certifcompletion->status); // Status completed.
                    $this->assertEquals(userdate($timeexpires), userdate($certifcompletion->timeexpires));
                    $this->assertEquals(userdate($timeexpires), userdate($certifcompletion->baselinetimeexpires));
                    $this->assertEquals(userdate($timewindowopens), userdate($certifcompletion->timewindowopens));
                    $this->assertEquals(userdate($this->secondcompletiontime), userdate($certifcompletion->timecompleted)); // Completed.
                    // When record was modified.
                    $this->assertGreaterThan($this->secondcompletiontimeminimum, $certifcompletion->timemodified);
                    $this->assertLessThan($this->secondcompletiontimemaximum, $certifcompletion->timemodified);
                } else {
                    // Those who failed to recertify will be back on the primary certification path.
                    if ($didfirstcompletion) {
                        $this->assertEquals(CERTIFRENEWALSTATUS_EXPIRED, $certifcompletion->renewalstatus); // Expired.
                        $this->assertEquals(CERTIFSTATUS_EXPIRED, $certifcompletion->status);
                    } else {
                        $this->assertEquals(CERTIFRENEWALSTATUS_NOTDUE, $certifcompletion->renewalstatus); // Not due for renewal.
                        $this->assertEquals(CERTIFSTATUS_ASSIGNED, $certifcompletion->status); // Status assigned, as if newly assigned.
                    }
                    $this->assertEquals(CERTIFPATH_CERT, $certifcompletion->certifpath); // Back to primary certification path.
                    $this->assertEquals(0, $certifcompletion->timeexpires); // No expiry.
                    $this->assertEquals(0, $certifcompletion->baselinetimeexpires); // No expiry.
                    $this->assertEquals(0, $certifcompletion->timewindowopens); // No window.
                    $this->assertEquals(0, $certifcompletion->timecompleted); // Not completed.
                    // When record was created.
                    if ($didfirstcompletion) {
                        // Those who did the first but not the second are now expired, which caused refresh of timemodified.
                        $this->assertGreaterThan($this->secondcrontimeminimum, $certifcompletion->timemodified);
                        $this->assertLessThan($this->secondcrontimemaximum, $certifcompletion->timemodified);
                    } else {
                        $this->assertGreaterThan($this->setuptimeminimum, $certifcompletion->timemodified);
                        $this->assertLessThan($this->setuptimemaximum, $certifcompletion->timemodified);
                    }
                }
            }
        }
    }

    public function check_stage_6() {
        global $DB;

        // Check the status of all users.
        foreach ($this->certprograms as $certprogram) {
            for ($i = 1; $i <= CERTIFICATION_USERS; $i++) {
                $user = $this->users[$i];
                $hasassignmentduedate = in_array($user, $this->userswithassignmentduedate);
                $didfirstcompletion = in_array($certprogram, $this->certsforcompletion) && $i <= CERTIFICATION_PART_2_USERS;
                $didsecondcompletion = in_array($certprogram, $this->certsforcompletion) && $i <= CERTIFICATION_PART_4_USERS;
                $didthirdcompletion = in_array($certprogram, $this->certsforcompletion);
                if ($didthirdcompletion) { // All users in programs 1, 2 and 3.
                    if ($didsecondcompletion) { // They are recertifying for the second time.
                        if ($certprogram == $this->certprogram1 || $certprogram == $this->certprogram3) {
                            /*
                             * CERTIFRECERT_EXPIRY and CERTIFRECERT_FIXED.
                             * At this point, these users do have previous expiry date.
                             */
                            if ($hasassignmentduedate) {
                                // These users have assignment due date (completiontime) in prog_assignment, so their previous
                                // timeexpires was $completiontime + 365 days + 365 days.
                                $basetime = strtotime("730 day", $this->assignmentduedate);
                            } else {
                                // These users have no assignment due date (completiontime) in prog_assignment, so their previous
                                // timeexpires was $firstcompletiontime + 365 days + 365 days.
                                $basetime = strtotime("730 day", $this->firstcompletiontime);
                            }
                        } else if ($certprogram == $this->certprogram2) {
                            // CERTIFRECERT_COMPLETION.
                            $basetime = $this->thirdcompletiontime;
                        }
                    } else { // Expired first certification or certifying overdue for the first time.
                        if ($certprogram == $this->certprogram1) {
                            /*
                             * CERTIFRECERT_EXPIRY.
                             * Since they are overdue, just use the completion date.
                             */
                            $basetime = $this->thirdcompletiontime;
                        } else if ($certprogram == $this->certprogram2) {
                            // CERTIFRECERT_COMPLETION.
                            $basetime = $this->thirdcompletiontime;
                        } else if ($certprogram == $this->certprogram3) {
                            /*
                             * CERTIFRECERT_FIXED.
                             */
                            if ($hasassignmentduedate) {
                                // These users have assignment due date (completiontime) in prog_assignment, so their previous
                                // timeexpires was $completiontime + 365 days + 365 days.
                                $basetime = strtotime("730 day", $this->assignmentduedate);
                            } else {
                                // These users have no assignment due date (completiontime) in prog_assignment.
                                if ($didfirstcompletion) {
                                    // If they did the first completion, use $firstcompletiontime + 365 days + 365 days.
                                    $basetime = strtotime("730 day", $this->firstcompletiontime);
                                } else {
                                    // This is their first completion.
                                    $basetime = $this->thirdcompletiontime;
                                }
                            }
                        }
                    }
                    $timeexpires = get_timeexpires($basetime, '365 day');
                    $timewindowopens = get_timewindowopens($timeexpires, '90 day');
                } else {
                    // Not applicable if they haven't certified.
                    $timeexpires = 'invalid';
                    $timewindowopens = 'invalid';
                }

                // Program assignment and program user assignment records should exist for all users.
                // We get all prog_user_assignment records and their matching prog_assignment records.
                $sql = "SELECT pa.*, pua.programid, pua.userid, pua.timeassigned, exceptionstatus
                          FROM {prog_assignment} pa
                          JOIN {prog_user_assignment} pua ON pua.assignmentid = pa.id
                         WHERE pua.programid = :programid AND pua.userid = :userid";
                $progassignments = $DB->get_records_sql($sql,
                    array('programid' => $certprogram->id,
                        'userid' => $user->id));
                $this->assertCount(1, $progassignments); // Just one.
                $progassignment = reset($progassignments);
                $this->assertEquals(ASSIGNTYPE_INDIVIDUAL, $progassignment->assignmenttype);
                $this->assertEquals($user->id, $progassignment->assignmenttypeid);
                $this->assertEquals(0, $progassignment->includechildren);
                if ($hasassignmentduedate) {
                    // Has assignment due date.
                    $this->assertEquals(userdate($this->assignmentduedate), userdate($progassignment->completiontime));
                } else {
                    $this->assertEquals(-1, $progassignment->completiontime); // No assignment due date.
                }
                $this->assertEquals(0, $progassignment->completionevent); // No completion event.
                $this->assertEquals(0, $progassignment->completioninstance); // No completion instance.
                // Time assigned.
                $this->assertGreaterThan($this->setuptimeminimum, $progassignment->timeassigned);
                $this->assertLessThan($this->setuptimemaximum, $progassignment->timeassigned);
                $this->assertEquals(PROGRAM_EXCEPTION_NONE, $progassignment->exceptionstatus); // No exceptions.

                // Program completion records should exist for all users.
                $progcompletions = $DB->get_records('prog_completion',
                    array('programid' => $certprogram->id,
                        'userid' => $user->id,
                        'coursesetid' => 0));
                $this->assertCount(1, $progcompletions); // Just one.
                $progcompletion = reset($progcompletions);
                // Check the time started.
                if ($didfirstcompletion) {
                    $this->assertGreaterThanOrEqual($this->firstcompletiontimeminimum, $progcompletion->timestarted);
                    $this->assertLessThanOrEqual($this->firstcompletiontimemaximum, $progcompletion->timestarted);
                } else if ($didsecondcompletion) {
                    $this->assertGreaterThanOrEqual($this->secondcompletiontimeminimum, $progcompletion->timestarted);
                    $this->assertLessThanOrEqual($this->secondcompletiontimemaximum, $progcompletion->timestarted);
                } else if ($didthirdcompletion) {
                    $this->assertGreaterThanOrEqual($this->thirdcompletiontimeminimum, $progcompletion->timestarted);
                    $this->assertLessThanOrEqual($this->thirdcompletiontimemaximum, $progcompletion->timestarted);
                } else {
                    $this->assertEquals(0, $progcompletion->timestarted);
                }
                $this->assertGreaterThan($this->setuptimeminimum, $progcompletion->timecreated);
                $this->assertLessThan($this->setuptimemaximum, $progcompletion->timecreated);
                if ($didthirdcompletion) {
                    $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status); // Set when completed.
                    $this->assertEquals(userdate($timeexpires), userdate($progcompletion->timedue)); // Set during window opening.
                } else {
                    $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status); // Set during setup.
                    if ($hasassignmentduedate) {
                        $this->assertEquals(userdate($this->assignmentduedate), userdate($progcompletion->timedue)); // Has timedue.
                    } else {
                        $this->assertEquals(-1, $progcompletion->timedue); // No timedue.
                    }
                }

                // Certification completion records should exist for all users.
                $certifcompletions = $DB->get_records('certif_completion',
                    array('certifid' => $certprogram->certifid,
                        'userid' => $user->id));
                $this->assertCount(1, $certifcompletions); // Just one.
                $certifcompletion = reset($certifcompletions);
                if ($didthirdcompletion) {
                    $this->assertEquals(CERTIFRENEWALSTATUS_NOTDUE, $certifcompletion->renewalstatus); // Not due for renewal.
                    $this->assertEquals(CERTIFPATH_RECERT, $certifcompletion->certifpath); // Recertification path.
                    $this->assertEquals(CERTIFSTATUS_COMPLETED, $certifcompletion->status); // Status completed.
                    $this->assertEquals(userdate($timeexpires), userdate($certifcompletion->timeexpires));
                    $this->assertEquals(userdate($timeexpires), userdate($certifcompletion->baselinetimeexpires));
                    $this->assertEquals(userdate($timewindowopens), userdate($certifcompletion->timewindowopens));
                    $this->assertEquals(userdate($this->thirdcompletiontime), userdate($certifcompletion->timecompleted)); // Completed.
                    // When record was modified.
                    $this->assertGreaterThan($this->thirdcompletiontimeminimum, $certifcompletion->timemodified);
                    $this->assertLessThan($this->thirdcompletiontimemaximum, $certifcompletion->timemodified);
                } else {
                    // Just those who are in the certifications that were left alone.
                    $this->assertEquals(CERTIFRENEWALSTATUS_NOTDUE, $certifcompletion->renewalstatus); // Not due for renewal.
                    $this->assertEquals(CERTIFPATH_CERT, $certifcompletion->certifpath); // Back to primary certification path.
                    $this->assertEquals(CERTIFSTATUS_ASSIGNED, $certifcompletion->status); // Status assigned, as if newly assigned.
                    $this->assertEquals(0, $certifcompletion->timeexpires); // No expiry.
                    $this->assertEquals(0, $certifcompletion->baselinetimeexpires); // No expiry.
                    $this->assertEquals(0, $certifcompletion->timewindowopens); // No window.
                    $this->assertEquals(0, $certifcompletion->timecompleted); // Not completed.
                    // When record was created.
                    $this->assertGreaterThan($this->setuptimeminimum, $certifcompletion->timemodified);
                    $this->assertLessThan($this->setuptimemaximum, $certifcompletion->timemodified);
                }
            }
        }
    }
}
