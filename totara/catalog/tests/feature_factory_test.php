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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_catalog
 */

use totara_catalog\catalog_retrieval;
use totara_catalog\datasearch\filter;
use totara_catalog\feature;
use totara_catalog\feature_factory;

defined('MOODLE_INTERNAL') || die();

/**
 * Class feature_factory_test
 *
 * Test that all the feature factories are returning expected data.
 *
 * @package totara_catalog
 * @group totara_catalog
 */
class totara_catalog_feature_factory_testcase extends advanced_testcase {

    /**
     * Get an array of all customfield feature factories.
     *
     * @return array
     */
    private function get_customfield_feature_factories(): array {
        return [
            'course' => 'core_course\totara_catalog\course\feature_factory\custom_fields',
            'certification' => 'totara_certification\totara_catalog\certification\feature_factory\custom_fields',
            'program' => 'totara_program\totara_catalog\program\feature_factory\custom_fields',
        ];
    }


    /**
     * Test get_features()
     *
     * Loop through all feature factories and make sure get_features() return values look good.
     */
    public function test_get_features() {
        $provider_classes = \core_component::get_namespace_classes('totara_catalog', 'totara_catalog\provider');
        $this->assertCount(3, $provider_classes);
        $expected_factory_counts = [
            'core_course\totara_catalog\course' => 4,
            'totara_certification\totara_catalog\certification' => 2,
            'totara_program\totara_catalog\program' => 2,
        ];
        foreach ($provider_classes as $provider_class) {
            $namespace = substr($provider_class, strpos($provider_class, 'totara_catalog')) . '\\feature_factory';
            $feature_factories = \core_component::get_namespace_classes($namespace, 'totara_catalog\feature_factory');
            $this->assertCount($expected_factory_counts[$provider_class], $feature_factories);
            foreach ($feature_factories as $feature_factory) {
                /** @var feature_factory $factory */
                $factory = new $feature_factory();
                $features = $factory->get_features();
                if (in_array($feature_factory, $this->get_customfield_feature_factories())) {
                    // Custom field feature factories should return empty array per default.
                    $this->assertCount(0, $features);
                } else {
                    $this->assertGreaterThan(0, count($features), "No features returned by {$feature_factory}::get_features()");
                    foreach ($features as $feature) {
                        $this->assert_feature($feature);
                    }
                }
            }
        }
    }

    /**
     * Assertions for a feature.
     *
     * Make sure all the properties look OK.
     *
     * @param feature $feature
     */
    private function assert_feature(feature $feature) {
        $this->assertNotEmpty($feature->key);
        $this->assertIsString($feature->key);
        $this->assertNotEmpty($feature->title);
        $this->assertIsString($feature->title);
        $this->assertNotEmpty($feature->datafilter);
        $this->assertInstanceOf(filter::class, $feature->datafilter);
        $this->assertTrue($feature->category instanceof lang_string || is_string($feature->category));
        $options = $feature->get_options();
        $this->assertIsArray($options);
        foreach ($options as $option) {
            $this->assertInstanceOf(lang_string::class, $option);
        }
    }

    /**
     * Test customfield feature factories.
     *
     * Check that customfield feature factories return features as expected for all supported customfields.
     */
    public function test_customfield_feature_factories() {
        $this->resetAfterTest();

        $factories = $this->get_customfield_feature_factories();
        /** @var feature_factory $factory_course */
        $factory_course = new $factories['course']();
        /** @var feature_factory $factory_certification */
        $factory_certification = new $factories['certification']();
        /** @var feature_factory $factory_program */
        $factory_program = new $factories['program']();

        /** @var totara_customfield_generator $cf_generator */
        $cf_generator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');

        // Create a course menu customfield.
        $field_ids = $cf_generator->create_menu('course', ['test_menu' => ['item1', 'item2']]);
        $features = $factory_course->get_features();
        $this->assertCount(1, $features);
        $this->assert_customfield_feature($features[0], 'course', $field_ids['test_menu'], 'menu');
        $this->assertCount(0, $factory_program->get_features());
        $this->assertCount(0, $factory_certification->get_features());

        // Program/Certification multiselect customfield.
        $field_ids = $cf_generator->create_multiselect('prog', ['test_multiselect' => ['option1', 'option2']]);
        $features = $factory_course->get_features();
        $this->assertCount(1, $features);
        $program_features = $factory_program->get_features();
        $this->assertCount(1, $program_features);
        $this->assert_customfield_feature($program_features[0], 'prog', $field_ids['test_multiselect'], 'multiselect');
        $cert_features = $factory_certification->get_features();
        $this->assertCount(1, $cert_features);
        $this->assert_customfield_feature($cert_features[0], 'prog', $field_ids['test_multiselect'], 'multiselect');

        // Create a course checkbox customfield.
        $field_ids = $cf_generator->create_checkbox('course', ['test_checkbox' => []]);
        $features = $factory_course->get_features();
        $this->assertCount(2, $features);
        $checkbox_feature = $this->get_feature_by_name('test_checkbox', $features);
        $this->assert_customfield_feature($checkbox_feature, 'course', $field_ids['test_checkbox'], 'checkbox');
        $this->assertCount(1, $factory_program->get_features());
        $this->assertCount(1, $factory_certification->get_features());
    }

    /**
     * Assertions for a customfield feature.
     *
     * @param feature $feature
     * @param string $provider
     * @param int $field_id
     * @param string $type
     */
    private function assert_customfield_feature(feature $feature, string $provider, int $field_id, string $type) {
        global $DB;
        $customfield = $DB->get_record($provider . '_info_field', array('id' => $field_id));
        $feature_key = 'cff_' . $type . '_' .
            catalog_retrieval::get_safe_table_alias($customfield->shortname . '_' . $customfield->fullname);
        $this->assertSame($feature_key, $feature->key);
        $this->assertSame('test_' . $type, $feature->title);
        $this->assertInstanceOf(filter::class, $feature->datafilter);
        $this->assertEquals(get_string('customfields', 'totara_customfield'), $feature->category);
        $options = $feature->get_options();
        $this->assertGreaterThan(0, count($options));
        foreach ($options as $option) {
            $this->assertIsString($option);
        }
    }

    /**
     * Helper method to extract the feature we're interested in from the array of features.
     *
     * @param string $title
     * @param array $features
     * @return null|feature
     */
    private function get_feature_by_name(string $title, array $features): ?feature {
        foreach ($features as $feature) {
            if ($title == $feature->title) {
                return $feature;
            }
        }
        $this->fail("feature {$title} not found. Something is not right.");
        return null;
    }
}
