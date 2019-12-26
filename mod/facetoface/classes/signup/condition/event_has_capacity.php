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
class event_has_capacity extends condition {

    /**
     * Is condition passing
     * @return bool
     */
    public function pass() : bool {
        $seminarevent = $this->signup->get_seminar_event();
        if ($seminarevent->get_free_capacity() > 0) {
            return true;
        }

        // User can overbook directly if waitlist is disabled.
        $module = $seminarevent->get_seminar()->get_coursemodule();
        $context = \context_module::instance($module->id);
        if (!$seminarevent->get_allowoverbook() && has_capability('mod/facetoface:signupwaitlist', $context)) {
            return true;
        }
        return false;
    }

    public static function get_description() : string {
        return get_string('state_eventhascapacity_desc', 'mod_facetoface');
    }

    public function get_failure() : array {
        return ['event_has_capacity' => get_string('sessionisfull', 'mod_facetoface')];
    }
}
