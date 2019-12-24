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
 * @author David Curry <david.curry@totaralms.com>
 * @package totara
 * @subpackage totaracore
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

global $CFG;
require_once($CFG->dirroot . '/completion/criteria/completion_criteria.php');
require_once($CFG->dirroot . '/completion/criteria/completion_criteria_activity.php');

class totara_core_completion_testcase extends advanced_testcase {
    protected $users, $courses, $modules, $now;



    protected function tearDown() {
        $this->users = null;
        parent::tearDown();
    }

    protected function setUp() {
        global $DB;

        parent::setUp();

        $this->resetAfterTest();

        set_config('enablecompletion', 1);

        $this->now = time();

        // Create test users.
        $this->users['rpl'] = $this->getDataGenerator()->create_user();
        $this->users['man'] = $this->getDataGenerator()->create_user();

        // Create two courses, one is the test course and the other is the control.
        $record = new stdClass();
        $record->enablecompletion = 1;
        $this->courses['test'] = $this->getDataGenerator()->create_course($record);
        $this->courses['cont'] = $this->getDataGenerator()->create_course($record);

        // Add test modules to both courses.
        $record = new stdClass();
        $record->completion = COMPLETION_TRACKING_AUTOMATIC;
        $record->completionview = 1; // These modules must be viewed to be marked as complete.
        $record->course = $this->courses['test']->id;
        $this->modules['test1'] = $this->getDataGenerator()->create_module('choice', $record);
        $this->modules['test2'] = $this->getDataGenerator()->create_module('choice', $record);
        $record->course = $this->courses['cont']->id;
        $this->modules['cont1'] = $this->getDataGenerator()->create_module('choice', $record);
        $this->modules['cont2'] = $this->getDataGenerator()->create_module('choice', $record);

        // Set up the course completion criteria.
        $criterion = new completion_criteria_activity();
        $data = new stdClass();
        $data->activity_aggregation = COMPLETION_AGGREGATION_ALL;
        $data->criteria_activity_value = array($this->modules['test1']->cmid => 1, $this->modules['test2']->cmid => 1);
        $data->id = $this->courses['test']->id;
        $criterion->update_config($data);
        $data->criteria_activity_value = array($this->modules['cont1']->cmid => 1, $this->modules['cont2']->cmid => 1);
        $data->id = $this->courses['cont']->id;
        $criterion->update_config($data);

        // Course completion criteria aggregation methods.
        foreach ($this->courses as $course) {
            $aggdata = array(
                'course'        => $course->id,
                'criteriatype'  => null
            );
            $aggregation = new completion_aggregation($aggdata);
            $aggregation->setMethod(COMPLETION_AGGREGATION_ALL);
            $aggregation->save();

            $aggdata['criteriatype'] = COMPLETION_CRITERIA_TYPE_ACTIVITY;
            $aggregation = new completion_aggregation($aggdata);
            $aggregation->setMethod(COMPLETION_AGGREGATION_ALL);
            $aggregation->save();
        }

        // Assign users to the courses.
        $this->getDataGenerator()->enrol_user($this->users['man']->id, $this->courses['test']->id);
        $this->getDataGenerator()->enrol_user($this->users['rpl']->id, $this->courses['test']->id);
        $this->getDataGenerator()->enrol_user($this->users['man']->id, $this->courses['cont']->id);
        $this->getDataGenerator()->enrol_user($this->users['rpl']->id, $this->courses['cont']->id);

        // Mark the RPL control user as complete by hacking the record (which is basically what completion upload does).
        $completion = $DB->get_record('course_completions',
            array('userid' => $this->users['rpl']->id, 'course' => $this->courses['cont']->id));
        $completion->timeenrolled = $this->now;
        $completion->timestarted = $this->now;
        $completion->timecompleted = $this->now;
        $completion->rpl = 'ripple';
        $completion->status = COMPLETION_STATUS_COMPLETEVIARPL;
        $DB->update_record('course_completions', $completion);

        // Mark the manual control user as complete by completing both modules.
        $completioninfo = new completion_info($this->courses['cont']);
        $coursemodules = get_coursemodules_in_course('choice', $this->courses['cont']->id);
        foreach ($coursemodules as $cm) {
            $completioninfo->set_module_viewed($cm, $this->users['man']->id);
        }

        // Do not assert in setUp!
    }

