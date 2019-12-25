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
 * @author Rob Tyler <rob.tyler@totaralms.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\display;

/**
 * Class describing column display formatting.
 *
 * @author Rob Tyler <rob.tyler@totaralms.com>
 * @package totara_reportbuilder
 */
class nice_date_list extends base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        if (!$value) {
            return '';
        }

        $items = explode(', ', $value);

        foreach ($items as $key => $item) {
            if (!is_numeric($item) || $item == 0 || $item == -1) {
                $items[$key] = '';
            } else {
                $items[$key] = userdate($item, get_string('strfdateshortmonth', 'langconfig'));
            }
        }

        // Use a line-break to separate the dates so they're handled consistently with
        // the other multi-line cells in the HTML report table and spreadsheet exports.
        // Note that because this function handles multiple dates in a single cell,
        // the content should not be converted for Excel oor Open Office - it should be
        // handled as plain text.
        return implode($items, "\n");
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
