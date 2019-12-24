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
 * @package	block_community
 */

use block_community\userdata\community;
use totara_userdata\userdata\target_item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * @group block_community
 */
class totara_community_userdata_test extends advanced_testcase {

    /**
     *  Set up tests
     */
    protected function setupdata() {
        $this->setAdminUser();

        $data = new stdClass();

        // Create 2 users.
        $data->user1 = $this->getDataGenerator()->create_user();
        $data->user2 = $this->getDataGenerator()->create_user();

        $systemcontext = \context_system::instance();
        $user1context = \context_user::instance($data->user1->id);
        $user2context = \context_user::instance($data->user2->id);

        // Create a community block for user 1, user context.
        $blockrecord1 = new stdClass();
        $blockrecord1->parentcontextid = $user1context->id;
        $block1 = $this->getDataGenerator()->create_block('community', $blockrecord1);

        // Create a community block for user 2, user context.
        $blockrecord2 = new stdClass();
        $blockrecord2->parentcontextid = $user2context->id;
        $block2 = $this->getDataGenerator()->create_block('community', $blockrecord2);

        // Create a third block in system context.
        $systemblock = new stdClass();
        $systemblock->parentcontextid = $systemcontext->id;
        $block3 = $this->getDataGenerator()->create_block('community', $systemblock);

        $community_generator = $this->getDataGenerator()->get_plugin_generator('block_community');

        // Add a course link for user one, block one.
        $data->quicklink1 = $community_generator->create_community_link($block1, [
            'userid' => $data->user1->id,
            'coursename' => 'Course 1',
            'coursedescription' => 'Course 1 description',
            'courseurl' => 'Course 1 url',
            'imageurl' => 'Course 1 image url',
        ]);

        // Add a second course link for user one, block one.
        $data->quicklink2 = $community_generator->create_community_link($block1, [
            'userid' => $data->user1->id,
            'coursename' => 'Course 2',
            'coursedescription' => 'Course 2 description',
            'courseurl' => 'Course 2 url',
            'imageurl' => 'Course 2 image url',
        ]);

        // Add a third course link for user one in block three, system context.
        $data->quicklink3 = $community_generator->create_community_link($block3, [
            'userid' => $data->user1->id,
            'coursename' => 'Course 3',
            'coursedescription' => 'Course 3 description',
            'courseurl' => 'Course 3 url',
            'imageurl' => 'Course 3 image url',
        ]);

        // Add a course link for user 2, block two.
        $data->quicklink4 = $community_generator->create_community_link($block2, [
            'userid' => $data->user2->id,
            'coursename' => 'Course 4',
            'coursedescription' => 'Course 4 description',
            'courseurl' => 'Course 4 url',
            'imageurl' => 'Course 4 image url',
        ]);

        return $data;
    }

    /**
     * Test function is_purgeable.
     */
    public function test_is_purgeable() {
        $this->assertFalse(community::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertFalse(community::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertFalse(community::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * Test function is_exportable.
     */
    public function test_is_exportable() {
        $this->assertTrue(community::is_exportable());
    }

    /**
     * Test if data is exported
     */
    public function test_export_block_community() {
        $this->resetAfterTest();

        $data = $this->setupdata();

        //
        // Test export for user 1.
        //
        $targetuser = new target_user($data->user1, context_system::instance()->id);
        $export = community::execute_export($targetuser, context_system::instance());

        $this->assertCount(3, $export->data);

        $exportdata = $export->data;
        ksort($exportdata);

        $actual = current($exportdata);
        $this->assertEquals($data->user1->id, $actual->userid);
        $this->assertEquals('Course 1', $actual->coursename);
        $this->assertEquals('Course 1 description', $actual->coursedescription);
        $this->assertEquals('Course 1 url', $actual->courseurl);
        $this->assertEquals('Course 1 image url', $actual->imageurl);

        $actual = next($exportdata);
        $this->assertEquals($data->user1->id, $actual->userid);
        $this->assertEquals('Course 2', $actual->coursename);
        $this->assertEquals('Course 2 description', $actual->coursedescription);
        $this->assertEquals('Course 2 url', $actual->courseurl);
        $this->assertEquals('Course 2 image url', $actual->imageurl);

        $actual = next($exportdata);
        $this->assertEquals($data->user1->id, $actual->userid);
        $this->assertEquals('Course 3', $actual->coursename);
        $this->assertEquals('Course 3 description', $actual->coursedescription);
        $this->assertEquals('Course 3 url', $actual->courseurl);
        $this->assertEquals('Course 3 image url', $actual->imageurl);

        //
        // Test export for user 2.
        //
        $targetuser = new target_user($data->user2, context_system::instance()->id);
        $export = community::execute_export($targetuser, context_system::instance());

        $this->assertCount(1, $export->data);

        $exportdata = $export->data;

        $actual = current($exportdata);
        $this->assertEquals($data->user2->id, $actual->userid);
        $this->assertEquals('Course 4', $actual->coursename);
        $this->assertEquals('Course 4 description', $actual->coursedescription);
        $this->assertEquals('Course 4 url', $actual->courseurl);
        $this->assertEquals('Course 4 image url', $actual->imageurl);
    }

    /**
     * Test function is_countable.
     */
    public function test_is_countable() {
        $this->assertTrue(community::is_countable());
    }

    /**
     * Test count of items
     */
    public function test_count_block_community() {
        $this->resetAfterTest();

        $data = $this->setupdata();

        // Test count for user 1.
        $targetuser = new target_user($data->user1, context_system::instance()->id);
        $count = community::execute_count($targetuser, context_system::instance());
        $this->assertEquals(3, $count);

        // Test count for user 2.
        $targetuser = new target_user($data->user2, context_system::instance()->id);
        $count = community::execute_count($targetuser, context_system::instance());
        $this->assertEquals(1, $count);
    }
}
