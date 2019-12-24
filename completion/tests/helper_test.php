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

use core_completion\helper;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once("{$CFG->dirroot}/completion/completion_completion.php");

/**
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose core_completion_helper_testcase completion/tests/helper_test.php
 */
class core_completion_helper_testcase extends advanced_testcase {

    public function test_write_course_completion() {
        global $DB, $USER;

        $this->resetAfterTest(true);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        // Insert without problem inserts only the specified record and writes default log.
        $this->assertEquals(0, $DB->count_records('course_completions'));
        $this->assertEquals(0, $DB->count_records('course_completion_log'));
        $coursecompletion = new stdClass();
        $coursecompletion->status = COMPLETION_STATUS_NOTYETSTARTED;
        $coursecompletion->course = $course1->id;
        $coursecompletion->userid = $user1->id;
        $this->assertNotFalse(helper::write_course_completion($coursecompletion));
        $this->assertEquals(1, $DB->count_records('course_completions'));
        $this->assertEquals(1, $DB->count_records('course_completions',
            array('course' => $course1->id, 'userid' => $user1->id, 'status' => COMPLETION_STATUS_NOTYETSTARTED)));
        $this->assertEquals(1, $DB->count_records('course_completion_log'));
        $log = $DB->get_record('course_completion_log', array('courseid' => $course1->id, 'userid' => $user1->id));
        $this->assertEquals($USER->id, $log->changeuserid);
        $this->assertContains('Current completion created', $log->description);

        // Insert without problem inserts only the specified record and writes specified log.
        $coursecompletion = new stdClass();
        $coursecompletion->status = COMPLETION_STATUS_NOTYETSTARTED;
        $coursecompletion->course = $course2->id;
        $coursecompletion->userid = $user1->id;
        $this->assertNotFalse(helper::write_course_completion($coursecompletion, 'custom message'));
        $this->assertEquals(2, $DB->count_records('course_completions'));
        $this->assertEquals(1, $DB->count_records('course_completions',
            array('course' => $course2->id, 'userid' => $user1->id, 'status' => COMPLETION_STATUS_NOTYETSTARTED)));
        $this->assertEquals(2, $DB->count_records('course_completion_log'));
        $log = $DB->get_record('course_completion_log', array('courseid' => $course2->id, 'userid' => $user1->id));
        $this->assertEquals($USER->id, $log->changeuserid);
        $this->assertContains('custom message', $log->description);

        // Insert some more control data.
        $coursecompletion = new stdClass();
        $coursecompletion->status = COMPLETION_STATUS_NOTYETSTARTED;
        $coursecompletion->course = $course1->id;
        $coursecompletion->userid = $user2->id;
        $this->assertNotFalse(helper::write_course_completion($coursecompletion));
        $this->assertEquals(3, $DB->count_records('course_completions'));

        // Insert with existing record fails.
        $coursecompletion = new stdClass();
        $coursecompletion->status = COMPLETION_STATUS_NOTYETSTARTED;
        $coursecompletion->course = $course1->id;
        $coursecompletion->userid = $user1->id;
        try {
            helper::write_course_completion($coursecompletion);
            $this->fail("Shouldn't reach this code, exception not triggered!");
        } catch (coding_exception $e) {
            $this->assertContains("Course_completions already exists", $e->getMessage());
        }
        $this->assertEquals(3, $DB->count_records('course_completions'));

        // Make sure that the existing records aren't touched in any of the failing updates.
        $expectedrecords = $DB->get_records('course_completions');

        // Update without matching id fails.
        $coursecompletion->id = 999;
        $coursecompletion->course = $course1->id;
        $coursecompletion->userid = $user1->id;
        try {
            helper::write_course_completion($coursecompletion);
            $this->fail("Shouldn't reach this code, exception not triggered!");
        } catch (coding_exception $e) {
            $this->assertContains("Either course_completions doesn't exist or belongs to a different user or course", $e->getMessage());
        }
        $this->assertEquals($expectedrecords, $DB->get_records('course_completions'));

        // Update without matching course fails.
        $coursecompletion = helper::load_course_completion($course1->id, $user1->id);
        $coursecompletion->course = 888;
        try {
            helper::write_course_completion($coursecompletion);
            $this->fail("Shouldn't reach this code, exception not triggered!");
        } catch (coding_exception $e) {
            $this->assertContains("Either course_completions doesn't exist or belongs to a different user or course", $e->getMessage());
        }
        $this->assertEquals($expectedrecords, $DB->get_records('course_completions'));

        // Update without matching user fails.
        $coursecompletion->course = $course1->id;
        $coursecompletion->userid = 777;
        try {
            helper::write_course_completion($coursecompletion);
            $this->fail("Shouldn't reach this code, exception not triggered!");
        } catch (coding_exception $e) {
            $this->assertContains("Either course_completions doesn't exist or belongs to a different user or course", $e->getMessage());
        }
        $this->assertEquals($expectedrecords, $DB->get_records('course_completions'));

        // Updated with invalid data fails and creates log.
        $DB->delete_records('course_completion_log');
        $coursecompletion = helper::load_course_completion($course1->id, $user1->id);
        $coursecompletion->status = -1;
        $this->assertFalse(helper::write_course_completion($coursecompletion));
        $this->assertEquals($expectedrecords, $DB->get_records('course_completions'));
        $this->assertEquals(1, $DB->count_records('course_completion_log'));
        $log = $DB->get_record('course_completion_log', array('courseid' => $course1->id, 'userid' => $user1->id));
        $this->assertEquals($USER->id, $log->changeuserid);
        $this->assertContains('An error occurred. Message of caller was', $log->description);
        $this->assertContains('Current completion updated', $log->description);

        // Insert with invalid data fails and creates log.
        $DB->delete_records('course_completion_log');
        $coursecompletion = new \stdClass();
        $coursecompletion->course = $course2->id;
        $coursecompletion->userid = $user2->id;
        $coursecompletion->status = -1;
        $this->assertFalse(helper::write_course_completion($coursecompletion));
        $this->assertEquals($expectedrecords, $DB->get_records('course_completions'));
        $this->assertEquals(1, $DB->count_records('course_completion_log'));
        $log = $DB->get_record('course_completion_log', array('courseid' => $course2->id, 'userid' => $user2->id));
        $this->assertEquals($USER->id, $log->changeuserid);
        $this->assertContains('An error occurred. Message of caller was', $log->description);
        $this->assertContains('Current completion created', $log->description);

        // Update without problem updates only specified record and creates log.
        $DB->delete_records('course_completion_log');
        $coursecompletion = helper::load_course_completion($course1->id, $user1->id);
        $ccid = $coursecompletion->id;
        $expectedrecords[$ccid]->status = COMPLETION_STATUS_COMPLETE;
        $expectedrecords[$ccid]->timecompleted = 567;
        $this->assertNotFalse(helper::write_course_completion($expectedrecords[$ccid]));
        $this->assertEquals($expectedrecords, $DB->get_records('course_completions'));
        $this->assertEquals(1, $DB->count_records('course_completion_log'));
        $log = $DB->get_record('course_completion_log', array('courseid' => $course1->id, 'userid' => $user1->id));
        $this->assertEquals($USER->id, $log->changeuserid);
        $this->assertContains('Current completion updated', $log->description);

        // Update with custom log message.
        $DB->delete_records('course_completion_log');
        $expectedrecords[$ccid]->timecompleted = 678;
        $this->assertNotFalse(helper::write_course_completion($expectedrecords[$ccid], 'custom message'));
        $this->assertEquals($expectedrecords, $DB->get_records('course_completions'));
        $this->assertEquals(1, $DB->count_records('course_completion_log'));
        $log = $DB->get_record('course_completion_log', array('courseid' => $course1->id, 'userid' => $user1->id));
        $this->assertEquals($USER->id, $log->changeuserid);
        $this->assertContains('custom message', $log->description);
    }

