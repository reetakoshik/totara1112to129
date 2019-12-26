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
 * @author Nathan Lewis <nathan.lewis@totaralms.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/completion/completion_completion.php');

class rb_source_course_completion_all extends rb_base_source {
    use \core_course\rb\source\report_trait;
    use \totara_job\rb\source\report_trait;

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        $this->base = $this->define_base();
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = array();
        $this->sourcetitle = $this->define_sourcetitle();
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

    protected function define_sourcetitle() {
        return get_string('sourcetitle', 'rb_source_course_completion_all');
    }

    protected function define_base() {
        global $DB;

        $global_restriction_join_cc = $this->get_global_report_restriction_join('cc', 'userid');
        $global_restriction_join_cch = $this->get_global_report_restriction_join('cch', 'userid');

        $ccuniqueid = $DB->sql_concat_join("'CC'", array($DB->sql_cast_2char('cc.id')));
        $cchuniqueid = $DB->sql_concat_join("'CCH'", array($DB->sql_cast_2char('cch.id')));
        $grade = "CASE WHEN cc.status = " . COMPLETION_STATUS_COMPLETEVIARPL . " THEN cc.rplgrade ELSE gg.finalgrade END";
        $base = "(
              SELECT {$ccuniqueid} AS id, cc.userid, cc.course AS courseid, cc.timecompleted, {$grade} AS grade, gi.grademax, gi.grademin, 1 AS iscurrent
                FROM {course_completions} cc
                {$global_restriction_join_cc}
           LEFT JOIN {grade_items} gi ON cc.course = gi.courseid AND gi.itemtype = 'course'
           LEFT JOIN {grade_grades} gg ON gi.id = gg.itemid AND gg.userid = cc.userid
               WHERE cc.status > " . COMPLETION_STATUS_NOTYETSTARTED . "
           UNION ALL
              SELECT {$cchuniqueid} AS id,cch.userid, cch.courseid, cch.timecompleted, cch.grade, gi.grademax, gi.grademin, 0 AS iscurrent
                FROM {course_completion_history} cch
                {$global_restriction_join_cch}
           LEFT JOIN {grade_items} gi ON cch.courseid = gi.courseid AND gi.itemtype = 'course'
           LEFT JOIN {grade_grades} gg ON gi.id = gg.itemid AND gg.userid = cch.userid
              )";
        return $base;
    }

    /**
     * Creates the array of rb_join objects required for this->joinlist.
     *
     * @return array
     */
    protected function define_joinlist() {
        $joinlist = array();

        $this->add_core_user_tables($joinlist, 'base', 'userid');
        $this->add_totara_job_tables($joinlist, 'base', 'userid');
        $this->add_core_course_tables($joinlist, 'base', 'courseid', 'INNER');

        return $joinlist;
    }

    /**
     * Creates the array of rb_column_option objects required for $this->columnoptions.
     *
     * @return array
     */
    protected function define_columnoptions() {
        $columnoptions = array(
            new rb_column_option(
                'base',
                'timecompleted',
                get_string('timecompleted', 'rb_source_course_completion_all'),
                'base.timecompleted',
                array(
                    'displayfunc' => 'nice_date',
                    'dbdatatype' => 'timestamp'
                )
            ),
            new rb_column_option(
                'base',
                'grade',
                get_string('grade', 'rb_source_course_completion_all'),
                'base.grade',
                array(
                    'displayfunc' => 'course_grade_string',
                    'extrafields' => array(
                        'grademax' => 'base.grademax',
                        'grademin' => 'base.grademin',
                    )
                )
            ),
        );
        if (get_class($this) === 'rb_source_course_completion_all') {
            // Only add this to the base class. The plan subclass doesn't have this column.
            $columnoptions[] = new rb_column_option(
                'base',
                'iscurrent',
                get_string('iscurrent', 'rb_source_course_completion_all'),
                'base.iscurrent',
                array(
                    'displayfunc' => 'yes_or_no'
                )
            );
        }

        $this->add_core_user_columns($columnoptions);
        $this->add_totara_job_columns($columnoptions);
        $this->add_core_course_columns($columnoptions);

        return $columnoptions;
    }

    /**
     * Creates the array of rb_filter_option objects required for $this->filteroptions.
     *
     * @return array
     */
    protected function define_filteroptions() {
        $filteroptions = array(
            new rb_filter_option(
                'base',
                'timecompleted',
                get_string('timecompleted', 'rb_source_course_completion_all'),
                'date'
            ),
            new rb_filter_option(
                'base',
                'grade',
                get_string('grade', 'rb_source_course_completion_all'),
                'number'
            ),
        );
        if (get_class($this) === 'rb_source_course_completion_all') {
            // Only add this to the base class. The plan subclass doesn't have this column.
            $filteroptions[] = new rb_filter_option(
                'base',
                'iscurrent',
                get_string('iscurrent', 'rb_source_course_completion_all'),
                'select',
                array(
                    'selectfunc' => 'yesno_list',
                    'attributes' => rb_filter_option::select_width_limiter(),
                )
            );
        }

        $this->add_core_user_filters($filteroptions);
        $this->add_totara_job_filters($filteroptions, 'base', 'userid');
        $this->add_core_course_filters($filteroptions);

        return $filteroptions;
    }

    /**
     * Creates the array of rb_content_option objects required for $this->contentoptions.
     *
     * @return array
     */
    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);

        return $contentoptions;
    }

    /**
     * Creates the array of rb_param_option objects required for $this->paramoptions.
     *
     * @return array
     */
    protected function define_paramoptions() {
        $paramoptions = array();

        $paramoptions[] = new rb_param_option(
            'userid',
            'base.userid',
            'base'
        );
        $paramoptions[] = new rb_param_option(
            'courseid',
            'base.courseid',
            'base'
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
                'value' => 'coursetypeicon',
            ),
            array(
                'type' => 'course',
                'value' => 'courselink',
            ),
            array(
                'type' => 'base',
                'value' => 'timecompleted',
            ),
            array(
                'type' => 'base',
                'value' => 'grade',
            ),
        );
        if (get_class($this) === 'rb_source_course_completion_all') {
            // Only add this to the base class. The plan subclass doesn't have this column.
            $defaultcolumns[] = array(
                'type' => 'base',
                'value' => 'iscurrent',
            );
        }
        return $defaultcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = array(
            array(
                'type' => 'user',
                'value' => 'fullname',
            ),
            array(
                'type' => 'course',
                'value' => 'fullname',
            ),
            array(
                'type' => 'base',
                'value' => 'timecompleted',
            ),
            array(
                'type' => 'base',
                'value' => 'grade',
            ),
        );
        if (get_class($this) === 'rb_source_course_completion_all') {
            // Only add this to the base class. The plan subclass doesn't have this column.
            $defaultcolumns[] = array(
                'type' => 'base',
                'value' => 'iscurrent',
            );
        }

        return $defaultfilters;
    }
}
