<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Simon Player <simon.player@totaralms.com>
 * @package totara_reportbuilder
 */

namespace totara_program\rb\display;

/**
 * Class describing column display formatting.
 *
 * @author Simon Player <simon.player@totaralms.com>
 * @package totara_reportbuilder
 */
class programduedate extends \totara_reportbuilder\rb\display\base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        // Get the necessary fields out of the row.
        $extrafields = self::get_extrafields_row($row, $column);

        if (!is_numeric($value) || $value == 0 || $value == -1) {
            return '';
        }

        if (!empty($extrafields->unassigned)) {
            return '';
        }

        if ($format === 'excel') {
            $dateformat = new \MoodleExcelFormat();
            $dateformat->set_num_format(14);
            return array('date', $value, $dateformat);
        }

        if ($format === 'ods') {
            $dateformat = new \MoodleOdsFormat();
            $dateformat->set_num_format(14);
            return array('date', $value, $dateformat);
        }

        if ($format === 'csv') {
            return userdate($value, get_string('strfdateshortmonth', 'langconfig'));
        }

        $out = prog_display_duedate($value, $extrafields->programid, $extrafields->userid);

        if ($format !== 'html') {
            return parent::to_plaintext($out, true);
        }

        return $out;
    }
}