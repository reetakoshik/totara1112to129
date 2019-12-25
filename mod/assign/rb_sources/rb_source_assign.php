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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package mod_assign
 */

defined('MOODLE_INTERNAL') || die();

class rb_source_assign extends rb_base_source {
    use \core_course\rb\source\report_trait;

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $this->add_global_report_restriction_join('base', 'userid', 'auser');

        $this->base = '{assign_submission}';
        $this->usedcomponents[] = 'mod_assign';
        $this->usedcomponents[] = 'totara_cohort';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_assign');

        parent::__construct();
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return true;
    }

    /**
     * Define join list
     * @return array
     */
    protected function define_joinlist() {

        $joinlist = array(
            // Join assignment.
            new rb_join(
                'assign',
                'INNER',
                '{assign}',
                'assign.id = base.assignment',
                REPORT_BUILDER_RELATION_MANY_TO_ONE
            ),

            // Join assignment grade.
            new rb_join(
                'assign_grades',
                'LEFT',
                '{assign_grades}',
                'assign.id = assign_grades.assignment AND base.userid = assign_grades.userid AND base.attemptnumber = assign_grades.attemptnumber',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'assign'
            ),

            // Join grade_items.
            new rb_join(
                'grade_items',
                'INNER',
                '{grade_items}',
                'grade_items.courseid = assign.course AND grade_items.itemmodule = \'assign\' AND grade_items.iteminstance = assign.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'assign'
            ),

            // Join grade_grades.
            new rb_join(
                'grade_grades',
                'LEFT',
                '{grade_grades}',
                'grade_grades.itemid = grade_items.id AND grade_grades.userid = base.userid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'grade_items'
            ),

            // Join scale.
            new rb_join(
                'scale',
                'LEFT',
                '{scale}',
                'scale.id = grade_items.scaleid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'grade_items'
            ),

            // Join feedback comments.
            new rb_join(
                'assign_comments',
                'LEFT',
                '{assignfeedback_comments}',
                'assign_comments.assignment = assign.id AND assign_comments.grade = assign_grades.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                array('assign', 'assign_grades')
            )
        );

        // join users, courses and categories
        $this->add_core_user_tables($joinlist, 'base', 'userid');
        $this->add_core_course_tables($joinlist, 'assign', 'course');
        $this->add_core_course_category_tables($joinlist, 'course', 'category');

        return $joinlist;
    }

    /**
     * define column options
     * @return array
     */
    protected function define_columnoptions() {
        global $CFG;
        include_once($CFG->dirroot.'/mod/assign/locallib.php');

        $columnoptions = array(
            // Assignment name.
            new rb_column_option(
                'assignment',
                'name',
                get_string('assignmentname', 'rb_source_assign'),
                'assign.name',
                array(
                    'joins' => 'assign',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string'
                )
            ),

            // Assignment intro.
            new rb_column_option(
                'assignment',
                'intro',
                get_string('assignmentintro', 'rb_source_assign'),
                'assign.intro',
                array(
                    'joins' => 'assign',
                    'dbdatatype' => 'text',
                    'outputformat' => 'text',
                    'displayfunc' => 'editor_textarea'
                )
            ),

            // Assignment status.
            new rb_column_option(
                'assignment',
                'status',
                get_string('submissionstatus', 'rb_source_assign'),
                "CASE WHEN assign_grades.grade >= 0 THEN 'graded'
                      WHEN base.status = '" . ASSIGN_SUBMISSION_STATUS_SUBMITTED . "' THEN 'submitted'
                      WHEN base.status = '" . ASSIGN_SUBMISSION_STATUS_DRAFT . "' THEN 'draft'
                      ELSE 'notsubmitted' END",
                array(
                    'joins' => 'assign_grades',
                    'displayfunc' => 'assign_submission_status',
                    'dbdatatype' => 'text',
                    'outputformat' => 'text'
                )
            ),

            // Grade scale values.
            new rb_column_option(
                'scale',
                'scale_values',
                get_string('gradescalevalues', 'rb_source_assign'),
                'scale.scale',
                array(
                    'displayfunc' => 'assign_scale_values',
                    'joins' => 'scale'
                )
            ),

            // Submission grade.
            new rb_column_option(
                'base',
                'grade',
                get_string('submissiongrade', 'rb_source_assign'),
                'assign_grades.grade',
                array(
                    'displayfunc' => 'assign_submission_grade',
                    'joins' => array('assign_grades', 'assign'),
                    'extrafields' => array(
                        'scale_values' => 'scale.scale',
                        'assign_grade' => 'assign.grade'
                    )
                )
            ),

            // Feedback comment.
            new rb_column_option(
                'base',
                'comment',
                get_string('feedbackcomment', 'rb_source_assign'),
                'assign_comments.commenttext',
                array(
                    'joins' => 'assign_comments',
                    'dbdatatype' => 'text',
                    'outputformat' => 'text',
                    'displayfunc' => 'editor_textarea'
                )
            ),

            // Submission last modified date.
            new rb_column_option(
                'base',
                'timemodified',
                get_string('lastmodifiedsubmission', 'rb_source_assign'),
                'base.timemodified',
                array(
                    'displayfunc' => 'nice_datetime'
                )
            ),

            // Grade last modified date.
            new rb_column_option(
                'base',
                'timemarked',
                get_string('lastmodifiedgrade', 'rb_source_assign'),
                'assign_grades.timemodified',
                array(
                    'displayfunc' => 'nice_datetime',
                    'joins' => 'assign_grades'
                )
            ),

            // Max grade.
            new rb_column_option(
                'grade_grades',
                'maxgrade',
                get_string('maxgrade', 'rb_source_assign'),
                'grade_grades.rawgrademax',
                array(
                    'displayfunc' => 'assign_max_grade',
                    'joins' => array('grade_grades', 'assign'),
                    'extrafields' => array(
                        'scale_values' => 'scale.scale',
                        'assign_grade' => 'assign.grade'
                    )
                )
            ),

            // Min grade.
            new rb_column_option(
                'grade_grades',
                'mingrade',
                get_string('mingrade', 'rb_source_assign'),
                'grade_grades.rawgrademin',
                array(
                    'displayfunc' => 'assign_min_grade',
                    'joins' => array('grade_grades', 'assign'),
                    'extrafields' => array(
                        'scale_values' => 'scale.scale',
                        'assign_grade' => 'assign.grade'
                    )
                )
            )
        );

        // User, course and category fields.
        $this->add_core_user_columns($columnoptions);
        $this->add_core_course_columns($columnoptions);
        $this->add_core_course_category_columns($columnoptions);

        return $columnoptions;
    }

    /**
     * define filter options
     * @return array
     */
    protected function define_filteroptions() {

        $filteroptions = array(
            // Assignment columns.
            new rb_filter_option(
                'assignment',
                'name',
                get_string('assignmentname', 'rb_source_assign'),
                'text'
            ),
            new rb_filter_option(
                'assignment',
                'intro',
                get_string('assignmentintro', 'rb_source_assign'),
                'text'
            ),

            // Submission status.
            new rb_filter_option(
                'assignment',
                'status',
                get_string('submissionstatus', 'rb_source_assign'),
                'select',
                array(
                    'selectchoices' => array(
                        'notsubmitted' => get_string('status_notsubmitted', 'rb_source_assign'),
                        'submitted' => get_string('status_submitted', 'rb_source_assign'),
                        'graded' => get_string('status_graded', 'rb_source_assign')),
                )
            ),

            // Submission grade.
            new rb_filter_option(
                'base',
                'grade',
                get_string('submissiongrade', 'rb_source_assign'),
                'number'
            ),

            // Last modified (submission).
            new rb_filter_option(
                'base',
                'timemodified',
                get_string('lastmodifiedsubmission', 'rb_source_assign'),
                'date'
            ),

            // Last modified (grade).
            new rb_filter_option(
                'base',
                'timemarked',
                get_string('lastmodifiedgrade', 'rb_source_assign'),
                'date'
            ),
        );

        // user, course and category filters
        $this->add_core_user_filters($filteroptions);
        $this->add_core_course_filters($filteroptions);
        $this->add_core_course_category_filters($filteroptions);

        return $filteroptions;
    }

    /**
     * define required columns
     * @return array
     */
    protected function define_requiredcolumns() {
        $requiredcolumns = array(
            // Scale id.
            new rb_column(
                'scale',
                'scaleid',
                '',
                'scale.id',
                array('hidden' => true, 'joins' => 'scale')
            ),
        );

        return $requiredcolumns;
    }

    /**
     * define default columns
     * @return array
     */
    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'assignment',
                'value' => 'name'
            ),
            array(
                'type' => 'user',
                'value' => 'fullname'
            ),
            array(
                'type' => 'base',
                'value' => 'grade'
            ),
        );
        return $defaultcolumns;
    }

    /**
     * Define default filters
     * @return array
     */
    protected function define_defaultfilters(){
        $defaultfilters = array(
            array(
                'type' => 'user',
                'value' => 'fullname',
            )
        );

        return $defaultfilters;
    }

    /**
     * display the assignment type
     *
     * @deprecated Since Totara 12.0
     * @param string $field
     * @param object $record
     * @param boolean $isexport
     */
    public function rb_display_assignmenttype($field, $record, $isexport) {
        debugging('rb_source_assign::rb_display_assignmenttype has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        return get_string("type{$field}", 'assignment');
    }

    /**
     * display the scale values
     *
     * @deprecated Since Totara 12.0
     * @param string $field
     * @param object $record
     * @param boolean $isexport
     */
    public function rb_display_scalevalues($field, $record, $isexport) {
        debugging('rb_source_assign::rb_display_scalevalues has been deprecated since Totara 12.0. Use mod_assign\rb\display\assign_scale_values::display', DEBUG_DEVELOPER);
        // If there's no scale values, return an empty string.
        if (empty($field)) {
            return '';
        }

        // If there are scale values, format them nicely.
        $v = explode(',', $field);
        $v = implode(', ', $v);
        return $v;
    }

    /**
     * Display the submission grade
     *
     * @deprecated Since Totara 12.0
     * @param string $field
     * @param object $record
     * @param boolean $isexport
     */
    public function rb_display_submissiongrade($field, $record, $isexport) {
        debugging('rb_source_assign::rb_display_submissiongrade has been deprecated since Totara 12.0. Use mod_assign\rb\display\assign_submission_grade::display', DEBUG_DEVELOPER);
        // If there's no grade (yet), then return a string saying so.
        // If $field is 0, it is may be $mingrade or $grade.
        if ((integer)$field < 0 || empty($field)) {
            return get_string('nograde', 'rb_source_assign');
        }

        // If there's no scale values, return the raw grade.
        if (empty($record->scale_values)) {
            return (integer)$field;
        }

        // If there are scale values, work out which scale value was achieved.
        $v = explode(',', $record->scale_values);
        $i = (integer)$field - 1;
        return $v[$i];
    }

    /**
     * Display the max grade
     *
     * @deprecated Since Totara 12.0
     * @param string $field
     * @param object $record
     * @param boolean $isexport
     */
    public function rb_display_maxgrade($field, $record, $isexport) {
        debugging('rb_source_assign::rb_display_maxgrade has been deprecated since Totara 12.0. Use mod_assign\rb\display\assign_max_grade::display', DEBUG_DEVELOPER);
        // if there's no scale values, return the raw grade.
        if (empty($record->scale_values)) {
            return (integer)$field;
        }

        // If there are scale values, work out which scale value is the maximum.
        $v = explode(',', $record->scale_values);
        $i = (integer)count($v) - 1;
        return $v[$i];
    }

    /**
     * Display the min grade
     *
     * @deprecated Since Totara 12.0
     * @param string $field
     * @param object $record
     * @param boolean $isexport
     */
    public function rb_display_mingrade($field, $record, $isexport) {
        debugging('rb_source_assign::rb_display_mingrade has been deprecated since Totara 12.0. Use mod_assign\rb\display\assign_min_grade::display', DEBUG_DEVELOPER);
        // If there's no scale values, return the raw grade.
        if (empty($record->scale_values)) {
            return (integer)$field;
        }

        // If there are scale values, work out which scale value is the minimum.
        $v = explode(',', $record->scale_values);
        return $v[0];
    }
}
