<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\display;

defined('MOODLE_INTERNAL') || die();

/**
 * Code shared by all user custom fields.
 *
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_reportbuilder
 */
trait userfield_trait {
    public static function is_cell_visible($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $USER;

        $visibility = $column->extracontext['visible'];

        if ($visibility == PROFILE_VISIBLE_NONE) {
            $extrafields = self::get_extrafields_row($row, $column);
            if (empty($extrafields->userid)) {
                // This happens when we use LEFT join to find user id and profile data is missing.
                $context = \context_system::instance();
            } else {
                $context = \context_user::instance($extrafields->userid, IGNORE_MISSING);
                if (!$context) {
                    // Deleted user.
                    $context = \context_system::instance();
                }
            }
            if (!has_capability('moodle/user:viewalldetails', $context)) {
                return false;
            }

        } else if ($visibility == PROFILE_VISIBLE_PRIVATE) {
            $extrafields = self::get_extrafields_row($row, $column);
            if (!empty($extrafields->userid) and $USER->id == $extrafields->userid) {
                // Fine - users can view own profile data.
            } else {
                if (empty($extrafields->userid)) {
                    // This happens when we use LEFT join to find user id and profile data is missing.
                    $context = \context_system::instance();
                } else {
                    $context = \context_user::instance($extrafields->userid, IGNORE_MISSING);
                    if (!$context) {
                        // Deleted user.
                        $context = \context_system::instance();
                    }
                }
                if (!has_capability('moodle/user:viewalldetails', $context)) {
                    return false;
                }
            }
        }

        return true;
    }
}