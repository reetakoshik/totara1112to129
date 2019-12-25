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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_core
 */

use totara_core\userdata\external_tokens;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Test setting to keep or delete external_tokens
 *
 * @group totara_userdata
 */
class totara_core_userdata_external_tokens_testcase extends advanced_testcase {

    /**
     * test if data is correctly purged
     */
    public function test_purge() {
        global $DB;

        $this->resetAfterTest(true);
        list($user1, $user2) = $this->create_user_fixtures();

        // Get the expected data.
        $this->assertNotEmpty($DB->get_records('external_tokens', ['userid' => $user1->id]));

        $targetuser = new target_user($user1);
        // Purge data.
        $result = external_tokens::execute_purge($targetuser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        // Check the results. Entry is gone.
        $this->assertEmpty($DB->get_records('external_tokens', ['userid' => $user1->id]));
        // Check if second users record is untouched.
        $this->assertNotEmpty($DB->get_records('external_tokens', ['userid' => $user2->id]));
    }

    /**
     * test if data is correctly exported
     */
    public function test_count() {
        $this->resetAfterTest(true);
        list($user1, $user2) = $this->create_user_fixtures();

        $targetuser = new target_user($user1);
        // Count data.
        $result = external_tokens::execute_count($targetuser, context_system::instance());
        $this->assertEquals(2, $result);
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

        // Set up context.
        $context1 = context_user::instance($user1->id);
        $context2 = context_user::instance($user1->id);

        // Create new webservice.
        $webservicemanager = new webservice();
        $serviceid1 = $webservicemanager->add_external_service((object) [
            'name' => 'test service1',
            'enabled' => 1,
            'restrictedusers' => 0,
            'downloadfiles' => 0,
            'uploadfiles' => 0,
        ]);
        $serviceid2 = $webservicemanager->add_external_service((object) [
            'name' => 'test service2',
            'enabled' => 1,
            'restrictedusers' => 0,
            'downloadfiles' => 0,
            'uploadfiles' => 0,
        ]);

        // Generate new token.
        external_generate_token(EXTERNAL_TOKEN_PERMANENT, $serviceid1, $user1->id, $context1);
        external_generate_token(EXTERNAL_TOKEN_PERMANENT, $serviceid2, $user1->id, $context1);
        external_generate_token(EXTERNAL_TOKEN_PERMANENT, $serviceid1, $user2->id, $context2);

        return [$user1, $user2];
    }

}