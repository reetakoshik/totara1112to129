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
    totara_form\form\element\url;

/**
 * Totara form url validator.
 *
 * Only http, https and ftp protocols are accepted.
 *
 * @package   totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
class element_url extends element_validator {
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
     * @throws \coding_exception If the item is not an instance of \totara_form\form\element\editor
     * @param item $item
     */
    public function added_to_item(item $item) {
        if (!($item instanceof url)) {
            throw new \coding_exception('Validator "element_url" is designed to validate "url" element only!');
        }
        parent::added_to_item($item);
    }

    /**
     * Validate the element.
     *
     * @return void adds errors to element
     */
    public function validate() {
        global $CFG;
        require_once($CFG->dirroot . '/lib/validateurlsyntax.php');

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
            // Empty value is ok, add required validator if necessary.
            return;
        }

        // Make sure this will pass as PARAM_URL.
        $value = clean_param($value, PARAM_URL);
        if ($value !== '' and validateUrlSyntax($value, 's+H?S?F?E-u-P-a+I?p?f?q?r?')) {
            // Url is in correct format - must be a subset of PARAM_URL,
            // schema is required, relative links are not supported here.
            return;
        }

        // We may want to mimic the actual native browser messages here in the future,
        // for now this should match the poly-fills used in html5 elements.
        $this->element->add_error(get_string('urlvalidationerror', 'totara_form'));
    }
}
