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
 * @author David Curry <david.curry@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\task;

use \mod_facetoface\signup;
use \mod_facetoface\signup_list;
use \mod_facetoface\signup_helper;
use \mod_facetoface\seminar_event;
use \mod_facetoface\seminar_event_list;
use \mod_facetoface\signup\state\{waitlisted, user_cancelled};


/**
 * Clean the waitlists for events that have already started
 * so that the waitlisted users can signup for or express interest
 * in other events.
 */
class waitlist_autoclean_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('waitlistautocleantask', 'mod_facetoface');
    }

    /**
     * Periodic cleanup of users on waitlist of events that have closed .
     */
    public function execute() {

        $seminarevents = seminar_event_list::pending_waitlist_clear();
        foreach ($seminarevents as $seminarevent) {

            $signups = signup_list::signups_by_statuscode_for_event($seminarevent->get_id(), waitlisted::get_code());
            foreach ($signups as $signup) {
                // First cancel the users waitlisted signup for the event.
                $signup->switch_state(user_cancelled::class);

                // Then remove the event from their calendar.
                \mod_facetoface\calendar::remove_seminar_event($seminarevent, 0, $signup->get_userid());

                // Finally trigger the waitlist_autoclean notification.
                \mod_facetoface\notice_sender::signup_waitlist_autoclean($signup);
            }

            // Now we've cancelled the waitlisted signups, update the attendees.
            if (!empty($signups)) {
                signup_helper::update_attendees($seminarevent);
            }
        }
    }
}
