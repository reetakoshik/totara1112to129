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

require_once(__DIR__ . '/source_csv_testcase.php');

/**
 * @group tool_totara_sync
 */
class tool_totara_sync_user_csv_emptyfields_setting_testcase extends totara_sync_csv_testcase {

    protected $filedir = null;
    protected $configcsv = array();
    protected $config = array();

    protected $elementname = 'user';
    protected $sourcename = 'totara_sync_source_user_csv';
    protected $source = null;

    // User profile field - text
    private $user_info_field_textinput_data = array(
        'id' => 1, 'shortname' => 'textinput', 'name' => 'textinput', 'datatype' => 'text', 'description' => '', 'categoryid' => 695000,
        'sortorder' => 1, 'required' => 0, 'locked' => 0, 'visible' => 1, 'forceunique' => 0, 'signup' => 0, 'defaultdata' => '',
        'param1' => 30, 'param2' => 2048, 'param3' => 0, 'param4' => '', 'param5' => '',
    );

    // User textinput profile field that requires values to be unique.
    private $user_info_field_textinput_unique_data = array(
        'id' => 5, 'shortname' => 'textinput_unique', 'name' => 'textinput_unique', 'datatype' => 'text', 'description' => '', 'categoryid' => 695000,
        'sortorder' => 1, 'required' => 0, 'locked' => 0, 'visible' => 1, 'forceunique' => 1, 'signup' => 0, 'defaultdata' => '',
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

    // Fields that cannot be erased.
    private $requiredfields = array('idnumber', 'timemodified', 'username', 'deleted', 'firstname', 'lastname', 'email');

    private $expected1 = array(
        'idnumber' => 1,
        'timemodified' => 0,
        'username' => 'user1',
        'deleted' => 0,
        'firstname' => 'user1-firstname',
        'lastname' => 'user1-lastname',
        'email' => 'user1@email.com',
        'firstnamephonetic' => 'user1-firstname-phonetic',
        'lastnamephonetic' => 'user1-lastname-phonetic',
        'middlename' => 'user1-middlename',
        'alternatename' => 'user1-alternatename',
        'city' => 'Brighton',
        'country' => 'GB',
        'description' => 'This is the description',
        'url' => 'https://www.totaralms.com',
        'institution' => 'Totara',
        'department' => 'Development',
        'phone1' => '0123456',
        'phone2' => '01234567',
        'address' => 'Brighton, UK'
        // Text input custom field,
    );

    private $expected1_edited = array(
        'idnumber' => 1,
        'timemodified' => 0,
        'username' => 'user1edited',
        'deleted' => 0,
        'firstname' => 'user1-firstnameedited',
        'lastname' => 'user1-lastnameedited',
        'email' => 'user1-edited@email.com',
        'firstnamephonetic' => 'user1-firstname-phoneticedited',
        'lastnamephonetic' => 'user1-lastname-phoneticedited',
        'middlename' => 'user1-middlenameedited',
        'alternatename' => 'user1-alternatenameedited',
        'city' => 'Brighton & Hove',
        'country' => 'NZ',
        'description' => 'This is the description - edited',
        'url' => 'https://www.totaralms.com/about-us',
        'institution' => 'Totara - Europe',
        'department' => 'Research & Development',
        'phone1' => '1234567',
        'phone2' => '12345678',
        'address' => 'Centre Point, Brighton, UK'
        //'Text input custom field - edited
    );

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        global $CFG;

        require_once($CFG->dirroot . '/admin/tool/totara_sync/lib.php');
        require_once($CFG->dirroot . '/admin/tool/totara_sync/sources/source_user_csv.php');
        require_once($CFG->dirroot . '/user/profile/lib.php');
    }

    protected function tearDown() {
        $this->filedir = null;
        $this->configcsv = null;
        $this->config = null;
        $this->user_info_field_textinput_data = null;
        $this->user_info_field_textinput_unique_data = null;
        $this->user_info_field_textarea_data = null;
        $this->user_info_field_checkbox_data = null;
        $this->user_info_field_datetime_data = null;
        $this->importdata = null;
        $this->source = null;
        parent::tearDown();
    }

