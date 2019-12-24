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

namespace totara_form;

/**
 * Base class for Totara form element validators.
 *
 * NOTE: Make sure the current data is valid before freezing the element,
 *       otherwise users will not be able to submit the form.
 *
 * @package   totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
abstract class element_validator implements validator {
    /** @var string $message custom error message */
    protected $message;

    /** @var element $element */
    protected $element;

    /**
     * Validator constructor.
     *
     * @param string $message validation message, null means default
     */
    public function __construct($message = null) {
        $this->message = $message;
    }

    /**
     * Inform validator that it was added to an element.
     *
     * This is expected to be used for sanity checks.
     *
     * @throws \coding_exception If the item is not an element, validators can only be added to items.
     * @throws \coding_exception If the you try to add the validator to more than one item, one item only.
     * @param item $item
     */
    public function added_to_item(item $item) {
        if (!($item instanceof element)) {
            throw new \coding_exception('This validator is intended for elements only!');
        }
        if (isset($this->element)) {
            throw new \coding_exception('Validator can be added to one element only!');
        }
        $this->element = $item;
    }

    /**
     * Validate the element.
     *
     * @return void adds errors to element
     */
    abstract public function validate();

    /**
     * Add validator specific data to template data.
     *
     * @param array &$data item template data
     * @param \renderer_base $output
     * @return void $data argument is modified
     */
    public function set_validator_template_data(&$data, \renderer_base $output) {
        // Override if necessary.
    }
}
