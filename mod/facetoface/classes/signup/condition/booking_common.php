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

defined('MOODLE_INTERNAL') || die();

/**
 * Class booking_common
 */
class booking_common extends condition {

    /**
     * Is condition passing:
     * - waitlist everyone is disabled
     * - event has session
     * - event has capacity
     * - registration is available
     * - event not cancelled
     *
     * @return bool
     */
    public function pass() : bool {

        $waitlist = new waitlist_everyone_disabled($this->signup);
        $sessions = new event_has_session($this->signup);
        $capacity = new event_has_capacity($this->signup);
        $futureis = new event_not_in_the_past($this->signup);
        $register = new event_registration_is_available($this->signup);
        $nocancel = new event_is_not_cancelled($this->signup);

        return $waitlist->pass() && $sessions->pass() && $capacity->pass() && $futureis->pass() && $register->pass() && $nocancel->pass();
    }

    /**
     * Get English description of condition
     * Used for debug purpose only
     * @return mixed
     */
    public static function get_description() : string {
        return get_string('state_bookingcommon_desc', 'mod_facetoface');
    }

    /**
     * Return explanation why condition has not passed
     * Used for debug purposes only
     * @return array of strings
     */
    public function get_failure() : array {

        $reason = [];

        $waitlist = new waitlist_everyone_disabled($this->signup);
        if (!$waitlist->pass()) {
            $reason = array_merge($reason, $waitlist->get_failure());
        }

        $capacity = new event_has_capacity($this->signup);
        if (!$capacity->pass()) {
            $reason = array_merge($reason, $capacity->get_failure());
        }

        $futureis = new event_not_in_the_past($this->signup);
        if (!$futureis->pass()) {
            $reason = array_merge($reason, $futureis->get_failure());
        }

        $register = new event_registration_is_available($this->signup);
        if (!$register->pass()) {
            $reason = array_merge($reason, $register->get_failure());
        }

        $nocancel = new event_is_not_cancelled($this->signup);
        if (!$nocancel->pass()) {
            $reason = array_merge($reason, $nocancel->get_failure());
        }

        // Move event_has_session at bottom because "Event does not have session" is not very clear.
        $sessions = new event_has_session($this->signup);
        if (!$sessions->pass()) {
            $reason = array_merge($reason, $sessions->get_failure());
        }

        return $reason;
    }
}
