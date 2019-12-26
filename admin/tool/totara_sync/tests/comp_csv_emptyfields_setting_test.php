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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package tool_totara_sync
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/totara_sync/lib.php');
require_once($CFG->dirroot . '/admin/tool/totara_sync/tests/source_csv_testcase.php');
require_once($CFG->dirroot . '/admin/tool/totara_sync/sources/source_comp_csv.php');

/**
 * @group tool_totara_sync
 */
class tool_totara_sync_comp_csv_emptyfields_setting_testcase extends totara_sync_csv_testcase {

    protected $config = array();

    protected $elementname = 'comp';
    protected $sourcename = 'totara_sync_source_comp_csv';

    /* @var totara_sync_element_comp */
    protected $element;

    protected $comp_framework_data1 = array(
        'id' => 1, 'fullname' => 'Framework 1', 'shortname' => 'FW1', 'idnumber' => '1', 'description' => 'Description 1',
        'sortorder' => 1, 'visible' => 1, 'hidecustomfields' => 0, 'timecreated' => 1265963591, 'timemodified' => 1265963591, 'usermodified' => 2,
    );

    protected $comp_framework_data2 = array(
        'id' => 2, 'fullname' => 'Framework 2', 'shortname' => 'OFW2', 'idnumber' => '2', 'description' => 'Description 2',
        'sortorder' => 2, 'visible' => 1, 'hidecustomfields' => 0, 'timecreated' => 1265963591, 'timemodified' => 1265963591, 'usermodified' => 2,
    );

    protected $comp_data1 = array(
        'id' => 1, 'fullname' => 'Top', 'shortname' => 'top', 'idnumber' => '777', 'description' => 'Top level competency', 'frameworkid' => 1,
        'path' => '/1', 'depthlevel' => 1, 'parentid' => 0, 'sortthread' => '01', 'visible' => 1, 'timevalidfrom' => 0, 'timevalidto' => 0,
        'timecreated' => 0, 'timemodified' => 0, 'usermodified' => 2, 'aggregationmethod' => 1, 'proficiencyexpected' => 1, 'evidencecount' => 0,
    );

    protected $type_data1 = array(
        'id' => 1, 'fullname' => 'Type 1', 'shortname' => 'type1', 'idnumber' => '1', 'description' => 'type 1',
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
        'fullname' => 'Competency 1',
        'shortname' => 'comp1',
        'frameworkid' => 1,
        'timemodified' => 0,
        'description' => 'Description',
        'aggregationmethod' => 1
    );

    private $expected1_edited = array(
        'idnumber' => 1,
        'fullname' => 'Competency 1 edited',
        'shortname' => 'comp1edited',
        'frameworkid' => 2,
        'timemodified' => 0,
        'description' => 'Description edited',
        'aggregationmethod' => 2
    );

    private $expected2 = array(
        'idnumber' => 1,
        'fullname' => 'Competency 1',
        'shortname' => '',
        'frameworkid' => 1,
        'timemodified' => 0,
        'description' => '',
        'aggregationmethod' => 2
    );

    protected function tearDown() {
        $this->config = null;
        $this->comp_framework_data1 = null;
        $this->comp_framework_data2 = null;
        $this->comp_data1 = null;
        $this->type_data1 = null;
        $this->customfield_textinput_data = null;
        $this->source = null;
        $this->element = null;
        parent::tearDown();
    }

    public function setUp() {
        parent::setUp();

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $this->source = new $this->sourcename();
        $this->element = new totara_sync_element_comp();
        $this->element->source = $this->source;

        set_config('element_comp_enabled', 1, 'totara_sync');
        set_config('source_comp', 'totara_sync_source_comp_csv', 'totara_sync');
        set_config('fileaccess', TOTARA_SYNC_FILE_ACCESS_MEMORY, 'totara_sync');

        // Create a Competency framework.
        $this->loadDataSet($this->createArrayDataset(array(
            'comp_framework' => array($this->comp_framework_data1, $this->comp_framework_data2),
            'comp' => array($this->comp_data1),
            'comp_type' => array($this->type_data1),
            'comp_type_info_field' => array($this->customfield_textinput_data)
        )));

        $this->set_source_config([
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

            'import_parentidnumber' => '0',
            'import_typeidnumber' => '0',
            'import_idnumber' => '1',
            'import_fullname' => '1',
            'import_shortname' => '1',
            'import_frameworkidnumber' => '1',
            'import_timemodified' => '1',
            'import_description' => '1',
            'import_aggregationmethod' => '1',

            // Customfields.
            'fieldmapping_customfield_textinput' => '',
            'import_customfield_textinput' => '0',

        ]);

        $this->element->set_config('sourceallrecords', '1');
        $this->element->set_config('allow_create', '1');
        $this->element->set_config('allow_delete', '0');
        $this->element->set_config('allow_update', '1');
    }

