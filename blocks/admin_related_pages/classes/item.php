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
 * Admin related pages item class.
 */
final class item implements \cacheable_object {

    /**
     * The key used to identify this item.
     * @var string
     */
    private $key;

    /**
     * String identifier for the label.
     * @var string
     */
    private $label_identifier;

    /**
     * String component for the label.
     * @var string
     */
    private $label_component;

    /**
     * Related pages.
     * @var string[]
     */
    private $related_pages = [];

    /**
     * Parent page references.
     * @var string[]
     */
    private $parents = [];

    /**
     * The URL used to access this item.
     * @var \moodle_url
     */
    private $url;

    /**
     * Item constructor
     *
     * @param string $key The key for this item, should be the admin page name.
     * @param string $identifier The string identifier of a string used to label this item.
     * @param string $component The string component of a string used to label this item.
     * @param string[] $parents An array of admin page names to consider "parents" of this item.
     */
    public function __construct(string $key, string $identifier, string $component, array $parents = []) {
        $this->key = $key;
        $this->label_identifier = $identifier;
        $this->label_component = $component;
        $this->url = new \moodle_url('/');
        foreach ($parents as $parent) {
            $this->add_parent($parent);
        }
    }

    /**
     * Adds a parent page reference to this item.
     *
     * Parent page references are used to ensure that this item is considered active if it is the active node
     * or if any of the parent nodes are active.
     *
     * @param string $parent The admin page/category name to reference as a parent of this item.
     */
    public function add_parent(string $parent) {
        if (!in_array($parent, $this->parents)) {
            $this->parents[] = $parent;
        }
    }

    /**
     * Returns an array containing all of the parent page names.
     *
     * Parent page references are used to ensure that this item is considered active if it is the active node
     * or if any of the parent nodes are active.
     *
     * @return array
     */
    public function get_parents(): array {
        return $this->parents;
    }

    /**
     * Attaches a relationship between two admin nodes to this item.
     *
     * This essentially forms a three way relation, whereby we consider this item
     * to be represented by the given key, and with a relationship to the for key.
     *
     * The key can be for any item, as can the for.
     *
     * These relationships are used to determine if this item is a relation to the current
     * item being viewed by the user.
     * Allowing us to remove the this item, if the related page is the page being currently viewed.
     *
     * @param string $key
     * @param string $for
     */
    public function add_related_page(string $key, string $for) {
        if ($key === '') {
            // Empty keys are not permitted, the associative array converts them to 0.
            throw new \coding_exception('Invalid related page key.', $for);
        }
        if (!isset($this->related_pages[$key])) {
            $this->related_pages[$key] = $for;
        }
    }

    /**
     * Returns the URL used to access this item.
     *
     * @return \moodle_url|null
     */
    public function get_url(): \moodle_url {
        return $this->url;
    }

    /**
     * Sets the URL that should be used to access this item.
     *
     * @param \moodle_url $url
     */
    public function set_url(\moodle_url $url) {
        $this->url = $url;
    }

    /**
     * Returns the key that identifies this item.
     *
     * @return string
     */
    public function get_key(): string {
        return $this->key;
    }

    /**
     * Returns the label for this item.
     *
     * @return \lang_string|string
     */
    public function get_label() {
        return new \lang_string($this->label_identifier, $this->label_component);
    }

    /**
     * Returns an array of admin page names that this item represents.
     *
     * @return array
     */
    public function get_related_pages(): array {
        return $this->related_pages;
    }

    /**
     * Returns an array of data that can be cached, and rebuilt into this item.
     *
     * @return array
     */
    public function prepare_to_cache(): array {
        return [
            (string)$this->key,
            (string)$this->label_identifier,
            (string)$this->label_component,
            (string)$this->url->out_as_local_url(false),
            (array)$this->get_parents(),
            (array)$this->get_related_pages(),
        ];
    }

    /**
     * Builds an item given information returned from the cache.
     *
     * @param array $data
     * @return item
     */
    public static function wake_from_cache($data): item {
        [$key, $identifier, $component, $url, $parents, $relatedpages] = $data;

        $item = new item($key, $identifier, $component, $parents);
        $item->set_url(new \moodle_url($url));
        foreach ($relatedpages as $key => $for) {
            $item->add_related_page($key, $for);
        }
        return $item;
    }
}
