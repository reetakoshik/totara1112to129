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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package mod_wiki
 */

use mod_wiki\event\comment_deleted;
use mod_wiki\userdata\collaborative_comments;
use totara_userdata\userdata\target_user;
use totara_userdata\userdata\item;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/wiki/tests/wiki_testcase.php');

/**
 * This class tests purging and exporting userdata collaborative wiki comments item.
 * Please note that these tests fully cover the functionality of
 * related helper classes.
 *
 * Class mod_wiki_userdata_collaborative_comments_test
 *
 * @group mod_wiki
 * @group totara_userdata
 */
class mod_wiki_userdata_collaborative_comments_test extends wiki_testcase {

    /**
     * Reusable human-readable error messages.
     *
     * @param string $error Error slug
     * @return array|string Error message(s)
     */
    protected function errors($error = '') {
        $errors = [
            'purge_failed' => 'Collaborative wiki comments user_data purge failed',
            'nothing_to_purge' => 'No Collaborative wiki comments data to purge',
            'underdone_purge' => 'Some items required to purge are still there',
            'excessive_purge' => 'Something that should have stayed was purged',
        ];

        if ($error != '') {
            return in_array($error, $errors) ? $errors[$error] : 'Something went wrong';
        }

        return $errors;
    }

    public function test_it_is_countable() {
        $this->assertTrue(collaborative_comments::is_countable(), 'Collaborative wiki comments user_data must be countable');
    }

    public function test_it_is_exportable() {
        $this->assertTrue(collaborative_comments::is_exportable(), 'Collaborative wiki comments user_data must be exportable');
    }

    public function test_it_is_purgeable() {
        $error = 'Collaborative wiki comments user_data must be purgeable';

        $this->assertTrue(collaborative_comments::is_purgeable(target_user::STATUS_ACTIVE), $error);
        $this->assertTrue(collaborative_comments::is_purgeable(target_user::STATUS_DELETED), $error);
        $this->assertTrue(collaborative_comments::is_purgeable(target_user::STATUS_SUSPENDED), $error);
    }

    public function test_it_is_compatible_with_wide_range_of_contexts() {
        $expected = [
            CONTEXT_SYSTEM,
            CONTEXT_COURSECAT,
            CONTEXT_COURSE,
            CONTEXT_MODULE
        ];

        $contexts = collaborative_comments::get_compatible_context_levels();

        $this->assertEqualsCanonicalizing($expected, $contexts,
            "Collaborative wiki comments user_data item is expected to work with a wide range of contexts");
    }


    public function test_it_purges_collaborative_wiki_comments_for_system_context() {
        $data = $this->seed();

        $user = array_values($data['users'])[0];

        $whatwehavehere = $this->get_related_data($user, $context = context_system::instance());
        $unrelated = $this->get_unrelated_data($user, $context, $whatwehavehere);

        // Initializing mighty purger.
        $status = collaborative_comments::execute_purge(new target_user($user), $context);

        // Purged successfully.
        $this->assertEquals(collaborative_comments::RESULT_STATUS_SUCCESS, $status, $this->errors('purge_failed'));

        $this->is_data_gone($whatwehavehere, $user->id);
        $this->is_unrelated_data_untouched($unrelated);
    }

    public function test_it_purges_collaborative_wiki_comments_for_course_category_context() {
        $data = $this->seed();

        $context = context_coursecat::instance($cat = array_keys($data['cats'])[0]);
        $user = new target_user(array_values($data['users'])[0]);

        $whatwehavehere = $this->get_related_data($user, $context);
        $unrelated = $this->get_unrelated_data($user, $context, $whatwehavehere);

        // Initializing mighty purger.
        $status = collaborative_comments::execute_purge(new target_user($user), $context);

        // Purged successfully.
        $this->assertEquals(collaborative_comments::RESULT_STATUS_SUCCESS, $status, $this->errors('purge_failed'));

        $this->is_data_gone($whatwehavehere, $user->id);
        $this->is_unrelated_data_untouched($unrelated);
    }

    public function test_it_purges_collaborative_wiki_comments_for_course_context() {
        $data = $this->seed();

        $context = context_course::instance($course = intval(array_keys(array_values($data['cats'])[0])[0]));
        $user = new target_user(array_values($data['users'])[0]);

        $whatwehavehere = $this->get_related_data($user, $context);
        $unrelated = $this->get_unrelated_data($user, $context, $whatwehavehere);

        // Initializing mighty purger.
        $status = collaborative_comments::execute_purge(new target_user($user), $context);

        // Purged successfully.
        $this->assertEquals(collaborative_comments::RESULT_STATUS_SUCCESS, $status, $this->errors('purge_failed'));

        $this->is_data_gone($whatwehavehere, $user->id);
        $this->is_unrelated_data_untouched($unrelated);
    }

