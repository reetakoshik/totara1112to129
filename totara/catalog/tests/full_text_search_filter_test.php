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
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package totara_catalog
 */

defined('MOODLE_INTERNAL') || die();

use totara_catalog\local\full_text_search_filter;
use totara_catalog\filter;

/**
 * @group totara_catalog
 */
class totara_catalog_full_text_search_filter_testcase extends advanced_testcase {

    /**
     * @var full_text_search_filter
     */
    private $full_text_search_filter = null;

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->full_text_search_filter = new full_text_search_filter();
    }

    public function tearDown() {
        $this->full_text_search_filter = null;
        parent::tearDown();
    }

    public function test_get_search_relevance_weight() {
        global $CFG;
        // check default weight
        $default_weight = $this->full_text_search_filter->get_search_relevance_weight();
        $this->assertSame(full_text_search_filter::FTS_HEIGH_WEIGHT, $default_weight['ftshigh']);
        $this->assertSame(full_text_search_filter::FTS_MEDIUM_WEIGHT, $default_weight['ftsmedium']);
        $this->assertSame(full_text_search_filter::FTS_LOW_WEIGHT, $default_weight['ftslow']);

        //check config weight
        $CFG->catalogrelevanceweight = ['high' => 3, 'medium' => 2, 'low' => 1];
        $config_weight = $this->full_text_search_filter->get_search_relevance_weight();
        $this->assertSame(3, $config_weight['ftshigh']);
        $this->assertSame(2, $config_weight['ftsmedium']);
        $this->assertSame(1, $config_weight['ftslow']);
    }

    public function test_create() {
        $filter = $this->full_text_search_filter->create();
        $this->assertSame('catalog_fts', $filter->key);
        $this->assertSame(filter::REGION_FTS, $filter->region);
    }
}
