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

use mod_facetoface\signup\condition as condition;

defined('MOODLE_INTERNAL') || die();

/**
 * This class is used in booking class and responsible for exact state
 */
class user_cancelled extends state implements interface_event {

    final public function get_map() : array {
        // User cancelled has exactly the same steps forward as initial state (at least now).
        return (new not_set($this->signup))->get_map();
    }

    /**
     * Code of status as it is stored in DB
     * Numeric statuses are backward compatible except not_set which was not meant to be written into DB.
     * Statuses don't have to follow particular order (except must be unique of course)
     */
    public static function get_code() : int {
        return 10;
    }

    /**
     * Get event to fire when entering state
     * @return \mod_facetoface\event\abstract_signup_event
     */
    public function get_event() : \mod_facetoface\event\abstract_signup_event {
        $cm = $this->signup->get_seminar_event()->get_seminar()->get_coursemodule();
        $context = \context_module::instance($cm->id);
        return \mod_facetoface\event\booking_cancelled::create_from_signup($this->signup, $context);
    }

    /**
     * Get the user_cancelled status string.
     * @return string
     */
    public static function get_string() : string {
        return get_string('status_user_cancelled', 'mod_facetoface');
    }

    /**
     * Get action label for getting into state.
     * @return string
     */
    public function get_action_label(): string {
        $multisignup = new condition\multisignup_common($this->signup);

        // If user is not able to sign up to different session, and user already has another signup,
        // then this state should be treated as not_set state.
        if (!$multisignup->pass()) {
            return parent::get_action_label();
        }
        return get_string('cancelbooking', 'mod_facetoface');
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
        return get_string('statemessage_user_cancelled', 'mod_facetoface');
    }
}
