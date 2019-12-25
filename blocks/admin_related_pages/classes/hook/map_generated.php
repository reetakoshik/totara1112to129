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

namespace block_admin_related_pages\hook;

/**
 * Hook to allow plugins and customisations to modify the admin related pages map.
 *
 * Please note that this method is called when the map is generated, before it is cached for the user.
 * You can access the map through the map public property.
 * It is the real object, as such you can perform the manipulations you want directly on it.
 */
class map_generated extends \totara_core\hook\base {

    /**
     * The map that has been generated.
     * @var \block_admin_related_pages\map
     */
    public $map;

    /**
     * Constructs an instance of this hook.
     *
     * @param \block_admin_related_pages\map $map
     */
    public function __construct(\block_admin_related_pages\map $map) {
        $this->map = $map;
    }
}
