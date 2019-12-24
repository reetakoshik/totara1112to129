<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
 * Copyright (C) 1999 onwards Martin Dougiamas
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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package tool_customlang
 */

defined('MOODLE_INTERNAL') || die();

class rb_source_customlang extends rb_base_source {
    public $base, $joinlist, $columnoptions, $filteroptions;
    public $contentoptions, $paramoptions, $defaultcolumns;
    public $defaultfilters, $requiredcolumns, $sourcetitle;

    function __construct() {
        $this->base = '(SELECT *
            FROM {tool_customlang}
            WHERE local IS NOT NULL)';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->defaulttoolbarsearchcolumns = $this->define_defaultsearchcolumns();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_customlang');

        parent::__construct();
    }

    //
    //
    // Methods for defining contents of source
    //
    //

    protected function define_joinlist() {

        $joinlist = array(
            new rb_join(
                'customlang_component',
                'LEFT',
                '{tool_customlang_components}',
                'base.componentid = customlang_component.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
        );

        return $joinlist;
    }

    protected function define_columnoptions() {
        $columnoptions = array(
            new rb_column_option(
                'customlang',
                'lang',
                get_string('lang', 'rb_source_customlang'),
                "base.lang",
                array(
                    'dbdatatype' => 'char',
                    'outputformat' => 'text'
                )
            ),
            new rb_column_option(
                'customlang',
                'stringid',
                get_string('stringid', 'rb_source_customlang'),
                "base.stringid",
                array(
                    'dbdatatype' => 'char',
                    'outputformat' => 'text'
                )
            ),
            new rb_column_option(
                'customlang',
                'original',
                get_string('original', 'rb_source_customlang'),
                "base.original",
                array(
                    'dbdatatype' => 'char',
                    'outputformat' => 'text'
                )
            ),
            new rb_column_option(
                'customlang',
                'local',
                get_string('local', 'rb_source_customlang'),
                "base.local",
                array(
                    'dbdatatype' => 'char',
                    'outputformat' => 'text'
                )
            ),
            new rb_column_option(
                'customlang',
                'timecustomised',
                get_string('timecustomised', 'rb_source_customlang'),
                "base.timecustomized",
                array('displayfunc' => 'nice_datetime')
            ),
            new rb_column_option(
                'customlang_component',
                'name',
                get_string('component', 'rb_source_customlang'),
                "customlang_component.name",
                array('joins' => 'customlang_component')
            ),
            new rb_column_option(
                'customlang_component',
                'version',
                get_string('componentversion', 'rb_source_customlang'),
                "customlang_component.version",
                array('joins' => 'customlang_component')
            ),
        );

        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = array(
            new rb_filter_option(
                'customlang',
                'lang',
                get_string('lang', 'rb_source_customlang'),
                'select',
                array(            // options
                    'selectfunc' => 'lang_list',
                    'attributes' => rb_filter_option::select_width_limiter(),
                )
            ),
            new rb_filter_option(
                'customlang',
                'stringid',
                get_string('stringid', 'rb_source_customlang'),
                'text',
                array()
            ),
            new rb_filter_option(
                'customlang',
                'timecustomised',
                get_string('timecustomised', 'rb_source_customlang'),
                'date',
                array()
            ),
            new rb_filter_option(
                'customlang_component',
                'name',
                get_string('component', 'rb_source_customlang'),
                'text',
                array()
            ),
        );

        return $filteroptions;
    }

    protected function define_contentoptions() {
        $contentoptions = array();

        return $contentoptions;
    }

    protected function define_paramoptions() {
        $paramoptions = array();

        return $paramoptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'customlang',
                'value' => 'lang',
            ),
            array(
                'type' => 'customlang_component',
                'value' => 'name',
            ),
            array(
                'type' => 'customlang',
                'value' => 'stringid',
            ),
            array(
                'type' => 'customlang',
                'value' => 'original',
            ),
            array(
                'type' => 'customlang',
                'value' => 'local',
            ),
        );
        return $defaultcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = array(
            array(
                'type' => 'customlang',
                'value' => 'lang',
                'advanced' => 0,
            ),
            array(
                'type' => 'customlang_component',
                'value' => 'name',
                'advanced' => 0,
            ),
            array(
                'type' => 'customlang',
                'value' => 'stringid',
                'advanced' => 0,
            )
        );

        return $defaultfilters;
    }

    protected function define_defaultsearchcolumns() {
        $defaultsearchcolumns = array(
            array(
                'type' => 'customlang',
                'value' => 'original',
            ),
            array(
                'type' => 'customlang',
                'value' => 'local',
            ),
            array(
                'type' => 'customlang',
                'value' => 'stringid',
            ),
        );

        return $defaultsearchcolumns;
    }

    protected function define_requiredcolumns() {
        return array();
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return false;
    }


    //
    //
    // Source specific helper functions
    //
    //

    function rb_filter_lang_list() {
        global $DB;

        $langs = $DB->get_records('tool_customlang', array(), 'lang', 'DISTINCT lang');
        $return = array();
        foreach ($langs as $code => $lang) {
            $return[$code] = $this->rb_display_language_code($code, array())." ({$code})";
        }

        return $return;
    }
} // End of rb_source_customlang class.
