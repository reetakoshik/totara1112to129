<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @package tool_totara_sync
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/totara_sync/lib.php');
require_once($CFG->dirroot . '/admin/tool/totara_sync/sources/databaselib.php');

/**
 * Class tool_totara_sync_user_externaldb_check_sanity_testcase
 *
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose tool_totara_sync_user_externaldb_check_sanity_testcase admin/tool/totara_sync/tests/user_externaldb_check_sanity_test.php
 *
 * @group tool_totara_sync
 */
class tool_totara_sync_user_externaldb_check_sanity_testcase extends advanced_testcase {

    private $configdb = array();
    private $config = array();
    private $configexists = false;

    private $ext_dbconnection = null;

    // Database variables for connection.
    private $dbtype = '';
    private $dbhost = '';
    private $dbport = '';
    private $dbname = '';
    private $dbuser = '';
    private $dbpass = '';
    private $dbtable = '';

    private $element;
    private $synctable;
    private $synctable_clone;

    private $fields = array(
        "idnumber",
        "deleted",
        "timemodified",
        "username",
        "firstname",
        "lastname",
        "email",
        "country",
        "lang",
    );

    public function setUp() {
        global $CFG;

        parent::setup();

        $this->resetAfterTest(true);
        $this->preventResetByRollback();
        $this->setAdminUser();

        if (defined('TEST_SYNC_DB_TYPE') ||
            defined('TEST_SYNC_DB_HOST') ||
            defined('TEST_SYNC_DB_PORT') ||
            defined('TEST_SYNC_DB_NAME') ||
            defined('TEST_SYNC_DB_USER') ||
            defined('TEST_SYNC_DB_PASS') ||
            defined('TEST_SYNC_DB_TABLE')) {
            $this->dbtype = defined('TEST_SYNC_DB_TYPE') ? TEST_SYNC_DB_TYPE : '';
            $this->dbhost = defined('TEST_SYNC_DB_HOST') ? TEST_SYNC_DB_HOST : '';
            $this->dbport = defined('TEST_SYNC_DB_PORT') ? TEST_SYNC_DB_PORT : '';
            $this->dbname = defined('TEST_SYNC_DB_NAME') ? TEST_SYNC_DB_NAME : '';
            $this->dbuser = defined('TEST_SYNC_DB_USER') ? TEST_SYNC_DB_USER : '';
            $this->dbpass = defined('TEST_SYNC_DB_PASS') ? TEST_SYNC_DB_PASS : '';
            $this->dbtable = defined('TEST_SYNC_DB_TABLE') ? TEST_SYNC_DB_TABLE : '';
        } else {
            $this->dbtype = $CFG->dbtype;
            $this->dbhost = $CFG->dbhost;
            $this->dbport = !empty($CFG->dboptions['dbport']) ? $CFG->dboptions['dbport'] : '';
            $this->dbname = $CFG->dbname;
            $this->dbuser = $CFG->dbuser;
            $this->dbpass = !empty($CFG->dbpass) ? $CFG->dbpass : '';
            $this->dbtable = $CFG->prefix . 'totara_sync_user_source';
        }

        if (!empty($this->dbtype) &&
            !empty($this->dbhost) &&
            !empty($this->dbname) &&
            !empty($this->dbuser) &&
            !empty($this->dbtable)) {
            // All necessary config variables are set.
            $this->configexists = true;
            $this->ext_dbconnection = setup_sync_DB($this->dbtype, $this->dbhost, $this->dbname, $this->dbuser, $this->dbpass, array('dbport' => $this->dbport));
        } else {
            $this->assertTrue(false, 'HR Import database test configuration was only partially provided');
        }

        set_config('element_user_enabled', 1, 'totara_sync');
        set_config('source_user', 'totara_sync_source_user_database', 'totara_sync');

        $this->configdb = array(
            'database_dbtype' => $this->dbtype,
            'database_dbhost' => $this->dbhost,
            'database_dbname' => $this->dbname,
            'database_dbuser' => $this->dbuser,
            'database_dbpass' => $this->dbpass,
            'database_dbport' => $this->dbport,
            'database_dbtable' => $this->dbtable,
            'csvuserencoding' => 'UTF-8',
            'delimiter' => ',',
            'import_deleted' => '1',
            'import_timemodified' => '1',
        );
        $this->config = array(
            'allow_create' => '0', // We're not actually doing a sync, and one sub-check needs this set to 0.
            'allow_delete' => '0',
            'allow_update' => '1',
            'allowduplicatedemails' => '0',
            'defaultsyncemail' => '',
            'forcepwchange' => '0',
            'ignoreexistingpass' => '0',
            'sourceallrecords' => '0',
        );

        // Update the config to set fields to import.
        foreach ($this->fields as $field) {
            $this->configdb['import_' . $field] = '1';
        }

        // Set the config.
        set_config('timezone', $this->setTimezone());
        set_config('database_dateformat', 'Y-m-d', 'totara_sync_source_user_database');
        foreach ($this->configdb as $k => $v) {
            set_config($k, $v, 'totara_sync_source_user_database');
        }
        foreach ($this->config as $k => $v) {
            set_config($k, $v, 'totara_sync_element_user');
        }

        // Create the external user table.
        $dbman = $this->ext_dbconnection->get_manager();
        $table = new xmldb_table($this->dbtable);

        // Drop table first, if it exists.
        if ($dbman->table_exists($this->dbtable)) {
            $dbman->drop_table($table, $this->dbtable);
        }

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);

