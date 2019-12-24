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
 * @package mod_feedback
 */

use mod_feedback\userdata\temporary;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;
use totara_userdata\userdata\export;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/feedback/lib.php');
require_once($CFG->libdir . '/completionlib.php');

/**
 * @group mod_feedback
 * @group totara_userdata
 */
class mod_feedback_userdata_temporary_testcase extends advanced_testcase {

    /**
     * Set up data.
     *
     * @return object
     */
    private function setup_data() {

        $data = new class() {
            /* @var totara_userdata\userdata\target_user  */
            public $targetuser;

            /*  @var stdClass */
            public $learner1;

            /* @var stdClass */
            public $learner2;

            /* @var stdClass */
            public $learner3;

            /*  @var stdClass */
            public $cat1;

            /*  @var stdClass */
            public $cat2;

            /*  @var stdClass */
            public $course1;

            /*  @var stdClass */
            public $course2;

            /* @var completion_info */
            public $completioninfo;

            /*  @var stdClass */
            public $feedback1;

            /*  @var stdClass */
            public $feedback2;

            /*  @var stdClass */
            public $feedback3;
        };

        // Create users.
        $data->learner1 = self::getDataGenerator()->create_user();
        $data->learner2 = self::getDataGenerator()->create_user();
        $data->learner3 = self::getDataGenerator()->create_user();

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
     * @return coursecat coursecat
     */
    public function create_course_catogory() {
        return $this->getDataGenerator()->create_category();
    }

    /**
     * Create a course
     *
     * @param $data
     * @param stdClass|null $category
     * @return stdClass
     */
    public function create_course($data, $category = null) {

        $coursedefaults = ['enablecompletion' => COMPLETION_ENABLED];
        if ($category) {
            $coursedefaults['category'] = $category;
        }
        $course = $this->getDataGenerator()->create_course($coursedefaults);

        // Check it has course completion.
        $data->completioninfo = new completion_info($course);
        $this->assertEquals(COMPLETION_ENABLED, $data->completioninfo->is_enabled());

        return $course;
    }

    /**
     * Create a feedback activity
     *
     * @param stdClass $course
     * @param array $questions
     * @return stdClass
     */
    public function create_feedback(stdClass $course, array $questions) {
        global $DB;

        $completiondefaults = [
            'completion' => COMPLETION_TRACKING_MANUAL,
            'completionview' => COMPLETION_VIEW_REQUIRED
        ];
        $feedback = $this->getDataGenerator()->create_module(
            'feedback',
            ['course' => $course->id, 'completionsubmit' => 1],
            $completiondefaults);

        $this->assertEquals(1, $DB->count_records('feedback', ['id' => $feedback->id]));

        // Create some feedback questions - need to create manually because the
        // feedback_item_textfield->save_item() function depends on form->get_data().
        $this->assertEquals(0, $DB->count_records('feedback_item', ['feedback' => $feedback->id]));

        $count = 1;
        foreach ($questions as $question) {
            $feedback_item = new stdClass();
            $feedback_item->feedback = $feedback->id;
            $feedback_item->template = 0;
            $feedback_item->name = $question;
            $feedback_item->label = str_replace(' ', '', $question);
            $feedback_item->presentation = '30|255';
            $feedback_item->typ = 'textfield'; // Using textfield type for all tests.
            $feedback_item->hasvalue = 1;
            $feedback_item->position = $count;
            $feedback_item->required = 0;
            $feedback_item->dependitem = 0;
            $feedback_item->dependvalue = '';
            $feedback_item->options = '';
            $feedback_item->id = $DB->insert_record('feedback_item', $feedback_item);
            $this->assertEquals($count, $DB->count_records('feedback_item', ['feedback' => $feedback->id]));

            $count++;
        }

        return $feedback;
    }

    /**
     * Enrol a user into a course
     *
     * @param stdClass $user
     * @param stdClass $course
     */
    public function enrol_user(stdClass $user, stdClass $course) {
        $this->assertTrue($this->getDataGenerator()->enrol_user($user->id, $course->id));
    }

    /**
     * Give a feedback response
     *
     * @param stdClass $user
     * @param stdClass $course
     * @param stdClass $feedback
     * @param array $answers
     */
    public function give_feedback(stdClass $user, stdClass $course, stdClass $feedback, array $answers) {
        global $DB;

        // Save values for the questions - as in /mod/feedback/complete.php
        // feedback_save_values() - depends on optional_params() so need to do save the values manually
        $current_num_feedback_completed = $DB->count_records('feedback_completedtmp');

        $time = time();
        $timemodified = mktime(0, 0, 0, date('m', $time), date('d', $time), date('Y', $time));

        $completed = new stdClass();
        $completed->feedback           = $feedback->id;
        $completed->userid             = $user->id;
        $completed->guestid            = false;
        $completed->timemodified       = $timemodified;
        $completed->anonymous_response = false;
        $completedid = $DB->insert_record('feedback_completedtmp', $completed);
        $this->assertEquals($current_num_feedback_completed + 1, $DB->count_records('feedback_completedtmp'));

        $current_num_feedback_value = $DB->count_records('feedback_valuetmp');

        // Get feedback items.
        $items = array_values($DB->get_records('feedback_item', ['feedback' => $feedback->id], 'id ASC'));

        $count = 1;
        foreach ($answers as $answer) {
            $itemobj = feedback_get_item_class('textfield'); // Using textfield type for all tests.
            $value = new stdClass();
            $value->item = $items[$count - 1]->id;
            $value->completed = $completedid;
            $value->course_id = $course->id;
            $value->value = $itemobj->create_value($answer);
            $DB->insert_record('feedback_valuetmp', $value);
            $this->assertEquals($current_num_feedback_value + $count, $DB->count_records('feedback_valuetmp'));

            $count++;
        }
    }

    /**
     * Validate the export data
     *
     * @param array $exportdata
     * @param stdClass $user
     */
    public function validate_export(array $exportdata, stdClass $user) {
        global $DB;

        $sql = "SELECT count(fv.id)
                  FROM {feedback_valuetmp} fv
                  JOIN {feedback_completedtmp} fc ON fc.id = fv.completed
                  JOIN {feedback_item} fi ON fi.id = fv.item
                  JOIN {feedback} f ON f.id = fi.feedback
                 WHERE fv.id = :id
                   AND fc.userid = :userid
                   AND f.name = :feedbackname
                   AND fi.name = :question
                   AND fv.value = :response";

        foreach ($exportdata as $record) {
            $params = [
                'id' => $record->id,
                'userid' => $user->id,
                'feedbackname' => $record->feedbackname,
                'question' => $record->question,
                'response' => $record->response,
            ];

            $this->assertEquals(1, $DB->count_records_sql($sql, $params));
        }
    }

    /**
     * Test function is_compatible_context_level with all possible contexts.
     */
    public function test_get_compatible_context_levels() {
        $this->assertEquals([CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE, CONTEXT_MODULE], temporary::get_compatible_context_levels());
    }

    /**
     * Test function is_purgeable.
     */
    public function test_is_purgeable() {
        $this->assertTrue(temporary::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(temporary::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(temporary::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * Test the purge function in the system context.
     */
    public function test_purge_system_context() {
        global $DB;

        // Setup the course and data, which will create,
        // * 1 course.
        // * 2 feedback activity with 2 questions.
        // * 2 enrolled learners.
        // * Both learners to have completed saved responses to the 4 feedback questions
        $data = $this->setup_data();
        $data->course1 = $this->create_course($data);
        $data->feedback1 = $this->create_feedback($data->course1,  ['What is 1+1', 'What is your favorite food']);
        $data->feedback2 = $this->create_feedback($data->course1,  ['What is 2+2', 'What is your worst food']);

        // User 1.
        $this->enrol_user($data->learner1, $data->course1);
        $this->give_feedback($data->learner1, $data->course1, $data->feedback1, ['5', 'Cake!']);
        $this->give_feedback($data->learner1, $data->course1, $data->feedback2, ['7', 'Cabbage']);

        // User 2.
        $this->enrol_user($data->learner2, $data->course1);
        $this->give_feedback($data->learner2, $data->course1, $data->feedback1, ['2', 'Hot Dogs']);
        $this->give_feedback($data->learner2, $data->course1, $data->feedback2, ['4', 'Parsnip']);

        // We should have 4 saved record from each of the two users.
        $this->assertCount(8, $DB->get_records('feedback_valuetmp'));

        // Execute the purge.
        $status = temporary::execute_purge($data->targetuser1, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);
        // We should now just have 4 saved record from the other user.
        $this->assertCount(4, $DB->get_records('feedback_valuetmp'));
        // Recheck the user count is now 0.
        $this->assertEquals(0, temporary::execute_count($data->targetuser1, context_system::instance()));

        // User 2 should be unaffected.
        $this->assertEquals(4, temporary::execute_count($data->targetuser2, context_system::instance()));
    }

    /**
     * Test the purge function in the coursecat context.
     */
    public function test_purge_coursecat_context() {
        global $DB;

        // Setup the course and data, which will create,
        // * 2 course categories.
        // * 2 courses, one in category 1 and the other not in any category.
        // * 1 feedback activity with 2 questions in each course.
        // * 1 enrolled learner in each course.
        // * The learner has completed saved responses to the 2 feedback questions
        $data = $this->setup_data();

        // Create 2 categories.
        $data->cat1 = $this->create_course_catogory();
        $data->cat2 = $this->create_course_catogory();

        // Create course 1 in category 1 and add feedback activity.
        $data->course1 = $this->create_course($data, $data->cat1->id);
        $data->feedback1 = $this->create_feedback($data->course1,  ['What is 1+1', 'What is your favorite food']);

        // Create course 2 not in a category and add feedback activity.
        $data->course2 = $this->create_course($data);
        $data->feedback2 = $this->create_feedback($data->course2,  ['What is 2+2', 'What is your worst food']);

        // User 1, course 1, give feedback.
        $this->enrol_user($data->learner1, $data->course1);
        $this->give_feedback($data->learner1, $data->course1, $data->feedback1, ['5', 'Cake!']);

        // User 1, course 2, give feedback.
        $this->enrol_user($data->learner1, $data->course1);
        $this->give_feedback($data->learner1, $data->course2, $data->feedback2, ['4', 'Parsnip']);

        // We should have 2 saved record from each of the two courses.
        $this->assertCount(4, $DB->get_records('feedback_valuetmp'));

        // Check the count for user 1 in course 1 that is assigned to the category.
        $this->assertEquals(2, temporary::execute_count($data->targetuser1, context_coursecat::instance($data->cat1->id)));
        // Check the count for user 1 in course 2 that is not assigned to the category.
        $this->assertEquals(0, temporary::execute_count($data->targetuser1, context_coursecat::instance($data->cat2->id)));

        // Execute the purge for category 1.
        $status = temporary::execute_purge($data->targetuser1, context_coursecat::instance($data->cat1->id));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);
        // We should now just have 2 saved records.
        $this->assertCount(2, $DB->get_records('feedback_valuetmp'));
        // Recheck the user count is now 0.
        $this->assertEquals(0, temporary::execute_count($data->targetuser1, context_coursecat::instance($data->cat1->id)));

        // Execute the purge for category 2.
        $status = temporary::execute_purge($data->targetuser1, context_coursecat::instance($data->cat2->id));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);
        // We should still  have 2 saved records.
        $this->assertCount(2, $DB->get_records('feedback_valuetmp'));
    }

    /**
     * Test the purge function in the course context.
     */
    public function test_purge_course_context() {
        global $DB;

        // Setup the course and data, which will create,
        // * 3 courses all with feedback activity.
        // * User 1 enrolled into course 1 and 2.
        $data = $this->setup_data();

        // Create course 1 and add feedback activity.
        $data->course1 = $this->create_course($data);
        $data->feedback1 = $this->create_feedback($data->course1,  ['What is 1+1', 'What is your favorite food']);

        // Create course 2 and add feedback activity.
        $data->course2 = $this->create_course($data);
        $data->feedback2 = $this->create_feedback($data->course2,  ['What is 2+2', 'What is your worst food']);

        // Create course 3 and add feedback activity.
        $data->course3 = $this->create_course($data);
        $data->feedback3 = $this->create_feedback($data->course3,  ['What is 3+3', 'What is your favorite band']);

        // User 1, course 1, give feedback.
        $this->enrol_user($data->learner1, $data->course1);
        $this->give_feedback($data->learner1, $data->course1, $data->feedback1, ['5', 'Cake!']);

        // User 1, course 2, give feedback.
        $this->enrol_user($data->learner1, $data->course2);
        $this->give_feedback($data->learner1, $data->course2, $data->feedback2, ['4', 'Parsnip']);

        // We should have 2 saved record from each of the two courses.
        $this->assertCount(4, $DB->get_records('feedback_valuetmp'));

        // Check the count for user 1 in course 1 context.
        $this->assertEquals(2, temporary::execute_count($data->targetuser1, context_course::instance($data->course1->id)));
        // Check the count for user 1 in course 2 context.
        $this->assertEquals(2, temporary::execute_count($data->targetuser1, context_course::instance($data->course2->id)));
        // Check the count for user 1 in course 3 context
        $this->assertEquals(0, temporary::execute_count($data->targetuser1, context_course::instance($data->course3->id)));

        // Execute the purge using course 1 context.
        // First check our count.
        $this->assertEquals(2, temporary::execute_count($data->targetuser1, context_course::instance($data->course1->id)));
        // Run the purge.
        $status = temporary::execute_purge($data->targetuser1, context_course::instance($data->course1->id));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);
        // Recheck the user count is now 0.
        $this->assertEquals(0, temporary::execute_count($data->targetuser1, context_course::instance($data->course1->id)));
        // We should now just have 2 saved record from the other course.
        $this->assertCount(2, $DB->get_records('feedback_valuetmp'));

        // Execute the purge using course 3 context.
        $status = temporary::execute_purge($data->targetuser1, context_course::instance($data->course3->id));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);
        // We should still have 2 saved records.
        $this->assertCount(2, $DB->get_records('feedback_valuetmp'));
    }

    /**
     * Test the purge function in the module context.
     */
    public function test_purge_module_context() {
        global $DB;

        // Setup the course and data, which will create,
        // * 1 course.
        // * 3 feedback activity with 2 questions.
        // * 2 enrolled learners.
        // * Both learners to have completed saved responses to the 2 feedback questions
        $data = $this->setup_data();

        // Create course 1 and add 2 feedback activities.
        $data->course1 = $this->create_course($data);
        $data->feedback1 = $this->create_feedback($data->course1,  ['What is 1+1', 'What is your favorite food']);
        $data->feedback2 = $this->create_feedback($data->course1,  ['What is 2+2', 'What is your worst food']);
        $data->feedback3 = $this->create_feedback($data->course1,  ['Some other question', 'And another...']);

        // User 1, course 1, respond to feedback 1 and 2.
        $this->enrol_user($data->learner1, $data->course1);
        $this->give_feedback($data->learner1, $data->course1, $data->feedback1, ['5', 'Cake!']);
        $this->give_feedback($data->learner1, $data->course1, $data->feedback2, ['4', 'Parsnip']);

        // User 2, course 1, respond to feedback 1.
        $this->enrol_user($data->learner2, $data->course1);
        $this->give_feedback($data->learner2, $data->course1, $data->feedback1, ['2', 'Everything']);
        $this->give_feedback($data->learner2, $data->course1, $data->feedback2, ['4', 'Nothing']);

        // We should have 4 saved record from each of the two users.
        $this->assertCount(8, $DB->get_records('feedback_valuetmp'));

        // Execute the purge using feedback module 1 context.
        // First check our count.
        $this->assertEquals(2, temporary::execute_count($data->targetuser1, context_module::instance($data->feedback1->cmid)));
        // Execute the purge.
        $status = temporary::execute_purge($data->targetuser1, context_module::instance($data->feedback1->cmid));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);
        // Recheck the user count is now 0.
        $this->assertEquals(0, temporary::execute_count($data->targetuser1, context_module::instance($data->feedback1->cmid)));
        // We should now just have 6 saved records, 2 from user 1 and 4 from user 2.
        $this->assertCount(6, $DB->get_records('feedback_valuetmp'));

        // Purge again for feedback module 3. This should not have eny effect.
        $status = temporary::execute_purge($data->targetuser1, context_module::instance($data->feedback3->cmid));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);
        $this->assertCount(6, $DB->get_records('feedback_valuetmp'));
    }

    /**
     * Test function is_exportable.
     */
    public function test_is_exportable() {
        $this->assertTrue(temporary::is_exportable());
    }

    /**
     * Test the export function in system context.
     */
    public function test_export_system_context() {
        global $DB;

        // Setup the course and data, which will create,
        // * 1 course.
        // * 2 feedback activity with 2 questions.
        // * 2 enrolled learners.
        // * Both learners to have completed saved responses to the 4 feedback questions
        $data = $this->setup_data();
        $data->course1 = $this->create_course($data);
        $data->feedback1 = $this->create_feedback($data->course1,  ['What is 1+1', 'What is your favorite food']);
        $data->feedback2 = $this->create_feedback($data->course1,  ['What is 2+2', 'What is your worst food']);

        // User 1.
        $this->enrol_user($data->learner1, $data->course1);
        $this->give_feedback($data->learner1, $data->course1, $data->feedback1, ['5', 'Cake!']);
        $this->give_feedback($data->learner1, $data->course1, $data->feedback2, ['7', 'Cabbage']);

        // User 2.
        $this->enrol_user($data->learner2, $data->course1);
        $this->give_feedback($data->learner2, $data->course1, $data->feedback1, ['2', 'Hot Dogs']);
        $this->give_feedback($data->learner2, $data->course1, $data->feedback2, ['4', 'Parsnip']);

        // We should have 4 saved record from each of the two users.
        $this->assertCount(8, $DB->get_records('feedback_valuetmp'));
        $this->assertEquals(4, temporary::execute_count($data->targetuser1, context_system::instance()));
        $this->assertEquals(4, temporary::execute_count($data->targetuser2, context_system::instance()));

        // Execute the export.
        $result = temporary::execute_export($data->targetuser1, context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(0, $result->files);
        $this->assertCount(4, $result->data);

        $this->validate_export($result->data, $data->learner1);
    }

    /**
     * Test the export function in coursecat_context context.
     */
    public function test_export_coursecat_context() {
        global $DB;

        // Setup the course and data, which will create,
        // * 2 course categories.
        // * 2 courses, one in category 1 and the other not in any category.
        // * 1 feedback activity with 2 questions in each course.
        // * 1 enrolled learner in each course.
        // * The learner has completed saved responses to the 2 feedback questions
        $data = $this->setup_data();

        // Create 2 categories.
        $data->cat1 = $this->create_course_catogory();
        $data->cat2 = $this->create_course_catogory();

        // Create course 1 in category 1 and add feedback activity.
        $data->course1 = $this->create_course($data, $data->cat1->id);
        $data->feedback1 = $this->create_feedback($data->course1,  ['What is 1+1', 'What is your favorite food']);

        // Create course 2 not in a category and add feedback activity.
        $data->course2 = $this->create_course($data);
        $data->feedback2 = $this->create_feedback($data->course2,  ['What is 2+2', 'What is your worst food']);

        // User 1, course 1, give feedback.
        $this->enrol_user($data->learner1, $data->course1);
        $this->give_feedback($data->learner1, $data->course1, $data->feedback1, ['5', 'Cake!']);

        // User 1, course 2, give feedback.
        $this->enrol_user($data->learner1, $data->course1);
        $this->give_feedback($data->learner1, $data->course2, $data->feedback2, ['4', 'Parsnip']);

        // We should have 2 saved record from each of the two courses.
        $this->assertCount(4, $DB->get_records('feedback_valuetmp'));

        // Check the count for user 1 in course 1 that is assigned to the category.
        $this->assertEquals(2, temporary::execute_count($data->targetuser1, context_coursecat::instance($data->cat1->id)));
        // Check the count for user 1 in course 2 that is not assigned to the category.
        $this->assertEquals(0, temporary::execute_count($data->targetuser1, context_coursecat::instance($data->cat2->id)));

        // Execute the export for category 1 context.
        $result = temporary::execute_export($data->targetuser1, context_coursecat::instance($data->cat1->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(0, $result->files);
        $this->assertCount(2, $result->data);

        $this->validate_export($result->data, $data->learner1);

        // Execute the export for category 2 context.
        $result = temporary::execute_export($data->targetuser1, context_coursecat::instance($data->cat2->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(0, $result->files);
        $this->assertCount(0, $result->data);
    }

    /**
     * Test the export function in course context.
     */
    public function test_export_course_context() {
        global $DB;

        // Setup the course and data, which will create,
        // * 3 courses all with feedback activity.
        // * User 1 enrolled into course 1 and 2.
        $data = $this->setup_data();

        // Create course 1 and add feedback activity.
        $data->course1 = $this->create_course($data);
        $data->feedback1 = $this->create_feedback($data->course1,  ['What is 1+1', 'What is your favorite food']);

        // Create course 2 and add feedback activity.
        $data->course2 = $this->create_course($data);
        $data->feedback2 = $this->create_feedback($data->course2,  ['What is 2+2', 'What is your worst food']);

        // Create course 3 and add feedback activity.
        $data->course3 = $this->create_course($data);
        $data->feedback3 = $this->create_feedback($data->course3,  ['What is 3+3', 'What is your favorite band']);

        // User 1, course 1, give feedback.
        $this->enrol_user($data->learner1, $data->course1);
        $this->give_feedback($data->learner1, $data->course1, $data->feedback1, ['5', 'Cake!']);

        // User 1, course 2, give feedback.
        $this->enrol_user($data->learner1, $data->course2);
        $this->give_feedback($data->learner1, $data->course2, $data->feedback2, ['4', 'Parsnip']);

        // We should have 2 saved record from each of the two courses.
        $this->assertCount(4, $DB->get_records('feedback_valuetmp'));

        // Check the count for user 1 in course 1 context.
        $this->assertEquals(2, temporary::execute_count($data->targetuser1, context_course::instance($data->course1->id)));
        // Check the count for user 1 in course 2 context.
        $this->assertEquals(2, temporary::execute_count($data->targetuser1, context_course::instance($data->course2->id)));
        // Check the count for user 1 in course 3 context
        $this->assertEquals(0, temporary::execute_count($data->targetuser1, context_course::instance($data->course3->id)));

        // Execute the export using course 1 context.
        $result = temporary::execute_export($data->targetuser1, context_course::instance($data->course1->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(0, $result->files);
        $this->assertCount(2, $result->data);

        $this->validate_export($result->data, $data->learner1);

        // Execute the export using course 3 context.
        $result = temporary::execute_export($data->targetuser1, context_course::instance($data->course3->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(0, $result->files);
        $this->assertCount(0, $result->data);
    }

    /**
     * Test the export function in module context.
     */
    public function test_export_module_context() {
        global $DB;

        // Setup the course and data, which will create,
        // * 1 course.
        // * 3 feedback activity with 2 questions.
        // * 2 enrolled learners.
        // * Both learners to have completed saved responses to the 2 feedback questions
        $data = $this->setup_data();

        // Create course 1 and add 2 feedback activities.
        $data->course1 = $this->create_course($data);
        $data->feedback1 = $this->create_feedback($data->course1,  ['What is 1+1', 'What is your favorite food']);
        $data->feedback2 = $this->create_feedback($data->course1,  ['What is 2+2', 'What is your worst food']);
        $data->feedback3 = $this->create_feedback($data->course1,  ['Some other question', 'And another...']);

        // User 1, course 1, respond to feedback 1 and 2.
        $this->enrol_user($data->learner1, $data->course1);
        $this->give_feedback($data->learner1, $data->course1, $data->feedback1, ['5', 'Cake!']);
        $this->give_feedback($data->learner1, $data->course1, $data->feedback2, ['4', 'Parsnip']);

        // User 2, course 1, respond to feedback 1.
        $this->enrol_user($data->learner2, $data->course1);
        $this->give_feedback($data->learner2, $data->course1, $data->feedback1, ['2', 'Everything']);
        $this->give_feedback($data->learner2, $data->course1, $data->feedback2, ['4', 'Nothing']);

        // We should have 4 saved record from each of the two users.
        $this->assertCount(8, $DB->get_records('feedback_valuetmp'));

        // Execute the export using feedback module 1 context.
        $result = temporary::execute_export($data->targetuser1, context_module::instance($data->feedback1->cmid));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(0, $result->files);
        $this->assertCount(2, $result->data);

        $this->validate_export($result->data, $data->learner1);

        // Execute the export using feedback module 3 context.
        $result = temporary::execute_export($data->targetuser1, context_module::instance($data->feedback3->cmid));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(0, $result->files);
        $this->assertCount(0, $result->data);
    }

    /**
     * Test function is_countable.
     */
    public function test_is_countable() {
        $this->assertTrue(temporary::is_countable());
    }

    /**
     * Test the count function in system context.
     */
    public function test_count_system_context() {
        global $DB;

        // Setup the course and data, which will create,
        // * 1 course.
        // * 2 feedback activity with 2 questions.
        // * 2 enrolled learners.
        // * Both learners to have completed saved responses to the 4 feedback questions
        $data = $this->setup_data();
        $data->course1 = $this->create_course($data);
        $data->feedback1 = $this->create_feedback($data->course1,  ['What is 1+1', 'What is your favorite food']);
        $data->feedback2 = $this->create_feedback($data->course1,  ['What is 2+2', 'What is your worst food']);

        // User 1.
        $this->enrol_user($data->learner1, $data->course1);
        $this->give_feedback($data->learner1, $data->course1, $data->feedback1, ['5', 'Cake!']);
        $this->give_feedback($data->learner1, $data->course1, $data->feedback2, ['7', 'Cabbage']);

        // User 2.
        $this->enrol_user($data->learner2, $data->course1);
        $this->give_feedback($data->learner2, $data->course1, $data->feedback1, ['2', 'Hot Dogs']);
        $this->give_feedback($data->learner2, $data->course1, $data->feedback2, ['4', 'Parsnip']);

        // We should have 4 saved record from each of the two users.
        $this->assertCount(8, $DB->get_records('feedback_valuetmp'));

        // Test the count for user 1.
        $this->assertEquals(4, temporary::execute_count($data->targetuser1, context_system::instance()));

        // Test the count for user 2.
        $this->assertEquals(4, temporary::execute_count($data->targetuser2, context_system::instance()));

        // Test the count for user 3.
        $this->assertEquals(4, temporary::execute_count($data->targetuser2, context_system::instance()));
    }

    /**
     * Test the count function in category context.
     */
    public function test_count_coursecat_context() {
        global $DB;

        // Setup the course and data, which will create,
        // * 2 course categories.
        // * 2 courses, one in category 1 and the other not in any category.
        // * 1 feedback activity with 2 questions in each course.
        // * 1 enrolled learner in each course.
        // * The learner has completed saved responses to the 2 feedback questions
        $data = $this->setup_data();

        // Create 2 categories.
        $data->cat1 = $this->create_course_catogory();
        $data->cat2 = $this->create_course_catogory();

        // Create course 1 in category 1 and add feedback activity.
        $data->course1 = $this->create_course($data, $data->cat1->id);
        $data->feedback1 = $this->create_feedback($data->course1,  ['What is 1+1', 'What is your favorite food']);

        // Create course 2 not in a category and add feedback activity.
        $data->course2 = $this->create_course($data);
        $data->feedback2 = $this->create_feedback($data->course2,  ['What is 2+2', 'What is your worst food']);

        // User 1, course 1, give feedback.
        $this->enrol_user($data->learner1, $data->course1);
        $this->give_feedback($data->learner1, $data->course1, $data->feedback1, ['5', 'Cake!']);

        // User 1, course 2, give feedback.
        $this->enrol_user($data->learner1, $data->course1);
        $this->give_feedback($data->learner1, $data->course2, $data->feedback2, ['4', 'Parsnip']);

        // We should have 2 saved record from each of the two courses.
        $this->assertCount(4, $DB->get_records('feedback_valuetmp'));

        // Test the count for user 1 in category 1 context.
        $this->assertEquals(2, temporary::execute_count($data->targetuser1, context_coursecat::instance($data->cat1->id)));

        // Test the count for user 1 in category 2 context.
        $this->assertEquals(0, temporary::execute_count($data->targetuser1, context_coursecat::instance($data->cat2->id)));
    }

    /**
     * Test the count function in course context.
     */
    public function test_count_course_context() {
        global $DB;

        // Setup the course and data, which will create,
        // * 3 courses all with feedback activity.
        // * User 1 enrolled into course 1 and 2.
        $data = $this->setup_data();

        // Create course 1 and add feedback activity.
        $data->course1 = $this->create_course($data);
        $data->feedback1 = $this->create_feedback($data->course1,  ['What is 1+1', 'What is your favorite food']);

        // Create course 2 and add feedback activity.
        $data->course2 = $this->create_course($data);
        $data->feedback2 = $this->create_feedback($data->course2,  ['What is 2+2', 'What is your worst food']);

        // Create course 3 and add feedback activity.
        $data->course3 = $this->create_course($data);
        $data->feedback3 = $this->create_feedback($data->course3,  ['What is 3+3', 'What is your favorite band']);

        // User 1, course 1, give feedback.
        $this->enrol_user($data->learner1, $data->course1);
        $this->give_feedback($data->learner1, $data->course1, $data->feedback1, ['5', 'Cake!']);

        // User 1, course 2, give feedback.
        $this->enrol_user($data->learner1, $data->course2);
        $this->give_feedback($data->learner1, $data->course2, $data->feedback2, ['4', 'Parsnip']);

        // We should have 2 saved record from each of the two courses.
        $this->assertCount(4, $DB->get_records('feedback_valuetmp'));

        // Check the count for user 1 in course 1 context.
        $this->assertEquals(2, temporary::execute_count($data->targetuser1, context_course::instance($data->course1->id)));
        // Check the count for user 1 in course 2 context.
        $this->assertEquals(2, temporary::execute_count($data->targetuser1, context_course::instance($data->course2->id)));
        // Check the count for user 1 in course 3 context
        $this->assertEquals(0, temporary::execute_count($data->targetuser1, context_course::instance($data->course3->id)));
    }

    /**
     * Test the count function in module context.
     */
    public function test_count_module_context() {
        global $DB;

        // Setup the course and data, which will create,
        // * 1 course.
        // * 3 feedback activity with 2 questions.
        // * 2 enrolled learners.
        // * Both learners to have completed saved responses to the 2 feedback questions
        $data = $this->setup_data();

        // Create course 1 and add 2 feedback activities.
        $data->course1 = $this->create_course($data);
        $data->feedback1 = $this->create_feedback($data->course1,  ['What is 1+1', 'What is your favorite food']);
        $data->feedback2 = $this->create_feedback($data->course1,  ['What is 2+2', 'What is your worst food']);
        $data->feedback3 = $this->create_feedback($data->course1,  ['Some other question', 'And another...']);

        // User 1, course 1, respond to feedback 1 and 2.
        $this->enrol_user($data->learner1, $data->course1);
        $this->give_feedback($data->learner1, $data->course1, $data->feedback1, ['5', 'Cake!']);
        $this->give_feedback($data->learner1, $data->course1, $data->feedback2, ['4', 'Parsnip']);

        // User 2, course 1, respond to feedback 1.
        $this->enrol_user($data->learner2, $data->course1);
        $this->give_feedback($data->learner2, $data->course1, $data->feedback1, ['2', 'Everything']);
        $this->give_feedback($data->learner2, $data->course1, $data->feedback2, ['4', 'Nothing']);

        // We should have 4 saved record from each of the two users.
        $this->assertCount(8, $DB->get_records('feedback_valuetmp'));

        // Check the count for user 1 in feedback module 1 context.
        $this->assertEquals(2, temporary::execute_count($data->targetuser1, context_module::instance($data->feedback1->cmid)));
        // Check the count for user 1 in feedback module 2 context.
        $this->assertEquals(2, temporary::execute_count($data->targetuser1, context_module::instance($data->feedback2->cmid)));
        // Check the count for user 1 in feedback module 3 context.
        $this->assertEquals(0, temporary::execute_count($data->targetuser1, context_module::instance($data->feedback3->cmid)));
    }

}
