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
class core_course_totara_catalog_course_type_filters_testcase extends \advanced_testcase {

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Generates test data.
     *
     * @param int $course_count
     * @return array (type labels, mapping of types to courses, type filters,
     *         all courses) tuple.
     */
    private function generate($course_count = 15): array {
        global $TOTARA_COURSE_TYPES;
        $this->setAdminUser();

        $available_types = [];
        $type_labels = [];
        $strings = get_string_manager();
        foreach ($TOTARA_COURSE_TYPES as $type => $type_id) {
            $label = $strings->get_string($type, 'totara_core');
            $available_types[] = $type_id;
            $type_labels[] = $label;
        }

        // Course types are randomly assigned to courses.
        $type_courses = [];
        $all_courses = [];

        $generator = $this->getDataGenerator();
        for ($i = 0; $i < $course_count; $i++) {
            $j = rand(1, count($available_types));
            $type_id = $available_types[$j - 1];

            $course = $generator->create_course(['coursetype' => $type_id]);
            $all_courses[] = $course->fullname;

            $courses = array_key_exists($type_id, $type_courses)
                       ? $type_courses[$type_id]
                       : [];
            $courses[] = $course->fullname;
            $type_courses[$type_id] = $courses;
        }


        // Filters were removed in setUp(); the line below indirectly loads the
        // type_filter among other course filters. All the filters are initially
        // inactive.
        $panel_filter = null;
        $browse_filter = null;
        $all_filters = provider_handler::instance()->get_provider('course')->get_filters();
        foreach ($all_filters as $filter) {
            if ($filter->key === 'course_type_panel') {
                $panel_filter = $filter;
            }

            if ($filter->key === 'course_type_browse') {
                $browse_filter = $filter;
            }
        }

        $this->assertNotNull($panel_filter, "type panel filter not loaded");
        $this->assertNotNull($browse_filter, "type browse filter not loaded");
        $filters = [$panel_filter, $browse_filter];

        return [$type_labels, $type_courses, $filters, $all_courses];
    }

    public function test_type_panel_filter() {
        [$type_labels, $type_courses, $filters, $all_courses] = $this->generate();

        /** @var filter $filter */
        $filter = $filters[0]; // Panel filter.
        /** @var multi $filter_selector */
        $filter_selector = $filter->selector;

        $filter_types = $filter_selector->get_options();
        $this->assertEquals(count($type_labels), count($filter_types), "wrong type count");
        foreach ($filter_types as $type) {
            $this->assertContains((string)$type, $type_labels, "unknown type label");
        }

        $catalog = new catalog_retrieval();
        $filter_data = $filter->datafilter;
        foreach ($type_courses as $type => $courses) {
            $filter_data->set_current_data([$type]); // This makes the filter active.
            $result = $catalog->get_page_of_objects(1000, 0);

            foreach ($result->objects as $retrieved) {
                $this->assertContains($retrieved->sorttext, $courses, "wrong courses for type");
            }
        }

        // Test multiple filter selection.
        $filter_data->set_current_data(array_keys($type_courses));
        $result = $catalog->get_page_of_objects(1000, 0);
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_courses, "wrong courses for multi selected types");
        }

        // Test empty filter selection. This should disable the filter and thus
        // returns all courses.
        $filter_data->set_current_data(null);
        $result = $catalog->get_page_of_objects(1000, 0);
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_courses, "wrong courses for empty type");
        }

        // Test filter with non existent type id.
        $filter_data->set_current_data([123]);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(0, $result->objects, "unknown data retrieved");

        // Test filter with invalid type value.
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('in or equal search filter only accepts null or array data of int, string or bool');
        $filter_data->set_current_data(123);
    }

    public function test_type_browse_filter() {
        [$type_labels, $type_courses, $filters, $all_courses] = $this->generate();

        /** @var filter $filter */
        $filter = $filters[1]; // Browse filter.
        /** @var single $filter_selector */
        $filter_selector = $filter->selector;

        // Unlike the panel filter, the browse filter has an "all" option.
        $filter_types = array_slice($filter_selector->get_options(), 1);
        $this->assertEquals(count($type_labels), count($filter_types), "wrong type count");
        foreach ($filter_types as $type) {
            $this->assertContains((string)$type->name, $type_labels, "unknown type label");
        };

        $catalog = new catalog_retrieval();
        $filter_data = $filter->datafilter;
        foreach ($type_courses as $type => $courses) {
            // Unlike the panel filter, the browse filter expects a single value
            // for matching.
            $filter_data->set_current_data($type); // This makes the filter active.
            $result = $catalog->get_page_of_objects(1000, 0);

            foreach ($result->objects as $retrieved) {
                $this->assertContains($retrieved->sorttext, $courses, "wrong courses for type");
            }
        }

        // Test empty filter selection. This should disable the filter and thus
        // returns all courses.
        $filter_data->set_current_data(null);
        $result = $catalog->get_page_of_objects(1000, 0);
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_courses, "wrong courses for empty type");
        }

        // Test filter with non existent type id.
        $filter_data->set_current_data(123);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(0, $result->objects, "unknown data retrieved");

        // Test filter with invalid type value.
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('equal filter only accepts null, int, string or bool data');
        $filter_data->set_current_data(array_keys($type_courses));
    }
}
