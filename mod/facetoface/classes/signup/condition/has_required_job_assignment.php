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
 * Class approval_required
 */
class has_required_job_assignment extends condition {

    /**
     * Is condition passing
     * @return bool
     */
    public function pass() : bool {
        $seminar = $this->signup->get_seminar_event()->get_seminar();
        $userid = $this->signup->get_userid();
        if(empty($userid)) {
            return false;
        }
        if ($seminar->get_forceselectjobassignment()
            && empty(\totara_job\job_assignment::get_all($userid, $seminar->is_approval_required()))) {
            return false;
        }
        return true;
    }

    public static function get_description() : string {
        return get_string('state_hasrequiredjobassignment_desc', 'mod_facetoface');
    }

    public function get_failure() : array {
        return ['has_required_job_assignment' => get_string('error:nojobassignmentselectedactivity', 'mod_facetoface')];
    }
}
