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

class select_region_primary_test extends advanced_testcase {

    public function test_create() {
        $options = [
            'one' => 'Test option one',
        ];

        $multiselect = \totara_core\output\select_multi::create(
            'testmultiselect',
            'Test multi select title',
            true,
            $options
        );

        $searchtext = \totara_core\output\select_search_text::create(
            'testsearchtext',
            'Test full text search',
            true
        );

        $regionprimary = \totara_core\output\select_region_primary::create([$multiselect, $searchtext]);

        $expected = [
            'selectors' => [
                0 => (object)[
                    'template_name' => 'totara_core/select_multi',
                    'template_data' => [
                        'key' => 'testmultiselect',
                        'title' => 'Test multi select title',
                        'title_hidden' => true,
                        'options' => [
                            0 => (object)[
                                'active' => false,
                                'key' => 'one',
                                'name' => 'Test option one',
                            ],
                        ],
                    ],
                ],
                1 => (object)[
                    'template_name' => 'totara_core/select_search_text',
                    'template_data' => [
                        'key' => 'testsearchtext',
                        'title' => 'Test full text search',
                        'title_hidden' => true,
                        'current_val' => null,
                        'placeholder_show' => true,
                        'has_hint_icon' => false,
                    ],
                ],
            ],
        ];

        $actual = $regionprimary->get_template_data();

        $this->assertEquals($expected, $actual);
    }
}