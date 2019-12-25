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
 * Class describing column display formatting for the edit and view link column.
 *
 * Displays the report name as a link to the report editing page, with a second
 * link to the report itself called "View".
 *
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_reportbuilder
 */
class report_link_edit_and_view extends base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $OUTPUT, $CFG;

        // Retrieve the extra row data.
        $extra = self::get_extrafields_row($row, $column);

        if ($format == 'html') {
            // Build minimal object needed to get URL.
            $report = new \stdClass();
            $report->id = $extra->id;
            $report->embedded = $extra->embedded;
            $report->shortname = $extra->shortname;

            // This can be expensive, but should only happen in paged view as link not generated on export.
            require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');
            $viewreporturl = reportbuilder_get_report_url($report);
            $editreporturl = new \moodle_url('/totara/reportbuilder/general.php', ['id' => $extra->id]);

            return $OUTPUT->action_link($editreporturl, $value) . ' (' .
                $OUTPUT->action_link($viewreporturl, get_string('view')) . ')';
        } else {
            return $value;
        }
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
