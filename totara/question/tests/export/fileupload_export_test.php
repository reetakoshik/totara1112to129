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
require_once($CFG->dirroot.'/totara/feedback360/lib.php');

/**
 * @group totara_userdata
 * @group totara_question
 * @Class totara_question_fileupload_export_testcase
 *
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose totara_question_fileupload_export_testcase totara/question/tests/export/fileupload_export_test.php
 */
class totara_question_fileupload_export_testcase extends advanced_testcase {

    public function test_export_data_no_answer() {
        $exporter = \totara_question\local\export_helper::create('appraisal', 'xyz', 'fileupload');

        $data = new stdClass();
        $data->data_123 = null;

        $question = new stdClass();
        $question->id = 123;

        $result = $exporter->export_data($data, $question);

        $this->assertEquals('', $result);
    }

    public function test_export_data() {
        $exporter = \totara_question\local\export_helper::create('appraisal', 'xyz', 'fileupload');
        $tststring = 'stringofwordshere';

        $data = new stdClass();
        $data->data_123 = $tststring;

        $question = new stdClass();
        $question->id = 123;
        $question->param1 = 'xyz';

        $result = $exporter->export_data($data, $question);

        $this->assertEquals('', $result);
    }

    public function test_export_files() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $fs = \get_file_storage();
        $syscontext = \context_system::instance();
        $exporter = \totara_question\local\export_helper::create('feedback360', 'feedback360respassignmentid', 'fileupload');

        $feedback360 = new \feedback360();
        $feedback360->name = 'Feedback';
        $feedback360->description = 'Description';
        $feedback360->anonymous = 0;
        $feedback360->selfevaluation = \feedback360::SELF_EVALUATION_OPTIONAL;
        $feedback360->save();

        $question = new \feedback360_question();
        $question->feedback360id = $feedback360->id;
        $question->attach_element('fileupload');
        $question->name = 'FileUpload';
        $question->save();

        $user1 = $this->getDataGenerator()->create_user();
        $uadata = ['feedback360id' => $feedback360->id, 'userid' => $user1->id, 'timedue' => 0];
        $fb360userid = $DB->insert_record('feedback360_user_assignment', (object) $uadata);

        $user2 = $this->getDataGenerator()->create_user();
        $radata = ['feedback360userassignmentid' => $fb360userid, 'userid' => $user2->id, 'timeassigned' => time(), 'requestertoken' => 123];
        $fb360resp1id = $DB->insert_record('feedback360_resp_assignment', (object) $radata);

        $user3 = $this->getDataGenerator()->create_user();
        $radata = ['feedback360userassignmentid' => $fb360userid, 'userid' => $user3->id, 'timeassigned' => time(), 'requestertoken' => 123];
        $fb360resp2id = $DB->insert_record('feedback360_resp_assignment', (object) $radata);

        $user4 = $this->getDataGenerator()->create_user();
        $radata = ['feedback360userassignmentid' => $fb360userid, 'userid' => $user4->id, 'timeassigned' => time(), 'requestertoken' => 123];
        $fb360resp3id = $DB->insert_record('feedback360_resp_assignment', (object) $radata);

        $fileoptions = array(
            'contextid' => $syscontext->id,
            'component' => 'totara_feedback360',
            'filearea' => 'quest_' . $question->id,
            'itemid' => $fb360resp1id,
            'filepath' => '/',
            'filename' => 'leaves-green.png'
        );
        $fs->create_file_from_string($fileoptions, random_string(30));

        $fileoptions['itemid'] = $fb360resp2id;
        $fileoptions['filename'] = 'leaves-blue.png';
        $fs->create_file_from_string($fileoptions, random_string(30));

        $fieldname = "data_{$question->id}";
        $data = new stdClass();
        $data->$fieldname = 1;

        $result = $exporter->export_files($question->id, $fb360resp1id);
        $this->assertEquals(1, count($result));
        $file = array_pop($result);
        $this->assertEquals('totara_feedback360', $file->get_component());
        $this->assertEquals('quest_' . $question->id, $file->get_filearea());
        $this->assertEquals($fb360resp1id, $file->get_itemid());
        $this->assertEquals('leaves-green.png', $file->get_filename());

        $result = $exporter->export_files($question->id, $fb360resp2id);
        $this->assertEquals(1, count($result));
        $file = array_pop($result);
        $this->assertEquals('totara_feedback360', $file->get_component());
        $this->assertEquals('quest_' . $question->id, $file->get_filearea());
        $this->assertEquals($fb360resp2id, $file->get_itemid());
        $this->assertEquals('leaves-blue.png', $file->get_filename());

        $result = $exporter->export_files($question->id, $fb360resp3id);
        $this->assertEmpty($result);
    }
}
