<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_form
 */

namespace totara_form\form\element;

use totara_form\element,
    totara_form\form\validator\attribute_required,
    totara_form\form\validator\element_utc10date;

/**
 * UTC 10AM date input element.
 *
 * @package   totara_form
 * @copyright 2017 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralearning.com>
 */
class utc10date extends element {

    /**
     * Text input constructor.
     *
     * NOTE: current value NULL means date not set, '0' is considered to be a valid 1970 date.
     *
     * @param string $name
     * @param string $label
     */
    public function __construct($name, $label) {
        if (func_num_args() > 3) {
            debugging('Extra unused constructor parameters detected.', DEBUG_DEVELOPER);
        }

        parent::__construct($name, $label);
        $this->attributes = array(
            'required' => false,
            'placeholder' => null,
        );

        // Add validators.
        $this->add_validator(new attribute_required());
        $this->add_validator(new element_utc10date());
    }

    /**
     * Magic UTC 10AM date conversion.
     *
     * @param string $isodate "Y-m-d" string
     * @return int
     */
    protected function convert_to_timestamp($isodate) {
        if (is_array($isodate)) {
            return null;
        }

        if ($isodate === '') {
            return null;
        }

        try {
            $utc10date = new \DateTime($isodate. 'T10:00:00+00:00');
            return $utc10date->getTimestamp();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Magic UTC 10AM date conversion.
     *
     * @param int $timestamp
     * @return string
     */
    protected function convert_to_iso($timestamp) {
        if (is_array($timestamp)) {
            return '';
        }

        if ($timestamp === null) {
            return '';
        }

        $utc10date = new \DateTime('@' . (int)$timestamp); // Value @ forces UTC.
        return $utc10date->format('Y-m-d');
    }

    /**
     * Get submitted data without validation.
     *
     * @return array
     */
    public function get_data() {
        $name = $this->get_name();
        $model = $this->get_model();

        if ($this->is_frozen()) {
            $current = $model->get_current_data($name);
            if (isset($current[$name])) {
                return $current;
            }
            return array($name => null);
        }

        $data = $this->get_model()->get_raw_post_data($name);
        if ($data === null or !isset($data['isodate'])) {
            // No value in _POST or invalid value format, most likely disabled element.
            $value = $this->get_initial_value();
            return array($name => $this->convert_to_timestamp($value['isodate']));
        }

        return array($name => $this->convert_to_timestamp($data['isodate']));
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
            'form_item_template' => 'totara_form/element_utc10date',
            'name__' . $this->get_name() => true,
            'name' => $this->get_name(),
            'id' => $this->get_id(),
            'label' => (string)$this->label,
            'frozen' => $this->is_frozen(),
            'amdmodule' => 'totara_form/form_element_utc10date',
        );

        $value = $this->get_field_value();

        $attributes = $this->get_attributes();
        $attributes['isodate'] = (string)$value['isodate'];
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

        if ($this->is_frozen()) {
            // Frozen means always return current data or nothing if not present.
            $current = $model->get_current_data($name);
            if (!array_key_exists($name, $current)) {
                return array(
                    'isodate' => '',
                );
            }
            return array(
                'isodate' => $this->convert_to_iso($current[$name]),
            );
        }

        if ($model->is_form_submitted()) {
            $data = $this->get_model()->get_raw_post_data($name);
            if ($data === null or !isset($data['isodate']) or is_array($data['isodate'])) {
                // No value in _POST or invalid value format, most likely disabled element.
                return $this->get_initial_value();
            }

            return array(
                'isodate' => $data['isodate'],
            );
        }

        return $this->get_initial_value();
    }

    /**
     * Returns current value or nothing.
     *
     * @return array with keys 'isodate'
     */
    protected function get_initial_value() {
        $model = $this->get_model();
        $name = $this->get_name();

        $current = $model->get_current_data($name);
        if (array_key_exists($name, $current)) {
            return array(
                'isodate' => $this->convert_to_iso($current[$name]),
            );
        }

        return array(
            'isodate' => $this->convert_to_iso(null),
        );
    }
}
