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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package core_badges
 */

namespace core_badges\userdata;

use advanced_testcase;
use tool_usertours\target;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the connected connectedbackpacks userdata.
 *
 * @group totara_userdata
 */
class core_badges_userdata_backpack_testcase extends advanced_testcase {

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        global $CFG;

        require_once($CFG->dirroot . '/user/lib.php');
    }

    /**
     * Test issuebadges is purgeable in all statuses.
     */
    public function test_is_purgeable() {
        self::assertTrue(connectedbackpacks::is_purgeable(target_user::STATUS_ACTIVE));
        self::assertTrue(connectedbackpacks::is_purgeable(target_user::STATUS_DELETED));
        self::assertTrue(connectedbackpacks::is_purgeable(target_user::STATUS_SUSPENDED));
    }

    /**
     * Test checking context levels are compatible.
     */
    public function test_is_compatible_context() {
        self::assertTrue(connectedbackpacks::is_compatible_context_level(CONTEXT_SYSTEM));
        self::assertFalse(connectedbackpacks::is_compatible_context_level(CONTEXT_COURSECAT));
        self::assertFalse(connectedbackpacks::is_compatible_context_level(CONTEXT_COURSE));
        self::assertFalse(connectedbackpacks::is_compatible_context_level(CONTEXT_USER));
        self::assertFalse(connectedbackpacks::is_compatible_context_level(CONTEXT_MODULE));
        self::assertFalse(connectedbackpacks::is_compatible_context_level(CONTEXT_BLOCK));
        self::assertFalse(connectedbackpacks::is_compatible_context_level(CONTEXT_PROGRAM));
    }

    /**
     * Test compatible context levels
     */
    public function test_compatible_context_levels() {
        $expectedcontextlevels = [CONTEXT_SYSTEM];
        $this->assertEquals($expectedcontextlevels, connectedbackpacks::get_compatible_context_levels());
    }

    /**
     * Tests purge of connected backpacks.
     */
    public function test_purge_active_users() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        /** @var \core_badges_generator $badgegenerator */
        $badgegenerator = $generator->get_plugin_generator('core_badges');

        $user1 = $generator->create_user(['username' => 'test1']);
        $user2 = $generator->create_user(['username' => 'test2']);

        $result = connectedbackpacks::execute_purge(new target_user($user2), \context_system::instance());
        self::assertSame(item::RESULT_STATUS_SUCCESS, $result);

        $badgegenerator->create_backpack_connection($user1);
        $badgegenerator->create_backpack_connection($user2);

        $badgescache = \cache::make('core', 'externalbadges');

        self::assertFalse($badgescache->get($user1->id));
        self::assertFalse($badgescache->get($user2->id));

        self::assertTrue(badges_user_has_backpack($user1->id));
        self::assertTrue(badges_user_has_backpack($user2->id));

        $badgegenerator->mock_external_badges_in_cache($user1);
        $user1_settings = get_backpack_settings($user1->id);

        self::assertNotEmpty($badgescache->get($user1->id));
        self::assertFalse($badgescache->get($user2->id));

        $badgegenerator->mock_external_badges_in_cache($user2);
        get_backpack_settings($user2->id);

        self::assertNotEmpty($badgescache->get($user1->id));
        self::assertNotEmpty($badgescache->get($user2->id));

        $result = connectedbackpacks::execute_purge(new target_user($user2), \context_system::instance());
        self::assertSame(item::RESULT_STATUS_SUCCESS, $result);

        // Check that the cache has been purged.
        self::assertNotEmpty($badgescache->get($user1->id));
        self::assertFalse($badgescache->get($user2->id));

        self::assertEquals($user1_settings, get_backpack_settings($user1->id));
        self::assertNull(get_backpack_settings($user2->id));

        $result = connectedbackpacks::execute_purge(new target_user($user1), \context_system::instance());
        self::assertSame(item::RESULT_STATUS_SUCCESS, $result);

        self::assertFalse($badgescache->get($user1->id));
        self::assertFalse($badgescache->get($user2->id));

        self::assertNull(get_backpack_settings($user1->id));
        self::assertNull(get_backpack_settings($user2->id));
    }

    /**
     * Tests purge of connected backpacks for suspended users.
     */
    public function test_purge_suspended_users() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        /** @var \core_badges_generator $badgegenerator */
        $badgegenerator = $generator->get_plugin_generator('core_badges');

        $user1 = $generator->create_user(['username' => 'test1']);
        $user2 = $generator->create_user(['username' => 'test2']);

        $result = connectedbackpacks::execute_purge(new target_user($user2), \context_system::instance());
        self::assertSame(item::RESULT_STATUS_SUCCESS, $result);

        $badgegenerator->create_backpack_connection($user1);
        $badgegenerator->create_backpack_connection($user2);

        $badgescache = \cache::make('core', 'externalbadges');

        self::assertFalse($badgescache->get($user1->id));
        self::assertFalse($badgescache->get($user2->id));

        self::assertTrue(badges_user_has_backpack($user1->id));
        self::assertTrue(badges_user_has_backpack($user2->id));

        $badgegenerator->mock_external_badges_in_cache($user1);
        $user1_settings = get_backpack_settings($user1->id);

        self::assertNotEmpty($badgescache->get($user1->id));
        self::assertFalse($badgescache->get($user2->id));

        $badgegenerator->mock_external_badges_in_cache($user2);
        get_backpack_settings($user2->id);

        self::assertNotEmpty($badgescache->get($user1->id));
        self::assertNotEmpty($badgescache->get($user2->id));

        $user1 = $this->suspend_user_for_testing($user1);
        $user2 = $this->suspend_user_for_testing($user2);

        $result = connectedbackpacks::execute_purge(new target_user($user2), \context_system::instance());
        self::assertSame(item::RESULT_STATUS_SUCCESS, $result);

        // Check that the cache has been purged.
        self::assertNotEmpty($badgescache->get($user1->id));
        self::assertFalse($badgescache->get($user2->id));

        self::assertEquals($user1_settings, get_backpack_settings($user1->id));
        self::assertNull(get_backpack_settings($user2->id));

        $result = connectedbackpacks::execute_purge(new target_user($user1), \context_system::instance());
        self::assertSame(item::RESULT_STATUS_SUCCESS, $result);

        self::assertFalse($badgescache->get($user1->id));
        self::assertFalse($badgescache->get($user2->id));

        self::assertNull(get_backpack_settings($user1->id));
        self::assertNull(get_backpack_settings($user2->id));
    }


    /**
     * Tests purge of connected backpacks for suspended users.
     */
    public function test_purge_deleted_users() {
        global $DB;
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        /** @var \core_badges_generator $badgegenerator */
        $badgegenerator = $generator->get_plugin_generator('core_badges');

        $user1 = $generator->create_user(['username' => 'test1']);
        $user2 = $generator->create_user(['username' => 'test2']);

        $result = connectedbackpacks::execute_purge(new target_user($user2), \context_system::instance());
        self::assertSame(item::RESULT_STATUS_SUCCESS, $result);

        $badgegenerator->create_backpack_connection($user1);
        $badgegenerator->create_backpack_connection($user2);

        $badgescache = \cache::make('core', 'externalbadges');

        self::assertFalse($badgescache->get($user1->id));
        self::assertFalse($badgescache->get($user2->id));

        self::assertTrue(badges_user_has_backpack($user1->id));
        self::assertTrue(badges_user_has_backpack($user2->id));

        $badgegenerator->mock_external_badges_in_cache($user1);
        $user1_settings = get_backpack_settings($user1->id);

        self::assertNotEmpty($badgescache->get($user1->id));
        self::assertFalse($badgescache->get($user2->id));

        $badgegenerator->mock_external_badges_in_cache($user2);
        get_backpack_settings($user2->id);

        self::assertNotEmpty($badgescache->get($user1->id));
        self::assertNotEmpty($badgescache->get($user2->id));

        $user1 = $this->delete_user_for_testing($user1);
        $user2 = $this->delete_user_for_testing($user2);

        $result = connectedbackpacks::execute_purge(new target_user($user2), \context_system::instance());
        self::assertSame(item::RESULT_STATUS_SUCCESS, $result);

        // Check that the cache has been purged.
        self::assertNotEmpty($badgescache->get($user1->id));
        self::assertFalse($badgescache->get($user2->id));

        self::assertEquals($user1_settings, get_backpack_settings($user1->id));
        self::assertNull(get_backpack_settings($user2->id));

        $result = connectedbackpacks::execute_purge(new target_user($user1), \context_system::instance());
        self::assertSame(item::RESULT_STATUS_SUCCESS, $result);

        self::assertFalse($badgescache->get($user1->id));
        self::assertFalse($badgescache->get($user2->id));

        self::assertNull(get_backpack_settings($user1->id));
        self::assertNull(get_backpack_settings($user2->id));
    }

    /**
     * DO NOT COPY THIS TO PRODUCTION CODE!
     *
     * See user/action.php
     *
     * @param \stdClass $user
     * @return \stdClass The updated user object.
     */
    private function suspend_user_for_testing(\stdClass $user) {
        global $DB;
        $user->suspended = 1;
        // No need to end user sessions. DO NOT COPY THIS TO PRODUCTION CODE!
        user_update_user($user, false);
        \totara_core\event\user_suspended::create_from_user($user)->trigger();
        return $DB->get_record('user', ['id' => $user->id]);
    }

    /**
     * DO NOT COPY THIS TO PRODUCTION CODE!
     *
     * See user/action.php
     *
     * @param \stdClass $user
     * @return \stdClass The updated user object.
     */
    private function delete_user_for_testing(\stdClass $user) {
        global $DB;
        user_delete_user($DB->get_record('user', ['id' => $user->id]));
        return $DB->get_record('user', ['id' => $user->id]);
    }

    /**
     * Test that connected backpack info can be exported.
     */
    public function test_is_exportable() {
        self::assertTrue(connectedbackpacks::is_exportable());
    }

    /**
     * Tests export of connected backpacks.
     */
    public function test_export() {
        global $CFG;

        require_once($CFG->libdir . '/badgeslib.php');

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        /** @var \core_badges_generator $badgegenerator */
        $badgegenerator = $generator->get_plugin_generator('core_badges');

        $user1 = $generator->create_user(['username' => 'test1']);
        $user2 = $generator->create_user(['username' => 'test2']);

        $badgegenerator->create_backpack_connection($user1);

        $badgegenerator->mock_external_badges_in_cache($user1);

        $export = connectedbackpacks::execute_export(new target_user($user1), \context_system::instance());
        self::assertInstanceOf(export::class, $export);
        self::assertCount(1, $export->data);
        $backpack = reset($export->data);
        self::assertSame($user1->email, $backpack->email);
        self::assertSame($user1->id, $backpack->backpackuid);
        self::assertCount(1, $backpack->connectedcollections);
        self::assertEmpty($export->files);

        $export = connectedbackpacks::execute_export(new target_user($user2), \context_system::instance());
        self::assertInstanceOf(export::class, $export);
        self::assertEmpty($export->data);
        self::assertEmpty($export->files);
    }

    /**
     * Tests export of connected backpacks for suspended users.
     */
    public function test_export_of_suspended_users() {
        global $CFG;

        require_once($CFG->libdir . '/badgeslib.php');

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        /** @var \core_badges_generator $badgegenerator */
        $badgegenerator = $generator->get_plugin_generator('core_badges');

        $user1 = $generator->create_user(['username' => 'test1']);
        $user2 = $generator->create_user(['username' => 'test2']);

        $badgegenerator->create_backpack_connection($user1);

        $badgegenerator->mock_external_badges_in_cache($user1);

        $user1 = $this->suspend_user_for_testing($user1);
        $user2 = $this->suspend_user_for_testing($user2);

        $export = connectedbackpacks::execute_export(new target_user($user1), \context_system::instance());
        self::assertInstanceOf(export::class, $export);
        self::assertCount(1, $export->data);
        $backpack = reset($export->data);
        self::assertSame($user1->email, $backpack->email);
        self::assertSame($user1->id, $backpack->backpackuid);
        self::assertCount(1, $backpack->connectedcollections);
        self::assertEmpty($export->files);

        $export = connectedbackpacks::execute_export(new target_user($user2), \context_system::instance());
        self::assertInstanceOf(export::class, $export);
        self::assertEmpty($export->data);
        self::assertEmpty($export->files);
    }

    /**
     * Tests export of connected backpacks for deleted users.
     */
    public function test_export_of_deleted_users() {
        global $CFG;

        require_once($CFG->libdir . '/badgeslib.php');

        global $DB;
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        /** @var \core_badges_generator $badgegenerator */
        $badgegenerator = $generator->get_plugin_generator('core_badges');

        $user1 = $generator->create_user(['username' => 'test1']);
        $user2 = $generator->create_user(['username' => 'test2']);

        $badgegenerator->create_backpack_connection($user1);

        $badgegenerator->mock_external_badges_in_cache($user1);

        $user1 = $this->delete_user_for_testing($user1);
        $user2 = $this->delete_user_for_testing($user2);

        $export = connectedbackpacks::execute_export(new target_user($user1), \context_system::instance());
        self::assertInstanceOf(export::class, $export);
        self::assertCount(1, $export->data);
        $backpack = reset($export->data);
        self::assertSame('test1@example.com', $backpack->email);
        self::assertSame($user1->id, $backpack->backpackuid);
        self::assertCount(1, $backpack->connectedcollections);
        self::assertEmpty($export->files);

        $export = connectedbackpacks::execute_export(new target_user($user2), \context_system::instance());
        self::assertInstanceOf(export::class, $export);
        self::assertEmpty($export->data);
        self::assertEmpty($export->files);
    }

    /**
     * Test connected backpacks belonging to the user can be counted.
     */
    public function test_is_countable() {
        self::assertTrue(connectedbackpacks::is_countable());
    }

    /**
     * Test counting connected backpacks belonging to the user.
     */
    public function test_count() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        /** @var \core_badges_generator $badgegenerator */
        $badgegenerator = $generator->get_plugin_generator('core_badges');

        $user1 = $generator->create_user(['username' => 'test1']);
        $user2 = $generator->create_user(['username' => 'test2']);
        $context = \context_system::instance();

        $badgegenerator->create_backpack_connection($user1);

        self::assertSame(1, connectedbackpacks::execute_count(new target_user($user1), $context));
        self::assertSame(0, connectedbackpacks::execute_count(new target_user($user2), $context));
    }
}