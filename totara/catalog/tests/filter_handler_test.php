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

use totara_catalog\filter;
use totara_catalog\local\config;
use totara_catalog\local\filter_handler;
use totara_catalog\provider_handler;

/**
 * @group totara_catalog
 */
class totara_catalog_filter_handler_testcase extends advanced_testcase {

    /**
     * @var filter_handler
     */
    private $filter_handler = null;

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->filter_handler = filter_handler::instance();
    }

    public function tearDown() {
        $this->filter_handler = null;
        parent::tearDown();
    }

    public function test_instance() {
        $this->assertInstanceOf('totara_catalog\\local\\filter_handler', $this->filter_handler);
    }

    public function test_reset_cache() {
        config::instance()->update(['learning_types_in_catalog' => ['course']]);
        $this->assertArrayHasKey('course_format_multi', $this->filter_handler->get_all_filters());

        // update providers but still get it from the cache
        config::instance()->update(['learning_types_in_catalog' => ['program']]);
        $this->assertArrayHasKey('course_format_multi', $this->filter_handler->get_all_filters());

        // reset the cache and get the new providers
        provider_handler::instance()->reset_cache();
        $this->filter_handler->reset_cache();
        $this->assertArrayNotHasKey('course_format_multi', $this->filter_handler->get_all_filters());
    }

    public function test_get_all_filters() {
        // Disable course provider, to show that disabled provider fitlers are not included.
        config::instance()->update(['learning_types_in_catalog' => ['program']]);

        // Find progs and cert tag collection id.
        $tagcollectionid = \core_tag_area::get_collection('totara_program', 'prog');

        $allfilters = $this->filter_handler->get_all_filters();
        $this->assertArrayNotHasKey('course_acttyp_panel', $allfilters);
        $this->assertArrayNotHasKey('course_acttyp_browse', $allfilters);
        $this->assertArrayNotHasKey('course_format_multi', $allfilters);
        $this->assertArrayHasKey('tag_panel_' . $tagcollectionid, $allfilters);
        $this->assertArrayHasKey('tag_browse_' . $tagcollectionid, $allfilters);
        $this->assertArrayHasKey('catalog_cat_panel', $allfilters);
        $this->assertArrayHasKey('catalog_cat_browse', $allfilters);
        $this->assertArrayHasKey('catalog_fts', $allfilters);
        $this->assertArrayHasKey('catalog_learning_type_panel', $allfilters);
        $this->assertArrayHasKey('catalog_learning_type_browse', $allfilters);
    }

    public function test_get_active_filters() {
        // check default filters
        $this->assertCount(3, $this->filter_handler->get_active_filters());

        // update config and check the filters
        config::instance()->update(
            ['filters' => [
                'catalog_learning_type_panel' => 'Learning type',
                'course_acttyp_panel'         => 'Activity type',
                'course_format_multi'         => 'Format',
            ],
            ]
        );
        provider_handler::instance()->reset_cache();
        $this->filter_handler->reset_cache();
        $this->assertCount(5, $this->filter_handler->get_active_filters());
    }

    public function test_get_region_filters() {
        // fts region filters
        $fts_filters = $this->filter_handler->get_region_filters(filter::REGION_FTS);
        $this->assertCount(1, $fts_filters);
        $this->assertArrayHasKey('catalog_fts', $fts_filters);

        // Find course tag collection id.
        $tagcollectionid = \core_tag_area::get_collection('core', 'course');

        // browse region filters
        $filters = $this->filter_handler->get_region_filters(filter::REGION_BROWSE);
        $this->assertCount(6, $filters);

        $keys = [
            'course_acttyp_browse',
            'course_format_tree',
            'tag_browse_' . $tagcollectionid,
            'course_type_browse',
            'catalog_cat_browse',
            'catalog_learning_type_browse',
        ];
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $filters);
        }

        // panel region filters
        $filters = $this->filter_handler->get_region_filters(filter::REGION_PANEL);
        $this->assertCount(6, $filters);

        $keys = [
            'course_acttyp_panel',
            'course_format_multi',
            'tag_panel_' . $tagcollectionid,
            'course_type_panel',
            'catalog_cat_panel',
            'catalog_learning_type_panel',
        ];

        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $filters);
        }
    }

    public function test_get_enabled_panel_filters() {
        $panel_filters = $this->filter_handler->get_enabled_panel_filters();
        $this->assertSame('catalog_learning_type_panel', $panel_filters[0]->key);
    }

    public function test_get_current_browse_filter() {
        $browse_filter = $this->filter_handler->get_current_browse_filter();
        $this->assertSame('catalog_cat_browse', $browse_filter->key);
    }

    public function test_get_category_filters() {
        $catagory_filters = $this->filter_handler->get_category_filters();
        $this->assertSame('catalog_cat_panel', $catagory_filters[0]->key);
        $this->assertSame('catalog_cat_browse', $catagory_filters[1]->key);
    }

    public function test_get_full_text_search_filter() {
        $fts_filter = $this->filter_handler->get_full_text_search_filter();
        $this->assertSame('catalog_fts', $fts_filter->key);
        $this->assertSame(filter::REGION_FTS, $fts_filter->region);
    }

    public function test_get_learning_type_filters() {
        $learning_type_filter = $this->filter_handler->get_learning_type_filters();
        $this->assertSame('catalog_learning_type_panel', $learning_type_filter[0]->key);
        $this->assertSame('catalog_learning_type_browse', $learning_type_filter[1]->key);
    }
}
