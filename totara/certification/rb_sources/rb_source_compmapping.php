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
 * @author Yashco Systems <reeta.yashco@gmail.com>
 * @package totara
 * @subpackage reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/program/rb_sources/rb_source_program_completion.php');
require_once($CFG->dirroot . '/totara/certification/lib.php');

class rb_source_compmapping extends rb_source_program_completion {

    /**
     * Overwrite instance type value of totara_visibility_where() in rb_source_program->post_config().
     */
    protected $instancetype = 'certification';

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        parent::__construct($groupid, $globalrestrictionset);

        // Global Report Restrictions are applied in rb_source_program_completion and work for rb_source_compmapping
        // as well.

        $this->sourcetitle = get_string('sourcetitle', 'rb_source_compmapping');
        $this->sourcewhere = $this->define_sourcewhere();
        $this->usedcomponents[] = "totara_certification";
    }

    /**
     * Hide this source if feature disabled or hidden.
     * @return bool
     */
    public function is_ignored() {
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
        $sourcewhere = 'base.coursesetid = 0';

        // Exclude programs (they have their own source).
        $sourcewhere .= ' AND (program.certifid IS NOT NULL)';

        return $sourcewhere;
    }

    protected function define_joinlist() {
        $joinlist = parent::define_joinlist();

        $this->add_certification_table_to_joinlist($joinlist, 'program', 'certifid');

        $joinlist[] = new rb_join(
            'certif_completion',
            'INNER',
            '{certif_completion}',
            "certif_completion.userid = base.userid AND certif_completion.certifid = program.certifid",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            array('base', 'program')
        );

        $joinlist[] = new rb_join(
            'prog_courseset',
            'LEFT',
            '{certif_completion}',
            "certif_completion.userid = base.userid AND certif_completion.certifid = program.certifid",
            REPORT_BUILDER_RELATION_ONE_TO_MANY,
            array('base', 'program')
        );

        return $joinlist;
    }

    protected function get_source_joins() {
        $parentjoins = parent::get_source_joins();
        return array_merge(array('certif_completion'), $parentjoins);
    }

    protected function define_columnoptions() {
        $columnoptions = parent::define_columnoptions();

        $this->add_certification_fields_to_columns($columnoptions, 'certif', 'totara_certification');

        // Remove the columns that we are going to replace with certification versions.

        foreach ($columnoptions as $key => $columnoption) {
            if ($columnoption->type == 'progcompletion' &&
                in_array($columnoption->value, array('status', 'iscomplete', 'isnotcomplete', 'isinprogress', 'isnotstarted'))) {
                unset($columnoptions[$key]);
            }
        }

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
            'statussecond',
            get_string('certstatus1', 'rb_source_compmapping'),
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
            get_string('certstatus2', 'rb_source_compmapping'),
            "certif_completion.status",
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'certcount',
                'dbdatatype' => 'decimal')
        );

        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'status4',
            get_string('parentcertstatus3', 'rb_source_compmapping'),
            "certif_completion.status",
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'parentcertcount',
                'dbdatatype' => 'decimal')
        );

        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'status5',
            get_string('catcertifstatus', 'rb_source_compmapping'),
            "certif_completion.status",
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'catcertifstatus',
                'dbdatatype' => 'decimal')
        );

        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'status6',
            get_string('parentcatcertifstatus', 'rb_source_compmapping'),
            "certif_completion.status",
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'parentcatcertifstatus',
                'dbdatatype' => 'decimal')
        );

        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'iscertified',
            get_string('iscertified', 'rb_source_compmapping'),
            'CASE WHEN certif_completion.certifpath = ' . CERTIFPATH_RECERT . ' THEN 1 ELSE 0 END',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'yes_or_no',
                'dbdatatype' => 'boolean',
                'defaultheading' => get_string('iscertified', 'rb_source_compmapping'),
            )
        );

        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'iscertified1',
            get_string('goalachieved', 'rb_source_compmapping'),
            'CASE WHEN certif_completion.certifpath = ' . CERTIFPATH_RECERT . ' THEN 1 ELSE 0 END',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'goal_achieve',
                'dbdatatype' => 'decimal',
                'defaultheading' => get_string('goalachieved', 'rb_source_compmapping'),
            )
        );

        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'iscertified2',
            get_string('subcatpercent', 'rb_source_compmapping'),
            'CASE WHEN certif_completion.certifpath = ' . CERTIFPATH_RECERT . ' THEN 1 ELSE 0 END',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'subcatpercent',
                'dbdatatype' => 'decimal',
                'defaultheading' => get_string('subcatpercent', 'rb_source_compmapping'),
            )
        );

        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'iscertified3',
            get_string('subcatpercentiscertif', 'rb_source_compmapping'),
            'CASE WHEN certif_completion.certifpath = ' . CERTIFPATH_RECERT . ' THEN 1 ELSE 0 END',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'subcatpercentiscertif',
                'dbdatatype' => 'decimal',
                'defaultheading' => get_string('subcatpercentiscertif', 'rb_source_compmapping'),
            )
        );

        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'iscertified4',
            get_string('iscertified4', 'rb_source_compmapping'),
            'CASE WHEN certif_completion.certifpath = ' . CERTIFPATH_RECERT . ' THEN 1 ELSE 0 END',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'yes_or_no4',
                'dbdatatype' => 'boolean',
                'defaultheading' => get_string('iscertified4', 'rb_source_compmapping'),
            )
        );

        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'iscertified5',
            get_string('iscertified5', 'rb_source_compmapping'),
            'CASE WHEN certif_completion.certifpath = ' . CERTIFPATH_RECERT . ' THEN 1 ELSE 0 END',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'iscertified5',
                'dbdatatype' => 'decimal',
                'defaultheading' => get_string('iscertified5', 'rb_source_compmapping'),
            )
        );

        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'iscertified6',
            get_string('iscertified6', 'rb_source_compmapping'),
            'CASE WHEN certif_completion.certifpath = ' . CERTIFPATH_RECERT . ' THEN 1 ELSE 0 END',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'iscertified6',
                'dbdatatype' => 'decimal',
                'defaultheading' => get_string('iscertified6', 'rb_source_compmapping'),
            )
        );

        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'iscertified7',
            get_string('iscertified7', 'rb_source_compmapping'),
            "null",
            array(
                'displayfunc' => 'iscertified7',
                'dbdatatype' => 'decimal',
                'defaultheading' => get_string('iscertified7', 'rb_source_compmapping'),
            )
        );//'joins' => 'certif_completion',
