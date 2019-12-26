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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package mod_assign
 */

namespace mod_assign\rb\display;
use totara_reportbuilder\rb\display\base;

/**
 * Display class intended for max grade
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package mod_assign
 */
class assign_max_grade extends base {

    /**
     * Handles the display
     *
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        $extrafields = self::get_extrafields_row($row, $column);

        // If assignment doesn't require grading.
        if ($extrafields->assign_grade == 0) {
            return get_string('gradingnotrequired', 'rb_source_assign');
        }

        // if there's no scale values, return the raw grade.
        if (empty($extrafields->scale_values)) {
            return (integer)$value;
        }

        // If there are scale values, work out which scale value is the maximum.
        $v = explode(',', $extrafields->scale_values);
        $i = (integer)count($v) - 1;
        return $v[$i];
    }

    /**
     * Is this column graphable?
     *
     * @param \rb_column $column
     * @param \rb_column_option $option
     * @param \reportbuilder $report
     * @return bool
     */
    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
