<?php
/*
 * This file is part of Totara Learn
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package block_totara_report_graph
 */

namespace block_totara_report_graph\hook;

/**
 * Block graph cache key.
 *
 * This hook is called after key generation when fetching cached data.
 *
 * This hook can be used to customise the caching strategy for graphs in block.
 *
 * @package core_course\hook
 */
class get_svg_data_cache_key extends \totara_core\hook\base {
    /**
     * The cache key. Empty means disable caching.
     *
     * @var string
     */
    public $key;

    /**
     * Report identification from graph block config - read only.
     *
     * Positive number is report id, negative number is saved report id.
     *
     * @var int
     */
    public $reportorsavedid;

    /**
     * User selection from block config - read only.
     *
     * Zero means current user.
     *
     * @var int
     */
    public $reportfor;

    /**
     * The relevant raw report record - read only.
     *
     * @var \stdClass
     */
    public $rawreport;

    /**
     * cache_key constructor.
     * @param string $key
     * @param int $reportorsavedid
     * @param int $reportfor
     */
    public function __construct($key, $reportorsavedid, $reportfor, \stdClass $rawreport) {
        $this->key = $key;
        $this->reportorsavedid = $reportorsavedid;
        $this->reportfor = $reportfor;
        $this->rawreport = $rawreport;
    }
}