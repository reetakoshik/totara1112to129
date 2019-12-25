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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_hierarchy
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

global $CFG;
require_once($CFG->dirroot . '/totara/hierarchy/prefix/goal/lib.php');

class totara_hierarchy_goal_user_assignments_testcase extends advanced_testcase {

    private function setup_common_data(): stdClass {
        $this->resetAfterTest(true);

        $data = new stdClass();

        /** @var totara_hierarchy_generator $datagen */
        $data->gen = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $pos_framework = $data->gen->create_framework('position');
        $data->pos1 = $data->gen->create_hierarchy($pos_framework->id, 'position');
        $data->pos2 = $data->gen->create_hierarchy($pos_framework->id, 'position');

        $org_framework = $data->gen->create_framework('organisation');
        $data->org1 = $data->gen->create_hierarchy($org_framework->id, 'organisation');
        $data->org2 = $data->gen->create_hierarchy($org_framework->id, 'organisation', ['parentid' => $data->org1->id]);
        $data->org3 = $data->gen->create_hierarchy($org_framework->id, 'organisation', ['parentid' => $data->org2->id]);
        $data->org4 = $data->gen->create_hierarchy($org_framework->id, 'organisation');
        $data->org5 = $data->gen->create_hierarchy($org_framework->id, 'organisation', ['parentid' => $data->org1->id]);

        $goal_framework = $data->gen->create_framework('goal');
        $goalrecord = $data->gen->create_hierarchy($goal_framework->id, 'goal');
        $data->goalid = $goalrecord->id;
        $data->goal = new goal();

        $data->user1 = $this->getDataGenerator()->create_user();
        $data->user2 = $this->getDataGenerator()->create_user();
        $data->user3 = $this->getDataGenerator()->create_user();
        $data->user4 = $this->getDataGenerator()->create_user();
        $data->user5 = $this->getDataGenerator()->create_user();

        return $data;
    }

    /**
     * Create some normal user assginments.
     *
     * @param stdClass $data
     * @return stdClass
     */
    private function setup_assignment_data(stdClass $data): stdClass {
        global $DB;

        $data->user1ja1 = \totara_job\job_assignment::create_default($data->user1->id, ['organisationid' => $data->org1->id]);
        $data->user1ja2 = \totara_job\job_assignment::create_default($data->user1->id, ['organisationid' => $data->org2->id]);
        $data->user1ja3 = \totara_job\job_assignment::create_default($data->user1->id, ['organisationid' => $data->org2->id]);
        $data->user1ja4 = \totara_job\job_assignment::create_default($data->user1->id, ['organisationid' => $data->org3->id]);
        $data->user1ja5 = \totara_job\job_assignment::create_default($data->user1->id, ['organisationid' => $data->org4->id, 'positionid' => $data->pos1->id]);

        $data->user2ja1 = \totara_job\job_assignment::create_default($data->user2->id, ['organisationid' => $data->org3->id]);

        $data->original_timemodified = 12345;
        $data->original_usermodified = 666;

        $assignment1 = new stdClass();
        $assignment1->goalid = $data->goalid;
        $assignment1->orgid = $data->org1->id;
        $assignment1->includechildren = true;
        $assignment1->timemodified = 67890;
        $assignment1->usermodified = 888;
        $assignment1->id = $DB->insert_record('goal_grp_org', $assignment1);
        $data->assignment1 = $assignment1;

        $data->goal->update_user_assignments($data->goalid, GOAL_ASSIGNMENT_ORGANISATION, $assignment1);

        // Change the timemodified to something distinct.
        $DB->set_field('goal_user_assignment', 'timemodified', $data->original_timemodified);

        // Change the usermodified to something distinct.
        $DB->set_field('goal_user_assignment', 'usermodified', $data->original_usermodified);

        return $data;
    }

