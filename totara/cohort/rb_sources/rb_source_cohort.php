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
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page
}
global $CFG;
require_once($CFG->dirroot.'/cohort/lib.php');

/**
 * A report builder source for the "cohorts" table.
 */
class rb_source_cohort extends rb_base_source {
    use \core_tag\rb\source\report_trait;
    use \totara_job\rb\source\report_trait;

    /**
     * Constructor
     * @global object $CFG
     */
    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Global restrictions are applied in define_joinlist() method.

        $this->base = '{cohort}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = array();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_cohort');
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

    /**
     * Creates the array of rb_join objects required for this->joinlist
     *
     * @global object $CFG
     * @return array
     */
    protected function define_joinlist() {
        // Apply global user restrictions.
        $global_restriction_join_cm = $this->get_global_report_restriction_join('cm', 'userid');
        $global_restriction_join_cm2 = $this->get_global_report_restriction_join('cm2', 'userid');

        $joinlist = array(
                        new rb_join(
                            'members', // Table alias?
                            'LEFT', // Type of join.
                            "(SELECT cm.cohortid, cm.userid FROM {cohort_members} cm {$global_restriction_join_cm})",
                            'base.id = members.cohortid', // How it is joined.
                            REPORT_BUILDER_RELATION_ONE_TO_MANY
                        ),
                        new rb_join(
                            'membercount',
                            'LEFT', // Type of join.
                            "(SELECT cohortid, count(cm2.id) AS count FROM {cohort_members} cm2 {$global_restriction_join_cm2} GROUP BY cohortid)",
                            'base.id = membercount.cohortid', // How it is joined.
                            REPORT_BUILDER_RELATION_ONE_TO_ONE
                        ),
                        new rb_join(
                            'context',
                            'INNER',
                            '{context}',
                            "context.id = base.contextid",
                            REPORT_BUILDER_RELATION_MANY_TO_ONE
                        ),
                        new rb_join(
                            'course_category',
                            'LEFT',
                            '{course_categories}',
                            "(course_category.id = context.instanceid AND context.contextlevel = ". CONTEXT_COURSECAT . ")",
                            REPORT_BUILDER_RELATION_MANY_TO_ONE,
                            'context'
                        )
        );

        $this->add_core_user_tables($joinlist, 'members', 'userid');
        $this->add_totara_job_tables($joinlist, 'members', 'userid');
        $this->add_core_tag_tables('core', 'cohort', $joinlist, 'base', 'id');

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
            'cohort',  // Which table? Type.
            'name', // Alias for the field.
            get_string('name', 'totara_cohort'), // Name for the column.
            'base.name', // Table alias and field name.
            array('dbdatatype' => 'char',
                  'outputformat' => 'text',
                  'displayfunc' => 'format_string') // Options.
        );
        $columnoptions[] = new rb_column_option(
            'cohort',
            'namelink',
            get_string('namelink', 'totara_cohort'),
            'base.name',
            array(
                'displayfunc' => 'cohort_name_link',
                'extrafields' => array(
                    'cohort_id' => 'base.id'
                )
            )
        );
        $columnoptions[] = new rb_column_option(
            'cohort',
            'idnumber',
            get_string('idnumber', 'totara_cohort'),
            'base.idnumber',
            array('dbdatatype' => 'char',
                  'displayfunc' => 'plaintext',
                  'outputformat' => 'text')
        );
        $columnoptions[] = new rb_column_option(
            'cohort',
            'type',
            get_string('type', 'totara_cohort'),
            'base.cohorttype',
            array(
                'displayfunc' => 'cohort_type'
            )
        );
        $columnoptions[] = new rb_column_option(
            'cohort',
            'numofmembers',
            get_string('numofmembers', 'totara_cohort'),
            'CASE WHEN membercount.count IS NULL THEN 0 ELSE membercount.count END',
            array(
                'joins' => array('membercount'),
                'dbdatatype' => 'integer',
                'displayfunc' => 'integer'
            )
        );
        $columnoptions[] = new rb_column_option(
            'cohort',
            'actions',
            get_string('actions', 'totara_cohort'),
            'base.id',
            array(
                'displayfunc' => 'cohort_actions',
                'extrafields' => array('contextid' => 'base.contextid', 'component' => 'base.component'),
                'nosort' => true,
                'noexport' => true
            )
        );
        $columnoptions[] = new rb_column_option(
            'cohort',
            'startdate',
            get_string('startdate', 'totara_cohort'),
            'base.startdate',
            array(
                'displayfunc' => 'nice_date',
                'dbdatatype' => 'timestamp',
            )
        );
        $columnoptions[] = new rb_column_option(
            'cohort',
            'enddate',
            get_string('enddate', 'totara_cohort'),
            'base.enddate',
            array(
                'displayfunc' => 'nice_date',
                'dbdatatype' => 'timestamp',
            )
        );
        $columnoptions[] = new rb_column_option(
            'cohort',
            'status',
            get_string('status', 'totara_cohort'),
            'base.id',
            array(
                'displayfunc' => 'cohort_status',
                'extrafields' => array(
                    'startdate'=>'base.startdate',
                    'enddate'=>'base.enddate'
                )
            )
        );
        $columnoptions[] = new rb_column_option(
            'course_category',
            'name',
            get_string('coursecategory', 'totara_reportbuilder'),
            "course_category.name",
            array('joins' => 'course_category',
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'displayfunc' => 'format_string')
        );
        $columnoptions[] = new rb_column_option(
            'course_category',
            'namelink',
            get_string('coursecategorylinked', 'totara_reportbuilder'),
            "course_category.name",
            array(
                'joins' => 'course_category',
                'displayfunc' => 'cohort_category_link',
                'defaultheading' => get_string('category', 'totara_reportbuilder'),
                'extrafields' => array('context_id' => 'base.contextid')
            )
        );
        $columnoptions[] = new rb_column_option(
            'course_category',
            'id',
            get_string('coursecategoryid', 'totara_reportbuilder'),
            "course_category.id",
            array('joins' => 'course_category',
                  'displayfunc' => 'integer')
        );

        $this->add_core_user_columns($columnoptions);
        $this->add_totara_job_columns($columnoptions);
        $this->add_core_tag_columns('core', 'cohort', $columnoptions);

        return $columnoptions;
    }

    /**
     * Creates the array of rb_filter_option objects required for $this->filteroptions
     * @return array
     */
    protected function define_filteroptions() {
        // No filter options!
        $filteroptions = array();
        $filteroptions[] = new rb_filter_option(
            'cohort',
            'name',
            get_string('name', 'totara_cohort'),
            'text'
        );
        $filteroptions[] = new rb_filter_option(
            'cohort',
            'idnumber',
            get_string('idnumber', 'totara_cohort'),
            'text'
        );
        $filteroptions[] = new rb_filter_option(
            'cohort',
            'type',
            get_string('type', 'totara_cohort'),
            'select',
            array(
                'selectchoices' => array(
                    cohort::TYPE_DYNAMIC => get_string('dynamic', 'totara_cohort'),
                    cohort::TYPE_STATIC  => get_string('set', 'totara_cohort'),
                ),
                'simplemode' => true,
            )
        );
        $this->add_core_user_filters($filteroptions);
        $this->add_core_tag_filters('core', 'cohort', $filteroptions);

        return $filteroptions;
    }


    protected function define_defaultcolumns() {
        $defaultcolumns = array(
                            array(
                                'type' => 'cohort',
                                'value' => 'name',
                            ),
                            array(
                                'type' => 'user',
                                'value' => 'fullname',
                            )
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
        );

        return $defaultfilters;
    }
    /**
     * Creates the array of rb_content_option object required for $this->contentoptions
     * @return array
     */
    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);

        $contentoptions[] = new rb_content_option(
            'date',
            get_string('modifieddate', 'rb_source_goal_status_history'),
            'base.timemodified'
        );

        return $contentoptions;
    }

    protected function define_paramoptions() {
        $paramoptions = array(
            new rb_param_option(
                'cohortid', // Parameter name.
                'base.id'  // Field.
            ),
            new rb_param_option(
                'contextid', // Parameter name.
                'base.contextid'  // Field.
            ),
        );
        return $paramoptions;
    }

    /**
     * Displays category name as link to event
     *
     * @deprecated Since Totara 12.0
     * @param string $categoryname
     * @param object Report row $row
     * @param bool $isexport optional false
     * @return string html link
     */
    public function rb_display_link_cohort_category($categoryname, $row, $isexport = false) {
        debugging('rb_source_cohort::rb_display_link_cohort_category has been deprecated since Totara 12.0. Use totara_cohort\rb\display\cohort_category_link::display', DEBUG_DEVELOPER);

        $categoryname = format_string($categoryname);

        $contextid = $row->context_id;
        $context = context::instance_by_id($contextid, IGNORE_MISSING);

        if (!$context) {
            return $categoryname;
        }

        if ($context->contextlevel == CONTEXT_SYSTEM) {
            $categoryname = context_system::get_level_name();
        }

        if ($isexport) {
            return $categoryname;
        }

        if (!has_any_capability(array('moodle/cohort:manage', 'moodle/cohort:view'), $context)) {
            return $categoryname;
        }

        $url = new moodle_url('/cohort/index.php', array('contextid' => $context->id));
        return html_writer::link($url, $categoryname);
    }

    /**
     * RB helper function to show the name of the cohort with a link to the cohort's details page.
     *
     * @deprecated Since Totara 12.0
     * @param int $cohortid
     * @param object $row
     * @return string html link
     */
    public function rb_display_cohort_name_link($cohortname, $row) {
        debugging('rb_source_cohort::rb_display_cohort_name_link has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        if (empty($cohortname)) {
            return '';
        }
        return html_writer::link(new moodle_url('/cohort/view.php', array('id' => $row->cohort_id)), format_string($cohortname));
    }

    /**
     * RB helper function to show whether a cohort is dynamic or static
     *
     * @deprecated Since Totara 12.0
     * @param int $cohorttype
     * @param object $row
     */
    public function rb_display_cohort_type($cohorttype, $row) {
        debugging('rb_source_cohort::rb_display_cohort_type has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        global $CFG;
        require_once($CFG->dirroot.'/cohort/lib.php');

        switch( $cohorttype ) {
            case cohort::TYPE_DYNAMIC:
                $ret = get_string('dynamic', 'totara_cohort');
                break;
            case cohort::TYPE_STATIC:
                $ret = get_string('set', 'totara_cohort');
                break;
            default:
                $ret = get_string('typeunknown', 'totara_cohort', $cohorttype);
        }
        return $ret;
    }

    /**
     * RB helper function to show the "action" links for a cohort -- edit/clone/delete
     *
     * @deprecated Since Totara 12.0
     * @param int $cohortid
     * @param stdClass $row
     * @return string
     */
    public function rb_display_cohort_actions($cohortid, $row) {
        debugging('rb_source_cohort::rb_display_cohort_actions has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        global $OUTPUT;

        $contextid = $row->contextid;
        if ($contextid) {
            $context = context::instance_by_id($contextid);
        } else {
            $context = context_system::instance();
        }

        if (!has_capability('moodle/cohort:manage', $context)) {
            return '';
        }

        $str = '';
        if (empty($row->component)) {
            $editurl = new moodle_url('/cohort/edit.php', array('id' => $cohortid));
            $str .= html_writer::link($editurl, $OUTPUT->pix_icon('t/edit', get_string('edit')));
        }
        $cloneurl = new moodle_url('/cohort/view.php', array('id' => $cohortid, 'clone' => 1, 'cancelurl' => qualified_me()));
        $str .= html_writer::link($cloneurl, $OUTPUT->pix_icon('t/copy', get_string('copy', 'totara_cohort')));
        $delurl = new moodle_url('/cohort/view.php', array('id' => $cohortid, 'delete' => 1, 'cancelurl' => qualified_me()));
        $str .= html_writer::link($delurl, $OUTPUT->pix_icon('t/delete', get_string('delete')));
        return $str;
    }

    /**
     * Displays the cohort status
     *
     * @deprecated Since Totara 12.0
     * @param $cohortid
     * @param $row
     * @return string
     */
    public function rb_display_cohort_status($cohortid, $row) {
        debugging('rb_source_cohort::rb_display_cohort_status has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        $now = time();
        if (totara_cohort_is_active($row, $now)) {
            return get_string('cohortdateactive', 'totara_cohort');
        }

        if ($row->startdate && $row->startdate > $now) {
            return get_string('cohortdatenotyetstarted', 'totara_cohort');
        }

        if ($row->enddate && $row->enddate < $now) {
            return get_string('cohortdatealreadyended', 'totara_cohort');
        }

        return '';
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
        if (strpos("{$columnoption->type}_{$columnoption->value}", 'course_category_') === 0) {
            return 0;
        }
        return parent::phpunit_column_test_expected_count($columnoption);
    }
}

// End of rb_source_user class
