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
use totara_form\form\validator\attribute_required,
    totara_form\form\validator\attribute_maxlength,
    totara_form\trait_item_paramtype;

/**
 * Textarea input element.
 *
 * @package   totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
class textarea extends text {
    use trait_item_paramtype;

    /**
     * Textarea input constructor.
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

        parent::__construct($name, $label, $paramtype);
        $this->attributes = array(
            'maxlength' => null,
            'size' => null,
            'cols' => 50, // Different browsers do different things, we'll default to 50.
            'rows' => null,
            'wrap' => null,
            'required' => false,
            'placeholder' => null,
        );

        // Add validators.
        $this->add_validator(new attribute_required());
        $this->add_validator(new attribute_maxlength());
    }

    /**
     * Get Mustache template data.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output) {
        $result = parent::export_for_template($output);
        $result['form_item_template'] = 'totara_form/element_textarea';
        $result['amdmodule'] = 'totara_form/form_element_textarea';
        return $result;
    }
}
