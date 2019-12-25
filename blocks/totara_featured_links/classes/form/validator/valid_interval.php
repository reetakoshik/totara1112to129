<?php
/**
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package block_totara_featured_links
 */

namespace block_totara_featured_links\form\validator;

defined('MOODLE_INTERNAL') || die();

use totara_form\element_validator;

/**
 * Makes sure the interval for a gallery tile is valid before the form is submitted
 * Class valid_interval
 * @package block_totara_featured_links\form\validator
 */
class valid_interval extends element_validator {

    /**
     * This makes sure that the entered value is not negative and is a number
     *
     * @return void adds errors to element
     */
    public function validate() {
        if (!is_numeric($this->element->get_data()['interval']) || $this->element->get_data()['interval'] < 0) {
            $this->element->add_error(get_string('interval_error', 'block_totara_featured_links'));
        }
    }
}