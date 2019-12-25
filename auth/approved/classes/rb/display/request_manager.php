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

final class request_manager extends base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $DB;

        if (!$value) {
            return '';
        }
        $extra = self::get_extrafields_row($row, $column);
        $user = $DB->get_record('user', array('id' => $extra->userid));
        if (empty($user)) {
            return '';
        }

        if (!isset($extra->jobfullname) or $extra->jobfullname === '') {
            $jobname = get_string('jobassignmentdefaultfullname', 'totara_job', $extra->jobidnumber);
        } else {
            $jobname = $extra->jobfullname;
        }
        return \fullname($user) . ' - ' . $jobname;
    }
}
