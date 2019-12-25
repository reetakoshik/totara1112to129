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
 * Class event_allows_cancellation
 */
class event_allows_cancellation extends condition {

    /**
     * Is condition passing
     * @return bool
     */
    public function pass() : bool {

        $seminarevent = $this->signup->get_seminar_event();
        $allowed = $seminarevent->get_allowcancellations();

        if ($allowed == $seminarevent::ALLOW_CANCELLATION_ANY_TIME) {
            return true;
        } else if ($allowed == $seminarevent::ALLOW_CANCELLATION_CUT_OFF) {
            $now = time();
            $cancellationcutoff = $seminarevent->get_cancellationcutoff();
            $minstart = $seminarevent->get_mintimestart();

            // We are still before the cancellation cutoff, so we can cancel.
            if ($now < ($minstart - $cancellationcutoff)) {
                return true;
            }
        }

        return false;
    }

    public static function get_description() : string {
        return get_string('state_eventhascapacity_desc', 'mod_facetoface');
    }
}
