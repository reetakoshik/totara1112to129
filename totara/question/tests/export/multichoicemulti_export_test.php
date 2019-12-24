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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara
 * @subpackage question
 */

global $CFG;
require_once($CFG->dirroot.'/totara/question/tests/question_testcase.php');

/**
 * @group totara_userdata
 * @group totara_question
 * @Class totara_question_multichoicemulti_export_testcase
 *
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose totara_question_multichoicemulti_export_testcase totara/question/tests/export/multichoicemulti_export_test.php
 */
class totara_question_multichoicemulti_export_testcase extends advanced_testcase {

    public function test_export_data_no_answer() {
        global $DB, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create some dummy scale data.
        $scaleid = $DB->insert_record('feedback360_scale', (object) ['name' => 'mcmscale', 'userid' => $USER->id, 'scaletype' => \multichoice::SCALE_TYPE_MULTICHOICE]);
        $scalevalue1id = $DB->insert_record('feedback360_scale_value', (object) ['feedback360scaleid' => $scaleid, 'name' => 'value1']);
        $scalevalue2id = $DB->insert_record('feedback360_scale_value', (object) ['feedback360scaleid' => $scaleid, 'name' => 'value2']);
        $scalevalue3id = $DB->insert_record('feedback360_scale_value', (object) ['feedback360scaleid' => $scaleid, 'name' => 'value3']);
        $scalevalue4id = $DB->insert_record('feedback360_scale_value', (object) ['feedback360scaleid' => $scaleid, 'name' => 'value4']);

        $exporter = \totara_question\local\export_helper::create('feedback360', 'feedback360respassignmentid', 'multichoicemulti');

        $data = new stdClass();
        $data->data_123 = null;
        $data->feedback360respassignmentid = 123;

        $question = new stdClass();
        $question->id = 123;
        $question->param1 = $scaleid;

        $expectedresult = get_string('noanswer', 'totara_question');
        $result = $exporter->export_data($data, $question);

        $this->assertEquals($expectedresult, $result);
    }

    public function test_export_feedback360_data() {
        global $DB, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create some dummy scale data.
        $scaleid = $DB->insert_record('feedback360_scale', (object) ['name' => 'mcmscale', 'userid' => $USER->id, 'scaletype' => \multichoice::SCALE_TYPE_MULTICHOICE]);
        $scalevalue1id = $DB->insert_record('feedback360_scale_value', (object) ['feedback360scaleid' => $scaleid, 'name' => 'value1']);
        $scalevalue2id = $DB->insert_record('feedback360_scale_value', (object) ['feedback360scaleid' => $scaleid, 'name' => 'value2']);
        $scalevalue3id = $DB->insert_record('feedback360_scale_value', (object) ['feedback360scaleid' => $scaleid, 'name' => 'value3']);
        $scalevalue4id = $DB->insert_record('feedback360_scale_value', (object) ['feedback360scaleid' => $scaleid, 'name' => 'value4']);

        $scaledata1 = new \stdClass();
        $scaledata1->feedback360scalevalueid = $scalevalue1id;
        $scaledata1->feedback360respassignmentid = 321;
        $scaledata1->feedback360questfieldid = 321;
        $scaledata1id = $DB->insert_record('feedback360_scale_data', $scaledata1);

        $scaledata2 = new \stdClass();
        $scaledata2->feedback360scalevalueid = $scalevalue4id;
        $scaledata2->feedback360respassignmentid = 321;
        $scaledata2->feedback360questfieldid = 321;
        $scaledata2id = $DB->insert_record('feedback360_scale_data', $scaledata2);

        $exporter = \totara_question\local\export_helper::create('feedback360', 'feedback360respassignmentid', 'multichoicemulti');

        $data = new stdClass();
        $data->data_321 = -1;
        $data->feedback360respassignmentid = 321;

        $question = new stdClass();
        $question->id = 321;
        $question->param1 = $scaleid;

        $result = $exporter->export_data($data, $question);

        $this->assertEquals(2, count($result));
        $this->assertContains('value1', $result);
        $this->assertContains('value4', $result);
    }

    public function test_export_appraisal_data() {
        global $DB, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create some dummy scale data.
        $scaleid = $DB->insert_record('appraisal_scale', (object) ['name' => 'mcmscale', 'userid' => $USER->id, 'scaletype' => \multichoice::SCALE_TYPE_MULTICHOICE]);
        $scalevalue1id = $DB->insert_record('appraisal_scale_value', (object) ['appraisalscaleid' => $scaleid, 'name' => 'value1']);
        $scalevalue2id = $DB->insert_record('appraisal_scale_value', (object) ['appraisalscaleid' => $scaleid, 'name' => 'value2']);
        $scalevalue3id = $DB->insert_record('appraisal_scale_value', (object) ['appraisalscaleid' => $scaleid, 'name' => 'value3']);
        $scalevalue4id = $DB->insert_record('appraisal_scale_value', (object) ['appraisalscaleid' => $scaleid, 'name' => 'value4']);

        $scaledata1 = new \stdClass();
        $scaledata1->appraisalscalevalueid = $scalevalue1id;
        $scaledata1->appraisalroleassignmentid = 123;
        $scaledata1->appraisalquestfieldid = 123;
        $scaledata1id = $DB->insert_record('appraisal_scale_data', $scaledata1);

        $scaledata2 = new \stdClass();
        $scaledata2->appraisalscalevalueid = $scalevalue4id;
        $scaledata2->appraisalroleassignmentid = 123;
        $scaledata2->appraisalquestfieldid = 123;
        $scaledata2id = $DB->insert_record('appraisal_scale_data', $scaledata2);

        $exporter = \totara_question\local\export_helper::create('appraisal', 'appraisalroleassignmentid', 'multichoicemulti');

        $data = new stdClass();
        $data->data_123 = $scalevalue3id;
        $data->appraisalroleassignmentid = 123;

        $question = new stdClass();
        $question->id = 123;
        $question->param1 = $scaleid;

        $result = $exporter->export_data($data, $question);

        $this->assertEquals(2, count($result));
        $this->assertContains('value1', $result);
        $this->assertContains('value4', $result);
    }
}
