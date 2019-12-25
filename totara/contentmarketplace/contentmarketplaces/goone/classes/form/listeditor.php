<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Greg Newton <greg.newton@androgogic.com>
 * @package contentmarketplace_goone
 */

namespace contentmarketplace_goone\form;

use totara_form\element,
    totara_form\item,
    totara_form\form\clientaction\supports_onchange_clientactions;


class listeditor extends element implements supports_onchange_clientactions {

    private $items;

    /**
     * A list of removable items.
     *
     * @throws \coding_exception if the list of radio buttons is empty.
     * @param string $name
     * @param string $label
     * @param array  $items The base set of items that are to be chosen from. An array of id=>name pairs.
     */
    public function __construct($name, $label, array $items) {
        if (func_num_args() > 3) {
            debugging('Extra unused constructor parameters detected.', DEBUG_DEVELOPER);
        }

        parent::__construct($name, $label);
        $this->attributes = array(
            'required' => false, // This is a group of elements, 'required' means user must select at least one.
            'horizontal' => false,
        );

        if (empty($items)) {
            throw new \coding_exception('List of items cannot be empty');
        }

        // Normalise the values that are stored as keys.
        $this->items = array();
        foreach ($items as $k => $v) {
            $this->items[(string)$k] = $v;
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
            return array($name => $model->get_current_data($name));
        }

        $data = $model->get_raw_post_data($name);

        if (is_array($data)) {
            $castvalues = array();
            foreach ($data as $value) {
                $castvalues[] = (int) $value;
            }
            return array($name => $castvalues);
        }

        return array($name => array((int) $data));
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
            'form_item_template' => 'contentmarketplace_goone/listeditor',
            'name__' . $this->get_name() => true,
            'name' => $this->get_name(),
            'id' => $id,
            'label' => (string)$this->label,
            'frozen' => $this->is_frozen(),
            'items' => array(),
            'amdmodule' => 'contentmarketplace_goone/form_element_listeditor'
        );

        $attributes = $this->get_attributes();
        $this->set_attribute_template_data($result, $attributes);

        // Add errors if found, tweak attributes by validators.
        $this->set_validator_template_data($result, $output);

        // Add help button data.
        $this->set_help_template_data($result, $output);

        $existing = $this->get_field_value();
        foreach ($this->items as $value => $text) {

            // Don't include items that have been removed by the user.
            if ($existing && !in_array($value, $existing)) {
                continue;
            }

            $result['items'][] = array(
                'text' => $text,
                'oid' => $value
            );
        }

        return $result;
    }

    /**
     * Get the value of text input element.
     *
     * @return array|null
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

        return $this->get_current_value();
    }

    /**
     * Returns current selected checkboxes value.
     *
     * @param bool $debuggingifinvalid true means print debugging message if value invalid
     * @return array|null null means incorrect current value or not specified
     */
    protected function get_current_value($debuggingifinvalid = false) {
        $name = $this->get_name();
        $model = $this->get_model();

        $current = $model->get_current_data($name);
        if (!isset($current[$name])) {
            return null;
        }
        $current = $current[$name];
        return $current;
    }
}
