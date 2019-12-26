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
require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');

/**
 * Class totara_program_duedates_report_testcase
 *
 * Provides the duedates report associated tests for programs and certifications.
 */
class totara_program_duedates_report_testcase extends reportcache_advanced_testcase {

    /** @var totara_reportbuilder_cache_generator $data_generator */
    private $data_generator;

    /** @var totara_program_generator $program_generator */
    private $program_generator;

    protected function setUp() {
        parent::setUp();

        $this->setAdminUser();
        $this->resetAfterTest(true);

        $this->data_generator = $this->getDataGenerator();
        $this->program_generator = $this->data_generator->get_plugin_generator('totara_program');
    }

    protected function tearDown() {
        $this->data_generator = null;
        $this->program_generator = null;

        parent::tearDown();
    }

    public function test_rb_cert_assignment_duedates_is_capable() {
        $certification = $this->data_generator->create_certification();
        $user = $this->data_generator->create_user();

        // Set up certification assignment report and embedded object for is_capable checks.
        $config = new rb_config();
        $config->set_embeddata(['programid' => $certification->id]);
        $report = reportbuilder::create_embedded('cert_assignment_duedates', $config);
        $embeddedobject = $report->embedobj;

        // Test admin can access report and generic user cannot.
        self::assertTrue($embeddedobject->is_capable(get_admin()->id, $report));
        self::assertFalse($embeddedobject->is_capable($user->id, $report));

        // Test generic user with configure assignments capability can access report.
        $roleid = $this->data_generator->create_role();
        role_change_permission($roleid, context_system::instance(), 'totara/program:configureassignments', CAP_ALLOW);
        $this->data_generator->role_assign($roleid, $user->id);
        self::assertTrue($embeddedobject->is_capable($user->id, $report));
    }

