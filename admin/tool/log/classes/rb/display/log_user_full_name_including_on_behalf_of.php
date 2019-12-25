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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package tool_log
 */

namespace tool_log\rb\display;
use totara_reportbuilder\rb\display\base;

/**
 * Display class intended for description
 *
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package tool_log
 */
class log_user_full_name_including_on_behalf_of extends base {

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
        $row = self::get_extrafields_row($row, $column);
        $rowarray = (array)$row;

        $isexport = ($format !== 'html');

        $auser = new \stdClass();
        $realuser = new \stdClass();

        foreach ($rowarray as $key => $value) {
            if (substr($key, 0, 5) == 'auser') {
                $shortkey = substr($key, 5);
                $auser->$shortkey = $value;
            } else if (substr($key, 0, 8) == 'realuser') {
                $shortkey = substr($key, 8);
                $realuser->$shortkey = $value;
            }
        }

        if (!empty($row->realuserid)) {
            $a = new \stdClass();
            if (!$a->realusername = fullname($realuser)) {
                $a->realusername = '-';
            }
            if (!$a->asusername = fullname($auser)) {
                $a->asusername = '-';
            }
            if (!$isexport) {
                $a->realusername = \html_writer::link(
                    new \moodle_url(
                        '/user/view.php',
                        array('id' => $row->realuserid)
                    ),
                    $a->realusername
                );
                $a->asusername = \html_writer::link(
                    new \moodle_url(
                        '/user/view.php',
                        array('id' => $row->auserid)
                    ),
                    $a->asusername
                );
            }
            $username = get_string('eventloggedas', 'report_log', $a);

        } else if (!empty($row->auserid) && $username = fullname($auser)) {
            if (!$isexport) {
                $username = \html_writer::link(
                    new \moodle_url(
                        '/user/view.php',
                        array('id' => $row->auserid)
                    ),
                    $username
                );
            }
            return $username;
        } else {
            $username = '-';
        }

        return $username;
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
