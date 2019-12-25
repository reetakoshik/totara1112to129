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
 * Display class intended to convert a program/certification name into an expanding link.
 * When exporting, only the user's full name is displayed (without link)
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_program
 */
class program_expand extends base {

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

        $extrafields = self::get_extrafields_row($row, $column);
        $isexport = ($format !== 'html');

        if ($isexport) {
            return format_string($value);
        }

        $attr = array('class' => totara_get_style_visibility($extrafields, 'prog_visible', 'prog_audiencevisible'));
        $alturl = new \moodle_url('/totara/program/view.php', array('id' => $extrafields->prog_id));

        // Serialize the data so that it can be passed as a single value.
        $paramstring = http_build_query(array('expandprogid' => $extrafields->prog_id), '', '&');

        $class_link = 'rb-display-expand-link ';
        if (array_key_exists('class', $attr)) {
            $class_link .=  $attr['class'];
        }

        $attr['class'] = 'rb-display-expand';
        $attr['data-name'] = 'prog_details';
        $attr['data-param'] = $paramstring;
        $infoicon = $OUTPUT->flex_icon('info-circle', ['classes' => 'ft-state-info']);

        // Create the result.
        $link = \html_writer::link($alturl, format_string($value), array('class' => $class_link));
        return \html_writer::div($infoicon . $link, 'rb-display-expand', $attr);
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
