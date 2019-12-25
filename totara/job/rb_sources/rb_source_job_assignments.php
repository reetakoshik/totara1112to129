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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

/**
 * A report builder source for the "job_assignment" table.
 */
class rb_source_job_assignments extends rb_base_source {
    /**
     * Constructor
     *
     * @param int $groupid (ignored)
     * @param rb_global_restriction_set $globalrestrictionset
     */
    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }

        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        $this->base = '{job_assignment}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = array();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_job_assignments');

        // Apply global report restrictions.
        $this->add_global_report_restriction_join('base', 'userid', 'base');

        parent::__construct();
    }

    /**
     * Are the global report restrictions implemented in the source?
     * @return null|bool
     */
    public function global_restrictions_supported() {
        return true;
    }

    protected function define_joinlist() {
        $joinlist = array(
            new rb_join(
                'pos',
                'LEFT',
                "{pos}",
                "pos.id = base.positionid"
            ),
            new rb_join(
                'postype',
                'LEFT',
                "{pos_type}",
                "postype.id = pos.typeid",
                null,
                'pos'
            ),
            new rb_join(
                'posframework',
                'LEFT',
                "{pos_framework}",
                "posframework.id = pos.frameworkid",
                null,
                'pos'
            ),
            new rb_join(
                'org',
                'LEFT',
                "{org}",
                "org.id = base.organisationid"
            ),
            new rb_join(
                'orgtype',
                'LEFT',
                "{org_type}",
                "orgtype.id = org.typeid",
                null,
                'org'
            ),
            new rb_join(
                'orgframework',
                'LEFT',
                "{org_framework}",
                "orgframework.id = org.frameworkid",
                null,
                'org'
            ),
            new rb_join(
                'managerja',
                'LEFT',
                "{job_assignment}",
                "managerja.id = base.managerjaid"
            ),
            new rb_join(
                'tempmanagerja',
                'LEFT',
                "{job_assignment}",
                "tempmanagerja.id = base.tempmanagerjaid"
            ),
        );

        $this->add_core_user_tables($joinlist, 'base', 'userid', 'auser');
        $this->add_core_user_tables($joinlist, 'managerja', 'userid', 'manager');
        $this->add_core_user_tables($joinlist, 'base', 'appraiserid', 'appraiser');
        $this->add_core_user_tables($joinlist, 'tempmanagerja', 'userid', 'tempmanager');

        return $joinlist;
    }

    protected function define_columnoptions() {
        $columnoptions = array();

        $columnoptions[] = new rb_column_option(
            'base',
            'fullname',
            get_string('jobassignmentfullname', 'totara_job'),
            "base.fullname",
            array(
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'displayfunc' => 'format_string'
            )
        );
        $columnoptions[] = new rb_column_option(
            'base',
            'shortname',
            get_string('jobassignmentshortname', 'totara_job'),
            "base.shortname",
            array(
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'displayfunc' => 'plaintext'
            )
        );
        $columnoptions[] = new rb_column_option(
            'base',
            'idnumber',
            get_string('jobassignmentidnumber', 'totara_job'),
            "base.idnumber",
            array(
                'dbdatatype' => 'char',
                'displayfunc' => 'plaintext',
                'outputformat' => 'text',
            )
        );
        $columnoptions[] = new rb_column_option(
            'base',
            'startdate',
            get_string('jobassignmentstartdate', 'totara_job'),
            "base.startdate",
            array(
                'displayfunc' => 'nice_datetime',
                'dbdatatype' => 'timestamp',
            )
        );
        $columnoptions[] = new rb_column_option(
            'base',
            'enddate',
            get_string('jobassignmentenddate', 'totara_job'),
            "base.enddate",
            array(
                'displayfunc' => 'nice_datetime',
                'dbdatatype' => 'timestamp',
            )
        );

        $this->add_core_user_columns($columnoptions, 'auser', 'user', false);

        $columnoptions[] = new rb_column_option(
            'pos',
            'idnumber',
            get_string('idnumber', 'rb_source_pos'),
            "pos.idnumber",
            array(
                'displayfunc' => 'plaintext',
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'addtypetoheading' => true,
                'joins' => array('pos'),
            )
        );
        $columnoptions[] = new rb_column_option(
            'pos',
            'fullname',
            get_string('name', 'rb_source_pos'),
            "pos.fullname",
            array(
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'addtypetoheading' => true,
                'joins' => array('pos'),
                'displayfunc' => 'format_string'
            )
        );
        $columnoptions[] = new rb_column_option(
            'pos',
            'shortname',
            get_string('shortname', 'rb_source_pos'),
            "pos.shortname",
            array(
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'addtypetoheading' => true,
                'joins' => array('pos'),
                'displayfunc' => 'plaintext'
            )
        );
        $columnoptions[] = new rb_column_option(
            'pos',
            'postypefullname',
            get_string('type', 'rb_source_pos'),
            'postype.fullname',
            array(
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'addtypetoheading' => true,
                'joins' => 'postype',
                'displayfunc' => 'format_string'
            )
        );
        $columnoptions[] = new rb_column_option(
            'pos',
            'postypeidnumber',
            get_string('typeidnumber', 'rb_source_pos'),
            'postype.idnumber',
            array(
                'displayfunc' => 'plaintext',
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'addtypetoheading' => true,
                'joins' => 'postype',
                'displayfunc' => 'plaintext'
            )
        );
        $columnoptions[] = new rb_column_option(
            'pos',
            'frameworkfullname',
            get_string('framework', 'rb_source_pos'),
            "posframework.fullname",
            array(
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'addtypetoheading' => true,
                'joins' => 'posframework',
                'displayfunc' => 'format_string'
            )
        );
        $columnoptions[] = new rb_column_option(
            'pos',
            'frameworkidnumber',
            get_string('frameworkidnumber', 'rb_source_pos'),
            "posframework.idnumber",
            array(
                'displayfunc' => 'plaintext',
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'addtypetoheading' => true,
                'joins' => 'posframework',
            )
        );
        $columnoptions[] = new rb_column_option(
            'pos',
            'visible',
            get_string('visible', 'rb_source_pos'),
            'pos.visible',
            array(
                'addtypetoheading' => true,
                'displayfunc' => 'yes_or_no',
            )
        );

        $columnoptions[] = new rb_column_option(
            'org',
            'idnumber',
            get_string('idnumber', 'rb_source_org'),
            "org.idnumber",
            array(
                'dbdatatype' => 'char',
                'displayfunc' => 'plaintext',
                'outputformat' => 'text',
                'addtypetoheading' => true,
                'joins' => array('org'),
            )
        );
        $columnoptions[] = new rb_column_option(
            'org',
            'fullname',
            get_string('name', 'rb_source_org'),
            "org.fullname",
            array(
                'extrafields' => array('orgid' => 'base.id'),
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'addtypetoheading' => true,
                'joins' => array('org'),
                'displayfunc' => 'format_string'
            )
        );
        $columnoptions[] = new rb_column_option(
            'org',
            'shortname',
            get_string('shortname', 'rb_source_org'),
            "org.shortname",
            array(
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'addtypetoheading' => true,
                'joins' => array('org'),
                'displayfunc' => 'format_string'
            )
        );
        $columnoptions[] = new rb_column_option(
            'org',
            'orgtypefullname',
            get_string('type', 'rb_source_org'),
            'orgtype.fullname',
            array(
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'addtypetoheading' => true,
                'joins' => 'orgtype',
                'displayfunc' => 'format_string'
            )
        );
        $columnoptions[] = new rb_column_option(
            'org',
            'orgtypeidnumber',
            get_string('typeidnumber', 'rb_source_org'),
            'orgtype.idnumber',
            array(
                'displayfunc' => 'plaintext',
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'addtypetoheading' => true,
                'joins' => 'orgtype',
            )
        );
        $columnoptions[] = new rb_column_option(
            'org',
            'frameworkfullname',
            get_string('framework', 'rb_source_org'),
            "orgframework.fullname",
            array(
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'addtypetoheading' => true,
                'joins' => 'orgframework',
                'displayfunc' => 'format_string'
            )
        );
        $columnoptions[] = new rb_column_option(
            'org',
            'frameworkidnumber',
            get_string('frameworkidnumber', 'rb_source_org'),
            "orgframework.idnumber",
            array(
                'displayfunc' => 'plaintext',
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'addtypetoheading' => true,
                'joins' => 'orgframework',
            )
        );
        $columnoptions[] = new rb_column_option(
            'org',
            'visible',
            get_string('visible', 'rb_source_org'),
            'org.visible',
            array(
                'addtypetoheading' => true,
                'displayfunc' => 'yes_or_no',
            )
        );

        $this->add_core_user_columns($columnoptions, 'manager', 'manager', true);
        $this->add_core_user_columns($columnoptions, 'appraiser', 'appraiser', true);
        $this->add_core_user_columns($columnoptions, 'tempmanager', 'tempmanager', true);

        $columnoptions[] = new rb_column_option(
            'tempmanager',
            'expirydate',
            get_string('tempmanagerexpirydate', 'totara_job'),
            "base.tempmanagerexpirydate",
            array(
                'displayfunc' => 'nice_datetime',
                'dbdatatype' => 'timestamp',
            )
        );

        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = array();

        $filteroptions[] = new rb_filter_option(
            'base',
            'fullname',
            get_string('jobassignmentfullname', 'totara_job'),
            'text'
        );
        $filteroptions[] = new rb_filter_option(
            'base',
            'shortname',
            get_string('jobassignmentshortname', 'totara_job'),
            'text'
        );
        $filteroptions[] = new rb_filter_option(
            'base',
            'idnumber',
            get_string('jobassignmentidnumber', 'totara_job'),
            'text'
        );
        $filteroptions[] = new rb_filter_option(
            'base',
            'startdate',
            get_string('jobassignmentstartdate', 'totara_job'),
            'date'
        );
        $filteroptions[] = new rb_filter_option(
            'base',
            'enddate',
            get_string('jobassignmentenddate', 'totara_job'),
            'date'
        );

        $this->add_core_user_filters($filteroptions, 'user', false);

        $filteroptions[] = new rb_filter_option(
            'pos',
            'idnumber',
            get_string('idnumber', 'rb_source_pos'),
            'text',
            array(
                'addtypetoheading' => true,
            )
        );
        $filteroptions[] = new rb_filter_option(
            'pos',
            'fullname',
            get_string('name', 'rb_source_pos'),
            'text',
            array(
                'addtypetoheading' => true,
            )
        );
        $filteroptions[] = new rb_filter_option(
            'pos',
            'shortname',
            get_string('shortname', 'rb_source_pos'),
            'text',
            array(
                'addtypetoheading' => true,
            )
        );
        $filteroptions[] = new rb_filter_option(
            'pos',
            'typeid',
            get_string('type', 'rb_source_pos'),
            'select',
            array(
                'addtypetoheading' => true,
                'selectfunc' => 'postypes',
                'attributes' => rb_filter_option::select_width_limiter(),
            ),
            'pos.typeid',
            'pos'
        );
        $filteroptions[] = new rb_filter_option(
            'pos',
            'frameworkid',
            get_string('framework', 'rb_source_pos'),
            'select',
            array(
                'addtypetoheading' => true,
                'selectfunc' => 'posframeworks',
                'attributes' => rb_filter_option::select_width_limiter(),
            ),
            'pos.frameworkid',
            'pos'
        );
        $filteroptions[] = new rb_filter_option(
            'pos',
            'visible',
            get_string('visible', 'rb_source_pos'),
            'multicheck',
            array(
                'addtypetoheading' => true,
                'simplemode' => true,
                'selectfunc' => 'yesno_list',
                'attributes' => rb_filter_option::select_width_limiter(),
            )
        );

        $filteroptions[] = new rb_filter_option(
            'org',
            'idnumber',
            get_string('idnumber', 'rb_source_org'),
            'text',
            array(
                'addtypetoheading' => true,
            )
        );
        $filteroptions[] = new rb_filter_option(
            'org',
            'fullname',
            get_string('name', 'rb_source_org'),
            'text',
            array(
                'addtypetoheading' => true,
            )
        );
        $filteroptions[] = new rb_filter_option(
            'org',
            'shortname',
            get_string('shortname', 'rb_source_org'),
            'text',
            array(
                'addtypetoheading' => true,
            )
        );
        $filteroptions[] = new rb_filter_option(
            'org',
            'typeid',
            get_string('type', 'rb_source_org'),
            'select',
            array(
                'addtypetoheading' => true,
                'selectfunc' => 'orgtypes',
                'attributes' => rb_filter_option::select_width_limiter(),
            ),
            'org.typeid',
            'org'
        );
        $filteroptions[] = new rb_filter_option(
            'org',
            'frameworkid',
            get_string('framework', 'rb_source_org'),
            'select',
            array(
                'addtypetoheading' => true,
                'selectfunc' => 'orgframeworks',
                'attributes' => rb_filter_option::select_width_limiter(),
            ),
            'org.frameworkid',
            'org'
        );
        $filteroptions[] = new rb_filter_option(
            'org',
            'visible',
            get_string('visible', 'rb_source_org'),
            'multicheck',
            array(
                'addtypetoheading' => true,
                'simplemode' => true,
                'selectfunc' => 'yesno_list',
                'attributes' => rb_filter_option::select_width_limiter(),
            )
        );

        $this->add_core_user_filters($filteroptions, 'manager', true);

        $this->add_core_user_filters($filteroptions, 'appraiser', true);

        $this->add_core_user_filters($filteroptions, 'tempmanager', true);

        return $filteroptions;
    }


    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'user',
                'value' => 'namelinkicon',
            ),
            array(
                'type' => 'base',
                'value' => 'fullname',
            ),
        );
        return $defaultcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = array(
        );

        return $defaultfilters;
    }

    public function rb_filter_postypes() {
        global $DB;

        $types = $DB->get_records('pos_type', null, 'fullname ASC', 'id, fullname');
        $list = array();
        foreach ($types as $type) {
            $list[$type->id] = $type->fullname;
        }
        return $list;
    }

    public function rb_filter_posframeworks() {
        global $DB;

        $frameworks = $DB->get_records('pos_framework', null, 'fullname ASC', 'id, fullname');
        $list = array();
        foreach ($frameworks as $framework) {
            $list[$framework->id] = $framework->fullname;
        }
        return $list;
    }

    public function rb_filter_orgtypes() {
        global $DB;

        $types = $DB->get_records('org_type', null, 'fullname ASC', 'id, fullname');
        $list = array();
        foreach ($types as $type) {
            $list[$type->id] = $type->fullname;
        }
        return $list;
    }

    public function rb_filter_orgframeworks() {
        global $DB;

        $frameworks = $DB->get_records('org_framework', null, 'fullname ASC', 'id, fullname');
        $list = array();
        foreach ($frameworks as $framework) {
            $list[$framework->id] = $framework->fullname;
        }
        return $list;
    }
}
