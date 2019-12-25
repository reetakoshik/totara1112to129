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

use mod_facetoface\event\booking_booked;
use mod_facetoface\signup\condition\{event_allows_cancellation, event_is_cancelled, event_in_the_future, event_has_started,
    event_is_not_cancelled, waitlist_common};
use mod_facetoface\signup\restriction\actor_can_removeattendees;
use mod_facetoface\signup\restriction\actor_can_signuppastevents;
use mod_facetoface\signup\transition;
use mod_facetoface\event\abstract_signup_event;

defined('MOODLE_INTERNAL') || die();

/**
 * This class is used in booking class and responsible for exact state
 */
class booked extends state implements interface_event {
    /**
     * Get conditions and validations of transitions from current state
     *
     * booked -- Event session in the past <br/> Event is not cancelled --> no_show
     * booked -- Event session in the past <br/> Event is not cancelled --> partially_attended
     * booked -- Event session in the past <br/> Event is not cancelled --> fully_attended
     * booked -- Attendee request/Session in future <br/> Event is not cancelled --> user_cancelled
     * booked -- Event is cancelled --> event_cancelled
     */
    final public function get_map() : array {
        return [
            transition::to(new no_show($this->signup))->with_conditions(
                event_is_not_cancelled::class,
                event_has_started::class
            ),
            transition::to(new partially_attended($this->signup))->with_conditions(
                event_is_not_cancelled::class,
                event_has_started::class
            ),
            transition::to(new fully_attended($this->signup))->with_conditions(
                event_is_not_cancelled::class,
                event_has_started::class
            ),
            transition::to(new user_cancelled($this->signup))->with_conditions(
                event_is_not_cancelled::class,
                event_allows_cancellation::class,
                event_in_the_future::class
            ),
            // Users with "signuppastevents" capability can remove users from non cancelled events that allow cancellations.
            transition::to(new user_cancelled($this->signup))->with_conditions(
                event_is_not_cancelled::class,
                event_allows_cancellation::class
            )->with_restrictions(
                actor_can_signuppastevents::class
            ),
            // Users with "removeattendees" capability can remove users from non cancelled events in future.
            transition::to(new user_cancelled($this->signup))->with_conditions(
                event_is_not_cancelled::class,
                event_in_the_future::class
            )->with_restrictions(
                actor_can_removeattendees::class
            ),
            // Users with both "removeattendees" and "signuppastevents" capabilities  can remove users
            // from non cancelled events.
            transition::to(new user_cancelled($this->signup))->with_conditions(
                event_is_not_cancelled::class
            )->with_restrictions(
                actor_can_removeattendees::class,
                actor_can_signuppastevents::class
            ),
            transition::to(new event_cancelled($this->signup))->with_conditions(
                event_is_cancelled::class
            ),
            transition::to(new waitlisted($this->signup))->with_conditions(
                waitlist_common::class
            )
        ];
    }

    /**
     * Code of status as it is stored in DB
     * Numeric statuses are backward compatible except not_set which was not meant to be written into DB.
     * Statuses don't have to follow particular order (except must be unique of course)
     */
    public static function get_code() : int {
        return 70;
    }

    /**
     * Message for user on entering the state
     * @return string
     */
    public function get_message() : string {
        return get_string('bookingcompleted', 'mod_facetoface');
    }

    /**
     * Get action label for getting into state.
     * @return string
     */
    public function get_action_label(): string {
        return get_string('signup', 'mod_facetoface');
    }

    /**
     * Get event to fire when entering state
     *
     * @return abstract_signup_event
     */
    public function get_event() : abstract_signup_event {
        $cm = $this->signup->get_seminar_event()->get_seminar()->get_coursemodule();
        $context = \context_module::instance($cm->id);
        return booking_booked::create_from_signup($this->signup, $context);
    }

    /**
     * Get the booked status string.
     * @return string
     */
    public static function get_string() : string {
        return get_string('status_booked', 'mod_facetoface');
    }
}
