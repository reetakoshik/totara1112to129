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

namespace mod_facetoface\signup\state;

defined('MOODLE_INTERNAL') || die();

/**
 * This class is used in booking class and responsible for exact state
 */
class declined extends state {

    final public function get_map() : array {
        // User declined has exactly the same steps forward as initial state (at least now).
        return (new not_set($this->signup))->get_map();
    }

    /**
     * Code of status as it is stored in DB
     * Numeric statuses are backward compatible except not_set which was not meant to be written into DB.
     * Statuses don't have to follow particular order (except must be unique of course)
     */
    public static function get_code(): int {
        return 30;
    }

    /**
     * Get the declined status string.
     * @return string
     */
    public static function get_string() : string {
        return get_string('status_declined', 'mod_facetoface');
    }

    /**
     * Get action label for getting into state.
     * @return string
     */
    public function get_action_label(): string {
        return get_string('decline', 'mod_facetoface');
    }

    /**
     * Is current state means that signup either cancelled or declined.
     * @return bool
     */
    public function is_not_happening() : bool {
        return true;
    }

    /**
     * Message for user on entering the state
     * @return string
     */
    public function get_message(): string {
        return get_string('statemessage_declined', 'mod_facetoface');
    }
}
