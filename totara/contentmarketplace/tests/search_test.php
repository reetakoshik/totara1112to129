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
 * @author Michael Dunstan <michael.dunstan@androgogic.com>
 * @package totara_contentmarketplace
 */

use totara_contentmarketplace\local\contentmarketplace;

defined('MOODLE_INTERNAL') || die();

/**
 * Test search class
 *
 * @group totara_contentmarketplace
 */
class totara_contentmarketplace_search_testcase extends basic_testcase {

    public function test_paginate_one_page() {
        $options = ['a', 'b', 'c'];
        $paginated = contentmarketplace\search::paginate($options);
        $this->assertSame([
            'pages' => [
                [
                    'options' => ['a', 'b', 'c'],
                    'class' => '',
                ],
            ],
            'show_more' => false,
        ], $paginated);
    }

    public function test_paginate_two_pages() {
        $options = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j'];
        $paginated = contentmarketplace\search::paginate($options);
        $this->assertSame([
            'pages' => [
                [
                    'options' => ['a', 'b', 'c', 'd', 'e'],
                    'class' => '',
                ],
                [
                    'options' => ['f', 'g', 'h', 'i', 'j'],
                    'class' => 'hidden',
                ],
            ],
            'show_more' => true,
        ], $paginated);
    }

    public function test_paginate_many_pages_with_orphan() {
        $options = [
            'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x',
            'y', 'z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
        ];
        $paginated = contentmarketplace\search::paginate($options);
        $this->assertSame([
            'pages' => [
                [
                    'options' => ['a', 'b', 'c', 'd', 'e'],
                    'class' => '',
                ],
                [
                    'options' => ['f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o'],
                    'class' => 'hidden',
                ],
                [
                    'options' => ['p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y'],
                    'class' => 'hidden',
                ],
                [
                    'options' => ['z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
                    'class' => 'hidden',
                ],
            ],
            'show_more' => true,
        ], $paginated);
    }


    public function test_paginate_one_page_with_orphan() {
        $options = ['a', 'b', 'c', 'd', 'e', 'f', 'g'];
        $paginated = contentmarketplace\search::paginate($options);
        $this->assertSame([
            'pages' => [
                [
                    'options' => ['a', 'b', 'c', 'd', 'e', 'f', 'g'],
                    'class' => '',
                ],
            ],
            'show_more' => false,
        ], $paginated);
    }

    public function test_paginate_empty() {
        $options = [];
        $paginated = contentmarketplace\search::paginate($options);
        $this->assertSame([
            'pages' => [],
            'show_more' => false,
        ], $paginated);
    }

    public function test_sort() {
        $options = [
            ["label" => "a", "count" => 1, "checked" => false],
            ["label" => "b", "count" => 2, "checked" => false],
            ["label" => "c", "count" => 3, "checked" => false],
        ];
        $sorted = contentmarketplace\search::sort($options);
        $this->assertSame([
            ["label" => "c", "count" => 3, "checked" => false],
            ["label" => "b", "count" => 2, "checked" => false],
            ["label" => "a", "count" => 1, "checked" => false],
        ], $sorted);
    }

    public function test_sort_equal_count() {
        $options = [
            ["label" => "b", "count" => 1, "checked" => false],
            ["label" => "c", "count" => 1, "checked" => false],
            ["label" => "a", "count" => 1, "checked" => false],
        ];
        $sorted = contentmarketplace\search::sort($options);
        $this->assertSame([
            ["label" => "a", "count" => 1, "checked" => false],
            ["label" => "b", "count" => 1, "checked" => false],
            ["label" => "c", "count" => 1, "checked" => false],
        ], $sorted);
    }

    public function test_sort_checked() {
        $options = [
            ["label" => "a", "count" => 1, "checked" => true],
            ["label" => "b", "count" => 2, "checked" => false],
            ["label" => "c", "count" => 3, "checked" => false],
        ];
        $sorted = contentmarketplace\search::sort($options);
        $this->assertSame([
            ["label" => "a", "count" => 1, "checked" => true],
            ["label" => "c", "count" => 3, "checked" => false],
            ["label" => "b", "count" => 2, "checked" => false],
        ], $sorted);
    }

    public function test_sort_checked_equal() {
        $options = [
            ["label" => "a", "count" => 1, "checked" => true],
            ["label" => "b", "count" => 1, "checked" => false],
            ["label" => "c", "count" => 1, "checked" => true],
        ];
        $sorted = contentmarketplace\search::sort($options);
        $this->assertSame([
            ["label" => "a", "count" => 1, "checked" => true],
            ["label" => "c", "count" => 1, "checked" => true],
            ["label" => "b", "count" => 1, "checked" => false],
        ], $sorted);
    }
}
