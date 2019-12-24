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

namespace totara_form\form\validator;

use totara_form\element_validator;

/**
 * Totara form 'maxlength' attribute validator.
 *
 * NOTE: this validator is should be added to elements
 *       that support html5 maxlength attribute in the element constructor.
 *
 * @package   totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
class attribute_maxlength extends element_validator {
    /**
     * HTML5 'maxlength' attribute validator constructor.
     */
    public function __construct() {
        parent::__construct(null);
    }

    /**
     * Validate the element.
     *
     * @return void adds errors to element
     */
    public function validate() {
        if ($this->element->is_frozen()) {
            // There is no point in validating frozen elements because all they can do
            // is to return current data that user cannot change.
            return;
        }

        $maxlength = $this->element->get_attribute('maxlength');
        if ($maxlength === null) {
            return;
        }

        $name = $this->element->get_name();
        $data = $this->element->get_data();

        if (is_array($data[$name])) {
            // This is wrong!
            debugging('maxlength attribute cannot work for elements that return array data!', DEBUG_DEVELOPER);
            return;
        }

        $value = (string)$data[$name];
        if (\core_text::strlen($value) <= $maxlength) {
            return;
        }

        // Note: this should be handled by poly-fill!
        $this->element->add_error(get_string('error'));
    }
}
