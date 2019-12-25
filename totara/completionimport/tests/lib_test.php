<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @package    totara_completionimport
 * @author     Brendan Cox <brendan.cox@totaralearning.com>
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/completionimport/lib.php');

/**
 * Class totara_completionimport_lib_testcase.
 *
 * Tests functions within the totara/completionimport/lib.php file.
 *
 * @group totara_completionimport
 */
class totara_completionimport_lib_testcase extends advanced_testcase {

    /**
     * DataProvider for test_import_data_checks_date_formats
     *
     * Each set of data supplied to the test will have
     * - a date format.
     * - a set of completion dates in the various formats, plus some extras that will always fail.
     * - a set of expected results (true or false) for each completion date.
     *
     * @return array
     */
    public function data_provider_date_formats() {
        $csvdateformats = array(
            'Y-m-d', 'Y/m/d', 'Y.m.d', 'Y m d',
            'y-m-d', 'y/m/d', 'y.m.d', 'y m d',
            'd-m-Y', 'd/m/Y', 'd.m.Y', 'd m Y',
            'd-m-y', 'd/m/y', 'd.m.y', 'd m y',
            'm-d-Y', 'm/d/Y', 'm.d.Y', 'm d Y',
            'm-d-y', 'm/d/y', 'm.d.y', 'm d y',
        );

        // Users will be created in test_import_data_checks_date_formats
        // that will each have one of the completion dates.
        $completiondates_canbevalid = array(
            'Y-m-d' => '1998-08-30',
            'Y/m/d' => '1998/08/30',
            'Y.m.d' => '1998.08.30',
            'Y m d' => '1998 08 30',
            'y-m-d' => '98-08-30',
            'y/m/d' => '98/08/30',
            'y.m.d' => '98.08.30',
            'y m d' => '98 08 30',
            'd-m-Y' => '30-08-1998',
            'd/m/Y' => '30/08/1998',
            'd.m.Y' => '30.08.1998',
            'd m Y' => '30 08 1998',
            'd-m-y' => '30-08-98',
            'd/m/y' => '30/08/98',
            'd.m.y' => '30.08.98',
            'd m y' => '30 08 98',
            'm-d-Y' => '08-30-1998',
            'm/d/Y' => '08/30/1998',
            'm.d.Y' => '08.30.1998',
            'm d Y' => '08 30 1998',
            'm-d-y' => '08-30-98',
            'm/d/y' => '08/30/98',
            'm.d.y' => '08.30.98',
            'm d y' => '08 30 98',
            'd/m/Y - singledigit' => '30/8/1998',
            'd-m-y - singledigit' => '30-8-98',
            'm d Y - singledigit' => '8 30 1998',
            'd.m.y or m.d.y - singledigits' => '8.6.98',
            'd/m/Y - leapyear' => '29/02/2016'
        );

        $completiondates_nevervalid = array(
            'nonsensicalnumbers' => '52.86.6452',
            'letters' => 'one day',
            'empty' => '',
            'd/m/Y - non-leapyear' => '29/02/2015',
            'd.m.y - 32 day month' => '32/05/2016',
            'Y-m-d - 13 month year' => '2014/13/15'
        );

        // Create an array with the same keys as above, and the expected outcome for each.
        // By default, this is false. When we put the data sets together, we'll overwrite the expected
        // result for valid formats with true.
        $expectedresults = array();
        foreach($completiondates_canbevalid as $key => $completiondate) {
            $expectedresults[$key] = false;
        }
        foreach($completiondates_nevervalid as $key => $completiondate) {
            $expectedresults[$key] = false;
        }

        // Build the data array
        $data = array();
        foreach($csvdateformats as $csvdateformat) {
            $thisexpectedresults = $expectedresults;
            // Below, we set the expected result to true if the format of the corresponding completion date
            // will be valid.
            foreach($completiondates_canbevalid as $key => $completiondate) {
                if (strpos($key, $csvdateformat) !== false) {
                    // If the format of the $completiondate is exactly the same
                    // as $csvdateformat, the result should return true.
                    $thisexpectedresults[$key] = true;
                }
            }

            $completiondates = array_merge($completiondates_canbevalid, $completiondates_nevervalid);

            // Add the data set.
            $data[] = array(
                $csvdateformat,
                $completiondates,
                $thisexpectedresults
            );
        }
        return $data;
    }

