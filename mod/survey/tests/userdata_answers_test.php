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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package mod_survey
 */

use mod_survey\userdata\answers;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;
use totara_userdata\userdata\export;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/mod/survey/lib.php');

/**
 * @group totara_userdata
 */
class mod_survey_userdata_answers_testcase extends externallib_advanced_testcase {

    /**
     * Set up data.
     *
     * @return object
     */
    public function setup_data() {
        $data = new class() {
            /**
             * @var totara_userdata\userdata\target_user
             */
            public $targetuser1, $targetuser2;

            /**
             * @var stdClass
             */
            public $learner1, $learner2, $learner3;

            /**
             * @var stdClass
             */
            public $manager1;

            /**
             * @var stdClass
             */
            public $cat1, $cat2, $cat3;

            /**
             * @var stdClass
             */
            public $course1, $course2, $course3;

            /**
             * @var stdClass
             */
            public $survey1, $survey2, $survey3;
        };

        // Create users.
        $data->learner1 = self::getDataGenerator()->create_user();
        $data->learner2 = self::getDataGenerator()->create_user();
        $data->learner3 = self::getDataGenerator()->create_user();
        $data->manager1 = self::getDataGenerator()->create_user();

        // Set up the target users.
        $data->targetuser1 = new target_user($data->learner1);
        $data->targetuser2 = new target_user($data->learner2);
        $data->targetuser3 = new target_user($data->learner3);

        $this->resetAfterTest(true);
        $this->setAdminUser();

        return $data;
    }

    /**
     * Create a course category
     *
     * @return coursecat
     */
    public function create_course_catogory() {
        return $this->getDataGenerator()->create_category();
    }

    /**
     * Create a categpory
     *
     * @param int|null $category
     * @return stdClass
     */
    public function create_course(int $category = null) {
        $coursedefaults = [];
        if ($category) {
            $coursedefaults['category'] = $category;
        }
        return $this->getDataGenerator()->create_course($coursedefaults);
    }

    /**
     * Create a course
     *
     * @param stdClass $course
     * @return stdClass
     */
    public function create_survey(stdClass $course) {
        return $this->getDataGenerator()->create_module('survey', array('course' => $course->id));
    }

    /**
     * Enrol a user into a course
     *
     * @param $user
     * @param $course
     */
    public function enrol_user(stdClass $user, stdClass $course) {
        $this->assertTrue($this->getDataGenerator()->enrol_user($user->id, $course->id));
    }

    /**
     * Complete the survey
     *
     * @param stdClass $user
     * @param stdClass $survey
     */
    public function do_survey(stdClass $user, stdClass $survey) {

        // Test user with full capabilities.
        $this->setUser($user);

        // Build our questions and responses array.
        $realquestions = array();
        $questions = survey_get_questions($survey);

        $i = 5;
        foreach ($questions as $q) {
            if ($q->type >= 0) {
                if ($q->multi) {
                    $subquestions = survey_get_subquestions($q);
                    foreach ($subquestions as $sq) {
                        $realquestions[] = array(
                            'key' => 'q' . $sq->id,
                            'value' => $i % 5 + 1   // Values between 1 and 5.
                        );
                        $i++;
                    }
                } else {
                    $realquestions[] = array(
                        'key' => 'q' . $q->id,
                        'value' => $i % 5 + 1
                    );
                    $i++;
                }
            }
        }

        $result = mod_survey_external::submit_answers($survey->id, $realquestions);
        $result = external_api::clean_returnvalue(mod_survey_external::submit_answers_returns(), $result);

        $this->assertTrue($result['status']);
        $this->assertCount(0, $result['warnings']);
    }

    /**
     * Validate answers
     *
     * @param stdClass $user
     * @param array $anwers
     */
    public function validate_answers(stdClass $user, array $anwers) {
        foreach ($anwers as $anwer) {
            $this->assertEquals($anwer->userid, $user->id);
        }
    }

    /**
     * Test function is_compatible_context_level with all possible contexts.
     */
    public function test_get_compatible_context_levels() {
        $this->assertEquals([CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE, CONTEXT_MODULE], answers::get_compatible_context_levels());
    }

