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

use mod_wiki\userdata\collaborative_versions;
use totara_userdata\userdata\target_user;
use totara_userdata\userdata\item;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/wiki/tests/wiki_testcase.php');

/**
 * This class tests exporting userdata collaborative wiki item.
 * Please note that these tests fully cover the functionality of
 * related helper classes.
 *
 * Class mod_wiki_userdata_collaborative_versions_test
 *
 * @group mod_wiki
 * @group totara_userdata
 */
class mod_wiki_userdata_collaborative_versions_test extends wiki_testcase {

    /**
     * Reusable human-readable error messages.
     *
     * @param string $error Error slug
     * @return array|string Error message(s)
     */
    protected function errors($error = '') {
        $errors = [

        ];

        if ($error != '') {
            return in_array($error, $errors) ? $errors[$error] : 'Something went wrong';
        }

        return $errors;
    }

    public function test_it_is_countable() {
        $this->assertTrue(collaborative_versions::is_countable(), 'Collaborative versions wiki user_data must be countable');
    }

    public function test_it_is_exportable() {
        $this->assertTrue(collaborative_versions::is_exportable(), 'Collaborative versions wiki user_data must be exportable');
    }

    public function test_it_is__not_purgeable() {
        $error = 'Collaborative versions wiki user_data must NOT be purgeable';

        $this->assertFalse(collaborative_versions::is_purgeable(target_user::STATUS_ACTIVE), $error);
        $this->assertFalse(collaborative_versions::is_purgeable(target_user::STATUS_DELETED), $error);
        $this->assertFalse(collaborative_versions::is_purgeable(target_user::STATUS_SUSPENDED), $error);
    }

    public function test_it_is_compatible_with_wide_range_of_contexts() {
        $expected = [
            CONTEXT_SYSTEM,
            CONTEXT_COURSECAT,
            CONTEXT_COURSE,
            CONTEXT_MODULE
        ];

        $contexts = collaborative_versions::get_compatible_context_levels();

        $this->assertEqualsCanonicalizing($expected, $contexts,
            "Collaborative versions wiki user_data item is expected to work with a wide range of contexts");
    }

    public function test_it_exports_collaborative_wiki_versions_for_system_context() {
        $data = $this->seed();

        $user = array_values($data['users'])[0];
        $this->setUser($user);
        $whatwehavehere = $this->get_related_data($user, $context = context_system::instance());

        $export = collaborative_versions::execute_export(new target_user($user), $context);
        $this->assertExportMatches($whatwehavehere, $export);
    }

    public function test_it_exports_collaborative_wiki_versions_for_course_category_context() {
        $data = $this->seed();

        $context = context_coursecat::instance($cat = array_keys($data['cats'])[0]);
        $user = array_values($data['users'])[0];

        $this->setUser($user);
        $whatwehavehere = $this->get_related_data($user, $context);

        $export = collaborative_versions::execute_export(new target_user($user), $context);
        $this->assertExportMatches($whatwehavehere, $export);
    }

    public function test_it_exports_collaborative_wiki_versions_for_course_context() {
        $data = $this->seed();

        $context = context_course::instance($course = intval(array_keys(array_values($data['cats'])[0])[0]));
        $user = array_values($data['users'])[0];

        $this->setUser($user);
        $whatwehavehere = $this->get_related_data($user, $context);

        $export = collaborative_versions::execute_export(new target_user($user), $context);
        $this->assertExportMatches($whatwehavehere, $export);
    }

    public function test_it_exports_collaborative_wiki_versions_for_course_module_context() {
        $data = $this->seed();

        $module = get_coursemodule_from_instance('wiki',
            array_keys(array_values(array_values($data['cats'])[0])[0])[1]);
        $user = array_values($data['users'])[0];
        $context = context_module::instance($module->id);

        $this->setUser($user);
        $whatwehavehere = $this->get_related_data($user, $context);

        $export = collaborative_versions::execute_export(new target_user($user), $context);
        $this->assertExportMatches($whatwehavehere, $export);
    }

    public function test_it_counts_collaborative_wiki_versions_for_system_context() {
        $data = $this->seed();

        $user = array_values($data['users'])[0];

        $this->assertEquals($this->count_versions($user, $context = context_system::instance()),
            collaborative_versions::execute_count(new target_user($user), $context));
    }

    public function test_it_counts_collaborative_wiki_versions_for_course_category_context() {
        $data = $this->seed();

        $context = context_coursecat::instance($cat = array_keys($data['cats'])[0]);
        $user = new target_user(array_values($data['users'])[0]);

        $this->assertEquals($this->count_versions($user, $context),
            collaborative_versions::execute_count(new target_user($user), $context));
    }