    public function test_it_purges_collaborative_wiki_comments_for_course_module_context() {
        $data = $this->seed();

        $module = get_coursemodule_from_instance('wiki',
            array_keys(array_values(array_values($data['cats'])[0])[0])[1]);
        $user = new target_user(array_values($data['users'])[0]);
        $context = context_module::instance($module->id);

        $whatwehavehere = $this->get_related_data($user, $context);
        $unrelated = $this->get_unrelated_data($user, $context, $whatwehavehere);

        // Initializing mighty purger.
        $status = collaborative_comments::execute_purge(new target_user($user), $context);

        // Purged successfully.
        $this->assertEquals(collaborative_comments::RESULT_STATUS_SUCCESS, $status, $this->errors('purge_failed'));

        $this->is_data_gone($whatwehavehere, $user->id);
        $this->is_unrelated_data_untouched($unrelated);
    }

    public function test_it_exports_collaborative_wiki_comments_for_system_context() {
        $data = $this->seed();

        $user = array_values($data['users'])[0];
        $this->setUser($user);
        $whatwehavehere = $this->get_related_data($user, $context = context_system::instance());

        $export = collaborative_comments::execute_export(new target_user($user), $context);
        $this->assertExportMatches($whatwehavehere, $export);
    }

    public function test_it_exports_collaborative_wiki_comments_for_course_category_context() {
        $data = $this->seed();

        $context = context_coursecat::instance($cat = array_keys($data['cats'])[0]);
        $user = array_values($data['users'])[0];

        $this->setUser($user);
        $whatwehavehere = $this->get_related_data($user, $context);

        $export = collaborative_comments::execute_export(new target_user($user), $context);
        $this->assertExportMatches($whatwehavehere, $export);
    }

    public function test_it_exports_collaborative_wiki_comments_for_course_context() {
        $data = $this->seed();

        $context = context_course::instance($course = intval(array_keys(array_values($data['cats'])[0])[0]));
        $user = array_values($data['users'])[0];

        $this->setUser($user);
        $whatwehavehere = $this->get_related_data($user, $context);

        $export = collaborative_comments::execute_export(new target_user($user), $context);
        $this->assertExportMatches($whatwehavehere, $export);
    }

    public function test_it_exports_collaborative_wiki_comments_for_course_module_context() {
        $data = $this->seed();

        $module = get_coursemodule_from_instance('wiki',
            array_keys(array_values(array_values($data['cats'])[0])[0])[1]);
        $user = array_values($data['users'])[0];
        $context = context_module::instance($module->id);

        $this->setUser($user);
        $whatwehavehere = $this->get_related_data($user, $context);

        $export = collaborative_comments::execute_export(new target_user($user), $context);
        $this->assertExportMatches($whatwehavehere, $export);
    }

    public function test_it_counts_collaborative_wiki_comments_for_system_context() {
        $data = $this->seed();

        $user = array_values($data['users'])[0];

        $this->assertEquals($this->count_comments($user, $context = context_system::instance()),
            collaborative_comments::execute_count(new target_user($user), $context));
    }

    public function test_it_counts_collaborative_wiki_comments_for_course_category_context() {
        $data = $this->seed();

        $context = context_coursecat::instance($cat = array_keys($data['cats'])[0]);
        $user = new target_user(array_values($data['users'])[0]);

        $this->assertEquals($this->count_comments($user, $context),
            collaborative_comments::execute_count(new target_user($user), $context));
    }

    public function test_it_counts_collaborative_wiki_comments_for_course_context() {
        $data = $this->seed();

        $context = context_course::instance($course = intval(array_keys(array_values($data['cats'])[0])[0]));
        $user = new target_user(array_values($data['users'])[0]);

        $this->assertEquals($this->count_comments($user, $context),
            collaborative_comments::execute_count(new target_user($user), $context));
    }

    public function test_it_counts_collaborative_wiki_comments_for_course_module_context() {
        $data = $this->seed();

        $module = get_coursemodule_from_instance('wiki',
            array_keys(array_values(array_values($data['cats'])[0])[0])[1]);
        $user = new target_user(array_values($data['users'])[0]);
        $context = context_module::instance($module->id);

        $this->assertEquals($this->count_comments($user, $context),
            collaborative_comments::execute_count(new target_user($user), $context));
    }

