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
 * @author  David Curry <david.curry@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\signup\condition;

defined('MOODLE_INTERNAL') || die();

/**
 * Class event_registration_is_available
 */
class event_registration_is_available extends condition {

    /**
     * Is condition passing
     * @return bool
     */
    public function pass() : bool {

        // Can user add attendees outside Sign-up registration period.
        $cansignup = new \mod_facetoface\signup\restriction\actor_can_surpasssignupperiod($this->signup);
        if ($cansignup->pass()) {
            return true;
        }

        $now = time();
        $timestart = $this->signup->get_seminar_event()->get_registrationtimestart();
        $timefinish = $this->signup->get_seminar_event()->get_registrationtimefinish();
        $start = empty($timestart) || $now > $timestart;
        $finish = empty($timefinish) || $now < $timefinish;

        return $start && $finish;
    }

    public static function get_description() : string {
        return get_string('state_eventregistrationisavailable_desc', 'mod_facetoface');
    }

    public function get_failure() : array {
        $now = time();
        $timestart = $this->signup->get_seminar_event()->get_registrationtimestart();
        $timefinish = $this->signup->get_seminar_event()->get_registrationtimefinish();
        $failure = [];

        if (!empty($timestart) && $timestart > $now) {
            $datetimetz = new \stdClass();
            $datetimetz->date = userdate($timestart, get_string('strftimedate', 'langconfig'));
            $datetimetz->time = userdate($timestart,  get_string('strftimetime', 'langconfig'));
            $datetimetz->timezone = \core_date::get_user_timezone();
            $failure['event_registration_is_available'] = get_string('signupregistrationnotyetopen', 'facetoface', $datetimetz);

            $failure['event_registration_is_available_start'] = get_string('state_eventregistrationisavailable_failstart', 'mod_facetoface');
        }

        if (!empty($timefinish) && $timefinish < $now) {
            $datetimetz = new \stdClass();
            $datetimetz->date = userdate($timefinish, get_string('strftimedate', 'langconfig'));
            $datetimetz->time = userdate($timefinish,  get_string('strftimetime', 'langconfig'));
            $datetimetz->timezone = \core_date::get_user_timezone();
            $failure['event_registration_is_available_finish'] = get_string('signupregistrationclosed', 'facetoface', $datetimetz);
        }

        return $failure;
    }
}
