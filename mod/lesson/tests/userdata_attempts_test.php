<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@@totaralearning.com>
 * @package mod_lesson
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_userdata
 */
class mod_lesson_userdata_attempts_testcase extends advanced_testcase {
    /**
     * Test that purging still occurs when the module is not visible.
     *
     * The reason for this test is largely due to the fact that if we used the API in the lesson class,
     * we could potentially run into an error.
     *
     * There's no need to repeat this test across different contexts as it is expected to either
     * occur in this scenario or not at all.
     *
     */
    public function test_purge_attempts_module_not_visible() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $user = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course();
        $lesson = $this->getDataGenerator()->create_module('lesson', ['course' => $course]);
        $lessongenerator = $this->getDataGenerator()->get_plugin_generator('mod_lesson');

        $page1 = $lessongenerator->create_question_numeric($lesson);
        $answerid = $DB->get_field('lesson_answers', 'id', ['lessonid' => $lesson->id, 'pageid' => $page1->id]);

        $this->create_attempt($user, $lesson, $page1, $answerid,  '1');
        $this->create_attempt($otheruser, $lesson, $page1, $answerid, '1');
        $this->assertEquals(2, $DB->count_records('lesson_attempts', ['lessonid' => $lesson->id]));


        $DB->set_field('modules', 'visible', '0', ['name' => 'lesson']);

        // Set up and execute the purge item.
        $target_user = new \totara_userdata\userdata\target_user($user);
        \mod_lesson\userdata\attempts::execute_purge($target_user, context_system::instance());

        $this->assertEquals(1, $DB->count_records('lesson_attempts', ['lessonid' => $lesson->id]));

