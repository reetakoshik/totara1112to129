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
 * @author Yuliya Bozhko <yuliya.bozhko@totaralearning.com>
 * @package totara_program
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php'); // Required for program creation.

/**
 * @group totara_reportbuilder
 */
class totara_program_rb_source_program_completion_testcase extends reportcache_advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    public function test_report() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $users[] = $this->getDataGenerator()->create_user();
        $users[] = $this->getDataGenerator()->create_user();
        $users[] = $this->getDataGenerator()->create_user();
        $users[] = $this->getDataGenerator()->create_user();

        $programs[] = $this->getDataGenerator()->create_program(['fullname' => 'Program 1']);
        $programs[] = $this->getDataGenerator()->create_program(['fullname' => 'Program 2']);
        $programs[] = $this->getDataGenerator()->create_program(['fullname' => 'Program 3']);

        $this->getDataGenerator()->assign_program($programs[0]->id, [$users[0]->id, $users[2]->id]);
        $this->getDataGenerator()->assign_program($programs[1]->id, [$users[1]->id, $users[3]->id]);
        $this->getDataGenerator()->assign_program($programs[2]->id, [$users[0]->id]);

        // Update records of 2 users to make them assigned on January 1, 2019.
        $DB->set_field('prog_completion', 'timecreated', 1546300800, ['userid' => $users[0]->id, 'programid' => $programs[0]->id]);
        $DB->set_field('prog_completion', 'timecreated', 1546300800, ['userid' => $users[3]->id, 'programid' => $programs[1]->id]);

        $rid = $this->create_report('program_completion', 'Test program completion report');

        $report = new reportbuilder($rid, null, false, null, null, true);
        $this->add_column($report, 'prog', 'fullname', null, null, null, 0);
        $this->add_column($report, 'user', 'username', null, null, null, 0);
        $this->add_column($report, 'progcompletion', 'status', null, null, null, 0);
        $this->add_column($report, 'progcompletion', 'assigneddate', null, null, null, 0);
        $this->add_column($report, 'progcompletion', 'starteddate', null, null, null, 0);

        $report = new reportbuilder($rid, null, false, null, null, true);
        list($sql, $params, $cache) = $report->build_query();

        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(5, $records); // No filters, we expect all users in the report.

        $record = reset($records);
        $record = (array)$record;
        $this->assertCount(6, $record);
        $this->assertArrayHasKey('id', $record);
        $this->assertArrayHasKey('prog_fullname', $record);
        $this->assertArrayHasKey('user_username', $record);
        $this->assertArrayHasKey('progcompletion_status', $record);
        $this->assertArrayHasKey('progcompletion_assigneddate', $record);
        $this->assertArrayHasKey('progcompletion_starteddate', $record);

        // Add program completion 'assigneddate' filter.
        $todb = new stdClass();
        $todb->reportid = $report->_id;
        $todb->type = 'progcompletion';
        $todb->value = 'assigneddate';
        $todb->sortorder = 1;
        $DB->insert_record('report_builder_filters', $todb);

        $report = new reportbuilder($rid, null, false, null, null, true);
        $filters = $report->get_filters();
        $this->assertCount(1, $filters);

        $afterdate =  new DateTime();
        $afterdate->setTimestamp(time());
        $afterdate->modify('-2 day 00:00:00'); // Get any date 2 days before right now.
        $after = $afterdate->getTimestamp();

        $filter = $filters['progcompletion-assigneddate'];
        $this->assertInstanceOf('rb_filter_date', $filter);
        $filter->set_data(['after' => $after, 'before' => 0, 'daysafter' => 0, 'daysbefore' => 0, 'after_applied' => true]);

        $report = new reportbuilder($rid, null, false, null, null, true);
        list($sql, $params, $cache) = $report->build_query(false, true);

        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(3, $records); // With date filter to filter out users assigned in the last 2 days.

        // Add program 'fullname' filter.
        $todb = new stdClass();
        $todb->reportid = $report->_id;
        $todb->type = 'prog';
        $todb->value = 'fullname';
        $todb->sortorder = 2;
        $DB->insert_record('report_builder_filters', $todb);

        $report = new reportbuilder($rid, null, false, null, null, true);
        $filters = $report->get_filters();
        $this->assertCount(2, $filters);
        $filter = $filters['prog-fullname'];
        $this->assertInstanceOf('rb_filter_text', $filter);
        $filter->set_data(array('operator' => rb_filter_type::RB_FILTER_ISEQUALTO, 'value' => 'Program 2'));

        $report = new reportbuilder($rid, null, false, null, null, true);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(1, $records); // Expecting 1 record after program name filter has been applied in addition to the date.
    }
}