    /**
     * Tests that totara_completionimport_validate_date returns the correct values for various dates
     * and formats.
     *
     * @param string $csvdateformat - the format for the completion import, e.g. 'Y-m-d'.
     * @param array $completiondates - contains completion dates in different formats. Keys describe the format.
     * @param array $expectedresults - has the same keys as $completiondates, this contains the expected result for
     * each one.
     *
     * @dataProvider data_provider_date_formats
     */
    public function test_totara_completionimport_validate_date($csvdateformat, $completiondates, $expectedresults) {
        $this->resetAfterTest(true);

        $this->assertEquals(count($completiondates), count($expectedresults));

        foreach($completiondates as $key => $completiondate) {
            $result = totara_completionimport_validate_date($csvdateformat, $completiondate);
            $this->assertEquals($expectedresults[$key], $result, 'Failed for completion date with format: ' . $key);
        }
    }

    /**
     * Tests move_sourcefile().
     *
     * If the config setting 'completionimportdir' has not been set, the function should return
     * false as it we don't want to move files without it.
     */
    public function test_move_sourcefile_noconfig() {
        $this->resetAfterTest(true);
        global $CFG;

        // Config setting should be empty by default.
        $this->assertTrue(empty($CFG->completionimportdir));

        $dirpath = $CFG->dirroot . '/totara/completionimport/tests/fixtures/';
        $filename = $dirpath . 'course_single_upload.csv';

        // The temp file name shouldn't be used as the function will return true before using it in
        // a php unit test. But we'll set it somewhere safe in case that doesn't happen.
        $tempfilename = $dirpath . 'new_course_single_upload.csv';

        ob_start();
        $result = move_sourcefile($filename, $tempfilename);
        $this->assertFalse($result);
        $output = ob_get_clean();
        $this->assertContains('Additional configuration settings are required', $output);

        // No temp file should have been created whether this was a unit test or not.
        $this->assertFalse(is_readable($tempfilename));
    }

    /**
     * Tests move_sourcefile().
     *
     * The config setting is given a value. The file supplied to the function we're testing
     * is in the directory given in the config setting. The function should return true.
     */
    public function test_move_sourcefile_configset_valid_no_subdir() {
        $this->resetAfterTest(true);
        global $CFG;

        $dirpath = $CFG->dirroot . '/totara/completionimport/tests/fixtures/';

        $CFG->completionimportdir = $dirpath;

        $filename = $dirpath . 'course_single_upload.csv';

        // The temp file name shouldn't be used as the function will return true before using it in
        // a php unit test. But we'll set it somewhere safe in case that doesn't happen.
        $tempfilename = $dirpath . 'new_course_single_upload.csv';

        ob_start();
        $result = move_sourcefile($filename, $tempfilename);
        $this->assertTrue($result);
        $output = ob_get_clean();
        $this->assertEmpty($output);

        // For a live site, the temp file would have been created. But this should not happen for a unit test.
        $this->assertFalse(is_readable($tempfilename));
    }

    /**
     * Tests move_sourcefile().
     *
     * The config setting is given a value. The file supplied to the function we're testing
     * is in a subdirectory of the what's in the config setting. The function should return true.
     */
    public function test_move_sourcefile_configset_valid_in_subdir() {
        $this->resetAfterTest(true);
        global $CFG;

        $dirpath = $CFG->dirroot . '/totara/completionimport/tests/fixtures/';

        $CFG->completionimportdir = $dirpath;

        $filename = $dirpath . 'subdir/course_single_upload.csv';

        // The temp file name shouldn't be used as the function will return true before using it in
        // a php unit test. But we'll set it somewhere safe in case that doesn't happen.
        $tempfilename = $dirpath . 'new_course_single_upload.csv';

        ob_start();
        $result = move_sourcefile($filename, $tempfilename);
        $this->assertTrue($result);
        $output = ob_get_clean();
        $this->assertEmpty($output);

        // For a live site, the temp file would have been created. But this should not happen for a unit test.
        $this->assertFalse(is_readable($tempfilename));
    }

