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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package core_user
 */

use core_user\userdata\interests;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose core_user_userdata_interests_testcase user/tests/userdata_interests_test.php
 *
 * @group totara_userdata
 */
class core_user_userdata_interests_testcase extends \advanced_testcase {

    /**
     * Test function is_compatible_context_level with all possible contexts.
     */
    public function test_get_compatible_context_levels() {
        $this->assertEquals(array(CONTEXT_SYSTEM), interests::get_compatible_context_levels());
    }

    /**
     * Test function is_purgeable.
     */
    public function test_is_purgeable() {
        $this->assertTrue(interests::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(interests::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(interests::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * Test function is_exportable.
     */
    public function test_is_exportable() {
        $this->assertTrue(interests::is_exportable());
    }

    /**
     * Test function is_countable.
     */
    public function test_is_countable() {
        $this->assertTrue(interests::is_countable());
    }

    /**
     * Test the purge function. Make sure that the control data is not affected.
     */
    public function test_purge() {
        global $DB;

        $this->resetAfterTest(true);

        // Set up users with intertests. 'interest1' and 'generictag1' won't be deleted from the tag table, only tag_instance.
        $user1 = $this->getDataGenerator()->create_user(['interests' => ['interest1', 'interest2', 'generictag1']]);
        // Control user.
        $this->getDataGenerator()->create_user(['interests' => ['interest1', 'interest3', 'generictag2']]);

        // Set up a non-user tag.
        $context = \context_system::instance();
        \core_tag_tag::add_item_tag('core', 'course', 123, $context, 'generictag1', $user1->id);

        // Get the expected data, by modifying the actual data.

        // All user instances belonging to the user should be deleted.
        $expectedtaginstances = $DB->get_records('tag_instance', [], 'id');
        foreach ($expectedtaginstances as $key => $expectedtaginstance) {
            if ($expectedtaginstance->itemtype == 'user' && $expectedtaginstance->itemid == $user1->id) {
                unset($expectedtaginstances[$key]);
            }
        }

        // Tags created by the user which are no longer in use by any user or other thing should be deleted.
        // 'interest1' and 'generictag1' won't be deleted.
        $expectedtags = $DB->get_records('tag', [], 'id');
        foreach ($expectedtags as $key => $expectedtag) {
            if ($expectedtag->name == 'interest2') {
                unset($expectedtags[$key]);
            }
        }

        // Execute the purge.
        $status = interests::execute_purge(new target_user($user1), context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        // Check the results.
        $this->assertEquals($expectedtags, $DB->get_records('tag', [], 'id'));
        $this->assertEquals($expectedtaginstances, $DB->get_records('tag_instance', [], 'id'));
    }

    /**
     * Test the count function.
     */
    public function test_count() {
        $this->resetAfterTest(true);

        // Set up users with intertests. 'interest1' and 'generictag1' won't be deleted from the tag table, only tag_instance.
        $user = $this->getDataGenerator()->create_user(['interests' => ['interest1', 'interest2', 'generictag1']]);

        // Set up the target user.
        $targetuser = new target_user($user);

        $this->assertEquals(3, interests::execute_count($targetuser, context_system::instance()));

        // Execute the purge and recheck count.
        interests::execute_purge($targetuser, context_system::instance());
        $this->assertEquals(0, interests::execute_count($targetuser, context_system::instance()));
    }

    /**
     * Test the export function. Make sure that the control data is not exported.
     */
    public function test_export() {
        $this->resetAfterTest(true);

        // Set up users with intertests. 'interest1' and 'generictag1' won't be deleted from the tag table, only tag_instance.
        $user1 = $this->getDataGenerator()->create_user(['interests' => ['interest1', 'interest2', 'generictag1']]);
        // Control user.
        $this->getDataGenerator()->create_user(['interests' => ['interest1', 'interest3', 'generictag2']]);

        // Execute the export.
        $result = interests::execute_export(new target_user($user1), context_system::instance());

        // Check the results.
        $this->assertCount(0, $result->files);
        $this->assertCount(3, $result->data['interests']);
        $this->assertContains('interest1', $result->data['interests']);
        $this->assertContains('interest2', $result->data['interests']);
        $this->assertContains('generictag1', $result->data['interests']);
    }
}