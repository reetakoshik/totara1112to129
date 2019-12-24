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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

class totara_core_accesslib_testcase extends advanced_testcase {
    public function test_capability_names() {
        global $DB;
        $capabilities = $DB->get_records('capabilities', array());
        foreach ($capabilities as $capability) {
            $name = get_capability_string($capability->name);
            $this->assertDebuggingNotCalled("Debugging not expected when getting name of capability {$capability->name}");
            $this->assertNotContains('???', $name, "Unexpected problem when getting name of capability {$capability->name}");
        }
    }

    public function test_role_unassign_all_bulk() {
        global $DB;

        $this->resetAfterTest();

        $student = $DB->get_record('role', array('shortname' => 'student'), '*', MUST_EXIST);
        $teacher = $DB->get_record('role', array('shortname' => 'editingteacher'), '*', MUST_EXIST);

        $course1 = $this->getDataGenerator()->create_course();
        $context1 = context_course::instance($course1->id);
        $course2 = $this->getDataGenerator()->create_course();
        $context2 = context_course::instance($course2->id);
        $catcontext = context_coursecat::instance($course1->category);

        $user1 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id, $teacher->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id, $teacher->id);

        $user2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id, $student->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course2->id, $student->id);

        $user3 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user3->id, $course1->id, $student->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course2->id, $student->id);

        $user4 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user4->id, $course1->id, $student->id);
        $this->getDataGenerator()->enrol_user($user4->id, $course2->id, $student->id);

        $this->assertCount(8, $DB->get_records('role_assignments'));

        // Empty user list.
        role_unassign_all_bulk(array('contextid' => $context1->id, 'userids' => array()));
        $this->assertCount(8, $DB->get_records('role_assignments'));

        role_unassign_all_bulk(array('contextid' => $context1->id, 'roleid' => $student->id, 'userids' => array($user4->id)));
        $this->assertCount(7, $DB->get_records('role_assignments'));
        $this->assertCount(0, $DB->get_records('role_assignments', array('userid' => $user4->id, 'contextid' => $context1->id)));
        $this->assertCount(1, $DB->get_records('role_assignments', array('userid' => $user4->id, 'contextid' => $context2->id)));

        role_unassign_all_bulk(array('contextid' => $catcontext->id, 'userids' => array($user2->id, $user3->id)), true);
        $this->assertCount(3, $DB->get_records('role_assignments'));
        $this->assertCount(0, $DB->get_records('role_assignments', array('userid' => $user3->id)));
        $this->assertCount(0, $DB->get_records('role_assignments', array('userid' => $user2->id)));

        try {
            role_unassign_all_bulk(array());
            $this->fail('Exception expected when contextid parameter missing.');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: Missing parameters in role_unsassign_all_bulk() call', $e->getMessage());
        }

        try {
            role_unassign_all_bulk(array('contextid' => $catcontext->id, 'xxx' => 1));
            $this->fail('Exception expected when unknown parameter present.');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: Unknown role_unsassign_all_bulk() parameter key (key:xxx)', $e->getMessage());
        }
    }

    /**
     * Test that core takes over migrated capabilities automatically with a debug message in case
     * we forget to do it in Totara pre-upgrade script.
     */
    public function test_capability_move() {
        global $DB;
        $this->resetAfterTest();

        update_capabilities('moodle');
        $this->assertDebuggingNotCalled();

        $viewbadges = $DB->get_record('capabilities', array('name' => 'moodle/badges:viewbadges'), '*', MUST_EXIST);
        $this->assertSame('moodle', $viewbadges->component);

        $DB->set_field('capabilities', 'component', 'totara_core', array('id' => $viewbadges->id));

        update_capabilities('moodle');
        $this->assertDebuggingCalled('Capability \'moodle/badges:viewbadges\' already existed in different component, please fix the pre-upgrade code');

        $newviewbadges = $DB->get_record('capabilities', array('name' => 'moodle/badges:viewbadges'), '*', MUST_EXIST);
        $this->assertEquals($viewbadges, $newviewbadges);
    }
}
