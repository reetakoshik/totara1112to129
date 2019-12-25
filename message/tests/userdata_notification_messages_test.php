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
 * @package core_message
 */

namespace core_message\userdata;

use context_system;
use context_user;
use core_message_generator;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/userdata_messages_testcase.php');

/**
 * @group totara_userdata
 */
class core_message_userdata_notification_messages_testcase extends userdata_messages_testcase {

    /**
     * Testing the compatible context and is_[purgable|exportable|countable] methods
     */
    public function test_general_properties() {
        $this->assertEquals([CONTEXT_SYSTEM], notification_messages::get_compatible_context_levels());
        $this->assertTrue(notification_messages::is_exportable());
        $this->assertTrue(notification_messages::is_countable());
        $this->assertTrue(notification_messages::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(notification_messages::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(notification_messages::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * test if messages and according data are purged
     */
    public function test_purge() {
        $this->resetAfterTest(true);

        // Set up users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        // Set totara_program generator.
        /** @var core_message_generator $message_generator */
        $message_generator = $this->getDataGenerator()->get_plugin_generator('core_message');

        list($message1id, $message1readid) = $message_generator->create_message_data($user1->id, $user2->id, 'instantmessage');
        list($message2id, $message2readid) = $message_generator->create_message_data($user1->id, $user2->id, 'notification');
        list($message3id, $message3readid) = $message_generator->create_message_data($user1->id, $user2->id, 'confirmation');
        list($message4id, $message4readid) = $message_generator->create_message_data($user2->id, $user1->id, 'availableupdate');
        list($message5id, $message5readid) = $message_generator->create_message_data($user2->id, $user1->id, 'courserequested');
        list($message6id, $message6readid) = $message_generator->create_message_data($user2->id, $user3->id, 'instantmessage');
        list($message7id, $message7readid) = $message_generator->create_message_data($user2->id, $user3->id, 'notification');
        list($message8id, $message8readid) = $message_generator->create_message_data($user3->id, $user2->id, 'notification');

        // Check if expected data is there.
        $this->assert_message_exists($message1id);
        $this->assert_message_exists($message2id);
        $this->assert_message_exists($message3id);
        $this->assert_message_exists($message4id);
        $this->assert_message_exists($message5id);
        $this->assert_message_exists($message6id);
        $this->assert_message_exists($message7id);
        $this->assert_message_exists($message8id);

        $this->assert_message_read_exists($message1readid);
        $this->assert_message_read_exists($message2readid);
        $this->assert_message_read_exists($message3readid);
        $this->assert_message_read_exists($message4readid);
        $this->assert_message_read_exists($message5readid);
        $this->assert_message_read_exists($message6readid);
        $this->assert_message_read_exists($message7readid);
        $this->assert_message_read_exists($message8readid);

        $targetuser = new target_user($user1);
        // Purge data.
        $result = notification_messages::execute_purge($targetuser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        // Check if data for userid is deleted but for the others it's still there.
        $this->assert_message_not_exists($message2id);
        $this->assert_message_not_exists($message3id);
        $this->assert_message_not_exists($message4id);
        $this->assert_message_not_exists($message5id);

        // Other types than instant_message should be affected.
        $this->assert_message_exists($message1id);
        $this->assert_message_exists($message6id);
        // Messages not related to user still exist.
        $this->assert_message_exists($message7id);
        $this->assert_message_exists($message8id);

        $this->assert_message_read_not_exists($message2readid);
        $this->assert_message_read_not_exists($message3readid);
        $this->assert_message_read_not_exists($message4readid);
        $this->assert_message_read_not_exists($message5readid);

        // Other types than instant_message should be affected.
        $this->assert_message_read_exists($message1readid);
        $this->assert_message_read_exists($message6readid);
        // Messages not related to user still exist.
        $this->assert_message_read_exists($message7readid);
        $this->assert_message_read_exists($message8readid);
    }

    /**
     * test if messages and according data are purged
     */
    public function test_count() {
        $this->resetAfterTest(true);

        // Set up users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        // Set totara_program generator.
        /** @var core_message_generator $message_generator */
        $message_generator = $this->getDataGenerator()->get_plugin_generator('core_message');

        $message_generator->create_message_data($user1->id, $user2->id, 'instantmessage');
        $message_generator->create_message_data($user1->id, $user2->id, 'notification');
        $message_generator->create_message_data($user1->id, $user2->id, 'confirmation');
        $message_generator->create_message_data($user2->id, $user1->id, 'availableupdate');
        $message_generator->create_message_data($user2->id, $user1->id, 'courserequested');
        $message_generator->create_message_data($user2->id, $user3->id, 'instantmessage');
        $message_generator->create_message_data($user2->id, $user3->id, 'notification');
        $message_generator->create_message_data($user3->id, $user2->id, 'notification');

        // Count data.
        $targetuser = new target_user($user1);
        $result = notification_messages::execute_count($targetuser, context_system::instance());
        $this->assertEquals(8, $result);

        // Count data.
        $targetuser = new target_user($user2);
        $result = notification_messages::execute_count($targetuser, context_system::instance());
        $this->assertEquals(12, $result);

        // Count data.
        $targetuser = new target_user($user3);
        $result = notification_messages::execute_count($targetuser, context_system::instance());
        $this->assertEquals(4, $result);
    }

    /**
     * test if messages and according data are exported
     */
    public function test_export() {
        $this->resetAfterTest(true);

        // Set up users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        // Set totara_program generator.
        /** @var core_message_generator $message_generator */
        $message_generator = $this->getDataGenerator()->get_plugin_generator('core_message');

        list($message1id, $message1readid) = $message_generator->create_message_data($user1->id, $user2->id, 'instantmessage');
        list($message2id, $message2readid) = $message_generator->create_message_data($user1->id, $user2->id, 'notification');
        list($message3id, $message3readid) = $message_generator->create_message_data($user1->id, $user2->id, 'confirmation');
        list($message4id, $message4readid) = $message_generator->create_message_data($user2->id, $user1->id, 'availableupdate');
        list($message5id, $message5readid) = $message_generator->create_message_data($user2->id, $user1->id, 'courserequested');
        list($message6id, $message6readid) = $message_generator->create_message_data($user2->id, $user3->id, 'instantmessage');
        list($message7id, $message7readid) = $message_generator->create_message_data($user2->id, $user3->id, 'notification');
        list($message8id, $message8readid) = $message_generator->create_message_data($user3->id, $user2->id, 'notification');

        // Export notification messages of user 1.
        $targetuser = new target_user($user1);
        $result = notification_messages::execute_export($targetuser, context_system::instance());
        // Check the structure.
        $this->assertInstanceOf(export::class, $result);
        $this->assertArrayHasKey('unread', $result->data);
        $this->assertArrayHasKey('read', $result->data);

        $this->assertCount(4, $result->data['unread']);
        $this->assertArrayHasKey($message2id, $result->data['unread']);
        $this->assertArrayHasKey($message3id, $result->data['unread']);
        $this->assertArrayHasKey($message4id, $result->data['unread']);
        $this->assertArrayHasKey($message5id, $result->data['unread']);
        $this->assertCount(4, $result->data['read']);
        $this->assertArrayHasKey($message2readid, $result->data['read']);
        $this->assertArrayHasKey($message3readid, $result->data['read']);
        $this->assertArrayHasKey($message4readid, $result->data['read']);
        $this->assertArrayHasKey($message5readid, $result->data['read']);

        // Export notification messages of user 2.
        $targetuser = new target_user($user2);
        $result = notification_messages::execute_export($targetuser, context_system::instance());

        $this->assertCount(6, $result->data['unread']);
        $this->assertArrayHasKey($message2id, $result->data['unread']);
        $this->assertArrayHasKey($message3id, $result->data['unread']);
        $this->assertArrayHasKey($message4id, $result->data['unread']);
        $this->assertArrayHasKey($message5id, $result->data['unread']);
        $this->assertArrayHasKey($message7id, $result->data['unread']);
        $this->assertArrayHasKey($message8id, $result->data['unread']);
        $this->assertCount(6, $result->data['read']);
        $this->assertArrayHasKey($message3readid, $result->data['read']);
        $this->assertArrayHasKey($message3readid, $result->data['read']);
        $this->assertArrayHasKey($message4readid, $result->data['read']);
        $this->assertArrayHasKey($message5readid, $result->data['read']);
        $this->assertArrayHasKey($message7readid, $result->data['read']);
        $this->assertArrayHasKey($message8readid, $result->data['read']);

        // Export notification messages of user 3.
        $targetuser = new target_user($user3);
        $result = notification_messages::execute_export($targetuser, context_system::instance());

        $this->assertCount(2, $result->data['unread']);
        $this->assertArrayHasKey($message7id, $result->data['unread']);
        $this->assertArrayHasKey($message8id, $result->data['unread']);
        $this->assertCount(2, $result->data['read']);
        $this->assertArrayHasKey($message7readid, $result->data['read']);
        $this->assertArrayHasKey($message8readid, $result->data['read']);
    }

}
