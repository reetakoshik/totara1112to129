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
 * Defines backup_wiki_activity_task class
 *
 * @package     mod_wiki
 * @category    backup
 * @copyright   2010 Jordi Piguillem <pigui0@hotmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/wiki/backup/moodle2/backup_wiki_stepslib.php');
require_once($CFG->dirroot . '/mod/wiki/backup/moodle2/backup_wiki_settingslib.php');

/**
 * Provides all the settings and steps to perform one complete backup of the activity
 */
class backup_wiki_activity_task extends backup_activity_task {

    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the wiki.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new backup_wiki_activity_structure_step('wiki_structure', 'wiki.xml'));
    }

    /**
     * Encodes URLs to the index.php and view.php scripts
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     * @return string the content with the URLs encoded
     */
    static public function encode_content_links($content, backup_task $task = null) {
        global $CFG, $DB;

        if (!self::has_scripts_in_content($content, 'mod/wiki', ['index.php', 'view.php'])) {
            // No scripts present in the content, simply continue.
            return $content;
        }

        if (empty($task)) {
            // No task has been provided, lets just encode everything, must be some old school backup code.
            $content = self::encode_content_link_basic_id($content, "/mod/wiki/index.php?id=", 'WIKIINDEX');
            $content = self::encode_content_link_basic_id($content, "/mod/wiki/view.php?id=", 'WIKIVIEWBYID');
            $content = self::encode_content_link_basic_id($content, "/mod/wiki/view.php?pageid=", 'WIKIPAGEBYID');
        } else {
            // OK we have a valid task, we can translate just those links belonging to content that is being backed up.
            $content = self::encode_content_link_basic_id($content, "/mod/wiki/index.php?id=", 'WIKIINDEX', $task->get_courseid());
            $activityids = array();
            foreach ($task->get_tasks_of_type_in_plan('backup_wiki_activity_task') as $task) {
                /** @var backup_wiki_activity_task $task */
                $content = self::encode_content_link_basic_id($content, "/mod/wiki/view.php?id=", 'WIKIVIEWBYID', $task->get_moduleid());
                $activityids[] = $task->get_activityid();
            }

            $base = preg_quote($CFG->wwwroot.'/mod/wiki/view.php?pageid=', '#');
            $search = "#({$base})(?<pageid>\d+)#";
            if (preg_match_all($search, $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $pageid = $match['pageid'];
                    $sql = 'SELECT s.wikiid
                              FROM {wiki_subwikis} s
                              JOIN {wiki_pages} p ON p.subwikiid = s.id
                             WHERE p.id = :pageid';
                    $wikiid = $DB->get_field_sql($sql, ['pageid' => $pageid]);
                    if (in_array($wikiid, $activityids)) {
                        $content = self::encode_content_link_basic_id($content, "/mod/wiki/view.php?pageid=", 'WIKIPAGEBYID', $pageid);
                    }
                }

            }
        }

        return $content;
    }
}
