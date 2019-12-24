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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_facetoface
 */


namespace mod_facetoface\rb\display;
defined('MOODLE_INTERNAL') || die();

use totara_reportbuilder\rb\display\base;
use stdClass;
use rb_column;
use rb_column_option;
use reportbuilder;
use MoodleExcelFormat;
use MoodleOdsFormat;

/**
 * Since seminar session/event date is a bit more complicated than the other date time related
 * information, therefore, this display function here was intended for displaying the date time
 * related columns with the user's setting of timezone, and this will change the format to cope well
 * with exporting to different format. Whereas, the other display class is using the seminar's
 * timezone with the config, and it does not cope well with the exporting to different format.
 */
class local_event_date extends base {
    /**
     * @param string        $value
     * @param string        $format
     * @param stdClass      $row
     * @param rb_column     $column
     * @param reportbuilder $report
     *
     * @return string|array
     */
    public static function display($value, $format, stdClass $row, rb_column $column, reportbuilder $report) {
        if (empty($value)) {
            return '';
        }

        if ($format === 'excel') {
            $dateformat = new MoodleExcelFormat();
            $dateformat->set_num_format(22);
            return array('date', $value, $dateformat);
        } else if ($format === 'ods') {
            $dateformat = new MoodleOdsFormat();
            $dateformat->set_num_format(22);
            return array('date', $value, $dateformat);
        }

        $timezone = \core_date::get_user_timezone();
        $date = userdate($value, get_string('strftimedatetime', 'langconfig'), $timezone);
        return $date;
    }

    /**
     * @return bool
     */
    public static function is_graphable(rb_column $column, rb_column_option $option, reportbuilder $report) {
        return false;
    }
}