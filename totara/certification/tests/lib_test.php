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
require_once($CFG->dirroot . '/totara/program/lib.php');

/**
 * Certification module PHPUnit archive test class.
 *
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose totara_certification_lib_testcase totara/certification/tests/lib_test.php
 */
class totara_certification_lib_testcase extends reportcache_advanced_testcase {

    public $users = array();
    public $programs = array();
    public $certifications = array();
    public $numtestusers = 10;
    public $numtestcerts = 12;
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
     * Asserts that the number of items in a recordset equals the given number, then close the recordset.
     *
     * @param int $expectedcount the expected number of items in the recordset
     * @param moodle_recordset $rs the recordset to iterate over and then close
     * @throws \PHPUnit\Framework\ExpectationFailedException
     */
    public function assert_count_and_close_recordset($expectedcount, $rs) {
        $i = 0;
        foreach ($rs as $item) {
            $i++;
        }
        $rs->close();

        if ($i != $expectedcount) {
            $this->fail('Recordset does not contain the expected number of items');
        }
    }

    public function test_find_courses_for_certif() {
        $this->resetAfterTest(true);

        // Set up some courses and certifications.
        $courses = array();
        $certifications = array();
        for ($i = 1; $i <= 10; $i++) {
            $courses[$i] = $this->getDataGenerator()->create_course();
            $certifications[$i] = $this->getDataGenerator()->create_certification();
        }

        // Set up some courses in the certifications.
        $this->getDataGenerator()->add_courseset_program($certifications[2]->id,
            array($courses[2]->id, $courses[3]->id, $courses[4]->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($certifications[2]->id,
            array($courses[3]->id, $courses[4]->id, $courses[5]->id), CERTIFPATH_RECERT);

        $this->getDataGenerator()->add_courseset_program($certifications[3]->id,
            array($courses[2]->id, $courses[3]->id, $courses[4]->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($certifications[3]->id,
            array($courses[3]->id, $courses[4]->id, $courses[5]->id), CERTIFPATH_RECERT);

        $this->getDataGenerator()->add_courseset_program($certifications[4]->id,
            array($courses[2]->id, $courses[3]->id, $courses[4]->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($certifications[4]->id,
            array($courses[2]->id, $courses[3]->id, $courses[4]->id), CERTIFPATH_RECERT);

        $this->getDataGenerator()->add_courseset_program($certifications[5]->id,
            array($courses[8]->id, $courses[9]->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($certifications[5]->id,
            array($courses[8]->id, $courses[9]->id), CERTIFPATH_RECERT);

        $this->getDataGenerator()->add_courseset_program($certifications[6]->id,
            array($courses[6]->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($certifications[6]->id,
            array($courses[7]->id), CERTIFPATH_RECERT);

        $this->getDataGenerator()->add_courseset_program($certifications[7]->id,
            array($courses[6]->id), CERTIFPATH_CERT);

        $this->getDataGenerator()->add_courseset_program($certifications[8]->id,
            array($courses[7]->id), CERTIFPATH_RECERT);

        // Call find_courses_for_certif with each of the three params and ensure that the correct courses are returned.
        $found = array_keys(find_courses_for_certif($certifications[2]->certifid, 'c.id'));
        sort($found);
        $this->assertEquals(array($courses[2]->id, $courses[3]->id, $courses[4]->id, $courses[5]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[2]->certifid, 'c.id', CERTIFPATH_CERT));
        sort($found);
        $this->assertEquals(array($courses[2]->id, $courses[3]->id, $courses[4]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[2]->certifid, 'c.id', CERTIFPATH_RECERT));
        sort($found);
        $this->assertEquals(array($courses[3]->id, $courses[4]->id, $courses[5]->id), $found);

        $found = array_keys(find_courses_for_certif($certifications[3]->certifid));
        sort($found);
        $this->assertEquals(array($courses[2]->id, $courses[3]->id, $courses[4]->id, $courses[5]->id), $found); // Note default fields.
        $found = array_keys(find_courses_for_certif($certifications[3]->certifid, 'c.id', CERTIFPATH_CERT));
        sort($found);
        $this->assertEquals(array($courses[2]->id, $courses[3]->id, $courses[4]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[3]->certifid, 'c.id', CERTIFPATH_RECERT));
        sort($found);
        $this->assertEquals(array($courses[3]->id, $courses[4]->id, $courses[5]->id), $found);

        $found = array_keys(find_courses_for_certif($certifications[4]->certifid, 'c.id'));
        sort($found);
        $this->assertEquals(array($courses[2]->id, $courses[3]->id, $courses[4]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[4]->certifid, 'c.id', CERTIFPATH_CERT));
        sort($found);
        $this->assertEquals(array($courses[2]->id, $courses[3]->id, $courses[4]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[4]->certifid, 'c.id', CERTIFPATH_RECERT));
        sort($found);
        $this->assertEquals(array($courses[2]->id, $courses[3]->id, $courses[4]->id), $found);

        $found = array_keys(find_courses_for_certif($certifications[5]->certifid, 'c.id'));
        sort($found);
        $this->assertEquals(array($courses[8]->id, $courses[9]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[5]->certifid, 'c.id', CERTIFPATH_CERT));
        sort($found);
        $this->assertEquals(array($courses[8]->id, $courses[9]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[5]->certifid, 'c.id', CERTIFPATH_RECERT));
        sort($found);
        $this->assertEquals(array($courses[8]->id, $courses[9]->id), $found);

        $found = array_keys(find_courses_for_certif($certifications[6]->certifid, 'c.id'));
        sort($found);
        $this->assertEquals(array($courses[6]->id, $courses[7]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[6]->certifid, 'c.id', CERTIFPATH_CERT));
        sort($found);
        $this->assertEquals(array($courses[6]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[6]->certifid, 'c.id', CERTIFPATH_RECERT));
        sort($found);
        $this->assertEquals(array($courses[7]->id), $found);

        $found = array_keys(find_courses_for_certif($certifications[7]->certifid, 'c.id'));
        sort($found);
        $this->assertEquals(array($courses[6]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[7]->certifid, 'c.id', CERTIFPATH_CERT));
        sort($found);
        $this->assertEquals(array($courses[6]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[7]->certifid, 'c.id', CERTIFPATH_RECERT));
        sort($found);
        $this->assertEquals(array(), $found);

        $found = array_keys(find_courses_for_certif($certifications[8]->certifid, 'c.id'));
        sort($found);
        $this->assertEquals(array($courses[7]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[8]->certifid, 'c.id', CERTIFPATH_CERT));
        sort($found);
        $this->assertEquals(array(), $found);
        $found = array_keys(find_courses_for_certif($certifications[8]->certifid, 'c.id', CERTIFPATH_RECERT));
        sort($found);
        $this->assertEquals(array($courses[7]->id), $found);

        $found = array_keys(find_courses_for_certif($certifications[9]->certifid, 'c.id'));
        sort($found);
        $this->assertEquals(array(), $found);
        $found = array_keys(find_courses_for_certif($certifications[9]->certifid, 'c.id', CERTIFPATH_CERT));
        sort($found);
        $this->assertEquals(array(), $found);
        $found = array_keys(find_courses_for_certif($certifications[9]->certifid, 'c.id', CERTIFPATH_RECERT));
        sort($found);
        $this->assertEquals(array(), $found);
    }

    /**
     * Note that this function is looking at course SET completion dates. A user might have completed the courses required
     * for a couse set, but if they weren't on that path then they won't have a course set completion record for it.
     */
    public function test_certif_get_content_completion_time() {
        $this->resetAfterTest(true);

        // Set up some courses and certifications.
        $courses = array();
        $certifications = array();
        for ($i = 1; $i <= 20; $i++) {
            $courses[$i] = $this->getDataGenerator()->create_course();
        }
        for ($i = 1; $i <= 10; $i++) {
            $certifications[$i] = $this->getDataGenerator()->create_certification();
        }

        // Set up some courses in the certifications.
        $this->getDataGenerator()->add_courseset_program($certifications[2]->id,
            array($courses[2]->id, $courses[3]->id, $courses[4]->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($certifications[2]->id,
            array($courses[5]->id, $courses[6]->id), CERTIFPATH_RECERT);

        $this->getDataGenerator()->add_courseset_program($certifications[4]->id,
            array($courses[7]->id, $courses[8]->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($certifications[4]->id,
            array($courses[8]->id, $courses[9]->id), CERTIFPATH_RECERT);

        $this->getDataGenerator()->add_courseset_program($certifications[6]->id,
            array($courses[11]->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($certifications[6]->id,
            array($courses[12]->id), CERTIFPATH_RECERT);

        // Set up some users.
        $users = array();
        for ($i = 1; $i <= 5; $i++) {
            $users[$i] = $this->getDataGenerator()->create_user();
        }

        // Assign all users to all certification as individuals.
        foreach ($certifications as $certification) {
            foreach ($users as $user) {
                $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
            }
        }

        // Mark some users as complete in some courses, with identifiable completion dates.
        $completiondatas = array(
            // Array(userindex, courseindex, time).
            array(1, 1, 1),
            array(1, 2, 1000),
            array(1, 3, 3000),
            array(1, 4, 2000),
            array(1, 5, 1),
            array(1, 6, 99999),
            array(1, 7, 1),
            array(1, 8, 99999),
            array(1, 9, 99999),
            array(1, 11, 1),
            array(1, 12, 99999),
            array(1, 13, 99999),

            array(2, 2, 8000),
            array(2, 3, 6000),
            array(2, 4, 7000),

            array(3, 2, 1000),
            array(3, 3, 4000),
            array(3, 4, 6000),
            array(3, 5, 1),
            array(3, 6, 99999),
            array(3, 7, 5000),
            array(3, 8, 3000),
            array(3, 9, 99999),
            array(3, 11, 1),
            array(3, 12, 99999),

            array(4, 2, 2000),
            array(4, 3, 3000),
            array(4, 4, 4000),
            array(4, 7, 1000),
            array(4, 8, 2000),
            array(4, 9, 99999),
            array(4, 11, 99999),
            array(4, 12, 5000),

            array(5, 2, 1),
            array(5, 4, 99999),
            array(5, 6, 1),
            array(5, 7, 99999),
        );

        // Put user 4 onto recert path for cert 6 (only complete recert path).
        list($certcompletion, $progcompletion) = certif_load_completion($certifications[6]->id, $users[4]->id);
        $certcompletion->status = CERTIFSTATUS_COMPLETED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_DUE;
        $certcompletion->certifpath = CERTIFPATH_RECERT;
        $certcompletion->timecompleted = 100;
        $certcompletion->timewindowopens = 200;
        $certcompletion->timeexpires = 300;
        $certcompletion->baselinetimeexpires = 300;
        $progcompletion->timedue = 300;
        $this->assertEquals(array(), certif_get_completion_errors($certcompletion, $progcompletion));
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));

        foreach ($completiondatas as $completiondata) {
            list($user, $course, $time) = $completiondata;
            $completion = new completion_completion(array('userid' => $users[$user]->id, 'course' => $courses[$course]->id));
            $completion->mark_complete($time);
        }

        // Put user 4 onto recert path for cert 2 (complete primary and recert paths).
        list($certcompletion, $progcompletion) = certif_load_completion($certifications[2]->id, $users[4]->id);
        $certcompletion->status = CERTIFSTATUS_COMPLETED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_DUE;
        $certcompletion->certifpath = CERTIFPATH_RECERT;
        $certcompletion->timecompleted = 100;
        $certcompletion->timewindowopens = 200;
        $certcompletion->timeexpires = 300;
        $certcompletion->baselinetimeexpires = 300;
        $progcompletion->timedue = 300;
        $progcompletion->timecompleted = 0;
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $this->assertEquals(array(), certif_get_completion_errors($certcompletion, $progcompletion));
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));

        $completion = new completion_completion(array('userid' => $users[4]->id, 'course' => $courses[5]->id));
        $completion->mark_complete(1000);
        $completion = new completion_completion(array('userid' => $users[4]->id, 'course' => $courses[6]->id));
        $completion->mark_complete(6000);

        // Call the function, checking if the correct times are returned.

        // These two check that the correct user's results are being returned.
        $this->assertEquals(3000, certif_get_content_completion_time($certifications[2]->certifid, $users[1]->id));
        $this->assertEquals(8000, certif_get_content_completion_time($certifications[2]->certifid, $users[2]->id));

        // These two check that the correct certification's results are being returned (on primary path).
        $this->assertEquals(6000, certif_get_content_completion_time($certifications[2]->certifid, $users[3]->id));
        $this->assertEquals(5000, certif_get_content_completion_time($certifications[4]->certifid, $users[3]->id));

        // These check that the correct certification path results are being returned.
        $this->assertEquals(2000, certif_get_content_completion_time($certifications[4]->certifid, $users[4]->id, CERTIFPATH_CERT));
        $this->assertNull(        certif_get_content_completion_time($certifications[4]->certifid, $users[4]->id, CERTIFPATH_RECERT)); // The prog completion record doesn't exist.
        $this->assertEmpty(       certif_get_content_completion_time($certifications[6]->certifid, $users[4]->id, CERTIFPATH_CERT)); // The prog completion record was created but is not completed.
        $this->assertEquals(5000, certif_get_content_completion_time($certifications[6]->certifid, $users[4]->id, CERTIFPATH_RECERT));
        $this->assertEquals(4000, certif_get_content_completion_time($certifications[2]->certifid, $users[4]->id, CERTIFPATH_CERT));
        $this->assertEquals(6000, certif_get_content_completion_time($certifications[2]->certifid, $users[4]->id, CERTIFPATH_RECERT));

        // Check result when course set not complete (user completed recert path courses, but is on primary path).
        $this->assertEmpty(certif_get_content_completion_time($certifications[2]->certifid, $users[5]->id));
    }

    /**
     * This tests that write_certif_completion is getting the timecompleted from the correct path courses.
     */
    public function test_write_certif_completion_timecompleted_course_path() {
        global $DB;

        $this->resetAfterTest(true);

        // Set up some stuff.
        $user = $this->getDataGenerator()->create_user();
        $certification = $this->getDataGenerator()->create_certification();
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();
        $course4 = $this->getDataGenerator()->create_course();

        // Add the courses to the certification.
        $this->getDataGenerator()->add_courseset_program($certification->id,
            array($course1->id, $course2->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($certification->id,
            array($course3->id, $course4->id), CERTIFPATH_RECERT);

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);

        // Mark all the courses complete, with traceable time completed.
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course1->id));
        $completion->mark_complete(1000);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course2->id));
        $completion->mark_complete(2000);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course3->id));
        $completion->mark_complete(4000);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course4->id));
        $completion->mark_complete(3000);

        // Check the existing data.
        $this->assertEquals(1, $DB->count_records('prog_completion', array('coursesetid' => 0)));
        $this->assertEquals(1, $DB->count_records('certif_completion'));
        list($certcompletion, $progcompletion) = certif_load_completion($certification->id, $user->id);
        $this->assertEquals(2000, $progcompletion->timecompleted);
        $this->assertEquals(2000, $certcompletion->timecompleted);

        // The user is now certified. Update the certification so that the window is open.
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_DUE;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion)); // Contains data validation, so we don't need to check it here.

        // Indirectly call write_certif_completion, causing the user to be marked certified again.
        prog_update_completion($user->id);

        // Verify the the user was marked complete using the dates in the recert path courses.
        list($certcompletion, $progcompletion) = certif_load_completion($certification->id, $user->id);
        $this->assertEquals(4000, $progcompletion->timecompleted);
        $this->assertEquals(4000, $certcompletion->timecompleted);

        // Update the certification so that the user is expired.
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $certcompletion->status = CERTIFSTATUS_EXPIRED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_EXPIRED;
        $certcompletion->certifpath = CERTIFPATH_CERT;
        $certcompletion->timecompleted = 0;
        $certcompletion->timewindowopens = 0;
        $certcompletion->timeexpires = 0;
        $certcompletion->baselinetimeexpires = 0;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion)); // Contains data validation, so we don't need to check it here.

        // Indirectly call write_certif_completion, causing the user to be marked certified again.
        prog_update_completion($user->id);

        // Verify the the user was marked complete using the dates in the primary cert path courses.
        list($certcompletion, $progcompletion) = certif_load_completion($certification->id, $user->id);
        $this->assertEquals(2000, $certcompletion->timecompleted);
        $this->assertEquals(2000, $progcompletion->timecompleted);
    }

    /**
     * Test getting the progress of a certification with a single course set.
     */
    public function test_prog_display_progress_single_then_courseset() {
        $this->resetAfterTest(true);
        $now = time();

        $certification = $this->getDataGenerator()->create_certification();
        $user = $this->getDataGenerator()->create_user();
        $courses = array();;
        for ($i = 1; $i <= 5; $i++) {
            $courses[$i] = $this->getDataGenerator()->create_course();
        }

        // Set up some courses in the certifications.
        $this->getDataGenerator()->add_courseset_program($certification->id,
            array($courses[1]->id, $courses[2]->id, $courses[5]->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($certification->id,
            array($courses[4]->id, $courses[1]->id), CERTIFPATH_RECERT);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);

        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $completion = new completion_completion(array('userid' => $user->id, 'course' => $courses[1]->id));
        $completion->mark_complete($now);

        $this->assertSame(33, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $completion = new completion_completion(array('userid' => $user->id, 'course' => $courses[2]->id));
        $completion->mark_complete($now);

        $this->assertSame(66, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $completion = new completion_completion(array('userid' => $user->id, 'course' => $courses[5]->id));
        $completion->mark_complete($now);

        // This is stupid - now its not a float!
        $this->assertSame(100, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
    }

    /**
     * Test getting the progress of a certification with two course sets.
     */
    public function test_prog_display_progress_two_then_coursesets() {
        $this->resetAfterTest(true);
        $now = time();

        $certification = $this->getDataGenerator()->create_certification();
        $user = $this->getDataGenerator()->create_user();
        $courses = array();;
        for ($i = 1; $i <= 7; $i++) {
            $courses[$i] = $this->getDataGenerator()->create_course();
        }

        // Set up some courses in the certifications.
        $this->getDataGenerator()->add_courseset_program($certification->id,
            array($courses[1]->id, $courses[2]->id, $courses[5]->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($certification->id,
            array($courses[4]->id, $courses[3]->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($certification->id,
            array($courses[6]->id, $courses[7]->id), CERTIFPATH_RECERT);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);

        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $completion = new completion_completion(array('userid' => $user->id, 'course' => $courses[1]->id));
        $completion->mark_complete($now);

        $this->assertSame(20, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $completion = new completion_completion(array('userid' => $user->id, 'course' => $courses[2]->id));
        $completion->mark_complete($now);

        $this->assertSame(40, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $completion = new completion_completion(array('userid' => $user->id, 'course' => $courses[5]->id));
        $completion->mark_complete($now);

        $this->assertSame(60, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $completion = new completion_completion(array('userid' => $user->id, 'course' => $courses[4]->id));
        $completion->mark_complete($now);

        $this->assertSame(80, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $completion = new completion_completion(array('userid' => $user->id, 'course' => $courses[3]->id));
        $completion->mark_complete($now);

        // This is stupid - now its not a float!
        $this->assertSame(100, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
    }

    /**
     * Completes the given course and then asserts that cert progress is as expected.
     *
     * @param float|int $expectedprogress
     * @param program $program
     * @param stdClass $user
     * @param stdClass $course
     * @param int $path
     */
    protected function assert_program_progress_after_course_completion($expectedprogress, program $program, stdClass $user, stdClass $course, $path = CERTIFPATH_CERT) {
        $completion = new completion_completion(['userid' => $user->id, 'course' => $course->id]);
        $completion->mark_complete(time());

        $this->assertTrue($completion->is_complete());

        $actualprogress = prog_display_progress($program->id, $user->id, $path, true);

        $this->assertSame($expectedprogress, $actualprogress, "Progress found to be $actualprogress but expected $expectedprogress");
    }

    /**
     * Assert that the parsed courseset groups are as you would expect.
     *
     * @param program $certification
     * @param array $expectednames An array of expected course set groups, each of which is an array containing the set names.
     */
    protected function assert_courseset_groups_contain_expected_names(program $certification, array $expectednames) {

        $certification = new program($certification->id);
        $courseset_groups = $certification->get_content()->get_courseset_groups(CERTIFPATH_CERT, true);

        $this->assertCount(count($expectednames), $courseset_groups);

        $courseset_group_names = [];
        foreach ($courseset_groups as $group) {
            $coursesetnames = [];
            foreach ($group as $courseset) {
                $coursesetnames[] = $courseset->label;
            }
            $courseset_group_names[] = $coursesetnames;
        }
        $this->assertSame($courseset_group_names, $expectednames);
    }

    /**
     * Test getting the progress of a certification with three course sets.
     *
     *  - Course set 1 (A, B, C)
     *      THEN
     *  - Course set 2 (D, E)
     *      THEN
     *  - Course set 3 (F)
     */
    public function test_prog_display_progress_three_then_coursesets() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 6; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }
        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1],
                    $courses[2],
                    $courses[3],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[4],
                    $courses[5],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[6]
                ]
            ]
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
            ],
            [
                'Course set 2',
            ],
            [
                'Course set 3',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);

        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $this->assert_program_progress_after_course_completion((int)((1/6)*100), $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion((int)((2/6)*100), $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion((int)((3/6)*100), $certification, $user, $courses[3]);
        $this->assert_program_progress_after_course_completion((int)((4/6)*100), $certification, $user, $courses[4]);
        $this->assert_program_progress_after_course_completion((int)((5/6)*100), $certification, $user, $courses[5]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[6]);
    }

    /**
     * Test getting the progress of a certification with two coursesets using the OR operator.
     */
    public function test_prog_display_progress_two_or_coursesets() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 4; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_OR,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1],
                    $courses[2],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_OR,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3],
                    $courses[4],
                ]
            ]
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $this->assert_program_progress_after_course_completion(50, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(50, $certification, $user, $courses[3]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[4]);
    }

    /**
     * Test getting the progress of a certification with two course sets using the AND operator.
     */
    public function test_prog_display_progress_two_and_coursesets() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 4; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1],
                    $courses[2],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3],
                    $courses[4],
                ]
            ]
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $this->assert_program_progress_after_course_completion((int)((1/4)*100), $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion((int)((2/4)*100), $certification, $user, $courses[3]);
        $this->assert_program_progress_after_course_completion((int)((3/4)*100), $certification, $user, $courses[4]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
    }

    /**
     * Test getting the progress of a certification with AND plus OR operators.
     *
     * This uses:
     *   Course set 1
     *      AND
     *   Course set 2
     *     THEN
     *   Course set 3
     *      OR
     *   Course set 4
     *     THEN
     *   Course set 5
     */
    public function test_prog_display_progress_and_plus_or_coursesets() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 10; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1],
                    $courses[2],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3],
                    $courses[4],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_OR,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[5],
                    $courses[6],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[7],
                    $courses[8],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[9],
                    $courses[10],
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
            ],
            [
                'Course set 3',
                'Course set 4',
            ],
            [
                'Course set 5',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $this->assert_program_progress_after_course_completion((int)((1/8)*100), $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion((int)((2/8)*100), $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion((int)((3/8)*100), $certification, $user, $courses[3]);
        $this->assert_program_progress_after_course_completion((int)((4/8)*100), $certification, $user, $courses[4]);
        $this->assert_program_progress_after_course_completion((int)((5/8)*100), $certification, $user, $courses[5]);
        $this->assert_program_progress_after_course_completion((int)((6/8)*100), $certification, $user, $courses[6]);
        $this->assert_program_progress_after_course_completion((int)((6/8)*100), $certification, $user, $courses[7]);
        $this->assert_program_progress_after_course_completion((int)((6/8)*100), $certification, $user, $courses[8]);
        $this->assert_program_progress_after_course_completion((int)((7/8)*100), $certification, $user, $courses[9]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[10]);
    }

    /**
     * Test getting the progress of a certification with OR plus AND operators.
     *
     * This uses:
     *   Course set 1
     *      OR
     *   Course set 2
     *     THEN
     *   Course set 3
     *      AND
     *   Course set 4
     *     THEN
     *   Course set 5
     */
    public function test_prog_display_progress_or_plus_and_coursesets() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 10; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_OR,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1],
                    $courses[2],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3],
                    $courses[4],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[5],
                    $courses[6],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[7],
                    $courses[8],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[9],
                    $courses[10],
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
            ],
            [
                'Course set 3',
                'Course set 4',
            ],
            [
                'Course set 5',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $this->assert_program_progress_after_course_completion((int)((1/8)*100), $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion((int)((2/8)*100), $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion((int)((2/8)*100), $certification, $user, $courses[3]);
        $this->assert_program_progress_after_course_completion((int)((2/8)*100), $certification, $user, $courses[4]);
        $this->assert_program_progress_after_course_completion((int)((3/8)*100), $certification, $user, $courses[5]);
        $this->assert_program_progress_after_course_completion((int)((4/8)*100), $certification, $user, $courses[6]);
        $this->assert_program_progress_after_course_completion((int)((5/8)*100), $certification, $user, $courses[7]);
        $this->assert_program_progress_after_course_completion((int)((6/8)*100), $certification, $user, $courses[8]);
        $this->assert_program_progress_after_course_completion((int)((7/8)*100), $certification, $user, $courses[9]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[10]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1
     *     THEN
     *   Course set 2 (Optional)
     *     THEN
     *   Course set 3
     *
     * Here we are testing three course sets of which one is optional.
     */
    public function test_prog_display_progress_simple_optional_coursesets() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();
        for ($i = 1; $i <= 6; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1],
                    $courses[2],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3],
                    $courses[4],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[5],
                    $courses[6],
                ]
            ]
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1'
            ],
            [
                'Course set 3'
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $this->assert_program_progress_after_course_completion((int)((1/4)*100), $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion((int)((2/4)*100), $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion((int)((2/4)*100), $certification, $user, $courses[3]);
        $this->assert_program_progress_after_course_completion((int)((2/4)*100), $certification, $user, $courses[4]);
        $this->assert_program_progress_after_course_completion((int)((3/4)*100), $certification, $user, $courses[5]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[6]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1 (Optional)
     *
     * Here we are testing that a single optional course set is fine.
     */
    public function test_prog_display_progress_single_optional_courseset() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $course = $generator->create_course(['summary' => 'A short summary']);

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $course
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, []);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        // Completion is checked during assignment.
        $this->assertSame(100, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $course);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1 (Optional)
     *      OR
     *   Course set 2 (Optional)
     *
     * Here we are testing optional or optional.
     */
    public function test_prog_display_progress_OoO() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 2; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_OR,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, []);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        // Completion is checked during assignment.
        $this->assertSame(100, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1 (Optional)
     *      AND
     *   Course set 2 (Optional)
     *
     * Here we are testing optional and optional.
     */
    public function test_prog_display_progress_OaO() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 2; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, []);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        // Completion is checked during assignment.
        $this->assertSame(100, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1
     *      AND
     *   Course set 2 (Optional)
     *
     * Here we are testing required and optional.
     */
    public function test_prog_display_progress_RaO() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 2; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2'
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1 (Optional)
     *      AND
     *   Course set 2
     *
     * Here we are testing optional and required.
     */
    public function test_prog_display_progress_OaR() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 2; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(0, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1 (Optional)
     *      OR
     *   Course set 2
     *
     * Here we are testing optional or required.
     */
    public function test_prog_display_progress_OoR() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 2; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_OR,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, []);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        // Completion is checked during assignment.
        $this->assertSame(100, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1
     *      AND
     *   Course set 2  (Optional)
     *      AND
     *   Course set 3  (Optional)
     *
     * Here we are testing required and optional and optional.
     */
    public function test_prog_display_progress_RaOaO() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 3; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
                'Course set 3',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[3]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1  (Optional)
     *      AND
     *   Course set 2  (Optional)
     *      AND
     *   Course set 3
     *
     * Here we are testing optional and optional and required.
     */
    public function test_prog_display_progress_OaOaR() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 3; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
                'Course set 3',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(0, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(0, $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[3]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1  (Optional)
     *      AND
     *   Course set 2
     *      AND
     *   Course set 3  (Optional)
     *
     * Here we are testing optional and required and optional.
     */
    public function test_prog_display_progress_OaRaO() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 3; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
                'Course set 3',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(0, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[3]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1
     *      AND
     *   Course set 2  (Optional)
     *      AND
     *   Course set 3
     *
     * Here we are testing required and optional and required.
     */
    public function test_prog_display_progress_RaOaR() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 3; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
                'Course set 3',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion((int)((1/2)*100), $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion((int)((1/2)*100), $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[3]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1
     *      AND
     *   Course set 2 (Optional)
     *     THEN
     *   Course set 3 (Optional)
     *      AND
     *   Course set 4
     *     THEN
     *   Course set 5 (Optional)
     *
     * Here we are testing three course sets groups each with an optional courseset.
     */
    public function test_prog_display_progress_RaOtOaRto() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 10; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1],
                    $courses[2],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3],
                    $courses[4],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[5],
                    $courses[6],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[7],
                    $courses[8],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[9],
                    $courses[10],
                ]
            ],
        ]);

        $certification = new program($certification->id);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
            ],
            [
                'Course set 3',
                'Course set 4',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $this->assert_program_progress_after_course_completion((int)((1/4)*100), $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion((int)((2/4)*100), $certification, $user, $courses[2]);
        // Complete set 1. Set 2 + 3 skipped as optional.
        $this->assert_program_progress_after_course_completion((int)((2/4)*100), $certification, $user, $courses[3]);
        $this->assert_program_progress_after_course_completion((int)((2/4)*100), $certification, $user, $courses[4]);
        $this->assert_program_progress_after_course_completion((int)((2/4)*100), $certification, $user, $courses[5]);
        $this->assert_program_progress_after_course_completion((int)((2/4)*100), $certification, $user, $courses[6]);
        $this->assert_program_progress_after_course_completion((int)((3/4)*100), $certification, $user, $courses[7]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[8]);
        // Completed set 4. Set 5 skipped as optional.
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[9]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[10]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1
     *      AND
     *   Course set 2 (Some - 0)
     *     THEN
     *   Course set 3 (Some - 0)
     *      AND
     *   Course set 4
     *     THEN
     *   Course set 5 (Some - 0)
     *
     * Here we are testing three course set groups each with a some courses = 0 courseset.
     */
    public function test_prog_display_progress_RaStSaRtS_mincourses_0() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 10; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1],
                    $courses[2],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_SOME,
                'certifpath' => CERTIFPATH_STD,
                'mincourses' => 0,
                'courses' => [
                    $courses[3],
                    $courses[4],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_SOME,
                'mincourses' => 0,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[5],
                    $courses[6],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[7],
                    $courses[8],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_SOME,
                'mincourses' => 0,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[9],
                    $courses[10],
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
            ],
            [
                'Course set 3',
                'Course set 4',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        // Mincourses == 0 implies that courseset is completed
        $this->assertSame((int)((3/7)*100), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true, true));

        $this->assert_program_progress_after_course_completion((int)((4/7)*100), $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion((int)((5/7)*100), $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion((int)((5/7)*100), $certification, $user, $courses[3]);
        $this->assert_program_progress_after_course_completion((int)((5/7)*100), $certification, $user, $courses[4]);
        $this->assert_program_progress_after_course_completion((int)((5/7)*100), $certification, $user, $courses[5]);
        $this->assert_program_progress_after_course_completion((int)((5/7)*100), $certification, $user, $courses[6]);
        $this->assert_program_progress_after_course_completion((int)((6/7)*100), $certification, $user, $courses[7]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[8]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[9]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[10]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1
     *      AND
     *   Course set 2 (Some - 1)
     *     THEN
     *   Course set 3 (Some - 1)
     *      AND
     *   Course set 4
     *     THEN
     *   Course set 5 (Some - 1)
     *
     * Here we are testing three course sets groups each with a some courses = 1 courseset.
     */
    public function test_prog_display_progress_RaStSaRtS_mincourses_1() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 10; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1],
                    $courses[2],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_SOME,
                'certifpath' => CERTIFPATH_STD,
                'mincourses' => 1,
                'courses' => [
                    $courses[3],
                    $courses[4],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_SOME,
                'mincourses' => 1,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[5],
                    $courses[6],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[7],
                    $courses[8],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_SOME,
                'mincourses' => 1,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[9],
                    $courses[10],
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
            ],
            [
                'Course set 3',
                'Course set 4',
            ],
            [
                'Course set 5',
            ],
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $this->assert_program_progress_after_course_completion((int)((1/7)*100), $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion((int)((2/7)*100), $certification, $user, $courses[2]); // Course set 1 complete now.

        $this->assert_program_progress_after_course_completion((int)((3/7)*100), $certification, $user, $courses[3]); // Course set 2 complete now.
        $this->assert_program_progress_after_course_completion((int)((3/7)*100), $certification, $user, $courses[4]);

        $this->assert_program_progress_after_course_completion((int)((4/7)*100), $certification, $user, $courses[5]); // Course set 3 complete now.
        $this->assert_program_progress_after_course_completion((int)((4/7)*100), $certification, $user, $courses[6]);

        $this->assert_program_progress_after_course_completion((int)((5/7)*100), $certification, $user, $courses[7]);
        $this->assert_program_progress_after_course_completion((int)((6/7)*100), $certification, $user, $courses[8]); // Course set 4 complete now.

        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[9]); // Course set 5 complete now.
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[10]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1
     *      AND
     *   Course set 2  (Optional)
     *      OR
     *   Course set 3  (Optional)
     *
     * Here we are testing required and optional or optional.
     */
    public function test_prog_display_progress_RaOoO() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 3; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_OR,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, []);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        // Completion is checked during assignment.
        $this->assertSame(100, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[3]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1  (Optional)
     *      AND
     *   Course set 2  (Optional)
     *      OR
     *   Course set 3
     *
     * Here we are testing optional and optional or required.
     */
    public function test_prog_display_progress_OaOoR() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 3; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_OR,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, []);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        // Completion is checked during assignment.
        $this->assertSame(100, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[3]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1  (Optional)
     *      AND
     *   Course set 2
     *      OR
     *   Course set 3  (Optional)
     *
     * Here we are testing optional and required or optional.
     */
    public function test_prog_display_progress_OaRoO() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 3; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_OR,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, []);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        // Completion is checked during assignment.
        $this->assertSame(100, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[3]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1
     *      AND
     *   Course set 2  (Optional)
     *      OR
     *   Course set 3
     *
     * Here we are testing required and optional or required.
     */
    public function test_prog_display_progress_RaOoR() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 3; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $generator->create_certification([], [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_OR,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3]
                ]
            ],
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
                'Course set 3'
            ]
        ]);

        // Assign the user to the cert as an individual.
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[3]);
    }

    /**
     * Test certif_create_completion. This test doesn't test the reassignment code within certif_create_completion
     * because that is already heavily tested below.
     */
    public function test_certif_create_completion() {
        global $DB;

        $this->resetAfterTest();

        // Set up some stuff.
        $user = $this->getDataGenerator()->create_user();

        $prog = $this->getDataGenerator()->create_program();
        $cert = $this->getDataGenerator()->create_certification();

        // Check that we get an exception if we try to do it with a program.
        try {
            certif_create_completion($prog->id, $user->id);
            $this->fail('Expected exception!');
        } catch (moodle_exception $e) {
            $this->assertEquals('Attempting to create certification completion record for non-certification program.',
                $e->getMessage());
        }

        // Create a non-zero course set group completion record to make sure that it doesn't interfere with the later steps.
        $data = new stdClass();
        $data->programid = $cert->id;
        $data->userid = $user->id;
        $data->coursesetid = 1;
        $data->status = STATUS_PROGRAM_COMPLETE;
        $data->timestarted = 987;
        $data->timedue = 876;
        $data->timecompleted = 765;
        $DB->insert_record('prog_completion', $data);

        // Check that two records created successfully if none already exist.
        $timebefore = time();
        certif_create_completion($cert->id, $user->id);
        $timeafter = time();

        list($certcompletion, $progcompletion) = certif_load_completion($cert->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);
        $this->assertEquals(0, $progcompletion->timecompleted);
        $this->assertEquals(COMPLETION_TIME_NOT_SET, $progcompletion->timedue);
        $this->assertGreaterThanOrEqual($timebefore, $progcompletion->timecreated);
        $this->assertLessThanOrEqual($timeafter, $progcompletion->timecreated);
        $this->assertEquals(0, $progcompletion->timestarted);
        $this->assertEquals(CERTIFPATH_CERT, $certcompletion->certifpath);
        $this->assertEquals(CERTIFSTATUS_ASSIGNED, $certcompletion->status);
        $this->assertEquals(CERTIFRENEWALSTATUS_NOTDUE, $certcompletion->renewalstatus);
        $this->assertEquals(0, $certcompletion->timecompleted);
        $this->assertEquals(0, $certcompletion->timewindowopens);
        $this->assertEquals(0, $certcompletion->timeexpires);
        $this->assertEquals(0, $certcompletion->baselinetimeexpires);
        $this->assertGreaterThanOrEqual($timebefore, $certcompletion->timemodified);
        $this->assertLessThanOrEqual($timeafter, $certcompletion->timemodified);

        // Check that the log was created.
        $lastlog = $DB->get_records('prog_completion_log', array(), 'id DESC', '*', 0, 2);
        $lastlog = reset($lastlog);
        $this->assertEquals($cert->id, $lastlog->programid);
        $this->assertEquals($user->id, $lastlog->userid);
        $this->assertStringStartsWith('Created new certif_completion and new prog_completion', $lastlog->description);

        // Check that nothing happens if the records already exist.
        $progcompletion->status = 123;
        $progcompletion->timestarted = 234;
        $progcompletion->timedue = 345;
        $progcompletion->timecompleted = 456;
        $DB->update_record('prog_completion', $progcompletion); // Make the existing records unique so we will know it is unchanged.
        $certcompletion->certifpath = 5;
        $certcompletion->status = 9;
        $certcompletion->renewalstatus = 8;
        $certcompletion->timecompleted = 567;
        $certcompletion->timewindowopens = 678;
        $certcompletion->timemodified = 789;
        $DB->update_record('certif_completion', $certcompletion);
        certif_create_completion($cert->id, $user->id);
        list($newcertcompletion, $newprogcompletion) = certif_load_completion($cert->id, $user->id);
        $this->assertEquals($certcompletion, $newcertcompletion);
        $this->assertEquals($progcompletion, $newprogcompletion);

        // Check that no new log has been created.
        $newlatestlog = $DB->get_records('prog_completion_log', array(), 'id DESC', '*', 0, 1);
        $newlatestlog = reset($newlatestlog);
        $this->assertEquals($lastlog, $newlatestlog);

        // Check that certif_completion is created if only prog_completion exists, and set it to incomplete if it isn't
        // already. But timestarted and timedue should be unaltered.
        $DB->delete_records('certif_completion', array('id' => $certcompletion->id));

        $this->waitForSecond();
        $timebefore = time();
        certif_create_completion($cert->id, $user->id);
        $timeafter = time();

        list($newcertcompletion, $newprogcompletion) = certif_load_completion($cert->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $newprogcompletion->status);
        $this->assertEquals($progcompletion->timecreated, $newprogcompletion->timecreated);
        $this->assertEquals($progcompletion->timestarted, $newprogcompletion->timestarted);
        $this->assertEquals($progcompletion->timedue, $newprogcompletion->timedue);
        $this->assertEquals(0, $newprogcompletion->timecompleted);
        $this->assertEquals(CERTIFPATH_CERT, $newcertcompletion->certifpath);
        $this->assertEquals(CERTIFSTATUS_ASSIGNED, $newcertcompletion->status);
        $this->assertEquals(CERTIFRENEWALSTATUS_NOTDUE, $newcertcompletion->renewalstatus);
        $this->assertEquals(0, $newcertcompletion->timecompleted);
        $this->assertEquals(0, $newcertcompletion->timewindowopens);
        $this->assertEquals(0, $newcertcompletion->timeexpires);
        $this->assertEquals(0, $newcertcompletion->baselinetimeexpires);
        $this->assertGreaterThanOrEqual($timebefore, $newcertcompletion->timemodified);
        $this->assertLessThanOrEqual($timeafter, $newcertcompletion->timemodified);

        // Check that the log was created.
        $lastlog = $DB->get_records('prog_completion_log', array(), 'id DESC', '*', 0, 1);
        $lastlog = reset($lastlog);
        $this->assertEquals($cert->id, $lastlog->programid);
        $this->assertEquals($user->id, $lastlog->userid);
        $this->assertStringStartsWith('Created new certif_completion for existing prog_completion', $lastlog->description);

        // Check that prog_completion is created if only certif_completion exists.
        $progcompletion = $newprogcompletion;
        $certcompletion = $newcertcompletion;
        $DB->delete_records('prog_completion', array('id' => $progcompletion->id));

        $this->waitForSecond();
        $timebefore = time();
        certif_create_completion($cert->id, $user->id);
        $timeafter = time();

        list($newcertcompletion, $newprogcompletion) = certif_load_completion($cert->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $newprogcompletion->status);
        $this->assertGreaterThanOrEqual($timebefore, $newprogcompletion->timecreated);
        $this->assertLessThanOrEqual($timeafter, $newprogcompletion->timecreated);
        $this->assertEquals(0, $newprogcompletion->timestarted);
        $this->assertEquals(COMPLETION_TIME_NOT_SET, $newprogcompletion->timedue);
        $this->assertEquals(0, $newprogcompletion->timecompleted);
        $this->assertEquals($certcompletion->certifpath, $newcertcompletion->certifpath);
        $this->assertEquals($certcompletion->status, $newcertcompletion->status);
        $this->assertEquals($certcompletion->renewalstatus, $newcertcompletion->renewalstatus);
        $this->assertEquals($certcompletion->timecompleted, $newcertcompletion->timecompleted);
        $this->assertEquals($certcompletion->timewindowopens, $newcertcompletion->timewindowopens);
        $this->assertEquals($certcompletion->timeexpires, $newcertcompletion->timeexpires);
        $this->assertEquals($certcompletion->baselinetimeexpires, $newcertcompletion->baselinetimeexpires);
        $this->assertEquals($certcompletion->timemodified, $newcertcompletion->timemodified);

        // Check that the log was created.
        $lastlog = $DB->get_records('prog_completion_log', array(), 'id DESC', '*', 0, 1);
        $lastlog = reset($lastlog);
        $this->assertEquals($cert->id, $lastlog->programid);
        $this->assertEquals($user->id, $lastlog->userid);
        $this->assertStringStartsWith('Created missing prog_completion record for existing certif_completion', $lastlog->description);
    }

    /**
     * Tests that certif_create_completion works when the prog_completion doesn't exist, but history does.
     * In this case, the prog_completion will start out incomplete and will be updated to whatever it needs to be.
     */
    public function test_certif_create_completion_missing_prog_completion() {
        $this->resetAfterTest(true);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $cert1 = $this->getDataGenerator()->create_certification();
        $cert2 = $this->getDataGenerator()->create_certification();
        $cert3 = $this->getDataGenerator()->create_certification();

        //////////////////////////////
        // Assigned certif_completion.
        $certid = $cert1->certifid;
        $progid = $cert1->id;
        $userid = $user1->id;

        // Set up the history record.
        $certcompletionhistory = new stdClass();
        $certcompletionhistory->certifid = $certid;
        $certcompletionhistory->userid = $userid;
        $certcompletionhistory->certifpath = CERTIFPATH_CERT;
        $certcompletionhistory->status = CERTIFSTATUS_ASSIGNED;
        $certcompletionhistory->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletionhistory->timewindowopens = 0;
        $certcompletionhistory->timeexpires = 0;
        $certcompletionhistory->baselinetimeexpires = 0;
        $certcompletionhistory->timecompleted = 0;
        $certcompletionhistory->timemodified = 123;
        $certcompletionhistory->unassigned = 1;
        certif_write_completion_history($certcompletionhistory);

        // Run the function.
        certif_create_completion($progid, $userid);

        // Load the data.
        list($newcertcompletion, $newprogcompletion) = certif_load_completion($progid, $userid);
        $this->assertEmpty(certif_get_completion_errors($newcertcompletion, $newprogcompletion));

        // Set up the expected certif_completion.
        $expectedcertcompletion = $certcompletionhistory;
        unset($expectedcertcompletion->timemodified);
        unset($expectedcertcompletion->unassigned);
        unset($newcertcompletion->id);
        unset($newcertcompletion->timemodified);

        // Set up the expected prog_completion.
        $expectedprogcompletion = new stdClass();
        $expectedprogcompletion->programid = $progid;
        $expectedprogcompletion->userid = $userid;
        $expectedprogcompletion->coursesetid = 0;
        $expectedprogcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $expectedprogcompletion->timestarted = 0;
        $expectedprogcompletion->timedue = COMPLETION_TIME_NOT_SET;
        $expectedprogcompletion->timecompleted = 0;
        $expectedprogcompletion->organisationid = null;
        $expectedprogcompletion->positionid = null;
        unset($newprogcompletion->id);
        unset($newprogcompletion->timecreated);

        // Check that they match.
        $this->assertEquals($expectedcertcompletion, $newcertcompletion);
        $this->assertEquals($expectedprogcompletion, $newprogcompletion);

        ///////////////////////////////
        // Certified certif_completion.
        $certid = $cert2->certifid;
        $progid = $cert2->id;
        $userid = $user2->id;

        // Set up the history record.
        $certcompletionhistory = new stdClass();
        $certcompletionhistory->certifid = $certid;
        $certcompletionhistory->userid = $userid;
        $certcompletionhistory->certifpath = CERTIFPATH_RECERT;
        $certcompletionhistory->status = CERTIFSTATUS_COMPLETED;
        $certcompletionhistory->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletionhistory->timewindowopens = 345;
        $certcompletionhistory->timeexpires = 456;
        $certcompletionhistory->baselinetimeexpires = 454;
        $certcompletionhistory->timecompleted = 234;
        $certcompletionhistory->timemodified = 123;
        $certcompletionhistory->unassigned = 1;
        certif_write_completion_history($certcompletionhistory);

        // Run the function.
        certif_create_completion($progid, $userid);

        // Load the data.
        list($newcertcompletion, $newprogcompletion) = certif_load_completion($progid, $userid);
        $this->assertEmpty(certif_get_completion_errors($newcertcompletion, $newprogcompletion));

        // Set up the expected certif_completion.
        $expectedcertcompletion = $certcompletionhistory;
        unset($expectedcertcompletion->timemodified);
        unset($expectedcertcompletion->unassigned);
        unset($newcertcompletion->id);
        unset($newcertcompletion->timemodified);

        // Set up the expected prog_completion.
        $expectedprogcompletion = new stdClass();
        $expectedprogcompletion->programid = $progid;
        $expectedprogcompletion->userid = $userid;
        $expectedprogcompletion->coursesetid = 0;
        $expectedprogcompletion->status = STATUS_PROGRAM_COMPLETE;
        $expectedprogcompletion->timestarted = 0;
        $expectedprogcompletion->timedue = 456;
        $expectedprogcompletion->timecompleted = 234;
        $expectedprogcompletion->organisationid = null;
        $expectedprogcompletion->positionid = null;
        unset($newprogcompletion->id);
        unset($newprogcompletion->timecreated);

        // Check that they match.
        $this->assertEquals($expectedcertcompletion, $newcertcompletion);
        $this->assertEquals($expectedprogcompletion, $newprogcompletion);

        /////////////////////////////////
        // Window open certif_completion.
        $certid = $cert3->certifid;
        $progid = $cert3->id;
        $userid = $user3->id;

        // Set up the history record.
        $certcompletionhistory = new stdClass();
        $certcompletionhistory->certifid = $certid;
        $certcompletionhistory->userid = $userid;
        $certcompletionhistory->certifpath = CERTIFPATH_RECERT;
        $certcompletionhistory->status = CERTIFSTATUS_INPROGRESS; // Just for fun.
        $certcompletionhistory->renewalstatus = CERTIFRENEWALSTATUS_DUE;
        $certcompletionhistory->timewindowopens = 345;
        $certcompletionhistory->timeexpires = 456;
        $certcompletionhistory->baselinetimeexpires = 454;
        $certcompletionhistory->timecompleted = 234;
        $certcompletionhistory->timemodified = 123;
        $certcompletionhistory->unassigned = 1;
        certif_write_completion_history($certcompletionhistory);

        // Run the function.
        certif_create_completion($progid, $userid);

        // Load the data.
        list($newcertcompletion, $newprogcompletion) = certif_load_completion($progid, $userid);
        $this->assertEmpty(certif_get_completion_errors($newcertcompletion, $newprogcompletion));

        // Set up the expected certif_completion.
        $expectedcertcompletion = $certcompletionhistory;
        unset($expectedcertcompletion->timemodified);
        unset($expectedcertcompletion->unassigned);
        unset($newcertcompletion->id);
        unset($newcertcompletion->timemodified);

        // Set up the expected prog_completion.
        $expectedprogcompletion = new stdClass();
        $expectedprogcompletion->programid = $progid;
        $expectedprogcompletion->userid = $userid;
        $expectedprogcompletion->coursesetid = 0;
        $expectedprogcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $expectedprogcompletion->timestarted = 0;
        $expectedprogcompletion->timedue = 456;
        $expectedprogcompletion->timecompleted = 0;
        $expectedprogcompletion->organisationid = null;
        $expectedprogcompletion->positionid = null;
        unset($newprogcompletion->id);
        unset($newprogcompletion->timecreated);

        // Check that they match.
        $this->assertEquals($expectedcertcompletion, $newcertcompletion);
        $this->assertEquals($expectedprogcompletion, $newprogcompletion);

        /////////////////////////////
        // Expired certif_completion.
        $certid = $cert1->certifid;
        $progid = $cert1->id;
        $userid = $user2->id;

        // Set up the history record that will be used for program timedue, created when window opened.
        $certcompletionhistory = new stdClass();
        $certcompletionhistory->certifid = $certid;
        $certcompletionhistory->userid = $userid;
        $certcompletionhistory->certifpath = CERTIFPATH_RECERT;
        $certcompletionhistory->status = CERTIFSTATUS_COMPLETED;
        $certcompletionhistory->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletionhistory->timewindowopens = 345;
        $certcompletionhistory->timeexpires = 456;
        $certcompletionhistory->baselinetimeexpires = 456;
        $certcompletionhistory->timecompleted = 234;
        $certcompletionhistory->timemodified = 123;
        $certcompletionhistory->unassigned = 0;
        certif_write_completion_history($certcompletionhistory);

        // Set up the history record that will be restored.
        $certcompletionhistory = new stdClass();
        $certcompletionhistory->certifid = $certid;
        $certcompletionhistory->userid = $userid;
        $certcompletionhistory->certifpath = CERTIFPATH_CERT;
        $certcompletionhistory->status = CERTIFSTATUS_EXPIRED;
        $certcompletionhistory->renewalstatus = CERTIFRENEWALSTATUS_EXPIRED;
        $certcompletionhistory->timewindowopens = 0;
        $certcompletionhistory->timeexpires = 0;
        $certcompletionhistory->baselinetimeexpires = 0;
        $certcompletionhistory->timecompleted = 0;
        $certcompletionhistory->timemodified = 123;
        $certcompletionhistory->unassigned = 1;
        certif_write_completion_history($certcompletionhistory);

        // Run the function.
        certif_create_completion($progid, $userid);

        // Load the data.
        list($newcertcompletion, $newprogcompletion) = certif_load_completion($progid, $userid);
        $this->assertEmpty(certif_get_completion_errors($newcertcompletion, $newprogcompletion));

        // Set up the expected certif_completion.
        $expectedcertcompletion = $certcompletionhistory;
        unset($expectedcertcompletion->timemodified);
        unset($expectedcertcompletion->unassigned);
        unset($newcertcompletion->id);
        unset($newcertcompletion->timemodified);

        // Set up the expected prog_completion.
        $expectedprogcompletion = new stdClass();
        $expectedprogcompletion->programid = $progid;
        $expectedprogcompletion->userid = $userid;
        $expectedprogcompletion->coursesetid = 0;
        $expectedprogcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $expectedprogcompletion->timestarted = 0;
        $expectedprogcompletion->timedue = 456; // Restored from other history.
        $expectedprogcompletion->timecompleted = 0;
        $expectedprogcompletion->organisationid = null;
        $expectedprogcompletion->positionid = null;
        unset($newprogcompletion->id);
        unset($newprogcompletion->timecreated);

        // Check that they match.
        $this->assertEquals($expectedcertcompletion, $newcertcompletion);
        $this->assertEquals($expectedprogcompletion, $newprogcompletion);
    }

    /**
     * Tests that certif_create_completion works when the prog_completion exists, but doesn't match the history being restored.
     * In this case, we'll mostly use a prog_completion in a complete state, since the incomplete state is covered by the
     * previous test, but be sure to keep other details from the original prog_completion.
     */
    public function test_certif_create_completion_mismatched_prog_completion() {
        global $DB;

        $this->resetAfterTest(true);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $cert1 = $this->getDataGenerator()->create_certification();
        $cert2 = $this->getDataGenerator()->create_certification();
        $cert3 = $this->getDataGenerator()->create_certification();

        //////////////////////////////
        // Assigned certif_completion.
        $certid = $cert1->certifid;
        $progid = $cert1->id;
        $userid = $user1->id;

        // Set up the history record.
        $certcompletionhistory = new stdClass();
        $certcompletionhistory->certifid = $certid;
        $certcompletionhistory->userid = $userid;
        $certcompletionhistory->certifpath = CERTIFPATH_CERT;
        $certcompletionhistory->status = CERTIFSTATUS_ASSIGNED;
        $certcompletionhistory->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletionhistory->timewindowopens = 0;
        $certcompletionhistory->timeexpires = 0;
        $certcompletionhistory->baselinetimeexpires = 0;
        $certcompletionhistory->timecompleted = 0;
        $certcompletionhistory->timemodified = 123;
        $certcompletionhistory->unassigned = 1;
        $this->assertTrue(certif_write_completion_history($certcompletionhistory));

        // Set up the prog_completion record, setting fields to values that are inconsistent with the history.
        $progcompletion = new stdClass();
        $progcompletion->programid = $progid;
        $progcompletion->userid = $userid;
        $progcompletion->coursesetid = 0;
        $progcompletion->status = STATUS_PROGRAM_COMPLETE; // Inconsistent with history status.
        $progcompletion->timestarted = 567;
        $progcompletion->timedue = 678;
        $progcompletion->timecompleted = 789; // Inconsistent with history timecompleted.
        $progcompletion->organisationid = 890;
        $progcompletion->positionid = 901;
        $DB->insert_record('prog_completion', $progcompletion);

        // Run the function.
        certif_create_completion($progid, $userid);

        // Load the data.
        list($newcertcompletion, $newprogcompletion) = certif_load_completion($progid, $userid);
        $this->assertEmpty(certif_get_completion_errors($newcertcompletion, $newprogcompletion));

        // Set up the expected certif_completion.
        $expectedcertcompletion = $certcompletionhistory;
        unset($expectedcertcompletion->timemodified);
        unset($expectedcertcompletion->unassigned);
        unset($newcertcompletion->id);
        unset($newcertcompletion->timemodified);

        // Set up the expected prog_completion.
        $expectedprogcompletion = $progcompletion;
        $expectedprogcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $expectedprogcompletion->timecompleted = 0;
        unset($newprogcompletion->id);
        unset($newprogcompletion->timecreated);

        // Check that they match.
        $this->assertEquals($expectedcertcompletion, $newcertcompletion);
        $this->assertEquals($expectedprogcompletion, $newprogcompletion);

        ///////////////////////////////
        // Certified certif_completion.
        $certid = $cert2->certifid;
        $progid = $cert2->id;
        $userid = $user2->id;

        // Set up the history record.
        $certcompletionhistory = new stdClass();
        $certcompletionhistory->certifid = $certid;
        $certcompletionhistory->userid = $userid;
        $certcompletionhistory->certifpath = CERTIFPATH_RECERT;
        $certcompletionhistory->status = CERTIFSTATUS_COMPLETED;
        $certcompletionhistory->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletionhistory->timewindowopens = 345;
        $certcompletionhistory->timeexpires = 456;
        $certcompletionhistory->baselinetimeexpires = 456;
        $certcompletionhistory->timecompleted = 234;
        $certcompletionhistory->timemodified = 123;
        $certcompletionhistory->unassigned = 1;
        $this->assertTrue(certif_write_completion_history($certcompletionhistory));

        // Set up the prog_completion record, setting fields to values that are inconsistent with the history.
        $progcompletion = new stdClass();
        $progcompletion->programid = $progid;
        $progcompletion->userid = $userid;
        $progcompletion->coursesetid = 0;
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE; // Inconsistent with history status.
        $progcompletion->timestarted = 567;
        $progcompletion->timedue = 678; // Inconsistent with history timeexpires.
        $progcompletion->timecompleted = 789; // Inconsistent with history timecompleted.
        $progcompletion->organisationid = 890;
        $progcompletion->positionid = 901;
        $DB->insert_record('prog_completion', $progcompletion);

        // Run the function.
        certif_create_completion($progid, $userid);

        // Load the data.
        list($newcertcompletion, $newprogcompletion) = certif_load_completion($progid, $userid);
        $this->assertEmpty(certif_get_completion_errors($newcertcompletion, $newprogcompletion));

        // Set up the expected certif_completion.
        $expectedcertcompletion = $certcompletionhistory;
        unset($expectedcertcompletion->timemodified);
        unset($expectedcertcompletion->unassigned);
        unset($newcertcompletion->id);
        unset($newcertcompletion->timemodified);

        // Set up the expected prog_completion.
        $expectedprogcompletion = $progcompletion;
        $expectedprogcompletion->status = STATUS_PROGRAM_COMPLETE;
        $expectedprogcompletion->timedue = $expectedcertcompletion->timeexpires;
        $expectedprogcompletion->timecompleted = $expectedcertcompletion->timecompleted;
        unset($newprogcompletion->id);
        unset($newprogcompletion->timecreated);

        // Check that they match.
        $this->assertEquals($expectedcertcompletion, $newcertcompletion);
        $this->assertEquals($expectedprogcompletion, $newprogcompletion);

        /////////////////////////////////
        // Window open certif_completion.
        $certid = $cert3->certifid;
        $progid = $cert3->id;
        $userid = $user3->id;

        // Set up the history record.
        $certcompletionhistory = new stdClass();
        $certcompletionhistory->certifid = $certid;
        $certcompletionhistory->userid = $userid;
        $certcompletionhistory->certifpath = CERTIFPATH_RECERT;
        $certcompletionhistory->status = CERTIFSTATUS_INPROGRESS; // Just for fun.
        $certcompletionhistory->renewalstatus = CERTIFRENEWALSTATUS_DUE;
        $certcompletionhistory->timewindowopens = 345;
        $certcompletionhistory->timeexpires = 456;
        $certcompletionhistory->baselinetimeexpires = 456;
        $certcompletionhistory->timecompleted = 234;
        $certcompletionhistory->timemodified = 123;
        $certcompletionhistory->unassigned = 1;
        $this->assertTrue(certif_write_completion_history($certcompletionhistory));

        // Set up the prog_completion record, setting fields to values that are inconsistent with the history.
        $progcompletion = new stdClass();
        $progcompletion->programid = $progid;
        $progcompletion->userid = $userid;
        $progcompletion->coursesetid = 0;
        $progcompletion->status = STATUS_PROGRAM_COMPLETE; // Inconsistent with history status.
        $progcompletion->timestarted = 567;
        $progcompletion->timedue = 678; // Inconsistent with history timeexpires.
        $progcompletion->timecompleted = 789; // Inconsistent with history status.
        $progcompletion->organisationid = 890;
        $progcompletion->positionid = 901;
        $DB->insert_record('prog_completion', $progcompletion);

        // Run the function.
        certif_create_completion($progid, $userid);

        // Load the data.
        list($newcertcompletion, $newprogcompletion) = certif_load_completion($progid, $userid);
        $this->assertEmpty(certif_get_completion_errors($newcertcompletion, $newprogcompletion));

        // Set up the expected certif_completion.
        $expectedcertcompletion = $certcompletionhistory;
        unset($expectedcertcompletion->timemodified);
        unset($expectedcertcompletion->unassigned);
        unset($newcertcompletion->id);
        unset($newcertcompletion->timemodified);

        // Set up the expected prog_completion.
        $expectedprogcompletion = $progcompletion;
        $expectedprogcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $expectedprogcompletion->timedue = $expectedcertcompletion->timeexpires;
        $expectedprogcompletion->timecompleted = 0;
        unset($newprogcompletion->id);
        unset($newprogcompletion->timecreated);

        // Check that they match.
        $this->assertEquals($expectedcertcompletion, $newcertcompletion);
        $this->assertEquals($expectedprogcompletion, $newprogcompletion);

        ////////////////////////////////////////////////////////////////
        // Expired certif_completion and prog_completion has no timedue.
        $certid = $cert1->certifid;
        $progid = $cert1->id;
        $userid = $user2->id;

        // Set up the history record that will be used for program timedue, created when window opened.
        $certcompletionhistory = new stdClass();
        $certcompletionhistory->certifid = $certid;
        $certcompletionhistory->userid = $userid;
        $certcompletionhistory->certifpath = CERTIFPATH_RECERT;
        $certcompletionhistory->status = CERTIFSTATUS_COMPLETED;
        $certcompletionhistory->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletionhistory->timewindowopens = 345;
        $certcompletionhistory->timeexpires = 456;
        $certcompletionhistory->baselinetimeexpires = 456;
        $certcompletionhistory->timecompleted = 234;
        $certcompletionhistory->timemodified = 123;
        $certcompletionhistory->unassigned = 0;
        $this->assertTrue(certif_write_completion_history($certcompletionhistory));

        // Set up the history record that will be restored.
        $certcompletionhistory = new stdClass();
        $certcompletionhistory->certifid = $certid;
        $certcompletionhistory->userid = $userid;
        $certcompletionhistory->certifpath = CERTIFPATH_CERT;
        $certcompletionhistory->status = CERTIFSTATUS_EXPIRED;
        $certcompletionhistory->renewalstatus = CERTIFRENEWALSTATUS_EXPIRED;
        $certcompletionhistory->timewindowopens = 0;
        $certcompletionhistory->timeexpires = 0;
        $certcompletionhistory->baselinetimeexpires = 0;
        $certcompletionhistory->timecompleted = 0;
        $certcompletionhistory->timemodified = 123;
        $certcompletionhistory->unassigned = 1;
        certif_write_completion_history($certcompletionhistory);

        // Set up the prog_completion record, setting fields to values that are inconsistent with the history.
        $progcompletion = new stdClass();
        $progcompletion->programid = $progid;
        $progcompletion->userid = $userid;
        $progcompletion->coursesetid = 0;
        $progcompletion->status = STATUS_PROGRAM_COMPLETE; // Inconsistent with history status.
        $progcompletion->timestarted = 567;
        $progcompletion->timedue = 0; // Inconsistent with history status (must have value when expired).
        $progcompletion->timecompleted = 789; // Inconsistent with history status.
        $progcompletion->organisationid = 890;
        $progcompletion->positionid = 901;
        $DB->insert_record('prog_completion', $progcompletion);

        // Run the function.
        certif_create_completion($progid, $userid);

        // Load the data.
        list($newcertcompletion, $newprogcompletion) = certif_load_completion($progid, $userid);
        $this->assertEmpty(certif_get_completion_errors($newcertcompletion, $newprogcompletion));

        // Set up the expected certif_completion.
        $expectedcertcompletion = $certcompletionhistory;
        unset($expectedcertcompletion->timemodified);
        unset($expectedcertcompletion->unassigned);
        unset($newcertcompletion->id);
        unset($newcertcompletion->timemodified);

        // Set up the expected prog_completion.
        $expectedprogcompletion = $progcompletion;
        $expectedprogcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $expectedprogcompletion->timedue = 456; // Matches other (non-unassigned history) timeexpires.
        $expectedprogcompletion->timecompleted = 0;
        unset($newprogcompletion->id);
        unset($newprogcompletion->timecreated);

        // Check that they match.
        $this->assertEquals($expectedcertcompletion, $newcertcompletion);
        $this->assertEquals($expectedprogcompletion, $newprogcompletion);

        ///////////////////////////////////////////////////////////////
        // Expired certif_completion and prog_completion has a timedue.
        $certid = $cert2->certifid;
        $progid = $cert2->id;
        $userid = $user3->id;

        // Set up the history record that will be used for program timedue, created when window opened.
        $certcompletionhistory = new stdClass();
        $certcompletionhistory->certifid = $certid;
        $certcompletionhistory->userid = $userid;
        $certcompletionhistory->certifpath = CERTIFPATH_RECERT;
        $certcompletionhistory->status = CERTIFSTATUS_COMPLETED;
        $certcompletionhistory->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletionhistory->timewindowopens = 345;
        $certcompletionhistory->timeexpires = 456;
        $certcompletionhistory->baselinetimeexpires = 456;
        $certcompletionhistory->timecompleted = 234;
        $certcompletionhistory->timemodified = 123;
        $certcompletionhistory->unassigned = 0;
        $this->assertTrue(certif_write_completion_history($certcompletionhistory));

        // Set up the history record that will be restored.
        $certcompletionhistory = new stdClass();
        $certcompletionhistory->certifid = $certid;
        $certcompletionhistory->userid = $userid;
        $certcompletionhistory->certifpath = CERTIFPATH_CERT;
        $certcompletionhistory->status = CERTIFSTATUS_EXPIRED;
        $certcompletionhistory->renewalstatus = CERTIFRENEWALSTATUS_EXPIRED;
        $certcompletionhistory->timewindowopens = 0;
        $certcompletionhistory->timeexpires = 0;
        $certcompletionhistory->baselinetimeexpires = 0;
        $certcompletionhistory->timecompleted = 0;
        $certcompletionhistory->timemodified = 123;
        $certcompletionhistory->unassigned = 1;
        certif_write_completion_history($certcompletionhistory);

        // Set up the prog_completion record, setting fields to values that are inconsistent with the history.
        $progcompletion = new stdClass();
        $progcompletion->programid = $progid;
        $progcompletion->userid = $userid;
        $progcompletion->coursesetid = 0;
        $progcompletion->status = STATUS_PROGRAM_COMPLETE; // Inconsistent with history status.
        $progcompletion->timestarted = 567;
        $progcompletion->timedue = 678; // Should be used since history record has no timeexpires.
        $progcompletion->timecompleted = 789; // Inconsistent with history status.
        $progcompletion->organisationid = 890;
        $progcompletion->positionid = 901;
        $DB->insert_record('prog_completion', $progcompletion);

        // Run the function.
        certif_create_completion($progid, $userid);

        // Load the data.
        list($newcertcompletion, $newprogcompletion) = certif_load_completion($progid, $userid);
        $this->assertEmpty(certif_get_completion_errors($newcertcompletion, $newprogcompletion));

        // Set up the expected certif_completion.
        $expectedcertcompletion = $certcompletionhistory;
        unset($expectedcertcompletion->timemodified);
        unset($expectedcertcompletion->unassigned);
        unset($newcertcompletion->id);
        unset($newcertcompletion->timemodified);

        // Set up the expected prog_completion.
        $expectedprogcompletion = $progcompletion;
        $expectedprogcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $expectedprogcompletion->timecompleted = 0;
        unset($newprogcompletion->id);
        unset($newprogcompletion->timecreated);

        // Check that they match.
        $this->assertEquals($expectedcertcompletion, $newcertcompletion);
        $this->assertEquals($expectedprogcompletion, $newprogcompletion);
    }

    public function test_certif_load_all_completions() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        // Create some users.
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();

        // Create some certs.
        $cert1 = $generator->create_certification();
        $cert2 = $generator->create_certification();

        // Create some programs.
        $prog1 = $generator->create_program();
        $prog2 = $generator->create_program();

        // Add the users to the certs.
        $this->getDataGenerator()->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);
        $this->getDataGenerator()->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user2->id);
        $this->getDataGenerator()->assign_to_program($cert2->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);
        $this->getDataGenerator()->assign_to_program($cert2->id, ASSIGNTYPE_INDIVIDUAL, $user2->id);

        // Add the users to the programs.
        $this->getDataGenerator()->assign_to_program($prog1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);
        $this->getDataGenerator()->assign_to_program($prog1->id, ASSIGNTYPE_INDIVIDUAL, $user2->id);
        $this->getDataGenerator()->assign_to_program($prog2->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);
        $this->getDataGenerator()->assign_to_program($prog2->id, ASSIGNTYPE_INDIVIDUAL, $user2->id);

        // Run the function and check the correct records are returned.
        $results = certif_load_all_completions($user1->id);

        // Make sure we've got two records and they're not the same.
        $this->assertCount(2, $results);
        $this->assertNotEquals($results[0]['progcompletion']->id, $results[1]['progcompletion']->id);

        foreach ($results as $result) {
            $certcompletion = $result['certcompletion'];
            $progcompletion = $result['progcompletion'];

            // The record belongs to user1.
            $this->assertEquals($user1->id, $certcompletion->userid);
            $this->assertEquals($user1->id, $progcompletion->userid);

            // The record is not associated with either of the programs.
            $this->assertNotEquals($prog1->id, $progcompletion->programid);
            $this->assertNotEquals($prog2->id, $progcompletion->programid);

            // The cert and prog records are valid - the results should be identical to certif_load_completion, which has
            // already been tested above.
            list($exectedcertcompletion, $expectedprogcompletion) = certif_load_completion($progcompletion->programid, $user1->id);
            $this->assertEquals($exectedcertcompletion, $certcompletion);
            $this->assertEquals($expectedprogcompletion, $progcompletion);
        }
    }

    /**
     * Data provider for test_certif_conditionally_delete_completion.
     */
    public function data_certif_conditionally_delete_completion() {
        return array(
            array(
                array(),
                array(),
                true, false, false, false), // Assigned, newly assigned.
            array(
                array(),
                array(),
                false, true, true, false), // Not assigned, newly assigned, no history because no progress.
            array(
                array(
                    'status' => CERTIFSTATUS_COMPLETED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_NOTDUE,
                    'certifpath' => CERTIFPATH_RECERT,
                    'timecompleted' => 100,
                    'timewindowopens' => 200,
                    'timeexpires' => 300,
                    'baselinetimeexpires' => 300,
                ),
                array(
                    'status' => STATUS_PROGRAM_COMPLETE,
                    'timecompleted' => 100,
                    'timedue' => 300,
                ),
                true, false, false, false), // Assigned, certified.
            array(
                array(
                    'status' => CERTIFSTATUS_COMPLETED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_NOTDUE,
                    'certifpath' => CERTIFPATH_RECERT,
                    'timecompleted' => 100,
                    'timewindowopens' => 200,
                    'timeexpires' => 300,
                    'baselinetimeexpires' => 300,
                ),
                array(
                    'status' => STATUS_PROGRAM_COMPLETE,
                    'timecompleted' => 100,
                    'timedue' => 300,
                ),
                false, true, false, true), // Not assigned, certified.
            array(
                array(
                    'status' => CERTIFSTATUS_COMPLETED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_DUE,
                    'certifpath' => CERTIFPATH_RECERT,
                    'timecompleted' => 100,
                    'timewindowopens' => 200,
                    'timeexpires' => 300,
                    'baselinetimeexpires' => 300,
                ),
                array(
                    'status' => STATUS_PROGRAM_INCOMPLETE,
                    'timedue' => 300,
                ),
                true, false, false, false), // Assigned, window open.
            array(
                array(
                    'status' => CERTIFSTATUS_COMPLETED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_DUE,
                    'certifpath' => CERTIFPATH_RECERT,
                    'timecompleted' => 100,
                    'timewindowopens' => 200,
                    'timeexpires' => 300,
                    'baselinetimeexpires' => 300,
                ),
                array(
                    'status' => STATUS_PROGRAM_INCOMPLETE,
                    'timedue' => 300,
                ),
                false, true, false, true), // Not assigned, window open.
            array(
                array(
                    'status' => CERTIFSTATUS_EXPIRED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_EXPIRED,
                    'certifpath' => CERTIFPATH_CERT,
                ),
                array(
                    'status' => STATUS_PROGRAM_INCOMPLETE,
                    'timedue' => 300,
                ),
                true, false, false, false), // Assigned, expired.
            array(
                array(
                    'status' => CERTIFSTATUS_EXPIRED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_EXPIRED,
                    'certifpath' => CERTIFPATH_CERT,
                ),
                array(
                    'status' => STATUS_PROGRAM_INCOMPLETE,
                    'timedue' => 300,
                ),
                false, true, false, true), // Not assigned, expired.
        );
    }

    /**
     * Test certif_conditionally_delete_completion.
     *
     * @dataProvider data_certif_conditionally_delete_completion
     */
    public function test_certif_conditionally_delete_completion($certcompletionchanges, $progcompletionchanges, $isassigned,
                                                         $certshouldbedeleted, $progshouldbedeleted, $shouldhavehistory) {
        global $DB;

        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        // Create some users.
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();

        // Create some certs.
        $cert1 = $generator->create_certification();
        $cert2 = $generator->create_certification();

        // Create some programs.
        $prog1 = $generator->create_program();
        $prog2 = $generator->create_program();

        // Add the users to the certs.
        $generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);
        $generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user2->id);
        $generator->assign_to_program($cert2->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);
        $generator->assign_to_program($cert2->id, ASSIGNTYPE_INDIVIDUAL, $user2->id);

        // Add the users to the programs.
        $generator->assign_to_program($prog1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);
        $generator->assign_to_program($prog1->id, ASSIGNTYPE_INDIVIDUAL, $user2->id);
        $generator->assign_to_program($prog2->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);
        $generator->assign_to_program($prog2->id, ASSIGNTYPE_INDIVIDUAL, $user2->id);

        // Hack the removal of the assignment records. We can't unassign the user because that would remove the completion records!
        if (!$isassigned) {
            $DB->delete_records('prog_user_assignment');
        }

        // Update the state of the records.
        $completions = array_merge(certif_load_all_completions($user1->id), certif_load_all_completions($user2->id));
        foreach ($completions as $completion) {
            $certcompletion = $completion['certcompletion'];
            $progcompletion = $completion['progcompletion'];
            foreach ($certcompletionchanges as $key => $value) {
                $certcompletion->$key = $value;
            }
            foreach ($progcompletionchanges as $key => $value) {
                $progcompletion->$key = $value;
            }
            $this->assertEquals(array(), certif_get_completion_errors($certcompletion, $progcompletion));
            $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        }

        // Load the current set of data.
        $expectedcertcompletions = $DB->get_records('certif_completion');
        $expectedprogcompletions = $DB->get_records('prog_completion');
        list($expectedcertcompletionhistory, $progcompletion) = certif_load_completion($cert1->id, $user1->id);

        // Conditionally delete just one cert completion.
        certif_conditionally_delete_completion($cert1->id, $user1->id);

        // Manually make the same change to the expected data.
        if ($certshouldbedeleted) {
            foreach ($expectedcertcompletions as $key => $certcompletion) {
                if ($certcompletion->certifid == $cert1->certifid && $certcompletion->userid == $user1->id) {
                    unset($expectedcertcompletions[$key]);
                }
            }
        }
        if ($progshouldbedeleted) {
            foreach ($expectedprogcompletions as $key => $progcompletion) {
                if ($progcompletion->programid == $cert1->id && $progcompletion->userid == $user1->id) {
                    unset($expectedprogcompletions[$key]);
                }
            }
        }
        if ($shouldhavehistory) {
            unset($expectedcertcompletionhistory->id);
            unset($expectedcertcompletionhistory->timemodified);
            $expectedcertcompletionhistory->unassigned = 1;
        }

        // Then just compare the current data with the expected.
        $actualcertcompletions = $DB->get_records('certif_completion');
        $actualprogcompletions = $DB->get_records('prog_completion');
        $this->assertEquals($expectedcertcompletions, $actualcertcompletions);
        $this->assertEquals($expectedprogcompletions, $actualprogcompletions);

        // Make sure that the history record has been created when appropriate.
        $certcomplhistories = $DB->get_records('certif_completion_history');
        if ($shouldhavehistory) {
            $this->assertCount(1, $certcomplhistories);
            $certcompletionhistory = reset($certcomplhistories);
            unset($certcompletionhistory->id);
            unset($certcompletionhistory->timemodified);
            $this->assertEquals($expectedcertcompletionhistory, $certcompletionhistory);
        } else {
            $this->assertCount(0, $certcomplhistories);
        }
    }

    public function test_certif_conditionally_delete_completion_with_missing_records() {
        global $DB;

        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();

        // Create some users.
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();

        // Create a cert.
        $cert1 = $generator->create_certification();

        // We've already tested what happens when both records exist, so just try with missing cert or prog records.

        // Check that the cert record is still deleted if the prog record doesn't exist.
        $generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);
        $this->assertCount(1, $DB->get_records('certif_completion'));
        $this->assertCount(1, $DB->get_records('prog_completion'));
        $DB->delete_records('prog_completion', array('programid' => $cert1->id, 'userid' => $user1->id));
        $DB->delete_records('prog_user_assignment'); // To make sure the records aren't kept because the user is still assigned.
        $DB->delete_records('prog_assignment'); // To make sure the records aren't kept because the user is still assigned.
        certif_conditionally_delete_completion($cert1->id, $user1->id);
        $this->assertCount(0, $DB->get_records('certif_completion'));

        // Check that the prog record is still deleted if the cert record doesn't exist.
        $generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user2->id);
        $this->assertCount(1, $DB->get_records('certif_completion'));
        $this->assertCount(1, $DB->get_records('prog_completion'));
        $DB->delete_records('certif_completion', array('certifid' => $cert1->certifid, 'userid' => $user2->id));
        $DB->delete_records('prog_user_assignment'); // To make sure the records aren't kept because the user is still assigned.
        $DB->delete_records('prog_assignment'); // To make sure the records aren't kept because the user is still assigned.
        certif_conditionally_delete_completion($cert1->id, $user2->id);
        $this->assertCount(0, $DB->get_records('prog_completion'));
    }

    public function test_certif_delete_completion() {
        global $DB;

        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        // Create some users.
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();

        // Create some certs.
        $cert1 = $generator->create_certification();
        $cert2 = $generator->create_certification();

        // Create some programs.
        $prog1 = $generator->create_program();
        $prog2 = $generator->create_program();

        // Add the users to the certs.
        $generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);
        $generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user2->id);
        $generator->assign_to_program($cert2->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);
        $generator->assign_to_program($cert2->id, ASSIGNTYPE_INDIVIDUAL, $user2->id);

        // Add the users to the programs.
        $generator->assign_to_program($prog1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);
        $generator->assign_to_program($prog1->id, ASSIGNTYPE_INDIVIDUAL, $user2->id);
        $generator->assign_to_program($prog2->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);
        $generator->assign_to_program($prog2->id, ASSIGNTYPE_INDIVIDUAL, $user2->id);

        // Load the current set of data.
        $expectedcertcompletions = $DB->get_records('certif_completion');
        $expectedprogcompletions = $DB->get_records('prog_completion');

        // Delete just one cert completion.
        certif_delete_completion($cert1->id, $user1->id);

        // Manually make the same change to the expected data. Only certif_completion is affected!!!
        foreach ($expectedcertcompletions as $key => $certcompletion) {
            if ($certcompletion->certifid == $cert1->certifid && $certcompletion->userid == $user1->id) {
                unset($expectedcertcompletions[$key]);
            }
        }

        // Then just compare the current data with the expected.
        $actualcertcompletions = $DB->get_records('certif_completion');
        $actualprogcompletions = $DB->get_records('prog_completion');
        $this->assertEquals($expectedcertcompletions, $actualcertcompletions);
        $this->assertEquals($expectedprogcompletions, $actualprogcompletions);

        // Make sure that it still deletes the record if the user is certified.
        list($certcompletion, $progcompletion) = certif_load_completion($cert2->id, $user2->id);
        $certcompletion->status = CERTIFSTATUS_COMPLETED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletion->certifpath = CERTIFPATH_RECERT;
        $certcompletion->timecompleted = 100;
        $certcompletion->timewindowopens = 200;
        $certcompletion->timeexpires = 300;
        $certcompletion->baselinetimeexpires = 300;
        $progcompletion->status = STATUS_PROGRAM_COMPLETE;
        $progcompletion->timecompleted = 100;
        $progcompletion->timedue = 300;
        $this->assertEquals(array(), certif_get_completion_errors($certcompletion, $progcompletion));
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));

        // Load the current set of data.
        $expectedcertcompletions = $DB->get_records('certif_completion');
        $expectedprogcompletions = $DB->get_records('prog_completion');

        // Delete just one cert completion.
        certif_delete_completion($cert2->id, $user2->id);

        // Manually make the same change to the expected data. Only certif_completion is affected!!!
        foreach ($expectedcertcompletions as $key => $certcompletion) {
            if ($certcompletion->certifid == $cert2->certifid && $certcompletion->userid == $user2->id) {
                unset($expectedcertcompletions[$key]);
            }
        }

        // Then just compare the current data with the expected.
        $actualcertcompletions = $DB->get_records('certif_completion');
        $actualprogcompletions = $DB->get_records('prog_completion');
        $this->assertEquals($expectedcertcompletions, $actualcertcompletions);
        $this->assertEquals($expectedprogcompletions, $actualprogcompletions);
    }

    /**
     * Set up users, programs, certifications and assignments.
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

        // Assign users to the programs as individuals.
        foreach ($this->users as $user) {
            foreach ($this->programs as $prog) {
                $this->getDataGenerator()->assign_to_program($prog->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
            }
        }

        // Assign users to the certifications as individuals.
        foreach ($this->users as $user) {
            foreach ($this->certifications as $prog) {
                $this->getDataGenerator()->assign_to_program($prog->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
            }
        }
    }

    /**
     * Test certif_fix_missing_completions - ensure that the correct records are repaired when prog_completions are missing.
     */
    public function test_certif_fix_missing_completions_with_prog_completions() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Break all the records, progs and certs.
        $DB->delete_records('prog_completion', array('coursesetid' => 0));

        // Check that all of the records are broken, progs and certs.
        $expectedfixedcount = 0;
        $progcompletions = $DB->get_records('prog_completion', array('coursesetid' => 0));
        $this->assertCount($expectedfixedcount, $progcompletions);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs, $fulllist);

        // Apply the fix to just one user/cert.
        certif_fix_missing_completions($this->certifications[6]->id, $this->users[2]->id);

        // Check that the correct records have been fixed.
        $expectedfixedcount = 1; // One cell in the matrix.
        $progcompletions = $DB->get_records('prog_completion', array('coursesetid' => 0));
        $this->assertCount($expectedfixedcount, $progcompletions);
        foreach ($progcompletions as $progcompletion) {
            $this->assertEquals($this->certifications[6]->id, $progcompletion->programid);
            $this->assertEquals($this->users[2]->id, $progcompletion->userid);
        }
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Apply the fix to just one user, all certs (don't need to reset, just overlap).
        certif_fix_missing_completions(0, $this->users[2]->id);

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestcerts; // One column in the matrix.
        $progcompletions = $DB->get_records('prog_completion', array('coursesetid' => 0));
        $this->assertCount($expectedfixedcount, $progcompletions);
        foreach ($progcompletions as $progcompletion) {
            $this->assertEquals($this->users[2]->id, $progcompletion->userid);
        }
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Apply the fix to just one cert, all users (don't need to reset, just overlap).
        certif_fix_missing_completions($this->certifications[6]->id, 0);

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestcerts + $this->numtestusers - 1; // One column and one row in the matrix.
        $progcompletions = $DB->get_records('prog_completion', array('coursesetid' => 0));
        $this->assertCount($expectedfixedcount, $progcompletions);
        foreach ($progcompletions as $progcompletion) {
            $this->assertTrue($this->certifications[6]->id == $progcompletion->programid ||
                $this->users[2]->id == $progcompletion->userid);
        }
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Apply the fix to all records (overlaps previous fixes).
        certif_fix_missing_completions(0, 0);

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestcerts * $this->numtestusers; // The whole matrix.
        $progcompletions = $DB->get_records('prog_completion', array('coursesetid' => 0));
        $this->assertCount($expectedfixedcount, $progcompletions);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Make sure that no progs were fixed.
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs, $fulllist);
    }

    /**
     * Test certif_fix_missing_completions - ensure that the correct records are repaired when certif_completions are missing.
     */
    public function test_certif_fix_missing_completions_with_certif_completions() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Break all the records, just certs (progs don't have certif_completion records!).
        $DB->delete_records('certif_completion');

        // Check that all of the records are broken, just certs.
        $expectedfixedcount = 0;
        $certcompletions = $DB->get_records('certif_completion');
        $this->assertCount($expectedfixedcount, $certcompletions);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Apply the fix to just one user/cert.
        certif_fix_missing_completions($this->certifications[6]->id, $this->users[2]->id);

        // Check that the correct records have been fixed.
        $expectedfixedcount = 1; // One cell in the matrix.
        $certcompletions = $DB->get_records('certif_completion');
        $this->assertCount($expectedfixedcount, $certcompletions);
        foreach ($certcompletions as $certcompletion) {
            $this->assertEquals($this->certifications[6]->certifid, $certcompletion->certifid);
            $this->assertEquals($this->users[2]->id, $certcompletion->userid);
        }
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Apply the fix to just one user, all certs (don't need to reset, just overlap).
        certif_fix_missing_completions(0, $this->users[2]->id);

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestcerts; // One column in the matrix.
        $certcompletions = $DB->get_records('certif_completion');
        $this->assertCount($expectedfixedcount, $certcompletions);
        foreach ($certcompletions as $certcompletion) {
            $this->assertEquals($this->users[2]->id, $certcompletion->userid);
        }
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Apply the fix to just one cert, all users (don't need to reset, just overlap).
        certif_fix_missing_completions($this->certifications[6]->id, 0);

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestcerts + $this->numtestusers - 1; // One column and one row in the matrix.
        $certcompletions = $DB->get_records('certif_completion');
        $this->assertCount($expectedfixedcount, $certcompletions);
        foreach ($certcompletions as $certcompletion) {
            $this->assertTrue($this->certifications[6]->certifid == $certcompletion->certifid ||
                $this->users[2]->id == $certcompletion->userid);
        }
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Apply the fix to all records (overlaps previous fixes).
        certif_fix_missing_completions(0, 0);

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestcerts * $this->numtestusers; // The whole matrix.
        $certcompletions = $DB->get_records('certif_completion');
        $this->assertCount($expectedfixedcount, $certcompletions);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Make sure that no progs were unfixed.
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);
    }

    /**
     * Test certif_fix_missing_completions - ensure that the correct records are repaired when both records are missing.
     */
    public function test_certif_fix_missing_completions_with_both_completions() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Break all the records, progs and certs.
        $DB->delete_records('prog_completion');
        $DB->delete_records('certif_completion');

        // Check that all of the records are broken, progs and certs.
        $expectedfixedcount = 0;
        $certcompletions = $DB->get_records('certif_completion');
        $this->assertCount($expectedfixedcount, $certcompletions);
        $progcompletions = $DB->get_records('prog_completion');
        $this->assertCount($expectedfixedcount, $progcompletions);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs, $fulllist);

        // Apply the fix to just one user/cert.
        certif_fix_missing_completions($this->certifications[6]->id, $this->users[2]->id);

        // Check that the correct records have been fixed.
        $expectedfixedcount = 1; // One cell in the matrix.
        $certcompletions = $DB->get_records('certif_completion');
        $this->assertCount($expectedfixedcount, $certcompletions);
        $progcompletions = $DB->get_records('prog_completion');
        $this->assertCount($expectedfixedcount, $progcompletions);
        foreach ($certcompletions as $certcompletion) {
            $this->assertEquals($this->certifications[6]->certifid, $certcompletion->certifid);
            $this->assertEquals($this->users[2]->id, $certcompletion->userid);
        }
        foreach ($progcompletions as $progcompletion) {
            $this->assertEquals($this->certifications[6]->id, $progcompletion->programid);
            $this->assertEquals($this->users[2]->id, $progcompletion->userid);
        }
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Apply the fix to just one user, all certs (don't need to reset, just overlap).
        certif_fix_missing_completions(0, $this->users[2]->id);

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestcerts; // One column in the matrix.
        $certcompletions = $DB->get_records('certif_completion');
        $this->assertCount($expectedfixedcount, $certcompletions);
        $progcompletions = $DB->get_records('prog_completion');
        $this->assertCount($expectedfixedcount, $progcompletions);
        foreach ($certcompletions as $certcompletion) {
            $this->assertEquals($this->users[2]->id, $certcompletion->userid);
        }
        foreach ($progcompletions as $progcompletion) {
            $this->assertEquals($this->users[2]->id, $progcompletion->userid);
        }
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Apply the fix to just one cert, all users (don't need to reset, just overlap).
        certif_fix_missing_completions($this->certifications[6]->id, 0);

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestcerts + $this->numtestusers - 1; // One column and one row in the matrix.
        $certcompletions = $DB->get_records('certif_completion');
        $this->assertCount($expectedfixedcount, $certcompletions);
        $progcompletions = $DB->get_records('prog_completion');
        $this->assertCount($expectedfixedcount, $progcompletions);
        foreach ($certcompletions as $certcompletion) {
            $this->assertTrue($this->certifications[6]->certifid == $certcompletion->certifid ||
                $this->users[2]->id == $certcompletion->userid);
        }
        foreach ($progcompletions as $progcompletion) {
            $this->assertTrue($this->certifications[6]->id == $progcompletion->programid ||
                $this->users[2]->id == $progcompletion->userid);
        }
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Apply the fix to all records (overlaps previous fixes).
        certif_fix_missing_completions(0, 0);

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestcerts * $this->numtestusers; // The whole matrix.
        $certcompletions = $DB->get_records('certif_completion');
        $this->assertCount($expectedfixedcount, $certcompletions);
        $progcompletions = $DB->get_records('prog_completion');
        $this->assertCount($expectedfixedcount, $progcompletions);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Make sure that no progs were fixed.
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs, $fulllist);
    }

    public function test_certif_fix_unassigned_certif_completions() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Break all the records, progs and certs.
        $DB->delete_records('prog_user_assignment');

        // Check that all of the records are broken, progs and certs.
        $expectedfixedcount = 0;
        $certcompletions = $DB->get_records('certif_completion');
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $certcompletions);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs, $fulllist);

        // Apply the fix to just one user/cert.
        certif_fix_unassigned_certif_completions($this->certifications[6]->id, $this->users[2]->id);

        // Check that the correct records have been fixed.
        $expectedfixedcount = 1; // One cell in the matrix.
        $certcompletions = $DB->get_records('certif_completion');
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $certcompletions);
        foreach ($certcompletions as $certcompletion) {
            $this->assertTrue($this->certifications[6]->certifid != $certcompletion->certifid ||
                $this->users[2]->id != $certcompletion->userid);
        }
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Apply the fix to just one user, all certs (don't need to reset, just overlap).
        certif_fix_unassigned_certif_completions(0, $this->users[2]->id);

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestcerts; // One column in the matrix.
        $certcompletions = $DB->get_records('certif_completion');
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $certcompletions);
        foreach ($certcompletions as $certcompletion) {
            $this->assertTrue($this->users[2]->id != $certcompletion->userid);
        }
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Apply the fix to just one cert, all users (don't need to reset, just overlap).
        certif_fix_unassigned_certif_completions($this->certifications[6]->id, 0);

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestcerts + $this->numtestusers - 1; // One column and one row in the matrix.
        $certcompletions = $DB->get_records('certif_completion');
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $certcompletions);
        foreach ($certcompletions as $certcompletion) {
            $this->assertTrue($this->certifications[6]->certifid != $certcompletion->certifid &&
                $this->users[2]->id != $certcompletion->userid);
        }
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Apply the fix to all records (overlaps previous fixes).
        certif_fix_unassigned_certif_completions(0, 0);

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestcerts * $this->numtestusers; // The whole matrix.
        $certcompletions = $DB->get_records('certif_completion');
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $certcompletions);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Make sure that no progs were fixed.
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs, $fulllist);
    }

    public function test_certif_find_missing_completions() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();
        $this->assert_count_and_close_recordset(0, certif_find_missing_completions());
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Break all the records, progs and certs.
        $DB->delete_records('prog_completion', array('coursesetid' => 0));

        // Check that all of the records are broken, progs and certs.
        $this->assert_count_and_close_recordset($this->numtestusers * $this->numtestcerts, certif_find_missing_completions());
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs, $fulllist);

        // Apply the fix to just one user, all certs.
        certif_fix_missing_completions(0, $this->users[2]->id);

        // Apply the fix to just one cert, all users (overlapping).
        certif_fix_missing_completions($this->certifications[6]->id, 0);

        // Test the function is returning the correct records when no user or certification is specified.
        $expectedfixedcount = $this->numtestusers * $this->numtestcerts - $this->numtestcerts - $this->numtestusers + 1;
        $this->assert_count_and_close_recordset($expectedfixedcount, certif_find_missing_completions());

        // Test the function is returning the correct records when just the certification is specified.
        $this->assert_count_and_close_recordset($this->numtestusers - 1, certif_find_missing_completions($this->certifications[4]->id, 0));
        $this->assert_count_and_close_recordset(0, certif_find_missing_completions($this->certifications[6]->id, 0)); // All were fixed.

        // Test the function is returning the correct records when just the user is specified.
        $this->assert_count_and_close_recordset($this->numtestcerts - 1, certif_find_missing_completions(0, $this->users[7]->id));
        $this->assert_count_and_close_recordset(0, certif_find_missing_completions(0, $this->users[2]->id)); // All were fixed.

        // Test the function is returning the correct records when certification and user are specified.
        $this->assert_count_and_close_recordset(1, certif_find_missing_completions($this->certifications[4]->id, $this->users[6]->id));
        $this->assert_count_and_close_recordset(0, certif_find_missing_completions($this->certifications[6]->id, $this->users[3]->id));
        $this->assert_count_and_close_recordset(0, certif_find_missing_completions($this->certifications[5]->id, $this->users[2]->id));
        $this->assert_count_and_close_recordset(0, certif_find_missing_completions($this->certifications[6]->id, $this->users[2]->id));
    }

    public function test_certif_find_unassigned_certif_completions() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();

        // Show that existing prog_user_assignments will prevent the certif_completions from being reported as broken.
        $this->assert_count_and_close_recordset(0, certif_find_unassigned_certif_completions());
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Break all the records, progs and certs.
        $DB->delete_records('prog_user_assignment');

        // Check that all of the records are broken, progs and certs.
        $this->assert_count_and_close_recordset($this->numtestusers * $this->numtestcerts, certif_find_unassigned_certif_completions());
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs, $fulllist);

        // Apply the fix to just one user, all progs.
        certif_fix_unassigned_certif_completions(0, $this->users[2]->id);

        // Apply the fix to just one cert, all users (overlapping).
        certif_fix_unassigned_certif_completions($this->certifications[6]->id, 0);

        // Test the function is returning the correct records when no user or certification is specified.
        $expectedfixedcount = $this->numtestusers * $this->numtestcerts - $this->numtestcerts - $this->numtestusers + 1;
        $this->assert_count_and_close_recordset($expectedfixedcount, certif_find_unassigned_certif_completions());

        // Test the function is returning the correct records when just the certification is specified.
        $this->assert_count_and_close_recordset($this->numtestusers - 1, certif_find_unassigned_certif_completions($this->certifications[4]->id, 0));
        $this->assert_count_and_close_recordset(0, certif_find_unassigned_certif_completions($this->certifications[6]->id, 0)); // All were fixed.

        // Test the function is returning the correct records when just the user is specified.
        $this->assert_count_and_close_recordset($this->numtestcerts - 1, certif_find_unassigned_certif_completions(0, $this->users[7]->id));
        $this->assert_count_and_close_recordset(0, certif_find_unassigned_certif_completions(0, $this->users[2]->id)); // All were fixed.

        // Test the function is returning the correct records when certification and user are specified.
        $this->assert_count_and_close_recordset(1, certif_find_unassigned_certif_completions($this->certifications[4]->id, $this->users[6]->id));
        $this->assert_count_and_close_recordset(0, certif_find_unassigned_certif_completions($this->certifications[6]->id, $this->users[3]->id));
        $this->assert_count_and_close_recordset(0, certif_find_unassigned_certif_completions($this->certifications[5]->id, $this->users[2]->id));
        $this->assert_count_and_close_recordset(0, certif_find_unassigned_certif_completions($this->certifications[6]->id, $this->users[2]->id));
    }

    public function test_certif_get_all_completions_with_errors() {
        global $DB;

        $this->setup_completions();

        // One consistency problem.
        list($certcompletion, $progcompletion) = certif_load_completion($this->certifications[1]->id, $this->users[2]->id);
        $progcompletion->status = STATUS_PROGRAM_COMPLETE;
        $errors = certif_get_completion_errors($certcompletion, $progcompletion);
        $this->assertNotEmpty($errors);
        $problemkey = certif_get_completion_error_problemkey($errors);
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion, '', $problemkey));

        // One missing prog_completion.
        $DB->delete_records('prog_completion', array('programid' => $this->certifications[3]->id, 'userid' => $this->users[4]->id));

        // One unassigned certif completion record.
        $DB->delete_records('prog_user_assignment', array('programid' => $this->certifications[5]->id, 'userid' => $this->users[6]->id));

        // Run the function.
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();

        // Check that the results contain the problems.
        $this->assertCount(3, $fulllist);
        $consistencyitem = $fulllist[$this->certifications[1]->id . '-' . $this->users[2]->id];
        $missingitem = $fulllist[$this->certifications[3]->id . '-' . $this->users[4]->id];
        $unassigneditem = $fulllist[$this->certifications[5]->id . '-' . $this->users[6]->id];
        $this->assertEquals('Program status should be \'Program incomplete\' when user is newly assigned.', $consistencyitem->problem);
        $this->assertEquals('Completion records missing', $missingitem->problem);
        $this->assertEquals('Completion exists for unassigned user', $unassigneditem->problem);

        $this->assertCount(3, $aggregatelist);
        $consistencyaggregate = $aggregatelist['error:stateassigned-progstatusincorrect'];
        $missingaggregate = $aggregatelist['error:missingcompletion'];
        $unassignedaggregate = $aggregatelist['error:unassignedcertifcompletion'];
        $this->assertEquals(1, $consistencyaggregate->count);
        $this->assertEquals(1, $missingaggregate->count);
        $this->assertEquals(1, $unassignedaggregate->count);
        $this->assertEquals('Consistency', $consistencyaggregate->category);
        $this->assertEquals('Files', $missingaggregate->category);
        $this->assertEquals('Files', $unassignedaggregate->category);
        $this->assertTrue(isset($consistencyaggregate->problem));
        $this->assertTrue(isset($missingaggregate->problem));
        $this->assertTrue(isset($unassignedaggregate->problem));
        $this->assertTrue(isset($consistencyaggregate->solution));
        $this->assertTrue(isset($missingaggregate->solution));
        $this->assertTrue(isset($unassignedaggregate->solution));

        $this->assertEquals($this->numtestusers * $this->numtestcerts, $totalcount); // Excludes progs.
    }

    /**
     * Creates a certif_completion record for the user and certification we are testing on,
     * as well as for a different user and another again for a different certification.
     */
    public function create_certif_completion() {
        global $DB;

        /* @var totara_program_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $certification1 = new program($programgenerator->create_certification());
        $certification2 = new program($programgenerator->create_certification());

        // We'll clone this object and vary it to get a variation of history records.
        $originalcompletion = new stdClass();
        $originalcompletion->certifid = $certification1->certifid;
        $originalcompletion->userid = $this->getDataGenerator()->create_user()->id;
        $originalcompletion->certifpath = CERTIFPATH_RECERT;
        $originalcompletion->status = CERTIFSTATUS_COMPLETED;
        $originalcompletion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $originalcompletion->timewindowopens = 1000;
        $originalcompletion->timeexpires = 1500;
        $originalcompletion->baselinetimeexpires = 1500;
        $originalcompletion->timecompleted = 500;
        $originalcompletion->timemodified = time();
        $originalcompletion->id = $DB->insert_record('certif_completion', $originalcompletion);

        $cert2_user1 = clone $originalcompletion;
        unset($cert2_user1->id);
        $cert2_user1->certifid = $certification2->certifid;
        $DB->insert_record('certif_completion', $cert2_user1);

        $cert1_user2 = clone $originalcompletion;
        unset($cert1_user2->id);
        $cert1_user2->userid = $this->getDataGenerator()->create_user()->id;
        $DB->insert_record('certif_completion', $cert1_user2);

        copy_certif_completion_to_hist($originalcompletion->certifid, $originalcompletion->userid);

        return $originalcompletion;
    }

    /**
     * Creates a certif_completion record for the user and certification we are testing on,
     * as well as for a different user and another again for a different certification.
     *
     * Checks we only copy the certif_completion record that we specified the user and certification for.
     */
    public function test_copy_certif_completion_to_hist_selects_correct_user_cert() {
        global $DB;

        $originalcompletion = $this->create_certif_completion();

        $this->assertEquals(1, $DB->count_records('certif_completion_history'));
        $this->assertTrue($DB->record_exists('certif_completion_history',
            array('certifid' => $originalcompletion->certifid, 'userid' => $originalcompletion->userid)));

        // The certif_completion record that gets copied is not deleted by copy_certif_completion_to_hist().
        $this->assertTrue($DB->record_exists('certif_completion',
            array('certifid' => $originalcompletion->certifid, 'userid' => $originalcompletion->userid)));
    }

    /**
     * Try copying the same certif_completion record to history as we did in the last test.
     *
     * The history record should NOT be duplicated.
     */
    public function test_copy_certif_completion_to_hist_no_change_with_duplicate() {
        global $DB;

        $originalcompletion = $this->create_certif_completion();

        copy_certif_completion_to_hist($originalcompletion->certifid, $originalcompletion->userid);
        $this->assertEquals(1, $DB->count_records('certif_completion_history'));
    }

    /**
     * Change the timewindowopens value in the certif_completion record.
     *
     * This should not create a new record but simply update the existing history record.
     */
    public function test_copy_certif_completion_to_hist_only_windowopen_is_different() {
        global $DB;

        $originalcompletion = $this->create_certif_completion();

        $differentwindowopen = $originalcompletion->timewindowopens + 100;

        $DB->set_field('certif_completion', 'timewindowopens', $differentwindowopen,
            array('certifid' => $originalcompletion->certifid, 'userid' => $originalcompletion->userid));

        copy_certif_completion_to_hist($originalcompletion->certifid, $originalcompletion->userid);

        // There should still be 1 record which was updated with the new timewindowopens.
        $this->assertEquals(1, $DB->count_records('certif_completion_history'));
        $this->assertTrue($DB->record_exists('certif_completion_history',
            array('timewindowopens' => $differentwindowopen)));
    }

    /**
     * Change the timecompleted value in the certif_completion record.
     *
     * Copying this creates a new history record and leaves the other as is.
     */
    public function test_copy_certif_completion_to_hist_only_timecompleted_is_different() {
        global $DB;

        $originalcompletion = $this->create_certif_completion();

        $differenttimecompleted = $originalcompletion->timecompleted + 100;

        $DB->set_field('certif_completion', 'timecompleted', $differenttimecompleted,
            array('certifid' => $originalcompletion->certifid, 'userid' => $originalcompletion->userid));

        copy_certif_completion_to_hist($originalcompletion->certifid, $originalcompletion->userid);

        // There should be a new record which contains the new timecompleted. The old record should still have it's
        // original timecompleted.
        $this->assertEquals(2, $DB->count_records('certif_completion_history'));
        $this->assertTrue($DB->record_exists('certif_completion_history',
            array('timecompleted' => $originalcompletion->timecompleted)));
        $this->assertTrue($DB->record_exists('certif_completion_history',
            array('timecompleted' => $differenttimecompleted)));

        return $differenttimecompleted;
    }

    /**
     * Now change the timeexpires as well.
     *
     * The 2 existing history records should remain unchanged. A third is created to contain the different expiry date.
     */
    public function test_copy_certif_completion_to_hist_only_timeexpires_is_different() {
        global $DB;

        $originalcompletion = $this->create_certif_completion();

        $differenttimecompleted = $originalcompletion->timecompleted + 100;

        $DB->set_field('certif_completion', 'timecompleted', $differenttimecompleted,
            array('certifid' => $originalcompletion->certifid, 'userid' => $originalcompletion->userid));

        copy_certif_completion_to_hist($originalcompletion->certifid, $originalcompletion->userid);

        $differenttimeexpires = $originalcompletion->timeexpires + 100;

        $DB->set_field('certif_completion', 'timeexpires', $differenttimeexpires,
            array('certifid' => $originalcompletion->certifid, 'userid' => $originalcompletion->userid));

        copy_certif_completion_to_hist($originalcompletion->certifid, $originalcompletion->userid);

        // There should be a new record which contains the new timeexpires.
        // The first 2 records should contain their original values.
        $this->assertEquals(3, $DB->count_records('certif_completion_history'));
        $this->assertTrue($DB->record_exists('certif_completion_history',
            array('timecompleted' => $originalcompletion->timecompleted, 'timeexpires' => $originalcompletion->timeexpires)));
        $this->assertTrue($DB->record_exists('certif_completion_history',
            array('timecompleted' => $differenttimecompleted, 'timeexpires' => $originalcompletion->timeexpires)));
        $this->assertTrue($DB->record_exists('certif_completion_history',
            array('timecompleted' => $differenttimecompleted, 'timeexpires' => $differenttimeexpires)));
    }

    /**
     * With no existing completion data, write a valid history record.
     *
     */
    public function test_certif_write_completion_history_creates_history_record() {
        global $DB;

        /* @var totara_program_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $certification1 = new program($programgenerator->create_certification());

        $originalhistory = new stdClass();
        $originalhistory->certifid = $certification1->certifid;
        $originalhistory->userid = $this->getDataGenerator()->create_user()->id;
        $originalhistory->certifpath = CERTIFPATH_RECERT;
        $originalhistory->status = CERTIFSTATUS_COMPLETED;
        $originalhistory->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $originalhistory->timewindowopens = 1000;
        $originalhistory->timeexpires = 1500;
        $originalhistory->baselinetimeexpires = 1500;
        $originalhistory->timecompleted = 500;
        $originalhistory->timemodified = time();
        $originalhistory->unassigned = 0;

        $this->assertTrue(certif_write_completion_history($originalhistory));
        $this->assertEquals(0, $DB->count_records('certif_completion'));
        $this->assertEquals(1, $DB->count_records('certif_completion_history'));
        $this->assertTrue($DB->record_exists('certif_completion_history', (array) $originalhistory));
    }

    /**
     * Try to repeat writing the same history data from the last test. This is considered a duplicate
     * and is not allowed.
     */
    public function test_certif_write_completion_history_duplicate() {
        global $DB;

        /* @var totara_program_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $certification1 = new program($programgenerator->create_certification());

        $originalhistory = new stdClass();
        $originalhistory->certifid = $certification1->certifid;
        $originalhistory->userid = $this->getDataGenerator()->create_user()->id;
        $originalhistory->certifpath = CERTIFPATH_RECERT;
        $originalhistory->status = CERTIFSTATUS_COMPLETED;
        $originalhistory->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $originalhistory->timewindowopens = 1000;
        $originalhistory->timeexpires = 1500;
        $originalhistory->baselinetimeexpires = 1500;
        $originalhistory->timecompleted = 500;
        $originalhistory->timemodified = time();
        $originalhistory->unassigned = 0;

        $this->assertTrue(certif_write_completion_history($originalhistory));

        try {
            certif_write_completion_history($originalhistory);
            $this->fail('Expected exception was not thrown.');
        } catch (\moodle_exception $e) {
            $this->assertContains('Call to certif_write_completion_history with completion record that does not match the existing record', $e->getMessage());
        }
        $this->assertEquals(1, $DB->count_records('certif_completion_history'));
    }

    /**
     * The only change made in from the original data is the timewindowopens. This is still considered a duplicate
     * and is not allowed.
     */
    public function test_certif_write_completion_history_different_timewindowopens() {
        global $DB;

        /* @var totara_program_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $certification1 = new program($programgenerator->create_certification());

        $originalhistory = new stdClass();
        $originalhistory->certifid = $certification1->certifid;
        $originalhistory->userid = $this->getDataGenerator()->create_user()->id;
        $originalhistory->certifpath = CERTIFPATH_RECERT;
        $originalhistory->status = CERTIFSTATUS_COMPLETED;
        $originalhistory->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $originalhistory->timewindowopens = 1000;
        $originalhistory->timeexpires = 1500;
        $originalhistory->baselinetimeexpires = 1500;
        $originalhistory->timecompleted = 500;
        $originalhistory->timemodified = time();
        $originalhistory->unassigned = 0;

        $this->assertTrue(certif_write_completion_history($originalhistory));

        $timewindowopenshistory = clone $originalhistory;
        $timewindowopenshistory->timewindowopens = $originalhistory->timewindowopens + 100;
        try {
            certif_write_completion_history($timewindowopenshistory);
            $this->fail('Expected exception was not thrown.');
        } catch (\moodle_exception $e) {
            $this->assertContains('Call to certif_write_completion_history with completion record that does not match the existing record', $e->getMessage());
        }
        $this->assertEquals(1, $DB->count_records('certif_completion_history'));
        $this->assertTrue($DB->record_exists('certif_completion_history', (array) $originalhistory));
    }

    /**
     * Change only the timecompleted value from the original data. This is now considered a unique record
     * and is inserted alongside the original record.
     */
    public function test_certif_write_completion_history_different_timecompleted() {
        global $DB;

        /* @var totara_program_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $certification1 = new program($programgenerator->create_certification());

        $originalhistory = new stdClass();
        $originalhistory->certifid = $certification1->certifid;
        $originalhistory->userid = $this->getDataGenerator()->create_user()->id;
        $originalhistory->certifpath = CERTIFPATH_RECERT;
        $originalhistory->status = CERTIFSTATUS_COMPLETED;
        $originalhistory->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $originalhistory->timewindowopens = 1000;
        $originalhistory->timeexpires = 1500;
        $originalhistory->baselinetimeexpires = 1500;
        $originalhistory->timecompleted = 500;
        $originalhistory->timemodified = time();
        $originalhistory->unassigned = 0;

        $this->assertTrue(certif_write_completion_history($originalhistory));

        $timecompletedhistory = clone $originalhistory;
        $timecompletedhistory->timecompleted = $originalhistory->timecompleted + 100;
        certif_write_completion_history($timecompletedhistory);
        $this->assertEquals(2, $DB->count_records('certif_completion_history'));
        $this->assertTrue($DB->record_exists('certif_completion_history', (array) $originalhistory));
        $this->assertTrue($DB->record_exists('certif_completion_history', (array) $timecompletedhistory));
    }

    /**
     * Change the timeexpires from the original data. This is considered unique and is inserted alongside
     * the two records that already exist.
     */
    public function test_certif_write_completion_history_different_timeexpires() {
        global $DB;

        /* @var totara_program_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $certification1 = new program($programgenerator->create_certification());

        $originalhistory = new stdClass();
        $originalhistory->certifid = $certification1->certifid;
        $originalhistory->userid = $this->getDataGenerator()->create_user()->id;
        $originalhistory->certifpath = CERTIFPATH_RECERT;
        $originalhistory->status = CERTIFSTATUS_COMPLETED;
        $originalhistory->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $originalhistory->timewindowopens = 1000;
        $originalhistory->timeexpires = 1500;
        $originalhistory->baselinetimeexpires = 1500;
        $originalhistory->timecompleted = 500;
        $originalhistory->timemodified = time();
        $originalhistory->unassigned = 0;

        $this->assertTrue(certif_write_completion_history($originalhistory));

        $timecompletedhistory = clone $originalhistory;
        $timecompletedhistory->timecompleted = $originalhistory->timecompleted + 100;
        certif_write_completion_history($timecompletedhistory);

        $timeexpireshistory = clone $originalhistory;
        $timeexpireshistory->timeexpires = $originalhistory->timeexpires + 100;
        certif_write_completion_history($timeexpireshistory);
        $this->assertEquals(3, $DB->count_records('certif_completion_history'));
        $this->assertTrue($DB->record_exists('certif_completion_history', (array) $originalhistory));
        $this->assertTrue($DB->record_exists('certif_completion_history', (array) $timecompletedhistory));
        $this->assertTrue($DB->record_exists('certif_completion_history', (array) $timeexpireshistory));
    }
}
