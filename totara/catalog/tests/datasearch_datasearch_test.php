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
class totara_catalog_datasearch_datasearch_testcase extends advanced_testcase {

    public function test_add_filter_unique_keys() {
        // Make sure that two filters with different keys are treated separately.
        $filter1 = new \totara_catalog\datasearch\equal('testfilter1');
        $filter1->add_source('testsourcefield1', 'testsourcetable1');

        $filter2 = new \totara_catalog\datasearch\equal('testfilter2');
        $filter2->add_source('testsourcefield2', 'testsourcetable2');

        $df = new \totara_catalog\datasearch\datasearch('testbasetable', 'testoutputcolumns');
        $df->add_filter($filter1);
        $df->add_filter($filter2);
        $filter1->set_current_data('testdata1');
        $filter2->set_current_data('testdata2');

        list($join, $where, $params) = $df->get_filter_joins();

        $this->assertEmpty($join);

        $this->assertContains('testsourcefield1', $where);
        $this->assertContains('testsourcefield2', $where);
        $this->assertContains('AND', $where);

        $this->assertCount(2, $params);
        foreach ($params as $key => $value) {
            $this->assertContains($value, ['testdata1', 'testdata2']);
        }
    }

    public function test_add_filter_common_keys() {
        // Make sure that two filters with the same keys are merged.
        $filter1 = new \totara_catalog\datasearch\equal('testfiltercommon');
        $filter1->add_source('testsourcefield1', 'testsourcetable1');

        $filter2 = new \totara_catalog\datasearch\equal('testfiltercommon');
        $filter2->add_source('testsourcefield2', 'testsourcetable2');

        $df = new \totara_catalog\datasearch\datasearch('testbasetable', 'testoutputcolumns');
        $df->add_filter($filter1);
        $df->add_filter($filter2);
        $filter1->set_current_data('testdata1');
        $filter2->set_current_data('testdata2');

        list($join, $where, $params) = $df->get_filter_joins();

        $this->assertContains('WHERE testsourcefield1', $join);
        $this->assertContains('WHERE testsourcefield2', $join);
        $this->assertContains('UNION', $join);
        $this->assertEquals(1, substr_count($join, 'JOIN'));

        $this->assertEmpty($where);

        $this->assertCount(2, $params);
        foreach ($params as $key => $value) {
            $this->assertContains($value, ['testdata1', 'testdata2']);
        }
    }

    public function test_get_joins_with_separate_joins() {
        // Make sure that two filters with different join tables result in two joins.
        $filter1 = new \totara_catalog\datasearch\equal(
            'testfilter1',
            'testbasealias',
            ['joinonbasefield1']
        );
        $filter1->add_source(
            'testsourcefield1',
            'testsourcetable1',
            'testsourcealias1',
            ['joinonbasefield1' => 'testsourcejoinon1']
        );

        $filter2 = new \totara_catalog\datasearch\equal(
            'testfilter2',
            'testbasealias',
            ['joinonbasefield2']
        );
        $filter2->add_source(
            'testsourcefield2',
            'testsourcetable2',
            'testsourcealias2',
            ['joinonbasefield2' => 'testsourcejoinon2']
        );

        $df = new \totara_catalog\datasearch\datasearch('testbasetable', 'testoutputcolumns');
        $df->add_filter($filter1);
        $df->add_filter($filter2);
        $filter1->set_current_data('testdata1');
        $filter2->set_current_data('testdata2');

        list($join, $where, $params) = $df->get_filter_joins();

        $this->assertContains('testsourcetable1', $join);
        $this->assertContains('testsourcealias1', $join);
        $this->assertContains('testsourcetable2', $join);
        $this->assertContains('testsourcealias2', $join);
        $this->assertEquals(2, substr_count($join, 'JOIN')); // This is the key!

        $this->assertContains('testsourcefield1', $where);
        $this->assertContains('testsourcefield2', $where);
        $this->assertContains('AND', $where);

        $this->assertCount(2, $params);
        foreach ($params as $key => $value) {
            $this->assertContains($value, ['testdata1', 'testdata2']);
        }
    }

