<?php
/*
 * This file is part of Totara Learn
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
 * Class user_is_enrolable
 */
class user_is_enrolable extends condition {

    /**
     * Is the restriction met.
     * @return bool
     */
    public function pass(): bool {
        global $DB;

        if (empty($this->signup->get_userid())) {
            return false;
        }

        $user = $DB->get_record('user', ['id' => $this->signup->get_userid()]);

        // $user->suspended is allowed with a reason sample: "A manager may be allocating reserved space to a team
        // member who is on maternity leave, who will not be suspended anymore by the time the event starts."
        if ($user->deleted) {
            return false;
        }

        // User must be not guest and be allowed to enrol
        $seminarevent = $this->signup->get_seminar_event();
        $seminar = $seminarevent->get_seminar();
        $module = $seminar->get_coursemodule();
        $context = \context_module::instance($module->id);

        if (!is_guest($context, $this->signup->get_userid())) {
            return true;
        }

        // Can enrol?
        $enrol = enrol_get_plugin('totara_facetoface');
        $events = $enrol->get_enrolable_sessions($seminar->get_course());
        return in_array($seminarevent->get_id(), array_keys($events));
    }

    public static function get_description(): string {
        return get_string('state_userisnotenrolable_desc', 'mod_facetoface');
    }

    public function get_failure(): array {
        return ['user_is_enrolable' => get_string('state_userisenrolable_fail', 'mod_facetoface')];
    }
}
