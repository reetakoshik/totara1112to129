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

use totara_form\item,
    totara_form\element_validator,
    totara_form\form\element\number;

/**
 * Totara form integer number validator.
 *
 * @package   totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
class element_number extends element_validator {
    /**
     * Validator constructor.
     */
    public function __construct() {
        parent::__construct(null);
    }

    /**
     * Inform validator that it was added to an item.
     *
     * This is expected to be used for sanity checks and
     * attribute tweaks such as the required flag.
     *
     * @throws \coding_exception If the item is not an instance of \totara_form\form\element\number
     * @param item $item
     */
    public function added_to_item(item $item) {
        if (!($item instanceof number)) {
            throw new \coding_exception('Validator "element_number" is designed to validate "number" element only!');
        }
        parent::added_to_item($item);
    }

    /**
     * Validate the element.
     *
     * @return void adds errors to element
     */
    public function validate() {
        $name = $this->element->get_name();
        $data = $this->element->get_data();

        // There is no point in validating frozen elements because all they can do is to return current data that user cannot change.
        if ($this->element->is_frozen()) {
            return;
        }

        if (!array_key_exists($name, $data)) {
            $value = null;
        } else {
            $value = $data[$name];
        }

        if ($value === '' or $value === null) {
            // Empty value is ok, set required attribute if necessary.
            return;
        }

        do {
            if ((string)$value !== (string)(int)$value) {
                // Not an integer integer.
                break;
            }
            $value = (int)$value;

            $max = $this->element->get_attribute('max');
            if ($max !== null) {
                if ($value > $max) {
                    break;
                }
            }

            $min = $this->element->get_attribute('min');
            if ($min !== null) {
                if ($value < $min) {
                    break;
                }
            }

            $step = $this->element->get_attribute('step');
            if ($step > 1) {
                $mod = ($value - (int)$min) % $step;
                if ($mod !== 0) {
                    break;
                }
            }

            // All fine!
            return;
        } while (false);

        // This needs to be handled via poly-fill - no need to add string here.
        $this->element->add_error(get_string('error'));
    }
}