    /**
     * Tests move_sourcefile().
     *
     * The config setting is set. But the filename supplied to the function we're
     * testing has a different path.
     */
    public function test_move_sourcefile_configset_invalid() {
        $this->resetAfterTest(true);
        global $CFG;

        $dirpath = $CFG->dirroot . '/totara/completionimport/tests/fixtures/';

        $CFG->completionimportdir = $dirpath;

        $filename = '/totara/completionimport/tests/behat/course_single_upload.csv';

        // The temp file name shouldn't be used as the function will return true before using it in
        // a php unit test. But we'll set it somewhere safe in case that doesn't happen.
        $tempfilename = $dirpath . 'new_course_single_upload.csv';

        ob_start();
        $result = move_sourcefile($filename, $tempfilename);
        $this->assertFalse($result);
        $output = ob_get_clean();
        $this->assertContains('The source file name must include the full path to the file and begin with ', $output);

        // No temp file should have been created whether this was a unit test or not.
        $this->assertFalse(is_readable($tempfilename));
    }

    public function test_totara_completionimport_resolve_course_references() {
        global $DB, $USER;

        $this->resetAfterTest(true);

        $this->setAdminUser();
        $importtime = time();

        $base = [
            'importuserid' => $USER->id,
            'timecreated' => $importtime,
            'importerror' => 0,
            'timeupdated' => 0,
            'importevidence' => 0,
            'rownumber' => '1',
        ];

        $DB->insert_records(
            'course',
            [
                ['idnumber' => 'test1', 'shortname' => 'Test one', 'fullname' => 'Test course one'],
                ['idnumber' => 'test2', 'shortname' => 'Test two', 'fullname' => 'Test course two'],
                ['idnumber' => '', 'shortname' => 'Test three', 'fullname' => 'Test course three'],
            ]
        );

        $DB->insert_records(
            'totara_compl_import_course',
            [
                // Three users completing the same course.
                $base  + ['courseidnumber' => 'test1', 'courseshortname' => 'Test one', 'username' => 'user1', 'customfields' => 'Expected: test1', 'grade' => 'a#1'],
                $base  + ['courseidnumber' => 'test1', 'courseshortname' => 'Test one', 'username' => 'user2', 'customfields' => 'Expected: test1', 'grade' => 'a#2'],
                $base  + ['courseidnumber' => 'test1', 'courseshortname' => 'Test one', 'username' => 'user3', 'customfields' => 'Expected: test1', 'grade' => 'a#3'],

                // The same user completing the same course multiple times.
                $base  + ['courseidnumber' => 'test2', 'courseshortname' => 'Test two', 'username' => 'user1', 'customfields' => 'Expected: test2', 'grade' => 'b#1'],
                $base  + ['courseidnumber' => 'test2', 'courseshortname' => 'Test two', 'username' => 'user1', 'customfields' => 'Expected: test2', 'grade' => 'b#2'],
                $base  + ['courseidnumber' => 'test2', 'courseshortname' => 'Test two', 'username' => 'user1', 'customfields' => 'Expected: test2', 'grade' => 'b#3'],

                // Matching on shortname only.
                $base  + ['courseidnumber' => '', 'courseshortname' => 'Test two', 'username' => 'user2', 'customfields' => 'Expected: test2', 'grade' => 'c#1'],
                $base  + ['courseidnumber' => null, 'courseshortname' => 'Test two', 'username' => 'user2', 'customfields' => 'Expected: test2', 'grade' => 'c#2'],
                $base  + ['courseidnumber' => '', 'courseshortname' => 'Test Three', 'username' => 'user2', 'customfields' => 'Expected: ', 'grade' => 'c#3'],
                $base  + ['courseidnumber' => null, 'courseshortname' => 'Test Three', 'username' => 'user2', 'customfields' => 'Expected: ', 'grade' => 'c#3'],

                // Matching shortname, but with conflicting idnumber.
                $base  + ['courseidnumber' => 'test1', 'courseshortname' => 'Test two', 'username' => 'user2', 'customfields' => 'No match', 'grade' => 'd#1'],
                $base  + ['courseidnumber' => 'test2', 'courseshortname' => 'Test one', 'username' => 'user2', 'customfields' => 'No match', 'grade' => 'd#2'],
                $base  + ['courseidnumber' => 'test2', 'courseshortname' => 'Test three', 'username' => 'user2', 'customfields' => 'Expected: ', 'grade' => 'd#3'],

                // Matching shortname, but with non-matching idnumber.
                $base  + ['courseidnumber' => 'demo1', 'courseshortname' => 'Test two', 'username' => 'user2', 'customfields' => 'No match', 'grade' => 'e#1'],
                $base  + ['courseidnumber' => 'demo2', 'courseshortname' => 'Test one', 'username' => 'user2', 'customfields' => 'No match', 'grade' => 'e#2'],
                $base  + ['courseidnumber' => 'demo3', 'courseshortname' => 'Test three', 'username' => 'user2', 'customfields' => 'No match', 'grade' => 'e#3'],

                // Matching idnumber, with empty shortname.
                $base  + ['courseidnumber' => 'test1', 'courseshortname' => '', 'username' => 'user2', 'customfields' => 'Expected: test1', 'grade' => 'f#1'],
                $base  + ['courseidnumber' => 'test2', 'courseshortname' => null, 'username' => 'user2', 'customfields' => 'Expected: test2', 'grade' => 'f#2'],

                // Matching idnumber with non-matching shortname
                $base  + ['courseidnumber' => 'test1', 'courseshortname' => 'Demo one', 'username' => 'user2', 'customfields' => 'No match', 'grade' => 'g#1'],
                $base  + ['courseidnumber' => 'test2', 'courseshortname' => 'Demo two', 'username' => 'user2', 'customfields' => 'No match', 'grade' => 'g#2'],

                // Matching idnumber with conflicting shortname
                $base  + ['courseidnumber' => 'test1', 'courseshortname' => 'Test two', 'username' => 'user2', 'customfields' => 'No match', 'grade' => 'h#1'],
                $base  + ['courseidnumber' => 'test2', 'courseshortname' => 'Test one', 'username' => 'user2', 'customfields' => 'No match', 'grade' => 'h#2'],

                // No matching at all
                $base  + ['courseidnumber' => 'demo1', 'courseshortname' => 'Demo two', 'username' => 'user2', 'customfields' => 'No match', 'grade' => 'i#1'],
                $base  + ['courseidnumber' => 'demo2', 'courseshortname' => 'Demo two', 'username' => 'user2', 'customfields' => 'No match', 'grade' => 'i#2'],
                $base  + ['courseidnumber' => null, 'courseshortname' => 'Demo two', 'username' => 'user2', 'customfields' => 'No match', 'grade' => 'i#3'],
                $base  + ['courseidnumber' => 'demo2', 'courseshortname' => null, 'username' => 'user2', 'customfields' => 'No match', 'grade' => 'i#4'],
                $base  + ['courseidnumber' => null, 'courseshortname' => null, 'username' => 'user2', 'customfields' => 'No match', 'grade' => 'i#5'],
            ]
        );

        self::assertSame(27, $DB->count_records('totara_compl_import_course'));
        self::assertSame(27, $DB->count_records_select('totara_compl_import_course', 'courseid IS NULL'));

        totara_completionimport_resolve_references('course', $importtime);

        list($timewhere, $params) = get_importsqlwhere($importtime);
        $sql = "SELECT i.id, i.courseidnumber, i.courseshortname, i.courseid, c.idnumber AS actualcourseidnumber, i.customfields, i.grade
                  FROM {totara_compl_import_course} i
             LEFT JOIN {course} c ON c.id = i.courseid
                       {$timewhere}";

        $records = $DB->get_records_sql($sql, $params);
        $failures = [];
        foreach ($records as $record) {
            if ($record->customfields === 'No match') {
                if (!empty($record->courseid)) {
                    $failures[] = $record;
                }
            } else {
                $expected = substr($record->customfields, strlen('Expected: '));
                $actual = $record->actualcourseidnumber;
                if ($expected != $actual) {
                    $failures[] = $record;
                }
            }
        }
        self::assertEmpty($failures, "The following records didn't contain the expected matches: \n" . print_r($failures, true));

        self::assertSame(27, $DB->count_records('totara_compl_import_course'));
        self::assertSame(17, $DB->count_records_select('totara_compl_import_course', 'courseid IS NULL'));
    }

