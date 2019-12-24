<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package tool_sitepolicy
 */

namespace tool_sitepolicy\form\validator;

defined('MOODLE_INTERNAL') || die();

use totara_form\element_validator;
use totara_form\model;
use tool_sitepolicy\form\element\statement;

/**
 * Totara site policy statement 'required' attribute validator.
 *
 * NOTE: this validator should be added to statement element only
 *
 */
class statement_required extends element_validator {
    /**
     * HTML5 'required' attribute validator constructor.
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
        if (!($this->element instanceof statement)) {
            throw new \coding_exception("Attribute statement_required must be only applied to statement element");
        }

        if ($this->element->is_frozen()) {
            // There is no point in validating frozen elements because all they can do
            // is to return current data that user cannot change.
            return;
        }

        $required = $this->element->get_attribute('required');
        if (!$required) {
            return;
        }

        if ($this->element->compare_value(model::OP_FILLED)) {
            // We have some data, good!
            return;
        }
        // Required stuff is handled via poly-fills, no need to adds strings here.
        $this->element->add_error(get_string('allstatementsrequired', 'tool_sitepolicy'));
    }
}