    public function test_get_joins_with_duplicate_joins() {
        // Make sure that two filters with the same tables result in one join.
        $filter1 = new \totara_catalog\datasearch\equal(
            'testfilter1',
            'testbasealias',
            ['joinonbasefield']
        );
        $filter1->add_source(
            'testsourcefield1',
            'testsourcetable',
            'testsourcealias',
            ['joinonbasefield' => 'testsourcejoinon']
        );

        $filter2 = new \totara_catalog\datasearch\equal(
            'testfilter2',
            'testbasealias',
            ['joinonbasefield']
        );
        $filter2->add_source(
            'testsourcefield2',
            'testsourcetable',
            'testsourcealias',
            ['joinonbasefield' => 'testsourcejoinon']
        );

        $df = new \totara_catalog\datasearch\datasearch('testbasetable', 'testoutputcolumns');
        $df->add_filter($filter1);
        $df->add_filter($filter2);
        $filter1->set_current_data('testdata1');
        $filter2->set_current_data('testdata2');

        list($join, $where, $params) = $df->get_filter_joins();

        $this->assertContains('testsourcetable', $join);
        $this->assertContains('testsourcealias', $join);
        $this->assertEquals(1, substr_count($join, 'JOIN')); // This is the key!

        $this->assertContains('testsourcefield1', $where);
        $this->assertContains('testsourcefield2', $where);
        $this->assertContains('AND', $where);

        $this->assertCount(2, $params);
        foreach ($params as $key => $value) {
            $this->assertContains($value, ['testdata1', 'testdata2']);
        }
    }

    public function test_get_joins_single_where_clause() {
        $filter = new \totara_catalog\datasearch\equal('testfilter');
        $filter->add_source('testsourcefield');

        $df = new \totara_catalog\datasearch\datasearch('testbasetable', 'testoutputcolumns');
        $df->add_filter($filter);
        $filter->set_current_data('testdata');

        list($join, $where, $params) = $df->get_filter_joins();

        $this->assertEmpty($join);

        $this->assertContains('testsourcefield', $where);
        $this->assertNotContains('AND', $where); // This is the key!

        $this->assertCount(1, $params);
        foreach ($params as $key => $value) {
            $this->assertEquals('testdata', $value);
        }
    }

    public function test_get_joins_multiple_where_clauses() {
        $filter1 = new \totara_catalog\datasearch\equal('testfilter1');
        $filter1->add_source('testsourcefield1');

        $filter2 = new \totara_catalog\datasearch\equal('testfilter2');
        $filter2->add_source('testsourcefield2');

        $filter3 = new \totara_catalog\datasearch\equal('testfilter3');
        $filter3->add_source('testsourcefield3');

        $df = new \totara_catalog\datasearch\datasearch('testbasetable', 'testoutputcolumns');
        $df->add_filter($filter1);
        $df->add_filter($filter2);
        $df->add_filter($filter3);
        $filter1->set_current_data('testdata1');
        $filter2->set_current_data('testdata2');
        $filter3->set_current_data('testdata3');

        list($join, $where, $params) = $df->get_filter_joins();

        $this->assertEmpty($join);

        $this->assertContains('testsourcefield', $where);
        $this->assertEquals(2, substr_count($where, 'AND')); // This is the key!

        $this->assertCount(3, $params);
        foreach ($params as $key => $value) {
            $this->assertContains($value, ['testdata1', 'testdata2', 'testdata3']);
        }
    }

    public function test_get_joins_no_filters() {
        $df = new \totara_catalog\datasearch\datasearch('testbasetable', 'testoutputcolumns');

        list($join, $where, $params) = $df->get_filter_joins();

        $this->assertEmpty($join);
        $this->assertEmpty($where);
        $this->assertEmpty($params);
    }

    public function test_get_sql_no_filters() {
        $df = new \totara_catalog\datasearch\datasearch('testbasetable', 'testoutputcolumns');

        list($selectsql, $countsql, $params) = $df->get_sql();

        // Make sure there is only stuff we are expecting.
        $this->assertContains("SELECT DISTINCT testoutputcolumns", $selectsql);
        $this->assertContains("FROM testbasetable", $selectsql);
        $this->assertNotContains("WHERE", $selectsql);
        $this->assertNotContains("JOIN", $selectsql);
        $this->assertNotContains("ORDER BY", $selectsql);

        // Confirm that count and normal select are the same base query.
        $expectedcountsql = str_replace("SELECT DISTINCT testoutputcolumns", "SELECT COUNT(1)", $selectsql);
        $this->assertEquals($expectedcountsql, $countsql);

        $this->assertEmpty($params);
    }

