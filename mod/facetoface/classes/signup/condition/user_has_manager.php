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
 * Class user_has_manager
 */
class user_has_manager extends condition {

    /**
     * Is the restriction met.
     * @return bool
     */
    public function pass() : bool {
        if(empty($this->signup->get_userid())) {
            return false;
        }
        if ($this->signup->get_managerid()) {
            return true;
        }
        if (get_config(null, 'facetoface_managerselect') && !$this->signup->get_managerid()) {
            return false;
        }
        if (\totara_job\job_assignment::has_manager($this->signup->get_userid())) {
            return true;
        }
        return false;
    }

    public static function get_description() : string {
        return get_string('state_userhasmanager_desc', 'mod_facetoface');
    }

    public function get_failure() : array {
        return ['user_has_manager' => get_string('error:missingrequiredmanager', 'mod_facetoface')];
    }
}
