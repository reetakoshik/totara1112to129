<?php
/**
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package block_totara_featured_links
 */

namespace block_totara_featured_links\form\element;

defined('MOODLE_INTERNAL') || die();

use core\output\flex_icon;
use core\output\flex_icon_helper;
use totara_form\form\element\static_html;
use totara_form\form\element\text;

class iconpicker extends static_html{

    public function __construct($name, $label) {
        parent::__construct($name, $label, '');
    }

    /**
     * Renders the form field.
     *
     * @param array $data
     * @return bool|string
     */
    public function render($data) {
        global $PAGE, $CFG;
        $renderer = $PAGE->get_renderer('core');

        $icons = flex_icon_helper::get_icons($CFG->theme);
        $data['valid'] = isset($icons[$this->get_field_value()]);
        if ($data['valid']) {
            $icon = new flex_icon($this->get_field_value());
            $data['value'] = [
                'template' => $icon->get_template(),
                'context' => $icon->export_for_template($renderer)
            ];
        } else {
            $data['value'] = false;
        }

        $icondata = array_map(function($icon) use ($data, $renderer) {
            $iconobj = new flex_icon($icon);
            return [
                'identifier' => $icon,
                'data' => [
                    'template' => $iconobj->get_template(),
                    'context' => $iconobj->export_for_template($renderer)
                ],
                'selected' => $icon == $data['value']];
        }, array_keys($icons));
        $icondata = array_values($icondata);

        $PAGE->requires->js_call_amd(
            'block_totara_featured_links/element_icon_picker',
            'init',
            [$data['id'], $data['name'], $icondata]
        );

        return $renderer->render_from_template(
            'block_totara_featured_links/element_icon_picker',
            $data
        );
    }


    /**
     * Changes the attributes from the default static html attributes
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template (\renderer_base $output) {
        $result = parent::export_for_template($output);
        $result['html'] = (string)$this->render($result);
        $result['value'] = $this->get_field_value();
        return $result;
    }

    /**
     * Get the value of the hidden input
     *
     * @return array
     */
    public function get_data () {
        return [$this->get_name() => $this->get_model()->get_raw_post_data($this->get_name())];
    }

    /**
     * Static html does not have any value.
     *
     * @return null
     */
    public function get_field_value() {
        $model = $this->get_model();
        $name = $this->get_name();

        if ($model->is_form_submitted() && !$this->is_frozen()) {
            $data = $this->get_data();
            return (string)$data[$name];
        }

        $current = $model->get_current_data($name);
        if (!isset($current[$name])) {
            return '';
        } else {
            return $current[$name];
        }
    }


}