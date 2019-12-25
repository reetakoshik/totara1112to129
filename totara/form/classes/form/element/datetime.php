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
    totara_form\form\validator\element_datetime;

/**
 * Text input element.
 *
 * @package   totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
class datetime extends element {
    /** @var string $tz use timezone */
    protected $tz;

    /**
     * Text input constructor.
     *
     * NOTE: current value NULL means time not set, '0' is considered to be a valid 1970 date.
     *
     * @param string $name
     * @param string $label
     * @param string $tz
     */
    public function __construct($name, $label, $tz = null) {
        if (func_num_args() > 3) {
            debugging('Extra unused constructor parameters detected.', DEBUG_DEVELOPER);
        }

        parent::__construct($name, $label);
        $this->attributes = array(
            'required' => false,
            'placeholder' => null,
        );
        $this->tz = $tz;

        // Add validators.
        $this->add_validator(new attribute_required());
        $this->add_validator(new element_datetime());
    }

    /**
     * Magic date-time conversion.
     *
     * @param string $isodate
     * @param string $tz
     * @return int
     */
    protected function convert_to_timestamp($isodate, $tz) {
        if (is_array($isodate) or is_array($tz)) {
            return null;
        }

        if ($isodate === '') {
            return null;
        }

        try {
            $datetime = new \DateTime($isodate, \core_date::get_user_timezone_object($tz));
            return $datetime->getTimestamp();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Magic date-time conversion.
     *
     * @param int $time
     * @param string $tz
     * @return string
     */
    protected function convert_to_iso($time, $tz) {
        if (is_array($time) or is_array($tz)) {
            return '';
        }

        if ($time === null) {
            return '';
        }

        $datetime = new \DateTime('@' . (int)$time);
        $datetime->setTimezone(\core_date::get_user_timezone_object($tz));
        return $datetime->format('Y-m-d\TH:i:s');
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
        if ($data === null or !isset($data['isodate']) or !isset($data['tz'])) {
            // No value in _POST or invalid value format, most likely disabled element.
            $value = $this->get_initial_value();
            return array($name => $this->convert_to_timestamp($value['isodate'], $value['tz']));
        }

        return array($name => $this->convert_to_timestamp($data['isodate'], $data['tz']));
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
            'form_item_template' => 'totara_form/element_datetime',
            'name__' . $this->get_name() => true,
            'name' => $this->get_name(),
            'id' => $this->get_id(),
            'label' => (string)$this->label,
            'frozen' => $this->is_frozen(),
            'amdmodule' => 'totara_form/form_element_datetime',
        );

        $value = $this->get_field_value();

        $attributes = $this->get_attributes();
        $attributes['isodate'] = (string)$value['isodate'];
        $attributes['showtz'] = isset($this->tz);
        $attributes['tz'] = (string)$value['tz'];
        $this->set_attribute_template_data($result, $attributes);

        foreach (\core_date::get_list_of_timezones($attributes['tz'], true) as $value => $text) {
            if ($value == 99) {
                $usertz = \core_date::get_user_timezone(99);
                $usertz = \core_date::get_localised_timezone($usertz);
                $text = get_string('mytimezone', 'totara_form', $usertz);
            }
            $selected = ((string)$value === $attributes['tz']);
            $result['timezones'][] = array('value' => $value, 'text' => $text, 'selected' => ($selected));
        }

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
            $tz = isset($this->tz) ? $this->tz : '99';
            $current = $model->get_current_data($name);
            if (!array_key_exists($name, $current)) {
                return array(
                    'isodate' => '',
                    'tz' => $tz,
                );
            }
            return array(
                'isodate' => $this->convert_to_iso($current[$name], $tz),
                'tz' => $tz,
            );
        }

        if ($model->is_form_submitted()) {
            $data = $this->get_model()->get_raw_post_data($name);
            if ($data === null or !isset($data['isodate']) or !isset($data['tz']) or is_array($data['isodate']) or is_array($data['tz'])) {
                // No value in _POST or invalid value format, most likely disabled element.
                return $this->get_initial_value();
            }

            return array(
                'isodate' => $data['isodate'],
                'tz' => $data['tz'],
            );
        }

        return $this->get_initial_value();
    }

    /**
     * Returns current value or nothing.
     *
     * @return array with keys 'isodate' and 'tz'
     */
    protected function get_initial_value() {
        $model = $this->get_model();
        $name = $this->get_name();

        $tz = isset($this->tz) ? $this->tz : '99';

        $current = $model->get_current_data($name);
        if (array_key_exists($name, $current)) {
            return array(
                'isodate' => $this->convert_to_iso($current[$name], $tz),
                'tz' => $tz,
            );
        }

        return array(
            'isodate' => $this->convert_to_iso(null, $tz),
            'tz' => $tz,
        );
    }
}
