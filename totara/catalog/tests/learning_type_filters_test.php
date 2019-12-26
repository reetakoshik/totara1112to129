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
use totara_catalog\filter;
use totara_catalog\local\filter_handler;
use totara_catalog\merge_select\multi;
use totara_catalog\merge_select\single;
use totara_catalog\provider_handler;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_catalog
 */
class totara_catalog_learning_type_filters_testcase extends advanced_testcase {
    public function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Data provider for test_learning_type_filter().
     */
    public function td_learning_type_filter(): array {
        return [
            [0], // panel filter index
            [1]  // browse filter index
        ];
    }

    /**
     * Generates test courses, programs and certs.
     *
     * @param int $course_count no of courses to create.
     * @param int $program_count no of programs to create.
     * @param int $cert_count no of certs to create.
     *
     * @return array a (item names, mapping of learning type to items of that
     *         type) tuple. The learning type text mirrors the values returned
     *         by the various providers' get_object_type() methods.
     */
    private function generate_items(
        int $course_count,
        int $program_count,
        int $cert_count
    ): array {
        $all_items = [];

        $course_type = core_course\totara_catalog\course::get_object_type();
        $prog_type = totara_program\totara_catalog\program::get_object_type();
        $cert_type = totara_certification\totara_catalog\certification::get_object_type();
        $by_types = [
            $course_type => [],
            $prog_type => [],
            $cert_type => []
        ];

        $generator = $this->getDataGenerator();
        for ($i = 0; $i < $course_count; $i++) {
            $name = $generator->create_course()->fullname;
            $all_items[] = $name;
            $by_types[$course_type][] = $name;
        }

        /** @var totara_program_generator $program_generator */
        $program_generator = $generator->get_plugin_generator('totara_program');
        for ($i = 0; $i < $program_count; $i++) {
            $name = "test program name $i";
            $program_generator->create_program(['fullname' => $name]);

            $all_items[] = $name;
            $by_types[$prog_type][] = $name;
        }

        for ($i = 0; $i < $cert_count; $i++) {
            $name = "test cert name $i";
            $program_generator->create_certification(['fullname' => $name]);

            $all_items[] = $name;
            $by_types[$cert_type][] = $name;
        }

        return [$all_items, $by_types];
    }

    /**
     * Generates test data.
     *
     * @return array (type labels, mapping of types to items, type filters,
     *         all items) tuple.
     */
    private function generate() {
        [$all_items, $by_types] = $this->generate_items(10, 10, 10);

        $labels = [];
        foreach (provider_handler::instance()->get_active_providers() as $provider) {
            $labels[] = $provider::get_name();
        }

        // Filters were removed in setUp(); the line below indirectly loads the
        // type_filter among other course filters. All the filters are initially
        // inactive.
        $panel_filter = null;
        $browse_filter = null;

        foreach (filter_handler::instance()->get_all_filters() as $filter) {
            if ($filter->key === 'catalog_learning_type_panel') {
                $panel_filter = $filter;
            }

            if ($filter->key === 'catalog_learning_type_browse') {
                $browse_filter = $filter;
            }
        }

        $this->assertNotNull($panel_filter, "type panel filter not loaded");
        $this->assertNotNull($browse_filter, "type browse filter not loaded");
        $filters = [$panel_filter, $browse_filter];

        return [$labels, $by_types, $filters, $all_items];
    }

    public function test_learning_type_panel_filter() {
        [$labels, $by_types, $filters, $all_items] = $this->generate();

        /** @var filter $filter */
        $filter = $filters[0]; // Panel filter.
        /** @var multi $filter_selector */
        $filter_selector = $filter->selector;

        $filter_types = $filter_selector->get_options();
        $this->assertEquals(count($labels), count($filter_types), "wrong type count");
        foreach ($filter_types as $type) {
            $this->assertContains((string)$type, $labels, "unknown type label");
        }

        $catalog = new catalog_retrieval();
        $filter_data = $filter->datafilter;
        foreach ($by_types as $type => $items) {
            $filter_data->set_current_data([$type]); // This makes the filter active.
            $result = $catalog->get_page_of_objects(1000, 0);

            foreach ($result->objects as $retrieved) {
                $this->assertContains($retrieved->sorttext, $items, "wrong items for type");
            }
        }

        // Test multiple filter selection.
        $filter_data->set_current_data(array_keys($by_types));
        $result = $catalog->get_page_of_objects(1000, 0);
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_items, "wrong items for multi selected types");
        }

        // Test empty filter selection. This should disable the filter and thus
        // returns all courses.
        $filter_data->set_current_data(null);
        $result = $catalog->get_page_of_objects(1000, 0);
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_items, "wrong courses for empty type");
        }

        // Test filter with non existent type id.
        $filter_data->set_current_data([123]);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(0, $result->objects, "unknown data retrieved");

        // Test filter with invalid type value.
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('in or equal search filter only accepts null or array data of int, string or bool');
        $filter_data->set_current_data(123);
    }

    public function test_learning_type_browse_filter() {
        [$labels, $by_types, $filters, $all_items] = $this->generate();

        /** @var filter $filter */
        $filter = $filters[1]; // Browse filter.
        /** @var single $filter_selector */
        $filter_selector = $filter->selector;

        // Unlike the panel filter, the browse filter has an "all" option.
        $filter_types = array_slice($filter_selector->get_options(), 1);
        $this->assertEquals(count($labels), count($filter_types), "wrong type count");
        foreach ($filter_types as $type) {
            $this->assertContains((string)$type->name, $labels, "unknown type label");
        }

        $catalog = new catalog_retrieval();
        $filter_data = $filter->datafilter;
        foreach ($by_types as $type => $items) {
            // Unlike the panel filter, the browse filter expects a single value
            // for matching.
            $filter_data->set_current_data($type); // This makes the filter active.
            $result = $catalog->get_page_of_objects(1000, 0);

            foreach ($result->objects as $retrieved) {
                $this->assertContains($retrieved->sorttext, $items, "wrong items for type");
            }
        }

        // Test empty filter selection. This should disable the filter and thus
        // returns all courses.
        $filter_data->set_current_data(null);
        $result = $catalog->get_page_of_objects(1000, 0);
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_items, "wrong items for empty type");
        }

        // Test filter with non existent type id.
        $filter_data->set_current_data(123);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(0, $result->objects, "unknown data retrieved");

        // Test filter with invalid type value.
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('equal filter only accepts null, int, string or bool data');
        $filter_data->set_current_data(array_keys($all_items));
    }
}
