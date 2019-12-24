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
 * @package totara
 * @subpackage hierarchy
 */

/*
 * Unit tests for move_hierarchy_item()
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}
global $CFG;
require_once($CFG->dirroot . '/totara/hierarchy/lib.php');
require_once($CFG->dirroot . '/totara/hierarchy/prefix/organisation/lib.php');

class movehierarchyitem_test extends advanced_testcase {
//TODO: add tests for moving hierarchy items between frameworks

    private $frame1, $frame2;
    private $org1, $org2, $org3, $org4, $org5, $org6, $org7, $org8, $org9, $org10, $org11, $org12, $org13, $org14, $org15, $org16;
    private $org;

    protected function tearDown() {
        $this->frame1 = null;
        $this->org1 = null;
        $this->org = null;
        parent::tearDown();
    }

    protected function setUp() {
        global $DB;
        parent::setup();

        $admin = get_admin();
        $userid = $admin->id;

        $this->frame1 = new stdClass();
        $this->frame1->fullname = 'Framework A';
        $this->frame1->shortname = 'FW A';
        $this->frame1->description = 'Org Framework Description A';
        $this->frame1->idnumber = 'FA';
        $this->frame1->visible = 1;
        $this->frame1->timecreated = 1234567890;
        $this->frame1->timemodified = 1234567890;
        $this->frame1->usermodified = $userid;
        $this->frame1->sortorder = 1;
        $this->frame1->hidecustomfields = 1;
        $this->frame1->id = $DB->insert_record('org_framework', $this->frame1);

        $this->frame2 = new stdClass();
        $this->frame2->fullname = 'Framework B';
        $this->frame2->shortname = 'FW B';
        $this->frame2->description = 'Org Framework Description B';
        $this->frame2->idnumber = 'FB';
        $this->frame2->visible = 1;
        $this->frame2->timecreated = 1234567890;
        $this->frame2->timemodified = 1234567890;
        $this->frame2->usermodified = $userid;
        $this->frame2->sortorder = 2;
        $this->frame2->hidecustomfields = 1;
        $this->frame2->id = $DB->insert_record('org_framework', $this->frame2);

        // create the competency object
        $this->org = new organisation();
        $this->org->frameworkid = $this->frame1->id;

        $neworg = new stdClass();
        $neworg->fullname = 'Organisation A';
        $neworg->shortname = 'Org A';
        $neworg->description = 'Org Description A';
        $neworg->idnumber = 'OA';
        $neworg->visible = 1;
        $neworg->usermodified = $userid;
        $neworg->typeid = 0;
        $this->org1 = $this->org->add_hierarchy_item($neworg, 0, $this->frame1->id, false, true, false);

        $neworg = new stdClass();
        $neworg->fullname = 'Organisation B';
        $neworg->shortname = 'Org B';
        $neworg->description = 'Org Description B';
        $neworg->idnumber = 'OB';
        $neworg->visible = 1;
        $neworg->usermodified = $userid;
        $neworg->typeid = 0;
        $this->org2 = $this->org->add_hierarchy_item($neworg, $this->org1->id, $this->frame1->id, false, true, false);

        $neworg = new stdClass();
        $neworg->fullname = 'Organisation C';
        $neworg->shortname = 'Org C';
        $neworg->description = 'Org Description C';
        $neworg->idnumber = 'OC';
        $neworg->visible = 1;
        $neworg->usermodified = $userid;
        $neworg->typeid = 0;
        $this->org3 = $this->org->add_hierarchy_item($neworg, $this->org2->id, $this->frame1->id, false, true, false);

        $neworg = new stdClass();
        $neworg->fullname = 'Organisation D';
        $neworg->shortname = 'Org D';
        $neworg->description = 'Org Description D';
        $neworg->idnumber = 'OD';
        $neworg->visible = 1;
        $neworg->usermodified = $userid;
        $neworg->typeid = 0;
        $this->org4 = $this->org->add_hierarchy_item($neworg, $this->org2->id, $this->frame1->id, false, true, false);

        $neworg = new stdClass();
        $neworg->fullname = 'Organisation E';
        $neworg->shortname = 'Org E';
        $neworg->description = 'Org Description E';
        $neworg->idnumber = 'OE';
        $neworg->visible = 1;
        $neworg->usermodified = $userid;
        $neworg->typeid = 0;
        $this->org5 = $this->org->add_hierarchy_item($neworg, 0, $this->frame1->id, false, true, false);

        $neworg = new stdClass();
        $neworg->fullname = 'Organisation F';
        $neworg->shortname = 'Org F';
        $neworg->description = 'Org Description F';
        $neworg->idnumber = 'OF';
        $neworg->visible = 1;
        $neworg->usermodified = $userid;
        $neworg->typeid = 0;
        $this->org6 = $this->org->add_hierarchy_item($neworg, $this->org5->id, $this->frame1->id, false, true, false);

        $neworg = new stdClass();
        $neworg->fullname = 'Organisation G';
        $neworg->shortname = 'Org G';
        $neworg->description = 'Org Description G';
        $neworg->idnumber = 'OG';
        $neworg->visible = 1;
        $neworg->usermodified = $userid;
        $neworg->typeid = 0;
        $this->org7 = $this->org->add_hierarchy_item($neworg, $this->org6->id, $this->frame1->id, false, true, false);

        $neworg = new stdClass();
        $neworg->fullname = 'Organisation H';
        $neworg->shortname = 'Org H';
        $neworg->description = 'Org Description H';
        $neworg->idnumber = 'OH';
        $neworg->visible = 1;
        $neworg->usermodified = $userid;
        $neworg->typeid = 0;
        $this->org8 = $this->org->add_hierarchy_item($neworg, $this->org6->id, $this->frame1->id, false, true, false);

        $neworg = new stdClass();
        $neworg->fullname = 'Organisation I';
        $neworg->shortname = 'Org I';
        $neworg->description = 'Org Description I';
        $neworg->idnumber = 'OI';
        $neworg->visible = 1;
        $neworg->usermodified = $userid;
        $neworg->typeid = 0;
        $this->org9 = $this->org->add_hierarchy_item($neworg, $this->org8->id, $this->frame1->id, false, true, false);

        $neworg = new stdClass();
        $neworg->fullname = 'Organisation J';
        $neworg->shortname = 'Org J';
        $neworg->description = 'Org Description J';
        $neworg->idnumber = 'OJ';
        $neworg->visible = 1;
        $neworg->usermodified = $userid;
        $neworg->typeid = 0;
        $this->org10 = $this->org->add_hierarchy_item($neworg, 0, $this->frame1->id, false, true, false);

        $neworg = new stdClass();
        $neworg->fullname = 'Organisation 1';
        $neworg->shortname = 'Org 1';
        $neworg->description = 'Org Description 1';
        $neworg->idnumber = 'O1';
        $neworg->visible = 1;
        $neworg->usermodified = $userid;
        $neworg->typeid = 0;
        $this->org11 = $this->org->add_hierarchy_item($neworg, 0, $this->frame2->id, false, true, false);

        $neworg = new stdClass();
        $neworg->fullname = 'Organisation 2';
        $neworg->shortname = 'Org 2';
        $neworg->description = 'Org Description 2';
        $neworg->idnumber = 'O2';
        $neworg->visible = 1;
        $neworg->usermodified = $userid;
        $neworg->typeid = 0;
        $this->org12 = $this->org->add_hierarchy_item($neworg, $this->org11->id, $this->frame2->id, false, true, false);

        $neworg = new stdClass();
        $neworg->fullname = 'Organisation 3';
        $neworg->shortname = 'Org 3';
        $neworg->description = 'Org Description 3';
        $neworg->idnumber = 'O3';
        $neworg->visible = 1;
        $neworg->usermodified = $userid;
        $neworg->typeid = 0;
        $this->org13 = $this->org->add_hierarchy_item($neworg, $this->org12->id, $this->frame2->id, false, true, false);

        $neworg = new stdClass();
        $neworg->fullname = 'Organisation 4';
        $neworg->shortname = 'Org 4';
        $neworg->description = 'Org Description 4';
        $neworg->idnumber = 'O4';
        $neworg->visible = 1;
        $neworg->usermodified = $userid;
        $neworg->typeid = 0;
        $this->org14 = $this->org->add_hierarchy_item($neworg, $this->org11->id, $this->frame2->id, false, true, false);

        $neworg = new stdClass();
        $neworg->fullname = 'Organisation 5';
        $neworg->shortname = 'Org 5';
        $neworg->description = 'Org Description 5';
        $neworg->idnumber = 'O5';
        $neworg->visible = 1;
        $neworg->usermodified = $userid;
        $neworg->typeid = 0;
        $this->org15 = $this->org->add_hierarchy_item($neworg, $this->org11->id, $this->frame2->id, false, true, false);

        $neworg = new stdClass();
        $neworg->fullname = 'Organisation 6';
        $neworg->shortname = 'Org 6';
        $neworg->description = 'Org Description 6';
        $neworg->idnumber = 'O6';
        $neworg->visible = 1;
        $neworg->usermodified = $userid;
        $neworg->typeid = 0;
        $this->org16 = $this->org->add_hierarchy_item($neworg, $this->org11->id, $this->frame2->id, false, true, false);
    }

/*
 * Testing hierarchy:
 *
 * FRAMEWORK 1:
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
 * FRAMEWORK 2:
 * 1
 * |_2
 * | |_3
 * |
 * |_4
 * |
 * |_5
 * |
 * |_6
 *
 */
    function test_new_parent_id() {
        global $DB;

        $moveorg = $DB->get_record('org', array('id' =>  $this->org6->id));
        $newparent = $this->org3->id;
        $before = $DB->get_records_menu('org', array('frameworkid' => $moveorg->frameworkid), 'sortthread', 'id,parentid');
        $this->assertTrue((bool)$this->org->move_hierarchy_item($moveorg, $moveorg->frameworkid, $newparent));
        $after = $DB->get_records_menu('org', array('frameworkid' => $moveorg->frameworkid), 'sortthread', 'id,parentid');
        // all that should have changed is item 6 should now have 3 as a parentid
        // others should stay the same
        $before[$moveorg->id] = $newparent;
        $this->assertEquals($before, $after);

        // now test moving to the top level
        $moveorg = $DB->get_record('org', array('id' => $this->org6->id));
        $newparent = 0;
        $before = $after;
        $this->assertTrue((bool)$this->org->move_hierarchy_item($moveorg, $moveorg->frameworkid, $newparent));
        $after = $DB->get_records_menu('org', array('frameworkid' => $moveorg->frameworkid), 'sortthread', 'id,parentid');
        $before[$moveorg->id] = $newparent;
        $this->assertEquals($before, $after);

        // now test moving from the top level
        $moveorg = $DB->get_record('org', array('id' =>  $this->org1->id));
        $newparent = $this->org6->id;
        $before = $after;
        $this->assertTrue((bool)$this->org->move_hierarchy_item($moveorg, $moveorg->frameworkid, $newparent));
        $after = $DB->get_records_menu('org', array('frameworkid' => $moveorg->frameworkid), 'sortthread', 'id,parentid');
        $before[$moveorg->id] = $newparent;
        $this->assertEquals($before, $after);

        $this->resetAfterTest(true);
    }

    function test_new_depthlevel() {
        global $DB;

        $moveorg = $DB->get_record('org', array('id' =>  $this->org6->id));
        $newparent = $this->org3->id;
        $before = $DB->get_records_menu('org', array('frameworkid' => $moveorg->frameworkid), 'sortthread', 'id,depthlevel');
        $this->assertTrue((bool)$this->org->move_hierarchy_item($moveorg, $moveorg->frameworkid, $newparent));
        $after = $DB->get_records_menu('org', array('frameworkid' => $moveorg->frameworkid), 'sortthread', 'id,depthlevel');
        // item and all it's children should have changed
        $before[$this->org6->id] = 4;
        $before[$this->org7->id] = 5;
        $before[$this->org8->id] = 5;
        $before[$this->org9->id] = 6;
        // everything else stays the same
        $this->assertEquals($before, $after);

        // now try attaching to top level
        $moveorg = $DB->get_record('org', array('id' => $this->org6->id));
        $newparent = 0;
        $before = $after;
        $this->assertTrue((bool)$this->org->move_hierarchy_item($moveorg, $moveorg->frameworkid, $newparent));
        $after = $DB->get_records_menu('org', array('frameworkid' => $moveorg->frameworkid), 'sortthread', 'id,depthlevel');
        // item and all it's children should have changed
        $before[$this->org6->id] = 1;
        $before[$this->org7->id] = 2;
        $before[$this->org8->id] = 2;
        $before[$this->org9->id] = 3;
        // everything else stays the same
        $this->assertEquals($before, $after);

        // now try moving from the top level
        $moveorg = $DB->get_record('org', array('id' =>  $this->org1->id));
        $newparent = $this->org10->id;
        $before = $after;
        $this->assertTrue((bool)$this->org->move_hierarchy_item($moveorg, $moveorg->frameworkid, $newparent));
        $after = $DB->get_records_menu('org', array('frameworkid' => $moveorg->frameworkid), 'sortthread', 'id,depthlevel');
        // item and all it's children should have changed
        $before[$this->org1->id] = 2;
        $before[$this->org2->id] = 3;
        $before[$this->org3->id] = 4;
        $before[$this->org4->id] = 4;
        // everything else stays the same
        $this->assertEquals($before, $after);

        $this->resetAfterTest(true);
    }

    function test_new_path() {
        global $DB;

        $moveorg = $DB->get_record('org', array('id' =>  $this->org6->id));
        $newparent = $this->org3->id;
        $before = $DB->get_records_menu('org', array('frameworkid' => $moveorg->frameworkid), 'sortthread', 'id,path');
        $this->assertTrue((bool)$this->org->move_hierarchy_item($moveorg, $moveorg->frameworkid, $newparent));
        $after = $DB->get_records_menu('org', array('frameworkid' => $moveorg->frameworkid), 'sortthread', 'id,path');
        // item and all it's children should have changed
        $before[$this->org6->id] = $this->org3->path . '/' . $this->org6->id;
        $before[$this->org7->id] = $before[$moveorg->id] . '/' . $this->org7->id;
        $before[$this->org8->id] = $before[$moveorg->id] . '/' . $this->org8->id;
        $before[$this->org9->id] = $before[$this->org8->id] . '/' . $this->org9->id;
        // everything else stays the same
        $this->assertEquals($before, $after);

        // now try attaching to top level
        $moveorg = $DB->get_record('org', array('id' => $this->org6->id));
        $newparent = 0;
        $before = $after;
        $this->assertTrue((bool)$this->org->move_hierarchy_item($moveorg, $moveorg->frameworkid, $newparent));
        $after = $DB->get_records_menu('org', array('frameworkid' => $moveorg->frameworkid), 'sortthread', 'id,path');
        // item and all it's children should have changed
        $before[$this->org6->id] = '/' . $this->org6->id;
        $before[$this->org7->id] = $before[$moveorg->id] . '/' . $this->org7->id;
        $before[$this->org8->id] = $before[$moveorg->id] . '/' . $this->org8->id;
        $before[$this->org9->id] = $before[$this->org8->id] . '/' . $this->org9->id;
        // everything else stays the same
        $this->assertEquals($before, $after);

        // now try moving from the top level
        $moveorg = $DB->get_record('org', array('id' =>  $this->org1->id));
        $newparent = $this->org10->id;
        $before = $after;
        $this->assertTrue((bool)$this->org->move_hierarchy_item($moveorg, $moveorg->frameworkid, $newparent));
        $after = $DB->get_records_menu('org', array('frameworkid' => $moveorg->frameworkid), 'sortthread', 'id,path');
        // item and all it's children should have changed
        $before[$this->org1->id] = $this->org10->path . '/' . $this->org1->id;
        $before[$this->org2->id] = $before[$moveorg->id] . '/' . $this->org2->id;
        $before[$this->org3->id] = $before[$this->org2->id] . '/' . $this->org3->id;
        $before[$this->org4->id] = $before[$this->org2->id] . '/' . $this->org4->id;
        $this->assertEquals($before, $after);
        // everything else stays the same
        $this->resetAfterTest(true);
    }

    function test_new_sortorder() {
        global $DB;

        $moveorg = $DB->get_record('org', array('id' =>  $this->org6->id));
        $newparent = $this->org3->id;

        $before = $DB->get_records_menu('org', array('frameworkid' => $moveorg->frameworkid), 'sortthread', 'id,sortthread');
        $this->assertTrue((bool)$this->org->move_hierarchy_item($moveorg, $moveorg->frameworkid, $newparent));
        $after = $DB->get_records_menu('org', array('frameworkid' => $moveorg->frameworkid), 'sortthread', 'id,sortthread');
        // item and all it's children should have changed
        $before[$this->org6->id] = '01.01.01.01';
        $before[$this->org7->id] = '01.01.01.01.01';
        $before[$this->org8->id] = '01.01.01.01.02';
        $before[$this->org9->id] = '01.01.01.01.02.01';
        // displaced items and everything else stays the same
        $this->assertEquals($before, $after);


        // now try attaching to top level
        $moveorg = $DB->get_record('org', array('id' =>  $this->org6->id));
        $newparent = 0;

        $before = $after;
        $this->assertTrue((bool)$this->org->move_hierarchy_item($moveorg, $moveorg->frameworkid, $newparent));
        $after = $DB->get_records_menu('org', array('frameworkid' => $moveorg->frameworkid), 'sortthread', 'id,sortthread');
        // item and all it's children should have changed
        $before[$this->org6->id] = '04';
        $before[$this->org7->id] = '04.01';
        $before[$this->org8->id] = '04.02';
        $before[$this->org9->id] = '04.02.01';
        // displaced items and everything else stays the same
        $this->assertEquals($before, $after);

        // now try moving from the top level
        $moveorg = $DB->get_record('org', array('id' =>  $this->org1->id));
        $newparent = $this->org10->id;
        $before = $after;
        $this->assertTrue((bool)$this->org->move_hierarchy_item($moveorg, $moveorg->frameworkid, $newparent));
        $after = $DB->get_records_menu('org', array('frameworkid' => $moveorg->frameworkid), 'sortthread', 'id,sortthread');
        // item and all it's children should have changed
        $before[$this->org1->id] = '03.01';
        $before[$this->org2->id] = '03.01.01';
        $before[$this->org3->id] = '03.01.01.01';
        $before[$this->org4->id] = '03.01.01.02';
        // displayed items and everything else stays the same
        $this->assertEquals($before, $after);
        $this->resetAfterTest(true);
    }

    function test_moving_subtree() {
        global $DB;

        $moveorg = $DB->get_record('org', array('id' => $this->org12->id));
        $newparent = $this->org14->id;

        $before = $DB->get_records_menu('org', array('frameworkid' => $moveorg->frameworkid), 'sortthread', 'id,sortthread');
        $this->assertTrue((bool)$this->org->move_hierarchy_item($moveorg, $moveorg->frameworkid, $newparent));
        $after = $DB->get_records_menu('org', array('frameworkid' => $moveorg->frameworkid), 'sortthread', 'id,sortthread');

        // item and all it's children should have changed
        $before[$this->org12->id] = '01.02.01';
        $before[$this->org13->id] = '01.02.01.01';
        // displaced items and everything else stays the same
        $this->assertEquals($before, $after);
        $this->resetAfterTest(true);
    }

    // these moves should fail and nothing should change
    function test_bad_moves() {
        global $DB;

        // you shouldn't be able to move an item into it's own child
        $moveorg = $DB->get_record('org', array('id' => $this->org12->id));
        $newparent = $this->org13->id;

        $before = $DB->get_records_menu('org', array('frameworkid' => $moveorg->frameworkid), 'sortthread', 'id,sortthread');
        // this should fail
        $this->assertFalse((bool)$this->org->move_hierarchy_item($moveorg, $moveorg->frameworkid, $newparent));
        $after = $DB->get_records_menu('org', array('frameworkid' => $moveorg->frameworkid), 'sortthread', 'id,sortthread');
        // everything stays the same
        $this->assertEquals($before, $after);


        // you shouldn't be able move to parent that doesn't exist
        $newparent = 999;

        $before = $DB->get_records_menu('org', array('frameworkid' => $moveorg->frameworkid), 'sortthread', 'id,sortthread');
        // this should fail
        $this->assertFalse((bool)$this->org->move_hierarchy_item($moveorg, $moveorg->frameworkid, $newparent));
        $after = $DB->get_records_menu('org', array('frameworkid' => $moveorg->frameworkid), 'sortthread', 'id,sortthread');
        // everything stays the same
        $this->assertEquals($before, $after);


        // item must be an object
        $item = 1234;
        $newparent = 0;

        $before = $DB->get_records_menu('org', array('frameworkid' => $moveorg->frameworkid), 'sortthread', 'id,sortthread');
        // this should fail
        $this->assertFalse((bool)$this->org->move_hierarchy_item($item, $item, $newparent));
        $after = $DB->get_records_menu('org', array('frameworkid' => $moveorg->frameworkid), 'sortthread', 'id,sortthread');
        // everything stays the same
        $this->assertEquals($before, $after);
        $this->resetAfterTest(true);
    }
}
