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
require_once($CFG->dirroot . '/admin/tool/totara_sync/sources/source_org_database.php');

/**
 * @group tool_totara_sync
 */
class tool_totara_sync_org_database_testcase extends totara_sync_database_testcase {

    public function setUp() {
        $this->elementname = 'org';
        $this->sourcetable = 'totara_sync_org_source';

        parent::setUp();

        set_config('source_org', 'totara_sync_source_org_database', 'totara_sync');

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
        $orgframework = $hierarchy_generator->create_framework('organisation');

        $org1 = $hierarchy_generator->create_org(
            [
                'frameworkid' => $orgframework->id, 'fullname' => 'Organisation 1', 'idnumber' => 'org1',
                'totarasync' => 1
            ]
        );

        $org2 = $hierarchy_generator->create_org(
            [
                'frameworkid' => $orgframework->id, 'fullname' => 'Organisation 2', 'idnumber' => 'org2',
                'parentid' => $org1->id, 'totarasync' => 1
            ]
        );

        $entry = new stdClass();
        $entry->idnumber = 'org2';
        $entry->fullname = 'Organisation 2';
        $entry->deleted = 0;
        $entry->timemodified = 0;
        $entry->frameworkidnumber = null;

        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $source = new totara_sync_source_org_database();
        $source->set_config('import_idnumber', '1');
        $source->set_config('import_fullname', '1');
        $source->set_config('import_deleted', '1');
        $source->set_config('import_frameworkidnumber', '1');
        $source->set_config('import_timemodified', '1');

        $element = new totara_sync_element_org();
        $element->set_config('allow_update', '1');
        $this->assertFalse($element->sync());

        $org2_actual = $DB->get_record('org', array('id' => $org2->id));
        $this->assertTrue('org1', $org2_actual->idnumber);
        $this->assertTrue('Organisation 1', $org2_actual->fullname);
        $this->assertTrue($org1->id, $org2_actual->parentid);

    }

    public function test_null_in_other_fields() {
        global $DB;

        $this->resetAfterTest(true);

        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $orgframework = $hierarchy_generator->create_framework('organisation');

        $org1 = $hierarchy_generator->create_org(
            [
                'frameworkid' => $orgframework->id, 'fullname' => 'Organisation 1', 'idnumber' => 'org1',
                'totarasync' => 1
            ]
        );

        $org2 = $hierarchy_generator->create_org(
            [
                'frameworkid' => $orgframework->id, 'fullname' => 'Organisation 2', 'idnumber' => 'org2',
                'parentid' => $org1->id, 'totarasync' => 1
            ]
        );

        $entry = new stdClass();
        $entry->idnumber = 'org2';
        $entry->fullname = 'Organisation 2';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->frameworkidnumber = $orgframework->idnumber;
        $entry->description = null;
        $entry->parentidnumber = null;

        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $source = new totara_sync_source_org_database();
        $source->set_config('import_idnumber', '1');
        $source->set_config('import_fullname', '1');
        $source->set_config('import_frameworkidnumber', '1');
        $source->set_config('import_timemodified', '1');
        $source->set_config('import_deleted', '1');
        $source->set_config('import_description', '1');
        $source->set_config('import_parentidnumber', '1');

        $element = new totara_sync_element_org();
        $element->set_config('allow_update', '1');
        $this->assertTrue($element->sync());

        $org2_actual = $DB->get_record('org', array('id' => $org2->id));
        $this->assertEquals('org2', $org2_actual->idnumber);
        $this->assertEquals('Organisation 2', $org2_actual->fullname);
        $this->assertEquals($org1->id, $org2_actual->parentid);

    }

