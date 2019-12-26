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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package core_course
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

class core_course_user_learning_item_testcase extends advanced_testcase {

    private $generator;
    private $completion_generator;
    private $course1, $course2, $course3, $course4, $course5, $course6;
    private $user1;

    protected function tearDown() {
        $this->generator = null;
        $this->completion_generator = null;
        $this->course1 = $this->course2 = $this->course3 = $this->course4 = $this->course5 = $this->course6 = null;
        $this->user1 = null;
        parent::tearDown();
    }

    public function setUp() {
        global $DB;

        $this->resetAfterTest(true);
        parent::setUp();

        $this->generator = $this->getDataGenerator();
        $this->completion_generator = $this->getDataGenerator()->get_plugin_generator('core_completion');

        // Create some course.
        $this->course1 = $this->generator->create_course(array('shortname' => 'c1','fullname' => 'Course 1'));
        $this->course2 = $this->generator->create_course(array('shortname' => 'c2','fullname' => 'Course 2'));
        $this->course3 = $this->generator->create_course(array('shortname' => 'c3','fullname' => 'Course 3'));
        $this->course4 = $this->generator->create_course(array('shortname' => 'c4','fullname' => 'Course 4'));
        $this->course5 = $this->generator->create_course(array('shortname' => 'c5','fullname' => 'Course 5'));
        $this->course6 = $this->generator->create_course(array('shortname' => 'c6','fullname' => 'Course 6'));

        // Reload courses to get accurate data.
        // See note in totara/program/tests/program_content_test.php for more info.
        $this->course1 = $DB->get_record('course', array('id' => $this->course1->id));
        $this->course2 = $DB->get_record('course', array('id' => $this->course2->id));
        $this->course3 = $DB->get_record('course', array('id' => $this->course3->id));
        $this->course4 = $DB->get_record('course', array('id' => $this->course4->id));
        $this->course5 = $DB->get_record('course', array('id' => $this->course5->id));
        $this->course6 = $DB->get_record('course', array('id' => $this->course6->id));

        // Enable completion for courses.
        $this->completion_generator->enable_completion_tracking($this->course1);
        $this->completion_generator->enable_completion_tracking($this->course2);
        $this->completion_generator->enable_completion_tracking($this->course3);
        $this->completion_generator->enable_completion_tracking($this->course4);
        $this->completion_generator->enable_completion_tracking($this->course5);
        $this->completion_generator->enable_completion_tracking($this->course6);

        // Add criteria for some courses
        $this->user1 = $this->generator->create_user(array('fullname' => 'user1'));
    }

    public function test_all_courses() {
        // Enrolled user into three courses.
        $this->generator->enrol_user($this->user1->id, $this->course1->id);
        $this->generator->enrol_user($this->user1->id, $this->course2->id);
        $this->generator->enrol_user($this->user1->id, $this->course3->id);

        // Get the users learning items.
        $learning_items = \core_course\user_learning\item::all($this->user1->id);

        // Ensure we get the right number of courses.
        $this->assertCount(3, $learning_items);

        $results = array();
        foreach ($learning_items as $item) {
            $results[$item->shortname] = $item->fullname;
        }

        $this->assertEquals('Course 1', $results['c1']);
        $this->assertEquals('Course 2', $results['c2']);
        $this->assertEquals('Course 3', $results['c3']);
    }

    public function test_one_course() {
        // Enrolled user into three courses.
        $this->generator->enrol_user($this->user1->id, $this->course1->id);

        // Get the users learning items.
        $learning_items = \core_course\user_learning\item::one($this->user1->id, $this->course1->id);

        // Ensure we get the correct course
        $this->assertEquals('Course 1', $learning_items->fullname);
    }

    public function test_all_course_future_enrol() {
        // Enrolled user with a future start date.
        $this->generator->enrol_user($this->user1->id, $this->course1->id, null, 'manual', time() + 604800);

        // Get the users learning items.
        $learning_items = \core_course\user_learning\item::all($this->user1->id);

        // Ensure we get the right number of courses.
        $this->assertCount(0, $learning_items);
    }

