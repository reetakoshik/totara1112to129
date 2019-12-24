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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_quiz
 */

use mod_quiz\userdata\user_overrides;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/lib.php');

/**
 * Test purging, exporting and counting of quiz user overrides
 * @group totara_userdata
 * @group mod_quiz
 */
class mod_quiz_userdata_user_overrides_testcase extends advanced_testcase {

    /**
     * Generates fixtures and return them as an anonymous class
     * to be used in the tests
     */
    public function setup_fixtures() {
        $this->resetAfterTest(true);

        $data = new class {
            /** @var target_user */
            public $activeuser, $controluser;
            /** @var stdClass */
            public $course1, $course2;
            /** @var stdClass */
            public $category1, $category2;
            /** @var stdClass */
            public $quiz1, $quiz2, $quiz3;
            /** @var stdClass */
            public $override11, $override12, $override13, $override14, $override15, $override16;
            /** @var stdClass */
            public $override21, $override22, $override23;
        };

        $data->activeuser = new target_user($this->getDataGenerator()->create_user());
        $data->controluser = new target_user($this->getDataGenerator()->create_user());

        $data->category1 = $this->getDataGenerator()->create_category();
        $data->category2 = $this->getDataGenerator()->create_category();

        $data->course1 = $this->getDataGenerator()->create_course(['category' => $data->category1->id]);
        $data->course2 = $this->getDataGenerator()->create_course(['category' => $data->category2->id]);

        $data->quiz1 = $this->create_quiz($data->course1);
        $data->quiz2 = $this->create_quiz($data->course2);
        $data->quiz3 = $this->create_quiz($data->course2);

        // Usually there will be only one override per user and quiz
        // but for easier testability we create multiple entries here.
        $data->override11 = $this->create_override($data->quiz1, $data->activeuser);
        $data->override12 = $this->create_override($data->quiz2, $data->activeuser);
        $data->override13 = $this->create_override($data->quiz2, $data->activeuser);
        $data->override14 = $this->create_override($data->quiz3, $data->activeuser);
        $data->override15 = $this->create_override($data->quiz3, $data->activeuser);
        $data->override16 = $this->create_override($data->quiz3, $data->activeuser);
        $data->override21 = $this->create_override($data->quiz1, $data->controluser);
        $data->override22 = $this->create_override($data->quiz2, $data->controluser);
        $data->override23 = $this->create_override($data->quiz3, $data->controluser);

        return $data;
    }

    /**
     * test compatible context levels
     */
    public function test_compatible_context_levels() {
        $contextlevels = user_overrides::get_compatible_context_levels();
        $this->assertCount(4, $contextlevels);
        $this->assertContains(CONTEXT_SYSTEM, $contextlevels);
        $this->assertContains(CONTEXT_COURSECAT, $contextlevels);
        $this->assertContains(CONTEXT_COURSE, $contextlevels);
        $this->assertContains(CONTEXT_MODULE, $contextlevels);
    }

