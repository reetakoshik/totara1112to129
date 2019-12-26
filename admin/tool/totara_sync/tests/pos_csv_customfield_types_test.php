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
require_once($CFG->dirroot . '/admin/tool/totara_sync/elements/pos.php');

/**
 * Class tool_totara_sync_pos_csv_customfield_types_testcase
 *
 * Intended to test that all the different custom fields have their data imported correctly.
 *
 * Positions imported via CSV is chosen here. This could be enough testing of importing the different types
 * that we don't need to replicate this test across other import elements and sources.
 */
class tool_totara_sync_pos_csv_customfield_types_testcase extends advanced_testcase {

    private function set_up_importing() {

        set_config('element_pos_enabled', 1, 'totara_sync');
        set_config('source_pos', 'totara_sync_source_pos_csv', 'totara_sync');
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
            set_config($setting, $value, 'totara_sync_source_pos_csv');
        }

        $elementconfig = [
            'allow_create' => '1',
            'allow_delete' => '1',
            'allow_update' => '1',
            'sourceallrecords' => 1,
        ];
        foreach($elementconfig as $setting => $value) {
            set_config($setting, $value, 'totara_sync_element_pos');
        }
    }

    private function set_up_framework() {
        /* @var totara_hierarchy_generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $hierarchy_generator->create_framework(
            'position',
            ['idnumber' => 'fw1']
        );
    }

    private function set_up_type() {
        /* @var totara_hierarchy_generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        return $hierarchy_generator->create_hierarchy_type(
            'position',
            ['idnumber' => 'typeidnumber1', 'fullname' => 'Type 1 full name']
        );
    }

    /**
     * To import a checkbox value, use 1 for checked and 0 for not checked.
     */
    public function test_checkbox_import() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/totara/customfield/field/checkbox/define.class.php');
        $this->resetAfterTest();
        $this->set_up_importing();
        $this->set_up_framework();

        $data = new stdClass();
        $data->id = 0;
        $data->datatype = 'checkbox';
        $data->fullname = 'Checkbox one';
        $data->shortname = 'checkbox1';
        $data->description = '';
        $data->defaultdata = '';
        $data->forceunique = 0;
        $data->hidden = 0;
        $data->locked = 0;
        $data->required = 0;
        $data->description_editor = array('text' => '', 'format' => 0);
        $data->typeid = $this->set_up_type();

        $formfield = new customfield_define_checkbox();
        $formfield->define_save($data, 'pos_type');

        $sync_customfields = \tool_totara_sync\internal\hierarchy\customfield::get_all(new position());
        foreach($sync_customfields as $sync_customfield) {
            set_config($sync_customfield->get_import_setting_name(), 1, 'totara_sync_source_pos_csv');
        }

        $element = new totara_sync_element_pos();

        $csv = "idnumber,fullname,frameworkidnumber,timemodified,typeidnumber,customfield_checkbox1\n";
        $csv .= "id1,Item one,fw1,0,typeidnumber1,1";
        $element->get_source()->set_csv_in_memory($csv);

        $this->assertTrue($element->sync());

        $item1 = $DB->get_record('pos', ['idnumber' => 'id1']);
        $this->assertEquals('Item one', $item1->fullname);
        $hierarchy = hierarchy::load_hierarchy('position');
        $savedcustomfields = $hierarchy->get_custom_fields($item1->id);
        $this->assertEquals('1', reset($savedcustomfields)->data);
    }

    /**
     * To import a datetime, use either a timestamp or date that aligns with csvdateformat config setting.
     *
     * The formatted time will need to be within quotes.
     */
    public function test_datetime_import() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/totara/customfield/field/datetime/define.class.php');
        $this->resetAfterTest();
        $this->set_up_importing();
        $this->set_up_framework();

        $data = new stdClass();
        $data->id = 0;
        $data->datatype = 'datetime';
        $data->fullname = 'Date/Time one';
        $data->shortname = 'datetime1';
        $data->description = '';
        $data->defaultdata = '';
        $data->forceunique = 0;
        $data->hidden = 0;
        $data->locked = 0;
        $data->required = 0;
        $data->description_editor = array('text' => '', 'format' => 0);
        $data->typeid = $this->set_up_type();

        $formfield = new customfield_define_datetime();
        $formfield->define_save($data, 'pos_type');

        $sync_customfields = \tool_totara_sync\internal\hierarchy\customfield::get_all(new position());
        foreach($sync_customfields as $sync_customfield) {
            set_config($sync_customfield->get_import_setting_name(), 1, 'totara_sync_source_pos_csv');
        }

        $element = new totara_sync_element_pos();

        $time = time();

        set_config('csvdateformt', 'Y-m-d H:i:s');
        $date = new DateTime('now', core_date::get_server_timezone_object());
        $formatteddate = $date->format('Y-m-d H:i:s');

        $csv = "idnumber,fullname,frameworkidnumber,timemodified,typeidnumber,customfield_datetime1\n";
        $csv .= "id1,Item one,fw1,0,typeidnumber1,{$time}\n";
        $csv .= "id2,Item two,fw1,0,typeidnumber1,\"{$formatteddate}\"";
        $element->get_source()->set_csv_in_memory($csv);

        $this->assertTrue($element->sync());

        $hierarchy = hierarchy::load_hierarchy('position');

        $item1 = $DB->get_record('pos', ['idnumber' => 'id1']);
        $this->assertEquals('Item one', $item1->fullname);
        $savedcustomfields = $hierarchy->get_custom_fields($item1->id);
        $this->assertEquals($time, reset($savedcustomfields)->data);

        $item2 = $DB->get_record('pos', ['idnumber' => 'id2']);
        $this->assertEquals('Item two', $item2->fullname);
        $savedcustomfields = $hierarchy->get_custom_fields($item2->id);
        $this->assertEquals($date->getTimestamp(), reset($savedcustomfields)->data);
    }

    /**
     * We do not allow importing of file custom fields.
     *
     * It should not be possible to enable importing of this field in the first place, therefore
     * corresponding columns for it will be ignored.
     */
    public function test_file_import() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/totara/customfield/field/file/define.class.php');
        $this->resetAfterTest();
        $this->set_up_importing();
        $this->set_up_framework();

        $data = new stdClass();
        $data->id = 0;
        $data->datatype = 'file';
        $data->fullname = 'File one';
        $data->shortname = 'file1';
        $data->description = '';
        $data->defaultdata = '';
        $data->forceunique = 0;
        $data->hidden = 0;
        $data->locked = 0;
        $data->required = 0;
        $data->description_editor = array('text' => '', 'format' => 0);
        $data->typeid = $this->set_up_type();

        $formfield = new customfield_define_file();
        $formfield->define_save($data, 'pos_type');

        $sync_customfields = \tool_totara_sync\internal\hierarchy\customfield::get_all(new position());
        // We don't get the option to enable the file custom field.
        $this->assertCount(0, $sync_customfields);
        // Still, we'll push on anyway...

        $element = new totara_sync_element_pos();

        $csv = "idnumber,fullname,frameworkidnumber,timemodified,typeidnumber,customfield_file1\n";
        $csv .= "id1,Item one,fw1,0,typeidnumber1,\"SomeFile.txt\"";
        $element->get_source()->set_csv_in_memory($csv);

        $this->assertTrue($element->sync());

        $item1 = $DB->get_record('pos', ['idnumber' => 'id1']);
        $this->assertEquals('Item one', $item1->fullname);
        $hierarchy = hierarchy::load_hierarchy('position');
        $savedcustomfields = $hierarchy->get_custom_fields($item1->id);
        $this->assertCount(0, $savedcustomfields);
    }

    /**
     * To import a location, the field must give the address, within quotes.
     *
     * Quotation marks within the value need to be escaped by doubling up.
     */
    public function test_location_import() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/totara/customfield/field/location/define.class.php');
        $this->resetAfterTest();
        $this->set_up_importing();
        $this->set_up_framework();

        $data = new stdClass();
        $data->id = 0;
        $data->datatype = 'location';
        $data->fullname = 'Location one';
        $data->shortname = 'location1';
        $data->description = '';
        $data->defaultdata = '';
        $data->forceunique = 0;
        $data->hidden = 0;
        $data->locked = 0;
        $data->required = 0;
        $data->description_editor = array('text' => '', 'format' => 0);
        $data->typeid = $this->set_up_type();

        $formfield = new customfield_define_location();
        $formfield->define_save($data, 'pos_type');

        $sync_customfields = \tool_totara_sync\internal\hierarchy\customfield::get_all(new position());
        foreach($sync_customfields as $sync_customfield) {
            set_config($sync_customfield->get_import_setting_name(), 1, 'totara_sync_source_pos_csv');
        }

        $element = new totara_sync_element_pos();

        $csv = "idnumber,fullname,frameworkidnumber,timemodified,typeidnumber,customfield_location1\n";
        $csv .= "id1,Item one,fw1,0,typeidnumber1,\"150 Willis St,\nWellington\"";

        $element->get_source()->set_csv_in_memory($csv);

        $this->assertTrue($element->sync());

        $item1 = $DB->get_record('pos', ['idnumber' => 'id1']);
        $this->assertEquals('Item one', $item1->fullname);
        $hierarchy = hierarchy::load_hierarchy('position');
        $savedcustomfields = $hierarchy->get_custom_fields($item1->id);
        $locationdata = json_decode(reset($savedcustomfields)->data);
        $this->assertEquals("150 Willis St,\nWellington", $locationdata->address);
    }

    /**
     * To import a menu value, use the value as displayed to the user for the chosen option, within quotes.
     *
     * Quotation marks within the value need to be escaped by doubling up.
     */
    public function test_menu_import() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/totara/customfield/field/menu/define.class.php');
        $this->resetAfterTest();
        $this->set_up_importing();
        $this->set_up_framework();

        $data = new stdClass();
        $data->id = 0;
        $data->datatype = 'menu';
        $data->fullname = 'Menu one';
        $data->shortname = 'menu1';
        $data->description = '';
        $data->defaultdata = '';
        $data->forceunique = 0;
        $data->hidden = 0;
        $data->locked = 0;
        $data->required = 0;
        $data->description_editor = array('text' => '', 'format' => 0);
        $data->typeid = $this->set_up_type();

        $data->param1 = "Option\"A\"\nOption\"B\"\nOption\"C\"";

        $formfield = new customfield_define_menu();
        $formfield->define_save($data, 'pos_type');

        $sync_customfields = \tool_totara_sync\internal\hierarchy\customfield::get_all(new position());
        foreach($sync_customfields as $sync_customfield) {
            set_config($sync_customfield->get_import_setting_name(), 1, 'totara_sync_source_pos_csv');
        }

        $element = new totara_sync_element_pos();

        $csv = "idnumber,fullname,frameworkidnumber,timemodified,typeidnumber,customfield_menu1\n";
        $csv .= "id1,Item one,fw1,0,typeidnumber1,\"Option\"\"B\"\"\"";
        $element->get_source()->set_csv_in_memory($csv);

        $this->assertTrue($element->sync());

        $item1 = $DB->get_record('pos', ['idnumber' => 'id1']);
        $this->assertEquals('Item one', $item1->fullname);
        $hierarchy = hierarchy::load_hierarchy('position');
        $savedcustomfields = $hierarchy->get_custom_fields($item1->id);
        $this->assertEquals('Option"B"', reset($savedcustomfields)->data);
    }

    /**
     * To import a multi-select item, within quotes, use a comma-separated list of the values
     * for the items you have chosen. These values will be as displayed to a user
     * (i.e. some text, not a key or id of any sort).
     *
     * Quotation marks within the value need to be escaped by doubling up.
     */
    public function test_multiselect_import() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/totara/customfield/field/multiselect/define.class.php');
        $this->resetAfterTest();
        $this->set_up_importing();
        $this->set_up_framework();

        $data = new stdClass();
        $data->id = 0;
        $data->datatype = 'multiselect';
        $data->fullname = 'Multi-select one';
        $data->shortname = 'multiselect1';
        $data->description = '';
        $data->defaultdata = '';
        $data->forceunique = 0;
        $data->hidden = 0;
        $data->locked = 0;
        $data->required = 0;
        $data->description_editor = array('text' => '', 'format' => 0);
        $data->typeid = $this->set_up_type();

        $data->multiselectitem = [
            0 => ['delete' => 0, 'option' => 'Option"A"', 'icon' => ''],
            1 => ['delete' => 0, 'option' => 'Option"B"', 'icon' => ''],
            2 => ['delete' => 0, 'option' => 'Option"C"', 'icon' => '']
        ];

        $formfield = new customfield_define_multiselect();
        $formfield->define_save($data, 'pos_type');

        $sync_customfields = \tool_totara_sync\internal\hierarchy\customfield::get_all(new position());
        foreach($sync_customfields as $sync_customfield) {
            set_config($sync_customfield->get_import_setting_name(), 1, 'totara_sync_source_pos_csv');
        }

        $element = new totara_sync_element_pos();

        $csv = "idnumber,fullname,frameworkidnumber,timemodified,typeidnumber,customfield_multiselect1\n";
        $csv .= "id1,Item one,fw1,0,typeidnumber1,\"Option\"\"B\"\",Option\"\"C\"\"\"";
        $element->get_source()->set_csv_in_memory($csv);

        $this->assertTrue($element->sync());

        $item1 = $DB->get_record('pos', ['idnumber' => 'id1']);
        $this->assertEquals('Item one', $item1->fullname);
        $hierarchy = hierarchy::load_hierarchy('position');
        $savedcustomfields = $hierarchy->get_custom_fields($item1->id);
        $this->assertEquals('Option"B", Option"C"', customfield_multiselect::display_item_data(reset($savedcustomfields)->data, ['display' => 'list-text']));
    }

    /**
     * To import a text value, simply provide the text, enclosed within quotes.
     *
     * Quotation marks within the value need to be escaped by doubling up.
     */
    public function test_text_import() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/totara/customfield/field/text/define.class.php');
        $this->resetAfterTest();
        $this->set_up_importing();
        $this->set_up_framework();

        $data = new stdClass();
        $data->id = 0;
        $data->datatype = 'text';
        $data->fullname = 'Text one';
        $data->shortname = 'text1';
        $data->description = '';
        $data->defaultdata = '';
        $data->forceunique = 0;
        $data->hidden = 0;
        $data->locked = 0;
        $data->required = 0;
        $data->description_editor = array('text' => '', 'format' => 0);
        $data->typeid = $this->set_up_type();

        $formfield = new customfield_define_text();
        $formfield->define_save($data, 'pos_type');

        $sync_customfields = \tool_totara_sync\internal\hierarchy\customfield::get_all(new position());
        foreach($sync_customfields as $sync_customfield) {
            set_config($sync_customfield->get_import_setting_name(), 1, 'totara_sync_source_pos_csv');
        }

        $element = new totara_sync_element_pos();

        $csv = "idnumber,fullname,frameworkidnumber,timemodified,typeidnumber,customfield_text1\n";
        $csv .= "id1,Item one,fw1,0,typeidnumber1,\"Text \"\"one\"\" value\"";
        $element->get_source()->set_csv_in_memory($csv);

        $this->assertTrue($element->sync());

        $item1 = $DB->get_record('pos', ['idnumber' => 'id1']);
        $this->assertEquals('Item one', $item1->fullname);
        $hierarchy = hierarchy::load_hierarchy('position');
        $savedcustomfields = $hierarchy->get_custom_fields($item1->id);
        $this->assertEquals('Text "one" value', reset($savedcustomfields)->data);
    }

    /**
     * To import a text area, provide the value, enclosed within quotes.
     *
     * Quotation marks within the value need to be escaped by doubling up.
     */
    public function test_textarea_import() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/totara/customfield/field/textarea/define.class.php');
        $this->resetAfterTest();
        $this->set_up_importing();
        $this->set_up_framework();

        $data = new stdClass();
        $data->id = 0;
        $data->datatype = 'textarea';
        $data->fullname = 'Text Area one';
        $data->shortname = 'textarea1';
        $data->description = '';
        $data->defaultdata = '';
        $data->forceunique = 0;
        $data->hidden = 0;
        $data->locked = 0;
        $data->required = 0;
        $data->description_editor = array('text' => '', 'format' => 0);
        $data->typeid = $this->set_up_type();

        $data->defaultdata_editor = array('text' => '', 'format' => 0);

        $formfield = new customfield_define_textarea();
        $formfield->define_save($data, 'pos_type');

        $sync_customfields = \tool_totara_sync\internal\hierarchy\customfield::get_all(new position());
        foreach($sync_customfields as $sync_customfield) {
            set_config($sync_customfield->get_import_setting_name(), 1, 'totara_sync_source_pos_csv');
        }

        $element = new totara_sync_element_pos();

        $textareacontent = 'A picture says <strong>1000</strong> words.<br />See this one for example:<img src="@@PLUGINFILE@@" alt="@@PLUGINFILE@@" />';
        $textareacontent_doublequoted = str_replace('"', '""', $textareacontent);

        $csv = "idnumber,fullname,frameworkidnumber,timemodified,typeidnumber,customfield_textarea1\n";
        $csv .= "id1,Item one,fw1,0,typeidnumber1,\"{$textareacontent_doublequoted}\"";
        $element->get_source()->set_csv_in_memory($csv);

        // We need to set a user as it does things with draft areas for the current user while saving text area data.
        $this->setAdminUser();

        $this->assertTrue($element->sync());

        $item1 = $DB->get_record('pos', ['idnumber' => 'id1']);
        $this->assertEquals('Item one', $item1->fullname);
        $hierarchy = hierarchy::load_hierarchy('position');
        $savedcustomfields = $hierarchy->get_custom_fields($item1->id);
        $this->assertEquals($textareacontent, reset($savedcustomfields)->data);
    }
}