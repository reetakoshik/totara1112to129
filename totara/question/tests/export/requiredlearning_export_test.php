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
 * vendor/bin/phpunit --verbose totara_question_requiredlearning_export_testcase totara/question/tests/export/requiredlearning_export_test.php
 */
class totara_question_requiredlearning_export_testcase extends advanced_testcase {

    public function test_get_items_no_items() {
        $questiontype = 'requiredlearning';

        /** @var \totara_question\local\review_export $exporter */
        $exporter = \totara_question\local\export_helper::create('appraisal', 'appraisalroleassignmentid', $questiontype);

        $result = $exporter->get_items(123, 345);

        $this->assertEquals([], $result);
    }

    public function test_get_items_with_items() {
        global $DB;

        $this->resetAfterTest();

        $questiontype = 'requiredlearning';

        /** @var totara_program_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $prog1 = $programgenerator->create_program();
        $prog2id = $programgenerator->create_certification();
        $prog2 = $DB->get_record('prog', ['id' => $prog2id]);
        $prog3 = $programgenerator->create_program();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $programgenerator->assign_program($prog1->id, [$user1->id]);
        $programgenerator->assign_program($prog2->id, [$user1->id]);
        $programgenerator->assign_program($prog3->id, [$user2->id]);

        $progcompletion1 = prog_load_completion($prog1->id, $user1->id, true);
        list($certcompletion2, $progcompletion2) = certif_load_completion($prog2->id, $user1->id, true);
        $progcompletion3 = prog_load_completion($prog3->id, $user2->id, true);

        // Target.
        $rd1 = new stdClass();
        $rd1->appraisalquestfieldid = 123;
        $rd1->appraisalscalevalueid = 234;
        $rd1->appraisalroleassignmentid = 345;
        $rd1->itemid = $progcompletion1->id;
        $rd1->scope = null;
        $rd1->content = 'abc';
        $rd1->id = $DB->insert_record('appraisal_review_data', $rd1);

        // Target other scale value id, same item (check for unique results).
        $rd2 = new stdClass();
        $rd2->appraisalquestfieldid = 123;
        $rd2->appraisalscalevalueid = 666;
        $rd2->appraisalroleassignmentid = 345;
        $rd2->itemid = $progcompletion1->id;
        $rd2->scope = null;
        $rd2->content = 'def';
        $rd2->id = $DB->insert_record('appraisal_review_data', $rd2);

        // Target other item.
        $rd3 = new stdClass();
        $rd3->appraisalquestfieldid = 123;
        $rd3->appraisalscalevalueid = 0;
        $rd3->appraisalroleassignmentid = 345;
        $rd3->itemid = $progcompletion2->id;
        $rd3->scope = null;
        $rd3->content = 'ghi';
        $rd3->id = $DB->insert_record('appraisal_review_data', $rd3);

        // Control other item/role.
        $rd4 = new stdClass();
        $rd4->appraisalquestfieldid = 123;
        $rd4->appraisalscalevalueid = 234;
        $rd4->appraisalroleassignmentid = 789;
        $rd4->itemid = $progcompletion3->id;
        $rd4->scope = null;
        $rd4->content = 'jkl';
        $rd4->id = $DB->insert_record('appraisal_review_data', $rd4);

        /** @var \totara_question\local\review_export $exporter */
        $exporter = \totara_question\local\export_helper::create('appraisal', 'appraisalroleassignmentid', $questiontype);

        $result = $exporter->get_items(123, 345);

        $record1 = new stdClass();
        $record1->id = $progcompletion1->id . '_0';
        $record1->name = $prog1->fullname;
        $record2 = new stdClass();
        $record2->id = $progcompletion2->id . '_0';
        $record2->name = $prog2->fullname;
        $expected = [
            $progcompletion1->id . '_0' => $record1,
            $progcompletion2->id . '_0' => $record2,
        ];
        $this->assertEquals($expected, $result);
    }
}