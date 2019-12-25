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
 * scorm module PHPUnit archive test class
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit mod_scorm_archive_testcase mod/scorm/tests/archive_test.php
 *
 * @package    mod_scorm
 * @subpackage phpunit
 * @author     Nathan Lewis <nathan.lewis@totaralms.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 *
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/scorm/lib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/completion/criteria/completion_criteria_activity.php');

class mod_scorm_archive_testcase extends advanced_testcase {
    /**
     * Is archive completion supported?
     */
    public function test_module_supports_archive_completion() {
        $this->assertTrue(scorm_supports(FEATURE_ARCHIVE_COMPLETION));
    }

    public function test_archive() {
        global $CFG, $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Enable site-wide completion setting.
        $CFG->enablecompletion = true;

        // Create a course.
        $this->assertEquals(1, $DB->count_records('course')); // Site course.
        $coursedefaults = array('enablecompletion' => COMPLETION_ENABLED);
        $course = $this->getDataGenerator()->create_course($coursedefaults);
        $this->assertEquals(2, $DB->count_records('course')); // Site course + this course.

        // Check it has course competion.
        $completioninfo = new completion_info($course);
        $this->assertEquals(COMPLETION_ENABLED, $completioninfo->is_enabled());

        // Create a scorm and add it to the course.
        $this->assertEquals(0, $DB->count_records('scorm'));
        $completiondefaults = array(
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionview' => COMPLETION_VIEW_REQUIRED
        );
        $scorm = $this->getDataGenerator()->create_module(
                'scorm',
                array('course' => $course->id),
                $completiondefaults);
        $this->assertEquals(1, $DB->count_records('scorm'));

        // Create completion criteria based on the scorm activity.
        // This is usually done as part of form saving, so has to be reproduced here.
        $this->assertEquals(0, $DB->count_records('course_completion_criteria'));
        $this->assertEquals(0, $DB->count_records('course_completion_aggr_methd'));
        $data = new stdClass();
        $data->id = $course->id;
        $data->overall_aggregation = COMPLETION_AGGREGATION_ANY;
        $data->criteria_activity_value = array($scorm->cmid => 1);
        $criterion = new completion_criteria_activity();
        $criterion->update_config($data);
        $aggdata = array(
            'course'        => $data->id,
            'criteriatype'  => COMPLETION_CRITERIA_TYPE_ACTIVITY
        );
        $aggregation = new completion_aggregation($aggdata);
        $aggregation->setMethod(COMPLETION_AGGREGATION_ANY);
        $aggregation->save();
        $this->assertEquals(1, $DB->count_records('course_completion_criteria'));
        $this->assertEquals(1, $DB->count_records('course_completion_aggr_methd'));

        // Create a user.
        $this->assertEquals(2, $DB->count_records('user')); // Guest + Admin.
        $user = $this->getDataGenerator()->create_user();
        $this->assertEquals(3, $DB->count_records('user')); // Guest + Admin + this user.

        // Enrol user on course.
        $this->assertTrue($this->getDataGenerator()->enrol_user($user->id, $course->id));

        // Get the course module.
        $course_module = get_coursemodule_from_instance('scorm', $scorm->id, $course->id);
        $this->assertEquals(COMPLETION_TRACKING_AUTOMATIC, $completioninfo->is_enabled($course_module));

        // Check it isn't complete.
        $params = array('userid' => $user->id, 'coursemoduleid' => $course_module->id);
        $completionstate = $DB->get_field('course_modules_completion', 'completionstate', $params);
        $this->assertEmpty($completionstate);

        // Create scorm attempt data.
        $this->assertEquals(0, $DB->count_records('scorm_scoes_track'));
        $sco = $DB->get_record('scorm_scoes', array('scormtype' => 'sco'));
        scorm_insert_track($user->id, $scorm->id, $sco->id, 1, 'element', 'value', true);
        $this->assertEquals(1, $DB->count_records('scorm_scoes_track'));

        // Trigger the module completion - set viewed.
        $this->assertEquals(1, $DB->count_records('course_completions'));
        $this->assertEquals(0, $DB->count_records('course_completion_crit_compl'));
        $this->assertEquals(0, $DB->count_records('course_modules_completion'));
        $completioninfo->set_module_viewed($course_module, $user->id);
        $this->assertEquals(COMPLETION_STATUS_COMPLETE, $DB->get_field('course_completions', 'status',
            array('course' => $course->id, 'userid' => $user->id)));
        $this->assertEquals(1, $DB->count_records('course_completion_crit_compl'));
        $this->assertEquals(1, $DB->count_records('course_modules_completion'));

        // Update completion state.
        $completioninfo = new completion_info($course);
        if ($completioninfo->is_enabled($course_module)) {
            $completioninfo->update_state($course_module, COMPLETION_COMPLETE, $user->id);
        }

        // Check its completed.
        $completionstate = $DB->get_field('course_modules_completion', 'completionstate', $params, MUST_EXIST);
        $this->assertEquals(COMPLETION_COMPLETE, $completionstate);

        // Archive it, checking that it doesn't mess up course completions.
        $this->assertEquals(COMPLETION_STATUS_COMPLETE, $DB->get_field('course_completions', 'status',
            array('course' => $course->id, 'userid' => $user->id)));
        $this->assertEquals(1, $DB->count_records('course_completion_crit_compl'));
        $this->assertEquals(1, $DB->count_records('course_modules_completion'));
        $this->assertEquals(1, $DB->count_records('scorm_scoes_track'));
        archive_course_completion($user->id, $course->id);
        $this->assertEquals(0, $DB->count_records('course_completions'));
        archive_course_activities($user->id, $course->id);
        $this->assertEquals(0, $DB->count_records('course_completions'));
        $this->assertEquals(0, $DB->count_records('course_completion_crit_compl'));
        $this->assertEquals(0, $DB->count_records('course_modules_completion'));
        $this->assertEquals(0, $DB->count_records('scorm_scoes_track'));
    }
}
