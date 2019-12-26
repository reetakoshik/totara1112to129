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
 * User have capability to signup and cancel on past or in progress events.
 */
class actor_can_signuppastevents extends restriction {
    public function pass() : bool {
        $module =  $this->signup->get_seminar_event()->get_seminar()->get_coursemodule();
        $context = \context_module::instance($module->id);
        return has_capability('mod/facetoface:signuppastevents', $context, $this->signup->get_actor());
    }

    public static function get_description() : string {
        return get_string('state_actorcansignuppastevents_desc', 'mod_facetoface');
    }

    public function get_failure() : array {
        return ['actor_can_signuppastevents' => get_string('state_actorcansignuppastevents_fail', 'mod_facetoface')];
    }
}
