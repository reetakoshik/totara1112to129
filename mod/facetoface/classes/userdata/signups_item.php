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
use stdClass;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

abstract class signups_item extends item {

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
     * Get signup records.
     *
     * @param target_user $user
     * @param context $context
     * @return stdClass[]
     */
    protected static function get_signups(target_user $user, context $context): array {
        global $DB;
        $join = self::get_activities_join($context, 'facetoface', 'se.facetoface', 'f');
        $sql = "SELECT su.*
                  FROM {facetoface_signups} su
                  JOIN {facetoface_sessions} se ON su.sessionid = se.id
                 $join
                 WHERE su.userid = :userid
              ORDER BY su.id";
        $signups = $DB->get_records_sql($sql, ['userid' => $user->id]);

        return $signups;
    }
}
