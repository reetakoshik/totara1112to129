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
 * Class event_not_in_the_past
 * Condition: Event is not in past or user who performs signup (for themselves or for others) have capability to override it
 */
class event_not_in_the_past extends condition {

    /**
     * Is condition passing
     * @return bool
     */
    public function pass() : bool {
        $seminarevent = $this->signup->get_seminar_event();
        if (!$seminarevent->is_started() || !$seminarevent->is_sessions()) {
            return true;
        }
        $cansignuppast = new \mod_facetoface\signup\restriction\actor_can_signuppastevents($this->signup);
        return $cansignuppast->pass($this->signup);
    }

    public static function get_description() : string {
        return get_string('state_eventnotinthepast_desc', 'mod_facetoface');
    }

    function get_failure() : array {
        $seminarevent = $this->signup->get_seminar_event();
        if ($seminarevent->is_progress()) {
            return ['event_not_in_the_past' => get_string('cannotsignupsessioninprogress', 'mod_facetoface')];
        }
        return ['event_not_in_the_past' => get_string('cannotsignupsessionover', 'mod_facetoface')];
    }
}
