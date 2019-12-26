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
class totara_catalog_datasearch_filter_testcase extends advanced_testcase {

    public function test_get_alias() {
        $filter = new \totara_catalog\datasearch\like('testalias');

        $this->assertEquals('testalias', $filter->get_alias());
    }

    public function test_set_current_data() {
        $filter = new \totara_catalog\datasearch\equal('testalias');
        $filter->add_source(
            'testsourcefield'
        );

        $filter->set_current_data('testtext');

        list($join, $where, $params) = $filter->make_sql();
        $this->assertCount(1, $params);
        foreach ($params as $key => $value) {
            $this->assertEquals('testtext', $value);
        }
    }

    public function test_add_source_missing_joinonbasefields() {
        $filter = new \totara_catalog\datasearch\equal(
            'testalias',
            'testbasealias',
            ['testfield1', 'testfield2', 'testfield3']
        );

        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('$joinons must contain the same keys as $this->joinonbasefields');
        $filter->add_source(
            'testsourcefield'
        );
    }

    public function test_add_source_extra_joinonbasefields() {
        $filter = new \totara_catalog\datasearch\equal(
            'testalias'
        );

        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('$joinons must contain the same keys as $this->joinonbasefields');
        $filter->add_source(
            'testsourcefield',
            'testsourcetable',
            'testsourcealias',
            ['testjoinon1a' => 'testjoinon1b']
        );
    }

    public function test_add_source() {
        $filter = new \totara_catalog\datasearch\equal(
            'testalias',
            'testbasealias',
            ['testfield1', 'testfield2']
        );

        $filter->add_source(
            'testsourcefield1',
            'testsourcetable1',
            'testsourcealias1',
            [
                'testfield1' => 'testjoinfield1',
                'testfield2' => 'testjoinfield2',
            ],
            'testadditionalcriteria1',
            [
                'testadditionalparam1' => 'testadditionaldata1',
                'testadditionalparam2' => 'testadditionaldata2',
            ],
            [
                'testadditionalselectbase1' => 'testadditionalselectfield1',
                'testadditionalselectbase2' => 'testadditionalselectfield2',
            ]
        );

        $filter->add_source(
            'testsourcefield3',
            'testsourcetable3',
            'testsourcealias3',
            [
                'testfield1' => 'testjoinfield3',
                'testfield2' => 'testjoinfield4',
            ]
        );

        $filter->set_current_data('testdata');

        list($join, $where, $params) = $filter->make_sql();

        $this->assertContains('testjoinfield1 AS testfield1', $join); // Joinons.
        $this->assertContains('testjoinfield2 AS testfield2', $join); // Joinons.
        $this->assertContains('testadditionalselectfield1 AS testadditionalselectbase1', $join); // Additional select.
        $this->assertContains('testadditionalselectfield2 AS testadditionalselectbase2', $join); // Additional select.
        $this->assertContains('testsourcetable1 testsourcealias1', $join); // Table, alias.
        $this->assertContains('testsourcefield1 = :', $join); // Filterfield.
        $this->assertContains('AND testadditionalcriteria1', $join); // Additionalcriteria.
        $this->assertContains('testjoinfield3 AS testfield1', $join);
        $this->assertContains('testjoinfield4 AS testfield2', $join);
        $this->assertContains('testsourcetable3 testsourcealias3', $join);
        $this->assertContains('testsourcefield3 = :', $join);

        $this->assertEmpty($where);

        $this->assertCount(4, $params);
        $this->assertEquals('testadditionaldata1', $params['testadditionalparam1']); // Additionlparams.
        $this->assertEquals('testadditionaldata2', $params['testadditionalparam2']); // Additionlparams.
    }

