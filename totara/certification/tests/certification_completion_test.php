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

/**
 * Certification module PHPUnit archive test class.
 *
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose totara_certification_certification_completion_testcase totara/certification/tests/certification_completion_test.php
 */
class totara_certification_certification_completion_testcase extends reportcache_advanced_testcase {

    public $users = array();
    public $programs = array();
    public $certifications = array();
    public $numtestusers = 10;
    public $numtestcerts = 10;
    public $numtestprogs = 7;

    protected function tearDown() {
        $this->users = null;
        $this->programs = null;
        $this->certifications = null;
        $this->numtestusers = null;
        $this->numtestcerts = null;
        $this->numtestprogs = null;
        parent::tearDown();
    }

    /**
     * Set up the users, certifications and completions.
     */
    public function setup_completions() {
        $this->resetAfterTest(true);

        // Turn off programs. This is to test that it doesn't interfere with certification completion.
        set_config('enableprograms', TOTARA_DISABLEFEATURE);

        // Create users.
        for ($i = 1; $i <= $this->numtestusers; $i++) {
            $this->users[$i] = $this->getDataGenerator()->create_user();
        }

        // Create programs, mostly so that we don't end up with coincidental success due to matching ids.
        for ($i = 1; $i <= $this->numtestprogs; $i++) {
            $this->programs[$i] = $this->getDataGenerator()->create_program();
        }

        // Create certifications.
        for ($i = 1; $i <= $this->numtestcerts; $i++) {
            $this->certifications[$i] = $this->getDataGenerator()->create_certification();
        }

        // Assign users to the certification as individuals.
        foreach ($this->users as $user) {
            foreach ($this->certifications as $prog) {
                $this->getDataGenerator()->assign_to_program($prog->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
            }
        }
    }

    public function test_certif_process_submitted_edit_completion() {
        global $DB;

        $this->setup_completions();

        // Select a user and cert to use for the test.
        $user = $this->users[3];
        $prog = $this->certifications[6];

        $submitted = new stdClass();
        $submitted->id = $prog->id; // Program id (the cert test generator returns a program object).
        $submitted->userid = $user->id;
        $submitted->status = 1001;
        $submitted->renewalstatus = 1002;
        $submitted->certifpath = 1003;
        $submitted->timecompleted = 1004;
        $submitted->timewindowopens = 1005;
        $submitted->timeexpires = 1006;
        $submitted->baselinetimeexpires = 1010;
        $submitted->progstatus = 1007;
        $submitted->timeduenotset = 'no';
        $submitted->timedue = 1008;
        $submitted->progtimecompleted = 1009;

        $timebefore = time();
        list($certcompletion, $progcompletion) = certif_process_submitted_edit_completion($submitted);
        $timeafter = time();

        $certcompletionid = $DB->get_field('certif_completion', 'id',
            array('certifid' => $prog->certifid, 'userid' => $user->id));
        $progcompletionid = $DB->get_field('prog_completion', 'id',
            array('programid' => $prog->id, 'userid' => $user->id));

        $this->assertEquals($certcompletionid, $certcompletion->id);
        $this->assertEquals($prog->certifid, $certcompletion->certifid);
        $this->assertEquals($user->id, $certcompletion->userid);
        $this->assertEquals(1001, $certcompletion->status);
        $this->assertEquals(1002, $certcompletion->renewalstatus);
        $this->assertEquals(1003, $certcompletion->certifpath);
        $this->assertEquals(1004, $certcompletion->timecompleted);
        $this->assertEquals(1005, $certcompletion->timewindowopens);
        $this->assertEquals(1006, $certcompletion->timeexpires);
        $this->assertEquals(1010, $certcompletion->baselinetimeexpires);
        $this->assertGreaterThanOrEqual($timebefore, $certcompletion->timemodified);
        $this->assertLessThanOrEqual($timeafter, $certcompletion->timemodified);

        $this->assertEquals($progcompletionid, $progcompletion->id);
        $this->assertEquals($prog->id, $progcompletion->programid);
        $this->assertEquals($user->id, $progcompletion->userid);
        $this->assertEquals(1007, $progcompletion->status);
        $this->assertEquals(1008, $progcompletion->timedue);
        $this->assertEquals(1009, $progcompletion->timecompleted);
        $this->assertGreaterThanOrEqual($timebefore, $progcompletion->timemodified);
        $this->assertLessThanOrEqual($timeafter, $progcompletion->timemodified);

        $this->assertEquals($certcompletion->timemodified, $progcompletion->timemodified);

        // Run a second test, just disable the due date.
        $submitted->timeduenotset = 'yes';
        $submitted->timedue = 56789;

        $timebefore = time();
        list($certcompletion, $progcompletion) = certif_process_submitted_edit_completion($submitted);
        $timeafter = time();

        $certcompletionid = $DB->get_field('certif_completion', 'id',
            array('certifid' => $prog->certifid, 'userid' => $user->id));
        $progcompletionid = $DB->get_field('prog_completion', 'id',
            array('programid' => $prog->id, 'userid' => $user->id));

        $this->assertEquals($certcompletionid, $certcompletion->id);
        $this->assertEquals($prog->certifid, $certcompletion->certifid);
        $this->assertEquals($user->id, $certcompletion->userid);
        $this->assertEquals(1001, $certcompletion->status);
        $this->assertEquals(1002, $certcompletion->renewalstatus);
        $this->assertEquals(1003, $certcompletion->certifpath);
        $this->assertEquals(1004, $certcompletion->timecompleted);
        $this->assertEquals(1005, $certcompletion->timewindowopens);
        $this->assertEquals(1006, $certcompletion->timeexpires);
        $this->assertEquals(1010, $certcompletion->baselinetimeexpires);
        $this->assertGreaterThanOrEqual($timebefore, $certcompletion->timemodified);
        $this->assertLessThanOrEqual($timeafter, $certcompletion->timemodified);

        $this->assertEquals($progcompletionid, $progcompletion->id);
        $this->assertEquals($prog->id, $progcompletion->programid);
        $this->assertEquals($user->id, $progcompletion->userid);
        $this->assertEquals(1007, $progcompletion->status);
        $this->assertEquals(-1, $progcompletion->timedue);
        $this->assertEquals(1009, $progcompletion->timecompleted);
        $this->assertGreaterThanOrEqual($timebefore, $progcompletion->timemodified);
        $this->assertLessThanOrEqual($timeafter, $progcompletion->timemodified);

        $this->assertEquals($certcompletion->timemodified, $progcompletion->timemodified);
    }

    /**
     * certif_get_completion_change_consequences is basically just a lookup table
     * for some strings. This test just makes sure that the cron stuff is correct.
     */
    public function test_certif_get_completion_change_consequences() {
        $now = time();

        // New record is assigned.
        $newcertcompletion = new stdClass();
        list($userresults, $cronresults) =
            certif_get_completion_change_consequences(CERTIFCOMPLETIONSTATE_INVALID, CERTIFCOMPLETIONSTATE_ASSIGNED,
                $newcertcompletion);
        $this->assertEquals(array(), $cronresults);

        // New record is certified, before window opens.
        $newcertcompletion = new stdClass();
        $newcertcompletion->timewindowopens = $now + 10000;
        $newcertcompletion->timeexpires = $now + 20000;
        list($userresults, $cronresults) =
            certif_get_completion_change_consequences(CERTIFCOMPLETIONSTATE_INVALID, CERTIFCOMPLETIONSTATE_CERTIFIED,
                $newcertcompletion);
        $this->assertEquals(array(), $cronresults);

        // New record is certified, window should have opened.
        $newcertcompletion = new stdClass();
        $newcertcompletion->timewindowopens = $now - 10000;
        $newcertcompletion->timeexpires = $now + 10000;
        list($userresults, $cronresults) =
            certif_get_completion_change_consequences(CERTIFCOMPLETIONSTATE_INVALID, CERTIFCOMPLETIONSTATE_CERTIFIED,
                $newcertcompletion);
        $this->assertEquals(array('completionchangecronwindowopen'), $cronresults);

        // New record is certified, window should have opened and should have expired.
        $newcertcompletion = new stdClass();
        $newcertcompletion->timewindowopens = $now - 20000;
        $newcertcompletion->timeexpires = $now - 10000;
        list($userresults, $cronresults) =
            certif_get_completion_change_consequences(CERTIFCOMPLETIONSTATE_INVALID, CERTIFCOMPLETIONSTATE_CERTIFIED,
                $newcertcompletion);
        $this->assertEquals(array('completionchangecronwindowopen', 'completionchangecronexpire'), $cronresults);

        // New record is window open, before expiry.
        $newcertcompletion = new stdClass();
        $newcertcompletion->timeexpires = $now + 10000;
        list($userresults, $cronresults) =
            certif_get_completion_change_consequences(CERTIFCOMPLETIONSTATE_INVALID, CERTIFCOMPLETIONSTATE_WINDOWOPEN,
                $newcertcompletion);
        $this->assertEquals(array(), $cronresults);

        // New record is window open, should have expired.
        $newcertcompletion = new stdClass();
        $newcertcompletion->timeexpires = $now - 10000;
        list($userresults, $cronresults) =
            certif_get_completion_change_consequences(CERTIFCOMPLETIONSTATE_INVALID, CERTIFCOMPLETIONSTATE_WINDOWOPEN,
                $newcertcompletion);
        $this->assertEquals(array('completionchangecronexpire'), $cronresults);

        // New record is expired.
        $newcertcompletion = new stdClass();
        list($userresults, $cronresults) =
            certif_get_completion_change_consequences(CERTIFCOMPLETIONSTATE_INVALID, CERTIFCOMPLETIONSTATE_EXPIRED,
                $newcertcompletion);
        $this->assertEquals(array(), $cronresults);
    }

    /**
     * Data provider for test_certif_get_completion_state.
     *
     * Note that several of the tested combinations are not possible, but this is ok because the problems are
     * reported later by certif_get_completion_errors.
     */
    public function data_certif_get_completion_state() {
        return array(
            array(CERTIFSTATUS_UNSET, CERTIFRENEWALSTATUS_NOTDUE, CERTIFCOMPLETIONSTATE_INVALID),
            array(CERTIFSTATUS_UNSET, CERTIFRENEWALSTATUS_DUE, CERTIFCOMPLETIONSTATE_INVALID),
            array(CERTIFSTATUS_UNSET, CERTIFRENEWALSTATUS_EXPIRED, CERTIFCOMPLETIONSTATE_INVALID),
            array(CERTIFSTATUS_ASSIGNED, CERTIFRENEWALSTATUS_NOTDUE, CERTIFCOMPLETIONSTATE_ASSIGNED),
            array(CERTIFSTATUS_ASSIGNED, CERTIFRENEWALSTATUS_DUE, CERTIFCOMPLETIONSTATE_ASSIGNED),
            array(CERTIFSTATUS_ASSIGNED, CERTIFRENEWALSTATUS_EXPIRED, CERTIFCOMPLETIONSTATE_ASSIGNED),
            array(CERTIFSTATUS_INPROGRESS, CERTIFRENEWALSTATUS_NOTDUE, CERTIFCOMPLETIONSTATE_ASSIGNED),
            array(CERTIFSTATUS_INPROGRESS, CERTIFRENEWALSTATUS_DUE, CERTIFCOMPLETIONSTATE_WINDOWOPEN),
            array(CERTIFSTATUS_INPROGRESS, CERTIFRENEWALSTATUS_EXPIRED, CERTIFCOMPLETIONSTATE_EXPIRED),
            array(CERTIFSTATUS_COMPLETED, CERTIFRENEWALSTATUS_NOTDUE, CERTIFCOMPLETIONSTATE_CERTIFIED),
            array(CERTIFSTATUS_COMPLETED, CERTIFRENEWALSTATUS_DUE, CERTIFCOMPLETIONSTATE_WINDOWOPEN),
            array(CERTIFSTATUS_COMPLETED, CERTIFRENEWALSTATUS_EXPIRED, CERTIFCOMPLETIONSTATE_CERTIFIED),
            array(CERTIFSTATUS_EXPIRED, CERTIFRENEWALSTATUS_NOTDUE, CERTIFCOMPLETIONSTATE_EXPIRED),
            array(CERTIFSTATUS_EXPIRED, CERTIFRENEWALSTATUS_DUE, CERTIFCOMPLETIONSTATE_EXPIRED),
            array(CERTIFSTATUS_EXPIRED, CERTIFRENEWALSTATUS_EXPIRED, CERTIFCOMPLETIONSTATE_EXPIRED),
        );
    }

    /**
     * Test certif_get_completion_state.
     *
     * @dataProvider data_certif_get_completion_state
     */
    public function test_certif_get_completion_state($status, $renewalstatus, $expectedstate) {
        $certcompletion = new stdClass();
        $certcompletion->status = $status;
        $certcompletion->renewalstatus = $renewalstatus;

        $state = certif_get_completion_state($certcompletion);
        $this->assertEquals($expectedstate, $state);
    }

    /**
     * Data provider for test_certif_get_completion_errors_for_current.
     *
     * Each test matches one of the scenarios in certif_get_completion_state.
     */
    public function data_certif_get_completion_errors_for_current() {
        return array(
            // Cert status isn't set. We deliberately don't set the other fields because they shouldn't be used.
            array('cert status not set',
                array('status' => CERTIFSTATUS_UNSET),
                array('status' => STATUS_PROGRAM_COMPLETE),
                array('error:completionstatusunset' => 'state')
            ),
            // Really wrong prog completion status.
            array('prog completion status courseset status complete',
                array('status' => CERTIFSTATUS_ASSIGNED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_NOTDUE,
                    'certifpath' => CERTIFPATH_CERT,
                    'timecompleted' => 0,
                    'timewindowopens' => 0,
                    'timeexpires' => 0,
                    'baselinetimeexpires' => 0),
                array('status' => STATUS_COURSESET_COMPLETE, // 1, 2: Should be STATUS_PROGRAM_INCOMPLETE.
                    'timecompleted' => 0,
                    'timedue' => 1001),
                array('error:stateassigned-progstatusincorrect' => 'progstatus', // 1.
                    'error:progstatusinvalid' => 'progstatus') // 2.
            ),
            // Really wrong prog completion status.
            array('prog completion status courseset status incomplete',
                array('status' => CERTIFSTATUS_ASSIGNED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_NOTDUE,
                    'certifpath' => CERTIFPATH_CERT,
                    'timecompleted' => 0,
                    'timewindowopens' => 0,
                    'timeexpires' => 0,
                    'baselinetimeexpires' => 0),
                array('status' => STATUS_COURSESET_INCOMPLETE, // 1, 2: Should be STATUS_PROGRAM_INCOMPLETE.
                    'timecompleted' => 0,
                    'timedue' => 1001),
                array('error:stateassigned-progstatusincorrect' => 'progstatus', // 1.
                    'error:progstatusinvalid' => 'progstatus') // 2.
            ),
            // Assigned, problems #1.
            array('assigned problems #1',
                array('status' => CERTIFSTATUS_ASSIGNED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_DUE, // 1: Should be CERTIFRENEWALSTATUS_NOTDUE.
                    'certifpath' => CERTIFPATH_RECERT, // 2: Should be CERTIFPATH_CERT.
                    'timecompleted' => 1001, // 3: Should be 0.
                    'timewindowopens' => 1002, // 4: Should be 0.
                    'timeexpires' => 1003, // 5: Should be 0.
                    'baselinetimeexpires' => 1005), // 6: Should be 0
                array('status' => STATUS_PROGRAM_COMPLETE, // 7: Should be STATUS_PROGRAM_INCOMPLETE.
                    'timecompleted' => 1004, // 8: Should be 0.
                    'timedue' => COMPLETION_TIME_UNKNOWN), // 9: Should be anything else.
                array('error:stateassigned-renewalstatusincorrect' => 'renewalstatus', // 1.
                    'error:stateassigned-pathincorrect' => 'certifpath', // 2.
                    'error:stateassigned-timecompletednotempty' => 'timecompleted', // 3.
                    'error:stateassigned-timewindowopensnotempty' => 'timewindowopens', // 4.
                    'error:stateassigned-timeexpiresnotempty' => 'timeexpires', // 5.
                    'error:stateassigned-baselinetimeexpiresnotempty' => 'baselinetimeexpires', // 5.
                    'error:stateassigned-progstatusincorrect' => 'progstatus', // 7.
                    'error:stateassigned-progtimecompletednotempty' => 'progtimecompleted', // 8.
                    'error:stateassigned-timedueunknown' => 'timedue') // 9.
            ),
            // Assigned, problems #2.
            array('assigned problems #2',
                array('status' => CERTIFSTATUS_ASSIGNED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_EXPIRED, // 1: Should be CERTIFRENEWALSTATUS_NOTDUE.
                    'certifpath' => CERTIFPATH_CERT,
                    'timecompleted' => -1, // 2: Should be 0.
                    'timewindowopens' => -1, // 3: Should be 0.
                    'timeexpires' => -1, // 4: Should be 0.
                    'baselinetimeexpires' => -1), // 5: Should be 0
                array('status' => STATUS_PROGRAM_INCOMPLETE,
                    'timecompleted' => -1, // 6: Should be 0.
                    'timedue' => 1001),
                array('error:stateassigned-renewalstatusincorrect' => 'renewalstatus', // 1.
                    'error:stateassigned-timecompletednotempty' => 'timecompleted', // 2.
                    'error:stateassigned-timewindowopensnotempty' => 'timewindowopens', // 3.
                    'error:stateassigned-timeexpiresnotempty' => 'timeexpires', // 4.
                    'error:stateassigned-baselinetimeexpiresnotempty' => 'baselinetimeexpires', // 5.
                    'error:stateassigned-progtimecompletednotempty' => 'progtimecompleted') // 6.
            ),
            // Assigned, no problems.
            array('assigned no problems',
                array('status' => CERTIFSTATUS_ASSIGNED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_NOTDUE,
                    'certifpath' => CERTIFPATH_CERT,
                    'timecompleted' => 0,
                    'timewindowopens' => 0,
                    'timeexpires' => 0,
                    'baselinetimeexpires' => 0),
                array('status' => STATUS_PROGRAM_INCOMPLETE,
                    'timecompleted' => 0,
                    'timedue' => COMPLETION_TIME_NOT_SET),
                array()
            ),
            // In progress + not due => assigned, problems #1.
            array('in progress not due problems #1',
                array('status' => CERTIFSTATUS_INPROGRESS,
                    'renewalstatus' => CERTIFRENEWALSTATUS_NOTDUE,
                    'certifpath' => CERTIFPATH_RECERT, // 1: Should be CERTIFPATH_CERT.
                    'timecompleted' => 1001, // 3: Should be 0.
                    'timewindowopens' => 1002, // 4: Should be 0.
                    'timeexpires' => 1003, // 5: Should be 0.
                    'baselinetimeexpires' => 1003), // 6: Should be 0.
                array('status' => STATUS_PROGRAM_COMPLETE, // 7: Should be STATUS_PROGRAM_INCOMPLETE.
                    'timecompleted' => 1004, // 8: Should be 0.
                    'timedue' => COMPLETION_TIME_UNKNOWN), // 9: Should be anything else.
                array('error:stateassigned-pathincorrect' => 'certifpath', // 1.
                    'error:stateassigned-timecompletednotempty' => 'timecompleted', // 3.
                    'error:stateassigned-timewindowopensnotempty' => 'timewindowopens', // 4.
                    'error:stateassigned-timeexpiresnotempty' => 'timeexpires', // 5.
                    'error:stateassigned-baselinetimeexpiresnotempty' => 'baselinetimeexpires', // 6.
                    'error:stateassigned-progstatusincorrect' => 'progstatus', // 7.
                    'error:stateassigned-progtimecompletednotempty' => 'progtimecompleted', // 8.
                    'error:stateassigned-timedueunknown' => 'timedue') // 9.
            ),
            // In progress + not due => assigned, problems #2.
            array('in progress not due problems #2',
                array('status' => CERTIFSTATUS_INPROGRESS,
                    'renewalstatus' => CERTIFRENEWALSTATUS_NOTDUE,
                    'certifpath' => CERTIFPATH_CERT,
                    'timecompleted' => -1, // 2: Should be 0.
                    'timewindowopens' => -1, // 3: Should be 0.
                    'timeexpires' => -1, // 4: Should be 0.
                    'baselinetimeexpires' => -1), // 5: Should be 0.
                array('status' => STATUS_PROGRAM_INCOMPLETE,
                    'timecompleted' => -1, // 6: Should be 0.
                    'timedue' => 1001),
                array('error:stateassigned-timecompletednotempty' => 'timecompleted', // 2.
                    'error:stateassigned-timewindowopensnotempty' => 'timewindowopens', // 3.
                    'error:stateassigned-timeexpiresnotempty' => 'timeexpires', // 4.
                    'error:stateassigned-baselinetimeexpiresnotempty' => 'baselinetimeexpires', // 5.
                    'error:stateassigned-progtimecompletednotempty' => 'progtimecompleted') // 6.
            ),
            // In progress + not due => assigned, no problems.
            array('in progress not due no problems',
                array('status' => CERTIFSTATUS_INPROGRESS,
                    'renewalstatus' => CERTIFRENEWALSTATUS_NOTDUE,
                    'certifpath' => CERTIFPATH_CERT,
                    'timecompleted' => 0,
                    'timewindowopens' => 0,
                    'timeexpires' => 0,
                    'baselinetimeexpires' => 0),
                array('status' => STATUS_PROGRAM_INCOMPLETE,
                    'timecompleted' => 0,
                    'timedue' => COMPLETION_TIME_NOT_SET),
                array()
            ),
            // Completed + not due => certified, problems #1.
            array('certified not due problems #1',
                array('status' => CERTIFSTATUS_COMPLETED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_NOTDUE,
                    'certifpath' => CERTIFPATH_CERT, // 1: Should be CERTIFPATH_RECERT.
                    'timecompleted' => 0, // 3: Should be > 0.
                    'timewindowopens' => 0, // 4: Should be > 0.
                    'timeexpires' => 0, // 5: Should be > 0.
                    'baselinetimeexpires' => 0), // 6: Should be > 0.
                array('status' => STATUS_PROGRAM_INCOMPLETE, // 7: Should be STATUS_PROGRAM_COMPLETE.
                    'timecompleted' => 0, // 8: Should be > 0.
                    'timedue' => COMPLETION_TIME_UNKNOWN), // 9: Should be > 0.
                array('error:statecertified-pathincorrect' => 'certifpath', // 1.
                    'error:statecertified-timecompletedempty' => 'timecompleted', // 3.
                    'error:statecertified-timewindowopensempty' => 'timewindowopens', // 4.
                    'error:statecertified-timeexpiresempty' => 'timeexpires', // 5.
                    'error:statecertified-baselinetimeexpiresempty' => 'baselinetimeexpires', // 6.
                    'error:statecertified-progstatusincorrect' => 'progstatus', // 7.
                    'error:statecertified-progtimecompletedempty' => 'progtimecompleted', // 8.
                    'error:statecertified-timedueempty' => 'timedue') // 9.
            ),
            // Completed + not due => certified, problems #2.
            array('certified not due problems #2',
                array('status' => CERTIFSTATUS_COMPLETED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_NOTDUE,
                    'certifpath' => CERTIFPATH_RECERT,
                    'timecompleted' => -1, // 2: Should be > 0.
                    'timewindowopens' => -1, // 3: Should be > 0.
                    'timeexpires' => -1, // 4: Should be > 0.
                    'baselinetimeexpires' => -1), // 5: Should be > 0.
                array('status' => STATUS_PROGRAM_COMPLETE,
                    'timecompleted' => -1, // 6: Should be > 0.
                    'timedue' => COMPLETION_TIME_NOT_SET), // 7: Should be > 0.
                array('error:statecertified-timecompletedempty' => 'timecompleted', // 2.
                    'error:statecertified-timewindowopensempty' => 'timewindowopens', // 3.
                    'error:statecertified-timeexpiresempty' => 'timeexpires', // 4.
                    'error:statecertified-baselinetimeexpiresempty' => 'baselinetimeexpires', // 5.
                    'error:statecertified-progtimecompletedempty' => 'progtimecompleted', // 6.
                    'error:statecertified-timedueempty' => 'timedue') // 7.
            ),
            // Completed + not due => certified, problems #3.
            array('certified not due problems #3',
                array('status' => CERTIFSTATUS_COMPLETED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_NOTDUE,
                    'certifpath' => CERTIFPATH_RECERT,
                    'timecompleted' => 1003,
                    'timewindowopens' => 1002, // 1: Should be >= timecompleted.
                    'timeexpires' => 1001, // 2: Should be >= timewindowopens.
                    'baselinetimeexpires' => 1001), // 3: Should be >= timewindowopens.
                array('status' => STATUS_PROGRAM_COMPLETE,
                    'timecompleted' => 1006000, // 4: TL-8341: Should be equal to cert timecompleted.
                    'timedue' => 1004), // 5: Should be equal to cert timeexpires.
                array('error:statecertified-timewindowopenstimecompletednotordered' => 'timewindowopens', // 1.
                    'error:statecertified-timeexpirestimewindowopensnotordered' => 'timeexpires', // 2.
                    'error:statecertified-baselinetimeexpirestimewindowopensnotordered' => 'baselinetimeexpires', // 3.
                    'error:statecertified-certprogtimecompleteddifferent' => 'progtimecompleted', // 4.
                    'error:statecertified-timeexpirestimeduedifferent' => 'timedue') // 5.
            ),
            // Completed + not due => certified, no problems.
            array('certified not due no problems',
                array('status' => CERTIFSTATUS_COMPLETED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_NOTDUE,
                    'certifpath' => CERTIFPATH_RECERT,
                    'timecompleted' => 1001,
                    'timewindowopens' => 1002,
                    'timeexpires' => 1003,
                    'baselinetimeexpires' => 1003),
                array('status' => STATUS_PROGRAM_COMPLETE,
                    'timecompleted' => 1001,
                    'timedue' => 1003),
                array()
            ),
            // Completed + expired => certified, one big problem. This can never be valid.
            array('certified expired one big problem',
                array('status' => CERTIFSTATUS_COMPLETED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_EXPIRED, // 1: Cannot be expired when certified.
                    'certifpath' => CERTIFPATH_RECERT,
                    'timecompleted' => 1001,
                    'timewindowopens' => 1002,
                    'timeexpires' => 1003,
                    'baselinetimeexpires' => 1003),
                array('status' => STATUS_PROGRAM_COMPLETE,
                    'timecompleted' => 1001,
                    'timedue' => 1003),
                array('error:statecertified-renewalstatusincorrect' => 'renewalstatus') // 1.
            ),
            // Completed + due => window open, problems #1.
            array('certified due problems #1',
                array('status' => CERTIFSTATUS_COMPLETED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_DUE,
                    'certifpath' => CERTIFPATH_CERT, // 1: Should be CERTIFPATH_RECERT.
                    'timecompleted' => 0, // 3: Should be > 0.
                    'timewindowopens' => 0, // 4: Should be > 0.
                    'timeexpires' => 0, // 5: Should be > 0.
                    'baselinetimeexpires' => 0), // 6: Should be > 0.
                array('status' => STATUS_PROGRAM_COMPLETE, // 7: Should be STATUS_PROGRAM_INCOMPLETE.
                    'timecompleted' => 1001, // 8: Should be 0.
                    'timedue' => 0), // 9: Should be > 0.
                array('error:statewindowopen-pathincorrect' => 'certifpath', // 1.
                    'error:statewindowopen-timecompletedempty' => 'timecompleted', // 3.
                    'error:statewindowopen-timewindowopensempty' => 'timewindowopens', // 4.
                    'error:statewindowopen-timeexpiresempty' => 'timeexpires', // 5.
                    'error:statewindowopen-baselinetimeexpiresempty' => 'baselinetimeexpires', // 6.
                    'error:statewindowopen-progstatusincorrect' => 'progstatus', // 7.
                    'error:statewindowopen-progtimecompletednotempty' => 'progtimecompleted', // 8.
                    'error:statewindowopen-timedueempty' => 'timedue') // 9.
            ),
            // Completed + due => window open, problems #2.
            array('certified due problems #2',
                array('status' => CERTIFSTATUS_COMPLETED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_DUE,
                    'certifpath' => CERTIFPATH_RECERT,
                    'timecompleted' => -1, // 2: Should be > 0.
                    'timewindowopens' => -1, // 3: Should be > 0.
                    'timeexpires' => -1, // 4: Should be > 0.
                    'baselinetimeexpires' => -1), // 5: Should be > 0.
                array('status' => STATUS_PROGRAM_INCOMPLETE,
                    'timecompleted' => -1, // 6: Should be 0.
                    'timedue' => -1), // 8: Should be > 0.
                array('error:statewindowopen-timecompletedempty' => 'timecompleted', // 2.
                    'error:statewindowopen-timewindowopensempty' => 'timewindowopens', // 3.
                    'error:statewindowopen-timeexpiresempty' => 'timeexpires', // 4.
                    'error:statewindowopen-baselinetimeexpiresempty' => 'baselinetimeexpires', // 5.
                    'error:statewindowopen-progtimecompletednotempty' => 'progtimecompleted', // 6.
                    'error:statewindowopen-timedueempty' => 'timedue') // 8.
            ),
            // Completed + due => window open, problems #3.
            array('certified due problems #3',
                array('status' => CERTIFSTATUS_COMPLETED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_DUE,
                    'certifpath' => CERTIFPATH_RECERT,
                    'timecompleted' => 1003,
                    'timewindowopens' => 1002, // 1: Should be >= timecompleted.
                    'timeexpires' => 1001, // 2: Should be >= timewindowopens.
                    'baselinetimeexpires' => 1001), // 3: Should be >= timewindowopens.
                array('status' => STATUS_PROGRAM_INCOMPLETE,
                    'timecompleted' => 0,
                    'timedue' => 1004), // 4: Should be same as timeexpires.
                array('error:statewindowopen-timewindowopenstimecompletednotordered' => 'timewindowopens', // 1.
                    'error:statewindowopen-timeexpirestimewindowopensnotordered' => 'timeexpires', // 2.
                    'error:statewindowopen-baselinetimeexpirestimewindowopensnotordered' => 'baselinetimeexpires', // 3.
                    'error:statewindowopen-timeexpirestimeduedifferent' => 'timedue') // 4.
            ),
            // Completed + due => window open, no problems.
            array('certified due no problems',
                array('status' => CERTIFSTATUS_COMPLETED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_DUE,
                    'certifpath' => CERTIFPATH_RECERT,
                    'timecompleted' => 1001,
                    'timewindowopens' => 1002,
                    'timeexpires' => 1003,
                    'baselinetimeexpires' => 1003),
                array('status' => STATUS_PROGRAM_INCOMPLETE,
                    'timecompleted' => 0,
                    'timedue' => 1003),
                array()
            ),
            // In progress + due => window open, problems #1.
            array('in progress due problems #1',
                array('status' => CERTIFSTATUS_INPROGRESS,
                    'renewalstatus' => CERTIFRENEWALSTATUS_DUE,
                    'certifpath' => CERTIFPATH_CERT, // 1: Should be CERTIFPATH_RECERT.
                    'timecompleted' => 0, // 3: Should be > 0.
                    'timewindowopens' => 0, // 4: Should be > 0.
                    'timeexpires' => 0, // 5: Should be > 0.
                    'baselinetimeexpires' => 0), // 6: Should be > 0.
                array('status' => STATUS_PROGRAM_COMPLETE, // 7: Should be STATUS_PROGRAM_INCOMPLETE.
                    'timecompleted' => 1001, // 8: Should be 0.
                    'timedue' => 0), // 9: Should be > 0.
                array('error:statewindowopen-pathincorrect' => 'certifpath', // 1.
                    'error:statewindowopen-timecompletedempty' => 'timecompleted', // 3.
                    'error:statewindowopen-timewindowopensempty' => 'timewindowopens', // 4.
                    'error:statewindowopen-timeexpiresempty' => 'timeexpires', // 5.
                    'error:statewindowopen-baselinetimeexpiresempty' => 'baselinetimeexpires', // 6.
                    'error:statewindowopen-progstatusincorrect' => 'progstatus', // 7.
                    'error:statewindowopen-progtimecompletednotempty' => 'progtimecompleted', // 8.
                    'error:statewindowopen-timedueempty' => 'timedue') // 9.
            ),
            // In progress + due => window open, problems #2.
            array('in progress due problems #2',
                array('status' => CERTIFSTATUS_INPROGRESS,
                    'renewalstatus' => CERTIFRENEWALSTATUS_DUE,
                    'certifpath' => CERTIFPATH_RECERT,
                    'timecompleted' => -1, // 2: Should be > 0.
                    'timewindowopens' => -1, // 3: Should be > 0.
                    'timeexpires' => -1, // 4: Should be > 0.
                    'baselinetimeexpires' => -1), // 5: Should be > 0.
                array('status' => STATUS_PROGRAM_INCOMPLETE,
                    'timecompleted' => -1, // 6: Should be 0.
                    'timedue' => -1), // 8: Should be > 0.
                array('error:statewindowopen-timecompletedempty' => 'timecompleted', // 2.
                    'error:statewindowopen-timewindowopensempty' => 'timewindowopens', // 3.
                    'error:statewindowopen-timeexpiresempty' => 'timeexpires', // 4.
                    'error:statewindowopen-baselinetimeexpiresempty' => 'baselinetimeexpires', // 5.
                    'error:statewindowopen-progtimecompletednotempty' => 'progtimecompleted', // 6.
                    'error:statewindowopen-timedueempty' => 'timedue') // 8.
            ),
            // In progress + due => window open, problems #3.
            array('in progress due problems #3',
                array('status' => CERTIFSTATUS_INPROGRESS,
                    'renewalstatus' => CERTIFRENEWALSTATUS_DUE,
                    'certifpath' => CERTIFPATH_RECERT,
                    'timecompleted' => 1003,
                    'timewindowopens' => 1002, // 1: Should be >= timecompleted.
                    'timeexpires' => 1001, // 2: Should be >= timewindowopens.
                    'baselinetimeexpires' => 1001), // 3: Should be >= timewindowopens.
                array('status' => STATUS_PROGRAM_INCOMPLETE,
                    'timecompleted' => 0,
                    'timedue' => 1004), // 4: Should be same as timeexpires.
                array('error:statewindowopen-timewindowopenstimecompletednotordered' => 'timewindowopens', // 1.
                    'error:statewindowopen-timeexpirestimewindowopensnotordered' => 'timeexpires', // 2.
                    'error:statewindowopen-baselinetimeexpirestimewindowopensnotordered' => 'baselinetimeexpires', // 2.
                    'error:statewindowopen-timeexpirestimeduedifferent' => 'timedue') // 4.
            ),
            // In progress + due => window open, no problems.
            array('in progress due no problems',
                array('status' => CERTIFSTATUS_INPROGRESS,
                    'renewalstatus' => CERTIFRENEWALSTATUS_DUE,
                    'certifpath' => CERTIFPATH_RECERT,
                    'timecompleted' => 1001,
                    'timewindowopens' => 1002,
                    'timeexpires' => 1003,
                    'baselinetimeexpires' => 1003),
                array('status' => STATUS_PROGRAM_INCOMPLETE,
                    'timecompleted' => 0,
                    'timedue' => 1003),
                array()
            ),
            // Expired, problems #1.
            array('expired problems #1',
                array('status' => CERTIFSTATUS_EXPIRED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_DUE, // 1: Should be CERTIFRENEWALSTATUS_EXPIRED.
                    'certifpath' => CERTIFPATH_RECERT, // 2: Should be CERTIFPATH_CERT.
                    'timecompleted' => 1001, // 3: Should be 0.
                    'timewindowopens' => 1002, // 4: Should be 0.
                    'timeexpires' => 1003, // 5: Should be 0.
                    'baselinetimeexpires' => 1003), // 6: Should be 0.
                array('status' => STATUS_PROGRAM_COMPLETE, // 7: Should be STATUS_PROGRAM_INCOMPLETE.
                    'timecompleted' => 1004, // 8: Should be 0.
                    'timedue' => COMPLETION_TIME_UNKNOWN), // 9: Should be > 0.
                array('error:stateexpired-renewalstatusincorrect' => 'renewalstatus', // 1.
                    'error:stateexpired-pathincorrect' => 'certifpath', // 2.
                    'error:stateexpired-timecompletednotempty' => 'timecompleted', // 3.
                    'error:stateexpired-timewindowopensnotempty' => 'timewindowopens', // 4.
                    'error:stateexpired-timeexpiresnotempty' => 'timeexpires', // 5.
                    'error:stateexpired-baselinetimeexpiresnotempty' => 'baselinetimeexpires', // 6.
                    'error:stateexpired-progstatusincorrect' => 'progstatus', // 7.
                    'error:stateexpired-progtimecompletednotempty' => 'progtimecompleted', // 8.
                    'error:stateexpired-timedueempty' => 'timedue') // 9.
            ),
            // Expired, problems #2.
            array('expired problems #2',
                array('status' => CERTIFSTATUS_EXPIRED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_NOTDUE, // 1: Should be CERTIFRENEWALSTATUS_EXPIRED.
                    'certifpath' => CERTIFPATH_CERT,
                    'timecompleted' => -1, // 2: Should be 0.
                    'timewindowopens' => -1, // 3: Should be 0.
                    'timeexpires' => -1, // 4: Should be 0.
                    'baselinetimeexpires' => -1), // 5: Should be 0.
                array('status' => STATUS_PROGRAM_INCOMPLETE,
                    'timecompleted' => -1, // 6: Should be 0.
                    'timedue' => COMPLETION_TIME_NOT_SET), // 8: Should be > 0.
                array('error:stateexpired-renewalstatusincorrect' => 'renewalstatus', // 1.
                    'error:stateexpired-timecompletednotempty' => 'timecompleted', // 2.
                    'error:stateexpired-timewindowopensnotempty' => 'timewindowopens', // 3.
                    'error:stateexpired-timeexpiresnotempty' => 'timeexpires', // 4.
                    'error:stateexpired-baselinetimeexpiresnotempty' => 'baselinetimeexpires', // 5.
                    'error:stateexpired-progtimecompletednotempty' => 'progtimecompleted', // 6.
                    'error:stateexpired-timedueempty' => 'timedue') // 8.
            ),
            // Expired, no problems.
            array('expired no problems',
                array('status' => CERTIFSTATUS_EXPIRED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_EXPIRED,
                    'certifpath' => CERTIFPATH_CERT,
                    'timecompleted' => 0,
                    'timewindowopens' => 0,
                    'timeexpires' => 0,
                    'baselinetimeexpires' => 0),
                array('status' => STATUS_PROGRAM_INCOMPLETE,
                    'timecompleted' => 0,
                    'timedue' => 1001),
                array()
            ),
            // In progress + expired => expired, problems #1.
            array('expired problems #1',
                array('status' => CERTIFSTATUS_INPROGRESS,
                    'renewalstatus' => CERTIFRENEWALSTATUS_EXPIRED,
                    'certifpath' => CERTIFPATH_RECERT, // 2: Should be CERTIFPATH_CERT.
                    'timecompleted' => 1001, // 3: Should be 0.
                    'timewindowopens' => 1002, // 4: Should be 0.
                    'timeexpires' => 1003, // 5: Should be 0.
                    'baselinetimeexpires' => 1003), // 6: Should be 0.
                array('status' => STATUS_PROGRAM_COMPLETE, // 7: Should be STATUS_PROGRAM_INCOMPLETE.
                    'timecompleted' => 1004, // 8: Should be 0.
                    'timedue' => COMPLETION_TIME_UNKNOWN), // 9: Should be > 0.
                array('error:stateexpired-pathincorrect' => 'certifpath', // 2.
                    'error:stateexpired-timecompletednotempty' => 'timecompleted', // 3.
                    'error:stateexpired-timewindowopensnotempty' => 'timewindowopens', // 4.
                    'error:stateexpired-timeexpiresnotempty' => 'timeexpires', // 5.
                    'error:stateexpired-baselinetimeexpiresnotempty' => 'baselinetimeexpires', // 6.
                    'error:stateexpired-progstatusincorrect' => 'progstatus', // 7.
                    'error:stateexpired-progtimecompletednotempty' => 'progtimecompleted', // 8.
                    'error:stateexpired-timedueempty' => 'timedue') // 9.
            ),
            // In progress + expired => expired, problems #2.
            array('expired problems #2',
                array('status' => CERTIFSTATUS_INPROGRESS,
                    'renewalstatus' => CERTIFRENEWALSTATUS_EXPIRED,
                    'certifpath' => CERTIFPATH_CERT,
                    'timecompleted' => -1, // 2: Should be 0.
                    'timewindowopens' => -1, // 3: Should be 0.
                    'timeexpires' => -1, // 4: Should be 0.
                    'baselinetimeexpires' => -1), // 5: Should be 0.
                array('status' => STATUS_PROGRAM_INCOMPLETE,
                    'timecompleted' => -1, // 6: Should be 0.
                    'timedue' => COMPLETION_TIME_NOT_SET), // 8: Should be > 0.
                array('error:stateexpired-timecompletednotempty' => 'timecompleted', // 2.
                    'error:stateexpired-timewindowopensnotempty' => 'timewindowopens', // 3.
                    'error:stateexpired-timeexpiresnotempty' => 'timeexpires', // 4.
                    'error:stateexpired-baselinetimeexpiresnotempty' => 'baselinetimeexpires', // 5.
                    'error:stateexpired-progtimecompletednotempty' => 'progtimecompleted', // 6.
                    'error:stateexpired-timedueempty' => 'timedue') // 8.
            ),
            // In progress + expired => expired, no problems.
            array('expired no problems',
                array('status' => CERTIFSTATUS_INPROGRESS,
                    'renewalstatus' => CERTIFRENEWALSTATUS_EXPIRED,
                    'certifpath' => CERTIFPATH_CERT,
                    'timecompleted' => 0,
                    'timewindowopens' => 0,
                    'timeexpires' => 0,
                    'baselinetimeexpires' => 0),
                array('status' => STATUS_PROGRAM_INCOMPLETE,
                    'timecompleted' => 0,
                    'timedue' => 1001),
                array()
            ),
        );
    }

    /**
     * Test certif_get_completion_state with current completion records (includes prog completion).
     *
     * @dataProvider data_certif_get_completion_errors_for_current
     */
    public function test_certif_get_completion_errors_for_current($debugkey, $certcompletion, $progcompletion, $expectederrors) {
        $errors = certif_get_completion_errors((object)$certcompletion, (object)$progcompletion);
        $this->assertEquals($expectederrors, $errors, $debugkey);
    }

    /**
     * Data provider for test_certif_get_completion_errors_for_history.
     */
    public function data_certif_get_completion_errors_for_history() {
        return array(
            // Cert status isn't set. We only set the fields that should be used (to check what shouldn't be used isn't used).
            array('cert status not set',
                array('id' => 0,
                    'certifid' => 0,
                    'userid' => 0,
                    'status' => CERTIFSTATUS_UNSET,
                    'timecompleted' => 0,
                    'timeexpires' => 0,
                    'baselinetimeexpires' => 0),
                array('error:completionstatusunset' => 'state')
            ),
            // Assigned, problems #1.
            array('assigned problems #1',
                array('id' => 0,
                    'certifid' => 0,
                    'userid' => 0,
                    'status' => CERTIFSTATUS_ASSIGNED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_DUE, // 1: Should be CERTIFRENEWALSTATUS_NOTDUE.
                    'certifpath' => CERTIFPATH_RECERT, // 2: Should be CERTIFPATH_CERT.
                    'timecompleted' => 1001, // 3: Should be 0.
                    'timewindowopens' => 1002, // 4: Should be 0.
                    'timeexpires' => 1003, // 5: Should be 0.
                    'baselinetimeexpires' => 1003), // 6: Should be 0.
                array('error:stateassigned-renewalstatusincorrect' => 'renewalstatus', // 1.
                    'error:stateassigned-pathincorrect' => 'certifpath', // 2.
                    'error:stateassigned-timecompletednotempty' => 'timecompleted', // 3.
                    'error:stateassigned-timewindowopensnotempty' => 'timewindowopens', // 4.
                    'error:stateassigned-timeexpiresnotempty' => 'timeexpires', // 5.
                    'error:stateassigned-baselinetimeexpiresnotempty' => 'baselinetimeexpires') // 6.
            ),
            // Assigned, problems #2.
            array('assigned problems #2',
                array('id' => 0,
                    'certifid' => 0,
                    'userid' => 0,
                    'status' => CERTIFSTATUS_ASSIGNED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_EXPIRED, // 1: Should be CERTIFRENEWALSTATUS_NOTDUE.
                    'certifpath' => CERTIFPATH_CERT,
                    'timecompleted' => -1, // 2: Should be 0.
                    'timewindowopens' => -1, // 3: Should be 0.
                    'timeexpires' => -1, // 4: Should be 0.
                    'baselinetimeexpires' => -1), // 5: Should be 0.
                array('error:stateassigned-renewalstatusincorrect' => 'renewalstatus', // 1.
                    'error:stateassigned-timecompletednotempty' => 'timecompleted', // 2.
                    'error:stateassigned-timewindowopensnotempty' => 'timewindowopens', // 3.
                    'error:stateassigned-timeexpiresnotempty' => 'timeexpires', // 4.
                    'error:stateassigned-baselinetimeexpiresnotempty' => 'baselinetimeexpires') // 5.
            ),
            // Assigned, no problems.
            array('assigned no problems',
                array('id' => 0,
                    'certifid' => 0,
                    'userid' => 0,
                    'status' => CERTIFSTATUS_ASSIGNED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_NOTDUE,
                    'certifpath' => CERTIFPATH_CERT,
                    'timecompleted' => 0,
                    'timewindowopens' => 0,
                    'timeexpires' => 0,
                    'baselinetimeexpires' => 0),
                array()
            ),
            // In progress + not due => assigned, problems #1.
            array('in progress not due problems #1',
                array('id' => 0,
                    'certifid' => 0,
                    'userid' => 0,
                    'status' => CERTIFSTATUS_INPROGRESS,
                    'renewalstatus' => CERTIFRENEWALSTATUS_NOTDUE,
                    'certifpath' => CERTIFPATH_RECERT, // 1: Should be CERTIFPATH_CERT.
                    'timecompleted' => 1001, // 3: Should be 0.
                    'timewindowopens' => 1002, // 4: Should be 0.
                    'timeexpires' => 1003, // 5: Should be 0.
                    'baselinetimeexpires' => 1003), // 6: Should be 0.
                array('error:stateassigned-pathincorrect' => 'certifpath', // 1.
                    'error:stateassigned-timecompletednotempty' => 'timecompleted', // 3.
                    'error:stateassigned-timewindowopensnotempty' => 'timewindowopens', // 4.
                    'error:stateassigned-timeexpiresnotempty' => 'timeexpires', // 5.
                    'error:stateassigned-baselinetimeexpiresnotempty' => 'baselinetimeexpires') // 6.
            ),
            // In progress + not due => assigned, problems #2.
            array('in progress not due problems #2',
                array('id' => 0,
                    'certifid' => 0,
                    'userid' => 0,
                    'status' => CERTIFSTATUS_INPROGRESS,
                    'renewalstatus' => CERTIFRENEWALSTATUS_NOTDUE,
                    'certifpath' => CERTIFPATH_CERT,
                    'timecompleted' => -1, // 2: Should be 0.
                    'timewindowopens' => -1, // 3: Should be 0.
                    'timeexpires' => -1, // 4: Should be 0.
                    'baselinetimeexpires' => -1), // 5: Should be 0.
                array('error:stateassigned-timecompletednotempty' => 'timecompleted', // 2.
                    'error:stateassigned-timewindowopensnotempty' => 'timewindowopens', // 3.
                    'error:stateassigned-timeexpiresnotempty' => 'timeexpires', // 4.
                    'error:stateassigned-baselinetimeexpiresnotempty' => 'baselinetimeexpires') // 5.
            ),
            // In progress + not due => assigned, no problems.
            array('in progress not due no problems',
                array('id' => 0,
                    'certifid' => 0,
                    'userid' => 0,
                    'status' => CERTIFSTATUS_INPROGRESS,
                    'renewalstatus' => CERTIFRENEWALSTATUS_NOTDUE,
                    'certifpath' => CERTIFPATH_CERT,
                    'timecompleted' => 0,
                    'timewindowopens' => 0,
                    'timeexpires' => 0,
                    'baselinetimeexpires' => 0),
                array()
            ),
            // Completed + not due => certified, problems #1.
            array('certified not due problems #1',
                array('id' => 0,
                    'certifid' => 0,
                    'userid' => 0,
                    'status' => CERTIFSTATUS_COMPLETED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_NOTDUE,
                    'certifpath' => CERTIFPATH_CERT, // 1: Should be CERTIFPATH_RECERT.
                    'timecompleted' => 0, // 3: Should be > 0.
                    'timewindowopens' => 0, // 4: Should be > 0.
                    'timeexpires' => 0, // 5: Should be > 0.
                    'baselinetimeexpires' => 0), // 6: Should be > 0.
                array('error:statecertified-pathincorrect' => 'certifpath', // 1.
                    'error:statecertified-timecompletedempty' => 'timecompleted', // 3.
                    'error:statecertified-timewindowopensempty' => 'timewindowopens', // 4.
                    'error:statecertified-timeexpiresempty' => 'timeexpires', // 5.
                    'error:statecertified-baselinetimeexpiresempty' => 'baselinetimeexpires') // 6.
            ),
            // Completed + not due => certified, problems #2.
            array('certified not due problems #2',
                array('id' => 0,
                    'certifid' => 0,
                    'userid' => 0,
                    'status' => CERTIFSTATUS_COMPLETED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_NOTDUE,
                    'certifpath' => CERTIFPATH_RECERT,
                    'timecompleted' => -1, // 2: Should be > 0.
                    'timewindowopens' => -1, // 3: Should be > 0.
                    'timeexpires' => -1, // 4: Should be > 0.
                    'baselinetimeexpires' => -1), // 4: Should be > 0.
                array('error:statecertified-timecompletedempty' => 'timecompleted', // 2.
                    'error:statecertified-timewindowopensempty' => 'timewindowopens', // 3.
                    'error:statecertified-timeexpiresempty' => 'timeexpires', // 4.
                    'error:statecertified-baselinetimeexpiresempty' => 'baselinetimeexpires') // 4.
            ),
            // Completed + not due => certified, problems #3.
            array('certified not due problems #3',
                array('id' => 0,
                    'certifid' => 0,
                    'userid' => 0,
                    'status' => CERTIFSTATUS_COMPLETED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_NOTDUE,
                    'certifpath' => CERTIFPATH_RECERT,
                    'timecompleted' => 1003,
                    'timewindowopens' => 1002, // 1: Should be >= timecompleted.
                    'timeexpires' => 1001, // 2: Should be >= timewindowopens.
                    'baselinetimeexpires' => 1001), // 3: Should be >= timewindowopens.
                array('error:statecertified-timewindowopenstimecompletednotordered' => 'timewindowopens', // 1.
                    'error:statecertified-timeexpirestimewindowopensnotordered' => 'timeexpires', // 2.
                    'error:statecertified-baselinetimeexpirestimewindowopensnotordered' => 'baselinetimeexpires') // 3.
            ),
            // Completed + not due => certified, no problems.
            array('certified not due no problems',
                array('id' => 0,
                    'certifid' => 0,
                    'userid' => 0,
                    'status' => CERTIFSTATUS_COMPLETED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_NOTDUE,
                    'certifpath' => CERTIFPATH_RECERT,
                    'timecompleted' => 1001,
                    'timewindowopens' => 1002,
                    'timeexpires' => 1003,
                    'baselinetimeexpires' => 1003),
                array()
            ),
            // Completed + expired => certified, one big problem. This can never be valid.
            array('certified expired one big problem',
                array('id' => 0,
                    'certifid' => 0,
                    'userid' => 0,
                    'status' => CERTIFSTATUS_COMPLETED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_EXPIRED, // 1: Cannot be expired when certified.
                    'certifpath' => CERTIFPATH_RECERT,
                    'timecompleted' => 1001,
                    'timewindowopens' => 1002,
                    'timeexpires' => 1003,
                    'baselinetimeexpires' => 1003),
                array('error:statecertified-renewalstatusincorrect' => 'renewalstatus') // 1.
            ),
            // Completed + due => window open, problems #1.
            array('certified due problems #1',
                array('id' => 0,
                    'certifid' => 0,
                    'userid' => 0,
                    'status' => CERTIFSTATUS_COMPLETED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_DUE,
                    'certifpath' => CERTIFPATH_CERT, // 1: Should be CERTIFPATH_RECERT.
                    'timecompleted' => 0, // 3: Should be > 0.
                    'timewindowopens' => 0, // 4: Should be > 0.
                    'timeexpires' => 0, // 5: Should be > 0.
                    'baselinetimeexpires' => 0), // 6: Should be > 0.
                array('error:statewindowopen-pathincorrect' => 'certifpath', // 1.
                    'error:statewindowopen-timecompletedempty' => 'timecompleted', // 3.
                    'error:statewindowopen-timewindowopensempty' => 'timewindowopens', // 4.
                    'error:statewindowopen-timeexpiresempty' => 'timeexpires', // 5.
                    'error:statewindowopen-baselinetimeexpiresempty' => 'baselinetimeexpires') // 6.
            ),
            // Completed + due => window open, problems #2.
            array('certified due problems #2',
                array('id' => 0,
                    'certifid' => 0,
                    'userid' => 0,
                    'status' => CERTIFSTATUS_COMPLETED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_DUE,
                    'certifpath' => CERTIFPATH_RECERT,
                    'timecompleted' => -1, // 2: Should be > 0.
                    'timewindowopens' => -1, // 3: Should be > 0.
                    'timeexpires' => -1, // 4: Should be > 0.
                    'baselinetimeexpires' => -1), // 5: Should be > 0.
                array('error:statewindowopen-timecompletedempty' => 'timecompleted', // 2.
                    'error:statewindowopen-timewindowopensempty' => 'timewindowopens', // 3.
                    'error:statewindowopen-timeexpiresempty' => 'timeexpires', // 4.
                    'error:statewindowopen-baselinetimeexpiresempty' => 'baselinetimeexpires') // 5.
            ),
            // Completed + due => window open, problems #3.
            array('certified due problems #3',
                array('id' => 0,
                    'certifid' => 0,
                    'userid' => 0,
                    'status' => CERTIFSTATUS_COMPLETED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_DUE,
                    'certifpath' => CERTIFPATH_RECERT,
                    'timecompleted' => 1003,
                    'timewindowopens' => 1002, // 1: Should be >= timecompleted.
                    'timeexpires' => 1001, // 2: Should be >= timewindowopens.
                    'baselinetimeexpires' => 1001), // 3: Should be >= timewindowopens.
                array('error:statewindowopen-timewindowopenstimecompletednotordered' => 'timewindowopens', // 1.
                    'error:statewindowopen-timeexpirestimewindowopensnotordered' => 'timeexpires', // 2.
                    'error:statewindowopen-baselinetimeexpirestimewindowopensnotordered' => 'baselinetimeexpires') // 3.
            ),
            // Completed + due => window open, no problems.
            array('certified due no problems',
                array('id' => 0,
                    'certifid' => 0,
                    'userid' => 0,
                    'status' => CERTIFSTATUS_COMPLETED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_DUE,
                    'certifpath' => CERTIFPATH_RECERT,
                    'timecompleted' => 1001,
                    'timewindowopens' => 1002,
                    'timeexpires' => 1003,
                    'baselinetimeexpires' => 1003),
                array()
            ),
            // In progress + due => window open, problems #1.
            array('in progress due problems #1',
                array('id' => 0,
                    'certifid' => 0,
                    'userid' => 0,
                    'status' => CERTIFSTATUS_INPROGRESS,
                    'renewalstatus' => CERTIFRENEWALSTATUS_DUE,
                    'certifpath' => CERTIFPATH_CERT, // 1: Should be CERTIFPATH_RECERT.
                    'timecompleted' => 0, // 3: Should be > 0.
                    'timewindowopens' => 0, // 4: Should be > 0.
                    'timeexpires' => 0, // 5: Should be > 0.
                    'baselinetimeexpires' => 0), // 6: Should be > 0.
                array('error:statewindowopen-pathincorrect' => 'certifpath', // 1.
                    'error:statewindowopen-timecompletedempty' => 'timecompleted', // 3.
                    'error:statewindowopen-timewindowopensempty' => 'timewindowopens', // 4.
                    'error:statewindowopen-timeexpiresempty' => 'timeexpires', // 5.
                    'error:statewindowopen-baselinetimeexpiresempty' => 'baselinetimeexpires') // 6.
            ),
            // In progress + due => window open, problems #2.
            array('in progress due problems #2',
                array('id' => 0,
                    'certifid' => 0,
                    'userid' => 0,
                    'status' => CERTIFSTATUS_INPROGRESS,
                    'renewalstatus' => CERTIFRENEWALSTATUS_DUE,
                    'certifpath' => CERTIFPATH_RECERT,
                    'timecompleted' => -1, // 2: Should be > 0.
                    'timewindowopens' => -1, // 3: Should be > 0.
                    'timeexpires' => -1, // 4: Should be > 0.
                    'baselinetimeexpires' => -1), // 5: Should be > 0.
                array('error:statewindowopen-timecompletedempty' => 'timecompleted', // 2.
                    'error:statewindowopen-timewindowopensempty' => 'timewindowopens', // 3.
                    'error:statewindowopen-timeexpiresempty' => 'timeexpires', // 4.
                    'error:statewindowopen-baselinetimeexpiresempty' => 'baselinetimeexpires') // 5.
            ),
            // In progress + due => window open, problems #3.
            array('in progress due problems #3',
                array('id' => 0,
                    'certifid' => 0,
                    'userid' => 0,
                    'status' => CERTIFSTATUS_INPROGRESS,
                    'renewalstatus' => CERTIFRENEWALSTATUS_DUE,
                    'certifpath' => CERTIFPATH_RECERT,
                    'timecompleted' => 1003,
                    'timewindowopens' => 1002, // 1: Should be >= timecompleted.
                    'timeexpires' => 1001, // 2: Should be >= timewindowopens.
                    'baselinetimeexpires' => 1001), // 3: Should be >= timewindowopens.
                array('error:statewindowopen-timewindowopenstimecompletednotordered' => 'timewindowopens', // 1.
                    'error:statewindowopen-timeexpirestimewindowopensnotordered' => 'timeexpires', // 2.
                    'error:statewindowopen-baselinetimeexpirestimewindowopensnotordered' => 'baselinetimeexpires') // 3.
            ),
            // In progress + due => window open, no problems.
            array('in progress due no problems',
                array('id' => 0,
                    'certifid' => 0,
                    'userid' => 0,
                    'status' => CERTIFSTATUS_INPROGRESS,
                    'renewalstatus' => CERTIFRENEWALSTATUS_DUE,
                    'certifpath' => CERTIFPATH_RECERT,
                    'timecompleted' => 1001,
                    'timewindowopens' => 1002,
                    'timeexpires' => 1003,
                    'baselinetimeexpires' => 1003),
                array()
            ),
            // Expired, problems #1.
            array('expired problems #1',
                array('id' => 0,
                    'certifid' => 0,
                    'userid' => 0,
                    'status' => CERTIFSTATUS_EXPIRED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_DUE, // 1: Should be CERTIFRENEWALSTATUS_EXPIRED.
                    'certifpath' => CERTIFPATH_RECERT, // 2: Should be CERTIFPATH_CERT.
                    'timecompleted' => 1001, // 3: Should be 0.
                    'timewindowopens' => 1002, // 4: Should be 0.
                    'timeexpires' => 1003, // 5: Should be 0.
                    'baselinetimeexpires' => 1003), // 6: Should be 0.
                array('error:stateexpired-renewalstatusincorrect' => 'renewalstatus', // 1.
                    'error:stateexpired-pathincorrect' => 'certifpath', // 2.
                    'error:stateexpired-timecompletednotempty' => 'timecompleted', // 3.
                    'error:stateexpired-timewindowopensnotempty' => 'timewindowopens', // 4.
                    'error:stateexpired-timeexpiresnotempty' => 'timeexpires', // 5.
                    'error:stateexpired-baselinetimeexpiresnotempty' => 'baselinetimeexpires') // 6.
            ),
            // Expired, problems #2.
            array('expired problems #2',
                array('id' => 0,
                    'certifid' => 0,
                    'userid' => 0,
                    'status' => CERTIFSTATUS_EXPIRED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_NOTDUE, // 1: Should be CERTIFRENEWALSTATUS_EXPIRED.
                    'certifpath' => CERTIFPATH_CERT,
                    'timecompleted' => -1, // 2: Should be 0.
                    'timewindowopens' => -1, // 3: Should be 0.
                    'timeexpires' => -1, // 4: Should be 0.
                    'baselinetimeexpires' => -1), // 5: Should be 0.
                array('error:stateexpired-renewalstatusincorrect' => 'renewalstatus', // 1.
                    'error:stateexpired-timecompletednotempty' => 'timecompleted', // 2.
                    'error:stateexpired-timewindowopensnotempty' => 'timewindowopens', // 3.
                    'error:stateexpired-timeexpiresnotempty' => 'timeexpires', // 4.
                    'error:stateexpired-baselinetimeexpiresnotempty' => 'baselinetimeexpires') // 5.
            ),
            // Expired, no problems.
            array('expired no problems',
                array('id' => 0,
                    'certifid' => 0,
                    'userid' => 0,
                    'status' => CERTIFSTATUS_EXPIRED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_EXPIRED,
                    'certifpath' => CERTIFPATH_CERT,
                    'timecompleted' => 0,
                    'timewindowopens' => 0,
                    'timeexpires' => 0,
                    'baselinetimeexpires' => 0),
                array()
            ),
            // In progress + expired => expired, problems #1.
            array('expired problems #1',
                array('id' => 0,
                    'certifid' => 0,
                    'userid' => 0,
                    'status' => CERTIFSTATUS_INPROGRESS,
                    'renewalstatus' => CERTIFRENEWALSTATUS_EXPIRED,
                    'certifpath' => CERTIFPATH_RECERT, // 2: Should be CERTIFPATH_CERT.
                    'timecompleted' => 1001, // 3: Should be 0.
                    'timewindowopens' => 1002, // 4: Should be 0.
                    'timeexpires' => 1003, // 5: Should be 0.
                    'baselinetimeexpires' => 1003), // 6: Should be 0.
                array('error:stateexpired-pathincorrect' => 'certifpath', // 2.
                    'error:stateexpired-timecompletednotempty' => 'timecompleted', // 3.
                    'error:stateexpired-timewindowopensnotempty' => 'timewindowopens', // 4.
                    'error:stateexpired-timeexpiresnotempty' => 'timeexpires', // 5.
                    'error:stateexpired-baselinetimeexpiresnotempty' => 'baselinetimeexpires') // 6.
            ),
            // In progress + expired => expired, problems #2.
            array('expired problems #2',
                array('id' => 0,
                    'certifid' => 0,
                    'userid' => 0,
                    'status' => CERTIFSTATUS_INPROGRESS,
                    'renewalstatus' => CERTIFRENEWALSTATUS_EXPIRED,
                    'certifpath' => CERTIFPATH_CERT,
                    'timecompleted' => -1, // 2: Should be 0.
                    'timewindowopens' => -1, // 3: Should be 0.
                    'timeexpires' => -1, // 4: Should be 0.
                    'baselinetimeexpires' => -1), // 4: Should be 0.
                array('error:stateexpired-timecompletednotempty' => 'timecompleted', // 2.
                    'error:stateexpired-timewindowopensnotempty' => 'timewindowopens', // 3.
                    'error:stateexpired-timeexpiresnotempty' => 'timeexpires', // 4.
                    'error:stateexpired-baselinetimeexpiresnotempty' => 'baselinetimeexpires') // 5.
            ),
            // In progress + expired => expired, no problems.
            array('expired no problems',
                array('id' => 0,
                    'certifid' => 0,
                    'userid' => 0,
                    'status' => CERTIFSTATUS_INPROGRESS,
                    'renewalstatus' => CERTIFRENEWALSTATUS_EXPIRED,
                    'certifpath' => CERTIFPATH_CERT,
                    'timecompleted' => 0,
                    'timewindowopens' => 0,
                    'timeexpires' => 0,
                    'baselinetimeexpires' => 0),
                array()
            ),
        );
    }

    /**
     * Test certif_get_completion_state with cert compl history records (no prog completion).
     *
     * @dataProvider data_certif_get_completion_errors_for_history
     */
    public function test_certif_get_completion_errors_for_history($debugkey, $certcompletion, $expectederrors) {
        $errors = certif_get_completion_errors((object)$certcompletion, null);
        $this->assertEquals($expectederrors, $errors, $debugkey);
    }

    /**
     * Test certif_get_completion_state with cert compl history records (no prog completion).
     * This test checks the unique expiry date constraint, which requires other existing records to test fully.
     */
    public function test_certif_get_completion_errors_for_history_expiry() {
        global $DB;

        // Set up some normal completion records.
        $now = time();
        $this->setup_completions();
        $this->shift_completions_to_certified($now);

        // Check that all records are ok.
        $certcompletions = $DB->get_records('certif_completion');
        foreach ($certcompletions as $certcompletion) {
            $sql = "SELECT pc.*
                      FROM {prog_completion} pc
                      JOIN {prog} prog ON prog.id = pc.programid
                     WHERE prog.certifid = :certifid AND pc.userid = :userid AND pc.coursesetid = 0";
            $params = array('certifid' => $certcompletion->certifid, 'userid' => $certcompletion->userid);
            $progcompletion = $DB->get_record_sql($sql, $params);
            $errors = certif_get_completion_errors($certcompletion, $progcompletion);
            $this->assertEquals(array(), $errors);
        }
        $this->assertEquals($this->numtestusers * $this->numtestcerts, count($certcompletions));

        $certwithhistory = $this->certifications[4];
        $certwithouthistory = $this->certifications[2];
        $user = $this->users[5];

        // Copy some current completion records into history.
        $certcompletions = $DB->get_records('certif_completion', array('certifid' => $certwithhistory->certifid));
        foreach ($certcompletions as $certcompletion) {
            copy_certif_completion_to_hist($certcompletion->certifid, $certcompletion->userid);
        }

        // Check that all history records are valid.
        $histcompletions = $DB->get_records('certif_completion_history');
        foreach ($histcompletions as $histcompletion) {
            $errors = certif_get_completion_errors($histcompletion, null);
            $this->assertEquals(array(), $errors);
        }
        $this->assertEquals($this->numtestusers, count($histcompletions));

        // Test 1 - New history record, no existing history record - no error.
        $completionhistory = array(
            'id' => 0,
            'certifid' => $certwithouthistory->certifid,
            'userid' => $user->id,
            'status' => CERTIFSTATUS_COMPLETED,
            'renewalstatus' => CERTIFRENEWALSTATUS_DUE,
            'certifpath' => CERTIFPATH_RECERT,
            'timecompleted' => 1001,
            'timewindowopens' => 1002,
            'timeexpires' => 1003,
            'baselinetimeexpires' => 1003);
        $errors = certif_get_completion_errors((object)$completionhistory, null);
        $this->assertEquals(array(), $errors);

        // Test 2 - New history record, existing record has different expiry - no error.
        $completionhistory = array(
            'id' => 0,
            'certifid' => $certwithhistory->certifid,
            'userid' => $user->id,
            'status' => CERTIFSTATUS_COMPLETED,
            'renewalstatus' => CERTIFRENEWALSTATUS_DUE,
            'certifpath' => CERTIFPATH_RECERT,
            'timecompleted' => 1001,
            'timewindowopens' => 1002,
            'timeexpires' => 1003, // Not the same.
            'baselinetimeexpires' => 1003);
        $errors = certif_get_completion_errors((object)$completionhistory, null);
        $this->assertEquals(array(), $errors);

        // Test 3 - New history record, existing record has same time completed and expiry - error.
        $completionhistory = array(
            'id' => 0,
            'certifid' => $certwithhistory->certifid,
            'userid' => $user->id,
            'status' => CERTIFSTATUS_COMPLETED,
            'renewalstatus' => CERTIFRENEWALSTATUS_DUE,
            'certifpath' => CERTIFPATH_RECERT,
            'timecompleted' => $now, // Note.
            'timewindowopens' => $now + 1000,
            'timeexpires' => $now + 2000, // As set in shift_completions_to_certified.
            'baselinetimeexpires' => $now + 2000); // As set in shift_completions_to_certified.
        $errors = certif_get_completion_errors((object)$completionhistory, null);
        $this->assertEquals(array('error:completionhistorydatesnotunique' => 'timecompleted'), $errors);

        // Test 4 - Update history record, change expiry - no error.
        $completionhistory = $DB->get_record('certif_completion_history',
            array('certifid' => $certwithhistory->certifid, 'userid' => $user->id));
        $completionhistory->timeexpires = $completionhistory->timeexpires + 12345;
        $errors = certif_get_completion_errors($completionhistory, null);
        $this->assertEquals(array(), $errors);

        // Test 5 - Update history record, don't change expiry (so same expiry) - no error (ignores the record being updated).
        $completionhistory = $DB->get_record('certif_completion_history',
            array('certifid' => $certwithhistory->certifid, 'userid' => $user->id));
        $completionhistory->timecompleted = $completionhistory->timecompleted - 1;
        $errors = certif_get_completion_errors($completionhistory, null);
        $this->assertEquals(array(), $errors);

        // Test 6 - Update history record, other record has same expiry - error.
        // First create a second history record with a different expiry date.
        $completionhistory = $DB->get_record('certif_completion_history',
            array('certifid' => $certwithhistory->certifid, 'userid' => $user->id));
        $originaltimeexpires = $completionhistory->timeexpires;
        $completionhistory->id = 0; // Force new.
        $completionhistory->timeexpires = $completionhistory->timeexpires + 12345;
        $errors = certif_get_completion_errors($completionhistory, null);
        $this->assertEquals(array(), $errors);
        certif_write_completion_history($completionhistory);
        // Then update the second record, changing the expiry date to that of the first record.
        $completionhistory->timeexpires = $originaltimeexpires;
        $errors = certif_get_completion_errors($completionhistory, null);
        $this->assertEquals(array('error:completionhistorydatesnotunique' => 'timecompleted'), $errors);
    }

    /**
     * Test certif_get_completion_state with cert compl history records (no prog completion).
     * This test checks the unique unassigned constraint, which requires other existing records to test fully.
     */
    public function test_certif_get_completion_errors_for_history_unassigned() {
        global $DB;

        // Set up some normal completion records.
        $now = time();
        $this->setup_completions();
        $this->shift_completions_to_certified($now);

        // Check that all records are ok.
        $certcompletions = $DB->get_records('certif_completion');
        foreach ($certcompletions as $certcompletion) {
            $sql = "SELECT pc.*
                      FROM {prog_completion} pc
                      JOIN {prog} prog ON prog.id = pc.programid
                     WHERE prog.certifid = :certifid AND pc.userid = :userid AND pc.coursesetid = 0";
            $params = array('certifid' => $certcompletion->certifid, 'userid' => $certcompletion->userid);
            $progcompletion = $DB->get_record_sql($sql, $params);
            $errors = certif_get_completion_errors($certcompletion, $progcompletion);
            $this->assertEquals(array(), $errors);
        }
        $this->assertEquals($this->numtestusers * $this->numtestcerts, count($certcompletions));

        $certwithhistorycurrent = $this->certifications[4];
        $certwithhistorynocurrent = $this->certifications[5];
        $certnohistorycurrent = $this->certifications[2];
        $certnohistorynocurrent = $this->certifications[7];
        $user = $this->users[5];

        // Copy some current completion records into history.
        $certcompletions = $DB->get_records('certif_completion', array('certifid' => $certwithhistorycurrent->certifid));
        foreach ($certcompletions as $certcompletion) {
            copy_certif_completion_to_hist($certcompletion->certifid, $certcompletion->userid);
        }
        $certcompletions = $DB->get_records('certif_completion', array('certifid' => $certwithhistorynocurrent->certifid));
        foreach ($certcompletions as $certcompletion) {
            copy_certif_completion_to_hist($certcompletion->certifid, $certcompletion->userid);
        }

        // Check that all history records are valid.
        $histcompletions = $DB->get_records('certif_completion_history');
        foreach ($histcompletions as $histcompletion) {
            $errors = certif_get_completion_errors($histcompletion, null);
            $this->assertEquals(array(), $errors);
        }
        $this->assertEquals($this->numtestusers * 2, count($histcompletions));

        // Delete some current completion records (like unassigning).
        $DB->delete_records('certif_completion', array('certifid' => $certwithhistorynocurrent->certifid));
        $DB->delete_records('certif_completion', array('certifid' => $certnohistorynocurrent->certifid));

        // Test 1 - New unassigned record, no existing history record, no current completion record - no error.
        $completionhistory = array(
            'id' => 0,
            'certifid' => $certnohistorynocurrent->certifid,
            'userid' => $user->id,
            'status' => CERTIFSTATUS_COMPLETED,
            'renewalstatus' => CERTIFRENEWALSTATUS_DUE,
            'certifpath' => CERTIFPATH_RECERT,
            'timecompleted' => 1001,
            'timewindowopens' => 1002,
            'timeexpires' => 1003,
            'baselinetimeexpires' => 1003,
            'unassigned' => 1);
        $errors = certif_get_completion_errors((object)$completionhistory, null);
        $this->assertEquals(array(), $errors);

        // Test 2 - New unassigned record, no existing history record, with current completion record - error.
        $completionhistory = array(
            'id' => 0,
            'certifid' => $certnohistorycurrent->certifid,
            'userid' => $user->id,
            'status' => CERTIFSTATUS_COMPLETED,
            'renewalstatus' => CERTIFRENEWALSTATUS_DUE,
            'certifpath' => CERTIFPATH_RECERT,
            'timecompleted' => 1001,
            'timewindowopens' => 1002,
            'timeexpires' => 1003,
            'baselinetimeexpires' => 1003,
            'unassigned' => 1);
        $errors = certif_get_completion_errors((object)$completionhistory, null);
        $this->assertEquals(array('error:invalidunassignedhist' => 'unassigned'), $errors);

        // Test 3 - New unassigned record, with existing history record (not unassigned), no current completion record - no error.
        $completionhistory = array(
            'id' => 0,
            'certifid' => $certwithhistorynocurrent->certifid,
            'userid' => $user->id,
            'status' => CERTIFSTATUS_COMPLETED,
            'renewalstatus' => CERTIFRENEWALSTATUS_DUE,
            'certifpath' => CERTIFPATH_RECERT,
            'timecompleted' => 1001,
            'timewindowopens' => 1002,
            'timeexpires' => 1003,
            'baselinetimeexpires' => 1003,
            'unassigned' => 1);
        $errors = certif_get_completion_errors((object)$completionhistory, null);
        $this->assertEquals(array(), $errors);

        // Test 4 - Update existing record to unassigned - no error.
        $completionhistory = $DB->get_record('certif_completion_history',
            array('certifid' => $certwithhistorynocurrent->certifid, 'userid' => $user->id));
        $completionhistory->unassigned = 1;
        $errors = certif_get_completion_errors($completionhistory, null);
        $this->assertEquals(array(), $errors);

        // Save the change in test 4, to be used in test 5.
        certif_write_completion_history($completionhistory);

        // Test 5 - New unassigned record, with existing unassigned history record - error.
        $completionhistory = array(
            'id' => 0,
            'certifid' => $certwithhistorynocurrent->certifid,
            'userid' => $user->id,
            'status' => CERTIFSTATUS_COMPLETED,
            'renewalstatus' => CERTIFRENEWALSTATUS_DUE,
            'certifpath' => CERTIFPATH_RECERT,
            'timecompleted' => 1001,
            'timewindowopens' => 1002,
            'timeexpires' => 1003,
            'baselinetimeexpires' => 1003,
            'unassigned' => 1);
        $errors = certif_get_completion_errors((object)$completionhistory, null);
        $this->assertEquals(array('error:invalidunassignedhist' => 'unassigned'), $errors);

        // Test 6 - Update existing record (from test 4), still unassigned (some other change) - no error.
        $completionhistory = $DB->get_record('certif_completion_history',
            array('certifid' => $certwithhistorynocurrent->certifid, 'userid' => $user->id));
        $completionhistory->timeexpires += 32123;
        $errors = certif_get_completion_errors($completionhistory, null);
        $this->assertEquals(array(), $errors);

        // Test 6 - Update existing record (from test 4), remove unassigned - no error.
        $completionhistory = $DB->get_record('certif_completion_history',
            array('certifid' => $certwithhistorynocurrent->certifid, 'userid' => $user->id));
        $completionhistory->unassigned = 0;
        $errors = certif_get_completion_errors($completionhistory, null);
        $this->assertEquals(array(), $errors);
    }

    /**
     * Test certif_get_completion_form_errors. Quick and simple, just to make sure it switches the data around correctly.
     */
    public function test_certif_get_completion_form_errors() {
        $rawerrors = array(
            'error:stateassigned-timedueunknown' => 'timedue',
            'error:stateassigned-pathincorrect' => 'certif_path'
        );
        $expectederrors = array(
            'timedue' => get_string('error:stateassigned-timedueunknown', 'totara_certification'),
            'certif_path' => get_string('error:stateassigned-pathincorrect', 'totara_certification')
        );
        $formerrors = certif_get_completion_form_errors($rawerrors);
        $this->assertEquals($expectederrors, $formerrors);
    }

    /**
     * Test certif_get_completion_error_problemkey. Quick and simple, just to make sure it switches the data around correctly.
     */
    public function test_certif_get_completion_error_problemkey() {
        $rawerrors = array(
            'error:stateassigned-timedueunknown' => 'timedue',
            'error:stateassigned-pathincorrect' => 'certif_path'
        );
        $expectedproblemkey = 'error:stateassigned-pathincorrect|error:stateassigned-timedueunknown';
        $problemkey = certif_get_completion_error_problemkey($rawerrors);
        $this->assertEquals($expectedproblemkey, $problemkey);
    }

    /**
     * Change the state of all completion records to certified, before the window opens.
     */
    public function shift_completions_to_certified($timecompleted) {
        global $DB;

        // Manually change their state.
        $sql = "UPDATE {prog_completion}
                   SET status = :progstatus, timecompleted = :timecompleted, timedue = :timedue
                 WHERE coursesetid = 0";
        $params = array('progstatus' => STATUS_PROGRAM_COMPLETE, 'timecompleted' => $timecompleted,
                        'timedue' => $timecompleted + 2000);
        $DB->execute($sql, $params);
        $sql = "UPDATE {certif_completion}
                   SET status = :certstatus, renewalstatus = :renewalstatus, certifpath = :certifpath,
                       timecompleted = :timecompleted, timewindowopens = :timewindowopens, timeexpires = :timeexpires, baselinetimeexpires = :baselinetimeexpires";
        $params = array('certstatus' => CERTIFSTATUS_COMPLETED, 'renewalstatus' => CERTIFRENEWALSTATUS_NOTDUE,
            'certifpath' => CERTIFPATH_RECERT, 'timecompleted' => $timecompleted, 'timewindowopens' => $timecompleted + 1000,
            'timeexpires' => $timecompleted + 2000, 'baselinetimeexpires' => $timecompleted + 2000);
        $DB->execute($sql, $params);
    }

    /**
     * Test certif_fix_completions - ensure that the correct records are repaired.
     */
    public function test_certif_fix_completions_only_selected() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();
        $timecompleted = time();
        $this->shift_completions_to_certified($timecompleted);

        // Break all the records.
        $sql = "UPDATE {prog_completion} SET timedue = -1 WHERE coursesetid = 0";
        $DB->execute($sql);

        $expectederrors = array('error:statecertified-timedueempty' => 'timedue',
            'error:statecertified-timeexpirestimeduedifferent' => 'timedue');

        // Check that all records are broken in the specified way.
        $certcompletions = $DB->get_records('certif_completion');
        foreach ($certcompletions as $certcompletion) {
            $sql = "SELECT pc.*
                      FROM {prog_completion} pc
                      JOIN {prog} prog ON prog.id = pc.programid
                     WHERE prog.certifid = :certifid AND pc.userid = :userid AND pc.coursesetid = 0";
            $params = array('certifid' => $certcompletion->certifid, 'userid' => $certcompletion->userid);
            $progcompletion = $DB->get_record_sql($sql, $params);
            $errors = certif_get_completion_errors($certcompletion, $progcompletion);
            $this->assertEquals($expectederrors, $errors);
        }
        $this->assertEquals($this->numtestusers * $this->numtestcerts, count($certcompletions));

        $prog = $this->certifications[8];
        $user = $this->users[2];

        // Apply the fix to just one user/cert.
        certif_fix_completions('fixcertcertifiedduedateempty', $prog->id, $user->id);

        // Check that the correct records have been fixed.
        $certcompletions = $DB->get_records('certif_completion');
        foreach ($certcompletions as $certcompletion) {
            $sql = "SELECT pc.*
                      FROM {prog_completion} pc
                      JOIN {prog} prog ON prog.id = pc.programid
                     WHERE prog.certifid = :certifid AND pc.userid = :userid AND pc.coursesetid = 0";
            $params = array('certifid' => $certcompletion->certifid, 'userid' => $certcompletion->userid);
            $progcompletion = $DB->get_record_sql($sql, $params);
            $errors = certif_get_completion_errors($certcompletion, $progcompletion);
            if ($certcompletion->userid == $user->id && $progcompletion->programid == $prog->id) {
                $this->assertEquals(array(), $errors);
            } else {
                $this->assertEquals($expectederrors, $errors);
            }
        }
        $this->assertEquals($this->numtestusers * $this->numtestcerts, count($certcompletions));

        // Apply the fix to just one user, all certs (don't need to reset, just overlap).
        certif_fix_completions('fixcertcertifiedduedateempty', 0, $user->id);

        // Check that the correct records have been fixed.
        $certcompletions = $DB->get_records('certif_completion');
        foreach ($certcompletions as $certcompletion) {
            $sql = "SELECT pc.*
                      FROM {prog_completion} pc
                      JOIN {prog} prog ON prog.id = pc.programid
                     WHERE prog.certifid = :certifid AND pc.userid = :userid AND pc.coursesetid = 0";
            $params = array('certifid' => $certcompletion->certifid, 'userid' => $certcompletion->userid);
            $progcompletion = $DB->get_record_sql($sql, $params);
            $errors = certif_get_completion_errors($certcompletion, $progcompletion);
            if ($certcompletion->userid == $user->id) { // Overlap previous fixes.
                $this->assertEquals(array(), $errors);
            } else {
                $this->assertEquals($expectederrors, $errors);
            }
        }
        $this->assertEquals($this->numtestusers * $this->numtestcerts, count($certcompletions));

        // Apply the fix to just one cert, all users (don't need to reset, just overlap).
        certif_fix_completions('fixcertcertifiedduedateempty', $prog->id);

        // Check that the correct records have been fixed.
        $certcompletions = $DB->get_records('certif_completion');
        foreach ($certcompletions as $certcompletion) {
            $sql = "SELECT pc.*
                      FROM {prog_completion} pc
                      JOIN {prog} prog ON prog.id = pc.programid
                     WHERE prog.certifid = :certifid AND pc.userid = :userid AND pc.coursesetid = 0";
            $params = array('certifid' => $certcompletion->certifid, 'userid' => $certcompletion->userid);
            $progcompletion = $DB->get_record_sql($sql, $params);
            $errors = certif_get_completion_errors($certcompletion, $progcompletion);
            if ($certcompletion->userid == $user->id || $progcompletion->programid == $prog->id) { // Overlap previous fixes.
                $this->assertEquals(array(), $errors);
            } else {
                $this->assertEquals($expectederrors, $errors);
            }
        }
        $this->assertEquals($this->numtestusers * $this->numtestcerts, count($certcompletions));

        // Apply the fix to all records (overlaps previous fixes).
        certif_fix_completions('fixcertcertifiedduedateempty');

        // Check that the correct records have been fixed.
        $certcompletions = $DB->get_records('certif_completion');
        foreach ($certcompletions as $certcompletion) {
            $sql = "SELECT pc.*
                      FROM {prog_completion} pc
                      JOIN {prog} prog ON prog.id = pc.programid
                     WHERE prog.certifid = :certifid AND pc.userid = :userid AND pc.coursesetid = 0";
            $params = array('certifid' => $certcompletion->certifid, 'userid' => $certcompletion->userid);
            $progcompletion = $DB->get_record_sql($sql, $params);
            $errors = certif_get_completion_errors($certcompletion, $progcompletion);
            $this->assertEquals(array(), $errors);
        }
        $this->assertEquals($this->numtestusers * $this->numtestcerts, count($certcompletions));
    }

