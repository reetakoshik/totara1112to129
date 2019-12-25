<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_job
 */

// Not namespaced, because that prevents the event manager from using it.

defined('MOODLE_INTERNAL') || die();

/**
 * Class job_event_handler.
 *
 * @package totara_job
 */
class job_event_handler {

    /**
     * Handler function called when a user_deleted event is triggered.
     *
     * @param \core\event\user_deleted $event The user object for the deleted user.
     */
    public static function user_deleted(\core\event\user_deleted $event) {
        $userid = $event->objectid;

        // Remove all job assignments belonging to the user.

        $jas = \totara_job\job_assignment::get_all($userid);
        $jas = array_reverse($jas); // Reverse it, so that there's not lots of sort order messing around.
        foreach ($jas as $ja) {
            \totara_job\job_assignment::delete($ja); // Will tidy up staff associations (manager, temp manager).
        }

        // Remove the user as appraiser in all job assignments.
        \totara_job\job_assignment::update_to_empty_by_criteria('appraiserid', $userid);
    }
}
