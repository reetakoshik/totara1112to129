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
 * @author David Curry <david.curry@totaralms.com>
 * @package totara
 * @subpackage reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/certification/lib.php');

class rb_source_global_certification_completion extends rb_base_source {
    use \core_course\rb\source\report_trait;
    use \totara_job\rb\source\report_trait;
    use \totara_cohort\rb\source\report_trait;
    use \totara_certification\rb\source\certification_trait;

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $this->add_global_report_restriction_join('base', 'userid');

        $this->base = '{prog_completion}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_global_certification_completion');
        $this->sourcewhere = $this->define_sourcewhere();
        $this->sourcejoins = $this->get_source_joins();
        $this->usedcomponents[] = "totara_certification";
        $this->usedcomponents[] = "totara_program";
        $this->usedcomponents[] = 'totara_cohort';

        $this->cacheable = false;

        // Add custom fields.
        $this->add_totara_customfield_component(
            'prog', 'certif', 'programid',
            $this->joinlist, $this->columnoptions, $this->filteroptions
        );

        parent::__construct();
    }

    /**
     * Hide this source if feature disabled or hidden.
     * @return bool
     */
    public static function is_source_ignored() {
        return !totara_feature_visible('certifications');
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return true;
    }

    protected function define_sourcewhere() {
        // Only consider whole certifications - not courseset completion.
        return 'base.coursesetid = 0 AND certif.id IS NOT NULL';
    }

    protected function get_source_joins() {
        return array('certif');
    }

    protected function define_joinlist() {
        global $CFG;

        $joinlist = array();
        $this->add_totara_certification_tables($joinlist, 'base', 'programid');

        // Join 'prog_user_assignment' is needed for applying assignmentid parameter.
        $joinlist[] = new rb_join(
            'prog_user_assignment',
            'LEFT',
            '{prog_user_assignment}',
            'prog_user_assignment.programid = base.programid AND prog_user_assignment.userid = base.userid',
            REPORT_BUILDER_RELATION_ONE_TO_ONE
        );
        $joinlist[] = new rb_join(
            'completion_organisation',
            'LEFT',
            '{org}',
            'completion_organisation.id = base.organisationid',
            REPORT_BUILDER_RELATION_ONE_TO_ONE
        );
        $joinlist[] = new rb_join(
            'completion_position',
            'LEFT',
            '{pos}',
            'completion_position.id = base.positionid',
            REPORT_BUILDER_RELATION_ONE_TO_ONE
        );

        $this->add_core_user_tables($joinlist, 'base', 'userid');
        $this->add_totara_job_tables($joinlist, 'base', 'userid');
        $this->add_core_course_category_tables($joinlist, 'certif', 'category');
        $this->add_totara_cohort_program_tables($joinlist, 'base', 'programid');

        $joinlist[] = new rb_join(
            'certif_completion',
            'INNER',
            '{certif_completion}',
            "certif_completion.userid = base.userid AND certif_completion.certifid = certif.certifid",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            array('certif')
        );

        return $joinlist;
    }

    protected function define_columnoptions() {
        $columnoptions = array();

        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'starteddate',
            get_string('datestarted', 'rb_source_program_completion'),
            'base.timestarted',
            array('displayfunc' => 'nice_date', 'dbdatatype' => 'timestamp')
        );
