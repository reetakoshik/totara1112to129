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
 * vendor/bin/phpunit --verbose totara_question_review_export_testcase totara/question/tests/export/review_export_test.php
 *
 * review_export is an abstract class, so we'll use compfromplan_export so that we can instantiate and test it.
 */
class totara_question_review_export_testcase extends advanced_testcase {

    public function test_export_data_no_items() {
        $exporter = \totara_question\local\export_helper::create('appraisal', 'appraisalroleassignmentid', 'compfromplan');

        $data = new stdClass();
        $data->data_123 = null;
        $data->appraisalroleassignmentid = 456;

        $question = new stdClass();
        $question->id = 123;

        $expectedresult = get_string('nothingselected', 'totara_question');
        $result = $exporter->export_data($data, $question);

        $this->assertEquals($expectedresult, $result);
    }

    public function test_export_data_single_question() {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/totara/hierarchy/lib.php');

        $this->resetAfterTest();

        $comp1 = $this->create_competency(111);
        $comp2 = $this->create_competency(222);

        $user1 = $this->getDataGenerator()->create_user();

        // Stupid access control.
        $this->setAdminUser();

        // Competency plan assignments.
        /** @var totara_plan_generator $plangenerator */
        $plangenerator = $this->getDataGenerator()->get_plugin_generator('totara_plan');
        $planrecord1 = $plangenerator->create_learning_plan(array('userid' => $user1->id));
        $planrecord2 = $plangenerator->create_learning_plan(array('userid' => $user1->id));
        $plan1 = new development_plan($planrecord1->id);
        $plan2 = new development_plan($planrecord2->id);
        $plangenerator->add_learning_plan_competency($plan1->id, $comp1->id);
        $plangenerator->add_learning_plan_competency($plan2->id, $comp2->id);
        $sql = "SELECT pca.*
                  FROM {dp_plan_competency_assign} pca
                 WHERE pca.competencyid = :competencyid";
        $plancompetency1 = $DB->get_record_sql($sql, ['planid' => $plan1->id, 'competencyid' => $comp1->id]);
        $plancompetency2 = $DB->get_record_sql($sql, ['planid' => $plan2->id, 'competencyid' => $comp2->id]);

        // Set up two review data records with two distinct competencies.
        $reviewdata1 = new stdClass();
        $reviewdata1->appraisalquestfieldid = 123;
        $reviewdata1->appraisalscalevalueid = 0;
        $reviewdata1->appraisalroleassignmentid = 456;
        $reviewdata1->itemid = $plancompetency1->id;
        $reviewdata1->content = 'x'; // Make sure that the string "0" is a valid answer.
        $DB->insert_record('appraisal_review_data', $reviewdata1);

        $reviewdata2 = new stdClass();
        $reviewdata2->appraisalquestfieldid = 123;
        $reviewdata2->appraisalscalevalueid = 0;
        $reviewdata2->appraisalroleassignmentid = 789;
        $reviewdata2->itemid = $plancompetency2->id;
        $reviewdata2->content = '';
        $DB->insert_record('appraisal_review_data', $reviewdata2);

        // First check the review data record which has an answer for the first competency, but none for the second.
        // Note that although the first role only answers the first competency, the exporter will include records
        // for both competencies.
        $exporter = \totara_question\local\export_helper::create('appraisal', 'appraisalroleassignmentid', 'compfromplan');

        $data = new stdClass();
        $data->data_123 = null;
        $data->appraisalroleassignmentid = 456;

        $question = new stdClass();
        $question->id = 123;

        $answer1 = new stdClass();
        $answer1->item = $comp1->fullname;
        $answer1->answer = $reviewdata1->content;

        $expectedresult = [
            $answer1,
            // The other competency doesn't show here at all, because there are no review data records for it for this user.
        ];

        $result = $exporter->export_data($data, $question);

        $this->assertEquals($expectedresult, $result);

        // Then check the review data record which has no answer to either competency.
        $exporter = \totara_question\local\export_helper::create('appraisal', 'appraisalroleassignmentid', 'compfromplan');

        $data->appraisalroleassignmentid = 789;

        $answer2 = new stdClass();
        $answer2->item = $comp2->fullname;
        $answer2->answer = get_string('noanswer', 'totara_question');

        $expectedresult = [
            // The other competency doesn't show here at all, because there are no review data records for it for this user.
            $answer2,
        ];

        $result = $exporter->export_data($data, $question);

        $this->assertEquals($expectedresult, $result);
    }

