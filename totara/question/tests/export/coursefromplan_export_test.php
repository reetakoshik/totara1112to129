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
 * vendor/bin/phpunit --verbose totara_question_coursefromplan_export_testcase totara/question/tests/export/coursefromplan_export_test.php
 */
class totara_question_coursefromplan_export_testcase extends advanced_testcase {

    public function test_get_items_no_items() {
        $questiontype = 'coursefromplan';

        /** @var \totara_question\local\review_export $exporter */
        $exporter = \totara_question\local\export_helper::create('appraisal', 'appraisalroleassignmentid', $questiontype);

        $result = $exporter->get_items(123, 345);

        $this->assertEquals([], $result);
    }

    public function test_get_items_with_items() {
        global $DB;

        $this->resetAfterTest();

        $questiontype = 'coursefromplan';

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

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
        $plangenerator->add_learning_plan_course($plan1->id, $course1->id);
        $plangenerator->add_learning_plan_course($plan2->id, $course2->id);
        $plangenerator->add_learning_plan_course($plan3->id, $course3->id);
        $sql = "SELECT pca.*
                  FROM {dp_plan_course_assign} pca
                 WHERE pca.courseid = :courseid";
        $plancourse1 = $DB->get_record_sql($sql, ['planid' => $plan1->id, 'courseid' => $course1->id]);
        $plancourse2 = $DB->get_record_sql($sql, ['planid' => $plan2->id, 'courseid' => $course2->id]);
        $plancourse3 = $DB->get_record_sql($sql, ['planid' => $plan3->id, 'courseid' => $course3->id]);

        // Target.
        $rd1 = new stdClass();
        $rd1->appraisalquestfieldid = 123;
        $rd1->appraisalscalevalueid = 234;
        $rd1->appraisalroleassignmentid = 345;
        $rd1->itemid = $plancourse1->id;
        $rd1->scope = null;
        $rd1->content = 'abc';
        $rd1->id = $DB->insert_record('appraisal_review_data', $rd1);

        // Target other scale value id, same item (check for unique results).
        $rd2 = new stdClass();
        $rd2->appraisalquestfieldid = 123;
        $rd2->appraisalscalevalueid = 666;
        $rd2->appraisalroleassignmentid = 345;
        $rd2->itemid = $plancourse1->id;
        $rd2->scope = null;
        $rd2->content = 'def';
        $rd2->id = $DB->insert_record('appraisal_review_data', $rd2);

        // Target other item.
        $rd3 = new stdClass();
        $rd3->appraisalquestfieldid = 123;
        $rd3->appraisalscalevalueid = 0;
        $rd3->appraisalroleassignmentid = 345;
        $rd3->itemid = $plancourse2->id;
        $rd3->scope = null;
        $rd3->content = 'ghi';
        $rd3->id = $DB->insert_record('appraisal_review_data', $rd3);

        // Control other item/role.
        $rd4 = new stdClass();
        $rd4->appraisalquestfieldid = 123;
        $rd4->appraisalscalevalueid = 234;
        $rd4->appraisalroleassignmentid = 789;
        $rd4->itemid = $plancourse3->id;
        $rd4->scope = null;
        $rd4->content = 'jkl';
        $rd4->id = $DB->insert_record('appraisal_review_data', $rd4);

        /** @var \totara_question\local\review_export $exporter */
        $exporter = \totara_question\local\export_helper::create('appraisal', 'appraisalroleassignmentid', $questiontype);

        $result = $exporter->get_items(123, 345);

        $record1 = new stdClass();
        $record1->id = $plancourse1->id . '_0';
        $record1->name = $course1->fullname;
        $record2 = new stdClass();
        $record2->id = $plancourse2->id . '_0';
        $record2->name = $course2->fullname;
        $expected = [
            $plancourse1->id . '_0' => $record1,
            $plancourse2->id . '_0' => $record2,
        ];
        $this->assertEquals($expected, $result);
    }
}