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
 * @author Carl Anderson <carl.anderson@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\output;

use totara_core\quickaccessmenu\group;
use totara_core\quickaccessmenu\item;
use totara_core\quickaccessmenu\menu;

final class quickaccessmenu extends \core\output\template {

    /**
     * @param menu $menu
     * @return quickaccessmenu
     */
    public static function create_from_menu(menu $menu): quickaccessmenu {
        global $USER, $CFG;

        $data = [];
        $groups = [];

        //Set up data constants
        $canedit = has_capability('totara/core:editownquickaccessmenu', \context_system::instance());

        $data['can_edit'] = $canedit;
        $data['can_search'] = has_capability('moodle/site:config', \context_system::instance());

        if ($canedit) {
            $data['empty_message'] = get_string('quickaccessmenu:empty-message', 'totara_core', $CFG->wwwroot . '/user/quickaccessmenu.php');
        } else {
            $data['empty_message'] = get_string('quickaccessmenu:empty-message-noedit', 'totara_core');
        }

        $allgroups = group::get_groups($USER->id);

        foreach (self::organise_items_by_group($menu->get_items(), $allgroups) as $group => $items) {
            $groups[$group] = [
                'title'      => (string)$allgroups[$group]->get_label(),
                'has_items'  => !empty($items),
                'item_count' => count($items),
                'items'      => [],
            ];
            /** @var item $item */
            foreach ($items as $item) {
                $itemdata = [
                    'label' => $item->get_label(),
                    'url' => $item->get_url()->out()
                ];

                $groups[$group]['items'][] = $itemdata;
            }
        }

        $data['groups'] = array_values($groups);
        $data['group_count'] = count($groups);
        $data['has_groups'] = ($data['group_count'] > 0);

        return new quickaccessmenu($data);
    }

    /**
     * @param array $items
     * @param array $allgroups
     *
     * @return array
     */
    private static function organise_items_by_group(array $items, array $allgroups): array {
        $groups = [];
        foreach ($allgroups as $group) {
            $groups[$group->get_key()] = [];
        }
        foreach ($items as $item) {
            $group = $item->get_group();
            $groups[$group][] = $item;
        }
        foreach ($groups as $group => &$items) {
            if (empty($items) || $allgroups[$group]->get_visible() === false) {
                unset($groups[$group]);
                continue;
            }
            usort($items, [item::class, 'sort_items']);
        }
        return $groups;
    }

}
