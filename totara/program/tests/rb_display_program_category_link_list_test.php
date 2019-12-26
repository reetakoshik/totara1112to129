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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_program
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

/**
 * @group totara_program
 */
class totara_program_rb_display_program_category_link_list_testcase extends advanced_testcase {

    /**
     * Test resorting when there are no courses.
     */
    public function test_resort_no_courses() {

        $resort = new ReflectionMethod(\totara_program\rb\display\program_category_link_list::class, 'resort');
        $resort->setAccessible(true);
        $resort->invoke(null, -1, [], []);

    }

    /**
     * Test resorting a single course.
     */
    public function test_resort_one_courses() {

        $resort = new ReflectionMethod(\totara_program\rb\display\program_category_link_list::class, 'resort');
        $resort->setAccessible(true);
        $data = [-2 => '<a href="-2">Apples</a>'];
        self::assertSame($data, $resort->invoke(null, -1, $data, $data));

    }

    /**
     * Test resorting multiple courses
     */
    public function test_resort_many_courses() {

        $resort = new ReflectionMethod(\totara_program\rb\display\program_category_link_list::class, 'resort');
        $resort->setAccessible(true);

        $cache = \cache::make('totara_program', 'course_order');
        $cache->set(-1, [-2, -6, -9]);

        $data = [
            -2 => '<a href="-2">Apples</a>',
            -6 => '<a href="-2">Apples</a>',
            -9 => '<a href="-6">Bananas</a>',
        ];
        $expected = [
            '<a href="-2">Apples</a>',
            '<a href="-2">Apples</a>',
            '<a href="-6">Bananas</a>',
        ];
        self::assertSame($expected, $resort->invoke(null, -1, array_values($data), array_keys($data)));

        $cache->set(-1, [-6, -2, -9]);
        $data = [
            -2 => '<a href="-2">Apples</a>',
            -6 => '<a href="-2">Apples</a>',
            -9 => '<a href="-6">Bananas</a>',
        ];
        $expected = [
            '<a href="-2">Apples</a>',
            '<a href="-2">Apples</a>',
            '<a href="-6">Bananas</a>',
        ];
        self::assertSame($expected, $resort->invoke(null, -1, array_values($data), array_keys($data)));

        $cache->set(-1, [-6, -9, -2]);
        $data = [
            -2 => '<a href="-2">Apples</a>',
            -6 => '<a href="-2">Apples</a>',
            -9 => '<a href="-6">Bananas</a>',
        ];
        $expected = [
            '<a href="-2">Apples</a>',
            '<a href="-6">Bananas</a>',
            '<a href="-2">Apples</a>',
        ];
        self::assertSame($expected, $resort->invoke(null, -1, array_values($data), array_keys($data)));

    }

    /**
     * Tests the outcome when the cache is aware of more courses then are actually present in the results.
     */
    public function test_resort_additional_courses() {
        $resort = new ReflectionMethod(\totara_program\rb\display\program_category_link_list::class, 'resort');
        $resort->setAccessible(true);

        $cache = \cache::make('totara_program', 'course_order');
        $cache->set(-1, [-2, -6, -9, -11]);

        $data = [
            -2 => '<a href="-2">Apples</a>',
            -9 => '<a href="-6">Bananas</a>',
        ];
        $expected = [
            '<a href="-2">Apples</a>',
            '<a href="-6">Bananas</a>',
        ];
        self::assertSame($expected, $resort->invoke(null, -1, array_values($data), array_keys($data)));
    }

    /**
     * Tests the outcome when the cache is aware of less courses then are actually present in the results.
     */
    public function test_resort_missing_courses() {
        $resort = new ReflectionMethod(\totara_program\rb\display\program_category_link_list::class, 'resort');
        $resort->setAccessible(true);

        $cache = \cache::make('totara_program', 'course_order');
        $cache->set(-1, [-2, -9]);

        $data = [
            -2 => '<a href="-2">Apples</a>',
            -6 => '<a href="-2">Apples</a>',
            -9 => '<a href="-6">Bananas</a>',
            -11 => '<a href="-11">Donuts</a>',
        ];
        $expected = [
            '<a href="-2">Apples</a>',
            '<a href="-6">Bananas</a>',
            '<a href="-2">Apples</a>',
            '<a href="-11">Donuts</a>',
        ];
        self::assertSame($expected, $resort->invoke(null, -1, array_values($data), array_keys($data)));
        self::assertDebuggingCalled('Expected program courses count does not match cached course count, try purging your caches.');
    }

    /**
     * Tests the outcome when the courses do not match any of the cached courses.
     */
    public function test_resort_mismatch_courses() {
        $resort = new ReflectionMethod(\totara_program\rb\display\program_category_link_list::class, 'resort');
        $resort->setAccessible(true);

        $cache = \cache::make('totara_program', 'course_order');
        $cache->set(-1, [-2, -9]);

        $data = [
            -6 => '<a href="-2">Apples</a>',
            -11 => '<a href="-11">Donuts</a>',
        ];
        $expected = [
            '<a href="-2">Apples</a>',
            '<a href="-11">Donuts</a>',
        ];
        self::assertSame($expected, $resort->invoke(null, -1, array_values($data), array_keys($data)));
        self::assertDebuggingCalled('Expected program courses count does not match cached course count, try purging your caches.');
    }

}