    public function test_totara_completionimport_resolve_certification_references() {
        global $DB, $USER;

        $this->resetAfterTest(true);

        $this->setAdminUser();
        $importtime = time();

        $base = [
            'importuserid' => $USER->id,
            'timecreated' => $importtime,
            'importerror' => 0,
            'timeupdated' => 0,
            'importevidence' => 0,
            'rownumber' => '1',
        ];

        $DB->insert_records(
            'prog',
            [
                ['certifid' => 1, 'idnumber' => 'test1', 'shortname' => 'Test one', 'fullname' => 'Test certification one'],
                ['certifid' => 2, 'idnumber' => 'test2', 'shortname' => 'Test two', 'fullname' => 'Test certification two'],
                ['certifid' => 3, 'idnumber' => '', 'shortname' => 'Test three', 'fullname' => 'Test certification three'],
                ['certifid' => null, 'idnumber' => 'test4', 'shortname' => 'Test four', 'fullname' => 'Test program four'],
            ]
        );

        /** @var pgsql_native_moodle_database $DB */
        $DB->insert_records(
            'totara_compl_import_cert',
            [
                // Three users completing the same certification.
                $base  + ['certificationidnumber' => 'test1', 'certificationshortname' => 'Test one', 'username' => 'user1', 'customfields' => 'Expected: test1', 'completiondate' => 'a#1'],
                $base  + ['certificationidnumber' => 'test1', 'certificationshortname' => 'Test one', 'username' => 'user2', 'customfields' => 'Expected: test1', 'completiondate' => 'a#2'],
                $base  + ['certificationidnumber' => 'test1', 'certificationshortname' => 'Test one', 'username' => 'user3', 'customfields' => 'Expected: test1', 'completiondate' => 'a#3'],

                // The same user completing the same certification multiple times.
                $base  + ['certificationidnumber' => 'test2', 'certificationshortname' => 'Test two', 'username' => 'user1', 'customfields' => 'Expected: test2', 'completiondate' => 'b#1'],
                $base  + ['certificationidnumber' => 'test2', 'certificationshortname' => 'Test two', 'username' => 'user1', 'customfields' => 'Expected: test2', 'completiondate' => 'b#2'],
                $base  + ['certificationidnumber' => 'test2', 'certificationshortname' => 'Test two', 'username' => 'user1', 'customfields' => 'Expected: test2', 'completiondate' => 'b#3'],

                // Matching on shortname only.
                $base  + ['certificationidnumber' => '', 'certificationshortname' => 'Test two', 'username' => 'user2', 'customfields' => 'Expected: test2', 'completiondate' => 'c#1'],
                $base  + ['certificationidnumber' => null, 'certificationshortname' => 'Test two', 'username' => 'user2', 'customfields' => 'Expected: test2', 'completiondate' => 'c#2'],
                $base  + ['certificationidnumber' => '', 'certificationshortname' => 'Test three', 'username' => 'user2', 'customfields' => 'Expected: ', 'completiondate' => 'c#3'],
                $base  + ['certificationidnumber' => null, 'certificationshortname' => 'Test three', 'username' => 'user2', 'customfields' => 'Expected: ', 'completiondate' => 'c#4'],

                // Matching shortname, but with conflicting idnumber.
                $base  + ['certificationidnumber' => 'test1', 'certificationshortname' => 'Test two', 'username' => 'user2', 'customfields' => 'No match', 'completiondate' => 'd#1'],
                $base  + ['certificationidnumber' => 'test2', 'certificationshortname' => 'Test one', 'username' => 'user2', 'customfields' => 'No match', 'completiondate' => 'd#2'],
                $base  + ['certificationidnumber' => 'test2', 'certificationshortname' => 'Test three', 'username' => 'user2', 'customfields' => 'No match', 'completiondate' => 'd#3'],

                // Matching shortname, but with non-matching idnumber.
                $base  + ['certificationidnumber' => 'demo1', 'certificationshortname' => 'Test two', 'username' => 'user2', 'customfields' => 'No match', 'completiondate' => 'e#1'],
                $base  + ['certificationidnumber' => 'demo2', 'certificationshortname' => 'Test one', 'username' => 'user2', 'customfields' => 'No match', 'completiondate' => 'e#2'],
                $base  + ['certificationidnumber' => 'demo2', 'certificationshortname' => 'Test three', 'username' => 'user2', 'customfields' => 'No match', 'completiondate' => 'e#2'],

                // Matching idnumber, with empty shortname.
                $base  + ['certificationidnumber' => 'test1', 'certificationshortname' => '', 'username' => 'user2', 'customfields' => 'Expected: test1', 'completiondate' => 'f#1'],
                $base  + ['certificationidnumber' => 'test2', 'certificationshortname' => null, 'username' => 'user2', 'customfields' => 'Expected: test2', 'completiondate' => 'f#2'],

                // Matching idnumber with non-matching shortname
                $base  + ['certificationidnumber' => 'test1', 'certificationshortname' => 'Demo one', 'username' => 'user2', 'customfields' => 'No match', 'completiondate' => 'g#1'],
                $base  + ['certificationidnumber' => 'test2', 'certificationshortname' => 'Demo two', 'username' => 'user2', 'customfields' => 'No match', 'completiondate' => 'g#2'],

                // Matching idnumber with conflicting shortname
                $base  + ['certificationidnumber' => 'test1', 'certificationshortname' => 'Test two', 'username' => 'user2', 'customfields' => 'No match', 'completiondate' => 'h#1'],
                $base  + ['certificationidnumber' => 'test2', 'certificationshortname' => 'Test one', 'username' => 'user2', 'customfields' => 'No match', 'completiondate' => 'h#2'],

                // No matching at all
                $base  + ['certificationidnumber' => 'demo1', 'certificationshortname' => 'Demo two', 'username' => 'user2', 'customfields' => 'No match', 'completiondate' => 'i#1'],
                $base  + ['certificationidnumber' => 'demo2', 'certificationshortname' => 'Demo two', 'username' => 'user2', 'customfields' => 'No match', 'completiondate' => 'i#2'],
                $base  + ['certificationidnumber' => null, 'certificationshortname' => 'Demo two', 'username' => 'user2', 'customfields' => 'No match', 'completiondate' => 'i#3'],
                $base  + ['certificationidnumber' => 'demo2', 'certificationshortname' => null, 'username' => 'user2', 'customfields' => 'No match', 'completiondate' => 'i#4'],
                $base  + ['certificationidnumber' => null, 'certificationshortname' => null, 'username' => 'user2', 'customfields' => 'No match', 'completiondate' => 'i#5'],

                // Programs aren't found.
                $base  + ['certificationidnumber' => 'test4', 'certificationshortname' => 'Test four', 'username' => 'user1', 'customfields' => 'No match', 'completiondate' => 'j#1'],
            ]
        );

        self::assertSame(28, $DB->count_records('totara_compl_import_cert'));
        self::assertSame(28, $DB->count_records_select('totara_compl_import_cert', 'certificationid IS NULL'));

        totara_completionimport_resolve_references('certification', $importtime);

        list($timewhere, $params) = get_importsqlwhere($importtime);
        $sql = "SELECT i.id, i.certificationidnumber, i.certificationshortname, i.certificationid, p.idnumber AS actualcertificationidnumber, i.customfields, i.completiondate
                  FROM {totara_compl_import_cert} i
             LEFT JOIN {prog} p ON p.id = i.certificationid
                       {$timewhere}";

        $records = $DB->get_records_sql($sql, $params);
        $failures = [];
        foreach ($records as $record) {
            if ($record->customfields === 'No match') {
                if (!empty($record->certificationid)) {
                    $failures[] = $record;
                }
            } else {
                $expected = substr($record->customfields, strlen('Expected: '));
                $actual = $record->actualcertificationidnumber;
                if ($expected != $actual) {
                    $failures[] = $record;
                }
            }
        }
        self::assertEmpty($failures, "The following records didn't contain the expected matches: \n" . print_r($failures, true));

        self::assertSame(28, $DB->count_records('totara_compl_import_cert'));
        self::assertSame(16, $DB->count_records_select('totara_compl_import_cert', 'certificationid IS NULL'));
    }
}
