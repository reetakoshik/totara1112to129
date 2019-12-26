<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package availability_language
 */

defined('MOODLE_INTERNAL') || die();

use availability_language\condition;

/**
 * @group availability
 */
class availability_language_condition_testcase extends advanced_testcase {
    /**
     * Tests constructing and using grade condition.
     */
    public function test_usage() {
        global $CFG;

        $this->resetAfterTest();
        $CFG->enableavailability = true;

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

        // Construct structure for language condition.
        $structure = (object)array('type' => 'language', 'lang' => 'en');
        $condition = new condition($structure);

        // Check not available when user is not using the required language.
        $structure = (object)array('type' => 'language', 'lang' => 'x');
        $condition = new condition($structure);

        $this->assertFalse($condition->is_available(false, $info, true, $user->id));
        $this->assertTrue($condition->is_available(true, $info, true, $user->id));
        $information = $condition->get_description(false, true, $info);
        // Remove any weird (U+200E) formatting.
        // TODO: This can be removed when TL-16811 is in.
        $information = preg_replace('/\x{E2}\x{80}\x{8E}/', '', $information);
        $this->assertEquals('Your language is not <strong>(x)</strong>', $information);


        // Check is available when user is using the required language.
        $structure = (object)array('type' => 'language', 'lang' => 'en');
        $condition = new condition($structure);

        $this->assertTrue($condition->is_available(false, $info, true, $user->id));
        $this->assertFalse($condition->is_available(true, $info, true, $user->id));
        $information = $condition->get_description(false, true, $info);
        // Remove any weird (U+200E) formatting.
        // TODO: This can be removed when TL-16811 is in.
        $information = preg_replace('/\x{E2}\x{80}\x{8E}/', '', $information);
        $this->assertEquals('Your language is not <strong>English (en)</strong>', $information);
    }

    /**
     * Tests the constructor including error conditions. Also tests the
     * string conversion feature (intended for debugging only).
     */
    public function test_constructor() {
        // Invalid structure, no parameters.
        $structure = new stdClass();
        try {
            $cond = new condition($structure);
            $this->fail();
        } catch (coding_exception $e) {
            $this->assertContains('Missing ->lang for language condition', $e->getMessage());
        }

        // Valid structure.
        $structure = new stdClass();
        $structure->lang = 'en';
        $condition = new condition($structure);
        $this->assertEquals('{language:en}', (string)$condition);
    }

    /**
     * Tests the save() function.
     */
    public function test_save() {
        $structure = (object)array('lang' => 'en');
        $condition = new condition($structure);
        $structure->type = 'language';
        $this->assertEquals($structure, $condition->save());
    }
}
