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

class grid_test extends advanced_testcase {

    public function test_create() {
        $tiles = [];

        for ($i = 1; $i <= 3; $i++) {
            $tile = \totara_core\output\select_search_text::create('tile' . $i, 'Tile ' . $i, true);
            $tiles[] = $tile;
        }

        // Test with multiple columns.

        $grid1 = \totara_core\output\grid::create($tiles);

        $expected = [
            'single_column' => false,
            'tiles_exist' => true,
            'tiles' => [
                0 => (object)[
                    'template_name' => 'totara_core/select_search_text',
                    'template_data' => [
                        'key' => 'tile1',
                        'title' => 'Tile 1',
                        'title_hidden' => true,
                        'current_val' => null,
                        'placeholder_show' => true,
                        'has_hint_icon' => false,
                    ],
                ],
                1 => (object)[
                    'template_name' => 'totara_core/select_search_text',
                    'template_data' => [
                        'key' => 'tile2',
                        'title' => 'Tile 2',
                        'title_hidden' => true,
                        'current_val' => null,
                        'placeholder_show' => true,
                        'has_hint_icon' => false,
                    ],
                ],
                2 => (object)[
                    'template_name' => 'totara_core/select_search_text',
                    'template_data' => [
                        'key' => 'tile3',
                        'title' => 'Tile 3',
                        'title_hidden' => true,
                        'current_val' => null,
                        'placeholder_show' => true,
                        'has_hint_icon' => false,
                    ],
                ],
            ]
        ];

        $actual1 = $grid1->get_template_data();

        $this->assertEquals($expected, $actual1);

        // Test with multiple columns.

        $grid2 = \totara_core\output\grid::create($tiles, true);

        $expected['single_column'] = true;

        $actual2 = $grid2->get_template_data();

        $this->assertEquals($expected, $actual2);
    }
}
