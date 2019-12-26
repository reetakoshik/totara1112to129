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
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

class select_multi_test extends advanced_testcase {

    public function test_create() {
        $options = [
            'one' => 'Test option one',
            'two' => 'Test option two',
            'three' => 'Test option three',
        ];

        // Test with no selected options and no title.

        $multiselect1 = \totara_core\output\select_multi::create(
            'testmultiselect',
            'Test multi select title',
            true,
            $options
        );

        $expected = [
            'key' => 'testmultiselect',
            'title' => 'Test multi select title',
            'title_hidden' => true,
            'options' => [
                0 => (object)[
                    'active' => false,
                    'key' => 'one',
                    'name' => 'Test option one',
                ],
                1 => (object)[
                    'active' => false,
                    'key' => 'two',
                    'name' => 'Test option two',
                ],
                2 => (object)[
                    'active' => false,
                    'key' => 'three',
                    'name' => 'Test option three',
                ],
            ],
        ];

        $actual1 = $multiselect1->get_template_data();

        $this->assertEquals($expected, $actual1);

        // Test with some selected options and title.

        $multiselect2 = \totara_core\output\select_multi::create(
            'testmultiselect',
            'Test multi select title',
            false,
            $options,
            ['two', 'three']
        );

        $expected['title_hidden'] = false;
        $expected['options'][1]->active = true;
        $expected['options'][2]->active = true;

        $actual2 = $multiselect2->get_template_data();

        $this->assertEquals($expected, $actual2);
    }
}