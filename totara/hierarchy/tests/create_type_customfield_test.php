<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Oleg Demeshev <oleg.demeshev@totaralms.com>
 * @package totara_hierarchy
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/customfield/lib.php');

/**
 * Test hierarchy type creation.
 */
class totara_hierarchy_create_type_customfield_testcase extends advanced_testcase {

    /**
     * @var totara_hierarchy_generator
     */
    protected $hierarchy_generator = null;

    protected function tearDown() {
        $this->hierarchy_generator = null;
        parent::tearDown();
    }

    protected function setUp() {
        parent::setup();
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();

        // Set totara_hierarchy_generator.
        $this->hierarchy_generator = $generator->get_plugin_generator('totara_hierarchy');
    }

    /**
     * Tests the organisation type customfield.
     */
    public function test_organisation_type_customfield() {
        global $DB;

        // Create organisation type.
        $id = $this->hierarchy_generator->create_org_type();
        if (!$typeid = $DB->get_field('org_type', 'idnumber', array('id' => $id))) {
            throw new coding_exception('Unknown hierarchy type id '.$id.' in hierarchy definition');
        }

        // Create checkbox for organisation type.
        $defaultdata = 1; // Checked.
        $shortname   = 'checkbox'.$id;
        $data = array('hierarchy' => 'organisation', 'typeidnumber' => $typeid, 'value' => $defaultdata);
        $this->hierarchy_generator->create_hierarchy_type_checkbox($data);
        $this->assertTrue($DB->record_exists('org_type_info_field', array('shortname' => $shortname)));

        // Create text for organisation type.
        $defaultdata = 'Apple';
        $shortname   = 'text'.$id;
        $data = array('hierarchy' => 'organisation', 'typeidnumber' => $typeid, 'value' => $defaultdata);
        $this->hierarchy_generator->create_hierarchy_type_text($data);
        $this->assertTrue($DB->record_exists('org_type_info_field', array('shortname' => $shortname)));

        // Create menu of choice for organisation type.
        $defaultdata = '2345';
        $shortname   = 'menu'.$id;
        $data = array('hierarchy' => 'organisation', 'typeidnumber' => $typeid, 'value' => $defaultdata);
        $this->hierarchy_generator->create_hierarchy_type_menu($data);
        $this->assertTrue($DB->record_exists('org_type_info_field', array('shortname' => $shortname)));

        // Create text for organisation type.
        $defaultdata = '0'; // No valid value in the default data column needed.
        $shortname   = 'datetime'.$id;
        $data = array('hierarchy' => 'organisation', 'typeidnumber' => $typeid, 'value' => $defaultdata);
        $this->hierarchy_generator->create_hierarchy_type_datetime($data);
        $this->assertTrue($DB->record_exists('org_type_info_field', array('shortname' => $shortname)));
    }

    /**
     * Tests the position type customfield.
     */
    public function test_position_type_customfield() {
        global $DB;

        // Create position type.
        $id = $this->hierarchy_generator->create_pos_type();
        if (!$typeid = $DB->get_field('pos_type', 'idnumber', array('id' => $id))) {
            throw new coding_exception('Unknown hierarchy type id '.$id.' in hierarchy definition');
        }

        // Create checkbox for position type.
        $defaultdata = 1; // Checked.
        $shortname   = 'checkbox'.$id;
        $data = array('hierarchy' => 'position', 'typeidnumber' => $typeid, 'value' => $defaultdata);
        $this->hierarchy_generator->create_hierarchy_type_checkbox($data);
        $this->assertTrue($DB->record_exists('pos_type_info_field', array('shortname' => $shortname)));

        // Create text for position type.
        $defaultdata = 'Apple';
        $shortname   = 'text'.$id;
        $data = array('hierarchy' => 'position', 'typeidnumber' => $typeid, 'value' => $defaultdata);
        $this->hierarchy_generator->create_hierarchy_type_text($data);
        $this->assertTrue($DB->record_exists('pos_type_info_field', array('shortname' => $shortname)));

        // Create menu of choice for position type.
        $defaultdata = '2345';
        $shortname   = 'menu'.$id;
        $data = array('hierarchy' => 'position', 'typeidnumber' => $typeid, 'value' => $defaultdata);
        $this->hierarchy_generator->create_hierarchy_type_menu($data);
        $this->assertTrue($DB->record_exists('pos_type_info_field', array('shortname' => $shortname)));

        // Create text for position type.
        $defaultdata = '0'; // No valid value in the default data column needed.
        $shortname   = 'datetime'.$id;
        $data = array('hierarchy' => 'position', 'typeidnumber' => $typeid, 'value' => $defaultdata);
        $this->hierarchy_generator->create_hierarchy_type_datetime($data);
        $this->assertTrue($DB->record_exists('pos_type_info_field', array('shortname' => $shortname)));
    }

