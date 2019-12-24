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
 * @package contentmarketplace_goone
 */

namespace contentmarketplace_goone;

defined('MOODLE_INTERNAL') || die();

/**
 * Collection class.
 */
final class collection extends \totara_contentmarketplace\local\contentmarketplace\collection {

    /**
     * @param string $id The collection ID
     * @return array
     */
    public function get($id = 'default'): array {
        $api = new api();
        return $api->list_ids_for_all_learning_objects(['collection' => $id]);
    }

    /**
     * @param array $items
     * @param string $id The collection ID
     */
    public function add($items, $id = 'default'): void {
        $api = new api();
        $api->add_to_collection($items, $id);
    }

    /**
     * @param array $items
     * @param string $id The collection ID
     */
    public function remove($items, $id = 'default'): void {
        $api = new api();
        $api->remove_from_collection($items, $id);
    }

}
