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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @package totara
 * @subpackage reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

class rb_source_program_completion extends rb_base_source {
    use \core_course\rb\source\report_trait;
    use \totara_program\rb\source\program_trait;
    use \totara_job\rb\source\report_trait;
    use \totara_cohort\rb\source\report_trait;

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
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_program_completion');
        $this->sourcewhere = $this->define_sourcewhere();
        $this->sourcejoins = $this->get_source_joins();
        $this->usedcomponents[] = "totara_program";
        $this->usedcomponents[] = 'totara_cohort';

        $this->cacheable = false;

        parent::__construct();
    }

    /**
     * Hide this source if feature disabled or hidden.
     * @return bool
     */
    public static function is_source_ignored() {
        return !totara_feature_visible('programs');
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return true;
    }

    protected function define_sourcewhere() {
         // Only consider whole programs - not courseset completion.
         $sourcewhere = 'base.coursesetid = 0';

         // Exclude certifications (they have their own source).
         $sourcewhere .= ' AND program.certifid IS NULL';

         return $sourcewhere;
    }

    protected function get_source_joins() {
        return array('program');
    }

    protected function define_joinlist() {
        $joinlist = array(
            new rb_join(
                'program',
                'INNER',
                '{prog}',
                "program.id = base.programid",
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'base'
            ),
            // Join 'prog_user_assignment' is needed for applying assignmentid parameter.
            new rb_join(
                'prog_user_assignment',
                'LEFT',
                '{prog_user_assignment}',
                'prog_user_assignment.programid = base.programid AND prog_user_assignment.userid = base.userid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'completion_organisation',
                'LEFT',
                '{org}',
                'completion_organisation.id = base.organisationid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'completion_position',
                'LEFT',
                '{pos}',
                'completion_position.id = base.positionid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
        );

        $this->add_core_user_tables($joinlist, 'base', 'userid');
        $this->add_totara_job_tables($joinlist, 'base', 'userid');
        $this->add_core_course_category_tables($joinlist, 'program', 'category');
        $this->add_totara_cohort_program_tables($joinlist, 'base', 'programid');

        return $joinlist;
    }

    protected function define_columnoptions() {
        $columnoptions = array();

        $columnoptions[] = new rb_column_option(
            'progcompletion',
            'status',
            get_string('programcompletionstatus','rb_source_program_completion'),
            "base.status",
            array(
                'displayfunc' => 'program_completion_status'
            )
        );

        $columnoptions[] = new rb_column_option(
            'progcompletion',
            'starteddate',
            get_string('datestarted', 'rb_source_program_completion'),
            'base.timestarted',
            array('displayfunc' => 'nice_date', 'dbdatatype' => 'timestamp')
        );
        $columnoptions[] = new rb_column_option(
            'progcompletion',
            'assigneddate',
            get_string('dateassigned', 'rb_source_program_completion'),
            'base.timecreated',
            array('displayfunc' => 'nice_date', 'dbdatatype' => 'timestamp')
        );
        $columnoptions[] = new rb_column_option(
            'progcompletion',
            'iscomplete',
            get_string('iscomplete', 'rb_source_program_completion'),
            'CASE WHEN base.status = ' . STATUS_PROGRAM_COMPLETE . ' THEN 1 ELSE 0 END',
            array(
                'displayfunc' => 'yes_or_no',
                'dbdatatype' => 'boolean',
                'defaultheading' => get_string('iscomplete', 'rb_source_program_completion'),
            )
        );
        $columnoptions[] = new rb_column_option(
            'progcompletion',
            'isnotcomplete',
            get_string('isnotcomplete', 'rb_source_program_completion'),
            'CASE WHEN base.status <> ' . STATUS_PROGRAM_COMPLETE . ' THEN 1 ELSE 0 END',
            // NOTE: STATUS_PROGRAM_INCOMPLETE comparison would be less future-proof here.
            array(
                'displayfunc' => 'yes_or_no',
                'dbdatatype' => 'boolean',
                'defaultheading' => get_string('isnotcomplete', 'rb_source_program_completion'),
            )
        );
        $columnoptions[] = new rb_column_option(
            'progcompletion',
            'isinprogress',
            get_string('isinprogress', 'rb_source_program_completion'),
            'CASE WHEN base.timestarted > 0 AND base.status <> ' . STATUS_PROGRAM_COMPLETE . ' THEN 1 ELSE 0 END',
            array(
                'displayfunc' => 'yes_or_no',
                'dbdatatype' => 'boolean',
                'defaultheading' => get_string('isinprogress', 'rb_source_program_completion'),
            )
        );
        $columnoptions[] = new rb_column_option(
            'progcompletion',
            'isnotstarted',
            get_string('isnotstarted', 'rb_source_program_completion'),
            'CASE WHEN base.timestarted = 0 AND base.status <> ' . STATUS_PROGRAM_COMPLETE . ' THEN 1 ELSE 0 END',
            array(
                'displayfunc' => 'yes_or_no',
                'dbdatatype' => 'boolean',
                'defaultheading' => get_string('isnotstarted', 'rb_source_program_completion'),
            )
        );

        $columnoptions[] = new rb_column_option(
            'progcompletion',
            'completeddate',
            get_string('completeddate', 'rb_source_program_completion'),
            'base.timecompleted',
            array('displayfunc' => 'nice_date', 'dbdatatype' => 'timestamp')
        );

        $columnoptions[] = new rb_column_option(
            'progcompletion',
            'duedate',
            get_string('duedate', 'rb_source_program_completion'),
            'base.timedue',
            array('displayfunc' => 'nice_datetime', 'dbdatatype' => 'timestamp')
        );

        $columnoptions[] =new rb_column_option(
            'progcompletion',
            'organisationid',
            get_string('completionorgid', 'rb_source_program_completion'),
            'base.organisationid',
            array('displayfunc' => 'integer')
        );

        $columnoptions[] =new rb_column_option(
            'progcompletion',
            'organisationid2',
            get_string('completionorgid', 'rb_source_program_completion'),
            'base.organisationid',
            array('selectable' => false,
                  'displayfunc' => 'integer')
        );

        $columnoptions[] =new rb_column_option(
            'progcompletion',
            'organisationpath',
            get_string('completionorgpath', 'rb_source_program_completion'),
            'completion_organisation.path',
            array('joins' => 'completion_organisation', 'selectable' => false)
        );

        $columnoptions[] =new rb_column_option(
            'progcompletion',
            'organisation',
            get_string('completionorgname', 'rb_source_program_completion'),
            'completion_organisation.fullname',
            array('joins' => 'completion_organisation',
                  'dbdatatype' => 'char',
                  'outputformat' => 'text',
                  'displayfunc' => 'format_string')
        );

        $columnoptions[] =new rb_column_option(
            'progcompletion',
            'positionid',
            get_string('completionposid', 'rb_source_program_completion'),
            'base.positionid',
            array('displayfunc' => 'integer')
        );

        $columnoptions[] =new rb_column_option(
            'progcompletion',
            'positionid2',
            get_string('completionposid', 'rb_source_program_completion'),
            'base.positionid',
            array('selectable' => false,
                  'displayfunc' => 'integer')
        );

        $columnoptions[] =new rb_column_option(
            'progcompletion',
            'positionpath',
            get_string('completionpospath', 'rb_source_program_completion'),
            'completion_position.path',
            array('joins' => 'completion_position', 'selectable' => false)
        );

        $columnoptions[] =new rb_column_option(
            'progcompletion',
            'position',
            get_string('completionposname', 'rb_source_program_completion'),
            'completion_position.fullname',
            array('joins' => 'completion_position',
                  'dbdatatype' => 'char',
                  'outputformat' => 'text',
                  'displayfunc' => 'format_string')
        );

        $columnoptions[] = new rb_column_option(
            'progcompletion',
            'isassigned',
            get_string('isuserassigned', 'rb_source_program_completion'),
            '(SELECT CASE WHEN COUNT(pua.id) >= 1 THEN 1 ELSE 0 END
                FROM {prog_user_assignment} pua
               WHERE pua.programid = base.programid AND pua.userid = base.userid
               AND pua.exceptionstatus NOT IN (' . PROGRAM_EXCEPTION_DISMISSED . ', ' . PROGRAM_EXCEPTION_RAISED . '))',
            array(
                'displayfunc' => 'yes_or_no',
                'dbdatatype' => 'boolean',
                'issubquery' => true,
                'defaultheading' => get_string('isuserassigned', 'rb_source_program_completion')
            )
        );

        $columnoptions[] = new rb_column_option(
            'progcompletion',
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
            'progcompletion',
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
        $this->add_core_course_category_columns($columnoptions, 'course_category', 'program');
        $this->add_totara_program_columns($columnoptions, 'program');
        $this->add_totara_cohort_program_columns($columnoptions);

        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = array();

        $filteroptions[] = new rb_filter_option(
            'progcompletion',
            'starteddate',
            get_string('datestarted', 'rb_source_program_completion'),
            'date'
        );

        $filteroptions[] = new rb_filter_option(
            'progcompletion',
            'assigneddate',
            get_string('dateassigned', 'rb_source_program_completion'),
            'date'
        );

        $filteroptions[] = new rb_filter_option(
            'progcompletion',
            'completeddate',
            get_string('completeddate', 'rb_source_program_completion'),
            'date'
        );

        $filteroptions[] = new rb_filter_option(
            'progcompletion',
            'duedate',
            get_string('duedate', 'rb_source_program_completion'),
            'date'
        );

        $filteroptions[] = new rb_filter_option(
            'progcompletion',
            'status',
            get_string('programcompletionstatus', 'rb_source_program_completion'),
            'select',
            array (
                'selectchoices' => array(
                    0 => get_string('incomplete', 'totara_program'),
                    1 => get_string('complete', 'totara_program'),
                ),
                'attributes' => rb_filter_option::select_width_limiter(),
                'simplemode' => true,
            )
        );
        $filteroptions[] = new rb_filter_option(
            'progcompletion',
            'iscomplete',
            get_string('iscomplete', 'rb_source_program_completion'),
            'select',
            array(
                'selectfunc' => 'yesno_list',
                'simplemode' => true,
            )
        );
        $filteroptions[] = new rb_filter_option(
            'progcompletion',
            'isnotcomplete',
            get_string('isnotcomplete', 'rb_source_program_completion'),
            'select',
            array(
                'selectfunc' => 'yesno_list',
                'simplemode' => true,
            )
        );
        $filteroptions[] = new rb_filter_option(
            'progcompletion',
            'isinprogress',
            get_string('isinprogress', 'rb_source_program_completion'),
            'select',
            array(
                'selectfunc' => 'yesno_list',
                'simplemode' => true,
            )
        );
        $filteroptions[] = new rb_filter_option(
            'progcompletion',
            'isnotstarted',
            get_string('isnotstarted', 'rb_source_program_completion'),
            'select',
            array(
                'selectfunc' => 'yesno_list',
                'simplemode' => true,
            )
        );
        $filteroptions[] = new rb_filter_option(
            'progcompletion',
            'organisationid',
            get_string('orgwhencompletedbasic', 'rb_source_program_completion'),
            'select',
            array(
                'selectfunc' => 'organisations_list',
                'attributes' => rb_filter_option::select_width_limiter(),
            )
        );

        $filteroptions[] = new rb_filter_option(
            'progcompletion',
            'organisationid2',
            get_string('multiorgwhencompleted', 'rb_source_program_completion'),
            'hierarchy_multi',
            array(
                'hierarchytype' => 'org',
            )
        );

        $filteroptions[] = new rb_filter_option(
            'progcompletion',
            'organisationpath',
            get_string('orgwhencompleted', 'rb_source_program_completion'),
            'hierarchy',
            array(
                'hierarchytype' => 'org',
            )
        );

        $filteroptions[] = new rb_filter_option(
            'progcompletion',
            'positionid',
            get_string('poswhencompletedbasic', 'rb_source_program_completion'),
            'select',
            array(
                'selectfunc' => 'positions_list',
                'attributes' => rb_filter_option::select_width_limiter()
            )
        );

        $filteroptions[] = new rb_filter_option(
            'progcompletion',
            'positionid2',
            get_string('multiposwhencompleted', 'rb_source_program_completion'),
            'hierarchy_multi',
            array(
                'hierarchytype' => 'pos',
            )
        );

        $filteroptions[] = new rb_filter_option(
            'progcompletion',
            'positionpath',
            get_string('poswhencompleted', 'rb_source_program_completion'),
            'hierarchy',
            array(
                'hierarchytype' => 'pos',
            )
        );

        $filteroptions[] = new rb_filter_option(
            'progcompletion',
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
        $this->add_totara_program_filters($filteroptions);
        $this->add_totara_cohort_program_filters($filteroptions, "totara_program");

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
                'type' => 'prog',
                'value' => 'proglinkicon',
            ),
            array(
                'type' => 'progcompletion',
                'value' => 'status',
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
                'type' => 'progcompletion',
                'value' => 'status',
                'advanced' => 0,
            ),
        );
        return $defaultfilters;
    }

    protected function define_requiredcolumns() {
        $requiredcolumns = array();
        return $requiredcolumns;
    }

    /**
     * Display the program completion status
     *
     * @deprecated Since Totara 12.0
     * @param $status
     * @param $row
     * @return string
     */
    function rb_display_program_completion_status($status, $row) {
        debugging('rb_source_program_completion::rb_display_program_completion_status has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        if (is_null($status)) {
            return '';
        }
        if ($status) {
            return get_string('complete', 'totara_program');
        } else {
            return get_string('incomplete', 'totara_program');
        }
    }

}