    public function test_can_merge() {
        $filter = new \totara_catalog\datasearch\equal(
            'testalias',
            'testbasealias',
            ['testfield1', 'testfield2']
        );

        // Filters with different aliases cannot be merged.
        $otherfilter = new \totara_catalog\datasearch\equal(
            'testalias2',
            'testbasealias',
            ['testfield1', 'testfield2']
        );
        $this->assertFalse($filter->can_merge($otherfilter));

        // Filters with different classes cannot be merged (same alias).
        $otherfilter = new \totara_catalog\datasearch\like(
            'testalias',
            'testbasealias',
            ['testfield1', 'testfield2']
        );
        $this->assertFalse($filter->can_merge($otherfilter));

        // Filters with different base join alias cannot be merged (same alias, class).
        $otherfilter = new \totara_catalog\datasearch\equal(
            'testalias',
            'testbasealias2',
            ['testfield1', 'testfield2']
        );
        $this->assertFalse($filter->can_merge($otherfilter));

        // Filters with different base join fields cannot be merged (same alias, class, base join alias).
        $otherfilter = new \totara_catalog\datasearch\equal(
            'testalias',
            'testbasealias',
            ['testfield1', 'testfield3']
        );
        $this->assertFalse($filter->can_merge($otherfilter));

        // Filters with different join types cannot be merged (same alias, class, base join alias, base join fields).
        $otherfilter = new \totara_catalog\datasearch\equal(
            'testalias',
            'testbasealias',
            ['testfield1', 'testfield2'],
            'NON-DEFAULT_JOIN_TYPE'
        );
        $this->assertFalse($filter->can_merge($otherfilter));

        // Filters with everything the same can be merged.
        $otherfilter = new \totara_catalog\datasearch\equal(
            'testalias',
            'testbasealias',
            ['testfield1', 'testfield2']
        );
        $this->assertTrue($filter->can_merge($otherfilter));
    }

    public function test_can_merge_exception1() {
        $filter = new \totara_catalog\datasearch\equal(
            'testalias',
            'testbasealias',
            ['testfield1', 'testfield2']
        );
        $otherfilter = new \totara_catalog\datasearch\equal(
            'testalias',
            'testbasealias',
            ['testfield1', 'testfield2']
        );
        $filter->set_current_data('testdata');
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('Shouldn\'t be checking if filters can be merged if data has already been set');
        $filter->can_merge($otherfilter);
    }

    public function test_can_merge_exception2() {
        $filter = new \totara_catalog\datasearch\equal(
            'testalias',
            'testbasealias',
            ['testfield1', 'testfield2']
        );
        $otherfilter = new \totara_catalog\datasearch\equal(
            'testalias',
            'testbasealias',
            ['testfield1', 'testfield2']
        );
        $otherfilter->set_current_data('testdata');
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('Shouldn\'t be checking if filters can be merged if data has already been set');
        $filter->can_merge($otherfilter);
    }

    public function test_merge_exception() {
        $filter = new \totara_catalog\datasearch\equal(
            'testalias',
            'testbasealias',
            ['testfield1', 'testfield2']
        );
        $otherfilter = new \totara_catalog\datasearch\equal(
            'testalias',
            'testbasealias',
            ['testfield1', 'testfield3']
        );
        $this->assertFalse($filter->can_merge($otherfilter)); // Can_merge says false...
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('Tried to merge datasearch filters which don\'t match');
        $filter->merge($otherfilter); // But we ignore it, to our own demise.
    }

    public function test_merge() {
        $filter1 = new \totara_catalog\datasearch\equal('testfiltercommon');
        $filter1->add_source('testsourcefield1', 'testsourcetable1');
        $this->assertCount(1, $filter1->sources);
        $filter1source = $filter1->sources[0];

        $filter2 = new \totara_catalog\datasearch\equal('testfiltercommon');
        $filter2->add_source('testsourcefield2', 'testsourcetable2');
        $this->assertCount(1, $filter2->sources);
        $filter2source = $filter1->sources[0];

        $this->assertTrue($filter1->can_merge($filter2));

        $filter1->merge($filter2);

        $this->assertCount(1, $filter2->sources); // Unchanged.
        $this->assertCount(2, $filter1->sources); // Merged.

        // Both sources are contained in the first filter's source list.
        $sources = $filter1->sources;
        $this->assertContains($filter1source, $sources);
        $this->assertContains($filter2source, $sources);
    }

    public function test_make_sql_no_source() {
        $filter = new \totara_catalog\datasearch\equal('testfilter');

        list($join, $where, $params) = $filter->make_sql();

        $this->assertEmpty($join);
        $this->assertEmpty($where);
        $this->assertEmpty($params);
    }

