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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package	block_community
 */

namespace block_community\userdata;

use totara_userdata\userdata\item;
use totara_userdata\userdata\export;
use totara_userdata\userdata\purge;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

class community extends item {

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
        $export->data = $DB->get_records('block_community', ['userid' => $user->id]);

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
     * Count number of community links for the specified user
     *
     * @param target_user $user
     * @param \context $context
     * @return int
     */
    public static function count(target_user $user, \context $context) {
        global $DB;
        return $DB->count_records('block_community', ['userid' => $user->id]);
    }
}
