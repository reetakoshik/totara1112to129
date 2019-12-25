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
 * Defines backup_forum_activity_task class
 *
 * @package   mod_forum
 * @category  backup
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/forum/backup/moodle2/backup_forum_stepslib.php');
require_once($CFG->dirroot . '/mod/forum/backup/moodle2/backup_forum_settingslib.php');

/**
 * Provides the steps to perform one complete backup of the Forum instance
 */
class backup_forum_activity_task extends backup_activity_task {

    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the forum.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new backup_forum_activity_structure_step('forum structure', 'forum.xml'));
    }

    /**
     * Encodes URLs to the index.php, view.php and discuss.php scripts
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     * @param backup_task $task The backup task if needed. Added in Totara 2.7.22, 2.9.14, 9.2, may not be set, may be null.
     * @return string the content with the URLs encoded
     */
    static public function encode_content_links($content, backup_task $task = null) {
        global $CFG, $DB;

        if (!self::has_scripts_in_content($content, 'mod/forum', ['index.php', 'view.php', 'discuss.php'])) {
            // No scripts present in the content, simply continue.
            return $content;
        }

        $base = preg_quote($CFG->wwwroot."/mod/forum/discuss.php?d=", '#');

        if (empty($task)) {
            // No task has been provided, lets just encode everything, must be some old school backup code.
            $content = self::encode_content_link_basic_id($content, "/mod/forum/index.php?id=", 'FORUMINDEX');
            $content = self::encode_content_link_basic_id($content, "/mod/forum/view.php?id=", 'FORUMVIEWBYID');
            $content = self::encode_content_link_basic_id($content, "/mod/forum/view.php?f=", 'FORUMVIEWBYF');
            $content = preg_replace("#{$base}(\d+)(&|&amp;)parent=(\d+)#", '$@FORUMDISCUSSIONVIEWPARENT*$1*$3@$', $content);
            $content = preg_replace("#{$base}(\d+)\#(\d+)#", '$@FORUMDISCUSSIONVIEWINSIDE*$1*$2@$', $content);
            $content = preg_replace("#{$base}(\d+)#", '$@FORUMDISCUSSIONVIEW*$1@$', $content);
        } else {
            $instances = array();
            // OK we have a valid task, we can translate just those links belonging to content that is being backed up.
            $content = self::encode_content_link_basic_id($content, "/mod/forum/index.php?id=", 'FORUMINDEX', $task->get_courseid());
            foreach ($task->get_tasks_of_type_in_plan('backup_forum_activity_task') as $task) {
                $instanceid = $task->get_activityid();
                $content = self::encode_content_link_basic_id($content, "/mod/forum/view.php?id=", 'FORUMVIEWBYID', $task->get_moduleid());
                $content = self::encode_content_link_basic_id($content, "/mod/forum/view.php?f=", 'FORUMVIEWBYF', $instanceid);
                $instances[$instanceid] = $instanceid;
            }

            // We want to translate just discussions relating to the forum instances included in the backup.
            // Rather than fetch all discussions and search for them we will identify links and check the discussion ids in them.
            // This will be cheaper as typically there will be no links to translate.
            $search = "#(?<path>{$base})(?<discussion>\d+)((?:\&amp;|\&)parent\=(?<parent>\d+)|\#(?<post>\d+))?#";
            if (!empty($instances) && preg_match_all($search, $content, $matches, PREG_SET_ORDER)) {
                // OK one or more links to discussion have been found.
                // First up work out the signatures, so that we can authenticate the discussions will be included in that
                // backup.
                $discussionids = array();
                $signatures = array();
                foreach ($matches as $match) {
                    $discussionid = $match['discussion'];
                    $parent = !empty($match['parent']) ? $match['parent'] : '';
                    $post = !empty($match['post']) ? $match['post'] : '';
                    $signatures[$discussionid.'-'.$parent.'-'.$post] = [
                        'discussionid' => $discussionid,
                        'parent' => $parent,
                        'post' => $post
                    ];
                    $discussionids[$match['discussion']] = $match['discussion'];
                }

                // Now check all discussions that need to be translated in a single query.
                list($discussioninsql, $discussionparams) = $DB->get_in_or_equal($discussionids, SQL_PARAMS_NAMED, 'dis');
                list($acitivityinsql, $activityparams) = $DB->get_in_or_equal($instances, SQL_PARAMS_NAMED, 'act');
                $sql = 'SELECT d.id, d.forum
                          FROM {forum_discussions} d
                         WHERE d.id '.$discussioninsql.'
                           AND d.forum ' . $acitivityinsql;
                $params = array_merge($activityparams, $discussionparams);
                $discussions = $DB->get_records_sql($sql, $params);

                // Finally process the signatures.
                foreach ($signatures as $match) {
                    $discussionid = $match['discussionid'];
                    if (empty($discussions[$discussionid])) {
                        // Its not a discussion in the backup content;
                        continue;
                    }
                    if (!empty($match['parent'])) {
                        $parent = $match['parent'];
                        $search = "#(?<path>{$base})({$discussionid}(?!\d))(?:\&amp;|\&)parent=({$parent}(?!\d))#";
                        $replace = '$@FORUMDISCUSSIONVIEWPARENT*' . $discussionid . '*' . $match['parent'] . '@$';
                    } else if (!empty($match['post'])) {
                        $post = $match['post'];
                        $search = "#(?<path>{$base})({$discussionid}(?!\d))\#({$post}(?!\d))#";
                        $replace = '$@FORUMDISCUSSIONVIEWINSIDE*' . $discussionid . '*' . $match['post'] . '@$';
                    } else {
                        $search = "#(?<path>{$base})({$discussionid}(?!\d))#";
                        $replace = '$@FORUMDISCUSSIONVIEW*' . $discussionid . '@$';
                    }
                    $content = preg_replace($search, $replace, $content);
                }
            }
        }

        return $content;
    }
}