    public function get_competency($idnumber) {
        global $DB;

        $comp =  $DB->get_record('comp', array('idnumber' => $idnumber));

        // Add the customfields.
        $allcustomfields = $DB->get_records('comp_type_info_field');
        foreach ($allcustomfields as $customfield) {
            $field = 'customfield_' . $customfield->shortname;
            $value = $DB->get_field('comp_type_info_data', 'data', array('fieldid' => $customfield->id, 'competencyid' => $comp->id));
            $comp->$field = $value;
        }

        return $comp;
    }

    public function test_sync_add_competencies_emptyfields_setting_off_populated_fields() {
        global $DB;

        // Adding competencies.
        // The 'Empty fields remove data' setting is off.
        // All the fields in the CSV are populated. (not empty)

        $this->element->set_config('csvsaveemptyfields', false);

        $csv = "idnumber,fullname,shortname,frameworkidnumber,timemodified,description,parentidnumber,aggregationmethod\n";
        $csv .= "1,Competency 1,comp1,1,0,Description,,1";
        $this->source->set_csv_in_memory($csv);

        $this->assertTrue($this->element->sync()); // Run the sync.
        $this->assertCount(2, $DB->get_records('comp')); // Check the correct count of competencies.

        // Now check each field is populated for competency idnumber 1.
        $comp = $this->get_competency(1);
        foreach ($this->expected1 as $field => $value) {
            if (in_array($field, $this->requiredfields)) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $comp->$field);
                $this->assertNotNull($comp->$field);
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($value, $comp->$field, 'Failed for field ' . $field);
            }
        }
    }

    public function test_sync_add_competencies_emptyfields_setting_on_populated_fields() {
        global $DB;

        // Adding competencys.
        // The 'Empty fields remove data' setting is on.
        // All the fields in the CSV are populated. (not empty)

        $this->element->set_config('csvsaveemptyfields', true);

        $element = $this->get_element();
        $element->source = new totara_sync_source_comp_csv();
        $csv = "idnumber,fullname,shortname,frameworkidnumber,timemodified,description,parentidnumber,aggregationmethod\n";
        $csv .= "1,Competency 1,comp1,1,0,Description,,1";
        $element->source->set_csv_in_memory($csv);

        $this->assertTrue($element->sync()); // Run the sync.
        $this->assertCount(2, $DB->get_records('comp')); // Check the correct count of competencies.

        // Now check each field is populated for competency idnumber 1.
        $comp = $this->get_competency(1);
        foreach ($this->expected1 as $field => $value) {
            if (in_array($field, $this->requiredfields)) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $comp->$field);
                $this->assertNotNull($comp->$field);
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($value, $comp->$field, 'Failed for field ' . $field);
            }
        }
    }

    public function test_sync_update_competencies_emptyfields_setting_off_populated_fields() {
        global $DB;

        // Updating competencies.
        // The 'Empty fields remove data' setting is off.
        // All the fields in the CSV are populated. (not empty)

        $this->element->set_config('csvsaveemptyfields', false);

        // First add an competency we can update.
        $competency = array(
            'id' => 2,
            'fullname' => 'competency 1',
            'shortname' => 'comp1',
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
            'totarasync' => 1,
            'aggregationmethod' => 1,
            'proficiencyexpected' => 1,
            'evidencecount' => 0,
        );

        $this->loadDataSet($this->createArrayDataset(array(
            'comp' => array($competency)
        )));

        //
        // Now lets update the competency.
        //

        $element = $this->get_element();
        $element->source = new totara_sync_source_comp_csv();
        $csv = "idnumber,fullname,shortname,frameworkidnumber,timemodified,description,parentidnumber,aggregationmethod\n";
        $csv .= "1,Competency 1 edited,comp1edited,2,0,Description edited,,2";
        $element->source->set_csv_in_memory($csv);

        $this->assertTrue($element->sync()); // Run the sync.
        $this->assertCount(2, $DB->get_records('comp')); // Check the correct count of competencies.

        // Now check each field is populated for competency idnumber 1.
        $comp = $this->get_competency(1);
        foreach ($this->expected1_edited as $field => $value) {
            if (in_array($field, $this->requiredfields)) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $comp->$field);
                $this->assertNotNull($comp->$field);
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($value, $comp->$field, 'Failed for field ' . $field);
            }
        }
    }

    public function test_sync_update_competencies_emptyfields_setting_on_populated_fields() {
        global $DB;

        // Updating competencies.
        // The 'Empty fields remove data' setting is on.
        // All the fields in the CSV are populated. (not empty)

        $this->element->set_config('csvsaveemptyfields', true);

        $competency = array(
            'id' => 2,
            'fullname' => 'competency 1',
            'shortname' => 'comp1',
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
            'totarasync' => 1,
            'aggregationmethod' => 1,
            'proficiencyexpected' => 1,
            'evidencecount' => 0,
        );

        $this->loadDataSet($this->createArrayDataset(array(
            'comp' => array($competency)
        )));

        //
        // Now lets update the competencies.
        //

        $element = $this->get_element();
        $element->source = new totara_sync_source_comp_csv();
        $csv = "idnumber,fullname,shortname,frameworkidnumber,timemodified,description,parentidnumber,aggregationmethod\n";
        $csv .= "1,Competency 1 edited,comp1edited,2,0,Description edited,,2";
        $element->source->set_csv_in_memory($csv);

        $this->assertTrue($element->sync()); // Run the sync.
        $this->assertCount(2, $DB->get_records('comp')); // Check the correct count of competencies.

        // Now check each field is populated for competency idnumber 1.
        $comp = $this->get_competency(1);
        foreach ($this->expected1_edited as $field => $value) {
            if (in_array($field, $this->requiredfields)) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $comp->$field);
                $this->assertNotNull($comp->$field);
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($value, $comp->$field, 'Failed for field ' . $field);
            }
        }
    }

    public function test_sync_update_competencies_emptyfields_setting_off_empty_fields() {
        global $DB;

        // Updating competencies.
        // The 'Empty fields remove data' setting is off.
        // All the fields in the CSV are empty.

        $this->element->set_config('csvsaveemptyfields', false);

        // First add an competency we can update.
        $competency = array(
            'id' => 2,
            'fullname' => 'competency 1',
            'shortname' => 'comp1',
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
            'totarasync' => 1,
            'aggregationmethod' => 1,
            'proficiencyexpected' => 1,
            'evidencecount' => 0,
        );

        $this->loadDataSet($this->createArrayDataset(array(
            'comp' => array($competency)
        )));

        //
        // Now lets update the competencies.
        //

        $element = $this->get_element();
        $element->source = new totara_sync_source_comp_csv();
        $csv = "idnumber,fullname,shortname,frameworkidnumber,timemodified,description,parentidnumber,aggregationmethod\n";
        $csv .= "1,Competency 1,,1,0,,,";
        $element->source->set_csv_in_memory($csv);

        $this->assertTrue($element->sync()); // Run the sync.
        $this->assertCount(2, $DB->get_records('comp')); // Check the correct count of competencies.

        // Now check each field is populated for competency idnumber 1.
        $comp = $this->get_competency(1);
        foreach ($this->expected1 as $field => $value) {
            if (in_array($field, $this->requiredfields)) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $comp->$field);
                $this->assertNotNull($comp->$field);
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($value, $comp->$field, 'Failed for field ' . $field);
            }
        }
    }

    public function test_sync_update_competencies_emptyfields_setting_on_empty_fields() {
        global $DB;

        // Updating competencies.
        // The 'Empty fields remove data' setting is off.
        // All the fields in the CSV are empty.

        $this->element->set_config('csvsaveemptyfields', true);

        // First add an competency we can update.
        $competency = array(
            'id' => 2,
            'fullname' => 'competency 1',
            'shortname' => 'comp1',
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
            'totarasync' => 1,
            'aggregationmethod' => 2,
            'proficiencyexpected' => 1,
            'evidencecount' => 0,
        );

        $this->loadDataSet($this->createArrayDataset(array(
            'comp' => array($competency)
        )));

        //
        // Now lets update the competency.
        //

        $element = $this->get_element();
        $element->source = new totara_sync_source_comp_csv();
        $csv = "idnumber,fullname,shortname,frameworkidnumber,timemodified,description,parentidnumber,aggregationmethod\n";
        $csv .= "1,Competency 1,,1,0,,,2";
        $element->source->set_csv_in_memory($csv);

        $this->assertTrue($element->sync()); // Run the sync.
        $this->assertCount(2, $DB->get_records('comp')); // Check the correct count of competencies.

        // Now check each field is populated for competency idnumber 1.
        $comp = $this->get_competency(1);
        foreach ($this->expected2 as $field => $value) {
            if (in_array($field, $this->requiredfields)) {
                // For required fields we just want to check they are not empty/null.
                $this->assertNotEquals('', $comp->$field);
                $this->assertNotNull($comp->$field);
            } else {
                // Check the data matches the value in the CSV.
                $this->assertEquals($value, $comp->$field, 'Failed for field ' . $field);
            }
        }
    }

    public function test_sync_update_competencies_aggregationmethod_cannot_be_emptied() {
        global $DB;

        // Updating competencies.
        // The 'Empty fields remove data' setting is off.
        // All the fields in the CSV are empty.

        $this->element->set_config('csvsaveemptyfields', true);

        // First add an competency we can update.
        $competency = array(
            'id' => 2,
            'fullname' => 'competency 1',
            'shortname' => 'comp1',
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
            'totarasync' => 1,
            'aggregationmethod' => 2,
            'proficiencyexpected' => 1,
            'evidencecount' => 0,
        );

        $this->loadDataSet($this->createArrayDataset(array(
            'comp' => array($competency)
        )));

        //
        // Now lets update the competency.
        //

        $element = $this->get_element();
        $element->source = new totara_sync_source_comp_csv();
        $csv = "idnumber,fullname,shortname,frameworkidnumber,timemodified,description,parentidnumber,aggregationmethod\n";
        $csv .= "1,Competency 1,,1,0,,,";
        $element->source->set_csv_in_memory($csv);

        ob_start();
        $this->assertTrue($element->sync()); // Run the sync.
        ob_end_clean();

        $this->assertEquals(1, $DB->count_records('totara_sync_log', [
            'element' => 'comp',
            'logtype' => 'error',
            'action' => 'populatesynctablecsv',
            'info' => 'Unrecognised aggregation method value: '])
        );
    }

    public function test_sync_parent_with_emptyfields_setting_off() {
        global $DB;

        $this->source->set_config('import_parentidnumber', 1);
        $this->element->set_config('csvsaveemptyfields', false);

        // First add some competencies we can update.
        $competency1 = array(
            'id' => 2,
            'fullname' => 'competency 1',
            'shortname' => 'comp1',
            'idnumber' => 'comp1',
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
            'totarasync' => 1,
            'aggregationmethod' => 1,
            'proficiencyexpected' => 1,
            'evidencecount' => 0,
        );

        $competency2 = array(
            'id' => 3,
            'fullname' => 'competency 2',
            'shortname' => 'comp2',
            'idnumber' => 'comp2',
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
            'totarasync' => 1,
            'aggregationmethod' => 1,
            'proficiencyexpected' => 1,
            'evidencecount' => 0,
        );

        $this->loadDataSet($this->createArrayDataset(array(
            'comp' => array($competency1, $competency2)
        )));

        $this->assertCount(3, $DB->get_records('comp')); // Check the correct count of competencies.

        // Check that the import and parent was assigned correctly.
        $competency1 = $this->get_competency('comp1');
        $competency2 = $this->get_competency('comp2');
        $this->assertEquals('competency 2', $competency2->fullname);
        $this->assertEquals($competency1->id, $competency2->parentid);
        $this->assertEquals(2, $competency2->depthlevel);

        $element = $this->get_element();
        $element->source = new totara_sync_source_comp_csv();
        $csv = "idnumber,fullname,shortname,frameworkidnumber,timemodified,description,parentidnumber,aggregationmethod\n";
        $csv .= "comp2,Competency 2,comp2,1,0,Description 2,,1";
        $element->source->set_csv_in_memory($csv);

        $this->assertCount(3, $DB->get_records('comp'));
        $this->assertTrue($element->sync());
        $this->assertCount(3, $DB->get_records('comp'));

        // Get the new record for competency 2 (it shouldn't have changed).
        $competency2 = $this->get_competency('comp2');
        $this->assertEquals($competency1->id, $competency2->parentid);
        $this->assertEquals(2, $competency2->depthlevel);
    }

    public function test_sync_parent_with_emptyfields_setting_on() {
        global $DB;

        $this->source->set_config('import_parentidnumber', 1);
        $this->element->set_config('csvsaveemptyfields', true);

        // First add some competencies we can update.
        $competency1 = array(
            'id' => 2,
            'fullname' => 'competency 1',
            'shortname' => 'comp1',
            'idnumber' => 'comp1',
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
            'totarasync' => 1,
            'aggregationmethod' => 1,
            'proficiencyexpected' => 1,
            'evidencecount' => 0,
        );

        $competency2 = array(
            'id' => 3,
            'fullname' => 'competency 2',
            'shortname' => 'comp2',
            'idnumber' => 'comp2',
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
            'totarasync' => 1,
            'aggregationmethod' => 1,
            'proficiencyexpected' => 1,
            'evidencecount' => 0,
        );

        $this->loadDataSet($this->createArrayDataset(array(
            'comp' => array($competency1, $competency2)
        )));

        // Check we are starting with the number of comps.
        $this->assertCount(3, $DB->get_records('comp'));

        // Check that the import and parent was assigned correctly.
        $competency1 = $this->get_competency('comp1');
        $competency2 = $this->get_competency('comp2');
        $this->assertEquals('competency 2', $competency2->fullname);
        $this->assertEquals($competency1->id, $competency2->parentid);

        $element = $this->get_element();
        $element->source = new totara_sync_source_comp_csv();
        $csv = "idnumber,fullname,shortname,frameworkidnumber,timemodified,description,parentidnumber,aggregationmethod\n";
        $csv .= "comp2,Competency 2,comp2,1,0,Description 2,,1";
        $element->source->set_csv_in_memory($csv);

        $this->assertCount(3, $DB->get_records('comp'));
        $this->assertTrue($element->sync());
        $this->assertCount(3, $DB->get_records('comp'));

        // Get the new record for competency 2.
        $competency2 = $this->get_competency('comp2');
        $this->assertEquals(0, $competency2->parentid); // competency ID should have been removed.
        $this->assertEquals(1, $competency2->depthlevel);
    }

    public function test_empty_frameworkidnumber_ignore_emptyfields() {
        global $DB;

        $this->element->set_config('csvsaveemptyfields', false);

        $element = $this->get_element();
        $element->source = new totara_sync_source_comp_csv();
        $csv = "idnumber,fullname,shortname,frameworkidnumber,timemodified,description,parentidnumber,aggregationmethod\n";
        $csv .= "777,Competency 2,comp2,,0,Description 2,,1";
        $element->source->set_csv_in_memory($csv);

        $this->assertTrue($element->sync());

        $this->assertCount(1, $DB->get_records('comp')); // Check the correct count of competencys.
    }

    /**
     * An exception is exptected from the sync() function here as the file being
     * imported contains an empty framework id which is not allowed.
     *
     * @expectedException moodle_exception
     */
    public function test_empty_frameworkidnumber_save_emptyfields() {

        $this->element->set_config('csvsaveemptyfields', true);

        $element = $this->get_element();
        $element->source = new totara_sync_source_comp_csv();
        $csv = "idnumber,fullname,shortname,frameworkidnumber,timemodified,description,parentidnumber,aggregationmethod\n";
        $csv .= "777,Competency 2,comp2,,0,Description 2,,1";
        $element->source->set_csv_in_memory($csv);

        $element->sync();
    }
}
