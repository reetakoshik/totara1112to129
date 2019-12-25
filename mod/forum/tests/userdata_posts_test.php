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
 * @package mod_forum
 */

namespace mod_forum\userdata;

use advanced_testcase;
use context_course;
use context_coursecat;
use context_module;
use context_system;
use file_storage;
use mod_forum_generator;
use stdClass;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_userdata
 */
class mod_forum_userdata_posts_testcase extends advanced_testcase {

    /**
     * @var stdClass
     */
    private $user1, $user2;

    /**
     * @var stdClass
     */
    private $cat1, $cat2;

    /**
     * @var stdClass
     */
    private $course1, $course2;

    /**
     * @var stdClass
     */
    private $forum1, $forum2, $forum3;

    /**
     * @var mod_forum_generator
     */
    private $generator;

    /**
     * @var file_storage
     */
    private $fs;

    /**
     * Prepare general fixtures for the following tests
     */
    protected function setUp() {
        parent::setUp();

        global $CFG;

        $this->resetAfterTest(true);

        // We must clear the subscription caches. This has to be done both before each test, and after in case of other
        // tests using these functions.
        \mod_forum\subscriptions::reset_forum_cache();

        // PREPARE GENERAL DATA.

        // Set up users.
        $this->user1 = $this->getDataGenerator()->create_user();
        $this->user2 = $this->getDataGenerator()->create_user();

        // Create course categories.
        $this->cat1 = self::getDataGenerator()->create_category();
        $this->cat2 = self::getDataGenerator()->create_category();
        // Create courses to add the forum to.
        $this->course1 = self::getDataGenerator()->create_course(['category' => $this->cat1->id]);
        $this->course2 = self::getDataGenerator()->create_course(['category' => $this->cat2->id]);

        // Prepare forums.
        $record = new stdClass();
        $record->course = $this->course1->id;
        $this->forum1 = self::getDataGenerator()->create_module('forum', $record);
        $this->forum2 = self::getDataGenerator()->create_module('forum', $record);
        $record->course = $this->course2->id;
        $this->forum3 = self::getDataGenerator()->create_module('forum', $record);

        $this->generator = self::getDataGenerator()->get_plugin_generator('mod_forum');

        $this->fs = get_file_storage();
    }

    /**
     * Clear any properties
     */
    protected function tearDown() {
        // We must clear the subscription caches. This has to be done both before each test, and after in case of other
        // tests using these functions.
        \mod_forum\subscriptions::reset_forum_cache();

        // Clean up properties to avoid memory leaks.
        $this->forum1 = $this->forum2 = $this->forum3 = null;
        $this->course1 = $this->course2 = null;
        $this->cat1 = $this->cat2 = null;
        $this->user1 = $this->user2 = null;
        $this->generator = null;
        $this->fs = null;

        parent::tearDown();
    }

