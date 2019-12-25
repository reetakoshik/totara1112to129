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
 * @package tool_totara_sync
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/totara_sync/lib.php');

/**
 * @group tool_totara_sync
 */
class tool_totara_sync_user_csv_forcepasswordchange_testcase extends advanced_testcase {

    private $filedir = null;
    private $configcsv = array();
    private $config = array();

    protected function tearDown() {
        $this->filedir = null;
        $this->configcsv = null;
        $this->config = null;
        parent::tearDown();
    }

    public function setUp() {
        global $CFG;

        parent::setup();

        $this->resetAfterTest();
        $this->setAdminUser();

        $this->filedir = $CFG->dataroot . '/totara_sync';
        mkdir($this->filedir . '/csv/ready', 0777, true);

        set_config('element_user_enabled', 1, 'totara_sync');
        set_config('source_user', 'totara_sync_source_user_csv', 'totara_sync');
        set_config('fileaccess', FILE_ACCESS_DIRECTORY, 'totara_sync');
        set_config('filesdir', $this->filedir, 'totara_sync');

        $this->configcsv = array(
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
            'fieldmapping_jobassignmentenddate' => '',
            'fieldmapping_jobassignmentfullname' => '',
            'fieldmapping_jobassignmentidnumber' => '',
            'fieldmapping_jobassignmentstartdate' => '',
            'fieldmapping_lang' => '',
            'fieldmapping_lastname' => '',
            'fieldmapping_lastnamephonetic' => '',
            'fieldmapping_manageridnumber' => '',
            'fieldmapping_middlename' => '',
            'fieldmapping_orgidnumber' => '',
            'fieldmapping_password' => '',
            'fieldmapping_phone1' => '',
            'fieldmapping_phone2' => '',
            'fieldmapping_posidnumber' => '',
            'fieldmapping_suspended' => '',
            'fieldmapping_timemodified' => '',
            'fieldmapping_timezone' => '',
            'fieldmapping_url' => '',
            'fieldmapping_username' => '',
            'import_address' => '0',
            'import_alternatename' => '0',
            'import_appraiseridnumber' => '0',
            'import_auth' => '0',
            'import_city' => '0',
            'import_country' => '0',
            'import_deleted' => '0',
            'import_department' => '0',
            'import_description' => '0',
            'import_email' => '0',
            'import_emailstop' => '0',
            'import_firstname' => '1',
            'import_firstnamephonetic' => '0',
            'import_idnumber' => '1',
            'import_institution' => '0',
            'import_jobassignmentenddate' => '0',
            'import_jobassignmentfullname' => '0',
            'import_jobassignmentidnumber' => '0',
            'import_jobassignmentstartdate' => '0',
            'import_lang' => '0',
            'import_lastname' => '1',
            'import_lastnamephonetic' => '0',
            'import_manageridnumber' => '0',
            'import_middlename' => '0',
            'import_orgidnumber' => '0',
            'import_password' => '0',
            'import_phone1' => '0',
            'import_phone2' => '0',
            'import_posidnumber' => '0',
            'import_suspended' => '0',
            'import_timemodified' => '1',
            'import_timezone' => '0',
            'import_url' => '0',
            'import_username' => '1',
        );
        $this->config = array(
            'allow_create' => '1',
            'allow_delete' => '0',
            'allow_update' => '1',
            'allowduplicatedemails' => '0',
            'defaultsyncemail' => '',
            'forcepwchange' => '0',
            'undeletepwreset' => '0',
            'ignoreexistingpass' => '0',
            'sourceallrecords' => '0',
            'csvsaveemptyfields' => true,
        );
    }

    private function do_import($configcsv, $csvfile) {
        foreach ($configcsv as $k => $v) {
            set_config($k, $v, 'totara_sync_source_user_csv');
        }

        $elements = totara_sync_get_elements(true);
        /** @var totara_sync_element_user $element */
        $element = $elements['user'];

        $data = file_get_contents(__DIR__ . '/fixtures/' . $csvfile);
        $filepath = $this->filedir . '/csv/ready/user.csv';
        file_put_contents($filepath, $data);

        return $element->sync();
    }

