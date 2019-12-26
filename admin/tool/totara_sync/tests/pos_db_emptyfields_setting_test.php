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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package tool_totara_sync
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/totara_sync/tests/source_database_testcase.php');
require_once($CFG->dirroot . '/admin/tool/totara_sync/sources/source_pos_database.php');

/**
 * @group tool_totara_sync
 */
class tool_totara_sync_pos_database_testcase extends totara_sync_database_testcase {

    public function setUp() {
        $this->elementname = 'pos';
        $this->sourcetable = 'totara_sync_pos_source';

        parent::setUp();

        set_config('source_pos', 'totara_sync_source_pos_database', 'totara_sync');

        $this->resetAfterTest(true);
        $this->preventResetByRollback();
        $this->setAdminUser();

        $this->create_external_db_table();
    }

    public function tearDown() {
        $this->elementname = null;
        $this->sourcetable = null;

        parent::tearDown();
    }

    public function create_external_db_table() {

        $dbman = $this->ext_dbconnection->get_manager();
        $table = new xmldb_table($this->dbtable);

        // Drop table first, if it exists
        if ($dbman->table_exists($this->dbtable)) {
            $dbman->drop_table($table);
        }

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('idnumber', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('fullname', XMLDB_TYPE_CHAR, '255');
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '255');
        $table->add_field('frameworkidnumber', XMLDB_TYPE_CHAR, 255);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('deleted', XMLDB_TYPE_INTEGER, '1');
        $table->add_field('description', XMLDB_TYPE_TEXT);
        $table->add_field('parentidnumber', XMLDB_TYPE_CHAR, '255');
        $table->add_field('typeidnumber', XMLDB_TYPE_CHAR, '255');
        $table->add_field('textcf1', XMLDB_TYPE_CHAR, '255');
        $table->add_field('menucf1', XMLDB_TYPE_CHAR, '255');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        $dbman->create_table($table);
    }

    public function test_null_in_required_fields() {
        global $DB;

        // Remove this once TL-16743 is merged.
        // Currently the entire sync is stopped if there is any problem in the sanity check
        // so we can't check to see if only the row with the missing data isn't synced.
        $this->markTestSkipped();

        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $posframework = $hierarchy_generator->create_framework('position');

        $pos1 = $hierarchy_generator->create_pos(
            [
                'frameworkid' => $posframework->id, 'fullname' => 'Position 1', 'idnumber' => 'pos1',
                'totarasync' => 1
            ]
        );

        $pos2 = $hierarchy_generator->create_pos(
            [
                'frameworkid' => $posframework->id, 'fullname' => 'Position 2', 'idnumber' => 'pos2',
                'parentid' => $pos1->id, 'totarasync' => 1
            ]
        );

        $entry = new stdClass();
        $entry->idnumber = 'pos2';
        $entry->fullname = 'Position 2';
        $entry->deleted = 0;
        $entry->timemodified = 0;
        $entry->frameworkidnumber = null;

        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $source = new totara_sync_source_pos_database();
        $source->set_config('import_idnumber', '1');
        $source->set_config('import_fullname', '1');
        $source->set_config('import_deleted', '1');
        $source->set_config('import_frameworkidnumber', '1');
        $source->set_config('import_timemodified', '1');

        $element = new totara_sync_element_pos();
        $element->set_config('allow_update', '1');
        $this->assertTrue($element->sync());

        $pos2_actual = $DB->get_record('pos', array('id' => $pos2->id));
        $this->assertTrue('pos1', $pos2_actual->idnumber);
        $this->assertTrue('Position 1', $pos2_actual->fullname);
        $this->assertTrue($pos1->id, $pos2_actual->parentid);

    }

    public function test_null_in_other_fields() {
        global $DB;

        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $posframework = $hierarchy_generator->create_framework('position');

        $pos1 = $hierarchy_generator->create_pos(
            [
                'frameworkid' => $posframework->id, 'fullname' => 'Position 1', 'idnumber' => 'pos1',
                'totarasync' => 1
            ]
        );

        $pos2 = $hierarchy_generator->create_pos(
            [
                'frameworkid' => $posframework->id, 'fullname' => 'Position 2', 'idnumber' => 'pos2',
                'parentid' => $pos1->id, 'totarasynce' => 1
            ]
        );

        $entry = new stdClass();
        $entry->idnumber = 'pos2';
        $entry->fullname = 'Position 2';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->frameworkidnumber = $posframework->idnumber;
        $entry->description = null;
        $entry->parentidnumber = null;

        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $source = new totara_sync_source_pos_database();
        $source->set_config('import_idnumber', '1');
        $source->set_config('import_fullname', '1');
        $source->set_config('import_frameworkidnumber', '1');
        $source->set_config('import_timemodified', '1');
        $source->set_config('import_deleted', '1');
        $source->set_config('import_description', '1');
        $source->set_config('import_parentidnumber', '1');

        $element = new totara_sync_element_pos();
        $element->set_config('allow_update', '1');
        $this->assertTrue($element->sync());

        $pos2_actual = $DB->get_record('pos', array('id' => $pos2->id));
        $this->assertEquals('pos2', $pos2_actual->idnumber);
        $this->assertEquals('Position 2', $pos2_actual->fullname);
        $this->assertEquals($pos1->id, $pos2_actual->parentid);

    }