/*FROM mdl_prog p
      INNER JOIN mdl_course_categories cc ON cc.id = p.category
      WHERE p.certifid IS NOT NULL AND cc.path LIKE '/".$compid->id."/%'*/
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


        $columnoptions[] = new rb_column_option(
            'certcompletion',
            'isnotcertified',
            get_string('isnotcertified', 'rb_source_compmapping'),
            'CASE WHEN certif_completion.certifpath <> ' . CERTIFPATH_RECERT . ' THEN 1 ELSE 0 END',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'yes_or_no',
                'dbdatatype' => 'boolean',
                'defaultheading' => get_string('isnotcertified', 'rb_source_compmapping'),
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
            get_string('hasnevercertified', 'rb_source_compmapping'),
            'CASE WHEN certif_completion.status = ' . CERTIFSTATUS_ASSIGNED . ' OR
                       (certif_completion.status = ' . CERTIFSTATUS_INPROGRESS . ' AND
                        certif_completion.renewalstatus = ' . CERTIFRENEWALSTATUS_NOTDUE . ') THEN 1 ELSE 0 END',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'yes_or_no',
                'dbdatatype' => 'boolean',
                'defaultheading' => get_string('hasnevercertified', 'rb_source_compmapping'),
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
            get_string('redambergreenstatus', 'rb_source_compmapping'),
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
                'defaultheading' => get_string('status', 'rb_source_compmapping')
            )
        );

        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = parent::define_filteroptions();

        $this->add_certification_fields_to_filters($filteroptions, 'totara_certification');

        // Remove the filters that we are going to replace with certification versions.
        foreach ($filteroptions as $key => $filteroption) {
            if ($filteroption->type == 'progcompletion' &&
                in_array($filteroption->value, array('status', 'iscomplete', 'isnotcomplete', 'isinprogress', 'isnotstarted'))) {
                unset($filteroptions[$key]);
            }
        }

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
            get_string('iscertified', 'rb_source_compmapping'),
            'select',
            array(
                'selectfunc' => 'yesno_list',
                'simplemode' => true,
            )
        );
        $filteroptions[] = new rb_filter_option(
            'certcompletion',
            'isnotcertified',
            get_string('isnotcertified', 'rb_source_compmapping'),
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
            get_string('redambergreenstatus', 'rb_source_compmapping'),
            'grpconcat_multi',
            array(
                'selectfunc' => 'rag_status_list',
                'concat' => true,
                'simplemode' => true
            )
        );

        return $filteroptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'user',
                'value' => 'namelink',
            ),
            array(
                'type' => 'prog',
                'value' => 'proglinkicon',
            ),
            array(
                'type' => 'certcompletion',
                'value' => 'redambergreenstatus',
            ),
            array(
                'type' => 'progcompletion',
                'value' => 'duedate',
            ),
        );
        return $defaultcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = array(
            array(
                'type' => 'prog',
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

    public function rb_filter_certcount_category() {
        return true;
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
        $str = get_string('filter:problem', 'rb_source_compmapping');
        $class = 'label label-danger';
        $statuslist['problem'] = \html_writer::span($str, $class);

        // Action required.
        $str = get_string('filter:action', 'rb_source_compmapping');
        $class = 'label label-warning';
        $statuslist['action'] = \html_writer::span($str, $class);

        // No action required.
        $str = get_string('filter:noaction', 'rb_source_compmapping');
        $class = 'label label-success';
        $statuslist['success'] = \html_writer::span($str, $class);

        return $statuslist;
    }

}