    public function test_it_counts_collaborative_wiki_versions_for_course_context() {
        $data = $this->seed();

        $context = context_course::instance($course = intval(array_keys(array_values($data['cats'])[0])[0]));
        $user = new target_user(array_values($data['users'])[0]);

        $this->assertEquals($this->count_versions($user, $context),
            collaborative_versions::execute_count(new target_user($user), $context));
    }

    public function test_it_counts_collaborative_wiki_versions_for_course_module_context() {
        $data = $this->seed();

        $module = get_coursemodule_from_instance('wiki',
            array_keys(array_values(array_values($data['cats'])[0])[0])[1]);
        $user = new target_user(array_values($data['users'])[0]);
        $context = context_module::instance($module->id);

        $this->assertEquals($this->count_versions($user, $context),
            collaborative_versions::execute_count(new target_user($user), $context));
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

        $user = intval($user->id);

        // We need only sub-wikis with user files.
        $subwikis = $DB->get_records_sql(
            "SELECT subwikis.*, activities.name as wiki_name, activities.intro as wiki_intro, ctx.id as context_id
                  FROM {wiki_subwikis} subwikis {$joins}
                  WHERE subwikis.userid = 0 AND activities.wikimode = 'collaborative' AND
                      EXISTS (SELECT versions.id
                              FROM {wiki_versions} versions
                              JOIN {wiki_pages} pages ON versions.pageid = pages.id
                              WHERE versions.version <> 0 AND versions.userid = {$user}
                                  AND pages.subwikiid = subwikis.id)");

        // Check that there is something...
        if (empty($subwikis)) {
            // Squawk at the user and blow up.
            throw new coding_exception("No related data there, oops.");
        }

        // Get pages only with user created versions.
        $subwikiids = $this->normalize_ids($subwikis);
        $pages = $DB->get_records_sql(
            "SELECT pages.* FROM {wiki_pages} pages
                  WHERE pages.subwikiid {$subwikiids} AND EXISTS
                   (SELECT versions.id FROM {wiki_versions} versions
                     WHERE versions.pageid = pages.id AND versions.userid = {$user})");

        $pageids = $this->normalize_ids($pages);

        // And then when we have main sub-wiki ids let's rock and roll and select data from all the related tables.
        return (object) [
            'subwikis' => $subwikis,
            'pages' => $pages,
            'versions' => $this->get_versions($pageids, $user),
        ];
    }

    /**
     * Count individual wiki instances.
     *
     * @param \stdClass|\totara_userdata\userdata\target_user $user User object
     * @param $context
     * @return int Number of wiki instances
     */
    protected function count_versions($user, $context) {
        global $DB;

        $user = intval($user->id);
        $joins = item::get_activities_join($context, 'wiki', 'subwikis.wikiid', 'activities');

        return $DB->count_records_sql(
            "SELECT count(versions.id)
                  FROM {wiki_versions} versions 
                  JOIN {wiki_pages} pages ON versions.pageid = pages.id
                  JOIN {wiki_subwikis} subwikis ON pages.subwikiid = subwikis.id
                  {$joins}
                  WHERE activities.wikimode = 'collaborative' AND subwikis.userid = 0
                      AND versions.userid = {$user} AND versions.version <> 0");
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

        foreach ($actual['versions'] as $version) {
            $this->assertArrayHasKey($version['id'], $expected->versions);
            $this->match_version($expected, $version);
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
     * Entry point to match the exported data to the original data.
     *
     * @param \stdClass $expected get_related_data()
     * @param \totara_userdata\userdata\export $actual Actual export
     */
    protected function assertExportMatches($expected, $actual) {
        // Clone expected things so we can safely unset those.
        $expected = clone $expected;

        foreach ($actual->data as $subwiki) {
            $this->assertArrayHasKey($subwiki['id'], $expected->subwikis);
            $this->match_subwiki($expected, $subwiki);
        }

        $this->assertEmpty($expected->subwikis, 'Not all the sub-wikis with pages with versions have been exported');
        $this->assertEmpty($expected->pages, 'Not all the pages with versions have been exported');

        // Filter empty versions.
        $expected->versions = array_filter($expected->versions, function($version) {
            return $version->version != 0;
        });

        $this->assertEmpty($expected->versions, 'Not all the versions have been exported');

        $this->assertEmpty($actual->files, 'Collaborative wiki versions must not have files int the export.');
    }
}
