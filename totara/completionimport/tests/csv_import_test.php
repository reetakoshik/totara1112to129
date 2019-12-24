<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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

/**
 * Class totara_completionimport_csv_import_testcase
 *
 * Tests methods within the \totara_completionimport\csv_import class.
 *
 * @group totara_completionimport
 */
class totara_completionimport_csv_import_testcase extends advanced_testcase {

    private $coursecolumns = array(
        'username',
        'courseshortname',
        'courseidnumber',
        'completiondate',
        'grade'
    );

    private $certificationcolumns = array(
        'username',
        'certificationshortname',
        'certificationidnumber',
        'completiondate',
        'duedate',
    );

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Allows execution of a private or protected static method.
     *
     * @param string $classname
     * @param string $methodname
     * @param array $arguments
     * @return mixed the return value of the static method.
     */
    private function execute_restricted_static_method($classname, $methodname, $arguments = array()) {
        $reflection = new \ReflectionClass($classname);
        $method = $reflection->getMethod($methodname);
        $method->setAccessible(true);

        return $method->invokeArgs(null, $arguments);
    }

    public function data_provider_importname_and_columns() {
        return array(
            array('course', $this->coursecolumns),
            array('certification', $this->certificationcolumns)
        );
    }

    // For testing the import() method of this class, much of the full testing is done in other files
    // where we test the process overall.
    // See course_upload_test.php, importcertification_test.php and importcourse_test.php.

    /**
     * Tests the import() method when an empty string is supplied for content.
     */
    public function test_import_with_empty_content() {
        global $DB;

        $importname = 'course';
        $importime = time();
        $content = '';
        $errors = \totara_completionimport\csv_import::import($content, $importname, $importime);

        // We should get the errors returned by validation.
        $this->assertContains("Missing required column 'username'", $errors);
        $this->assertContains("Missing required column 'courseshortname'", $errors);
        $this->assertContains("Missing required column 'courseidnumber'", $errors);
        $this->assertContains("Missing required column 'completiondate'", $errors);
        $this->assertContains("Missing required column 'grade'", $errors);
        $this->assertCount(5, $errors);

        // There also should be nothing in the import tables.
        $courseimportrecords = $DB->get_records('totara_compl_import_course');
        $this->assertEmpty($courseimportrecords);

        $certimportrecords = $DB->get_records('totara_compl_import_cert');
        $this->assertEmpty($certimportrecords);
    }

    /**
     * Tests the validate_columns() method with standard required columns. No columns missing and no customfields.
     *
     * @param string $importname - course or certification.
     * @param array $columns - array of all standard column names for this importname.
     *
     * @dataProvider data_provider_importname_and_columns
     */
    public function test_validate_columns_for_course_with_standard_only($importname, $columns) {
        $errors = $this->execute_restricted_static_method('\totara_completionimport\csv_import', 'validate_columns',
            array($columns, $importname));

        $this->assertEmpty($errors);
    }

    /**
     * Tests the validate_columns() method with some required columns missing.
     *
     * @param string $importname - course or certification.
     * @param array $columns - array of all standard column names for this importname.
     *
     * @dataProvider data_provider_importname_and_columns
     */
    public function test_validate_columns_with_missing_columns($importname, $columns) {
        // Both types have the column completiondate. We'll remove it.
        $key = array_search('completiondate', $columns);
        unset($columns[$key]);
        // Let's also remove a column with a name specific to the import type.
        $shortnamecolumn = $importname . 'shortname';
        $key = array_search($shortnamecolumn, $columns);
        unset($columns[$key]);

        $errors = $this->execute_restricted_static_method('\totara_completionimport\csv_import', 'validate_columns',
            array($columns, $importname));

        $this->assertContains("Missing required column 'completiondate'", $errors);
        $this->assertContains("Missing required column '$shortnamecolumn'", $errors);
        $this->assertCount(2, $errors);
    }

    /**
     * Tests the validate_columns() method with extra unknown columns.
     *
     * @param string $importname - course or certification.
     * @param array $columns - array of all standard column names for this importname.
     *
     * @dataProvider data_provider_importname_and_columns
     */
    public function test_validate_columns_with_unknown_columns($importname, $columns) {
        $columns[] = 'notarealcolumn';
        $columns[] = 'iwishiwasacolumn';

        $errors = $this->execute_restricted_static_method('\totara_completionimport\csv_import', 'validate_columns',
            array($columns, $importname));

        $this->assertContains("Unknown column 'notarealcolumn'", $errors);
        $this->assertContains("Unknown column 'iwishiwasacolumn'", $errors);
        $this->assertCount(2, $errors);
    }

