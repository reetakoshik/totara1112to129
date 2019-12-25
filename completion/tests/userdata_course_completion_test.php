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
 * @package core_completion
 */

/**
 * Class core_completion_userdata_course_completion_testcase
 *
 * Tests purging, exporting and counting in the core_completion\userdata\course_completion item.
 *
 * @group totara_userdata
 */
class core_completion_userdata_course_completion_testcase extends advanced_testcase {

    /**
     * Ensure no errors are generated when running the export, count and purge against a user with no completion data.
     *
     * @throws coding_exception
     * @throws dml_exception
     */
    public function test_with_no_data() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();

        $export = \core_completion\userdata\course_completion::execute_export(
                new \totara_userdata\userdata\target_user($user),
                context_system::instance()
        );
        $this->assertEmpty($export->files);
        $this->assertEmpty($export->data['course_completion_crit_compl']);
        $this->assertEmpty($export->data['course_modules_completion']);
        $this->assertEmpty($export->data['course_completion_history']);
        $this->assertEmpty($export->data['course_completion_log']);
        $this->assertEmpty($export->data['block_totara_stats']);

        $count = \core_completion\userdata\course_completion::execute_count(
                new \totara_userdata\userdata\target_user($user),
                context_system::instance()
        );
        $this->assertEquals(0, $count);

        \core_completion\userdata\course_completion::execute_purge(
                new \totara_userdata\userdata\target_user($user),
                context_system::instance()
        );

        // Do it again inside a context. The first category will the Miscellaneous category which on a site by default.
        $miscellaneous_context = context_coursecat::instance($DB->get_field('course_categories', 'id', []));

        $export = \core_completion\userdata\course_completion::execute_export(
                new \totara_userdata\userdata\target_user($user),
                $miscellaneous_context
        );
        $this->assertEmpty($export->files);
        $this->assertEmpty($export->data['course_completion_crit_compl']);
        $this->assertEmpty($export->data['course_modules_completion']);
        $this->assertEmpty($export->data['course_completion_history']);
        $this->assertEmpty($export->data['course_completion_log']);
        $this->assertEmpty($export->data['block_totara_stats']);

        $count = \core_completion\userdata\course_completion::execute_count(
                new \totara_userdata\userdata\target_user($user),
                $miscellaneous_context
        );
        $this->assertEquals(0, $count);

