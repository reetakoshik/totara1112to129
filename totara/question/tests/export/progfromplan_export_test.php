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
 * vendor/bin/phpunit --verbose totara_question_progfromplan_export_testcase totara/question/tests/export/progfromplan_export_test.php
 */
class totara_question_progfromplan_export_testcase extends advanced_testcase {

    public function test_get_items_no_items() {
        $questiontype = 'progfromplan';

        /** @var \totara_question\local\review_export $exporter */
        $exporter = \totara_question\local\export_helper::create('appraisal', 'appraisalroleassignmentid', $questiontype);

        $result = $exporter->get_items(123, 345);

        $this->assertEquals([], $result);
    }

    public function test_get_items_with_items() {
        global $DB;

        $this->resetAfterTest();

        $questiontype = 'progfromplan';

        /** @var totara_program_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $prog1 = $programgenerator->create_program();
        $prog2 = $programgenerator->create_program();
        $prog3 = $programgenerator->create_program();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Stupid access control.
        $this->setAdminUser();

        // Competency plan assignments.
        /** @var totara_plan_generator $plangenerator */
        $plangenerator = $this->getDataGenerator()->get_plugin_generator('totara_plan');
        $planrecord1 = $plangenerator->create_learning_plan(array('userid' => $user1->id));
        $planrecord2 = $plangenerator->create_learning_plan(array('userid' => $user1->id));
        $planrecord3 = $plangenerator->create_learning_plan(array('userid' => $user2->id));
        $plan1 = new development_plan($planrecord1->id);
        $plan2 = new development_plan($planrecord2->id);
        $plan3 = new development_plan($planrecord3->id);
        $plangenerator->add_learning_plan_program($plan1->id, $prog1->id);
        $plangenerator->add_learning_plan_program($plan2->id, $prog2->id);
        $plangenerator->add_learning_plan_program($plan3->id, $prog3->id);
        $sql = "SELECT pca.*
                  FROM {dp_plan_program_assign} pca
                 WHERE pca.programid = :programid";
        $planprogram1 = $DB->get_record_sql($sql, ['planid' => $plan1->id, 'programid' => $prog1->id]);
        $planprogram2 = $DB->get_record_sql($sql, ['planid' => $plan2->id, 'programid' => $prog2->id]);
        $planprogram3 = $DB->get_record_sql($sql, ['planid' => $plan3->id, 'programid' => $prog3->id]);

        // Target.
        $rd1 = new stdClass();
        $rd1->appraisalquestfieldid = 123;
        $rd1->appraisalscalevalueid = 234;
        $rd1->appraisalroleassignmentid = 345;
        $rd1->itemid = $planprogram1->id;
        $rd1->scope = null;
        $rd1->content = 'abc';
        $rd1->id = $DB->insert_record('appraisal_review_data', $rd1);

        // Target other scale value id, same item (check for unique results).
        $rd2 = new stdClass();
        $rd2->appraisalquestfieldid = 123;
        $rd2->appraisalscalevalueid = 666;
        $rd2->appraisalroleassignmentid = 345;
        $rd2->itemid = $planprogram1->id;
        $rd2->scope = null;
        $rd2->content = 'def';
        $rd2->id = $DB->insert_record('appraisal_review_data', $rd2);

        // Target other item.
        $rd3 = new stdClass();
        $rd3->appraisalquestfieldid = 123;
        $rd3->appraisalscalevalueid = 0;
        $rd3->appraisalroleassignmentid = 345;
        $rd3->itemid = $planprogram2->id;
        $rd3->scope = null;
        $rd3->content = 'ghi';
        $rd3->id = $DB->insert_record('appraisal_review_data', $rd3);

        // Control other item/role.
        $rd4 = new stdClass();
        $rd4->appraisalquestfieldid = 123;
        $rd4->appraisalscalevalueid = 234;
        $rd4->appraisalroleassignmentid = 789;
        $rd4->itemid = $planprogram3->id;
        $rd4->scope = null;
        $rd4->content = 'jkl';
        $rd4->id = $DB->insert_record('appraisal_review_data', $rd4);

        /** @var \totara_question\local\review_export $exporter */
        $exporter = \totara_question\local\export_helper::create('appraisal', 'appraisalroleassignmentid', $questiontype);

        $result = $exporter->get_items(123, 345);

        $record1 = new stdClass();
        $record1->id = $planprogram1->id . '_0';
        $record1->name = $prog1->fullname;
        $record2 = new stdClass();
        $record2->id = $planprogram2->id . '_0';
        $record2->name = $prog2->fullname;
        $expected = [
            $planprogram1->id . '_0' => $record1,
            $planprogram2->id . '_0' => $record2,
        ];
        $this->assertEquals($expected, $result);
    }
}