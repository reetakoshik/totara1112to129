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
 * Defines backup_glossary_activity_task class
 *
 * @package     mod_glossary
 * @category    backup
 * @copyright   2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/glossary/backup/moodle2/backup_glossary_stepslib.php');

/**
 * Provides the steps to perform one complete backup of the Glossary instance
 */
class backup_glossary_activity_task extends backup_activity_task {

    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the glossary.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new backup_glossary_activity_structure_step('glossary_structure', 'glossary.xml'));
    }

    /**
     * Encodes URLs to the index.php and view.php scripts
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     * @return string the content with the URLs encoded
     */
    static public function encode_content_links($content, backup_task $task = null) {
        global $CFG, $DB;

        if (!self::has_scripts_in_content($content, 'mod/glossary', ['index.php', 'view.php', 'showentry.php'])) {
            // No scripts present in the content, simply continue.
            return $content;
        }

        $showentrybase = preg_quote($CFG->wwwroot.'/mod/glossary/showentry.php?courseid=', '#');

        if (empty($task)) {
            $before = $content;
            // No task has been provided, lets just encode everything, must be some old school backup code.
            $content = self::encode_content_link_basic_id($content, "/mod/glossary/index.php?id=", 'GLOSSARYINDEX');
            $content = self::encode_content_link_basic_id($content, "/mod/glossary/view.php?id=", 'GLOSSARYVIEWBYID');
            $content = self::encode_content_link_basic_id($content, "/mod/glossary/view.php?g=", 'GLOSSARYVIEWBYG');
            $content = preg_replace("#{$showentrybase}(\d+)(&|&amp;)eid=(\d+)#", '$@GLOSSARYSHOWENTRY*$1*$3@$', $content);
        } else {
            // OK we have a valid task, we can translate just those links belonging to content that is being backed up.
            $courseid = $task->get_courseid();
            $activityids = array();
            $content = self::encode_content_link_basic_id($content, "/mod/glossary/index.php?id=", 'GLOSSARYINDEX', $courseid);
            foreach ($task->get_tasks_of_type_in_plan('backup_glossary_activity_task') as $task) {
                /** @var backup_glossary_activity_task $task */
                $content = self::encode_content_link_basic_id($content, "/mod/glossary/view.php?id=", 'GLOSSARYVIEWBYID', $task->get_moduleid());
                $content = self::encode_content_link_basic_id($content, "/mod/glossary/view.php?g=", 'GLOSSARYVIEWBYG', $task->get_activityid());
                $activityids[] = $task->get_activityid();
            }

            $search = "#{$showentrybase}{$courseid}(&|&amp;)eid=(?<eid>\d+(?!\d))#";
            if (!empty($activityids) && preg_match_all($search, $content, $matches)) {
                list($eidsin, $eidparams) = $DB->get_in_or_equal($matches[2], SQL_PARAMS_NAMED, 'eid');
                list($activityidsin, $activityparams) = $DB->get_in_or_equal($activityids, SQL_PARAMS_NAMED, 'act');
                $sql = 'SELECT ge.id
                              FROM {glossary_entries} ge
                             WHERE ge.id '.$eidsin.'
                               AND ge.glossaryid '.$activityidsin;
                $entries = $DB->get_records_sql($sql, array_merge($eidparams, $activityparams));
                foreach ($entries as $entry) {
                    $eid = $entry->id;
                    $search = "#{$showentrybase}{$courseid}(&|&amp;)eid={$eid}(?!\d)#";
                    $content = preg_replace($search, '$@GLOSSARYSHOWENTRY*'.$courseid.'*'.$eid.'@$', $content);
                }
            }
        }

        return $content;
    }
}
