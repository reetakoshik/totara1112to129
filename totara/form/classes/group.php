<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_form
 */

namespace totara_form;

/**
 * Base class for Totara forms element groups.
 *
 * This does not correspond much to anything in html markup,
 * main use is to group elements and provide some custom template.
 *
 * @package   totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
abstract class group implements item {
    use trait_item_find,
        trait_item_validation,
        trait_item_help;

    /** @var item[] $items */
    private $items = array();

    /** @var item $parent */
    private $parent;

    /** @var bool $frozen is this element frozen? */
    private $frozen = false;

    /** @var string group name */
    private $name;

    /** @var string name of the element, must be unique withing the elements */
    private $id;

    /**
     * Group constructor.
     *
     * @throws \coding_exception If the given name is not valid.
     * @param string $name
     */
    public function __construct($name) {
        if (!model::is_valid_name($name)) {
            // Array style names are forbidden in Totara forms!
            throw new \coding_exception('Invalid group name');
        }
        $this->name = $name;
    }

    /**
     * Group name.
     *
     * @return string
     */
    final public function get_name() {
        return $this->name;
    }

    /**
     * Is the given name used by this element?
     *
     * This is intended mainly for elements that
     * use more entries in current and returned data.
     *
     * Please note that ___xxx is usually better solution
     * if you need to add data to _POST only.
     *
     * @param string $name
     * @return bool
     */
    public function is_name_used($name) {
        return ($name === $this->get_name());
    }

    /**
     * Element id.
     *
     * @return string
     */
    public function get_id() {
        // Make sure we have model with suffix id.
        $this->get_model();
        return $this->id;
    }

    /**
     * Is the form model finalised?
     *
     * @return bool
     */
    final public function is_finalised() {
        if (!isset($this->parent)) {
            return false;
        }
        return $this->get_model()->is_finalised();
    }

    /**
     * Returns contained items.
     *
     * @return item[]
     */
    final public function get_items() {
        return $this->items;
    }

    /**
     * Add item as child of this item.
     *
     * @throws \coding_exception if the form has been finalised and the structure can no longer change.
     * @throws \coding_exception if the parent is not yet set.
     * @throws \coding_exception if the given item already has a parent set.
     * @throws \coding_exception if the items name has already been used.
     * @param item $item
     * @param int $position null means the end, 0 is the first element, -1 means last
     * @return item|element $item
     */
    public function add(item $item, $position = null) {
        if ($this->is_finalised()) {
            throw new \coding_exception('Form structure cannot be changed any more!');
        }

        if (!isset($this->parent)) {
            throw new \coding_exception('Cannot add items before adding this item to some parent!');
        }

        if ($item->get_parent()) {
            throw new \coding_exception('Item already has parent!');
        }

        // Make sure no element is using (or abusing) the same name.
        if ($this->get_model()->find(true, 'is_name_used', 'totara_form\item', true, array($item->get_name()), false)) {
            throw new \coding_exception('Duplicate name "' . $item->get_name() . '" detected!');
        }

        $item->set_parent($this);

        if (isset($position) and $position >= 0 and $position < count($this->items)) {
            array_splice($this->items, $position, 0, array($item));
        } else {
            $this->items[] = $item;
        }

        return $item;
    }

    /**
     * Remove item recursively.
     *
     * @throws \coding_exception if the form has been finalised and the structure can no longer change.
     * @throws \coding_exception if you are trying to remove a group with existing items.
     * @param item $item
     * @return bool true on success, false if not found
     */
    public function remove(item $item) {
        if ($this->is_finalised()) {
            throw new \coding_exception('Form structure cannot be changed any more!');
        }

        if ($item === $this) {
            if ($this->get_items()) {
                throw new \coding_exception('Cannot remove group with existing items!');
            }
            if (!isset($this->parent)) {
                return true;
            }
            $parent = $this->parent;
            $this->set_parent(null);

            // This should be a short trip through the "if (!isset($this->parent))" above,
            // we need the parent to forget this item too.
            $parent->remove($this);

            return true;
        }

        $key = array_search($item, $this->items, true);
        if ($key !== false) {
            unset($this->items[$key]);
            $this->items = array_merge($this->items); // Fix keys.
            $item->remove($item);
            return true;
        }

        foreach ($this->items as $i) {
            $result = $i->remove($item);
            if ($result === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get model.
     *
     * NOTE: This must be called only after item::add().
     *
     * @throws \coding_exception if the parent has not been set yet.
     * @return model
     */
    final public function get_model() {
        if (!isset($this->parent)) {
            throw new \coding_exception('No parent set, cannot get model!');
        }
        return $this->parent->get_model();
    }

    /**
     * Called by parent before adding this group
     * or after removing group from parent.
     *
     * @throws \coding_exception if the form has been finalised and the structure can no longer change.
     * @throws \coding_exception if the given parent is empty and no parent has been set for the form yet.
     * @throws \coding_exception if the parent has already been set.
     * @throws \coding_exception if the parent is not attached to the model.
     * @param item $parent
     */
    public function set_parent(item $parent = null) {
        if ($this->is_finalised()) {
            throw new \coding_exception('Form structure cannot be changed any more!');
        }
        if ($parent === null) {
            if (!isset($this->parent)) {
                throw new \coding_exception('No parent set, cannot remove it!');
            }
            $this->parent = null;
            $this->id = null;
            return;
        }

        if (isset($this->parent)) {
            throw new \coding_exception('Parent is already set, cannot change it!');
        }

        $model = $parent->get_model();
        if (!$model) {
            throw new \coding_exception('Parent must be already attached to model!');
        }

        $this->id = 'tfiid_' . $this->get_name() . $model->get_id_suffix();
        $this->parent = $parent;
    }

    /**
     * Get parent of item.
     *
     * @return item
     */
    final public function get_parent() {
        return $this->parent;
    }

    /**
     * Freeze or unfreeze item recursively.
     *
     * @throws \coding_exception if the form has been finalised and the structure can no longer change.
     * @param bool $state new state
     */
    public function set_frozen($state) {
        if ($this->is_finalised()) {
            throw new \coding_exception('Form structure cannot be changed any more!');
        }
        $this->frozen = (bool)$state;
        foreach ($this->items as $item) {
            $item->set_frozen($state);
        }
    }

    /**
     * Is this item or its parents frozen?
     *
     * Frozen elements keep their current value from the form constructor.
     * Data submitted via form is ignored.
     *
     * This method is recursive upwards, if parent if frozen all children must be too.
     *
     * @return bool
     */
    public function is_frozen() {
        if ($parent = $this->get_parent()) {
            if ($parent->is_frozen()) {
                return true;
            }
        }
        return $this->frozen;
    }

    /**
     * Does anything cancel form submission?
     *
     * So called "cancel button" should return true, everything else false.
     *
     * @return bool
     */
    public function is_form_cancelled() {
        foreach ($this->items as $item) {
            if ($item->is_form_cancelled()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Does user submit the form with the intention to reload the form only?
     *
     * This is usually triggered by so called "no submit" buttons.
     *
     * @return bool
     */
    public function is_form_reloaded() {
        foreach ($this->items as $item) {
            if ($item->is_form_reloaded()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get submitted data without validation.
     *
     * @return array
     */
    public function get_data() {
        $result = array();
        foreach ($this->items as $item) {
            $result = array_merge($result, $item->get_data());
        }
        return $result;
    }

    /**
     * Get submitted draft files.
     *
     * @return array
     */
    public function get_files() {
        $result = array();
        foreach ($this->items as $item) {
            $result = array_merge($result, $item->get_files());
        }
        return $result;
    }

    /**
     * Get Mustache template data.
     *
     * @param \renderer_base $output
     * @return array
     */
    abstract public function export_for_template(\renderer_base $output);
}
