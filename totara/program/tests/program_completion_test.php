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
 * @package totara_program
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot . '/totara/program/lib.php');

/**
 * Program module PHPUnit archive test class.
 *
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose totara_program_program_completion_testcase totara/program/tests/program_completion_test.php
 */
class totara_program_program_completion_testcase extends reportcache_advanced_testcase {

    public $users = array();
    public $programs = array();
    public $certifications = array();
    public $numtestusers = 10;
    public $numtestprogs = 10;
    public $numtestcerts = 7;

    protected function tearDown() {
        $this->users = null;
        $this->programs = null;
        $this->certifications = null;
        $this->numtestusers = null;
        $this->numtestprogs = null;
        $this->numtestcerts = null;
        parent::tearDown();
    }

    /**
     * Set up the users, certifications and completions.
     */
    public function setup_completions() {
        $this->resetAfterTest(true);

        // Turn off certifications. This is to test that it doesn't interfere with program completion.
        set_config('enablecertifications', TOTARA_DISABLEFEATURE);

        // Create users.
        for ($i = 1; $i <= $this->numtestusers; $i++) {
            $this->users[$i] = $this->getDataGenerator()->create_user();
        }

        // Create certifications, mostly so that we don't end up with coincidental success due to matching ids.
        for ($i = 1; $i <= $this->numtestcerts; $i++) {
            $this->certifications[$i] = $this->getDataGenerator()->create_certification();
        }

        // Create programs.
        for ($i = 1; $i <= $this->numtestprogs; $i++) {
            $this->programs[$i] = $this->getDataGenerator()->create_program();
            $coursesetdata = array(
                array(
                    'type' => CONTENTTYPE_MULTICOURSE,
                    'nextsetoperator' => NEXTSETOPERATOR_THEN,
                    'completiontype' => COMPLETIONTYPE_ALL,
                    'certifpath' => CERTIFPATH_CERT,
                    'timeallowed' => 123123,
                    'courses' => array($this->getDataGenerator()->create_course()),
                ),
            );
            $this->getDataGenerator()->create_coursesets_in_program($this->programs[$i], $coursesetdata);
        }

        // Assign users to the programs as individuals.
        foreach ($this->users as $user) {
            foreach ($this->programs as $prog) {
                $this->getDataGenerator()->assign_to_program($prog->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
            }
        }
    }

    /**
     * Data provider for test_prog_get_completion_errors.
     */
    public function data_prog_get_completion_errors() {
        return array(
            // Really wrong prog completion status.
            array('courseset status complete',
                array('status' => STATUS_COURSESET_COMPLETE, // 1: Impossible value.
                    'timecompleted' => 0,
                    'timedue' => 1001),
                array('error:progstatusinvalid' => 'status') // 1.
            ),
            // Really wrong prog completion status.
            array('courseset status incomplete',
                array('status' => STATUS_COURSESET_INCOMPLETE, // 1: Impossible value.
                    'timecompleted' => 0,
                    'timedue' => 1001),
                array('error:progstatusinvalid' => 'status') // 1.
            ),
            // Incomplete, problems.
            array('incomplete problems',
                array('status' => STATUS_PROGRAM_INCOMPLETE,
                    'timecompleted' => 1004, // 1: Should be 0.
                    'timedue' => COMPLETION_TIME_UNKNOWN), // 2: Should be anything else.
                array('error:stateincomplete-timecompletednotempty' => 'timecompleted', // 1.
                    'error:timedueunknown' => 'timedue') // 2.
            ),
            // Incomplete, no problems #1.
            array('incomplete correct 1',
                array('status' => STATUS_PROGRAM_INCOMPLETE,
                    'timecompleted' => 0,
                    'timedue' => COMPLETION_TIME_NOT_SET),
                array()
            ),
            // Incomplete, no problems #2.
            array('incomplete correct 2',
                array('status' => STATUS_PROGRAM_INCOMPLETE,
                    'timecompleted' => 0,
                    'timedue' => 1001),
                array()
            ),
            // Complete, problems #1.
            array('complete problems 1',
                array('status' => STATUS_PROGRAM_COMPLETE,
                    'timecompleted' => 0, // 1: Should be > 0.
                    'timedue' => COMPLETION_TIME_UNKNOWN), // 2: Should be anything else.
                array('error:statecomplete-timecompletedempty' => 'timecompleted', // 1.
                    'error:timedueunknown' => 'timedue') // 2.
            ),
            // Complete, problems #2.
            array('complete problems 2',
                array('status' => STATUS_PROGRAM_COMPLETE,
                    'timecompleted' => -1, // 1: Should be > 0.
                    'timedue' => COMPLETION_TIME_NOT_SET),
                array('error:statecomplete-timecompletedempty' => 'timecompleted') // 1.
            ),
            // Complete, no problems #1.
            array('complete correct 1',
                array('status' => STATUS_PROGRAM_COMPLETE,
                    'timecompleted' => 1001,
                    'timedue' => COMPLETION_TIME_NOT_SET),
                array()
            ),
            // Complete, no problems #2.
            array('complete correct 2',
                array('status' => STATUS_PROGRAM_COMPLETE,
                    'timecompleted' => 1001,
                    'timedue' => 1002),
                array()
            ),
        );
    }

    /**
     * Test prog_get_completion_errors with current completion record.
     *
     * @dataProvider data_prog_get_completion_errors
     */
    public function test_prog_get_completion_errors($debugkey, $progcompletion, $expectederrors) {
        $errors = prog_get_completion_errors((object)$progcompletion);
        $this->assertEquals($expectederrors, $errors);
    }

    /**
     * Test prog_get_completion_form_errors. Quick and simple, just to make sure it switches the data around correctly.
     */
    public function test_prog_get_completion_form_errors() {
        $rawerrors = array(
            'error:timedueunknown' => 'timedue',
            'error:stateincomplete-timecompletednotempty' => 'timecompleted'
        );
        $expectederrors = array(
            'timedue' => get_string('error:timedueunknown', 'totara_program'),
            'timecompleted' => get_string('error:stateincomplete-timecompletednotempty', 'totara_program')
        );
        $formerrors = prog_get_completion_form_errors($rawerrors);
        $this->assertEquals($expectederrors, $formerrors);
    }

    /**
     * Test prog_get_completion_error_problemkey. Quick and simple, just to make sure it switches the data around correctly.
     */
    public function test_prog_get_completion_error_problemkey() {
        $rawerrors = array(
            'error:timedueunknown' => 'timedue',
            'error:stateincomplete-timecompletednotempty' => 'timecompleted'
        );
        $expectedproblemkey = 'error:stateincomplete-timecompletednotempty|error:timedueunknown';
        $problemkey = prog_get_completion_error_problemkey($rawerrors);
        $this->assertEquals($expectedproblemkey, $problemkey);
    }

    /**
     * Test prog_load_completion.
     */
    public function test_prog_load_completion() {
        global $DB;

        $this->setup_completions();

        // Manually retrieve the records and compare to the records returned by the function.
        $progcompletions = $DB->get_records('prog_completion', array('coursesetid' => 0));
        foreach ($progcompletions as $expectedprogcompletion) {
            $progcompletion = prog_load_completion($expectedprogcompletion->programid, $expectedprogcompletion->userid);
            $this->assertEquals($expectedprogcompletion, $progcompletion);
        }

        // Check that an exception is generated if the records don't exist.
        try {
            $progcompletion = prog_load_completion(1234321, -5);
            $this->assertEquals("Shouldn't reach this code, exception not triggered!", $progcompletion);
        } catch (exception $e) {
            $a = array('programid' => 1234321, 'userid' => -5);
            $this->assertContains(get_string('error:cannotloadcompletionrecord', 'totara_program', $a), $e->getMessage());
        }
    }

    /**
     * Test that prog_write_completion causes exceptions when expected (for faults that are caused by bad code).
     */
    public function test_prog_write_completion_exceptions() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();

        // Check that all records are valid.
        $progcompletions = $DB->get_records('prog_completion', array('coursesetid' => 0));
        foreach ($progcompletions as $progcompletion) {
            $errors = prog_get_completion_errors($progcompletion);
            $this->assertEquals(array(), $errors);
        }
        $this->assertEquals($this->numtestusers * $this->numtestprogs, count($progcompletions));

        $prog1 = $this->programs[5];
        $prog2 = $this->programs[9];
        $user1 = $this->users[2];
        $user2 = $this->users[3];

        // Update, everything is correct (load and save the same records).
        $progcompletion = prog_load_completion($prog1->id, $user1->id);
        $result = prog_write_completion($progcompletion);
        $this->assertEquals(true, $result);

        // Trying to insert when the records already exist.
        $progcompletion = prog_load_completion($prog1->id, $user1->id);
        unset($progcompletion->id);
        try {
            prog_write_completion($progcompletion);
            $this->fail("Shouldn't reach this code, exception not triggered!");
        } catch (exception $e) {
            $this->assertStringStartsWith('error/Call to prog_write_completion with completion record that does not match the existing record', $e->getMessage());
        }

        // Update, but records don't match the database #1.
        $progcompletion = prog_load_completion($prog1->id, $user1->id);
        $progcompletion->programid = $prog2->id;
        try {
            prog_write_completion($progcompletion);
            $this->fail("Shouldn't reach this code, exception not triggered!");
        } catch (exception $e) {
            $this->assertStringStartsWith('error/Call to prog_write_completion with completion record that does not match the existing record', $e->getMessage());
        }

        // Update, but records don't match the database #2.
        $progcompletion = prog_load_completion($prog1->id, $user1->id);
        $progcompletion->userid = $user2->id;
        try {
            prog_write_completion($progcompletion);
            $this->fail("Shouldn't reach this code, exception not triggered!");
        } catch (exception $e) {
            $this->assertStringStartsWith('error/Call to prog_write_completion with completion record that does not match the existing record', $e->getMessage());
        }
    }

