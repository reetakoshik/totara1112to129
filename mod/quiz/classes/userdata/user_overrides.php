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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_quiz
 */

namespace mod_quiz\userdata;

use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * This item is responsible for purging and counting all user overrides (individual quiz settings for user).
 */
class user_overrides extends item {

    /**
     * Returns all contexts this item is compatible with, defaults to CONTEXT_SYSTEM.
     *
     * @return array
     */
    public static function get_compatible_context_levels() {
        return [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE, CONTEXT_MODULE];
    }

    /**
     * String used for human readable name of this item.
     *
     * @return array parameters of get_string($identifier, $component) to get full item name and optionally help.
     */
    public static function get_fullname_string() {
        return ['useroverrides', 'mod_quiz'];
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
     * Purge user data for this item.
     *
     * NOTE: Remember that context record does not exist for deleted users any more,
     *       it is also possible that we do not know the original user context id.
     *
     * @param target_user $user
     * @param \context $context restriction for purging e.g., system context for everything, course context for purging one course
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or status::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, \context $context) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/quiz/lib.php');

        // Load all quizs ids including the module ids
        // for the given contexts where the user has overrides in.
        $join = self::get_activities_join($context, 'quiz', 'q.id');
        $sql = "
            SELECT q.id, cm.id AS cmid, q.course
              FROM {quiz} q
              $join
             WHERE q.id IN (
                SELECT qo.quiz
                  FROM {quiz_overrides} qo
                 WHERE qo.userid = :userid
             )
        ";
        $params = ['userid' => $user->id];
        $quizs = $DB->get_records_sql($sql, $params);

        foreach ($quizs as $quiz) {
            $params = ['quiz' => $quiz->id, 'userid' => $user->id];
            $overrides = $DB->get_records('quiz_overrides', $params, 'id');
            foreach ($overrides as $override) {
                // This fires the event user_override_deleted.
                quiz_delete_override($quiz, $override->id);
            }
        }

        return self::RESULT_STATUS_SUCCESS;
    }


    /**
     * Can user data of this item data be exported from system?
     *
     * @return bool
     */
    public static function is_exportable() {
        // It is not personal data so we don't export it.
        return false;
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
     * Count user data for this item.
     *
     * @param target_user $user
     * @param \context $context restriction for counting i.e., system context for everything and course context for course data
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, \context $context) {
        global $DB;

        // Load all overrides for the given user.
        $join = self::get_activities_join($context, 'quiz', 'qo.quiz', 'q');
        $sql = "
            SELECT COUNT(qo.id)
              FROM {quiz_overrides} qo
              $join
             WHERE qo.userid = :userid
        ";
        $params = ['userid' => $user->id];
        return $DB->count_records_sql($sql, $params);
    }

}