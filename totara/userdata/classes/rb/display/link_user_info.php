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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_userdata
 */

namespace totara_userdata\rb\display;

use \totara_reportbuilder\rb\display\base;

/**
 * Link to userdata info page.
 */
final class link_user_info extends base {
    /**
     * Display data.
     *
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        $user = self::get_extrafields_row($row, $column);

        if (!$user->id) {
            return '';
        }

        $fullname = fullname($user);
        if ($format !== 'html') {
            return $fullname;
        }

        $fullname = s($fullname);

        $context = \context_user::instance($user->id, IGNORE_MISSING);
        if (!$context) {
            $context = \context_system::instance();
        }
        if (!has_capability('totara/userdata:viewinfo', $context)) {
            return $fullname;
        }

        $url = new \moodle_url('/totara/userdata/user_info.php', array('id' => $user->id));
        return \html_writer::link($url, $fullname);
    }
}
