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
use totara_catalog\feature;
use totara_catalog\cache_handler;
use totara_catalog\local\config;
use totara_catalog\local\feature_handler;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_catalog
 */
class totara_customfield_totara_catalog_feature_factory_testcase extends \advanced_testcase {

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
     * Generates the string key to use when getting catalog features.
     *
     * @param array $data custom field creation data.
     *
     * @return string the key.
     */
    private function generate_feature_key(array $data): string {
        return sprintf(
            "cff_%s_%s",
            $data['datatype'],
            catalog_retrieval::get_safe_table_alias(
                $data['shortname'] . '_' . $data['fullname']
            )
        );
    }

    /**
     * Generates test custom field *definitions*.
     *
     * @param bool $with_defaults if true, creates custom field definitions with
     *        default values.
     *
     * @return \stdclass with these fields:
     *         - "menu_options": array of menu/multiselect options; 1st option is
     *           the default.
     *         - "checkbox_options": array of [value => label] checkbox options;
     *           default is "yes".
     *         - "menu_feature_key: string suffix to use when getting catalog
     *            features.
     *         - "multi_feature_key: string suffix to use when getting catalog
     *            features.
     *         - "checkbox_feature_key: string suffix to use when getting
     *            catalog features.
     *         - "menu_program: int program custom field id.
     *         - "multi_program: int program custom field id.
     *         - "checkbox_program: int program custom field id.
     *         - "menu_course: int course custom field id.
     *         - "multi_course: int course custom field id.
     *         - "checkbox_course: int course custom field id.
     */
    private function generate_customfields(bool $with_defaults): \stdClass {
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
            'param1' => $metadata->menu_options
        ];
        if ($with_defaults) {
            $menu_data['defaultdata'] = $metadata->menu_options[0];
        }

        /** @var \totara_core_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_core');
        $metadata->menu_course = $generator->create_custom_course_field($menu_data)->id;
        $metadata->menu_program = $generator->create_custom_program_field($menu_data)->id;
        $metadata->menu_feature_key = $this->generate_feature_key($menu_data);

        $multi_options = [];
        foreach ($metadata->menu_options as $i => $option) {
            $ms_options = [
                'option' => $option,
                'icon' => '',
                'delete' => 0,
                'default' => 0 // Multiselect always has this field even for "no" defaults
            ];
            if ($with_defaults) {
                $ms_options['default'] = $i === 0;
            }

            $multi_options[] = $ms_options;
        };

        $multi_data = [
            'datatype' => 'multiselect',
            'fullname' => 'multiselect',
            'shortname' => 'multiselect',
            'param1' => $multi_options
        ];
        $metadata->multi_course = $generator->create_custom_course_field($multi_data)->id;
        $metadata->multi_program = $generator->create_custom_program_field($multi_data)->id;
        $metadata->multi_feature_key = $this->generate_feature_key($multi_data);

        $checkbox_data = [
            'datatype' => 'checkbox',
            'fullname' => 'checkbox',
            'shortname' => 'checkbox',
            'defaultdata' => 1 // Checkboxes always have defaults!
        ];
        $metadata->checkbox_course = $generator->create_custom_course_field($checkbox_data)->id;
        $metadata->checkbox_program = $generator->create_custom_program_field($checkbox_data)->id;
        $metadata->checkbox_feature_key = $this->generate_feature_key($checkbox_data);

        return $metadata;
    }

    /**
     * Assigns menu customfields to various items.
     *
     * @param array $items the result returned from generate_items().
     * @param \stdClass $metadata result returned from generate_customfields().
     * @param bool $cf_has_defaults indicates if custom field definitions have
     *        default values.
     *
     * @return array mapping of menu options to assigned items.
     */
    private function generate_menu(
        array $items,
        \stdClass $metadata,
        bool $cf_has_defaults
    ): array {
        $by_cf = [];

        /** @var \totara_customfield_generator $cf_generator */
        $cf_generator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');
        foreach ($items as $i => $tuple) {
            [$item, $prefix, $table] = $tuple;
            $cf_field = "menu_$prefix";

            $options = $metadata->menu_options;
            $option = $cf_has_defaults ? $options[0] : null;
            if ($i % 2 !== 0) {
                $j = rand(1, count($options));
                $option = $options[$j - 1];
                $cf_generator->set_menu($item, $metadata->$cf_field, $j - 1, $prefix, $table);
            }

            if (!is_null($option)) {
                $assigned = array_key_exists($option, $by_cf) ? $by_cf[$option] : [];
                if (!in_array($item->fullname, $assigned)) {
                    $assigned[] = $item->fullname;
                }
                $by_cf[$option] = $assigned;
            }
        };

        return $by_cf;
    }

