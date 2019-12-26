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
 * @package totara_program
 */

namespace totara_program\rb\source;

defined('MOODLE_INTERNAL') || die();

trait program_trait {

    /**
     * Adds the program table to the $joinlist array
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include new table joins
     * @param string $join Name of the join that provides the
     *                     'program id' field
     * @param string $field Name of table containing program id field to join on
     *
     * @retun bool always true
     */
    protected function add_totara_program_tables(&$joinlist, $join, $field) {

        $joinlist[] = new \rb_join(
            'program',
            'LEFT',
            '{prog}',
            "program.id = $join.$field",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            $join
        );
        return true;
    }

    /**
     * Adds some common program info to the $columnoptions array
     *
     * @param array &$columnoptions Array of current column options
     *                              Passed by reference and updated by
     *                              this method
     * @param string $join Name of the join that provides the {prog} table, either 'program' or 'base'
     *
     * @return bool
     */
    protected function add_totara_program_columns(&$columnoptions, $join) {
        $columnoptions[] = new \rb_column_option(
            'prog',
            'fullname',
            get_string('programname', 'totara_program'),
            "$join.fullname",
            array('joins' => $join,
                  'dbdatatype' => 'char',
                  'outputformat' => 'text',
                  'displayfunc' => 'format_string')
        );
        $columnoptions[] = new \rb_column_option(
            'prog',
            'shortname',
            get_string('programshortname', 'totara_program'),
            "$join.shortname",
            array('joins' => $join,
                  'dbdatatype' => 'char',
                  'outputformat' => 'text',
                  'displayfunc' => 'format_string')
        );
        $columnoptions[] = new \rb_column_option(
            'prog',
            'idnumber',
            get_string('programidnumber', 'totara_program'),
            "$join.idnumber",
            array('joins' => $join,
                  'displayfunc' => 'plaintext',
                  'dbdatatype' => 'char',
                  'outputformat' => 'text')
        );
        $columnoptions[] = new \rb_column_option(
            'prog',
            'id',
            get_string('programid', 'totara_program'),
            "$join.id",
            array('joins' => $join,
                  'displayfunc' => 'integer')
        );
        $columnoptions[] = new \rb_column_option(
            'prog',
            'summary',
            get_string('programsummary', 'totara_program'),
            "$join.summary",
            array(
                'joins' => $join,
                'displayfunc' => 'editor_textarea',
                'extrafields' => array(
                    'filearea' => '\'summary\'',
                    'component' => '\'totara_program\'',
                    'context' => '\'context_program\'',
                    'recordid' => "$join.id",
                    'fileid' => 0
                ),
                'dbdatatype' => 'text',
                'outputformat' => 'text'
            )
        );
        $columnoptions[] = new \rb_column_option(
            'prog',
            'availablefrom',
            get_string('availablefrom', 'totara_program'),
            "$join.availablefrom",
            array(
                'joins' => $join,
                'displayfunc' => 'nice_date',
                'dbdatatype' => 'timestamp'
            )
        );
        $columnoptions[] = new \rb_column_option(
            'prog',
            'availableuntil',
            get_string('availableuntil', 'totara_program'),
            "$join.availableuntil",
            array(
                'joins' => $join,
                'displayfunc' => 'nice_date',
                'dbdatatype' => 'timestamp'
            )
        );
        $columnoptions[] = new \rb_column_option(
            'prog',
            'proglinkicon',
            get_string('prognamelinkedicon', 'totara_program'),
            "$join.fullname",
            array(
                'joins' => $join,
                'displayfunc' => 'program_icon_link',
                'defaultheading' => get_string('programname', 'totara_program'),
                'extrafields' => array(
                    'programid' => "$join.id",
                    'program_icon' => "$join.icon",
                    'program_visible' => "$join.visible",
                    'program_audiencevisible' => "$join.audiencevisible",
                )
            )
        );
        $columnoptions[] = new \rb_column_option(
            'prog',
            'progexpandlink',
            get_string('programexpandlink', 'totara_program'),
            "$join.fullname",
            array(
                'joins' => $join,
                'displayfunc' => 'program_expand',
                'defaultheading' => get_string('programname', 'totara_program'),
                'extrafields' => array(
                    'prog_id' => "$join.id",
                    'prog_visible' => "$join.visible",
                    'prog_audiencevisible' => "$join.audiencevisible",
                    'prog_certifid' => "$join.certifid")
            )
        );
        $audvisibility = get_config(null, 'audiencevisibility');
        if (empty($audvisibility)) {
            $programvisiblestring = get_string('programvisible', 'totara_program');
            $audvisibilitystring = get_string('audiencevisibilitydisabled', 'totara_reportbuilder');
        } else {
            $programvisiblestring = get_string('programvisibledisabled', 'totara_program');
            $audvisibilitystring = get_string('audiencevisibility', 'totara_reportbuilder');
        }
        $columnoptions[] = new \rb_column_option(
            'prog',
            'visible',
            $programvisiblestring,
            "$join.visible",
            array(
                'joins' => $join,
                'displayfunc' => 'yes_or_no'
            )
        );
        $columnoptions[] = new \rb_column_option(
            'prog',
            'audvis',
            $audvisibilitystring,
            "$join.audiencevisible",
            array(
                'joins' => $join,
                'displayfunc' => 'cohort_visibility'
            )
        );

        return true;
    }

    /**
     * Adds some common program filters to the $filteroptions array
     *
     * @param array &$filteroptions Array of current filter options
     *                              Passed by reference and updated by
     *                              this method
     * @return bool
     */
    protected function add_totara_program_filters(&$filteroptions) {
        $filteroptions[] = new \rb_filter_option(
            'prog',
            'fullname',
            get_string('programname', 'totara_program'),
            'text'
        );
        $filteroptions[] = new \rb_filter_option(
            'prog',
            'shortname',
            get_string('programshortname', 'totara_program'),
            'text'
        );
        $filteroptions[] = new \rb_filter_option(
            'prog',
            'idnumber',
            get_string('programidnumber', 'totara_program'),
            'text'
        );
        $filteroptions[] = new \rb_filter_option(
            'prog',
            'summary',
            get_string('programsummary', 'totara_program'),
            'textarea'
        );
        $filteroptions[] = new \rb_filter_option(
            'prog',
            'availablefrom',
            get_string('availablefrom', 'totara_program'),
            'date'
        );
        $filteroptions[] = new \rb_filter_option(
            'prog',
            'availableuntil',
            get_string('availableuntil', 'totara_program'),
            'date'
        );
        return true;
    }
}
