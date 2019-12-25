<?php // $Id$
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Simon Coggins <simonc@catalyst.net.nz>
 * @editor David Curry <david.curry@totaralms.com>
 * @package totara
 * @subpackage hierarchy
 */

/*
 * PhpUnit tests for hierarchy/lib.php
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}
global $CFG;
require_once($CFG->dirroot . '/totara/hierarchy/lib.php');
require_once($CFG->dirroot . '/totara/hierarchy/prefix/competency/lib.php');


class hierarchylib_test extends advanced_testcase {

    /** @var  $competency competency */
    private $competency, $nofwid;

    // test data for database
    private $frame1, $frame2;
    private $type1, $type2, $type3;
    private $comp1, $comp2, $comp3, $comp4, $comp5;
    private $type_field_data, $type_data_data, $competency_data, $template_data, $template_assignment_data, $org_pos_data;
    private $relations_data, $scale_assignments_data, $plan_competency_assign_data, $plan_course_assign_data, $events_handlers_data;

    protected function tearDown() {
        $this->competency = null;
        $this->frame1 = null;
        $this->type1 = null;
        $this->comp1 = null;
        $this->type_field_data = null;
        $this->relations_data = null;
        parent::tearDown();
    }

    protected function setUp() {
        global $DB;
        parent::setup();

        $admin = get_admin();
        $userid = $admin->id;

        // set up some hierarchy frameworks
        $this->frame1 = new stdClass();
        $this->frame1->fullname = 'Framework 1';
        $this->frame1->shortname = 'FW1';
        $this->frame1->description = 'Description 1';
        $this->frame1->sortorder = 1;
        $this->frame1->idnumber = 'ID1';
        $this->frame1->visible = 1;
        $this->frame1->timecreated = 1265963591;
        $this->frame1->timemodified = 1265963591;
        $this->frame1->usermodified = $userid;
        $this->frame1->hidecustomfields = 1;
        $this->frame1->id = $DB->insert_record('comp_framework', $this->frame1);

        $this->frame2 = new stdClass();
        $this->frame2->fullname = 'Framework 2';
        $this->frame2->shortname = 'FW2';
        $this->frame2->description = 'Description 2';
        $this->frame2->sortorder = 2;
        $this->frame2->idnumber = 'ID2';
        $this->frame2->visible = 1;
        $this->frame2->timecreated = 1265963591;
        $this->frame2->timemodified = 1265963591;
        $this->frame2->usermodified = $userid;
        $this->frame2->hidecustomfields = 1;
        $this->frame2->id = $DB->insert_record('comp_framework', $this->frame2);

        // create the competency object
        $this->competency = new competency();
        $this->competency->frameworkid = $this->frame1->id;

        // create 2nd competency object with no frameworkid specified
        $this->nofwid = new competency();

        // Set up some hierarchy types
        $this->type1 = new stdClass();
        $this->type1->fullname = 'type 1';
        $this->type1->shortname = 'type 1';
        $this->type1->description = 'Description 1';
        $this->type1->timemodified = 1265963591;
        $this->type1->timecreated = 1265963591;
        $this->type1->usermodified = $userid;
        $this->type1->idnumber = 'TYPE1ID';
        $this->type1->id = $DB->insert_record('comp_type', $this->type1);

        $this->type2 = new stdClass();
        $this->type2->fullname = 'type 2';
        $this->type2->shortname = 'type 2';
        $this->type2->description = 'Description 2';
        $this->type2->timecreated = 1265963591;
        $this->type2->timemodified = 1265963591;
        $this->type2->usermodified = $userid;
        $this->type2->idnumber = 'TYPE1ID';
        $this->type2->id = $DB->insert_record('comp_type', $this->type2);

        $this->type3 = new stdClass();
        $this->type3->fullname = 'type 3';
        $this->type3->shortname = 'type 3';
        $this->type3->description = 'Description 3';
        $this->type3->timecreated = 1265963591;
        $this->type3->timemodified = 1265963591;
        $this->type3->usermodified = $userid;
        $this->type3->id = $DB->insert_record('comp_type', $this->type3);

        // set up some competencies
        $newcomp = new stdClass();
        $newcomp->fullname = 'Competency 1';
        $newcomp->shortname = 'Comp 1';
        $newcomp->description = 'Competency Description 1';
        $newcomp->idnumber = 'C1';
        $newcomp->visible = 1;
        $newcomp->aggregationmethod = 1;
        $newcomp->proficiencyexpected = 1;
        $newcomp->evidencecount = 0;
        $newcomp->usermodified = $userid;
        $newcomp->typeid = $this->type1->id;
        $this->comp1 = $this->competency->add_hierarchy_item($newcomp, 0, $this->frame1->id, false, true, false);

        $newcomp = new stdClass();
        $newcomp->fullname = 'Competency 2';
        $newcomp->shortname = 'Comp 2';
        $newcomp->description = 'Competency Description 2';
        $newcomp->idnumber = 'C2';
        $newcomp->visible = 1;
        $newcomp->aggregationmethod = 1;
        $newcomp->proficiencyexpected = 1;
        $newcomp->evidencecount = 0;
        $newcomp->usermodified = $userid;
        $newcomp->typeid = $this->type2->id;
        $this->comp2 = $this->competency->add_hierarchy_item($newcomp, $this->comp1->id, $this->frame1->id, false, true, false);

        $newcomp = new stdClass();
        $newcomp->fullname = 'F2 Competency 1';
        $newcomp->shortname = 'F2 Comp 1';
        $newcomp->description = 'F2 Competency Description 1';
        $newcomp->idnumber = 'F2 C1';
        $newcomp->visible = 1;
        $newcomp->aggregationmethod = 1;
        $newcomp->proficiencyexpected = 1;
        $newcomp->evidencecount = 0;
        $newcomp->usermodified = $userid;
        $newcomp->typeid = $this->type2->id;
        $this->comp3 = $this->competency->add_hierarchy_item($newcomp, 0, $this->frame2->id, false, true, false);

        $newcomp = new stdClass();
        $newcomp->fullname = 'Competency 3';
        $newcomp->shortname = 'Comp 3';
        $newcomp->description = 'Competency Description 3';
        $newcomp->idnumber = 'C3';
        $newcomp->visible = 1;
        $newcomp->aggregationmethod = 1;
        $newcomp->proficiencyexpected = 1;
        $newcomp->evidencecount = 0;
        $newcomp->usermodified = $userid;
        $newcomp->typeid = 0;
        $this->comp4 = $this->competency->add_hierarchy_item($newcomp, $this->comp1->id, $this->frame1->id, false, true, false);

        $newcomp = new stdClass();
        $newcomp->fullname = 'Competency 4';
        $newcomp->shortname = 'Comp 4';
        $newcomp->description = 'Competency Description 4';
        $newcomp->idnumber = 'C4';
        $newcomp->visible = 1;
        $newcomp->aggregationmethod = 1;
        $newcomp->proficiencyexpected = 1;
        $newcomp->evidencecount = 0;
        $newcomp->usermodified = $userid;
        $newcomp->typeid = 0;
        $this->comp5 = $this->competency->add_hierarchy_item($newcomp, 0, $this->frame1->id, false, true, false);

        //set up a hierarchy custom type field
        $this->type_field_data = new stdClass();
        $this->type_field_data->fullname = 'Custom Field 1';
        $this->type_field_data->shortname = 'CF1';
        $this->type_field_data->typeid = 2;
        $this->type_field_data->datatype = 'checkbox';
        $this->type_field_data->description = 'Custom Field Description 1';
        $this->type_field_data->sortorder = 1;
        $this->type_field_data->hidden = 0;
        $this->type_field_data->locked = 0;
        $this->type_field_data->required = 0;
        $this->type_field_data->forceunique = 0;
        $this->type_field_data->id = $DB->insert_record('comp_type_info_field', $this->type_field_data);

        $this->type_data_data = new stdClass();
        $this->type_data_data->data = 1;
        $this->type_data_data->fieldid = $this->type_field_data->id;
        $this->type_data_data->competencyid = $this->comp2->id;
        $this->type_data_data->id = $DB->insert_record('comp_type_info_data', $this->type_data_data);

        //set up evidence data
        $this->competency_data = new stdClass();
        $this->competency_data->userid = 1;
        $this->competency_data->competencyid = 1;
        $this->competency_data->timecreated = 1265963591;
        $this->competency_data->timemodified = 1265963591;
        $this->competency_data->reaggregate = 1;
        $this->competency_data->manual = 1;
        $this->competency_data->iteminstance = 1;
        $this->competency_data->usermodified = $userid;
        $this->competency_data->itemid = 1;
        $this->competency_data->comprecordid = $DB->insert_record('comp_record', $this->competency_data);
        $this->competency_data->compcriteriaid = $DB->insert_record('comp_criteria', $this->competency_data);
        $this->competency_data->compcriteriarecordid = $DB->insert_record('comp_criteria_record', $this->competency_data);

        //set up a competency template
        $this->template_data = new stdClass();
        $this->template_data->frameworkid = 1;
        $this->template_data->fullname = 'framework 1';
        $this->template_data->visible = 1;
        $this->template_data->competencycount = 1;
        $this->template_data->timecreated = 1265963591;
        $this->template_data->timemodified = 1265963591;
        $this->template_data->usermodified = $userid;
        $this->template_data->id = $DB->insert_record('comp_template', $this->template_data);

        //set up competency template assignments
        $this->template_assignment_data = new stdClass();
        $this->template_assignment_data->templateid = 1;
        $this->template_assignment_data->type = 1;
        $this->template_assignment_data->instanceid = 1;
        $this->template_assignment_data->timecreated = 1265963591;
        $this->template_assignment_data->usermodified = $userid;
        $this->template_assignment_data->id = $DB->insert_record('comp_template_assignment', $this->template_assignment_data);

        //set up org/pos competency links
        $this->org_pos_data = new stdClass();
        $this->org_pos_data->positionid = 1;
        $this->org_pos_data->organisationid = 1;
        $this->org_pos_data->timecreated = 1265963591;
        $this->org_pos_data->timemodified = 1265963591;
        $this->org_pos_data->usermodified = $userid;
        $this->org_pos_data->orgid = $DB->insert_record('org_competencies', $this->org_pos_data);
        $this->org_pos_data->posid = $DB->insert_record('pos_competencies', $this->org_pos_data);

        //set up relations
        $this->relations_data = new stdClass();
        $this->relations_data->id1 = 1;
        $this->relations_data->id2 = 1;
        $this->relations_data->id = $DB->insert_record('comp_relations', $this->relations_data);

        //set up competency scale assignments
        $this->scale_assignments_data = new stdClass();
        $this->scale_assignments_data->scaleid = 1;
        $this->scale_assignments_data->frameworkid = 1;
        $this->scale_assignments_data->timemodified = 1;
        $this->scale_assignments_data->usermodified = $userid;
        $this->scale_assignments_data->id = $DB->insert_record('comp_scale_assignments', $this->scale_assignments_data);

        //set up plan competencies
        $this->plan_competency_assign_data = new stdClass();
        $this->plan_competency_assign_data->planid = 1;
        $this->plan_competency_assign_data->competencyid = 5;
        $this->plan_competency_assign_data->id = $DB->insert_record('dp_plan_competency_assign', $this->plan_competency_assign_data);

        //set up plan courses
        $this->plan_course_assign_data = new stdClass();
        $this->plan_course_assign_data->planid = 1;
        $this->plan_course_assign_data->courseid = 3;
        $this->plan_course_assign_data->id = $DB->insert_record('dp_plan_course_assign', $this->plan_course_assign_data);

        //set up event handlers
        $this->events_handlers_data = new stdClass();
        $this->events_handlers_data->eventname = 'fakeevent';
        $this->events_handlers_data->component = '';
        $this->events_handlers_data->handlerfile = '';
        $this->events_handlers_data->handlerfunction = '';
        $this->events_handlers_data->schedule = '';
        $this->events_handlers_data->status = 0;
        $this->events_handlers_data->internal = 1;
        $this->events_handlers_data->id = $DB->insert_record('events_handlers', $this->events_handlers_data);
    }

    function test_hierarchy_get_framework() {
        global $DB;
        $competency = $this->competency;

        // specifying id should get that framework
        $this->assertEquals($this->frame2, $competency->get_framework($this->frame2->id));
        // not specifying id should get first framework (by sort order)
        // the framework returned should contain all the necessary fields
        $this->assertEquals($this->frame1, $competency->get_framework());
        // clear all frameworks
        $DB->delete_records('comp_framework');
        // if no frameworks exist should return false
        $this->assertFalse((bool)$competency->get_framework(0, false, true));

        $this->resetAfterTest(true);
    }

    function test_hierarchy_get_type_by_id() {
        $competency = $this->competency;

        // the type returned should contain all the necessary fields
        $this->assertEquals($this->type1, $competency->get_type_by_id($this->type1->id));
        // the type with the correct id should be returned
        $this->assertEquals($this->type2, $competency->get_type_by_id($this->type2->id));
        // false should be returned if the type doesn't exist
        $this->assertFalse((bool)$competency->get_type_by_id(999));
        $this->resetAfterTest(true);
    }

    function test_hierarchy_get_frameworks() {
        global $DB;
        $competency = $this->competency;

        // should return an array of frameworks
        $this->assertTrue((bool)is_array($competency->get_frameworks()));
        // the array should include all frameworks
        $this->assertEquals(2, count($competency->get_frameworks()));
        // each array element should contain a framework
        $this->assertEquals($this->frame1, current($competency->get_frameworks()));
        // clear out the framework
        $DB->delete_records('comp_framework');
        // if no frameworks exist should return false
        $this->assertFalse((bool)$competency->get_frameworks());
        $this->resetAfterTest(true);
    }

    function test_hierarchy_get_types() {
        global $DB;
        $competency = $this->competency;
        $type1 = $this->type1;
        // should return an array of types
        $this->assertTrue((bool)is_array($competency->get_types()));
        // the array should include all types (in this framework)
        $this->assertEquals(3, count($competency->get_types()));
        // each array element should contain a type
        $this->assertEquals($type1, current($competency->get_types()));
        // clear out the types
        $DB->delete_records('comp_type');
        // if no types exist should return false
        $this->assertFalse((bool)$competency->get_types());
        $this->resetAfterTest(true);
    }

    function test_hierarchy_get_custom_fields() {
        $competency = $this->competency;
        $customfields = $competency->get_custom_fields($this->comp2->id);

        //Returned value is an array
        $this->assertTrue((bool)is_array($customfields));

        //Returned array is not empty
        $this->assertFalse((bool)empty($customfields));

        //Returned array contains one item
        $this->assertEquals(1, count($customfields));

        // Returned array is identical to expected data.
        $expectedcustomfield = clone($this->type_data_data);
        $expectedcustomfield->datatype = $this->type_field_data->datatype;
        $expectedcustomfield->hidden = $this->type_field_data->hidden;
        $expectedcustomfield->fullname = $this->type_field_data->fullname;
        $expectedcustomfield->shortname = $this->type_field_data->shortname;
        $expected = array($this->type_data_data->id => $expectedcustomfield);
        $this->assertEquals($expected, $customfields);

        //Empty array is returned for a non-existent item id
        $this->assertEquals(array(), $competency->get_custom_fields(9000));
        $this->resetAfterTest(true);
    }

    function test_hierarchy_get_item() {
        $competency = $this->competency;

        // the item returned should contain all the necessary fields
        $this->assertEquals($this->comp1, $competency->get_item($this->comp1->id));
        // the item should match the id requested
        $this->assertEquals($this->comp2, $competency->get_item($this->comp2->id));
        // should return false if the item doesn't exist
        $this->assertFalse((bool)$competency->get_item(999));
        $this->resetAfterTest(true);
    }

    function test_hierarchy_get_items() {
        global $DB;
        $competency = $this->competency;

        // should return an array of items
        $this->assertTrue((bool)is_array($competency->get_items()));
        // the array should include all items
        $this->assertEquals(4, count($competency->get_items()));
        // each array element should contain an item object
        $this->assertEquals($this->comp1, current($competency->get_items()));
        // clear out the items
        $DB->delete_records('comp');
        // if no items exist should return false
        $this->assertFalse((bool)$competency->get_items());
        $this->resetAfterTest(true);
    }

    function test_hierarchy_get_items_by_parent() {
        global $DB;
        $competency = $this->competency;

        // should return an array of items belonging to specified parent
        $this->assertTrue((bool)is_array($competency->get_items_by_parent($this->comp1->id)));
        // should return one element per item
        $this->assertEquals(2, count($competency->get_items_by_parent($this->comp1->id)));
        // each array element should contain an item
        $this->assertEquals($this->comp2, current($competency->get_items_by_parent($this->comp1->id)));
        // if no parent specified should return root level items
        $this->assertEquals($this->comp1, current($competency->get_items_by_parent()));
        // clear out the items
        $DB->delete_records('comp');
        // if no items exist should return false for root items and parents
        $this->assertFalse((bool)$competency->get_items_by_parent());
        $this->assertFalse((bool)$competency->get_items_by_parent(1));
        $this->resetAfterTest(true);
    }

    function test_hierarchy_get_all_root_items() {
        global $DB;
        $competency = $this->competency;
        $nofwid = $this->nofwid;

        // should return root items for framework where id specified
        $this->assertEquals($this->comp1, current($competency->get_all_root_items()));
        // should return all root items (cross framework) if no fwid given
        $this->assertEquals(3, count($nofwid->get_all_root_items()));
        // should return all root items, even if fwid given, if $all set to true
        $this->assertEquals(3, count($competency->get_all_root_items(true)));
        // clear out the items
        $DB->delete_records('comp');
        // if no items exist should return false
        $this->assertFalse((bool)$competency->get_all_root_items());
        $this->assertFalse((bool)$nofwid->get_all_root_items());
        $this->resetAfterTest(true);
    }

    function test_hierarchy_get_item_descendants() {
        $competency = $this->competency;
        $nofwid = $this->nofwid;

        // create an object of the expected format
        $obj = new StdClass();
        $obj->fullname = $this->comp1->fullname;
        $obj->parentid = $this->comp1->parentid;
        $obj->path = $this->comp1->path;
        $obj->sortthread = $this->comp1->sortthread;
        $obj->id = $this->comp1->id;

        // should return an array of items
        $this->assertTrue((bool)is_array($competency->get_item_descendants($this->comp1->id)));
        // array elements should match an expected format
        $this->assertEquals($obj, current($competency->get_item_descendants($this->comp1->id)));
        // should return the item with the specified ID and all its descendants
        $this->assertEquals(3, count($competency->get_item_descendants($this->comp1->id)));
        // should still return itself if an item has no descendants
        $this->assertEquals(1, count($competency->get_item_descendants($this->comp2->id)));
        // should work the same for different frameworks
        $this->assertEquals(1, count($nofwid->get_item_descendants($this->comp3->id)));
        $this->resetAfterTest(true);
    }

    function test_hierarchy_get_hierarchy_item_adjacent_peer() {
        // if an adjacent peer exists, should return its id
        $this->assertEquals($this->comp4->id, $this->competency->get_hierarchy_item_adjacent_peer($this->comp2, HIERARCHY_ITEM_BELOW));
        // should return false if no adjacent peer exists in the direction specified
        $this->assertFalse((bool)$this->competency->get_hierarchy_item_adjacent_peer($this->comp2->id, HIERARCHY_ITEM_ABOVE));
        $this->assertFalse((bool)$this->competency->get_hierarchy_item_adjacent_peer($this->comp1->id, HIERARCHY_ITEM_ABOVE));
        // should return false if item is not valid
        $this->assertFalse((bool)$this->competency->get_hierarchy_item_adjacent_peer(null));
        $this->resetAfterTest(true);
    }

    function test_hierarchy_make_hierarchy_list() {
        global $DB;
        $competency = $this->competency;

        // standard list with default options
        $competency->make_hierarchy_list($list);
        // list with other options
        $competency->make_hierarchy_list($list2, null, true, true);

        // value should be fullname by default
        $this->assertEquals($this->comp1->fullname, $list[$this->comp1->id]);
        // value should be shortname if required
        $this->assertEquals($this->comp1->shortname, $list2[$this->comp1->id]);
        // should include all children unless specified
        $this->assertFalse((bool)array_search('Comp 1 (and all children)', $list));
        // should include all children row if required
        $this->assertEquals(implode(',', array($this->comp1->id, $this->comp2->id, $this->comp4->id)), array_search('Comp 1 (and all children)', $list2));

        // clear out the items
        $DB->delete_records('comp');
        // if no items exist should return false
        $competency->make_hierarchy_list($list3);
        // should return empty list if no items found
        $this->assertEquals(array(), $list3);
        $this->resetAfterTest(true);
    }

    function test_hierarchy_get_item_lineage() {
        $competency = $this->competency;
        $nofwid = $this->nofwid;

        // expected format of result
        $obj = new stdClass();
        $obj->fullname = $this->comp1->fullname;
        $obj->parentid = $this->comp1->parentid;
        $obj->depthlevel = $this->comp1->depthlevel;
        $obj->id = $this->comp1->id;

        // should return an array of items
        $this->assertTrue((bool)is_array($competency->get_item_lineage($this->comp2->id)));
        // array elements should match an expected format
        $this->assertEquals($obj, $competency->get_item_lineage($this->comp2->id)[$this->comp1->id]);
        // should return the item with the specified ID and all its parents
        $this->assertEquals(2, count($competency->get_item_lineage($this->comp2->id)));
        // should still return itself if an item has no parents
        $this->assertEquals(1, count($competency->get_item_lineage($this->comp1->id)));
        $this->assertEquals('Competency 1', current($competency->get_item_lineage($this->comp1->id))->fullname);
        // should work the same for different frameworks
        $this->assertEquals(1, count($nofwid->get_item_lineage($this->comp3->id)));
        // NOTE function ignores fwid of current hierarchy object
        // not sure that this is correct behaviour
        $this->assertEquals('F2 Competency 1', current($competency->get_item_lineage($this->comp3->id))->fullname);
        $this->resetAfterTest(true);
    }

    // skipped tests for the following display functions:
    // get_editing_button()
    // display_framework_selector()
    // display_add_item_button()
    // display_add_type_button()

    function test_hierarchy_hide_item() {
        global $DB;
        $competency = $this->competency;
        $competency->hide_item($this->comp1->id);
        $visible = $DB->get_field('comp', 'visible', array('id' => $this->comp1->id));
        // item should not be visible
        $this->assertEquals(0, $visible);
        // also test show item
        $competency->show_item($this->comp1->id);
        $visible = $DB->get_field('comp', 'visible', array('id' => $this->comp1->id));
        // item should be visible again
        $this->assertEquals(1, $visible);
        $this->resetAfterTest(true);
    }

    function test_hierarchy_hide_framework() {
        global $DB;
        $competency = $this->competency;
        $competency->hide_framework($this->frame1->id);
        $visible =  $DB->get_field('comp_framework', 'visible', array('id' => $this->frame1->id));
        // framework should not be visible
        $this->assertEquals(0, $visible);
        // also test show framework
        $competency->show_framework($this->frame1->id);
        $visible =  $DB->get_field('comp_framework', 'visible', array('id' => $this->frame1->id));
        // framework should be visible again
        $this->assertEquals(1, $visible);
        $this->resetAfterTest(true);
    }

    function test_hierarchy_framework_sortorder_offset() {
        $competency = $this->competency;
        $this->assertEquals(1002, $competency->get_framework_sortorder_offset());
        $this->resetAfterTest(true);
    }

    function test_hierarchy_move_framework() {
        global $DB;
        $competency = $this->competency;
        $f1_before =  $DB->get_field('comp_framework', 'sortorder', array('id' => $this->frame1->id));
        $f2_before =  $DB->get_field('comp_framework', 'sortorder', array('id' => $this->frame2->id));
        // a successful move should return true
        $this->assertTrue((bool)$competency->move_framework($this->frame2->id, true));
        $f1_after =  $DB->get_field('comp_framework', 'sortorder', array('id' => $this->frame1->id));
        $f2_after =  $DB->get_field('comp_framework', 'sortorder', array('id' => $this->frame2->id));
        // frameworks should have swapped sort orders
        $this->assertEquals($f1_before, $f2_after);
        $this->assertEquals($f2_before, $f1_after);
        // a failed move should return false
        $this->assertFalse((bool)$competency->move_framework($this->frame2->id, true));
        $this->resetAfterTest(true);
    }

    function test_hierarchy_delete_hierarchy_item() {
        global $DB;
        $competency = $this->competency;
        // function should return true
        $this->assertTrue((bool)$competency->delete_hierarchy_item($this->comp1->id, false));
        // the item should have be deleted
        $this->assertFalse((bool)$competency->get_item($this->comp1->id));
        // the item's children should also have been deleted
        $this->assertFalse((bool)$competency->get_items_by_parent($this->comp1->id));
        // custom field data for items and children should also be deleted
        $this->assertFalse((bool)$DB->get_records('comp_type_info_data', array('competencyid' => $this->comp2->id)));
        // non descendants in same framework should not be deleted
        $this->assertEquals(1, count($competency->get_items()));
        $this->resetAfterTest(true);
    }

    function test_hierarchy_delete_framework() {
        global $DB;
        $competency = $this->competency;
        // function should return null
        $this->assertTrue((bool)$competency->delete_framework(false));
        // items should have been deleted
        $this->assertFalse((bool)$competency->get_items());
        // types should still all exist because they are framework independant
        $this->assertEquals(3, count($competency->get_types()));
        // the framework should have been deleted
        $this->assertFalse((bool)$DB->get_records('comp_framework', array('id' => 1)));
        $this->resetAfterTest(true);
    }

    function test_hierarchy_delete_type() {
        global $DB;
        $competency = $this->competency;

        // delete all items to make deleting types possible
        $DB->delete_records('comp');

        $before = count($competency->get_types());
        // should return true if type is deleted
        $this->assertTrue((bool)$this->competency->delete_type($this->type2->id));
        $after = count($competency->get_types());
        // should have deleted the type
        $this->assertNotEquals($before, $after);
        $this->resetAfterTest(true);
    }

    function test_hierarchy_delete_type_metadata() {
        global $DB;
        $competency = $this->competency;

        // function should return null
        $this->assertTrue((bool)$competency->delete_type_metadata(2));
        // should have deleted all fields for the type
        $this->assertFalse((bool)$DB->get_records('comp_type_info_field', array('typeid' => 2)));

        $this->resetAfterTest(true);
    }

    function test_hierarchy_get_item_data() {
        $competency = $this->competency;

        // should return an array of info
        $this->assertTrue((bool)is_array($competency->get_item_data($this->comp1)));
        // if no params requested, should return default ones (includes aggregation method which
        // is specific to competencies)
        $this->assertEquals(6, count($competency->get_item_data($this->comp1)));
        // should return the correct number of fields requested
        $this->assertEquals(4, count($competency->get_item_data($this->comp1, array('sortthread', 'description'))));
        // should return the correct information based on fields requested
        $result = current($competency->get_item_data($this->comp1, array('description')));
        $this->assertEquals('Description', $result['title']);
        $this->assertEquals('Competency Description 1', $result['value']);
        $this->resetAfterTest(true);
    }

    function test_hierarchy_get_max_depth() {
        $competency = $this->competency;
        $nofwid = $this->nofwid;
        $nofwid->frameworkid = 999;
        // should return the correct maximum depth level if there are depth levels
        $this->assertEquals(2, $competency->get_max_depth());
        // should return null for framework with no depth levels
        $this->assertNull($nofwid->get_max_depth());
        $this->resetAfterTest(true);
    }

    function test_hierarchy_get_all_parents() {
        global $DB;

        // should return an array containing all items that have children
        // array should contain an item that has children
        $this->assertTrue((bool)array_key_exists($this->comp1->id, $this->competency->get_all_parents()));
        // array should not contain an item if it does not have children
        $this->assertFalse((bool)array_key_exists($this->comp2->id, $this->competency->get_all_parents()));
        // should work even if frameworkid not set
        $this->assertFalse((bool)array_key_exists($this->comp3->id, $this->nofwid->get_all_parents()));

        // clear out all items
        $DB->delete_records('comp');
        // should return an empty array if no parents found
        $this->assertEquals(array(), $this->competency->get_all_parents());
        $this->resetAfterTest(true);
    }

    function test_get_short_prefix(){
        $shortprefix = hierarchy::get_short_prefix('competency');
        $this->assertEquals('comp', $shortprefix);
        $this->resetAfterTest(true);
    }

    function test_reorder_hierarchy_item() {
        global $DB;
        $competency = $this->competency;

        $this->assertEquals('01.01', $DB->get_field('comp', 'sortthread', array('id' => $this->comp2->id)));
        $this->assertEquals('01.02', $DB->get_field('comp', 'sortthread', array('id' => $this->comp4->id)));
        $this->assertTrue((bool)$competency->reorder_hierarchy_item($this->comp2->id, $this->comp4->id));
        $this->assertEquals('01.02', $DB->get_field('comp', 'sortthread', array('id' => $this->comp2->id)));
        $this->assertEquals('01.01', $DB->get_field('comp', 'sortthread', array('id' => $this->comp4->id)));
        $this->resetAfterTest(true);
    }

    function test_get_extra_fields() {
        $competency = new competency();
        $position = new position();
        $organisation = new organisation();

        $this->assertEquals(array('evidencecount'), $competency->get_extrafields());
        $this->assertNull($position->get_extrafields());
        $this->assertNull($organisation->get_extrafields());

        $this->resetAfterTest(true);
    }


    function test_update_hierarchy_item() {
        $updatedcomp2 = clone($this->comp2);
        $updatedcomp2->fullname = 'UPDATED2';

        $this->assertEquals($updatedcomp2, $this->competency->update_hierarchy_item($updatedcomp2->id, $updatedcomp2));
        $this->assertEquals($updatedcomp2, $this->competency->get_item($this->comp2->id));

        $this->resetAfterTest(true);
    }

    function test_move_hierarchy_item() {
        $this->assertEquals($this->comp1->id, $this->competency->get_item($this->comp4->id)->parentid);
        $this->assertTrue((bool)$this->competency->move_hierarchy_item($this->comp4, $this->comp4->frameworkid, $this->comp5->id));
        $this->assertEquals($this->comp5->id, $this->competency->get_item($this->comp4->id)->parentid);

        $this->resetAfterTest(true);
    }

    function test_get_unclassified_items() {
        $competency = $this->competency;
        $unclassified = $competency->get_unclassified_items();

        $this->assertEquals(2, count($unclassified));
        $this->assertEquals($this->comp4, $unclassified[$this->comp4->id]);
        $this->assertEquals($this->comp5, $unclassified[$this->comp5->id]);

        $this->resetAfterTest(true);
    }

    function test_get_item_stats() {
        $info = $this->competency->get_item_stats($this->comp1->id);
        $this->assertEquals('Competency 1', $info['itemname']);
        $this->assertEquals(2, $info['children']);

        $info = $this->competency->get_item_stats($this->comp3->id);
        $this->assertEquals('F2 Competency 1', $info['itemname']);
        $this->assertEquals(0, $info['children']);

        $this->resetAfterTest(true);
    }

    function test_get_items_excluding_children() {
        $excluded = $this->competency->get_items_excluding_children(array($this->comp1->id, $this->comp2->id, $this->comp3->id, $this->comp4->id, $this->comp5->id));
        $this->assertEquals(array($this->comp1->id, $this->comp3->id, $this->comp5->id), $excluded);

        $this->resetAfterTest(true);
    }

    function test_is_child_of() {
        $this->assertTrue((bool)$this->competency->is_child_of($this->comp2, $this->comp1->id));
        $this->assertTrue((bool)$this->competency->is_child_of($this->comp4, array($this->comp1->id, $this->comp3->id, $this->comp5->id)));
        $this->assertFalse((bool)$this->competency->is_child_of($this->comp2, array($this->comp3->id, $this->comp2->id, $this->comp4->id, $this->comp5->id, 6)));
        $this->assertFalse((bool)$this->competency->is_child_of($this->comp4, $this->comp2->id));
        $this->assertFalse((bool)$this->competency->is_child_of($this->comp1, array(0, $this->comp1->id, $this->comp2->id, $this->comp3->id, $this->comp4->id, $this->comp5->id)));

        $this->resetAfterTest(true);
    }

    function test_get_parent_list() {
        $competency = $this->competency;

        $inctop = $competency->get_parent_list($competency->get_items(), array(), true);
        $noinctop = $competency->get_parent_list($competency->get_items(), array(), false);
        $expectedinctop =
                array('0'=>'Top',
                      $this->comp1->id => 'Competency 1',
                      $this->comp2->id => '&nbsp;&nbsp;&nbsp;&nbsp;Competency 2',
                      $this->comp4->id => '&nbsp;&nbsp;&nbsp;&nbsp;Competency 3',
                      $this->comp5->id => 'Competency 4');
        $this->assertEquals($expectedinctop, $inctop);
        $expectednoinctop =
                array($this->comp1->id => 'Competency 1',
                      $this->comp2->id => '&nbsp;&nbsp;&nbsp;&nbsp;Competency 2',
                      $this->comp4->id => '&nbsp;&nbsp;&nbsp;&nbsp;Competency 3',
                      $this->comp5->id => 'Competency 4');
        $this->assertEquals($expectednoinctop, $noinctop);

        $inctop = $competency->get_parent_list($competency->get_items(), $this->comp1->id, true);
        $noinctop = $competency->get_parent_list($competency->get_items(), $this->comp1->id, false);
        $this->assertEquals(array('0' => 'Top', $this->comp5->id => 'Competency 4'), $inctop);
        $this->assertEquals(array($this->comp5->id => 'Competency 4'), $noinctop);

        $this->resetAfterTest(true);
    }

    /* TODO
    function test_get_next_child_sortthread() {

    }

    function test_get_types_list() {

    }

    function test_move_sortthread() {

    }

    function test_swap_item_sortthreads(){

    }

    function test_increment_sortthread() {

    }

    function test_fix_sortthreads() {

    }

    function test_support_old_url_syntax() {

    }

    function test_add_hierarchy_item() {

    }

    function test_add_multiple_hierarchy_items() {

    }

     */

}
