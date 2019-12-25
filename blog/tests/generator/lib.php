<?php
/**
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package core_blog
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class core_blog_generator
 */
class core_blog_generator extends component_generator_base {

    /**
     * Creates a entry in the post table for a blog.
     *
     * @param null|array|stdClass $record
     * @param $attachment
     * @return stdClass
     */
    public function create_instance($record = null) {
        global $DB, $USER;
        $record = (object)(array)$record;

        if (empty($record->module)) {
            $record->module = 'blog';
        }
        if (empty($record->userid)) {
            throw new coding_exception('Module generator requires $record->userid.');
        }
        if (!isset($record->courseid)) {
            $record->courseid = 0;
        }
        if (!isset($record->groupid)) {
            $record->groupid = 0;
        }
        if (!isset($record->moduleid)) {
            $record->moduleid = 0;
        }
        if (!isset($record->coursemoduleid)) {
            $record->coursemoduleid = 0;
        }
        if (!isset($record->subject)) {
            $record->subject = 'This is test generated blog';
        }
        if (!isset($record->summay)) {
            $record->summary = 'This is test generated blog';
        }
        if (!isset($record->content)) {
            $record->content = '';
        }
        if (!isset($record->uniquehash)) {
            $record->uniquehash = '';
        }
        if (!isset($record->rating)) {
            $record->rating = 0;
        }
        if (!isset($record->format)) {
            $record->format = FORMAT_HTML;
        }
        if (!isset($record->summaryformat)) {
            $record->summaryformat = FORMAT_HTML;
        }
        if (!isset($record->attachment)) {
            $record->attachment = null;
        }
        if (!isset($record->publishstate)) {
            $record->publishstate = 'site';
        }
        if (!isset($record->lastmodified)) {
            $record->lastmodified = time();
        }
        if (!isset($record->created)) {
            $record->created = time();
        }
        if (!isset($record->usermodified)) {
            $record->usermodified = $USER->id;
        }

        $id = $DB->insert_record('post', $record);
        $record->id = $id;
        return $record;
    }
}