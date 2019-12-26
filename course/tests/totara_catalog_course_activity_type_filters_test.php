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

/**
 * @group totara_catalog
 */
class core_course_totara_catalog_course_activity_type_filters_testcase extends \advanced_testcase {

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Generates test data.
     *
     * @param int $course_count
     * @param int $activities_per_course
     * @return array (activity labels, mapping of activity module ids to courses,
     *         activity type filters, all courses] tuple.
     */
    private function generate($course_count = 15, $activities_per_course = 3): array {
        global $DB;
        $this->setAdminUser();

        $available_activities = [];
        $strings = get_string_manager();
        $modules = $DB->get_records('modules', ['visible' => 1], '', "id, name");
        $generator = $this->getDataGenerator();
        foreach ($modules as $module) {
            $label = $strings->string_exists('pluginname', $module->name)
                     ? $strings->get_string('pluginname', $module->name)
                     : ucfirst($module->name);

            if ($generator->module_exists($module->name)) {
                $available_activities[] = [$module->id, $module->name, $label];
            }
        }

        // Activities are randomly assigned to courses. Hence the activities the
        // catalog picks up != total no of activities available.
        $activity_courses = [];
        $activity_labels = [];
        $all_courses = [];

        for ($i = 0; $i < $course_count; $i++) {
            $course = $generator->create_course();

            for ($j = 0; $j < $activities_per_course; $j++) {
                $k = rand(1, count($available_activities));
                [$module_id, $module_name, $module_label] = $available_activities[$k - 1];
                $generator->create_module($module_name, ['course' => $course->id, ]);

                $courses = array_key_exists($module_id, $activity_courses)
                           ? $activity_courses[$module_id]
                           : [];

                if (!in_array($course->fullname, $courses)) {
                    $courses[] = $course->fullname;
                }
                $activity_courses[$module_id] = $courses;

                if (!in_array($module_label, $activity_labels)) {
                    $activity_labels[] = $module_label;
                }

                $all_courses[] = $course->fullname;
            }
        }

        // Create some courses with no activities. These should not be picked
        // during the filtering although the catalog will still know of them.
        for ($i = 0; $i < 10; $i++) {
            $all_courses[] = $generator->create_course()->fullname;
        }


        // Filters were removed in setUp(); the line below indirectly loads the
        // activity_type_filter among other course filters. All the filters are
        // initially inactive.
        $panel_filter = null;
        $browse_filter = null;
        $all_filters = provider_handler::instance()->get_provider('course')->get_filters();
        foreach ($all_filters as $filter) {
            if ($filter->key === 'course_acttyp_panel') {
                $panel_filter = $filter;
            }

            if ($filter->key === 'course_acttyp_browse') {
                $browse_filter = $filter;
            }
        }

        $this->assertNotNull($panel_filter, "activity type panel filter not loaded");
        $this->assertNotNull($browse_filter, "activity type browse filter not loaded");
        $filters = [$panel_filter, $browse_filter];

        return [$activity_labels, $activity_courses, $filters, $all_courses];
    }

    public function test_activity_type_panel_filter() {
        [$activity_labels, $activity_courses, $filters, $all_courses] = $this->generate();

        /** @var filter $filter */
        $filter = $filters[0]; // Panel filter.
        /** @var multi $filter_selector */
        $filter_selector = $filter->selector;

        $filter_activities = $filter_selector->get_options();
        $this->assertEquals(count($activity_labels), count($filter_activities), "wrong activity count");
        foreach ($filter_activities as $activity) {
            $this->assertContains((string)$activity, $activity_labels, "unknown activity label");
        }

        $catalog = new catalog_retrieval();
        $filter_data = $filter->datafilter;
        foreach ($activity_courses as $module_id => $courses) {
            $filter_data->set_current_data([$module_id]); // This makes the filter active.
            $result = $catalog->get_page_of_objects(1000, 0);

            foreach ($result->objects as $retrieved) {
                $this->assertContains($retrieved->sorttext, $courses, "wrong courses for activity");
            }
        }

        // Test multiple filter selection.
        $filter_data->set_current_data(array_keys($activity_courses));
        $result = $catalog->get_page_of_objects(1000, 0);
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_courses, "wrong courses for multi selected activities");
        }

        // Test empty filter selection. This should disable the filter and thus
        // returns all courses.
        $filter_data->set_current_data(null);
        $result = $catalog->get_page_of_objects(1000, 0);
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_courses, "wrong courses for empty activity");
        }

        // Test filter with non existent activity module id.
        $filter_data->set_current_data([123]);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(0, $result->objects, "unknown data retrieved");

        // Test filter with invalid module value.
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('in or equal search filter only accepts null or array data of int, string or bool');
        $filter_data->set_current_data(123);
    }

    public function test_activity_type_browse_filter() {
        [$activity_labels, $activity_courses, $filters, $all_courses] = $this->generate();

        /** @var filter $filter */
        $filter = $filters[1]; // Browse filter.
        /** @var single $filter_selector */
        $filter_selector = $filter->selector;

        // Unlike the panel filter, the browse filter has an "all" option.
        $filter_activities = array_slice($filter_selector->get_options(), 1);
        $this->assertEquals(count($activity_labels), count($filter_activities), "wrong activity count");
        foreach ($filter_activities as $activity) {
            $this->assertContains((string)$activity->name, $activity_labels, "unknown activity label");
        };

        $catalog = new catalog_retrieval();
        $filter_data = $filter->datafilter;
        foreach ($activity_courses as $module_id => $courses) {
            // Unlike the panel filter, the browse filter expects a single value
            // for matching.
            $filter_data->set_current_data($module_id); // This makes the filter active.
            $result = $catalog->get_page_of_objects(1000, 0);

            foreach ($result->objects as $retrieved) {
                $this->assertContains($retrieved->sorttext, $courses, "wrong courses for activity");
            }
        }

        // Test empty filter selection. This should disable the filter and thus
        // returns all courses.
        $filter_data->set_current_data(null);
        $result = $catalog->get_page_of_objects(1000, 0);
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_courses, "wrong courses for empty activity");
        }

        // Test filter with non existent activity module id.
        $filter_data->set_current_data(123);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(0, $result->objects, "unknown data retrieved");

        // Test filter with invalid module value.
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('equal filter only accepts null, int, string or bool data');
        $filter_data->set_current_data(array_keys($activity_courses));
    }
}
