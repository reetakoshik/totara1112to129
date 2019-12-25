<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

class rb_source_upgrade_log extends rb_base_source {
    public $base, $joinlist, $columnoptions, $filteroptions;
    public $contentoptions, $paramoptions, $defaultcolumns;
    public $defaultfilters, $requiredcolumns, $sourcetitle;

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $this->add_global_report_restriction_join('base', 'userid', 'auser');

        $this->base = '{upgrade_log}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_upgrade_log');

        parent::__construct();
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return true;
    }

    protected function define_joinlist() {
        $joinlist = array();

        $this->add_user_table_to_joinlist($joinlist, 'base', 'userid');

        return $joinlist;
    }

    protected function define_columnoptions() {
        $columnoptions = array(
            new rb_column_option(
                'upgrade_log',
                'type',
                get_string('coltype', 'rb_source_upgrade_log'),
                'base.type',
                array('displayfunc' => 'upgradelogtype')
            ),
            new rb_column_option(
                'upgrade_log',
                'plugin',
                get_string('colplugin', 'rb_source_upgrade_log'),
                'base.plugin',
                array('dbdatatype' => 'char', 'outputformat' => 'text')
            ),
            new rb_column_option(
                'upgrade_log',
                'version',
                get_string('colversion', 'rb_source_upgrade_log'),
                'base.version',
                array()
            ),
            new rb_column_option(
                'upgrade_log',
                'targetversion',
                get_string('coltargetversion', 'rb_source_upgrade_log'),
                'base.targetversion',
                array()
            ),
            new rb_column_option(
                'upgrade_log',
                'info',
                get_string('colinfo', 'rb_source_upgrade_log'),
                'base.info',
                array('dbdatatype' => 'char', 'outputformat' => 'text')
            ),
            new rb_column_option(
                'upgrade_log',
                'details',
                get_string('coldetails', 'rb_source_upgrade_log'),
                'base.details',
                array('dbdatatype' => 'text')
            ),
            new rb_column_option(
                'upgrade_log',
                'backtrace',
                get_string('colbacktrace', 'rb_source_upgrade_log'),
                'base.backtrace',
                array('displayfunc' => 'backtrace', 'dbdatatype' => 'text')
            ),
            new rb_column_option(
                'upgrade_log',
                'timemodified',
                get_string('coltimemodified', 'rb_source_upgrade_log'),
                'base.timemodified',
                array('displayfunc' => 'nice_datetime', 'dbdatatype' => 'timestamp')
            ),
        );

        $this->add_user_fields_to_columns($columnoptions);

        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = array();

        $filteroptions[] = new rb_filter_option(
            'upgrade_log',
            'timemodified',
            get_string('coltimemodified', 'rb_source_upgrade_log'),
            'date',
            array(
                'includetime' => true
            )
        );

        $filteroptions[] = new rb_filter_option(
            'upgrade_log',
            'type',
            get_string('coltype', 'rb_source_upgrade_log'),
            'select',
            array(
                'selectchoices' => array(0 => get_string('normal'), 1 => get_string('notice'), 2 => get_string('error')),
                'simplemode' => true
            )
        );

        $filteroptions[] = new rb_filter_option(
            'upgrade_log',
            'plugin',
            get_string('colplugin', 'rb_source_upgrade_log'),
            'text',
            array()
        );

        $filteroptions[] = new rb_filter_option(
            'upgrade_log',
            'version',
            get_string('colversion', 'rb_source_upgrade_log'),
            'text',
            array()
        );

        $filteroptions[] = new rb_filter_option(
            'upgrade_log',
            'targetversion',
            get_string('coltargetversion', 'rb_source_upgrade_log'),
            'text',
            array()
        );

        $filteroptions[] = new rb_filter_option(
            'upgrade_log',
            'info',
            get_string('colinfo', 'rb_source_upgrade_log'),
            'text',
            array()
        );

        $filteroptions[] = new rb_filter_option(
            'upgrade_log',
            'details',
            get_string('coldetails', 'rb_source_upgrade_log'),
            'text',
            array()
        );

        $filteroptions[] = new rb_filter_option(
            'upgrade_log',
            'backtrace',
            get_string('colbacktrace', 'rb_source_upgrade_log'),
            'text',
            array()
        );

        $this->add_user_fields_to_filters($filteroptions);

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
                'type' => 'upgrade_log',
                'value' => 'timemodified'
            ),
            array(
                'type' => 'upgrade_log',
                'value' => 'plugin'
            ),
            array(
                'type' => 'upgrade_log',
                'value' => 'type'
            ),
            array(
                'type' => 'upgrade_log',
                'value' => 'version'
            ),
            array(
                'type' => 'upgrade_log',
                'value' => 'targetversion'
            ),
            array(
                'type' => 'upgrade_log',
                'value' => 'info'
            ),
            array(
                'type' => 'upgrade_log',
                'value' => 'details'
            ),
            array(
                'type' => 'upgrade_log',
                'value' => 'backtrace'
            ),
        );
        return $defaultcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = array(
            array(
                'type' => 'upgrade_log',
                'value' => 'type',
                'advanced' => 0,
            ),
            array(
                'type' => 'upgrade_log',
                'value' => 'plugin',
                'advanced' => 0,
            ),
            array(
                'type' => 'upgrade_log',
                'value' => 'timemodified',
                'advanced' => 1,
            ),
            array(
                'type' => 'upgrade_log',
                'value' => 'details',
                'advanced' => 1,
            ),
        );

        return $defaultfilters;
    }

    public function rb_display_backtrace($value, $row, $isexport = false) {
        if ($value === '' or $value === null) {
            return '';
        }
        if ($isexport) {
            return \core_text::entities_to_utf8($value);
        }

        return '<pre>' . s($value) . '</pre>';
    }

    public function rb_display_upgradelogtype($value, $row, $isexport = false) {
        if ($value == 0) {
            return get_string('normal');
        } else if ($value == 1) {
            return get_string('notice');
        } else {
            return get_string('error');
        }
    }
}
