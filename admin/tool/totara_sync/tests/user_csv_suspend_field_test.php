<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Alastair Munro <alatair.munro@totaralearning.com>
 * @package tool_totara_sync
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/totara_sync/lib.php');
require_once($CFG->dirroot . '/admin/tool/totara_sync/elements/user.php');

class tool_totara_sync_user_csv_suspend_field_testcase extends advanced_testcase {

    private $filedir = null;
    private $element;
    private $synctable;
    private $synctable_clone;
    private $user = null;

    protected function tearDown() {
        $this->filedir = null;
        $this->element = null;
        $this->synctable = null;
        $this->synctable_clone = null;
        $this->user = null;
        parent::tearDown();
    }

    /**
     * Configure records, with one faulty record for each sub-check.
     */
    public function setUp() {
        global $CFG, $DB;

        parent::setup();

        $this->resetAfterTest(true);
        $this->preventResetByRollback();
        $this->setAdminUser();

        $this->getDataGenerator()->create_user(array('idnumber' => '1', 'username' => 'user1', 'totarasync' => '1'));

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
            'import_lang' => '0',
            'import_lastname' => '1',
            'import_lastnamephonetic' => '0',
            'import_manageridnumber' => '0',
            'import_middlename' => '0',
            'import_orgidnumber' => '0',
            'import_password' => '0',
            'import_phone1' => '0',
            'import_phone2' => '0',
            'import_jobassignmentenddate' => '0',
            'import_posidnumber' => '0',
            'import_jobassignmentstartdate' => '0',
            'import_postitle' => '0',
            'import_suspended' => '1',
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

        $this->user = $DB->get_record('user', array('idnumber' => 1));
    }

    private function get_element() {

        $elements = totara_sync_get_elements(true);
        /** @var totara_sync_element_user $element */
        $element = $elements['user'];

        return $element;
    }

    /**
     * Test user is not suspeded by setting suspended column to 1 when
     * delete setting is set to SUSPEND_USERS.
     */
    public function test_user_not_suspended() {
        global $DB;

        $this->load_user_csv_file('user_suspended_1.csv');

        $this->assertTrue($this->get_element()->sync());

        $user = $DB->get_record('user', array('idnumber' => '1'));
        $this->assertEquals(0, $user->suspended);

        // Change the delete setting and try again.
        set_config('allow_delete', totara_sync_element_user::SUSPEND_USERS, 'totara_sync_element_user');

        $this->load_user_csv_file('user_suspended_2.csv');

        $this->assertTrue($this->get_element()->sync());

        $user = $DB->get_record('user', array('idnumber' => '1'));
        $this->assertEquals('user1', $user->username);
        $this->assertEquals(0, $user->suspended);
    }

    /**
     * Test using the suspended field when allow_delete is set to
     * different settings.
     * DELETE_USERS: should suspend the user
     * SUSPEND_USERS: should ignore the suspended field
     */
    public function test_user_suspended() {
        global $DB;

        // Make sure user is not suspended
        $user = $DB->get_record('user', array('id' => $this->user->id));
        $this->assertEquals('0', $user->suspended);

        $this->load_user_csv_file('user_suspended_2.csv');

        $this->assertTrue($this->get_element()->sync());

        $user = $DB->get_record('user', array('id' => $this->user->id));
        $this->assertEquals('user1', $user->username);
        $this->assertEquals(1, $user->suspended);

        // Unsuspend the user, change the delete setting and try again.
        $data = array(
            'id' => $this->user->id,
            'suspended' => 0
        );
        $DB->update_record('user', $data);

        $user = $DB->get_record('user', array('id' => $this->user->id));
        $this->assertEquals('0', $user->suspended);

        set_config('allow_delete', totara_sync_element_user::SUSPEND_USERS, 'totara_sync_element_user');

        $this->load_user_csv_file('user_suspended_2.csv');

        $this->assertTrue($this->get_element()->sync());

        $user = $DB->get_record('user', array('id' => $this->user->id));
        $this->assertEquals('user1', $user->username);
        // This is still zero as suspend is ignored when we have deleted set to
        // SUSPEND_USERS.
        $this->assertEquals(0, $user->suspended);
    }

    /**
     * Test user is correctly unsuspended and ignoring the suspended
     * field occurs when allow_delete is set to suspend users
     */
    public function test_user_unsuspend_user() {
        global $DB;

        // Suspend user1
        $data = array(
            'id' => $this->user->id,
            'suspended' => 1
        );
        $DB->update_record('user', $data);

        $user = $DB->get_record('user', array('id' => $this->user->id));
        $this->assertEquals('1', $user->suspended);

        $this->load_user_csv_file('user_suspended_1.csv');

        $this->assertTrue($this->get_element()->sync());

        $user = $DB->get_record('user', array('idnumber' => '1'));
        $this->assertEquals('user1', $user->username);
        $this->assertEquals(0, $user->suspended);

        // Change the delete setting and try again, this time with a file
        // that has a suspend setting of 1 which is ignored.
        set_config('allow_delete', totara_sync_element_user::SUSPEND_USERS, 'totara_sync_element_user');

        $this->load_user_csv_file('user_suspended_2.csv');

        $this->assertTrue($this->get_element()->sync());

        $user = $DB->get_record('user', array('id' => $this->user->id));
        $this->assertEquals('user1', $user->username);
        $this->assertEquals(0, $user->suspended);
    }

