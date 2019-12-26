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

class select_region_panel_test extends advanced_testcase {

    public function test_create() {
        $multioptions = [
            'one' => 'Test option one',
        ];

        $multiselect = \totara_core\output\select_multi::create(
            'testmultiselect',
            'Test multi select title',
            true,
            $multioptions
        );

        $searchtext = \totara_core\output\select_search_text::create(
            'testsearchtext',
            'Test full text search',
            true
        );

        $treeoptions = [
            (object)[
                'key' => 'abc',
                'name' => 'ABC',
                'default' => true,
            ]
        ];

        $tree = \totara_core\output\select_tree::create(
            'testtreelist',
            'Test tree list title',
            false,
            $treeoptions
        );

        $regionpanel = \totara_core\output\select_region_panel::create(
            'Select region panel title',
            [$multiselect, $searchtext, $tree],
            0,
            false,
            true
        );

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
                2 => (object)[
                    'template_name' => 'totara_core/select_tree',
                    'template_data' => [
                        'key' => 'testtreelist',
                        'title' => 'Test tree list title',
                        'title_hidden' => false,
                        'options' => [
                            0 => (object)[
                                'key' => 'abc',
                                'name' => 'ABC',
                                'active' => true,
                                'default' => true,
                                'has_children' => false,
                            ],
                        ],
                        'active_name' => 'ABC',
                        'flat_tree' => false,
                        'parents_are_selectable' => true,
                    ],
                ],
            ],
            'title' => 'Select region panel title',
            'display_active_count' => false,
            'display_clear_trigger' => false,
            'hide_on_mobile' => true,
        ];

        $actual = $regionpanel->get_template_data();

        $this->assertEquals($expected, $actual);
    }
}