<?php
/**
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package totara_reportbuilder
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}
global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');

/**
 * Class totara_reportbuilder_rb_jobassignment_cache_test
 * Test that the cahcing of reports with jobassignment filters works properly
 *
 * @group totara_reportbuilder
 */
class totara_reportbuilder_rb_jobassignment_cache_testcase extends reportcache_advanced_testcase {

    // Test case data
    protected $report_builder_data = array('id' => 6, 'fullname' => 'User Report', 'shortname' => 'userreport',
                                            'source' => 'user', 'hidden' => 0, 'embedded' => 0);

    protected $report_builder_filters_additional_data = array(
                                array('id' => 2, 'reportid' => 6, 'type' => 'job_assignment', 'value' => 'allpositions',
                                    'heading' => '', 'sortorder' => 2),
                                array('id' => 3, 'reportid' => 6, 'type' => 'job_assignment', 'value' => 'allmanagers',
                                    'heading' => '', 'sortorder' => 3),
                                array('id' => 4, 'reportid' => 6, 'type' => 'job_assignment', 'value' => 'allorganisations',
                                    'heading' => '', 'sortorder' => 4),
                                array('id' => 5, 'reportid' => 6, 'type' => 'job_assignment', 'value' => 'allstartdatesfilter',
                                    'heading' => '', 'sortorder' => 5),
                                array('id' => 6, 'reportid' => 6, 'type' => 'job_assignment', 'value' => 'allenddatesfilter',
                                    'heading' => '', 'sortorder' => 6),
                                array('id' => 7, 'reportid' => 6, 'type' => 'job_assignment', 'value' => 'allappraisers',
                                    'heading' => '', 'sortorder' => 7));

    protected $report_builder_columns_additional_data = array(
                                array('id' => 2, 'reportid' => 6, 'type' => 'job_assignment', 'value' => 'allstartdates',
                                    'heading' => '', 'sortorder' => 3),
                                array('id' => 3, 'reportid' => 6, 'type' => 'job_assignment', 'value' => 'allenddates',
                                    'heading' => '', 'sortorder' => 4));

    /**
     * Prepare mock data for testing
     *
     * Common part for all test cases:
     *  - Set up a base report no columns or filters
     *
     */
    protected function setUp(){
        parent::setup();
        $this->setAdminUser();
        $this->loadDataSet($this->createArrayDataSet(array('report_builder' => array($this->report_builder_data))));
    }

    /**
     * Test case
     *  - Common part (@see: self::setUp())
     *  - Cache report
     *  - Add filters
     *  - Cache report
     *
     * tests that filters of the type job_assignment can be added to the report
     */
    public function test_cache_filters() {
        $this->resetAfterTest();
        $this->enable_caching($this->report_builder_data['id']);
        $this->assertSame(RB_CACHE_FLAG_OK, $this->get_report_cache_status($this->report_builder_data['id'], array()));

        $this->loadDataSet($this->createArrayDataSet(array('report_builder_filters' => $this->report_builder_filters_additional_data,
                                                           'report_builder_columns' => $this->report_builder_columns_additional_data)));

        $report = reportbuilder::create($this->report_builder_data['id']);
        // Make sure the cached table is not longer valid.
        $this->assertFalse($report->get_cache_table());

        // Try to make the new cache table.
        reportbuilder_generate_cache($this->report_builder_data['id']);
        $this->assertSame(RB_CACHE_FLAG_OK, $this->get_report_cache_status($this->report_builder_data['id'], array()));
    }

    /**
     * Test case:
     *  - Common part (@see: self::setUp())
     *  - Cache report
     *  - Add filters and columns that have the same name
     *  - Check that the cache was created
     *
     * Checks that there is not collisions in the column names in the database between the filters and columns
     */
    public function test_cache_job_assignment_sql_collsion(){
        $this->resetAfterTest();
        $this->enable_caching($this->report_builder_data['id']);
        $this->assertSame(RB_CACHE_FLAG_OK, $this->get_report_cache_status($this->report_builder_data['id'], array()));

        // Add columns and filters of the same value
        $this->loadDataSet($this->createArrayDataSet(array('report_builder_filters' => $this->report_builder_filters_additional_data)));

        $report = reportbuilder::create($this->report_builder_data['id']);
        $this->assertFalse($report->get_cache_table());

        // Try to generate the new cache table
        reportbuilder_generate_cache($this->report_builder_data['id']);
        $this->assertSame(RB_CACHE_FLAG_OK, $this->get_report_cache_status($this->report_builder_data['id'], array()));
    }
}
