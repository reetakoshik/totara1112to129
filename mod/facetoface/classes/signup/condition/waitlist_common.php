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
 * Class waitlist_common
 */
class waitlist_common extends condition {

    /**
     * Is condition passing:
     * - (event doesn't have sessions AND the event is not cancelled)
     * OR
     * - (waitlisteveryone enabled for event AND event is not cancelled)
     * OR
     * -
     * (Waitlist enabled for event AND event has no capacity AND event is not cancelled)
     *
     * @return bool
     */
    public function pass() : bool {

        $nosession = new event_has_no_session($this->signup);
        $notcancelledcond = new event_is_not_cancelled($this->signup);
        $registrationopen = new event_registration_is_available($this->signup);
        $available = $notcancelledcond->pass() && $registrationopen->pass();
        if ($nosession->pass() && $available) {
            return true;
        }

        $waitlistall = new waitlist_everyone_enabled($this->signup);
        if ($waitlistall->pass() && $available) {
            return true;
        }

        $waitlist = new waitlist_enabled($this->signup);
        $nocapacity = new event_has_no_capacity($this->signup);
        if ($waitlist->pass() && $nocapacity->pass() && $available) {
            return true;
        }
        return false;
    }

    /**
     * Get English description of condition
     * Used for debug purpose only
     * @return mixed
     */
    public static function get_description() : string {
        return get_string('state_waitlistcommon_desc', 'mod_facetoface');
    }

    /**
     * Return explanation why condition has not passed
     * Used for debug purposes only
     * @return array of strings
     */
    public function get_failure() : array {
        $reason = [];

        $notcancelled = new event_is_not_cancelled($this->signup);
        if (!$notcancelled->pass()) {
            $reason = array_merge($reason, $notcancelled->get_failure());
        }

        $registrationopen = new event_registration_is_available($this->signup);
        if (!$registrationopen->pass() && $notcancelled->pass()) {
            $reason = array_merge($reason, $registrationopen->get_failure());
        }

        $nosession = new event_has_no_session($this->signup);
        if (!$nosession->pass() && $notcancelled->pass()) {
            $reason = array_merge($reason, $nosession->get_failure());
        }

        $waitlistall = new waitlist_everyone_enabled($this->signup);
        if (!$waitlistall->pass() && $notcancelled->pass()) {
            $reason = array_merge($reason, $waitlistall->get_failure());
        }

        $waitlist = new waitlist_enabled($this->signup);
        $nocapacity = new event_has_no_capacity($this->signup);
        if (!$waitlist->pass() && $nocapacity->pass() && $notcancelled->pass()) {
            $reason = array_merge($reason, $waitlist->get_failure());
        }

        if ($waitlist->pass() && !$nocapacity->pass() && $notcancelled->pass()) {
            $reason = array_merge($reason, $nocapacity->get_failure());
        }

        return $reason;
    }
}
