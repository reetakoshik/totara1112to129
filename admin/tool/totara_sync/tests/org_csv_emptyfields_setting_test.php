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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package tool_totara_sync
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/totara_sync/lib.php');
require_once($CFG->dirroot . '/admin/tool/totara_sync/tests/source_csv_testcase.php');
require_once($CFG->dirroot . '/admin/tool/totara_sync/sources/source_org_csv.php');

/**
 * @group tool_totara_sync
 */
class tool_totara_sync_org_csv_emptyfields_setting_testcase extends totara_sync_csv_testcase {

    protected $filedir = null;
    protected $configcsv = array();
    protected $config = array();

    protected $elementname = 'org';
    protected $sourcename = 'totara_sync_source_org_csv';
    protected $source = null;

    protected $org_framework_data1 = array(
        'id' => 1, 'fullname' => 'Organisation Framework 1', 'shortname' => 'OFW1', 'idnumber' => '1', 'description' => 'Description 1',
        'sortorder' => 1, 'visible' => 1, 'hidecustomfields' => 0, 'timecreated' => 1265963591, 'timemodified' => 1265963591, 'usermodified' => 2,
    );

    protected $org_framework_data2 = array(
        'id' => 2, 'fullname' => 'Organisation Framework 2', 'shortname' => 'OFW2', 'idnumber' => '2', 'description' => 'Description 2',
        'sortorder' => 2, 'visible' => 1, 'hidecustomfields' => 0, 'timecreated' => 1265963591, 'timemodified' => 1265963591, 'usermodified' => 2,
    );

    protected $org_data1 = array(
        'id' => 1, 'fullname' => 'Top Organisation', 'shortname' => 'toporg', 'idnumber' => '777', 'description' => 'Top level organisation', 'frameworkid' => 1,
        'path' => '/1', 'depthlevel' => 1, 'parentid' => 0, 'sortthread' => '01', 'visible' => 1, 'timevalidfrom' => 0, 'timevalidto' => 0,
        'timecreated' => 0, 'timemodified' => 0, 'usermodified' => 2,
    );

    protected $type_data1 = array(
        'id' => 1, 'fullname' => 'Organisation Type 1', 'shortname' => 'type1', 'idnumber' => '1', 'description' => 'Organisation type 1',
        'timecreated' => 0, 'timemodified' => 0, 'usermodified' => 2,
    );

    // Customfield - text
    private $customfield_textinput_data = array(
        'id' => 1, 'shortname' => 'textinput', 'fullname' => 'Text Input', 'name' => 'textinput', 'typeid' => 1, 'datatype' => 'text', 'description' => '', 'categoryid' => 695000,
        'sortorder' => 1, 'hidden' => 0, 'required' => 0, 'locked' => 0, 'visible' => 1, 'forceunique' => 0, 'signup' => 0, 'defaultdata' => '',
        'param1' => 30, 'param2' => 2048, 'param3' => 0, 'param4' => '', 'param5' => '',
    );

    private $requiredfields = array('idnumber', 'fullname', 'frameworkidnumber', 'timemodified');

    // Expected data uses database field names rather than
    // csv header names.
    private $expected1 = array(
        'idnumber' => 1,
        'fullname' => 'Organisation 1',
        'shortname' => 'org1',
        'frameworkid' => 1,
        'timemodified' => 0,
        'description' => 'Description'
    );

    private $expected1_edited = array(
        'idnumber' => 1,
        'fullname' => 'Organisation 1 edited',
        'shortname' => 'org1edited',
        'frameworkid' => 2,
        'timemodified' => 0,
        'description' => 'Description edited'
    );

    private $expected2 = array(
        'idnumber' => 1,
        'fullname' => 'Organisation 1',
        'shortname' => '',
        'frameworkid' => 1,
        'timemodified' => 0,
        'description' => ''
    );

    protected function tearDown() {
        $this->filedir = null;
        $this->configcsv = null;
        $this->config = null;
        $this->org_framework_data1 = null;
        $this->org_framework_data2 = null;
        $this->org_data1 = null;
        $this->type_data1 = null;
        $this->customfield_textinput_data = null;
        $this->source = null;
        parent::tearDown();
    }

