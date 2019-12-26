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
require_once($CFG->dirroot . '/admin/tool/totara_sync/tests/source_database_testcase.php');
require_once($CFG->dirroot . '/admin/tool/totara_sync/sources/source_comp_database.php');

/**
 * @group tool_totara_sync
 */
class tool_totara_sync_comp_database_testcase extends totara_sync_database_testcase {

    public function setUp() {
        $this->elementname = 'comp';
        $this->sourcetable = 'totara_sync_comp_source';

        parent::setUp();

        set_config('source_comp', 'totara_sync_source_comp_database', 'totara_sync');

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
        $table->add_field('aggregationmethod', XMLDB_TYPE_INTEGER, '10');

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
        $compframework = $hierarchy_generator->create_framework('competency');

        $comp1 = $hierarchy_generator->create_comp(
            [
                'frameworkid' => $compframework->id, 'fullname' => 'Competency 1', 'idnumber' => 'comp1',
                'totarasync' => 1
            ]
        );

        $comp2 = $hierarchy_generator->create_comp(
            [
                'frameworkid' => $compframework->id, 'fullname' => 'Competency 2', 'idnumber' => 'comp2',
                'parentid' => $comp1->id, 'totarasync' => 1
            ]
        );

        $entry = new stdClass();
        $entry->idnumber = 'comp2';
        $entry->fullname = 'Competency 2';
        $entry->deleted = 0;
        $entry->timemodified = 0;
        $entry->frameworkidnumber = null;
        $entry->aggregationmethod = null;

        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $source = new totara_sync_source_comp_database();
        $source->set_config('import_idnumber', '1');
        $source->set_config('import_fullname', '1');
        $source->set_config('import_deleted', '1');
        $source->set_config('import_frameworkidnumber', '1');
        $source->set_config('import_timemodified', '1');
        $source->set_config('import_aggregationmethod', '1');

        $element = new totara_sync_element_comp();
        $element->set_config('allow_update', '1');
        $this->assertFalse($element->sync());

        $comp2_actual = $DB->get_record('comp', array('id' => $comp2->id));
        $this->assertTrue('comp1', $comp2_actual->idnumber);
        $this->assertTrue('Competency 1', $comp2_actual->fullname);
        $this->assertTrue($comp1->id, $comp2_actual->parentid);

    }

    public function test_null_in_other_fields() {
        global $DB;

        $this->resetAfterTest(true);

        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $compframework = $hierarchy_generator->create_framework('competency');

        $comp1 = $hierarchy_generator->create_comp(
            [
                'frameworkid' => $compframework->id, 'fullname' => 'Competency 1', 'idnumber' => 'comp1',
                'totarasync' => 1
            ]
        );

        $comp2 = $hierarchy_generator->create_comp(
            [
                'frameworkid' => $compframework->id, 'fullname' => 'Competency 2', 'idnumber' => 'comp2',
                'parentid' => $comp1->id, 'totarasync' => 1
            ]
        );

        $entry = new stdClass();
        $entry->idnumber = 'comp2';
        $entry->fullname = 'Competency 2';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->frameworkidnumber = $compframework->idnumber;
        $entry->description = null;
        $entry->parentidnumber = null;
        $entry->aggregationmethod = null;

        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $source = new totara_sync_source_comp_database();
        $source->set_config('import_idnumber', '1');
        $source->set_config('import_fullname', '1');
        $source->set_config('import_frameworkidnumber', '1');
        $source->set_config('import_timemodified', '1');
        $source->set_config('import_deleted', '1');
        $source->set_config('import_description', '1');
        $source->set_config('import_parentidnumber', '1');
        $source->set_config('import_aggregationmethod', '1');

        $element = new totara_sync_element_comp();
        $element->set_config('allow_update', '1');
        $this->assertTrue($element->sync());

        $comp2_actual = $DB->get_record('comp', array('id' => $comp2->id));
        $this->assertEquals('comp2', $comp2_actual->idnumber);
        $this->assertEquals('Competency 2', $comp2_actual->fullname);
        $this->assertEquals($comp1->id, $comp2_actual->parentid);

    }

