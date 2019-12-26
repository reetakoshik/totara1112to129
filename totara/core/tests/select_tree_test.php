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

class select_tree_test extends advanced_testcase {

    public function test_create() {
        $optionsdeep = [
            (object)[
                'name' => 'All',
                'key' => 'all',
            ],
            (object)[
                'name' => 'Flooding',
                'key' => 'flooding',
                'children' => [
                    (object)[
                        'name' => 'Level 2',
                        'key' => 'level2',
                        'children' => [
                            (object)[
                                'name' => 'Level 3',
                                'key' => 'level3',
                            ],
                        ],
                    ],
                    (object)[
                        'name' => 'Level 2b',
                        'key' => 'level2b',
                        'default' => true,
                    ],
                ],
            ],
            (object)[
                'name' => 'Earthquake',
                'key' => 'earthquake'
            ],
            (object)[
                'name' => 'Self Combustion',
                'key' => 'selfcombustion'
            ],
        ];

        // Test with no title and no active key.

        $treelist1 = \totara_core\output\select_tree::create(
            'testtreelistdeep',
            'Test tree list title deep',
            true,
            $optionsdeep
        );

        $expecteddeep = [
            'key' => 'testtreelistdeep',
            'title' => 'Test tree list title deep',
            'title_hidden' => true,
            'options' => [
                0 => (object)[
                    'key' => 'all',
                    'name' => 'All',
                    'active' => false,
                    'default' => false,
                    'has_children' => false,
                ],
                1 => (object)[
                    'key' => 'flooding',
                    'name' => 'Flooding',
                    'active' => false,
                    'default' => false,
                    'has_children' => true,
                    'children' => [
                        0 => (object)[
                            'key' => 'level2',
                            'name' => 'Level 2',
                            'active' => false,
                            'default' => false,
                            'has_children' => true,
                            'children' => [
                                0 => (object)[
                                    'key' => 'level3',
                                    'name' => 'Level 3',
                                    'active' => false,
                                    'default' => false,
                                    'has_children' => false,
                                ],
                            ],
                        ],
                        1 => (object)[
                            'key' => 'level2b',
                            'name' => 'Level 2b',
                            'active' => true,
                            'default' => true,
                            'has_children' => false,
                        ],
                    ],
                ],
                2 => (object)[
                    'key' => 'earthquake',
                    'name' => 'Earthquake',
                    'active' => false,
                    'default' => false,
                    'has_children' => false,
                ],
                3 => (object)[
                    'key' => 'selfcombustion',
                    'name' => 'Self Combustion',
                    'active' => false,
                    'default' => false,
                    'has_children' => false,
                ],
            ],
            'active_name' => 'Level 2b',
            'flat_tree' => false,
            'parents_are_selectable' => true,
        ];

        $actual1 = $treelist1->get_template_data();

        $this->assertEquals($expecteddeep, $actual1);

        // Test with title and active key.

        $treelist2 = \totara_core\output\select_tree::create(
            'testtreelistdeep',
            'Test tree list title deep',
            false,
            $optionsdeep,
            'level3'
        );

        $expecteddeep['title_hidden'] = false;
        $expecteddeep['options'][1]->children[1]->active = false;
        $expecteddeep['options'][1]->children[0]->children[0]->active = true;
        $expecteddeep['active_name'] = 'Level 3';

        $actual2 = $treelist2->get_template_data();

        $this->assertEquals($expecteddeep, $actual2);

        // Test with flat tree.

        $optionsflat = [
            (object)[
                'name' => 'Earthquake',
                'key' => 'earthquake'
            ],
            (object)[
                'name' => 'Self Combustion',
                'key' => 'selfcombustion',
                'default' => 'true'
            ],
            (object)[
                'name' => 'Plague',
                'key' => 'plague'
            ],
        ];

        $treelist3 = \totara_core\output\select_tree::create(
            'testtreelistflat',
            'Test tree list title flat',
            true,
            $optionsflat,
            'earthquake',
            true,
            false
        );

        $expectedflat = [
            'key' => 'testtreelistflat',
            'title' => 'Test tree list title flat',
            'title_hidden' => true,
            'options' => [
                0 => (object)[
                    'key' => 'earthquake',
                    'name' => 'Earthquake',
                    'active' => true,
                    'default' => false,
                    'has_children' => false,
                ],
                1 => (object)[
                    'key' => 'selfcombustion',
                    'name' => 'Self Combustion',
                    'active' => false,
                    'default' => true,
                    'has_children' => false,
                ],
                2 => (object)[
                    'key' => 'plague',
                    'name' => 'Plague',
                    'active' => false,
                    'default' => false,
                    'has_children' => false,
                ],
            ],
            'active_name' => 'Earthquake',
            'flat_tree' => true,
            'parents_are_selectable' => false,
        ];

        $actual3 = $treelist3->get_template_data();

        $this->assertEquals($expectedflat, $actual3);
    }
}