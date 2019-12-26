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

namespace mod_facetoface\signup\restriction;

defined('MOODLE_INTERNAL') || die();

/**
 * Class actor_has_role
 */
class actor_has_role extends restriction {
    public function pass() : bool {
        global $DB;

        $sql = 'SELECT 1
                  FROM {facetoface_session_roles} fsr
                  JOIN {facetoface_sessions} fs
                    ON fsr.sessionid = fs.id
                  JOIN {facetoface} f
                    ON fs.facetoface = f.id
                 WHERE f.approvalrole IS NOT NULL
                   AND fsr.roleid = f.approvalrole
                   AND fsr.userid = :uid
                   AND fs.id = :sid';
        return $DB->record_exists_sql($sql, ['uid' => $this->signup->get_actorid(), 'sid' => $this->signup->get_sessionid()]);
    }

    public static function get_description() : string {
        return get_string('state_actorhasrole_desc', 'mod_facetoface');
    }

    public function get_failure() : array {
        return [];
    }
}