    /**
     * Tests the goal type customfield.
     */
    public function test_goal_type_customfield() {
        global $DB;

        // Create goal type.
        $id = $this->hierarchy_generator->create_goal_type();
        if (!$typeid = $DB->get_field('goal_type', 'idnumber', array('id' => $id))) {
            throw new coding_exception('Unknown hierarchy type id '.$id.' in hierarchy definition');
        }

        // Create checkbox for position type.
        $defaultdata = 1; // Checked.
        $shortname   = 'checkbox'.$id;
        $data = array('hierarchy' => 'goal', 'typeidnumber' => $typeid, 'value' => $defaultdata);
        $this->hierarchy_generator->create_hierarchy_type_checkbox($data);
        $this->assertTrue($DB->record_exists('goal_type_info_field', array('shortname' => $shortname)));

        // Create text for goal type.
        $defaultdata = 'Apple';
        $shortname   = 'text'.$id;
        $data = array('hierarchy' => 'goal', 'typeidnumber' => $typeid, 'value' => $defaultdata);
        $this->hierarchy_generator->create_hierarchy_type_text($data);
        $this->assertTrue($DB->record_exists('goal_type_info_field', array('shortname' => $shortname)));

        // Create menu of choice for goal type.
        $defaultdata = '2345';
        $shortname   = 'menu'.$id;
        $data = array('hierarchy' => 'goal', 'typeidnumber' => $typeid, 'value' => $defaultdata);
        $this->hierarchy_generator->create_hierarchy_type_menu($data);
        $this->assertTrue($DB->record_exists('goal_type_info_field', array('shortname' => $shortname)));

        // Create text for goal type.
        $defaultdata = '0'; // No valid value in the default data column needed.
        $shortname   = 'datetime'.$id;
        $data = array('hierarchy' => 'goal', 'typeidnumber' => $typeid, 'value' => $defaultdata);
        $this->hierarchy_generator->create_hierarchy_type_datetime($data);
        $this->assertTrue($DB->record_exists('goal_type_info_field', array('shortname' => $shortname)));
    }

    /**
     * Tests the competency type customfield.
     */
    public function test_competency_type_customfield() {
        global $DB;

        // Create competency type.
        $id = $this->hierarchy_generator->create_comp_type();
        if (!$typeid = $DB->get_field('comp_type', 'idnumber', array('id' => $id))) {
            throw new coding_exception('Unknown hierarchy type id '.$id.' in hierarchy definition');
        }

        // Create checkbox for competency type.
        $defaultdata = 1; // Checked.
        $shortname   = 'checkbox'.$id;
        $data = array('hierarchy' => 'competency', 'typeidnumber' => $typeid, 'value' => $defaultdata);
        $this->hierarchy_generator->create_hierarchy_type_checkbox($data);
        $this->assertTrue($DB->record_exists('comp_type_info_field', array('shortname' => $shortname)));

        // Create text for competency type.
        $defaultdata = 'Apple';
        $shortname   = 'text'.$id;
        $data = array('hierarchy' => 'competency', 'typeidnumber' => $typeid, 'value' => $defaultdata);
        $this->hierarchy_generator->create_hierarchy_type_text($data);
        $this->assertTrue($DB->record_exists('comp_type_info_field', array('shortname' => $shortname)));

        // Create menu of choice for competency type.
        $defaultdata = '2345';
        $shortname   = 'menu'.$id;
        $data = array('hierarchy' => 'competency', 'typeidnumber' => $typeid, 'value' => $defaultdata);
        $this->hierarchy_generator->create_hierarchy_type_menu($data);
        $this->assertTrue($DB->record_exists('comp_type_info_field', array('shortname' => $shortname)));

        // Create text for competency type.
        $defaultdata = '0'; // No valid value in the default data column needed.
        $shortname   = 'datetime'.$id;
        $data = array('hierarchy' => 'competency', 'typeidnumber' => $typeid, 'value' => $defaultdata);
        $this->hierarchy_generator->create_hierarchy_type_datetime($data);
        $this->assertTrue($DB->record_exists('comp_type_info_field', array('shortname' => $shortname)));
    }