    public function test_make_sql_one_source_no_base_join() {
        // All filter, no additional criteria, results in empty where.
        $filter = new \totara_catalog\datasearch\all(
            'testfilter'
        );
        $filter->add_source(
            'testsourcefield'
        );
        $filter->set_current_data('testdata');

        list($join, $where, $params) = $filter->make_sql();

        $this->assertEmpty($join);
        $this->assertEmpty($where);
        $this->assertEmpty($params);

        // All, with additional criteria, results in some where and params.
        $filter = new \totara_catalog\datasearch\all(
            'testfilter'
        );
        $filter->add_source(
            'testsourcefield',
            'testsourcetable',
            'testsourcealias',
            [],
            'testadditionalcriteria',
            [
                'testadditionalparam1' => 'testadditionaldata1',
                'testadditionalparam2' => 'testadditionaldata2',
            ]
        );
        $filter->set_current_data('testdata');

        list($join, $where, $params) = $filter->make_sql();

        $this->assertEmpty($join);
        $this->assertEquals('testadditionalcriteria', $where);
        $this->assertEquals(
            [
                'testadditionalparam1' => 'testadditionaldata1',
                'testadditionalparam2' => 'testadditionaldata2',
            ],
            $params
        );

        // Equal, no additional criteria, results in some where and param.
        $filter = new \totara_catalog\datasearch\equal(
            'testfilter'
        );
        $filter->add_source(
            'testsourcefield'
        );
        $filter->set_current_data('testdata');

        list($join, $where, $params) = $filter->make_sql();

        $this->assertEmpty($join);
        $this->assertContains('testsourcefield = :', $where);
        $this->assertCount(1, $params);
        foreach ($params as $param => $value) {
            $this->assertEquals('testdata', $value);
        }

        // Equal, with additional criteria, results in some where and params.
        $filter = new \totara_catalog\datasearch\equal(
            'testfilter'
        );
        $filter->add_source(
            'testsourcefield',
            'testsourcetable',
            'testsourcealias',
            [],
            'testadditionalcriteria',
            [
                'testadditionalparam1' => 'testadditionaldata1',
                'testadditionalparam2' => 'testadditionaldata2',
            ]
        );
        $filter->set_current_data('testdata');

        list($join, $where, $params) = $filter->make_sql();

        $this->assertEmpty($join);
        $this->assertContains('testsourcefield = :', $where);
        $this->assertContains('testadditionalcriteria', $where);
        $this->assertCount(3, $params);
        $this->assertEquals('testadditionaldata1', $params['testadditionalparam1']); // Additionlparams.
        $this->assertEquals('testadditionaldata2', $params['testadditionalparam2']); // Additionlparams.
        unset($params['testadditionalparam1']);
        unset($params['testadditionalparam2']);
        foreach ($params as $param => $value) {
            $this->assertEquals('testdata', $value);
        }
    }

