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

use totara_catalog\dataformatter\formatter;
use totara_catalog\dataformatter\static_text;
use totara_catalog\dataholder;
use totara_catalog\dataholder_factory;
use totara_catalog\local\learning_type_dataholders;
use totara_catalog\local\required_dataholder;

defined('MOODLE_INTERNAL') || die();

/**
 * Class dataholder_test
 *
 * Tests for dataholder and dataholder factories.
 *
 * @package totara_catalog
 * @group totara_catalog
 */
class totara_catalog_dataholder_testcase extends advanced_testcase {

    /**
     * Get an array of all customfield dataholder factories.
     *
     * @return array
     */
    private function get_customfield_dataholder_factories(): array {
        return [
            'course' => 'core_course\totara_catalog\course\dataholder_factory\custom_fields',
            'certification' => 'totara_certification\totara_catalog\certification\dataholder_factory\custom_fields',
            'program' => 'totara_program\totara_catalog\program\dataholder_factory\custom_fields',
        ];
    }

    /**
     * Loop through all dataholder factories and make sure get_dataholders() return values look good.
     */
    public function test_get_dataholders() {
        $provider_classes = \core_component::get_namespace_classes('totara_catalog', 'totara_catalog\provider');
        $this->assertCount(3, $provider_classes);
        $expected_factory_counts = [
            'core_course\totara_catalog\course' => 20,
            'totara_certification\totara_catalog\certification' => 17,
            'totara_program\totara_catalog\program' => 16,
        ];
        foreach ($provider_classes as $provider_class) {
            $namespace = substr($provider_class, strpos($provider_class, 'totara_catalog')) . '\\dataholder_factory';
            $dataholder_factories = \core_component::get_namespace_classes($namespace, 'totara_catalog\dataholder_factory');
            $this->assertCount($expected_factory_counts[$provider_class], $dataholder_factories);
            foreach ($dataholder_factories as $dataholder_factory) {
                /** @var dataholder_factory $factory */
                $factory = new $dataholder_factory();
                $dataholders = $factory->get_dataholders();
                if (in_array($dataholder_factory, $this->get_customfield_dataholder_factories())) {
                    // Custom field dataholder factories should return empty array per default.
                    $this->assertCount(0, $dataholders);
                } else {
                    $this->assertGreaterThan(
                        0,
                        count($dataholders),
                        "No dataholders returned by {$dataholder_factory}::get_dataholders()"
                    );
                    foreach ($dataholders as $dataholder) {
                        $this->assert_dataholder($dataholder);
                    }
                }
            }
        }
    }

    /**
     * Make sure all dataholder properties look OK.
     *
     * @param dataholder $dataholder
     */
    private function assert_dataholder(dataholder $dataholder) {
        $this->assertNotEmpty($dataholder->key);
        $this->assertIsString($dataholder->key);
        $this->assertIsString($dataholder->name);
        $this->assertNotEmpty($dataholder->formatters);
        $this->assertIsArray($dataholder->formatters);
        foreach ($dataholder->formatters as $formatter) {
            $this->assertInstanceOf(formatter::class, $formatter);
        }
        $this->assertIsArray($dataholder->datajoins);
        $this->assertIsArray($dataholder->dataparams);
        $this->assertTrue($dataholder->category instanceof lang_string || is_string($dataholder->category));
    }