    /**
     * Test certif_fix_completions - ensure that records with a different state aren't affected.
     *
     * We will use the fixcertcertifiedduedateempty fix key. This will set the due date to
     * be the same as the expiry date.
     */
    public function test_certif_fix_completions_only_specified_state() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();
        $timecompleted = time();
        $this->shift_completions_to_certified($timecompleted);

        $windowopenprog = $this->certifications[8];
        $windowopenuser = $this->users[2];

        // Change some records to the window open state.
        $sql = "UPDATE {prog_completion}
                   SET status = :status, timecompleted = 0
                 WHERE programid = :programid OR userid = :userid AND coursesetid = 0";
        $params = array('status' => STATUS_PROGRAM_INCOMPLETE, 'timecompleted' => 0, 'programid' => $windowopenprog->id,
            'userid' => $windowopenuser->id);
        $DB->execute($sql, $params);
        $sql = "UPDATE {certif_completion}
                   SET renewalstatus = :renewalstatus
                 WHERE certifid = :certifid OR userid = :userid";
        $params = array('renewalstatus' => CERTIFRENEWALSTATUS_DUE, 'certifid' => $windowopenprog->certifid,
            'userid' => $windowopenuser->id);
        $DB->execute($sql, $params);

        // Check that all records are ok.
        $certcompletions = $DB->get_records('certif_completion');
        foreach ($certcompletions as $certcompletion) {
            $sql = "SELECT pc.*
                      FROM {prog_completion} pc
                      JOIN {prog} prog ON prog.id = pc.programid
                     WHERE prog.certifid = :certifid AND pc.userid = :userid AND pc.coursesetid = 0";
            $params = array('certifid' => $certcompletion->certifid, 'userid' => $certcompletion->userid);
            $progcompletion = $DB->get_record_sql($sql, $params);
            $errors = certif_get_completion_errors($certcompletion, $progcompletion);
            $this->assertEquals(array(), $errors);
        }
        $this->assertEquals($this->numtestusers * $this->numtestcerts, count($certcompletions));

