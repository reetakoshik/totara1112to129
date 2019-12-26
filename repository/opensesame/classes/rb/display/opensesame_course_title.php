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
 * @package repository_opensesame
 */

namespace repository_opensesame\rb\display;
use totara_reportbuilder\rb\display\base;

/**
 * Display class intended for showing the course title with create course icon
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package repository_opensesame
 */
class opensesame_course_title extends base {

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
        global $OUTPUT;

        $value = format_string($value);

        $isexport = ($format !== 'html');

        if ($isexport) {
            return $value;
        }

        $extrafields = self::get_extrafields_row($row, $column);

        $return = $value;
        $syscontext = \context_system::instance();
        if (!has_capability('repository/opensesame:managepackages', $syscontext)) {
            return $value;
        }

        $createurl = new \moodle_url('/repository/opensesame/create_course.php', array('id' => $extrafields->packageid));
        $icon = new \pix_icon('t/add', get_string('createcourse', 'totara_program'));
        $return .= ' ' . $OUTPUT->action_icon($createurl, $icon);

        return $return;
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