    public function test_write_course_completion_history() {
        global $DB, $USER;

        $this->resetAfterTest(true);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        // Insert without problem inserts only the specified record and writes default log.
        $this->assertEquals(0, $DB->count_records('course_completion_history'));
        $this->assertEquals(0, $DB->count_records('course_completion_log'));
        $cch = new stdClass();
        $cch->timecompleted = 123;
        $cch->courseid = $course1->id;
        $cch->userid = $user1->id;
        $this->assertNotFalse(helper::write_course_completion_history($cch));
        $this->assertEquals(1, $DB->count_records('course_completion_history'));
        $this->assertEquals(1, $DB->count_records('course_completion_history',
            array('courseid' => $course1->id, 'userid' => $user1->id, 'timecompleted' => 123)));
        $this->assertEquals(1, $DB->count_records('course_completion_log'));
        $log = $DB->get_record('course_completion_log', array('courseid' => $course1->id, 'userid' => $user1->id));
        $this->assertEquals($USER->id, $log->changeuserid);
        $this->assertContains('History completion created', $log->description);

        // Insert without problem inserts only the specified record and writes specified log.
        $cch = new stdClass();
        $cch->timecompleted = 123;
        $cch->courseid = $course2->id;
        $cch->userid = $user1->id;
        $this->assertNotFalse(helper::write_course_completion_history($cch, 'custom message'));
        $this->assertEquals(2, $DB->count_records('course_completion_history'));
        $this->assertEquals(1, $DB->count_records('course_completion_history',
            array('courseid' => $course2->id, 'userid' => $user1->id, 'timecompleted' => 123)));
        $this->assertEquals(2, $DB->count_records('course_completion_log'));
        $log = $DB->get_record('course_completion_log', array('courseid' => $course2->id, 'userid' => $user1->id));
        $this->assertEquals($USER->id, $log->changeuserid);
        $this->assertContains('custom message', $log->description);

        // Insert some more control data.
        $cch = new stdClass();
        $cch->timecompleted = 123;
        $cch->courseid = $course1->id;
        $cch->userid = $user2->id;
        $this->assertNotFalse(helper::write_course_completion_history($cch));
        $this->assertEquals(3, $DB->count_records('course_completion_history'));

        // Insert with existing record succeeds.
        $cch = new stdClass();
        $cch->timecompleted = 123;
        $cch->courseid = $course1->id;
        $cch->userid = $user1->id;
        $this->assertNotFalse(helper::write_course_completion_history($cch));
        $this->assertEquals(4, $DB->count_records('course_completion_history'));
        $this->assertEquals(2, $DB->count_records('course_completion_history',
            array('courseid' => $course1->id, 'userid' => $user1->id)));

        // Make sure that the existing records aren't touched in any of the failing updates.
        $expectedrecords = $DB->get_records('course_completion_history');

        // Update without matching id fails.
        $cch->id = 999;
        try {
            helper::write_course_completion_history($cch);
            $this->fail("Shouldn't reach this code, exception not triggered!");
        } catch (coding_exception $e) {
            $this->assertContains("Either course_completion_history doesn't exist or belongs to a different user or course", $e->getMessage());
        }
        $this->assertEquals($expectedrecords, $DB->get_records('course_completion_history'));

        // Update without matching course fails.
        $cch = $DB->get_record('course_completion_history',
            array('courseid' => $course2->id, 'userid' => $user1->id));
        $cch->courseid = 888;
        try {
            helper::write_course_completion_history($cch);
            $this->fail("Shouldn't reach this code, exception not triggered!");
        } catch (coding_exception $e) {
            $this->assertContains("Either course_completion_history doesn't exist or belongs to a different user or course", $e->getMessage());
        }
        $this->assertEquals($expectedrecords, $DB->get_records('course_completion_history'));

        // Update without matching user fails.
        $cch->courseid = $course2->id;
        $cch->userid = 777;
        try {
            helper::write_course_completion_history($cch);
            $this->fail("Shouldn't reach this code, exception not triggered!");
        } catch (coding_exception $e) {
            $this->assertContains("Either course_completion_history doesn't exist or belongs to a different user or course", $e->getMessage());
        }
        $this->assertEquals($expectedrecords, $DB->get_records('course_completion_history'));

        // Update without problem updates only specified record and creates log.
        $DB->delete_records('course_completion_log');
        $cch = $DB->get_record('course_completion_history',
            array('courseid' => $course2->id, 'userid' => $user1->id));
        $cchid = $cch->id;
        $expectedrecords[$cchid]->timecompleted = 567;
        $this->assertNotFalse(helper::write_course_completion_history($expectedrecords[$cchid]));
        $this->assertEquals($expectedrecords, $DB->get_records('course_completion_history'));
        $this->assertEquals(1, $DB->count_records('course_completion_log'));
        $log = $DB->get_record('course_completion_log', array('courseid' => $course2->id, 'userid' => $user1->id));
        $this->assertEquals($USER->id, $log->changeuserid);
        $this->assertContains('History completion updated', $log->description);

        // Update with custom log message.
        $DB->delete_records('course_completion_log');
        $expectedrecords[$cchid]->timecompleted = 567;
        $this->assertNotFalse(helper::write_course_completion_history($expectedrecords[$cchid], 'custom message'));
        $this->assertEquals($expectedrecords, $DB->get_records('course_completion_history'));
        $this->assertEquals(1, $DB->count_records('course_completion_log'));
        $log = $DB->get_record('course_completion_log', array('courseid' => $course2->id, 'userid' => $user1->id));
        $this->assertEquals($USER->id, $log->changeuserid);
        $this->assertContains('custom message', $log->description);
    }

    public function test_write_criteria_completion() {
        global $DB, $USER;

        $this->resetAfterTest(true);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        // Insert without problem inserts only the specified record and writes default log.
        $this->assertEquals(0, $DB->count_records('course_completion_crit_compl'));
        $this->assertEquals(0, $DB->count_records('course_completion_log'));
        $cccc = new stdClass();
        $cccc->timecompleted = 123;
        $cccc->criteriaid = 234;
        $cccc->course = $course1->id;
        $cccc->userid = $user1->id;
        $this->assertNotFalse(helper::write_criteria_completion($cccc));
        $this->assertEquals(1, $DB->count_records('course_completion_crit_compl'));
        $this->assertEquals(1, $DB->count_records('course_completion_crit_compl',
            array('course' => $course1->id, 'userid' => $user1->id, 'timecompleted' => 123)));
        $this->assertEquals(1, $DB->count_records('course_completion_log'));
        $log = $DB->get_record('course_completion_log', array('courseid' => $course1->id, 'userid' => $user1->id));
        $this->assertEquals($USER->id, $log->changeuserid);
        $this->assertContains('Criteria completion created', $log->description);

        // Insert without problem inserts only the specified record and writes specified log.
        $cccc = new stdClass();
        $cccc->timecompleted = 123;
        $cccc->criteriaid = 234;
        $cccc->course = $course2->id;
        $cccc->userid = $user1->id;
        $this->assertNotFalse(helper::write_criteria_completion($cccc, 'custom message'));
        $this->assertEquals(2, $DB->count_records('course_completion_crit_compl'));
        $this->assertEquals(1, $DB->count_records('course_completion_crit_compl',
            array('course' => $course2->id, 'userid' => $user1->id, 'timecompleted' => 123)));
        $this->assertEquals(2, $DB->count_records('course_completion_log'));
        $log = $DB->get_record('course_completion_log', array('courseid' => $course2->id, 'userid' => $user1->id));
        $this->assertEquals($USER->id, $log->changeuserid);
        $this->assertContains('custom message', $log->description);

        // Insert some more control data.
        $cccc = new stdClass();
        $cccc->timecompleted = 123;
        $cccc->criteriaid = 234;
        $cccc->course = $course1->id;
        $cccc->userid = $user2->id;
        $this->assertNotFalse(helper::write_criteria_completion($cccc));
        $this->assertEquals(3, $DB->count_records('course_completion_crit_compl'));

        // Insert with existing record fails.
        $cccc = new stdClass();
        $cccc->timecompleted = 123;
        $cccc->criteriaid = 234;
        $cccc->course = $course1->id;
        $cccc->userid = $user1->id;
        try {
            helper::write_criteria_completion($cccc);
            $this->fail("Shouldn't reach this code, exception not triggered!");
        } catch (coding_exception $e) {
            $this->assertContains("Course_completion_crit_compl already exists", $e->getMessage());
        }
        $this->assertEquals(3, $DB->count_records('course_completion_crit_compl'));

        // Make sure that the existing records aren't touched in any of the failing updates.
        $expectedrecords = $DB->get_records('course_completion_crit_compl');

        // Update without matching id fails.
        $cccc->id = 999;
        try {
            helper::write_criteria_completion($cccc);
            $this->fail("Shouldn't reach this code, exception not triggered!");
        } catch (coding_exception $e) {
            $this->assertContains("Either course_completion_crit_compl doesn't exist or belongs to a different user, course or criteria", $e->getMessage());
        }
        $this->assertEquals($expectedrecords, $DB->get_records('course_completion_crit_compl'));

        // Update without matching course fails.
        $cccc = $DB->get_record('course_completion_crit_compl',
            array('course' => $course2->id, 'userid' => $user1->id));
        $cccc->course = 888;
        try {
            helper::write_criteria_completion($cccc);
            $this->fail("Shouldn't reach this code, exception not triggered!");
        } catch (coding_exception $e) {
            $this->assertContains("Either course_completion_crit_compl doesn't exist or belongs to a different user, course or criteria", $e->getMessage());
        }
        $this->assertEquals($expectedrecords, $DB->get_records('course_completion_crit_compl'));

        // Update without matching user fails.
        $cccc->course = $course2->id;
        $cccc->userid = 777;
        try {
            helper::write_criteria_completion($cccc);
            $this->fail("Shouldn't reach this code, exception not triggered!");
        } catch (coding_exception $e) {
            $this->assertContains("Either course_completion_crit_compl doesn't exist or belongs to a different user, course or criteria", $e->getMessage());
        }
        $this->assertEquals($expectedrecords, $DB->get_records('course_completion_crit_compl'));

        // Update without matching criteriaid fails.
        $cccc->userid = $user1->id;
        $cccc->criteriaid = 666;
        try {
            helper::write_criteria_completion($cccc);
            $this->fail("Shouldn't reach this code, exception not triggered!");
        } catch (coding_exception $e) {
            $this->assertContains("Either course_completion_crit_compl doesn't exist or belongs to a different user, course or criteria", $e->getMessage());
        }
        $this->assertEquals($expectedrecords, $DB->get_records('course_completion_crit_compl'));

        // Update without problem updates only specified record and creates log.
        $DB->delete_records('course_completion_log');
        $cccc = $DB->get_record('course_completion_crit_compl',
            array('course' => $course2->id, 'userid' => $user1->id));
        $ccccid = $cccc->id;
        $expectedrecords[$ccccid]->timecompleted = 567;
        $this->assertNotFalse(helper::write_criteria_completion($expectedrecords[$ccccid]));
        $this->assertEquals($expectedrecords, $DB->get_records('course_completion_crit_compl'));
        $this->assertEquals(1, $DB->count_records('course_completion_log'));
        $log = $DB->get_record('course_completion_log', array('courseid' => $course2->id, 'userid' => $user1->id));
        $this->assertEquals($USER->id, $log->changeuserid);
        $this->assertContains('Criteria completion updated', $log->description);

        // Update with custom log message.
        $DB->delete_records('course_completion_log');
        $expectedrecords[$ccccid]->timecompleted = 567;
        $this->assertNotFalse(helper::write_criteria_completion($expectedrecords[$ccccid], 'custom message'));
        $this->assertEquals($expectedrecords, $DB->get_records('course_completion_crit_compl'));
        $this->assertEquals(1, $DB->count_records('course_completion_log'));
        $log = $DB->get_record('course_completion_log', array('courseid' => $course2->id, 'userid' => $user1->id));
        $this->assertEquals($USER->id, $log->changeuserid);
        $this->assertContains('custom message', $log->description);
    }

