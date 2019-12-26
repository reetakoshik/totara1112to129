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
 * @author     Riana Rossouw <riana.rossouw@totaralearning.com>
 * @copyright  2017 Totara Learning Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package    totara_core
 */

namespace totara_core\progressinfo;

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Tests recursive function progressinfo->aggregate_score_weight to ensure it
 * aggregates weight and progress correctly
 */
class progressinfo_aggregate_score_weight_testcase extends \advanced_testcase {

    public function test_no_criteria() {
        $pinfo = progressinfo::from_data(progressinfo::AGGREGATE_ALL, 1, 1);

        $pinfo->aggregate_score_weight();
        $this->assertEquals(1, $pinfo->get_weight());
        $this->assertEquals(1, $pinfo->get_score());

        // Should have no effect - testing for completion purposes ony
        $pinfo->set_agg_method(progressinfo::AGGREGATE_ANY);

        $pinfo->aggregate_score_weight();
        $this->assertEquals(1, $pinfo->get_weight());
        $this->assertEquals(1, $pinfo->get_score());
    }

    public function test_single_criteria() {
        $pinfo = progressinfo::from_data(progressinfo::AGGREGATE_ALL, 5, 3);
        $pinfo->add_criteria(0, progressinfo::AGGREGATE_ALL, 1, 0);

        // Top level weight and criteria doesn't play a role
        $pinfo->aggregate_score_weight();
        $this->assertEquals(1, $pinfo->get_weight());
        $this->assertEquals(0, $pinfo->get_score());

        $pinfo->set_agg_method(progressinfo::AGGREGATE_ANY);
        $pinfo->aggregate_score_weight();
        $this->assertEquals(1, $pinfo->get_weight());
        $this->assertEquals(0, $pinfo->get_score());
    }

    public function test_multi_criteria_single_level() {
        $pinfo = progressinfo::from_data(progressinfo::AGGREGATE_ALL, 0, 0);
        $pinfo->add_criteria(0, progressinfo::AGGREGATE_ALL, 4, 1);
        $pinfo->add_criteria(1, progressinfo::AGGREGATE_ALL, 2, 2);

        $pinfo->aggregate_score_weight();
        $this->assertEquals(6, $pinfo->get_weight());
        $this->assertEquals(3, $pinfo->get_score());

        $pinfo->set_agg_method(progressinfo::AGGREGATE_ANY);
        $pinfo->aggregate_score_weight();
        $this->assertEquals(2, $pinfo->get_weight());
        $this->assertEquals(2, $pinfo->get_score());

        $pinfo->set_agg_method(progressinfo::AGGREGATE_NONE);
        $pinfo->aggregate_score_weight();
        $this->assertEquals(0, $pinfo->get_weight());
        $this->assertEquals(0, $pinfo->get_score());
    }

    public function test_multi_criteria_single_level_any_highest_weight() {
        $pinfo = progressinfo::from_data(progressinfo::AGGREGATE_ALL, 0, 0);
        $pinfo->add_criteria(0, progressinfo::AGGREGATE_ALL, 4, 4);
        $pinfo->add_criteria(1, progressinfo::AGGREGATE_ALL, 2, 2);

        $pinfo->aggregate_score_weight();
        $this->assertEquals(6, $pinfo->get_weight());
        $this->assertEquals(6, $pinfo->get_score());

        // Highest weight is used if more than one results in the same progress
        $pinfo->set_agg_method(progressinfo::AGGREGATE_ANY);
        $pinfo->aggregate_score_weight();
        $this->assertEquals(4, $pinfo->get_weight());
        $this->assertEquals(4, $pinfo->get_score());
    }

    public function test_multi_criteria_and_levels() {
        $pinfo = progressinfo::from_data(progressinfo::AGGREGATE_ALL, 0, 0);
        $crit4 = $pinfo->add_criteria(4, progressinfo::AGGREGATE_ANY, 0, 0);
        $crit4->add_criteria('4-1', progressinfo::AGGREGATE_ALL, 1, 1);
        $crit4->add_criteria('4-2', progressinfo::AGGREGATE_ALL, 1, 0);
        $crit4->add_criteria('4-3', progressinfo::AGGREGATE_ALL, 1, 0);

        $crit7 = $pinfo->add_criteria(7, progressinfo::AGGREGATE_ALL, 0, 0);
        $crit7->add_criteria('7-3', progressinfo::AGGREGATE_ALL, 1, 0);
        $crit7->add_criteria('7-4', progressinfo::AGGREGATE_ALL, 1, 0);

        $crit8 = $pinfo->add_criteria(8, progressinfo::AGGREGATE_ALL, 0, 0);
        $crit8->add_criteria('8-1', progressinfo::AGGREGATE_ALL, 1, 0);
        $crit8->add_criteria('8-2', progressinfo::AGGREGATE_ALL, 1, 1);

        $pinfo->add_criteria(2, progressinfo::AGGREGATE_ALL, 1, 0);

        $pinfo->aggregate_score_weight();
        $this->assertEquals(6, $pinfo->get_weight());
        $this->assertEquals(2, $pinfo->get_score());

        $pinfo->set_agg_method(progressinfo::AGGREGATE_ANY);
        $pinfo->aggregate_score_weight();
        $this->assertEquals(1, $pinfo->get_weight());
        $this->assertEquals(1, $pinfo->get_score());
    }
}