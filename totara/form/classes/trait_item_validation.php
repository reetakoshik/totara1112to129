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
 * Trait for item validation.
 *
 * @package   totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
trait trait_item_validation {
    /**
     * @internal do not use directly!
     *
     * @var string[] validation errors
     */
    private $errors = array();

    /**
     * @internal do not use directly!
     *
     * @var validator[] list of validators for this item
     */
    private $validators = array();

    /**
     * Add validator to this item.
     *
     * @throws \coding_exception if the form structure has been finalised and validators cannot be added.
     * @param validator $validator
     * @return validator
     */
    public function add_validator(validator $validator) {
        /** @var item $this */
        if ($this->is_finalised()) {
            throw new \coding_exception('Form model is already finalised, cannot add validators!');
        }
        /** @var trait_item_validation $this */
        $this->validators[] = $validator;

        // Let validators tweak the attributes as necessary.
        /** @var item $this */
        $validator->added_to_item($this);
    }

    /**
     * Remove validator from this item.
     *
     * @throws \coding_exception if the form structure has been finalised and validator cannot be removed.
     * @param validator $validator
     */
    public function remove_validator(validator $validator) {
        /** @var item $this */
        if ($this->is_finalised()) {
            throw new \coding_exception('Form model is already finalised, cannot remove validators!');
        }
        /** @var trait_item_validation $this */
        $key = array_search($validator, $this->validators, true);
        if ($key !== false) {
            unset($this->validators[$key]);
            $this->validators = array_merge($this->validators);
        }
    }

    /**
     * Validate the submitted element data
     * for this item and all children.
     *
     * Validation adds errors to each problematic item.
     *
     * NOTE: Add your own validator instead of overriding.
     */
    final public function validate() {
        /** @var item $this */
        $this->get_model()->require_finalised();

        /** @var trait_item_validation $this */
        foreach ($this->validators as $validator) {
            /** @var item $this */
            $validator->validate();
        }
        // If there are any children validate them too.
        /** @var item $this */
        foreach ($this->get_items() as $item) {
            $item->validate();
        }
    }

    /**
     * Add validation error message for this item.
     *
     * @param string $error
     */
    public function add_error($error) {
        if (in_array($error, $this->errors, true)) {
            return;
        }
        $this->errors[] = $error;
    }

    /**
     * Get list of errors if present for this item.
     *
     * @return string[]
     */
    public function get_errors() {
        return $this->errors;
    }

    /**
     * Is this item and all children valid (without errors)?
     *
     * @return bool
     */
    public function is_valid() {
        /** @var item $this */
        if (!empty($this->errors)) {
            return false;
        }
        foreach ($this->get_items() as $item) {
            if (!$item->is_valid()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Add errors to template data and tweak attributes if necessary.
     *
     * @param array &$data
     * @param \renderer_base $output
     * @return void $data argument is modified
     */
    protected function set_validator_template_data(&$data, \renderer_base $output) {
        $data['errors_has_items'] = false;
        $data['errors'] = array();
        if ($this->errors) {
            $data['errors_has_items'] = true;
            foreach ($this->errors as $error) {
                $data['errors'][] = array('message' => (string)$error);
            }
        }
        foreach ($this->validators as $validator) {
            $validator->set_validator_template_data($data, $output);
        }
    }
}
