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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_completioneditor
 */

namespace totara_completioneditor\form\element;

use totara_form\form\element\action_button;

/**
 * Confirm action button element.
 *
 * Clicking on these buttons causes a confirmation dialog to display before it triggers form submission, cancelling or reloading.
 *
 * Buttons do not use $currentdata from the form constructor.
 * Value is returned only for submit buttons as '1' or '0'.
 *
 * @package   totara_completioneditor
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Nathan Lewis <nathan.lewis@totaralearning.com>
 */
class confirm_action_button extends action_button {
    /**
     * Button constructor.
     *
     * @param string $name the element name
     * @param string $label text on the button
     * @param int $type type of the button: action_button::TYPE_SUBMIT, action_button::TYPE_CANCEL or action_button::TYPE_RELOAD
     * @param array $options these can be accessed as attributes
     */
    public function __construct($name, $label, $type, $options = array()) {
        parent::__construct($name, $label, $type);

        $this->attributes = array(
            'dialogtitle' => get_string('confirm'),
            'dialogmessage' => get_string('areyousure'),
            'yesbuttonlabel' => get_string('yes'),
            'nobuttonlabel' => get_string('no'),
        );

        $this->set_attributes($options);
    }

    /**
     * Get Mustache template data.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output) {
        $result = parent::export_for_template($output);

        // Override some of the action button stuff.
        $result['form_item_template'] = 'totara_completioneditor/element_confirm_action_button';
        $result['amdmodule'] = 'totara_completioneditor/form_element_confirm_action_button';

        return $result;
    }

}
