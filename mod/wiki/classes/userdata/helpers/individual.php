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

namespace mod_wiki\userdata\helpers;


use mod_wiki\event\page_deleted;
use mod_wiki\event\page_locks_deleted;
use mod_wiki\event\page_version_deleted;
use stdClass;

/** @var $CFG \stdClass */
require_once("{$CFG->dirroot}/tag/lib.php");

/**
 * Class individual_helper
 *
 * @package mod_wiki\userdata
 */
final class individual extends wiki_userdata {

    /**
     * @var string TYPE Wiki mode
     */
    protected const TYPE = 'individual';

    /**
     * Purge individual wiki instances.
     *
     * @return void
     */
    public function purge() : void {
        // The purged items depend on each other we don't want to have incomplete wiki instances if something fails.
        // The order of deletion should prevent orphan entries anyway, except having missing files.
        $transaction = $this->db->start_delegated_transaction();

        $this->purge_files()
            ->purge_tags()
            ->purge_comments()
            ->purge_links()
            ->purge_locks()
            ->purge_synonyms()
            ->purge_versions()
            ->purge_pages()
            ->purge_subwikis();

        $transaction->allow_commit();
    }

    /**
     * Perform export of user-related mod_wiki content.
     *
     * @return array [$data, $files]
     */
    public function export() : array {
        $export = [];

        // Clearing files on subsequent calls (for some reason somebody may do that).
        $this->reset_files();

        foreach ($this->subwikis as $record) {
            $export[intval($record->id)] = [
                'id' => intval($record->id),
                'name' => $record->wiki_name,
                'intro' => $record->wiki_intro,
                'pages' => $this->export_pages($record),
                'files' => $this->export_files($record),
            ];
        }

        return [$export, $this->files];
    }

    /**
     * Count wiki instances for a given user.
     *
     * @return int
     */
    public function count() : int {
        return count($this->get_cached_subwiki_ids());
    }

    /**
     * Export sub-wiki pages.
     *
     * @param stdClass $subwiki Sub-wiki record
     * @return array
     */
    protected function export_pages(stdClass $subwiki) : array {
        $pages = array_filter($this->pages, function ($page) use ($subwiki) { return $page->subwikiid == $subwiki->id; });

        $export = [];

        foreach ($pages as $page) {
            $export[intval($page->id)] = [
                'id' => intval($page->id),
                'title' => $page->title,
                'latest_version' => $page->cachedcontent,
                'created_at' => intval($page->timecreated),
                'created_at_human' => $this->human_time($page->timecreated),
                'updated_at' => intval($page->timemodified),
                'updated_at_human' => $this->human_time($page->timemodified),
                'rendered_at' => intval($page->timerendered),
                'rendered_at_human' => $this->human_time($page->timerendered),
                'last_updated_by' => $this->is_published_by_user($page->userid),
                'versions' => $this->export_versions($page),
                'page_links' => $this->export_links($page),
                'synonyms' => $this->export_synonyms($page),
                'comments' => $this->export_comments($page->id),
                'tags' => $this->export_tags($page),
            ];
        }

        return $export;
    }

    /**
     * Export wiki page synonyms.
     *
     * @param stdClass $page Page record
     * @return array
     */
    protected function export_synonyms(stdClass $page) {
        // Synonyms aren't actually used anywhere. There is plenty of code related to deleting synonyms, but I haven't
        // seen any related to storing synonyms (except restoring from a backup).
        return array_column($this->db->get_records('wiki_synonyms', ['pageid' => $page->id]), 'pagesynonym');
    }