    /**
     * Adds some legacy data. Legacy data isn't functional, so this is just for a control.
     *
     * @param stdClass $data
     * @return stdClass
     */
    private function setup_legacy_assignment_data(stdClass $data): stdClass {
        global $DB;

        $data->legacy_timemodified = 45678;
        $data->legacy_usermodified = 777;

        $record1 = new stdClass();
        $record1->assigntype = GOAL_ASSIGNMENT_INDIVIDUAL;
        $record1->assignmentid = 0;
        $record1->goalid = $data->goalid;
        $record1->userid = $data->user1->id;
        $assignmenttype = GOAL_ASSIGNMENT_ORGANISATION;
        $record1->extrainfo = "OLD:{$assignmenttype},{$data->org1->id}";
        $record1->timemodified = $data->legacy_timemodified;
        $record1->usermodified = $data->legacy_usermodified;

        $DB->insert_record('goal_user_assignment', $record1);

        return $data;
    }

    private function setup_all_data(): stdClass {
        global $DB;

        $data = $this->setup_common_data();
        $data = $this->setup_assignment_data($data);
        $data = $this->setup_legacy_assignment_data($data);

        $data->existing_user_assignments = $DB->get_records('goal_user_assignment', [], 'id');

        return $data;
    }

    private function check_expected(
        array $existing_user_assignments,
        array $expected_new_items,
        int $new_assignmenttype,
        int $new_assignmentid,
        int $new_goalid,
        int $new_timebefore,
        int $new_timeafter,
        int $new_usermodified
    ) {
        global $DB;

        $all_user_assignments = $DB->get_records('goal_user_assignment', [], 'id');

        $actual_new_items = [];
        foreach ($all_user_assignments as $key => $user_assignment) {
            if (isset($existing_user_assignments[$key])) {
                $this->assertEquals($user_assignment, $existing_user_assignments[$key]);
                continue;
            }

            $actual_new_items[$user_assignment->userid][$user_assignment->extrainfo] = true;
            $this->assertEquals($new_assignmenttype, $user_assignment->assigntype);
            $this->assertEquals($new_assignmentid, $user_assignment->assignmentid);
            $this->assertEquals($new_goalid, $user_assignment->goalid);
            $this->assertGreaterThanOrEqual($new_timebefore, $user_assignment->timemodified);
            $this->assertLessThanOrEqual($new_timeafter, $user_assignment->timemodified);
            $this->assertEquals($new_usermodified, $user_assignment->usermodified);
        }

        $this->assertEquals($expected_new_items, $actual_new_items);
    }

    public function test_update_user_assignments_test_setup_data() {
        global $DB;

        $data = $this->setup_common_data();
        $data = $this->setup_assignment_data($data);

        $existing_user_assignments = $DB->get_records('goal_user_assignment', [], 'id');
        $this->assertCount(4, $existing_user_assignments);

        $assignmenttype = GOAL_ASSIGNMENT_ORGANISATION;
        $expected_items = [
            $data->user1->id => [
                "ITEM:{$assignmenttype},{$data->assignment1->id},{$data->org1->id}" => true,
                "ITEM:{$assignmenttype},{$data->assignment1->id},{$data->org2->id}" => true,
                "ITEM:{$assignmenttype},{$data->assignment1->id},{$data->org3->id}" => true,
            ],
            $data->user2->id => [
                "ITEM:{$assignmenttype},{$data->assignment1->id},{$data->org3->id}" => true,
            ],
        ];

        $this->check_expected(
            [],
            $expected_items,
            GOAL_ASSIGNMENT_ORGANISATION,
            $data->assignment1->id,
            $data->goalid,
            $data->original_timemodified,
            $data->original_timemodified,
            $data->original_usermodified
        );
    }

    public function test_update_user_assignments_test_setup_legacy_data() {
        global $DB;

        $data = $this->setup_common_data();
        $data = $this->setup_legacy_assignment_data($data);

        $existing_user_assignments = $DB->get_records('goal_user_assignment', [], 'id');
        $this->assertCount(1, $existing_user_assignments);

        $assignmenttype = GOAL_ASSIGNMENT_ORGANISATION;
        $expected_items = [
            $data->user1->id => [
                "OLD:{$assignmenttype},{$data->org1->id}" => true,
            ],
        ];

        $this->check_expected(
            [],
            $expected_items,
            GOAL_ASSIGNMENT_INDIVIDUAL,
            0,
            $data->goalid,
            $data->legacy_timemodified,
            $data->legacy_timemodified,
            $data->legacy_usermodified
        );
    }

