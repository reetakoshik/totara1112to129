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

use totara_core\quickaccessmenu\factory;
use totara_core\quickaccessmenu\group;
use totara_core\quickaccessmenu\item;
use totara_core\quickaccessmenu\helper;
use totara_core\quickaccessmenu\preference_helper;
use totara_core\quickaccessmenu\provider;

global $CFG;
require_once($CFG->dirroot . '/lib/adminlib.php');

/**
 * @group totara_core
 */
class totara_core_quickaccessmenu_testcase extends advanced_testcase {

    private static function assertNodeOrder($expected, $items) {
        $labels = [];
        $keys = [];
        foreach ($items as $item) {
            /** @var item $item */
            $labels[] = $item->get_label();
            $keys[] = $item->get_key();
        }
        self::assertEquals(join("\n", array_values($expected)), join("\n", $labels));
        self::assertEquals(join("\n", array_keys($expected)), join("\n", $keys));
        self::assertSame(count($expected), count($labels)); // Just here to detect empties.
        self::assertSame(count($expected), count($keys)); // Just here to detect empties.
    }

    /**
     * Tests menu structure against a given structure for a user
     *
     * @param array    $expectedstructure
     * @param stdClass $user
     * @param bool     $includeemptygroups
     *
     * @throws coding_exception
     */
    private static function assertMenuStructure(array $expectedstructure, \stdClass $user, bool $includeemptygroups = false) {
        $factory = factory::instance($user->id);
        $menu = $factory->get_menu();
        $groups = self::menu_get_items_by_group($user->id, $menu->get_items(), $includeemptygroups);
        $expectedgroups = array_keys($expectedstructure);
        $actualgroups = array_keys($groups);
        self::assertEquals($expectedgroups, $actualgroups);

        foreach ($expectedgroups as $group) {
            self::assertNodeOrder($expectedstructure[$group], $groups[$group]);
        }
    }

    /**
     * @param int   $userid
     * @param array $items
     * @param bool  $includeemptygroups
     *
     * @return array
     */
    private static function menu_get_items_by_group(int $userid, array $items, bool $includeemptygroups = false): array {
        $groups = [];
        foreach (group::get_group_keys($userid) as $group) {
            $groups[$group] = [];
        }
        foreach ($items as $item) {
            $group = $item->get_group();
            $groups[$group][] = $item;
        }
        foreach ($groups as $group => &$items) {
            if (empty($items) && !$includeemptygroups) {
                unset($groups[$group]);
                continue;
            }
            usort($items, [item::class, 'sort_items']);
        }
        return $groups;
    }

    public function test_get_user_preference_menu() {
        global $USER;

        // Needed because of capability checks.
        $this->setAdminUser();
        $this->resetAfterTest();

        admin_get_root(true, false); // Force the admin tree to reload.

        $factory = factory::instance($USER->id);
        $menu = $factory->get_user_preference_menu();
        self::assertInstanceOf(\totara_core\quickaccessmenu\menu::class, $menu);
        self::assertEmpty($menu->get_all_items());

        self::assertTrue(helper::add_item($USER->id, 'editusers', group::get(group::CONFIGURATION)));

        $menu = $factory->get_user_preference_menu();
        self::assertInstanceOf(\totara_core\quickaccessmenu\menu::class, $menu);
        self::assertNotEmpty($menu->get_all_items());

        preference_helper::unset_preference($USER->id, 'items');

        $menu = $factory->get_user_preference_menu();
        self::assertInstanceOf(\totara_core\quickaccessmenu\menu::class, $menu);
        self::assertEmpty($menu->get_all_items());

        preference_helper::set_preference($USER->id, 'items', 'Bung data');

        self::assertSame('Bung data', preference_helper::get_preference($USER->id, 'items', false));
        $menu = $factory->get_user_preference_menu();
        self::assertDebuggingCalled('Invalid user preference stored for quickaccessmenu');
        self::assertInstanceOf(\totara_core\quickaccessmenu\menu::class, $menu);
        self::assertEmpty($menu->get_all_items());
        self::assertFalse(preference_helper::get_preference($USER->id, 'items', false));
    }

    public function test_default_menu_for_admin() {
        if ($this->is_addon_with_item_present()) {
            $this->markTestSkipped('Test is for standard distribution only.');
        }

        // Needed because of capability checks.
        $this->setAdminUser();
        $this->resetAfterTest();

        admin_get_root(true, false); // Force the admin tree to reload.

        $user = get_admin();

        self::assertMenuStructure([
            group::PLATFORM => [
                'editusers' => 'Users',
                'cohorts' => 'Audiences',
                'userpolicies' => 'Permissions',
                'positionmanage' => 'Positions',
                'organisationmanage' => 'Organisations',
                'managebadges' => 'Badges',
                'competencymanage' => 'Competencies',
                'rbmanagereports' => 'Reports',
            ],
            group::LEARN => [
                'coursemgmt' => 'Courses and categories',
                'programmgmt' => 'Programs',
                'managecertifications' => 'Certifications',
                'modsettingfacetoface' => 'Seminars',
                'gradessettings' => 'Grades',
                'managetemplates' => 'Learning Plans',
                'setup_content_marketplaces' => 'Content Marketplace'
            ],
            group::PERFORM => [
                'manageappraisals' => 'Appraisals',
                'managefeedback360' => '360° Feedback',
                'goalmanage' => 'Goals',
            ],
            group::CONFIGURATION => [
                'themesettings' => 'Appearance',
                'navigation' => 'Navigation',
                'pluginsoverview' => 'Plugins',
                'sitepolicies' => 'Security',
                'totarasyncsettings' => 'HR Import',
                'langsettings' => 'Localisation',
                'environment' => 'Server',
                'debugging' => 'Development',
                'optionalsubsystems' => 'Advanced features',
                'adminnotifications' => 'System information',
            ]
        ], $user);
    }

    public function test_default_menu_for_site_manager() {
        global $DB;

        if ($this->is_addon_with_item_present()) {
            $this->markTestSkipped('Test is for standard distribution only.');
        }

        $this->resetAfterTest();

        $roleid = $DB->get_field('role', 'id', ['shortname' => 'manager']);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->role_assign($roleid, $user->id);

        $this->setUser($user);
        admin_get_root(true, false); // Force the admin tree to reload.

        self::assertMenuStructure([
            group::PLATFORM => [
                'editusers' => 'Users',
                'cohorts' => 'Audiences',
                'positionmanage' => 'Positions',
                'organisationmanage' => 'Organisations',
                'managebadges' => 'Badges',
                'competencymanage' => 'Competencies',
                'rbmanagereports' => 'Reports',
            ],
            group::LEARN => [
                'coursemgmt' => 'Courses and categories',
                'programmgmt' => 'Programs',
                'managecertifications' => 'Certifications',
                'gradessettings' => 'Grades',
                'managetemplates' => 'Learning Plans',
                'setup_content_marketplaces' => 'Content Marketplace'
            ],
            group::PERFORM => [
                'manageappraisals' => 'Appraisals',
                'managefeedback360' => '360° Feedback',
                'goalmanage' => 'Goals',
            ],
            group::CONFIGURATION => [
                'totarasyncsettings' => 'HR Import',
            ]
        ], $user);

    }

