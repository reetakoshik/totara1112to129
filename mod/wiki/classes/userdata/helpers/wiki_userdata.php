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


use context;
use mod_wiki\event\comment_deleted;
use stdClass;
use core_date;
use DateTime;
use DateTimeZone;
use moodle_database;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

/**
 * Class wiki_userdata_helper
 *
 * @package mod_wiki\userdata
 */
abstract class wiki_userdata {

    protected const TYPE = '';

    /**
     * @var target_user
     */
    protected $user;

    /**
     * @var context
     */
    protected $context;

    /**
     * @var moodle_database
     */
    protected $db;

    /**
     * User full name
     *
     * @var string
     */
    protected $name;

    /**
     * Cached page ids
     *
     * @var array|null
     */
    protected $pages = null;

    /**
     * Cached sub-wiki ids
     *
     * @var array|null
     */
    protected $subwikis = null;

    /**
     * File objects
     *
     * @var array Files
     */
    protected $files = [];

    /**
     * Array of conditions to restrict the basic sub-wiki query.
     *
     * @var array
     */
    protected $conditions = [];

    /**
     * wiki_userdata_helper constructor.
     * @param target_user $user Target user for userdata items
     * @param context $context Context object
     * @param moodle_database|null $db Database object reference
     */
    public function __construct(target_user $user, context $context, moodle_database $db = null) {
        if (is_null($db)) {
            global $DB;

            $db = $DB;
        }

        $this->user = $user;
        $this->context = $context;
        $this->db = $db;
        $this->name = fullname($this->fetch_user($user->id));

        $this->set_conditions()
             ->cache_subwikis()
             ->cache_pages();
    }

    /**
     * Set additional basic query conditions.
     *
     * @return $this
     */
    protected function set_conditions() {
        // This is a stub it should be overridden if necessary to set $conditions variable.
        // Can also be set in the overridden constructor and then parent::construct called (compulsory!).
        // How-to:
        // $conditions = [... Array of array of string conditions and params ...];
        // $conditions[] = ['userid = :user_id', ['user_id' => 2]];
        // $this->conditions = $conditions or better use like $this->conditions[] = ...;
        // Conditions are joined with and.
        // Do not forget to return $this or return parent::set_conditions();
        return $this;
    }

    /**
     * Purge all the comments related to the wiki.
     *
     * @return $this
     */
    protected function purge_comments() {

        // Need to get comments for events snapshots.
        $comments = $this->db->get_records_sql(...$this->prepare_comments_query());

        // Wipe them all.
        $this->db->delete_records_list('comments', 'id', array_keys($comments));

        // And fire the events.
        foreach ($comments as $comment) {
            // Need to get context:
            $contextid = $this->get_context_by_page($comment->itemid);

            if (!is_null($contextid)) {
                $event = comment_deleted::create([
                    'contextid' => $contextid,
                    'objectid' => $comment->id,
                    'other' => ['itemid' => $comment->itemid],
                ]);
                $event->add_record_snapshot('comments', $comment);
                $event->trigger();
            } else {
                debugging("Could not find context for comment with id = '{$comment->id}', event is not fired.");
            }
        }
        return $this;
    }

    /**
     * Export comments.
     *
     * @param \stdClass|null $page Wiki page id or null for all pages
     * @return array Comments
     */
    protected function export_comments($page = null) : array {
        return array_map(function ($comment) {
            return [
                'id' => intval($comment->id),
                'comment' => $comment->content,
                'posted_at' => intval($comment->timecreated),
                'posted_at_human' => $this->human_time($comment->timecreated),
                'posted_by_user' => $this->is_published_by_user($comment->userid),
            ];
        }, $this->db->get_records_sql(...$this->prepare_comments_query($page)));
    }

    /**
     * Export files for a sub-wiki.
     *
     * @param stdClass $subwiki Sub-wiki record.
     * @return array
     */
    protected function export_files(stdClass $subwiki) : array {
        // Looks complicated, but fairly simple:
        // 1. Get all the files for a wiki.
        // 2. Filter those by user id for collaborative wikis.
        // 3. Export only id, file and hash.
        $files = get_file_storage()->get_area_files($subwiki->context_id, 'mod_wiki', 'attachments', $subwiki->id, 'filename', false);

        // Filter down to only files uploaded by user for collaborative wikis.
        // For individual wikis we do export for the wiki regardless of the author.
        if (!$this->is_individual()) {
            $files = array_filter($files, function($file) {
                /** @var $file \stored_file */
                return $this->user->id == $file->get_userid();
            });
        }

        return array_map(function($file) {
            /** @var $file \stored_file */
            $this->files[] = $file;
            return [
                'fileid' => intval($file->get_id()),
                'filename' => $file->get_filename(),
                'contenthash' => $file->get_contenthash(),
                'published_by_user' => $this->is_published_by_user($file->get_userid()),
            ];
        }, $files);
    }

