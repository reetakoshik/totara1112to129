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
 * @package core_completion
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/completion/completion_completion.php');
require_once($CFG->dirroot . '/completion/criteria/completion_criteria_activity.php');

/**
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose core_completion_completion_logging_testcase completion/tests/completion_logging_test.php
 */
class core_completion_completion_logging_testcase extends advanced_testcase {

    public function test_completion_completion_save() {
        global $DB, $USER;

        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => COMPLETION_ENABLED));

        // First test that "created" logs are created.
        $this->assertEquals(0, $DB->count_records('course_completion_log'));

        // _save is called from within aggregate, which is called when enrolling a user in a course.
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $logs = $DB->get_records('course_completion_log');
        $this->assertCount(1,$logs);
        $log = reset($logs);

        $this->assertEquals($user->id, $log->userid);
        $this->assertEquals($course->id, $log->courseid);
        $this->assertEquals($USER->id, $log->changeuserid);
        $this->assertContains('Created current completion in completion_completion->_save', $log->description);
        $this->assertContains('Status', $log->description);

        // Then test that "updated" logs are created.
        $DB->delete_records('course_completion_log');

        // _save is called from within mark_complete. The record already exists (was just created above).
        $cc = new completion_completion( array('userid' => $user->id, 'course' => $course->id));
        $cc->mark_complete(time());

        $logs = $DB->get_records('course_completion_log');
        $this->assertCount(1,$logs);
        $log = reset($logs);

        $this->assertEquals($user->id, $log->userid);
        $this->assertEquals($course->id, $log->courseid);
        $this->assertEquals($USER->id, $log->changeuserid);
        $this->assertContains('Updated current completion in completion_completion->_save', $log->description);
        $this->assertContains('Status', $log->description);
    }

    public function test_archive_course_activities() {
        global $DB, $USER;

        $this->resetAfterTest(true);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => COMPLETION_ENABLED));

        // We need to set up a module in the course.
        $moduleinstance = $this->getDataGenerator()->create_module(
            'certificate',
            array('course' => $course->id),
            array(
                'completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completionview' => COMPLETION_VIEW_REQUIRED
            )
        );

        $data = new stdClass();
        $data->course = $course->id;
        $data->id = $moduleinstance->id;
        $data->overall_aggregation = COMPLETION_AGGREGATION_ANY;
        $data->criteria_activity_value = array($moduleinstance->id => 1);
        $criterion = new completion_criteria_activity();
        $criterion->update_config($data);

        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);

        // Now we need to generate a module completion record for the users.
        $coursemodule = get_coursemodule_from_instance('certificate', $moduleinstance->id, $course->id);
        $completioninfo = new completion_info($course);
        $completioninfo->set_module_viewed($coursemodule, $user1->id);
        $cmcid = $DB->get_field('course_modules_completion', 'id',
            array('coursemoduleid' => $coursemodule->id, 'userid' => $user1->id));

        // Clear out any logs that might have been created above.
        $DB->delete_records('course_completion_log');

        // Run the function.
        archive_course_activities($user1->id, $course->id);

        $logs = $DB->get_records('course_completion_log');
        $this->assertCount(1,$logs);
        $log = reset($logs);

        $this->assertEquals($user1->id, $log->userid);
        $this->assertEquals($course->id, $log->courseid);
        $this->assertEquals($USER->id, $log->changeuserid);
        $this->assertContains('Deleted module completion in archive_course_activities', $log->description);
        $this->assertContains((string)$cmcid, $log->description);
    }

    public function test_archive_course_completion() {
        global $DB, $USER;

        $this->resetAfterTest(true);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => COMPLETION_ENABLED));

        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);

        $cc = new completion_completion( array('userid' => $user1->id, 'course' => $course->id));
        $cc->mark_complete(time());

        $cc = new completion_completion( array('userid' => $user2->id, 'course' => $course->id));
        $cc->mark_complete(time());

        // Clear out any logs that might have been created above.
        $DB->delete_records('course_completion_log');

        // Run the function.
        archive_course_completion($user1->id, $course->id);

        $logs = $DB->get_records('course_completion_log', array(), 'id');
        $this->assertCount(2,$logs);
        $log1 = reset($logs);
        $log2 = next($logs);

        $this->assertEquals($user1->id, $log1->userid);
        $this->assertEquals($course->id, $log1->courseid);
        $this->assertEquals($USER->id, $log1->changeuserid);
        $this->assertContains('History created in archive_course_completion', $log1->description);

        $this->assertEquals($user1->id, $log2->userid);
        $this->assertEquals($course->id, $log2->courseid);
        $this->assertEquals($USER->id, $log2->changeuserid);
        $this->assertContains('Deleted current completion and all crit compl records in delete_course_completion_data', $log2->description);
    }
}