    /**
     * Test that prog_write_completion writes the data correctly and returns true or false.
     */
    public function test_prog_write_completion() {
        global $DB;

        // Set up some data that is valid.
        $beforeassigned = time();
        $this->setup_completions();
        $afterassigned = time();

        $emptyprog = $this->programs[1];
        $emptyuser = $this->users[9];
        $anotherprog = $this->programs[5];
        $anotheruser = $this->users[6];

        // Remove all completion records for one program.
        $DB->delete_records('prog_completion', array('programid' => $emptyprog->id, 'coursesetid' => 0));

        // Remove all completion records for one user.
        $DB->delete_records('prog_completion', array('userid' => $emptyuser->id, 'coursesetid' => 0));

        // Check that all remaining records are valid.
        $progcompletions = $DB->get_records('prog_completion', array('coursesetid' => 0));
        foreach ($progcompletions as $progcompletion) {
            $errors = prog_get_completion_errors($progcompletion);
            $this->assertEquals(array(), $errors);
        }
        // Think of it as a grid - we deleted one row and one column.
        $this->assertEquals(($this->numtestusers - 1) * ($this->numtestprogs - 1), count($progcompletions));

        $progcompletioncompletedtemplate = new stdClass();
        $progcompletioncompletedtemplate->id = 0;
        $progcompletioncompletedtemplate->status = STATUS_PROGRAM_COMPLETE;
        $progcompletioncompletedtemplate->timedue = 1003;
        $progcompletioncompletedtemplate->timecompleted = 1001;
        $progcompletioncompletedtemplate->organisationid = 13;
        $progcompletioncompletedtemplate->positionid = 14;

        // Add completion for empty program, empty user, but with invalid data.
        $progcompletion = clone($progcompletioncompletedtemplate);
        $progcompletion->programid = $emptyprog->id;
        $progcompletion->userid = $emptyuser->id;
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE; // Invalid.

        $errors = prog_get_completion_errors($progcompletion);
        $this->assertEquals(array('error:stateincomplete-timecompletednotempty' => 'timecompleted'), $errors);
        $result = prog_write_completion($progcompletion);
        $this->assertEquals(false, $result); // Fails to write (but doesn't cause exception)!

        // Add completion for empty program, empty user.
        $progcompletion = clone($progcompletioncompletedtemplate);
        $progcompletion->programid = $emptyprog->id;
        $progcompletion->userid = $emptyuser->id;

        $errors = prog_get_completion_errors($progcompletion);
        $this->assertEquals(array(), $errors);
        $result = prog_write_completion($progcompletion);
        $this->assertEquals(true, $result);

        // Add completion for empty program, another user.
        $progcompletion = clone($progcompletioncompletedtemplate);
        $progcompletion->programid = $emptyprog->id;
        $progcompletion->userid = $anotheruser->id;

        $errors = prog_get_completion_errors($progcompletion);
        $this->assertEquals(array(), $errors);
        $result = prog_write_completion($progcompletion);
        $this->assertEquals(true, $result);

        // Add completion for another program, empty user.
        $progcompletion = clone($progcompletioncompletedtemplate);
        $progcompletion->programid = $anotherprog->id;
        $progcompletion->userid = $emptyuser->id;

        $errors = prog_get_completion_errors($progcompletion);
        $this->assertEquals(array(), $errors);
        $result = prog_write_completion($progcompletion);
        $this->assertEquals(true, $result);

        // Check that all records are correct (original are assigned, extras are completed).
        $progcompletions = $DB->get_records('prog_completion', array('coursesetid' => 0));
        foreach ($progcompletions as $progcompletion) {
            $errors = prog_get_completion_errors($progcompletion);
            $this->assertEquals(array(), $errors);

            // Determine which type of record to expect.
            if ($progcompletion->programid == $emptyprog->id && $progcompletion->userid == $emptyuser->id ||
                $progcompletion->programid == $emptyprog->id && $progcompletion->userid == $anotheruser->id ||
                $progcompletion->programid == $anotherprog->id && $progcompletion->userid == $emptyuser->id) {

                $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
                $this->assertEquals(1003, $progcompletion->timedue);
                $this->assertEquals(1001, $progcompletion->timecompleted);
                $this->assertEquals(13, $progcompletion->organisationid);
                $this->assertEquals(14, $progcompletion->positionid);
            } else {
                $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);
                $this->assertEquals(-1, $progcompletion->timedue);
                $this->assertEquals(0, $progcompletion->timecompleted);
                $this->assertEquals(0, $progcompletion->organisationid);
                $this->assertEquals(0, $progcompletion->positionid);
            }
        }
        // We re-added 3 items to the grid, one on the intersection, one on column, one on row.
        $this->assertEquals(($this->numtestusers - 1) * ($this->numtestprogs - 1) + 3, count($progcompletions));
    }

    /**
     * Test that prog_write_courseset_completion causes exceptions when expected (for faults that are caused by bad code).
     */
    public function test_prog_write_courseset_completion_exceptions() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();

        $prog1 = $this->programs[5];
        $csid1 = $DB->get_field('prog_courseset', 'id', array('programid' => $prog1->id));
        $prog2 = $this->programs[9];
        $csid2 = $DB->get_field('prog_courseset', 'id', array('programid' => $prog2->id));
        $user1 = $this->users[2];
        $user2 = $this->users[3];

        // Update, everything is correct (load and save the same records).
        $cscompletion = prog_load_courseset_completion($csid1, $user1->id);
        prog_write_courseset_completion($cscompletion);

        // Trying to insert when the records already exist.
        $cscompletion = prog_load_courseset_completion($csid1, $user1->id);
        unset($cscompletion->id);
        try {
            prog_write_courseset_completion($cscompletion);
            $this->fail("Shouldn't reach this code, exception not triggered!");
        } catch (exception $e) {
            $this->assertStringStartsWith('error/Call to prog_write_courseset_completion insert with completion record that does not match the existing record', $e->getMessage());
        }

        // Update, but records don't match the database #1.
        $cscompletion = prog_load_courseset_completion($csid1, $user1->id);
        $cscompletion->coursesetid = $csid2;
        try {
            prog_write_courseset_completion($cscompletion);
            $this->fail("Shouldn't reach this code, exception not triggered!");
        } catch (exception $e) {
            $this->assertStringStartsWith('error/Call to prog_write_courseset_completion update with completion record that does not match the existing record', $e->getMessage());
        }

        // Update, but records don't match the database #2.
        $cscompletion = prog_load_courseset_completion($csid1, $user1->id);
        $cscompletion->userid = $user2->id;
        try {
            prog_write_courseset_completion($cscompletion);
            $this->fail("Shouldn't reach this code, exception not triggered!");
        } catch (exception $e) {
            $this->assertStringStartsWith('error/Call to prog_write_courseset_completion update with completion record that does not match the existing record', $e->getMessage());
        }

        // Update, but records don't match the database #3.
        $cscompletion = prog_load_courseset_completion($csid1, $user1->id);
        $cscompletion->programid = $prog2->id;
        try {
            prog_write_courseset_completion($cscompletion);
            $this->fail("Shouldn't reach this code, exception not triggered!");
        } catch (exception $e) {
            $this->assertStringStartsWith('error/Call to prog_write_courseset_completion update with completion record that does not match the existing record', $e->getMessage());
        }
    }

    /**
     * Test that prog_write_courseset_completion writes the data correctly and returns true or false.
     */
    public function test_prog_write_courseset_completion() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();

        $emptyprog = $this->programs[1];
        $emptyuser = $this->users[9];
        $emptycsid = $DB->get_field('prog_courseset', 'id', array('programid' => $emptyprog->id));
        $anotherprog = $this->programs[5];
        $anotheruser = $this->users[6];
        $anothercsid = $DB->get_field('prog_courseset', 'id', array('programid' => $anotherprog->id));

        // Remove all completion records for one program.
        $DB->delete_records_select('prog_completion', "programid = :programid AND coursesetid > 0", array('programid' => $emptyprog->id));

        // Remove all completion records for one user.
        $DB->delete_records_select('prog_completion', "userid = :userid AND coursesetid > 0", array('userid' => $emptyuser->id));

        // Think of it as a grid - we deleted one row and one column.
        $cscompletions = $DB->get_records_select('prog_completion', "coursesetid > 0");
        $this->assertEquals(($this->numtestusers - 1) * ($this->numtestprogs - 1), count($cscompletions));

        $cscompletioncompletedtemplate = new stdClass();
        $cscompletioncompletedtemplate->id = 0;
        $cscompletioncompletedtemplate->status = STATUS_COURSESET_COMPLETE;
        $cscompletioncompletedtemplate->timedue = 1003;
        $cscompletioncompletedtemplate->timecompleted = 1001;

        // Add course set completion for empty program, empty user, but with invalid data.
        $cscompletion = clone($cscompletioncompletedtemplate);
        $cscompletion->programid = $emptyprog->id;
        $cscompletion->coursesetid = $emptycsid;
        $cscompletion->userid = $emptyuser->id;
        $cscompletion->status = STATUS_PROGRAM_INCOMPLETE; // Invalid.

        $result = prog_write_courseset_completion($cscompletion);
        $this->assertEquals(false, $result); // Fails to write (but doesn't cause exception)!

        // Add completion for empty program, empty user.
        $cscompletion = clone($cscompletioncompletedtemplate);
        $cscompletion->programid = $emptyprog->id;
        $cscompletion->coursesetid = $emptycsid;
        $cscompletion->userid = $emptyuser->id;

        $result = prog_write_courseset_completion($cscompletion);
        $this->assertEquals(true, $result);

        // Add completion for empty program, another user.
        $cscompletion = clone($cscompletioncompletedtemplate);
        $cscompletion->programid = $emptyprog->id;
        $cscompletion->coursesetid = $emptycsid;
        $cscompletion->userid = $anotheruser->id;

        $result = prog_write_courseset_completion($cscompletion);
        $this->assertEquals(true, $result);

        // Add completion for another program, empty user.
        $cscompletion = clone($cscompletioncompletedtemplate);
        $cscompletion->programid = $anotherprog->id;
        $cscompletion->coursesetid = $anothercsid;
        $cscompletion->userid = $emptyuser->id;

        $result = prog_write_courseset_completion($cscompletion);
        $this->assertEquals(true, $result);

        // Check that all records are correct (original are incomplete, extras are completed).
        $cscompletions = $DB->get_records_select('prog_completion', "coursesetid > 0");
        foreach ($cscompletions as $cscompletion) {
            // Determine which type of record to expect.
            if ($cscompletion->programid == $emptyprog->id && $cscompletion->userid == $emptyuser->id ||
                $cscompletion->programid == $emptyprog->id && $cscompletion->userid == $anotheruser->id ||
                $cscompletion->programid == $anotherprog->id && $cscompletion->userid == $emptyuser->id) {

                $this->assertEquals(STATUS_COURSESET_COMPLETE, $cscompletion->status);
                $this->assertEquals(1003, $cscompletion->timedue);
                $this->assertEquals(1001, $cscompletion->timecompleted);
                $this->assertEquals(0, $cscompletion->organisationid);
                $this->assertEquals(0, $cscompletion->positionid);
            } else {
                $this->assertEquals(STATUS_COURSESET_INCOMPLETE, $cscompletion->status);
                $this->assertEquals(0, $cscompletion->timecompleted);
                $this->assertEquals(0, $cscompletion->organisationid);
                $this->assertEquals(0, $cscompletion->positionid);
            }
        }
        // We re-added 3 items to the grid, one on the intersection, one on column, one on row.
        $this->assertEquals(($this->numtestusers - 1) * ($this->numtestprogs - 1) + 3, count($cscompletions));

        // The program completion records are all still there, right from the start.
        $progcompletions = $DB->get_records('prog_completion', array('coursesetid' => 0));
        $this->assertEquals($this->numtestusers * $this->numtestprogs, count($progcompletions));
    }

    /**
     * Test prog_write_completion_log. Quick and simple, just make sure the params are used to create a matching record.
     */
    public function test_prog_write_completion_log() {
        global $DB;

        $this->setup_completions();

        $prog = $this->programs[4];
        $user = $this->users[10];
        $changeuser = $this->users[1];

        // Use another user as the "changeuser", to identify the record and to check the "changeuser" functionality.
        prog_write_completion_log($prog->id, $user->id, "test_certif_write_completion_log", $changeuser->id);

        $logs = $DB->get_records('prog_completion_log', array('changeuserid' => $changeuser->id));
        $this->assertEquals(1, count($logs));
        $log = reset($logs);
        $this->assertEquals($prog->id, $log->programid);
        $this->assertEquals($user->id, $log->userid);
        $this->assertStringStartsWith("test_certif_write_completion_log", $log->description);
        $this->assertGreaterThan(0, strpos($log->description, 'Status'));
        $this->assertGreaterThan(0, strpos($log->description, 'Time started'));
        $this->assertGreaterThan(0, strpos($log->description, 'Due date'));
        $this->assertGreaterThan(0, strpos($log->description, 'Completion date'));
    }

    /**
     * Test prog_log_completion. Quick and simple, just make sure the params are used to create a matching record.
     */
    public function test_prog_log_completion() {
        global $DB;

        $this->setup_completions();

        $prog = $this->programs[4];
        $user = $this->users[10];
        $changeuser = $this->users[1];

        // Use another user as the "changeuser", to identify the record and to check the "changeuser" functionality.
        prog_log_completion($prog->id, $user->id, "test_certif_write_completion_log", $changeuser->id);

        $logs = $DB->get_records('prog_completion_log', array('changeuserid' => $changeuser->id));
        $this->assertEquals(1, count($logs));
        $log = reset($logs);
        $this->assertEquals($prog->id, $log->programid);
        $this->assertEquals($user->id, $log->userid);
        $this->assertEquals("test_certif_write_completion_log", $log->description);
    }

    public function test_prog_process_submitted_edit_completion() {
        global $DB;

        $this->setup_completions();

        // Select a user and prog to use for the test.
        $user = $this->users[3];
        $prog = $this->programs[6];

        $submitted = new stdClass();
        $submitted->id = $prog->id;
        $submitted->userid = $user->id;
        $submitted->status = 1007;
        $submitted->timeduenotset = 'no';
        $submitted->timedue = 1008;
        $submitted->timecompleted = 1009;

        $timebefore = time();
        $progcompletion = prog_process_submitted_edit_completion($submitted);
        $timeafter = time();

        $progcompletionid = $DB->get_field('prog_completion', 'id', array('programid' => $prog->id, 'userid' => $user->id, 'coursesetid' => 0));

        $this->assertEquals($progcompletionid, $progcompletion->id);
        $this->assertEquals($prog->id, $progcompletion->programid);
        $this->assertEquals($user->id, $progcompletion->userid);
        $this->assertEquals(1007, $progcompletion->status);
        $this->assertEquals(1008, $progcompletion->timedue);
        $this->assertEquals(1009, $progcompletion->timecompleted);
        $this->assertGreaterThanOrEqual($timebefore, $progcompletion->timemodified);
        $this->assertLessThanOrEqual($timeafter, $progcompletion->timemodified);

        // Run a second test, just disable the due date.
        $submitted->timeduenotset = 'yes';
        $submitted->timedue = 56789;

        $timebefore = time();
        $progcompletion = prog_process_submitted_edit_completion($submitted);
        $timeafter = time();

        $progcompletionid = $DB->get_field('prog_completion', 'id', array('programid' => $prog->id, 'userid' => $user->id, 'coursesetid' => 0));

        $this->assertEquals($progcompletionid, $progcompletion->id);
        $this->assertEquals($prog->id, $progcompletion->programid);
        $this->assertEquals($user->id, $progcompletion->userid);
        $this->assertEquals(1007, $progcompletion->status);
        $this->assertEquals(-1, $progcompletion->timedue);
        $this->assertEquals(1009, $progcompletion->timecompleted);
        $this->assertGreaterThanOrEqual($timebefore, $progcompletion->timemodified);
        $this->assertLessThanOrEqual($timeafter, $progcompletion->timemodified);
    }
}
