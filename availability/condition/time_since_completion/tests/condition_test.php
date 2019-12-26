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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package availability_time_since_completion
 */

defined('MOODLE_INTERNAL') || die();

use availability_time_since_completion\condition;

global $CFG;
require_once($CFG->libdir . '/completionlib.php');

/**
 * @group availability
 */
class availability_time_since_completion_condition_testcase extends advanced_testcase {

    /**
     * Load required classes.
     */
    public function setUp() {
        // Load the mock info class so that it can be used.
        global $CFG;
        require_once($CFG->dirroot . '/availability/tests/fixtures/mock_info.php');
    }

    /**
     * Set up our data
     *
     * @return stdClass
     */
    public function setup_data() {
        global $CFG;
        $this->resetAfterTest();

        $data = new stdClass;

        // Create course with completion turned on.
        $CFG->enablecompletion = true;
        $CFG->enableavailability = true;
        $generator = $this->getDataGenerator();
        $data->course = $generator->create_course(array('enablecompletion' => 1));
        $data->user = $generator->create_user();
        $generator->enrol_user($data->user->id, $data->course->id);
        $this->setUser($data->user);

        // Create an assignment - we need to have something that can be graded
        // so as to test the PASS/FAIL states. Set it up to be completed based
        // on its grade item.
        $assignrow = $this->getDataGenerator()->create_module('assign', array(
            'course' => $data->course->id, 'name' => 'Assign!',
            'completion' => COMPLETION_TRACKING_MANUAL
        ));
        $data->assign = new assign(context_module::instance($assignrow->cmid), false, false);

        // Get basic details.
        $data->assigncm = $data->assign->get_course_module();
        $data->info = new \core_availability\mock_info($data->course, $data->user->id);

        $data->completion = new completion_info($data->course);

        return $data;
    }

    /**
     * Updates the grade of a user in the given assign module instance.
     *
     * @param stdClass $assignrow Assignment row from database
     * @param int $userid User id
     * @param float $grade Grade
     */
    protected static function set_grade($assignrow, $userid, $grade) {
        $grades = array();
        $grades[$userid] = (object)array(
            'rawgrade' => $grade, 'userid' => $userid);
        $assignrow->cmidnumber = null;
        assign_grade_item_update($assignrow, $grades);
    }

    /**
     * Get the condition description.
     *
     * @param stdclass $data
     * @param object $condition
     * @param bool $not
     *
     * @return string The description
     */
    public function get_description($data, $condition, $not) {
        $information = $condition->get_description(false, $not, $data->info);
        $information = \core_availability\info::format_info($information, $data->course);
        $information = strip_tags($information);
        return $information;
    }

    /**
     * Tests constructing and using condition as part of tree.
     */
    public function test_in_tree() {
        global $USER, $CFG, $DB;
        $this->resetAfterTest();

        $this->setAdminUser();

        // Create course with completion turned on and a Page.
        $CFG->enablecompletion = true;
        $CFG->enableavailability = true;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(array('enablecompletion' => 1));
        $page = $generator->get_plugin_generator('mod_page')->create_instance(
                array('course' => $course->id, 'completion' => COMPLETION_TRACKING_MANUAL));

        $modinfo = get_fast_modinfo($course);
        $cm = $modinfo->get_cm($page->cmid);
        $info = new \core_availability\mock_info($course, $USER->id);

        $structure = (object)array('op' => '|', 'show' => true, 'c' => array(
                (object)array('type' => 'time_since_completion', 'cm' => (int)$cm->id,
                'expectedcompletion' => COMPLETION_COMPLETE, 'timeamount' => 1, 'timeperiod' => condition::TIME_PERIOD_DAYS
                )));
        $tree = new \core_availability\tree($structure);

        // Initial check (user has not completed activity).
        $result = $tree->check_available(false, $info, true, $USER->id);
        $this->assertFalse($result->is_available());

        // Mark activity complete.
        $completion = new completion_info($course);
        $completion->update_state($cm, COMPLETION_COMPLETE);

        // Still no access, the required time has not passed.
        $result = $tree->check_available(false, $info, true, $USER->id);
        $this->assertFalse($result->is_available());

        // Change the completion date so the condition is met.
        $data = $DB->get_record('course_modules_completion', array('coursemoduleid' => $cm->id, 'userid' => $USER->id));
        $data->timemodified = $data->timemodified - (4 * DAYSECS);
        $DB->update_record('course_modules_completion', $data);
        $completion->invalidatecache();

        // Now it's true!
        $result = $tree->check_available(false, $info, true, $USER->id);
        $this->assertTrue($result->is_available());
    }

