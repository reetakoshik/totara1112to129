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
 * @package totara_catalog
 */

namespace totara_catalog\output;

use core\output\template;

defined('MOODLE_INTERNAL') || die();

class pagination extends template {

    /**
     * @param int $limitfrom indicates where the next page load should start from (used in sql)
     * @param int $maxcount the current estimate of the maximum number of results that could be found
     * @param bool $endofresults true if there are no more results to load (so remove "Load more")
     * @return pagination
     */
    public static function create(int $limitfrom, int $maxcount, bool $endofresults) {
        $data = new \stdClass();
        $data->limit_from = $limitfrom;
        $data->max_count = $maxcount;
        $data->end_of_results = $endofresults;

        return new static((array)$data);
    }
}