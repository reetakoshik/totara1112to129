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
class timeexpires extends \totara_reportbuilder\rb\display\base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $OUTPUT;

        if (!is_numeric($value) || $value == 0 || $value == -1) {
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

        $out = userdate($value, get_string('strfdateshortmonth', 'langconfig'));

        $days = '';
        $days_remaining = floor(($value - time()) / 86400);
        if ($days_remaining == 1) {
            $days = get_string('onedayremaining', 'totara_program');
        } else if ($days_remaining < 10 && $days_remaining > 0) {
            $days = get_string('daysremaining', 'totara_program', $days_remaining);
        } else if ($value < time()) {
            $days = get_string('overdue', 'totara_plan');
        }

        if ($format !== 'html') {
            // We use <br /> even though it's about to be replaced using to_plaintext()
            // as new lines using "\n" simply get lost in that same function.
            $out .= '<br />' . $days;
            return parent::to_plaintext($out, true);
        }

        if ($days != '') {
            // Can't use html_writer due to namespace issues
            $out .= '<br />' . $OUTPUT->error_text($days);
        }

        return $out;
    }
}