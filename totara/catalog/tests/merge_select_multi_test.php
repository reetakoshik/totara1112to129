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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_catalog
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the merge_select base class for catalog component.
 *
 * @group totara_catalog
 */
class totara_catalog_merge_select_multi_testcase extends advanced_testcase {

    public function test_get_options() {
        $multi = new \totara_catalog\merge_select\multi('testmergeselectkey', 'testtitle');

        $optionsloader1 = function () {
            return [
                'testoptionkey2' => 'testoptionname2',
                'testoptionkey1' => 'testoptionname1',
            ];
        };
        $multi->add_options_loader($optionsloader1);

        $optionsloader2 = function () {
            return [
                'testoptionkey1' => 'testoptionnamex',
                'testoptionkey3' => 'testoptionname3',
            ];
        };
        $multi->add_options_loader($optionsloader2);

        $options = $multi->get_options();
        $expectedoptions = [
            'testoptionkey2' => 'testoptionname2',
            'testoptionkey3' => 'testoptionname3',
            'testoptionkey1' => 'testoptionnamex', // Sorted alphabetically.
        ];

        $this->assertEquals($expectedoptions, $options);

        // Check that the records have been ordered correctly.
        $this->assertEquals(
            implode(',', array_keys($expectedoptions)),
            implode(',', array_keys($options))
        );
    }

    public function test_get_optional_params() {
        $multi = new \totara_catalog\merge_select\multi('testmergeselectkey', 'testtitle');

        $optionsloader = function () {
            return [
                'testoptionkey2' => 'testoptionname2',
                'testoptionkey1' => 'testoptionname1',
                'testoptionkey3' => 'testoptionname3',
            ];
        };
        $multi->add_options_loader($optionsloader);

        $optionalparams = $multi->get_optional_params();
        $expectedoptionalparams = [
            new \totara_catalog\optional_param('testmergeselectkey', null, PARAM_RAW, true),
        ];

        $this->assertEquals($expectedoptionalparams, $optionalparams);
    }

    public function test_merge() {
        $multi1 = new \totara_catalog\merge_select\multi('testmergeselectkey', 'testtitle');
        $optionsloader1 = function () {
            return [
                'testoptionkey1' => 'testoptionname1',
            ];
        };
        $multi1->add_options_loader($optionsloader1);

        $multi2 = new \totara_catalog\merge_select\multi('testmergeselectkey', 'testtitle');
        $optionsloader2 = function () {
            return [
                'testoptionkey2' => 'testoptionname2',
            ];
        };
        $multi2->add_options_loader($optionsloader2);

        $this->assertTrue($multi1->can_merge($multi2));
        $multi1->merge($multi2);

        // Options have been merged.
        $options = $multi1->get_options();
        $expectedoptions = [
            'testoptionkey1' => 'testoptionname1',
            'testoptionkey2' => 'testoptionname2',
        ];

        $this->assertEquals($expectedoptions, $options);
    }

    public function test_set_current_data() {
        $multi = new \totara_catalog\merge_select\multi('testmergeselectkey', 'testtitle');
        $multi->set_current_data(
            [
                'testmergeselectkey' => [
                    false,
                    1,
                    true,
                    0,
                    'foo%20bar%40baz',
                ]
            ]
        );

        // We expect values to go through rawurldecode().
        $expecteddata = [
            0 => '',
            1 => '1',
            2 => '1',
            3 => '0',
            4 => 'foo bar@baz',
        ];

        $this->assertEquals($expecteddata, $multi->get_data());
    }

    public function test_get_template() {
        $multi = new \totara_catalog\merge_select\multi('testmergeselectkey', 'testtitle');
        $optionsloader = function () {
            return [
                'testoptionkey3' => 'testoptionname3',
                'testoptionkey1' => 'testoptionnamex',
                'testoptionkey2' => 'testoptionname2',
                'test & option key 4' => 'testoptionname4',
            ];
        };
        $multi->add_options_loader($optionsloader);
        $multi->set_title_hidden();
        $multi->set_current_data(
            [
                'testmergeselectkey' => [
                    'unknown',
                    'testoptionkey1',
                    'testoptionkey2',
                    'test%20%26%20option%20key%204'
                ],
            ]
        );

        $template = $multi->get_template();

        $this->assertEquals('totara_core\output\select_multi', get_class($template));
        $data = $template->get_template_data();

        $expecteddata = [
            'key' => 'testmergeselectkey',
            'title' => 'testtitle',
            'title_hidden' => true,
            'options' => [
                (object)[
                    'active' => true,
                    'key' => 'testoptionkey2',
                    'name' => 'testoptionname2',
                ],
                (object)[
                    'active' => false,
                    'key' => 'testoptionkey3',
                    'name' => 'testoptionname3',
                ],
                (object)[
                    'active' => true,
                    'key' => 'test%20%26%20option%20key%204',
                    'name' => 'testoptionname4',
                ],
                (object)[
                    'active' => true,
                    'key' => 'testoptionkey1',
                    'name' => 'testoptionnamex',
                ],
            ]
        ];
        $this->assertEquals($expecteddata, $data);
    }
}
