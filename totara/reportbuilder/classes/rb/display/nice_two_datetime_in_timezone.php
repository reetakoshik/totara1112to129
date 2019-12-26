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
 * Display class intended for the reformat of two timestamps and timezones into a datetime, showing only one date if
 * only one is present and nothing if invalid or null.
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_reportbuilder
 */
class nice_two_datetime_in_timezone extends base {

    /**
     * Handles the display
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        // finishdate and timezone are expected as extra fields.
        $extrafields = self::get_extrafields_row($row, $column);

        $finishdate = $extrafields->finishdate;
        $startdatetext = '';
        $finishdatetext = '';
        $returntext = '';

        if (empty($extrafields->timezone)) {
            $targetTZ = \core_date::get_user_timezone();
        } else {
            $targetTZ = \core_date::normalise_timezone($extrafields->timezone);
        }

        if ($value && is_numeric($value)) {
            $startdatetext = userdate($value, get_string('strftimedatetime', 'langconfig'), $targetTZ) . ' ' . $targetTZ;
        }

        if ($finishdate && is_numeric($finishdate)) {
            $finishdatetext = userdate($finishdate, get_string('strftimedatetime', 'langconfig'), $targetTZ) . ' ' . $targetTZ;
        }

        if ($startdatetext && $finishdatetext) {
            $returntext = get_string('datebetween', 'totara_reportbuilder', array('from' => $startdatetext, 'to' => $finishdatetext));
        } else if ($startdatetext) {
            $returntext = get_string('dateafter', 'totara_reportbuilder', $startdatetext);
        } else if ($finishdatetext) {
            $returntext = get_string('datebefore', 'totara_reportbuilder', $finishdatetext);
        }

        return $returntext;
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
