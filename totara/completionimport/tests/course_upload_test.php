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
 * Tests importing courses from a generated csv file
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit totara/completionimport/tests/course_upload_test.php
 *
 * @author     David Curry <david.curry@totaralms.com>
 * @package    totara_completionimport
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/completionimport/lib.php');

/**
 * Class totara_completionimport_course_upload_testcase
 *
 * @group totara_completionimport
 */
class totara_completionimport_course_upload_testcase extends advanced_testcase {

    protected $user1, $course1;

    protected function tearDown() {
        $this->user1 = null;
        $this->course1 = null;
        parent::tearDown();
    }

    public function setup() {
        set_config('enablecompletion', 1);

        $datagen = $this->getDataGenerator();

        // Create user(s).
        $urecord = new stdClass();
        $urecord->username = 'franklin';
        $this->user1 = $datagen->create_user($urecord);

        // Create course(s).
        $crecord = new stdClass();
        $crecord->shortname = 'uploadtests';
        $crecord->idnumber = 'c1_upl';
        $crecord->enablecompletion = 1;
        $crecord->completionstartonenrol = 1;
        $this->course1 = $this->getDataGenerator()->create_course($crecord);
    }

    public function test_course_singular_upload_empty() {
        global $DB, $CFG;

        $this->resetAfterTest(true);

        $filename = $CFG->dirroot . '/totara/completionimport/tests/fixtures/course_single_upload.csv';
        $importname = 'course';
        $importtime = time();

        $this->assertEquals(0, $DB->count_records('course_completions'));
        $this->assertEquals(0, $DB->count_records('course_completion_history'));

        $handle = fopen($filename, 'r');
        $this->assertNotSame(false, $handle);
        $size = filesize($filename);
        $this->assertGreaterThan(0, $size);
        $content = fread($handle, $size);
        $this->assertNotSame(false, $content);
        \totara_completionimport\csv_import::import($content, $importname, $importtime);

        $this->assertEquals(1, $DB->count_records('course_completions'));
        $this->assertEquals(0, $DB->count_records('course_completion_history'));

        $record = $DB->get_record('course_completions', array('userid' => $this->user1->id));

        $expected = totara_date_parse_from_format('Y-m-d', '2020-06-06');
        $this->assertEquals($expected, $record->timecompleted);
        $this->assertEquals(80, $record->rplgrade);

    }

    public function test_course_singular_upload_existing() {
        global $DB, $CFG;

        $this->resetAfterTest(true);

        // Set up a dummy completion.
        $completiondata = new stdClass();
        $completiondata->userid = $this->user1->id;
        $completiondata->course = $this->course1->id;
        $completiondata->timeenrolled = time();
        $completiondata->timestarted = time();
        $completiondata->timecompleted = time();
        $completiondata->reaggregate = 0;
        $completiondata->rpl = "Completion history import - imported grade = 75";
        $completiondata->rplgrade = "75.00000";
        $completiondata->invalidatecache = "0";
        $completiondata->status = "75";
        $completiondata->renewalstatus = "0";
        $DB->insert_record('course_completions', $completiondata);

        $filename = $CFG->dirroot . '/totara/completionimport/tests/fixtures/course_single_upload.csv';
        $importname = 'course';
        $importtime = time();

        $this->assertEquals(1, $DB->count_records('course_completions'));
        $this->assertEquals(0, $DB->count_records('course_completion_history'));

        $handle = fopen($filename, 'r');
        $this->assertNotSame(false, $handle);
        $size = filesize($filename);
        $this->assertGreaterThan(0, $size);
        $content = fread($handle, $size);
        $this->assertNotSame(false, $content);
        \totara_completionimport\csv_import::import($content, $importname, $importtime);

        $this->assertEquals(1, $DB->count_records('course_completions'));
        $this->assertEquals(1, $DB->count_records('course_completion_history'));

        $record = $DB->get_record('course_completion_history', array('userid' => $this->user1->id));

        $expected = totara_date_parse_from_format('Y-m-d', '2020-06-06');
        $this->assertEquals($expected, $record->timecompleted);
        $this->assertEquals(80, $record->grade);
    }
}
