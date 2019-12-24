<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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

/**
 * @group tool_totara_sync
 */
class tool_totara_sync_user_csv_emptyfields_setting_testcase extends advanced_testcase {

    private $filedir = null;
    private $configcsv = array();
    private $config = array();

    // User profile field - text
    private $user_info_field_textinput_data = array(
        'id' => 1, 'shortname' => 'textinput', 'name' => 'textinput', 'datatype' => 'text', 'description' => '', 'categoryid' => 695000,
        'sortorder' => 1, 'required' => 0, 'locked' => 0, 'visible' => 1, 'forceunique' => 0, 'signup' => 0, 'defaultdata' => '',
        'param1' => 30, 'param2' => 2048, 'param3' => 0, 'param4' => '', 'param5' => '',
    );

    private $user_info_field_textarea_data = array(
        'id' => 2, 'shortname' => 'textarea', 'name' => 'textarea', 'datatype' => 'textarea', 'description' => '', 'categoryid' => 695000,
        'sortorder' => 1, 'required' => 0, 'locked' => 0, 'visible' => 1, 'forceunique' => 0, 'signup' => 0, 'defaultdata' => '',
        'param1' => 30, 'param2' => 2048, 'param3' => 0, 'param4' => '', 'param5' => '',
    );

    private $user_info_field_checkbox_data = array(
        'id' => 3, 'shortname' => 'checkbox', 'name' => 'checkbox', 'datatype' => 'checkbox', 'description' => '', 'categoryid' => 695000,
        'sortorder' => 1, 'required' => 0, 'locked' => 0, 'visible' => 1, 'forceunique' => 0, 'signup' => 0, 'defaultdata' => '',
        'param1' => 30, 'param2' => 2048, 'param3' => 0, 'param4' => '', 'param5' => '',
    );

    private $user_info_field_datetime_data = array(
        'id' => 4, 'shortname' => 'datetime', 'name' => 'datetime', 'datatype' => 'datetime', 'description' => '', 'categoryid' => 695000,
        'sortorder' => 1, 'required' => 0, 'locked' => 0, 'visible' => 1, 'forceunique' => 0, 'signup' => 0, 'defaultdata' => '',
        'param1' => 30, 'param2' => 2048, 'param3' => 0, 'param4' => '', 'param5' => '',
    );

