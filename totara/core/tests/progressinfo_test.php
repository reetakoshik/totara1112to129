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

namespace totara_core;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests progressinfo unpack method
 */
class totara_core_progressinfo_testcase extends \advanced_testcase {

    public function test_from_data() {
        $progressinfo = progressinfo::from_data(progressinfo::AGGREGATE_ANY, 1, 0.55, 'customdata');

        $this->assertInstanceOf('\totara_core\progressinfo', $progressinfo);
        $this->assertSame(progressinfo::AGGREGATE_ANY, $progressinfo->get_agg_method());
        $this->assertSame(1, $progressinfo->get_weight());
        $this->assertSame(0.55, $progressinfo->get_score());
        $this->assertSame('customdata', $progressinfo->get_customdata());
        $this->assertCount(0, $progressinfo->get_all_criteria());
    }

    public function test_from_data_multilevel() {
        $progressinfo = progressinfo::from_data(progressinfo::AGGREGATE_ANY, 1, 0.55, 'customdata');
        $progressinfo->add_criteria(3, progressinfo::AGGREGATE_ALL, 2, 0.66, ['key' => 'subkey', 'value' => 'subvalue']);
        $progressinfo->add_criteria(1, progressinfo::AGGREGATE_ANY, 5, 0.44, '', '');

        $this->assertInstanceOf('\totara_core\progressinfo', $progressinfo);
        $this->assertSame(progressinfo::AGGREGATE_ANY, $progressinfo->get_agg_method());
        $this->assertSame(1, $progressinfo->get_weight());
        $this->assertSame(0.55, $progressinfo->get_score());
        $this->assertSame('customdata', $progressinfo->get_customdata());
        $this->assertCount(2, $progressinfo->get_all_criteria());

        $subprogressinfo = $progressinfo->get_criteria(3);
        $this->assertInstanceOf('\totara_core\progressinfo', $subprogressinfo);
        $this->assertSame(progressinfo::AGGREGATE_ALL, $subprogressinfo->get_agg_method());
        $this->assertSame(2, $subprogressinfo->get_weight());
        $this->assertSame(0.66, $subprogressinfo->get_score());
        $this->assertSame(['key' => 'subkey', 'value' => 'subvalue'], $subprogressinfo->get_customdata());
        $this->assertCount(0, $subprogressinfo->get_all_criteria());

        $subprogressinfo = $progressinfo->get_criteria('3');
        $this->assertInstanceOf('\totara_core\progressinfo', $subprogressinfo);
        $this->assertSame(progressinfo::AGGREGATE_ALL, $subprogressinfo->get_agg_method());
        $this->assertSame(2, $subprogressinfo->get_weight());
        $this->assertSame(0.66, $subprogressinfo->get_score());
        $this->assertSame(['key' => 'subkey', 'value' => 'subvalue'], $subprogressinfo->get_customdata());
        $this->assertCount(0, $subprogressinfo->get_all_criteria());

        $subprogressinfo = $progressinfo->get_criteria(1);
        $this->assertInstanceOf('\totara_core\progressinfo', $subprogressinfo);
        $this->assertSame(progressinfo::AGGREGATE_ANY, $subprogressinfo->get_agg_method());
        $this->assertSame(5, $subprogressinfo->get_weight());
        $this->assertSame(0.44, $subprogressinfo->get_score());
        $this->assertNull($subprogressinfo->get_customdata());
        $this->assertCount(0, $subprogressinfo->get_all_criteria());
    }

    public function test_from_data_caching() {
        $progressinfo = progressinfo::from_data(progressinfo::AGGREGATE_ANY, 1, 0.55, 'customdata');

        $cachedata = $progressinfo->prepare_to_cache();
        $this->assertInternalType('array', $cachedata);
        $this->assertSame(progressinfo::AGGREGATE_ANY, $cachedata['agg_method']);
        $this->assertSame(1, $cachedata['weight']);
        $this->assertSame(0.55, $cachedata['score']);
        $this->assertSame('customdata', $cachedata['customdata']);
        $this->assertCount(0, $cachedata['criteria']);

        $progressinfo = progressinfo::wake_from_cache($cachedata);
        $this->assertSame(progressinfo::AGGREGATE_ANY, $progressinfo->get_agg_method());
        $this->assertSame(1, $progressinfo->get_weight());
        $this->assertSame(0.55, $progressinfo->get_score());
        $this->assertSame('customdata', $progressinfo->get_customdata());
        $this->assertCount(0, $progressinfo->get_all_criteria());
    }