	/**
     * Test deleting data in suspended field using empty field in CSV,
     * the user should remain suspended as we are ignoring empty fields.
     * Once we change the delete setting the suspend field is ignored and the
     * will be un-suspended.
     */
    public function test_user_suspended_empty_fields_ignored() {
        global $DB;
        // Set save empty fields to off
        set_config('csvsaveemptyfields', '0', 'totara_sync_element_user');
        set_config('allow_delete', totara_sync_element_user::DELETE_USERS, 'totara_sync_element_user');

        // Suspend user1
        $data = array(
            'id' => $this->user->id,
            'suspended' => 1
        );
        $DB->update_record('user', $data);

        $user = $DB->get_record('user', array('id' => $this->user->id));
        $this->assertEquals('1', $user->suspended);

        // Suspended file in CSV is empty
        $this->load_user_csv_file('user_suspended_3.csv');

        $this->assertTrue($this->get_element()->sync());

        // User will remain suspended as the empty suspended field is ignored
        $user = $DB->get_record('user', array('id' => $this->user->id));
        $this->assertEquals('user1', $user->username);
        $this->assertEquals(1, $user->suspended);

        // Change deletion method and try again
        set_config('allow_delete', totara_sync_element_user::SUSPEND_USERS, 'totara_sync_element_user');

        // Re-suspend user1
        $data = array(
            'id' => $this->user->id,
            'suspended' => 1
        );
        $DB->update_record('user', $data);

        $user = $DB->get_record('user', array('id' => $this->user->id));
        $this->assertEquals('1', $user->suspended);

        // Suspended field in CSV is empty
        $this->load_user_csv_file('user_suspended_3.csv');

        $this->assertTrue($this->get_element()->sync());

        // The suspended field is not included this time so use should be
        // un-deleted (un-suspended) based on the delete setting.
        $user = $DB->get_record('user', array('id' => $this->user->id));
        $this->assertEquals('user1', $user->username);
        $this->assertEquals(0, $user->suspended);
    }

    /**
     * Test deleting data in suspended field using empty field in CSV,
     * the user should be un-suspended saving the empty suspended field.
     *
     * Once we change the delete setting the empty suspended field will
     * be saved and the user will be un-suspended.
     *
     */
    public function test_user_suspended_empty_fields_saved() {
        global $DB;

        // Set save empty fields
        set_config('csvsaveemptyfields', '1', 'totara_sync_element_user');

        // Suspend user1
        $data = array(
            'id' => $this->user->id,
            'suspended' => 1
        );
        $DB->update_record('user', $data);

        $user = $DB->get_record('user', array('id' => $this->user->id));
        $this->assertEquals('1', $user->suspended);

        // Suspended field in CSV is empty
        $this->load_user_csv_file('user_suspended_3.csv');

        $this->assertTrue($this->get_element()->sync());

        // User should be unsuspended (saving the empty value)
        $user = $DB->get_record('user', array('id' => $this->user->id));
        $this->assertEquals('user1', $user->username);
        $this->assertEquals(0, $user->suspended);

        // Set user to be suspended again.
        $data = array(
            'id' => $this->user->id,
            'suspended' => 1
        );
        $DB->update_record('user', $data);

        $user = $DB->get_record('user', array('id' => $this->user->id));
        $this->assertEquals('1', $user->suspended);

        // Change deletion type.
        set_config('allow_delete', totara_sync_element_user::SUSPEND_USERS, 'totara_sync_element_user');

        // Suspended field in CSV is empty
        $this->load_user_csv_file('user_suspended_3.csv');

        $this->assertTrue($this->get_element()->sync());

        // User should be unsuspended
        $user = $DB->get_record('user', array('id' => $this->user->id));
        $this->assertEquals('user1', $user->username);
        $this->assertEquals(0, $user->suspended);
    }

    /**
     * Test Delete and undelete when suspend users is the selected
     * deletion option.
     */
    public function test_delete_and_undelete_user() {
        global $DB;

        // Change deletion type.
        set_config('allow_delete', totara_sync_element_user::SUSPEND_USERS, 'totara_sync_element_user');

        $this->load_user_csv_file('user_suspended_4.csv');
        $this->assertTrue($this->get_element()->sync());

        // Check user is suspended.
        $user = $DB->get_record('user', array('id' => $this->user->id));
        $this->assertEquals('user1', $user->username);
        $this->assertEquals(1, $user->suspended);

        $this->load_user_csv_file('user_suspended_3.csv');
        $this->assertTrue($this->get_element()->sync());

        // Check user is unsuspended.
        $user = $DB->get_record('user', array('id' => $this->user->id));
        $this->assertEquals('user1', $user->username);
        $this->assertEquals(0, $user->suspended);
    }

    /**
     * Test multiple uploads of a file keeps the suspended flag
     * as set in the file.
     */
    public function test_user_stays_suspended() {
        global $DB;

        set_config('allow_delete', totara_sync_element_user::DELETE_USERS, 'totara_sync_element_user');
        $element = $this->get_element();
        $element->set_config('sourceallrecords', '1');

        $this->load_user_csv_file('user_suspended_2.csv');
        $this->assertTrue($this->get_element()->sync());

        // Check user is suspended.
        $user = $DB->get_record('user', array('id' => $this->user->id));
        $this->assertEquals('user1', $user->username);
        $this->assertEquals(1, $user->suspended);

        $this->load_user_csv_file('user_suspended_2.csv');
        $this->assertTrue($this->get_element()->sync());

        // Check user is still suspended.
        $user = $DB->get_record('user', array('id' => $this->user->id));
        $this->assertEquals('user1', $user->username);
        $this->assertEquals(1, $user->suspended);
    }

    /**
     * Helper function to load CSV file.
     *
     * @param String $filename Name of file to load
     */
    private function load_user_csv_file($filename) {
        $path = __DIR__ . '/fixtures/' . $filename;
        if (file_exists($path)) {
            $data = file_get_contents($path);
            $filepath = $this->filedir . '/csv/ready/user.csv';
            file_put_contents($filepath, $data);

            return true;
        }

        return false;
    }
}

