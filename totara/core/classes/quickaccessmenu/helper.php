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

namespace totara_core\quickaccessmenu;

/**
 * Helper class for the quickaccessmenu
 *
 * This class contains only static methods, that facilitate working easily and consistently with the quickaccessmenu.
 * All interaction from outside of the quickaccessmenu should happen through this class.
 */
final class helper {

    /**
     * Returns a user menu.
     *
     * @param int|null $userid
     * @return menu
     */
    public static function get_user_menu(?int $userid = null): menu {
        global $USER;
        if ($userid === null) {
            $userid = $USER->id;
        }
        $factory = factory::instance($userid);
        $menu = $factory->get_menu();
        return $menu;
    }

    /**
     * Reorders all of the items in the given group, using the order of the keys to determine the new order.
     *
     * @param int $userid
     * @param group $group
     * @param string[] $itemkeys Keys for all items in this group, as you want them ordered now.
     * @return bool Returns true if the operation was a success, or false if the items could not be reordered with the given arguments.
     */
    public static function reorder_items_in_group(int $userid, group $group, array $itemkeys): bool {
        $factory = factory::instance($userid);

        $currentmenu = $factory->get_menu();

        $items = $currentmenu->get_items_in_group($group);

        if (count($items) !== count($itemkeys)) {
            debugging('Invalid number of items provided, expected ' . count($items) . ' got ' . count($itemkeys), DEBUG_DEVELOPER);
            return false;
        }

        $weights = [];
        foreach ($itemkeys as $key) {
            $item = null;
            foreach ($items as $temp) {
                if ($temp->get_key() === $key) {
                    $item = $temp;
                    continue;
                }
            }
            if ($item === null) {
                debugging('Provided key cannot be found in user admin menu.', DEBUG_DEVELOPER);
                return false;
            }
            $weights[] = $item->get_weight();
        }
        sort($weights);

        $map = [];
        foreach ($itemkeys as $key) {
            $map[$key] = array_shift($weights);
        }

        $usermenu = $factory->get_user_preference_menu();
        $items = $usermenu->get_all_items_in_group($group);
        foreach ($items as $item) {
            $key = $item->get_key();
            if (isset($map[$key])) {
                $item->set_weight($map[$key]);
                unset($map[$key]);
            }
        }

        foreach ($map as $key => $weight) {
            $item = item::from_preference($key);
            $item->set_weight($map[$key]);
            $usermenu->add_item($item);
        }

        $usermenu->save();

        return true;
    }

    /**
     * Modifies an item in the user's preferences.
     *
     * This method modifies the given item and saves the modifications in the users preferences.
     * If the item does not exist in the user preferences, but can be added, then it is added and modified at the same time.
     *
     * @param int $userid
     * @param string $key
     * @param string $newlabel The new label, or null if not being modified now.
     * @param group $newgroup The new group, or null if not being modified now.
     * @param bool $newvisible True to make the item visible, false to hide it, or null if not being modified now.
     * @return bool True if the operation was completely successfully, false otherwise.
     */
    private static function modify_item_user_preferences(int $userid, string $key, ?string $newlabel = null, ?group $newgroup = null, ?bool $newvisible = null): bool {
        $newlyadded = false;
        $changed = false;

        $factory = factory::instance($userid);
        $usermenu = $factory->get_user_preference_menu();
        $item = $usermenu->locate($key);

        if ($item === null) {
            // You can rename an item that is not yet shown on your menu.
            // Albeit this is somewhat redundant support it as it technically can work.
            // This is covered by unit tests.
            $item = $factory->get_possible_item($key);

            if ($item === null) {
                debugging('Invalid item key specified ' . $key, DEBUG_DEVELOPER);
                return false;
            }

            $item = item::from_preference($key);
            $usermenu->add_item($item);
            $newlyadded = true;
        }

        if ($newlabel !== null) {
            $item->set_label($newlabel);
            $changed = true;
        }

        if ($newgroup !== null) {
            $item->set_group($newgroup);
            $newlyadded = true; // adding to the group is removing item and re-adding it as new.
        }

        if ($newvisible === true) {
            $item->make_visible();
            $changed = true;
        } else if ($newvisible === false) {
            $item->make_hidden();
            $changed = true;
        }

        if ($newlyadded || $changed) {
            $usermenu->save();
        }

        // Because technically we can rename hidden items, make sure to move only the ones we can see.
        if ($newlyadded && $item->get_visible() === true) {
            self::move_item_to_bottom($userid, $key);
        }

        return true;
    }

