<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Yuliya Bozhko <yuliya.bozhko@totaralearning.com>
 * @package mod_quiz
 */

defined('MOODLE_INTERNAL') || die();

class mod_quiz_backup_restore_quiz_testcase extends advanced_testcase {

    /** @var testing_data_generator */
    private $generator;

    /** @var mod_quiz_generator */
    private $quiz_generator;

    /** @var core_question_generator */
    private $question_generator;

    /** @var array Question bank for this test case */
    private $questions;

    /** @var stdClass Question categories */
    private $category_course, $category_system;

    private $course;

    protected function setUp() {
        parent::setUp();

        self::setAdminUser();
        self::resetAfterTest();

        $this->generator = self::getDataGenerator();
        $this->quiz_generator = $this->generator->get_plugin_generator('mod_quiz');
        $this->question_generator = $this->generator->get_plugin_generator('core_question');

        $this->course = $this->generator->create_course();
        $context = context_course::instance($this->course->id);

        $this->category_course = $this->question_generator->create_question_category(['contextid' => $context->id]);
        $this->category_system = $this->question_generator->create_question_category();

        $system_options = ['category' => $this->category_system->id];
        $course_options = ['category' => $this->category_course->id];
        $this->questions['c1'] = $this->question_generator->create_question('shortanswer', null, $course_options);
        $this->questions['c2'] = $this->question_generator->create_question('numerical', null, $course_options);
        $this->questions['s1'] = $this->question_generator->create_question('essay', null, $system_options);
        $this->questions['s2'] = $this->question_generator->create_question('match', null, $system_options);
    }

    protected function tearDown() {
        $this->generator = null;
        $this->quiz_generator = null;
        $this->question_generator = null;
        $this->questions = null;
        $this->category_system = null;
        $this->category_course = null;
        $this->course = null;

        parent::tearDown();
    }

    /**
     * Tests duplicating a quiz which contains random questions inside the course.
     */
    public function test_duplicate_random_questions_quiz() {
        global $DB;

        self::assertEquals(0, $DB->count_records('question', ['qtype' => 'random']));

        $quiz = $this->quiz_generator->create_instance(['course' => $this->course->id]);

        quiz_add_quiz_question($this->questions['c1']->id, $quiz);
        quiz_add_random_questions($quiz, 0, $this->category_course->id, 2, false);
        self::assertEquals(2, $DB->count_records('question', ['qtype' => 'random']));

        // Duplicate the quiz inside this course.
        self::duplicate($this->course, $quiz->cmid);

        self::assertEquals(2, $DB->count_records('quiz', ['course' => $this->course->id]));
        // Count of random questions: 2 in q1 + 2 in q2 = 4.
        self::assertEquals(4, $DB->count_records('question', ['qtype' => 'random']));
    }

    /**
     * Tests backing up and restoring a course with a quiz which contains
     * random questions.
     */
    public function test_backup_restore_course_with_random_questions_quiz() {
        global $DB;

        self::assertEquals(0, $DB->count_records('question', ['qtype' => 'random']));

        $quiz = $this->quiz_generator->create_instance(['course' => $this->course->id]);

        quiz_add_random_questions($quiz, 0, $this->category_system->id, 2, false);
        self::assertEquals(2, $DB->count_records('question', ['qtype' => 'random']));

        $newcourseid = self::backup_and_restore($this->course);

        self::assertEquals(2, $DB->count_records('quiz'));
        self::assertEquals(1, $DB->count_records('quiz', ['course' => $this->course->id]));
        self::assertEquals(1, $DB->count_records('quiz', ['course' => $newcourseid]));
        // Count of random questions: 2 in c1q1 + 2 in c2q1 = 4.
        self::assertEquals(4, $DB->count_records('question', ['qtype' => 'random']));
    }

