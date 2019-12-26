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

// needed for approval constants etc
require_once($CFG->dirroot . '/totara/plan/lib.php');
// needed for instatiating and checking programs
require_once($CFG->dirroot . '/totara/program/lib.php');

class rb_source_dp_program extends rb_base_source {
    use \core_course\rb\source\report_trait;
    use \totara_job\rb\source\report_trait;
    use \totara_reportbuilder\rb\source\report_trait;
    use \totara_cohort\rb\source\report_trait;

    public $instancetype;

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $this->add_global_report_restriction_join('program_completion', 'userid');

        $this->base = '{prog}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->instancetype = 'program';
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_dp_program');
        $this->sourcewhere = 'base.certifid IS NULL';
        $this->usedcomponents[] = 'totara_program';
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
        global $DB;

        $joinlist = array(
            new rb_join(
                'program_completion', // table alias
                'INNER', // type of join
                '{prog_completion}',
                'base.id = program_completion.programid AND program_completion.coursesetid = 0', //how it is joined
                REPORT_BUILDER_RELATION_ONE_TO_MANY,
                array('base')
            ),
            new rb_join(
                'prog_user_assignment', // table alias
                'LEFT', // type of join
                '(SELECT pua2.*
                  FROM (SELECT MAX(id) as id, programid, userid
                        FROM {prog_user_assignment}
                        GROUP BY userid, programid) AS pua
                  INNER JOIN {prog_user_assignment} AS pua2
                    ON pua2.id = pua.id)',
                'program_completion.programid = prog_user_assignment.programid AND program_completion.userid = prog_user_assignment.userid', //how it is joined
                REPORT_BUILDER_RELATION_ONE_TO_MANY,
                array('program_completion')
            ),
            new rb_join(
                'dp_plan', // table alias
                'LEFT', // type of join
                '{dp_plan}', // actual table name
                'dp_plan.id = prog_plan_assignment.planid', //how it is joined
                REPORT_BUILDER_RELATION_ONE_TO_MANY,
                array('prog_plan_assignment')
            ),
            new rb_join(
                'prog_plan_assignment', // table alias
                'LEFT', // type of join
                '{dp_plan_program_assignment}', // actual table name
                'base.id = prog_plan_assignment.programid = ', //how it is joined
                REPORT_BUILDER_RELATION_ONE_TO_MANY,
                array('base')
            ),
            new rb_join(
                'program_completion_history',
                'LEFT',
                '(SELECT ' . $DB->sql_concat('userid', 'programid') . ' uniqueid,
                    userid,
                    programid,
                    COUNT(id) historycount
                    FROM {prog_completion_history} program_completion_history
                    GROUP BY userid, programid)',
                '(base.id = program_completion_history.programid AND ' .
                    'prog_user_assignment.userid = program_completion_history.userid)',
                REPORT_BUILDER_RELATION_ONE_TO_MANY,
                array('base', 'prog_user_assignment')
            ),
        );
        $joinlist[] =  new rb_join(
                'completion_organisation',
                'LEFT',
                '{org}',
                'completion_organisation.id = program_completion.organisationid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
        );
        $this->add_context_tables($joinlist, 'base', 'id', CONTEXT_PROGRAM, 'INNER');
        $this->add_core_course_category_tables($joinlist, 'base', 'category');
        $this->add_totara_cohort_program_tables($joinlist, 'base', 'id');
        $this->add_core_user_tables($joinlist, 'program_completion', 'userid');
        $this->add_totara_job_tables($joinlist, 'program_completion', 'userid');

        return $joinlist;
    }

    protected function define_columnoptions() {
        $columnoptions = array();

        $columnoptions[] = new rb_column_option(
            'program',
            'fullname',
            get_string('programname', 'totara_program'),
            "base.fullname",
            array('joins' => 'base',
                  'dbdatatype' => 'char',
                  'outputformat' => 'text',
                  'displayfunc' => 'format_string')
        );
        $columnoptions[] = new rb_column_option(
            'program',
            'shortname',
            get_string('programshortname', 'totara_program'),
            "base.shortname",
            array('joins' => 'base',
                  'dbdatatype' => 'char',
                  'outputformat' => 'text',
                  'displayfunc' => 'plaintext')
        );
        $columnoptions[] = new rb_column_option(
            'program',
            'idnumber',
           get_string('programidnumber', 'totara_program'),
            "base.idnumber",
            array('joins' => 'base',
                  'displayfunc' => 'plaintext',
                  'dbdatatype' => 'char',
                  'outputformat' => 'text')
        );
        $columnoptions[] = new rb_column_option(
            'program',
            'id',
            get_string('programid', 'totara_program'),
            "base.id",
            array('joins' => 'base',
                  'displayfunc' => 'integer')
        );
        $columnoptions[] = new rb_column_option(
            'program',
            'proglinkicon',
            get_string('prognamelinkedicon', 'totara_program'),
            "base.fullname",
            array(
                'joins' => 'program_completion',
                'displayfunc' => 'program_icon_link',
                'defaultheading' => get_string('programname', 'totara_program'),
                'extrafields' => array(
                    'programid' => "base.id",
                    'userid' => "program_completion.userid"
                )
            )
        );

        $columnoptions[] = new rb_column_option(
            'program',
            'timedue',
            get_string('programduedate', 'totara_program'),
            "program_completion.timedue",
            array(
                'joins' => 'program_completion',
                'dbdatatype' => 'timestamp',
                'displayfunc' => 'programduedate',
                'extrafields' => array(
                    'programid' => 'base.id',
                    'userid' => 'program_completion.userid',
                    'status' => 'program_completion.status',
                )
            )
        );

        $columnoptions[] = new rb_column_option(
            'program',
            'mandatory',
            get_string('programmandatory', 'totara_program'),
            "prog_user_assignment.id",
            array(
                'joins' => 'prog_user_assignment',
                'displayfunc' => 'program_mandatory_status',
            )
        );

        $columnoptions[] = new rb_column_option(
            'program',
            'recurring',
            get_string('programrecurring', 'totara_program'),
            "base.id",
            array(
                'joins' => 'program_completion',
                'displayfunc' => 'program_recurring_status',
                'extrafields' => array(
                    'userid' => "program_completion.userid"
                )
            )
        );

        $columnoptions[] = new rb_column_option(
            'program_completion',
            'status',
            get_string('progress', 'rb_source_dp_course'),
            "program_completion.status",
            array(
                'joins' => array('program_completion'),
                'displayfunc' => 'program_completion_progress',
                'extrafields' => array(
                    'programid' => "base.id",
                    'userid' => "program_completion.userid",
                    "stringexport" => 0
                )
            )
        );
        $columnoptions[] = new rb_column_option(
            'program_completion',
            'progresspercentage',
            get_string('progresspercentage', 'rb_source_dp_course'),
            "program_completion.status",
            array(
                'joins' => array('program_completion'),
                'displayfunc' => 'program_completion_progress',
                'extrafields' => array(
                    'programid' => "base.id",
                    'userid' => "program_completion.userid",
                    "stringexport" => 1
                )
            )
        );
        $columnoptions[] = new rb_column_option(
            'program_completion',
            'starteddate',
            get_string('datestarted', 'rb_source_dp_program'),
            'program_completion.timestarted',
            array('joins' => array('program_completion'), 'displayfunc' => 'nice_date')
        );
        $columnoptions[] = new rb_column_option(
            'program_completion',
            'assigneddate',
            get_string('dateassigned', 'rb_source_dp_program'),
            'program_completion.timecreated',
            array('joins' => array('program_completion'), 'displayfunc' => 'nice_date')
        );
        $columnoptions[] = new rb_column_option(
            'program_completion',
            'completeddate',
            get_string('completeddate', 'rb_source_program_completion'),
            'program_completion.timecompleted',
            array('joins' => array('program_completion'), 'displayfunc' => 'nice_date')
        );
        $columnoptions[] = new rb_column_option(
            'program_completion_history',
            'program_previous_completion',
            get_string('program_previous_completion', 'rb_source_dp_program'),
            'program_completion_history.historycount',
            array(
                'joins' => 'program_completion_history',
                'defaultheading' => get_string('program_previous_completion', 'rb_source_dp_program'),
                'displayfunc' => 'program_previous_completion',
                'extrafields' => array(
                    'program_id' => "base.id",
                    'program_fullname' => "base.fullname",
                    'userid' => "program_completion.userid"
                ),
            )
        );
        $columnoptions[] = new rb_column_option(
            'program_completion_history',
            'program_completion_history_count',
            get_string('program_completion_history_count', 'rb_source_dp_program'),
            'program_completion_history.historycount',
            array(
                'joins' => 'program_completion_history',
                'dbdatatype' => 'integer',
                'displayfunc' => 'integer'
            )
        );

        // Include some standard columns.
        $this->add_core_user_columns($columnoptions);
        $this->add_totara_job_columns($columnoptions);
        $this->add_core_course_category_columns($columnoptions, 'course_category', 'base');
        $this->add_totara_cohort_program_columns($columnoptions);

        return $columnoptions;
    }

    /**
     * Display program progress
     *
     * @deprecated Since Totara 12.0
     * @param $status
     * @param $row
     * @param bool $export
     * @return string
     */
    public function rb_display_program_completion_progress($status, $row, $export=false) {
        debugging('rb_source_dp_program::rb_display_program_completion_progress has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        $progress = prog_display_progress($row->programid, $row->userid, CERTIFPATH_STD, $export);
        if ($export && !is_empty($row->stringexport) && is_numeric($progress)) {
            return get_string('xpercentcomplete', 'totara_core', $progress);
        } else {
            return $progress;
        }
    }

    /**
     * Reformat a timestamp into a date, handling -1 which is used by program code for no date.
     *
     * If not -1 just call the regular date display function.
     *
     * @deprecated Since Totara 12.0
     * @param integer $date Unix timestamp
     * @param object $row Object containing all other fields for this row
     *
     * @return string Date in a nice format
     */
    public function rb_display_prog_date($date, $row) {
        debugging('rb_source_dp_program::rb_display_prog_date has been deprecated since Totara 12.0', DEBUG_DEVELOPER);

        if (is_numeric($date) && $date != 0 && $date != -1) {
            return userdate($date, get_string('strfdateshortmonth', 'langconfig'));
        } else {
            return '';
        }
    }

    /**
     * Is mandatory
     *
     * @deprecated Since Totara 12.0
     * @param $id
     * @return string
     */
    function rb_display_mandatory_status($id) {
        debugging('rb_source_dp_program::rb_display_mandatory_status has been deprecated since Totara 12.0. Use totara_program\rb\display\program_mandatory_status::display', DEBUG_DEVELOPER);
        global $OUTPUT;
        if (!empty($id)) {
            return $OUTPUT->pix_icon('/i/valid', get_string('yes'));
        }
        return get_string('no');
    }

    /**
     * Is recurring
     *
     * @deprecated Since Totara 12.0
     * @param $programid
     * @param $row
     * @return string
     */
    function rb_display_recurring_status($programid, $row) {
        debugging('rb_source_dp_program::rb_display_recurring_status has been deprecated since Totara 12.0. Use totara_program\rb\display\program_recurring_status::display', DEBUG_DEVELOPER);
        global $OUTPUT;

        $userid = $row->userid;

        $program_content = new prog_content($programid);
        $coursesets = $program_content->get_course_sets();
        if (isset($coursesets[0])) {
            $courseset = $coursesets[0];
            if ($courseset->is_recurring()) {
                $recurringcourse = $courseset->course;
                $link = get_string('yes');
                $link .= $OUTPUT->action_link(new moodle_url('/totara/plan/record/programs_recurring.php', array('programid' => $programid, 'userid' => $userid)), get_string('viewrecurringprogramhistory', 'totara_program'));
                return $link;
            }
        }
        return get_string('no');
    }

    /**
     * Display program icon with name and link
     *
     * @deprecated Since Totara 12.0
     * @param $programname
     * @param $row
     * @param bool $isexport
     * @return string
     */
    function rb_display_link_program_icon($programname, $row, $isexport = false) {
        debugging('rb_source_dp_program::rb_display_link_program_icon has been deprecated since Totara 12.0. Use totara_program\rb\display\program_icon_link::display', DEBUG_DEVELOPER);
        if ($isexport) {
            return $programname;
        }

        return prog_display_link_icon($row->programid, $row->userid);
    }

    /**
     * Display for previous completions
     *
     * @deprecated Since Totara 12.0
     * @param $count
     * @param $row
     * @return int
     */
    public function rb_display_program_previous_completion($count, $row) {
        debugging('rb_source_dp_program::rb_display_program_previous_completion has been deprecated since Totara 12.0. Use totara_program\rb\display\program_previous_completions::display', DEBUG_DEVELOPER);
        global $OUTPUT;
        if (!$count) {
            return 0;
        }
        $description = html_writer::span(get_string('viewpreviouscompletions', 'rb_source_dp_program', $row->program_fullname), 'sr-only');
        return $OUTPUT->action_link(new moodle_url('/totara/plan/record/programs.php',
                array('program_id' => $row->program_id, 'userid' => $row->userid, 'history' => 1)), $count . $description);
    }

    protected function define_filteroptions() {
        $filteroptions = array(
            new rb_filter_option(
                'program',
                'fullname',
                get_string('programname', 'totara_program'),
                'text'
            )
        );
        $filteroptions[] = new rb_filter_option(
            'program_completion_history',
            'program_completion_history_count',
            get_string('program_completion_history_count', 'rb_source_dp_program'),
            'number'
        );
        $filteroptions[] = new rb_filter_option(
            'program_completion',
            'completeddate',
            get_string('completeddate', 'rb_source_program_completion'),
            'date'
        );
        $this->add_core_user_filters($filteroptions);
        $this->add_totara_job_filters($filteroptions, 'program_completion', 'userid');
        $this->add_core_course_category_filters($filteroptions, 'base', 'category');
        $this->add_totara_cohort_program_filters($filteroptions, 'totara_program');

        return $filteroptions;
    }

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
        $paramoptions = array(
            new rb_param_option(
                'programid',
                'base.id'
            ),
            new rb_param_option(
                'visible',
                'base.visible'
            ),
            new rb_param_option(
                'category',
                'base.category'
            ),
            new rb_param_option(
                'userid',
                'program_completion.userid',
                'program_completion'
            ),
        );

        $paramoptions[] = new rb_param_option(
                'programstatus',
                'program_completion.status',
                'program_completion'
        );

        $paramoptions[] = new rb_param_option(
            'exceptionstatus',
            'CASE WHEN prog_user_assignment.exceptionstatus IN (' . PROGRAM_EXCEPTION_RAISED . ',' . PROGRAM_EXCEPTION_DISMISSED .')
                THEN 1 ELSE 0 END',
            'prog_user_assignment',
            'int'
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
                'type' => 'program',
                'value' => 'proglinkicon',
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
                'type' => 'program',
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

    protected function define_requiredcolumns() {
        $requiredcolumns = array();

        $requiredcolumns[] = new rb_column(
            'ctx',
            'id',
            '',
            "ctx.id",
            array('joins' => 'ctx')
        );

        $requiredcolumns[] = new rb_column(
            'visibility',
            'id',
            '',
            "base.id"
        );

        $requiredcolumns[] = new rb_column(
            'visibility',
            'visible',
            '',
            "base.visible"
        );

        $requiredcolumns[] = new rb_column(
            'visibility',
            'audiencevisible',
            '',
            "base.audiencevisible"
        );

        $requiredcolumns[] = new rb_column(
            'base',
            'available',
            '',
            "base.available"
        );

        $requiredcolumns[] = new rb_column(
            'base',
            'availablefrom',
            '',
            "base.availablefrom"
        );

        $requiredcolumns[] = new rb_column(
            'base',
            'availableuntil',
            '',
            "base.availableuntil"
        );

        $requiredcolumns[] = new rb_column(
            'visibility',
            'completionstatus',
            '',
            "program_completion.status",
            array(
                'joins' => array('program_completion')
            )
        );

        return $requiredcolumns;
    }

    public function post_config(reportbuilder $report) {
        // Visibility checks are only applied if viewing a single user's records.
        if ($report->get_param_value('userid')) {
            list($visibilitysql, $whereparams) = $report->post_config_visibility_where('program', 'base',
                $report->get_param_value('userid'), true);
            $completionstatus = $report->get_field('visibility', 'completionstatus', 'program_completion.status');
            $wheresql = "(({$visibilitysql}) OR ({$completionstatus} > :incomplete))";
            $whereparams['incomplete'] = STATUS_PROGRAM_INCOMPLETE;
            $report->set_post_config_restrictions(array($wheresql, $whereparams));
        }
    }

    /**
     * Check if the report source is disabled and should be ignored.
     *
     * @return boolean If the report should be ignored of not.
     */
    public static function is_source_ignored() {
        return (!totara_feature_visible('recordoflearning') or !totara_feature_visible('programs'));
    }
} // end of rb_source_courses class
