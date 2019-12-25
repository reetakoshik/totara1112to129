<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @package mod_facetoface
 */

namespace mod_facetoface\rb\display;

/**
 * Display Seminar event session date/time including timezone.
 *
 * @package mod_facetoface
 */
class event_date_link extends \totara_reportbuilder\rb\display\base {

    /**
     * Displays the session date and time with timezone.
     *
     * @param string $date UTC timestamp.
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($date, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $OUTPUT;
        $extra = self::get_extrafields_row($row, $column);
        $sessionid = $extra->session_id;
        if ($date && is_numeric($date)) {
            $date = \mod_facetoface\rb\display\event_date::display($date, $format, $row, $column, $report);
        } else {
            return "";
        }
        if ($format != 'html') {
            return $date;
        }
        return $OUTPUT->action_link(new \moodle_url('/mod/facetoface/attendees/view.php', array('s' => $sessionid)), $date);
    }

    /**
     * Is this column graphable? No is the answer. You can't plot status strings.
     *
     * @param \rb_column $column
     * @param \rb_column_option $option
     * @param \reportbuilder $report
     * @return bool
     */
    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        // You can't plot strings on a graph - this display type is not graphable.
        return false;
    }
}