    /**
     * Data to purge returned by get_related_data()
     *
     * @param stdClass $data get_related_data()
     * @param int $user User id
     */
    protected function is_data_gone(stdClass $data, $user) {
        // Make id arrays friendly to insert into database query.
        $pageids = $this->normalize_ids($data->pages);

        // Check no comments.
        $this->assertEmpty($this->get_comments($pageids, $user));

        // Check that required comment-related events have been fired.
        // Filter the comment-related events events only.
        $events = $this->filter_events($data->events_sink, 'comment');
        foreach ($events as $event) {
            /** @var $event \core\event\base */
            $this->assertInstanceOf(comment_deleted::class, $event);
        }
    }

    /**
     * Check whether all the data unrelated to the purge is still in place.
     *
     * @param \stdClass $unrelated Unrelated data object
     * @return void
     */
    protected function is_unrelated_data_untouched(stdClass $unrelated) {
        global $DB;

        // All the data in the database that is left after the purge must be unrelated.
        $subwikis = $DB->get_records("wiki_subwikis");
        $pages = $DB->get_records('wiki_pages');

        // Check sub-wikis.
        $this->assertIdsMatch($unrelated->subwikis, $subwikis);

        // Check pages.
        $this->assertIdsMatch($unrelated->pages, $pages);

        // Check comments, That will select all the comments from the database.
        $this->assertIdsMatch($unrelated->comments, $DB->get_records('comments'));

    }

