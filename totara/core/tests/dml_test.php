<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author David Curry <david.curry@totaralms.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for Totara functionality added to DML database drivers.
 */
class totara_core_dml_testcase extends database_driver_testcase {
    protected $olddboptions = null;

    public function tearDown() {
        // Set our dboptions back to their initial values.
        if (isset($this->olddboptions)) {
            $reflection = new ReflectionClass($this->tdb);
            $property = $reflection->getProperty('dboptions');
            $property->setAccessible(true);
            $property->setValue($this->tdb, $this->olddboptions);
            $property->setAccessible(false);
            $this->olddboptions = null;
        }

        parent::tearDown();
    }

    public function test_get_in_or_equal() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $tablename = 'test_table';
        $table = new xmldb_table($tablename);

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('valint', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('valchar', XMLDB_TYPE_CHAR, '225', null, null, null, null);
        $table->add_field('valtext', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $dbman->create_table($table);

        $DB->insert_record($tablename, (object)array('valint' => 0));
        $DB->insert_record($tablename, (object)array('valint' => 1));
        $DB->insert_record($tablename, (object)array('valint' => 2));
        $DB->insert_record($tablename, (object)array('valchar' => 0));
        $DB->insert_record($tablename, (object)array('valchar' => 1));
        $DB->insert_record($tablename, (object)array('valchar' => 2));
        $DB->insert_record($tablename, (object)array('valchar' => 'abc'));
        $DB->insert_record($tablename, (object)array('valchar' => '1a'));
        $DB->insert_record($tablename, (object)array('valchar' => ' 1'));
        $DB->insert_record($tablename, (object)array('valtext' => 0));
        $DB->insert_record($tablename, (object)array('valtext' => 1));
        $DB->insert_record($tablename, (object)array('valtext' => 2));
        $DB->insert_record($tablename, (object)array('valtext' => 'abc'));
        $DB->insert_record($tablename, (object)array('valtext' => '1a'));
        $DB->insert_record($tablename, (object)array('valtext' => ' 1'));

        $totalcount = $DB->count_records($tablename, array());
        $this->assertGreaterThan(5, $totalcount, 'More than 5 records expected in tests');

        // Search integer id column - note that non-integer items would fail in PostgreSQL, but work in MySQL.

        $items = range(1, 5, 1);
        list($usql, $params) = $DB->get_in_or_equal($items);
        $this->assertCount(count($items), $params, 'Items are not supposed to be embedded in SQL if less than 10');
        $count = $DB->count_records_sql("SELECT COUNT('x') FROM {{$tablename}} WHERE id $usql", $params);
        $this->assertEquals(5, $count);

        $items = range(1, 100, 1);
        list($usql, $params) = $DB->get_in_or_equal($items);
        $this->assertCount(0, $params, 'Items are supposed to be embedded in SQL if more than 10');
        $count = $DB->count_records_sql("SELECT COUNT('x') FROM {{$tablename}} WHERE id $usql", $params);
        $this->assertEquals($totalcount, $count);

        // Search integer column - note that non-integer items would fail in PostgreSQL, but work in MySQL.

        $items = range(0, 5, 1);
        list($usql, $params) = $DB->get_in_or_equal($items);
        $this->assertCount(count($items), $params, 'Items are not supposed to be embedded in SQL if less than 10');
        $count = $DB->count_records_sql("SELECT COUNT('x') FROM {{$tablename}} WHERE valint $usql", $params);
        $this->assertEquals(3, $count);

        $items = range(0, 100, 1);
        list($usql, $params) = $DB->get_in_or_equal($items);
        $this->assertCount(0, $params, 'Items are supposed to be embedded in SQL if more than 10');
        $count = $DB->count_records_sql("SELECT COUNT('x') FROM {{$tablename}} WHERE valint $usql", $params);
        $this->assertEquals(3, $count);

        $items = range(1, 100, 1);
        $items[] = null;
        list($usql, $params) = $DB->get_in_or_equal($items);
        $this->assertCount(count($items), $params, 'Items are not supposed to be embedded in SQL if there are NULLs');
        $count = $DB->count_records_sql("SELECT COUNT('x') FROM {{$tablename}} WHERE valchar $usql", $params);
        $this->assertEquals(2, $count, 'NULL should not match anything if more array items given');

        $items = array(null);
        list($usql, $params) = $DB->get_in_or_equal($items);
        $this->assertCount(count($items), $params, 'Items are not supposed to be embedded in SQL if there are NULLs');
        $count = $DB->count_records_sql("SELECT COUNT('x') FROM {{$tablename}} WHERE valchar $usql", $params);
        $this->assertEquals(0, $count, 'NULL should not match anything if more array items given');

        // Search char column.

        $items = range(0, 5, 1);
        list($usql, $params) = $DB->get_in_or_equal($items);
        $this->assertCount(count($items), $params, 'Items are not supposed to be embedded in SQL if less than 10');
        $count = $DB->count_records_sql("SELECT COUNT('x') FROM {{$tablename}} WHERE valchar $usql", $params);
        $this->assertEquals(3, $count);

        $items = range(1, 5, 1);
        $items[] = 'x';
        list($usql, $params) = $DB->get_in_or_equal($items);
        $this->assertCount(count($items), $params, 'Items are not supposed to be embedded in SQL if less than 10');
        $count = $DB->count_records_sql("SELECT COUNT('x') FROM {{$tablename}} WHERE valchar $usql", $params);
        $this->assertEquals(2, $count);

        $items = range(1, 5, 1);
        $items[] = ' 1';
        list($usql, $params) = $DB->get_in_or_equal($items);
        $this->assertCount(count($items), $params, 'Items are not supposed to be embedded in SQL if less than 10');
        $count = $DB->count_records_sql("SELECT COUNT('x') FROM {{$tablename}} WHERE valchar $usql", $params);
        $this->assertEquals(3, $count);

        $items = range(0, 100, 1);
        list($usql, $params) = $DB->get_in_or_equal($items);
        $this->assertCount(0, $params, 'Items are supposed to be embedded in SQL if more than 10');
        $count = $DB->count_records_sql("SELECT COUNT('x') FROM {{$tablename}} WHERE valchar $usql", $params);
        $this->assertEquals(3, $count);

        $items = range(1, 100, 1);
        $items[] = 'x';
        list($usql, $params) = $DB->get_in_or_equal($items);
        $this->assertCount(count($items), $params, 'Items are not supposed to be embedded in SQL if there are non-integers');
        $count = $DB->count_records_sql("SELECT COUNT('x') FROM {{$tablename}} WHERE valchar $usql", $params);
        $this->assertEquals(2, $count);

        $items = range(1, 100, 1);
        $items[] = ' 1';
        list($usql, $params) = $DB->get_in_or_equal($items);
        $this->assertCount(count($items), $params, 'Items are not supposed to be embedded in SQL if there are non-integers');
        $count = $DB->count_records_sql("SELECT COUNT('x') FROM {{$tablename}} WHERE valchar $usql", $params);
        $this->assertEquals(3, $count);

        // Search text column.

        $items = range(0, 5, 1);
        list($usql, $params) = $DB->get_in_or_equal($items);
        $this->assertCount(count($items), $params, 'Items are not supposed to be embedded in SQL if less than 10');
        $count = $DB->count_records_sql("SELECT COUNT('x') FROM {{$tablename}} WHERE valtext $usql", $params);
        $this->assertEquals(3, $count);

        $items = range(1, 5, 1);
        $items[] = 'x';
        list($usql, $params) = $DB->get_in_or_equal($items);
        $this->assertCount(count($items), $params, 'Items are not supposed to be embedded in SQL if less than 10');
        $count = $DB->count_records_sql("SELECT COUNT('x') FROM {{$tablename}} WHERE valtext $usql", $params);
        $this->assertEquals(2, $count);

        $items = range(1, 5, 1);
        $items[] = ' 1';
        list($usql, $params) = $DB->get_in_or_equal($items);
        $this->assertCount(count($items), $params, 'Items are not supposed to be embedded in SQL if less than 10');
        $count = $DB->count_records_sql("SELECT COUNT('x') FROM {{$tablename}} WHERE valtext $usql", $params);
        $this->assertEquals(3, $count);

        $items = range(0, 100, 1);
        list($usql, $params) = $DB->get_in_or_equal($items);
        $this->assertCount(0, $params, 'Items are supposed to be embedded in SQL if more than 10');
        $count = $DB->count_records_sql("SELECT COUNT('x') FROM {{$tablename}} WHERE valtext $usql", $params);
        $this->assertEquals(3, $count);

        $items = range(1, 100, 1);
        $items[] = 'x';
        list($usql, $params) = $DB->get_in_or_equal($items);
        $this->assertCount(count($items), $params, 'Items are not supposed to be embedded in SQL if there are non-integers');
        $count = $DB->count_records_sql("SELECT COUNT('x') FROM {{$tablename}} WHERE valtext $usql", $params);
        $this->assertEquals(2, $count);

        $items = range(1, 100, 1);
        $items[] = ' 1';
        list($usql, $params) = $DB->get_in_or_equal($items);
        $this->assertCount(count($items), $params, 'Items are not supposed to be embedded in SQL if there are non-integers');
        $count = $DB->count_records_sql("SELECT COUNT('x') FROM {{$tablename}} WHERE valtext $usql", $params);
        $this->assertEquals(3, $count);

        $dbman->drop_table($table);
    }

    public function test_sql_group_concat() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $tablename = 'test_table';
        $table = new xmldb_table('test_table');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('orderby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('valchar', XMLDB_TYPE_CHAR, '225', null, null, null, null);
        $table->add_field('valtext', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('valint', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $dbman->create_table($table);

        $text = str_repeat('š', 3999);

        $DB->insert_record($tablename, (object)array('orderby' => 15, 'groupid' => 12, 'valchar' => 'áéíóú', 'valtext' => $text.'1', 'valint' => null));
        $DB->insert_record($tablename, (object)array('orderby' => 20, 'groupid' => 12, 'valchar' => '12345', 'valtext' => $text.'2', 'valint' => 2));
        $DB->insert_record($tablename, (object)array('orderby' =>  5, 'groupid' => 12, 'valchar' =>    null, 'valtext' => $text.'3', 'valint' => 3));
        $DB->insert_record($tablename, (object)array('orderby' => 10, 'groupid' => 12, 'valchar' => 'abcde', 'valtext' =>      null, 'valint' => 4));
        $DB->insert_record($tablename, (object)array('orderby' => 12, 'groupid' => 24, 'valchar' => 'abc12', 'valtext' =>      null, 'valint' => 5));
        $DB->insert_record($tablename, (object)array('orderby' =>  4, 'groupid' => 24, 'valchar' => 'abc12', 'valtext' => $text.'6', 'valint' => 6));
        $DB->insert_record($tablename, (object)array('orderby' =>  8, 'groupid' => 24, 'valchar' => 'abcde', 'valtext' => $text.'7', 'valint' => 7));
        $DB->insert_record($tablename, (object)array('orderby' =>  6, 'groupid' => 36, 'valchar' => 'a\+1_', 'valtext' => $text.'8', 'valint' => null));
        $DB->insert_record($tablename, (object)array('orderby' =>  3, 'groupid' => 36, 'valchar' =>    null, 'valtext' =>      null, 'valint' => 9));

        $sloppymssql = false;
        if ($DB->get_dbfamily() === 'mssql') {
            $serverinfo = $DB->get_server_info();
            if (version_compare($serverinfo['version'], '14', '<')) {
                $sloppymssql = true;
            }
        }

        $sql = 'SELECT groupid, ' . $DB->sql_group_concat('valchar', ',', 'orderby DESC') . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        $this->assertCount(3, explode(',', $records[12]->grpconcat));
        $this->assertContains('12345', $records[12]->grpconcat);
        $this->assertContains('áéíóú', $records[12]->grpconcat);
        $this->assertContains('abcde', $records[12]->grpconcat);
        $this->assertCount(3, explode(',', $records[24]->grpconcat));
        $this->assertContains('abc12', $records[24]->grpconcat);
        $this->assertContains('abcde', $records[24]->grpconcat);
        $this->assertContains('abc12', $records[24]->grpconcat);
        $this->assertCount(1, explode(',', $records[36]->grpconcat));
        $this->assertContains('a\+1_', $records[36]->grpconcat);
        if (!$sloppymssql) {
            // All decent databases can order the values properly.
            $this->assertEquals('12345,áéíóú,abcde', $records[12]->grpconcat);
            $this->assertEquals('abc12,abcde,abc12', $records[24]->grpconcat);
            $this->assertEquals('a\+1_', $records[36]->grpconcat);
        }

        $sql = 'SELECT groupid, ' . $DB->sql_group_concat('UPPER(valchar)', ',', 'orderby DESC') . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        $this->assertCount(3, explode(',', $records[12]->grpconcat));
        $this->assertContains('12345', $records[12]->grpconcat);
        $this->assertContains('ÁÉÍÓÚ', $records[12]->grpconcat);
        $this->assertContains('ABCDE', $records[12]->grpconcat);
        $this->assertCount(3, explode(',', $records[24]->grpconcat));
        $this->assertContains('ABC12', $records[24]->grpconcat);
        $this->assertContains('ABCDE', $records[24]->grpconcat);
        $this->assertContains('ABC12', $records[24]->grpconcat);
        $this->assertCount(1, explode(',', $records[36]->grpconcat));
        $this->assertContains('A\+1_', $records[36]->grpconcat);
        if (!$sloppymssql) {
            // All decent databases can order the values properly.
            $this->assertEquals('12345,ÁÉÍÓÚ,ABCDE', $records[12]->grpconcat);
            $this->assertEquals('ABC12,ABCDE,ABC12', $records[24]->grpconcat);
            $this->assertEquals('A\+1_', $records[36]->grpconcat);
        }

        $sql = 'SELECT groupid, ' . $DB->sql_group_concat('UPPER(valchar)', "'", 'orderby DESC') . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        $this->assertCount(3, explode("'", $records[12]->grpconcat));
        $this->assertContains('12345', $records[12]->grpconcat);
        $this->assertContains('ÁÉÍÓÚ', $records[12]->grpconcat);
        $this->assertContains('ABCDE', $records[12]->grpconcat);
        $this->assertCount(3, explode("'", $records[24]->grpconcat));
        $this->assertContains('ABC12', $records[24]->grpconcat);
        $this->assertContains('ABCDE', $records[24]->grpconcat);
        $this->assertContains('ABC12', $records[24]->grpconcat);
        $this->assertCount(1, explode("'", $records[36]->grpconcat));
        $this->assertContains('A\+1_', $records[36]->grpconcat);
        if (!$sloppymssql) {
            // All decent databases can order the values properly.
            $this->assertEquals('12345\'ÁÉÍÓÚ\'ABCDE', $records[12]->grpconcat);
            $this->assertEquals('ABC12\'ABCDE\'ABC12', $records[24]->grpconcat);
            $this->assertEquals('A\+1_', $records[36]->grpconcat);
        }

        $sql = 'SELECT groupid, ' . $DB->sql_group_concat('UPPER(valchar)', '\\', 'orderby DESC') . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        $this->assertCount(3, explode('\\', $records[12]->grpconcat));
        $this->assertContains('12345', $records[12]->grpconcat);
        $this->assertContains('ÁÉÍÓÚ', $records[12]->grpconcat);
        $this->assertContains('ABCDE', $records[12]->grpconcat);
        $this->assertCount(3, explode('\\', $records[24]->grpconcat));
        $this->assertContains('ABC12', $records[24]->grpconcat);
        $this->assertContains('ABCDE', $records[24]->grpconcat);
        $this->assertContains('ABC12', $records[24]->grpconcat);
        $this->assertCount(2, explode('\\', $records[36]->grpconcat));
        $this->assertContains('A\+1_', $records[36]->grpconcat);
        if (!$sloppymssql) {
            // All decent databases can order the values properly.
            $this->assertEquals('12345\ÁÉÍÓÚ\ABCDE', $records[12]->grpconcat);
            $this->assertEquals('ABC12\ABCDE\ABC12', $records[24]->grpconcat);
            $this->assertEquals('A\+1_', $records[36]->grpconcat);
        }

        $sql = 'SELECT groupid, ' . $DB->sql_group_concat("COALESCE(valchar, '-')", '|', 'orderby ASC') . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        $this->assertCount(4, explode('|', $records[12]->grpconcat));
        $this->assertContains('-', $records[12]->grpconcat);
        $this->assertContains('12345', $records[12]->grpconcat);
        $this->assertContains('áéíóú', $records[12]->grpconcat);
        $this->assertContains('abcde', $records[12]->grpconcat);
        $this->assertCount(3, explode('|', $records[24]->grpconcat));
        $this->assertContains('abc12', $records[24]->grpconcat);
        $this->assertContains('abcde', $records[24]->grpconcat);
        $this->assertContains('abc12', $records[24]->grpconcat);
        $this->assertCount(2, explode('|', $records[36]->grpconcat));
        $this->assertContains('a\+1_', $records[36]->grpconcat);
        $this->assertContains('-', $records[36]->grpconcat);
        if (!$sloppymssql) {
            // All decent databases can order the values properly.
            $this->assertEquals('-|abcde|áéíóú|12345', $records[12]->grpconcat);
            $this->assertEquals('abc12|abcde|abc12', $records[24]->grpconcat);
            $this->assertEquals('-|a\+1_', $records[36]->grpconcat);
        }

        // Verify integers are cast to varchars.
        $sql = 'SELECT groupid, ' . $DB->sql_group_concat('valint', ':', 'orderby ASC') . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        $this->assertCount(3, explode(':', $records[12]->grpconcat));
        $this->assertContains('3', $records[12]->grpconcat);
        $this->assertContains('4', $records[12]->grpconcat);
        $this->assertContains('2', $records[12]->grpconcat);
        $this->assertCount(3, explode(':', $records[24]->grpconcat));
        $this->assertContains('6', $records[24]->grpconcat);
        $this->assertContains('7', $records[24]->grpconcat);
        $this->assertContains('5', $records[24]->grpconcat);
        $this->assertCount(1, explode(':', $records[36]->grpconcat));
        $this->assertContains('9', $records[36]->grpconcat);
        if (!$sloppymssql) {
            // All decent databases can order the values properly.
            $this->assertEquals('3:4:2', $records[12]->grpconcat);
            $this->assertEquals('6:7:5', $records[24]->grpconcat);
            $this->assertEquals('9', $records[36]->grpconcat);
        }

        // Verify texts are cast to varchars.
        $sql = 'SELECT groupid, ' . $DB->sql_group_concat('valtext', ':', 'orderby ASC') . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        $this->assertCount(3, explode(':', $records[12]->grpconcat));
        $this->assertSame(23999, strlen($records[12]->grpconcat));
        $this->assertContains($text.'3', $records[12]->grpconcat);
        $this->assertContains($text.'1', $records[12]->grpconcat);
        $this->assertContains($text.'2', $records[12]->grpconcat);
        $this->assertCount(2, explode(':', $records[24]->grpconcat));
        $this->assertSame(15999, strlen($records[24]->grpconcat));
        $this->assertContains($text.'6', $records[24]->grpconcat);
        $this->assertContains($text.'7', $records[24]->grpconcat);
        $this->assertCount(1, explode(':', $records[36]->grpconcat));
        $this->assertSame(7999, strlen($records[36]->grpconcat));
        $this->assertContains($text.'8', $records[36]->grpconcat);
        if (!$sloppymssql) {
            // All decent databases can order the values properly.
            $this->assertEquals($text.'3:'.$text.'1:'.$text.'2', $records[12]->grpconcat);
            $this->assertEquals($text.'6:'.$text.'7', $records[24]->grpconcat);
            $this->assertEquals($text.'8', $records[36]->grpconcat);
        }

        // Make sure the orders are independent.
        $sql = 'SELECT groupid, ' . $DB->sql_group_concat('valtext', ':', 'orderby ASC') . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid ORDER BY groupid DESC';
        $records = $DB->get_records_sql($sql);
        $this->assertSame(array(36, 24, 12), array_keys($records));
        $this->assertCount(3, explode(':', $records[12]->grpconcat));
        $this->assertSame(23999, strlen($records[12]->grpconcat));
        $this->assertContains($text.'3', $records[12]->grpconcat);
        $this->assertContains($text.'1', $records[12]->grpconcat);
        $this->assertContains($text.'2', $records[12]->grpconcat);
        $this->assertCount(2, explode(':', $records[24]->grpconcat));
        $this->assertSame(15999, strlen($records[24]->grpconcat));
        $this->assertContains($text.'6', $records[24]->grpconcat);
        $this->assertContains($text.'7', $records[24]->grpconcat);
        $this->assertCount(1, explode(':', $records[36]->grpconcat));
        $this->assertSame(7999, strlen($records[36]->grpconcat));
        $this->assertContains($text.'8', $records[36]->grpconcat);
        if (!$sloppymssql) {
            // All decent databases can order the values properly.
            $this->assertEquals($text.'3:'.$text.'1:'.$text.'2', $records[12]->grpconcat);
            $this->assertEquals($text.'6:'.$text.'7', $records[24]->grpconcat);
            $this->assertEquals($text.'8', $records[36]->grpconcat);
        }

        // Test without $orderby param, the order is not defined.
        $sql = 'SELECT groupid, ' . $DB->sql_group_concat('valint', ':') . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        $this->assertCount(3, explode(':', $records[12]->grpconcat));
        $this->assertContains('3', $records[12]->grpconcat);
        $this->assertContains('4', $records[12]->grpconcat);
        $this->assertContains('2', $records[12]->grpconcat);
        $this->assertCount(3, explode(':', $records[24]->grpconcat));
        $this->assertContains('6', $records[24]->grpconcat);
        $this->assertContains('7', $records[24]->grpconcat);
        $this->assertContains('5', $records[24]->grpconcat);
        $this->assertCount(1, explode(':', $records[36]->grpconcat));
        $this->assertContains('9', $records[36]->grpconcat);

        // Test new '^|:' uniquedelimiter which is using in rb_base_source and limited by 4 chars for MS SQL GROUP_CONCAT_D.
        $sql = 'SELECT groupid, ' . $DB->sql_group_concat('valint', '^|:') . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        $this->assertCount(3, explode('^|:', $records[12]->grpconcat));
        $this->assertContains('3', $records[12]->grpconcat);
        $this->assertContains('4', $records[12]->grpconcat);
        $this->assertContains('2', $records[12]->grpconcat);
        $this->assertCount(3, explode('^|:', $records[24]->grpconcat));
        $this->assertContains('6', $records[24]->grpconcat);
        $this->assertContains('7', $records[24]->grpconcat);
        $this->assertContains('5', $records[24]->grpconcat);
        $this->assertCount(1, explode('^|:', $records[36]->grpconcat));
        $this->assertContains('9', $records[36]->grpconcat);

        // Test invalid '\.|./' uniquedelimiter.
        $sql = 'SELECT groupid, ' . $DB->sql_group_concat('valint', '\.|./') . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        // The explode should return 3 records with valid delimiter.
        $this->assertNotEquals(3, explode('\.|./', $records[12]->grpconcat));

        $dbman->drop_table($table);
    }

    public function test_sql_group_concat_unique() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $tablename = 'test_table';
        $table = new xmldb_table('test_table');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('orderby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('valchar', XMLDB_TYPE_CHAR, '225', null, null, null, null);
        $table->add_field('valtext', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('valint', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $dbman->create_table($table);

        $text = str_repeat('š', 3999);

        $DB->insert_record($tablename, (object)array('orderby' => 15, 'groupid' => 12, 'valchar' => 'áéíóú', 'valtext' => $text.'1', 'valint' => null));
        $DB->insert_record($tablename, (object)array('orderby' => 20, 'groupid' => 12, 'valchar' => '12345', 'valtext' => $text.'2', 'valint' => 2));
        $DB->insert_record($tablename, (object)array('orderby' =>  5, 'groupid' => 12, 'valchar' =>    null, 'valtext' => $text.'3', 'valint' => 3));
        $DB->insert_record($tablename, (object)array('orderby' => 10, 'groupid' => 12, 'valchar' => 'abcde', 'valtext' =>      null, 'valint' => 4));
        $DB->insert_record($tablename, (object)array('orderby' => 12, 'groupid' => 24, 'valchar' => 'abc12', 'valtext' =>      null, 'valint' => 5));
        $DB->insert_record($tablename, (object)array('orderby' =>  4, 'groupid' => 24, 'valchar' => 'abc12', 'valtext' => $text.'6', 'valint' => 6));
        $DB->insert_record($tablename, (object)array('orderby' =>  8, 'groupid' => 24, 'valchar' => 'abcde', 'valtext' => $text.'7', 'valint' => 7));
        $DB->insert_record($tablename, (object)array('orderby' =>  6, 'groupid' => 36, 'valchar' => 'a\+1_', 'valtext' => $text.'8', 'valint' => null));
        $DB->insert_record($tablename, (object)array('orderby' =>  3, 'groupid' => 36, 'valchar' =>    null, 'valtext' =>      null, 'valint' => 9));

        $grpconcat = $DB->sql_group_concat_unique('valchar', ',');
        $sql = 'SELECT groupid, ' . $grpconcat . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        $this->assertCount(3, explode(',', $records[12]->grpconcat));
        $this->assertContains('12345', $records[12]->grpconcat);
        $this->assertContains('áéíóú', $records[12]->grpconcat);
        $this->assertContains('abcde', $records[12]->grpconcat);
        $this->assertCount(2, explode(',', $records[24]->grpconcat));
        $this->assertContains('abc12', $records[24]->grpconcat);
        $this->assertContains('abcde', $records[24]->grpconcat);
        $this->assertCount(1, explode(',', $records[36]->grpconcat));
        $this->assertContains('a\+1_', $records[36]->grpconcat);
        $this->assertEquals('abc12,abcde', $records[24]->grpconcat);
        $this->assertEquals('a\+1_', $records[36]->grpconcat);

        $grpconcat = $DB->sql_group_concat_unique('UPPER(valchar)', ',');
        $sql = 'SELECT groupid, ' . $grpconcat . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        $this->assertCount(3, explode(',', $records[12]->grpconcat));
        $this->assertContains('12345', $records[12]->grpconcat);
        $this->assertContains('ÁÉÍÓÚ', $records[12]->grpconcat);
        $this->assertContains('ABCDE', $records[12]->grpconcat);
        $this->assertCount(2, explode(',', $records[24]->grpconcat));
        $this->assertContains('ABC12', $records[24]->grpconcat);
        $this->assertContains('ABCDE', $records[24]->grpconcat);
        $this->assertCount(1, explode(',', $records[36]->grpconcat));
        $this->assertContains('A\+1_', $records[36]->grpconcat);
        $this->assertEquals('ABC12,ABCDE', $records[24]->grpconcat);
        $this->assertEquals('A\+1_', $records[36]->grpconcat);

        $grpconcat = $DB->sql_group_concat_unique('UPPER(valchar)', "'");
        $sql = 'SELECT groupid, ' . $grpconcat . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        $this->assertCount(3, explode("'", $records[12]->grpconcat));
        $this->assertContains('12345', $records[12]->grpconcat);
        $this->assertContains('ÁÉÍÓÚ', $records[12]->grpconcat);
        $this->assertContains('ABCDE', $records[12]->grpconcat);
        $this->assertCount(2, explode("'", $records[24]->grpconcat));
        $this->assertContains('ABC12', $records[24]->grpconcat);
        $this->assertContains('ABCDE', $records[24]->grpconcat);
        $this->assertCount(1, explode("'", $records[36]->grpconcat));
        $this->assertContains('A\+1_', $records[36]->grpconcat);
        $this->assertEquals('ABC12\'ABCDE', $records[24]->grpconcat);
        $this->assertEquals('A\+1_', $records[36]->grpconcat);

        $grpconcat = $DB->sql_group_concat_unique('UPPER(valchar)', '\\');
        $sql = 'SELECT groupid, ' . $grpconcat . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        $this->assertCount(3, explode('\\', $records[12]->grpconcat));
        $this->assertContains('12345', $records[12]->grpconcat);
        $this->assertContains('ÁÉÍÓÚ', $records[12]->grpconcat);
        $this->assertContains('ABCDE', $records[12]->grpconcat);
        $this->assertCount(2, explode('\\', $records[24]->grpconcat));
        $this->assertContains('ABC12', $records[24]->grpconcat);
        $this->assertContains('ABCDE', $records[24]->grpconcat);
        $this->assertCount(2, explode('\\', $records[36]->grpconcat));
        $this->assertContains('A\+1_', $records[36]->grpconcat);
        $this->assertEquals('ABC12\ABCDE', $records[24]->grpconcat);
        $this->assertEquals('A\+1_', $records[36]->grpconcat);

        $grpconcat = $DB->sql_group_concat_unique("COALESCE(valchar, '-')", '|');
        $sql = 'SELECT groupid, ' . $grpconcat . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        $this->assertCount(4, explode('|', $records[12]->grpconcat));
        $this->assertContains('-', $records[12]->grpconcat);
        $this->assertContains('12345', $records[12]->grpconcat);
        $this->assertContains('áéíóú', $records[12]->grpconcat);
        $this->assertContains('abcde', $records[12]->grpconcat);
        $this->assertCount(2, explode('|', $records[24]->grpconcat));
        $this->assertContains('abc12', $records[24]->grpconcat);
        $this->assertContains('abcde', $records[24]->grpconcat);
        $this->assertCount(2, explode('|', $records[36]->grpconcat));
        $this->assertContains('a\+1_', $records[36]->grpconcat);
        $this->assertContains('-', $records[36]->grpconcat);

        // Verify integers are cast to varchars.
        $grpconcat = $DB->sql_group_concat_unique('valint', ':');
        $sql = 'SELECT groupid, ' . $grpconcat . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        $this->assertCount(3, explode(':', $records[12]->grpconcat));
        $this->assertContains('3', $records[12]->grpconcat);
        $this->assertContains('4', $records[12]->grpconcat);
        $this->assertContains('2', $records[12]->grpconcat);
        $this->assertCount(3, explode(':', $records[24]->grpconcat));
        $this->assertContains('6', $records[24]->grpconcat);
        $this->assertContains('7', $records[24]->grpconcat);
        $this->assertContains('5', $records[24]->grpconcat);
        $this->assertCount(1, explode(':', $records[36]->grpconcat));
        $this->assertContains('9', $records[36]->grpconcat);
        $this->assertEquals('9', $records[36]->grpconcat);

        // Verify texts are cast to varchars.
        $grpconcat = $DB->sql_group_concat_unique('valtext', ':');
        $sql = 'SELECT groupid, ' . $grpconcat . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        $this->assertCount(3, explode(':', $records[12]->grpconcat));
        $this->assertSame(23999, strlen($records[12]->grpconcat));
        $this->assertContains($text.'3', $records[12]->grpconcat);
        $this->assertContains($text.'1', $records[12]->grpconcat);
        $this->assertContains($text.'2', $records[12]->grpconcat);
        $this->assertCount(2, explode(':', $records[24]->grpconcat));
        $this->assertSame(15999, strlen($records[24]->grpconcat));
        $this->assertContains($text.'6', $records[24]->grpconcat);
        $this->assertContains($text.'7', $records[24]->grpconcat);
        $this->assertCount(1, explode(':', $records[36]->grpconcat));
        $this->assertSame(7999, strlen($records[36]->grpconcat));
        $this->assertContains($text.'8', $records[36]->grpconcat);
        $this->assertEquals($text.'6:'.$text.'7', $records[24]->grpconcat);
        $this->assertEquals($text.'8', $records[36]->grpconcat);

        // Test new '^|:' uniquedelimiter which is using in rb_base_source and limited by 4 chars for MS SQL GROUP_CONCAT_D.
        $grpconcat = $DB->sql_group_concat_unique('valtext', '^|:');
        $sql = 'SELECT groupid, ' . $grpconcat . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        $this->assertCount(3, explode('^|:', $records[12]->grpconcat));
        $this->assertSame(24003, strlen($records[12]->grpconcat));
        $this->assertContains($text.'3', $records[12]->grpconcat);
        $this->assertContains($text.'1', $records[12]->grpconcat);
        $this->assertContains($text.'2', $records[12]->grpconcat);
        $this->assertCount(2, explode('^|:', $records[24]->grpconcat));
        $this->assertSame(16001, strlen($records[24]->grpconcat));
        $this->assertContains($text.'6', $records[24]->grpconcat);
        $this->assertContains($text.'7', $records[24]->grpconcat);
        $this->assertCount(1, explode('^|:', $records[36]->grpconcat));
        $this->assertSame(7999, strlen($records[36]->grpconcat));
        $this->assertContains($text.'8', $records[36]->grpconcat);
        $this->assertEquals($text.'6^|:'.$text.'7', $records[24]->grpconcat);
        $this->assertEquals($text.'8', $records[36]->grpconcat);

        // Test invalid '\.|./' uniquedelimiter.
        $grpconcat = $DB->sql_group_concat_unique('valtext', '\.|./');
        $sql = 'SELECT groupid, ' . $grpconcat . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        // The explode should return 3 records with valid delimiter.
        $this->assertNotEquals(3, explode('\.|./', $records[12]->grpconcat));

        $dbman->drop_table($table);
    }

    /**
     * Test get_counted_records_sql() and get_counted_recordset_sql() methods
     *
     * @dataProvider trueFalseProvider
     * @param bool $userecordset If true: get_counted_recordset_sql, false: get_counted_records_sql
     */
    public function test_get_counted_records_sql($userecordset) {
        global $DB;
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $tablename = 'test_table';
        $table = new xmldb_table('test_table');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('parentid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('orderby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('valchar', XMLDB_TYPE_CHAR, '225', null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $dbman->create_table($table);

        $expected_fields = array(
            'id',
            'parentid',
            'orderby',
            'groupid',
            'valchar'
        );

        $text = str_repeat('š', 3999);

        $ids = array();
        $ids[0] = $DB->insert_record($tablename, (object)array('parentid' => null,    'orderby' => 15, 'groupid' => 12, 'valchar' => 'áéíóú'));
        $ids[1] = $DB->insert_record($tablename, (object)array('parentid' => $ids[0], 'orderby' => 20, 'groupid' => 12, 'valchar' => '12345'));
        $ids[2] = $DB->insert_record($tablename, (object)array('parentid' => null,    'orderby' =>  5, 'groupid' => 24, 'valchar' =>    null));
        $ids[3] = $DB->insert_record($tablename, (object)array('parentid' => null,    'orderby' => 10, 'groupid' => 24, 'valchar' => 'abcde'));
        $ids[4] = $DB->insert_record($tablename, (object)array('parentid' => $ids[3], 'orderby' => 12, 'groupid' => 36, 'valchar' => 'abc12'));
        $ids[5] = $DB->insert_record($tablename, (object)array('parentid' => $ids[4], 'orderby' =>  4, 'groupid' => 36, 'valchar' => 'abc13'));
        $ids[6] = $DB->insert_record($tablename, (object)array('parentid' => $ids[3], 'orderby' =>  8, 'groupid' => 47, 'valchar' => 'abcde'));
        $ids[7] = $DB->insert_record($tablename, (object)array('parentid' => null,    'orderby' =>  6, 'groupid' => 58, 'valchar' => 'a\+1_'));
        $ids[8] = $DB->insert_record($tablename, (object)array('parentid' => $ids[3], 'orderby' =>  3, 'groupid' => 58, 'valchar' =>    null));

        // Prepare records array from recordset.
        $makerecords = function($recordset) {
            $records = array();
            foreach ($recordset as $record) {
                $rec = (array)$record;
                $records[reset($rec)] = $record;
            }
            return $records;
        };

        // Zero query.
        $sql = 'SeLeCt * FrOm {test_table} WHERE 1=0';
        $count = 0;

        if ($userecordset) {
            $recordset = $DB->get_counted_recordset_sql($sql, array(), $count);
            $this->assertSame($count, $recordset->get_count_without_limits());
            $records = $makerecords($recordset);
        } else {
            $records = $DB->get_counted_records_sql($sql, array(), 0, 0, $count);
        }

        $this->assertCount(0, $records);
        $this->assertSame(0, $count);

        // Simple query.
        $sql = 'SeLeCt * FrOm {test_table}';
        $count = 0;
        if ($userecordset) {
            $recordset = $DB->get_counted_recordset_sql($sql, array(), 0, 0, $count);
            $this->assertSame($count, $recordset->get_count_without_limits());
            $records = $makerecords($recordset);
            $this->assertCount(9, $records);
        } else {
            $records = $DB->get_counted_records_sql($sql, array(), 0, 0, $count);
            $this->assertSame(9, $count);
            $this->assertCount(9, $records);
        }

        $this->assertCount(9, $records);
        $this->assertSame(9, $count);
        $this->assertSame('abcde', $records[$ids[3]]->valchar);

        // Verify that the records have the exact fields we expect.
        foreach ($records as $record) {
            $record_array = (array)$record;
            $this->assertSame($expected_fields, array_keys($record_array));
        }

        // Simple query with limits.
        $sql = 'SeLeCt id, valchar FrOm {test_table} ORDER BY id';
        $count = 0;
        if ($userecordset) {
            $recordset = $DB->get_counted_recordset_sql($sql, array(), 4, 2, $count);
            $this->assertSame($count, $recordset->get_count_without_limits());
            $records = $makerecords($recordset);
        } else {
            $records = $DB->get_counted_records_sql($sql, array(), 4, 2, $count);
        }

        $this->assertCount(2, $records);
        $this->assertSame(9, $count);
        $this->assertSame('abc12', $records[$ids[4]]->valchar);
        $this->assertSame('abc13', $records[$ids[5]]->valchar);

        // Simple query with limits outside of bounds.
        $sql = 'SeLeCt id, valchar FrOm {test_table} ORDER BY id';
        $count = null;
        if ($userecordset) {
            $recordset = $DB->get_counted_recordset_sql($sql, array(), 1000, 100, $count);
            $this->assertSame($count, $recordset->get_count_without_limits());
            $records = $makerecords($recordset);
        } else {
            $records = $DB->get_counted_records_sql($sql, array(), 1000, 100, $count);
        }
        $this->assertCount(0, $records);
        $this->assertSame(9, $count);

        // Verify that the records have the exact fields we expect.
        foreach ($records as $record) {
            $record_array = (array)$record;
            $this->assertSame(['id', 'valchar'], array_keys($record_array));
        }

        // The following tests is basically one complex query written in different way that should return the same result.
        $complexassert = function($sql) use ($userecordset, $makerecords) {
            global $DB;
            $count = 0;
            if ($userecordset) {
                $recordset = $DB->get_counted_recordset_sql($sql, array('orderby' => 4), 1, 2, $count);
                $this->assertSame($count, $recordset->get_count_without_limits());
                $records = $makerecords($recordset);
            } else {
                $records = $DB->get_counted_records_sql($sql, array('orderby' => 4), 1, 2, $count);
            }
            $this->assertCount(2, $records);
            $this->assertSame(5, $count);
            $this->assertEquals(2, $records[10]->cnt);
            $this->assertEquals(1, $records[12]->cnt);
        };

        // Complex one line query.
        $sql = "SELECT MAX(tt1.orderby) as maxord, COUNT(tt2.valchar) as cnt, '$text' as long_text FROM {test_table} tt1 LEFT JOIN {test_table} tt2 ON tt2.parentid=tt1.id WHERE tt1.orderby > :orderby GROUP BY tt1.groupid ORDER BY tt1.groupid";
        $complexassert($sql);

        // Complex multi line query (line breaks in different places).
        $sql = "
        SELECT
        MAX(tt1.orderby) as maxord,
        COUNT(tt2.valchar) as cnt, '$text' as long_text
        FROM {test_table} tt1
        LEFT JOIN {test_table} tt2 ON tt2.parentid=tt1.id
        WHERE tt1.orderby > :orderby
        GROUP BY tt1.groupid
        ORDER BY tt1.groupid";

        $complexassert($sql);

        $sql = "
        SELECT
        MAX(tt1.orderby) as maxord,
        COUNT(tt2.valchar) as cnt, ' \"FROM\" (FROM) SELECT FROM' as long_text
        FROM {test_table} tt1
        LEFT JOIN {test_table} tt2 ON tt2.parentid=tt1.id
        WHERE tt1.orderby > :orderby
        GROUP BY tt1.groupid
        ORDER BY tt1.groupid";

        $complexassert($sql);

        $sql = "SELECT
                  MAX(tt1.orderby) as maxord,
                  COUNT(tt2.valchar) as cnt, '$text' as long_text
                FROM
                  {test_table} tt1
                  LEFT JOIN {test_table} tt2
                    ON tt2.parentid=tt1.id
                WHERE
                  tt1.orderby > :orderby
                GROUP BY
                  tt1.groupid
                ORDER BY
                  tt1.groupid";
        $complexassert($sql);

        // Complex one line query with sub query.
        $sql = "SELECT MAX(tt1.orderby) as maxord, COUNT(tt2.valchar) as cnt, '$text' as long_text FROM {test_table} tt1 LEFT JOIN (SELECT * FROM {test_table} itt WHERE orderby > 2) tt2 ON tt2.parentid=tt1.id WHERE tt1.orderby > :orderby GROUP BY tt1.groupid ORDER BY tt1.groupid";
        $complexassert($sql);

        // Complex multi line sub queries (line breaks in different places).
        $sql = "SELECT MAX(tt1.orderby) as maxord, COUNT(tt2.valchar) as cnt, '$text' as long_text FROM {test_table} tt1 LEFT JOIN (
SELECT * FROM {test_table} itt WHERE orderby > 2) tt2 ON tt2.parentid=tt1.id WHERE tt1.orderby > :orderby GROUP BY tt1.groupid ORDER BY tt1.groupid";
        $complexassert($sql);

        $sql = "
SELECT MAX(tt1.orderby) as maxord, COUNT(tt2.valchar) as cnt, '$text' as long_text
FROM {test_table} tt1 LEFT JOIN (
  SELECT *
  FROM {test_table} itt
  WHERE orderby > 2
) tt2 ON tt2.parentid=tt1.id
WHERE tt1.orderby > :orderby
GROUP BY tt1.groupid
ORDER BY tt1.groupid";
        $complexassert($sql);

        $sql = "
  SELECT MAX(tt1.orderby) as maxord, COUNT(tt2.valchar) as cnt, '$text' as long_text
  FROM
    {test_table} tt1
    LEFT JOIN (
        SELECT
          parentid,
          valchar
        FROM
          {test_table}
        WHERE
          orderby > 2
    ) tt2 ON tt2.parentid=tt1.id
  WHERE
    tt1.orderby > :orderby
  GROUP BY
    tt1.groupid
  ORDER BY
    tt1.groupid";
        $complexassert($sql);

        $sql = "
  SELECT MAX(tt1.orderby) as maxord, COUNT(tt2.valchar) as cnt, (SELECT MAX(orderby) FROM {test_table}) as select_from
  FROM
    {test_table} tt1
    LEFT JOIN (
        SELECT
          parentid,
          valchar
        FROM
          {test_table}
        WHERE
          orderby > 2
    ) tt2 ON tt2.parentid=tt1.id
  WHERE
    tt1.orderby > :orderby
  GROUP BY
    tt1.groupid
  ORDER BY
    tt1.groupid";
        $complexassert($sql);

        $dbman->drop_table($table);
    }

    public function test_reserved_columns() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $tablename = 'test_table';
        $table = new xmldb_table('test_table');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('from', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('where', XMLDB_TYPE_TEXT, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $dbman->create_table($table);

        $record = new stdClass();
        $record->from = '77';
        $record->where = 'home';

        // Test normal inserts.
        $record->id = $DB->insert_record($tablename, $record);
        $result = $DB->get_record($tablename, array('id' => $record->id));
        $this->assertEquals($record, $result);

        // Test normal update.
        $update = clone($record);
        $update->from = '99';
        $update->where = 'work';
        $DB->update_record($tablename, $update);
        $result = $DB->get_record($tablename, array('id' => $update->id));
        $this->assertEquals($update, $result);

        // Test setting of field.
        $update->from = '11';
        $DB->set_field($tablename, 'from', $update->from, array('id' => $update->id));
        $result = $DB->get_record($tablename, array('id' => $update->id));
        $this->assertEquals($update, $result);

        // Test where conditions array supports quoted columns.
        $result = $DB->get_record($tablename, array('"id"' => $update->id));
        $this->assertEquals($update, $result);
        $result = $DB->get_record($tablename, array('"from"' => $update->from));
        $this->assertEquals($update, $result);
    }

    /**
     * Data provider that just returns two values: true and false
     * Useful for running phpunit test in two modes
     */
    public static function trueFalseProvider() {
        return [[true], [false]];
    }

    /**
     * Override a dboption for tests.
     *
     * @param string $name  The name of the option (for example, ftslanguage)
     * @param mixed  $value New value that the option should have
     */
    protected function override_dboption(string $name, $value) {
        $DB = $this->tdb;

        if (!isset($this->olddboptions)) {
            $cfg = $DB->export_dbconfig();
            if (!empty($cfg->dboptions)) {
                $this->olddboptions = $cfg->dboptions;
            } else {
                $this->olddboptions = [];
            }
        }

        $reflection = new ReflectionClass($DB);
        $property = $reflection->getProperty('dboptions');
        $property->setAccessible(true);
        $dboptions = $property->getValue($DB);
        $dboptions[$name] = $value;
        $property->setValue($DB, $dboptions);
        $property->setAccessible(false);
    }

    /**
     * Oh well, MS SQL Server needs time to index the data, we need to wait a few seconds.
     * @param string $tablename
     */
    public function wait_for_mssql_fts_indexing(string $tablename) {
        $DB = $this->tdb;

        if ($DB->get_dbfamily() !== 'mssql') {
            return;
        }

        /** @var sqlsrv_native_moodle_database $DB */
        $done = $DB->fts_wait_for_indexing($tablename, 10);
        $this->assertTrue($done);
    }

    public function test_override_dboption() {
        $DB = $this->tdb;

        $lang = $DB->get_ftslanguage();
        $fts3b = $DB->get_fts3bworkaround();
        $inparams = $DB->get_max_in_params();

        $this->assertNotNull($inparams);
        $this->assertNotNull($lang);
        $this->assertNotNull($fts3b);

        $this->override_dboption('maxinparams', 234);
        $this->assertSame(234, $DB->get_max_in_params());

        $this->override_dboption('ftslanguage', $lang . 'xyz');
        $this->assertSame($lang . 'xyz', $DB->get_ftslanguage());

        $this->override_dboption('ftslanguage', $lang);
        $this->assertSame($lang, $DB->get_ftslanguage());

        $this->override_dboption('fts3bworkaround', true);
        $this->assertSame(true, $DB->get_fts3bworkaround());

        $this->override_dboption('fts3bworkaround', $fts3b);
        $this->assertSame($fts3b, $DB->get_fts3bworkaround());

        $this->override_dboption('fts3bworkaround', false);
        $this->assertSame(false, $DB->get_fts3bworkaround());

        $this->override_dboption('maxinparams', $inparams);
        $this->assertSame($inparams, $DB->get_max_in_params());
    }

    /**
     * Make sure the full text search indexes are created and visible.
     */
    public function test_get_indexes_full_text_search() {
        $DB = $this->tdb;
        $dbman = $this->tdb->get_manager();

        $tablename = 'test_table';
        $table = new xmldb_table($tablename);

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('high', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_field('low', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('course', XMLDB_INDEX_NOTUNIQUE, array('course'));
        $table->add_index('course-id', XMLDB_INDEX_UNIQUE, array('course', 'id'));
        $table->add_index('high', XMLDB_INDEX_NOTUNIQUE, array('high'), array('full_text_search'));
        $table->add_index('low', XMLDB_INDEX_NOTUNIQUE, array('low'), array('full_text_search'));
        $dbman->create_table($table);

        $indices = $DB->get_indexes($tablename);
        $this->assertIsArray($indices);
        $this->assertCount(4, $indices);

        // Ignore the index names here.

        $coursex = null;
        $courseidx = null;
        $highx = null;
        $lowx = null;
        foreach ($indices as $index) {
            if ($index['columns'] === array('course')) {
                $coursex = (object)$index;
                continue;
            }
            if ($index['columns'] === array('course', 'id')) {
                $courseidx = (object)$index;
                continue;
            }
            if ($index['columns'] === array('high')) {
                $highx = (object)$index;
                continue;
            }
            if ($index['columns'] === array('low')) {
                $lowx = (object)$index;
                continue;
            }
        }
        $this->assertNotNull($coursex);
        $this->assertNotNull($courseidx);
        $this->assertNotNull($highx);
        $this->assertNotNull($lowx);

        $this->assertFalse($coursex->unique);
        $this->assertTrue($courseidx->unique);
        $this->assertFalse($highx->unique);
        $this->assertFalse($lowx->unique);

        $this->assertFalse($coursex->fulltextsearch);
        $this->assertFalse($courseidx->fulltextsearch);
        $this->assertTrue($highx->fulltextsearch);
        $this->assertTrue($lowx->fulltextsearch);

        $dbman->drop_table($table);
    }

    public function test_unformat_fts_content() {
        $DB = $this->tdb;
        $content = "Lorem <span class='xyz'>Ipsum</span> dolor<br/>sit amet.\n\n* ABC\n* def __xyz__ &amp; *opq*";
        $this->assertSame($content, $DB->unformat_fts_content($content, FORMAT_PLAIN));
        $this->assertSame("Lorem Ipsum dolor\nsit amet.\n\n* ABC\n* def __xyz__ & *opq*", $DB->unformat_fts_content($content, FORMAT_HTML));
        $this->assertSame("Lorem Ipsum dolor\nsit amet.\n\n* ABC\n* def __xyz__ & *opq*", $DB->unformat_fts_content($content, FORMAT_MOODLE));
        $this->assertSame("Lorem Ipsum dolor\nsit amet.\n\n ABC\n def xyz & opq ", $DB->unformat_fts_content($content, FORMAT_MARKDOWN));
        $this->assertNull($DB->unformat_fts_content(null, FORMAT_HTML));
    }

    public function test_get_fts_subquery() {
        $DB = $this->tdb;
        $dbman = $this->tdb->get_manager();

        $coursetable = 'test_table_course';
        $table = new xmldb_table($coursetable);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('visible', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('fullname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('summary', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('summaryformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $dbman->create_table($table);

        $coursesearchtable = 'test_table_course_search';
        $table = new xmldb_table($coursesearchtable);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('fullname', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('shortname', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('summary', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, array('courseid'), 'course', array('id'));
        $table->add_index('fullname', XMLDB_INDEX_NOTUNIQUE, array('fullname'), array('full_text_search'));
        $table->add_index('shortname', XMLDB_INDEX_NOTUNIQUE, array('shortname'), array('full_text_search'));
        $table->add_index('summary', XMLDB_INDEX_NOTUNIQUE, array('summary'), array('full_text_search'));
        $dbman->create_table($table);

        // Create some fake courses.
        $now = time();
        $courses = array();
        $courses[0] = $DB->insert_record($coursetable, (object)array(
            'fullname' => 'Old style PHP development',
            'shortname' => 'Old PHP',
            'summary' => '<p>Good old times when <strong>web applications</strong> were single pages written purely in PHP.<p>',
            'summaryformat' => FORMAT_HTML,
            'timemodified' => $now,
        ));
        $courses[1] = $DB->insert_record($coursetable, (object)array(
            'fullname' => 'New PHP frameworks',
            'shortname' => 'Frameworks',
            'summary' => 'Fancy new PHP frameworks that can be used to build some great systems.',
            'summaryformat' => FORMAT_MARKDOWN,
            'timemodified' => $now,
        ));
        $courses[2] = $DB->insert_record($coursetable, (object)array(
            'fullname' => 'Web development basics',
            'shortname' => 'Basics',
            'summary' => 'Quick overview of different approaches to building of modern web applications including PHP, Ajax, REST, Node, Ruby and other fancy stuff.',
            'summaryformat' => FORMAT_MOODLE,
            'timemodified' => $now,
        ));
        $courses[3] = $DB->insert_record($coursetable, (object)array(
            'fullname' => 'Javascript rulez',
            'shortname' => 'JS',
            'summary' => 'Nothing is impossible with JavaScript! Prepare to be amazed how great this ancient language can be when used for the good of mankind.',
            'summaryformat' => FORMAT_MOODLE,
            'timemodified' => $now,
        ));
        $courses[4] = $DB->insert_record($coursetable, (object)array(
            'fullname' => 'Future of development with PHP',
            'shortname' => 'PHP666',
            'summary' => 'Should we use PHP for any new projects? Some x xx stuff.',
            'summaryformat' => FORMAT_MARKDOWN,
            'visible' => 0,
            'timemodified' => $now,
        ));
        $courses[5] = $DB->insert_record($coursetable, (object)array(
            'fullname' => 'Course with null data',
            'shortname' => null,
            'summary' => null,
            'summaryformat' => FORMAT_MARKDOWN,
            'visible' => 0,
            'timemodified' => $now,
        ));
        $courses[6] = $DB->insert_record($coursetable, (object)array( // Czech test course.
            'fullname' => 'Žluťoučký koníček',
            'shortname' => 'Koníček',
            'summary' => 'Šíleně žluťoučký koníček úpěl ďábelské ódy.',
            'summaryformat' => FORMAT_MARKDOWN,
            'timemodified' => $now,
        ));
        $courses[7] = $DB->insert_record($coursetable, (object)array( // Russian test course.
            'fullname' => 'Выставка достижений народного хозяйства',
            'shortname' => 'ВДНХ',
            'summary' => 'Learn more about the former trade show in Moscow',
            'summaryformat' => FORMAT_MARKDOWN,
            'timemodified' => $now,
        ));
        $courses[8] = $DB->insert_record($coursetable, (object)array( // Japanese test course - something about a dog and tree (hopefully).
            'fullname' => '犬と木', // Dog and tree.
            'shortname' => '犬', // Dog.
            'summary' => 'Psíček uviděl stromeček. = Dog saw a tree. = 犬が木を見た。',
            'summaryformat' => FORMAT_MARKDOWN,
            'timemodified' => $now,
        ));
        $courses[9] = $DB->insert_record($coursetable, (object)array( // Hebrew.
            'fullname' => 'שיעורי ספורט', // Sport courses
            'shortname' => null,
            'summary' => 'שיעורי ספורט מיועדים לבני נוער שמעוניינים לשפר את כישוריהם האתלטיים. הקורס ייפתח באוגוסט הבא',
            'summaryformat' => FORMAT_MARKDOWN,
            'timemodified' => $now,
        ));

        // NOTE: the results may be different if there are only a few records in the search table,
        //       this is because the databases may ignore words that are repeated in all rows.
        foreach ($courses as $i => $id) {
            $courses[$i] = $DB->get_record($coursetable, array('id' => $id), '*', MUST_EXIST);
        }

        // Fill the course search table with data, these will have to be kept in sync via events in real code.
        foreach ($courses as $i => $course) {
            $record = new stdClass();
            $record->courseid = $course->id;
            $record->fullname = $DB->unformat_fts_content($course->fullname, FORMAT_HTML); // This is not plain text because it may have multilang!
            $record->shortname = $DB->unformat_fts_content($course->shortname, FORMAT_HTML); // This is not plain text because it may have multilang!
            $record->summary = $DB->unformat_fts_content($course->summary, $course->summaryformat);
            $record->timemodified = time(); // Keep track of last update.
            $DB->insert_record($coursesearchtable, $record);
        }
        $this->wait_for_mssql_fts_indexing($coursesearchtable);

        // One column only.
        list($ftssql, $params) = $DB->get_fts_subquery($coursesearchtable, ['fullname' => 1], 'php');
        $this->assertIsString($ftssql);
        $this->assertIsArray($params);
        $sql = "SELECT c.*, fts.score
                  FROM {{$coursesearchtable}} cs
                  JOIN {$ftssql} fts ON fts.id = cs.id
                  JOIN {{$coursetable}} c ON c.id = cs.courseid
                 WHERE c.visible = 1
              ORDER BY fts.score DESC, c.fullname ASC";
        $result = $DB->get_records_sql($sql, $params);
        $this->assertSame(array((int)$courses[1]->id, (int)$courses[0]->id), array_keys($result));

        // Three columns ranked.
        list($ftssql, $params) = $DB->get_fts_subquery($coursesearchtable, ['shortname' => 100, 'fullname' => 10, 'summary' => 1], 'php');
        $this->assertIsString($ftssql);
        $this->assertIsArray($params);
        $sql = "SELECT c.*, fts.score
                  FROM {{$coursesearchtable}} cs
                  JOIN {$ftssql} fts ON fts.id = cs.id
                  JOIN {{$coursetable}} c ON c.id = cs.courseid
                 WHERE c.visible = 1
              ORDER BY fts.score DESC, c.fullname ASC";
        $result = $DB->get_records_sql($sql, $params);
        $this->assertSame(array((int)$courses[0]->id, (int)$courses[1]->id, (int)$courses[2]->id), array_keys($result));

        // Three columns ranked with null values
        list($ftssql, $params) = $DB->get_fts_subquery($coursesearchtable, ['shortname' => 100, 'fullname' => 10, 'summary' => 1], 'null data');
        $this->assertIsString($ftssql);
        $this->assertIsArray($params);
        $sql = "SELECT c.*, fts.score
                  FROM {{$coursesearchtable}} cs
                  JOIN {$ftssql} fts ON fts.id = cs.id
                  JOIN {{$coursetable}} c ON c.id = cs.courseid
              ORDER BY fts.score DESC, c.fullname ASC";
        $result = $DB->get_records_sql($sql, $params);
        $this->assertSame(array((int)$courses[5]->id), array_keys($result));

        // Empty search.
        $this->assertDebuggingNotCalled();
        list($ftssql, $params) = $DB->get_fts_subquery($coursesearchtable, ['shortname' => 100, 'fullname' => 10, 'summary' => 1], ' ');
        $this->assertDebuggingCalled('Full text search text is empty, developers should make sure user entered something.');
        $this->assertIsString($ftssql);
        $this->assertIsArray($params);
        $sql = "SELECT c.*, fts.score
                  FROM {{$coursesearchtable}} cs
                  JOIN {$ftssql} fts ON fts.id = cs.id
                  JOIN {{$coursetable}} c ON c.id = cs.courseid
                 WHERE c.visible = 1
              ORDER BY fts.score DESC, c.fullname ASC";
        $result = $DB->get_records_sql($sql, $params);
        $this->assertCount(0, $result);

        // Search too short.
        list($ftssql, $params) = $DB->get_fts_subquery($coursesearchtable, ['shortname' => 100, 'fullname' => 10, 'summary' => 1], 'x');
        $this->assertIsString($ftssql);
        $this->assertIsArray($params);
        $sql = "SELECT c.*, fts.score
                  FROM {{$coursesearchtable}} cs
                  JOIN {$ftssql} fts ON fts.id = cs.id
                  JOIN {{$coursetable}} c ON c.id = cs.courseid
                 WHERE c.visible = 1
              ORDER BY fts.score DESC, c.fullname ASC";
        $result = $DB->get_records_sql($sql, $params);
        $this->assertCount(0, $result);
        list($ftssql, $params) = $DB->get_fts_subquery($coursesearchtable, ['shortname' => 100, 'fullname' => 10, 'summary' => 1], 'xx');
        $this->assertIsString($ftssql);
        $this->assertIsArray($params);
        $sql = "SELECT c.*, fts.score
                  FROM {{$coursesearchtable}} cs
                  JOIN {$ftssql} fts ON fts.id = cs.id
                  JOIN {{$coursetable}} c ON c.id = cs.courseid
                 WHERE c.visible = 1
              ORDER BY fts.score DESC, c.fullname ASC";
        $result = $DB->get_records_sql($sql, $params);
        $this->assertCount(0, $result);

        // Search for an incomplete word should not match anything,
        // this proves full text search is not good for autocompletion!
        list($ftssql, $params) = $DB->get_fts_subquery($coursesearchtable, ['shortname' => 100, 'fullname' => 10, 'summary' => 1], 'Java');
        $this->assertIsString($ftssql);
        $this->assertIsArray($params);
        $sql = "SELECT c.*, fts.score
                  FROM {{$coursesearchtable}} cs
                  JOIN {$ftssql} fts ON fts.id = cs.id
                  JOIN {{$coursetable}} c ON c.id = cs.courseid
                 WHERE c.visible = 1
              ORDER BY fts.score DESC, c.fullname ASC";
        $result = $DB->get_records_sql($sql, $params);
        $this->assertCount(0, $result);

        // Search one phrase.
        list($ftssql, $params) = $DB->get_fts_subquery($coursesearchtable, ['fullname' => 1], '"PHP development"');
        $this->assertIsString($ftssql);
        $this->assertIsArray($params);
        $sql = "SELECT c.*, fts.score
                  FROM {{$coursesearchtable}} cs
                  JOIN {$ftssql} fts ON fts.id = cs.id
                  JOIN {{$coursetable}} c ON c.id = cs.courseid
              ORDER BY fts.score DESC, c.id DESC";
        $result = $DB->get_records_sql($sql, $params);
        $count = count($result);
        $this->assertGreaterThanOrEqual(1, $count);
        $first = reset($result);
        $this->assertEquals($first->id, $courses[0]->id);
        // NOTE: pg is not great with phrase searching, especially in old versions < 9.6,
        //       MS SQL Server may return more results too.
        $this->assertLessThanOrEqual(4, $count);

        // Other languages: Czech.
        list($ftssql, $params) = $DB->get_fts_subquery($coursesearchtable, ['summary' => 1], 'žluťoučký koníček');
        $this->assertIsString($ftssql);
        $this->assertIsArray($params);
        $sql = "SELECT c.*, fts.score
                  FROM {{$coursesearchtable}} cs
                  JOIN {$ftssql} fts ON fts.id = cs.id
                  JOIN {{$coursetable}} c ON c.id = cs.courseid
                 WHERE c.visible = 1
              ORDER BY fts.score DESC, c.fullname ASC";
        $result = $DB->get_records_sql($sql, $params);
        $this->assertCount(1, $result);
        $this->assertSame(array((int)$courses[6]->id), array_keys($result));

        // Other languages: Russian.
        list($ftssql, $params) = $DB->get_fts_subquery($coursesearchtable, ['fullname' => 1], 'достижений народного');
        $this->assertIsString($ftssql);
        $this->assertIsArray($params);
        $sql = "SELECT c.*, fts.score
                  FROM {{$coursesearchtable}} cs
                  JOIN {$ftssql} fts ON fts.id = cs.id
                  JOIN {{$coursetable}} c ON c.id = cs.courseid
                 WHERE c.visible = 1
              ORDER BY fts.score DESC, c.fullname ASC";
        $result = $DB->get_records_sql($sql, $params);
        $this->assertCount(1, $result);
        $this->assertSame(array((int)$courses[7]->id), array_keys($result));

        // RTL languages: Hebrew
        list($ftssql, $params) = $DB->get_fts_subquery($coursesearchtable, ['summary' => 1], 'שיעורי ספורט');
        $this->assertIsString($ftssql);
        $this->assertIsArray($params);
        $sql = "SELECT c.*, fts.score
                  FROM {{$coursesearchtable}} cs
                  JOIN {$ftssql} fts ON fts.id = cs.id
                  JOIN {{$coursetable}} c ON c.id = cs.courseid
                 WHERE c.visible = 1
              ORDER BY fts.score DESC, c.fullname ASC";
        $result = $DB->get_records_sql($sql, $params);
        $this->assertCount(1, $result);
        $this->assertSame(array((int)$courses[9]->id), array_keys($result));

        $dbman->drop_table(new xmldb_table($coursetable));
        $dbman->drop_table(new xmldb_table($coursesearchtable));
    }

    /**
     * Test that PHP Intl extension works properly.
     */
    public function test_word_breaking_for_search() {
        $i = IntlBreakIterator::createWordInstance('en_AU');
        $i->setText('Psíček uviděl stromeček. = Dog saw a tree.');
        $words = array();
        foreach ($i->getPartsIterator() as $word) {
            $words[] = $word;
        }
        $this->assertSame('Psíček/ /uviděl/ /stromeček/./ /=/ /Dog/ /saw/ /a/ /tree/.', implode('/', $words));
    }

    /**
     * Test that PHP Intl extension works properly.
     */
    public function test_word_breaking_for_search_complex() {
        if (!defined('INTL_ICU_VERSION') or version_compare('57', INTL_ICU_VERSION, '>')) {
            // Most likely RedHat with outdated ICU.
            $this->markTestSkipped('Outdated ICU detected');
        }

        $i = IntlBreakIterator::createWordInstance('en_AU');
        $i->setText('Psíček uviděl stromeček. : Dog saw a tree. : 犬が木を見た。 : 狗看到树');
        $words = array();
        foreach($i->getPartsIterator() as $word) {
            $words[] = $word;
        }
        $this->assertSame('Psíček/ /uviděl/ /stromeček/./ /:/ /Dog/ /saw/ /a/ /tree/./ /:/ /犬/が/木/を/見/た/。/ /:/ /狗/看到/树', implode('/', $words));
    }

    public function test_apply_fts_3b_workaround() {
        $DB = $this->tdb;

        if (!defined('INTL_ICU_VERSION') or version_compare('57', INTL_ICU_VERSION, '>')) {
            // Most likely RedHat with outdated ICU.
            $this->markTestSkipped('Outdated ICU detected');
        }

        $result = $DB->apply_fts_3b_workaround('Psíček uviděl stromeček. : Dog saw a tree. : 犬が木を見た。 : 狗看到树');
        $expected = 'Psíček uviděl stromeček. : Dog saw a tree. :  犬xx  がxx  木xx  をxx  見xx  たxx   :  狗xx  看到xx  树xx ';
        $this->assertSame($expected, $result);
    }

    public function test_get_fts_subquery_japanese() {
        $DB = $this->tdb;
        $dbman = $this->tdb->get_manager();
        $dbfamily = $DB->get_dbfamily();

        if ($dbfamily !== 'mssql') {
            if (!defined('INTL_ICU_VERSION') or version_compare('57', INTL_ICU_VERSION, '>')) {
                // Most likely RedHat with outdated ICU.
                $this->markTestSkipped('Outdated ICU detected');
            }
        }

        if ($dbfamily === 'postgres') {
            $this->override_dboption('ftslanguage', 'english');
            $this->override_dboption('fts3bworkaround', true);
        } else if ($dbfamily === 'mysql') {
            $this->override_dboption('fts3bworkaround', true);
        } else if ($dbfamily === 'mssql') {
            $this->override_dboption('ftslanguage', 'Japanese');
            $this->override_dboption('fts3bworkaround', false);
        }

        $coursetable = 'test_table_course';
        $table = new xmldb_table($coursetable);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('visible', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('fullname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('summary', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('summaryformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $dbman->create_table($table);

        $coursesearchtable = 'test_table_course_search';
        $table = new xmldb_table($coursesearchtable);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('fullname', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('shortname', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('summary', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, array('courseid'), 'course', array('id'));
        $table->add_index('fullname', XMLDB_INDEX_NOTUNIQUE, array('fullname'), array('full_text_search'));
        $table->add_index('shortname', XMLDB_INDEX_NOTUNIQUE, array('shortname'), array('full_text_search'));
        $table->add_index('summary', XMLDB_INDEX_NOTUNIQUE, array('summary'), array('full_text_search'));
        $dbman->create_table($table);

        // Create some fake courses.
        $now = time();
        $courses = array();
        $courses[0] = $DB->insert_record($coursetable, (object)array(
            'fullname' => 'Old style PHP development',
            'shortname' => 'Old PHP',
            'summary' => '<p>Good old times when <strong>web applications</strong> were single pages written purely in PHP.<p>',
            'summaryformat' => FORMAT_HTML,
            'timemodified' => $now,
        ));
        $courses[1] = $DB->insert_record($coursetable, (object)array(
            'fullname' => 'New PHP frameworks',
            'shortname' => 'Frameworks',
            'summary' => 'Fancy new PHP frameworks that can be used to build some great systems.',
            'summaryformat' => FORMAT_MARKDOWN,
            'timemodified' => $now,
        ));
        $courses[2] = $DB->insert_record($coursetable, (object)array(
            'fullname' => 'Web development basics',
            'shortname' => 'Basics',
            'summary' => 'Quick overview of different approaches to building of modern web applications including PHP, Ajax, REST, Node, Ruby and other fancy stuff.',
            'summaryformat' => FORMAT_MOODLE,
            'timemodified' => $now,
        ));
        $courses[3] = $DB->insert_record($coursetable, (object)array(
            'fullname' => 'Javascript rulez',
            'shortname' => 'JS',
            'summary' => 'Nothing is impossible with JavaScript! Prepare to be amazed how great this ancient language can be when used for the good of mankind.',
            'summaryformat' => FORMAT_MOODLE,
            'timemodified' => $now,
        ));
        $courses[4] = $DB->insert_record($coursetable, (object)array(
            'fullname' => 'Future of development with PHP',
            'shortname' => 'PHP666',
            'summary' => 'Should we use PHP for any new projects?',
            'summaryformat' => FORMAT_MARKDOWN,
            'visible' => 0,
            'timemodified' => $now,
        ));
        $courses[5] = $DB->insert_record($coursetable, (object)array(
            'fullname' => 'Course with null data',
            'shortname' => null,
            'summary' => null,
            'summaryformat' => FORMAT_MARKDOWN,
            'visible' => 0,
            'timemodified' => $now,
        ));
        $courses[6] = $DB->insert_record($coursetable, (object)array( // Czech test course.
            'fullname' => 'Žluťoučký koníček',
            'shortname' => 'Koníček',
            'summary' => 'Šíleně žluťoučký koníček úpěl ďábelské ódy.',
            'summaryformat' => FORMAT_MARKDOWN,
            'timemodified' => $now,
        ));
        $courses[7] = $DB->insert_record($coursetable, (object)array( // Russian test course.
            'fullname' => 'Выставка достижений народного хозяйства',
            'shortname' => 'ВДНХ',
            'summary' => 'Learn more about the former trade show in Moscow',
            'summaryformat' => FORMAT_MARKDOWN,
            'timemodified' => $now,
        ));
        $courses[8] = $DB->insert_record($coursetable, (object)array( // Japanese test course - something about a dog and tree (hopefully).
            'fullname' => '犬と木', // Dog and tree.
            'shortname' => '犬', // Dog.
            'summary' => 'Psíček uviděl stromeček. = Dog saw a tree. = 犬が木を見た。',
            'summaryformat' => FORMAT_MARKDOWN,
            'timemodified' => $now,
        ));
        // NOTE: the results may be different if there are only a few records in the search table,
        //       this is because the databases may ignore words that are repeated in all rows.
        foreach ($courses as $i => $id) {
            $courses[$i] = $DB->get_record($coursetable, array('id' => $id), '*', MUST_EXIST);
        }

        // Fill the course search table with data, these will have to be kept in sync via events in real code.
        foreach ($courses as $i => $course) {
            $record = new stdClass();
            $record->courseid = $course->id;
            $record->fullname = $DB->unformat_fts_content($course->fullname, FORMAT_HTML); // This is not plain text because it may have multilang!
            $record->shortname = $DB->unformat_fts_content($course->shortname, FORMAT_HTML); // This is not plain text because it may have multilang!
            $record->summary = $DB->unformat_fts_content($course->summary, $course->summaryformat);
            $record->timemodified = time(); // Keep track of last update.
            $DB->insert_record($coursesearchtable, $record);
        }
        $this->wait_for_mssql_fts_indexing($coursesearchtable);

        list($ftssql, $params) = $DB->get_fts_subquery($coursesearchtable, ['summary' => 1], '犬');
        $this->assertIsString($ftssql);
        $this->assertIsArray($params);
        $sql = "SELECT c.*, fts.score
                  FROM {{$coursesearchtable}} cs
                  JOIN {$ftssql} fts ON fts.id = cs.id
                  JOIN {{$coursetable}} c ON c.id = cs.courseid
                 WHERE c.visible = 1
              ORDER BY fts.score DESC, c.fullname ASC";
        $result = $DB->get_records_sql($sql, $params);
        $this->assertCount(1, $result);
        $this->assertSame(array((int)$courses[8]->id), array_keys($result));

        // Not sure if it is correct, but MS SQL Server does not search for these two without space.
        list($ftssql, $params) = $DB->get_fts_subquery($coursesearchtable, ['summary' => 1], '犬 木');
        $this->assertIsString($ftssql);
        $this->assertIsArray($params);
        $sql = "SELECT c.*, fts.score
                  FROM {{$coursesearchtable}} cs
                  JOIN {$ftssql} fts ON fts.id = cs.id
                  JOIN {{$coursetable}} c ON c.id = cs.courseid
                 WHERE c.visible = 1
              ORDER BY fts.score DESC, c.fullname ASC";
        $result = $DB->get_records_sql($sql, $params);
        $this->assertCount(1, $result);
        $this->assertSame(array((int)$courses[8]->id), array_keys($result));

        $dbman->drop_table(new xmldb_table($coursetable));
        $dbman->drop_table(new xmldb_table($coursesearchtable));
    }

    public function test_get_fts_subquery_chinese_traditional() {
        $DB = $this->tdb;
        $dbman = $this->tdb->get_manager();
        $dbfamily = $DB->get_dbfamily();

        if ($dbfamily !== 'mssql') {
            if (!defined('INTL_ICU_VERSION') or version_compare('57', INTL_ICU_VERSION, '>')) {
                // Most likely RedHat with outdated ICU.
                $this->markTestSkipped('Outdated ICU detected');
            }
        }

        if ($dbfamily === 'postgres') {
            $this->override_dboption('ftslanguage', 'english');
            $this->override_dboption('fts3bworkaround', true);
        } else if ($dbfamily === 'mysql') {
            $this->override_dboption('fts3bworkaround', true);
        } else if ($dbfamily === 'mssql') {
            $this->override_dboption('ftslanguage', 1028); // Traditional Chinese
            $this->override_dboption('fts3bworkaround', false);
        }

        $coursetable = 'test_table_course';
        $table = new xmldb_table($coursetable);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('visible', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('fullname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('summary', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('summaryformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $dbman->create_table($table);

        $coursesearchtable = 'test_table_course_search';
        $table = new xmldb_table($coursesearchtable);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('fullname', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('shortname', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('summary', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, array('courseid'), 'course', array('id'));
        $table->add_index('fullname', XMLDB_INDEX_NOTUNIQUE, array('fullname'), array('full_text_search'));
        $table->add_index('shortname', XMLDB_INDEX_NOTUNIQUE, array('shortname'), array('full_text_search'));
        $table->add_index('summary', XMLDB_INDEX_NOTUNIQUE, array('summary'), array('full_text_search'));
        $dbman->create_table($table);

        // Create some fake courses.
        $now = time();
        $courses = array();
        $courses[0] = $DB->insert_record($coursetable, (object)array(
            'fullname' => 'Old style PHP development',
            'shortname' => 'Old PHP',
            'summary' => '<p>Good old times when <strong>web applications</strong> were single pages written purely in PHP.<p>',
            'summaryformat' => FORMAT_HTML,
            'timemodified' => $now,
        ));
        $courses[1] = $DB->insert_record($coursetable, (object)array(
            'fullname' => 'New PHP frameworks',
            'shortname' => 'Frameworks',
            'summary' => 'Fancy new PHP frameworks that can be used to build some great systems.',
            'summaryformat' => FORMAT_MARKDOWN,
            'timemodified' => $now,
        ));
        $courses[2] = $DB->insert_record($coursetable, (object)array(
            'fullname' => 'Web development basics',
            'shortname' => 'Basics',
            'summary' => 'Quick overview of different approaches to building of modern web applications including PHP, Ajax, REST, Node, Ruby and other fancy stuff.',
            'summaryformat' => FORMAT_MOODLE,
            'timemodified' => $now,
        ));
        $courses[3] = $DB->insert_record($coursetable, (object)array(
            'fullname' => 'Javascript rulez',
            'shortname' => 'JS',
            'summary' => 'Nothing is impossible with JavaScript! Prepare to be amazed how great this ancient language can be when used for the good of mankind.',
            'summaryformat' => FORMAT_MOODLE,
            'timemodified' => $now,
        ));
        $courses[4] = $DB->insert_record($coursetable, (object)array(
            'fullname' => 'Future of development with PHP',
            'shortname' => 'PHP666',
            'summary' => 'Should we use PHP for any new projects?',
            'summaryformat' => FORMAT_MARKDOWN,
            'visible' => 0,
            'timemodified' => $now,
        ));
        $courses[5] = $DB->insert_record($coursetable, (object)array(
            'fullname' => 'Course with null data',
            'shortname' => null,
            'summary' => null,
            'summaryformat' => FORMAT_MARKDOWN,
            'visible' => 0,
            'timemodified' => $now,
        ));
        $courses[6] = $DB->insert_record($coursetable, (object)array( // Czech test course.
            'fullname' => 'Žluťoučký koníček',
            'shortname' => 'Koníček',
            'summary' => 'Šíleně žluťoučký koníček úpěl ďábelské ódy.',
            'summaryformat' => FORMAT_MARKDOWN,
            'timemodified' => $now,
        ));
        $courses[7] = $DB->insert_record($coursetable, (object)array( // Russian test course.
            'fullname' => 'Выставка достижений народного хозяйства',
            'shortname' => 'ВДНХ',
            'summary' => 'Learn more about the former trade show in Moscow',
            'summaryformat' => FORMAT_MARKDOWN,
            'timemodified' => $now,
        ));
        $courses[8] = $DB->insert_record($coursetable, (object)array( // Chinese test course - something about a dog and tree (hopefully, blame Google translate if not).
            'fullname' => '狗和樹', // Dog and tree.
            'shortname' => '狗', // Dog.
            'summary' => 'Psíček vidí stromeček. = Dog sees the tree. = 狗看到樹',
            'summaryformat' => FORMAT_MARKDOWN,
            'timemodified' => $now,
        ));
        // NOTE: the results may be different if there are only a few records in the search table,
        //       this is because the databases may ignore words that are repeated in all rows.
        foreach ($courses as $i => $id) {
            $courses[$i] = $DB->get_record($coursetable, array('id' => $id), '*', MUST_EXIST);
        }

        // Fill the course search table with data, these will have to be kept in sync via events in real code.
        foreach ($courses as $i => $course) {
            $record = new stdClass();
            $record->courseid = $course->id;
            $record->fullname = $DB->unformat_fts_content($course->fullname, FORMAT_HTML); // This is not plain text because it may have multilang!
            $record->shortname = $DB->unformat_fts_content($course->shortname, FORMAT_HTML); // This is not plain text because it may have multilang!
            $record->summary = $DB->unformat_fts_content($course->summary, $course->summaryformat);
            $record->timemodified = time(); // Keep track of last update.
            $DB->insert_record($coursesearchtable, $record);
        }
        $this->wait_for_mssql_fts_indexing($coursesearchtable);

        list($ftssql, $params) = $DB->get_fts_subquery($coursesearchtable, ['summary' => 1], '狗');
        $this->assertIsString($ftssql);
        $this->assertIsArray($params);
        $sql = "SELECT c.*, fts.score
                  FROM {{$coursesearchtable}} cs
                  JOIN {$ftssql} fts ON fts.id = cs.id
                  JOIN {{$coursetable}} c ON c.id = cs.courseid
                 WHERE c.visible = 1
              ORDER BY fts.score DESC, c.fullname ASC";
        $result = $DB->get_records_sql($sql, $params);
        $this->assertCount(1, $result);
        $this->assertSame(array((int)$courses[8]->id), array_keys($result));

        // Not sure if it is correct, but MS SQL Server does not search for these two without space.
        list($ftssql, $params) = $DB->get_fts_subquery($coursesearchtable, ['summary' => 1], '狗 樹');
        $this->assertIsString($ftssql);
        $this->assertIsArray($params);
        $sql = "SELECT c.*, fts.score
                  FROM {{$coursesearchtable}} cs
                  JOIN {$ftssql} fts ON fts.id = cs.id
                  JOIN {{$coursetable}} c ON c.id = cs.courseid
                 WHERE c.visible = 1
              ORDER BY fts.score DESC, c.fullname ASC";
        $result = $DB->get_records_sql($sql, $params);
        $this->assertCount(1, $result);
        $this->assertSame(array((int)$courses[8]->id), array_keys($result));

        $dbman->drop_table(new xmldb_table($coursetable));
        $dbman->drop_table(new xmldb_table($coursesearchtable));
    }

    public function test_get_fts_subquery_chinese_simplified() {
        $DB = $this->tdb;
        $dbman = $this->tdb->get_manager();
        $dbfamily = $DB->get_dbfamily();

        if ($dbfamily !== 'mssql') {
            if (!defined('INTL_ICU_VERSION') or version_compare('57', INTL_ICU_VERSION, '>')) {
                // Most likely RedHat with outdated ICU.
                $this->markTestSkipped('Outdated ICU detected');
            }
        }

        if ($dbfamily === 'postgres') {
            $this->override_dboption('ftslanguage', 'english');
            $this->override_dboption('fts3bworkaround', true);
        } else if ($dbfamily === 'mysql') {
            $this->override_dboption('fts3bworkaround', true);
        } else if ($dbfamily === 'mssql') {
            $this->override_dboption('ftslanguage', 2052); // Simplified Chinese
            $this->override_dboption('fts3bworkaround', false);
        }

        $coursetable = 'test_table_course';
        $table = new xmldb_table($coursetable);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('visible', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('fullname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('summary', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('summaryformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $dbman->create_table($table);

        $coursesearchtable = 'test_table_course_search';
        $table = new xmldb_table($coursesearchtable);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('fullname', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('shortname', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('summary', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, array('courseid'), 'course', array('id'));
        $table->add_index('fullname', XMLDB_INDEX_NOTUNIQUE, array('fullname'), array('full_text_search'));
        $table->add_index('shortname', XMLDB_INDEX_NOTUNIQUE, array('shortname'), array('full_text_search'));
        $table->add_index('summary', XMLDB_INDEX_NOTUNIQUE, array('summary'), array('full_text_search'));
        $dbman->create_table($table);

        // Create some fake courses.
        $now = time();
        $courses = array();
        $courses[0] = $DB->insert_record($coursetable, (object)array(
            'fullname' => 'Old style PHP development',
            'shortname' => 'Old PHP',
            'summary' => '<p>Good old times when <strong>web applications</strong> were single pages written purely in PHP.<p>',
            'summaryformat' => FORMAT_HTML,
            'timemodified' => $now,
        ));
        $courses[1] = $DB->insert_record($coursetable, (object)array(
            'fullname' => 'New PHP frameworks',
            'shortname' => 'Frameworks',
            'summary' => 'Fancy new PHP frameworks that can be used to build some great systems.',
            'summaryformat' => FORMAT_MARKDOWN,
            'timemodified' => $now,
        ));
        $courses[2] = $DB->insert_record($coursetable, (object)array(
            'fullname' => 'Web development basics',
            'shortname' => 'Basics',
            'summary' => 'Quick overview of different approaches to building of modern web applications including PHP, Ajax, REST, Node, Ruby and other fancy stuff.',
            'summaryformat' => FORMAT_MOODLE,
            'timemodified' => $now,
        ));
        $courses[3] = $DB->insert_record($coursetable, (object)array(
            'fullname' => 'Javascript rulez',
            'shortname' => 'JS',
            'summary' => 'Nothing is impossible with JavaScript! Prepare to be amazed how great this ancient language can be when used for the good of mankind.',
            'summaryformat' => FORMAT_MOODLE,
            'timemodified' => $now,
        ));
        $courses[4] = $DB->insert_record($coursetable, (object)array(
            'fullname' => 'Future of development with PHP',
            'shortname' => 'PHP666',
            'summary' => 'Should we use PHP for any new projects?',
            'summaryformat' => FORMAT_MARKDOWN,
            'visible' => 0,
            'timemodified' => $now,
        ));
        $courses[5] = $DB->insert_record($coursetable, (object)array(
            'fullname' => 'Course with null data',
            'shortname' => null,
            'summary' => null,
            'summaryformat' => FORMAT_MARKDOWN,
            'visible' => 0,
            'timemodified' => $now,
        ));
        $courses[6] = $DB->insert_record($coursetable, (object)array( // Czech test course.
            'fullname' => 'Žluťoučký koníček',
            'shortname' => 'Koníček',
            'summary' => 'Šíleně žluťoučký koníček úpěl ďábelské ódy.',
            'summaryformat' => FORMAT_MARKDOWN,
            'timemodified' => $now,
        ));
        $courses[7] = $DB->insert_record($coursetable, (object)array( // Russian test course.
            'fullname' => 'Выставка достижений народного хозяйства',
            'shortname' => 'ВДНХ',
            'summary' => 'Learn more about the former trade show in Moscow',
            'summaryformat' => FORMAT_MARKDOWN,
            'timemodified' => $now,
        ));
        $courses[8] = $DB->insert_record($coursetable, (object)array( // Chinese test course - something about a dog and tree (hopefully, blame Google translate if not).
            'fullname' => '狗和树', // Dog and tree.
            'shortname' => '狗', // Dog.
            'summary' => 'Psíček vidí stromeček. = Dog sees the tree. = 狗看到树',
            'summaryformat' => FORMAT_MARKDOWN,
            'timemodified' => $now,
        ));
        // NOTE: the results may be different if there are only a few records in the search table,
        //       this is because the databases may ignore words that are repeated in all rows.
        foreach ($courses as $i => $id) {
            $courses[$i] = $DB->get_record($coursetable, array('id' => $id), '*', MUST_EXIST);
        }

        // Fill the course search table with data, these will have to be kept in sync via events in real code.
        foreach ($courses as $i => $course) {
            $record = new stdClass();
            $record->courseid = $course->id;
            $record->fullname = $DB->unformat_fts_content($course->fullname, FORMAT_HTML); // This is not plain text because it may have multilang!
            $record->shortname = $DB->unformat_fts_content($course->shortname, FORMAT_HTML); // This is not plain text because it may have multilang!
            $record->summary = $DB->unformat_fts_content($course->summary, $course->summaryformat);
            $record->timemodified = time(); // Keep track of last update.
            $DB->insert_record($coursesearchtable, $record);
        }
        $this->wait_for_mssql_fts_indexing($coursesearchtable);

        list($ftssql, $params) = $DB->get_fts_subquery($coursesearchtable, ['summary' => 1], '狗');
        $this->assertIsString($ftssql);
        $this->assertIsArray($params);
        $sql = "SELECT c.*, fts.score
                  FROM {{$coursesearchtable}} cs
                  JOIN {$ftssql} fts ON fts.id = cs.id
                  JOIN {{$coursetable}} c ON c.id = cs.courseid
                 WHERE c.visible = 1
              ORDER BY fts.score DESC, c.fullname ASC";
        $result = $DB->get_records_sql($sql, $params);
        $this->assertCount(1, $result);
        $this->assertSame(array((int)$courses[8]->id), array_keys($result));

        // Not sure if it is correct, but MS SQL Server does not search for these two without space.
        list($ftssql, $params) = $DB->get_fts_subquery($coursesearchtable, ['summary' => 1], '狗 树');
        $this->assertIsString($ftssql);
        $this->assertIsArray($params);
        $sql = "SELECT c.*, fts.score
                  FROM {{$coursesearchtable}} cs
                  JOIN {$ftssql} fts ON fts.id = cs.id
                  JOIN {{$coursetable}} c ON c.id = cs.courseid
                 WHERE c.visible = 1
              ORDER BY fts.score DESC, c.fullname ASC";
        $result = $DB->get_records_sql($sql, $params);
        $this->assertCount(1, $result);
        $this->assertSame(array((int)$courses[8]->id), array_keys($result));

        $dbman->drop_table(new xmldb_table($coursetable));
        $dbman->drop_table(new xmldb_table($coursesearchtable));
    }

    /**
     * Tests that recommends_counted_recordset returns the expected value.
     *
     * Expected results are thanks to performance testing completed for each database.
     * For results on performance testing of paginated results see moodle_database class.
     */
    public function test_recommends_counted_recordset() {
        global $DB;
        $expected = false;
        $dbfamily = $DB->get_dbvendor(); // Cannot use get_dbfamily() here because MariaDB returns 'mysql'.
        if ($dbfamily === 'mariadb') {
            $expected = true;
        }
        self::assertSame($expected, $DB->recommends_counted_recordset());
    }

    public function test_get_max_in_params() {
        global $CFG;

        $DB = $this->tdb;

        // Basic check for default value.
        if (empty($CFG->dboptions['maxinparams'])) {
            self::assertEquals(30000, $DB->get_max_in_params());
        } else {
            self::assertEquals($CFG->dboptions['maxinparams'], $DB->get_max_in_params());
        }

        // Override 'maxinparams' in dboptions for this test.
        $this->override_dboption('maxinparams', 100);

        // Check we have a new value now.
        self::assertEquals(100, $DB->get_max_in_params());

        // Check get_in_or_equal() warns about exceeding maximum number allowed.
        $params = range(1, 1000);
        $DB->get_in_or_equal($params);
        self::assertDebuggingCalled("The number of parameters passed (1000) exceeds maximum number allowed (100)", DEBUG_DEVELOPER);

        // Check get_in_or_equal() passes with the maximum number allowed.
        $params = range(1, 100);
        $DB->get_in_or_equal($params);
        self::assertDebuggingNotCalled();
    }
}
