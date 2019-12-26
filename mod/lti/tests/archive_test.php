<?php
/*
* This file is part of Totara Learn
*
* Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
* along with this program. If not, see <http://www.gnu.org/licenses/>.
*
* @author Yuliya Bozhko <yuliya.bozhko@totaralearning.com>
* @package mod_lti
*/

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/lti/lib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/mod/lti/servicelib.php');

class mod_lti_archive_testcase extends advanced_testcase {

    /**
     * Is archive completion supported?
     */
    public function test_module_supports_archive_completion() {
        self::assertTrue(lti_supports(FEATURE_ARCHIVE_COMPLETION));
    }

    public function test_archive() {
        global $CFG, $DB;

        self::setAdminUser();

        // Enable site-wide completion setting.
        $CFG->enablecompletion = true;

        // Create a course.
        $coursedefaults = ['enablecompletion' => COMPLETION_ENABLED];
        $course = self::getDataGenerator()->create_course($coursedefaults);

        // Check it has course completion.
        $completioninfo = new completion_info($course);
        self::assertEquals(COMPLETION_ENABLED, $completioninfo->is_enabled());

        // Create LTI activity and add it to the course.
        $completiondefaults = [
            'completion'     => COMPLETION_TRACKING_AUTOMATIC,
            'completionview' => COMPLETION_VIEW_REQUIRED,
        ];
        $moddefaults = [
            'course'  => $course->id,
            'toolurl' => 'http://exmaple.lti.org',
        ];
        $lti = self::getDataGenerator()->create_module('lti', $moddefaults, $completiondefaults);
        self::assertEquals(1, $DB->count_records('lti'));

        // Create completion criteria based on the LTI activity.
        /** @var core_completion_generator $comp_generator */
        $comp_generator = self::getDataGenerator()->get_plugin_generator('core_completion');
        $comp_generator->set_completion_criteria($course, [
            COMPLETION_CRITERIA_TYPE_ACTIVITY => [
                'elements'          => [$lti],
                'aggregationmethod' => COMPLETION_AGGREGATION_ANY,
            ],
        ]);
        $comp_generator->set_aggregation_method($course->id, COMPLETION_CRITERIA_TYPE_ACTIVITY, COMPLETION_AGGREGATION_ANY);

        // Create a user and enrol them in the course.
        $user = self::getDataGenerator()->create_user();
        self::assertTrue(self::getDataGenerator()->enrol_user($user->id, $course->id));

        // Get the course module.
        $course_module = get_coursemodule_from_instance('lti', $lti->id, $course->id);
        self::assertEquals(COMPLETION_TRACKING_AUTOMATIC, $completioninfo->is_enabled($course_module));

        // Check it isn't complete.
        $params = array('userid' => $user->id, 'coursemoduleid' => $course_module->id);
        $completionstate = $DB->get_field('course_modules_completion', 'completionstate', $params);
        self::assertEmpty($completionstate);

        // Create LTI submission data.
        self::assertEquals(0, $DB->count_records('lti_submission'));
        self::assertEquals(0, $DB->count_records('lti_submission_history'));
        lti_update_grade($lti, $user->id, 255, 0.65);
        self::assertEquals(1, $DB->count_records('lti_submission'));
        self::assertEquals(0, $DB->count_records('lti_submission_history')); // History should not be written yet.

        // Trigger the module completion - set viewed.
        self::assertEquals(1, $DB->count_records('course_completions'));
        self::assertEquals(0, $DB->count_records('course_completion_crit_compl'));
        self::assertEquals(0, $DB->count_records('course_modules_completion'));
        $completioninfo->set_module_viewed($course_module, $user->id);
        $completed = $DB->get_field('course_completions', 'status', ['course' => $course->id, 'userid' => $user->id]);
        self::assertEquals(COMPLETION_STATUS_COMPLETE, $completed);
        self::assertEquals(1, $DB->count_records('course_completion_crit_compl'));
        self::assertEquals(1, $DB->count_records('course_modules_completion'));

        // Update completion state.
        $completioninfo = new completion_info($course);
        if ($completioninfo->is_enabled($course_module)) {
            $completioninfo->update_state($course_module, COMPLETION_COMPLETE, $user->id);
        }

        // Check it's completed.
        $completionstate = $DB->get_field('course_modules_completion', 'completionstate', $params, MUST_EXIST);
        self::assertEquals(COMPLETION_COMPLETE, $completionstate);

        // Archive it, checking that it doesn't mess up course completions.
        $completed = $DB->get_field('course_completions', 'status', ['course' => $course->id, 'userid' => $user->id]);
        self::assertEquals(COMPLETION_STATUS_COMPLETE, $completed);
        self::assertEquals(1, $DB->count_records('course_completion_crit_compl'));
        self::assertEquals(1, $DB->count_records('course_modules_completion'));
        self::assertEquals(1, $DB->count_records('lti_submission'));
        self::assertEquals(0, $DB->count_records('lti_submission_history'));
        archive_course_completion($user->id, $course->id);
        self::assertEquals(0, $DB->count_records('course_completions'));
        archive_course_activities($user->id, $course->id);
        self::assertEquals(0, $DB->count_records('course_completions'));
        self::assertEquals(0, $DB->count_records('course_completion_crit_compl'));
        self::assertEquals(0, $DB->count_records('course_modules_completion'));
        self::assertEquals(0, $DB->count_records('lti_submission'));
        self::assertEquals(1, $DB->count_records('lti_submission_history'));
    }
}
