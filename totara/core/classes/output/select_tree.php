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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\output;

defined('MOODLE_INTERNAL') || die();

class select_tree extends select {

    /**
     * Gets the data, default and active option for the given tree node.
     *
     * The active and default may be found somewhere in the children, or elsewhere in the tree.
     *
     * @param \stdClass $option
     * @param string|null $activekey
     * @return array [string $data, bool $defaultfound, \stdClass $activeoption]
     */
    private static function get_option_data(\stdClass $option, string $activekey = null) : array {
        $defaultfound = false;
        $activeoption = null;

        $data = new \stdClass();
        $data->key = $option->key;
        $data->name = $option->name;
        $data->active = false;
        $data->default = false;
        $data->has_children = false;

        if (!empty($activekey) && $option->key == $activekey) {
            $data->active = true;
            $activeoption = $option;
        }

        if (!empty($option->default) && $option->default) {
            $data->default = true;
            $defaultfound = true;

            // Set the default to be active when no active key has been specified.
            if (empty($activekey)) {
                $data->active = true;
                $activeoption = $option;
            }
        }

        if (!empty($option->children)) {
            $data->has_children = true;
            $data->children = [];
            foreach ($option->children as $child) {
                list($data->children[], $childdefaultfound, $childactiveoption) = static::get_option_data($child, $activekey);
                $defaultfound = $defaultfound || $childdefaultfound;
                $activeoption = $activeoption ?? $childactiveoption;
            }
        }

        return [$data, $defaultfound, $activeoption];
    }

    /**
     * Create a tree select template.
     *
     * If no node in the tree is marked as default then an exception will be thrown.
     * If the specified active key does not exist in the tree then an exception will be thrown.
     * If no active key is specified then the default node will be marked active.
     *
     * @param string $key
     * @param string $title
     * @param bool $titlehidden true if the title should be hidden
     * @param array $rawoptions array of \stdClass objects containing key, name, children (optional array of $rawoptions),
     *                          default (optional, should be on one option only, except when calltoaction is specified)
     * @param string|null $activekey the key of the active node (defaults to the default node)
     * @param bool $flattree true to indicate that the tree should be styled as one level only
     * @param bool $parentsareselectable true if clicking a parent name selects the option, false if it behaves like a chevron
     * @param string $calltoaction if specified then displayed as the default (and default must not be specified in options)
     * @return select_tree
     */
    public static function create(
        string $key,
        string $title,
        bool $titlehidden,
        array $rawoptions,
        string $activekey = null,
        bool $flattree = false,
        bool $parentsareselectable = true,
        string $calltoaction = null
    ) : select_tree {
        $data = parent::get_base_template_data($key, $title, $titlehidden);

        $data->options = [];

        $defaultfound = false;
        $activeoption = null;

        foreach ($rawoptions as $option) {
            list($data->options[], $childdefaultfound, $childactiveoption) = static::get_option_data($option, $activekey);
            $defaultfound = $defaultfound || $childdefaultfound;
            $activeoption = $activeoption ?? $childactiveoption;
        }

        // We have to have either default or call to action.
        if (!empty($defaultfound) && !empty($calltoaction)) {
            throw new \coding_exception('Default and call to action cannot both be specified: ' . $key);
        }

        // Also, we can't have both, because it would be confusing.
        if (empty($defaultfound) && empty($calltoaction)) {
            throw new \coding_exception('No default option or call to action specified in select tree: ' . $key);
        }

        // We need either an active option or a call to action (maybe from default), so that we can be sure that there is
        // something to show. Because we can't have both default AND call to action, when no active option was specified/found,
        // we can be sure that we will have active default XOR a call to action.
        if (empty($activeoption) && empty($calltoaction)) {
            throw new \coding_exception('Invalid active option specified and no call to action in select tree: ' . $key);
        }

        if (!empty($calltoaction)) {
            $data->call_to_action = $calltoaction;
        }

        if (!empty($activeoption)) {
            $data->active_name = $activeoption->name;
        }

        $data->flat_tree = $flattree;

        $data->parents_are_selectable = $parentsareselectable;

        return new static((array)$data);
    }
}