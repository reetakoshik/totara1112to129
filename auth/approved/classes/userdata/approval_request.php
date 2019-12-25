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
use auth_approved\userdata\approval_request_snapshot;
use context;

defined('MOODLE_INTERNAL') || die();

final class approval_request extends item {

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

        $DB->delete_records('auth_approved_request', ['userid' => $user->id]);

        return approval_request_snapshot::execute_purge($user, $context);
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

        $sql = 'SELECT a.id, a.username, a.firstname, a.lastname, a.lastnamephonetic, a.firstnamephonetic,
        a.middlename, a.alternatename, a.email, a.city, a.country, a.lang, {pos}.fullname AS selectedpos, a.positionfreetext,
        ' . $DB->sql_concat_join("' '", totara_get_all_user_name_fields_join('{user}', null, true)) . 'AS manager_name, 
        a.managerfreetext, {org}.fullname AS selectedorg, a.organisationfreetext, a.profilefields, a.timecreated
        FROM {auth_approved_request} a
        LEFT JOIN {pos} ON {pos}.id = a.positionid
        LEFT JOIN {org} ON {org}.id = a.organisationid
        LEFT JOIN {user} ON {user}.id = a.managerjaid
        WHERE a.userid = :userid';
        $params = ['userid' => $user->id];

        $export = new \totara_userdata\userdata\export();
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

        return $DB->count_records('auth_approved_request', ['userid'=>$user->id]);
    }
}