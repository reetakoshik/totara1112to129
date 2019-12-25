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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_certification
 */

namespace totara_certification\rb\display;

class redambergreenstatus extends \totara_reportbuilder\rb\display\base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {

        $extrafields = self::get_extrafields_row($row, $column);

        switch($value) {
            case '1|problem|expired':
                $datestr = userdate($extrafields->timedue, get_string('strfdateshortmonth', 'langconfig'));
                $str = get_string('status:expired', 'rb_source_certification_completion', $datestr);
                $class = 'label label-danger';
                break;
            case '1|problem|overdue':
                $datestr = userdate($extrafields->timedue, get_string('strfdateshortmonth', 'langconfig'));
                $str = get_string('status:overdue', 'rb_source_certification_completion', $datestr);
                $class = 'label label-danger';
                break;
            case '2|action|assignedwithduedate':
                $datestr = userdate($extrafields->timedue, get_string('strfdateshortmonth', 'langconfig'));
                $str = get_string('status:assignedwithduedate', 'rb_source_certification_completion', $datestr);
                $class = 'label label-warning';
                break;
            case '2|action|windowopen':
                $datestr = userdate($extrafields->timedue, get_string('strfdateshortmonth', 'langconfig'));
                $str = get_string('status:windowopen', 'rb_source_certification_completion', $datestr);
                $class = 'label label-warning';
                break;
            case '3|success|assignedwithoutduedate':
                $str = get_string('status:assignedwithoutduedate', 'rb_source_certification_completion');
                $class = 'label label-success';
                break;
            case '3|success|certified':
                $datestr = userdate($extrafields->timewindowopens, get_string('strfdateshortmonth', 'langconfig'));
                $str = get_string('status:certified', 'rb_source_certification_completion', $datestr);
                $class = 'label label-success';
                break;
            default:
                $str = get_string('status:notavailable', 'rb_source_certification_completion');
                $class = '';
        }
        if ($format !== 'html') {
            return $str;
        }
        return \html_writer::span($str, $class);
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}