    /**
     * Assigns multiselect customfields to various items.
     *
     * @param array $items the result returned from generate_items().
     * @param \stdClass $metadata result returned from generate_customfields().
     * @param bool $cf_has_defaults indicates if custom field definitions have
     *        default values.
     *
     * @return array mapping of multi options to assigned items.
     */
    private function generate_multi(
        array $items,
        \stdClass $metadata,
        bool $cf_has_defaults
    ): array {
        $by_cf = [];

        /** @var \totara_customfield_generator $cf_generator */
        $cf_generator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');
        foreach ($items as $i => $tuple) {
            [$item, $prefix, $table] = $tuple;
            $cf_field = "multi_$prefix";

            $options = $metadata->menu_options;
            $option = $cf_has_defaults ? $options[0] : null;
            if ($i % 3 !== 0) {
                $j = rand(1, count($options));
                $option = $options[$j - 1];
                $cf_generator->set_multiselect($item, $metadata->$cf_field, [$option], $prefix, $table);
            }

            if (!is_null($option)) {
                $assigned = array_key_exists($option, $by_cf) ? $by_cf[$option] : [];
                if (!in_array($item->fullname, $assigned)) {
                    $assigned[] = $item->fullname;
                }
                $by_cf[$option] = $assigned;
            }
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

            // Unlike other custom fields, checkboxes always have default values.
            // This makes the processing here slightly different.
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
     * Returns the customfield features to use for testing.
     *
     * @param \stdClass $metadata result from generate_customfields().
     *
     * @return \stdClass with these fields:
     *         - "menu": totara_catalog\feature menu cf feature
     *         - "multi": totara_catalog\feature multiselect cf feature
     *         - "checkbox": totara_catalog\feature checkbox cf feature
     */
    private function get_features(\stdClass $metadata): \stdClass {
        $features = (object)[
            'menu' => null,
            'multi' => null,
            'checkbox' => null
        ];

        $keys = [
            $metadata->menu_feature_key => 'menu',
            $metadata->multi_feature_key => 'multi',
            $metadata->checkbox_feature_key => 'checkbox'
        ];

        foreach (feature_handler::instance()->get_all_features() as $feature) {
            if (array_key_exists($feature->key, $keys)) {
                $field = $keys[$feature->key];
                $features->$field = $feature;
            }
        }

        foreach ((array)$features as $key => $feature) {
            $this->assertNotNull($feature, "$key feature not loaded");
        }

        return $features;
    }

    /**
     * Assigns customfields to various items.
     *
     * @param bool $with_defaults indicates if custom field definitions will
     *        have default values.
     *
     * @return \stdclass with these fields:
     *         - "by_menu": mapping of menu options to assigned items.
     *         - "by_multi": mapping of multiselect options to assigned items.
     *         - "by_checkbox": mapping of checkbox options to assigned items.
     *         - "features": result from get_features().
     *         - "menu_options": menu/multiselect options.
     *         - "checkbox_options": checkbox options.
     *         - "item_names": all item names.
     */
    private function generate(bool $with_defaults = true): \stdClass {
        [$items, $item_names] = $this->generate_items(10, 10, 10);
        $cf_metadata = $this->generate_customfields($with_defaults);

        return (object) [
            'by_menu' => $this->generate_menu($items, $cf_metadata, $with_defaults),
            'by_multi' => $this->generate_multi($items, $cf_metadata, $with_defaults),
            'by_checkbox' => $this->generate_checkbox($items, $cf_metadata),
            'features' => $this->get_features($cf_metadata),
            'menu_options' => $cf_metadata->menu_options,
            'checkbox_options' => $cf_metadata->checkbox_options,
            'item_names' => $item_names
        ];
    }

    /**
     * Returns the catalog search result after setting up the specified featured
     * learning options.
     *
     * @param string $source featured learning source.
     * @param string $value featured learning value.
     * @param bool $enabled whether the catalog featured learning facility is
     *        enabled.
     *
     * @return stdClass retrieval result.
     */
    private function featured_learning_result(
        string $source,
        string $value,
        bool $enabled = true
    ): \stdClass {
        cache_handler::reset_all_caches();
        config::instance()->update(
            [
                'featured_learning_enabled' => $enabled,
                'featured_learning_source' => $source,
                'featured_learning_value' => $value
            ]
        );

        $catalog = new catalog_retrieval();
        return $catalog->get_page_of_objects(1000, 0);
    }

    /**
     * Tests a single custom field type set as featured learning.
     *
     * @param feature $feature to test.
     * @param array $by_cf mapping of customfield options to assigned items.
     * @param string[] $item_names all item names.
     * @param string[] $options valid menu/multiselect options.
     */
    private function feature_test(
        feature $feature,
        array $by_cf,
        array $item_names,
        array $options
    ): void {
        // Test features by a single, specific menu option.
        foreach ($by_cf as $option => $items) {
            $result = $this->featured_learning_result($feature->key, $option);
            $this->assertCount(count($item_names), $result->objects, "wrong retrieved count");

            foreach ($result->objects as $i => $retrieved) {
                if ($i < count($items)) {
                    $this->assertContains($retrieved->sorttext, $items, "wrong featured for option");
                    $this->assertSame(1, (int)$retrieved->featured, "featured item not at top of retrieved");
                } else {
                    $this->assertContains($retrieved->sorttext, $item_names, "unknown item");
                    $this->assertSame(0, (int)$retrieved->featured, "non featured item at top of retrieved");
                }
            }
        }

        // Test feature with non existent option. This is not possible via the
        // UI, but nonetheless it is possible programmatically.
        $result = $this->featured_learning_result($feature->key, 'does not exist');
        $this->assertCount(count($item_names), $result->objects, "wrong retrieved count");

        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $item_names, "unknown item");
            $this->assertSame(0, (int)$retrieved->featured, "featured item exists");
        }

        // Test disabled feature selection even if a valid option is there.
        $result = $this->featured_learning_result($feature->key, $options[0], false);
        $this->assertCount(count($item_names), $result->objects, "wrong retrieved count");

        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $item_names, "unknown item");
            $this->assertObjectNotHasAttribute('featured', $retrieved, "featured field exists");
        }
    }

    public function test_feature_with_cf_defaults() {
        $generated = $this->generate();
        $menu = $generated->features->menu;
        $multi = $generated->features->multi;
        $checkbox = $generated->features->checkbox;

        $options = $generated->menu_options;
        $checkbox_options = $generated->checkbox_options;
        $item_names = $generated->item_names;

        $this->feature_test($menu, $generated->by_menu, $item_names, $options);
        $this->feature_test($multi, $generated->by_multi, $item_names, $options);
        $this->feature_test($checkbox, $generated->by_checkbox, $item_names, $checkbox_options);
    }

    public function test_feature_no_cf_defaults() {
        $generated = $this->generate(false);
        $menu = $generated->features->menu;
        $multi = $generated->features->multi;
        $checkbox = $generated->features->checkbox;

        $options = $generated->menu_options;
        $checkbox_options = $generated->checkbox_options;
        $item_names = $generated->item_names;

        $this->feature_test($menu, $generated->by_menu, $item_names, $options);
        $this->feature_test($multi, $generated->by_multi, $item_names, $options);
        $this->feature_test($checkbox, $generated->by_checkbox, $item_names, $checkbox_options);
    }
}
