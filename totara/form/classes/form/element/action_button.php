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

use totara_form\element;

/**
 * Action button element.
 *
 * Clicking on these buttons triggers form submission, cancelling or reloading.
 *
 * Buttons do not use $currentdata from the form constructor.
 * Value is returned only for submit buttons as '1' or '0'.
 *
 * @package   totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
class action_button extends element {
    /** @var int $type */
    private $type;

    /** Button does regular form submission  */
    const TYPE_SUBMIT = 0;

    /** Button cancels form submission */
    const TYPE_CANCEL = 1;

    /** Button just reloads form - no actual form submission is done */
    const TYPE_RELOAD = 2;

    /**
     * Button constructor.
     *
     * @param string $name the element name
     * @param string $label text on the button
     * @param int $type type of the button: action_button::TYPE_SUBMIT, action_button::TYPE_CANCEL or action_button::TYPE_RELOAD
     */
    public function __construct($name, $label, $type) {
        $this->attributes = array();
        parent::__construct($name, $label);
        $this->type = (int)$type;
    }

    /**
     * Did this button cancel form submission?
     *
     * So called "cancel button" should return true, everything else false.
     *
     * @return bool
     */
    public function is_form_cancelled() {
        if ($this->type !== self::TYPE_CANCEL) {
            return false;
        }
        return $this->get_field_value();
    }

    /**
     * Did this button prevent form submission?
     *
     * This is usually triggered by so called "no submit" buttons.
     *
     * @return bool
     */
    public function is_form_reloaded() {
        if ($this->type !== self::TYPE_RELOAD) {
            return false;
        }
        return $this->get_field_value();
    }

    /**
     * Get submitted data without validation.
     *
     * NOTE: The value is returned for submit buttons only as '1' or '0'.
     *
     * @return array
     */
    public function get_data() {
        if ($this->type !== self::TYPE_SUBMIT) {
            // Reload and cancel buttons prevent form submission,
            // they cannot be in submitted data.
            return array();
        }

        $name = $this->get_name();
        $clicked = $this->get_field_value();

        if ($clicked) {
            return array($name => '1');
        } else {
            return array($name => '0');
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
            'form_item_template' => 'totara_form/element_action_button',
            'name__' . $this->get_name() => true,
            'name' => $this->get_name(),
            'id' => $this->get_id(),
            'frozen' => $this->is_frozen(),
        );

        $attributes = $this->get_attributes();
        $attributes['value'] = (string)$this->label;
        $attributes['formnovalidate'] = ($this->type !== self::TYPE_SUBMIT);
        $this->set_attribute_template_data($result, $attributes);

        // Add errors if found, tweak attributes by validators.
        $this->set_validator_template_data($result, $output);

        // Add help button data.
        $this->set_help_template_data($result, $output);

        return $result;
    }

    /**
     * Was the form submitted via this button?
     *
     * @return bool
     */
    public function get_field_value() {
        $model = $this->get_model();
        $name = $this->get_name();

        if ($this->is_frozen()) {
            // Obviously, frozen buttons cannot be clicked!
            return false;
        }

        $data = $model->get_raw_post_data($name);

        // Malformed array data is ignored, any other value is considered a valid click on the button.
        if ($data === null or is_array($data)) {
            return false;
        } else {
            return true;
        }
    }
}
