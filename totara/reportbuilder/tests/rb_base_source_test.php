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

    public function test_get_report_source() {
        global $CFG;

        require_once($CFG->dirroot . '/totara/reportbuilder/tests/fixtures/phpunit_test_report_source.php');

        $source = new phpunit_test_report_source();
        self::assertInstanceOf('phpunit_test_report_source', $source);
        self::assertInstanceOf('rb_base_source', $source);
        return $source;
    }

    /**
     * @depends test_get_report_source
     */
    public function test_get_all_advanced_column_options(rb_base_source $source) {
        $options = $source->get_all_advanced_column_options();
        self::assertInternalType('array', $options);
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
            self::assertInternalType('string', $value);
        }

        foreach ($options[$key_aggregations] as $key => $value) {
            self::assertStringStartsWith('aggregate_', $key);
            self::assertInternalType('string', $value);
        }
    }

    /**
     * @depends test_get_report_source
     */
    public function test_get_allowed_advanced_column_options(rb_base_source $source) {
        $options = $source->get_allowed_advanced_column_options();

        self::assertInternalType('array', $options);
        foreach ($options as $key => $values) {
            self::assertRegExp('/^[a-z0-9_]+-[a-z0-9_]+$/', $key);
            self::assertInternalType('array', $values);
            self::assertNotEmpty($values, 'There should be at least one option.');
            self::assertEmpty(reset($values), 'The first option should always be empty.');
            self::assertSame(count($values), count(array_unique($values)), 'All options should be unique.');
            while ($value = next($values)) {
                self::assertRegExp('/^(aggregate_|transform_)/', $value, 'All options should be either aggregate or transform.');
            }
        }
    }

    /**
     * @depends test_get_report_source
     */
    public function test_global_restrictions_supported(phpunit_test_report_source $source) {
        self::assertNull($source->global_restrictions_supported());
    }

    /**
     * @depends test_get_report_source
     */
    public function test_rb_display_nice_time(phpunit_test_report_source $source) {
        $row = new stdClass;
        self::assertSame('11:25', $source->rb_display_nice_time(1514345115, $row));
        self::assertSame('11:25', $source->rb_display_nice_time('1514345115', $row));
        self::assertSame('', $source->rb_display_nice_time('blah', $row));
    }

    /**
     * @depends test_get_report_source
     */
    public function test_rb_display_nice_datetime_in_timezone(phpunit_test_report_source $source) {
        global $CFG;
        $row = new stdClass;
        self::assertEquals('99', $CFG->forcetimezone);

        $CFG->forcetimezone = '	Australia/Perth';
        self::assertSame('27 December 2017, 11:25 AM Unknown Timezone', $source->rb_display_nice_datetime_in_timezone(1514345115, $row));
        self::assertSame('27 December 2017, 11:25 AM Unknown Timezone', $source->rb_display_nice_datetime_in_timezone('1514345115', $row));
        self::assertSame('', $source->rb_display_nice_datetime_in_timezone('blah', $row));

        $row->timezone = 'Pacific/Auckland';
        self::assertSame('27 December 2017, 4:25 PM Pacific/Auckland', $source->rb_display_nice_datetime_in_timezone(1514345115, $row));
        self::assertSame('27 December 2017, 4:25 PM Pacific/Auckland', $source->rb_display_nice_datetime_in_timezone('1514345115', $row));
        self::assertSame('', $source->rb_display_nice_datetime_in_timezone('blah', $row));

        $row->timezone = '	Australia/Perth';
        self::assertSame('27 December 2017, 11:25 AM Australia/Perth', $source->rb_display_nice_datetime_in_timezone(1514345115, $row));
        self::assertSame('27 December 2017, 11:25 AM Australia/Perth', $source->rb_display_nice_datetime_in_timezone('1514345115', $row));
        self::assertSame('', $source->rb_display_nice_datetime_in_timezone('blah', $row));

        // Reset.
        $CFG->forcetimezone = '99';
    }

    /**
     * @depends test_get_report_source
     */
    public function test_rb_display_delimitedlist_date_in_timezone(phpunit_test_report_source $source) {
        global $CFG;
        $row = new stdClass;
        self::assertEquals('99', $CFG->forcetimezone);

        $CFG->forcetimezone = '	Australia/Perth';
        self::assertSame('27 December 2017, 11:25 AM Unknown Timezone', $source->rb_display_delimitedlist_date_in_timezone(1514345115, $row));
        self::assertSame('27 December 2017, 11:25 AM Unknown Timezone', $source->rb_display_delimitedlist_date_in_timezone('1514345115', $row));
        self::assertSame('-', $source->rb_display_delimitedlist_date_in_timezone('blah', $row));

        $row->timezone = 'Pacific/Auckland';
        self::assertSame('27 December 2017, 4:25 PM Pacific/Auckland', $source->rb_display_delimitedlist_date_in_timezone(1514345115, $row));
        self::assertSame('27 December 2017, 4:25 PM Pacific/Auckland', $source->rb_display_delimitedlist_date_in_timezone('1514345115', $row));
        self::assertSame('-', $source->rb_display_delimitedlist_date_in_timezone('blah', $row));

        $row->timezone = '	Australia/Perth';
        self::assertSame('27 December 2017, 11:25 AM Australia/Perth', $source->rb_display_delimitedlist_date_in_timezone(1514345115, $row));
        self::assertSame('27 December 2017, 11:25 AM Australia/Perth', $source->rb_display_delimitedlist_date_in_timezone('1514345115', $row));
        self::assertSame('-', $source->rb_display_delimitedlist_date_in_timezone('blah', $row));

        // Reset.
        $CFG->forcetimezone = '99';
    }

    /**
     * @depends test_get_report_source
     */
    public function test_rb_display_delimitedlist_datetime_in_timezone(phpunit_test_report_source $source) {
        global $CFG;
        $row = new stdClass;
        self::assertEquals('99', $CFG->forcetimezone);

        $CFG->forcetimezone = '	Australia/Perth';
        self::assertSame('27 December 2017, 11:25 AM Unknown Timezone', $source->rb_display_delimitedlist_datetime_in_timezone(1514345115, $row));
        self::assertSame('27 December 2017, 11:25 AM Unknown Timezone', $source->rb_display_delimitedlist_datetime_in_timezone('1514345115', $row));
        self::assertSame('-', $source->rb_display_delimitedlist_datetime_in_timezone('blah', $row));

        $row->timezone = 'Pacific/Auckland';
        self::assertSame('27 December 2017, 4:25 PM Pacific/Auckland', $source->rb_display_delimitedlist_datetime_in_timezone('1514345115', $row));
        self::assertSame('-', $source->rb_display_delimitedlist_datetime_in_timezone('blah', $row));

        $row->timezone = '	Australia/Perth';
        self::assertSame('27 December 2017, 11:25 AM Australia/Perth', $source->rb_display_delimitedlist_datetime_in_timezone(1514345115, $row));
        self::assertSame('-', $source->rb_display_delimitedlist_datetime_in_timezone('blah', $row));

        // Reset.
        $CFG->forcetimezone = '99';
    }

    /**
     * @depends test_get_report_source
     */
    public function test_rb_display_nice_two_datetime_in_timezone_twodates(phpunit_test_report_source $source) {
        global $CFG;
        $row = new stdClass;
        $row->finishdate = 1514345115 + 86400;
        self::assertEquals('99', $CFG->forcetimezone);

        $CFG->forcetimezone = '	Australia/Perth';
        self::assertSame('27 December 2017, 11:25 AM Australia/Perth to 28 December 2017, 11:25 AM Australia/Perth', $source->rb_display_nice_two_datetime_in_timezone(1514345115, $row));
        self::assertSame('27 December 2017, 11:25 AM Australia/Perth to 28 December 2017, 11:25 AM Australia/Perth', $source->rb_display_nice_two_datetime_in_timezone('1514345115', $row));
        self::assertSame('Before 28 December 2017, 11:25 AM Australia/Perth', $source->rb_display_nice_two_datetime_in_timezone('blah', $row));

        $row->timezone = 'Pacific/Auckland';
        self::assertSame('27 December 2017, 4:25 PM Pacific/Auckland to 28 December 2017, 4:25 PM Pacific/Auckland', $source->rb_display_nice_two_datetime_in_timezone('1514345115', $row));
        self::assertSame('Before 28 December 2017, 4:25 PM Pacific/Auckland', $source->rb_display_nice_two_datetime_in_timezone('blah', $row));

        $row->timezone = '	Australia/Perth';
        self::assertSame('27 December 2017, 11:25 AM Australia/Perth to 28 December 2017, 11:25 AM Australia/Perth', $source->rb_display_nice_two_datetime_in_timezone(1514345115, $row));
        self::assertSame('Before 28 December 2017, 11:25 AM Australia/Perth', $source->rb_display_nice_two_datetime_in_timezone('blah', $row));

        // Reset.
        $CFG->forcetimezone = '99';
    }

    /**
     * @depends test_get_report_source
     */
    public function test_rb_display_nice_two_datetime_in_timezone_startdate_only(phpunit_test_report_source $source) {
        global $CFG;
        $row = new stdClass;
        $row->finishdate = null;
        self::assertEquals('99', $CFG->forcetimezone);

        $CFG->forcetimezone = '	Australia/Perth';
        self::assertSame('After 27 December 2017, 11:25 AM Australia/Perth', $source->rb_display_nice_two_datetime_in_timezone(1514345115, $row));
        self::assertSame('After 27 December 2017, 11:25 AM Australia/Perth', $source->rb_display_nice_two_datetime_in_timezone('1514345115', $row));
        self::assertSame('', $source->rb_display_nice_two_datetime_in_timezone('blah', $row));

        $row->timezone = 'Pacific/Auckland';
        self::assertSame('After 27 December 2017, 4:25 PM Pacific/Auckland', $source->rb_display_nice_two_datetime_in_timezone('1514345115', $row));
        self::assertSame('', $source->rb_display_nice_two_datetime_in_timezone('blah', $row));

        $row->timezone = '	Australia/Perth';
        self::assertSame('After 27 December 2017, 11:25 AM Australia/Perth', $source->rb_display_nice_two_datetime_in_timezone(1514345115, $row));
        self::assertSame('', $source->rb_display_nice_two_datetime_in_timezone('blah', $row));

        // Reset.
        $CFG->forcetimezone = '99';
    }

    /**
     * @depends test_get_report_source
     */
    public function test_rb_display_nice_datetime_seconds(phpunit_test_report_source $source) {
        $row = new stdClass;
        self::assertSame('27 Dec 2017 at 11:25:15', $source->rb_display_nice_datetime_seconds(1514345115, $row));
        self::assertSame('27 Dec 2017 at 11:25:15', $source->rb_display_nice_datetime_seconds('1514345115', $row));
        self::assertSame('', $source->rb_display_nice_datetime_seconds('blah', $row));
    }

    /**
     * @depends test_get_report_source
     */
    public function test_rb_display_round2(phpunit_test_report_source $source) {

        $row = new stdClass;
        self::assertSame('2.02', $source->rb_display_round2(2.02, $row));
        self::assertSame('2.22', $source->rb_display_round2(2.22, $row));
        self::assertSame('2.20', $source->rb_display_round2(2.2, $row));
        self::assertSame('0.22', $source->rb_display_round2(0.22, $row));
        self::assertSame('0.20', $source->rb_display_round2(0.2, $row));
        self::assertSame('0.00', $source->rb_display_round2(0.0, $row));
        self::assertSame('0.00', $source->rb_display_round2(0, $row));
        self::assertSame('2.00', $source->rb_display_round2(2, $row));
        self::assertSame('-', $source->rb_display_round2('', $row));
        self::assertSame('-', $source->rb_display_round2(null, $row));
        self::assertSame('0.00', $source->rb_display_round2('blah', $row));
        self::assertSame('2.00', $source->rb_display_round2(0x02, $row));

    }

    /**
     * @depends test_get_report_source
     */
    public function test_rb_display_percent(phpunit_test_report_source $source) {

        $row = new stdClass;
        self::assertSame('2.0%', $source->rb_display_percent(2.02, $row));
        self::assertSame('2.2%', $source->rb_display_percent(2.22, $row));
        self::assertSame('2.2%', $source->rb_display_percent(2.2, $row));
        self::assertSame('0.2%', $source->rb_display_percent(0.22, $row));
        self::assertSame('0.2%', $source->rb_display_percent(0.2, $row));
        self::assertSame('0.0%', $source->rb_display_percent(0.0, $row));
        self::assertSame('0.0%', $source->rb_display_percent(0, $row));
        self::assertSame('2.0%', $source->rb_display_percent(2, $row));
        self::assertSame('99.0%', $source->rb_display_percent(99, $row));
        self::assertSame('99.9%', $source->rb_display_percent(99.9, $row));
        self::assertSame('100.0%', $source->rb_display_percent(99.99, $row));
        self::assertSame('100.0%', $source->rb_display_percent(100.01, $row));
        self::assertSame('100.1%', $source->rb_display_percent(100.1, $row));
        self::assertSame('100.2%', $source->rb_display_percent(100.19, $row));
        self::assertSame('-', $source->rb_display_percent('', $row));
        self::assertSame('-', $source->rb_display_percent(null, $row));
        self::assertSame('0.0%', $source->rb_display_percent('blah', $row));
        self::assertSame('2.0%', $source->rb_display_percent(0x02, $row));

    }

    /**
     * @depends test_get_report_source
     */
    public function test_rb_display_list_to_newline(phpunit_test_report_source $source) {

        $row = new stdClass;
        self::assertSame("one\ntwo\nthree", $source->rb_display_list_to_newline("one, two, three", $row));
        self::assertSame("one\n-\nthree", $source->rb_display_list_to_newline("one, , three", $row));
        self::assertSame("one", $source->rb_display_list_to_newline("one", $row));
        self::assertSame("one\ntwo", $source->rb_display_list_to_newline("one\ntwo", $row));
        self::assertSame("one,two,three", $source->rb_display_list_to_newline("one,two,three", $row));
        self::assertSame("one\n-\nthree", $source->rb_display_list_to_newline("one, 0, three", $row));
        self::assertSame(",", $source->rb_display_list_to_newline(",", $row));
        self::assertSame(",\n,,", $source->rb_display_list_to_newline(",, ,,", $row));

    }

    /**
     * @depends test_get_report_source
     */
    public function test_rb_display_delimitedlist_to_newline(phpunit_test_report_source $source) {

        $d = $source->get_uniquedelimiter();

        $row = new stdClass;
        self::assertSame("one\ntwo\nthree", $source->rb_display_delimitedlist_to_newline("one{$d}two{$d}three", $row));
        self::assertSame("one\n-\nthree", $source->rb_display_delimitedlist_to_newline("one{$d}{$d}three", $row));
        self::assertSame("one", $source->rb_display_delimitedlist_to_newline("one", $row));
        self::assertSame("one,two,three", $source->rb_display_delimitedlist_to_newline("one,two,three", $row));
        self::assertSame("one\n-\nthree", $source->rb_display_delimitedlist_to_newline("one{$d} {$d}three", $row));
        self::assertSame("one\n-\nthree", $source->rb_display_delimitedlist_to_newline("one{$d}    {$d}three", $row));
        self::assertSame("one\n-\nthree", $source->rb_display_delimitedlist_to_newline("one{$d}0{$d}three", $row));
        self::assertSame("one\n0000\nthree", $source->rb_display_delimitedlist_to_newline("one{$d}0000{$d}three", $row));
        self::assertSame(",", $source->rb_display_delimitedlist_to_newline(",", $row));
        self::assertSame(",\n,,", $source->rb_display_delimitedlist_to_newline(",{$d},,", $row));

    }

    /**
     * @depends test_get_report_source
     */
    public function test_rb_display_delimitedlist_multi_to_newline(phpunit_test_report_source $source) {

        $d = $source->get_uniquedelimiter();

        $object = json_encode([['option' => 'one'], ['option' => 'two']]);
        $row = new stdClass;
        self::assertSame("one, two", $source->rb_display_delimitedlist_multi_to_newline($object, $row));
        self::assertSame("one, two\none, two", $source->rb_display_delimitedlist_multi_to_newline($object.$d.$object, $row));
        self::assertSame("one, two\none, two\none, two", $source->rb_display_delimitedlist_multi_to_newline($object.$d.$object.$d.$object, $row));

    }

    /**
     * @depends test_get_report_source
     */
    public function test_rb_display_delimitedlist_url_to_newline(phpunit_test_report_source $source) {

        $d = $source->get_uniquedelimiter();

        $object = json_encode(['text' => 'One', 'url' => '#1']);
        $row = new stdClass;
        self::assertSame("<a href=\"#1\">One</a>", $source->rb_display_delimitedlist_url_to_newline($object, $row));
        self::assertSame("<a href=\"#1\">One</a>\n<a href=\"#1\">One</a>", $source->rb_display_delimitedlist_url_to_newline($object.$d.$object, $row));
        self::assertSame("<a href=\"#1\">One</a>\n<a href=\"#1\">One</a>\n<a href=\"#1\">One</a>", $source->rb_display_delimitedlist_url_to_newline($object.$d.$object.$d.$object, $row));

    }

}
