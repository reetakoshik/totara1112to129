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
 * @package totara_customfield
 * @category totara_catalog
 */

namespace totara_customfield\totara_catalog;

use totara_catalog\catalog_retrieval;
use totara_catalog\filter;
use totara_catalog\local\filter_handler;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_customfield
 * @group totara_catalog
 */
class totara_customfield_totara_catalog_filter_factory_testcase extends \advanced_testcase {

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Generates test courses, programs and certs.
     *
     * @param int $course_count no of courses to create.
     * @param int $program_count no of programs to create.
     * @param int $cert_count no of certs to create.
     *
     * @return array (items, item names) tuple. Each of the elements in "items"
     *         is an (item, cf prefix, cf table) tuple.
     */
    private function generate_items(
        int $course_count,
        int $program_count,
        int $cert_count
    ): array {
        $items = [];
        $item_names = [];

        $generator = $this->getDataGenerator();
        for ($i = 0; $i < $course_count; $i++) {
            $item = $generator->create_course();

            $items[] = [$item, 'course', 'course'];
            $item_names[] = $item->fullname;
        }

        /** @var \totara_program_generator $program_generator */
        $program_generator = $generator->get_plugin_generator('totara_program');
        for ($i = 0; $i < $program_count; $i++) {
            $item = $program_generator->create_program(['fullname' => "test program name $i"]);

            $items[] = [$item, 'program', 'prog'];
            $item_names[] = $item->fullname;
        }

        for ($i = 0; $i < $cert_count; $i++) {
            $id = $program_generator->create_certification(['fullname' => "test cert name $i"]);
            $item = new \program($id);

            $items[] = [$item, 'program', 'prog'];
            $item_names[] = $item->fullname;
        }

        return [$items, $item_names];
    }

    /**
     * Generates the string suffix to use when getting catalog filters.
     *
     * @param array $data custom field creation data.
     *
     * @return string the suffix.
     */
    private function generate_filter_suffix(array $data): string {
        return sprintf(
            "%s_%s",
            $data['datatype'],
            catalog_retrieval::get_safe_table_alias(
                $data['shortname'] . '_' . $data['fullname']
            )
        );
    }

