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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package core_user
 */

use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;
use core_user\userdata\statistics;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_userdata
 */
class core_user_userdata_statistics_testcase extends advanced_testcase {

    /**
     * Setup users, courses
     */
    public function presetup() {
        global $DB;

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        $data = [
            ['courseid' => $course1->id, 'userid' => $user1->id, 'roleid' => 0, 'timeend' => time(), 'statsreads' => 0, 'statswrites' => 0, 'stattype' => 'logins'],
            ['courseid' => $course1->id, 'userid' => $user1->id, 'roleid' => 0, 'timeend' => time(), 'statsreads' => 0, 'statswrites' => 0, 'stattype' => 'activity'],
            ['courseid' => $course2->id, 'userid' => $user1->id, 'roleid' => 0, 'timeend' => time(), 'statsreads' => 0, 'statswrites' => 0, 'stattype' => 'logins'],
            ['courseid' => $course2->id, 'userid' => $user1->id, 'roleid' => 0, 'timeend' => time(), 'statsreads' => 0, 'statswrites' => 0, 'stattype' => 'activity'],
            ['courseid' => $course3->id, 'userid' => $user1->id, 'roleid' => 0, 'timeend' => time(), 'statsreads' => 0, 'statswrites' => 0, 'stattype' => 'logins'],
            ['courseid' => $course3->id, 'userid' => $user1->id, 'roleid' => 0, 'timeend' => time(), 'statsreads' => 0, 'statswrites' => 0, 'stattype' => 'activity'],

            ['courseid' => $course1->id, 'userid' => $user2->id, 'roleid' => 0, 'timeend' => time(), 'statsreads' => 0, 'statswrites' => 0, 'stattype' => 'logins'],
            ['courseid' => $course1->id, 'userid' => $user2->id, 'roleid' => 0, 'timeend' => time(), 'statsreads' => 0, 'statswrites' => 0, 'stattype' => 'activity'],
            ['courseid' => $course2->id, 'userid' => $user2->id, 'roleid' => 0, 'timeend' => time(), 'statsreads' => 0, 'statswrites' => 0, 'stattype' => 'logins'],
            ['courseid' => $course2->id, 'userid' => $user2->id, 'roleid' => 0, 'timeend' => time(), 'statsreads' => 0, 'statswrites' => 0, 'stattype' => 'activity'],
            ['courseid' => $course3->id, 'userid' => $user2->id, 'roleid' => 0, 'timeend' => time(), 'statsreads' => 0, 'statswrites' => 0, 'stattype' => 'logins'],
            ['courseid' => $course3->id, 'userid' => $user2->id, 'roleid' => 0, 'timeend' => time(), 'statsreads' => 0, 'statswrites' => 0, 'stattype' => 'activity'],

            ['courseid' => $course1->id, 'userid' => $user3->id, 'roleid' => 0, 'timeend' => time(), 'statsreads' => 0, 'statswrites' => 0, 'stattype' => 'logins'],
            ['courseid' => $course1->id, 'userid' => $user3->id, 'roleid' => 0, 'timeend' => time(), 'statsreads' => 0, 'statswrites' => 0, 'stattype' => 'activity'],
            ['courseid' => $course2->id, 'userid' => $user3->id, 'roleid' => 0, 'timeend' => time(), 'statsreads' => 0, 'statswrites' => 0, 'stattype' => 'logins'],
            ['courseid' => $course2->id, 'userid' => $user3->id, 'roleid' => 0, 'timeend' => time(), 'statsreads' => 0, 'statswrites' => 0, 'stattype' => 'activity'],
            ['courseid' => $course3->id, 'userid' => $user3->id, 'roleid' => 0, 'timeend' => time(), 'statsreads' => 0, 'statswrites' => 0, 'stattype' => 'logins'],
            ['courseid' => $course3->id, 'userid' => $user3->id, 'roleid' => 0, 'timeend' => time(), 'statsreads' => 0, 'statswrites' => 0, 'stattype' => 'activity'],
        ];

        foreach ($data as $id => $record) {
            $dataobj = (object)$record;
            $DB->insert_record('stats_user_daily', $dataobj);
            $DB->insert_record('stats_user_weekly', $dataobj);
            $DB->insert_record('stats_user_monthly', $dataobj);
        }
        $total = count($data);
        $this->assertEquals($total, $DB->count_records('stats_user_daily'));
        $this->assertEquals($total, $DB->count_records('stats_user_weekly'));
        $this->assertEquals($total, $DB->count_records('stats_user_monthly'));

        return [$user1, $user2, $user3, $total];
    }

    /**
     * Test function is_purgeable.
     */
    public function test_is_purgeable() {
        $this->assertTrue(statistics::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(statistics::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(statistics::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * Test function is_exportable.
     */
    public function test_is_exportable() {
        $this->assertFalse(statistics::is_exportable());
    }

    /**
     * Test function is_countable.
     */
    public function test_is_countable() {
        $this->assertTrue(statistics::is_countable());
    }

    /**
     * Test counts and if stats are purged.
     */
    public function test_count_and_purge_data() {
        global $DB;
        $this->resetAfterTest(true);

        list($user1, $user2, $user3, $total) = $this->presetup();

        // Purge statistics data.
        $targetuser = new target_user($user1);
        $context = \context_system::instance();
        $countuser = statistics::execute_count($targetuser, $context);
        $result = statistics::execute_purge($targetuser, $context);
        $this->assertEquals(0, statistics::execute_count($targetuser, $context));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $this->assertCount((int)($total-$countuser), $DB->get_records('stats_user_monthly'));
        $users = $DB->get_records('stats_user_monthly', [], 'id');
        foreach ($users as $user) {
            $this->assertNotEmpty($user->stattype);
            $this->assertContains($user->userid, [$user2->id, $user3->id]);
        }
    }
}