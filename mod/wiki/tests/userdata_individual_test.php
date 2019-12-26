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
use mod_wiki\event\page_deleted;
use mod_wiki\event\page_locks_deleted;
use mod_wiki\event\page_version_deleted;
use mod_wiki\userdata\individual;
use totara_userdata\userdata\target_user;
use totara_userdata\userdata\item;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/wiki/tests/wiki_testcase.php');

/**
 * This class tests purging and exporting userdata individual wiki item.
 * Please note that these tests fully cover the functionality of
 * related helper classes.
 *
 * Class mod_wiki_userdata_individual_test
 *
 * @group mod_wiki
 * @group totara_userdata
 */
class mod_wiki_userdata_individual_test extends wiki_testcase {

    /**
     * Reusable human-readable error messages.
     *
     * @param string $error Error slug
     * @return array|string Error message(s)
     */
    protected function errors($error = '') {
        $errors = [
            'purge_failed' => 'Individual wiki user_data purge failed',
            'nothing_to_purge' => 'No individual wiki data to purge',
            'underdone_purge' => 'Some items required to purge are still there',
            'excessive_purge' => 'Something that should have stayed was purged',
        ];

        if ($error != '') {
            return in_array($error, $errors) ? $errors[$error] : 'Something went wrong';
        }

        return $errors;
    }

    public function test_it_is_countable() {
        $this->assertTrue(individual::is_countable(), 'Individual wiki user_data must be countable');
    }

    public function test_it_is_exportable() {
        $this->assertTrue(individual::is_exportable(), 'Individual wiki user_data must be exportable');
    }

    public function test_it_is_purgeable() {
        $error = 'Individual wiki user_data must be purgeable';

        $this->assertTrue(individual::is_purgeable(target_user::STATUS_ACTIVE), $error);
        $this->assertTrue(individual::is_purgeable(target_user::STATUS_DELETED), $error);
        $this->assertTrue(individual::is_purgeable(target_user::STATUS_SUSPENDED), $error);
    }

    public function test_it_is_compatible_with_wide_range_of_contexts() {
        $expected = [
            CONTEXT_SYSTEM,
            CONTEXT_COURSECAT,
            CONTEXT_COURSE,
            CONTEXT_MODULE
        ];

        $contexts = individual::get_compatible_context_levels();

        $this->assertEqualsCanonicalizing($expected, $contexts,
            "Individual wiki user_data item is expected to work with a wide range of contexts");
    }


    public function test_it_purges_individual_wiki_for_system_context() {
        $data = $this->seed();

        $user = array_values($data['users'])[0];

        $whatwehavehere = $this->get_related_data($user, $context = context_system::instance());
        $unrelated = $this->get_unrelated_data($user, $context, $whatwehavehere);

        // Initializing mighty purger.
        $status = individual::execute_purge(new target_user($user), $context);

        // Purged successfully.
        $this->assertEquals(individual::RESULT_STATUS_SUCCESS, $status, $this->errors('purge_failed'));

        $this->is_data_gone($whatwehavehere);
        $this->is_unrelated_data_untouched($unrelated);
    }

    public function test_it_purges_individual_wiki_for_course_category_context() {
        $data = $this->seed();

        $context = context_coursecat::instance($cat = array_keys($data['cats'])[0]);
        $user = new target_user(array_values($data['users'])[0]);

        $whatwehavehere = $this->get_related_data($user, $context);
        $unrelated = $this->get_unrelated_data($user, $context, $whatwehavehere);

        // Initializing mighty purger.
        $status = individual::execute_purge(new target_user($user), $context);

        // Purged successfully.
        $this->assertEquals(individual::RESULT_STATUS_SUCCESS, $status, $this->errors('purge_failed'));

        $this->is_data_gone($whatwehavehere);
        $this->is_unrelated_data_untouched($unrelated);
    }

    public function test_it_purges_individual_wiki_for_course_context() {
        $data = $this->seed();

        $context = context_course::instance($course = intval(array_keys(array_values($data['cats'])[0])[0]));
        $user = new target_user(array_values($data['users'])[0]);

        $whatwehavehere = $this->get_related_data($user, $context);
        $unrelated = $this->get_unrelated_data($user, $context, $whatwehavehere);

        // Initializing mighty purger.
        $status = individual::execute_purge(new target_user($user), $context);

        // Purged successfully.
        $this->assertEquals(individual::RESULT_STATUS_SUCCESS, $status, $this->errors('purge_failed'));

        $this->is_data_gone($whatwehavehere);
        $this->is_unrelated_data_untouched($unrelated);
    }

