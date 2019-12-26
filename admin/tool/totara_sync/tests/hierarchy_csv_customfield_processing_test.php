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

use tool_totara_sync\internal\hierarchy\customfield;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/totara_sync/elements/org.php');
require_once($CFG->dirroot . '/admin/tool/totara_sync/elements/pos.php');

/**
 * Class tool_totara_sync_hierarchy_csv_customfield_processing_testcase
 *
 * This runs tests of logic for processing custom fields where it is common to all hierarchy types.
 */
class tool_totara_sync_hierarchy_csv_customfield_processing_testcase extends advanced_testcase {

    public function setUp() {
        parent::setUp();
        ob_start();
    }

    public function tearDown() {
        ob_end_clean();
        parent::tearDown();
    }

    /**
     * Data provider for test methods.
     *
     * Provides the short and full names of hierarchy types.
     *
     * @return array
     */
    public function get_hierarchy_names() {
        return [
            ['org', 'organisation'],
            ['pos', 'position']
        ];
    }

    /**
     * Creates import settings.
     *
     * @param string $hierarchyshortname
     */
    private function set_up_importing($hierarchyshortname) {

        set_config('element_' . $hierarchyshortname . '_enabled', 1, 'totara_sync');
        set_config('source_' . $hierarchyshortname, 'totara_sync_source_' . $hierarchyshortname . '_csv', 'totara_sync');
        set_config('fileaccess', TOTARA_SYNC_FILE_ACCESS_MEMORY, 'totara_sync');

        $sourceconfig = [
            'csvuserencoding' => 'UTF-8',
            'delimiter' => ',',
            'csvsaveemptyfields' => false,

            'import_idnumber' => '1',
            'import_fullname' => '1',
            'import_typeidnumber' => '1',
            'import_frameworkidnumber' => '1',
        ];
        foreach($sourceconfig as $setting => $value) {
            set_config($setting, $value, 'totara_sync_source_' . $hierarchyshortname . '_csv');
        }

        $elementconfig = [
            'allow_create' => '1',
            'allow_delete' => '1',
            'allow_update' => '1',
            'sourceallrecords' => 1,
        ];
        foreach($elementconfig as $setting => $value) {
            set_config($setting, $value, 'totara_sync_element_' . $hierarchyshortname);
        }
    }

