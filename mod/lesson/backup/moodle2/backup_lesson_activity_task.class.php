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
 * This file contains the backup task for the lesson module
 *
 * @package     mod_lesson
 * @category    backup
 * @copyright   2010 Sam Hemelryk
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/lesson/backup/moodle2/backup_lesson_stepslib.php');

/**
 * Provides the steps to perform one complete backup of the Lesson instance
 *
 * @copyright  2010 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_lesson_activity_task extends backup_activity_task {

    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the lesson.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new backup_lesson_activity_structure_step('lesson structure', 'lesson.xml'));
    }

    /**
     * Encodes URLs to various Lesson scripts
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     * @return string the content with the URLs encoded
     */
    static public function encode_content_links($content, backup_task $task = null) {
        global $CFG, $DB;

        if (!self::has_scripts_in_content($content, 'mod/lesson', ['index.php', 'view.php', 'edit.php', 'essay.php', 'report.php', 'mediafile.php', 'editpage.php'])) {
            // No scripts present in the content, simply continue.
            return $content;
        }

        $viewbase = preg_quote($CFG->wwwroot.'/mod/lesson/view.php?id=','#');
        $editpagebase = preg_quote($CFG->wwwroot.'/mod/lesson/editpage.php?id=','#');

        if (empty($task)) {

            // No task has been provided, lets just encode everything, must be some old school backup code.
            $content = self::encode_content_link_basic_id($content, "/mod/lesson/index.php?id=", 'LESSONINDEX');
            $content = preg_replace("#{$viewbase}(\d+)(&|&amp;)pageid=(\d+)#", '$@LESSONVIEWPAGE*$1*$3@$', $content);
            $content = self::encode_content_link_basic_id($content, "/mod/lesson/view.php?id=", 'LESSONVIEWBYID');
            $content = preg_replace("#{$editpagebase}(\d+)(&|&amp;)pageid=(\d+)#", '$@LESSONEDITPAGE*$1*$3@$', $content);
            $content = self::encode_content_link_basic_id($content, "/mod/lesson/edit.php?id=", 'LESSONEDIT');
            $content = self::encode_content_link_basic_id($content, "/mod/lesson/essay.php?id=", 'LESSONESSAY');
            $content = self::encode_content_link_basic_id($content, "/mod/lesson/report.php?id=", 'LESSONREPORT');
            $content = self::encode_content_link_basic_id($content, "/mod/lesson/mediafile.php?id=", 'LESSONMEDIAFILE');

        } else {
            // OK we have a valid task, we can translate just those links belonging to content that is being backed up.

            $content = self::encode_content_link_basic_id($content, "/mod/lesson/index.php?id=", 'LESSONINDEX', $task->get_courseid());

            foreach ($task->get_tasks_of_type_in_plan('backup_lesson_activity_task') as $task) {
                /** @var backup_lesson_activity_task $task */
                $cmid = $task->get_moduleid();

                $content = self::encode_content_link_basic_id($content, "/mod/lesson/edit.php?id=", 'LESSONEDIT', $cmid);
                $content = self::encode_content_link_basic_id($content, "/mod/lesson/essay.php?id=", 'LESSONESSAY', $cmid);
                $content = self::encode_content_link_basic_id($content, "/mod/lesson/report.php?id=", 'LESSONREPORT', $cmid);
                $content = self::encode_content_link_basic_id($content, "/mod/lesson/mediafile.php?id=", 'LESSONMEDIAFILE', $cmid);

                $search = "#{$viewbase}{$cmid}(&|&amp;)pageid=([0-9]+)#";
                if (preg_match_all($search, $content, $matches)) {
                    list($insql, $params) = $DB->get_in_or_equal($matches[2], SQL_PARAMS_NAMED);
                    $sql = 'SELECT lp.id
                              FROM {lesson_pages} lp
                             WHERE lp.lessonid = :lessonid
                               AND lp.id '.$insql;
                    $params['lessonid'] = $task->get_activityid();
                    $pageids = $DB->get_records_sql($sql, $params);
                    foreach ($pageids as $page) {
                        $pageid = $page->id;
                        $search = "#{$viewbase}{$cmid}(&|&amp;)pageid={$pageid}(?!\d)#";
                        $content = preg_replace($search, '$@LESSONVIEWPAGE*'.$cmid.'*'.$pageid.'@$', $content);
                    }
                }

                $search = "#{$editpagebase}{$cmid}(&|&amp;)pageid=([0-9]+)#";
                if (preg_match_all($search, $content, $matches)) {
                    list($insql, $params) = $DB->get_in_or_equal($matches[2], SQL_PARAMS_NAMED);
                    $sql = 'SELECT lp.id
                              FROM {lesson_pages} lp
                             WHERE lp.lessonid = :lessonid
                               AND lp.id '.$insql;
                    $params['lessonid'] = $task->get_activityid();
                    $pageids = $DB->get_records_sql($sql, $params);
                    foreach ($pageids as $page) {
                        $pageid = $page->id;
                        $search = "#{$editpagebase}{$cmid}(&|&amp;)pageid={$pageid}(?!\d)#";
                        $content = preg_replace($search, '$@LESSONEDITPAGE*'.$cmid.'*'.$pageid.'@$', $content);
                    }
                }

                // This must be last as we must encode all view.php links that include a page arg first.
                $content = self::encode_content_link_basic_id($content, "/mod/lesson/view.php?id=", 'LESSONVIEWBYID', $cmid);
            }
        }

        // Return the now encoded content
        return $content;
    }
}
