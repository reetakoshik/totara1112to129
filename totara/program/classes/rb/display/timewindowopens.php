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
class timewindowopens extends \totara_reportbuilder\rb\display\base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {

        global $OUTPUT;

        // Get the necessary fields out of the row.
        $extrafields = self::get_extrafields_row($row, $column);

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

        $extra = '';
        if ($value < time()) {
            // Window is currently open or expired.
            if ($extrafields->status != CERTIFSTATUS_EXPIRED) {
                $extra = $OUTPUT->notification(get_string('windowopen', 'totara_certification'), 'notifysuccess');
            } else {
                $extra = $OUTPUT->notification(get_string('status_expired', 'totara_certification'), 'notifyproblem');
            }
        } else {
            // Window is sometime in the future.
            $days_remaining = floor(($value - time()) / 86400);

            if ($days_remaining == 1) {
                $extra = $OUTPUT->notification(get_string('windowopenin1day', 'totara_certification'), 'notifynotice');
            } else if ($days_remaining < 10 && $days_remaining > 0) {
                $extra = $OUTPUT->notification(get_string('windowopeninxdays', 'totara_certification', $days_remaining), 'notifynotice');
            }
        }

        if ($format !== 'html') {
            $out .= $extra;
            return parent::to_plaintext($out, true);
        }

        if (!empty($extra)) {
            // Can't use html_writer due to namespace issues
            $out .= '<br />' . $extra;
        }

        return $out;
    }
}