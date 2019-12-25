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
 * Test overriden and non-overriden
 */
class mod_lesson_pagetypes_export_test_testcase extends advanced_testcase {
    /**
     * Test export essay
     */
    public function test_export_essay() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();


        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $lesson = $this->getDataGenerator()->create_module('lesson', array('course' => $course));
        /**
         * @var mod_lesson_generator $lessongenerator
         */
        $lessongenerator = $this->getDataGenerator()->get_plugin_generator('mod_lesson');

        $page = $lessongenerator->create_question_essay($lesson);

        $question = lesson_page_type_essay::load($page->id, new lesson($lesson));

        $exportanswer = "<p>Answer in <b>bold</b> and <i>italic</i>.</p>";

        $attempt = new stdClass;
        $attempt->lessonid = $lesson->id;
        $attempt->pageid = $page->id;
        $attempt->userid = $user->id;
        $attempt->answerid = $DB->get_field('lesson_answers', 'id', ['pageid'=>$page->id], IGNORE_MULTIPLE);
        $attempt->retry = 0;
        $attempt->correct = 1;
        $attempt->useranswer = 'O:8:"stdClass":7:{s:4:"sent";i:0;s:6:"graded";i:0;s:5:"score";i:0;s:6:"answer";s:47:'
            . '"<p>Answer in <b>bold</b> and <i>italic</i>.</p>";s:12:"answerformat";s:1:"1";s:8:"response";s:0:"";'
            . 's:14:"responseformat";s:1:"1";}';
        $attempt->timeseen = time();
        $attempt->id = $DB->insert_record('lesson_attempts', $attempt);

