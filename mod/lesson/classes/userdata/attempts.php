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
 * @author Valerii Kuznetsov <valerii.kuznetsov@@totaralearning.com>
 * @package mod_lesson
 */

namespace mod_lesson\userdata;

use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/lesson/locallib.php');

/**
 * This item takes care of exporting, counting and purging of lesson user attempts data.
 * User can export grades that they have received for each lesson being exported.
 */
final class attempts extends item {
    /**
     * Can user data of this item data be exported from the system?
     *
     * @return bool
     */
    public static function is_exportable() {
        // Grades are user private data, so should be possible to export.
        return true;
    }

    /**
     * Can user data of this item be somehow counted?
     *
     * @return bool
     */
    public static function is_countable() {
        return true;
    }

    /**
     * Can user data of this item data be purged from system?
     *
     * @param int $userstatus target_user::STATUS_ACTIVE, target_user::STATUS_DELETED or target_user::STATUS_SUSPENDED
     * @return bool
     */
    public static function is_purgeable(int $userstatus) {
        return true;
    }

    /**
     * Returns all contexts this item is compatible with, defaults to CONTEXT_SYSTEM.
     *
     * @return array
     */
    public static function get_compatible_context_levels() {
        return [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE, CONTEXT_MODULE];
    }

    /**
     * Execute user data purging for this item.
     *
     * NOTE: Remember that context record does not exist for deleted users any more,
     *       it is also possible that we do not know the original user context id.
     *
     * @param target_user $user
     * @param \context $context restriction for purging e.g., system context for everything, course context for purging one course
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or status::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, \context $context) {
        global $DB;

        $join = self::get_activities_join($context, 'lesson', 'l1.id');

        $select = "lessonid IN (SELECT l1.id FROM {lesson} l1 $join) AND userid = :userid";

        $transaction = null;
        if ($user->status == \totara_userdata\userdata\target_user::STATUS_ACTIVE) {
            $transaction = $DB->start_delegated_transaction();
        }
        $DB->delete_records_select('lesson_attempts', $select, ['userid' => $user->id]);
        $DB->delete_records_select('lesson_timer', $select, ['userid' => $user->id]);
        $DB->delete_records_select('lesson_branch', $select, ['userid' => $user->id]);

        // Remove overrides.
        $overrides = $DB->get_records_select("lesson_overrides", $select, ["userid" => $user->id], 'lessonid');
        $lessonrec = null;
        foreach ($overrides as $override) {
            if (!isset($lessonrec) || $lessonrec->id != $override->lessonid) {
                $lessonrec = $DB->get_record('lesson', array('id' => $override->lessonid), '*', IGNORE_MISSING);
            }

            $lesson = new \lesson($lessonrec);
            $lesson->delete_override($override->id);
        }

        $DB->delete_records_select('lesson_grades', $select, ['userid' => $user->id]);

        if ($transaction) {
            $transaction->allow_commit();
        }

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * Execute user data export for this item.
     *
     * @param target_user $user
     * @param \context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return \totara_userdata\userdata\export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, \context $context) {
        $export = new \totara_userdata\userdata\export();
        $export->data = [
            'answers' => static::export_answers($user, $context),
            'grades' => static::export_grades($user, $context)
        ];

        return $export;
    }

    /**
     * Export data related to user answers on pages
     * @param target_user $user
     * @param \context $context
     * @return array
     */
    private static function export_answers(target_user $user, \context $context) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/mod/lesson/locallib.php');

        $join = self::get_activities_join($context, 'lesson', 'latt.lessonid');

        $attemptssql = "
            SELECT latt.*
            FROM {lesson_attempts} latt
              $join
            WHERE latt.userid = :userid
            ORDER BY latt.id
        ";
        $attemptsrecs = $DB->get_recordset_sql($attemptssql, ['userid' => $user->id]);

        $answers = [];
        foreach ($attemptsrecs as $attemptsrec) {
            $lesson = \lesson::load($attemptsrec->lessonid);
            $manager = \lesson_page_type_manager::get($lesson);
            /**
             * @var \lesson_page $question
             */
            $question = $manager->load_page($attemptsrec->pageid, $lesson);
            $answers[] = [
                'time' => $attemptsrec->timeseen,
                'title' => $question->title,
                'retry' => $attemptsrec->retry,
                'useranswer' => $question->export($attemptsrec)
            ];
        }
        return $answers;
    }

    /**
     * Export data related to user grades for their attempts
     * @param target_user $user
     * @param \context $context
     * @return array
     */
    private static function export_grades(target_user $user, \context $context) {
        global $DB;
        $join = self::get_activities_join($context, 'lesson', 'lg.lessonid');

        $gradessql = "
            SELECT lg.id, l.name, lg.grade, lg.completed
            FROM {lesson_grades} lg
              $join
              LEFT JOIN {lesson} l ON (l.id = lg.lessonid)
            WHERE userid = :userid
        ";
        $gradesrecords = $DB->get_recordset_sql($gradessql, ['userid' => $user->id]);
        $grades = [];
        foreach ($gradesrecords as $gradesrecord) {
            $grades[] = [
                'lesson' => $gradesrecord->name,
                'completed' => $gradesrecord->completed,
                'grade' => $gradesrecord->grade
            ];
        }
        return array_values($grades);
    }

    /**
     * Count user data for this item.
     *
     * @param target_user $user
     * @param \context $context restriction for counting i.e., system context for everything and course context for course data
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, \context $context) {
        global $DB;
        $join = self::get_activities_join($context, 'lesson', 'latt.lessonid');

        $attemptssql = "
            SELECT COUNT(latt.id)
            FROM {lesson_attempts} latt
              $join
            WHERE latt.userid = :userid
        ";
        $countattempts = $DB->count_records_sql($attemptssql, ['userid' => $user->id]);

        return $countattempts + count(self::export_grades($user, $context));
    }
}