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

namespace block_totara_featured_links\tile;

use totara_form\group;

defined('MOODLE_INTERNAL') || die();

/**
 * Class default_form_visibility
 * This is the visibility form for the default tile type
 * You can use this as an example for other tile types
 * @package block_totara_featured_links
 */
class default_form_visibility extends base_form_visibility {

    /**
     * The default tile does not define any custom visibility rules for the tile
     * @return bool
     */
    public function has_custom_rules() {
        return false;
    }

    /**
     * @param group $group
     * @return array
     */
    public function specific_definition(group $group) {
        return [];
    }

    /**
     * This will get an java script requirements for the form.
     * The default form does not have any.
     */
    public function requirements() {
    }
}