    /**
     * test if forum posts are purged properly in the system context
     */
    public function test_purge_system() {
        // Prepare data for FORUM 1 (course 1).
        // **************************************.

        // Post 1 is discussion topic 1 => user1.
        // Post 2 is discussion topic 2 => user1.
        // Post 3 is discussion topic 3 => user2.
        // Post 4 is reply to discussion 1 => user2.
        // Post 5 is reply to discussion 3 => user1.
        list($discussion1, $discussion2, $discussion3) = $this->create_discussions_forum1();
        list($post1, $post2, $post3, $post4, $post5) = $this->create_posts_forum1($discussion1, $discussion2, $discussion3);

        $contextmodule = context_module::instance($this->forum1->cmid);

        $file1 = (object)[
            'contextid' => $contextmodule->id,
            'component' => 'mod_forum',
            'filearea' => 'attachment',
            'itemid' => $post1->id,
            'filepath' => '/',
            'filename' => 'testfile.txt'
        ];
        $this->fs->create_file_from_string($file1, 'testfile');

        $this->assertTrue(
            $this->fs->file_exists(
                $file1->contextid,
                $file1->component,
                $file1->filearea,
                $file1->itemid,
                $file1->filepath,
                $file1->filename
            )
        );

        $file2 = (object)[
            'contextid' => $contextmodule->id,
            'component' => 'mod_forum',
            'filearea' => 'attachment',
            'itemid' => $post3->id,
            'filepath' => '/',
            'filename' => 'testfile.txt'
        ];
        $this->fs->create_file_from_string($file2, 'testfile2');

        $this->assertTrue(
            $this->fs->file_exists(
                $file2->contextid,
                $file2->component,
                $file2->filearea,
                $file2->itemid,
                $file2->filepath,
                $file2->filename
            )
        );

        // Prepare data for FORUM 2 (course 1).
        // **************************************.

        // Post 1 is discussion topic 1 => user1.
        // Post 2 is reply to discussion 1 => user2.
        // Post 3 is reply to post 2 => user1.
        list($discussion1forum2) = $this->create_discussions_forum2();
        list($post1forum2, $post2forum2, $post3forum2) = $this->create_posts_forum2($discussion1forum2);

        // Prepare data for FORUM 3 (course 2, category 2).
        // **************************************.

        // Post 1 is discussion topic 1 => user1.
        // Post 2 is reply to discussion 1 => user2.
        // Post 3 is reply to post 2 => user1.
        list($discussion1forum3) = $this->create_discussions_forum3();
        list($post1forum3, $post2forum3, $post3forum3) = $this->create_posts_forum3($discussion1forum3);

        // DO PURGE.
        // **************************************.

        $targetuser = new target_user($this->user1);
        // Purge data.
        $result = posts::execute_purge($targetuser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        // Check result for FORUM 1.
        // **************************************.

        // Discussions 1 and 2 changed.
        $this->assert_discussion_has_been_deleted($discussion1);
        $this->assert_discussion_has_been_deleted($discussion2);
        // Discussion 3 hasn't changed.
        $this->assert_discussion_unchanged($discussion3);

        // As posts 1 to 2 represent the discussion topics we expect them to be emptied.
        $this->assert_post_has_been_deleted($post1);
        $this->assert_post_has_been_deleted($post2);
        // Post 5 was changed as well.
        $this->assert_post_has_been_deleted($post5);

        // Posts 3 and 4 are unchanged as they belong to a different user.
        $this->assert_post_unchanged($post3);
        $this->assert_post_unchanged($post4);

        // File was deleted.
        $this->assertFalse(
            $this->fs->file_exists(
                $file1->contextid,
                $file1->component,
                $file1->filearea,
                $file1->itemid,
                $file1->filepath,
                $file1->filename
            )
        );

        // File of other post is still there.
        $this->assertTrue(
            $this->fs->file_exists(
                $file2->contextid,
                $file2->component,
                $file2->filearea,
                $file2->itemid,
                $file2->filepath,
                $file2->filename
            )
        );

        // Check result for FORUM 2.
        // **************************************.

        // Discussions and post 1 and 3 (user 1) changed.
        $this->assert_discussion_has_been_deleted($discussion1forum2);
        $this->assert_post_has_been_deleted($post1forum2);
        $this->assert_post_has_been_deleted($post3forum2);
        // Post 2 (user 2) did not change.
        $this->assert_post_unchanged($post2forum2);

        // Check result for FORUM 3.
        // **************************************.

        // Discussions and post 1 and 3 (user 1) changed.
        $this->assert_discussion_has_been_deleted($discussion1forum3);
        $this->assert_post_has_been_deleted($post1forum3);
        $this->assert_post_has_been_deleted($post3forum3);
        // Post 2 (user 2) did not change.
        $this->assert_post_unchanged($post2forum3);
    }

    /**
     * test if forum posts are purged properly in the course category context
     */
    public function test_purge_coursecat() {
        // Prepare data for FORUM 1 (course 1).
        // **************************************.

        // Post 1 is discussion topic 1 => user1.
        // Post 2 is discussion topic 2 => user1.
        // Post 3 is discussion topic 3 => user2.
        // Post 4 is reply to discussion 1 => user2.
        // Post 5 is reply to discussion 3 => user1.
        list($discussion1, $discussion2, $discussion3) = $this->create_discussions_forum1();
        list($post1, $post2, $post3, $post4, $post5) = $this->create_posts_forum1($discussion1, $discussion2, $discussion3);

        // Prepare data for FORUM 2 (course 1).
        // **************************************.

        // Post 1 is discussion topic 1 => user1.
        // Post 2 is reply to discussion 1 => user2.
        // Post 3 is reply to post 2 => user1.
        list($discussion1forum2) = $this->create_discussions_forum2();
        list($post1forum2, $post2forum2, $post3forum2) = $this->create_posts_forum2($discussion1forum2);

        // Prepare data for FORUM 3 (course 2, category 2).
        // **************************************.

        // Post 1 is discussion topic 1 => user1.
        // Post 2 is reply to discussion 1 => user2.
        // Post 3 is reply to post 2 => user1.
        list($discussion1forum3) = $this->create_discussions_forum3();
        list($post1forum3, $post2forum3, $post3forum3) = $this->create_posts_forum3($discussion1forum3);

        // DO PURGE.
        // **************************************.

        $targetuser = new target_user($this->user1);
        // Purge data.
        $result = posts::execute_purge($targetuser, context_coursecat::instance($this->cat1->id));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        // Check result for FORUM 1.
        // **************************************.

        // Discussions 1 and 2 changed.
        $this->assert_discussion_has_been_deleted($discussion1);
        $this->assert_discussion_has_been_deleted($discussion2);
        // Discussion 3 hasn't changed.
        $this->assert_discussion_unchanged($discussion3);

        // As posts 1 to 2 represent the discussion topics we expect the discussion placeholder.
        $this->assert_post_has_been_deleted($post1);
        $this->assert_post_has_been_deleted($post2);
        // Post 5 was changed as well.
        $this->assert_post_has_been_deleted($post5);

        // Posts 3 and 4 are unchanged as they belong to a different user.
        $this->assert_post_unchanged($post3);
        $this->assert_post_unchanged($post4);

        // Check result for FORUM 2.
        // **************************************.

        // Discussions and post 1 and 3 (user 1) changed.
        $this->assert_discussion_has_been_deleted($discussion1forum2);
        $this->assert_post_has_been_deleted($post1forum2);
        $this->assert_post_has_been_deleted($post3forum2);
        // Post 2 (user 2) did not change.
        $this->assert_post_unchanged($post2forum2);

        // Check result for FORUM 3 (different course, different category).
        // **************************************.

        // Nothing should have changed in category 2.
        $this->assert_discussion_unchanged($discussion1forum3);
        $this->assert_post_unchanged($post1forum3);
        $this->assert_post_unchanged($post2forum3);
        $this->assert_post_unchanged($post3forum3);
    }

    /**
     * test if forum posts are purged properly in the course context
     */
    public function test_purge_course() {
        // Prepare data for FORUM 1 (course 1).
        // **************************************.

        // Post 1 is discussion topic 1 => user1.
        // Post 2 is discussion topic 2 => user1.
        // Post 3 is discussion topic 3 => user2.
        // Post 4 is reply to discussion 1 => user2.
        // Post 5 is reply to discussion 3 => user1.
        list($discussion1, $discussion2, $discussion3) = $this->create_discussions_forum1();
        list($post1, $post2, $post3, $post4, $post5) = $this->create_posts_forum1($discussion1, $discussion2, $discussion3);

        // Prepare data for FORUM 2 (course 1).
        // **************************************.

        // Post 1 is discussion topic 1 => user1.
        // Post 2 is reply to discussion 1 => user2.
        // Post 3 is reply to post 2 => user1.
        list($discussion1forum2) = $this->create_discussions_forum2();
        list($post1forum2, $post2forum2, $post3forum2) = $this->create_posts_forum2($discussion1forum2);

        // Prepare data for FORUM 3 (course 2, category 2).
        // **************************************.

        // Post 1 is discussion topic 1 => user1.
        // Post 2 is reply to discussion 1 => user2.
        // Post 3 is reply to post 2 => user1.
        list($discussion1forum3) = $this->create_discussions_forum3();
        list($post1forum3, $post2forum3, $post3forum3) = $this->create_posts_forum3($discussion1forum3);

        // DO PURGE.
        // **************************************.

        $targetuser = new target_user($this->user1);
        // Purge data.
        $result = posts::execute_purge($targetuser, context_course::instance($this->course2->id));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        // Check result for FORUM 1.
        // **************************************.

        // Nothing changed in forum 1.
        $this->assert_discussion_unchanged($discussion1);
        $this->assert_discussion_unchanged($discussion2);
        $this->assert_discussion_unchanged($discussion3);
        $this->assert_post_unchanged($post1);
        $this->assert_post_unchanged($post2);
        $this->assert_post_unchanged($post3);
        $this->assert_post_unchanged($post4);
        $this->assert_post_unchanged($post5);

        // Check result for FORUM 2.
        // **************************************.

        // Nothing changed in forum 2.
        $this->assert_discussion_unchanged($discussion1forum2);
        $this->assert_post_unchanged($post1forum2);
        $this->assert_post_unchanged($post3forum2);
        $this->assert_post_unchanged($post2forum2);

        // Check result for FORUM 3 (different course, different category).
        // **************************************.

        // Discussions and post 1 and 3 (user 1) changed.
        $this->assert_discussion_has_been_deleted($discussion1forum3);
        $this->assert_post_has_been_deleted($post1forum3);
        $this->assert_post_has_been_deleted($post3forum3);
        // Post 2 (user 2) did not change.
        $this->assert_post_unchanged($post2forum3);

    }

    /**
     * test if forum posts are purged properly in the module context
     */
    public function test_purge_module() {
        // Prepare data for FORUM 1 (course 1).
        // **************************************.

        // Post 1 is discussion topic 1 => user1.
        // Post 2 is discussion topic 2 => user1.
        // Post 3 is discussion topic 3 => user2.
        // Post 4 is reply to discussion 1 => user2.
        // Post 5 is reply to discussion 3 => user1.
        list($discussion1, $discussion2, $discussion3) = $this->create_discussions_forum1();
        list($post1, $post2, $post3, $post4, $post5) = $this->create_posts_forum1($discussion1, $discussion2, $discussion3);

        // Prepare data for FORUM 2 (course 1).
        // **************************************.

        // Post 1 is discussion topic 1 => user1.
        // Post 2 is reply to discussion 1 => user2.
        // Post 3 is reply to post 2 => user1.
        list($discussion1forum2) = $this->create_discussions_forum2();
        list($post1forum2, $post2forum2, $post3forum2) = $this->create_posts_forum2($discussion1forum2);

        // Prepare data for FORUM 3 (course 2, category 2).
        // **************************************.

        // Post 1 is discussion topic 1 => user1.
        // Post 2 is reply to discussion 1 => user2.
        // Post 3 is reply to post 2 => user1.
        list($discussion1forum3) = $this->create_discussions_forum3();
        list($post1forum3, $post2forum3, $post3forum3) = $this->create_posts_forum3($discussion1forum3);

        // DO PURGE.
        // **************************************.

        $targetuser = new target_user($this->user1);
        // Purge data.
        $result = posts::execute_purge($targetuser, context_module::instance($this->forum2->cmid));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        // Check result for FORUM 1.
        // **************************************.

        // Nothing changed in forum 1.
        $this->assert_discussion_unchanged($discussion1);
        $this->assert_discussion_unchanged($discussion2);
        $this->assert_discussion_unchanged($discussion3);
        $this->assert_post_unchanged($post1);
        $this->assert_post_unchanged($post2);
        $this->assert_post_unchanged($post3);
        $this->assert_post_unchanged($post4);
        $this->assert_post_unchanged($post5);

        // Check result for FORUM 2.
        // **************************************.

        // Discussions and post 1 and 3 (user 1) changed.
        $this->assert_discussion_has_been_deleted($discussion1forum2);
        $this->assert_post_has_been_deleted($post1forum2);
        $this->assert_post_has_been_deleted($post3forum2);
        // Post 2 (user 2) did not change.
        $this->assert_post_unchanged($post2forum2);


        // Check result for FORUM 3 (different course, different category).
        // **************************************.

        // Nothing should have changed in forum 3.
        $this->assert_discussion_unchanged($discussion1forum3);
        $this->assert_post_unchanged($post1forum3);
        $this->assert_post_unchanged($post2forum3);
        $this->assert_post_unchanged($post3forum3);
    }

    /**
     * Test that count returns the correct values
     */
    public function test_count() {
        // Prepare data for FORUM 1 (course 1).
        // **************************************.

        // Post 1 is discussion topic 1 => user1.
        // Post 2 is discussion topic 2 => user1.
        // Post 3 is discussion topic 3 => user2.
        // Post 4 is reply to discussion 1 => user2.
        // Post 5 is reply to discussion 3 => user1.
        list($discussion1, $discussion2, $discussion3) = $this->create_discussions_forum1();
        $this->create_posts_forum1($discussion1, $discussion2, $discussion3);

        // Prepare data for FORUM 2 (course 1).
        // **************************************.

        // Post 1 is discussion topic 1 => user1.
        // Post 2 is reply to discussion 1 => user2.
        // Post 3 is reply to post 2 => user1.
        list($discussion1forum2) = $this->create_discussions_forum2();
        $this->create_posts_forum2($discussion1forum2);

        // Prepare data for FORUM 3 (course 2, category 2).
        // **************************************.

        // Post 1 is discussion topic 1 => user1.
        // Post 2 is reply to discussion 1 => user2.
        // Post 3 is reply to post 2 => user1.
        list($discussion1forum3) = $this->create_discussions_forum3();
        $this->create_posts_forum3($discussion1forum3);

        // DO COUNT.
        // **************************************.

        // Count data.
        $targetuser = new target_user($this->user1);
        $result = posts::execute_count($targetuser, context_system::instance());
        $this->assertEquals(7, $result);

        // Context course category.
        $result = posts::execute_count($targetuser, context_coursecat::instance($this->cat1->id));
        $this->assertEquals(5, $result);
        $result = posts::execute_count($targetuser, context_coursecat::instance($this->cat2->id));
        $this->assertEquals(2, $result);

        // Context course.
        $result = posts::execute_count($targetuser, context_course::instance($this->course1->id));
        $this->assertEquals(5, $result);
        $result = posts::execute_count($targetuser, context_course::instance($this->course2->id));
        $this->assertEquals(2, $result);

        // Context module.
        $result = posts::execute_count($targetuser, context_module::instance($this->forum1->cmid));
        $this->assertEquals(3, $result);
        $result = posts::execute_count($targetuser, context_module::instance($this->forum2->cmid));
        $this->assertEquals(2, $result);
        $result = posts::execute_count($targetuser, context_module::instance($this->forum3->cmid));
        $this->assertEquals(2, $result);

        // Purge and recount data.
        posts::execute_purge($targetuser, context_system::instance());
        // After purging nothing should be left.
        $result = posts::execute_count($targetuser, context_system::instance());
        $this->assertEquals(0, $result);
    }

    /**
     * Test that export returns the correct values
     */
    public function test_export() {
        // Prepare data for FORUM 1 (course 1).
        // **************************************.

        // Post 1 is discussion topic 1 => user1.
        // Post 2 is discussion topic 2 => user1.
        // Post 3 is discussion topic 3 => user2.
        // Post 4 is reply to discussion 1 => user2.
        // Post 5 is reply to discussion 3 => user1.
        list($discussion1, $discussion2, $discussion3) = $this->create_discussions_forum1();
        list($post1, $post2, $post3, $post4, $post5) = $this->create_posts_forum1($discussion1, $discussion2, $discussion3);

        // Prepare data for FORUM 2 (course 1).
        // **************************************.

        // Post 1 is discussion topic 1 => user1.
        // Post 2 is reply to discussion 1 => user2.
        // Post 3 is reply to post 2 => user1.
        list($discussion1forum2) = $this->create_discussions_forum2();
        list($post1forum2, $post2forum2, $post3forum2) = $this->create_posts_forum2($discussion1forum2);

        // Prepare data for FORUM 3 (course 2, category 2).
        // **************************************.

        // Post 1 is discussion topic 1 => user1.
        // Post 2 is reply to discussion 1 => user2.
        // Post 3 is reply to post 2 => user1.
        list($discussion1forum3) = $this->create_discussions_forum3();
        list($post1forum3, $post2forum3, $post3forum3) = $this->create_posts_forum3($discussion1forum3);

        // DO COUNT.
        // **************************************.

        // Export user 1.
        $targetuser = new target_user($this->user1);
        $result = posts::execute_export($targetuser, context_system::instance());

        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(7, $result->data);
        $exportedids = [];
        foreach ($result->data as $data) {
            $this->assertObjectHasAttribute('id', $data);
            $this->assertObjectHasAttribute('subject', $data);
            $this->assertObjectHasAttribute('userid', $data);
            $this->assertObjectHasAttribute('message', $data);
            $this->assertEquals($this->user1->id, $data->userid);
            $exportedids[] = $data->id;
        }
        $this->assertContains($post1->id, $exportedids);
        $this->assertContains($post2->id, $exportedids);
        $this->assertContains($post5->id, $exportedids);
        $this->assertContains($post1forum2->id, $exportedids);
        $this->assertContains($post3forum2->id, $exportedids);
        $this->assertContains($post1forum3->id, $exportedids);
        $this->assertContains($post3forum3->id, $exportedids);

        // Export user 2.
        $targetuser = new target_user($this->user2);
        $result = posts::execute_export($targetuser, context_system::instance());

        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(4, $result->data);
        $exportedids = [];
        foreach ($result->data as $data) {
            $this->assertObjectHasAttribute('id', $data);
            $this->assertObjectHasAttribute('subject', $data);
            $this->assertObjectHasAttribute('userid', $data);
            $this->assertObjectHasAttribute('message', $data);
            $this->assertEquals($this->user2->id, $data->userid);
            $exportedids[] = $data->id;
        }
        $this->assertContains($post3->id, $exportedids);
        $this->assertContains($post4->id, $exportedids);
        $this->assertContains($post2forum2->id, $exportedids);
        $this->assertContains($post2forum3->id, $exportedids);
    }

    /**
     * Test that export returns the correct values
     */
    public function test_export_different_contexts() {
        // Prepare data for FORUM 1 (course 1).
        // **************************************.

        // Post 1 is discussion topic 1 => user1.
        // Post 2 is discussion topic 2 => user1.
        // Post 3 is discussion topic 3 => user2.
        // Post 4 is reply to discussion 1 => user2.
        // Post 5 is reply to discussion 3 => user1.
        list($discussion1, $discussion2, $discussion3) = $this->create_discussions_forum1();
        list($post1, $post2, $post3, $post4, $post5) = $this->create_posts_forum1($discussion1, $discussion2, $discussion3);

        // Prepare data for FORUM 2 (course 1).
        // **************************************.

        // Post 1 is discussion topic 1 => user1.
        // Post 2 is reply to discussion 1 => user2.
        // Post 3 is reply to post 2 => user1.
        list($discussion1forum2) = $this->create_discussions_forum2();
        list($post1forum2, $post2forum2, $post3forum2) = $this->create_posts_forum2($discussion1forum2);

        // Prepare data for FORUM 3 (course 2, category 2).
        // **************************************.

        // Post 1 is discussion topic 1 => user1.
        // Post 2 is reply to discussion 1 => user2.
        // Post 3 is reply to post 2 => user1.
        list($discussion1forum3) = $this->create_discussions_forum3();
        list($post1forum3, $post2forum3, $post3forum3) = $this->create_posts_forum3($discussion1forum3);

        // DO COUNT.
        // **************************************.

        // Export user 1.
        $targetuser = new target_user($this->user1);
        $result = posts::execute_export($targetuser, context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(7, $result->data);

        $result = posts::execute_export($targetuser, context_coursecat::instance($this->cat1->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(5, $result->data);

        $result = posts::execute_export($targetuser, context_coursecat::instance($this->cat2->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(2, $result->data);

        $result = posts::execute_export($targetuser, context_course::instance($this->course1->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(5, $result->data);

        $result = posts::execute_export($targetuser, context_course::instance($this->course2->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(2, $result->data);

        $result = posts::execute_export($targetuser, context_module::instance($this->forum1->cmid));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(3, $result->data);

        $result = posts::execute_export($targetuser, context_module::instance($this->forum2->cmid));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(2, $result->data);

        $result = posts::execute_export($targetuser, context_module::instance($this->forum3->cmid));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(2, $result->data);
    }

    /**
     * Discussion 1 => user 1.
     * Discussion 2 => user 1.
     * Discussion 3 => user 3.
     *
     * @return array(discussion1, discussion2, discussion3)
     */
    private function create_discussions_forum1() {
        // Add a few discussions.
        $record = ['course' => $this->course1->id, 'forum' => $this->forum1->id, 'userid' => $this->user1->id];
        // User 1 opens two new discussions.
        $discussion1 = $this->generator->create_discussion($record);
        $discussion2 = $this->generator->create_discussion($record);

        $record = ['course' => $this->course1->id, 'forum' => $this->forum1->id, 'userid' => $this->user2->id];
        // User 2 opens a new discussion.
        $discussion3 = $this->generator->create_discussion($record);

        return [$discussion1, $discussion2, $discussion3];
    }

    /**
     * Post 1 is discussion topic 1 => user 1.
     * Post 2 is discussion topic 2 => user 1.
     * Post 3 is discussion topic 3 => user 2.
     * Post 4 is reply to discussion 1 => user 2.
     * Post 5 is reply to discussion 3 => user1.
     *
     * @param stdClass $discussion1
     * @param stdClass $discussion2
     * @param stdClass $discussion3
     *
     * @return array (post1, post2, post3, post4, post5)
     */
    private function create_posts_forum1($discussion1, $discussion2, $discussion3) {
        global $DB;

        // Posts 1 to 3 are the main posts for the discussions so they are already created.
        $post1 = $DB->get_record('forum_posts', ['discussion' => $discussion1->id, 'parent' => 0, 'userid' => $this->user1->id]);
        $post2 = $DB->get_record('forum_posts', ['discussion' => $discussion2->id, 'parent' => 0, 'userid' => $this->user1->id]);
        $post3 = $DB->get_record('forum_posts', ['discussion' => $discussion3->id, 'parent' => 0, 'userid' => $this->user2->id]);
        // User 2 replies to discussion 1.
        $post4 = $this->generator->create_post(
            ['discussion' => $discussion1->id, 'userid' => $this->user2->id, 'parent' => $post1->id]
        );
        // User 1 replies to discussion 3.
        $post5 = $this->generator->create_post(
            ['discussion' => $discussion3->id, 'userid' => $this->user1->id, 'parent' => $post3->id]
        );

        return [$post1, $post2, $post3, $post4, $post5];
    }

    /**
     * Discussion 1 => user 1.
     *
     * @return array(discussion1)
     */
    private function create_discussions_forum2() {
        // Add a discussion in another forum.
        $record = ['course' => $this->course1->id, 'forum' => $this->forum2->id, 'userid' => $this->user1->id];
        // User 1 opens a new discussion.
        $discussion1 = $this->generator->create_discussion($record);

        return [$discussion1];
    }

    /**
     * Course 1
     * Forum 2
     *
     * Post 1 is discussion topic 1 => user 1.
     * Post 2 is reply to discussion 1 => user 2.
     * Post 3 is reply to post 2 => user 1.
     *
     * @param stdClass $discussion1
     *
     * @return array (post1, post2, post3)
     */
    private function create_posts_forum2($discussion1) {
        global $DB;

        // New discussion.
        $post1 = $DB->get_record('forum_posts', ['discussion' => $discussion1->id, 'parent' => 0, 'userid' => $this->user1->id]);
        // User 2 replies to discussion 1 in forum2.
        $post2 = $this->generator->create_post([
            'discussion' => $discussion1->id,
            'userid' => $this->user2->id,
            'parent' => $post1->id
        ]);

        // User 1 replies to post of user 2 in forum2.
        $post3 = $this->generator->create_post([
            'discussion' => $discussion1->id,
            'userid' => $this->user1->id,
            'parent' => $post2->id
        ]);

        return [$post1, $post2, $post3];
    }

    /**
     * Discussion 1 => user 1.
     *
     * @return array(discussion1)
     */
    private function create_discussions_forum3() {
        // Add a discussion in another forum.
        $record = ['course' => $this->course2->id, 'forum' => $this->forum3->id, 'userid' => $this->user1->id];
        // User 1 opens a new discussion.
        $discussion1 = $this->generator->create_discussion($record);

        return [$discussion1];
    }

    /**
     * Course 2 (category 2)
     * Forum 2
     *
     * Post 1 is discussion topic 1 => user 1.
     * Post 2 is reply to discussion 1 => user 2.
     * Post 3 is reply to post 2 => user 1.
     *
     * @param stdClass $discussion1
     *
     * @return array (post1, post2, post3)
     */
    private function create_posts_forum3($discussion1) {
        global $DB;

        // New discussion.
        $post1 = $DB->get_record('forum_posts', ['discussion' => $discussion1->id, 'parent' => 0, 'userid' => $this->user1->id]);
        // User 2 replies to discussion 1 in forum2.
        $post2 = $this->generator->create_post([
            'discussion' => $discussion1->id,
            'userid' => $this->user2->id,
            'parent' => $post1->id
        ]);

        // User 1 replies to post of user 2 in forum2.
        $post3 = $this->generator->create_post([
            'discussion' => $discussion1->id,
            'userid' => $this->user1->id,
            'parent' => $post2->id
        ]);

        return [$post1, $post2, $post3];
    }

    /**
     * @param stdClass $discussion
     */
    private function assert_discussion_has_been_deleted($discussion) {
        global $DB;

        // Reload post.
        $discussion = $DB->get_record('forum_discussions', ['id' => $discussion->id]);
        $this->assertEquals('', $discussion->name);
    }

    /**
     * @param stdClass $discussion
     */
    private function assert_discussion_unchanged($discussion) {
        global $DB;

        // Reload post.
        $discussionreloaded = $DB->get_record('forum_discussions', ['id' => $discussion->id]);
        $this->assertEquals($discussion->name, $discussionreloaded->name);
    }

    /**
     * @param stdClass $post
     */
    private function assert_post_has_been_deleted($post) {
        global $DB;

        // Reload post.
        $post = $DB->get_record('forum_posts', ['id' => $post->id]);
        $this->assertEquals('', $post->subject);
        $this->assertEquals('', $post->message);
        $this->assertEquals(1, $post->deleted);
    }

    /**
     * @param stdClass $post
     */
    private function assert_post_unchanged($post) {
        global $DB;

        // Reload post.
        $postreloaded = $DB->get_record('forum_posts', ['id' => $post->id]);
        $this->assertEquals($post->subject, $postreloaded->subject);
        $this->assertEquals($post->message, $postreloaded->message);
        $this->assertEquals($post->deleted, $postreloaded->deleted);
    }

}