    /**
     * Tests the validate_columns() method with extra columns matching evidence custom fields.
     *
     * @param string $importname - course or certification.
     * @param array $columns - array of all standard column names for this importname.
     *
     * @dataProvider data_provider_importname_and_columns
     */
    public function test_validate_columns_with_evidencefields($importname, $columns) {
        /** @var totara_customfield_generator $customfieldgenerator */
        $customfieldgenerator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');

        // Create 3 text customfields.
        $customfieldgenerator->create_text('dp_plan_evidence', array('text1', 'text2', 'text3'));

        $columns[] = 'customfield_text1';
        // We're leaving out text2 and this should not produce an error as these are optional.
        $columns[] = 'customfield_text3';
        $errors = $this->execute_restricted_static_method('\totara_completionimport\csv_import', 'validate_columns',
            array($columns, $importname));
        $this->assertEmpty($errors);
    }

    /**
     * Tests the new_row_object() method with data sufficient to generate a record with no errors.
     *
     * Note that this method assumes columns were already validated, meaning that it does not check
     * the check the column names are correct.
     */
    public function test_new_row_object_with_no_errors() {
        $this->setAdminUser();

        // Data for input into the method.

        // Just defining an arbitrary completion date for testing against.
        $completiondate = 1484442000;

        // Important to note that this does not validate the names of columns as that should have already been
        // done. However the completiondate column is referred to so must be included.
        $allcolumns = ['column1', 'column2', 'completiondate'];
        $item = ['value1', 'value2', $completiondate];
        $rownumber = 3;
        $importtime = time();
        $csvdateformat = 'Y-m-d';

        $result = $this->execute_restricted_static_method('\totara_completionimport\csv_import', 'new_row_object',
            array($item, $rownumber, $allcolumns, $importtime, $csvdateformat));

        // Confirm no errors.
        $this->assertEquals(0, $result->importerror);
        $this->assertEquals('', $result->importerrormsg);

        // The supplied columns.
        $this->assertEquals('value1', $result->column1);
        $this->assertEquals('value2', $result->column2);
        $this->assertFalse(isset($result->customfields));

        $this->assertEquals($completiondate, $result->completiondate);
        // In the interests of avoiding timezone issues, we'll parse the expected output in code.
        $expectedparsedcompletiondate = totara_date_parse_from_format('Y-m-d', $completiondate);
        $this->assertEquals($expectedparsedcompletiondate, $result->completiondateparsed);

        $this->assertEquals($importtime, $result->timecreated);
        $this->assertEquals(0, $result->timeupdated);
        $admin = get_admin();
        $this->assertEquals($admin->id, $result->importuserid);
        $this->assertEquals(3, $result->rownumber);

        // Let's do this one more time, but changing user and date format to make sure these
        // aren't stuck on any defaults.
        // And we'll use more realistic column names this time.
        $allcolumns = ['username', 'courseshortname', 'courseidnumber', 'completiondate', 'grade'];
        $item = ['user1', 'course1', 'id1', $completiondate, 50];
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $csvdateformat = 'd-m-Y';

        $result = $this->execute_restricted_static_method('\totara_completionimport\csv_import', 'new_row_object',
            array($item, $rownumber, $allcolumns, $importtime, $csvdateformat));

        $this->assertEquals($user1->id, $result->importuserid);
        $expectedparsedcompletiondate = totara_date_parse_from_format('d-m-Y', $completiondate);
        $this->assertEquals($expectedparsedcompletiondate, $result->completiondateparsed);

        $this->assertEquals(0, $result->importerror);
        $this->assertEquals('', $result->importerrormsg);
        $this->assertEquals('user1', $result->username);
        $this->assertEquals('course1', $result->courseshortname);
        $this->assertEquals('id1', $result->courseidnumber);
        $this->assertEquals($completiondate, $result->completiondate);
        $this->assertEquals(50, $result->grade);
        $this->assertFalse(isset($result->customfields));

    }

