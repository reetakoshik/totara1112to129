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

use totara_catalog\dataformatter\formatter;
use totara_catalog\local\config;
use totara_catalog\local\required_dataholder;
use totara_catalog\output\item;
use totara_catalog\output\item_wide;
use totara_catalog\provider_handler;

defined('MOODLE_INTERNAL') || die();

/**
 * Class output_item_test
 *
 * Tests for catalog item template class.
 *
 * @package totara_catalog
 * @group totara_catalog
 */
class totara_catalog_output_item_testcase extends advanced_testcase {

    /**
     * Example item data.
     *
     * Test data that will be passed to item output class. It's designed so that it can be reused for all
     * provider types (course,program,certification).
     *
     * @param string $provider
     * @return array
     */
    private function get_example_item_data(string $provider): array {
        return [
            'id' => 123,
            'objectid' => 234,
            'objecttype' => $provider,
            'data' => [
                formatter::TYPE_PLACEHOLDER_TITLE => [
                    'fullname' => 'Test item 1',
                    'shortname' => 'test-short-name'
                ],
                formatter::TYPE_PLACEHOLDER_TEXT => [
                    'catalog_learning_type' => 'Test Learning Type',
                    'course_category' => 'My Course Category',
                    'u_i_r_editingteacher' => 'Test Teacher',
                    'tags' => 'Test Tags',
                ],
                formatter::TYPE_PLACEHOLDER_IMAGE => [
                    'image' => (object)['url' => 'http://example.com/course/defaultimage.svg', 'alt' => 'Test image 1']
                ],
                formatter::TYPE_PLACEHOLDER_ICON => [
                    'type_icon' => (object)['icon' => '<span class="test">Type Icon HTML</span>']
                ],
                formatter::TYPE_PLACEHOLDER_ICONS => [
                    'activity_type_icons' => [(object)['icon' => '<span class="test">Activity Types Icon HTML</span>']],
                    'icon' => [(object)['icon' => '<span class="test">Course Icon HTML</span>']]
                ],
                formatter::TYPE_PLACEHOLDER_PROGRESS => ['progressbar' => 'Test progressbar']
            ],
        ];
    }

    /**
     * Get default expected template data.
     *
     * Item template data that is expected to be returned for our example item data (see get_example_item_data()),
     * given the default catalog configuration.
     *
     * @return array
     */
    private function get_default_expected_template_data(): array {
        return [
            'itemid' => 123,
            'featured' => false,
            'title' => 'Test item 1',
            'image_enabled' => true,
            'image' =>
                (object)[
                    'url' => 'http://example.com/course/defaultimage.svg',
                    'alt' => 'Test image 1',
                ],
            'hero_data_text_enabled' => false,
            'hero_data_icon_enabled' => false,
            'hero_data_type' => 'none',
            'description_enabled' => false,
            'progress_bar_enabled' => false,
            'text_placeholders_enabled' => true,
            'text_placeholders' =>
                [
                    0 =>
                        (object)[
                            'data_exists' => true,
                            'data' => 'Test Learning Type',
                        ],
                    1 =>
                        (object)[
                            'data_exists' => true,
                            'data' => 'My Course Category',
                        ],
                ],
            'icon_placeholders_enabled' => false,
        ];
    }