    public function test_empty_strings_in_other_fields() {
        global $DB;

        $this->resetAfterTest(true);

        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $compframework = $hierarchy_generator->create_framework('competency');

        $comp1 = $hierarchy_generator->create_comp(
            [
                'frameworkid' => $compframework->id, 'fullname' => 'Competency 1', 'idnumber' => 'comp1',
                'totarasync' => 1
            ]
        );

        $comp2 = $hierarchy_generator->create_comp(
            [
                'frameworkid' => $compframework->id, 'fullname' => 'Competency 2', 'idnumber' => 'comp2',
                'parentid' => $comp1->id, 'totarasync' => 1
            ]
        );

        $entry = new stdClass();
        $entry->idnumber = 'comp2';
        $entry->fullname = 'Competency 2';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->frameworkidnumber = $compframework->idnumber;
        $entry->description = '';
        $entry->parentidnumber = '';
        $entry->aggregationmethod = 0;

        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $source = new totara_sync_source_comp_database();
        $source->set_config('import_idnumber', '1');
        $source->set_config('import_fullname', '1');
        $source->set_config('import_frameworkidnumber', '1');
        $source->set_config('import_timemodified', '1');
        $source->set_config('import_deleted', '1');
        $source->set_config('import_description', '1');
        $source->set_config('import_parentidnumber', '1');
        $source->set_config('import_aggregationmethod', '1');

        $element = new totara_sync_element_comp();
        $element->set_config('allow_update', '1');

        ob_start();
        $this->assertTrue($element->sync());
        ob_end_clean();

        $comp2_actual = $DB->get_record('comp', array('id' => $comp2->id));
        $this->assertEquals('comp2', $comp2_actual->idnumber);
        $this->assertEquals('Competency 2', $comp2_actual->fullname);
        $this->assertEquals('0', $comp2_actual->parentid);
        $this->assertEquals('1', $comp2_actual->aggregationmethod);

        $this->assertEquals(1, $DB->count_records('totara_sync_log', [
            'element' => 'comp',
            'logtype' => 'error',
            'action' => 'populatesynctablecsv',
            'info' => 'Unrecognised aggregation method value: 0'])
        );
    }

    public function test_null_typeidnumber() {
        global $DB;

        $this->resetAfterTest(true);

        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $compframework = $hierarchy_generator->create_framework('competency');

        // Create a type
        $comptypeid = $hierarchy_generator->create_comp_type();
        $comptype = $DB->get_record('comp_type', array('id' => $comptypeid));

        $type_item1_data = array('typeid' => $comptypeid, 'hierarchy' => 'competency', 'typeidnumber' => $comptype->idnumber, 'value' => '');
        $type_item1 = $hierarchy_generator->create_hierarchy_type_text($type_item1_data);

        $comp1 = $hierarchy_generator->create_comp(
            [
                'frameworkid' => $compframework->id, 'fullname' => 'Competency 1', 'idnumber' => 'comp1',
                'totarasync' => 1, 'typeid' => $comptype->id
            ]
        );

        $comp2 = $hierarchy_generator->create_comp(
            [
                'frameworkid' => $compframework->id, 'fullname' => 'Competency 2', 'idnumber' => 'comp2',
                'totarasync' => 1, 'typeid' => $comptype->id
            ]
        );

        $entry = new stdClass();
        $entry->idnumber = 'comp2';
        $entry->fullname = 'Competency 2';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->frameworkidnumber = $compframework->idnumber;
        $entry->typeidnumber = null;
        $entry->aggregationmethod = 1;

        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $source = new totara_sync_source_comp_database();
        $source->set_config('import_idnumber', '1');
        $source->set_config('import_fullname', '1');
        $source->set_config('import_frameworkidnumber', '1');
        $source->set_config('import_timemodified', '1');
        $source->set_config('import_deleted', '1');
        $source->set_config('import_typeidnumber', '1');
        $source->set_config('import_aggregationmethod', '1');

        $element = new totara_sync_element_comp();
        $element->set_config('allow_update', '1');
        $this->assertTrue($element->sync());

        $comp2_actual = $DB->get_record('comp', array('id' => $comp2->id));
        $this->assertEquals('comp2', $comp2_actual->idnumber);
        $this->assertEquals('Competency 2', $comp2_actual->fullname);
        $this->assertEquals($comp2->typeid, $comp2_actual->typeid);
    }

