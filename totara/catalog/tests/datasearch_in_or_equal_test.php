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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_catalog
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_catalog
 */
class totara_catalog_datasearch_in_or_equal_testcase extends advanced_testcase {

    public function validate_current_data_data_provider() {
        return [
            [null, false],
            [[], false],
            [[123], false],
            [['textdata'], false],
            [[true], false],
            [[null], true],
            [[[]], true],
            [[['a']], true],
            [[new stdClass()], true],
        ];
    }

    /**
     * @dataProvider validate_current_data_data_provider
     *
     * @param $data
     * @param $expectsexception
     */
    public function test_validate_current_data($data, bool $expectsexception) {
        $filter = new \totara_catalog\datasearch\in_or_equal('testfilter');

        if ($expectsexception) {
            $this->expectException(\coding_exception::class);
            $this->expectExceptionMessage('in or equal search filter only accepts int, string or bool data in an array');
        }

        // The make_compare function for 'in_or_equal' calls validate_current_data.
        $filter->set_current_data($data);
    }

    public function test_is_active() {
        // It returns false before we set data.
        $filter = new \totara_catalog\datasearch\in_or_equal('testfilter');
        $this->assertEquals(false, $filter->is_active());

        // It returns false after we set null.
        $filter->set_current_data(null);
        $this->assertEquals(false, $filter->is_active());

        // It returns false after we set an empty array.
        $filter = new \totara_catalog\datasearch\in_or_equal('testfilter');
        $filter->set_current_data([]);
        $this->assertEquals(false, $filter->is_active());

        // It returns true after we set valid, non-null data, with at least one item in the array, even if the item looks 'empty'.
        $filter = new \totara_catalog\datasearch\in_or_equal('testfilter');
        $filter->set_current_data([0]);
        $this->assertEquals(true, $filter->is_active());

        $filter = new \totara_catalog\datasearch\in_or_equal('testfilter');
        $filter->set_current_data(['0']);
        $this->assertEquals(true, $filter->is_active());

        $filter = new \totara_catalog\datasearch\in_or_equal('testfilter');
        $filter->set_current_data([false]);
        $this->assertEquals(true, $filter->is_active());
    }

    public function make_compare_data_provider() {
        return [
            [null, false],
            [[], false],
            [[123], true],
            [['textdata'], true],
            [[true], true],
            [[0, false, 'somedata'], true],
        ];
    }

    /**
     * @dataProvider make_compare_data_provider
     *
     * @param $data
     * @param $isactive
     */
    public function test_make_compare($data, bool $isactive) {
        $filter = new \totara_catalog\datasearch\in_or_equal('testfilter');
        $filter->add_source(
            'in_or_equal_to'
        );

        $filter->set_current_data($data);

        if (!$isactive) {
            $this->expectException(\coding_exception::class);
            $this->expectExceptionMessage('Tried to apply \'in_or_equal\' filter with no values specified');
        }

        list($join, $where, $params) = $filter->make_sql();

        $this->assertEmpty($join);
        $this->assertCount(count($data), $params);

        if (count($data) == 1) {
            foreach ($params as $key => $value) {
                $this->assertEquals($data[0], $value);
                $this->assertEquals('in_or_equal_to = :' . $key, $where);
            }
        } else { // Three items.
            $this->assertCount(3, $data);
            $collapsedkeys = "";
            foreach ($params as $key => $value) {
                if (!empty($collapsedkeys)) {
                    $collapsedkeys .= ",";
                }
                $collapsedkeys .= ':' . $key;
                $this->assertContains($value, $data);
            }
            $expectedwhere = "in_or_equal_to IN ({$collapsedkeys})";
            $this->assertEquals($expectedwhere, $where);
        }
    }
}
