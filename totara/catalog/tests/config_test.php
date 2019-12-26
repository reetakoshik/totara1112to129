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

use totara_catalog\local\config;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/totara/catalog/tests/config_test_base.php');

/**
 * Class config_test
 *
 * Tests for configuration data access and update.
 *
 * @package totara_catalog
 * @group totara_catalog
 */
class totara_catalog_config_testcase extends config_base_testcase {

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function defaults_data_provider() {
        $all = [];
        $all_expected = array_merge($this->get_expected_static_defaults(), $this->get_expected_generated_defaults());
        foreach ($all_expected as $k => $v) {
            $all[] = [$k, $v];
        }
        return $all;
    }

    public function test_get() {
        $actual = config::instance()->get();
        $expected = array_merge($this->get_expected_static_defaults(), $this->get_expected_generated_defaults());
        $this->assertEquals($expected, $actual);
        $this->assertCount(32, $actual);
    }

    public function test_get_defaults() {
        $actual = config::instance()->get_defaults();
        $expected = array_merge($this->get_expected_static_defaults(), $this->get_expected_generated_defaults());
        $this->assertEquals($expected, $actual);
        $this->assertCount(32, $actual);
    }

    public function test_get_static_defaults() {
        $actual = $config = config::instance()->get_static_defaults();
        $this->assertEquals($this->get_expected_static_defaults(), $actual);
    }

    public function test_get_provider_defaults() {
        $config = config::instance();
        $generated_defaults = $config->get_provider_defaults();
        $this->assertCount(13, $generated_defaults);
        $this->assertEquals($this->get_expected_generated_defaults(), $generated_defaults);
        $this->assertEquals(array_diff_key($config->get(), $config->get_static_defaults()), $generated_defaults);
    }

    /**
     * @dataProvider defaults_data_provider
     * @param $key
     * @param $value
     */
    public function test_get_value_returns_defaults($key, $value) {
        $config = config::instance();
        $this->assertEquals($value, $config->get_value($key));
    }

    public function test_get_bad_config_key() {
        $this->assertEquals(null, config::instance()->get_value('nonexistent_config_key'));
    }

    /**
     * @dataProvider defaults_data_provider
     * @param $key
     * @param $value
     */
    public function test_update($key, $value) {
        $this->assertArrayHasKey(
            $key,
            $this->get_non_default_example_values(),
            "Bad test data. Couldn't find a value for '{$key}' in non_default_example_values."
        );
        $non_default_value = $this->get_non_default_example_values()[$key];

        $update_data = [$key => $non_default_value];
        $config = config::instance();
        $defaults = $config->get_defaults();
        $pre_update = $config->get();
        $config->update($update_data);
        $post_update = $config->get();

        // Sanity check for test data.
        $this->assertNotEquals(
            $pre_update[$key],
            $non_default_value,
            "Bad test data. Value for '{$key}' must not be the same as default in non_default_example_values array."
        );

        $this->assertEquals(array_merge($defaults, $update_data), $post_update);
        $this->assertEquals($non_default_value, $config->get_value($key));

        // Verify it also works with new instance.
        $config = config::instance();
        $this->assertEquals(array_merge($defaults, $update_data), $config->get());
        $this->assertEquals($non_default_value, $config->get_value($key));
    }

    public function test_update_changes_provider_status() {
        global $DB;
        $config = config::instance();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        // Check provider deactivation indirectly by verifying that the catalog record gets deleted.
        $this->assertTrue($DB->record_exists('catalog', ['objecttype' => 'course', 'objectid' => $course->id]));
        $config->update(['learning_types_in_catalog' => ['program', 'certification']]);
        $this->assertFalse($DB->record_exists('catalog', ['objecttype' => 'course', 'objectid' => $course->id]));

        // Check provider activation indirectly by verifying that ad hoc task is added to task queue.
        $count_before = $DB->count_records('task_adhoc', ['component' => 'totara_catalog']);
        $config->update(['learning_types_in_catalog' => ['course', 'program', 'certification']]);
        $this->assertEquals($count_before + 1, $DB->count_records('task_adhoc', ['component' => 'totara_catalog']));
    }

    /**
     * Test get_provider_config() and get_provider_config_value() methods.
     */
    public function test_get_provider_config() {
        $config = config::instance();
        $config->update($this->get_non_default_example_values());

        $provider_config = $config->get_provider_config('course');
        $this->assertCount(13, $provider_config);
        foreach ($provider_config as $k => $v) {
            $this->assertEquals($v, $this->get_non_default_example_values()[$k]['course']);
            $this->assertEquals($v, $config->get_provider_config_value('course', $k));
        }

        // Set back to defaults and make sure cache isn't stale.
        $config->update($this->get_expected_generated_defaults());
        $provider_config = $config->get_provider_config('course');
        $this->assertCount(13, $provider_config);
        foreach ($provider_config as $k => $v) {
            $this->assertEquals($v, $this->get_expected_generated_defaults()[$k]['course']);
            $this->assertEquals($v, $config->get_provider_config_value('course', $k));
        }
    }

    public function test_is_provider_active() {
        $config = config::instance();
        $this->assertTrue($config->is_provider_active('course'));
        $this->assertTrue($config->is_provider_active('program'));
        $this->assertTrue($config->is_provider_active('certification'));

        $config->update(['learning_types_in_catalog' => ['course']]);

        $this->assertTrue($config->is_provider_active('course'));
        $this->assertFalse($config->is_provider_active('program'));
        $this->assertFalse($config->is_provider_active('certification'));

        $config->update(['learning_types_in_catalog' => []]);

        $this->assertFalse($config->is_provider_active('course'));
        $this->assertFalse($config->is_provider_active('program'));
        $this->assertFalse($config->is_provider_active('certification'));
    }

    public function test_get_learning_types_in_catalog() {
        $config = config::instance();
        $actual = $config->get_learning_types_in_catalog();
        $expected = ['course', 'program', 'certification'];
        sort($expected);
        sort($actual);
        $this->assertSame($expected, $actual);

        $config->update(['learning_types_in_catalog' => ['course', 'program']]);
        $actual = $config->get_learning_types_in_catalog();
        $expected = ['course', 'program'];
        sort($expected);
        sort($actual);
        $this->assertSame($expected, $actual);

        $config->update(['learning_types_in_catalog' => []]);
        $this->assertSame([], $config->get_learning_types_in_catalog());
    }
}