    public function test_write_module_completion() {
        global $DB, $USER;

        $this->resetAfterTest(true);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        // Add a quiz.
        /** @var mod_quiz_generator $quizgenerator */
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance(array('course' => $course1->id, 'questionsperpage' => 3, 'grade' => 100.0));

        // Add a facetoface.
        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $facetoface = $facetofacegenerator->create_instance(array('course' => $course2->id));

        $cm1id = $quiz->cmid;
        $cm2id = $facetoface->cmid;

        // Insert without problem inserts only the specified record and writes default log.
        $this->assertEquals(0, $DB->count_records('course_modules_completion'));
        $this->assertEquals(0, $DB->count_records('course_completion_log'));
        $cmc = new stdClass();
        $cmc->timecompleted = 123;
        $cmc->timemodified = 234;
        $cmc->completionstate = 34;
        $cmc->coursemoduleid = $cm1id;
        $cmc->userid = $user1->id;
        $this->assertNotFalse(helper::write_module_completion($cmc));
        $this->assertEquals(1, $DB->count_records('course_modules_completion'));
        $this->assertEquals(1, $DB->count_records('course_modules_completion',
            array('coursemoduleid' => $cm1id, 'userid' => $user1->id, 'timecompleted' => 123, 'timemodified' => 234, 'completionstate' => 34)));
        $this->assertEquals(1, $DB->count_records('course_completion_log'));
        $log = $DB->get_record('course_completion_log', array('courseid' => $course1->id, 'userid' => $user1->id));
        $this->assertEquals($USER->id, $log->changeuserid);
        $this->assertContains('Module completion created', $log->description);

        // Insert without problem inserts only the specified record and writes specified log.
        $cmc = new stdClass();
        $cmc->timecompleted = 123;
        $cmc->timemodified = 234;
        $cmc->completionstate = 34;
        $cmc->coursemoduleid = $cm2id;
        $cmc->userid = $user1->id;
        $this->assertNotFalse(helper::write_module_completion($cmc, 'custom message'));
        $this->assertEquals(2, $DB->count_records('course_modules_completion'));
        $this->assertEquals(1, $DB->count_records('course_modules_completion',
            array('coursemoduleid' => $cm2id, 'userid' => $user1->id, 'timecompleted' => 123, 'timemodified' => 234, 'completionstate' => 34)));
        $this->assertEquals(2, $DB->count_records('course_completion_log'));
        $log = $DB->get_record('course_completion_log', array('courseid' => $course2->id, 'userid' => $user1->id));
        $this->assertEquals($USER->id, $log->changeuserid);
        $this->assertContains('custom message', $log->description);

        // Insert some more control data.
        $cmc = new stdClass();
        $cmc->timecompleted = 123;
        $cmc->timemodified = 234;
        $cmc->completionstate = 34;
        $cmc->coursemoduleid = $cm1id;
        $cmc->userid = $user2->id;
        $this->assertNotFalse(helper::write_module_completion($cmc));
        $this->assertEquals(3, $DB->count_records('course_modules_completion'));

        // Insert with existing record fails.
        $cmc = new stdClass();
        $cmc->timecompleted = 123;
        $cmc->timemodified = 234;
        $cmc->completionstate = 34;
        $cmc->coursemoduleid = $cm1id;
        $cmc->userid = $user1->id;
        try {
            helper::write_module_completion($cmc);
            $this->fail("Shouldn't reach this code, exception not triggered!");
        } catch (coding_exception $e) {
            $this->assertContains("Course_modules_completion already exists", $e->getMessage());
        }
        $this->assertEquals(3, $DB->count_records('course_modules_completion'));

        // Make sure that the existing records aren't touched in any of the failing updates.
        $expectedrecords = $DB->get_records('course_modules_completion');

        // Update without matching id fails.
        $cmc->id = 999;
        try {
            helper::write_module_completion($cmc);
            $this->fail("Shouldn't reach this code, exception not triggered!");
        } catch (coding_exception $e) {
            $this->assertContains("Either course_modules_completion doesn't exist or belongs to a different user or module", $e->getMessage());
        }
        $this->assertEquals($expectedrecords, $DB->get_records('course_modules_completion'));

        // Update without matching course fails.
        $cmc = $DB->get_record('course_modules_completion',
            array('coursemoduleid' => $cm2id, 'userid' => $user1->id));
        $cmc->coursemoduleid = 888;
        try {
            helper::write_module_completion($cmc);
            $this->fail("Shouldn't reach this code, exception not triggered!");
        } catch (coding_exception $e) {
            $this->assertContains("Either course_modules_completion doesn't exist or belongs to a different user or module", $e->getMessage());
        }
        $this->assertEquals($expectedrecords, $DB->get_records('course_modules_completion'));

        // Update without matching user fails.
        $cmc->coursemoduleid = $cm2id;
        $cmc->userid = 777;
        try {
            helper::write_module_completion($cmc);
            $this->fail("Shouldn't reach this code, exception not triggered!");
        } catch (coding_exception $e) {
            $this->assertContains("Either course_modules_completion doesn't exist or belongs to a different user or module", $e->getMessage());
        }
        $this->assertEquals($expectedrecords, $DB->get_records('course_modules_completion'));

        // Update without problem updates only specified record and creates log.
        $DB->delete_records('course_completion_log');
        $cmc = $DB->get_record('course_modules_completion',
            array('coursemoduleid' => $cm2id, 'userid' => $user1->id));
        $cmcid = $cmc->id;
        $expectedrecords[$cmcid]->timecompleted = 567;
        $this->assertNotFalse(helper::write_module_completion($expectedrecords[$cmcid]));
        $this->assertEquals($expectedrecords, $DB->get_records('course_modules_completion'));
        $this->assertEquals(1, $DB->count_records('course_completion_log'));
        $log = $DB->get_record('course_completion_log', array('courseid' => $course2->id, 'userid' => $user1->id));
        $this->assertEquals($USER->id, $log->changeuserid);
        $this->assertContains('Module completion updated', $log->description);

        // Update with custom log message.
        $DB->delete_records('course_completion_log');
        $expectedrecords[$cmcid]->timecompleted = 567;
        $this->assertNotFalse(helper::write_module_completion($expectedrecords[$cmcid], 'custom message'));
        $this->assertEquals($expectedrecords, $DB->get_records('course_modules_completion'));
        $this->assertEquals(1, $DB->count_records('course_completion_log'));
        $log = $DB->get_record('course_completion_log', array('courseid' => $course2->id, 'userid' => $user1->id));
        $this->assertEquals($USER->id, $log->changeuserid);
        $this->assertContains('custom message', $log->description);
    }

