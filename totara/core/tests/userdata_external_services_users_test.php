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
 * @package totara_core
 */

use totara_core\userdata\external_services_users;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Test setting to keep or delete external_services_users
 *
 * @group totara_userdata
 */
class totara_core_userdata_external_services_users_testcase extends advanced_testcase {

    /**
     * test if data is correctly purged
     */
    public function test_purge() {
        global $DB;

        $this->resetAfterTest(true);
        list($user1, $user2) = $this->create_user_fixtures();

        // Get the expected data.
        $expectedserviceuser = $DB->get_record('external_services_users', ['userid' => $user1->id]);
        $this->assertEquals($user1->id, $expectedserviceuser->userid);

        $targetuser = new target_user($user1);
        // Purge data.
        $result = external_services_users::execute_purge($targetuser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $actualserviceuser = $DB->get_record('external_services_users', ['userid' => $user1->id]);
        // Check the results. Entry is gone.
        $this->assertEmpty($actualserviceuser);

        // Check if second users record is untouched.
        $actualserviceuser = $DB->get_record('external_services_users', ['userid' => $user2->id]);
        $this->assertEquals($actualserviceuser->userid, $user2->id);
    }

    /**
     * test if data is correctly counted
     */
    public function test_count() {
        $this->resetAfterTest(true);
        list($user1, $user2) = $this->create_user_fixtures();
        $user3 = $this->getDataGenerator()->create_user();

        // Count data.
        $targetuser = new target_user($user1);
        $result = external_services_users::execute_count($targetuser, context_system::instance());
        $this->assertEquals(1, $result);

        // Count data (User 3 does not have an entry).
        $targetuser = new target_user($user3);
        $result = external_services_users::execute_count($targetuser, context_system::instance());
        $this->assertEquals(0, $result);
    }

    /**
     * Create necessary fixtures
     *
     * @return array($user1, $user2)
     */
    private function create_user_fixtures(): array {
        global $CFG;

        require_once($CFG->dirroot . '/webservice/lib.php');

        // Set up users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Create new webservice.
        $webservicemanager = new webservice();
        $serviceid = $webservicemanager->add_external_service((object) [
            'name' => 'test service',
            'enabled' => 1,
            'restrictedusers' => 0,
            'downloadfiles' => 0,
            'uploadfiles' => 0,
        ]);

        // Assign users to service.
        $webservicemanager->add_ws_authorised_user((object)[
            'externalserviceid' => $serviceid,
            'userid' => $user1->id
        ]);
        $webservicemanager->add_ws_authorised_user((object)[
            'externalserviceid' => $serviceid,
            'userid' => $user2->id
        ]);

        return [$user1, $user2];
    }

}