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
class totara_catalog_datasearch_equal_testcase extends advanced_testcase {

    public function validate_current_data_data_provider() {
        return [
            [null, false],
            [123, false],
            ['textdata', false],
            [true, false],
            [[], true],
            [['a'], true],
            [new stdClass(), true],
        ];
    }

    /**
     * @dataProvider validate_current_data_data_provider
     *
     * @param $data
     * @param $expectsexception
     */
    public function test_validate_current_data($data, bool $expectsexception) {
        $filter = new \totara_catalog\datasearch\equal('testfilter');

        if ($expectsexception) {
            $this->expectException(\coding_exception::class);
            $this->expectExceptionMessage('equal filter only accepts null, int, string or bool data');
        }

        // The make_compare function for 'equal' calls validate_current_data.
        $filter->set_current_data($data);
    }

    public function test_is_active() {
        // It returns false before we set data.
        $filter = new \totara_catalog\datasearch\equal('testfilter');
        $this->assertEquals(false, $filter->is_active());

        // It returns false after we set null.
        $filter->set_current_data(null);
        $this->assertEquals(false, $filter->is_active());

        // It returns true after we set valid, non-null data, even if it looks 'empty'.
        $filter = new \totara_catalog\datasearch\equal('testfilter');
        $filter->set_current_data(0);
        $this->assertEquals(true, $filter->is_active());

        $filter = new \totara_catalog\datasearch\equal('testfilter');
        $filter->set_current_data('0');
        $this->assertEquals(true, $filter->is_active());

        $filter = new \totara_catalog\datasearch\equal('testfilter');
        $filter->set_current_data(false);
        $this->assertEquals(true, $filter->is_active());
    }

    public function provider_compare_data() {
        return [
            [null, false],
            [123, true],
            ['textdata', true],
            [true, true],
        ];
    }

    /**
     * @dataProvider provider_compare_data
     *
     * @param $data
     * @param $isactive
     */
    public function test_make_compare($data, bool $isactive) {
        $filter = new \totara_catalog\datasearch\equal('testfilter');
        $filter->add_source(
            'equate_to'
        );

        $filter->set_current_data($data);

        if (!$isactive) {
            $this->expectException(\coding_exception::class);
            $this->expectExceptionMessage('Tried to apply \'equal\' filter with no value specified');
        }

        list($join, $where, $params) = $filter->make_sql();

        $this->assertEmpty($join);
        $this->assertCount(1, $params);
        foreach ($params as $key => $value) {
            $this->assertEquals($data, $value);
            $this->assertEquals('equate_to = :' . $key, $where);
        }
    }
}
