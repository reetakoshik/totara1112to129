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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core_user
 */

namespace core_user\userdata;

use advanced_testcase;
use context_system;
use context_user;
use stdClass;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_userdata
 */
class core_user_userdata_devices_testcase extends advanced_testcase {

    /**
     * test if user devices are purged properly
     */
    public function test_purge() {
        global $DB;

        $this->resetAfterTest(true);

        // Set up users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $deviceid1 = $this->create_user_device($user1);
        $deviceid2 = $this->create_user_device($user2);
        $deviceid3 = $this->create_user_device($user3);

        $DB->insert_record('message_airnotifier_devices', (object)['userdeviceid' => $deviceid1, 'enable' => 1]);
        $DB->insert_record('message_airnotifier_devices', (object)['userdeviceid' => $deviceid2, 'enable' => 1]);
        $DB->insert_record('message_airnotifier_devices', (object)['userdeviceid' => $deviceid3, 'enable' => 1]);

        $this->assertEquals(3, $DB->count_records('user_devices'));
        $this->assertEquals(3, $DB->count_records('message_airnotifier_devices'));

        $targetuser = new target_user($user1);
        // Purge data.
        $result = devices::execute_purge($targetuser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $this->assertEquals(2, $DB->count_records('user_devices'));
        $this->assertEquals(2, $DB->count_records('message_airnotifier_devices'));

        // User 1's entry should be gone.
        $this->assertEmpty($DB->get_record('message_airnotifier_devices', ['userdeviceid' => $deviceid1]));
        $this->assertEmpty($DB->get_record('user_devices', ['userid' => $user1->id]));
        // User 2 and 3's data should still be there.
        $this->assertNotEmpty($DB->get_record('message_airnotifier_devices', ['userdeviceid' => $deviceid2]));
        $this->assertNotEmpty($DB->get_record('message_airnotifier_devices', ['userdeviceid' => $deviceid3]));
        $this->assertNotEmpty($DB->get_record('user_devices', ['userid' => $user2->id]));
        $this->assertNotEmpty($DB->get_record('user_devices', ['userid' => $user3->id]));
    }

    /**
     * test if user devices are correctly counted
     */
    public function test_count() {
        global $DB;

        $this->resetAfterTest(true);

        // Set up users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $deviceid11 = $this->create_user_device($user1);
        $deviceid21 = $this->create_user_device($user2);
        $deviceid22 = $this->create_user_device($user2);
        $deviceid31 = $this->create_user_device($user3);
        $deviceid32 = $this->create_user_device($user3);
        $deviceid33 = $this->create_user_device($user3);

        $DB->insert_record('message_airnotifier_devices', (object)['userdeviceid' => $deviceid11, 'enable' => 1]);
        $DB->insert_record('message_airnotifier_devices', (object)['userdeviceid' => $deviceid21, 'enable' => 1]);
        $DB->insert_record('message_airnotifier_devices', (object)['userdeviceid' => $deviceid22, 'enable' => 1]);
        $DB->insert_record('message_airnotifier_devices', (object)['userdeviceid' => $deviceid31, 'enable' => 1]);
        $DB->insert_record('message_airnotifier_devices', (object)['userdeviceid' => $deviceid32, 'enable' => 1]);
        $DB->insert_record('message_airnotifier_devices', (object)['userdeviceid' => $deviceid33, 'enable' => 1]);

        // Count data.
        $targetuser = new target_user($user1);
        $result = devices::execute_count($targetuser, context_system::instance());
        $this->assertEquals(1, $result);

        // Count data.
        $targetuser = new target_user($user2);
        $result = devices::execute_count($targetuser, context_system::instance());
        $this->assertEquals(2, $result);

        // Count data.
        $targetuser = new target_user($user3);
        $result = devices::execute_count($targetuser, context_system::instance());
        $this->assertEquals(3, $result);
    }

    /**
     * create a new user device entry which is needed by the airnotifier messages
     *
     * @param stdClass $user
     * @return int
     */
    private function create_user_device(stdClass $user): int {
        global $DB;

        $deviceid = $DB->insert_record('user_devices', (object)[
            'userid' => $user->id,
            'appid' => 'appid',
            'name' => 'name',
            'model' => 'model',
            'platform' => 'platform',
            'version' => 'version',
            'pushid' => uniqid(),
            'uuid' => generate_uuid(),
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        return $deviceid;
    }

}
