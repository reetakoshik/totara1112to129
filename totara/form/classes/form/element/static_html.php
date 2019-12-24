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
 * Some static html.
 *
 * @package   totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
class static_html extends element {
    /** @var string $html */
    private $html;

    /** @var bool $allowxss */
    private $allowxss = false;

    /**
     * Static html constructor.
     *
     * @param string $name
     * @param string $label
     * @param string $html
     */
    public function __construct($name, $label, $html) {
        parent::__construct($name, $label);
        $this->html = $html;
    }

    /**
     * No data from static html.
     *
     * @return array
     */
    public function get_data() {
        return array();
    }

    /**
     * No files from static html.
     *
     * @return array
     */
    public function get_files() {
        return array();
    }

    /**
     * Static html does not have any value.
     *
     * @return null
     */
    public function get_field_value() {
        return null;
    }

    /**
     * Call with true if you need to use JavaScript
     * in this static html element.
     *
     * If true is used, then developer is responsible for XSS protection,
     * otherwise the supplied html is cleaned before output.
     *
     * @param bool $state
     */
    public function set_allow_xss($state) {
        $this->allowxss = (bool)$state;
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
            'form_item_template' => 'totara_form/element_static_html',
            'name__' . $this->get_name() => true,
            'name' => $this->get_name(),
            'id' => $this->get_id(),
            'label' => (string)$this->label,
            'frozen' => $this->is_frozen(),
            'amdmodule' => 'totara_form/form_element_static_html',
        );

        $attributes = array();
        if ($this->allowxss) {
            $attributes['html'] = (string)$this->html;
        } else {
            $attributes['html'] = clean_text($this->html);
        }
        $this->set_attribute_template_data($result, $attributes);

        // Add errors if found, tweak attributes by validators.
        $this->set_validator_template_data($result, $output);

        // Add help button data.
        $this->set_help_template_data($result, $output);

        return $result;
    }
}
