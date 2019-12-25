<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * @group totara_reportbuilder
 */
class totara_reportbuilder_rb_filter_grpconcat_date_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    public function test_filter() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create four users.
        for ($i = 1; $i <= 4; $i++) {
            $users[$i] = $this->getDataGenerator()->create_user(['username' => 'user' . $i]);
        }

        // Create a job assignment for user 1, with no start date.
        $dataarray = array('fullname' => 'Test JA 1');
        \totara_job\job_assignment::create_default($users[1]->id, $dataarray);

        // Create a job assignment for user 2, with start date 15/10/2015
        $dataarray = array('fullname' => 'Test JA 1', 'startdate' => mktime(0, 0, 0, 10, 15, 2015));
        \totara_job\job_assignment::create_default($users[2]->id, $dataarray);

        // Create a job assignment for user 3, with start date 15/10/2019
        $dataarray = array('fullname' => 'Test JA 1', 'startdate' => mktime(0, 0, 0, 10, 15, 2019));
        \totara_job\job_assignment::create_default($users[3]->id, $dataarray);

        // Create a job assignment for user 4, with start date 15/10/2050
        $dataarray = array('fullname' => 'Test JA 1', 'startdate' => mktime(0, 0, 0, 10, 15, 2050));
        \totara_job\job_assignment::create_default($users[4]->id, $dataarray);

        // Create a report.
        $rid = $this->create_report('user', 'custom_user_report');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);

        // Add the 'User's Job Start Date(s)' column and filter.
        $this->add_column($report, 'job_assignment', 'allstartdates', null, null, null, 0);
        $this->add_filter($rid, 'job_assignment', 'allstartdatesfilter', 1, 'Course start date', 0, 1);

        $todb = new stdClass();
        $todb->reportid = $report->_id;
        $todb->type = 'job_assignment';
        $todb->value = 'allstartdatesfilter';
        $todb->sortorder = 1;
        $DB->insert_record('report_builder_filters', $todb);

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $filters = $report->get_filters();
        $this->assertCount(1, $filters);
        $filter = $filters['job_assignment-allstartdatesfilter'];
        $this->assertInstanceOf('rb_filter_grpconcat_date', $filter);

        // Test filter with no dates set.
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        list($sql, $params,) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(6, $records); // The 4 users plus admin and guest.
        $this->assertArrayHasKey($users[1]->id, $records);
        $this->assertArrayHasKey($users[2]->id, $records);
        $this->assertArrayHasKey($users[3]->id, $records);
        $this->assertArrayHasKey($users[4]->id, $records);

        // Test Filter after date.
        $filter->set_data([
            'after_applied' => true,
            'after' => mktime(0, 0, 0, 1, 1, 2020),
            'before_applied' => false,
            'before' => 0,
            'daysafter'=> 0,
            'daysbefore' => 0]);
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        list($sql, $params,) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(1, $records);
        $this->assertArrayHasKey($users[4]->id, $records); // User4.

        // Test Filter before date.
        $filter->set_data([
            'after_applied' => false,
            'after' => 0,
            'before_applied' => true,
            'before' => mktime(0, 0, 0, 1, 1, 2019),
            'daysafter'=> 0,
            'daysbefore' => 0]);
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        list($sql, $params,) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(1, $records);
        $this->assertArrayHasKey($users[2]->id, $records); // User2.

        // Test Filter with before and after date.
        $filter->set_data([
            'after_applied' => true,
            'after' => mktime(0, 0, 0, 1, 1, 2019),
            'before_applied' => true,
            'before' => mktime(0, 0, 0, 1, 1, 2045),
            'daysafter'=> 0,
            'daysbefore' => 0]);
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        list($sql, $params,) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(1, $records);
        $this->assertArrayHasKey($users[3]->id, $records); // User3.
    }
}
