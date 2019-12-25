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
 * @package totara_job
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/formslib.php');

/**
 * This set of tests covers all methods of the job_assignment class.
 *
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose totara_job_job_assignment_testcase totara/job/tests/job_assignment_test.php
 */
class totara_job_job_assignment_testcase extends advanced_testcase {

    private $users = array();

    protected function tearDown() {
        $this->users = null;
        parent::tearDown();
    }

    /**
     * Set up some stuff that will be useful for most tests.
     */
    public function setUp() {
        parent::setup();
        $this->resetAfterTest();
        $this->setAdminUser();

        for ($i = 1; $i <= 10; $i++) {
            $this->users[$i] = $this->getDataGenerator()->create_user();
        }
    }

    /**
     * Tests create(), create_default(), _get().
     *
     * This test (as well as most others) implicitly tests __construct().
     */
    public function test_create_default_and_create_and_get() {
        global $USER;

        // Create a default manager job assignment.
        $timebefore = time();
        $managerja = \totara_job\job_assignment::create_default($this->users[2]->id);
        $timeafter = time();

        // Try accessing each property.
        $this->assertGreaterThan(0, $managerja->id); // Checks that retrieving the id does not cause a failure.
        $this->assertEquals($this->users[2]->id, $managerja->userid);
        $this->assertEquals(get_string('jobassignmentdefaultfullname', 'totara_job', 1), $managerja->fullname);
        $this->assertNull($managerja->shortname);
        $this->assertEquals("1", $managerja->idnumber);
        $this->assertEquals('', $managerja->description);
        $this->assertGreaterThanOrEqual($timebefore, $managerja->timecreated);
        $this->assertLessThanOrEqual($timeafter, $managerja->timecreated);
        $this->assertGreaterThanOrEqual($timebefore, $managerja->timemodified);
        $this->assertLessThanOrEqual($timeafter, $managerja->timemodified);
        $this->assertEquals($USER->id, $managerja->usermodified);
        $this->assertNull($managerja->positionid);
        $this->assertGreaterThanOrEqual($timebefore, $managerja->positionassignmentdate);
        $this->assertLessThanOrEqual($timeafter, $managerja->positionassignmentdate);
        $this->assertNull($managerja->organisationid);
        $this->assertNull($managerja->startdate);
        $this->assertNull($managerja->enddate);
        $this->assertNull($managerja->managerid);
        $this->assertNull($managerja->managerjaid);
        $this->assertEquals('/' . $managerja->id, $managerja->managerjapath);
        $this->assertNull($managerja->tempmanagerid);
        $this->assertNull($managerja->tempmanagerjaid);
        $this->assertNull($managerja->tempmanagerexpirydate);
        $this->assertNull($managerja->appraiserid);
        $this->assertEquals(1, $managerja->sortorder);

        // Create a temporary manager job assignment and check the optional param available in create_default.
        $data = array(
            'shortname' => 'sn',
            'fullname' => 'fn',
            'positionid' => '1234',
        );
        $tempmanagerja = \totara_job\job_assignment::create_default($this->users[3]->id, $data);
        $this->assertEquals($this->users[2]->id, $managerja->userid);
        $this->assertEquals('fn', $tempmanagerja->fullname);
        $this->assertEquals('sn', $tempmanagerja->shortname);
        $this->assertEquals($managerja->idnumber, $tempmanagerja->idnumber); // Shows that idnumber is not site-wide unique.
        $this->assertEquals('1234', $tempmanagerja->positionid);

        // Create a normal job assignment with all the possible data.
        $data = array(
            'userid' => $this->users[1]->id,
            'fullname' => 'fullname1',
            'shortname' => 'shortname1',
            'idnumber' => 'id1',
            'description' => 'description pre-processed',
            'positionid' => 123,
            'organisationid' => 234,
            'startdate' => 1234567,
            'enddate' => 2345678,
            'managerjaid' => $managerja->id, // User 2.
            'tempmanagerjaid' => $tempmanagerja->id, // User 3.
            'tempmanagerexpirydate' => 3456789,
            'appraiserid' => $this->users[4]->id,
        );
        $timebefore = time();
        $jobassignment = \totara_job\job_assignment::create($data);
        $timeafter = time();

        // Check that the correct data was recorded.
        $this->assertGreaterThan(0, $jobassignment->id);
        $this->assertEquals($data['userid'], $jobassignment->userid);
        $this->assertEquals($data['fullname'], $jobassignment->fullname);
        $this->assertEquals($data['shortname'], $jobassignment->shortname);
        $this->assertEquals($data['idnumber'], $jobassignment->idnumber);
        $this->assertEquals($data['description'], $jobassignment->description);
        $this->assertGreaterThanOrEqual($timebefore, $jobassignment->timecreated);
        $this->assertLessThanOrEqual($timeafter, $jobassignment->timecreated);
        $this->assertGreaterThanOrEqual($timebefore, $jobassignment->timemodified);
        $this->assertLessThanOrEqual($timeafter, $jobassignment->timemodified);
        $this->assertEquals($USER->id, $jobassignment->usermodified);
        $this->assertEquals($data['positionid'], $jobassignment->positionid);
        $this->assertGreaterThanOrEqual($timebefore, $jobassignment->positionassignmentdate);
        $this->assertLessThanOrEqual($timeafter, $jobassignment->positionassignmentdate);
        $this->assertEquals($data['organisationid'], $jobassignment->organisationid);
        $this->assertEquals($data['startdate'], $jobassignment->startdate);
        $this->assertEquals($data['enddate'], $jobassignment->enddate);
        $this->assertEquals($this->users[2]->id, $jobassignment->managerid);
        $this->assertEquals($data['managerjaid'], $jobassignment->managerjaid);
        $this->assertEquals('/' . $jobassignment->managerjaid . '/' . $jobassignment->id, $jobassignment->managerjapath);
        $this->assertEquals($this->users[3]->id, $jobassignment->tempmanagerid);
        $this->assertEquals($data['tempmanagerjaid'], $jobassignment->tempmanagerjaid);
        $this->assertEquals($data['tempmanagerexpirydate'], $jobassignment->tempmanagerexpirydate);
        $this->assertEquals($data['appraiserid'], $jobassignment->appraiserid);
        $this->assertEquals(1, $jobassignment->sortorder);

        // Check the description editor is being processed.
        $this->assertEquals($data['description'], $jobassignment->description_editor['text']);
        $this->assertEquals(FORMAT_HTML, $jobassignment->description_editor['format']);
        $this->assertGreaterThan(0, $jobassignment->description_editor['itemid']);

        // Create a second job assignment for a user - will have the next sortorder.
        $ja2 = \totara_job\job_assignment::create_default($data['userid']);
        $this->assertEquals(2, $ja2->sortorder);

        // Check that the idnumber must be unique for a given user.
        try {
            $ja3 = \totara_job\job_assignment::create_default($data['userid'], array('idnumber' => $ja2->idnumber));
            $this->fail('Exception was not triggered!');
        } catch (Exception $e) {
            $this->assertEquals('Tried to create job assignment idnumber which is not unique for this user', $e->getMessage());
        }

        // Check that both temp manager jaid and expiry date must be specified together.
        try {
            $ja3 = \totara_job\job_assignment::create_default($this->users[9]->id, array('tempmanagerjaid' => $ja2->id));
            $this->fail('Exception was not triggered!');
        } catch (Exception $e) {
            $this->assertEquals('Temporary manager and expiry date must either both be provided or both be empty in job_assignment::create', $e->getMessage());
        }
        try {
            $ja3 = \totara_job\job_assignment::create_default($this->users[9]->id, array('tempmanagerexpirydate' => time() + YEARSECS * 2));
            $this->fail('Exception was not triggered!');
        } catch (Exception $e) {
            $this->assertEquals('Temporary manager and expiry date must either both be provided or both be empty in job_assignment::create', $e->getMessage());
        }
    }

    /**
     * Tests calculate_managerjapath().
     */
    public function test_calculate_managerjapath() {
        // Tested indirectly through create and update.
        $teamleaderja = \totara_job\job_assignment::create_default($this->users[3]->id);
        $this->assertEquals('/' . $teamleaderja->id, $teamleaderja->managerjapath);

        $managerja = \totara_job\job_assignment::create_default($this->users[1]->id);
        $managerja->update(array('managerjaid' => $teamleaderja->id));

        $staffja = \totara_job\job_assignment::create_default($this->users[4]->id);
        $staffja->update(array('managerjaid' => $managerja->id));

        $this->assertEquals('/' . $teamleaderja->id . '/' . $managerja->id . '/' . $staffja->id, $staffja->managerjapath);

        // Make sure that loops are not allowed.
        try {
            $teamleaderja->update(array('managerjaid' => $staffja->id));
            $this->fail('Exception was not triggered!');
        } catch (Exception $e) {
            $this->assertEquals('Tried to create a manager path loop in job_assignment::calculate_managerjapath', $e->getMessage());
        }
        try {
            $managerja->update(array('managerjaid' => $staffja->id));
            $this->fail('Exception was not triggered!');
        } catch (Exception $e) {
            $this->assertEquals('Tried to create a manager path loop in job_assignment::calculate_managerjapath', $e->getMessage());
        }
        try {
            $staffja->update(array('managerjaid' => $staffja->id));
            $this->fail('Exception was not triggered!');
        } catch (Exception $e) {
            $this->assertEquals('Tried to create a manager path loop in job_assignment::calculate_managerjapath', $e->getMessage());
        }
    }

    /**
     * Tests get_data().
     */
    public function test_get_data() {
        // TODO update for recent changes.
        global $USER;

        $managerja = \totara_job\job_assignment::create_default($this->users[2]->id);
        $tempmanagerja = \totara_job\job_assignment::create_default($this->users[3]->id);

        $savedata = array(
            'userid' => $this->users[5]->id,
            'fullname' => 'fullname1',
            'shortname' => 'shortname1',
            'idnumber' => 'id1',
            'description' => 'description pre-processed',
            'positionid' => 123,
            'organisationid' => 234,
            'startdate' => 1234567,
            'enddate' => 2345678,
            'managerjaid' => $managerja->id, // User 2.
            'tempmanagerjaid' => $tempmanagerja->id, // User 3.
            'tempmanagerexpirydate' => 3456789,
            'appraiserid' => $this->users[4]->id,
        );
        $timebefore = time();
        $jobassignment = \totara_job\job_assignment::create($savedata);
        $timeafter = time();

        $retrieveddata = $jobassignment->get_data();

        // Check that the correct data was returned.
        $this->assertGreaterThan(0, $retrieveddata->id);
        $this->assertEquals($savedata['userid'], $retrieveddata->userid);
        $this->assertEquals($savedata['fullname'], $retrieveddata->fullname);
        $this->assertEquals($savedata['shortname'], $retrieveddata->shortname);
        $this->assertEquals($savedata['idnumber'], $retrieveddata->idnumber);
        $this->assertGreaterThanOrEqual($timebefore, $retrieveddata->timecreated);
        $this->assertLessThanOrEqual($timeafter, $retrieveddata->timecreated);
        $this->assertGreaterThanOrEqual($timebefore, $retrieveddata->timemodified);
        $this->assertLessThanOrEqual($timeafter, $retrieveddata->timemodified);
        $this->assertEquals($USER->id, $retrieveddata->usermodified);
        $this->assertEquals($savedata['positionid'], $retrieveddata->positionid);
        $this->assertGreaterThanOrEqual($timebefore, $retrieveddata->positionassignmentdate);
        $this->assertLessThanOrEqual($timeafter, $retrieveddata->positionassignmentdate);
        $this->assertEquals($savedata['organisationid'], $retrieveddata->organisationid);
        $this->assertEquals($savedata['startdate'], $retrieveddata->startdate);
        $this->assertEquals($savedata['enddate'], $retrieveddata->enddate);
        $this->assertEquals($this->users[2]->id, $retrieveddata->managerid);
        $this->assertEquals($savedata['managerjaid'], $retrieveddata->managerjaid);
        $this->assertEquals('/' . $savedata['managerjaid'] . '/' . $retrieveddata->id, $retrieveddata->managerjapath);
        $this->assertEquals($this->users[3]->id, $retrieveddata->tempmanagerid);
        $this->assertEquals($savedata['tempmanagerjaid'], $retrieveddata->tempmanagerjaid);
        $this->assertEquals($savedata['tempmanagerexpirydate'], $retrieveddata->tempmanagerexpirydate);
        $this->assertEquals($savedata['appraiserid'], $retrieveddata->appraiserid);
        $this->assertEquals(1, $jobassignment->sortorder);

        $this->assertEquals($savedata['description'], $retrieveddata->description_editor['text']);
        $this->assertEquals(FORMAT_HTML, $retrieveddata->description_editor['format']);
        $this->assertGreaterThan(0, $retrieveddata->description_editor['itemid']);

        // Test that whitespace job assignment names are handled correctly.
        $savedata = array(
            'userid' => $this->users[5]->id,
            'fullname' => ' ',
            'shortname' => 'shortname2',
            'idnumber' => 'id2',
            'description' => 'description pre-processed',
            'positionid' => 123,
            'organisationid' => 234,
            'startdate' => 1234567,
            'enddate' => 2345678,
            'managerjaid' => $managerja->id, // User 2.
            'tempmanagerjaid' => $tempmanagerja->id, // User 3.
            'tempmanagerexpirydate' => 3456789,
            'appraiserid' => $this->users[4]->id,
        );
        $jobassignment = \totara_job\job_assignment::create($savedata);

        $retrieveddata = $jobassignment->get_data();
        $this->assertNotEquals($savedata['fullname'], $retrieveddata->fullname);
    }

