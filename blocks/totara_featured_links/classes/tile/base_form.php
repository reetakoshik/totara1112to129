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

defined('MOODLE_INTERNAL') || die();

use \totara_form\form;
use \totara_form\group;
use \totara_form\item;

/**
 * Class base_form
 * each type of form should extend this class.
 * However plugin tile types should not extend this class
 * @package block_totara_featured_links
 */
abstract class base_form extends form {

    /**
     * Defines the wrapping for the form defined in specific definition
     * makes tile type and position appear on every form
     */
    protected function definition() {
        $this->model->add_action_buttons();
    }

    /**
     * contains the tile defined form
     * @param group $group
     * @return null
     */
    protected abstract function specific_definition(group $group);

    /**
     * gets the requirements for the form eg css and javascript
     * @return null
     */
    public function requirements() {
        return;
    }

    /**
     * Replaces an element with a new element
     *
     * @param item $parent
     * @param item $olditem item that will be removed
     * @param item $item item that will be added
     */
    protected function replace(item $parent, item $olditem, item $newitem) {
        $position = array_search($olditem, $parent->get_items());
        $parent->remove($olditem);
        $parent->add($newitem, $position);
    }

    /**
     * Overloaded version of {@link base_form::replace}
     * finds the item with the name given
     *
     * @param item $parent
     * @param string $olditemname
     * @param item $newitem
     */
    protected function replace_with_name(item $parent, string $olditemname, item $newitem) {
        $this->replace($parent, $parent->find($olditemname, 'get_name', '\totara_form\item'), $newitem);
    }
}
