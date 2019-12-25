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
 * @package totara_core
 */

namespace totara_core\quickaccessmenu\menu;

use totara_core\quickaccessmenu\factory;
use totara_core\quickaccessmenu\group;
use totara_core\quickaccessmenu\item;
use totara_core\quickaccessmenu\preference_helper;

final class user_preference extends base {

    /**
     * The user preference for the menu items.
     */
    private const PREFERENCE = 'items';

    /**
     * Gets the user preference menu for the give user.
     *
     * @param factory $factory
     * @return user_preference
     */
    public static function get(factory $factory): user_preference {
        $menu = new user_preference($factory);
        $preference = preference_helper::get_preference($factory->get_userid(), self::PREFERENCE, null);
        if ($preference === null) {
            return $menu;
        }
        if (!is_array($preference)) {
            debugging('Invalid user preference stored for quickaccessmenu', DEBUG_DEVELOPER);
            preference_helper::unset_preference($factory->get_userid(), self::PREFERENCE);
            return $menu;
        }
        $groups = group::get_groups($factory->get_userid());
        foreach ($preference as $entry) {
            $key = (isset($entry->key)) ? $entry->key : null;
            $group = (isset($entry->group) && isset($groups[$entry->group])) ? $groups[$entry->group] : null;
            $label = (isset($entry->label)) ? $entry->label : null;
            $weight = (isset($entry->weight)) ? $entry->weight : null;
            $visible = (isset($entry->visible)) ? $entry->visible : null;

            $item = item::from_preference($key, $group, $label, $weight, $visible);

            $menu->add_item($item);
        }

        return $menu;
    }

    /**
     * Saves the users preference.
     *
     * The expectation here is that this item has been modified to reflect the desired state.
     */
    public function save(): void {
        $data = [];
        foreach ($this->get_all_items() as $item) {
            $data[] = $item->get_preference_array();
        }
        preference_helper::set_preference($this->get_userid(), self::PREFERENCE, $data);
    }

    /**
     * Return all items, visible or not, within the given group.
     *
     * @param group $group
     * @return item[]
     */
    public function get_all_items_in_group(group $group): array {
        $groups = $this->get_all_items_by_group();
        if (isset($groups[(string)$group])) {
            return $groups[(string)$group];
        }
        return array();
    }

    /**
     * Returns all items visible or not, organised by group.
     *
     * @return array
     */
    public function get_all_items_by_group(): array {
        $groups = [];
        foreach (group::get_group_keys($this->get_userid()) as $group) {
            $groups[$group] = [];
        }
        foreach ($this->get_all_items() as $item) {
            $group = $item->get_group();
            if (empty($group)) {
                $baseitem = $this->get_factory()->get_menu()->locate($item->get_key());
                if ($baseitem) {
                    $group = $baseitem->get_group();
                } else {
                    // Happens if the item used in the preferences is no longer an item in the system.
                    continue;
                }
            }
            $groups[$group][] = $item;
        }
        foreach ($groups as $group => &$items) {
            if (empty($items)) {
                unset($groups[$group]);
                continue;
            }
            usort($items, [item::class, 'sort_items']);
        }
        return $groups;
    }

    /**
     * Resets the given users preferences to default.
     *
     * @param int $userid
     */
    public static function reset_for_user(int $userid): void {
        preference_helper::reset_for_user($userid);
    }
}