    /**
     * Check that when a new org assignment is created, existing assignments have no effect.
     */
    public function test_update_user_assignments_add_org_assignment() {
        global $DB;

        $data = $this->setup_all_data();

        $newassignment = new stdClass();
        $newassignment->goalid = $data->goalid;
        $newassignment->orgid = $data->org2->id;
        $newassignment->includechildren = false;
        $newassignment->timemodified = time() - 10;
        $newassignment->usermodified = $data->user2->id;
        $newassignment->id = $DB->insert_record('goal_grp_org', $newassignment);

        $this->setUser($data->user3);
        $timebefore = time();
        $data->goal->update_user_assignments($data->goalid, GOAL_ASSIGNMENT_ORGANISATION, $newassignment);
        $timeafter = time();

        $assignmenttype = GOAL_ASSIGNMENT_ORGANISATION;
        $expected_new_items = [
            $data->user1->id => [
                "ITEM:{$assignmenttype},{$newassignment->id},{$data->org2->id}" => true,
            ],
        ];

        $this->check_expected(
            $data->existing_user_assignments,
            $expected_new_items,
            GOAL_ASSIGNMENT_ORGANISATION,
            $newassignment->id,
            $data->goalid,
            $timebefore,
            $timeafter,
            $data->user3->id
        );
    }

    /**
     * Check that when a new org assignment is created with children, existing assignments have no effect.
     */
    public function test_update_user_assignments_add_org_assignment_with_children() {
        global $DB;

        $data = $this->setup_all_data();

        $newassignment = new stdClass();
        $newassignment->goalid = $data->goalid;
        $newassignment->orgid = $data->org2->id;
        $newassignment->includechildren = true;
        $newassignment->timemodified = time() - 10;
        $newassignment->usermodified = $data->user4->id;
        $newassignment->id = $DB->insert_record('goal_grp_org', $newassignment);

        $this->setUser($data->user5);
        $timebefore = time();
        $data->goal->update_user_assignments($data->goalid, GOAL_ASSIGNMENT_ORGANISATION, $newassignment);
        $timeafter = time();

        $assignmenttype = GOAL_ASSIGNMENT_ORGANISATION;
        $expected_new_items = [
            $data->user1->id => [
                "ITEM:{$assignmenttype},{$newassignment->id},{$data->org2->id}" => true,
                "ITEM:{$assignmenttype},{$newassignment->id},{$data->org3->id}" => true,
            ],
            $data->user2->id => [
                "ITEM:{$assignmenttype},{$newassignment->id},{$data->org3->id}" => true,
            ],
        ];

        $this->check_expected(
            $data->existing_user_assignments,
            $expected_new_items,
            GOAL_ASSIGNMENT_ORGANISATION,
            $newassignment->id,
            $data->goalid,
            $timebefore,
            $timeafter,
            $data->user5->id
        );
    }

    /**
     * Check that when a new pos assignment is created, existing org assignments have no effect.
     */
    public function test_update_user_assignments_add_pos_assignment() {
        global $DB;

        $data = $this->setup_all_data();

        $newassignment = new stdClass();
        $newassignment->goalid = $data->goalid;
        $newassignment->posid = $data->pos1->id;
        $newassignment->includechildren = true;
        $newassignment->timemodified = time() - 10;
        $newassignment->usermodified = $data->user3->id;
        $newassignment->id = $DB->insert_record('goal_grp_pos', $newassignment);

        $this->setUser($data->user4);
        $timebefore = time();
        $data->goal->update_user_assignments($data->goalid, GOAL_ASSIGNMENT_POSITION, $newassignment);
        $timeafter = time();

        $assignmenttype = GOAL_ASSIGNMENT_POSITION;
        $expected_new_items = [
            $data->user1->id => [
                "ITEM:{$assignmenttype},{$newassignment->id},{$data->pos1->id}" => true,
            ],
        ];

        $this->check_expected(
            $data->existing_user_assignments,
            $expected_new_items,
            GOAL_ASSIGNMENT_POSITION,
            $newassignment->id,
            $data->goalid,
            $timebefore,
            $timeafter,
            $data->user4->id
        );
    }

