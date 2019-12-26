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

use mod_facetoface\signup;
use mod_facetoface\signup_list;

defined('MOODLE_INTERNAL') || die();

/**
 * Class multisignup_common
 */
class multisignup_common extends condition {

    /**
     * Note: signups with user_cancelled or event_cancelled are not taken into consideration here.
     *
     * Is condition passing:
     * - User has no other signups
     * OR
     * - Multisignup is enabled
     * - Existing signups are in allowed states
     * - Number of signups does not exceed maximum
     *
     * @return bool
     */
    public function pass() : bool {
        global $DB;

        $signup = $this->signup;
        $seminarevent = $signup->get_seminar_event();
        $seminar = $seminarevent->get_seminar();
        $signups = \mod_facetoface\signup_list::user_active_signups_within_seminar($signup->get_userid(), $seminar->get_id());

        if ($signups->is_empty()) {
             // There are no existing signups for the activity
             return true; // no reason to check anything else
        } else {

            $enabled = $seminar->get_multiplesessions();
            if (empty($enabled)) {
                return false; // They're signed up already and multiple signups is disabled.
            }

            $allowedstates = $seminar->get_multisignup_states();
            if (!empty($allowedstates)) {
                foreach ($signups as $previous) {
                    $oldstate = $previous->get_state();

                    // Check all previous signups are in a valid state.
                    $code = $oldstate::get_code();
                    if (empty($allowedstates[$code]) || !$oldstate instanceof $allowedstates[$code]) {
                        return false; // Previous signup in an invalid state.
                    }
                }
            }

            // Check the user is within the allowable amount of signups.
            $maxsignups = $seminar->get_multisignup_maximum();
            if (!empty($maxsignups) && $signups->count() >= $maxsignups) {
                return false; // User has exceeded the allowable amount of signups.
            }

            return true;
        }
    }

    /**
     * Get English description of condition
     * Used for debug purpose only
     * @return mixed
     */
    public static function get_description() : string {
        return get_string('state_multisignup_enabled_desc', 'mod_facetoface');
    }

    /**
     * Return explanation why condition has not passed
     * Used for debug purposes only
     * @return array of strings
     */
    public function get_failure() : array {
        $failures = [];

        $signup = $this->signup;
        $seminarevent = $signup->get_seminar_event();
        $seminar = $seminarevent->get_seminar();
        $signups = \mod_facetoface\signup_list::user_active_signups_within_seminar($signup->get_userid(), $seminar->get_id());

        $enabled = $seminar->get_multiplesessions();
        if (empty($enabled)) {
            if (!empty($signups)) {
                $failures['multisignup_common'] = get_string('state_multisignup_enabled_fail', 'mod_facetoface');
            }
        } else {
            $allowedstates = $seminar->get_multisignup_states();
            if (!empty($allowedstates)) {
                foreach ($signups as $previous) {
                    $oldstate = $previous->get_state();

                    // Check all previous signups are in a valid state.
                    $code = $oldstate::get_code();
                    if (empty($allowedstates[$code]) || !$oldstate instanceof $allowedstates[$code]) {
                        $failures['multisignup_common'] = get_string('state_multisignup_enabled_fail', 'mod_facetoface'); // Make sure we have the initial string.
                        $failures['multisignup_restriction'] = get_string('state_multisignup_restriction_fail', 'mod_facetoface');
                    }
                }
            }

            // Check the user is within the allowable amount of signups.
            $maxsignups = $seminar->get_multisignup_maximum();
            if (!empty($maxsignups) && $signups->count() >= $maxsignups) {
                $failures['multisignup_common'] = get_string('state_multisignup_enabled_fail', 'mod_facetoface'); // Make sure we have the initial string.
                $failures['multisignup_limitation'] = get_string('state_multisignup_limitation_fail', 'mod_facetoface', $maxsignups);
            }

            // Join the two failures together.
            $failures = ['multiplesignup_common' => implode("", $failures)];
        }

        return $failures;
    }
}
