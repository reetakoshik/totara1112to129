<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @package totara_hierarchy
 */

/*
 * Unit tests for update_hierarchy_item()
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}
global $CFG;
require_once($CFG->dirroot . '/totara/hierarchy/lib.php');
require_once($CFG->dirroot . '/totara/hierarchy/prefix/organisation/lib.php');

class totara_hierarchy_updateitem_testcase extends advanced_testcase {

    private $frame1, $frame2;
    private $org1, $org2, $org3, $org4;
    private $org;

    protected function tearDown() {
        $this->frame1 = $this->frame2 = null;
        $this->org1 = $this->org2 = $this->org3 = $this->org4 = null;
        $this->org = null;

        parent::tearDown();
    }

    protected function setUp() {
        global $DB;
        parent::setup();

        $admin = get_admin();
        $userid = $admin->id;

        $this->resetAfterTest(true);

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

        // Create the competency object.
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
        $this->org4 = $this->org->add_hierarchy_item($neworg, 0, $this->frame1->id, false, true, false);
    }

    public function test_new_parent_id() {
        global $DB;

        $org = $DB->get_record('org', array('id' => $this->org4->id));
        $updatedorg = $org;
        $updatedorg->parentid = $this->org1->id;

        $before = $DB->get_records_menu('org', array('frameworkid' => $org->frameworkid), 'sortthread', 'id,parentid');
        $this->assertTrue((bool)$this->org->update_hierarchy_item($org->id, $updatedorg));
        $after = $DB->get_records_menu('org', array('frameworkid' => $org->frameworkid), 'sortthread', 'id,parentid');

        // All that should have changed is item 1 should now have a new parentid.
        $before[$org->id] = $this->org1->id;
        $this->assertEquals($before, $after);

        $this->resetAfterTest(true);
    }

    /**
     * @expectedException moodle_exception
     */
    public function test_invalid_new_parent_id() {
        global $DB;

        $org = $DB->get_record('org', array('id' => $this->org3->id));
        $updatedorg = $org;
        $updatedorg->parentid = '9999'; // Invalid id

        $before = $DB->get_records_menu('org', array('frameworkid' => $org->frameworkid), 'sortthread', 'id,parentid');
        $this->assertFalse((bool)$this->org->update_hierarchy_item($org->id, $updatedorg));
        $after = $DB->get_records_menu('org', array('frameworkid' => $org->frameworkid), 'sortthread', 'id,parentid');

        // Nothing should be updated.
        $this->assertEquals($before, $after);
    }


    public function test_new_framework_id() {
        global $DB;

        $org = $DB->get_record('org', array('id' => $this->org4->id));
        $updatedorg = $org;
        $updatedorg->frameworkid = $this->frame2->id;

        $before = $DB->get_record('org', array('id' => $org->id));
        $this->assertTrue((bool)$this->org->update_hierarchy_item($org->id, $updatedorg));
        $after = $DB->get_record('org', array('id' => $org->id));

        $before->frameworkid = $this->frame2->id;

        // Nothing should be updated.
        $this->assertEquals($before->shortname, $after->shortname);
        $this->assertEquals($before->idnumber, $after->idnumber);
        $this->assertEquals($before->id, $after->id);
        $this->assertEquals($this->frame2->id, $after->frameworkid);
    }

    /**
     * @expectedException moodle_exception
     */
    public function test_invalid_new_framework_id() {
        global $DB;

        $org = $DB->get_record('org', array('id' => $this->org4->id));
        $updatedorg = $org;
        $updatedorg->frameworkid = '9999'; // Invalid id

        $before = $DB->get_record('org', array('id' => $org->id));
        $this->assertFalse((bool)$this->org->update_hierarchy_item($org->id, $updatedorg, true, true, false));
        $after = $DB->get_record('org', array('id' => $org->id));

        // Nothing should be updated.
        $this->assertEquals($before, $after);
    }

    public function test_moving_to_top_of_hierarchy() {
        global $DB;

        $org = $DB->get_record('org', array('id' => $this->org3->id));
        $updatedorg = $org;
        $updatedorg->parentid = 0; // Move to top level of hierarchy.

        $before = $DB->get_record('org', array('id' => $org->id));
        $this->assertTrue((bool)$this->org->update_hierarchy_item($org->id, $updatedorg, true, true, false));
        $after = $DB->get_record('org', array('id' => $org->id));

        // Check that the parent was successfully updated.
        $before->parentid = '0';
        $before->depthlevel = '1';
        $before->path = '/'.$org->id;
        $before->sortthread = '03'; // Third item at top level.
        $this->assertEquals($before, $after);

        $org2 = $DB->get_record('org', array('id' => $this->org2->id));
        $updatedorg2 = $org2;
        $updatedorg2->parentid = null;

        $before = $DB->get_record('org', array('id' => $org2->id));
        $this->assertTrue((bool)$this->org->update_hierarchy_item($org2->id, $updatedorg2, true, true, false));
        $after = $DB->get_record('org', array('id' => $org2->id));

        // Check that the parent was successfully updated.
        $before->parentid = '0';
        $before->depthlevel = '1';
        $before->path = '/'.$org2->id;
        $before->sortthread = '04'; // Third item at top level.
        $this->assertEquals($before, $after);
    }
}