    /**
     * Tests the constructor including error conditions. Also tests the
     * string conversion feature (intended for debugging only).
     */
    public function test_constructor() {
        // No parameters.
        $structure = new stdClass();
        try {
            $cond = new condition($structure);
            $this->fail();
        } catch (coding_exception $e) {
            $this->assertContains('Missing or invalid ->cm', $e->getMessage());
        }

        // Invalid $cm.
        $structure->cm = 'hello';
        try {
            $cond = new condition($structure);
            $this->fail();
        } catch (coding_exception $e) {
            $this->assertContains('Missing or invalid ->cm', $e->getMessage());
        }

        $structure->cm = 42;

        // Missing $expectedcompletion.
        try {
            $cond = new condition($structure);
            $this->fail();
        } catch (coding_exception $e) {
            $this->assertContains('Missing or invalid ->expectedcompletion', $e->getMessage());
        }

        // Invalid $expectedcompletion.
        $structure->expectedcompletion = 99;
        try {
            $cond = new condition($structure);
            $this->fail();
        } catch (coding_exception $e) {
            $this->assertContains('Missing or invalid ->expectedcompletion', $e->getMessage());
        }

        $structure->expectedcompletion = COMPLETION_COMPLETE;

        // Missing $timeamount.
        try {
            $cond = new condition($structure);
            $this->fail();
        } catch (coding_exception $e) {
            $this->assertContains('Missing or invalid ->timeamount', $e->getMessage());
        }

        // Invalid $timeamount.
        $structure->timeamount = 'hello';
        try {
            $cond = new condition($structure);
            $this->fail();
        } catch (coding_exception $e) {
            $this->assertContains('Missing or invalid ->timeamount', $e->getMessage());
        }

        // Invalid $a.
        $structure->timeamount = 0;
        try {
            $cond = new condition($structure);
            $this->fail();
        } catch (coding_exception $e) {
            $this->assertContains('Missing or invalid ->timeamount', $e->getMessage());
        }

        // Invalid $a.
        $structure->timeamount = -1;
        try {
            $cond = new condition($structure);
            $this->fail();
        } catch (coding_exception $e) {
            $this->assertContains('Missing or invalid ->timeamount', $e->getMessage());
        }

        $structure->timeamount = 2;

        // Missing $timeperiod.
        try {
            $cond = new condition($structure);
            $this->fail();
        } catch (coding_exception $e) {
            $this->assertContains('Missing or invalid ->timeperiod', $e->getMessage());
        }

        // Invalid $timeperiod.
        $structure->timeperiod = 'hello';
        try {
            $cond = new condition($structure);
            $this->fail();
        } catch (coding_exception $e) {
            $this->assertContains('Missing or invalid ->timeperiod', $e->getMessage());
        }

        $structure->timeperiod = condition::TIME_PERIOD_DAYS;

        // Successful construct & display with all different expected values.
        $cond = new condition($structure);
        $this->assertEquals('{time_since_completion:cm42 COMPLETE}', (string)$cond);

        $structure->expectedcompletion = COMPLETION_COMPLETE_PASS;
        $cond = new condition($structure);
        $this->assertEquals('{time_since_completion:cm42 COMPLETE_PASS}', (string)$cond);

        $structure->expectedcompletion = COMPLETION_COMPLETE_FAIL;
        $cond = new condition($structure);
        $this->assertEquals('{time_since_completion:cm42 COMPLETE_FAIL}', (string)$cond);

        $structure->expectedcompletion = COMPLETION_INCOMPLETE;
        $cond = new condition($structure);
        $this->assertEquals('{time_since_completion:cm42 INCOMPLETE}', (string)$cond);
    }

    /**
     * Tests the save() function.
     */
    public function test_save() {
        $structure                      = new stdClass();
        $structure->cm                  = 42;
        $structure->expectedcompletion  = COMPLETION_COMPLETE;
        $structure->timeamount          = 2;
        $structure->timeperiod          = condition::TIME_PERIOD_DAYS;
        $cond                           = new condition($structure);
        $structure->type                = 'time_since_completion';
        $this->assertEquals($structure, $cond->save());
    }