    /**
     * Check that when a user has no assignment already, their first assignment is created.
     */
    public function test_update_user_assignments_add_primary_reason() {
        $data = $this->setup_all_data();

        // User3 does not currently have any org matching assignment1.
        \totara_job\job_assignment::create_default($data->user3->id, ['organisationid' => $data->org3->id]);

        $this->setUser($data->user5);
        $timebefore = time();
        $data->goal->update_user_assignments($data->goalid, GOAL_ASSIGNMENT_ORGANISATION, $data->assignment1);
        $timeafter = time();

        $assignmenttype = GOAL_ASSIGNMENT_ORGANISATION;
        $expected_new_items = [
            $data->user3->id => [
                "ITEM:{$assignmenttype},{$data->assignment1->id},{$data->org3->id}" => true,
            ],
        ];

        $this->check_expected(
            $data->existing_user_assignments,
            $expected_new_items,
            GOAL_ASSIGNMENT_ORGANISATION,
            $data->assignment1->id,
            $data->goalid,
            $timebefore,
            $timeafter,
            $data->user5->id
        );
    }

    /**
     * Check that when a user has an assignment already, their second assignment is created.
     */
    public function test_update_user_assignments_add_secondary_reason() {
        $data = $this->setup_all_data();

        // User1 currently has an org matching assignment1, but not for this reason.
        \totara_job\job_assignment::create_default($data->user1->id, ['organisationid' => $data->org5->id]);

        $this->setUser($data->user5);
        $timebefore = time();
        $data->goal->update_user_assignments($data->goalid, GOAL_ASSIGNMENT_ORGANISATION, $data->assignment1);
        $timeafter = time();

        $assignmenttype = GOAL_ASSIGNMENT_ORGANISATION;
        $expected_new_items = [
            $data->user1->id => [
                "ITEM:{$assignmenttype},{$data->assignment1->id},{$data->org5->id}" => true,
            ],
        ];

        $this->check_expected(
            $data->existing_user_assignments,
            $expected_new_items,
            GOAL_ASSIGNMENT_ORGANISATION,
            $data->assignment1->id,
            $data->goalid,
            $timebefore,
            $timeafter,
            $data->user5->id
        );
    }

    /**
     * Check that when a user has as assignment already, a duplicate assignment is NOT created.
     */
    public function test_update_user_assignments_add_duplicate_reason() {
        $data = $this->setup_all_data();

        // Add a duplicate org from the user.
        \totara_job\job_assignment::create_default($data->user1->id, ['organisationid' => $data->org3->id]);

        $this->setUser($data->user2);
        $data->goal->update_user_assignments($data->goalid, GOAL_ASSIGNMENT_ORGANISATION, $data->assignment1);

        $this->check_expected(
            $data->existing_user_assignments,
            [],
            0,
            0,
            0,
            0,
            0,
            0
        );
    }

    /**
     * Check that removing a duplicate reason does NOT result in the reason being removed.
     */
    public function test_update_user_assignments_remove_duplicate_reason() {
        $data = $this->setup_all_data();

        // Remove a non-duplicate org from the user.
        /** @var \totara_job\job_assignment $ja */
        $ja = $data->user1ja3;
        $ja->update(['organisationid' => null]);

        $this->setUser($data->user3);
        $data->goal->update_user_assignments($data->goalid, GOAL_ASSIGNMENT_ORGANISATION, $data->assignment1);

        $this->check_expected(
            $data->existing_user_assignments,
            [],
            0,
            0,
            0,
            0,
            0,
            0
        );
    }

    /**
     * Check that removing one out of several different reasons results in only the specific reason being removed.
     */
    public function test_update_user_assignments_remove_secondary_reason() {
        $data = $this->setup_all_data();

        // Remove a non-duplicate org from the user.
        /** @var \totara_job\job_assignment $ja */
        $ja = $data->user1ja4;
        $ja->update(['organisationid' => null]);

        $this->setUser($data->user4);
        $timebefore = time();
        $data->goal->update_user_assignments($data->goalid, GOAL_ASSIGNMENT_ORGANISATION, $data->assignment1);
        $timeafter = time();

        $assignmenttype = GOAL_ASSIGNMENT_ORGANISATION;
        foreach ($data->existing_user_assignments as $key => $user_assignment) {
            if ($user_assignment->userid == $data->user1->id &&
                $user_assignment->extrainfo == "ITEM:{$assignmenttype},{$data->assignment1->id},{$data->org3->id}") {
                unset($data->existing_user_assignments[$key]);
            }
        }

        $expected_new_items = [
            $data->user1->id => [
                "OLD:{$assignmenttype},{$data->assignment1->id},{$data->org3->id}" => true,
            ],
        ];

        $this->check_expected(
            $data->existing_user_assignments,
            $expected_new_items,
            GOAL_ASSIGNMENT_INDIVIDUAL,
            0,
            $data->goalid,
            $timebefore,
            $timeafter,
            $data->user4->id
        );
    }

