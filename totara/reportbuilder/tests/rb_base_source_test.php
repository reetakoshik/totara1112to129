<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

/**
 * @group totara_reportbuilder
 */
class totara_reportbuilder_rb_base_source_testcase extends advanced_testcase {

    use \totara_reportbuilder\phpunit\report_testing;

    /**
     * @return phpunit_test_report_source
     */
    public function get_test_report_source() {
        global $CFG;

        require_once($CFG->dirroot . '/totara/reportbuilder/tests/fixtures/phpunit_test_report_source.php');

        $source = new phpunit_test_report_source();
        self::assertInstanceOf('phpunit_test_report_source', $source);
        self::assertInstanceOf('rb_base_source', $source);
        return $source;
    }

    public function test_get_all_advanced_column_options() {
        $source = $this->get_test_report_source();

        $options = $source->get_all_advanced_column_options();
        self::assertIsArray($options);
        self::assertCount(3, $options);

        $key_none = get_string('none');
        $key_transforms = get_string('advancedgrouptimedate', 'totara_reportbuilder');
        $key_aggregations = get_string('advancedgroupaggregate', 'totara_reportbuilder');

        self::assertArrayHasKey($key_none, $options);
        self::assertArrayHasKey($key_transforms, $options);
        self::assertArrayHasKey($key_aggregations, $options);

        self::assertCount(1, $options[$key_none]);

        foreach ($options[$key_transforms] as $key => $value) {
            self::assertStringStartsWith('transform_', $key);
            self::assertIsString($value);
        }

        foreach ($options[$key_aggregations] as $key => $value) {
            self::assertStringStartsWith('aggregate_', $key);
            self::assertIsString($value);
        }
    }

    public function test_get_allowed_advanced_column_options() {
        $source = $this->get_test_report_source();

        $options = $source->get_allowed_advanced_column_options();

        self::assertIsArray($options);
        foreach ($options as $key => $values) {
            self::assertRegExp('/^[a-z0-9_]+-[a-z0-9_]+$/', $key);
            self::assertIsArray($values);
            self::assertNotEmpty($values, 'There should be at least one option.');
            self::assertEmpty(reset($values), 'The first option should always be empty.');
            self::assertSame(count($values), count(array_unique($values)), 'All options should be unique.');
            while ($value = next($values)) {
                self::assertRegExp('/^(aggregate_|transform_)/', $value, 'All options should be either aggregate or transform.');
            }
        }
    }

    public function test_global_restrictions_supported() {
        $source = $this->get_test_report_source();
        self::assertNull($source->global_restrictions_supported());
    }

}