        // Create fields from fieldstoimport array.
        foreach ($this->fields as $field) {
            $table->add_field($field, XMLDB_TYPE_CHAR, 20);
        }

        // Add keys.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Create the table.
        $dbman->create_table($table, false, false);

        // Configure records, with one faulty record for each sub-check.

        // Retain idnumber when deleting users.
        set_config('authdeleteusers', 'partial');

        // Set up existing Totara data.
        // Causes the 7th record to fail due to existing user with the same username (and different idnumber).
        $this->getDataGenerator()->create_user(array('idnumber' => 'idx1', 'username' => 'user0007'));
        // This user is deleted and we try to undelete, but allow_create is off, so fail.
        $user13 = $this->getDataGenerator()->create_user(array('idnumber' => 'idnum013', 'totarasync' => 1));
        delete_user($user13);
        // Causes the 17th record to fail due to existing user with the same email address (and different idnumber).
        $this->getDataGenerator()->create_user(array('idnumber' => 'idx2', 'email' => 'e17@x.nz'));
        // Causes the 30th record to fail due to existing user with totara sync flag turned off.
        $this->getDataGenerator()->create_user(array('idnumber' => 'idnum030', 'totarasync' => 0));
    }

    protected function tearDown() {
        if ($this->configexists) {
            // Drop sync table.
            $dbman = $this->ext_dbconnection->get_manager();
            $table = new xmldb_table($this->dbtable);
            if ($dbman->table_exists($this->dbtable)) {
                $dbman->drop_table($table, $this->dbtable);
            }
        }
        $this->configdb = null;
        $this->config = null;
        $this->configexists = null;
        $this->ext_dbconnection = null;
        $this->dbtype = null;
        $this->dbhost = null;
        $this->dbport = null;
        $this->dbname = null;
        $this->dbuser = null;
        $this->dbpass = null;
        $this->dbtable = null;
        $this->element = null;
        $this->synctable = null;
        $this->synctable_clone = null;
        $this->fields = null;
        parent::tearDown();
    }

    /**
     * Run each sub-check on the records, checking that they find the problem and no others.
     */
    public function test_check_sanity_sub_checks_with_null() {
        global $DB;

        // Set up import data in external db. We get the data from the csv file.
        $file = fopen(__DIR__ . '/fixtures/user_check_sanity.csv', 'r');
        $sourcerecords = array();
        fgetcsv($file, 0, ','); // Skip header row.
        $nullfields = array( // The other fields MUST be non-null, otherwise importing to the temp table will fail dramatically.
            "email",
            "country",
            "lang",
        );
        while ($csvrow = fgetcsv($file, 0, ',')) {
            $csvrow = array_combine($this->fields, $csvrow);
            foreach ($nullfields as $field) {
                if ($csvrow[$field] === "") {
                    $csvrow[$field] = null;
                }
            }
            $sourcerecords[] = $csvrow;
        }
        $this->ext_dbconnection->insert_records($this->dbtable, $sourcerecords);

        // We can't run sync() because we need to see what happens half way through. So instead, we run the stuff that usually
        // happens at the start of sync(), everything before check_sanity() (which is actually not much when simplified).
        $elements = totara_sync_get_elements(true);
        /* @var totara_sync_element_user $element */
        $element = $elements['user'];
        $this->synctable = $element->get_source_sync_table();
        $this->synctable_clone = $element->get_source_sync_table_clone($this->synctable);
        $element->set_customfieldsdb();
        $this->element = $element;

        // Start the testing.
        $synctable = $this->synctable;
        $synctable_clone = $this->synctable_clone;
        $element = $this->element;

        // We'll also check that the correct number of error messages are logged.
        $this->assertCount(0, $DB->get_records('totara_sync_log'));

        // Get duplicated idnumbers.
        $badids = $element->get_duplicated_values($synctable, $synctable_clone, 'idnumber', 'duplicateuserswithidnumberx');
        sort($badids);
        $this->assertEquals(array(1, 2), $badids);
        $this->assertCount(2, $DB->get_records('totara_sync_log'));

        // Get empty idnumbers.
        $badids = $element->check_empty_values($synctable, 'idnumber', 'emptyvalueidnumberx');
        $this->assertEquals(array(3), $badids);
        $this->assertCount(3, $DB->get_records('totara_sync_log'));

        // Get duplicated usernames.
        $badids = $element->get_duplicated_values($synctable, $synctable_clone, 'username', 'duplicateuserswithusernamex');
        sort($badids);
        $this->assertEquals(array(4, 5), $badids);
        $this->assertCount(5, $DB->get_records('totara_sync_log'));

        // Get empty usernames.
        $badids = $element->check_empty_values($synctable, 'username', 'emptyvalueusernamex');
        $this->assertEquals(array(6), $badids);
        $this->assertCount(6, $DB->get_records('totara_sync_log'));

        // Check usernames against the DB to avoid saving repeated values.
        $badids = $element->check_values_in_db($synctable, 'username', 'duplicateusernamexdb');
        $this->assertEquals(array(7), $badids);
        $this->assertCount(7, $DB->get_records('totara_sync_log'));

        // Get invalid usernames.
        $badids = $element->check_invalid_username($synctable, $synctable_clone);
        $this->assertEquals(array(8), $badids);
        $this->assertCount(9, $DB->get_records('totara_sync_log')); // One error for idnum008 and one warning for idnum031.
        // Check that the warning resulted in an updated username in both sync tables.
        $this->assertEquals('user0031', $DB->get_field($synctable, 'username', array('idnumber' => 'idnum031')));
        $this->assertEquals('user0031', $DB->get_field($synctable_clone, 'username', array('idnumber' => 'idnum031')));

        // Get empty firstnames. If it is provided then it must have a non-empty value.
        $badids = $element->check_empty_values($synctable, 'firstname', 'emptyvaluefirstnamex');
        $this->assertEquals(array(9), $badids);
        $this->assertCount(10, $DB->get_records('totara_sync_log'));

        // Get empty lastnames. If it is provided then it must have a non-empty value.
        $badids = $element->check_empty_values($synctable, 'lastname', 'emptyvaluelastnamex');
        $this->assertEquals(array(10), $badids);
        $this->assertCount(11, $DB->get_records('totara_sync_log'));

        // Check invalid language set.
        $badids = $element->get_invalid_lang($synctable);
        $this->assertEquals(array(0), $badids); // WARNING ONLY!!!
        $this->assertCount(12, $DB->get_records('totara_sync_log')); // Warning was logged.

        // User is deleted, trying to undelete, but allow_create is turned off.
        $badids = $element->check_users_unable_to_revive($synctable);
        $this->assertEquals(array(13), $badids);
        $this->assertCount(13, $DB->get_records('totara_sync_log'));

        // Get duplicated emails.
        $badids = $element->get_duplicated_values($synctable, $synctable_clone, 'LOWER(email)', 'duplicateuserswithemailx');
        sort($badids);
        $this->assertEquals(array(14, 15, 17, 35), $badids);
        $this->assertCount(17, $DB->get_records('totara_sync_log'));

        // Get empty emails.
        $badids = $element->check_empty_values($synctable, 'email', 'emptyvalueemailx');
        $this->assertEquals(array(), $badids); // Null email is allowed - it will not update the existing value or will be mepty on insert.
        $this->assertCount(17, $DB->get_records('totara_sync_log'));

        // Check emails against the DB to avoid saving repeated values.
        $badids = $element->check_values_in_db($synctable, 'email', 'duplicateusersemailxdb');
        sort($badids);
        $this->assertEquals(array(17, 35), $badids);
        $this->assertCount(19, $DB->get_records('totara_sync_log'));

        // Get invalid emails.
        $badids = $element->get_invalid_emails($synctable);
        $this->assertEquals(array(18), $badids); // Null email is allowed - it will not update the existing value or will be mepty on insert.
        $this->assertCount(20, $DB->get_records('totara_sync_log'));

        // Can't check custom field sanity check in this test - it's too complicated.

        // Check for users with the totarasync flag turned off.
        $badids = $element->check_user_sync_disabled($synctable);
        $this->assertEquals(array(30), $badids);
        $this->assertCount(21, $DB->get_records('totara_sync_log'));
    }

    /**
     * Run check_sanity, checking that it finds all of the problems. Because of the previous test, we can be sure that
     * each record was excluded for the correct reason and not just coincidence.
     */
    public function test_check_sanity_with_null() {
        global $DB;

        // Set up import data in external db. We get the data from the csv file.
        $file = fopen(__DIR__ . '/fixtures/user_check_sanity.csv', 'r');
        $sourcerecords = array();
        fgetcsv($file, 0, ','); // Skip header row.
        $nullfields = array( // The other fields MUST be non-null, otherwise importing to the temp table will fail dramatically.
            "email",
            "lang",
        );
        while ($csvrow = fgetcsv($file, 0, ',')) {
            $csvrow = array_combine($this->fields, $csvrow);
            foreach ($nullfields as $field) {
                if ($csvrow[$field] === "") {
                    $csvrow[$field] = null;
                }
            }
            $sourcerecords[] = $csvrow;
        }
        $this->ext_dbconnection->insert_records($this->dbtable, $sourcerecords);

        // We can't run sync() because we need to see what happens half way through. So instead, we run the stuff that usually
        // happens at the start of sync(), everything before check_sanity() (which is actually not much when simplified).
        $elements = totara_sync_get_elements(true);
        /* @var totara_sync_element_user $element */
        $element = $elements['user'];
        $this->synctable = $element->get_source_sync_table();
        $this->synctable_clone = $element->get_source_sync_table_clone($this->synctable);
        $element->set_customfieldsdb();
        $this->element = $element;

        // Start the testing.
        $invalididnumbers = $this->element->check_sanity($this->synctable, $this->synctable_clone);
        ksort($invalididnumbers);
        $this->assertEquals(array(
            1 => 'idnum001',
            2 => 'idnum001',
            3 => '',
            4 => 'idnum004',
            5 => 'idnum005',
            6 => 'idnum006',
            7 => 'idnum007',
            8 => 'idnum008',
            9 => 'idnum009',
            10 => 'idnum010',
            // Record with idnum012 is not here because it was merged with just a warning.
            13 => 'idnum013',
            14 => 'idnum014',
            15 => 'idnum015',
            17 => 'idnum017',
            18 => 'idnum018',
            30 => 'idnum030',
            // Record with idnum31 is not here because it was merged with just a warning.
            32 => 'idnum032',
            33 => 'idnum033',
            35 => 'idnum035',
        ), $invalididnumbers);

        $this->assertEquals(23, count($DB->get_records('totara_sync_log')));
    }

    /**
     * Run each sub-check on the records, checking that they find the problem and no others.
     */
    public function test_check_sanity_sub_checks_with_empty() {
        global $DB;

        // Set up import data in external db. We get the data from the csv file.
        $file = fopen(__DIR__ . '/fixtures/user_check_sanity.csv', 'r');
        $sourcerecords = array();
        fgetcsv($file, 0, ','); // Skip header row.
        while ($csvrow = fgetcsv($file, 0, ',')) {
            $csvrow = array_combine($this->fields, $csvrow);
            $sourcerecords[] = $csvrow;
        }
        $this->ext_dbconnection->insert_records($this->dbtable, $sourcerecords);

        // We can't run sync() because we need to see what happens half way through. So instead, we run the stuff that usually
        // happens at the start of sync(), everything before check_sanity() (which is actually not much when simplified).
        $elements = totara_sync_get_elements(true);
        /* @var totara_sync_element_user $element */
        $element = $elements['user'];
        $this->synctable = $element->get_source_sync_table();
        $this->synctable_clone = $element->get_source_sync_table_clone($this->synctable);
        $element->set_customfieldsdb();
        $this->element = $element;

        // Start the testing.
        $synctable = $this->synctable;
        $synctable_clone = $this->synctable_clone;
        $element = $this->element;

        // We'll also check that the correct number of error messages are logged.
        $this->assertCount(0, $DB->get_records('totara_sync_log'));

        // Get duplicated idnumbers.
        $badids = $element->get_duplicated_values($synctable, $synctable_clone, 'idnumber', 'duplicateuserswithidnumberx');
        sort($badids);
        $this->assertEquals(array(1, 2), $badids);
        $this->assertCount(2, $DB->get_records('totara_sync_log'));

        // Get empty idnumbers.
        $badids = $element->check_empty_values($synctable, 'idnumber', 'emptyvalueidnumberx');
        $this->assertEquals(array(3), $badids);
        $this->assertCount(3, $DB->get_records('totara_sync_log'));

        // Get duplicated usernames.
        $badids = $element->get_duplicated_values($synctable, $synctable_clone, 'username', 'duplicateuserswithusernamex');
        sort($badids);
        $this->assertEquals(array(4, 5), $badids);
        $this->assertCount(5, $DB->get_records('totara_sync_log'));

        // Get empty usernames.
        $badids = $element->check_empty_values($synctable, 'username', 'emptyvalueusernamex');
        $this->assertEquals(array(6), $badids);
        $this->assertCount(6, $DB->get_records('totara_sync_log'));

        // Check usernames against the DB to avoid saving repeated values.
        $badids = $element->check_values_in_db($synctable, 'username', 'duplicateusernamexdb');
        $this->assertEquals(array(7), $badids);
        $this->assertCount(7, $DB->get_records('totara_sync_log'));

        // Get invalid usernames.
        $badids = $element->check_invalid_username($synctable, $synctable_clone);
        $this->assertEquals(array(8), $badids);
        $this->assertCount(9, $DB->get_records('totara_sync_log')); // One error for idnum008 and one warning for idnum031.
        // Check that the warning resulted in an updated username in both sync tables.
        $this->assertEquals('user0031', $DB->get_field($synctable, 'username', array('idnumber' => 'idnum031')));
        $this->assertEquals('user0031', $DB->get_field($synctable_clone, 'username', array('idnumber' => 'idnum031')));

        // Get empty firstnames. If it is provided then it must have a non-empty value.
        $badids = $element->check_empty_values($synctable, 'firstname', 'emptyvaluefirstnamex');
        $this->assertEquals(array(9), $badids);
        $this->assertCount(10, $DB->get_records('totara_sync_log'));

        // Get empty lastnames. If it is provided then it must have a non-empty value.
        $badids = $element->check_empty_values($synctable, 'lastname', 'emptyvaluelastnamex');
        $this->assertEquals(array(10), $badids);
        $this->assertCount(11, $DB->get_records('totara_sync_log'));

        // Check invalid language set.
        $badids = $element->get_invalid_lang($synctable);
        $this->assertEquals(array(0), $badids); // WARNING ONLY!!!
        $this->assertCount(12, $DB->get_records('totara_sync_log')); // Warning was logged.

        // User is deleted, trying to undelete, but allow_create is turned off.
        $badids = $element->check_users_unable_to_revive($synctable);
        $this->assertEquals(array(13), $badids);
        $this->assertCount(13, $DB->get_records('totara_sync_log'));

        // Get duplicated emails.
        $badids = $element->get_duplicated_values($synctable, $synctable_clone, 'LOWER(email)', 'duplicateuserswithemailx');
        sort($badids);
        $this->assertEquals(array(14, 15, 17, 35), $badids);
        $this->assertCount(17, $DB->get_records('totara_sync_log'));

        // Get empty emails.
        $badids = $element->check_empty_values($synctable, 'email', 'emptyvalueemailx');
        $this->assertEquals(array(16), $badids);
        $this->assertCount(18, $DB->get_records('totara_sync_log'));

        // Check emails against the DB to avoid saving repeated values.
        $badids = $element->check_values_in_db($synctable, 'email', 'duplicateusersemailxdb');
        sort($badids);
        $this->assertEquals(array(17, 35), $badids);
        $this->assertCount(20, $DB->get_records('totara_sync_log'));

        // Get invalid emails.
        $badids = $element->get_invalid_emails($synctable);
        sort($badids);
        $this->assertEquals(array(16, 18), $badids); // Empty email address is also invalid.
        $this->assertCount(22, $DB->get_records('totara_sync_log'));

        // Can't check custom field sanity check in this test - it's too complicated.

        // Check for users with the totarasync flag turned off.
        $badids = $element->check_user_sync_disabled($synctable);
        $this->assertEquals(array(30), $badids);
        $this->assertCount(23, $DB->get_records('totara_sync_log'));
    }

    /**
     * Run check_sanity, checking that it finds all of the problems. Because of the previous test, we can be sure that
     * each record was excluded for the correct reason and not just coincidence.
     */
    public function test_check_sanity_with_empty() {
        global $DB;

        // Set up import data in external db. We get the data from the csv file.
        $file = fopen(__DIR__ . '/fixtures/user_check_sanity.csv', 'r');
        $sourcerecords = array();
        fgetcsv($file, 0, ','); // Skip header row.
        while ($csvrow = fgetcsv($file, 0, ',')) {
            $csvrow = array_combine($this->fields, $csvrow);
            $sourcerecords[] = $csvrow;
        }
        $this->ext_dbconnection->insert_records($this->dbtable, $sourcerecords);

        // We can't run sync() because we need to see what happens half way through. So instead, we run the stuff that usually
        // happens at the start of sync(), everything before check_sanity() (which is actually not much when simplified).
        $elements = totara_sync_get_elements(true);
        /* @var totara_sync_element_user $element */
        $element = $elements['user'];
        $this->synctable = $element->get_source_sync_table();
        $this->synctable_clone = $element->get_source_sync_table_clone($this->synctable);
        $element->set_customfieldsdb();
        $this->element = $element;

        // Start the testing.
        $invalididnumbers = $this->element->check_sanity($this->synctable, $this->synctable_clone);
        ksort($invalididnumbers);
        $this->assertEquals(array(
            1 => 'idnum001',
            2 => 'idnum001',
            3 => '',
            4 => 'idnum004',
            5 => 'idnum005',
            6 => 'idnum006',
            7 => 'idnum007',
            8 => 'idnum008',
            9 => 'idnum009',
            10 => 'idnum010',
            // Record with idnum012 is not here because it was merged with just a warning.
            13 => 'idnum013',
            14 => 'idnum014',
            15 => 'idnum015',
            16 => 'idnum016', // This may have failed due to two different tests - we can't be sure which, but we're just happy it failed.
            17 => 'idnum017',
            18 => 'idnum018',
            30 => 'idnum030',
            // Record with idnum31 is not here because it was merged with just a warning.
            32 => 'idnum032',
            33 => 'idnum033',
            35 => 'idnum035'
        ), $invalididnumbers);

        $this->assertEquals(25, count($DB->get_records('totara_sync_log')));
    }

}
