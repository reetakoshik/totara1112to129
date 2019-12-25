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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package block_admin_related_pages
 */

namespace block_admin_related_pages\output;

/**
 * Item list output component.
 */
final class itemlist extends \core\output\template {

    /**
     * Creates a itemlist given an array of items.
     *
     * @param array $items
     * @return itemlist
     */
    public static function from_items(array $items): itemlist {
        $data = [
            'hasitems' => false,
            'items' => [],
        ];
        foreach ($items as $item) {
            $data['hasitems'] = true;
            $data['items'][] = [
                'url' => (string)$item->get_url(),
                'label' => (string)$item->get_label(),
            ];
        }
        return new self($data);
    }
}