    public function test_make_sql_one_source_with_base_join() {
        // All filter, no additional criteria, results in just joins.
        $filter = new \totara_catalog\datasearch\all(
            'testfilter',
            'testbasealias',
            ['testjoin1', 'testjoin2'],
            'TESTJOINTYPE'
        );
        $filter->add_source(
            'testsourcefield',
            'testsourcetable',
            'testsourcealias',
            [
                'testjoin1' => 'testjoinsource1',
                'testjoin2' => 'testjoinsource2',
            ]
        );
        $filter->set_current_data('testdata');

        list($join, $where, $params) = $filter->make_sql();

        $this->assertContains('TESTJOINTYPE testsourcetable testsourcealias', $join);
        $this->assertContains('testbasealias.testjoin1 = testjoinsource1', $join);
        $this->assertContains('testbasealias.testjoin2 = testjoinsource2', $join);
        $this->assertEmpty($where);
        $this->assertEmpty($params);

        // All, with additional criteria, results in join, where and params.
        $filter = new \totara_catalog\datasearch\all(
            'testfilter',
            'testbasealias',
            ['testjoin1', 'testjoin2'],
            'TESTJOINTYPE'
        );
        $filter->add_source(
            'testsourcefield',
            'testsourcetable',
            'testsourcealias',
            [
                'testjoin1' => 'testjoinsource1',
                'testjoin2' => 'testjoinsource2',
            ],
            'testadditionalcriteria',
            [
                'testadditionalparam1' => 'testadditionaldata1',
                'testadditionalparam2' => 'testadditionaldata2',
            ]
        );
        $filter->set_current_data('testdata');

        list($join, $where, $params) = $filter->make_sql();

        $this->assertContains('TESTJOINTYPE testsourcetable testsourcealias', $join);
        $this->assertContains('testbasealias.testjoin1 = testjoinsource1', $join);
        $this->assertContains('testbasealias.testjoin2 = testjoinsource2', $join);
        $this->assertContains('testadditionalcriteria', $join);
        $this->assertEmpty($where);
        $this->assertCount(2, $params);
        $this->assertEquals('testadditionaldata1', $params['testadditionalparam1']); // Additionlparams.
        $this->assertEquals('testadditionaldata2', $params['testadditionalparam2']); // Additionlparams.

        // Equal, no additional criteria, results in some where and param.
        $filter = new \totara_catalog\datasearch\equal(
            'testfilter',
            'testbasealias',
            ['testjoin1', 'testjoin2'],
            'TESTJOINTYPE'
        );
        $filter->add_source(
            'testsourcefield',
            'testsourcetable',
            'testsourcealias',
            [
                'testjoin1' => 'testjoinsource1',
                'testjoin2' => 'testjoinsource2',
            ]
        );
        $filter->set_current_data('testdata');

        list($join, $where, $params) = $filter->make_sql();

        $this->assertContains('TESTJOINTYPE testsourcetable testsourcealias', $join);
        $this->assertContains('testbasealias.testjoin1 = testjoinsource1', $join);
        $this->assertContains('testbasealias.testjoin2 = testjoinsource2', $join);
        $this->assertContains('testsourcefield = :', $where);
        $this->assertCount(1, $params);
        foreach ($params as $param => $value) {
            $this->assertEquals('testdata', $value);
        }

        // Equal, with additional criteria, results in some where and params.
        $filter = new \totara_catalog\datasearch\equal(
            'testfilter',
            'testbasealias',
            ['testjoin1', 'testjoin2']
        );
        $filter->add_source(
            'testsourcefield',
            'testsourcetable',
            'testsourcealias',
            [
                'testjoin1' => 'testjoinsource1',
                'testjoin2' => 'testjoinsource2',
            ],
            'testadditionalcriteria',
            [
                'testadditionalparam1' => 'testadditionaldata1',
                'testadditionalparam2' => 'testadditionaldata2',
            ]
        );
        $filter->set_current_data('testdata');

        list($join, $where, $params) = $filter->make_sql();

        $this->assertContains('JOIN testsourcetable testsourcealias', $join);
        $this->assertContains('testbasealias.testjoin1 = testjoinsource1', $join);
        $this->assertContains('testbasealias.testjoin2 = testjoinsource2', $join);
        $this->assertContains('testsourcefield = :', $where);
        $this->assertCount(3, $params);
        $this->assertEquals('testadditionaldata1', $params['testadditionalparam1']); // Additionlparams.
        $this->assertEquals('testadditionaldata2', $params['testadditionalparam2']); // Additionlparams.
        unset($params['testadditionalparam1']);
        unset($params['testadditionalparam2']);
        foreach ($params as $param => $value) {
            $this->assertEquals('testdata', $value);
        }
    }

