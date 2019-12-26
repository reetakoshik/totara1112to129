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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\display;

/**
 * Class describing column display formatting.
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_reportbuilder
 */
class orderedlist_to_newline extends base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {

        // Displays a delimited list of strings as one string per line.
        // Assumes you used "'grouping' => 'sql_aggregate'", which concatenates with $uniquedelimiter to construct a pre-ordered string.

        // TODO: Improve how we get the delimiter, perhaps via a definable config.
        $uniquedelimiter = $report->src->get_uniquedelimiter();

        $items = explode($uniquedelimiter, $value);

        $newitems = array();
        foreach ($items as $item) {
            $item = trim($item);
            if (empty($item) || $item === '-') {
                $newitems[] = '-';
            } else {
                $newitems[] = format_string($item);
            }
        }

        $output = implode($newitems, "\n");

        if ($format !== 'html') {
            $output = static::to_plaintext($output);
        }

        // For excel and ods export, force cell type to be a string.
        if ($format == "excel" || $format == "ods") {
            return array('string', $output, null);
        }

        return $output;
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
