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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_cohort
 */

namespace totara_cohort\rb\display;
use totara_reportbuilder\rb\display\base;

/**
 * Display class intended for "View date" link for a program (should only be used with enrolled items)
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_cohort
 */
class cohort_program_view_duedate_link extends base {

    /**
     * Handles the display
     *
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        $extrafields = self::get_extrafields_row($row, $column);

        static $canedit = null;
        if ($canedit === null) {
            $canedit = has_capability('moodle/cohort:manage', \context_system::instance());
        }

        if ($canedit && ($extrafields->type == COHORT_ASSN_ITEMTYPE_PROGRAM || $extrafields->type == COHORT_ASSN_ITEMTYPE_CERTIF)) {
            $viewsql = new \moodle_url('/totara/program/assignment/duedates_report.php',
                array('programid' => $extrafields->programid, 'assignmentid' => $value));
            return \html_writer::link($viewsql, get_string('viewdates', 'totara_program'),
                array('class' => 'assignment-duedates'));
        }

        return get_string('na', 'totara_cohort');
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
