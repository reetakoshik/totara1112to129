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

use totara_form\element_validator,
    totara_form\model;

/**
 * Totara form nonempty data value validator.
 *
 * The difference from required attribute is that '0' is considered to be validation error here.
 * This validator is not compatible with elements that upload files.
 *
 * @package   totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
class nonempty extends element_validator {

    /**
     * PHP non-empty validator constructor.
     *
     * @param string $message validation message, null means default message
     */
    public function __construct($message = null) {
        parent::__construct($message);
    }

    /**
     * Validate the element.
     *
     * @return void adds errors to element
     */
    public function validate() {
        if ($this->element->is_frozen()) {
            // There is no point in validating frozen elements because all they can do is to return current data that user cannot change.
            return;
        }

        if ($this->element->compare_value(model::OP_NOT_EMPTY)) {
            // We have some data, good!
            return;
        }

        if (isset($this->message)) {
            $this->element->add_error($this->message);
        } else {
            $this->element->add_error(get_string('nonemptyvalidationerror', 'totara_form'));
        }
    }
}
