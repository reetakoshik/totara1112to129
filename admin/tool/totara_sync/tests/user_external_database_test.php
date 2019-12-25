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
 * @author Simon Player <simon.player@totaralms.com>
 * @package totara_sync
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/totara_sync/lib.php');
require_once($CFG->dirroot . '/admin/tool/totara_sync/sources/databaselib.php');

/**
 * Class tool_totara_sync_user_external_database_testcase
 *
 * These tests require an external database to be configured and defined in the sites config.
 * See the README.md file in /admin/tool/totara_sync/
 *
 * These tests will be skipped if an external database is not defined.
 *
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose tool_totara_sync_user_external_database_testcase admin/tool/totara_sync/tests/user_external_database_test.php
 *
 * @group tool_totara_sync
 */
class tool_totara_sync_user_external_database_testcase extends advanced_testcase {

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

    // The fields to import and test.
    private $fieldstoimport = array(
        'idnumber' => array(
            'maxfieldsize' => 255,
            'type' => 'string',
            'data' => '1',
        ),
        'username' => array(
            'maxfieldsize' => 100,
            'type' => 'string',
            'data' => 'user1',
        ),
        'firstname' => array(
            'maxfieldsize' => 100,
            'type' => 'string',
            'data' => 'firstname',
        ),
        'lastname' => array(
            'maxfieldsize' => 100,
            'type' => 'string',
            'data' => 'lastname',
        ),
        'firstnamephonetic' => array(
            'maxfieldsize' => 255,
            'type' => 'string',
            'data' => 'firstnamephonetic',
        ),
        'lastnamephonetic' => array(
            'maxfieldsize' => 255,
            'type' => 'string',
            'data' => 'lastnamephonetic',
        ),
        'middlename' => array(
            'maxfieldsize' => 255,
            'type' => 'string',
            'data' => 'middlename',
        ),
        'alternatename' => array(
            'maxfieldsize' => 255,
            'type' => 'string',
            'data' => 'alternatename',
        ),
        'email' => array(
            'maxfieldsize' => 100,
            'type' => 'string',
            'data' => 'email@example.com',
        ),
        'city' => array(
            'maxfieldsize' => 120,
            'type' => 'string',
            'data' => 'Brighton',
        ),
        'url' => array(
            'maxfieldsize' => 255,
            'type' => 'string',
            'data' => 'https://www.totaralms.com/',
        ),
        'institution' => array(
            'maxfieldsize' => 255,
            'type' => 'string',
            'data' => 'institution',
        ),
        'department' => array(
            'maxfieldsize' => 255,
            'type' => 'string',
            'data' => 'department',
        ),
        'phone1' => array(
            'maxfieldsize' => 20,
            'type' => 'string',
            'data' => '0123456789',
        ),
        'phone2' => array(
            'maxfieldsize' => 20,
            'type' => 'string',
            'data' => '9876543210',
        ),
        'address' => array(
            'maxfieldsize' => 255,
            'type' => 'string',
            'data' => 'address',
        ),
        'password' => array(
            'maxfieldsize' => 255,
            'type' => 'string',
            'data' => '!Passw0rd$#!'
        ),
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
            'allow_create' => '1',
            'allow_delete' => '0',
            'allow_update' => '1',
            'allowduplicatedemails' => '0',
            'defaultsyncemail' => '',
            'forcepwchange' => '0',
            'ignoreexistingpass' => '0',
            'sourceallrecords' => '0',
        );

        // Update the config to set fields to import.
        foreach ($this->fieldstoimport as $field => $fieldsettings) {
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
        $this->fieldstoimport = null;
        parent::tearDown();
    }

    protected function create_external_user_table() {
        $dbman = $this->ext_dbconnection->get_manager();
        $table = new xmldb_table($this->dbtable);

        // Drop table first, if it exists.
        if ($dbman->table_exists($this->dbtable)) {
            $dbman->drop_table($table, $this->dbtable);
        }

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('deleted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        // Create fields from fieldstoimport array.
        foreach ($this->fieldstoimport as $field => $fieldsettings) {
            $type = $fieldsettings['type'] == 'integer' ? XMLDB_TYPE_INTEGER : XMLDB_TYPE_CHAR;
            $table->add_field($field, $type, $fieldsettings['maxfieldsize']);
        }

        // Add keys.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Create the table.
        $dbman->create_table($table, false, false);
    }

    protected function populate_external_user_table($skipcreatetable = false) {
        if (!$skipcreatetable) {
            // First, lets create user table.
            $this->create_external_user_table();
        }

        // Data to insert.
        $data = array(
            "timemodified" => 0,
            "deleted" => 0
        );

        // Additional data.
        foreach ($this->fieldstoimport as $field => $fieldsettings) {
            $data[$field] = $fieldsettings['data'];
        }

        $this->ext_dbconnection->insert_record($this->dbtable, $data, true);
    }

    protected function generate_data($type, $max, $isemail = null) {
        $data = '';
        if ($type == 'string') {
            $items = range('a', 'z');
        } else {
            $items = range(0, 9);
        }
        $numitems = count($items);

        for ($i = 1; $i <= $max; $i++) {
            $data .= $items[rand(0,$numitems-1)];
        }

        if ($isemail) {
            $data = substr($data, 0, -12) . "@example.com";
        }

        return $data;
    }

    protected function run_sync() {
        $elements = totara_sync_get_elements(true);
        $element = $elements['user'];
        $result = $element->sync();
        return $result;
    }

    public function test_sync_database_connect() {
        if (!$this->configexists) {
            $this->markTestSkipped();
        }
        $this->assertInstanceOf('moodle_database', $this->ext_dbconnection);
    }

    // Test the sync using data from fieldstoimport array.
    public function test_sync() {
        global $DB;
        if (!$this->configexists) {
            $this->markTestSkipped();
        }

        // Populate the external db table.
        $this->populate_external_user_table();

        // Run and test the sync.
        $this->assertTrue($this->run_sync());

        // Check data synced correctly.
        $data = array();
        foreach ($this->fieldstoimport as $field => $fieldsettings) {
            if ($field != "password") {
                $data[$field] = $fieldsettings['data'];
            }
        }
        $this->assertTrue($DB->record_exists('user', $data));
    }

    // Test the sync using data from fieldstoimport array but with the data length set to its maximum length.
    public function test_sync_max_fieldsize() {
        global $DB;
        if (!$this->configexists) {
            $this->markTestSkipped();
        }

        // Set import data to maximum length.
        foreach ($this->fieldstoimport as $field => $fieldsettings) {
            $isemail = $field == 'email' ? true : false;
            $this->fieldstoimport[$field]['data'] = $this->generate_data($this->fieldstoimport[$field]['type'], $this->fieldstoimport[$field]['maxfieldsize'], $isemail);
        }

        // Populate the external db table.
        $this->populate_external_user_table();

        // Run and test the sync.
        $this->assertTrue($this->run_sync());

        // Check data synced correctly.
        $data = array();
        foreach ($this->fieldstoimport as $field => $fieldsettings) {
            if ($field !== "password") {
                $data[$field] = $fieldsettings['data'];
            }
        }
        $this->assertTrue($DB->record_exists('user', $data));
    }
}
