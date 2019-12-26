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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_program
 */

class totara_program_assignment_cohorts_test extends advanced_testcase {


    public function test_get_user_count() {
        global $DB;
        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();
        $programgenerator = $generator->get_plugin_generator('totara_program');


        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $user4 = $generator->create_user();

        $cohortgenerator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');
        $audience1 = $this->getDataGenerator()->create_cohort(['name' => 'Audience 1']);
        $cohortgenerator->cohort_assign_users($audience1->id, [$user1->id, $user2->id, $user3->id]);

        $audience2 = $this->getDataGenerator()->create_cohort(['name' => 'Audience 2']);
        $cohortgenerator->cohort_assign_users($audience2->id, [$user1->id, $user4->id]);

        $program1 = $programgenerator->create_program();

        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_COHORT, $audience1->id);
        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_COHORT, $audience2->id);

        $assign1id = $DB->get_field('prog_assignment', 'id', ['programid' => $program1->id, 'assignmenttype' => 3, 'assignmenttypeid' => $audience1->id]);
        $assignment1 = \totara_program\assignment\cohort::create_from_id($assign1id);
        $assign2id = $DB->get_field('prog_assignment', 'id', ['programid' => $program1->id, 'assignmenttype' => 3, 'assignmenttypeid' => $audience2->id]);
        $assignment2 = \totara_program\assignment\cohort::create_from_id($assign2id);

        $this->assertEquals(3, $assignment1->get_user_count());
        $this->assertEquals(2, $assignment2->get_user_count());
    }

    public function test_get_name() {
        global $DB;
        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();
        $programgenerator = $generator->get_plugin_generator('totara_program');

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $user4 = $generator->create_user();

        $cohortgenerator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');
        $audience1 = $this->getDataGenerator()->create_cohort(['name' => 'Audience 1']);
        $cohortgenerator->cohort_assign_users($audience1->id, [$user1->id, $user2->id, $user3->id]);

        $audience2 = $this->getDataGenerator()->create_cohort(['name' => 'Audience 2']);
        $cohortgenerator->cohort_assign_users($audience2->id, [$user1->id, $user4->id]);

        $program1 = $programgenerator->create_program();

        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_COHORT, $audience1->id);

        $assign1id = $DB->get_field('prog_assignment', 'id', ['programid' => $program1->id, 'assignmenttype' => 3, 'assignmenttypeid' => $audience1->id]);
        $assignment1 = \totara_program\assignment\cohort::create_from_id($assign1id);

        // Does the name match?
        $this->assertEquals($audience1->name, $assignment1->get_name());
    }

    /**
     * Test to see if user user_assignment records are created
     * correctly for new assignments
     */
    public function test_create_from_instance_id() {
        global $DB, $CFG;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $programgenerator = $generator->get_plugin_generator('totara_program');

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $user4 = $generator->create_user();

        $cohortgenerator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');
        $audience1 = $this->getDataGenerator()->create_cohort(['name' => 'Audience 1']);
        $cohortgenerator->cohort_assign_users($audience1->id, [$user1->id, $user2->id, $user3->id]);

        $program1 = $programgenerator->create_program();

        $cohorttypeid = 3;
        $assignment = \totara_program\assignment\base::create_from_instance_id($program1->id, $cohorttypeid, $audience1->id);
        $assignment->save();

        $this->assertInstanceOf('\totara_program\assignment\cohort', $assignment);

        $reflection = new ReflectionClass('\totara_program\assignment\cohort');
        $property = $reflection->getProperty('typeid');
        $property->setAccessible(true);
        $this->assertEquals(3, $property->getValue($assignment));

        $property = $reflection->getProperty('instanceid');
        $property->setAccessible(true);
        $this->assertEquals($audience1->id, $property->getValue($assignment));

        // Check all the correct records were created.
        $this->assertEquals(1, $DB->count_records('prog_assignment', ['programid' => $program1->id]));
        //$this->assertEquals(3, $DB->count_records('prog_user_assignment', ['programid' => $program1->id]));
        //$this->assertEquals(3, $DB->count_records('prog_completion', ['programid' => $program1->id]));
    }

    public function test_get_type() {
        global $DB;
        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();
        $programgenerator = $generator->get_plugin_generator('totara_program');

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();

        $cohortgenerator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');
        $audience1 = $this->getDataGenerator()->create_cohort(['name' => 'Audience 1']);
        $cohortgenerator->cohort_assign_users($audience1->id, [$user1->id, $user2->id, $user3->id]);

        $program1 = $programgenerator->create_program();

        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_COHORT, $audience1->id);

        $assignments = $DB->get_records('prog_assignment', ['programid' => $program1->id]);
        $record = reset($assignments);
        $assignment = \totara_program\assignment\cohort::create_from_id($record->id);
        $this->assertEquals(3, $assignment->get_type());
    }


}

