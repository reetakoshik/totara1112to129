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

/**
 * @group totara_reportbuilder
 */
class totara_program_rb_source_program_completion_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    /**
     * @var testing_data_generator
     */
    protected $generator;

    /**
     * @var totara_program_generator
     */
    protected $generator_program;

    protected function setUp() {
        parent::setUp();

        self::setAdminUser();

        $this->generator = self::getDataGenerator();
        $this->generator_program = $this->generator->get_plugin_generator('totara_program');
    }

    protected function tearDown() {
        $this->generator = null;
        $this->generator_program = null;

        parent::tearDown();
    }

    public function test_report() {
        global $DB;

        $users[] = $this->generator->create_user();
        $users[] = $this->generator->create_user();
        $users[] = $this->generator->create_user();
        $users[] = $this->generator->create_user();

        $programs[] = $this->generator_program->create_program(['fullname' => 'Program 1']);
        $programs[] = $this->generator_program->create_program(['fullname' => 'Program 2']);
        $programs[] = $this->generator_program->create_program(['fullname' => 'Program 3']);

        $this->generator_program->assign_program($programs[0]->id, [$users[0]->id, $users[2]->id]);
        $this->generator_program->assign_program($programs[1]->id, [$users[1]->id, $users[3]->id]);
        $this->generator_program->assign_program($programs[2]->id, [$users[0]->id]);

        // Update records of 2 users to make them assigned on January 1, 2019.
        $DB->set_field('prog_completion', 'timecreated', 1546300800, ['userid' => $users[0]->id, 'programid' => $programs[0]->id]);
        $DB->set_field('prog_completion', 'timecreated', 1546300800, ['userid' => $users[3]->id, 'programid' => $programs[1]->id]);

        $rid = $this->create_report('program_completion', 'Test program completion report');

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'prog', 'fullname', null, null, null, 0);
        $this->add_column($report, 'user', 'username', null, null, null, 0);
        $this->add_column($report, 'progcompletion', 'status', null, null, null, 0);
        $this->add_column($report, 'progcompletion', 'assigneddate', null, null, null, 0);
        $this->add_column($report, 'progcompletion', 'starteddate', null, null, null, 0);

        $report = reportbuilder::create($rid);
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

        $report = reportbuilder::create($rid);
        $filters = $report->get_filters();
        $this->assertCount(1, $filters);

        $afterdate =  new DateTime();
        $afterdate->setTimestamp(time());
        $afterdate->modify('-2 day 00:00:00'); // Get any date 2 days before right now.
        $after = $afterdate->getTimestamp();

        $filter = $filters['progcompletion-assigneddate'];
        $this->assertInstanceOf('rb_filter_date', $filter);
        $filter->set_data(['after' => $after, 'before' => 0, 'daysafter' => 0, 'daysbefore' => 0, 'after_applied' => true]);

        $report = reportbuilder::create($rid);
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

        $report = reportbuilder::create($rid);
        $filters = $report->get_filters();
        $this->assertCount(2, $filters);
        $filter = $filters['prog-fullname'];
        $this->assertInstanceOf('rb_filter_text', $filter);
        $filter->set_data(array('operator' => rb_filter_type::RB_FILTER_ISEQUALTO, 'value' => 'Program 2'));

        $report = reportbuilder::create($rid);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(1, $records); // Expecting 1 record after program name filter has been applied in addition to the date.
    }

    public function test_isassigned_column() {
        global $DB;

        $users[] = $this->generator->create_user();
        $users[] = $this->generator->create_user();
        $users[] = $this->generator->create_user();
        $users[] = $this->generator->create_user();
        $users[] = $this->generator->create_user();

        // Generate some courses to be added to the programs.
        $this->generator->create_course();
        $this->generator->create_course();
        $this->generator->create_course();
        $this->generator->create_course();

        $programs[] = $this->generator_program->create_program(['fullname' => 'Program 1']);
        $programs[] = $this->generator_program->create_program(['fullname' => 'Program 2']);

        $this->generator_program->add_courseset_to_program($programs[0]->id, 1, 1);
        $this->generator_program->add_courseset_to_program($programs[1]->id, 1, 2);

        // Assign some users normally via generator.
        $this->generator_program->assign_program($programs[1]->id, [$users[1]->id, $users[3]->id]);

        // Assign others in a way that would create a couple exceptions
        $data = new stdClass();
        $data->id = $programs[0]->id;

        $data->item[ASSIGNTYPE_INDIVIDUAL][$users[0]->id] = 1;
        $data->completiontime[ASSIGNTYPE_INDIVIDUAL][$users[0]->id] = '5 day';
        $data->completionevent[ASSIGNTYPE_INDIVIDUAL][$users[0]->id] = COMPLETION_EVENT_FIRST_LOGIN;

        // Exception (no program with id -1).
        $data->item[ASSIGNTYPE_INDIVIDUAL][$users[2]->id] = 1;
        $data->completiontime[ASSIGNTYPE_INDIVIDUAL][$users[2]->id] = '1 day';
        $data->completionevent[ASSIGNTYPE_INDIVIDUAL][$users[2]->id] = COMPLETION_EVENT_PROGRAM_COMPLETION;
        $data->completioninstance[ASSIGNTYPE_INDIVIDUAL][$users[2]->id] = -1;

        // Exception (no course with id -1).
        $data->item[ASSIGNTYPE_INDIVIDUAL][$users[4]->id] = 1;
        $data->completiontime[ASSIGNTYPE_INDIVIDUAL][$users[4]->id] = '1 day';
        $data->completionevent[ASSIGNTYPE_INDIVIDUAL][$users[4]->id] = COMPLETION_EVENT_COURSE_COMPLETION;
        $data->completioninstance[ASSIGNTYPE_INDIVIDUAL][$users[4]->id] = -1;

        $category = new individuals_category();
        $category->update_assignments($data);

        // Apply assignment changes.
        $programs[0]->update_learner_assignments(true);

        // Make sure the exception was generated.
        $this->assertCount(2, $DB->get_records('prog_exception'));

        $rid = $this->create_report('program_completion', 'Test program completion report');

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'prog', 'fullname', null, null, null, 0);
        $this->add_column($report, 'user', 'username', null, null, null, 0);
        $this->add_column($report, 'progcompletion', 'isassigned', null, null, null, 0);
        $this->add_column($report, 'progcompletion', 'starteddate', null, null, null, 0);
        $this->add_filter($rid, 'progcompletion', 'isassigned', 0, 'Is assigned?', 0, 1);

        $report = reportbuilder::create($rid);
        list($sql, $params, $cache) = $report->build_query();

        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(5, $records); // No filters, we expect all users in the report.

        $filters = $report->get_filters();
        $this->assertCount(1, $filters);
        $filter = $filters['progcompletion-isassigned'];

        // Set filter to show assigned users.
        $filter->set_data(['value' => 1]);

        list($sql, $params, $cache) = $report->build_query(false, true);
        $result = [];
        foreach ($DB->get_records_sql($sql, $params) as $record) {
            $result[] = $record->user_username;
        }
        $this->assertCount(3, $result); // Two users with unresolved exceptions not shown.
        $this->assertEqualsCanonicalizing([$users[3]->username, $users[1]->username, $users[0]->username], $result);

        // Set filter to show not assigned users.
        $filter->set_data(['value' => 0]);

        list($sql, $params, $cache) = $report->build_query(false, true);
        $result = [];
        foreach ($DB->get_records_sql($sql, $params) as $record) {
            $result[] = $record->user_username;
        }
        $this->assertCount(2, $result); // Two users with unresolved exceptions when filtered by 'not assigned'.
        $this->assertEqualsCanonicalizing([$users[2]->username, $users[4]->username], $result);
    }
}
