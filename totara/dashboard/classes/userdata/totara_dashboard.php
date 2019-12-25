<?php
/*
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
 * @author Andrew McGhie <andrew.mcghie@totaralms.com>
 * @package totara_dashboard
 */

namespace totara_dashboard\userdata;

use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

require_once($CFG->dirroot . '/totara/dashboard/lib.php');

/**
 * Class for representing the user data sorted for a totara_dashboard
 *
 * The data is purgeable, exportable and countable.
 * The purge will delete all a users dashboards including the block contained.
 * The export will only export the names and ids of the users custom dashboards and not the blocks.
 *
 * @package totara_dashboard\userdata
 */
class totara_dashboard extends item {

    /**
     * Gets the dashboards for the users
     * Only gets the name and id as thats all the data that is needed.
     *
     * @param $userid
     * @return array
     */
    private static function get_dashboards($userid) {
        global $DB;
        $sql = "SELECT td.id, td.name
                  FROM {totara_dashboard_user} as tdu
                  JOIN {totara_dashboard} as td
                    ON tdu.dashboardid = td.id
                 WHERE tdu.userid = :userid";
        $params = ['userid' => $userid];
        return $DB->get_records_sql($sql, $params);
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
        $dashboards = self::get_dashboards($user->id);
        foreach ($dashboards as $dashboarddata) {
            $dashboard = new \totara_dashboard($dashboarddata->id);
            $dashboard->user_reset($user->id);
        }
        return self::RESULT_STATUS_SUCCESS;
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
     * Export user data from this item.
     *
     * @param target_user $user
     * @param \context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, \context $context) {
        $export = new export();
        $data = self::get_dashboards($user->id);
        $export->data = $data;
        return $export;
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
        return $DB->count_records('totara_dashboard_user', ['userid' => $user->id]);
    }
}