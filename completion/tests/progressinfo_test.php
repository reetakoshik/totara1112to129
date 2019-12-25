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
 * @author     Riana Rossouw <riana.rossouw@totaralearning.com>
 * @authot     Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @copyright  2017 Totara Learning Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package    totara_core
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * Progress information generation test
 */
class core_completion_progressinfo_testcase extends externallib_advanced_testcase {

    /**
     * Test progressinfo for activities_completion only
     */
    public function test_progressinfo_activities_only() {
        global $DB, $CFG;

        $this->resetAfterTest(true);

        $CFG->enablecompletion = true;
        $student = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));

        $data = $this->getDataGenerator()->create_module('data', array('course' => $course->id),
                                                             array('completion' => 1));
        $forum = $this->getDataGenerator()->create_module('forum',  array('course' => $course->id),
                                                             array('completion' => 1));
        $assign = $this->getDataGenerator()->create_module('assign',  array('course' => $course->id));

        $cmdata = get_coursemodule_from_id('data', $data->cmid);
        $cmforum = get_coursemodule_from_id('forum', $forum->cmid);

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id);

        /** @var core_completion_generator $cgen */
        $cgen = $this->getDataGenerator()->get_plugin_generator('core_completion');
        $cgen->enable_completion_tracking($course);
        $cgen->set_activity_completion($course->id, array($data, $forum));

        // Verify progress info structure
        $completion = new completion_info($course);

        $verify_info = \totara_core\progressinfo\progressinfo::from_data(\totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 0, 0);
        $verify_act = $verify_info->add_criteria(COMPLETION_CRITERIA_TYPE_ACTIVITY, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 0, 0);
        $verify_act->add_criteria($cmdata->id, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0);
        $verify_act->add_criteria($cmforum->id, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0);

        $progressinfo = $completion->get_progressinfo();
        $this->assertEquals($verify_info, $progressinfo);
    }

    /**
     * Test progressinfo multiple criteria types
     */
    public function test_progressinfo_multiple_criteria_types() {
        global $DB, $CFG;

        $this->resetAfterTest(true);

        $CFG->enablecompletion = true;
        $student = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $editteacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));

        /** @var core_completion_generator $cgen */
        $cgen = $this->getDataGenerator()->get_plugin_generator('core_completion');

        // Create 3 courses
        $course1 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $course2 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $course3 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));

        // Set course completion for course1 :
        //     - Complete all activities
        //     - Some of the other course
        //     - Both editingteacher and teacher roles must complete
        $data = $this->getDataGenerator()->create_module('data', array('course' => $course1->id),
                                                             array('completion' => COMPLETION_TRACKING_MANUAL));
        $forum = $this->getDataGenerator()->create_module('forum',  array('course' => $course1->id),
                                                             array('completion' => COMPLETION_TRACKING_MANUAL));
        $assign = $this->getDataGenerator()->create_module('assign',  array('course' => $course1->id),
                                                             array('completion' => COMPLETION_TRACKING_MANUAL));

        $cmdata = get_coursemodule_from_id('data', $data->cmid);
        $cmforum = get_coursemodule_from_id('forum', $forum->cmid);
        $cmassign = get_coursemodule_from_id('assign', $assign->cmid);

        $this->getDataGenerator()->enrol_user($student->id, $course1->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($teacher->id, $course1->id, $teacherrole->id);

        $cgen->enable_completion_tracking($course1);

        $todate = time() + WEEKSECS;
        $completioncriteria = array();
        $completioncriteria[COMPLETION_CRITERIA_TYPE_ACTIVITY] = array(
            'elements' => array($data, $forum, $assign));
        $completioncriteria[COMPLETION_CRITERIA_TYPE_COURSE] = array(
            'elements' => array($course2->id, $course3->id),
            'aggregationmethod' => COMPLETION_AGGREGATION_ANY);
        $completioncriteria[COMPLETION_CRITERIA_TYPE_ROLE] = array(
            'elements' => array($teacherrole->id, $editteacherrole->id),
            'aggregationmethod' => COMPLETION_AGGREGATION_ALL);
        $completioncriteria[COMPLETION_CRITERIA_TYPE_SELF] = 1;
        $completioncriteria[COMPLETION_CRITERIA_TYPE_DATE] = $todate;
        $completioncriteria[COMPLETION_CRITERIA_TYPE_DURATION] = DAYSECS;
        $completioncriteria[COMPLETION_CRITERIA_TYPE_GRADE] = 50.0;
        $cgen->set_completion_criteria($course1, $completioncriteria);

        $this->assertEquals(11, $DB->count_records('course_completion_criteria', array('course' => $course1->id)));
        $this->assertEquals(3, $DB->count_records('course_completion_criteria',
            array('course' => $course1->id, 'criteriatype' => COMPLETION_CRITERIA_TYPE_ACTIVITY)));
        $this->assertEquals(2, $DB->count_records('course_completion_criteria',
            array('course' => $course1->id, 'criteriatype' => COMPLETION_CRITERIA_TYPE_COURSE)));
        $this->assertEquals(2, $DB->count_records('course_completion_criteria',
            array('course' => $course1->id, 'criteriatype' => COMPLETION_CRITERIA_TYPE_ROLE)));
        $this->assertEquals(1, $DB->count_records('course_completion_criteria',
            array('course' => $course1->id, 'criteriatype' => COMPLETION_CRITERIA_TYPE_SELF)));
        $this->assertEquals(1, $DB->count_records('course_completion_criteria',
            array('course' => $course1->id, 'criteriatype' => COMPLETION_CRITERIA_TYPE_DATE)));
        $this->assertEquals(1, $DB->count_records('course_completion_criteria',
            array('course' => $course1->id, 'criteriatype' => COMPLETION_CRITERIA_TYPE_DURATION)));
        $this->assertEquals(1, $DB->count_records('course_completion_criteria',
            array('course' => $course1->id, 'criteriatype' => COMPLETION_CRITERIA_TYPE_GRADE)));

        // Verify progress info structure
        $completion = new \completion_info($course1);

        // Verify the progressinfo structure
        $verify_info = \totara_core\progressinfo\progressinfo::from_data(\totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 0, 0);

        $verify_act = $verify_info->add_criteria(COMPLETION_CRITERIA_TYPE_ACTIVITY, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 0, 0);
        $verify_act->add_criteria($cmdata->id, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0);
        $verify_act->add_criteria($cmforum->id, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0);
        $verify_act->add_criteria($cmassign->id, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0);

        $verify_course = $verify_info->add_criteria(COMPLETION_CRITERIA_TYPE_COURSE, \totara_core\progressinfo\progressinfo::AGGREGATE_ANY, 0, 0);
        $verify_course->add_criteria($course2->id, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0);
        $verify_course->add_criteria($course3->id, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0);

        $verify_role = $verify_info->add_criteria(COMPLETION_CRITERIA_TYPE_ROLE, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 0, 0,
            array('roles' => 'Editing Trainer, Trainer'));
        $verify_role->add_criteria($teacherrole->id, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0);
        $verify_role->add_criteria($editteacherrole->id, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0);

        $verify_info->add_criteria(COMPLETION_CRITERIA_TYPE_SELF, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0);

        $format = get_string('strfdateshortmonth', 'langconfig');
        $verify_info->add_criteria(COMPLETION_CRITERIA_TYPE_DATE, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0,
            array('date' => userdate($todate, $format, null, false)));
        $verify_info->add_criteria(COMPLETION_CRITERIA_TYPE_DURATION, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0,
            array('duration' => '1 days'));
        $verify_info->add_criteria(COMPLETION_CRITERIA_TYPE_GRADE, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0,
            array('grade' => '50.00'));

        $progressinfo = $completion->get_progressinfo();
        $this->assertEquals($verify_info, $progressinfo);
    }

    public function test_course_completion_criteria_progress_calculation() {
        global $DB;

        $generator = $this->getDataGenerator();
        /** @var core_completion_generator $comp_generator */
        $comp_generator = $generator->get_plugin_generator('core_completion');

        // Create a course
        $course = $generator->create_course(['idnumber' => 'progress_test']);
        $comp_generator->enable_completion_tracking($course);

        // Create a label with manual completion.
        $label = $generator->create_module('label', array(
            'course' => $course,
            'completion' => COMPLETION_TRACKING_MANUAL
        ));

        $managerroleid = $DB->get_field('role', 'id', ['shortname' => 'manager']);

        $tomorrow = time() + DAYSECS;

        // Now add criteria to the course.
        $comp_generator->set_completion_criteria($course, [
            COMPLETION_CRITERIA_TYPE_SELF => 1, // Its a checkbox.
            COMPLETION_CRITERIA_TYPE_DATE => $tomorrow, // A date well in the future. We'll hack this later.
            COMPLETION_CRITERIA_TYPE_ACTIVITY => [
                'elements' => array($label),
                'aggregationmethod' => COMPLETION_AGGREGATION_ALL
            ],
            COMPLETION_CRITERIA_TYPE_ROLE => [
                'elements' => array($managerroleid),
                'aggregationmethod' => COMPLETION_AGGREGATION_ALL
            ],
        ]);

        // All must be completed.
        $comp_generator->set_aggregation_method(COMPLETION_AGGREGATION_ALL);

        // Create a learner and a manager
        $learner = $generator->create_user(['idnumber' => 'progress_learner']);
        $manager = $generator->create_user(['idnumber' => 'progress_manager']);

        // Enrol them in the course with appropriate perms.
        $generator->enrol_user($learner->id, $course->id, 'student');
        $generator->enrol_user($manager->id, $course->id, $managerroleid);

        // Test the setup we've created is as we expect. Semi testing the generators.
        $this::assertSame($DB->get_field('course', 'fullname', ['id' => $course->id]), $course->fullname);
        $this::assertSame($DB->get_field('user', 'username', ['id' => $learner->id]), $learner->username);
        $this::assertSame($DB->get_field('user', 'username', ['id' => $manager->id]), $manager->username);

        self::assertTrue(completion_criteria::course_has_criteria($course->id));

        $completioninfo = new completion_info($course);
        $activities = $completioninfo->get_activities();
        self::assertCount(1, $activities);
        $activity = reset($activities);
        self::assertInstanceOf('cm_info', $activity);
        self::assertSame($activity->completion, (string)COMPLETION_TRACKING_MANUAL);

        $criteria = $completioninfo->get_criteria();
        self::assertCount(4, $criteria);
        $criteria_self = null;
        $criteria_date = null;
        $criteria_activity = null;
        $criteria_role = null;
        foreach ($criteria as $criterion) {
            switch (get_class($criterion)) {
                case 'completion_criteria_activity':
                    if ($criteria_activity !== null) {
                        $this->fail('Duplicate activity criterion found.');
                    }
                    /** @var completion_criteria_activity $criterion */
                    self::assertEquals(COMPLETION_CRITERIA_TYPE_ACTIVITY, $criterion->criteriatype);
                    self::assertEquals($course->id, $criterion->course);
                    self::assertSame('label', $criterion->module);
                    self::assertEquals($label->cmid, $criterion->moduleinstance);
                    self::assertEquals('', $criterion->gradepass);
                    self::assertEquals('', $criterion->role);
                    self::assertEquals('', $criterion->timeend);
                    $criteria_activity = $criterion;
                    break;
                case 'completion_criteria_course':
                    $this->fail('Unexpected course criterion found.');
                    break;
                case 'completion_criteria_date':
                    if ($criteria_date !== null) {
                        $this->fail('Duplicate date criterion found.');
                    }
                    /** @var completion_criteria_date $criterion */
                    self::assertEquals(COMPLETION_CRITERIA_TYPE_DATE, $criterion->criteriatype);
                    self::assertEquals($course->id, $criterion->course);
                    self::assertEquals('', $criterion->module);
                    self::assertEquals('', $criterion->moduleinstance);
                    self::assertEquals('', $criterion->gradepass);
                    self::assertEquals('', $criterion->role);
                    self::assertEquals($tomorrow, $criterion->timeend);

                    $criteria_date = $criterion;
                    break;
                case 'completion_criteria_duration':
                    $this->fail('Unexpected duration criterion found.');
                    break;
                case 'completion_criteria_grade':
                    $this->fail('Unexpected duration criterion found.');
                    break;
                case 'completion_criteria_role':
                    if ($criteria_role !== null) {
                        $this->fail('Duplicate role criterion found.');
                    }
                    /** @var completion_criteria_grade $criterion */
                    self::assertEquals(COMPLETION_CRITERIA_TYPE_ROLE, $criterion->criteriatype);
                    self::assertEquals($course->id, $criterion->course);
                    self::assertEquals('', $criterion->module);
                    self::assertEquals('', $criterion->moduleinstance);
                    self::assertEquals('', $criterion->gradepass);
                    self::assertEquals($managerroleid, $criterion->role);
                    self::assertEquals('', $criterion->timeend);
                    $criteria_role = $criterion;
                    break;
                case 'completion_criteria_self':
                    if ($criteria_self !== null) {
                        $this->fail('Duplicate self criterion found.');
                    }
                    /** @var completion_criteria_self $criterion */
                    self::assertEquals(COMPLETION_CRITERIA_TYPE_SELF, $criterion->criteriatype);
                    self::assertEquals($course->id, $criterion->course);
                    self::assertEquals('', $criterion->module);
                    self::assertEquals('', $criterion->moduleinstance);
                    self::assertEquals('', $criterion->gradepass);
                    self::assertEquals('', $criterion->role);
                    self::assertEquals('', $criterion->timeend);
                    $criteria_self = $criterion;
                    break;
            }
        }
        if (is_null($criteria_activity)) {
            $this->fail('Activity criterion not found.');
        }
        if (is_null($criteria_date)) {
            $this->fail('Date criterion not found.');
        }
        if (is_null($criteria_role)) {
            $this->fail('Role criterion not found.');
        }
        if (is_null($criteria_self)) {
            $this->fail('Self criterion not found.');
        }
        // OK now we trust the criteria.

        // Check progress info.
        $progressinfo = $completioninfo->get_progressinfo();
        self::assertInstanceOf('\totara_core\progressinfo\progressinfo', $progressinfo);
        self::assertSame(0, $progressinfo->get_weight());
        self::assertSame(0.0, $progressinfo->get_score());
        self::assertEquals('', $progressinfo->get_customdata());
        $progresscriteria = $progressinfo->get_all_criteria();
        self::assertCount(4, $progresscriteria);
        $totalcount = $this->dive_count($progressinfo);
        self::assertSame(6, $totalcount);

        $cache = cache::make('totara_core', 'completion_progressinfo');
        $cachekey = "{$course->id}_{$learner->id}";
        $cache->delete($cachekey);

        $completion = new completion_completion(['userid' => $learner->id, 'course' => $course->id]);
        self::assertFalse($completion->is_complete());
        self::assertSame(0, $completion->get_percentagecomplete());

        $cache = cache::make('totara_core', 'completion_progressinfo');
        $cachedata = $cache->get("{$course->id}_{$learner->id}");
        self::assertNotFalse($cachedata);

        // There are at this point 4 criteria to complete.
        // Each criteria the user completes is worth 25%.
        $comp_generator->complete_activity($course->id, $learner->id, $label->cmid);
        // We have to get a new completion_completion instance, as the state has changed.
        $completion = new completion_completion(['userid' => $learner->id, 'course' => $course->id]);
        self::assertFalse($completion->is_complete());
        self::assertSame(25, $completion->get_percentagecomplete());
        self::assertSame(25, $completion->get_progressinfo()->get_percentagecomplete());

        $comp_generator->complete_by_role($course, $learner->id, $managerroleid);
        $completion = new completion_completion(['userid' => $learner->id, 'course' => $course->id]);
        self::assertFalse($completion->is_complete());
        self::assertSame(50, $completion->get_percentagecomplete());
        self::assertSame(50, $completion->get_progressinfo()->get_percentagecomplete());

        $comp_generator->complete_by_self($course, $learner->id);
        $completion = new completion_completion(['userid' => $learner->id, 'course' => $course->id]);
        self::assertFalse($completion->is_complete());
        self::assertSame(75, $completion->get_percentagecomplete());
        self::assertSame(75, $completion->get_progressinfo()->get_percentagecomplete());

        // Set the date criteria back and then execute the criterion's cron.
        $DB->set_field('course_completion_criteria', 'timeend', time() - 86400, ['id' => $criteria_date->id]);
        $criteria_date->cron();

        $completion = new completion_completion(['userid' => $learner->id, 'course' => $course->id]);
        self::assertTrue($completion->is_complete());
        self::assertSame(100, $completion->get_percentagecomplete());
        self::assertSame(100, $completion->get_progressinfo()->get_percentagecomplete());

        // test advanced all course completion criteria progress calculation

        $generator = $this->getDataGenerator();
        /** @var core_completion_generator $comp_generator */
        $comp_generator = $generator->get_plugin_generator('core_completion');

        $learner = $DB->get_record('user', ['idnumber' => 'progress_learner'], '*', MUST_EXIST);
        $manager = $DB->get_record('user', ['idnumber' => 'progress_manager'], '*', MUST_EXIST);
        $trainer = $generator->create_user(['idnumber' => 'progress_trainer']);
        $simple_course = $DB->get_record('course', ['idnumber' => 'progress_test'], '*', MUST_EXIST);
        $adv_course = $generator->create_course(['idnumber' => 'progress_all_test']);
        $comp_generator->enable_completion_tracking($adv_course);

        // Create a label with manual completion.
        $label_1 = $generator->create_module('label', array(
            'course' => $adv_course,
            'completion' => COMPLETION_TRACKING_MANUAL
        ));
        $label_2 = $generator->create_module('label', array(
            'course' => $adv_course,
            'completion' => COMPLETION_TRACKING_MANUAL
        ));
        $label_3 = $generator->create_module('label', array(
            'course' => $adv_course,
            'completion' => COMPLETION_TRACKING_MANUAL
        ));

        $managerroleid = $DB->get_field('role', 'id', ['shortname' => 'manager']);
        $trainerroleid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);

        $tomorrow = time() + 86400;

        // Now add criteria to the course.
        $comp_generator->set_completion_criteria($adv_course, [
            COMPLETION_CRITERIA_TYPE_SELF => 1, // Its a checkbox.
            COMPLETION_CRITERIA_TYPE_DATE => $tomorrow, // A date well in the future. We'll hack this later.
            COMPLETION_CRITERIA_TYPE_ACTIVITY => [
                'elements' => array($label_1, $label_2, $label_3),
                'aggregationmethod' => COMPLETION_AGGREGATION_ALL
            ],
            COMPLETION_CRITERIA_TYPE_ROLE => [
                'elements' => array($managerroleid, $trainerroleid),
                'aggregationmethod' => COMPLETION_AGGREGATION_ALL
            ],
            COMPLETION_CRITERIA_TYPE_COURSE => [
                'elements' => array($simple_course->id),
                'aggregationmethod' => COMPLETION_AGGREGATION_ALL
            ]
        ]);
        // All must be completed.
        $comp_generator->set_aggregation_method(COMPLETION_AGGREGATION_ALL);

        // Enrol them in the course with appropriate perms.
        $generator->enrol_user($learner->id, $adv_course->id, 'student');
        $generator->enrol_user($trainer->id, $adv_course->id, $trainerroleid);
        $generator->enrol_user($manager->id, $adv_course->id, $managerroleid);

        $completioninfo = new completion_info($adv_course);
        $activities = $completioninfo->get_activities();
        self::assertCount(3, $activities);

        $criteria = $completioninfo->get_criteria();
        self::assertCount(8, $criteria);

        $criteria_self = null;
        $criteria_date = null;
        $criteria_activity = null;
        $criteria_role = null;
        foreach ($criteria as $criterion) {
            switch (get_class($criterion)) {
                case 'completion_criteria_date':
                    $criteria_date = $criterion;
                    break;
            }
        }

        // Check progress info.
        $progressinfo = $completioninfo->get_progressinfo();
        self::assertInstanceOf('\totara_core\progressinfo\progressinfo', $progressinfo);
        self::assertSame(0, $progressinfo->get_weight());
        self::assertSame(0.0, $progressinfo->get_score());
        self::assertEquals('', $progressinfo->get_customdata());
        $progresscriteria = $progressinfo->get_all_criteria();
        self::assertCount(5, $progresscriteria);
        $totalcount = $this->dive_count($progressinfo);
        self::assertSame(11, $totalcount); // 5 first level, 6 second level.

        // In total there are 8 criteria that have to be completed.
        // There are 8 criteria and aggregation is "all".
        $total = 8;

        // NOTE: To begin with the user has already completed the simple course!
        $completion = new completion_completion(['userid' => $learner->id, 'course' => $adv_course->id]);
        self::assertFalse($completion->is_complete());
        self::assertSame((int)floor(1/$total * 100), $completion->get_percentagecomplete());

        $cache = cache::make('totara_core', 'completion_progressinfo');
        $cachedata = $cache->get("{$adv_course->id}_{$learner->id}");
        self::assertNotFalse($cachedata);

        // There are at this point 4 criteria to complete.
        // Of the 4, one has 2 subcriteria, and one has 3.
        // Overall total criteria taking an equal share is 7.
        $comp_generator->complete_activity($adv_course->id, $learner->id, $label_1->cmid);
        // We have to get a new completion_completion instance, as the state has changed.
        $completion = new completion_completion(['userid' => $learner->id, 'course' => $adv_course->id]);
        self::assertFalse($completion->is_complete());
        self::assertSame((int)floor(2/$total * 100), $completion->get_percentagecomplete());
        self::assertSame((int)floor(2/$total * 100), $completion->get_progressinfo()->get_percentagecomplete());

        $comp_generator->complete_by_role($adv_course, $learner->id, $managerroleid);
        $completion = new completion_completion(['userid' => $learner->id, 'course' => $adv_course->id]);
        self::assertFalse($completion->is_complete());
        self::assertSame((int)floor(3/$total * 100), $completion->get_percentagecomplete());
        self::assertSame((int)floor(3/$total * 100), $completion->get_progressinfo()->get_percentagecomplete());

        $comp_generator->complete_by_self($adv_course, $learner->id);
        $completion = new completion_completion(['userid' => $learner->id, 'course' => $adv_course->id]);
        self::assertFalse($completion->is_complete());
        self::assertSame((int)floor(4/$total * 100), $completion->get_percentagecomplete());
        self::assertSame((int)floor(4/$total * 100), $completion->get_progressinfo()->get_percentagecomplete());

        $comp_generator->complete_by_role($adv_course, $learner->id, $trainerroleid);
        $completion = new completion_completion(['userid' => $learner->id, 'course' => $adv_course->id]);
        self::assertFalse($completion->is_complete());
        self::assertSame((int)floor(5/$total * 100), $completion->get_percentagecomplete());
        self::assertSame((int)floor(5/$total * 100), $completion->get_progressinfo()->get_percentagecomplete());

        $comp_generator->complete_activity($adv_course->id, $learner->id, $label_2->cmid);
        // We have to get a new completion_completion instance, as the state has changed.
        $completion = new completion_completion(['userid' => $learner->id, 'course' => $adv_course->id]);
        self::assertFalse($completion->is_complete());
        self::assertSame((int)floor(6/$total * 100), $completion->get_percentagecomplete());
        self::assertSame((int)floor(6/$total * 100), $completion->get_progressinfo()->get_percentagecomplete());

        // Set the date criteria back and then execute the criterion's cron.
        $DB->set_field('course_completion_criteria', 'timeend', time() - 86400, ['id' => $criteria_date->id]);
        $criteria_date->cron();

        $completion = new completion_completion(['userid' => $learner->id, 'course' => $adv_course->id]);
        self::assertFalse($completion->is_complete());
        self::assertSame((int)floor(7/$total * 100), $completion->get_percentagecomplete());
        self::assertSame((int)floor(7/$total * 100), $completion->get_progressinfo()->get_percentagecomplete());

        $comp_generator->complete_activity($adv_course->id, $learner->id, $label_3->cmid);
        // We have to get a new completion_completion instance, as the state has changed.
        $completion = new completion_completion(['userid' => $learner->id, 'course' => $adv_course->id]);
        self::assertTrue($completion->is_complete());
        self::assertSame(100, $completion->get_percentagecomplete());
        self::assertSame(100, $completion->get_progressinfo()->get_percentagecomplete());

        // test advanced any course completion criteria progress calculation

        $generator = $this->getDataGenerator();
        /** @var core_completion_generator $comp_generator */
        $comp_generator = $generator->get_plugin_generator('core_completion');

        $learner = $DB->get_record('user', ['idnumber' => 'progress_learner'], '*', MUST_EXIST);
        $manager = $DB->get_record('user', ['idnumber' => 'progress_manager'], '*', MUST_EXIST);
        $trainer = $generator->create_user(['idnumber' => 'progress_trainer']);
        $simple_course = $DB->get_record('course', ['idnumber' => 'progress_test'], '*', MUST_EXIST);
        $adv_course = $generator->create_course(['idnumber' => 'progress_any_test']);
        $comp_generator->enable_completion_tracking($adv_course);

        // Create a label with manual completion.
        $label_1 = $generator->create_module('label', array(
            'course' => $adv_course,
            'completion' => COMPLETION_TRACKING_MANUAL
        ));
        $label_2 = $generator->create_module('label', array(
            'course' => $adv_course,
            'completion' => COMPLETION_TRACKING_MANUAL
        ));
        $label_3 = $generator->create_module('label', array(
            'course' => $adv_course,
            'completion' => COMPLETION_TRACKING_MANUAL
        ));

        $managerroleid = $DB->get_field('role', 'id', ['shortname' => 'manager']);
        $trainerroleid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);

        $tomorrow = time() + 86400;

        // Now add criteria to the course.
        $comp_generator->set_completion_criteria($adv_course, [
            COMPLETION_CRITERIA_TYPE_SELF => 1, // Its a checkbox.
            COMPLETION_CRITERIA_TYPE_DATE => $tomorrow, // A date well in the future. We'll hack this later.
            COMPLETION_CRITERIA_TYPE_ACTIVITY => [
                'elements' => array($label_1, $label_2, $label_3),
                'aggregationmethod' => COMPLETION_AGGREGATION_ANY
            ],
            COMPLETION_CRITERIA_TYPE_ROLE => [
                'elements' => array($managerroleid, $trainerroleid),
                'aggregationmethod' => COMPLETION_AGGREGATION_ANY
            ],
            COMPLETION_CRITERIA_TYPE_COURSE => [
                'elements' => array($simple_course->id),
                'aggregationmethod' => COMPLETION_AGGREGATION_ANY
            ]
        ]);
        // All must be completed.
        $comp_generator->set_aggregation_method(COMPLETION_AGGREGATION_ALL);

        // Enrol them in the course with appropriate perms.
        $generator->enrol_user($learner->id, $adv_course->id, 'student');
        $generator->enrol_user($trainer->id, $adv_course->id, $trainerroleid);
        $generator->enrol_user($manager->id, $adv_course->id, $managerroleid);

        $completioninfo = new completion_info($adv_course);
        $activities = $completioninfo->get_activities();
        self::assertCount(3, $activities);

        $criteria = $completioninfo->get_criteria();
        self::assertCount(8, $criteria);

        $criteria_self = null;
        $criteria_date = null;
        $criteria_activity = null;
        $criteria_role = null;
        foreach ($criteria as $criterion) {
            switch (get_class($criterion)) {
                case 'completion_criteria_date':
                    $criteria_date = $criterion;
                    break;
            }
        }

        // Check progress info.
        $progressinfo = $completioninfo->get_progressinfo();
        self::assertInstanceOf('\totara_core\progressinfo\progressinfo', $progressinfo);
        self::assertSame(0, $progressinfo->get_weight());
        self::assertSame(0.0, $progressinfo->get_score());
        self::assertEquals('', $progressinfo->get_customdata());
        $progresscriteria = $progressinfo->get_all_criteria();
        self::assertCount(5, $progresscriteria);
        $totalcount = $this->dive_count($progressinfo);
        self::assertSame(11, $totalcount); // 5 first level, 6 second level.

        // In total there are 5 criteria that have to be completed.
        // There are 8 criteria and aggregation is "any".
        // Three have to be completed, the there are three of which one must be completed, and finally two of which one must
        // be completed.
        $total = 5;

        // NOTE: To begin with the user has already completed the simple course!
        $completion = new completion_completion(['userid' => $learner->id, 'course' => $adv_course->id]);
        self::assertFalse($completion->is_complete());
        self::assertSame((int)floor(1/$total * 100), $completion->get_percentagecomplete());

        $cache = cache::make('totara_core', 'completion_progressinfo');
        $cachedata = $cache->get("{$adv_course->id}_{$learner->id}");
        self::assertNotFalse($cachedata);

        // There are at this point 4 criteria to complete.
        // Of the 4, one has 2 subcriteria, and one has 3.
        // Overall total criteria taking an equal share is 7.
        $comp_generator->complete_activity($adv_course->id, $learner->id, $label_1->cmid);
        // We have to get a new completion_completion instance, as the state has changed.
        $completion = new completion_completion(['userid' => $learner->id, 'course' => $adv_course->id]);
        self::assertFalse($completion->is_complete());
        self::assertSame((int)floor(2/$total * 100), $completion->get_percentagecomplete());
        self::assertSame((int)floor(2/$total * 100), $completion->get_progressinfo()->get_percentagecomplete());

        $comp_generator->complete_by_role($adv_course, $learner->id, $managerroleid);
        $completion = new completion_completion(['userid' => $learner->id, 'course' => $adv_course->id]);
        self::assertFalse($completion->is_complete());
        self::assertSame((int)floor(3/$total * 100), $completion->get_percentagecomplete());
        self::assertSame((int)floor(3/$total * 100), $completion->get_progressinfo()->get_percentagecomplete());

        $comp_generator->complete_by_self($adv_course, $learner->id);
        $completion = new completion_completion(['userid' => $learner->id, 'course' => $adv_course->id]);
        self::assertFalse($completion->is_complete());
        self::assertSame((int)floor(4/$total * 100), $completion->get_percentagecomplete());
        self::assertSame((int)floor(4/$total * 100), $completion->get_progressinfo()->get_percentagecomplete());

        $comp_generator->complete_by_role($adv_course, $learner->id, $trainerroleid);
        $completion = new completion_completion(['userid' => $learner->id, 'course' => $adv_course->id]);
        self::assertFalse($completion->is_complete());
        self::assertSame((int)floor(4/$total * 100), $completion->get_percentagecomplete());
        self::assertSame((int)floor(4/$total * 100), $completion->get_progressinfo()->get_percentagecomplete());

        $comp_generator->complete_activity($adv_course->id, $learner->id, $label_2->cmid);
        // We have to get a new completion_completion instance, as the state has changed.
        $completion = new completion_completion(['userid' => $learner->id, 'course' => $adv_course->id]);
        self::assertFalse($completion->is_complete());
        self::assertSame((int)floor(4/$total * 100), $completion->get_percentagecomplete());
        self::assertSame((int)floor(4/$total * 100), $completion->get_progressinfo()->get_percentagecomplete());

        // Set the date criteria back and then execute the criterion's cron.
        $DB->set_field('course_completion_criteria', 'timeend', time() - 86400, ['id' => $criteria_date->id]);
        $criteria_date->cron();

        $completion = new completion_completion(['userid' => $learner->id, 'course' => $adv_course->id]);
        self::assertTrue($completion->is_complete());
        self::assertSame(100, $completion->get_percentagecomplete());
        self::assertSame(100, $completion->get_progressinfo()->get_percentagecomplete());

        $comp_generator->complete_activity($adv_course->id, $learner->id, $label_3->cmid);
        // We have to get a new completion_completion instance, as the state has changed.
        $completion = new completion_completion(['userid' => $learner->id, 'course' => $adv_course->id]);
        self::assertTrue($completion->is_complete());
        self::assertSame(100, $completion->get_percentagecomplete());
        self::assertSame(100, $completion->get_progressinfo()->get_percentagecomplete());
    }

    public function test_course_completion_via_rpl_progress_calculation() {
        global $DB;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        /** @var core_completion_generator $comp_generator */
        $comp_generator = $generator->get_plugin_generator('core_completion');

        // Create a course
        $course = $generator->create_course(['idnumber' => 'progress_rpl_test']);
        $comp_generator->enable_completion_tracking($course);

        // Create a label with manual completion.
        $label_1 = $generator->create_module('label', array(
            'course' => $course,
            'completion' => COMPLETION_TRACKING_MANUAL
        ));
        $label_2 = $generator->create_module('label', array(
            'course' => $course,
            'completion' => COMPLETION_TRACKING_MANUAL
        ));
        $label_3 = $generator->create_module('label', array(
            'course' => $course,
            'completion' => COMPLETION_TRACKING_MANUAL
        ));

        // Now add criteria to the course.
        $comp_generator->set_completion_criteria($course, [
            COMPLETION_CRITERIA_TYPE_ACTIVITY => [
                'elements' => array($label_1, $label_2, $label_3),
                'aggregationmethod' => COMPLETION_AGGREGATION_ALL
            ],
        ]);

        // All must be completed.
        $comp_generator->set_aggregation_method(COMPLETION_AGGREGATION_ALL);

        // Create a learner and enrol it in the course
        $learner = $generator->create_user(['idnumber' => 'progress_learner']);
        $generator->enrol_user($learner->id, $course->id, 'student');

        // Test the setup we've created is as we expect. Semi testing the generators.
        $this::assertSame($DB->get_field('course', 'fullname', ['id' => $course->id]), $course->fullname);
        $this::assertSame($DB->get_field('user', 'username', ['id' => $learner->id]), $learner->username);

        self::assertTrue(completion_criteria::course_has_criteria($course->id));

        $completioninfo = new completion_info($course);
        $activities = $completioninfo->get_activities();
        self::assertCount(3, $activities);

        $cache = cache::make('totara_core', 'completion_progressinfo');
        $cachekey = "{$course->id}_{$learner->id}";
        $cache->delete($cachekey);

        $completion = new completion_completion(['userid' => $learner->id, 'course' => $course->id]);
        self::assertFalse($completion->is_complete());
        self::assertSame(0, $completion->get_percentagecomplete());

        // Cache should exist at this point as we retrieved the percentagecomplete
        $cache = cache::make('totara_core', 'completion_progressinfo');
        $cachedata = $cache->get("{$course->id}_{$learner->id}");
        self::assertNotFalse($cachedata);

        // Now mark complete via rpl without actually completing any criteria
        $completion->rpl = 'Course completed via rpl';
        $completion->status = COMPLETION_STATUS_COMPLETEVIARPL;
        $completion->mark_complete();

        // Cache should not exist at this point as it gets invalidated when the course_completion is updated
        $cache = cache::make('totara_core', 'completion_progressinfo');
        $cachedata = $cache->get("{$course->id}_{$learner->id}");
        self::assertFalse($cachedata);

        // We have to get a new completion_completion instance, as the state has changed.
        $completion = new completion_completion(['userid' => $learner->id, 'course' => $course->id]);
        self::assertTrue($completion->is_complete());
        self::assertSame(100, $completion->get_percentagecomplete());
        self::assertSame(100, $completion->get_progressinfo()->get_percentagecomplete());
        self::assertSame(0, $completion->get_progressinfo()->count_criteria());
    }

    /**
     * @param \totara_core\progressinfo\progressinfo $progressinfo
     * @return int
     */
    private function dive_count(\totara_core\progressinfo\progressinfo $progressinfo) {
        $count = $progressinfo->count_criteria();
        if ($count > 0) {
            foreach ($progressinfo->get_all_criteria() as $criterion) {
                $count += $this->dive_count($criterion);
            }
        }
        return $count;
    }

    public function test_get_criteria_invalid_key() {
        $progressinfo = \totara_core\progressinfo\progressinfo::from_data();
        $criteria = $progressinfo->get_criteria('not_that_droid');
        self::assertFalse($criteria);
    }

    public function test_search_criteria() {
        $progressinfo = \totara_core\progressinfo\progressinfo::from_data();
        $crit_1 = $progressinfo->add_criteria('1');
        $crit_2 = $progressinfo->add_criteria('2');
        $crit_4 = $progressinfo->add_criteria('4');
        foreach ([$crit_1, $crit_2, $crit_4] as $crit) {
            $crit->add_criteria('1');
            $crit->add_criteria('4');
        }

        $result = $progressinfo->search_criteria(1);
        self::assertIsArray($result);
        self::assertCount(4, $result);

        $result = $progressinfo->search_criteria('1');
        self::assertIsArray($result);
        self::assertCount(4, $result);

        $result = $progressinfo->search_criteria(true);
        self::assertIsArray($result);
        self::assertCount(4, $result);

        $result = $progressinfo->search_criteria(2);
        self::assertIsArray($result);
        self::assertCount(1, $result);

        $result = $progressinfo->search_criteria(4);
        self::assertIsArray($result);
        self::assertCount(4, $result);

        $result = $progressinfo->search_criteria(5);
        self::assertIsArray($result);
        self::assertCount(0, $result);
    }

    public function test_replace_criteria() {
        $progressinfo = \totara_core\progressinfo\progressinfo::from_data();
        $crit_1 = $progressinfo->add_criteria('1', \totara_core\progressinfo\progressinfo::AGGREGATE_ANY);
        $crit_1a = \totara_core\progressinfo\progressinfo::from_data(\totara_core\progressinfo\progressinfo::AGGREGATE_ALL);
        $crit_2 = $progressinfo->add_criteria('2', \totara_core\progressinfo\progressinfo::AGGREGATE_ANY);
        $crit_2_1 = $progressinfo->add_criteria('3', \totara_core\progressinfo\progressinfo::AGGREGATE_ANY);
        $crit_2_1a = \totara_core\progressinfo\progressinfo::from_data(\totara_core\progressinfo\progressinfo::AGGREGATE_ALL);

        $crit = $progressinfo->get_criteria(1);
        self::assertSame(\totara_core\progressinfo\progressinfo::AGGREGATE_ANY, $crit->get_agg_method());

        $progressinfo->replace_criteria(1, $crit_1a);

        $crit = $progressinfo->get_criteria(1);
        self::assertSame(\totara_core\progressinfo\progressinfo::AGGREGATE_ALL, $crit->get_agg_method());

        $crits = $progressinfo->search_criteria(3);
        self::assertCount(1, $crits);
        $crit = reset($crits);
        self::assertSame(\totara_core\progressinfo\progressinfo::AGGREGATE_ANY, $crit->get_agg_method());

        $progressinfo->replace_criteria(3, $crit_2_1a);

        $crits = $progressinfo->search_criteria(3);
        self::assertCount(1, $crits);
        $crit = reset($crits);
        self::assertSame(\totara_core\progressinfo\progressinfo::AGGREGATE_ALL, $crit->get_agg_method());
    }

    public function test_attach_criteria() {
        $progressinfo = \totara_core\progressinfo\progressinfo::from_data();
        $crit = \totara_core\progressinfo\progressinfo::from_data(\totara_core\progressinfo\progressinfo::AGGREGATE_ALL);

        self::assertInstanceOf('\totara_core\progressinfo\progressinfo', $progressinfo->attach_criteria(1, $crit));
        // Only once.
        self::assertFalse($progressinfo->attach_criteria(1, $crit));
    }

    public function test_set_agg_method() {
        $progressinfo = \totara_core\progressinfo\progressinfo::from_data();
        $progressinfo->set_agg_method(\totara_core\progressinfo\progressinfo::AGGREGATE_NONE);
        self::assertSame(\totara_core\progressinfo\progressinfo::AGGREGATE_NONE, $progressinfo->get_agg_method());
        $progressinfo->set_agg_method(\totara_core\progressinfo\progressinfo::AGGREGATE_ALL);
        self::assertSame(\totara_core\progressinfo\progressinfo::AGGREGATE_ALL, $progressinfo->get_agg_method());
        $progressinfo->set_agg_method(\totara_core\progressinfo\progressinfo::AGGREGATE_ANY);
        self::assertSame(\totara_core\progressinfo\progressinfo::AGGREGATE_ANY, $progressinfo->get_agg_method());

        self::expectException('coding_exception');
        $progressinfo->set_agg_method(-1);
    }

    public function test_is_enabled() {
        $progressinfo = \totara_core\progressinfo\progressinfo::from_data(\totara_core\progressinfo\progressinfo::AGGREGATE_NONE, 0);
        self::assertFalse($progressinfo->is_enabled());
        $progressinfo->set_weight(1);
        self::assertFalse($progressinfo->is_enabled());
        $progressinfo->add_criteria(1);
        self::assertTrue($progressinfo->is_enabled());
        $progressinfo->set_weight(0);
        self::assertFalse($progressinfo->is_enabled());
    }
}


