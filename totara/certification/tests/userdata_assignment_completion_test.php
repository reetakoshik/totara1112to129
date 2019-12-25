<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_certification
 */

use totara_certification\userdata\assignment_completion;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/program/tests/userdata_assignment_completion_base_testcase.php');

/**
 * Tests for certification assignment and completion userdata items
 *
 * @group totara_userdata
 */
class totara_certification_userdata_assignment_completion_test extends totara_program_base_userdata_assignment_completion_base_test {

    /**
     * Returns the item class, needs to be based on \totara_program\userdata\base_assignment_completion
     *
     * @return string
     */
    protected function get_item_class(): string {
        return assignment_completion::class;
    }

    /**
     * Test counting the completion records in compatible contexts
     */
    public function test_count() {
        global $DB;

        $this->resetAfterTest(true);

        // Set up users.
        $user1 = new target_user($this->getDataGenerator()->create_user());
        $user2 = new target_user($this->getDataGenerator()->create_user());

        $generator = $this->getDataGenerator();
        /** @var \totara_program_generator $programgenerator */
        $programgenerator = $generator->get_plugin_generator('totara_program');

        $category1 = $generator->create_category();
        $category2 = $generator->create_category();

        $programid1 = $programgenerator->create_certification(['fullname' => 'Certification 1', 'category' => $category1->id]);
        $programid2 = $programgenerator->create_certification(['fullname' => 'Certification 2', 'category' => $category1->id]);
        $programid3 = $programgenerator->create_certification(['fullname' => 'Certification 3', 'category' => $category2->id]);

        $programgenerator->assign_program($programid1, [$user1->id, $user2->id]);
        $programgenerator->assign_program($programid2, [$user1->id]);
        $programgenerator->assign_program($programid3, [$user1->id, $user2->id]);

        // Have a certification to be able to test that it won't be affected.
        $progam = $programgenerator->create_program(['fullname' => 'Program', 'category' => $category1->id]);
        $programgenerator->assign_program($progam->id, [$user1->id]);

        // 3 programs + 1 certification assignment.
        $this->assertEquals(4, $DB->count_records('prog_user_assignment', ['userid' => $user1->id]));
        $this->assertEquals(4, $DB->count_records('prog_completion', ['userid' => $user1->id]));
        $this->assertEquals(2, $DB->count_records('prog_user_assignment', ['userid' => $user2->id]));
        $this->assertEquals(2, $DB->count_records('prog_completion', ['userid' => $user2->id]));

        // Count in system context.
        $result = assignment_completion::execute_count($user1, \context_system::instance());
        $this->assertEquals(3, $result);

        $result = assignment_completion::execute_count($user2, \context_system::instance());
        $this->assertEquals(2, $result);

        // Count in category context.
        $result = assignment_completion::execute_count($user1, \context_coursecat::instance($category1->id));
        $this->assertEquals(2, $result);

        $result = assignment_completion::execute_count($user1, \context_coursecat::instance($category2->id));
        $this->assertEquals(1, $result);

        // Count in program context.
        $result = assignment_completion::execute_count($user1, \context_program::instance($programid1));
        $this->assertEquals(1, $result);

        $result = assignment_completion::execute_count($user1, \context_program::instance($programid2));
        $this->assertEquals(1, $result);

        $result = assignment_completion::execute_count($user2, \context_program::instance($programid1));
        $this->assertEquals(1, $result);

        $result = assignment_completion::execute_count($user2, \context_program::instance($programid2));
        $this->assertEquals(0, $result);

        $result = assignment_completion::execute_count($user2, \context_program::instance($programid3));
        $this->assertEquals(1, $result);
    }

