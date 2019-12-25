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
 * @package totara_certification
 */

namespace totara_certification\rb\source;

defined('MOODLE_INTERNAL') || die();

trait certification_trait {
    /**
     * Adds the program table to the $joinlist array
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include new table joins
     * @param string $join Name of the join that provides the
     *                     'program id' field
     * @param string $field Name of table containing program id field to join on
     * @return bool always true
     */
    protected function add_totara_certification_tables(&$joinlist, $join, $field) {

        $joinlist[] = new \rb_join(
            'certif',
            'INNER',
            "(SELECT p.*, c.learningcomptype, c.activeperiod, c.minimumactiveperiod, c.windowperiod, c.recertifydatetype
                FROM {prog} p
                JOIN {certif} c ON c.id = p.certifid)",
            "certif.id = $join.$field",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            $join
        );

        return true;
    }

    /**
     * Adds some common certification info to the $columnoptions array
     *
     * @param array &$columnoptions Array of current column options
     *                              Passed by reference and updated by
     *                              this method
     * @param string $join Name of the join that provides the {prog}+{certif} virtual table, either 'certif' or 'base'
     *
     * @return bool always true
     */
    protected function add_totara_certification_columns(&$columnoptions, $join) {
        $columnoptions[] = new \rb_column_option(
            'certif',
            'fullname',
            get_string('programname', 'totara_certification'),
            "$join.fullname",
            array('joins' => $join,
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'displayfunc' => 'format_string')
        );
        $columnoptions[] = new \rb_column_option(
            'certif',
            'shortname',
            get_string('programshortname', 'totara_certification'),
            "$join.shortname",
            array('joins' => $join,
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'displayfunc' => 'format_string')
        );
        $columnoptions[] = new \rb_column_option(
            'certif',
            'idnumber',
            get_string('programidnumber', 'totara_certification'),
            "$join.idnumber",
            array('joins' => $join,
                'displayfunc' => 'plaintext',
                'dbdatatype' => 'char',
                'outputformat' => 'text')
        );
        $columnoptions[] = new \rb_column_option(
            'certif',
            'id',
            get_string('programid', 'totara_certification'),
            "$join.id",
            array('joins' => $join,
                'displayfunc' => 'integer')
        );
        $columnoptions[] = new \rb_column_option(
            'certif',
            'summary',
            get_string('programsummary', 'totara_certification'),
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
            'certif',
            'availablefrom',
            get_string('availablefrom', 'totara_certification'),
            "$join.availablefrom",
            array(
                'joins' => $join,
                'displayfunc' => 'nice_date',
                'dbdatatype' => 'timestamp'
            )
        );
        $columnoptions[] = new \rb_column_option(
            'certif',
            'availableuntil',
            get_string('availableuntil', 'totara_certification'),
            "$join.availableuntil",
            array(
                'joins' => $join,
                'displayfunc' => 'nice_date',
                'dbdatatype' => 'timestamp'
            )
        );
        $columnoptions[] = new \rb_column_option(
            'certif',
            'proglinkicon',
            get_string('prognamelinkedicon', 'totara_certification'),
            "$join.fullname",
            array(
                'joins' => $join,
                'displayfunc' => 'program_icon_link',
                'defaultheading' => get_string('programname', 'totara_certification'),
                'extrafields' => array(
                    'programid' => "$join.id",
                    'program_icon' => "$join.icon",
                    'program_visible' => "$join.visible",
                    'program_audiencevisible' => "$join.audiencevisible",
                )
            )
        );
        $columnoptions[] = new \rb_column_option(
            'certif',
            'progexpandlink',
            get_string('programexpandlink', 'totara_certification'),
            "$join.fullname",
            array(
                'joins' => $join,
                'displayfunc' => 'program_expand',
                'defaultheading' => get_string('programname', 'totara_certification'),
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
            'certif',
            'visible',
            $programvisiblestring,
            "$join.visible",
            array(
                'joins' => $join,
                'displayfunc' => 'yes_or_no'
            )
        );
        $columnoptions[] = new \rb_column_option(
            'certif',
            'audvis',
            $audvisibilitystring,
            "$join.audiencevisible",
            array(
                'joins' => $join,
                'displayfunc' => 'cohort_visibility'
            )
        );

        $columnoptions[] = new \rb_column_option(
            'certif',
            'recertifydatetype',
            get_string('recertdatetype', 'totara_certification'),
            "$join.recertifydatetype",
            array(
                'joins' => $join,
                'displayfunc' => 'certif_recertify_date_type',
            )
        );

        $columnoptions[] = new \rb_column_option(
            'certif',
            'activeperiod',
            get_string('activeperiod', 'totara_certification'),
            "$join.activeperiod",
            array('joins' => $join,
                  'dbdatatype' => 'char',
                  'outputformat' => 'text',
                  'displayfunc' => 'plaintext')
        );

        $columnoptions[] = new \rb_column_option(
            'certif',
            'windowperiod',
            get_string('windowperiod', 'totara_certification'),
            "$join.windowperiod",
            array('joins' => $join,
                  'dbdatatype' => 'char',
                  'outputformat' => 'text',
                  'displayfunc' => 'plaintext')
        );

        return true;
    }

    /**
     * Adds some common certification filters to the $filteroptions array
     *
     * @param array &$filteroptions Array of current filter options
     *                              Passed by reference and updated by
     *                              this method
     * @return bool always true
     */
    protected function add_totara_certification_filters(&$filteroptions) {
        $filteroptions[] = new \rb_filter_option(
            'certif',
            'fullname',
            get_string('programname', 'totara_certification'),
            'text'
        );
        $filteroptions[] = new \rb_filter_option(
            'certif',
            'shortname',
            get_string('programshortname', 'totara_certification'),
            'text'
        );
        $filteroptions[] = new \rb_filter_option(
            'certif',
            'idnumber',
            get_string('programidnumber', 'totara_certification'),
            'text'
        );
        $filteroptions[] = new \rb_filter_option(
            'certif',
            'summary',
            get_string('programsummary', 'totara_certification'),
            'textarea'
        );
        $filteroptions[] = new \rb_filter_option(
            'certif',
            'availablefrom',
            get_string('availablefrom', 'totara_certification'),
            'date'
        );
        $filteroptions[] = new \rb_filter_option(
            'certif',
            'availableuntil',
            get_string('availableuntil', 'totara_certification'),
            'date'
        );
        $filteroptions[] = new \rb_filter_option(
            'certif',
            'recertifydatetype',
            get_string('recertdatetype', 'totara_certification'),
            'select',
            array(
                'selectfunc' => 'recertifydatetype',
            )
        );
        $filteroptions[] = new \rb_filter_option(
            'certif',
            'activeperiod',
            get_string('activeperiod', 'totara_certification'),
            'text'
        );
        $filteroptions[] = new \rb_filter_option(
            'certif',
            'windowperiod',
            get_string('windowperiod', 'totara_certification'),
            'text'
        );
        return true;
    }
}
