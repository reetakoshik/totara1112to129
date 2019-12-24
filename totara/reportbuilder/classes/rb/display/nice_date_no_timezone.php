<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\display;

/**
 * Display date in UTC without timezone.
 *
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_reportbuilder
 */
class nice_date_no_timezone extends base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        if (empty($value)) {
            return '';
        }

        if ($format !== 'excel' and $format !== 'ods') {
            return userdate($value, get_string('strfdateshortmonth', 'langconfig'), 'UTC');
        }

        // Spreadsheet exports do not support timezones, they use always convert to user timezone during export.
        // We want the export to use UTC, that is why we need to offset the timestamp.

        $date = new \DateTime('@' . $value);
        $date->setTimezone(new \DateTimeZone('UTC'));
        $datestr = $date->format('Y-m-d\TH:i:s');
        $newdate = new \DateTime($datestr, \core_date::get_user_timezone_object());
        $timestamp = $newdate->getTimestamp();

        if ($format === 'excel') {
            $dateformat = new \MoodleExcelFormat();
        } else { // ODS
            $dateformat = new \MoodleOdsFormat();
        }

        $dateformat->set_num_format(14);
        return array('date', $timestamp, $dateformat);
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
