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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_program
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');

/**
 * Tests program progress information functions
 */
class totara_program_program_progress_cache_testcase extends reportcache_advanced_testcase {

    /**
     * Setup data used in test functions
     *
     * @return object $data
     */
    private function setup_common() {
        $that = new class() {
            /** @var totara_core\progressinfo\progressinfo */
            public $progressinfo;

            /** @var cache_application */
            public $cache, $progcache, $usercache;

            /** @var int */
            public $numusers;
        };

        $that->progressinfo = \totara_core\progressinfo\progressinfo::from_data(\totara_core\progressinfo\progressinfo::AGGREGATE_ALL);

        $that->cache = \totara_program\progress\program_progress_cache::get_progressinfo_cache();
        $that->progcache = \totara_program\progress\program_progress_cache::get_program_users_cache();
        $that->usercache = \totara_program\progress\program_progress_cache::get_user_programs_cache();

        $that->numusers = 10;

        return $that;
    }


    public function test_add_to_cache() {
        $that = $this->setup_common();

        $progid = 1;
        $progkey = \totara_program\progress\program_progress_cache::get_program_users_cache_key($progid);

        // Test initial state
        $this->assertFalse($that->progcache->get($progkey));

        for ($userid = 1; $userid <= $that->numusers; $userid++) {
            $key = \totara_program\progress\program_progress_cache::get_progressinfo_cache_key($progid, $userid);
            $this->assertFalse($that->cache->get($key));

            $userkey = \totara_program\progress\program_progress_cache::get_user_programs_cache_key($userid);
            $this->assertFalse($that->usercache->get($userkey));
        }


        // Add cache for user1. program1
        $userid = 1;
        \totara_program\progress\program_progress_cache::add_progressinfo_to_cache($progid, $userid, $that->progressinfo);

        // user1's keys should have been added
        $key = \totara_program\progress\program_progress_cache::get_progressinfo_cache_key($progid, $userid);
        $data = $that->cache->get($key);
        $this->assertNotFalse($data);
        $this->assertEquals($that->progressinfo, $data);

        // $key should also be in the cached program_users user_programs value
        $progdata = $that->progcache->get($progkey);
        $this->assertNotFalse($progdata);
        $this->assertTrue(in_array($key, $progdata));

        $userkey = \totara_program\progress\program_progress_cache::get_user_programs_cache_key($userid);
        $userdata = $that->usercache->get($userkey);
        $this->assertTrue(in_array($key, $userdata));

        // Rest of users should still not have any cached data
        for ($id = 2; $id <= $that->numusers; $id++) {
            $key = \totara_program\progress\program_progress_cache::get_progressinfo_cache_key($progid, $id);
            $this->assertFalse($that->cache->get($key));

            $this->assertFalse(in_array($key, $progdata));

            $userkey = \totara_program\progress\program_progress_cache::get_user_programs_cache_key($id);
            $this->assertFalse($that->usercache->get($userkey));
        }


        // Add cache for user2. program1
        $userid = 2;
        \totara_program\progress\program_progress_cache::add_progressinfo_to_cache($progid, $userid, $that->progressinfo);

        // user1 and user2's keys should have been added
        $progdata = $that->progcache->get($progkey);
        $this->assertNotFalse($progdata);

        for ($id = 1; $id <= 2; $id++) {
            $key = \totara_program\progress\program_progress_cache::get_progressinfo_cache_key($progid, $id);
            $data = $that->cache->get($key);
            $this->assertNotFalse($data);
            $this->assertEquals($that->progressinfo, $data);

            // $key should also be in the cached program_users user_programs value
            $this->assertTrue(in_array($key, $progdata));

            $userkey = \totara_program\progress\program_progress_cache::get_user_programs_cache_key($id);
            $userdata = $that->usercache->get($userkey);
            $this->assertTrue(in_array($key, $userdata));
        }

        // Rest of users should still not have any cached data
        for ($id = 3; $id <= $that->numusers; $id++) {
            $key = \totara_program\progress\program_progress_cache::get_progressinfo_cache_key($progid, $id);
            $this->assertFalse($that->cache->get($key));

            $this->assertFalse(in_array($key, $progdata));

            $userkey = \totara_program\progress\program_progress_cache::get_user_programs_cache_key($id);
            $this->assertFalse($that->usercache->get($userkey));
        }

        // Add cache for all other users in program1 (used in later tests)
        for ($userid = 3; $userid <= $that->numusers; $userid++) {
            \totara_program\progress\program_progress_cache::add_progressinfo_to_cache($progid, $userid, $that->progressinfo);
        }

        // Now cache should exist for all users
        $progdata = $that->progcache->get($progkey);
        $this->assertNotFalse($progdata);

        for ($id = 1; $id <= $that->numusers; $id++) {
            $key = \totara_program\progress\program_progress_cache::get_progressinfo_cache_key($progid, $id);
            $data = $that->cache->get($key);
            $this->assertNotFalse($data);
            $this->assertEquals($that->progressinfo, $data);

            // $key should also be in the cached program_users user_programs value
            $this->assertTrue(in_array($key, $progdata));

            $userkey = \totara_program\progress\program_progress_cache::get_user_programs_cache_key($id);
            $userdata = $that->usercache->get($userkey);
            $this->assertTrue(in_array($key, $userdata));
        }

        // add to second program

        $that = $this->setup_common();

        // Add cache for first 5 users to program2
        $progid = 2;

        for ($userid = 1; $userid <= 5; $userid++) {
            \totara_program\progress\program_progress_cache::add_progressinfo_to_cache($progid, $userid, $that->progressinfo);
        }

        $progkey1 = \totara_program\progress\program_progress_cache::get_program_users_cache_key(1);
        $progkey2 = \totara_program\progress\program_progress_cache::get_program_users_cache_key(2);

        $progdata1 = $that->progcache->get($progkey1);
        $progdata2 = $that->progcache->get($progkey2);

        // user1 - user5 should have cached entries for program1 and program2. Rest only program1
        for ($id = 1; $id <= $that->numusers; $id++) {
            $key1 = \totara_program\progress\program_progress_cache::get_progressinfo_cache_key(1, $id);
            $key2 = \totara_program\progress\program_progress_cache::get_progressinfo_cache_key(2, $id);

            $data = $that->cache->get($key1);
            $this->assertNotFalse($data);
            $this->assertEquals($that->progressinfo, $data);

            if ($id <= 5) {
                $data = $that->cache->get($key2);
                $this->assertNotFalse($data);
                $this->assertEquals($that->progressinfo, $data);
            } else {
                $this->assertFalse($that->cache->get($key2));
            }

            // Check the cached program_users user_programs values
            $this->assertTrue(in_array($key1, $progdata1));
            $this->assertFalse(in_array($key1, $progdata2));
            $this->assertFalse(in_array($key2, $progdata1));
            if ($id <= 5) {
                $this->assertTrue(in_array($key2, $progdata2));
            } else {
                $this->assertFalse(in_array($key2, $progdata2));
            }

            $userkey = \totara_program\progress\program_progress_cache::get_user_programs_cache_key($id);
            $userdata = $that->usercache->get($userkey);
            $this->assertNotFalse($userdata);

            $this->assertTrue(in_array($key1, $userdata));
            if ($id <= 5) {
                $this->assertTrue(in_array($key2, $userdata));
            } else {
                $this->assertFalse(in_array($key2, $userdata));
            }
        }

        // mark progressinfo stale

        $that = $this->setup_common();

        // We are going to mark cache for program2, user2 stale
        $key1 = \totara_program\progress\program_progress_cache::get_progressinfo_cache_key(1, 2);
        $key2 = \totara_program\progress\program_progress_cache::get_progressinfo_cache_key(2, 2);

        $progkey1 = \totara_program\progress\program_progress_cache::get_program_users_cache_key(1);
        $progkey2 = \totara_program\progress\program_progress_cache::get_program_users_cache_key(2);
        $progdata1 = $that->progcache->get($progkey1);
        $progdata2 = $that->progcache->get($progkey2);

        $userkey = \totara_program\progress\program_progress_cache::get_user_programs_cache_key(2);
        $userdata = $that->usercache->get($userkey);

        // Test starting state
        $this->assertNotFalse($that->cache->get($key1));
        $this->assertNotFalse($that->cache->get($key2));

        $this->assertTrue(in_array($key1, $progdata1));
        $this->assertFalse(in_array($key2, $progdata1));
        $this->assertFalse(in_array($key1, $progdata2));
        $this->assertTrue(in_array($key2, $progdata2));

        $this->assertTrue(in_array($key1, $userdata));
        $this->assertTrue(in_array($key2, $userdata));

        // Now mark the progressinfo cache stale for program1, user2
        \totara_program\progress\program_progress_cache::mark_progressinfo_stale(2, 2);

        $progdata1 = $that->progcache->get($progkey1);
        $progdata2 = $that->progcache->get($progkey2);
        $userdata = $that->usercache->get($userkey);

        $this->assertNotFalse($that->cache->get($key1));
        $this->assertFalse($that->cache->get($key2));

        $this->assertTrue(in_array($key1, $progdata1));
        $this->assertFalse(in_array($key2, $progdata1));
        $this->assertFalse(in_array($key1, $progdata2));
        $this->assertFalse(in_array($key2, $progdata2));

        $this->assertTrue(in_array($key1, $userdata));
        $this->assertFalse(in_array($key2, $userdata));
    }