    /**
     * Export wiki page links from and to.
     *
     * @param stdClass $page Page record
     * @return array
     */
    protected function export_links(stdClass $page) {
        $id = intval($page->id);

        $links = $this->db->get_records_sql("SELECT links.frompageid, links.topageid FROM {wiki_links} links 
                                                  WHERE links.frompageid = {$id} OR links.topageid = {$id}");

        // It looks complicated but it only filters the links to the IDs of existing pages and runs it through intval.
        return [
            'links_to' => array_map('intval', array_column(array_filter($links, function($link) use ($id) {
                            return $link->frompageid == $id && $link->topageid != 0;
                        }), 'topageid')),

            'linked_to' => array_map('intval', array_column(array_filter($links, function($link) use ($id) {
                            return $link->topageid == $id && $link->frompageid != 0;
                        }), 'frompageid')),
        ];
    }

    /**
     * Export tags for a page.
     *
     * @param stdClass $page Page record.
     * @return array
     */
    protected function export_tags(stdClass $page) : array {
        return array_map(function($tag) {
            return [
                'id' => intval($tag->instanceid),
                'tag_id' => intval($tag->id),
                'applied_at' => intval($tag->timecreated),
                'applied_at_human' => $this->human_time($tag->timecreated),
                'instance_modified_at' => intval($tag->timemodified),
                'instance_modified_at_human' => $this->human_time($tag->timemodified),
                'name' => $tag->name,
                'raw_name' => $tag->rawname,
                'description' => $tag->description,
                'description_format' => intval($tag->descriptionformat),
                'is_standard_tag' => !!$tag->isstandard,
                'published_by_user' => $this->is_published_by_user($tag->userid),
            ];
        }, $this->db->get_records_sql(...$this->prepare_tags_query($page->id)));
    }

    /**
     * Purge wiki related files.
     *
     * @return $this
     */
    protected function purge_files() {
        $fs = get_file_storage();

        foreach ($this->subwikis as $subwiki) {
            $fs->delete_area_files($subwiki->context_id, 'mod_wiki', 'attachments', $subwiki->id);
        }

        return $this;
    }

    /**
     * Purge wiki related tags.
     *
     * @return $this
     */
    protected function purge_tags() {
        // Using handy-dandy tags removal API.
        foreach ($this->pages as $page) {
            core_tag_remove_instances('mod_wiki', 'wiki_pages', $page->id);
        }

        return $this;
    }

    /**
     * Purge wiki related links.
     *
     * @return $this
     */
    protected function purge_links() {
        $this->db->delete_records_list('wiki_links','subwikiid', $this->get_cached_subwiki_ids());

        return $this;
    }

    /**
     * Purge wiki related synonyms.
     *
     * @return $this
     */
    protected function purge_synonyms() {
        $this->db->delete_records_list('wiki_synonyms', 'subwikiid', $this->get_cached_subwiki_ids());

        return $this;
    }

    /**
     * Purge wiki-related page locks.
     *
     * @return $this
     */
    protected function purge_locks() {
        // Wiping page-locks.
        $this->db->delete_records_list('wiki_locks', 'pageid', $this->get_cached_page_ids());

        // Firing the events.
        // Event is fired per page.
        foreach ($this->pages as $page) {
            if ($contextid = $this->get_context_by_page($page->id)) {
                page_locks_deleted::create([
                    'contextid' => $contextid,
                    'objectid' => $page->id,
                    'relateduserid' => $this->user->id,
                ])->trigger();
            } else {
                debugging("Could not find context for page with id = \'{$page->id}\', event is not fired.");
            }
        }

        return $this;
    }

    /**
     * Purge wiki-related page versions.
     *
     * @return $this
     */
    protected function purge_versions() {
        $ids = $this->get_cached_page_ids(true);
        $versions = $this->db->get_records_sql("SELECT * FROM {wiki_versions} WHERE pageid IN ({$ids})");

        // Wipe them all.
        $this->db->delete_records_list('wiki_versions', 'pageid', $this->get_cached_page_ids());

        // Trigger the events.
        foreach ($versions as $version) {
            $event = page_version_deleted::create([
                'contextid' => $this->get_context_by_page($version->pageid),
                'objectid' => $version->id,
                'other' => ['pageid' => $version->pageid],
            ]);
            $event->add_record_snapshot('wiki_versions', $version);
            $event->trigger();
        }

        return $this;
    }

    /**
     * Purge wiki-related pages.
     *
     * @return $this
     */
    protected function purge_pages() {
        // Wiping pages.
        $this->db->delete_records_list('wiki_pages', 'subwikiid', $this->get_cached_subwiki_ids());

        // Firing the events.
        foreach ($this->pages as $page) {
            if ($contextid = $this->get_context_by_page($page->id)) {
                $event = page_deleted::create([
                    'contextid' => $contextid,
                    'objectid' => $page->id,
                    'other' => ['subwikiid' => $page->subwikiid],
                ]);

                $event->add_record_snapshot('wiki_pages', $page);
                $event->trigger();
            } else {
                debugging("Could not find context for page with id = \'{$page->id}\', event is not fired.");
            }
        }

        return $this;
    }

    /**
     * Purge sub-wikis
     *
     * @return $this
     */
    protected function purge_subwikis() {
        $this->db->delete_records_list('wiki_subwikis', 'id', $this->get_cached_subwiki_ids());

        return $this;
    }
}