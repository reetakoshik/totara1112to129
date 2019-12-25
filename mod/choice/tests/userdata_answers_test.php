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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package mod_choice
 */

defined('MOODLE_INTERNAL') || die();

use totara_userdata\userdata\target_user;
use mod_choice\userdata\answers;

/**
 * Class mod_choice_userstatus_archived_answers_testcase
 *
 * Tests export, count and purge of choice activity answers.
 *
 * @group totara_userdata
 */
class mod_choice_userdata_answers_testcase extends advanced_testcase {

    /**
     * Runs the purge, export and count methods when no choice data exists in the system.
     */
    public function test_user_with_no_answers() {
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $choice = $this->getDataGenerator()->create_module('choice', ['course' => $course->id]);

        $user = $this->getDataGenerator()->create_user();

        answers::execute_purge(new target_user($user), context_system::instance());

        $export = answers::execute_export(new target_user($user), context_system::instance());

        $this->assertEmpty($export->data);
        $this->assertEmpty($export->files);

        $count = answers::execute_count(new target_user($user), context_system::instance());

        $this->assertEquals(0, $count);
    }

    /**
     * Helper method to set up the choice activity for testing.
     *
     * @param stdClass $course for adding the choice to.
     * @return stdClass The choice activity record.
     */
    private function set_up_choice_with_options($course) {
        $choice = $this->getDataGenerator()->create_module('choice', ['course' => $course->id]);

        $choicemodule = get_coursemodule_from_id('choice', $choice->cmid);
        $options = ['yes', 'no'];
        $choicemodule->option = $options;
        choice_update_instance($choicemodule);

        return $choice;
    }

    /**
     * Helper method so that it is more convenient to make a user provide an answer to a choice.
     *
     * @param stdClass $user
     * @param stdClass $choice
     * @param stdClass $course
     */
    private function user_answers_choice($user, $choice, $course) {
        global $DB;

        $choiceoptions = $DB->get_records_menu('choice_options', ['choiceid' => $choice->id], '', 'text, id');

        choice_user_submit_response(
            $choiceoptions['yes'],
            $choice,
            $user->id,
            $course,
            get_fast_modinfo($course, $user->id)->get_cm($choice->cmid)
        );
    }

    /**
     * Creates data for testing the item against, including users, choices and the users' answers to choices.
     *
     * @return array containing the users, categories, courses and choices generated.
     */
    private function create_answer_data() {
        $data = [];

        $data['user1'] = $this->getDataGenerator()->create_user();
        $data['user2'] = $this->getDataGenerator()->create_user();

        $data['category1'] = $this->getDataGenerator()->create_category();
        $data['category2'] = $this->getDataGenerator()->create_category();

        $data['course1'] = $this->getDataGenerator()->create_course(['category' => $data['category1']->id]);
        $data['course2'] = $this->getDataGenerator()->create_course(['category' => $data['category2']->id]);
        $data['course3'] = $this->getDataGenerator()->create_course(['category' => $data['category2']->id]);

        $data['choice1'] = $this->set_up_choice_with_options($data['course1']);
        $data['choice2'] = $this->set_up_choice_with_options($data['course2']);
        $data['choice3'] = $this->set_up_choice_with_options($data['course3']);
        $data['choice4'] = $this->set_up_choice_with_options($data['course3']);

        $this->user_answers_choice($data['user1'], $data['choice1'], $data['course1']);
        $this->user_answers_choice($data['user2'], $data['choice1'], $data['course1']);

        $this->user_answers_choice($data['user1'], $data['choice2'], $data['course2']);
        $this->user_answers_choice($data['user2'], $data['choice2'], $data['course2']);

        $this->user_answers_choice($data['user1'], $data['choice3'], $data['course3']);
        $this->user_answers_choice($data['user2'], $data['choice3'], $data['course3']);

        $this->user_answers_choice($data['user1'], $data['choice4'], $data['course3']);
        $this->user_answers_choice($data['user2'], $data['choice4'], $data['course3']);

        $this->assertCount(2, choice_get_all_responses($data['choice1']));
        $this->assertCount(2, choice_get_all_responses($data['choice1']));
        $this->assertCount(2, choice_get_all_responses($data['choice1']));
        $this->assertCount(2, choice_get_all_responses($data['choice1']));

        return $data;
    }