    public function test_make_log_record() {
        global $USER;

        $expected = new \stdClass();
        $expected->courseid = 123;
        $expected->userid = 234;
        $expected->description = 'some text';

        // Test without change user id.
        $expected->changeuserid = $USER->id;
        $timebefore = time();
        $logrecord = helper::make_log_record($expected->courseid, $expected->userid, $expected->description);
        $timeafter = time();

        $this->assertGreaterThanOrEqual($timebefore, $logrecord->timemodified);
        $this->assertLessThanOrEqual($timeafter, $logrecord->timemodified);
        unset($logrecord->timemodified);

        $this->assertEquals($expected, $logrecord);

        // Test with change user id.
        $expected->changeuserid = 789;
        $timebefore = time();
        $logrecord = helper::make_log_record($expected->courseid, $expected->userid, $expected->description, $expected->changeuserid);
        $timeafter = time();

        $this->assertGreaterThanOrEqual($timebefore, $logrecord->timemodified);
        $this->assertLessThanOrEqual($timeafter, $logrecord->timemodified);
        unset($logrecord->timemodified);

        $this->assertEquals($expected, $logrecord);
    }

    public function test_save_completion_log() {
        global $DB, $USER;

        $this->resetAfterTest(true);

        $timebefore = time();
        helper::save_completion_log(123, 234, 'abc', 345);  // Normal log.
        helper::save_completion_log(456, 567, 'def');                   // Deafult change user id.
        helper::save_completion_log(678, null, 'ghi', 789); // Course-wide log.
        $timeafter = time();

        $this->assertEquals(3, $DB->count_records('course_completion_log'));

        $log = $DB->get_record('course_completion_log', array('courseid' => 123, 'userid' => 234));
        $this->assertEquals('abc',$log->description);
        $this->assertEquals(345,$log->changeuserid);
        $this->assertGreaterThanOrEqual($timebefore,$log->timemodified);
        $this->assertLessThanOrEqual($timeafter,$log->timemodified);

        $log = $DB->get_record('course_completion_log', array('courseid' => 456, 'userid' => 567));
        $this->assertEquals('def',$log->description);
        $this->assertEquals($USER->id,$log->changeuserid);
        $this->assertGreaterThanOrEqual($timebefore,$log->timemodified);
        $this->assertLessThanOrEqual($timeafter,$log->timemodified);

        $log = $DB->get_record('course_completion_log', array('courseid' => 678));
        $this->assertEmpty($log->userid);
        $this->assertEquals('ghi',$log->description);
        $this->assertEquals(789,$log->changeuserid);
        $this->assertGreaterThanOrEqual($timebefore,$log->timemodified);
        $this->assertLessThanOrEqual($timeafter,$log->timemodified);
    }

    public function test_log_course_completion() {
        global $DB, $USER;

        $this->resetAfterTest(true);

        $this->assertEquals(0, $DB->count_records('course_completion_log'));

        // Test without message or change user.
        $courseid = 123;
        $userid = 234;

        $coursecompletion = new stdClass();
        $coursecompletion->course = $courseid;
        $coursecompletion->userid = $userid;
        $coursecompletion->status = COMPLETION_STATUS_NOTYETSTARTED;
        $DB->insert_record('course_completions', $coursecompletion);

        $timebefore = time();
        helper::log_course_completion($courseid, $userid);
        $timeafter = time();

        $this->assertEquals(1, $DB->count_records('course_completion_log'));
        $log = $DB->get_record('course_completion_log', array());
        $this->assertEquals($courseid, $log->courseid);
        $this->assertEquals($userid, $log->userid);
        $this->assertEquals($USER->id, $log->changeuserid);
        $this->assertContains('Current completion record logged', $log->description);
        $this->assertContains('Not yet started (10)', $log->description);
        $this->assertGreaterThanOrEqual($timebefore,$log->timemodified);
        $this->assertLessThanOrEqual($timeafter,$log->timemodified);

        // Make sure that the change user and message can be specified.
        helper::log_course_completion($courseid, $userid, 'Another message', 789);
        $logs = $DB->get_records('course_completion_log', array(), 'id DESC');
        $this->assertCount(2, $logs);
        $log = reset($logs);
        $this->assertEquals(789, $log->changeuserid);
        $this->assertContains('Another message', $log->description);
    }

    public function test_get_course_completion_log_description() {
        // Deafult message.
        $coursecompletion = new stdClass();
        $coursecompletion->status = COMPLETION_STATUS_INPROGRESS;
        $coursecompletion->timeenrolled = 1234;
        $coursecompletion->timestarted = 2345;
        $coursecompletion->timecompleted = 3456;
        $coursecompletion->rpl = 'a reason?';
        $coursecompletion->rplgrade = 4567;
        $coursecompletion->reaggregate = 5678;

        $description = helper::get_course_completion_log_description($coursecompletion);

        $this->assertContains('Current completion record logged', $description);
        $this->assertContains('In progress (25)', $description);
        $this->assertContains('1234', $description);
        $this->assertContains('2345', $description);
        $this->assertContains('3456', $description);
        $this->assertContains('4567', $description);
        $this->assertContains('a reason?', $description);
        $this->assertContains('5678', $description);

        // Custom message.
        $coursecompletion->status = COMPLETION_STATUS_COMPLETEVIARPL;
        $coursecompletion->rpl = '';

        $description = helper::get_course_completion_log_description($coursecompletion, 'MessageX');

        $this->assertContains('MessageX', $description);
        $this->assertContains('Complete via rpl (75)', $description);
        $this->assertContains('1234', $description);
        $this->assertContains('2345', $description);
        $this->assertContains('3456', $description);
        $this->assertContains('4567', $description);
        $this->assertContains('Empty', $description);
        $this->assertContains('5678', $description);
    }

    public function test_log_course_completion_history() {
        global $DB, $USER;

        $this->resetAfterTest(true);

        $this->assertEquals(0, $DB->count_records('course_completion_log'));

        // Test without message or change user.
        $courseid = 123;
        $userid = 234;

        $historycompletion = new stdClass();
        $historycompletion->courseid = $courseid;
        $historycompletion->userid = $userid;
        $historycompletion->grade = 345;
        $chid = $DB->insert_record('course_completion_history', $historycompletion);

        $timebefore = time();
        helper::log_course_completion_history($chid);
        $timeafter = time();

        $this->assertEquals(1, $DB->count_records('course_completion_log'));
        $log = $DB->get_record('course_completion_log', array());
        $this->assertEquals($courseid, $log->courseid);
        $this->assertEquals($userid, $log->userid);
        $this->assertEquals($USER->id, $log->changeuserid);
        $this->assertContains('Course completion history logged', $log->description);
        $this->assertContains('345', $log->description);
        $this->assertGreaterThanOrEqual($timebefore,$log->timemodified);
        $this->assertLessThanOrEqual($timeafter,$log->timemodified);

        // Make sure that the change user and message can be specified.
        helper::log_course_completion_history($chid, 'Another message', 789);
        $logs = $DB->get_records('course_completion_log', array('courseid' => $courseid, 'userid' => $userid), 'id DESC');
        $this->assertCount(2, $logs);
        $log = reset($logs);
        $this->assertEquals(789, $log->changeuserid);
        $this->assertContains('Another message', $log->description);
        $this->assertContains('345', $log->description);
    }

    public function test_get_course_completion_history_log_description() {
        // Deafult message.
        $historycompletion = new stdClass();
        $historycompletion->id = 123;
        $historycompletion->timecompleted = 234;
        $historycompletion->grade = 345;

        $description = helper::get_course_completion_history_log_description($historycompletion);

        $this->assertContains('Course completion history logged', $description);
        $this->assertContains('123', $description);
        $this->assertContains('234', $description);
        $this->assertContains('345', $description);

        // Custom message, no id.
        unset($historycompletion->id);
        $description = helper::get_course_completion_history_log_description($historycompletion, 'MessageX');

        $this->assertContains('MessageX', $description);
        $this->assertContains('234', $description);
        $this->assertContains('345', $description);
    }

