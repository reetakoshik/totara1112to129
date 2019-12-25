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
 * @package availability_audience
 */

defined('MOODLE_INTERNAL') || die();

use availability_audience\condition;

/**
 * @group availability
 */
class availability_audience_condition_testcase extends advanced_testcase {
    /**
     * Tests constructing and using grade condition.
     */
    public function test_usage() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        $this->resetAfterTest();
        $CFG->enableavailability = true;

        $cohort_generator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');

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

        // Create an audience
        $cohort = $cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_STATIC, 'name' => 'Test Audience 1'));

        // Construct structure for audience condition.
        $structure = (object)array('type' => 'audience', 'cohort' => $cohort->id);
        $condition = new condition($structure);

        // Check if available when user is not part of audience.
        $this->assertFalse($condition->is_available(false, $info, true, $user->id));
        $information = strip_tags($condition->get_description(false, false, $info));
        $this->assertRegExp('~You are a member of the Audience: Test Audience 1~', $information);
        $this->assertTrue($condition->is_available(true, $info, true, $user->id));

        // Add the user to the audience and check availablity again (should be successful).
        $cohort_generator->cohort_assign_users($cohort->id, $users);
        $this->assertTrue($condition->is_available(false, $info, true, $user->id));
        $this->assertFalse($condition->is_available(true, $info, true, $user->id));
        $information = strip_tags($condition->get_description(false, true, $info));
        $this->assertRegExp('~You are not a member of the Audience: Test Audience 1~', $information);
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
            $this->assertContains('Missing or invalid ->cohort', $e->getMessage());
        }

        // Invalid id (not int).
        $structure = new stdClass();
        $structure->cohort = 'tesla';
        try {
            $condition = new condition($structure);
            $this->fail();
        } catch (coding_exception $e) {
            $this->assertContains('Missing or invalid ->cohort', $e->getMessage());
        }

        // Valid structure.
        $structure = new stdClass();
        $structure->cohort = '4';
        $condition = new condition($structure);
        $this->assertEquals('{audience:#4}', (string)$condition);
    }

    /**
     * Tests the save() function.
     */
    public function test_save() {
        $structure = (object)array('cohort' => 19);
        $condition = new condition($structure);
        $structure->type = 'audience';
        $this->assertEquals($structure, $condition->save());
    }
}