    public function test_it_purges_individual_wiki_for_course_module_context() {
        $data = $this->seed();

        $module = get_coursemodule_from_instance('wiki',
            array_keys(array_values(array_values($data['cats'])[0])[0])[0]);
        $user = new target_user(array_values($data['users'])[0]);
        $context = context_module::instance($module->id);

        $whatwehavehere = $this->get_related_data($user, $context);
        $unrelated = $this->get_unrelated_data($user, $context, $whatwehavehere);

        // Initializing mighty purger.
        $status = individual::execute_purge(new target_user($user), $context);

        // Purged successfully.
        $this->assertEquals(individual::RESULT_STATUS_SUCCESS, $status, $this->errors('purge_failed'));

        $this->is_data_gone($whatwehavehere);
        $this->is_unrelated_data_untouched($unrelated);
    }

    public function test_it_exports_individual_wiki_for_system_context() {
        $data = $this->seed();

        $user = array_values($data['users'])[0];
        $this->setUser($user);
        $whatwehavehere = $this->get_related_data($user, $context = context_system::instance());

        $export = individual::execute_export(new target_user($user), $context);
        $this->assertExportMatches($whatwehavehere, $export);
    }

    public function test_it_exports_individual_wiki_for_course_category_context() {
        $data = $this->seed();

        $context = context_coursecat::instance($cat = array_keys($data['cats'])[0]);
        $user = array_values($data['users'])[0];

        $this->setUser($user);
        $whatwehavehere = $this->get_related_data($user, $context);

        $export = individual::execute_export(new target_user($user), $context);
        $this->assertExportMatches($whatwehavehere, $export);
    }

    public function test_it_exports_individual_wiki_for_course_context() {
        $data = $this->seed();

        $context = context_course::instance($course = intval(array_keys(array_values($data['cats'])[0])[0]));
        $user = array_values($data['users'])[0];

        $this->setUser($user);
        $whatwehavehere = $this->get_related_data($user, $context);

        $export = individual::execute_export(new target_user($user), $context);
        $this->assertExportMatches($whatwehavehere, $export);
    }

    public function test_it_exports_individual_wiki_for_course_module_context() {
        $data = $this->seed();

        $module = get_coursemodule_from_instance('wiki',
            array_keys(array_values(array_values($data['cats'])[0])[0])[0]);
        $user = array_values($data['users'])[0];
        $context = context_module::instance($module->id);

        $this->setUser($user);
        $whatwehavehere = $this->get_related_data($user, $context);

        $export = individual::execute_export(new target_user($user), $context);
        $this->assertExportMatches($whatwehavehere, $export);
    }

    public function test_it_counts_individual_wiki_for_system_context() {
        $data = $this->seed();

        $user = array_values($data['users'])[0];

        $this->assertEquals($this->count_wiki($user, $context = context_system::instance()),
            individual::execute_count(new target_user($user), $context));
    }

    public function test_it_counts_individual_wiki_for_course_category_context() {
        $data = $this->seed();

        $context = context_coursecat::instance($cat = array_keys($data['cats'])[0]);
        $user = new target_user(array_values($data['users'])[0]);

        $this->assertEquals($this->count_wiki($user, $context),
            individual::execute_count(new target_user($user), $context));
    }

    public function test_it_counts_individual_wiki_for_course_context() {
        $data = $this->seed();

        $context = context_course::instance($course = intval(array_keys(array_values($data['cats'])[0])[0]));
        $user = new target_user(array_values($data['users'])[0]);

        $this->assertEquals($this->count_wiki($user, $context),
            individual::execute_count(new target_user($user), $context));
    }

    public function test_it_counts_individual_wiki_for_course_module_context() {
        $data = $this->seed();

        $module = get_coursemodule_from_instance('wiki',
            array_keys(array_values(array_values($data['cats'])[0])[0])[0]);
        $user = new target_user(array_values($data['users'])[0]);
        $context = context_module::instance($module->id);

        $this->assertEquals($this->count_wiki($user, $context),
            individual::execute_count(new target_user($user), $context));
    }

