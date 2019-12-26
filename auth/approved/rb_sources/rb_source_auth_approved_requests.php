<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author Andrew Bell <andrewb@learningpool.com>
 * @author Ryan Lynch <ryanlynch@learningpool.com>
 * @author Barry McKay <barry@learningpool.com>
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 *
 * @package auth_approved
 */

defined('MOODLE_INTERNAL') || die();

class rb_source_auth_approved_requests extends rb_base_source {
    public function __construct() {
        $this->usedcomponents[] = 'auth_approved';
        $this->base = '{auth_approved_request}';
        $this->sourcetitle = get_string('reportrequests', 'auth_approved');

        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();

        // Add the created user info.
        $this->add_core_user_tables($this->joinlist, 'base', 'userid', 'auser');
        $this->add_core_user_columns($this->columnoptions, 'auser', 'user', true);
        $this->add_core_user_filters($this->filteroptions, 'user', true);

        // NOTE: we cannot add more user type columns in Totara 9 - see TL-12609 for more info.

        // No caching, we always need the latest data!
        $this->cacheable = false;

        parent::__construct();
    }

    protected function define_joinlist() {
        return array(
            new rb_join(
                'position',
                'LEFT',
                '{pos}',
                'base.positionid = position.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'position_framework',
                'LEFT',
                '{pos_framework}',
                'position.frameworkid = position_framework.id',
                REPORT_BUILDER_RELATION_ONE_TO_MANY,
                array('position')
            ),
            new rb_join(
                'organisation',
                'LEFT',
                '{org}',
                'base.organisationid = organisation.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'organisation_framework',
                'LEFT',
                '{org_framework}',
                'organisation.frameworkid = organisation_framework.id',
                REPORT_BUILDER_RELATION_ONE_TO_MANY,
                array('organisation')
            ),
            new rb_join(
                'manager_job',
                'LEFT',
                '{job_assignment}',
                'base.managerjaid = manager_job.id',
                REPORT_BUILDER_RELATION_ONE_TO_MANY
            ),
            new rb_join(
                'manager',
                'LEFT',
                '{user}',
                'manager.id = manager_job.userid',
                REPORT_BUILDER_RELATION_ONE_TO_MANY,
                array('manager_job')
            ),
        );
    }

    protected function define_columnoptions() {
        return array(
            new rb_column_option(
                'request',
                'status',
                get_string('requeststatus', 'auth_approved'),
                "base.status",
                array(
                    'displayfunc' => 'request_status',
                )
            ),
            new rb_column_option(
                'request',
                'firstname',
                get_string('userfirstname', 'totara_reportbuilder'),
                "base.firstname",
                array(
                    'displayfunc' => 'plaintext',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                )
            ),
            new rb_column_option(
                'request',
                'lastname',
                get_string('userlastname', 'totara_reportbuilder'),
                "base.lastname",
                array(
                    'displayfunc' => 'plaintext',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                )
            ),
            new rb_column_option(
                'request',
                'username',
                get_string('username', 'totara_reportbuilder'),
                "base.username",
                array(
                    'displayfunc' => 'plaintext',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                )
            ),
            new rb_column_option(
                'request',
                'email',
                get_string('useremail', 'totara_reportbuilder'),
                "base.email",
                array(
                    'displayfunc' => 'plaintext',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                )
            ),
            new rb_column_option(
                'request',
                'city',
                get_string('usercity', 'totara_reportbuilder'),
                "base.city",
                array(
                    'displayfunc' => 'plaintext',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                )
            ),
            new rb_column_option(
                'request',
                'country',
                get_string('usercountry', 'totara_reportbuilder'),
                "base.country",
                array(
                    'displayfunc' => 'country_code',
                )
            ),
            new rb_column_option(
                'request',
                'lang',
                get_string('userlang', 'totara_reportbuilder'),
                "base.lang",
                array(
                    'displayfunc' => 'language_code',
                )
            ),
            new rb_column_option(
                'request',
                'confirmed',
                get_string('confirmed', 'auth_approved'),
                "base.confirmed",
                array(
                    'displayfunc' => 'yes_or_no',
                    'dbdatatype' => 'bool',
                )
            ),
            new rb_column_option(
                'organisation',
                'framework',
                get_string('organisationframework', 'totara_hierarchy'),
                'organisation_framework.fullname',
                array(
                    'joins' => array('organisation_framework'),
                    'displayfunc' => 'format_string'
                )
            ),
            new rb_column_option(
                'organisation',
                'fullname',
                get_string('organisation', 'totara_job'),
                'organisation.fullname',
                array(
                    'joins' => array('organisation'),
                    'displayfunc' => 'format_string'
                )
            ),
            new rb_column_option(
                'request',
                'organisationfreetext',
                get_string('organisationfreetext', 'auth_approved'),
                'base.organisationfreetext',
                array(
                    'displayfunc' => 'plaintext',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                )
            ),
            new rb_column_option(
                'position',
                'framework',
                get_string('positionframework', 'totara_hierarchy'),
                'position_framework.fullname',
                array(
                    'joins' => array('position_framework'),
                    'displayfunc' => 'format_string'
                )
            ),
            new rb_column_option(
                'position',
                'fullname',
                get_string('position', 'totara_job'),
                'position.fullname',
                array(
                    'joins' => array('position'),
                    'displayfunc' => 'format_string'
                )
            ),
            new rb_column_option(
                'request',
                'positionfreetext',
                get_string('positionfreetext', 'auth_approved'),
                'base.positionfreetext',
                array(
                    'displayfunc' => 'plaintext',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                )
            ),
            new rb_column_option(
                'request',
                'manager',
                get_string('manager', 'totara_job'),
                'base.managerjaid',
                array(
                    'nosort' => true,
                    'displayfunc' => 'request_manager',
                    'extrafields' => array('userid' => "manager.id",
                        'jobidnumber' => "manager_job.idnumber", 'jobfullname' => "manager_job.fullname"),
                    'joins' => array('manager_job', 'manager')
                )
            ),
            new rb_column_option(
                'request',
                'managerfreetext',
                get_string('managerfreetext', 'auth_approved'),
                'base.managerfreetext',
                array(
                    'displayfunc' => 'plaintext',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                )
            ),
            new rb_column_option(
                'request',
                'timecreated',
                get_string('requesttimecreated', 'auth_approved'),
                'base.timecreated',
                array(
                    'displayfunc' => 'nice_datetime'
                )
            ),
            new rb_column_option(
                'request',
                'timemodified',
                get_string('requesttimemodified', 'auth_approved'),
                'base.timemodified',
                array(
                    'displayfunc' => 'nice_datetime'
                )
            ),
            new rb_column_option(
                'request',
                'timeresolved',
                get_string('requesttimeresolved', 'auth_approved'),
                'base.timeresolved',
                array(
                    'displayfunc' => 'nice_datetime'
                )
            ),
            new rb_column_option(
                'request',
                'profilefields',
                get_string('profilefields', 'auth_approved'),
                'base.profilefields',
                array(
                    'displayfunc'  => 'request_profilefields',
                    'outputformat' => 'text',
                    'nosort'       => true,
                )
            ),
            new rb_column_option(
                'request',
                'actions',
                get_string('actions', 'auth_approved'),
                'base.id',
                array(
                    'displayfunc' => 'request_actions',
                    'noexport' => true,
                    'nosort' => true,
                    'capability' => 'auth/approved:approve',
                    'extrafields' => array('status' => "base.status"),
                )
            ),
        );
    }

