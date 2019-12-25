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
 * Defines backup_data_activity_task
 *
 * @package     mod_data
 * @category    backup
 * @copyright   2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/data/backup/moodle2/backup_data_stepslib.php');

/**
 * Provides the steps to perform one complete backup of the Database instance
 */
class backup_data_activity_task extends backup_activity_task {

    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the data.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new backup_data_activity_structure_step('data_structure', 'data.xml'));
    }

    /**
     * Encodes URLs to the index.php and view.php scripts
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     * @return string the content with the URLs encoded
     */
    static public function encode_content_links($content, backup_task $task = null) {
        global $CFG, $DB;

        if (!self::has_scripts_in_content($content, 'mod/data', ['index.php', 'view.php'])) {
            // No scripts present in the content, simply continue.
            return $content;
        }

        $base = preg_quote($CFG->wwwroot.'/mod/data/view.php?d=', '#');

        if (empty($task)) {

            // No task has been provided, lets just encode everything, must be some old school backup code.
            $content = self::encode_content_link_basic_id($content, "/mod/data/index.php?id=", 'DATAINDEX');
            $content = self::encode_content_link_basic_id($content, "/mod/data/view.php?id=", 'DATAVIEWBYID');
            $content = preg_replace("#{$base}([0-9]+)(&|&amp;)rid=([0-9]+)#", '$@DATAVIEWRECORD*$1*$3@$', $content);
            $content = self::encode_content_link_basic_id($content, "/mod/data/view.php?d=", 'DATAVIEWBYD');

        } else {
            // OK we have a valid task, we can translate just those links belonging to content that is being backed up.

            $content = self::encode_content_link_basic_id($content, "/mod/data/index.php?id=", 'DATAINDEX', $task->get_courseid());

            foreach ($task->get_tasks_of_type_in_plan('backup_data_activity_task') as $task) {
                /** @var backup_data_activity_task $task */
                $cmid = $task->get_moduleid();
                $activityid = $task->get_activityid();

                $search = "#{$base}{$activityid}(&|&amp;)rid=(?<rid>[0-9]+)#";
                if (preg_match_all($search, $content, $matches)) {
                    list($insql, $params) = $DB->get_in_or_equal($matches['rid'], SQL_PARAMS_NAMED);
                    $sql = 'SELECT dr.id
                              FROM {data_records} dr
                             WHERE dr.dataid = :dataid
                               AND dr.id '.$insql;
                    $params['dataid'] = $activityid;
                    $entries = $DB->get_records_sql($sql, $params);
                    foreach ($entries as $entry) {
                        $entryid = $entry->id;
                        $search = "#{$base}{$activityid}(&|&amp;)rid={$entryid}(?!\d)#";
                        $content = preg_replace($search, '$@DATAVIEWRECORD*'.$activityid.'*'.$entryid.'@$', $content);
                    }
                }

                // This must be last as we must encode all view.php links that include a record arg first.
                $content = self::encode_content_link_basic_id($content, "/mod/data/view.php?id=", 'DATAVIEWBYID', $cmid);
                $content = self::encode_content_link_basic_id($content, "/mod/data/view.php?d=", 'DATAVIEWBYD', $activityid);
            }
        }

        // Return the now encoded content
        return $content;
    }
}
