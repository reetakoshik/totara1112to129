<?php
/*
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\progressinfo;

defined('MOODLE_INTERNAL') || die();

/**
 * Default aggregation implementation to be used with AGGREGATE_NONE
 */
final class progressinfo_aggregate_none implements progressinfo_aggregation {

    /**
     * No aggregation in needed. Return 0 weight and score
     *
     * @param progressinfo $progressinfo Progress information to aggregate
     * @return array $results Associative array containing the aggregated weight and score
     */
    public static function aggregate(progressinfo $progressinfo) {
        return array('score' => 0.0, 'weight' => 0);
    }
}
