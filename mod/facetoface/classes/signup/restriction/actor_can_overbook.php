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

namespace mod_facetoface\signup\restriction;

defined('MOODLE_INTERNAL') || die();

/**
 * Class actor_can_overbook
 */
class actor_can_overbook extends restriction {
    public function pass() : bool {
        $seminarevent = $this->signup->get_seminar_event();

        $module = $seminarevent->get_seminar()->get_coursemodule();
        $context = \context_module::instance($module->id);
        if (has_capability('mod/facetoface:signupwaitlist', $context, $this->signup->get_actor())) {
            return true;
        }
        return false;
    }

    public static function get_description() : string {
        return get_string('state_actorcanoverbook_desc', 'mod_facetoface');
    }

    public function get_failure() : array {
        return ['actor_can_overbook' => get_string('state_actorcanoverbook_fail', 'mod_facetoface')];
    }
}