    /**
     * Get test data for provider.
     *
     * Test data is designed to be reusable for all provider types.
     *
     * @param string $provider
     * @return array
     */
    private function get_create_test_data(string $provider): array {
        $test_data =
            [
                // No config changes: Default result expected.
                [ [], [], [], ],

                [
                    ['item_title' => [$provider => 'shortname']],
                    ['title' => 'test-short-name'],
                    [],
                ],

                [
                    ['item_title' => [$provider => 'nonexistent']],
                    // Default to first title dataholder expected.
                    ['title' => 'Test item 1'],
                    [],
                ],

                [
                    ['image_enabled' => '0'],
                    ['image_enabled' => false],
                    ['image'],
                ],

                [
                    ['hero_data_type' => 'text', 'hero_data_text' => [$provider => 'course_category']],
                    ['hero_data_type' => 'text', 'hero_data_text_enabled' => true, 'hero_data_text' => 'My Course Category'],
                    [],
                ],

                [
                    ['hero_data_type' => 'icon', 'hero_data_icon' => [$provider => 'type_icon']],
                    [
                        'hero_data_type' => 'icon',
                        'hero_data_icon_enabled' => true,
                        'hero_data_icon' => (object)['icon' => '<span class="test">Type Icon HTML</span>'],
                    ],
                    [],
                ],

                [
                    ['item_description_enabled' => '1', 'item_description' => [$provider => 'catalog_learning_type']],
                    ['description_enabled' => true, 'description' => 'Test Learning Type'],
                    [],
                ],

                [
                    // Additional texts. Configured count: 2. Configured texts: 2.
                    [
                        'item_additional_text_count' => '2',
                        'item_additional_text' => [$provider => ['u_i_r_editingteacher', 'tags']],
                    ],
                    [
                        'text_placeholders' =>
                            [
                                0 =>
                                    (object)[
                                        'data_exists' => true,
                                        'data' => 'Test Teacher',
                                    ],
                                1 =>
                                    (object)[
                                        'data_exists' => true,
                                        'data' => 'Test Tags',
                                    ],
                            ],
                    ],
                    [],
                ],

                [
                    // Additional texts. Configured count: 3. Configured texts: 2.
                    [
                        'item_additional_text_count' => '3',
                        'item_additional_text' => [$provider => ['u_i_r_editingteacher', 'tags']],
                    ],
                    [
                        'text_placeholders' =>
                            [
                                0 =>
                                    (object)[
                                        'data_exists' => true,
                                        'data' => 'Test Teacher',
                                    ],
                                1 =>
                                    (object)[
                                        'data_exists' => true,
                                        'data' => 'Test Tags',
                                    ],
                                2 =>
                                    (object)[
                                        'data_exists' => false,
                                        'data' => '',
                                    ],
                            ],
                    ],
                    [],
                ],

                [
                    // Additional texts. Configured count: 1. Configured texts: 2.
                    [
                        'item_additional_text_count' => '1',
                        'item_additional_text' => [$provider => ['u_i_r_editingteacher', 'tags']],
                    ],
                    [
                        'text_placeholders' =>
                            [
                                0 =>
                                    (object)[
                                        'data_exists' => true,
                                        'data' => 'Test Teacher',
                                    ],
                            ],
                    ],
                    [],
                ],

                [
                    // With additional text label.
                    [
                        'item_additional_text_count' => '2',
                        'item_additional_text' => [$provider => ['u_i_r_editingteacher', 'tags']],
                        'item_additional_text_label' => [$provider => ['0', '1']],
                    ],
                    [
                        'text_placeholders' =>
                            [
                                0 =>
                                    (object)[
                                        'data_exists' => true,
                                        'data' => 'Test Teacher',
                                    ],
                                1 =>
                                    (object)[
                                        'data_exists' => true,
                                        'data' => 'Test Tags',
                                        'show_label' => true,
                                        'label' => 'Tags',
                                    ],
                            ],
                    ],
                    [],
                ],

                [
                    // Additional icons.
                    [
                        'item_additional_icons_enabled' => '1',
                        'item_additional_icons' => [$provider => ['activity_type_icons', 'icon']],
                    ],
                    [
                        'icon_placeholders_enabled' => true,
                        'icon_placeholders' =>
                            [
                                0 => (object)['icon' => '<span class="test">Activity Types Icon HTML</span>'],
                                1 => (object)['icon' => '<span class="test">Course Icon HTML</span>'],
                            ],
                    ],
                    [],
                ],
                [
                    ['progress_bar_enabled' => '1'],
                    ['progress_bar_enabled' => true, 'progress_bar' => 'Test progressbar'],
                    [],
                ]
            ];
        return $test_data;
    }