//custom
        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'statussecond',
            get_string('certstatus1', 'rb_source_global_certification_completion'),
            "certif_completion.status",
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'certif_status')
        );


        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'status7',
            'Certif Status 7',
            "certif_completion.status",
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'certifstatus7',
                'dbdatatype' => 'decimal')
        );

        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'status3',
            get_string('certstatus2', 'rb_source_global_certification_completion'),
            "certif_completion.status",
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'certcount',
                'dbdatatype' => 'decimal')
        );

        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'status4',
            get_string('parentcertstatus3', 'rb_source_global_certification_completion'),
            "certif_completion.status",
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'parentcertcount',
                'dbdatatype' => 'decimal')
        );

        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'status5',
            get_string('catcertifstatus', 'rb_source_global_certification_completion'),
            "certif_completion.status",
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'catcertifstatus',
                'dbdatatype' => 'decimal')
        );

        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'status6',
            get_string('parentcatcertifstatus', 'rb_source_global_certification_completion'),
            "certif_completion.status",
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'parentcatcertifstatus',
                'dbdatatype' => 'decimal')
        );
        
        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'iscertified1',
            get_string('goalachieved', 'rb_source_global_certification_completion'),
            'CASE WHEN certif_completion.certifpath = ' . CERTIFPATH_RECERT . ' THEN 1 ELSE 0 END',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'goal_achieve',
                'dbdatatype' => 'decimal',
                'defaultheading' => get_string('goalachieved', 'rb_source_global_certification_completion'),
            )
        );

        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'iscertified2',
            get_string('subcatpercent', 'rb_source_global_certification_completion'),
            'CASE WHEN certif_completion.certifpath = ' . CERTIFPATH_RECERT . ' THEN 1 ELSE 0 END',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'subcatpercent',
                'dbdatatype' => 'decimal',
                'defaultheading' => get_string('subcatpercent', 'rb_source_global_certification_completion'),
            )
        );

        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'iscertified3',
            get_string('subcatpercentiscertif', 'rb_source_global_certification_completion'),
            'CASE WHEN certif_completion.certifpath = ' . CERTIFPATH_RECERT . ' THEN 1 ELSE 0 END',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'subcatpercentiscertif',
                'dbdatatype' => 'decimal',
                'defaultheading' => get_string('subcatpercentiscertif', 'rb_source_global_certification_completion'),
            )
        );

        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'iscertified4',
            get_string('iscertified4', 'rb_source_global_certification_completion'),
            'CASE WHEN certif_completion.certifpath = ' . CERTIFPATH_RECERT . ' THEN 1 ELSE 0 END',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'yes_or_no4',
                'dbdatatype' => 'boolean',
                'defaultheading' => get_string('iscertified4', 'rb_source_global_certification_completion'),
            )
        );

        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'iscertified5',
            get_string('iscertified5', 'rb_source_global_certification_completion'),
            'CASE WHEN certif_completion.certifpath = ' . CERTIFPATH_RECERT . ' THEN 1 ELSE 0 END',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'iscertified5',
                'dbdatatype' => 'decimal',
                'defaultheading' => get_string('iscertified5', 'rb_source_global_certification_completion'),
            )
        );

        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'iscertified6',
            get_string('iscertified6', 'rb_source_global_certification_completion'),
            'CASE WHEN certif_completion.certifpath = ' . CERTIFPATH_RECERT . ' THEN 1 ELSE 0 END',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'iscertified6',
                'dbdatatype' => 'decimal',
                'defaultheading' => get_string('iscertified6', 'rb_source_global_certification_completion'),
            )
        );

        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'iscertifiedorg',
            'Is Certif Organisation',
            'CASE WHEN certif_completion.certifpath = ' . CERTIFPATH_RECERT . ' THEN 1 ELSE 0 END',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'iscertifiedorg',
                'dbdatatype' => 'decimal',
                'defaultheading' => 'Is Certif Organisation',
            )
        );

        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'iscertifiedorgnoncomp',
            'Is Certif Org Non-Compliant',
            'CASE WHEN certif_completion.certifpath = ' . CERTIFPATH_RECERT . ' THEN 1 ELSE 0 END',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'iscertifiedorgnoncomp',
                'dbdatatype' => 'decimal',
                'defaultheading' => 'Is Certif Org Non-Compliant',
            )
        );