    public function test_make_sql_multi_source_no_base_join() {
        // All filter, with and without additional criteria.
        $filter = new \totara_catalog\datasearch\all(
            'testfilter'
        );
        $filter->add_source(
            'testsourcefield1',
            'testsourcetable1',
            'testsourcealias1'
        );
        $filter->add_source(
            'testsourcefield2',
            'testsourcetable2',
            'testsourcealias2',
            [],
            'testadditionalcriteria2'
        );
        $filter->set_current_data('testdata');

        list($join, $where, $params) = $filter->make_sql();

        $this->assertEquals(1, substr_count($join, 'JOIN'));
        $this->assertContains('FROM testsourcetable1 testsourcealias1', $join);
        $this->assertContains('FROM testsourcetable2 testsourcealias2', $join);
        $this->assertEquals(1, substr_count($join, 'UNION'));
        $this->assertEquals(1, substr_count($join, 'WHERE')); // Only added for additional criteria.
        $this->assertContains('WHERE testadditionalcriteria2', $join);
        $this->assertContains('testfilter', $join);
        $this->assertEmpty($where);
        $this->assertEmpty($params);

        // Equal filter, with and without additional criteria.
        $filter = new \totara_catalog\datasearch\equal(
            'testfilter'
        );
        $filter->add_source(
            'testsourcefield1',
            'testsourcetable1',
            'testsourcealias1'
        );
        $filter->add_source(
            'testsourcefield2',
            'testsourcetable2',
            'testsourcealias2',
            [],
            'testadditionalcriteria2'
        );
        $filter->set_current_data('testdata');

        list($join, $where, $params) = $filter->make_sql();

        $this->assertEquals(1, substr_count($join, 'JOIN'));
        $this->assertContains('FROM testsourcetable1 testsourcealias1', $join);
        $this->assertContains('FROM testsourcetable2 testsourcealias2', $join);
        $this->assertEquals(1, substr_count($join, 'UNION'));
        $this->assertEquals(2, substr_count($join, 'WHERE'));
        $this->assertContains('testsourcefield1 = :', $join);
        $this->assertContains('testsourcefield2 = :', $join);
        $this->assertContains('testadditionalcriteria2', $join);
        $this->assertContains('testfilter', $join);
        $this->assertEmpty($where);
        $this->assertCount(2, $params);
        foreach ($params as $param => $value) {
            $this->assertEquals('testdata', $value);
        }

        // Equal filter with joins and additionalselect, with and without additional criteria.
        $filter = new \totara_catalog\datasearch\equal(
            'testfilter',
            'testbasealias',
            ['testjoin1', 'testjoin2'],
            'TESTJOINTYPE'
        );
        $filter->add_source(
            'testsourcefield1',
            'testsourcetable1',
            'testsourcealias1',
            [
                'testjoin1' => 'testsourcejoin1',
                'testjoin2' => 'testsourcejoin2',
            ]
        );
        $filter->add_source(
            'testsourcefield2',
            'testsourcetable2',
            'testsourcealias2',
            [
                'testjoin1' => 'testsourcejoin3',
                'testjoin2' => 'testsourcejoin4',
            ],
            'testadditionalcriteria2',
            [
                'testadditionalparam1' => 'testadditionaldata1',
                'testadditionalparam2' => 'testadditionaldata2',
            ],
            [
                'additionalselectalias1' => 'additionalselectsource1',
                'additionalselectalias2' => 'additionalselectsource2',
            ]
        );
        $filter->set_current_data('testdata');

        list($join, $where, $params) = $filter->make_sql();

        $this->assertEquals(1, substr_count($join, 'TESTJOINTYPE'));
        $this->assertEquals(1, substr_count($join, 'JOIN')); // 'TESTJOINTYPE'.
        $this->assertContains('testsourcejoin1 AS testjoin1', $join);
        $this->assertContains('testsourcejoin2 AS testjoin2', $join);
        $this->assertContains('testsourcejoin3 AS testjoin1', $join);
        $this->assertContains('testsourcejoin4 AS testjoin2', $join);
        $this->assertContains('additionalselectsource1 AS additionalselectalias1', $join);
        $this->assertContains('additionalselectsource2 AS additionalselectalias2', $join);
        $this->assertContains('FROM testsourcetable1 testsourcealias1', $join);
        $this->assertContains('FROM testsourcetable2 testsourcealias2', $join);
        $this->assertEquals(1, substr_count($join, 'UNION'));
        $this->assertEquals(2, substr_count($join, 'WHERE'));
        $this->assertContains('testsourcefield1 = :', $join);
        $this->assertContains('testsourcefield2 = :', $join);
        $this->assertContains('testadditionalcriteria2', $join);
        $this->assertContains('testfilter', $join);
        $this->assertEquals(2, substr_count($join, 'ON')); // 'ON' + 'UNION'!
        $this->assertEquals(2, substr_count($join, 'AND')); // 'additionalcriteria' and 'ON'!
        $this->assertContains('testbasealias.testjoin1 = testfilter.testjoin1', $join);
        $this->assertContains('testbasealias.testjoin2 = testfilter.testjoin2', $join);
        $this->assertEmpty($where);
        $this->assertCount(4, $params);
        $this->assertEquals('testadditionaldata1', $params['testadditionalparam1']); // Additionlparams.
        $this->assertEquals('testadditionaldata2', $params['testadditionalparam2']); // Additionlparams.
        unset($params['testadditionalparam1']);
        unset($params['testadditionalparam2']);
        foreach ($params as $param => $value) {
            $this->assertEquals('testdata', $value);
        }
    }
}
