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
 */

namespace totara_core\quickaccessmenu\menu;

use totara_core\quickaccessmenu\factory;
use totara_core\quickaccessmenu\item;
use totara_core\quickaccessmenu\menu;

final class admin extends base {

    /**
     * Gets quick access menu items for the admin tree nodes that a user has access to.
     *
     * @param factory $factory
     * @return admin
     */
    public static function get(factory $factory): admin {
        global $CFG;

        require_once($CFG->dirroot . '/lib/adminlib.php');

        $menu = new admin($factory);
        $adminroot = \admin_get_root(false, false);
        foreach ($adminroot->get_children(false) as $child) {
            if ($child->check_access() && !$child->is_hidden()) {
                self::add_admin_part_to_menu($child, $menu);
            }
        }

        return $menu;
    }

    /**
     * Creates quick access menu items given a subset
     * of the admin tree
     *
     * @param \part_of_admin_tree $part
     * @param menu $menu
     */
    private static function add_admin_part_to_menu(\part_of_admin_tree $part, ?menu $menu) {
        if ($part instanceof \admin_externalpage || $part instanceof \admin_settingpage) {
            $menu->add_item(item::from_part_of_admin_tree($part));
        }

        if ($part instanceof \admin_category) {
            foreach ($part->get_children(false) as $child) {
                if ($child->check_access() && !$child->is_hidden()) {
                    // Generate the child branches as well now using this branch as the reference
                    self::add_admin_part_to_menu($child, $menu);
                }
            }
        }
    }
}
