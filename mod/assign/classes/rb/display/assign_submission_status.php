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
 * @package mod_assign
 */

namespace mod_assign\rb\display;
use totara_reportbuilder\rb\display\base;

/**
 * Display assign submission status.
 *
 * @package mod_assign
 */
class assign_submission_status extends base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        switch ($value) {
            case 'submitted':
                return get_string('status_submitted', 'rb_source_assign');
            case 'graded':
                return get_string('status_graded', 'rb_source_assign');
            case 'draft':
                return get_string('status_draft', 'rb_source_assign');
            default:
                return get_string('status_notsubmitted', 'rb_source_assign');
        }
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
