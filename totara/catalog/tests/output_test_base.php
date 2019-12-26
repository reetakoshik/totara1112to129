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

defined('MOODLE_INTERNAL') || die();

/**
 * Class output_test_base
 *
 * Functionality commonly used by output tests.
 *
 * @package totara_catalog
 * @group totara_catalog
 */
abstract class output_test_base extends advanced_testcase {

    /**
     * Get catalog default params.
     *
     * Returns array with default parameters for calling the catalog::create() method.
     *
     * @param array $override  Optional overrides for adjusting one or more params.
     * @return array
     */
    protected function get_catalog_default_params(array $override = []): array {
        $defaults = [
            'itemstyle' => 'narrow',
            'limitfrom' => 0,
            'maxcount' => -1,
            'orderbykey' => '',
            'resultsonly' => 0,
            'showdebugging' => false,
            'filterparams' => [
                'catalog_learning_type_panel-certification' => null,
                'catalog_learning_type_panel-course' => null,
                'catalog_learning_type_panel-program' => null,
                'catalog_cat_browse' => null,
                'catalog_fts' => null,
            ],
            'request' => '',
        ];

        $params = array_replace($defaults, $override);
        return array_values($params);
    }

    /**
     * Get expected catalog template data.
     *
     * Returns array with expected data that the catalog class should generate.
     * Use parameters $override and $remove for adjustments of expected structure.
     *
     * @param array $override
     * @param array $remove
     * @return array
     */
    protected function get_expected_catalog_template_data(array $override = [], array $remove = []): array {
        $defaults = [
            'item_style_toggle_enabled' => true,
            'grid_template_name' => 'totara_core/grid',
            'grid_template_data' => [
                'single_column' => false,
                'tiles_exist' => false,
                'tiles' => []
            ],
            'pagination_template_name' => 'totara_catalog/pagination',
            'primary_region_template_data' => 'value ignored - deep inspection not in this test',
            'pagination_template_data' => [
                'limit_from' => 0,
                'max_count' => 0,
                'end_of_results' => true,
            ],
            'results_count' => 'No items',
            'manage_btns_enabled' => true,
            'manage_btns' => (object)[
                'has_buttons' => false,
                'buttons' => [],
            ],
            'primary_region_template_name' => 'totara_core/select_region_primary',
            'panel_region_enabled' => true,
            'panel_region_template_name' => 'totara_core/select_region_panel',
            'panel_region_template_data' => 'value ignored - deep inspection not in this test',
            'order_by_template_name' => 'totara_core/select_tree',
            'order_by_template_data' => 'value ignored - deep inspection not in this test'
        ];

        foreach ($remove as $key) {
            unset($defaults[$key]);
        }

        return array_replace($defaults, $override);
    }

    /**
     * Assert template data.
     *
     * Compare arrays of expected and actual template_data element by element.
     *
     * @param array $expected
     * @param array $actual
     */
    protected function assert_catalog_template_data(array $expected, array $actual) {
        // Some of the sub-template_data is tested separately, so here we only make sure it exists.
        $skip_deep_inspection = [
            'primary_region_template_data',
            'panel_region_template_data',
            'order_by_template_data',
        ];
        foreach ($expected as $k => $v) {
            $this->assertArrayHasKey($k, $actual);
            if (!in_array($k, $skip_deep_inspection)) {
                $this->assertEquals(
                    $v,
                    $actual[$k],
                    "Expected for {$k}: " . var_export($v, true) . " Actual: " . var_export($actual[$k], true)
                );
            }
        }
    }

    /**
     * Helper method to create course/program/certification.
     *
     * Returns id of created object.
     *
     * @param string $provider
     * @return int
     * @throws coding_exception
     */
    protected function create_object_for_provider(string $provider): int {
        $generator = $this->getDataGenerator();
        if ($provider == 'course') {
            $course = $generator->create_course();
            return $course->id;
        }

        /** @var totara_program_generator $program_generator */
        $program_generator = $generator->get_plugin_generator('totara_program');
        if ($provider == 'program') {
            $program = $program_generator->create_program();
            return $program->id;
        }

        return $program_generator->create_certification();
    }

    /**
     * Get default expected template data.
     *
     * Item template data that is expected to be returned for our example details data (see get_example_details_data()),
     * given the default catalog configuration.
     *
     * @param string $provider
     * @param int $object_id
     * @return array
     */
    protected function get_default_expected_item_template_data(string $provider, int $object_id): array {

        $details_link = '';
        switch ($provider) {
            case 'course':
                $details_link = (object)[
                    'description' => 'You are not enrolled in this course',
                    'button' => (object)[
                        'url' => 'https://www.example.com/moodle/course/view.php?id=' . $object_id,
                        'label' => 'Go to course',
                    ],
                ];
                break;
            case 'program':
                $details_link = (object)[
                    'description' => 'You are not enrolled in this program',
                    'button' => (object)[
                        'url' => 'https://www.example.com/moodle/totara/program/view.php?id=' . $object_id,
                        'label' => 'Go to program',
                    ],
                ];
                break;
            case 'certification':
                $details_link = (object)[
                    'description' => 'You are not enrolled in this certification',
                    'button' => (object)[
                        'url' => 'https://www.example.com/moodle/totara/program/view.php?id=' . $object_id,
                        'label' => 'Go to certification',
                    ],
                ];
        }

        return [
            'id' => 123,
            'title_enabled' => '1',
            'title' => 'Test details 1',
            'manage_link' => null,
            'has_manage_link' => false,
            'details_link' => $details_link,
            'has_details_link' => true,
            'rich_text_enabled' => true,
            'rich_text' => '',
            'description_enabled' => false,
            'text_placeholders_enabled' => true,
            'text_placeholders' =>
                [
                    0 =>
                        (object)[
                            'data_exists' => false,
                            'data' => '',
                        ],
                    1 =>
                        (object)[
                            'data_exists' => false,
                            'data' => '',
                        ],
                ],
            'icon_placeholders_enabled' => false,
            'request' => 'arbitrary request string',
        ];
    }
}
