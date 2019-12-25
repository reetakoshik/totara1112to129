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

use totara_catalog\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . "/totara/catalog/tests/output_test_base.php");

/**
 * Class external_test
 *
 * Tests for external class.
 *
 * @package totara_catalog
 * @group totara_catalog
 */
class totara_catalog_external_testcase extends output_test_base {

    /**
     * Test get_catalog_template_data_parameters() method.
     */
    public function test_get_catalog_template_data_parameters() {
        $params = external::get_catalog_template_data_parameters();
        $this->assertInstanceOf(external_function_parameters::class, $params);
        $this->assertCount(8, $params->keys);

        $expected_external_values = [
            'itemstyle' => 'alpha',
            'limitfrom' => 'int',
            'maxcount' => 'int',
            'orderbykey' => 'alpha',
            'resultsonly' => 'bool',
            'debug' => 'bool',
            'request' => 'raw',
        ];
        foreach ($expected_external_values as $key => $type) {
            $this->assertArrayHasKey($key, $params->keys);
            $param_key = $params->keys[$key];
            $this->assertInstanceOf(external_value::class, $param_key);
            $this->assertEquals($type, $param_key->type);
            $this->assertNotEmpty($param_key->desc);
            $this->assertTrue($param_key->allownull);
            $this->assertSame(1, $param_key->required);
            $this->assertNull($param_key->default);
        }

        $expected_count = 0;
        $expected_filter_params = [
            external_value::class => [],
            external_multiple_structure::class => [],
        ];

        foreach (\totara_catalog\local\filter_handler::instance()->get_all_filters() as $filter) {
            $optionalparams = $filter->selector->get_optional_params();
            foreach ($optionalparams as $optionalparam) {
                if ($optionalparam->multiplevalues) {
                    $expected_filter_params[external_multiple_structure::class][] = $optionalparam->key;
                } else {
                    $expected_filter_params[external_value::class][] = $optionalparam->key;
                }
                $expected_count++;
            }
        }

        $filterparams = $params->keys['filterparams'];
        $this->assertInstanceOf(external_single_structure::class, $filterparams);
        $this->assertCount($expected_count, $filterparams->keys);
        foreach ($expected_filter_params as $expected_class => $keys) {
            foreach ($keys as $key) {
                $this->assertArrayHasKey($key, $filterparams->keys);
                $filterparam_key = $filterparams->keys[$key];
                $this->assertInstanceOf($expected_class, $filterparam_key);
            }
        }
    }

    /**
     * Test get_catalog_template_data() method.
     *
     * That method is basically just a wrapper for catalog::create(), which is tested elsewhere. So we
     * just do a basic call and assertion here.
     */
    public function test_get_catalog_template_data() {
        $params = $this->get_catalog_default_params();
        $actual = external::get_catalog_template_data(...$params);
        $expected = $this->get_expected_catalog_template_data();
        $this->assert_catalog_template_data($expected, $actual);
    }

    /**
     * Test get_catalog_template_data_returns() method.
     */
    public function test_get_catalog_template_data_returns() {
        $this->assertNull(external::get_catalog_template_data_returns());
    }

    /**
     * Test get_details_template_data_parameters() method.
     */
    public function test_get_details_template_data_parameters() {
        $params = external::get_details_template_data_parameters();
        $this->assertInstanceOf(external_function_parameters::class, $params);
        $this->assertCount(2, $params->keys);

        $expected_external_values = [
            'catalogid' => 'int',
            'request' => 'raw',
        ];
        foreach ($expected_external_values as $key => $type) {
            $this->assertArrayHasKey($key, $params->keys);
            $param_key = $params->keys[$key];
            $this->assertInstanceOf(external_value::class, $param_key);
            $this->assertEquals($type, $param_key->type);
            $this->assertNotEmpty($param_key->desc);
            $this->assertTrue($param_key->allownull);
            $this->assertSame(1, $param_key->required);
            $this->assertNull($param_key->default);
        }
    }

    /**
     * Test get_details_template_data() method.
     */
    public function test_get_details_template_data() {
        global $DB;
        $this->resetAfterTest();

        $test_default_titles = [
            'course' => 'Test course 1',
            'program' => 'Program Fullname',
            'certification' => 'Program Fullname'
        ];
        foreach (['course', 'program', 'certification'] as $provider) {
            $object_id = $this->create_object_for_provider($provider);
            $catalog_record = $DB->get_record('catalog', ['objecttype' => $provider, 'objectid' => $object_id], 'id');
            $expected = array_replace(
                $this->get_default_expected_item_template_data($provider, $object_id),
                [
                    'id'    => $catalog_record->id,
                    'title' => $test_default_titles[$provider],
                ]
            );

            $actual = external::get_details_template_data($catalog_record->id, 'arbitrary request string');
            $this->assertEquals($expected, $actual);
        }
    }

    /**
     * Test get_details_template_data_returns() method.
     */
    public function test_get_details_template_data_returns() {
        $this->assertNull(external::get_details_template_data_returns());
    }
}
