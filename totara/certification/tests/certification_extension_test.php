<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Carl Anderson <carl.anderson@totaralearning.com>
 * @package totara_certification
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/program/lib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');

/**
 * Lib test for prog_process_extension to ensure correct behaviour of extensions
 */
class totara_certif_extension_testcase extends reportcache_advanced_testcase {

    /** @var totara_reportbuilder_cache_generator $data_generator */
    private $data_generator;

    /** @var totara_program_generator $program_generator */
    private $program_generator;

    private $user, $course;


    protected function setUp() {
        global $DB, $CFG;

        parent::setUp();
        $this->resetAfterTest(true);

        $CFG->enablecompletion = true;

        $this->data_generator = $this->getDataGenerator();
        $this->program_generator = $this->data_generator->get_plugin_generator('totara_program');

        $this->course = $this->data_generator->create_course(array('enablecompletion' => true));
        $this->user = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->assertNotEmpty($studentrole);

        // Get manual enrolment plugin and enrol user.
        require_once($CFG->dirroot.'/enrol/manual/locallib.php');
        $manplugin = enrol_get_plugin('manual');
        $maninstance = $DB->get_record('enrol', array('courseid' => $this->course->id, 'enrol' => 'manual'), '*', MUST_EXIST);
        $manplugin->enrol_user($maninstance, $this->user->id, $studentrole->id);
        $this->assertEquals(1, $DB->count_records('user_enrolments'));

        $completionsettings = array('completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionview' => 1);
        $this->module = $this->getDataGenerator()->create_module('forum', array('course' => $this->course->id), $completionsettings);
    }

    protected function tearDown() {
        $this->data_generator = null;
        $this->program_generator = null;
        $this->user = null;
        $this->course = null;
        parent::tearDown();
    }

    public function test_can_request_extension() {
        global $DB;

        $initdate = time();

        // Set up some stuff.
        $user = $this->data_generator->create_user();
        $certification = $this->data_generator->create_certification();
        $course1 = $this->data_generator->create_course();
        $course2 = $this->data_generator->create_course();

        // Add the courses to the certification.
        $this->data_generator->add_courseset_program($certification->id, array($course1->id), CERTIFPATH_CERT);
        $this->data_generator->add_courseset_program($certification->id, array($course2->id), CERTIFPATH_RECERT);

        // Assign the user to the cert as an individual.
        $this->data_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);

        list($certcompletion, $progcompletion) = certif_load_completion($certification->id, $user->id);
        // Check the existing data.
        self::assertEquals(1, $DB->count_records('prog_completion', array('coursesetid' => 0)));
        self::assertEquals(1, $DB->count_records('certif_completion'));

        // Update the certification so that it is in progress and has a due date.
        $certcompletion->status = CERTIFSTATUS_INPROGRESS;
        $progcompletion->timedue = strtotime('+1 day', $initdate);
        self::assertTrue(certif_write_completion($certcompletion, $progcompletion));

        // Users can request extensions for themselves only.
        $this->setUser($user->id);
        self::assertTrue($certification->can_request_extension($user->id));

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
        self::assertTrue(certif_write_completion($certcompletion, $progcompletion)); // Contains data validation, so we don't need to check it here.

        // Check that expired user can no longer request extension.
        $this->setUser($user->id);
        self::assertFalse($certification->can_request_extension($user->id));
    }

    public function test_granting_extension_extends_expiry() {
        global $DB;

        $this->setAdminUser();

        //Create a certification
        $prog = $this->getDataGenerator()->create_certification(array(
            'cert_recertifydatetype' => CERTIFRECERT_EXPIRY
        ));

        //Assign User to program
        $this->getDataGenerator()->assign_to_program($prog->id, ASSIGNTYPE_INDIVIDUAL, $this->user->id);

        $initdate = time();
        $expirydate = strtotime('+1 day', $initdate);

        $submitted = new stdClass();
        $submitted->id = $prog->id;
        $submitted->userid = $this->user->id;
        $submitted->status = CERTIFSTATUS_COMPLETED;
        $submitted->renewalstatus = CERTIFRENEWALSTATUS_DUE;
        $submitted->certifpath = CERTIFPATH_RECERT;
        $submitted->timecompleted = 1004;
        $submitted->timewindowopens = 1005;
        $submitted->timeexpires = $expirydate;
        $submitted->baselinetimeexpires = $expirydate;
        $submitted->progstatus = STATUS_PROGRAM_INCOMPLETE;
        $submitted->timeduenotset = 'no';
        $submitted->timedue = $initdate;
        $submitted->progtimecompleted = 0;

        // Get completion data
        list($certcompletion, $progcompletion) = certif_process_submitted_edit_completion($submitted);

        //Commit it to the database
        $DB->update_record('certif_completion', $certcompletion);
        $DB->update_record('prog_completion', $progcompletion);

        //Request extension for user
        $extensiondate = strtotime('+1 day', $expirydate);

        $extension = new stdClass;
        $extension->programid = $prog->id;
        $extension->userid = $this->user->id;
        $extension->extensiondate = $extensiondate;
        $extension->extensionreason = ""; //Ehhh we don't need a reason
        $extension->status = 0;

        $extensionid = $DB->insert_record('prog_extension', $extension);

        prog_process_extensions(array($extensionid => PROG_EXTENSION_GRANT), array($extensionid => ''));

        //Check that extension moves timeexpires, but not baselinetimeexpires
        list($updatedcert, $updatedprog) = certif_load_completion($extension->programid, $extension->userid);
        $this->assertEquals($extensiondate, $updatedcert->timeexpires);
        $this->assertEquals($expirydate, $updatedcert->baselinetimeexpires);

        //Write cert completion, and verify that new completion has used baselinetimeexpires as the base instead of
        //timeexpires
        write_certif_completion($prog->certifid, $this->user->id, CERTIFPATH_RECERT);

        //Grab the certificate active period
        $activeperiod = $DB->get_field('certif', 'activeperiod', array('id' => $prog->certifid));

        $expectedexpiry = strtotime($activeperiod, $expirydate);
        $updatedcert = $DB->get_record('certif_completion', array('certifid' => $prog->certifid, 'userid' => $this->user->id));
        $updatedprog = $DB->get_record('prog_completion', array('programid' => $prog->id, 'userid' => $this->user->id));

        $this->assertEquals($expectedexpiry, $updatedprog->timedue);
        $this->assertEquals($expectedexpiry, $updatedcert->timeexpires);
        $this->assertEquals($expectedexpiry, $updatedcert->baselinetimeexpires); //verify default expiry is reset
    }
}