    public function test_log_criteria_completion() {
        global $DB, $USER;

        $this->resetAfterTest(true);

        $this->assertEquals(0, $DB->count_records('course_completion_log'));

        // Test without message or change user.
        $courseid = 123;
        $userid = 234;

        $critcompl = new stdClass();
        $critcompl->userid = $userid;
        $critcompl->course = $courseid;
        $critcompl->criteriaid = 345;
        $critcompl->gradefinal = 456;
        $critcompl->unenroled = 567;
        $critcompl->rpl = 678;
        $critcompl->timecompleted = 789;
        $ccccid = $DB->insert_record('course_completion_crit_compl', $critcompl);

        $timebefore = time();
        helper::log_criteria_completion($ccccid);
        $timeafter = time();

        $this->assertEquals(1, $DB->count_records('course_completion_log'));
        $log = $DB->get_record('course_completion_log', array());
        $this->assertEquals($courseid, $log->courseid);
        $this->assertEquals($userid, $log->userid);
        $this->assertEquals($USER->id, $log->changeuserid);
        $this->assertContains('Crit compl logged', $log->description);
        $this->assertContains((string)$ccccid, $log->description);
        $this->assertContains('345', $log->description);
        $this->assertContains('456', $log->description);
        $this->assertContains('567', $log->description);
        $this->assertContains('678', $log->description);
        $this->assertContains('789', $log->description);
        $this->assertGreaterThanOrEqual($timebefore,$log->timemodified);
        $this->assertLessThanOrEqual($timeafter,$log->timemodified);

        // Make sure that the change user and message can be specified.
        $DB->set_field('course_completion_crit_compl', 'rpl', '', array('id' => $ccccid));
        helper::log_criteria_completion($ccccid, 'Another message', 890);
        $logs = $DB->get_records('course_completion_log', array('courseid' => $courseid, 'userid' => $userid), 'id DESC');
        $this->assertCount(2, $logs);
        $log = reset($logs);
        $this->assertEquals(890, $log->changeuserid);
        $this->assertContains('Another message', $log->description);
        $this->assertContains((string)$ccccid, $log->description);
        $this->assertContains('Empty', $log->description);
    }

    public function test_log_course_module_completion() {
        global $DB, $USER;

        $this->resetAfterTest(true);

        $this->assertEquals(0, $DB->count_records('course_completion_log'));

        // Test without message or change user.
        $courseid = 123;
        $userid = 234;

        $module = new stdClass();
        $module->course = $courseid;
        $coursemoduleid = $DB->insert_record('course_modules', $module);

        $modulecompletion = new stdClass();
        $modulecompletion->coursemoduleid = $coursemoduleid;
        $modulecompletion->userid = $userid;
        $modulecompletion->completionstate = COMPLETION_COMPLETE_PASS;
        $modulecompletion->viewed = 1;
        $modulecompletion->timemodified = 345;
        $modulecompletion->timecompleted = 456;
        $modulecompletion->reaggregate = 567;
        $cmcid = $DB->insert_record('course_modules_completion', $modulecompletion);

        $timebefore = time();
        helper::log_course_module_completion($cmcid);
        $timeafter = time();

        $this->assertEquals(1, $DB->count_records('course_completion_log'));
        $log = $DB->get_record('course_completion_log', array());
        $this->assertEquals($courseid, $log->courseid);
        $this->assertEquals($userid, $log->userid);
        $this->assertEquals($USER->id, $log->changeuserid);
        $this->assertContains('Module completion logged', $log->description);
        $this->assertContains((string)$cmcid, $log->description);
        $this->assertContains('Yes', $log->description);
        $this->assertContains('345', $log->description);
        $this->assertContains('456', $log->description);
        $this->assertContains('567', $log->description);
        $this->assertGreaterThanOrEqual($timebefore,$log->timemodified);
        $this->assertLessThanOrEqual($timeafter,$log->timemodified);

        // Make sure that the change user and message can be specified.
        helper::log_course_module_completion($cmcid, 'Another message', 890);
        $logs = $DB->get_records('course_completion_log', array('courseid' => $courseid, 'userid' => $userid), 'id DESC');
        $this->assertCount(2, $logs);
        $log = reset($logs);
        $this->assertEquals(890, $log->changeuserid);
        $this->assertContains('Another message', $log->description);
        $this->assertContains((string)$cmcid, $log->description);
    }

    public function test_load_course_completion() {
        global $DB;

        $this->resetAfterTest(true);

        $courseid = 123;
        $userid = 234;

        $expectedcoursecompletion = new stdClass();
        $expectedcoursecompletion->userid = $userid;
        $expectedcoursecompletion->course = $courseid;
        $expectedcoursecompletion->organisationid = 345;
        $expectedcoursecompletion->positionid = 456;
        $expectedcoursecompletion->timeenrolled = 567;
        $expectedcoursecompletion->timestarted = 678;
        $expectedcoursecompletion->timecompleted = 789;
        $expectedcoursecompletion->reaggregate = 890;
        $expectedcoursecompletion->rpl = 'a reason!';
        $expectedcoursecompletion->rplgrade = 901;
        $expectedcoursecompletion->invalidatecache = 1;
        $expectedcoursecompletion->status = COMPLETION_STATUS_COMPLETEVIARPL;
        $expectedcoursecompletion->renewalstatus = -1;

        $expectedcoursecompletion->id = $DB->insert_record('course_completions', $expectedcoursecompletion);

        $result = helper::load_course_completion($courseid, $userid);
        $this->assertEquals($expectedcoursecompletion, $result);

        // Check that must_exist is working:
        try {
            helper::load_course_completion(765, 432);
            $this->fail("Shouldn't reach this code, exception not triggered!");
        } catch (coding_exception $e) {
            $this->assertContains("Tried to load a course_completions but it does not exist", $e->getMessage());
        }

        $result = helper::load_course_completion(765, 432, false);
        $this->assertFalse($result);
    }

    public function test_load_course_completion_history() {
        global $DB;

        $this->resetAfterTest(true);

        $courseid = 123;
        $userid = 234;

        $expectedhistorycompletion = new stdClass();
        $expectedhistorycompletion->courseid = $courseid;
        $expectedhistorycompletion->userid = $userid;
        $expectedhistorycompletion->timecompleted = 345;
        $expectedhistorycompletion->grade = 456;

        $expectedhistorycompletion->id = $DB->insert_record('course_completion_history', $expectedhistorycompletion);

        $result = helper::load_course_completion_history($expectedhistorycompletion->id);
        $this->assertEquals($expectedhistorycompletion, $result);

        // Check that must_exist is working:
        try {
            helper::load_course_completion_history(765);
            $this->fail("Shouldn't reach this code, exception not triggered!");
        } catch (coding_exception $e) {
            $this->assertContains("Tried to load a course_completion_history but it does not exist", $e->getMessage());
        }

        $result = helper::load_course_completion_history(765, false);
        $this->assertFalse($result);
    }

    public function test_delete_course_completion() {
        global $DB;

        $this->resetAfterTest(true);

        $userids = array(123, 234, 345, 456);
        $courseids = array(567, 678, 789, 890);

        foreach ($userids as $userid) {
            foreach ($courseids as $courseid) {
                $coursecompletion = new stdClass();
                $coursecompletion->userid = $userid;
                $coursecompletion->course = $courseid;
                $DB->insert_record('course_completions', $coursecompletion);
            }
        }

        $totalcount = count($userids) * count($courseids);

        $this->assertEquals($totalcount, $DB->count_records('course_completions'));
        $this->assertEquals(0, $DB->count_records('course_completion_log'));

        $this->assertEquals(1, $DB->count_records('course_completions', array('course' => 678, 'userid' => 345)));
        helper::delete_course_completion(678, 345);
        $this->assertEquals($totalcount - 1, $DB->count_records('course_completions'));
        $this->assertEquals(0, $DB->count_records('course_completions', array('course' => 678, 'userid' => 345)));

        $this->assertEquals(1, $DB->count_records('course_completions', array('course' => 567, 'userid' => 345)));
        helper::delete_course_completion(567, 345);
        $this->assertEquals($totalcount - 2, $DB->count_records('course_completions'));
        $this->assertEquals(0, $DB->count_records('course_completions', array('course' => 567, 'userid' => 345)));

        $this->assertEquals(1, $DB->count_records('course_completions', array('course' => 567, 'userid' => 123)));
        helper::delete_course_completion(567, 123, 'This is a custom message');
        $this->assertEquals($totalcount - 3, $DB->count_records('course_completions'));
        $this->assertEquals(0, $DB->count_records('course_completions', array('course' => 567, 'userid' => 123)));

        $logs = $DB->get_records('course_completion_log', array(), 'id DESC');
        $this->assertCount(3, $logs);
        $log = reset($logs);
        $this->assertEquals('This is a custom message', $log->description);
        $log = next($logs);
        $this->assertEquals('Current completion deleted', $log->description);
    }

