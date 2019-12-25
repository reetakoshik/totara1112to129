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

use mod_facetoface\signup\condition\{approval_admin_required, approval_manager_required, approval_not_required, booking_common,
    event_is_cancelled, event_is_not_cancelled, waitlist_common, waitlist_everyone_disabled};
use mod_facetoface\signup\transition;
use mod_facetoface\signup\restriction\actor_is_admin;

defined('MOODLE_INTERNAL') || die();

/**
 * This class is used in booking class and responsible for exact state
 */
class requestedadmin extends state {
    /**
     * Get conditions and validations of transitions from current state
     *
     * requestedadmin -- Waitlist basic conditions<br/>Approval not required anymore --> waitlisted
     * requestedadmin -- Booking basic conditions<br/>Approval not required anymore --> booked
     * requestedadmin -. Waitlist basic conditions<br/> <b>Has admin approval</b> .-> waitlisted
     * requestedadmin -- Booking basic conditions<br/>Has admin approval--> booked
     * requestedadmin -- Admin declined --> declined
     * requestedadmin -- Session is cancelled --> event_cancelled
     */
    final public function get_map() : array {
        return [
            // Approval no longer required.
            transition::to(new waitlisted($this->signup))->with_conditions(
                waitlist_common::class,
                approval_not_required::class
            ),
            transition::to(new booked($this->signup))->with_conditions(
                booking_common::class,
                approval_not_required::class
            ),
            // Approval level lowered to manager only (so admin approval is not needed anymore)
            transition::to(new waitlisted($this->signup))->with_conditions(
                waitlist_common::class,
                approval_manager_required::class
            )->with_restrictions(
                actor_is_admin::class
            ),
            transition::to(new booked($this->signup))->with_conditions(
                booking_common::class,
                waitlist_everyone_disabled::class,
                approval_manager_required::class
            ),
            // Admin approver approves or declines the request.
            transition::to(new waitlisted($this->signup))->with_conditions(
                waitlist_common::class,
                approval_admin_required::class
            )->with_restrictions(
                actor_is_admin::class
            ),
            transition::to(new booked($this->signup))->with_conditions(
                waitlist_everyone_disabled::class,
                booking_common::class,
                approval_admin_required::class
            )->with_restrictions(
                actor_is_admin::class
            ),
            transition::to(new declined($this->signup))->with_conditions(
                approval_admin_required::class,
                event_is_not_cancelled::class
            )->with_restrictions(
                actor_is_admin::class
            ),
            // The seminar event is cancelled.
            transition::to(new event_cancelled($this->signup))->with_conditions(
                event_is_cancelled::class
            )
        ];
    }

    /**
     * Code of status as it is stored in DB
     * Numeric statuses are backward compatible except not_set which was not meant to be written into DB.
     * Statuses don't have to follow particular order (except must be unique of course)
     */
    public static function get_code() : int {
        return 45;
    }

    /**
     * Message for user on entering the state
     * @return string
     */
    public function get_message() : string {
        return get_string('bookingcompleted_approvalrequired', 'facetoface');
    }

    /**
     * Get action label for getting into state.
     * @return string
     */
    public function get_action_label(): string {
        return get_string('signupandrequest', 'mod_facetoface');
    }

    /**
     * Get the requestedadmin status string.
     * @return string
     */
    public static function get_string() : string {
        return get_string('status_requestedadmin', 'mod_facetoface');
    }
}