    public function test_all_course_past_enrol() {
        // Enrolled user where enrolment end date has past.
        $this->generator->enrol_user($this->user1->id, $this->course1->id, null, 'manual', time() - 864000, time() - 604800);

        // Get the users learning items.
        $learning_items = \core_course\user_learning\item::all($this->user1->id);

        // Ensure we get the right number of courses.
        $this->assertCount(0, $learning_items);
    }

    public function test_all_course_suspended_enrol() {
        // Enrolment suspended
        $this->generator->enrol_user($this->user1->id, $this->course1->id, null, 'manual', 0, 0, ENROL_USER_SUSPENDED);

        // Get the users learning items.
        $learning_items = \core_course\user_learning\item::all($this->user1->id);

        // Ensure we get the right number of courses.
        $this->assertCount(0, $learning_items);
    }

    public function test_ensure_completion_loaded() {
        global $CFG;

        // Enrolled user to a course.
        $this->generator->enrol_user($this->user1->id, $this->course1->id);

        // Get the users learning items.
        $learning_items = \core_course\user_learning\item::one($this->user1->id, $this->course1->id);

        $progress_canbecompleted = new ReflectionProperty('core_course\user_learning\item', 'progress_canbecompleted');
        $progress_canbecompleted->setAccessible(true);

        $progress_hascompletioncriteria = new ReflectionProperty('core_course\user_learning\item', 'progress_hascompletioncriteria');
        $progress_hascompletioncriteria->setAccessible(true);

        $progress_percentage = new ReflectionProperty('core_course\user_learning\item', 'progress_percentage');
        $progress_percentage->setAccessible(true);

        $progress_summary = new ReflectionProperty('core_course\user_learning\item', 'progress_summary');
        $progress_summary->setAccessible(true);

        // Check they are all empty.
        $this->assertEmpty($progress_canbecompleted->getValue($learning_items));
        $this->assertEmpty($progress_hascompletioncriteria->getValue($learning_items));
        $this->assertEmpty($progress_percentage->getValue($learning_items));
        $this->assertEmpty($progress_summary->getValue($learning_items));

        // Lets turn on completion and try again.
        $CFG->enablecompletion = true;

        $rm = new ReflectionMethod('core_course\user_learning\item', 'ensure_completion_loaded');
        $rm->setAccessible(true);

        $rm->invoke($learning_items);

        // We should have some values this time (even if there is no progress).
        $this->assertTrue($progress_canbecompleted->getValue($learning_items));
        $this->assertFalse($progress_hascompletioncriteria->getValue($learning_items));
        $this->assertEquals(0, $progress_percentage->getValue($learning_items));
        $this->assertEquals('No criteria', $progress_summary->getValue($learning_items));
    }

    public function test_export_for_template() {

        // Enrolled user to a course.
        $this->generator->enrol_user($this->user1->id, $this->course1->id);

        // Get the users learning items.
        $learning_items = \core_course\user_learning\item::one($this->user1->id, $this->course1->id);

        $info = $learning_items->export_for_template();

        $this->assertEquals($this->course1->id, $info->id);
        $this->assertEquals($this->course1->fullname, $info->fullname);
    }

    public function test_get_component() {
        // Enrolled user to a course.
        $this->generator->enrol_user($this->user1->id, $this->course1->id);

        // Get the users learning items.
        $learning_items = \core_course\user_learning\item::one($this->user1->id, $this->course1->id);

        // Test component name.
        $this->assertEquals('core_course', $learning_items->get_component());
    }

    public function test_get_type() {
        // Enrolled user to a course.
        $this->generator->enrol_user($this->user1->id, $this->course1->id);

        // Get the users learning items.
        $learning_items = \core_course\user_learning\item::one($this->user1->id, $this->course1->id);

        // Test type.
        $this->assertEquals('course', $learning_items->get_type());
    }


