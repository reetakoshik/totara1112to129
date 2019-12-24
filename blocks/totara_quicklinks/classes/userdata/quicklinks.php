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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package	block_totara_quicklinks
 */

namespace block_totara_quicklinks\userdata;

use totara_userdata\userdata\item;
use totara_userdata\userdata\export;
use totara_userdata\userdata\purge;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

class quicklinks extends item {

    /**
     * Can the data of this item be exported
     *
     * @return bool
     */
	public static function is_exportable() {
		return true;
	}

    /**
     * Export the users data for this item
     *
     * @param target_user $user
     * @param \context $context
     * @return array Data to be exported
     */
    public static function export(target_user $user, \context $context) {
        global $DB;

        $export = new \totara_userdata\userdata\export();

        $sql = "SELECT bq.* FROM {block_quicklinks} bq
                JOIN
                    {block_instances} bi ON bq.block_instance_id = bi.id
                WHERE bi.parentcontextid = :contextid AND bq.userid = :userid
                ORDER BY bq.block_instance_id, bq.displaypos";

        $usercontext = \context_user::instance($user->id);

        $params = ['contextid' => $usercontext->id, 'userid' => $user->id];

        $quicklinkdata = $DB->get_records_sql($sql, $params);
        $exportdata = array();

        foreach ($quicklinkdata as $link) {
            $linkdata = new \stdClass();
            $linkdata->id = $link->id;
            $linkdata->title = $link->title;
            $linkdata->url = $link->url;
            $linkdata->sort = $link->displaypos;

            $exportdata[] = $linkdata;
        }

        $export->data = $exportdata;

        return $export;
    }

    /**
     * Can user data of this item be purged from the system
     *
     * @param int $userstatus
     * @return bool
     */
    public static function is_purgeable(int $userstatus) {
        return false;
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
     * Count number of quicklinks for the specified user
     *
     * @param target_user $user
     * @param \context $context
     * @return int
     */
    public static function count(target_user $user, \context $context) {
        global $DB;

        $sql = "SELECT COUNT(bq.id) FROM {block_quicklinks} bq
                JOIN
                    {block_instances} bi ON bq.block_instance_id = bi.id
                WHERE bi.parentcontextid = :contextid AND bq.userid = :userid";

        $usercontext = \context_user::instance($user->id, IGNORE_MISSING);
        if (!$usercontext) {
            // Context not found return zero.
            return 0;
        }

        $params = ['contextid' => $usercontext->id, 'userid' => $user->id];

        $count = $DB->count_records_sql($sql, $params);

        return $count;
    }
}
