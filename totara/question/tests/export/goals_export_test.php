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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara
 * @subpackage question
 */

/**
 * @group totara_userdata
 * @group totara_question
 *
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose totara_question_goals_export_testcase totara/question/tests/export/goals_export_test.php
 */
class totara_question_goals_export_testcase extends advanced_testcase {

    public function test_get_items_no_items() {
        $questiontype = 'goals';

        /** @var \totara_question\local\review_export $exporter */
        $exporter = \totara_question\local\export_helper::create('appraisal', 'appraisalroleassignmentid', $questiontype);

        $result = $exporter->get_items(123, 345);

        $this->assertEquals([], $result);
    }

    public function test_get_items_with_company_goals() {
        global $DB;

        $this->resetAfterTest();

        $questiontype = 'goals';

        /** @var totara_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $fwk = $hierarchygenerator->create_goal_frame([]);

        $companygoal1 = $hierarchygenerator->create_goal(['frameworkid' => $fwk->id]);
        $companygoal2 = $hierarchygenerator->create_goal(['frameworkid' => $fwk->id]);
        $companygoal3 = $hierarchygenerator->create_goal(['frameworkid' => $fwk->id]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        /** @var totara_hierarchy_generator $hierarchygen */
        $hierarchygen = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $hierarchygen->goal_assign_individuals($companygoal1->id, [$user1->id]);
        $hierarchygen->goal_assign_individuals($companygoal2->id, [$user1->id]);
        $hierarchygen->goal_assign_individuals($companygoal3->id, [$user2->id]);

        $goalassign1 = $DB->get_record('goal_record', ['goalid' => $companygoal1->id, 'userid' => $user1->id]);
        $goalassign2 = $DB->get_record('goal_record', ['goalid' => $companygoal2->id, 'userid' => $user1->id]);
        $goalassign3 = $DB->get_record('goal_record', ['goalid' => $companygoal3->id, 'userid' => $user2->id]);

        // Target.
        $rd1 = new stdClass();
        $rd1->appraisalquestfieldid = 123;
        $rd1->appraisalscalevalueid = 234;
        $rd1->appraisalroleassignmentid = 345;
        $rd1->itemid = $goalassign1->id;
        $rd1->scope = \goal::SCOPE_COMPANY;
        $rd1->content = 'abc';
        $rd1->id = $DB->insert_record('appraisal_review_data', $rd1);

        // Target other scale value id, same item (check for unique results).
        $rd2 = new stdClass();
        $rd2->appraisalquestfieldid = 123;
        $rd2->appraisalscalevalueid = 666;
        $rd2->appraisalroleassignmentid = 345;
        $rd2->itemid = $goalassign1->id;
        $rd2->scope = \goal::SCOPE_COMPANY;
        $rd2->content = 'def';
        $rd2->id = $DB->insert_record('appraisal_review_data', $rd2);

        // Target other item.
        $rd3 = new stdClass();
        $rd3->appraisalquestfieldid = 123;
        $rd3->appraisalscalevalueid = 0;
        $rd3->appraisalroleassignmentid = 345;
        $rd3->itemid = $goalassign2->id;
        $rd3->scope = \goal::SCOPE_COMPANY;
        $rd3->content = 'ghi';
        $rd3->id = $DB->insert_record('appraisal_review_data', $rd3);

        // Control other item/role.
        $rd4 = new stdClass();
        $rd4->appraisalquestfieldid = 123;
        $rd4->appraisalscalevalueid = 234;
        $rd4->appraisalroleassignmentid = 789;
        $rd4->itemid = $goalassign3->id;
        $rd4->scope = \goal::SCOPE_COMPANY;
        $rd4->content = 'jkl';
        $rd4->id = $DB->insert_record('appraisal_review_data', $rd4);

        /** @var \totara_question\local\review_export $exporter */
        $exporter = \totara_question\local\export_helper::create('appraisal', 'appraisalroleassignmentid', $questiontype);

        $result = $exporter->get_items(123, 345);

        $record1 = new stdClass();
        $record1->id = $goalassign1->id . '_' . \goal::SCOPE_COMPANY;
        $record1->name = $companygoal1->fullname;
        $record2 = new stdClass();
        $record2->id = $goalassign2->id . '_' . \goal::SCOPE_COMPANY;
        $record2->name = $companygoal2->fullname;
        $expected = [
            $goalassign1->id . '_' . \goal::SCOPE_COMPANY => $record1,
            $goalassign2->id . '_' . \goal::SCOPE_COMPANY => $record2,
        ];
        $this->assertEquals($expected, $result);
    }

    public function test_get_items_with_personal_goals() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/totara/hierarchy/prefix/goal/lib.php');

        $this->resetAfterTest();

