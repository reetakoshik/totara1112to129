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
 * @package totara_program
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/program/lib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');

/**
 * Lib test for prog_process_extension to ensure correct behaviour of extensions
 */
class totara_program_extension_testcase extends reportcache_advanced_testcase {

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

    public function test_granting_extension_extends_due_date() {
        global $DB;

        $this->setAdminUser();

        //Create a program
        $programid = $this->program_generator->create_program()->id;

        //Assign User to program
        $this->program_generator->assign_to_program($programid, ASSIGNTYPE_INDIVIDUAL, $this->user->id);

        //Create a program completion
        $duedate = time();
        $prog_completion = new stdClass();
        $prog_completion->programid = $programid;
        $prog_completion->userid = $this->user->id;
        $prog_completion->status = STATUS_PROGRAM_INCOMPLETE;
        $prog_completion->timedue = $duedate;
        $prog_completion->timecompleted = 0;
        $prog_completion->organisationid = 0;
        $prog_completion->positionid = 0;

        prog_write_completion($prog_completion);

        //Request extension for user
        $extensiondate = strtotime('+1 day', $duedate);

        $extension = new stdClass;
        $extension->programid = $programid;
        $extension->userid = $this->user->id;
        $extension->extensiondate = $extensiondate;
        $extension->extensionreason = ""; //Ehhh we don't need a reason
        $extension->status = 0;

        $extensionid = $DB->insert_record('prog_extension', $extension);
        prog_process_extensions(array($extensionid => PROG_EXTENSION_GRANT), array($extensionid => ''));
        $result = prog_load_completion($extension->programid, $extension->userid);

        $this->assertEquals($result->timedue, $extensiondate);
    }
}