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
 * @package mod_facetoface
 */

namespace mod_facetoface\rb\display;
use totara_reportbuilder\rb\display\base;

/**
 * Display class intended to return a list of user names linked to their profiles from string of concatenated user names,
 * their ids, and length of every name with id
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package mod_facetoface
 */
class f2f_coded_user_link extends base {

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
        // Concatenated names are provided as (kind of) pascal string beginning with id in the following format:
        // length_of_following_string.' '.id.' '.name.', '
        if (empty($value)) {
            return '';
        }

        $isexport = ($format !== 'html');

        $leftname = $value;
        $result = array();

        while(true) {
            $len = (int)$leftname; // Take string length.
            if (!$len) {
                break;
            }

            $idname = \core_text::substr($leftname, \core_text::strlen((string)$len) + 1, $len, 'UTF-8');
            if (empty($idname)) {
                break;
            }

            $idendpos = \core_text::strpos($idname, ' ');
            $id = (int)\core_text::substr($idname, 0, $idendpos);
            if (!$id) {
                break;
            }

            $name = trim(\core_text::substr($idname, $idendpos));
            $result[] = ($isexport) ? $name : \html_writer::link(new \moodle_url('/user/view.php', array('id' => $id)), $name);

            // length(length(idname)) + length(' ') + length(idname) + length(', ').
            $leftname = \core_text::substr($leftname, \core_text::strlen((string)$len) + 1 + $len + 2);
        }

        return implode(', ', $result);
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
