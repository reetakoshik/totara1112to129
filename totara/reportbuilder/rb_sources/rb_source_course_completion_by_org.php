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
require_once($CFG->dirroot . '/completion/criteria/completion_criteria.php');

/**
 * NOTE: this source makes little sense now, the columns defined here
 * might as well be added directly to the organisation source or trait.
 * Previously there were a lot more columns here, but it was just duplicating
 * regular completion report with custom aggregation, anyway those columns
 * and filters were removed because they were not compatible with subqueries.
 */
class rb_source_course_completion_by_org extends rb_base_source {

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        $this->base = '{org}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_course_completion_by_org');
        $this->usedcomponents[] = 'totara_hierarchy';
        $this->usedcomponents[] = 'totara_cohort';

        $this->cacheable = false;

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
        $joinlist = array();
        return $joinlist;
    }

    protected function define_columnoptions() {
        global $DB;

        // Apply global user restrictions.
        $global_restriction_join_cc = $this->get_global_report_restriction_join('cc', 'userid');

        $columnoptions = array();
        $columnoptions[] = new rb_column_option(
            'course_completion',
            'organisationid',
            get_string('completionorgid', 'rb_source_course_completion_by_org'),
            'base.id',
            array('displayfunc' => 'integer')
        );
        $columnoptions[] = new rb_column_option(
            'course_completion',
            'organisationpath',
            get_string('completionorgpath', 'rb_source_course_completion_by_org'),
            'base.path',
            array(
                'displayfunc' => 'plaintext',
            )
        );
        $columnoptions[] = new rb_column_option(
            'course_completion',
            'organisationpathtext',
            get_string('completionorgpathtext', 'rb_source_course_completion_by_org'),
            'base.path',
            array(
                'displayfunc' => 'hierarchy_nice_path',
                'extrafields' => array('hierarchytype' => '\'org\'')
            )
        );
        $columnoptions[] = new rb_column_option(
            'course_completion',
            'organisation',
            get_string('completionorgname', 'rb_source_course_completion_by_org'),
            'base.fullname',
            array(
                  'dbdatatype' => 'char',
                  'outputformat' => 'text',
                  'displayfunc' => 'format_string')
        );
        $concat = $DB->sql_group_concat($DB->sql_concat('u.firstname', "' '", 'u.lastname'), ', ', 'u.firstname ASC, u.lastname ASC');
        $columnoptions[] = new rb_column_option(
            'user',
            'allparticipants',
            get_string('participants', 'rb_source_course_completion_by_org'),
            // Note: This technically should be changed to use fullname() but the effort and performance hit
            //       to do that for just this column isn't justified.
            "(SELECT $concat
                FROM {course_completions} cc
                $global_restriction_join_cc
                JOIN {user} u ON u.id = cc.userid AND u.deleted = 0 
               WHERE base.id = cc.organisationid
            GROUP BY u.firstname, u.lastname)",
            array(
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'displayfunc' => 'format_string',
                'iscompound' => true,
                'issubquery' => true,
            )
        );
        $columnoptions[] = new rb_column_option(
            'course_completion',
            'total',
            get_string('numofrecords', 'rb_source_course_completion_by_org'),
            "(SELECT COUNT('x')
                FROM {course_completions} cc
                $global_restriction_join_cc
               WHERE base.id = cc.organisationid)",
            array(
                'displayfunc' => 'integer',
                'iscompound' => true,
                'issubquery' => true,
            )
        );
        $columnoptions[] = new rb_column_option(
            'course_completion',
            'completed',
            get_string('numcompleted', 'rb_source_course_completion_by_org'),
            "(SELECT COUNT('x')
                FROM {course_completions} cc
                $global_restriction_join_cc
               WHERE base.id = cc.organisationid AND cc.timecompleted > 0 AND (cc.rpl IS NULL OR cc.rpl = ''))",
            array(
                'displayfunc' => 'integer',
                'iscompound' => true,
                'issubquery' => true,
            )
        );
        $columnoptions[] = new rb_column_option(
            'course_completion',
            'perccompleted',
            get_string('percentagecompleted', 'rb_source_course_completion_by_org'),
            "(SELECT " . $DB->sql_round("AVG(CASE WHEN cc.timecompleted > 0 AND (cc.rpl IS NULL OR cc.rpl = '') THEN 100.0 ELSE 0 END)", 0) . "
                FROM {course_completions} cc
                $global_restriction_join_cc
               WHERE base.id = cc.organisationid)",
            array(
                'displayfunc' => 'integer',
                'iscompound' => true,
                'issubquery' => true,
            )
        );
        $columnoptions[] = new rb_column_option(
            'course_completion',
            'completedrpl',
            get_string('numcompletedviarpl', 'rb_source_course_completion_by_org'),
            "(SELECT COUNT('x')
                FROM {course_completions} cc
                $global_restriction_join_cc
               WHERE base.id = cc.organisationid AND cc.timecompleted > 0 AND cc.rpl IS NOT NULL AND cc.rpl <> '')",
            array(
                'displayfunc' => 'integer',
                'iscompound' => true,
                'issubquery' => true,
            )
        );
        $columnoptions[] = new rb_column_option(
            'course_completion',
            'perccompletedrpl',
            get_string('percentagecompletedviarpl', 'rb_source_course_completion_by_org'),
            "(SELECT " . $DB->sql_round("AVG(CASE WHEN cc.timecompleted > 0 AND cc.rpl IS NOT NULL AND cc.rpl <> '' THEN 100.0 ELSE 0 END)", 0) . "
                FROM {course_completions} cc
                $global_restriction_join_cc
               WHERE base.id = cc.organisationid)",
            array(
                'displayfunc' => 'integer',
                'iscompound' => true,
                'issubquery' => true,
            )
        );
        $columnoptions[] = new rb_column_option(
            'course_completion',
            'inprogress',
            get_string('numinprogress', 'rb_source_course_completion_by_org'),
            "(SELECT COUNT('x')
                FROM {course_completions} cc
                $global_restriction_join_cc
               WHERE base.id = cc.organisationid AND cc.timestarted > 0 AND (cc.rpl IS NULL OR cc.timecompleted = 0))",
            array(
                'displayfunc' => 'integer',
                'iscompound' => true,
                'issubquery' => true,
            )
        );
        $columnoptions[] = new rb_column_option(
            'course_completion',
            'notstarted',
            get_string('numnotstarted', 'rb_source_course_completion_by_org'),
            "(SELECT COUNT('x')
                FROM {course_completions} cc
                $global_restriction_join_cc
               WHERE base.id = cc.organisationid AND (cc.timecompleted IS NULL OR cc.timecompleted = 0) AND (cc.timestarted IS NULL OR cc.timestarted = 0))",
            array(
                'displayfunc' => 'integer',
                'iscompound' => true,
                'issubquery' => true,
            )
        );
        $columnoptions[] = new rb_column_option(
            'course_completion',
            'earliest_completeddate',
            get_string('earliestcompletiondate', 'rb_source_course_completion_by_org'),
            "(SELECT MIN(cc.timecompleted)
                FROM {course_completions} cc
                $global_restriction_join_cc
               WHERE base.id = cc.organisationid)",
            array(
                'displayfunc' => 'nice_date',
                'dbdatatype' => 'timestamp',
                'iscompound' => true,
                'issubquery' => true,
            )
        );
        $columnoptions[] = new rb_column_option(
            'course_completion',
            'latest_completeddate',
            get_string('latestcompletiondate', 'rb_source_course_completion_by_org'),
            "(SELECT MAX(cc.timecompleted)
                FROM {course_completions} cc
                $global_restriction_join_cc
               WHERE base.id = cc.organisationid)",
            array(
                'displayfunc' => 'nice_date',
                'dbdatatype' => 'timestamp',
                'iscompound' => true,
                'issubquery' => true,
            )
        );

        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = array(
            new rb_filter_option(
                'course_completion',
                'organisationid',
                get_string('officewhencompletedbasic', 'rb_source_course_completion_by_org'),
                'select',
                array(
                    'selectfunc' => 'organisations_list',
                    'attributes' => rb_filter_option::select_width_limiter(),
                )
            ),
            new rb_filter_option(
                'course_completion',
                'organisationpath',
                get_string('officewhencompleted', 'rb_source_course_completion_by_org'),
                'hierarchy',
                array(
                    'hierarchytype' => 'org',
                )
            ),
            // aggregated filters
            new rb_filter_option(
                'course_completion',
                'total',
                get_string('totalcompletions', 'rb_source_course_completion_by_org'),
                'number'),
            new rb_filter_option(
                'course_completion',
                'completed',
                get_string('numcompleted', 'rb_source_course_completion_by_org'),
                'number'),
            new rb_filter_option(
                'course_completion',
                'completedrpl',
                get_string('numcompletedviarpl', 'rb_source_course_completion_by_org'),
                'number'),
            new rb_filter_option(
                'course_completion',
                'inprogress',
                get_string('numinprogress', 'rb_source_course_completion_by_org'),
                'number'),
            new rb_filter_option(
                'course_completion',
                'notstarted',
                get_string('numnotstarted', 'rb_source_course_completion_by_org'),
                'number'),
            new rb_filter_option(
                'user',
                'allparticipants',
                get_string('participants', 'rb_source_course_completion_by_org'),
                'text'),
        );

        return $filteroptions;
    }

    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);

        $contentoptions[] = new rb_content_option(
            'completed_org',
            get_string('orgwhencompleted', 'rb_source_course_completion_by_org'),
            'base.path'
        );

        // NOTE: aggregation by completion date is not supported here, use regular completion report for that.
        return $contentoptions;
    }

    protected function define_paramoptions() {
        $paramoptions = array(
            new rb_param_option(
                'userid',
                'base.userid',
                null
            ),
            // NOTE: aggregation by course id is not supported here, use regular completion report for that.
        );

        return $paramoptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'course_completion',
                'value' => 'organisation',
            ),
            array(
                'type' => 'course_completion',
                'value' => 'completed',
            ),
            array(
                'type' => 'course_completion',
                'value' => 'total',
            ),
            array(
                'type' => 'course_completion',
                'value' => 'earliest_completeddate',
            ),
            array(
                'type' => 'course_completion',
                'value' => 'latest_completeddate',
            ),
        );
        return $defaultcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = array(
            array(
                'type' => 'course_completion',
                'value' => 'organisationpath',
                'advanced' => 1,
            ),
        );

        return $defaultfilters;
    }

    protected function define_requiredcolumns() {
        $requiredcolumns = array();
        return $requiredcolumns;
    }

}

