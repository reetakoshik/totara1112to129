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

$functions = [
    'block_totara_featured_links_external_remove_tile' => [
        'classname'   => '\\block_totara_featured_links\\external',
        'methodname'  => 'remove_tile',
        'classpath'   => 'blocks/totara_featured_links/classes/external.php',
        'description' => 'Removes a Tile',
        'type'        => 'write',
        'capabilities'=> 'moodle/my:manageblocks,',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'block_totara_featured_links_external_reorder_tiles' => [
        'classname'   => '\\block_totara_featured_links\\external',
        'methodname'  => 'reorder_tiles',
        'classpath'   => 'blocks/totara_featured_links/classes/external.php',
        'description' => 'reorders the tiles',
        'type'        => 'write',
        'capabilities'=> 'moodle/my:manageblocks',
        'ajax'        => true,
        'loginrequired' => true,
    ]
];
