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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package mod_facetoface
*/

namespace mod_facetoface\rb\display;

/**
 * Display class intended for the reformat of two timestamps and timezones into a datetime, showing only one date if
 * only one is present and nothing if invalid or null.
 */
class event_dates_period extends \totara_reportbuilder\rb\display\base {

    /**
     * Handles the display
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($startdate, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $CFG;

        // Finishdate and timezone are expected as extra fields.
        $extra = self::get_extrafields_row($row, $column);

        $finishdate = $extra->finishdate;
        $startdatetext = '';
        $finishdatetext = '';
        $returntext = '';

        if (empty($extra->timezone) || (int)$extra->timezone == 99 || empty($CFG->facetoface_displaysessiontimezones)) {
            $targetTZ = \core_date::get_user_timezone();
        } else {
            $targetTZ = \core_date::normalise_timezone($extra->timezone);
        }

        if ($startdate && is_numeric($startdate)) {
            if (!empty($CFG->facetoface_displaysessiontimezones)) {
                $startdate = userdate($startdate, get_string('strftimedatetime', 'langconfig'), $targetTZ) . ' ';
                $tzstring = \core_date::get_localised_timezone($targetTZ);
                $startdatetext = $startdate . $tzstring;
            } else {
                $startdate = userdate($startdate, get_string('strftimedatetime', 'langconfig'), $targetTZ);
                $startdatetext = $startdate;
            }
        }

        if ($finishdate && is_numeric($finishdate)) {
            if (!empty($CFG->facetoface_displaysessiontimezones)) {
                $finishdate = userdate($finishdate, get_string('strftimedatetime', 'langconfig'), $targetTZ) . ' ';
                $tzstring = \core_date::get_localised_timezone($targetTZ);
                $finishdatetext = $finishdate . $tzstring;
            } else {
                $finishdate = userdate($finishdate, get_string('strftimedatetime', 'langconfig'), $targetTZ);
                $finishdatetext = $finishdate;
            }
        }

        if ($startdatetext && $finishdatetext) {
            $returntext = get_string('datebetween', 'totara_reportbuilder', array('from' => $startdatetext, 'to' => $finishdatetext));
        } else if ($startdatetext) {
            $returntext = get_string('dateafter', 'totara_reportbuilder', $startdatetext);
        } else if ($finishdatetext) {
            $returntext = get_string('datebefore', 'totara_reportbuilder', $finishdatetext);
        }

        return $returntext;
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