    /**
     * Generates test custom field *definitions*.
     *
     * @return \stdclass with these fields:
     *         - "menu_options": array of menu/multiselect options; 1st option is
     *           the default.
     *         - "checkbox_options": array of [value => label] checkbox options;
     *           default is "yes".
     *         - "menu_filter_suffix: string suffix to use when getting catalog
     *            filters.
     *         - "multi_filter_suffix: string suffix to use when getting catalog
     *            filters.
     *         - "checkbox_filter_suffix: string suffix to use when getting
     *            catalog filters.
     *         - "menu_program: int program custom field id.
     *         - "multi_program: int program custom field id.
     *         - "checkbox_program: int program custom field id.
     *         - "menu_course: int course custom field id.
     *         - "multi_course: int course custom field id.
     *         - "checkbox_course: int course custom field id.
     */
    private function generate_customfields(): \stdClass {
        $metadata = (object) [
            'menu_options' => ['aaa', 'bbb', 'ccc', 'ddd', 'eee'],
            'checkbox_options' => [1 => 'Yes', 0 => 'No'],
            'menu_program' => null,
            'multi_program' => null,
            'checkbox_program' => null,
            'menu_course' => null,
            'multi_course' => null,
            'checkbox_course' => null
        ];

        $menu_data = [
            'datatype' => 'menu',
            'fullname' => 'menu',
            'shortname' => 'menu',
            'defaultdata' => $metadata->menu_options[0],
            'param1' => $metadata->menu_options
        ];
        /** @var \totara_core_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_core');
        $metadata->menu_course = $generator->create_custom_course_field($menu_data)->id;
        $metadata->menu_program = $generator->create_custom_program_field($menu_data)->id;
        $metadata->menu_filter_suffix = $this->generate_filter_suffix($menu_data);

        $multi_options = [];
        foreach ($metadata->menu_options as $i => $option) {
            $multi_options[] = [
                'option' => $option,
                'icon' => '',
                'default' => $i === 0,
                'delete' => 0
            ];
        };

        $multi_data = [
            'datatype' => 'multiselect',
            'fullname' => 'multiselect',
            'shortname' => 'multiselect',
            'param1' => $multi_options
        ];
        $metadata->multi_course = $generator->create_custom_course_field($multi_data)->id;
        $metadata->multi_program = $generator->create_custom_program_field($multi_data)->id;
        $metadata->multi_filter_suffix = $this->generate_filter_suffix($multi_data);

        $checkbox_data = [
            'datatype' => 'checkbox',
            'fullname' => 'checkbox',
            'shortname' => 'checkbox',
            'defaultdata' => 1
        ];
        $metadata->checkbox_course = $generator->create_custom_course_field($checkbox_data)->id;
        $metadata->checkbox_program = $generator->create_custom_program_field($checkbox_data)->id;
        $metadata->checkbox_filter_suffix = $this->generate_filter_suffix($checkbox_data);

        return $metadata;
    }

    /**
     * Assigns menu customfields to various items.
     *
     * @param array $items the result returned from generate_items().
     * @param \stdClass $metadata result returned from generate_customfields().
     * @return array mapping of menu options to assigned items.
     */
    private function generate_menu(
        array $items,
        \stdClass $metadata
    ): array {
        $by_cf = [];

        /** @var \totara_customfield_generator $cf_generator */
        $cf_generator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');
        foreach ($items as $i => $tuple) {
            [$item, $prefix, $table] = $tuple;
            $cf_field = "menu_$prefix";

            $options = $metadata->menu_options;
            $option = $options[0];
            if ($i % 2 !== 0) {
                $j = rand(1, count($options));
                $option = $options[$j - 1];
                $cf_generator->set_menu($item, $metadata->$cf_field, $j - 1, $prefix, $table);
            }

            $assigned = array_key_exists($option, $by_cf) ? $by_cf[$option] : [];
            if (!in_array($item->fullname, $assigned)) {
                $assigned[] = $item->fullname;
            }
            $by_cf[$option] = $assigned;
        };

        return $by_cf;
    }

    /**
     * Assigns multiselect customfields to various items.
     *
     * @param array $items the result returned from generate_items().
     * @param \stdClass $metadata result returned from generate_customfields().
     *
     * @return array mapping of multi options to assigned items.
     */
    private function generate_multi(
        array $items,
        \stdClass $metadata
    ): array {
        $by_cf = [];

        /** @var \totara_customfield_generator $cf_generator */
        $cf_generator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');
        foreach ($items as $i => $tuple) {
            [$item, $prefix, $table] = $tuple;
            $cf_field = "multi_$prefix";

            $options = $metadata->menu_options;
            $option = $options[0];
            if ($i % 3 !== 0) {
                $j = rand(1, count($options));
                $option = $options[$j - 1];
                $cf_generator->set_multiselect($item, $metadata->$cf_field, [$option], $prefix, $table);
            }

            $assigned = array_key_exists($option, $by_cf) ? $by_cf[$option] : [];
            if (!in_array($item->fullname, $assigned)) {
                $assigned[] = $item->fullname;
            }
            $by_cf[$option] = $assigned;
        };

        return $by_cf;
    }

    /**
     * Assigns checkbox customfields to various items.
     *
     * @param array $items the result returned from generate_items().
     * @param \stdClass $metadata result returned from generate_customfields().
     *
     * @return array mapping of checkbox options to assigned items.
     */
    private function generate_checkbox(
        array $items,
        \stdClass $metadata
    ): array {
        $by_cf = [];

        /** @var \totara_customfield_generator $cf_generator */
        $cf_generator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');
        foreach ($items as $i => $tuple) {
            [$item, $prefix, $table] = $tuple;
            $cf_field = "checkbox_$prefix";

            $options = $metadata->checkbox_options;
            $option = 1;
            if ($i % 4 !== 0) {
                $j = rand(1, count($options));
                $option = $j - 1;
                $cf_generator->set_checkbox($item, $metadata->$cf_field, $option, $prefix, $table);
            }

            $assigned = array_key_exists((int)$option, $by_cf)
                        ? $by_cf[(int)$option]
                        : [];

            if (!in_array($item->fullname, $assigned)) {
                $assigned[] = $item->fullname;
            }
            $by_cf[(int)$option] = $assigned;
        };

        return $by_cf;
    }

    /**
     * Returns the customfield filters to use for testing.
     *
     * @param \stdClass $metadata result from generate_customfields().
     *
     * @return \stdClass with these fields:
     *         - "menu_panel": totara_catalog\filter menu cf panel filter
     *         - "menu_browse": totara_catalog\filter menu cf browse filter
     *         - "multi_panel": totara_catalog\filter multiselect cf panel filter
     *         - "multi_browse": totara_catalog\filter multiselect cf browse filter
     *         - "checkbox_panel": totara_catalog\filter checkbox cf panel filter
     *         - "checkbox_browse": totara_catalog\filter checkbox cf browse filter
     */
    private function get_filters(\stdClass $metadata): \stdClass {
        $filters = (object)[
            'menu_panel' => null,
            'menu_browse' => null,
            'multi_panel' => null,
            'multi_browse' => null,
            'checkbox_panel' => null,
            'checkbox_browse' => null
        ];

        $keys = [
            'cfp_' . $metadata->menu_filter_suffix => 'menu_panel',
            'cfb_' . $metadata->menu_filter_suffix => 'menu_browse',
            'cfp_' . $metadata->multi_filter_suffix => 'multi_panel',
            'cfb_' . $metadata->multi_filter_suffix => 'multi_browse',
            'cfb_' . $metadata->checkbox_filter_suffix => 'checkbox_browse',
            'cfp_' . $metadata->checkbox_filter_suffix => 'checkbox_panel'
        ];

        foreach (filter_handler::instance()->get_all_filters() as $filter) {
            if (array_key_exists($filter->key, $keys)) {
                $field = $keys[$filter->key];
                $filters->$field = $filter;
            }
        }

        foreach ((array)$filters as $key => $filter) {
            $this->assertNotNull($filter, "$key filter not loaded");
        }

        return $filters;
    }

    /**
     * Assigns customfields to various items.
     *
     * @return \stdclass with these fields:
     *         - "by_menu": mapping of menu options to assigned items.
     *         - "by_multi": mapping of multiselect options to assigned items.
     *         - "by_checkbox": mapping of checkbox options to assigned items.
     *         - "filters": result from get_filters().
     *         - "menu_options": menu/multiselect options.
     *         - "checkbox_options": checkbox options.
     *         - "item_names": all item names.
     */
    private function generate(): \stdClass {
        [$items, $item_names] = $this->generate_items(10, 10, 10);
        $cf_metadata = $this->generate_customfields();

        return (object) [
            'by_menu' => $this->generate_menu($items, $cf_metadata),
            'by_multi' => $this->generate_multi($items, $cf_metadata),
            'by_checkbox' => $this->generate_checkbox($items, $cf_metadata),
            'filters' => $this->get_filters($cf_metadata),
            'menu_options' => $cf_metadata->menu_options,
            'checkbox_options' => $cf_metadata->checkbox_options,
            'item_names' => $item_names
        ];
    }

    /**
     * Tests a single panel filter selector.
     *
     * @param filter $filter to test.
     * @param array $options allowed options.
     */
    private function panel_filter_selector_test(filter $filter, array $options): void {
        $filter_options = $filter->selector->get_options();

        $this->assertCount(count($options), $filter_options, "wrong cf options count");
        foreach ($filter_options as $option) {
            $this->assertContains((string)$option, $options, "unknown cf label");
        }
    }

    /**
     * Tests a single panel filter data filter.
     *
     * @param filter $filter to test.
     * @param array $by_cf mapping of customfield options to assigned items.
     * @param array $item_names all item names.
     */
    private function panel_filter_data_test(
        filter $filter,
        array $by_cf,
        array $item_names
    ): void {
        $catalog = new catalog_retrieval();
        $filter_data = $filter->datafilter;

        // Test filtering by a single, specific menu option.
        foreach ($by_cf as $option => $items) {
            $filter_data->set_current_data([$option]);
            $result = $catalog->get_page_of_objects(1000, 0);

            $this->assertCount(count($items), $result->objects, "wrong item count");
            foreach ($result->objects as $retrieved) {
                $this->assertContains($retrieved->sorttext, $items, "wrong items for option");
            }
        }

        // Test multiple filter selection.
        $filter_data->set_current_data(array_keys($by_cf));
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(count($item_names), $result->objects, "wrong course count");
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $item_names, "wrong courses for multi selected options");
        }

