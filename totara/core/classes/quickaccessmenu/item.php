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
 * A menu item.
 *
 * Items can be complete, or partial in nature.
 * They are always constructed using one of the public static from methods.
 */
final class item {

    /**
     * The key used to identify the item
     * @var string|null
     */
    private $key;

    /**
     * The group that the item belongs to.
     * @var group|null
     */
    private $group;

    /**
     * The label for this item.
     * @var string|null
     */
    private $label;

    /**
     * The weight of this item.
     * @var int|null
     */
    private $weight;

    /**
     * Whether or not this item is visible to the user.
     * @var bool|null
     */
    private $visible;

    /**
     * The URL for thi item.
     * @var \moodle_url|null
     */
    private $url;

    /**
     * Set to true if this item comes from a preference
     * @var bool
     */
    private $from_preference = false;

    /**
     * Creates this item from a system default provider
     * @param string $key
     * @param group $group
     * @param \lang_string $label
     * @param int $weight
     * @return item
     */
    public static function from_provider(string $key, group $group, \lang_string $label, int $weight): item {
        $item = new self($key, $group, $label, $weight, true, null);
        return $item;
    }

    /**
     * Creates this item from a preference
     *
     * @param null|string      $key
     * @param group|null       $group
     * @param null|string      $label
     * @param int|null         $weight
     * @param bool|null        $visible
     * @param \moodle_url|null $url
     *
     * @return item
     */
    public static function from_preference(?string $key, ?group $group = null, ?string $label = null, ?int $weight = null, ?bool $visible = null, ?\moodle_url $url = null): item {
        $item = new self($key, $group, $label, $weight, $visible, $url);
        $item->from_preference = true;
        return $item;
    }

    /**
     * Creates this item from a default setting in config.php
     *
     * @param string      $key
     * @param group       $group
     * @param null|string $label
     * @param int|null    $weight
     *
     * @return item
     */
    public static function from_config(string $key, group $group, ?string $label = null, ?int $weight = null): item {
        $item = new self($key, $group, $label, $weight, true, null);
        return $item;
    }

    /**
     * Creates this item from part of the admin tree
     *
     * @param \part_of_admin_tree $part
     * @return item
     */
    public static function from_part_of_admin_tree(\part_of_admin_tree $part): item {
        global $CFG;

        static $running_weight = 0;
        $running_weight++;

        $key = $part->name;
        $label = $part->visiblename;
        $weight = $running_weight;
        $visible = false;

        // Add code for URL here
        if ($part instanceof \admin_settingpage) {
            $url = new \moodle_url('/' . $CFG->admin . '/settings.php', array('section' => $part->name));
        } else if ($part instanceof \admin_externalpage) {
            $url = new \moodle_url($part->url);
        } else if ($part instanceof \admin_category) {
            throw new \coding_exception('Admin categories cannot be used as menu items.', $part->name);
        } else {
            // Unreachable, but here just in case someone introduces something new.
            throw new \coding_exception('Unknown part', get_class($part));
        }

        return new self($key, group::get(group::LEARN), $label, $weight, $visible, $url);
    }

    /**
     * Creates a new item given two items.
     *
     * The second item will have precedence.
     *
     * @internal This function is intended for use by the quickaccessmenu api only.
     * @param item $a
     * @param item $b
     * @return item
     */
    public static function merge(item $a, item $b): item {
        $key = $a->key;
        $group = ($b->group !== null) ? $b->group : $a->group;
        $label = ($b->label !== null) ? $b->label : $a->label;
        $weight = ($b->weight !== null) ? $b->weight : $a->weight;
        $visible = ($b->visible !== null) ? $b->visible : $a->visible;
        $url = ($b->url !== null) ? $b->url : $a->url;

        $newitem = new self($key, $group, $label, $weight, $visible, $url);

        return $newitem;
    }

