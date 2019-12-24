<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Nathan Leiws <nathan.lewis@totaralearning.com>
 * @package totara_plan
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');

/**
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose totara_plan_component_program_testcase totara/plan/tests/component_program_test.php
 */
class totara_plan_component_program_testcase extends reportcache_advanced_testcase {

    public function test_dp_program_component_unassign_item() {
        global $DB;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        /* @var totara_plan_generator $plangenerator */
        $plangenerator = $generator->get_plugin_generator('totara_plan');

        // A user with permissions is required to do some of the operations, so just do it all as admin.
        $this->setAdminUser();

        // Create some users.
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();

        // Create some programs.
        $prog1 = $generator->create_program();
        $prog2 = $generator->create_program();

        // Add the programs to learning plans. User1 has two plans, both with program1, only first with program2.
        // User2 has two learning plans, each with one program. We'll be removing user1's program1 which should
        // remove the program2 completion but should leave the program1 completion.

        // User1, first learning plan, both programs.
        $planrecord = $plangenerator->create_learning_plan(array('userid' => $user1->id));
        $plan = new development_plan($planrecord->id);
        $plan->set_status(DP_PLAN_STATUS_APPROVED);
        // Reload to get change in status.
        $plan = new development_plan($planrecord->id);
        /* @var dp_program_component $component_program */
        $user1lp1componentprogram = $plan->get_component('program');
        $user1lp1program1 = $user1lp1componentprogram->assign_new_item($prog1->id, false);
        $user1lp1program2 = $user1lp1componentprogram->assign_new_item($prog2->id, false);

        // User1, second learning plan, just program1.
        $planrecord = $plangenerator->create_learning_plan(array('userid' => $user1->id));
        $plan = new development_plan($planrecord->id);
        $plan->set_status(DP_PLAN_STATUS_APPROVED);
        // Reload to get change in status.
        $plan = new development_plan($planrecord->id);
        /* @var dp_program_component $component_program */
        $componentprogram = $plan->get_component('program');
        $componentprogram->assign_new_item($prog1->id, false);

        // User2, first learning plan, just program1.
        $planrecord = $plangenerator->create_learning_plan(array('userid' => $user2->id));
        $plan = new development_plan($planrecord->id);
        $plan->set_status(DP_PLAN_STATUS_APPROVED);
        // Reload to get change in status.
        $plan = new development_plan($planrecord->id);
        /* @var dp_program_component $component_program */
        $componentprogram = $plan->get_component('program');
        $componentprogram->assign_new_item($prog1->id, false);

        // User2, first learning plan, just program2.
        $planrecord = $plangenerator->create_learning_plan(array('userid' => $user2->id));
        $plan = new development_plan($planrecord->id);
        $plan->set_status(DP_PLAN_STATUS_APPROVED);
        // Reload to get change in status.
        $plan = new development_plan($planrecord->id);
        /* @var dp_program_component $component_program */
        $componentprogram = $plan->get_component('program');
        $componentprogram->assign_new_item($prog2->id, false);

        // Load the current set of data.
        $expectedprogcompletions = $DB->get_records('prog_completion');

        // Remove both programs from user1's first learning plan.
        $user1lp1componentprogram->unassign_item($user1lp1program1);
        $user1lp1componentprogram->unassign_item($user1lp1program2);

        // Manually make the same change to the expected data.
        foreach ($expectedprogcompletions as $key => $progcompletion) {
            // Only user1's program2 completion is removed, because they still have program1 in their other lp.
            if ($progcompletion->programid == $prog2->id && $progcompletion->userid == $user1->id) {
                unset($expectedprogcompletions[$key]);
            }
        }

        // Then just compare the current data with the expected.
        $actualprogcompletions = $DB->get_records('prog_completion');
        $this->assertEquals($expectedprogcompletions, $actualprogcompletions);
    }
}
