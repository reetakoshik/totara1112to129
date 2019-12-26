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

use totara_catalog\local\config;
use totara_catalog\local\config_form_helper;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/totara/catalog/tests/config_test_base.php');

/**
 * Class config_form_helper_testcase
 *
 * Tests for the configuration form helper methods. Mainly tests for transformations from
 * form data to config data in DB and vice versa.
 *
 * @package totara_catalog
 * @group totara_catalog
 */
class totara_catalog_config_form_helper_testcase extends config_base_testcase {

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Expected transformation to form data that corresponds to get_non_default_example_values().
     *
     * @return array
     */
    private function get_expected_form_non_default_examples(): array {
        return [
            'browse_by' => 'custom',
            'browse_by_custom' => 'test_val',
            'details_additional_text_count' => '0',
            'details_additional_icons_enabled' => '1',
            'details_description_enabled' => '1',
            'details_title_enabled' => '0',
            'featured_learning_enabled' => '1',
            'featured_learning_source' => 'testsource',
            'featured_learning_value' => 'testvalue',
            'filters' => [ 'testkey' => 'testname' ],
            'hero_data_type' => 'icon',
            'image_enabled' => '0',
            'item_description_enabled' => '1',
            'item_additional_text_count' => '5',
            'item_additional_icons_enabled' => '1',
            'items_per_load' => '40',
            'progress_bar_enabled' => '1',
            'rich_text_content_enabled' => '0',
            'view_options' => 'tile_only',
            'learning_types_in_catalog' => [ 0 => 'program' ],
            'item_title__course' => 'shortname',
            'item_title__program' => 'shortname',
            'item_title__certification' => 'shortname',
            'item_additional_icons__course' => ['test_placeholder'],
            'item_additional_icons__certification' => ['abc', 'def'],
            'item_additional_icons__program' => ['test'],
            'item_additional_text__course__0' => 'test_placeholder',
            'item_additional_text__certification__0' => 'test',
            'item_additional_text_label__course__0' => '0',
            'item_additional_text_label__course__1' => '1',
            'item_additional_text_label__certification__0' => '1',
            'item_additional_text_label__certification__1' => '1',
            'item_description__course' => 'test_placeholder',
            'item_description__program' => '',
            'item_description__certification' => 'test',
            'details_title__course' => 'shortname',
            'details_title__program' => 'shortname',
            'details_title__certification' => 'shortname',
            'details_additional_icons__course' => ['test_placeholder2'],
            'details_additional_icons__certification' => ['ghi', 'jkl'],
            'details_additional_icons__program' => ['test2'],
            'details_additional_text__program__0' => 'test_placeholder',
            'details_additional_text__certification__0' => 'test',
            'details_additional_text_label__program__0' => '0',
            'details_additional_text_label__program__1' => '1',
            'details_additional_text_label__certification__0' => '1',
            'details_additional_text_label__certification__1' => '1',
            'details_description__course' => '',
            'details_description__program' => 'test_placeholder',
            'details_description__certification' => 'test',
            'hero_data_icon__course' => 'test_placeholder',
            'hero_data_icon__certification' => 'test',
            'hero_data_icon__program' => '',
            'hero_data_text__course' => '',
            'hero_data_text__program' => 'test_placeholder',
            'hero_data_text__certification' => 'test',
            'rich_text__course' => 'test_placeholder',
            'rich_text__program' => '',
            'rich_text__certification' => 'test',
        ];
    }

    public function test_get_config_for_form_default() {
        $this->assertEquals($this->get_expected_form_defaults(), config_form_helper::create()->get_config_for_form());
    }

    public function test_get_config_for_form_non_default() {
        $config = config::instance();
        $config->update($this->get_non_default_example_values() + ['learning_types_in_catalog' => ['program']]);

        $this->assertEquals($this->get_expected_form_non_default_examples(), config_form_helper::create()->get_config_for_form());
    }

