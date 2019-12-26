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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_cohort
 */

namespace totara_cohort\rb\source;

defined('MOODLE_INTERNAL') || die();

/**
 * Cohort trait
 */
trait report_trait {
    /**
     * Adds the cohort course tables to the $joinlist array
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include new table joins
     * @param string $join Name of the join that provides the
     *                     'course' table
     * @param string $field Name of course id field to join on
     * @return boolean True
     */
    protected function add_totara_cohort_course_tables(&$joinlist, $join, $field) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/cohort/lib.php');

        $idlist = $DB->sql_group_concat_unique($DB->sql_cast_2char('customint1'), '|');
        $joinlist[] = new \rb_join(
            'cohortenrolledcourse',
            'LEFT',
            // subquery as table name
            "(SELECT courseid AS course, {$idlist} AS idlist
                FROM {enrol} e
               WHERE e.enrol = 'cohort'
            GROUP BY courseid)",
            "cohortenrolledcourse.course = $join.$field",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            $join
        );

        return true;
    }


    /**
     * Adds the cohort program tables to the $joinlist array
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include new table joins
     * @param string $join Name of the join that provides the
     *                     table containing the program id
     * @param string $field Name of program id field to join on
     * @return boolean True
     */
    protected function add_totara_cohort_program_tables(&$joinlist, $join, $field) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/cohort/lib.php');

        $idlist = $DB->sql_group_concat_unique($DB->sql_cast_2char('assignmenttypeid'), '|');
        $joinlist[] = new \rb_join(
            'cohortenrolledprogram',
            'LEFT',
            // subquery as table name
            "(SELECT programid AS program, {$idlist} AS idlist
                FROM {prog_assignment} pa
               WHERE assignmenttype = " . ASSIGNTYPE_COHORT . "
            GROUP BY programid)",
            "cohortenrolledprogram.program = $join.$field",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            $join
        );

        return true;
    }

    /**
     * Adds some common cohort course info to the $columnoptions array
     *
     * @param array &$columnoptions Array of current column options
     *                              Passed by reference and updated by
     *                              this method
     * @param string $cohortenrolledids Name of the join that provides the
     *                          'cohortenrolledcourse' table.
     *
     * @return True
     */
    protected function add_totara_cohort_course_columns(&$columnoptions, $cohortenrolledids='cohortenrolledcourse') {
        $columnoptions[] = new \rb_column_option(
            'cohort',
            'enrolledcoursecohortids',
            get_string('enrolledcoursecohortids', 'totara_reportbuilder'),
            "$cohortenrolledids.idlist",
            array('joins' => $cohortenrolledids, 'selectable' => false)
        );

        return true;
    }


    /**
     * Adds some common cohort program info to the $columnoptions array
     *
     * @param array &$columnoptions Array of current column options
     *                              Passed by reference and updated by
     *                              this method
     * @param string $cohortenrolledids Name of the join that provides the
     *                          'cohortenrolledprogram' table.
     *
     * @return True
     */
    protected function add_totara_cohort_program_columns(&$columnoptions, $cohortenrolledids='cohortenrolledprogram') {
        $columnoptions[] = new \rb_column_option(
            'cohort',
            'enrolledprogramcohortids',
            get_string('enrolledprogramcohortids', 'totara_reportbuilder'),
            "$cohortenrolledids.idlist",
            array('joins' => $cohortenrolledids, 'selectable' => false)
        );

        return true;
    }

    /**
     * Adds some common course cohort filters to the $filteroptions array
     *
     * @param array &$columnoptions Array of current filter options
     *                              Passed by reference and updated by
     *                              this method
     * @return True
     */
    protected function add_totara_cohort_course_filters(&$filteroptions) {

        if (!has_capability('moodle/cohort:view', \context_system::instance())) {
            return true;
        }

        $filteroptions[] = new \rb_filter_option(
            'cohort',
            'enrolledcoursecohortids',
            get_string('courseenrolledincohort', 'totara_reportbuilder'),
            'cohort'
        );

        return true;
    }


    /**
     * Adds some common program cohort filters to the $filteroptions array
     *
     * @param array &$columnoptions Array of current filter options
     *                              Passed by reference and updated by
     *                              this method
     * @param string $langfile Source for translation, totara_program or totara_certification
     *
     * @return True
     */
    protected function add_totara_cohort_program_filters(&$filteroptions, $langfile) {

        if (!has_capability('moodle/cohort:view', \context_system::instance())) {
            return true;
        }

        $filteroptions[] = new \rb_filter_option(
            'cohort',
            'enrolledprogramcohortids',
            get_string('programenrolledincohort', $langfile),
            'cohort'
        );

        return true;
    }
}