    /**
     * Check that customfield dataholder factories return dataholders as expected for all supported customfields.
     */
    public function test_customfield_dataholder_factories() {
        $this->resetAfterTest();

        $factories = $this->get_customfield_dataholder_factories();
        /** @var dataholder_factory $factory_course */
        $factory_course = new $factories['course']();
        /** @var dataholder_factory $factory_certification */
        $factory_certification = new $factories['certification']();
        /** @var dataholder_factory $factory_program */
        $factory_program = new $factories['program']();

        /** @var totara_customfield_generator $cf_generator */
        $cf_generator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');

        // Course text customfield.
        $field_ids = $cf_generator->create_text('course', ['test_text']);
        $dataholders = $factory_course->get_dataholders();
        $this->assertCount(1, $dataholders);
        $this->assert_customfield_dataholder($dataholders[0], 'course', $field_ids['test_text'], 'text');
        $this->assertCount(0, $factory_program->get_dataholders());
        $this->assertCount(0, $factory_certification->get_dataholders());

        // Program/Certification textarea customfield.
        $field_ids = $cf_generator->create_textarea('prog', ['test_textarea']);
        $this->assertCount(1, $factory_course->get_dataholders());
        $dataholders = $factory_program->get_dataholders();
        $this->assertCount(1, $dataholders);
        $this->assert_customfield_dataholder($dataholders[0], 'prog', $field_ids['test_textarea'], 'textarea');
        // Certification and program customfields are expected to be the same.
        $dataholders_cert = $factory_certification->get_dataholders();
        $this->assertCount(1, $dataholders_cert);
        $this->assertEquals($dataholders_cert[0], $dataholders[0]);

        // Program/Certification datetime customfield.
        $field_ids = $cf_generator->create_datetime('prog', ['test_datetime' => []]);
        $this->assertCount(1, $factory_course->get_dataholders());
        $dataholders = $factory_program->get_dataholders();
        $this->assertCount(2, $dataholders);
        $datetime_dataholder_prog = $this->get_dataholder_by_name('test_datetime', $dataholders);
        $this->assert_customfield_dataholder($datetime_dataholder_prog, 'prog', $field_ids['test_datetime'], 'datetime');
        $dataholders = $factory_certification->get_dataholders();
        $this->assertCount(2, $dataholders);
        $datetime_dataholder_cert = $this->get_dataholder_by_name('test_datetime', $dataholders);
        $this->assertEquals($datetime_dataholder_cert, $datetime_dataholder_prog);

        // Create a course menu customfield.
        $field_ids = $cf_generator->create_menu('course', ['test_menu' => ['item1', 'item2']]);
        $dataholders = $factory_course->get_dataholders();
        $this->assertCount(2, $dataholders);
        $menu_dataholder = $this->get_dataholder_by_name('test_menu', $dataholders);
        $this->assert_customfield_dataholder($menu_dataholder, 'course', $field_ids['test_menu'], 'menu');
        $this->assertCount(2, $factory_program->get_dataholders());
        $this->assertCount(2, $factory_certification->get_dataholders());

        // Create a course multiselect customfield.
        $field_ids = $cf_generator->create_multiselect('course', ['test_multiselect' => ['option1', 'option2']]);
        $dataholders = $factory_course->get_dataholders();
        $this->assertCount(3, $dataholders);
        $menu_dataholder = $this->get_dataholder_by_name('test_multiselect', $dataholders);
        $this->assert_customfield_dataholder($menu_dataholder, 'course', $field_ids['test_multiselect'], 'multiselect');
        $this->assertCount(2, $factory_program->get_dataholders());
        $this->assertCount(2, $factory_certification->get_dataholders());

        // Create a course checkbox customfield.
        $field_ids = $cf_generator->create_checkbox('course', ['test_checkbox' => []]);
        $dataholders = $factory_course->get_dataholders();
        $this->assertCount(4, $dataholders);
        $menu_dataholder = $this->get_dataholder_by_name('test_checkbox', $dataholders);
        $this->assert_customfield_dataholder($menu_dataholder, 'course', $field_ids['test_checkbox'], 'checkbox');
        $this->assertCount(2, $factory_program->get_dataholders());
        $this->assertCount(2, $factory_certification->get_dataholders());
    }

    /**
     * Assertions for a customfield dataholder.
     *
     * @param dataholder $dataholder
     * @param string $provider
     * @param int $field_id
     * @param string $type
     */
    private function assert_customfield_dataholder(dataholder $dataholder, string $provider, int $field_id, string $type) {
        $dataholder_key = 'cf_' . $provider . '_' . $field_id;
        $this->assertEquals($dataholder_key, $dataholder->key);
        $this->assertEquals('test_' . $type, $dataholder->name);
        foreach ($dataholder->formatters as $formatter_type => $formatter) {
            $this->assertInstanceOf(formatter::class, $formatter);
        }
        $this->assertCount(1, $dataholder->datajoins);
        $this->assertArrayHasKey($dataholder_key, $dataholder->datajoins);
        $this->assertCount(1, $dataholder->dataparams);
        $this->assertArrayHasKey($dataholder_key . '_data', $dataholder->dataparams);
        $this->assertEquals(get_string('customfields', 'totara_customfield'), $dataholder->category);
    }