    private $importdata = array(

        // Required fields.
        "idnumber" => array(
            "required" => true,
            "newdata" => array("1"),
            "editeddata" => array("1"), // Keep the same for this field.
        ),
        "timemodified" => array(
            "required" => true,
            "newdata" => array("0"),
            "editeddata" => array("0"),
        ),
        "username" => array(
            "required" => true,
            "newdata" => array("user1"),
            "editeddata" => array("user1edited")
        ),
        "deleted" => array(
            "required" => true,
            "newdata" => array("0"),
            "editeddata" => array("0"),
        ),
        "firstname" => array(
            "required" => true,
            "newdata" => array("user1-firstname"),
            "editeddata" => array("user1-firstnameedited")
        ),
        "lastname" => array(
            "required" => true,
            "newdata" => array("user1-lastname"),
            "editeddata" => array("user1-lastnameedited")
        ),
        "email" => array(
            "required" => true,
            "newdata" => array("user1@email.com"),
            "editeddata" => array("user1-edited@email.com")
        ),

        // Additional fields.
        "firstnamephonetic" => array(
            "required" => false,
            "newdata" => array("user1-firstname-phonetic"),
            "editeddata" => array("user1-firstname-phoneticedited"),
            "default" => array("")
        ),
        "lastnamephonetic" => array(
            "required" => false,
            "newdata" => array("user1-firstname-phonetic"),
            "editeddata" => array("user1-firstname-phoneticedited"),
            "default" => array("")
        ),
        "middlename" => array(
            "required" => false,
            "newdata" => array("user1-middlename"),
            "editeddata" => array("user1-middlenameedited"),
            "default" => array("")
        ),
        "alternatename" => array(
            "required" => false,
            "newdata" => array("user1-alternatename"),
            "editeddata" => array("user1-alternatenameedited"),
            "default" => array("")
        ),
        // TODO: Make emailstop field work using same logic for the setting.
        /*
        "emailstop" => array(
            "required" => false,
            "newdata" => array("1"),
            "editeddata" => array("0"),
            "default" => array(0)
        ),
        */
        "city" => array(
            "required" => false,
            "newdata" => array("Brighton"),
            "editeddata" => array("Brighton & Hove"),
            "default" => array("")
        ),
        "country" => array(
            "required" => false,
            "newdata" => array("GB"),
            "editeddata" => array("NZ"),
            "default" => array("")
        ),
        "description" => array(
            "required" => false,
            "newdata" => array("The is the description"),
            "editeddata" => array("The is the description - edited"),
            "default" => array("")
        ),
        "url" => array(
            "required" => false,
            "newdata" => array("https://www.totaralms.com"),
            "editeddata" => array("https://www.totaralms.com/about-us"),
            "default" => array("")
        ),
        "institution" => array(
            "required" => false,
            "newdata" => array("Totara"),
            "editeddata" => array("Totara - Europe"),
            "default" => array("")
        ),
        "department" => array(
            "required" => false,
            "newdata" => array("Development"),
            "editeddata" => array("Research & Development"),
            "default" => array("")
        ),
        "phone1" => array(
            "required" => false,
            "newdata" => array("0123456"),
            "editeddata" => array("1234567"),
            "default" => array("")
        ),
        "phone2" => array(
            "required" => false,
            "newdata" => array("01234567"),
            "editeddata" => array("12345678"),
            "default" => array("")
        ),
        "address" => array(
            "required" => false,
            "newdata" => array("Brighton, UK"),
            "editeddata" => array("Centre Points, Brighton, UK"),
            "default" => array(""),
        ),

        // User profile custom fields.
        "customfield_textinput" => array(
            "required" => false,
            "newdata" => array("Text input custom field"),
            "editeddata" => array("Text input custom field - edited"),
            "default" => array(""),
        ),
        // TODO: Make textare work in the test.
        /*
        "customfield_textarea" => array(
            "required" => false,
            "newdata" => array("Text area custom field"),
            "editeddata" => array("Text area custom field - edited"),
            "default" => array(""),
        ),
        */
        "customfield_checkbox" => array(
            "required" => false,
            "newdata" => array("1"),
            "editeddata" => array("0"),
            "default" => array(""),
        ),
        /*
        // TODO: make the tests work with date fields.
        "customfield_datetime" => array(
            "required" => false,
            "newdata" => array("21/03/2012"),
            "editeddata" => array("1332259200"),
            "default" => array("0"),
        ),
        */
        // TODO: Add all the other possible custom field types.
    );

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        global $CFG;

