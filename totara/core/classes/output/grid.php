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

use core\output\template;

defined('MOODLE_INTERNAL') || die();

class grid extends template {

    /**
     * @param template[] $tiles
     * @param bool $singlecolumn
     * @return static
     */
    public static function create(array $tiles, bool $singlecolumn = false) : grid {
        $data = new \stdClass();
        $data->single_column = $singlecolumn;
        $data->tiles_exist = !empty($tiles);
        $data->tiles = [];

        foreach ($tiles as $tile) {
            $tiletemplatedata = new \stdClass();
            $tiletemplatedata->template_name = $tile->get_template_name();
            $tiletemplatedata->template_data = $tile->get_template_data();
            $data->tiles[] = $tiletemplatedata;
        }

        return new static((array)$data);
    }
}