    /**
     * Creates data required for testing custom fields.
     *
     * Includes framework, types and several text custom fields.
     *
     * @param string $hierarchyshortname
     * @param string $hierarchyfullname
     */
    private function create_data_for_multiple_customfields($hierarchyshortname, $hierarchyfullname) {
        /* @var totara_hierarchy_generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $hierarchy_generator->create_framework(
            $hierarchyfullname,
            ['idnumber' => 'fw1']
        );

        $type1id = $hierarchy_generator->create_hierarchy_type(
            $hierarchyfullname,
            ['idnumber' => 'typeidnumber1', 'fullname' => 'Type 1 full name']
        );
        $type2id = $hierarchy_generator->create_hierarchy_type(
            $hierarchyfullname,
            ['idnumber' => 'typeidnumber2', 'fullname' => 'Type 2 full name']
        );

        $this->create_text_custom_field(
            $hierarchyshortname,
            [
                'fullname' => 'field A',
                'shortname' => 'customfieldA',
                'typeid' => $type1id
            ]
        );
        $this->create_text_custom_field(
            $hierarchyshortname,
            [
                'fullname' => 'field A',
                'shortname' => 'customfieldA',
                'typeid' => $type2id
            ]
        );
        $this->create_text_custom_field(
            $hierarchyshortname,
            [
                'fullname' => 'field B',
                'shortname' => 'customfieldB',
                'typeid' => $type2id
            ]
        );
    }

    /**
     * Ideally, a generator method is used here. But the existing method in the totara custom field generator
     * isn't suitable for our use, it takes an array of names while we need to set
     * type id and duplicate shortnames.
     *
     * Modifying the API of the generator came with challenges that are out of scope for writing this test.
     */
    private function create_text_custom_field($hierarchyshortname, $settings) {
        global $CFG;
        require_once($CFG->dirroot . '/totara/customfield/field/text/define.class.php');

        $data = new stdClass();
        $data->id = 0;
        $data->datatype = 'text';
        $data->fullname = $settings['fullname'];
        $data->shortname = $settings['shortname'];
        $data->description = '';
        $data->defaultdata = '';
        $data->forceunique = 0;
        $data->hidden = 0;
        $data->locked = 0;
        $data->required = 0;
        $data->description_editor = array('text' => '', 'format' => 0);
        $data->typeid = $settings['typeid'];

        $formfield = new customfield_define_text();
        $formfield->define_save($data, $hierarchyshortname . '_type');

        return $data;
    }

    /**
     * Helper method for getting an easily checked (and asserted against) array
     * of the custom field data for a given instance of hierarchy item (e.g. a position or organisation).
     *
     * @param string $hierarchyfullname
     * @param stdClass $item database record for a given hierarchy item (e.g. a pos or org record).
     * @return array of the structure ['shortname' => 'custom field valye']
     */
    private function get_custom_fields_array($hierarchyfullname, $item) {
        $hierarchy = hierarchy::load_hierarchy($hierarchyfullname);
        $savedcustomfields = $hierarchy->get_custom_fields($item->id);

        // The below will create an array with the structure ['shortname' => 'data'].
        return array_combine(
            array_column($savedcustomfields, 'shortname'),
            array_column($savedcustomfields, 'data')
        );
    }

    /**
     * Check whether the sync logs contain a given string in the info field.
     *
     * @param string $info
     * @return bool True if the log does contain the given string
     */
    private function sync_log_contains($info) {
        global $DB;

        $logrecords = $DB->get_records('totara_sync_log');
        foreach($logrecords as $logrecord) {
            if ($logrecord->info === $info) {
                return true;
            }
        }

        return false;
    }

    /**
     * @dataProvider get_hierarchy_names
     */
    public function test_import_single_customfield($hierarchyshortname, $hierarchyfullname) {
        global $DB;
        $this->resetAfterTest(true);
        $this->set_up_importing($hierarchyshortname);
        $this->create_data_for_multiple_customfields($hierarchyshortname, $hierarchyfullname);

        $sync_customfields = customfield::get_all(new $hierarchyfullname());
        foreach($sync_customfields as $sync_customfield) {
            // We're only testing with one custom field set to import. Other tests cover multiple.
            if ($sync_customfield->get_shortname_with_type() === 'customfieldA (typeidnumber1)') {
                set_config($sync_customfield->get_import_setting_name(), 1, 'totara_sync_source_' . $hierarchyshortname . '_csv');
            }
        }

        $elementclass  = 'totara_sync_element_' . $hierarchyshortname;
        $element = new $elementclass();

        $csv = "idnumber,fullname,frameworkidnumber,timemodified,typeidnumber,customfield_customfieldA\n";
        $csv .= "id1,Item one,fw1,0,typeidnumber1,Item1 customfieldA value";

        $element->get_source()->set_csv_in_memory($csv);

        $this->assertTrue($element->sync());

        $item1 = $DB->get_record($hierarchyshortname, ['idnumber' => 'id1']);
        $this->assertEquals('Item one', $item1->fullname);
        $values = $this->get_custom_fields_array($hierarchyfullname, $item1);
        $this->assertCount(1, $values);
        $this->assertEquals('Item1 customfieldA value', $values['customfieldA']);
    }

    /**
     * To set a custom field, a type id must be given so that we know which type the custom field
     * relates to.
     *
     * @dataProvider get_hierarchy_names
     */
    public function test_import_single_customfield_with_no_typeid($hierarchyshortname, $hierarchyfullname) {
        global $DB;
        $this->resetAfterTest(true);
        $this->set_up_importing($hierarchyshortname);
        $this->create_data_for_multiple_customfields($hierarchyshortname, $hierarchyfullname);

        $sync_customfields = customfield::get_all(new $hierarchyfullname());
        foreach($sync_customfields as $sync_customfield) {
            // We're only testing with one custom field set to import. Other tests cover multiple.
            if ($sync_customfield->get_shortname_with_type() === 'customfieldA (typeidnumber1)') {
                set_config($sync_customfield->get_import_setting_name(), 1, 'totara_sync_source_' . $hierarchyshortname . '_csv');
            }
        }

        $elementclass  = 'totara_sync_element_' . $hierarchyshortname;
        $element = new $elementclass();

        $csv = "idnumber,fullname,frameworkidnumber,timemodified,typeidnumber,customfield_customfieldA\n";
        $csv .= "id1,item one,fw1,0,,Item1 customfieldA value";

        $element->get_source()->set_csv_in_memory($csv);

        $this->assertTrue($element->sync());

        $item1 = $DB->get_record($hierarchyshortname, ['idnumber' => 'id1']);
        $this->assertEquals('item one', $item1->fullname);
        $values = $this->get_custom_fields_array($hierarchyfullname, $item1);
        $this->assertCount(0, $values);
        $this->assertTrue($this->sync_log_contains('custom fields specified, but no type id1'));
    }

    /**
     * When setting a custom field, the given type must contain the custom field that we are providing data for.
     *
     * @dataProvider get_hierarchy_names
     */
    public function test_import_single_customfield_with_incorrect_typeid($hierarchyshortname, $hierarchyfullname) {
        global $DB;
        $this->resetAfterTest(true);
        $this->set_up_importing($hierarchyshortname);
        $this->create_data_for_multiple_customfields($hierarchyshortname, $hierarchyfullname);

        $sync_customfields = customfield::get_all(new $hierarchyfullname());
        foreach($sync_customfields as $sync_customfield) {
            // We're only testing with one custom field set to import. Other tests cover multiple.
            if ($sync_customfield->get_shortname_with_type() === 'customfieldA (typeidnumber1)') {
                set_config($sync_customfield->get_import_setting_name(), 1, 'totara_sync_source_' . $hierarchyshortname . '_csv');
            }
        }

        $elementclass  = 'totara_sync_element_' . $hierarchyshortname;
        $element = new $elementclass();

        $csv = "idnumber,fullname,frameworkidnumber,timemodified,typeidnumber,customfield_customfieldA,customfield_customfieldB\n";
        $csv .= "id1,item one,fw1,0,typeidnumber1,,Value in field B";

        $element->get_source()->set_csv_in_memory($csv);

        $this->assertTrue($element->sync());

        $item1 = $DB->get_record($hierarchyshortname, ['idnumber' => 'id1']);
        $this->assertEquals('item one', $item1->fullname);
        $values = $this->get_custom_fields_array($hierarchyfullname, $item1);
        $this->assertCount(0, $values);

        $this->assertTrue($this->sync_log_contains('While processing item id1: the custom field column, customfield_customfieldB, is not valid for type: typeidnumber1'));
    }

    /**
     * There can be custom fields with duplicate shortnames. This can happen if the custom fields are in different
     * types. Importing must allow for this.
     *
     * @dataProvider get_hierarchy_names
     */
    public function test_import_customfields_including_duplicate_shortnames($hierarchyshortname, $hierarchyfullname) {
        global $DB;
        $this->resetAfterTest(true);
        $this->set_up_importing($hierarchyshortname);
        $this->create_data_for_multiple_customfields($hierarchyshortname, $hierarchyfullname);

        $sync_customfields = customfield::get_all(new $hierarchyfullname());
        foreach($sync_customfields as $sync_customfield) {
            set_config($sync_customfield->get_import_setting_name(), 1, 'totara_sync_source_' . $hierarchyshortname . '_csv');
        }

        $elementclass  = 'totara_sync_element_' . $hierarchyshortname;
        $element = new $elementclass();

        $csv = "idnumber,fullname,frameworkidnumber,timemodified,typeidnumber,customfield_customfieldA,customfield_customfieldB\n";
        $csv .= "id1,item one,fw1,0,typeidnumber1,id1 customfieldA value,\n";
        $csv .= "id2,item two,fw1,0,typeidnumber2,id2 customfieldA value,\n";
        $csv .= "id3,item three,fw1,0,typeidnumber2,,id3 customfieldB value\n";
        $element->get_source()->set_csv_in_memory($csv);

        $this->assertTrue($element->sync());

        $item1 = $DB->get_record($hierarchyshortname, ['idnumber' => 'id1']);
        $this->assertEquals('item one', $item1->fullname);
        $values = $this->get_custom_fields_array($hierarchyfullname, $item1);
        $this->assertCount(1, $values);
        $this->assertEquals('id1 customfieldA value', $values['customfieldA']);

        $item2 = $DB->get_record($hierarchyshortname, ['idnumber' => 'id2']);
        $this->assertEquals('item two', $item2->fullname);
        $values = $this->get_custom_fields_array($hierarchyfullname, $item2);
        $this->assertCount(1, $values);
        $this->assertEquals('id2 customfieldA value', $values['customfieldA']);

        $item3 = $DB->get_record($hierarchyshortname, ['idnumber' => 'id3']);
        $this->assertEquals('item three', $item3->fullname);
        $values = $this->get_custom_fields_array($hierarchyfullname, $item3);
        $this->assertCount(1, $values);
        $this->assertEquals('id3 customfieldB value', $values['customfieldB']);
    }

    /**
     * @dataProvider get_hierarchy_names
     */
    public function test_import_customfields_with_fieldmapping($hierarchyshortname, $hierarchyfullname) {
        global $DB;
        $this->resetAfterTest(true);
        $this->set_up_importing($hierarchyshortname);
        $this->create_data_for_multiple_customfields($hierarchyshortname, $hierarchyfullname);

        $sync_customfields = customfield::get_all(new $hierarchyfullname());
        foreach($sync_customfields as $sync_customfield) {
            set_config($sync_customfield->get_import_setting_name(), 1, 'totara_sync_source_' . $hierarchyshortname . '_csv');
            if ($sync_customfield->get_shortname_with_type() === 'customfieldA (typeidnumber2)') {
                // We will set a custom mapping for custom field A in type 2 only.
                set_config($sync_customfield->get_fieldmapping_setting_name(), 'type2fieldA', 'totara_sync_source_' . $hierarchyshortname . '_csv');
            }
        }

        $elementclass  = 'totara_sync_element_' . $hierarchyshortname;
        $element = new $elementclass();

        $csv = "idnumber,fullname,frameworkidnumber,timemodified,typeidnumber,customfield_customfieldA,type2fieldA,customfield_customfieldB\n";
        $csv .= "id1,item one,fw1,0,typeidnumber1,id1 customfieldA value,,\n";
        $csv .= "id2,item two,fw1,0,typeidnumber2,,id2 customfieldA value,\n";
        $csv .= "id3,item three,fw1,0,typeidnumber2,,,id3 customfieldB value\n";
        $element->get_source()->set_csv_in_memory($csv);

        $this->assertTrue($element->sync());

        $item1 = $DB->get_record($hierarchyshortname, ['idnumber' => 'id1']);
        $this->assertEquals('item one', $item1->fullname);
        $values = $this->get_custom_fields_array($hierarchyfullname, $item1);
        $this->assertCount(1, $values);
        $this->assertEquals('id1 customfieldA value', $values['customfieldA']);

        $item2 = $DB->get_record($hierarchyshortname, ['idnumber' => 'id2']);
        $this->assertEquals('item two', $item2->fullname);
        $values = $this->get_custom_fields_array($hierarchyfullname, $item2);
        $this->assertCount(1, $values);
        $this->assertEquals('id2 customfieldA value', $values['customfieldA']);

        $item3 = $DB->get_record($hierarchyshortname, ['idnumber' => 'id3']);
        $this->assertEquals('item three', $item3->fullname);
        $values = $this->get_custom_fields_array($hierarchyfullname, $item3);
        $this->assertCount(1, $values);
        $this->assertEquals('id3 customfieldB value', $values['customfieldB']);
    }

    /**
     * If we have custom fields with duplicate shortnames, but one has been mapped to a different column
     * name, then the other column can only relate to the custom field without any special mapping.
     *
     * @dataProvider get_hierarchy_names
     */
    public function test_import_customfields_with_fieldmapping_value_in_incorrect_column($hierarchyshortname, $hierarchyfullname) {
        global $DB;
        $this->resetAfterTest(true);
        $this->set_up_importing($hierarchyshortname);
        $this->create_data_for_multiple_customfields($hierarchyshortname, $hierarchyfullname);

        $sync_customfields = customfield::get_all(new $hierarchyfullname());
        foreach($sync_customfields as $sync_customfield) {
            set_config($sync_customfield->get_import_setting_name(), 1, 'totara_sync_source_' . $hierarchyshortname . '_csv');
            if ($sync_customfield->get_shortname_with_type() === 'customfieldA (typeidnumber2)') {
                // We will set a custom mapping for custom field A in type 2 only.
                set_config($sync_customfield->get_fieldmapping_setting_name(), 'type2fieldA', 'totara_sync_source_' . $hierarchyshortname . '_csv');
            }
        }

        $elementclass  = 'totara_sync_element_' . $hierarchyshortname;
        $element = new $elementclass();

        $csv = "idnumber,fullname,frameworkidnumber,timemodified,typeidnumber,customfield_customfieldA,type2fieldA,customfield_customfieldB\n";
        $csv .= "id1,item one,fw1,0,typeidnumber1,id1 customfieldA value,,\n";
        // For type 2, the value should be in the column type2fieldA, but we're putting it in customfieldA instead.
        // However, that will only map to customfield A for type 1.
        $csv .= "id2,item two,fw1,0,typeidnumber2,id2 customfieldA value,,\n";
        $csv .= "id3,item three,fw1,0,typeidnumber2,,,id3 customfieldB value\n";
        $element->get_source()->set_csv_in_memory($csv);

        $this->assertTrue($element->sync());

        $item1 = $DB->get_record($hierarchyshortname, ['idnumber' => 'id1']);
        $this->assertEquals('item one', $item1->fullname);
        $values = $this->get_custom_fields_array($hierarchyfullname, $item1);
        $this->assertCount(1, $values);
        $this->assertEquals('id1 customfieldA value', $values['customfieldA']);

        $item2 = $DB->get_record($hierarchyshortname, ['idnumber' => 'id2']);
        $this->assertEquals('item two', $item2->fullname);
        $values = $this->get_custom_fields_array($hierarchyfullname, $item2);
        // The value did not get imported.
        $this->assertCount(0, $values);
        $this->assertTrue($this->sync_log_contains('While processing item id2: the custom field column, customfield_customfieldA, is not valid for type: typeidnumber2'));

        $item3 = $DB->get_record($hierarchyshortname, ['idnumber' => 'id3']);
        $this->assertEquals('item three', $item3->fullname);
        $values = $this->get_custom_fields_array($hierarchyfullname, $item3);
        $this->assertCount(1, $values);
        $this->assertEquals('id3 customfieldB value', $values['customfieldB']);
    }
}