    /**
     * Create fixtures to be used in the test cases.
     */
    protected function create_fixtures() {
        $this->resetAfterTest(true);

        $fixtures = new class() {
            /** @var target_user */
            public $activeuser, $controluser;
            /** @var \stdClass */
            public $category1, $category2;
            /** @var int */
            public $program1, $program2, $program3;
            /** @var \stdClass */
            public $cert1, $cert2, $cert3;
            /** @var int */
            public $controlprogramid;
        };

        // Set up users.
        $fixtures->activeuser = new target_user($this->getDataGenerator()->create_user());
        $fixtures->controluser = new target_user($this->getDataGenerator()->create_user());

        $generator = $this->getDataGenerator();
        /** @var \totara_program_generator $programgenerator */
        $programgenerator = $generator->get_plugin_generator('totara_program');

        $fixtures->category1 = $generator->create_category();
        $fixtures->category2 = $generator->create_category();

        $programid1 = $programgenerator->create_certification(['category' => $fixtures->category1->id]);
        $programid2 = $programgenerator->create_certification(['category' => $fixtures->category1->id]);
        $programid3 = $programgenerator->create_certification(['category' => $fixtures->category2->id]);

        $fixtures->program1 = $this->get_program($programid1);
        $fixtures->program2 = $this->get_program($programid2);
        $fixtures->program3 = $this->get_program($programid3);

        // As create_certification just returns the related program id we need to load the certifications.
        $fixtures->cert1 = $this->get_certification($fixtures->program1->id);
        $fixtures->cert2 = $this->get_certification($fixtures->program2->id);
        $fixtures->cert3 = $this->get_certification($fixtures->program3->id);

        $programgenerator->assign_to_program($fixtures->program1->id, ASSIGNTYPE_INDIVIDUAL, $fixtures->activeuser->id, null, true);
        $programgenerator->assign_to_program($fixtures->program2->id, ASSIGNTYPE_INDIVIDUAL, $fixtures->activeuser->id, ['completionevent' => COMPLETION_EVENT_FIRST_LOGIN], true);
        $programgenerator->assign_to_program($fixtures->program3->id, ASSIGNTYPE_INDIVIDUAL, $fixtures->activeuser->id, null, true);

        $programgenerator->assign_to_program($fixtures->program1->id, ASSIGNTYPE_INDIVIDUAL, $fixtures->controluser->id, null, true);
        $programgenerator->assign_to_program($fixtures->program2->id, ASSIGNTYPE_INDIVIDUAL, $fixtures->controluser->id, null, true);
        $programgenerator->assign_to_program($fixtures->program3->id, ASSIGNTYPE_INDIVIDUAL, $fixtures->controluser->id, null, true);

        // Create non-certification program to be able to test that it won't be affected.
        $program = $programgenerator->create_program(['fullname' => 'Certification', 'category' => $fixtures->category1->id]);
        $fixtures->controlprogramid = $program->id;
        $programgenerator->assign_program($fixtures->controlprogramid, [$fixtures->activeuser->id]);
        $this->create_prog_history($fixtures->controlprogramid, $fixtures->activeuser->id);
        $this->create_extension($fixtures->activeuser, $fixtures->controlprogramid);

        // Create completion history entries.
        $this->create_certif_history($fixtures->cert1->id, $fixtures->activeuser->id);
        $this->create_certif_history($fixtures->cert2->id, $fixtures->activeuser->id);
        $this->create_certif_history($fixtures->cert3->id, $fixtures->activeuser->id);
        $this->create_certif_history($fixtures->cert1->id, $fixtures->controluser->id);
        $this->create_certif_history($fixtures->cert2->id, $fixtures->controluser->id);
        $this->create_certif_history($fixtures->cert3->id, $fixtures->controluser->id);

        $this->create_prog_history($fixtures->program1->id, $fixtures->activeuser->id);
        $this->create_prog_history($fixtures->program2->id, $fixtures->activeuser->id);
        $this->create_prog_history($fixtures->program3->id, $fixtures->activeuser->id);
        $this->create_prog_history($fixtures->program1->id, $fixtures->controluser->id);
        $this->create_prog_history($fixtures->program2->id, $fixtures->controluser->id);
        $this->create_prog_history($fixtures->program3->id, $fixtures->controluser->id);

        // We want the message being sent to have some entries in the messagelog.
        $this->send_messages();

        $this->create_extension($fixtures->activeuser, $fixtures->program1->id);
        $this->create_extension($fixtures->activeuser, $fixtures->program2->id);
        $this->create_extension($fixtures->activeuser, $fixtures->program3->id);

        $this->create_extension($fixtures->controluser, $fixtures->program1->id);
        $this->create_extension($fixtures->controluser, $fixtures->program2->id);
        $this->create_extension($fixtures->controluser, $fixtures->program3->id);

        return $fixtures;
    }

    /**
     * @param int $programid
     * @return stdClass
     */
    private function get_program(int $programid): stdClass {
        global $DB;
        return $DB->get_record('prog', ['id' => $programid]);
    }

