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

/**
 * Class tool_totara_sync_user_csv_check_sanity_testcase
 *
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose tool_totara_sync_user_csv_check_sanity_testcase admin/tool/totara_sync/tests/user_csv_check_sanity_test.php
 *
 * @group tool_totara_sync
 */
class tool_totara_sync_user_csv_check_sanity_testcase extends advanced_testcase {

    private $filedir = null;
    /**
     * @var totara_sync_element_user
     */
    private $element;
    private $synctable;
    private $synctable_clone;

    protected function tearDown() {
        $this->filedir = null;
        $this->element = null;
        $this->synctable = null;
        $this->synctable_clone = null;
        parent::tearDown();
    }

    /**
     * Configure records, with one faulty record for each sub-check.
     */
    public function setUp() {
        global $CFG;

        parent::setup();

        $this->resetAfterTest(true);
        $this->preventResetByRollback();
        $this->setAdminUser();

        // Retain idnumber when deleting users.
        set_config('authdeleteusers', 'partial');

        // Set up the existing data first.
        // Causes the 7th record to fail due to existing user with the same username (and different idnumber).
        $this->getDataGenerator()->create_user(array('idnumber' => 'idx1', 'username' => 'user0007'));
        // This user is deleted and we try to undelete, but allow_create is off, so fail.
        $user13 = $this->getDataGenerator()->create_user(array('idnumber' => 'idnum013', 'totarasync' => 1));
        delete_user($user13);
        // Causes the 17th and 35th records to fail due to existing user with the same email address (and different idnumber).
        $this->getDataGenerator()->create_user(array('idnumber' => 'idx2', 'email' => 'e17@x.nz'));
        // Causes the 30th record to fail due to existing user with totara sync flag turned off.
        $this->getDataGenerator()->create_user(array('idnumber' => 'idnum030', 'totarasync' => 0));

        // Next create a valid pos and org.
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $orgframework = $hierarchy_generator->create_org_frame(array());
        $posframework = $hierarchy_generator->create_pos_frame(array());
        $hierarchy_generator->create_org(array('frameworkid' => $orgframework->id, 'idnumber' => 'org1'));
        $hierarchy_generator->create_pos(array('frameworkid' => $posframework->id, 'idnumber' => 'pos1'));

        // Then configure the import data.
        $this->filedir = $CFG->dataroot . '/totara_sync';
        mkdir($this->filedir . '/csv/ready', 0777, true);

        set_config('element_user_enabled', 1, 'totara_sync');
        set_config('source_user', 'totara_sync_source_user_csv', 'totara_sync');
        set_config('fileaccess', FILE_ACCESS_DIRECTORY, 'totara_sync');
        set_config('filesdir', $this->filedir, 'totara_sync');

        $configcsv = array(
            'csvuserencoding' => 'UTF-8',
            'delimiter' => ',',
            'fieldmapping_address' => '',
            'fieldmapping_alternatename' => '',
            'fieldmapping_appraiseridnumber' => '',
            'fieldmapping_auth' => '',
            'fieldmapping_city' => '',
            'fieldmapping_country' => '',
            'fieldmapping_deleted' => '',
            'fieldmapping_department' => '',
            'fieldmapping_description' => '',
            'fieldmapping_email' => '',
            'fieldmapping_emailstop' => '',
            'fieldmapping_firstname' => '',
            'fieldmapping_firstnamephonetic' => '',
            'fieldmapping_idnumber' => '',
            'fieldmapping_institution' => '',
            'fieldmapping_lang' => '',
            'fieldmapping_lastname' => '',
            'fieldmapping_lastnamephonetic' => '',
            'fieldmapping_manageridnumber' => '',
            'fieldmapping_middlename' => '',
            'fieldmapping_orgidnumber' => '',
            'fieldmapping_password' => '',
            'fieldmapping_phone1' => '',
            'fieldmapping_phone2' => '',
            'fieldmapping_jobassignmentenddate' => '',
            'fieldmapping_jobassignmentidnumber' => '',
            'fieldmapping_jobassignmentstartdate' => '',
            'fieldmapping_postitle' => '',
            'fieldmapping_suspended' => '',
            'fieldmapping_timemodified' => '',
            'fieldmapping_timezone' => '',
            'fieldmapping_url' => '',
            'fieldmapping_username' => '',
            'import_address' => '0',
            'import_alternatename' => '0',
            'import_appraiseridnumber' => '1',
            'import_auth' => '0',
            'import_city' => '0',
            'import_country' => '1',
            'import_deleted' => '1',
            'import_department' => '0',
            'import_description' => '0',
            'import_email' => '1',
            'import_emailstop' => '0',
            'import_firstname' => '1',
            'import_firstnamephonetic' => '0',
            'import_idnumber' => '1',
            'import_institution' => '0',
            'import_lang' => '1',
            'import_lastname' => '1',
            'import_lastnamephonetic' => '0',
            'import_manageridnumber' => '1',
            'import_middlename' => '0',
            'import_orgidnumber' => '1',
            'import_password' => '0',
            'import_phone1' => '0',
            'import_phone2' => '0',
            'import_jobassignmentenddate' => '1',
            'import_posidnumber' => '1',
            'import_jobassignmentstartdate' => '1',
            'import_postitle' => '0',
            'import_suspended' => '0',
            'import_timemodified' => '1',
            'import_timezone' => '0',
            'import_url' => '0',
            'import_username' => '1',
        );
        $config = array(
            'allow_create' => '0', // We're not actually doing a sync, and one sub-check needs this set to 0.
            'allow_delete' => '0',
            'allow_update' => '1',
            'allowduplicatedemails' => '0',
            'defaultsyncemail' => '',
            'forcepwchange' => '0',
            'undeletepwreset' => '0',
            'ignoreexistingpass' => '0',
            'sourceallrecords' => '0',
        );

        foreach ($configcsv as $k => $v) {
            set_config($k, $v, 'totara_sync_source_user_csv');
        }

        foreach ($config as $k => $v) {
            set_config($k, $v, 'totara_sync_element_user');
        }

        $data = file_get_contents(__DIR__ . '/fixtures/user_check_sanity.csv');
        $filepath = $this->filedir . '/csv/ready/user.csv';
        file_put_contents($filepath, $data);

        // We can't run sync() because we need to see what happens half way through. So instead, we run the stuff that usually
        // happens at the start of sync(), everything before check_sanity() (which is actually not much when simplified).
        $elements = totara_sync_get_elements(true);
        /* @var totara_sync_element_user $element */
        $element = $elements['user'];
        $this->synctable = $element->get_source_sync_table();
        $this->synctable_clone = $element->get_source_sync_table_clone($this->synctable);
        $element->set_customfieldsdb();
        $this->element = $element;
    }

    /**
     * Run each sub-check on the records, checking that they find the problem and no others.
     */
    public function test_check_sanity_sub_checks() {
        global $DB;

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

        // Check invalid country.
        $badids = $element->check_invalid_countrycode($synctable);
        sort($badids);
        $this->assertEquals(array(32,33), $badids);
        $this->assertCount(25, $DB->get_records('totara_sync_log')); // Warning was logged.
    }

    /**
     * Run check_sanity, checking that it finds all of the problems. Because of the previous test, we can be sure that
     * each record was excluded for the correct reason and not just coincidence.
     */
    public function test_check_sanity() {
        global $DB;

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
