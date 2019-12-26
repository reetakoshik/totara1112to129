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
 * Display class intended to display an icon based on the course icon field
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_reportbuilder
 */
class course_type_icon extends base {

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

        $isexport = ($format !== 'html');

        if ($isexport) {
            switch ($value) {
                case TOTARA_COURSE_TYPE_ELEARNING:
                    return get_string('elearning', 'rb_source_dp_course');
                case TOTARA_COURSE_TYPE_BLENDED:
                    return get_string('blended', 'rb_source_dp_course');
                case TOTARA_COURSE_TYPE_FACETOFACE:
                    return get_string('facetoface', 'rb_source_dp_course');
                default:
                    return '';
            }
        }

        switch ($value) {
            case null:
                return null;
            case TOTARA_COURSE_TYPE_ELEARNING:
                $image = 'elearning';
                break;
            case TOTARA_COURSE_TYPE_BLENDED:
                $image = 'blended';
                break;
            case TOTARA_COURSE_TYPE_FACETOFACE:
                $image = 'facetoface';
                break;
        }
        $alt = get_string($image, 'rb_source_dp_course');
        $icon = $OUTPUT->pix_icon('/msgicons/' . $image . '-regular', $alt, 'totara_core', array('title' => $alt));

        return $icon;
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
