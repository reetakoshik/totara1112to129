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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_program
 */

namespace totara_program\rb\display;

defined('MOODLE_INTERNAL') || die();

/**
 * Display class intended for course names as html links
 *
 * @deprecated since Totara 12, will be removed once MSSQL 2017 is the minimum required version.
 */
class program_course_newline_date extends program_course_base {

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

        if (empty($value)) {
            return '';
        }

        $uniquedelimiter = $report->src->get_uniquedelimiter();

        $output = array();
        $items = explode($uniquedelimiter, $value);
        $reference = [];
        $programid = null;
        foreach ($items as $key => $item) {
            if ($items)
            list($programid, $courseid, $date) = explode('|', $item);
            if (empty($date) || $date === '-') {
                $output[$key] = '-';
            } else {
                $output[$key] = userdate($date, get_string('strfdateshortmonth', 'langconfig'));
            }
            $reference[$key] = $courseid;
        }

        if ($programid && count($output) > 1 && self::resort_required()) {
            $output = self::resort($programid, $output, $reference);
        }

        return implode($output, "\n");
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