    /**
     * Test purging the completion records in system context
     */
    public function test_purge_system_context() {
        $fixtures = $this->create_fixtures();

        $result = assignment_completion::execute_purge($fixtures->activeuser, \context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $this->assert_entries_not_exist($fixtures->activeuser, $fixtures->program1->id);
        $this->assert_entries_not_exist($fixtures->activeuser, $fixtures->program2->id);
        $this->assert_entries_not_exist($fixtures->activeuser, $fixtures->program3->id);
        // Certification entries should still exist.
        $this->assert_entries_exist($fixtures->activeuser, $fixtures->controlprogramid, false);

        $this->assert_entries_exist($fixtures->controluser, $fixtures->program1->id);
        $this->assert_entries_exist($fixtures->controluser, $fixtures->program2->id);
        $this->assert_entries_exist($fixtures->controluser, $fixtures->program3->id);
    }

    /**
     * Test purging the completion records in course category context
     */
    public function test_purge_coursecat_context() {
        $fixtures = $this->create_fixtures();

        $categorycontext = \context_coursecat::instance($fixtures->category1->id);

        $result = assignment_completion::execute_purge($fixtures->activeuser, $categorycontext);
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        // Program 1 and 2 belong to category1, so should be gone.
        $this->assert_entries_not_exist($fixtures->activeuser, $fixtures->program1->id);
        $this->assert_entries_not_exist($fixtures->activeuser, $fixtures->program2->id);
        // Program 3 should be untouched.
        $this->assert_entries_exist($fixtures->activeuser, $fixtures->program3->id);
        // Certification entries should still exist.
        $this->assert_entries_exist($fixtures->activeuser, $fixtures->controlprogramid, false);

        // All data of controluser should be untouched.
        $this->assert_entries_exist($fixtures->controluser, $fixtures->program1->id);
        $this->assert_entries_exist($fixtures->controluser, $fixtures->program2->id);
        $this->assert_entries_exist($fixtures->controluser, $fixtures->program3->id);
    }

    /**
     * Test purging the completion records in program context
     */
    public function test_purge_program_context() {
        $fixtures = $this->create_fixtures();

        $programcontext = \context_program::instance($fixtures->program2->id);

        $result = assignment_completion::execute_purge($fixtures->activeuser, $programcontext);
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        // Program 2 should be gone.
        $this->assert_entries_not_exist($fixtures->activeuser, $fixtures->program2->id);
        // Program 1 and 3 should be untouched.
        $this->assert_entries_exist($fixtures->activeuser, $fixtures->program1->id);
        $this->assert_entries_exist($fixtures->activeuser, $fixtures->program3->id);
        // Certification entries should still exist.
        $this->assert_entries_exist($fixtures->activeuser, $fixtures->controlprogramid, false);

        // All data of controluser should be untouched.
        $this->assert_entries_exist($fixtures->controluser, $fixtures->program1->id);
        $this->assert_entries_exist($fixtures->controluser, $fixtures->program2->id);
        $this->assert_entries_exist($fixtures->controluser, $fixtures->program3->id);
    }

    /**
     * Test exporting the completion records in system context
     */
    public function test_export_system_context() {
        $fixtures = $this->create_fixtures();

        $activeuser = $fixtures->activeuser;

        $result = assignment_completion::execute_export($activeuser, \context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertArrayHasKey('completion', $result->data);
        $this->assertArrayHasKey('history', $result->data);

        $this->assertCount(3, $result->data['assignment']);
        $this->assertCount(1, $result->data['future_assignment']);
        $this->assertCount(3, $result->data['completion']);
        $this->assertCount(3, $result->data['history']);
        $this->assertCount(3, $result->data['extension']);

        $assignment = $result->data['assignment'];
        $futureassignment = $result->data['future_assignment'];
        $completion = $result->data['completion'];
        $history = $result->data['history'];
        $extension = $result->data['extension'];

        // Make sure only activeusers entries are exported.
        $this->assertEmpty(array_filter($assignment, function($item) use ($activeuser) {
            return $activeuser->id != $item->userid;
        }));
        $this->assertEmpty(array_filter($futureassignment, function($item) use ($activeuser) {
            return $activeuser->id != $item->userid;
        }));
        $this->assertEmpty(array_filter($completion, function($item) use ($activeuser) {
            return $activeuser->id != $item->userid;
        }));
        $this->assertEmpty(array_filter($history, function($item) use ($activeuser) {
            return $activeuser->id != $item->userid;
        }));
        $this->assertEmpty(array_filter($extension, function($item) use ($activeuser) {
            return $activeuser->id != $item->userid;
        }));

        // Check that the expected programs are there.
        $certifids = array_column($completion, 'certifid');
        $this->assertContains($fixtures->cert1->id, $certifids);
        $this->assertContains($fixtures->cert2->id, $certifids);
        $this->assertContains($fixtures->cert3->id, $certifids);

        $historycertifids = array_column($history, 'certifid');
        $this->assertContains($fixtures->cert1->id, $historycertifids);
        $this->assertContains($fixtures->cert2->id, $historycertifids);
        $this->assertContains($fixtures->cert3->id, $historycertifids);

        $programids = array_column($extension, 'programid');
        $this->assertContains($fixtures->program1->id, $programids);
        $this->assertContains($fixtures->program2->id, $programids);
        $this->assertContains($fixtures->program3->id, $programids);

        // Make sure history is not the same as completion.
        $completionids = array_column($completion, 'id');
        $historyids = array_column($history, 'id');
        $this->assertCount(3, array_diff($completionids, $historyids));

    }

    /**
     * Test exporting the completion records in category context
     */
    public function test_export_category_context() {
        $fixtures = $this->create_fixtures();

        $activeuser = $fixtures->activeuser;

        $result = assignment_completion::execute_export($activeuser, \context_coursecat::instance($fixtures->category1->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertArrayHasKey('completion', $result->data);
        $this->assertArrayHasKey('history', $result->data);

        $this->assertCount(2, $result->data['assignment']);
        $this->assertCount(1, $result->data['future_assignment']);
        $this->assertCount(2, $result->data['completion']);
        $this->assertCount(2, $result->data['history']);
        $this->assertCount(2, $result->data['extension']);

        $assignment = $result->data['assignment'];
        $futureassignment = $result->data['future_assignment'];
        $completion = $result->data['completion'];
        $history = $result->data['history'];
        $extension = $result->data['extension'];

        // Make sure only activeusers entries are exported.
        $this->assertEmpty(array_filter($assignment, function($item) use ($activeuser) {
            return $activeuser->id != $item->userid;
        }));
        $this->assertEmpty(array_filter($futureassignment, function($item) use ($activeuser) {
            return $activeuser->id != $item->userid;
        }));
        $this->assertEmpty(array_filter($completion, function($item) use ($activeuser) {
            return $activeuser->id != $item->userid;
        }));
        $this->assertEmpty(array_filter($history, function($item) use ($activeuser) {
            return $activeuser->id != $item->userid;
        }));
        $this->assertEmpty(array_filter($extension, function($item) use ($activeuser) {
            return $activeuser->id != $item->userid;
        }));

        // Check that the expected programs are there.
        $certifids = array_column($completion, 'certifid');
        $this->assertContains($fixtures->cert1->id, $certifids);
        $this->assertContains($fixtures->cert2->id, $certifids);

        $historycertifids = array_column($history, 'certifid');
        $this->assertContains($fixtures->cert1->id, $historycertifids);
        $this->assertContains($fixtures->cert2->id, $historycertifids);

        $programids = array_column($extension, 'programid');
        $this->assertContains($fixtures->program1->id, $programids);
        $this->assertContains($fixtures->program2->id, $programids);

        // Make sure history is not the same as completion.
        $completionids = array_column($completion, 'id');
        $historyids = array_column($history, 'id');
        $this->assertCount(2, array_diff($completionids, $historyids));
    }

    /**
     * Test exporting the completion records in program context
     */
    public function test_export_program_context() {
        $fixtures = $this->create_fixtures();

        $activeuser = $fixtures->activeuser;

        $result = assignment_completion::execute_export($activeuser, \context_program::instance($fixtures->program2->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertArrayHasKey('completion', $result->data);
        $this->assertArrayHasKey('history', $result->data);

        $this->assertCount(1, $result->data['assignment']);
        $this->assertCount(1, $result->data['future_assignment']);
        $this->assertCount(1, $result->data['completion']);
        $this->assertCount(1, $result->data['history']);
        $this->assertCount(1, $result->data['extension']);

        $assignment = $result->data['assignment'];
        $futureassignment = $result->data['future_assignment'];
        $completion = $result->data['completion'];
        $history = $result->data['history'];
        $extension = $result->data['extension'];

        // Make sure only activeusers entries are exported.
        $this->assertEmpty(array_filter($assignment, function($item) use ($activeuser) {
            return $activeuser->id != $item->userid;
        }));
        $this->assertEmpty(array_filter($futureassignment, function($item) use ($activeuser) {
            return $activeuser->id != $item->userid;
        }));
        $this->assertEmpty(array_filter($completion, function($item) use ($activeuser) {
            return $activeuser->id != $item->userid;
        }));
        $this->assertEmpty(array_filter($history, function($item) use ($activeuser) {
            return $activeuser->id != $item->userid;
        }));
        $this->assertEmpty(array_filter($extension, function($item) use ($activeuser) {
            return $activeuser->id != $item->userid;
        }));

        // Check that the expected programs are there.
        $certifids = array_column($completion, 'certifid');
        $this->assertContains($fixtures->cert2->id, $certifids);

        $historycertifids = array_column($history, 'certifid');
        $this->assertContains($fixtures->cert2->id, $historycertifids);

        $programids = array_column($extension, 'programid');
        $this->assertContains($fixtures->program2->id, $programids);

        // Make sure history is not the same as completion.
        $completionids = array_column($completion, 'id');
        $historyids = array_column($history, 'id');
        $this->assertCount(1, array_diff($completionids, $historyids));
    }

}