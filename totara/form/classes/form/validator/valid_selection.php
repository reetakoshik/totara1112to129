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
    totara_form\element_validator;

/**
 * Totara form validator for elements that allow users to select things,
 * such as select, checkboxes or radios.
 *
 * NOTE: The element must implement valid_selection($data) method.
 *
 * @package   totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
class valid_selection extends element_validator {
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
     * @throws \coding_exception if the item does not define the is_valid_selection method.
     * @param item $item
     */
    public function added_to_item(item $item) {
        if (!method_exists($item, 'is_valid_selection')) {
            throw new \coding_exception('Validator "valid_selection" required item::is_valid_selection($data) method!');
        }
        parent::added_to_item($item);
    }

    /**
     * Validate the element.
     *
     * @return void adds errors to element
     */
    public function validate() {
        $data = $this->element->get_data();

        // There is no point in validating frozen elements because all they can do is to return current data that user cannot change.
        if ($this->element->is_frozen()) {
            return;
        }

        if (!method_exists($this->element, 'is_valid_selection')) {
            // This silences phpstorm warning only, we do the check in added_to_item() above.
            return;
        }
        if (!$this->element->is_valid_selection($data)) {
            // No need to localise, this should not happen unless somebody hacks form or developer messed up the options!
            $this->element->add_error(get_string('error'));
        }
    }
}
