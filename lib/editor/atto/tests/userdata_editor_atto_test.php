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
 * @author Maria Torres <maria.torres@totaralearning.com>
 * @package editor_atto
 * @category test
 */

use editor_atto\userdata\editor_atto;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * General tests for atto editor userdata.
 *
 * @group totara_userdata
 */
class editor_atto_userdata_editor_atto_testcase extends advanced_testcase {
    /**
     * set up tests
     */
    protected function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Test editor atto options.
     */
    public function test_editor_atto_options() {
        $contextlevels = editor_atto::get_compatible_context_levels();

        $this->assertCount(7, $contextlevels);
        $this->assertContains(CONTEXT_SYSTEM, $contextlevels);
        $this->assertContains(CONTEXT_COURSE, $contextlevels);
        $this->assertContains(CONTEXT_COURSECAT, $contextlevels);
        $this->assertContains(CONTEXT_MODULE, $contextlevels);
        $this->assertContains(CONTEXT_PROGRAM, $contextlevels);
        $this->assertContains(CONTEXT_USER, $contextlevels);
        $this->assertContains(CONTEXT_BLOCK, $contextlevels);

        $this->assertFalse(editor_atto::is_exportable());
        $this->assertTrue(editor_atto::is_countable());
        $this->assertTrue(editor_atto::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(editor_atto::is_purgeable(target_user::STATUS_DELETED));
        $this->assertTrue(editor_atto::is_purgeable(target_user::STATUS_SUSPENDED));
    }

    /**
     * Test data is correctly purged in system context.
     */
    public function test_purge_in_system_context() {
        global $DB;

        // Set up users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Create courses.
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        
        // Get contexts.
        $contextcourse1 = context_course::instance($course1->id);
        $contextcourse2 = context_course::instance($course2->id);

        // Create fake data in the editor_atto_autosave table so we have data to purge.
        $now = time();
        $this->create_record('id_summary_editor', $user1->id, $contextcourse1->id, 'This is a draft', $now);
        $this->create_record('id_summary_editor', $user1->id, $contextcourse2->id, 'This is a draft2', $now);
        $this->create_record('id_summary_editor', $user2->id, $contextcourse2->id, 'This is a draft3', $now);

        // Get the expected data before purging.
        $this->assertCount(2, $DB->get_records('editor_atto_autosave', ['userid' => $user1->id]));

        // Purge data in System context.
        $targetuser = new target_user($user1);
        $status = editor_atto::execute_purge($targetuser, context_system::instance());

        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        // Get the expected data after purging.
        $this->assertCount(0, $DB->get_records('editor_atto_autosave', ['userid' => $user1->id]));

        // Check if second user record is untouched.
        $this->assertCount(1, $DB->get_records('editor_atto_autosave', ['userid' => $user2->id]));
    }

    /**
     * Test data is correctly purged in course context.
     */
    public function test_purge_in_course_context() {
        global $DB;

        // Set up users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Create courses.
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        // Get contexts.
        $contextcourse1 = context_course::instance($course1->id);
        $contextcourse2 = context_course::instance($course2->id);

        // Create fake data in the editor_atto_autosave table so we have data to purge.
        $now = time();
        $this->create_record('id_summary_editor', $user1->id, $contextcourse1->id, 'This is a draft', $now);
        $this->create_record('id_summary_editor', $user1->id, $contextcourse2->id, '<p>This is a draft2</p>', $now);
        $this->create_record('id_summary_editor', $user2->id, $contextcourse2->id, '<p>This is a draft3</p>', $now);

        // Get the expected data before purging.
        $this->assertCount(2, $DB->get_records('editor_atto_autosave', ['userid' => $user1->id]));

        // Purge data in course context.
        $targetuser = new target_user($user1);
        $status = editor_atto::execute_purge($targetuser, $contextcourse2);

        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        // Get the expected data after purging.
        $user1records = $DB->get_records('editor_atto_autosave', ['userid' => $user1->id]);
        $firstrecord = reset($user1records);
        $this->assertCount(1, $user1records);
        $this->assertEquals($contextcourse1->id, $firstrecord->contextid);

        // Check the second user record is untouched.
        $this->assertCount(1, $DB->get_records('editor_atto_autosave', ['userid' => $user1->id]));
    }

    /**
     * Test data is correctly purged in category context.
     */
    public function test_purge_in_category_context() {
        global $DB;

        // Set up users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Create categories.
        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();

        // Create courses.
        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $category2->id]);