        require_once($CFG->dirroot . '/admin/tool/totara_sync/lib.php');
        require_once($CFG->dirroot . '/user/profile/lib.php');
    }

    protected function tearDown() {
        $this->filedir = null;
        $this->configcsv = null;
        $this->config = null;
        $this->user_info_field_textinput_data = null;
        $this->user_info_field_textarea_data = null;
        $this->user_info_field_checkbox_data = null;
        $this->user_info_field_datetime_data = null;
        $this->importdata = null;
        parent::tearDown();
    }

    public function setUp() {
        global $CFG;

        parent::setUp();

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create some user profile fields.
        $this->loadDataSet($this->createArrayDataset(array(
            'user_info_field' => array(
                $this->user_info_field_textinput_data,
                $this->user_info_field_textarea_data,
                $this->user_info_field_checkbox_data,
                $this->user_info_field_datetime_data,
            )
        )));

        $this->filedir = $CFG->dataroot . '/totara_sync';
        mkdir($this->filedir . '/csv/ready', 0777, true);

        set_config('element_user_enabled', 1, 'totara_sync');
        set_config('source_user', 'totara_sync_source_user_csv', 'totara_sync');
        set_config('fileaccess', FILE_ACCESS_DIRECTORY, 'totara_sync');
        set_config('filesdir', $this->filedir, 'totara_sync');

        $this->configcsv = array(
            'csvuserencoding' => 'UTF-8',
            'delimiter' => ',',
            'csvsaveemptyfields' => true,
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

            // User profile fields.
            'fieldmapping_customfield_textinput' => '',
            'import_customfield_textinput' => '1',
        );
        $this->config = array(
            'allow_create' => '1',
            'allow_delete' => '1',
            'allow_update' => '1',
            'allowduplicatedemails' => '0',
            'defaultsyncemail' => '',
            'forcepwchange' => '0',
            'undeletepwreset' => '0',
            'ignoreexistingpass' => '0',
            'sourceallrecords' => '0',
        );
    }

    public function set_config($config, $plugin) {
        foreach ($config as $k => $v) {
            set_config($k, $v, $plugin);
        }
    }

    public function importfields() {
        $importfield = array();

        foreach ($this->importdata as $field => $fielddata) {
            $importfield['import_' . $field] = 1;
        }

        return $importfield;
    }

    public function create_csv($usedata = "newdata") {
        $csvdata = "";

        // The header.
        foreach ($this->importdata as $field => $fielddata) {
            $csvdata .= '"' . $field . '",';
        }
        $csvdata = rtrim($csvdata, ",") . PHP_EOL;

        // The data.
        foreach ($this->importdata as $field => $fielddata) {

            if ($usedata == 'emptydata' && $fielddata["required"]) {
                $data =  $fielddata["newdata"][0];
            } elseif ($usedata == 'emptydata' && !$fielddata["required"]) {
                $data =  "";
            } else {
                $data =  $fielddata[$usedata][0];
            }

            $csvdata .= '"' . $data . '",';
        }
        $csvdata = rtrim($csvdata, ",");

        // Create the file.
        $filepath = $this->filedir . '/csv/ready/user.csv';
        file_put_contents($filepath, $csvdata);
    }

    public function get_element() {
        $elements = totara_sync_get_elements(true);
        /** @var totara_sync_element_user $element */
        return $elements['user'];
    }

    function sync_add_users() {
        global $DB;

        // Create the CSV file and run the sync.
        $this->create_csv('newdata'); // Create and upload our CSV data file
        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(3, $DB->get_records('user')); // Check the correct count of users.

        // Now check each field is populated for user idnumber 1.
        $user = $this->get_user('1');
        foreach ($this->importdata as $field => $fielddata) {
            if ($fielddata["required"]) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $user->$field);
                $this->assertNotNull($user->$field);
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($fielddata["newdata"][0], $user->$field, 'Failed for user field ' . $field);
            }
        }
    }

    public function get_user($idnumber) {
        global $DB;

        $user =  $DB->get_record('user', array('idnumber' => $idnumber));

        // Load custom profile fields data.
        profile_load_data($user);

        // Need to change the field prefix from profile_field_ to customfield_
        foreach($user as $key => $value) {
            if (substr($key, 0, 14) == 'profile_field_') {
                $fieldname = 'customfield_' . substr($key, 14);
                $user->$fieldname = $value;
            }
        }

        return $user;
    }

    public function test_sync_add_users_emptyfields_setting_off_populated_fields() {

        // Adding users.
        // The 'Empty fields remove data' setting is off.
        // All the fields in the CSV are populated. (not empty)

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_config($config, 'totara_sync_source_user_csv');
        $config = array_merge($this->config, array('csvsaveemptyfields' => false));
        $this->set_config($config, 'totara_sync_element_user');

        // Create the CSV file and run the sync and test.
        $this->sync_add_users();
    }

    public function test_sync_add_users_emptyfields_setting_on_populated_fields() {

        // Adding users.
        // The 'Empty fields remove data' setting is on.
        // All the fields in the CSV are populated. (not empty)

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_config($config, 'totara_sync_source_user_csv');
        $config = array_merge($this->config, array('csvsaveemptyfields' => true));
        $this->set_config($config, 'totara_sync_element_user');

        // Create the CSV file and run the sync and test.
        $this->sync_add_users();
    }

    public function test_sync_update_users_emptyfields_setting_off_populated_fields() {

        // Updating users.
        // The 'Empty fields remove data' setting is off.
        // All the fields in the CSV are populated. (not empty)

        global $DB;

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_config($config, 'totara_sync_source_user_csv');
        $config = array_merge($this->config, array('csvsaveemptyfields' => false));
        $this->set_config($config, 'totara_sync_element_user');

        //
        // First lets add users.
        //

        $this->sync_add_users();

        //
        // Now lets update the users.
        //

        // Create the CSV file and run the sync.
        $this->create_csv('editeddata'); // Create and upload our CSV data file
        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(3, $DB->get_records('user')); // Check the correct count of users.

        // Now check each field is populated for user idnumber 1.
        $user = $this->get_user('1');
        foreach ($this->importdata as $field => $fielddata) {
            if ($fielddata["required"]) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $user->$field);
                $this->assertNotNull($user->$field);
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($fielddata["editeddata"][0], $user->$field, 'Failed for user field ' . $field);
            }
        }
    }

    public function test_sync_update_users_emptyfields_setting_on_populated_fields() {

        // Updating users.
        // The 'Empty fields remove data' setting is on.
        // All the fields in the CSV are populated. (not empty)

        global $DB;

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_config($config, 'totara_sync_source_user_csv');
        $config = array_merge($this->config, array('csvsaveemptyfields' => true));
        $this->set_config($config, 'totara_sync_element_user');

        //
        // First lets add users.
        //

        $this->sync_add_users();

        //
        // Now lets update the users.
        //

        // Create the CSV file and run the sync.
        $this->create_csv('editeddata'); // Create and upload our CSV data file
        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(3, $DB->get_records('user')); // Check the correct count of users.

        // Now check each field is populated for user idnumber 1.
        $user = $this->get_user('1');
        foreach ($this->importdata as $field => $fielddata) {
            if ($fielddata["required"]) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $user->$field);
                $this->assertNotNull($user->$field);
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($fielddata["editeddata"][0], $user->$field, 'Failed for user field ' . $field);
            }
        }
    }

    public function test_sync_update_users_emptyfields_setting_off_empty_fields() {

        // Updating users.
        // The 'Empty fields remove data' setting is off.
        // All the fields in the CSV are empty.

        global $DB;

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_config($config, 'totara_sync_source_user_csv');
        $config = array_merge($this->config, array('csvsaveemptyfields' => false));
        $this->set_config($config, 'totara_sync_element_user');

        //
        // First lets add users.
        //

        $this->sync_add_users();

        //
        // Now lets update the users.
        //

        // Create the CSV file and run the sync.
        $this->create_csv('emptydata'); // Create and upload our CSV data file with empty fields.
        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(3, $DB->get_records('user')); // Check the correct count of users.

        // Now check each field is populated for user idnumber 1.
        $user = $this->get_user('1');
        foreach ($this->importdata as $field => $fielddata) {
            if ($fielddata["required"]) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $user->$field);
                $this->assertNotNull($user->$field);
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($fielddata["newdata"][0], $user->$field, 'Failed for user field ' . $field);
            }
        }
    }

    public function test_sync_update_users_emptyfields_setting_on_empty_fields() {

        // Updating users.
        // The 'Empty fields remove data' setting is off.
        // All the fields in the CSV are empty.

        global $DB;

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_config($config, 'totara_sync_source_user_csv');
        $config = array_merge($this->config, array('csvsaveemptyfields' => true));
        $this->set_config($config, 'totara_sync_element_user');

        //
        // First lets add users.
        //

        $this->sync_add_users();

        //
        // Now lets update the users.
        //

        // Create the CSV file and run the sync.
        $this->create_csv('emptydata'); // Create and upload our CSV data file with empty fields.
        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(3, $DB->get_records('user')); // Check the correct count of users.

        // Now check each field is populated for user idnumber 1.
        $user = $this->get_user('1');
        foreach ($this->importdata as $field => $fielddata) {
            if ($fielddata["required"]) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $user->$field);
                $this->assertNotNull($user->$field);
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($fielddata["default"][0], $user->$field, 'Failed for user field ' . $field);
            }
        }
    }

    public function test_sync_timezone() {
        // TODO: Special case here to test.
        // If if the field is empty, the $CFG->timezone is used
        $this->markTestSkipped('HR Import user source timezone field needs tests.');
    }

    public function test_sync_lang() {
        // TODO: Special case here to test.
        $this->markTestSkipped('HR Import user source lang field needs tests.');
    }

    public function test_sync_auth() {
        // TODO: Special case here to test. Auth can not be empty. The sync should fail..
        $this->markTestSkipped('HR Import user source auth field needs tests.');
    }

    public function test_sync_user_defaults() {
        global $DB;

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_config($config, 'totara_sync_source_user_csv');
        $config = array_merge($this->config, array('csvsaveemptyfields' => true));
        $this->set_config($config, 'totara_sync_element_user');

        set_config('defaultpreference_maildisplay', 1);

        // Add users
        $this->sync_add_users();

        // Create the CSV file and run the sync.
        $this->create_csv('emptydata'); // Create and upload our CSV data file with empty fields.
        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(3, $DB->get_records('user')); // Check the correct count of users.

        $user = $this->get_user('1');
        $this->assertEquals(1, $user->maildisplay);
    }

}