    public function test_mark_user_cache_stale() {
        $this->resetAfterTest(true);

        $that = $this->setup_common();

        // Add all users to program1, only first 5 to program2
        for ($id = 1; $id <= $that->numusers; $id++) {
            \totara_program\progress\program_progress_cache::add_progressinfo_to_cache(1, $id, $that->progressinfo);
        }
        for ($userid = 1; $userid <= 5; $userid++) {
            \totara_program\progress\program_progress_cache::add_progressinfo_to_cache(2, $userid, $that->progressinfo);
        }

        // We are first going to mark user_programs cache stale for user4
        $key1 = \totara_program\progress\program_progress_cache::get_progressinfo_cache_key(1, 4);
        $key2 = \totara_program\progress\program_progress_cache::get_progressinfo_cache_key(2, 4);

        $progkey1 = \totara_program\progress\program_progress_cache::get_program_users_cache_key(1);
        $progkey2 = \totara_program\progress\program_progress_cache::get_program_users_cache_key(2);
        $progdata1 = $that->progcache->get($progkey1);
        $progdata2 = $that->progcache->get($progkey2);

        $userkey = \totara_program\progress\program_progress_cache::get_user_programs_cache_key(4);
        $userdata = $that->usercache->get($userkey);

        // Test starting state
        $this->assertNotFalse($that->cache->get($key1));
        $this->assertNotFalse($that->cache->get($key2));

        $this->assertTrue(in_array($key1, $progdata1));
        $this->assertFalse(in_array($key2, $progdata1));
        $this->assertFalse(in_array($key1, $progdata2));
        $this->assertTrue(in_array($key2, $progdata2));

        $this->assertTrue(in_array($key1, $userdata));
        $this->assertTrue(in_array($key2, $userdata));

        // Now mark the user cache stale for user4 (in both programs)
        \totara_program\progress\program_progress_cache::mark_user_cache_stale(4);

        $progdata1 = $that->progcache->get($progkey1);
        $progdata2 = $that->progcache->get($progkey2);
        $userdata = $that->usercache->get($userkey);

        $this->assertFalse($that->cache->get($key1));
        $this->assertFalse($that->cache->get($key2));

        // Entries are not deleted from program_users cache
        $this->assertTrue(in_array($key1, $progdata1));
        $this->assertFalse(in_array($key2, $progdata1));
        $this->assertFalse(in_array($key1, $progdata2));
        $this->assertTrue(in_array($key2, $progdata2));

        $this->assertFalse($userdata);

        // Now mark the user cache stale for user7 (only in program1)
        \totara_program\progress\program_progress_cache::mark_user_cache_stale(7);

        $progdata1 = $that->progcache->get($progkey1);
        $progdata2 = $that->progcache->get($progkey2);
        $userkey = \totara_program\progress\program_progress_cache::get_user_programs_cache_key(7);
        $userdata = $that->usercache->get($userkey);

        $key1 = \totara_program\progress\program_progress_cache::get_progressinfo_cache_key(1, 7);
        $key2 = \totara_program\progress\program_progress_cache::get_progressinfo_cache_key(2, 7);

        $this->assertFalse($that->cache->get($key1));
        $this->assertFalse($that->cache->get($key2));

        // Entries are not deleted from program_users cache
        $this->assertTrue(in_array($key1, $progdata1));
        $this->assertFalse(in_array($key2, $progdata1));
        $this->assertFalse(in_array($key1, $progdata2));
        $this->assertFalse(in_array($key2, $progdata2));

        $this->assertFalse($userdata);
    }