    function test_validateExportInfo () {
        $testobj = new stdClass();
        $testobj->progress = new stdClass();
        $testobj->progress->pbar = array(
            'popover' => array (
                'contenttemplatecontext' => array (
                    'hascoursecriteria' => true,
                    'aggregation' => '<strong>All</strong> of the following criteria need to be met to complete this course',
                    'criteria' => array('You must be marked as complete by <strong>All</strong> of the following roles: Trainer, Editing Trainer'),
                )
            )
        );

        $this->validateExportInfo($testobj, 'All',
            array('You must be marked as complete by <strong>All</strong> of the following roles: Editing Trainer, Trainer'));

        $testobj2 = new stdClass();
        $testobj2->progress = new stdClass();
        $testobj2->progress->pbar = array(
            'popover' => array (
                'contenttemplatecontext' => array (
                    'hascoursecriteria' => true,
                    'aggregation' => '<strong>All</strong> of the following criteria need to be met to complete this course',
                    'criteria' => array(
                        '2 other courses need to be completed',
                        'You must be marked as complete by <strong>All</strong> of the following roles: Trainer, Editing Trainer',
                        'One activity needs to be completed'),
                )
            )
        );

        $this->validateExportInfo($testobj2, 'All',
            array('One activity needs to be completed',
                  'You must be marked as complete by <strong>All</strong> of the following roles: Editing Trainer, Trainer',
                  '2 other courses need to be completed'));
    }

    public function test_export_for_template_one_activity() {
        global $DB;

        $course = $this->generator->create_course(array('shortname' => 'cc1','fullname' => 'Completion Course 1', 'enablecompletion' => 1));
        $course = $DB->get_record('course', array('id' => $this->course6->id));
        $data = $this->generator->create_module('data', array('course' => $course->id), array('completion' => 1));

        $this->completion_generator->enable_completion_tracking($course);
        $this->completion_generator->set_activity_completion($course->id, array($data));

        $this->generator->enrol_user($this->user1->id, $course->id);

        $learning_items = \core_course\user_learning\item::one($this->user1->id, $course->id);
        $info = $learning_items->export_for_template();

        $this->validateExportInfo($info, 'All', array('One activity needs to be completed'));
    }

    public function test_export_for_template_multi_activities() {
        global $DB;

        $course = $this->generator->create_course(array('shortname' => 'cc1','fullname' => 'Completion Course 1', 'enablecompletion' => 1));
        $course = $DB->get_record('course', array('id' => $this->course6->id));
        $data = $this->generator->create_module('data', array('course' => $course->id), array('completion' => 1));
        $forum = $this->generator->create_module('forum',  array('course' => $course->id), array('completion' => 1));
        $assign = $this->generator->create_module('assign',  array('course' => $course->id));

        $this->completion_generator->enable_completion_tracking($course);
        $this->completion_generator->set_aggregation_method($course->id, null, COMPLETION_AGGREGATION_ANY);
        $this->completion_generator->set_activity_completion($course->id, array($data, $forum, $assign));

        $this->generator->enrol_user($this->user1->id, $course->id);

        $learning_items = \core_course\user_learning\item::one($this->user1->id, $course->id);
        $info = $learning_items->export_for_template();

        $this->validateExportInfo($info, 'Any', array('3 activities need to be completed'));
    }

    public function test_export_for_template_one_other_course() {
        global $DB;

        $course = $this->generator->create_course(array('shortname' => 'cc1','fullname' => 'Completion Course 1', 'enablecompletion' => 1));
        $course = $DB->get_record('course', array('id' => $this->course6->id));

        $this->completion_generator->enable_completion_tracking($course);
        $this->completion_generator->set_course_criteria_course_completion($course, array($this->course2->id), COMPLETION_AGGREGATION_ALL);

        $this->generator->enrol_user($this->user1->id, $course->id);

        $learning_items = \core_course\user_learning\item::one($this->user1->id, $course->id);
        $info = $learning_items->export_for_template();

        $this->validateExportInfo($info, 'All', array('One other course needs to be completed'));
    }

