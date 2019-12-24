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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package block_comments
 */

namespace block_comments\userdata;

use advanced_testcase;
use context;
use context_course;
use context_coursecat;
use context_helper;
use context_system;
use context_user;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/comment/lib.php');

/**
 * Test purging, exporting and counting of username
 *
 * @group totara_userdata
 */
class block_comments_userdata_username_testcase extends advanced_testcase {

    /**
     * test compatible context levels
     */
    public function test_compatible_context_levels() {
        $expectedcontextlevels = [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE];
        $actualcontextlevels = comments::get_compatible_context_levels();
        sort($actualcontextlevels);
        $this->assertEquals($expectedcontextlevels, $actualcontextlevels);
    }

    /**
     * Testing abilities, is_purgeable|countable|exportable()
     */
    public function test_abilities() {
        $this->assertTrue(comments::is_countable());
        $this->assertTrue(comments::is_exportable());
        $this->assertTrue(comments::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(comments::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(comments::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * Create fixtures for our tests.
     */
    private function create_fixtures() {
        $this->resetAfterTest(true);

        $fixtures = new class() {
            /** @var target_user */
            public $user, $controluser;
            /** @var \stdClass */
            public $category1, $category2;
            /** @var \stdClass */
            public $course1, $course2, $course3;
            /** @var \stdClass */
            public $comment1, $comment2, $comment3, $comment4, $comment5, $comment6;
        };

        $fixtures->category1 = $this->getDataGenerator()->create_category();
        $fixtures->category2 = $this->getDataGenerator()->create_category();
        $fixtures->course1 = $this->getDataGenerator()->create_course(['category' => $fixtures->category1->id]);
        $fixtures->course2 = $this->getDataGenerator()->create_course(['category' => $fixtures->category2->id]);
        $fixtures->course3 = $this->getDataGenerator()->create_course(['category' => $fixtures->category2->id]);

        $fixtures->user = new target_user($this->getDataGenerator()->create_user(['username' => 'user1']));
        $fixtures->controluser = new target_user($this->getDataGenerator()->create_user(['username' => 'controluser']));

        $fixtures->comment1 = $this->create_system_comment($fixtures->user, 'test system comment 1');
        $fixtures->comment2 = $this->create_system_comment($fixtures->user, 'test system comment 2');
        $fixtures->comment3 = $this->create_user_comment($fixtures->user, 'test user comment 1');
        $fixtures->comment4 = $this->create_course_comment($fixtures->user, $fixtures->course1->id, 'test course comment 1');
        $fixtures->comment5 = $this->create_course_comment($fixtures->user, $fixtures->course2->id, 'test course comment 2');
        $fixtures->comment6 = $this->create_course_comment($fixtures->user, $fixtures->course3->id, 'test course comment 3');

        $this->create_system_comment($fixtures->controluser, 'test system comment 1');
        $this->create_system_comment($fixtures->controluser, 'test system comment 2');
        $this->create_user_comment($fixtures->controluser, 'test user comment 1');
        $this->create_course_comment($fixtures->controluser, $fixtures->course1->id, 'test course comment 1');
        $this->create_course_comment($fixtures->controluser, $fixtures->course2->id, 'test course comment 2');
        $this->create_course_comment($fixtures->controluser, $fixtures->course3->id, 'test course comment 2');

        return $fixtures;
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge_system_context() {
        global $DB;

        $fixtures = $this->create_fixtures();

        // Purge active user.
        $result = comments::execute_purge($fixtures->user, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $this->assertEmpty($DB->get_records('comments', ['userid' => $fixtures->user->id]));

        // Control user must not be affected.
        $this->assertEquals(6, $DB->count_records('comments', ['userid' => $fixtures->controluser->id]));
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge_system_context_suspended_user() {
        global $DB;

        $fixtures = $this->create_fixtures();
        $fixtures->user = new target_user($this->suspend_user($fixtures->user->id));

        // Purge active user.
        $result = comments::execute_purge($fixtures->user, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $this->assertEmpty($DB->get_records('comments', ['userid' => $fixtures->user->id]));

        // Control user must not be affected.
        $this->assertEquals(6, $DB->count_records('comments', ['userid' => $fixtures->controluser->id]));
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge_system_context_deleted_user() {
        global $DB;

        $fixtures = $this->create_fixtures();
        $fixtures->user = new target_user($this->delete_user($fixtures->user->id));

        // Purge active user.
        $result = comments::execute_purge($fixtures->user, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $this->assertEmpty($DB->get_records('comments', ['userid' => $fixtures->user->id]));

        // Control user must not be affected.
        $this->assertEquals(6, $DB->count_records('comments', ['userid' => $fixtures->controluser->id]));
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge_coursecat_context1() {
        global $DB;

        $fixtures = $this->create_fixtures();

        // Purge active user.
        $result = comments::execute_purge($fixtures->user, context_coursecat::instance($fixtures->category1->id));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $this->assertNotEmpty($DB->get_records('comments', ['id' => $fixtures->comment1->id]));
        $this->assertNotEmpty($DB->get_records('comments', ['id' => $fixtures->comment2->id]));
        $this->assertNotEmpty($DB->get_records('comments', ['id' => $fixtures->comment3->id]));
        $this->assertEmpty($DB->get_records('comments', ['id' => $fixtures->comment4->id]));
        $this->assertNotEmpty($DB->get_records('comments', ['id' => $fixtures->comment5->id]));
        $this->assertNotEmpty($DB->get_records('comments', ['id' => $fixtures->comment6->id]));

        // Control user must not be affected.
        $this->assertEquals(6, $DB->count_records('comments', ['userid' => $fixtures->controluser->id]));
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge_coursecat_context2() {
        global $DB;

        $fixtures = $this->create_fixtures();

        // Purge active user.
        $result = comments::execute_purge($fixtures->user, context_coursecat::instance($fixtures->category2->id));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $this->assertNotEmpty($DB->get_records('comments', ['id' => $fixtures->comment1->id]));
        $this->assertNotEmpty($DB->get_records('comments', ['id' => $fixtures->comment2->id]));
        $this->assertNotEmpty($DB->get_records('comments', ['id' => $fixtures->comment3->id]));
        $this->assertNotEmpty($DB->get_records('comments', ['id' => $fixtures->comment4->id]));
        $this->assertEmpty($DB->get_records('comments', ['id' => $fixtures->comment5->id]));
        $this->assertEmpty($DB->get_records('comments', ['id' => $fixtures->comment6->id]));

        // Control user must not be affected.
        $this->assertEquals(6, $DB->count_records('comments', ['userid' => $fixtures->controluser->id]));
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge_course_context1() {
        global $DB;

        $fixtures = $this->create_fixtures();

        // Purge active user.
        $result = comments::execute_purge($fixtures->user, context_course::instance($fixtures->course2->id));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $this->assertNotEmpty($DB->get_records('comments', ['id' => $fixtures->comment1->id]));
        $this->assertNotEmpty($DB->get_records('comments', ['id' => $fixtures->comment2->id]));
        $this->assertNotEmpty($DB->get_records('comments', ['id' => $fixtures->comment3->id]));
        $this->assertNotEmpty($DB->get_records('comments', ['id' => $fixtures->comment4->id]));
        $this->assertEmpty($DB->get_records('comments', ['id' => $fixtures->comment5->id]));
        $this->assertNotEmpty($DB->get_records('comments', ['id' => $fixtures->comment6->id]));

        // Control user must not be affected.
        $this->assertEquals(6, $DB->count_records('comments', ['userid' => $fixtures->controluser->id]));
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge_course_context2() {
        global $DB;

        $fixtures = $this->create_fixtures();

        // Purge active user.
        $result = comments::execute_purge($fixtures->user, context_course::instance($fixtures->course3->id));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $this->assertNotEmpty($DB->get_records('comments', ['id' => $fixtures->comment1->id]));
        $this->assertNotEmpty($DB->get_records('comments', ['id' => $fixtures->comment2->id]));
        $this->assertNotEmpty($DB->get_records('comments', ['id' => $fixtures->comment3->id]));
        $this->assertNotEmpty($DB->get_records('comments', ['id' => $fixtures->comment4->id]));
        $this->assertNotEmpty($DB->get_records('comments', ['id' => $fixtures->comment5->id]));
        $this->assertEmpty($DB->get_records('comments', ['id' => $fixtures->comment6->id]));

        // Control user must not be affected.
        $this->assertEquals(6, $DB->count_records('comments', ['userid' => $fixtures->controluser->id]));
    }

    /**
     * test if data is correctly counted
     */
    public function test_count() {
        $fixtures = $this->create_fixtures();

        // Do the count.
        $result = comments::execute_count($fixtures->user, context_system::instance());
        $this->assertEquals(6, $result);

        $result = comments::execute_count($fixtures->user, context_coursecat::instance($fixtures->category1->id));
        $this->assertEquals(1, $result);

        $result = comments::execute_count($fixtures->user, context_coursecat::instance($fixtures->category2->id));
        $this->assertEquals(2, $result);

        $result = comments::execute_count($fixtures->user, context_course::instance($fixtures->course1->id));
        $this->assertEquals(1, $result);

        $result = comments::execute_count($fixtures->user, context_course::instance($fixtures->course2->id));
        $this->assertEquals(1, $result);

        $result = comments::execute_count($fixtures->user, context_course::instance($fixtures->course3->id));
        $this->assertEquals(1, $result);

        // Purge data.
        comments::execute_purge($fixtures->user, context_system::instance());

        $result = comments::execute_count($fixtures->user, context_system::instance());
        $this->assertEquals(0, $result);
    }


    /**
     * test if data is correctly counted
     */
    public function test_export() {
        $fixtures = $this->create_fixtures();

        // Export data.
        $result = comments::execute_export($fixtures->user, context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(6, $result->data);

        $commentids = array_column($result->data, 'id');
        $this->assertContains($fixtures->comment1->id, $commentids);
        $this->assertContains($fixtures->comment2->id, $commentids);
        $this->assertContains($fixtures->comment3->id, $commentids);
        $this->assertContains($fixtures->comment4->id, $commentids);
        $this->assertContains($fixtures->comment5->id, $commentids);
        $this->assertContains($fixtures->comment6->id, $commentids);

        $result = comments::execute_export($fixtures->user, context_coursecat::instance($fixtures->category1->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(1, $result->data);

        $commentids = array_column($result->data, 'id');
        $this->assertContains($fixtures->comment4->id, $commentids);

        $result = comments::execute_export($fixtures->user, context_coursecat::instance($fixtures->category2->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(2, $result->data);

        $commentids = array_column($result->data, 'id');
        $this->assertContains($fixtures->comment5->id, $commentids);
        $this->assertContains($fixtures->comment6->id, $commentids);

        $result = comments::execute_export($fixtures->user, context_course::instance($fixtures->course1->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(1, $result->data);

        $commentids = array_column($result->data, 'id');
        $this->assertContains($fixtures->comment4->id, $commentids);

        $result = comments::execute_export($fixtures->user, context_course::instance($fixtures->course2->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(1, $result->data);

        $commentids = array_column($result->data, 'id');
        $this->assertContains($fixtures->comment5->id, $commentids);

        $result = comments::execute_export($fixtures->user, context_course::instance($fixtures->course3->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(1, $result->data);

        $commentids = array_column($result->data, 'id');
        $this->assertContains($fixtures->comment6->id, $commentids);
    }

    /**
     * @param target_user $user
     * @param string $content
     * @return \stdClass
     */
    private function create_system_comment(target_user $user, string $content): \stdClass {
        $context = context_system::instance();
        return $this->create_comment($user, $context, $content);
    }

    /**
     * @param target_user $user
     * @param string $content
     * @return \stdClass
     */
    private function create_user_comment(target_user $user, string $content): \stdClass {
        $context = context_user::instance($user->id);
        return $this->create_comment($user, $context, $content);
    }

    /**
     * @param target_user $user
     * @param int $courseid
     * @param string $content
     * @return \stdClass
     */
    private function create_course_comment(target_user $user, int $courseid, string $content): \stdClass {
        $context = context_course::instance($courseid);
        return $this->create_comment($user, $context, $content);
    }

    /**
     * @param \stdClass $user
     * @param context $context
     * @param string $content
     * @return \stdClass
     */
    private function create_comment(\stdClass $user, context $context, string $content): \stdClass {
        global $USER;

        $olduser = $USER;
        $this->setUser($user->id);

        $commentdata = [
            'context' => $context,
            'component' => 'block_comments',
            'itemid' => 0,
            'area' => 'page_comments'
        ];
        $comment = new \comment((object)$commentdata);
        $comment->set_post_permission(true);
        $newcomment = $comment->add($content);

        $this->setUser($olduser);

        return $newcomment;
    }

    /**
     * DO NOT COPY THIS TO PRODUCTION CODE!
     *
     * See user/action.php
     *
     * @param int $userid
     * @return \stdClass The updated user object.
     */
    private function suspend_user(int $userid): \stdClass {
        global $DB;
        // Note that we don't properly delete the user, in fact we just simulate it.
        $DB->set_field('user', 'suspended', '1', ['id' => $userid]);
        return $DB->get_record('user', ['id' => $userid]);
    }

    /**
     * DO NOT COPY THIS TO PRODUCTION CODE!
     *
     * See user/action.php
     *
     * @param int $userid
     * @return \stdClass The updated user object.
     */
    private function delete_user(int $userid): \stdClass {
        global $DB;
        // Note that we don't properly delete the user, in fact we just simulate it.
        $DB->set_field('user', 'deleted', '1', ['id' => $userid]);
        context_helper::delete_instance(CONTEXT_USER, $userid);
        return $DB->get_record('user', ['id' => $userid]);
    }

}