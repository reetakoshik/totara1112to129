<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\display;

use \scheduler;

/**
 * Class describing column display formatting.
 *
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_reportbuilder
 */
class report_schedule_next extends base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $OUTPUT;

        // Retrieve the extra row data.
        $extra = self::get_extrafields_row($row, $column);

        if (isset($extra->frequency) && isset($value)){
            $report = new \stdClass();
            $report->schedule = $value;
            $report->frequency = $extra->frequency;
            $report->nextevent = $extra->nextreport;
            $scheduler = new scheduler($report);
            if ($next = $scheduler->get_scheduled_time()) {
                if ($next < time()) {
                    // As soon as possible.
                    $next = time();
                }
                return userdate($next);
            } else {
                return get_string('schedulenotset', 'totara_reportbuilder');
            }
        } else {
            return get_string('schedulenotset', 'totara_reportbuilder');
        }

        return $formatted;
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
