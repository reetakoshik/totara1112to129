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

global $CFG;
require_once($CFG->dirroot . '/admin/tool/totara_sync/lib.php');

/**
 * @group tool_totara_sync
 */
class tool_totara_sync_pos_csv_emptyfields_setting_testcase extends advanced_testcase {

    private $filedir = null;
    private $configcsv = array();
    private $config = array();

    protected $pos_framework_data1 = array(
        'id' => 1, 'fullname' => 'Postion Framework 1', 'shortname' => 'PFW1', 'idnumber' => '1', 'description' => 'Description 1',
        'sortorder' => 1, 'visible' => 1, 'hidecustomfields' => 0, 'timecreated' => 1265963591, 'timemodified' => 1265963591, 'usermodified' => 2,
    );

    protected $pos_framework_data2 = array(
        'id' => 2, 'fullname' => 'Postion Framework 2', 'shortname' => 'PFW2', 'idnumber' => '2', 'description' => 'Description 2',
        'sortorder' => 2, 'visible' => 1, 'hidecustomfields' => 0, 'timecreated' => 1265963591, 'timemodified' => 1265963591, 'usermodified' => 2,
    );

    protected $pos_data1 = array(
        'id' => 1, 'fullname' => 'Top Position', 'shortname' => 'toppos', 'idnumber' => '777', 'description' => 'Top level position', 'frameworkid' => 1,
        'path' => '/1', 'depthlevel' => 1, 'parentid' => 0, 'sortthread' => '01', 'visible' => 1, 'timevalidfrom' => 0, 'timevalidto' => 0,
        'timecreated' => 0, 'timemodified' => 0, 'usermodified' => 2,
    );

    protected $type_data1 = array(
        'id' => 1, 'fullname' => 'Postion Type 1', 'shortname' => 'type1', 'idnumber' => '1', 'description' => 'Position type 1',
        'timecreated' => 0, 'timemodified' => 0, 'usermodified' => 2,
    );

    // Customfield - text
    private $customfield_textinput_data = array(
        'id' => 1, 'shortname' => 'textinput', 'fullname' => 'Text Input', 'name' => 'textinput', 'typeid' => 1, 'datatype' => 'text', 'description' => '', 'categoryid' => 695000,
        'sortorder' => 1, 'hidden' => 0, 'required' => 0, 'locked' => 0, 'visible' => 1, 'forceunique' => 0, 'signup' => 0, 'defaultdata' => '',
        'param1' => 30, 'param2' => 2048, 'param3' => 0, 'param4' => '', 'param5' => '',
    );

    private $importdata = array(

        // Required fields.
        "idnumber" => array(
            "required" => true,
            "tablefieldname" => "idnumber",
            "newdata" => array("1"),
            "editeddata" => array("1"), // Keep the same for this field.
        ),
        "fullname" => array(
            "required" => true,
            "tablefieldname" => "fullname",
            "newdata" => array("pos1"),
            "editeddata" => array("pos1-edited"),
        ),
        "frameworkidnumber" => array(
            "required" => true,
            "tablefieldname" => "frameworkid",
            "newdata" => array(1),
            "editeddata" => array(2)
        ),
        "timemodified" => array(
            "required" => true,
            "tablefieldname" => "timemodified",
            "newdata" => array("0"),
            "editeddata" => array("0"),
        ),

        // Additional fields.
        "shortname" => array(
            "required" => false,
            "tablefieldname" => "shortname",
            "newdata" => array("shortname"),
            "editeddata" => array("shortname-edited"),
            "default" => array("")
        ),
        "description" => array(
            "required" => false,
            "tablefieldname" => "description",
            "newdata" => array("description"),
            "editeddata" => array("description-edited"),
            "default" => array("")
        ),
    );

    protected function tearDown() {
        $this->filedir = null;
        $this->configcsv = null;
        $this->config = null;
        $this->pos_framework_data1 = null;
        $this->pos_framework_data2 = null;
        $this->pos_data1 = null;
        $this->type_data1 = null;
        $this->customfield_textinput_data = null;
        $this->importdata = null;
        parent::tearDown();
    }

