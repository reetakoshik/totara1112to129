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
 * @author Ben Lobo <ben@benlobo.co.uk>
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @package totara
 * @subpackage plan
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

// needed for approval constants etc
require_once($CFG->dirroot . '/totara/plan/lib.php');
// needed for instatiating and checking programs
require_once($CFG->dirroot . '/totara/program/lib.php');

class rb_source_dp_program_recurring extends rb_base_source {
    use \totara_job\rb\source\report_trait;
    use \totara_reportbuilder\rb\source\report_trait;

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $this->add_global_report_restriction_join('base', 'userid');

        $this->base = '{prog_completion_history}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_dp_program_recurring');
        // only consider whole programs - not courseset completion
        $this->sourcewhere = 'base.coursesetid = 0';
        $this->usedcomponents[] = 'totara_program';
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

        $joinlist = array(
            new rb_join(
                'prog', // table alias
                'INNER', // type of join
                '{prog}',
                '(base.programid = prog.id AND prog.certifid IS NULL)', // how it is joined
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
        );

        $joinlist[] =  new rb_join(
                'completion_organisation',
                'LEFT',
                '{org}',
                'completion_organisation.id = base.organisationid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
        );
        $this->add_context_tables($joinlist, 'base', 'programid', CONTEXT_PROGRAM, 'INNER');
        $this->add_core_user_tables($joinlist, 'base', 'userid');
        $this->add_totara_job_tables($joinlist, 'base', 'userid');

        return $joinlist;
    }

    protected function define_columnoptions() {
        $columnoptions = array();

        $columnoptions[] = new rb_column_option(
            'program',
            'fullname',
            get_string('programname', 'totara_program'),
            "prog.fullname",
            array('joins' => 'prog',
                  'dbdatatype' => 'char',
                  'outputformat' => 'text',
                  'displayfunc' => 'format_string')
        );
        $columnoptions[] = new rb_column_option(
            'program',
            'proglinkicon',
            get_string('prognamelinkedicon', 'totara_program'),
            "prog.fullname",
            array(
                'joins' => 'prog',
                'displayfunc' => 'program_icon_link',
                'defaultheading' => get_string('programname', 'totara_program'),
                'extrafields' => array(
                    'programid' => "prog.id",
                    'userid' => 'base.userid',
                )
            )
        );
        $columnoptions[] = new rb_column_option(
            'program',
            'shortname',
            get_string('programshortname', 'totara_program'),
            "prog.shortname",
            array('joins' => 'prog',
                  'dbdatatype' => 'char',
                  'outputformat' => 'text',
                  'displayfunc' => 'plaintext')
        );
        $columnoptions[] = new rb_column_option(
            'program',
            'idnumber',
            get_string('programidnumber', 'totara_program'),
            "prog.idnumber",
            array('joins' => 'prog',
                  'displayfunc' => 'plaintext',
                  'dbdatatype' => 'char',
                  'outputformat' => 'text')
        );
        $columnoptions[] = new rb_column_option(
            'program',
            'id',
            get_string('programid', 'totara_program'),
            "base.programid",
            array('displayfunc' => 'integer')
        );

        $columnoptions[] = new rb_column_option(
            'program_completion_history',
            'courselink',
            get_string('coursenamelink', 'totara_program'),
            "base.recurringcourseid",
            array(
                'displayfunc' => 'program_course_name_link',
            )
        );

        $columnoptions[] = new rb_column_option(
            'program_completion_history',
            'status',
            get_string('completionstatus', 'totara_program'),
            "base.status",
            array(
                'displayfunc' => 'program_completion_status',
                'extrafields' => array(
                    'programid' => "base.id",
                    'userid' => "base.userid"
                )
            )
        );

        $columnoptions[] = new rb_column_option(
            'program_completion_history',
            'timecompleted',
            get_string('completiondate', 'totara_program'),
            "base.timecompleted",
            array(
                'displayfunc' => 'program_completion_date',
                'dbdatatype' => 'timestamp',
            )
        );

        $columnoptions[] = new rb_column_option(
            'program_completion_history',
            'timedue',
            get_string('duedate', 'totara_program'),
            "base.timedue",
            array(
                'displayfunc' => 'program_completion_date',
                'dbdatatype' => 'timestamp',
            )
        );

        $this->add_core_user_columns($columnoptions);
        $this->add_totara_job_columns($columnoptions);

        return $columnoptions;
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
        debugging('rb_source_dp_program_recurring::rb_display_link_program_icon has been deprecated since Totara 12.0. Use totara_program\rb\display\program_icon_link::display', DEBUG_DEVELOPER);
        if ($isexport) {
            return $programname;
        }

        return prog_display_link_icon($row->programid, $row->userid);
    }

    /**
     * Display program completion status
     *
     * @deprecated Since Totara 12.0
     * @param $status
     * @param $row
     * @return string
     */
    function rb_display_program_completion_status($status,$row) {
        debugging('rb_source_dp_program_recurring::rb_display_program_completion_status has been deprecated since Totara 12.0. Use totara_program\rb\display\program_completion_status::display', DEBUG_DEVELOPER);
        global $OUTPUT;

        if ($status == STATUS_PROGRAM_COMPLETE) {
            return get_string('complete', 'totara_program');
        } else if ($status == STATUS_PROGRAM_INCOMPLETE) {
            return $OUTPUT->error_text(get_string('incomplete', 'totara_program'));
        } else {
            return get_string('unknownstatus', 'totara_program');
        }

    }

    /**
     * Display completion date
     *
     * @deprecated Since Totara 12.0
     * @param $time
     * @return string
     */
    function rb_display_completion_date($time) {
        debugging('rb_source_dp_program_recurring::rb_display_completion_date has been deprecated since Totara 12.0. Use totara_program\rb\display\program_completion_date::display', DEBUG_DEVELOPER);
        if ($time == 0) {
            return '';
        } else {
            return userdate($time, get_string('strftimedatefulllong', 'langconfig'), 99, false);
        }
    }

    /**
     * Display course name and link
     *
     * @deprecated Since Totara 12.0
     * @param $courseid
     * @return string
     */
    function rb_display_link_course_name($courseid) {
        debugging('rb_source_dp_program_recurring::rb_display_link_course_name has been deprecated since Totara 12.0. Use totara_program\rb\display\program_course_name_link::display', DEBUG_DEVELOPER);
        global $DB, $OUTPUT;

        $html = '';

        if ($course = $DB->get_record('course', array('id' => $courseid))) {
            $html = $OUTPUT->action_link(new moodle_url('/course/view.php', array('id' => $course->id)), format_string($course->fullname));
        } else {
            $html = get_string('coursenotfound', 'totara_plan');
        }

        return $html;
    }

    protected function define_filteroptions() {
        $filteroptions = array();
        $filteroptions[] = new rb_filter_option(
                'program',
                'fullname',
                get_string('programname', 'totara_program'),
                'text'
            );
        $filteroptions[] = new rb_filter_option(
                'program',
                'shortname',
                get_string('programshortname', 'totara_program'),
                'text'
            );
        $filteroptions[] = new rb_filter_option(
                'program',
                'idnumber',
                get_string('programidnumber', 'totara_program'),
                'text'
            );
        $filteroptions[] = new rb_filter_option(
                'program',
                'id',
                get_string('programid', 'totara_program'),
                'int'
            );
        $filteroptions[] = new rb_filter_option(
                'program_completion_history',
                'timedue',
                get_string('programduedate', 'totara_program'),
                'date'
            );
        $filteroptions[] = new rb_filter_option(
                'program_completion_history',
                'timecompleted',
                get_string('completiondate', 'totara_program'),
                'date'
            );

        $this->add_core_user_filters($filteroptions);
        $this->add_totara_job_filters($filteroptions, 'base', 'userid');

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
                'base.programid'
            ),
            new rb_param_option(
                'visible',
                'prog.visible',
                'prog'
            ),
            new rb_param_option(
                'userid',
                'base.userid'
            ),
        );

        $paramoptions[] = new rb_param_option(
                'programstatus',
                'base.status'
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
                'type' => 'program_completion_history',
                'value' => 'courselink',
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
            "prog.id",
            array('joins' => 'prog')
        );

        $requiredcolumns[] = new rb_column(
            'visibility',
            'visible',
            '',
            "prog.visible",
            array('joins' => 'prog')
        );

        $requiredcolumns[] = new rb_column(
            'visibility',
            'audiencevisible',
            '',
            "prog.audiencevisible",
            array('joins' => 'prog')
        );

        $requiredcolumns[] = new rb_column(
            'prog',
            'available',
            '',
            "prog.available",
            array('joins' => 'prog')
        );

        $requiredcolumns[] = new rb_column(
            'prog',
            'availablefrom',
            '',
            "prog.availablefrom",
            array('joins' => 'prog')
        );

        $requiredcolumns[] = new rb_column(
            'prog',
            'availableuntil',
            '',
            "prog.availableuntil",
            array('joins' => 'prog')
        );

        $requiredcolumns[] = new rb_column(
            'visibility',
            'completionstatus',
            '',
            "base.status"
        );

        return $requiredcolumns;
    }

    public function post_config(reportbuilder $report) {
        // Visibility checks are only applied if viewing a single user's records.
        if ($report->get_param_value('userid')) {
            list($visibilitysql, $whereparams) = $report->post_config_visibility_where('program', 'prog',
                $report->get_param_value('userid'), true);
            $completionstatus = $report->get_field('visibility', 'completionstatus', 'base.status');
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
} // end of rb_source_dp_program_recurring class
