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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_catalog
 */

use totara_catalog\catalog_retrieval;
use totara_catalog\local\config;
use totara_catalog\local\filter_handler;
use totara_catalog\output\catalog;
use totara_catalog\output\item_narrow;
use totara_catalog\output\item_wide;
use totara_core\output\select_region_panel;
use totara_core\output\select_region_primary;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . "/totara/catalog/tests/output_test_base.php");

/**
 * Class output_catalog_test
 *
 * Tests for catalog output template class.
 *
 * @package totara_catalog
 * @group totara_catalog
 */
class totara_catalog_output_catalog_testcase extends output_test_base {

    /**
     * Test some parameter changes.
     *
     * Most parameters for the create() method are passed on to objects that are tested elsewhere.
     * Here we test some parameters that are processed within the create() method.
     */
    public function test_create_params() {
        $this->resetAfterTest();

        // Default
        $params = $this->get_catalog_default_params();
        $actual = catalog::create(...$params)->get_template_data();
        $expected = $this->get_expected_catalog_template_data();
        $this->assert_catalog_template_data($expected, $actual);

        // Request
        $params = $this->get_catalog_default_params(['request' => 'arbitrary string']);
        $actual = catalog::create(...$params)->get_template_data();
        $expected = $this->get_expected_catalog_template_data(['request' => 'arbitrary string']);
        $this->assert_catalog_template_data($expected, $actual);

        // Results only
        $params = $this->get_catalog_default_params(['resultsonly' => '1']);
        $actual = catalog::create(...$params)->get_template_data();
        $expected = $this->get_expected_catalog_template_data(
            [],
            [
                'manage_btns_enabled',
                'manage_btns',
                'primary_region_template_name',
                'primary_region_template_data',
                'panel_region_enabled',
                'panel_region_template_name',
                'panel_region_template_data',
                'order_by_template_name',
                'order_by_template_data',
            ]
        );
        $this->assert_catalog_template_data($expected, $actual);
    }

    /**
     * Test created template data depending on view_options configuration.
     */
    public function test_create_view_options() {
        $this->resetAfterTest();
        $config = config::instance();
        $config->update(['view_options' => 'list_only']);

        $params = $this->get_catalog_default_params();
        $actual = catalog::create(...$params)->get_template_data();
        $expected = $this->get_expected_catalog_template_data(
            [
                'item_style_toggle_enabled' => false,
                'grid_template_data' => [
                    'single_column' => true,
                    'tiles_exist' => false,
                    'tiles' => []
                ],
            ]
        );
        $this->assert_catalog_template_data($expected, $actual);

        $config->update(['view_options' => 'tile_only']);
        $params = $this->get_catalog_default_params();
        $actual = catalog::create(...$params)->get_template_data();
        $expected = $this->get_expected_catalog_template_data(
            [
                'item_style_toggle_enabled' => false,
                'grid_template_data' => [
                    'single_column' => false,
                    'tiles_exist' => false,
                    'tiles' => []
                ],
            ]
        );
        $this->assert_catalog_template_data($expected, $actual);
    }

    /**
     * Generate a course and test created template data.
     */
    public function test_create_results_count() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $generator->create_course();
        $params = $this->get_catalog_default_params();
        $actual = catalog::create(...$params)->get_template_data();
        $expected = $this->get_expected_catalog_template_data(
            [
                'results_count' => '1 items',
                'pagination_template_data' => [
                    'limit_from' => 1,
                    'max_count' => 1,
                    'end_of_results' => true,
                ],
            ],
            ['grid_template_data']
        );
        $this->assert_catalog_template_data($expected, $actual);

