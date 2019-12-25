<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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

/**
 * Class describing column display formatting for global report restrictions column.
 *
 * Displays yes, no or empty depending on status of GRR in each report.
 *
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_reportbuilder
 */
class report_global_restrictions extends base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $CFG, $OUTPUT;

        if (empty($CFG->enableglobalrestrictions)) {
            return '';
        }

        // Retrieve the extra row data.
        $extra = self::get_extrafields_row($row, $column);
        require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');
        $src = \reportbuilder::get_source_object($extra->source, true, false);
        if (!$src->global_restrictions_supported()) {
            return '';
        }

        if ($value) {
            return get_string('yes');
        } else {
            return get_string('no');
        }

    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
