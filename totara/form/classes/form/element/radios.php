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
 * Group of radio buttons.
 *
 * @package   totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
class radios extends element implements supports_onchange_clientactions {
    /** @var array $options */
    private $options;

    /**
     * Group of radio buttons constructor.
     *
     * @throws \coding_exception if the list of radio buttons is empty.
     * @param string $name
     * @param string $label
     * @param string[] $options associative array "option value"=>"option text"
     */
    public function __construct($name, $label, array $options) {
        if (func_num_args() > 3) {
            debugging('Extra unused constructor parameters detected.', DEBUG_DEVELOPER);
        }

        parent::__construct($name, $label);
        $this->attributes = array(
            'required' => false, // This is a group of elements, 'required' means user must select at least one.
            'horizontal' => false,
        );

        if (empty($options)) {
            throw new \coding_exception('List of radio button options cannot be empty');
        }

        // Normalise the values that are stored as keys.
        $this->options = array();
        foreach ($options as $k => $v) {
            $this->options[(string)$k] = $v;
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
     * NOTE: null value means no radio selected
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
        if (is_array($data)) {
            // Some weird error, use initial value as safe fallback.
            return array($name => $this->get_initial_value());
        }

        if ($data === null) {
            // Either not checked or disabled, we need to find out.
            $initial = $this->get_initial_value();
            if ($initial !== null) {
                // Some hackery is going on, they cannot uncheck all radios!
                return array($name => $initial);
            }

            // Nothing was selected yet.
            return array($name => null);
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
     * @return bool result, may return false for invalid data or operator
     */
    public function compare_value($operator, $value2 = null, $finaldata = true) {
        if ($finaldata) {
            $data = $this->get_data();
            $name = $this->get_name();
            $value1 = $data[$name];
        } else {
            $value1 = $this->get_field_value();
        }
        // Null means no radio was selected.
        if ($operator === model::OP_FILLED or $operator === model::OP_NOT_EMPTY) {
            return ($value1 !== null);
        }
        if ($operator === model::OP_NOT_FILLED or $operator === model::OP_EMPTY) {
            return ($value1 === null);
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

        $id = $this->get_id();

        $result = array(
            'form_item_template' => 'totara_form/element_radios',
            'name__' . $this->get_name() => true,
            'name' => $this->get_name(),
            'id' => $id,
            'label' => (string)$this->label,
            'frozen' => $this->is_frozen(),
            'options' => array(),
            'amdmodule' => 'totara_form/form_element_radios'
        );

        $checked = $this->get_field_value();
        if ($checked !== null) {
            // We need string to do strict comparison later.
            $checked = strval($checked);
        }

        $i = 0;
        foreach ($this->options as $value => $text) {
            $value = (string)$value; // PHP converts type of numeric keys it seems.
            $text = clean_text($text);
            $oid = $id . '___rd_' . $i;
            $result['options'][] = array('value' => $value, 'oid' => $oid, 'text' => $text, 'checked' => ($checked === $value));
            $i++;
        }

        $attributes = $this->get_attributes();
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
     * @return string|null
     */
    public function get_field_value() {
        $model = $this->get_model();
        $name = $this->get_name();

        if ($model->is_form_submitted() and !$this->is_frozen()) {
            $data = $this->get_data();
            if (isset($data[$name])) {
                return $data[$name];
            } else {
                return null;
            }
        }

        return $this->get_initial_value();
    }

    /**
     * Is the element data ok?
     *
     * NOTE: to be used from element_checkboxes validator only.
     *
     * @param array $data from self::get_dta()
     * @return bool
     */
    public function is_valid_selection($data) {
        $name = $this->get_name();
        if (!array_key_exists($name, $data)) {
            return false;
        }
        $data = $data[$name];
        if ($data === null) {
            return true;
        }
        if (is_array($data)) {
            return false;
        }
        return $this->is_valid_option($data);
    }

    /**
     * Is this a valid option value?
     *
     * @param string $value
     * @return bool
     */
    protected function is_valid_option($value) {
        return array_key_exists($value, $this->options);
    }

    /**
     * Returns current selected checkboxes value.
     *
     * @param bool $debuggingifinvalid true means print debugging message if value invalid
     * @return string|null null means incorrect current value or not specified
     */
    protected function get_current_value($debuggingifinvalid = false) {
        $name = $this->get_name();
        $model = $this->get_model();

        $current = $model->get_current_data($name);
        if (!isset($current[$name])) {
            return null;
        }
        $current = $current[$name];

        if (is_array($current)) {
            if ($debuggingifinvalid) {
                debugging('Invalid current value detected in radios element ' . $this->get_name(), DEBUG_DEVELOPER);
            }
            return null;
        }

        $current = (string)$current;
        if (!$this->is_valid_option($current)) {
            if ($debuggingifinvalid) {
                debugging('Invalid current value detected in radios element ' . $this->get_name(), DEBUG_DEVELOPER);
            }
        }

        return $current;
    }

    /**
     * Returns current value or nothing.
     *
     * @return string|null
     */
    protected function get_initial_value() {
        return $this->get_current_value();
    }
}
