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
 * @package totara_core
 */

$functions = [

    'totara_core_quickaccessmenu_get_user_menu' => [
        'classname' => '\totara_core\quickaccessmenu\external',
        'methodname' => 'get_user_menu',
        'classpath' => 'totara/core/classses/quickaccessmenu/external.php',
        'description' => 'Get the admin menu for the current user',
        'ajax' => true,
        'type' => 'read',
    ],

    'totara_core_quickaccessmenu_add_item' => [
        'classname' => '\totara_core\quickaccessmenu\external',
        'methodname' => 'add_item',
        'classpath' => 'totara/core/classses/quickaccessmenu/external.php',
        'description' => 'Add item to the admin menu',
        'ajax' => true,
        'type' => 'write',
    ],

    'totara_core_quickaccessmenu_change_item_group' => [
        'classname' => '\totara_core\quickaccessmenu\external',
        'methodname' => 'change_item_group',
        'classpath' => 'totara/core/classses/quickaccessmenu/external.php',
        'description' => 'Move item to another group in the admin menu',
        'ajax' => true,
        'type' => 'write',
    ],

    'totara_core_quickaccessmenu_get_possible_items_menu' => [
        'classname' => '\totara_core\quickaccessmenu\external',
        'methodname' => 'get_addable_items',
        'classpath' => 'totara/core/classses/quickaccessmenu/external.php',
        'description' => 'Get items that can be added to the admin menu',
        'ajax' => true,
        'type' => 'read',
    ],

    'totara_core_quickaccessmenu_move_item_before' => [
        'classname' => '\totara_core\quickaccessmenu\external',
        'methodname' => 'move_item_before',
        'classpath' => 'totara/core/classses/quickaccessmenu/external.php',
        'description' => 'Move item before another item within its group',
        'ajax' => true,
        'type' => 'write',
    ],

    'totara_core_quickaccessmenu_remove_item' => [
        'classname' => '\totara_core\quickaccessmenu\external',
        'methodname' => 'remove_item',
        'classpath' => 'totara/core/classses/quickaccessmenu/external.php',
        'description' => 'Remove item from the admin menu',
        'ajax' => true,
        'type' => 'write',
    ],

    'totara_core_quickaccessmenu_rename_item' => [
        'classname' => '\totara_core\quickaccessmenu\external',
        'methodname' => 'rename_item',
        'classpath' => 'totara/core/classses/quickaccessmenu/external.php',
        'description' => 'Rename item in the admin menu',
        'ajax' => true,
        'type' => 'write',
    ],

    'totara_core_quickaccessmenu_reorder_items_in_group' => [
        'classname' => '\totara_core\quickaccessmenu\external',
        'methodname' => 'reorder_items_in_group',
        'classpath' => 'totara/core/classses/quickaccessmenu/external.php',
        'description' => 'Reorder items in the admin menu using the order of the keys to determine the new order',
        'ajax' => true,
        'type' => 'write',
    ],

    'totara_core_quickaccessmenu_add_group' => [
        'classname' => '\totara_core\quickaccessmenu\external',
        'methodname' => 'add_group',
        'classpath' => 'totara/core/classses/quickaccessmenu/external.php',
        'description' => 'Add admin menu group',
        'ajax' => true,
        'type' => 'write',
    ],

    'totara_core_quickaccessmenu_remove_group' => [
        'classname' => '\totara_core\quickaccessmenu\external',
        'methodname' => 'remove_group',
        'classpath' => 'totara/core/classses/quickaccessmenu/external.php',
        'description' => 'Remove admin menu group',
        'ajax' => true,
        'type' => 'write',
    ],

    'totara_core_quickaccessmenu_reorder_groups' => [
        'classname' => '\totara_core\quickaccessmenu\external',
        'methodname' => 'reorder_groups',
        'classpath' => 'totara/core/classses/quickaccessmenu/external.php',
        'description' => 'Reorder groups in the admin menu using the order of the group keys to determine the new order',
        'ajax' => true,
        'type' => 'write',
    ],

    'totara_core_quickaccessmenu_move_group_before' => [
        'classname' => '\totara_core\quickaccessmenu\external',
        'methodname' => 'move_group_before',
        'classpath' => 'totara/core/classses/quickaccessmenu/external.php',
        'description' => 'Move admin menu group before another group',
        'ajax' => true,
        'type' => 'write',
    ],

    'totara_core_quickaccessmenu_rename_group' => [
        'classname' => '\totara_core\quickaccessmenu\external',
        'methodname' => 'rename_group',
        'classpath' => 'totara/core/classses/quickaccessmenu/external.php',
        'description' => 'Rename admin menu group',
        'ajax' => true,
        'type' => 'write',
    ],
];

$services = [
    // None by default.
];
