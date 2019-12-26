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
 * @package hierarchy_goal
 */

use totara_job\job_assignment;

defined('MOODLE_INTERNAL') || die();


class hierarchy_goal_permission_testcase extends advanced_testcase {

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        global $CFG;
        require_once($CFG->dirroot . '/totara/hierarchy/lib.php');
        require_once($CFG->dirroot . '/totara/hierarchy/prefix/goal/lib.php');
    }

    public function test_permissions_to_view_own_goals() {
        $data = $this->create_data();

        $this->setUser($data->user1);

        $goal = new goal();
        $permissions = $goal->get_permissions(null, $data->user1->id);

        // A user can always view his own goals
        $this->assertTrue($permissions['can_view_personal']);
        $this->assertTrue($permissions['can_edit_personal']);
        $this->assertTrue($permissions['can_view_company']);
        $this->assertTrue($permissions['can_edit_company']);
        $can_edit = $permissions['can_edit'];
        $this->assertTrue($can_edit[GOAL_ASSIGNMENT_INDIVIDUAL]);
        $this->assertTrue($can_edit[GOAL_ASSIGNMENT_SELF]);
        $this->assertFalse($can_edit[GOAL_ASSIGNMENT_MANAGER]);
        $this->assertFalse($can_edit[GOAL_ASSIGNMENT_ADMIN]);

        $this->setUser($data->user2);

        $goal = new goal();
        $permissions = $goal->get_permissions(null, $data->user2->id);

        // A user can always view his own goals
        $this->assertTrue($permissions['can_view_personal']);
        $this->assertTrue($permissions['can_edit_personal']);
        $this->assertTrue($permissions['can_view_company']);
        $this->assertTrue($permissions['can_edit_company']);
        $can_edit = $permissions['can_edit'];
        $this->assertTrue($can_edit[GOAL_ASSIGNMENT_INDIVIDUAL]);
        $this->assertTrue($can_edit[GOAL_ASSIGNMENT_SELF]);
        $this->assertFalse($can_edit[GOAL_ASSIGNMENT_MANAGER]);
        $this->assertFalse($can_edit[GOAL_ASSIGNMENT_ADMIN]);

        $this->setUser($data->user3);

        $goal = new goal();
        $permissions = $goal->get_permissions(null, $data->user3->id);

        // A user can always view his own goals
        $this->assertTrue($permissions['can_view_personal']);
        $this->assertTrue($permissions['can_edit_personal']);
        $this->assertTrue($permissions['can_view_company']);
        $this->assertTrue($permissions['can_edit_company']);
        $can_edit = $permissions['can_edit'];
        $this->assertTrue($can_edit[GOAL_ASSIGNMENT_INDIVIDUAL]);
        $this->assertTrue($can_edit[GOAL_ASSIGNMENT_SELF]);
        $this->assertFalse($can_edit[GOAL_ASSIGNMENT_MANAGER]);
        $this->assertFalse($can_edit[GOAL_ASSIGNMENT_ADMIN]);
    }

    public function test_permissions_to_view_team_members_goals() {
        $data = $this->create_data();

        $this->setUser($data->user1);

        $goal = new goal();
        $permissions = $goal->get_permissions(null, $data->user2->id);

        // A manager can view his team members goals
        $this->assertTrue($permissions['can_view_personal']);
        $this->assertTrue($permissions['can_edit_personal']);
        $this->assertTrue($permissions['can_view_company']);
        $this->assertTrue($permissions['can_edit_company']);
        $can_edit = $permissions['can_edit'];
        $this->assertTrue($can_edit[GOAL_ASSIGNMENT_INDIVIDUAL]);
        $this->assertTrue($can_edit[GOAL_ASSIGNMENT_SELF]);
        $this->assertTrue($can_edit[GOAL_ASSIGNMENT_MANAGER]);
        $this->assertFalse($can_edit[GOAL_ASSIGNMENT_ADMIN]);

        $this->setUser($data->user2);

        $goal = new goal();
        $permissions = $goal->get_permissions(null, $data->user1->id);

        // A manager can view his team members goals
        $this->assertTrue($permissions['can_view_personal']);
        $this->assertTrue($permissions['can_edit_personal']);
        $this->assertTrue($permissions['can_view_company']);
        $this->assertTrue($permissions['can_edit_company']);
        $can_edit = $permissions['can_edit'];
        $this->assertTrue($can_edit[GOAL_ASSIGNMENT_INDIVIDUAL]);
        $this->assertTrue($can_edit[GOAL_ASSIGNMENT_SELF]);
        $this->assertTrue($can_edit[GOAL_ASSIGNMENT_MANAGER]);
        $this->assertFalse($can_edit[GOAL_ASSIGNMENT_ADMIN]);

        $this->setUser($data->user3);

        $goal = new goal();
        $permissions = $goal->get_permissions(null, $data->user1->id);

        // A manager can view his team members goals
        $this->assertTrue($permissions['can_view_personal']);
        $this->assertTrue($permissions['can_edit_personal']);
        $this->assertTrue($permissions['can_view_company']);
        $this->assertTrue($permissions['can_edit_company']);
        $can_edit = $permissions['can_edit'];
        $this->assertTrue($can_edit[GOAL_ASSIGNMENT_INDIVIDUAL]);
        $this->assertTrue($can_edit[GOAL_ASSIGNMENT_SELF]);
        $this->assertTrue($can_edit[GOAL_ASSIGNMENT_MANAGER]);
        $this->assertFalse($can_edit[GOAL_ASSIGNMENT_ADMIN]);
    }

    public function test_permissions_to_view_non_team_members_goals() {
        $data = $this->create_data();

        $this->setUser($data->user1);

        $goal = new goal();
        $permissions = $goal->get_permissions(null, $data->user3->id);
        $this->assertFalse($permissions);

        $this->setUser($data->user2);

        $goal = new goal();
        $permissions = $goal->get_permissions(null, $data->user3->id);
        $this->assertFalse($permissions);

        $this->setUser($data->user3);

        $goal = new goal();
        $permissions = $goal->get_permissions(null, $data->user2->id);

        // By default a manager's manager can't view his managers team members goals
        $this->assertFalse($permissions['can_view_personal']);
        $this->assertFalse($permissions['can_edit_personal']);
        $this->assertFalse($permissions['can_view_company']);
        $this->assertFalse($permissions['can_edit_company']);
        $can_edit = $permissions['can_edit'];
        $this->assertFalse($can_edit[GOAL_ASSIGNMENT_INDIVIDUAL]);
        $this->assertFalse($can_edit[GOAL_ASSIGNMENT_SELF]);
        $this->assertFalse($can_edit[GOAL_ASSIGNMENT_MANAGER]);
        $this->assertFalse($can_edit[GOAL_ASSIGNMENT_ADMIN]);
    }

    public function test_permissions_to_view_goals_with_special_capability() {
        global $DB;

        $data = $this->create_data();

        $syscontext = context_system::instance();

        $rolestaffmanager = $DB->get_record('role', ['shortname' => 'staffmanager']);
        role_assign($rolestaffmanager->id, $data->user1->id, $syscontext->id);
        assign_capability('totara/hierarchy:managegoalassignments', CAP_ALLOW, $rolestaffmanager->id, $syscontext->id);

        $this->setUser($data->user1);

        $goal = new goal();
        $permissions = $goal->get_permissions(null, $data->user3->id);

        $this->assertTrue($permissions['can_view_personal']);
        $this->assertTrue($permissions['can_edit_personal']);
        $this->assertTrue($permissions['can_view_company']);
        $this->assertTrue($permissions['can_edit_company']);
        $can_edit = $permissions['can_edit'];
        $this->assertTrue($can_edit[GOAL_ASSIGNMENT_INDIVIDUAL]);
        $this->assertTrue($can_edit[GOAL_ASSIGNMENT_SELF]);
        $this->assertTrue($can_edit[GOAL_ASSIGNMENT_MANAGER]);
        $this->assertTrue($can_edit[GOAL_ASSIGNMENT_ADMIN]);
    }


    protected function create_data() {
        $data = new class() {
            public $user1, $user2, $user3;
        };

        $generator = $this->getDataGenerator();
        $data->user1 = $generator->create_user();
        $data->user2 = $generator->create_user();
        $data->user3 = $generator->create_user();

        // Job 1
        // user3 manages user1 manages user2
        // Job 2
        // user2 manages user 1
        $user3ja = job_assignment::create_default($data->user3->id);
        $user1ja1 = job_assignment::create_default($data->user1->id, ['idnumber' => 'job1', 'managerjaid' => $user3ja->id]);
        $user2ja = job_assignment::create_default($data->user2->id);
        // make user2 manager for user 1 in job2
        job_assignment::create_default($data->user1->id, ['idnumber' => 'job2', 'managerjaid' => $user2ja->id]);
        // make user1 the manager for user2 in existing assignment
        $user2ja->update(['managerjaid' => $user1ja1->id]);

        return $data;
    }

}
