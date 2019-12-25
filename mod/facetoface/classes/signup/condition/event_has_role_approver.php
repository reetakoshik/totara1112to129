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

namespace mod_facetoface\signup\condition;

defined('MOODLE_INTERNAL') || die();

/**
 * Class event_has_capacity
 */
class event_has_role_approver extends condition {

    /**
     * Is condition passing
     * @return bool
     */
    public function pass() : bool {
        global $DB;

        $seminar = $this->signup->get_seminar_event()->get_seminar();

        $sql = 'SELECT 1
                  FROM {facetoface_session_roles} fsr
                 WHERE fsr.roleid = :approvalrole
                   AND fsr.sessionid = :sid';
        return $DB->record_exists_sql($sql, ['approvalrole' => $seminar->get_approvalrole(), 'sid' => $this->signup->get_sessionid()]);

        return false;
    }

    public static function get_description() : string {
        return get_string('state_eventhasroleapprover_desc', 'mod_facetoface');
    }

    public function get_failure() : array {
        return ['event_has_role_approver' => get_string('state_eventhasroleapprover_fail', 'mod_facetoface')];
    }
}
