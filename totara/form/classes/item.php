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
 * Totara form item.
 *
 * This interface is used to construct and maintain the tree of things in form model.
 *
 * NOTE: there are also optional methods, some of them implemented in traits, do not use them for any other purpose!
 *   - set_type($paramtype) set PARAM_XXX if value cleaning supported
 *   - get_type() get PARAM_XXX if value cleaning supported
 *   - get_field_value() returns the low level input element value - string or array
 *
 * @package   totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
interface item extends \templatable {
    /**
     * Get item name.
     *
     * This is used for validation and other purposes,
     * the names must be unique in each form.
     *
     * @return string
     */
    public function get_name();

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
    public function is_name_used($name);

    /**
     * Get item id.
     *
     * Ids must be unique in each form.
     *
     * @return string
     */
    public function get_id();

    /**
     * Is the form model finalised?
     *
     * @return bool
     */
    public function is_finalised();

    /**
     * Returns contained items.
     *
     * @return item[]
     */
    public function get_items();

    /**
     * Add new sub-item to this item.
     *
     * NOTE: form must not be finalised yet.
     *
     * @param item $item
     * @param int $position null means the end
     * @return item $item
     */
    public function add(item $item, $position = null);

    /**
     * Remove item recursively.
     *
     * NOTE: form must not be finalised yet.
     *
     * @param item $item
     * @return bool true on success, false if not found
     */
    public function remove(item $item);

    /**
     * Get model.
     *
     * NOTE: This must be called only after adding this item to some parent.
     *
     * @return model
     */
    public function get_model();

    /**
     * Called by parent before adding this item
     * or after removing item from parent.
     *
     * NOTE: Do not call directly!
     *
     * @param item $parent
     */
    public function set_parent(item $parent = null);

    /**
     * Get parent of item.
     *
     * @return item
     */
    public function get_parent();

    /**
     * Freeze or unfreeze item.
     *
     * Frozen elements keep their current value from the form constructor.
     * Data submitted via form is ignored.
     *
     * NOTE: form must not be finalised yet.
     *
     * @param bool $state new state
     */
    public function set_frozen($state);

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
    public function is_frozen();

    /**
     * Find first child item that matches the criteria.
     *
     * @param mixed $value value to match
     * @param string $method method to get value from item
     * @param string $class filter by class, totara_form\form\item means all
     * @param bool $recursive true means look in sub items too
     * @param array $arguments method arguments
     * @param bool $strict use strict comparison
     * @return item found item or null if not found
     */
    public function find($value, $method, $class, $recursive = true, array $arguments = null, $strict = true);

    /**
     * Does this item cancel form submission?
     *
     * So called "cancel button" should return true, everything else false.
     *
     * NOTE: form must be already finalised.
     *
     * @return bool
     */
    public function is_form_cancelled();

    /**
     * Does user submit the form with the intention to reload the form only?
     *
     * This is usually triggered by so called "no submit" buttons.
     *
     * NOTE: form must be already finalised.
     *
     * @return bool
     */
    public function is_form_reloaded();

    /**
     * Add validator to this item.
     *
     * NOTE: form must not be finalised yet.
     *
     * @param validator $validator
     * @return validator
     */
    public function add_validator(validator $validator);

    /**
     * Remove validator from this item.
     *
     * NOTE: form must not be finalised yet.
     *
     * @param validator $validator
     */
    public function remove_validator(validator $validator);

    /**
     * Validate the recursively.
     *
     * NOTE: form must be already finalised.
     */
    public function validate();

    /**
     * Add validation error message for this item.
     *
     * @param string $error
     */
    public function add_error($error);

    /**
     * Get list of errors if present for this item.
     *
     * @return string[]
     */
    public function get_errors();

    /**
     * Is this item and all children valid (without errors)?
     *
     * NOTE: form must be already finalised.
     *
     * @return bool
     */
    public function is_valid();

    /**
     * Get submitted data without validation.
     *
     * @return array
     */
    public function get_data();

    /**
     * Get submitted draft files without validation.
     *
     * NOTE: form must be already finalised.
     *
     * @return array
     */
    public function get_files();

    /**
     * Add help button to item.
     *
     * @param string $identifier help string identifier without _help suffix
     * @param string $component component name to look the help string in
     * @param string $linktext optional text to display next to the icon
     */
    public function add_help_button($identifier, $component = 'core', $linktext = '');

    /**
     * Get Mustache template data.
     *
     * NOTE: form must be already finalised.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output);
}