    public function test_update_from_form_data() {
        // Get defaults for form so we have something to write back.
        $fh = config_form_helper::create();
        $default_form_config = $fh->get_config_for_form();

        // Set non-default values in config.
        $fh->config->update($this->get_non_default_example_values());
        $this->assertNotEquals($default_form_config, $fh->get_config_for_form());

        // Write default values back.
        $fh->update_from_form_data($default_form_config);

        // Only the values that are passed to update_from_form_data() should be overwritten, so we expect
        // some of the non-default values to still be there, because these config keys are not in the defaults.
        $non_default_still_there = [
            'item_additional_text_label__course__0' => '0',
            'item_additional_text_label__course__1' => '1',
            'item_additional_text_label__certification__0' => '1',
            'item_additional_text_label__certification__1' => '1',
            'details_additional_text__program__0' => 'test_placeholder',
            'details_additional_text__certification__0' => 'test',
            'details_additional_text_label__program__0' => '0',
            'details_additional_text_label__program__1' => '1',
            'details_additional_text_label__certification__0' => '1',
            'details_additional_text_label__certification__1' => '1',
        ];
        $this->assertEquals(array_merge($default_form_config, $non_default_still_there), $fh->get_config_for_form());

        $fh->update_from_form_data(['item_additional_text_label__otherprovider__321' => '1']);

        // Overwrite these as well and add some more valid configuration.
        $new_values = [
            'item_additional_text__course__0' => 'text1',
            'item_additional_text__course__1' => 'text2',
            'item_additional_text__certification__0' => 'text3',
            'item_additional_text__certification__1' => 'text4',
            'item_additional_text__program__0' => 'text5',
            'item_additional_text__program__1' => 'text6',
            'item_additional_text_label__course__0' => '1',
            'item_additional_text_label__course__1' => '0',
            'details_additional_text__program__0' => 'test_placeholder6',
            'details_additional_text_label__program__0' => '1',
            'details_additional_text_label__program__1' => '0',
            'item_additional_text__otherprovider__0' => 'test_placeholder8',
            'item_additional_text__otherprovider__1' => 'test_placeholder9',
            'item_additional_text_label__otherprovider__0' => '1',
            'item_additional_text_label__otherprovider__1' => '1',
            'item_additional_text_label__otherprovider__2' => '1',
            'details_additional_text__otherprovider__0' => 'test_placeholder11',
            'details_additional_text_label__otherprovider__0' => '1',
            'filters' => [
                'catalog_learning_type_panel' => 'Learning type',
                'course_acttyp_panel' => 'Activity type',
                'course_format_multi' => 'Format',
            ],
        ];
        $fh->update_from_form_data($new_values);
        $this->assertEquals(array_merge($default_form_config, $new_values), $fh->get_config_for_form());
    }
    
    public function test_bad_form_data_handling() {
        // Config keys for dynamic provider list fields (additional texts).
        // As long as these keys are well formed, only the order in which they are sent matters and
        // indexes are silently rectified. Badly formed keys are silently ignored.
        $new_values = [
            'item_additional_text__testprovider__99' => 'test_placeholder1',
            'item_additional_text__testprovider__invalid' => 'test_placeholder2',
            'item_additional_text__testprovider__2' => 'test_placeholder3',
            'item_additional_text__testprovider__10' => 'test_placeholder4',
            'unknown_prefix__testprovider__0' => 'test_placeholder5',
            'item_additional_text__only-lowercase-and-underscores-allowed__0' => 'test_placeholder6',
            'item_additional_text__Capitals_Bad__0' => 'test_placeholder7',
            'details_additional_text__x__0' => '',
            'details_additional_text______0' => '',
            'details_additional_text___start_with_underscore_bad__0' => '',
            'details_additional_text__end_with_underscores_ok______0' => 'test_placeholder9',

            'item_additional_text__course__0' => 'text1',
            'item_additional_text__course__1' => 'text2',
            'item_additional_text__certification__0' => 'text3',
            'item_additional_text__certification__1' => 'text4',
            'item_additional_text__program__0' => 'text5',
            'item_additional_text__program__1' => 'text6',
        ];

        $fh = config_form_helper::create();
        $fh->update_from_form_data($new_values);

        $expected = [
            'item_additional_text__testprovider__0' => 'test_placeholder1',
            'item_additional_text__testprovider__1' => 'test_placeholder3',
            'item_additional_text__testprovider__2' => 'test_placeholder4',
            'details_additional_text__x__0' => '',
            'details_additional_text__end_with_underscores_ok______0' => 'test_placeholder9',

            'item_additional_text__course__0' => 'text1',
            'item_additional_text__course__1' => 'text2',
            'item_additional_text__certification__0' => 'text3',
            'item_additional_text__certification__1' => 'text4',
            'item_additional_text__program__0' => 'text5',
            'item_additional_text__program__1' => 'text6',
        ];
        $this->assertEquals(array_merge($this->get_expected_form_defaults(), $expected), $fh->get_config_for_form());
    }
}
