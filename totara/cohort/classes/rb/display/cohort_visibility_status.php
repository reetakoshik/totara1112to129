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
 * Display class intended for visible learning status
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_cohort
 */
class cohort_visibility_status extends base {

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
        global $CFG, $COHORT_VISIBILITY, $DB;

        $extrafields = self::get_extrafields_row($row, $column);

        if (empty($CFG->audiencevisibility)) {
            return $COHORT_VISIBILITY[$value];
        }

        $type = $extrafields->type;
        if ($type == COHORT_ASSN_ITEMTYPE_COURSE) {
            $hascapability = has_capability('moodle/course:update', \context_course::instance($extrafields->insid));
        } else if (in_array($type, array(COHORT_ASSN_ITEMTYPE_PROGRAM, COHORT_ASSN_ITEMTYPE_CERTIF))) {
            $programcontext = \context_program::instance($extrafields->insid);
            $hascapability = has_capability('totara/program:configuredetails', $programcontext);
        }

        if (isset($hascapability) && $hascapability) { // Has capability to change the visibility of learning contents.
            $cohortshortname = $DB->get_field('cohort', 'idnumber', ['id' => $extrafields->cohortid]);

            // Adding a record id here, so that the id rendered into the browser will be a unique one
            $output = \html_writer::start_tag(
                'form',
                array('id' => "changevisibilityaudience_{$extrafields->insid}_{$extrafields->id}")
            );
            $output .= \html_writer::select(
                $COHORT_VISIBILITY,
                "_{$type}_{$extrafields->insid}_{$extrafields->id}",
                $value,
                false,
                ['data-name' => "{$extrafields->instanceshortname}_{$cohortshortname}"]
            );
            $output .= \html_writer::end_tag('form');
        } else {
            $output = $COHORT_VISIBILITY[$value];
        }

        return $output;
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
