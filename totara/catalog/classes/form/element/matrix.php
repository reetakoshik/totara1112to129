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

class matrix extends \totara_form\element {
    use optgroup_trait;

    protected $attributes = [];

    public function __construct($name, $label) {
        $this->attributes = [
            'selected' => [],
            'filters' => [],
        ];
        parent::__construct($name, $label);
    }

    /**
     * Get submitted data without validation.
     *
     * @return array
     */
    public function get_data() {
        $name = $this->get_name();
        $data = $this->get_model()->get_raw_post_data($name);
        return [$name => $data];
    }

    /**
     * Get Mustache template data.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output) {
        $this->get_model()->require_finalised();
        $additional_filters = $this->attributes['filters'];

        $context = [
            'form_item_template' => 'totara_catalog/element_matrix',
            'legend' => 'leg',
            'legend_hidden' => true,
            'frozen' => $this->is_frozen(),
            'id' => $this->get_id(),
            'name' => $this->get_name(),
            'amdmodule' => 'totara_catalog/form_element_matrix',
        ];
        
        $context['rows'] = [];
        foreach ($this->attributes['selected'] as $id => $title) {
            $context['rows'][] = [
                'id' => $id,
                'filtername' => $this->attributes['filters'][$id],
                'heading' => $title,
            ];
            unset($additional_filters[$id]);
        }

        $context['potentialfilters'] = $this->get_grouped_options($this->attributes['filters'], 'id', 'filtername');

        return $context;
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
            // TODO huh?
            return clean_param($current[$name], $this->get_type());
        }
    }
}
