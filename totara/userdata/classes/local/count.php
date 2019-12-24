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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_userdata
 */

namespace totara_userdata\local;

use \totara_userdata\userdata\item;

defined('MOODLE_INTERNAL') || die();

/**
 * Helpers for user data count.
 *
 * NOTE: This is not a public API - do not use in plugins or 3rd party code!
 */
final class count {
    /** How long do we allow count to run? */
    public const MAX_EXECUTION_DURATION = 60 * 30;

    /**
     * Returns list of item classes available in the system
     * that support user data count.
     *
     * @return string[] list of class names
     */
    public static function get_countable_item_classes() {
        $classes = array();

        /** @var \totara_userdata\userdata\item $class this is not an instance, but it helps with autocomplete */
        foreach (util::get_item_classes() as $class) {
            if (!$class::is_countable()) {
                continue;
            }
            $classes[] = $class;
        }

        return $classes;
    }

    /**
     * Returns list of all item classes that allow counting grouped by main component.
     *
     * This is intended for UI item visual grouping.
     *
     * @return array nested lists of classes grouped by component
     */
    public static function get_countable_items_grouped_list() {
        $classes = array();

        /** @var item $class this is not an instance, but it helps with autocomplete */
        foreach (self::get_countable_item_classes() as $class) {
            $maincomponent = $class::get_main_component();
            if (!isset($classes[$maincomponent])) {
                $classes[$maincomponent] = array();
            }
            $classes[$maincomponent][$class] = $class::get_sortorder();
        }

        // Sort using sortorder defined in items.
        foreach ($classes as $maincomponent => $items) {
            asort($items, SORT_NUMERIC);
            $classes[$maincomponent] = array_keys($items);
        }

        return $classes;
    }
}
