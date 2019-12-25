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
 */

use totara_core\quickaccessmenu\external;
use totara_core\quickaccessmenu\group;

global $CFG;
require_once($CFG->dirroot . '/lib/adminlib.php');

/**
 * @group totara_core
 */
class totara_core_quickaccessmenu_external_testcase extends advanced_testcase {

    private function basic_setup() {
        $this->resetAfterTest(true);
        $roleid = $this->getDataGenerator()->create_role();
        $user = $this->getDataGenerator()->create_user();
        $capabilities = [
            'moodle/user:update',
            'moodle/role:assign',
            'moodle/role:manage',
            'totara/core:manageprofilefields',
            'moodle/restore:restorefile',
        ];
        foreach ($capabilities as $cap) {
            role_change_permission($roleid, context_system::instance(), $cap, CAP_ALLOW);
        }
        $this->getDataGenerator()->role_assign($roleid, $user->id);

        $this->setUser($user);
        admin_get_root(true, false); // Force the admin tree to reload.
        return $user;
    }

    public function test_get_user_menu() {
        $user = $this->basic_setup();

        $return = external::get_user_menu($user->id);
        $result = external::clean_returnvalue(external::get_user_menu_returns(), $return);

        self::assertEquals($return, $result);

        $expected = [
            'can_edit' => true,
            'can_search' => false,
            'empty_message' => get_string('quickaccessmenu:empty-message', 'totara_core', 'https://www.example.com/moodle/user/quickaccessmenu.php'),
            'groups'        => [
                [
                    'title'      => 'Core platform',
                    'has_items'  => true,
                    'item_count' => 1,
                    'items'      => [
                        [
                            'label' => 'Users',
                            'url'   => 'https://www.example.com/moodle/admin/user.php',
                        ],
                    ],
                ],
            ],
            'group_count'   => 1,
            'has_groups'    => true,
        ];

        self::assertEquals($expected, $result);
    }

    public function test_add_item() {
        $user = $this->basic_setup();

        $return = external::add_item('defineroles', group::LEARN, $user->id);
        self::assertEquals(
            [
                'key'   => 'defineroles',
                'page'  => 'Define roles',
                'label' => 'Define roles',
                'url'   => 'https://www.example.com/moodle/admin/roles/manage.php',
            ],
            $return
        );

        $result = external::clean_returnvalue(external::add_item_returns(), $return);
        self::assertEquals(
            [
                'key'   => 'defineroles',
                'page'  => 'Define roles',
                'label' => 'Define roles',
                'url'   => 'https://www.example.com/moodle/admin/roles/manage.php',
            ],
            $result
        );

        try {
            // Add unknown item to the menu.
            external::add_item('green', group::LEARN, $user->id);
            self::fail('Coding exception expected when adding invalid item key.');
        } catch (\moodle_exception $ex) {
            self::assertDebuggingCalled('Invalid item key specified green');
            self::assertInstanceOf('coding_exception', $ex);
            self::assertContains("The requested item 'green' cannot be added to the group 'learn'", $ex->getMessage());
        }
    }

    public function test_change_item_group() {
        $user = $this->basic_setup();

        $return = external::change_item_group('editusers', group::LEARN, $user->id);
        self::assertTrue($return);

        $result = external::clean_returnvalue(external::change_item_group_returns(), $return);
        self::assertTrue($result);

        $return = external::change_item_group('green', group::LEARN, $user->id);
        self::assertFalse($return);

        $result = external::clean_returnvalue(external::change_item_group_returns(), $return);
        self::assertDebuggingCalled('You cannot set the group of an item that it not included on your menu');
        self::assertFalse($result);
    }

    public function test_move_item_before() {
        $user = $this->basic_setup();

        self::assertEquals(
            [
                'key'   => 'defineroles',
                'page'  => 'Define roles',
                'label' => 'Define roles',
                'url'   => 'https://www.example.com/moodle/admin/roles/manage.php',
            ],
            external::add_item('defineroles', group::PLATFORM, $user->id)
        );
        $return = external::move_item_before('defineroles', 'editusers', $user->id);
        self::assertTrue($return);

        $result = external::clean_returnvalue(external::move_item_before_returns(), $return);
        self::assertTrue($result);

        $return = external::move_item_before('green', 'defineroles', $user->id);
        self::assertFalse($return);

        $result = external::clean_returnvalue(external::move_item_before_returns(), $return);
        self::assertDebuggingCalled('Unknown menu item key green');
        self::assertFalse($result);
    }

    public function test_reorder_items_in_group() {
        $user = $this->basic_setup();

        self::assertEquals(
            [
                'key'   => 'defineroles',
                'page'  => 'Define roles',
                'label' => 'Define roles',
                'url'   => 'https://www.example.com/moodle/admin/roles/manage.php',
            ],
            external::add_item('defineroles', group::PLATFORM, $user->id)
        );
        $return = external::reorder_items_in_group(group::PLATFORM, [['key' => 'defineroles'], ['key' => 'editusers']], $user->id);
        self::assertTrue($return);

        $result = external::clean_returnvalue(external::reorder_items_in_group_returns(), $return);
        self::assertTrue($result);
    }

