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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 *
 * @package auth_approved
 */

namespace auth_approved\rb\display;

use \totara_reportbuilder\rb\display\base;

final class request_actions extends base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $OUTPUT;
        $extra = self::get_extrafields_row($row, $column);
        $status = $extra->status;

        $actions = array();
        if ($status == \auth_approved\request::STATUS_PENDING) {
            $url = new \moodle_url('/auth/approved/approve.php', array('requestid' => $value, 'reportid' => $report->_id));
            $actions[] = $OUTPUT->action_icon($url, new \core\output\flex_icon('auth_approved|approve', array('alt' => get_string('approve', 'auth_approved'))));

            $url = new \moodle_url('/auth/approved/reject.php', array('requestid' => $value, 'reportid' => $report->_id));
            $actions[] = $OUTPUT->action_icon($url, new \core\output\flex_icon('auth_approved|reject', array('alt' => get_string('reject', 'auth_approved'))));

            $url = new \moodle_url('/auth/approved/edit.php', array('requestid' => $value, 'reportid' => $report->_id));
            $actions[] = $OUTPUT->action_icon($url, new \core\output\flex_icon('edit', array('alt' => get_string('edit'))));

            $url = new \moodle_url('/auth/approved/message.php', array('requestid' => $value, 'reportid' => $report->_id));
            $actions[] = $OUTPUT->action_icon($url, new \core\output\flex_icon('email', array('alt' => get_string('message', 'auth_approved'))));
        }

        return implode(' ', $actions);
    }
}
