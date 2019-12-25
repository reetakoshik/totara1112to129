<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

/**
 * A report builder source for the "report_builder_schedule" table.
 */
final class rb_source_scheduled_reports extends rb_base_source {

    use \totara_reportbuilder\rb\source\report_trait;
    use \totara_reportbuilder\rb\source\report_schedule_trait;
    use \totara_reportbuilder\rb\source\report_saved_trait;
    use \totara_job\rb\source\report_trait;

    private $reporturl;

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
        $this->add_global_report_restriction_join('base', 'userid');

        $this->base = '{report_builder_schedule}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();

        // Pull in report schedule related info via a trait.
        $this->add_report_schedule_to_base();

        // Pull in all saved search related info via a trait.
        $this->add_saved_search(new rb_join(
                'saved',
                'LEFT',
                "{report_builder_saved}",
                'base.savedsearchid = saved.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
        ));

        // Pull in all report related info via a trait.
        $this->add_report(new rb_join(
                'report',
                'INNER',
                "{report_builder}",
                'base.reportid = report.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
        ));

        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = [];
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_scheduled_reports');

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

    /**
     * Creates the array of rb_join objects required for this->joinlist
     *
     * @return array
     */
    protected function define_joinlist() {
        $joinlist = [];

        $this->add_core_user_tables($joinlist, 'base', 'userid');
        $this->add_totara_job_tables($joinlist, 'base', 'userid');

        return $joinlist;
    }

    /**
     * Creates the array of rb_column_option objects required for
     * $this->columnoptions
     *
     * @return array
     */
    protected function define_columnoptions() {
        $columnoptions = [];

        $this->add_core_user_columns($columnoptions);
        $this->add_totara_job_columns($columnoptions);

        return $columnoptions;
    }

    /**
     * Creates the array of rb_filter_option objects required for $this->filteroptions
     * @return array
     */
    protected function define_filteroptions() {
        global $CFG;
        $filteroptions = [];

        $this->add_core_user_filters($filteroptions);
        $this->add_totara_job_filters($filteroptions);

        return $filteroptions;
    }


    protected function define_defaultcolumns() {
        $defaultcolumns = [
            [
                'type' => 'report',
                'value' => 'namelinkview'
            ],
            [
                'type' => 'user',
                'value' => 'fullname'
            ],
            [
                'type' => 'schedule',
                'value' => 'format'
            ],
            [
                'type' => 'schedule',
                'value' => 'schedule'
            ],
            [
                'type' => 'schedule',
                'value' => 'next'
            ],
            [
                'type' => 'schedule',
                'value' => 'user_modified'
            ],
            [
                'type' => 'schedule',
                'value' => 'last_modified'
            ]
        ];

        return $defaultcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = [
            [
                'type' => 'report',
                'value' => 'name'
            ],
            [
                'type' => 'user',
                'value' => 'fullname'
            ],
            [
                'type' => 'schedule',
                'value' => 'format'
            ],
        ];

        return $defaultfilters;
    }

    /**
     * Creates the array of rb_content_option object required for $this->contentoptions
     * @return array
     */
    protected function define_contentoptions() {
        $contentoptions = [];

        $contentoptions[] = new rb_content_option(
            'report_access',
            get_string('access', 'totara_reportbuilder'),
            'report.id',
            'report'
        );

        $contentoptions[] = new rb_content_option(
            'date',
            get_string('completiondate', 'rb_source_course_completion'),
            'base.nextreport'
        );

        $this->add_basic_user_content_options($contentoptions);

        return $contentoptions;
    }

    protected function define_paramoptions() {
        $paramoptions = [];

        return $paramoptions;
    }

    /**
     * Inject column_test data into database.
     * @param totara_reportbuilder_column_testcase $testcase
     */
    public function phpunit_column_test_add_data(totara_reportbuilder_column_testcase $testcase) {
        global $DB;

        if (!PHPUNIT_TEST) {
            throw new coding_exception('phpunit_column_test_add_data() cannot be used outside of unit tests');
        }

        $report_builder = [
            'fullname' => 'scheduled test report',
            'shortname' => 'scheduled test report',
            'source' => 'user',
            'hidden' => 0,
            'accessmode' => 0,
            'contentmode' => 0,
            'showtotalcount' => 1
        ];
        $report_id = $DB->insert_record('report_builder', $report_builder);

        $report_builder_schedule = [
            'reportid' => $report_id,
            'userid' => 2,
            'savedsearchid' => 0,
            'format' => 'csv',
            'frequency' => 1,
            'exporttofilesystem' => 0,
            'schedule' => 0,
            'nextreport' => time() + 100000,
            'usermodified' => 2,
            'lastmodified' => time()
        ];
        $scheduled_report_id = $DB->insert_record('report_builder_schedule', $report_builder_schedule);

        $report_builder_schedule_email_external = [
            'scheduleid' => $scheduled_report_id,
            'email' => "a@example.com"
        ];
        $DB->insert_record('report_builder_schedule_email_external', $report_builder_schedule_email_external);
    }
}

// end of rb_source_scheduled_reports class