        // Break all records.
        $sql = "UPDATE {prog_completion} SET timedue = -1 WHERE coursesetid = 0";
        $DB->execute($sql);

        $expectederrorscertified = array('error:statecertified-timedueempty' => 'timedue',
            'error:statecertified-timeexpirestimeduedifferent' => 'timedue');

        $expectederrorswindowopen = array('error:statewindowopen-timedueempty' => 'timedue',
            'error:statewindowopen-timeexpirestimeduedifferent' => 'timedue');

        // Check that all records are broken in the specified way.
        $certcompletions = $DB->get_records('certif_completion');
        foreach ($certcompletions as $certcompletion) {
            $sql = "SELECT pc.*
                      FROM {prog_completion} pc
                      JOIN {prog} prog ON prog.id = pc.programid
                     WHERE prog.certifid = :certifid AND pc.userid = :userid AND pc.coursesetid = 0";
            $params = array('certifid' => $certcompletion->certifid, 'userid' => $certcompletion->userid);
            $progcompletion = $DB->get_record_sql($sql, $params);
            $errors = certif_get_completion_errors($certcompletion, $progcompletion);
            if ($certcompletion->userid == $windowopenuser->id || $progcompletion->programid == $windowopenprog->id) {
                $this->assertEquals($expectederrorswindowopen, $errors);
            } else {
                $this->assertEquals($expectederrorscertified, $errors);
            }
        }
        $this->assertEquals($this->numtestusers * $this->numtestcerts, count($certcompletions));