    /**
     * Item constructor.
     *
     * Private because if you want to construct an item you must use one of the appropriate from_* methods.
     *
     * @param string|null $key
     * @param group|null $group
     * @param string|null $label
     * @param int|null $weight
     * @param bool|null $visible
     * @param \moodle_url| null $url
     */
    private function __construct(?string $key, ?group $group, ?string $label, ?int $weight, ?bool $visible, ?\moodle_url $url) {
        $this->key = $key;
        $this->group = $group;
        $this->label = $label;
        $this->weight = $weight;
        $this->visible = $visible;
        $this->url = $url;
    }

    /**
     * Returns the key used to identify this item
     * @return string
     */
    public function get_key(): string {
        return $this->key;
    }

    /**
     * Returns the group this item should appear within
     * @return string
     */
    public function get_group(): string {
        return (string)$this->group;
    }

    /**
     * Returns the label for this item
     * @return string
     */
    public function get_label(): string {
        return (string)$this->label;
    }

    /**
     * Returns the sort weight of this item
     * @return int
     */
    public function get_weight(): int {
        return $this->weight;
    }

    /**
     * Returns true if this item is visible to the user
     * @return bool
     */
    public function get_visible(): bool {
        return $this->visible == false ? false : true;
    }

    /**
     * Returns url for this item
     * @return \moodle_url
     */
    public function get_url(): ?\moodle_url {
        return isset($this->url) ? $this->url : null;
    }

    /**
     * Sorts two items
     *
     * Suitable for use with usort
     *
     * @internal This function is intended for use by the quickaccessmenu api only.
     * @param item $a
     * @param item $b
     * @return int
     */
    public static function sort_items(item $a, item $b): int {
        $weight_a = $a->weight;
        $weight_b = $b->weight;
        if ($weight_a === $weight_b) {
            $label_a = $a->get_label();
            $label_b = $b->get_label();
            return strcmp($label_a, $label_b);
        }
        return ($weight_a > $weight_b) ? 1 : -1;
    }

    /**
     * Sets the weight for this item
     * @param int $newweight
     * @throws \coding_exception if this is not a preference.
     */
    public function set_weight(int $newweight) {
        if (!$this->from_preference) {
            throw new \coding_exception('Only preference items can be modified.');
        }
        $this->weight = $newweight;
    }

    /**
     * Sets the label for this item
     * @param string $newlabel
     * @throws \coding_exception if this is not a preference.
     */
    public function set_label(string $newlabel) {
        if (!$this->from_preference) {
            throw new \coding_exception('Only preference items can be modified.');
        }
        if ($newlabel === '') {
            $newlabel = null;
        }
        $this->label = $newlabel;
    }

    /**
     * Sets the group for this item
     * @param group $newgroup
     * @throws \coding_exception if this is not a preference.
     */
    public function set_group(group $newgroup) {
        if (!$this->from_preference) {
            throw new \coding_exception('Only preference items can be modified.');
        }
        $this->group = $newgroup;
    }

    /**
     * Makes the item visible
     * @throws \coding_exception if this is not a preference.
     */
    public function make_visible() {
        if (!$this->from_preference) {
            throw new \coding_exception('Only preference items can be modified.');
        }
        $this->visible = true;
    }

    /**
     * Makes the item hidden
     * @throws \coding_exception if this is not a preference.
     */
    public function make_hidden() {
        if (!$this->from_preference) {
            throw new \coding_exception('Only preference items can be modified.');
        }
        $this->visible = false;
    }

    /**
     * Returns this item as a preference array
     *
     * @return array
     */
    public function get_preference_array(): array {
        if (!$this->from_preference) {
            throw new \coding_exception('Preference arrays can only be exported for preference items');
        }
        $data = [];
        $data['key'] = (isset($this->key)) ? $this->get_key() : null;
        $data['group'] = (isset($this->group)) ? $this->get_group() : null;
        $data['label'] = (isset($this->label)) ? $this->get_label() : null;
        $data['weight'] = (isset($this->weight)) ? $this->get_weight() : null;
        $data['visible'] = (isset($this->visible)) ? $this->get_visible() : null;
        return $data;
    }
}
