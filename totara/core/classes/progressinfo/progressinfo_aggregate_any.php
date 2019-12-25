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
 * Default aggregation implementation to be used with AGGREGATE_ANY
 */
final class progressinfo_aggregate_any implements progressinfo_aggregation {

    /**
     * Recursive depth first function to aggregate this node's score and weight
     * when ANY criteria must be completed
     *     - aggregated weight = max (weight) of items with max(score / weight)
     *     - aggregated score = score of item with max weight with max(score / weight)
     * @param progressinfo $progressinfo Progress information to aggregate
     * @return array $results Associative array containing the aggregated weight and score
     */
    public static function aggregate(progressinfo $progressinfo) {

        $weight = 0;
        $score = 0.0;

        // weight = max (weight) of items with max(score / weight)
        // score = score of item with max weight with max(score / weight)
        $weightedscore = 0.0;

        $criteria = $progressinfo->get_all_criteria();
        foreach ($criteria as $critany) {
            if ($critany->get_weight() != 0) {
                $critscore = ($critany->get_score() / $critany->get_weight());
            } else {
                $critscore = $critany->get_score();
            }

            if ($critscore > $weightedscore) {
                $score = $critany->get_score();
                $weight = $critany->get_weight();
                $weightedscore = $critscore;
            } else if ($critscore == $weightedscore &&
                       $critany->get_weight() > $weight) {
                $score = $critany->get_score();
                $weight = $critany->get_weight();
            }
        }

        return array('score' => $score, 'weight' => $weight);
    }
}