    public function test_from_data_caching_multilevel() {
        $progressinfo = progressinfo::from_data(progressinfo::AGGREGATE_ANY, 1, 0.55, 'customdata');
        $progressinfo->add_criteria(3, progressinfo::AGGREGATE_ALL, 2, 0.66, ['key' => 'subkey', 'value' => 'subvalue']);
        $progressinfo->add_criteria(1, progressinfo::AGGREGATE_ANY, 5, 0.44, '', '');

        $cachedata = $progressinfo->prepare_to_cache();
        $this->assertInternalType('array', $cachedata);
        $this->assertSame(progressinfo::AGGREGATE_ANY, $cachedata['agg_method']);
        $this->assertSame(1, $cachedata['weight']);
        $this->assertSame(0.55, $cachedata['score']);
        $this->assertSame('customdata', $cachedata['customdata']);
        $this->assertCount(2, $cachedata['criteria']);

        $progressinfo = progressinfo::wake_from_cache($cachedata);
        $this->assertInstanceOf('\totara_core\progressinfo', $progressinfo);
        $this->assertSame(progressinfo::AGGREGATE_ANY, $progressinfo->get_agg_method());
        $this->assertSame(1, $progressinfo->get_weight());
        $this->assertSame(0.55, $progressinfo->get_score());
        $this->assertSame('customdata', $progressinfo->get_customdata());
        $this->assertCount(2, $progressinfo->get_all_criteria());

        $subprogressinfo = $progressinfo->get_criteria(3);
        $this->assertInstanceOf('\totara_core\progressinfo', $subprogressinfo);
        $this->assertSame(progressinfo::AGGREGATE_ALL, $subprogressinfo->get_agg_method());
        $this->assertSame(2, $subprogressinfo->get_weight());
        $this->assertSame(0.66, $subprogressinfo->get_score());
        $this->assertSame(['key' => 'subkey', 'value' => 'subvalue'], $subprogressinfo->get_customdata());
        $this->assertCount(0, $subprogressinfo->get_all_criteria());

        $subprogressinfo = $progressinfo->get_criteria('3');
        $this->assertInstanceOf('\totara_core\progressinfo', $subprogressinfo);
        $this->assertSame(progressinfo::AGGREGATE_ALL, $subprogressinfo->get_agg_method());
        $this->assertSame(2, $subprogressinfo->get_weight());
        $this->assertSame(0.66, $subprogressinfo->get_score());
        $this->assertSame(['key' => 'subkey', 'value' => 'subvalue'], $subprogressinfo->get_customdata());
        $this->assertCount(0, $subprogressinfo->get_all_criteria());

        $subprogressinfo = $progressinfo->get_criteria(1);
        $this->assertInstanceOf('\totara_core\progressinfo', $subprogressinfo);
        $this->assertSame(progressinfo::AGGREGATE_ANY, $subprogressinfo->get_agg_method());
        $this->assertSame(5, $subprogressinfo->get_weight());
        $this->assertSame(0.44, $subprogressinfo->get_score());
        $this->assertNull($subprogressinfo->get_customdata());
        $this->assertCount(0, $subprogressinfo->get_all_criteria());
    }

    /**
     * Test progressinfo exception is thrown on duplicate criteria key
     */
    public function test_duplicate_criteria_execption() {
        $this->resetAfterTest(true);

        $verify_info = progressinfo::from_data(progressinfo::AGGREGATE_ALL, 0, 0);
        $verify_info->add_criteria(4,
            progressinfo::AGGREGATE_ALL, 0, 0);

        $this->expectException('\coding_exception');
        $verify_info->add_criteria(4,
            progressinfo::AGGREGATE_ALL, 0, 0);
    }
}