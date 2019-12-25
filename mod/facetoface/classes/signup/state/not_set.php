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

use mod_facetoface\signup\condition\{approval_manager_required, approval_not_required, approval_role_required,
    booking_common, has_required_job_assignment, has_signup_capability, is_reservation, no_other_signups,
    event_has_role_approver, user_can_select_manager, user_has_manager, user_has_no_conflicts, user_is_enrolable,
    waitlist_common, waitlist_everyone_disabled, multisignup_common};
use mod_facetoface\signup\transition;

defined('MOODLE_INTERNAL') || die();

/**
 * This class is used in booking class and responsible for exact state
 */
class not_set extends state {

    final public function get_map() : array {
        return [
            // Straight-forward booking
            transition::to(new booked($this->signup))->with_conditions(
                multisignup_common::class,
                has_signup_capability::class,
                has_required_job_assignment::class,
                no_other_signups::class,
                booking_common::class,
                approval_not_required::class,
                user_is_enrolable::class,
                waitlist_everyone_disabled::class,
                user_has_no_conflicts::class
            ),
            // Straight-forward wait list
            transition::to(new waitlisted($this->signup))->with_conditions(
                multisignup_common::class,
                has_signup_capability::class,
                has_required_job_assignment::class,
                no_other_signups::class,
                waitlist_common::class,
                approval_not_required::class,
                user_is_enrolable::class,
                user_has_no_conflicts::class
            ),
            // Request approval: manager and user has manager
            transition::to(new requested($this->signup))->with_conditions(
                multisignup_common::class,
                approval_manager_required::class,
                has_required_job_assignment::class,
                user_has_manager::class,
                user_is_enrolable::class
            ),
            // Request approval: manager and user can select manager
            transition::to(new requested($this->signup))->with_conditions(
                multisignup_common::class,
                approval_manager_required::class,
                has_required_job_assignment::class,
                user_can_select_manager::class,
                user_is_enrolable::class
            ),
            // Request approval: role
            transition::to(new requested($this->signup))->with_conditions(
                multisignup_common::class,
                has_signup_capability::class,
                has_required_job_assignment::class,
                no_other_signups::class,
                approval_role_required::class,
                event_has_role_approver::class,
                user_is_enrolable::class
            ),

            // Reservations
            transition::to(new booked($this->signup))->with_conditions(
                is_reservation::class,
                booking_common::class,
                waitlist_everyone_disabled::class
            ),
            transition::to(new waitlisted($this->signup))->with_conditions(
                is_reservation::class,
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
        return 0;
    }

    /**
     * Message for user on entering the state
     * @return string
     */
    public function get_message() : string {
        return get_string('status_not_set', 'mod_facetoface');
    }

    /**
     * Get the not_set status string.
     * @return string
     */
    public static function get_string() : string {
        return get_string('status_not_set', 'mod_facetoface');
    }
}
