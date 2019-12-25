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
 * Display class intended to convert a course name into a link to that course and shows the course icon next to it
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_reportbuilder
 */
class course_icon_link extends base {

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
        global $CFG, $OUTPUT;
        require_once($CFG->dirroot . '/cohort/lib.php');

        $extrafields = self::get_extrafields_row($row, $column);
        $isexport = ($format !== 'html');

        if ($isexport) {
            return format_string($value);
        }

        $courseid = $extrafields->course_id;
        $courseicon = !empty($extrafields->course_icon) ? $extrafields->course_icon : 'default';
        $cssclass = totara_get_style_visibility($extrafields, 'course_visible', 'course_audiencevisible');
        $icon = \html_writer::empty_tag('img', array('src' => totara_get_icon($courseid, TOTARA_ICON_TYPE_COURSE),
            'class' => 'course_icon', 'alt' => ''));
        $link = $OUTPUT->action_link(
            new \moodle_url('/course/view.php', array('id' => $courseid)),
            $icon . $value, null, array('class' => $cssclass)
        );
        return $link;
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