    /**
     * Renames the item with the given key,
     *
     * @param int $userid
     * @param string $key
     * @param string $label
     * @return bool
     */
    public static function rename_item(int $userid, string $key, string $label): bool {
        return self::modify_item_user_preferences($userid, $key, $label);
    }

    /**
     * Adds the item with the given key into the given group.
     *
     * @param int $userid
     * @param string $key
     * @param group $group
     * @return bool
     */
    public static function add_item(int $userid, string $key, group $group): bool {
        return self::modify_item_user_preferences($userid, $key, null, $group, true);
    }

    /**
     * Removes the item given its key.
     *
     * @param int $userid
     * @param string $key
     * @return bool
     */
    public static function remove_item(int $userid, string $key): bool {
        return self::modify_item_user_preferences($userid, $key, null, null, false);
    }

    /**
     * Adds a group with the given name.
     *
     * Alias to group::create_group, but doesn't return anything.
     *
     * @param int $userid
     * @param string|null $groupname
     * @return group
     */
    public static function add_group(int $userid, ?string $groupname): group {
        // Create a group and save the preferences.
        return group::create_group($groupname, $userid);
    }

    /**
     * Renames a group with the given key.
     *
     * Alias to group::rename_group, but doesn't return anything.
     *
     * @param int $userid
     * @param string $groupkey
     * @param null|string $groupname
     * @return bool
     * @throws \coding_exception
     */
    public static function rename_group(int $userid, string $groupkey, ?string $groupname): bool {
        // Rename the group and save the preferences.
        group::rename_group($groupkey, $groupname, $userid);

        return true;
    }

    /**
     * Removes the group given its key.
     *
     * @param int $userid
     * @param string $groupkey
     * @return bool
     */
    public static function remove_group(int $userid, string $groupkey): bool {
        $factory = factory::instance($userid);

        $usermenu = $factory->get_user_preference_menu();
        $items = $usermenu->get_items_in_group(group::get($groupkey), true);

        // Hide all items in the group.
        // We cannot just remove them since they can pop-up in the default groups.
        foreach ($items as $item) {
            // Move item to the default group so that we can drop any custom one.
            $usermenu->locate($item->get_key())->set_group(group::get(group::LEARN));
            $usermenu->locate($item->get_key())->make_hidden();
        }
        $usermenu->save();

        // Remove (or hide if default) the group itself.
        group::remove_group($groupkey, $userid);

        return true;
    }

    /**
     * Moves one group before another.
     *
     * @param int $userid
     * @param string $key The key of the group to move.
     * @param string $beforekey The key of the group to move the first item before.
     * @return bool
     */
    public static function move_group_before(int $userid, string $key, string $beforekey): bool {
        $groups = group::get_group_keys($userid);

        if (!in_array($key, $groups)) {
            debugging('Unknown menu group key ' . $key, DEBUG_DEVELOPER);
            return false;
        }

        if (!in_array($beforekey, $groups)) {
            debugging('Unknown menu group key (before) ' . $beforekey, DEBUG_DEVELOPER);
            return false;
        }

        $out = array_splice($groups, array_search($key, $groups), 1);
        array_splice($groups, array_search($beforekey, $groups), 0, $out);

        return self::reorder_groups($userid, $groups);
    }

    /**
     * Moves an group to the bottom of the list.
     *
     * Note: this method is not used right now. Leaving it in place in
     * case we implement drag and drop of menu groups in the future.
     * It is fully covered in the unit tests.
     *
     * @param int $userid
     * @param string $key The key of the group to move.
     * @return bool
     */
    protected static function move_group_to_bottom(int $userid, string $key): bool {
        $groups = group::get_group_keys($userid);

        if (!in_array($key, $groups)) {
            debugging('Unknown menu group key ' . $key, DEBUG_DEVELOPER);
            return false;
        }

        $out = array_splice($groups, array_search($key, $groups), 1);
        $groups = array_merge($groups, $out);

        return self::reorder_groups($userid, $groups);
    }