        // Apply the fixcertcertifiedduedateempty fix all users and certs, but won't affect window open user/prog.
        certif_fix_completions('fixcertcertifiedduedateempty');

        // Check that the correct records have been fixed.
        $certcompletions = $DB->get_records('certif_completion');
        foreach ($certcompletions as $certcompletion) {
            $sql = "SELECT pc.*
                      FROM {prog_completion} pc
                      JOIN {prog} prog ON prog.id = pc.programid
                     WHERE prog.certifid = :certifid AND pc.userid = :userid AND pc.coursesetid = 0";
            $params = array('certifid' => $certcompletion->certifid, 'userid' => $certcompletion->userid);
            $progcompletion = $DB->get_record_sql($sql, $params);
            $errors = certif_get_completion_errors($certcompletion, $progcompletion);
            if ($certcompletion->userid == $windowopenuser->id || $progcompletion->programid == $windowopenprog->id) {
                $this->assertEquals($expectederrorswindowopen, $errors);
            } else {
                $this->assertEquals(array(), $errors);
            }
        }
        $this->assertEquals($this->numtestusers * $this->numtestcerts, count($certcompletions));

        // Apply the fixcertwindowopenduedateempty fix all users and certs, will fix the remaining records.
        certif_fix_completions('fixcertwindowopenduedateempty');