    public function test_default_menu_for_staff_manager() {
        global $DB;

        if ($this->is_addon_with_item_present()) {
            $this->markTestSkipped('Test is for standard distribution only.');
        }

        $this->resetAfterTest();

        $roleid = $DB->get_field('role', 'id', ['shortname' => 'staffmanager']);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->role_assign($roleid, $user->id);

        $this->setUser($user);
        admin_get_root(true, false); // Force the admin tree to reload.

        self::assertMenuStructure([
            group::PLATFORM => [
                'cohorts' => 'Audiences',
            ]
        ], $user);

    }

    public function test_default_menu_for_course_creator() {
        global $DB;

        if ($this->is_addon_with_item_present()) {
            $this->markTestSkipped('Test is for standard distribution only.');
        }

        $this->resetAfterTest();

        $roleid = $DB->get_field('role', 'id', ['shortname' => 'coursecreator']);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->role_assign($roleid, $user->id);

        $this->setUser($user);
        admin_get_root(true, false); // Force the admin tree to reload.

        self::assertMenuStructure([
            group::LEARN => [
                'coursemgmt' => 'Courses and categories',
            ]
        ], $user);

    }

    public function test_default_menu_for_learner() {
        global $DB;

        if ($this->is_addon_with_item_present()) {
            $this->markTestSkipped('Test is for standard distribution only.');
        }

        $this->resetAfterTest();

        $roleid = $DB->get_field('role', 'id', ['shortname' => 'student']);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->role_assign($roleid, $user->id);

        $this->setUser($user);
        admin_get_root(true, false); // Force the admin tree to reload.

        self::assertMenuStructure([], $user);
    }

    public function test_default_menu_for_custom_role() {
        if ($this->is_addon_with_item_present()) {
            $this->markTestSkipped('Test is for standard distribution only.');
        }

        $this->resetAfterTest();

        $roleid = $this->getDataGenerator()->create_role();
        $user = $this->getDataGenerator()->create_user();
        role_change_permission($roleid, context_system::instance(), 'totara/reportbuilder:managereports', CAP_ALLOW);
        $this->getDataGenerator()->role_assign($roleid, $user->id);

        $this->setUser($user);
        admin_get_root(true, false); // Force the admin tree to reload.

        self::assertMenuStructure([
            group::PLATFORM => [
                'rbmanagereports' => 'Reports',
            ]
        ], $user);

    }

    public function test_default_menu_for_custom_role_with_access_but_no_default() {
        if ($this->is_addon_with_item_present()) {
            $this->markTestSkipped('Test is for standard distribution only.');
        }

        $this->resetAfterTest();

        $roleid = $this->getDataGenerator()->create_role();
        $user = $this->getDataGenerator()->create_user();
        $capabilities = [
            'moodle/role:assign',
            'moodle/role:manage',
            'totara/core:manageprofilefields',
        ];
        foreach ($capabilities as $cap) {
            role_change_permission($roleid, context_system::instance(), $cap, CAP_ALLOW);
        }
        $this->getDataGenerator()->role_assign($roleid, $user->id);

        $this->setUser($user);
        $admin = admin_get_root(true, false); // Force the admin tree to reload.

        self::assertMenuStructure([], $user);

        $visible = $this->recurse_visible_admin_nodes($admin);
        sort($visible);

        self::assertNotEmpty($visible);
    }

