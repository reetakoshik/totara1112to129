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
 * @author Russell England <russell.england@catalyst-eu.net>
 * @package totara
 * @subpackage reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/certification/lib.php');

/**
 * A report builder source for Certifications
 */
class rb_source_dp_certification_history extends rb_base_source {
    use \core_course\rb\source\report_trait;
    use \totara_job\rb\source\report_trait;

    /**
     * Constructor
     */
    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        global $DB;
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $global_restriction_join_cc = $this->get_global_report_restriction_join('cc', 'userid');
        $global_restriction_join_cch = $this->get_global_report_restriction_join('cch', 'userid');

        $activeunique = $DB->sql_concat("'active'", 'cc.id');
        $historyunique = $DB->sql_concat("'history'", 'cch.id');
        $sql = '(SELECT ' . $activeunique . ' AS id,
                1 AS active,
                cc.id AS completionid,
                certifid,
                userid,
                certifpath,
                status,
                renewalstatus,
                timewindowopens,
                timecompleted,
                timeexpires
                FROM {certif_completion} cc
                ' . $global_restriction_join_cc . '
                UNION
                SELECT ' . $historyunique . ' AS id,
                0 AS active,
                cch.id AS completionid,
                certifid,
                userid,
                certifpath,
                status,
                renewalstatus,
                timewindowopens,
                timecompleted,
                timeexpires
                FROM {certif_completion_history} cch
                ' . $global_restriction_join_cch . '
                WHERE unassigned = 0)';
        $this->base = $sql;
        $this->joinlist = $this->define_joinlist();
        $this->usedcomponents[] = 'totara_certification';
        $this->usedcomponents[] = 'totara_program';
        $this->usedcomponents[] = 'totara_cohort';
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = array();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_dp_certification_history');

        parent::__construct();
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return true;
    }

    //
    //
    // Methods for defining contents of source
    //
    //

    /**
     * Creates the array of rb_join objects required for this->joinlist
     *
     * @global object $CFG
     * @return array
     */
    protected function define_joinlist() {
        global $CFG;

        $joinlist = array();

        // to get access to position type constants
        require_once($CFG->dirroot . '/totara/reportbuilder/classes/rb_join.php');

        $joinlist[] = new rb_join(
                'prog',
                'LEFT',
                '{prog}',
                'prog.certifid = base.certifid',
                REPORT_BUILDER_RELATION_ONE_TO_MANY,
                array('base')
        );

        $joinlist[] = new rb_join(
                'prog_completion', // Table alias.
                'LEFT', // Type of join.
                '{prog_completion}',
                '(prog_completion.programid = prog.id
                    AND prog_completion.coursesetid = 0
                    AND prog_completion.userid = base.userid)',
                REPORT_BUILDER_RELATION_ONE_TO_MANY,
                array('base')
        );

        $joinlist[] =  new rb_join(
                'completion_organisation',
                'LEFT',
                '{org}',
                'completion_organisation.id = prog_completion.organisationid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                array('prog', 'prog_completion')
        );

        $this->add_core_user_tables($joinlist, 'base', 'userid');
        $this->add_totara_job_tables($joinlist, 'base', 'userid');
        $this->add_core_course_category_tables($joinlist, 'prog', 'category');

        return $joinlist;
    }


    /**
     * Creates the array of rb_column_option objects required for
     * $this->columnoptions
     *
     * @return array
     */
    protected function define_columnoptions() {
        $columnoptions = array();

        $columnoptions[] = new rb_column_option(
                'prog',
                'fullname',
                get_string('certificationname', 'totara_program'),
                'prog.fullname',
                array(
                    'joins' => 'prog',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string'
                )
        );

        $columnoptions[] = new rb_column_option(
                'prog',
                'fullnamelink',
                get_string('certificationname', 'totara_program'),
                "prog.fullname",
                array(
                    'joins' => 'prog',
                    'defaultheading' => get_string('certificationname', 'totara_program'),
                    'displayfunc' => 'program_icon_link',
                    'extrafields' => array(
                        'programid' => 'prog.id',
                        'userid' => 'base.userid',
                    ),
                )
        );

        $columnoptions[] = new rb_column_option(
                'base',
                'active',
                get_string('current', 'rb_source_dp_certification_history'),
                'base.active',
                array(
                    'displayfunc' => 'yes_or_no',
                )
        );

        $columnoptions[] = new rb_column_option(
                'prog',
                'shortname',
                get_string('programshortname', 'totara_program'),
                'prog.shortname',
                array(
                    'joins' => 'prog',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'plaintext'
                )
        );

        $columnoptions[] = new rb_column_option(
                'prog',
                'idnumber',
                get_string('programidnumber', 'totara_program'),
                'prog.idnumber',
                array(
                    'joins' => 'prog',
                    'displayfunc' => 'plaintext',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text'
                )
        );

        $columnoptions[] = new rb_column_option(
                'base',
                'certifid',
                get_string('certificationid', 'rb_source_dp_certification'),
                'base.certifid',
                array('displayfunc' => 'integer')
        );

        $columnoptions[] = new rb_column_option(
                'base',
                'timecompleted',
                get_string('timecompleted', 'rb_source_dp_certification'),
                'base.timecompleted',
                array(
                    'displayfunc' => 'nice_date',
                    'dbdatatype' => 'timestamp'
                )
        );

        $columnoptions[] = new rb_column_option(
                'base',
                'timeexpires',
                get_string('timeexpires', 'rb_source_dp_certification'),
                'base.timeexpires',
                array(
                    'displayfunc' => 'nice_date',
                    'dbdatatype' => 'timestamp'
                )
        );

        $columnoptions[] = new rb_column_option(
            'base',
            'status',
            get_string('status', 'rb_source_dp_certification_history'),
            'base.status',
            array(
                'displayfunc' => 'certif_status',
                'dbdatatype' => 'integer',
                'extrafields' => array(
                    'active' => 'base.active'
                )
            )
        );

        $columnoptions[] = new rb_column_option(
            'base',
            'renewalstatus',
            get_string('renewalstatus', 'rb_source_dp_certification_history'),
            'base.renewalstatus',
            array(
                'displayfunc' => 'certif_renewalstatus',
                'dbdatatype' => 'integer',
                'extrafields' => array(
                    'status' => 'base.status',
                    'active' => 'base.active'
                )
            )
        );

        $columnoptions[] = new rb_column_option(
            'base',
            'progress',
            get_string('progress', 'rb_source_dp_certification_history'),
            'base.status',
            array(
                'displayfunc' => 'certif_completion_progress',
                'joins' => 'prog',
                'extrafields' => array(
                    'completion' => 'base.timecompleted',
                    'window' => 'base.timewindowopens',
                    'histpath' => 'base.certifpath',
                    'histcomp' => 'base.timecompleted',
                    'programid' => "prog.id",
                    'userid' => "base.userid",
                    'stringexport' => 0,
                )
            )
        );

        $columnoptions[] = new rb_column_option(
            'base',
            'progresspercentage',
            get_string('progresspercentage', 'rb_source_dp_certification_history'),
            'base.status',
            array(
                'displayfunc' => 'certif_completion_progress',
                'joins' => 'prog',
                'extrafields' => array(
                    'completion' => 'base.timecompleted',
                    'window' => 'base.timewindowopens',
                    'histpath' => 'base.certifpath',
                    'histcomp' => 'base.timecompleted',
                    'programid' => "prog.id",
                    'userid' => "base.userid",
                    'stringexport' => 1,
                )
            )
        );

        // Include some standard columns.
        $this->add_core_user_columns($columnoptions);
        $this->add_totara_job_columns($columnoptions);
        $this->add_core_course_category_columns($columnoptions, 'course_category', 'prog');

        return $columnoptions;
    }

    /**
     * Creates the array of rb_filter_option objects required for $this->filteroptions
     * @return array
     */
    protected function define_filteroptions() {
        $filteroptions = array();

        $filteroptions[] = new rb_filter_option(
                'prog',
                'fullname',
                get_string('certificationname', 'totara_program'),
                'text'
        );

        $filteroptions[] = new rb_filter_option(
                'base',
                'active',
                get_string('current', 'rb_source_dp_certification_history'),
                'select',
                array(
                    'selectfunc' => 'yesno_list',
                    'attributes' => rb_filter_option::select_width_limiter(),
                )
        );

        $filteroptions[] = new rb_filter_option(
                'prog',
                'shortname',
                get_string('programshortname', 'totara_program'),
                'text'
        );

        $filteroptions[] = new rb_filter_option(
                'prog',
                'idnumber',
                get_string('programidnumber', 'totara_program'),
                'text'
        );

        $filteroptions[] = new rb_filter_option(
                'base',
                'certifid',
                get_string('certificationid', 'rb_source_dp_certification'),
                'int'
        );

        $filteroptions[] = new rb_filter_option(
                'base',
                'timecompleted',
                get_string('timecompleted', 'rb_source_dp_certification'),
                'date'
        );

        $filteroptions[] = new rb_filter_option(
                'base',
                'timeexpires',
                get_string('timeexpires', 'rb_source_dp_certification'),
                'date'
        );

        $filteroptions[] = new rb_filter_option(
            'base',
            'status',
            get_string('status', 'rb_source_dp_certification_history'),
            'select',
            array(
                'selectfunc' => 'cert_status_list',
                'attributes' => rb_filter_option::select_width_limiter()
            )
        );

        $filteroptions[] = new rb_filter_option(
            'base',
            'renewalstatus',
            get_string('renewalstatus', 'rb_source_dp_certification_history'),
            'select',
            array(
                'selectfunc' => 'renewal_status_list',
                'attributes' => rb_filter_option::select_width_limiter()
            )
        );

        $this->add_core_user_filters($filteroptions);
        $this->add_totara_job_filters($filteroptions, 'base', 'userid');
        $this->add_core_course_category_filters($filteroptions);

        return $filteroptions;
    }

    /**
     * Creates an array of Certification renewal statuses for use in the renewal status filter
     *
     * return array list of Certification renewal statuses
     */
    public function rb_filter_renewal_status_list() {
        global $CERTIFRENEWALSTATUS;

        $list = array();

        foreach ($CERTIFRENEWALSTATUS as $key => $status) {
            $list[$key] = get_string($status, 'totara_certification');
        }

        return $list;
    }

    /*
     * Creates an array of Certification statuses for use in the status filter
     *
     * return array List of Certification statuses
     */
    public function rb_filter_cert_status_list() {
        global $CERTIFSTATUS;

        $list = array();

        foreach ($CERTIFSTATUS as $key => $status) {
            $list[$key] = get_string($status, 'totara_certification');
        }

        return $list;
    }


    /**
     * Creates the array of rb_content_option object required for $this->contentoptions
     * @return array
     */
    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);

        $contentoptions[] = new rb_content_option(
            'completed_org',
            get_string('orgwhencompleted', 'rb_source_course_completion_by_org'),
            'completion_organisation.path',
            'completion_organisation'
        );

        return $contentoptions;
    }


    protected function define_paramoptions() {
        global $CFG;

        $paramoptions = array();
        require_once($CFG->dirroot.'/totara/plan/lib.php');

        $paramoptions[] = new rb_param_option(
                'userid',
                'base.userid'
        );
        $paramoptions[] = new rb_param_option(
                'certifid',
                'base.certifid'
        );
        $paramoptions[] = new rb_param_option(
                'active',
                'base.active'
        );
        $paramoptions[] = new rb_param_option(
                'visible',
                'prog.visible',
                'prog'
        );
        $paramoptions[] = new rb_param_option(
                'category',
                'prog.category',
                'prog'
        );
        return $paramoptions;
    }


    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'user',
                'value' => 'namelink',
            ),
            array(
                'type' => 'prog',
                'value' => 'fullnamelink',
            ),
            array(
                'type' => 'course_category',
                'value' => 'namelink',
            ),
        );
        return $defaultcolumns;
    }


    protected function define_defaultfilters() {
        $defaultfilters = array(
            array(
                'type' => 'user',
                'value' => 'fullname',
                'advanced' => 0,
            ),
            array(
                'type' => 'prog',
                'value' => 'fullname',
                'advanced' => 0,
            ),
            array(
                'type' => 'course_category',
                'value' => 'path',
                'advanced' => 0,
            ),
        );
        return $defaultfilters;
    }

    /**
     * Display program icon with name and link.
     *
     * @deprecated Since Totara 12.0
     * @param $certificationname
     * @param $row
     * @param bool $isexport
     * @return string
     */
    function rb_display_link_program_icon($certificationname, $row, $isexport = false) {
        debugging('rb_source_dp_certification_history::rb_display_link_program_icon has been deprecated since Totara 12.0. Use totara_program\rb\display\program_icon_link::display', DEBUG_DEVELOPER);
        if ($isexport) {
            return $certificationname;
        }

        return prog_display_link_icon($row->programid, $row->userid);
    }

    /**
     * Check if the report source is disabled and should be ignored.
     *
     * @return boolean If the report should be ignored of not.
     */
    public static function is_source_ignored() {
        return (!totara_feature_visible('recordoflearning') or !totara_feature_visible('certifications'));
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
        return 2;
    }
}
