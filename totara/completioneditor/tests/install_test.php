<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Nathan Lewis <nathan.lewis@totaralms.com>
 * @package totara_completioneditor
 */

global $CFG;

require_once($CFG->dirroot . '/totara/completioneditor/db/install.php');
require_once($CFG->dirroot . '/completion/completion_completion.php');

defined('MOODLE_INTERNAL') || die();

/**
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose totara_completioneditor_install_testcase totara/completioneditor/tests/install_test.php
 */
class totara_completioneditor_install_testcase extends advanced_testcase {

    public function test_totara_completioneditor_install_log_existing_module_completions() {
        global $DB;

        $this->resetAfterTest(true);

        $cm = new stdClass();
        $cm->course = 123;
        $cmid = $DB->insert_record('course_modules', $cm);

        $cmc = new stdClass();
        $cmc->coursemoduleid = $cmid;
        $cmc->userid = 234;
        $cmc->completionstate = 34;
        $cmc->timemodified = 456;
        $cmcid1 = $DB->insert_record('course_modules_completion', $cmc);

        $cmc = new stdClass();
        $cmc->coursemoduleid = $cmid;
        $cmc->userid = 222;
        $cmc->completionstate = 33;
        $cmc->viewed = 44;
        $cmc->timemodified = 555;
        $cmc->timecompleted = 666;
        $cmc->reaggregate = 777;
        $cmcid2 = $DB->insert_record('course_modules_completion', $cmc);

        totara_completioneditor_install_log_existing_module_completions();

        $this->assertEquals(2, $DB->count_records('course_completion_log'));

        $log = $DB->get_record('course_completion_log', array('courseid' => 123, 'userid' => 234));
        $this->assertContains((string)$cmcid1, $log->description);
        $this->assertContains('34', $log->description);
        $this->assertContains('456', $log->description);

        $log = $DB->get_record('course_completion_log', array('courseid' => 123, 'userid' => 222));
        $this->assertContains((string)$cmcid2, $log->description);
        $this->assertContains('33', $log->description);
        $this->assertContains('44', $log->description);
        $this->assertContains('555', $log->description);
        $this->assertContains('666', $log->description);
        $this->assertContains('777', $log->description);
    }

    public function test_totara_completioneditor_install_log_existing_criteria_completions() {
        global $DB;

        $this->resetAfterTest(true);

        $cccc = new stdClass();
        $cccc->userid = 111;
        $cccc->course = 222;
        $cccc->criteriaid = 999;
        $ccccid1 = $DB->insert_record('course_completion_crit_compl', $cccc);

        $cccc = new stdClass();
        $cccc->userid = 123;
        $cccc->course = 234;
        $cccc->criteriaid = 901;
        $cccc->gradefinal = 45.67;
        $cccc->unenroled = 567;
        $cccc->rpl = 'a reason';
        $cccc->timecompleted = 789;
        $ccccid2 = $DB->insert_record('course_completion_crit_compl', $cccc);

        totara_completioneditor_install_log_existing_criteria_completions();

        $this->assertEquals(2, $DB->count_records('course_completion_log'));

        $log = $DB->get_record('course_completion_log', array('userid' => 111, 'courseid' => 222));
        $this->assertContains((string)$ccccid1, $log->description);

        $log = $DB->get_record('course_completion_log', array('userid' => 123, 'courseid' => 234));
        $this->assertContains((string)$ccccid2, $log->description);
        $this->assertContains('45.67', $log->description);
        $this->assertContains('567', $log->description);
        $this->assertContains('a reason', $log->description);
        $this->assertContains('789', $log->description);
    }

    public function test_totara_completioneditor_install_log_existing_history_completions() {
        global $DB;

        $this->resetAfterTest(true);

        $cch = new stdClass();
        $cch->courseid = 111;
        $cch->userid = 222;
        $cchid1 = $DB->insert_record('course_completion_history', $cch);

        $cch = new stdClass();
        $cch->courseid = 123;
        $cch->userid = 234;
        $cch->timecompleted = 345;
        $cch->grade = 45.67;
        $cchid2 = $DB->insert_record('course_completion_history', $cch);

        totara_completioneditor_install_log_existing_history_completions();

        $this->assertEquals(2, $DB->count_records('course_completion_log'));

        $log = $DB->get_record('course_completion_log', array('courseid' => 111, 'userid' => 222));
        $this->assertContains((string)$cchid1, $log->description);

        $log = $DB->get_record('course_completion_log', array('courseid' => 123, 'userid' => 234));
        $this->assertContains((string)$cchid2, $log->description);
        $this->assertContains('345', $log->description);
        $this->assertContains('45.67', $log->description);
    }

    public function test_totara_completioneditor_install_log_existing_current_completions() {
        global $DB;

        $this->resetAfterTest(true);

        $cc = new stdClass();
        $cc->userid = 111;
        $cc->course = 222;
        $DB->insert_record('course_completions', $cc);

        $cc = new stdClass();
        $cc->userid = 123;
        $cc->course = 234;
        $cc->timeenrolled = 345;
        $cc->timestarted = 456;
        $cc->timecompleted = 567;
        $cc->reaggregate = 678;
        $cc->rpl = 'a reason';
        $cc->rplgrade = 78.09;
        $cc->status = 89;
        $DB->insert_record('course_completions', $cc);

        totara_completioneditor_install_log_existing_current_completions();

        $this->assertEquals(2, $DB->count_records('course_completion_log'));

        $log = $DB->get_record('course_completion_log', array('userid' => 111, 'courseid' => 222));
        $this->assertNotEmpty($log->description);

        $log = $DB->get_record('course_completion_log', array('userid' => 123, 'courseid' => 234));
        $this->assertContains('345', $log->description);
        $this->assertContains('456', $log->description);
        $this->assertContains('567', $log->description);
        $this->assertContains('678', $log->description);
        $this->assertContains('a reason', $log->description);
        $this->assertContains('78.09', $log->description);
        $this->assertContains('89', $log->description);
    }
}