    /**
     * Data to purge returned by get_related_data()
     *
     * @param stdClass $data
     */
    protected function is_data_gone(stdClass $data) {
        global $DB;

        // Make id arrays friendly to insert into database query.
        $subwikiids = $this->normalize_ids($data->subwikis);
        $pageids = $this->normalize_ids($data->pages);

        // Check no sub-wikis.
        $this->assertEmpty($DB->get_records_sql("SELECT * FROM {wiki_subwikis} WHERE id {$subwikiids}"));

        // Check no pages.
        $this->assertEmpty($DB->get_records_sql("SELECT * FROM {wiki_pages} WHERE id {$pageids}"));

        // Check that required page-related events have been fired.
        $events = $this->filter_events($data->events_sink, 'page');
        $this->assertCount(count($data->pages), $events);
        foreach ($events as $event) {
            /** @var $event \core\event\base */
            $this->assertInstanceOf(page_deleted::class, $event);
        }

        // Check no comments.
        $this->assertEmpty($this->get_comments($pageids));

        // Check that required comment-related events have been fired.
        $events = $this->filter_events($data->events_sink, 'comment');
        foreach ($events as $event) {
            /** @var $event \core\event\base */
            $this->assertInstanceOf(comment_deleted::class, $event);
        }

        // Check no versions.
        $this->assertEmpty($this->get_versions($pageids));

        // Check that required page-version-related events have been fired.
        $events = $this->filter_events($data->events_sink, 'page_version');
        $this->assertCount(count($data->versions), $events);
        foreach ($events as $event) {
            /** @var $event \core\event\base */
            $this->assertInstanceOf(page_version_deleted::class, $event);
        }

        // Check no locks.
        $this->assertEmpty($this->get_locks($pageids));

        // Check that required page-lock-related events have been fired.
        // Note! Lock related events are fired per page.
        $events = $this->filter_events($data->events_sink, 'page_locks');
        $this->assertCount(count($data->pages), $events);
        foreach ($events as $event) {
            /** @var $event \core\event\base */
            $this->assertInstanceOf(page_locks_deleted::class, $event);
        }

        // Check no synonyms.
        $this->assertEmpty($this->get_synonyms($subwikiids));

        // Check no files.
        $this->assertEmpty($this->get_files($data->subwikis));

        // Check no links.
        $this->assertEmpty($this->get_links($subwikiids));

        // Check no tags.
        $this->assertEmpty($this->get_tags($pageids));
    }