    public function test_empty_strings_in_other_fields() {
        global $DB;

        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $posframework = $hierarchy_generator->create_framework('position');

        $pos1 = $hierarchy_generator->create_pos(
            [
                'frameworkid' => $posframework->id, 'fullname' => 'Position 1', 'idnumber' => 'pos1',
                'totarasync' => 1
            ]
        );

        $pos2 = $hierarchy_generator->create_pos(
            [
                'frameworkid' => $posframework->id, 'fullname' => 'Position 2', 'idnumber' => 'pos2',
                'parentid' => $pos1->id, 'totarasync' => 1
            ]
        );

        $entry = new stdClass();
        $entry->idnumber = 'pos2';
        $entry->fullname = 'Position 2';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->frameworkidnumber = $posframework->idnumber;
        $entry->description = '';
        $entry->parentidnumber = '';

        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $source = new totara_sync_source_pos_database();
        $source->set_config('import_idnumber', '1');
        $source->set_config('import_fullname', '1');
        $source->set_config('import_frameworkidnumber', '1');
        $source->set_config('import_timemodified', '1');
        $source->set_config('import_deleted', '1');
        $source->set_config('import_description', '1');
        $source->set_config('import_parentidnumber', '1');

        $element = new totara_sync_element_pos();
        $element->set_config('allow_update', '1');
        $this->assertTrue($element->sync());

        $pos2_actual = $DB->get_record('pos', array('id' => $pos2->id));
        $this->assertEquals('pos2', $pos2_actual->idnumber);
        $this->assertEquals('Position 2', $pos2_actual->fullname);
        $this->assertEquals('0', $pos2_actual->parentid);
    }

    public function test_null_typeidnumber() {
        global $DB;

        $this->resetAfterTest(true);

        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $posframework = $hierarchy_generator->create_framework('position');

        // Create a type
        $postypeid = $hierarchy_generator->create_pos_type();
        $postype = $DB->get_record('pos_type', array('id' => $postypeid));

        $type_item1_data = array('typeid' => $postypeid, 'hierarchy' => 'position', 'typeidnumber' => $postype->idnumber, 'value' => '');
        $type_item1 = $hierarchy_generator->create_hierarchy_type_text($type_item1_data);

        $pos1 = $hierarchy_generator->create_pos(
            [
                'frameworkid' => $posframework->id, 'fullname' => 'Position 1', 'idnumber' => 'pos1',
                'totarasync' => 1, 'typeid' => $postype->id
            ]
        );

        $pos2 = $hierarchy_generator->create_pos(
            [
                'frameworkid' => $posframework->id, 'fullname' => 'Position 2', 'idnumber' => 'pos2',
                'totarasync' => 1, 'typeid' => $postype->id
            ]
        );

        $entry = new stdClass();
        $entry->idnumber = 'pos2';
        $entry->fullname = 'Position 2';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->frameworkidnumber = $posframework->idnumber;
        $entry->typeidnumber = null;

        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $source = new totara_sync_source_pos_database();
        $source->set_config('import_idnumber', '1');
        $source->set_config('import_fullname', '1');
        $source->set_config('import_frameworkidnumber', '1');
        $source->set_config('import_timemodified', '1');
        $source->set_config('import_deleted', '1');
        $source->set_config('import_typeidnumber', '1');

        $element = new totara_sync_element_pos();
        $element->set_config('allow_update', '1');
        $this->assertTrue($element->sync());

        $pos2_actual = $DB->get_record('pos', array('id' => $pos2->id));
        $this->assertEquals('pos2', $pos2_actual->idnumber);
        $this->assertEquals('Position 2', $pos2_actual->fullname);
        $this->assertEquals($pos2->typeid, $pos2_actual->typeid);
    }