        // Get contexts.
        $contextcat1 = context_coursecat::instance($category1->id);
        $contextcat2 = context_coursecat::instance($category2->id);

        // Create fake data in the editor_atto_autosave table so we have data to purge.
        $now = time();
        $this->create_record('id_summary_editor', $user1->id, $contextcat1->id, 'This is a draft', $now);
        $this->create_record('id_summary_editor', $user1->id, $contextcat2->id, '<p>This is a draft2</p>', $now);
        $this->create_record('id_summary_editor', $user2->id, $contextcat2->id, '<p>This is a draft3</p>', $now);

        // Get the expected data before purging.
        $this->assertCount(2, $DB->get_records('editor_atto_autosave', ['userid' => $user1->id]));

        // Purge data in category context.
        $targetuser = new target_user($user1);
        $status = editor_atto::execute_purge($targetuser, $contextcat2);

        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        // Get the expected data.
        $user1records = $DB->get_records('editor_atto_autosave', ['userid' => $user1->id]);
        $firstrecord = reset($user1records);
        $this->assertCount(1, $user1records);
        $this->assertEquals($contextcat1->id, $firstrecord->contextid);

        // Check second user record is not affected.
        $this->assertCount(1, $DB->get_records('editor_atto_autosave', ['userid' => $user2->id]));
    }

    /**
     * Test data is correctly purged in module context.
     */
    public function test_purge_in_module_context() {
        global $DB;

        // Set up users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Create courses.
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        // Create modules.
        $seminar1 = $this->getDataGenerator()->create_module('facetoface', array('course' => $course1->id));
        $seminar2 = $this->getDataGenerator()->create_module('facetoface', array('course' => $course2->id));
        $certificate = $this->getDataGenerator()->create_module('certificate', array('course' => $course1->id));

        // Get contexts.
        $contextseminar1 = context_module::instance($seminar1->cmid);
        $contextseminar2 = context_module::instance($seminar2->cmid);
        $contextcertificate = context_module::instance($certificate->cmid);
        $contextcourse1 = context_course::instance($course1->id);
        $contextcourse2 = context_course::instance($course2->id);

        // Create fake data in the editor_atto_autosave table so we have data to purge.
        $now = time();
        $this->create_record('id_summary_editor', $user1->id, $contextcourse1->id, 'This is a draft', $now);
        $this->create_record('id_summary_editor', $user1->id, $contextcourse2->id, '<p>This is a draft2</p>', $now);
        $this->create_record('id_summary_editor', $user1->id, $contextseminar1->id, '<p>This is a draft seminar1 by user 1</p>', $now);
        $this->create_record('id_summary_editor', $user2->id, $contextseminar1->id, '<p>This is a draft seminar1 by user 2</p>', $now);
        $this->create_record('id_summary_editor', $user2->id, $contextseminar2->id, '<p>This is a draft seminar 2</p>', $now);
        $this->create_record('id_introeditor', $user1->id, $contextcertificate->id, '<p>This is a draft certificate</p>', $now);

        // Get the expected data before purging.
        $this->assertCount(4, $DB->get_records('editor_atto_autosave', ['userid' => $user1->id]));
        $this->assertCount(2, $DB->get_records('editor_atto_autosave', ['userid' => $user2->id]));

        // Purge data in module context.
        $targetuser = new target_user($user1);
        $status = editor_atto::execute_purge($targetuser, $contextseminar1);

        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        // Get the expected data after purging.
        $user1records = $DB->get_records('editor_atto_autosave', ['userid' => $user1->id]);
        $this->assertCount(3, $user1records);
        $this->assertFalse($DB->record_exists('editor_atto_autosave', ['userid' => $user1->id, 'contextid' => $contextseminar1->id]));

        // Check second user record is not affected.
        $this->assertCount(2, $DB->get_records('editor_atto_autosave', ['userid' => $user2->id]));
    }

    /**
     * Test data is correctly purged in program context.
     */
    public function test_purge_in_program_context() {
        global $DB;

        /** @var totara_program_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('totara_program');

        // Set up users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Create courses.
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        // Create programs.
        $program1 = $programgenerator->create_program();
        $program2 = $programgenerator->create_program();

        // Adding courses to the programs.
        $programgenerator->add_courses_and_courseset_to_program($program1, [[$course1, $course2]]);
        $programgenerator->add_courses_and_courseset_to_program($program2, [[$course1, $course3]]);

        // Get contexts.
        $contextprogram1 = context_program::instance($program1->id);
        $contextprogram2 = context_program::instance($program2->id);
        $contextcourse1 = context_course::instance($course1->id);
        $contextcourse2 = context_course::instance($course2->id);

        // Create fake data in the editor_atto_autosave table so we have data to purge.
        $now = time();
        $this->create_record('id_summary_editor', $user1->id, $contextcourse1->id, 'This is a draft', $now);
        $this->create_record('id_summary_editor', $user1->id, $contextcourse2->id, '<p>This is a draft2</p>', $now);
        $this->create_record('id_summary_editor', $user1->id, $contextprogram1->id, '<p>This is a draft prog1 by user 1</p>', $now);
        $this->create_record('id_endnote_editor', $user1->id, $contextprogram1->id, '<p>This is another draft prog1 by user 1</p>', $now);
        $this->create_record('id_summary_editor', $user1->id, $contextprogram2->id, '<p>This is a draft prog2 by user 1</p>', $now);
        $this->create_record('id_summary_editor', $user2->id, $contextprogram1->id, '<p>This is a draft prog1 by user 2</p>', $now);
        $this->create_record('id_summary_editor', $user2->id, $contextprogram2->id, '<p>This is a draft prog2 by user 2</p>', $now);
        $this->create_record('id_introeditor', $user1->id, context_user::instance($user1->id)->id, '<p>personal draft</p>', $now);

        // Get the expected data before purging.
        $this->assertCount(6, $DB->get_records('editor_atto_autosave', ['userid' => $user1->id]));
        $this->assertCount(2, $DB->get_records('editor_atto_autosave', ['userid' => $user2->id]));

        // Purge data in program context.
        $targetuser = new target_user($user1);
        $status = editor_atto::execute_purge($targetuser, $contextprogram1);

        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        // Get the expected data after purging.
        $this->assertEquals(4, $DB->count_records('editor_atto_autosave', ['userid' => $user1->id]));
        $this->assertFalse($DB->record_exists('editor_atto_autosave', ['userid' => $user1->id, 'contextid' => $contextprogram1->id]));

        // Check second user record is not affected.
        $this->assertCount(2, $DB->get_records('editor_atto_autosave', ['userid' => $user2->id]));
    }

    /**
     * Test data is correctly purged in block context.
     */
    public function test_purge_in_block_context() {
        global $DB;

        // Set up users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Create course.
        $course1 = $this->getDataGenerator()->create_course();
        $contextcourse1 = context_course::instance($course1->id);

        // Create blocks.
        $block1 = $this->getDataGenerator()->create_block('online_users', array('parentcontextid'=>$contextcourse1->id));
        $block2 = $this->getDataGenerator()->create_block('online_users', array('parentcontextid'=>$contextcourse1->id));

        // Get contexts.
        $contextblock1 = context_block::instance($block1->id);
        $contextblock2 = context_block::instance($block2->id);

        // Create fake data in the editor_atto_autosave table so we have data to purge.
        $now = time();
        $this->create_record('id_summary_editor', $user1->id, $contextblock1->id, 'Draft in block 1 - user1', $now);
        $this->create_record('id_endnote_editor', $user1->id, $contextblock1->id, 'Draft2 in block 1 - user1', $now);
        $this->create_record('id_description_editor', $user1->id, $contextblock1->id, 'Draft3 in block 1 - user1', $now);
        $this->create_record('id_summary_editor', $user1->id, $contextblock2->id, 'Draft in block 2 - user1', $now);
        $this->create_record('id_summary_editor', $user2->id, $contextblock1->id, 'Draft in block 1 - user2', $now);

        // Get the expected data before purging.
        $this->assertCount(4, $DB->get_records('editor_atto_autosave', ['userid' => $user1->id]));
        $this->assertCount(1, $DB->get_records('editor_atto_autosave', ['userid' => $user2->id]));

        // Purge data in block context.
        $targetuser = new target_user($user1);
        $status = editor_atto::execute_purge($targetuser, $contextblock1);

        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        // Get the expected data after purging.
        $this->assertEquals(1, $DB->count_records('editor_atto_autosave', ['userid' => $user1->id]));
        $this->assertFalse($DB->record_exists('editor_atto_autosave', ['userid' => $user1->id, 'contextid' => $contextblock1->id]));

        // Check second user record is not affected.
        $this->assertCount(1, $DB->get_records('editor_atto_autosave', ['userid' => $user2->id]));
    }

    /**
     * Test count
     */
    public function test_count() {
        /** @var totara_program_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('totara_program');

        // Set up users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();

        // Create courses.
        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $category2->id]);
        $course3 = $this->getDataGenerator()->create_course(['category' => $category2->id]);

        // Create modules.
        $seminar1 = $this->getDataGenerator()->create_module('facetoface', array('course' => $course1->id));
        $seminar2 = $this->getDataGenerator()->create_module('facetoface', array('course' => $course2->id));

        // Create programs and add courses to it.
        $program1 = $programgenerator->create_program();
        $program2 = $programgenerator->create_program();
        $programgenerator->add_courses_and_courseset_to_program($program1, [[$course1, $course2]]);
        $programgenerator->add_courses_and_courseset_to_program($program2, [[$course1, $course3]]);

        // Get contexts.
        $contextcat1 = context_coursecat::instance($category1->id);
        $contextcat2 = context_coursecat::instance($category2->id);
        $contextcourse1 = context_course::instance($course1->id);
        $contextcourse2 = context_course::instance($course2->id);
        $contextprogram1 = context_program::instance($program1->id);
        $contextprogram2 = context_program::instance($program2->id);
        $contextseminar1 = context_module::instance($seminar1->cmid);
        $contextseminar2 = context_module::instance($seminar2->cmid);

        // Create fake data in the editor_atto_autosave table so we have data to purge.
        $now = time();
        $this->create_record('id_summary_editor', $user1->id, $contextcourse1->id, 'draft in course 1 - user1', $now);
        $this->create_record('id_summary_editor', $user1->id, $contextcourse2->id, 'draft in course 2 - user1', $now);
        $this->create_record('id_summary_editor', $user2->id, $contextcourse2->id, 'draft in course 2 - user2', $now);
        $this->create_record('id_summary_editor', $user1->id, $contextprogram1->id, 'draft in prog 1 - user1', $now);
        $this->create_record('id_summary_editor', $user2->id, $contextprogram1->id, 'draft in prog 1 - user2', $now);
        $this->create_record('id_summary_editor', $user2->id, $contextprogram2->id, 'draft in prog 2 - user2', $now);
        $this->create_record('id_summary_editor', $user1->id, $contextseminar1->id, 'draft in seminar 1 - user1', $now);
        $this->create_record('id_endnote_editor', $user1->id, $contextseminar1->id, 'draft2 in seminar 1 - user1', $now);
        $this->create_record('id_endnote_editor', $user2->id, $contextseminar2->id, 'draft in seminar 2 - user2', $now);
        $this->create_record('id_summary_editor', $user1->id, $contextcat1->id, 'draft in category 1 - user1', $now);

        // Target user1.
        $targetuser = new target_user($user1);

        // Get count in the system context.
        $count = editor_atto::execute_count($targetuser, context_system::instance());
        $this->assertEquals(6, $count);

        // Get count for course context.
        $count = editor_atto::execute_count($targetuser, $contextcourse1);
        $this->assertEquals(1, $count);

        // Get count for category context.
        $count = editor_atto::execute_count($targetuser, $contextcat1);
        $this->assertEquals(1, $count);

        // Get count for course context that does not have any data.
        $count = editor_atto::execute_count($targetuser, context_course::instance($course3->id));
        $this->assertEquals(0, $count);

        // Get count for module context.
        $count = editor_atto::execute_count($targetuser, $contextseminar1);
        $this->assertEquals(2, $count);

        // Get count for program context.
        $count = editor_atto::execute_count($targetuser, $contextprogram1);
        $this->assertEquals(1, $count);
    }

    private function create_record($elementid, $userid, $contextid, $drafttext, $timemodified) {
        global $DB;

        $record = new stdClass();
        $record->elementid = $elementid;
        $record->userid = $userid;
        $record->pagehash = '';
        $record->contextid = $contextid;
        $record->drafttext = $drafttext;
        $record->pageinstance = '';
        $record->timemodified = !empty($timemodified) ? $timemodified : time();

        return $DB->insert_record('editor_atto_autosave', $record);
    }
}