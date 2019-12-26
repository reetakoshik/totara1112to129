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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @package totara
 * @subpackage reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/plan/lib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/classes/rb_join_nonpruneable.php');

/**
 * A report builder source for DP objectives
 */
class rb_source_dp_objective extends rb_base_source {
    use \totara_job\rb\source\report_trait;

    /**
     * Constructor
     */
    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $this->add_global_report_restriction_join('dp', 'userid');

        $this->base = '{dp_plan_objective}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = array();
        $this->requiredcolumns = array();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_dp_objective');
        $this->usedcomponents[] = 'totara_plan';
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

        $joinlist[] = new rb_join_nonpruneable(
                'dp',
                'LEFT',
                '{dp_plan}',
                'base.planid = dp.id',
                REPORT_BUILDER_RELATION_MANY_TO_ONE,
                array()
        );

        $joinlist[] = new rb_join(
                'template',
                'LEFT',
                '{dp_template}',
                'dp.templateid = template.id',
                REPORT_BUILDER_RELATION_MANY_TO_ONE,
                array('dp')
        );

        $joinlist[] = new rb_join(
                'priority',
                'LEFT',
                '{dp_priority_scale_value}',
                'base.priority = priority.id',
                REPORT_BUILDER_RELATION_MANY_TO_ONE,
                array()
        );

        $joinlist[] = new rb_join(
                'objective_scale_value',
                'LEFT',
                '{dp_objective_scale_value}',
                'base.scalevalueid = objective_scale_value.id',
                REPORT_BUILDER_RELATION_MANY_TO_ONE,
                array()
        );