        $questiontype = 'goals';

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        /** @var totara_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $personalgoal1 = $hierarchygenerator->create_personal_goal($user1->id);
        $personalgoal2 = $hierarchygenerator->create_personal_goal($user1->id);
        $personalgoal3 = $hierarchygenerator->create_personal_goal($user2->id);

        // Target.
        $rd1 = new stdClass();
        $rd1->appraisalquestfieldid = 123;
        $rd1->appraisalscalevalueid = 234;
        $rd1->appraisalroleassignmentid = 345;
        $rd1->itemid = $personalgoal1->id;
        $rd1->scope = \goal::SCOPE_PERSONAL;
        $rd1->content = 'abc';
        $rd1->id = $DB->insert_record('appraisal_review_data', $rd1);

        // Target other scale value id, same item (check for unique results).
        $rd2 = new stdClass();
        $rd2->appraisalquestfieldid = 123;
        $rd2->appraisalscalevalueid = 666;
        $rd2->appraisalroleassignmentid = 345;
        $rd2->itemid = $personalgoal1->id;
        $rd2->scope = \goal::SCOPE_PERSONAL;
        $rd2->content = 'def';
        $rd2->id = $DB->insert_record('appraisal_review_data', $rd2);

        // Target other item.
        $rd3 = new stdClass();
        $rd3->appraisalquestfieldid = 123;
        $rd3->appraisalscalevalueid = 0;
        $rd3->appraisalroleassignmentid = 345;
        $rd3->itemid = $personalgoal2->id;
        $rd3->scope = \goal::SCOPE_PERSONAL;
        $rd3->content = 'ghi';
        $rd3->id = $DB->insert_record('appraisal_review_data', $rd3);

        // Control other item/role.
        $rd4 = new stdClass();
        $rd4->appraisalquestfieldid = 123;
        $rd4->appraisalscalevalueid = 234;
        $rd4->appraisalroleassignmentid = 789;
        $rd4->itemid = $personalgoal3->id;
        $rd4->scope = \goal::SCOPE_PERSONAL;
        $rd4->content = 'jkl';
        $rd4->id = $DB->insert_record('appraisal_review_data', $rd4);

        /** @var \totara_question\local\review_export $exporter */
        $exporter = \totara_question\local\export_helper::create('appraisal', 'appraisalroleassignmentid', $questiontype);

        $result = $exporter->get_items(123, 345);

        $record1 = new stdClass();
        $record1->id = $personalgoal1->id . '_' . \goal::SCOPE_PERSONAL;
        $record1->name = $personalgoal1->name;
        $record2 = new stdClass();
        $record2->id = $personalgoal2->id . '_' . \goal::SCOPE_PERSONAL;
        $record2->name = $personalgoal2->name;
        $expected = [
            $personalgoal1->id . '_' . \goal::SCOPE_PERSONAL => $record1,
            $personalgoal2->id . '_' . \goal::SCOPE_PERSONAL => $record2,
        ];
        $this->assertEquals($expected, $result);
    }

    public function test_get_items_with_mix() {
        global $DB;

        $this->resetAfterTest();

        $questiontype = 'goals';

        $user1 = $this->getDataGenerator()->create_user();

        /** @var totara_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $fwk = $hierarchygenerator->create_goal_frame([]);

        $companygoal1 = $hierarchygenerator->create_goal(['frameworkid' => $fwk->id]);
        $personalgoal2 = $hierarchygenerator->create_personal_goal($user1->id);

        $user1 = $this->getDataGenerator()->create_user();

        /** @var totara_hierarchy_generator $hierarchygen */
        $hierarchygen = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $hierarchygen->goal_assign_individuals($companygoal1->id, [$user1->id]);

        $companygoalassign1 = $DB->get_record('goal_record', ['goalid' => $companygoal1->id, 'userid' => $user1->id]);

        // Target.
        $rd1 = new stdClass();
        $rd1->appraisalquestfieldid = 123;
        $rd1->appraisalscalevalueid = 234;
        $rd1->appraisalroleassignmentid = 345;
        $rd1->itemid = $companygoalassign1->id;
        $rd1->scope = \goal::SCOPE_COMPANY;
        $rd1->content = 'abc';
        $rd1->id = $DB->insert_record('appraisal_review_data', $rd1);

        // Target other scale value id, same item (check for unique results).
        $rd2 = new stdClass();
        $rd2->appraisalquestfieldid = 123;
        $rd2->appraisalscalevalueid = 666;
        $rd2->appraisalroleassignmentid = 345;
        $rd2->itemid = $personalgoal2->id;
        $rd2->scope = \goal::SCOPE_PERSONAL;
        $rd2->content = 'def';
        $rd2->id = $DB->insert_record('appraisal_review_data', $rd2);

        /** @var \totara_question\local\review_export $exporter */
        $exporter = \totara_question\local\export_helper::create('appraisal', 'appraisalroleassignmentid', $questiontype);

        $result = $exporter->get_items(123, 345);

        $record1 = new stdClass();
        $record1->id = $companygoalassign1->id . '_' . \goal::SCOPE_COMPANY;
        $record1->name = $companygoal1->fullname;
        $record2 = new stdClass();
        $record2->id = $personalgoal2->id . '_' . \goal::SCOPE_PERSONAL;
        $record2->name = $personalgoal2->name;
        $expected = [
            $companygoalassign1->id . '_' . \goal::SCOPE_COMPANY => $record1,
            $personalgoal2->id . '_' . \goal::SCOPE_PERSONAL => $record2,
        ];
        $this->assertEquals($expected, $result);
    }
}