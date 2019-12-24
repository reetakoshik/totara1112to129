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
 * @author Carl Anderson <carl.anderson@totaralearning.com>
 * @package auth_approved
 */

namespace auth_approved\userdata;

use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;
use context;

defined('MOODLE_INTERNAL') || die();

final class approval_request_snapshot extends item {

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
     * Purge related data
     *
     * @param target_user $user
     * @param context $context
     * @return int
     */
    protected static function purge(target_user $user, \context $context) {
        global $DB;

        $records = $DB->get_records('auth_approved_request_snapshots', ['userid' => $user->id]);

        foreach ($records as $record) {
            $DB->delete_records('auth_approved_request_snapshots', ['requestid' => $record->requestid]);
        }

        // This one looks like overkill that can happen only if there bug in the system leading to creating inconsistent snapshots.
        $DB->delete_records('auth_approved_request_snapshots', ['userid' => $user->id]);

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
     * Export user-related data.
     *
     * @param target_user $user
     * @param context $context
     * @return int|export
     */
    protected static function export(target_user $user, \context $context) {
        global $DB;

        $export = new \totara_userdata\userdata\export();
        $export->data = [];

        //Have to have unique first column, so we fetch id and remove later
        $sql = 'SELECT a.id, a.username, a.firstname, a.lastname, a.lastnamephonetic, a.firstnamephonetic,
            a.middlename, a.alternatename, a.email, a.city, a.country, a.lang, {pos}.fullname AS selectedpos, a.positionfreetext,
            ' . $DB->sql_concat_join("' '", totara_get_all_user_name_fields_join('{user}', null, true)) . 'AS manager_name, 
            a.managerfreetext, {org}.fullname AS selectedorg, a.organisationfreetext, a.profilefields, a.timecreated
            FROM {auth_approved_request} aar
            LEFT JOIN {auth_approved_request_snapshots} a ON aar.id = a.requestid
            LEFT JOIN {pos} ON {pos}.id = a.positionid
            LEFT JOIN {org} ON {org}.id = a.organisationid
            LEFT JOIN {user} ON {user}.id = a.managerjaid
            WHERE aar.userid = :userid
            ORDER BY a.timesnapshot';

        $params = ["userid" => $user->id];
        $export->data = $DB->get_records_sql($sql, $params);

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

        $sql = 'SELECT COUNT(*) FROM {auth_approved_request} aar 
        LEFT JOIN {auth_approved_request_snapshots} a 
        ON aar.id = a.requestid WHERE aar.userid = :userid';
        return $DB->count_records_sql($sql, ['userid'=>$user->id]);
    }
}