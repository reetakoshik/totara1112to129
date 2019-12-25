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
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

class interest extends item {

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
     * Can user data of this item be exported from the system?
     *
     * @return bool
     */
    public static function is_exportable() {
        return true;
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
     * Is the given context level compatible with this item?
     *
     * @return array
     */
    public static function get_compatible_context_levels() {
        return [
            CONTEXT_SYSTEM,
            CONTEXT_COURSECAT,
            CONTEXT_COURSE,
            CONTEXT_MODULE
        ];
    }

    /**
     * Execute user data purging for this item.
     *
     * @param target_user $user
     * @param context $context restriction for purging e.g., system context for everything, course context for purging one course
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, context $context) {
        global $DB;

        $records = self::get_interests($user, $context);
        if (empty($records)) {
            // Nothing to purge.
            return self::RESULT_STATUS_SUCCESS;
        }

        $recordids = array_column($records, 'id');

        $DB->delete_records_list('facetoface_interest', 'id', $recordids);

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * Count user data for this item.
     *
     * @param target_user $user
     * @param context $context restriction for counting i.e., system context for everything and course context for course data
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, context $context) {
        return count(self::get_interests($user, $context));
    }

    /**
     * Export user data from this item.
     *
     * @param target_user $user
     * @param context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, context $context) {
        $export = new export();
        $export->data = self::get_interests($user, $context);
        return $export;
    }

    /**
     * Get interest records for the given user and context.
     *
     * @param target_user $user
     * @param context $context
     * @return array
     */
    private static function get_interests(target_user $user, context $context) {
        global $DB;

        $join = self::get_activities_join($context, 'facetoface', 'fi.facetoface');
        $sql = "SELECT fi.*
                  FROM {facetoface_interest} fi
                 $join
                 WHERE fi.userid = :userid
              ORDER BY fi.id";
        $interests = $DB->get_records_sql($sql, ['userid' => $user->id]);

        return $interests;
    }

    /**
     * Returns sort order.
     *
     * @return int
     */
    public static function get_sortorder() {
        return 17000;
    }
}