    /**
     * Test syncs from csv file to check is forcepasswordchange is being set correctly.
     * We are checking both Manual Auth users and SSO type users.
     * For the SSO type users we are using CAS auth type.
     */
    public function test_sync_users_forcepasswordchange() {
        global $DB;

        $this->resetAfterTest();

        $config = array_merge($this->config, array('allow_update' => '1'));
        foreach ($config as $k => $v) {
            set_config($k, $v, 'totara_sync_element_user');
        }

        // Adding new Manual auth users with no password column in csv file.
        $result = $this->do_import(array_merge($this->configcsv, array('import_deleted' => '1', 'import_password' => '0', 'import_auth' => '0')), 'user_forcepasswordchange_1.csv');
        $this->assertTrue($result);

        $users = $DB->get_records('user', array(), 'id');
        $this->assertCount(4, $users); // First two users are guest and admin. Third and fourth were just created.
        $userids = array_keys($users);

        $this->assertTrue($DB->record_exists('user_preferences', array('userid' => $userids[2], 'name' => 'auth_forcepasswordchange', 'value' => 1)));
        $this->assertTrue($DB->record_exists('user_preferences', array('userid' => $userids[2], 'name' => 'create_password', 'value' => 1)));
        $this->assertTrue($DB->record_exists('user_preferences', array('userid' => $userids[3], 'name' => 'auth_forcepasswordchange', 'value' => 1)));
        $this->assertTrue($DB->record_exists('user_preferences', array('userid' => $userids[3], 'name' => 'create_password', 'value' => 1)));

        // Adding new Manual auth users with a password column in csv file but with no password content.
        $result = $this->do_import(array_merge($this->configcsv, array('import_deleted' => '1', 'import_password' => '1', 'import_auth' => '0')), 'user_forcepasswordchange_2.csv');
        $this->assertTrue($result);

        $users = $DB->get_records('user', array(), 'id');
        $this->assertCount(6, $users);
        $userids = array_keys($users);

        $this->assertTrue($DB->record_exists('user_preferences', array('userid' => $userids[4], 'name' => 'auth_forcepasswordchange', 'value' => 1)));
        $this->assertTrue($DB->record_exists('user_preferences', array('userid' => $userids[4], 'name' => 'create_password', 'value' => 1)));
        $this->assertTrue($DB->record_exists('user_preferences', array('userid' => $userids[5], 'name' => 'auth_forcepasswordchange', 'value' => 1)));
        $this->assertTrue($DB->record_exists('user_preferences', array('userid' => $userids[5], 'name' => 'create_password', 'value' => 1)));

        // Adding new manual auth users with a password column and password in csv file.
        $result = $this->do_import(array_merge($this->configcsv, array('import_deleted' => '1', 'import_password' => '1', 'import_auth' => '0')), 'user_forcepasswordchange_3.csv');
        $this->assertTrue($result);

        $users = $DB->get_records('user', array(), 'id');
        $this->assertCount(8, $users);
        $userids = array_keys($users);

        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[6], 'name' => 'auth_forcepasswordchange', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[6], 'name' => 'create_password', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[7], 'name' => 'auth_forcepasswordchange', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[7], 'name' => 'create_password', 'value' => 1)));

        // Updating existing Manual auth users with no password column in csv file.
        $result = $this->do_import(array_merge($this->configcsv, array('import_deleted' => '1', 'import_password' => '0', 'import_auth' => '0')), 'user_forcepasswordchange_4.csv');
        $this->assertTrue($result);

        $users = $DB->get_records('user', array(), 'id');
        $this->assertCount(8, $users);
        $userids = array_keys($users);

        $this->assertTrue($DB->record_exists('user_preferences', array('userid' => $userids[2], 'name' => 'auth_forcepasswordchange', 'value' => 1)));
        $this->assertTrue($DB->record_exists('user_preferences', array('userid' => $userids[2], 'name' => 'create_password', 'value' => 1)));
        $this->assertTrue($DB->record_exists('user_preferences', array('userid' => $userids[3], 'name' => 'auth_forcepasswordchange', 'value' => 1)));
        $this->assertTrue($DB->record_exists('user_preferences', array('userid' => $userids[3], 'name' => 'create_password', 'value' => 1)));

        // Updating existing Manual auth users with a password column in csv file but with no password content.
        $result = $this->do_import(array_merge($this->configcsv, array('import_deleted' => '1', 'import_password' => '1', 'import_auth' => '0')), 'user_forcepasswordchange_5.csv');
        $this->assertTrue($result);

        $users = $DB->get_records('user', array(), 'id');
        $this->assertCount(8, $users);
        $userids = array_keys($users);

        $this->assertTrue($DB->record_exists('user_preferences', array('userid' => $userids[4], 'name' => 'auth_forcepasswordchange', 'value' => 1)));
        $this->assertTrue($DB->record_exists('user_preferences', array('userid' => $userids[4], 'name' => 'create_password', 'value' => 1)));
        $this->assertTrue($DB->record_exists('user_preferences', array('userid' => $userids[5], 'name' => 'auth_forcepasswordchange', 'value' => 1)));
        $this->assertTrue($DB->record_exists('user_preferences', array('userid' => $userids[5], 'name' => 'create_password', 'value' => 1)));

        // Updating existing Manual auth users with a password column and password in csv file.
        $result = $this->do_import(array_merge($this->configcsv, array('import_deleted' => '1', 'import_password' => '1', 'import_auth' => '0')), 'user_forcepasswordchange_6.csv');
        $this->assertTrue($result);

        $users = $DB->get_records('user', array(), 'id');
        $this->assertCount(8, $users);
        $userids = array_keys($users);

        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[6], 'name' => 'auth_forcepasswordchange', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[6], 'name' => 'create_password', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[7], 'name' => 'auth_forcepasswordchange', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[7], 'name' => 'create_password', 'value' => 1)));

        // Adding new CAS auth users with no password column in csv file.
        $result = $this->do_import(array_merge($this->configcsv, array('import_deleted' => '1', 'import_password' => '0', 'import_auth' => '1')), 'user_forcepasswordchange_7.csv');
        $this->assertTrue($result);

        $users = $DB->get_records('user', array(), 'id');
        $this->assertCount(10, $users);
        $userids = array_keys($users);

        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[8], 'name' => 'auth_forcepasswordchange', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[8], 'name' => 'create_password', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[9], 'name' => 'auth_forcepasswordchange', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[9], 'name' => 'create_password', 'value' => 1)));

        // Adding new CAS auth users with a password column in csv file but with no password content.
        $result = $this->do_import(array_merge($this->configcsv, array('import_deleted' => '1', 'import_password' => '1', 'import_auth' => '1')), 'user_forcepasswordchange_8.csv');
        $this->assertTrue($result);

        $users = $DB->get_records('user', array(), 'id');
        $this->assertCount(12, $users);
        $userids = array_keys($users);

        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[10], 'name' => 'auth_forcepasswordchange', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[10], 'name' => 'create_password', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[11], 'name' => 'auth_forcepasswordchange', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[11], 'name' => 'create_password', 'value' => 1)));

        // Adding new CAS auth users with a password column and password in csv file.
        $result = $this->do_import(array_merge($this->configcsv, array('import_deleted' => '1', 'import_password' => '1', 'import_auth' => '1')), 'user_forcepasswordchange_9.csv');
        $this->assertTrue($result);

        $users = $DB->get_records('user', array(), 'id');
        $this->assertCount(14, $users);
        $userids = array_keys($users);

        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[12], 'name' => 'auth_forcepasswordchange', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[12], 'name' => 'create_password', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[13], 'name' => 'auth_forcepasswordchange', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[13], 'name' => 'create_password', 'value' => 1)));

        // Updating existing CAS auth users with no password column in csv file.
        $result = $this->do_import(array_merge($this->configcsv, array('import_deleted' => '1', 'import_password' => '0', 'import_auth' => '1')), 'user_forcepasswordchange_10.csv');
        $this->assertTrue($result);

        $users = $DB->get_records('user', array(), 'id');
        $this->assertCount(14, $users);
        $userids = array_keys($users);

        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[8], 'name' => 'auth_forcepasswordchange', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[8], 'name' => 'create_password', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[9], 'name' => 'auth_forcepasswordchange', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[9], 'name' => 'create_password', 'value' => 1)));

        // Updating existing CAS auth users with a password column in csv file but with no password content.
        $result = $this->do_import(array_merge($this->configcsv, array('import_deleted' => '1', 'import_password' => '1', 'import_auth' => '1')), 'user_forcepasswordchange_11.csv');
        $this->assertTrue($result);

        $users = $DB->get_records('user', array(), 'id');
        $this->assertCount(14, $users);
        $userids = array_keys($users);

        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[10], 'name' => 'auth_forcepasswordchange', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[10], 'name' => 'create_password', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[11], 'name' => 'auth_forcepasswordchange', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[11], 'name' => 'create_password', 'value' => 1)));

        // Updating existing CAS auth users with a password column and password in csv file.
        $result = $this->do_import(array_merge($this->configcsv, array('import_deleted' => '1', 'import_password' => '1', 'import_auth' => '1')), 'user_forcepasswordchange_12.csv');
        $this->assertFalse($result);

        $users = $DB->get_records('user', array(), 'id');
        $this->assertCount(14, $users);
        $userids = array_keys($users);

        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[12], 'name' => 'auth_forcepasswordchange', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[12], 'name' => 'create_password', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[13], 'name' => 'auth_forcepasswordchange', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[13], 'name' => 'create_password', 'value' => 1)));

        // Update manual auth users to become CAS auth users, with no password column in csv file.
        $result = $this->do_import(array_merge($this->configcsv, array('import_deleted' => '1', 'import_password' => '0', 'import_auth' => '1')), 'user_forcepasswordchange_13.csv');
        $this->assertTrue($result);

        $users = $DB->get_records('user', array(), 'id');
        $this->assertCount(14, $users);
        $userids = array_keys($users);

        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[2], 'name' => 'auth_forcepasswordchange', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[2], 'name' => 'create_password', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[3], 'name' => 'auth_forcepasswordchange', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[3], 'name' => 'create_password', 'value' => 1)));

        // Update manual auth users to become CAS auth users, with a password column in csv file but with no password content.
        $result = $this->do_import(array_merge($this->configcsv, array('import_deleted' => '1', 'import_password' => '1', 'import_auth' => '1')), 'user_forcepasswordchange_14.csv');
        $this->assertTrue($result);

        $users = $DB->get_records('user', array(), 'id');
        $this->assertCount(14, $users);
        $userids = array_keys($users);

        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[4], 'name' => 'auth_forcepasswordchange', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[4], 'name' => 'create_password', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[5], 'name' => 'auth_forcepasswordchange', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[5], 'name' => 'create_password', 'value' => 1)));

        // Update manual auth users to become CAS auth users, with a password column and password in csv file.
        $result = $this->do_import(array_merge($this->configcsv, array('import_deleted' => '1', 'import_password' => '1', 'import_auth' => '1')), 'user_forcepasswordchange_15.csv');
        $this->assertFalse($result);

        $users = $DB->get_records('user', array(), 'id');
        $this->assertCount(14, $users);
        $userids = array_keys($users);

        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[6], 'name' => 'auth_forcepasswordchange', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[6], 'name' => 'create_password', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[7], 'name' => 'auth_forcepasswordchange', 'value' => 1)));
        $this->assertFalse($DB->record_exists('user_preferences', array('userid' => $userids[7], 'name' => 'create_password', 'value' => 1)));
    }
}
