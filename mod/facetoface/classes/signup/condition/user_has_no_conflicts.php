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
 * @author  Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\signup\condition;

use mod_facetoface\signup;

defined('MOODLE_INTERNAL') || die();

/**
 * Class user_has_no_conflicts
 */
class user_has_no_conflicts extends condition {

    /**
     * Is the restriction met.
     * @return bool
     */
    public function pass() : bool {
        global $DB;

        // Don't bother checking if we are ignoring conflicts.
        if ($this->signup->get_ignoreconflicts()) {
            return true;
        }
        if(empty($this->signup->get_userid())) {
            return false;
        }

        $seminarevent = $this->signup->get_seminar_event();
        $userid = $this->signup->get_userid();
        $user = $DB->get_record('user', ['id' => $userid]);

        // Check if the user has any date conflicts with existing signups.
        $dates = facetoface_get_session_dates($seminarevent->get_id());
        $conflicts = facetoface_get_booking_conflicts($dates, [$user], '', []);

        return empty($conflicts);
    }

    public static function get_description() : string {
        return get_string('state_userhasnoconflicts_desc', 'mod_facetoface');
    }

    public function get_failure() : array {
        return ['user_has_no_conflicts' => get_string('state_userhasnoconflicts_fail', 'mod_facetoface')];
    }
}
