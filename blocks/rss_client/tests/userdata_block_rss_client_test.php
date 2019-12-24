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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package block_rss_client
 */

use block_rss_client\userdata\rss_client;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Test purging, exporting and counting of block rss client
 * @group totara_userdata
 * @group block_rss_client
 */
class userdata_block_rss_client_test extends advanced_testcase {

    /**
     * Test the abilities to purge, export and count
     */
    public function test_abilities() {
        $this->assertTrue(rss_client::is_countable());
        $this->assertTrue(rss_client::is_exportable());
        $this->assertFalse(rss_client::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertFalse(rss_client::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertFalse(rss_client::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * test count when user has no rss client block
     */
    public function test_count_when_user_has_empty_rss_client_block() {
        global $DB;

        $this->resetAfterTest();

        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $this->create_test_rss_client_block($controluser);

        // init user
        $user = $this->getDataGenerator()->create_user();
        $targetuser = new target_user($user);

        // prove that control user has records
        $this->assertCount(1, $DB->get_records('block_rss_client', ['userid' => $controluser->id]));

        // check count
        $result = rss_client::execute_count($targetuser, context_system::instance());
        $this->assertEquals(0, $result);
    }

    /**
     * test count when user has rss client block
     */
    public function test_count_when_user_has_rss_client_block() {
        global $DB;

        $this->resetAfterTest();

        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $this->create_test_rss_client_block($controluser);

        // init user
        $user = $this->getDataGenerator()->create_user();
        $this->create_test_rss_client_block($user);
        $targetuser = new target_user($user);

        // prove that both users have records
        $this->assertCount(1, $DB->get_records('block_rss_client', ['userid' => $controluser->id]));
        $this->assertCount(1, $DB->get_records('block_rss_client', ['userid' => $targetuser->id]));

        // check count
        $result = rss_client::execute_count($targetuser, context_system::instance());
        $this->assertEquals(1, $result);
    }

    /**
     * test export when user has no rss client block
     */
    public function test_export_when_user_has_no_rss_client_block() {
        $this->resetAfterTest();

        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $this->create_test_rss_client_block($controluser);

        // init user
        $targetuser = new target_user($this->getDataGenerator()->create_user());

        //check export data for user
        $result = rss_client::execute_export($targetuser, context_system::instance());
        $this->assertEmpty($result->data);
        $this->assertEmpty($result->files);
    }

    /**
     * test export when user has rss client block
     */
    public function test_export_when_user_has_rss_client_block() {
        $this->resetAfterTest();

        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $this->create_test_rss_client_block($controluser);

        // init user
        $user = $this->getDataGenerator()->create_user();
        $this->create_test_rss_client_block($user);
        $targetuser = new target_user($user);

        // check export data for user
        $result = rss_client::execute_export($targetuser, context_system::instance());
        $this->assertCount(1, $result->data);
        $this->assertEmpty($result->files);
        foreach ($result->data as $exportitem) {
            $this->assertEquals($targetuser->id, $exportitem->userid);
            $this->assertNotEquals($controluser->id, $exportitem->userid);
            $this->assertContains("/" . $exportitem->userid . "/", $exportitem->url);
            foreach (['id', 'userid', 'title', 'preferredtitle', 'description', 'shared', 'url', 'skiptime', 'skipuntil'] as $attribute) {
                $this->assertObjectHasAttribute($attribute, $exportitem);
            }
        }
    }

    /**
     * Create Test RSS client block
     *
     * @param $user
     */
    private function create_test_rss_client_block($user) {
        global $DB;

        $data = ['userid' => $user->id, 'title' => 'example Rss', 'description' => 'test description', 'url' => 'http://example.com/' . $user->id . '/rss'];
        $DB->insert_record('block_rss_client', $data);
    }
}