    /**
     * Tests update() and update_internal().
     */
    public function test_update_and_update_internal() {
        global $USER;

        $updatinguser = $this->getDataGenerator()->create_user();

        $createtimebefore = time();
        $jobassignment = \totara_job\job_assignment::create_default($this->users[4]->id);
        $managerja = \totara_job\job_assignment::create_default($this->users[2]->id);
        $tempmanagerja = \totara_job\job_assignment::create_default($this->users[3]->id);
        $createtimeafter = time();

        $updatedata = array(
            'fullname' => 'fullname1',
            'shortname' => 'shortname1',
            'idnumber' => 'idnumber1',
            'description' => 'description pre-processed',
            'positionid' => 123,
            'organisationid' => 234,
            'startdate' => 1234567,
            'enddate' => 2345678,
            'managerjaid' => $managerja->id, // User 2.
            'tempmanagerjaid' => $tempmanagerja->id, // User 3.
            'tempmanagerexpirydate' => 3456789,
            'appraiserid' => $this->users[4]->id,
        );

        $this->setUser($updatinguser); // A different user is doing the update.
        $this->waitForSecond(); // Ensure that the time has moved forward.
        $updatetimebefore = time();
        $jobassignment->update($updatedata);
        $updatetimeafter = time();

        $this->assertGreaterThan(0, $jobassignment->id);
        $this->assertEquals($this->users[4]->id, $jobassignment->userid);
        $this->assertEquals($updatedata['fullname'], $jobassignment->fullname);
        $this->assertEquals($updatedata['shortname'], $jobassignment->shortname);
        $this->assertEquals($updatedata['idnumber'], $jobassignment->idnumber);
        $this->assertEquals($updatedata['description'], $jobassignment->description);
        $this->assertGreaterThanOrEqual($createtimebefore, $jobassignment->timecreated);
        $this->assertLessThanOrEqual($createtimeafter, $jobassignment->timecreated);
        $this->assertGreaterThanOrEqual($updatetimebefore, $jobassignment->timemodified);
        $this->assertLessThanOrEqual($updatetimeafter, $jobassignment->timemodified);
        $this->assertEquals($USER->id, $jobassignment->usermodified);
        $this->assertEquals($updatedata['positionid'], $jobassignment->positionid);
        $this->assertGreaterThanOrEqual($updatetimebefore, $jobassignment->positionassignmentdate);
        $this->assertLessThanOrEqual($updatetimeafter, $jobassignment->positionassignmentdate);
        $this->assertEquals($updatedata['organisationid'], $jobassignment->organisationid);
        $this->assertEquals($updatedata['startdate'], $jobassignment->startdate);
        $this->assertEquals($updatedata['enddate'], $jobassignment->enddate);
        $this->assertEquals($this->users[2]->id, $jobassignment->managerid);
        $this->assertEquals($updatedata['managerjaid'], $jobassignment->managerjaid);
        $this->assertEquals('/' . $jobassignment->managerjaid . '/' . $jobassignment->id, $jobassignment->managerjapath);
        $this->assertEquals($this->users[3]->id, $jobassignment->tempmanagerid);
        $this->assertEquals($updatedata['tempmanagerjaid'], $jobassignment->tempmanagerjaid);
        $this->assertEquals($updatedata['tempmanagerexpirydate'], $jobassignment->tempmanagerexpirydate);
        $this->assertEquals($updatedata['appraiserid'], $jobassignment->appraiserid);
        $this->assertEquals(1, $jobassignment->sortorder);

        // Show that positionassignmentdate does not change if the positionid is specified but does not change.
        $previousposassignmentdate = $jobassignment->positionassignmentdate;
        $this->waitForSecond();
        $posupdatetimebefore = time();
        $jobassignment->update(array('positionid' => $updatedata['positionid'], 'organisationid' => 777));
        $posupdatetimeafter = time();

        $this->assertGreaterThanOrEqual($posupdatetimebefore, $jobassignment->timemodified);
        $this->assertLessThanOrEqual($posupdatetimeafter, $jobassignment->timemodified);
        $this->assertEquals($updatedata['positionid'], $jobassignment->positionid);
        $this->assertEquals($previousposassignmentdate, $jobassignment->positionassignmentdate);
        $this->assertEquals(777, $jobassignment->organisationid);

        // Make sure that the userid cannot be changed.
        try {
            $jobassignment->update(array('userid' => $this->users[1]->id));
            $this->fail('Exception was not thrown!');
        } catch (Exception $e) {
            $this->assertEquals("Invalid field specified when updating job_assignment (not allowed or doesn't exist).", $e->getMessage());
        }

        // Make sure that passing no data doesn't fail and doesn't update the timemodified or usermodified.
        $previoustimemodified = $jobassignment->timemodified;
        $previoususermodified = $jobassignment->usermodified;
        $this->waitForSecond();
        $this->setAdminUser();
        $this->assertNotEquals($previoususermodified, $USER->id);
        $jobassignment->update(array()); // Empty array.
        $jobassignment->update((object)array()); // Empty object.

        $this->assertEquals($previoustimemodified, $jobassignment->timemodified);
        $this->assertEquals($previoususermodified, $jobassignment->usermodified);

        // Check that the idnumber must be unique for a given user.
        $previousidnumber = $jobassignment->idnumber;
        $jobassignment->update(array('shortname' => $previousidnumber)); // Can update to the same idnumber, no problem.
        $seconddata = array(
            'userid' => $jobassignment->userid,
            'fullname' => 'newfullname',
            'shortname' => 'newshortname',
            'idnumber' => 'newidnumber',
        );
        \totara_job\job_assignment::create($seconddata); // Create a second job assignment.
        try {
            $jobassignment->update(array('idnumber' => $seconddata['idnumber'])); // Update first to match second.
            $this->fail('Exception was not triggered!');
        } catch (Exception $e) {
            $this->assertEquals('Tried to update job assignment to an idnumber which is not unique for this user', $e->getMessage());
        }

        // Make sure that update of $jobassignment isn't messing with other job assignment records.
        $managerja = \totara_job\job_assignment::get_with_idnumber($managerja->userid, $managerja->idnumber); // Reload the record from db.
        $this->assertEquals($this->users[2]->id, $managerja->userid);
        $this->assertEquals(get_string('jobassignmentdefaultfullname', 'totara_job', 1), $managerja->fullname); // Default calculated.
        $this->assertNull($managerja->shortname);
        $this->assertEquals(1, $managerja->idnumber);
        $this->assertEquals('', $managerja->description);
        $this->assertGreaterThanOrEqual($createtimebefore, $managerja->timecreated);
        $this->assertLessThanOrEqual($createtimeafter, $managerja->timecreated);
        $this->assertGreaterThanOrEqual($createtimebefore, $managerja->timemodified);
        $this->assertLessThanOrEqual($createtimeafter, $managerja->timemodified);
        $this->assertEquals($USER->id, $managerja->usermodified);
        $this->assertNull($managerja->positionid);
        $this->assertGreaterThanOrEqual($createtimebefore, $managerja->positionassignmentdate);
        $this->assertLessThanOrEqual($createtimeafter, $managerja->positionassignmentdate);
        $this->assertNull($managerja->organisationid);
        $this->assertNull($managerja->startdate);
        $this->assertNull($managerja->enddate);
        $this->assertNull($managerja->managerid);
        $this->assertNull($managerja->managerjaid);
        $this->assertEquals('/' . $managerja->id, $managerja->managerjapath);
        $this->assertNull($managerja->tempmanagerid);
        $this->assertNull($managerja->tempmanagerjaid);
        $this->assertNull($managerja->tempmanagerexpirydate);
        $this->assertNull($managerja->appraiserid);
        $this->assertEquals(1, $managerja->sortorder);

        // Check that both temp manager jaid and expiry date must be specified together.
        try {
            $jobassignment->update(array('tempmanagerjaid' => $tempmanagerja->id));
            $this->fail('Exception was not triggered!');
        } catch (Exception $e) {
            $this->assertEquals('Temporary manager and expiry date must either both be provided or both be empty in job_assignment::update_internal', $e->getMessage());
        }
        try {
            $jobassignment->update(array('tempmanagerexpirydate' => time() + DAYSECS * 100));
            $this->fail('Exception was not triggered!');
        } catch (Exception $e) {
            $this->assertEquals('Temporary manager and expiry date must either both be provided or both be empty in job_assignment::update_internal', $e->getMessage());
        }

        // Check that unsetting the managerjaid will reset the managerjapath.
        $jobassignment->update(array('managerjaid' => null));
        $this->assertNull($jobassignment->managerid);
        $this->assertNull($jobassignment->managerjaid);
        $this->assertEquals('/' . $jobassignment->id, $jobassignment->managerjapath);

        // Check that updating the position to null causes the positionassignmentdate to be updated.
        $this->waitForSecond();
        $timebefore = time();
        $jobassignment->update(array('positionid' => null));
        $this->assertNull($jobassignment->positionid);
        $this->assertGreaterThanOrEqual($timebefore, $jobassignment->positionassignmentdate);
    }

    /**
     * Tests updated_manager.
     */
    public function test_updated_manager() {
        // TODO Writeme.
    }

    /**
     * Tests update_manager_role_assignments.
     */
    public function test_update_manager_role_assignments() {
        // TODO Writeme.
    }