    /**
     * Fetch data that should be related to the purge/export from the database.
     *
     * @param stdClass|\totara_userdata\userdata\target_user $user User object
     * @param \context $context Context to get the data
     * @return \stdClass Data object
     */
    protected function get_related_data($user, $context) {
        global $DB;

        $user = intval($user->id);

        // Select all the sub-wikis bound by context.
        $joins = item::get_activities_join($context, 'wiki', 'subwikis.wikiid', 'activities');

        // Select sub-wikis which have pages with user comments.
        $subwikis = $DB->get_records_sql(
            "SELECT subwikis.*, activities.name as wiki_name, activities.intro as wiki_intro, ctx.id as context_id
                  FROM {wiki_subwikis} subwikis {$joins}
                  WHERE subwikis.userid = 0 AND activities.wikimode = 'collaborative' AND EXISTS 
                    (SELECT pages.id FROM {wiki_pages} pages WHERE pages.subwikiid = subwikis.id AND EXISTS
                     (SELECT comments.id FROM {comments} comments WHERE comments.component = 'mod_wiki'
                      AND comments.commentarea = 'wiki_page' AND comments.itemid = pages.id AND comments.userid = {$user}))");

        // Check that there is something...
        if (empty($subwikis)) {
            // Squawk at the user and blow up.
            throw new coding_exception("No related data there, oops.");
        }

        $subwikiids = $this->normalize_ids($subwikis);

        // And then when we have main sub-wiki ids let's rock and roll and select data from all the related tables.

        // Get only pages with user comments.
        $pages = $DB->get_records_sql(
            "SELECT pages.* FROM {wiki_pages} pages
                  WHERE pages.subwikiid {$subwikiids} AND EXISTS
                   (SELECT comments.id FROM {comments} comments
                     WHERE comments.itemid = pages.id AND comments.component = 'mod_wiki'
                     AND comments.commentarea = 'wiki_page' AND comments.userid = {$user})");

        return (object) [
            'subwikis' => $subwikis,
            'pages' => $pages,
            'comments' => $this->get_comments($this->normalize_ids($pages), $user),
            'events_sink' => $this->redirectEvents(),
        ];
    }

    /**
     * Select all the unrelated to the purge/export data.
     *
     * @param \stdClass|\totara_userdata\userdata\target_user $user User object
     * @param \context $context Context object
     * @param \stdClass $related Related data object (returned by get_related_data())
     * @return \stdClass Unrelated data object
     */
    protected function get_unrelated_data($user, $context, $related) {
        global $DB;
        // All the wiki and pages are unrelated and must stay intact only certain comments should be purged
        $subwikis = $DB->get_records('wiki_subwikis');

        // Make id arrays friendly to insert into database query.
        $pages = $DB->get_records('wiki_pages');

        return (object) [
            'subwikis' => $subwikis,
            'pages' => $pages,
            'comments' => $this->get_unrelated_comments($this->normalize_ids($related->pages), $user->id),
        ];
    }

    /**
     * Get all the unrelated to the purge\export comments.
     *
     * @param string $pagessql Normalized IDs returned by normalize_ids
     * @param int $user
     * @return \stdClass[]
     */
    protected function get_unrelated_comments($pagessql, $user) {
        global $DB;

        $comments = $this->normalize_ids($this->get_comments($pagessql, $user), false);
        return $DB->get_records_sql("SELECT id FROM {comments} WHERE id NOT IN ({$comments})");
    }

    /**
     * Count Collaborative wiki comments instances.
     *
     * @param \stdClass|\totara_userdata\userdata\target_user $user User object
     * @param $context
     * @return int Number of wiki instances
     */
    protected function count_comments($user, $context) {
        global $DB;

        $user = intval($user->id);
        $joins = item::get_activities_join($context, 'wiki', 'subwikis.wikiid', 'activities');

        return $DB->count_records_sql(
            "SELECT count(comments.id) FROM {comments} comments
                  WHERE comments.itemid IN
                      (SELECT pages.id FROM {wiki_pages} pages
                       JOIN {wiki_subwikis} subwikis ON pages.subwikiid = subwikis.id {$joins}
                       WHERE activities.wikimode = 'collaborative' AND subwikis.userid = 0)
                      AND comments.userid = {$user} AND comments.component = 'mod_wiki'
                      AND comments.commentarea = 'wiki_page'");
    }

    /**
     * Match exported sub-wiki to the original data.
     *
     * @param \stdClass $expected get_related_data()
     * @param array $actual Exported sub-wiki
     */
    protected function match_subwiki($expected, $actual) {
        $subwiki = $expected->subwikis[$actual['id']];

        $this->assertSame(intval($subwiki->id), $actual['id']);
        $this->assertEquals($subwiki->wiki_name, $actual['name']);
        $this->assertEquals($subwiki->wiki_intro, $actual['intro']);

        foreach ($actual['pages'] as $page) {
            $this->assertArrayHasKey($page['id'], $expected->pages);
            $this->match_page($expected, $page);
        }

        unset($expected->subwikis[$actual['id']]);
    }

    /**
     * Match exported wiki page to the original data.
     *
     * @param \stdClass $expected get_related_data()
     * @param array $actual Exported wiki page
     */
    protected function match_page($expected, $actual) {
        $page = $expected->pages[$actual['id']];

        $this->assertSame(intval($page->id), $actual['id']);
        $this->assertEquals($page->title, $actual['title']);

        foreach ($actual['comments'] as $comment) {
            $this->assertArrayHasKey($comment['id'], $expected->comments);
            $this->match_comment($expected, $comment);
        }

        unset($expected->pages[$actual['id']]);
    }

    /**
     * Match exported page comment to the original data.
     *
     * @param \stdClass $expected get_related_data()
     * @param array $actual Exported page comment
     */
    protected function match_comment($expected, $actual) {
        $comment = $expected->comments[$actual['id']];

        $this->assertSame(intval($comment->id), $actual['id']);
        $this->assertEquals($comment->content, $actual['comment']);
        $this->assertSame(intval($comment->timecreated), $actual['posted_at']);
        $this->assertEquals($this->human_time($comment->timecreated), $actual['posted_at_human']);
        $this->assertTrue($actual['posted_by_user']);

        unset($expected->comments[$actual['id']]);
    }

    /**
     * Entry point to match the exported data to the original data.
     *
     * @param \stdClass $expected get_related_data()
     * @param \totara_userdata\userdata\export $actual Actual export
     */
    protected function assertExportMatches($expected, $actual) {
        // Clone expected and files things so we can safely unset those.
        $expected = clone $expected;
        $files = array_map(function($file) { return clone $file ;}, $actual->files);

        foreach ($actual->data as $subwiki) {
            $this->assertArrayHasKey($subwiki['id'], $expected->subwikis);
            $this->match_subwiki($expected, $subwiki);
        }

        $this->assertEmpty($expected->subwikis, 'Not all the sub-wikis with pages with comments have been exported');
        $this->assertEmpty($expected->pages, 'Not all the pages with comments have been exported');
        $this->assertEmpty($expected->comments, 'Not all the comments have been exported');

        $this->assertEmpty($actual->files, 'Collaborative wiki comments user_data item must not include files.');
        $this->assertEmpty($files, 'Collaborative wiki comments user_data item must not include files.');
    }
}
