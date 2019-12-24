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
}