        $attempt = $DB->get_record('lesson_attempts', ['lessonid' => $lesson->id]);
        $this->assertEquals($lesson->id, $attempt->lessonid);
        $this->assertEquals($page1->id, $attempt->pageid);
        $this->assertEquals($answerid, $attempt->answerid);
        $this->assertEquals($otheruser->id, $attempt->userid);
    }

    /**
     * Mock user grades for the lesson in DB
     * @param stdClass $user
     * @param stdClass $lesson
     * @param int $time
     * @return stdClass
     * @throws dml_exception
     */
    private function create_grade(stdClass $user, stdClass $lesson, $time = 0) {
        global $DB;

        $record = new stdClass();
        $record->lessonid = $lesson->id;
        $record->userid = $user->id;
        $record->grade = rand(0,100);
        $record->late = rand(0,1);
        $record->completed = $time ? $time : time();
        $record->id = $DB->insert_record('lesson_grades', $record);
        return $record;
    }

    /**
     * Mock user attempt to answer on lesson question page in DB
     * @param stdClass $user
     * @param stdClass $lesson
     * @param stdClass $page
     * @param $answerid
     * @param $useranswer
     * @param int $time
     * @return stdClass
     */
    private function create_attempt(stdClass $user, stdClass $lesson, stdClass $page, $answerid = 0,  $useranswer = "1", $time = 0) {
        global $DB;

        if (empty($useranswerid)) {
            // Get answer with minimal id
            $answers = $DB->get_records('lesson_answers', ['lessonid' => $lesson->id, 'pageid' => $page->id], 'id',
                '*', 0, 1);
            $answerid = key($answers);
        }

        $record = new stdClass();
        $record->lessonid = $lesson->id;
        $record->pageid = $page->id;
        $record->answerid = $answerid;
        $record->userid = $user->id;
        $record->useranswer = $useranswer;
        $record->retry = 0;
        $record->correct = rand(0, 1);
        $record->timeseen = $time ? $time : time();
        $record->id = $DB->insert_record('lesson_attempts', $record);
        return $record;
    }

    /**
     * Create override
     * @param stdClass $user
     * @param stdClass $lesson
     * @param int $time
     */
    public function create_override(stdClass $user, stdClass $lesson, $time = 0) {
        global $DB;

        $record = new stdClass();
        $record->lessonid = $lesson->id;
        $record->userid = $user->id;
        $record->available = $time;
        $record->deadline = $time + 3600;
        $record->maxattempts = 3;
        $record->id = $DB->insert_record('lesson_overrides', $record);
        return $record;
    }

    /**
     * Create lesson
     * @param $course
     * @return stdClass
     */
    private function create_lesson($course) {

        $lesson = $this->getDataGenerator()->create_module(
            'lesson',
            ['course' => $course]
        );

        return $lesson;
    }

    /**
     * Create lessons with the following scheme:
     * - Two users (user and otheruser)
     * - In deafult (misc) category one course (c1)
     * - Course Category (cat2) with two courses (c2, c3)
     * - 4 Lessons (l1 in c1, l2 in c2, l3 and l4 in c3)
     * - 8 Overrides (one override in each course for each user)
     * - 4 Numeric questions (1 question in each lesson)
     * - 16 attempts (two attempts for each question by both users)
     * - 8 grades (each user for each lesson
     * @return stdClass with created instances
     */
    private function create_lesson_data_for_multiple_contexts() {
        global $DB;

        $that = new stdClass();
        $that->user = $this->getDataGenerator()->create_user();
        $that->otheruser = $this->getDataGenerator()->create_user();

        $that->courses['c1'] = $this->getDataGenerator()->create_course();

        $that->cat2 = $this->getDataGenerator()->create_category();
        $that->courses['c2'] = $this->getDataGenerator()->create_course(['category' => $that->cat2->id]);
        $that->courses['c3'] = $this->getDataGenerator()->create_course(['category' => $that->cat2->id]);

        /**
         * @var mod_lesson_generator $lessongenerator
         */
        $lessongenerator = $this->getDataGenerator()->get_plugin_generator('mod_lesson');

        $that->lessons = [];

        $that->lessons['l1'] = $this->create_lesson($that->courses['c1']);
        $that->lessons['l2'] = $this->create_lesson($that->courses['c2']);
        $that->lessons['l3'] = $this->create_lesson($that->courses['c3']);
        $that->lessons['l4'] = $this->create_lesson($that->courses['c3']);

        for ($i = 1; $i <= 4; $i++) {
            $that->questions['q' . $i] = $lessongenerator->create_question_numeric($that->lessons['l' . $i]);

            $this->create_attempt($that->user, $that->lessons['l' . $i], $that->questions['q' . $i]);
            $this->create_attempt($that->user, $that->lessons['l' . $i], $that->questions['q' . $i]);
            $this->create_attempt($that->otheruser, $that->lessons['l' . $i], $that->questions['q' . $i]);
            $this->create_attempt($that->otheruser, $that->lessons['l' . $i], $that->questions['q' . $i]);

            $this->create_grade($that->user, $that->lessons['l' . $i]);
            $this->create_grade($that->otheruser, $that->lessons['l' . $i]);

            $this->create_override($that->user, $that->lessons['l' . $i]);
            $this->create_override($that->otheruser, $that->lessons['l' . $i]);
        }

        $this->assertEquals(4, $DB->count_records('lesson'));
        $this->assertEquals(4, $DB->count_records('lesson_pages'));
        $this->assertEquals(4, $DB->count_records('lesson_answers'));
        $this->assertEquals(16, $DB->count_records('lesson_attempts'));
        $this->assertEquals(8, $DB->count_records('lesson_grades'));
        $this->assertEquals(8, $DB->count_records('lesson_overrides'));

        return $that;
    }

    /**
     * Test purge of attempts in system context
     */
    public function test_purge_attempts_system_context() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $that = $this->create_lesson_data_for_multiple_contexts();

        // Set up and execute the purge item.
        $target_user = new \totara_userdata\userdata\target_user($that->user);
        \mod_lesson\userdata\attempts::execute_purge($target_user, context_system::instance());

        $this->assertEquals(8, $DB->count_records('lesson_attempts'));
        $this->assertEquals(0, $DB->count_records('lesson_attempts', ['userid' => $that->user->id]));

        $this->assertEquals(4, $DB->count_records('lesson_grades'));
        $this->assertEquals(0, $DB->count_records('lesson_grades', ['userid' => $that->user->id]));

        $this->assertEquals(4, $DB->count_records('lesson_overrides'));
        $this->assertEquals(0, $DB->count_records('lesson_overrides', ['userid' => $that->user->id]));
    }

    /**
     * Test purge of attempts in course category context
     */
    public function test_purge_attempts_coursecat_context() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $that = $this->create_lesson_data_for_multiple_contexts();

        // Set up and execute the purge item.
        $target_user = new \totara_userdata\userdata\target_user($that->user);
        \mod_lesson\userdata\attempts::execute_purge(
            $target_user,
            context_coursecat::instance($that->cat2->id)
        );

        $this->assertEquals(8, $DB->count_records('lesson_attempts', ['userid' => $that->otheruser->id]));
        $this->assertEquals(2, $DB->count_records('lesson_attempts', ['userid' => $that->user->id]));

        $this->assertEquals(4, $DB->count_records('lesson_grades', ['userid' => $that->otheruser->id]));
        $this->assertEquals(1, $DB->count_records('lesson_grades', ['userid' => $that->user->id]));

        $this->assertEquals(4, $DB->count_records('lesson_overrides', ['userid' => $that->otheruser->id]));
        $this->assertEquals(1, $DB->count_records('lesson_overrides', ['userid' => $that->user->id]));

        // Make sure that the correct lesson attempts and grades were removed.
        $remainingattempts = $DB->get_records('lesson_attempts', ['userid' => $that->user->id]);
        foreach ($remainingattempts as $remainingattempt) {
            $this->assertEquals($that->lessons['l1']->id, $remainingattempt->lessonid);
            $this->assertEquals($that->questions['q1']->id, $remainingattempt->pageid);
        }

        $remaininggrades = $DB->get_records('lesson_grades', ['userid' => $that->user->id]);
        foreach ($remaininggrades as $remaininggrade) {
            $this->assertEquals($that->lessons['l1']->id, $remaininggrade->lessonid);
        }

        $remainingoverrides = $DB->get_records('lesson_overrides', ['userid' => $that->user->id]);
        foreach ($remainingoverrides as $remainingoverride) {
            $this->assertEquals($that->lessons['l1']->id, $remainingoverride->lessonid);
        }
    }

    /**
     * Test purge of attempts in course context
     */
    public function test_purge_attempts_course_context() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $that = $this->create_lesson_data_for_multiple_contexts();


        // Set up and execute the purge item.
        $target_user = new \totara_userdata\userdata\target_user($that->user);
        \mod_lesson\userdata\attempts::execute_purge(
            $target_user,
            context_course::instance($that->courses['c3']->id)
        );

        $this->assertEquals(8, $DB->count_records('lesson_attempts', ['userid' => $that->otheruser->id]));
        $this->assertEquals(4, $DB->count_records('lesson_attempts', ['userid' => $that->user->id]));

        $this->assertEquals(4, $DB->count_records('lesson_grades', ['userid' => $that->otheruser->id]));
        $this->assertEquals(2, $DB->count_records('lesson_grades', ['userid' => $that->user->id]));

        $this->assertEquals(4, $DB->count_records('lesson_overrides', ['userid' => $that->otheruser->id]));
        $this->assertEquals(2, $DB->count_records('lesson_overrides', ['userid' => $that->user->id]));

        // Make sure that the correct lesson attempts and grades were removed.
        $remainingattempts = $DB->get_records('lesson_attempts', ['userid' => $that->user->id]);
        foreach ($remainingattempts as $remainingattempt) {
            $this->assertContains($remainingattempt->lessonid, [$that->lessons['l1']->id, $that->lessons['l2']->id]);
            $this->assertContains($remainingattempt->pageid, [$that->questions['q1']->id, $that->questions['q2']->id]);
        }

        $remaininggrades = $DB->get_records('lesson_grades', ['userid' => $that->user->id]);
        foreach ($remaininggrades as $remaininggrade) {
            $this->assertContains($remaininggrade->lessonid, [$that->lessons['l1']->id, $that->lessons['l2']->id]);
        }

        $remainingoverrides = $DB->get_records('lesson_overrides', ['userid' => $that->user->id]);
        foreach ($remainingoverrides as $remainingoverride) {
            $this->assertContains($remainingoverride->lessonid, [$that->lessons['l1']->id, $that->lessons['l2']->id]);
        }
    }

    /**
     * Test purge of attempts in module context
     */
    public function test_purge_attempts_module_context() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $that = $this->create_lesson_data_for_multiple_contexts();

        // Set up and execute the purge item.
        $target_user = new \totara_userdata\userdata\target_user($that->user);
        \mod_lesson\userdata\attempts::execute_purge(
            $target_user,
            context_module::instance($that->lessons['l4']->cmid)
        );

        $this->assertEquals(8, $DB->count_records('lesson_attempts', ['userid' => $that->otheruser->id]));
        $this->assertEquals(6, $DB->count_records('lesson_attempts', ['userid' => $that->user->id]));

        $this->assertEquals(4, $DB->count_records('lesson_grades', ['userid' => $that->otheruser->id]));
        $this->assertEquals(3, $DB->count_records('lesson_grades', ['userid' => $that->user->id]));

        // Make sure that the correct lesson attempts and grades were removed.
        $remainingattempts = $DB->get_records('lesson_attempts', ['userid' => $that->user->id]);
        foreach ($remainingattempts as $remainingattempt) {
            $this->assertContains($remainingattempt->lessonid, [$that->lessons['l1']->id, $that->lessons['l2']->id, $that->lessons['l3']->id]);
            $this->assertContains($remainingattempt->pageid, [$that->questions['q1']->id, $that->questions['q2']->id, $that->questions['q3']->id]);
        }

        $remaininggrades = $DB->get_records('lesson_grades', ['userid' => $that->user->id]);
        foreach ($remaininggrades as $remaininggrade) {
            $this->assertContains($remaininggrade->lessonid, [$that->lessons['l1']->id, $that->lessons['l2']->id, $that->lessons['l3']->id]);
        }

        $remainingoverrides = $DB->get_records('lesson_overrides', ['userid' => $that->user->id]);
        foreach ($remainingoverrides as $remainingoverride) {
            $this->assertContains($remainingoverride->lessonid, [$that->lessons['l1']->id, $that->lessons['l2']->id, $that->lessons['l3']->id]);
        }
    }

    /**
     * @param \totara_userdata\userdata\export $export
     */
    private function assert_export_structure(\totara_userdata\userdata\export $export) {
        foreach($export->data['grades'] as $grade) {
            $this->assertArrayHasKey('lesson', $grade);
            $this->assertArrayHasKey('grade', $grade);
            $this->assertArrayHasKey('completed', $grade);
        }
        foreach($export->data['answers'] as $answer) {
            $this->assertArrayHasKey('title', $answer);
            $this->assertArrayHasKey('useranswer', $answer);
        }
    }
    /**
     * Test export of attempts in system context
     */
    public function test_export_attempts_system_context() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $that = $this->create_lesson_data_for_multiple_contexts();

        // Set up and execute the purge item.
        $target_user = new \totara_userdata\userdata\target_user($that->user);
        $export = \mod_lesson\userdata\attempts::execute_export(
            $target_user,
            context_system::instance()
        );
        $count = \mod_lesson\userdata\attempts::execute_count(
            $target_user,
            context_system::instance()
        );
        $this->assertArrayHasKey('answers', $export->data);
        $this->assertArrayHasKey('grades', $export->data);
        $this->assertCount(8, $export->data['answers']);
        $this->assertCount(4, $export->data['grades']);
        $this->assert_export_structure($export);
        $this->assertEquals(12, $count);
    }

    /**
     * Test export of attempts in course category context
     */
    public function test_export_attempts_coursecat_context() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $that = $this->create_lesson_data_for_multiple_contexts();

        // Set up and execute the purge item.
        $target_user = new \totara_userdata\userdata\target_user($that->user);
        $export = \mod_lesson\userdata\attempts::execute_export(
            $target_user,
            context_coursecat::instance($that->cat2->id)
        );
        $count = \mod_lesson\userdata\attempts::execute_count(
            $target_user,
            context_coursecat::instance($that->cat2->id)
        );
        $this->assertArrayHasKey('answers', $export->data);
        $this->assertArrayHasKey('grades', $export->data);
        $this->assertCount(6, $export->data['answers']);
        $this->assertCount(3, $export->data['grades']);
        $this->assert_export_structure($export);
        $this->assertEquals(9, $count);
    }

    /**
     * Test export of attempts in course context
     */
    public function test_export_attempts_course_context() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $that = $this->create_lesson_data_for_multiple_contexts();

        // Set up and execute the purge item.
        $target_user = new \totara_userdata\userdata\target_user($that->user);
        $export = \mod_lesson\userdata\attempts::execute_export(
            $target_user,
            context_course::instance($that->courses['c3']->id)
        );
        $count = \mod_lesson\userdata\attempts::execute_count(
            $target_user,
            context_course::instance($that->courses['c3']->id)
        );
        $this->assertArrayHasKey('answers', $export->data);
        $this->assertArrayHasKey('grades', $export->data);
        $this->assertCount(4, $export->data['answers']);
        $this->assertCount(2, $export->data['grades']);
        $this->assert_export_structure($export);
        $this->assertEquals(6, $count);
    }

    /**
     * Test export of attempts in module context
     */
    public function test_export_attempts_module_context() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $that = $this->create_lesson_data_for_multiple_contexts();

        // Set up and execute the purge item.
        $target_user = new \totara_userdata\userdata\target_user($that->user);
        $export = \mod_lesson\userdata\attempts::execute_export(
            $target_user,
            context_module::instance($that->lessons['l4']->cmid)
        );
        $count = \mod_lesson\userdata\attempts::execute_count(
            $target_user,
            context_module::instance($that->lessons['l4']->cmid)
        );
        $this->assertArrayHasKey('answers', $export->data);
        $this->assertArrayHasKey('grades', $export->data);
        $this->assertCount(2, $export->data['answers']);
        $this->assertCount(1, $export->data['grades']);
        $this->assert_export_structure($export);
        $this->assertEquals(3, $count);
    }
}