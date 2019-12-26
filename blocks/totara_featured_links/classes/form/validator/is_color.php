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
 * Class is_color
 * Makes sure the value passed by the color input is a 3 or 6 long hexadecimal string starting with a hash
 * @package block_totara_featured_links
 */
class is_color extends element_validator {

    /**
     * this makes sure the color is a hash followed by 6 numbers
     *
     * @return void adds errors to element
     */
    public function validate() {
        if (preg_match('/^#([0-9a-fA-F]{3}){1,2}$/', $this->element->get_data()['background_color']) == 0) {
            $this->element->add_error(get_string('color_error', 'block_totara_featured_links'));
        }
    }
}