    /**
     * Reorders all of the groups in the given menu, using the order of the keys to
     * determine the new order, and saves to the user preferences.
     *
     * @param int   $userid
     * @param array $groupkeys Keys for all the group, as you want them ordered now.
     * @return bool
     */
    public static function reorder_groups(int $userid, array $groupkeys): bool {
        $groups = group::get_groups($userid);
        if (count($groups) !== count($groupkeys)) {
            debugging('Invalid number of groups provided, expected ' . count($groups) . ' got ' . count($groupkeys), DEBUG_DEVELOPER);
            return false;
        }

        $keys = array_keys($groups);
        foreach ($groupkeys as $groupkey) {
            $key = array_search($groupkey, $keys);
            if ($key === false) {
                debugging('Given key is not presently in use, ' . $groupkey, DEBUG_DEVELOPER);
                return false;
            }
            unset($keys[$key]);
        }
        if (!empty($keys)) {
            debugging('Not all groups were specified in given keys, missing ' . join(', ', $keys), DEBUG_DEVELOPER);
            return false;
        }

        group::reorder_groups($userid, $groupkeys);

        return true;
    }

    /**
     * Checks if an item exists in the menu given a key.
     *
     * @param int $userid
     * @param string $key
     * @return bool
     */
    public static function item_exists_in_user_menu(int $userid, string $key): bool {
        $factory = factory::instance($userid);
        $menu = $factory->get_menu();
        $item = $menu->locate($key);

        return ($item !== null && $item->get_visible());
    }

    /**
     * Moves an item from one group to another.
     *
     * @param int $userid
     * @param string $key
     * @param string $newgroup
     * @return bool
     */
    public static function change_item_group(int $userid, string $key, string $newgroup): bool {
        $factory = factory::instance($userid);

        $usermenu = $factory->get_user_preference_menu();
        $item = $usermenu->locate($key);

        if ($item === null) {

            $menu = $factory->get_menu();
            $item = $menu->locate($key);

            if ($item === null || $item->get_visible() == false) {
                // You can't change the group of an item that is not shown, as when you choose to show it you must provide a group
                // which would override anything done here.
                debugging('You cannot set the group of an item that it not included on your menu', DEBUG_DEVELOPER);
                return false;
            }

            $item = item::from_preference($key);
            $usermenu->add_item($item);

        }

        $item->set_group(group::get($newgroup));

        $usermenu->save();

        self::move_item_to_bottom($userid, $key);

        return true;
    }

    /**
     * Moves one item before another.
     *
     * @param int $userid
     * @param string $key The key of the item to move.
     * @param string $beforekey The key of the item to move the first item before.
     * @return bool
     */
    public static function move_item_before(int $userid, string $key, string $beforekey): bool {
        $factory = factory::instance($userid);

        $menu = $factory->get_menu();
        $item = $menu->locate($key);
        $itembefore = $menu->locate($beforekey);

        if (!$item) {
            debugging('Unknown menu item key '.$key, DEBUG_DEVELOPER);
            return false;
        }

        if (!$itembefore) {
            debugging('Unknown menu item key (before) '.$beforekey, DEBUG_DEVELOPER);
            return false;
        }

        if ($item->get_group() !== $itembefore->get_group()) {
            debugging('Items are in different groups, item is in '.$item->get_group().' and before is in '.$itembefore->get_group(), DEBUG_DEVELOPER);
            return false;
        }

        $group = group::get($item->get_group());

        $keys = [];
        foreach ($menu->get_items_in_group($group) as $currenitem) {
            if ($currenitem->get_key() === $itembefore->get_key()) {
                $keys[] = $item->get_key();
            }
            if ($currenitem->get_key() === $item->get_key()) {
                continue;
            }
            $keys[] = $currenitem->get_key();
        }

        return self::reorder_items_in_group($userid, $group, $keys);
    }

