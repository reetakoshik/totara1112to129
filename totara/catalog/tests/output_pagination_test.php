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

use totara_catalog\output\pagination;

defined('MOODLE_INTERNAL') || die();

/**
 * Class output_pagination_test
 *
 * Tests for catalog pagination template class.
 *
 * @package totara_catalog
 * @group totara_catalog
 */
class totara_catalog_output_pagination_testcase extends advanced_testcase {

    public function create_data_provider() {
        return [
            [
                [0, -1, true],
                [
                    'limit_from' => 0,
                    'max_count' => -1,
                    'end_of_results' => true
                ],
            ],
            [
                [20, 10, false],
                [
                    'limit_from' => 20,
                    'max_count' => 10,
                    'end_of_results' => false
                ],
            ],
        ];
    }

    /**
     * @dataProvider create_data_provider
     * @param array $params
     * @param $expected
     */
    public function test_create(array $params, $expected) {
        $actual = pagination::create(...$params)->get_template_data();
        $this->assertSame($expected, $actual);
    }
}
