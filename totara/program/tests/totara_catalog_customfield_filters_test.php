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
 * @package totara_program
 * @category totara_catalog
 */

namespace totara_program\totara_catalog\program;

use totara_catalog\catalog_retrieval;
use totara_catalog\filter;
use totara_catalog\local\filter_handler;
use totara_catalog\merge_select\multi;
use totara_catalog\merge_select\single;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_catalog
 */
class totara_program_totara_catalog_customfield_filters_testcase extends \advanced_testcase {

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Generates test custom field *definitions*.
     *
     * @param array $data metadata to use when creating custom field.
     *
     * @return int the generated definition id.
     */
    private function generate_customfield(
        array $data
    ): int {
        // The totara_customfield generator can create custom fields but does not
        // allow the setting of its default values. Then there is the totara_core
        // generator that can create custom fields with default data but cannot
        // assign custom fields to programs. Incredible.
        /** @var \totara_core_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_core');
        return $generator->create_custom_program_field($data)->id;
    }

    /**
     * Generates test programs.
     *
     * @param int $program_count no of programs to create.
     *
     * @return \stdClass[] programs.
     */
    private function generate_programs(int $program_count): array {
        $all_programs = [];

        /** @var \totara_program_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        for ($i = 0; $i < $program_count; $i++) {
            $all_programs[] = $generator->create_program(
                [
                    'fullname' => "test program name $i"
                ]
            );
        }

        return $all_programs;
    }

    /**
     * Returns the customfield filters to use for testing.
     *
     * @param string $cf_name custom field name.
     * @param string $cf_type custom field type eg 'menu'.
     *
     * @return filter[] (panel filter, browse filter) tuple.
     */
    private function get_filters(string $cf_name, string $cf_type): array {
        // Filters were removed in setUp(); the line below indirectly loads the
        // custom field filter among other filters
        $panel_filter = null;
        $browse_filter = null;

        $suffix = sprintf(
            "%s_%s",
            $cf_type,
            catalog_retrieval::get_safe_table_alias($cf_name . '_' . $cf_name)
        );

        foreach (filter_handler::instance()->get_all_filters() as $filter) {
            if ($filter->key === "cfp_$suffix") {
                $panel_filter = $filter;
            }

            if ($filter->key === "cfb_$suffix") {
                $browse_filter = $filter;
            }
        }

        $this->assertNotNull($panel_filter, "program customfield panel filter not loaded");
        $this->assertNotNull($browse_filter, "program customfield browse filter not loaded");
        return [$panel_filter, $browse_filter];
    }

    /**
     * Generates test data using custom menu fields.
     *
     * @param array $options menu options; the 1st entry is the default value.
     * @param int $program_count no of programs to create.
     *
     * @return array (custom field options, program names, programs by customfield,
     *         filters) tuple.
     */
    private function generate_menu(
        int $program_count = 25,
        array $options = ['aaa', 'bbb', 'ccc', 'ddd', 'eee']
    ): array {
        $cf_type = 'menu';
        $cf_id = $this->generate_customfield(
            [
                'datatype' => $cf_type,
                'fullname' => $cf_type,
                'shortname' => $cf_type,
                'defaultdata' => $options[0],
                'param1' => $options
            ]
        );

        $programs_by_cf = [];
        $program_names = [];
        /** @var \totara_customfield_generator $cf_generator */
        $cf_generator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');

        foreach ($this->generate_programs($program_count) as $i => $program) {
            $program_names[] = $program->fullname;

            // The way custom fields work, a program still has a "default" value
            // for a custom field even if the custom field was not explicitly
            // assigned to it. Hence the "incomplete" assigning below.
            $option = $options[0];
            if ($i % 3 !== 0) {
                $j = rand(1, count($options));
                $option = $options[$j - 1];
                $cf_generator->set_menu($program, $cf_id, $j - 1, 'program', 'prog');
            }

            $assigned_programs = array_key_exists($option, $programs_by_cf)
                                ? $programs_by_cf[$option]
                                : [];

            if (!in_array($program->fullname, $assigned_programs)) {
                $assigned_programs[] = $program->fullname;
            }
            $programs_by_cf[$option] = $assigned_programs;
        };

        $filters = $this->get_filters($cf_type, $cf_type);

        return [$options, $program_names, $programs_by_cf, $filters];
    }

    public function test_menu_cf_panel_filter() {
        [$options, $all_programs, $programs_by_cf, $filters] = $this->generate_menu();

        /** @var filter $filter */
        $filter = $filters[0]; // Panel filter.
        /** @var multi $filter_selector */
        $filter_selector = $filter->selector;

        // Test that display options show only menu custom field options.
        $filter_options = $filter_selector->get_options();
        $this->assertCount(count($options), $filter_options, "wrong menu cf options count");
        foreach ($filter_options as $option) {
            $this->assertContains((string)$option, $options, "unknown menu cf label");
        }

        // Test filtering by a single, specific menu option.
        $catalog = new catalog_retrieval();
        $filter_data = $filter->datafilter;
        foreach ($programs_by_cf as $option => $programs) {
            $filter_data->set_current_data([$option]);
            $result = $catalog->get_page_of_objects(1000, 0);

            $this->assertCount(count($programs), $result->objects, "wrong program count");
            foreach ($result->objects as $retrieved) {
                $this->assertContains($retrieved->sorttext, $programs, "wrong programs for option");
            }
        }

        // Test multiple filter selection.
        $filter_data->set_current_data(array_keys($programs_by_cf));
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(count($all_programs), $result->objects, "wrong program count");
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_programs, "wrong programs for multi selected options");
        }

        // Test empty filter selection. This should disable the filter but since
        // all programs have custom fields (some with default values), everything
        // should be picked up.
        $filter_data->set_current_data(null);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(count($all_programs), $result->objects, "wrong program count");
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_programs, "wrong programs for empty option");
        }

        // Test filter with non existent option.
        $filter_data->set_current_data(['does not exist']);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(0, $result->objects, "unknown data retrieved");

        // Test filter with invalid option value.
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('in or equal search filter only accepts null or array data of int, string or bool');
        $filter_data->set_current_data(123);
    }

    public function test_menu_cf_browse_filter() {
        [$options, $all_programs, $programs_by_cf, $filters] = $this->generate_menu();

        /** @var filter $filter */
        $filter = $filters[1]; // Browse filter.
        /** @var single $filter_selector */
        $filter_selector = $filter->selector;

        // Test that display options show only those options that are attached to a
        // program. Also, unlike the panel filter, the browse filter has an "all"
        // option.
        $filter_options = array_slice($filter_selector->get_options(), 1);
        $this->assertCount(count($options), $filter_options, "wrong menu cf options count");
        foreach ($filter_options as $option) {
            $this->assertContains((string)$option->name, $options, "unknown menu cf label");
        }

        // Test filtering by a single, specific menu option.
        $catalog = new catalog_retrieval();
        $filter_data = $filter->datafilter;
        foreach ($programs_by_cf as $option => $programs) {
            // Unlike the panel filter, the browse filter expects a single value
            // for matching.
            $filter_data->set_current_data($option);
            $result = $catalog->get_page_of_objects(1000, 0);

            $this->assertCount(count($programs), $result->objects, "wrong program count");
            foreach ($result->objects as $retrieved) {
                $this->assertContains($retrieved->sorttext, $programs, "wrong programs for option");
            }
        }

        // Test empty filter selection. This should disable the filter but since
        // all programs have custom fields (some with default values), everything
        // should be picked up.
        $filter_data->set_current_data(null);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(count($all_programs), $result->objects, "wrong program count");
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_programs, "wrong programs for empty option");
        }

        // Test filter with non existent option.
        $filter_data->set_current_data(123);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(0, $result->objects, "unknown data retrieved");

        // Test filter with invalid option value.
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('equal filter only accepts null, int, string or bool data');
        $filter_data->set_current_data(array_keys($programs_by_cf));
    }

    /**
     * Generates test data using custom multiselect fields.
     *
     * @param array $options menu options; the 1st entry is the default value.
     * @param int $program_count no of programs to create.
     *
     * @return array (custom field options, program names, programs by customfield,
     *         filters) tuple.
     */
    private function generate_multi(
        int $program_count = 25,
        array $options = ['aaa', 'bbb', 'ccc', 'ddd', 'eee']
    ): array {
        $multi_options = [];
        foreach ($options as $i => $option) {
            $multi_options[] = [
                'option' => $option,
                'icon' => '',
                'default' => $i === 0,
                'delete' => 0
            ];
        };

        $cf_type = 'multiselect';
        $cf_id = $this->generate_customfield(
            [
                'datatype' => $cf_type,
                'fullname' => $cf_type,
                'shortname' => $cf_type,
                'param1' => $multi_options
            ]
        );

        $programs_by_cf = [];
        $program_names = [];
        /** @var \totara_customfield_generator $cf_generator */
        $cf_generator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');

        foreach ($this->generate_programs($program_count) as $i => $program) {
            $program_names[] = $program->fullname;

            // The way custom fields work, a program still has a "default" value
            // for a custom field even if the custom field was not explicitly
            // assigned to it. Hence the "incomplete" assigning below.
            $option = $options[0];
            if ($i % 3 !== 0) {
                $j = rand(1, count($options));
                $option = $options[$j - 1];
                $cf_generator->set_multiselect($program, $cf_id, [$option], 'program', 'prog');
            }

            $assigned_programs = array_key_exists($option, $programs_by_cf)
                                ? $programs_by_cf[$option]
                                : [];

            if (!in_array($program->fullname, $assigned_programs)) {
                $assigned_programs[] = $program->fullname;
            }
            $programs_by_cf[$option] = $assigned_programs;
        };

        $filters = $this->get_filters($cf_type, $cf_type);

        return [$options, $program_names, $programs_by_cf, $filters];
    }

    public function test_multi_cf_panel_filter() {
        [$options, $all_programs, $programs_by_cf, $filters] = $this->generate_multi();

        /** @var filter $filter */
        $filter = $filters[0]; // Panel filter.
        /** @var multi $filter_selector */
        $filter_selector = $filter->selector;

        // Test that display options show only multi custom field options.
        $filter_options = $filter_selector->get_options();
        $this->assertCount(count($options), $filter_options, "wrong multi cf options count");
        foreach ($filter_options as $option) {
            $this->assertContains((string)$option, $options, "unknown multi cf label");
        }

        // Test filtering by a single, specific multi option.
        $catalog = new catalog_retrieval();
        $filter_data = $filter->datafilter;
        foreach ($programs_by_cf as $option => $programs) {
            $filter_data->set_current_data([$option]);
            $result = $catalog->get_page_of_objects(1000, 0);

            $this->assertCount(count($programs), $result->objects, "wrong program count");
            foreach ($result->objects as $retrieved) {
                $this->assertContains($retrieved->sorttext, $programs, "wrong programs for option");
            }
        }

        // Test multiple filter selection.
        $filter_data->set_current_data(array_keys($programs_by_cf));
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(count($all_programs), $result->objects, "wrong program count");
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_programs, "wrong programs for multi selected options");
        }

        // Test empty filter selection. This should disable the filter but since
        // all programs have custom fields (some with default values), everything
        // should be picked up.
        $filter_data->set_current_data(null);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(count($all_programs), $result->objects, "wrong program count");
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_programs, "wrong programs for empty option");
        }

        // Test filter with non existent option.
        $filter_data->set_current_data(['does not exist']);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(0, $result->objects, "unknown data retrieved");

        // Test filter with invalid option value.
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('like or search filter only accepts null or array data');
        $filter_data->set_current_data(123);
    }

    public function test_multi_cf_browse_filter() {
        [$options, $all_programs, $programs_by_cf, $filters] = $this->generate_multi();

        /** @var filter $filter */
        $filter = $filters[1]; // Browse filter.
        /** @var single $filter_selector */
        $filter_selector = $filter->selector;

        // Test that display options show only those options that are attached to a
        // program. Also, unlike the panel filter, the browse filter has an "all"
        // option.
        $filter_options = array_slice($filter_selector->get_options(), 1);
        $this->assertCount(count($options), $filter_options, "wrong multi cf options count");
        foreach ($filter_options as $option) {
            $this->assertContains((string)$option->name, $options, "unknown multi cf label");
        }

        // Test filtering by a single, specific multi option.
        $catalog = new catalog_retrieval();
        $filter_data = $filter->datafilter;
        foreach ($programs_by_cf as $option => $programs) {
            // Unlike the panel filter, the browse filter expects a single value
            // for matching.
            $filter_data->set_current_data($option);
            $result = $catalog->get_page_of_objects(1000, 0);

            $this->assertCount(count($programs), $result->objects, "wrong program count");
            foreach ($result->objects as $retrieved) {
                $this->assertContains($retrieved->sorttext, $programs, "wrong programs for option");
            }
        }

        // Test empty filter selection. This should disable the filter but since
        // all programs have custom fields (some with default values), everything
        // should be picked up.
        $filter_data->set_current_data(null);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(count($all_programs), $result->objects, "wrong program count");
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_programs, "wrong programs for empty option");
        }

        // Test filter with non existent option.
        $filter_data->set_current_data(123);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(0, $result->objects, "unknown data retrieved");

        // Test filter with invalid option value.
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('like filter only accepts null, int, string or bool data');
        $filter_data->set_current_data(array_keys($programs_by_cf));
    }

    /**
     * Generates test data using custom checkbox fields. The default value for
     * checkbox custom fields is TRUE.
     *
     * @param int $program_count no of programs to create.
     *
     * @return array (options, program names, programs by customfield, filters) tuple.
     */
    private function generate_checkbox(
        int $program_count = 3
    ): array {
        $options = [1 => 'Yes', 0 => 'No'];
        $cf_type = 'checkbox';
        $cf_id = $this->generate_customfield(
            [
                'datatype' => $cf_type,
                'fullname' => $cf_type,
                'shortname' => $cf_type,
                'defaultdata' => 1
            ]
        );

        $programs_by_cf = [];
        $program_names = [];
        /** @var \totara_customfield_generator $cf_generator */
        $cf_generator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');

        foreach ($this->generate_programs($program_count) as $i => $program) {
            $program_names[] = $program->fullname;

            // The way custom fields work, a program still has a "default" value
            // for a custom field even if the custom field was not explicitly
            // assigned to it. Hence the "incomplete" assigning below.
            $option = 1;
            if ($i % 3 !== 0) {
                $j = rand(1, count($options));
                $option = $j - 1;
                $cf_generator->set_checkbox($program, $cf_id, $option, 'program', 'prog');
            }

            $assigned_programs = array_key_exists((int)$option, $programs_by_cf)
                                ? $programs_by_cf[(int)$option]
                                : [];

            if (!in_array($program->fullname, $assigned_programs)) {
                $assigned_programs[] = $program->fullname;
            }
            $programs_by_cf[(int)$option] = $assigned_programs;
        };

        $filters = $this->get_filters($cf_type, $cf_type);

        return [$options, $program_names, $programs_by_cf, $filters];
    }

    public function test_checkbox_cf_panel_filter() {
        [$options, $all_programs, $programs_by_cf, $filters] = $this->generate_checkbox();

        /** @var filter $filter */
        $filter = $filters[0]; // Panel filter.
        /** @var multi $filter_selector */
        $filter_selector = $filter->selector;

        // Test that display options show only yes or no.
        $filter_options = $filter_selector->get_options();
        $this->assertCount(count($options), $filter_options, "wrong checkbox cf options count");
        foreach ($filter_options as $option) {
            $this->assertContains((string)$option, $options, "unknown checkbox cf label");
        }

        // Test filtering by a single, specific multi option.
        $catalog = new catalog_retrieval();
        $filter_data = $filter->datafilter;
        foreach ($programs_by_cf as $option => $programs) {
            $filter_data->set_current_data([$option]);
            $result = $catalog->get_page_of_objects(1000, 0);

            $this->assertCount(count($programs), $result->objects, "wrong program count");
            foreach ($result->objects as $retrieved) {
                $this->assertContains($retrieved->sorttext, $programs, "wrong programs for checkbox");
            }
        }

        // Test multiple filter selection.
        $filter_data->set_current_data(array_keys($programs_by_cf));
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(count($all_programs), $result->objects, "wrong program count");
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_programs, "wrong programs for checkbox selected options");
        }

        // Test empty filter selection. This should disable the filter but since
        // all programs have custom fields (some with default values), everything
        // should be picked up.
        $filter_data->set_current_data(null);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(count($all_programs), $result->objects, "wrong program count");
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_programs, "wrong programs for empty option");
        }

        // Test filter with non existent option.
        $filter_data->set_current_data([224]);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(0, $result->objects, "unknown data retrieved");

        // Test filter with invalid option value.
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('in or equal search filter only accepts null or array data of int, string or bool');
        $filter_data->set_current_data(123);
    }

    public function test_checkbox_cf_browse_filter() {
        [$options, $all_programs, $programs_by_cf, $filters] = $this->generate_checkbox();

        /** @var filter $filter */
        $filter = $filters[1]; // Browse filter.
        /** @var single $filter_selector */
        $filter_selector = $filter->selector;

        // Test that display options show only those options that are attached to a
        // program. Also, unlike the panel filter, the browse filter has an "all"
        // option.
        $filter_options = array_slice($filter_selector->get_options(), 1);
        $this->assertCount(count($options), $filter_options, "wrong checkbox cf options count");
        foreach ($filter_options as $option) {
            $this->assertContains((string)$option->name, $options, "unknown checkbox cf label");
        }

        // Test filtering by a single, specific checkbox option.
        $catalog = new catalog_retrieval();
        $filter_data = $filter->datafilter;
        foreach ($programs_by_cf as $option => $programs) {
            // Unlike the panel filter, the browse filter expects a single value
            // for matching.
            $filter_data->set_current_data($option);
            $result = $catalog->get_page_of_objects(1000, 0);

            $this->assertCount(count($programs), $result->objects, "wrong program count");
            foreach ($result->objects as $retrieved) {
                $this->assertContains($retrieved->sorttext, $programs, "wrong programs for option");
            }
        }

        // Test empty filter selection. This should disable the filter but since
        // all programs have custom fields (some with default values), everything
        // should be picked up.
        $filter_data->set_current_data(null);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(count($all_programs), $result->objects, "wrong program count");
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_programs, "wrong programs for empty option");
        }

        // Test filter with non existent option.
        $filter_data->set_current_data(123);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(0, $result->objects, "unknown data retrieved");

        // Test filter with invalid option value.
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('equal filter only accepts null, int, string or bool data');
        $filter_data->set_current_data(array_keys($programs_by_cf));
    }
}
