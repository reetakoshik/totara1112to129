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
 * @author Carl Anderson <carl.anderson@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\output;

use totara_core\quickaccessmenu\group;
use totara_core\quickaccessmenu\item;
use totara_core\quickaccessmenu\menu;
use totara_core\quickaccessmenu\helper;

use action_menu;
use action_menu_link;
use core\output\flex_icon;

final class quickaccesssettings extends \core\output\template {

    /**
     * Returns the admin root.
     *
     * @return \admin_root
     */
    private static function get_admin_root(): \admin_root {
        global $CFG;
        require_once($CFG->dirroot . '/lib/adminlib.php');
        $adminroot = \admin_get_root(false, false);
        return $adminroot;
    }

    /**
     * @param menu $menu
     * @return quickaccesssettings
     */
    public static function create_from_menu(menu $menu): quickaccesssettings {
        global $USER, $OUTPUT;

        $data = [];
        $groups = [];

        $allgroups = group::get_groups($USER->id);
        $tree_selector = self::get_tree_selector_data();
        $adminroot = self::get_admin_root();

        $actions = self::get_group_actions();

        foreach (self::organise_items_by_group($menu->get_items(), $allgroups) as $group => $items) {
            $groups[$group] = [
                'key'           => $group,
                'title'         => (string)$allgroups[$group]->get_label(),
                'has_items'     => !empty($items),
                'item_count'    => count($items),
                'items'         => [],
                'actions'       => $actions->export_for_template($OUTPUT),
                'tree_selector' => $tree_selector,
            ];
            /** @var item $item */
            foreach ($items as $item) {
                $page = $adminroot->locate($item->get_key());

                $itemdata = [
                    'key'   => $item->get_key(),
                    'page'  => $page->visiblename,
                    'label' => $item->get_label(),
                    'url'   => $item->get_url()->out(),
                ];

                $groups[$group]['items'][] = $itemdata;
            }
        }

        $data['groups'] = array_values($groups);
        $data['group_count'] = count($groups);
        $data['has_groups'] = ($data['group_count'] > 0);

        return new quickaccesssettings($data);
    }

    /**
     * Returns the item data for the given key.
     *
     * @param string $key
     * @return array
     */
    public static function get_item_data($key): array {
        $menu = helper::get_user_menu();
        $item = $menu->locate($key);

        $adminroot = self::get_admin_root();
        $page = $adminroot->locate($item->get_key());

        if (!empty($item)) {
            $itemdata = [
                'key'   => $item->get_key(),
                'page'  => (string)$page->visiblename,
                'label' => $item->get_label(),
                'url'   => $item->get_url()->out(),
            ];

            return $itemdata;
        }

        return [];
    }

    /**
     * Returns the group data for the given key
     * @param $key
     * @return array
     */
    public static function get_group_data($key): array {
        global $USER;
        $menu = helper::get_user_menu();

        $allgroups = group::get_groups($USER->id);
        $tree_selector = self::get_tree_selector_data();
        $adminroot = self::get_admin_root();

        $groups = [
            $key => [] //ensure non-null return
        ];
        foreach (self::organise_items_by_group($menu->get_items(), $allgroups) as $group => $items) {
            $groups[$group] = [
                'key'           => $group,
                'title'         => (string)$allgroups[$group]->get_label(),
                'has_items'     => !empty($items),
                'item_count'    => count($items),
                'items'         => [],
                'tree_selector' => $tree_selector,
            ];
            /** @var item $item */
            foreach ($items as $item) {
                $page = $adminroot->locate($item->get_key());

                $itemdata = [
                    'key'   => $item->get_key(),
                    'page'  => $page->visiblename,
                    'label' => $item->get_label(),
                    'url'   => $item->get_url()->out(),
                ];

                $groups[$group]['items'][] = $itemdata;
            }
        }

        return $groups[$key];
    }

    /**
     * Returns the admin tree in a format suitable for the select_tree widget
     * @return array
     */
    public static function get_tree_selector_data() {
        $options = [];
        $adminroot = self::get_admin_root();

        //We have to traverse the tree, and covert everything into the expected $options object
        foreach ($adminroot->get_children(false) as $child) {
            if ($child->check_access() && !$child->is_hidden()) {
                $options[] = self::convert_admin_tree($child);
            }
        }

        $select_tree = select_tree::create(
            $adminroot->name,
            $adminroot->visiblename,
            true,
            $options,
            null,
            false,
            false,
            get_string('quickaccessmenu:addmenuitem', 'totara_core')
        );

        return $select_tree->get_template_data();
    }

    /**
     * Converts a part of the admin tree to a format suitable for the select_tree widget
     * @param \part_of_admin_tree $parentnode
     * @return \stdClass
     */
    private static function convert_admin_tree(\part_of_admin_tree $parentnode) {
        $options = new \stdClass();

        $options->key = (string)$parentnode->name;
        $options->name = (string)$parentnode->visiblename;
        $options->selectable = true;

        if ($parentnode instanceof \admin_category) {
            $options->children = [];
            $options->selectable = false;

            foreach ($parentnode->get_children(false) as $child) {
                if ($child->check_access() && !$child->is_hidden()) {
                    $options->children[] = self::convert_admin_tree($child);
                }
            }
        }

        return $options;
    }

    /**
     * @param array $items items to sort into groups
     * @param array $allgroups list of groups to sort into
     *
     * @return array list of groups with sorted items
     */
    private static function organise_items_by_group(array $items, array $allgroups): array {
        $groups = [];
        foreach ($allgroups as $group) {
            $groups[$group->get_key()] = [];
        }
        foreach ($items as $item) {
            $group = $item->get_group();
            $groups[$group][] = $item;
        }
        foreach ($groups as $group => &$items) {
            // Hide any deleted default groups.
            if ($allgroups[$group]->get_visible() === false) {
                unset($groups[$group]);
                continue;
            }
            usort($items, [item::class, 'sort_items']);
        }
        return $groups;
    }

    private static function get_group_actions() : action_menu {
        global $OUTPUT;

        // Set up group actions
        $menu = new action_menu();
        $menu->set_constraint('totara_core__QuickAccessSettings__group');
        $menu->set_alignment(action_menu::TR, action_menu::BR);
        $menu->set_menu_trigger($OUTPUT->flex_icon('settings', [
            'alt' => get_string('quickaccesssettings:groupactions', 'totara_core')
        ]));

        // Create actions
        $noop_url = new \moodle_url('#');
        $moveup_icon = new flex_icon('arrow-up', ['alt' => get_string('quickaccesssettings:reordergroup-up','totara_core')]);
        $action_moveup = new action_menu_link($noop_url, $moveup_icon , '', true, [
            'data-quickaccesssettings-group-action' => 'moveup'
        ]);
        $movedown_icon = new flex_icon('arrow-down', ['alt' => get_string('quickaccesssettings:reordergroup-down','totara_core')]);
        $action_movedown = new action_menu_link($noop_url, $movedown_icon, '', true, [
            'data-quickaccesssettings-group-action' => 'movedown'
        ]);
        $action_delete = new action_menu_link($noop_url, null, get_string('quickaccesssettings:deletegroup', 'totara_core'), false, [
            'data-quickaccesssettings-group-action' => 'delete'
        ]);

        // Add to the menu
        $menu->add($action_moveup);
        $menu->add($action_movedown);
        $menu->add($action_delete);

        return $menu;
    }
}