    public function test_mark_program_cache_stale() {
        $this->resetAfterTest(true);

        $that = $this->setup_common();

        // Add all users to program1, only first 5 to program2
        for ($id = 1; $id <= $that->numusers; $id++) {
            \totara_program\progress\program_progress_cache::add_progressinfo_to_cache(1, $id, $that->progressinfo);
        }
        for ($userid = 1; $userid <= 5; $userid++) {
            \totara_program\progress\program_progress_cache::add_progressinfo_to_cache(2, $userid, $that->progressinfo);
        }

        // We are going to mark program cache stale for program1
        $progkey1 = \totara_program\progress\program_progress_cache::get_program_users_cache_key(1);
        $progkey2 = \totara_program\progress\program_progress_cache::get_program_users_cache_key(2);
        $progdata1 = $that->progcache->get($progkey1);
        $progdata2 = $that->progcache->get($progkey2);

        // Test starting state
        for ($id = 1; $id <= $that->numusers; $id++) {
            $key1 = \totara_program\progress\program_progress_cache::get_progressinfo_cache_key(1, $id);
            $key2 = \totara_program\progress\program_progress_cache::get_progressinfo_cache_key(2, $id);
            $userkey = \totara_program\progress\program_progress_cache::get_user_programs_cache_key($id);
            $userdata = $that->usercache->get($userkey);

            $this->assertNotFalse($that->cache->get($key1));
            $this->assertTrue(in_array($key1, $progdata1));
            $this->assertFalse(in_array($key1, $progdata2));
            $this->assertTrue(in_array($key1, $userdata));

            if ($id <= 5) {
                $this->assertNotFalse($that->cache->get($key2));
                $this->assertTrue(in_array($key2, $progdata2));
                $this->assertTrue(in_array($key2, $userdata));
            } else {
                $this->assertFalse($that->cache->get($key2));
                $this->assertFalse(in_array($key2, $progdata2));
                $this->assertFalse(in_array($key2, $userdata));
            }
        }

        // Now mark the program cache stale for program1
        \totara_program\progress\program_progress_cache::mark_program_cache_stale(1);

        $progdata1 = $that->progcache->get($progkey1);
        $progdata2 = $that->progcache->get($progkey2);
        $this->assertFalse($progdata1);
        $this->assertNotFalse($progdata2);

        for ($id = 1; $id <= $that->numusers; $id++) {
            $key1 = \totara_program\progress\program_progress_cache::get_progressinfo_cache_key(1, $id);
            $key2 = \totara_program\progress\program_progress_cache::get_progressinfo_cache_key(2, $id);
            $userkey = \totara_program\progress\program_progress_cache::get_user_programs_cache_key($id);
            $userdata = $that->usercache->get($userkey);

            $this->assertFalse($that->cache->get($key1));
            $this->assertFalse(in_array($key1, $progdata2));

            // User cache not updated when program cache is cleared
            $this->assertTrue(in_array($key1, $userdata));

            if ($id <= 5) {
                $this->assertNotFalse($that->cache->get($key2));
                $this->assertTrue(in_array($key2, $progdata2));
                $this->assertTrue(in_array($key2, $userdata));
            } else {
                $this->assertFalse($that->cache->get($key2));
                $this->assertFalse(in_array($key2, $progdata2));
                $this->assertFalse(in_array($key2, $userdata));
            }
        }
    }
}
