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
 * Base class for Totara forms elements.
 *
 * This is intended to mimic html form input elements.
 *
 * @package   totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
abstract class element implements item {
    use trait_item_find,
        trait_item_validation,
        trait_item_help;

    /** @var string name of the element, must be unique withing the elements */
    private $name;

    /** @var string name of the element, must be unique withing the elements */
    private $id;

    /** @var item $parent */
    private $parent;

    /** @var bool $frozen is this element frozen? */
    private $frozen = false;

    /** @var string[] allowed element attributes (except value, name and id) */
    protected $attributes = array();

    /** @var string label */
    protected $label;

    /**
     * Element constructor.
     *
     * @throws \coding_exception if the given name is not valid.
     * @param string $name unique element name
     * @param string $label text label of the element
     */
    public function __construct($name, $label) {
        if (!model::is_valid_name($name)) {
            // Array style names are forbidden in Totara forms!
            throw new \coding_exception('Invalid element name');
        }
        $this->name = $name;
        $this->label = $label;
    }

    /**
     * Element name.
     *
     * For simple elements it is the html name attribute of the html input element.
     * Complex elements should use arrays of this name for individual html input elements.
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
     * Do not use!
     *
     * @internal Elements cannot have children!
     *
     * @return item[]
     */
    final public function get_items() {
        return array();
    }

    /**
     * Do not use!
     *
     * @internal Elements cannot have children!
     *
     * @param item $item
     * @param int $position
     * @return item $item
     *
     * @throws \coding_exception because elements cannot contain items.
     */
    final public function add(item $item, $position = null) {
        throw new \coding_exception('Element cannot have any contained items!');
    }

    /**
     * Do not use!
     *
     * @internal Elements cannot have children.
     *
     * @throws \coding_exception if the form has been finalised and the structure can no longer change.
     * @param item $item
     * @return bool true on success, false if not found
     */
    final public function remove(item $item) {
        if ($this->is_finalised()) {
            throw new \coding_exception('Form structure cannot be changed any more!');
        }
        if ($item === $this) {
            if (!isset($this->parent)) {
                return true;
            }
            $parent = $this->parent;
            $this->set_parent(null);
            $parent->remove($this);
            return true;
        }
        return false;
    }

    /**
     * Get model.
     *
     * NOTE: This must be called only after item::add().
     *
     * @throws \coding_exception If the parent has not been set yet, without it we cannot get the model.
     * @return model
     */
    final public function get_model() {
        if (!isset($this->parent)) {
            throw new \coding_exception('No parent set yet, cannot get model!');
        }
        return $this->parent->get_model();
    }

    /**
     * Called by parent before adding this element
     * or after removing element from parent.
     *
     * @throws \coding_exception if the form has been finalised and the structure can no longer change.
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

        $this->id = 'tfiid_' . $this->get_name() . '_' . $model->get_id_suffix();
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
     * NOTE: Make sure the current data is valid before freezing the element,
     *       otherwise validators may prevent form submission.
     *
     * @throws \coding_exception if the form has been finalised and the structure can no longer change.
     * @param bool $state new state
     */
    public function set_frozen($state) {
        if ($this->is_finalised()) {
            throw new \coding_exception('Form structure cannot be changed any more!');
        }
        $this->frozen = (bool)$state;
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
     * Does this item cancel form submission?
     *
     * So called "cancel button" should return true, everything else false.
     *
     * @return bool
     */
    public function is_form_cancelled() {
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
        return false;
    }

    /**
     * Get submitted data without validation.
     *
     * @return array
     */
    abstract public function get_data();

    /**
     * Get submitted draft files.
     *
     * @return array
     */
    public function get_files() {
        return array();
    }

    /**
     * Compare element value.
     *
     * @param string $operator open of model::OP_XXX operators
     * @param mixed $value2
     * @param bool $finaldata true means use get_data(), false means use get_field_value()
     * @return bool result
     */
    public function compare_value($operator, $value2 = null, $finaldata = true) {
        $value1 = null;
        if ($finaldata) {
            $data = $this->get_data();
            $name = $this->get_name();
            if (isset($data[$name])) {
                $value1 = $data[$name];
            }
        } else {
            $value1 = $this->get_field_value();
        }

        return $this->get_model()->compare($value1, $operator, $value2);
    }

    /**
     * Get Mustache template data.
     *
     * @param \renderer_base $output
     * @return array
     */
    abstract public function export_for_template(\renderer_base $output);

    /**
     * Set value of attribute.
     *
     * @param string $name
     * @param mixed $value null means value not specified
     */
    public function set_attribute($name, $value) {
        if (!array_key_exists($name, $this->attributes)) {
            debugging('Form element attribute ' . $name . ' cannot be set', DEBUG_DEVELOPER);
            return;
        }

        if ($value === null) {
            $this->attributes[$name] = null;
        }
        // There can only be strings in HTML markup, so do the conversion now.
        $this->attributes[$name] = $value;
    }

    /**
     * Sets one or more attributes in a single call.
     *
     * This method calls set_attribute repetitively.
     *
     * @param string[] $associativeattrs
     */
    public function set_attributes(array $associativeattrs) {
        foreach ($associativeattrs as $name => $value) {
            $this->set_attribute($name, $value);
        }
    }

    /**
     * Get value of attribute.
     *
     * NOTE: 'name', 'value' and 'id' are not in attributes.
     *
     * @param string $name
     * @return mixed
     */
    public function get_attribute($name) {
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }
        return null;
    }

    /**
     * Get all attributes.
     *
     * @return array
     */
    public function get_attributes() {
        return $this->attributes;
    }

    /**
     * Add attribute stuff to template data.
     *
     * @param array &$data the template data
     * @param array $attributes final attribute values for totara_form/element_attributes
     * @return void $data argument is modified
     */
    protected function set_attribute_template_data(&$data, array $attributes) {
        foreach ($attributes as $name => $value) {
            if ($value === null) {
                $data[$name] = null;
            } else if ($value === false) {
                $data[$name] = false;
            } else if ($value === true) {
                $data[$name] = true;
            } else {
                $data[$name] = (string)$value;
            }
        }
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
     * Get form element value.
     *
     * This is intended primarily for form rendering, the expected order of value lookup is:
     *  1/ if frozen use current value (always)
     *  2/ _POST data (if form submitted)
     *  3/ current data if not null (if form not submitted)
     *  4/ some reasonable 'nothing' value based on element type (if form not submitted)
     *
     * This can be also used to find out the element value in definition method.
     *
     * @return string|array|null
     */
    abstract public function get_field_value();
}