    public function test_export_data_multiple_questions() {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/totara/hierarchy/lib.php');
        require_once($CFG->dirroot.'/totara/question/field/multichoice.class.php');

        $this->resetAfterTest();

        // Set up the scale, which contains the subquestions.
        $scale = new stdClass();
        $scale->name = 'Subquestions';
        $scale->userid = 666;
        $scale->scaletype = multichoice::SCALE_TYPE_REVIEW;
        $scale->id = $DB->insert_record('appraisal_scale', $scale);

        $scalevalue1 = new stdClass();
        $scalevalue1->appraisalscaleid = $scale->id;
        $scalevalue1->name = 'Subquestion 1';
        $scalevalue1->id = $DB->insert_record('appraisal_scale_value', $scalevalue1);

        $scalevalue2 = new stdClass();
        $scalevalue2->appraisalscaleid = $scale->id;
        $scalevalue2->name = 'Subquestion 2';
        $scalevalue2->id = $DB->insert_record('appraisal_scale_value', $scalevalue2);

        $scalevalue3 = new stdClass();
        $scalevalue3->appraisalscaleid = $scale->id;
        $scalevalue3->name = 'Subquestion 3';
        $scalevalue3->id = $DB->insert_record('appraisal_scale_value', $scalevalue3);

        $comp1 = $this->create_competency(111);
        $comp2 = $this->create_competency(222);

        $user1 = $this->getDataGenerator()->create_user();

        // Stupid access control.
        $this->setAdminUser();

        // Competency plan assignments.
        /** @var totara_plan_generator $plangenerator */
        $plangenerator = $this->getDataGenerator()->get_plugin_generator('totara_plan');
        $planrecord1 = $plangenerator->create_learning_plan(array('userid' => $user1->id));
        $planrecord2 = $plangenerator->create_learning_plan(array('userid' => $user1->id));
        $plan1 = new development_plan($planrecord1->id);
        $plan2 = new development_plan($planrecord2->id);
        $plangenerator->add_learning_plan_competency($plan1->id, $comp1->id);
        $plangenerator->add_learning_plan_competency($plan2->id, $comp2->id);
        $sql = "SELECT pca.*
                  FROM {dp_plan_competency_assign} pca
                 WHERE pca.competencyid = :competencyid";
        $plancompetency1 = $DB->get_record_sql($sql, ['planid' => $plan1->id, 'competencyid' => $comp1->id]);
        $plancompetency2 = $DB->get_record_sql($sql, ['planid' => $plan2->id, 'competencyid' => $comp2->id]);

        // Set up review data records for the first role, same competency, two of the three subquestions.
        $reviewdata1 = new stdClass();
        $reviewdata1->appraisalquestfieldid = 123;
        $reviewdata1->appraisalscalevalueid = $scalevalue1->id;
        $reviewdata1->appraisalroleassignmentid = 456;
        $reviewdata1->itemid = $plancompetency1->id;
        $reviewdata1->content = '0'; // Make sure that the string "0" is a valid answer.
        $DB->insert_record('appraisal_review_data', $reviewdata1);

        $reviewdata2 = new stdClass();
        $reviewdata2->appraisalquestfieldid = 123;
        $reviewdata2->appraisalscalevalueid = $scalevalue2->id;
        $reviewdata2->appraisalroleassignmentid = 456;
        $reviewdata2->itemid = $plancompetency1->id;
        $reviewdata2->content = 'First competency, first role, second subanswer';
        $DB->insert_record('appraisal_review_data', $reviewdata2);

        // No answer to subquestion 3!

        // Set up another competency, which neither role answers any subquestions (only one empty answer placeholder).
        $reviewdata3 = new stdClass();
        $reviewdata3->appraisalquestfieldid = 123;
        $reviewdata3->appraisalscalevalueid = $scalevalue2->id;
        $reviewdata3->appraisalroleassignmentid = 789;
        $reviewdata3->itemid = $plancompetency2->id;
        $reviewdata3->content = '';
        $DB->insert_record('appraisal_review_data', $reviewdata3);

        // There should be two answers to two subquestions, and 4 empty subanswers.
        $exporter = \totara_question\local\export_helper::create('appraisal', 'appraisalroleassignmentid', 'compfromplan');

        $data = new stdClass();
        $data->data_123 = null;
        $data->appraisalroleassignmentid = 456;

        $question = new stdClass();
        $question->id = 123;
        $question->param1 = $scale->id;

        $answer1 = new stdClass();
        $answer1->item = $comp1->fullname;
        $subquestion1 = new stdClass();
        $subquestion1->question = $scalevalue1->name;
        $subquestion1->answer = $reviewdata1->content;
        $subquestion2 = new stdClass();
        $subquestion2->question = $scalevalue2->name;
        $subquestion2->answer = $reviewdata2->content;
        $subquestion3 = new stdClass();
        $subquestion3->question = $scalevalue3->name;
        $subquestion3->answer = get_string('noanswer', 'totara_question');
        $answer1->subquestions = [
            $subquestion1,
            $subquestion2,
            $subquestion3,
        ];

        $expectedresult = [
            $answer1,
            // The other competency doesn't show here at all, because there are no review data records for it for this user.
        ];

        $result = $exporter->export_data($data, $question);

        $this->assert_export_data_multiple_questions($expectedresult, $result);

        // Then check the review data record which has no answer to either competency.
        $exporter = \totara_question\local\export_helper::create('appraisal', 'appraisalroleassignmentid', 'compfromplan');

        $data->appraisalroleassignmentid = 789;

        $answer2 = new stdClass();
        $answer2->item = $comp2->fullname;
        $subquestion1 = new stdClass();
        $subquestion1->question = $scalevalue1->name;
        $subquestion1->answer = get_string('noanswer', 'totara_question');
        $subquestion2 = new stdClass();
        $subquestion2->question = $scalevalue2->name;
        $subquestion2->answer = get_string('noanswer', 'totara_question');
        $subquestion3 = new stdClass();
        $subquestion3->question = $scalevalue3->name;
        $subquestion3->answer = get_string('noanswer', 'totara_question');
        $answer2->subquestions = [
            $subquestion1,
            $subquestion2,
            $subquestion3,
        ];

        $expectedresult = [
            // The other competency doesn't show here at all, because there are no review data records for it for this user.
            $answer2,
        ];
        $result = $exporter->export_data($data, $question);

        $this->assert_export_data_multiple_questions($expectedresult, $result);
    }

    private function assert_export_data_multiple_questions(array &$expectedresult, array $result): void {
        $this->assertCount(1, $result);
        $this->assertObjectHasAttribute('subquestions', $result[0]);
        $this->assertCount(3, $result[0]->subquestions);

        // stabilise the order of subquestions
        usort(
            $result[0]->subquestions,
            function ($x, $y) {
                return strcmp($x->question, $y->question);
            }
        );

        $this->assertEquals($expectedresult, $result);
    }

    public function create_competency(int $id, $record = array()) {
        $default = array(
            'shortname' => 'Competency ' . $id,
            'fullname' => 'Competency #' . $id,
            'description' => 'This is test competency #' . $id,
            'idnumber' => 'ID' . $id,
            'timemodified' => time(),
            'usermodified' => 2,
            'proficiencyexpected' => 1,
            'evidencecount' => 0,
            'visible' => 1,
            'aggregationmethod' => 0,

        );
        $properties = array_merge($default, $record);

        $hierarchy = \hierarchy::load_hierarchy('competency');
        $result = $hierarchy->add_hierarchy_item((object)$properties, 0, 1, false);
        return $result;
    }
}