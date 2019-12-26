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

use mod_facetoface\seminar;

defined('MOODLE_INTERNAL') || die();

/**
 * Class approval_admin_required
 */
class approval_admin_required extends condition {

    /**
     * Is condition passing
     * @return bool
     */
    public function pass() : bool {
        if ($this->signup->get_skipapproval()) {
            return true;
        }

        return $this->signup->get_seminar_event()->get_seminar()->get_approvaltype() == seminar::APPROVAL_ADMIN;
    }

    public static function get_description() : string {
        return get_string('state_approvaladminrequired_desc', 'mod_facetoface');
    }
}
