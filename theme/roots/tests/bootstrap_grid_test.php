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

    /**
     * @param array $regions
     * @param bool|array $addregions
     * @throws ReflectionException
     * @return bootstrap_grid
     */
    private function get_grid($regions, $addregions = true) {
        if ($addregions === true) {
            $addregions = $regions;
        }

        $grid = new bootstrap_grid($regions, $addregions);
        return $grid;
    }

    public function test_it_returns_a_list_of_region_css_classes() {
        // All regions.
        $grid = $this->get_grid(['side-pre', 'side-post', 'top', 'bottom'], []);
        self::assertInstanceOf(bootstrap_grid::class, $grid);

        self::assertSame('col-sm-12', $grid->classes('top'));
        self::assertSame('col-sm-12', $grid->classes('bottom'));
        self::assertSame('col-sm-12 col-md-6 col-md-push-3', $grid->classes('content'));
        self::assertSame('col-sm-6 col-md-3 col-md-pull-6', $grid->classes('side-pre'));
        self::assertSame('col-sm-6 col-md-3', $grid->classes('side-post'));

        // Pre and top
        $grid = $this->get_grid(['side-pre', 'top'], []);
        self::assertInstanceOf(bootstrap_grid::class, $grid);

        self::assertSame('col-sm-12', $grid->classes('top'));
        self::assertSame('col-sm-12', $grid->classes('bottom'));
        self::assertSame('col-sm-12 col-md-9 col-md-push-3', $grid->classes('content'));
        self::assertSame('col-sm-6 col-md-3 col-md-pull-9', $grid->classes('side-pre'));
        self::assertSame('empty', $grid->classes('side-post'));

        // Post and bottom
        $grid = $this->get_grid(['side-post', 'bottom'], []);
        self::assertInstanceOf(bootstrap_grid::class, $grid);

        self::assertSame('col-sm-12', $grid->classes('top'));
        self::assertSame('col-sm-12', $grid->classes('bottom'));
        self::assertSame('col-sm-12 col-md-9', $grid->classes('content'));
        self::assertSame('empty', $grid->classes('side-pre'));
        self::assertSame('col-sm-6 col-sm-offset-6 col-md-3 col-md-offset-0', $grid->classes('side-post'));

        // No regions
        $grid = $this->get_grid([], []);
        self::assertInstanceOf(bootstrap_grid::class, $grid);

        self::assertSame('col-sm-12', $grid->classes('top'));
        self::assertSame('col-sm-12', $grid->classes('bottom'));
        self::assertSame('col-md-12', $grid->classes('content'));
        self::assertSame('empty', $grid->classes('side-pre'));
        self::assertSame('empty', $grid->classes('side-post'));
    }

    public function test_get_regions_classes_editing_mode() {
        // All regions.
        $grid = $this->get_grid(['side-pre', 'side-post', 'top', 'bottom']);
        self::assertInstanceOf(bootstrap_grid::class, $grid);

        self::assertSame('col-sm-12 editing-region-border', $grid->classes('top'));
        self::assertSame('col-sm-12 editing-region-border', $grid->classes('bottom'));
        self::assertSame('col-sm-12 col-md-6 col-md-push-3', $grid->classes('content'));
        self::assertSame('col-sm-6 col-md-3 col-md-pull-6 editing-region-border', $grid->classes('side-pre'));
        self::assertSame('col-sm-6 col-md-3 editing-region-border', $grid->classes('side-post'));

        // Pre and top
        $grid = $this->get_grid(['side-pre', 'top']);
        self::assertInstanceOf(bootstrap_grid::class, $grid);

        self::assertSame('col-sm-12 editing-region-border', $grid->classes('top'));
        self::assertSame('col-sm-12', $grid->classes('bottom'));
        self::assertSame('col-sm-12 col-md-9 col-md-push-3', $grid->classes('content'));
        self::assertSame('col-sm-6 col-md-3 col-md-pull-9 editing-region-border', $grid->classes('side-pre'));
        self::assertSame('empty', $grid->classes('side-post'));

        // Post and bottom
        $grid = $this->get_grid(['side-post', 'bottom']);
        self::assertInstanceOf(bootstrap_grid::class, $grid);

        self::assertSame('col-sm-12', $grid->classes('top'));
        self::assertSame('col-sm-12 editing-region-border', $grid->classes('bottom'));
        self::assertSame('col-sm-12 col-md-9', $grid->classes('content'));
        self::assertSame('empty', $grid->classes('side-pre'));
        self::assertSame('col-sm-6 col-sm-offset-6 col-md-3 col-md-offset-0 editing-region-border', $grid->classes('side-post'));

        // No regions
        $grid = $this->get_grid([]);
        self::assertInstanceOf(bootstrap_grid::class, $grid);

        self::assertSame('col-sm-12', $grid->classes('top'));
        self::assertSame('col-sm-12', $grid->classes('bottom'));
        self::assertSame('col-md-12', $grid->classes('content'));
        self::assertSame('empty', $grid->classes('side-pre'));
        self::assertSame('empty', $grid->classes('side-post'));
    }
}
