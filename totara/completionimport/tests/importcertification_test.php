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
 * Tests importing generated from a csv file
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit importcertification_testcase totara/completionimport/tests/importcertification_test.php
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
require_once($CFG->dirroot . '/totara/certification/lib.php');
require_once($CFG->dirroot . '/totara/cohort/lib.php');
require_once($CFG->libdir  . '/csvlib.class.php');
require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');

/**
 * Class totara_completionimport_importcertification_testcase
 *
 * @group totara_completionimport
 */
class totara_completionimport_importcertification_testcase extends reportcache_advanced_testcase {

    const COUNT_USERS = 11;
    const COUNT_CERTIFICATIONS = 11;
    const COUNT_CSV_ROWS = 100; // Must be less than user * certification counts.

    // Used in import action tests below.
    private $users;
    private $program;
    private $cohort;
    private $usersincohort;
    private $progdata;

    private $csvdateformat;
    private $csvdelimiter;
    private $csvseparator;

    private $futurecompletiondate;
    private $futureduedate;
    private $pastcompletiondate;
    private $pastduedate;
    private $farpastcompletiondate;
    private $farpastduedate;

    private $initialcompletiondate;
    private $initialexpirydate;
    private $filename;

    protected function tearDown() {
        $this->users = null;
        $this->program = null;
        $this->cohort = null;
        $this->usersincohort = null;
        $this->progdata = null;
        $this->csvdateformat = null;
        $this->csvdelimiter = null;
        $this->csvseparator = null;
        $this->futurecompletiondate = null;
        $this->futureduedate = null;
        $this->pastcompletiondate = null;
        $this->pastduedate = null;
        $this->farpastcompletiondate = null;
        $this->farpastduedate = null;
        $this->initialcompletiondate = null;
        $this->initialexpirydate = null;
        $this->filename = null;
        parent::tearDown();
    }

    public function test_import() {
        global $DB, $CFG;

        set_config('enablecompletion', 1);
        $this->resetAfterTest(true);

        $importname = 'certification';
        $pluginname = 'totara_completionimport_' . $importname;
        $csvdateformat = get_default_config($pluginname, 'csvdateformat', TCI_CSV_DATE_FORMAT);

        $this->setAdminUser();

        $generatorstart = time();

        // Create some programs.
        $this->assertEquals(0, $DB->count_records('prog'), "Programs table isn't empty");
        for ($i = 1; $i <= self::COUNT_CERTIFICATIONS; $i++) {
            $certifications[$i] = $this->getDataGenerator()->create_certification(array('prog_idnumber' => 'ID' . $i));
        }
        $this->assertEquals(self::COUNT_CERTIFICATIONS, $DB->count_records('prog'),
            'Record count mismatch in program table');
        $this->assertEquals(self::COUNT_CERTIFICATIONS, $DB->count_records('certif'),
            'Record count mismatch for certif');

        // Create users.
        $this->assertEquals(2, $DB->count_records('user')); // Guest + Admin.
        for ($i = 1; $i <= self::COUNT_USERS; $i++) {
            $this->getDataGenerator()->create_user();
        }
        $this->assertEquals(self::COUNT_USERS+2, $DB->count_records('user'),
            'Record count mismatch for users'); // Guest + Admin + generated users.

        // Generate import data - product of user and certif tables.
        $fields = array('username', 'certificationshortname', 'certificationidnumber', 'completiondate', 'duedate');

        // Start building the content that would be returned from a csv file.
        $content = implode(",", $fields) . "\n";

        $uniqueid = $DB->sql_concat('u.username', 'p.shortname');
        $sql = "SELECT  {$uniqueid} AS uniqueid,
                        u.username,
                        p.shortname AS certificationshortname,
                        p.idnumber AS certificationidnumber,
                        p.availableuntil AS duedate
                FROM    {user} u,
                        {prog} p";
        $imports = $DB->get_recordset_sql($sql, null, 0, self::COUNT_CSV_ROWS);
        if ($imports->valid()) {
            $count = 0;
            foreach ($imports as $import) {
                $data = array();
                $data['username'] = $import->username;
                $data['certificationshortname'] = $import->certificationshortname;
                $data['certificationidnumber'] = $import->certificationidnumber;
                $data['completiondate'] = date($csvdateformat, strtotime(date('Y-m-d') . ' -' . rand(1, 365) . ' days'));
                $data['duedate'] = $import->duedate;
                $content .= implode(",", $data) . "\n";
                $count++;
            }
        }
        $imports->close();
        $this->assertEquals(self::COUNT_CSV_ROWS, $count, 'Record count mismatch when creating CSV file');

        $generatorstop = time();

        $importstart = time();
        \totara_completionimport\csv_import::import($content, $importname, $importstart);
        $importstop = time();

        $importtablename = get_tablename($importname);
        $this->assertEquals(self::COUNT_CSV_ROWS, $DB->count_records($importtablename),
            'Record count mismatch in the import table ' . $importtablename);
        $this->assertEquals(0, $DB->count_records('dp_plan_evidence'),
            'There should be no evidence records');
        $this->assertEquals(self::COUNT_CSV_ROWS, $DB->count_records('certif_completion'),
            'Record count mismatch in the certif_completion table');
        $this->assertEquals(self::COUNT_CSV_ROWS, $DB->count_records('prog_completion'),
            'Record count mismatch in the prog_completion table');
        $this->assertEquals(self::COUNT_CSV_ROWS, $DB->count_records('prog_user_assignment'),
            'Record count mismatch in the prog_user_assignment table');
    }

    /**
     * Test the test_completionimport_resolve_references() function to ensure the certificationid is matched correctly from
     * the csv shortname and idnumber fields.
     */
    public function test_completionimport_resolve_references() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        set_config('enablecompletion', 1);

        // Create a certification
        $cert1 =  $this->getDataGenerator()->create_certification(array(
            'prog_shortname' => 'cert1',
            'prog_idnumber' => 'certid1'
        ));

        // Create another certification with blank spaces in the shortname and idnumber fields.
        $cert2 =  $this->getDataGenerator()->create_certification(array(
            'prog_shortname' => '   cert2   ',
            'prog_idnumber' => '   certid2   '
        ));

        // Create a user.
        $user1 = $this->getDataGenerator()->create_user();

        $importname = 'certification';
        $importtablename = get_tablename($importname);
        $pluginname = 'totara_completionimport_' . $importname;
        $csvdateformat = get_default_config($pluginname, 'csvdateformat', TCI_CSV_DATE_FORMAT);
        $completiondate = date($csvdateformat, time());
        $importstart = time();

        // Generate import data.
        $fields = array('username', 'certificationshortname', 'certificationidnumber', 'completiondate', 'duedate');

        //
        // Test completion is saved correctly.
        //

        $content = implode(",", $fields) . "\n";
        $data = array();
        $data['username'] = $user1->username;
        $data['certificationshortname'] = $cert1->shortname;
        $data['certificationidnumber'] = $cert1->idnumber;
        $data['completiondate'] = $completiondate;
        $data['duedate'] = $completiondate;
        $content .= implode(",", $data) . "\n";

        \totara_completionimport\csv_import::import($content, $importname, $importstart);

        $importdata = $DB->get_records($importtablename, null, 'id asc');
        $import = end($importdata);