    /**
     * Tests update_descendant_manager_paths.
     *
     * TL1 > Man1 > Learn1a
     *            > Learn2
     *     > Learn1b
     * TL2 > Man2 > Learn1c
     *            > Learn3 > Sub1
     *     > Man3 > Learn4
     *
     * Move Man2 to TL1. Man2, Learn1c, Learn3 and Sub1 should be updated - timemodified and managerjapath.
     */
    public function test_update_descendant_manager_paths() {
        // Set up management hierarchy.
        $tl1ja = \totara_job\job_assignment::create_default($this->users[1]->id);
        $tl2ja = \totara_job\job_assignment::create_default($this->users[2]->id);
        $man1ja = \totara_job\job_assignment::create_default($this->users[3]->id, array('managerjaid' => $tl1ja->id));
        $man2ja = \totara_job\job_assignment::create_default($this->users[4]->id, array('managerjaid' => $tl2ja->id));
        $man3ja = \totara_job\job_assignment::create_default($this->users[5]->id, array('managerjaid' => $tl2ja->id));
        $learn1aja = \totara_job\job_assignment::create_default($this->users[6]->id, array('managerjaid' => $man1ja->id));
        $learn1bja = \totara_job\job_assignment::create_default($this->users[6]->id, array('managerjaid' => $tl1ja->id));
        $learn1cja = \totara_job\job_assignment::create_default($this->users[6]->id, array('managerjaid' => $man2ja->id));
        $learn2ja = \totara_job\job_assignment::create_default($this->users[7]->id, array('managerjaid' => $man1ja->id));
        $learn3ja = \totara_job\job_assignment::create_default($this->users[8]->id, array('managerjaid' => $man2ja->id));
        $learn4ja = \totara_job\job_assignment::create_default($this->users[9]->id, array('managerjaid' => $man3ja->id));
        $sub1ja = \totara_job\job_assignment::create_default($this->users[10]->id, array('managerjaid' => $learn3ja->id));

        // Reload the job assignments, because they might have been modified as the managers were set up.
        $originaltl1ja = \totara_job\job_assignment::get_with_id($tl1ja->id);
        $originaltl2ja = \totara_job\job_assignment::get_with_id($tl2ja->id);
        $originalman1ja = \totara_job\job_assignment::get_with_id($man1ja->id);
        $originalman2ja = \totara_job\job_assignment::get_with_id($man2ja->id);
        $originalman3ja = \totara_job\job_assignment::get_with_id($man3ja->id);
        $originallearn1aja = \totara_job\job_assignment::get_with_id($learn1aja->id);
        $originallearn1bja = \totara_job\job_assignment::get_with_id($learn1bja->id);
        $originallearn1cja = \totara_job\job_assignment::get_with_id($learn1cja->id);
        $originallearn2ja = \totara_job\job_assignment::get_with_id($learn2ja->id);
        $originallearn3ja = \totara_job\job_assignment::get_with_id($learn3ja->id);
        $originallearn4ja = \totara_job\job_assignment::get_with_id($learn4ja->id);
        $originalsub1ja = \totara_job\job_assignment::get_with_id($sub1ja->id);

        // Sleep for one second so that we know the time has changed.
        sleep(1);

        // Call update_descendant_manager_paths indirectly.
        $man2ja = \totara_job\job_assignment::get_with_id($man2ja->id);
        $timebefore = time();
        $man2ja->update(array('managerjaid' => $tl1ja->id));

        // Reload the data.
        $resulttl1ja = \totara_job\job_assignment::get_with_id($tl1ja->id);
        $resulttl2ja = \totara_job\job_assignment::get_with_id($tl2ja->id);
        $resultman1ja = \totara_job\job_assignment::get_with_id($man1ja->id);
        $resultman2ja = \totara_job\job_assignment::get_with_id($man2ja->id);
        $resultman3ja = \totara_job\job_assignment::get_with_id($man3ja->id);
        $resultlearn1aja = \totara_job\job_assignment::get_with_id($learn1aja->id);
        $resultlearn1bja = \totara_job\job_assignment::get_with_id($learn1bja->id);
        $resultlearn1cja = \totara_job\job_assignment::get_with_id($learn1cja->id);
        $resultlearn2ja = \totara_job\job_assignment::get_with_id($learn2ja->id);
        $resultlearn3ja = \totara_job\job_assignment::get_with_id($learn3ja->id);
        $resultlearn4ja = \totara_job\job_assignment::get_with_id($learn4ja->id);
        $resultsub1ja = \totara_job\job_assignment::get_with_id($sub1ja->id);

        // Check that control data is unchanged.
        $this->assertEquals($originaltl1ja, $resulttl1ja);
        $this->assertEquals($originaltl2ja, $resulttl2ja);
        $this->assertEquals($originalman1ja, $resultman1ja);
        $this->assertEquals($originalman3ja, $resultman3ja);
        $this->assertEquals($originallearn1aja, $resultlearn1aja);
        $this->assertEquals($originallearn1bja, $resultlearn1bja);
        $this->assertEquals($originallearn2ja, $resultlearn2ja);
        $this->assertEquals($originallearn4ja, $resultlearn4ja);

        // Check all the timemodifieds.
        $this->assertLessThan($timebefore, $resulttl1ja->timemodified);
        $this->assertLessThan($timebefore, $resulttl2ja->timemodified);
        $this->assertLessThan($timebefore, $resultman1ja->timemodified);
        $this->assertGreaterThanOrEqual($timebefore, $resultman2ja->timemodified);
        $this->assertLessThan($timebefore, $resultman3ja->timemodified);
        $this->assertLessThan($timebefore, $resultlearn1aja->timemodified);
        $this->assertLessThan($timebefore, $resultlearn1bja->timemodified);
        $this->assertGreaterThanOrEqual($timebefore, $resultlearn1cja->timemodified);
        $this->assertLessThan($timebefore, $resultlearn2ja->timemodified);
        $this->assertGreaterThanOrEqual($timebefore, $resultlearn3ja->timemodified);
        $this->assertLessThan($timebefore, $resultlearn4ja->timemodified);
        $this->assertGreaterThanOrEqual($timebefore, $resultsub1ja->timemodified);

        // Check the modified paths.
        $expectedman2path = $originaltl1ja->managerjapath . '/' . $man2ja->id;
        $this->assertEquals($expectedman2path, $resultman2ja->managerjapath);
        $expectedlearn1cpath = $expectedman2path . '/' . $learn1cja->id;
        $this->assertEquals($expectedlearn1cpath, $resultlearn1cja->managerjapath);
        $expectedlearn3path = $expectedman2path . '/' . $learn3ja->id;
        $this->assertEquals($expectedlearn3path, $resultlearn3ja->managerjapath);
        $expectedsub1path = $expectedlearn3path . '/' . $sub1ja->id;
        $this->assertEquals($expectedsub1path, $resultsub1ja->managerjapath);
    }

    /**
     * Tests updated_temporary_manager.
     */
    public function test_updated_temporary_manager() {
        // TODO Writeme.
    }

    /**
     * Tests delete().
     */
    public function test_delete() {
        global $DB;

        $this->assertEquals(0, $DB->count_records('job_assignment'));

        $u1ja1 = \totara_job\job_assignment::create_default($this->users[1]->id);

        $u2ja1 = \totara_job\job_assignment::create_default($this->users[2]->id);
        $u2ja2 = \totara_job\job_assignment::create_default($this->users[2]->id);
        $u2ja3 = \totara_job\job_assignment::create_default($this->users[2]->id);

        $u3ja1 = \totara_job\job_assignment::create_default($this->users[3]->id);

        $this->assertEquals(5, $DB->count_records('job_assignment'));

        // Only the specified job assignment is deleted for the user.
        \totara_job\job_assignment::delete($u2ja3);
        $this->assertEmpty($u2ja3);
        $this->assertEquals(4, $DB->count_records('job_assignment'));
        $this->assertEmpty($DB->get_records('job_assignment', array('userid' => $this->users[2]->id, 'idnumber' => '3')));

        // Only the specified user's job assignment is deleted, even if other users share the same idnumber.
        \totara_job\job_assignment::delete($u3ja1);
        $this->assertEmpty($u3ja1);
        $this->assertEquals(3, $DB->count_records('job_assignment'));
        $this->assertEmpty($DB->get_records('job_assignment', array('userid' => $this->users[3]->id, 'idnumber' => '1')));
        $this->assertEquals(2, $DB->count_records('job_assignment', array('idnumber' => '1')));
    }

    /**
     * A user has staff associated with one job assignment. When we delete that job assignment, they should
     * no longer have a staff manager role for those staff.
     */
    public function test_delete_updates_role_assignments_when_has_staff() {
        global $CFG;

        // We'll use user 2 as the user to assign a staff manager to.
        $u2context = context_user::instance($this->users[2]->id);
        $this->assertEmpty(get_role_users($CFG->managerroleid, $u2context));

        $u1managerja = \totara_job\job_assignment::create_default($this->users[1]->id);
        $u2staffja = \totara_job\job_assignment::create_default($this->users[2]->id);
        $u2staffja->update(['managerjaid' => $u1managerja->id]);

        $this->assertCount(1, get_role_users($CFG->managerroleid, $u2context));

        // Testing when we delete the manager's job assignment.
        \totara_job\job_assignment::delete($u1managerja);

        $this->assertEmpty(get_role_users($CFG->managerroleid, $u2context));
    }

    /**
     * A user has a manager associated with one job assignment. When we delete that job assignment, their manager
     * should no longer have a staff manager role in the user's context.
     */
    public function test_delete_updates_role_assignments_when_has_manager() {
        global $CFG;

        // We'll use user 2 as the user to assign a staff manager to.
        $u2context = context_user::instance($this->users[2]->id);
        $this->assertEmpty(get_role_users($CFG->managerroleid, $u2context));

        $u1managerja = \totara_job\job_assignment::create_default($this->users[1]->id);
        $u2staffja = \totara_job\job_assignment::create_default($this->users[2]->id);
        $u2staffja->update(['managerjaid' => $u1managerja->id]);

        $this->assertCount(1, get_role_users($CFG->managerroleid, $u2context));

        // Testing when we delete the staff's job assignment.
        \totara_job\job_assignment::delete($u2staffja);

        $this->assertEmpty(get_role_users($CFG->managerroleid, $u2context));
    }

    /**
     * A user has temporary staff associated with one job assignment (ie they are a temporary manager).
     * When we delete that job assignment, they should no longer have a staff manager role for those staff.
     */
    public function test_delete_updates_role_assignments_when_has_tempstaff() {
        global $CFG;

        // We'll use user 2 as the user to assign a staff manager to.
        $u2context = context_user::instance($this->users[2]->id);
        $this->assertEmpty(get_role_users($CFG->managerroleid, $u2context));

        $u1managerja = \totara_job\job_assignment::create_default($this->users[1]->id);
        $u2staffja = \totara_job\job_assignment::create_default($this->users[2]->id);
        $u2staffja->update(['tempmanagerjaid' => $u1managerja->id, 'tempmanagerexpirydate' => time() + DAYSECS]);

        $this->assertCount(1, get_role_users($CFG->managerroleid, $u2context));

        // Delete the temp manager's job assignment.
        \totara_job\job_assignment::delete($u1managerja);

        $this->assertEmpty(get_role_users($CFG->managerroleid, $u2context));
    }

    /**
     * A user has a temporary manager associated with one job assignment.
     * When we delete that job assignment, their manager should no longer have
     * a staff manager role in the user's context.
     */
    public function test_delete_updates_role_assignments_when_has_tempmanager() {
        global $CFG;

        // We'll use user 2 as the user to assign a staff manager to.
        $u2context = context_user::instance($this->users[2]->id);
        $this->assertEmpty(get_role_users($CFG->managerroleid, $u2context));

        $u1managerja = \totara_job\job_assignment::create_default($this->users[1]->id);
        $u2staffja = \totara_job\job_assignment::create_default($this->users[2]->id);
        $u2staffja->update(['tempmanagerjaid' => $u1managerja->id, 'tempmanagerexpirydate' => time() + DAYSECS]);

        $this->assertCount(1, get_role_users($CFG->managerroleid, $u2context));

        // Delete the temp staffs job assignment.
        \totara_job\job_assignment::delete($u2staffja);

        $this->assertEmpty(get_role_users($CFG->managerroleid, $u2context));
    }

    /**
     * A user has a manager and staff associated with a single job assignment.
     * When we delete that job assignment, there should be no remaining role assignments reflecting those
     * relationships.
     */
    public function test_delete_updates_role_assignments_when_manager_and_staff() {
        global $CFG;

        // We'll use user 2 as the user to assign a staff manager to.
        $u2context = context_user::instance($this->users[2]->id);
        $this->assertEmpty(get_role_users($CFG->managerroleid, $u2context));

        // User 1 will be the middle manager.
        $u1context = context_user::instance($this->users[1]->id);
        $this->assertEmpty(get_role_users($CFG->managerroleid, $u1context));

        $u1managerja = \totara_job\job_assignment::create_default($this->users[1]->id);
        $u2staffja = \totara_job\job_assignment::create_default($this->users[2]->id);
        $u2staffja->update(['managerjaid' => $u1managerja->id]);

        // User 3 is the highest level manager.
        $u3boss = \totara_job\job_assignment::create_default($this->users[3]->id);
        $u1managerja->update(['managerjaid' => $u3boss->id]);

        $this->assertCount(1, get_role_users($CFG->managerroleid, $u2context));
        $this->assertCount(1, get_role_users($CFG->managerroleid, $u1context));

        // Testing when we delete the middle manager's job assignment.
        \totara_job\job_assignment::delete($u1managerja);

        $this->assertEmpty(get_role_users($CFG->managerroleid, $u2context));
        $this->assertEmpty(get_role_users($CFG->managerroleid, $u1context));
    }

