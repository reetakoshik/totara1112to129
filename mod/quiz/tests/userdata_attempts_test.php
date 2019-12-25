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

use mod_quiz\event\attempt_deleted;
use mod_quiz\userdata\attempts;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Test purging, exporting and counting of quiz attempts
 * @group totara_userdata
 * @group mod_quiz
 */
class mod_quiz_userdata_attempts_testcase extends advanced_testcase {

    /**
     * test compatible context levels
     */
    public function test_compatible_context_levels() {
        $contextlevels = attempts::get_compatible_context_levels();
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
        $this->assertTrue(attempts::is_countable());
        $this->assertTrue(attempts::is_exportable());
        $this->assertTrue(attempts::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(attempts::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(attempts::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge_system() {
        global $DB;

        $this->resetAfterTest(true);

        $this->setAdminUser();

        $activeuser = new target_user($this->getDataGenerator()->create_user());
        $suspendeduser = new target_user($this->getDataGenerator()->create_user(['suspended' => 1]));
        $deleteduser = new target_user($this->getDataGenerator()->create_user(['deleted' => 1]));
        $controluser = new target_user($this->getDataGenerator()->create_user());

        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();

        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $category2->id]);

        $quiz1 = $this->create_quiz($course1);
        $quiz2 = $this->create_quiz($course2);

        // Create attempts for quiz1.
        $attempt1 = $this->create_attempt($quiz1, $activeuser);
        $attempt2 = $this->create_attempt($quiz1, $suspendeduser);
        $attempt3 = $this->create_attempt($quiz1, $deleteduser);
        $this->create_attempt($quiz1, $controluser);
        // Create attempts for quiz2.
        $attempt4 = $this->create_attempt($quiz2, $activeuser);
        $attempt5 = $this->create_attempt($quiz2, $suspendeduser);
        $attempt6 = $this->create_attempt($quiz2, $deleteduser);
        $this->create_attempt($quiz2, $controluser);

        $sink = $this->redirectEvents();

        /****************************
         * PURGE activeuser
         ***************************/

        $result = attempts::execute_purge($activeuser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $events = $sink->get_events();
        $sink->clear();

        // Check if the correct events are there.
        // There were some more grade and completion events fire in which we are not interested here.
        $this->assert_count_events_fired(2, attempt_deleted::class, $events);
        $this->assert_attempt_deleted_event_fired($attempt1, $events);
        $this->assert_attempt_deleted_event_fired($attempt4, $events);

        $this->assertCount(0, $DB->get_records('quiz_attempts', ['userid' => $activeuser->id]));
        $this->assertCount(0, $DB->get_records('quiz_grades', ['userid' => $activeuser->id]));
        $this->assertCount(2, $DB->get_records('quiz_attempts', ['userid' => $suspendeduser->id]));
        $this->assertCount(2, $DB->get_records('quiz_grades', ['userid' => $suspendeduser->id]));
        $this->assertCount(2, $DB->get_records('quiz_attempts', ['userid' => $deleteduser->id]));
        $this->assertCount(2, $DB->get_records('quiz_grades', ['userid' => $deleteduser->id]));

        /****************************
         * PURGE suspendeduser
         ***************************/

        $result = attempts::execute_purge($suspendeduser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $this->assertCount(0, $DB->get_records('quiz_attempts', ['userid' => $activeuser->id]));
        $this->assertCount(0, $DB->get_records('quiz_grades', ['userid' => $activeuser->id]));
        $this->assertCount(0, $DB->get_records('quiz_attempts', ['userid' => $suspendeduser->id]));
        $this->assertCount(0, $DB->get_records('quiz_grades', ['userid' => $suspendeduser->id]));
        $this->assertCount(2, $DB->get_records('quiz_attempts', ['userid' => $deleteduser->id]));
        $this->assertCount(2, $DB->get_records('quiz_grades', ['userid' => $deleteduser->id]));

        $events = $sink->get_events();
        $sink->clear();

        // Check if the correct events are there.
        // There were some more grade and completion events fire in which we are not interested here.
        $this->assert_count_events_fired(2, attempt_deleted::class, $events);
        $this->assert_attempt_deleted_event_fired($attempt2, $events);
        $this->assert_attempt_deleted_event_fired($attempt5, $events);

        /****************************
         * PURGE deleteduser
         ***************************/

        $result = attempts::execute_purge($deleteduser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $this->assertCount(0, $DB->get_records('quiz_attempts', ['userid' => $activeuser->id]));
        $this->assertCount(0, $DB->get_records('quiz_grades', ['userid' => $activeuser->id]));
        $this->assertCount(0, $DB->get_records('quiz_attempts', ['userid' => $suspendeduser->id]));
        $this->assertCount(0, $DB->get_records('quiz_grades', ['userid' => $suspendeduser->id]));
        $this->assertCount(0, $DB->get_records('quiz_attempts', ['userid' => $deleteduser->id]));
        $this->assertCount(0, $DB->get_records('quiz_grades', ['userid' => $deleteduser->id]));

        $events = $sink->get_events();
        $sink->clear();

        // Check if the correct events are there.
        // There were some more grade and completion events fire in which we are not interested here.
        $this->assert_count_events_fired(2, attempt_deleted::class, $events);
        $this->assert_attempt_deleted_event_fired($attempt3, $events);
        $this->assert_attempt_deleted_event_fired($attempt6, $events);

        /****************************
         * CHECK controluser
         ***************************/

        $this->assertCount(2, $DB->get_records('quiz_attempts', ['userid' => $controluser->id]));
        $this->assertCount(2, $DB->get_records('quiz_grades', ['userid' => $controluser->id]));
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge_coursecat() {
        global $DB;

        $this->resetAfterTest(true);

        $this->setAdminUser();

        $activeuser = new target_user($this->getDataGenerator()->create_user());
        $controluser = new target_user($this->getDataGenerator()->create_user());

        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();

        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $category2->id]);

        $quiz1 = $this->create_quiz($course1);
        $quiz2 = $this->create_quiz($course2);

        // Create attempts for quiz1.
        $attempt1 = $this->create_attempt($quiz1, $activeuser);
        $this->create_attempt($quiz1, $controluser);
        // Create attempts for quiz2.
        $this->create_attempt($quiz2, $activeuser);
        $this->create_attempt($quiz2, $controluser);

        $sink = $this->redirectEvents();

        /****************************
         * PURGE activeuser
         ***************************/

        $result = attempts::execute_purge($activeuser, context_coursecat::instance($category1->id));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $events = $sink->get_events();
        $sink->clear();

        // Check if the correct events are there.
        // There were some more grade and completion events fire in which we are not interested here.
        $this->assert_count_events_fired(1, attempt_deleted::class, $events);
        $this->assert_attempt_deleted_event_fired($attempt1, $events);

        $this->assertCount(0, $DB->get_records('quiz_attempts', ['userid' => $activeuser->id, 'quiz' => $quiz1->id]));
        $this->assertCount(1, $DB->get_records('quiz_attempts', ['userid' => $activeuser->id, 'quiz' => $quiz2->id]));
        $this->assertCount(0, $DB->get_records('quiz_grades', ['userid' => $activeuser->id, 'quiz' => $quiz1->id]));
        $this->assertCount(1, $DB->get_records('quiz_grades', ['userid' => $activeuser->id, 'quiz' => $quiz2->id]));

        /****************************
         * CHECK controluser
         ***************************/

        $this->assertCount(2, $DB->get_records('quiz_attempts', ['userid' => $controluser->id]));
        $this->assertCount(2, $DB->get_records('quiz_grades', ['userid' => $controluser->id]));
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge_course() {
        global $DB;

        $this->resetAfterTest(true);

        $this->setAdminUser();

        $activeuser = new target_user($this->getDataGenerator()->create_user());
        $controluser = new target_user($this->getDataGenerator()->create_user());

        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();

        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $category2->id]);

        $quiz1 = $this->create_quiz($course1);
        $quiz2 = $this->create_quiz($course2);

        // Create attempts for quiz1.
        $this->create_attempt($quiz1, $activeuser);
        $this->create_attempt($quiz1, $controluser);
        // Create attempts for quiz2.
        $attempt1 = $this->create_attempt($quiz2, $activeuser);
        $this->create_attempt($quiz2, $controluser);

        $sink = $this->redirectEvents();

        /****************************
         * PURGE activeuser
         ***************************/

        $result = attempts::execute_purge($activeuser, context_course::instance($course2->id));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $events = $sink->get_events();
        $sink->clear();

        // Check if the correct events are there.
        // There were some more grade and completion events fire in which we are not interested here.
        $this->assert_count_events_fired(1, attempt_deleted::class, $events);
        $this->assert_attempt_deleted_event_fired($attempt1, $events);

        $this->assertCount(1, $DB->get_records('quiz_attempts', ['userid' => $activeuser->id, 'quiz' => $quiz1->id]));
        $this->assertCount(0, $DB->get_records('quiz_attempts', ['userid' => $activeuser->id, 'quiz' => $quiz2->id]));
        $this->assertCount(1, $DB->get_records('quiz_grades', ['userid' => $activeuser->id, 'quiz' => $quiz1->id]));
        $this->assertCount(0, $DB->get_records('quiz_grades', ['userid' => $activeuser->id, 'quiz' => $quiz2->id]));

        /****************************
         * CHECK controluser
         ***************************/

        $this->assertCount(2, $DB->get_records('quiz_attempts', ['userid' => $controluser->id]));
        $this->assertCount(2, $DB->get_records('quiz_grades', ['userid' => $controluser->id]));
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge_module() {
        global $DB;

        $this->resetAfterTest(true);

        $this->setAdminUser();

        $activeuser = new target_user($this->getDataGenerator()->create_user());
        $controluser = new target_user($this->getDataGenerator()->create_user());

        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();

        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $category2->id]);

        $quiz1 = $this->create_quiz($course1);
        $quiz2 = $this->create_quiz($course2);
        $quiz3 = $this->create_quiz($course2);

        // Create attempts for quiz1.
        $this->create_attempt($quiz1, $activeuser);
        $this->create_attempt($quiz1, $controluser);
        // Create attempts for quiz2.
        $this->create_attempt($quiz2, $activeuser);
        $this->create_attempt($quiz2, $controluser);
        // Create attempts for quiz3.
        $attempt1 = $this->create_attempt($quiz3, $activeuser);
        $this->create_attempt($quiz3, $controluser);

        $sink = $this->redirectEvents();

        /****************************
         * PURGE activeuser
         ***************************/

        $result = attempts::execute_purge($activeuser, context_module::instance($quiz3->cmid));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $events = $sink->get_events();
        $sink->clear();

        // Check if the correct events are there.
        // There were some more grade and completion events fire in which we are not interested here.
        $this->assert_count_events_fired(1, attempt_deleted::class, $events);
        $this->assert_attempt_deleted_event_fired($attempt1, $events);

        $this->assertCount(1, $DB->get_records('quiz_attempts', ['userid' => $activeuser->id, 'quiz' => $quiz1->id]));
        $this->assertCount(1, $DB->get_records('quiz_attempts', ['userid' => $activeuser->id, 'quiz' => $quiz2->id]));
        $this->assertCount(0, $DB->get_records('quiz_attempts', ['userid' => $activeuser->id, 'quiz' => $quiz3->id]));
        $this->assertCount(1, $DB->get_records('quiz_grades', ['userid' => $activeuser->id, 'quiz' => $quiz1->id]));
        $this->assertCount(1, $DB->get_records('quiz_grades', ['userid' => $activeuser->id, 'quiz' => $quiz2->id]));
        $this->assertCount(0, $DB->get_records('quiz_grades', ['userid' => $activeuser->id, 'quiz' => $quiz3->id]));

        /****************************
         * CHECK controluser
         ***************************/

        $this->assertCount(3, $DB->get_records('quiz_attempts', ['userid' => $controluser->id]));
        $this->assertCount(3, $DB->get_records('quiz_grades', ['userid' => $controluser->id]));
    }

    /**
     * @param int $expectedcount
     * @param string $expectedevent
     * @param array $events
     */
    private function assert_count_events_fired(int $expectedcount, string $expectedevent, array $events): void {
        $eventsfound = $this->filter_events($expectedevent, $events);
        $this->assertCount($expectedcount, $eventsfound);
    }

    /**
     * @param quiz_attempt $expectedattempt
     * @param array $events
     */
    private function assert_attempt_deleted_event_fired(quiz_attempt $expectedattempt, array $events): void {
        // Go through events and filter out attempt_deleted ones.
        // There were some more grade and completion events fire in which we are not interested here.
        $deletedevents =  $this->filter_events(attempt_deleted::class, $events);
        $eventobjectids = [];
        // Make sure the events are only related to this user.
        foreach ($deletedevents as $event) {
            $this->assertEquals($expectedattempt->get_userid(), $event->relateduserid);
            $eventobjectids[] = $event->objectid;
            if ($event->relateduserid == $expectedattempt->get_userid()
                && $event->objectid == $expectedattempt->get_attemptid()) {
                return;
            }
        }
        $this->fail('Expected attempt_deleted event not fired for attempt '.$expectedattempt->get_attemptid());
    }

    /**
     * @param string $expectedevent
     * @param array $events
     * @return array
     */
    private function filter_events(string $expectedevent, array $events): array {
        $eventsfound = [];
        foreach ($events as $event) {
            if (get_class($event) == $expectedevent) {
                $eventsfound[] = $event;
            }
        }
        return $eventsfound;
    }

    /**
     * test if data is correctly counted
     */
    public function test_count() {
        $this->resetAfterTest(true);

        $this->setAdminUser();

        $user = new target_user($this->getDataGenerator()->create_user());
        $controluser = new target_user($this->getDataGenerator()->create_user());

        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();

        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $category2->id]);

        $quiz1 = $this->create_quiz($course1);
        $quiz2 = $this->create_quiz($course2);
        $quiz3 = $this->create_quiz($course2);

        // Activeuser has 1 attempts in quiz1.
        $this->create_attempt($quiz1, $user);
        // Controluser has 2 attempts in quiz1.
        $this->create_attempt($quiz1, $controluser);
        $this->create_attempt($quiz1, $controluser);

        // Activeuser has 3 attempts in quiz2.
        $this->create_attempt($quiz2, $user);
        $this->create_attempt($quiz2, $user);
        $this->create_attempt($quiz2, $user);
        // Controluser has 4 attempts in quiz2.
        $this->create_attempt($quiz2, $controluser);
        $this->create_attempt($quiz2, $controluser);
        $this->create_attempt($quiz2, $controluser);
        $this->create_attempt($quiz2, $controluser);

        // Activeuser has 1 attempts in quiz3.
        $this->create_attempt($quiz3, $user);
        // Controluser has 2 attempts in quiz3.
        $this->create_attempt($quiz3, $controluser);
        $this->create_attempt($quiz3, $controluser);

        /****************************
         * COUNT activeuser
         ***************************/

        // Count all attempts.
        $result = attempts::execute_count($user, context_system::instance());
        $this->assertEquals(5, $result);

        // Count attempts in categories.
        $result = attempts::execute_count($user, context_coursecat::instance($category1->id));
        $this->assertEquals(1, $result);
        $result = attempts::execute_count($user, context_coursecat::instance($category2->id));
        $this->assertEquals(4, $result);

        // Count attempts in course.
        $result = attempts::execute_count($user, context_course::instance($course1->id));
        $this->assertEquals(1, $result);
        $result = attempts::execute_count($user, context_course::instance($course2->id));
        $this->assertEquals(4, $result);

        // Count attempts in modules.
        $result = attempts::execute_count($user, context_module::instance($quiz1->cmid));
        $this->assertEquals(1, $result);
        $result = attempts::execute_count($user, context_module::instance($quiz2->cmid));
        $this->assertEquals(3, $result);
        $result = attempts::execute_count($user, context_module::instance($quiz3->cmid));
        $this->assertEquals(1, $result);

        /****************************
         * COUNT controluser
         ***************************/

        // Count all attempts.
        $result = attempts::execute_count($controluser, context_system::instance());
        $this->assertEquals(8, $result);

        // Count attempts in categories.
        $result = attempts::execute_count($controluser, context_coursecat::instance($category1->id));
        $this->assertEquals(2, $result);
        $result = attempts::execute_count($controluser, context_coursecat::instance($category2->id));
        $this->assertEquals(6, $result);

        // Count attempts in course.
        $result = attempts::execute_count($controluser, context_course::instance($course1->id));
        $this->assertEquals(2, $result);
        $result = attempts::execute_count($controluser, context_course::instance($course2->id));
        $this->assertEquals(6, $result);

        // Count attempts in modules.
        $result = attempts::execute_count($controluser, context_module::instance($quiz1->cmid));
        $this->assertEquals(2, $result);
        $result = attempts::execute_count($controluser, context_module::instance($quiz2->cmid));
        $this->assertEquals(4, $result);
        $result = attempts::execute_count($controluser, context_module::instance($quiz3->cmid));
        $this->assertEquals(2, $result);
    }

