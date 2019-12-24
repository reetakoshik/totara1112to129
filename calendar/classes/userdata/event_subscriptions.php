<?php
/**
 * This file is part of Totara LMS
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package core_calendar
 */

namespace core_calendar\userdata;

use cache_helper;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

require_once($CFG->dirroot . "/calendar/lib.php");

class event_subscriptions extends item {

    /**
     * Put the item in the user category as there isn't a calender category.
     *
     * @return string
     */
    public static function get_main_component() {
        return 'core_user';
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
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, \context $context) {
        global $DB;
        $subscriptions = $DB->get_records('event_subscriptions', [
            'userid' => $user->id,
            'eventtype' => 'user'
        ]);
        if (empty($subscriptions)) {
            return self::RESULT_STATUS_SUCCESS;
        }
        if ($user->status != target_user::STATUS_DELETED) {
            foreach ($subscriptions as $subscription) {
                calendar_delete_subscription($subscription);
            }
        } else {
            $ids = array_keys($subscriptions);
            list($insql, $params) = $DB->get_in_or_equal($ids);
            $DB->delete_records_select('event', "subscriptionid $insql", $params);
            // Dont need to delete files as they are not supported by subscriptions.
            $DB->delete_records('event_subscriptions', [
                'userid' => $user->id,
                'eventtype' => 'user'
            ]);
            cache_helper::invalidate_by_definition('core', 'calendar_subscriptions', [], $ids);

        }
        return self::RESULT_STATUS_SUCCESS;
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
        return $DB->count_records('event_subscriptions', [
            'userid' => $user->id,
            'eventtype' => 'user'
        ]);
    }
}