    public function test_export_for_template_multi_other_courses() {
        global $DB;

        $course = $this->generator->create_course(array('shortname' => 'cc1','fullname' => 'Completion Course 1', 'enablecompletion' => 1));
        $course = $DB->get_record('course', array('id' => $this->course6->id));

        $this->completion_generator->enable_completion_tracking($course);
        $this->completion_generator->set_course_criteria_course_completion($course, array($this->course2->id, $this->course3->id), COMPLETION_AGGREGATION_ALL);

        $this->generator->enrol_user($this->user1->id, $course->id);

        $learning_items = \core_course\user_learning\item::one($this->user1->id, $course->id);
        $info = $learning_items->export_for_template();

        $this->validateExportInfo($info, 'All', array('2 other courses need to be completed'));
    }

    public function test_export_for_template_until_data() {
        global $DB;

        $course = $this->generator->create_course(array('shortname' => 'cc1','fullname' => 'Completion Course 1', 'enablecompletion' => 1));
        $course = $DB->get_record('course', array('id' => $this->course6->id));

        $enddate = strtotime("+1 week");
        $format = get_string('strfdateshortmonth', 'langconfig');
        $strdate = userdate($enddate, $format, null, false);
        $this->completion_generator->enable_completion_tracking($course);
        $completioncriteria = array();
        $completioncriteria[COMPLETION_CRITERIA_TYPE_DATE] = $enddate;
        $this->completion_generator->set_completion_criteria($course, $completioncriteria);

        $this->generator->enrol_user($this->user1->id, $course->id);

        $learning_items = \core_course\user_learning\item::one($this->user1->id, $course->id);
        $info = $learning_items->export_for_template();

        $this->validateExportInfo($info, 'All', array('You must remain enrolled until ' . $strdate));
    }

    public function test_export_for_template_days_left() {
        global $DB;

        $course = $this->generator->create_course(array('shortname' => 'cc1','fullname' => 'Completion Course 1', 'enablecompletion' => 1));
        $course = $DB->get_record('course', array('id' => $this->course6->id));

        $this->completion_generator->enable_completion_tracking($course);
        $completioncriteria = array();
        $completioncriteria[COMPLETION_CRITERIA_TYPE_DURATION] = 2 * 86400;
        $this->completion_generator->set_completion_criteria($course, $completioncriteria);

        $this->generator->enrol_user($this->user1->id, $course->id);

        $learning_items = \core_course\user_learning\item::one($this->user1->id, $course->id);
        $info = $learning_items->export_for_template();

        $this->validateExportInfo($info, 'All', array('You must be enrolled for a total of 2 days'));
    }

    public function test_export_for_template_grade() {
        global $DB;

        $course = $this->generator->create_course(array('shortname' => 'cc1','fullname' => 'Completion Course 1', 'enablecompletion' => 1));
        $course = $DB->get_record('course', array('id' => $this->course6->id));

        $this->completion_generator->enable_completion_tracking($course);
        $completioncriteria = array();
        $completioncriteria[COMPLETION_CRITERIA_TYPE_GRADE] = 75.0;
        $this->completion_generator->set_completion_criteria($course, $completioncriteria);

        $this->generator->enrol_user($this->user1->id, $course->id);

        $learning_items = \core_course\user_learning\item::one($this->user1->id, $course->id);
        $info = $learning_items->export_for_template();

        $this->validateExportInfo($info, 'All', array('You must achieve a grade of 75.00'));
    }

    public function test_export_for_template_manual_self_completion() {
        global $DB;

        $course = $this->generator->create_course(array('shortname' => 'cc1','fullname' => 'Completion Course 1', 'enablecompletion' => 1));
        $course = $DB->get_record('course', array('id' => $this->course6->id));

        $this->completion_generator->enable_completion_tracking($course);
        $completioncriteria = array();
        $completioncriteria[COMPLETION_CRITERIA_TYPE_SELF] = 1;
        $this->completion_generator->set_completion_criteria($course, $completioncriteria);

        $this->generator->enrol_user($this->user1->id, $course->id);

        $learning_items = \core_course\user_learning\item::one($this->user1->id, $course->id);
        $info = $learning_items->export_for_template();

        $this->validateExportInfo($info, 'All', array('You must mark yourself as complete'));
    }

