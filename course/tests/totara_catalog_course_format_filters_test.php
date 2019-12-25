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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package core_course
 * @category totara_catalog
 */

namespace core_course\totara_catalog\course;

use totara_catalog\catalog_retrieval;
use totara_catalog\filter;
use totara_catalog\merge_select\multi;
use totara_catalog\merge_select\single;
use totara_catalog\provider_handler;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("{$CFG->dirroot}/course/lib.php");

/**
 * @group totara_catalog
 */
class core_course_totara_catalog_course_format_filters_testcase extends \advanced_testcase {

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Generates test data.
     *
     * @param int $course_count
     * @return array (format labels, mapping of formats to courses, format filters,
     *         all courses) tuple.
     */
    private function generate($course_count = 15): array {
        $this->setAdminUser();

        $format_labels = [];
        $strings = get_string_manager();
        $available_formats = get_sorted_course_formats(true);
        foreach ($available_formats as $format) {
            $format_labels[] = $strings->get_string('pluginname', "format_$format");
        }

        // Formats are randomly assigned to courses.
        $format_courses = [];
        $all_courses = [];

        $generator = $this->getDataGenerator();
        for ($i = 0; $i < $course_count; $i++) {
            $j = rand(1, count($available_formats));
            $format = $available_formats[$j - 1];

            $course = $generator->create_course(['format' => $format]);
            $all_courses[] = $course->fullname;

            $courses = array_key_exists($format, $format_courses)
                       ? $format_courses[$format]
                       : [];
            $courses[] = $course->fullname;
            $format_courses[$format] = $courses;
        }


        // Filters were removed in setUp(); the line below indirectly loads the
        // format_filter among other course filters. All the filters are initially
        // inactive.
        $panel_filter = null;
        $browse_filter = null;
        $all_filters = provider_handler::instance()->get_provider('course')->get_filters();
        foreach ($all_filters as $filter) {
            if ($filter->key === 'course_format_multi') {
                $panel_filter = $filter;
            }

            if ($filter->key === 'course_format_tree') {
                $browse_filter = $filter;
            }
        }

        $this->assertNotNull($panel_filter, "format panel filter not loaded");
        $this->assertNotNull($browse_filter, "format browse filter not loaded");
        $filters = [$panel_filter, $browse_filter];

        return [$format_labels, $format_courses, $filters, $all_courses];
    }

    public function test_format_panel_filter() {
        [$format_labels, $format_courses, $filters, $all_courses] = $this->generate();

        /** @var filter $filter */
        $filter = $filters[0]; // Panel filter.
        /** @var multi $filter_selector */
        $filter_selector = $filter->selector;

        $filter_formats = $filter_selector->get_options();
        $this->assertEquals(count($format_labels), count($filter_formats), "wrong format count");
        foreach ($filter_formats as $format) {
            $this->assertContains((string)$format, $format_labels, "unknown format label");
        }

        $catalog = new catalog_retrieval();
        $filter_data = $filter->datafilter;
        foreach ($format_courses as $format => $courses) {
            $filter_data->set_current_data([$format]); // This makes the filter active.
            $result = $catalog->get_page_of_objects(1000, 0);

            foreach ($result->objects as $retrieved) {
                $this->assertContains($retrieved->sorttext, $courses, "wrong courses for format");
            }
        }

        // Test multiple filter selection.
        $filter_data->set_current_data(array_keys($format_courses));
        $result = $catalog->get_page_of_objects(1000, 0);
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_courses, "wrong courses for multi selected formats");
        }

        // Test empty filter selection. This should disable the filter and thus
        // returns all courses.
        $filter_data->set_current_data(null);
        $result = $catalog->get_page_of_objects(1000, 0);
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_courses, "wrong courses for empty format");
        }

        // Test filter with non existent format format.
        $filter_data->set_current_data(['unknown format']);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(0, $result->objects, "unknown data retrieved");

        // Test filter with invalid format value.
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('in or equal search filter only accepts null or array data of int, string or bool');
        $filter_data->set_current_data(123);
    }

    public function test_format_browse_filter() {
        [$format_labels, $format_courses, $filters, $all_courses] = $this->generate();

        /** @var filter $filter */
        $filter = $filters[1]; // Browse filter.
        /** @var single $filter_selector */
        $filter_selector = $filter->selector;

        // Unlike the panel filter, the browse filter has an "all" option.
        $filter_formats = array_slice($filter_selector->get_options(), 1);
        $this->assertEquals(count($format_labels), count($filter_formats), "wrong format count");
        foreach ($filter_formats as $format) {
            $this->assertContains((string)$format->name, $format_labels, "unknown format label");
        };

        $catalog = new catalog_retrieval();
        $filter_data = $filter->datafilter;
        foreach ($format_courses as $format => $courses) {
            // Unlike the panel filter, the browse filter expects a single value
            // for matching.
            $filter_data->set_current_data($format); // This makes the filter active.
            $result = $catalog->get_page_of_objects(1000, 0);

            foreach ($result->objects as $retrieved) {
                $this->assertContains($retrieved->sorttext, $courses, "wrong courses for format");
            }
        }

        // Test empty filter selection. This should disable the filter and thus
        // returns all courses.
        $filter_data->set_current_data(null);
        $result = $catalog->get_page_of_objects(1000, 0);
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_courses, "wrong courses for empty format");
        }

        // Test filter with non existent format.
        $filter_data->set_current_data('unknown format');
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(0, $result->objects, "unknown data retrieved");

        // Test filter with invalid module value.
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('equal filter only accepts null, int, string or bool data');
        $filter_data->set_current_data(array_keys($format_courses));
    }
}