    public function test_rename_item() {
        $user = $this->basic_setup();

        self::assertEquals(
            [
                'key'   => 'defineroles',
                'page'  => 'Define roles',
                'label' => 'Define roles',
                'url'   => 'https://www.example.com/moodle/admin/roles/manage.php',
            ],
            external::add_item('defineroles', group::PLATFORM, $user->id)
        );
        $return = external::rename_item('defineroles', 'My label', $user->id);
        self::assertTrue($return);

        $result = external::clean_returnvalue(external::rename_item_returns(), $return);
        self::assertTrue($result);
    }

    public function test_remove_item() {
        $user = $this->basic_setup();

        self::assertEquals(
            [
                'key'   => 'defineroles',
                'page'  => 'Define roles',
                'label' => 'Define roles',
                'url'   => 'https://www.example.com/moodle/admin/roles/manage.php',
            ],
            external::add_item('defineroles', group::PLATFORM, $user->id)
        );
        $return = external::remove_item('defineroles', $user->id);
        self::assertTrue($return);
        $result = external::clean_returnvalue(external::remove_item_returns(), $return);
        self::assertTrue($result);

        $return = external::remove_item('editusers', $user->id);
        self::assertTrue($return);
        $result = external::clean_returnvalue(external::remove_item_returns(), $return);
        self::assertTrue($result);

        $return = external::remove_item('green', $user->id);
        self::assertFalse($return);
        self::assertDebuggingCalled('Invalid item key specified green');
        $result = external::clean_returnvalue(external::remove_item_returns(), $return);
        self::assertFalse($result);
    }

    public function test_get_addable_items() {
        $user = $this->basic_setup();

        $return = external::get_addable_items($user->id);
        $result = external::clean_returnvalue(external::get_addable_items_returns(), $return);

        self::assertEquals($return, $result);

        $expected = [
            [
                'key'   => 'editusers',
                'label' => 'Browse list of users',
            ],
            [
                'key'   => 'userbulk',
                'label' => 'Bulk user actions',
            ],
            [
                'key'   => 'profilefields',
                'label' => 'User profile fields',
            ],
            [
                'key'   => 'defineroles',
                'label' => 'Define roles',
            ],
            [
                'key'   => 'assignroles',
                'label' => 'Assign system roles',
            ],
            [
                'key'   => 'checkpermissions',
                'label' => 'Check system permissions',
            ],
            [
                'key'   => 'roledefaults',
                'label' => 'Default role settings',
            ],
            [
                'key'   => 'toolcapability',
                'label' => 'Capability overview',
            ],
            [
                'key'   => 'restorecourse',
                'label' => 'Restore course',
            ],
        ];

        foreach ($result as &$item) {
            unset($item['weight']);
        }

        self::assertEquals($expected, $result);
    }

    public function test_add_group() {
        $user = $this->basic_setup();

        $return = external::add_group('Group 1', $user->id);
        self::assertNotEmpty($return);
        self::assertContains($return['key'], group::get_group_keys($user->id));

        $result = external::clean_returnvalue(external::add_group_returns(), $return);
        self::assertNotEmpty($result);
    }

    public function test_rename_group() {
        $user = $this->basic_setup();

        $return = external::rename_group(group::PLATFORM, 'My group title', $user->id);
        self::assertTrue($return);

        $result = external::clean_returnvalue(external::rename_group_returns(), $return);
        self::assertTrue($result);
    }

    public function test_remove_group() {
        $user = $this->basic_setup();

        // Remove one of the default groups.
        $return = external::remove_group(group::PLATFORM, $user->id);
        self::assertTrue($return);

        $result = external::clean_returnvalue(external::remove_group_returns(), $return);
        self::assertTrue($result);

        // Remove non-existing group.
        $return = external::remove_group('somegroup', $user->id);
        self::assertFalse($return);

        $result = external::clean_returnvalue(external::remove_group_returns(), $return);
        self::assertFalse($result);
    }

    public function test_move_group_before() {
        $user = $this->basic_setup();

        // Move around one of the default groups.
        $return = external::move_group_before(group::CONFIGURATION, group::PLATFORM, $user->id);
        self::assertTrue($return);

        $result = external::clean_returnvalue(external::move_group_before_returns(), $return);
        self::assertTrue($result);

        // Move non-existing group.
        $return = external::move_group_before('somegroup', group::PLATFORM, $user->id);
        self::assertDebuggingCalled('Unknown menu group key somegroup');
        self::assertFalse($return);

        $result = external::clean_returnvalue(external::move_group_before_returns(), $return);
        self::assertFalse($result);
    }

    public function test_reorder_groups() {
        $user = $this->basic_setup();

        $return = external::reorder_groups([['key' => group::PLATFORM], ['key' => group::LEARN], ['key' => group::PERFORM], ['key' => group::CONFIGURATION]], $user->id);
        self::assertTrue($return);

        $result = external::clean_returnvalue(external::reorder_groups_returns(), $return);
        self::assertTrue($result);
    }

}