        $this->assertEmpty($import->importerrormsg,'There should be no import errors: ' . $import->importerrormsg);
        $this->assertEquals(0, $DB->count_records('dp_plan_evidence'), 'Evidence should not be created');
        $this->assertEquals($cert1->id, $import->certificationid, 'The certification was not matched');

        //
        // Test completion is saved correctly when csv has empty spaces in shortname and idnumber
        //

        $content = implode(",", $fields) . "\n";
        $data = array();
        $data['username'] = $user1->username;
        $data['courseshortname'] = '   ' . $cert1->shortname . '   ';
        $data['certificationidnumber'] = '   ' . $cert1->idnumber . '   ';
        $data['completiondate'] = $completiondate;
        $data['duedate'] = $completiondate;
        $content .= implode(",", $data) . "\n";

        \totara_completionimport\csv_import::import($content, $importname, $importstart);

        $importdata = $DB->get_records($importtablename, null, 'id asc');
        $import = end($importdata);

        $this->assertEmpty($import->importerrormsg,'There should be no import errors: ' . $import->importerrormsg);
        $this->assertEquals(0, $DB->count_records('dp_plan_evidence'), 'Evidence should not be created');
        $this->assertEquals($cert1->id, $import->certificationid, 'The certification was not matched');

        //
        // Test completion is saved correctly when certification has empty spaces in shortname and idnumber.
        //

        $content = implode(",", $fields) . "\n";
        $data = array();
        $data['username'] = $user1->username;
        $data['certificationshortname'] = $cert2->shortname;
        $data['certificationidnumber'] = $cert2->idnumber;
        $data['completiondate'] = $completiondate;
        $data['duedate'] = $completiondate;
        $content .= implode(",", $data) . "\n";

        \totara_completionimport\csv_import::import($content, $importname, $importstart);

        $importdata = $DB->get_records($importtablename, null, 'id asc');
        $import = end($importdata);

        $this->assertEmpty($import->importerrormsg,'There should be no import errors: ' . $import->importerrormsg);
        $this->assertEquals(0, $DB->count_records('dp_plan_evidence'), 'Evidence should not be created');
        $this->assertEquals($cert2->id, $import->certificationid, 'The certification was not matched');

        //
        // Test completion is saved correctly when certification and csv has empty spaces in shortname and idnumber, crazy hey!
        //

        $content = implode(",", $fields) . "\n";
        $data = array();
        $data['username'] = $user1->username;
        $data['courseshortname'] = '   ' . $cert2->shortname . '   ';
        $data['certificationidnumber'] = '   ' . $cert2->idnumber . '   ';
        $data['completiondate'] = $completiondate;
        $data['duedate'] = $completiondate;
        $content .= implode(",", $data) . "\n";

        \totara_completionimport\csv_import::import($content, $importname, $importstart);

        $importdata = $DB->get_records($importtablename, null, 'id asc');
        $import = end($importdata);

        $this->assertEmpty($import->importerrormsg,'There should be no import errors: ' . $import->importerrormsg);
        $this->assertEquals(0, $DB->count_records('dp_plan_evidence'), 'Evidence should not be created');
        $this->assertEquals($cert2->id, $import->certificationid, 'The certification was not matched');

        //
        // Test evidence is created when certification is not found.
        //

        $content = implode(",", $fields) . "\n";
        $data = array();
        $data['username'] = $user1->username;
        $data['certificationshortname'] = 'cert3';
        $data['certificationidnumber'] = 'cert3';
        $data['completiondate'] = $completiondate;
        $data['duedate'] = $completiondate;
        $content .= implode(",", $data) . "\n";

        \totara_completionimport\csv_import::import($content, $importname, $importstart);

        $importdata = $DB->get_records($importtablename, null, 'id asc');
        $import = end($importdata);