    /**
     * Tests the new_row_object() method where the number of items of data is greater than
     * the number of rows, producing a 'fieldcountmismatch' error.
     */
    public function test_new_row_object_with_fieldcountmismatch() {
        $this->setAdminUser();

        // Data for input into the method.

        // Just defining an arbitrary completion date for testing against.
        $completiondate = 1484442000;

        $allcolumns = ['column1', 'column2', 'completiondate'];
        $item = ['value1', 'value2', $completiondate, 'extradata'];
        $rownumber = 3;
        $importtime = time();
        $csvdateformat = 'Y-m-d';

        $result = $this->execute_restricted_static_method('\totara_completionimport\csv_import', 'new_row_object',
            array($item, $rownumber, $allcolumns, $importtime, $csvdateformat));

        // Confirm the fieldcountmismatcherror was found.
        $this->assertEquals(1, $result->importerror);
        $this->assertEquals('fieldcountmismatch;', $result->importerrormsg);

        // The supplied columns.
        $this->assertEquals('value1', $result->column1);
        $this->assertEquals('value2', $result->column2);
        $this->assertFalse(isset($result->customfields));

        $this->assertEquals($completiondate, $result->completiondate);
        // In the interests of avoiding timezone issues, we'll parse the expected output in code.
        $expectedparsedcompletiondate = totara_date_parse_from_format('Y-m-d', $completiondate);
        $this->assertEquals($expectedparsedcompletiondate, $result->completiondateparsed);

        $this->assertEquals($importtime, $result->timecreated);
        $this->assertEquals(0, $result->timeupdated);
        $admin = get_admin();
        $this->assertEquals($admin->id, $result->importuserid);
        $this->assertEquals(3, $result->rownumber);
    }

    /**
     * Tests the new_row_object() field with customfield data.
     *
     * Also covered here is how customfields must be passed in through the $customfield
     * parameter when calling the function, otherwise that column will be treated as a standard column.
     */
    public function test_new_row_object_with_customfields() {
        $this->setAdminUser();

        // Just defining an arbitrary completion date for testing against.
        $completiondate = 1484442000;

        /** @var totara_customfield_generator $customfieldgenerator */
        $customfieldgenerator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');
        // Create 3 text customfields.
        $customfieldgenerator->create_text('dp_plan_evidence', array('text1', 'text2', 'text3'));

        // We'll pass in text2 and text3, but we're not going to be using text2.
        $customfields = array('customfield_text2', 'customfield_text3');

        // We're adding text1 into the allcolumns array, but we haven't added it into the customfields array.
        $allcolumns = ['column1', 'column2', 'completiondate', 'customfield_text1', 'customfield_text3'];
        $item = ['value1', 'value2', $completiondate, 'textvalue1', 'textvalue3'];
        $rownumber = 3;
        $importtime = time();
        $csvdateformat = 'Y-m-d';

        $result = $this->execute_restricted_static_method('\totara_completionimport\csv_import', 'new_row_object',
            array($item, $rownumber, $allcolumns, $importtime, $csvdateformat, $customfields));

        // Confirm the fieldcountmismatcherror was found.
        $this->assertEquals(0, $result->importerror);
        $this->assertEquals('', $result->importerrormsg);

        // The supplied columns.
        $this->assertEquals('value1', $result->column1);
        $this->assertEquals('value2', $result->column2);
        // Probably not what was wanted, but text1 should have been added to the customfields array.
        $this->assertTrue(isset($result->customfield_text1));
        // The next 2 aren't set as they were in the customfields array as they should have been.
        // Meaning values from them are serialised and added to $result->customfields instead.
        $this->assertFalse(isset($result->customfield_text2));
        $this->assertFalse(isset($result->customfield_text3));
        $this->assertEquals('a:1:{s:17:"customfield_text3";s:10:"textvalue3";}', $result->customfields);

        $this->assertEquals($completiondate, $result->completiondate);
        // In the interests of avoiding timezone issues, we'll parse the expected output in code.
        $expectedparsedcompletiondate = totara_date_parse_from_format('Y-m-d', $completiondate);
        $this->assertEquals($expectedparsedcompletiondate, $result->completiondateparsed);

        $this->assertEquals($importtime, $result->timecreated);
        $this->assertEquals(0, $result->timeupdated);
        $admin = get_admin();
        $this->assertEquals($admin->id, $result->importuserid);
        $this->assertEquals(3, $result->rownumber);
    }
}