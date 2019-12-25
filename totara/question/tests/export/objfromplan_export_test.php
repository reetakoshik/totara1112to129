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
 * vendor/bin/phpunit --verbose totara_question_objfromplan_export_testcase totara/question/tests/export/objfromplan_export_test.php
 */
class totara_question_objfromplan_export_testcase extends advanced_testcase {

    public function test_get_items_no_items() {
        $questiontype = 'objfromplan';

        /** @var \totara_question\local\review_export $exporter */
        $exporter = \totara_question\local\export_helper::create('appraisal', 'appraisalroleassignmentid', $questiontype);

        $result = $exporter->get_items(123, 345);

        $this->assertEquals([], $result);
    }

    public function test_get_items_with_items() {
        global $DB;

        $this->resetAfterTest();

        $questiontype = 'objfromplan';

        $admin = get_admin();
        $this->setUser($admin);

        /** @var totara_plan_generator $plangenerator */
        $plangenerator = $this->getDataGenerator()->get_plugin_generator('totara_plan');
        $plan = $plangenerator->create_learning_plan();
        $objective1 = $plangenerator->create_learning_plan_objective($plan->id, null);
        $objective2 = $plangenerator->create_learning_plan_objective($plan->id, null);
        $objective3 = $plangenerator->create_learning_plan_objective($plan->id, null);

        // Target.
        $rd1 = new stdClass();
        $rd1->appraisalquestfieldid = 123;
        $rd1->appraisalscalevalueid = 234;
        $rd1->appraisalroleassignmentid = 345;
        $rd1->itemid = $objective1->id;
        $rd1->scope = null;
        $rd1->content = 'abc';
        $rd1->id = $DB->insert_record('appraisal_review_data', $rd1);

        // Target other scale value id, same item (check for unique results).
        $rd2 = new stdClass();
        $rd2->appraisalquestfieldid = 123;
        $rd2->appraisalscalevalueid = 666;
        $rd2->appraisalroleassignmentid = 345;
        $rd2->itemid = $objective1->id;
        $rd2->scope = null;
        $rd2->content = 'def';
        $rd2->id = $DB->insert_record('appraisal_review_data', $rd2);

        // Target other item.
        $rd3 = new stdClass();
        $rd3->appraisalquestfieldid = 123;
        $rd3->appraisalscalevalueid = 0;
        $rd3->appraisalroleassignmentid = 345;
        $rd3->itemid = $objective2->id;
        $rd3->scope = null;
        $rd3->content = 'ghi';
        $rd3->id = $DB->insert_record('appraisal_review_data', $rd3);

        // Control other item/role.
        $rd4 = new stdClass();
        $rd4->appraisalquestfieldid = 123;
        $rd4->appraisalscalevalueid = 234;
        $rd4->appraisalroleassignmentid = 789;
        $rd4->itemid = $objective3->id;
        $rd4->scope = null;
        $rd4->content = 'jkl';
        $rd4->id = $DB->insert_record('appraisal_review_data', $rd4);

        /** @var \totara_question\local\review_export $exporter */
        $exporter = \totara_question\local\export_helper::create('appraisal', 'appraisalroleassignmentid', $questiontype);

        $result = $exporter->get_items(123, 345);

        $record1 = new stdClass();
        $record1->id = $objective1->id . '_0';
        $record1->name = $objective1->fullname;
        $record2 = new stdClass();
        $record2->id = $objective2->id . '_0';
        $record2->name = $objective2->fullname;
        $expected = [
            $objective1->id . '_0' => $record1,
            $objective2->id . '_0' => $record2,
        ];
        $this->assertEquals($expected, $result);
    }
}