//custom
        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'assigneddate',
            get_string('dateassigned', 'rb_source_program_completion'),
            'base.timecreated',
            array('displayfunc' => 'nice_date', 'dbdatatype' => 'timestamp')
        );

        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'completeddate',
            get_string('completeddate', 'rb_source_program_completion'),
            'base.timecompleted',
            array('displayfunc' => 'nice_date', 'dbdatatype' => 'timestamp')
        );

        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'duedate',
            get_string('duedate', 'rb_source_program_completion'),
            'base.timedue',
            array('displayfunc' => 'nice_datetime', 'dbdatatype' => 'timestamp')
        );

        $columnoptions[] =new rb_column_option(
            'certcompletion',
            'organisationid',
            get_string('completionorgid', 'rb_source_program_completion'),
            'base.organisationid',
            array('displayfunc' => 'integer')
        );

        $columnoptions[] =new rb_column_option(
            'certcompletion',
            'organisationid2',
            get_string('completionorgid', 'rb_source_program_completion'),
            'base.organisationid',
            array('selectable' => false,
                'displayfunc' => 'integer')
        );

        $columnoptions[] =new rb_column_option(
            'certcompletion',
            'organisationpath',
            get_string('completionorgpath', 'rb_source_program_completion'),
            'completion_organisation.path',
            array('joins' => 'completion_organisation', 'selectable' => false)
        );

        $columnoptions[] =new rb_column_option(
            'certcompletion',
            'organisation',
            get_string('completionorgname', 'rb_source_program_completion'),
            'completion_organisation.fullname',
            array('joins' => 'completion_organisation',
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'displayfunc' => 'format_string')
        );

        $columnoptions[] =new rb_column_option(
            'certcompletion',
            'positionid',
            get_string('completionposid', 'rb_source_program_completion'),
            'base.positionid',
            array('displayfunc' => 'integer')
        );

        $columnoptions[] =new rb_column_option(
            'certcompletion',
            'positionid2',
            get_string('completionposid', 'rb_source_program_completion'),
            'base.positionid',
            array('selectable' => false,
                'displayfunc' => 'integer')
        );

        $columnoptions[] =new rb_column_option(
            'certcompletion',
            'positionpath',
            get_string('completionpospath', 'rb_source_program_completion'),
            'completion_position.path',
            array('joins' => 'completion_position', 'selectable' => false)
        );

        $columnoptions[] =new rb_column_option(
            'certcompletion',
            'position',
            get_string('completionposname', 'rb_source_program_completion'),
            'completion_position.fullname',
            array('joins' => 'completion_position',
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'displayfunc' => 'format_string')
        );

        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'isassigned',
            get_string('isuserassigned', 'rb_source_program_completion'),
            '(SELECT CASE WHEN COUNT(pua.id) >= 1 THEN 1 ELSE 0 END
                FROM {prog_user_assignment} pua
               WHERE pua.programid = base.programid AND pua.userid = base.userid)',
            array(
                'displayfunc' => 'yes_or_no',
                'dbdatatype' => 'boolean',
                'issubquery' => true,
                'defaultheading' => get_string('isuserassigned', 'rb_source_program_completion')
            )
        );

        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'progressnumeric',
            get_string('programcompletionprogressnumeric','rb_source_program_completion'),
            "base.status",
            array(
                'displayfunc' => 'program_completion_progress',
                'extrafields' => array(
                    'programid' => "base.programid",
                    'userid' => "base.userid",
                    'stringexport' => 0
                )
            )
        );

        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'progresspercentage',
            get_string('programcompletionprogresspercentage','rb_source_program_completion'),
            "base.status",
            array(
                'displayfunc' => 'program_completion_progress',
                'extrafields' => array(
                    'programid' => "base.programid",
                    'userid' => "base.userid",
                    'stringexport' => 1
                )
            )
        );

        // Include some standard columns.
        $this->add_core_user_columns($columnoptions);
        $this->add_totara_job_columns($columnoptions);
        $this->add_core_course_category_columns($columnoptions, 'course_category', 'certif');
        $this->add_totara_cohort_program_columns($columnoptions);
        $this->add_totara_certification_columns($columnoptions, 'certif');

        // Add back the columns that were just removed, but suitable for certifications.
        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'status',
            get_string('status', 'rb_source_dp_certification'),
            'certif_completion.status',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'certif_status',
            )
        );
        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'iscertified',
            get_string('iscertified', 'rb_source_global_certification_completion'),
            'CASE WHEN certif_completion.certifpath = ' . CERTIFPATH_RECERT . ' THEN 1 ELSE 0 END',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'yes_or_no',
                'dbdatatype' => 'boolean',
                'defaultheading' => get_string('iscertified', 'rb_source_global_certification_completion'),
            )
        );
        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'isnotcertified',
            get_string('isnotcertified', 'rb_source_global_certification_completion'),
            'CASE WHEN certif_completion.certifpath <> ' . CERTIFPATH_RECERT . ' THEN 1 ELSE 0 END',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'yes_or_no',
                'dbdatatype' => 'boolean',
                'defaultheading' => get_string('isnotcertified', 'rb_source_global_certification_completion'),
            )
        );
        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'isinprogress',
            get_string('isinprogress', 'rb_source_program_completion'),
            'CASE WHEN certif_completion.status = ' . CERTIFSTATUS_INPROGRESS . ' THEN 1 ELSE 0 END',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'yes_or_no',
                'dbdatatype' => 'boolean',
                'defaultheading' => get_string('isinprogress', 'rb_source_program_completion'),
            )
        );
        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'isnotstarted',
            get_string('isnotstarted', 'rb_source_program_completion'),
            'CASE WHEN certif_completion.status = ' . CERTIFSTATUS_ASSIGNED . ' THEN 1 ELSE 0 END',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'yes_or_no',
                'dbdatatype' => 'boolean',
                'defaultheading' => get_string('isnotstarted', 'rb_source_program_completion'),
            )
        );
        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'hasnevercertified',
            get_string('hasnevercertified', 'rb_source_global_certification_completion'),
            'CASE WHEN certif_completion.status = ' . CERTIFSTATUS_ASSIGNED . ' OR
                       (certif_completion.status = ' . CERTIFSTATUS_INPROGRESS . ' AND
                        certif_completion.renewalstatus = ' . CERTIFRENEWALSTATUS_NOTDUE . ') THEN 1 ELSE 0 END',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'yes_or_no',
                'dbdatatype' => 'boolean',
                'defaultheading' => get_string('hasnevercertified', 'rb_source_global_certification_completion'),
            )
        );

        // Note.
        // The field select uses a case statement that returns a concatenated value comprising of the following,
        //  * Numeric order for the column sort.
        //  * Text used for the filter, (problem, action required, no action required / red, amber, green).
        //  * Status text identifier used by the display class.
        $now = time();
        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'redambergreenstatus',
            get_string('redambergreenstatus', 'rb_source_global_certification_completion'),
            "CASE WHEN certif_completion.status = " . CERTIFSTATUS_EXPIRED . " THEN '1|problem|expired'
                  WHEN certif_completion.timecompleted = 0 AND (base.timedue > 0 AND base.timedue <= " . $now . ")  THEN '1|problem|overdue'
                  WHEN certif_completion.timecompleted = 0 AND base.timedue <= 0  THEN '3|success|assignedwithoutduedate'
                  WHEN certif_completion.timecompleted = 0 AND base.timedue > 0  THEN '2|action|assignedwithduedate'
                  WHEN certif_completion.timecompleted <> 0
                    AND certif_completion.timewindowopens <> 0
                    AND certif_completion.timewindowopens <= " . $now . "
                    AND certif_completion.timeexpires >= " . $now . " THEN '2|action|windowopen'
                  WHEN certif_completion.timecompleted <> 0  THEN '3|success|certified'
                  ELSE null END",
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'redambergreenstatus',
                'extrafields' => array(
                    'timedue' => 'base.timedue',
                    'timewindowopens' => 'certif_completion.timewindowopens'),
                'defaultheading' => get_string('status', 'rb_source_global_certification_completion')
            )
        );

        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = array();

        $filteroptions[] = new rb_filter_option(
            'certcompletion',
            'starteddate',
            get_string('dateassigned', 'rb_source_program_completion'),
            'date'
        );

        $filteroptions[] = new rb_filter_option(
            'certcompletion',
            'completeddate',
            get_string('completeddate', 'rb_source_program_completion'),
            'date'
        );

        $filteroptions[] = new rb_filter_option(
            'certcompletion',
            'duedate',
            get_string('duedate', 'rb_source_program_completion'),
            'date'
        );

        $filteroptions[] = new rb_filter_option(
            'certcompletion',
            'organisationid',
            get_string('orgwhencompletedbasic', 'rb_source_program_completion'),
            'select',
            array(
                'selectfunc' => 'organisations_list',
                'attributes' => rb_filter_option::select_width_limiter(),
            )
        );

        $filteroptions[] = new rb_filter_option(
            'certcompletion',
            'organisationid2',
            get_string('multiorgwhencompleted', 'rb_source_program_completion'),
            'hierarchy_multi',
            array(
                'hierarchytype' => 'org',
            )
        );

        $filteroptions[] = new rb_filter_option(
            'certcompletion',
            'organisationpath',
            get_string('orgwhencompleted', 'rb_source_program_completion'),
            'hierarchy',
            array(
                'hierarchytype' => 'org',
            )
        );

        $filteroptions[] = new rb_filter_option(
            'certcompletion',
            'positionid',
            get_string('poswhencompletedbasic', 'rb_source_program_completion'),
            'select',
            array(
                'selectfunc' => 'positions_list',
                'attributes' => rb_filter_option::select_width_limiter()
            )
        );

        $filteroptions[] = new rb_filter_option(
            'certcompletion',
            'positionid2',
            get_string('multiposwhencompleted', 'rb_source_program_completion'),
            'hierarchy_multi',
            array(
                'hierarchytype' => 'pos',
            )
        );

        $filteroptions[] = new rb_filter_option(
            'certcompletion',
            'positionpath',
            get_string('poswhencompleted', 'rb_source_program_completion'),
            'hierarchy',
            array(
                'hierarchytype' => 'pos',
            )
        );

        $filteroptions[] = new rb_filter_option(
            'certcompletion',
            'isassigned',
            get_string('isuserassigned', 'rb_source_program_completion'),
            'select',
            array(
                'selectfunc' => 'yesno_list',
                'simplemode' => 'true'
            )
        );

        // Include some standard filters.
        $this->add_core_user_filters($filteroptions);
        $this->add_core_course_category_filters($filteroptions);
        $this->add_totara_job_filters($filteroptions, 'base', 'userid');
        $this->add_totara_cohort_program_filters($filteroptions, "totara_certification");

        $this->add_totara_certification_filters($filteroptions);

        // Add back the filters that were just removed, but suitable for certifications.
        $filteroptions[] = new rb_filter_option(
            'certcompletion',
            'status',
            get_string('status', 'rb_source_dp_certification'),
            'select',
            array(
                'selectfunc' => 'status',
                'attributes' => rb_filter_option::select_width_limiter(),
            )
        );
        $filteroptions[] = new rb_filter_option(
            'certcompletion',
            'iscertified',
            get_string('iscertified', 'rb_source_global_certification_completion'),
            'select',
            array(
                'selectfunc' => 'yesno_list',
                'simplemode' => true,
            )
        );
        $filteroptions[] = new rb_filter_option(
            'certcompletion',
            'isnotcertified',
            get_string('isnotcertified', 'rb_source_global_certification_completion'),
            'select',
            array(
                'selectfunc' => 'yesno_list',
                'simplemode' => true,
            )
        );
        $filteroptions[] = new rb_filter_option(
            'certcompletion',
            'isinprogress',
            get_string('isinprogress', 'rb_source_program_completion'),
            'select',
            array(
                'selectfunc' => 'yesno_list',
                'simplemode' => true,
            )
        );
        $filteroptions[] = new rb_filter_option(
            'certcompletion',
            'isnotstarted',
            get_string('isnotstarted', 'rb_source_program_completion'),
            'select',
            array(
                'selectfunc' => 'yesno_list',
                'simplemode' => true,
            )
        );
        $filteroptions[] = new rb_filter_option(
            'certcompletion',
            'redambergreenstatus',
            get_string('redambergreenstatus', 'rb_source_global_certification_completion'),
            'grpconcat_multi',
            array(
                'selectfunc' => 'rag_status_list',
                'concat' => true,
                'simplemode' => true
            )
        );

        return $filteroptions;
    }

    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);

        $contentoptions[] = new rb_content_option(
            'completed_org',
            get_string('orgwhencompleted', 'rb_source_program_completion'),
            'completion_organisation.path',
            'completion_organisation'
        );

        $contentoptions[] = new rb_content_option(
            'date',
            get_string('completeddate', 'rb_source_program_completion'),
            'base.timecompleted'
        );

        return $contentoptions;
    }

    protected function define_paramoptions() {
        $paramoptions = array(
            new rb_param_option(
                'programid',
                'base.programid'
            ),
            new rb_param_option(
                'userid',
                'base.userid'
            ),
            new rb_param_option(
                'assignmentid',
                'prog_user_assignment.assignmentid',
                'prog_user_assignment'
            ),
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
                'type' => 'certif',
                'value' => 'proglinkicon',
            ),
            array(
                'type' => 'certcompletion',
                'value' => 'redambergreenstatus',
            ),
            array(
                'type' => 'certcompletion',
                'value' => 'duedate',
            ),
        );
        return $defaultcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = array(
            array(
                'type' => 'certif',
                'value' => 'fullname',
                'advanced' => 0,
            ),
            array(
                'type' => 'user',
                'value' => 'fullname',
                'advanced' => 0,
            ),
            array(
                'type' => 'certcompletion',
                'value' => 'redambergreenstatus',
                'advanced' => 0,
            ),
        );
        return $defaultfilters;
    }

    protected function define_requiredcolumns() {
        $requiredcolumns = array();
        return $requiredcolumns;
    }


    public function rb_filter_status() {
        global $CERTIFSTATUS;

        $out = array();
        foreach ($CERTIFSTATUS as $code => $statusstring) {
            $out[$code] = get_string($statusstring, 'totara_certification');
        }
        return $out;
    }

    /**
     * Filter rag status.
     *
     * @return array
     */
    public function rb_filter_rag_status_list() {

        // Problem.
        $str = get_string('filter:problem', 'rb_source_global_certification_completion');
        $class = 'label label-danger';
        $statuslist['problem'] = \html_writer::span($str, $class);

        // Action required.
        $str = get_string('filter:action', 'rb_source_global_certification_completion');
        $class = 'label label-warning';
        $statuslist['action'] = \html_writer::span($str, $class);

        // No action required.
        $str = get_string('filter:noaction', 'rb_source_global_certification_completion');
        $class = 'label label-success';
        $statuslist['success'] = \html_writer::span($str, $class);

        return $statuslist;
    }
}
