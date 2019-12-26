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

class rb_source_course_completion extends rb_base_source {
    use \core_course\rb\source\report_trait;
    use \core_tag\rb\source\report_trait;
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

        $this->base = '{course_completions}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_course_completion');
        $this->usedcomponents[] = 'totara_cohort';

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

    protected function define_joinlist() {
        global $CFG;

        // to get access to constants
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria.php');

        $joinlist = array(
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
            new rb_join(
                'criteria',
                'LEFT',
                '{course_completion_criteria}',
                '(criteria.course = base.course AND ' .
                    'criteria.criteriatype = ' .
                    COMPLETION_CRITERIA_TYPE_GRADE . ')',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'critcompl',
                'LEFT',
                '{course_completion_crit_compl}',
                '(critcompl.userid = base.userid AND ' .
                    'critcompl.criteriaid = criteria.id AND ' .
                    '(critcompl.deleted IS NULL OR critcompl.deleted = 0))',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'criteria'
            ),
            new rb_join(
                'grade_items',
                'LEFT',
                '{grade_items}',
                '(grade_items.courseid = base.course AND ' .
                    'grade_items.itemtype = \'course\')',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'grade_grades',
                'LEFT',
                '{grade_grades}',
                '(grade_grades.itemid = grade_items.id AND ' .
                    'grade_grades.userid = base.userid)',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'grade_items'
            ),
        );

        // include some standard joins
        $this->add_core_user_tables($joinlist, 'base', 'userid');
        $this->add_core_course_tables($joinlist, 'base', 'course', 'INNER');
        // requires the course join
        $this->add_core_course_category_tables($joinlist,
            'course', 'category');
        $this->add_totara_job_tables($joinlist, 'base', 'userid');
        $this->add_core_tag_tables('core', 'course', $joinlist, 'base', 'course');
        $this->add_totara_cohort_course_tables($joinlist, 'base', 'course');

        return $joinlist;
    }

    protected function define_columnoptions() {
        global $DB;
        $columnoptions = array(
            new rb_column_option(
                'course_completion',
                'status',
                get_string('completionstatus', 'rb_source_course_completion'),
                'base.status',
                array('displayfunc' => 'course_completion_status')
            ),
            new rb_column_option(
                'course_completion',
                'iscomplete',
                get_string('iscompleteany', 'rb_source_course_completion'),
                'CASE WHEN base.status = ' . COMPLETION_STATUS_COMPLETE . ' OR base.status = ' . COMPLETION_STATUS_COMPLETEVIARPL . ' THEN 1 ELSE 0 END',
                array(
                    'displayfunc' => 'yes_or_no',
                    'dbdatatype' => 'boolean',
                    'defaultheading' => get_string('iscomplete', 'rb_source_course_completion'),
                )
            ),
            new rb_column_option(
                'course_completion',
                'isnotcomplete',
                get_string('isnotcomplete', 'rb_source_course_completion'),
                'CASE WHEN base.status = ' . COMPLETION_STATUS_COMPLETE . ' OR base.status = ' . COMPLETION_STATUS_COMPLETEVIARPL . ' THEN 0 ELSE 1 END',
                array(
                    'displayfunc' => 'yes_or_no',
                    'dbdatatype' => 'boolean',
                    'defaultheading' => get_string('isnotcomplete', 'rb_source_course_completion'),
                )
            ),
            new rb_column_option(
                'course_completion',
                'iscompletenorpl',
                get_string('iscompletenorpl', 'rb_source_course_completion'),
                'CASE WHEN base.status = ' . COMPLETION_STATUS_COMPLETE . ' THEN 1 ELSE 0 END',
                array(
                    'displayfunc' => 'yes_or_no',
                    'dbdatatype' => 'boolean',
                    'defaultheading' => get_string('iscomplete', 'rb_source_course_completion'),
                )
            ),
            new rb_column_option(
                'course_completion',
                'iscompleterpl',
                get_string('iscompleterpl', 'rb_source_course_completion'),
                'CASE WHEN base.status = ' . COMPLETION_STATUS_COMPLETEVIARPL . ' THEN 1 ELSE 0 END',
                array(
                    'displayfunc' => 'yes_or_no',
                    'dbdatatype' => 'boolean',
                    'defaultheading' => get_string('iscomplete', 'rb_source_course_completion'),
                )
            ),
            // RPL note column, will contain the note provided when manually awarding RPL completion,
            // or will be empty if not an RPL completion or if no note was provided.
            new rb_column_option(
                'course_completion',
                'rplnote',
                get_string('rplnote', 'rb_source_course_completion'),
                'rpl',
                array(
                    'displayfunc' => 'plaintext',
                )
            ),
            new rb_column_option(
                'course_completion',
                'isinprogress',
                get_string('isinprogress', 'rb_source_course_completion'),
                'CASE WHEN base.status = ' . COMPLETION_STATUS_INPROGRESS . ' THEN 1 ELSE 0 END',
                array(
                    'displayfunc' => 'yes_or_no',
                    'dbdatatype' => 'boolean',
                    'defaultheading' => get_string('isinprogress', 'rb_source_course_completion'),
                )
            ),
            new rb_column_option(
                'course_completion',
                'isnotyetstarted',
                get_string('isnotyetstarted', 'rb_source_course_completion'),
                'CASE WHEN base.status = ' . COMPLETION_STATUS_NOTYETSTARTED . ' THEN 1 ELSE 0 END',
                array(
                    'displayfunc' => 'yes_or_no',
                    'dbdatatype' => 'boolean',
                    'defaultheading' => get_string('isnotyetstarted', 'rb_source_course_completion'),
                )
            ),
            new rb_column_option(
                'course_completion',
                'completeddate',
                get_string('completiondate', 'rb_source_course_completion'),
                'base.timecompleted',
                array('displayfunc' => 'nice_date', 'dbdatatype' => 'timestamp')
            ),
            new rb_column_option(
                'course_completion',
                'starteddate',
                get_string('datestarted', 'rb_source_course_completion'),
                'base.timestarted',
                array('displayfunc' => 'nice_date', 'dbdatatype' => 'timestamp')
            ),
            new rb_column_option(
                'course_completion',
                'enrolleddate',
                get_string('dateenrolled', 'rb_source_course_completion'),
                'base.timeenrolled',
                array('displayfunc' => 'nice_date', 'dbdatatype' => 'timestamp')
            ),
            new rb_column_option(
                'course_completion',
                'enrolmenttype',
                get_string('courseenroltypes', 'totara_reportbuilder'),
                "(SELECT " . $DB->sql_group_concat('e.enrol', '|', 'e.enrol ASC') . "
                    FROM {enrol} e
                    JOIN {user_enrolments} ue ON ue.enrolid = e.id
                   WHERE ue.userid = base.userid AND e.courseid = base.course)",
                array(
                    'displayfunc' => 'enrolment_types_list',
                    'issubquery' => true,
                    'iscompound' => true,
                )
            ),
            new rb_column_option(
                'course_completion',
                'timecompletedsincestart',
                get_string('timetocompletesincestart', 'rb_source_course_completion'),
                "CASE WHEN base.timecompleted IS NULL OR base.timecompleted = 0 THEN null
                      ELSE base.timecompleted - base.timestarted END",
                array(
                    'displayfunc' => 'duration',
                    'dbdatatype' => 'integer'
                )
            ),
            new rb_column_option(
                'course_completion',
                'timecompletedsinceenrol',
                get_string('timetocompletesinceenrol', 'rb_source_course_completion'),
                "CASE WHEN base.timecompleted IS NULL OR base.timecompleted = 0 THEN null
                      ELSE base.timecompleted - base.timeenrolled END",
                array(
                    'displayfunc' => 'duration',
                    'dbdatatype' => 'integer'
                )
            ),
            new rb_column_option(
                'course_completion',
                'organisationid',
                get_string('completionorgid', 'rb_source_course_completion'),
                'base.organisationid',
                array('displayfunc' => 'integer')
            ),
            new rb_column_option(
                'course_completion',
                'organisationid2',
                get_string('completionorgid', 'rb_source_course_completion'),
                'base.organisationid',
                array('selectable' => false)
            ),
            new rb_column_option(
                'course_completion',
                'organisationpath',
                get_string('completionorgpath', 'rb_source_course_completion'),
                'completion_organisation.path',
                array('joins' => 'completion_organisation', 'selectable' => false)
            ),
            new rb_column_option(
                'course_completion',
                'organisation',
                get_string('completionorgname', 'rb_source_course_completion'),
                'completion_organisation.fullname',
                array('joins' => 'completion_organisation',
                      'dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'format_string')
            ),
            new rb_column_option(
                'course_completion',
                'positionid',
                get_string('completionposid', 'rb_source_course_completion'),
                'base.positionid',
                array('displayfunc' => 'integer')
            ),
            new rb_column_option(
                'course_completion',
                'positionid2',
                get_string('completionposid', 'rb_source_course_completion'),
                'base.positionid',
                array('selectable' => false)
            ),
            new rb_column_option(
                'course_completion',
                'positionpath',
                get_string('completionpospath', 'rb_source_course_completion'),
                'completion_position.path',
                array('joins' => 'completion_position', 'selectable' => false)
            ),
            new rb_column_option(
                'course_completion',
                'position',
                get_string('completionposname', 'rb_source_course_completion'),
                'completion_position.fullname',
                array('joins' => 'completion_position',
                      'dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'format_string')
            ),
            new rb_column_option(
                'course_completion',
                'grade',
                get_string('grade', 'rb_source_course_completion'),
                'CASE WHEN base.status = ' . COMPLETION_STATUS_COMPLETEVIARPL . ' THEN base.rplgrade
                      ELSE grade_grades.finalgrade END',
                array(
                    'joins' => 'grade_grades',
                    'extrafields' => array(
                        'maxgrade' => 'grade_grades.rawgrademax',
                        'mingrade' => 'grade_grades.rawgrademin',
                        'rplgrade' => 'base.rplgrade',
                        'status' => 'base.status'
                    ),
                    'displayfunc' => 'course_grade_percent',
                )
            ),
            new rb_column_option(
                'course_completion',
                'passgrade',
                get_string('passgrade', 'rb_source_course_completion'),
                'CASE WHEN grade_items.grademax = 0 THEN NULL
                      ELSE (((criteria.gradepass - grade_items.grademin) / (grade_items.grademax - grade_items.grademin)) * 100) END',
                array(
                    'joins' => ['criteria', 'grade_items'],
                    'displayfunc' => 'percent',
                )
            ),
            new rb_column_option(
                'course_completion',
                'gradestring',
                get_string('requiredgrade', 'rb_source_course_completion'),
                'CASE WHEN base.status = ' . COMPLETION_STATUS_COMPLETEVIARPL . ' THEN base.rplgrade
                      ELSE grade_grades.finalgrade END',
                array(
                    'joins' => array('criteria', 'grade_grades'),
                    'displayfunc' => 'course_grade_string',
                    'extrafields' => array(
                        'gradepass' => 'criteria.gradepass',
                        'grademax' => 'grade_items.grademax',
                        'grademin' => 'grade_items.grademin',
                    ),
                    'defaultheading' => get_string('grade', 'rb_source_course_completion'),
                )
            ),
            new rb_column_option(
                'course_completion',
                'progressnumeric',
                get_string('progressnumeric', 'rb_source_course_completion'),
                'base.status',
                array(
                    'displayfunc' => 'course_progress',
                    'extrafields' => array('numericonly' => 1, 'userid' => 'base.userid', 'courseid' => 'base.course'),
                    'defaultheading' => get_string('progress', 'rb_source_course_completion'),
                )
            ),
            new rb_column_option(
                'course_completion',
                'progresspercent',
                get_string('progresspercent', 'rb_source_course_completion'),
                'base.status',
                array(
                    'displayfunc' => 'course_progress',
                    'extrafields' => array('numericonly' => 0, 'userid' => 'base.userid', 'courseid' => 'base.course'),
                    'defaultheading' => get_string('progress', 'rb_source_course_completion'),
                )
            ),
        );

        // include some standard columns
        $this->add_core_user_columns($columnoptions);
        $this->add_core_course_columns($columnoptions);
        $this->add_core_course_category_columns($columnoptions);
        $this->add_totara_job_columns($columnoptions);
        $this->add_core_tag_columns('core', 'course', $columnoptions);
        $this->add_totara_cohort_course_columns($columnoptions);

        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = array(
            /*
            // array of rb_filter_option objects, e.g:
            new rb_filter_option(
                '',       // type
                '',       // value
                '',       // label
                '',       // filtertype
                array()   // options
            )
            */
            new rb_filter_option(
                'course_completion',
                'completeddate',
                get_string('datecompleted', 'rb_source_course_completion'),
                'date'
            ),
            new rb_filter_option(
                'course_completion',
                'starteddate',
                get_string('datestarted', 'rb_source_course_completion'),
                'date'
            ),
            new rb_filter_option(
                'course_completion',
                'enrolleddate',
                get_string('dateenrolled', 'rb_source_course_completion'),
                'date'
            ),
            new rb_filter_option(
                'course_completion',
                'status',
                get_string('completionstatus', 'rb_source_course_completion'),
                'multicheck',
                array(
                    'selectfunc' => 'completion_status_list',
                    'attributes' => rb_filter_option::select_width_limiter(),
                    'showcounts' => array(
                            'joins' => array("LEFT JOIN {course_completions} ccs_filter ON base.id = ccs_filter.id"),
                            'dataalias' => 'ccs_filter',
                            'datafield' => 'status')
                )
            ),
            new rb_filter_option(
                'course_completion',
                'iscomplete',
                get_string('iscompleteany', 'rb_source_course_completion'),
                'select',
                array(
                    'selectfunc' => 'yesno_list',
                    'simplemode' => true,
                )
            ),
            new rb_filter_option(
                'course_completion',
                'isnotcomplete',
                get_string('isnotcomplete', 'rb_source_course_completion'),
                'select',
                array(
                    'selectfunc' => 'yesno_list',
                    'simplemode' => true,
                )
            ),
            new rb_filter_option(
                'course_completion',
                'iscompletenorpl',
                get_string('iscompletenorpl', 'rb_source_course_completion'),
                'select',
                array(
                    'selectfunc' => 'yesno_list',
                    'simplemode' => true,
                )
            ),
            new rb_filter_option(
                'course_completion',
                'iscompleterpl',
                get_string('iscompleterpl', 'rb_source_course_completion'),
                'select',
                array(
                    'selectfunc' => 'yesno_list',
                    'simplemode' => true,
                )
            ),
            new rb_filter_option(
                'course_completion',
                'isinprogress',
                get_string('isinprogress', 'rb_source_course_completion'),
                'select',
                array(
                    'selectfunc' => 'yesno_list',
                    'simplemode' => true,
                )
            ),
            new rb_filter_option(
                'course_completion',
                'isnotyetstarted',
                get_string('isnotyetstarted', 'rb_source_course_completion'),
                'select',
                array(
                    'selectfunc' => 'yesno_list',
                    'simplemode' => true,
                )
            ),
            new rb_filter_option(
                'course_completion',
                'organisationid',
                get_string('officewhencompletedbasic', 'rb_source_course_completion'),
                'select',
                array(
                    'selectfunc' => 'organisations_list',
                    'attributes' => rb_filter_option::select_width_limiter(),
                )
            ),
            new rb_filter_option(
                'course_completion',
                'organisationpath',
                get_string('orgwhencompleted', 'rb_source_course_completion'),
                'hierarchy',
                array(
                    'hierarchytype' => 'org',
                )
            ),
            new rb_filter_option(
                'course_completion',
                'organisationid2',
                get_string('multiorgwhencompleted', 'rb_source_course_completion'),
                'hierarchy_multi',
                array(
                    'hierarchytype' => 'org',
                )
            ),
            new rb_filter_option(
                'course_completion',
                'positionid',
                get_string('poswhencompletedbasic', 'rb_source_course_completion'),
                'select',
                array(
                    'selectfunc' => 'positions_list',
                    'attributes' => rb_filter_option::select_width_limiter()
                )
            ),
            new rb_filter_option(
                'course_completion',
                'positionid2',
                get_string('multiposwhencompleted', 'rb_source_course_completion'),
                'hierarchy_multi',
                array(
                    'hierarchytype' => 'pos',
                )
            ),
            new rb_filter_option(
                'course_completion',
                'positionpath',
                get_string('poswhencompleted', 'rb_source_course_completion'),
                'hierarchy',
                array(
                    'hierarchytype' => 'pos',
                )
            ),
            new rb_filter_option(
                'course_completion',
                'grade',
                get_string('grade', 'rb_source_course_completion'),
                'number'
            ),
            new rb_filter_option(
                'course_completion',
                'passgrade',
                'Required Grade',
                'number'
            ),
            new rb_filter_option(
                'course_completion',
                'enrolled',
                get_string('isenrolled', 'rb_source_course_completion'),
                'enrol',
                array(),
                // special enrol filter requires a composite field
                array('course' => 'base.course', 'user' => 'base.userid')
            ),
            new rb_filter_option(
                'course_completion',
                'enrolmenttype',
                get_string('courseenroltypes', 'totara_reportbuilder'),
                'multicheck',
                array(
                    'cachingcompatible' => false, // Current filter code is not compatible with aggregated columns.
                    'concat' => true,
                    'selectfunc' => 'enrolment_types_list',
                    'attributes' => rb_filter_option::select_width_limiter(),
                    'simplemode' => false,
                    'showcounts' => false
                )
            ),
        );

        // include some standard filters
        $this->add_core_user_filters($filteroptions);
        $this->add_core_course_filters($filteroptions);
        $this->add_core_course_category_filters($filteroptions);
        $this->add_totara_job_filters($filteroptions, 'base', 'userid');
        $this->add_core_tag_filters('core', 'course', $filteroptions);
        $this->add_totara_cohort_course_filters($filteroptions);

        return $filteroptions;
    }

    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);

        $contentoptions[] = new rb_content_option(
            'completed_org',
            get_string('orgwhencompleted', 'rb_source_course_completion'),
            'completion_organisation.path',
            'completion_organisation'
        );

        $contentoptions[] = new rb_content_option(
            'date',
            get_string('completiondate', 'rb_source_course_completion'),
            'base.timecompleted'
        );

        return $contentoptions;
    }

    protected function define_paramoptions() {
        $paramoptions = array(
            new rb_param_option(
                'userid',       // parameter name
                'base.userid',  // field
                null            // joins
            ),
            new rb_param_option(
                'courseid',
                'base.course'
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
                'type' => 'course',
                'value' => 'courselink',
            ),
            array(
                'type' => 'job_assignment',
                'value' => 'allorganisationnames',
            ),
            array(
                'type' => 'course_completion',
                'value' => 'organisation',
            ),
            array(
                'type' => 'job_assignment',
                'value' => 'allpositionnames',
            ),
            array(
                'type' => 'course_completion',
                'value' => 'position',
            ),
            array(
                'type' => 'course_completion',
                'value' => 'status',
            ),
            array(
                'type' => 'course_completion',
                'value' => 'completeddate',
            ),
        );
        return $defaultcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = array(
            array(
                'type' => 'user',
                'value' => 'fullname',
            ),
            array(
                'type' => 'job_assignment',
                'value' => 'allorganisations',
                'advanced' => 1,
            ),
            array(
                'type' => 'course_completion',
                'value' => 'organisationpath',
                'advanced' => 1,
            ),
            array(
                'type' => 'job_assignment',
                'value' => 'allpositions',
                'advanced' => 1,
            ),
            array(
                'type' => 'course_completion',
                'value' => 'positionpath',
                'advanced' => 1,
            ),
            array(
                'type' => 'course',
                'value' => 'fullname',
                'advanced' => 1,
            ),
            array(
                'type' => 'course_category',
                'value' => 'path',
                'advanced' => 1,
            ),
            array(
                'type' => 'course_completion',
                'value' => 'completeddate',
                'advanced' => 1,
            ),
            array(
                'type' => 'course_completion',
                'value' => 'status',
                'advanced' => 1,
            ),
        );

        return $defaultfilters;
    }

    protected function define_requiredcolumns() {
        $requiredcolumns = array(
            /*
            // array of rb_column objects, e.g:
            new rb_column(
                '',         // type
                '',         // value
                '',         // heading
                '',         // field
                array()     // options
            )
            */
        );
        return $requiredcolumns;
    }

    //
    //
    // Source specific column display methods
    //
    //

    /**
     * Display for course completion status
     *
     * @deprecated Since Totara 12.0
     * @param $status
     * @param $row
     * @param $isexport
     * @return string
     */
    function rb_display_completion_status($status, $row, $isexport) {
        debugging('rb_source_course_completion::rb_display_completion_status has been deprecated since Totara 12.0. Use course_completion_status::display', DEBUG_DEVELOPER);
        global $CFG;
        require_once($CFG->dirroot.'/completion/completion_completion.php');
        global $COMPLETION_STATUS;

        if (!array_key_exists((int)$status, $COMPLETION_STATUS)) {
            return '';
        }
        $string = $COMPLETION_STATUS[(int)$status];
        if (empty($string)) {
            return '';
        } else {
            return get_string($string, 'completion');
        }
    }

    /**
     * Display for course progress
     *
     * @deprecated Since Totara 12.0
     * @param $status
     * @param $row
     * @param $isexport
     * @return mixed|string
     */
    function rb_display_course_progress($status, $row, $isexport) {
        debugging('rb_source_course_completion::rb_display_course_progress has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        if ($isexport) {
            global $PAGE;

            $renderer = $PAGE->get_renderer('totara_core');
            $content = (array)$renderer->export_course_progress_for_template($row->userid, $row->courseid, $status);

            $percent = '';
            if (isset($content['percent'])){
                $percent = $content['percent'];
            } else if (isset($content['statustext'])) {
                $percent = $content['statustext'];
            }

            if ($row->numericonly || !is_numeric($percent)) {
                return $percent;
            }

            return get_string('xpercentcomplete', 'totara_core', $percent);
        }

        return totara_display_course_progress_bar($row->userid, $row->courseid, $status);
    }

    //
    //
    // Source specific filter display methods
    //
    //

    function rb_filter_completion_status_list() {
        global $CFG;
        require_once($CFG->dirroot.'/completion/completion_completion.php');
        global $COMPLETION_STATUS;

        $statuslist = array();
        foreach ($COMPLETION_STATUS as $key => $value) {
            $statuslist[(string)$key] = get_string($value, 'completion');
        }
        return $statuslist;
    }

    /**
     * Get all the enabled enrolment types for the filter
     *
     * @return array
     */
    function rb_filter_enrolment_types_list() : array {
        global $CFG;
        require_once($CFG->libdir . '/enrollib.php');

        $types = [];
        $plugins = enrol_get_plugins(true);

        foreach ($plugins as $key => $plugin) {
            if ($key == 'guest') {
                continue;
            }

            $types[$key] = $plugin->get_instance_name(null);
        }

        return $types;
    }

} // end of rb_source_course_completion class

