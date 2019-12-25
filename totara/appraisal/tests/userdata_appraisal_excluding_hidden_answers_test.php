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
 * @package totara_appraisal
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/totara/appraisal/tests/userdata_appraisal_export_answers.php');

/**
 * @group totara_userdata
 *
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose totara_appraisal_userdata_appraisal_excluding_hidden_answers_testcase totara/appraisal/tests/userdata_appraisal_excluding_hidden_answers_test.php
 */
class totara_appraisal_userdata_appraisal_excluding_hidden_answers_testcase extends totara_appraisal_userdata_appraisal_export_answers_testcase {

    protected function classtotest() {
        return "\\totara_appraisal\\userdata\\appraisal_excluding_hidden_answers";
    }

    protected function includehiddenanswers() {
        return false;
    }
}