        // For grid_template_data only assert that it's there. Grid template itself is tested elsewhere.
        $this->assertTrue($actual['grid_template_data']['tiles_exist']);
        $this->assertSame('totara_catalog/item_narrow', $actual['grid_template_data']['tiles'][0]->template_name);
        $this->assertIsArray($actual['grid_template_data']['tiles'][0]->template_data);
    }

    /**
     * Test get_item_templates() method.
     */
    public function test_get_item_templates() {
        $this->resetAfterTest();
        $params = $this->get_catalog_default_params();
        $catalog = catalog::create(...$params);
        $rm = new ReflectionMethod(catalog::class, 'get_item_templates');
        $rm->setAccessible(true);
        $catalog_retrieval = new catalog_retrieval();

        // Empty result expected.
        $page = $catalog_retrieval->get_page_of_objects(20, 0, -1, '');
        $items = $rm->invoke($catalog, $page->objects, 'narrow');
        $this->assertCount(0, $items);

        // One narrow course item expected.
        $generator = $this->getDataGenerator();
        $generator->create_course();
        $page = $catalog_retrieval->get_page_of_objects(20, 0, -1, '');
        $items = $rm->invoke($catalog, $page->objects, 'narrow');
        $this->assertCount(1, $items);
        $this->assertInstanceOf(item_narrow::class, $items[0]);

        // Two wide course items expected.
        $generator->create_course();
        $page = $catalog_retrieval->get_page_of_objects(20, 0, -1, '');
        $items = $rm->invoke($catalog, $page->objects, 'wide');
        $this->assertCount(2, $items);
        foreach ($items as $item) {
            $this->assertInstanceOf(item_wide::class, $item);
        }
    }

    /**
     * Test get_results_count() method.
     */
    public function test_get_results_count() {
        $params = $this->get_catalog_default_params();
        $catalog = catalog::create(...$params);

        $rm = new ReflectionMethod(catalog::class, 'get_results_count');
        $rm->setAccessible(true);

        $this->assertSame(get_string('count_up_to', 'totara_catalog', 50), $rm->invoke($catalog, 43, false));
        $this->assertSame(get_string('count_up_to', 'totara_catalog', 50), $rm->invoke($catalog, 49, false));
        $this->assertSame(get_string('count_up_to', 'totara_catalog', 50), $rm->invoke($catalog, 50, false));

        // If someone has in the hundreds of millions of courses - you poor thing!
        $this->assertSame(get_string('count_up_to', 'totara_catalog', 800000000), $rm->invoke($catalog, 756354157, false));

        // Now test exact code
        $this->assertSame(get_string('count_exact', 'totara_catalog', 5), $rm->invoke($catalog, 5, true));
        $this->assertSame(get_string('count_exact', 'totara_catalog', 50), $rm->invoke($catalog, 50, true));
        $this->assertSame(get_string('count_exact', 'totara_catalog', 74), $rm->invoke($catalog, 74, true));
        $this->assertSame(get_string('count_exact', 'totara_catalog', 756354157), $rm->invoke($catalog, 756354157, true));
    }

    /**
     * Test get_primary_region_template() method.
     */
    public function test_get_primary_region_template() {
        $this->resetAfterTest();
        $params = $this->get_catalog_default_params();
        $catalog = catalog::create(...$params);
        $rm = new ReflectionMethod(catalog::class, 'get_primary_region_template');
        $rm->setAccessible(true);

        $primary_region_template = $rm->invoke($catalog);
        $template_data = $primary_region_template->get_template_data();
        $selector_keys = $this->get_template_data_selector_keys($template_data);

        $this->assertInstanceOf(select_region_primary::class, $primary_region_template);
        $this->assertCount(2, $selector_keys);
        $this->assertContains('catalog_cat_browse', $selector_keys);
        $this->assertContains('catalog_fts', $selector_keys);

        // Change browse filter.
        config::instance()->update(
            [
                'browse_by' => 'custom',
                'browse_by_custom' => 'course_format_tree',
            ]
        );
        \totara_catalog\cache_handler::reset_all_caches();

        $catalog = catalog::create(...$params);
        $primary_region_template = $rm->invoke($catalog);
        $template_data = $primary_region_template->get_template_data();
        $selector_keys = $this->get_template_data_selector_keys($template_data);

        $this->assertInstanceOf(select_region_primary::class, $primary_region_template);
        $this->assertCount(2, $selector_keys);
        $this->assertContains('course_format_browse', $selector_keys);
        $this->assertContains('catalog_fts', $selector_keys);
    }

    /**
     * Test get_panel_region_template() method.
     */
    public function test_get_panel_region_template() {
        $this->resetAfterTest();
        $params = $this->get_catalog_default_params();
        $catalog = catalog::create(...$params);
        $rm = new ReflectionMethod(catalog::class, 'get_panel_region_template');
        $rm->setAccessible(true);

        $panel_region_template = $rm->invoke($catalog);
        $template_data = $panel_region_template->get_template_data();
        $selector_keys = $this->get_template_data_selector_keys($template_data);

        $this->assertInstanceOf(select_region_panel::class, $panel_region_template);
        $this->assertCount(1, $selector_keys);
        $this->assertContains('catalog_learning_type_panel', $selector_keys);

        // Change panel filters.
        config::instance()->update(
            [
                'filters' => [
                    "course_acttyp_panel" => "Activity type",
                    "course_type_panel" => "Course Type",
                    "catalog_cat_panel" => "Category",
                ],
            ]
        );
        \totara_catalog\cache_handler::reset_all_caches();

        $catalog = catalog::create(...$params);
        $panel_region_template = $rm->invoke($catalog);
        $template_data = $panel_region_template->get_template_data();
        $this->assertInstanceOf(select_region_panel::class, $panel_region_template);
        $selector_keys = $this->get_template_data_selector_keys($template_data);

        $this->assertCount(3, $selector_keys);
        $this->assertContains('course_acttyp_panel', $selector_keys);
        $this->assertContains('course_type_panel', $selector_keys);
        $this->assertContains('catalog_cat_panel', $selector_keys);
    }

    /**
     * Test get_manage_buttons() method.
     */
    public function test_get_manage_buttons() {
        $this->resetAfterTest();
        $params = $this->get_catalog_default_params();
        $catalog = catalog::create(...$params);
        $rm = new ReflectionMethod(catalog::class, 'get_manage_buttons');
        $rm->setAccessible(true);

        $manage_buttons = $rm->invoke($catalog);
        $this->assertFalse($manage_buttons->has_buttons);
        $this->assertCount(0, $manage_buttons->buttons);

        $this->setAdminUser();
        $manage_buttons = $rm->invoke($catalog);
        $this->assertTrue($manage_buttons->has_buttons);
        $this->assertCount(1, $manage_buttons->buttons);
        $this->assertEquals('Configure catalogue', $manage_buttons->buttons[0]->label);

        $this->assertTrue($manage_buttons->has_create_dropdown);
        $this->assertCount(3, $manage_buttons->create_buttons);
        $labels = [];
        foreach ($manage_buttons->create_buttons as $button) {
            $labels[] = $button->label;
        }
        $this->assertContains('Course', $labels);
        $this->assertContains('Certification', $labels);
        $this->assertContains('Program', $labels);
    }

    /**
     * Test get_order_by_options() method.
     */
    public function test_get_order_by_options() {
        $this->resetAfterTest();
        $params = $this->get_catalog_default_params();
        $catalog = catalog::create(...$params);
        $rm = new ReflectionMethod(catalog::class, 'get_order_by_options');
        $rm->setAccessible(true);

        $order_by_options = $rm->invoke($catalog);
        $this->assertCount(2, $order_by_options);
        $this->assertEquals('text', $order_by_options['text']->key);
        $this->assertEquals('time', $order_by_options['time']->key);

        filter_handler::instance()->get_full_text_search_filter()->datafilter->set_current_data('test search');
        $order_by_options = $rm->invoke($catalog);
        $this->assertCount(3, $order_by_options);
        $this->assertEquals('text', $order_by_options['text']->key);
        $this->assertEquals('time', $order_by_options['time']->key);
        $this->assertEquals('score', $order_by_options['score']->key);

        config::instance()->update(['featured_learning_enabled' => '1']);
        $order_by_options = $rm->invoke($catalog);
        $this->assertCount(4, $order_by_options);
        $this->assertEquals('text', $order_by_options['text']->key);
        $this->assertEquals('time', $order_by_options['time']->key);
        $this->assertEquals('score', $order_by_options['score']->key);
        $this->assertEquals('featured', $order_by_options['featured']->key);
    }

    /**
     * Test get_debugging_data() method.
     */
    public function test_get_debugging_data() {
        $this->resetAfterTest();
        $params = $this->get_catalog_default_params();
        $catalog = catalog::create(...$params);
        $rm = new ReflectionMethod(catalog::class, 'get_debugging_data');
        $rm->setAccessible(true);
        $catalog_retrieval = new catalog_retrieval();

        $debugging_data = $rm->invoke($catalog, $catalog_retrieval, 'text');
        $this->assertObjectHasAttribute('sql', $debugging_data);
        $this->assertObjectHasAttribute('params', $debugging_data);
    }

    /**
     * Helper method to extract selector keys for assertions.
     *
     * @param array $template_data
     * @return array
     */
    private function get_template_data_selector_keys(array $template_data): array {
        $keys = [];
        foreach ($template_data['selectors'] as $selector) {
            $keys[] = $selector->template_data['key'];
        }
        return $keys;
    }
}