        $this->assertEmpty($import->importerrormsg,'There should be no import errors: ' . $import->importerrormsg);
        $this->assertEquals(1, $DB->count_records('dp_plan_evidence'), 'Evidence should be created');
        $this->assertEquals(null, $import->certificationid, 'A certificationid should not be set');
    }

    /* Check that users are assigned to the certification with the correct assignment type.
     * When a certification is created users could be already assigned via audience, individual assignment
     * or any other assignment type. If that happens, we need to make sure we are creating the user-program
     * association correctly.
     */
    public function test_import_assignments() {
        global $DB, $CFG;

        $this->resetAfterTest(true);

        $importname = 'certification';
        $pluginname = 'totara_completionimport_' . $importname;
        $csvdateformat = get_default_config($pluginname, 'csvdateformat', TCI_CSV_DATE_FORMAT);

        $this->setAdminUser();

        // Create a certification.
        $this->assertEquals(0, $DB->count_records('prog'), "Programs table isn't empty");
        $this->assertEquals(0, $DB->count_records('certif'), "Certif table isn't empty");
        $data = array();
        $data['prog_fullname'] = 'Certification Program1 ';
        $data['prog_shortname'] = 'CP1';
        $data['prog_idnumber'] = 1;
        $data['cert_activeperiod'] = '1 year';
        $data['cert_windowperiod'] = '4 week';
        $data['cert_recertifydatetype'] = CERTIFRECERT_EXPIRY;
        $program = $this->getDataGenerator()->create_certification($data);
        $this->assertEquals(1, $DB->count_records('prog'), 'Record count mismatch in programs table');
        $this->assertEquals(1, $DB->count_records('certif'), 'Record count mismatch in certif table');

        // Create users.
        $this->assertEquals(2, $DB->count_records('user')); // Guest + Admin.
        $users = array();
        for ($i = 1; $i <= self::COUNT_USERS; $i++) {
            $users[$i] = $this->getDataGenerator()->create_user();
        }
        $this->assertEquals(self::COUNT_USERS+2, $DB->count_records('user'),
            'Record count mismatch for users'); // Guest + Admin + generated users.

        // Associate some users to an audience - (users from 1-5).
        $this->assertEquals(0, $DB->count_records('cohort'));
        $cohort = $this->getDataGenerator()->create_cohort();
        $this->assertEquals(1, $DB->count_records('cohort'));
        $usersincohort = array();
        for ($i = 1; $i <= 5; $i++) {
            cohort_add_member($cohort->id, $users[$i]->id);
            $usersincohort[] = $users[$i]->id;
        }
        $this->assertEquals(5, $DB->count_records('cohort_members', array('cohortid' => $cohort->id)));

        // Assign audience to the certification.
        $this->getDataGenerator()->assign_to_program($program->id, ASSIGNTYPE_COHORT, $cohort->id);

        // Assign some users as individual to the certification - (users: 6 and 7).
        $this->getDataGenerator()->assign_to_program($program->id, ASSIGNTYPE_INDIVIDUAL, $users[6]->id);
        $this->getDataGenerator()->assign_to_program($program->id, ASSIGNTYPE_INDIVIDUAL, $users[7]->id);

        // Assign user 8 as an individual but set completion date in the future.
        $record = array('completiontime' => '15 2'  , 'completionevent' => COMPLETION_EVENT_FIRST_LOGIN);
        $this->getDataGenerator()->assign_to_program($program->id, ASSIGNTYPE_INDIVIDUAL, $users[8]->id, $record);

        // Generate import data - product of user and certif tables.
        $fields = array('username', 'certificationshortname', 'certificationidnumber', 'completiondate', 'duedate');

        // Start building the content that would be returned from a csv file.
        $content = implode(",", $fields) . "\n";

        $uniqueid = $DB->sql_concat('u.username', 'p.shortname');
        $sql = "SELECT  {$uniqueid} AS uniqueid,
                        u.username,
                        p.shortname AS certificationshortname,
                        p.idnumber AS certificationidnumber,
                        p.availableuntil AS duedate
                FROM    {user} u,
                        {prog} p";
        $imports = $DB->get_recordset_sql($sql, null, 0, self::COUNT_CSV_ROWS);
        if ($imports->valid()) {
            $count = 0;
            foreach ($imports as $import) {
                $data = array();
                $data['username'] = $import->username;
                $data['certificationshortname'] = $import->certificationshortname;
                $data['certificationidnumber'] = $import->certificationidnumber;
                $data['completiondate'] = date($csvdateformat, strtotime(date('Y-m-d') . ' -' . rand(1, 365) . ' days'));
                $data['duedate'] = $import->duedate;
                $content .= implode(",", $data) . "\n";
                $count++;
            }
        }
        $imports->close();
        $this->assertEquals(self::COUNT_USERS+2, $count, 'Record count mismatch when creating CSV file');

        $generatorstop = time();

        $importstart = time();
        \totara_completionimport\csv_import::import($content, $importname, $importstart);
        $importstop = time();

        // Check assignments were created correctly.
        $params = array($program->id);
        list($insql, $inparams) = $DB->get_in_or_equal($usersincohort);
        $sql = "SELECT pa.assignmenttype
                FROM {prog_user_assignment} pua
                LEFT JOIN {prog_assignment} pa ON pa.id = pua.assignmentid AND pa.programid = pua.programid
                WHERE pua.programid = ? AND userid $insql";
        $params = array_merge($params, $inparams);
        $cohortassignments = $DB->get_fieldset_sql($sql, $params);
        foreach ($cohortassignments as $assignmenttype) {
            $this->assertEquals(ASSIGNTYPE_COHORT, $assignmenttype,
                'wrong assignment type assigned. The user is already assigned to the program as a cohort member');
        }

        $params = array($program->id);
        list($insql, $inparams) = $DB->get_in_or_equal(array($users[6]->id, $users[7]->id));
        $sql = "SELECT pa.assignmenttype
                FROM {prog_user_assignment} pua
                LEFT JOIN {prog_assignment} pa ON pa.id = pua.assignmentid AND pa.programid = pua.programid
                WHERE pua.programid = ? AND userid $insql";
        $params = array_merge($params, $inparams);
        $individualassignments = $DB->get_fieldset_sql($sql, $params);
        foreach ($individualassignments as $assignmenttype) {
            $this->assertEquals(ASSIGNTYPE_INDIVIDUAL, $assignmenttype,
                'wrong assignment type assigned. The user is already assigned to the program as an individual');
        }

        // Check user 8 was assigned as individual and also has records for future assignment.
        $params = array('programid' => $program->id, 'assignmenttype' => ASSIGNTYPE_INDIVIDUAL, 'assignmenttypeid' => $users[8]->id);
        $records = $DB->get_records('prog_assignment', $params);
        $this->assertEquals(1, count($records));
        $assignment = reset($records);
        $params = array('programid' => $program->id, 'userid' => $users[8]->id, 'assignmentid' => $assignment->id);
        $this->assertTrue($DB->record_exists('prog_future_user_assignment', $params));
        $this->assertTrue($DB->record_exists('prog_user_assignment', $params));

        // Check that the rest of users who don't have previous assignments were assigned as individual.
        $params = array($program->id);
        list($insql, $inparams) = $DB->get_in_or_equal(array($users[9]->id, $users[10]->id, $users[11]->id));
        $sql = "SELECT pa.assignmenttype
                FROM {prog_user_assignment} pua
                LEFT JOIN {prog_assignment} pa ON pa.id = pua.assignmentid AND pa.programid = pua.programid
                WHERE pua.programid = ? AND userid $insql";
        $params = array_merge($params, $inparams);
        $individualassignments = $DB->get_fieldset_sql($sql, $params);
        foreach ($individualassignments as $assignmenttype) {
            $this->assertEquals(ASSIGNTYPE_INDIVIDUAL, $assignmenttype,
                'wrong assignment type assigned. The user should have been assigned as an individual');
        }

        $importtablename = get_tablename($importname);
        $this->assertEquals(self::COUNT_USERS+2, $DB->count_records($importtablename),
            'Record count mismatch in the import table ' . $importtablename);
        $this->assertEquals(0, $DB->count_records('dp_plan_evidence'),
            'There should be no evidence records');
        $this->assertEquals(self::COUNT_USERS+2, $DB->count_records('certif_completion'),
            'Record count mismatch in the certif_completion table');
        $this->assertEquals(self::COUNT_USERS+2, $DB->count_records('prog_completion'),
            'Record count mismatch in the prog_completion table');
        $this->assertEquals(self::COUNT_USERS+2, $DB->count_records('prog_user_assignment'),
            'Record count mismatch in the prog_user_assignment table');
        $this->assertEquals(1, $DB->count_records('prog_future_user_assignment'),
            'Record count mismatch in the prog_future_user_assignment table');
    }

    /**
     * Creates:
     * - one cert
     * - self::COUNT_USERS users
     * - cohort
     * - first 10 users are in the cohort
     * - cohort is in the cert
     * - users 1 and 2 are newly assigned
     * - users 3 and 4 are certified
     * - users 5 and 6 are window open
     * - users 7 and 8 are expired
     * - users 9 and 10 are invalid
     * - users 11 and 12 are individual assignments newly assigned
     * - users 13 and 14 are in the cert with first login due date criteria
     * - users 15 to 20 are not assigned
     * - odd users (up to 15) have import date in past
     * - even users (up to 16) have import date in future
     * @param int $recertifydatetype CERTIFRECERT_XXX
     */
    private function setup_import_action_tests($recertifydatetype) {
        global $CFG, $DB;

        set_config('enablecompletion', 1);
        $this->resetAfterTest(true);

        $this->csvdateformat = get_default_config('totara_completionimport_certification', 'csvdateformat', TCI_CSV_DATE_FORMAT);
        $this->csvdelimiter = get_default_config('totara_completionimport_certification', 'csvdelimiter', TCI_CSV_DELIMITER);
        $this->csvseparator = get_default_config('totara_completionimport_certification', 'csvseparator', TCI_CSV_SEPARATOR);

        $this->setAdminUser();

        // Create a certification.
        $this->assertEquals(0, $DB->count_records('prog'), "Programs table isn't empty");
        $this->assertEquals(0, $DB->count_records('certif'), "Certif table isn't empty");
        $this->progdata = array();
        $this->progdata['prog_fullname'] = 'Certification Program1 ';
        $this->progdata['prog_shortname'] = 'CP1';
        $this->progdata['prog_idnumber'] = 1;
        $this->progdata['cert_activeperiod'] = '1 year';
        $this->progdata['cert_windowperiod'] = '4 week';
        $this->progdata['cert_minimumactiveperiod'] = '8 month';
        $this->progdata['cert_recertifydatetype'] = $recertifydatetype;
        $this->program = $this->getDataGenerator()->create_certification($this->progdata);
        $this->assertEquals(1, $DB->count_records('prog'), 'Record count mismatch in programs table');
        $this->assertEquals(1, $DB->count_records('certif'), 'Record count mismatch in certif table');

        // Create users.
        $this->assertEquals(2, $DB->count_records('user')); // Guest + Admin.
        $this->users = array();
        for ($i = 1; $i <= 20; $i++) {
            $this->users[$i] = $this->getDataGenerator()->create_user();
        }
        $this->assertEquals(20 + 2, $DB->count_records('user'),
            'Record count mismatch for users'); // Guest + Admin + generated users.

        // Associate some users to an audience - (users from 1-10).
        $this->assertEquals(0, $DB->count_records('cohort'));
        $this->cohort = $this->getDataGenerator()->create_cohort();
        $this->assertEquals(1, $DB->count_records('cohort'));
        $this->usersincohort = array();
        for ($i = 1; $i <= 10; $i++) {
            cohort_add_member($this->cohort->id, $this->users[$i]->id);
            $this->usersincohort[] = $this->users[$i]->id;
        }
        $this->assertEquals(10, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Assign audience to the certification.
        $this->getDataGenerator()->assign_to_program($this->program->id, ASSIGNTYPE_COHORT, $this->cohort->id);

        // Assign some users as individual to the certification - (users: 11 and 12).
        $this->getDataGenerator()->assign_to_program($this->program->id, ASSIGNTYPE_INDIVIDUAL, $this->users[11]->id);
        $this->getDataGenerator()->assign_to_program($this->program->id, ASSIGNTYPE_INDIVIDUAL, $this->users[12]->id);

        // Assign users 13 and 14 as individual but set completion date to require login.
        $record = array('completiontime' => '15 2'  , 'completionevent' => COMPLETION_EVENT_FIRST_LOGIN);
        $this->getDataGenerator()->assign_to_program($this->program->id, ASSIGNTYPE_INDIVIDUAL, $this->users[13]->id, $record);
        $record = array('completiontime' => '15 2'  , 'completionevent' => COMPLETION_EVENT_FIRST_LOGIN);
        $this->getDataGenerator()->assign_to_program($this->program->id, ASSIGNTYPE_INDIVIDUAL, $this->users[14]->id, $record);

        $this->initialcompletiondate = time();
        $this->initialexpirydate = get_timeexpires($this->initialcompletiondate, $this->progdata['cert_activeperiod']);

        // Certify users 3 and 4.
        $this->certif_set_state_certified($this->program->id, $this->users[3]->id, $this->initialcompletiondate);
        $this->certif_set_state_certified($this->program->id, $this->users[4]->id, $this->initialcompletiondate);

        // Users 5 and 6 have their recert window open.
        $this->certif_set_state_certified($this->program->id, $this->users[5]->id, $this->initialcompletiondate);
        $this->certif_set_state_windowopen($this->program->id, $this->users[5]->id);
        $this->certif_set_state_certified($this->program->id, $this->users[6]->id, $this->initialcompletiondate);
        $this->certif_set_state_windowopen($this->program->id, $this->users[6]->id);

        // Users 7 and 8 are expired.
        $this->certif_set_state_certified($this->program->id, $this->users[7]->id, $this->initialcompletiondate);
        $this->certif_set_state_windowopen($this->program->id, $this->users[7]->id);
        $this->certif_set_state_expired($this->program->id, $this->users[7]->id);
        $this->certif_set_state_certified($this->program->id, $this->users[8]->id, $this->initialcompletiondate);
        $this->certif_set_state_windowopen($this->program->id, $this->users[8]->id);
        $this->certif_set_state_expired($this->program->id, $this->users[8]->id);

        // Users 9 and 10 have errors (certified, just missing program due date).
        // These users will still be treated as certified.
        $this->certif_set_state_certified($this->program->id, $this->users[9]->id, $this->initialcompletiondate);
        $this->certif_set_state_certified($this->program->id, $this->users[10]->id, $this->initialcompletiondate);
        $DB->set_field('prog_completion', 'timedue', COMPLETION_TIME_UNKNOWN,
            array('programid' => $this->program->id, 'userid' => $this->users[9]->id, 'coursesetid' => 0));
        $DB->set_field('prog_completion', 'timedue', COMPLETION_TIME_UNKNOWN,
            array('programid' => $this->program->id, 'userid' => $this->users[10]->id, 'coursesetid' => 0));

        $this->waitForSecond();

        // Validate the test data.
        $icd = $this->initialcompletiondate;
        $ied = $this->initialexpirydate;
        $err = COMPLETION_TIME_UNKNOWN;
        $not = COMPLETION_TIME_NOT_SET;
        $expected = array( // State, due date, cert time completed, history count, history dates, errors.
            1  => array(CERTIFCOMPLETIONSTATE_ASSIGNED,   $not, 0,    0, array(),     false),
            2  => array(CERTIFCOMPLETIONSTATE_ASSIGNED,   $not, 0,    0, array(),     false),
            3  => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $ied, $icd, 0, array(),     false),
            4  => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $ied, $icd, 0, array(),     false),
            5  => array(CERTIFCOMPLETIONSTATE_WINDOWOPEN, $ied, $icd, 1, array($icd), false),
            6  => array(CERTIFCOMPLETIONSTATE_WINDOWOPEN, $ied, $icd, 1, array($icd), false),
            7  => array(CERTIFCOMPLETIONSTATE_EXPIRED,    $ied, 0,    1, array($icd), false),
            8  => array(CERTIFCOMPLETIONSTATE_EXPIRED,    $ied, 0,    1, array($icd), false),
            9  => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $err, $icd, 0, array(),     true),
            10 => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $err, $icd, 0, array(),     true),
            11 => array(CERTIFCOMPLETIONSTATE_ASSIGNED,   $not, 0,    0, array(),     false),
            12 => array(CERTIFCOMPLETIONSTATE_ASSIGNED,   $not, 0,    0, array(),     false),
            13 => array(CERTIFCOMPLETIONSTATE_ASSIGNED,   $not, 0,    0, array(),     false),
            14 => array(CERTIFCOMPLETIONSTATE_ASSIGNED,   $not, 0,    0, array(),     false),
        );

        for ($i = 1; $i <= 14; $i++) {
            list($certcompletion, $progcompletion) = certif_load_completion($this->program->id, $this->users[$i]->id);
            $state = certif_get_completion_state($certcompletion);
            $errors = certif_get_completion_errors($certcompletion, $progcompletion);

            $expectedstate = $expected[$i][0];
            $expectedduedate = $expected[$i][1];
            $expectedcerttimecompleted = $expected[$i][2];
            $expectedhistorycount = $expected[$i][3];
            $expectedhistorydates = array_flip($expected[$i][4]);
            $expectedhaserrors = $expected[$i][5];

            $this->assertEquals($expectedstate, $state, $i);

            $this->assertEquals($expectedduedate, $progcompletion->timedue, $i);

            $this->assertEquals($expectedcerttimecompleted, $certcompletion->timecompleted, $i);

            $historyrecords = $DB->get_records('certif_completion_history',
                array('certifid' => $this->program->certifid, 'userid' => $this->users[$i]->id));
            $this->assertEquals($expectedhistorycount, count($historyrecords), $i);

            foreach ($historyrecords as $historyrecord) {
                $this->assertArrayHasKey($historyrecord->timecompleted, $expectedhistorydates);
            }

            if ($expectedhaserrors) {
                $this->assertNotEmpty($errors);
            } else {
                $this->assertEmpty($errors);
            }
        }

        // Make sure there are no completion records for users 15 to 20.
        for ($i = 15; $i <= 20; $i++) {
            list($certcompletion, $progcompletion) = certif_load_completion($this->program->id, $this->users[$i]->id, false);
            $this->assertFalse($certcompletion);
            $this->assertFalse($progcompletion);
            $this->assertEquals(0, $DB->count_records('certif_completion_history', array('userid' => $this->users[$i]->id)));
        }

        // Generate import data.
        $fields = array('username', 'certificationshortname', 'certificationidnumber', 'completiondate', 'duedate');
        $csvexport = new csv_export_writer($this->csvdelimiter, $this->csvseparator);
        $csvexport->add_data($fields);

        $this->waitForSecond();

        $now = time();

        // Due dates are 10 days after completing (e.e. would have expired/been overdue in 10 days time).
        $futurecompletiondate = date($this->csvdateformat, $now + DAYSECS * 10);
        $futureduedate = date($this->csvdateformat, $now + DAYSECS * 10 + DAYSECS * 10);
        $pastcompletiondate = date($this->csvdateformat, $now - DAYSECS * 10);
        $pastduedate = date($this->csvdateformat, $now - DAYSECS * 10 + DAYSECS * 10);
        $farpastcompletiondate = date($this->csvdateformat, $now - DAYSECS * 400);
        $farpastduedate = date($this->csvdateformat, $now - DAYSECS * 400 + DAYSECS * 10);

        $this->futurecompletiondate = totara_date_parse_from_format($this->csvdateformat, $futurecompletiondate);
        $this->futureduedate = totara_date_parse_from_format($this->csvdateformat, $futureduedate);
        $this->pastcompletiondate = totara_date_parse_from_format($this->csvdateformat, $pastcompletiondate);
        $this->pastduedate = totara_date_parse_from_format($this->csvdateformat, $pastduedate);
        $this->farpastcompletiondate = totara_date_parse_from_format($this->csvdateformat, $farpastcompletiondate);
        $this->farpastduedate = totara_date_parse_from_format($this->csvdateformat, $farpastduedate);

        for ($i = 1; $i <= 16; $i++) {
            $data = array();
            $data['username'] = $this->users[$i]->username;
            $data['certificationshortname'] = $this->program->shortname;
            $data['certificationidnumber'] = $this->program->idnumber;
            $data['completiondate'] = ($i % 2) ? $pastcompletiondate : $futurecompletiondate; // Odd older, even newer.
            $data['duedate'] = ($i % 2) ? $pastduedate : $futureduedate; // Odd older, even newer.
            $csvexport->add_data($data);
            $csvexport->add_data($data); // Duplicate the data.

            // Second record, definitely older than the first (must go into history).
            $data['completiondate'] = $farpastcompletiondate;
            $data['duedate'] = ''; // Test empty due date.
            $csvexport->add_data($data);
            $csvexport->add_data($data); // Duplicate the data.
        }

        $this->assertTrue(file_exists($csvexport->path), 'The CSV export file does not exist at '.$csvexport->path);
        $this->assertTrue(is_readable($csvexport->path), 'The CSV export file is not readable at '.$csvexport->path);
        $this->assertNotEquals(0, filesize($csvexport->path), 'The CSV export file is reporting a length of 0 at '.$csvexport->path);

        // Save the csv file generated by csvexport.
        $temppath = make_temp_directory('certification');
        $this->assertNotEquals(false, $temppath);
        $this->filename = $temppath . DIRECTORY_SEPARATOR . 'import_content_csv.imp';
        $this->assertFalse(file_exists($this->filename), 'Unexpectedly encountered lingering file ' . $this->filename);

        $result = copy($csvexport->path, $this->filename);
        $this->assertTrue($result, 'Failed to copy the generated CSV file to the temporary location.');
    }

    public function data_provider_recertifydatetypes() {
        return array(array(CERTIFRECERT_COMPLETION), array(CERTIFRECERT_EXPIRY), array(CERTIFRECERT_FIXED));
    }

    /**
     * Test "Save to history" option. Make sure:
     * - current completions are not overridden (3, 4, 5, 6, 9, 10 have initial completion time)
     * - incomplete users are not completed (1, 2, 7, 8, 11 to 16 have 0 completion time)
     * - unassigned users are assigned and not completed (15 and 16 are assigned, 0 completion time)
     * - future assignments are not completed (13 and 14 are assigned, 0 completion time)
     * - all have history (2 records added to every user)
     * @dataProvider data_provider_recertifydatetypes
     */
    public function test_import_action_save_to_history($recertifydatetype) {
        global $DB;

        $this->setup_import_action_tests($recertifydatetype);

        $importtime = time();
        set_config('importactioncertification', COMPLETION_IMPORT_TO_HISTORY, 'totara_completionimport_certification');

        $handle = fopen($this->filename, 'r');
        $size = filesize($this->filename);
        $content = fread($handle, $size);
        \totara_completionimport\csv_import::import($content, 'certification', $importtime);

        // Key: i => initial, f => future, p => past, fp => far past, cd => completion, ed => expiry date, h => history.
        $icd = $this->initialcompletiondate;
        $fcd = $this->futurecompletiondate;
        $pcd = $this->pastcompletiondate;
        $fpcd = $this->farpastcompletiondate;
        $ied = $this->initialexpirydate;
        $hfped = get_timeexpires($fpcd, $this->progdata['cert_activeperiod']); // Far past import has no due date, so no switch needed.
        if ($recertifydatetype == CERTIFRECERT_COMPLETION) {
            $hped = get_timeexpires($pcd, $this->progdata['cert_activeperiod']);
            $hfed = get_timeexpires($fcd, $this->progdata['cert_activeperiod']);
        } else {
            $hped = get_timeexpires($this->pastduedate, $this->progdata['cert_activeperiod']);
            $hfed = get_timeexpires($this->futureduedate, $this->progdata['cert_activeperiod']);
        }
        $err = COMPLETION_TIME_UNKNOWN;
        $not = COMPLETION_TIME_NOT_SET;
        $expected = array( // State, cert time completed, due date, history count, history dates (timecompleted => timeexpires), errors.
            1  => array(CERTIFCOMPLETIONSTATE_ASSIGNED,   0,    $not, 2, array($fpcd => $hfped, $pcd => $hped),               false),
            2  => array(CERTIFCOMPLETIONSTATE_ASSIGNED,   0,    $not, 2, array($fpcd => $hfped, $fcd => $hfed),               false),
            3  => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $icd, $ied, 2, array($fpcd => $hfped, $pcd => $hped),               false),
            4  => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $icd, $ied, 2, array($fpcd => $hfped, $fcd => $hfed),               false),
            5  => array(CERTIFCOMPLETIONSTATE_WINDOWOPEN, $icd, $ied, 3, array($fpcd => $hfped, $pcd => $hped, $icd => $ied), false),
            6  => array(CERTIFCOMPLETIONSTATE_WINDOWOPEN, $icd, $ied, 3, array($fpcd => $hfped, $fcd => $hfed, $icd => $ied), false),
            7  => array(CERTIFCOMPLETIONSTATE_EXPIRED,    0,    $ied, 3, array($fpcd => $hfped, $pcd => $hped, $icd => $ied), false),
            8  => array(CERTIFCOMPLETIONSTATE_EXPIRED,    0,    $ied, 3, array($fpcd => $hfped, $fcd => $hfed, $icd => $ied), false),
            9  => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $icd, $err, 2, array($fpcd => $hfped, $pcd => $hped),               true),
            10 => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $icd, $err, 2, array($fpcd => $hfped, $fcd => $hfed),               true),
            11 => array(CERTIFCOMPLETIONSTATE_ASSIGNED,   0,    $not, 2, array($fpcd => $hfped, $pcd => $hped),               false),
            12 => array(CERTIFCOMPLETIONSTATE_ASSIGNED,   0,    $not, 2, array($fpcd => $hfped, $fcd => $hfed),               false),
            13 => array(CERTIFCOMPLETIONSTATE_ASSIGNED,   0,    $not, 2, array($fpcd => $hfped, $pcd => $hped),               false),
            14 => array(CERTIFCOMPLETIONSTATE_ASSIGNED,   0,    $not, 2, array($fpcd => $hfped, $fcd => $hfed),               false),
            15 => array(CERTIFCOMPLETIONSTATE_ASSIGNED,   0,    $not, 2, array($fpcd => $hfped, $pcd => $hped),               false),
            16 => array(CERTIFCOMPLETIONSTATE_ASSIGNED,   0,    $not, 2, array($fpcd => $hfped, $fcd => $hfed),               false),
        );

        for ($i = 1; $i <= 16; $i++) {
            list($certcompletion, $progcompletion) = certif_load_completion($this->program->id, $this->users[$i]->id);
            $state = certif_get_completion_state($certcompletion);
            $errors = certif_get_completion_errors($certcompletion, $progcompletion);

            $expectedstate = $expected[$i][0];
            $expectedcerttimecompleted = $expected[$i][1];
            $expectedduedate = $expected[$i][2];
            $expectedhistorycount = $expected[$i][3];
            $expectedhistorydates = $expected[$i][4];
            $expectedhaserrors = $expected[$i][5];

            $this->assertEquals($expectedstate, $state, $i);

            $this->assertEquals($expectedcerttimecompleted, $certcompletion->timecompleted, $i);

            $this->assertEquals($expectedduedate, $progcompletion->timedue, $i);

            $historyrecords = $DB->get_records('certif_completion_history',
                array('certifid' => $this->program->certifid, 'userid' => $this->users[$i]->id));
            $this->assertEquals($expectedhistorycount, count($historyrecords), $i);

            foreach ($historyrecords as $historyrecord) {
                $this->assertArrayHasKey($historyrecord->timecompleted, $expectedhistorydates, $i);
                $this->assertEquals($expectedhistorydates[$historyrecord->timecompleted], $historyrecord->timeexpires, $i);
            }

            if ($expectedhaserrors) {
                $this->assertNotEmpty($errors);
            } else {
                $this->assertEmpty($errors);
            }
        }

        // Make sure there are no completion records for users 17 to 20.
        for ($i = 17; $i <= 20; $i++) {
            list($certcompletion, $progcompletion) = certif_load_completion($this->program->id, $this->users[$i]->id, false);
            $this->assertFalse($certcompletion);
            $this->assertFalse($progcompletion);
            $this->assertEquals(0, $DB->count_records('certif_completion_history', array('userid' => $this->users[$i]->id)));
        }

    }

    /**
     * Test "Certify uncertified users" option. Make sure:
     * - current completions are not overridden if the imported completion is newer or older (3, 4, 5, 6, 9, 10 have initial completion time)
     * - incomplete users are completed (1, 2, 7, 8, 11 to 16 have new completion time (future or past))
     * - unassigned users are assigned and completed (15 and 16 are certified and have completion times)
     * - future assignments are completed (13 and 14 are certified and have completion times)
     * - 2 historys are created if and only if the user was already completed (3, 4, 9, 10 have 2, 5 and 6 have 3)
     * - otherwise 1 history added created (1, 2, 11 to 16 have 1, 7 and 8 have 2)
     * @dataProvider data_provider_recertifydatetypes
     */
    public function test_import_action_certify_uncertified($recertifydatetype) {
        global $DB;

        $this->setup_import_action_tests($recertifydatetype);

        $importtime = time();
        set_config('importactioncertification', COMPLETION_IMPORT_COMPLETE_INCOMPLETE, 'totara_completionimport_certification');

        $handle = fopen($this->filename, 'r');
        $size = filesize($this->filename);
        $content = fread($handle, $size);
        \totara_completionimport\csv_import::import($content, 'certification', $importtime);

        // Key: i => initial, f => future, p => past, fp => far past, cd => completion, ed => expiry date, h => history.
        $icd = $this->initialcompletiondate;
        $fcd = $this->futurecompletiondate;
        $pcd = $this->pastcompletiondate;
        $fpcd = $this->farpastcompletiondate;
        $ied = $this->initialexpirydate;
        $hfped = get_timeexpires($fpcd, $this->progdata['cert_activeperiod']); // Far past import has no due date, so no switch needed.
        if ($recertifydatetype == CERTIFRECERT_COMPLETION) {
            $ped = get_timeexpires($pcd, $this->progdata['cert_activeperiod']);
            $fed = get_timeexpires($fcd, $this->progdata['cert_activeperiod']);
            $hped = get_timeexpires($pcd, $this->progdata['cert_activeperiod']);
            $hfed = get_timeexpires($fcd, $this->progdata['cert_activeperiod']);
        } else {
            $ped = get_timeexpires($this->pastduedate, $this->progdata['cert_activeperiod']);
            $fed = get_timeexpires($this->futureduedate, $this->progdata['cert_activeperiod']);
            $hped = get_timeexpires($this->pastduedate, $this->progdata['cert_activeperiod']);
            $hfed = get_timeexpires($this->futureduedate, $this->progdata['cert_activeperiod']);
        }
        $expected = array( // State, due date, cert time completed, history count, history dates (timecompleted => timeexpires), errors.
            1  => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $pcd, $ped, 1, array($fpcd => $hfped),                              false),
            2  => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $fcd, $fed, 1, array($fpcd => $hfped),                              false),
            3  => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $icd, $ied, 2, array($fpcd => $hfped, $pcd => $hped),               false),
            4  => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $icd, $ied, 2, array($fpcd => $hfped, $fcd => $hfed),               false),
            5  => array(CERTIFCOMPLETIONSTATE_WINDOWOPEN, $icd, $ied, 3, array($fpcd => $hfped, $pcd => $hped, $icd => $ied), false),
            6  => array(CERTIFCOMPLETIONSTATE_WINDOWOPEN, $icd, $ied, 3, array($fpcd => $hfped, $fcd => $hfed, $icd => $ied), false),
            7  => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $pcd, $ped, 2, array($fpcd => $hfped, $icd => $ied),                false),
            8  => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $fcd, $fed, 2, array($fpcd => $hfped, $icd => $ied),                false),
            9  => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $pcd, $ped, 2, array($fpcd => $hfped, $icd => $ied),                false),
            10 => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $fcd, $fed, 2, array($fpcd => $hfped, $icd => $ied),                false),
            11 => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $pcd, $ped, 1, array($fpcd => $hfped),                              false),
            12 => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $fcd, $fed, 1, array($fpcd => $hfped),                              false),
            13 => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $pcd, $ped, 1, array($fpcd => $hfped),                              false),
            14 => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $fcd, $fed, 1, array($fpcd => $hfped),                              false),
            15 => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $pcd, $ped, 1, array($fpcd => $hfped),                              false),
            16 => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $fcd, $fed, 1, array($fpcd => $hfped),                              false),
        );

        for ($i = 1; $i <= 16; $i++) {
            list($certcompletion, $progcompletion) = certif_load_completion($this->program->id, $this->users[$i]->id);
            $state = certif_get_completion_state($certcompletion);
            $errors = certif_get_completion_errors($certcompletion, $progcompletion);

            $expectedstate = $expected[$i][0];
            $expectedcerttimecompleted = $expected[$i][1];
            $expectedduedate = $expected[$i][2];
            $expectedhistorycount = $expected[$i][3];
            $expectedhistorydates = $expected[$i][4];
            $expectedhaserrors = $expected[$i][5];

            $this->assertEquals($expectedstate, $state, $i);

            $this->assertEquals($expectedcerttimecompleted, $certcompletion->timecompleted, $i);

            $this->assertEquals($expectedduedate, $progcompletion->timedue, $i);

            $historyrecords = $DB->get_records('certif_completion_history',
                array('certifid' => $this->program->certifid, 'userid' => $this->users[$i]->id));
            $this->assertEquals($expectedhistorycount, count($historyrecords), $i);

            foreach ($historyrecords as $historyrecord) {
                $this->assertArrayHasKey($historyrecord->timecompleted, $expectedhistorydates, $i);
                $this->assertEquals($expectedhistorydates[$historyrecord->timecompleted], $historyrecord->timeexpires, $i);
            }

            if ($expectedhaserrors) {
                $this->assertNotEmpty($errors);
            } else {
                $this->assertEmpty($errors);
            }
        }

        // Make sure there are no completion records for users 17 to 20.
        for ($i = 17; $i <= 20; $i++) {
            list($certcompletion, $progcompletion) = certif_load_completion($this->program->id, $this->users[$i]->id, false);
            $this->assertFalse($certcompletion);
            $this->assertFalse($progcompletion);
            $this->assertEquals(0, $DB->count_records('certif_completion_history', array('userid' => $this->users[$i]->id)));
        }
    }

    /**
     * Test "Certify if newer" option. Make sure:
     * - current completions are overridden if the imported completion is newer (4, 6, 10 have future completion date, 10 no longer has error)
     * - current completions are not overridden if the imported completion is older (3, 5, 9 still have the initial completion time)
     * - incomplete users are completed (1, 2, 7, 8, 11 to 16 are certified, have import completion time)
     * - unassigned users are assigned and completed (15 and 16 are certified)
     * - future assignments are completed (13 and 14 are certified)
     * - 2 historys are created if and only if the user was already completed ( have 2,  have 3)
     * - otherwise 1 history added created ( have 1,  have 2)
     * @dataProvider data_provider_recertifydatetypes
     */
    public function test_import_action_certify_if_newer($recertifydatetype) {
        global $DB;

        $this->setup_import_action_tests($recertifydatetype);

        $importtime = time();
        set_config('importactioncertification', COMPLETION_IMPORT_OVERRIDE_IF_NEWER, 'totara_completionimport_certification');

        $handle = fopen($this->filename, 'r');
        $size = filesize($this->filename);
        $content = fread($handle, $size);
        \totara_completionimport\csv_import::import($content, 'certification', $importtime);

        // Key: i => initial, f => future, p => past, fp => far past, cd => completion, ed => expiry date, h => history.
        $icd = $this->initialcompletiondate;
        $fcd = $this->futurecompletiondate;
        $pcd = $this->pastcompletiondate;
        $fpcd = $this->farpastcompletiondate;
        $ied = $this->initialexpirydate;
        $hfped = get_timeexpires($fpcd, $this->progdata['cert_activeperiod']); // Far past import has no due date, so no switch needed.
        if ($recertifydatetype == CERTIFRECERT_COMPLETION) {
            $ped = get_timeexpires($pcd, $this->progdata['cert_activeperiod']);
            $hped = get_timeexpires($pcd, $this->progdata['cert_activeperiod']);
            $fednew = get_timeexpires($fcd, $this->progdata['cert_activeperiod']);
            $fedupd = $fednew;
        } else {
            $ped = get_timeexpires($this->pastduedate, $this->progdata['cert_activeperiod']);
            $hped = get_timeexpires($this->pastduedate, $this->progdata['cert_activeperiod']); // History are ignoring current expiry date.
            $fednew = get_timeexpires($this->futureduedate, $this->progdata['cert_activeperiod']); // New are creating completely fresh expiry dates.
            $fedupd = get_timeexpires($icd, $this->progdata['cert_activeperiod']); // Update are based on previous expiry date.
            // Note that $fedupd is using $icd, not $ied, because the completion date is before window open and beyond minimum active period.
        }
        $expected = array( // State, cert time completed, due date, history count, history dates (timecompleted => timeexpires), errors.
            1  => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $pcd, $ped,    1, array($fpcd => $hfped),                              false),
            2  => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $fcd, $fednew, 1, array($fpcd => $hfped),                              false),
            3  => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $icd, $ied,    2, array($fpcd => $hfped, $pcd => $hped),               false),
            4  => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $fcd, $fedupd, 2, array($fpcd => $hfped, $icd => $ied),                false),
            5  => array(CERTIFCOMPLETIONSTATE_WINDOWOPEN, $icd, $ied,    3, array($fpcd => $hfped, $pcd => $hped, $icd => $ied), false),
            6  => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $fcd, $fedupd, 2, array($fpcd => $hfped, $icd => $ied),                false),
            7  => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $pcd, $ped,    2, array($fpcd => $hfped, $icd => $ied),                false),
            8  => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $fcd, $fednew, 2, array($fpcd => $hfped, $icd => $ied),                false),
            9  => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $pcd, $ped,    2, array($fpcd => $hfped, $icd => $ied),                false),
            10 => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $fcd, $fednew, 2, array($fpcd => $hfped, $icd => $ied),                false),
            11 => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $pcd, $ped,    1, array($fpcd => $hfped),                              false),
            12 => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $fcd, $fednew, 1, array($fpcd => $hfped),                              false),
            13 => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $pcd, $ped,    1, array($fpcd => $hfped),                              false),
            14 => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $fcd, $fednew, 1, array($fpcd => $hfped),                              false),
            15 => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $pcd, $ped,    1, array($fpcd => $hfped),                              false),
            16 => array(CERTIFCOMPLETIONSTATE_CERTIFIED,  $fcd, $fednew, 1, array($fpcd => $hfped),                              false),
        );

        for ($i = 1; $i <= 16; $i++) {
            list($certcompletion, $progcompletion) = certif_load_completion($this->program->id, $this->users[$i]->id);
            $state = certif_get_completion_state($certcompletion);
            $errors = certif_get_completion_errors($certcompletion, $progcompletion);

            $expectedstate = $expected[$i][0];
            $expectedcerttimecompleted = $expected[$i][1];
            $expectedduedate = $expected[$i][2];
            $expectedhistorycount = $expected[$i][3];
            $expectedhistorydates = $expected[$i][4];
            $expectedhaserrors = $expected[$i][5];

            $this->assertEquals($expectedstate, $state, $i);

            $this->assertEquals($expectedcerttimecompleted, $certcompletion->timecompleted, $i);

            $this->assertEquals($expectedduedate, $progcompletion->timedue, $i);

            $historyrecords = $DB->get_records('certif_completion_history',
                array('certifid' => $this->program->certifid, 'userid' => $this->users[$i]->id));
            $this->assertEquals($expectedhistorycount, count($historyrecords), $i);

            foreach ($historyrecords as $historyrecord) {
                $this->assertArrayHasKey($historyrecord->timecompleted, $expectedhistorydates, $i);
                $this->assertEquals($expectedhistorydates[$historyrecord->timecompleted], $historyrecord->timeexpires, $i);
            }

            if ($expectedhaserrors) {
                $this->assertNotEmpty($errors);
            } else {
                $this->assertEmpty($errors);
            }
        }

        // Make sure there are no completion records for users 17 to 20.
        for ($i = 17; $i <= 20; $i++) {
            list($certcompletion, $progcompletion) = certif_load_completion($this->program->id, $this->users[$i]->id, false);
            $this->assertFalse($certcompletion);
            $this->assertFalse($progcompletion);
            $this->assertEquals(0, $DB->count_records('certif_completion_history', array('userid' => $this->users[$i]->id)));
        }
    }

    /**
     * Mark a user's certification as certified.
     *
     * This function is copied from a patch coming to certification in the near future. Once released, this
     * function should be removed.
     *
     * This action is only valid if the user is currently in one of the following states:
     * CERTIFCOMPLETIONSTATE_ASSIGNED
     * CERTIFCOMPLETIONSTATE_WINDOWOPEN
     * CERTIFCOMPLETIONSTATE_EXPIRED
     *
     * @param int $programid
     * @param int $userid
     * @param int $timecompleted
     */
    private function certif_set_state_certified($programid, $userid, $timecompleted) {
        global $DB;

        list($certcompletion, $progcompletion) = certif_load_completion($programid, $userid);

        $now = time();

        // Ensure that the existing data is valid (don't modify invalid data, because we'll just make it worse).
        $errors = certif_get_completion_errors($certcompletion, $progcompletion);
        $this->assertEmpty($errors);

        // State can only be changed to certified from these specific states.
        $validfromstates = array(CERTIFCOMPLETIONSTATE_ASSIGNED, CERTIFCOMPLETIONSTATE_WINDOWOPEN, CERTIFCOMPLETIONSTATE_EXPIRED);
        $currentstate = certif_get_completion_state($certcompletion);
        $this->assertTrue(in_array($currentstate, $validfromstates));

        if (empty($message)) {
            $message = 'User certified';
        }

        // Calculate the base time.
        $certification = $DB->get_record('certif', array('id' => $certcompletion->certifid));
        $base = get_certiftimebase($certification->recertifydatetype, $certcompletion->baselinetimeexpires,
            $timecompleted, $progcompletion->timedue, $certification->activeperiod, $certification->minimumactiveperiod,
            $certification->windowperiod);

        // Change the cert and prog completion records.
        $certcompletion->status = CERTIFSTATUS_COMPLETED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletion->certifpath = CERTIFPATH_RECERT;
        $certcompletion->timecompleted = $timecompleted;
        $certcompletion->timeexpires = get_timeexpires($base, $certification->activeperiod);
        $certcompletion->baselinetimeexpires = $certcompletion->timeexpires;
        $certcompletion->timewindowopens = get_timewindowopens($certcompletion->timeexpires, $certification->windowperiod);
        $certcompletion->timemodified = $timecompleted;

        $jobassignment = \totara_job\job_assignment::get_first($userid);

        $progcompletion->status = STATUS_PROGRAM_COMPLETE;
        $progcompletion->timecompleted = $timecompleted;
        $progcompletion->timedue = $certcompletion->timeexpires;
        $progcompletion->positionid = $jobassignment->positionid;
        $progcompletion->organisationid = $jobassignment->organisationid;

        // Save the change (performs data validation and logging).
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion, $message));
    }

    /**
     * Mark a user's certification as window open.
     *
     * This function is copied from a patch coming to certification in the near future. Once released, this
     * function should be removed.
     *
     * This action is only valid if the user is currently CERTIFCOMPLETIONSTATE_CERTIFIED.
     *
     * @param int $programid
     * @param int $userid
     */
    private function certif_set_state_windowopen($programid, $userid) {
        list($certcompletion, $progcompletion) = certif_load_completion($programid, $userid);

        // Ensure that the existing data is valid (don't modify invalid data, because we'll just make it worse).
        $errors = certif_get_completion_errors($certcompletion, $progcompletion);
        $this->assertEmpty($errors);

        // State can only be changed to window open if it is currently certified.
        $currentstate = certif_get_completion_state($certcompletion);
        $this->assertEquals($currentstate, CERTIFCOMPLETIONSTATE_CERTIFIED);

        copy_certif_completion_to_hist($certcompletion->certifid, $userid);

        $logmessage = 'Window opened, current certification completion archived, all courses reset';

        // Change the cert and prog completion records.
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_DUE;
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;

        // Save the change (performs data validation and logging).
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion, $logmessage));
    }

    /**
     * Mark a user's certification as expired.
     *
     * This function is copied from a patch coming to certification in the near future. Once released, this
     * function should be removed.
     *
     * This action is only valid if the user is currently CERTIFCOMPLETIONSTATE_WINDOWOPEN.
     *
     * @param int $programid
     * @param int $userid
     */
    private function certif_set_state_expired($programid, $userid) {
        list($certcompletion, $progcompletion) = certif_load_completion($programid, $userid);

        $now = time();

        // Ensure that the existing data is valid (don't modify invalid data, because we'll just make it worse).
        $errors = certif_get_completion_errors($certcompletion, $progcompletion);
        $this->assertEmpty($errors);

        // State can only be changed to expired if it is currently window open.
        $currentstate = certif_get_completion_state($certcompletion);
        $this->assertEquals($currentstate, CERTIFCOMPLETIONSTATE_WINDOWOPEN);

        $logmessage = 'Certification expired, changed to primary certification path';

        // Change the cert and prog completion records.
        $certcompletion->status = CERTIFSTATUS_EXPIRED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_EXPIRED;
        $certcompletion->certifpath = CERTIFPATH_CERT;
        $certcompletion->timecompleted = 0;
        $certcompletion->timewindowopens = 0;
        $certcompletion->timeexpires = 0;
        $certcompletion->baselinetimeexpires = 0;
        $certcompletion->timemodified = $now;

        // Save the change (performs data validation and logging).
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion, $logmessage));
    }
}
