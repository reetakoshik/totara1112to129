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
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\display;

/**
 * Display class intended for reformat a timestamp and timezone into a datetime, showing nothing if invalid or null
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_reportbuilder
 */
class nice_datetime_in_timezone extends base {

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
        if ($value && is_numeric($value)) {
            $extrafields = self::get_extrafields_row($row, $column);
            if (empty($extrafields->timezone)) {
                $targetTZ = \core_date::get_user_timezone();
                $tzstring = get_string('nice_time_unknown_timezone', 'totara_reportbuilder');
            } else {
                $targetTZ = \core_date::normalise_timezone($extrafields->timezone);
                $tzstring = \core_date::get_localised_timezone($targetTZ);
            }
            $date = userdate($value, get_string('strftimedatetime', 'langconfig'), $targetTZ) . ' ';
            return $date . $tzstring;
        } else {
            return '';
        }
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