    private function check_control() {
        global $DB;

        // Check the course completion records.
        $this->assertEquals(2, $DB->count_records('course_completions', array('course' => $this->courses['cont']->id)));

        $record = $DB->get_record('course_completions',
            array('course' => $this->courses['cont']->id, 'userid' => $this->users['rpl']->id));
        $this->assertEquals('ripple', $record->rpl);
        $this->assertGreaterThanOrEqual($this->now, $record->timecompleted);

        $record = $DB->get_record('course_completions',
            array('course' => $this->courses['cont']->id, 'userid' => $this->users['man']->id));
        $this->assertEquals('', $record->rpl);
        $this->assertGreaterThanOrEqual($this->now, $record->timecompleted);

        // Check the course module completion records.
        $records = $DB->get_records('course_completion_criteria', array('course' => $this->courses['cont']->id));
        $this->assertEquals(2, count($records));
        foreach ($records as $record) {
            $this->assertEquals(1, $DB->count_records('course_modules_completion',
                array('coursemoduleid' => $record->moduleinstance))); // Moduleinstance contains a coursemoduleid!
            $this->assertEquals(1, $DB->count_records('course_modules_completion',
                array('coursemoduleid' => $record->moduleinstance, 'userid' => $this->users['man']->id)));
        }

        // Check the module criteria completion records (none for RPL user because they just marked course complete).
        $this->assertEquals(2, $DB->count_records('course_completion_crit_compl', array('course' => $this->courses['cont']->id)));

        $records = $DB->get_records('course_completion_crit_compl', array('course' => $this->courses['cont']->id));
        foreach ($records as $record) {
            $this->assertEquals($record->userid, $this->users['man']->id);
            $this->assertGreaterThanOrEqual(0, $record->timecompleted);
        }
    }

    public function test_set_up() {
        // Verify that the control has been set up correctly.
        $this->check_control();
    }

    /**
     * Check that completion records enrolment date comes from the enrolment record after a reset and not time().
     */
    public function test_enrolmentdate_reset() {
        global $DB;

        // Turn on completion starts on enrolment for the test course.
        $updatesql = "UPDATE {course} SET completionstartonenrol = 1 where id = ?";
        $DB->execute($updatesql, array($this->courses['test']->id));

        // Get the expected times.
        $sql = "SELECT userid, MIN(timecreated) AS mindate
                  FROM {user_enrolments}
              GROUP BY userid";
        $timeenrolled = $DB->get_records_sql($sql);

        // Reset the completions and restart the users.
        $completion = new completion_info($this->courses['test']);
        $completion->delete_course_completion_data();
        completion_start_user_bulk($this->courses['test']->id);

        // Compare the generated dates with the expected ones.
        $resetcompletions = $DB->get_records('course_completions',
            array('course' => $this->courses['test']->id), '', 'userid, timeenrolled');
        $this->assertEquals($timeenrolled[$this->users['man']->id]->mindate,
            $resetcompletions[$this->users['man']->id]->timeenrolled);
        $this->assertEquals($timeenrolled[$this->users['rpl']->id]->mindate,
            $resetcompletions[$this->users['rpl']->id]->timeenrolled);

        // Now set a start date for the user enrolments (it should use the startdate instead of timecreated).
        $timestart = "1234567890";
        $updatesql = "UPDATE {user_enrolments} SET timestart = {$timestart}";
        $DB->execute($updatesql);

        // Reset the completions again.
        $completion = new completion_info($this->courses['test']);
        $completion->delete_course_completion_data();
        completion_start_user_bulk($this->courses['test']->id);

        // Compare the generated dates with the expected ones.
        $resetcompletions = $DB->get_records('course_completions',
            array('course' => $this->courses['test']->id), '', 'userid, timeenrolled');
        $this->assertEquals($timestart, $resetcompletions[$this->users['man']->id]->timeenrolled);
        $this->assertEquals($timestart, $resetcompletions[$this->users['rpl']->id]->timeenrolled);

        $this->check_control();
    }