    public function test_empty_strings_in_other_fields() {
        global $DB;

        $this->resetAfterTest(true);

        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $orgframework = $hierarchy_generator->create_framework('organisation');

        $org1 = $hierarchy_generator->create_org(
            [
                'frameworkid' => $orgframework->id, 'fullname' => 'Organisation 1', 'idnumber' => 'org1',
                'totarasync' => 1
            ]
        );

        $org2 = $hierarchy_generator->create_org(
            [
                'frameworkid' => $orgframework->id, 'fullname' => 'Organisation 2', 'idnumber' => 'org2',
                'parentid' => $org1->id, 'totarasync' => 1
            ]
        );

        $entry = new stdClass();
        $entry->idnumber = 'org2';
        $entry->fullname = 'Organisation 2';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->frameworkidnumber = $orgframework->idnumber;
        $entry->description = '';
        $entry->parentidnumber = '';

        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $source = new totara_sync_source_org_database();
        $source->set_config('import_idnumber', '1');
        $source->set_config('import_fullname', '1');
        $source->set_config('import_frameworkidnumber', '1');
        $source->set_config('import_timemodified', '1');
        $source->set_config('import_deleted', '1');
        $source->set_config('import_description', '1');
        $source->set_config('import_parentidnumber', '1');

        $element = new totara_sync_element_org();
        $element->set_config('allow_update', '1');
        $this->assertTrue($element->sync());

        $org2_actual = $DB->get_record('org', array('id' => $org2->id));
        $this->assertEquals('org2', $org2_actual->idnumber);
        $this->assertEquals('Organisation 2', $org2_actual->fullname);
        $this->assertEquals('0', $org2_actual->parentid);
    }

    public function test_null_typeidnumber() {
        global $DB;

        $this->resetAfterTest(true);

        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $orgframework = $hierarchy_generator->create_framework('organisation');

        // Create a type
        $orgtypeid = $hierarchy_generator->create_org_type();
        $orgtype = $DB->get_record('org_type', array('id' => $orgtypeid));

        $type_item1_data = array('typeid' => $orgtypeid, 'hierarchy' => 'organisation', 'typeidnumber' => $orgtype->idnumber, 'value' => '');
        $type_item1 = $hierarchy_generator->create_hierarchy_type_text($type_item1_data);

        $org1 = $hierarchy_generator->create_org(
            [
                'frameworkid' => $orgframework->id, 'fullname' => 'Organisation 1', 'idnumber' => 'org1',
                'totarasync' => 1, 'typeid' => $orgtype->id
            ]
        );

        $org2 = $hierarchy_generator->create_org(
            [
                'frameworkid' => $orgframework->id, 'fullname' => 'Organisation 2', 'idnumber' => 'org2',
                'totarasync' => 1, 'typeid' => $orgtype->id
            ]
        );

        $entry = new stdClass();
        $entry->idnumber = 'org2';
        $entry->fullname = 'Organisation 2';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->frameworkidnumber = $orgframework->idnumber;
        $entry->typeidnumber = null;

        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $source = new totara_sync_source_org_database();
        $source->set_config('import_idnumber', '1');
        $source->set_config('import_fullname', '1');
        $source->set_config('import_frameworkidnumber', '1');
        $source->set_config('import_timemodified', '1');
        $source->set_config('import_deleted', '1');
        $source->set_config('import_typeidnumber', '1');

        $element = new totara_sync_element_org();
        $element->set_config('allow_update', '1');
        $this->assertTrue($element->sync());

        $org2_actual = $DB->get_record('org', array('id' => $org2->id));
        $this->assertEquals('org2', $org2_actual->idnumber);
        $this->assertEquals('Organisation 2', $org2_actual->fullname);
        $this->assertEquals($org2->typeid, $org2_actual->typeid);
    }

    public function test_empty_typeidnumber() {
        global $DB;

        $this->resetAfterTest(true);

        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $orgframework = $hierarchy_generator->create_framework('organisation');

        // Create a type
        $orgtypeid = $hierarchy_generator->create_org_type(array('idnumber' => 'orgtype1'));
        $orgtype = $DB->get_record('org_type', array('id' => $orgtypeid));

        $type_item1_data = array('typeid' => $orgtypeid, 'hierarchy' => 'organisation', 'typeidnumber' => $orgtype->idnumber, 'value' => '');
        $type_item1 = $hierarchy_generator->create_hierarchy_type_text($type_item1_data);

        $org1 = $hierarchy_generator->create_org(
            [
                'frameworkid' => $orgframework->id, 'fullname' => 'Organisation 1', 'idnumber' => 'org1',
                'totarasync' => 1, 'typeid' => $orgtype->id
            ]
        );

        $org2 = $hierarchy_generator->create_org(
            [
                'frameworkid' => $orgframework->id, 'fullname' => 'Organisation 2', 'idnumber' => 'org2',
                'totarasync' => 1, 'typeid' => $orgtype->id
            ]
        );

        $entry = new stdClass();
        $entry->idnumber = 'org2';
        $entry->fullname = 'Organisation 2';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->frameworkidnumber = $orgframework->idnumber;
        $entry->typeidnumber = '';

        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $source = new totara_sync_source_org_database();
        $source->set_config('import_idnumber', '1');
        $source->set_config('import_fullname', '1');
        $source->set_config('import_frameworkidnumber', '1');
        $source->set_config('import_timemodified', '1');
        $source->set_config('import_deleted', '1');
        $source->set_config('import_typeidnumber', '1');

        $element = new totara_sync_element_org();
        $element->set_config('allow_update', '1');
        $this->assertTrue($element->sync());

        $org2_actual = $DB->get_record('org', array('id' => $org2->id));
        $this->assertEquals('org2', $org2_actual->idnumber);
        $this->assertEquals('Organisation 2', $org2_actual->fullname);
        $this->assertEquals('0', $org2_actual->typeid);
    }

