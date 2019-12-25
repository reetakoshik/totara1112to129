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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_glossary
 */

use mod_glossary\userdata\entries;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Test purge, export and count of glossary entries user data item.
 *
 * @group totara_userdata
 */
class core_user_userdata_entries_test extends advanced_testcase {

    /**
     * Set up tests.
     */
    protected function setUp() {
        parent::setUp();

        $this->resetAfterTest(true);
    }

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        global $CFG;
        require_once($CFG->dirroot . '/comment/lib.php');
        require_once($CFG->dirroot . '/rating/lib.php');
    }

    /**
     * Test some general settings for this item.
     */
    public function test_general() {
        $contextlevels = entries::get_compatible_context_levels();

        $this->assertCount(4, $contextlevels);
        $this->assertContains(CONTEXT_SYSTEM, $contextlevels);
        $this->assertContains(CONTEXT_COURSE, $contextlevels);
        $this->assertContains(CONTEXT_COURSECAT, $contextlevels);
        $this->assertContains(CONTEXT_MODULE, $contextlevels);

        $this->assertTrue(entries::is_exportable());
        $this->assertTrue(entries::is_countable());
        $this->assertTrue(entries::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(entries::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(entries::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * @param array $user_properties
     * @return stdClass
     */
    private function setup_data(array $user_properties = []): stdClass {
        global $DB;

        $data = new stdClass();

        // Create users.
        $data->user1 = $this->getDataGenerator()->create_user($user_properties);
        $data->user2 = $this->getDataGenerator()->create_user($user_properties);

        // Create categories.
        $data->category1 = $this->getDataGenerator()->create_category();
        $data->category2 = $this->getDataGenerator()->create_category();

        // Create courses.
        $data->course1 = $this->getDataGenerator()->create_course(['category' => $data->category1->id]);
        $data->course2 = $this->getDataGenerator()->create_course(['category' => $data->category2->id]);
        $data->course3 = $this->getDataGenerator()->create_course(['category' => $data->category2->id]);

        // Enrol users.
        $student_role = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($data->user1->id, $data->course1->id, $student_role->id);
        $this->getDataGenerator()->enrol_user($data->user1->id, $data->course2->id, $student_role->id);
        $this->getDataGenerator()->enrol_user($data->user1->id, $data->course3->id, $student_role->id);
        $this->getDataGenerator()->enrol_user($data->user2->id, $data->course1->id, $student_role->id);
        $this->getDataGenerator()->enrol_user($data->user2->id, $data->course2->id, $student_role->id);
        $this->getDataGenerator()->enrol_user($data->user2->id, $data->course3->id, $student_role->id);

        /** @var mod_glossary_generator $glossary_generator */
        $glossary_generator = self::getDataGenerator()->get_plugin_generator('mod_glossary');
        /** @var core_completion_generator $completiongenerator */
        $completiongenerator = $this->getDataGenerator()->get_plugin_generator('core_completion');
        $completiongenerator->enable_completion_tracking($data->course1);
        $completiongenerator->enable_completion_tracking($data->course2);
        $completiongenerator->enable_completion_tracking($data->course3);

        // Create one glossary for each course.
        $common_options = [
            'allowcomments' => 1,
            'assessed' => RATING_AGGREGATE_AVERAGE,
            'scale' => 100,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionentries' => 1,
        ];
        $data->glossary1 = $glossary_generator->create_instance(['course' => $data->course1->id] + $common_options);
        $data->glossary2 = $glossary_generator->create_instance(['course' => $data->course2->id] + $common_options);
        $data->glossary3 = $glossary_generator->create_instance(['course' => $data->course3->id] + $common_options);

        $context1 = context_module::instance($data->glossary1->cmid);
        $context2 = context_module::instance($data->glossary2->cmid);
        $context3 = context_module::instance($data->glossary3->cmid);
        assign_capability('mod/glossary:rate', CAP_ALLOW, $student_role->id, $context1->id, true);
        assign_capability('mod/glossary:rate', CAP_ALLOW, $student_role->id, $context2->id, true);
        assign_capability('mod/glossary:rate', CAP_ALLOW, $student_role->id, $context3->id, true);

        // Add glossary entries
        $common_options = [
            'approved' => 1,
            'attachment' => 1,
        ];
        $data->entry_11 = $glossary_generator->create_content($data->glossary1, ['userid' => $data->user1->id] + $common_options, ['alias1', 'alias2']);
        $data->entry_12 = $glossary_generator->create_content($data->glossary2, ['userid' => $data->user1->id] + $common_options, ['alias3']);
        $data->entry_13 = $glossary_generator->create_content($data->glossary3, ['userid' => $data->user1->id] + $common_options, ['alias4']);
        $data->entry_21 = $glossary_generator->create_content($data->glossary1, ['userid' => $data->user2->id] + $common_options, ['alias5']);

        // Add fake inline images to all the entries.
        $fs = get_file_storage();
        $filerecord_inline = [
            'component' => 'mod_glossary',
            'filearea'  => 'entry',
            'filepath'  => '/',
            'filename'  => 'shouldbeanimage.jpg',
        ];
        $data->files['entry'][$data->entry_11->id] = $fs->create_file_from_string(['itemid' => $data->entry_11->id, 'contextid' => $context1->id, 'userid' => $data->user1->id] + $filerecord_inline, 'image entry11 contents');
        $data->files['entry'][$data->entry_12->id] = $fs->create_file_from_string(['itemid' => $data->entry_12->id, 'contextid' => $context2->id, 'userid' => $data->user1->id] + $filerecord_inline, 'image entry12 contents');
        $data->files['entry'][$data->entry_13->id] = $fs->create_file_from_string(['itemid' => $data->entry_13->id, 'contextid' => $context3->id, 'userid' => $data->user1->id] + $filerecord_inline, 'image entry13 contents');
        $data->files['entry'][$data->entry_21->id] = $fs->create_file_from_string(['itemid' => $data->entry_21->id, 'contextid' => $context1->id, 'userid' => $data->user2->id] + $filerecord_inline, 'image entry21 contents');

        // Also add attachment files to all the entries.
        $filerecord_attachment = [
            'component' => 'mod_glossary',
            'filearea'  => 'attachment',
            'filepath'  => '/',
            'filename'  => 'attachmentfilename.pdf',
        ];
        $data->files['attachment'][$data->entry_11->id] = $fs->create_file_from_string(['itemid' => $data->entry_11->id, 'contextid' => $context1->id, 'userid' => $data->user1->id] + $filerecord_attachment, 'attachment entry11 contents');
        $data->files['attachment'][$data->entry_12->id] = $fs->create_file_from_string(['itemid' => $data->entry_12->id, 'contextid' => $context2->id, 'userid' => $data->user1->id] + $filerecord_attachment, 'attachment entry12 contents');
        $data->files['attachment'][$data->entry_13->id] = $fs->create_file_from_string(['itemid' => $data->entry_13->id, 'contextid' => $context3->id, 'userid' => $data->user1->id] + $filerecord_attachment, 'attachment entry13 contents');
        $data->files['attachment'][$data->entry_21->id] = $fs->create_file_from_string(['itemid' => $data->entry_21->id, 'contextid' => $context1->id, 'userid' => $data->user2->id] + $filerecord_attachment, 'attachment entry21 contents');

        // Add comments and ratings for purge user.
        $this->setUser($data->user1);
        $data->comment_11 = $this->add_entry_comment($data->glossary1, $data->entry_21);
        $data->comment_12 = $this->add_entry_comment($data->glossary2, $data->entry_12);
        $this->add_rating($data->glossary1, $data->entry_21, 60);

        // Add comments and ratings for control user.
        $this->setUser($data->user2);
        $data->comment_21 = $this->add_entry_comment($data->glossary1, $data->entry_11);
        $data->comment_23 = $this->add_entry_comment($data->glossary3, $data->entry_13);
        $this->add_rating($data->glossary1, $data->entry_11, 70);
        $this->add_rating($data->glossary2, $data->entry_12, 80);
        $this->add_rating($data->glossary3, $data->entry_13, 90);

        // Add completion data for both users for glossary1.
        $completiongenerator->set_activity_completion($data->course1->id, [$data->glossary1]);
        $completioninfo = new completion_info($data->course1);
        $data->cm1 = get_coursemodule_from_instance('glossary', $data->glossary1->id);
        $completioninfo->update_state($data->cm1, COMPLETION_COMPLETE, $data->user1->id);
        $completioninfo->update_state($data->cm1, COMPLETION_COMPLETE, $data->user2->id);

        // Also glossary2 for user1.
        $completiongenerator->set_activity_completion($data->course2->id, [$data->glossary2]);
        $completioninfo = new completion_info($data->course2);
        $data->cm2 = get_coursemodule_from_instance('glossary', $data->glossary2->id);
        $completioninfo->update_state($data->cm2, COMPLETION_COMPLETE, $data->user1->id);

        // And glossary3 for user1.
        $completiongenerator->set_activity_completion($data->course3->id, [$data->glossary3]);
        $completioninfo = new completion_info($data->course3);
        $data->cm3 = get_coursemodule_from_instance('glossary', $data->glossary3->id);
        $completioninfo->update_state($data->cm3, COMPLETION_COMPLETE, $data->user1->id);

        // Verify that data was generated as expected.
        // Glossary entries.
        $this->assertCount(3, $DB->get_records('glossary_entries', ['userid' => $data->user1->id]));
        $this->assertCount(1, $DB->get_records('glossary_entries', ['userid' => $data->user2->id]));

        // Comments.
        $this->assertCount(2, $DB->get_records('comments', ['userid' => $data->user1->id]));
        $this->assertCount(2, $DB->get_records('comments', ['userid' => $data->user2->id]));

        // Ratings.
        $this->assertCount(1, $DB->get_records('rating', ['userid' => $data->user1->id]));
        $this->assertCount(3, $DB->get_records('rating', ['userid' => $data->user2->id]));

        // Aliases.
        $this->assertCount(2, $DB->get_records('glossary_alias', ['entryid' => $data->entry_11->id]));
        $this->assertCount(1, $DB->get_records('glossary_alias', ['entryid' => $data->entry_12->id]));
        $this->assertCount(1, $DB->get_records('glossary_alias', ['entryid' => $data->entry_21->id]));

        // Module completion records.
        $this->assertCount(3, $DB->get_records('course_modules_completion', ['userid' => $data->user1->id]));
        $this->assertTrue($DB->record_exists('course_modules_completion', ['userid' => $data->user1->id, 'completionstate' => COMPLETION_COMPLETE, 'coursemoduleid' => $data->cm1->id]));
        $this->assertTrue($DB->record_exists('course_modules_completion', ['userid' => $data->user1->id, 'completionstate' => COMPLETION_COMPLETE, 'coursemoduleid' => $data->cm2->id]));
        $this->assertTrue($DB->record_exists('course_modules_completion', ['userid' => $data->user1->id, 'completionstate' => COMPLETION_COMPLETE, 'coursemoduleid' => $data->cm3->id]));
        $this->assertCount(1, $DB->get_records('course_modules_completion', ['userid' => $data->user2->id]));
        $this->assertTrue($DB->record_exists('course_modules_completion', ['userid' => $data->user2->id, 'completionstate' => COMPLETION_COMPLETE, 'coursemoduleid' => $data->cm1->id]));

        // Grades.
        $data->glossary1_grade_item_id = $DB->get_field('grade_items', 'id', ['iteminstance' => $data->glossary1->id]);
        $data->glossary2_grade_item_id = $DB->get_field('grade_items', 'id', ['iteminstance' => $data->glossary2->id]);
        $data->glossary3_grade_item_id = $DB->get_field('grade_items', 'id', ['iteminstance' => $data->glossary3->id]);
        $this->assertCount(2, $DB->get_records('grade_grades', ['itemid' => $data->glossary1_grade_item_id]));
        $this->assertCount(1, $DB->get_records('grade_grades', ['itemid' => $data->glossary2_grade_item_id]));
        $this->assertCount(1, $DB->get_records('grade_grades', ['itemid' => $data->glossary3_grade_item_id]));
        $this->assertTrue($DB->record_exists('grade_grades', ['userid' => $data->user2->id, 'itemid' => $data->glossary1_grade_item_id, 'finalgrade' => 60]));
        $this->assertTrue($DB->record_exists('grade_grades', ['userid' => $data->user1->id, 'itemid' => $data->glossary1_grade_item_id,'finalgrade' => 70]));
        $this->assertTrue($DB->record_exists('grade_grades', ['userid' => $data->user1->id, 'itemid' => $data->glossary2_grade_item_id, 'finalgrade' => 80]));
        $this->assertTrue($DB->record_exists('grade_grades', ['userid' => $data->user1->id, 'itemid' => $data->glossary3_grade_item_id, 'finalgrade' => 90]));

        // Files (2 records expected per file).
        $this->assertCount(12, $DB->get_records('files', ['userid' => $data->user1->id]));
        $this->assertCount(4, $DB->get_records('files', ['userid' => $data->user2->id]));

        return $data;
    }

    /**
     * @param stdClass $glossary
     * @param stdClass $entry
     * @return stdClass
     */
    private function add_entry_comment(stdClass $glossary, stdClass $entry): stdClass {
        $context = context_module::instance($glossary->cmid);
        $cm = get_coursemodule_from_instance('glossary', $glossary->id, $glossary->course);
        $cmt = new stdClass();
        $cmt->component = 'mod_glossary';
        $cmt->context = $context;
        $cmt->courseid = $glossary->course;
        $cmt->cm = $cm;
        $cmt->area = 'glossary_entry';
        $cmt->itemid = $entry->id;
        $cmt->showcount = true;
        $comment = new comment($cmt);
        return $comment->add('New comment');
    }

    /**
     * @param stdClass $glossary
     * @param stdClass $entry
     * @param int $rating
     */
    private function add_rating(stdClass $glossary, stdClass $entry, int $rating) {
        $context = context_module::instance($glossary->cmid);
        $cm = get_coursemodule_from_instance('glossary', $glossary->id, $glossary->course);
        $rm = new rating_manager();
        $result = $rm->add_rating($cm, $context, 'mod_glossary', 'entry', $entry->id, 100, $rating, $entry->userid, 1);
        $this->assertTrue($result->success);
    }

    /**
     * Test if data is correctly purged in system context for active user.
     */
    public function test_purge_in_system_context_active_user() {
        $data = $this->setup_data([]);
        $this->purge_and_assert_system_context($data);
    }

    /**
     * Test if data is correctly purged in system context for suspended user.
     */
    public function test_purge_in_system_context_suspended_user() {
        $data = $this->setup_data(['suspended' => 1]);
        $this->purge_and_assert_system_context($data);
    }

    /**
     * Test if data is correctly purged in system context for deleted user.
     */
    public function test_purge_in_system_context_deleted_user() {
        global $DB;

        // Set up data for active user, then delete user afterwards.
        $data = $this->setup_data([]);
        delete_user($DB->get_record('user', ['id' => $data->user1->id]));

        // Make sure the glossary entries are still there.
        $this->assertCount(3, $DB->get_records('glossary_entries', ['userid' => $data->user1->id]));

        $data->user1 = $DB->get_record('user', ['id' => $data->user1->id]);
        $this->purge_and_assert_system_context($data);
    }

    /**
     * Test if data is correctly purged in system context.
     *
     * @param stdClass $data  Setup data as created by method setup_data()
     */
    private function purge_and_assert_system_context(stdClass $data) {
        global $DB;

        // Purge data for user1.
        $targetuser = new target_user($data->user1);
        $sink = $this->redirectEvents();
        $status = entries::execute_purge($targetuser, context_system::instance());
        $events = $sink->get_events();
        $sink->close();
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        // Check that entry_deleted events were triggered correctly.
        $deleted_entry_ids = [];
        foreach ($events as $event) {
            if ($event instanceof \mod_glossary\event\entry_deleted) {
                $event_data = $event->get_data();
                $deleted_entry_ids[] = $event_data['objectid'];
            }
        }
        $this->assertCount(3, $deleted_entry_ids);
        $this->assertArraySubset([$data->entry_11->id, $data->entry_12->id, $data->entry_13->id], $deleted_entry_ids);

        // Verify that expected data was removed for purged user1 and control user2 wasn't affected.
        // Glossary entries.
        $this->assertCount(0, $DB->get_records('glossary_entries', ['userid' => $data->user1->id]));
        $this->assertCount(1, $DB->get_records('glossary_entries', ['userid' => $data->user2->id]));

        // Only comments on deleted entries should be deleted, no matter who made the comment.
        $this->assertTrue($DB->record_exists('comments', ['id' => $data->comment_11->id]));
        $this->assertFalse($DB->record_exists('comments', ['id' => $data->comment_12->id]));
        $this->assertFalse($DB->record_exists('comments', ['id' => $data->comment_21->id]));
        $this->assertFalse($DB->record_exists('comments', ['id' => $data->comment_23->id]));

        // Only ratings on deleted entries should be deleted.
        $this->assertCount(1, $DB->get_records('rating', ['userid' => $data->user1->id]));
        $this->assertCount(0, $DB->get_records('rating', ['userid' => $data->user2->id]));

        // Aliases.
        $this->assertCount(0, $DB->get_records('glossary_alias', ['entryid' => $data->entry_11->id]));
        $this->assertCount(0, $DB->get_records('glossary_alias', ['entryid' => $data->entry_12->id]));
        $this->assertCount(0, $DB->get_records('glossary_alias', ['entryid' => $data->entry_13->id]));
        $this->assertCount(1, $DB->get_records('glossary_alias', ['entryid' => $data->entry_21->id]));

        // Completion and grade updates are not happening for purging of deleted users, so skip those assertions.
        if ($targetuser->status !== $targetuser::STATUS_DELETED) {
            $this->assertCount(3, $DB->get_records('course_modules_completion', ['userid' => $data->user1->id]));
            $this->assertTrue($DB->record_exists('course_modules_completion', ['userid' => $data->user1->id, 'completionstate' => COMPLETION_INCOMPLETE, 'coursemoduleid' => $data->cm1->id]));
            $this->assertTrue($DB->record_exists('course_modules_completion', ['userid' => $data->user1->id, 'completionstate' => COMPLETION_INCOMPLETE, 'coursemoduleid' => $data->cm2->id]));
            $this->assertTrue($DB->record_exists('course_modules_completion', ['userid' => $data->user1->id, 'completionstate' => COMPLETION_INCOMPLETE, 'coursemoduleid' => $data->cm3->id]));
            $this->assertCount(1, $DB->get_records('course_modules_completion', ['userid' => $data->user2->id]));
            $this->assertTrue($DB->record_exists('course_modules_completion', ['userid' => $data->user2->id, 'completionstate' => COMPLETION_COMPLETE, 'coursemoduleid' => $data->cm1->id]));

            $this->assertCount(2, $DB->get_records('grade_grades', ['itemid' => $data->glossary1_grade_item_id]));
            $this->assertCount(1, $DB->get_records('grade_grades', ['itemid' => $data->glossary2_grade_item_id]));
            $this->assertCount(1, $DB->get_records('grade_grades', ['itemid' => $data->glossary3_grade_item_id]));
            $this->assertTrue($DB->record_exists('grade_grades', ['userid' => $data->user2->id, 'itemid' => $data->glossary1_grade_item_id, 'finalgrade' => 60]));
            $this->assertTrue($DB->record_exists('grade_grades', ['userid' => $data->user1->id, 'itemid' => $data->glossary1_grade_item_id,'finalgrade' => null]));
            $this->assertTrue($DB->record_exists('grade_grades', ['userid' => $data->user1->id, 'itemid' => $data->glossary2_grade_item_id, 'finalgrade' => null]));
            $this->assertTrue($DB->record_exists('grade_grades', ['userid' => $data->user1->id, 'itemid' => $data->glossary3_grade_item_id, 'finalgrade' => null]));
        }

        // Files (2 records expected per file).
        $this->assertCount(0, $DB->get_records('files', ['userid' => $data->user1->id]));
        $this->assertCount(4, $DB->get_records('files', ['userid' => $data->user2->id]));
    }

    /**
     * Test if data is correctly purged in course context for active user.
     */
    public function test_purge_in_course_context_active_user() {
        $data = $this->setup_data([]);
        $this->purge_and_assert_course_context($data);
    }

    /**
     * Test if data is correctly purged in course context for suspended user.
     */
    public function test_purge_in_course_context_suspended_user() {
        $data = $this->setup_data(['suspended' => 1]);
        $this->purge_and_assert_course_context($data);
    }

    /**
     * Test if data is correctly purged in system context for deleted user.
     */
    public function test_purge_in_course_context_deleted_user() {
        global $DB;

        // Set up data for active user, then delete user afterwards.
        $data = $this->setup_data([]);
        delete_user($DB->get_record('user', ['id' => $data->user1->id]));

        // Make sure the glossary entries are still there.
        $this->assertCount(3, $DB->get_records('glossary_entries', ['userid' => $data->user1->id]));

        $data->user1 = $DB->get_record('user', ['id' => $data->user1->id]);
        $this->purge_and_assert_course_context($data);
    }

    /**
     * Test if data is correctly purged in course context.
     *
     * @param stdClass $data  Setup data as created by method setup_data()
     */
    private function purge_and_assert_course_context(stdClass $data) {
        global $DB;

        // Purge data for user1 in course1 context.
        $targetuser = new target_user($data->user1);
        $sink = $this->redirectEvents();
        $status = entries::execute_purge($targetuser, context_course::instance($data->course1->id));
        $events = $sink->get_events();
        $sink->close();
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        // Check that entry_deleted event was triggered correctly.
        $deleted_entry_ids = [];
        foreach ($events as $event) {
            if ($event instanceof \mod_glossary\event\entry_deleted) {
                $event_data = $event->get_data();
                $deleted_entry_ids[] = $event_data['objectid'];
            }
        }
        $this->assertCount(1, $deleted_entry_ids);
        $this->assertArraySubset([$data->entry_11->id], $deleted_entry_ids);

        // Verify that expected data was removed for purged user1/course1 and control user2 wasn't affected.
        $this->assertCount(2, $DB->get_records('glossary_entries', ['userid' => $data->user1->id]));
        $this->assertTrue($DB->record_exists('glossary_entries', ['userid' => $data->user1->id, 'id' => $data->entry_12->id]));
        $this->assertTrue($DB->record_exists('glossary_entries', ['userid' => $data->user1->id, 'id' => $data->entry_13->id]));
        $this->assertCount(1, $DB->get_records('glossary_entries', ['userid' => $data->user2->id]));

        // Only comments on deleted entries should be deleted, no matter who made the comment.
        $this->assertTrue($DB->record_exists('comments', ['id' => $data->comment_11->id]));
        $this->assertTrue($DB->record_exists('comments', ['id' => $data->comment_12->id]));
        $this->assertFalse($DB->record_exists('comments', ['id' => $data->comment_21->id]));
        $this->assertTrue($DB->record_exists('comments', ['id' => $data->comment_23->id]));

        // Only ratings on deleted entries should be deleted.
        $this->assertCount(1, $DB->get_records('rating', ['userid' => $data->user1->id]));
        $this->assertCount(2, $DB->get_records('rating', ['userid' => $data->user2->id]));
        $this->assertFalse($DB->record_exists('rating', ['userid' => $data->user2->id, 'itemid' => $data->entry_11->id]));
        $this->assertTrue($DB->record_exists('rating', ['userid' => $data->user2->id, 'itemid' => $data->entry_12->id]));
        $this->assertTrue($DB->record_exists('rating', ['userid' => $data->user2->id, 'itemid' => $data->entry_13->id]));

        $this->assertCount(0, $DB->get_records('glossary_alias', ['entryid' => $data->entry_11->id]));
        $this->assertCount(1, $DB->get_records('glossary_alias', ['entryid' => $data->entry_12->id]));
        $this->assertCount(1, $DB->get_records('glossary_alias', ['entryid' => $data->entry_13->id]));
        $this->assertCount(1, $DB->get_records('glossary_alias', ['entryid' => $data->entry_21->id]));

        // Completion and grade updates are not happening for purging of deleted users, so skip those assertions.
        if ($targetuser->status !== $targetuser::STATUS_DELETED) {
            $this->assertCount(3, $DB->get_records('course_modules_completion', ['userid' => $data->user1->id]));
            $this->assertTrue($DB->record_exists('course_modules_completion', ['userid' => $data->user1->id, 'completionstate' => COMPLETION_INCOMPLETE, 'coursemoduleid' => $data->cm1->id]));
            $this->assertTrue($DB->record_exists('course_modules_completion', ['userid' => $data->user1->id, 'completionstate' => COMPLETION_COMPLETE, 'coursemoduleid' => $data->cm2->id]));
            $this->assertTrue($DB->record_exists('course_modules_completion', ['userid' => $data->user1->id, 'completionstate' => COMPLETION_COMPLETE, 'coursemoduleid' => $data->cm3->id]));
            $this->assertCount(1, $DB->get_records('course_modules_completion', ['userid' => $data->user2->id]));
            $this->assertTrue($DB->record_exists('course_modules_completion', ['userid' => $data->user2->id, 'completionstate' => COMPLETION_COMPLETE, 'coursemoduleid' => $data->cm1->id]));

            $this->assertCount(2, $DB->get_records('grade_grades', ['itemid' => $data->glossary1_grade_item_id]));
            $this->assertCount(1, $DB->get_records('grade_grades', ['itemid' => $data->glossary2_grade_item_id]));
            $this->assertCount(1, $DB->get_records('grade_grades', ['itemid' => $data->glossary3_grade_item_id]));
            $this->assertTrue($DB->record_exists('grade_grades', ['userid' => $data->user2->id, 'itemid' => $data->glossary1_grade_item_id, 'finalgrade' => 60]));
            $this->assertTrue($DB->record_exists('grade_grades', ['userid' => $data->user1->id, 'itemid' => $data->glossary1_grade_item_id,'finalgrade' => null]));
            $this->assertTrue($DB->record_exists('grade_grades', ['userid' => $data->user1->id, 'itemid' => $data->glossary2_grade_item_id, 'finalgrade' => 80]));
            $this->assertTrue($DB->record_exists('grade_grades', ['userid' => $data->user1->id, 'itemid' => $data->glossary3_grade_item_id, 'finalgrade' => 90]));
        }

        // 2 records expected per file.
        $this->assertCount(8, $DB->get_records('files', ['userid' => $data->user1->id]));
        $this->assertCount(4, $DB->get_records('files', ['userid' => $data->user1->id, 'itemid' => $data->entry_12->id]));
        $this->assertCount(4, $DB->get_records('files', ['userid' => $data->user1->id, 'itemid' => $data->entry_13->id]));
        $this->assertCount(4, $DB->get_records('files', ['userid' => $data->user2->id]));
    }

    /**
     * Test if data is correctly purged in course category context for active user.
     */
    public function test_purge_in_course_category_context_active_user() {
        $data = $this->setup_data([]);
        $this->purge_and_assert_course_category_context($data);
    }

    /**
     * Test if data is correctly purged in course category context for suspended user.
     */
    public function test_purge_in_course_category_context_suspended_user() {
        $data = $this->setup_data(['suspended' => 1]);
        $this->purge_and_assert_course_category_context($data);
    }

    /**
     * Test if data is correctly purged in course category context for deleted user.
     */
    public function test_purge_in_course_category_context_deleted_user() {
        global $DB;

        // Set up data for active user, then delete user afterwards.
        $data = $this->setup_data([]);
        delete_user($DB->get_record('user', ['id' => $data->user1->id]));

        // Make sure the glossary entries are still there.
        $this->assertCount(3, $DB->get_records('glossary_entries', ['userid' => $data->user1->id]));

        $data->user1 = $DB->get_record('user', ['id' => $data->user1->id]);
        $this->purge_and_assert_course_category_context($data);
    }

    /**
     * Test if data is correctly purged in course category context.
     *
     * @param array stdClass $data
     */
    private function purge_and_assert_course_category_context(stdClass $data) {
        global $DB;

        // Purge data for user1 in course category2 context.
        $targetuser = new target_user($data->user1);
        $sink = $this->redirectEvents();
        $status = entries::execute_purge($targetuser, context_coursecat::instance($data->category2->id));
        $events = $sink->get_events();
        $sink->close();
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        // Check that entry_deleted event was triggered correctly.
        $deleted_entry_ids = [];
        foreach ($events as $event) {
            if ($event instanceof \mod_glossary\event\entry_deleted) {
                $event_data = $event->get_data();
                $deleted_entry_ids[] = $event_data['objectid'];
            }
        }
        $this->assertCount(2, $deleted_entry_ids);
        $this->assertArraySubset([$data->entry_12->id, $data->entry_13->id], $deleted_entry_ids);

        // Verify that expected data was removed for purged user1/course1 and control user2 wasn't affected.
        $this->assertCount(1, $DB->get_records('glossary_entries', ['userid' => $data->user1->id]));
        $this->assertTrue($DB->record_exists('glossary_entries', ['userid' => $data->user1->id, 'id' => $data->entry_11->id]));
        $this->assertCount(1, $DB->get_records('glossary_entries', ['userid' => $data->user2->id]));

        // Only comments on deleted entries should be deleted, no matter who made the comment.
        $this->assertTrue($DB->record_exists('comments', ['id' => $data->comment_11->id]));
        $this->assertFalse($DB->record_exists('comments', ['id' => $data->comment_12->id]));
        $this->assertTrue($DB->record_exists('comments', ['id' => $data->comment_21->id]));
        $this->assertFalse($DB->record_exists('comments', ['id' => $data->comment_23->id]));

        // Only ratings on deleted entries should be deleted.
        $this->assertCount(1, $DB->get_records('rating', ['userid' => $data->user1->id]));
        $this->assertCount(1, $DB->get_records('rating', ['userid' => $data->user2->id]));
        $this->assertTrue($DB->record_exists('rating', ['userid' => $data->user2->id, 'itemid' => $data->entry_11->id]));
        $this->assertFalse($DB->record_exists('rating', ['userid' => $data->user2->id, 'itemid' => $data->entry_12->id]));
        $this->assertFalse($DB->record_exists('rating', ['userid' => $data->user2->id, 'itemid' => $data->entry_13->id]));

        $this->assertCount(2, $DB->get_records('glossary_alias', ['entryid' => $data->entry_11->id]));
        $this->assertCount(0, $DB->get_records('glossary_alias', ['entryid' => $data->entry_12->id]));
        $this->assertCount(0, $DB->get_records('glossary_alias', ['entryid' => $data->entry_13->id]));
        $this->assertCount(1, $DB->get_records('glossary_alias', ['entryid' => $data->entry_21->id]));

        // Completion and grade updates are not happening for purging of deleted users, so skip those assertions.
        if ($targetuser->status !== $targetuser::STATUS_DELETED) {
            $this->assertCount(3, $DB->get_records('course_modules_completion', ['userid' => $data->user1->id]));
            $this->assertTrue($DB->record_exists('course_modules_completion', ['userid' => $data->user1->id, 'completionstate' => COMPLETION_COMPLETE, 'coursemoduleid' => $data->cm1->id]));
            $this->assertTrue($DB->record_exists('course_modules_completion', ['userid' => $data->user1->id, 'completionstate' => COMPLETION_INCOMPLETE, 'coursemoduleid' => $data->cm2->id]));
            $this->assertTrue($DB->record_exists('course_modules_completion', ['userid' => $data->user1->id, 'completionstate' => COMPLETION_INCOMPLETE, 'coursemoduleid' => $data->cm3->id]));
            $this->assertCount(1, $DB->get_records('course_modules_completion', ['userid' => $data->user2->id]));
            $this->assertTrue($DB->record_exists('course_modules_completion', ['userid' => $data->user2->id, 'completionstate' => COMPLETION_COMPLETE, 'coursemoduleid' => $data->cm1->id]));

            $this->assertCount(2, $DB->get_records('grade_grades', ['itemid' => $data->glossary1_grade_item_id]));
            $this->assertCount(1, $DB->get_records('grade_grades', ['itemid' => $data->glossary2_grade_item_id]));
            $this->assertCount(1, $DB->get_records('grade_grades', ['itemid' => $data->glossary3_grade_item_id]));
            $this->assertTrue($DB->record_exists('grade_grades', ['userid' => $data->user2->id, 'itemid' => $data->glossary1_grade_item_id, 'finalgrade' => 60]));
            $this->assertTrue($DB->record_exists('grade_grades', ['userid' => $data->user1->id, 'itemid' => $data->glossary1_grade_item_id,'finalgrade' => 70]));
            $this->assertTrue($DB->record_exists('grade_grades', ['userid' => $data->user1->id, 'itemid' => $data->glossary2_grade_item_id, 'finalgrade' => null]));
            $this->assertTrue($DB->record_exists('grade_grades', ['userid' => $data->user1->id, 'itemid' => $data->glossary3_grade_item_id, 'finalgrade' => null]));
        }

        // 2 records expected per file.
        $this->assertCount(4, $DB->get_records('files', ['userid' => $data->user1->id]));
        $this->assertCount(4, $DB->get_records('files', ['userid' => $data->user1->id, 'itemid' => $data->entry_11->id]));
        $this->assertCount(4, $DB->get_records('files', ['userid' => $data->user2->id]));
    }

    /**
     * Test if data is correctly purged in module context for active user.
     */
    public function test_purge_in_module_context_active_user() {
        $data = $this->setup_data([]);
        $this->purge_and_assert_module_context($data);
    }

    /**
     * Test if data is correctly purged in module context for suspended user.
     */
    public function test_purge_in_module_context_suspended_user() {
        $data = $this->setup_data(['suspended' => 1]);
        $this->purge_and_assert_module_context($data);
    }

    /**
     * Test if data is correctly purged in module context for deleted user.
     */
    public function test_purge_in_module_context_deleted_user() {
        global $DB;

        // Set up data for active user, then delete user afterwards.
        $data = $this->setup_data([]);
        delete_user($DB->get_record('user', ['id' => $data->user1->id]));

        // Make sure the glossary entries are still there.
        $this->assertCount(3, $DB->get_records('glossary_entries', ['userid' => $data->user1->id]));

        $data->user1 = $DB->get_record('user', ['id' => $data->user1->id]);
        $this->purge_and_assert_module_context($data);
    }

    /**
     * Test if data is correctly purged in module context.
     *
     * @param stdClass $data  Setup data as created by method setup_data()
     */
    private function purge_and_assert_module_context(stdClass $data) {
        global $DB;

        // Purge data for user1 in module1 context.
        $targetuser = new target_user($data->user1);
        $sink = $this->redirectEvents();
        $status = entries::execute_purge($targetuser, context_module::instance($data->cm2->id));
        $events = $sink->get_events();
        $sink->close();
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        // Check that entry_deleted event was triggered correctly.
        $deleted_entry_ids = [];
        foreach ($events as $event) {
            if ($event instanceof \mod_glossary\event\entry_deleted) {
                $event_data = $event->get_data();
                $deleted_entry_ids[] = $event_data['objectid'];
            }
        }
        $this->assertCount(1, $deleted_entry_ids);
        $this->assertArraySubset([$data->entry_12->id], $deleted_entry_ids);

        // Verify that expected data was removed for purged user1/module2 and control user2 wasn't affected.
        $this->assertCount(2, $DB->get_records('glossary_entries', ['userid' => $data->user1->id]));
        $this->assertTrue($DB->record_exists('glossary_entries', ['userid' => $data->user1->id, 'id' => $data->entry_11->id]));
        $this->assertTrue($DB->record_exists('glossary_entries', ['userid' => $data->user1->id, 'id' => $data->entry_13->id]));
        $this->assertCount(1, $DB->get_records('glossary_entries', ['userid' => $data->user2->id]));

        // Only comments on deleted entries should be deleted, no matter who made the comment.
        $this->assertTrue($DB->record_exists('comments', ['id' => $data->comment_11->id]));
        $this->assertFalse($DB->record_exists('comments', ['id' => $data->comment_12->id]));
        $this->assertTrue($DB->record_exists('comments', ['id' => $data->comment_21->id]));
        $this->assertTrue($DB->record_exists('comments', ['id' => $data->comment_23->id]));

        // Only ratings on deleted entries should be deleted.
        $this->assertCount(1, $DB->get_records('rating', ['userid' => $data->user1->id]));
        $this->assertCount(2, $DB->get_records('rating', ['userid' => $data->user2->id]));
        $this->assertTrue($DB->record_exists('rating', ['userid' => $data->user2->id, 'itemid' => $data->entry_11->id]));
        $this->assertFalse($DB->record_exists('rating', ['userid' => $data->user2->id, 'itemid' => $data->entry_12->id]));
        $this->assertTrue($DB->record_exists('rating', ['userid' => $data->user2->id, 'itemid' => $data->entry_13->id]));

        $this->assertCount(2, $DB->get_records('glossary_alias', ['entryid' => $data->entry_11->id]));
        $this->assertCount(0, $DB->get_records('glossary_alias', ['entryid' => $data->entry_12->id]));
        $this->assertCount(1, $DB->get_records('glossary_alias', ['entryid' => $data->entry_13->id]));
        $this->assertCount(1, $DB->get_records('glossary_alias', ['entryid' => $data->entry_21->id]));

        // Completion and grade updates are not happening for purging of deleted users, so skip those assertions.
        if ($targetuser->status !== $targetuser::STATUS_DELETED) {
            $this->assertCount(3, $DB->get_records('course_modules_completion', ['userid' => $data->user1->id]));
            $this->assertTrue($DB->record_exists('course_modules_completion', ['userid' => $data->user1->id, 'completionstate' => COMPLETION_COMPLETE, 'coursemoduleid' => $data->cm1->id]));
            $this->assertTrue($DB->record_exists('course_modules_completion', ['userid' => $data->user1->id, 'completionstate' => COMPLETION_INCOMPLETE, 'coursemoduleid' => $data->cm2->id]));
            $this->assertTrue($DB->record_exists('course_modules_completion', ['userid' => $data->user1->id, 'completionstate' => COMPLETION_COMPLETE, 'coursemoduleid' => $data->cm3->id]));
            $this->assertCount(1, $DB->get_records('course_modules_completion', ['userid' => $data->user2->id]));
            $this->assertTrue($DB->record_exists('course_modules_completion', ['userid' => $data->user2->id, 'completionstate' => COMPLETION_COMPLETE, 'coursemoduleid' => $data->cm1->id]));

            $this->assertCount(2, $DB->get_records('grade_grades', ['itemid' => $data->glossary1_grade_item_id]));
            $this->assertCount(1, $DB->get_records('grade_grades', ['itemid' => $data->glossary2_grade_item_id]));
            $this->assertCount(1, $DB->get_records('grade_grades', ['itemid' => $data->glossary3_grade_item_id]));
            $this->assertTrue($DB->record_exists('grade_grades', ['userid' => $data->user2->id, 'itemid' => $data->glossary1_grade_item_id, 'finalgrade' => 60]));
            $this->assertTrue($DB->record_exists('grade_grades', ['userid' => $data->user1->id, 'itemid' => $data->glossary1_grade_item_id,'finalgrade' => 70]));
            $this->assertTrue($DB->record_exists('grade_grades', ['userid' => $data->user1->id, 'itemid' => $data->glossary2_grade_item_id, 'finalgrade' => null]));
            $this->assertTrue($DB->record_exists('grade_grades', ['userid' => $data->user1->id, 'itemid' => $data->glossary3_grade_item_id, 'finalgrade' => 90]));
        }

        // 2 records expected per file.
        $this->assertCount(8, $DB->get_records('files', ['userid' => $data->user1->id]));
        $this->assertCount(4, $DB->get_records('files', ['userid' => $data->user1->id, 'itemid' => $data->entry_11->id]));
        $this->assertCount(4, $DB->get_records('files', ['userid' => $data->user1->id, 'itemid' => $data->entry_13->id]));
        $this->assertCount(4, $DB->get_records('files', ['userid' => $data->user2->id]));
    }

    /**
     * Test if data is correctly counted for active user.
     */
    public function test_count_active_user() {
        $data = $this->setup_data();
        $this->verify_count($data);
    }

    /**
     * Test if data is correctly counted for active user.
     */
    public function test_count_suspended_user() {
        $data = $this->setup_data(['suspended' => 1]);
        $this->verify_count($data);
    }

    /**
     * Test if data is correctly counted for deleted user.
     */
    public function test_count_deleted_user() {
        global $DB;

        // Set up data for active user, then delete user afterwards.
        $data = $this->setup_data();
        delete_user($DB->get_record('user', ['id' => $data->user1->id]));
        $data->user1 = $DB->get_record('user', ['id' => $data->user1->id]);
        $this->verify_count($data);
    }

    /**
     * Test if data is correctly counted.
     *
     * @param stdClass $data
     */
    private function verify_count(stdClass $data) {

        $targetuser = new target_user($data->user1);
        $this->assertEquals(3, entries::execute_count($targetuser, context_system::instance()));
        $this->assertEquals(1, entries::execute_count($targetuser, context_course::instance($data->course1->id)));
        $this->assertEquals(1, entries::execute_count($targetuser, context_course::instance($data->course2->id)));
        $this->assertEquals(1, entries::execute_count($targetuser, context_course::instance($data->course3->id)));
        $this->assertEquals(1, entries::execute_count($targetuser, context_coursecat::instance($data->category1->id)));
        $this->assertEquals(2, entries::execute_count($targetuser, context_coursecat::instance($data->category2->id)));
        $this->assertEquals(1, entries::execute_count($targetuser, context_module::instance($data->cm1->id)));
        $this->assertEquals(1, entries::execute_count($targetuser, context_module::instance($data->cm2->id)));
        $this->assertEquals(1, entries::execute_count($targetuser, context_module::instance($data->cm3->id)));

        entries::execute_purge($targetuser, context_system::instance());
        $this->assertEquals(0, entries::execute_count($targetuser, context_system::instance()));
    }

    /**
     * Test if data is correctly exported for active user.
     */
    public function test_export_active_user() {
        $data = $this->setup_data();
        $this->verify_export($data);
    }

    /**
     * Test if data is correctly exported for suspended user.
     */
    public function test_export_suspended_user() {
        $data = $this->setup_data(['suspended' => 1]);
        $this->verify_export($data);
    }

    /**
     * Test if data is correctly exported for deleted user.
     */
    public function test_export_deleted_user() {
        global $DB;

        // Set up data for active user, then delete user afterwards.
        $data = $this->setup_data();
        delete_user($DB->get_record('user', ['id' => $data->user1->id]));

        // Make sure the glossary entries are still there.
        $this->assertCount(3, $DB->get_records('glossary_entries', ['userid' => $data->user1->id]));

        $data->user1 = $DB->get_record('user', ['id' => $data->user1->id]);
        $this->verify_export($data);
    }

    /**
     * Test if data is correctly exported.
     *
     * @param stdClass $setup_data
     */
    private function verify_export(stdClass $setup_data) {
        $targetuser = new target_user($setup_data->user1);

        $export = entries::execute_export($targetuser, context_system::instance());
        $this->assert_export([$setup_data->entry_11, $setup_data->entry_12, $setup_data->entry_13], $export, $setup_data);

        $export = entries::execute_export($targetuser, context_course::instance($setup_data->course1->id));
        $this->assert_export([$setup_data->entry_11], $export, $setup_data);

        $export = entries::execute_export($targetuser, context_course::instance($setup_data->course2->id));
        $this->assert_export([$setup_data->entry_12], $export, $setup_data);

        $export = entries::execute_export($targetuser, context_coursecat::instance($setup_data->category1->id));
        $this->assert_export([$setup_data->entry_11], $export, $setup_data);

        $export = entries::execute_export($targetuser, context_coursecat::instance($setup_data->category2->id));
        $this->assert_export([$setup_data->entry_12, $setup_data->entry_13], $export, $setup_data);

        $export = entries::execute_export($targetuser, context_module::instance($setup_data->cm1->id));
        $this->assert_export([$setup_data->entry_11], $export, $setup_data);

        $export = entries::execute_export($targetuser, context_module::instance($setup_data->cm2->id));
        $this->assert_export([$setup_data->entry_12], $export, $setup_data);
    }

    /**
     * @param array $expected_entries
     * @param export $export
     * @param stdClass $setup_data
     */
    private function assert_export(array $expected_entries, export $export, stdClass $setup_data) {
        // Assert that the export contains the expected record ids.
        $this->assertCount(count($expected_entries), $export->data);

        // 2 files for each entry expected.
        $this->assertCount(2 * count($expected_entries), $export->files);

        foreach ($expected_entries as $expected_entry) {
            $this->assertArrayHasKey($expected_entry->id, $export->data);

            $export_entry = $export->data[$expected_entry->id];
            $this->assertEquals($expected_entry->glossaryid, $export_entry['glossaryid']);
            $this->assertEquals($expected_entry->concept, $export_entry['concept']);
            $this->assertEquals($expected_entry->definition, $export_entry['definition']);

            // Each entry should have one attachment and one inline (editor) file.
            $expected_entry_file = $setup_data->files['entry'][$expected_entry->id];
            $exported_file = $export->files[$expected_entry_file->get_id()];
            $this->assertEquals($expected_entry_file, $exported_file);

            $expected_attachment_file = $setup_data->files['attachment'][$expected_entry->id];
            $exported_file = $export->files[$expected_attachment_file->get_id()];
            $this->assertEquals($expected_attachment_file, $exported_file);
        }
    }
    
    /**
     * Test the case when an entry is moved (exported) from a secondary to a main glossary.
     */
    public function test_moved_entry() {
        global $DB;

        $data = $this->setup_data();

        // Add a main glossary to course1.
        $glossary_generator = self::getDataGenerator()->get_plugin_generator('mod_glossary');
        $mainglossary = $glossary_generator->create_instance(['course' => $data->course1->id, 'mainglossary' => 1]);

        // Move an entry to main glossary.

        // There is a bug about losing files when moving an entry, see TL-17394. When that gets fixed, this
        // test should be adjusted/extended accordingly. A method (e.g. export_entry_to_main_glossary()) should be
        // called here instead of DB manipulation and file assertions for user data purge/export should be added.
        $entry = $data->entry_11;
        $entry->glossaryid       = $mainglossary->id;
        $entry->sourceglossaryid = $data->glossary1->id;
        $DB->update_record('glossary_entries', $entry);

        $targetuser = new target_user($data->user1);

        // Verify it's included in the count.
        $this->assertEquals(3, entries::execute_count($targetuser, context_system::instance()));

        // Verify it's included in the export.
        $export = entries::execute_export($targetuser, context_system::instance());
        $this->assertCount(3, $export->data);
        foreach ([$data->entry_11, $data->entry_12, $data->entry_13] as $expected_entry) {
            $this->assertArrayHasKey($expected_entry->id, $export->data);

            $export_entry = $export->data[$expected_entry->id];
            $this->assertEquals($expected_entry->glossaryid, $export_entry['glossaryid']);
            $this->assertEquals($expected_entry->concept, $export_entry['concept']);
            $this->assertEquals($expected_entry->definition, $export_entry['definition']);
        }

        // Verify purging of moved entry.
        $status = entries::execute_purge($targetuser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        $this->assertFalse($DB->record_exists('glossary_entries', ['userid' => $data->user1->id, 'id' => $data->entry_11->id]));
    }
}
