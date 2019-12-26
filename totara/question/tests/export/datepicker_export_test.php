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
 * vendor/bin/phpunit --verbose totara_question_datepicker_export_testcase totara/question/tests/export/datepicker_export_test.php
 */
class totara_question_datepicker_export_testcase extends advanced_testcase {

    public function test_export_data_no_answer() {
        $exporter = \totara_question\local\export_helper::create('appraisal', 'xyz', 'datepicker');

        $data = new stdClass();
        $data->data_123 = null;

        $question = new stdClass();
        $question->id = 123;

        $expectedresult = get_string('noanswer', 'totara_question');
        $result = $exporter->export_data($data, $question);

        $this->assertEquals($expectedresult, $result);
    }

    public function test_export_data_zero_answer() {
        $exporter = \totara_question\local\export_helper::create('appraisal', 'xyz', 'datepicker');

        $data = new stdClass();
        $data->data_123 = "0";

        $question = new stdClass();
        $question->id = 123;

        $expectedresult = get_string('noanswer', 'totara_question');
        $result = $exporter->export_data($data, $question);

        $this->assertEquals($expectedresult, $result);
    }

    public function test_export_data_with_seconds() {
        $exporter = \totara_question\local\export_helper::create('appraisal', 'xyz', 'datepicker');

        $targettime = time() - DAYSECS * 10;
        $targetformat = get_string('strfdateattime', 'langconfig');

        $data = new stdClass();
        $data->data_123 = $targettime;

        $question = new stdClass();
        $question->id = 123;
        $question->param1 = '{"withtime":"1"}';

        $expectedresult = userdate($targettime, $targetformat);
        $result = $exporter->export_data($data, $question);

        $this->assertEquals($expectedresult, $result);
    }

    public function test_export_data_without_seconds() {
        $exporter = \totara_question\local\export_helper::create('appraisal', 'xyz', 'datepicker');

        $targettime = time() - DAYSECS * 10;
        $targetformat = get_string('strfdateshortmonth', 'langconfig');

        $data = new stdClass();
        $data->data_123 = $targettime;

        $question = new stdClass();
        $question->id = 123;
        $question->param1 = '{"withtime":"0"}';

        $expectedresult = userdate($targettime, $targetformat);
        $result = $exporter->export_data($data, $question);

        $this->assertEquals($expectedresult, $result);
    }

    public function test_export_data_with_timezone() {
        $exporter = \totara_question\local\export_helper::create('appraisal', 'xyz', 'datepicker');

        $targettime = time() - DAYSECS * 10;
        $targetformat = get_string('strfdateattime', 'langconfig');
        $targettimezone = "UTC+3";

        $data = new stdClass();
        $data->data_123 = $targettime;
        $data->data_123tz = $targettimezone;

        $question = new stdClass();
        $question->id = 123;
        $question->param1 = '{"withtime":"1","withtimezone":"1"}';

        $expectedresult = userdate($targettime, $targetformat, $targettimezone) . ' ' . $targettimezone;
        $result = $exporter->export_data($data, $question);

        $this->assertEquals($expectedresult, $result);
    }
}