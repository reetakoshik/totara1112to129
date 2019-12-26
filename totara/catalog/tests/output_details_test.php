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
use totara_catalog\output\details;
use totara_catalog\provider_handler;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . "/totara/catalog/tests/output_test_base.php");

/**
 * Class output_details_test
 *
 * Tests for catalog details template class.
 *
 * @package totara_catalog
 * @group totara_catalog
 */
class totara_catalog_output_details_testcase extends output_test_base {

    /**
     * Example details data.
     *
     * Test data that will be passed to details output class. It's designed so that it can be reused for all
     * provider types (course,program,certification).
     *
     * @param string $provider
     * @param int $objectid
     * @return array
     */
    private function get_example_details_data(string $provider, $objectid): array {
        return [
            'id' => 123,
            'objectid' => $objectid,
            'objecttype' => $provider,
            'data' => [
                formatter::TYPE_PLACEHOLDER_TITLE => [
                    'fullname' => 'Test details 1',
                    'shortname' => 'test-short-name'
                ],
                formatter::TYPE_PLACEHOLDER_TEXT => [
                    'u_i_r_editingteacher' => 'Test Teacher',
                    'tags' => 'Test Tags',
                ],
                formatter::TYPE_PLACEHOLDER_ICONS => [
                    'activity_type_icons' => [(object)['icon' => '<span class="test">Activity Types Icon HTML</span>']],
                    'icon' => [(object)['icon' => '<span class="test">Course Icon HTML</span>']]
                ],
                formatter::TYPE_PLACEHOLDER_RICH_TEXT => ['summary_rich' => 'Test rich text content'],
            ],
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

                // Title
                [
                    ['details_title_enabled' => '0'],
                    ['title_enabled' => '0'],
                    ['title'],
                ],
                [
                    ['details_title' => [$provider => 'shortname']],
                    ['title' => 'test-short-name'],
                    [],
                ],
                [
                    ['details_title' => [$provider => 'invalid']],
                    ['title' => ''],
                    [],
                ],

                // Rich text
                [
                    ['rich_text_content_enabled' => '1', 'rich_text' => [$provider => 'summary_rich']],
                    ['rich_text_enabled' => true, 'rich_text' => 'Test rich text content'],
                    [],
                ],
                [
                    ['rich_text_content_enabled' => '1', 'rich_text' => [$provider => 'invalid']],
                    ['rich_text_enabled' => true, 'rich_text' => ''],
                    [],
                ],
                [
                    ['rich_text_content_enabled' => '0', 'rich_text' => [$provider => 'summary_rich']],
                    ['rich_text_enabled' => false],
                    ['rich_text'],
                ],

                // Description
                [
                    ['details_description_enabled' => '1', 'details_description' => [$provider => 'tags']],
                    ['description_enabled' => true, 'description' => 'Test Tags'],
                    [],
                ],
                [
                    ['details_description_enabled' => '1', 'details_description' => [$provider => 'invalid']],
                    ['description_enabled' => true, 'description' => ''],
                    [],
                ],
                [
                    ['details_description_enabled' => '0', 'details_description' => [$provider => 'tags']],
                    ['description_enabled' => false],
                    ['description'],
                ],

                // Additional texts
                [
                    // Configured count: 2. Configured texts: 2.
                    [
                        'details_additional_text_count' => '2',
                        'details_additional_text' => [$provider => ['u_i_r_editingteacher', 'tags']],
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
                    // Configured count: 3. Configured texts: 2.
                    [
                        'details_additional_text_count' => '3',
                        'details_additional_text' => [$provider => ['u_i_r_editingteacher', 'tags']],
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
                    // Configured count: 1. Configured texts: 2.
                    [
                        'details_additional_text_count' => '1',
                        'details_additional_text' => [$provider => ['u_i_r_editingteacher', 'tags']],
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
                        'details_additional_text_count' => '2',
                        'details_additional_text' => [$provider => ['u_i_r_editingteacher', 'tags']],
                        'details_additional_text_label' => [$provider => ['0', '1']],
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
                        'details_additional_icons_enabled' => '1',
                        'details_additional_icons' => [$provider => ['activity_type_icons', 'icon']],
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
     * Verify details template data depending on config changes.
     *
     * @dataProvider config_changes_provider
     * @param array $config_changes
     * @param array $override_expected
     * @param array $expected_removed
     * @param string $provider
     */
    public function test_create(array $config_changes, array $override_expected, array $expected_removed, string $provider) {
        $this->resetAfterTest();

        $object_id = $this->create_object_for_provider($provider);

        config::instance()->update($config_changes);

        $details_data = $this->get_example_details_data($provider, $object_id);
        $expected = array_replace($this->get_default_expected_item_template_data($provider, $object_id), $override_expected);
        foreach ($expected_removed as $remove_key) {
            unset($expected[$remove_key]);
        }

        $actual = details::create((object)$details_data, 'arbitrary request string')->get_template_data();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test manage_link separately as admin user because it only shows for users with capabilities.
     */
    public function test_manage_link() {
        $this->setAdminUser();
        $this->resetAfterTest();

        // Course
        $object_id = $this->create_object_for_provider('course');
        $details_data = $this->get_example_details_data('course', $object_id);
        $override_expected = [
            'has_manage_link' => true,
            'manage_link' => (object)[
                'url' => 'https://www.example.com/moodle/course/view.php?id=' . $object_id,
                'label' => 'Go to course',
            ],
        ];
        $expected = array_replace($this->get_default_expected_item_template_data('course', $object_id), $override_expected);
        $actual = details::create((object)$details_data, 'arbitrary request string')->get_template_data();
        $this->assertEquals($expected, $actual);

        // Program
        $object_id = $this->create_object_for_provider('program');
        $details_data = $this->get_example_details_data('program', $object_id);
        $override_expected = [
            'has_manage_link' => true,
            'manage_link' => (object)[
                'url' => 'https://www.example.com/moodle/totara/program/edit.php?id=' . $object_id,
                'label' => 'Edit program details',
            ],
        ];
        $expected = array_replace($this->get_default_expected_item_template_data('program', $object_id), $override_expected);
        $actual = details::create((object)$details_data, 'arbitrary request string')->get_template_data();
        $this->assertEquals($expected, $actual);

        // Certification
        $object_id = $this->create_object_for_provider('certification');
        $details_data = $this->get_example_details_data('certification', $object_id);
        $override_expected = [
            'has_manage_link' => true,
            'manage_link' => (object)[
                'url' => 'https://www.example.com/moodle/totara/program/edit.php?id=' . $object_id,
                'label' => 'Edit certification details',
            ],
        ];
        $expected = array_replace($this->get_default_expected_item_template_data('certification', $object_id), $override_expected);
        $actual = details::create((object)$details_data, 'arbitrary request string')->get_template_data();
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
        ];
        $test_data =
            [
                [
                    // No config changes: Default result.
                    [],
                    $defaults,
                ],

                [
                    ['details_title' => [$provider => 'shortname']],
                    [
                        ['shortname', formatter::TYPE_PLACEHOLDER_TITLE],
                    ],
                ],

                [
                    ['details_title_enabled' => '0', 'details_title' => [$provider => 'shortname']],
                    [],
                ],

                [
                    ['details_title_enabled' => '1', 'details_title' => [$provider => 'invalid']],
                    [],
                ],

                [
                    ['rich_text_content_enabled' => '1', 'rich_text' => [$provider => 'summary_rich']],
                    array_merge($defaults, [['summary_rich', formatter::TYPE_PLACEHOLDER_RICH_TEXT]]),
                ],

                [
                    ['rich_text_content_enabled' => '1', 'rich_text' => [$provider => 'invalid']],
                    $defaults,
                ],

                [
                    ['rich_text_content_enabled' => '0', 'rich_text' => [$provider => 'summary_rich']],
                    $defaults,
                ],

                [
                    ['details_description_enabled' => '1', 'details_description' => [$provider => 'catalog_learning_type']],
                    array_merge($defaults, [['catalog_learning_type', formatter::TYPE_PLACEHOLDER_TEXT]]),
                ],

                [
                    ['details_description_enabled' => '1', 'details_description' => [$provider => 'invalid']],
                    $defaults,
                ],

                [
                    ['details_description_enabled' => '0', 'details_description' => [$provider => 'catalog_learning_type']],
                    $defaults,
                ],

                [
                    // Additional texts. Configured count: 2. Configured texts: 2.
                    ['details_additional_text_count' => '2', 'details_additional_text' => [$provider => ['shortname', 'tags']]],
                    array_merge(
                        $defaults,
                        [
                            ['shortname', formatter::TYPE_PLACEHOLDER_TEXT],
                            ['tags', formatter::TYPE_PLACEHOLDER_TEXT],
                        ]
                    ),
                ],

                [
                    // Additional texts. Configured count: 2. Configured texts: 1.
                    ['details_additional_text_count' => '2', 'details_additional_text' => [$provider => ['shortname']]],
                    array_merge(
                        $defaults,
                        [
                            ['shortname', formatter::TYPE_PLACEHOLDER_TEXT],
                        ]
                    ),
                ],

                [
                    // Additional texts. Configured count: 3. Configured texts: 2.
                    ['details_additional_text_count' => '3', 'details_additional_text' => [$provider => ['shortname', 'tags']]],
                    array_merge(
                        $defaults,
                        [
                            ['shortname', formatter::TYPE_PLACEHOLDER_TEXT],
                            ['tags', formatter::TYPE_PLACEHOLDER_TEXT],
                        ]
                    ),
                ],
            ];


        if ($provider == 'course') {
            // For course there is more than one icon, so we should test more than one.
            $test_data[] = [
                [
                    'details_additional_icons_enabled' => '1',
                    'details_additional_icons' => [$provider => ['activity_type_icons', 'icon']],
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
                    'details_additional_icons_enabled' => '1',
                    'details_additional_icons' => [$provider => ['icon']],
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
        $required_dataholders = details::get_required_dataholders($provider);

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