    /**
     * Test the is_available and get_description functions when using with COMPLETION_COMPLETE.
     */
    public function test_usage_completion_complete() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        $this->resetAfterTest();

        // Create course with completion turned on.
        $CFG->enablecompletion = true;
        $CFG->enableavailability = true;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(array('enablecompletion' => 1));
        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id);
        $this->setUser($user);

        // Create an assignment - we need to have something that can be graded
        // so as to test the PASS/FAIL states. Set it up to be completed based
        // on its grade item.
        $assignrow = $this->getDataGenerator()->create_module('assign', array(
            'course'     => $course->id, 'name' => 'Assign!',
            'completion' => COMPLETION_TRACKING_MANUAL
        ));
        $assign = new assign(context_module::instance($assignrow->cmid), false, false);

        // Get basic details.
        $assigncm = $assign->get_course_module();
        $info = new \core_availability\mock_info($course, $user->id);

        // Set up the condition.
        $cond = new condition((object)array(
            'cm'                 => (int)$assigncm->id,
            'expectedcompletion' => COMPLETION_COMPLETE,
            'timeamount'         => 2, // 2 days.
            'timeperiod'         => condition::TIME_PERIOD_DAYS));

        // Must match.
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course->id);
        $information = strip_tags($information);
        $this->assertEquals('2 days have elapsed since the activity Assign! is marked complete', $information);

        // Must NOT match.
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));
        $information = $cond->get_description(false, true, $info);
        $information = \core_availability\info::format_info($information, $course);
        $information = strip_tags($information);
        $this->assertEquals('2 days have elapsed since the activity Assign! is incomplete', $information);

        // Mark assign complete.
        $completion = new completion_info($course);
        $completion->update_state($assigncm, COMPLETION_COMPLETE, $user->id);

        // Must match.
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course);
        $information = strip_tags($information);
        $this->assertEquals('2 days have elapsed since the activity Assign! is marked complete', $information);

        // Must NOT match.
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));
        $information = $cond->get_description(false, true, $info);
        $information = \core_availability\info::format_info($information, $course);
        $information = strip_tags($information);
        $this->assertEquals('2 days have elapsed since the activity Assign! is incomplete', $information);

        // Change the completion date so the condition is met.
        $data = $DB->get_record('course_modules_completion', array('coursemoduleid' => $assigncm->id, 'userid' => $user->id));
        $data->timemodified = $data->timemodified - (4 * DAYSECS);
        $DB->update_record('course_modules_completion', $data);

        $completion->invalidatecache();

        // Must match.
        $this->assertTrue($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course);
        $information = strip_tags($information);
        $this->assertEquals($information, '2 days have elapsed since the activity Assign! is marked complete');

        // Must NOT match.
        $this->assertFalse($cond->is_available(true, $info, true, $user->id));
        $information = $cond->get_description(false, true, $info);
        $information = \core_availability\info::format_info($information, $course);
        $information = strip_tags($information);
        $this->assertEquals($information, '2 days have elapsed since the activity Assign! is incomplete');
    }

    /**
     * Test the is_available and get_description functions when using with COMPLETION_COMPLETE_PASS.
     */
    public function test_usage_completion_complete_pass() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        $this->resetAfterTest();

        // Create course with completion turned on.
        $CFG->enablecompletion = true;
        $CFG->enableavailability = true;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(array('enablecompletion' => 1));
        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id);
        $this->setUser($user);

        // Create an assignment - we need to have something that can be graded
        // so as to test the PASS/FAIL states. Set it up to be completed based
        // on its grade item.
        $assignrow = $this->getDataGenerator()->create_module('assign', array(
            'course' => $course->id, 'name' => 'Assign!',
            'completion' => COMPLETION_TRACKING_AUTOMATIC
        ));
        $DB->set_field('course_modules', 'completiongradeitemnumber', 0,
            array('id' => $assignrow->cmid));
        $assign = new assign(context_module::instance($assignrow->cmid), false, false);

        // Get basic details.
        $assigncm = $assign->get_course_module();
        $info = new \core_availability\mock_info($course, $user->id);

        // We are going to need the grade item so that we can get pass/fails.
        $gradeitem = $assign->get_grade_item();
        grade_object::set_properties($gradeitem, array('gradepass' => 50.0));
        $gradeitem->update();

        // Set up the condition.
        $cond = new condition((object)array(
            'cm'                 => (int)$assigncm->id,
            'expectedcompletion' => COMPLETION_COMPLETE_PASS,
            'timeamount'         => 2, // 2 days.
            'timeperiod'         => condition::TIME_PERIOD_DAYS));

        // Must match.
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course->id);
        $information = strip_tags($information);
        $this->assertEquals('2 days have elapsed since the activity Assign! is complete and passed', $information);

        // Must NOT match.
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));
        $information = $cond->get_description(false, true, $info);
        $information = \core_availability\info::format_info($information, $course);
        $information = strip_tags($information);
        $this->assertEquals('2 days have elapsed since the activity Assign! is incomplete', $information);

        // Change the grade to be complete and failed.
        self::set_grade($assignrow, $user->id, 40);

        // Must match.
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course);
        $information = strip_tags($information);
        $this->assertEquals('2 days have elapsed since the activity Assign! is complete and passed', $information);

        // Must NOT match.
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));
        $information = $cond->get_description(false, true, $info);
        $information = \core_availability\info::format_info($information, $course);
        $information = strip_tags($information);
        $this->assertEquals('2 days have elapsed since the activity Assign! is incomplete', $information);

        // Change the grade to be complete and passed.
        self::set_grade($assignrow, $user->id, 60);

        // Must match.
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course);
        $information = strip_tags($information);
        $this->assertEquals('2 days have elapsed since the activity Assign! is complete and passed', $information);

        // Must NOT match.
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));
        $information = $cond->get_description(false, true, $info);
        $information = \core_availability\info::format_info($information, $course);
        $information = strip_tags($information);
        $this->assertEquals('2 days have elapsed since the activity Assign! is incomplete', $information);

        // Change the completion date so the condition is met.
        $data = $DB->get_record('course_modules_completion', array('coursemoduleid' => $assigncm->id, 'userid' => $user->id));
        $data->timemodified = $data->timemodified - (4 * DAYSECS);
        $DB->update_record('course_modules_completion', $data);
        $completion = new completion_info($course);
        $completion->invalidatecache();

        // Must match.
        $this->assertTrue($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course);
        $information = strip_tags($information);
        $this->assertEquals($information, '2 days have elapsed since the activity Assign! is complete and passed');

        // Must NOT match.
        $this->assertFalse($cond->is_available(true, $info, true, $user->id));
        $information = $cond->get_description(false, true, $info);
        $information = \core_availability\info::format_info($information, $course);
        $information = strip_tags($information);
        $this->assertEquals($information, '2 days have elapsed since the activity Assign! is incomplete');
    }

    /**
     * Test the is_available and get_description functions when using with COMPLETION_COMPLETE_FAIL.
     */
    public function test_usage_completion_complete_fail() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        $this->resetAfterTest();

        // Create course with completion turned on.
        $CFG->enablecompletion = true;
        $CFG->enableavailability = true;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(array('enablecompletion' => 1));
        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id);
        $this->setUser($user);

        // Create an assignment - we need to have something that can be graded
        // so as to test the PASS/FAIL states. Set it up to be completed based
        // on its grade item.
        $assignrow = $this->getDataGenerator()->create_module('assign', array(
            'course' => $course->id, 'name' => 'Assign!',
            'completion' => COMPLETION_TRACKING_AUTOMATIC
        ));
        $DB->set_field('course_modules', 'completiongradeitemnumber', 0,
            array('id' => $assignrow->cmid));
        $assign = new assign(context_module::instance($assignrow->cmid), false, false);

        // Get basic details.
        $assigncm = $assign->get_course_module();
        $info = new \core_availability\mock_info($course, $user->id);

        // We are going to need the grade item so that we can get pass/fails.
        $gradeitem = $assign->get_grade_item();
        grade_object::set_properties($gradeitem, array('gradepass' => 50.0));
        $gradeitem->update();

        // Set up the condition.
        $cond = new condition((object)array(
            'cm' => (int)$assigncm->id,
            'expectedcompletion' => COMPLETION_COMPLETE_FAIL,
            'timeamount' => 2, // 2 days.
            'timeperiod' => condition::TIME_PERIOD_DAYS));

        // Must match.
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course->id);
        $information = strip_tags($information);
        $this->assertEquals('2 days have elapsed since the activity Assign! is complete and failed', $information);

        // Must NOT match.
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));
        $information = $cond->get_description(false, true, $info);
        $information = \core_availability\info::format_info($information, $course);
        $information = strip_tags($information);
        $this->assertEquals('2 days have elapsed since the activity Assign! is incomplete', $information);

        // Mark assign complete.
        $completion = new completion_info($course);
        $completion->update_state($assigncm, COMPLETION_COMPLETE, $user->id);

        // Change the grade to be complete and failed.
        self::set_grade($assignrow, $user->id, 40);

        // Must match.
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course);
        $information = strip_tags($information);
        $this->assertEquals('2 days have elapsed since the activity Assign! is complete and failed', $information);

        // Must NOT match.
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));
        $information = $cond->get_description(false, true, $info);
        $information = \core_availability\info::format_info($information, $course);
        $information = strip_tags($information);
        $this->assertEquals('2 days have elapsed since the activity Assign! is incomplete', $information);

        // Change the grade to be complete and passed.
        self::set_grade($assignrow, $user->id, 60);

        // Must match.
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course);
        $information = strip_tags($information);
        $this->assertEquals('2 days have elapsed since the activity Assign! is complete and failed', $information);

        // Must NOT match.
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));
        $information = $cond->get_description(false, true, $info);
        $information = \core_availability\info::format_info($information, $course);
        $information = strip_tags($information);
        $this->assertEquals('2 days have elapsed since the activity Assign! is incomplete', $information);

        // Change the completion date so the condition is met.
        $data = $DB->get_record('course_modules_completion', array('coursemoduleid' => $assigncm->id, 'userid' => $user->id));
        $data->timemodified = $data->timemodified - (4 * DAYSECS);
        $DB->update_record('course_modules_completion', $data);

        $completion->invalidatecache();

        // Must match.
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $information = \core_availability\info::format_info($information, $course);
        $information = strip_tags($information);
        $this->assertEquals($information, '2 days have elapsed since the activity Assign! is complete and failed');

        // Must NOT match.
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));
        $information = $cond->get_description(false, true, $info);
        $information = \core_availability\info::format_info($information, $course);
        $information = strip_tags($information);
        $this->assertEquals($information, '2 days have elapsed since the activity Assign! is incomplete');
    }

    /**
     * Test the get_description_time method.
     */
    public function test_get_description_time() {
        $this->resetAfterTest();

        $conddata = array('cm' => 1, 'expectedcompletion' => COMPLETION_COMPLETE);

        // 1 day.
        $cond = new condition((object)array_merge($conddata, array('timeamount' => 1, 'timeperiod' => condition::TIME_PERIOD_DAYS)));
        $this->assertEquals('1 day has elapsed since', $cond->get_description_time());

        // 2 days.
        $cond = new condition((object)array_merge($conddata, array('timeamount' => 2, 'timeperiod' => condition::TIME_PERIOD_DAYS)));
        $this->assertEquals('2 days have elapsed since', $cond->get_description_time());

        // 1 week.
        $cond = new condition((object)array_merge($conddata, array('timeamount' => 1, 'timeperiod' => condition::TIME_PERIOD_WEEKS)));
        $this->assertEquals('1 week has elapsed since', $cond->get_description_time());

        // 2 weeks.
        $cond = new condition((object)array_merge($conddata, array('timeamount' => 2, 'timeperiod' => condition::TIME_PERIOD_WEEKS)));
        $this->assertEquals('2 weeks have elapsed since', $cond->get_description_time());

        // 1 year.
        $cond = new condition((object)array_merge($conddata, array('timeamount' => 1, 'timeperiod' => condition::TIME_PERIOD_YEARS)));
        $this->assertEquals('1 year has elapsed since', $cond->get_description_time());

        // 2 years.
        $cond = new condition((object)array_merge($conddata, array('timeamount' => 2, 'timeperiod' => condition::TIME_PERIOD_YEARS)));
        $this->assertEquals('2 years have elapsed since', $cond->get_description_time());
    }

    /**
     * Tests the update_dependency_id() function.
     */
    public function test_update_dependency_id() {
        $cond = new condition((object)array(
                'cm' => 123, 'expectedcompletion' => COMPLETION_COMPLETE, 'timeamount' => 2, 'timeperiod' => condition::TIME_PERIOD_DAYS));
        $this->assertFalse($cond->update_dependency_id('frogs', 123, 456));
        $this->assertFalse($cond->update_dependency_id('course_modules', 12, 34));
        $this->assertTrue($cond->update_dependency_id('course_modules', 123, 456));
        $after = $cond->save();
        $this->assertEquals(456, $after->cm);
    }
}