    public function test_rb_cert_assignment_duedates_report() {
        global $DB;

        // Set up users, audiences, and a certification.
        $user1 = $this->data_generator->create_user(['username' => 'user1']);
        $user2 = $this->data_generator->create_user(['username' => 'user2']);
        $user3 = $this->data_generator->create_user(['username' => 'user3']);
        $user4 = $this->data_generator->create_user(['username' => 'user4']);

        $certification = $this->data_generator->create_certification();

        $cohort1 = $this->data_generator->create_cohort();
        cohort_add_member($cohort1->id, $user1->id);
        cohort_add_member($cohort1->id, $user4->id);
        cohort_add_member($cohort1->id, $user2->id);

        $cohort2 = $this->data_generator->create_cohort();
        cohort_add_member($cohort2->id, $user2->id);
        cohort_add_member($cohort2->id, $user3->id);

        // Assign users and an audience to the programs.
        $this->program_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);
        $this->program_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);
        $this->program_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user3->id, null, true);
        $this->program_generator->assign_to_program($certification->id, ASSIGNTYPE_COHORT, $cohort1->id, null, true);
        $this->program_generator->assign_to_program($certification->id, ASSIGNTYPE_COHORT, $cohort2->id, null, true);

        self::assertEquals(5, $DB->count_records('prog_assignment')); // 3 individual + 2 cohort type assignments.
        self::assertEquals(8, $DB->count_records('prog_user_assignment')); // 3 users + 5 from a cohort.

        $params = ['programid' => $certification->id, 'assignmenttype' => ASSIGNTYPE_COHORT, 'assignmenttypeid' => $cohort2->id];
        $assignmentid = $DB->get_field('prog_assignment', 'id', $params);
        self::assertEquals(2, $DB->count_records('prog_user_assignment', ['assignmentid' => $assignmentid])); // 3 from cohort1.

        // Set up certification assignment duedates report.
        $config = new rb_config();
        $config->set_embeddata(['programid' => $certification->id, 'assignmentid' => $assignmentid]);
        $report = reportbuilder::create_embedded('cert_assignment_duedates', $config);
        $this->add_column($report, 'user', 'username', null, null, '', 0);
        $report = reportbuilder::create_embedded('cert_assignment_duedates', $config); // Recreate after adding column.
        list($sql, $sqlparams, $cache) = $report->build_query(false, false, false);

        $records = $DB->get_records_sql($sql, $sqlparams);
        self::assertCount(2, $records);

        $reportusers = [];
        foreach ($records as $record) {
            $reportusers[] = $record->user_username;
        }
        // Only users from cohort2 should be with the expected assignment id.
        self::assertEqualsCanonicalizing([$user3->username, $user2->username], $reportusers);
    }

    public function test_rb_program_assignment_duedates_is_capable() {
        $program = $this->program_generator->create_program();
        $user = $this->data_generator->create_user();

        // Set up program assignment report and embedded object for is_capable checks.
        $config = new rb_config();
        $config->set_embeddata(['programid' => $program->id]);
        $report = reportbuilder::create_embedded('program_assignment_duedates', $config);
        $embeddedobject = $report->embedobj;

        // Test admin can access report and generic user cannot.
        self::assertTrue($embeddedobject->is_capable(get_admin()->id, $report));
        self::assertFalse($embeddedobject->is_capable($user->id, $report));

        // Test generic user with configure assignments capability can access report.
        $roleid = $this->data_generator->create_role();
        role_change_permission($roleid, context_system::instance(), 'totara/program:configureassignments', CAP_ALLOW);
        $this->data_generator->role_assign($roleid, $user->id);
        self::assertTrue($embeddedobject->is_capable($user->id, $report));
    }

    public function test_rb_program_assignment_duedates_report() {
        global $DB;

        // Set up users, audiences, and a program.
        $user1 = $this->data_generator->create_user(['username' => 'user1']);
        $user2 = $this->data_generator->create_user(['username' => 'user2']);
        $user3 = $this->data_generator->create_user(['username' => 'user3']);
        $user4 = $this->data_generator->create_user(['username' => 'user4']);

        $program = $this->program_generator->create_program();

        $cohort1 = $this->data_generator->create_cohort();
        cohort_add_member($cohort1->id, $user1->id);
        cohort_add_member($cohort1->id, $user4->id);
        cohort_add_member($cohort1->id, $user2->id);

        $cohort2 = $this->data_generator->create_cohort();
        cohort_add_member($cohort2->id, $user2->id);
        cohort_add_member($cohort2->id, $user3->id);

        // Assign users and an audience to the programs.
        $this->program_generator->assign_to_program($program->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);
        $this->program_generator->assign_to_program($program->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);
        $this->program_generator->assign_to_program($program->id, ASSIGNTYPE_INDIVIDUAL, $user3->id, null, true);
        $this->program_generator->assign_to_program($program->id, ASSIGNTYPE_COHORT, $cohort1->id, null, true);
        $this->program_generator->assign_to_program($program->id, ASSIGNTYPE_COHORT, $cohort2->id, null, true);

        self::assertEquals(5, $DB->count_records('prog_assignment')); // 3 individual + 2 cohort type assignments.
        self::assertEquals(8, $DB->count_records('prog_user_assignment')); // 3 users + 5 from a cohort.

        $params = ['programid' => $program->id, 'assignmenttype' => ASSIGNTYPE_COHORT, 'assignmenttypeid' => $cohort1->id];
        $assignmentid = $DB->get_field('prog_assignment', 'id', $params);
        self::assertEquals(3, $DB->count_records('prog_user_assignment', ['assignmentid' => $assignmentid])); // 3 from cohort1.

        // Set up program assignment duedates report.
        $config = new rb_config();
        $config->set_embeddata(['programid' => $program->id, 'assignmentid' => $assignmentid]);
        $report = reportbuilder::create_embedded('program_assignment_duedates', $config);
        $this->add_column($report, 'user', 'username', null, null, '', 0);
        $report = reportbuilder::create_embedded('program_assignment_duedates', $config); // Recreate after adding column.
        list($sql, $sqlparams, $cache) = $report->build_query(false, false, false);

        $records = $DB->get_records_sql($sql, $sqlparams);
        self::assertCount(3, $records);

        $reportusers = [];
        foreach ($records as $record) {
            $reportusers[] = $record->user_username;
        }
        // Only users from cohort1 should be with the expected assignment id.
        self::assertEqualsCanonicalizing([$user1->username, $user2->username, $user4->username], $reportusers);
    }
}
