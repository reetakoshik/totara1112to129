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
 * Class config_base_testcase
 *
 * Provides data commonly used by configuration-related tests.
 *
 * @package totara_catalog
 * @group totara_catalog
 */
abstract class config_base_testcase extends advanced_testcase {

    protected function get_expected_static_defaults() {
        return [
            'browse_by' => 'category',
            'browse_by_custom' => '',
            'details_additional_text_count' => '2',
            'details_additional_icons_enabled' => '0',
            'details_description_enabled' => '0',
            'details_title_enabled' => '1',
            'featured_learning_enabled' => '0',
            'featured_learning_source' => '',
            'featured_learning_value' => '',
            'filters' => ['catalog_learning_type_panel' => 'Learning type'],
            'hero_data_type' => 'none',
            'image_enabled' => '1',
            'item_description_enabled' => '0',
            'item_additional_text_count' => '2',
            'item_additional_icons_enabled' => '0',
            'items_per_load' => '20',
            'progress_bar_enabled' => '0',
            'rich_text_content_enabled' => '1',
            'view_options' => 'tile_and_list',
        ];
    }

    protected function get_expected_generated_defaults() {
        return [
            'item_additional_icons' => ['course' => [], 'program' => [], 'certification' => []],
            'item_additional_text' => [
                'course' => ['catalog_learning_type', 'course_category'],
                'program' => ['catalog_learning_type', 'course_category'],
                'certification' => ['catalog_learning_type', 'course_category'],
            ],
            'item_additional_text_label' => ['course' => [], 'program' => [], 'certification' => []],
            'item_title' => ['course' => 'fullname', 'program' => 'fullname', 'certification' => 'fullname'],
            'item_description' => ['course' => '', 'program' => '', 'certification' => ''],
            'details_title' => ['course' => 'fullname', 'program' => 'fullname', 'certification' => 'fullname'],
            'details_additional_icons' => ['course' => [], 'program' => [], 'certification' => []],
            'details_additional_text' => ['course' => [], 'program' => [], 'certification' => []],
            'details_additional_text_label' => ['course' => [], 'program' => [], 'certification' => []],
            'details_description' => ['course' => '', 'program' => '', 'certification' => ''],
            'hero_data_icon' => ['course' => '', 'program' => '', 'certification' => ''],
            'hero_data_text' => ['course' => '', 'program' => '', 'certification' => ''],
            'rich_text' => ['course' => '', 'program' => '', 'certification' => ''],
        ];
    }

    protected function get_non_default_example_values() {
        return [
            // Static defaults.
            'browse_by' => 'custom',
            'browse_by_custom' => 'test_val',
            'details_additional_text_count' => '0',
            'details_additional_icons_enabled' => '1',
            'details_description_enabled' => '1',
            'details_title_enabled' => '0',
            'featured_learning_enabled' => '1',
            'featured_learning_source' => 'testsource',
            'featured_learning_value' => 'testvalue',
            'filters' => ['testkey' => 'testname'],
            'hero_data_type' => 'icon',
            'image_enabled' => '0',
            'item_description_enabled' => '1',
            'item_additional_text_count' => '5',
            'item_additional_icons_enabled' => '1',
            'items_per_load' => '40',
            'progress_bar_enabled' => '1',
            'rich_text_content_enabled' => '0',
            'view_options' => 'tile_only',

            // Generated defaults.
            'item_additional_icons' => ['course' => ['test_placeholder'], 'program' => ['test'], 'certification' => ['abc', 'def']],
            'item_additional_text' => ['course' => ['test_placeholder'], 'program' => [], 'certification' => ['test']],
            'item_additional_text_label' => ['course' => ['0', '1'], 'program' => [], 'certification' => ['1', '1']],
            'item_title' => ['course' => 'shortname', 'program' => 'shortname', 'certification' => 'shortname'],
            'item_description' => ['course' => 'test_placeholder', 'program' => '', 'certification' => 'test'],
            'details_title' => ['course' => 'shortname', 'program' => 'shortname', 'certification' => 'shortname'],
            'details_additional_icons' =>
                ['course' => ['test_placeholder2'], 'program' => ['test2'], 'certification' => ['ghi', 'jkl']],
            'details_additional_text' => ['course' => [], 'program' => ['test_placeholder'], 'certification' => ['test']],
            'details_additional_text_label' => ['course' => [], 'program' => ['0', '1'], 'certification' => ['1', '1']],
            'details_description' => ['course' => '', 'program' => 'test_placeholder', 'certification' => 'test'],
            'hero_data_icon' => ['course' => 'test_placeholder', 'program' => '', 'certification' => 'test'],
            'hero_data_text' => ['course' => '', 'program' => 'test_placeholder', 'certification' => 'test'],
            'rich_text' => ['course' => 'test_placeholder', 'program' => '', 'certification' => 'test'],
        ];
    }

    protected function get_expected_form_defaults() {
        return [
            'browse_by' => 'category',
            'browse_by_custom' => '',
            'details_additional_icons_enabled' => '0',
            'details_additional_icons__course' => [],
            'details_additional_icons__certification' => [],
            'details_additional_icons__program' => [],
            'details_additional_text_count' => '2',
            'details_description__course' => '',
            'details_description__program' => '',
            'details_description__certification' => '',
            'details_description_enabled' => '0',
            'details_title__course' => 'fullname',
            'details_title__program' => 'fullname',
            'details_title__certification' => 'fullname',
            'details_title_enabled' => '1',
            'featured_learning_enabled' => '0',
            'featured_learning_source' => '',
            'featured_learning_value' => '',
            'filters' => ['catalog_learning_type_panel' => 'Learning type'],
            'hero_data_icon__course' => '',
            'hero_data_icon__program' => '',
            'hero_data_icon__certification' => '',
            'hero_data_text__course' => '',
            'hero_data_text__program' => '',
            'hero_data_text__certification' => '',
            'hero_data_type' => 'none',
            'image_enabled' => '1',
            'item_description_enabled' => '0',
            'item_additional_text_count' => '2',
            'item_additional_text__course__0' => 'catalog_learning_type',
            'item_additional_text__course__1' => 'course_category',
            'item_additional_text__program__0' => 'catalog_learning_type',
            'item_additional_text__program__1' => 'course_category',
            'item_additional_text__certification__0' => 'catalog_learning_type',
            'item_additional_text__certification__1' => 'course_category',
            'item_additional_icons_enabled' => '0',
            'item_additional_icons__course' => [],
            'item_additional_icons__certification' => [],
            'item_additional_icons__program' => [],
            'item_title__course' => 'fullname',
            'item_title__program' => 'fullname',
            'item_title__certification' => 'fullname',
            'item_description__course' => '',
            'item_description__program' => '',
            'item_description__certification' => '',
            'items_per_load' => '20',
            'learning_types_in_catalog' => [0 => 'course', 1 => 'certification', 2 => 'program'],
            'progress_bar_enabled' => '0',
            'rich_text_content_enabled' => '1',
            'rich_text__course' => '',
            'rich_text__program' => '',
            'rich_text__certification' => '',
            'view_options' => 'tile_and_list',
        ];
    }
}
