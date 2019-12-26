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
 * @author Maria Torres <maria.torres@totaralearning.com>
 * @package totara
 * @subpackage totaracore
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

class totara_core_dialog_load_courses_testcase extends advanced_testcase {

    public function test_courses_dialog_load_courses() {
        global $CFG;
        require_once($CFG->dirroot . '/totara/core/dialogs/dialog_content_courses.class.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_activity.php');

        $this->resetAfterTest(true);

        // Ensure completion is enabled sitewide.
        $CFG->enablecompletion = true;

        $generator = $this->getDataGenerator();
        $category = $generator->create_category();

        $course1 = $generator->create_course(array(
            'category' => $category->id,
            'enablecompletion' => COMPLETION_DISABLED,
            'completionstartonenrol' => 1,
            'completionprogressonview' => 0
        ));

        $course2 = $generator->create_course(array(
            'category' => $category->id,
            'enablecompletion' => COMPLETION_ENABLED,
            'completionstartonenrol' => 1,
            'completionprogressonview' => 1
        ));

        $course3 = $generator->create_course(array(
            'category' => $category->id,
            'enablecompletion' => COMPLETION_ENABLED,
            'completionstartonenrol' => 1,
            'completionprogressonview' => 1
        ));

        $course4 = $generator->create_course(array(
            'category' => $category->id,
            'enablecompletion' => COMPLETION_ENABLED,
            'completionstartonenrol' => 1,
            'completionprogressonview' => 1
        ));

        $course5 = $generator->create_course(array(
            'category' => $category->id,
            'enablecompletion' => COMPLETION_ENABLED,
            'completionstartonenrol' => 1,
            'completionprogressonview' => 1
        ));

        $user = $generator->create_user();

        // Add an activity and course completion criteria to course 2.
        $facetoface = $this->getDataGenerator()->create_module(
            'facetoface',
            array('course' => $course2->id, 'completionsubmit' => 1), // User must submit facetoface for it to complete.
            array(
                'completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completionstatusrequired' => '{"100":1}'
            )
        );

        $data = new stdClass();
        $data->id = $course2->id;
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

        // Enrol user in courses: 1,2,3 and 4
        $generator->enrol_user($user->id, $course1->id);
        $generator->enrol_user($user->id, $course2->id);
        $generator->enrol_user($user->id, $course3->id);
        $generator->enrol_user($user->id, $course4->id);

        // Complete course3 via RPL.
        $completionrpl = new completion_completion(array('userid' => $user->id, 'course' => $course3->id));
        $completionrpl->rpl = 'Course completed via rpl';
        $completionrpl->status = COMPLETION_STATUS_COMPLETEVIARPL;
        $completionrpl->mark_complete();

        // At this point.
        // There are 5 courses 1,2,3,4,5.
        // Course 1 does not have completion enabled, the others do.
        // The user has enrolled in courses 1,2,3,4
        // Course 2 has completion criteria.
        // The user has completed course 3 via RPL.
        $dialog = new totara_dialog_content_courses($category->id);
        $dialog->requirecompletioncriteria = false;
        $dialog->requirecompletion = false;
        $dialog->load_courses();
        $this->assertCount(5, $dialog->courses);

        $dialog->requirecompletioncriteria = true;
        $dialog->requirecompletion = false;
        $dialog->load_courses();
        $this->assertCount(1, $dialog->courses);

        $dialog->requirecompletioncriteria = false;
        $dialog->requirecompletion = true;
        $dialog->load_courses();
        $this->assertCount(4, $dialog->courses);

        $dialog->requirecompletioncriteria = true;
        $dialog->requirecompletion = true;
        $dialog->load_courses();
        $this->assertCount(1, $dialog->courses);
    }

}