    public function test_export_in_system_context() {
        $data = $this->create_answer_data();

        $export = answers::execute_export(new target_user($data['user1']), context_system::instance());

        $this->assertEmpty($export->files);
        $this->assertCount(4, $export->data);

        foreach ($export->data as $answer) {
            $this->assertEquals($data['user1']->id, $answer->userid);
        }
    }

    public function test_count_in_system_context() {
        $data = $this->create_answer_data();

        $count = answers::execute_count(new target_user($data['user1']), context_system::instance());

        $this->assertEquals(4, $count);

        return $data;
    }

    public function test_purge_in_system_context() {
        $data = $this->create_answer_data();

        $eventsink = $this->redirectEvents();

        answers::execute_purge(new target_user($data['user1']), context_system::instance());

        $events = $eventsink->get_events();
        $this->assertInstanceOf(\mod_choice\event\answer_deleted::class, reset($events));
        $eventsink->close();

        $responses1 = choice_get_all_responses($data['choice1']);
        $this->assertCount(1, $responses1);
        $this->assertEquals($data['user2']->id, reset($responses1)->userid);

        $responses2 = choice_get_all_responses($data['choice2']);
        $this->assertCount(1, $responses2);
        $this->assertEquals($data['user2']->id, reset($responses2)->userid);

        $responses3 = choice_get_all_responses($data['choice3']);
        $this->assertCount(1, $responses3);
        $this->assertEquals($data['user2']->id, reset($responses3)->userid);

        $responses4 = choice_get_all_responses($data['choice4']);
        $this->assertCount(1, $responses4);
        $this->assertEquals($data['user2']->id, reset($responses4)->userid);

        $count = answers::execute_count(new target_user($data['user1']), context_system::instance());
        $this->assertEquals(0, $count);
    }

    public function test_export_in_category_context() {
        $data = $this->create_answer_data();

        $export = answers::execute_export(new target_user($data['user1']), context_coursecat::instance($data['category2']->id));

        $this->assertEmpty($export->files);
        $this->assertCount(3, $export->data);

        foreach ($export->data as $answer) {
            $this->assertEquals($data['user1']->id, $answer->userid);

            // Choice 1 is is course 1 which is in category 1. So should not be included when
            // exporting within category 2 only.
            $this->assertNotEquals($data['choice1']->id, $answer->choiceid);
        }
    }

    public function test_count_in_category_context() {
        $data = $this->create_answer_data();

        $count = answers::execute_count(new target_user($data['user1']), context_coursecat::instance($data['category2']->id));

        $this->assertEquals(3, $count);
    }

    public function test_purge_in_category_context() {
        $data = $this->create_answer_data();

        $eventsink = $this->redirectEvents();

        answers::execute_purge(new target_user($data['user1']), context_coursecat::instance($data['category2']->id));

        $events = $eventsink->get_events();
        $this->assertInstanceOf(\mod_choice\event\answer_deleted::class, reset($events));
        $eventsink->close();

        $responses1 = choice_get_all_responses($data['choice1']);
        $this->assertCount(2, $responses1);

        $responses2 = choice_get_all_responses($data['choice2']);
        $this->assertCount(1, $responses2);
        $this->assertEquals($data['user2']->id, reset($responses2)->userid);

        $responses3 = choice_get_all_responses($data['choice3']);
        $this->assertCount(1, $responses3);
        $this->assertEquals($data['user2']->id, reset($responses3)->userid);

        $responses4 = choice_get_all_responses($data['choice4']);
        $this->assertCount(1, $responses4);
        $this->assertEquals($data['user2']->id, reset($responses4)->userid);

        $count = answers::execute_count(new target_user($data['user1']), context_coursecat::instance($data['category2']->id));
        $this->assertEquals(0, $count);
    }

    public function test_export_in_course_context() {
        $data = $this->create_answer_data();

        $export = answers::execute_export(new target_user($data['user1']), context_course::instance($data['course3']->id));

        $this->assertEmpty($export->files);
        $this->assertCount(2, $export->data);

        foreach ($export->data as $answer) {
            $this->assertEquals($data['user1']->id, $answer->userid);

            // Only choice 3 and choice 4 are in course 3. Others should not be there.
            $this->assertNotEquals($data['choice1']->id, $answer->choiceid);
            $this->assertNotEquals($data['choice2']->id, $answer->choiceid);
        }
    }