    public function test_empty_strings_fields_in_types() {
        global $DB;

        $this->resetAfterTest(true);

        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $orgframework = $hierarchy_generator->create_framework('organisation');

        // Create a type
        $orgtypeid = $hierarchy_generator->create_org_type();
        $orgtype = $DB->get_record('org_type', array('id' => $orgtypeid));

        $type_item1_data = array('typeid' => $orgtypeid, 'hierarchy' => 'organisation', 'typeidnumber' => $orgtype->idnumber, 'value' => '', 'idnumber' => 'field1');
        $hierarchy_generator->create_hierarchy_type_text($type_item1_data);
        $type_item1 = $DB->get_record('org_type_info_field', array('shortname' => 'text'.$orgtype->id));
        $type_item2_data = array('typeid' => $orgtypeid, 'hierarchy' => 'organisation', 'typeidnumber' => $orgtype->idnumber, 'value' => '2345', 'idnumber' => 'field2');
        $hierarchy_generator->create_hierarchy_type_menu($type_item2_data);
        $type_item2 = $DB->get_record('org_type_info_field', array('shortname' => 'menu'.$orgtype->id));


        $org1 = $hierarchy_generator->create_org(
            [
                'frameworkid' => $orgframework->id, 'fullname' => 'Organisation 1', 'idnumber' => 'org1',
                'totarasync' => 1, 'typeid' => $orgtype->id
            ]
        );

        // Save customfield data.
        $itemnew = new stdClass();
        $itemnew->id = $org1->id;
        $itemnew->typeid = $orgtype->id;
        $textfieldname = 'customfield_text' . $orgtype->id;
        $itemnew->$textfieldname = 'TESTING1';
        $menufieldname = 'customfield_menu' . $orgtype->id;
        $itemnew->$menufieldname = '3';

        customfield_save_data($itemnew, 'organisation', 'org_type');

        $source = new totara_sync_source_org_database();
        $source->set_config('import_idnumber', '1');
        $source->set_config('import_fullname', '1');
        $source->set_config('import_frameworkidnumber', '1');
        $source->set_config('import_timemodified', '1');
        $source->set_config('import_deleted', '1');
        $source->set_config('import_typeidnumber', '1');

        // We need field mappings here because the customfield names are
        // stupid, we can fix this after TL-16723 is fixed
        $source->set_config('import_customfield_' . $orgtype->id . '_text'.$orgtype->id, '1');
        $source->set_config('import_customfield_' . $orgtype->id . '_menu'.$orgtype->id, '1');
        $source->set_config('fieldmapping_customfield_' . $orgtype->id . '_text'.$orgtype->id, 'textcf1');
        $source->set_config('fieldmapping_customfield_' . $orgtype->id . '_menu'.$orgtype->id, 'menucf1');

        $entry = new stdClass();
        $entry->idnumber = 'org1';
        $entry->fullname = 'Organisation 1';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->frameworkidnumber = $orgframework->idnumber;
        $entry->typeidnumber = $orgtype->idnumber;
        $entry->textcf1 = '';
        $entry->menucf1 = '';

        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $org1_cf_records = $DB->get_records('org_type_info_data', array('organisationid' => $org1->id), '', 'fieldid, id, organisationid, data');
        $this->assertEquals('TESTING1', $org1_cf_records[$type_item1->id]->data);

        $element = new totara_sync_element_org();
        $element->set_config('allow_update', '1');
        $this->assertTrue($element->sync());

        $org1_actual = $DB->get_record('org', array('id' => $org1->id));
        $this->assertEquals('org1', $org1_actual->idnumber);
        $this->assertEquals('Organisation 1', $org1_actual->fullname);

        $org1_cf_records = $DB->get_records('org_type_info_data', array('organisationid' => $org1->id), '', 'fieldid, id, organisationid, data');

        $this->assertEquals('', $org1_cf_records[$type_item1->id]->data);
        // TODO: Menus are not working, the default should be used when deleted
        // $this->assertEquals('2345', $org1_cf_records[$type_item2->id]->data);
    }

