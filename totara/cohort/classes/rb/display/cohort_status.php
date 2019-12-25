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
 * Display class intended for the cohort status
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_cohort
 */
class cohort_status extends base {

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

        $now = time();
        if (totara_cohort_is_active($extrafields, $now)) {
            return get_string('cohortdateactive', 'totara_cohort');
        }

        if ($extrafields->startdate && $extrafields->startdate > $now) {
            return get_string('cohortdatenotyetstarted', 'totara_cohort');
        }

        if ($extrafields->enddate && $extrafields->enddate < $now) {
            return get_string('cohortdatealreadyended', 'totara_cohort');
        }

        return '';
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
