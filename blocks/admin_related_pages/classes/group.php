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

/**
 * Item group class.
 */
final class group {

    /**
     * An array of items, considered to be in this group.
     * @var item[]
     */
    private $items = [];

    /**
     * An array of relationships that should be propogated to the items in this group.
     * @var string[]
     */
    private $relationships = [];

    /**
     * Stored separately to items as additional items, outside of this group may have their keys added here.
     * @var string[]
     */
    private $keys = [];

    /**
     * Constructs a new group
     *
     * @param array $items
     */
    public function __construct(array $items = []) {
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    /**
     * Adds an item to this group.
     *
     * @param item $item
     */
    public function add(item $item) {
        $this->items[] = $item;
        $this->keys[] = $item->get_key();
        foreach ($item->get_parents() as $attachment) {
            $this->add_relationship($attachment, $item->get_key());
        }
    }

    /**
     * Adds a relationship to this group.
     *
     * @param string $to
     * @param string $for
     */
    public function add_relationship(string $to, string $for) {
        if (!isset($this->relationships[$to])) {
            $this->relationships[$to] = $for;
        }
    }

    /**
     * Returns the keys belonging to the items in this group.
     *
     * @return string[]
     */
    public function get_keys() {
        return $this->keys;
    }

    /**
     * Returns the items in this group.
     *
     * @return item[]
     */
    public function get_items() {
        return $this->items;
    }

    /**
     * Resolves the relationships between for this group and its items.
     *
     * @param \admin_root $adminroot
     */
    public function resolve_relationships(\admin_root $adminroot) {
        foreach ($this->get_keys() as $key) {
            foreach ($this->get_items() as $item) {
                $item->add_parent($key);
            }
        }
        foreach ($this->relationships as $attachment => $for) {
            $page = $adminroot->locate($attachment);
            if (empty($page)) {
                continue;
            }

            if ($page instanceof \admin_category) {
                foreach ($page->get_children(false) as $child) {
                    if ($child instanceof \admin_externalpage || $child instanceof \admin_settingpage) {
                        $this->keys[] = $child->name;
                        foreach ($this->items as $item) {
                            $item->add_related_page($child->name, $for);
                        }
                    }
                }
            } else {
                $this->keys[] = $attachment;
                foreach ($this->items as $item) {
                    $item->add_related_page($attachment, $for);
                }
            }
        }
    }
}
