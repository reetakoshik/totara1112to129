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

namespace totara_form\form\element;

use totara_form\element,
    totara_form\form\validator\attribute_required,
    totara_form\form\validator\valid_selection,
    totara_form\item,
    totara_form\model,
    totara_form\form\clientaction\supports_onchange_clientactions;

/**
 * Checkbox input element.
 *
 * @package   totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
class checkbox extends element implements supports_onchange_clientactions {
    /** @var string $checkedvalue */
    private $checkedvalue;

    /** @var string $uncheckedvalue */
    private $uncheckedvalue;

    /**
     * Checkbox input constructor.
     *
     * @throws \coding_exception if the checked value is the same as the unchecked value.
     * @param string $name
     * @param string $label
     * @param string $checkedvalue returned value when checkbox checked
     * @param string $uncheckedvalue returned value when checkbox unchecked
     */
    public function __construct($name, $label, $checkedvalue = '1', $uncheckedvalue = '0') {
        if (func_num_args() > 4) {
            debugging('Extra unused constructor parameters detected.', DEBUG_DEVELOPER);
        }

        parent::__construct($name, $label);
        $this->attributes = array(
            'required' => false,
        );
        $this->checkedvalue = (string)$checkedvalue;
        $this->uncheckedvalue = (string)$uncheckedvalue;
        if ($this->checkedvalue === $this->uncheckedvalue) {
            throw new \coding_exception('The checked and unchecked values must be different!');
        }

        // Add validators.
        $this->add_validator(new attribute_required());
        $this->add_validator(new valid_selection());
    }

    /**
     * Called by parent before adding this element
     * or after removing element from parent.
     *
     * @param item $parent
     */
    public function set_parent(item $parent = null) {
        parent::set_parent($parent);

        if ($parent) {
            // Validate the current value is valid if present.
            $this->get_current_value(true);
        }
    }

    /**
     * Get submitted data without validation.
     *
     * @return array
     */
    public function get_data() {
        $model = $this->get_model();
        $name = $this->get_name();

        if ($this->is_frozen()) {
            return array($name => $this->get_current_value());
        }

        $data = $model->get_raw_post_data($name);
        if ($data === null or is_array($data)) {
            // Malformed or missing data, this should not happen!
            return array($name => $this->get_initial_value());
        }

        // Selection values are validated on submission only.
        return array($name => $data);
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
        if ($finaldata) {
            $data = $this->get_data();
            $name = $this->get_name();
            $value1 = $data[$name];
        } else {
            $value1 = $this->get_field_value();
        }
        if ($operator === model::OP_FILLED or $operator === model::OP_NOT_EMPTY) {
            return ($value1 === $this->checkedvalue);
        }
        if ($operator === model::OP_NOT_FILLED or $operator === model::OP_EMPTY) {
            return ($value1 !== $this->checkedvalue);
        }

        return $this->get_model()->compare($value1, $operator, $value2);
    }

    /**
     * Get Mustache template data.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output) {
        $this->get_model()->require_finalised();

        $result = array(
            'form_item_template' => 'totara_form/element_checkbox',
            'name__' . $this->get_name() => true,
            'name' => $this->get_name(),
            'id' => $this->get_id(),
            'label' => (string)$this->label,
            'frozen' => $this->is_frozen(),
            'amdmodule' => 'totara_form/form_element_checkbox',
        );

        $attributes = $this->get_attributes();
        $attributes['checkedvalue'] = $this->checkedvalue;
        $attributes['uncheckedvalue'] = $this->uncheckedvalue;
        $attributes['checked'] = ($this->checkedvalue === $this->get_field_value());
        $this->set_attribute_template_data($result, $attributes);

        // Add errors if found, tweak attributes by validators.
        $this->set_validator_template_data($result, $output);

        // Add help button data.
        $this->set_help_template_data($result, $output);

        return $result;
    }

    /**
     * Get the value of text input element.
     *
     * @return string
     */
    public function get_field_value() {
        $model = $this->get_model();
        $name = $this->get_name();

        if ($model->is_form_submitted() and !$this->is_frozen()) {
            $data = $this->get_data();
            return $data[$name];
        }

        return $this->get_initial_value();
    }

    /**
     * Is the element data ok?
     *
     * NOTE: to be used from element_checkbox validator only.
     *
     * @param array $data from self::get_dta()
     * @return bool
     */
    public function is_valid_selection($data) {
        $name = $this->get_name();
        if (!isset($data[$name])) {
            return false;
        }
        return $this->is_valid_value($data[$name]);
    }

    /**
     * Is this a valid value?
     *
     * @param string $value
     * @return bool
     */
    protected function is_valid_value($value) {
        return ($value === $this->checkedvalue or $value === $this->uncheckedvalue);
    }

    /**
     * Returns current checkbox value.
     *
     * @param bool $debuggingifinvalid true means print debugging message if value invalid
     * @return string|null null means incorrect current value or not specified
     */
    protected function get_current_value($debuggingifinvalid = false) {
        $name = $this->get_name();
        $model = $this->get_model();

        $current = $model->get_current_data($name);
        if (array_key_exists($name, $current)) {
            $current = $current[$name];

            // Let's pretend booleans are valid here.
            if ($current === false and ($this->uncheckedvalue === '0' or $this->checkedvalue === '0')) {
                return '0';
            }
            if ($current === true and ($this->uncheckedvalue === '1' or $this->checkedvalue === '1')) {
                return '1';
            }

            if ($this->is_valid_value((string)$current)) {
                return (string)$current;
            }

            if ($debuggingifinvalid) {
                debugging('Invalid current value detected in checkbox element ' . $this->get_name(), DEBUG_DEVELOPER);
            }
        }

        return null;
    }

    /**
     * Returns current value or initial nothing value.
     *
     * @return string
     */
    protected function get_initial_value() {
        $current = $this->get_current_value();
        if ($current === null) {
            return $this->uncheckedvalue;
        } else {
            return $current;
        }
    }
}