    public function test_delete_course_completion_history() {
        global $DB;

        $this->resetAfterTest(true);

        $userids = array(123, 234, 345, 456);
        $courseids = array(567, 678, 789, 890);
        $grades = array(null, 901, 111);

        foreach ($userids as $userid) {
            foreach ($courseids as $courseid) {
                foreach ($grades as $grade) {
                    $historycompletion = new stdClass();
                    $historycompletion->userid = $userid;
                    $historycompletion->courseid = $courseid;
                    $historycompletion->grade = $grade;
                    $DB->insert_record('course_completion_history', $historycompletion);
                }
            }
        }

        $totalcount = count($userids) * count($courseids) * count($grades);

        $this->assertEquals($totalcount, $DB->count_records('course_completion_history'));
        $this->assertEquals(0, $DB->count_records('course_completion_log'));

        $histories = $DB->get_records('course_completion_history', array('courseid' => 678, 'userid' => 345, 'grade' => 901));
        $this->assertCount(1, $histories);
        $history = reset($histories);
        helper::delete_course_completion_history($history->id);
        $this->assertEquals($totalcount - 1, $DB->count_records('course_completion_history'));
        $this->assertEquals(0, $DB->count_records('course_completion_history', array('courseid' => 678, 'userid' => 345, 'grade' => 901)));

        $histories = $DB->get_records('course_completion_history', array('courseid' => 678, 'userid' => 345, 'grade' => 111));
        $this->assertCount(1, $histories);
        $history = reset($histories);
        helper::delete_course_completion_history($history->id);
        $this->assertEquals($totalcount - 2, $DB->count_records('course_completion_history'));
        $this->assertEquals(0, $DB->count_records('course_completion_history', array('courseid' => 678, 'userid' => 345, 'grade' => 111)));

        $histories = $DB->get_records('course_completion_history', array('courseid' => 678, 'userid' => 234, 'grade' => 901));
        $this->assertCount(1, $histories);
        $history = reset($histories);
        helper::delete_course_completion_history($history->id);
        $this->assertEquals($totalcount - 3, $DB->count_records('course_completion_history'));
        $this->assertEquals(0, $DB->count_records('course_completion_history', array('courseid' => 678, 'userid' => 234, 'grade' => 901)));

        $histories = $DB->get_records('course_completion_history', array('courseid' => 890, 'userid' => 345, 'grade' => 901));
        $this->assertCount(1, $histories);
        $history = reset($histories);
        helper::delete_course_completion_history($history->id, 'This is a custom message');
        $this->assertEquals($totalcount - 4, $DB->count_records('course_completion_history'));
        $this->assertEquals(0, $DB->count_records('course_completion_history', array('courseid' => 890, 'userid' => 345, 'grade' => 901)));

        $logs = $DB->get_records('course_completion_log', array(), 'id DESC');
        $this->assertCount(4, $logs);
        $log = reset($logs);
        $this->assertContains('This is a custom message', $log->description);
        $log = next($logs);
        $this->assertContains('History deleted', $log->description);
    }

    public function test_delete_criteria_completion() {
        global $DB;

        $this->resetAfterTest(true);

        $userids = array(123, 234, 345, 456);
        $courseids = array(567, 678, 789, 890);
        $criteriaids = array(111, 222, 333);

        foreach ($userids as $userid) {
            foreach ($courseids as $courseid) {
                foreach ($criteriaids as $criteriaid) {
                    $critcompl = new stdClass();
                    $critcompl->userid = $userid;
                    $critcompl->course = $courseid;
                    $critcompl->criteriaid = $criteriaid;
                    $DB->insert_record('course_completion_crit_compl', $critcompl);
                }
            }
        }

        $totalcount = count($userids) * count($courseids) * count($criteriaids);

        $this->assertEquals($totalcount, $DB->count_records('course_completion_crit_compl'));
        $this->assertEquals(0, $DB->count_records('course_completion_log'));

        $critcompls = $DB->get_records('course_completion_crit_compl', array('course' => 678, 'userid' => 345, 'criteriaid' => 222));
        $this->assertCount(1, $critcompls);
        $critcompl = reset($critcompls);
        helper::delete_criteria_completion($critcompl->id);
        $this->assertEquals($totalcount - 1, $DB->count_records('course_completion_crit_compl'));
        $this->assertEquals(0, $DB->count_records('course_completion_crit_compl', array('course' => 678, 'userid' => 345, 'criteriaid' => 222)));

        $critcompls = $DB->get_records('course_completion_crit_compl', array('course' => 678, 'userid' => 345, 'criteriaid' => 333));
        $this->assertCount(1, $critcompls);
        $critcompl = reset($critcompls);
        helper::delete_criteria_completion($critcompl->id);
        $this->assertEquals($totalcount - 2, $DB->count_records('course_completion_crit_compl'));
        $this->assertEquals(0, $DB->count_records('course_completion_crit_compl', array('course' => 678, 'userid' => 345, 'criteriaid' => 333)));

        $critcompls = $DB->get_records('course_completion_crit_compl', array('course' => 678, 'userid' => 123, 'criteriaid' => 222));
        $this->assertCount(1, $critcompls);
        $critcompl = reset($critcompls);
        helper::delete_criteria_completion($critcompl->id);
        $this->assertEquals($totalcount - 3, $DB->count_records('course_completion_crit_compl'));
        $this->assertEquals(0, $DB->count_records('course_completion_crit_compl', array('course' => 678, 'userid' => 123, 'criteriaid' => 222)));

        $critcompls = $DB->get_records('course_completion_crit_compl', array('course' => 890, 'userid' => 345, 'criteriaid' => 111));
        $this->assertCount(1, $critcompls);
        $critcompl = reset($critcompls);
        helper::delete_criteria_completion($critcompl->id, 'This is a custom message');
        $this->assertEquals($totalcount - 4, $DB->count_records('course_completion_crit_compl'));
        $this->assertEquals(0, $DB->count_records('course_completion_crit_compl', array('course' => 890, 'userid' => 345, 'criteriaid' => 111)));

        $logs = $DB->get_records('course_completion_log', array(), 'id DESC');
        $this->assertCount(4, $logs);
        $log = reset($logs);
        $this->assertContains('This is a custom message', $log->description);
        $log = next($logs);
        $this->assertContains('Crit compl deleted', $log->description);
    }

    public function test_delete_module_completion() {
        global $DB;

        $this->resetAfterTest(true);

        $courseids = array(567, 678, 789, 890);
        $coursemoduleids = array();
        $userids = array(123, 234, 345, 456);

        foreach ($courseids as $courseid) {
            $module = new stdClass();
            $module->course = $courseid;
            $id = $DB->insert_record('course_modules', $module);
            $coursemoduleids[$courseid] = $id;
            foreach ($userids as $userid) {
                $modulecompletion = new stdClass();
                $modulecompletion->coursemoduleid = $coursemoduleids[$courseid];
                $modulecompletion->userid = $userid;
                $modulecompletion->completionstate = COMPLETION_INCOMPLETE;
                $modulecompletion->timemodified = 987654;
                $DB->insert_record('course_modules_completion', $modulecompletion);
            }
        }

        $totalcount = count($userids) * count($coursemoduleids);

        $this->assertEquals($totalcount, $DB->count_records('course_modules_completion'));
        $this->assertEquals(0, $DB->count_records('course_completion_log'));

        $modulecompletions = $DB->get_records('course_modules_completion',
            array('coursemoduleid' => $coursemoduleids[678], 'userid' => 345));
        $this->assertCount(1, $modulecompletions);
        $modulecompletion = reset($modulecompletions);
        helper::delete_module_completion($modulecompletion->id);
        $this->assertEquals($totalcount - 1, $DB->count_records('course_modules_completion'));
        $this->assertEquals(0, $DB->count_records('course_modules_completion',
            array('coursemoduleid' => $coursemoduleids[678], 'userid' => 345)));

        $modulecompletions = $DB->get_records('course_modules_completion',
            array('coursemoduleid' => $coursemoduleids[678], 'userid' => 123));
        $this->assertCount(1, $modulecompletions);
        $modulecompletion = reset($modulecompletions);
        helper::delete_module_completion($modulecompletion->id);
        $this->assertEquals($totalcount - 2, $DB->count_records('course_modules_completion'));
        $this->assertEquals(0, $DB->count_records('course_modules_completion',
            array('coursemoduleid' => $coursemoduleids[678], 'userid' => 123)));

        $modulecompletions = $DB->get_records('course_modules_completion',
            array('coursemoduleid' => $coursemoduleids[890], 'userid' => 345));
        $this->assertCount(1, $modulecompletions);
        $modulecompletion = reset($modulecompletions);
        helper::delete_module_completion($modulecompletion->id, 'This is a custom message');
        $this->assertEquals($totalcount - 3, $DB->count_records('course_modules_completion'));
        $this->assertEquals(0, $DB->count_records('course_modules_completion',
            array('coursemoduleid' => $coursemoduleids[890], 'userid' => 345)));

        $logs = $DB->get_records('course_completion_log', array(), 'id DESC');
        $this->assertCount(3, $logs);
        $log = reset($logs);
        $this->assertContains('This is a custom message', $log->description);
        $log = next($logs);
        $this->assertContains('Module completion deleted', $log->description);
    }

