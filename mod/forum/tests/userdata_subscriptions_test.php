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
use mod_forum_generator;
use stdClass;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_userdata
 */
class mod_forum_userdata_subscriptions_testcase extends advanced_testcase {

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
     * Prepare general fixtures for the following tests
     */
    protected function setUp() {
        parent::setUp();

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

        parent::tearDown();
    }

    /**
     * test if forum posts are purged properly in the system context
     */
    public function test_purge_system() {
        global $DB;

        // Prepare data for FORUM 1 (course 1).
        // **************************************.

        // Sub1 => user1.
        // Sub2 => user2.
        $sub1 = $this->create_forum_sub($this->forum1->id, $this->user1->id);
        $sub2 = $this->create_forum_sub($this->forum1->id, $this->user2->id);
        // Discussion1 => user1.
        // Discussion2 => user1.
        // Discussion3 => user2.
        list($discussion1, $discussion2, $discussion3) = $this->create_discussions_forum1();
        // Discussionsub1 => user1.
        // Discussionsub2 => user2.
        // Discussionsub3 => user1.
        // Discussionsub4 => user2.
        $discussionsub1 = $this->create_discussion_sub($discussion1->id, $discussion1->forum, $this->user1->id);
        $discussionsub2 = $this->create_discussion_sub($discussion1->id, $discussion1->forum, $this->user2->id);
        $discussionsub3 = $this->create_discussion_sub($discussion2->id, $discussion2->forum, $this->user1->id);
        $discussionsub4 = $this->create_discussion_sub($discussion3->id, $discussion3->forum, $this->user2->id);

        // Prepare data for FORUM 2 (course 1).
        // **************************************.

        // Sub1 => user1.
        // Sub2 => user2.
        $sub1forum2 = $this->create_forum_sub($this->forum2->id, $this->user1->id);
        $sub2forum2 = $this->create_forum_sub($this->forum2->id, $this->user2->id);
        // Discussion1 => user1.
        list($discussion1forum2) = $this->create_discussions_forum2();
        // Discussionsub1forum2 => user1.
        // Discussionsub2forum2 => user2.
        $discussionsub1forum2 = $this->create_discussion_sub($discussion1forum2->id, $discussion1forum2->forum, $this->user1->id);
        $discussionsub2forum2 = $this->create_discussion_sub($discussion1forum2->id, $discussion1forum2->forum, $this->user2->id);

        // Prepare data for FORUM 3 (course 2, category 2).
        // **************************************.

        // Sub1 => user1.
        // Sub2 => user2.
        $sub1forum3 = $this->create_forum_sub($this->forum3->id, $this->user1->id);
        $sub2forum3 = $this->create_forum_sub($this->forum3->id, $this->user2->id);
        // Discussion1 => user1.
        list($discussion1forum3) = $this->create_discussions_forum3();
        // Discussionsub1forum3 => user1.
        // Discussionsub2forum3 => user2.
        $discussionsub1forum3 = $this->create_discussion_sub($discussion1forum3->id, $discussion1forum3->forum, $this->user1->id);
        $discussionsub2forum3 = $this->create_discussion_sub($discussion1forum3->id, $discussion1forum3->forum, $this->user2->id);

        // DO PURGE.
        // **************************************.

        $targetuser = new target_user($this->user1);
        // Purge data.
        $result = subscriptions::execute_purge($targetuser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        // Check result for FORUM 1.
        // **************************************.

        $this->assertEmpty($DB->get_record('forum_subscriptions', ['id' => $sub1->id]));
        $this->assertEmpty($DB->get_record('forum_discussion_subs', ['id' => $discussionsub1->id]));
        $this->assertEmpty($DB->get_record('forum_discussion_subs', ['id' => $discussionsub3->id]));

        $this->assertNotEmpty($DB->get_record('forum_subscriptions', ['id' => $sub2->id]));
        $this->assertNotEmpty($DB->get_record('forum_discussion_subs', ['id' => $discussionsub2->id]));
        $this->assertNotEmpty($DB->get_record('forum_discussion_subs', ['id' => $discussionsub4->id]));

        // Check result for FORUM 2.
        // **************************************.

        $this->assertEmpty($DB->get_record('forum_subscriptions', ['id' => $sub1forum2->id]));
        $this->assertEmpty($DB->get_record('forum_discussion_subs', ['id' => $discussionsub1forum2->id]));

        $this->assertNotEmpty($DB->get_record('forum_subscriptions', ['id' => $sub2forum2->id]));
        $this->assertNotEmpty($DB->get_record('forum_discussion_subs', ['id' => $discussionsub2forum2->id]));

        // Check result for FORUM 3.
        // **************************************.

        $this->assertEmpty($DB->get_record('forum_subscriptions', ['id' => $sub1forum3->id]));
        $this->assertEmpty($DB->get_record('forum_discussion_subs', ['id' => $discussionsub1forum3->id]));

        $this->assertNotEmpty($DB->get_record('forum_subscriptions', ['id' => $sub2forum3->id]));
        $this->assertNotEmpty($DB->get_record('forum_discussion_subs', ['id' => $discussionsub2forum3->id]));
    }

    /**
     * test if forum posts are purged properly in the course category context
     */
    public function test_purge_coursecat() {
        global $DB;

        // Prepare data for FORUM 1 (course 1).
        // **************************************.

        // Sub1 => user1.
        // Sub2 => user2.
        $sub1 = $this->create_forum_sub($this->forum1->id, $this->user1->id);
        $sub2 = $this->create_forum_sub($this->forum1->id, $this->user2->id);
        // Discussion1 => user1.
        // Discussion2 => user1.
        // Discussion3 => user2.
        list($discussion1, $discussion2, $discussion3) = $this->create_discussions_forum1();
        // Discussionsub1 => user1.
        // Discussionsub2 => user2.
        // Discussionsub3 => user1.
        // Discussionsub4 => user2.
        $discussionsub1 = $this->create_discussion_sub($discussion1->id, $discussion1->forum, $this->user1->id);
        $discussionsub2 = $this->create_discussion_sub($discussion1->id, $discussion1->forum, $this->user2->id);
        $discussionsub3 = $this->create_discussion_sub($discussion2->id, $discussion2->forum, $this->user1->id);
        $discussionsub4 = $this->create_discussion_sub($discussion3->id, $discussion3->forum, $this->user2->id);

        // Prepare data for FORUM 2 (course 1).
        // **************************************.

        // Sub1 => user1.
        // Sub2 => user2.
        $sub1forum2 = $this->create_forum_sub($this->forum2->id, $this->user1->id);
        $sub2forum2 = $this->create_forum_sub($this->forum2->id, $this->user2->id);
        // Discussion1 => user1.
        list($discussion1forum2) = $this->create_discussions_forum2();
        // Discussionsub1forum2 => user1.
        // Discussionsub2forum2 => user2.
        $discussionsub1forum2 = $this->create_discussion_sub($discussion1forum2->id, $discussion1forum2->forum, $this->user1->id);
        $discussionsub2forum2 = $this->create_discussion_sub($discussion1forum2->id, $discussion1forum2->forum, $this->user2->id);

        // Prepare data for FORUM 3 (course 2, category 2).
        // **************************************.

        // Sub1 => user1.
        // Sub2 => user2.
        $sub1forum3 = $this->create_forum_sub($this->forum3->id, $this->user1->id);
        $sub2forum3 = $this->create_forum_sub($this->forum3->id, $this->user2->id);
        // Discussion1 => user1.
        list($discussion1forum3) = $this->create_discussions_forum3();
        // Discussionsub1forum3 => user1.
        // Discussionsub2forum3 => user2.
        $discussionsub1forum3 = $this->create_discussion_sub($discussion1forum3->id, $discussion1forum3->forum, $this->user1->id);
        $discussionsub2forum3 = $this->create_discussion_sub($discussion1forum3->id, $discussion1forum3->forum, $this->user2->id);

        // DO PURGE.
        // **************************************.

        $targetuser = new target_user($this->user1);
        // Purge data.
        $result = subscriptions::execute_purge($targetuser, context_coursecat::instance($this->cat1->id));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        // Check result for FORUM 1.
        // **************************************.

        $this->assertEmpty($DB->get_record('forum_subscriptions', ['id' => $sub1->id]));
        $this->assertEmpty($DB->get_record('forum_discussion_subs', ['id' => $discussionsub1->id]));
        $this->assertEmpty($DB->get_record('forum_discussion_subs', ['id' => $discussionsub3->id]));

        $this->assertNotEmpty($DB->get_record('forum_subscriptions', ['id' => $sub2->id]));
        $this->assertNotEmpty($DB->get_record('forum_discussion_subs', ['id' => $discussionsub2->id]));
        $this->assertNotEmpty($DB->get_record('forum_discussion_subs', ['id' => $discussionsub4->id]));

        // Check result for FORUM 2.
        // **************************************.

        $this->assertEmpty($DB->get_record('forum_subscriptions', ['id' => $sub1forum2->id]));
        $this->assertEmpty($DB->get_record('forum_discussion_subs', ['id' => $discussionsub1forum2->id]));

        $this->assertNotEmpty($DB->get_record('forum_subscriptions', ['id' => $sub2forum2->id]));
        $this->assertNotEmpty($DB->get_record('forum_discussion_subs', ['id' => $discussionsub2forum2->id]));

        // Check result for FORUM 3.
        // **************************************.

        // Category 2 is untouched.
        $this->assertNotEmpty($DB->get_record('forum_subscriptions', ['id' => $sub1forum3->id]));
        $this->assertNotEmpty($DB->get_record('forum_discussion_subs', ['id' => $discussionsub1forum3->id]));
        $this->assertNotEmpty($DB->get_record('forum_subscriptions', ['id' => $sub2forum3->id]));
        $this->assertNotEmpty($DB->get_record('forum_discussion_subs', ['id' => $discussionsub2forum3->id]));
    }

    /**
     * test if forum posts are purged properly in the course context
     */
    public function test_purge_course() {
        global $DB;

        // Prepare data for FORUM 1 (course 1).
        // **************************************.

        // Sub1 => user1.
        // Sub2 => user2.
        $sub1 = $this->create_forum_sub($this->forum1->id, $this->user1->id);
        $sub2 = $this->create_forum_sub($this->forum1->id, $this->user2->id);
        // Discussion1 => user1.
        // Discussion2 => user1.
        // Discussion3 => user2.
        list($discussion1, $discussion2, $discussion3) = $this->create_discussions_forum1();
        // Discussionsub1 => user1.
        // Discussionsub2 => user2.
        // Discussionsub3 => user1.
        // Discussionsub4 => user2.
        $discussionsub1 = $this->create_discussion_sub($discussion1->id, $discussion1->forum, $this->user1->id);
        $discussionsub2 = $this->create_discussion_sub($discussion1->id, $discussion1->forum, $this->user2->id);
        $discussionsub3 = $this->create_discussion_sub($discussion2->id, $discussion2->forum, $this->user1->id);
        $discussionsub4 = $this->create_discussion_sub($discussion3->id, $discussion3->forum, $this->user2->id);

        // Prepare data for FORUM 2 (course 1).
        // **************************************.

        // Sub1 => user1.
        // Sub2 => user2.
        $sub1forum2 = $this->create_forum_sub($this->forum2->id, $this->user1->id);
        $sub2forum2 = $this->create_forum_sub($this->forum2->id, $this->user2->id);
        // Discussion1 => user1.
        list($discussion1forum2) = $this->create_discussions_forum2();
        // Discussionsub1forum2 => user1.
        // Discussionsub2forum2 => user2.
        $discussionsub1forum2 = $this->create_discussion_sub($discussion1forum2->id, $discussion1forum2->forum, $this->user1->id);
        $discussionsub2forum2 = $this->create_discussion_sub($discussion1forum2->id, $discussion1forum2->forum, $this->user2->id);

        // Prepare data for FORUM 3 (course 2, category 2).
        // **************************************.

        // Sub1 => user1.
        // Sub2 => user2.
        $sub1forum3 = $this->create_forum_sub($this->forum3->id, $this->user1->id);
        $sub2forum3 = $this->create_forum_sub($this->forum3->id, $this->user2->id);
        // Discussion1 => user1.
        list($discussion1forum3) = $this->create_discussions_forum3();
        // Discussionsub1forum3 => user1.
        // Discussionsub2forum3 => user2.
        $discussionsub1forum3 = $this->create_discussion_sub($discussion1forum3->id, $discussion1forum3->forum, $this->user1->id);
        $discussionsub2forum3 = $this->create_discussion_sub($discussion1forum3->id, $discussion1forum3->forum, $this->user2->id);

        // DO PURGE.
        // **************************************.

        $targetuser = new target_user($this->user1);
        // Purge data.
        $result = subscriptions::execute_purge($targetuser, context_course::instance($this->course2->id));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        // Check result for FORUM 1.
        // **************************************.

        // Course 1 is untouched.
        $this->assertNotEmpty($DB->get_record('forum_subscriptions', ['id' => $sub1->id]));
        $this->assertNotEmpty($DB->get_record('forum_discussion_subs', ['id' => $discussionsub1->id]));
        $this->assertNotEmpty($DB->get_record('forum_discussion_subs', ['id' => $discussionsub3->id]));
        $this->assertNotEmpty($DB->get_record('forum_subscriptions', ['id' => $sub2->id]));
        $this->assertNotEmpty($DB->get_record('forum_discussion_subs', ['id' => $discussionsub2->id]));
        $this->assertNotEmpty($DB->get_record('forum_discussion_subs', ['id' => $discussionsub4->id]));

        // Check result for FORUM 2.
        // **************************************.

        // Course 1 is untouched.
        $this->assertNotEmpty($DB->get_record('forum_subscriptions', ['id' => $sub1forum2->id]));
        $this->assertNotEmpty($DB->get_record('forum_discussion_subs', ['id' => $discussionsub1forum2->id]));
        $this->assertNotEmpty($DB->get_record('forum_subscriptions', ['id' => $sub2forum2->id]));
        $this->assertNotEmpty($DB->get_record('forum_discussion_subs', ['id' => $discussionsub2forum2->id]));

        // Check result for FORUM 3 (course2, category2).
        // **************************************.

        $this->assertEmpty($DB->get_record('forum_subscriptions', ['id' => $sub1forum3->id]));
        $this->assertEmpty($DB->get_record('forum_discussion_subs', ['id' => $discussionsub1forum3->id]));

        $this->assertNotEmpty($DB->get_record('forum_subscriptions', ['id' => $sub2forum3->id]));
        $this->assertNotEmpty($DB->get_record('forum_discussion_subs', ['id' => $discussionsub2forum3->id]));
    }

    /**
     * test if forum posts are purged properly in the module context
     */
    public function test_purge_module() {
        global $DB;

        // Prepare data for FORUM 1 (course 1).
        // **************************************.

        // Sub1 => user1.
        // Sub2 => user2.
        $sub1 = $this->create_forum_sub($this->forum1->id, $this->user1->id);
        $sub2 = $this->create_forum_sub($this->forum1->id, $this->user2->id);
        // Discussion1 => user1.
        // Discussion2 => user1.
        // Discussion3 => user2.
        list($discussion1, $discussion2, $discussion3) = $this->create_discussions_forum1();
        // Discussionsub1 => user1.
        // Discussionsub2 => user2.
        // Discussionsub3 => user1.
        // Discussionsub4 => user2.
        $discussionsub1 = $this->create_discussion_sub($discussion1->id, $discussion1->forum, $this->user1->id);
        $discussionsub2 = $this->create_discussion_sub($discussion1->id, $discussion1->forum, $this->user2->id);
        $discussionsub3 = $this->create_discussion_sub($discussion2->id, $discussion2->forum, $this->user1->id);
        $discussionsub4 = $this->create_discussion_sub($discussion3->id, $discussion3->forum, $this->user2->id);

        // Prepare data for FORUM 2 (course 1).
        // **************************************.

        // Sub1 => user1.
        // Sub2 => user2.
        $sub1forum2 = $this->create_forum_sub($this->forum2->id, $this->user1->id);
        $sub2forum2 = $this->create_forum_sub($this->forum2->id, $this->user2->id);
        // Discussion1 => user1.
        list($discussion1forum2) = $this->create_discussions_forum2();
        // Discussionsub1forum2 => user1.
        // Discussionsub2forum2 => user2.
        $discussionsub1forum2 = $this->create_discussion_sub($discussion1forum2->id, $discussion1forum2->forum, $this->user1->id);
        $discussionsub2forum2 = $this->create_discussion_sub($discussion1forum2->id, $discussion1forum2->forum, $this->user2->id);

        // Prepare data for FORUM 3 (course 2, category 2).
        // **************************************.

        // Sub1 => user1.
        // Sub2 => user2.
        $sub1forum3 = $this->create_forum_sub($this->forum3->id, $this->user1->id);
        $sub2forum3 = $this->create_forum_sub($this->forum3->id, $this->user2->id);
        // Discussion1 => user1.
        list($discussion1forum3) = $this->create_discussions_forum3();
        // Discussionsub1forum3 => user1.
        // Discussionsub2forum3 => user2.
        $discussionsub1forum3 = $this->create_discussion_sub($discussion1forum3->id, $discussion1forum3->forum, $this->user1->id);
        $discussionsub2forum3 = $this->create_discussion_sub($discussion1forum3->id, $discussion1forum3->forum, $this->user2->id);

        // DO PURGE.
        // **************************************.

        $targetuser = new target_user($this->user1);
        // Purge data.
        $result = subscriptions::execute_purge($targetuser, context_module::instance($this->forum2->cmid));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        // Check result for FORUM 1.
        // **************************************.

        // Subscriptions are untouched.
        $this->assertNotEmpty($DB->get_record('forum_subscriptions', ['id' => $sub1->id]));
        $this->assertNotEmpty($DB->get_record('forum_discussion_subs', ['id' => $discussionsub1->id]));
        $this->assertNotEmpty($DB->get_record('forum_discussion_subs', ['id' => $discussionsub3->id]));
        $this->assertNotEmpty($DB->get_record('forum_subscriptions', ['id' => $sub2->id]));
        $this->assertNotEmpty($DB->get_record('forum_discussion_subs', ['id' => $discussionsub2->id]));
        $this->assertNotEmpty($DB->get_record('forum_discussion_subs', ['id' => $discussionsub4->id]));

        // Check result for FORUM 2.
        // **************************************.

        $this->assertEmpty($DB->get_record('forum_subscriptions', ['id' => $sub1forum2->id]));
        $this->assertEmpty($DB->get_record('forum_discussion_subs', ['id' => $discussionsub1forum2->id]));

        $this->assertNotEmpty($DB->get_record('forum_subscriptions', ['id' => $sub2forum2->id]));
        $this->assertNotEmpty($DB->get_record('forum_discussion_subs', ['id' => $discussionsub2forum2->id]));

        // Check result for FORUM 3.
        // **************************************.

        // Subscriptions are untouched.
        $this->assertNotEmpty($DB->get_record('forum_subscriptions', ['id' => $sub1forum3->id]));
        $this->assertNotEmpty($DB->get_record('forum_discussion_subs', ['id' => $discussionsub1forum3->id]));
        $this->assertNotEmpty($DB->get_record('forum_subscriptions', ['id' => $sub2forum3->id]));
        $this->assertNotEmpty($DB->get_record('forum_discussion_subs', ['id' => $discussionsub2forum3->id]));
    }

    /**
     * Test that count returns the correct values
     */
    public function test_count() {
        // Prepare data for FORUM 1 (course 1).
        // **************************************.

        // Sub1 => user1.
        // Sub2 => user2.
        $this->create_forum_sub($this->forum1->id, $this->user1->id);
         $this->create_forum_sub($this->forum1->id, $this->user2->id);
        // Discussion1 => user1.
        // Discussion2 => user1.
        // Discussion3 => user2.
        list($discussion1, $discussion2, $discussion3) = $this->create_discussions_forum1();
        // Discussionsub1 => user1.
        // Discussionsub2 => user2.
        // Discussionsub3 => user1.
        // Discussionsub4 => user2.
        $this->create_discussion_sub($discussion1->id, $discussion1->forum, $this->user1->id);
        $this->create_discussion_sub($discussion1->id, $discussion1->forum, $this->user2->id);
        $this->create_discussion_sub($discussion2->id, $discussion2->forum, $this->user1->id);
        $this->create_discussion_sub($discussion2->id, $discussion2->forum, $this->user2->id);
        $this->create_discussion_sub($discussion3->id, $discussion3->forum, $this->user2->id);

        // Prepare data for FORUM 2 (course 1).
        // **************************************.

        // Sub1 => user1.
        // Sub2 => user2.
        $this->create_forum_sub($this->forum2->id, $this->user1->id);
        $this->create_forum_sub($this->forum2->id, $this->user2->id);
        // Discussion1 => user1.
        list($discussion1forum2) = $this->create_discussions_forum2();
        // Discussionsub1forum2 => user1.
        // Discussionsub2forum2 => user2.
        $this->create_discussion_sub($discussion1forum2->id, $discussion1forum2->forum, $this->user1->id);
        $this->create_discussion_sub($discussion1forum2->id, $discussion1forum2->forum, $this->user2->id);

        // Prepare data for FORUM 3 (course 2, category 2).
        // **************************************.

        // Sub1 => user1.
        // Sub2 => user2.
        $this->create_forum_sub($this->forum3->id, $this->user1->id);
        $this->create_forum_sub($this->forum3->id, $this->user2->id);
        // Discussion1 => user1.
        list($discussion1forum3) = $this->create_discussions_forum3();
        // Discussionsub1forum3 => user1.
        // Discussionsub2forum3 => user2.
        $this->create_discussion_sub($discussion1forum3->id, $discussion1forum3->forum, $this->user2->id);

        // DO COUNT.
        // **************************************.

        // Count data.
        $targetuser = new target_user($this->user1);
        $result = subscriptions::execute_count($targetuser, context_system::instance());
        $this->assertEquals(6, $result);

        // Context course category.
        $result = subscriptions::execute_count($targetuser, context_coursecat::instance($this->cat1->id));
        $this->assertEquals(5, $result);
        $result = subscriptions::execute_count($targetuser, context_coursecat::instance($this->cat2->id));
        $this->assertEquals(1, $result);

        // Context course.
        $result = subscriptions::execute_count($targetuser, context_course::instance($this->course1->id));
        $this->assertEquals(5, $result);
        $result = subscriptions::execute_count($targetuser, context_course::instance($this->course2->id));
        $this->assertEquals(1, $result);

        // Context module.
        $result = subscriptions::execute_count($targetuser, context_module::instance($this->forum1->cmid));
        $this->assertEquals(3, $result);
        $result = subscriptions::execute_count($targetuser, context_module::instance($this->forum2->cmid));
        $this->assertEquals(2, $result);
        $result = subscriptions::execute_count($targetuser, context_module::instance($this->forum3->cmid));
        $this->assertEquals(1, $result);

        // User 2.
        $targetuser = new target_user($this->user2);
        $result = subscriptions::execute_count($targetuser, context_system::instance());
        $this->assertEquals(8, $result);

        // Context course category.
        $result = subscriptions::execute_count($targetuser, context_coursecat::instance($this->cat1->id));
        $this->assertEquals(6, $result);
        $result = subscriptions::execute_count($targetuser, context_coursecat::instance($this->cat2->id));
        $this->assertEquals(2, $result);

        // Context course.
        $result = subscriptions::execute_count($targetuser, context_course::instance($this->course1->id));
        $this->assertEquals(6, $result);
        $result = subscriptions::execute_count($targetuser, context_course::instance($this->course2->id));
        $this->assertEquals(2, $result);

        // Context module.
        $result = subscriptions::execute_count($targetuser, context_module::instance($this->forum1->cmid));
        $this->assertEquals(4, $result);
        $result = subscriptions::execute_count($targetuser, context_module::instance($this->forum2->cmid));
        $this->assertEquals(2, $result);
        $result = subscriptions::execute_count($targetuser, context_module::instance($this->forum3->cmid));
        $this->assertEquals(2, $result);
    }

    /**
     * Test that export returns the correct values
     */
    public function test_export() {
        // Prepare data for FORUM 1 (course 1).
        // **************************************.

        // Sub1 => user1.
        // Sub2 => user2.
        $sub1 = $this->create_forum_sub($this->forum1->id, $this->user1->id);
        $sub2 = $this->create_forum_sub($this->forum1->id, $this->user2->id);
        // Discussion1 => user1.
        // Discussion2 => user1.
        // Discussion3 => user2.
        list($discussion1, $discussion2, $discussion3) = $this->create_discussions_forum1();
        // Discussionsub1 => user1.
        // Discussionsub2 => user2.
        // Discussionsub3 => user1.
        // Discussionsub4 => user2.
        $discussionsub1 = $this->create_discussion_sub($discussion1->id, $discussion1->forum, $this->user1->id);
        $discussionsub2 = $this->create_discussion_sub($discussion1->id, $discussion1->forum, $this->user2->id);
        $discussionsub3 = $this->create_discussion_sub($discussion2->id, $discussion2->forum, $this->user1->id);
        $discussionsub4 = $this->create_discussion_sub($discussion3->id, $discussion3->forum, $this->user2->id);

        // Prepare data for FORUM 2 (course 1).
        // **************************************.

        // Sub1 => user1.
        // Sub2 => user2.
        $sub1forum2 = $this->create_forum_sub($this->forum2->id, $this->user1->id);
        $sub2forum2 = $this->create_forum_sub($this->forum2->id, $this->user2->id);
        // Discussion1 => user1.
        list($discussion1forum2) = $this->create_discussions_forum2();
        // Discussionsub1forum2 => user1.
        // Discussionsub2forum2 => user2.
        $discussionsub1forum2 = $this->create_discussion_sub($discussion1forum2->id, $discussion1forum2->forum, $this->user1->id);
        $discussionsub2forum2 = $this->create_discussion_sub($discussion1forum2->id, $discussion1forum2->forum, $this->user2->id);

        // Prepare data for FORUM 3 (course 2, category 2).
        // **************************************.

        // Sub1 => user1.
        // Sub2 => user2.
        $sub1forum3 = $this->create_forum_sub($this->forum3->id, $this->user1->id);
        $sub2forum3 = $this->create_forum_sub($this->forum3->id, $this->user2->id);
        // Discussion1 => user1.
        list($discussion1forum3) = $this->create_discussions_forum3();
        // Discussionsub1forum3 => user1.
        // Discussionsub2forum3 => user2.
        $discussionsub1forum3 = $this->create_discussion_sub($discussion1forum3->id, $discussion1forum3->forum, $this->user1->id);
        $discussionsub2forum3 = $this->create_discussion_sub($discussion1forum3->id, $discussion1forum3->forum, $this->user2->id);

        // DO Export.
        // **************************************.

        // Export user 1.
        $targetuser = new target_user($this->user1);
        $result = subscriptions::execute_export($targetuser, context_system::instance());

        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(3, $result->data['forum_subscriptions']);
        $exportedids = [];
        foreach ($result->data['forum_subscriptions'] as $data) {
            $this->assertObjectHasAttribute('id', $data);
            $this->assertObjectHasAttribute('forum', $data);
            $this->assertObjectHasAttribute('userid', $data);
            $this->assertEquals($this->user1->id, $data->userid);
            $exportedids[] = $data->id;
        }
        $this->assertContains($sub1->id, $exportedids);
        $this->assertContains($sub1forum2->id, $exportedids);
        $this->assertContains($sub1forum3->id, $exportedids);

        $this->assertCount(4, $result->data['discussion_subscriptions']);
        $exportedids = [];
        foreach ($result->data['discussion_subscriptions'] as $data) {
            $this->assertObjectHasAttribute('id', $data);
            $this->assertObjectHasAttribute('forum', $data);
            $this->assertObjectHasAttribute('userid', $data);
            $this->assertEquals($this->user1->id, $data->userid);
            $exportedids[] = $data->id;
        }
        $this->assertContains($discussionsub1->id, $exportedids);
        $this->assertContains($discussionsub3->id, $exportedids);
        $this->assertContains($discussionsub1forum2->id, $exportedids);
        $this->assertContains($discussionsub1forum3->id, $exportedids);
    }

    /**
     * Test that export returns the correct values
     */
    public function test_export_different_contexts() {
        // Prepare data for FORUM 1 (course 1).
        // **************************************.

        // Sub1 => user1.
        // Sub2 => user2.
        $this->create_forum_sub($this->forum1->id, $this->user1->id);
        $this->create_forum_sub($this->forum1->id, $this->user2->id);
        // Discussion1 => user1.
        // Discussion2 => user1.
        // Discussion3 => user2.
        list($discussion1, $discussion2, $discussion3) = $this->create_discussions_forum1();
        // Discussionsub1 => user1.
        // Discussionsub2 => user2.
        // Discussionsub3 => user1.
        // Discussionsub4 => user2.
        $this->create_discussion_sub($discussion1->id, $discussion1->forum, $this->user1->id);
        $this->create_discussion_sub($discussion1->id, $discussion1->forum, $this->user2->id);
        $this->create_discussion_sub($discussion2->id, $discussion2->forum, $this->user1->id);
        $this->create_discussion_sub($discussion3->id, $discussion3->forum, $this->user2->id);

        // Prepare data for FORUM 2 (course 1).
        // **************************************.

        // Sub1 => user1.
        // Sub2 => user2.
        $this->create_forum_sub($this->forum2->id, $this->user1->id);
        $this->create_forum_sub($this->forum2->id, $this->user2->id);
        // Discussion1 => user1.
        list($discussion1forum2) = $this->create_discussions_forum2();
        // Discussionsub1forum2 => user1.
        // Discussionsub2forum2 => user2.
        $this->create_discussion_sub($discussion1forum2->id, $discussion1forum2->forum, $this->user1->id);
        $this->create_discussion_sub($discussion1forum2->id, $discussion1forum2->forum, $this->user2->id);

        // Prepare data for FORUM 3 (course 2, category 2).
        // **************************************.

        // Sub1 => user1.
        // Sub2 => user2.
        $this->create_forum_sub($this->forum3->id, $this->user1->id);
        $this->create_forum_sub($this->forum3->id, $this->user2->id);
        // Discussion1 => user1.
        list($discussion1forum3) = $this->create_discussions_forum3();
        // Discussionsub1forum3 => user1.
        // Discussionsub2forum3 => user2.
        $this->create_discussion_sub($discussion1forum3->id, $discussion1forum3->forum, $this->user1->id);
        $this->create_discussion_sub($discussion1forum3->id, $discussion1forum3->forum, $this->user2->id);

        // DO COUNT.
        // **************************************.

        // Export user 1.
        $targetuser = new target_user($this->user1);
        $result = subscriptions::execute_export($targetuser, context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(3, $result->data['forum_subscriptions']);
        $this->assertCount(4, $result->data["discussion_subscriptions"]);

        $result = subscriptions::execute_export($targetuser, context_coursecat::instance($this->cat1->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(2, $result->data['forum_subscriptions']);
        $this->assertCount(3, $result->data["discussion_subscriptions"]);

        $result = subscriptions::execute_export($targetuser, context_coursecat::instance($this->cat2->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(1, $result->data['forum_subscriptions']);
        $this->assertCount(1, $result->data["discussion_subscriptions"]);

        $result = subscriptions::execute_export($targetuser, context_course::instance($this->course1->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(2, $result->data['forum_subscriptions']);
        $this->assertCount(3, $result->data["discussion_subscriptions"]);

        $result = subscriptions::execute_export($targetuser, context_course::instance($this->course2->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(1, $result->data['forum_subscriptions']);
        $this->assertCount(1, $result->data["discussion_subscriptions"]);

        $result = subscriptions::execute_export($targetuser, context_module::instance($this->forum1->cmid));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(1, $result->data['forum_subscriptions']);
        $this->assertCount(2, $result->data["discussion_subscriptions"]);

        $result = subscriptions::execute_export($targetuser, context_module::instance($this->forum2->cmid));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(1, $result->data['forum_subscriptions']);
        $this->assertCount(1, $result->data["discussion_subscriptions"]);

        $result = subscriptions::execute_export($targetuser, context_module::instance($this->forum3->cmid));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(1, $result->data['forum_subscriptions']);
        $this->assertCount(1, $result->data["discussion_subscriptions"]);
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
     * @param int $forumid
     * @param int $userid
     * @return stdClass
     */
    private function create_forum_sub(int $forumid, int $userid): stdClass {
        return $this->generator->create_subscription([
            'course' => 'notused',
            'forum' => $forumid,
            'userid' => $userid
        ]);
    }

    /**
     * @param int $forumid
     * @param int $userid
     * @return stdClass
     */
    private function create_discussion_sub(int $discussionid, int $forumid, int $userid): stdClass {
        return $this->generator->create_discussion_subscription([
            'discussion' => $discussionid,
            'forum' => $forumid,
            'userid' => $userid
        ]);
    }

    /**
     * Discussion 1 => user 1.
     *
     * @return array(discussion1)
     */
    private function create_discussions_forum2() {
        // Add a discussion in another forum.
        $record = ['course' => $this->course1->id, 'forum' => $this->forum2->id, 'userid' => $this->user1->id];
        // User 1 opens two new discussions.
        $discussion1 = $this->generator->create_discussion($record);

        return [$discussion1];
    }

    /**
     * Discussion 1 => user 1.
     *
     * @return array(discussion1)
     */
    private function create_discussions_forum3() {
        // Add a discussion in another forum.
        $record = ['course' => $this->course2->id, 'forum' => $this->forum3->id, 'userid' => $this->user1->id];
        // User 1 opens two new discussions.
        $discussion1 = $this->generator->create_discussion($record);

        return [$discussion1];
    }

}
