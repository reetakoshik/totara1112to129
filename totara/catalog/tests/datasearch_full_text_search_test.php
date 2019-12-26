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
class totara_catalog_datasearch_full_text_search_testcase extends advanced_testcase {

    public function validate_current_data_data_provider() {
        return [
            [null, false],
            ['textdata', false],
            [123, true],
            [true, true],
            [[], true],
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
        $filter = new \totara_catalog\datasearch\full_text_search('testfilter');

        if ($expectsexception) {
            $this->expectException(\coding_exception::class);
            $this->expectExceptionMessage('full text search filter only accepts null or string data');
        }

        // The make_compare function for 'full_text_search' calls validate_current_data.
        $filter->set_current_data($data);
    }

    public function test_is_active() {
        // It returns false before we set data.
        $filter = new \totara_catalog\datasearch\full_text_search('testfilter');
        $this->assertEquals(false, $filter->is_active());

        // It returns false after we set null.
        $filter->set_current_data(null);
        $this->assertEquals(false, $filter->is_active());

        // It returns true after we set valid, non-null data, even if it looks 'empty'.
        $filter = new \totara_catalog\datasearch\full_text_search('testfilter');
        $filter->set_current_data("");
        $this->assertEquals(true, $filter->is_active());

        $filter = new \totara_catalog\datasearch\full_text_search('testfilter');
        $filter->set_current_data('0');
        $this->assertEquals(true, $filter->is_active());
    }

    public function make_compare_data_provider() {
        return [
            ['', ['testfield1' => 111], true],
            [null, ['testfield1' => 111], false],
            ['textdata', ['testfield1' => 111, 'testfield2' => 222, 'testfield3' => 333], true],
        ];
    }

    /**
     * @dataProvider make_compare_data_provider
     *
     * @param $data
     * @param array $fieldsandweights
     * @param bool $isactive
     */
    public function test_make_compare($data, array $fieldsandweights, bool $isactive) {
        global $DB;

        $filter = new \totara_catalog\datasearch\full_text_search(
            'testfilter',
            'testbasetable',
            ['testbaseid']
        );
        $filter->set_fields_and_weights($fieldsandweights);
        $filter->add_source(
            'find_text',
            'test_not_used',
            'test_join_alias',
            ['testbaseid' => 'find_text_join_field']
        );

        $filter->set_current_data($data);

        if (!$isactive) {
            $this->expectException(\coding_exception::class);
            $this->expectExceptionMessage('Tried to do full text search without specifying some text');
        }

        list($join, $where, $params) = $filter->make_sql();

        // Build the expected join by calling the DB function. Unfortunately, it contains unique param keys.
        list($ftsjoin, $ftsparams) = $DB->get_fts_subquery('testbasetable', $fieldsandweights, 'placeholder');
        $expectedjoin = "JOIN {$ftsjoin} test_join_alias
                     ON testbasetable.testbaseid = find_text_join_field";

        // Replace the unique param keys in the sql with a known string, so that they can be compared.
        foreach ($ftsparams as $key => $placeholder) {
            $expectedjoin = str_replace($key, 'sometext', $expectedjoin);
        }

        // Likewise with the resulting sql. This also shows that the key must be contained in the join, otherwise
        // things wouldn't match.
        foreach ($params as $key => $value) {
            $join = str_replace($key, 'sometext', $join);
        }

        $this->assertEquals($expectedjoin, $join);

        $this->assertEmpty($where);

        $this->assertCount(count($ftsparams), $params);
        foreach ($params as $key => $value) {
            $this->assertEquals($data, $value);
        }
    }
}
