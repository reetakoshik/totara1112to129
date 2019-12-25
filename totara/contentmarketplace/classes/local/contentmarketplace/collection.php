<?php
/*
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Sergey Vidusov <sergey.vidusov@androgogic.com>
 * @package totara_contentmarketplace
 */

namespace totara_contentmarketplace\local\contentmarketplace;

defined('MOODLE_INTERNAL') || die();

/**
 *
 * @package totara_contentmarketplace
 */
abstract class collection {

    /**
     * Fetch all available items in collection.
     *
     * @param string $id Collection ID
     * @return array
     */
    abstract public function get($id = 'default'): array;

    /**
     * Add items to collection
     *
     * @param array $items Array of item IDs to be added
     * @param string $id Collection ID
     * @return void
     */
    abstract public function add($items, $id = 'default'): void;

    /**
     * Add items to collection
     *
     * @param array $items Array of item IDs to be removed
     * @param string $id Collection ID
     * @return void
     */
    abstract public function remove($items, $id = 'default'): void;

}