    /**
     * Test the abilities to purge, export and count
     */
    public function test_abilities() {
        $this->assertTrue(user_overrides::is_countable());
        $this->assertFalse(user_overrides::is_exportable());
        $this->assertTrue(user_overrides::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(user_overrides::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(user_overrides::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge_system() {
        global $DB;

        $data = $this->setup_fixtures();

        /****************************
         * PURGE activeuser
         ***************************/

        $result = user_overrides::execute_purge($data->activeuser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $this->assertEmpty($DB->get_record('quiz_overrides', ['id' => $data->override11->id]));
        $this->assertEmpty($DB->get_record('quiz_overrides', ['id' => $data->override12->id]));
        $this->assertEmpty($DB->get_record('quiz_overrides', ['id' => $data->override13->id]));
        $this->assertEmpty($DB->get_record('quiz_overrides', ['id' => $data->override14->id]));
        $this->assertEmpty($DB->get_record('quiz_overrides', ['id' => $data->override15->id]));
        $this->assertEmpty($DB->get_record('quiz_overrides', ['id' => $data->override16->id]));

        /****************************
         * CHECK controluser
         ***************************/

        $this->assertNotEmpty($DB->get_record('quiz_overrides', ['id' => $data->override21->id]));
        $this->assertNotEmpty($DB->get_record('quiz_overrides', ['id' => $data->override22->id]));
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge_coursecat() {
        global $DB;

        $data = $this->setup_fixtures();

        /****************************
         * PURGE activeuser
         ***************************/

        $result = user_overrides::execute_purge($data->activeuser, context_coursecat::instance($data->category1->id));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $this->assertEmpty($DB->get_record('quiz_overrides', ['id' => $data->override11->id]));
        $this->assertNotEmpty($DB->get_record('quiz_overrides', ['id' => $data->override12->id]));
        $this->assertNotEmpty($DB->get_record('quiz_overrides', ['id' => $data->override13->id]));
        $this->assertNotEmpty($DB->get_record('quiz_overrides', ['id' => $data->override14->id]));
        $this->assertNotEmpty($DB->get_record('quiz_overrides', ['id' => $data->override15->id]));
        $this->assertNotEmpty($DB->get_record('quiz_overrides', ['id' => $data->override16->id]));

        /****************************
         * CHECK controluser
         ***************************/

        $this->assertNotEmpty($DB->get_record('quiz_overrides', ['id' => $data->override21->id]));
        $this->assertNotEmpty($DB->get_record('quiz_overrides', ['id' => $data->override22->id]));
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge_course() {
        global $DB;

        $data = $this->setup_fixtures();

        /****************************
         * PURGE activeuser
         ***************************/

        $result = user_overrides::execute_purge($data->activeuser, context_course::instance($data->course2->id));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $this->assertNotEmpty($DB->get_record('quiz_overrides', ['id' => $data->override11->id]));
        $this->assertEmpty($DB->get_record('quiz_overrides', ['id' => $data->override12->id]));
        $this->assertEmpty($DB->get_record('quiz_overrides', ['id' => $data->override13->id]));
        $this->assertEmpty($DB->get_record('quiz_overrides', ['id' => $data->override14->id]));
        $this->assertEmpty($DB->get_record('quiz_overrides', ['id' => $data->override15->id]));
        $this->assertEmpty($DB->get_record('quiz_overrides', ['id' => $data->override16->id]));

        /****************************
         * CHECK controluser
         ***************************/

        $this->assertNotEmpty($DB->get_record('quiz_overrides', ['id' => $data->override21->id]));
        $this->assertNotEmpty($DB->get_record('quiz_overrides', ['id' => $data->override22->id]));
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge_module() {
        global $DB;

        $data = $this->setup_fixtures();

        /****************************
         * PURGE activeuser
         ***************************/

        $result = user_overrides::execute_purge($data->activeuser, context_module::instance($data->quiz3->cmid));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $this->assertNotEmpty($DB->get_record('quiz_overrides', ['id' => $data->override11->id]));
        $this->assertNotEmpty($DB->get_record('quiz_overrides', ['id' => $data->override12->id]));
        $this->assertNotEmpty($DB->get_record('quiz_overrides', ['id' => $data->override13->id]));
        $this->assertEmpty($DB->get_record('quiz_overrides', ['id' => $data->override14->id]));
        $this->assertEmpty($DB->get_record('quiz_overrides', ['id' => $data->override15->id]));
        $this->assertEmpty($DB->get_record('quiz_overrides', ['id' => $data->override16->id]));

        /****************************
         * CHECK controluser
         ***************************/

        $this->assertNotEmpty($DB->get_record('quiz_overrides', ['id' => $data->override21->id]));
        $this->assertNotEmpty($DB->get_record('quiz_overrides', ['id' => $data->override22->id]));
    }

    /**
     * test if data is correctly counted
     */
    public function test_count() {
        $data = $this->setup_fixtures();

        /****************************
         * COUNT activeuser
         ***************************/

        // Count all overrides.
        $result = user_overrides::execute_count($data->activeuser, context_system::instance());
        $this->assertEquals(6, $result);

        // Count overrides in categories.
        $result = user_overrides::execute_count($data->activeuser, context_coursecat::instance($data->category1->id));
        $this->assertEquals(1, $result);
        $result = user_overrides::execute_count($data->activeuser, context_coursecat::instance($data->category2->id));
        $this->assertEquals(5, $result);

        // Count overrides in course.
        $result = user_overrides::execute_count($data->activeuser, context_course::instance($data->course1->id));
        $this->assertEquals(1, $result);
        $result = user_overrides::execute_count($data->activeuser, context_course::instance($data->course2->id));
        $this->assertEquals(5, $result);

        // Count overrides in modules.
        $result = user_overrides::execute_count($data->activeuser, context_module::instance($data->quiz1->cmid));
        $this->assertEquals(1, $result);
        $result = user_overrides::execute_count($data->activeuser, context_module::instance($data->quiz2->cmid));
        $this->assertEquals(2, $result);
        $result = user_overrides::execute_count($data->activeuser, context_module::instance($data->quiz3->cmid));
        $this->assertEquals(3, $result);
    }

    /**
     * @param stdClass $quiz
     * @param target_user $user
     * @return stdClass
     */
    private function create_override(stdClass $quiz, target_user $user): stdClass {
        global $DB;

        $override = (object)[
            'userid' => $user->id,
            'quiz' => $quiz->id,
            'attempts' => rand(1, 10)
        ];
        $id = $DB->insert_record('quiz_overrides', $override);

        return $DB->get_record('quiz_overrides', ['id' => $id]);
    }

    /**
     * @param stdClass $course
     * @return stdClass
     */
    private function create_quiz(stdClass $course): stdClass {
        /** @var mod_quiz_generator $quizgenerator */
        /** @var core_question_generator $questiongenerator */
        // Setup quiz1 with 2 questions.
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        // Quiz1 in course1 with one question.
        $quiz = $quizgenerator->create_instance([
            'course' => $course->id,
            'questionsperpage' => 0,
            'grade' => 100.0,
            'sumgrades' => 1
        ]);
        $cat = $questiongenerator->create_question_category();
        $question = $questiongenerator->create_question('shortanswer', null, array('category' => $cat->id));
        quiz_add_quiz_question($question->id, $quiz);

        return $quiz;
    }

}