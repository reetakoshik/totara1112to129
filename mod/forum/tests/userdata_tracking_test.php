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
class mod_forum_userdata_tracking_testcase extends advanced_testcase {

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

        // Track1id => user1.
        // Track2id => user1.
        // Track3id => user1.
        // Track4id => user2.
        // Track5id => user2.
        $record = (object)['forumid' => $this->forum1->id, 'userid' => $this->user1->id];
        $track1id = $DB->insert_record('forum_track_prefs', $record);
        $record = (object)['forumid' => $this->forum2->id, 'userid' => $this->user1->id];
        $track2id = $DB->insert_record('forum_track_prefs', $record);
        $record = (object)['forumid' => $this->forum3->id, 'userid' => $this->user1->id];
        $track3id = $DB->insert_record('forum_track_prefs', $record);
        $record = (object)['forumid' => $this->forum1->id, 'userid' => $this->user2->id];
        $track4id = $DB->insert_record('forum_track_prefs', $record);
        $record = (object)['forumid' => $this->forum2->id, 'userid' => $this->user2->id];
        $track5id = $DB->insert_record('forum_track_prefs', $record);

        // Discussion1 => user1.
        // Discussion2 => user1.
        // Discussion3 => user2.
        list($discussion1, $discussion2, $discussion3) = $this->create_discussions_forum1();
        $post1 = $DB->get_record('forum_posts', ['discussion' => $discussion1->id, 'parent' => 0]);
        $post2 = $DB->get_record('forum_posts', ['discussion' => $discussion2->id, 'parent' => 0]);
        $post3 = $DB->get_record('forum_posts', ['discussion' => $discussion3->id, 'parent' => 0]);

        // Post1 read => user1.
        // Post3 read => user1.
        $read1 = $this->create_forum_read($post1, $this->user1->id);
        $read2 = $this->create_forum_read($post3, $this->user1->id);

        // Post 1 read => user2.
        // Post 2 read => user2.
        $read3 = $this->create_forum_read($post1, $this->user2->id);
        $read4 = $this->create_forum_read($post2, $this->user2->id);

        // Prepare data for FORUM 2 (course 1).
        // **************************************.

        // Discussion1 => user1.
        list($discussion1forum2) = $this->create_discussions_forum2();
        $post1forum2 = $DB->get_record('forum_posts', ['discussion' => $discussion1forum2->id, 'parent' => 0]);

        $read1forum2 = $this->create_forum_read($post1forum2, $this->user1->id);
        $read2forum2 = $this->create_forum_read($post1forum2, $this->user2->id);

        // Prepare data for FORUM 3 (course 2, category 2).
        // **************************************.

        // Discussion1 => user1.
        list($discussion1forum3) = $this->create_discussions_forum3();
        $post1forum3 = $DB->get_record('forum_posts', ['discussion' => $discussion1forum3->id, 'parent' => 0]);

        $read1forum3 = $this->create_forum_read($post1forum3, $this->user1->id);
        $read2forum3 = $this->create_forum_read($post1forum3, $this->user2->id);

        // DO PURGE.
        // **************************************.

        $targetuser = new target_user($this->user1);
        // Purge data.
        $result = tracking::execute_purge($targetuser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        // Check result for FORUM 1.
        // **************************************.

        $this->assertEmpty($DB->get_record('forum_read', ['id' => $read1->id]));
        $this->assertEmpty($DB->get_record('forum_read', ['id' => $read2->id]));

        $this->assertNotEmpty($DB->get_record('forum_read', ['id' => $read3->id]));
        $this->assertNotEmpty($DB->get_record('forum_read', ['id' => $read4->id]));

        // Check result for FORUM 2.
        // **************************************.

        $this->assertEmpty($DB->get_record('forum_read', ['id' => $read1forum2->id]));
        $this->assertNotEmpty($DB->get_record('forum_read', ['id' => $read2forum2->id]));

        // Check result for FORUM 3.
        // **************************************.

        $this->assertEmpty($DB->get_record('forum_read', ['id' => $read1forum3->id]));
        $this->assertNotEmpty($DB->get_record('forum_read', ['id' => $read2forum3->id]));

        // Entries for user1 are purged.
        $this->assertEmpty($DB->get_record('forum_track_prefs', ['id' => $track1id]));
        $this->assertEmpty($DB->get_record('forum_track_prefs', ['id' => $track2id]));
        $this->assertEmpty($DB->get_record('forum_track_prefs', ['id' => $track3id]));
        // Entries for user2 are still there.
        $this->assertNotEmpty($DB->get_record('forum_track_prefs', ['id' => $track4id]));
        $this->assertNotEmpty($DB->get_record('forum_track_prefs', ['id' => $track5id]));
    }

