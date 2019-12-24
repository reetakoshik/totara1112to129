<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @package totara_feedback360
 */

global $CFG;
require_once($CFG->dirroot.'/totara/feedback360/db/upgradelib.php');

/**
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose totara_feedback360_upgradelib_test totara/feedback360/tests/upgradelib_test.php
 */
class totara_feedback360_upgradelib_test extends advanced_testcase {

    public function test_totara_feedback360_upgrade_fix_inconsistent_multichoice_param1() {
        $this->resetAfterTest();

        global $DB;

        // Insert some fake data.
        $question = new stdClass();
        $question->feedback360id = 123;
        $question->name = '1 fix me 1';
        $question->sortorder = 234;
        $question->datatype = 'multichoicemulti';
        $question->requried = 0;
        $question->param1 = '"345"';
        $question->param2 = '"456"';
        $question->param3 = '"567"';
        $question->param4 = '"678"';
        $question->param5 = '"789"';
        $DB->insert_record('feedback360_quest_field', $question);

        $question->name = '2 fix me 2';
        $question->datatype = 'multichoicesingle';
        $question->param1 = '"346"';
        $question->param2 = '456';
        $question->param3 = '{"1":"2","3":"4"}';
        $question->param4 = '[]';
        $question->param5 = null;
        $DB->insert_record('feedback360_quest_field', $question);

        $question->name = '3 leave me type';
        $question->datatype = 'someothertype';
        $question->param1 = '"347"';
        $DB->insert_record('feedback360_quest_field', $question);

        $question->name = '4 leave me int';
        $question->datatype = 'multichoicesingle';
        $question->param1 = '348';
        $DB->insert_record('feedback360_quest_field', $question);

        $question->name = '5 leave me null';
        $question->datatype = 'multichoicemulti';
        $question->param1 = null;
        $DB->insert_record('feedback360_quest_field', $question);

        $question->name = '6 leave me empty array';
        $question->datatype = 'multichoicemulti';
        $question->param1 = '[]';
        $DB->insert_record('feedback360_quest_field', $question);

        $question->name = '7 leave me array';
        $question->datatype = 'multichoicemulti';
        $question->param1 = '{"1":"2","3":"4"}';
        $DB->insert_record('feedback360_quest_field', $question);

        // Construct the expected results.
        $expectedresults = $DB->get_records('feedback360_quest_field', array(), 'name');
        $expectedfixme1 = reset($expectedresults);
        $expectedfixme1->param1 = '345';
        $expectedfixme2 = next($expectedresults);
        $expectedfixme2->param1 = '346';

        // Run the function.
        totara_feedback360_upgrade_fix_inconsistent_multichoice_param1();

        // Check the results.
        $actualresults = $DB->get_records('feedback360_quest_field', array(), 'name');
        $this->assertEquals($expectedresults, $actualresults);
    }
}