    public function test_empty_typeidnumber() {
        global $DB;

        $this->resetAfterTest(true);

        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $posframework = $hierarchy_generator->create_framework('position');

        // Create a type
        $postypeid = $hierarchy_generator->create_pos_type(array('idnumber' => 'postype1'));
        $postype = $DB->get_record('pos_type', array('id' => $postypeid));

        $type_item1_data = array('typeid' => $postypeid, 'hierarchy' => 'position', 'typeidnumber' => $postype->idnumber, 'value' => '');
        $type_item1 = $hierarchy_generator->create_hierarchy_type_text($type_item1_data);

        $pos1 = $hierarchy_generator->create_pos(
            [
                'frameworkid' => $posframework->id, 'fullname' => 'Position 1', 'idnumber' => 'pos1',
                'totarasync' => 1, 'typeid' => $postype->id
            ]
        );

        $pos2 = $hierarchy_generator->create_pos(
            [
                'frameworkid' => $posframework->id, 'fullname' => 'Position 2', 'idnumber' => 'pos2',
                'totarasync' => 1, 'typeid' => $postype->id
            ]
        );

        $entry = new stdClass();
        $entry->idnumber = 'pos2';
        $entry->fullname = 'Position 2';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->frameworkidnumber = $posframework->idnumber;
        $entry->typeidnumber = '';

        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $source = new totara_sync_source_pos_database();
        $source->set_config('import_idnumber', '1');
        $source->set_config('import_fullname', '1');
        $source->set_config('import_frameworkidnumber', '1');
        $source->set_config('import_timemodified', '1');
        $source->set_config('import_deleted', '1');
        $source->set_config('import_typeidnumber', '1');

        $element = new totara_sync_element_pos();
        $element->set_config('allow_update', '1');
        $this->assertTrue($element->sync());

        $pos2_actual = $DB->get_record('pos', array('id' => $pos2->id));
        $this->assertEquals('pos2', $pos2_actual->idnumber);
        $this->assertEquals('Position 2', $pos2_actual->fullname);
        $this->assertEquals('0', $pos2_actual->typeid);
    }

    public function test_empty_fields_in_types() {
        global $DB;

        $this->resetAfterTest(true);

        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $posframework = $hierarchy_generator->create_framework('position');

        // Create a type
        $postypeid = $hierarchy_generator->create_pos_type();
        $postype = $DB->get_record('pos_type', array('id' => $postypeid));

        $type_item1_data = array('typeid' => $postypeid, 'hierarchy' => 'position', 'typeidnumber' => $postype->idnumber, 'value' => '', 'idnumber' => 'field1');
        $hierarchy_generator->create_hierarchy_type_text($type_item1_data);
        $type_item1 = $DB->get_record('pos_type_info_field', array('shortname' => 'text'.$postype->id));
        $type_item2_data = array('typeid' => $postypeid, 'hierarchy' => 'position', 'typeidnumber' => $postype->idnumber, 'value' => '2345', 'idnumber' => 'field2');
        $hierarchy_generator->create_hierarchy_type_menu($type_item2_data);
        $type_item2 = $DB->get_record('pos_type_info_field', array('shortname' => 'menu'.$postype->id));


        $pos1 = $hierarchy_generator->create_pos(
            [
                'frameworkid' => $posframework->id, 'fullname' => 'Position 1', 'idnumber' => 'pos1',
                'totarasync' => 1, 'typeid' => $postype->id
            ]
        );

        // Save customfield data.
        $itemnew = new stdClass();
        $itemnew->id = $pos1->id;
        $itemnew->typeid = $postype->id;
        $textfieldname = 'customfield_text' . $postype->id;
        $itemnew->$textfieldname = 'TESTING1';
        $menufieldname = 'customfield_menu' . $postype->id;
        $itemnew->$menufieldname = '3';

        customfield_save_data($itemnew, 'position', 'pos_type');

        $source = new totara_sync_source_pos_database();
        $source->set_config('import_idnumber', '1');
        $source->set_config('import_fullname', '1');
        $source->set_config('import_frameworkidnumber', '1');
        $source->set_config('import_timemodified', '1');
        $source->set_config('import_deleted', '1');
        $source->set_config('import_typeidnumber', '1');

        // We need field mappings here because the customfield names are
        // stupid, we can fix this after TL-16723 is fixed
        $source->set_config('import_customfield_' . $postype->id . '_text'.$postype->id, '1');
        $source->set_config('import_customfield_' . $postype->id . '_menu'.$postype->id, '1');
        $source->set_config('fieldmapping_customfield_' . $postype->id . '_text'.$postype->id, 'textcf1');
        $source->set_config('fieldmapping_customfield_' . $postype->id . '_menu'.$postype->id, 'menucf1');

        $entry = new stdClass();
        $entry->idnumber = 'pos1';
        $entry->fullname = 'Position 1';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->frameworkidnumber = $posframework->idnumber;
        $entry->typeidnumber = $postype->idnumber;
        $entry->textcf1 = '';
        $entry->menucf1 = '';

        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $pos1_cf_records = $DB->get_records('pos_type_info_data');

        $element = new totara_sync_element_pos();
        $element->set_config('allow_update', '1');
        $this->assertTrue($element->sync());

        $pos1_actual = $DB->get_record('pos', array('id' => $pos1->id));
        $this->assertEquals('pos1', $pos1_actual->idnumber);
        $this->assertEquals('Position 1', $pos1_actual->fullname);

        $pos1_cf_records = $DB->get_records('pos_type_info_data', array('positionid' => $pos1->id), '', 'fieldid, id, positionid, data');

        $this->assertEquals('', $pos1_cf_records[$type_item1->id]->data);
    }

