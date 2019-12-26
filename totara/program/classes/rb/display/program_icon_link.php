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
use totara_reportbuilder\rb\display\base;

/**
 * Display class intended to convert a program name into a link to that program and shows the program icon next to it
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_program
 */
class program_icon_link extends base {

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
        global $OUTPUT, $DB, $USER;

        $extrafields = self::get_extrafields_row($row, $column);
        $isexport = ($format !== 'html');

        $value = format_string($value);

        if ($isexport) {
            return $value;
        }

        $prog = new \program($extrafields->programid);
        $user = isset($extrafields->userid) ? $DB->get_record('user', array('id' => $extrafields->userid)) : $USER;

        $accessibility = prog_check_availability($prog->availablefrom, $prog->availableuntil);
        $accessible = $accessibility == AVAILABILITY_TO_STUDENTS;
        $assigned = $prog->user_is_assigned($user->id);

        $progicon = totara_get_icon($prog->id, TOTARA_ICON_TYPE_PROGRAM);
        $icon = \html_writer::empty_tag('img', array('src' => $progicon, 'class' => 'course_icon', 'alt' => ''));

        if ($assigned && $accessible) {
            $url = new \moodle_url('/totara/program/required.php', array('id' => $prog->id, 'userid' => $user->id));
            $html = $OUTPUT->action_link($url, $icon . $prog->fullname);
        } else if ($accessible) {
            $url = new \moodle_url('/totara/program/view.php', array('id' => $prog->id));
            $html = $OUTPUT->action_link($url, $icon . $prog->fullname);
        } else {
            $html = $icon . $prog->fullname;
        }

        return $html;
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
