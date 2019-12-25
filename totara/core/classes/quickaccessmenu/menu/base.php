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
use totara_core\quickaccessmenu\menu;

/**
 * Menu base class.
 *
 * This abstract class provides all of the basic menu functionality.
 *
 * @internal
 */
abstract class base implements menu {

    /**
     * @var factory
     */
    private $factory;

    /**
     * @var item[]
     */
    private $items = [];

    /**
     * Construct the menu.
     *
     * @param factory $factory
     * @param array   $items Optionally the items to add immediately.
     */
    final protected function __construct(factory $factory, array $items = []) {
        $this->factory = $factory;
        foreach ($items as $item) {
            $this->add_item($item);
        }
    }

    /**
     * Add an item to this menu.
     *
     * @param item $item
     */
    final public function add_item(item $item): void {
        if (debugging() && isset($this->items[$item->get_key()])) {
            debugging("Duplicate key '{$item->get_key()}' found when preparing quick access menu", DEBUG_DEVELOPER);
        }
        $this->items[$item->get_key()] = $item;
    }

    /**
     * Replaces an item within this menu, with an updated version of the item.
     *
     * @param item $item
     * @throws \coding_exception
     */
    final public function replace_item(item $item): void {
        if (isset($this->items[$item->get_key()])) {
            $this->items[$item->get_key()] = $item;
            return;
        }
        throw new \coding_exception('Item cannot be replaced as it doesn\'t currently exist.');
    }

    /**
     * Find and return the item with the given key, or null if we don't know about it.
     *
     * @param string $key
     * @return item|null
     */
    final public function locate(string $key) {
        if (isset($this->items[$key])) {
            return $this->items[$key];
        }
        return null;
    }

    /**
     * Returns all items.
     *
     * @return item[]
     */
    final public function get_items(): array {
        $items = array_filter($this->items, function(item $item) {
            return $item->get_visible();
        });
        return $items;
    }

    /**
     * Returns all visible items in the given group.
     *
     * @param group $group
     * @param bool $includehidden
     * @return item[]
     */
    final public function get_items_in_group(group $group, bool $includehidden = false): array {
        $items = array_filter($this->items, function(item $item) use ($group, $includehidden) {

            $include = $includehidden ? true : $item->get_visible();

            return $include && $item->get_group() === (string)$group;
        });
        usort($items, [item::class, 'sort_items']);
        return $items;
    }

    /**
     * Returns an array of all items, regardless of whether the user can see them or not.
     *
     * @return item[]
     */
    final public function get_all_items(): array {
        return array_values($this->items);
    }

    /**
     * Returns the factory that created this menu.
     *
     * @return factory
     */
    final protected function get_factory(): factory {
        return $this->factory;
    }

    /**
     * Returns the user id that this menu was created for.
     * @return int
     */
    final public function get_userid(): int {
        return $this->factory->get_userid();
    }
}