    public function setUp() {
        global $CFG;

        parent::setUp();

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $this->source = new $this->sourcename();

        // Create some user profile fields.
        $this->loadDataSet($this->createArrayDataset(array(
            'user_info_field' => array(
                $this->user_info_field_textinput_data,
                $this->user_info_field_textinput_unique_data,
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

        $importfield['import_idnumber'] = 1;
        $importfield['import_timemodified'] = 1;
        $importfield['import_username'] = 1;
        $importfield['import_deleted'] = 1;
        $importfield['import_firstname'] = 1;
        $importfield['import_lastname'] = 1;
        $importfield['import_email'] = 1;
        $importfield['import_firstnamephonetic'] = 1;
        $importfield['import_lastnamephonetic'] = 1;
        $importfield['import_middlename'] = 1;
        $importfield['import_alternatename'] = 1;
        $importfield['import_city'] = 1;
        $importfield['import_country'] = 1;
        $importfield['import_description'] = 1;
        $importfield['import_url'] = 1;
        $importfield['import_institution'] = 1;
        $importfield['import_department'] = 1;
        $importfield['import_phone1'] = 1;
        $importfield['import_phone2'] = 1;
        $importfield['import_address'] = 1;
        $importfield['import_customfield_textinput'] = 1;
        $importfield['import_customfield_checkbox'] = 1;

        return $importfield;
    }

    /**
     * Get a user record and add profile field data
     *
     * @param $idnumber
     * @return bool|mixed
     */
    public function get_user($idnumber) {
        global $DB;

        $user =  $DB->get_record('user', array('idnumber' => $idnumber));

        if (!$user) {
            return false;
        }

        // Load custom profile fields data.
        profile_load_data($user);

        // Need to change the field prefix from profile_field_ to customfield_
        foreach ($user as $key => $value) {
            if (substr($key, 0, 14) == 'profile_field_') {
                $fieldname = 'customfield_' . substr($key, 14);
                $user->$fieldname = $value;
            }
        }

        return $user;
    }

    public function test_sync_add_users_emptyfields_setting_off_populated_fields() {
        global $DB;
        // Adding users.
        // The 'Empty fields remove data' setting is off.
        // All the fields in the CSV are populated. (not empty)

        $extraimportfields = array(
            'fieldmapping_customfield_textinput' => '',
            'import_customfield_textinput' => '1'
        );

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields(), $extraimportfields);
        $this->set_config($config, 'totara_sync_source_user_csv');
        $config = array_merge($this->config, array('csvsaveemptyfields' => false));
        $this->set_config($config, 'totara_sync_element_user');

        // Create the CSV file and run the sync.
        $this->add_csv('user_empty_fields_1.csv', 'user');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(3, $DB->get_records('user')); // Check the correct count of users.

        // Now check each field is populated for user idnumber 1.
        $user = $this->get_user('1');
        foreach ($this->expected1 as $field => $value) {
            if (in_array($field, $this->requiredfields)) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $user->$field);
                $this->assertNotNull($user->$field);
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($value, $user->$field, 'Failed for user field ' . $field);
            }
        }
    }

    public function test_sync_add_users_emptyfields_setting_on_populated_fields() {
        global $DB;
        // Adding users.
        // The 'Empty fields remove data' setting is on.
        // All the fields in the CSV are populated. (not empty)

        $extraimportfields = array(
            'fieldmapping_customfield_textinput' => '',
            'import_customfield_textinput' => '1'
        );

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields(), $extraimportfields);
        $this->set_config($config, 'totara_sync_source_user_csv');
        $config = array_merge($this->config, array('csvsaveemptyfields' => true));
        $this->set_config($config, 'totara_sync_element_user');

        // Create the CSV file and run the sync.
        $this->add_csv('user_empty_fields_1.csv', 'user');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(3, $DB->get_records('user')); // Check the correct count of users.

        // Now check each field is populated for user idnumber 1.
        $user = $this->get_user('1');
        foreach ($this->expected1 as $field => $value) {
            if (in_array($field, $this->requiredfields)) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $user->$field);
                $this->assertNotNull($user->$field);
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($value, $user->$field, 'Failed for user field ' . $field);
            }
        }
    }

    public function test_sync_update_users_emptyfields_setting_off_populated_fields() {

        // Updating users.
        // The 'Empty fields remove data' setting is off.
        // All the fields in the CSV are populated. (not empty)

        global $DB;

        $extraimportfields = array(
            'fieldmapping_customfield_textinput' => '',
            'import_customfield_textinput' => '1'
        );

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields(), $extraimportfields);
        $this->set_config($config, 'totara_sync_source_user_csv');
        $config = array_merge($this->config, array('csvsaveemptyfields' => false));
        $this->set_config($config, 'totara_sync_element_user');

        //
        // First lets add users.
        //

        $this->add_csv('user_empty_fields_1.csv', 'user');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(3, $DB->get_records('user')); // Check the correct count of users.

        //
        // Now lets update the users.
        //

        // Create the CSV file and run the sync.
        $this->add_csv('user_empty_fields_2.csv', 'user');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(3, $DB->get_records('user')); // Check the correct count of users.

        // Now check each field is populated for user idnumber 1.
        $user = $this->get_user('1');
        foreach ($this->expected1_edited as $field => $value) {
            if (in_array($field, $this->requiredfields)) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $user->$field);
                $this->assertNotNull($user->$field);
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($value, $user->$field, 'Failed for user field ' . $field);
            }
        }
    }

    public function test_sync_update_users_emptyfields_setting_on_populated_fields() {

        // Updating users.
        // The 'Empty fields remove data' setting is on.
        // All the fields in the CSV are populated. (not empty)

        global $DB;

        $extraimportfields = array(
            'fieldmapping_customfield_textinput' => '',
            'import_customfield_textinput' => '1'
        );

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields(), $extraimportfields);
        $this->set_config($config, 'totara_sync_source_user_csv');
        $config = array_merge($this->config, array('csvsaveemptyfields' => true));
        $this->set_config($config, 'totara_sync_element_user');

        //
        // First lets add users.
        //

        $this->add_csv('user_empty_fields_1.csv', 'user');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(3, $DB->get_records('user')); // Check the correct count of users.

        //
        // Now lets update the users.
        //

        // Create the CSV file and run the sync.
        $this->add_csv('user_empty_fields_2.csv', 'user');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(3, $DB->get_records('user')); // Check the correct count of users.

        // Now check each field is populated for user idnumber 1.
        $user = $this->get_user('1');
        foreach ($this->expected1_edited as $field => $value) {
            if (in_array($field, $this->requiredfields)) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $user->$field);
                $this->assertNotNull($user->$field);
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($value, $user->$field, 'Failed for user field ' . $field);
            }
        }
    }

    public function test_sync_update_users_emptyfields_setting_off_empty_fields() {

        // Updating users.
        // The 'Empty fields remove data' setting is off.
        // All the fields in the CSV are empty.

        global $DB;

        $extraimportfields = array(
            'fieldmapping_customfield_textinput' => '',
            'import_customfield_textinput' => '1'
        );

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields(), $extraimportfields);
        $this->set_config($config, 'totara_sync_source_user_csv');
        $config = array_merge($this->config, array('csvsaveemptyfields' => false));
        $this->set_config($config, 'totara_sync_element_user');

        //
        // First lets add users.
        //

        $this->add_csv('user_empty_fields_1.csv', 'user');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(3, $DB->get_records('user')); // Check the correct count of users.

        //
        // Now lets update the users.
        //

        // Create the CSV file and run the sync.
        $this->add_csv('user_empty_fields_2.csv', 'user');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(3, $DB->get_records('user')); // Check the correct count of users.

        // Now check each field is populated for user idnumber 1.
        $user = $this->get_user('1');
        foreach ($this->expected1_edited as $field => $value) { //TODO: check expected.
            if (in_array($field, $this->requiredfields)) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $user->$field);
                $this->assertNotNull($user->$field);
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($value, $user->$field, 'Failed for user field ' . $field);
            }
        }
    }

    public function test_sync_update_users_emptyfields_setting_on_empty_fields() {

        // Updating users.
        // The 'Empty fields remove data' setting is off.
        // All the fields in the CSV are empty.

        global $DB;

        $extraimportfields = array(
            'fieldmapping_customfield_textinput' => '',
            'import_customfield_textinput' => '1'
        );

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields(), $extraimportfields);
        $this->set_config($config, 'totara_sync_source_user_csv');
        $config = array_merge($this->config, array('csvsaveemptyfields' => true));
        $this->set_config($config, 'totara_sync_element_user');

        //
        // First lets add users.
        //

        $this->add_csv('user_empty_fields_1.csv', 'user');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(3, $DB->get_records('user')); // Check the correct count of users.

        //
        // Now lets update the users.
        //

        // Create the CSV file and run the sync.
        $this->add_csv('user_empty_fields_2.csv', 'user');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(3, $DB->get_records('user')); // Check the correct count of users.

        // Now check each field is populated for user idnumber 1.
        $user = $this->get_user('1');
        foreach ($this->expected1_edited as $field => $value) {
            if (in_array($field, $this->requiredfields)) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $user->$field);
                $this->assertNotNull($user->$field);
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($value, $user->$field, 'Failed for user field ' . $field);
            }
        }
    }

    public function test_sync_timezone() {
        global $DB;

        $extraimportfields = array(
            'import_timezone' => '1'
        );

        // Set the config.
        $config = array_merge($this->configcsv, $extraimportfields);
        $this->set_source_config($config);
        $config = array_merge($this->config, array('csvsaveemptyfields' => false));
        $this->set_element_config($config);

        // Load the initial user.
        $this->add_csv('user_empty_fields_timezone_1.csv', 'user');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(3, $DB->get_records('user')); // Check the correct count of users.

        $user = $DB->get_record('user', array('idnumber' => '3'));
        $this->assertNotEquals('99', $user->timezone);
        $this->assertEquals('Africa/Cairo', $user->timezone);

        // Load file with empty timezone.
        $this->add_csv('user_empty_fields_timezone_2.csv', 'user');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(3, $DB->get_records('user')); // Check the correct count of users.

        $user = $DB->get_record('user', array('idnumber' => '3'));
        $this->assertNotEquals('99', $user->timezone);
        $this->assertEquals('Africa/Cairo', $user->timezone);

        // Turn on saving empty fields
        $config = array_merge($this->config, array('csvsaveemptyfields' => true));
        $this->set_element_config($config);

        // Load file with empty timezone.
        $this->add_csv('user_empty_fields_timezone_2.csv', 'user');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(3, $DB->get_records('user')); // Check the correct count of users.

        $user = $DB->get_record('user', array('idnumber' => '3'));
        $this->assertNotEquals('Africa/Cairo', $user->timezone);
        $this->assertEquals('99', $user->timezone);
    }

    public function test_sync_lang() {
        global $DB, $CFG;
        // TODO: Testing language is hard as there are no extra language installed
        // in the PHPUnit environment.
        $this->markTestSkipped('HR Import user source lang field needs tests.');

        $extraimportfields = array(
            'import_lang' => 1
        );

        require_once($CFG->libdir . '/componentlib.class.php');

        // Install French lang pack for testing.
        $langinstaller = new lang_installer('fr');
        $langinstaller->run();

        $configcsv = array_merge($this->configcsv, $extraimportfields);
        $this->set_source_config($configcsv);
        $config = array_merge($this->config, array('csvsaveemptyfields' => false));
        $this->set_element_config($config);

        // Load the initial user.
        $this->add_csv('user_empty_fields_lang_1.csv', 'user');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(3, $DB->get_records('user')); // Check the correct count of users.

        $user = $DB->get_record('user', array('idnumber' => '3'));
        $this->assertNotEquals('en', $user->lang);
        $this->assertEquals('fr', $user->lang);

        $this->add_csv('user_empty_fields_lang_2.csv', 'user');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(3, $DB->get_records('user')); // Check the correct count of users.

        $user = $DB->get_record('user', array('idnumber' => '3'));
        $this->assertNotEquals('en', $user->lang);
        $this->assertEquals('fr', $user->lang);

        $config = array_merge($this->config, array('csvsaveemptyfields' => true));
        $this->set_element_config($config);

        $this->add_csv('user_empty_fields_lang_2.csv', 'user');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(3, $DB->get_records('user')); // Check the correct count of users.

        $user = $DB->get_record('user', array('idnumber' => '3'));
        $this->assertNotEquals('en', $user->lang);
        $this->assertEquals('fr', $user->lang);
    }

    public function test_sync_auth() {
        global $DB;

        $extraimportfields = array(
            'import_auth' => 1
        );

        $configcsv = array_merge($this->configcsv, $extraimportfields);
        $this->set_source_config($configcsv);
        $config = array_merge($this->config, array('csvsaveemptyfields' => false));
        $this->set_element_config($config);

        // Load the initial user.
        $this->add_csv('user_empty_fields_auth_1.csv', 'user');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(3, $DB->get_records('user')); // Check the correct count of users.

        $user = $DB->get_record('user', array('idnumber' => '2'));
        $this->assertEquals('manual', $user->auth);

        // Load the initial user.
        $this->add_csv('user_empty_fields_auth_2.csv', 'user');

        $this->assertFalse($this->get_element()->sync()); // Success, empty field ignored.
        $this->assertCount(3, $DB->get_records('user')); // Check the correct count of users.

        $user = $DB->get_record('user', array('idnumber' => '2'));
        $this->assertEquals('manual', $user->auth);

        $config = array_merge($this->config, array('csvsaveemptyfields' => true));
        $this->set_element_config($config);

        // Load the initial user.
        $this->add_csv('user_empty_fields_auth_2.csv', 'user');

        $this->assertFalse($this->get_element()->sync());
        $this->assertCount(3, $DB->get_records('user')); // Check the correct count of users.

        $user = $DB->get_record('user', array('idnumber' => '2'));
        $this->assertEquals('manual', $user->auth);
    }

    public function test_sync_user_defaults() {
        global $DB;

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_source_config($config);
        $config = array_merge($this->config, array('csvsaveemptyfields' => true));
        $this->set_element_config($config);

        set_config('defaultpreference_maildisplay', 1);

        // Add users
        $this->add_csv('user_empty_fields_1.csv', 'user');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(3, $DB->get_records('user')); // Check the correct count of users.
        //$this->sync_add_users();

        // Create the CSV file and run the sync.
        $this->add_csv('user_empty_fields_2.csv', 'user');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(3, $DB->get_records('user')); // Check the correct count of users.

        $user = $this->get_user('1');
        $this->assertEquals(1, $user->maildisplay);
    }

    public function test_empty_suspended_ignore_emptyfields() {
        global $DB;

        $extraimportfields = array(
            'import_timemodified' => 1,
            'import_email' => 1,
            'import_suspended' => 1
        );

        $configcsv = array_merge($this->configcsv, $extraimportfields);
        $this->set_source_config($configcsv);
        $config = array_merge($this->config, array('csvsaveemptyfields' => false));
        $this->set_element_config($config);

        // Load the initial user.
        $this->add_csv('user_empty_fields_4.csv', 'user');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(3, $DB->get_records('user')); // Check the correct count of users.

        // Check the suspended field is as expected.
        $user = $this->get_user('2');
        $this->assertEquals('1', $user->suspended);

        // Update user.
        $this->add_csv('user_empty_fields_4a.csv', 'user');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(3, $DB->get_records('user')); // Check the correct count of users.

        $user = $this->get_user('2');
        $this->assertEquals('1', $user->suspended);

        // Enable save empty fields.
        $config = array_merge($this->config, array('csvsaveemptyfields' => true));
        $this->set_element_config($config);

        $this->add_csv('user_empty_fields_4a.csv', 'user');

        // Update user again, this time saving empty fields.
        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(3, $DB->get_records('user')); // Check the correct count of users.

        $user = $this->get_user('2');
        $this->assertEquals('0', $user->suspended);
    }

    public function test_empty_username() {
        global $DB;

        $extraimportfields = array(
            'import_timemodified' => 1,
            'import_email' => 1,
        );

        $configcsv = array_merge($this->configcsv, $extraimportfields);
        $this->set_source_config($configcsv);
        $config = array_merge($this->config, array('csvsaveemptyfields' => false));
        $this->set_element_config($config);

        // Load the initial user.
        $this->add_csv('user_empty_fields_5.csv', 'user');

        $this->assertCount(2, $DB->get_records('user')); // Check the correct count of users.
        $this->assertFalse($this->get_element()->sync()); // Run the sync.
        $this->assertCount(2, $DB->get_records('user')); // Check we still have only 2 users.

        $config = array_merge($this->config, array('csvsaveemptyfields' => true));
        $this->set_element_config($config);

        // Load the csv again to try with save empty on.
        $this->add_csv('user_empty_fields_5.csv', 'user');

        $this->assertCount(2, $DB->get_records('user')); // Check the correct count of users.
        $this->assertFalse($this->get_element()->sync()); // Run the sync.
        $this->assertCount(2, $DB->get_records('user')); // Check we still have only 2 users.

    }

    public function test_empty_emailstop_ignore_emptyfields() {
        global $DB;

        $extraimportfields = array(
            'import_timemodified' => 1,
            'import_email' => 1,
            'import_emailstop' => '1',
        );

        $configcsv = array_merge($this->configcsv, $extraimportfields);
        $this->set_source_config($configcsv);

        $config = $this->config;
        $this->set_element_config($config);

        $this->add_csv('user_empty_fields_6.csv', 'user');

        $result = $this->get_element()->sync();
        $this->assertTrue($result);

        $this->add_csv('user_empty_fields_6a.csv', 'user');

        $result = $this->get_element()->sync();
        $this->assertTrue($result); // Sync is successful.

        $user = $DB->get_record('user', array('idnumber' => 2));
        $this->assertEquals('1', $user->emailstop);

        // Enable save empty fields.
        $config = array_merge($this->config, array('csvsaveemptyfields' => true));
        $this->set_element_config($config);

        $this->add_csv('user_empty_fields_6a.csv', 'user');

        $result = $this->get_element()->sync();
        $this->assertTrue($result);

        $user = $DB->get_record('user', array('idnumber' => 2));
        $this->assertEquals('0', $user->emailstop);
    }

    public function test_empty_name_fields_ignore_emptyfields() {
        global $DB;

        $csvconfig = array_merge($this->configcsv, $this->importfields());
        $this->set_source_config($csvconfig);
        $config = array_merge($this->config, array('csvsaveemptyfields' => false));
        $this->set_element_config($config);

        // Load the initial user.
        $this->add_csv('user_empty_fields_1.csv', 'user');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(3, $DB->get_records('user')); // Check the correct count of users.

        // Check the alternate field name fields are as expected.
        $user = $this->get_user('1');
        $this->assertEquals($this->expected1['middlename'], $user->middlename);
        $this->assertEquals($this->expected1['firstnamephonetic'], $user->firstnamephonetic);
        $this->assertEquals($this->expected1['lastnamephonetic'], $user->lastnamephonetic);
        $this->assertEquals($this->expected1['alternatename'], $user->alternatename);

        $this->add_csv('user_empty_fields_3.csv', 'user');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.

        // Check the fields are still as we expect.
        $user = $this->get_user('1');
        $this->assertEquals($this->expected1['middlename'], $user->middlename);
        $this->assertEquals($this->expected1['firstnamephonetic'], $user->firstnamephonetic);
        $this->assertEquals($this->expected1['lastnamephonetic'], $user->lastnamephonetic);
        $this->assertEquals($this->expected1['alternatename'], $user->alternatename);

        // Update config to save empty fields.
        $csvconfig = array_merge($this->config, array('csvsaveemptyfields' => true));
        $this->set_element_config($csvconfig);

        $this->add_csv('user_empty_fields_3.csv', 'user');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.

        // Check empty fields are now saved.
        $user = $this->get_user('1');
        $this->assertEquals('', $user->middlename);
        $this->assertEquals('', $user->firstnamephonetic);
        $this->assertEquals('', $user->lastnamephonetic);
        $this->assertEquals('', $user->alternatename);
    }

    public function test_customfields_unique_csvsaveemptyfields_on() {
        global $DB;

        // Set the config.
        $this->set_source_config(array_merge($this->configcsv, array(
            'import_customfield_textinput_unique' => 1,
            'fieldmapping_customfield_textinput_unique' => ''
        )));
        $config = array_merge($this->config, array('csvsaveemptyfields' => true)); // Empty fields erase existing data.
        $this->set_element_config($config);

        // Run the sync.
        $this->add_csv('user_customfields_unique.csv', 'user');
        $this->get_element()->sync();
        $this->assertCount(6, $DB->get_records('user')); // Check the correct count of users.

        // User 1 should not be imported, the custom field is not unique.
        $user = $this->get_user('1');
        $this->assertEmpty($user);

        // User 2 should not be imported, the custom field is not unique.
        $user = $this->get_user('2');
        $this->assertEmpty($user);

        // User 3 should be imported, the custom field is empty string.
        $user = $this->get_user('3');
        $this->assertNotEmpty($user);
        $this->assertEquals('', $user->customfield_textinput_unique);

        // User 4 should be imported, the custom field is empty string.
        $user = $this->get_user('4');
        $this->assertNotEmpty($user);
        $this->assertEquals('', $user->customfield_textinput_unique);

        // User 5 should be imported, the custom field is unique.
        $user = $this->get_user('5');
        $this->assertNotEmpty($user);
        $this->assertEquals('5', $user->customfield_textinput_unique);

        // User 6 should be imported, the custom field is unique.
        $user = $this->get_user('6');
        $this->assertNotEmpty($user);
        $this->assertEquals('6', $user->customfield_textinput_unique);
    }

    public function test_customfields_unique_csvsaveemptyfields_off() {
        global $DB;

        // Set the config.
        $this->set_source_config(array_merge($this->configcsv, array(
            'import_customfield_textinput_unique' => 1,
            'fieldmapping_customfield_textinput_unique' => ''
        )));
        $config = array_merge($this->config, array('csvsaveemptyfields' => false)); // Empty fields skip saving data.
        $this->set_element_config($config);

        // Run the sync.
        $this->add_csv('user_customfields_unique.csv', 'user');
        $this->get_element()->sync();
        $this->assertCount(6, $DB->get_records('user')); // Check the correct count of users.

        // User 1 should not be imported, the custom field is not unique.
        $user = $this->get_user('1');
        $this->assertEmpty($user);

        // User 2 should not be imported, the custom field is not unique.
        $user = $this->get_user('2');
        $this->assertEmpty($user);

        // User 3 should be imported, the custom field is empty string.
        $user = $this->get_user('3');
        $this->assertNotEmpty($user);
        $this->assertEquals('', $user->customfield_textinput_unique);

        // User 4 should be imported, the custom field is empty string.
        $user = $this->get_user('4');
        $this->assertNotEmpty($user);
        $this->assertEquals('', $user->customfield_textinput_unique);

        // User 5 should be imported, the custom field is unique.
        $user = $this->get_user('5');
        $this->assertNotEmpty($user);
        $this->assertEquals('5', $user->customfield_textinput_unique);

        // User 6 should be imported, the custom field is unique.
        $user = $this->get_user('6');
        $this->assertNotEmpty($user);
        $this->assertEquals('6', $user->customfield_textinput_unique);
    }
}