    /**
     * Moves an item to the bottom of the list, in its current group.
     *
     * Note: this method is not used right now. Leaving it in place in
     * case we implement drag and drop of menu items in the future.
     * It is fully covered in the unit tests.
     *
     * @param int $userid
     * @param string $key
     *
     * @return bool
     */
    protected static function move_item_to_bottom(int $userid, string $key): bool {
        $factory = factory::instance($userid);

        $menu = $factory->get_menu();
        $item = $menu->locate($key);

        if (!$item) {
            debugging('Unknown menu item key ' . $key, DEBUG_DEVELOPER);
            return false;
        }

        $group = group::get($item->get_group());

        $keys = [];
        foreach ($menu->get_items_in_group($group) as $currenitem) {
            if ($currenitem->get_key() === $item->get_key()) {
                continue;
            }
            $keys[] = $currenitem->get_key();
        }
        $keys[] = $item->get_key();

        return self::reorder_items_in_group($userid, $group, $keys);
    }

    /**
     * Returns an array of all items that can be added to the menu, that haven't already been added.
     *
     * @param int $userid
     * @return item[]
     */
    public static function get_addable_items(int $userid): array {
        $factory = factory::instance($userid);

        $usermenu = $factory->get_user_preference_menu();
        $visibleuseritems = $usermenu->get_items();

        $items = [];
        foreach ($factory->get_possible_items() as $item) {
            foreach ($visibleuseritems as $visibleuseritem) {
                if ($visibleuseritem->get_key() === $item->get_key()) {
                    continue 2;
                }
            }
            $items[] = clone($item);
        }

        return $items;
    }

    /**
     * Resets the given users menu to default.
     *
     * @param int $userid
     */
    public static function reset_to_default(int $userid) {
        menu\user_preference::reset_for_user($userid);
    }

    /**
     * Sets the URL that should be returned to if the user adds or removes a page from the menu.
     * @param \moodle_url $url
     * @return bool
     */
    private static function set_quickaction_returnurl(\moodle_url $url): bool {
        global $SESSION;
        if (!isloggedin() || isguestuser()) {
            return false;
        }
        $SESSION->totara_core_quickaccessmenu_return = $url->out(false);
        return true;
    }

    /**
     * Returns the quick action return URL.
     * @internal Should only be used by totara/core/quickaccessmenu_action.php
     * @return \moodle_url
     */
    public static function get_quickaction_returnurl(): \moodle_url {
        global $SESSION;
        $url = null;
        if (isset($SESSION->totara_core_quickaccessmenu_return)) {
            $url = new \moodle_url($SESSION->totara_core_quickaccessmenu_return);
        }
        if (empty($url)) {
            $url = new \moodle_url('/user/quickaccessmenu.php');
        }
        return $url;
    }

    /**
     * Adds a button to the current page that allows users to add this page to or remove this
     * page from their quick access menu.
     *
     * @param \moodle_page $page The page to add the button to, typically $PAGE is what you want.
     * @param string $key The admin page key.
     * @param \moodle_url|null $returnurl The return URL or null to use the page URL.
     */
    public static function add_quickaction_page_button(\moodle_page $page, string $key, ?\moodle_url $returnurl = null) {
        global $USER;

        if (!has_capability('totara/core:editownquickaccessmenu', $page->context)) {
            return;
        }

        /** @var \core_renderer $output */
        $output = $page->get_renderer('core', null, RENDERER_TARGET_GENERAL);
        $baseurl = new \moodle_url('/totara/core/quickaccessmenu_action.php', ['sesskey' => sesskey(), 'key' => $key]);

        if (helper::item_exists_in_user_menu($USER->id, $key)) {
            $url = new \moodle_url($baseurl, ['action' => 'remove']);
            $input = new \single_button($url, get_string('quickaccessmenu:removefrommenu', 'totara_core'), 'post');
        } else {
            $url = new \moodle_url($baseurl, ['action' => 'add']);
            $groups = group::get_group_strings($USER->id, 25);

            if (empty($groups)) {
                $url->param('group', '-1');
                $input = new \single_button($url, get_string('quickaccessmenu:addtomenu', 'totara_core'), 'post');
            } else {
                // Add 'Unititled' group to the selection.
                $groups['-1'] = get_string('quickaccessmenu:createnewgroup', 'totara_core');

                $input = new \single_select($url, 'group', $groups, '', array('' => get_string('quickaccessmenu:addtomenu', 'totara_core')));
                $input->method = 'post';
                $input->class = $input->class . ' addtomenu';
            }
        }

        $page->set_button($page->button . $output->render($input));
        self::set_quickaction_returnurl($returnurl ?? $page->url);
    }

}