    public function setUp() {
        global $CFG;

        parent::setUp();

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $this->source = new $this->sourcename();

        $this->filedir = $CFG->dataroot . '/totara_sync';
        mkdir($this->filedir . '/csv/ready', 0777, true);

        set_config('element_org_enabled', 1, 'totara_sync');
        set_config('source_org', 'totara_sync_source_org_csv', 'totara_sync');
        set_config('fileaccess', FILE_ACCESS_DIRECTORY, 'totara_sync');
        set_config('filesdir', $this->filedir, 'totara_sync');

        // Create a Organisation framework.
        $this->loadDataSet($this->createArrayDataset(array(
            'org_framework' => array($this->org_framework_data1, $this->org_framework_data2),
            'org' => array($this->org_data1),
            'org_type' => array($this->type_data1),
            'org_type_info_field' => array($this->customfield_textinput_data)
        )));

        $this->configcsv = array(
            'csvuserencoding' => 'UTF-8',
            'delimiter' => ',',
            'csvsaveemptyfields' => true,

            'fieldmapping_idnumber' => '',
            'fieldmapping_fullname' => '',
            'fieldmapping_frameworkidnumber' => '',
            'fieldmapping_timemodified' => '',

            'fieldmapping_shortname' => '',
            'fieldmapping_description' => '',
            'fieldmapping_parentidnumber' => '',
            'fieldmapping_typeidnumber' => '',

            'import_shortname' => '0',
            'import_description' => '0',
            'import_parentidnumber' => '0',
            'import_typeidnumber' => '0',
            'import_frameworkidnumber' => '1',

            // Customfields.
            'fieldmapping_customfield_textinput' => '',
            'import_customfield_textinput' => '0',

        );
        $this->config = array(
            'sourceallrecords' => '1',
            'allow_create' => '1',
            'allow_delete' => '0',
            'allow_update' => '1',
        );
    }

    public function importfields() {
        $importfields = array();

        $importfields['import_idnumber'] = 1;
        $importfields['import_fullname'] = 1;
        $importfields['import_shortname'] = 1;
        $importfields['import_frameworkidnumber'] = 1;
        $importfields['import_timemodified'] = 1;
        $importfields['import_description'] = 1;

        return $importfields;
    }

    public function get_organisation($idnumber) {
        global $DB;

        $org =  $DB->get_record('org', array('idnumber' => $idnumber));

        // Add the customfields.
        $allcustomfields = $DB->get_records('org_type_info_field');
        foreach ($allcustomfields as $customfield) {
            $field = 'customfield_' . $customfield->shortname;
            $value = $DB->get_field('org_type_info_data', 'data', array('fieldid' => $customfield->id, 'organisationid' => $org->id));
            $org->$field = $value;
        }

        return $org;
    }

