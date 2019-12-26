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
 * @package block_admin_related_pages
 */

namespace block_admin_related_pages;

final class map implements \cacheable_object {

    /**
     * @var \admin_root
     */
    private $adminroot;

    /**
     * @var item[]
     */
    private $items = [];

    /**
     * @var group[]
     */
    private $groups = [];

    /**
     * Maps a admin page name to one or more items.
     *
     * @var array
     */
    private $keymap = [];

    /**
     * True if this map has been finalised and can no longer be edited.
     * @var bool
     */
    private $finalised = false;

    /**
     * Constructs a new map.
     *
     * @param group[] $groups
     * @param item[] $items
     */
    public function __construct(array $groups = [], array $items = []) {
        foreach ($groups as $group) {
            $this->add_group($group);
        }
        foreach ($items as $item) {
            $this->add_item($item);
        }
    }

    /**
     * Finalises this map.
     *
     * This method resolves all relationships and ensures the map is complete.
     * After this method has been called the map can no longer be altered.
     */
    private function finalise() {
        /** @var item[] $items */
        $items = [];

        $adminroot = $this->get_admin_root();
        foreach ($this->groups as $group) {
            $group->resolve_relationships($adminroot);
            $items = array_merge($items, $group->get_items());
        }
        $items = array_merge($items, $this->items);

        foreach ($items as $item) {
            $item = $this->validate_item($item);
            if ($item === null) {
                continue;
            }
            foreach ($item->get_parents() as $fromkey) {
                $page = $adminroot->locate($fromkey);
                if ($page instanceof \admin_category) {
                    foreach ($page->get_children(false) as $child) {
                        if ($child instanceof \admin_externalpage || $child instanceof \admin_settingpage) {
                            $this->add_item_to_keymap($child->name, $item);
                        }
                    }
                } else {
                    $this->add_item_to_keymap($fromkey, $item);
                }
            }
            foreach ($item->get_related_pages() as $key => $for) {
                $this->add_item_to_keymap($key, $item);
            }
        }

        // Finalise at the end of the path.
        $this->finalised = true;
    }

    /**
     * Ensures the map has not yet been finalised.
     *
     * @throws \coding_exception
     */
    private function ensure_not_finalised() {
        if ($this->finalised) {
            throw new \coding_exception('Relevant page map has already been finalised.');
        }
    }

    /**
     * Returns the admin root.
     *
     * @return \admin_root
     */
    private function get_admin_root(): \admin_root {
        if ($this->adminroot === null) {
            $this->adminroot = admin_get_root(false, false);
        }
        return $this->adminroot;
    }

    /**
     * Adds the given group to the map.
     *
     * @param group $group
     */
    public function add_group(group $group) {
        $this->ensure_not_finalised();
        $this->groups[] = $group;
    }

    /**
     * Adds the given item to the key map.
     *
     * @param string $key
     * @param item $item
     */
    private function add_item_to_keymap(string $key, item $item) {
        if (isset($this->keymap[$key])) {
            $this->keymap[$key][] = $item;
        } else {
            $this->keymap[$key] = [$item];
        }
    }

    /**
     * Validates an item against the admin structure.
     *
     * @param item $item
     * @return item|null The item if valid, null if not.
     */
    private function validate_item(item $item): ?item {
        global $CFG;

        $this->ensure_not_finalised();

        $key = $item->get_key();
        $page = $this->get_admin_root()->locate($key);

        if ($page === null || !$page instanceof \part_of_admin_tree) {
            return null;
        }

        if ($page->is_hidden() || !$page->check_access()) {
            // The page is hidden, or you can't see it.
            return null;
        }

        if ($page instanceof \admin_settingpage) {
            $url = new \moodle_url('/' . $CFG->admin . '/settings.php', ['section' => $page->name]);
        } else if ($page instanceof \admin_externalpage) {
            $url = new \moodle_url($page->url);
        } else if ($page instanceof \admin_category) {
            $url = new \moodle_url('/' . $CFG->admin . '/category.php', ['category' => $page->name]);
        }

        if (!isset($url)) {
            debugging('Admin page with no URL specified in map', $page->name);
            return null;
        }

        $item->set_url($url);

        return $item;
    }

    /**
     * Adds an item to the map.
     *
     * @param item $item
     */
    public function add_item(item $item) {
        $this->ensure_not_finalised();
        $this->items[] = $item;
    }

    /**
     * Returns an array of items that have been mapped to the given key.
     *
     * @param string $key
     * @return item[]
     */
    public function get_mapped_items(string $key): array {

        if (!$this->finalised) {
            $this->finalise();
        }

        if (isset($this->keymap[$key])) {
            $return = [];
            foreach ($this->keymap[$key] as $item) {
                /** @var item $item */
                $key = $item->get_key();
                if (isset($return[$key])) {
                    continue;
                } else {
                    $return[$key] = $item;
                }
            }
            return $return;
        }
        return [];
    }

    /**
     * Converts this map into a simple data structure that can be cached.
     *
     * @internal Should only be by the cache API.
     * @return array
     */
    public function prepare_to_cache() {

        $this->finalise();

        $data = [
            'map' => [],
            'items' => [],
        ];
        foreach ($this->keymap as $fromkey => $items) {
            /** @var item[] $items */
            $data['map'][$fromkey] = [];
            foreach ($items as $item) {
                $key = $item->get_key();
                if (in_array($key, $data['map'][$fromkey])) {
                    continue;
                } else {
                    $data['map'][$fromkey][] = $key;
                }
                $data['items'][$key] = $item->prepare_to_cache();
            }
        }
        return $data;
    }

    /**
     * Takes data from the cache and turns it back into a map.
     *
     * @internal Should only be by the cache API.
     * @param array $data
     * @return map
     */
    public static function wake_from_cache($data) {
        $map = new map();
        foreach ($data['items'] as $key => $item) {
            $data['items'][$key] = item::wake_from_cache($item);
        }
        foreach ($data['map'] as $fromkey => $keys) {
            $map->keymap[$fromkey] = [];
            foreach ($keys as $key) {
                $map->keymap[$fromkey][] = $data['items'][$key];
            }
        }
        $map->finalise();
        return $map;
    }
}
