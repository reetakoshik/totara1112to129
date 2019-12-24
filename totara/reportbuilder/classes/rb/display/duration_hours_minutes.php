<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @author Rob Tyler <rob.tyler@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\display;

/**
 * Class describing column display formatting for time in hours.
 */
class duration_hours_minutes extends base {
    public static function display ($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        $result = '';

        $minutes = abs ((int) $value);

        if ($format === 'graph') {
            $result = round ($minutes / 60, 2);
        } else {
            $clock_hours = floor ($minutes / 60);
            $clock_minutes = $minutes - ($clock_hours * 60);
            $a = array ('hours' => $clock_hours, 'minutes' => $clock_minutes);
            $result = get_string ('duration_hours_minutes', 'totara_reportbuilder', $a);
        }

        return $result;
    }

    public static function is_graphable (\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return true;
    }
}
