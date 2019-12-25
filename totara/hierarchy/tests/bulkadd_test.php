<?php
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
 * @package totara
 * @subpackage hierarchy
 */

/**
 * Unit tests for add_multiple_hierarchy_items()
 *
 * Testing hierarchy:
 *
 * A
 * |_B
 * | |_C
 * | |_D
 * E
 * |_F
 * | |_G
 * | |_H
 * |   |_I
 * J
 *
 * We add two items in several places:
 * 1. To the root level
 * 2. Attached in the middle of hierarchy (to F)
 * 3. Attached to tip of hierarchy (to D)
 * 4. Attached to the end of the hierarchy (to J)
 *
 * @author Simon Coggins <simonc@catalyst.net.nz>
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

global $CFG;
require_once($CFG->dirroot . '/totara/hierarchy/lib.php');
require_once($CFG->dirroot . '/totara/hierarchy/prefix/organisation/lib.php');


class bulkaddhierarchyitems_test extends advanced_testcase {

    private $orgs = array();

    protected function tearDown() {
        $this->orgs = null;
        parent::tearDown();
    }

    protected function setUp() {
        parent::setup();

        $user = get_admin();

        $org = new organisation();

        $neworg = new stdClass();
        $neworg->fullname = 'Organisation A';
        $neworg->shortname = 'Org A';
        $neworg->description = 'Org Description A';
        $neworg->idnumber = 'OA';
        $neworg->typeid = 0;
        $neworg->visible = 1;
        $neworg->usermodified = $user->id;
        $this->orgs[1] = $org->add_hierarchy_item($neworg, 0, 1, false);
        $this->assertTrue((bool)$this->orgs[1]);

        $neworg = new stdClass();
        $neworg->fullname = 'Organisation B';
        $neworg->shortname = 'Org B';
        $neworg->description = 'Org Description B';
        $neworg->idnumber = 'OB';
        $neworg->typeid = 0;
        $neworg->visible = 1;
        $neworg->usermodified = $user->id;
        $this->orgs[2] = $org->add_hierarchy_item($neworg, $this->orgs[1]->id, 1, false);
        $this->assertTrue((bool)$this->orgs[2]);

        $neworg = new stdClass();
        $neworg->fullname = 'Organisation C';
        $neworg->shortname = 'Org C';
        $neworg->description = 'Org Description C';
        $neworg->idnumber = 'OC';
        $neworg->typeid = 0;
        $neworg->visible = 1;
        $neworg->usermodified = $user->id;
        $this->orgs[3] = $org->add_hierarchy_item($neworg, $this->orgs[2]->id, 1, false);
        $this->assertTrue((bool)$this->orgs[3]);

        $neworg = new stdClass();
        $neworg->fullname = 'Organisation D';
        $neworg->shortname = 'Org D';
        $neworg->description = 'Org Description D';
        $neworg->idnumber = 'OD';
        $neworg->typeid = 0;
        $neworg->visible = 1;
        $neworg->usermodified = $user->id;
        $this->orgs[4] = $org->add_hierarchy_item($neworg, $this->orgs[2]->id, 1, false);
        $this->assertTrue((bool)$this->orgs[4]);

        $neworg = new stdClass();
        $neworg->fullname = 'Organisation E';
        $neworg->shortname = 'Org E';
        $neworg->description = 'Org Description E';
        $neworg->idnumber = 'OE';
        $neworg->typeid = 0;
        $neworg->visible = 1;
        $neworg->usermodified = $user->id;
        $this->orgs[5] = $org->add_hierarchy_item($neworg, 0, 1, false);
        $this->assertTrue((bool)$this->orgs[5]);

        $neworg = new stdClass();
        $neworg->fullname = 'Organisation F';
        $neworg->shortname = 'Org F';
        $neworg->description = 'Org Description F';
        $neworg->idnumber = 'OF';
        $neworg->typeid = 0;
        $neworg->visible = 1;
        $neworg->usermodified = $user->id;
        $this->orgs[6] = $org->add_hierarchy_item($neworg, $this->orgs[5]->id, 1, false);
        $this->assertTrue((bool)$this->orgs[6]);

        $neworg = new stdClass();
        $neworg->fullname = 'Organisation G';
        $neworg->shortname = 'Org G';
        $neworg->description = 'Org Description G';
        $neworg->idnumber = 'OG';
        $neworg->typeid = 0;
        $neworg->visible = 1;
        $neworg->usermodified = $user->id;
        $this->orgs[7] = $org->add_hierarchy_item($neworg, $this->orgs[6]->id, 1, false);
        $this->assertTrue((bool)$this->orgs[7]);

        $neworg = new stdClass();
        $neworg->fullname = 'Organisation H';
        $neworg->shortname = 'Org H';
        $neworg->description = 'Org Description H';
        $neworg->idnumber = 'OH';
        $neworg->typeid = 0;
        $neworg->visible = 1;
        $neworg->usermodified = $user->id;
        $this->orgs[8] = $org->add_hierarchy_item($neworg, $this->orgs[6]->id, 1, false);
        $this->assertTrue((bool)$this->orgs[8]);

        $neworg = new stdClass();
        $neworg->fullname = 'Organisation I';
        $neworg->shortname = 'Org I';
        $neworg->description = 'Org Description I';
        $neworg->idnumber = 'OI';
        $neworg->typeid = 0;
        $neworg->visible = 1;
        $neworg->usermodified = $user->id;
        $this->orgs[9] = $org->add_hierarchy_item($neworg, $this->orgs[8]->id, 1, false);
        $this->assertTrue((bool)$this->orgs[9]);

        $neworg = new stdClass();
        $neworg->fullname = 'Organisation J';
        $neworg->shortname = 'Org J';
        $neworg->description = 'Org Description J';
        $neworg->idnumber = 'OJ';
        $neworg->typeid = 0;
        $neworg->visible = 1;
        $neworg->usermodified = $user->id;
        $this->orgs[10] = $org->add_hierarchy_item($neworg, 0, 1, false);
        $this->assertTrue((bool)$this->orgs[10]);
    }


    // test adding to the top level of a hierarchy
    function test_add_multiple_hierarchy_items_to_root() {
        global $DB;

        $org = new organisation();

        // test items to insert
        $item1 = new stdClass();
        $item1->fullname = 'Item 1';
        $item1->shortname = 'I1';
        $item1->description= 'Description Item 1';
        $item1->typeid = 0;
        $item2 = new stdClass();
        $item2->fullname = 'Item 2';
        $item2->shortname = 'I2';
        $item2->description= 'Description Item 2';
        $item2->typeid = 1;

        $items = array($item1, $item2);
        $parent = 0;

        // check items are added in the right place
        $before = $DB->get_records_menu('org', null, 'sortthread', 'id,sortthread');
        $this->assertTrue((bool)$org->add_multiple_hierarchy_items($parent, $items, 1, false));
        $after = $DB->get_records_menu('org', null, 'sortthread', 'id,sortthread');

        // new items should have been added to the end
        ksort($after);
        $item2value = end($after);
        $item2id = key($after);
        $this->assertEquals('05', $item2value);
        unset($after[$item2id]);
        $item1value = end($after);
        $item1id = key($after);
        $this->assertEquals('04', $item1value);
        unset($after[$item1id]);
        // all others should stay the same
        $this->assertEquals($before, $after);

        // get the items
        $this->assertTrue((bool)$item1 = $DB->get_record('org', array('id' => $item1id)));
        $this->assertTrue((bool)$item2 = $DB->get_record('org', array('id' => $item2id)));

        // check depthlevel set right
        $this->assertEquals(1, $item1->depthlevel);
        $this->assertEquals(1, $item2->depthlevel);

        // check path set right
        $this->assertEquals('/' . $item1id, $item1->path);
        $this->assertEquals('/' . $item2id, $item2->path);

        // check parentid set right
        $this->assertEquals(0, $item1->parentid);
        $this->assertEquals(0, $item2->parentid);

        // check the typeid set right
        $this->assertEquals(0, $item1->typeid);
        $this->assertEquals(1, $item2->typeid);

        $this->resetAfterTest(true);
    }

    // test adding to an item in the middle of a hierarchy
    function test_add_multiple_hierarchy_items_to_branch() {
        global $DB;

        $org = new organisation();

        // test items to insert
        $item1 = new stdClass();
        $item1->fullname = 'Item 1';
        $item1->shortname = 'I1';
        $item1->description= 'Description Item 1';
        $item1->typeid = 0;
        $item2 = new stdClass();
        $item2->fullname = 'Item 2';
        $item2->shortname = 'I2';
        $item2->description= 'Description Item 2';
        $item2->typeid = 1;

        $items = array($item1, $item2);

        $parent = $this->orgs[6];

        // check items are added in the right place
        $before = $DB->get_records_menu('org', null, 'sortthread', 'id,sortthread');
        $this->assertTrue((bool)$org->add_multiple_hierarchy_items($parent->id, $items, 1, false));
        $after = $DB->get_records_menu('org', null, 'sortthread', 'id,sortthread');

        // new items should have been inserted after parent's last child
        ksort($after);
        $item2value = end($after);
        $item2id = key($after);
        $this->assertEquals('02.01.04', $item2value);
        unset($after[$item2id]);
        $item1value = end($after);
        $item1id = key($after);
        $this->assertEquals('02.01.03', $item1value);
        unset($after[$item1id]);
        // all others should have stayed the same
        $this->assertEquals($before, $after);

        // get the items
        $this->assertTrue((bool)$item1 = $DB->get_record('org', array('id' => $item1id)));
        $this->assertTrue((bool)$item2 = $DB->get_record('org', array('id' => $item2id)));

        // check depthlevel set right
        $this->assertEquals(3, $item1->depthlevel);
        $this->assertEquals(3, $item2->depthlevel);

        // check path set right
        $this->assertEquals($parent->path . '/' . $item1id, $item1->path);
        $this->assertEquals($parent->path . '/' . $item2id, $item2->path);

        // check parentid set right
        $this->assertEquals($parent->id, $item1->parentid);
        $this->assertEquals($parent->id, $item2->parentid);

        // check the typeid set right
        $this->assertEquals(0, $item1->typeid);
        $this->assertEquals(1, $item2->typeid);

        $this->resetAfterTest(true);
    }

    // test adding to an item at the tip of a hierarchy
    function test_add_multiple_hierarchy_items_to_leaf() {
        global $DB;

        $org = new organisation();

        // test items to insert
        $item1 = new stdClass();
        $item1->fullname = 'Item 1';
        $item1->shortname = 'I1';
        $item1->description= 'Description Item 1';
        $item1->typeid = 0;
        $item2 = new stdClass();
        $item2->fullname = 'Item 2';
        $item2->shortname = 'I2';
        $item2->description= 'Description Item 2';
        $item2->typeid = 1;

        $items = array($item1, $item2);
        $parent = $this->orgs[4];

        // check items are added in the right place
        $before = $DB->get_records_menu('org', null, 'sortthread', 'id,sortthread');
        $this->assertTrue((bool)$org->add_multiple_hierarchy_items($parent->id, $items, 1, false));
        $after = $DB->get_records_menu('org', null, 'sortthread', 'id,sortthread');

        // new items should have been inserted directly after parent
        ksort($after);
        $item2value = end($after);
        $item2id = key($after);
        $this->assertEquals('01.01.02.02', $item2value);
        unset($after[$item2id]);
        $item1value = end($after);
        $item1id = key($after);
        $this->assertEquals('01.01.02.01', $item1value);
        unset($after[$item1id]);
        // all others should stay the same
        $this->assertEquals($before, $after);

        // get the items
        $this->assertTrue((bool)$item1 = $DB->get_record('org', array('id' => $item1id)));
        $this->assertTrue((bool)$item2 = $DB->get_record('org', array('id' => $item2id)));

        // check depthlevel set right
        $this->assertEquals(4, $item1->depthlevel);
        $this->assertEquals(4, $item2->depthlevel);

        // check path set right
        $this->assertEquals($parent->path . '/' . $item1id, $item1->path);
        $this->assertEquals($parent->path . '/' . $item2id, $item2->path);

        // check parentid set right
        $this->assertEquals($parent->id, $item1->parentid);
        $this->assertEquals($parent->id, $item2->parentid);

        // check the typeid set right
        $this->assertEquals(0, $item1->typeid);
        $this->assertEquals(1, $item2->typeid);

        $this->resetAfterTest(true);
    }


    // test adding to the end of a hierarchy
    function test_add_multiple_hierarchy_items_to_end() {
        global $DB;

        $org = new organisation();

        // test items to insert
        $item1 = new stdClass();
        $item1->fullname = 'Item 1';
        $item1->shortname = 'I1';
        $item1->description= 'Description Item 1';
        $item1->typeid = 0;
        $item2 = new stdClass();
        $item2->fullname = 'Item 2';
        $item2->shortname = 'I2';
        $item2->description= 'Description Item 2';
        $item2->typeid = 1;

        $items = array($item1, $item2);
        $parent = $this->orgs[10];

        // check items are added in the right place
        $before = $DB->get_records_menu('org', null, 'sortthread', 'id,sortthread');
        $this->assertTrue((bool)$org->add_multiple_hierarchy_items($parent->id, $items, 1, false));
        $after = $DB->get_records_menu('org', null, 'sortthread', 'id,sortthread');

        // new items should have been added to the end
        // all others should stay the same
        ksort($after);
        $item2value = end($after);
        $item2id = key($after);
        $this->assertEquals('03.02', $item2value);
        unset($after[$item2id]);
        $item1value = end($after);
        $item1id = key($after);
        $this->assertEquals('03.01', $item1value);
        unset($after[$item1id]);
        // all others should stay the same
        $this->assertEquals($before, $after);

        // get the items
        $this->assertTrue((bool)$item1 = $DB->get_record('org', array('id' => $item1id)));
        $this->assertTrue((bool)$item2 = $DB->get_record('org', array('id' => $item2id)));

        // check depthlevel set right
        $this->assertEquals(2, $item1->depthlevel);
        $this->assertEquals(2, $item2->depthlevel);

        // check path set right
        $this->assertEquals($parent->path . '/' . $item1id, $item1->path);
        $this->assertEquals($parent->path . '/' . $item2id, $item2->path);

        // check parentid set right
        $this->assertEquals($parent->id, $item1->parentid);
        $this->assertEquals($parent->id, $item2->parentid);

        // check the typeid set right
        $this->assertEquals(0, $item1->typeid);
        $this->assertEquals(1, $item2->typeid);

        $this->resetAfterTest(true);
    }

    /**
     * Test the bulk add hierarchy tree functionality.
     */
    public function test_add_multiple_hierarchy_items_at_once() {
        global $DB;

        // Simulate data from the bulk add form.
        $formdata = new stdClass();
        $formdata->prefix = 'organisation';
        $formdata->frameworkid = 1;
        $formdata->typeid = 0;
        $formdata->parentid = 0; // Top level.
        $formdata->itemnames = <<<EOD
Item 1
Item 2
  Item 2a
  Item 2b
    Item 2bi
  Item 2c
Item 3
  Item 3a
Item 4
  Item 4a
    Item 4ai
       Item 4ai1
EOD;
        $error = '';
        $items = hierarchy::construct_items_to_add($formdata, $error);
        // Should be constructed without issues.
        $this->assertTrue((bool)$items);
        $this->assertEmpty($error);

        $before = $DB->get_records_menu('org', null, 'sortthread', 'id,sortthread');
        $hierarchy = hierarchy::load_hierarchy('organisation');
        $newids = $hierarchy->add_multiple_hierarchy_items(0, $items, 1);
        $after = $DB->get_records_menu('org', null, 'sortthread', 'id,sortthread');

        // Should be 12 new orgs.
        $this->assertEquals(12, (count($after)-count($before)));
        // Should be 12 ids.
        $this->assertCount(12, $newids);

        $item2 = $DB->get_record('org', array('fullname' => 'Item 2'));
        // Item 2 should be top level.
        $this->assertEquals(0, $item2->parentid);
        // Item 2b should be child of item 2.
        $item2b = $DB->get_record('org', array('fullname' => 'Item 2b'));
        $this->assertEquals($item2->id, $item2b->parentid);

        $this->resetAfterTest(true);
    }

    /*
     * Data provider for method below.
     */
    public function multiple_hierarchy_data_provider() {
        return array(
            // Item with no parent should give an error.
            array(
                '  Bad item',
                false,
                get_string('bulkaddparenterror', 'totara_hierarchy', 'Bad item')
            ),
            // Two items with blank line between should be okay.
            array(
                'Item 1

Item 2',
                true,
                ''
            ),
            // Complex but valid tree should be okay.
            array(
                'Item 1

  Item 2
    Item 2a
    Item 2b
      Item 2bi
    Item 2c
  Item 3
    Item 3a
      Item 3ai
        Item x
    Item 3b

Item 4
                ',
                true,
                ''
            ),
            // Incorrect structure when parent level did exist should still fail.
            array(
                'Item 1
  Item 2
    Item 3
      Item 4
Item 5
      Bad item',
                false,
                get_string('bulkaddparenterror', 'totara_hierarchy', 'Bad item')
            ),
            // Another invalid structure which should fail.
            array(
                'Item 1
  Item 2
    Item 3
        Item 4',
                false,
                get_string('bulkaddparenterror', 'totara_hierarchy', 'Item 4')
            ),
            // Empty items string should give an error.
            array(
                '',
                false,
                get_string('bulkaddnoitems', 'totara_hierarchy')
            )
        );
    }

    /**
     * Test a range of inputs to ensure validation behaves as expected.
     *
     * @dataProvider multiple_hierarchy_data_provider
     */
    public function test_add_multiple_hierarchy_items_validation($itemnames, $expectedreturn, $expectederror) {
        global $DB;

        // Simulate data from the bulk add form.
        $formdata = new stdClass();
        $formdata->prefix = 'organisation';
        $formdata->frameworkid = 1;
        $formdata->typeid = 0;
        $formdata->parentid = 0; // Top level.
        $formdata->itemnames = $itemnames;
        $error = '';
        $items = hierarchy::construct_items_to_add($formdata, $error);
        // Should be constructed without issues.
        $this->assertEquals($expectedreturn, (bool)$items);
        $this->assertEquals($expectederror, $error);

        $this->resetAfterTest(true);
    }
}
