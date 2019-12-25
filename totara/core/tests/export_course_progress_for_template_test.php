<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author     Riana Rossouw <riana.rossouw@totaralearning.com>
 * @copyright  2017 Totara Learning Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package    totara_core
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/totara/core/renderer.php');

/**
 * Progress information generation test
 */
class totara_core_export_course_progress_for_template_testcase extends externallib_advanced_testcase {

    /**
     * Test not tracked
     */
    public function test_export_for_template_not_tracked() {
        global $DB, $CFG, $PAGE;

        $this->resetAfterTest(true);

        $CFG->enablecompletion = true;
        $student = $this->getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 0));

        $data = $this->getDataGenerator()->create_module('data', array('course' => $course->id));

        $cmdata = get_coursemodule_from_id('data', $data->cmid);

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);

        $renderer = $PAGE->get_renderer('totara_core');
        $data = $renderer->export_course_progress_for_template($student->id, $course->id, COMPLETION_STATUS_INPROGRESS);

        $this->assertEquals('Not tracked', $data->statustext);
        $this->assertFalse(property_exists($data, 'pbar'));
    }

    /**
     * Test tracked without criteria
     */
    public function test_export_for_template_tracked_without_criteria() {
        global $DB, $CFG, $PAGE;

        $this->resetAfterTest(true);

        $CFG->enablecompletion = true;
        $student = $this->getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));

        $data = $this->getDataGenerator()->create_module('data', array('course' => $course->id));

        $cmdata = get_coursemodule_from_id('data', $data->cmid);

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);

        $renderer = $PAGE->get_renderer('totara_core');
        $data = $renderer->export_course_progress_for_template($student->id, $course->id, COMPLETION_STATUS_INPROGRESS);

        $this->assertEquals('No criteria', $data->statustext);
        $this->assertFalse(property_exists($data, 'pbar'));
    }

    /**
     * Test tracked with criteria
     */
    public function test_export_for_template_tracked_with_criteria() {
        global $DB, $CFG, $PAGE;

        $CFG->enablecompletion = true;
        $student = $this->getDataGenerator()->create_user(['idnumber' => 'export_student']);

        $course = $this->getDataGenerator()->create_course(array('idnumber' => 'test_course'));

        $data = $this->getDataGenerator()->create_module('data', array('course' => $course->id));

        $cmdata = get_coursemodule_from_id('data', $data->cmid);

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);

        /** @var core_completion_generator $cgen */
        $cgen = $this->getDataGenerator()->get_plugin_generator('core_completion');
        $cgen->enable_completion_tracking($course);
        $cgen->set_activity_completion($course->id, array($data));

        $renderer = $PAGE->get_renderer('totara_core');
        $data = $renderer->export_course_progress_for_template($student->id, $course->id, COMPLETION_STATUS_INPROGRESS);

        $this->assertEquals('In progress', $data->statustext);
        $this->assertTrue(property_exists($data, 'pbar'));
    }

    public function test_export_for_template_tracked_later_disabled() {
        global $DB, $CFG, $PAGE;

        $CFG->enablecompletion = true;
        $student = $this->getDataGenerator()->create_user(['idnumber' => 'export_student']);

        $course = $this->getDataGenerator()->create_course(array('idnumber' => 'test_course'));

        $data = $this->getDataGenerator()->create_module('data', array('course' => $course->id));

        $cmdata = get_coursemodule_from_id('data', $data->cmid);

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);

        /** @var core_completion_generator $cgen */
        $cgen = $this->getDataGenerator()->get_plugin_generator('core_completion');
        $cgen->enable_completion_tracking($course);
        $cgen->set_activity_completion($course->id, array($data));

        $student = $DB->get_record('user', ['idnumber' => 'export_student'], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['idnumber' => 'test_course'], '*', MUST_EXIST);

        /** @var core_completion_generator $cgen */
        $cgen = $this->getDataGenerator()->get_plugin_generator('core_completion');
        $cgen->disable_completion_tracking($course);

        $renderer = $PAGE->get_renderer('totara_core');
        $data = $renderer->export_course_progress_for_template($student->id, $course->id, COMPLETION_STATUS_INPROGRESS);

        $this->assertEquals('Not tracked', $data->statustext);
        $this->assertFalse(property_exists($data, 'pbar'));

        // Enabling completion tracking again
        $cgen->enable_completion_tracking($course);

        $data = $renderer->export_course_progress_for_template($student->id, $course->id, COMPLETION_STATUS_INPROGRESS);

        $this->assertEquals('In progress', $data->statustext);
        $this->assertTrue(property_exists($data, 'pbar'));
    }
}
