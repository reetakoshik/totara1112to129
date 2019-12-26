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

use totara_catalog\form\base_config_form_controller;
use totara_catalog\local\config;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/totara/catalog/tests/config_form_helper_test.php');

/**
 * Class form_controller_test
 *
 * Test methods for the base form controller for catalog config admin forms.
 *
 * @package totara_catalog
 * @group totara_catalog
 */
class totara_catalog_form_controller_testcase extends config_base_testcase {

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    private function get_expected_default_currentdata($form_key) {
        return array_merge(
            $this->get_expected_form_defaults(),
            [
                'configformhiddenflag' => '1',
                'tab' => $form_key,
            ]
        );
    }

    public function form_key_provider() {
        return [
            ['contents'],
            ['general'],
            ['templates'],
            ['item'],
            ['details'],
            ['filters'],
        ];
    }

    /**
     * @dataProvider form_key_provider
     *
     * @param $form_key
     */
    public function test_get_current_data_defaults($form_key) {
        $form_controller = base_config_form_controller::create_from_key($form_key);
        list($currentdata, $params) = $form_controller->get_current_data_and_params();
        $this->assertEquals($this->get_expected_default_currentdata($form_key), $currentdata);
    }

    public function unknown_values_removed_provider() {
        // Find course tag collection id.
        $tagcollectionid = \core_tag_area::get_collection('core', 'course');

        return [
            [
                'contents',
                [
                    'learning_types_in_catalog' => ['unknown1', 'unknown2', 'course'],
                ],
                [
                    'learning_types_in_catalog' => ['course'],
                ],
            ],
            [
                'general',
                [
                    'browse_by_custom' => 'unknown',
                    'featured_learning_source' => 'unknown',
                    'featured_learning_value' => 'unknown',
                ],
                [
                    'browse_by_custom' => '',
                    'featured_learning_source' => '',
                    'featured_learning_value' => '',
                ],
            ],
            [
                'general',
                [
                    'featured_learning_source' => 'tag_' . $tagcollectionid,
                    'featured_learning_value' => 'unknown',
                ],
                [
                    'featured_learning_source' => 'tag_' . $tagcollectionid,
                    'featured_learning_value' => '',
                ],
            ],
            [
                'item',
                [
                    'hero_data_type' => 'icon',
                    'item_description_enabled' => '1',
                    'item_title' => ['course' => 'unknown'],
                    'hero_data_icon' => ['course' => 'unknown'],
                    'item_description' => ['course' => 'unknown'],
                    'item_additional_text' =>  ['course' => ['unknown']],
                ],
                [
                    'item_title__course' => 'fullname',
                    'hero_data_icon__course' => '',
                    'item_description__course' => '',
                    'item_additional_text__course__0' => '',
                ],
            ],
            [
                'item',
                [
                    'hero_data_type' => 'text',
                    'hero_data_text' => ['course' => 'unknown'],
                ],
                [
                    'hero_data_text__course' => '',
                ],
            ],
            [
                'details',
                [
                    'details_description_enabled' => '1',
                    'rich_text_content_enabled' => '1',
                    'details_title' => ['course' => 'unknown'],
                    'rich_text' => ['course' => 'unknown'],
                    'details_description' => ['course' => 'unknown'],
                    'details_additional_text' =>  ['course' => ['unknown']],
                ],
                [
                    'details_title__course' => 'fullname',
                    'rich_text__course' => '',
                    'details_description__course' => '',
                    'details_additional_text__course__0' => '',
                ],
            ],
        ];
    }

    /**
     * @dataProvider unknown_values_removed_provider
     *
     * @param $tab
     * @param $valid_unknown_values
     * @param $expected_values
     */
    public function test_get_current_data_removes_unknown_values($tab, $valid_unknown_values, $expected_values) {
        $config = config::instance();
        $config->update(
            array_merge(
                ['learning_types_in_catalog' => ['course']],
                $valid_unknown_values
            )
        );

        $form_controller = base_config_form_controller::create_from_key($tab);
        list($currentdata, $params) = $form_controller->get_current_data_and_params();
        $this->assertEquals($expected_values, array_intersect_key($currentdata, $expected_values));
    }
}
