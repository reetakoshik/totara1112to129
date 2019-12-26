<?php
/*
 * This file is part of Totara Learn
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package tool_totara_sync
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/totara_sync/sources/source_jobassignment_database.php');

/**
 * @group tool_totara_sync
 */
class tool_totara_sync_source_jobassignment_database_testcase extends advanced_testcase {

    /** @var moodle_database */
    private $ext_dbconnection = null;

    // Database variable for connection.
    private $dbtype = '';
    private $dbhost = '';
    private $dbport = '';
    private $dbname = '';
    private $dbuser = '';
    private $dbpass = '';
    private $dbtable = '';

    protected function tearDown() {
        $this->ext_dbconnection = null;
        $this->dbtype = $this->dbhost = $this->dbport = $this->dbname = $this->dbuser = $this->dbpass = $this->dbtable = '';

        parent::tearDown();
    }

    protected function setUp() {
        global $CFG;

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
            $this->dbtable = $CFG->prefix . 'totara_sync_jobassignment_source';
        }

        if (!empty($this->dbtype) &&
            !empty($this->dbhost) &&
            !empty($this->dbname) &&
            !empty($this->dbuser) &&
            !empty($this->dbtable)) {
            // All necessary config variables are set.
            $this->ext_dbconnection = setup_sync_DB($this->dbtype, $this->dbhost, $this->dbname, $this->dbuser, $this->dbpass, array('dbport' => $this->dbport));
        } else {
            $this->assertTrue(false, 'HR Import database test configuration was only partially provided');
        }

        set_config('source_jobassignment', 'totara_sync_source_jobassignment_database', 'totara_sync');

        set_config('database_dbtype', $this->dbtype, 'totara_sync_source_jobassignment_database');
        set_config('database_dbhost', $this->dbhost, 'totara_sync_source_jobassignment_database');
        set_config('database_dbname', $this->dbname, 'totara_sync_source_jobassignment_database');
        set_config('database_dbuser', $this->dbuser, 'totara_sync_source_jobassignment_database');
        set_config('database_dbpass', $this->dbpass, 'totara_sync_source_jobassignment_database');
        set_config('database_dbport', $this->dbport, 'totara_sync_source_jobassignment_database');
        set_config('database_dbtable', $this->dbtable, 'totara_sync_source_jobassignment_database');

        parent::setup();

        $this->resetAfterTest(true);
        $this->preventResetByRollback();
        $this->setAdminUser();

