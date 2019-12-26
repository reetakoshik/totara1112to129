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

class select_search_text_test extends advanced_testcase {

    public function test_create() {

        // Test with no currrent value and no title and show placeholder.

        $searchtext1 = \totara_core\output\select_search_text::create(
            'testsearchtext1',
            'Test full text search 1',
            true,
            null,
            true
        );

        $expected1 = [
            'key' => 'testsearchtext1',
            'title' => 'Test full text search 1',
            'title_hidden' => true,
            'current_val' => null,
            'placeholder_show' => true,
            'has_hint_icon' => false,
        ];

        $actual1 = $searchtext1->get_template_data();

        $this->assertEquals($expected1, $actual1);

        // Test with currrent value and title.

        $searchtext2 = \totara_core\output\select_search_text::create(
            'testsearchtext2',
            'Test full text search 2',
            false,
            'asdf'
        );

        $expected2 = [
            'key' => 'testsearchtext2',
            'title' => 'Test full text search 2',
            'title_hidden' => false,
            'current_val' => 'asdf',
            'placeholder_show' => true,
            'has_hint_icon' => false,
        ];

        $actual2 = $searchtext2->get_template_data();

        $this->assertEquals($expected2, $actual2);
    }
}