        $this->assertEquals($exportanswer, $question->export($attempt));
    }

    /**
     * Test export matching items
     */
    public function test_export_matching() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $lesson = $this->getDataGenerator()->create_module('lesson', array('course' => $course));
        /**
         * @var mod_lesson_generator $lessongenerator
         */
        $lessongenerator = $this->getDataGenerator()->get_plugin_generator('mod_lesson');

        $page = $lessongenerator->create_question_matching($lesson);

        $question = lesson_page_type_essay::load($page->id, new lesson($lesson));

        $exportanswer = ['Match value 1' => 'Match answer 1', 'Match value 2' => 'Match answer 2'];

        $attempt = new stdClass;
        $attempt->lessonid = $lesson->id;
        $attempt->pageid = $page->id;
        $attempt->userid = $user->id;
        $attempt->answerid = $DB->get_field('lesson_answers', 'id', ['pageid'=>$page->id], IGNORE_MULTIPLE);
        $attempt->retry = 0;
        $attempt->correct = 1;
        $attempt->useranswer = 'Match answer 1,Match answer 2';
        $attempt->timeseen = time();
        $attempt->id = $DB->insert_record('lesson_attempts', $attempt);

        $this->assertEquals($exportanswer, $question->export($attempt));
    }

    /**
     * Test export multichoice question with single answer
     */
    public function test_export_multichoice_single() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();


        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $lesson = $this->getDataGenerator()->create_module('lesson', array('course' => $course));
        /**
         * @var mod_lesson_generator $lessongenerator
         */
        $lessongenerator = $this->getDataGenerator()->get_plugin_generator('mod_lesson');

        $record = [
            'answer_editor'=> [
                0 => [
                    'text' => 'Answered',
                    'format' => FORMAT_HTML
                ]
            ]
        ];
        $page = $lessongenerator->create_question_multichoice($lesson, $record);

        $question = lesson_page_type_multichoice::load($page->id, new lesson($lesson));

        $exportanswer = 'Answered';

        $attempt = new stdClass;
        $attempt->lessonid = $lesson->id;
        $attempt->pageid = $page->id;
        $attempt->userid = $user->id;
        $attempt->answerid = $DB->get_field('lesson_answers', 'id', ['pageid'=>$page->id], IGNORE_MULTIPLE);
        $attempt->retry = 0;
        $attempt->correct = 1;
        $attempt->useranswer = 'Answered';
        $attempt->timeseen = time();
        $attempt->id = $DB->insert_record('lesson_attempts', $attempt);

        $this->assertEquals($exportanswer, $question->export($attempt));
    }

    /**
     * Test export multichoice question with multiple answers
     */
    public function test_export_multichoice_multi() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();


        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $lesson = $this->getDataGenerator()->create_module('lesson', array('course' => $course));
        /**
         * @var mod_lesson_generator $lessongenerator
         */
        $lessongenerator = $this->getDataGenerator()->get_plugin_generator('mod_lesson');

        $record = [
            'qoption' => 1,
            'answer_editor'=> [
                0 => [
                    'text' => 'Option 1',
                    'format' => FORMAT_HTML
                ],
                1 => [
                    'text' => 'Option 2',
                    'format' => FORMAT_HTML
                ],
                2 => [
                    'text' => 'Option 3',
                    'format' => FORMAT_HTML
                ]
            ]
        ];
        $page = $lessongenerator->create_question_multichoice($lesson, $record);

        $question = lesson_page_type_multichoice::load($page->id, new lesson($lesson));

        $answers = array_values(
            $DB->get_records('lesson_answers', ['pageid'=>$page->id], 'id', '*', 0, 2)
        );

        $exportanswer = [$answers[0]->answer, $answers[1]->answer];

        $attempt = new stdClass;
        $attempt->lessonid = $lesson->id;
        $attempt->pageid = $page->id;
        $attempt->userid = $user->id;
        $attempt->answerid = $DB->get_field('lesson_answers', 'id', ['pageid'=>$page->id], IGNORE_MULTIPLE);
        $attempt->retry = 0;
        $attempt->correct = 1;
        $attempt->useranswer = $answers[0]->id . ',' . $answers[1]->id;
        $attempt->timeseen = time();
        $attempt->id = $DB->insert_record('lesson_attempts', $attempt);

        $this->assertEquals($exportanswer, $question->export($attempt));
    }

    /**
     * Test export numerical question
     */
    public function test_export_numerical() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();


        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $lesson = $this->getDataGenerator()->create_module('lesson', array('course' => $course));
        /**
         * @var mod_lesson_generator $lessongenerator
         */
        $lessongenerator = $this->getDataGenerator()->get_plugin_generator('mod_lesson');

        $page = $lessongenerator->create_question_numeric($lesson);

        $question = lesson_page_type_numerical::load($page->id, new lesson($lesson));

        $exportanswer = "42"; // User answer

        $attempt = new stdClass;
        $attempt->lessonid = $lesson->id;
        $attempt->pageid = $page->id;
        $attempt->userid = $user->id;
        $attempt->answerid = $DB->get_field('lesson_answers', 'id', ['pageid'=>$page->id], IGNORE_MULTIPLE);
        $attempt->retry = 0;
        $attempt->correct = 1;
        $attempt->useranswer = '42';
        $attempt->timeseen = time();
        $attempt->id = $DB->insert_record('lesson_attempts', $attempt);

        $this->assertEquals($exportanswer, $question->export($attempt));
    }

    /**
     * Test export short answer question
     */
    public function test_export_shortanswer() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();


        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $lesson = $this->getDataGenerator()->create_module('lesson', array('course' => $course));
        /**
         * @var mod_lesson_generator $lessongenerator
         */
        $lessongenerator = $this->getDataGenerator()->get_plugin_generator('mod_lesson');

        $page = $lessongenerator->create_question_shortanswer($lesson);

        $question = lesson_page_type_shortanswer::load($page->id, new lesson($lesson));

        $exportanswer = "short answer"; // User answer

        $attempt = new stdClass;
        $attempt->lessonid = $lesson->id;
        $attempt->pageid = $page->id;
        $attempt->userid = $user->id;
        $attempt->answerid = $DB->get_field('lesson_answers', 'id', ['pageid'=>$page->id], IGNORE_MULTIPLE);
        $attempt->retry = 0;
        $attempt->correct = 1;
        $attempt->useranswer = 'short answer';
        $attempt->timeseen = time();
        $attempt->id = $DB->insert_record('lesson_attempts', $attempt);

        $this->assertEquals($exportanswer, $question->export($attempt));
    }

    /**
     * Test export true/false question
     */
    public function test_export_truefalse() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();


        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $lesson = $this->getDataGenerator()->create_module('lesson', array('course' => $course));
        /**
         * @var mod_lesson_generator $lessongenerator
         */
        $lessongenerator = $this->getDataGenerator()->get_plugin_generator('mod_lesson');

        $record = [
            'qoption' => 1,
            'answer_editor'=> [
                0 => [
                    'text' => 'TRUE',
                    'format' => FORMAT_HTML
                ]
            ]
        ];
        $page = $lessongenerator->create_question_truefalse($lesson, $record);

        $question = lesson_page_type_truefalse::load($page->id, new lesson($lesson));

        $exportanswer = "TRUE";

        $attempt = new stdClass;
        $attempt->lessonid = $lesson->id;
        $attempt->pageid = $page->id;
        $attempt->userid = $user->id;
        $attempt->answerid = $DB->get_field('lesson_answers', 'id', ['pageid'=>$page->id], IGNORE_MULTIPLE);
        $attempt->retry = 0;
        $attempt->correct = 1;
        $attempt->useranswer = 'TRUE';
        $attempt->timeseen = time();
        $attempt->id = $DB->insert_record('lesson_attempts', $attempt);

        $this->assertEquals($exportanswer, $question->export($attempt));
    }
}