    /**
     * Tests moving goal type customfields.
     */
    public function test_moving_goal_type_customfields() {
        global $DB;

        // Create first goal type.
        $idone = $this->hierarchy_generator->create_goal_type(['idnumber' => 'type_one']);
        if (!$typeidone = $DB->get_field('goal_type', 'idnumber', array('id' => $idone))) {
            throw new coding_exception('Unknown hierarchy type id '.$idone.' in hierarchy definition');
        }

        // Create second goal type.
        $idtwo = $this->hierarchy_generator->create_goal_type(['idnumber' => 'type_two']);
        if (!$typeidtwo = $DB->get_field('goal_type', 'idnumber', array('id' => $idtwo))) {
            throw new coding_exception('Unknown hierarchy type id '.$idtwo.' in hierarchy definition');
        }

        // Create checkbox for position type.
        $defaultdata = 1; // Checked.
        $shortname   = 'checkbox'.$idone;
        $data = array('hierarchy' => 'goal', 'typeidnumber' => $typeidone, 'value' => $defaultdata);
        $this->hierarchy_generator->create_hierarchy_type_checkbox($data);
        $this->assertTrue($DB->record_exists('goal_type_info_field', array('shortname' => $shortname)));
        $shortname   = 'checkbox'.$idtwo;
        $data = array('hierarchy' => 'goal', 'typeidnumber' => $typeidtwo, 'value' => $defaultdata);
        $this->hierarchy_generator->create_hierarchy_type_checkbox($data);
        $this->assertTrue($DB->record_exists('goal_type_info_field', array('shortname' => $shortname)));

        // Create text for goal type.
        $defaultdata = 'Apple';
        $shortname   = 'text'.$idone;
        $data = array('hierarchy' => 'goal', 'typeidnumber' => $typeidone, 'value' => $defaultdata);
        $this->hierarchy_generator->create_hierarchy_type_text($data);
        $this->assertTrue($DB->record_exists('goal_type_info_field', array('shortname' => $shortname)));
        $shortname   = 'text'.$idtwo;
        $data = array('hierarchy' => 'goal', 'typeidnumber' => $typeidtwo, 'value' => $defaultdata);
        $this->hierarchy_generator->create_hierarchy_type_text($data);
        $this->assertTrue($DB->record_exists('goal_type_info_field', array('shortname' => $shortname)));

        // Create menu of choice for goal type.
        $defaultdata = '2345';
        $shortname   = 'menu'.$idone;
        $data = array('hierarchy' => 'goal', 'typeidnumber' => $typeidone, 'value' => $defaultdata);
        $this->hierarchy_generator->create_hierarchy_type_menu($data);
        $this->assertTrue($DB->record_exists('goal_type_info_field', array('shortname' => $shortname)));
        $shortname   = 'menu'.$idtwo;
        $data = array('hierarchy' => 'goal', 'typeidnumber' => $typeidtwo, 'value' => $defaultdata);
        $this->hierarchy_generator->create_hierarchy_type_menu($data);
        $this->assertTrue($DB->record_exists('goal_type_info_field', array('shortname' => $shortname)));

        // Create text for goal type.
        $defaultdata = '0'; // No valid value in the default data column needed.
        $shortname   = 'datetime'.$idone;
        $data = array('hierarchy' => 'goal', 'typeidnumber' => $typeidone, 'value' => $defaultdata);
        $this->hierarchy_generator->create_hierarchy_type_datetime($data);
        $this->assertSame(1, $DB->count_records('goal_type_info_field', array('shortname' => $shortname)));
        $this->assertTrue($DB->record_exists('goal_type_info_field', array('shortname' => $shortname)));

        $fields_one = [];
        $fields_two = [];

        $fields = $DB->get_records('goal_type_info_field', ['typeid' => $idone], 'sortorder ASC');
        $expected = ['checkbox'.$idone, 'text'.$idone, 'menu'.$idone, 'datetime'.$idone];
        $this->assertCount(count($expected), $fields);
        foreach ($fields as $field) {
            $fields_one[$field->shortname] = $field;
            $this->assertSame(array_shift($expected), $field->shortname);
        }

        $fields = $DB->get_records('goal_type_info_field', ['typeid' => $idtwo], 'sortorder ASC');
        $expected = ['checkbox'.$idtwo, 'text'.$idtwo, 'menu'.$idtwo];
        $this->assertCount(count($expected), $fields);
        foreach ($fields as $field) {
            $fields_two[$field->shortname] = $field;
            $this->assertSame(array_shift($expected), $field->shortname);
        }

        $type = get_customfield_type_instace('goal', context_system::instance(), array('typeid' => $idone, 'class' => 'company'));
        $this->assertInstanceOf('totara_customfield\prefix\hierarchy_type', $type);

        $this->assertTrue($type->move($fields_one['checkbox'.$idone]->id, 'down'));
        $fields = $DB->get_records('goal_type_info_field', ['typeid' => $idone], 'sortorder ASC');
        $expected = ['text'.$idone, 'checkbox'.$idone, 'menu'.$idone, 'datetime'.$idone];
        $this->assertCount(count($expected), $fields);
        foreach ($fields as $field) {
            $fields_one[$field->shortname] = $field;
            $this->assertSame(array_shift($expected), $field->shortname);
        }

        $this->assertTrue($type->move($fields_one['checkbox'.$idone]->id, 'down'));
        $fields = $DB->get_records('goal_type_info_field', ['typeid' => $idone], 'sortorder ASC');
        $expected = ['text'.$idone, 'menu'.$idone, 'checkbox'.$idone, 'datetime'.$idone];
        $this->assertCount(count($expected), $fields);
        foreach ($fields as $field) {
            $fields_one[$field->shortname] = $field;
            $this->assertSame(array_shift($expected), $field->shortname);
        }

        $this->assertTrue($type->move($fields_one['checkbox'.$idone]->id, 'down'));
        $fields = $DB->get_records('goal_type_info_field', ['typeid' => $idone], 'sortorder ASC');
        $expected = ['text'.$idone, 'menu'.$idone, 'datetime'.$idone, 'checkbox'.$idone];
        $this->assertCount(count($expected), $fields);
        foreach ($fields as $field) {
            $fields_one[$field->shortname] = $field;
            $this->assertSame(array_shift($expected), $field->shortname);
        }

        $this->assertFalse($type->move($fields_one['checkbox'.$idone]->id, 'down'));
        $this->assertDebuggingCalled('Invalid action, the selected field cannot be moved down');
        $fields = $DB->get_records('goal_type_info_field', ['typeid' => $idone], 'sortorder ASC');
        $expected = ['text'.$idone, 'menu'.$idone, 'datetime'.$idone, 'checkbox'.$idone];
        $this->assertCount(count($expected), $fields);
        foreach ($fields as $field) {
            $fields_one[$field->shortname] = $field;
            $this->assertSame(array_shift($expected), $field->shortname);
        }

        $this->assertTrue($type->move($fields_one['checkbox'.$idone]->id, 'up'));
        $fields = $DB->get_records('goal_type_info_field', ['typeid' => $idone], 'sortorder ASC');
        $expected = ['text'.$idone, 'menu'.$idone, 'checkbox'.$idone, 'datetime'.$idone];
        $this->assertCount(count($expected), $fields);
        foreach ($fields as $field) {
            $fields_one[$field->shortname] = $field;
            $this->assertSame(array_shift($expected), $field->shortname);
        }

        $this->assertTrue($type->move($fields_one['menu'.$idone]->id, 'up'));
        $fields = $DB->get_records('goal_type_info_field', ['typeid' => $idone], 'sortorder ASC');
        $expected = ['menu'.$idone, 'text'.$idone, 'checkbox'.$idone, 'datetime'.$idone];
        $this->assertCount(count($expected), $fields);
        foreach ($fields as $field) {
            $fields_one[$field->shortname] = $field;
            $this->assertSame(array_shift($expected), $field->shortname);
        }

        $this->assertFalse($type->move($fields_one['menu'.$idone]->id, 'up'));
        $this->assertDebuggingCalled('Invalid action, the selected field cannot be moved up');
        $fields = $DB->get_records('goal_type_info_field', ['typeid' => $idone], 'sortorder ASC');
        $expected = ['menu'.$idone, 'text'.$idone, 'checkbox'.$idone, 'datetime'.$idone];
        $this->assertCount(count($expected), $fields);
        foreach ($fields as $field) {
            $fields_one[$field->shortname] = $field;
            $this->assertSame(array_shift($expected), $field->shortname);
        }

        // Check nothing in type 2 has been moved.
        $fields = $DB->get_records('goal_type_info_field', ['typeid' => $idtwo], 'sortorder ASC');
        $expected = ['checkbox'.$idtwo, 'text'.$idtwo, 'menu'.$idtwo];
        $this->assertCount(count($expected), $fields);
        foreach ($fields as $field) {
            $fields_two[$field->shortname] = $field;
            $this->assertSame(array_shift($expected), $field->shortname);
        }
    }
}