    /**
     * @return array
     */
    public function config_changes_provider(): array {
        $test_data = [];
        foreach (['course', 'program', 'certification'] as $provider) {
            $provider_test_data = $this->get_create_test_data($provider);
            // Add provider name to the test data set.
            foreach ($provider_test_data as &$dataset) {
                $dataset[] = $provider;
            }
            $test_data = array_merge($test_data, $provider_test_data);
        }
        return $test_data;
    }

    /**
     * Test create() method.
     *
     * Verify item template data depending on config changes.
     *
     * @dataProvider config_changes_provider
     * @param array $config_changes
     * @param array $override_expected
     * @param array $expected_removed
     * @param string $provider
     */
    public function test_create(array $config_changes, array $override_expected, array $expected_removed, string $provider) {
        $this->resetAfterTest();
        config::instance()->update($config_changes);

        $item_data = $this->get_example_item_data($provider);
        $expected = array_replace($this->get_default_expected_template_data(), $override_expected);
        foreach ($expected_removed as $remove_key) {
            unset($expected[$remove_key]);
        }

        // item_wide and item_narrow share the create() method, so only test one.
        $actual = item_wide::create((object)$item_data)->get_template_data();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Get test data for provider.
     *
     * Test data is designed to be reusable for all provider types.
     *
     * @param string $provider
     * @return array
     */
    private function get_required_dataholders_test_data(string $provider): array {
        $defaults = [
            ['fullname', formatter::TYPE_PLACEHOLDER_TITLE],
            ['catalog_learning_type', formatter::TYPE_PLACEHOLDER_TEXT],
            ['course_category', formatter::TYPE_PLACEHOLDER_TEXT],
            ['image', formatter::TYPE_PLACEHOLDER_IMAGE],
        ];
        $test_data =
            [
                [
                    // No config changes: Default result.
                    [],
                    $defaults,
                ],

                [
                    ['item_title' => [$provider => 'shortname']],
                    [
                        ['shortname', formatter::TYPE_PLACEHOLDER_TITLE],
                        ['catalog_learning_type', formatter::TYPE_PLACEHOLDER_TEXT],
                        ['course_category', formatter::TYPE_PLACEHOLDER_TEXT],
                        ['image', formatter::TYPE_PLACEHOLDER_IMAGE],
                    ],
                ],

                [
                    ['hero_data_type' => 'text', 'hero_data_text' => [$provider => 'course_category']],
                    array_merge($defaults, [['course_category', formatter::TYPE_PLACEHOLDER_TEXT]]),
                ],

                [
                    ['hero_data_type' => 'text', 'hero_data_text' => [$provider => 'invalid']],
                    $defaults,
                ],

                [
                    ['hero_data_type' => 'icon', 'hero_data_icon' => [$provider => 'icon']],
                    array_merge($defaults, [['icon', formatter::TYPE_PLACEHOLDER_ICON]]),
                ],

                [
                    ['hero_data_type' => 'icon', 'hero_data_icon' => []],
                    $defaults,
                ],

                [
                    ['item_description_enabled' => '1', 'item_description' => [$provider => 'catalog_learning_type']],
                    array_merge($defaults, [['catalog_learning_type', formatter::TYPE_PLACEHOLDER_TEXT]]),
                ],

                [
                    ['item_description_enabled' => '1', 'item_description' => [$provider => 'invalid']],
                    $defaults,
                ],

                [
                    // Additional texts. Configured count: 2. Configured texts: 2. This is expected to override the
                    // default 2 additional texts.
                    ['item_additional_text_count' => '2', 'item_additional_text' => [$provider => ['shortname', 'tags']]],
                    [
                        ['fullname', formatter::TYPE_PLACEHOLDER_TITLE],
                        ['shortname', formatter::TYPE_PLACEHOLDER_TEXT],
                        ['tags', formatter::TYPE_PLACEHOLDER_TEXT],
                        ['image', formatter::TYPE_PLACEHOLDER_IMAGE],
                    ],
                ],

                [
                    // Additional texts. Configured count: 2. Configured texts: 1.
                    ['item_additional_text_count' => '2', 'item_additional_text' => [$provider => ['shortname']]],
                    [
                        ['fullname', formatter::TYPE_PLACEHOLDER_TITLE],
                        ['shortname', formatter::TYPE_PLACEHOLDER_TEXT],
                        ['image', formatter::TYPE_PLACEHOLDER_IMAGE],
                    ],
                ],

                [
                    // Additional texts. Configured count: 3. Configured texts: 2.
                    ['item_additional_text_count' => '3', 'item_additional_text' => [$provider => ['shortname', 'tags']]],
                    [
                        ['fullname', formatter::TYPE_PLACEHOLDER_TITLE],
                        ['shortname', formatter::TYPE_PLACEHOLDER_TEXT],
                        ['tags', formatter::TYPE_PLACEHOLDER_TEXT],
                        ['image', formatter::TYPE_PLACEHOLDER_IMAGE],
                    ],
                ],

                [
                    ['image_enabled' => '0'],
                    [
                        ['fullname', formatter::TYPE_PLACEHOLDER_TITLE],
                        ['catalog_learning_type', formatter::TYPE_PLACEHOLDER_TEXT],
                        ['course_category', formatter::TYPE_PLACEHOLDER_TEXT],
                    ],
                ],

                [
                    ['progress_bar_enabled' => '1'],
                    array_merge($defaults, [['progressbar', formatter::TYPE_PLACEHOLDER_PROGRESS]]),
                ],
            ];


        if ($provider == 'course') {
            // For course there is more than one icon, so we should test more than one.
            $test_data[] = [
                [
                    'item_additional_icons_enabled' => '1',
                    'item_additional_icons' => [$provider => ['activity_type_icons', 'icon']],
                ],
                array_merge(
                    $defaults,
                    [
                        ['activity_type_icons', formatter::TYPE_PLACEHOLDER_ICONS],
                        ['icon', formatter::TYPE_PLACEHOLDER_ICONS]
                    ]
                ),
            ];
        } else {
            // For program/certs there is only one icon.
            $test_data[] = [
                [
                    'item_additional_icons_enabled' => '1',
                    'item_additional_icons' => [$provider => ['icon']],
                ],
                array_merge(
                    $defaults,
                    [
                        ['icon', formatter::TYPE_PLACEHOLDER_ICONS]
                    ]
                ),
            ];
        }

        return $test_data;
    }

    /**
     * @return array
     */
    public function required_dataholders_provider(): array {
        $test_data = [];
        foreach (['course', 'program', 'certification'] as $provider) {
            $provider_test_data = $this->get_required_dataholders_test_data($provider);
            // Add provider name to the test data set.
            foreach ($provider_test_data as &$dataset) {
                $dataset[] = $provider;
            }
            $test_data = array_merge($test_data, $provider_test_data);
        }
        return $test_data;
    }

    /**
     * Test get_required_dataholders() method.
     *
     * @dataProvider required_dataholders_provider
     * @param array $config_changes
     * @param array $expected  Array with expected combinations of dataholder-key and formattertype.
     * @param string $provider_name
     */
    public function test_get_required_dataholders(array $config_changes, array $expected, string $provider_name) {
        $this->resetAfterTest();
        config::instance()->update($config_changes);

        $provider_handler = provider_handler::instance();
        $provider = $provider_handler->get_provider($provider_name);
        $required_dataholders = item::get_required_dataholders($provider);

        $actual = array_map(
            function (required_dataholder $required_dataholder) {
                return [$required_dataholder->dataholder->key, $required_dataholder->formattertype];
            },
            $required_dataholders
        );
        sort($expected);
        sort($actual);
        $this->assertSame($expected, $actual);
    }
}
