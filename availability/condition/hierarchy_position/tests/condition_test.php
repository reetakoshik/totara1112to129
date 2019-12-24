<?php
/*
 * This file is part of Totara Learn
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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package availability_hierarchy_position
 */

defined('MOODLE_INTERNAL') || die();

use availability_hierarchy_position\condition;

/**
 * @group availability
 */
class availability_hierarchy_position_condition_testcase extends advanced_testcase {
    /**
     * Tests constructing and using grade condition.
     */
    public function test_usage() {
        global $CFG;

        $this->resetAfterTest();
        $CFG->enableavailability = true;

        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        // Make a test course and user.
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $users = [$user->id];
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // Make assign module.
        $assignrow = $this->getDataGenerator()->create_module('assign', array(
                'course' => $course->id, 'name' => 'Interesting Assignment'));
        $assign = new assign(context_module::instance($assignrow->cmid), false, false);
        $modinfo = get_fast_modinfo($course);
        $cm = $modinfo->get_cm($assignrow->cmid);

        $info = new \core_availability\info_module($cm);

        // Create a position.
        $pos_framework = $hierarchy_generator->create_framework('position', array('fullname' => 'All Positions'));
        $position_data = array(
            'fullname' => 'Test Position 1',
        );
        $position = $hierarchy_generator->create_hierarchy($pos_framework->id, 'position', $position_data);

        // Construct structure for position condition.
        $structure = (object)array('type' => 'hierarchy_position', 'position' => $position->id);
        $condition = new condition($structure);

        // Check if available when user is not assigned to a position.
        $this->assertFalse($condition->is_available(false, $info, true, $user->id));
        $information = strip_tags($condition->get_description(false, false, $info));
        $this->assertRegExp('~You are assigned to the Position: Test Position 1~', $information);
        $this->assertTrue($condition->is_available(true, $info, true, $user->id));

        // Assign user to position via job assignment.
        $ja_data = array(
            'fullname' => 'ja1',
            'positionid' => $position->id
        );
        $ja = \totara_job\job_assignment::create_default($user->id, $ja_data);

        $this->assertTrue($condition->is_available(false, $info, true, $user->id));
        $this->assertFalse($condition->is_available(true, $info, true, $user->id));
        $information = strip_tags($condition->get_description(false, true, $info));
        $this->assertRegExp('~You are not assigned to Position: Test Position 1~', $information);
    }

    /**
     * Tests the constructor including error conditions. Also tests the
     * string conversion feature (intended for debugging only).
     */
    public function test_constructor() {
        // No parameters.
        $structure = new stdClass();
        try {
            $cond = new condition($structure);
            $this->fail();
        } catch (coding_exception $e) {
            $this->assertContains('Missing or invalid ->pos', $e->getMessage());
        }

        // Invalid id (not int).
        $structure = new stdClass();
        $structure->position = 'tesla';
        try {
            $condition = new condition($structure);
            $this->fail();
        } catch (coding_exception $e) {
            $this->assertContains('Missing or invalid ->pos', $e->getMessage());
        }

        // Valid structure.
        $structure = new stdClass();
        $structure->position = '4';
        $condition = new condition($structure);
        $this->assertEquals('{hierarchy_position:#4}', (string)$condition);
    }

    /**
     * Tests the save() function.
     */
    public function test_save() {
        $structure = (object)array('position' => 19);
        $condition = new condition($structure);
        $structure->type = 'hierarchy_position';
        $this->assertEquals($structure, $condition->save());
    }
}
