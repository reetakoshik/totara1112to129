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

require_once($CFG->dirroot . '/blocks/totara_featured_links/backup/moodle2/restore_totara_featured_links_steplib.php');

class restore_totara_featured_links_block_task extends restore_block_task {

    public function get_blockname() {
        return 'totara_featured_links';
    }

    /**
     * Define one array() of fileareas that each block controls
     */
    public function get_fileareas() {
        return [];
    }

    /**
     * Define one array() of configdata attributes
     * that need to be decoded
     */
    public function get_configdata_encoded_attributes() {
        return [];
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        return [];
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        return [];
    }

    /**
     * Define (add) particular settings that each block can have
     */
    protected function define_my_settings() {

    }

    /**
     * Define (add) particular steps that each block can have
     */
    protected function define_my_steps() {
        $this->add_step(new restore_totara_featured_links_block_structure_step('tiles_structure', 'tiles.xml'));
    }
}