    /**
     * Data provider for test_get_course_completion_errors.
     */
    public function data_get_course_completion_errors() {
        return array(
            // Status isn't valid. We deliberately don't set the other fields because they shouldn't be used.
            array('status invalid',
                array(
                    'status' => -1,
                ),
                array(
                    'error:stateinvalid' => 'status',
                ),
            ),
            // Really wrong Not yet started.
            array('not yet started all data wrong',
                array(
                    'status' => COMPLETION_STATUS_NOTYETSTARTED,
                    'timecompleted' => 123,
                    'rpl' => 'a reason!',
                    'rplgrade' => '123',
                ),
                array(
                    'error:coursestatusnotyetstarted-timecompletednotempty' => 'timecompleted',
                    'error:coursestatusnotyetstarted-rplnotempty' => 'rpl',
                    'error:coursestatusnotyetstarted-rplgradenotempty' => 'rplgrade',
                ),
            ),
            // Valid Not yet started.
            array('not yet started all data valid',
                array(
                    'status' => COMPLETION_STATUS_NOTYETSTARTED,
                    'timecompleted' => 0,
                    'rpl' => '',
                    'rplgrade' => '',
                ),
                array(
                ),
            ),
            // Really wrong In progress.
            array('in progress all data wrong',
                array(
                    'status' => COMPLETION_STATUS_INPROGRESS,
                    'timecompleted' => 123,
                    'rpl' => 'a reason!',
                    'rplgrade' => '123',
                ),
                array(
                    'error:coursestatusinprogress-timecompletednotempty' => 'timecompleted',
                    'error:coursestatusinprogress-rplnotempty' => 'rpl',
                    'error:coursestatusinprogress-rplgradenotempty' => 'rplgrade',
                ),
            ),
            // Valid In progress.
            array('in progress all data valid',
                array(
                    'status' => COMPLETION_STATUS_INPROGRESS,
                    'timecompleted' => 0,
                    'rpl' => '',
                    'rplgrade' => '',
                ),
                array(
                ),
            ),
            // Really wrong Complete.
            array('complete all data wrong',
                array(
                    'status' => COMPLETION_STATUS_COMPLETE,
                    'timecompleted' => 0,
                    'rpl' => 'a reason!',
                    'rplgrade' => '123',
                ),
                array(
                    'error:coursestatuscomplete-timecompletedempty' => 'timecompleted',
                    'error:coursestatuscomplete-rplnotempty' => 'rpl',
                    'error:coursestatuscomplete-rplgradenotempty' => 'rplgrade',
                ),
            ),
            // Valid Complete.
            array('complete all data valid',
                array(
                    'status' => COMPLETION_STATUS_COMPLETE,
                    'timecompleted' => 123,
                    'rpl' => '',
                    'rplgrade' => '',
                ),
                array(
                ),
            ),
            // Really wrong RPL Complete.
            array('rpl complete all data wrong',
                array(
                    'status' => COMPLETION_STATUS_COMPLETEVIARPL,
                    'timecompleted' => 0,
                    'rpl' => '',
                    'rplgrade' => '123',
                ),
                array(
                    'error:coursestatusrplcomplete-timecompletedempty' => 'timecompleted',
                    'error:coursestatusrplcomplete-rplempty' => 'rpl',
                ),
            ),
            // Valid RPL Complete.
            array('rpl complete all data valid',
                array(
                    'status' => COMPLETION_STATUS_COMPLETEVIARPL,
                    'timecompleted' => 123,
                    'rpl' => 'a reason!',
                    'rplgrade' => '',
                ),
                array(
                ),
            ),
        );
    }

    /**
     * Test course_completion_get_errors.
     *
     * @dataProvider data_get_course_completion_errors
     */
    public function test_get_course_completion_errors($debugkey, $coursecompletion, $expectederrors) {
        $errors = helper::get_course_completion_errors((object)$coursecompletion);
        $this->assertEquals($expectederrors, $errors, $debugkey);
    }

    /**
     * Data provider for test_get_module_completion_errors.
     */
    public function data_get_module_completion_errors() {
        return array(
            // Timecompleted: Completion state isn't valid. We deliberately don't set the other fields because they shouldn't be used.
            array('timecompleted, completion state invalid',
                true,
                array(
                    'completionstate' => -1,
                ),
                array(
                    'error:stateinvalid' => 'completionstate',
                ),
            ),
            // Timecompleted: Really wrong Incomplete.
            array('timecompleted, incomplete all data wrong',
                true,
                array(
                    'completionstate' => COMPLETION_INCOMPLETE,
                    'timecompleted' => 123,
                ),
                array(
                    'error:modulestatusincomplete-timecompletednotempty' => 'cmctimecompleted',
                ),
            ),
            // Timecompleted: Valid Incomplete.
            array('timecompleted, incomplete all data valid',
                true,
                array(
                    'completionstate' => COMPLETION_INCOMPLETE,
                    'timecompleted' => 0,
                ),
                array(
                ),
            ),
            // Timecompleted: Really wrong Complete.
            array('timecompleted, complete all data wrong',
                true,
                array(
                    'completionstate' => COMPLETION_COMPLETE,
                    'timecompleted' => 0,
                ),
                array(
                    'error:modulestatuscomplete-timecompletedempty' => 'cmctimecompleted',
                ),
            ),
            // Timecompleted: Valid Complete.
            array('timecompleted, complete all data valid',
                true,
                array(
                    'completionstate' => COMPLETION_COMPLETE,
                    'timecompleted' => 123,
                ),
                array(
                ),
            ),
            // Timecompleted: Really wrong Complete pass.
            array('timecompleted, complete pass all data wrong',
                true,
                array(
                    'completionstate' => COMPLETION_COMPLETE_PASS,
                    'timecompleted' => 0,
                ),
                array(
                    'error:modulestatuscompletepass-timecompletedempty' => 'cmctimecompleted',
                ),
            ),
            // Timecompleted: Valid Complete pass.
            array('timecompleted, complete pass all data valid',
                true,
                array(
                    'completionstate' => COMPLETION_COMPLETE_PASS,
                    'timecompleted' => 123,
                ),
                array(
                ),
            ),
            // Timecompleted: Really wrong Complete fail.
            array('timecompleted, complete fail all data wrong',
                true,
                array(
                    'completionstate' => COMPLETION_COMPLETE_FAIL,
                    'timecompleted' => 0,
                ),
                array(
                    'error:modulestatuscompletefail-timecompletedempty' => 'cmctimecompleted',
                ),
            ),
            // Timecompleted: Valid Complete fail.
            array('timecompleted, complete fail all data valid',
                true,
                array(
                    'completionstate' => COMPLETION_COMPLETE_FAIL,
                    'timecompleted' => 123,
                ),
                array(
                ),
            ),
            // Timemodified: Completion state isn't valid. We deliberately don't set the other fields because they shouldn't be used.
            array('timemodified, completion state invalid',
                false,
                array(
                    'completionstate' => -1,
                ),
                array(
                    'error:stateinvalid' => 'completionstate',
                ),
            ),
            // Timemodified: Really wrong Incomplete.
            array('timemodified, incomplete all data wrong',
                false,
                array(
                    'completionstate' => COMPLETION_INCOMPLETE,
                    'timecompleted' => 123,
                ),
                array(
                    'error:modulestatusincomplete-timecompletednotempty' => 'cmctimecompleted',
                ),
            ),
            // Timemodified: Valid Incomplete.
            array('timemodified, incomplete all data valid',
                false,
                array(
                    'completionstate' => COMPLETION_INCOMPLETE,
                    'timemodified' => 234,
                    'timecompleted' => 0,
                ),
                array(
                ),
            ),
            // Timemodified: Weird valid Complete.
            array('timemodified, complete all data weird valid',
                false,
                array(
                    'completionstate' => COMPLETION_COMPLETE,
                    'timemodified' => 234,
                    'timecompleted' => 0,
                ),
                array(
                ),
            ),
            // Timemodified: Valid Complete.
            array('timemodified, complete all data valid',
                false,
                array(
                    'completionstate' => COMPLETION_COMPLETE,
                    'timecompleted' => 123,
                ),
                array(
                ),
            ),
            // Timemodified: Weird valid Complete pass.
            array('timemodified, complete pass all data weird valid',
                false,
                array(
                    'completionstate' => COMPLETION_COMPLETE_PASS,
                    'timemodified' => 234,
                    'timecompleted' => 0,
                ),
                array(
                ),
            ),
            // Timemodified: Valid Complete pass.
            array('timemodified, complete pass all data valid',
                false,
                array(
                    'completionstate' => COMPLETION_COMPLETE_PASS,
                    'timecompleted' => 123,
                ),
                array(
                ),
            ),
            // Timemodified: Weird valid Complete fail.
            array('timemodified, complete fail all data weird valid',
                false,
                array(
                    'completionstate' => COMPLETION_COMPLETE_FAIL,
                    'timemodified' => 234,
                    'timecompleted' => 0,
                ),
                array(
                ),
            ),
            // Timemodified: Valid Complete fail.
            array('timemodified, complete fail all data valid',
                false,
                array(
                    'completionstate' => COMPLETION_COMPLETE_FAIL,
                    'timecompleted' => 123,
                ),
                array(
                ),
            ),
            // Timemodified: Invalid Complete Empty timemodified.
            array('timemodified, complete fail all data valid',
                false,
                array(
                    'completionstate' => COMPLETION_COMPLETE,
                    'timecompleted' => 0,
                ),
                array(
                    'error:modulestatuscomplete-timecompletedempty' => 'cmctimecompleted',
                ),
            ),
            // Timemodified: Invalid Complete pass Empty timemodified.
            array('timemodified, complete fail all data valid',
                false,
                array(
                    'completionstate' => COMPLETION_COMPLETE_PASS,
                    'timecompleted' => 0,
                ),
                array(
                    'error:modulestatuscompletepass-timecompletedempty' => 'cmctimecompleted',
                ),
            ),
            // Timemodified: Invalid Complete fail Empty timemodified.
            array('timemodified, complete fail all data valid',
                false,
                array(
                    'completionstate' => COMPLETION_COMPLETE_FAIL,
                    'timecompleted' => 0,
                ),
                array(
                    'error:modulestatuscompletefail-timecompletedempty' => 'cmctimecompleted',
                ),
            ),
        );
    }