    protected function define_filteroptions() {
        return array(
            new rb_filter_option(
                'request',
                'status',
                get_string('requeststatus', 'auth_approved'),
                'multicheck',
                array(
                    'selectchoices' => \auth_approved\request::get_statuses(),
                    'simplemode' => true
                )
            ),
            new rb_filter_option(
                'request',
                'firstname',
                get_string('userfirstname', 'totara_reportbuilder'),
                'text'
            ),
            new rb_filter_option(
                'request',
                'lastname',
                get_string('userlastname', 'totara_reportbuilder'),
                'text'
            ),
            new rb_filter_option(
                'request',
                'username',
                get_string('username', 'totara_reportbuilder'),
                'text'
            ),
            new rb_filter_option(
                'request',
                'email',
                get_string('useremail', 'totara_reportbuilder'),
                'text'
            ),
            new rb_filter_option(
                'request',
                'city',
                get_string('usercity', 'totara_reportbuilder'),
                'text'
            ),
            new rb_filter_option(
                'request',
                'country',
                get_string('usercountry', 'totara_reportbuilder'),
                'select',
                array(
                    'selectchoices' => get_string_manager()->get_list_of_countries(),
                    'attributes' => rb_filter_option::select_width_limiter(),
                    'simplemode' => true,
                )
            ),
            new rb_filter_option(
                'request',
                'confirmed',
                get_string('confirmed', 'auth_approved'),
                'multicheck',
                array(
                    'selectchoices' => array('0' => get_string('no'), '1' => get_string('yes')),
                    'simplemode' => true
                )
            ),
            new rb_filter_option(
                'request',
                'positionfreetext',
                get_string('positionfreetext', 'auth_approved'),
                'text'
            ),
            new rb_filter_option(
                'request',
                'organisationfreetext',
                get_string('organisationfreetext', 'auth_approved'),
                'text'
            ),
            new rb_filter_option(
                'request',
                'managerfreetext',
                get_string('managerfreetext', 'auth_approved'),
                'text'
            ),
            new rb_filter_option(
                'request',
                'timecreated',
                get_string('requesttimecreated', 'auth_approved'),
                'date',
                array(
                    'includetime' => true,
                )
            ),
            new rb_filter_option(
                'request',
                'timemodified',
                get_string('requesttimemodified', 'auth_approved'),
                'date',
                array(
                    'includetime' => true,
                )
            ),
            new rb_filter_option(
                'request',
                'timeresolved',
                get_string('requesttimeresolved', 'auth_approved'),
                'date',
                array(
                    'includetime' => true,
                )
            ),
        );
    }

    protected function define_defaultcolumns() {
        return array(
            array(
                'type' => 'request',
                'value' => 'status'
            ),
            array(
                'type' => 'request',
                'value' => 'firstname'
            ),
            array(
                'type' => 'request',
                'value' => 'lastname'
            ),
            array(
                'type' => 'request',
                'value' => 'username'
            ),
            array(
                'type' => 'request',
                'value' => 'email'
            ),
            array(
                'type' => 'request',
                'value' => 'confirmed'
            ),
            array(
                'type' => 'request',
                'value' => 'timecreated'
            ),
            array(
                'type' => 'request',
                'value' => 'actions'
            ),
        );
    }

    protected function define_defaultfilters() {
        return array(
            array(
                'type' => 'request',
                'value' => 'status'
            ),
            array(
                'type' => 'request',
                'value' => 'confirmed'
            ),
        );
    }

    protected function define_paramoptions() {
        $paramoptions = array(
            new rb_param_option('status', 'base.status'),
            new rb_param_option('requestid', 'base.id'),
        );

        return $paramoptions;
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return false;
    }

    /**
     * Returns expected result for column_test.
     * @param rb_column_option $columnoption
     * @return int
     */
    public function phpunit_column_test_expected_count($columnoption) {
        if (!PHPUNIT_TEST) {
            throw new coding_exception('phpunit_column_test_expected_count() cannot be used outside of unit tests');
        }
        return 0;
    }
}