    public function setUp() {
        global $CFG;

        parent::setUp();

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $this->filedir = $CFG->dataroot . '/totara_sync';
        mkdir($this->filedir . '/csv/ready', 0777, true);

        set_config('element_pos_enabled', 1, 'totara_sync');
        set_config('source_pos', 'totara_sync_source_pos_csv', 'totara_sync');
        set_config('fileaccess', FILE_ACCESS_DIRECTORY, 'totara_sync');
        set_config('filesdir', $this->filedir, 'totara_sync');

        // Create a Position framework.
        $this->loadDataSet($this->createArrayDataset(array(
            'pos_framework' => array($this->pos_framework_data1, $this->pos_framework_data2),
            'pos' => array($this->pos_data1),
            'pos_type' => array($this->type_data1),
            'pos_type_info_field' => array($this->customfield_textinput_data)
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
            'allow_delete' => '1',
            'allow_update' => '1',
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
        $filepath = $this->filedir . '/csv/ready/pos.csv';
        file_put_contents($filepath, $csvdata);
    }

    public function get_element() {
        $elements = totara_sync_get_elements(true);
        /** @var totara_sync_element_pos $element */
        return $elements['pos'];
    }

    function sync_add_positions() {
        global $DB;

        // Create the CSV file and run the sync.
        $this->create_csv('newdata'); // Create and upload our CSV data file
        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(2, $DB->get_records('pos')); // Check the correct count of positions.

        // Now check each field is populated for position idnumber 1.
        $pos = $this->get_position(1);
        foreach ($this->importdata as $field => $fielddata) {
            if ($fielddata["required"]) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $pos->{$fielddata['tablefieldname']});
                $this->assertNotNull($pos->{$fielddata['tablefieldname']});
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($fielddata["newdata"][0], $pos->{$fielddata['tablefieldname']}, 'Failed for field ' . $field);
            }
        }
    }

    public function get_position($idnumber) {
        global $DB;

        $pos =  $DB->get_record('pos', array('idnumber' => $idnumber));

        // Add the customfields.
        $allcustomfields = $DB->get_records('pos_type_info_field');
        foreach ($allcustomfields as $customfield) {
            $field = 'customfield_' . $customfield->shortname;
            $value = $DB->get_field('pos_type_info_data', 'data', array('fieldid' => $customfield->id, 'positionid' => $pos->id));
            $pos->$field = $value;
        }

        return $pos;
    }

    public function test_sync_add_positions_emptyfields_setting_off_populated_fields() {

        // Adding positions.
        // The 'Empty fields remove data' setting is off.
        // All the fields in the CSV are populated. (not empty)

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_config($config, 'totara_sync_source_pos_csv');
        $config = array_merge($this->config, array('csvsaveemptyfields' => false));
        $this->set_config($config, 'totara_sync_element_pos');

        // Create the CSV file and run the sync and test.
        $this->sync_add_positions();
    }

    public function test_sync_add_positions_emptyfields_setting_on_populated_fields() {

        // Adding positions.
        // The 'Empty fields remove data' setting is on.
        // All the fields in the CSV are populated. (not empty)

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_config($config, 'totara_sync_source_pos_csv');
        $config = array_merge($this->config, array('csvsaveemptyfields' => true));
        $this->set_config($config, 'totara_sync_element_pos');

        // Create the CSV file and run the sync and test.
        $this->sync_add_positions();
    }

    public function test_sync_update_positions_emptyfields_setting_off_populated_fields() {

        // Updating positions.
        // The 'Empty fields remove data' setting is off.
        // All the fields in the CSV are populated. (not empty)

        global $DB;

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_config($config, 'totara_sync_source_pos_csv');
        $config = array_merge($this->config, array('csvsaveemptyfields' => false));
        $this->set_config($config, 'totara_sync_element_pos');

        //
        // First lets add positions.
        //

        $this->sync_add_positions();

        //
        // Now lets update the positions.
        //

        // Create the CSV file and run the sync.
        $this->create_csv('editeddata'); // Create and upload our CSV data file
        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(2, $DB->get_records('pos')); // Check the correct count of positions.

        // Now check each field is populated for position idnumber 1.
        $pos = $this->get_position(1);
        foreach ($this->importdata as $field => $fielddata) {
            if ($fielddata["required"]) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $pos->{$fielddata['tablefieldname']});
                $this->assertNotNull($pos->{$fielddata['tablefieldname']});
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($fielddata["editeddata"][0], $pos->{$fielddata['tablefieldname']}, 'Failed for field ' . $field);
            }
        }
    }

    public function test_sync_update_positions_emptyfields_setting_on_populated_fields() {

        // Updating positions.
        // The 'Empty fields remove data' setting is on.
        // All the fields in the CSV are populated. (not empty)

        global $DB;

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_config($config, 'totara_sync_source_pos_csv');
        $config = array_merge($this->config, array('csvsaveemptyfields' => true));
        $this->set_config($config, 'totara_sync_element_pos');

        //
        // First lets add positions.
        //

        $this->sync_add_positions();

        //
        // Now lets update the positions.
        //

        // Create the CSV file and run the sync.
        $this->create_csv('editeddata'); // Create and upload our CSV data file
        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(2, $DB->get_records('pos')); // Check the correct count of positions.

        // Now check each field is populated for position idnumber 1.
        $pos = $this->get_position(1);
        foreach ($this->importdata as $field => $fielddata) {
            if ($fielddata["required"]) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $pos->{$fielddata['tablefieldname']});
                $this->assertNotNull($pos->{$fielddata['tablefieldname']});
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($fielddata["editeddata"][0], $pos->{$fielddata['tablefieldname']}, 'Failed for field ' . $field);
            }
        }
    }

    public function test_sync_update_positions_emptyfields_setting_off_empty_fields() {

        // Updating positions.
        // The 'Empty fields remove data' setting is off.
        // All the fields in the CSV are empty.

        global $DB;

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_config($config, 'totara_sync_source_pos_csv');
        $config = array_merge($this->config, array('csvsaveemptyfields' => false));
        $this->set_config($config, 'totara_sync_element_pos');

        //
        // First lets add positions.
        //

        $this->sync_add_positions();

        //
        // Now lets update the positions.
        //

        // Create the CSV file and run the sync.
        $this->create_csv('emptydata'); // Create and upload our CSV data file with empty fields.
        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(2, $DB->get_records('pos')); // Check the correct count of positions.

        // Now check each field is populated for position idnumber 1.
        $pos = $this->get_position(1);
        foreach ($this->importdata as $field => $fielddata) {
            if ($fielddata["required"]) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $pos->{$fielddata['tablefieldname']});
                $this->assertNotNull($pos->{$fielddata['tablefieldname']});
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($fielddata["newdata"][0], $pos->{$fielddata['tablefieldname']}, 'Failed for field ' . $field);
            }
        }
    }

    public function test_sync_update_positions_emptyfields_setting_on_empty_fields() {

        // Updating positions.
        // The 'Empty fields remove data' setting is off.
        // All the fields in the CSV are empty.

        global $DB;

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_config($config, 'totara_sync_source_pos_csv');
        $config = array_merge($this->config, array('csvsaveemptyfields' => true));
        $this->set_config($config, 'totara_sync_element_pos');

        //
        // First lets add positions.
        //

        $this->sync_add_positions();

        //
        // Now lets update the positions.
        //

        // Create the CSV file and run the sync.
        $this->create_csv('emptydata'); // Create and upload our CSV data file with empty fields.
        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(2, $DB->get_records('pos')); // Check the correct count of positions.

        // Now check each field is populated for position idnumber 1.
        $pos = $this->get_position(1);
        foreach ($this->importdata as $field => $fielddata) {
            if ($fielddata["required"]) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $pos->{$fielddata['tablefieldname']});
                $this->assertNotNull($pos->{$fielddata['tablefieldname']});
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($fielddata["default"][0], $pos->{$fielddata['tablefieldname']}, 'Failed for field ' . $field);
            }
        }
    }

    public function test_sync_parent() {
        // TODO:
        // All data, (the parent and child) neesd to be in the CSV.
        $this->markTestSkipped('HR Import position source hierarchy needs tests.');
    }

    public function test_sync_type() {
        // TODO: Special case. Can not be included in the $importdata fields to test as causes failures to other fields when changing the type.
        $this->markTestSkipped('HR Import position source changing types needs tests.');

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
        $this->markTestSkipped('HR Import position source custom fields need tests.');
    }

}
