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
 * @package totara_completioneditor
 */

defined('MOODLE_INTERNAL') || die();

use totara_completioneditor\course_editor;

global $CFG;
require_once($CFG->dirroot . '/completion/completion_completion.php');

/**
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose totara_completioneditor_course_editor_testcase totara/completioneditor/tests/course_editor_test.php
 */
class totara_completioneditor_course_editor_testcase extends advanced_testcase {

    /**
     * Just a basic test to show that it doesn't fall over.
     */
    public function test_get_error_solution() {
        $solution = course_editor::get_error_solution('imaginaryproblemkey');
        $this->assertContains('There is no specific information', $solution);
    }

    /**
     * Just a basic test to show that the data returned relates directly to the params provided.
     */
    public function test_get_all_progs_and_certs() {
        $this->resetAfterTest(true);

        /** @var totara_program_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('totara_program');

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $prog1 = $programgenerator->create_program();
        $cert1id = $programgenerator->create_certification();
        $cert1 = new program($cert1id);
        $programgenerator->add_courses_and_courseset_to_program($prog1, array(array($course1)));
        $programgenerator->add_courses_and_courseset_to_program($cert1, array(array($course2)), CERTIFPATH_RECERT);

        $programgenerator->assign_program($prog1->id, array($user1->id));
        $programgenerator->assign_program($cert1->id, array($user2->id));

        list($resultprogs, $resultcerts) = course_editor::get_all_progs_and_certs($course1->id, $user1->id);
        $this->assertNotEmpty($resultprogs);
        $this->assertEmpty($resultcerts);
        list($resultprogs, $resultcerts) = course_editor::get_all_progs_and_certs($course1->id, $user2->id);
        $this->assertEmpty($resultprogs);
        $this->assertEmpty($resultcerts);
        list($resultprogs, $resultcerts) = course_editor::get_all_progs_and_certs($course2->id, $user1->id);
        $this->assertEmpty($resultprogs);
        $this->assertEmpty($resultcerts);
        list($resultprogs, $resultcerts) = course_editor::get_all_progs_and_certs($course2->id, $user2->id);
        $this->assertEmpty($resultprogs);
        $this->assertNotEmpty($resultcerts);
    }

    /**
     * Just a basic test to show that the data returned relates directly to the params provided.
     */
    public function test_get_orphaned_criteria() {
        global $DB;

        $this->resetAfterTest(true);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $ccc = new stdClass();
        $ccc->course = $course1->id;
        $ccc->criteriatype = 567;
        $cccid = $DB->insert_record('course_completion_criteria', $ccc);

        // Selected.
        $cccc = new stdClass();
        $cccc->userid = $user1->id;
        $cccc->course = $course1->id;
        $cccc->criteriaid = 123;
        $DB->insert_record('course_completion_crit_compl', $cccc);

        // Not selected because it's not orphaned.
        $cccc = new stdClass();
        $cccc->userid = $user1->id;
        $cccc->course = $course1->id;
        $cccc->criteriaid = $cccid;
        $DB->insert_record('course_completion_crit_compl', $cccc);

        // Different course.
        $cccc = new stdClass();
        $cccc->userid = $user1->id;
        $cccc->course = $course2->id;
        $cccc->criteriaid = 234;
        $DB->insert_record('course_completion_crit_compl', $cccc);

        // Different user.
        $cccc = new stdClass();
        $cccc->userid = $user2->id;
        $cccc->course = $course1->id;
        $cccc->criteriaid = 345;
        $DB->insert_record('course_completion_crit_compl', $cccc);

        $results = course_editor::get_orphaned_criteria($course1->id, $user1->id);

        $this->assertCount(1, $results);
        $row = reset($results);
        $this->assertEquals(123, $row->criteriaid);
    }

    /**
     * Make sure that get_current_completion_from_data gives the correct results under various circumstances.
     */
    public function test_get_current_completion_from_data() {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $cc = core_completion\helper::load_course_completion($course->id, $user->id);

        // Test with most fields empty - the fields should come back defined but empty.
        $data = new stdClass();
        $data->courseid = $course->id;
        $data->userid = $user->id;
        $data->courseid = $course->id;
        $data->status = 123;

        $expectedcompletion = new stdClass();
        $expectedcompletion->id = $cc->id;
        $expectedcompletion->course = $course->id;
        $expectedcompletion->userid = $user->id;
        $expectedcompletion->status = 123;
        $expectedcompletion->timeenrolled = 0;
        $expectedcompletion->timestarted = 0;
        $expectedcompletion->timecompleted = null;
        $expectedcompletion->rpl = null;
        $expectedcompletion->rplgrade = null;

        $timebefore = time();
        $actualcompletion = course_editor::get_current_completion_from_data($data);
        $timeafter = time();

        $this->assertGreaterThanOrEqual($timebefore, $actualcompletion->timemodified);
        $this->assertLessThanOrEqual($timeafter, $actualcompletion->timemodified);
        unset($actualcompletion->timemodified);
        $this->assertEquals($expectedcompletion, $actualcompletion);

        // Test with all fields defined.
        $data = new stdClass();
        $data->courseid = $course->id;
        $data->userid = $user->id;
        $data->courseid = $course->id;
        $data->status = 123;
        $data->timeenrolled = 234;
        $data->timestarted = 345;
        $data->timecompleted = 456;
        $data->rpl = 'some reason';
        $data->rplgrade = 567;
        $data->timemodified = 999;

        $expectedcompletion = new stdClass();
        $expectedcompletion->id = $cc->id;
        $expectedcompletion->course = $course->id;
        $expectedcompletion->userid = $user->id;
        $expectedcompletion->status = 123;
        $expectedcompletion->timeenrolled = 234;
        $expectedcompletion->timestarted = 345;
        $expectedcompletion->timecompleted = 456;
        $expectedcompletion->rpl = 'some reason';
        $expectedcompletion->rplgrade = 567;

        $timebefore = time();
        $actualcompletion = course_editor::get_current_completion_from_data($data);
        $timeafter = time();

        $this->assertGreaterThanOrEqual($timebefore, $actualcompletion->timemodified);
        $this->assertLessThanOrEqual($timeafter, $actualcompletion->timemodified);
        unset($actualcompletion->timemodified);
        $this->assertEquals($expectedcompletion, $actualcompletion);

        // Test the rpl field when it's an empty string - should revert to null.
        $data = new stdClass();
        $data->courseid = $course->id;
        $data->userid = $user->id;
        $data->courseid = $course->id;
        $data->status = 123;
        $data->timeenrolled = null;
        $data->timestarted = null;
        $data->timecompleted = null;
        $data->rpl = '';
        $data->rplgrade = null;

        $expectedcompletion = new stdClass();
        $expectedcompletion->id = $cc->id;
        $expectedcompletion->course = $course->id;
        $expectedcompletion->userid = $user->id;
        $expectedcompletion->status = 123;
        $expectedcompletion->timeenrolled = 0;
        $expectedcompletion->timestarted = 0;
        $expectedcompletion->timecompleted = null;
        $expectedcompletion->rpl = null;
        $expectedcompletion->rplgrade = null;

        $actualcompletion = course_editor::get_current_completion_from_data($data);

        unset($actualcompletion->timemodified);
        $this->assertEquals($expectedcompletion, $actualcompletion);

        // Test the rpl field when it's '0' - should NOT revert to null.
        $data = new stdClass();
        $data->courseid = $course->id;
        $data->userid = $user->id;
        $data->courseid = $course->id;
        $data->status = 123;
        $data->rpl = '0';

        $expectedcompletion = new stdClass();
        $expectedcompletion->id = $cc->id;
        $expectedcompletion->course = $course->id;
        $expectedcompletion->userid = $user->id;
        $expectedcompletion->status = 123;
        $expectedcompletion->timeenrolled = 0;
        $expectedcompletion->timestarted = 0;
        $expectedcompletion->timecompleted = null;
        $expectedcompletion->rpl = '0';
        $expectedcompletion->rplgrade = null;

        $actualcompletion = course_editor::get_current_completion_from_data($data);

        unset($actualcompletion->timemodified);
        $this->assertEquals($expectedcompletion, $actualcompletion);
    }

    /**
     * Make sure that get_module_and_criteria_from_data gives the correct results under various circumstances.
     *
     * This tests when only cmc data is provided - an activity that's not part of course completion.
     */
    public function test_get_module_and_criteria_from_data_cmc_only() {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        // Add a quiz.
        /** @var mod_quiz_generator $quizgenerator */
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance(array('course' => $course->id, 'questionsperpage' => 3, 'grade' => 100.0));

        // Add a facetoface.
        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $facetoface = $facetofacegenerator->create_instance(array('course' => $course->id));

        // Test f2f (use timecompleted) with most fields empty - the fields should come back defined but empty.
        $data = new stdClass();
        $data->userid = $user->id;
        $data->cmid = $facetoface->cmid;
        $data->completionstate = 234;
        $data->viewed = 345;

        $expectedcmc = new stdClass();
        $expectedcmc->coursemoduleid = $facetoface->cmid;
        $expectedcmc->userid = $user->id;
        $expectedcmc->completionstate = 234;
        $expectedcmc->viewed = 345;
        $expectedcmc->timecompleted = null;

        $timebefore = time();
        list($actualcmc, $actualcccc) = course_editor::get_module_and_criteria_from_data($data);
        $timeafter = time();

        $this->assertEmpty($actualcccc);
        $this->assertGreaterThanOrEqual($timebefore, $actualcmc->timemodified);
        $this->assertLessThanOrEqual($timeafter, $actualcmc->timemodified);
        unset($actualcmc->timemodified);
        $this->assertEquals($expectedcmc, $actualcmc);

        // Test f2f (use timecompleted) with all fields filled.
        $data = new stdClass();
        $data->userid = $user->id;
        $data->cmid = $facetoface->cmid;
        $data->completionstate = 234;
        $data->viewed = 345;
        $data->cmctimecompleted = 567;

        $expectedcmc = new stdClass();
        $expectedcmc->coursemoduleid = $facetoface->cmid;
        $expectedcmc->userid = $user->id;
        $expectedcmc->completionstate = 234;
        $expectedcmc->viewed = 345;
        $expectedcmc->timecompleted = 567;

        $timebefore = time();
        list($actualcmc, $actualcccc) = course_editor::get_module_and_criteria_from_data($data);
        $timeafter = time();

        $this->assertEmpty($actualcccc);
        $this->assertGreaterThanOrEqual($timebefore, $actualcmc->timemodified);
        $this->assertLessThanOrEqual($timeafter, $actualcmc->timemodified);
        unset($actualcmc->timemodified);
        $this->assertEquals($expectedcmc, $actualcmc);

        // Test quiz (use timemodified) with most fields empty - the fields should come back defined but empty.
        $data = new stdClass();
        $data->userid = $user->id;
        $data->cmid = $quiz->cmid;
        $data->completionstate = 234;
        $data->viewed = 345;

        $expectedcmc = new stdClass();
        $expectedcmc->coursemoduleid = $quiz->cmid;
        $expectedcmc->userid = $user->id;
        $expectedcmc->completionstate = 234;
        $expectedcmc->viewed = 345;
        $expectedcmc->timecompleted = null;

        $timebefore = time();
        list($actualcmc, $actualcccc) = course_editor::get_module_and_criteria_from_data($data);
        $timeafter = time();

        $this->assertEmpty($actualcccc);
        $this->assertGreaterThanOrEqual($timebefore, $actualcmc->timemodified);
        $this->assertLessThanOrEqual($timeafter, $actualcmc->timemodified);
        unset($actualcmc->timemodified);
        $this->assertEquals($expectedcmc, $actualcmc);

        // Test quiz (use timemodified) with all fields filled.
        $data = new stdClass();
        $data->userid = $user->id;
        $data->cmid = $quiz->cmid;
        $data->completionstate = 234;
        $data->viewed = 345;
        $data->cmctimecompleted = 567;

        $expectedcmc = new stdClass();
        $expectedcmc->coursemoduleid = $quiz->cmid;
        $expectedcmc->userid = $user->id;
        $expectedcmc->completionstate = 234;
        $expectedcmc->viewed = 345;
        $expectedcmc->timemodified = 567;
        $expectedcmc->timecompleted = null;

        list($actualcmc, $actualcccc) = course_editor::get_module_and_criteria_from_data($data);

        $this->assertEmpty($actualcccc);
        $this->assertEquals($expectedcmc, $actualcmc);

        // Test with an existing record, and that having a timecompleted non-zero in a module which isn't supposed to
        // store completion date in timecompleted will save to timecompleted anyway! Prevents breaking customisations.
        $cmc = new stdClass();
        $cmc->coursemoduleid = $quiz->cmid;
        $cmc->userid = $user->id;
        $cmc->completionstate = 1;
        $cmc->viewed = 1;
        $cmc->timemodified = 678;
        $cmc->timecompleted = 789;
        $cmcid = $DB->insert_record('course_modules_completion', $cmc);

        $data = new stdClass();
        $data->userid = $user->id;
        $data->cmid = $quiz->cmid;
        $data->completionstate = 234;
        $data->viewed = 345;
        $data->cmctimecompleted = 567;

        $expectedcmc = new stdClass();
        $expectedcmc->id = $cmcid;
        $expectedcmc->coursemoduleid = $quiz->cmid;
        $expectedcmc->userid = $user->id;
        $expectedcmc->completionstate = 234;
        $expectedcmc->viewed = 345;
        $expectedcmc->timecompleted = 567;

        $timebefore = time();
        list($actualcmc, $actualcccc) = course_editor::get_module_and_criteria_from_data($data);
        $timeafter = time();

        $this->assertEmpty($actualcccc);
        $this->assertGreaterThanOrEqual($timebefore, $actualcmc->timemodified);
        $this->assertLessThanOrEqual($timeafter, $actualcmc->timemodified);
        unset($actualcmc->timemodified);
        $this->assertEquals($expectedcmc, $actualcmc);
    }

    /**
     * Make sure that get_module_and_criteria_from_data gives the correct results under various circumstances.
     *
     * This tests when only cccc data is provided - course completion criteria that is not an activity.
     */
    public function test_get_module_and_criteria_from_data_cccc_only() {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        // Test with most fields empty - the fields should come back defined but empty.
        $data = new stdClass();
        $data->userid = $user->id;
        $data->courseid = $course->id;
        $data->criteriaid = 123;

        $expectedcccc = new stdClass();
        $expectedcccc->userid = $user->id;
        $expectedcccc->course = $course->id;
        $expectedcccc->criteriaid = 123;
        $expectedcccc->rpl = null;
        $expectedcccc->timecompleted = null;

        list($actualcmc, $actualcccc) = course_editor::get_module_and_criteria_from_data($data);

        $this->assertEmpty($actualcmc);
        $this->assertEquals($expectedcccc, $actualcccc);

        // Test with all fields filled.
        $data = new stdClass();
        $data->userid = $user->id;
        $data->courseid = $course->id;
        $data->criteriaid = 123;
        $data->rpl = 'some reason';
        $data->cctimecompleted = 234;

        $expectedcccc = new stdClass();
        $expectedcccc->userid = $user->id;
        $expectedcccc->course = $course->id;
        $expectedcccc->criteriaid = 123;
        $expectedcccc->rpl = 'some reason';
        $expectedcccc->timecompleted = 234;

        list($actualcmc, $actualcccc) = course_editor::get_module_and_criteria_from_data($data);

        $this->assertEmpty($actualcmc);
        $this->assertEquals($expectedcccc, $actualcccc);

        // Test when the record already exists.
        $cccc = new stdClass();
        $cccc->course = $course->id;
        $cccc->userid = $user->id;
        $cccc->criteriaid = 123;
        $ccccid = $DB->insert_record('course_completion_crit_compl', $cccc);

        $data = new stdClass();
        $data->userid = $user->id;
        $data->courseid = $course->id;
        $data->criteriaid = 123;
        $data->rpl = 'some reason';
        $data->cctimecompleted = 234;

        $expectedcccc = new stdClass();
        $expectedcccc->id = $ccccid;
        $expectedcccc->userid = $user->id;
        $expectedcccc->course = $course->id;
        $expectedcccc->criteriaid = 123;
        $expectedcccc->rpl = 'some reason';
        $expectedcccc->timecompleted = 234;

        list($actualcmc, $actualcccc) = course_editor::get_module_and_criteria_from_data($data);

        $this->assertEmpty($actualcmc);
        $this->assertEquals($expectedcccc, $actualcccc);
    }

    /**
     * Make sure that get_module_and_criteria_from_data gives the correct results under various circumstances.
     *
     * This tests when cmc and cccc data are provided - course completion criteria that is an activity.
     */
    public function test_get_module_and_criteria_from_data_both_at_once() {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        // Add a facetoface.
        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $facetoface = $facetofacegenerator->create_instance(array('course' => $course->id));

        // Test with most fields empty - the fields should come back defined but empty.
        $data = new stdClass();
        $data->userid = $user->id;
        $data->cmid = $facetoface->cmid;
        $data->completionstate = 234;
        $data->viewed = 345;
        $data->courseid = $course->id;
        $data->criteriaid = 456;
        $data->editingmode = \totara_completioneditor\form\course_completion::EDITINGMODESEPARATE;

        $expectedcmc = new stdClass();
        $expectedcmc->coursemoduleid = $facetoface->cmid;
        $expectedcmc->userid = $user->id;
        $expectedcmc->completionstate = 234;
        $expectedcmc->viewed = 345;
        $expectedcmc->timecompleted = null;

        $expectedcccc = new stdClass();
        $expectedcccc->userid = $user->id;
        $expectedcccc->course = $course->id;
        $expectedcccc->criteriaid = 456;
        $expectedcccc->rpl = null;
        $expectedcccc->timecompleted = null;

        $timebefore = time();
        list($actualcmc, $actualcccc) = course_editor::get_module_and_criteria_from_data($data);
        $timeafter = time();

        $this->assertGreaterThanOrEqual($timebefore, $actualcmc->timemodified);
        $this->assertLessThanOrEqual($timeafter, $actualcmc->timemodified);
        unset($actualcmc->timemodified);
        $this->assertEquals($expectedcmc, $actualcmc);
        $this->assertEquals($expectedcccc, $actualcccc);

        // Test with all fields filled and editing mode separate.
        $data = new stdClass();
        $data->userid = $user->id;
        $data->cmid = $facetoface->cmid;
        $data->completionstate = 234;
        $data->viewed = 345;
        $data->courseid = $course->id;
        $data->criteriaid = 456;
        $data->cmctimecompleted = 567;
        $data->cctimecompleted = 678;
        $data->rpl = 'some reason';
        $data->editingmode = \totara_completioneditor\form\course_completion::EDITINGMODESEPARATE;

        $expectedcmc = new stdClass();
        $expectedcmc->coursemoduleid = $facetoface->cmid;
        $expectedcmc->userid = $user->id;
        $expectedcmc->completionstate = 234;
        $expectedcmc->viewed = 345;
        $expectedcmc->timecompleted = 567;

        $expectedcccc = new stdClass();
        $expectedcccc->userid = $user->id;
        $expectedcccc->course = $course->id;
        $expectedcccc->criteriaid = 456;
        $expectedcccc->rpl = 'some reason';;
        $expectedcccc->timecompleted = 678;

        $timebefore = time();
        list($actualcmc, $actualcccc) = course_editor::get_module_and_criteria_from_data($data);
        $timeafter = time();

        $this->assertGreaterThanOrEqual($timebefore, $actualcmc->timemodified);
        $this->assertLessThanOrEqual($timeafter, $actualcmc->timemodified);
        unset($actualcmc->timemodified);
        $this->assertEquals($expectedcmc, $actualcmc);
        $this->assertEquals($expectedcccc, $actualcccc);

        // Test with all fields filled and editing mode using module.
        $data = new stdClass();
        $data->userid = $user->id;
        $data->cmid = $facetoface->cmid;
        $data->completionstate = 234;
        $data->viewed = 345;
        $data->courseid = $course->id;
        $data->criteriaid = 456;
        $data->cmctimecompleted = 567;
        $data->cctimecompleted = 0; // Is ignored.
        $data->rpl = 'some reason';
        $data->editingmode = \totara_completioneditor\form\course_completion::EDITINGMODEUSEMODULE;

        $expectedcmc = new stdClass();
        $expectedcmc->coursemoduleid = $facetoface->cmid;
        $expectedcmc->userid = $user->id;
        $expectedcmc->completionstate = 234;
        $expectedcmc->viewed = 345;
        $expectedcmc->timecompleted = 567;

        $expectedcccc = new stdClass();
        $expectedcccc->userid = $user->id;
        $expectedcccc->course = $course->id;
        $expectedcccc->criteriaid = 456;
        $expectedcccc->rpl = 'some reason';;
        $expectedcccc->timecompleted = 567;

        $timebefore = time();
        list($actualcmc, $actualcccc) = course_editor::get_module_and_criteria_from_data($data);
        $timeafter = time();

        $this->assertGreaterThanOrEqual($timebefore, $actualcmc->timemodified);
        $this->assertLessThanOrEqual($timeafter, $actualcmc->timemodified);
        unset($actualcmc->timemodified);
        $this->assertEquals($expectedcmc, $actualcmc);
        $this->assertEquals($expectedcccc, $actualcccc);
    }
}