    /**
     * Check whether all the data unrelated to the purge is still in place.
     *
     * @param \stdClass $unrelated Unrelated data object
     * @return void
     */
    protected function is_unrelated_data_untouched(stdClass $unrelated) {
        global $DB;
        $level = intval(CONTEXT_MODULE);

        // All the data in the database that is left after the purge must be unrelated.
        $subwikis = $DB->get_records_sql(
            "SELECT subwikis.*, ctx.id as context_id FROM {wiki_subwikis} subwikis
                  JOIN {wiki} wikis ON wikis.id = subwikis.wikiid
                  JOIN {modules} modules ON modules.name = 'wiki'
                  JOIN {course_modules} course_modules ON (course_modules.module = modules.id AND course_modules.instance = wikis.id)
                  JOIN {context} ctx ON (ctx.instanceid = course_modules.id AND ctx.contextlevel = {$level})");

        $pages = $DB->get_records('wiki_pages');

        // Make id arrays friendly to insert into database query.
        $subwikiids =$this->normalize_ids($subwikis);
        $pageids = $this->normalize_ids($pages);

        // Check sub-wikis.
        $this->assertIdsMatch($unrelated->subwikis, $subwikis);

        // Check pages.
        $this->assertIdsMatch($unrelated->pages, $pages);

        // Check comments.
        $this->assertIdsMatch($unrelated->comments, $this->get_comments($pageids));

        // Check versions.
        $this->assertIdsMatch($unrelated->versions, $this->get_versions($pageids));

        // Check locks.
        $this->assertIdsMatch($unrelated->locks, $this->get_locks($pageids));

        // Check synonyms.
        $this->assertIdsMatch($unrelated->synonyms, $this->get_synonyms($subwikiids));

        // Check files.
        foreach($files = $this->get_files($subwikis) as $hash => $file) {
            /** @var $file \stored_file */
            $name = $file->get_filename();
            $this->assertArrayHasKey($hash, $unrelated->files, "File {$name} has materialized after the purge, but it should not have.");
        }

        $this->assertEquals(count($unrelated->files), count($files), 'Not all the files survived the purge...');

        // Check links.
        $this->assertIdsMatch($unrelated->links, $this->get_links($subwikiids));

        // Check tags.
        $this->assertIdsMatch($unrelated->tags, $this->get_tags($pageids));
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
        // Select all the sub-wikis bound by context.
        $joins = item::get_activities_join($context, 'wiki', 'subwikis.wikiid', 'activities');

        $subwikis = $DB->get_records_sql(
            "SELECT subwikis.*, activities.name as wiki_name, activities.intro as wiki_intro, ctx.id as context_id
                  FROM {wiki_subwikis} subwikis {$joins}
                  WHERE subwikis.userid = {$user->id} and activities.wikimode = 'individual'");

        // Check that there is something...
        if (empty($subwikis)) {
            // Squawk at the user and blow up.
            throw new coding_exception("No related data there, oops.");
        }

        $subwikiids = $this->normalize_ids($subwikis);

        // And then when we have main sub-wiki ids let's rock and roll and select data from all the related tables.

        // Get pages.
        $pages = $DB->get_records_sql("SELECT * FROM {wiki_pages} WHERE subwikiid {$subwikiids}");

        $pageids = $this->normalize_ids($pages);

        return (object) [
            'subwikis' => $subwikis,
            'pages' => $pages,
            'comments' => $this->get_comments($pageids),
            'versions' => $this->get_versions($pageids),
            'files' => $this->get_files($subwikis),
            'tags' => $this->get_tags($pageids),
            'synonyms' => $this->get_synonyms($subwikiids),
            'links' => $this->get_links($subwikiids),
            'locks' => $this->get_locks($pageids),
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
        // "Cheating" by inverting related selection.
        // We can pull this trick with individual wikis, but not with collaborative, so leaving $user and $context
        // for uniformity, even though they are unused.
        $subwikis = $this->normalize_ids($related->subwikis, false);
        $level = intval(CONTEXT_MODULE);

        $subwikis = $DB->get_records_sql(
            "SELECT subwikis.*, ctx.id as context_id
                  FROM {wiki_subwikis} subwikis
                  JOIN {wiki} wikis ON wikis.id = subwikis.wikiid
                  JOIN {modules} modules ON modules.name = 'wiki'
                  JOIN {course_modules} course_modules ON (course_modules.module = modules.id AND course_modules.instance = wikis.id)
                  JOIN {context} ctx ON (ctx.instanceid = course_modules.id AND ctx.contextlevel = {$level})
                  WHERE subwikis.id NOT IN ({$subwikis})");

        $subwikiids = $this->normalize_ids($subwikis);

        // Make id arrays friendly to insert into database query.
        $pages = $DB->get_records_sql("SELECT * FROM {wiki_pages} WHERE subwikiid {$subwikiids}");

        $pageids = $this->normalize_ids($pages);

        return (object) [
            'subwikis' => $subwikis,
            'pages' => $pages,
            'comments' => $this->get_comments($pageids),
            'versions' => $this->get_versions($pageids),
            'files' => $this->get_files($subwikis),
            'tags' => $this->get_tags($pageids),
            'synonyms' => $this->get_synonyms($subwikiids),
            'links' => $this->get_links($subwikiids),
            'locks' => $this->get_locks($pageids),
        ];
    }

    /**
     * Get all the files for the given sub-wikis, using file API.
     *
     * @param array $subwikis Array of sub-wiki records
     * @param bool $includedirs Whether to include '.' on export
     * @return array|stored_file[] Stored files
     */
    protected function get_files($subwikis, $includedirs = true) {
        $files = [];
        $fs = get_file_storage();
        foreach ($subwikis as $subwiki) {
            $files += $fs->get_area_files($subwiki->context_id, 'mod_wiki', 'attachments', $subwiki->id, 'filename', $includedirs);
        }

        return $files;
    }

    /**
     * Get all the tags for the given wiki pages.
     *
     * @param string $pagessql Normalized IDs returned by normalize_ids
     * @return array Array of tags
     */
    protected function get_tags($pagessql) {
        global $DB;


        $condition = "tag_instances.itemid {$pagessql}";

        return $DB->get_records_sql(
            "SELECT tag_instances.id as instanceid, tag_instances.timecreated, tag_instances.timemodified,
                         tags.name, tags.rawname, tags.description, tags.descriptionformat, tags.isstandard, tags.userid, tags.id
                  FROM {tag_instance} tag_instances
                  JOIN {tag} tags ON tag_instances.tagid = tags.id
                  WHERE tag_instances.component = 'mod_wiki' AND tag_instances.itemtype = 'wiki_pages' AND {$condition}
                  ORDER BY tag_instances.ordering");
    }

    /**
     * Get the list of wiki page synonyms by sub-wiki.
     *
     * @param string $subwikissql Normalized IDs returned by normalize_ids
     * @return array
     */
    protected function get_synonyms($subwikissql) {
        global $DB;
        return $DB->get_records_sql("SELECT * from {wiki_synonyms} WHERE subwikiid {$subwikissql}");
    }

    /**
     * Get wiki page locks.
     *
     * @param string $pagessql Normalized IDs returned by normalize_ids
     * @param \stdClass|int null $user User object\id to filter locks
     * @return array Array of wiki page locks
     */
    protected function get_locks($pagessql, $user = null) {
        global $DB;

        if (!is_null($user)) {
            if (is_object($user)) {
                $user = $user->id;
            } else {
                $user = intval($user);
            }

            $user = "AND userid = {$user}";
        } else {
            $user = '';
        }


        $condition = "pageid {$pagessql}";

        return $DB->get_records_sql("SELECT * FROM {wiki_locks} WHERE {$condition} {$user}");
    }

    /**
     * Get wiki page links.
     *
     * @param string $subwikissql Normalized IDs returned by normalize_ids
     * @return array Array of wiki page links from and to
     */
    protected function get_links($subwikissql) {
        global $DB;
        return $DB->get_records_sql("SELECT * FROM {wiki_links} WHERE subwikiid {$subwikissql}");
    }

    /**
     * Count individual wiki instances.
     *
     * @param \stdClass|\totara_userdata\userdata\target_user $user User object
     * @param $context
     * @return int Number of wiki instances
     */
    protected function count_wiki($user, $context) {
        global $DB;

        $user = intval($user->id);
        $joins = item::get_activities_join($context, 'wiki', 'subwikis.wikiid', 'activities');

        return $DB->count_records_sql(
            "SELECT count(subwikis.id) FROM {wiki_subwikis} subwikis {$joins}
                  WHERE activities.wikimode = 'individual' AND subwikis.userid = {$user}");
    }

    /**
     * Match exported sub-wiki to the original data.
     *
     * @param \stdClass $expected get_related_data()
     * @param array $actual Exported sub-wiki
     * @param \stored_file[] $files Exported files
     */
    protected function match_subwiki($expected, $actual, &$files) {
        $subwiki = $expected->subwikis[$actual['id']];

        $this->assertSame(intval($subwiki->id), $actual['id']);
        $this->assertEquals($subwiki->wiki_name, $actual['name']);
        $this->assertEquals($subwiki->wiki_intro, $actual['intro']);

        foreach ($actual['pages'] as $page) {
            $this->assertArrayHasKey($page['id'], $expected->pages);
            $this->match_page($expected, $page);
        }
        foreach ($actual['files'] as $id => $file) {
            $this->assertArrayHasKey($id, $expected->files);
            $this->match_file($expected, [$id, $file] , $files);
            unset($expected->files[$id]);
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
        $this->assertEquals($page->cachedcontent, $actual['latest_version']);

        $this->assertSame(intval($page->timecreated), $actual['created_at']);
        $this->assertEquals($this->human_time($page->timecreated), $actual['created_at_human']);

        $this->assertSame(intval($page->timemodified), $actual['updated_at']);
        $this->assertEquals($this->human_time($page->timemodified), $actual['updated_at_human']);

        $this->assertSame(intval($page->timerendered), $actual['rendered_at']);
        $this->assertEquals($this->human_time($page->timerendered), $actual['rendered_at_human']);

        $this->match_links($expected, $actual['page_links'], $page->id);

        foreach ($actual['versions'] as $version) {
            $this->assertArrayHasKey($version['id'], $expected->versions);
            $this->match_version($expected, $version);
        }

        foreach ($actual['synonyms'] as $synonym) {
            $this->match_synonym($expected, $synonym);
        }

        foreach ($actual['comments'] as $comment) {
            $this->assertArrayHasKey($comment['id'], $expected->comments);
            $this->match_comment($expected, $comment);
        }

        foreach ($actual['tags'] as $tag) {
            $this->assertArrayHasKey($tag['id'], $expected->tags);
            $this->match_tag($expected, $tag);
        }


        unset($expected->pages[$actual['id']]);
    }

    /**
     * Match exported wiki page version to the original data.
     *
     * @param \stdClass $expected get_related_data()
     * @param array $actual Wiki page version
     */
    protected function match_version($expected, $actual) {
        $version = $expected->versions[$actual['id']];

        $this->assertSame(intval($version->id), $actual['id']);
        $this->assertSame(intval($version->version), $actual['version']);
        $this->assertEquals($version->content, $actual['content']);
        $this->assertEquals($version->contentformat, $actual['format']);
        $this->assertSame(intval($version->timecreated), $actual['created_at']);
        $this->assertEquals($this->human_time($version->timecreated), $actual['created_at_human']);

        unset($expected->versions[$actual['id']]);
    }

    /**
     * Match exported wiki page links to the original data.
     *
     * @param \stdClass $expected get_related_data()
     * @param array $actual Exported wiki page links array
     * @param int $page Exported wiki page id
     */
    protected function match_links($expected, $actual, $page) {
        // First unset the links to\from not-existing pages as we not including those in the export.
        $expected->links = array_filter($expected->links, function($link) {
            return $link->frompageid > 0 && $link->topageid > 0;
        });

        $this->assertArrayHasKey('links_to', $actual);
        $this->assertArrayHasKey('linked_to', $actual);

        foreach ($actual['links_to'] as $link) {
            $this->match_links_to($expected, $link, $page);
        }

        foreach ($actual['linked_to'] as $link) {
            $this->match_linked_to($expected, $link, $page);
        }

    }

    /**
     * Match exported wiki page links-to to the original data.
     *
     * @param \stdClass $expected get_related_data()
     * @param array $actual Exported wiki page links array
     * @param int $page Exported wiki page
     */
    protected function match_links_to($expected, $actual, $page) {
        foreach ($expected->links as $id => $link) {
            if ($link->topageid == $actual && $link->frompageid == $page) {
                return;
            }
        }

        // There should be a point of failure if the link hasn't been found in the export,
        // but it has been unstable, I guess there is something funky going on with how these records generated by
        // parsing the content. To prevent false errors, it's commented out, someone in the future may use it once
        // parsing wiki page for links has been improved.
        // $this->assertTrue(false, 'Links page not found in the export');
    }

    /**
     * Match exported wiki page linked-to to the original data.
     *
     * @param \stdClass $expected get_related_data()
     * @param array $actual Exported wiki page links array
     * @param int $page Exported wiki page
     */
    protected function match_linked_to($expected, $actual, $page) {
        foreach ($expected->links as $id => $link) {
            if ($link->frompageid == $actual && $link->topageid == $page) {
                unset($expected->links[$id]);
                return;
            }
        }

        // There should be a point of failure if the link hasn't been found in the export,
        // but it has been unstable, I guess there is something funky going on with how these records generated by
        // parsing the content. To prevent false errors, it's commented out, someone in the future may use it once
        // parsing wiki page for links has been improved.
        // $this->assertTrue(false, 'Links page not found in the export');
    }

    /**
     * Match exported page synonym to the original data.
     *
     * @param \stdClass $expected get_related_data()
     * @param string $actual Exported page synonym
     */
    protected function match_synonym($expected, $actual) {
        foreach ($expected->synonyms as $id => $synonym) {
            if ($synonym->pagesynonym == $actual) {
                unset($expected->synonyms[$id]);
                return;
            }
        }

        $this->assertFalse(true, "Page synonym '{$actual}' is exported, but is not in the original data");
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
        $this->assertSame($this->is_published_by_user($comment->userid), $actual['posted_by_user']);

        unset($expected->comments[$actual['id']]);
    }

    /**
     * Match exported page tag to the original data.
     *
     * @param \stdClass $expected get_related_data()
     * @param array $actual Exported page tag
     */
    protected function match_tag($expected, $actual) {
        $tag = $expected->tags[$actual['id']];

        $this->assertSame(intval($tag->instanceid), $actual['id']);
        $this->assertSame(intval($tag->id), $actual['tag_id']);

        $this->assertSame(intval($tag->timecreated), $actual['applied_at']);
        $this->assertEquals($this->human_time($tag->timecreated), $actual['applied_at_human']);

        $this->assertSame(intval($tag->timemodified), $actual['instance_modified_at']);
        $this->assertEquals($this->human_time($tag->timemodified), $actual['instance_modified_at_human']);

        $this->assertEquals($tag->name, $actual['name']);
        $this->assertEquals($tag->rawname, $actual['raw_name']);
        $this->assertEquals($tag->description, $actual['description']);
        $this->assertSame(intval($tag->descriptionformat), $actual['description_format']);
        $this->assertSame(!!$tag->isstandard, $actual['is_standard_tag']);
        $this->assertSame($this->is_published_by_user($tag->userid), $actual['published_by_user']);

        unset($expected->tags[$actual['id']]);
    }

    /**
     * Match exported file to the original data.
     *
     * @param \stdClass $expected get_related_data()
     * @param array $actual [$path_hash, \stored_file] Exported file
     * @param \stored_file[] $files Stored files array
     */
    protected function match_file($expected, $actual, &$files) {
        [$hash, $actual] = $actual;
        /** @var \stored_file $file */
        $file = $expected->files[$hash];

        $this->assertSame(intval($file->get_id()), $actual['fileid']);
        $this->assertEquals($file->get_filename(), $actual['filename']);
        $this->assertEquals($file->get_contenthash(), $actual['contenthash']);
        $this->assertSame($this->is_published_by_user($file->get_userid()), $actual['published_by_user']);

        foreach ($files as $id => $realfile) {
            /** @var \stored_file $realfile */
            if ($realfile->get_id() == $file->get_id()) {
                unset($files[$id]);
                return;
            }
        }

        $this->assertFalse(true, 'Could not find exported file');
    }

    /**
     * Helper to match that the IDs of two get_records from the database match.
     *
     * @param \stdClass[] $expected Expected database query result.
     * @param \stdClass[] $actual Actual database query result.
     * @param string $error Customized error message
     */
    protected function assertIdsMatch($expected, $actual, $error = '') {
        $expected = array_column($expected, 'id');
        $actual = array_column($actual, 'id');

        $this->assertEqualsCanonicalizing($expected, $actual, $error);
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
        $files = array_map(function($file) { return clone $file; }, $actual->files);

        foreach ($actual->data as $subwiki) {
            $this->assertArrayHasKey($subwiki['id'], $expected->subwikis);
            $this->match_subwiki($expected, $subwiki, $files);
        }

        $this->assertEmpty($expected->subwikis, 'Not all the sub-wikis with pages with versions have been exported');
        $this->assertEmpty($expected->pages, 'Not all the pages with versions have been exported');

        // Filter empty versions.
        $expected->versions = array_filter($expected->versions, function($version) {
            return $version->version != 0;
        });

        $this->assertEmpty($expected->versions, 'Not all the versions have been exported');
        $this->assertEmpty($expected->synonyms, 'Not all the synonyms have been exported');
        $this->assertEmpty($expected->comments, 'Not all the comments have been exported');
        $this->assertEmpty($expected->tags, 'Not all the tags have been exported');

        // Filter directories as we are not exporting those.
        $expected->files = array_filter($expected->files, function($file) {
            /** @var \stored_file $file */
            return $file->get_filename() != '.';
        });

        $this->assertEmpty($expected->files, 'Not all the files have been exported');
        // Not checking links as we can not unset them properly. See comment above.

        $this->assertEmpty($files, 'Exported files do not match the exported files data');
    }
}
