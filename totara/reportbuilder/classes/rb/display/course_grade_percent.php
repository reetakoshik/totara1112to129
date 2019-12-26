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
 * Display class intended for showing course grade via grade or RPL as a percentage string
 * When exporting, only the user's full name is displayed (without link)
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_reportbuilder
 */
class course_grade_percent extends base {

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

        if ($extrafields->status == COMPLETION_STATUS_COMPLETEVIARPL && !empty($extrafields->rplgrade)) {
            // If RPL then print the RPL grade.
            return sprintf('%.1f%%', $extrafields->rplgrade);
        } else if (!empty($extrafields->maxgrade) && !empty($value)) {

            $maxgrade = (float)$extrafields->maxgrade;
            $mingrade = 0.0;
            if (!empty($extrafields->mingrade)) {
                $mingrade = (float)$extrafields->mingrade;
            }

            // We can't have a divisor of zero, and a negative one doesn't make much sense either.
            if ($maxgrade - $mingrade <= 0) {
                return '-';
            }

            // Create a percentage using the max grade.
            $percent = ((($value - $mingrade) / ($maxgrade - $mingrade)) * 100);

            return sprintf('%.1f%%', $percent);
        } else if ($value !== null && $value !== '') {
            // If the item has a value show it.
            return $value;
        } else {
            // Otherwise show a '-'
            return '-';
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
        return true;
    }
}
