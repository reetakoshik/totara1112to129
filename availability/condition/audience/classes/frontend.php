<?php
/*
 * This file is part of Totara Learn
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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package availability_audience
 */

namespace availability_audience;

defined('MOODLE_INTERNAL') || die();

/**
 * Front-end class.
 */
class frontend extends \core_availability\frontend {

    /**
     * Restrict the adding of this restriction to people who have the capability
     * to view audiences.
     *
     * @param stdClass course
     * @param \cm_info $cm
     * @param \section_info $section
     * @return bool True if the user can add this restriction.
     */
    protected function allow_add($course, \cm_info $cm = null, \section_info $section = null) {
        if (!empty($course->id)) {
            $context = \context_course::instance($course->id);
        } else if (!empty($course->category)) {
            $context = \context_coursecat::instance($course->category);
        } else {
            $context = \context_system::instance();
        }

        if (has_capability('moodle/cohort:view', $context)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Strings to send to the form javascript
     *
     * @return array An array of string identifiers
     */
    protected function get_javascript_strings() {
        return array('chooseaudience', 'error_selectfield');
    }

    /**
     * Parameters to pass into javascript module
     *
     * @param Course $course Course object
     * @param \cm_info $cm Course module currently being edited
     * @param \section_info $section Section currently being edited
     *
     * @return array Array of parameters for the JavaScript function
     */
    protected function get_javascript_init_params($course, \cm_info $cm = null,
            \section_info $section = null) {
        global $DB;

        $audience_names = array();

        if (!empty($cm->availability)) {
            $ids = array();
            self::for_each_condition_in_availability_json(
                $cm->availability, function ($condition) use (&$ids) {
                    if ($condition->type == 'audience') {
                        $ids[$condition->cohort] = $condition->cohort;
                    }
                }
            );

            if (!empty($ids)) {
                list($insql, $params) = $DB->get_in_or_equal($ids);
                $sql = "SELECT id, name
                        FROM {cohort}
                        WHERE id $insql";
                $audience_names = $DB->get_records_sql($sql, $params);

                foreach ($audience_names as $id => $value) {
                    $value->name = format_string($value->name);
                    $audience_names[$id] = $value;
                }
            }
        } else if (!empty($section->availability)) {
            $util = new section_util($section);
            $cohorts = $util->load_cohort_availabilities();
            if (!empty($cohorts)) {
                foreach ($cohorts as $cohort) {
                    $name = format_string($cohort->name);
                    $audience_names[$cohort->id] = array('name' => $name);
                }
            }
        } else {
            $audience_names = $DB->get_records_sql('SELECT id, name FROM {cohort}');
            foreach ($audience_names as $id => $value) {
                $value->name = format_string($value->name);
                $audience_names[$id] = $value;
            }
        }

        $data = new \stdClass();
        $data->audienceNames = $audience_names;
        $result = array($data);

        return $result;
    }
}
