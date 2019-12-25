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

/**
 * Class totara_program_program_assignments_class_testcase
 *
 * Tests the methods in the program class in program_assignments.class.php
 */
class totara_program_program_assignments_class_testcase extends advanced_testcase {
    /** @var totara_reportbuilder_cache_generator $data_generator */
    private $data_generator;

    /** @var totara_program_generator $program_generator */
    private $program_generator;

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        global $CFG;
        require_once($CFG->dirroot . '/totara/program/program_assignments.class.php');
    }

    protected function setUp() {
        parent::setUp();
        $this->resetAfterTest();

        $this->data_generator = $this->getDataGenerator();
        $this->program_generator = $this->data_generator->get_plugin_generator('totara_program');
    }

    protected function tearDown() {
        $this->data_generator = null;
        $this->program_generator = null;

        parent::tearDown();
    }

    public function test_remove_outdated_assignments() {
        global $DB;

        // Set up users, audiences, and programs.
        $user1 = $this->data_generator->create_user();
        $user2 = $this->data_generator->create_user();
        $user3 = $this->data_generator->create_user();
        $user4 = $this->data_generator->create_user();

        $program1 = $this->program_generator->create_program();
        $program2 = $this->program_generator->create_program();
        $program3 = $this->program_generator->create_program();

        $cohort1 = $this->data_generator->create_cohort();
        cohort_add_member($cohort1->id, $user1->id);
        cohort_add_member($cohort1->id, $user4->id);

        $cohort2 = $this->data_generator->create_cohort();
        cohort_add_member($cohort2->id, $user2->id);
        cohort_add_member($cohort2->id, $user3->id);

        // Assign the user to the programs.
        $this->program_generator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);
        $this->program_generator->assign_to_program($program2->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);
        $this->program_generator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);
        $this->program_generator->assign_to_program($program2->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);
        $this->program_generator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $user3->id, null, true);
        $this->program_generator->assign_to_program($program1->id, ASSIGNTYPE_COHORT, $cohort1->id, null, true);
        $this->program_generator->assign_to_program($program3->id, ASSIGNTYPE_COHORT, $cohort2->id, null, true);

        self::assertEquals(7, $DB->count_records('prog_assignment')); // 5 individual + 2 cohort type assignments.
        self::assertEquals(9, $DB->count_records('prog_user_assignment')); // 5 users + 4 from a cohort.
        self::assertEquals(5, $DB->count_records('prog_user_assignment', ['programid' => $program1->id])); // 3 users + 2 from a cohort.
        self::assertEquals(2, $DB->count_records('prog_user_assignment', ['programid' => $program2->id])); // 2 users.
        self::assertEquals(2, $DB->count_records('prog_user_assignment', ['programid' => $program3->id])); // 2 from a cohort.

        // User 1 is assigned individually and via a cohort, so there will be 2 records in the table.
        self::assertEquals(2, $DB->count_records('prog_user_assignment', ['userid' => $user1->id, 'programid' => $program1->id]));

        // Remove user 1 from cohorts assignment.
        $category = new cohorts_category();
        $category->remove_outdated_assignments($program1->id, $cohort1->id, [$user1->id]);

        self::assertEquals(7, $DB->count_records('prog_assignment')); // Assignment type counts remain the same.
        self::assertEquals(8, $DB->count_records('prog_user_assignment')); // User records changed since we removed one.
        self::assertEquals(4, $DB->count_records('prog_user_assignment', ['programid' => $program1->id]));
        self::assertEquals(2, $DB->count_records('prog_user_assignment', ['programid' => $program2->id]));
        self::assertEquals(2, $DB->count_records('prog_user_assignment', ['programid' => $program3->id]));

        // Verify individual assigment for the removed user still exists.
        self::assertEquals(1, $DB->count_records('prog_user_assignment', ['userid' => $user1->id, 'programid' => $program1->id]));
    }
}
