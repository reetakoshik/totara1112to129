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
 * Defines backup_feedback_activity_task class
 *
 * @package     mod_feedback
 * @category    backup
 * @copyright   2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/feedback/backup/moodle2/backup_feedback_stepslib.php');
require_once($CFG->dirroot . '/mod/feedback/backup/moodle2/backup_feedback_settingslib.php');

/**
 * Provides the steps to perform one complete backup of the Feedback instance
 */
class backup_feedback_activity_task extends backup_activity_task {

    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the feedback.xml file
     */
    protected function define_my_steps() {
        // feedback only has one structure step
        $this->add_step(new backup_feedback_activity_structure_step('feedback structure', 'feedback.xml'));
    }

    /**
     * Encodes URLs to the index.php and view.php scripts
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     * @param backup_task $task
     * @return string the content with the URLs encoded
     */
    static public function encode_content_links($content, backup_task $task = null) {

        if (!self::has_scripts_in_content($content, 'mod/feedback', ['index.php', 'view.php', 'analysis.php', 'show_entries.php'])) {
            // No scripts present in the content, simply continue.
            return $content;
        }
        if (empty($task)) {
            // No task has been provided, lets just encode everything, must be some old school backup code.
            $content = self::encode_content_link_basic_id($content, "/mod/feedback/index.php?id=", 'FEEDBACKINDEX');
            $content = self::encode_content_link_basic_id($content, "/mod/feedback/view.php?id=", 'FEEDBACKVIEWBYID');
            $content = self::encode_content_link_basic_id($content, "/mod/feedback/analysis.php?id=", 'FEEDBACKANALYSISBYID');
            $content = self::encode_content_link_basic_id($content, "/mod/feedback/show_entries.php?id=", 'FEEDBACKSHOWENTRIESBYID');
        } else {
            // OK we have a valid task, we can translate just those links belonging to content that is being backed up.
            $content = self::encode_content_link_basic_id($content, "/mod/feedback/index.php?id=", 'FEEDBACKINDEX', $task->get_courseid());
            foreach ($task->get_tasks_of_type_in_plan('backup_feedback_activity_task') as $task) {
                /** @var backup_feedback_activity_task $task */
                $content = self::encode_content_link_basic_id($content, "/mod/feedback/view.php?id=", 'FEEDBACKVIEWBYID', $task->get_moduleid());
                $content = self::encode_content_link_basic_id($content, "/mod/feedback/analysis.php?id=", 'FEEDBACKANALYSISBYID', $task->get_moduleid());
                $content = self::encode_content_link_basic_id($content, "/mod/feedback/show_entries.php?id=", 'FEEDBACKSHOWENTRIESBYID', $task->get_moduleid());
            }
        }

        return $content;
    }
}
