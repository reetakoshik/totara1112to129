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
 * vendor/bin/phpunit importcourse_testcase totara/completionimport/tests/importcourse_test.php
 *
 * @package    totara_completionimport
 * @subpackage phpunit
 * @author     Russell England <russell.england@catalyst-eu.net>
 * @copyright  Catalyst IT Ltd 2013 <http://catalyst-eu.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/completionimport/lib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->libdir . '/completionlib.php');

/**
 * Class totara_completionimport_importcourse_testcase
 *
 * @group totara_completionimport
 */
class totara_completionimport_importcourse_testcase extends advanced_testcase {

    const COUNT_USERS = 11;
    const COUNT_COURSES = 11;
    const COUNT_CSV_ROWS = 100; // Must be less than user * course counts.

    public function test_import() {
        global $DB;

        set_config('enablecompletion', 1);
        $this->resetAfterTest(true);

        $importname = 'course';
        $pluginname = 'totara_completionimport_' . $importname;
        $csvdateformat = get_default_config($pluginname, 'csvdateformat', TCI_CSV_DATE_FORMAT);

        $this->setAdminUser();

        // Create courses with completion enabled.
        $generatorstart = time();
        $this->assertEquals(1, $DB->count_records('course')); // Site course.
        $coursedefaults = array('enablecompletion' => COMPLETION_ENABLED);
        for ($i = 1; $i <= self::COUNT_USERS; $i++) {
            $this->getDataGenerator()->create_course($coursedefaults);
        }
        // Site course + generated courses.
        $this->assertEquals(self::COUNT_USERS+1, $DB->count_records('course'),
            'Record count mismatch for courses');

        // Create users
        $this->assertEquals(2, $DB->count_records('user')); // Guest + Admin.
        for ($i = 1; $i <= self::COUNT_COURSES; $i++) {
            $this->getDataGenerator()->create_user();
        }
        // Guest + Admin + generated users.
        $this->assertEquals(self::COUNT_COURSES+2, $DB->count_records('user'),
            'Record count mismatch for users');

        // Manual enrol should be set.
        $this->assertEquals(self::COUNT_COURSES, $DB->count_records('enrol', array('enrol'=>'manual')),
            'Manual enrol is not set for all courses');

        // Generate import data - product of user and course tables - exluding site course and admin/guest user.
        $fields = array('username', 'courseshortname', 'courseidnumber', 'completiondate', 'grade');

        // Start building the content that would be returned from a csv file.
        $content = implode(",", $fields) . "\n";

        $uniqueid = $DB->sql_concat('u.username', 'c.shortname');
        $sql = "SELECT  {$uniqueid} AS uniqueid,
                        u.username,
                        c.shortname AS courseshortname,
                        c.idnumber AS courseidnumber
                FROM    {user} u,
                        {course} c
                WHERE   u.id > 2
                AND     c.id > 1";
        $imports = $DB->get_recordset_sql($sql, null, 0, self::COUNT_CSV_ROWS);
        if ($imports->valid()) {
            $count = 0;
            foreach ($imports as $import) {
                $data = array();
                $data['username'] = $import->username;
                $data['courseshortname'] = $import->courseshortname;
                $data['courseidnumber'] = $import->courseidnumber;
                $data['completiondate'] = date($csvdateformat, strtotime(date('Y-m-d') . ' -' . rand(1, 365) . ' days'));
                $data['grade'] = rand(1, 100);
                $content .= implode(",", $data) . "\n";
                $count++;
            }
            // Create records to save them as evidence.
            $countevidence = 2;
            for ($i = 1; $i <= $countevidence; $i++) {
                $lastrecord = $data;
                $data['username'] = $lastrecord['username'];
                $data['courseshortname'] = ($i == 1) ? 'mycourseshortname' : $lastrecord['courseshortname'];
                $data['courseidnumber'] = 'XXXY';
                $data['completiondate'] = $lastrecord['completiondate'];
                $data['grade'] = rand(1, 100);
                $content .= implode(",", $data) . "\n";
                $count++;
            }
        }
        $imports->close();
        $this->assertEquals(self::COUNT_CSV_ROWS + $countevidence, $count, 'Record count mismatch when creating CSV file');

        // Time info for load testing - 4.4 minutes for 10,000 csv rows on postgresql.
        $generatorstop = time();

        $importstart = time();
        \totara_completionimport\csv_import::import($content, $importname, $importstart);
        $importstop = time();

        $importtablename = get_tablename($importname);
        $this->assertEquals(self::COUNT_CSV_ROWS + $countevidence, $DB->count_records($importtablename),
            'Record count mismatch in the import table ' . $importtablename);
        $this->assertEquals($countevidence, $DB->count_records('dp_plan_evidence'),
            'There should be two evidence records');
        $this->assertEquals(self::COUNT_CSV_ROWS, $DB->count_records('course_completions'),
            'Record count mismatch in the course_completions table');
        $this->assertEquals(self::COUNT_CSV_ROWS, $DB->count_records('user_enrolments'),
            'Record count mismatch in the user_enrolments table');
    }

    /**
     * Test the test_completionimport_resolve_references() function to ensure the courseid is matched correctly from
     * the csv courseshortname and idnumber fields.
     */
    public function test_completionimport_resolve_references() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        set_config('enablecompletion', 1);

        // Create a course with completion enabled.
        $course1 = $this->getDataGenerator()->create_course(array(
            'enablecompletion' => COMPLETION_ENABLED,
            'shortname' => 'course1',
            'idnumber' => '1'));

