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
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package block_recent_activity
 */

namespace block_recent_activity\userdata;

use totara_userdata\userdata\item;
use totara_userdata\userdata\export;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * This item is responsible for purging, exporting and counting block recent activity
 */
class recent_activity extends item {

    /**
     * Can user data of this item data be purged from system?
     *
     * @param int $userstatus target_user::STATUS_ACTIVE, target_user::STATUS_DELETED or target_user::STATUS_SUSPENDED
     *
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
     * @param \context    $context restriction for purging e.g., system context for everything, course context for
     *                             purging one course
     *
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or status::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, \context $context) {
        global $DB;

        $DB->delete_records('block_recent_activity', ['userid' => $user->id]);

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * Can user data of this item data be exported from system?
     *
     * @return bool
     */
    public static function is_exportable() {
        return true;
    }

    /**
     * Export user data from this item.
     *
     * @param target_user $user
     * @param \context    $context restriction for exporting i.e., system context for everything and course context for
     *                             course export
     *
     * @return export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, \context $context) {
        global $DB;
        $export = new export();

        $export->data = $DB->get_records('block_recent_activity', ['userid' => $user->id]);

        return $export;
    }

    /**
     * Can user data of this item be somehow counted?
     * How much date is there?
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
     * @param \context    $context restriction for counting i.e., system context for everything and course context for
     *                             course data
     *
     * @return int is the count >= 0, negative number is error result self::RESULT_STATUS_ERROR or
     *             self::RESULT_STATUS_SKIPPED
     */
    protected static function count(target_user $user, \context $context) {
        global $DB;

        return $DB->count_records('block_recent_activity', ['userid' => $user->id]);
    }
}
