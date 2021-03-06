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
    totara_form\form\validator\attribute_maxlength,
    totara_form\form\validator\attribute_required,
    totara_form\trait_item_paramtype;

/**
 * Text input element.
 *
 * @package   totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
class text extends element {
    use trait_item_paramtype;

    /**
     * Text input constructor.
     *
     * NOTE: the $paramtype is not used if value is empty string
     *
     * @throws \coding_exception if initialised without a param type.
     * @param string $name
     * @param string $label
     * @param string $paramtype PARAM_XX constant
     */
    public function __construct($name, $label, $paramtype) {
        if (func_num_args() < 3) {
            throw new \coding_exception('$paramtype parameter must be specified');
        }
        if (func_num_args() > 3) {
            debugging('Extra unused constructor parameters detected.', DEBUG_DEVELOPER);
        }

        parent::__construct($name, $label);
        $this->attributes = array(
            'maxlength' => null,
            'placeholder' => null,
            'required' => false,
            'size' => null,
        );
        $this->set_type($paramtype);

        // Add validators.
        $this->add_validator(new attribute_required());
        $this->add_validator(new attribute_maxlength());
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
            $current = $model->get_current_data($name);
            if (!isset($current[$name])) {
                return array($name => null);
            } else if ($current[$name] === '' or is_array($current[$name])) {
                return array($name => '');
            } else {
                // Usually the current data should not be modified, but security is more important here.
                return array($name => clean_param($current[$name], $this->get_type()));
            }
        }

        $data = $this->get_model()->get_raw_post_data($name);
        if ($data === null or is_array($data)) {
            // No value in _POST or invalid value format, this should not happen.
            return array($name => $this->get_initial_value());
        } else if ($data === '') {
            // Do not clean '' value, use 'required' attribute if value needs to be filled.
            return array($name => '');
        } else {
            return array($name => clean_param($data, $this->get_type()));
        }
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
            'form_item_template' => 'totara_form/element_text',
            'name__' . $this->get_name() => true,
            'name' => $this->get_name(),
            'id' => $this->get_id(),
            'label' => (string)$this->label,
            'frozen' => $this->is_frozen(),
            'amdmodule' => 'totara_form/form_element_text',
        );

        $attributes = $this->get_attributes();
        $attributes['value'] = $this->get_field_value();
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
            return (string)$data[$name];
        }

        return (string)$this->get_initial_value();
    }

    /**
     * Returns current value or nothing.
     *
     * @return string
     */
    protected function get_initial_value() {
        $model = $this->get_model();
        $name = $this->get_name();

        $current = $model->get_current_data($name);
        if (!isset($current[$name])) {
            return '';
        } else if ($current[$name] === '' or is_array($current[$name])) {
            return '';
        } else {
            return clean_param($current[$name], $this->get_type());
        }
    }
}
