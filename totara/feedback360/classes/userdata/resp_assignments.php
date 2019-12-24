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
 * Feedback360 responses to other user's requests.
 */
class resp_assignments extends item {
    /**
     * Returns sort order.
     *
     * @return int
     */
    public static function get_sortorder() {
        return 200;
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

        return $DB->count_records('feedback360_resp_assignment', ['userid' => $user->id]);
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
        $respassignments = $DB->get_records('feedback360_resp_assignment', ['userid' => $user->id]);

        $responses = [];
        foreach ($respassignments as $respassignment) {
            $userassignment = $DB->get_record('feedback360_user_assignment', ['id' => $respassignment->feedback360userassignmentid]);
            $responses[] = helper::export_resp_assignment($export, $userassignment, $respassignment);
        }

        $export->data = $responses;
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
        $respassignments = $DB->get_records('feedback360_resp_assignment', ['userid' => $user->id]);
        foreach ($respassignments as $respassignment) {
            $userassignment = $DB->get_record('feedback360_user_assignment', ['id' => $respassignment->feedback360userassignmentid]);

            $email = '';
            if (!empty($respassignment->feedback360emailassignmentid)) {
                // Get the email field we need for the event, then delete the email assignment record.
                $email = $DB->get_field('feedback360_email_assignment', 'email', ['id' => $respassignment->feedback360emailassignmentid]);
                $DB->delete_records('feedback360_email_assignment', ['id' => $respassignment->feedback360emailassignmentid]);
            }

            // Delete any scale data for the resp_assignment.
            $DB->delete_records('feedback360_scale_data', ['feedback360respassignmentid' => $respassignment->id]);

            // Clear out any files associated with answers to the resp_assignment.
            $questions = $DB->get_records('feedback360_quest_field', ['feedback360id' => $userassignment->feedback360id]);
            foreach ($questions as $question) {
                $filearea = "quest_{$question->id}";
                $fs->delete_area_files($systemcontext->id, 'totara_feedback360', $filearea, $respassignment->id);
            }

            // Delete the answers to the resp_assignment.
            $questtable = 'feedback360_quest_data_' . $userassignment->feedback360id;
            $DB->delete_records($questtable, ['feedback360respassignmentid' => $respassignment->id]);

            // Finally delete the resp assignment itself and fire the event.
            $DB->delete_records('feedback360_resp_assignment', ['id' => $respassignment->id]);
            \totara_feedback360\event\request_deleted::create_from_instance($respassignment, $userassignment->userid, $email)->trigger();
        }

        return self::RESULT_STATUS_SUCCESS;
    }
}
