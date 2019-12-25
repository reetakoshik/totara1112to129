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

/**
 * Class learning_item_visibility
 * The learning items by default have no extra visibility rules.
 * @package block_totara_featured_links\tile
 */
abstract class learning_item_form_visibility extends base_form_visibility {
    /**
     * Learning items do not have any custom rules by default.
     *
     * {@inheritdoc}
     * @return false
     */
    public function has_custom_rules() {
        return false;
    }

    /**
     * Since there are no custom rules there are no form elements for them.
     *
     * {@inheritdoc}
     * @param group $group
     * @return array
     */
    public function specific_definition(group $group) {
        return [];
    }
}