    public function test_count_in_course_context() {
        $data = $this->create_answer_data();

        $count = answers::execute_count(new target_user($data['user1']), context_course::instance($data['course3']->id));

        $this->assertEquals(2, $count);

        return $data;
    }

    public function test_purge_in_course_context() {
        $data = $this->create_answer_data();

        $eventsink = $this->redirectEvents();

        answers::execute_purge(new target_user($data['user1']), context_course::instance($data['course3']->id));

        $events = $eventsink->get_events();
        $this->assertInstanceOf(\mod_choice\event\answer_deleted::class, reset($events));
        $eventsink->close();

        $responses1 = choice_get_all_responses($data['choice1']);
        $this->assertCount(2, $responses1);

        $responses2 = choice_get_all_responses($data['choice2']);
        $this->assertCount(2, $responses2);

        $responses3 = choice_get_all_responses($data['choice3']);
        $this->assertCount(1, $responses3);
        $this->assertEquals($data['user2']->id, reset($responses3)->userid);

        $responses4 = choice_get_all_responses($data['choice4']);
        $this->assertCount(1, $responses4);
        $this->assertEquals($data['user2']->id, reset($responses4)->userid);

        $count = answers::execute_count(new target_user($data['user1']), context_course::instance($data['course3']->id));
        $this->assertEquals(0, $count);
    }

    public function test_export_in_module_context() {
        $data = $this->create_answer_data();

        $export = answers::execute_export(new target_user($data['user1']), context_module::instance($data['choice4']->cmid));

        $this->assertEmpty($export->files);
        $this->assertCount(1, $export->data);
        $this->assertEquals($data['choice4']->id, reset($export->data)->choiceid);
    }

    public function test_count_in_module_context() {
        $data = $this->create_answer_data();

        $count = answers::execute_count(new target_user($data['user1']), context_module::instance($data['choice4']->cmid));

        $this->assertEquals(1, $count);
    }

    public function test_purge_in_module_context() {
        $data = $this->create_answer_data();

        $eventsink = $this->redirectEvents();

        answers::execute_purge(new target_user($data['user1']), context_module::instance($data['choice4']->cmid));

        $events = $eventsink->get_events();
        $this->assertInstanceOf(\mod_choice\event\answer_deleted::class, reset($events));
        $eventsink->close();

        $responses1 = choice_get_all_responses($data['choice1']);
        $this->assertCount(2, $responses1);

        $responses2 = choice_get_all_responses($data['choice2']);
        $this->assertCount(2, $responses2);

        $responses3 = choice_get_all_responses($data['choice3']);
        $this->assertCount(2, $responses3);

        $responses4 = choice_get_all_responses($data['choice4']);
        $this->assertCount(1, $responses4);
        $this->assertEquals($data['user2']->id, reset($responses4)->userid);

        $count = answers::execute_count(new target_user($data['user1']), context_module::instance($data['choice4']->cmid));
        $this->assertEquals(0, $count);
    }

    /**
     * Tests purge against a deleted user to ensure that the deleted status does not introduce any
     * unexpected outcomes.
     */
    public function test_purge_with_deleted_user() {
        global $DB;

        $data = $this->create_answer_data();

        delete_user($data['user1']);
        $data['user1'] = $DB->get_record('user', ['id' => $data['user1']->id]);

        answers::execute_purge(new target_user($data['user1']), context_system::instance());

        $responses1 = choice_get_all_responses($data['choice1']);
        $this->assertCount(1, $responses1);
        $this->assertEquals($data['user2']->id, reset($responses1)->userid);

        $responses2 = choice_get_all_responses($data['choice2']);
        $this->assertCount(1, $responses2);
        $this->assertEquals($data['user2']->id, reset($responses2)->userid);

        $responses3 = choice_get_all_responses($data['choice3']);
        $this->assertCount(1, $responses3);
        $this->assertEquals($data['user2']->id, reset($responses3)->userid);

        $responses4 = choice_get_all_responses($data['choice4']);
        $this->assertCount(1, $responses4);
        $this->assertEquals($data['user2']->id, reset($responses4)->userid);

        $count = answers::execute_count(new target_user($data['user1']), context_system::instance());
        $this->assertEquals(0, $count);
    }
}