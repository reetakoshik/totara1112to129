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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package mod_survey
 */

namespace mod_survey\userdata;

use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;
use mod_survey\userdata\answers;
use mod_survey\userdata\analysis;

/**
 * This item is responsible for exporting, deleting and purging analysis text about the user from the survey activity.
 */
class answers_analysis extends item {

    /**
     * Returns all contexts this item is compatible with, defaults to CONTEXT_SYSTEM.
     *
     * @return array
     */
    public static function get_compatible_context_levels() {
        return [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE, CONTEXT_MODULE];
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
     * Execute user data purging for this item.
     *
     * NOTE: Remember that context record does not exist for deleted users any more,
     *       it is also possible that we do not know the original user context id.
     *
     * @param target_user $user
     * @param \context $context restriction for purging e.g., system context for everything, course context for purging one course
     * @return int result self::RESULT_STATUS_SUCCESS
     */
    protected static function purge(target_user $user, \context $context) {
        global $DB;

        $join = self::get_activities_join($context, 'survey', 'sa.survey');

        $transaction = null;
        if ($user->status == \totara_userdata\userdata\target_user::STATUS_ACTIVE) {
            $transaction = $DB->start_delegated_transaction();
        }

        // Delete analysis.
        $sql = "SELECT sa.id
                  FROM {survey_analysis} sa
                 $join
                 WHERE sa.userid = :userid";
        $analysisrecords = $DB->get_fieldset_sql($sql, ['userid' => $user->id]);
        if ($analysisrecords) {
            list($select, $params) = $DB->get_in_or_equal($analysisrecords, SQL_PARAMS_NAMED);
            $DB->delete_records_select('survey_analysis', ' id ' . $select, $params);
        }

        // Delete answers.
        $sql = "SELECT sa.id
                  FROM {survey_answers} sa
                 $join
                 WHERE sa.userid = :userid";
        $answerrecords = $DB->get_fieldset_sql($sql, ['userid' => $user->id]);
        if ($answerrecords) {
            list($select, $params) = $DB->get_in_or_equal($answerrecords, SQL_PARAMS_NAMED);
            $DB->delete_records_select('survey_answers', ' id ' . $select, $params);
        }

        if ($transaction) {
            $transaction->allow_commit();
        }

        return item::RESULT_STATUS_SUCCESS;
    }

    /**
     * Can user data of this item data be exported from the system?
     *
     * @return bool
     */
    public static function is_exportable() {
        return false;
    }

    /**
     * Can user data of this item be somehow counted?
     * How much data is there?
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
        return answers::count($user, $context) + analysis::count($user, $context);
    }

}