    public function test_sync_add_organisations_emptyfields_setting_off_populated_fields() {
        global $DB;

        // Adding organisations.
        // The 'Empty fields remove data' setting is off.
        // All the fields in the CSV are populated. (not empty)

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_source_config($config);
        $config = array_merge($this->config, array('csvsaveemptyfields' => false));
        $this->set_element_config($config);

        // Create the CSV file and run the sync.
        $this->add_csv('organisation_empty_fields_1.csv', 'org');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(2, $DB->get_records('org')); // Check the correct count of organisations.

        // Now check each field is populated for organisation idnumber 1.
        $org = $this->get_organisation(1);
        foreach ($this->expected1 as $field => $value) {
            if (in_array($field, $this->requiredfields)) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $org->$field);
                $this->assertNotNull($org->$field);
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($value, $org->$field, 'Failed for field ' . $field);
            }
        }
    }

    public function test_sync_add_organisations_emptyfields_setting_on_populated_fields() {
        global $DB;

        // Adding organisations.
        // The 'Empty fields remove data' setting is on.
        // All the fields in the CSV are populated. (not empty)

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_source_config($config);
        $config = array_merge($this->config, array('csvsaveemptyfields' => true));
        $this->set_element_config($config);

        // Create the CSV file and run the sync.
        $this->add_csv('organisation_empty_fields_1.csv', 'org');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(2, $DB->get_records('org')); // Check the correct count of organisations.

        // Now check each field is populated for organisation idnumber 1.
        $org = $this->get_organisation(1);
        foreach ($this->expected1 as $field => $value) {
            if (in_array($field, $this->requiredfields)) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $org->$field);
                $this->assertNotNull($org->$field);
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($value, $org->$field, 'Failed for field ' . $field);
            }
        }
    }

    public function test_sync_update_organisations_emptyfields_setting_off_populated_fields() {
        global $DB;

        // Updating organisations.
        // The 'Empty fields remove data' setting is off.
        // All the fields in the CSV are populated. (not empty)

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_source_config($config);
        $config = array_merge($this->config, array('csvsaveemptyfields' => false));
        $this->set_element_config($config);

        // First add an organisation we can update.
        $organisation = array(
            'id' => 2,
            'fullname' => 'Organisation 1',
            'shortname' => 'org1',
            'idnumber' => 1,
            'description' => 'Description',
            'frameworkid' => 1,
            'path' => '/2',
            'depthlevel' => 1,
            'parentid' => 0,
            'sortthread' => 02,
            'visible' => 1,
            'timevalidfrom' => 0,
            'timevalidto' => 0,
            'timecreated' => 0,
            'timemodified' => 0,
            'usermodified' => 2,
            'totarasync' => 1
        );

        $this->loadDataSet($this->createArrayDataset(array(
            'org' => array($organisation)
        )));

        //
        // Now lets update the organisation.
        //

        // Create the CSV file and run the sync.
        // Add import file with edited data and run sync.
        $this->add_csv('organisation_empty_fields_2.csv', 'org');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(2, $DB->get_records('org')); // Check the correct count of organisations.

        // Now check each field is populated for organisation idnumber 1.
        $org = $this->get_organisation(1);
        foreach ($this->expected1_edited as $field => $value) {
            if (in_array($field, $this->requiredfields)) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $org->$field);
                $this->assertNotNull($org->$field);
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($value, $org->$field, 'Failed for field ' . $field);
            }
        }
    }

    public function test_sync_update_organisations_emptyfields_setting_on_populated_fields() {
        global $DB;

        // Updating organisations.
        // The 'Empty fields remove data' setting is on.
        // All the fields in the CSV are populated. (not empty)

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_source_config($config);
        $config = array_merge($this->config, array('csvsaveemptyfields' => true));
        $this->set_element_config($config);

        // First add an organisation we can update.
        $organisation = array(
            'id' => 2,
            'fullname' => 'Organisation 1',
            'shortname' => 'org1',
            'idnumber' => 1,
            'description' => 'Description',
            'frameworkid' => 1,
            'path' => '/2',
            'depthlevel' => 1,
            'parentid' => 0,
            'sortthread' => 02,
            'visible' => 1,
            'timevalidfrom' => 0,
            'timevalidto' => 0,
            'timecreated' => 0,
            'timemodified' => 0,
            'usermodified' => 2,
            'totarasync' => 1
        );

        $this->loadDataSet($this->createArrayDataset(array(
            'org' => array($organisation)
        )));

        //
        // Now lets update the organisations.
        //

        // Create the CSV file and run the sync.
        $this->add_csv('organisation_empty_fields_2.csv', 'org');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(2, $DB->get_records('org')); // Check the correct count of organisations.

        // Now check each field is populated for organisation idnumber 1.
        $org = $this->get_organisation(1);
        foreach ($this->expected1_edited as $field => $value) {
            if (in_array($field, $this->requiredfields)) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $org->$field);
                $this->assertNotNull($org->$field);
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($value, $org->$field, 'Failed for field ' . $field);
            }
        }
    }

    public function test_sync_update_organisations_emptyfields_setting_off_empty_fields() {
        global $DB;

        // Updating organisations.
        // The 'Empty fields remove data' setting is off.
        // All the fields in the CSV are empty.

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_source_config($config);
        $config = array_merge($this->config, array('csvsaveemptyfields' => false));
        $this->set_element_config($config);

        // First add an organisation we can update.
        $organisation = array(
            'id' => 2,
            'fullname' => 'Organisation 1',
            'shortname' => 'org1',
            'idnumber' => 1,
            'description' => 'Description',
            'frameworkid' => 1,
            'path' => '/2',
            'depthlevel' => 1,
            'parentid' => 0,
            'sortthread' => 02,
            'visible' => 1,
            'timevalidfrom' => 0,
            'timevalidto' => 0,
            'timecreated' => 0,
            'timemodified' => 0,
            'usermodified' => 2,
            'totarasync' => 1
        );

        $this->loadDataSet($this->createArrayDataset(array(
            'org' => array($organisation)
        )));

        //
        // Now lets update the organisations.
        //

        // Create the CSV file and run the sync.
        $this->add_csv('organisation_empty_fields_3.csv', 'org');

        // Sync CSV with empty fields in non-required columns.
        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(2, $DB->get_records('org')); // Check the correct count of organisations.

        // Now check each field is populated for organisation idnumber 1.
        $org = $this->get_organisation(1);
        foreach ($this->expected1 as $field => $value) {
            if (in_array($field, $this->requiredfields)) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $org->$field);
                $this->assertNotNull($org->$field);
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($value, $org->$field, 'Failed for field ' . $field);
            }
        }
    }

    public function test_sync_update_organisations_emptyfields_setting_on_empty_fields() {
        global $DB;

        // Updating organisations.
        // The 'Empty fields remove data' setting is off.
        // All the fields in the CSV are empty.

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_source_config($config);
        $config = array_merge($this->config, array('csvsaveemptyfields' => true));
        $this->set_element_config($config);

        // First add an organisation we can update.
        $organisation = array(
            'id' => 2,
            'fullname' => 'Organisation 1',
            'shortname' => 'org1',
            'idnumber' => 1,
            'description' => 'Description',
            'frameworkid' => 1,
            'path' => '/2',
            'depthlevel' => 1,
            'parentid' => 0,
            'sortthread' => 02,
            'visible' => 1,
            'timevalidfrom' => 0,
            'timevalidto' => 0,
            'timecreated' => 0,
            'timemodified' => 0,
            'usermodified' => 2,
            'totarasync' => 1
        );

        $this->loadDataSet($this->createArrayDataset(array(
            'org' => array($organisation)
        )));

        //
        // Now lets update the organisation.
        //

        // Create the CSV file and run the sync.
        $this->add_csv('organisation_empty_fields_3.csv', 'org');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(2, $DB->get_records('org')); // Check the correct count of organisations.

        // Now check each field is populated for organisation idnumber 1.
        $org = $this->get_organisation(1);
        foreach ($this->expected2 as $field => $value) {
            if (in_array($field, $this->requiredfields)) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $org->$field);
                $this->assertNotNull($org->$field);
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($value, $org->$field, 'Failed for field ' . $field);
            }
        }
    }

    public function test_sync_parent_with_emptyfields_setting_off() {
        global $DB;

        // Set the config.
        $additional_fields = array('import_parentidnumber' => 1);
        $config = array_merge($this->configcsv, $this->importfields(), $additional_fields);
        $this->set_source_config($config);
        $config = array_merge($this->config, array('csvsaveemptyfields' => 0));
        $this->set_element_config($config);

        // First add some organisations we can update.
        $organisation1 = array(
            'id' => 2,
            'fullname' => 'Organisation 1',
            'shortname' => 'org1',
            'idnumber' => 'ORG1',
            'description' => 'Description 1',
            'frameworkid' => 1,
            'path' => '/2',
            'depthlevel' => 1,
            'parentid' => 0,
            'sortthread' => 02,
            'visible' => 1,
            'timevalidfrom' => 0,
            'timevalidto' => 0,
            'timecreated' => 0,
            'timemodified' => 0,
            'usermodified' => 2,
            'totarasync' => 1
        );

        $organisation2 = array(
            'id' => 3,
            'fullname' => 'Organisation 2',
            'shortname' => 'org2',
            'idnumber' => 'ORG2',
            'description' => 'Description 2',
            'frameworkid' => 1,
            'path' => '/2/3',
            'depthlevel' => 2,
            'parentid' => 2,
            'sortthread' => 02.01,
            'visible' => 1,
            'timevalidfrom' => 0,
            'timevalidto' => 0,
            'timecreated' => 0,
            'timemodified' => 0,
            'usermodified' => 2,
            'totarasync' => 1
        );

        $this->loadDataSet($this->createArrayDataset(array(
            'org' => array($organisation1, $organisation2)
        )));

        $this->assertCount(3, $DB->get_records('org')); // Check the correct count of organisations.

        // Check that the import and parent was assigned correctly.
        $organisation1 = $this->get_organisation('ORG1');
        $organisation2 = $this->get_organisation('ORG2');
        $this->assertEquals('Organisation 2', $organisation2->fullname);
        $this->assertEquals($organisation1->id, $organisation2->parentid);
        $this->assertEquals(2, $organisation2->depthlevel);

        // Create CSV and sync.
        $this->add_csv('organisation_empty_fields_4.csv', 'org');

        $this->assertCount(3, $DB->get_records('org'));
        $this->assertTrue($this->get_element()->sync());
        $this->assertCount(3, $DB->get_records('org'));

        // Get the new record for organisation 2 (it shouldn't have changed).
        $organisation2 = $this->get_organisation('ORG2');
        $this->assertEquals($organisation1->id, $organisation2->parentid);
        $this->assertEquals(2, $organisation2->depthlevel);
    }

    public function test_sync_parent_with_emptyfields_setting_on() {
        global $DB;

        // Set the config.
        $additional_fields = array('import_parentidnumber' => 1);
        $config = array_merge($this->configcsv, $this->importfields(), $additional_fields);
        $this->set_source_config($config);
        $config = array_merge($this->config, array('csvsaveemptyfields' => true));
        $this->set_element_config($config);

        // First add some organisations we can update.
        $organisation1 = array(
            'id' => 2,
            'fullname' => 'Organisation 1',
            'shortname' => 'org1',
            'idnumber' => 'ORG1',
            'description' => 'Description 1',
            'frameworkid' => 1,
            'path' => '/2',
            'depthlevel' => 1,
            'parentid' => 0,
            'sortthread' => 02,
            'visible' => 1,
            'timevalidfrom' => 0,
            'timevalidto' => 0,
            'timecreated' => 0,
            'timemodified' => 0,
            'usermodified' => 2,
            'totarasync' => 1
        );

        $organisation2 = array(
            'id' => 3,
            'fullname' => 'Organisation 2',
            'shortname' => 'org2',
            'idnumber' => 'ORG2',
            'description' => 'Description 2',
            'frameworkid' => 1,
            'path' => '/2/3',
            'depthlevel' => 2,
            'parentid' => 2,
            'sortthread' => 02.01,
            'visible' => 1,
            'timevalidfrom' => 0,
            'timevalidto' => 0,
            'timecreated' => 0,
            'timemodified' => 0,
            'usermodified' => 2,
            'totarasync' => 1
        );

        $this->loadDataSet($this->createArrayDataset(array(
            'org' => array($organisation1, $organisation2)
        )));

        // Check we are starting with the number of orgs.
        $this->assertCount(3, $DB->get_records('org'));

        // Check that the import and parent was assigned correctly.
        $organisation1 = $this->get_organisation('ORG1');
        $organisation2 = $this->get_organisation('ORG2');
        $this->assertEquals('Organisation 2', $organisation2->fullname);
        $this->assertEquals($organisation1->id, $organisation2->parentid);

        // Load fixture CSV.
        $this->add_csv('organisation_empty_fields_4.csv', 'org');

        $this->assertCount(3, $DB->get_records('org'));
        $this->assertTrue($this->get_element()->sync());
        $this->assertCount(3, $DB->get_records('org'));

        // Get the new record for organisation 2.
        $organisation2 = $this->get_organisation('ORG2');
        $this->assertEquals(0, $organisation2->parentid); // Organisation ID should have been removed.
        $this->assertEquals(1, $organisation2->depthlevel);
    }

    public function test_sync_type() {
        // TODO: Special case. Can not be included in the $importdata fields to test as causes failures to other fields when changing the type.
        $this->markTestSkipped('HR Import organisation source type change tests need to be written.');

        $testdata = array(
            "typeidnumber" => array(
                "required" => false,
                "tablefieldname" => "typeid",
                "newdata" => array(1),
                "editeddata" => array(1),
                "default" => array(0)
            ),
        );
    }

    public function test_custom_fields() {
        // TODO:
        $this->markTestSkipped('HR Import organisation source custom field tests still need to be written.');
    }

    public function test_empty_frameworkidnumber_ignore_emptyfields() {
        global $DB;

        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_source_config($config);
        $config = array_merge($this->config, array('csvsaveemptyfields' => false));
        $this->set_element_config($config);

        $this->add_csv('organisation_empty_fields_5.csv', 'org');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.

        $this->assertCount(1, $DB->get_records('org')); // Check the correct count of organisations.
    }


    /**
     * An exception is exptected from the sync() function here as the file being
     * imported contains an empty framework id which is not allowed.
     *
     * @expectedException moodle_exception
     */
    public function test_empty_frameworkidnumber_save_emptyfields() {
        global $DB;

        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_source_config($config);
        $config = array_merge($this->config, array('csvsaveemptyfields' => true));
        $this->set_element_config($config);

        $this->add_csv('organisation_empty_fields_5.csv', 'org');

        $this->get_element()->sync(); // Run the sync.
    }
}
