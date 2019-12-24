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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\display;

/**
 * Class describing column display formatting.
 */
class report_schedule_audiences extends base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $DB;

        $ids = array_reduce(
            explode(",", $value),

            function ($accumulated, $part) {
                $trimmed = trim($part);
                if (!empty($trimmed)) {
                    $accumulated[] = $trimmed;
                }

                return $accumulated;
            },

            []
        );

        return array_reduce(
            $DB->get_records_list('cohort', 'id', $ids, '', 'id, name'),

            function ($names, $cohort) {
                $name = $cohort->name;
                return empty($names) ? $name : "$names<br/>$name";
            },

            ''
        );
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
