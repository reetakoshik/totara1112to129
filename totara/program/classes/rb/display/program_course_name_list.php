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
 * @package totara_program
 */

namespace totara_program\rb\display;

/**
 * Display class intended for course names as html links
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_program
 */
class program_course_name_list extends program_course_base {

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

        if (empty($value)) {
            return '';
        }

        $isexport = ($format !== 'html');

        $uniquedelimiter = $report->src->get_uniquedelimiter();
        $items = explode($uniquedelimiter, $value);
        $reference = [];
        $programid = null;
        foreach ($items as $key => $item) {
            list($programid, $courseid, $coursename) = explode('|', $item);

            if ($isexport) {
                $items[$key] = format_string($coursename);
            } else {
                $url = new \moodle_url('/course/view.php', array('id' => $courseid));
                $items[$key] = \html_writer::link($url, format_string($coursename));
            }

            $reference[$key] = $courseid;
        }

        if ($programid && count($items) > 1 && self::resort_required()) {
            $items = self::resort($programid, $items, $reference);
        }

        return implode($items, "\n");
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
