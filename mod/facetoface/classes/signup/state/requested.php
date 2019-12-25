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

use mod_facetoface\signup\condition\{approval_admin_not_required,
    approval_admin_required,
    approval_manager_required,
    approval_not_required,
    approval_role_required,
    booking_common,
    event_is_cancelled,
    event_is_not_cancelled,
    waitlist_common,
    waitlist_everyone_disabled};
use mod_facetoface\signup\restriction\{actor_has_role, actor_is_admin, actor_is_manager_or_admin};
use mod_facetoface\signup\transition;
use mod_facetoface\event\booking_requested;
use mod_facetoface\event\abstract_signup_event;

defined('MOODLE_INTERNAL') || die();

/**
 * This class is used in booking class and responsible for exact state
 */
class requested extends state implements interface_event {
    /**
     * Get conditions and validations of transitions from current state
     *
     * requested -- Waitlist basic conditions<br/>Approval not required anymore --> waitlisted
     * requested -- Booking basic conditions<br/>Approval not required anymore --> booked
     * requested -- Admin approval not required<br/>Manager approved<br/>Waitlist basic conditions --> waitlisted
     * requested -- Admin approval not required<br/>Manager approved<br/>Booking basic conditions --> booked
     * requested -- Admin approval required<br/>Has admin approval <br/> Event is not cancelled --> requestedadmin
     * requested -- Manager declined <br/> Event is not cancelled --> declined
     *
     * Event cancelled
     * requested -- Session is cancelled <br/> Event is not cancelled --> event_cancelled
     */
    final public function get_map() : array {
        return [
            // Approval no longer required.
            transition::to(new booked($this->signup))->with_conditions(
                booking_common::class,
                approval_not_required::class
            ),
            // Approval no longer required.
            transition::to(new waitlisted($this->signup))->with_conditions(
                waitlist_common::class,
                approval_not_required::class
            ),
            // The manager approves or declines the request.
            transition::to(new booked($this->signup))->with_conditions(
                booking_common::class,
                approval_manager_required::class,
                approval_admin_not_required::class
            )->with_restrictions(
                actor_is_manager_or_admin::class
            ),
            transition::to(new waitlisted($this->signup))->with_conditions(
                waitlist_common::class,
                approval_manager_required::class,
                approval_admin_not_required::class
            )->with_restrictions(
                actor_is_manager_or_admin::class
            ),
            transition::to(new declined($this->signup))->with_conditions(
                approval_manager_required::Class,
                event_is_not_cancelled::class
            )->with_restrictions(
                actor_is_manager_or_admin::class
            ),

            // A user with the specified role approves or declines the request.
            transition::to(new waitlisted($this->signup))->with_conditions(
                waitlist_common::class,
                approval_role_required::class,
                approval_admin_not_required::class
            )->with_restrictions(
                actor_has_role::class
            ),
            transition::to(new booked($this->signup))->with_conditions(
                booking_common::class,
                approval_role_required::class,
                approval_admin_not_required::class
            )->with_restrictions(
                actor_has_role::class
            ),
            transition::to(new declined($this->signup))->with_conditions(
                approval_role_required::class,
                event_is_not_cancelled::class
            )->with_restrictions(
                actor_has_role::class
            ),

            // Approval requires extra steps.
            transition::to(new requestedadmin($this->signup))->with_conditions(
                approval_admin_required::class,
                event_is_not_cancelled::class
            )->with_restrictions(
                actor_is_manager_or_admin::class
            ),

            // Admin can fast-forward approval or decline
            transition::to(new booked($this->signup))->with_conditions(
                booking_common::class,
                approval_admin_required::class
            )->with_restrictions(
                actor_is_admin::class
            ),
            transition::to(new waitlisted($this->signup))->with_conditions(
                waitlist_common::class,
                approval_admin_required::class
            )->with_restrictions(
                actor_is_admin::class
            ),
            transition::to(new declined($this->signup))->with_conditions(
                approval_admin_required::class
            )->with_restrictions(
                actor_is_admin::class
            ),
            // The seminar event is cancelled.
            transition::to(new event_cancelled($this->signup))->with_conditions(
                event_is_cancelled::class
            ),
            // The seminar event is cancelled.
            transition::to(new user_cancelled($this->signup)),
        ];
    }

    /**
     * Code of status as it is stored in DB
     * Numeric statuses are backward compatible except not_set which was not meant to be written into DB.
     * Statuses follow a logical order of operations i.e. none->requested->waitlisted->booked->completed from lower to higher,
     * which must be adhered to for some database queries to work. For example status > booked::get_code()
     */
    public static function get_code() : int {
        return 40;
    }

    /**
     * Message for user on entering the state
     * @return string
     */
    public function get_message(): string {
        return get_string('bookingcompleted_approvalrequired', 'mod_facetoface');
    }

    /**
     * Get action label for getting into state.
     * @return string
     */
    public function get_action_label(): string {
        return get_string('signupandrequest', 'mod_facetoface');
    }

    /**
     * Get event to fire when entering state
     *
     * @return abstract_signup_event
     */
    public function get_event() : abstract_signup_event {
        $cm = $this->signup->get_seminar_event()->get_seminar()->get_coursemodule();
        $context = \context_module::instance($cm->id);
        return booking_requested::create_from_signup($this->signup, $context);
    }

    /**
     * Get the requested status string.
     * @return string
     */
    public static function get_string() : string {
        return get_string('status_requested', 'mod_facetoface');
    }
}
