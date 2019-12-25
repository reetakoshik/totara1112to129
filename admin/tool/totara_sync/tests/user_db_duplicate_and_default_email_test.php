<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package tool_totara_sync
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/totara_sync/tests/source_database_testcase.php');
require_once($CFG->dirroot . '/admin/tool/totara_sync/sources/source_user_database.php');

/**
 * @group tool_totara_sync
 */
class tool_totara_sync_user_db_duplicate_and_default_email extends totara_sync_database_testcase {

    /**
     * Setup function
     */
    public function setUp() {
        $this->elementname = 'user';
        $this->sourcetable = 'totara_sync_user_source';

        parent::setUp();

        set_config('source_user', 'totara_sync_source_user_database', 'totara_sync');

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $this->create_external_db_table();
    }

    /**
     * Teardown function
     */
    public function tearDown() {
        $this->elementname = null;
        $this->sourcetable = null;

        parent::tearDown();
    }

    /**
     * Create table for external DB test
     */
    public function create_external_db_table() {
        $dbman = $this->ext_dbconnection->get_manager();
        $table = new xmldb_table($this->dbtable);

        // Drop table first, if it exists
        if ($dbman->table_exists($this->dbtable)) {
            $dbman->drop_table($table);
        }

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('idnumber', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('username', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('deleted', XMLDB_TYPE_INTEGER, '1');
        $table->add_field('firstname', XMLDB_TYPE_CHAR, '255');
        $table->add_field('lastname', XMLDB_TYPE_CHAR, '255');
        $table->add_field('email', XMLDB_TYPE_CHAR, '100');
        $table->add_field('suspended', XMLDB_TYPE_INTEGER, 1);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        $dbman->create_table($table);
    }

    /**
     * Get a user record and add profile field data
     *
     * @param $idnumber
     * @return bool|mixed
     */
    public function get_user($idnumber) {
        global $DB;
        return $DB->get_record('user', array('idnumber' => $idnumber));
    }

    /**
     * Test user email field without allowing duplicates.
     *
     */
    public function test_email_no_duplicates() {
        global $DB;

        // Create the source data, user1, valid non-duplicate email.
        $entry = new stdClass();
        $entry->idnumber = '1';
        $entry->username = 'user1';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->firstname = 'user1-firstname';
        $entry->lastname = 'user1-lastname';
        $entry->email = 'user1@email.com';
        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        // User2 has a duplicate email with user3. They should fail.
        $entry = new stdClass();
        $entry->idnumber = '2';
        $entry->username = 'user2';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->firstname = 'user2-firstname';
        $entry->lastname = 'user2-lastname';
        $entry->email = 'user2@email.com'; // Duplicate!
        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        // User3 has a duplicate email with user2. They should fail.
        $entry = new stdClass();
        $entry->idnumber = '3';
        $entry->username = 'user3';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->firstname = 'user3-firstname';
        $entry->lastname = 'user3-lastname';
        $entry->email = 'user2@email.com'; // Duplicate!
        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        // User4 has an invalid email. They should fail.
        $entry = new stdClass();
        $entry->idnumber = '4';
        $entry->username = 'user4';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->firstname = 'user4-firstname';
        $entry->lastname = 'user4-lastname';
        $entry->email = 'user4@test@email.com'; // Invalid!
        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        // Set the source config.
        $source = new totara_sync_source_user_database();
        $source->set_config('import_idnumber', '1');
        $source->set_config('import_username', '1');
        $source->set_config('import_firstname', '1');
        $source->set_config('import_lastname', '1');
        $source->set_config('import_email', '1');
        $source->set_config('import_deleted', '1');

        // Set the element config.
        $element = new totara_sync_element_user();
        $element->set_config('allow_create', '1');
        $element->set_config('allow_update', '1');
        $element->set_config('allow_delete', '1');
        $element->set_config('allowduplicatedemails', '0');
        $element->set_config('defaultsyncemail', '');
        $element->set_config('sourceallrecords', 0);

        // Run the sync and test.
        $element->sync();

        $this->assertCount(3, $DB->get_records('user')); // Check the correct count of users.

        // User 1 should be imported..
        $this->assertNotEmpty($this->get_user('1'));
        $this->assertEquals('created user 1',
            $DB->get_field('totara_sync_log', 'info', array('info' => 'created user 1')));

        // User 2 should not be imported, has a duplicate email.
        $this->assertEmpty($this->get_user('2'));
        $this->assertEquals('Duplicate users with email user2@email.com. Skipped user 2',
            $DB->get_field('totara_sync_log', 'info', array('info' => 'Duplicate users with email user2@email.com. Skipped user 2')));

        // User 3 should be imported, has a duplicate email.
        $this->assertEmpty($this->get_user('3'));
        $this->assertEquals('Duplicate users with email user2@email.com. Skipped user 3',
            $DB->get_field('totara_sync_log', 'info', array('info' => 'Duplicate users with email user2@email.com. Skipped user 3')));

        // User 4 should be imported, has an invalid email.
        $this->assertEmpty($this->get_user('4'));
        $this->assertEquals('Invalid email address. Skipped user 4',
            $DB->get_field('totara_sync_log', 'info', array('info' => 'Invalid email address. Skipped user 4')));

        // Check total count of error log entries.
        $this->assertEquals(6, count($DB->get_records('totara_sync_log')));

        // Delete all users from the source database and add a new user with a duplicate email.
        $this->ext_dbconnection->delete_records($this->dbtable);

        $entry = new stdClass();
        $entry->idnumber = '5';
        $entry->username = 'user5';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->firstname = 'user5-firstname';
        $entry->lastname = 'user5-lastname';
        $entry->email = 'user1@email.com'; // Duplicate with existing user.
        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $entry = new stdClass();
        $entry->idnumber = '6';
        $entry->username = 'user6';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->firstname = 'user6-firstname';
        $entry->lastname = 'user6-lastname';
        $entry->email = 'user6@email.com';
        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        // Run the sync and test.
        $element->sync();

        $this->assertCount(4, $DB->get_records('user')); // Check the correct count of users.

        // User 5 should not be imported, has a duplicate email.
        $this->assertEmpty($this->get_user('5'));
        $this->assertEquals('Email user1@email.com is already registered. Skipped user 5',
            $DB->get_field('totara_sync_log', 'info', array('info' => 'Email user1@email.com is already registered. Skipped user 5')));

        // User 6 should be imported..
        $this->assertNotEmpty($this->get_user('6'));
        $this->assertEquals('created user 6',
            $DB->get_field('totara_sync_log', 'info', array('info' => 'created user 6')));

        // Check total count of error log entries.
        $this->assertEquals(10, count($DB->get_records('totara_sync_log')));
    }

    /**
     * Test user email field allowing duplicates but without setting a default email.
     *
     */
    public function test_email_allow_duplicates() {
        global $DB;

        // Create the source data.
        $entry = new stdClass();
        $entry->idnumber = '1';
        $entry->username = 'user1';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->firstname = 'user1-firstname';
        $entry->lastname = 'user1-lastname';
        $entry->email = 'user1@email.com';
        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        // User2 has a duplicate email with user3. They should fail.
        $entry = new stdClass();
        $entry->idnumber = '2';
        $entry->username = 'user2';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->firstname = 'user2-firstname';
        $entry->lastname = 'user2-lastname';
        $entry->email = 'user2@email.com'; // Duplicate!
        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        // User3 has a duplicate email with user2. They should fail.
        $entry = new stdClass();
        $entry->idnumber = '3';
        $entry->username = 'user3';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->firstname = 'user3-firstname';
        $entry->lastname = 'user3-lastname';
        $entry->email = 'user2@email.com'; // Duplicate!
        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        // User4 has an invalid email. They should fail.
        $entry = new stdClass();
        $entry->idnumber = '4';
        $entry->username = 'user4';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->firstname = 'user4-firstname';
        $entry->lastname = 'user4-lastname';
        $entry->email = 'user4@test@email.com'; // Invalid!
        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        // Set the source config.
        $source = new totara_sync_source_user_database();
        $source->set_config('import_idnumber', '1');
        $source->set_config('import_username', '1');
        $source->set_config('import_firstname', '1');
        $source->set_config('import_lastname', '1');
        $source->set_config('import_email', '1');
        $source->set_config('import_deleted', '1');

        // Set the element config.
        $element = new totara_sync_element_user();
        $element->set_config('allow_create', '1');
        $element->set_config('allow_update', '1');
        $element->set_config('allow_delete', '1');
        $element->set_config('allowduplicatedemails', '1');
        $element->set_config('defaultsyncemail', '');
        $element->set_config('sourceallrecords', 0);

        // Run the sync and test.
        $element->sync();

        $this->assertCount(5, $DB->get_records('user')); // Check the correct count of users.

        // User 1 should be imported.
        $this->assertNotEmpty($this->get_user('1'));
        $this->assertEquals('created user 1',
            $DB->get_field('totara_sync_log', 'info', array('info' => 'created user 1')));

        // User 2 should be imported,.
        $this->assertNotEmpty($this->get_user('2'));
        $this->assertEquals('created user 2',
            $DB->get_field('totara_sync_log', 'info', array('info' => 'created user 2')));

        // User 3 should be imported.
        $this->assertNotEmpty($this->get_user('3'));
        $this->assertEquals('created user 3',
            $DB->get_field('totara_sync_log', 'info', array('info' => 'created user 3')));

        // User 4 should be imported, has an invalid email.
        $this->assertEmpty($this->get_user('4'));
        $this->assertEquals('Invalid email address. Skipped user 4',
            $DB->get_field('totara_sync_log', 'info', array('info' => 'Invalid email address. Skipped user 4')));

        // Check total count of error log entries.
        $this->assertEquals(6, count($DB->get_records('totara_sync_log')));

        // Delete all users from the source database and add a new user with a duplicate email.
        $this->ext_dbconnection->delete_records($this->dbtable);

        $entry = new stdClass();
        $entry->idnumber = '5';
        $entry->username = 'user5';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->firstname = 'user5-firstname';
        $entry->lastname = 'user5-lastname';
        $entry->email = 'user1@email.com'; // Duplicate with existing user.
        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $entry = new stdClass();
        $entry->idnumber = '6';
        $entry->username = 'user6';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->firstname = 'user6-firstname';
        $entry->lastname = 'user6-lastname';
        $entry->email = 'user6@email.com';
        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        // Run the sync and test.
        $element->sync();

        $this->assertCount(7, $DB->get_records('user')); // Check the correct count of users.

        // User 5 should be imported.
        $this->assertNotEmpty($this->get_user('5'));
        $this->assertEquals('created user 5',
            $DB->get_field('totara_sync_log', 'info', array('info' => 'created user 5')));

        // User 6 should be imported.
        $this->assertNotEmpty($this->get_user('6'));
        $this->assertEquals('created user 6',
            $DB->get_field('totara_sync_log', 'info', array('info' => 'created user 6')));

        // Check total count of error log entries.
        $this->assertEquals(10, count($DB->get_records('totara_sync_log')));
    }

    /**
     * Test user email field allowing duplicates with setting a default email for empty emails.
     *
     */
    public function test_email_allow_duplicates_with_default_email() {
        global $DB;

        // Create the source data.
        $entry = new stdClass();
        $entry->idnumber = '1';
        $entry->username = 'user1';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->firstname = 'user1-firstname';
        $entry->lastname = 'user1-lastname';
        $entry->email = 'user1@email.com';
        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        // User2 has a duplicate email with user3. They should fail.
        $entry = new stdClass();
        $entry->idnumber = '2';
        $entry->username = 'user2';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->firstname = 'user2-firstname';
        $entry->lastname = 'user2-lastname';
        $entry->email = 'user2@email.com'; // Duplicate!
        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        // User3 has a duplicate email with user2. They should fail.
        $entry = new stdClass();
        $entry->idnumber = '3';
        $entry->username = 'user3';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->firstname = 'user3-firstname';
        $entry->lastname = 'user3-lastname';
        $entry->email = 'user2@email.com'; // Duplicate!
        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        // User4 has an invalid email. They should fail.
        $entry = new stdClass();
        $entry->idnumber = '4';
        $entry->username = 'user4';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->firstname = 'user4-firstname';
        $entry->lastname = 'user4-lastname';
        $entry->email = 'user4@test@email.com'; // Invalid! . The default should be used.
        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        // User5 has a empty email. The default should be used.
        $entry = new stdClass();
        $entry->idnumber = '5';
        $entry->username = 'user5';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->firstname = 'user5-firstname';
        $entry->lastname = 'user5-lastname';
        $entry->email = ''; // Blank email. The default should be used.
        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        // User6 has a empty email. The default should be used.
        $entry = new stdClass();
        $entry->idnumber = '6';
        $entry->username = 'user6';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->firstname = 'user6-firstname';
        $entry->lastname = 'user6-lastname';
        $entry->email = ''; // Blank email. The default should be used.
        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        // Set the source config.
        $source = new totara_sync_source_user_database();
        $source->set_config('import_idnumber', '1');
        $source->set_config('import_username', '1');
        $source->set_config('import_firstname', '1');
        $source->set_config('import_lastname', '1');
        $source->set_config('import_email', '1');
        $source->set_config('import_deleted', '1');

        // Set the element config.
        $element = new totara_sync_element_user();
        $element->set_config('allow_create', '1');
        $element->set_config('allow_update', '1');
        $element->set_config('allow_delete', '1');
        $element->set_config('allowduplicatedemails', '1');
        $element->set_config('defaultsyncemail', 'default@email.com');
        $element->set_config('sourceallrecords', 0);

        // Run the sync and test.
        $element->sync();

        $this->assertCount(8, $DB->get_records('user')); // Check the correct count of users.

        // User 1 should be imported.
        $user = $this->get_user('1');
        $this->assertNotEmpty($user);
        $this->assertEquals('user1@email.com', $user->email);
        $this->assertEquals('created user 1',
            $DB->get_field('totara_sync_log', 'info', array('info' => 'created user 1')));

        // User 2 should be imported,.
        $user = $this->get_user('2');
        $this->assertNotEmpty($user);
        $this->assertEquals('user2@email.com', $user->email);
        $this->assertEquals('created user 2',
            $DB->get_field('totara_sync_log', 'info', array('info' => 'created user 2')));

        // User 3 should be imported.
        $user = $this->get_user('3');
        $this->assertNotEmpty($user);
        $this->assertEquals('user2@email.com', $user->email); // Using a duplicated email.
        $this->assertEquals('created user 3',
            $DB->get_field('totara_sync_log', 'info', array('info' => 'created user 3')));

        // User 4 has an invalid email. should be imported setting the default email
        $user = $this->get_user('4');
        $this->assertNotEmpty($user);
        $this->assertEquals('default@email.com', $user->email);
        $this->assertEquals('created user 4',
            $DB->get_field('totara_sync_log', 'info', array('info' => 'created user 4')));

        // User 5 should be imported.
        $user = $this->get_user('5');
        $this->assertNotEmpty($user);
        $this->assertEquals('default@email.com', $user->email);
        $this->assertEquals('created user 5',
            $DB->get_field('totara_sync_log', 'info', array('info' => 'created user 5')));

        // User 6 should be imported.
        $user = $this->get_user('6');
        $this->assertNotEmpty($user);
        $this->assertEquals('default@email.com', $user->email);
        $this->assertEquals('created user 6',
            $DB->get_field('totara_sync_log', 'info', array('info' => 'created user 6')));

        // Check total count of error log entries.
        $this->assertEquals(8, count($DB->get_records('totara_sync_log')));
    }
}