        // Create another course with completion enabled and blank spaces in the shortname and idnumber fields.
        $course2 = $this->getDataGenerator()->create_course(array(
            'enablecompletion' => COMPLETION_ENABLED,
            'shortname' => '   course2   ',
            'idnumber' => '   2   '));

        // Create a user.
        $user1 = $this->getDataGenerator()->create_user();

        $importname = 'course';
        $importtablename = get_tablename($importname);
        $pluginname = 'totara_completionimport_' . $importname;
        $csvdateformat = get_default_config($pluginname, 'csvdateformat', TCI_CSV_DATE_FORMAT);
        $completiondate = date($csvdateformat, time());
        $importstart = time();

        // Generate import data.
        $fields = array('username', 'courseshortname', 'courseidnumber', 'completiondate', 'grade');

        //
        // Test completion is saved correctly.
        //

        $content = implode(",", $fields) . "\n";
        $data = array();
        $data['username'] = $user1->username;
        $data['courseshortname'] = $course1->shortname;
        $data['courseidnumber'] = $course1->idnumber;
        $data['completiondate'] = $completiondate;
        $data['grade'] = 77;
        $content .= implode(",", $data) . "\n";

        \totara_completionimport\csv_import::import($content, $importname, $importstart);

        $importdata = $DB->get_records($importtablename, null, 'id asc');
        $import = end($importdata);

        $this->assertEmpty($import->importerrormsg,'There should be no import errors: ' . $import->importerrormsg);
        $this->assertEquals(0, $DB->count_records('dp_plan_evidence'), 'Evidence should not be created');
        $this->assertEquals($course1->id, $import->courseid, 'The course was not matched');

        //
        // Test completion is saved correctly when csv has empty spaces in shortname and idnumber
        //

        $content = implode(",", $fields) . "\n";
        $data = array();
        $data['username'] = $user1->username;
        $data['courseshortname'] = '   ' . $course1->shortname . '   ';
        $data['courseidnumber'] = '   ' . $course1->idnumber . '   ';
        $data['completiondate'] = $completiondate;
        $data['grade'] = 77;
        $content .= implode(",", $data) . "\n";

        \totara_completionimport\csv_import::import($content, $importname, $importstart);

        $importdata = $DB->get_records($importtablename, null, 'id asc');
        $import = end($importdata);

        $this->assertEmpty($import->importerrormsg,'There should be no import errors: ' . $import->importerrormsg);
        $this->assertEquals(0, $DB->count_records('dp_plan_evidence'), 'Evidence should not be created');
        $this->assertEquals($course1->id, $import->courseid, 'The course was not matched');

        //
        // Test completion is saved correctly when course has empty spaces in shortname and idnumber.
        //

        $content = implode(",", $fields) . "\n";
        $data = array();
        $data['username'] = $user1->username;
        $data['courseshortname'] = $course2->shortname;
        $data['courseidnumber'] = $course2->idnumber;
        $data['completiondate'] = $completiondate;
        $data['grade'] = 77;
        $content .= implode(",", $data) . "\n";

        \totara_completionimport\csv_import::import($content, $importname, $importstart);

        $importdata = $DB->get_records($importtablename, null, 'id asc');
        $import = end($importdata);

        $this->assertEmpty($import->importerrormsg,'There should be no import errors: ' . $import->importerrormsg);
        $this->assertEquals(0, $DB->count_records('dp_plan_evidence'), 'Evidence should not be created');
        $this->assertEquals($course2->id, $import->courseid, 'The course was not matched');

        //
        // Test completion is saved correctly when course and csv has empty spaces in shortname and idnumber, crazy hey!
        //

        $content = implode(",", $fields) . "\n";
        $data = array();
        $data['username'] = $user1->username;
        $data['courseshortname'] = '   ' . $course2->shortname . '   ';
        $data['courseidnumber'] = '   ' . $course2->idnumber . '   ';
        $data['completiondate'] = $completiondate;
        $data['grade'] = 77;
        $content .= implode(",", $data) . "\n";

        \totara_completionimport\csv_import::import($content, $importname, $importstart);

        $importdata = $DB->get_records($importtablename, null, 'id asc');
        $import = end($importdata);

        $this->assertEmpty($import->importerrormsg,'There should be no import errors: ' . $import->importerrormsg);
        $this->assertEquals(0, $DB->count_records('dp_plan_evidence'), 'Evidence should not be created');
        $this->assertEquals($course2->id, $import->courseid, 'The course was not matched');

        //
        // Test evidence is created when course is not found.
        //

        $content = implode(",", $fields) . "\n";
        $data = array();
        $data['username'] = $user1->username;
        $data['courseshortname'] = 'course3';
        $data['courseidnumber'] = 'course3';
        $data['completiondate'] = $completiondate;
        $data['grade'] = 77;
        $content .= implode(",", $data) . "\n";

        \totara_completionimport\csv_import::import($content, $importname, $importstart);

        $importdata = $DB->get_records($importtablename, null, 'id asc');
        $import = end($importdata);

        $this->assertEmpty($import->importerrormsg,'There should be no import errors: ' . $import->importerrormsg);
        $this->assertEquals(1, $DB->count_records('dp_plan_evidence'), 'Evidence should be created');
        $this->assertEquals(null, $import->courseid, 'A courseid should not be set');
    }
}