    /**
     * Test function is_purgeable.
     */
    public function test_is_purgeable() {
        $this->assertFalse(answers::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertFalse(answers::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertFalse(answers::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * Test function is_exportable.
     */
    public function test_is_exportable() {
        $this->assertTrue(answers::is_exportable());
    }

    /**
     * Test the export function in system context.
     */
    public function test_export_system_context() {

        $data = $this->setup_data();

        // Create a course and survey.
        $data->course1 = $this->create_course();
        $data->survey1 = $this->create_survey($data->course1);

        // Enrol 3 users.
        $this->enrol_user($data->learner1, $data->course1);
        $this->enrol_user($data->learner2, $data->course1);
        $this->enrol_user($data->learner3, $data->course1);

        // Both users to complete the survey.
        $this->do_survey($data->learner1, $data->survey1);
        $this->do_survey($data->learner2, $data->survey1);

        // Execute the export for user 1.
        $results = answers::execute_export($data->targetuser1, context_system::instance());
        $this->assertInstanceOf(export::class, $results);
        $this->assertCount(20, $results->data);
        $this->assertCount(0, $results->files);
        $this->validate_answers($data->learner1, $results->data);

        // Execute the export for user 2.
        $results = answers::execute_export($data->targetuser2, context_system::instance());
        $this->assertInstanceOf(export::class, $results);
        $this->assertCount(20, $results->data);
        $this->assertCount(0, $results->files);
        $this->validate_answers($data->learner2, $results->data);

        // Execute the export for user 3.
        $results = answers::execute_export($data->targetuser3, context_system::instance());
        $this->assertInstanceOf(export::class, $results);
        $this->assertCount(0, $results->data);
        $this->assertCount(0, $results->files);
        $this->validate_answers($data->learner3, $results->data);
    }

    /**
     * Test the export function in coursecat context.
     */
    public function test_export_coursecat_context() {

        $data = $this->setup_data();

        // Create three categories.
        $data->cat1 = $this->create_course_catogory();
        $data->cat2 = $this->create_course_catogory();
        $data->cat3 = $this->create_course_catogory();

        // Create course 1 in category 1 and add survey, enrol user 1 and complete.
        $data->course1 = $this->create_course($data->cat1->id);
        $data->survey1 = $this->create_survey($data->course1);
        $this->enrol_user($data->learner1, $data->course1);
        $this->do_survey($data->learner1, $data->survey1);

        // Create course 2 in category 2 and add survey, enrol user 1 and complete.
        $data->course2 = $this->create_course($data->cat2->id);
        $data->survey2 = $this->create_survey($data->course2);
        $this->enrol_user($data->learner1, $data->course2);
        $this->do_survey($data->learner1, $data->survey2);

        // Create course 3 in no category and add survey, enrol user 1 to complete.
        $data->course3 = $this->create_course();
        $data->survey3 = $this->create_survey($data->course3);
        $this->enrol_user($data->learner1, $data->course3);
        $this->do_survey($data->learner1, $data->survey3);

        // Execute the export for user 1, category 1.
        $results = answers::execute_export($data->targetuser1, context_coursecat::instance($data->cat1->id));
        $this->assertInstanceOf(export::class, $results);
        $this->assertCount(20, $results->data);
        $this->assertCount(0, $results->files);
        $this->validate_answers($data->learner1, $results->data);

        // Execute the export for user 1, category 2.
        $results = answers::execute_export($data->targetuser1, context_coursecat::instance($data->cat2->id));
        $this->assertInstanceOf(export::class, $results);
        $this->assertCount(20, $results->data);
        $this->assertCount(0, $results->files);
        $this->validate_answers($data->learner1, $results->data);

        // Execute the export for user 1, category 3.
        $results = answers::execute_export($data->targetuser1, context_coursecat::instance($data->cat3->id));
        $this->assertInstanceOf(export::class, $results);
        $this->assertCount(0, $results->data);
        $this->assertCount(0, $results->files);
        $this->validate_answers($data->learner1, $results->data);
    }

    /**
     * Test the export function in course context.
     */
    public function test_export_course_context() {

        $data = $this->setup_data();

        // Create course 1 and add survey, enrol user 1 and 2 to complete.
        $data->course1 = $this->create_course();
        $data->survey1 = $this->create_survey($data->course1);
        $this->enrol_user($data->learner1, $data->course1);
        $this->enrol_user($data->learner2, $data->course1);
        $this->do_survey($data->learner1, $data->survey1);
        $this->do_survey($data->learner2, $data->survey1);

        // Create course 2 and add survey, enrol user 1 to complete.
        $data->course2 = $this->create_course();
        $data->survey2 = $this->create_survey($data->course2);
        $this->enrol_user($data->learner1, $data->course2);
        $this->do_survey($data->learner1, $data->survey2);

        // Execute the export for user 1 course 1 context.
        $results = answers::execute_export($data->targetuser1, context_course::instance($data->course1->id));
        $this->assertInstanceOf(export::class, $results);
        $this->assertCount(20, $results->data);
        $this->assertCount(0, $results->files);
        $this->validate_answers($data->learner1, $results->data);

        // Execute the export for user 1 course 2 context.
        $results = answers::execute_export($data->targetuser1, context_course::instance($data->course2->id));
        $this->assertInstanceOf(export::class, $results);
        $this->assertCount(20, $results->data);
        $this->assertCount(0, $results->files);
        $this->validate_answers($data->learner1, $results->data);

        // Execute the export for user 2 course 2 context.
        $results = answers::execute_export($data->targetuser2, context_course::instance($data->course2->id));
        $this->assertInstanceOf(export::class, $results);
        $this->assertCount(0, $results->data);
        $this->assertCount(0, $results->files);
    }

    /**
     * Test the export function in module context.
     */
    public function test_export_module_context() {

        $data = $this->setup_data();

        // Create course 1 and enrol user 1 and 2.
        $data->course1 = $this->create_course();
        $this->enrol_user($data->learner1, $data->course1);
        $this->enrol_user($data->learner1, $data->course1);

        // Create 2 survey modules, user 1 and 2 to complete.
        $data->survey1 = $this->create_survey($data->course1);
        $data->survey2 = $this->create_survey($data->course1);
        $this->do_survey($data->learner1, $data->survey1);
        $this->do_survey($data->learner1, $data->survey2);

        // Create a third survey module.
        $data->survey3 = $this->create_survey($data->course1);

        // Execute the export for user 1 survey module 1 context.
        $results = answers::execute_export($data->targetuser1, context_module::instance($data->survey1->cmid));
        $this->assertInstanceOf(export::class, $results);
        $this->assertCount(20, $results->data);
        $this->assertCount(0, $results->files);
        $this->validate_answers($data->learner1, $results->data);

        // Execute the export for user 1 survey module 2 context.
        $results = answers::execute_export($data->targetuser1, context_module::instance($data->survey2->cmid));
        $this->assertInstanceOf(export::class, $results);
        $this->assertCount(20, $results->data);
        $this->assertCount(0, $results->files);
        $this->validate_answers($data->learner1, $results->data);

        // Execute the export for user 1 survey module 3 context.
        $results = answers::execute_export($data->targetuser1, context_module::instance($data->survey3->cmid));
        $this->assertInstanceOf(export::class, $results);
        $this->assertCount(0, $results->data);
        $this->assertCount(0, $results->files);

    }

    /**
     * Test function is_countable.
     */
    public function test_is_countable() {
        $this->assertTrue(answers::is_countable());
    }

    /**
     * Test the count in system context.
     */
    public function test_count_system_context() {

        $data = $this->setup_data();

        // Create a course and survey.
        $data->course1 = $this->create_course();
        $data->survey1 = $this->create_survey($data->course1);

        // Enrol two users.
        $this->enrol_user($data->learner1, $data->course1);
        $this->enrol_user($data->learner2, $data->course1);

        // Both users to complete the survey.
        $this->do_survey($data->learner1, $data->survey1);
        $this->do_survey($data->learner2, $data->survey1);

        // Test the count for user 1.
        $this->assertEquals(20, answers::execute_count($data->targetuser1, context_system::instance()));

        // Test the count for user 2.
        $this->assertEquals(20, answers::execute_count($data->targetuser2, context_system::instance()));

        // Test the count for user 3.
        $this->assertEquals(0, answers::execute_count($data->targetuser3, context_system::instance()));
    }

    /**
     * Test the count in coursecat context.
     */
    public function test_count_coursecat_context() {

        $data = $this->setup_data();

        // Create three categories.
        $data->cat1 = $this->create_course_catogory();
        $data->cat2 = $this->create_course_catogory();
        $data->cat3 = $this->create_course_catogory();

        // Create course 1 in category 1 and add survey, enrol user 1 and complete.
        $data->course1 = $this->create_course($data->cat1->id);
        $data->survey1 = $this->create_survey($data->course1);
        $this->enrol_user($data->learner1, $data->course1);
        $this->do_survey($data->learner1, $data->survey1);

        // Create course 2 in category 2 and add survey, enrol user 1 and complete.
        $data->course2 = $this->create_course($data->cat2->id);
        $data->survey2 = $this->create_survey($data->course2);
        $this->enrol_user($data->learner1, $data->course2);
        $this->do_survey($data->learner1, $data->survey2);

        // Create course 3 in no category and add survey, enrol user 1 and complete.
        $data->course3 = $this->create_course();
        $data->survey3 = $this->create_survey($data->course3);
        $this->enrol_user($data->learner1, $data->course3);
        $this->do_survey($data->learner1, $data->survey3);

        // Test the count for category 1 user 1.
        $this->assertEquals(20, answers::execute_count($data->targetuser1, context_coursecat::instance($data->cat1->id)));

        // Test the count for category 2 user 1.
        $this->assertEquals(20, answers::execute_count($data->targetuser1, context_coursecat::instance($data->cat2->id)));

        // Test the count for category 3 user 1.
        $this->assertEquals(0, answers::execute_count($data->targetuser1, context_coursecat::instance($data->cat3->id)));
    }

    /**
     * Test the count in course context.
     */
    public function test_count_course_context() {

        $data = $this->setup_data();

        // Create course 1 and add survey, enrol user 1 and 2 and complete.
        $data->course1 = $this->create_course();
        $data->survey1 = $this->create_survey($data->course1);
        $this->enrol_user($data->learner1, $data->course1);
        $this->enrol_user($data->learner2, $data->course1);
        $this->do_survey($data->learner1, $data->survey1);
        $this->do_survey($data->learner2, $data->survey1);

        // Create course 2 and add survey, enrol user 1 and complete.
        $data->course2 = $this->create_course();
        $data->survey2 = $this->create_survey($data->course2);
        $this->enrol_user($data->learner1, $data->course2);
        $this->do_survey($data->learner1, $data->survey2);

        // Test the count for user 1 course 1 context.
        $this->assertEquals(20, answers::execute_count($data->targetuser1, context_course::instance($data->course1->id)));

        // Test the count for user 1 course 2 context.
        $this->assertEquals(20, answers::execute_count($data->targetuser1, context_course::instance($data->course2->id)));

        // Test the count for user 2 course 2 context.
        $this->assertEquals(0, answers::execute_count($data->targetuser2, context_course::instance($data->course2->id)));
    }

    /**
     * Test the count in module context.
     */
    public function test_count_module_context() {

        $data = $this->setup_data();

        // Create course 1 and enrol user 1 and 2.
        $data->course1 = $this->create_course();
        $this->enrol_user($data->learner1, $data->course1);
        $this->enrol_user($data->learner1, $data->course1);

        // Create 2 survey modules, user 1 and 2 to complete.
        $data->survey1 = $this->create_survey($data->course1);
        $data->survey2 = $this->create_survey($data->course1);
        $this->do_survey($data->learner1, $data->survey1);
        $this->do_survey($data->learner1, $data->survey2);

        // Create survey 3 module, user 1 to complete.
        $data->survey3 = $this->create_survey($data->course1);
        $this->do_survey($data->learner1, $data->survey3);

        // Test the count for user 1 module 1 context.
        $this->assertEquals(20, answers::execute_count($data->targetuser1, context_module::instance($data->survey1->cmid)));

        // Test the count for user 1 module 2 context.
        $this->assertEquals(20, answers::execute_count($data->targetuser1, context_module::instance($data->survey2->cmid)));

        // Test the count for user 2 module 3 context.
        $this->assertEquals(0, answers::execute_count($data->targetuser2, context_module::instance($data->survey3->cmid)));
    }
}
