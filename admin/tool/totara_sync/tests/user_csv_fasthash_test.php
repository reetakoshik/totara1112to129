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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package tool_totara_sync
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/totara_sync/tests/user_csv_test.php');

/**
 * @group tool_totara_sync
 */
class tool_totara_sync_user_csv_fasthash_testcase extends advanced_testcase {

    private $filedir = null;
    private $configcsv = array();
    private $config = array();

    protected function tearDown() {
        $this->filedir = null;
        $this->configcsv = array();
        $this->config = array();

        parent::tearDown();
    }

    protected function setUp() {
        global $CFG;

        parent::setup();

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $this->filedir = $CFG->dataroot . '/totara_sync';
        mkdir($this->filedir . '/csv/ready', 0777, true);

        set_config('element_user_enabled', 1, 'totara_sync');
        set_config('source_user', 'totara_sync_source_user_csv', 'totara_sync');
        set_config('fileaccess', 0, 'totara_sync');
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
            'import_deleted' => '1',
            'import_department' => '0',
            'import_description' => '0',
            'import_email' => '1',
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
            'import_password' => '1',
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


    public function test_fasthash_disabled() {
        global $CFG, $DB;

        $this->resetAfterTest();

        // We need to import the password to check the hashing.
        foreach ($this->configcsv as $k => $v) {
            set_config($k, $v, 'totara_sync_source_user_csv');
        }

        foreach ($this->config as $k => $v) {
            set_config($k, $v, 'totara_sync_element_user');
        }

        // Disable fasthash.
        $CFG->tool_totara_sync_enable_fasthash = false;

        $elements = totara_sync_get_elements(true);
        /** @var totara_sync_element_user $element */
        $element = $elements['user'];

        $data = file_get_contents(__DIR__ . '/fixtures/user_password_1.csv');
        $filepath = $this->filedir . '/csv/ready/user.csv';
        file_put_contents($filepath, $data);

        $result = $element->sync();
        $this->assertTrue($result);

        $users = $DB->get_records('user');
        $this->assertCount(5, $users);

        $user1 = $DB->get_record('user', array('idnumber' => 'imp001'));

        // Check password is correct (import001).
        $this->assertTrue(password_verify('import001', $user1->password));
    }

    public function test_fasthash_enabled() {
        global $CFG, $DB;

        $this->resetAfterTest();

        // We need to import the password to check the hashing.
        foreach ($this->configcsv as $k => $v) {
            set_config($k, $v, 'totara_sync_source_user_csv');
        }

        foreach ($this->config as $k => $v) {
            set_config($k, $v, 'totara_sync_element_user');
        }

        // Ensure fasthash is enabled.
        $CFG->tool_totara_sync_enable_fasthash = true;

        $elements = totara_sync_get_elements(true);
        /** @var totara_sync_element_user $element */
        $element = $elements['user'];

        $data = file_get_contents(__DIR__ . '/fixtures/user_password_1.csv');
        $filepath = $this->filedir . '/csv/ready/user.csv';
        file_put_contents($filepath, $data);

        $result = $element->sync();
        $this->assertTrue($result);

        $users = $DB->get_records('user');
        $this->assertCount(5, $users);

        $user1 = $DB->get_record('user', array('idnumber' => 'imp001'));
        $user2 = $DB->get_record('user', array('idnumber' => 'imp002'));
        $user3 = $DB->get_record('user', array('idnumber' => 'imp003'));

        // Check password is correct (import001).
        $this->assertTrue(password_verify('import001', $user1->password));
        $this->assertTrue(password_verify('import002', $user2->password));
        $this->assertTrue(password_verify('import003', $user3->password));

        // Do another sync and check that the password is updated
        // correctly.
        $data = file_get_contents(__DIR__ . '/fixtures/user_password_2.csv');
        $filepath = $this->filedir . '/csv/ready/user.csv';
        file_put_contents($filepath, $data);

        $result = $element->sync();
        $this->assertTrue($result);

        $users = $DB->get_records('user');
        $this->assertCount(5, $users);

        $user1 = $DB->get_record('user', array('idnumber' => 'imp001'));
        $user2 = $DB->get_record('user', array('idnumber' => 'imp002'));
        $user3 = $DB->get_record('user', array('idnumber' => 'imp003'));

        // Check password is correct.
        $this->assertTrue(password_verify('import001', $user1->password));
        $this->assertTrue(password_verify('newpassword2', $user2->password));
        $this->assertTrue(password_verify('newpassword3', $user3->password));
    }
}