    public function test_update_user_assignments_remove_primary_reason() {
        $data = $this->setup_all_data();

        // Remove the last org from the user.
        /** @var \totara_job\job_assignment $ja */
        $ja = $data->user2ja1;
        $ja->update(['organisationid' => null]);

        $this->setUser($data->user4);
        $timebefore = time();
        $data->goal->update_user_assignments($data->goalid, GOAL_ASSIGNMENT_ORGANISATION, $data->assignment1);
        $timeafter = time();

        $assignmenttype = GOAL_ASSIGNMENT_ORGANISATION;
        foreach ($data->existing_user_assignments as $key => $user_assignment) {
            if ($user_assignment->userid == $data->user2->id &&
                $user_assignment->extrainfo == "ITEM:{$assignmenttype},{$data->assignment1->id},{$data->org3->id}") {
                unset($data->existing_user_assignments[$key]);
            }
        }

        $expected_new_items = [
            $data->user2->id => [
                "OLD:{$assignmenttype},{$data->assignment1->id},{$data->org3->id}" => true,
            ],
        ];

        $this->check_expected(
            $data->existing_user_assignments,
            $expected_new_items,
            GOAL_ASSIGNMENT_INDIVIDUAL,
            0,
            $data->goalid,
            $timebefore,
            $timeafter,
            $data->user4->id
        );
    }

    /**
     * Check that a record marked OLD will be turned into an ITEM record when the user meets the criteria again.
     */
    public function test_update_user_assignments_restore_old_to_item() {
        global $USER;

        $data = $this->setup_all_data();

        // Remove the last org from the user.
        /** @var \totara_job\job_assignment $ja */
        $ja = $data->user2ja1;
        $ja->update(['organisationid' => null]);

        $this->setUser($data->user4);
        $timebefore = time();
        $data->goal->update_user_assignments($data->goalid, GOAL_ASSIGNMENT_ORGANISATION, $data->assignment1);
        $timeafter = time();

        $assignmenttype = GOAL_ASSIGNMENT_ORGANISATION;
        foreach ($data->existing_user_assignments as $key => $user_assignment) {
            if ($user_assignment->userid == $data->user2->id &&
                $user_assignment->extrainfo == "ITEM:{$assignmenttype},{$data->assignment1->id},{$data->org3->id}") {
                unset($data->existing_user_assignments[$key]);
            }
        }

        $expected_new_items = [
            $data->user2->id => [
                "OLD:{$assignmenttype},{$data->assignment1->id},{$data->org3->id}" => true,
            ],
        ];

        $this->check_expected(
            $data->existing_user_assignments,
            $expected_new_items,
            GOAL_ASSIGNMENT_INDIVIDUAL,
            0,
            $data->goalid,
            $timebefore,
            $timeafter,
            $data->user4->id
        );

        // Add the org back (in a new JA).
        \totara_job\job_assignment::create_default($data->user2->id, ['organisationid' => $data->org3->id]);

        $this->setUser($data->user5);
        sleep(1); // Ensures that timemodified is updated for the second time.
        $timebefore = time();
        $data->goal->update_user_assignments($data->goalid, GOAL_ASSIGNMENT_ORGANISATION, $data->assignment1);
        $timeafter = time();

        $assignmenttype = GOAL_ASSIGNMENT_ORGANISATION;
        $expected_new_items = [
            $data->user2->id => [
                "ITEM:{$assignmenttype},{$data->assignment1->id},{$data->org3->id}" => true,
            ],
        ];

        $this->check_expected(
            $data->existing_user_assignments,
            $expected_new_items,
            GOAL_ASSIGNMENT_ORGANISATION,
            $data->assignment1->id,
            $data->goalid,
            $timebefore,
            $timeafter,
            $data->user5->id
        );
    }
}