    /**
     * Export page versions.
     *
     * @param stdClass $page Page record.
     * @return array
     */
    protected function export_versions(stdClass $page) : array {
        $id = intval($page->id);
        $userid = intval($this->user->id);
        $usercheck = $this->is_individual() ? '' : "AND userid = {$userid}";

        // We exclude version 0 as it's always empty and created at the time the page is created.
        return array_map(function($version) {
            return [
                'id' => intval($version->id),
                'version' => intval($version->version),
                'content' => $version->content,
                'format' => $version->contentformat,
                'created_at' => intval($version->timecreated),
                'created_at_human' => $this->human_time($version->timecreated),
            ];
        }, $this->db->get_records_sql("SELECT * FROM {wiki_versions} WHERE pageid = {$id} {$usercheck} AND version > 0 ORDER BY version"));
    }

    /**
     * Fetch user from the database.
     *
     * @param int $id User id
     * @return false|\stdClass
     */
    protected function fetch_user($id) {
        // Not returning guest user here.
        if (($id = intval($id)) < 2) {
            return false;
        }

        return $this->db->get_record('user', ['id' => $id]);
    }

    /**
     * Prepare query to account for context restrictions.
     *
     * @param string $field Activity id field
     * @param string $userfield User id field
     * @return array [SQL where, SQL joins, SQL where params]
     */
    protected function prepare_query(string $field = 'target.id', string $userfield = 'target.userid') : array {
        $where = ["activity.wikimode = '" . static::TYPE . "'"];

        if (trim($userfield) != '') {
            $userfield = clean_param($userfield, PARAM_TEXT);
            $where[] = "{$userfield} = :user_id";
        }

        $params = ['user_id' => $this->user->id];
        $joins = item::get_activities_join($this->context, 'wiki', $field, 'activity');

        // AND is hardcoded as it's fine here.
        $where = trim(implode(' AND ', $where));
        $joins = trim($joins);

        return [
            $where,
            $joins,
            $params
        ];

    }

    /**
     * Store all the sub-wikis as a class property.
     *
     * @return $this
     */
    protected function cache_subwikis() {
        $this->subwikis = $this->db->get_records_sql(...$this->prepare_base_query());
        return $this;
    }

    /**
     * Get IDs of cached sub-wikis as an array or comma-separated string
     *
     * @param bool $asstring Flag whether to return as as comma-separated string
     * @return array|string Array or list of cached sub-wiki IDs
     */
    protected function get_cached_subwiki_ids($asstring = false) {
        if ($asstring) {
            return !empty($this->subwikis) ? implode(', ', array_column($this->subwikis, 'id')) : '-1';
        }

        return !is_null($this->subwikis) ? array_column($this->subwikis, 'id') : [];
    }

    /**
     * Store all the related wiki pages as a class property
     *
     * @return $this
     */
    protected function cache_pages() {
        $this->pages = $this->db->get_records_sql(...$this->prepare_page_query());
        return $this;
    }

    /**
     * Get IDs of cached pages as an array or comma-separated string
     *
     * @param bool $asstring Flag whether to return as as comma-separated string
     * @return array|string Array or list of cached sub-wiki IDs
     */
    protected function get_cached_page_ids($asstring = false) {
        if ($asstring) {
            return !empty($this->pages) ? implode(', ', array_column($this->pages, 'id')) : '-1';
        }

        return !empty($this->pages) ? array_column($this->pages, 'id') : [];
    }

    /**
     * Reset files array
     *
     * @return $this
     */
    protected function reset_files() {
        $this->files = [];

        return $this;
    }

    /**
     * Prepare base SQL query to select sub-wikis.
     *
     * @param bool $extended Fetch relevant data as well as basic stuff
     *
     * @return array [$sql, $params]
     */
    protected function prepare_base_query($extended = true) : array {
        // Base query selects sub-wikis which is a basis for all other queries.
        [$where, $joins, $params] = $this->prepare_query('target.wikiid', $this->is_individual() ? 'target.userid' : '');

        $what = $extended ?
                'target.*, ctx.id as context_id, cm.id as cm_id, activity.name as wiki_name, activity.intro as wiki_intro, activity.id as wiki_id' :
                'target.id';

        // Accounting for extra conditions.
        foreach ($this->conditions as $condition) {
            if (is_array($condition)) {
                if (!isset($condition[1])) {
                    $params = array_merge($params, $condition[1]);
                }
                $condition = $condition[0];
            }

            // It's designed in the way that where is never empty here so can safely add AND.
            $where .= " AND {$condition}";
        }

        return ["SELECT {$what} FROM {wiki_subwikis} target $joins WHERE $where", $params];
    }

