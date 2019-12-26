<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @package auth_connect
 */

defined('MOODLE_INTERNAL') || die();

/**
 * TC clients.
 */
class rb_source_connect_clients extends rb_base_source {
    public function __construct() {
        $this->usedcomponents[] = 'totara_connect';
        $this->base = '{totara_connect_clients}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = array();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_connect_clients');

        parent::__construct();
    }

    /**
     * Hide this source if TC not enabled.
     * @return bool
     */
    public static function is_source_ignored() {
        global $CFG;
        return empty($CFG->enableconnectserver);
    }

    /**
     * There is no user data here.
     * @return null|bool
     */
    public function global_restrictions_supported() {
        return false;
    }

    protected function define_joinlist() {
        $joinlist = array();
        return $joinlist;
    }

    protected function define_columnoptions() {
        $columnoptions = array();

        $columnoptions[] = new rb_column_option(
            'connect_clients',
            'status',
            get_string('status'),
            'base.status',
            array(
                'dbdatatype' => 'bool',
                'displayfunc' => 'client_status',
            )
        );

        $columnoptions[] = new rb_column_option(
            'connect_clients',
            'clientidnumber',
            get_string('idnumber'),
            'base.clientidnumber',
            array(
                'dbdatatype' => 'char',
                'displayfunc' => 'plaintext',
                'outputformat' => 'text',
            )
        );

        $columnoptions[] = new rb_column_option(
            'connect_clients',
            'clienturl',
            get_string('url'),
            'base.clienturl',
            array(
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'extrafields' => array('status' => "base.status"),
                'displayfunc' => 'client_url',
            )
        );

        $columnoptions[] = new rb_column_option(
            'connect_clients',
            'clientname',
            get_string('name'),
            'base.clientname',
            array(
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'extrafields' => array('client_id' => "base.id", 'client_status' => "base.status"),
                'displayfunc' => 'client_name',
                )
        );

        $columnoptions[] = new rb_column_option(
            'connect_clients',
            'cohorts',
            get_string('cohorts', 'totara_connect'),
            '(SELECT COUNT(1) FROM {totara_connect_client_cohorts} WHERE clientid = base.id)',
            array(
                'outputformat' => 'text',
                'extrafields' => array('client_id' => "base.id"),
                'displayfunc' => 'client_cohorts',
            )
        );

        $columnoptions[] = new rb_column_option(
            'connect_clients',
            'courses',
            get_string('courses', 'totara_connect'),
            '(SELECT COUNT(1) FROM {totara_connect_client_courses} WHERE clientid = base.id)',
            array(
                'outputformat' => 'text',
                'extrafields' => array('client_id' => "base.id"),
                'displayfunc' => 'client_courses',
            )
        );

        $columnoptions[] = new rb_column_option(
            'connect_clients',
            'timecreated',
            get_string('timecreated', 'totara_connect'),
            'base.timecreated',
            array(
                'displayfunc' => 'nice_datetime',
                'dbdatatype' => 'timestamp',
            )
        );

        $columnoptions[] = new rb_column_option(
            'connect_clients',
            'timemodified',
            get_string('timemodified', 'totara_connect'),
            'base.timemodified',
            array(
                'displayfunc' => 'nice_datetime',
                'dbdatatype' => 'timestamp',
            )
        );

        $columnoptions[] = new rb_column_option(
            'connect_clients',
            'clientcomment',
            get_string('comment', 'totara_connect'),
            'base.clientcomment',
            array(
                'dbdatatype' => 'text',
                'outputformat' => 'text',
            )
        );

        return $columnoptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array('type' => 'connect_clients', 'value' => 'clientname'),
            array('type' => 'connect_clients', 'value' => 'clientidnumber'),
            array('type' => 'connect_clients', 'value' => 'clienturl'),
            array('type' => 'connect_clients', 'value' => 'cohorts'),
            array('type' => 'connect_clients', 'value' => 'courses'),
            array('type' => 'connect_clients', 'value' => 'clientcomment'),
            array('type' => 'connect_clients', 'value' => 'timecreated'),
            array('type' => 'connect_clients', 'value' => 'status'),
        );
        return $defaultcolumns;
    }

    protected function define_filteroptions() {
        $filteroptions = array();
        return $filteroptions;
    }

    protected function define_defaultfilters() {
        $defaultfilters = array();
        return $defaultfilters;
    }
}
