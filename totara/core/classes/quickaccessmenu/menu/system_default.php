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

namespace totara_core\quickaccessmenu\menu;

use totara_core\quickaccessmenu\factory;
use totara_core\quickaccessmenu\group;
use totara_core\quickaccessmenu\item;
use totara_core\quickaccessmenu\provider;

final class system_default extends base {

    /**
     * Returns the system default menu.
     *
     * This menu is not unique to the user.
     *
     * @param factory $factory
     * @return system_default
     */
    public static function get(factory $factory): system_default {
        global $CFG;
        $menu = new system_default($factory);

        // Check config setting first to see if we have anything there.
        if (!empty($CFG->defaultquickaccessmenu) && is_array($CFG->defaultquickaccessmenu)) {
            foreach ($CFG->defaultquickaccessmenu as $item) {
                if (empty($item['key'])) {
                    debugging('Empty key found when preparing quick access menu from config.php', DEBUG_DEVELOPER);
                    break;
                }
                $group = !empty($item['group']) ? $item['group'] : group::LEARN;
                $label = !empty($item['label']) ? $item['label'] : null;
                $weight = !empty($item['weight']) ? $item['weight'] : null;
                $menu->add_item(item::from_config($item['key'], group::get($group), $label, $weight));
            }

            // We've got our defaults, so ignoring providers and returning now.
            return $menu;
        }

        // Can we use a blank namespace here? Hopefully this is the base namespace for the component
        $providers = \core_component::get_namespace_classes('quickaccessmenu', provider::class);
        /** @var provider[] $providers */
        foreach ($providers as $provider) {
            $items = $provider::get_items();
            foreach ($items as $item) {
                $menu->add_item($item);
            }
        }

        return $menu;
    }

}
