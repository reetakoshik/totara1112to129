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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\event;

use mod_facetoface\seminar_event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when users are waitlisted on seminar
 *
 * @property-read array $other {
 * Extra information about the event.
 * - sessionid Seminar Event ID.
 * }
 */
class booking_waitlisted extends abstract_signup_event {

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventbookingwaitlisted', 'mod_facetoface');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "User with id {$this->userid} has been added to waitlist of Seminar Event with the id {$this->other['sessionid']}.";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        $params = array('s' => $this->other['sessionid']);
        return new \moodle_url('/mod/facetoface/attendees/waitlist.php', $params);
    }
}