    /**
     * This tests keeping RPL course completion data and removing non-RPL course completion data when an activity is reset.
     */
    public function test_modupdate_rpl() {
        global $DB;

        // Make sure the course completion records were created when the user was enrolled.
        $this->assertEquals(2, $DB->count_records('course_completions',
            array('course' => $this->courses['test']->id)));

        // We havent made any activity completion records yet.
        $this->assertEquals(0, $DB->count_records('course_completion_crit_compl',
            array('course' => $this->courses['test']->id)));

        // There should be no course module completion records either.
        $records = $DB->get_records('course_completion_criteria', array('course' => $this->courses['test']->id));
        $this->assertEquals(2, count($records));
        foreach ($records as $record) {
            $this->assertEquals(0, $DB->count_records('course_modules_completion',
                array('coursemoduleid' => $record->id)));
        }

        // Mark the RPL user as complete by updating the database directly (similar to completion upload).
        $completion = $DB->get_record('course_completions',
            array('userid' => $this->users['rpl']->id, 'course' => $this->courses['test']->id));
        $completion->timeenrolled = $this->now;
        $completion->timestarted = $this->now;
        $completion->timecompleted = $this->now;
        $completion->rpl = 'ripple';
        $completion->status = COMPLETION_STATUS_COMPLETEVIARPL;
        $DB->update_record('course_completions', $completion);

        // Mark the manual user as complete by completing each criteria.
        $completioninfo = new completion_info($this->courses['test']);
        $coursemodules = get_coursemodules_in_course('choice', $this->courses['test']->id);
        foreach ($coursemodules as $cm) {
            $completioninfo->set_module_viewed($cm, $this->users['man']->id);
        }

        // Make sure the users have the correct course completion records.
        $this->assertEquals(2, $DB->count_records('course_completions',
            array('course' => $this->courses['test']->id)));

        $comp = $DB->get_record('course_completions',
            array('course' => $this->courses['test']->id, 'userid' => $this->users['rpl']->id));
        $this->assertEquals('ripple', $comp->rpl);
        $this->assertGreaterThanOrEqual($this->now, $comp->timecompleted);

        $comp = $DB->get_record('course_completions',
            array('course' => $this->courses['test']->id, 'userid' => $this->users['man']->id));
        $this->assertEquals('', $comp->rpl);
        $this->assertGreaterThanOrEqual($this->now, $comp->timecompleted);

        // There should now be 2 criteria completion records.
        $this->assertEquals(2, $DB->count_records('course_completion_crit_compl', array('course' => $this->courses['test']->id)));

        // And two course module completion records as well.
        $records = $DB->get_records('course_completion_criteria', array('course' => $this->courses['test']->id));
        $this->assertEquals(2, count($records));
        foreach ($records as $record) {
            $this->assertEquals(1, $DB->count_records('course_modules_completion',
                array('coursemoduleid' => $record->moduleinstance)));
            $this->assertEquals(1, $DB->count_records('course_modules_completion',
                array('coursemoduleid' => $record->moduleinstance, 'userid' => $this->users['man']->id)));
        }

        // Trigger completion reset with delete for one of the course modules.
        $mod = $DB->get_record('course_modules', array('id' => $this->modules['test1']->cmid));
        $completion = new completion_info($this->courses['test']);
        $completion->reset_all_state($mod);

        // After the reset there should be only one record for the other activity which wasn't removed during the reset.
        $this->assertEquals(1, $DB->count_records('course_completion_crit_compl',
            array('course' => $this->courses['test']->id)));
        $criteria = $DB->get_record('course_completion_criteria', array('moduleinstance' => $this->modules['test2']->cmid));
        $this->assertEquals(1, $DB->count_records('course_completion_crit_compl',
            array('course' => $this->courses['test']->id, 'criteriaid' => $criteria->id)));

        // And a matching course module completion record.
        $records = $DB->get_records('course_completion_criteria', array('course' => $this->courses['test']->id));
        $this->assertEquals(2, count($records));
        foreach ($records as $record) {
            if ($record->moduleinstance == $this->modules['test2']->cmid) {
                $this->assertEquals(1, $DB->count_records('course_modules_completion',
                    array('coursemoduleid' => $record->moduleinstance)));
                $this->assertEquals(1, $DB->count_records('course_modules_completion',
                    array('coursemoduleid' => $record->moduleinstance, 'userid' => $this->users['man']->id)));
            } else {
                $this->assertEquals(0, $DB->count_records('course_modules_completion',
                    array('coursemoduleid' => $record->moduleinstance)));
            }
        }

        // Make sure the users still have course completion records.
        $this->assertEquals(2, $DB->count_records('course_completions', array('course' => $this->courses['test']->id)));

        // Verify the data is unchanged for rpl user.
        $comp = $DB->get_record('course_completions',
            array('course' => $this->courses['test']->id, 'userid' => $this->users['rpl']->id));
        $this->assertEquals('ripple', $comp->rpl);
        $this->assertGreaterThanOrEqual($this->now, $comp->timecompleted);

        // Verify the data has been reset for manual user.
        $comp = $DB->get_record('course_completions',
            array('course' => $this->courses['test']->id, 'userid' => $this->users['man']->id));
        $this->assertEquals('', $comp->rpl);
        $this->assertEquals(null, $comp->timecompleted);

        $this->check_control();
    }

    public function test_historical_deletion() {
        global $DB;

        $histcourse = $this->courses['test'];
        $histuser = $this->users['man'];

        // Generate a few historical completions for the user-course.
        $todb = new stdClass();
        $todb->courseid = $histcourse->id;
        $todb->userid = $histuser->id;

        $times = array('1262304000' => '75', '1325376000' => '60', '1388534400' => '75');
        foreach ($times as $time => $grade) {
            $todb->timecompleted = $time;
            $todb->grade = $grade;

            $DB->insert_record('course_completion_history', $todb);
        }

        // And one control historical completion.
        $contcourse = $this->courses['cont'];
        $todb->courseid = $contcourse->id;
        $DB->insert_record('course_completion_history', $todb);

        $this->assertEquals(4, $DB->count_records('course_completion_history'));

        // Now delete the course.
        delete_course($histcourse, false);

        // Then check the records are gone.
        $histrecords = $DB->get_records('course_completion_history');

        $this->assertEquals(1, count($histrecords));

        // And any remaining records are for the control course.
        foreach ($histrecords as $histrecord) {
            $this->assertEquals($contcourse->id, $histrecord->courseid);
        }

        $this->check_control();
    }

}