    public function test_null_fields_in_types() {
        global $DB;

        $this->resetAfterTest(true);

        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $orgframework = $hierarchy_generator->create_framework('organisation');

        // Create a type
        $orgtypeid = $hierarchy_generator->create_org_type();
        $orgtype = $DB->get_record('org_type', array('id' => $orgtypeid));

        $type_item1_data = array('typeid' => $orgtypeid, 'hierarchy' => 'organisation', 'typeidnumber' => $orgtype->idnumber, 'value' => '', 'idnumber' => 'field1');
        $hierarchy_generator->create_hierarchy_type_text($type_item1_data);
        $type_item1 = $DB->get_record('org_type_info_field', array('shortname' => 'text'.$orgtype->id));
        $type_item2_data = array('typeid' => $orgtypeid, 'hierarchy' => 'organisation', 'typeidnumber' => $orgtype->idnumber, 'value' => '2345', 'idnumber' => 'field2');
        $hierarchy_generator->create_hierarchy_type_menu($type_item2_data);
        $type_item2 = $DB->get_record('org_type_info_field', array('shortname' => 'menu'.$orgtype->id));


        $org1 = $hierarchy_generator->create_org(
            [
                'frameworkid' => $orgframework->id, 'fullname' => 'Organisation 1', 'idnumber' => 'org1',
                'totarasync' => 1, 'typeid' => $orgtype->id
            ]
        );

        // Save customfield data.
        $itemnew = new stdClass();
        $itemnew->id = $org1->id;
        $itemnew->typeid = $orgtype->id;
        $textfieldname = 'customfield_text' . $orgtype->id;
        $itemnew->$textfieldname = 'TESTING1';
        $menufieldname = 'customfield_menu' . $orgtype->id;
        $itemnew->$menufieldname = '3';

        customfield_save_data($itemnew, 'organisation', 'org_type');

        $source = new totara_sync_source_org_database();
        $source->set_config('import_idnumber', '1');
        $source->set_config('import_fullname', '1');
        $source->set_config('import_frameworkidnumber', '1');
        $source->set_config('import_timemodified', '1');
        $source->set_config('import_deleted', '1');
        $source->set_config('import_typeidnumber', '1');

        // We need field mappings here because the customfield names are
        // stupid, we can fix this after TL-16723 is fixed
        $source->set_config('import_customfield_' .$orgtype->id . '_text'.$orgtype->id, '1');
        $source->set_config('import_customfield_' .$orgtype->id . '_menu'.$orgtype->id, '1');
        $source->set_config('fieldmapping_customfield_' .$orgtype->id . '_text'.$orgtype->id, 'textcf1');
        $source->set_config('fieldmapping_customfield_' .$orgtype->id . '_menu'.$orgtype->id, 'menucf1');

        $entry = new stdClass();
        $entry->idnumber = 'org2';
        $entry->fullname = 'Organisation 2';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->frameworkidnumber = $orgframework->idnumber;
        $entry->typeidnumber = $orgtype->idnumber;
        $entry->textcf1 = null;
        $entry->menucf1 = null;

        $org1_cf_records = $DB->get_records('org_type_info_data', array('organisationid' => $org1->id), '', 'fieldid, id, organisationid, data');
        $this->assertEquals('TESTING1', $org1_cf_records[$type_item1->id]->data);
        $this->assertEquals('4567', $org1_cf_records[$type_item2->id]->data);

        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $element = new totara_sync_element_org();
        $element->set_config('allow_update', '1');
        $this->assertTrue($element->sync());

        $org1_actual = $DB->get_record('org', array('id' => $org1->id));
        $this->assertEquals('org1', $org1_actual->idnumber);
        $this->assertEquals('Organisation 1', $org1_actual->fullname);

        // Get the Custom field data records and check they are correct.
        $org1_cf_records = $DB->get_records('org_type_info_data', array('organisationid' => $org1->id), '', 'fieldid, id, organisationid, data');
        $this->assertEquals('TESTING1', $org1_cf_records[$type_item1->id]->data);
        $this->assertEquals('4567', $org1_cf_records[$type_item2->id]->data);
    }
}