    /**
     * Tests get_with_idnumber().
     */
    public function test_get_with_idnumber() {
        $u1ja1 = \totara_job\job_assignment::create_default($this->users[1]->id);

        $u2ja1 = \totara_job\job_assignment::create_default($this->users[2]->id);
        $u2ja2 = \totara_job\job_assignment::create_default($this->users[2]->id);
        $u2ja3 = \totara_job\job_assignment::create_default($this->users[2]->id);

        $u3ja1 = \totara_job\job_assignment::create_default($this->users[3]->id);

        // User with multiple job assignments, gets the one with the correct short name.
        $retrievedja = \totara_job\job_assignment::get_with_idnumber($u2ja2->userid, $u2ja2->idnumber, false);
        $this->assertEquals($u2ja2, $retrievedja);

        // Several users have the same shortname, gets the correct user's record.
        $retrievedja = \totara_job\job_assignment::get_with_idnumber($u3ja1->userid, $u3ja1->idnumber);
        $this->assertEquals($u3ja1, $retrievedja);

        // Test mustexist true (default).
        try {
            $retrievedja = \totara_job\job_assignment::get_with_idnumber($u3ja1->userid, 'notarealidnumber');
            $this->fail('Exception not triggered!');
        } catch (Exception $e) {
            $this->assertStringStartsWith('Can not find data record in database', $e->getMessage());
        }

        // Test mustexist false.
        $retrievedja = \totara_job\job_assignment::get_with_idnumber($u3ja1->userid, 'notarealidnumber', false);
        $this->assertNull($retrievedja);
    }

    /**
     * Tests get_with_id().
     */
    public function test_get_with_id() {
        $u1ja1 = \totara_job\job_assignment::create_default($this->users[1]->id);

        $u2ja1 = \totara_job\job_assignment::create_default($this->users[2]->id);
        $u2ja2 = \totara_job\job_assignment::create_default($this->users[2]->id);
        $u2ja3 = \totara_job\job_assignment::create_default($this->users[2]->id);

        $u3ja1 = \totara_job\job_assignment::create_default($this->users[3]->id);

        $retrievedja = \totara_job\job_assignment::get_with_id($u2ja1->id);
        $this->assertEquals($u2ja1, $retrievedja);

        // Test mustexist true (default).
        try {
            $retrievedja = \totara_job\job_assignment::get_with_id(-1);
            $this->fail('Exception not triggered!');
        } catch (Exception $e) {
            $this->assertStringStartsWith('Can not find data record in database', $e->getMessage());
        }

        // Test mustexist false.
        $retrievedja = \totara_job\job_assignment::get_with_id(-1, false);
        $this->assertNull($retrievedja);
    }

    /**
     * Tests get_all().
     */
    public function test_get_all() {
        $u1ja1 = \totara_job\job_assignment::create_default($this->users[1]->id);

        $u3ja1 = \totara_job\job_assignment::create_default($this->users[3]->id);

        $u2ja1 = \totara_job\job_assignment::create_default($this->users[2]->id);
        $u2ja2 = \totara_job\job_assignment::create_default($this->users[2]->id,
            array('managerjaid' => $u1ja1->id));
        $u2ja3 = \totara_job\job_assignment::create_default($this->users[2]->id,
            array('tempmanagerjaid' => $u3ja1->id, 'tempmanagerexpirydate' => time() + DAYSECS * 2));

        $retrievedjas = \totara_job\job_assignment::get_all($this->users[1]->id);
        $this->assertEquals(1, count($retrievedjas));
        $this->assertEquals($u1ja1, $retrievedjas[1]);

        $retrievedjas = \totara_job\job_assignment::get_all($this->users[2]->id);
        $this->assertEquals(3, count($retrievedjas));
        $this->assertEquals($u2ja1, $retrievedjas[1]);
        $this->assertEquals($u2ja2, $retrievedjas[2]);
        $this->assertEquals($u2ja3, $retrievedjas[3]);

        // Test managerreq true.
        $retrievedjas = \totara_job\job_assignment::get_all($this->users[1]->id, true);
        $this->assertEquals(0, count($retrievedjas));

        $retrievedjas = \totara_job\job_assignment::get_all($this->users[2]->id, true);
        $this->assertEquals(2, count($retrievedjas));
        $this->assertEquals($u2ja2, $retrievedjas[2]);
        $this->assertEquals($u2ja3, $retrievedjas[3]);
    }

    /**
     * Tests get_all_by_criteria().
     */
    public function test_get_all_by_criteria() {
        $u1ja1 = \totara_job\job_assignment::create_default($this->users[1]->id, array('appraiserid' => 123));

        $u2ja1 = \totara_job\job_assignment::create_default($this->users[2]->id, array('appraiserid' => 123));
        $u2ja2 = \totara_job\job_assignment::create_default($this->users[2]->id, array('appraiserid' => 123));
        $u2ja3 = \totara_job\job_assignment::create_default($this->users[2]->id, array('positionid' => 123));

        $u3ja1 = \totara_job\job_assignment::create_default($this->users[3]->id, array('organisationid' => 123));

        $this->assertEquals(array($u1ja1->id => $u1ja1, $u2ja1->id => $u2ja1, $u2ja2->id => $u2ja2),
            \totara_job\job_assignment::get_all_by_criteria('appraiserid', 123));
        $this->assertEquals(array($u2ja3->id => $u2ja3), \totara_job\job_assignment::get_all_by_criteria('positionid', 123));
        $this->assertEquals(array($u3ja1->id => $u3ja1), \totara_job\job_assignment::get_all_by_criteria('organisationid', 123));
        $this->assertEmpty(\totara_job\job_assignment::get_all_by_criteria('appraiserid', 444));
        $this->assertEmpty(\totara_job\job_assignment::get_all_by_criteria('positionid', 555));
        $this->assertEmpty(\totara_job\job_assignment::get_all_by_criteria('organisationid', 666));
    }

    /**
     * Tests update_to_empty_by_criteria().
     */
    public function test_update_to_empty_by_criteria() {
        $u1ja1 = \totara_job\job_assignment::create_default($this->users[1]->id,
            array('appraiserid' => 124));

        $u2ja1 = \totara_job\job_assignment::create_default($this->users[2]->id,
            array('appraiserid' => 123, 'positionid' => 234, 'organisationid' => 234));
        $u2ja2 = \totara_job\job_assignment::create_default($this->users[2]->id,
            array('appraiserid' => 123));
        $u2ja3 = \totara_job\job_assignment::create_default($this->users[2]->id,
            array('positionid' => 123));

        $u3ja1 = \totara_job\job_assignment::create_default($this->users[3]->id,
            array('organisationid' => 123));

        \totara_job\job_assignment::update_to_empty_by_criteria('appraiserid', 123);
        \totara_job\job_assignment::update_to_empty_by_criteria('positionid', 234);
        \totara_job\job_assignment::update_to_empty_by_criteria('organisationid', 234);

        $u1ja1 = \totara_job\job_assignment::get_with_id($u1ja1->id);
        $u2ja1 = \totara_job\job_assignment::get_with_id($u2ja1->id);
        $u2ja2 = \totara_job\job_assignment::get_with_id($u2ja2->id);
        $u2ja3 = \totara_job\job_assignment::get_with_id($u2ja3->id);
        $u3ja1 = \totara_job\job_assignment::get_with_id($u3ja1->id);

        $this->assertEquals(array($u1ja1->id => $u1ja1),
            \totara_job\job_assignment::get_all_by_criteria('appraiserid', 124));
        $this->assertEquals(array($u2ja3->id => $u2ja3),
            \totara_job\job_assignment::get_all_by_criteria('positionid', 123));
        $this->assertEquals(array($u3ja1->id => $u3ja1),
            \totara_job\job_assignment::get_all_by_criteria('organisationid', 123));

        $this->assertEmpty(\totara_job\job_assignment::get_all_by_criteria('appraiserid', 123));
        $this->assertEmpty(\totara_job\job_assignment::get_all_by_criteria('positionid', 234));
        $this->assertEmpty(\totara_job\job_assignment::get_all_by_criteria('organisationid', 234));
    }

    /**
     * Tests get_first().
     */
    public function test_get_first() {
        $u2ja1 = \totara_job\job_assignment::create_default($this->users[2]->id);
        $u2ja2 = \totara_job\job_assignment::create_default($this->users[2]->id);
        $u2ja3 = \totara_job\job_assignment::create_default($this->users[2]->id);

        $retrievedja = \totara_job\job_assignment::get_first($this->users[2]->id);
        $this->assertEquals($u2ja1, $retrievedja);

        // Test mustexist true (default).
        try {
            $retrievedja = \totara_job\job_assignment::get_first($this->users[1]->id);
            $this->fail('Exception not triggered!');
        } catch (Exception $e) {
            $this->assertStringStartsWith('Can not find data record in database', $e->getMessage());
        }

        // Test mustexist false.
        $retrievedja = \totara_job\job_assignment::get_first($this->users[1]->id, false);
        $this->assertNull($retrievedja);
    }

    /**
     * Tests get_staff() and get_direct_staff().
     */
    public function test_get_staff() {
        $managerid = $this->users[5]->id;
        $managerja1 = \totara_job\job_assignment::create_default($managerid);
        $managerja2 = \totara_job\job_assignment::create_default($managerid);

        $u2ja1 = \totara_job\job_assignment::create_default($this->users[2]->id,
            array('managerjaid' => $managerja1->id));
        $u2ja2 = \totara_job\job_assignment::create_default($this->users[2]->id,
            array('tempmanagerjaid' => $managerja1->id, 'tempmanagerexpirydate' => time() + DAYSECS * 2));

        $u3ja1 = \totara_job\job_assignment::create_default($this->users[3]->id,
            array('managerjaid' => $managerja1->id));

        $u4ja1 = \totara_job\job_assignment::create_default($this->users[4]->id,
            array('tempmanagerjaid' => $managerja2->id, 'tempmanagerexpirydate' => time() + DAYSECS * 2));

        $u1ja1 = \totara_job\job_assignment::create_default($this->users[1]->id,
            array('managerjaid' => $u2ja1->id));

        // Test when only managerid is specified.
        $staffjas = \totara_job\job_assignment::get_staff($managerid);
        $this->assertEquals(4, count($staffjas));
        $this->assertEquals($u2ja1, $staffjas[$u2ja1->id]);
        $this->assertEquals($u2ja2, $staffjas[$u2ja2->id]);
        $this->assertEquals($u3ja1, $staffjas[$u3ja1->id]);
        $this->assertEquals($u4ja1, $staffjas[$u4ja1->id]);

        // Test when managerjaid is specified.
        $staffjas = \totara_job\job_assignment::get_staff($managerid, $managerja1->id);
        $this->assertEquals(3, count($staffjas));
        $this->assertEquals($u2ja1, $staffjas[$u2ja1->id]);
        $this->assertEquals($u2ja2, $staffjas[$u2ja2->id]);
        $this->assertEquals($u3ja1, $staffjas[$u3ja1->id]);

        $staffjas = \totara_job\job_assignment::get_staff($managerid, $managerja2->id);
        $this->assertEquals(1, count($staffjas));
        $this->assertEquals($u4ja1, $staffjas[$u4ja1->id]);

        // Test when excluding temp staff.
        $staffjas = \totara_job\job_assignment::get_staff($managerid, null, false);
        $this->assertEquals(2, count($staffjas));
        $this->assertEquals($u2ja1, $staffjas[$u2ja1->id]);
        $this->assertEquals($u3ja1, $staffjas[$u3ja1->id]);

        $staffjas = \totara_job\job_assignment::get_staff($managerid, $managerja2->id, false);
        $this->assertEquals(0, count($staffjas));

        // Test mismatched managerid and managerjaid (should return nothing).
        $staffjas = \totara_job\job_assignment::get_staff($managerid, $u1ja1->id);
        $this->assertEquals(0, count($staffjas));
    }

    /**
     * Tests get_staff_userids() and get_direct_staff_userids().
     */
    public function test_get_staff_userids() {
        $managerid = $this->users[5]->id;
        $managerja1 = \totara_job\job_assignment::create_default($managerid);
        $managerja2 = \totara_job\job_assignment::create_default($managerid);

        $u2ja1 = \totara_job\job_assignment::create_default($this->users[2]->id,
            array('managerjaid' => $managerja1->id));
        $u2ja2 = \totara_job\job_assignment::create_default($this->users[2]->id,
            array('tempmanagerjaid' => $managerja1->id, 'tempmanagerexpirydate' => time() + DAYSECS * 2));

        $u3ja1 = \totara_job\job_assignment::create_default($this->users[3]->id,
            array('managerjaid' => $managerja1->id));

        $u4ja1 = \totara_job\job_assignment::create_default($this->users[4]->id,
            array('tempmanagerjaid' => $managerja2->id, 'tempmanagerexpirydate' => time() + DAYSECS * 2));

        $u1ja1 = \totara_job\job_assignment::create_default($this->users[1]->id,
            array('managerjaid' => $u2ja1->id));

        // Test when only managerid is specified.
        $staffids = \totara_job\job_assignment::get_staff_userids($managerid);
        sort($staffids); // Make sure they are in order.
        $this->assertEquals(array($this->users[2]->id, $this->users[3]->id, $this->users[4]->id), $staffids);

        // Test when managerjaid is specified.
        $staffids = \totara_job\job_assignment::get_staff_userids($managerid, $managerja1->id);
        sort($staffids); // Make sure they are in order.
        $this->assertEquals(array($this->users[2]->id, $this->users[3]->id), $staffids);

        $staffids = \totara_job\job_assignment::get_staff_userids($managerid, $managerja2->id);
        sort($staffids); // Make sure they are in order.
        $this->assertEquals(array($this->users[4]->id), $staffids);

        // Test when excluding temp staff.
        $staffids = \totara_job\job_assignment::get_staff_userids($managerid, null, false);
        sort($staffids); // Make sure they are in order.
        $this->assertEquals(array($this->users[2]->id, $this->users[3]->id), $staffids);

        $staffids = \totara_job\job_assignment::get_staff_userids($managerid, $managerja2->id, false);
        $this->assertEquals(0, count($staffids));

        // Test mismatched managerid and managerjaid (should return nothing).
        $staffids = \totara_job\job_assignment::get_staff_userids($managerid, $u1ja1->id);
        $this->assertEquals(0, count($staffids));
    }