    public function test_empty_typeidnumber() {
        global $DB;

        $this->resetAfterTest(true);

        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $compframework = $hierarchy_generator->create_framework('competency');

        // Create a type
        $comptypeid = $hierarchy_generator->create_comp_type(array('idnumber' => 'comptype1'));
        $comptype = $DB->get_record('comp_type', array('id' => $comptypeid));

        $type_item1_data = array('typeid' => $comptypeid, 'hierarchy' => 'competency', 'typeidnumber' => $comptype->idnumber, 'value' => '');
        $type_item1 = $hierarchy_generator->create_hierarchy_type_text($type_item1_data);

        $comp1 = $hierarchy_generator->create_comp(
            [
                'frameworkid' => $compframework->id, 'fullname' => 'Competency 1', 'idnumber' => 'comp1',
                'totarasync' => 1, 'typeid' => $comptype->id
            ]
        );

        $comp2 = $hierarchy_generator->create_comp(
            [
                'frameworkid' => $compframework->id, 'fullname' => 'Competency 2', 'idnumber' => 'comp2',
                'totarasync' => 1, 'typeid' => $comptype->id
            ]
        );

        $entry = new stdClass();
        $entry->idnumber = 'comp2';
        $entry->fullname = 'Competency 2';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->frameworkidnumber = $compframework->idnumber;
        $entry->typeidnumber = '';
        $entry->aggregationmethod = 1;

        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $source = new totara_sync_source_comp_database();
        $source->set_config('import_idnumber', '1');
        $source->set_config('import_fullname', '1');
        $source->set_config('import_frameworkidnumber', '1');
        $source->set_config('import_timemodified', '1');
        $source->set_config('import_deleted', '1');
        $source->set_config('import_typeidnumber', '1');
        $source->set_config('import_aggregationmethod', '1');

        $element = new totara_sync_element_comp();
        $element->set_config('allow_update', '1');
        $this->assertTrue($element->sync());

        $comp2_actual = $DB->get_record('comp', array('id' => $comp2->id));
        $this->assertEquals('comp2', $comp2_actual->idnumber);
        $this->assertEquals('Competency 2', $comp2_actual->fullname);
        $this->assertEquals('0', $comp2_actual->typeid);
    }

    public function test_empty_strings_fields_in_types() {
        global $DB;

        $this->resetAfterTest(true);

        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $compframework = $hierarchy_generator->create_framework('competency');

        // Create a type
        $comptypeid = $hierarchy_generator->create_comp_type();
        $comptype = $DB->get_record('comp_type', array('id' => $comptypeid));

        $type_item1_data = array('typeid' => $comptypeid, 'hierarchy' => 'competency', 'typeidnumber' => $comptype->idnumber, 'value' => '', 'idnumber' => 'field1');
        $hierarchy_generator->create_hierarchy_type_text($type_item1_data);
        $type_item1 = $DB->get_record('comp_type_info_field', array('shortname' => 'text'.$comptype->id));
        $type_item2_data = array('typeid' => $comptypeid, 'hierarchy' => 'competency', 'typeidnumber' => $comptype->idnumber, 'value' => '2345', 'idnumber' => 'field2');
        $hierarchy_generator->create_hierarchy_type_menu($type_item2_data);
        $type_item2 = $DB->get_record('comp_type_info_field', array('shortname' => 'menu'.$comptype->id));


        $comp1 = $hierarchy_generator->create_comp(
            [
                'frameworkid' => $compframework->id, 'fullname' => 'Competency 1', 'idnumber' => 'comp1',
                'totarasync' => 1, 'typeid' => $comptype->id
            ]
        );

        // Save customfield data.
        $itemnew = new stdClass();
        $itemnew->id = $comp1->id;
        $itemnew->typeid = $comptype->id;
        $textfieldname = 'customfield_text' . $comptype->id;
        $itemnew->$textfieldname = 'TESTING1';
        $menufieldname = 'customfield_menu' . $comptype->id;
        $itemnew->$menufieldname = '3';

        customfield_save_data($itemnew, 'competency', 'comp_type');

        $source = new totara_sync_source_comp_database();
        $source->set_config('import_idnumber', '1');
        $source->set_config('import_fullname', '1');
        $source->set_config('import_frameworkidnumber', '1');
        $source->set_config('import_timemodified', '1');
        $source->set_config('import_deleted', '1');
        $source->set_config('import_typeidnumber', '1');
        $source->set_config('import_aggregationmethod', '1');

        // We need field mappings here because the customfield names are
        // stupid, we can fix this after TL-16723 is fixed
        $source->set_config('import_customfield_' . $comptype->id . '_text'.$comptype->id, '1');
        $source->set_config('import_customfield_' . $comptype->id . '_menu'.$comptype->id, '1');
        $source->set_config('fieldmapping_customfield_' . $comptype->id . '_text'.$comptype->id, 'textcf1');
        $source->set_config('fieldmapping_customfield_' . $comptype->id . '_menu'.$comptype->id, 'menucf1');

        $entry = new stdClass();
        $entry->idnumber = 'comp1';
        $entry->fullname = 'Competency 1';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->frameworkidnumber = $compframework->idnumber;
        $entry->typeidnumber = $comptype->idnumber;
        $entry->textcf1 = '';
        $entry->menucf1 = '';
        $entry->aggregationmethod = 1;

        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $comp1_cf_records = $DB->get_records('comp_type_info_data', array('competencyid' => $comp1->id), '', 'fieldid, id, competencyid, data');
        $this->assertEquals('TESTING1', $comp1_cf_records[$type_item1->id]->data);

        $element = new totara_sync_element_comp();
        $element->set_config('allow_update', '1');
        $this->assertTrue($element->sync());

        $comp1_actual = $DB->get_record('comp', array('id' => $comp1->id));
        $this->assertEquals('comp1', $comp1_actual->idnumber);
        $this->assertEquals('Competency 1', $comp1_actual->fullname);

        $comp1_cf_records = $DB->get_records('comp_type_info_data', array('competencyid' => $comp1->id), '', 'fieldid, id, competencyid, data');

        $this->assertEquals('', $comp1_cf_records[$type_item1->id]->data);
        // TODO: Menus are not working, the default should be used when deleted
        // $this->assertEquals('2345', $comp1_cf_records[$type_item2->id]->data);
    }

