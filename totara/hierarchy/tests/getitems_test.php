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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @package totara
 * @subpackage hierarchy
 */

/*
 * Unit tests for get_items_excluding_children()
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}
global $CFG;
require_once($CFG->dirroot . '/totara/hierarchy/lib.php');
require_once($CFG->dirroot . '/totara/hierarchy/prefix/organisation/lib.php');

class totara_hierarchy_getitems_testcase extends advanced_testcase {

    private $orgs = array();

    protected function tearDown() {
        $this->orgs = null;
        parent::tearDown();
    }

    protected function setUp() {
        global $DB;

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

/*
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
 */
    function test_cases_with_no_children() {
        $org = new organisation();

        // cases where no items are the children of any others
        $testcases = array(
            array($this->orgs[2]->id, $this->orgs[5]->id, $this->orgs[10]->id),
            array($this->orgs[2]->id),
            array($this->orgs[1]->id, $this->orgs[9]->id),
            array($this->orgs[4]->id, $this->orgs[8]->id),
        );

        foreach ($testcases as $testcase) {
            // should match exactly without change
            $output = $org->get_items_excluding_children($testcase);
            $this->assertEquals($testcase, $output);
        }
    }

    function test_cases_with_duplicates() {
        $org = new organisation();

        // cases where there are duplicates
        $testcases = array(
            array($this->orgs[2]->id, $this->orgs[5]->id, $this->orgs[10]->id, $this->orgs[5]->id),
            array($this->orgs[2]->id, $this->orgs[2]->id),
            array($this->orgs[1]->id, $this->orgs[9]->id, $this->orgs[1]->id, $this->orgs[9]->id),
            array($this->orgs[4]->id, $this->orgs[8]->id, $this->orgs[4]->id),
        );

        foreach ($testcases as $testcase) {
            // should match the unique elements of the array
            $output = $org->get_items_excluding_children($testcase);
            $this->assertEquals(array_unique($testcase), $output);
        }
    }


    function test_cases_with_children() {
        $org = new organisation();

        // cases where no items are the children of any others
        $testcases = array(
            array('before' => array($this->orgs[1]->id, $this->orgs[3]->id, $this->orgs[5]->id, $this->orgs[7]->id, $this->orgs[9]->id),
                   'after' => array($this->orgs[1]->id, $this->orgs[5]->id)),
            array('before' => array($this->orgs[1]->id, $this->orgs[2]->id, $this->orgs[3]->id, $this->orgs[4]->id, $this->orgs[5]->id, $this->orgs[6]->id, $this->orgs[7]->id, $this->orgs[8]->id, $this->orgs[9]->id, $this->orgs[10]->id),
                   'after' => array($this->orgs[1]->id, $this->orgs[5]->id, $this->orgs[10]->id)),
            array('before' => array($this->orgs[2]->id, $this->orgs[4]->id, $this->orgs[6]->id, $this->orgs[9]->id),
                   'after' => array($this->orgs[2]->id, $this->orgs[6]->id)),
            array('before' => array($this->orgs[8]->id, $this->orgs[9]->id),
                   'after' => array($this->orgs[8]->id)),
        );

        foreach ($testcases as $testcase) {
            // should match the 'after' state
            $output = $org->get_items_excluding_children($testcase['before']);
            $this->assertEquals($testcase['after'], $output);
        }
    }

    function test_cases_with_duplicates_and_children() {
        $org = new organisation();

        // cases where no items are the children of any others
        $testcases = array(
            array('before' => array($this->orgs[1]->id, $this->orgs[3]->id, $this->orgs[5]->id, $this->orgs[1]->id, $this->orgs[7]->id, $this->orgs[9]->id, $this->orgs[1]->id),
                   'after' => array($this->orgs[1]->id, $this->orgs[5]->id)),
            array('before' => array($this->orgs[1]->id, $this->orgs[2]->id, $this->orgs[3]->id, $this->orgs[3]->id, $this->orgs[4]->id, $this->orgs[5]->id, $this->orgs[9]->id, $this->orgs[6]->id, $this->orgs[7]->id, $this->orgs[8]->id, $this->orgs[2]->id, $this->orgs[9]->id, $this->orgs[10]->id),
                   'after' => array($this->orgs[1]->id, $this->orgs[5]->id, $this->orgs[10]->id)),
            array('before' => array($this->orgs[2]->id, $this->orgs[2]->id, $this->orgs[2]->id, $this->orgs[2]->id, $this->orgs[4]->id, $this->orgs[9]->id, $this->orgs[6]->id, $this->orgs[9]->id),
                   'after' => array($this->orgs[2]->id, $this->orgs[6]->id)),
            array('before' => array($this->orgs[8]->id, $this->orgs[9]->id),
                   'after' => array($this->orgs[8]->id)),
        );

        foreach ($testcases as $testcase) {
            // should match the 'after' state
            $output = $org->get_items_excluding_children($testcase['before']);
            $this->assertEquals($testcase['after'], $output);
        }
    }
}