    /**
     * Test get_module_completion_errors.
     *
     * @dataProvider data_get_module_completion_errors
     */
    public function test_get_module_completion_errors($debugkey, $usestimecompleted, $cmc, $expectederrors) {
        global $DB;

        $this->resetAfterTest(true);

        // To test that the errors are correct depending on whether the related module uses timemodified or
        // timecompleted to store the time completed, we need to set up the correct type of module.
        $modulename = $usestimecompleted ? 'facetoface' : 'quiz';
        $moduleid = $DB->get_field('modules', 'id', array('name' => $modulename));
        $coursemodule = new stdClass();
        $coursemodule->module = $moduleid;
        $cmc['coursemoduleid'] = $DB->insert_record('course_modules', $coursemodule);

        $errors = helper::get_module_completion_errors((object)$cmc);
        $this->assertEquals($expectederrors, $errors, $debugkey);
    }

    /**
     * Data provider for test_get_criteria_completion_errors.
     */
    public function data_get_criteria_completion_errors() {
        return array(
            // Module, no RPL, no timecompleted => No problems.
            array('module, no rpl, no timecompleted',
                true,
                array(
                    'rpl' => '',
                    'timecompleted' => 0,
                ),
                array(
                ),
            ),
            // Module, RPL, no timecompleted => Problem.
            array('module, rpl, no timecompleted',
                true,
                array(
                    'rpl' => 'a reason!',
                    'timecompleted' => 0,
                ),
                array(
                    'error:criteriaincomplete-rplnotempty' => 'rpl',
                ),
            ),
            // Module, no RPL, timecompleted => No problems.
            array('module, no rpl, timecompleted',
                true,
                array(
                    'rpl' => '',
                    'timecompleted' => 123,
                ),
                array(
                ),
            ),
            // Module, RPL, timecompleted => No problems.
            array('module, rpl, timecompleted',
                true,
                array(
                    'rpl' => 'a reason!',
                    'timecompleted' => 123,
                ),
                array(
                ),
            ),
            // Not module, no RPL, no timecompleted => No problems.
            array('not module, no rpl, no timecompleted',
                false,
                array(
                    'rpl' => '',
                    'timecompleted' => 0,
                ),
                array(
                ),
            ),
            // Not module, RPL, no timecompleted => Problems!
            array('not module, rpl, no timecompleted',
                false,
                array(
                    'rpl' => 'a reason!',
                    'timecompleted' => 0,
                ),
                array(
                    'error:criterianotmodule-rplnotempty' => 'rpl',
                    'error:criteriaincomplete-rplnotempty' => 'rpl',
                ),
            ),
            // Not module, no RPL, timecompleted => No problems.
            array('not module, no rpl, timecompleted',
                false,
                array(
                    'rpl' => '',
                    'timecompleted' => 123,
                ),
                array(
                ),
            ),
            // Not module, RPL, timecompleted => Problem.
            array('not module, rpl, timecompleted',
                false,
                array(
                    'rpl' => 'a reason!',
                    'timecompleted' => 123,
                ),
                array(
                    'error:criterianotmodule-rplnotempty' => 'rpl',
                ),
            ),
        );
    }

    /**
     * Test get_criteria_completion_errors.
     *
     * @dataProvider data_get_criteria_completion_errors
     */
    public function test_get_criteria_completion_errors($debugkey, $ismodulecriteria, $cccc, $expectederrors) {
        global $DB;

        $this->resetAfterTest(true);

        // To test that the errors are correct depending on whether the criteria relates to a module, we need to
        // create a course completion criteria record.
        $criteriatype = $ismodulecriteria ? COMPLETION_CRITERIA_TYPE_ACTIVITY : COMPLETION_CRITERIA_TYPE_SELF;
        $criteria = new stdClass();
        $criteria->criteriatype = $criteriatype;
        $cccc['criteriaid'] = $DB->insert_record('course_completion_criteria', $criteria);

        $errors = helper::get_criteria_completion_errors((object)$cccc);
        $this->assertEquals($expectederrors, $errors, $debugkey);
    }

    public function test_convert_errors_for_form() {
        $rawerrors = array(
            'error:coursestatuscomplete-rplnotempty' => 'rpl',
            'error:coursestatuscomplete-rplgradenotempty' => 'rplgrade'
        );
        $expectederrors = array(
            'rpl' => get_string('error:coursestatuscomplete-rplnotempty', 'completion'),
            'rplgrade' => get_string('error:coursestatuscomplete-rplgradenotempty', 'completion')
        );
        $formerrors = helper::convert_errors_for_form($rawerrors);
        $this->assertEquals($expectederrors, $formerrors);

        // Test with multiple errors for the same key.
        $rawerrors = array(
            'error:coursestatuscomplete-rplnotempty' => 'xxx',
            'error:coursestatuscomplete-rplgradenotempty' => 'xxx'
        );
        $expectederrors = array(
            'xxx' => get_string('error:coursestatuscomplete-rplnotempty', 'completion') .
                '<br/>' . get_string('error:coursestatuscomplete-rplgradenotempty', 'completion')
        );
        $formerrors = helper::convert_errors_for_form($rawerrors);
        $this->assertEquals($expectederrors, $formerrors);
    }

    public function test_convert_errors_to_problemkey() {
        $rawerrors = array(
            'error:problemtwo' => 'fieldtwo',
            'error:problemthree' => 'fieldthree',
            'error:problemone' => 'fieldone',
        );
        $expectedproblemkey = 'error:problemone|error:problemthree|error:problemtwo'; // Sorted alphabetically.
        $problemkey = helper::convert_errors_to_problemkey($rawerrors);
        $this->assertEquals($expectedproblemkey, $problemkey);

        // Test with empty array.
        $rawerrors = array();
        $expectedproblemkey = ''; // Sorted alphabetically.
        $problemkey = helper::convert_errors_to_problemkey($rawerrors);
        $this->assertEquals($expectedproblemkey, $problemkey);
    }

    public function test_format_log_date() {
        $this->assertEquals('Not set (0)', helper::format_log_date(0));
        $this->assertEquals('Not set ()', helper::format_log_date(''));
        $this->assertEquals('Not set (null)', helper::format_log_date(null));
        $result = helper::format_log_date(1234567890);
        $this->assertContains('Feb', $result);
        $this->assertContains('2009', $result);
        $this->assertContains('(1234567890)', $result);
    }

    public function test_module_uses_timecompleted() {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();

        // Add a quiz.
        /** @var mod_quiz_generator $quizgenerator */
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance(array('course' => $course->id, 'questionsperpage' => 3, 'grade' => 100.0));

        // Add a facetoface.
        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $facetoface = $facetofacegenerator->create_instance(array('course' => $course->id));

        $this->assertFalse(helper::module_uses_timecompleted($quiz->cmid));
        $this->assertTrue(helper::module_uses_timecompleted($facetoface->cmid));
    }

}
