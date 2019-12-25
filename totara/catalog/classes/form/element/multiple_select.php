<?php
/*
 * This file is part of Totara Learn
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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @package totara_catalog
 */

namespace totara_catalog\form\element;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/catalog/classes/form/element/optgroup_trait.php');

use optgroup_trait;
use \totara_form\form\validator\valid_selection;
use \totara_form\element;

class multiple_select extends element {
    use optgroup_trait;

    protected $attributes = [];

    public function __construct($name, $label) {
        $this->attributes = [
            'selected' => [],
            'icons' => [],
        ];
        parent::__construct($name, $label);

        $this->add_validator(new valid_selection());
    }

    /**
     * Get submitted data without validation.
     *
     * @return array
     */
    public function get_data() {
        $name = $this->get_name();
        $data = $this->get_model()->get_raw_post_data($name);
        return [$name => json_decode($data)];
    }

    /**
     * Get Mustache template data.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output) {
        $this->get_model()->require_finalised();
        $values = [];

        $context = [
            'form_item_template' => 'totara_catalog/element_multiple_select',
            'legend' => $this->label,
            'frozen' => $this->is_frozen(),
            'id' => $this->get_id(),
            'name' => $this->get_name(),
            'amdmodule' => 'totara_catalog/form_element_multiple_select',
        ];
        
        $context['items'] = [];
        foreach ($this->attributes['selected'] as $selected) {
            $context['items'][] = [
                'id' => $selected,
                'iconname' => $this->attributes['icons'][$selected]
            ];
            $values[] = $selected;
        }
        $context['value'] = json_encode($values);

        $context['potentialicons'] = $this->get_grouped_options($this->attributes['icons'], 'id', 'iconname');

        return $context;
    }

    /**
     * Is the element data ok?
     *
     * NOTE: to be used from valid selection validator only.
     *
     * @param array $data from self::get_data()
     * @return bool
     */
    public function is_valid_selection($data) {
        $name = $this->get_name();
        if (!isset($data[$name])) {
            return false;
        }
        if (!is_array($data[$name])) {
            return false;
        }
        foreach ($data[$name] as $option) {
            if (is_array($option)) {
                return false;
            }
            if (!$this->is_valid_option($option)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Is this a valid option value?
     *
     * @param string $value
     * @return bool
     */
    protected function is_valid_option($value) {
        return array_key_exists($value, $this->attributes['icons']);
    }

    /**
     * Get form element value.
     *
     * This is intended primarily for form rendering, the expected order of value lookup is:
     *  1/ if frozen use current value (always)
     *  2/ _POST data (if form submitted)
     *  3/ current data if not null (if form not submitted)
     *  4/ some reasonable 'nothing' value based on element type (if form not submitted)
     *
     * This can be also used to find out the element value in definition method.
     *
     * @return string|array|null
     */
    public function get_field_value() {
        $model = $this->get_model();
        $name = $this->get_name();

        $current = $model->get_current_data($name);
        if (!isset($current[$name])) {
            return '';
        } else if ($current[$name] === '' or is_array($current[$name])) {
            return '';
        } else {
            // TODO why is this not found?
            return clean_param($current[$name], $this->get_type());
        }
    }
}