    /**
     * test if forum posts are purged properly in the course category context
     */
    public function test_purge_coursecat() {
        global $DB;

        // Track1id => user1.
        // Track2id => user1.
        // Track3id => user1.
        // Track4id => user2.
        // Track5id => user2.
        $record = (object)['forumid' => $this->forum1->id, 'userid' => $this->user1->id];
        $track1id = $DB->insert_record('forum_track_prefs', $record);
        $record = (object)['forumid' => $this->forum2->id, 'userid' => $this->user1->id];
        $track2id = $DB->insert_record('forum_track_prefs', $record);
        $record = (object)['forumid' => $this->forum3->id, 'userid' => $this->user1->id];
        $track3id = $DB->insert_record('forum_track_prefs', $record);
        $record = (object)['forumid' => $this->forum1->id, 'userid' => $this->user2->id];
        $track4id = $DB->insert_record('forum_track_prefs', $record);
        $record = (object)['forumid' => $this->forum2->id, 'userid' => $this->user2->id];
        $track5id = $DB->insert_record('forum_track_prefs', $record);

        // Discussion1 => user1.
        // Discussion2 => user1.
        // Discussion3 => user2.
        list($discussion1, $discussion2, $discussion3) = $this->create_discussions_forum1();
        $post1 = $DB->get_record('forum_posts', ['discussion' => $discussion1->id, 'parent' => 0]);
        $post2 = $DB->get_record('forum_posts', ['discussion' => $discussion2->id, 'parent' => 0]);
        $post3 = $DB->get_record('forum_posts', ['discussion' => $discussion3->id, 'parent' => 0]);

        // Post1 read => user1.
        // Post3 read => user1.
        $read1 = $this->create_forum_read($post1, $this->user1->id);
        $read2 = $this->create_forum_read($post3, $this->user1->id);

        // Post 1 read => user2.
        // Post 2 read => user2.
        $read3 = $this->create_forum_read($post1, $this->user2->id);
        $read4 = $this->create_forum_read($post2, $this->user2->id);

        // Prepare data for FORUM 2 (course 1).
        // **************************************.

        // Discussion1 => user1.
        list($discussion1forum2) = $this->create_discussions_forum2();
        $post1forum2 = $DB->get_record('forum_posts', ['discussion' => $discussion1forum2->id, 'parent' => 0]);

        $read1forum2 = $this->create_forum_read($post1forum2, $this->user1->id);
        $read2forum2 = $this->create_forum_read($post1forum2, $this->user2->id);

        // Prepare data for FORUM 3 (course 2, category 2).
        // **************************************.

        // Discussion1 => user1.
        list($discussion1forum3) = $this->create_discussions_forum3();
        $post1forum3 = $DB->get_record('forum_posts', ['discussion' => $discussion1forum3->id, 'parent' => 0]);

        $read1forum3 = $this->create_forum_read($post1forum3, $this->user1->id);
        $read2forum3 = $this->create_forum_read($post1forum3, $this->user2->id);

        // DO PURGE.
        // **************************************.

        $targetuser = new target_user($this->user1);
        // Purge data.
        $result = tracking::execute_purge($targetuser, context_coursecat::instance($this->cat1->id));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        // Check result for FORUM 1.
        // **************************************.

        $this->assertEmpty($DB->get_record('forum_read', ['id' => $read1->id]));
        $this->assertEmpty($DB->get_record('forum_read', ['id' => $read2->id]));

        $this->assertNotEmpty($DB->get_record('forum_read', ['id' => $read3->id]));
        $this->assertNotEmpty($DB->get_record('forum_read', ['id' => $read4->id]));

        // Check result for FORUM 2.
        // **************************************.

        $this->assertEmpty($DB->get_record('forum_read', ['id' => $read1forum2->id]));
        $this->assertNotEmpty($DB->get_record('forum_read', ['id' => $read2forum2->id]));

        // Check result for FORUM 3.
        // **************************************.

        $this->assertNotEmpty($DB->get_record('forum_read', ['id' => $read1forum3->id]));
        $this->assertNotEmpty($DB->get_record('forum_read', ['id' => $read2forum3->id]));

        // Entries for user1 and forum1 are purged.
        $this->assertEmpty($DB->get_record('forum_track_prefs', ['id' => $track1id]));
        $this->assertEmpty($DB->get_record('forum_track_prefs', ['id' => $track2id]));
        // Entries for forum3 are still there.
        $this->assertNotEmpty($DB->get_record('forum_track_prefs', ['id' => $track3id]));
        $this->assertNotEmpty($DB->get_record('forum_track_prefs', ['id' => $track4id]));
        $this->assertNotEmpty($DB->get_record('forum_track_prefs', ['id' => $track5id]));
    }

