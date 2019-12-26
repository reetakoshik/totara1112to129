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
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\display;

/**
 * Display class intended for grade along with passing grade if it is known
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_reportbuilder
 */
class course_grade_string extends base {

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

        $passgrade = isset($extrafields->gradepass) ? sprintf('%d', $extrafields->gradepass) : null;

        $usergrade = (int)$value;
        $grademin = 0;
        $grademax = 100;
        if (isset($extrafields->grademin)) {
            $grademin = $extrafields->grademin;
        }
        if (isset($extrafields->grademax)) {
            $grademax = $extrafields->grademax;
        }

        // We can't have a divisor of zero, and a negative one doesn't make much sense either.
        if ($grademax - $grademin <= 0) {
            return '-';
        }

        $usergrade = sprintf('%.1f', ((($usergrade - $grademin) / ($grademax - $grademin)) * 100));

        if ($value === null or $value === '') {
            return '';
        } else if ($passgrade === null) {
            return "{$usergrade}%";
        } else {
            $a = new \stdClass();
            $a->grade = $usergrade;
            $a->pass = sprintf('%.1f', ((($passgrade - $grademin) / ($grademax - $grademin)) * 100));
            return get_string('gradeandgradetocomplete', 'totara_reportbuilder', $a);
        }
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
