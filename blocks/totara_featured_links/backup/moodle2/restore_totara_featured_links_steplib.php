<?php
/**
 * This file is part of Totara LMS
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package block_totara_featured_links
 */

class restore_totara_featured_links_block_structure_step extends restore_block_instance_structure_step {

    /**
     * Function that will return the structure to be processed by this restore_step.
     * Must return one array of @restore_path_element elements
     */
    protected function define_structure() {
        $paths = [];

        $paths[] = new restore_path_element('tiles', '/block/tiles');
        $paths[] = new restore_path_element('tilesvisibility', '/block/tiles/tilesvisibility');

        return $paths;
    }

    public function process_tiles($data) {
        global $DB, $USER;

        $data = (object)$data;
        if (empty($data)) {
            return;
        }

        $oldid = $data->id;
        $data->blockid = $this->task->get_blockid();
        $data->userid  = $USER->id;
        $newid = $DB->insert_record('block_totara_featured_links_tiles', $data);
        // Restore block fileareas.
        $this->set_mapping('tiles', $oldid, $newid, true);
        $this->add_related_files('block_totara_featured_links', 'tile_background',  'tiles');
        $this->add_related_files('block_totara_featured_links', 'tile_backgrounds', 'tiles');

    }

    public function process_tilesvisibility($data) {

        $data = (object)$data;
        if (!isset($data->cohortid)) {
            return;
        }

        $instanceid = (int)$this->get_new_parentid('tiles');
        $restore = totara_cohort_add_association((int)$data->cohortid, $instanceid, COHORT_ASSN_ITEMTYPE_FEATURED_LINKS, COHORT_ASSN_VALUE_VISIBLE);
        if ($restore === false) {
            $this->log('Featured Links block visibility is failed to restore', backup::LOG_WARNING);
        }
    }
}

