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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\userdata;

use totara_userdata\userdata\export;
use totara_userdata\userdata\target_user;

/**
 * This item takes care of exporting, counting and purging of personal data stored when user exports to portfolios
 */
class portfolios extends \totara_userdata\userdata\item {

    /**
     * Compatible with system context only.
     *
     * @return array
     */
    public static function get_compatible_context_levels() {
        return [CONTEXT_SYSTEM];
    }

    /**
     * This item allows counting.
     *
     * @return bool
     */
    public static function is_countable() {
        return true;
    }

    /**
     * This item allows exporting.
     *
     * @return bool
     */
    public static function is_exportable() {
        return true;
    }

    /**
     * This item allows purging regardless of user status.
     *
     * @param int $userstatus
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

        $DB->delete_records('portfolio_instance_user', ['userid' => $user->id]);
        $DB->delete_records('portfolio_log', ['userid' => $user->id]);
        $DB->delete_records('portfolio_tempdata', ['userid' => $user->id]);

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * Export user data from this item.
     *
     * @param target_user $user
     * @param \context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return \totara_userdata\userdata\export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, \context $context) {
        global $DB;

        $export = new export();

        $instanceusersql =
            "SELECT iu.*, i.name AS instancename
               FROM {portfolio_instance_user} iu
               JOIN {portfolio_instance} i
                 ON iu.instance = i.id
                WHERE iu.userid = :userid";
        $export->data['instances'] = $DB->get_records_sql($instanceusersql, ['userid' => $user->id]);

        $logsql =
            "SELECT l.*, i.name AS instancename
               FROM {portfolio_log} l
               JOIN {portfolio_instance} i
                 ON (l.portfolio = i.id)
                WHERE l.userid = :userid";
        $export->data['log'] = $DB->get_records_sql($logsql, ['userid' => $user->id]);

        return $export;
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

        $sql =
            "SELECT count(i.id)
               FROM {portfolio_instance_user} i
                WHERE i.userid = :userid";
        $params = ['userid' => $user->id];
        $cntinstance = $DB->count_records_sql($sql, $params);

        $sql =
            "SELECT count(l.id)
               FROM {portfolio_log} l
                WHERE l.userid = :userid";
        $params = ['userid' => $user->id];
        $cntlog = $DB->count_records_sql($sql, $params);

        return $cntinstance + $cntlog;
    }
}