        $this->add_core_user_tables($joinlist, 'dp','userid');
        $this->add_totara_job_tables($joinlist, 'dp', 'userid');

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
                'plan',
                'name',
                get_string('planname', 'rb_source_dp_objective'),
                'dp.name',
                array(
                    'defaultheading' => get_string('plan', 'rb_source_dp_objective'),
                    'joins' => 'dp',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string'
                )
        );
        $columnoptions[] = new rb_column_option(
                'plan',
                'planlink',
                get_string('plannamelink', 'rb_source_dp_objective'),
                'dp.name',
                array(
                    'defaultheading' => get_string('plan', 'rb_source_dp_objective'),
                    'joins' => 'dp',
                    'displayfunc' => 'plan_link',
                    'extrafields' => array( 'plan_id' => 'dp.id' )
                )
        );
        $columnoptions[] = new rb_column_option(
                'plan',
                'startdate',
                get_string('planstartdate', 'rb_source_dp_objective'),
                'dp.startdate',
                array(
                    'joins' => 'dp',
                    'displayfunc' => 'nice_date',
                    'dbdatatype' => 'timestamp'
                )
        );
        $columnoptions[] = new rb_column_option(
                'plan',
                'enddate',
                get_string('planenddate', 'rb_source_dp_objective'),
                'dp.enddate',
                array(
                    'joins' => 'dp',
                    'displayfunc' => 'nice_date',
                    'dbdatatype' => 'timestamp'
                )
        );
        $columnoptions[] = new rb_column_option(
                'plan',
                'status',
                get_string('planstatus', 'rb_source_dp_objective'),
                'dp.status',
                array(
                    'joins' => 'dp',
                    'displayfunc' => 'plan_status'
                )
        );

        $columnoptions[] = new rb_column_option(
                'template',
                'name',
                get_string('templatename', 'rb_source_dp_objective'),
                'template.fullname',
                array(
                    'defaultheading' => get_string('plantemplate', 'rb_source_dp_objective'),
                    'joins' => 'template',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string'
                )
        );
        $columnoptions[] = new rb_column_option(
                'template',
                'startdate',
                get_string('templatestartdate', 'rb_source_dp_objective'),
                'template.startdate',
                array(
                    'joins' => 'template',
                    'displayfunc' => 'nice_date',
                    'dbdatatype' => 'timestamp'
                )
        );
        $columnoptions[] = new rb_column_option(
                'template',
                'enddate',
                get_string('templateenddate', 'rb_source_dp_objective'),
                'template.enddate',
                array(
                    'joins' => 'template',
                    'displayfunc' => 'nice_date',
                    'dbdatatype' => 'timestamp'
                )
        );

        $columnoptions[] = new rb_column_option(
                'objective',
                'fullname',
                get_string('fullname', 'rb_source_dp_objective'),
                'base.fullname',
                array(
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string')
        );

        $columnoptions[] = new rb_column_option(
                'objective',
                'fullnamelink',
                get_string('fullnamelink', 'rb_source_dp_objective'),
                'base.fullname',
                array(
                    'defaultheading' => get_string('fullname', 'rb_source_dp_objective'),
                    'displayfunc' => 'plan_objective_name_link',
                    'extrafields' => array(
                        'objective_id' => 'base.id',
                        'plan_id' => 'dp.id',
                    ),
                    'joins' => 'dp',
                )
        );

        $columnoptions[] = new rb_column_option(
                'objective',
                'shortname',
                get_string('shortname', 'rb_source_dp_objective'),
                'base.shortname',
                array(
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'plaintext')
        );

        $columnoptions[] = new rb_column_option(
                'objective',
                'description',
                get_string('description', 'rb_source_dp_objective'),
                'base.description',
                array(
                    'displayfunc' => 'editor_textarea',
                    'extrafields' => array(
                        'filearea' => '\'dp_plan_objective\'',
                        'component' => '\'totara_plan\'',
                        'recordid' => 'base.id',
                        'fileid' => 'base.id'
                    ),
                    'dbdatatype' => 'text',
                    'outputformat' => 'text')
        );

        $columnoptions[] = new rb_column_option(
                'objective',
                'duedate',
                get_string('objduedate', 'rb_source_dp_objective'),
                'base.duedate',
                array(
                    'displayfunc' => 'nice_date',
                    'dbdatatype' => 'timestamp'
                )
        );

        $columnoptions[] = new rb_column_option(
                'objective',
                'priority',
                get_string('objpriority', 'rb_source_dp_objective'),
                'priority.name',
                array(
                    'joins' => 'priority',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string'
                )
        );

        $columnoptions[] = new rb_column_option(
                'objective',
                'status',
                get_string('objapproved', 'rb_source_dp_objective'),
                'base.approved',
                array(
                    'displayfunc' => 'plan_item_status',
                )
        );

        $columnoptions[] = new rb_column_option(
                'objective',
                'proficiency',
                get_string('objstatus', 'rb_source_dp_objective'),
                'objective_scale_value.name',
                array(
                    'joins' => 'objective_scale_value',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string'
                )
        );


        $columnoptions[] = new rb_column_option(
                'objective',
                'isproficient',
                get_string('objproficient', 'rb_source_dp_objective'),
                'objective_scale_value.achieved',
                array(
                    'joins' => 'objective_scale_value',
                    'displayfunc' => 'yes_or_no'
                )
        );

        $columnoptions[] = new rb_column_option(
                'objective',
                'proficiencyandapproval',
                get_string('objectivestatusandapproval', 'rb_source_dp_objective'),
                'objective_scale_value.name',
                array(
                    'joins' => 'objective_scale_value',
                    'displayfunc' => 'plan_objective_status',
                    'defaultheading' => get_string('objstatus', 'rb_source_dp_objective'),
                    'extrafields' => array('approved' => 'base.approved')
                )
        );

        $columnoptions[] = new rb_column_option(
            'objective',
            'timecreated',
            get_string('objtimecreated', 'rb_source_dp_objective'),
            'base.timecreated',
            array(
                'displayfunc' => 'nice_date',
                'dbdatatype' => 'timestamp'
            )
        );

        $columnoptions[] = new rb_column_option(
            'objective',
            'timemodified',
            get_string('objtimemodified', 'rb_source_dp_objective'),
            'base.timemodified',
            array(
                'displayfunc' => 'nice_date',
                'dbdatatype' => 'timestamp'
            )
        );

        $this->add_core_user_columns($columnoptions);
        $this->add_totara_job_columns($columnoptions);

        return $columnoptions;
    }

    /**
     * Creates the array of rb_filter_option objects required for $this->filteroptions
     * @return array
     */
    protected function define_filteroptions() {
        $filteroptions = array();

        $filteroptions[] = new rb_filter_option(
                'objective',
                'fullname',
                get_string('objfullname', 'rb_source_dp_objective'),
                'text'
        );

        $filteroptions[] = new rb_filter_option(
                'objective',
                'shortname',
                get_string('objshortname', 'rb_source_dp_objective'),
                'text'
        );

        $filteroptions[] = new rb_filter_option(
                'objective',
                'description',
                get_string('objdescription', 'rb_source_dp_objective'),
                'textarea'
        );

        $filteroptions[] = new rb_filter_option(
                'objective',
                'priority',
                get_string('objpriority', 'rb_source_dp_objective'),
                'text'
        );

        $filteroptions[] = new rb_filter_option(
                'objective',
                'duedate',
                get_string('objduedate', 'rb_source_dp_objective'),
                'date'
        );

        $filteroptions[] = new rb_filter_option(
            'objective',
            'timecreated',
            get_string('objtimecreated', 'rb_source_dp_objective'),
            'date',
            array(
                'includenotset' => true,
            )
        );

        $filteroptions[] = new rb_filter_option(
            'objective',
            'timemodified',
            get_string('objtimemodified', 'rb_source_dp_objective'),
            'date',
            array(
                'includenotset' => true,
            )
        );

        $filteroptions[] = new rb_filter_option(
                'plan',
                'name',
                get_string('planname', 'rb_source_dp_objective'),
                'text'
        );

        $this->add_core_user_filters($filteroptions);
        $this->add_totara_job_filters($filteroptions, 'dp', 'userid');

        return $filteroptions;
    }

    /**
     * Creates the array of rb_content_option object required for $this->contentoptions
     * @return array
     */
    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);

        return $contentoptions;
    }

    protected function define_paramoptions() {
        global $CFG;

        $paramoptions = array();
        require_once($CFG->dirroot.'/totara/plan/lib.php');

        $paramoptions[] = new rb_param_option(
                'userid',
                'dp.userid',
                'dp'
        );
        $paramoptions[] = new rb_param_option(
                'rolstatus',
                'CASE WHEN objective_scale_value.achieved = 1
                THEN \'completed\' ELSE \'active\' END',
                'objective_scale_value',
                'string'
        );
        return $paramoptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'plan',
                'value' => 'planlink',
            ),
            array(
                'type' => 'plan',
                'value' => 'status',
            ),
            array(
                'type' => 'objective',
                'value' => 'fullnamelink',
            ),
            array(
                'type' => 'objective',
                'value' => 'description',
            ),
            array(
                'type' => 'objective',
                'value' => 'priority',
            ),
            array(
                'type' => 'objective',
                'value' => 'duedate',
            ),
            array(
                'type' => 'objective',
                'value' => 'proficiencyandapproval',
            ),
        );
        return $defaultcolumns;
    }

    /**
     * Generate the objective name with a link to the objective details page
     *
     * @deprecated Since Totara 12.0
     * @global object $CFG
     * @param string $objective Objective name
     * @param object $row Object containing other fields
     * @return string
     */
    public function rb_display_objectivelink($objective, $row) {
        debugging('rb_source_dp_objective::rb_display_objectivelink has been deprecated since Totara 12.0. Use totara_plan\rb\display\plan_objective_name_link::display', DEBUG_DEVELOPER);
        global $OUTPUT;
        if (empty($objective)) {
            return '';
        }
        return $OUTPUT->action_link(new moodle_url('/totara/plan/components/objective/view.php', array('id' => $row->plan_id, 'itemid' => $row->objective_id)), $objective);
    }

    /**
     * Display status
     *
     * @deprecated Since Totara 12.0
     * @param $status
     * @param $row
     * @return string
     */
    function rb_display_proficiency_and_approval($status, $row) {
        debugging('rb_source_dp_objective::rb_display_proficiency_and_approval has been deprecated since Totara 12.0. Use totara_plan\rb\display\plan_objective_status::display', DEBUG_DEVELOPER);
        global $CFG;
        // needed for approval constants
        require_once($CFG->dirroot . '/totara/plan/lib.php');

        $approved = isset($row->approved) ? $row->approved : null;

        $content = $status;

        // highlight if the item has not yet been approved
        if ($approved == DP_APPROVAL_UNAPPROVED ||
            $approved == DP_APPROVAL_DECLINED ||
            $approved == DP_APPROVAL_REQUESTED) {
            $content .= html_writer::empty_tag('br') . $this->rb_display_plan_item_status($approved);
        }
        return $content;
    }

    /**
     * Check if the report source is disabled and should be ignored.
     *
     * @return boolean If the report should be ignored of not.
     */
    public static function is_source_ignored() {
        return !totara_feature_visible('recordoflearning');
    }
}