    public function test_get_sql_order_by() {
        $df = new \totara_catalog\datasearch\datasearch('testbasetable', 'testoutputcolumns', 'testsortby');

        list($selectsql, $countsql, $params) = $df->get_sql();

        // Make sure there is only stuff we are expecting.
        $this->assertContains("SELECT DISTINCT testoutputcolumns", $selectsql);
        $this->assertContains("FROM testbasetable", $selectsql);
        $this->assertContains("ORDER BY testsortby", $selectsql);
        $this->assertNotContains("WHERE", $selectsql);
        $this->assertNotContains("JOIN", $selectsql);

        // Confirm that count doesn't contain an order by.
        $this->assertNotContains("ORDER BY", $countsql);

        $this->assertEmpty($params);
    }

    public function test_get_sql_with_join() {
        $filter1 = new \totara_catalog\datasearch\equal('testfilter');
        $filter1->add_source('testsourcefield1');

        $filter2 = new \totara_catalog\datasearch\equal('testfilter');
        $filter2->add_source('testsourcefield2');

        $df = new \totara_catalog\datasearch\datasearch('testbasetable', 'testoutputcolumns');
        $df->add_filter($filter1);
        $df->add_filter($filter2);
        $filter1->set_current_data('testdata1');
        $filter2->set_current_data('testdata2');

        list($selectsql, $countsql, $params) = $df->get_sql();
        list($expectedjoin, $expectedwhere, $expectedparams) = $df->get_filter_joins();

        $this->assertNotEmpty($expectedjoin);
        $this->assertEmpty($expectedwhere);

        $this->assertContains("SELECT DISTINCT testoutputcolumns", $selectsql);
        $this->assertContains("FROM testbasetable", $selectsql);
        $this->assertContains("JOIN", $selectsql);
        $this->assertEquals(3, substr_count($selectsql, 'SELECT'));
        $this->assertEquals(3, substr_count($selectsql, 'FROM'));
        $this->assertEquals(1, substr_count($selectsql, 'UNION'));
        $this->assertContains("WHERE testsourcefield1 = ", $selectsql);
        $this->assertContains("WHERE testsourcefield2 = ", $selectsql);
        $this->assertContains("testfilter", $selectsql);
        $this->assertNotContains("ORDER BY", $selectsql);

        // Confirm that count and normal select are the same base query.
        $expectedcountsql = str_replace("SELECT DISTINCT testoutputcolumns", "SELECT COUNT(1)", $selectsql);
        $this->assertEquals($expectedcountsql, $countsql);

        $this->assertEquals(array_values($expectedparams), array_values($params));
    }

    public function test_get_sql_with_where() {
        $filter1 = new \totara_catalog\datasearch\equal('testfilter1');
        $filter1->add_source('testsourcefield1');

        $filter2 = new \totara_catalog\datasearch\equal('testfilter2');
        $filter2->add_source('testsourcefield2');

        $df = new \totara_catalog\datasearch\datasearch('testbasetable', 'testoutputcolumns');
        $df->add_filter($filter1);
        $df->add_filter($filter2);
        $filter1->set_current_data('testdata1');
        $filter2->set_current_data('testdata2');

        list($selectsql, $countsql, $params) = $df->get_sql();
        list($expectedjoin, $expectedwhere, $expectedparams) = $df->get_filter_joins();

        $this->assertEmpty($expectedjoin);
        $this->assertNotEmpty($expectedwhere);

        $this->assertContains("SELECT DISTINCT testoutputcolumns", $selectsql);
        $this->assertContains("FROM testbasetable", $selectsql);
        $this->assertContains("WHERE", $selectsql);
        $this->assertContains("testsourcefield1 = ", $selectsql);
        $this->assertContains("AND", $selectsql);
        $this->assertContains("testsourcefield2 = ", $selectsql);
        $this->assertNotContains("JOIN", $selectsql);
        $this->assertNotContains("ORDER BY", $selectsql);

        // Confirm that count and normal select are the same base query.
        $expectedcountsql = str_replace("SELECT DISTINCT testoutputcolumns", "SELECT COUNT(1)", $selectsql);
        $this->assertEquals($expectedcountsql, $countsql);

        $this->assertEquals(array_values($expectedparams), array_values($params));
    }
}
