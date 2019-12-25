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
require_once($CFG->dirroot . '/admin/tool/totara_sync/sources/source_pos_csv.php');

/**
 * @group tool_totara_sync
 */
class tool_totara_sync_pos_csv_emptyfields_setting_testcase extends totara_sync_csv_testcase {

    protected $filedir = null;
    protected $configcsv = array();
    protected $config = array();

    protected $elementname = 'pos';
    protected $sourcename = 'totara_sync_source_pos_csv';
    protected $source = null;

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
        'timecreated' => 0, 'timemodified' => 0, 'usermodified' => 2, 'totarasync' => 1,
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

    private $requiredfields = array('idnumber', 'fullname', 'frameworkidnumber', 'timemodified');


    // Expected data uses database field names rather than
    // csv header names.
    private $expected1 = array(
        'idnumber' => 1,
        'fullname' => 'Position 1',
        'shortname' => 'pos1',
        'frameworkid' => 1,
        'timemodified' => 0,
        'description' => 'Description'
    );

    private $expected1_edited = array(
        'idnumber' => 1,
        'fullname' => 'Position 1 edited',
        'shortname' => 'pos1edited',
        'frameworkid' => 2,
        'timemodified' => 0,
        'description' => 'Description edited'
    );

    private $expected2 = array(
        'idnumber' => 1,
        'fullname' => 'Position 1',
        'shortname' => '',
        'frameworkid' => 1,
        'timemodified' => 0,
        'description' => ''
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

    public function get_element() {
        $elements = totara_sync_get_elements(true);
        /** @var totara_sync_element_pos $element */
        return $elements['pos'];
    }

    /**
     * Helper function to get a position given an idnumber.
     *
     * @param $idnumber
     *
     * @return stdClass An object containing the details of the position.
     */
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

    /**
     * Test syncing positions when emptyfields setting is set to "Ignore" and all
     * fields in the CSV are populated.
     */
    public function test_sync_add_positions_emptyfields_setting_off_populated_fields() {
        global $DB;

        // Adding positions.
        // The 'Empty fields remove data' setting is off.
        // All the fields in the CSV are populated. (not empty)

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_source_config($config);
        $config = array_merge($this->config, array('csvsaveemptyfields' => false));
        $this->set_element_config($config);

        // Add the CSV file and run the sync.
        $this->add_csv('position_empty_fields_1.csv', 'pos');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(2, $DB->get_records('pos')); // Check the correct count of positions.

        // Now check each field is populated for position idnumber 1.
        $pos = $this->get_position(1);
        foreach ($this->expected1 as $field => $value) {
            if (in_array($field, $this->requiredfields)) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $pos->$field);
                $this->assertNotNull($pos->$field);
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($value, $pos->$field, 'Failed for field ' . $field);
            }
        }
    }

    public function test_sync_add_positions_emptyfields_setting_on_populated_fields() {
        global $DB;

        // Adding positions.
        // The 'Empty fields remove data' setting is on.
        // All the fields in the CSV are populated. (not empty)

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_source_config($config);
        $config = array_merge($this->config, array('csvsaveemptyfields' => true));
        $this->set_element_config($config);

        // Load the CSV file and run the sync.
        $this->add_csv('position_empty_fields_1.csv', 'pos');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(2, $DB->get_records('pos')); // Check the correct count of positions.

        // Now check each field is populated for position idnumber 1.
        $pos = $this->get_position(1);
        foreach ($this->expected1 as $field => $value) {
            if (in_array($field, $this->requiredfields)) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $pos->$field);
                $this->assertNotNull($pos->$field);
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($value, $pos->$field, 'Failed for field ' . $field);
            }
        }
    }

    public function test_sync_update_positions_emptyfields_setting_off_populated_fields() {
        global $DB;

        // Updating positions.
        // The 'Empty fields remove data' setting is off.
        // All the fields in the CSV are populated. (not empty)

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_source_config($config);
        $config = array_merge($this->config, array('csvsaveemptyfields' => false));
        $this->set_element_config($config);

        // First add a position we can update.
        $position = array(
            'id' => 2,
            'fullname' => 'Position 1',
            'shortname' => 'pos1',
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
            'pos' => array($position)
        )));

        $this->assertCount(2, $DB->get_records('pos')); // Check the correct count of positions.

        //
        // Now lets update the positions.
        //

        // Create the CSV file and run the sync.
        // Add import file with edited data and run sync.
        $this->add_csv('position_empty_fields_2.csv', 'pos');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(2, $DB->get_records('pos')); // Check the correct count of positions.

        // Now check each field is populated for position idnumber 1.
        $pos = $this->get_position(1);
        foreach ($this->expected1_edited as $field => $value) {
            if (in_array($field, $this->requiredfields)) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $pos->$field);
                $this->assertNotNull($pos->$field);
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($value, $pos->$field, 'Failed for field ' . $field);
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
        $this->set_source_config($config);
        $config = array_merge($this->config, array('csvsaveemptyfields' => true));
        $this->set_element_config($config);

        // First add a position we can update.
        $position = array(
            'id' => 2,
            'fullname' => 'Position 1',
            'shortname' => 'pos1',
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
            'pos' => array($position)
        )));

        $this->assertCount(2, $DB->get_records('pos')); // Check the correct count of positions.

        //
        // Now lets update the positions.
        //

        // Create the CSV file and run the sync.
        $this->add_csv('position_empty_fields_2.csv', 'pos');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(2, $DB->get_records('pos')); // Check the correct count of positions.

        // Now check each field is populated for position idnumber 1.
        $pos = $this->get_position(1);
        foreach ($this->expected1_edited as $field => $value) {
            if (in_array($field, $this->requiredfields)) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $pos->$field);
                $this->assertNotNull($pos->$field);
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($value, $pos->$field, 'Failed for field ' . $field);
            }
        }
    }


    /**
     * Test updating positions with empty values and
     * the 'Empty fields remove data' setting is off.
     * We expect that empty fields don't change the values.
     */
    public function test_sync_update_positions_emptyfields_setting_off_empty_fields() {


        global $DB;

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_source_config($config);
        $config = array_merge($this->config, array('csvsaveemptyfields' => false));
        $this->set_element_config($config);

        // First add a position we can update.
        $position = array(
            'id' => 2,
            'fullname' => 'Position 1',
            'shortname' => 'pos1',
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
            'pos' => array($position)
        )));
        $this->assertCount(2, $DB->get_records('pos')); // Check the correct count of positions.

        //
        // Now lets update the positions.
        //

        // Create the CSV file and run the sync.
        $this->add_csv('position_empty_fields_3.csv', 'pos');

        // Sync CSV with empty fields in non-required columns.
        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(2, $DB->get_records('pos')); // Check the correct count of positions.

        // Now check each field is populated for position idnumber 1.
        // None of the values in the fields should have changed.
        $pos = $this->get_position(1);
        foreach ($this->expected1 as $field => $value) {
            if (in_array($field, $this->requiredfields)) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $pos->$field);
                $this->assertNotNull($pos->$field);
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($value, $pos->$field, 'Failed for field ' . $field);
            }
        }
    }

    /**
     * Test updating positions with empty values and
     * the 'Empty fields remove data' setting is on.
     * We expect that empty values in the CSV remove the value/reset values.
     */
    public function test_sync_update_positions_emptyfields_setting_on_empty_fields() {

        // Updating positions.
        // The 'Empty fields remove data' setting is off.
        // All the fields in the CSV are empty.

        global $DB;

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_source_config($config);
        $config = array_merge($this->config, array('csvsaveemptyfields' => true));
        $this->set_element_config($config);

        // First add a position we can update.
        $position = array(
            'id' => 2,
            'fullname' => 'Position 1',
            'shortname' => 'pos1',
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
            'pos' => array($position)
        )));
        $this->assertCount(2, $DB->get_records('pos')); // Check the correct count of positions.

        //
        // Now lets update the positions.
        //
        // Create the CSV file and run the sync.
        $this->add_csv('position_empty_fields_3.csv', 'pos');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.
        $this->assertCount(2, $DB->get_records('pos')); // Check the correct count of positions.

        // Now check each field is populated for position idnumber 1.
        // shortname and description should be blank.
        $pos = $this->get_position(1);
        foreach ($this->expected2 as $field => $value) {
            if (in_array($field, $this->requiredfields)) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $pos->$field);
                $this->assertNotNull($pos->$field);
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($value, $pos->$field, 'Failed for field ' . $field);
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

        // Add positions (2 positions, one with a parent).
        $position1 = array(
            'id' => 2,
            'fullname' => 'Position 1',
            'shortname' => 'pos1',
            'idnumber' => 'POS1',
            'description' => 'Description 1',
            'frameworkid' => 1,
            'path' => '/2',
            'depthlevel' => 1,
            'parentid' => 0,
            'sortthread' => '02',
            'visible' => 1,
            'timevalidfrom' => 0,
            'timevalidto' => 0,
            'timecreated' => 0,
            'timemodified' => 0,
            'usermodified' => 2,
            'totarasync' => 1
        );
        $position2 = array(
            'id' => 3,
            'fullname' => 'Position 2',
            'shortname' => 'pos2',
            'idnumber' => 'POS2',
            'description' => 'Description 2',
            'frameworkid' => 1,
            'path' => '/2/3',
            'depthlevel' => 2,
            'parentid' => 2,
            'sortthread' => '02.01',
            'visible' => 1,
            'timevalidfrom' => 0,
            'timevalidto' => 0,
            'timecreated' => 0,
            'timemodified' => 0,
            'usermodified' => 2,
            'totarasync' => 1
        );

        $this->loadDataSet($this->createArrayDataset(array(
            'pos' => array($position1, $position2)
        )));
        $this->assertCount(3, $DB->get_records('pos')); // Check the correct count of positions.

        // Check that the import and parent was assigned correctly.
        $position1 = $this->get_position('POS1');
        $position2 = $this->get_position('POS2');
        $this->assertEquals('Position 2', $position2->fullname);
        $this->assertEquals($position1->id, $position2->parentid);
        $this->assertEquals(2, $position2->depthlevel);

        // Create CSV and sync.
        $this->add_csv('position_empty_fields_4.csv', 'pos');

        $this->assertCount(3, $DB->get_records('pos'));
        $this->assertTrue($this->get_element()->sync());
        $this->assertCount(3, $DB->get_records('pos'));

        // Get the new record for Position 2 (it shouldn't have changed).
        $position2 = $this->get_position('POS2');
        $this->assertEquals($position1->id, $position2->parentid);
        $this->assertEquals(2, $position2->depthlevel);
    }

    public function test_sync_parent_with_emptyfields_setting_on() {
        global $DB;

        // Set the config.
        $additional_fields = array('import_parentidnumber' => 1);
        $config = array_merge($this->configcsv, $this->importfields(), $additional_fields);
        $this->set_source_config($config);
        $config = array_merge($this->config, array('csvsaveemptyfields' => true));
        $this->set_element_config($config);

        // Add positions (2 positions, one with a parent).
        $position1 = array(
            'id' => 2,
            'fullname' => 'Position 1',
            'shortname' => 'pos1',
            'idnumber' => 'POS1',
            'description' => 'Description 1',
            'frameworkid' => 1,
            'path' => '/2',
            'depthlevel' => 1,
            'parentid' => 0,
            'sortthread' => '02',
            'visible' => 1,
            'timevalidfrom' => 0,
            'timevalidto' => 0,
            'timecreated' => 0,
            'timemodified' => 0,
            'usermodified' => 2,
            'totarasync' => 1
        );
        $position2 = array(
            'id' => 3,
            'fullname' => 'Position 2',
            'shortname' => 'pos2',
            'idnumber' => 'POS2',
            'description' => 'Description 2',
            'frameworkid' => 1,
            'path' => '/2/3',
            'depthlevel' => 2,
            'parentid' => 2,
            'sortthread' => '02.01',
            'visible' => 1,
            'timevalidfrom' => 0,
            'timevalidto' => 0,
            'timecreated' => 0,
            'timemodified' => 0,
            'usermodified' => 2,
            'totarasync' => 1
        );

        $this->loadDataSet($this->createArrayDataset(array(
            'pos' => array($position1, $position2)
        )));
        $this->assertCount(3, $DB->get_records('pos')); // Check the correct count of positions.

        // Check that the import and parent was assigned correctly.
        $position1 = $this->get_position('POS1');
        $position2 = $this->get_position('POS2');
        $this->assertEquals('Position 2', $position2->fullname);
        $this->assertEquals($position1->id, $position2->parentid);

        // Create CSV and sync.
        $this->add_csv('position_empty_fields_4.csv', 'pos');

        $this->assertCount(3, $DB->get_records('pos'));
        $this->assertTrue($this->get_element()->sync());
        $this->assertCount(3, $DB->get_records('pos'));

        // Get the new record for Position 2.
        $position2 = $this->get_position('POS2');
        $this->assertEquals(0, $position2->parentid); // Parent ID should have been removed.
        $this->assertEquals(1, $position2->depthlevel);
    }

    public function test_empty_frameworkidnumber_ignore_emptyfields() {
        global $DB;

        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_source_config($config);
        $config = array_merge($this->config, array('csvsaveemptyfields' => false));
        $this->set_element_config($config);

        $this->add_csv('position_empty_fields_5.csv', 'pos');

        $this->assertTrue($this->get_element()->sync()); // Run the sync.

        $position2 = $this->get_position('777');

        // Check that the update change the record
        $this->assertCount(1, $DB->get_records('pos')); // Check the correct count of positions.
        $this->assertEquals('Position 2', $position2->fullname);
        $this->assertEquals(1, $position2->frameworkid);
    }

    /**
     * An exception is expected here as the file being imported contains an
     * empty framework id which is not allowed.
     *
     * @expectedException moodle_exception
     */
    public function test_empty_frameworkidnumber_save_emptyfields() {
        global $DB;

        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_source_config($config);
        $config = array_merge($this->config, array('csvsaveemptyfields' => true));
        $this->set_element_config($config);

        $this->add_csv('position_empty_fields_5.csv', 'pos');

        $this->get_element()->sync(); // Run the sync.

        $position2 = $this->get_position('777');

        $this->assertCount(1, $DB->get_records('pos')); // Check the correct count of positions.
        $this->assertEquals('Position 2', $position2->fullname);
        $this->assertEquals(1, $position2->frameworkid);
    }

    /**
     * Test creating a positions with an empty parent idnumber
     */
    public function test_create_pos_with_empty_parentidnumber() {
        global $DB;

        $extraimportfields = array('import_parentidnumber' => 1);
        $config = array_merge($this->configcsv, $this->importfields(), $extraimportfields);
        $this->set_source_config($config);
        $config = array_merge($this->config, array('csvsaveemptyfields' => false));
        $this->set_element_config($config);

        $this->add_csv('position_empty_fields_6.csv', 'pos');

        $this->get_element()->sync(); // Run the sync.

        $position = $this->get_position('888');

        $this->assertCount(2, $DB->get_records('pos')); // Check the correct count of positions.
        $this->assertEquals('Position 8', $position->fullname);
        $this->assertEquals(1, $position->frameworkid);
        $this->assertEquals(0, $position->parentid);
    }

    /**
     *  We expect an exception here as it is impossible to create a new item
     *  with a blank framework id
     *
     *  @expectedException moodle_exception
     */
    public function test_create_pos_with_empty_frameworkidnumber() {
        global $DB;

        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_source_config($config);
        $config = array_merge($this->config, array('csvsaveemptyfields' => false));
        $this->set_element_config($config);

        $this->add_csv('position_empty_fields_7.csv', 'pos');

        $this->get_element()->sync(); // Run the sync.

        // The sync will generate an exception so nothing else to check.
    }
}
