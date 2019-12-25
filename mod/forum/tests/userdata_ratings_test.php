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
class mod_forum_userdata_ratings_testcase extends advanced_testcase {

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
     * test if forum ratings are purged properly in the system context
     */
    public function test_purge_system() {
        global $DB;

        // Discussion1 => user1.
        // Discussion2 => user1.
        // Discussion3 => user2.
        list($discussion1, $discussion2, $discussion3) = $this->create_discussions_forum1();
        $post1 = $DB->get_record('forum_posts', ['discussion' => $discussion1->id, 'parent' => 0]);
        $post2 = $DB->get_record('forum_posts', ['discussion' => $discussion2->id, 'parent' => 0]);
        $post3 = $DB->get_record('forum_posts', ['discussion' => $discussion3->id, 'parent' => 0]);

        $contextforum1 = context_module::instance($this->forum1->cmid);

        // Usually a user cannot rate his own posts. For the purging and exporting this does not matter.
        $rating1forum1 = $this->create_rating($contextforum1->id, $post1->id, $this->user1->id);
        $rating2forum1 = $this->create_rating($contextforum1->id, $post3->id, $this->user1->id);
        $rating3forum1 = $this->create_rating($contextforum1->id, $post1->id, $this->user2->id);
        $rating4forum1 = $this->create_rating($contextforum1->id, $post2->id, $this->user2->id);

        // Prepare data for FORUM 2 (course 1).
        // **************************************.

        // Discussion1 => user1.
        list($discussion1forum2) = $this->create_discussions_forum2();
        $post1forum2 = $DB->get_record('forum_posts', ['discussion' => $discussion1forum2->id, 'parent' => 0]);

        $contextforum2 = context_module::instance($this->forum2->cmid);

        $rating1forum2 = $this->create_rating($contextforum2->id, $post1forum2->id, $this->user1->id);
        $rating2forum2 = $this->create_rating($contextforum2->id, $post1forum2->id, $this->user2->id);

        // Prepare data for FORUM 3 (course 2, category 2).
        // **************************************.

        // Discussion1 => user1.
        list($discussion1forum3) = $this->create_discussions_forum3();
        $post1forum3 = $DB->get_record('forum_posts', ['discussion' => $discussion1forum3->id, 'parent' => 0]);

        $contextforum3 = context_module::instance($this->forum3->cmid);

        $rating1forum3 = $this->create_rating($contextforum3->id, $post1forum3->id, $this->user1->id);
        $rating2forum3 = $this->create_rating($contextforum3->id, $post1forum3->id, $this->user2->id);

        // DO PURGE.
        // **************************************.

        $targetuser = new target_user($this->user1);
        // Purge data.
        $result = ratings::execute_purge($targetuser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        // Check result for FORUM 1.
        // **************************************.

        // Entries for user1 are gone.
        $this->assertEmpty($DB->get_record('rating', ['id' => $rating1forum1->id]));
        $this->assertEmpty($DB->get_record('rating', ['id' => $rating2forum1->id]));
        // Entries for user2 are still there.
        $this->assertNotEmpty($DB->get_record('rating', ['id' => $rating3forum1->id]));
        $this->assertNotEmpty($DB->get_record('rating', ['id' => $rating4forum1->id]));

        // Check result for FORUM 2.
        // **************************************.

        // Entries for user1 are gone.
        $this->assertEmpty($DB->get_record('rating', ['id' => $rating1forum2->id]));
        // Entries for user2 are still there.
        $this->assertNotEmpty($DB->get_record('rating', ['id' => $rating2forum2->id]));

        // Check result for FORUM 3.
        // **************************************.

        // Entries for user1 are gone.
        $this->assertEmpty($DB->get_record('rating', ['id' => $rating1forum3->id]));
        // Entries for user2 are still there.
        $this->assertNotEmpty($DB->get_record('rating', ['id' => $rating2forum3->id]));
    }

    /**
     * test if forum posts are purged properly in the course category context
     */
    public function test_purge_coursecat() {
        global $DB;

        // Discussion1 => user1.
        // Discussion2 => user1.
        // Discussion3 => user2.
        list($discussion1, $discussion2, $discussion3) = $this->create_discussions_forum1();
        $post1 = $DB->get_record('forum_posts', ['discussion' => $discussion1->id, 'parent' => 0]);
        $post2 = $DB->get_record('forum_posts', ['discussion' => $discussion2->id, 'parent' => 0]);
        $post3 = $DB->get_record('forum_posts', ['discussion' => $discussion3->id, 'parent' => 0]);

        $contextforum1 = context_module::instance($this->forum1->cmid);

        // Usually a user cannot rate his own posts. For the purging and exporting this does not matter.
        $rating1forum1 = $this->create_rating($contextforum1->id, $post1->id, $this->user1->id);
        $rating2forum1 = $this->create_rating($contextforum1->id, $post3->id, $this->user1->id);
        $rating3forum1 = $this->create_rating($contextforum1->id, $post1->id, $this->user2->id);
        $rating4forum1 = $this->create_rating($contextforum1->id, $post2->id, $this->user2->id);

        // Prepare data for FORUM 2 (course 1).
        // **************************************.

        // Discussion1 => user1.
        list($discussion1forum2) = $this->create_discussions_forum2();
        $post1forum2 = $DB->get_record('forum_posts', ['discussion' => $discussion1forum2->id, 'parent' => 0]);

        $contextforum2 = context_module::instance($this->forum2->cmid);

        $rating1forum2 = $this->create_rating($contextforum2->id, $post1forum2->id, $this->user1->id);
        $rating2forum2 = $this->create_rating($contextforum2->id, $post1forum2->id, $this->user2->id);

        // Prepare data for FORUM 3 (course 2, category 2).
        // **************************************.

        // Discussion1 => user1.
        list($discussion1forum3) = $this->create_discussions_forum3();
        $post1forum3 = $DB->get_record('forum_posts', ['discussion' => $discussion1forum3->id, 'parent' => 0]);

        $contextforum3 = context_module::instance($this->forum3->cmid);

        $rating1forum3 = $this->create_rating($contextforum3->id, $post1forum3->id, $this->user1->id);
        $rating2forum3 = $this->create_rating($contextforum3->id, $post1forum3->id, $this->user2->id);

        // DO PURGE.
        // **************************************.

        $targetuser = new target_user($this->user1);
        // Purge data.
        $result = ratings::execute_purge($targetuser, context_coursecat::instance($this->cat1->id));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        // Check result for FORUM 1.
        // **************************************.

        // Entries for user1 are gone.
        $this->assertEmpty($DB->get_record('rating', ['id' => $rating1forum1->id]));
        $this->assertEmpty($DB->get_record('rating', ['id' => $rating2forum1->id]));
        // Entries for user2 are still there.
        $this->assertNotEmpty($DB->get_record('rating', ['id' => $rating3forum1->id]));
        $this->assertNotEmpty($DB->get_record('rating', ['id' => $rating4forum1->id]));

        // Check result for FORUM 2.
        // **************************************.

        // Entries for user1 are gone.
        $this->assertEmpty($DB->get_record('rating', ['id' => $rating1forum2->id]));
        // Entries for user2 are still there.
        $this->assertNotEmpty($DB->get_record('rating', ['id' => $rating2forum2->id]));

        // Check result for FORUM 3.
        // **************************************.

        // Entries for category2 are untouched.
        $this->assertNotEmpty($DB->get_record('rating', ['id' => $rating1forum3->id]));
        $this->assertNotEmpty($DB->get_record('rating', ['id' => $rating2forum3->id]));
    }

    /**
     * test if forum posts are purged properly in the course context
     */
    public function test_purge_course() {
        global $DB;

        // Discussion1 => user1.
        // Discussion2 => user1.
        // Discussion3 => user2.
        list($discussion1, $discussion2, $discussion3) = $this->create_discussions_forum1();
        $post1 = $DB->get_record('forum_posts', ['discussion' => $discussion1->id, 'parent' => 0]);
        $post2 = $DB->get_record('forum_posts', ['discussion' => $discussion2->id, 'parent' => 0]);
        $post3 = $DB->get_record('forum_posts', ['discussion' => $discussion3->id, 'parent' => 0]);

        $contextforum1 = context_module::instance($this->forum1->cmid);

        // Usually a user cannot rate his own posts. For the purging and exporting this does not matter.
        $rating1forum1 = $this->create_rating($contextforum1->id, $post1->id, $this->user1->id);
        $rating2forum1 = $this->create_rating($contextforum1->id, $post3->id, $this->user1->id);
        $rating3forum1 = $this->create_rating($contextforum1->id, $post1->id, $this->user2->id);
        $rating4forum1 = $this->create_rating($contextforum1->id, $post2->id, $this->user2->id);

        // Prepare data for FORUM 2 (course 1).
        // **************************************.

        // Discussion1 => user1.
        list($discussion1forum2) = $this->create_discussions_forum2();
        $post1forum2 = $DB->get_record('forum_posts', ['discussion' => $discussion1forum2->id, 'parent' => 0]);

        $contextforum2 = context_module::instance($this->forum2->cmid);

        $rating1forum2 = $this->create_rating($contextforum2->id, $post1forum2->id, $this->user1->id);
        $rating2forum2 = $this->create_rating($contextforum2->id, $post1forum2->id, $this->user2->id);

        // Prepare data for FORUM 3 (course 2, category 2).
        // **************************************.

        // Discussion1 => user1.
        list($discussion1forum3) = $this->create_discussions_forum3();
        $post1forum3 = $DB->get_record('forum_posts', ['discussion' => $discussion1forum3->id, 'parent' => 0]);

        $contextforum3 = context_module::instance($this->forum3->cmid);

        $rating1forum3 = $this->create_rating($contextforum3->id, $post1forum3->id, $this->user1->id);
        $rating2forum3 = $this->create_rating($contextforum3->id, $post1forum3->id, $this->user2->id);

        // DO PURGE.
        // **************************************.

        $targetuser = new target_user($this->user1);
        // Purge data.
        $result = ratings::execute_purge($targetuser, context_course::instance($this->course2->id));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        // Check result for FORUM 1.
        // **************************************.

        // Entries for category1 are untouched.
        $this->assertNotEmpty($DB->get_record('rating', ['id' => $rating1forum1->id]));
        $this->assertNotEmpty($DB->get_record('rating', ['id' => $rating2forum1->id]));
        $this->assertNotEmpty($DB->get_record('rating', ['id' => $rating3forum1->id]));
        $this->assertNotEmpty($DB->get_record('rating', ['id' => $rating4forum1->id]));

        // Check result for FORUM 2.
        // **************************************.

        // Entries for category1 are untouched.
        $this->assertNotEmpty($DB->get_record('rating', ['id' => $rating1forum2->id]));
        $this->assertNotEmpty($DB->get_record('rating', ['id' => $rating2forum2->id]));

        // Check result for FORUM 3.
        // **************************************.

        // Entries for user1 are gone.
        $this->assertEmpty($DB->get_record('rating', ['id' => $rating1forum3->id]));
        // Entries for user2 are still there.
        $this->assertNotEmpty($DB->get_record('rating', ['id' => $rating2forum3->id]));
    }

    /**
     * test if forum ratings are purged properly in the module context
     */
    public function test_purge_module() {
        global $DB;

        // Discussion1 => user1.
        // Discussion2 => user1.
        // Discussion3 => user2.
        list($discussion1, $discussion2, $discussion3) = $this->create_discussions_forum1();
        $post1 = $DB->get_record('forum_posts', ['discussion' => $discussion1->id, 'parent' => 0]);
        $post2 = $DB->get_record('forum_posts', ['discussion' => $discussion2->id, 'parent' => 0]);
        $post3 = $DB->get_record('forum_posts', ['discussion' => $discussion3->id, 'parent' => 0]);

        $contextforum1 = context_module::instance($this->forum1->cmid);

        // Usually a user cannot rate his own posts. For the purging and exporting this does not matter.
        $rating1forum1 = $this->create_rating($contextforum1->id, $post1->id, $this->user1->id);
        $rating2forum1 = $this->create_rating($contextforum1->id, $post3->id, $this->user1->id);
        $rating3forum1 = $this->create_rating($contextforum1->id, $post1->id, $this->user2->id);
        $rating4forum1 = $this->create_rating($contextforum1->id, $post2->id, $this->user2->id);

        // Prepare data for FORUM 2 (course 1).
        // **************************************.

        // Discussion1 => user1.
        list($discussion1forum2) = $this->create_discussions_forum2();
        $post1forum2 = $DB->get_record('forum_posts', ['discussion' => $discussion1forum2->id, 'parent' => 0]);

        $contextforum2 = context_module::instance($this->forum2->cmid);

        $rating1forum2 = $this->create_rating($contextforum2->id, $post1forum2->id, $this->user1->id);
        $rating2forum2 = $this->create_rating($contextforum2->id, $post1forum2->id, $this->user2->id);

        // Prepare data for FORUM 3 (course 2, category 2).
        // **************************************.

        // Discussion1 => user1.
        list($discussion1forum3) = $this->create_discussions_forum3();
        $post1forum3 = $DB->get_record('forum_posts', ['discussion' => $discussion1forum3->id, 'parent' => 0]);

        $contextforum3 = context_module::instance($this->forum3->cmid);

        $rating1forum3 = $this->create_rating($contextforum3->id, $post1forum3->id, $this->user1->id);
        $rating2forum3 = $this->create_rating($contextforum3->id, $post1forum3->id, $this->user2->id);

        // DO PURGE.
        // **************************************.

        $targetuser = new target_user($this->user1);
        // Purge data.
        $result = ratings::execute_purge($targetuser, context_module::instance($this->forum2->cmid));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        // Check result for FORUM 1.
        // **************************************.

        // Entries for this forum are untouched.
        $this->assertNotEmpty($DB->get_record('rating', ['id' => $rating1forum1->id]));
        $this->assertNotEmpty($DB->get_record('rating', ['id' => $rating2forum1->id]));
        $this->assertNotEmpty($DB->get_record('rating', ['id' => $rating3forum1->id]));
        $this->assertNotEmpty($DB->get_record('rating', ['id' => $rating4forum1->id]));

        // Check result for FORUM 2.
        // **************************************.

        // Entries for user1 are gone.
        $this->assertEmpty($DB->get_record('rating', ['id' => $rating1forum2->id]));
        // Entries for user2 are still there.
        $this->assertNotEmpty($DB->get_record('rating', ['id' => $rating2forum2->id]));

        // Check result for FORUM 3.
        // **************************************.

        // Entries for this forum are untouched.
        $this->assertNotEmpty($DB->get_record('rating', ['id' => $rating1forum3->id]));
        $this->assertNotEmpty($DB->get_record('rating', ['id' => $rating2forum3->id]));
    }

    /**
     * Test that count returns the correct values
     */
    public function test_count() {
        global $DB;

        // Discussion1 => user1.
        // Discussion2 => user1.
        // Discussion3 => user2.
        list($discussion1, $discussion2, $discussion3) = $this->create_discussions_forum1();
        $post1 = $DB->get_record('forum_posts', ['discussion' => $discussion1->id, 'parent' => 0]);
        $post2 = $DB->get_record('forum_posts', ['discussion' => $discussion2->id, 'parent' => 0]);
        $post3 = $DB->get_record('forum_posts', ['discussion' => $discussion3->id, 'parent' => 0]);

        $contextforum1 = context_module::instance($this->forum1->cmid);

        // Usually a user cannot rate his own posts. For the purging and exporting this does not matter.
        $this->create_rating($contextforum1->id, $post1->id, $this->user1->id);
        $this->create_rating($contextforum1->id, $post2->id, $this->user1->id);
        $this->create_rating($contextforum1->id, $post3->id, $this->user1->id);
        $this->create_rating($contextforum1->id, $post1->id, $this->user2->id);
        $this->create_rating($contextforum1->id, $post2->id, $this->user2->id);

        // Prepare data for FORUM 2 (course 1).
        // **************************************.

        // Discussion1 => user1.
        list($discussion1forum2) = $this->create_discussions_forum2();
        $post1forum2 = $DB->get_record('forum_posts', ['discussion' => $discussion1forum2->id, 'parent' => 0]);

        $contextforum2 = context_module::instance($this->forum2->cmid);

        $this->create_rating($contextforum2->id, $post1forum2->id, $this->user1->id);
        $this->create_rating($contextforum2->id, $post1forum2->id, $this->user2->id);

        // Prepare data for FORUM 3 (course 2, category 2).
        // **************************************.

        // Discussion1 => user1.
        list($discussion1forum3, $discussion2forum3) = $this->create_discussions_forum3();
        $post1forum3 = $DB->get_record('forum_posts', ['discussion' => $discussion1forum3->id, 'parent' => 0]);
        $post2forum3 = $DB->get_record('forum_posts', ['discussion' => $discussion2forum3->id, 'parent' => 0]);

        $contextforum3 = context_module::instance($this->forum3->cmid);

        $this->create_rating($contextforum3->id, $post1forum3->id, $this->user1->id);
        $this->create_rating($contextforum3->id, $post2forum3->id, $this->user1->id);
        $this->create_rating($contextforum3->id, $post1forum3->id, $this->user2->id);

        // DO COUNT.
        // **************************************.

        // Count data.
        $targetuser = new target_user($this->user1);
        $result = ratings::execute_count($targetuser, context_system::instance());
        $this->assertEquals(6, $result);

        // Context course category.
        $result = ratings::execute_count($targetuser, context_coursecat::instance($this->cat1->id));
        $this->assertEquals(4, $result);
        $result = ratings::execute_count($targetuser, context_coursecat::instance($this->cat2->id));
        $this->assertEquals(2, $result);

        // Context course.
        $result = ratings::execute_count($targetuser, context_course::instance($this->course1->id));
        $this->assertEquals(4, $result);
        $result = ratings::execute_count($targetuser, context_course::instance($this->course2->id));
        $this->assertEquals(2, $result);

        // Context module.
        $result = ratings::execute_count($targetuser, context_module::instance($this->forum1->cmid));
        $this->assertEquals(3, $result);
        $result = ratings::execute_count($targetuser, context_module::instance($this->forum2->cmid));
        $this->assertEquals(1, $result);
        $result = ratings::execute_count($targetuser, context_module::instance($this->forum3->cmid));
        $this->assertEquals(2, $result);
    }

    /**
     * Test that export returns the correct values
     */
    public function test_export() {
        global $DB;

        // Discussion1 => user1.
        // Discussion2 => user1.
        // Discussion3 => user2.
        list($discussion1, $discussion2, $discussion3) = $this->create_discussions_forum1();
        $post1 = $DB->get_record('forum_posts', ['discussion' => $discussion1->id, 'parent' => 0]);
        $post2 = $DB->get_record('forum_posts', ['discussion' => $discussion2->id, 'parent' => 0]);
        $post3 = $DB->get_record('forum_posts', ['discussion' => $discussion3->id, 'parent' => 0]);

        $contextforum1 = context_module::instance($this->forum1->cmid);

        // Usually a user cannot rate his own posts. For the purging and exporting this does not matter.
        $rating1forum1 = $this->create_rating($contextforum1->id, $post1->id, $this->user1->id);
        $rating2forum1 = $this->create_rating($contextforum1->id, $post3->id, $this->user1->id);
        $rating3forum1 = $this->create_rating($contextforum1->id, $post1->id, $this->user2->id);
        $rating4forum1 = $this->create_rating($contextforum1->id, $post2->id, $this->user2->id);

        // Prepare data for FORUM 2 (course 1).
        // **************************************.

        // Discussion1 => user1.
        list($discussion1forum2) = $this->create_discussions_forum2();
        $post1forum2 = $DB->get_record('forum_posts', ['discussion' => $discussion1forum2->id, 'parent' => 0]);

        $contextforum2 = context_module::instance($this->forum2->cmid);

        $rating1forum2 = $this->create_rating($contextforum2->id, $post1forum2->id, $this->user1->id);
        $rating2forum2 = $this->create_rating($contextforum2->id, $post1forum2->id, $this->user2->id);

        // Prepare data for FORUM 3 (course 2, category 2).
        // **************************************.

        // Discussion1 => user1.
        list($discussion1forum3) = $this->create_discussions_forum3();
        $post1forum3 = $DB->get_record('forum_posts', ['discussion' => $discussion1forum3->id, 'parent' => 0]);

        $contextforum3 = context_module::instance($this->forum3->cmid);

        $rating1forum3 = $this->create_rating($contextforum3->id, $post1forum3->id, $this->user1->id);
        $rating2forum3 = $this->create_rating($contextforum3->id, $post1forum3->id, $this->user2->id);

        // DO Export.
        // **************************************.

        // Export user 1.
        $targetuser = new target_user($this->user1);
        $result = ratings::execute_export($targetuser, context_system::instance());

        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(4, $result->data);
        $exportedids = [];
        foreach ($result->data as $data) {
            $this->assertObjectHasAttribute('id', $data);
            $this->assertObjectHasAttribute('component', $data);
            $this->assertObjectHasAttribute('ratingarea', $data);
            $this->assertObjectHasAttribute('itemid', $data);
            $this->assertObjectHasAttribute('scaleid', $data);
            $this->assertObjectHasAttribute('rating', $data);
            $this->assertEquals($this->user1->id, $data->userid);
            $this->assertEquals('mod_forum', $data->component);
            $this->assertEquals('post', $data->ratingarea);
            $exportedids[] = $data->id;
        }
        $this->assertContains($rating1forum1->id, $exportedids);
        $this->assertContains($rating2forum1->id, $exportedids);
        $this->assertContains($rating1forum2->id, $exportedids);
        $this->assertContains($rating1forum3->id, $exportedids);

        // Export user 2.
        $targetuser = new target_user($this->user2);
        $result = ratings::execute_export($targetuser, context_system::instance());

        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(4, $result->data);
        $exportedids = [];
        foreach ($result->data as $data) {
            $this->assertObjectHasAttribute('id', $data);
            $this->assertObjectHasAttribute('component', $data);
            $this->assertObjectHasAttribute('ratingarea', $data);
            $this->assertObjectHasAttribute('itemid', $data);
            $this->assertObjectHasAttribute('scaleid', $data);
            $this->assertObjectHasAttribute('rating', $data);
            $this->assertEquals($this->user2->id, $data->userid);
            $this->assertEquals('mod_forum', $data->component);
            $this->assertEquals('post', $data->ratingarea);
            $exportedids[] = $data->id;
        }
        $this->assertContains($rating3forum1->id, $exportedids);
        $this->assertContains($rating4forum1->id, $exportedids);
        $this->assertContains($rating2forum2->id, $exportedids);
        $this->assertContains($rating2forum3->id, $exportedids);
    }

    /**
     * Test that export returns the correct values
     */
    public function test_export_different_contexts() {
        global $DB;

        // Discussion1 => user1.
        // Discussion2 => user1.
        // Discussion3 => user2.
        list($discussion1, $discussion2, $discussion3) = $this->create_discussions_forum1();
        $post1 = $DB->get_record('forum_posts', ['discussion' => $discussion1->id, 'parent' => 0]);
        $post2 = $DB->get_record('forum_posts', ['discussion' => $discussion2->id, 'parent' => 0]);
        $post3 = $DB->get_record('forum_posts', ['discussion' => $discussion3->id, 'parent' => 0]);

        $contextforum1 = context_module::instance($this->forum1->cmid);

        // Usually a user cannot rate his own posts. For the purging and exporting this does not matter.
        $this->create_rating($contextforum1->id, $post1->id, $this->user1->id);
        $this->create_rating($contextforum1->id, $post2->id, $this->user1->id);
        $this->create_rating($contextforum1->id, $post3->id, $this->user1->id);
        $this->create_rating($contextforum1->id, $post1->id, $this->user2->id);
        $this->create_rating($contextforum1->id, $post2->id, $this->user2->id);

        // Prepare data for FORUM 2 (course 1).
        // **************************************.

        // Discussion1 => user1.
        list($discussion1forum2) = $this->create_discussions_forum2();
        $post1forum2 = $DB->get_record('forum_posts', ['discussion' => $discussion1forum2->id, 'parent' => 0]);

        $contextforum2 = context_module::instance($this->forum2->cmid);

        $this->create_rating($contextforum2->id, $post1forum2->id, $this->user1->id);
        $this->create_rating($contextforum2->id, $post1forum2->id, $this->user2->id);

        // Prepare data for FORUM 3 (course 2, category 2).
        // **************************************.

        // Discussion1 => user1.
        list($discussion1forum3, $discussion2forum3) = $this->create_discussions_forum3();
        $post1forum3 = $DB->get_record('forum_posts', ['discussion' => $discussion1forum3->id, 'parent' => 0]);
        $post2forum3 = $DB->get_record('forum_posts', ['discussion' => $discussion2forum3->id, 'parent' => 0]);

        $contextforum3 = context_module::instance($this->forum3->cmid);

        $this->create_rating($contextforum3->id, $post1forum3->id, $this->user1->id);
        $this->create_rating($contextforum3->id, $post2forum3->id, $this->user1->id);
        $this->create_rating($contextforum3->id, $post1forum3->id, $this->user2->id);

        // DO COUNT.
        // **************************************.

        // Export in system context.
        $targetuser = new target_user($this->user1);
        $result = ratings::execute_export($targetuser, context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(6, $result->data);
        foreach ($result->data as $data) {
            $this->assertEquals($this->user1->id, $data->userid);
        }

        // Export in course category context.
        $result = ratings::execute_export($targetuser, context_coursecat::instance($this->cat1->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(4, $result->data);
        foreach ($result->data as $data) {
            $this->assertEquals($this->user1->id, $data->userid);
        }
        $result = ratings::execute_export($targetuser, context_coursecat::instance($this->cat2->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(2, $result->data);
        foreach ($result->data as $data) {
            $this->assertEquals($this->user1->id, $data->userid);
        }

        // Export in system course.
        $result = ratings::execute_export($targetuser, context_course::instance($this->course1->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(4, $result->data);
        foreach ($result->data as $data) {
            $this->assertEquals($this->user1->id, $data->userid);
        }
        $result = ratings::execute_export($targetuser, context_course::instance($this->course2->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(2, $result->data);
        foreach ($result->data as $data) {
            $this->assertEquals($this->user1->id, $data->userid);
        }

        // Export in module context.
        $result = ratings::execute_export($targetuser, context_module::instance($this->forum1->cmid));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(3, $result->data);
        foreach ($result->data as $data) {
            $this->assertEquals($this->user1->id, $data->userid);
        }
        $result = ratings::execute_export($targetuser, context_module::instance($this->forum2->cmid));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(1, $result->data);
        foreach ($result->data as $data) {
            $this->assertEquals($this->user1->id, $data->userid);
        }
        $result = ratings::execute_export($targetuser, context_module::instance($this->forum3->cmid));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(2, $result->data);
        foreach ($result->data as $data) {
            $this->assertEquals($this->user1->id, $data->userid);
        }
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
        // Add a discussion in another forum.
        $record = ['course' => $this->course2->id, 'forum' => $this->forum3->id, 'userid' => $this->user2->id];
        // User 1 opens two new discussions.
        $discussion2 = $this->generator->create_discussion($record);

        return [$discussion1, $discussion2];
    }

    /**
     * Create and return a rating for the given post.
     *
     * @param int $contextid
     * @param int $postid
     * @param int $userid
     *
     * @return stdClass
     */
    private function create_rating(int $contextid, int $postid, int $userid) {
        global $DB;
        $ratingid = $DB->insert_record('rating', (object)[
            'contextid' => $contextid,
            'component' => 'mod_forum',
            'ratingarea' => 'post',
            'itemid' => $postid,
            'scaleid' => 100,
            'rating' => 100,
            'userid' => $userid,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        return $DB->get_record('rating', ['id' => $ratingid]);
    }

}
