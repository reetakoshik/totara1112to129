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

use block_totara_featured_links\tile\base;
use block_totara_featured_links\tile\certification_tile;
use block_totara_featured_links\tile\course_tile;
use block_totara_featured_links\tile\default_tile;
use block_totara_featured_links\tile\gallery_tile;
use block_totara_featured_links\tile\learning_item_tile;
use block_totara_featured_links\tile\program_tile;

defined('MOODLE_INTERNAL') || die();

/**
 * Totara featured links block generator class.
 */
class block_totara_featured_links_generator extends testing_block_generator {

    /**
     * Creates a new default tile.
     * @param int $blockinstanceid
     * @param stdClass $data
     * @return default_tile
     */
    public function create_default_tile($blockinstanceid, int $parentid = 0, stdClass $data = null) {
        /** @var default_tile $tile */
        $tile = default_tile::add($blockinstanceid, $parentid);
        if (isset($data)) {
            if (!isset($data->url)) {
                $data->url = '/';
            }
            $tile->save_content($data);
        }
        return $tile;
    }

    /**
     * Creates a new course tile
     * @param int $blockinstanceid
     * @return learning_item_tile
     */
    public function create_course_tile($blockinstanceid, int $parentid = 0, stdClass $data = null) {
        /** @var learning_item_tile $tile */
        $tile = course_tile::add($blockinstanceid, $parentid);
        if (isset($data)) {
            $tile->save_content($data);
        }
        return $tile;
    }

    /**
     * Creates a new program tile
     * @param int $blockinstanceid
     * @param stdClass $data
     * @return base learning_item_tile
     */
    public function create_program_tile($blockinstanceid, int $parentid = 0, stdClass $data = null) {
        /** @var learning_item_tile $tile */
        $tile = program_tile::add($blockinstanceid, $parentid);
        if (isset($data)) {
            $tile->save_content($data);
        }
        return $tile;
    }

    /**
     * Creates a new certification tile
     * @param int $blockinstanceid
     * @param stdClass $data
     * @return base learning_item_tile
     */
    public function create_certification_tile($blockinstanceid, int $parentid = 0, stdClass $data = null) {
        /** @var learning_item_tile $tile */
        $tile = certification_tile::add($blockinstanceid, $parentid);
        if (isset($data)) {
            $tile->save_content($data);
        }
        return $tile;
    }

    /**
     * Created a new multi image
     * @param int $blockinstanceid
     * @param stdClass $data
     * @return gallery_tile
     */
    public function create_gallery_tile($blockinstanceid, int $parentid = 0, stdClass $data = null) {
        /** @var gallery_tile $tile */
        $tile = gallery_tile::add($blockinstanceid, $parentid);
        if (isset($data)) {
            $tile->save_content($data);
        }
        return $tile;
    }

    /**
     * @param int $blockinstanceid
     * @param string $type
     * @return base
     */
    public function create_tile($blockinstanceid, $type, int $parentid = 0, stdClass $data = null) {
        /** @var base $tile */
        $tile = $type::add($blockinstanceid, $parentid);
        if (isset($data)) {
            $tile->save_context($data);
        }
        return $tile;
    }
}