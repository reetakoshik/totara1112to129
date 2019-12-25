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
    totara_form\form\element\datetime;

/**
 * Totara form validator for datetime element.
 *
 * @package   totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
class element_datetime extends element_validator {
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
     * @throws \coding_exception If the item is not an instance of \totara_form\form\element\datetime
     * @param item $item
     */
    public function added_to_item(item $item) {
        if (!($item instanceof datetime)) {
            throw new \coding_exception('Validator "element_datetime" is designed to validate "datetime" element only!');
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

        // First check the final data to make sure it is a number or null.
        if (isset($data[$name])) {
            $value = $data[$name];
            if (!is_number($value)) {
                $this->element->add_error(get_string('datetimevalidationerror', 'totara_form'));
                return;
            }
        }

        // Make sure the raw submitted form data was valid too,
        // otherwise we might end up with a data loss
        // because the code tends to produce some timestamp event from invalid value.
        $rawvalue = $this->element->get_model()->get_raw_post_data($name);

        $isodate = isset($rawvalue['isodate']) ? $rawvalue['isodate'] : null;

        if ($isodate === '' or $isodate === null) {
            // Empty value is ok, add required validator if necessary.
            return;
        }

        if (is_array($isodate)) {
            $this->element->add_error(get_string('error'));
            return;
        }

        // NOTE: sometimes the browser does not send seconds or microseconds, let's make them optional here.
        if (!preg_match('/^\d\d\d\d-\d\d-\d\d[T ]\d\d:\d\d(:\d\d(\.\d{1,3})?)?$/', $isodate)) {
            $this->element->add_error(get_string('datetimevalidationerror', 'totara_form'));
            return;
        }

        $tz = isset($rawvalue['tz']) ? $rawvalue['tz'] : null;

        if ($tz === '') {
            $this->element->add_error(get_string('error'));
            return;
        }
    }
}
