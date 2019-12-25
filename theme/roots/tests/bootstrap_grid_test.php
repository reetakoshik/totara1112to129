<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @copyright 2016 onwards Totara Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Joby Harding <joby.harding@totaralearning.com>
 * @package   theme_roots
 */

defined('MOODLE_INTERNAL' || die());

use theme_roots\output\bootstrap_grid;

class theme_roots_bootstrap_grid_testcase extends basic_testcase {

    public function test_it_can_be_initialised() {

        $expected = 'theme_roots\output\bootstrap_grid';
        $actual = get_class(new bootstrap_grid());

        $this->assertEquals($expected, $actual);

    }

    public function test_it_returns_a_list_of_region_css_classes()  {

        //
        // Side-pre and side-post.
        //
        $expected =  array(
            'content' => 'col-sm-12 col-md-6 col-md-push-3',
            'pre' => 'col-sm-6 col-md-3 col-md-pull-6',
            'post' => 'col-sm-6 col-md-3',
        );
        $actual = (new bootstrap_grid())
            ->has_side_pre()
            ->has_side_post()
            ->get_regions_classes();

        $this->assertEquals($expected, $actual);

        //
        // Side-pre only.
        //
        $expected = array(
            'content' => 'col-sm-12 col-md-9 col-md-push-3',
            'pre' => 'col-sm-6 col-md-3 col-md-pull-9',
            'post' => 'empty',
        );
        $actual = (new bootstrap_grid())
            ->has_side_pre()
            ->get_regions_classes();

        $this->assertEquals($expected, $actual);

        //
        // Side-post only.
        //
        $expected = array(
            'content' => 'col-sm-12 col-md-9',
            'pre' => 'empty',
            'post' => 'col-sm-6 col-sm-offset-6 col-md-3 col-md-offset-0',
        );
        $actual = (new bootstrap_grid())
            ->has_side_post()
            ->get_regions_classes();

        $this->assertEquals($expected, $actual);

        //
        // No side regions.
        //
        $expected = array(
            'content' => 'col-md-12',
            'pre' => 'empty',
            'post' => 'empty',
        );
        $actual = (new bootstrap_grid())->get_regions_classes();

        $this->assertEquals($expected, $actual);
    }

}
