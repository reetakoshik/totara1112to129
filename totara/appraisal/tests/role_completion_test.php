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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_appraisal
 */
global $CFG;

use totara_appraisal\current_stage_editor;
use totara_job\job_assignment;

require_once($CFG->dirroot.'/totara/appraisal/tests/appraisal_testcase.php');

/**
 * Class role_completion_test
 */
class role_completion_test extends appraisal_testcase {

    private function get_appraisal_def() {
        /**
         * Appraisal with two stages. Last stage is only for manager to answer.
         */
        $def = [
            'name' => 'Appraisal', 'stages' => [
                [
                    'name' => 'St1',
                    'timedue' => time() + 86400,
                    'locks' => [appraisal::ROLE_LEARNER => 1],
                    'pages' => [
                        [
                            'name' => 'St1Page1',
                            'questions' => [
                                [
                                    'name' => 'Text1',
                                    'type' => 'text',
                                    'roles' => [appraisal::ROLE_LEARNER => 3, appraisal::ROLE_MANAGER => 3]
                                ]
                            ]
                        ]
                    ]
                ],

                [
                    'name' => 'St2',
                    'timedue' => time() + 86400,
                    'locks' => [appraisal::ROLE_LEARNER => 1],
                    'pages' => [
                        [
                            'name' => 'St2Page1',
                            'questions' => [
                                [
                                    'name' => 'Text2',
                                    'type' => 'text',
                                    'roles' => [appraisal::ROLE_MANAGER => 3]
                                ]
                            ]
                        ]
                    ]
                ],
            ]
        ];

        return $def;
    }

    public function is_roles_complete_data_provider() {
        return [
            [true],
            [false],
        ];
    }

    /**
     * Test is_all_roles_complete() method in conjunction with stage unlocking and auto-completion of subsequent stage.
     *
     * In this test, all calls of is_all_roles_complete() are expected to return the same regardless of the second
     * parameter, that's why we run it twice with values true and false. The case where this parameter makes a
     * difference is tested separately below.
     *
     * @dataProvider is_roles_complete_data_provider
     */
    public function test_is_all_roles_complete(bool $param2) {
        $this->resetAfterTest();
        $this->setAdminUser();

        /** @var appraisal $appraisal */
        list($appraisal, $users) = $this->prepare_appraisal_with_users($this->get_appraisal_def());
        $appraisal->validate();
        $appraisal->activate();
        $this->update_job_assignments($appraisal);

        $appraisal_id = $appraisal->get()->id;
        $map = $this->map($appraisal);

        $user = current($users);
        $admin = get_admin();
        $learner_role_assignment = appraisal_role_assignment::get_role($appraisal_id, $user->id, $user->id, appraisal::ROLE_LEARNER);
        $admin_role_assignment = appraisal_role_assignment::get_role($appraisal_id, $user->id, $admin->id, appraisal::ROLE_MANAGER);

        // Complete stage1 for learner and manager
        $stage1 = new appraisal_stage($map['stages']['St1']);
        $this->assertFalse($stage1->is_all_roles_complete($user->id, $param2));
        $stage1->complete_for_role($learner_role_assignment);
        $this->assertFalse($stage1->is_all_roles_complete($user->id, $param2));
        $stage1->complete_for_role($admin_role_assignment);
        $this->assertTrue($stage1->is_all_roles_complete($user->id, $param2));

        // Complete stage2.
        $stage2 = new appraisal_stage($map['stages']['St2']);
        $this->assertFalse($stage2->is_all_roles_complete($user->id, $param2));
        $stage2->complete_for_role($admin_role_assignment);
        $this->assertTrue($stage2->is_all_roles_complete($user->id, $param2));

        // Make sure appraisal is completed for the user.
        $user_assignment = appraisal_user_assignment::get_user($appraisal_id, $user->id);
        $this->assertNotEmpty($user_assignment->timecompleted);

        // Replace manager and reset learner to stage1. Stage2 should still count as completed, even though it was
        // completed by the old manager.
        $manager2 = $this->getDataGenerator()->create_user();
        $manager2_ja = job_assignment::create_default($manager2->id);
        $user_ja = job_assignment::get_first($user->id);
        $user_ja->update(['managerjaid' => $manager2_ja->id]);
        $this->update_job_assignments($appraisal);

        // Reset learner to stage1.
        $this->assertTrue($stage1->is_all_roles_complete($user->id, $param2));
        current_stage_editor::set_stage_for_role_assignment(
            $appraisal_id,
            $user->id,
            $learner_role_assignment->id,
            $map['stages']['St1']
        );
        $user_assignment = appraisal_user_assignment::get_user($appraisal_id, $user->id);
        $this->assertEmpty($user_assignment->timecompleted);

        $this->assertFalse($stage1->is_all_roles_complete($user->id, $param2));
        $this->assertTrue($stage2->is_all_roles_complete($user->id, $param2));

        // Completing stage1 should lead to completion of the whole appraisal because stage2 is already completed
        // by the old manager.
        $stage1->complete_for_role($learner_role_assignment);
        $this->assertTrue($stage1->is_all_roles_complete($user->id, $param2));
        $this->assertTrue($stage2->is_all_roles_complete($user->id, $param2));

        // Make sure the whole appraisal is complete.
        $user_assignment = appraisal_user_assignment::get_user($appraisal_id, $user->id);
        $this->assertNotEmpty($user_assignment->timecompleted);

        // Unassign manager role.
        appraisal::unassign_user_roles($admin->id);

        // Reset learner to stage1.
        $this->assertTrue($stage1->is_all_roles_complete($user->id, $param2));
        current_stage_editor::set_stage_for_role_assignment(
            $appraisal_id,
            $user->id,
            $learner_role_assignment->id,
            $map['stages']['St1']
        );
        $user_assignment = appraisal_user_assignment::get_user($appraisal_id, $user->id);
        $this->assertEmpty($user_assignment->timecompleted);

        $this->assertFalse($stage1->is_all_roles_complete($user->id, $param2));
        $this->assertTrue($stage2->is_all_roles_complete($user->id, $param2));
    }

    public function test_is_all_roles_complete_check_unfilled_roles() {
        $this->resetAfterTest();
        $this->setAdminUser();

        /** @var appraisal $appraisal */
        list($appraisal, $users) = $this->prepare_appraisal_with_users($this->get_appraisal_def());
        $appraisal->validate();
        $appraisal->activate();
        $this->update_job_assignments($appraisal);
        $map = $this->map($appraisal);
        $user = current($users);
        $admin = get_admin();

        // Unassign manager role.
        $stage2 = new appraisal_stage($map['stages']['St2']);
        appraisal::unassign_user_roles($admin->id);

        // At this point no valid role is assigned to stage2 AND there is no completion by invalid role. That's when
        // the second parameter $check_unfilled_roles makes a difference.
        $this->assertFalse($stage2->is_all_roles_complete($user->id, true));
        $this->assertTrue($stage2->is_all_roles_complete($user->id, false));
    }
}