    public function test_null_fields_in_types() {
        global $DB;

        $this->resetAfterTest(true);

        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $posframework = $hierarchy_generator->create_framework('position');

        // Create a type
        $postypeid = $hierarchy_generator->create_pos_type();
        $postype = $DB->get_record('pos_type', array('id' => $postypeid));

        $type_item1_data = array('typeid' => $postypeid, 'hierarchy' => 'position', 'typeidnumber' => $postype->idnumber, 'value' => '', 'idnumber' => 'field1');
        $hierarchy_generator->create_hierarchy_type_text($type_item1_data);
        $type_item1 = $DB->get_record('pos_type_info_field', array('shortname' => 'text'.$postype->id));
        $type_item2_data = array('typeid' => $postypeid, 'hierarchy' => 'position', 'typeidnumber' => $postype->idnumber, 'value' => '2345', 'idnumber' => 'field2');
        $hierarchy_generator->create_hierarchy_type_menu($type_item2_data);
        $type_item2 = $DB->get_record('pos_type_info_field', array('shortname' => 'menu'.$postype->id));


        $pos1 = $hierarchy_generator->create_pos(
            [
                'frameworkid' => $posframework->id, 'fullname' => 'Position 1', 'idnumber' => 'pos1',
                'totarasync' => 1, 'typeid' => $postype->id
            ]
        );

        // Save customfield data.
        $itemnew = new stdClass();
        $itemnew->id = $pos1->id;
        $itemnew->typeid = $postype->id;
        $textfieldname = 'customfield_text' . $postype->id;
        $itemnew->$textfieldname = 'TESTING1';
        $menufieldname = 'customfield_menu' . $postype->id;
        $itemnew->$menufieldname = '3';

        customfield_save_data($itemnew, 'position', 'pos_type');

        $source = new totara_sync_source_pos_database();
        $source->set_config('import_idnumber', '1');
        $source->set_config('import_fullname', '1');
        $source->set_config('import_frameworkidnumber', '1');
        $source->set_config('import_timemodified', '1');
        $source->set_config('import_deleted', '1');
        $source->set_config('import_typeidnumber', '1');

        // We need field mappings here because the customfield names are
        // stupid, we can fix this after TL-16723 is fixed
        $source->set_config('import_customfield_' . $postype->id . '_text'.$postype->id, '1');
        $source->set_config('import_customfield_' . $postype->id . '_menu'.$postype->id, '1');
        $source->set_config('fieldmapping_customfield_' . $postype->id . '_text'.$postype->id, 'textcf1');
        $source->set_config('fieldmapping_customfield_' . $postype->id . '_menu'.$postype->id, 'menucf1');

        $entry = new stdClass();
        $entry->idnumber = 'pos2';
        $entry->fullname = 'Position 2';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->frameworkidnumber = $posframework->idnumber;
        $entry->typeidnumber = $postype->idnumber;
        $entry->textcf1 = null;
        $entry->menucf1 = null;

        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $element = new totara_sync_element_pos();
        $element->set_config('allow_update', '1');
        $this->assertTrue($element->sync());

        $pos1_actual = $DB->get_record('pos', array('id' => $pos1->id));
        $this->assertEquals('pos1', $pos1_actual->idnumber);
        $this->assertEquals('Position 1', $pos1_actual->fullname);

        // Get the Custom field data records and check they are correct.
        $pos1_cf_records = $DB->get_records('pos_type_info_data', array('positionid' => $pos1->id), '', 'fieldid, id, positionid, data');
        $this->assertEquals('TESTING1', $pos1_cf_records[$type_item1->id]->data);
        $this->assertEquals('4567', $pos1_cf_records[$type_item2->id]->data);
    }
}