    public function test_menu_manipulation_add() {
        $roleid = $this->getDataGenerator()->create_role();
        $user = $this->getDataGenerator()->create_user();
        $capabilities = [
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
        $admin = admin_get_root(true, false); // Force the admin tree to reload.

        self::assertMenuStructure([], $user);

        $visible = $this->recurse_visible_admin_nodes($admin);
        sort($visible);

        self::assertNotEmpty($visible);

        $possibilities = helper::get_addable_items($user->id);
        $actual = [];
        foreach ($possibilities as $item) {
            $actual[] = $item->get_key();
        }
        sort($actual);

        self::assertEquals($visible, $actual);
        self::assertSame('assignroles,checkpermissions,defineroles,profilefields,restorecourse,roledefaults,toolcapability', join(',', $actual));

        self::assertTrue(helper::add_item($user->id, 'restorecourse', group::get(group::LEARN)));
        self::assertTrue(helper::add_item($user->id, 'checkpermissions', group::get(group::CONFIGURATION)));
        self::assertTrue(helper::add_item($user->id, 'profilefields', group::get(group::PLATFORM)));

        self::assertFalse(helper::add_item($user->id, 'totararegistration', group::get(group::PLATFORM)));
        self::assertDebuggingCalled('Invalid item key specified totararegistration');

        self::assertMenuStructure([
            group::PLATFORM => [
                'profilefields' => 'User profile fields',
            ],
            group::LEARN => [
                'restorecourse' => 'Restore course',
            ],
            group::CONFIGURATION => [
                'checkpermissions' => 'Check system permissions',
            ],
        ], $user);

        // Test that the possibilities have been reduced.
        $actual = [];
        foreach (helper::get_addable_items($user->id) as $item) {
            $actual[] = $item->get_key();
        }
        sort($actual);
        self::assertSame('assignroles,defineroles,roledefaults,toolcapability', join(',', $actual));
    }

    public function test_menu_manipulation_change_group() {
        $roleid = $this->getDataGenerator()->create_role();
        $user = $this->getDataGenerator()->create_user();
        $capabilities = [
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
        $admin = admin_get_root(true, false); // Force the admin tree to reload.

        self::assertMenuStructure([], $user);

        $visible = $this->recurse_visible_admin_nodes($admin);
        sort($visible);

        self::assertNotEmpty($visible);

        $possibilities = helper::get_addable_items($user->id);
        $actual = [];
        foreach ($possibilities as $item) {
            $actual[] = $item->get_key();
        }
        sort($actual);

        self::assertTrue(helper::add_item($user->id, 'restorecourse', group::get(group::LEARN)));
        self::assertTrue(helper::add_item($user->id, 'checkpermissions', group::get(group::CONFIGURATION)));
        self::assertTrue(helper::add_item($user->id, 'profilefields', group::get(group::PLATFORM)));

        self::assertTrue(helper::change_item_group($user->id, 'profilefields', group::get(group::LEARN)));

        self::assertMenuStructure([
            group::LEARN => [
                'restorecourse' => 'Restore course',
                'profilefields' => 'User profile fields',
            ],
            group::CONFIGURATION => [
                'checkpermissions' => 'Check system permissions',
            ],
        ], $user);

        // Change the group of one item.
        self::assertTrue(helper::change_item_group($user->id, 'checkpermissions', group::get(group::LEARN)));

        self::assertMenuStructure([
            group::LEARN => [
                'restorecourse' => 'Restore course',
                'profilefields' => 'User profile fields',
                'checkpermissions' => 'Check system permissions',
            ],
        ], $user);

        // Change the group of all items.
        self::assertTrue(helper::change_item_group($user->id, 'checkpermissions', group::get(group::PLATFORM)));
        self::assertTrue(helper::change_item_group($user->id, 'profilefields', group::get(group::PLATFORM)));
        self::assertTrue(helper::change_item_group($user->id, 'restorecourse', group::get(group::PLATFORM)));

        self::assertMenuStructure([
            group::PLATFORM => [
                'checkpermissions' => 'Check system permissions',
                'profilefields' => 'User profile fields',
                'restorecourse' => 'Restore course',
            ],
        ], $user);

        // Change the group of an item we won't track yet.
        self::assertFalse(helper::change_item_group($user->id, 'roledefaults', group::PLATFORM));
        self::assertDebuggingCalled('You cannot set the group of an item that it not included on your menu');

        self::assertMenuStructure([
            group::PLATFORM => [
                'checkpermissions' => 'Check system permissions',
                'profilefields' => 'User profile fields',
                'restorecourse' => 'Restore course',
            ],
        ], $user);

        // Change the group for a default item.
        $roleid = $this->getDataGenerator()->create_role();
        role_change_permission($roleid, context_system::instance(), 'totara/reportbuilder:managereports', CAP_ALLOW);
        $this->getDataGenerator()->role_assign($roleid, $user->id);
        admin_get_root(true, false);

        self::assertMenuStructure([
            group::PLATFORM => [
                'checkpermissions' => 'Check system permissions',
                'profilefields' => 'User profile fields',
                'restorecourse' => 'Restore course',
                'rbmanagereports' => 'Reports',
            ],
        ], $user);

        self::assertTrue(helper::change_item_group($user->id, 'rbmanagereports', group::LEARN));

        self::assertMenuStructure([
            group::PLATFORM => [
                'checkpermissions' => 'Check system permissions',
                'profilefields' => 'User profile fields',
                'restorecourse' => 'Restore course',
            ],
            group::LEARN => [
                'rbmanagereports' => 'Reports',
            ],
        ], $user);

        self::assertTrue(helper::remove_item($user->id, 'rbmanagereports'));

        self::assertMenuStructure([
            group::PLATFORM => [
                'checkpermissions' => 'Check system permissions',
                'profilefields' => 'User profile fields',
                'restorecourse' => 'Restore course',
            ],
        ], $user);
    }

    public function test_menu_manipulation_change_order() {
        $roleid = $this->getDataGenerator()->create_role();
        $user = $this->getDataGenerator()->create_user();
        $capabilities = [
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
        $admin = admin_get_root(true, false); // Force the admin tree to reload.

        self::assertMenuStructure([], $user);

        $visible = $this->recurse_visible_admin_nodes($admin);
        sort($visible);

        self::assertNotEmpty($visible);

        $possibilities = helper::get_addable_items($user->id);
        $actual = [];
        foreach ($possibilities as $item) {
            $actual[] = $item->get_key();
        }
        sort($actual);

        self::assertTrue(helper::add_item($user->id, 'checkpermissions', group::get(group::PLATFORM)));
        self::assertTrue(helper::add_item($user->id, 'profilefields', group::get(group::PLATFORM)));
        self::assertTrue(helper::add_item($user->id, 'restorecourse', group::get(group::PLATFORM)));

        $method = new ReflectionMethod(helper::class, 'move_item_to_bottom');
        $method->setAccessible(true);

        self::assertMenuStructure([
            group::PLATFORM => [
                'checkpermissions' => 'Check system permissions',
                'profilefields' => 'User profile fields',
                'restorecourse' => 'Restore course',
            ],
        ], $user);

        self::assertTrue(helper::reorder_items_in_group($user->id, group::get(group::PLATFORM), ['checkpermissions', 'profilefields', 'restorecourse']));

        self::assertMenuStructure([
            group::PLATFORM => [
                'checkpermissions' => 'Check system permissions',
                'profilefields' => 'User profile fields',
                'restorecourse' => 'Restore course',
            ],
        ], $user);

        self::assertTrue(helper::reorder_items_in_group($user->id, group::get(group::PLATFORM), ['profilefields', 'checkpermissions', 'restorecourse']));

        self::assertMenuStructure([
            group::PLATFORM => [
                'profilefields' => 'User profile fields',
                'checkpermissions' => 'Check system permissions',
                'restorecourse' => 'Restore course',
            ],
        ], $user);

        $result = $method->invoke(null, $user->id, 'profilefields');
        self::assertTrue($result);

        self::assertMenuStructure([
            group::PLATFORM => [
                'checkpermissions' => 'Check system permissions',
                'restorecourse' => 'Restore course',
                'profilefields' => 'User profile fields',
            ],
        ], $user);

        self::assertTrue(helper::move_item_before($user->id, 'restorecourse', 'checkpermissions'));

        self::assertMenuStructure([
            group::PLATFORM => [
                'restorecourse' => 'Restore course',
                'checkpermissions' => 'Check system permissions',
                'profilefields' => 'User profile fields',
            ],
        ], $user);

        self::assertTrue(helper::reorder_items_in_group($user->id, group::get(group::PLATFORM), ['restorecourse', 'profilefields', 'checkpermissions']));

        self::assertMenuStructure([
            group::PLATFORM => [
                'restorecourse' => 'Restore course',
                'profilefields' => 'User profile fields',
                'checkpermissions' => 'Check system permissions',
            ],
        ], $user);

        // Reorder across groups doesn't work.
        self::assertTrue(helper::change_item_group($user->id, 'restorecourse', group::LEARN));
        self::assertFalse(helper::move_item_before($user->id, 'profilefields', 'restorecourse'));
        self::assertDebuggingCalled('Items are in different groups, item is in platform and before is in learn');
        self::assertTrue(helper::change_item_group($user->id, 'restorecourse', group::PLATFORM));
        self::assertTrue(helper::move_item_before($user->id, 'restorecourse', 'profilefields'));

        // Reorder with the wrong number of items.
        self::assertFalse(helper::reorder_items_in_group($user->id, group::get(group::PLATFORM), ['green']));
        self::assertDebuggingCalled('Invalid number of items provided, expected 3 got 1');

        // Reorder with invalid items
        self::assertFalse(helper::reorder_items_in_group($user->id, group::get(group::PLATFORM), ['restorecourse', 'green', 'checkpermissions']));
        self::assertDebuggingCalled('Provided key cannot be found in user admin menu.');

        // Moving an unknown item before an unknown item.
        self::assertFalse(helper::move_item_before($user->id, 'green', 'blue'));
        self::assertDebuggingCalled('Unknown menu item key green');

        // Moving an item before an unknown item.
        self::assertFalse(helper::move_item_before($user->id, 'restorecourse', 'blue'));
        self::assertDebuggingCalled('Unknown menu item key (before) blue');

        // Moving an unknown item to the bottom.
        $result = $method->invoke(null, $user->id, 'green');
        self::assertFalse($result);
        self::assertDebuggingCalled('Unknown menu item key green');
    }

    public function test_menu_manipulation_rename_item() {
        $roleid = $this->getDataGenerator()->create_role();
        $user = $this->getDataGenerator()->create_user();
        $capabilities = [
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
        $admin = admin_get_root(true, false); // Force the admin tree to reload.

        self::assertMenuStructure([], $user);

        $visible = $this->recurse_visible_admin_nodes($admin);
        sort($visible);

        self::assertNotEmpty($visible);

        $possibilities = helper::get_addable_items($user->id);
        $actual = [];
        foreach ($possibilities as $item) {
            $actual[] = $item->get_key();
        }
        sort($actual);

        self::assertTrue(helper::add_item($user->id, 'restorecourse', group::get(group::PLATFORM)));
        self::assertTrue(helper::add_item($user->id, 'profilefields', group::get(group::PLATFORM)));
        self::assertTrue(helper::add_item($user->id, 'checkpermissions', group::get(group::PLATFORM)));

        self::assertMenuStructure([
            group::PLATFORM => [
                'restorecourse' => 'Restore course',
                'profilefields' => 'User profile fields',
                'checkpermissions' => 'Check system permissions',
            ],
        ], $user);

        // Rename one item.
        self::assertTrue(helper::rename_item($user->id, 'restorecourse', 'Course restoration tool'));

        self::assertMenuStructure([
            group::PLATFORM => [
                'restorecourse' => 'Course restoration tool',
                'profilefields' => 'User profile fields',
                'checkpermissions' => 'Check system permissions',
            ],
        ], $user);

        // Rename all items.
        self::assertTrue(helper::rename_item($user->id, 'restorecourse', 'Restore'));
        self::assertTrue(helper::rename_item($user->id, 'profilefields', 'User fields'));
        self::assertTrue(helper::rename_item($user->id, 'checkpermissions', 'Permissions'));

        self::assertMenuStructure([
            group::PLATFORM => [
                'restorecourse' => 'Restore',
                'profilefields' => 'User fields',
                'checkpermissions' => 'Permissions',
            ],
        ], $user);

        // Rename all items back to their original
        self::assertTrue(helper::rename_item($user->id, 'restorecourse', 'Restore course'));
        self::assertTrue(helper::rename_item($user->id, 'profilefields', 'User profile fields'));
        self::assertTrue(helper::rename_item($user->id, 'checkpermissions', 'Check system permissions'));

        self::assertMenuStructure([
            group::PLATFORM => [
                'restorecourse' => 'Restore course',
                'profilefields' => 'User profile fields',
                'checkpermissions' => 'Check system permissions',
            ],
        ], $user);

        // Rename an item not in the menu already but is known.
        self::assertTrue(helper::rename_item($user->id, 'roledefaults', 'My role defaults item'));

        self::assertMenuStructure([
            group::PLATFORM => [
                'restorecourse' => 'Restore course',
                'profilefields' => 'User profile fields',
                'checkpermissions' => 'Check system permissions',
            ],
        ], $user);

        self::assertTrue(helper::add_item($user->id, 'roledefaults', group::get(group::PLATFORM)));

        self::assertMenuStructure([
            group::PLATFORM => [
                'restorecourse' => 'Restore course',
                'profilefields' => 'User profile fields',
                'checkpermissions' => 'Check system permissions',
                'roledefaults' => 'My role defaults item',
            ],
        ], $user);

        // Rename an unknown item.
        self::assertFalse(helper::rename_item($user->id, 'green', 'A green item'));
        self::assertDebuggingCalled('Invalid item key specified green');
    }

    public function test_menu_manipulation_remove_item() {
        $roleid = $this->getDataGenerator()->create_role();
        $user = $this->getDataGenerator()->create_user();
        $capabilities = [
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
        $admin = admin_get_root(true, false); // Force the admin tree to reload.

        self::assertMenuStructure([], $user);

        $visible = $this->recurse_visible_admin_nodes($admin);
        sort($visible);

        self::assertNotEmpty($visible);

        $possibilities = helper::get_addable_items($user->id);
        $actual = [];
        foreach ($possibilities as $item) {
            $actual[] = $item->get_key();
        }
        sort($actual);

        self::assertTrue(helper::add_item($user->id, 'restorecourse', group::get(group::PLATFORM)));
        self::assertTrue(helper::add_item($user->id, 'profilefields', group::get(group::PLATFORM)));
        self::assertTrue(helper::add_item($user->id, 'checkpermissions', group::get(group::PLATFORM)));
        self::assertTrue(helper::add_item($user->id, 'roledefaults', group::get(group::PLATFORM)));

        self::assertMenuStructure([
            group::PLATFORM => [
                'restorecourse' => 'Restore course',
                'profilefields' => 'User profile fields',
                'checkpermissions' => 'Check system permissions',
                'roledefaults' => 'Default role settings',
            ],
        ], $user);

        // Remove one item.
        self::assertTrue(helper::remove_item($user->id, 'profilefields'));

        self::assertMenuStructure([
            group::PLATFORM => [
                'restorecourse' => 'Restore course',
                'checkpermissions' => 'Check system permissions',
                'roledefaults' => 'Default role settings',
            ],
        ], $user);

        self::assertTrue(helper::remove_item($user->id, 'roledefaults'));
        self::assertTrue(helper::remove_item($user->id, 'restorecourse'));
        self::assertTrue(helper::remove_item($user->id, 'checkpermissions'));

        self::assertMenuStructure([], $user);

        // Now try removing a default item. Implies having a default item.
        $roleid = $this->getDataGenerator()->create_role();
        role_change_permission($roleid, context_system::instance(), 'moodle/user:update', CAP_ALLOW);
        $this->getDataGenerator()->role_assign($roleid, $user->id);

        admin_get_root(true, false);

        self::assertMenuStructure([
            group::PLATFORM => [
                'editusers' => 'Users',
            ],
        ], $user);

        self::assertTrue(helper::remove_item($user->id, 'editusers'));

        self::assertMenuStructure([], $user);

        // Remove an unknown item.
        self::assertFalse(helper::remove_item($user->id, 'green'));
        self::assertDebuggingCalled('Invalid item key specified green');
    }

    private function recurse_visible_admin_nodes(part_of_admin_tree $node) {
        $visible = [];
        if ($node->check_access()) {
            if ($node instanceof admin_externalpage) {
                $visible[] = $node->name;
            }
            if ($node instanceof admin_category) {
                foreach ($node->get_children(false) as $child) {
                    $visible = array_merge($visible, $this->recurse_visible_admin_nodes($child));
                }
            }
        }
        return $visible;
    }

    public function test_item_merge_group() {
        $a = item::from_provider('a', group::get(group::LEARN), new lang_string('yes', 'core'), 50);

        $b = item::from_preference('a', group::get(group::LEARN), 'Yes', 100, true);
        $item = item::merge($a, $b);
        self::assertInstanceOf(item::class, $item);
        self::assertSame(group::LEARN, $item->get_group());

        $b = item::from_preference(null, null, null, null, false);
        $item = item::merge($a, $b);
        self::assertInstanceOf(item::class, $item);
        self::assertSame(group::LEARN, $item->get_group());
    }

    public function test_item_merge_weight() {
        $a = item::from_provider('a', group::get(group::LEARN), new lang_string('yes', 'core'), 50);

        $b = item::from_preference('a', group::get(group::LEARN), 'Yes', 100, true);
        $item = item::merge($a, $b);
        self::assertInstanceOf(item::class, $item);
        self::assertSame(100, $item->get_weight());

        $b = item::from_preference('a', group::get(group::LEARN), 'Yes', null, true);
        $item = item::merge($a, $b);
        self::assertInstanceOf(item::class, $item);
        self::assertSame(50, $item->get_weight());

        $b = item::from_preference(null, null, null, null, false);
        $item = item::merge($a, $b);
        self::assertInstanceOf(item::class, $item);
        self::assertSame(50, $item->get_weight());

        $b = item::from_preference(null, null, null, 25, true);
        $item = item::merge($a, $b);
        self::assertInstanceOf(item::class, $item);
        self::assertSame(25, $item->get_weight());
    }

    public function test_item_merge_visible() {
        $a = item::from_provider('a', group::get(group::LEARN), new lang_string('yes', 'core'), 50);

        $b = item::from_preference('a', group::get(group::LEARN), 'Yes', 50, true);
        $item = item::merge($a, $b);
        self::assertInstanceOf(item::class, $item);
        self::assertSame(true, $item->get_visible());

        $b = item::from_preference('a', group::get(group::LEARN), 'Yes', null, false);
        $item = item::merge($a, $b);
        self::assertInstanceOf(item::class, $item);
        self::assertSame(false, $item->get_visible());

        $b = item::from_preference(null, null, null, null, false);
        $item = item::merge($a, $b);
        self::assertInstanceOf(item::class, $item);
        self::assertSame(false, $item->get_visible());

        $b = item::from_preference(null, null, null, 25, true);
        $item = item::merge($a, $b);
        self::assertInstanceOf(item::class, $item);
        self::assertSame(true, $item->get_visible());
    }

    public function test_helper_get_user_menu() {
        // Needed because of capability checks.
        $this->setAdminUser();
        $this->resetAfterTest();

        admin_get_root(true, false); // Force the admin tree to reload.
        $user = get_admin();

        $menu = helper::get_user_menu($user->id);
        self::assertInstanceOf(\totara_core\quickaccessmenu\menu::class, $menu);

        $menu = helper::get_user_menu();
        self::assertInstanceOf(\totara_core\quickaccessmenu\menu::class, $menu);
    }

    public function test_group_get_group_keys() {
        self::assertEquals([
            group::PLATFORM,
            group::LEARN,
            group::PERFORM,
            group::CONFIGURATION,
        ], group::get_group_keys(1));
    }

    public function test_group_get_group_strings() {
        self::assertEquals([
            'platform'      => 'Core platform',
            'learn'         => 'Learning',
            'configuration' => 'Configuration',
            'perform'       => 'Performance'
        ], group::get_group_strings(1));

        // Check truncations.
        self::assertEquals([
            'platform'      => 'Core pl...',
            'learn'         => 'Learning',
            'configuration' => 'Configu...',
            'perform'       => 'Perform...'
        ], group::get_group_strings(1, 10));
    }

    public function test_group_get_factory() {
        self::assertSame(group::LEARN, (string)group::get(group::LEARN));
        self::assertSame(group::PLATFORM, (string)group::get(group::PLATFORM));
        self::assertSame(group::CONFIGURATION, (string)group::get(group::CONFIGURATION));
        // Test the default group.
        self::assertSame(group::LEARN, (string)group::get('Green'));
        self::assertDebuggingCalled('Invalid group provided, reset to default');
    }

    public function test_group_create_group() {
        $this->resetAfterTest();

        // Create a user with at least one role that lets them see the top menu.
        $roleid = $this->getDataGenerator()->create_role();
        $user = $this->getDataGenerator()->create_user();
        role_change_permission($roleid, context_system::instance(), 'totara/reportbuilder:managereports', CAP_ALLOW);
        $this->getDataGenerator()->role_assign($roleid, $user->id);

        $this->setUser($user);
        admin_get_root(true, false); // Force the admin tree to reload.

        self::assertMenuStructure([
            group::PLATFORM => [
                'rbmanagereports' => 'Reports',
            ]
        ], $user);

        $group1 = group::create_group('Group 1', $user->id);
        $group2 = group::create_group('', $user->id);
        $group3 = group::create_group(null, $user->id);

        self::assertMenuStructure([
            group::PLATFORM      => [
                'rbmanagereports' => 'Reports',
            ],
            group::LEARN         => [],
            group::PERFORM       => [],
            group::CONFIGURATION => [],
            $group1->get_key()   => [],
            $group2->get_key()   => [],
            $group3->get_key()   => [],
        ], $user, true);

        self::assertSame('Group 1', group::get($group1->get_key(), $user->id)->get_label());
        self::assertSame('', group::get($group2->get_key(), $user->id)->get_label());
        self::assertSame('Untitled', group::get($group3->get_key(), $user->id)->get_label());
    }

    public function test_group_rename_group() {
        $this->resetAfterTest();

        // Create a user with at least one role that lets them see the top menu.
        $roleid = $this->getDataGenerator()->create_role();
        $user = $this->getDataGenerator()->create_user();
        role_change_permission($roleid, context_system::instance(), 'totara/reportbuilder:managereports', CAP_ALLOW);
        $this->getDataGenerator()->role_assign($roleid, $user->id);

        $this->setUser($user);
        admin_get_root(true, false); // Force the admin tree to reload.

        self::assertMenuStructure([
            group::PLATFORM => [
                'rbmanagereports' => 'Reports',
            ]
        ], $user);

        $group = group::create_group('Green group', $user->id);
        self::assertSame('Green group', group::get($group->get_key(), $user->id)->get_label());

        group::rename_group($group->get_key(), 'Yellow group', $user->id);
        self::assertSame('Yellow group', group::get($group->get_key(), $user->id)->get_label());

        group::rename_group($group->get_key(), '', $user->id);
        self::assertSame('', group::get($group->get_key(), $user->id)->get_label());
    }

    public function test_group_remove_group() {
        $this->resetAfterTest();

        // Create a user with at least one role that lets them see the top menu.
        $roleid = $this->getDataGenerator()->create_role();
        $user = $this->getDataGenerator()->create_user();
        role_change_permission($roleid, context_system::instance(), 'totara/reportbuilder:managereports', CAP_ALLOW);
        $this->getDataGenerator()->role_assign($roleid, $user->id);

        $this->setUser($user);
        admin_get_root(true, false); // Force the admin tree to reload.

        self::assertMenuStructure([
            group::PLATFORM => [
                'rbmanagereports' => 'Reports',
            ]
        ], $user);

        $group = group::create_group('Blue group', $user->id);
        self::assertSame('Blue group', group::get($group->get_key(), $user->id)->get_label());

        // Remove custom group.
        self::assertTrue(group::remove_group($group->get_key(), $user->id));
        self::assertSame(group::LEARN, (string)group::get($group->get_key()));
        self::assertDebuggingCalled('Invalid group provided, reset to default');

        // Remove default group.
        self::assertTrue(group::remove_group(group::PLATFORM, $user->id));
        self::assertSame(group::PLATFORM, (string)group::get(group::PLATFORM));
        self::assertFalse(group::get(group::PLATFORM)->get_visible());
    }

    public function test_helper_remove_group() {
        global $DB;

        $this->resetAfterTest();

        $roleid = $DB->get_field('role', 'id', ['shortname' => 'manager']);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->role_assign($roleid, $user->id);

        $this->setUser($user);
        admin_get_root(true, false); // Force the admin tree to reload.

        self::assertMenuStructure([
            group::PLATFORM      => [
              'editusers'          => 'Users',
              'cohorts'            => 'Audiences',
              'positionmanage'     => 'Positions',
              'organisationmanage' => 'Organisations',
              'managebadges'       => 'Badges',
              'competencymanage'   => 'Competencies',
              'rbmanagereports'    => 'Reports',
            ],
            group::LEARN         => [
              'coursemgmt'           => 'Courses and categories',
              'programmgmt'          => 'Programs',
              'managecertifications' => 'Certifications',
              'gradessettings'       => 'Grades',
              'managetemplates'      => 'Learning Plans',
              'setup_content_marketplaces' => 'Content Marketplace'
            ],
            group::PERFORM => [
                'manageappraisals'   => 'Appraisals',
                'managefeedback360'  => '360° Feedback',
                'goalmanage'         => 'Goals',
            ],
            group::CONFIGURATION => [
              'totarasyncsettings' => 'HR Import',
            ],
        ], $user);

        $group = group::create_group('New group', $user->id);
        self::assertSame('New group', group::get($group->get_key(), $user->id)->get_label());

        self::assertTrue(helper::add_item($user->id, 'restorecourse', group::get($group->get_key())));
        self::assertTrue(helper::add_item($user->id, 'checkpermissions', group::get($group->get_key())));

        self::assertMenuStructure([
            group::PLATFORM      => [
                'editusers'          => 'Users',
                'cohorts'            => 'Audiences',
                'positionmanage'     => 'Positions',
                'organisationmanage' => 'Organisations',
                'managebadges'       => 'Badges',
                'competencymanage'   => 'Competencies',
                'rbmanagereports'    => 'Reports',
            ],
            group::LEARN         => [
                'coursemgmt'           => 'Courses and categories',
                'programmgmt'          => 'Programs',
                'managecertifications' => 'Certifications',
                'gradessettings'       => 'Grades',
                'managetemplates'      => 'Learning Plans',
                'setup_content_marketplaces' => 'Content Marketplace'
            ],
            group::PERFORM => [
                'manageappraisals'   => 'Appraisals',
                'managefeedback360'  => '360° Feedback',
                'goalmanage'         => 'Goals',
            ],
            group::CONFIGURATION => [
                'totarasyncsettings' => 'HR Import',
            ],
            $group->get_key()    => [
                'restorecourse'    => 'Restore course',
                'checkpermissions' => 'Check system permissions',
            ],
        ], $user);

        // Remove default group.
        self::assertTrue(helper::remove_group($user->id, group::LEARN));
        self::assertSame(group::LEARN, (string)group::get(group::LEARN));
        self::assertFalse(group::get(group::LEARN)->get_visible());

        // Remove custom group.
        self::assertTrue(helper::remove_group($user->id, $group->get_key()));
        self::assertSame(group::LEARN, (string)group::get($group->get_key()));
        self::assertDebuggingCalled('Invalid group provided, reset to default');

        self::assertMenuStructure([
            group::PLATFORM      => [
                'editusers'          => 'Users',
                'cohorts'            => 'Audiences',
                'positionmanage'     => 'Positions',
                'organisationmanage' => 'Organisations',
                'managebadges'       => 'Badges',
                'competencymanage'   => 'Competencies',
                'rbmanagereports'    => 'Reports',
            ],
            group::LEARN         => [
                'coursemgmt'           => 'Courses and categories',
                'programmgmt'          => 'Programs',
                'managecertifications' => 'Certifications',
                'gradessettings'       => 'Grades',
                'managetemplates'      => 'Learning Plans',
                'setup_content_marketplaces' => 'Content Marketplace'
            ],
            group::PERFORM => [
                'manageappraisals'   => 'Appraisals',
                'managefeedback360'  => '360° Feedback',
                'goalmanage'         => 'Goals',
            ],
            group::CONFIGURATION => [
                'totarasyncsettings' => 'HR Import',
            ],
        ], $user);

        // Check that we can re-add item from the deleted group to any other.
        self::assertTrue(helper::add_item($user->id, 'restorecourse', group::get(group::CONFIGURATION)));
        self::assertTrue(helper::add_item($user->id, 'coursemgmt', group::get(group::CONFIGURATION)));

        self::assertMenuStructure([
            group::PLATFORM      => [
                'editusers'          => 'Users',
                'cohorts'            => 'Audiences',
                'positionmanage'     => 'Positions',
                'organisationmanage' => 'Organisations',
                'managebadges'       => 'Badges',
                'competencymanage'   => 'Competencies',
                'rbmanagereports'    => 'Reports',
            ],
            group::LEARN         => [
                'programmgmt'          => 'Programs',
                'managecertifications' => 'Certifications',
                'gradessettings'       => 'Grades',
                'managetemplates'      => 'Learning Plans',
                'setup_content_marketplaces' => 'Content Marketplace'
            ],
            group::PERFORM => [
                'manageappraisals'   => 'Appraisals',
                'managefeedback360'  => '360° Feedback',
                'goalmanage'         => 'Goals',
            ],
            group::CONFIGURATION => [
                'totarasyncsettings' => 'HR Import',
                'restorecourse'      => 'Restore course',
                'coursemgmt'         => 'Courses and categories',
            ],
        ], $user);

        // Check that deleted group is still deleted.
        self::assertFalse(group::get(group::LEARN)->get_visible());
    }

    public function test_helper_change_group_order() {
        $this->resetAfterTest();

        $method = new ReflectionMethod(helper::class, 'move_group_to_bottom');
        $method->setAccessible(true);

        // Create a user with at least one role that lets them see the top menu.
        $roleid = $this->getDataGenerator()->create_role();
        $user = $this->getDataGenerator()->create_user();
        role_change_permission($roleid, context_system::instance(), 'totara/reportbuilder:managereports', CAP_ALLOW);
        $this->getDataGenerator()->role_assign($roleid, $user->id);

        $this->setUser($user);
        admin_get_root(true, false); // Force the admin tree to reload.

        self::assertMenuStructure([
            group::PLATFORM      => ['rbmanagereports' => 'Reports',],
            group::LEARN         => [],
            group::PERFORM       => [],
            group::CONFIGURATION => [],
        ], $user, true);

        $group1 = group::create_group('Group 1', $user->id);
        $group2 = group::create_group('Group 2', $user->id);
        $group3 = group::create_group('Group 3', $user->id);

        self::assertTrue(helper::reorder_groups($user->id,
            [group::CONFIGURATION, $group1->get_key(), $group3->get_key(), group::LEARN, group::PLATFORM, group::PERFORM, $group2->get_key()]
        ));

        self::assertMenuStructure([
            group::CONFIGURATION => [],
            $group1->get_key()   => [],
            $group3->get_key()   => [],
            group::LEARN         => [],
            group::PLATFORM      => ['rbmanagereports' => 'Reports',],
            group::PERFORM       => [],
            $group2->get_key()   => [],
        ], $user, true);

        $result = $method->invoke(null, $user->id, group::CONFIGURATION);
        self::assertTrue($result);

        self::assertMenuStructure([
            $group1->get_key()   => [],
            $group3->get_key()   => [],
            group::LEARN         => [],
            group::PLATFORM      => ['rbmanagereports' => 'Reports',],
            group::PERFORM       => [],
            $group2->get_key()   => [],
            group::CONFIGURATION => [],
        ], $user, true);

        self::assertTrue(helper::move_group_before($user->id, group::PLATFORM, $group3->get_key()));

        self::assertMenuStructure([
            $group1->get_key()   => [],
            group::PLATFORM      => ['rbmanagereports' => 'Reports',],
            $group3->get_key()   => [],
            group::LEARN         => [],
            group::PERFORM       => [],
            $group2->get_key()   => [],
            group::CONFIGURATION => [],
        ], $user, true);

        self::assertTrue(helper::reorder_groups($user->id,
            [group::CONFIGURATION, $group1->get_key(), $group3->get_key(), group::PLATFORM, $group2->get_key(), group::LEARN, group::PERFORM]
        ));

        self::assertMenuStructure([
            group::CONFIGURATION => [],
            $group1->get_key()   => [],
            $group3->get_key()   => [],
            group::PLATFORM      => ['rbmanagereports' => 'Reports',],
            $group2->get_key()   => [],
            group::LEARN         => [],
            group::PERFORM       => [],
        ], $user, true);

        // Reorder with the wrong number of group keys.
        self::assertFalse(helper::reorder_groups($user->id, ['green', 'blue', 'red']));
        self::assertDebuggingCalled('Invalid number of groups provided, expected 7 got 3');

        // Reorder with invalid group keys.
        self::assertFalse(helper::reorder_groups($user->id, ['green', 'blue', 'red', 'yellow', 'pink', 'black', 'orange']));
        self::assertDebuggingCalled('Given key is not presently in use, green');

        // Moving an unknown item before an unknown item.
        self::assertFalse(helper::move_group_before($user->id, 'green', 'blue'));
        self::assertDebuggingCalled('Unknown menu group key green');

        // Moving an item before an unknown item.
        self::assertFalse(helper::move_group_before($user->id, group::LEARN, 'blue'));
        self::assertDebuggingCalled('Unknown menu group key (before) blue');

        // Moving an unknown group to the bottom.
        $result = $method->invoke(null, $user->id, 'green');
        self::assertFalse($result);
        self::assertDebuggingCalled('Unknown menu group key green');
    }

    public function test_item_construct_from_provider() {
        $this->resetAfterTest();

        $item = item::from_provider('test', group::get(group::PLATFORM), new lang_string('yes'), 1500);
        self::assertInstanceOf(item::class, $item);
        self::assertSame('test', $item->get_key());
        self::assertSame(group::PLATFORM, $item->get_group());
        self::assertSame(get_string('yes'), $item->get_label());
        self::assertSame(1500, $item->get_weight());

        try {
            $item->set_group(group::get(group::PLATFORM));
            $this->fail('Group should not be settable here, this isn\'t a preference');
        } catch (\coding_exception $ex) {
            self::assertSame('Coding error detected, it must be fixed by a programmer: Only preference items can be modified.', $ex->getMessage());
        }

        try {
            $item->set_label('Super');
            $this->fail('Label should not be settable here, this isn\'t a preference');
        } catch (\coding_exception $ex) {
            self::assertSame('Coding error detected, it must be fixed by a programmer: Only preference items can be modified.', $ex->getMessage());
        }

        try {
            $item->set_weight(1200);
            $this->fail('Weight should not be settable here, this isn\'t a preference');
        } catch (\coding_exception $ex) {
            self::assertSame('Coding error detected, it must be fixed by a programmer: Only preference items can be modified.', $ex->getMessage());
        }

        try {
            $item->make_visible();
            $this->fail('Visibility should not be settable here, this isn\'t a preference');
        } catch (\coding_exception $ex) {
            self::assertSame('Coding error detected, it must be fixed by a programmer: Only preference items can be modified.', $ex->getMessage());
        }

        try {
            $item->make_hidden();
            $this->fail('Visibility should not be settable here, this isn\'t a preference');
        } catch (\coding_exception $ex) {
            self::assertSame('Coding error detected, it must be fixed by a programmer: Only preference items can be modified.', $ex->getMessage());
        }

        try {
            $item->get_preference_array();
            $this->fail('get_preference_array should not be available here, this isn\'t a preference');
        } catch (\coding_exception $ex) {
            self::assertSame('Coding error detected, it must be fixed by a programmer: Preference arrays can only be exported for preference items', $ex->getMessage());
        }
    }

    public function test_item_construct_from_preference() {
        $this->resetAfterTest();

        $item = item::from_preference('test', group::get(group::PLATFORM), new lang_string('yes'), 1500, true);
        self::assertInstanceOf(item::class, $item);
        self::assertSame('test', $item->get_key());
        self::assertSame(group::PLATFORM, $item->get_group());
        self::assertSame(get_string('yes'), $item->get_label());
        self::assertSame(1500, $item->get_weight());

        $item->make_hidden();
        $item->set_group(group::get(group::LEARN));
        $item->set_label('Super');
        $item->set_weight(1200);
        $item->make_visible();

        $expected = [
            'key'     => 'test',
            'group'   => group::LEARN,
            'label'   => 'Super',
            'weight'  => 1200,
            'visible' => true,
        ];
        self::assertEquals($expected, $item->get_preference_array());

        $item->set_label('');
        $expected = [
            'key'     => 'test',
            'group'   => group::LEARN,
            'label'   => null,
            'weight'  => 1200,
            'visible' => true,
        ];
        self::assertEquals($expected, $item->get_preference_array());
    }

    public function test_core_config_quickaccessmenu() {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();

        $CFG->defaultquickaccessmenu = [
            ['group' => 'learn', 'key' => 'userbulk', 'label' => 'sometextX1', 'weight' => 9000],
            ['group' => 'learn', 'key' => 'profilefields', 'label' => 'sometextX2', 'weight' => 10000],
            ['group' => 'learn', 'key' => 'userpolicies'], // intentionally missing label and weight
            ['group' => 'platform', 'key' => 'cohorts'], // intentionally missing label and weight
            ['group' => 'platform', 'key' => 'badgesettings', 'label' => 'sometextX3', 'weight' => 9000],
            ['key' => 'langsettings', 'label' => 'sometextX4', 'weight' => 9999], // intentionally missing group
        ];

        admin_get_root(true, false); // Force the admin tree to reload.
        $user = get_admin();

        // We are guessing here that 'userpolicies' and 'cohorts' will be first since their weight will be assigned
        // from the admin tree and there are less than 9000 items in it.
        // This is not ideal and should be refactored later.
        self::assertMenuStructure([
            'platform' => [
                'cohorts'       => 'Audiences', // Label comes from the admin tree since it is not specified
                'badgesettings' => 'sometextX3',
            ],
            'learn' => [
                'userpolicies'  => 'User policies', // Label comes from the admin tree since it is not specified
                'userbulk'      => 'sometextX1',
                'langsettings'  => 'sometextX4', // Group defaults to 'learn' since it is not specified
                'profilefields' => 'sometextX2',
            ],
        ], $user);
    }

    public function test_core_admin_output_quickaccessmenu() {
        $this->resetAfterTest();

        $roleid = $this->getDataGenerator()->create_role();
        $user = $this->getDataGenerator()->create_user();
        $capabilities = [
            'moodle/user:update',
            'moodle/cohort:manage',
            'moodle/category:manage',
        ];
        foreach ($capabilities as $cap) {
            role_change_permission($roleid, context_system::instance(), $cap, CAP_ALLOW);
        }
        $this->getDataGenerator()->role_assign($roleid, $user->id);

        $this->setUser($user);
        admin_get_root(true, false); // Force the admin tree to reload.

        $factory = factory::instance($user->id);

        $realmenu = $factory->get_menu();
        self::assertInstanceOf(\totara_core\quickaccessmenu\menu::class, $realmenu);

        $menu = \totara_core\output\quickaccessmenu::create_from_menu($realmenu);
        self::assertInstanceOf(\totara_core\output\quickaccessmenu::class, $menu);

        self::assertSame('totara_core/quickaccessmenu', $menu->get_template_name());

        $expected = [
            'can_edit' => true,
            'can_search' => false,
            'empty_message' => get_string('quickaccessmenu:empty-message', 'totara_core', 'https://www.example.com/moodle/user/quickaccessmenu.php'),
            'groups'        => [
                [
                    'title'      => 'Core platform',
                    'has_items'  => true,
                    'item_count' => 2,
                    'items'      => [
                        [
                            'label' => 'Users',
                            'url'   => 'https://www.example.com/moodle/admin/user.php',
                        ],
                        [
                            'label' => 'Audiences',
                            'url'   => 'https://www.example.com/moodle/cohort/index.php',
                        ],
                    ],
                ],
                [
                    'title'      => 'Learning',
                    'has_items'  => true,
                    'item_count' => 1,
                    'items'      => [
                        [
                            'label' => 'Courses and categories',
                            'url'   => 'https://www.example.com/moodle/course/management.php',
                        ],
                    ],
                ],
            ],
            'group_count'   => 2,
            'has_groups'    => true,
        ];

        self::assertEquals(json_encode($expected), json_encode($menu->get_template_data()));
    }

    public function test_helper_item_exists_in_user_menu() {
        $this->setAdminUser();
        $this->resetAfterTest();

        $user = get_admin();

        $valid_key = 'editusers';
        $invalid_key = 'fakepage';

        $this->assertTrue(helper::item_exists_in_user_menu($user->id, $valid_key));
        $this->assertFalse(helper::item_exists_in_user_menu($user->id, $invalid_key));

        // Test for a custom user role
        $roleid = $this->getDataGenerator()->create_role();
        $user = $this->getDataGenerator()->create_user();
        $capabilities = [
            'moodle/user:update',
            'moodle/cohort:manage',
            'moodle/category:manage',
        ];
        foreach ($capabilities as $cap) {
            role_change_permission($roleid, context_system::instance(), $cap, CAP_ALLOW);
        }
        $this->getDataGenerator()->role_assign($roleid, $user->id);

        $this->setUser($user);
        admin_get_root(true, false); // Force the admin tree to reload.

        $valid_key = 'editusers';
        $invalid_key = 'checkpermissions';

        $this->assertTrue(helper::item_exists_in_user_menu($user->id, $valid_key));
        $this->assertFalse(helper::item_exists_in_user_menu($user->id, $invalid_key));
    }

    public function test_item_from_part_of_admin_tree_category() {
        $this->setAdminUser();
        $this->resetAfterTest();

        $root = admin_get_root();
        $item = $root->locate('courses');
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('Admin categories cannot be used as menu items.');
        item::from_part_of_admin_tree($item);
    }

    public function test_reset_for_user() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        admin_get_root(true, false); // Force the admin tree to reload.

        $user = get_admin();

        $groups = group::get_groups($user->id);
        $group = reset($groups);
        $this->assertSame(0, $DB->count_records('quickaccess_preferences'));
        $this->assertTrue(helper::add_item($user->id, 'httpsecurity', $group));
        $this->assertTrue(helper::add_item($user->id, 'experimentalsettings', $group));
        $this->assertSame(1, $DB->count_records('quickaccess_preferences'));
        helper::reset_to_default($user->id);
        $this->assertSame(0, $DB->count_records('quickaccess_preferences'));
    }

    public function test_reset_for_user_guest_user() {
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('Preferences cannot be set for the guest user.');
        preference_helper::reset_for_user(guest_user()->id);
    }

    public function test_duplicate_weight_in_groups() {
        $this->resetAfterTest();
        $this->setAdminUser();
        admin_get_root(true, false); // Force the admin tree to reload.

        $user = get_admin();
        $groups = group::get_groups($user->id);
        $weights = [];
        foreach ($groups as $group) {
            $weights[] = $group->get_weight();
        }

        // Remove duplicate weights if there are any.
        $weights = array_unique($weights);

        $this->assertEquals(count($groups), count($weights), 'Coding error: Two or more default groups have the same weight, this needs to be fixed!');
    }

    /**
     * Is add-on with menu item present?
     *
     * @return bool false for standard installation, true if any addons with menu items are present
     */
    public function is_addon_with_item_present() {
        $items = \core_component::get_namespace_classes('quickaccessmenu', provider::class);

        foreach ($items as $item) {
            $parts = explode('\\', $item);
            $component = reset($parts);
            if ($component === 'core') {
                continue;
            }
            list($plugin_type, $plugin_name) = core_component::normalize_component($component);
            if ($plugin_type === 'core') {
                continue;
            }
            $standardplugins = core_plugin_manager::standard_plugins_list($plugin_type);
            if (!in_array($plugin_name, $standardplugins)) {
                return true;
            }
        }
        return false;
    }
}