    /**
     * test if forum posts are purged properly in the course context
     */
    public function test_purge_course() {
        global $DB;

        // Track1id => user1.
        // Track2id => user1.
        // Track3id => user1.
        // Track4id => user2.
        // Track5id => user2.
        $record = (object)['forumid' => $this->forum1->id, 'userid' => $this->user1->id];
        $track1id = $DB->insert_record('forum_track_prefs', $record);
        $record = (object)['forumid' => $this->forum2->id, 'userid' => $this->user1->id];
        $track2id = $DB->insert_record('forum_track_prefs', $record);
        $record = (object)['forumid' => $this->forum3->id, 'userid' => $this->user1->id];
        $track3id = $DB->insert_record('forum_track_prefs', $record);
        $record = (object)['forumid' => $this->forum1->id, 'userid' => $this->user2->id];
        $track4id = $DB->insert_record('forum_track_prefs', $record);
        $record = (object)['forumid' => $this->forum2->id, 'userid' => $this->user2->id];
        $track5id = $DB->insert_record('forum_track_prefs', $record);

        // Discussion1 => user1.
        // Discussion2 => user1.
        // Discussion3 => user2.
        list($discussion1, $discussion2, $discussion3) = $this->create_discussions_forum1();
        $post1 = $DB->get_record('forum_posts', ['discussion' => $discussion1->id, 'parent' => 0]);
        $post2 = $DB->get_record('forum_posts', ['discussion' => $discussion2->id, 'parent' => 0]);
        $post3 = $DB->get_record('forum_posts', ['discussion' => $discussion3->id, 'parent' => 0]);

        // Post1 read => user1.
        // Post3 read => user1.
        $read1 = $this->create_forum_read($post1, $this->user1->id);
        $read2 = $this->create_forum_read($post3, $this->user1->id);

        // Post 1 read => user2.
        // Post 2 read => user2.
        $read3 = $this->create_forum_read($post1, $this->user2->id);
        $read4 = $this->create_forum_read($post2, $this->user2->id);

        // Prepare data for FORUM 2 (course 1).
        // **************************************.

        // Discussion1 => user1.
        list($discussion1forum2) = $this->create_discussions_forum2();
        $post1forum2 = $DB->get_record('forum_posts', ['discussion' => $discussion1forum2->id, 'parent' => 0]);

        $read1forum2 = $this->create_forum_read($post1forum2, $this->user1->id);
        $read2forum2 = $this->create_forum_read($post1forum2, $this->user2->id);

        // Prepare data for FORUM 3 (course 2, category 2).
        // **************************************.

        // Discussion1 => user1.
        list($discussion1forum3) = $this->create_discussions_forum3();
        $post1forum3 = $DB->get_record('forum_posts', ['discussion' => $discussion1forum3->id, 'parent' => 0]);

        $read1forum3 = $this->create_forum_read($post1forum3, $this->user1->id);
        $read2forum3 = $this->create_forum_read($post1forum3, $this->user2->id);

        // DO PURGE.
        // **************************************.

        $targetuser = new target_user($this->user1);
        // Purge data.
        $result = tracking::execute_purge($targetuser, context_course::instance($this->course2->id));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        // Check result for FORUM 1.
        // **************************************.

        $this->assertNotEmpty($DB->get_record('forum_read', ['id' => $read1->id]));
        $this->assertNotEmpty($DB->get_record('forum_read', ['id' => $read2->id]));
        $this->assertNotEmpty($DB->get_record('forum_read', ['id' => $read3->id]));
        $this->assertNotEmpty($DB->get_record('forum_read', ['id' => $read4->id]));

        // Check result for FORUM 2.
        // **************************************.

        $this->assertNotEmpty($DB->get_record('forum_read', ['id' => $read1forum2->id]));
        $this->assertNotEmpty($DB->get_record('forum_read', ['id' => $read2forum2->id]));

        // Check result for FORUM 3.
        // **************************************.

        $this->assertEmpty($DB->get_record('forum_read', ['id' => $read1forum3->id]));
        $this->assertNotEmpty($DB->get_record('forum_read', ['id' => $read2forum3->id]));

        // Course 2 (= forum3) entries are purged.
        $this->assertEmpty($DB->get_record('forum_track_prefs', ['id' => $track3id]));
        // Entries for course1 (forum1 and forum2) are still there.
        $this->assertNotEmpty($DB->get_record('forum_track_prefs', ['id' => $track1id]));
        $this->assertNotEmpty($DB->get_record('forum_track_prefs', ['id' => $track2id]));
        $this->assertNotEmpty($DB->get_record('forum_track_prefs', ['id' => $track4id]));
        $this->assertNotEmpty($DB->get_record('forum_track_prefs', ['id' => $track5id]));
    }