    /**
     * Tests get_all_manager_userids().
     */
    public function test_get_all_manager_userids() {
        $future = time() + DAYSECS * 100;

        $manager8ja = \totara_job\job_assignment::create_default($this->users[8]->id);
        $manager9ja = \totara_job\job_assignment::create_default($this->users[9]->id);
        $tempmanager10ja = \totara_job\job_assignment::create_default($this->users[10]->id);

        $u1ja1 = \totara_job\job_assignment::create_default($this->users[1]->id, array('managerjaid' => $manager8ja->id));

        $u2ja1 = \totara_job\job_assignment::create_default($this->users[2]->id, array('managerjaid' => $manager8ja->id));
        $u2ja2 = \totara_job\job_assignment::create_default($this->users[2]->id, array('managerjaid' => $manager9ja->id));
        $u2ja3 = \totara_job\job_assignment::create_default($this->users[2]->id, array('tempmanagerjaid' => $tempmanager10ja->id, 'tempmanagerexpirydate' => $future));

        $u3ja1 = \totara_job\job_assignment::create_default($this->users[3]->id, array('managerjaid' => $manager8ja->id));
        $u3ja2 = \totara_job\job_assignment::create_default($this->users[3]->id, array('managerjaid' => $manager8ja->id));

        $u4ja1 = \totara_job\job_assignment::create_default($this->users[4]->id, array('managerjaid' => $manager9ja->id));

        $u5ja1 = \totara_job\job_assignment::create_default($this->users[5]->id, array('tempmanagerjaid' => $tempmanager10ja->id, 'tempmanagerexpirydate' => $future));

        $u6ja1 = \totara_job\job_assignment::create_default($this->users[5]->id);

        $manageruserids = \totara_job\job_assignment::get_all_manager_userids($this->users[1]->id);
        $this->assertEquals(array($manager8ja->userid), array_values($manageruserids));
        $manageruserids = \totara_job\job_assignment::get_all_manager_userids($this->users[2]->id);
        $this->assertEquals(array($manager8ja->userid, $manager9ja->userid, $tempmanager10ja->userid), array_values($manageruserids));
        $manageruserids = \totara_job\job_assignment::get_all_manager_userids($this->users[3]->id);
        $this->assertEquals(array($manager8ja->userid), array_values($manageruserids));
        $manageruserids = \totara_job\job_assignment::get_all_manager_userids($this->users[4]->id);
        $this->assertEquals(array($manager9ja->userid), array_values($manageruserids));
        $manageruserids = \totara_job\job_assignment::get_all_manager_userids($this->users[5]->id);
        $this->assertEquals(array($tempmanager10ja->userid), array_values($manageruserids));
        $manageruserids = \totara_job\job_assignment::get_all_manager_userids($this->users[6]->id);
        $this->assertEmpty($manageruserids);

        $manageruserids = \totara_job\job_assignment::get_all_manager_userids($this->users[1]->id, $u1ja1->id);
        $this->assertEquals(array($manager8ja->userid), array_values($manageruserids));
        $manageruserids = \totara_job\job_assignment::get_all_manager_userids($this->users[2]->id, $u2ja1->id);
        $this->assertEquals(array($manager8ja->userid), array_values($manageruserids));
        $manageruserids = \totara_job\job_assignment::get_all_manager_userids($this->users[2]->id, $u2ja2->id);
        $this->assertEquals(array($manager9ja->userid), array_values($manageruserids));
        $manageruserids = \totara_job\job_assignment::get_all_manager_userids($this->users[2]->id, $u2ja3->id);
        $this->assertEquals(array($tempmanager10ja->userid), array_values($manageruserids));
        $manageruserids = \totara_job\job_assignment::get_all_manager_userids($this->users[3]->id, $u3ja1->id);
        $this->assertEquals(array($manager8ja->userid), array_values($manageruserids));
        $manageruserids = \totara_job\job_assignment::get_all_manager_userids($this->users[3]->id, $u3ja2->id);
        $this->assertEquals(array($manager8ja->userid), array_values($manageruserids));
        $manageruserids = \totara_job\job_assignment::get_all_manager_userids($this->users[4]->id, $u4ja1->id);
        $this->assertEquals(array($manager9ja->userid), array_values($manageruserids));
        $manageruserids = \totara_job\job_assignment::get_all_manager_userids($this->users[5]->id, $u5ja1->id);
        $this->assertEquals(array($tempmanager10ja->userid), array_values($manageruserids));
        $manageruserids = \totara_job\job_assignment::get_all_manager_userids($this->users[6]->id, $u6ja1->id);
        $this->assertEmpty($manageruserids);

        $manageruserids = \totara_job\job_assignment::get_all_manager_userids($this->users[1]->id, null, false);
        $this->assertEquals(array($manager8ja->userid), array_values($manageruserids));
        $manageruserids = \totara_job\job_assignment::get_all_manager_userids($this->users[2]->id, null, false);
        $this->assertEquals(array($manager8ja->userid, $manager9ja->userid), array_values($manageruserids));
        $manageruserids = \totara_job\job_assignment::get_all_manager_userids($this->users[3]->id, null, false);
        $this->assertEquals(array($manager8ja->userid), array_values($manageruserids));
        $manageruserids = \totara_job\job_assignment::get_all_manager_userids($this->users[4]->id, null, false);
        $this->assertEquals(array($manager9ja->userid), array_values($manageruserids));
        $manageruserids = \totara_job\job_assignment::get_all_manager_userids($this->users[5]->id, null, false);
        $this->assertEmpty($manageruserids);
        $manageruserids = \totara_job\job_assignment::get_all_manager_userids($this->users[6]->id, null, false);
        $this->assertEmpty($manageruserids);
    }

    /**
     * Tests is_managing().
     */
    public function test_is_managing() {
        $managerid = $this->users[5]->id;
        $managerja1 = \totara_job\job_assignment::create_default($managerid);
        $managerja2 = \totara_job\job_assignment::create_default($managerid);
        $manager2id = $this->users[6]->id;
        $manager2ja = \totara_job\job_assignment::create_default($manager2id);

        $u2ja1 = \totara_job\job_assignment::create_default($this->users[2]->id,
            array('managerjaid' => $managerja1->id));
        $u2ja2 = \totara_job\job_assignment::create_default($this->users[2]->id,
            array('tempmanagerjaid' => $managerja2->id, 'tempmanagerexpirydate' => time() + DAYSECS * 2));

        $u3ja1 = \totara_job\job_assignment::create_default($this->users[3]->id,
            array('managerjaid' => $managerja1->id));

        $u4ja1 = \totara_job\job_assignment::create_default($this->users[4]->id,
            array('tempmanagerjaid' => $managerja2->id, 'tempmanagerexpirydate' => time() + DAYSECS * 2));

        $u1ja1 = \totara_job\job_assignment::create_default($this->users[1]->id,
            array('managerjaid' => $u2ja1->id));

        $u7ja1 = \totara_job\job_assignment::create_default($this->users[7]->id,
            array('managerjaid' => $managerja1->id));
        $u7ja2 = \totara_job\job_assignment::create_default($this->users[7]->id,
            array('managerjaid' => $manager2ja->id));

        // Test including temp managers (default).
        $this->assertFalse(\totara_job\job_assignment::is_managing($managerid, $this->users[1]->id));
        $this->assertTrue(\totara_job\job_assignment::is_managing($managerid, $this->users[2]->id));
        $this->assertTrue(\totara_job\job_assignment::is_managing($managerid, $this->users[3]->id));
        $this->assertTrue(\totara_job\job_assignment::is_managing($managerid, $this->users[4]->id));
        $this->assertFalse(\totara_job\job_assignment::is_managing($this->users[4]->id, $this->users[2]->id));
        $this->assertTrue(\totara_job\job_assignment::is_managing($this->users[2]->id, $this->users[1]->id));

        // Test with user job assignment ids.
        $this->assertTrue(\totara_job\job_assignment::is_managing($managerid, $this->users[7]->id));
        $this->assertTrue(\totara_job\job_assignment::is_managing($manager2id, $this->users[7]->id));
        $this->assertTrue(\totara_job\job_assignment::is_managing($managerid, $this->users[7]->id, $u7ja1->id));
        $this->assertFalse(\totara_job\job_assignment::is_managing($manager2id, $this->users[7]->id, $u7ja1->id));
        $this->assertFalse(\totara_job\job_assignment::is_managing($managerid, $this->users[7]->id, $u7ja2->id));
        $this->assertTrue(\totara_job\job_assignment::is_managing($manager2id, $this->users[7]->id, $u7ja2->id));

        // Excluding temp managers.
        $this->assertFalse(\totara_job\job_assignment::is_managing($managerid, $this->users[1]->id, null, false));
        $this->assertTrue(\totara_job\job_assignment::is_managing($managerid, $this->users[2]->id, null, false));
        $this->assertTrue(\totara_job\job_assignment::is_managing($managerid, $this->users[3]->id, null, false));
        $this->assertFalse(\totara_job\job_assignment::is_managing($managerid, $this->users[4]->id, null, false)); // Changed to false.
        $this->assertFalse(\totara_job\job_assignment::is_managing($this->users[4]->id, $this->users[2]->id, null, false));
        $this->assertTrue(\totara_job\job_assignment::is_managing($this->users[2]->id, $this->users[1]->id, null, false));
    }
    /**
     * Tests has_staff().
     */
    public function test_has_staff() {
        $managerid = $this->users[5]->id;
        $managerja1 = \totara_job\job_assignment::create_default($managerid);
        $managerja2 = \totara_job\job_assignment::create_default($this->users[5]->id);

        $u2ja1 = \totara_job\job_assignment::create_default($this->users[2]->id,
            array('managerjaid' => $managerja1->id));
        $u2ja2 = \totara_job\job_assignment::create_default($this->users[2]->id,
            array('tempmanagerjaid' => $managerja1->id, 'tempmanagerexpirydate' => time() + DAYSECS * 2));

        $u3ja1 = \totara_job\job_assignment::create_default($this->users[3]->id,
            array('managerjaid' => $managerja1->id));

        $u4ja1 = \totara_job\job_assignment::create_default($this->users[4]->id,
            array('tempmanagerjaid' => $managerja2->id, 'tempmanagerexpirydate' => time() + DAYSECS * 2));

        $u1ja1 = \totara_job\job_assignment::create_default($this->users[1]->id,
            array('managerjaid' => $u2ja1->id));
        $u1ja2 = \totara_job\job_assignment::create_default($this->users[1]->id,
            array('tempmanagerjaid' => $u3ja1->id, 'tempmanagerexpirydate' => time() + DAYSECS * 2));

        // Test including temp managers (default).
        $this->assertTrue(\totara_job\job_assignment::has_staff($managerid));
        $this->assertFalse(\totara_job\job_assignment::has_staff($this->users[1]->id));
        $this->assertTrue(\totara_job\job_assignment::has_staff($this->users[2]->id));
        $this->assertTrue(\totara_job\job_assignment::has_staff($this->users[3]->id));
        $this->assertFalse(\totara_job\job_assignment::has_staff($this->users[4]->id));

        // Test including temp managers (default), with managerjaid.
        $this->assertTrue(\totara_job\job_assignment::has_staff($managerid, $managerja1->id));
        $this->assertTrue(\totara_job\job_assignment::has_staff($managerid, $managerja2->id));
        $this->assertFalse(\totara_job\job_assignment::has_staff($this->users[1]->id, $u1ja1->id));
        $this->assertFalse(\totara_job\job_assignment::has_staff($this->users[1]->id, $u1ja2->id));
        $this->assertTrue(\totara_job\job_assignment::has_staff($this->users[2]->id, $u2ja1->id));
        $this->assertFalse(\totara_job\job_assignment::has_staff($this->users[2]->id, $u2ja2->id));
        $this->assertTrue(\totara_job\job_assignment::has_staff($this->users[3]->id, $u3ja1->id));
        $this->assertFalse(\totara_job\job_assignment::has_staff($this->users[4]->id, $u4ja1->id));

        // Excluding temp managers.
        $this->assertTrue(\totara_job\job_assignment::has_staff($managerid, null, false));
        $this->assertFalse(\totara_job\job_assignment::has_staff($this->users[1]->id, null, false));
        $this->assertTrue(\totara_job\job_assignment::has_staff($this->users[2]->id, null, false));
        $this->assertFalse(\totara_job\job_assignment::has_staff($this->users[3]->id, null, false)); // Changed to false.
        $this->assertFalse(\totara_job\job_assignment::has_staff($this->users[4]->id, null, false));

        // Excluding temp managers, with managerjaid.
        $this->assertTrue(\totara_job\job_assignment::has_staff($managerid, $managerja1->id, false));
        $this->assertFalse(\totara_job\job_assignment::has_staff($managerid, $managerja2->id, false)); // Changed to false.
        $this->assertFalse(\totara_job\job_assignment::has_staff($this->users[1]->id, $u1ja1->id, false));
        $this->assertFalse(\totara_job\job_assignment::has_staff($this->users[1]->id, $u1ja2->id, false));
        $this->assertTrue(\totara_job\job_assignment::has_staff($this->users[2]->id, $u2ja1->id, false));
        $this->assertFalse(\totara_job\job_assignment::has_staff($this->users[2]->id, $u2ja2->id, false));
        $this->assertFalse(\totara_job\job_assignment::has_staff($this->users[3]->id, $u3ja1->id, false)); // Changed to false.
        $this->assertFalse(\totara_job\job_assignment::has_staff($this->users[4]->id, $u4ja1->id, false));
    }

