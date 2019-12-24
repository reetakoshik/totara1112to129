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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_feedback360
 */

namespace totara_feedback360\userdata;

use totara_feedback360\userdata\feedback360_helper as helper;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Feedback360 user assignments and related responses.
 */
class user_assignments extends item {
    /**
     * Returns sort order.
     *
     * @return int
     */
    public static function get_sortorder() {
        return 100;
    }

    /**
     * Can user data of this item be counted?
     *
     * @return bool
     */
    public static function is_countable() {
        return true;
    }

    /**
     * Can user data of this item data be exported from the system?
     *
     * @return bool
     */
    public static function is_exportable() {
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
     * Count user data for this item.
     *
     * @param target_user $user
     * @param \context $context
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, \context $context) {
        global $DB;

        return $DB->count_records('feedback360_user_assignment', ['userid' => $user->id]);
    }

    /**
     * Export user data from this item.
     *
     * @param target_user $user
     * @param \context $context
     * @return export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, \context $context) {
        global $DB;

        $export = new export();

        $sql = "SELECT fbua.*, fb.name as feedback360name, fb.status as feedback360status, fb.anonymous AS feedback360anonymous
                  FROM {feedback360_user_assignment} fbua
                  JOIN {feedback360} fb
                    ON fbua.feedback360id = fb.id
                 WHERE fbua.userid = :uid";
        $userassignments = $DB->get_records_sql($sql, ['uid' => $user->id]);

        foreach ($userassignments as $userassignment) {
            $respassignments = $DB->get_records('feedback360_resp_assignment', ['feedback360userassignmentid' => $userassignment->id]);

            $responses = [];
            foreach ($respassignments as $respassignment) {
                $responses[] = helper::export_resp_assignment($export, $userassignment, $respassignment, $userassignment->feedback360anonymous);
            }

            $userassignment->responses = $responses;
        }

        $export->data = $userassignments;
        return $export;
    }


    /**
     * Purge user data for this item.
     *
     * NOTE: Remember that context record does not exist for deleted users any more,
     *       it is also possible that we do not know the original user context id.
     *
     * Also worth noting that user_assignment and resp_assignment deletion is handled differently in
     * the feedback360 lib functions, these have seperated purge code to replicated that.
     *
     * @param target_user $user
     * @param \context $context
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, \context $context) {
        global $DB;

        $fs = get_file_storage();
        $systemcontext = \context_system::instance();
        $userassignments = $DB->get_records('feedback360_user_assignment', ['userid' => $user->id]);

        foreach ($userassignments as $userassignment) {
            $respassignments = $DB->get_records('feedback360_resp_assignment', ['feedback360userassignmentid' => $userassignment->id]);
            $questions = $DB->get_records('feedback360_quest_field', ['feedback360id' => $userassignment->feedback360id]); // Note: These arent deleted.

            foreach ($respassignments as $respassignment) {
                if (!empty($respassignment->feedback360emailassignmentid)) {
                    $DB->delete_records('feedback360_email_assignment', ['id' => $respassignment->feedback360emailassignmentid]);
                }

                $DB->delete_records('feedback360_scale_data', ['feedback360respassignmentid' => $respassignment->id]);

                // Clear out any files associated with answers to the resp_assignment.
                foreach ($questions as $question) {
                    $filearea = "quest_{$question->id}";
                    $fs->delete_area_files($systemcontext->id, 'totara_feedback360', $filearea, $respassignment->id);
                }

                // Now clear out the answers themselves.
                $anstable = "feedback360_quest_data_{$userassignment->feedback360id}";
                $DB->delete_records($anstable, ['feedback360respassignmentid' => $respassignment->id]);
            }

            // Delete all of the clean resp assignments.
            $DB->delete_records('feedback360_resp_assignment', ['feedback360userassignmentid' => $userassignment->id]);
        }

        // Finally delete the user assignments.
        $DB->delete_records('feedback360_user_assignment', ['userid' => $user->id]);

        return self::RESULT_STATUS_SUCCESS;
    }
}
