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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\userdata;

use context;
use context_system;
use Exception;
use totara_userdata\userdata\export;
use totara_userdata\userdata\target_user;
use \mod_facetoface\seminar_event;
use \mod_facetoface\signup;
use \mod_facetoface\signup_helper;

defined('MOODLE_INTERNAL') || die();

/**
 * This item covers Face to face signups (session attendance).
 */
class signups extends signups_item {

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
     * @param target_user $user
     * @param context $context restriction for purging e.g., system context for everything, course context for purging one course
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, context $context) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/facetoface/lib.php');

        $signups = self::get_signups($user, $context);
        if (empty($signups)) {
            // Nothing to purge.
            return self::RESULT_STATUS_SUCCESS;
        }

        // Try to cancel signups before deleting, if not already cancelled.
        $signupids = [];
        foreach ($signups as $signupdata) {
            $signupids[] = $signupdata->id;
            $seminarevent = new seminar_event($signupdata->sessionid);
            $signup = signup::create($user->id, $seminarevent);

            if (signup_helper::can_user_cancel($signup)) {
                signup_helper::user_cancel($signup, get_string('userdatapurgedcancel', 'facetoface'));
            }
        }

        $transaction = null;
        if ($user->status == target_user::STATUS_ACTIVE) {
            $transaction = $DB->start_delegated_transaction();
        }

        foreach ($signups as $signup) {
            // Notifications records. These are basically historic data, not the actual notification contents.
            // The notification contents are sent using the message system and are not deleted here (see purging in the message component).
            $DB->delete_records('facetoface_notification_sent', ['sessionid' => $signup->sessionid, 'userid' => $user->id]);
            $DB->delete_records('facetoface_notification_hist', ['sessionid' => $signup->sessionid, 'userid' => $user->id]);
        }

        // Remove files.
        self::purge_files_for_signupids($signupids);

        foreach ($signups as $signup) {
            $instance = new \mod_facetoface\signup($signup->id);
            $instance->delete();
        }

        if ($transaction) {
            $transaction->allow_commit();
        }

        // Not calling facetoface_update_grades() here because existing code leaves the grades
        // untouched even when deleting facetoface records, e.g. facetoface_delete_session().

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * Remove all the files associated with signup or cancellation customfields for the given signups.
     *
     * @param array $signupids
     */
    private static function purge_files_for_signupids(array $signupids) {
        global $DB;

        list($sqlin, $paramin) = $DB->get_in_or_equal($signupids);

        $sqldata = "SELECT id FROM {facetoface_signup_info_data} WHERE facetofacesignupid ";
        $cfsignup = $DB->get_records_sql($sqldata . $sqlin, $paramin);

        $sqldata = "SELECT id FROM {facetoface_cancellation_info_data} WHERE facetofacecancellationid ";
        $cfcancellation = $DB->get_records_sql($sqldata . $sqlin, $paramin);

        $fs = get_file_storage();

        foreach (array_keys($cfsignup) as $itemid) {
            // Files generated by file custom fields.
            $fs->delete_area_files(context_system::instance()->id, 'totara_customfield', 'facetofacesignup_filemgr', $itemid);
            // Files generated by textarea custom fields.
            $fs->delete_area_files(context_system::instance()->id, 'totara_customfield', 'facetofacesignup', $itemid);
        }
        foreach (array_keys($cfcancellation) as $itemid) {
            // Files generated by file custom fields.
            $fs->delete_area_files(context_system::instance()->id, 'totara_customfield', 'facetofacecancellation_filemgr', $itemid);
            // Files generated by textarea custom fields.
            $fs->delete_area_files(context_system::instance()->id, 'totara_customfield', 'facetofacecancellation', $itemid);
        }
    }

    /**
     * Execute user data export for this item.
     *
     * Only data from the signup table is exported here. Signup and cancellation custom fields are exportable in a separate item.
     *
     * @param target_user $user
     * @param context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, context $context) {
        $export = new export();

        $export->data = self::get_signups($user, $context);

        return $export;
    }

    /**
     * Count user data for this item.
     *
     * @param target_user $user
     * @param \context $context restriction for counting i.e., system context for everything and course context for course data
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, context $context) {
        return count(self::get_signups($user, $context));
    }

    /**
     * Returns sort order.
     *
     * @return int
     */
    public static function get_sortorder() {
        return 10000;
    }
}
