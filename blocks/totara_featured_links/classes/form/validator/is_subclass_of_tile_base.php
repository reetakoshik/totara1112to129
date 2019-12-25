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
 * Class is_subclass_of_tile_base
 *
 * Validator that makes sure the tile type is a valid tile type not a random class
 *
 * @package block_totara_featured_links
 */
class is_subclass_of_tile_base extends element_validator {

    /**
     * This will return an error if the type field does not contain a valid class or a class that does not extend base
     *
     * @throws \coding_exception if the tile type does not exist or is not of the correct type.
     * @return void adds errors to element
     */
    public function validate () {
        $class_str = $this->element->get_data()['type'];
        list($plugin_name, $class_name) = explode('-', $class_str, 2);
        $type = "\\$plugin_name\\tile\\$class_name";
        if (!class_exists($type) || !is_subclass_of($type, '\block_totara_featured_links\tile\base')) {
            throw new \coding_exception('Invalid tile type');
        }
    }
}