    /**
     * Helper method to extract the dataholder we're interested in from the array of dataholders.
     *
     * @param string $name
     * @param array $dataholders
     * @return null|dataholder
     */
    private function get_dataholder_by_name(string $name, array $dataholders): ?dataholder {
        foreach ($dataholders as $dataholder) {
            if ($name == $dataholder->name) {
                return $dataholder;
            }
        }
        $this->fail("Dataholder {$name} not found. Something is not right.");
        return null;
    }

    /**
     * Test the get_formatted_value() method.
     */
    public function test_get_formatted_value() {
        $context = context_system::instance();

        // This only needs to be tested for one dataholder since it's basically just a wrapper for a dataformatter
        // method, which is tested elsewhere. Arbitrarily we pick the course fullname dataholder.
        $factory = new core_course\totara_catalog\course\dataholder_factory\fullname();
        /** @var dataholder $dataholder */
        $dataholder = $factory->get_dataholders()[0];

        $all_types = [
            formatter::TYPE_PLACEHOLDER_TITLE,
            formatter::TYPE_PLACEHOLDER_TEXT,
            formatter::TYPE_PLACEHOLDER_ICON,
            formatter::TYPE_PLACEHOLDER_ICONS,
            formatter::TYPE_PLACEHOLDER_IMAGE,
            formatter::TYPE_PLACEHOLDER_PROGRESS,
            formatter::TYPE_PLACEHOLDER_RICH_TEXT,
            formatter::TYPE_FTS,
            formatter::TYPE_SORT_TEXT,
            formatter::TYPE_SORT_TIME,
        ];

        $dataholder_types = [
            formatter::TYPE_FTS,
            formatter::TYPE_PLACEHOLDER_TITLE,
            formatter::TYPE_PLACEHOLDER_TEXT,
        ];

        foreach ($all_types as $type) {
            if (in_array($type, $dataholder_types)) {
                $this->assertArrayHasKey($type, $dataholder->formatters);
                $this->assertEquals('test_text', $dataholder->get_formatted_value($type, ['text' => 'test_text'], $context));
            } else {
                try {
                    $dataholder->get_formatted_value($type, ['text' => 'test_text'], $context);
                } catch (coding_exception $e) {
                    $this->assertContains('Invalid formatter type', $e->getMessage());
                }
            }
        }
    }

    /**
     * Test class learning_type_dataholders.
     */
    public function test_learning_type_dataholders() {
        $learning_type_dataholders = learning_type_dataholders::create('test_name');
        $this->assertCount(1, $learning_type_dataholders);
        $dataholder = $learning_type_dataholders[0];

        $this->assertSame('catalog_learning_type', $dataholder->key);
        $this->assertSame('Learning type', $dataholder->name);
        $this->assertCount(1, $dataholder->formatters);
        $this->assertInstanceOf(static_text::class, $dataholder->formatters[formatter::TYPE_PLACEHOLDER_TEXT]);
        $this->assertCount(0, $dataholder->datajoins);
        $this->assertCount(0, $dataholder->dataparams);
        $this->assertEquals(get_string('default_option_group', 'totara_catalog'), $dataholder->category);

        $this->assertEquals(
            'test_name',
            $dataholder->get_formatted_value(formatter::TYPE_PLACEHOLDER_TEXT, [], context_system::instance())
        );
    }

    /**
     * Test class required_dataholder.
     */
    public function test_required_dataholder() {
        $factory = new core_course\totara_catalog\course\dataholder_factory\fullname();
        /** @var dataholder $dataholder */
        $dataholder = $factory->get_dataholders()[0];
        $required_dataholder = new required_dataholder($dataholder, formatter::TYPE_PLACEHOLDER_TITLE);

        $this->assertSame($dataholder, $required_dataholder->dataholder);
        $this->assertSame(formatter::TYPE_PLACEHOLDER_TITLE, $required_dataholder->formattertype);
        $this->assertSame($dataholder->formatters[formatter::TYPE_PLACEHOLDER_TITLE], $required_dataholder->formatter);
    }
}
