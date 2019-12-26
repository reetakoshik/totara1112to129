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
class totara_catalog_merge_select_options_loader_testcase extends advanced_testcase {

    public function test_add_options_loader() {
        $single = new \totara_catalog\merge_select\single('testkey', 'testtile');

        // One options loader.
        $optionsloader1 = function () {
            return [
                'testoptionkey1' => 'testoptionvalue1',
                'testoptionkey2' => 'testoptionvalue2',
            ];
        };
        $single->add_options_loader($optionsloader1);

        $options = $single->get_options();
        $expectedoptions = [
            'testoptionkey1' => (object)[
                'key' => 'testoptionkey1',
                'name' => 'testoptionvalue1',
                'default' => true,
            ],
            'testoptionkey2' => (object)[
                'key' => 'testoptionkey2',
                'name' => 'testoptionvalue2',
            ],
        ];

        $this->assertEquals($expectedoptions, $options);

        // Add a second options loader.
        $optionsloader2 = function () {
            return [
                'testoptionkey1' => 'testoptionvalue1',
                'testoptionkey3' => 'testoptionvalue3',
            ];
        };
        $single->add_options_loader($optionsloader2);

        $options = $single->get_options();
        $expectedoptions = [
            'testoptionkey1' => (object)[
                'key' => 'testoptionkey1',
                'name' => 'testoptionvalue1',
                'default' => true,
            ],
            'testoptionkey2' => (object)[
                'key' => 'testoptionkey2',
                'name' => 'testoptionvalue2',
            ],
            'testoptionkey3' => (object)[
                'key' => 'testoptionkey3',
                'name' => 'testoptionvalue3',
            ],
        ];

        $this->assertEquals($expectedoptions, $options);
    }
}
