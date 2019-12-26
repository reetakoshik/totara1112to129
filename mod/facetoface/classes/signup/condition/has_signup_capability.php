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
 * Class has_signup_capability
 * Confirms that user has signup capability or seminar_enrolment module is enabled for course.
 */
class has_signup_capability extends condition {

    /**
     * Is condition passing
     * @return bool
     */
    public function pass() : bool {
        global $CFG;
        if (PHPUNIT_TEST) {
            // During tests, users are treated like they don't have it, even when enrolled.
            return true;
        }

        require_once($CFG->dirroot . '/lib/enrollib.php');
        $courseid = $this->signup->get_seminar_event()->get_seminar()->get_course();
        $instances = enrol_get_instances($courseid, true);
        foreach ($instances as $instance) {
            if ($instance->enrol == 'totara_facetoface') {
                return true;
            }
        }

        $module = $this->signup->get_seminar_event()->get_seminar()->get_coursemodule();
        $context = \context_module::instance($module->id);
        return has_capability('mod/facetoface:signup', $context);
    }

    public static function get_description() : string {
        return get_string('state_hassignupcapability_desc', 'mod_facetoface');
    }

    public function get_failure() : array {
        return ['has_signup_capability' => get_string('error:nopermissiontosignup', 'mod_facetoface')];
    }
}