    /**
     * Tests has_manager().
     */
    public function test_has_manager() {
        $managerid = $this->users[5]->id;
        $managerja1 = \totara_job\job_assignment::create_default($managerid);
        $managerja2 = \totara_job\job_assignment::create_default($this->users[5]->id);

        $u1ja1 = \totara_job\job_assignment::create_default($this->users[1]->id);

        $u2ja1 = \totara_job\job_assignment::create_default($this->users[2]->id,
            array('managerjaid' => $managerja1->id));
        $u2ja2 = \totara_job\job_assignment::create_default($this->users[2]->id,
            array('tempmanagerjaid' => $managerja1->id, 'tempmanagerexpirydate' => time() + DAYSECS * 2));

        $u3ja1 = \totara_job\job_assignment::create_default($this->users[3]->id,
            array('managerjaid' => $managerja1->id));

        $u4ja1 = \totara_job\job_assignment::create_default($this->users[4]->id,
            array('tempmanagerjaid' => $managerja2->id, 'tempmanagerexpirydate' => time() + DAYSECS * 2));

        // Test including temp managers (default).
        $this->assertFalse(\totara_job\job_assignment::has_manager($this->users[1]->id));
        $this->assertTrue(\totara_job\job_assignment::has_manager($this->users[2]->id));
        $this->assertTrue(\totara_job\job_assignment::has_manager($this->users[3]->id));
        $this->assertTrue(\totara_job\job_assignment::has_manager($this->users[4]->id));

        // Test including temp managers (default), with job assignment id.
        $this->assertFalse(\totara_job\job_assignment::has_manager($this->users[1]->id, $u1ja1->id));
        $this->assertTrue(\totara_job\job_assignment::has_manager($this->users[2]->id, $u2ja1->id));
        $this->assertTrue(\totara_job\job_assignment::has_manager($this->users[2]->id, $u2ja2->id));
        $this->assertTrue(\totara_job\job_assignment::has_manager($this->users[3]->id, $u3ja1->id));
        $this->assertTrue(\totara_job\job_assignment::has_manager($this->users[4]->id, $u4ja1->id));

        // Excluding temp managers.
        $this->assertFalse(\totara_job\job_assignment::has_manager($this->users[1]->id, null, false));
        $this->assertTrue(\totara_job\job_assignment::has_manager($this->users[2]->id, null, false));
        $this->assertTrue(\totara_job\job_assignment::has_manager($this->users[3]->id, null, false));
        $this->assertFalse(\totara_job\job_assignment::has_manager($this->users[4]->id, null, false)); // Changed to false.

        // Excluding temp managers, with managerjaid.
        $this->assertFalse(\totara_job\job_assignment::has_manager($this->users[1]->id, $u1ja1->id, false));
        $this->assertTrue(\totara_job\job_assignment::has_manager($this->users[2]->id, $u2ja1->id, false));
        $this->assertFalse(\totara_job\job_assignment::has_manager($this->users[2]->id, $u2ja2->id, false)); // Changed to false.
        $this->assertTrue(\totara_job\job_assignment::has_manager($this->users[3]->id, $u3ja1->id, false));
        $this->assertFalse(\totara_job\job_assignment::has_manager($this->users[4]->id, $u4ja1->id, false));
    }

    /**
     * Sets users up with job assignments and temporary managers.
     * Used with the test_update_temporary_managers tests that follow.
     *
     * @param int|bool $past - timestamp for temp manager expiry dates or false to only use
     * the $future timestamp value for expiry dates.
     * @param int $future - timestamp for temp manager expiry dates.
     * @param \totara_job\job_assignment $manager1ja - used for setting the users' managerjaid and tempmanagerjaid.
     * @param \totara_job\job_assignment $manager2ja - used for setting the users' managerjaid and tempmanagerjaid.
     * @return array of the users' job assignments.
     */
    private function set_job_assignments_with_tempmanagers($past, $future, $manager1ja, $manager2ja) {
        $jobassignments = array();

        if ($past === false) {
            // If we don't want a past date (because it's not relevant to the test), we'll
            // set all dates to the $future date.
            $past = $future;
        }

        // One job assignmnet and one temp manager.
        $jobassignments['1a'] = \totara_job\job_assignment::create_default($this->users[1]->id,
            array('tempmanagerjaid' => $manager1ja->id, 'tempmanagerexpirydate' => $past));

        // Two job assignments and temp manager on the second.
        $jobassignments['2a'] = \totara_job\job_assignment::create_default($this->users[2]->id);
        $jobassignments['2b'] = \totara_job\job_assignment::create_default($this->users[2]->id,
            array('tempmanagerjaid' => $manager1ja->id, 'tempmanagerexpirydate' => $past));

        // Two job assignments and different temp managers on each. A usual manager on one.
        $jobassignments['3a'] = \totara_job\job_assignment::create_default($this->users[3]->id,
            array('tempmanagerjaid' => $manager2ja->id, 'tempmanagerexpirydate' => $past));
        $jobassignments['3b'] = \totara_job\job_assignment::create_default($this->users[3]->id,
            array('tempmanagerjaid' => $manager1ja->id, 'tempmanagerexpirydate' => $past));

        // Two job assignments and the same temp manager on both.
        $jobassignments['4a'] = \totara_job\job_assignment::create_default($this->users[4]->id,
            array('tempmanagerjaid' => $manager2ja->id, 'tempmanagerexpirydate' => $past, 'managerjaid' => $manager1ja->id));
        $jobassignments['4b'] = \totara_job\job_assignment::create_default($this->users[4]->id,
            array('tempmanagerjaid' => $manager2ja->id, 'tempmanagerexpirydate' => $past));

        // Past and future dates on the different temp managers in each job assignment.
        $jobassignments['5a'] = \totara_job\job_assignment::create_default($this->users[5]->id,
            array('tempmanagerjaid' => $manager2ja->id, 'tempmanagerexpirydate' => $past));
        $jobassignments['5b'] = \totara_job\job_assignment::create_default($this->users[5]->id,
            array('tempmanagerjaid' => $manager1ja->id, 'tempmanagerexpirydate' => $future));
        $jobassignments['6a'] = \totara_job\job_assignment::create_default($this->users[6]->id,
            array('tempmanagerjaid' => $manager2ja->id, 'tempmanagerexpirydate' => $future));
        $jobassignments['6b'] = \totara_job\job_assignment::create_default($this->users[6]->id,
            array('tempmanagerjaid' => $manager2ja->id, 'tempmanagerexpirydate' => $past));

        // Repeat the cases for users 1 -4 but with future dates only.
        // The following are also the only ones returned if we don't ask for past expiry dates.

        // One job assignmnet and one temp manager.
        $jobassignments['7a'] = \totara_job\job_assignment::create_default($this->users[7]->id,
            array('tempmanagerjaid' => $manager1ja->id, 'tempmanagerexpirydate' => $future));
        // Two job assignments and temp manager on the second.
        $jobassignments['8a'] = \totara_job\job_assignment::create_default($this->users[8]->id);
        $jobassignments['8b'] = \totara_job\job_assignment::create_default($this->users[8]->id,
            array('tempmanagerjaid' => $manager1ja->id, 'tempmanagerexpirydate' => $future));
        // Two job assignments and different temp managers on each. A usual manager on one.
        $jobassignments['9a'] = \totara_job\job_assignment::create_default($this->users[9]->id,
            array('tempmanagerjaid' => $manager2ja->id, 'tempmanagerexpirydate' => $future));
        $jobassignments['9b'] = \totara_job\job_assignment::create_default($this->users[9]->id,
            array('tempmanagerjaid' => $manager1ja->id, 'tempmanagerexpirydate' => $future));
        // Two job assignments and the same temp manager on both.
        $jobassignments['10a'] = \totara_job\job_assignment::create_default($this->users[10]->id,
            array('tempmanagerjaid' => $manager2ja->id, 'tempmanagerexpirydate' => $future, 'managerjaid' => $manager1ja->id));
        $jobassignments['10b'] = \totara_job\job_assignment::create_default($this->users[10]->id,
            array('tempmanagerjaid' => $manager2ja->id, 'tempmanagerexpirydate' => $future));

        return $jobassignments;
    }

    /**
     * Tests update_temporary_managers() checking that expired temporary managers will be unset
     * in job assignment records.
     */
    public function test_update_temporary_managers_expiry() {
        global $DB;

        $data_generator = $this->getDataGenerator();
        $manager1 = $data_generator->create_user();
        $manager1ja = \totara_job\job_assignment::create_default($manager1->id);
        $manager2 = $data_generator->create_user();
        $manager2ja = \totara_job\job_assignment::create_default($manager2->id);

        // Timestamps
        $past = time() - 5 * DAYSECS;
        $future = time() + 5 * DAYSECS;

        $jobassignments = $this->set_job_assignments_with_tempmanagers($past, $future, $manager1ja, $manager2ja);

        // Do some basic pre-checks to ensure data is as it should be.

        // Total job assignments is 1 each for 2 managers. 3 users with 1 each and 8 users with 2 each.
        $this->assertEquals(20, $DB->count_records('job_assignment'));
        // Count how many job assignments with a temp manager there are.
        $this->assertEquals(16, $DB->count_records_sql('SELECT COUNT(id) FROM {job_assignment} WHERE tempmanagerjaid IS NOT NULL'));
        $this->assertEquals(16, $DB->count_records_sql('SELECT COUNT(id) FROM {job_assignment} WHERE tempmanagerexpirydate IS NOT NULL'));
        // Check there are the correct number of past and future expiry dates.
        $this->assertEquals(8, $DB->count_records('job_assignment', array('tempmanagerexpirydate' => $past)));
        $this->assertEquals(8, $DB->count_records('job_assignment', array('tempmanagerexpirydate' => $future)));

        // Run the function.
        ob_start();
        \totara_job\job_assignment::update_temporary_managers();
        ob_end_clean();

        // No job assignments should have been deleted, only updated.
        $this->assertEquals(20, $DB->count_records('job_assignment'));
        // Only the future expiry temp managers should remain.
        $this->assertEquals(8, $DB->count_records_sql('SELECT COUNT(id) FROM {job_assignment} WHERE tempmanagerjaid IS NOT NULL'));
        $this->assertEquals(8, $DB->count_records_sql('SELECT COUNT(id) FROM {job_assignment} WHERE tempmanagerexpirydate IS NOT NULL'));
        $this->assertEquals(0, $DB->count_records('job_assignment', array('tempmanagerexpirydate' => $past)));
        $this->assertEquals(8, $DB->count_records('job_assignment', array('tempmanagerexpirydate' => $future)));

        // Check the job assignment records specifically.
        // We'll check the basic scenario and then a couple of the more complicated ones such a mix of past and future dates.
        // The count checks above largely cover the rest.

        $user1ja_check = \totara_job\job_assignment::get_with_id($jobassignments['1a']->id);
        $this->assertNull($user1ja_check->tempmanagerjaid);
        $this->assertNull($user1ja_check->tempmanagerid);
        $this->assertNull($user1ja_check->tempmanagerexpirydate);

        $user4ja1_check = \totara_job\job_assignment::get_with_id($jobassignments['4a']->id);
        $this->assertNull($user4ja1_check->tempmanagerjaid);
        $this->assertNull($user4ja1_check->tempmanagerid);
        $this->assertNull($user4ja1_check->tempmanagerexpirydate);
        // But the usual manager should still be there.
        $this->assertEquals($manager1ja->id, $user4ja1_check->managerjaid);
        $this->assertEquals($manager1->id, $user4ja1_check->managerid);

        $user6ja1_check = \totara_job\job_assignment::get_with_id($jobassignments['6a']->id);
        $this->assertEquals($manager2ja->id, $user6ja1_check->tempmanagerjaid);
        $this->assertEquals($manager2->id, $user6ja1_check->tempmanagerid);
        $this->assertEquals($future, $user6ja1_check->tempmanagerexpirydate);

        $user6ja2_check = \totara_job\job_assignment::get_with_id($jobassignments['6b']->id);
        $this->assertNull($user6ja2_check->tempmanagerjaid);
        $this->assertNull($user6ja2_check->tempmanagerid);
        $this->assertNull($user6ja2_check->tempmanagerexpirydate);
    }

