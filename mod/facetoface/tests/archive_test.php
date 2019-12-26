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
 * facetoface module PHPUnit archive test class
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit mod_facetoface_archive_testcase mod/facetoface/tests/archive_test.php
 *
 * @package    mod_facetoface
 * @subpackage phpunit
 * @author     Nathan Lewis <nathan.lewis@totaralms.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 *
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/completion/criteria/completion_criteria_activity.php');

use \mod_facetoface\signup;
use \mod_facetoface\signup_helper;
use \mod_facetoface\signup\state\fully_attended;

class mod_facetoface_archive_testcase extends advanced_testcase {
    /**
     * Is archive completion supported?
     */
    public function test_module_supports_archive_completion() {
        $this->assertTrue(facetoface_supports(FEATURE_ARCHIVE_COMPLETION));
    }

    public function test_archive() {
        global $DB, $CFG;

        $this->resetAfterTest(true);

        // Ensure completion is enabled sitewide.
        $CFG->enablecompletion = true;

        // Create a course.
        $this->assertEquals(1, $DB->count_records('course')); // Site course.
        $coursedefaults = array('enablecompletion' => COMPLETION_ENABLED);
        $course = $this->getDataGenerator()->create_course($coursedefaults);
        $this->assertEquals(2, $DB->count_records('course')); // Site course + this course.

        // Check it has course competion.
        $completioninfo = new completion_info($course);
        $this->assertEquals(COMPLETION_ENABLED, $completioninfo->is_enabled());

        // Create a facetoface and add it to the course.
        $this->assertEquals(0, $DB->count_records('facetoface'));
        $completiondefaults = array(
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionstatusrequired' => '{"100":1}'
        );
        $facetoface = $this->getDataGenerator()->create_module(
                'facetoface',
                array('course' => $course->id, 'completionsubmit' => 1), // User must submit facetoface for it to complete.
                $completiondefaults);
        $this->assertEquals(1, $DB->count_records('facetoface'));

        // Create completion criteria based on the facetoface activity.
        // This is usually done as part of form saving, so has to be reproduced here.
        $this->assertEquals(0, $DB->count_records('course_completion_criteria'));
        $this->assertEquals(0, $DB->count_records('course_completion_aggr_methd'));
        $data = new stdClass();
        $data->id = $course->id;
        $data->overall_aggregation = COMPLETION_AGGREGATION_ANY;
        $data->criteria_activity_value = array($facetoface->cmid => 1);
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

        // Create two users.
        $this->assertEquals(2, $DB->count_records('user')); // Guest + Admin.
        $user = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();
        $this->assertEquals(4, $DB->count_records('user')); // Guest + Admin + test users.

        // Create a session date in the future in order to signup then move it back in time, future sessions should not be archived.
        $sessdate = new stdClass();
        $sessdate->timestart = time() + (1 * HOURSECS);
        $sessdate->timefinish = $sessdate->timestart + (8 * HOURSECS);
        $sessdate->sessiontimezone = 'Pacific/Auckland';

        // Create a facetoface session.
        $this->assertEquals(0, $DB->count_records('facetoface_sessions'));
        $session = new stdClass();
        $session->facetoface = $facetoface->id;
        $session->capacity = 10;
        $session->allowoverbook = 0;
        $session->normalcost = 0;
        $session->discountcost = 0;
        $session->usermodified = $manager->id;
        $session->waitlisteveryone = 0;

        $seminarevent = new \mod_facetoface\seminar_event();
        $seminarevent->from_record($session);
        $seminarevent->save();
        facetoface_save_dates($seminarevent->to_record(), array($sessdate));

        $sessid = $seminarevent->get_id();
        $session = facetoface_get_session($sessid); // Reload to get the correct dates + id;
        $this->assertEquals(1, $DB->count_records('facetoface_sessions'));

        // Enrol user on course.
        $this->assertTrue($this->getDataGenerator()->enrol_user($user->id, $course->id));

        // Get the course module.
        $course_module = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id);
        $this->assertEquals(COMPLETION_TRACKING_AUTOMATIC, $completioninfo->is_enabled($course_module));

        // Check it isn't complete.
        $params = array('userid' => $user->id, 'coursemoduleid' => $course_module->id);
        $completionstate = $DB->get_field('course_modules_completion', 'completionstate', $params);
        $this->assertEmpty($completionstate);

        // Signup the user to the session.
        $this->assertEquals(0, $DB->count_records('facetoface_signups'));
        \mod_facetoface\signup_helper::signup(\mod_facetoface\signup::create($user->id, new \mod_facetoface\seminar_event($session->id))->set_skipusernotification());
        $this->assertEquals(1, $DB->count_records('facetoface_signups'));

        $sessdate->timestart = time() - (7 * DAYSECS);
        $sessdate->timefinish = $sessdate->timestart + (8 * HOURSECS);
        facetoface_save_dates($seminarevent->get_id(), array($sessdate));

        // Trigger the completion - manager marks signup fully attended.
        $this->assertEquals(1, $DB->count_records('facetoface_signups_status'));
        $this->assertEquals(1, $DB->count_records('facetoface_signups_status', array('superceded' => 0)));

        $this->assertEquals(\mod_facetoface\signup\state\booked::get_code(), $DB->get_field('facetoface_signups_status', 'statuscode', array('superceded' => 0)));

        $signup = $DB->get_record('facetoface_signups', array());
        \mod_facetoface\signup_helper::process_attendance($seminarevent, [$signup->id => fully_attended::get_code()]);

        $this->assertEquals(2, $DB->count_records('facetoface_signups_status'));
        $this->assertEquals(1, $DB->count_records('facetoface_signups_status', array('superceded' => 0)));
        $this->assertEquals(\mod_facetoface\signup\state\fully_attended::get_code(), $DB->get_field('facetoface_signups_status', 'statuscode', array('superceded' => 0)));

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
        //$this->assertEquals(1, $DB->count_records('scorm_scoes_track')); // Check that f2f records exist.
        archive_course_completion($user->id, $course->id);
        $this->assertEquals(0, $DB->count_records('course_completions'));
        archive_course_activities($user->id, $course->id);
        $this->assertEquals(0, $DB->count_records('course_completions'));
        $this->assertEquals(0, $DB->count_records('course_completion_crit_compl'));
        $this->assertEquals(0, $DB->count_records('course_modules_completion'));
        //$this->assertEquals(0, $DB->count_records('scorm_scoes_track')); // And then are gone (if appropriate).
    }
}
