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
 * Class event_has_started
 */
class event_has_started extends condition {

    /**
     * Is condition passing
     * @return bool
     */
    public function pass() : bool {
        global $DB;

        $now = time();
        $sql = 'SELECT MIN(d.timestart)
                  FROM {facetoface_sessions_dates} d
                 WHERE d.sessionid = :sessid
              GROUP BY d.sessionid';
        $timestart = $DB->get_field_sql($sql, ['sessid' => $this->signup->get_sessionid()]);

        return $timestart < $now;
    }

    public static function get_description() : string {
        return get_string('state_eventhasstarted_desc', 'mod_facetoface');
    }

    public function get_failure() : array {
        return ['event_has_started' => get_string('state_eventhasstarted_fail', 'mod_facetoface')];
    }
}
