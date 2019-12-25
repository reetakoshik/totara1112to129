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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\display;

/**
 * Class describing column display formatting.
 *
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_reportbuilder
 */
class yes_or_no4 extends base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {

        static $cerval2 = -1;
        if ($column->aggregate) {
            // When aggregated, we no longer have a 0 or 1 value, so display whatever value is returned.
            return $value;
        }

        // Display empty string for null value.
        if (is_null($value)) {
            return '';
        }
//echo '<pre>';print_r($column);echo '</pre>';
        if ($value == 1 ) {
            //echo $cerval2.'=>1<br>';
            ++$cerval2;
            return '1';
        } else if ($value == 0 ) {
            ///echo $cerval2.'=>0<br>';
            ++$cerval2;
            return '0';
        } else {
            return '';
        }
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
