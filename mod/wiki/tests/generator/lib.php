<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * mod_wiki data generator.
 *
 * @package    mod_wiki
 * @category   test
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_wiki\event\page_updated;

defined('MOODLE_INTERNAL') || die();

/**
 * mod_wiki data generator class.
 *
 * @package    mod_wiki
 * @category   test
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_wiki_generator extends testing_module_generator {

    /**
     * @var int keep track of how many pages have been created.
     */
    protected $pagecount = 0;

    /**
     * To be called from data reset code only,
     * do not use in tests.
     * @return void
     */
    public function reset() {
        $this->pagecount = 0;
        parent::reset();
    }

    public function create_instance($record = null, array $options = null) {
        // Add default values for wiki.
        $record = (array)$record + array(
            'wikimode' => 'collaborative',
            'firstpagetitle' => 'Front page for wiki '.($this->instancecount+1),
            'defaultformat' => 'html',
            'forceformat' => 0
        );

        return parent::create_instance($record, (array)$options);
    }

    public function create_content($wiki, $record = array()) {
        $record = (array)$record + array(
            'wikiid' => $wiki->id
        );
        return $this->create_page($wiki, $record);
    }

    public function create_first_page($wiki, $record = array()) {
        $record = (array)$record + array(
            'title' => $wiki->firstpagetitle,
        );
        return $this->create_page($wiki, $record);
    }

    /**
     * Generates a page in wiki.
     *
     * @param stdClass wiki object returned from create_instance (if known)
     * @param stdClass|array $record data to insert as wiki entry.
     * @param \stdClass|int $author Page creator id\object.
     * @return stdClass
     * @throws coding_exception if neither $record->wikiid nor $wiki->id is specified
     */
    public function create_page($wiki, $record = array(), $author = null) {
        global $CFG, $USER;
        require_once($CFG->dirroot.'/mod/wiki/locallib.php');

        $defaults = [
            'title' => "wiki page {$this->pagecount}",
            'wikiid' => $wiki->id,
            'subwikiid' => 0,
            'group' => 0,
            'userid' => $wiki->wikimode == 'individual' ? $USER->id : 0,
            'content' => "Wiki page content {$this->pagecount}",
            'format' => $wiki->defaultformat
        ];

        if (is_object($author)) {
            $author = $author->id;
        } elseif (is_null($author)) {
            $author = $USER->id;
        } else {
            $author = intval($author);
        }

        $record = array_merge($defaults, $record);

        // Fail-safe collaborative wikis always have userid set to 0.
        $record['userid'] = $wiki->wikimode == 'individual' ? $record['userid'] : 0;

        if (empty($record['wikiid']) && empty($record['subwikiid'])) {
            throw new coding_exception('wiki page generator requires either wikiid or subwikiid');
        }
        if (!$record['subwikiid']) {
            if ($subwiki = wiki_get_subwiki_by_group($record['wikiid'], $record['group'], $record['userid'])) {
                $record['subwikiid'] = $subwiki->id;
            } else {
                $record['subwikiid'] = wiki_add_subwiki($record['wikiid'], $record['group'], $record['userid']);
            }
        }

        $page = wiki_get_page_by_title($record['subwikiid'], $record['title']);
        if (!$page) {
            $pageid = wiki_create_page($record['subwikiid'], $record['title'], $record['format'], $author);
            $page = wiki_get_page($pageid);
        }
        $rv = wiki_save_page($page, $record['content'], $author);

        if (array_key_exists('tags', $record)) {
            $tags = is_array($record['tags']) ? $record['tags'] : preg_split('/,/', $record['tags']);
            if (empty($wiki->cmid)) {
                $cm = get_coursemodule_from_instance('wiki', $wiki->id, isset($wiki->course) ? $wiki->course : 0);
                $wiki->cmid = $cm->id;
            }
            core_tag_tag::set_item_tags('mod_wiki', 'wiki_pages', $page->id,
                    context_module::instance($wiki->cmid), $tags);
        }
        $this->pagecount++;
        return $rv['page'];
    }

    /**
     * Update wiki page
     *
     * @param int $page Wiki page id
     * @param string $content New wiki page content
     * @param \stdClass|int|null $user User id or object or null to get current user
     * @param array $params Record object (will overwrite $page and $content if supplied
     * @param bool $trigger Whether to trigger page updated events. Slows down the process quite a bit, Do not use unless needed.
     * @return object Updated page version! object.
     */
    public function update_page($page, $content = 'New page content', $user = null, array $params = [], $trigger = false) {
        global $CFG, $DB;
        require_once("{$CFG->dirroot}/mod/wiki/locallib.php");
        $user = $this->normalize_user($user);

        $defaults = [
            'pageid' => $page,
            'content' => $content,
            'contentformat' => 'html',
            'timecreated' => time(),
            'userid' => $user->id,
        ];

        // Filter junk to return this as a created object instead of re-fetching it from the db.
        $params = array_merge($defaults, array_filter($params, function ($param) {
            return in_array($param, [
                'pageid',
                'content',
                'contentformat',
                'timecreated',
                'userid',
                'version',
            ]);
        }, ARRAY_FILTER_USE_KEY));

        // Get next version if override is not supplied.
        if (!isset($params['version'])) {
            $version = wiki_get_current_version($params['pageid']);
            $params['version'] = is_null($version) ? 0 : $version->version + 1;
        }

        $params['id'] = $DB->insert_record('wiki_versions', $params);

        $page = $DB->get_record('wiki_pages', ['id' => $params['pageid']], '*', MUST_EXIST);
        ['page' => $page] = wiki_refresh_cachedcontent($page);

        if ($trigger) {
            $wiki = wiki_get_wiki_from_pageid($page->id);
            $context = context_module::instance(get_coursemodule_from_instance('wiki', $wiki->id)->id);

            $event = page_updated::create([
                    'context' => $context,
                    'objectid' => $page->id,
                    'relateduserid' => $user->id,
                    'other' => ['newcontent' => $params['content']],
                ]);
            $event->add_record_snapshot('wiki', $wiki);
            $event->add_record_snapshot('wiki_pages', $page);
            $event->add_record_snapshot('wiki_versions', $params);
            $event->trigger();
        }

        return (object) $params;
    }

    /**
     * Create wiki page synonym
     *
     * @param int $page Wiki page id
     * @param string $synonym Synonym string
     * @param array $params Record object (if page and synonym supplied will overwrite the values)
     * @return object Page synonym object
     */
    public function create_page_synonym($page, $synonym = 'New page synonym', array $params = []) {
        global $DB;

        $page = intval($page);

        $defaults = [
            'pageid' => $page,
            'pagesynonym' => $synonym,
        ];

        // Filter this here to return this as a created object at the end, preventing unnecessary junk in it.
        $params = array_merge($defaults, array_filter($params, function ($option) {
            return in_array($option, [
                'pageid',
                'pagesynonym',
                'subwikiid',
            ]);
        }, ARRAY_FILTER_USE_KEY));

        if (empty($params['subwikiid'])) {
            // Need to get sub-wiki id.
            $page = intval($params['pageid']);
            $params['subwikiid'] = $DB->get_field_sql(
                "SELECT subwikiid
                      FROM {wiki_pages} 
                      WHERE id = {$page}", [], MUST_EXIST);
        }
        $params['id'] = $DB->insert_record('wiki_synonyms', $params);

        return (object) $params;
    }

    /**
     * Create wiki page lock
     *
     * @param int $page Wiki page id
     * @param \stdClass|int|null $user User object\id\null for current user
     * @param int|null $timestamp Timestamp or null for now
     * @param array $params Record object (if page and synonym supplied will overwrite the values)
     * @return object Page synonym object
     */
    public function lock_page($page, $user = null, $timestamp = null, $params = []) {
        global $DB;

        $timestamp = $timestamp ?: time();
        $user = $this->normalize_user($user);

        $defaults = [
            'pageid' => $page,
            'userid' => $user->id,
            'lockedat' => $timestamp
        ];
        
        $params = array_merge($defaults, array_filter($params, function($param) {
            return in_array($param, ['pageid', 'sectionname', 'userid', 'locekdat']);
        }, ARRAY_FILTER_USE_KEY));

        $params['id'] = $DB->insert_record('wiki_locks', $params);

        return (object) $params;
    }

    /**
     * Post wiki page comment.
     *
     * @param int $page Wiki page id
     * @param string $content Comment content
     * @param int|\stdClass|null $user User object or null to get the current user
     * @param array $params Record object (if page and content supplied will overwrite the values)
     * @return object Created comment object
     */
    public function post_comment($page, $content = 'New comment', $user = null, array $params = []) {
        global $DB;

        $user = $this->normalize_user($user);
        $page = intval($page);

        $defaults = [
            'component' => 'mod_wiki',
            'commentarea' => 'wiki_page',
            'itemid' => $page,
            'content' => $content,
            'userid' => $user->id,
            'format' => FORMAT_HTML,
            'timecreated' => time(),
        ];

        // Filter this here to return this as a created object at the end, preventing unnecessary junk in it.
        $params = array_merge($defaults, array_filter($params, function ($option) {
            return in_array($option, [
                'itemid',
                'content',
                'userid',
                'format',
                'timecreated',
                'contextid',
            ]);
        }, ARRAY_FILTER_USE_KEY));

        if (empty($params['contextid'])) {
            // Need to get context id.
            $level = intval(CONTEXT_MODULE);
            $page = intval($params['itemid']);
            $params['contextid'] = $DB->get_field_sql(
                "SELECT ctx.id
                  FROM {wiki_pages} pages
                  JOIN {wiki_subwikis} subwikis ON subwikis.id = pages.subwikiid
                  JOIN {wiki} wikis ON wikis.id = subwikis.wikiid
                  JOIN {modules} modules ON modules.name = 'wiki'
                  JOIN {course_modules} course_modules ON (course_modules.module = modules.id AND course_modules.instance = wikis.id)
                  JOIN {context} ctx ON (ctx.instanceid = course_modules.id AND ctx.contextlevel = {$level})
                  WHERE pages.id = {$page}", [], MUST_EXIST);
        }

        $params['id'] = $DB->insert_record('comments', $params);

        return (object) $params;
    }

    /**
     * Add a file to a sub-wiki.
     *
     * @param int $subwiki
     * @param string $filename Filename, default files: cute_kitten.jpg, cute_puppy.jpg, meaningful_text.txt
     * @param \stdClass|int|null $user User object\id\null for current user
     * @param array $params File object record (if filename or itemid specified $subwiki and $filename will be overwritten
     * @return stored_file
     */
    public function add_file($subwiki, $filename = 'meaningful_text.txt', $user = null, array $params = []) {
        global $DB, $CFG;
        $user = $this->normalize_user($user);

        $defaults = [
            'component' => 'mod_wiki',
            'filearea' => 'attachments',
            'itemid' => $subwiki,
            'userid' => $user->id,
            'filepath' => '/',
            'filename' => $filename,
        ];

        $params = array_merge($defaults, $params);

        // Check whether context has been supplied.
        if (empty($params['contextid'])) {
            $subwiki = intval($params['itemid']);
            $level = intval(CONTEXT_MODULE);
            $params['contextid'] = $DB->get_field_sql(
                "SELECT ctx.id
                  FROM {wiki_subwikis} subwikis
                  JOIN {wiki} wikis ON wikis.id = subwikis.wikiid
                  JOIN {modules} modules ON modules.name = 'wiki'
                  JOIN {course_modules} course_modules ON (course_modules.module = modules.id AND course_modules.instance = wikis.id)
                  JOIN {context} ctx ON (ctx.instanceid = course_modules.id AND ctx.contextlevel = {$level})
                  WHERE subwikis.id = {$subwiki}", [], MUST_EXIST);
        }

        if (!file_exists($filepath = "{$CFG->dirroot}/mod/wiki/tests/fixtures/uploads/{$params['filename']}")) {
            throw new coding_exception('Supplied file does not exist (default files are: cute_kitten.jpg, cute_puppy.jpg, meaningful_text.txt');
        }
        return get_file_storage()->create_file_from_pathname($params, $filepath);
    }

    /**
     * Normalize user attribute.
     *
     * @param null|int|\stdClass $user
     * @return stdClass User object that definitely has id field.
     */
    protected function normalize_user($user = null) {
        // Normalize user.
        if (is_null($user)) {
            global $USER;
            $user = $USER;
        } elseif (!is_object($user)) {
            $user = (object) ['id' => intval($user)];
        }

        if (intval($user->id) <= 0) {
            throw new coding_exception('Invalid user object\id supplied');
        }

        return $user;
    }
}
