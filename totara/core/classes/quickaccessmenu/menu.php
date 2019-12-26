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

namespace totara_core\quickaccessmenu;

/**
 * Menu interface
 *
 * All menus used by the quick access menu need to implement this interface.
 * Please note that this interface is considered internal, the supported menus must all exist in
 * totara/core/classes/quickaccessmenu/menu
 *
 * @internal
 */
interface menu {

    /**
     * Returns an instance of the menu implementing this interface.
     *
     * @param factory $factory
     * @return menu
     */
    public static function get(factory $factory);

    /**
     * Add an item to this menu.
     *
     * @param item $item
     */
    public function add_item(item $item);

    /**
     * Replaces an item within this menu, with an updated version of the item.
     *
     * @param item $item
     */
    public function replace_item(item $item);

    /**
     * Find and return the item with the given key, or null if we don't know about it.
     *
     * @param string $key
     * @return item|null
     */
    public function locate(string $key);

    /**
     * Returns all items.
     *
     * @return item[]
     */
    public function get_items(): array;

    /**
     * Returns all visible items in the given group.
     *
     * @param group $group
     * @param bool $includehidden
     * @return item[]
     */
    public function get_items_in_group(group $group, bool $includehidden = false): array;

    /**
     * Returns an array of all items, regardless of whether the user can see them or not.
     *
     * @return item[]
     */
    public function get_all_items();
}
