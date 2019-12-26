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
 * Class actor_is_admin
 */
class actor_is_admin extends restriction {

    public function pass() : bool {
        // System administrators are able to approve seminar requests.
        if (in_array($this->signup->get_actorid(), array_keys(get_admins()))) {
            return true;
        }

        $sysapprovers = get_users_from_config(get_config(null, 'facetoface_adminapprovers'), 'mod/facetoface:approveanyrequest');
        foreach ($sysapprovers as $sysapprover) {
            if ($sysapprover->id == $this->signup->get_actorid()) {
                return true;
            }
        }

        return in_array($this->signup->get_actorid(), $this->signup->get_seminar_event()->get_seminar()->get_approvaladmins_list());
    }

    public static function get_description() : string {
        return get_string('state_actorisadmin_desc', 'mod_facetoface');
    }

    public function get_failure() : array {
        return ['actor_is_admin' => get_string('state_actorisadmin_fail', 'mod_facetoface')];
    }
}
