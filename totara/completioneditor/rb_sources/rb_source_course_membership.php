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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_completioneditor
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/completion/completion_completion.php');

class rb_source_course_membership extends rb_base_source {

    public $base;
    public $joinlist;
    public $columnoptions;
    public $filteroptions;
    public $contentoptions;
    public $paramoptions;
    public $defaultcolumns;
    public $defaultfilters;
    public $requiredcolumns;
    public $sourcetitle;

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
        return get_string('sourcetitle', 'rb_source_course_membership');
    }


    protected function define_base() {
        global $DB;

        $global_restriction_join = $this->get_global_report_restriction_join('basesub', 'userid');

        $uniqueid = $DB->sql_concat_join("','", array('userid', 'courseid'));

        return  "(SELECT " . $uniqueid . " AS id, userid, courseid
                    FROM (SELECT ue.userid AS userid, e.courseid AS courseid
                           FROM {user_enrolments} ue
                           JOIN {enrol} e ON ue.enrolid = e.id
                          UNION
                         SELECT cc.userid AS userid, cc.course AS courseid
                           FROM {course_completions} cc
                          UNION
                         SELECT cch.userid AS userid, cch.courseid AS courseid
                           FROM {course_completion_history} cch
                          UNION
                         SELECT p1.userid AS userid, pca1.courseid AS courseid
                           FROM {dp_plan_course_assign} pca1
                           JOIN {dp_plan} p1 ON pca1.planid = p1.id
                    )
                basesub
                {$global_restriction_join})";
    }

    /**
     * Creates the array of rb_join objects required for this->joinlist.
     *
     * @return array
     */
    protected function define_joinlist() {
        $joinlist = array();

        $this->add_user_table_to_joinlist($joinlist, 'base', 'userid');
        $this->add_job_assignment_tables_to_joinlist($joinlist, 'base', 'userid');
        $this->add_course_table_to_joinlist($joinlist, 'base', 'courseid', 'INNER');

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
                'coursemembership',
                'editcoursecompletion',
                get_string('coursecompletionedit', 'totara_completioneditor'),
                'base.id',
                array(
                    'displayfunc' => 'edit_completion',
                    'extrafields' => array(
                        'userid' => 'base.userid',
                        'courseid' => 'base.courseid',
                    ),
                    'noexport' => true,
                )
            )
        );

        $this->add_user_fields_to_columns($columnoptions);
        $this->add_job_assignment_fields_to_columns($columnoptions);
        $this->add_course_fields_to_columns($columnoptions);

        return $columnoptions;
    }

    /**
     * Creates the array of rb_filter_option objects required for $this->filteroptions.
     *
     * @return array
     */
    protected function define_filteroptions() {
        $filteroptions = array();

        $this->add_user_fields_to_filters($filteroptions);
        $this->add_job_assignment_fields_to_filters($filteroptions);
        $this->add_course_fields_to_filters($filteroptions);

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
                'type' => 'course',
                'value' => 'fullname',
            ),
        );

        return $defaultfilters;
    }

    public function rb_display_edit_completion($id, $row, $isexport) {
        // Ignores $id == course_completions->id, because the user might have been unassigned and only history records exist.
        $url = new moodle_url('/totara/completioneditor/edit_course_completion.php',
            array('courseid' => $row->courseid, 'userid' => $row->userid));
        return html_writer::link($url, get_string('coursecompletionedit', 'totara_completioneditor'));
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
        if (get_class($this) === 'rb_source_course_membership') {
            return 2; // One record is in course_completion, one in course_completion_history, with different userids.
        }
        return parent::phpunit_column_test_expected_count($columnoption);
    }
}
