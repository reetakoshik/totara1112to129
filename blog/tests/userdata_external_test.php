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
 * @package core_blog
 */

use core\event\blog_comment_deleted;
use core\event\blog_entry_deleted;
use core\event\blog_external_removed;
use core_blog\userdata\external;
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
class core_blog_userdata_external_testcase extends advanced_testcase {

    /**
     * test compatible context levels
     */
    public function test_compatible_context_levels() {
        $expectedcontextlevels = [CONTEXT_SYSTEM];
        $this->assertEquals($expectedcontextlevels, external::get_compatible_context_levels());
    }

    /**
     * Testing abilities, is_purgeable|countable|exportable()
     */
    public function test_abilities() {
        $this->assertTrue(external::is_countable());
        $this->assertTrue(external::is_exportable());
        $this->assertTrue(external::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(external::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(external::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * Create fixtures for our tests.
     */
    private function create_fixtures() {
        global $CFG, $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $fixtures = new class() {
            /** @var target_user */
            public $user, $controluser;
            /** @var \stdClass */
            public $blog1, $blog2, $blog3, $blog4, $blog5;
            /** @var \stdClass */
            public $comment1, $comment2, $comment3;
            /** @var \stdClass */
            public $blogentry1, $blogentry2, $blogentry3,
                $blogentry4, $blogentry5, $blogentry6,
                $blogentry7, $blogentry8;
        };

        $fixtures->user = new target_user($this->getDataGenerator()->create_user(['username' => 'user1']));
        $fixtures->controluser = new target_user($this->getDataGenerator()->create_user(['username' => 'controluser']));

        assign_capability('moodle/blog:view', CAP_ALLOW, $CFG->defaultuserroleid, context_system::instance(), true);

        $fixtures->blog1 = $this->create_blog($fixtures->user, 'Blog 1');
        $fixtures->blog2 = $this->create_blog($fixtures->user, 'Blog 2');
        $fixtures->blog3 = $this->create_blog($fixtures->controluser, 'Blog 3');
        $fixtures->blog4 = $this->create_blog($fixtures->controluser, 'Blog 4');
        $fixtures->blog5 = $this->create_blog($fixtures->controluser, 'Blog 5');

        $fixtures->blogentry1 = $this->create_blog_entry($fixtures->blog1);
        $fixtures->blogentry2 = $this->create_blog_entry($fixtures->blog2);
        $fixtures->blogentry3 = $this->create_blog_entry($fixtures->blog2);
        $fixtures->blogentry4 = $this->create_blog_entry($fixtures->blog3);
        $fixtures->blogentry5 = $this->create_blog_entry($fixtures->blog4);
        $fixtures->blogentry6 = $this->create_blog_entry($fixtures->blog4);
        $fixtures->blogentry6 = $this->create_blog_entry($fixtures->blog5);
        $fixtures->blogentry7 = $this->create_blog_entry($fixtures->blog5);
        $fixtures->blogentry8 = $this->create_blog_entry($fixtures->blog5);

        // Create comments on blog entries.
        $fixtures->comment1 = $this->create_comment($fixtures->user, $fixtures->blogentry1->id, 'Comment 1');
        $fixtures->comment2 = $this->create_comment($fixtures->user, $fixtures->blogentry2->id, 'Comment 2');
        $fixtures->comment3 = $this->create_comment($fixtures->user, $fixtures->blogentry2->id, 'Comment 3');
        $fixtures->comment4 = $this->create_comment($fixtures->controluser, $fixtures->blogentry1->id, 'Comment 4');
        $fixtures->comment5 = $this->create_comment($fixtures->controluser, $fixtures->blogentry2->id, 'Comment 5');

        $this->create_comment($fixtures->controluser, $fixtures->blogentry4->id, 'Comment 4');
        $this->create_comment($fixtures->controluser, $fixtures->blogentry5->id, 'Comment 5');
        $this->create_comment($fixtures->controluser, $fixtures->blogentry5->id, 'Comment 6');
        $this->create_comment($fixtures->controluser, $fixtures->blogentry7->id, 'Comment 7');
        $this->create_comment($fixtures->controluser, $fixtures->blogentry7->id, 'Comment 8');
        $this->create_comment($fixtures->controluser, $fixtures->blogentry8->id, 'Comment 9');

        // Create tags for the blogs.
        $context = context_user::instance($fixtures->user->id);
        core_tag_tag::set_item_tags('core', 'blog_external', $fixtures->blog1->id, $context, ['tag1', 'tag2']);

        $context = context_user::instance($fixtures->controluser->id);
        core_tag_tag::set_item_tags('core', 'blog_external', $fixtures->blog3->id, $context, ['tag1', 'tag2']);

        $this->assertEquals(2, $DB->count_records('blog_external', ['userid' => $fixtures->user->id]));
        $this->assertEquals(3, $DB->count_records('blog_external', ['userid' => $fixtures->controluser->id]));
        $this->assertEquals(3, $DB->count_records('post', ['userid' => $fixtures->user->id]));
        $this->assertEquals(6, $DB->count_records('post', ['userid' => $fixtures->controluser->id]));
        $this->assertEquals(3, $DB->count_records('comments', ['component' => 'blog', 'userid' => $fixtures->user->id]));
        $this->assertEquals(8, $DB->count_records('comments', ['component' => 'blog', 'userid' => $fixtures->controluser->id]));
        $this->assertCount(2, core_tag_tag::get_item_tags_array('core', 'blog_external', $fixtures->blog1->id));
        $this->assertCount(0, core_tag_tag::get_item_tags_array('core', 'blog_external', $fixtures->blog2->id));
        $this->assertCount(2, core_tag_tag::get_item_tags_array('core', 'blog_external', $fixtures->blog3->id));

        return $fixtures;
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge() {
        $fixtures = $this->create_fixtures();

        $sink = $this->redirectEvents();

        // Purge active user.
        $result = external::execute_purge($fixtures->user, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        // Assert that all data is purged.
        $this->assert_data_purged($fixtures);
        // Control user must not be affected.
        $this->assert_control_data_untouched($fixtures);
        // Assert that all expected events were fired.
        $this->assert_events_fired($fixtures, $sink->get_events());
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge_suspended_user() {
        $fixtures = $this->create_fixtures();
        $fixtures->user = new target_user($this->suspend_user($fixtures->user->id));

        $sink = $this->redirectEvents();

        // Purge active user.
        $result = external::execute_purge($fixtures->user, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        // Assert that all data is purged.
        $this->assert_data_purged($fixtures);
        // Control user must not be affected.
        $this->assert_control_data_untouched($fixtures);
        // Assert that all expected events were fired.
        $this->assert_events_fired($fixtures, $sink->get_events());
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge_deleted_user() {
        $fixtures = $this->create_fixtures();
        $fixtures->user = new target_user($this->delete_user($fixtures->user->id));

        $sink = $this->redirectEvents();

        // Purge active user.
        $result = external::execute_purge($fixtures->user, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        // Assert that all data is purged.
        $this->assert_data_purged($fixtures);
        // Control user must not be affected.
        $this->assert_control_data_untouched($fixtures);
        // Assert that all expected events were fired.
        $this->assert_events_fired($fixtures, $sink->get_events());
    }

    /**
     * @param object $fixtures
     * @param array $events
     */
    private function assert_events_fired($fixtures, array $events) {
        if ($fixtures->user->status == target_user::STATUS_DELETED) {
            $this->assertCount(5, $events);
        } else {
            $this->assertCount(10, $events);
        }

        // Comment events should not be fired.
        if ($fixtures->user->status == target_user::STATUS_DELETED) {
            $this->assertCount(0, array_filter($events, function($event) {
                return $event instanceof blog_comment_deleted;
            }));
        } else {
            $this->assertCount(5, array_filter($events, function($event) {
                return $event instanceof blog_comment_deleted;
            }));
        }

        $this->assertCount(3, array_filter($events, function($event) {
            return $event instanceof blog_entry_deleted;
        }));
        $this->assertCount(2, array_filter($events, function($event) {
            return $event instanceof blog_external_removed;
        }));
    }

    /**
     * @param $fixtures
     */
    private function assert_data_purged($fixtures) {
        global $DB;

        $params = ['userid' => $fixtures->user->id];
        // All external blogs should be gone.
        $this->assertEmpty($DB->get_records('blog_external', $params));
        $params['module'] = 'blog_external';
        // All external blog posts should be gone.
        $this->assertEmpty($DB->get_records('post', $params));
        // All comments made on these blog posts should be gone.
        $this->assertEmpty($DB->get_records('comments', ['component' => 'blog', 'itemid' => $fixtures->blog1->id]));
        $this->assertEmpty($DB->get_records('comments', ['component' => 'blog', 'itemid' => $fixtures->blog2->id]));
        // All tags set for the external blogs should be gone.
        $this->assertEmpty(core_tag_tag::get_item_tags_array('core', 'blog_external', $fixtures->blog1->id));
        $this->assertEmpty(core_tag_tag::get_item_tags_array('core', 'blog_external', $fixtures->blog2->id));
    }

    /**
     * @param $fixtures
     */
    private function assert_control_data_untouched($fixtures) {
        global $DB;

        $params = ['userid' => $fixtures->controluser->id];
        $this->assertEquals(3, $DB->count_records('blog_external', $params));
        $params['module'] = 'blog_external';
        $this->assertEquals(6, $DB->count_records('post', $params));
        $this->assertEquals(6, $DB->count_records('comments', ['component' => 'blog', 'userid' => $fixtures->controluser->id]));
        $this->assertCount(2, core_tag_tag::get_item_tags_array('core', 'blog_external', $fixtures->blog3->id));
    }

    /**
     * test if data is correctly counted
     */
    public function test_count() {
        $fixtures = $this->create_fixtures();

        // Do the count.
        $result = external::execute_count($fixtures->user, context_system::instance());
        $this->assertEquals(2, $result);

        $result = external::execute_count($fixtures->controluser, context_system::instance());
        $this->assertEquals(3, $result);

        // Purge data.
        external::execute_purge($fixtures->user, context_system::instance());

        $result = external::execute_count($fixtures->user, context_system::instance());
        $this->assertEquals(0, $result);
    }

    /**
     * test if data is correctly counted
     */
    public function test_export() {
        $fixtures = $this->create_fixtures();

        // Export data.
        $result = external::execute_export($fixtures->user, context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(2, $result->data);

        $exportedids = [];
        $exportedposts = [];
        $exportedtags = [];
        foreach ($result->data as $blog) {
            $this->assertArrayHasKey('id', $blog);
            $this->assertArrayHasKey('userid', $blog);
            $this->assertArrayHasKey('name', $blog);
            $this->assertArrayHasKey('description', $blog);
            $this->assertArrayHasKey('posts', $blog);
            $this->assertArrayHasKey('tags', $blog);

            $posts = $blog['posts'];
            foreach ($posts as $post) {
                $this->assertArrayHasKey('id', $post);
                $this->assertArrayHasKey('subject', $post);
                $this->assertArrayHasKey('summary', $post);
                $this->assertArrayHasKey('uniquehash', $post);
                $this->assertArrayHasKey('created', $post);
            }

            $this->assertEquals($fixtures->user->id, $blog['userid']);
            $exportedids[] = $blog['id'];
            $exportedposts[$blog['id']] = $blog['posts'];
            $exportedtags[$blog['id']] = $blog['tags'];
        }
        $this->assertContains($fixtures->blog1->id, $exportedids);
        $this->assertContains($fixtures->blog2->id, $exportedids);

        // Check if we have expected data for blog 1.
        $this->assertCount(2, $exportedtags[$fixtures->blog1->id]);
        $this->assertContains('tag1', $exportedtags[$fixtures->blog1->id]);
        $this->assertContains('tag2', $exportedtags[$fixtures->blog1->id]);

        $this->assertCount(1, $exportedposts[$fixtures->blog1->id]);
        $exportedpostids = array_column($exportedposts[$fixtures->blog1->id], 'id');
        $this->assertContains($fixtures->blogentry1->id, $exportedpostids);

        // Check if we have expected data for blog 2.
        $this->assertCount(0, $exportedtags[$fixtures->blog2->id]);

        $this->assertCount(2, $exportedposts[$fixtures->blog2->id]);
        $exportedpostids = array_column($exportedposts[$fixtures->blog2->id], 'id');
        $this->assertContains($fixtures->blogentry2->id, $exportedpostids);
        $this->assertContains($fixtures->blogentry3->id, $exportedpostids);
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

    /**
     * @param target_user $user
     * @param int $blogid
     * @param string $content
     * @return \stdClass
     */
    private function create_comment(target_user $user, int $blogid, string $content): \stdClass {
        global $USER;

        $olduser = clone $USER;

        $this->setUser($user->id);

        $commentareaparams = (object)[
            'itemid' => $blogid,
            'component' => 'blog',
            'context' => context_user::instance($user->id),
            'area' => 'format_blog'
        ];
        $commentarea = new comment($commentareaparams);
        $comment = $commentarea->add($content);

        $this->setUser($olduser);

        return $comment;
    }

    /**
     * @param target_user $user
     * @param string $blockname
     * @return \stdClass
     */
    private function create_blog(target_user $user, string $blockname): \stdClass {
        global $DB;

        $blogdata = (object)[
            'userid' => $user->id,
            'name' => $blockname,
            'description' => random_string(15),
            'url' => 'http://www.external.com/blog/index.rss'
        ];
        $blogid = $DB->insert_record('blog_external', $blogdata);

        return $DB->get_record('blog_external', ['id' => $blogid]);
    }

    /**
     * @param \stdClass $blog
     * @return \stdClass
     */
    private function create_blog_entry(\stdClass $blog): \stdClass {
        /** @var core_blog_generator $bloggenerator */
        $bloggenerator = $this->getDataGenerator()->get_plugin_generator('core_blog');
        return $bloggenerator->create_instance(['module' => 'blog_external', 'content' => $blog->id, 'userid' => $blog->userid]);
    }

}