        // Test filter with non existent option.
        $filter_data->set_current_data(['does not exist']);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(0, $result->objects, "unknown data retrieved");

        // Test empty filter selection. This disables the filter and prevents it
        // from interfering with the next filter under test (filters are ANDed
        // together).
        // Since all courses have custom fields (some with default values), everything
        // should be picked up.
        $filter_data->set_current_data(null);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(count($item_names), $result->objects, "wrong course count");
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $item_names, "wrong courses for empty option");
        }
    }

    public function test_cf_panel_filter() {
        $generated = $this->generate();
        $menu_panel = $generated->filters->menu_panel;
        $multi_panel = $generated->filters->multi_panel;
        $checkbox_panel = $generated->filters->checkbox_panel;

        $options = $generated->menu_options;
        $checkbox_options = $generated->checkbox_options;
        $this->panel_filter_selector_test($menu_panel, $options);
        $this->panel_filter_selector_test($multi_panel, $options);
        $this->panel_filter_selector_test($checkbox_panel, $checkbox_options);

        $item_names = $generated->item_names;
        $this->panel_filter_data_test($menu_panel, $generated->by_menu, $item_names);
        $this->panel_filter_data_test($multi_panel, $generated->by_multi, $item_names);
        $this->panel_filter_data_test($checkbox_panel, $generated->by_checkbox, $item_names);
    }

    /**
     * Tests a single browse filter selector.
     *
     * @param filter $filter to test.
     * @param array $options allowed options.
     */
    private function browse_filter_selector_test(filter $filter, array $options): void {
        // Unlike the panel filter, the browse filter has an "all" option.
        $filter_options = array_slice($filter->selector->get_options(), 1);

        $this->assertCount(count($options), $filter_options, "wrong cf options count");
        foreach ($filter_options as $option) {
            $this->assertContains((string)$option->name, $options, "unknown cf label");
        }
    }

    /**
     * Tests a single browse filter data filter.
     *
     * @param filter $filter to test.
     * @param array $by_cf mapping of customfield options to assigned items.
     * @param array $item_names all item names.
     */
    private function browse_filter_data_test(
        filter $filter,
        array $by_cf,
        array $item_names
    ): void {
        $catalog = new catalog_retrieval();
        $filter_data = $filter->datafilter;

        // Test filtering by a single, specific menu option.
        foreach ($by_cf as $option => $items) {
            // Unlike the panel filter, the browse filter expects a single value
            // for matching.
            $filter_data->set_current_data($option);
            $result = $catalog->get_page_of_objects(1000, 0);

            $this->assertCount(count($items), $result->objects, "wrong item count");
            foreach ($result->objects as $retrieved) {
                $this->assertContains($retrieved->sorttext, $items, "wrong items for option");
            }
        }

        // Test filter with non existent option.
        $filter_data->set_current_data(123);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(0, $result->objects, "unknown data retrieved");

        // Test empty filter selection. This disables the filter and prevents it
        // from interfering with the next filter under test (filters are ANDed
        // together).
        // Since all courses have custom fields (some with default values), everything
        // should be picked up.
        $filter_data->set_current_data(null);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(count($item_names), $result->objects, "wrong course count");
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $item_names, "wrong courses for empty option");
        }
    }

    public function test_cf_browse_filter() {
        $generated = $this->generate();
        $menu_browse = $generated->filters->menu_browse;
        $multi_browse = $generated->filters->multi_browse;
        $checkbox_browse = $generated->filters->checkbox_browse;

        $options = $generated->menu_options;
        $checkbox_options = $generated->checkbox_options;
        $this->browse_filter_selector_test($menu_browse, $options);
        $this->browse_filter_selector_test($multi_browse, $options);
        $this->browse_filter_selector_test($checkbox_browse, $checkbox_options);

        $item_names = $generated->item_names;
        $this->browse_filter_data_test($menu_browse, $generated->by_menu, $item_names);
        $this->browse_filter_data_test($multi_browse, $generated->by_multi, $item_names);
        $this->browse_filter_data_test($checkbox_browse, $generated->by_checkbox, $item_names);
    }
}