        // Check that the correct records have been fixed.
        $certcompletions = $DB->get_records('certif_completion');
        foreach ($certcompletions as $certcompletion) {
            $sql = "SELECT pc.*
                      FROM {prog_completion} pc
                      JOIN {prog} prog ON prog.id = pc.programid
                     WHERE prog.certifid = :certifid AND pc.userid = :userid AND pc.coursesetid = 0";
            $params = array('certifid' => $certcompletion->certifid, 'userid' => $certcompletion->userid);
            $progcompletion = $DB->get_record_sql($sql, $params);
            $errors = certif_get_completion_errors($certcompletion, $progcompletion);
            $this->assertEquals(array(), $errors);
        }
        $this->assertEquals($this->numtestusers * $this->numtestcerts, count($certcompletions));
    }

    /**
     * Test certif_fix_completions - ensure that records with the specified problem AND other problems are NOT fixed.
     *
     * We will use the fixcertcertifiedduedateempty fix key. This will set the due date to
     * be the same as the expiry date.
     */
    public function test_certif_fix_completions_only_if_isolated_problem() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();
        $timecompleted = time();
        $this->shift_completions_to_certified($timecompleted);

        $windowopenprog = $this->certifications[8];
        $windowopenuser = $this->users[2];

        // Break all the records - timedue.
        $sql = "UPDATE {prog_completion} SET timedue = -1 WHERE coursesetid = 0";
        $DB->execute($sql);

        // Break some records - timewindowopens.
        $sql = "UPDATE {certif_completion}
                   SET timewindowopens = 0
                 WHERE certifid = :certifid OR userid = :userid";
        $params = array('certifid' => $windowopenprog->certifid, 'userid' => $windowopenuser->id);
        $DB->execute($sql, $params);

        $expectederrorstimedueonly = array(
            'error:statecertified-timedueempty' => 'timedue',
            'error:statecertified-timeexpirestimeduedifferent' => 'timedue'
        );

        $expectederrorswindowopen = array( // Two same as above, two extra.
            'error:statecertified-timedueempty' => 'timedue',
            'error:statecertified-timeexpirestimeduedifferent' => 'timedue',
            'error:statecertified-timewindowopensempty' => 'timewindowopens',
            'error:statecertified-timewindowopenstimecompletednotordered' => 'timewindowopens'
        );

        // Check that all records are broken in the correct way.
        $certcompletions = $DB->get_records('certif_completion');
        foreach ($certcompletions as $certcompletion) {
            $sql = "SELECT pc.*
                      FROM {prog_completion} pc
                      JOIN {prog} prog ON prog.id = pc.programid
                     WHERE prog.certifid = :certifid AND pc.userid = :userid AND pc.coursesetid = 0";
            $params = array('certifid' => $certcompletion->certifid, 'userid' => $certcompletion->userid);
            $progcompletion = $DB->get_record_sql($sql, $params);
            $errors = certif_get_completion_errors($certcompletion, $progcompletion);
            if ($certcompletion->userid == $windowopenuser->id || $progcompletion->programid == $windowopenprog->id) {
                $this->assertEquals($expectederrorswindowopen, $errors);
            } else {
                $this->assertEquals($expectederrorstimedueonly, $errors);
            }
        }
        $this->assertEquals($this->numtestusers * $this->numtestcerts, count($certcompletions));

        // Apply the fixcertcertifiedduedateempty fix to all users and certs, but won't affect those with window open problem.
        certif_fix_completions('fixcertcertifiedduedateempty');

        // Check that the correct records have been fixed.
        $certcompletions = $DB->get_records('certif_completion');
        foreach ($certcompletions as $certcompletion) {
            $sql = "SELECT pc.*
                      FROM {prog_completion} pc
                      JOIN {prog} prog ON prog.id = pc.programid
                     WHERE prog.certifid = :certifid AND pc.userid = :userid AND pc.coursesetid = 0";
            $params = array('certifid' => $certcompletion->certifid, 'userid' => $certcompletion->userid);
            $progcompletion = $DB->get_record_sql($sql, $params);
            $errors = certif_get_completion_errors($certcompletion, $progcompletion);
            if ($certcompletion->userid == $windowopenuser->id || $progcompletion->programid == $windowopenprog->id) {
                $this->assertEquals($expectederrorswindowopen, $errors);
            } else {
                $this->assertEquals(array(), $errors);
            }
        }
        $this->assertEquals($this->numtestusers * $this->numtestcerts, count($certcompletions));
    }

    /**
     * Test certif_fix_completions - ensure that partial fixes work, and don't fix those without the secondary problem.
     * We will use the fixcert001mismatchexpiry fix key. This repairs the due date of records which also have incorrect
     * program timecompleted and status.
     */
    public function test_certif_fix_completions_known_unfixed_problems() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();
        $timecompleted = time();
        $this->shift_completions_to_certified($timecompleted);

        $windowopenprog = $this->certifications[8];
        $windowopenuser = $this->users[2];

        // Change all records to the window open state.
        $sql = "UPDATE {prog_completion}
                   SET status = :status, timecompleted = :timecompleted
                 WHERE coursesetid = 0";
        $params = array('status' => STATUS_PROGRAM_INCOMPLETE, 'timecompleted' => 0);
        $DB->execute($sql, $params);
        $sql = "UPDATE {certif_completion}
                   SET renewalstatus = :renewalstatus";
        $params = array('renewalstatus' => CERTIFRENEWALSTATUS_DUE);
        $DB->execute($sql, $params);

        // Check that all records are ok.
        $certcompletions = $DB->get_records('certif_completion');
        foreach ($certcompletions as $certcompletion) {
            $sql = "SELECT pc.*
                      FROM {prog_completion} pc
                      JOIN {prog} prog ON prog.id = pc.programid
                     WHERE prog.certifid = :certifid AND pc.userid = :userid AND pc.coursesetid = 0";
            $params = array('certifid' => $certcompletion->certifid, 'userid' => $certcompletion->userid);
            $progcompletion = $DB->get_record_sql($sql, $params);
            $errors = certif_get_completion_errors($certcompletion, $progcompletion);
            $this->assertEquals(array(), $errors);
        }
        $this->assertEquals($this->numtestusers * $this->numtestcerts, count($certcompletions));

        // Break all the records - timedue (is set but doesn't match timeexpires).
        $sql = "UPDATE {prog_completion} SET timedue = 12345 WHERE coursesetid = 0";
        $DB->execute($sql);

        // Break some records - program status and timecompleted.
        $sql = "UPDATE {prog_completion}
                   SET status = :status, timecompleted = :timecompleted, timedue = :timedue
                 WHERE programid = :programid OR userid = :userid AND coursesetid = 0";
        $params = array('status' => STATUS_PROGRAM_COMPLETE, 'timecompleted' => $timecompleted,
                        'programid' => $windowopenprog->id, 'userid' => $windowopenuser->id,
                        // We want to change timedue just enough not to generate extra errors.
                        // Shifting completion changes timedue by 2000, so let's make is just below that.
                        'timedue' => $timecompleted + 1500);
        $DB->execute($sql, $params);

        $expectederrorstimedueonly = array(
            'error:statewindowopen-timeexpirestimeduedifferent' => 'timedue'
        );

        $expectederrorsprogramstatusandtimedue = array( // One same as above, two extra.
            'error:statewindowopen-timeexpirestimeduedifferent' => 'timedue',
            'error:statewindowopen-progstatusincorrect' => 'progstatus',
            'error:statewindowopen-progtimecompletednotempty' => 'progtimecompleted'
        );

        $expectederrorsprogramstatusonly = array( // What should be left after the timedue problem is fixed.
            'error:statewindowopen-progstatusincorrect' => 'progstatus',
            'error:statewindowopen-progtimecompletednotempty' => 'progtimecompleted'
        );

        // Check that all records are broken in the correct way.
        $certcompletions = $DB->get_records('certif_completion');
        foreach ($certcompletions as $certcompletion) {
            $sql = "SELECT pc.*
                      FROM {prog_completion} pc
                      JOIN {prog} prog ON prog.id = pc.programid
                     WHERE prog.certifid = :certifid AND pc.userid = :userid AND pc.coursesetid = 0";
            $params = array('certifid' => $certcompletion->certifid, 'userid' => $certcompletion->userid);
            $progcompletion = $DB->get_record_sql($sql, $params);
            $errors = certif_get_completion_errors($certcompletion, $progcompletion);
            if ($certcompletion->userid == $windowopenuser->id || $progcompletion->programid == $windowopenprog->id) {
                $this->assertEquals($expectederrorsprogramstatusandtimedue, $errors);
            } else {
                $this->assertEquals($expectederrorstimedueonly, $errors);
            }
        }
        $this->assertEquals($this->numtestusers * $this->numtestcerts, count($certcompletions));

        // Apply the fixcert001mismatchexpiry fix to all users and certs, but won't affect those with program status problems.
        certif_fix_completions('fixcert001mismatchexpiry');

        // Check that the correct records have been fixed, and that the others haven't.
        $certcompletions = $DB->get_records('certif_completion');
        foreach ($certcompletions as $certcompletion) {
            $sql = "SELECT pc.*
                      FROM {prog_completion} pc
                      JOIN {prog} prog ON prog.id = pc.programid
                     WHERE prog.certifid = :certifid AND pc.userid = :userid AND pc.coursesetid = 0";
            $params = array('certifid' => $certcompletion->certifid, 'userid' => $certcompletion->userid);
            $progcompletion = $DB->get_record_sql($sql, $params);
            $errors = certif_get_completion_errors($certcompletion, $progcompletion);
            if ($certcompletion->userid == $windowopenuser->id || $progcompletion->programid == $windowopenprog->id) {
                $this->assertEquals($expectederrorsprogramstatusonly, $errors);
            } else {
                $this->assertEquals($expectederrorstimedueonly, $errors);
            }
        }
        $this->assertEquals($this->numtestusers * $this->numtestcerts, count($certcompletions));
    }

    /**
     * Test that certif_write_completion causes exceptions when expected (for faults that are caused by bad code).
     */
    public function test_certif_write_completion_exceptions() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();

        // Check that all records are valid.
        $certcompletions = $DB->get_records('certif_completion');
        foreach ($certcompletions as $certcompletion) {
            $sql = "SELECT pc.*
                      FROM {prog_completion} pc
                      JOIN {prog} prog ON prog.id = pc.programid
                     WHERE prog.certifid = :certifid AND pc.userid = :userid AND pc.coursesetid = 0";
            $params = array('certifid' => $certcompletion->certifid, 'userid' => $certcompletion->userid);
            $progcompletion = $DB->get_record_sql($sql, $params);
            $errors = certif_get_completion_errors($certcompletion, $progcompletion);
            $this->assertEquals(array(), $errors);
        }
        $this->assertEquals($this->numtestusers * $this->numtestcerts, count($certcompletions));

        $prog1 = $this->certifications[5];
        $prog2 = $this->certifications[9];
        $user1 = $this->users[2];
        $user2 = $this->users[3];

        // Update, everything is correct (load and save the same records).
        $certcompletion = $DB->get_record('certif_completion', array('certifid' => $prog1->certifid, 'userid' => $user1->id));
        $progcompletion = $DB->get_record('prog_completion', array('programid' => $prog1->id, 'userid' => $user1->id));
        $result = certif_write_completion($certcompletion, $progcompletion);
        $this->assertEquals(true, $result);

        // Cert and prog don't match #1.
        $certcompletion = $DB->get_record('certif_completion', array('certifid' => $prog1->certifid, 'userid' => $user2->id));
        $progcompletion = $DB->get_record('prog_completion', array('programid' => $prog1->id, 'userid' => $user1->id));
        try {
            $result = certif_write_completion($certcompletion, $progcompletion);
            $this->assertEquals("Shouldn't reach this code, exception not triggered!", $result);
        } catch (exception $e) {
            $this->assertContains(get_string('error:updatinginvalidcompletionrecords', 'totara_certification'), $e->getMessage());
        }

        // Cert and prog don't match #2.
        $certcompletion = $DB->get_record('certif_completion', array('certifid' => $prog2->certifid, 'userid' => $user1->id));
        $progcompletion = $DB->get_record('prog_completion', array('programid' => $prog1->id, 'userid' => $user1->id));
        try {
            $result = certif_write_completion($certcompletion, $progcompletion);
            $this->assertEquals("Shouldn't reach this code, exception not triggered!", $result);
        } catch (exception $e) {
            $this->assertContains(get_string('error:updatinginvalidcompletionrecords', 'totara_certification'), $e->getMessage());
        }

        // Cert and prog don't match #3.
        $certcompletion = $DB->get_record('certif_completion', array('certifid' => $prog2->certifid, 'userid' => $user2->id));
        $progcompletion = $DB->get_record('prog_completion', array('programid' => $prog1->id, 'userid' => $user1->id));
        try {
            $result = certif_write_completion($certcompletion, $progcompletion);
            $this->assertEquals("Shouldn't reach this code, exception not triggered!", $result);
        } catch (exception $e) {
            $this->assertContains(get_string('error:updatinginvalidcompletionrecords', 'totara_certification'), $e->getMessage());
        }

        // Cert and prog don't match #4.
        $certcompletion = $DB->get_record('certif_completion', array('certifid' => $prog2->certifid, 'userid' => $user1->id));
        $progcompletion = $DB->get_record('prog_completion', array('programid' => $prog1->id, 'userid' => $user2->id));
        try {
            $result = certif_write_completion($certcompletion, $progcompletion);
            $this->assertEquals("Shouldn't reach this code, exception not triggered!", $result);
        } catch (exception $e) {
            $this->assertContains(get_string('error:updatinginvalidcompletionrecords', 'totara_certification'), $e->getMessage());
        }

        // Cert completion exists but program completion doesn't.
        $certcompletion = $DB->get_record('certif_completion', array('certifid' => $prog1->certifid, 'userid' => $user1->id));
        $progcompletion = $DB->get_record('prog_completion', array('programid' => $prog1->id, 'userid' => $user1->id));
        unset($progcompletion->id);
        try {
            $result = certif_write_completion($certcompletion, $progcompletion);
            $this->assertEquals("Shouldn't reach this code, exception not triggered!", $result);
        } catch (exception $e) {
            $this->assertContains(get_string('error:updatinginvalidcompletionrecords', 'totara_certification'), $e->getMessage());
        }

        // Prog completion exists but cert completion doesn't.
        $certcompletion = $DB->get_record('certif_completion', array('certifid' => $prog1->certifid, 'userid' => $user1->id));
        $progcompletion = $DB->get_record('prog_completion', array('programid' => $prog1->id, 'userid' => $user1->id));
        unset($certcompletion->id);
        try {
            $result = certif_write_completion($certcompletion, $progcompletion);
            $this->assertEquals("Shouldn't reach this code, exception not triggered!", $result);
        } catch (exception $e) {
            $this->assertContains(get_string('error:updatinginvalidcompletionrecords', 'totara_certification'), $e->getMessage());
        }

        // Trying to insert when the records already exist.
        $certcompletion = $DB->get_record('certif_completion', array('certifid' => $prog1->certifid, 'userid' => $user1->id));
        $progcompletion = $DB->get_record('prog_completion', array('programid' => $prog1->id, 'userid' => $user1->id));
        unset($certcompletion->id);
        unset($progcompletion->id);
        try {
            $result = certif_write_completion($certcompletion, $progcompletion);
            $this->assertEquals("Shouldn't reach this code, exception not triggered!", $result);
        } catch (exception $e) {
            $this->assertContains(get_string('error:updatinginvalidcompletionrecords', 'totara_certification'), $e->getMessage());
        }

        // Update, but records don't match the database #1.
        $certcompletion = $DB->get_record('certif_completion', array('certifid' => $prog1->certifid, 'userid' => $user1->id));
        $progcompletion = $DB->get_record('prog_completion', array('programid' => $prog1->id, 'userid' => $user1->id));
        $certcompletion->certifid = $prog2->certifid;
        try {
            $result = certif_write_completion($certcompletion, $progcompletion);
            $this->assertEquals("Shouldn't reach this code, exception not triggered!", $result);
        } catch (exception $e) {
            $this->assertContains(get_string('error:updatinginvalidcompletionrecords', 'totara_certification'), $e->getMessage());
        }

        // Update, but records don't match the database #2.
        $certcompletion = $DB->get_record('certif_completion', array('certifid' => $prog1->certifid, 'userid' => $user1->id));
        $progcompletion = $DB->get_record('prog_completion', array('programid' => $prog1->id, 'userid' => $user1->id));
        $certcompletion->userid = $user2->id;
        try {
            $result = certif_write_completion($certcompletion, $progcompletion);
            $this->assertEquals("Shouldn't reach this code, exception not triggered!", $result);
        } catch (exception $e) {
            $this->assertContains(get_string('error:updatinginvalidcompletionrecords', 'totara_certification'), $e->getMessage());
        }

        // Update, but records don't match the database #3.
        $certcompletion = $DB->get_record('certif_completion', array('certifid' => $prog1->certifid, 'userid' => $user1->id));
        $progcompletion = $DB->get_record('prog_completion', array('programid' => $prog1->id, 'userid' => $user1->id));
        $progcompletion->programid = $prog2->id;
        try {
            $result = certif_write_completion($certcompletion, $progcompletion);
            $this->assertEquals("Shouldn't reach this code, exception not triggered!", $result);
        } catch (exception $e) {
            $this->assertContains(get_string('error:updatinginvalidcompletionrecords', 'totara_certification'), $e->getMessage());
        }

        // Update, but records don't match the database #4.
        $certcompletion = $DB->get_record('certif_completion', array('certifid' => $prog1->certifid, 'userid' => $user1->id));
        $progcompletion = $DB->get_record('prog_completion', array('programid' => $prog1->id, 'userid' => $user1->id));
        $progcompletion->userid = $user2->id;
        try {
            $result = certif_write_completion($certcompletion, $progcompletion);
            $this->assertEquals("Shouldn't reach this code, exception not triggered!", $result);
        } catch (exception $e) {
            $this->assertContains(get_string('error:updatinginvalidcompletionrecords', 'totara_certification'), $e->getMessage());
        }

        // Check that all records are valid.
        $certcompletions = $DB->get_records('certif_completion');
        foreach ($certcompletions as $certcompletion) {
            $sql = "SELECT pc.*
                      FROM {prog_completion} pc
                      JOIN {prog} prog ON prog.id = pc.programid
                     WHERE prog.certifid = :certifid AND pc.userid = :userid AND pc.coursesetid = 0";
            $params = array('certifid' => $certcompletion->certifid, 'userid' => $certcompletion->userid);
            $progcompletion = $DB->get_record_sql($sql, $params);
            $errors = certif_get_completion_errors($certcompletion, $progcompletion);
            $this->assertEquals(array(), $errors);
        }
        $this->assertEquals($this->numtestusers * $this->numtestcerts, count($certcompletions));
    }

    /**
     * Test that certif_write_completion writes the data correctly and returns true or false.
     */
    public function test_certif_write_completion() {
        global $DB;

        // Set up some data that is valid.
        $beforeassigned = time();
        $this->setup_completions();
        $afterassigned = time();

        $emptyprog = $this->certifications[1];
        $emptyuser = $this->users[9];
        $anotherprog = $this->certifications[5];
        $anotheruser = $this->users[6];

        // Remove all completion records for one certification.
        $DB->delete_records('certif_completion', array('certifid' => $emptyprog->certifid));
        $DB->delete_records('prog_completion', array('programid' => $emptyprog->id));

        // Remove all completion records for one user.
        $DB->delete_records('certif_completion', array('userid' => $emptyuser->id));
        $DB->delete_records('prog_completion', array('userid' => $emptyuser->id));

        // Check that all remaining records are valid.
        $certcompletions = $DB->get_records('certif_completion');
        foreach ($certcompletions as $certcompletion) {
            $sql = "SELECT pc.*
                      FROM {prog_completion} pc
                      JOIN {prog} prog ON prog.id = pc.programid
                     WHERE prog.certifid = :certifid AND pc.userid = :userid AND pc.coursesetid = 0";
            $params = array('certifid' => $certcompletion->certifid, 'userid' => $certcompletion->userid);
            $progcompletion = $DB->get_record_sql($sql, $params);
            $errors = certif_get_completion_errors($certcompletion, $progcompletion);
            $this->assertEquals(array(), $errors);
        }
        // Think of it as a grid - we deleted one row and one column.
        $this->assertEquals(($this->numtestusers - 1) * ($this->numtestcerts - 1), count($certcompletions));

        $now = time();

        $certcompletioncertifiedtemplate = new stdClass();
        $certcompletioncertifiedtemplate->status = CERTIFSTATUS_COMPLETED;
        $certcompletioncertifiedtemplate->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletioncertifiedtemplate->certifpath = CERTIFPATH_RECERT;
        $certcompletioncertifiedtemplate->timecompleted = 1001;
        $certcompletioncertifiedtemplate->timewindowopens = 1002;
        $certcompletioncertifiedtemplate->timeexpires = 1003;
        $certcompletioncertifiedtemplate->baselinetimeexpires = 1003;
        $certcompletioncertifiedtemplate->timemodified = $now;

        $progcompletioncertifiedtemplate = new stdClass();
        $progcompletioncertifiedtemplate->status = STATUS_PROGRAM_COMPLETE;
        $progcompletioncertifiedtemplate->timedue = 1003;
        $progcompletioncertifiedtemplate->timecompleted = 1001;
        $progcompletioncertifiedtemplate->organisationid = 13;
        $progcompletioncertifiedtemplate->positionid = 14;

        // Add completion for empty cert, empty user, but with invalid data.
        $certcompletion = clone($certcompletioncertifiedtemplate);
        $certcompletion->certifid = $emptyprog->certifid;
        $certcompletion->userid = $emptyuser->id;
        $progcompletion = clone($progcompletioncertifiedtemplate);
        $progcompletion->programid = $emptyprog->id;
        $progcompletion->userid = $emptyuser->id;
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE; // Invalid.

        $errors = certif_get_completion_errors($certcompletion, $progcompletion);
        $this->assertEquals(array('error:statecertified-progstatusincorrect' => 'progstatus'), $errors);
        $result = certif_write_completion($certcompletion, $progcompletion);
        $this->assertEquals(false, $result); // Fails to write (but doesn't cause exception)!

        // Add completion for empty cert, empty user.
        $certcompletion = clone($certcompletioncertifiedtemplate);
        $certcompletion->certifid = $emptyprog->certifid;
        $certcompletion->userid = $emptyuser->id;
        $progcompletion = clone($progcompletioncertifiedtemplate);
        $progcompletion->programid = $emptyprog->id;
        $progcompletion->userid = $emptyuser->id;

        $errors = certif_get_completion_errors($certcompletion, $progcompletion);
        $this->assertEquals(array(), $errors);
        $result = certif_write_completion($certcompletion, $progcompletion);
        $this->assertEquals(true, $result);

        // Add completion for empty cert, another user.
        $certcompletion = clone($certcompletioncertifiedtemplate);
        $certcompletion->certifid = $emptyprog->certifid;
        $certcompletion->userid = $anotheruser->id;
        $progcompletion = clone($progcompletioncertifiedtemplate);
        $progcompletion->programid = $emptyprog->id;
        $progcompletion->userid = $anotheruser->id;

        $errors = certif_get_completion_errors($certcompletion, $progcompletion);
        $this->assertEquals(array(), $errors);
        $result = certif_write_completion($certcompletion, $progcompletion);
        $this->assertEquals(true, $result);

        // Add completion for another cert, empty user.
        $certcompletion = clone($certcompletioncertifiedtemplate);
        $certcompletion->certifid = $anotherprog->certifid;
        $certcompletion->userid = $emptyuser->id;
        $progcompletion = clone($progcompletioncertifiedtemplate);
        $progcompletion->programid = $anotherprog->id;
        $progcompletion->userid = $emptyuser->id;

        $errors = certif_get_completion_errors($certcompletion, $progcompletion);
        $this->assertEquals(array(), $errors);
        $result = certif_write_completion($certcompletion, $progcompletion);
        $this->assertEquals(true, $result);

        // Check that all records are correct (original are assigned, extras are certified).
        $certcompletions = $DB->get_records('certif_completion');
        foreach ($certcompletions as $certcompletion) {
            $sql = "SELECT pc.*
                      FROM {prog_completion} pc
                      JOIN {prog} prog ON prog.id = pc.programid
                     WHERE prog.certifid = :certifid AND pc.userid = :userid AND pc.coursesetid = 0";
            $params = array('certifid' => $certcompletion->certifid, 'userid' => $certcompletion->userid);
            $progcompletion = $DB->get_record_sql($sql, $params);
            $errors = certif_get_completion_errors($certcompletion, $progcompletion);
            $this->assertEquals(array(), $errors);

            // Determine which type of record to expect.
            if ($certcompletion->certifid == $emptyprog->certifid && $certcompletion->userid == $emptyuser->id ||
                $certcompletion->certifid == $emptyprog->certifid && $certcompletion->userid == $anotheruser->id ||
                $certcompletion->certifid == $anotherprog->certifid && $certcompletion->userid == $emptyuser->id) {

                $this->assertEquals(CERTIFSTATUS_COMPLETED, $certcompletion->status);
                $this->assertEquals(CERTIFRENEWALSTATUS_NOTDUE, $certcompletion->renewalstatus);
                $this->assertEquals(CERTIFPATH_RECERT, $certcompletion->certifpath);
                $this->assertEquals(1001, $certcompletion->timecompleted);
                $this->assertEquals(1002, $certcompletion->timewindowopens);
                $this->assertEquals(1003, $certcompletion->timeexpires);
                $this->assertEquals($now, $certcompletion->timemodified);

                $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
                $this->assertEquals(1003, $progcompletion->timedue);
                $this->assertEquals(1001, $progcompletion->timecompleted);
                $this->assertEquals(13, $progcompletion->organisationid);
                $this->assertEquals(14, $progcompletion->positionid);
            } else {
                $this->assertEquals(CERTIFSTATUS_ASSIGNED, $certcompletion->status);
                $this->assertEquals(CERTIFRENEWALSTATUS_NOTDUE, $certcompletion->renewalstatus);
                $this->assertEquals(CERTIFPATH_CERT, $certcompletion->certifpath);
                $this->assertEquals(0, $certcompletion->timecompleted);
                $this->assertEquals(0, $certcompletion->timewindowopens);
                $this->assertEquals(0, $certcompletion->timeexpires);
                $this->assertGreaterThanOrEqual($beforeassigned, $certcompletion->timemodified);
                $this->assertLessThanOrEqual($afterassigned, $certcompletion->timemodified);

                $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);
                $this->assertEquals(-1, $progcompletion->timedue);
                $this->assertEquals(0, $progcompletion->timecompleted);
                $this->assertEquals(0, $progcompletion->organisationid);
                $this->assertEquals(0, $progcompletion->positionid);
            }
        }
        // We re-added 3 items to the grid, one on the intersection, one on column, one on row.
        $this->assertEquals(($this->numtestusers - 1) * ($this->numtestcerts - 1) + 3, count($certcompletions));
    }

    /**
     * Test that certif_write_history_completion causes exceptions when expected (for faults that are caused by bad code).
     *
     * To do this test, we'll set up some certif_completion records and then move them into history.
     */
    public function test_certif_write_completion_history_exceptions() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();
        $this->shift_completions_to_certified(time());

        // Check that all records are ok.
        $certcompletions = $DB->get_records('certif_completion');
        foreach ($certcompletions as $certcompletion) {
            $sql = "SELECT pc.*
                      FROM {prog_completion} pc
                      JOIN {prog} prog ON prog.id = pc.programid
                     WHERE prog.certifid = :certifid AND pc.userid = :userid AND pc.coursesetid = 0";
            $params = array('certifid' => $certcompletion->certifid, 'userid' => $certcompletion->userid);
            $progcompletion = $DB->get_record_sql($sql, $params);
            $errors = certif_get_completion_errors($certcompletion, $progcompletion);
            $this->assertEquals(array(), $errors);
        }
        $this->assertEquals($this->numtestusers * $this->numtestcerts, count($certcompletions));

        // Copy current completion records into history. Note that there's only one per user/cert.
        $certcompletions = $DB->get_records('certif_completion');
        foreach ($certcompletions as $certcompletion) {
            copy_certif_completion_to_hist($certcompletion->certifid, $certcompletion->userid);
        }

        // Check that all history records are valid.
        $histcompletions = $DB->get_records('certif_completion_history');
        foreach ($histcompletions as $histcompletion) {
            $errors = certif_get_completion_errors($histcompletion, null);
            $this->assertEquals(array(), $errors);
        }
        $this->assertEquals($this->numtestusers * $this->numtestcerts, count($histcompletions));

        $prog1 = $this->certifications[5];
        $prog2 = $this->certifications[9];
        $user1 = $this->users[2];
        $user2 = $this->users[3];

        // Update, everything is correct (load and save the same record).
        $histcompletion = $DB->get_record('certif_completion_history', array('certifid' => $prog1->certifid, 'userid' => $user1->id));
        $result = certif_write_completion_history($histcompletion);
        $this->assertEquals(true, $result);

        // Update, but records don't match the database #1.
        $histcompletion = $DB->get_record('certif_completion', array('certifid' => $prog1->certifid, 'userid' => $user1->id));
        $histcompletion->certifid = $prog2->certifid;
        try {
            $result = certif_write_completion_history($histcompletion);
            $this->assertEquals("Shouldn't reach this code, exception not triggered!", $result);
        } catch (exception $e) {
            $this->assertContains(get_string('error:updatinginvalidcompletionhistoryrecord', 'totara_certification'), $e->getMessage());
        }

        // Update, but records don't match the database #2.
        $histcompletion = $DB->get_record('certif_completion', array('certifid' => $prog1->certifid, 'userid' => $user1->id));
        $histcompletion->userid = $user2->id;
        try {
            $result = certif_write_completion_history($histcompletion);
            $this->assertEquals("Shouldn't reach this code, exception not triggered!", $result);
        } catch (exception $e) {
            $this->assertContains(get_string('error:updatinginvalidcompletionhistoryrecord', 'totara_certification'), $e->getMessage());
        }

        // Trying to insert when the record already exists.
        $historycompletion = $DB->get_record('certif_completion_history', array('certifid' => $prog1->certifid, 'userid' => $user1->id));
        unset($historycompletion->id);
        try {
            $result = certif_write_completion_history($historycompletion);
            $this->assertEquals("Shouldn't reach this code, exception not triggered!", $result);
        } catch (exception $e) {
            $this->assertContains(get_string('error:updatinginvalidcompletionhistoryrecord', 'totara_certification'), $e->getMessage());
        }

        // Trying to insert when the record already exists but this is a different timeexpires (so can be inserted).
        $historycompletion = $DB->get_record('certif_completion_history', array('certifid' => $prog1->certifid, 'userid' => $user1->id));
        unset($historycompletion->id);
        $historycompletion->timeexpires = $historycompletion->timeexpires + 1234321;
        $result = certif_write_completion_history($historycompletion);
        $this->assertEquals(true, $result);

        // Check that all history records are valid.
        $histcompletions = $DB->get_records('certif_completion_history');
        foreach ($histcompletions as $histcompletion) {
            $errors = certif_get_completion_errors($histcompletion, null);
            $this->assertEquals(array(), $errors);
        }
        $this->assertEquals($this->numtestusers * $this->numtestcerts + 1, count($histcompletions)); // One record created above.
    }

    /**
     * Test that certif_write_completion_history writes the data correctly and returns true or false.
     */
    public function test_certif_write_completion_history() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();

        // Copy current completion records into history. Note that there's only one per user/cert.
        $certcompletions = $DB->get_records('certif_completion');
        $beforeassigned = time();
        foreach ($certcompletions as $certcompletion) {
            copy_certif_completion_to_hist($certcompletion->certifid, $certcompletion->userid);
        }
        $afterassigned = time();

        // Check that all history records are valid.
        $histcompletions = $DB->get_records('certif_completion_history');
        foreach ($histcompletions as $histcompletion) {
            $errors = certif_get_completion_errors($histcompletion, null);
            $this->assertEquals(array(), $errors);
        }
        $this->assertEquals($this->numtestusers * $this->numtestcerts, count($histcompletions));

        $prog = $this->certifications[1];
        $user = $this->users[9];

        $now = time();

        $historycompletioncertifiedtemplate = new stdClass();
        $historycompletioncertifiedtemplate->status = CERTIFSTATUS_COMPLETED;
        $historycompletioncertifiedtemplate->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $historycompletioncertifiedtemplate->certifpath = CERTIFPATH_RECERT;
        $historycompletioncertifiedtemplate->timecompleted = 1001;
        $historycompletioncertifiedtemplate->timewindowopens = 1002;
        $historycompletioncertifiedtemplate->timeexpires = 1003;
        $historycompletioncertifiedtemplate->baselinetimeexpires = 1003;
        $historycompletioncertifiedtemplate->timemodified = $now;

        // Add completion with invalid data.
        $historycompletion = clone($historycompletioncertifiedtemplate);
        $historycompletion->certifid = $prog->certifid;
        $historycompletion->userid = $user->id;
        $historycompletion->certifpath = CERTIFPATH_CERT; // This is the introduced problem - should be RECERT.

        $errors = certif_get_completion_errors($historycompletion, null);
        $this->assertEquals(array('error:statecertified-pathincorrect' => 'certifpath'), $errors);
        $result = certif_write_completion_history($historycompletion);
        $this->assertEquals(false, $result); // Fails to write (but doesn't cause exception)!

        // Add completion with valid data. This user/cert already has a record, but with a different timeexpires.
        $historycompletion = clone($historycompletioncertifiedtemplate);
        $historycompletion->certifid = $prog->certifid;
        $historycompletion->userid = $user->id;
        $historycompletion->unassigned = 0;

        $errors = certif_get_completion_errors($historycompletion, null);
        $this->assertEquals(array(), $errors);
        $result = certif_write_completion_history($historycompletion);
        $this->assertEquals(true, $result);

        // Check that all records are correct (original are assigned, extras are certified).
        $historycompletions = $DB->get_records('certif_completion_history');
        foreach ($historycompletions as $historycompletion) {
            $errors = certif_get_completion_errors($historycompletion, null);
            $this->assertEquals(array(), $errors);

            // Determine which type of record to expect.
            if ($historycompletion->certifid == $prog->certifid &&
                $historycompletion->userid == $user->id &&
                $historycompletion->timeexpires == 1003
                ) {
                $this->assertEquals(CERTIFSTATUS_COMPLETED, $historycompletion->status);
                $this->assertEquals(CERTIFRENEWALSTATUS_NOTDUE, $historycompletion->renewalstatus);
                $this->assertEquals(CERTIFPATH_RECERT, $historycompletion->certifpath);
                $this->assertEquals(1001, $historycompletion->timecompleted);
                $this->assertEquals(1002, $historycompletion->timewindowopens);
                $this->assertEquals(1003, $historycompletion->timeexpires);
                $this->assertEquals($now, $historycompletion->timemodified);
                $this->assertEquals(0, $historycompletion->unassigned);
            } else {
                $this->assertEquals(CERTIFSTATUS_ASSIGNED, $historycompletion->status);
                $this->assertEquals(CERTIFRENEWALSTATUS_NOTDUE, $historycompletion->renewalstatus);
                $this->assertEquals(CERTIFPATH_CERT, $historycompletion->certifpath);
                $this->assertEquals(0, $historycompletion->timecompleted);
                $this->assertEquals(0, $historycompletion->timewindowopens);
                $this->assertEquals(0, $historycompletion->timeexpires);
                $this->assertGreaterThanOrEqual($beforeassigned, $historycompletion->timemodified);
                $this->assertLessThanOrEqual($afterassigned, $historycompletion->timemodified);
                $this->assertEquals(0, $historycompletion->unassigned);
            }
        }
        // We added one record, so one user now has two history completion records.
        $this->assertEquals($this->numtestusers * $this->numtestcerts + 1, count($historycompletions));
    }

    /**
     * Test certif_fix_completion_expiry_to_due_date. Just copy timeexpires to timedue.
     */
    public function test_certif_fix_completion_expiry_to_due_date() {
        // Expected record is certified, before window opens.
        $expectedcertcompletion = new stdClass();
        $expectedcertcompletion->id = 1001;
        $expectedcertcompletion->certifid = 1002;
        $expectedcertcompletion->userid = 1003;
        $expectedcertcompletion->certifpath = CERTIFPATH_RECERT;
        $expectedcertcompletion->status = CERTIFSTATUS_COMPLETED;
        $expectedcertcompletion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $expectedcertcompletion->timecompleted = 1004;
        $expectedcertcompletion->timewindowopens = 1005;
        $expectedcertcompletion->timeexpires = 1006; // Should match timedue.
        $expectedcertcompletion->baselinetimeexpires = 1006;

        $expectedprogcompletion = new stdClass();
        $expectedprogcompletion->id = 1007;
        $expectedprogcompletion->programid = 1008;
        $expectedprogcompletion->userid = 1003;
        $expectedprogcompletion->status = STATUS_PROGRAM_COMPLETE;
        $expectedprogcompletion->timestarted = 1009;
        $expectedprogcompletion->timedue = 1006; // Should match timeexpires.
        $expectedprogcompletion->timecompleted = 1004;

        // Check that the expected test data is in a valid state.
        $errors = certif_get_completion_errors($expectedcertcompletion, $expectedprogcompletion);
        $this->assertEquals(array(), $errors);

        $certcompletion = clone($expectedcertcompletion);
        $progcompletion = clone($expectedprogcompletion);
        $progcompletion->timedue = 0; // This is the error that needs fixing.

        certif_fix_completion_expiry_to_due_date($certcompletion, $progcompletion);

        $this->assertEquals($expectedcertcompletion, $certcompletion);
        $this->assertEquals($expectedprogcompletion, $progcompletion);
    }

    /**
     * Test certif_fix_completion_window_reopen. Move the state backwards from window open to before window opens.
     */
    public function test_certif_fix_completion_window_reopen() {
        // Expected record is certified, before window opens.
        $expectedcertcompletion = new stdClass();
        $expectedcertcompletion->id = 1001;
        $expectedcertcompletion->certifid = 1002;
        $expectedcertcompletion->userid = 1003;
        $expectedcertcompletion->certifpath = CERTIFPATH_RECERT;
        $expectedcertcompletion->status = CERTIFSTATUS_COMPLETED;
        $expectedcertcompletion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $expectedcertcompletion->timecompleted = 1004;
        $expectedcertcompletion->timewindowopens = 1005;
        $expectedcertcompletion->timeexpires = 1006;
        $expectedcertcompletion->baselinetimeexpires = 1006;

        $expectedprogcompletion = new stdClass();
        $expectedprogcompletion->id = 1007;
        $expectedprogcompletion->programid = 1008;
        $expectedprogcompletion->userid = 1003;
        $expectedprogcompletion->status = STATUS_PROGRAM_COMPLETE;
        $expectedprogcompletion->timestarted = 1009;
        $expectedprogcompletion->timedue = 1006;
        $expectedprogcompletion->timecompleted = 1004;

        // Check that the expected test data is in a valid state.
        $errors = certif_get_completion_errors($expectedcertcompletion, $expectedprogcompletion);
        $this->assertEquals(array(), $errors);

        $certcompletion = clone($expectedcertcompletion);
        $progcompletion = clone($expectedprogcompletion);
        // Change the record so that the certification window has opened but didn't reset the program completion record.
        $certcompletion->status = CERTIFSTATUS_INPROGRESS;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_DUE;
        $progcompletion->timecompleted = 9999;

        certif_fix_completion_window_reopen($certcompletion, $progcompletion);

        $this->assertEquals($expectedcertcompletion, $certcompletion);
        $this->assertEquals($expectedprogcompletion, $progcompletion);
    }

    /**
     * Test certif_fix_completion_prog_status_reset. Reset the prog completion record, which should have happened on window open.
     */
    public function test_certif_fix_completion_prog_status_reset() {
        // Expected record is certified, after window opens.
        $expectedcertcompletion = new stdClass();
        $expectedcertcompletion->id = 1001;
        $expectedcertcompletion->certifid = 1002;
        $expectedcertcompletion->userid = 1003;
        $expectedcertcompletion->certifpath = CERTIFPATH_RECERT;
        $expectedcertcompletion->status = CERTIFSTATUS_COMPLETED;
        $expectedcertcompletion->renewalstatus = CERTIFRENEWALSTATUS_DUE;
        $expectedcertcompletion->timecompleted = 1004;
        $expectedcertcompletion->timewindowopens = 1005;
        $expectedcertcompletion->timeexpires = 1006;
        $expectedcertcompletion->baselinetimeexpires = 1006;

        $expectedprogcompletion = new stdClass();
        $expectedprogcompletion->id = 1007;
        $expectedprogcompletion->programid = 1008;
        $expectedprogcompletion->userid = 1003;
        $expectedprogcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $expectedprogcompletion->timestarted = 1009;
        $expectedprogcompletion->timedue = 1006;
        $expectedprogcompletion->timecompleted = 0;

        // Check that the expected test data is in a valid state.
        $errors = certif_get_completion_errors($expectedcertcompletion, $expectedprogcompletion);
        $this->assertEquals(array(), $errors);

        $certcompletion = clone($expectedcertcompletion);
        $progcompletion = clone($expectedprogcompletion);
        // Change the record so that the certification window has opened but didn't reset the program completion record.
        $progcompletion->status = STATUS_PROGRAM_COMPLETE;
        $progcompletion->timecompleted = 9999;

        certif_fix_completion_prog_status_reset($certcompletion, $progcompletion);

        $this->assertEquals($expectedcertcompletion, $certcompletion);
        $this->assertEquals($expectedprogcompletion, $progcompletion);
    }

    /**
     * Test certif_fix_completion_prog_status_set_complete. Set the program completion record to complete, to match cert completion.
     */
    public function test_certif_fix_completion_prog_status_set_complete() {
        // Expected record is certified, before window opens.
        $expectedcertcompletion = new stdClass();
        $expectedcertcompletion->id = 1001;
        $expectedcertcompletion->certifid = 1002;
        $expectedcertcompletion->userid = 1003;
        $expectedcertcompletion->certifpath = CERTIFPATH_RECERT;
        $expectedcertcompletion->status = CERTIFSTATUS_COMPLETED;
        $expectedcertcompletion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $expectedcertcompletion->timecompleted = 1004;
        $expectedcertcompletion->timewindowopens = 1005;
        $expectedcertcompletion->timeexpires = 1006;
        $expectedcertcompletion->baselinetimeexpires = 1006;

        $expectedprogcompletion = new stdClass();
        $expectedprogcompletion->id = 1007;
        $expectedprogcompletion->programid = 1008;
        $expectedprogcompletion->userid = 1003;
        $expectedprogcompletion->status = STATUS_PROGRAM_COMPLETE;
        $expectedprogcompletion->timestarted = 1009;
        $expectedprogcompletion->timedue = 1006;
        $expectedprogcompletion->timecompleted = 1004;

        // Check that the expected test data is in a valid state.
        $errors = certif_get_completion_errors($expectedcertcompletion, $expectedprogcompletion);
        $this->assertEquals(array(), $errors);

        $certcompletion = clone($expectedcertcompletion);
        $progcompletion = clone($expectedprogcompletion);
        // Change the record so that the program completion record is wrong.
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;

        certif_fix_completion_prog_status_set_complete($certcompletion, $progcompletion);

        $this->assertEquals($expectedcertcompletion, $certcompletion);
        $this->assertEquals($expectedprogcompletion, $progcompletion);
    }

    /**
     * Test certif_fix_cert_completion_date. Set the certification completion date to match program completion date.
     *
     * This fix function requires that the certification exists.
     */
    public function test_certif_fix_cert_completion_date() {
        $this->resetAfterTest(true);

        // Case 1: Certification uses completion date method.
        $settings = array(
            'cert_activeperiod' => '100 day',
            'cert_windowperiod' => '20 day',
            'cert_recertifydatetype' => CERTIFRECERT_COMPLETION,
        );
        $cert = $this->getDataGenerator()->create_certification($settings);

        // Expected record is certified, before window opens.
        $testcertcompletion = new stdClass();
        $testcertcompletion->id = 1001;
        $testcertcompletion->certifid = $cert->certifid;
        $testcertcompletion->userid = 1003;
        $testcertcompletion->certifpath = CERTIFPATH_RECERT;
        $testcertcompletion->status = CERTIFSTATUS_COMPLETED;
        $testcertcompletion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $testcertcompletion->timecompleted = 1004;
        $testcertcompletion->timewindowopens = 1005;
        $testcertcompletion->timeexpires = 1006;
        $testcertcompletion->baselinetimeexpires = 1006;

        $testprogcompletion = new stdClass();
        $testprogcompletion->id = 1007;
        $testprogcompletion->programid = 1008;
        $testprogcompletion->userid = 1003;
        $testprogcompletion->status = STATUS_PROGRAM_COMPLETE;
        $testprogcompletion->timestarted = 1009;
        $testprogcompletion->timedue = 1006;
        $testprogcompletion->timecompleted = 123456; // Cert dates will be based off this.

        $expectedcertcompletion = new stdClass();
        $expectedcertcompletion->id = 1001;
        $expectedcertcompletion->certifid = $cert->certifid;
        $expectedcertcompletion->userid = 1003;
        $expectedcertcompletion->certifpath = CERTIFPATH_RECERT;
        $expectedcertcompletion->status = CERTIFSTATUS_COMPLETED;
        $expectedcertcompletion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $expectedcertcompletion->timecompleted = $testprogcompletion->timecompleted;
        $expectedcertcompletion->timeexpires = $testprogcompletion->timecompleted + DAYSECS * 100;
        $expectedcertcompletion->baselinetimeexpires = $expectedcertcompletion->timeexpires;
        $expectedcertcompletion->timewindowopens = $expectedcertcompletion->timeexpires - DAYSECS * 20;

        $expectedprogcompletion = new stdClass();
        $expectedprogcompletion->id = 1007;
        $expectedprogcompletion->programid = 1008;
        $expectedprogcompletion->userid = 1003;
        $expectedprogcompletion->status = STATUS_PROGRAM_COMPLETE;
        $expectedprogcompletion->timestarted = 1009;
        $expectedprogcompletion->timedue = $expectedcertcompletion->timeexpires;
        $expectedprogcompletion->timecompleted = 123456;

        // Check that the expected test data is in a valid state.
        $errors = certif_get_completion_errors($expectedcertcompletion, $expectedprogcompletion);
        $this->assertEquals(array(), $errors);

        certif_fix_cert_completion_date($testcertcompletion, $testprogcompletion);

        $this->assertEquals($expectedcertcompletion, $testcertcompletion);
        $this->assertEquals($expectedprogcompletion, $testprogcompletion);

        // Case 2: Certification uses expiry date method and program date is ok.
        $settings = array(
            'cert_activeperiod' => '100 day',
            'cert_windowperiod' => '20 day',
            'cert_recertifydatetype' => CERTIFRECERT_EXPIRY,
        );
        $cert = $this->getDataGenerator()->create_certification($settings);

        $now = time(); // Arbitrary base time.

        // Expected record is certified, before window opens.
        $testcertcompletion = new stdClass();
        $testcertcompletion->id = 1001;
        $testcertcompletion->certifid = $cert->certifid;
        $testcertcompletion->userid = 1003;
        $testcertcompletion->certifpath = CERTIFPATH_RECERT;
        $testcertcompletion->status = CERTIFSTATUS_COMPLETED;
        $testcertcompletion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $testcertcompletion->timecompleted = $now;
        $testcertcompletion->timeexpires = $testcertcompletion->timecompleted + DAYSECS * 100;
        $testcertcompletion->baselinetimeexpires = $testcertcompletion->timecompleted + DAYSECS * 100;
        $testcertcompletion->timewindowopens = $testcertcompletion->timeexpires - DAYSECS * 20;

        $testprogcompletion = new stdClass();
        $testprogcompletion->id = 1007;
        $testprogcompletion->programid = 1008;
        $testprogcompletion->userid = 1003;
        $testprogcompletion->status = STATUS_PROGRAM_COMPLETE;
        $testprogcompletion->timestarted = 1009;
        $testprogcompletion->timedue = $testcertcompletion->timeexpires;
        $testprogcompletion->timecompleted = $now - DAYSECS * 10; // Prog completion date is 10 days before cert.

        $expectedcertcompletion = clone($testcertcompletion);
        $expectedprogcompletion = clone($testprogcompletion);
        $expectedcertcompletion->timecompleted = $testprogcompletion->timecompleted; // The only change.

        // Check that the expected test data is in a valid state.
        $errors = certif_get_completion_errors($expectedcertcompletion, $expectedprogcompletion);
        $this->assertEquals(array(), $errors);

        certif_fix_cert_completion_date($testcertcompletion, $testprogcompletion);

        $this->assertEquals($expectedcertcompletion, $testcertcompletion);
        $this->assertEquals($expectedprogcompletion, $testprogcompletion);

        // Check that the expected test data is in a valid state.
        $errors = certif_get_completion_errors($expectedcertcompletion, $expectedprogcompletion);
        $this->assertEquals(array(), $errors);

        certif_fix_cert_completion_date($testcertcompletion, $testprogcompletion);

        $this->assertEquals($expectedcertcompletion, $testcertcompletion);
        $this->assertEquals($expectedprogcompletion, $testprogcompletion);

        // Case 3: Certification uses expiry date method and program date is after window open date, so results in error.
        $settings = array(
            'cert_activeperiod' => '100 day',
            'cert_windowperiod' => '20 day',
            'cert_recertifydatetype' => CERTIFRECERT_EXPIRY,
        );
        $cert = $this->getDataGenerator()->create_certification($settings);

        $now = time(); // Arbitrary base time.

        // Expected record is certified, before window opens.
        $testcertcompletion = new stdClass();
        $testcertcompletion->id = 1001;
        $testcertcompletion->certifid = $cert->certifid;
        $testcertcompletion->userid = 1003;
        $testcertcompletion->certifpath = CERTIFPATH_RECERT;
        $testcertcompletion->status = CERTIFSTATUS_COMPLETED;
        $testcertcompletion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $testcertcompletion->timecompleted = $now;
        $testcertcompletion->timeexpires = $testcertcompletion->timecompleted + DAYSECS * 100;
        $testcertcompletion->baselinetimeexpires = $testcertcompletion->timecompleted + DAYSECS * 100;
        $testcertcompletion->timewindowopens = $testcertcompletion->timeexpires - DAYSECS * 20;

        $testprogcompletion = new stdClass();
        $testprogcompletion->id = 1007;
        $testprogcompletion->programid = 1008;
        $testprogcompletion->userid = 1003;
        $testprogcompletion->status = STATUS_PROGRAM_COMPLETE;
        $testprogcompletion->timestarted = 1009;
        $testprogcompletion->timedue = $testcertcompletion->timeexpires;
        $testprogcompletion->timecompleted = $now + DAYSECS * 90; // Prog completion date is after window open date.

        $expectedcertcompletion = clone($testcertcompletion);
        $expectedprogcompletion = clone($testprogcompletion);
        $expectedcertcompletion->timecompleted = $testprogcompletion->timecompleted; // The only change.

        // Check that the expected test data is in a INVALID state, specifically the completion/window open dates don't match.
        $errors = certif_get_completion_errors($expectedcertcompletion, $expectedprogcompletion);
        $this->assertEquals(array('error:statecertified-timewindowopenstimecompletednotordered' => 'timewindowopens'), $errors);

        certif_fix_cert_completion_date($testcertcompletion, $testprogcompletion);

        $this->assertEquals($expectedcertcompletion, $testcertcompletion);
        $this->assertEquals($expectedprogcompletion, $testprogcompletion);
    }

    /**
     * Test certif_fix_prog_timedue. Set the program due date to COMPLETION_TIME_NOT_SET.
     */
    public function test_certif_fix_prog_timedue() {
        // Expected record is newly assign.
        $expectedcertcompletion = new stdClass();
        $expectedcertcompletion->id = 1001;
        $expectedcertcompletion->certifid = 1002;
        $expectedcertcompletion->userid = 1003;
        $expectedcertcompletion->certifpath = CERTIFPATH_CERT;
        $expectedcertcompletion->status = CERTIFSTATUS_ASSIGNED;
        $expectedcertcompletion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $expectedcertcompletion->timecompleted = 0;
        $expectedcertcompletion->timewindowopens = 0;
        $expectedcertcompletion->timeexpires = 0;
        $expectedcertcompletion->baselinetimeexpires = 0;

        $expectedprogcompletion = new stdClass();
        $expectedprogcompletion->id = 1007;
        $expectedprogcompletion->programid = 1008;
        $expectedprogcompletion->userid = 1003;
        $expectedprogcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $expectedprogcompletion->timestarted = 0;
        $expectedprogcompletion->timedue = COMPLETION_TIME_NOT_SET;
        $expectedprogcompletion->timecompleted = 0;

        // Check that the expected test data is in a valid state.
        $errors = certif_get_completion_errors($expectedcertcompletion, $expectedprogcompletion);
        $this->assertEquals(array(), $errors);

        $certcompletion = clone($expectedcertcompletion);
        $progcompletion = clone($expectedprogcompletion);

        // Change the record so that the program completion record is wrong.
        $progcompletion->timedue = COMPLETION_TIME_UNKNOWN;

        certif_fix_prog_timedue($certcompletion, $progcompletion);

        // Check that the record was changed as expected (back to COMPLETION_TIME_NOT_SET).
        $this->assertEquals($expectedcertcompletion, $certcompletion);
        $this->assertEquals($expectedprogcompletion, $progcompletion);
    }

    /**
     * Test certif_fix_prog_completion_date. Set the program completion date to match certification completion date.
     */
    public function test_certif_fix_prog_completion_date() {
        // Expected record is certified, before window opens.
        $expectedcertcompletion = new stdClass();
        $expectedcertcompletion->id = 1001;
        $expectedcertcompletion->certifid = 1002;
        $expectedcertcompletion->userid = 1003;
        $expectedcertcompletion->certifpath = CERTIFPATH_RECERT;
        $expectedcertcompletion->status = CERTIFSTATUS_COMPLETED;
        $expectedcertcompletion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $expectedcertcompletion->timecompleted = 1004;
        $expectedcertcompletion->timewindowopens = 1005;
        $expectedcertcompletion->timeexpires = 1006;
        $expectedcertcompletion->baselinetimeexpires = 1006;

        $expectedprogcompletion = new stdClass();
        $expectedprogcompletion->id = 1007;
        $expectedprogcompletion->programid = 1008;
        $expectedprogcompletion->userid = 1003;
        $expectedprogcompletion->status = STATUS_PROGRAM_COMPLETE;
        $expectedprogcompletion->timestarted = 1009;
        $expectedprogcompletion->timedue = 1006;
        $expectedprogcompletion->timecompleted = 1004;

        // Check that the expected test data is in a valid state.
        $errors = certif_get_completion_errors($expectedcertcompletion, $expectedprogcompletion);
        $this->assertEquals(array(), $errors);

        $certcompletion = clone($expectedcertcompletion);
        $progcompletion = clone($expectedprogcompletion);
        // Change the record so that the program completion record is wrong.
        $progcompletion->timecompleted = 987123;

        certif_fix_prog_completion_date($certcompletion, $progcompletion);

        $this->assertEquals($expectedcertcompletion, $certcompletion);
        $this->assertEquals($expectedprogcompletion, $progcompletion);
    }

    /**
     * Test certif_fix_completion_prog_incomplete. Set the program completion record to incomplete, to match cert completion.
     */
    public function test_certif_fix_completion_prog_incomplete() {
        // Expected record is certified, before window opens.
        $expectedcertcompletion = new stdClass();
        $expectedcertcompletion->id = 1001;
        $expectedcertcompletion->certifid = 1002;
        $expectedcertcompletion->userid = 1003;
        $expectedcertcompletion->certifpath = CERTIFPATH_CERT;
        $expectedcertcompletion->status = CERTIFSTATUS_ASSIGNED;
        $expectedcertcompletion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $expectedcertcompletion->timecompleted = 0;
        $expectedcertcompletion->timewindowopens = 0;
        $expectedcertcompletion->timeexpires = 0;
        $expectedcertcompletion->baselinetimeexpires = 0;

        $expectedprogcompletion = new stdClass();
        $expectedprogcompletion->id = 1007;
        $expectedprogcompletion->programid = 1008;
        $expectedprogcompletion->userid = 1003;
        $expectedprogcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $expectedprogcompletion->timestarted = 1009;
        $expectedprogcompletion->timedue = 1006;
        $expectedprogcompletion->timecompleted = 0;

        // Check that the expected test data is in a valid state.
        $errors = certif_get_completion_errors($expectedcertcompletion, $expectedprogcompletion);
        $this->assertEquals(array(), $errors);

        $certcompletion = clone($expectedcertcompletion);
        $progcompletion = clone($expectedprogcompletion);
        // Change the record so that the program completion record is wrong.
        $progcompletion->status = STATUS_PROGRAM_COMPLETE;
        $progcompletion->timecompleted = 12345;

        certif_fix_completion_prog_incomplete($certcompletion, $progcompletion);

        $this->assertEquals($expectedcertcompletion, $certcompletion);
        $this->assertEquals($expectedprogcompletion, $progcompletion);
    }

    /**
     * Test certif_fix_expired_missing_timedue. Set the prog_completion
     */
    public function test_certif_fix_expired_missing_timedue() {
        global $DB;

        $this->resetAfterTest(true);

        // Expected record is certified, before window opens.
        $expectedcertcompletion = new stdClass();
        $expectedcertcompletion->id = 1001;
        $expectedcertcompletion->certifid = 1002;
        $expectedcertcompletion->userid = 1003;
        $expectedcertcompletion->certifpath = CERTIFPATH_CERT;
        $expectedcertcompletion->status = CERTIFSTATUS_EXPIRED;
        $expectedcertcompletion->renewalstatus = CERTIFRENEWALSTATUS_EXPIRED;
        $expectedcertcompletion->timecompleted = 0;
        $expectedcertcompletion->timewindowopens = 0;
        $expectedcertcompletion->timeexpires = 0;
        $expectedcertcompletion->baselinetimeexpires = 0;

        $expectedprogcompletion = new stdClass();
        $expectedprogcompletion->id = 1007;
        $expectedprogcompletion->programid = 1008;
        $expectedprogcompletion->userid = 1003;
        $expectedprogcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $expectedprogcompletion->timestarted = 1009;
        $expectedprogcompletion->timedue = 9003; // This is the date that will be restored from history.
        $expectedprogcompletion->timecompleted = 0;

        // Check that the expected test data is in a valid state.
        $errors = certif_get_completion_errors($expectedcertcompletion, $expectedprogcompletion);
        $this->assertEquals(array(), $errors);

        $certcompletion = clone($expectedcertcompletion);
        $progcompletion = clone($expectedprogcompletion);
        // Change the record so that the program completion record is wrong.
        $progcompletion->timedue = -1;

        // The first three calls to the function will not make any changes.
        $brokencertcompletion = clone($certcompletion);
        $brokenprogcompletion = clone($progcompletion);

        // First check that the function works correctly if there are no history records to restore.
        $DB->delete_records('prog_completion_log');

        $result = certif_fix_expired_missing_timedue($certcompletion, $progcompletion);

        // No change has been made.
        $this->assertEquals($brokencertcompletion, $certcompletion);
        $this->assertEquals($brokenprogcompletion, $progcompletion);

        // The log contains a warning.
        $this->assertStringStartsWith('Automated fix \'certif_fix_expired_missing_timedue\' was not applied because no history record existed', $result);

        // Second check that the function works correctly if history exists but doesn't contain an expiry date.
        $DB->delete_records('prog_completion_log');

        $certcompletionhistory = new stdClass();
        $certcompletionhistory->certifid = 1002;
        $certcompletionhistory->userid = 1003;
        $certcompletionhistory->certifpath = CERTIFPATH_CERT;
        $certcompletionhistory->status = CERTIFSTATUS_EXPIRED;
        $certcompletionhistory->renewalstatus = CERTIFRENEWALSTATUS_EXPIRED;
        $certcompletionhistory->timecompleted = 0;
        $certcompletionhistory->timewindowopens = 0;
        $certcompletionhistory->timemodified = 1007;
        $certcompletionhistory->timeexpires = 0;
        $DB->insert_record('certif_completion_history', $certcompletionhistory);

        $result = certif_fix_expired_missing_timedue($certcompletion, $progcompletion);

        // No change has been made.
        $this->assertEquals($brokencertcompletion, $certcompletion);
        $this->assertEquals($brokenprogcompletion, $progcompletion);

        // The log contains a warning.
        $this->assertStringStartsWith('Automated fix \'certif_fix_expired_missing_timedue\' was not applied because no history record existed', $result);

        // Third check that the function works correctly if history exists but history date is in the future.
        $DB->delete_records('prog_completion_log');

        $certcompletionhistory = new stdClass();
        $certcompletionhistory->certifid = 1002;
        $certcompletionhistory->userid = 1003;
        $certcompletionhistory->certifpath = CERTIFPATH_RECERT;
        $certcompletionhistory->status = CERTIFSTATUS_COMPLETED;
        $certcompletionhistory->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletionhistory->timecompleted = 1004;
        $certcompletionhistory->timewindowopens = 1005;
        $certcompletionhistory->timemodified = 1007;
        $certcompletionhistory->timeexpires = time() + DAYSECS * 12; // Future.
        $DB->insert_record('certif_completion_history', $certcompletionhistory);

        $result = certif_fix_expired_missing_timedue($certcompletion, $progcompletion);

        // No change has been made.
        $this->assertEquals($brokencertcompletion, $certcompletion);
        $this->assertEquals($brokenprogcompletion, $progcompletion);

        // The log contains a warning.
        $this->assertStringStartsWith('Automated fix \'certif_fix_expired_missing_timedue\' was not applied because no history record existed', $result);

        // Last check that the function works correctly if history exists which can be restored.
        $DB->delete_records('prog_completion_log');

        $certcompletionhistory = new stdClass();
        $certcompletionhistory->certifid = 1002;
        $certcompletionhistory->userid = 1003;
        $certcompletionhistory->certifpath = CERTIFPATH_RECERT;
        $certcompletionhistory->status = CERTIFSTATUS_COMPLETED;
        $certcompletionhistory->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletionhistory->timecompleted = 1004;
        $certcompletionhistory->timewindowopens = 1005;
        $certcompletionhistory->timemodified = 1007;
        $certcompletionhistory->timeexpires = 9001;
        $DB->insert_record('certif_completion_history', $certcompletionhistory);
        $certcompletionhistory->timeexpires = 9003; // This one should be seleted.
        $DB->insert_record('certif_completion_history', $certcompletionhistory);
        $certcompletionhistory->timeexpires = 9002;
        $DB->insert_record('certif_completion_history', $certcompletionhistory);

        $result = certif_fix_expired_missing_timedue($certcompletion, $progcompletion);

        // The fix has been applied.
        $this->assertEquals($expectedcertcompletion, $certcompletion);
        $this->assertEquals($expectedprogcompletion, $progcompletion);

        // The log contains a warning.
        $this->assertStringStartsWith('Automated fix \'certif_fix_expired_missing_timedue\' was applied', $result);
    }

    public function test_certif_load_completion() {
        global $DB;

        $this->setup_completions();

        // Manually retrieve the records and compare to the records returned by the function.
        $certcompletions = $DB->get_records('certif_completion');
        foreach ($certcompletions as $exectedcertcompletion) {
            $sql = "SELECT pc.*
                      FROM {prog_completion} pc
                      JOIN {prog} prog ON prog.id = pc.programid
                     WHERE prog.certifid = :certifid AND pc.userid = :userid AND pc.coursesetid = 0";
            $params = array('certifid' => $exectedcertcompletion->certifid, 'userid' => $exectedcertcompletion->userid);
            $expectedprogcompletion = $DB->get_record_sql($sql, $params);
            list($certcompletion, $progcompletion) =
                certif_load_completion($expectedprogcompletion->programid, $exectedcertcompletion->userid);
            $this->assertEquals($exectedcertcompletion, $certcompletion);
            $this->assertEquals($expectedprogcompletion, $progcompletion);
        }

        // Check that an exception is generated if the records don't exist.
        try {
            list($certcompletion, $progcompletion) = certif_load_completion(1234321, -5);
            $this->assertEquals("Shouldn't reach this code, exception not triggered!", $certcompletion);
        } catch (exception $e) {
            $a = array('programid' => 1234321, 'userid' => -5);
            $this->assertContains(get_string('error:cannotloadcompletionrecords', 'totara_certification', $a), $e->getMessage());
        }
    }

    /**
     * Test certif_write_completion_log. Quick and simple, just make sure the params are used to create a matching record.
     */
    public function test_certif_write_completion_log() {
        global $DB;

        $this->setup_completions();

        $prog = $this->certifications[4];
        $user = $this->users[10];
        $changeuser = $this->users[1];

        // Use another user as the "changeuser", to identify the record and to check the "changeuser" functionality.
        certif_write_completion_log($prog->id, $user->id, "test_certif_write_completion_log", $changeuser->id);

        $logs = $DB->get_records('prog_completion_log', array('changeuserid' => $changeuser->id));
        $this->assertEquals(1, count($logs));
        $log = reset($logs);
        $this->assertEquals($prog->id, $log->programid);
        $this->assertEquals($user->id, $log->userid);
        $this->assertStringStartsWith("test_certif_write_completion_log", $log->description);
        $this->assertGreaterThan(0, strpos($log->description, 'Status'));
        $this->assertGreaterThan(0, strpos($log->description, 'Renewal status'));
        $this->assertGreaterThan(0, strpos($log->description, 'Certification path'));
        $this->assertGreaterThan(0, strpos($log->description, 'Time started'));
        $this->assertGreaterThan(0, strpos($log->description, 'Due date'));
        $this->assertGreaterThan(0, strpos($log->description, 'Completion date'));
        $this->assertGreaterThan(0, strpos($log->description, 'Window open date'));
        $this->assertGreaterThan(0, strpos($log->description, 'Expiry date'));
        $this->assertGreaterThan(0, strpos($log->description, 'Program status'));
        $this->assertGreaterThan(0, strpos($log->description, 'Program completion date'));
    }

    /**
     * Test certif_write_completion_history_log. Quick and simple, just make sure the params are used to create a matching record.
     */
    public function test_certif_write_completion_history_log() {
        global $DB;

        $this->setup_completions();

        // Copy current completion records into history. Note that there's only one per user/cert.
        $certcompletions = $DB->get_records('certif_completion');
        foreach ($certcompletions as $certcompletion) {
            copy_certif_completion_to_hist($certcompletion->certifid, $certcompletion->userid);
        }

        // Check that all history records are valid.
        $histcompletions = $DB->get_records('certif_completion_history');
        foreach ($histcompletions as $histcompletion) {
            $errors = certif_get_completion_errors($histcompletion, null);
            $this->assertEquals(array(), $errors);
        }
        $this->assertEquals($this->numtestusers * $this->numtestcerts, count($histcompletions));

        $prog = $this->certifications[4];
        $user = $this->users[10];
        // Use another user as the "changeuser", to identify the record and to check the "changeuser" functionality.
        $changeuser = $this->users[1];

        $chid = $DB->get_field('certif_completion_history', 'id', array('certifid' => $prog->certifid, 'userid' => $user->id));
        certif_write_completion_history_log($chid, "test_certif_write_completion_history_log", $changeuser->id);

        $logs = $DB->get_records('prog_completion_log', array('changeuserid' => $changeuser->id));
        $this->assertEquals(1, count($logs));
        $log = reset($logs);
        $this->assertEquals($prog->id, $log->programid);
        $this->assertEquals($user->id, $log->userid);
        $this->assertStringStartsWith("test_certif_write_completion_history_log", $log->description);
        $this->assertFalse(strpos($log->description, 'Program completion date'));
    }

    /**
     * Test that certif_delete_completion_history is deleting the correct record.
     */
    public function test_certif_delete_completion_history() {
        global $DB;

        $this->setup_completions();

        // Copy current completion records into history. Note that there's only one per user/cert.
        $certcompletions = $DB->get_records('certif_completion');
        foreach ($certcompletions as $certcompletion) {
            copy_certif_completion_to_hist($certcompletion->certifid, $certcompletion->userid);
        }

        $timecompleted = 191919;

        // Change current completions to 'certifed'.
        $this->shift_completions_to_certified($timecompleted);

        // Copy current completion records into history. These are different from the previous records.
        $certcompletions = $DB->get_records('certif_completion');
        foreach ($certcompletions as $certcompletion) {
            copy_certif_completion_to_hist($certcompletion->certifid, $certcompletion->userid);
        }

        // Check that all history records are valid.
        $histcompletions = $DB->get_records('certif_completion_history');
        foreach ($histcompletions as $histcompletion) {
            $errors = certif_get_completion_errors($histcompletion, null);
            $this->assertEquals(array(), $errors);
        }
        $this->assertEquals($this->numtestusers * $this->numtestcerts * 2, count($histcompletions)); // Two per user/cert.

        $deleteprog = $this->certifications[2];
        $deleteuser = $this->users[10];

        $assigned = $DB->get_record('certif_completion_history',
            array('certifid' => $deleteprog->certifid, 'userid' => $deleteuser->id, 'status' => CERTIFSTATUS_ASSIGNED));

        // Delete only the 'assigned' completion history record for the user/cert.
        certif_delete_completion_history($assigned->id);

        // Make sure that all other records are still there.
        $count = 0;
        for ($u = 1; $u <= $this->numtestusers; $u++) {
            for ($c = 1; $c <= $this->numtestcerts; $c++) {
                $prog = $this->certifications[$c];
                $user = $this->users[$u];
                $records = $DB->get_records('certif_completion_history',
                    array('certifid' => $prog->certifid, 'userid' => $user->id));
                if ($c == 2 && $u == 10) {
                    $this->assertEquals(1, count($records));
                    $completed = reset($records);
                    // Make sure it was the 'assigned' record that was deleted, not the 'completed'.
                    $this->assertEquals(CERTIFSTATUS_COMPLETED, $completed->status);
                    $count += 1;
                } else {
                    $this->assertEquals(2, count($records));
                    $count += 2;
                }
            }
        }
        $this->assertEquals($this->numtestusers * $this->numtestcerts * 2 - 1, $count);
    }
}
