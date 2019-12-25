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

namespace block_totara_featured_links;

use block_totara_featured_links\tile\base;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');

/**
 * Class block_totara_featured_links\external
 * This has the functions that will be called by AJAX
 */
class external extends \external_api {

    /**
     * remove_tile function will remove a tile from a block from ajax making it so that the whole page doesn't have to be reloaded.
     * @return \external_function_parameters
     */
    public static function remove_tile_parameters() {
        return new \external_function_parameters(
            [
                'tileid' => new \external_value(PARAM_INT, 'The tile to be remove')
            ]
        );
    }

    /**
     * Removes a tile
     *
     * @throws \coding_exception
     * @param int $tileid
     * @return bool
     */
    public static function remove_tile($tileid) {
        self::validate_parameters(self::remove_tile_parameters(), ['tileid' => $tileid]);
        global $DB, $USER;
        if (!$DB->record_exists('block_totara_featured_links_tiles', ['id' => $tileid])) {
            return false;
        }
        $tile_instance = base::get_tile_instance($tileid);
        // Checks that the inputs are valid and the right capabilities exist.
        $context = \context_block::instance($tile_instance->blockid);
        \external_api::validate_context($context);
        // Checks the user has the correct permissions.
        if (!$tile_instance->can_edit_tile()) {
            throw new \moodle_exception(get_string('cannot_edit_tile', 'block_totara_featured_links'));
        }
        $parentcontext = $context->get_parent_context();
        if ($parentcontext->contextlevel == CONTEXT_COURSECAT) {
            // Check if category is visible and user can view this category.
            $category = $DB->get_record('course_categories', ['id' => $parentcontext->instanceid], '*', MUST_EXIST);
            if (!$category->visible) {
                require_capability('moodle/category:viewhiddencategories', $parentcontext);
            }
        } else if ($parentcontext->contextlevel == CONTEXT_USER && $parentcontext->instanceid != $USER->id) {
            throw new \coding_exception('You do not have permissions to remove the tile');
        }
        return $tile_instance->remove_tile();
    }

    /**
     * Return type of the remove_tile function
     * @return \external_value
     */
    public static function remove_tile_returns() {
        return new \external_value(PARAM_BOOL, "This will return whether then tile was successfully removed");
    }

    /**
     * Parameters for the reorder tiles method.
     * requires a json encoded string of the array containing the order of the tiles
     * @return \external_function_parameters
     */
    public static function reorder_tiles_parameters() {
        return new \external_function_parameters(
            [
                'tiles' => new \external_multiple_structure(
                    new \external_value(PARAM_ALPHANUMEXT, 'The tiles to be ordered')
                )
            ]
        );
    }

    /**
     * reorders the tiles to the ordering in the JSON array passes.
     * The JSON array must be of some strings that have the id of the tile row in the database last
     *
     * @throws \coding_exception
     * @param array $tiles
     * @return void
     */
    public static function reorder_tiles($tiles) {
        global $DB;
        self::validate_parameters(self::reorder_tiles_parameters(), ['tiles' => $tiles]);
        if (count($tiles) <= 1) {
            return;
        }
        $tiles_to_sort = [];
        // Make sure all the tiles are valid before saving.
        foreach ($tiles as $sortorder => $value) {
            $matches = [];
            if (empty(preg_match('/[0-9]+$/', $value, $matches))) {
                throw new \coding_exception('Could not find the tile id form the element id passed please end the element id with the tile id');
            }
            $id = $matches[0];
            $tile = base::get_tile_instance($id);

            $tiles_to_sort[] = $tile;
        }

        $context = \context_block::instance($tiles_to_sort[0]->blockid);
        \external_api::validate_context($context);
        if (!has_any_capability(['moodle/my:manageblocks', 'moodle/block:edit'], $context)) {
            return null; // The user does not have permission to drag and drop
        }

        $i = 1;
        foreach ($tiles_to_sort as $tile) {
            if ($i != $tile->sortorder) {
                $tile->sortorder = $i;
                $DB->update_record('block_totara_featured_links_tiles', $tile);
            }
            $i++;
        }
    }

    /**
     * The values that the reorder tiles return: nothing
     * @return void
     */
    public static function reorder_tiles_returns() {
        return null;
    }
}