    /**
     * Tests update_temporary_managers() checking that if the 'tempmanagerrestrictselection' is
     * turned on, then any assigned temp managers will be restricted to only those that
     * are also (usual/non-temp) managers.
     */
    public function test_update_temporary_managers_restrict() {
        global $DB;

        $data_generator = $this->getDataGenerator();
        $manager1 = $data_generator->create_user();
        $manager1ja = \totara_job\job_assignment::create_default($manager1->id);
        $manager2 = $data_generator->create_user();
        $manager2ja = \totara_job\job_assignment::create_default($manager2->id);

        // Timestamps
        $future = time() + 5 * DAYSECS;
        // We don't want past expiry dates. We're isolating the restrict behaviour in this test.
        $jobassignments = $this->set_job_assignments_with_tempmanagers(false, $future, $manager1ja, $manager2ja);

        // Total job assignments is 1 each for 2 managers. 3 users with 1 each and 8 users with 2 each.
        $this->assertEquals(20, $DB->count_records('job_assignment'));
        // Count how many job assignments with a temp manager there are.
        $this->assertEquals(16, $DB->count_records_sql('SELECT COUNT(id) FROM {job_assignment} WHERE tempmanagerjaid IS NOT NULL'));
        $this->assertEquals(16, $DB->count_records_sql('SELECT COUNT(id) FROM {job_assignment} WHERE tempmanagerexpirydate IS NOT NULL'));
        // Check the expiry dates.
        $this->assertEquals(16, $DB->count_records('job_assignment', array('tempmanagerexpirydate' => $future)));

        // Set the config to restrict temp managers to those that are also usual managers for others.
        set_config('tempmanagerrestrictselection', 1);

        // Run the function.
        ob_start();
        \totara_job\job_assignment::update_temporary_managers();
        ob_end_clean();

        // No job assignments should have been deleted, only updated.
        $this->assertEquals(20, $DB->count_records('job_assignment'));
        // There should be just one tempmanager set up now.
        $this->assertEquals(7, $DB->count_records_sql('SELECT COUNT(id) FROM {job_assignment} WHERE tempmanagerjaid IS NOT NULL'));
        $this->assertEquals(7, $DB->count_records_sql('SELECT COUNT(id) FROM {job_assignment} WHERE tempmanagerexpirydate IS NOT NULL'));
        $this->assertEquals(7, $DB->count_records('job_assignment', array('tempmanagerexpirydate' => $future)));

        // Check that some records specifically.
        $user7ja1_check = \totara_job\job_assignment::get_with_id($jobassignments['7a']->id);
        $this->assertEquals($manager1ja->id, $user7ja1_check->tempmanagerjaid);
        $this->assertEquals($manager1->id, $user7ja1_check->tempmanagerid);
        $this->assertEquals($future, $user7ja1_check->tempmanagerexpirydate);

        $user9ja1_check = \totara_job\job_assignment::get_with_id($jobassignments['9a']->id);
        $this->assertNull($user9ja1_check->tempmanagerjaid);
        $this->assertNull($user9ja1_check->tempmanagerid);
        $this->assertNull($user9ja1_check->tempmanagerexpirydate);

        // This user had the usual manager assigned. Their temp manager was not a usual manager though, so should have been removed.
        $user10ja1_check = \totara_job\job_assignment::get_with_id($jobassignments['10a']->id);
        $this->assertNull($user10ja1_check->tempmanagerjaid);
        $this->assertNull($user10ja1_check->tempmanagerid);
        $this->assertNull($user10ja1_check->tempmanagerexpirydate);
        // But the usual manager should still be there.
        $this->assertEquals($manager1ja->id, $user10ja1_check->managerjaid);
        $this->assertEquals($manager1->id, $user10ja1_check->managerid);
    }

    /**
     * Tests update_temporary_managers() checking that if temp managers are disabled, that all
     * temporary managers are unset in job assignment records.
     */
    public function test_update_temporary_managers_disable() {
        global $DB;

        $data_generator = $this->getDataGenerator();
        $manager1 = $data_generator->create_user();
        $manager1ja = \totara_job\job_assignment::create_default($manager1->id);
        $manager2 = $data_generator->create_user();
        $manager2ja = \totara_job\job_assignment::create_default($manager2->id);

        // Timestamps
        $future = time() + 5 * DAYSECS;
        // We don't want past expiry dates. We're isolating the restrict behaviour in this test.
        $jobassignments = $this->set_job_assignments_with_tempmanagers(false, $future, $manager1ja, $manager2ja);

        // Total job assignments is 1 each for 2 managers. 3 users with 1 each and 8 users with 2 each.
        $this->assertEquals(20, $DB->count_records('job_assignment'));
        // Count how many job assignments with a temp manager there are.
        $this->assertEquals(16, $DB->count_records_sql('SELECT COUNT(id) FROM {job_assignment} WHERE tempmanagerjaid IS NOT NULL'));
        $this->assertEquals(16, $DB->count_records_sql('SELECT COUNT(id) FROM {job_assignment} WHERE tempmanagerexpirydate IS NOT NULL'));
        // Check the expiry dates.
        $this->assertEquals(16, $DB->count_records('job_assignment', array('tempmanagerexpirydate' => $future)));

        // Set the config such that temporary managers are disabled.
        set_config('enabletempmanagers', 0);

        // Run the function.
        ob_start();
        \totara_job\job_assignment::update_temporary_managers();
        ob_end_clean();

        // No job assignments should have been deleted, only updated.
        $this->assertEquals(20, $DB->count_records('job_assignment'));
        // There should be just no tempmanager set up now.
        $this->assertEquals(0, $DB->count_records_sql('SELECT COUNT(id) FROM {job_assignment} WHERE tempmanagerjaid IS NOT NULL'));
        $this->assertEquals(0, $DB->count_records_sql('SELECT COUNT(id) FROM {job_assignment} WHERE tempmanagerexpirydate IS NOT NULL'));
        $this->assertEquals(0, $DB->count_records('job_assignment', array('tempmanagerexpirydate' => $future)));

        // This user had the usual manager assigned. Only the temp manager should have been removed.
        $user10ja1_check = \totara_job\job_assignment::get_with_id($jobassignments['10a']->id);
        $this->assertNull($user10ja1_check->tempmanagerjaid);
        $this->assertNull($user10ja1_check->tempmanagerid);
        $this->assertNull($user10ja1_check->tempmanagerexpirydate);
        // But the usual manager should still be there.
        $this->assertEquals($manager1ja->id, $user10ja1_check->managerjaid);
        $this->assertEquals($manager1->id, $user10ja1_check->managerid);
    }

    /**
     * Test swap_order() and swap_order_internal().
     */
    public function test_swap_order() {
        $u1ja1 = \totara_job\job_assignment::create_default($this->users[1]->id);
        $u1ja2 = \totara_job\job_assignment::create_default($this->users[1]->id);
        $u1ja3 = \totara_job\job_assignment::create_default($this->users[1]->id);

        $u2ja1 = \totara_job\job_assignment::create_default($this->users[2]->id);
        $u2ja2 = \totara_job\job_assignment::create_default($this->users[2]->id);
        $u2ja3 = \totara_job\job_assignment::create_default($this->users[2]->id);

        $u3ja1 = \totara_job\job_assignment::create_default($this->users[3]->id);

        // Check that they are swapped.
        $previoustimemodified = max(array($u2ja1->timemodified, $u2ja2->timemodified, $u2ja3->timemodified));
        $this->waitForSecond();
        $this->assertEquals(1, $u2ja1->sortorder);
        $this->assertEquals(2, $u2ja2->sortorder);
        $this->assertEquals(3, $u2ja3->sortorder);
        \totara_job\job_assignment::swap_order($u2ja1->id, $u2ja3->id);
        // Reload from the db because the objects are invalid.
        $u2ja1 = \totara_job\job_assignment::get_with_id($u2ja1->id);
        $u2ja2 = \totara_job\job_assignment::get_with_id($u2ja2->id);
        $u2ja3 = \totara_job\job_assignment::get_with_id($u2ja3->id);
        $this->assertEquals(3, $u2ja1->sortorder);
        $this->assertEquals(2, $u2ja2->sortorder);
        $this->assertEquals(1, $u2ja3->sortorder);
        // Swap uses update(), so timemodified is changed.
        $this->assertGreaterThan($previoustimemodified, $u2ja1->timemodified);
        $this->assertLessThanOrEqual($previoustimemodified, $u2ja2->timemodified);
        $this->assertGreaterThan($previoustimemodified, $u2ja3->timemodified);

        // Mess around with them a bit more, just for fun.
        \totara_job\job_assignment::swap_order($u2ja3->id, $u2ja2->id);
        \totara_job\job_assignment::swap_order($u2ja1->id, $u2ja2->id);
        \totara_job\job_assignment::swap_order($u2ja1->id, $u2ja3->id);
        $u2ja1 = \totara_job\job_assignment::get_with_id($u2ja1->id);
        $u2ja2 = \totara_job\job_assignment::get_with_id($u2ja2->id);
        $u2ja3 = \totara_job\job_assignment::get_with_id($u2ja3->id);
        $this->assertEquals(2, $u2ja1->sortorder);
        $this->assertEquals(3, $u2ja2->sortorder);
        $this->assertEquals(1, $u2ja3->sortorder);

        // Check that no other job assignment was affected.
        $u1ja1 = \totara_job\job_assignment::get_with_id($u1ja1->id);
        $u1ja2 = \totara_job\job_assignment::get_with_id($u1ja2->id);
        $u1ja3 = \totara_job\job_assignment::get_with_id($u1ja3->id);
        $u3ja1 = \totara_job\job_assignment::get_with_id($u3ja1->id);
        $this->assertEquals(1, $u1ja1->sortorder);
        $this->assertEquals(2, $u1ja2->sortorder);
        $this->assertEquals(3, $u1ja3->sortorder);
        $this->assertEquals(1, $u3ja1->sortorder);

        // Check that you can't swap job assigments belonging to two different users.
        try {
            \totara_job\job_assignment::swap_order($u1ja3->id, $u2ja2->id);
            $this->fail('Exception not triggered!');
        } catch (Exception $e) {
            $this->assertEquals('Cannot swap order of two job assignments belonging to different users.', $e->getMessage());
        }
    }

