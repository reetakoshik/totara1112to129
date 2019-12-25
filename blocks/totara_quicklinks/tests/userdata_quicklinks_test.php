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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package	block_totara_quicklinks
 */

use block_totara_quicklinks\userdata\quicklinks;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * @group block_totara_quicklinks
 */
class totara_quicklinks_userdata_test extends advanced_testcase {

    /**
     *  Set up tests
     */
    protected function setupdata() {
        $this->setAdminUser();

        $data = new stdClass();

        $data->user1 = $this->getDataGenerator()->create_user([
            'username' => 'testuser1',
            'firstname' => 'Edward',
            'lastname' => 'Elric'
        ]);

        $data->user2 = $this->getDataGenerator()->create_user([
            'username' => 'testuser2',
            'firstname' => 'Alphonse',
            'lastname' => 'Elric'
        ]);

        $systemcontext = \context_system::instance();
        $user1context = \context_user::instance($data->user1->id);
        $user2context = \context_user::instance($data->user2->id);

        $blockrecord1 = new stdClass();
        $blockrecord1->parentcontextid = $user1context->id;
        $block1 = $this->getDataGenerator()->create_block('totara_quicklinks', $blockrecord1);

        $blockrecord2 = new stdClass();
        $blockrecord2->parentcontextid = $user2context->id;
        $block2 = $this->getDataGenerator()->create_block('totara_quicklinks', $blockrecord2);

        $systemblock = new stdClass();
        $systemblock->parentcontextid = $systemcontext->id;
        $block3 = $this->getDataGenerator()->create_block('totara_quicklinks', $systemblock);

        $quicklink_generator = $this->getDataGenerator()->get_plugin_generator('block_totara_quicklinks');

        $data->quicklink1 = $quicklink_generator->create_quick_link($block1, [
            'userid' => $data->user1->id,
            'title' => 'Google',
            'url' => 'http://google.com',
        ]);

        $data->quicklink2 = $quicklink_generator->create_quick_link($block2, [
            'userid' => $data->user2->id,
            'title' => 'Reddit',
            'url' => 'http://reddit.com',
        ]);

        $data->quicklink3 = $quicklink_generator->create_quick_link($block1, [
            'userid' => $data->user1->id,
            'title' => 'Stuff',
            'url' => 'http://stuff.co.nz',
        ]);

        $data->quicklink4 = $quicklink_generator->create_quick_link($block3, [
            'userid' => $data->user1->id,
            'title' => 'BBC',
            'url' => 'http://bbc.co.uk'
        ]);

        return $data;
    }

    /**
     * Test if data is exported
     */
    public function test_export_quicklinks() {

        $this->resetAfterTest();

        $data = $this->setupdata();

        $targetuser = new target_user($data->user1, context_system::instance()->id);
        $export = quicklinks::execute_export($targetuser, context_system::instance());

        $this->assertCount(2, $export->data);

        $actual1 = $export->data[0];
        $this->assertEquals('Google', $actual1->title);
        $this->assertEquals('http://google.com', $actual1->url);
        $this->assertEquals('0', $actual1->sort);
        $actual2 = $export->data[1];
        $this->assertEquals('Stuff', $actual2->title);
        $this->assertEquals('http://stuff.co.nz', $actual2->url);
        $this->assertEquals('1', $actual2->sort);
    }

    /**
     * Test count of items
     */
    public function test_count_quicklinks() {
        global $CFG;

        require_once($CFG->dirroot . '/user/lib.php');

        $this->resetAfterTest();

        $data = $this->setupdata();

        $targetuser = new target_user($data->user1, context_system::instance()->id);
        $count = quicklinks::execute_count($targetuser, context_system::instance());

        $this->assertEquals(2, $count);

        // Delete user and check count function still works.
        user_delete_user($data->user1);
        $count = quicklinks::execute_count($targetuser, context_system::instance());
        $this->assertEquals(0, $count);
    }
}