    /**
     * test if forum posts are purged properly in the module context
     */
    public function test_purge_module() {
        global $DB;

        // Track1id => user1.
        // Track2id => user1.
        // Track3id => user1.
        // Track4id => user2.
        // Track5id => user2.
        $record = (object)['forumid' => $this->forum1->id, 'userid' => $this->user1->id];
        $track1id = $DB->insert_record('forum_track_prefs', $record);
        $record = (object)['forumid' => $this->forum2->id, 'userid' => $this->user1->id];
        $track2id = $DB->insert_record('forum_track_prefs', $record);
        $record = (object)['forumid' => $this->forum3->id, 'userid' => $this->user1->id];
        $track3id = $DB->insert_record('forum_track_prefs', $record);
        $record = (object)['forumid' => $this->forum1->id, 'userid' => $this->user2->id];
        $track4id = $DB->insert_record('forum_track_prefs', $record);
        $record = (object)['forumid' => $this->forum2->id, 'userid' => $this->user2->id];
        $track5id = $DB->insert_record('forum_track_prefs', $record);

        // Discussion1 => user1.
        // Discussion2 => user1.
        // Discussion3 => user2.
        list($discussion1, $discussion2, $discussion3) = $this->create_discussions_forum1();
        $post1 = $DB->get_record('forum_posts', ['discussion' => $discussion1->id, 'parent' => 0]);
        $post2 = $DB->get_record('forum_posts', ['discussion' => $discussion2->id, 'parent' => 0]);
        $post3 = $DB->get_record('forum_posts', ['discussion' => $discussion3->id, 'parent' => 0]);

        // Post1 read => user1.
        // Post3 read => user1.
        $read1 = $this->create_forum_read($post1, $this->user1->id);
        $read2 = $this->create_forum_read($post3, $this->user1->id);

        // Post 1 read => user2.
        // Post 2 read => user2.
        $read3 = $this->create_forum_read($post1, $this->user2->id);
        $read4 = $this->create_forum_read($post2, $this->user2->id);

        // Prepare data for FORUM 2 (course 1).
        // **************************************.

        // Discussion1 => user1.
        list($discussion1forum2) = $this->create_discussions_forum2();
        $post1forum2 = $DB->get_record('forum_posts', ['discussion' => $discussion1forum2->id, 'parent' => 0]);

        $read1forum2 = $this->create_forum_read($post1forum2, $this->user1->id);
        $read2forum2 = $this->create_forum_read($post1forum2, $this->user2->id);

        // Prepare data for FORUM 3 (course 2, category 2).
        // **************************************.

        // Discussion1 => user1.
        list($discussion1forum3) = $this->create_discussions_forum3();
        $post1forum3 = $DB->get_record('forum_posts', ['discussion' => $discussion1forum3->id, 'parent' => 0]);

        $read1forum3 = $this->create_forum_read($post1forum3, $this->user1->id);
        $read2forum3 = $this->create_forum_read($post1forum3, $this->user2->id);

        // DO PURGE.
        // **************************************.

        $targetuser = new target_user($this->user1);
        // Purge data.
        $result = tracking::execute_purge($targetuser, context_module::instance($this->forum2->cmid));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        // Check result for FORUM 1.
        // **************************************.

        $this->assertNotEmpty($DB->get_record('forum_read', ['id' => $read1->id]));
        $this->assertNotEmpty($DB->get_record('forum_read', ['id' => $read2->id]));
        $this->assertNotEmpty($DB->get_record('forum_read', ['id' => $read3->id]));
        $this->assertNotEmpty($DB->get_record('forum_read', ['id' => $read4->id]));

        // Check result for FORUM 2.
        // **************************************.

        $this->assertEmpty($DB->get_record('forum_read', ['id' => $read1forum2->id]));
        $this->assertNotEmpty($DB->get_record('forum_read', ['id' => $read2forum2->id]));

        // Check result for FORUM 3.
        // **************************************.

        $this->assertNotEmpty($DB->get_record('forum_read', ['id' => $read1forum3->id]));
        $this->assertNotEmpty($DB->get_record('forum_read', ['id' => $read2forum3->id]));

        // Course 2 (= forum2) entries are purged.
        $this->assertEmpty($DB->get_record('forum_track_prefs', ['id' => $track2id]));
        // Entries for forum1 and forum3 are still there.
        $this->assertNotEmpty($DB->get_record('forum_track_prefs', ['id' => $track1id]));
        $this->assertNotEmpty($DB->get_record('forum_track_prefs', ['id' => $track3id]));
        $this->assertNotEmpty($DB->get_record('forum_track_prefs', ['id' => $track4id]));
        $this->assertNotEmpty($DB->get_record('forum_track_prefs', ['id' => $track5id]));
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

        // Post1 read => user1.
        // Post3 read => user1.
        $this->create_forum_read($post1, $this->user1->id);
        $this->create_forum_read($post3, $this->user1->id);

        // Post 1 read => user2.
        // Post 2 read => user2.
        $this->create_forum_read($post1, $this->user2->id);
        $this->create_forum_read($post2, $this->user2->id);

        // Prepare data for FORUM 2 (course 1).
        // **************************************.

        // Discussion1 => user1.
        list($discussion1forum2) = $this->create_discussions_forum2();
        $post1forum2 = $DB->get_record('forum_posts', ['discussion' => $discussion1forum2->id, 'parent' => 0]);

        $this->create_forum_read($post1forum2, $this->user1->id);
        $this->create_forum_read($post1forum2, $this->user2->id);

        // Prepare data for FORUM 3 (course 2, category 2).
        // **************************************.

        // Discussion1 => user1.
        list($discussion1forum3) = $this->create_discussions_forum3();
        $post1forum3 = $DB->get_record('forum_posts', ['discussion' => $discussion1forum3->id, 'parent' => 0]);

        $this->create_forum_read($post1forum3, $this->user1->id);
        $this->create_forum_read($post1forum3, $this->user2->id);

        // DO COUNT.
        // **************************************.

        // Count data.
        $targetuser = new target_user($this->user1);
        $result = tracking::execute_count($targetuser, context_system::instance());
        $this->assertEquals(4, $result);

        // Context course category.
        $result = tracking::execute_count($targetuser, context_coursecat::instance($this->cat1->id));
        $this->assertEquals(3, $result);
        $result = tracking::execute_count($targetuser, context_coursecat::instance($this->cat2->id));
        $this->assertEquals(1, $result);

        // Context course.
        $result = tracking::execute_count($targetuser, context_course::instance($this->course1->id));
        $this->assertEquals(3, $result);
        $result = tracking::execute_count($targetuser, context_course::instance($this->course2->id));
        $this->assertEquals(1, $result);

        // Context module.
        $result = tracking::execute_count($targetuser, context_module::instance($this->forum1->cmid));
        $this->assertEquals(2, $result);
        $result = tracking::execute_count($targetuser, context_module::instance($this->forum2->cmid));
        $this->assertEquals(1, $result);
        $result = tracking::execute_count($targetuser, context_module::instance($this->forum3->cmid));
        $this->assertEquals(1, $result);

        // User 2.
        $targetuser = new target_user($this->user2);
        $result = tracking::execute_count($targetuser, context_system::instance());
        $this->assertEquals(4, $result);

        // Context course category.
        $result = tracking::execute_count($targetuser, context_coursecat::instance($this->cat1->id));
        $this->assertEquals(3, $result);
        $result = tracking::execute_count($targetuser, context_coursecat::instance($this->cat2->id));
        $this->assertEquals(1, $result);

        // Context course.
        $result = tracking::execute_count($targetuser, context_course::instance($this->course1->id));
        $this->assertEquals(3, $result);
        $result = tracking::execute_count($targetuser, context_course::instance($this->course2->id));
        $this->assertEquals(1, $result);

        // Context module.
        $result = tracking::execute_count($targetuser, context_module::instance($this->forum1->cmid));
        $this->assertEquals(2, $result);
        $result = tracking::execute_count($targetuser, context_module::instance($this->forum2->cmid));
        $this->assertEquals(1, $result);
        $result = tracking::execute_count($targetuser, context_module::instance($this->forum3->cmid));
        $this->assertEquals(1, $result);
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

        // Post1 read => user1.
        // Post3 read => user1.
        $read1 = $this->create_forum_read($post1, $this->user1->id);
        $read2 = $this->create_forum_read($post3, $this->user1->id);

        // Post 1 read => user2.
        // Post 2 read => user2.
        $read3 = $this->create_forum_read($post1, $this->user2->id);
        $read4 = $this->create_forum_read($post2, $this->user2->id);

        // Prepare data for FORUM 2 (course 1).
        // **************************************.

        // Discussion1 => user1.
        list($discussion1forum2) = $this->create_discussions_forum2();
        $post1forum2 = $DB->get_record('forum_posts', ['discussion' => $discussion1forum2->id, 'parent' => 0]);

        $read1forum2 = $this->create_forum_read($post1forum2, $this->user1->id);
        $read2forum2 = $this->create_forum_read($post1forum2, $this->user2->id);

        // Prepare data for FORUM 3 (course 2, category 2).
        // **************************************.

        // Discussion1 => user1.
        list($discussion1forum3) = $this->create_discussions_forum3();
        $post1forum3 = $DB->get_record('forum_posts', ['discussion' => $discussion1forum3->id, 'parent' => 0]);

        $read1forum3 = $this->create_forum_read($post1forum3, $this->user1->id);
        $read2forum3 = $this->create_forum_read($post1forum3, $this->user2->id);

        // DO Export.
        // **************************************.

        // Export user 1.
        $targetuser = new target_user($this->user1);
        $result = tracking::execute_export($targetuser, context_system::instance());

        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(4, $result->data);
        $exportedids = [];
        foreach ($result->data as $data) {
            $this->assertObjectHasAttribute('id', $data);
            $this->assertObjectHasAttribute('discussionid', $data);
            $this->assertObjectHasAttribute('forumid', $data);
            $this->assertObjectHasAttribute('postid', $data);
            $this->assertEquals($this->user1->id, $data->userid);
            $exportedids[] = $data->id;
        }
        $this->assertContains($read1->id, $exportedids);
        $this->assertContains($read2->id, $exportedids);
        $this->assertContains($read1forum2->id, $exportedids);
        $this->assertContains($read1forum3->id, $exportedids);

        // Export user 2.
        $targetuser = new target_user($this->user2);
        $result = tracking::execute_export($targetuser, context_system::instance());

        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(4, $result->data);
        $exportedids = [];
        foreach ($result->data as $data) {
            $this->assertObjectHasAttribute('id', $data);
            $this->assertObjectHasAttribute('discussionid', $data);
            $this->assertObjectHasAttribute('forumid', $data);
            $this->assertObjectHasAttribute('postid', $data);
            $this->assertEquals($this->user2->id, $data->userid);
            $exportedids[] = $data->id;
        }
        $this->assertContains($read3->id, $exportedids);
        $this->assertContains($read4->id, $exportedids);
        $this->assertContains($read2forum2->id, $exportedids);
        $this->assertContains($read2forum3->id, $exportedids);
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

        // Post1 read => user1.
        // Post3 read => user1.
        $this->create_forum_read($post1, $this->user1->id);
        $this->create_forum_read($post3, $this->user1->id);

        // Post 1 read => user2.
        // Post 2 read => user2.
        $this->create_forum_read($post1, $this->user2->id);
        $this->create_forum_read($post2, $this->user2->id);

        // Prepare data for FORUM 2 (course 1).
        // **************************************.

        // Discussion1 => user1.
        list($discussion1forum2) = $this->create_discussions_forum2();
        $post1forum2 = $DB->get_record('forum_posts', ['discussion' => $discussion1forum2->id, 'parent' => 0]);

        $this->create_forum_read($post1forum2, $this->user1->id);
        $this->create_forum_read($post1forum2, $this->user2->id);

        // Prepare data for FORUM 3 (course 2, category 2).
        // **************************************.

        // Discussion1 => user1.
        list($discussion1forum3) = $this->create_discussions_forum3();
        $post1forum3 = $DB->get_record('forum_posts', ['discussion' => $discussion1forum3->id, 'parent' => 0]);

        $this->create_forum_read($post1forum3, $this->user1->id);
        $this->create_forum_read($post1forum3, $this->user2->id);

        // DO COUNT.
        // **************************************.

        // Export user 1.
        $targetuser = new target_user($this->user1);
        $result = tracking::execute_export($targetuser, context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(4, $result->data);

        $result = tracking::execute_export($targetuser, context_coursecat::instance($this->cat1->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(3, $result->data);

        $result = tracking::execute_export($targetuser, context_coursecat::instance($this->cat2->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(1, $result->data);

        $result = tracking::execute_export($targetuser, context_course::instance($this->course1->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(3, $result->data);

        $result = tracking::execute_export($targetuser, context_course::instance($this->course2->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(1, $result->data);

        $result = tracking::execute_export($targetuser, context_module::instance($this->forum1->cmid));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(2, $result->data);

        $result = tracking::execute_export($targetuser, context_module::instance($this->forum2->cmid));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(1, $result->data);

        $result = tracking::execute_export($targetuser, context_module::instance($this->forum3->cmid));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(1, $result->data);
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

        return [$discussion1];
    }

    /**
     * @param stdClass $post
     * @param int $userid
     *
     * @return stdClass
     */
    private function create_forum_read(stdClass $post, int $userid) {
        global $DB;

        $discussion = $DB->get_record('forum_discussions', ['id' => $post->discussion]);

        $record = (object)[
            'discussionid' => $post->discussion,
            'forumid' => $discussion->forum,
            'postid' => $post->id,
            'userid' => $userid,
            'firstread' => time(),
            'lastread' => time()
        ];
        $id = $DB->insert_record('forum_read', $record);

        return $DB->get_record('forum_read', ['id' => $id]);
    }

}
