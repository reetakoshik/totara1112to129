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

defined('MOODLE_INTERNAL') || die();

use block_totara_featured_links\tile\base;

/**
 * Class block_totara_featured_links
 * This is the main class for the block
 * Handel's block level things
 */
class block_totara_featured_links extends block_base {

    /**
     * Initializes the block
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_totara_featured_links');
    }

    /**
     * Gets the javascript that is required for the block to work properly
     */
    public function get_required_javascript() {
        parent::get_required_javascript();
        if ($this->page->user_is_editing() && has_any_capability(['moodle/my:manageblocks', 'moodle/block:edit'], $this->context)) {
            $this->page->requires->js_call_amd('block_totara_featured_links/dragndrop', 'init');
        }
        $this->page->requires->strings_for_js(['delete', 'cancel'], 'core');
        $this->page->requires->strings_for_js(['confirm'], 'block_totara_featured_links');
        $this->page->requires->js_call_amd('block_totara_featured_links/ajax', 'block_totara_featured_links_remove_tile');

    }

    /**
     * Generates and returns the content of the block
     * @return stdClass
     */
    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        $editing = $this->page->user_is_editing();

        if (!isset($this->content)) {
            $this->content = new stdClass();
        }

        $data = [
            'tile_data' => [],
            'editing' => $editing,
            'size' => $this->config->size,
            'title' => $this->config->title,
            'manual_id' => $this->config->manual_id,
            'instanceid' => $this->instance->id,
            'shape' => isset($this->config->shape) ? $this->config->shape : 'square'
        ];

        $core_renderer = $this->page->get_renderer('core');

        $tiles = $this->get_tiles();
        $tile_data = [];

        if ($tiles != false) {
            foreach ($tiles as $tile) {
                $tile = base::get_tile_instance($tile->id);
                // Show the tile if it is visible or in editing mode and the user has the capability to edit the tile.
                if ($tile->is_visible()
                    || ($editing && parent::user_can_edit() && $tile->can_edit_tile())) {
                    $tile_data[$tile->sortorder]['content'] = $tile->render_content_wrapper($core_renderer, $data);
                }
            }
            // Put the tiles in order to the array and indexed rather than hashed.
            $keys = array_keys($tile_data);
            array_multisort($keys, $tile_data);
        }

        // Add the add tile.
        if ($editing) {
            $tile_data[] = base::export_for_template_add_tile($this->instance->id);
        }
        if (count($tile_data) == 0) {
            return $this->content;
        }

        // This is to add empty tiles at the end of the block so that the tiles in the last row stay the.
        // Same size as the tiles in the rows above.
        for ($i = 0; $i < 10; $i++) {
            $tile_data[] = ['filler' => true];
        }

        // Puts the tile data into the data array so the values are indexed rather than hashed.
        $data['tile_data'] = array_values($tile_data);

        $this->content->text = $core_renderer->render_from_template('block_totara_featured_links/main', $data);
        return $this->content;
    }

    /**
     * Sets up the database row in the block_totara_featured_links table
     * @return bool
     */
    public function instance_create() {
        $this->config = new stdClass();
        $this->config->size = 'medium';
        $this->config->shape = 'square';
        $this->config->title = get_string('pluginname', 'block_totara_featured_links');
        $this->config->manual_id = '';

        $this->instance_config_commit();

        return parent::instance_create();
    }

    /**
     * deletes the rows in the database from block_totara_featured_links and block_totara_featured_links_tiles
     * @return bool
     */
    public function instance_delete() {
        global $DB;

        $select = "instanceid IN (SELECT id FROM {block_totara_featured_links_tiles} WHERE blockid = :blockid) AND
                   instancetype = :instancetype";
        $params = ['blockid' => $this->instance->id, 'instancetype' => COHORT_ASSN_ITEMTYPE_FEATURED_LINKS];
        $transaction = $DB->start_delegated_transaction();
        try {
            $DB->delete_records_select('cohort_visibility', $select, $params);
            $DB->delete_records('block_totara_featured_links_tiles', ['blockid' => $this->instance->id]);
            $transaction->allow_commit();
        } catch (\Exception $e) {
            $transaction->rollback($e);
        }

        return parent::instance_delete();
    }

    /**
     * gets the tiles for a specific block id and returns all their data
     * @return array
     */
    private function get_tiles() {
        global $DB;
        $results = $DB->get_records('block_totara_featured_links_tiles', ['blockid' => $this->instance->id], 'sortorder ASC');
        return $results;
    }

    /**
     * returns whether the multiple instances of a block are allowed on one page
     * @return bool
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * Copy any block-specific data when copying to a new block instance.
     * @param int $fromid the id number of the block instance to copy from
     * @return boolean
     */
    public function instance_copy($fromid) {
        // Copy the tiles from the old block to the new one.
        global $DB, $USER;
        $from_block_tiles = $DB->get_records('block_totara_featured_links_tiles', ['blockid' => $fromid]);
        foreach ($from_block_tiles as $tile) {
            $old_tile = base::get_tile_instance($tile);
            if (!$old_tile->is_visible()) {
                continue;
            }
            $new_tile = clone $old_tile;
            $new_tile->userid = $USER->id;
            $new_tile->blockid = $this->instance->id;
            $new_tile->visibility = base::VISIBILITY_SHOW;
            $new_tile->presetsraw = '';
            $new_tile->tilerules = '';
            $new_tile->audienceaggregation = base::AGGREGATION_ANY;
            $new_tile->presetsaggregation = base::AGGREGATION_ANY;
            $new_tile->overallaggregation = base::AGGREGATION_ANY;
            $new_tile->audienceshowing = 0;
            $new_tile->presetshowing = 0;
            $new_tile->tilerulesshowing = 0;
            unset($new_tile->id);
            $new_tile->id = $DB->insert_record('block_totara_featured_links_tiles', $new_tile, true);
            $old_tile->copy_files($new_tile);
        }
        base::squash_ordering($this->instance->id);
        return true;
    }

    /**
     * returns whether the block should have a header
     * @return bool
     */
    public function hide_header() {
        return empty($this->config->title);
    }

    /**
     * Makes sure the plugin name and content is set initially
     */
    public function specialization() {
        if (isset($this->config)) {
            if (empty($this->config->title)) {
                $this->title = get_string('pluginname', 'block_totara_featured_links');
            } else {
                $this->title = $this->config->title;
            }

            if (!empty($this->config->data)) {
                if (!isset($this->content)) {
                    $this->content = new stdClass();
                }
                $this->get_content();
            }
        }
    }

    /**
     * This returns if this block instance should be displayed with a border.
     * If this is false then the block should be displayed chromeless.
     *
     * @return bool
     */
    public function display_with_border_by_default () {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * The data for the tile is in the block_totara_featured_links table so that changes the content of the block.
     * @return bool
     */
    public function has_configdata_in_other_table(): bool {
        return true;
    }
}