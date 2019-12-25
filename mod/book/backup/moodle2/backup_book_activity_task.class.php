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
 * Description of book backup task
 *
 * @package    mod_book
 * @copyright  2010-2011 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/mod/book/backup/moodle2/backup_book_stepslib.php');    // Because it exists (must)
require_once($CFG->dirroot.'/mod/book/backup/moodle2/backup_book_settingslib.php'); // Because it exists (optional)

class backup_book_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have
     *
     * @return void
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     *
     * @return void
     */
    protected function define_my_steps() {
        // book only has one structure step
        $this->add_step(new backup_book_activity_structure_step('book_structure', 'book.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     *
     * @param string $content
     * @return string encoded content
     */
    static public function encode_content_links($content, backup_task $task = null) {
        global $CFG;

        if (!self::has_scripts_in_content($content, 'mod/book', ['index.php', 'view.php'])) {
            // No scripts present in the content, simply continue.
            return $content;
        }

        $base = preg_quote($CFG->wwwroot.'/mod/book/view.php?', "/");

        if (empty($task)) {
            // No task has been provided, lets just encode everything, must be some old school backup code.
            $content = self::encode_content_link_basic_id($content, "/mod/book/index.php?id=", 'BOOKINDEX');
            $content = preg_replace("/({$base}id=)([0-9]+)(&|&amp;)chapterid=([0-9]+)/", '$@BOOKVIEWBYIDCH*$2*$4@$', $content);
            $content = self::encode_content_link_basic_id($content, "/mod/book/view.php?id=", 'BOOKVIEWBYID');
            $content = preg_replace("/({$base}b=)([0-9]+)(&|&amp;)chapterid=([0-9]+)/", '$@BOOKVIEWBYBCH*$2*$4@$', $content);
            $content = self::encode_content_link_basic_id($content, "/mod/book/view.php?b=", 'BOOKVIEWBYB');
        } else {
            // OK we have a valid task, we can translate just those links belonging to content that is being backed up.
            $content = self::encode_content_link_basic_id($content, "/mod/book/index.php?id=", 'BOOKINDEX', $task->get_courseid());
            $modulesids = array();
            $activityids = array();
            foreach ($task->get_tasks_of_type_in_plan('backup_book_activity_task') as $task) {
                /** @var backup_book_activity_task $task */
                $moduleid = $task->get_moduleid();
                $activityid = $task->get_activityid();
                $modulesids[$moduleid] = $activityid;
                $activityids[$activityid] = $moduleid;
            }

            // Find all cmid + chapter links, and process them.
            if (preg_match_all("/({$base}id=)(?<cmid>\d+)(&|&amp;)chapterid=(?<chapterid>\d+)/", $content, $matches, PREG_SET_ORDER)) {
                // There are chapter links - yay...
                foreach ($matches as $match) {
                    $cmid = $match['cmid'];
                    if (!isset($modulesids[$cmid])) {
                        continue;
                    }
                    $activityid = $modulesids[$cmid];
                    $chapterid = $match['chapterid'];
                    // We don't validate the chapter belongs to the book here, if you have a link that is incorrect it
                    // wouldn't work anyway.
                    $content = preg_replace("/({$base}id=)({$cmid}(?!\d))(&|&amp;)chapterid=({$chapterid}(?!\d))/", '$@BOOKVIEWBYIDCH*$2*$4@$', $content);
                }
            }

            // Find all activity + chapter links, and process them.
            if (preg_match_all("/({$base}b=)(?<activityid>\d+)(&|&amp;)chapterid=(?<chapterid>\d+)/", $content, $matches, PREG_SET_ORDER)) {
                // There are chapter links - yay...
                foreach ($matches as $match) {
                    $activityid = $match['activityid'];
                    if (!isset($activityids[$activityid])) {
                        continue;
                    }
                    $chapterid = $match['chapterid'];
                    // We don't validate the chapter belongs to the book here, if you have a link that is incorrect it
                    // wouldn't work anyway.
                    $content = preg_replace("/({$base}b=)({$activityid}(?!\d))(&|&amp;)chapterid=({$chapterid}(?!\d))/", '$@BOOKVIEWBYBCH*$2*$4@$', $content);
                }
            }
            foreach (array_keys($modulesids) as $id) {
                $content = self::encode_content_link_basic_id($content, "/mod/book/view.php?id=", 'BOOKVIEWBYID', $id);
            }
            foreach (array_keys($activityids) as $id) {
                $content = self::encode_content_link_basic_id($content, "/mod/book/view.php?b=", 'BOOKVIEWBYB', $id);
            }
        }

        return $content;
    }
}
