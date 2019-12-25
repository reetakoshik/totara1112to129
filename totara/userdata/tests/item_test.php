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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_userdata
 * @category test
 */

use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;
use totara_userdata\userdata\testitem;
use totara_userdata\userdata\testitemminimal;
use totara_userdata\userdata\testitempurgetransaction;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the base item class.
 */
class totara_userdata_item_testcase extends advanced_testcase {
    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        require_once(__DIR__ . '/fixtures/testitem.php');
        require_once(__DIR__ . '/fixtures/testitemminimal.php');
        require_once(__DIR__ . '/fixtures/testitempurgetransaction.php');
    }

    public function test_extending_class() {
        $this->assertTrue(class_exists('totara_userdata\userdata\testitemminimal', false));
    }

    public function test_constants() {
        $this->assertSame(-1, item::RESULT_STATUS_SUCCESS);
        $this->assertSame(-2, item::RESULT_STATUS_ERROR);
        $this->assertSame(-3, item::RESULT_STATUS_SKIPPED);
        $this->assertSame(-4, item::RESULT_STATUS_CANCELLED);
        $this->assertSame(-5, item::RESULT_STATUS_TIMEDOUT);
    }

    public function test_get_component() {
        $this->assertSame('totara_userdata', testitemminimal::get_component());
        $this->assertSame('totara_userdata', testitem::get_component());
    }

    public function test_get_main_component() {
        $this->assertSame('totara_userdata', testitemminimal::get_main_component());
        $this->assertSame('core_user', testitem::get_main_component());
    }

    public function test_get_fullname_string() {
        $this->assertSame(['userdataitemtestitemminimal', 'totara_userdata'], testitemminimal::get_fullname_string());
        $this->assertSame(['repurge', 'totara_userdata'], testitem::get_fullname_string());
    }

    public function test_get_fullname() {
        $this->assertSame('[[userdataitemtestitemminimal]]', testitemminimal::get_fullname());
        $this->assertDebuggingCalled();
        $this->assertSame('Reapply purging', testitem::get_fullname());
    }

    public function test_help_available() {
        $this->assertFalse(testitemminimal::help_available());
        $this->assertTrue(testitem::help_available());
    }

    public function test_get_sortorder() {
        $this->assertSame(10000, testitemminimal::get_sortorder());
        $this->assertSame(666, testitem::get_sortorder());
    }

    public function test_get_compatible_context_levels() {
        $expected = array(CONTEXT_SYSTEM);
        $this->assertSame($expected, testitemminimal::get_compatible_context_levels());
        $expected = array(CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE);
        $this->assertSame($expected, testitem::get_compatible_context_levels());
    }

    public function test_is_compatible_context_level() {
        $this->resetAfterTest();

        $syscontext = context_system::instance();

        $user = $this->getDataGenerator()->create_user();
        $usercontext = context_user::instance($user->id);

        $category = $this->getDataGenerator()->create_category();
        $categorycontext = context_coursecat::instance($category->id);

        $course = $this->getDataGenerator()->create_course();
        $coursecontext = context_course::instance($course->id);

        $forum = $this->getDataGenerator()->create_module('forum', array('course' => $course->id));
        $cm = get_coursemodule_from_instance('forum', $forum->id);
        $modcontext = context_module::instance($cm->id);

        $block = $this->getDataGenerator()->create_block('online_users', array('parentcontextid' => $modcontext->id));
        $blockcontext = context_block::instance($block->id);

        $this->assertTrue(testitemminimal::is_compatible_context_level($syscontext->contextlevel));
        $this->assertFalse(testitemminimal::is_compatible_context_level($usercontext->contextlevel));
        $this->assertFalse(testitemminimal::is_compatible_context_level($categorycontext->contextlevel));
        $this->assertFalse(testitemminimal::is_compatible_context_level($coursecontext->contextlevel));
        $this->assertFalse(testitemminimal::is_compatible_context_level($modcontext->contextlevel));
        $this->assertFalse(testitemminimal::is_compatible_context_level($blockcontext->contextlevel));

        $this->assertTrue(testitem::is_compatible_context_level($syscontext->contextlevel));
        $this->assertFalse(testitem::is_compatible_context_level($usercontext->contextlevel));
        $this->assertTrue(testitem::is_compatible_context_level($categorycontext->contextlevel));
        $this->assertTrue(testitem::is_compatible_context_level($coursecontext->contextlevel));
        $this->assertFalse(testitem::is_compatible_context_level($modcontext->contextlevel));
        $this->assertFalse(testitem::is_compatible_context_level($blockcontext->contextlevel));
    }

    public function test_is_purgeable() {
        $this->assertFalse(testitemminimal::is_purgeable(\totara_userdata\userdata\target_user::STATUS_ACTIVE));
        $this->assertTrue(testitem::is_purgeable(\totara_userdata\userdata\target_user::STATUS_ACTIVE));
        $this->assertTrue(testitempurgetransaction::is_purgeable(\totara_userdata\userdata\target_user::STATUS_ACTIVE));
    }

    public function test_execute_purge() {
        $this->resetAfterTest();

        $syscontext = context_system::instance();
        $user = $this->getDataGenerator()->create_user();
        $deleteduser = $this->getDataGenerator()->create_user(array('deleted' => 1));
        $usercontext = context_user::instance($user->id);
        $target = new target_user($user);
        $targetdeleted = new target_user($deleteduser);

        $result = testitemminimal::execute_purge($target, $syscontext);
        $this->assertSame(testitem::RESULT_STATUS_ERROR, $result);

        $result = testitem::execute_purge($target, $syscontext);
        $this->assertSame(testitem::RESULT_STATUS_SUCCESS, $result);

        $result = testitem::execute_purge($target, $usercontext);
        $this->assertSame(testitem::RESULT_STATUS_ERROR, $result);

        $result = testitem::execute_purge($targetdeleted, $syscontext);
        $this->assertSame(testitem::RESULT_STATUS_ERROR, $result);
    }

    public function test_execute_purge_with_transactions() {
        global $DB;
        $this->resetAfterTest();

        $syscontext = context_system::instance();
        $user = $this->getDataGenerator()->create_user();
        $usercontext = context_user::instance($user->id);
        $target = new target_user($user);

        $this->assertFalse($DB->is_transaction_started());
        $outertrans = $DB->start_delegated_transaction();
        try {
            testitem::execute_purge($target, $syscontext);
            $this->fail('transaction exception expected');
        } catch (dml_transaction_exception $ex) {
            $this->assertSame('Database transaction error (This code can not be excecuted in transaction)', $ex->getMessage());
        }
        $this->assertTrue($DB->is_transaction_started());
        $DB->force_transaction_rollback();

        $this->assertFalse($DB->is_transaction_started());
        try {
            testitempurgetransaction::execute_purge($target, $syscontext);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Transaction was not committed in purge() method', $ex->getMessage());
        }
        $this->assertFalse($DB->is_transaction_started());
    }

    public function test_is_exportable() {
        $this->assertFalse(testitemminimal::is_exportable());
        $this->assertTrue(testitem::is_exportable());
    }

    public function test_execute_export() {
        $this->resetAfterTest();

        $syscontext = context_system::instance();
        $user = $this->getDataGenerator()->create_user();
        $usercontext = context_user::instance($user->id);
        $target = new target_user($user);

        $result = testitemminimal::execute_export($target, $syscontext);
        $this->assertSame(testitem::RESULT_STATUS_ERROR, $result);

        $result = testitem::execute_export($target, $syscontext);
        $this->assertInstanceOf('totara_userdata\userdata\export', $result);

        $result = testitem::execute_export($target, $usercontext);
        $this->assertSame(testitem::RESULT_STATUS_ERROR, $result);
    }

    public function test_is_countable() {
        $this->assertFalse(testitemminimal::is_countable());
        $this->assertTrue(testitem::is_countable());
    }

    public function test_execute_count() {
        $this->resetAfterTest();

        $syscontext = context_system::instance();
        $user = $this->getDataGenerator()->create_user();
        $usercontext = context_user::instance($user->id);
        $target = new target_user($user);

        $result = testitemminimal::execute_count($target, $syscontext);
        $this->assertSame(testitem::RESULT_STATUS_ERROR, $result);

        $result = testitem::execute_count($target, $syscontext);
        $this->assertSame(0, $result);

        $result = testitem::execute_count($target, $usercontext);
        $this->assertSame(testitem::RESULT_STATUS_ERROR, $result);
    }

    public function test_get_courses_context_join() {
        global $DB;
        $this->resetAfterTest();

        $syscontext = context_system::instance();

        $user = $this->getDataGenerator()->create_user();
        $usercontext = context_user::instance($user->id);

        $category1 = $this->getDataGenerator()->create_category();
        $categorycontext1 = context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category();
        $categorycontext2 = context_coursecat::instance($category2->id);

        $course1a = $this->getDataGenerator()->create_course(array('category' => $category1->id));
        $coursecontext1a = context_course::instance($course1a->id);
        $course1b = $this->getDataGenerator()->create_course(array('category' => $category1->id));
        $coursecontext1b = context_course::instance($course1a->id);
        $course2a = $this->getDataGenerator()->create_course(array('category' => $category2->id));
        $coursecontext2a = context_course::instance($course2a->id);
        $course2b = $this->getDataGenerator()->create_course(array('category' => $category2->id));
        $coursecontext2b = context_course::instance($course2b->id);
        $course2c = $this->getDataGenerator()->create_course(array('category' => $category2->id));
        $coursecontext2c = context_course::instance($course2c->id);

        $forum = $this->getDataGenerator()->create_module('forum', array('course' => $course1a->id));
        $cm = get_coursemodule_from_instance('forum', $forum->id);
        $modcontext = context_module::instance($cm->id);

        $block = $this->getDataGenerator()->create_block('online_users', array('parentcontextid' => $modcontext->id));
        $blockcontext = context_block::instance($block->id);

        $join = item::get_courses_context_join($syscontext, 'c.id');
        $courses = $DB->get_records_sql("SELECT c.* FROM {course} c $join");
        $this->assertCount(6, $courses);

        $join = item::get_courses_context_join($categorycontext1, 'c.id', 'xxx');
        $courses = $DB->get_records_sql("SELECT c.* FROM {course} c $join");
        $this->assertCount(2, $courses);
        $this->assertArrayHasKey($course1a->id, $courses);
        $this->assertArrayHasKey($course1b->id, $courses);

        $join = item::get_courses_context_join($coursecontext1a, 'c.id', 'xxx');
        $courses = $DB->get_records_sql("SELECT c.* FROM {course} c $join");
        $this->assertCount(1, $courses);
        $this->assertArrayHasKey($course1a->id, $courses);

        $join = item::get_courses_context_join($modcontext, 'c.id', 'xxx');
        $courses = $DB->get_records_sql("SELECT c.* FROM {course} c $join");
        $this->assertCount(0, $courses);

        $join = item::get_courses_context_join($usercontext, 'c.id', 'xxx');
        $courses = $DB->get_records_sql("SELECT c.* FROM {course} c $join");
        $this->assertCount(0, $courses);

        $join = item::get_courses_context_join($modcontext, 'c.id', 'xxx');
        $courses = $DB->get_records_sql("SELECT c.* FROM {course} c $join");
        $this->assertCount(0, $courses);
    }

    public function test_get_activities_context_join() {
        global $DB;
        $this->resetAfterTest();

        $syscontext = context_system::instance();

        $user = $this->getDataGenerator()->create_user();
        $usercontext = context_user::instance($user->id);

        $category1 = $this->getDataGenerator()->create_category();
        $categorycontext1 = context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category();
        $categorycontext2 = context_coursecat::instance($category2->id);

        $course1 = $this->getDataGenerator()->create_course(array('category' => $category1->id));
        $coursecontext1 = context_course::instance($course1->id);
        $course2 = $this->getDataGenerator()->create_course(array('category' => $category2->id));
        $coursecontext2 = context_course::instance($course2->id);

        $forum1a = $this->getDataGenerator()->create_module('forum', array('course' => $course1->id));
        $cm1a = get_coursemodule_from_instance('forum', $forum1a->id);
        $modcontext1a = context_module::instance($cm1a->id);
        $forum1b = $this->getDataGenerator()->create_module('forum', array('course' => $course1->id));
        $cm1b = get_coursemodule_from_instance('forum', $forum1b->id);
        $modcontext1b = context_module::instance($cm1b->id);
        $glossary1c = $this->getDataGenerator()->create_module('glossary', array('course' => $course1->id));
        $cm1c = get_coursemodule_from_instance('glossary', $glossary1c->id);
        $modcontext1c = context_module::instance($cm1c->id);

        $forum2a = $this->getDataGenerator()->create_module('forum', array('course' => $course2->id));
        $cm2a = get_coursemodule_from_instance('forum', $forum2a->id);
        $modcontext2a = context_module::instance($cm2a->id);
        $forum2b = $this->getDataGenerator()->create_module('forum', array('course' => $course2->id));
        $cm2b = get_coursemodule_from_instance('forum', $forum2b->id);
        $modcontext2b = context_module::instance($cm2b->id);

        $block = $this->getDataGenerator()->create_block('online_users', array('parentcontextid' => $modcontext1a->id));
        $blockcontext = context_block::instance($block->id);

        $join = item::get_activities_context_join($syscontext, 'cm.id');
        $cms = $DB->get_records_sql("SELECT cm.* FROM {course_modules} cm $join");
        $this->assertCount(5, $cms);

        $join = item::get_activities_context_join($categorycontext1, 'cm.id', 'xxx');
        $cms = $DB->get_records_sql("SELECT cm.* FROM {course_modules} cm $join");
        $this->assertCount(3, $cms);
        $this->assertArrayHasKey($cm1a->id, $cms);
        $this->assertArrayHasKey($cm1b->id, $cms);
        $this->assertArrayHasKey($cm1c->id, $cms);

        $join = item::get_activities_context_join($coursecontext1, 'cm.id');
        $cms = $DB->get_records_sql("SELECT cm.* FROM {course_modules} cm $join");
        $this->assertCount(3, $cms);
        $this->assertArrayHasKey($cm1a->id, $cms);
        $this->assertArrayHasKey($cm1b->id, $cms);
        $this->assertArrayHasKey($cm1c->id, $cms);

        $join = item::get_activities_context_join($modcontext1a, 'cm.id');
        $cms = $DB->get_records_sql("SELECT cm.* FROM {course_modules} cm $join");
        $this->assertCount(1, $cms);
        $this->assertArrayHasKey($cm1a->id, $cms);

        $join = item::get_activities_context_join($blockcontext, 'cm.id');
        $cms = $DB->get_records_sql("SELECT cm.* FROM {course_modules} cm $join");
        $this->assertCount(0, $cms);

        $join = item::get_activities_context_join($usercontext, 'cm.id');
        $cms = $DB->get_records_sql("SELECT cm.* FROM {course_modules} cm $join");
        $this->assertCount(0, $cms);
    }

    public function test_get_activities_join() {
        global $DB;
        $this->resetAfterTest();

        $syscontext = context_system::instance();

        $user = $this->getDataGenerator()->create_user();
        $usercontext = context_user::instance($user->id);

        $category1 = $this->getDataGenerator()->create_category();
        $categorycontext1 = context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category();
        $categorycontext2 = context_coursecat::instance($category2->id);

        $course1 = $this->getDataGenerator()->create_course(array('category' => $category1->id));
        $coursecontext1 = context_course::instance($course1->id);
        $course2 = $this->getDataGenerator()->create_course(array('category' => $category2->id));
        $coursecontext2 = context_course::instance($course2->id);

        $forum1a = $this->getDataGenerator()->create_module('forum', array('course' => $course1->id));
        $cm1a = get_coursemodule_from_instance('forum', $forum1a->id);
        $modcontext1a = context_module::instance($cm1a->id);
        $forum1b = $this->getDataGenerator()->create_module('forum', array('course' => $course1->id));
        $cm1b = get_coursemodule_from_instance('forum', $forum1b->id);
        $modcontext1b = context_module::instance($cm1b->id);
        $glossary1c = $this->getDataGenerator()->create_module('glossary', array('course' => $course1->id));
        $cm1c = get_coursemodule_from_instance('glossary', $glossary1c->id);
        $modcontext1c = context_module::instance($cm1c->id);

        $forum2a = $this->getDataGenerator()->create_module('forum', array('course' => $course2->id));
        $cm2a = get_coursemodule_from_instance('forum', $forum2a->id);
        $modcontext2a = context_module::instance($cm2a->id);
        $forum2b = $this->getDataGenerator()->create_module('forum', array('course' => $course2->id));
        $cm2b = get_coursemodule_from_instance('forum', $forum2b->id);
        $modcontext2b = context_module::instance($cm2b->id);

        $block = $this->getDataGenerator()->create_block('online_users', array('parentcontextid' => $modcontext1a->id));
        $blockcontext = context_block::instance($block->id);

        $join = item::get_activities_join($syscontext, 'forum', 'f.id');
        $forums = $DB->get_records_sql("SELECT f.* FROM {forum} f $join");
        $this->assertCount(4, $forums);

        $join = item::get_activities_join($categorycontext1, 'forum', 'f.id', 'activity', 'coursemod', 'mods', 'xxx');
        $forums = $DB->get_records_sql("SELECT f.* FROM {forum} f $join");
        $this->assertCount(2, $forums);
        $this->assertArrayHasKey($forum1a->id, $forums);
        $this->assertArrayHasKey($forum1b->id, $forums);

        $join = item::get_activities_join($coursecontext1, 'forum', 'f.id');
        $forums = $DB->get_records_sql("SELECT f.* FROM {forum} f $join");
        $this->assertCount(2, $forums);
        $this->assertArrayHasKey($forum1a->id, $forums);
        $this->assertArrayHasKey($forum1b->id, $forums);

        $join = item::get_activities_join($modcontext1a, 'forum', 'f.id');
        $forums = $DB->get_records_sql("SELECT f.* FROM {forum} f $join");
        $this->assertCount(1, $forums);
        $this->assertArrayHasKey($forum1a->id, $forums);

        $join = item::get_activities_join($blockcontext, 'forum', 'f.id');
        $forums = $DB->get_records_sql("SELECT f.* FROM {forum} f $join");
        $this->assertCount(0, $forums);

        $join = item::get_activities_join($usercontext, 'forum', 'f.id');
        $forums = $DB->get_records_sql("SELECT f.* FROM {forum} f $join");
        $this->assertCount(0, $forums);
    }

}