    public function test_null_fields_in_types() {
        global $DB;

        $this->resetAfterTest(true);

        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $compframework = $hierarchy_generator->create_framework('competency');

        // Create a type
        $comptypeid = $hierarchy_generator->create_comp_type();
        $comptype = $DB->get_record('comp_type', array('id' => $comptypeid));

        $type_item1_data = array('typeid' => $comptypeid, 'hierarchy' => 'competency', 'typeidnumber' => $comptype->idnumber, 'value' => '', 'idnumber' => 'field1');
        $hierarchy_generator->create_hierarchy_type_text($type_item1_data);
        $type_item1 = $DB->get_record('comp_type_info_field', array('shortname' => 'text'.$comptype->id));
        $type_item2_data = array('typeid' => $comptypeid, 'hierarchy' => 'competency', 'typeidnumber' => $comptype->idnumber, 'value' => '2345', 'idnumber' => 'field2');
        $hierarchy_generator->create_hierarchy_type_menu($type_item2_data);
        $type_item2 = $DB->get_record('comp_type_info_field', array('shortname' => 'menu'.$comptype->id));


        $comp1 = $hierarchy_generator->create_comp(
            [
                'frameworkid' => $compframework->id, 'fullname' => 'Competency 1', 'idnumber' => 'comp1',
                'totarasync' => 1, 'typeid' => $comptype->id
            ]
        );

        // Save customfield data.
        $itemnew = new stdClass();
        $itemnew->id = $comp1->id;
        $itemnew->typeid = $comptype->id;
        $textfieldname = 'customfield_text' . $comptype->id;
        $itemnew->$textfieldname = 'TESTING1';
        $menufieldname = 'customfield_menu' . $comptype->id;
        $itemnew->$menufieldname = '3';

        customfield_save_data($itemnew, 'competency', 'comp_type');

        $source = new totara_sync_source_comp_database();
        $source->set_config('import_idnumber', '1');
        $source->set_config('import_fullname', '1');
        $source->set_config('import_frameworkidnumber', '1');
        $source->set_config('import_timemodified', '1');
        $source->set_config('import_deleted', '1');
        $source->set_config('import_typeidnumber', '1');
        $source->set_config('import_aggregationmethod', '1');

        // We need field mappings here because the customfield names are
        // stupid, we can fix this after TL-16723 is fixed
        $source->set_config('import_customfield_' . $comptype->id . '_text'.$comptype->id, '1');
        $source->set_config('import_customfield_' . $comptype->id . '_menu'.$comptype->id, '1');
        $source->set_config('fieldmapping_customfield_' . $comptype->id . '_text'.$comptype->id, 'textcf1');
        $source->set_config('fieldmapping_customfield_' . $comptype->id . '_menu'.$comptype->id, 'menucf1');

        $entry = new stdClass();
        $entry->idnumber = 'comp2';
        $entry->fullname = 'Competency 2';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->frameworkidnumber = $compframework->idnumber;
        $entry->typeidnumber = $comptype->idnumber;
        $entry->textcf1 = null;
        $entry->menucf1 = null;
        $entry->aggregationmethod = 1;

        $comp1_cf_records = $DB->get_records('comp_type_info_data', array('competencyid' => $comp1->id), '', 'fieldid, id, competencyid, data');
        $this->assertEquals('TESTING1', $comp1_cf_records[$type_item1->id]->data);
        $this->assertEquals('4567', $comp1_cf_records[$type_item2->id]->data);

        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $element = new totara_sync_element_comp();
        $element->set_config('allow_update', '1');
        $this->assertTrue($element->sync());

        $comp1_actual = $DB->get_record('comp', array('id' => $comp1->id));
        $this->assertEquals('comp1', $comp1_actual->idnumber);
        $this->assertEquals('Competency 1', $comp1_actual->fullname);

        // Get the Custom field data records and check they are correct.
        $comp1_cf_records = $DB->get_records('comp_type_info_data', array('competencyid' => $comp1->id), '', 'fieldid, id, competencyid, data');
        $this->assertEquals('TESTING1', $comp1_cf_records[$type_item1->id]->data);
        $this->assertEquals('4567', $comp1_cf_records[$type_item2->id]->data);
    }
}
