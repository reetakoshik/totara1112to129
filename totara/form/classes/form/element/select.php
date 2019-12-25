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
    totara_form\form\clientaction\supports_onchange_clientactions,
    totara_form\form\validator\attribute_required,
    totara_form\form\validator\valid_selection,
    totara_form\item;

/**
 * Select input element.
 *
 * @package   totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
class select extends element implements supports_onchange_clientactions {
    /** @var string[] $options */
    private $options;

    /** @var array $optgroups optional grouping of options via optgroups */
    private $optgroups = array();

    /**
     * Select input constructor.
     *
     * @throws \coding_exception if the list of options is empty,
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
            'size' => null,
            'required' => false, // This is not in HTML5 spec, required means non-'' value must be selected.
        );

        if (empty($options)) {
            throw new \coding_exception('List of options cannot be empty');
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
     * Specify optional grouping of options.
     *
     * The format of array elements is ['First group' => array('value1', 'value2')), 'Second group' => array('value3', 'value4'))]
     *
     * Please note that one value can be in multiple groups,
     * the order of original options is maintained
     * and values not present in options are ignored.
     *
     * @param array $optgroups
     */
    public function set_optgroups(array $optgroups) {
        $this->optgroups = array();
        // Normalise the values to be always strings!
        foreach ($optgroups as $name => $values) {
            $this->optgroups[(string)$name] = array_map('strval', $values);
        }
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
            // Should not happen.
            return array($name => $this->get_initial_value());
        }

        // Selection values are validated on submission only.
        return array($name => $data);
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
            'form_item_template' => 'totara_form/element_select',
            'name__' . $this->get_name() => true,
            'name' => $this->get_name(),
            'id' => $this->get_id(),
            'label' => (string)$this->label,
            'frozen' => $this->is_frozen(),
            'options' => array(),
            'amdmodule' => 'totara_form/form_element_select',
        );

        $selected = $this->get_field_value();
        if ($selected !== null) {
            // We need string to do strict comparison later.
            $selected = strval($selected);
        }

        $processedgroups = array();
        $options = array();
        foreach ($this->options as $value => $text) {
            $value = (string)$value; // PHP converts type of numeric keys it seems.
            $foundingroup = false;
            foreach ($this->optgroups as $groupname => $groupvalues) {
                if (!in_array($value, $groupvalues, true)) {
                    continue;
                }
                $foundingroup = true;
                if (isset($processedgroups[$groupname])) {
                    // Already processed
                    continue;
                }
                $groupedoptions = array();
                foreach ($groupvalues as $groupvalue) {
                    if (isset($this->options[$groupvalue])) {
                        $groupedoptions[] = array('value' => $groupvalue, 'text' => clean_text($this->options[$groupvalue]), 'selected' => false);
                    }
                }
                $processedgroups[$groupname] = true;
                $options[] = array('group' => true, 'label' => $groupname, 'options' => $groupedoptions);
            }
            if ($foundingroup) {
                continue;
            }

            $text = clean_text($text); // No JS allowed in select options!
            $options[] = array('value' => $value, 'text' => $text, 'selected' => false);
        }

        // Select the first option that matches the $selected value.
        foreach ($options as $k => $o) {
            if (!empty($o['group'])) {
                foreach ($o['options'] as $k2 => $o2) {
                    if ($o2['value'] === $selected) {
                        $options[$k]['options'][$k2]['selected'] = true;
                        break 2;
                    }
                }
                continue;
            }
            if ($o['value'] === $selected) {
                $options[$k]['selected'] = true;
                break;
            }
        }

        $result['options'] = $options;

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
     * NOTE: to be used from element_checkboxes validator only.
     *
     * @param array $data from self::get_dta()
     * @return bool
     */
    public function is_valid_selection($data) {
        $name = $this->get_name();
        if (!isset($data[$name])) {
            return false;
        }
        if (is_array($data[$name])) {
            return false;
        }
        return $this->is_valid_option($data[$name]);
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
                debugging('Invalid current value detected in select element ' . $this->get_name(), DEBUG_DEVELOPER);
            }
            return null;
        }

        if (!$this->is_valid_option($current)) {
            if ($debuggingifinvalid) {
                debugging('Invalid current value detected in select element ' . $this->get_name(), DEBUG_DEVELOPER);
            }
        }

        return $current;
    }

    /**
     * Returns current value or nothing.
     *
     * @return string
     */
    protected function get_initial_value() {
        $current = $this->get_current_value();
        if ($current === null) {
            // Something must be always selected, use 1st option here.
            $options = $this->options;
            reset($options);
            return (string)key($options);
        }
        return $current;
    }
}