        \core_completion\userdata\course_completion::execute_purge(
                new \totara_userdata\userdata\target_user($user),
                $miscellaneous_context
        );
    }

    /**
     * Creates an individual course along with an activity. Creates completions for the given user.
     *
     * @param $category
     * @param array $users
     * @return stdClass
     */
    private function create_course_and_completions($category, array $users) {
        /* @var mod_choice_generator $choicegenerator */
        $choicegenerator = $this->getDataGenerator()->get_plugin_generator('mod_choice');

        /* @var core_completion_generator $completiongenerator */
        $completiongenerator = $this->getDataGenerator()->get_plugin_generator('core_completion');

        $course = $this->getDataGenerator()->create_course(['category' => $category->id]);
        $completiongenerator->enable_completion_tracking($course);
        $activity = $choicegenerator->create_instance(['course' => $course->id, 'completion' => COMPLETION_TRACKING_MANUAL]);
        $completiongenerator->set_activity_completion($course->id, [$activity]);

        $completioninfo = new completion_info($course);

        $cm = get_coursemodule_from_instance('choice', $activity->id);

        foreach ($users as $user) {
            $this->getDataGenerator()->enrol_user($user->id, $course->id);
            $completioninfo->update_state($cm, COMPLETION_COMPLETE, $user->id);

            $history = new stdClass();
            $history->courseid = $course->id;
            $history->userid = $user->id;
            \core_completion\helper::write_course_completion_history($history);
        }

        return $course;
    }

    /**
     * Creates users, categories and courses with activities.
     *
     * Creates completion, including course completion history, so that we can test for data in
     * all tables affected by this.
     *
     * @return array
     */
    private function create_completion_data() {

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();

        $course1 = $this->create_course_and_completions($category1, [$user1, $user2]);
        $course2 = $this->create_course_and_completions($category2, [$user1, $user2]);
        $course3 = $this->create_course_and_completions($category2, [$user1, $user2]);

        return [
            'user1' => $user1,
            'user2' => $user2,
            'category1' => $category1,
            'category2' => $category2,
            'course1' => $course1,
            'course2' => $course2,
            'course3' => $course3
        ];
    }

    /**
     * Takes an array of objects and checks how many contain a matching value in the userid property.
     *
     * Optionally a course id can be checked. This will look for the course or courseid property in the object.
     *
     * @param $expected
     * @param $array
     * @param $userid
     * @param int $courseid
     */
    private function assert_count_with_userid($expected, $array, $userid, $courseid = 0) {
        $count = 0;
        foreach($array as $element) {
            if ($element->userid == $userid) {
                if (empty($courseid)) {
                    $count++;
                } else if (isset($element->course) and $element->course == $courseid) {
                    $count++;
                } else if (isset($element->courseid) and $element->courseid == $courseid) {
                    $count++;
                }
            }
        }

        $this->assertEquals($expected, $count);
    }

    public function test_export_system_context() {
        $testdata = $this->create_completion_data();

        $user1 = $testdata['user1'];

        $target_user = new \totara_userdata\userdata\target_user($user1);
        $export = \core_completion\userdata\course_completion::execute_export($target_user, context_system::instance());

        $this->assertEmpty($export->files);
        $this->assertCount(3, $export->data['course_completions']);

        $this->assertCount(3, $export->data['course_completion_crit_compl']);
        $this->assert_count_with_userid(3, $export->data['course_completion_crit_compl'], $user1->id);
        $this->assertCount(3, $export->data['course_modules_completion']);
        $this->assert_count_with_userid(3, $export->data['course_modules_completion'], $user1->id);
        $this->assertCount(3, $export->data['course_completion_history']);
        $this->assert_count_with_userid(3, $export->data['course_completion_history'], $user1->id);
        $this->assertCount(12, $export->data['course_completion_log']);
        $this->assert_count_with_userid(12, $export->data['course_completion_log'], $user1->id);
        $this->assertCount(6, $export->data['block_totara_stats']);
        $this->assert_count_with_userid(6, $export->data['block_totara_stats'], $user1->id);
    }

    public function test_count_system_context() {
        $testdata = $this->create_completion_data();

        $target_user = new \totara_userdata\userdata\target_user($testdata['user1']);
        $count = \core_completion\userdata\course_completion::execute_count($target_user, context_system::instance());

        $this->assertEquals(3, $count);

        return $testdata;
    }

    public function test_purge_system_context() {
        global $DB;

        $testdata = $this->create_completion_data();

        $user1 = $testdata['user1'];
        $user2 = $testdata['user2'];

        $target_user = new \totara_userdata\userdata\target_user($user1);
        \core_completion\userdata\course_completion::execute_purge($target_user, context_system::instance());

        $coursecompletions = $DB->get_records('course_completions');
        $this->assertCount(3, $coursecompletions);
        $this->assert_count_with_userid(0, $coursecompletions, $user1->id);
        $this->assert_count_with_userid(3, $coursecompletions, $user2->id);

        $coursecompletioncritcompl = $DB->get_records('course_completion_crit_compl');
        $this->assertCount(3, $coursecompletioncritcompl);
        $this->assert_count_with_userid(0, $coursecompletioncritcompl, $user1->id);
        $this->assert_count_with_userid(3, $coursecompletioncritcompl, $user2->id);

        $coursemodulescompletion = $DB->get_records('course_modules_completion');
        $this->assertCount(3, $coursemodulescompletion);
        $this->assert_count_with_userid(0, $coursemodulescompletion, $user1->id);
        $this->assert_count_with_userid(3, $coursemodulescompletion, $user2->id);

        $coursecompletionhistory = $DB->get_records('course_completion_history');
        $this->assertCount(3, $coursecompletionhistory);
        $this->assert_count_with_userid(0, $coursecompletionhistory, $user1->id);
        $this->assert_count_with_userid(3, $coursecompletionhistory, $user2->id);

        $coursecompletionlog = $DB->get_records('course_completion_log');
        $this->assertCount(12, $coursecompletionlog);
        $this->assert_count_with_userid(0, $coursecompletionlog, $user1->id);
        $this->assert_count_with_userid(12, $coursecompletionlog, $user2->id);

        $blocktotarastats = $DB->get_records('block_totara_stats');
        $this->assertCount(6, $blocktotarastats);
        $this->assert_count_with_userid(0, $blocktotarastats, $user1->id);
        $this->assert_count_with_userid(6, $blocktotarastats, $user2->id);

        // Running count in this context should now show that those records are gone.
        $count = \core_completion\userdata\course_completion::execute_count($target_user, context_system::instance());
        $this->assertEquals(0, $count);
    }

    public function test_export_coursecat_context() {
        $testdata = $this->create_completion_data();

        $user1 = $testdata['user1'];

        $target_user = new \totara_userdata\userdata\target_user($user1);
        $export = \core_completion\userdata\course_completion::execute_export($target_user, context_coursecat::instance($testdata['category2']->id));

        $this->assertEmpty($export->files);
        $this->assertCount(2, $export->data['course_completion_crit_compl']);
        $this->assert_count_with_userid(2, $export->data['course_completion_crit_compl'], $user1->id);
        $this->assertCount(2, $export->data['course_modules_completion']);
        $this->assert_count_with_userid(2, $export->data['course_modules_completion'], $user1->id);
        $this->assertCount(2, $export->data['course_completion_history']);
        $this->assert_count_with_userid(2, $export->data['course_completion_history'], $user1->id);
        $this->assertCount(8, $export->data['course_completion_log']);
        $this->assert_count_with_userid(8, $export->data['course_completion_log'], $user1->id);
        $this->assertCount(4, $export->data['block_totara_stats']);
        $this->assert_count_with_userid(4, $export->data['block_totara_stats'], $user1->id);
    }

    public function test_count_coursecat_context() {
        $testdata = $this->create_completion_data();

        $target_user = new \totara_userdata\userdata\target_user($testdata['user1']);
        $count = \core_completion\userdata\course_completion::execute_count($target_user, context_coursecat::instance($testdata['category2']->id));

        $this->assertEquals(2, $count);

        return $testdata;
    }

    public function test_purge_coursecat_context() {
        global $DB;

        $testdata = $this->create_completion_data();

        $user1 = $testdata['user1'];
        $user2 = $testdata['user2'];
        $category2 = $testdata['category2'];
        $course1 = $testdata['course1'];
        $course2 = $testdata['course2'];
        $course3 = $testdata['course3'];

        $target_user = new \totara_userdata\userdata\target_user($user1);
        \core_completion\userdata\course_completion::execute_purge($target_user, context_coursecat::instance($category2->id));

        $coursecompletions = $DB->get_records('course_completions');
        $this->assertCount(4, $coursecompletions);
        $this->assert_count_with_userid(1, $coursecompletions, $user1->id, $course1->id);
        $this->assert_count_with_userid(0, $coursecompletions, $user1->id, $course2->id);
        $this->assert_count_with_userid(0, $coursecompletions, $user1->id, $course3->id);
        $this->assert_count_with_userid(3, $coursecompletions, $user2->id);

        $coursecompletioncritcompl = $DB->get_records('course_completion_crit_compl');
        $this->assertCount(4, $coursecompletioncritcompl);
        $this->assert_count_with_userid(1, $coursecompletioncritcompl, $user1->id, $course1->id);
        $this->assert_count_with_userid(0, $coursecompletioncritcompl, $user1->id, $course2->id);
        $this->assert_count_with_userid(0, $coursecompletioncritcompl, $user1->id, $course3->id);
        $this->assert_count_with_userid(3, $coursecompletioncritcompl, $user2->id);

        $coursemodulescompletion = $DB->get_records('course_modules_completion');
        $this->assertCount(4, $coursemodulescompletion);
        $this->assert_count_with_userid(1, $coursemodulescompletion, $user1->id);
        $course1moduleid = $DB->get_field('course_modules', 'id', ['course' => $course1->id]);
        foreach ($coursemodulescompletion as $record) {
            if ($record->userid == $user1->id) {
                // The only record belonging to user1 should be the module in course1.
                $this->assertEquals($course1moduleid, $record->coursemoduleid);
            }
        }
        $this->assert_count_with_userid(3, $coursemodulescompletion, $user2->id);

        $coursecompletionhistory = $DB->get_records('course_completion_history');
        $this->assertCount(4, $coursecompletionhistory);
        $this->assert_count_with_userid(1, $coursecompletionhistory, $user1->id, $course1->id);
        $this->assert_count_with_userid(0, $coursecompletionhistory, $user1->id, $course2->id);
        $this->assert_count_with_userid(0, $coursecompletionhistory, $user1->id, $course3->id);
        $this->assert_count_with_userid(3, $coursecompletionhistory, $user2->id);

        $coursecompletionlog = $DB->get_records('course_completion_log');
        $this->assertCount(16, $coursecompletionlog);
        $this->assert_count_with_userid(4, $coursecompletionlog, $user1->id, $course1->id);
        $this->assert_count_with_userid(0, $coursecompletionlog, $user1->id, $course2->id);
        $this->assert_count_with_userid(0, $coursecompletionlog, $user1->id, $course3->id);
        $this->assert_count_with_userid(12, $coursecompletionlog, $user2->id);

        // Running count in this context should now show that those records are gone.
        $count = \core_completion\userdata\course_completion::execute_count($target_user, context_coursecat::instance($category2->id));
        $this->assertEquals(0, $count);
    }

    public function test_export_course_context() {
        $testdata = $this->create_completion_data();

        $user1 = $testdata['user1'];

        $target_user = new \totara_userdata\userdata\target_user($user1);
        $export = \core_completion\userdata\course_completion::execute_export($target_user,
                context_course::instance($testdata['course3']->id));

        $this->assertEmpty($export->files);
        $this->assertCount(1, $export->data['course_completion_crit_compl']);
        $this->assert_count_with_userid(1, $export->data['course_completion_crit_compl'], $user1->id);
        $this->assertCount(1, $export->data['course_modules_completion']);
        $this->assert_count_with_userid(1, $export->data['course_modules_completion'], $user1->id);
        $this->assertCount(1, $export->data['course_completion_history']);
        $this->assert_count_with_userid(1, $export->data['course_completion_history'], $user1->id);
        $this->assertCount(4, $export->data['course_completion_log']);
        $this->assert_count_with_userid(4, $export->data['course_completion_log'], $user1->id);
        $this->assertCount(2, $export->data['block_totara_stats']);
        $this->assert_count_with_userid(2, $export->data['block_totara_stats'], $user1->id);
    }

    public function test_count_course_context() {
        $testdata = $this->create_completion_data();

        $target_user = new \totara_userdata\userdata\target_user($testdata['user1']);
        $count = \core_completion\userdata\course_completion::execute_count($target_user, context_course::instance($testdata['course3']->id));

        $this->assertEquals(1, $count);

        return $testdata;
    }

    public function test_purge_course_context() {
        global $DB;

        $testdata = $this->create_completion_data();

        $user1 = $testdata['user1'];
        $user2 = $testdata['user2'];
        $course1 = $testdata['course1'];
        $course2 = $testdata['course2'];
        $course3 = $testdata['course3'];

        $target_user = new \totara_userdata\userdata\target_user($testdata['user1']);
        \core_completion\userdata\course_completion::execute_purge($target_user, context_course::instance($course3->id));

        $coursecompletions = $DB->get_records('course_completions');
        $this->assertCount(5, $coursecompletions);
        $this->assert_count_with_userid(1, $coursecompletions, $user1->id, $course1->id);
        $this->assert_count_with_userid(1, $coursecompletions, $user1->id, $course2->id);
        $this->assert_count_with_userid(0, $coursecompletions, $user1->id, $course3->id);
        $this->assert_count_with_userid(3, $coursecompletions, $user2->id);

        $coursecompletioncritcompl = $DB->get_records('course_completion_crit_compl');
        $this->assertCount(5, $coursecompletioncritcompl);
        $this->assert_count_with_userid(1, $coursecompletioncritcompl, $user1->id, $course1->id);
        $this->assert_count_with_userid(1, $coursecompletioncritcompl, $user1->id, $course2->id);
        $this->assert_count_with_userid(0, $coursecompletioncritcompl, $user1->id, $course3->id);
        $this->assert_count_with_userid(3, $coursecompletioncritcompl, $user2->id);

        $coursemodulescompletion = $DB->get_records('course_modules_completion');
        $this->assertCount(5, $coursemodulescompletion);
        $this->assert_count_with_userid(2, $coursemodulescompletion, $user1->id);
        $course3moduleid = $DB->get_field('course_modules', 'id', ['course' => $course3->id]);
        foreach ($coursemodulescompletion as $record) {
            if ($record->userid == $user1->id) {
                // Records belonging to user1 should not include course3 as this was purged.
                $this->assertNotEquals($course3moduleid, $record->coursemoduleid);
            }
        }
        $this->assert_count_with_userid(3, $coursemodulescompletion, $user2->id);

        $coursecompletionhistory = $DB->get_records('course_completion_history');
        $this->assertCount(5, $coursecompletionhistory);
        $this->assert_count_with_userid(1, $coursecompletionhistory, $user1->id, $course1->id);
        $this->assert_count_with_userid(1, $coursecompletionhistory, $user1->id, $course2->id);
        $this->assert_count_with_userid(0, $coursecompletionhistory, $user1->id, $course3->id);
        $this->assert_count_with_userid(3, $coursecompletionhistory, $user2->id);

        $coursecompletionlog = $DB->get_records('course_completion_log');
        $this->assertCount(20, $coursecompletionlog);
        $this->assert_count_with_userid(4, $coursecompletionlog, $user1->id, $course1->id);
        $this->assert_count_with_userid(4, $coursecompletionlog, $user1->id, $course2->id);
        $this->assert_count_with_userid(0, $coursecompletionlog, $user1->id, $course3->id);
        $this->assert_count_with_userid(12, $coursecompletionlog, $user2->id);

        // Running count in this context should now show that those records are gone.
        $count = \core_completion\userdata\course_completion::execute_count($target_user, context_course::instance($course3->id));
        $this->assertEquals(0, $count);
    }

    /**
     * Ensure that purging occurs without error when a user has been deleted.
     *
     * We don't need to do this for each context.
     */
    public function test_purge_deleted_user() {
        global $DB;

        $testdata = $this->create_completion_data();

        $user1 = $testdata['user1'];
        $user2 = $testdata['user2'];

        delete_user($user1);
        // Reload user so that deleted is set to 1 like it is now in the DB.
        $user1 = $DB->get_record('user', ['id' => $testdata['user1']->id]);

        $target_user = new \totara_userdata\userdata\target_user($user1);
        \core_completion\userdata\course_completion::execute_purge($target_user, context_system::instance());

        $coursecompletions = $DB->get_records('course_completions');
        $this->assertCount(3, $coursecompletions);
        $this->assert_count_with_userid(0, $coursecompletions, $user1->id);
        $this->assert_count_with_userid(3, $coursecompletions, $user2->id);

        $coursecompletioncritcompl = $DB->get_records('course_completion_crit_compl');
        $this->assertCount(3, $coursecompletioncritcompl);
        $this->assert_count_with_userid(0, $coursecompletioncritcompl, $user1->id);
        $this->assert_count_with_userid(3, $coursecompletioncritcompl, $user2->id);

        $coursemodulescompletion = $DB->get_records('course_modules_completion');
        $this->assertCount(3, $coursemodulescompletion);
        $this->assert_count_with_userid(0, $coursemodulescompletion, $user1->id);
        $this->assert_count_with_userid(3, $coursemodulescompletion, $user2->id);

        $coursecompletionhistory = $DB->get_records('course_completion_history');
        $this->assertCount(3, $coursecompletionhistory);
        $this->assert_count_with_userid(0, $coursecompletionhistory, $user1->id);
        $this->assert_count_with_userid(3, $coursecompletionhistory, $user2->id);

        $coursecompletionlog = $DB->get_records('course_completion_log');
        $this->assertCount(12, $coursecompletionlog);
        $this->assert_count_with_userid(0, $coursecompletionlog, $user1->id);
        $this->assert_count_with_userid(12, $coursecompletionlog, $user2->id);

        $blocktotarastats = $DB->get_records('block_totara_stats');
        $this->assertCount(6, $blocktotarastats);
        $this->assert_count_with_userid(0, $blocktotarastats, $user1->id);
        $this->assert_count_with_userid(6, $blocktotarastats, $user2->id);
    }
}