    /**
     * Tests duplicating a quiz inside the course and then backing up and restoring
     * this course into another course.
     */
    public function test_duplicate_backup_restore_course_with_random_questions_quiz() {
        global $DB;

        self::assertEquals(0, $DB->count_records('question', ['qtype' => 'random']));

        $quiz = $this->quiz_generator->create_instance(['course' => $this->course->id]);

        // Add random questions from both categories to the quiz.
        quiz_add_random_questions($quiz, 0, $this->category_course->id, 1, false);
        quiz_add_random_questions($quiz, 0, $this->category_system->id, 1, false);

        // Duplicate the quiz inside this course.
        $newcmid = self::duplicate($this->course, $quiz->cmid);
        self::assertEquals(4, $DB->count_records('question', ['qtype' => 'random']));

        $cm = get_coursemodule_from_id('quiz', $newcmid, $this->course->id);
        $newquiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);

        // Add one more random question to the new quiz.
        quiz_add_random_questions($newquiz, 0, $this->category_course->id, 1, false);

        // Backup and restore an entire course.
        $newcourseid = self::backup_and_restore($this->course);

        self::assertEquals(4, $DB->count_records('quiz'));
        self::assertEquals(2, $DB->count_records('quiz', ['course' => $this->course->id]));
        self::assertEquals(2, $DB->count_records('quiz', ['course' => $newcourseid]));
        // Count of random questions: 2 in c1q1 + 3 in c1q2 + 2 in c2q1 + 3 in c2q2 = 10.
        self::assertEquals(10, $DB->count_records('question', ['qtype' => 'random']));
    }

     /**
     * Backs a course up and restores it.
     *
     * This is based on the code from @see core_backup_moodle2_testcase
     *
     * @param stdClass $course Course object to backup
     *
     * @return int ID of newly restored course
     */
    protected static function backup_and_restore($course) {
        global $USER, $CFG;

        // Turn off file logging, otherwise it can't delete the file (Windows).
        $CFG->backup_file_logger_level = backup::LOG_NONE;

        // Do backup with default settings. MODE_IMPORT means it will just
        // create the directory and not zip it.
        $bc = new backup_controller(backup::TYPE_1COURSE, $course->id, backup::FORMAT_MOODLE,
                                    backup::INTERACTIVE_NO, backup::MODE_IMPORT, $USER->id);
        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();

        // Do restore to new course with default settings.
        $newcourseid = restore_dbops::create_new_course(
            $course->fullname, $course->shortname . '_2', $course->category);
        $rc = new restore_controller($backupid, $newcourseid, backup::INTERACTIVE_NO,
                                     backup::MODE_GENERAL, $USER->id, backup::TARGET_NEW_COURSE);
        self::assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        return $newcourseid;
    }

    /**
     * Duplicates a single activity within a course.
     *
     * This is based on the code from course/modduplicate.php, but reduced for simplicity.
     *
     * @param stdClass $course Course object
     * @param int $cmid Activity to duplicate
     * @return int ID of new activity
     */
    protected static function duplicate($course, $cmid) {
        global $USER;

        // Do backup.
        $bc = new backup_controller(backup::TYPE_1ACTIVITY, $cmid, backup::FORMAT_MOODLE,
                                    backup::INTERACTIVE_NO, backup::MODE_IMPORT, $USER->id);
        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();

        // Do restore.
        $rc = new restore_controller($backupid, $course->id, backup::INTERACTIVE_NO,
                                     backup::MODE_IMPORT, $USER->id, backup::TARGET_CURRENT_ADDING);
        self::assertTrue($rc->execute_precheck());
        $rc->execute_plan();

        // Find cmid.
        $tasks = $rc->get_plan()->get_tasks();
        $cmcontext = context_module::instance($cmid);
        $newcmid = 0;
        foreach ($tasks as $task) {
            if (is_subclass_of($task, 'restore_activity_task')) {
                if ($task->get_old_contextid() == $cmcontext->id) {
                    $newcmid = $task->get_moduleid();
                    break;
                }
            }
        }
        $rc->destroy();
        if (!$newcmid) {
            throw new coding_exception('Unexpected: failure to find restored cmid');
        }
        return $newcmid;
    }
}
