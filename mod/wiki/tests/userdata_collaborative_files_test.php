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

use mod_wiki\userdata\collaborative_files;
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
 * Class mod_wiki_userdata_collaborative_files_test
 *
 * @group mod_wiki
 * @group totara_userdata
 */
class mod_wiki_userdata_collaborative_files_test extends wiki_testcase {

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
        $this->assertTrue(collaborative_files::is_countable(), 'Collaborative files wiki user_data must be countable');
    }

    public function test_it_is_exportable() {
        $this->assertTrue(collaborative_files::is_exportable(), 'Collaborative files wiki user_data must be exportable');
    }

    public function test_it_is_not_purgeable() {
        $error = 'Collaborative files wiki user_data must NOT be purgeable';

        $this->assertFalse(collaborative_files::is_purgeable(target_user::STATUS_ACTIVE), $error);
        $this->assertFalse(collaborative_files::is_purgeable(target_user::STATUS_DELETED), $error);
        $this->assertFalse(collaborative_files::is_purgeable(target_user::STATUS_SUSPENDED), $error);
    }

    public function test_it_is_compatible_with_wide_range_of_contexts() {
        $expected = [
            CONTEXT_SYSTEM,
            CONTEXT_COURSECAT,
            CONTEXT_COURSE,
            CONTEXT_MODULE
        ];

        $contexts = collaborative_files::get_compatible_context_levels();

        $this->assertEqualsCanonicalizing($expected, $contexts,
            "Collaborative files wiki user_data item is expected to work with a wide range of contexts");
    }

    public function test_it_exports_collaborative_wiki_files_for_system_context() {
        $data = $this->seed();

        $user = array_values($data['users'])[0];
        $this->setUser($user);
        $whatwehavehere = $this->get_related_data($user, $context = context_system::instance());

        $export = collaborative_files::execute_export(new target_user($user), $context);
        $this->assertExportMatches($whatwehavehere, $export);
    }

    public function test_it_exports_collaborative_wiki_files_for_course_category_context() {
        $data = $this->seed();

        $context = context_coursecat::instance($cat = array_keys($data['cats'])[0]);
        $user = array_values($data['users'])[0];

        $this->setUser($user);
        $whatwehavehere = $this->get_related_data($user, $context);

        $export = collaborative_files::execute_export(new target_user($user), $context);
        $this->assertExportMatches($whatwehavehere, $export);
    }

    public function test_it_exports_collaborative_wiki_files_for_course_context() {
        $data = $this->seed();

        $context = context_course::instance($course = intval(array_keys(array_values($data['cats'])[0])[0]));
        $user = array_values($data['users'])[0];

        $this->setUser($user);
        $whatwehavehere = $this->get_related_data($user, $context);

        $export = collaborative_files::execute_export(new target_user($user), $context);
        $this->assertExportMatches($whatwehavehere, $export);
    }

    public function test_it_exports_collaborative_wiki_files_for_course_module_context() {
        $data = $this->seed();

        $module = get_coursemodule_from_instance('wiki',
            array_keys(array_values(array_values($data['cats'])[0])[0])[1]);
        $user = array_values($data['users'])[0];
        $context = context_module::instance($module->id);

        $this->setUser($user);
        $whatwehavehere = $this->get_related_data($user, $context);

        $export = collaborative_files::execute_export(new target_user($user), $context);
        $this->assertExportMatches($whatwehavehere, $export);
    }

    public function test_it_counts_collaborative_wiki_files_for_system_context() {
        $data = $this->seed();

        $user = array_values($data['users'])[0];

        $this->assertEquals($this->count_files($user, $context = context_system::instance()),
            collaborative_files::execute_count(new target_user($user), $context));
    }

    public function test_it_counts_collaborative_wiki_files_for_course_category_context() {
        $data = $this->seed();

        $context = context_coursecat::instance($cat = array_keys($data['cats'])[0]);
        $user = new target_user(array_values($data['users'])[0]);

        $this->assertEquals($this->count_files($user, $context),
            collaborative_files::execute_count(new target_user($user), $context));
    }

    public function test_it_counts_collaborative_wiki_files_for_course_context() {
        $data = $this->seed();

        $context = context_course::instance($course = intval(array_keys(array_values($data['cats'])[0])[0]));
        $user = new target_user(array_values($data['users'])[0]);

        $this->assertEquals($this->count_files($user, $context),
            collaborative_files::execute_count(new target_user($user), $context));
    }

    public function test_it_counts_collaborative_wiki_files_for_course_module_context() {
        $data = $this->seed();

        $module = get_coursemodule_from_instance('wiki',
            array_keys(array_values(array_values($data['cats'])[0])[0])[1]);
        $user = new target_user(array_values($data['users'])[0]);
        $context = context_module::instance($module->id);

        $this->assertEquals($this->count_files($user, $context),
            collaborative_files::execute_count(new target_user($user), $context));
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
                      EXISTS (SELECT files.id
                              FROM {files} files
                              WHERE files.component = 'mod_wiki' AND files.filearea = 'attachments'
                                  AND files.itemid = subwikis.id AND files.userid = {$user})");

        // Check that there is something...
        if (empty($subwikis)) {
            // Squawk at the user and blow up.
            throw new coding_exception("No related data there, oops.");
        }

        // And then when we have main sub-wiki ids let's rock and roll and select data from all the related tables.
        return (object) [
            'subwikis' => $subwikis,
            'files' => $this->get_files($subwikis, false, $user),
        ];
    }

    /**
     * Get all the files for the given sub-wikis, using file API.
     *
     * @param int|string $subwikis Id or comma-separated list of IDs
     * @param bool $includedirs Whether to include '.' on export
     * @param int $user Filter files by user id
     * @return \stored_file[] Stored files
     *
     */
    protected function get_files($subwikis, $includedirs = true, $user = null) {
        $files = [];
        $fs = get_file_storage();
        foreach ($subwikis as $subwiki) {
            $files += $fs->get_area_files($subwiki->context_id, 'mod_wiki', 'attachments', $subwiki->id, 'filename', $includedirs, 0, $user);
        }

        return $files;
    }

    /**
     * Count individual wiki instances.
     *
     * @param \stdClass|\totara_userdata\userdata\target_user $user User object
     * @param $context
     * @return int Number of wiki instances
     */
    protected function count_files($user, $context) {
        global $DB;

        $user = intval($user->id);
        $joins = item::get_activities_join($context, 'wiki', 'subwikis.wikiid', 'activities');

        return $DB->count_records_sql(
            "SELECT count(files.id)
                  FROM {files} files 
                  JOIN {wiki_subwikis} subwikis ON files.itemid = subwikis.id
                  {$joins}
                  WHERE activities.wikimode = 'collaborative' AND subwikis.userid = 0
                      AND files.component = 'mod_wiki' AND files.filearea = 'attachments'
                      AND files.filename <> '.' AND files.userid = {$user}");
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

        foreach ($actual['files'] as $id => $file) {
            $this->assertArrayHasKey($id, $expected->files);
            $this->match_file($expected, [$id, $file] , $files);
            unset($expected->files[$id]);
        }

        unset($expected->subwikis[$actual['id']]);
    }

    /**
     * Match exported file to the original data.
     *
     * @param \stdClass $expected get_related_data()
     * @param array $actual Exported file [$path_hash, \stored_file]
     * @param \stored_file[] $files Stored files array
     */
    protected function match_file($expected, $actual, &$files) {
        [$hash, $actual] = $actual;
        /** @var \stored_file $file */
        $file = $expected->files[$hash];

        $this->assertSame(intval($file->get_id()), $actual['fileid']);
        $this->assertEquals($file->get_filename(), $actual['filename']);
        $this->assertEquals($file->get_contenthash(), $actual['contenthash']);
        $this->assertTrue($actual['published_by_user']);

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
            $this->match_subwiki($expected, $subwiki, $files);
        }

        $this->assertEmpty($expected->subwikis, 'Not all the sub-wikis with files have been exported');
        $this->assertEmpty($expected->files, 'Not all the files have been exported');

        $this->assertEmpty($files, 'Exported files do not match the exported files data');
    }
}
