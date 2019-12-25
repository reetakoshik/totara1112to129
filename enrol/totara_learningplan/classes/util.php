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
 * @package enrol_totara_learningplan
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Various utility methods for learning plan enrolment.
 *
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package enrol_totara_learningplan
 */
class enrol_totara_learningplan_util {
    public static function feature_setting_updated_callback() {
        global $CFG;

        if (!isset($CFG->enrol_plugins_enabled)) {
            // Not installed yet.
            return;
        }

        $resetcaches = false;
        $enabled = explode(',', $CFG->enrol_plugins_enabled);

        if (totara_feature_visible('learningplans')) {
            // NOTE: do not enable learning plans automatically, new installs in 2.7 require manual enabling too.
            /*
            if (!in_array('totara_learningplan', $enabled)) {
                $enabled[] = 'totara_learningplan';
                set_config('enrol_plugins_enabled', implode(',', $enabled));
                $resetcaches = true;
            }
            */
        } else {
            // Make sure the learningplan enrol plugin is disabled.
            if (in_array('totara_learningplan', $enabled)) {
                $enabled = array_flip($enabled);
                unset($enabled['totara_learningplan']);
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
