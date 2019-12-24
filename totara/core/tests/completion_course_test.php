<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

class totara_core_completion_course_testcase extends advanced_testcase {
    public function test_activity_completion_any() {
        global $CFG;
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_activity.php');
        require_once($CFG->dirroot . '/mod/assign/externallib.php');

        $this->resetAfterTest();
        set_config('enablecompletion', 1);

        $this->assertTrue(empty($CFG->completionexcludefailures));

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $module = $this->getDataGenerator()->create_module('assign', array('completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionusegrade' => 1, 'course' => $course->id, 'gradepass' => 50));

        $criterion = new completion_criteria_activity();
        $data = new stdClass();
        $data->activity_aggregation = COMPLETION_AGGREGATION_ALL;
        $data->criteria_activity_value = array($module->cmid => 1);
        $data->id = $course->id;
        $criterion->update_config($data);

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

        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);

        $this->setAdminUser();
        $completion = new completion_info($course);
        $this->assertFalse($completion->is_course_complete($user1->id));
        $this->assertFalse($completion->is_course_complete($user2->id));

        mod_assign_external::save_grade($module->id, $user1->id, 40.0, -1, true, 'released', false, array('assignfeedbackcomments_editor' => array('text' => 'abc', 'format' => FORMAT_HTML), 'files_filemanager' => -1));
        mod_assign_external::save_grade($module->id, $user2->id, 60.0, -1, true, 'released', false, array('assignfeedbackcomments_editor' => array('text' => 'abc', 'format' => FORMAT_HTML), 'files_filemanager' => -1));

        $this->assertTrue($completion->is_course_complete($user1->id));
        $this->assertTrue($completion->is_course_complete($user2->id));
    }

    public function test_activity_completion_not_failed() {
        global $CFG;
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_activity.php');
        require_once($CFG->dirroot . '/mod/assign/externallib.php');

        $this->resetAfterTest();
        set_config('enablecompletion', 1);

        $CFG->completionexcludefailures = 1;

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $module = $this->getDataGenerator()->create_module('assign', array('completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionusegrade' => 1, 'course' => $course->id, 'gradepass' => 50));

        $criterion = new completion_criteria_activity();
        $data = new stdClass();
        $data->activity_aggregation = COMPLETION_AGGREGATION_ALL;
        $data->criteria_activity_value = array($module->cmid => 1);
        $data->id = $course->id;
        $criterion->update_config($data);

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

        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);

        $this->setAdminUser();
        $completion = new completion_info($course);
        $this->assertFalse($completion->is_course_complete($user1->id));
        $this->assertFalse($completion->is_course_complete($user2->id));

        mod_assign_external::save_grade($module->id, $user1->id, 40.0, -1, true, 'released', false, array('assignfeedbackcomments_editor' => array('text' => 'abc', 'format' => FORMAT_HTML), 'files_filemanager' => -1));
        mod_assign_external::save_grade($module->id, $user2->id, 60.0, -1, true, 'released', false, array('assignfeedbackcomments_editor' => array('text' => 'abc', 'format' => FORMAT_HTML), 'files_filemanager' => -1));

        $this->assertFalse($completion->is_course_complete($user1->id));
        $this->assertTrue($completion->is_course_complete($user2->id));
    }
}