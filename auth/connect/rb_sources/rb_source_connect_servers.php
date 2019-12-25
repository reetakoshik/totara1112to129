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

class rb_source_connect_servers extends rb_base_source {
    public function __construct() {
        $this->usedcomponents[] = 'auth_connect';
        $this->base = '{auth_connect_servers}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = array();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_connect_servers');

        parent::__construct();
    }

    /**
     * Hide this source if TC auth not enabled.
     * @return bool
     */
    public static function is_source_ignored() {
        return !is_enabled_auth('connect');
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
            'connect_servers',
            'status',
            get_string('status'),
            'base.status',
            array(
                'dbdatatype' => 'bool',
                'displayfunc' => 'server_status',
            )
        );

        $columnoptions[] = new rb_column_option(
            'connect_servers',
            'serveridnumber',
            get_string('idnumber'),
            'base.serveridnumber',
            array(
                'displayfunc' => 'plaintext',
                'dbdatatype' => 'char',
                'outputformat' => 'text',
            )
        );

        $columnoptions[] = new rb_column_option(
            'connect_servers',
            'serverurl',
            get_string('url'),
            'base.serverurl',
            array(
                'dbdatatype' => 'char',
                'outputformat' => 'text',
            )
        );

        $columnoptions[] = new rb_column_option(
            'connect_servers',
            'servername',
            get_string('name'),
            'base.servername',
            array(
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'extrafields' => array('server_id' => "base.id", 'server_status' => "base.status", 'server_serveridnumber' => "base.serveridnumber"),
                'displayfunc' => 'server_name',
            )
        );

        $columnoptions[] = new rb_column_option(
            'connect_servers',
            'timecreated',
            get_string('timecreated', 'auth_connect'),
            'base.timecreated',
            array(
                'displayfunc' => 'nice_datetime',
                'dbdatatype' => 'timestamp',
            )
        );

        $columnoptions[] = new rb_column_option(
            'connect_servers',
            'timemodified',
            get_string('timecreated', 'auth_connect'),
            'base.timemodified',
            array(
                'displayfunc' => 'nice_datetime',
                'dbdatatype' => 'timestamp',
            )
        );

        $columnoptions[] = new rb_column_option(
            'connect_servers',
            'servercomment',
            get_string('comment', 'auth_connect'),
            'base.servercomment',
            array(
                'dbdatatype' => 'text',
                'outputformat' => 'text',
            )
        );

        return $columnoptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array('type' => 'connect_servers', 'value' => 'servername'),
            array('type' => 'connect_servers', 'value' => 'serveridnumber'),
            array('type' => 'connect_servers', 'value' => 'serverurl'),
            array('type' => 'connect_servers', 'value' => 'servercomment'),
            array('type' => 'connect_servers', 'value' => 'timecreated'),
            array('type' => 'connect_servers', 'value' => 'status'),
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