        $this->create_external_jobassignment_table();
    }

    public function create_external_jobassignment_table() {

        $dbman = $this->ext_dbconnection->get_manager();
        $table = new xmldb_table($this->dbtable);

        // Drop table first, if it exists
        if ($dbman->table_exists($this->dbtable)) {
            $dbman->drop_table($table);
        }

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('idnumber', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('useridnumber', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
        $table->add_field('deleted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('startdate', XMLDB_TYPE_CHAR, '100');
        $table->add_field('enddate', XMLDB_TYPE_CHAR, '100');
        $table->add_field('posidnumber', XMLDB_TYPE_CHAR, '100');
        $table->add_field('orgidnumber', XMLDB_TYPE_CHAR, '32');
        $table->add_field('appraiseridnumber', XMLDB_TYPE_CHAR, '100');
        $table->add_field('manageridnumber', XMLDB_TYPE_CHAR, '255');
        $table->add_field('managerjobassignmentidnumber', XMLDB_TYPE_CHAR, '255');
        $table->add_field('fullname', XMLDB_TYPE_CHAR, '255');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        /// Create the table
        $dbman->create_table($table);
    }

    public function test_nulls_ignored_emptystrings_erase() {
        $user = $this->getDataGenerator()->create_user(['idnumber' => 'user1']);
        \totara_job\job_assignment::create(
            ['userid' => $user->id, 'idnumber' => 'dev1', 'fullname' =>'Developer', 'totarasync' => 1]);
        \totara_job\job_assignment::create(
            ['userid' => $user->id, 'idnumber' => 'dev2', 'fullname' =>'Developer', 'totarasync' => 1]);
        \totara_job\job_assignment::create(
            ['userid' => $user->id, 'idnumber' => 'dev3', 'fullname' =>'Developer', 'totarasync' => 1]);

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->fullname = null;
        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $entry = new stdClass();
        $entry->idnumber = 'dev2';
        $entry->useridnumber = 'user1';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->fullname = 'Programmer';
        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $entry = new stdClass();
        $entry->idnumber = 'dev3';
        $entry->useridnumber = 'user1';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->fullname = '';
        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $source = new totara_sync_source_jobassignment_database();
        $source->set_config('import_fullname', '1');

        $element = new totara_sync_element_jobassignment();
        $element->set_config('allow_update', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($user->id);
        $this->assertCount(3, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $this->assertEquals('Developer', $jobassignments[1]->fullname);
        $this->assertEquals('dev2', $jobassignments[2]->idnumber);
        $this->assertEquals('Programmer', $jobassignments[2]->fullname);
        $this->assertEquals('dev3', $jobassignments[3]->idnumber);
        $this->assertEquals('Unnamed job assignment (ID: dev3)', $jobassignments[3]->fullname);
    }

    public function test_startdate_invalid() {
        $tenjune = totara_date_parse_from_format('d/m/Y', '10/06/2017');
        $user = $this->getDataGenerator()->create_user(['idnumber' => 'user1']);
        \totara_job\job_assignment::create(
            ['userid' => $user->id, 'idnumber' => 'dev1', 'startdate' => $tenjune, 'totarasync' => 1]);

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->startdate = 'xyz';
        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $source = new totara_sync_source_jobassignment_database();
        $source->set_config('import_startdate', '1');
        $source->set_config('database_dateformat', 'd/m/Y');

        $element = new totara_sync_element_jobassignment();
        $element->set_config('allow_update', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($user->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $this->assertEquals($tenjune, $jobassignments[1]->startdate);
    }

    public function test_startdate_timestamp() {
        $tenjune = totara_date_parse_from_format('d/m/Y', '10/06/2017');
        $user = $this->getDataGenerator()->create_user(['idnumber' => 'user1']);
        \totara_job\job_assignment::create(
            ['userid' => $user->id, 'idnumber' => 'dev1', 'startdate' => $tenjune, 'totarasync' => 1]);

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->startdate = 10000;
        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $source = new totara_sync_source_jobassignment_database();
        $source->set_config('import_startdate', '1');
        $source->set_config('database_dateformat', 'd/m/Y');

        $element = new totara_sync_element_jobassignment();
        $element->set_config('allow_update', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($user->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $this->assertEquals(10000, $jobassignments[1]->startdate);
    }

    public function test_startdate_formatted() {
        $tenjune = totara_date_parse_from_format('d/m/Y', '10/06/2017');
        $user = $this->getDataGenerator()->create_user(['idnumber' => 'user1']);
        \totara_job\job_assignment::create(
            ['userid' => $user->id, 'idnumber' => 'dev1', 'startdate' => $tenjune, 'totarasync' => 1]);

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->startdate = '15/06/2017';
        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $source = new totara_sync_source_jobassignment_database();
        $source->set_config('import_startdate', '1');
        $source->set_config('database_dateformat', 'd/m/Y');

        $element = new totara_sync_element_jobassignment();
        $element->set_config('allow_update', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($user->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $fifteenjune = totara_date_parse_from_format('d/m/Y', '15/06/2017');
        $this->assertEquals($fifteenjune, $jobassignments[1]->startdate);
    }

    public function test_startdate_zero() {
        $tenjune = totara_date_parse_from_format('d/m/Y', '10/06/2017');
        $user = $this->getDataGenerator()->create_user(['idnumber' => 'user1']);
        \totara_job\job_assignment::create(
            ['userid' => $user->id, 'idnumber' => 'dev1', 'startdate' => $tenjune, 'totarasync' => 1]);

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->startdate = '0';
        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $source = new totara_sync_source_jobassignment_database();
        $source->set_config('import_startdate', '1');
        $source->set_config('database_dateformat', 'd/m/Y');

        $element = new totara_sync_element_jobassignment();
        $element->set_config('allow_update', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($user->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $this->assertEquals(0, $jobassignments[1]->startdate);
    }

    public function test_startdate_empty() {
        $tenjune = totara_date_parse_from_format('d/m/Y', '10/06/2017');
        $user = $this->getDataGenerator()->create_user(['idnumber' => 'user1']);
        \totara_job\job_assignment::create(
            ['userid' => $user->id, 'idnumber' => 'dev1', 'startdate' => $tenjune, 'totarasync' => 1]);

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->startdate = '';
        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $source = new totara_sync_source_jobassignment_database();
        $source->set_config('import_startdate', '1');
        $source->set_config('database_dateformat', 'd/m/Y');

        $element = new totara_sync_element_jobassignment();
        $element->set_config('allow_update', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($user->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $this->assertEquals(0, $jobassignments[1]->startdate);
    }

    public function test_startdate_null() {
        $tenjune = totara_date_parse_from_format('d/m/Y', '10/06/2017');
        $user = $this->getDataGenerator()->create_user(['idnumber' => 'user1']);
        \totara_job\job_assignment::create(
            ['userid' => $user->id, 'idnumber' => 'dev1', 'startdate' => $tenjune, 'totarasync' => 1]);

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->startdate = null;
        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $source = new totara_sync_source_jobassignment_database();
        $source->set_config('import_startdate', '1');
        $source->set_config('database_dateformat', 'd/m/Y');

        $element = new totara_sync_element_jobassignment();
        $element->set_config('allow_update', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($user->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $this->assertEquals($tenjune, $jobassignments[1]->startdate);
    }
}
