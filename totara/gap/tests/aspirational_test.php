<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package totara_gap
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page.
}
global $CFG;
require_once($CFG->dirroot . '/totara/gap/lib.php');

class totara_gap_aspirational_test extends advanced_testcase {
    /**
     * Test that permissions checked correctly
     */
    public function test_totara_gap_can_edit_aspirational_position() {
        global $DB;
        $this->resetAfterTest();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();

        $this->setAdminUser();
        $this->assertTrue(totara_gap_can_edit_aspirational_position($user1->id));

        $this->setUser($user1);
        $this->assertFalse(totara_gap_can_edit_aspirational_position($user2->id));
        // Need capability to change own aspirational position.
        $this->assertFalse(totara_gap_can_edit_aspirational_position($user1->id));

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $managerrole = $DB->get_record('role', array('shortname' => 'manager'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        assign_capability('totara/gap:assignaspirationalposition', CAP_ALLOW, $managerrole->id, context_user::instance($user1->id));
        assign_capability('totara/gap:assignaspirationalposition', CAP_ALLOW, $teacherrole->id, context_system::instance());
        assign_capability('totara/gap:assignselfaspirationalposition', CAP_ALLOW, $studentrole->id, context_system::instance());

        role_assign($studentrole->id, $user1->id, context_system::instance());
        role_assign($studentrole->id, $user2->id, context_system::instance());
        role_assign($managerrole->id, $manager->id, context_user::instance($user1->id));
        role_assign($managerrole->id, $teacher->id, context_system::instance());

        $this->setUser($user1);
        $this->assertTrue(totara_gap_can_edit_aspirational_position($user1->id));
        $this->setUser($user2);
        $this->assertFalse(totara_gap_can_edit_aspirational_position($user1->id));
        $this->setUser($teacher);
        $this->assertTrue(totara_gap_can_edit_aspirational_position($user1->id));
        $this->assertTrue(totara_gap_can_edit_aspirational_position($user2->id));
        $this->setUser($manager);
        $this->assertTrue(totara_gap_can_edit_aspirational_position($user1->id));
        $this->assertFalse(totara_gap_can_edit_aspirational_position($user2->id));
    }

    /**
     * Test aspirational position details fetch
     */
    public function test_totara_gap_get_aspirational_position() {
        global $DB;
        $this->resetAfterTest();
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $fw = $hierarchy_generator->create_pos_frame(array());

        $pos1 = $hierarchy_generator->create_pos(array('frameworkid' => $fw->id, 'fullname' => 'fw1p1'));

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $DB->insert_record('gap_aspirational', (object)array('userid' => $user1->id,
            'positionid' => $pos1->id, 'timecreated' => '1468468590', 'timemodified' => '1468468595', 'usermodified' => $user2->id));

        $gapasp = totara_gap_get_aspirational_position($user1->id);
        $this->assertEquals($user1->id, $gapasp->userid);
        $this->assertEquals('fw1p1', $gapasp->fullname);
        $this->assertEquals($pos1->id, $gapasp->positionid);
        $this->assertEquals('1468468590', $gapasp->timecreated);
        $this->assertEquals('1468468595', $gapasp->timemodified);
        $this->assertEquals($user2->id, $gapasp->usermodified);

        $nopos = totara_gap_get_aspirational_position($user2->id);
        $this->assertFalse($nopos);
    }

    /**
     * Test aspirational position assignment in profile
     */
    public function test_totara_gap_assign_aspirational_position() {
        $this->resetAfterTest();
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $fw = $hierarchy_generator->create_pos_frame(array());

        $pos1 = $hierarchy_generator->create_pos(array('frameworkid' => $fw->id, 'fullname' => 'fw1p1'));

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Assign.
        $time = time();
        $this->setUser($user2);
        totara_gap_assign_aspirational_position($user1->id, $pos1->id);

        $gapasp = totara_gap_get_aspirational_position($user1->id);
        $this->assertEquals($user1->id, $gapasp->userid);
        $this->assertEquals('fw1p1', $gapasp->fullname);
        $this->assertEquals($pos1->id, $gapasp->positionid);
        $this->assertLessThan(5, $gapasp->timecreated - $time);
        $this->assertLessThan(5, $gapasp->timemodified - $time);
        $this->assertEquals($user2->id, $gapasp->usermodified);

        // Unassign.
        totara_gap_assign_aspirational_position($user1->id, 0);
        $this->assertFalse(totara_gap_get_aspirational_position($user1->id));

        // Wrong user.
        try {
            totara_gap_assign_aspirational_position(0, $pos1->id);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: User id missing for aspirational position', $e->getMessage());
        }
    }
}