    /**
     * Test move_up().
     */
    public function test_move_up() {
        $u1ja1 = \totara_job\job_assignment::create_default($this->users[1]->id);
        $u1ja2 = \totara_job\job_assignment::create_default($this->users[1]->id);
        $u1ja3 = \totara_job\job_assignment::create_default($this->users[1]->id);

        $u2ja1 = \totara_job\job_assignment::create_default($this->users[2]->id);
        $u2ja2 = \totara_job\job_assignment::create_default($this->users[2]->id);
        $u2ja3 = \totara_job\job_assignment::create_default($this->users[2]->id);

        $u3ja1 = \totara_job\job_assignment::create_default($this->users[3]->id);

        // Check that it is moved up.
        $previoustimemodified = max(array($u2ja1->timemodified, $u2ja2->timemodified, $u2ja3->timemodified));
        $this->waitForSecond();
        $this->assertEquals(1, $u2ja1->sortorder);
        $this->assertEquals(2, $u2ja2->sortorder);
        $this->assertEquals(3, $u2ja3->sortorder);
        \totara_job\job_assignment::move_up($u2ja3->id);
        // Reload from the db because the objects in memory are invalid.
        $u2ja1 = \totara_job\job_assignment::get_with_id($u2ja1->id);
        $u2ja2 = \totara_job\job_assignment::get_with_id($u2ja2->id);
        $u2ja3 = \totara_job\job_assignment::get_with_id($u2ja3->id);
        $this->assertEquals(1, $u2ja1->sortorder);
        $this->assertEquals(3, $u2ja2->sortorder);
        $this->assertEquals(2, $u2ja3->sortorder);
        // Move up uses update(), so timemodified is changed.
        $this->assertLessThanOrEqual($previoustimemodified, $u2ja1->timemodified);
        $this->assertGreaterThan($previoustimemodified, $u2ja2->timemodified);
        $this->assertGreaterThan($previoustimemodified, $u2ja3->timemodified);

        // Mess around with them a bit more, just for fun.
        \totara_job\job_assignment::move_up($u2ja3->id);
        \totara_job\job_assignment::move_up($u2ja2->id);
        \totara_job\job_assignment::move_up($u2ja1->id);
        $u2ja1 = \totara_job\job_assignment::get_with_id($u2ja1->id);
        $u2ja2 = \totara_job\job_assignment::get_with_id($u2ja2->id);
        $u2ja3 = \totara_job\job_assignment::get_with_id($u2ja3->id);
        $this->assertEquals(2, $u2ja1->sortorder);
        $this->assertEquals(3, $u2ja2->sortorder);
        $this->assertEquals(1, $u2ja3->sortorder);

        // Check that no other job assignment was affected.
        $u1ja1 = \totara_job\job_assignment::get_with_id($u1ja1->id);
        $u1ja2 = \totara_job\job_assignment::get_with_id($u1ja2->id);
        $u1ja3 = \totara_job\job_assignment::get_with_id($u1ja3->id);
        $u3ja1 = \totara_job\job_assignment::get_with_id($u3ja1->id);
        $this->assertEquals(1, $u1ja1->sortorder);
        $this->assertEquals(2, $u1ja2->sortorder);
        $this->assertEquals(3, $u1ja3->sortorder);
        $this->assertEquals(1, $u3ja1->sortorder);

        // Check that moving the first job assignment up does not work.
        try {
            \totara_job\job_assignment::move_up($u2ja3->id);
            $this->fail('Exception not triggered!');
        } catch (Exception $e) {
            $this->assertEquals('Tried to move the first job assignment up.', $e->getMessage());
        }
    }

    /**
     * Test move_down().
     */
    public function test_move_down() {
        $u1ja1 = \totara_job\job_assignment::create_default($this->users[1]->id);
        $u1ja2 = \totara_job\job_assignment::create_default($this->users[1]->id);
        $u1ja3 = \totara_job\job_assignment::create_default($this->users[1]->id);

        $u2ja1 = \totara_job\job_assignment::create_default($this->users[2]->id);
        $u2ja2 = \totara_job\job_assignment::create_default($this->users[2]->id);
        $u2ja3 = \totara_job\job_assignment::create_default($this->users[2]->id);

        $u3ja1 = \totara_job\job_assignment::create_default($this->users[3]->id);

        // Check that it is moved down.
        $previoustimemodified = max(array($u2ja1->timemodified, $u2ja2->timemodified, $u2ja3->timemodified));
        $this->waitForSecond();
        $this->assertEquals(1, $u2ja1->sortorder);
        $this->assertEquals(2, $u2ja2->sortorder);
        $this->assertEquals(3, $u2ja3->sortorder);
        \totara_job\job_assignment::move_down($u2ja1->id);
        // Reload from the db because the objects in memory are invalid.
        $u2ja1 = \totara_job\job_assignment::get_with_id($u2ja1->id);
        $u2ja2 = \totara_job\job_assignment::get_with_id($u2ja2->id);
        $u2ja3 = \totara_job\job_assignment::get_with_id($u2ja3->id);
        $this->assertEquals(2, $u2ja1->sortorder);
        $this->assertEquals(1, $u2ja2->sortorder);
        $this->assertEquals(3, $u2ja3->sortorder);
        // Move up uses update(), so timemodified is changed.
        $this->assertGreaterThan($previoustimemodified, $u2ja1->timemodified);
        $this->assertGreaterThan($previoustimemodified, $u2ja2->timemodified);
        $this->assertLessThanOrEqual($previoustimemodified, $u2ja3->timemodified);

        // Mess around with them a bit more, just for fun.
        \totara_job\job_assignment::move_down($u2ja1->id);
        \totara_job\job_assignment::move_down($u2ja2->id);
        \totara_job\job_assignment::move_down($u2ja3->id);
        $u2ja1 = \totara_job\job_assignment::get_with_id($u2ja1->id);
        $u2ja2 = \totara_job\job_assignment::get_with_id($u2ja2->id);
        $u2ja3 = \totara_job\job_assignment::get_with_id($u2ja3->id);
        $this->assertEquals(3, $u2ja1->sortorder);
        $this->assertEquals(1, $u2ja2->sortorder);
        $this->assertEquals(2, $u2ja3->sortorder);

        // Check that no other job assignment was affected.
        $u1ja1 = \totara_job\job_assignment::get_with_id($u1ja1->id);
        $u1ja2 = \totara_job\job_assignment::get_with_id($u1ja2->id);
        $u1ja3 = \totara_job\job_assignment::get_with_id($u1ja3->id);
        $u3ja1 = \totara_job\job_assignment::get_with_id($u3ja1->id);
        $this->assertEquals(1, $u1ja1->sortorder);
        $this->assertEquals(2, $u1ja2->sortorder);
        $this->assertEquals(3, $u1ja3->sortorder);
        $this->assertEquals(1, $u3ja1->sortorder);

        // Check that moving the first job assignment up does not work.
        try {
            \totara_job\job_assignment::move_down($u2ja1->id);
            $this->fail('Exception not triggered!');
        } catch (Exception $e) {
            $this->assertEquals('Tried to move the last job assignment down.', $e->getMessage());
        }
    }

    /**
     * Tests the retrieval of the team lead id.
     *
     * A team lead (which is the term appraisals uses) is currently defined as a
     * user's manager's manager. This position is unique among all the fields in
     * \totara_job\job_assignment in that it is totally computed on the fly.
     */
    public function test_get_team_leader() {
        $teamleadja = \totara_job\job_assignment::create_default($this->users[1]->id);

        $managerja = \totara_job\job_assignment::create([
            'userid' => $this->users[2]->id,
            'fullname' => 'manager',
            'shortname' => 'manager',
            'idnumber' => 'id1',
            'managerjaid' => $teamleadja->id, // User 1.
        ]);

        $userja = \totara_job\job_assignment::create([
            'userid' => $this->users[3]->id,
            'fullname' => 'user',
            'shortname' => 'user',
            'idnumber' => 'id2',
            'managerjaid' => $managerja->id, // User 2.
        ]);

        $this->assertEquals($userja->managerid, $managerja->userid);
        $this->assertEquals($userja->teamleaderid, $teamleadja->userid);

        $this->assertEquals($managerja->managerid, $teamleadja->userid);
        $this->assertNull($managerja->teamleaderid);

        $userdata = $userja->get_data();
        $this->assertEquals($userdata->managerid, $managerja->userid);
        $this->assertEquals($userdata->teamleaderid, $teamleadja->userid);

        $mgrdata = $managerja->get_data();
        $this->assertEquals($mgrdata->managerid, $teamleadja->userid);
        $this->assertNull($mgrdata->teamleaderid);
    }

    /**
     * Test resort all actually does what it says it will.
     */
    public function test_resort_all() {
        global $DB;

        $limit = 5;
        $teamleadja = \totara_job\job_assignment::create_default($this->users[1]->id);

        for ($i = 1; $i <= $limit; $i++) {
            $result = \totara_job\job_assignment::create([
                'userid' => $this->users[2]->id,
                'fullname' => 'manager'.$i,
                'shortname' => 'manager'.$i,
                'idnumber' => 'id'.$i,
                'managerjaid' => $teamleadja->id, // User 1.
            ]);
            $this->assertInstanceOf('\totara_job\job_assignment', $result);
        }

        $assignments = \totara_job\job_assignment::get_all($this->users[2]->id);
        $this->assertCount($limit, $assignments);

        $i = 1;
        $ids = array();
        foreach ($assignments as $assignment) {
            $this->assertEquals($i, $assignment->sortorder);
            $this->assertSame('manager'.$i, $assignment->fullname);
            $i++;
            $ids[] = $assignment->id;
        }

        // Check nothing changes if we call resort all with the current order.
        \totara_job\job_assignment::resort_all($this->users[2]->id, $ids);
        $assignments = \totara_job\job_assignment::get_all($this->users[2]->id);
        $count = 1;
        reset($ids);
        foreach ($assignments as $assignment) {
            $this->assertEquals(current($ids), $assignment->id);
            $this->assertEquals($count, $assignment->sortorder);
            $this->assertSame('manager'.$count, $assignment->fullname);
            $count++;
            next($ids);
        }

        // Reverse the job assignments sort order.
        $ids = array_reverse($ids);
        \totara_job\job_assignment::resort_all($this->users[2]->id, $ids);

        $assignments = \totara_job\job_assignment::get_all($this->users[2]->id);
        $i = $limit;
        $count = 1;
        reset($ids);
        foreach ($assignments as $assignment) {
            $this->assertEquals(current($ids), $assignment->id);
            $this->assertEquals($count, $assignment->sortorder);
            $this->assertSame('manager'.$i, $assignment->fullname);
            $i--;
            $count++;
            next($ids);
        }

        // Test we can't resort all assignments without giving all assignments.
        $original = array_pop($ids);
        try {
            \totara_job\job_assignment::resort_all($this->users[2]->id, $ids);
            $this->fail('Expected an exception as not all job assignments are present.');
        } catch (moodle_exception $e) {
            $this->assertEquals('Incomplete job list in submit data.', $e->debuginfo);
        }

        // And nothing should have changed.
        $assignments = \totara_job\job_assignment::get_all($this->users[2]->id);
        $i = $limit;
        $count = 1;
        $ids[] = $original;
        reset($ids);
        foreach ($assignments as $assignment) {
            $this->assertEquals(current($ids), $assignment->id);
            $this->assertEquals($count, $assignment->sortorder);
            $this->assertSame('manager'.$i, $assignment->fullname);
            $i--;
            $count++;
            next($ids);
        }

        // Check we can't pass in another users job assignment.
        $result = \totara_job\job_assignment::create([
            'userid' => $this->users[3]->id,
            'fullname' => 'user3',
            'shortname' => 'user3',
            'idnumber' => 'u3',
            'managerjaid' => $teamleadja->id, // User 1.
        ]);
        $original = array_pop($ids);
        $ids[] = $result->id;

        try {
            \totara_job\job_assignment::resort_all($this->users[2]->id, $ids);
            $this->fail('Expected an exception as not all job assignments are present.');
        } catch (moodle_exception $e) {
            $this->assertEquals('Incorrect job list in submit data.', $e->debuginfo);
        }
        // And nothing should have changed.
        $assignments = \totara_job\job_assignment::get_all($this->users[2]->id);
        $i = $limit;
        $count = 1;
        array_pop($ids);
        $ids[] = $original;
        reset($ids);
        foreach ($assignments as $assignment) {
            $this->assertEquals(current($ids), $assignment->id);
            $this->assertEquals($count, $assignment->sortorder);
            $this->assertSame('manager'.$i, $assignment->fullname);
            $i--;
            $count++;
            next($ids);
        }

        $assignment = $DB->get_record('job_assignment', array('id' => $result->id), '*', MUST_EXIST);
        foreach ($assignment as $key => $value) {
            $this->assertEquals($value, $result->{$key});
        }
    }
}
