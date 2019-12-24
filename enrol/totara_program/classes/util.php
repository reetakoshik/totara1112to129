<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package enrol_totara_program
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Various utility methods for program enrolment.
 *
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package enrol_totara_program
 */
class enrol_totara_program_util {
    public static function feature_setting_updated_callback() {
        global $CFG;

        if (!isset($CFG->enrol_plugins_enabled)) {
            // Not installed yet.
            return;
        }

        $resetcaches = false;
        $enabled = explode(',', $CFG->enrol_plugins_enabled);

        if (totara_feature_visible('programs') || totara_feature_visible('certifications')) {
            // Make sure the program enrol plugin is enabled.
            if (!in_array('totara_program', $enabled)) {
                $enabled[] = 'totara_program';
                set_config('enrol_plugins_enabled', implode(',', $enabled));
                $resetcaches = true;
            }
        } else {
            // Make sure the program enrol plugin is disabled.
            if (in_array('totara_program', $enabled)) {
                $enabled = array_flip($enabled);
                unset($enabled['totara_program']);
                $enabled = array_flip($enabled);
                set_config('enrol_plugins_enabled', implode(',', $enabled));
                $resetcaches = true;
            }
        }

        if ($resetcaches) {
            // Reset enrol and plugin caches.
            core_plugin_manager::reset_caches();
            $syscontext = context_system::instance();
            $syscontext->mark_dirty();
        }
    }
}