    /**
     * test if data is correctly exported
     */
    public function test_export() {
        global $CFG;
        require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
        require_once($CFG->dirroot . '/question/type/essay/tests/helper.php');

        $this->resetAfterTest(true);

        $activeuser = new target_user($this->getDataGenerator()->create_user());
        $controluser = new target_user($this->getDataGenerator()->create_user());

        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();

        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $category2->id]);

        $quiz1 = $this->create_quiz($course1);
        $quiz2 = $this->create_quiz($course2);
        $quiz3 = $this->create_quiz($course2);

        // Create attempts for quiz1.
        $attempt1 = $this->create_attempt($quiz1, $activeuser);
        $attempt2 = $this->create_attempt($quiz1, $activeuser);
        $attempt3 = $this->create_attempt($quiz2, $activeuser);
        $attempt4 = $this->create_attempt($quiz3, $activeuser);
        $attempt5 = $this->create_attempt($quiz3, $activeuser);
        $attempt6 = $this->create_attempt($quiz3, $activeuser);

        $this->set_attempt_comment_mark($attempt1, 'my comment', 1);
        $this->set_attempt_comment_mark($attempt2, 'my second comment', 0);
        $this->set_attempt_comment_mark($attempt3, 'my third comment', 0.5);

        // Controluser.
        $this->create_attempt($quiz1, $controluser);
        $this->create_attempt($quiz2, $controluser);

        $files1 = $this->add_files_for_attempt($attempt1);
        $files2 = $this->add_files_for_attempt($attempt2);

        /****************************
         * EXPORT system context
         ***************************/

        $result = attempts::execute_export($activeuser, context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(6, $result->data);
        $this->assert_export_contains_attempt($attempt1, $result->data);
        $this->assert_export_contains_attempt($attempt2, $result->data);
        $this->assert_export_contains_attempt($attempt3, $result->data);
        $this->assert_export_contains_attempt($attempt4, $result->data);
        $this->assert_export_contains_attempt($attempt5, $result->data);
        $this->assert_export_contains_attempt($attempt6, $result->data);

        // Check that questions have the correct grades and comments.
        $this->assert_attempt_has_key_value(
            $attempt1->get_attemptid(),
            'grade',
            '100.00',
            $result->data
        );
        $this->assert_attempt_has_question_key_value(
            $attempt1->get_attemptid(),
            'summary',
            'Manually graded 1 with comment: my comment',
            $result->data
        );
        $this->assert_attempt_has_key_value(
            $attempt2->get_attemptid(),
            'grade',
            'Not yet graded',
            $result->data
        );
        $this->assert_attempt_has_question_key_value(
            $attempt2->get_attemptid(),
            'summary',
            'Commented: my second comment',
            $result->data
        );
        $this->assert_attempt_has_key_value(
            $attempt3->get_attemptid(),
            'grade',
            '50.00',
            $result->data
        );
        $this->assert_attempt_has_question_key_value(
            $attempt3->get_attemptid(),
            'summary',
            'Manually graded 0.5 with comment: my third comment',
            $result->data
        );

        /** @var stored_file[] $expectedfiles */
        $expectedfiles = array_merge($files1, $files2);
        $this->assertEquals(count($expectedfiles), count($result->files));
        foreach ($expectedfiles as $expectedfile) {
            $this->assertArrayHasKey($expectedfile->get_id(), $result->files);
        }

        // Check that data contains all files for this attempt.
        $this->assert_has_files($attempt1, $files1, $result);
        $this->assert_has_files($attempt2, $files2, $result);

        /****************************
         * EXPORT course category context
         ***************************/

        $result = attempts::execute_export($activeuser, context_coursecat::instance($category1->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(2, $result->data);
        $this->assert_export_contains_attempt($attempt1, $result->data);
        $this->assert_export_contains_attempt($attempt2, $result->data);

        $result = attempts::execute_export($activeuser, context_coursecat::instance($category2->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(4, $result->data);
        $this->assert_export_contains_attempt($attempt3, $result->data);
        $this->assert_export_contains_attempt($attempt4, $result->data);
        $this->assert_export_contains_attempt($attempt5, $result->data);
        $this->assert_export_contains_attempt($attempt6, $result->data);

        /****************************
         * EXPORT course context
         ***************************/

        $result = attempts::execute_export($activeuser, context_course::instance($course1->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(2, $result->data);
        $this->assert_export_contains_attempt($attempt1, $result->data);
        $this->assert_export_contains_attempt($attempt2, $result->data);

        $result = attempts::execute_export($activeuser, context_course::instance($course2->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(4, $result->data);
        $this->assert_export_contains_attempt($attempt3, $result->data);
        $this->assert_export_contains_attempt($attempt4, $result->data);
        $this->assert_export_contains_attempt($attempt5, $result->data);
        $this->assert_export_contains_attempt($attempt6, $result->data);

        /****************************
         * EXPORT module context
         ***************************/

        $result = attempts::execute_export($activeuser, context_module::instance($quiz1->cmid));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(2, $result->data);
        $this->assert_export_contains_attempt($attempt1, $result->data);
        $this->assert_export_contains_attempt($attempt2, $result->data);

        $result = attempts::execute_export($activeuser, context_module::instance($quiz2->cmid));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(1, $result->data);
        $this->assert_export_contains_attempt($attempt3, $result->data);

        $result = attempts::execute_export($activeuser, context_module::instance($quiz3->cmid));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(3, $result->data);
        $this->assert_export_contains_attempt($attempt4, $result->data);
        $this->assert_export_contains_attempt($attempt5, $result->data);
        $this->assert_export_contains_attempt($attempt6, $result->data);
    }

    /**
     * @param stdClass $quiz1
     * @param stdClass $user
     * @return quiz_attempt
     */
    private function create_attempt(stdClass $quiz1, stdClass $user): quiz_attempt {
        global $DB;

        $timenow = time();

        $quizobj = quiz::create($quiz1->id, $user->id);
        $quba = question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);

        $lastattempt = $DB->get_field('quiz_attempts', 'MAX(attempt)', ['userid' => $user->id]);

        $attempt = quiz_create_attempt($quizobj, $lastattempt + 1, null, $timenow, false, $user->id);
        $attempt = quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow);
        $attempt = quiz_attempt_save_started($quizobj, $quba, $attempt);

        // Process a response from the student.
        $attemptobj = quiz_attempt::create($attempt->id);
        $tosubmit = [1 => ['answer' => 'frog']];
        $attemptobj->process_submitted_actions($timenow, false, $tosubmit);
        $this->assertTrue($attemptobj->has_response_to_at_least_one_graded_question());
        $attemptobj->process_finish($timenow, false);

        // Reload attempt from db to get most current data.
        return quiz_attempt::create($attempt->id);
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

    /**
     * Update the comment and (optionally) the mark of an attempt
     *
     * @param quiz_attempt $attempt
     * @param string $comment
     * @param float|int $mark
     */
    private function set_attempt_comment_mark(quiz_attempt $attempt, string $comment, float $mark = null) {
        $responses = [];
        foreach ($attempt->get_slots() as $slot) {
            $responses[$slot] = ['-comment' => $comment];
            if ($mark !== null) {
                $responses[$slot]['-mark'] = round($mark, 2);
                $responses[$slot]['-maxmark'] = 1;
                $responses[$slot]['-minfraction'] = 0;
                $responses[$slot]['-maxfraction'] = 1;
            }
        }
        $attempt->process_submitted_actions(time(), false, $responses);
    }

    /**
     * @param quiz_attempt $expectedattempt
     * @param array $attempts
     */
    private function assert_export_contains_attempt(quiz_attempt $expectedattempt, array $attempts) {
        foreach ($attempts as $attempt) {
            if ($attempt['id'] == $expectedattempt->get_attemptid()) {
                $this->assertArrayHasKey('state', $attempt);
                $this->assertNotEmpty($attempt['state']);
                $this->assertArrayHasKey('grade', $attempt);
                $this->assertNotEmpty($attempt['grade']);
                $this->assertArrayHasKey('summark', $attempt);
                // Summark can be empty if not yet graded.
                $this->assertArrayHasKey('timestart', $attempt);
                $this->assertNotEmpty($attempt['timestart']);
                $this->assertArrayHasKey('timefinish', $attempt);
                $this->assertNotEmpty($attempt['timefinish']);
                $this->assertArrayHasKey('feedback', $attempt);
                $this->assertArrayHasKey('questions', $attempt);
                $this->assertNotEmpty($attempt['questions']);
                $this->assertIsArray($attempt['questions']);
                foreach ($attempt['questions'] as $question) {
                    $this->assertArrayHasKey('question', $question);
                    $this->assertNotEmpty($question['question']);
                    $this->assertArrayHasKey('mark', $question);
                    // Mark can be empty if not yet graded.
                    $this->assertArrayHasKey('response', $question);
                    $this->assertNotEmpty($question['response']);
                    $this->assertArrayHasKey('state', $question);
                    $this->assertNotEmpty($question['state']);
                    $this->assertArrayHasKey('summary', $question);
                    $this->assertNotEmpty($question['summary']);
                    $this->assertArrayHasKey('rightanswer', $question);
                    $this->assertNotEmpty($question['rightanswer']);
                }
                return;
            }
        }
        $this->fail(sprintf('Export does not contain expected attempt with id %d.', $expectedattempt->get_attemptid()));
    }

    /**
     * @param int $quizattemptid
     * @param string $key
     * @param string $value
     * @param array $attempts
     */
    private function assert_attempt_has_key_value(int $quizattemptid, string $key, string $value, array $attempts) {
        foreach ($attempts as $attempt) {
            if ($attempt['id'] == $quizattemptid) {
                $this->assertArrayHasKey($key, $attempt);
                $this->assertSame($value, $attempt[$key]);
                return;
            }
        }
        $this->fail('Export does not contain expected key value combination.');
    }

    /**
     * @param int $quizattemptid
     * @param string $key
     * @param string|array $value
     * @param array $attempts
     */
    private function assert_attempt_has_question_key_value(int $quizattemptid, string $key, $value, array $attempts) {
        foreach ($attempts as $attempt) {
            if ($attempt['id'] == $quizattemptid) {
                $this->assertArrayHasKey('questions', $attempt);
                $this->assertNotEmpty($attempt['questions']);
                $this->assertIsArray($attempt['questions']);
                foreach ($attempt['questions'] as $question) {
                    $this->assertArrayHasKey($key, $question);
                    if (is_array($value) && is_array($question[$key])) {
                        $this->assertContains($value, $question[$key]);
                    } else {
                        $this->assertEquals($value, $question[$key]);
                    }
                }
                return;
            }
        }
        $this->fail('Export does not contain expected key value combination.');
    }

    /**
     * @param quiz_attempt $attempt
     * @return stored_file[]
     */
    private function add_files_for_attempt(quiz_attempt $attempt): array {
        global $DB, $USER;
        $contextmodule = context_module::instance($attempt->get_cmid());
        // Files are stored as draft in the user context first.
        $this->setUser($attempt->get_userid());
        $qa = $attempt->get_question_attempt(1);
        $draftfiles = [];
        foreach ($qa->get_steps_with_submitted_response_iterator() as $step) {
            foreach (question_engine::get_all_response_file_areas() as $filearea) {
                $saver = (new qtype_essay_test_helper())->make_attachments_saver(2, $filearea);
                $draftfiles += $saver->get_files();
                $saver->save_files($step->get_id(), $contextmodule);
            }
        }
        // Set user back to previous user.
        $this->setUser($USER);

        $fs = get_file_storage();
        $files = [];
        $filerecords = $DB->get_records_select(
            'files',
            "component = 'question' AND itemid = :stepid AND filename <> '.'",
            ['stepid' => $step->get_id()]
        );
        foreach ($filerecords as $filerecord) {
            $files[] = $fs->get_file_instance($filerecord);
        }
        return $files;
    }

    /**
     * @param quiz_attempt $attempt
     * @param array $files
     * @param export $export
     */
    private function assert_has_files(quiz_attempt $attempt, array $files, export $export) {
        // Check that data contains all files for this attempt.
        foreach ($files as $file) {
            $this->assert_attempt_has_question_key_value(
                $attempt->get_attemptid(),
                'files',
                [
                    'fileid' => $file->get_id(),
                    'contenthash' => $file->get_contenthash(),
                    'filename' => $file->get_filename()
                ],
                $export->data
            );
        }
    }

}