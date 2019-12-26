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
 * @package totara_catalog
 */

use totara_catalog\catalog_retrieval;
use totara_catalog\local\filter_handler;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_catalog
 */
class totara_catalog_category_filters_testcase extends advanced_testcase {
    public function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Data provider for test_category_filter().
     */
    public function td_category_filter(): array {
        return [
            [0], // panel filter index
            [1]  // browse filter index
        ];
    }

    /**
     * @dataProvider td_category_filter
     *
     * Currently, the categories filter only does equals matching for both
     * panel and browse filter. Which is why both can have a common test.
     *
     * @param int $filter_index index to pass to generate(), indicating which
     *        filter to return for the test.
     */
    public function test_category_filter(int $filter_index) {
        [$all_categories, $category_courses, $filter] = $this->generate($filter_index);
        $filter_type = $filter->key;

        $all_option = $all_categories[0];
        /** @var \totara_catalog\merge_select\tree $filter_selector */
        $filter_selector = $filter->selector;
        $filter_selector->add_all_option($all_option, $all_option);

        $filter_categories = $filter_selector->get_options();
        $this->assertGreaterThanOrEqual(
            count($all_categories),
            count($filter_categories),
            "$filter_type: wrong category count"
        );

        foreach ($filter_categories as $category) {
            $this->assertContains(
                (string)$category->name,
                $all_categories,
                "$filter_type: unknown category name"
            );
        }

        $all_courses = [];
        $catalog = new catalog_retrieval();
        /** @var \totara_catalog\datasearch\equal $filter_data */
        $filter_data = $filter->datafilter;
        foreach ($category_courses as $context_id => $courses) {
            $filter_data->set_current_data($context_id);
            $result = $catalog->get_page_of_objects(1000, 0);

            foreach ($result->objects as $retrieved) {
                $this->assertContains(
                    $retrieved->sorttext,
                    $courses,
                    "$filter_type: wrong courses for category"
                );
            }

            foreach ($courses as $course) {
                $all_courses[] = $course;
            }
        }

        // Test empty filter selection. This should disable the filter and thus
        // return all courses. Note all courses have categories; if they are not
        // explicitly assigned, then they fall the default category.
        $filter_data->set_current_data(null);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(count($all_courses), $result->objects, "wrong course counts");
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_courses, "wrong courses for empty category");
        }

        // Test filter with non existent category value.
        $filter_data->set_current_data(123);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(0, $result->objects, "unknown data retrieved");

        // Test filter with invalid category value.
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('it must be fixed by a programmer: equal filter only accepts null, int, string or bool data');
        $filter_data->set_current_data(array_keys($category_courses));
    }

    /**
     * Generates test data.
     *
     * @param int $filter_index indicates which category subfilter to return for
     *        the test.
     * @param int $category_count no of categories to generate.
     * @param int $course_count no of courses to generate.
     *
     * @return array (category names, mapping of categories to courses, filter]
     *         tuple.
     */
    private function generate(int $filter_index, int $category_count = 5, int $course_count = 20): array {
        global $DB;

        $generated_categories = [];
        for ($i = 0; $i < $category_count; $i++) {
            // The category filter works by filtering on the category context id,
            // not the category id.
            $category_id = $this->getDataGenerator()->create_category()->id;
            $context_id = context_coursecat::instance($category_id)->id;
            $generated_categories[] = [$context_id, $category_id];
        }

        // There are more categories in the system that the number generated
        // above; some are provided out of the box. Hence the need to retrieve
        // these rather than use the generated ones above.
        $all_option = "all categories";
        $all_categories = [$all_option];
        foreach ($DB->get_records('course_categories', null, '', 'id, name') as $record) {
            $all_categories[] = $record->name;
        }

        // Creating courses indirectly updates the catalog.
        $category_courses = [];
        for ($i = 0; $i < $course_count; $i++) {
            $j = $i % count($generated_categories);
            [$context_id, $category_id] = $generated_categories[$j];

            if (!array_key_exists($context_id, $category_courses)) {
                $category_courses[$context_id] = [];
            }

            $course = $this->getDataGenerator()->create_course(['category' => $category_id]);
            $category_courses[$context_id][] = $course->fullname;
        }

        // Filters were removed in setUp(); the line below indirectly creates a
        // category_filter and makes it the only available filter. Also the
        // category filter has 2 subfilters - one for panel, one for browse.
        $filters = filter_handler::instance()->get_category_filters();
        $this->assertCount(2, $filters, "wrong category filter count");

        return [$all_categories, $category_courses, $filters[$filter_index]];
    }
}