    public function test_export_for_template_one_role() {
        global $DB;

        $course = $this->generator->create_course(array('shortname' => 'cc1','fullname' => 'Completion Course 1', 'enablecompletion' => 1));
        $course = $DB->get_record('course', array('id' => $this->course6->id));

        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));

        $this->completion_generator->enable_completion_tracking($course);
        $completioncriteria = array();
        $completioncriteria[COMPLETION_CRITERIA_TYPE_ROLE] = array(
            'elements' => array($teacherrole->id),
            'aggregationmethod' => COMPLETION_AGGREGATION_ALL);
        $this->completion_generator->set_completion_criteria($course, $completioncriteria);

        $this->generator->enrol_user($this->user1->id, $course->id);

        $learning_items = \core_course\user_learning\item::one($this->user1->id, $course->id);
        $info = $learning_items->export_for_template();

        $this->validateExportInfo($info, 'All', array('You must be marked as complete by a Trainer'));
    }

    public function test_export_for_template_multiple_roles_all() {
        global $DB;

        $course = $this->generator->create_course(array('shortname' => 'cc1','fullname' => 'Completion Course 1', 'enablecompletion' => 1));
        $course = $DB->get_record('course', array('id' => $this->course6->id));

        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $editteacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));

        $this->completion_generator->enable_completion_tracking($course);
        $completioncriteria = array();
        $completioncriteria[COMPLETION_CRITERIA_TYPE_ROLE] = array(
            'elements' => array($teacherrole->id, $editteacherrole->id),
            'aggregationmethod' => COMPLETION_AGGREGATION_ALL);
        $this->completion_generator->set_completion_criteria($course, $completioncriteria);

        $this->generator->enrol_user($this->user1->id, $course->id);

        $learning_items = \core_course\user_learning\item::one($this->user1->id, $course->id);
        $info = $learning_items->export_for_template();

        $this->validateExportInfo($info, 'All',
            array('You must be marked as complete by <strong>All</strong> of the following roles: Editing Trainer, Trainer'));
    }

    public function test_export_for_template_multiple_roles_any() {
        global $DB;

        $course = $this->generator->create_course(array('shortname' => 'cc1','fullname' => 'Completion Course 1', 'enablecompletion' => 1));
        $course = $DB->get_record('course', array('id' => $this->course6->id));

        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $editteacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));

        $this->completion_generator->enable_completion_tracking($course);
        $completioncriteria = array();
        $completioncriteria[COMPLETION_CRITERIA_TYPE_ROLE] = array(
            'elements' => array($teacherrole->id, $editteacherrole->id),
            'aggregationmethod' => COMPLETION_AGGREGATION_ANY);
        $this->completion_generator->set_completion_criteria($course, $completioncriteria);

        $this->generator->enrol_user($this->user1->id, $course->id);

        $learning_items = \core_course\user_learning\item::one($this->user1->id, $course->id);
        $info = $learning_items->export_for_template();

        $this->validateExportInfo($info, 'All',
            array('You must be marked as complete by <strong>Any</strong> of the following roles: Editing Trainer, Trainer'));
    }

    public function test_export_for_template_combined_criteriatypes() {
        global $DB;

        $course = $this->generator->create_course(array('shortname' => 'cc1','fullname' => 'Completion Course 1', 'enablecompletion' => 1));
        $course = $DB->get_record('course', array('id' => $this->course6->id));
        $data = $this->generator->create_module('data', array('course' => $course->id), array('completion' => 1));
        $forum = $this->generator->create_module('forum',  array('course' => $course->id), array('completion' => 1));
        $assign = $this->generator->create_module('assign',  array('course' => $course->id));

        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $editteacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));

        $this->completion_generator->enable_completion_tracking($course);
        $completioncriteria = array();
        $completioncriteria[COMPLETION_CRITERIA_TYPE_ACTIVITY] = array(
            'elements' => array($data, $forum, $assign),
            'aggregationmethod' => COMPLETION_AGGREGATION_ANY);
        $completioncriteria[COMPLETION_CRITERIA_TYPE_COURSE] = array(
            'elements' => array($this->course2->id, $this->course3->id),
            'aggregationmethod' => COMPLETION_AGGREGATION_ALL);
        $completioncriteria[COMPLETION_CRITERIA_TYPE_ROLE] = array(
            'elements' => array($teacherrole->id, $editteacherrole->id),
            'aggregationmethod' => COMPLETION_AGGREGATION_ALL);
        $this->completion_generator->set_completion_criteria($course, $completioncriteria);

        $this->generator->enrol_user($this->user1->id, $course->id);

        $learning_items = \core_course\user_learning\item::one($this->user1->id, $course->id);
        $info = $learning_items->export_for_template();

        $this->validateExportInfo($info, 'All',
            array('One activity needs to be completed',
                  'You must be marked as complete by <strong>All</strong> of the following roles: Editing Trainer, Trainer',
                  '2 other courses need to be completed'
            ));
    }

    public function test_export_for_template_completed() {
        global $DB;

        $course = $this->generator->create_course(array('shortname' => 'cc1','fullname' => 'Completion Course 1', 'enablecompletion' => 1));
        $course = $DB->get_record('course', array('id' => $this->course6->id));
        $data = $this->generator->create_module('data', array('course' => $course->id), array('completion' => 1));
        $forum = $this->generator->create_module('forum',  array('course' => $course->id), array('completion' => 1));
        $assign = $this->generator->create_module('assign',  array('course' => $course->id));

        $this->completion_generator->enable_completion_tracking($course);
        $completioncriteria = array();
        $completioncriteria[COMPLETION_CRITERIA_TYPE_DATE] = strtotime("-1 week");
        $this->completion_generator->set_completion_criteria($course, $completioncriteria);

        $this->generator->enrol_user($this->user1->id, $course->id);

        $learning_items = \core_course\user_learning\item::one($this->user1->id, $course->id);
        $info = $learning_items->export_for_template();

        $format = get_string('strfdateshortmonth', 'langconfig');
        $a = array('timecompleted' => userdate(time(), $format));
        $strdate = get_string('completed-on', 'completion', $a);
        $this->validateExportInfo($info, null, array(), $strdate);
    }

    public function test_export_for_template_completed_via_rpl() {
        global $DB;

        $course = $this->generator->create_course(array('shortname' => 'cc1','fullname' => 'Completion Course 1', 'enablecompletion' => 1));
        $course = $DB->get_record('course', array('id' => $this->course6->id));
        $data = $this->generator->create_module('data', array('course' => $course->id), array('completion' => 1));

        $this->completion_generator->enable_completion_tracking($course);
        $this->completion_generator->set_activity_completion($course->id, array($data));

        $this->generator->enrol_user($this->user1->id, $course->id);

        // Mark course completed via rpl
        $completion = new completion_completion(['userid' => $this->user1->id, 'course' => $course->id]);
        $completion->rpl = 'Course completed via rpl in user_learning_item_test';
        $completion->status = COMPLETION_STATUS_COMPLETEVIARPL;
        $completion->mark_complete();

        // Now test the exported completion criteria
        $learning_items = \core_course\user_learning\item::one($this->user1->id, $course->id);
        $info = $learning_items->export_for_template();

        $format = get_string('strfdateshortmonth', 'langconfig');
        $a = array(
            'timecompleted' => userdate(time(), $format),
            'rpl' => $completion->rpl
        );
        $strdate = get_string('completedviarpl-on', 'completion', $a);
        $this->validateExportInfo($info, null, array(), $strdate);
    }

    /**
     * Parse the export value into a fixed string and array of detail values
     *
     * @param string $value Value to parse
     * @return array containing the fixed string and detail array
     */
    function parseExportValue($value) {
        $fixed = $value;

        $valarr = explode(':', $value, 2);
        $detail = array();

        if (count($valarr) > 1) {
            $detail = explode(',', $valarr[1]);
            $fixed = $valarr[0];
        }

        return array($fixed, $detail);
    }

    /**
      * Test whether two exported criteria strings are the same. If the string contains a list of values, ignore the
      * order of the values when comparing
      *
      * @param string $expected Expected criteria string
      * @param string $actual Actual criteria string
      * @return boolean true if they are the same, else false
      */
    function compareExportCriteria($expected, $actual) {
        list($expFixed, $expDetail) = $this->parseExportValue($expected);
        list($actFixed, $actDetail) = $this->parseExportValue($actual);

        if ($expFixed != $actFixed) {
            return false;
        }

        if (count($expDetail) != count($actDetail)) {
            return false;
        }

        if (count($expDetail) == 0) {
            return true;
        }

        sort($expDetail);
        sort($actDetail);
        return $expDetail == $actDetail;
    }

    /**
     * Validate the exported course information
     *
     * @param stdClass $info Exported information to valudate
     * @param string $aggregation Expected aggregation value
     * @param int $num_criteria Expected number of criteria
     * @param array $expectedcriteria Expected criteria string
     */
    function validateExportInfo($info, $aggregation = '',
                                $expectedcriteria = null,
                                $summary = null) {
        $this->assertObjectHasAttribute('progress', $info);
        if (is_null($summary)) {
            $this->assertArrayNotHasKey('summary', $info->progress->pbar['popover']['contenttemplatecontext']);
        } else {
            $this->assertArrayHasKey('summary', $info->progress->pbar['popover']['contenttemplatecontext']);
            $this->assertEquals($summary, $info->progress->pbar['popover']['contenttemplatecontext']['summary']);
        }

        $this->assertObjectHasAttribute('pbar', $info->progress);
        $this->assertArrayHasKey('popover', $info->progress->pbar);
        $this->assertArrayHasKey('contenttemplatecontext', $info->progress->pbar['popover']);

        $this->assertArrayHasKey('hascoursecriteria', $info->progress->pbar['popover']['contenttemplatecontext']);
        if ($expectedcriteria == null || count($expectedcriteria)== 0) {
            $this->assertFalse($info->progress->pbar['popover']['contenttemplatecontext']['hascoursecriteria']);
            $this->assertArrayNotHasKey('criteria', $info->progress->pbar['popover']['contenttemplatecontext']);
            $this->assertArrayNotHasKey('aggregation', $info->progress->pbar['popover']['contenttemplatecontext']);
        } else {
            $this->assertTrue($info->progress->pbar['popover']['contenttemplatecontext']['hascoursecriteria']);

            $this->assertArrayHasKey('criteria', $info->progress->pbar['popover']['contenttemplatecontext']);
            $this->assertTrue(is_array($info->progress->pbar['popover']['contenttemplatecontext']['criteria']));
            $this->assertEquals(count($expectedcriteria), count($info->progress->pbar['popover']['contenttemplatecontext']['criteria']));

            $this->assertArrayHasKey('aggregation', $info->progress->pbar['popover']['contenttemplatecontext']);
            $this->assertEquals("<strong>$aggregation</strong> of the following criteria need to be met to complete this course",
                            $info->progress->pbar['popover']['contenttemplatecontext']['aggregation']);

            $valid = true;
            $results = $info->progress->pbar['popover']['contenttemplatecontext']['criteria'] ;

            // Already validated the number of criteria
            foreach ($expectedcriteria as $testval) {
                $fnd = false;
                foreach ($results as $resval) {
                    if ($this->compareExportCriteria($testval, $resval)) {
                        $fnd = true;
                        continue;
                    }
                }

                if (!$fnd) {
                    $valid = false;
                    continue;
                }
            }

            $msg = "Expected criteria don't match actual criteria: Expected - [" .
                        implode(';', $expectedcriteria) . "]; Actual - [" . implode(';', $results) . "]";
            $this->assertTrue($valid, $msg);
        }
    }
}