    /**
     * Prepare base SQL query to select wiki pages.
     *
     * @param bool $assubquery a flag to select only ids
     * @return array [$sql, $params]
     */
    protected function prepare_page_query($assubquery = false) : array {
        // Base query to select pages.
        $what = $assubquery ? 'pages.id' : 'pages.*';

        [$base, $params] = $this->prepare_base_query(false);

        if (is_null($this->subwikis)) {
            $base = $this->cache_subwikis()
                         ->get_cached_subwiki_ids(true);
        }

        return ["SELECT {$what} FROM {wiki_pages} pages WHERE pages.subwikiid IN ({$base})", $params];
    }

    /**
     * Prepare query to select comments
     *
     * @param int|null $pageid Page id or null to select all comments which belong to a sub-wiki
     * @param bool $assubquery If true will select only IDs
     * @return array [$query, $params]
     */
    protected function prepare_comments_query(int $pageid = null, bool $assubquery = false) : array {

        $pageid = is_null($pageid) ? $this->get_cached_page_ids(true) : $pageid;
        $condition = '';

        if (!$this->is_individual()) {
            $user = intval($this->user->id);
            $condition = " AND userid = {$user}";
        }

        $select = $assubquery ? 'id' : '*';

        return ["SELECT $select
                 FROM {comments}
                 WHERE component = 'mod_wiki' AND commentarea = 'wiki_page' AND itemid IN ({$pageid}) {$condition}
                 ORDER BY timecreated", []];
    }

    /**
     * Prepare query to select tags.
     *
     * @param int|null $page Page id or null to select all comments which belong to a sub-wiki
     * @return array [$query, $params]
     */
    protected function prepare_tags_query($page = null) : array {
        if (empty($page)) {
            $pages = implode(', ', $this->pages);
            $condition = "tag_instances.itemid IN ({$pages})";
        } else {
            $page = intval($page);
            $condition = "tag_instances.itemid = {$page}";
        }

        return ["SELECT tag_instances.id as instanceid, tag_instances.timecreated, tag_instances.timemodified,
                        tags.name, tags.rawname, tags.description, tags.descriptionformat, tags.isstandard, tags.userid, tags.id
                 FROM {tag_instance} tag_instances
                 JOIN {tag} tags ON tag_instances.tagid = tags.id
                 WHERE tag_instances.component = 'mod_wiki' AND tag_instances.itemtype = 'wiki_pages' AND {$condition}
                 ORDER BY tag_instances.ordering", []];
    }

    /**
     * Convert timestamp to a human readable time string in the exported user timezone.
     *
     * @param int $timestamp Timestamp
     * @return string
     */
    protected function human_time($timestamp) : string {
        // Fail safe if timestamp is not set, also prevents giving 1970.
        if (empty($timestamp)) {
            return '';
        }

        $date = new DateTime("@$timestamp");
        $date->setTimezone(new DateTimeZone(core_date::normalise_timezone($this->user->timezone)));
        return $date->format('F j, Y, g:i a T');
    }

    /**
     * Returns the boolean representation of user id to obscure the real user who published page or a comment.
     *
     * This is needed to provide indication of the author of the item when exporting individual wiki, as it has
     * been decided to export revisions and comments made by trainer (admin) who has access to individual users wiki
     * and can create pages or post comments.
     *
     * @param |int $id User id or object from the db record
     * @return bool
     */
    protected function is_published_by_user($id) : bool {
        return $id == $this->user->id;
    }

    /**
     * Returns whether it is related to individual wiki mode.
     *
     * @return bool
     */
    protected function is_individual() : bool {
        return static::TYPE == 'individual';
    }

    /**
     * Get context id based on page id (required for firing some events)
     *
     * @param int $id Page id
     * @return int|null null if page or subwikiid is not set (things happen).
     */
    protected function get_context_by_page($id) : ?int {
        if ($this->context->contextlevel == CONTEXT_MODULE) {
            return intval($this->context->id);
        }

        if (!isset($this->pages[$id])) {
            return null;
        }

        $page = $this->pages[$id];

        if (!isset($this->subwikis[$page->subwikiid])) {
            return null;
        }

        return $this->subwikis[$page->subwikiid]->context_id;
    }
}