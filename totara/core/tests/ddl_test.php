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

class totara_core_ddl_testcase extends database_driver_testcase {
    public function test_change_field_type() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        // Create the dummy table.
        $table = new xmldb_table('test_table');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('changeme', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'id');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $dbman->create_table($table);

        // Insert some dummy data.
        $todb = new stdClass();
        $todb->changeme = "1234567890";
        $DB->insert_record('test_table', $todb);
        $todb->changeme = 1237894560;
        $DB->insert_record('test_table', $todb);
        $todb->changeme = "0";
        $DB->insert_record('test_table', $todb);
        $todb->changeme = "   0321654987   ";
        $DB->insert_record('test_table', $todb);
        $todb->changeme = "-1";
        $DB->insert_record('test_table', $todb);
        $todb->changeme = "-123456789";
        $DB->insert_record('test_table', $todb);
        $todb->changeme = "123abc789";
        $DB->insert_record('test_table', $todb);
        $DB->insert_record('test_table', $todb);
        $todb->changeme = "abcdefghij";
        $DB->insert_record('test_table', $todb);
        $todb->changeme = "abcdefghijklmnopqrstuvwxyz";
        $DB->insert_record('test_table', $todb);
        $todb->changeme = "1234567891234567891234569789";
        $DB->insert_record('test_table', $todb);
        $todb->changeme = "123 456 789 0";
        $DB->insert_record('test_table', $todb);
        $todb->changeme = "";
        $DB->insert_record('test_table', $todb);
        $todb->changeme = "    ";

        $this->assertEquals(13, $DB->count_records('test_table'));

        // Run the checks from the upgrade.
        $records = $DB->get_recordset('test_table');
        foreach ($records as $record) {
            if (!preg_match('/^[0-9]{1,10}$/', $record->changeme)) {
                $DB->delete_records('test_table', array('id' => $record->id));
                continue;
            }
        }
        $records->close();

        // Only the first three should pass the checks.
        $this->assertEquals(3, $DB->count_records('test_table'));

        // Run the field change.
        $field_integer = new xmldb_field('changeme', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'id');
        $dbman->change_field_type($table, $field_integer);

        // Check the results.
        $this->assertEquals(3, $DB->count_records('test_table'));
        $records = $DB->get_records('test_table');
        $this->assertEquals(1234567890, $records[1]->changeme);
        $this->assertEquals(1237894560, $records[2]->changeme);
        $this->assertEquals(0, $records[3]->changeme);

        $dbman->drop_table($table);
    }

    public function test_create_temp_table_large() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        // Create the dummy table.
        $table = new xmldb_table('test_table');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $prefield = 'id';
        for ($i = 1; $i < 64; $i++) {
            $field = 'varfield' . $i;
            $table->add_field($field, XMLDB_TYPE_CHAR, '255', null, null, null, null, $prefield);
            $prefield = $field;
        }
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $dbman->create_temp_table($table);

        $record = new stdClass();
        for ($i = 1; $i < 80; $i++) {
            $record->{'varfield' . $i} = str_pad('š', 255);
        }
        $DB->insert_record('test_table', $record);

        $dbman->drop_table($table);
    }

    public function test_manage_table_with_reserved_columns() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $table = new xmldb_table('test_table');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('from', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'id');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $dbman->create_table($table);
        $columns = $DB->get_columns($table->getName());
        $this->assertArrayHasKey('from', $columns);

        $table = new xmldb_table('test_table');
        $field = new xmldb_field('from');
        $field->set_attributes(XMLDB_TYPE_CHAR, '20', null, null, null, 'general', 'id');
        $dbman->rename_field($table, $field, 'where');
        $columns = $DB->get_columns($table->getName());
        $this->assertArrayNotHasKey('from', $columns);
        $this->assertArrayHasKey('where', $columns);

        $table = new xmldb_table('test_table');
        $field = new xmldb_field('where');
        $field->set_attributes(XMLDB_TYPE_CHAR, '20', null, null, null, 'general', 'id');
        $dbman->drop_field($table, $field);
        $columns = $DB->get_columns($table->getName());
        $this->assertArrayNotHasKey('from', $columns);
        $this->assertArrayNotHasKey('where', $columns);

        $dbman->drop_table($table);
    }

    public function test_create_search_index() {
        $DB = $this->tdb;
        $dbman = $this->tdb->get_manager();
        $prefix = $DB->get_prefix();

        $tablename = 'test_table_search';
        $table = new xmldb_table($tablename);

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('high', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_field('low', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('course', XMLDB_INDEX_UNIQUE, array('course'));
        $table->add_index('high', XMLDB_INDEX_NOTUNIQUE, array('high'), array('full_text_search'));
        $table->add_index('low', XMLDB_INDEX_NOTUNIQUE, array('low'), array('full_text_search'));

        $dbman->create_table($table);
        $this->assertTrue($dbman->table_exists($table));
        $this->assertTrue($dbman->field_exists($table, 'high'));
        $this->assertTrue($dbman->field_exists($table, 'low'));
        $this->assertTrue($dbman->index_exists($table, new xmldb_index('high', XMLDB_INDEX_NOTUNIQUE, array('high'), 'full_text_search')));
        $this->assertTrue($dbman->index_exists($table, new xmldb_index('low', XMLDB_INDEX_NOTUNIQUE, array('low'), 'full_text_search')));

        // Insert some data and perform database specific full text search to make sure
        // it works as expected - case and accent insensitive.

        $ids = [];
        $ids[0] = $DB->insert_record($tablename, array('course' => 10, 'high' => 'Žluťoučký koníček')); // 'Green horse' in Czech.
        $ids[1] = $DB->insert_record($tablename, array('course' => 11, 'high' => 'zlutoucky Konicek')); // 'Green horse' in Czech without accents.
        $ids[2] = $DB->insert_record($tablename, array('course' => 12, 'high' => 'abc def'));

        $this->wait_for_mssql_fts_indexing($tablename);

        if ($DB->get_dbfamily() === 'postgres') {
            // By default PostgreSQL is accent sensitive, you nee to create a new config to make accent insensitive searches,
            // see http://rachbelaid.com/postgres-full-text-search-is-good-enough/

            $ftslanguage = $DB->get_ftslanguage();
            $sql = "SELECT t.id, t.course
                      FROM {{$tablename}} t
                     WHERE to_tsvector('$ftslanguage', t.high) @@ plainto_tsquery(:search)
                  ORDER BY t.id";
            $params = array('search' => 'zLUtoucky');

        } else if ($DB->get_dbfamily() === 'mysql') {
            $sql = "SELECT t.id, t.course
                      FROM {{$tablename}} t
                     WHERE MATCH (t.high) AGAINST (:search IN NATURAL LANGUAGE MODE)
                  ORDER BY t.id";
            $params = array('search' => 'zLUtoucky');

        } else if ($DB->get_dbfamily() === 'mssql') {
            $sql = "SELECT t.id, t.course
                      FROM {{$tablename}} t
                      WHERE FREETEXT(t.high, :search) 
                  ORDER BY t.id";
            $params = array('search' => 'zLUtoucky');
        }

        $result = $DB->get_records_sql($sql, $params);

        $this->assertGreaterThanOrEqual(1, count($result));
        $this->assertLessThanOrEqual(2, count($result));

        $this->assertArrayHasKey($ids[1], $result);
        if (count($result) == 2) {
            $this->assertArrayHasKey($ids[0], $result);
        }

        $dbman->drop_table($table);
    }

    public function test_create_search_index_from_file() {
        $dbman = $this->tdb->get_manager();

        $tablename = 'test_table_search';

        $dbman->install_from_xmldb_file(__DIR__ . '/fixtures/xmldb_search_table.xml');
        $this->assertTrue($dbman->table_exists($tablename));

        $dbman->drop_table(new xmldb_table($tablename));
    }

    public function test_create_invalid_search_index() {
        $dbman = $this->tdb->get_manager();

        $tablename = 'test_table_search';
        $table = new xmldb_table($tablename);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('high', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('high', XMLDB_INDEX_NOTUNIQUE, array('high', 'id'), array('full_text_search'));
        try {
            $dbman->create_table($table);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Full text search index must be over one text field only', $ex->getMessage());
        }

        $tablename = 'test_table_search';
        $table = new xmldb_table($tablename);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('high', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('high', XMLDB_INDEX_NOTUNIQUE, array('high'), array('full_text_search', 'xx'));
        try {
            $dbman->create_table($table);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Full text search index must be the only hint', $ex->getMessage());
        }

        $tablename = 'test_table_search';
        $table = new xmldb_table($tablename);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('high', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('high', XMLDB_INDEX_UNIQUE, array('high'), array('full_text_search'));
        try {
            $dbman->create_table($table);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Full text search index cannot be unique', $ex->getMessage());
        }

        $tablename = 'test_table_search';
        $table = new xmldb_table($tablename);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('high', XMLDB_TYPE_CHAR, 255, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('high', XMLDB_INDEX_NOTUNIQUE, array('high'), array('full_text_search'));
        try {
            $dbman->create_table($table);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Full text search index can be used for text fields only', $ex->getMessage());
        }

        $tablename = 'test_table_search';
        $table = new xmldb_table($tablename);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('high', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('high', XMLDB_INDEX_NOTUNIQUE, array('high'), array('full_text_search'));
        try {
            $dbman->create_table($table);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Full text search index can be used for text fields that allow nulls only', $ex->getMessage());
        }

        $this->assertDebuggingNotCalled();
        $tablename = 'test_table_search';
        $table = new xmldb_table($tablename);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('high', XMLDB_TYPE_TEXT, null, null, null, null, 'abc');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('high', XMLDB_INDEX_NOTUNIQUE, array('high'), array('full_text_search'));
        $dbman->create_table($table);
        $this->assertDebuggingCalled('XMLDB has detected one TEXT/BINARY column (high) with some DEFAULT defined. This type of columns cannot have any default value. Please fix it in source (XML and/or upgrade script) to avoid this message to be displayed.');
        $this->resetDebugging();
        $this->assertTrue($dbman->table_exists($tablename));
        $dbman->drop_table($table);
    }

    public function test_add_search_index() {
        $DB = $this->tdb;
        $dbman = $this->tdb->get_manager();

        $tablename = 'test_table_search';
        $table = new xmldb_table($tablename);

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('high', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_field('low', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('course', XMLDB_INDEX_UNIQUE, array('course'));

        $dbman->create_table($table);
        $this->assertTrue($dbman->table_exists($table));

        $highindex = new xmldb_index('high', XMLDB_INDEX_NOTUNIQUE, array('high'), array('full_text_search'));
        $lowindex = new xmldb_index('high', XMLDB_INDEX_NOTUNIQUE, array('low'), array('full_text_search'));
        $dbman->add_index($table, $highindex);
        $dbman->add_index($table, $lowindex);
        $this->assertTrue($dbman->index_exists($table, $highindex));
        $this->assertTrue($dbman->index_exists($table, $lowindex));

        $dbman->drop_table($table);
    }

    public function test_drop_search_index() {
        $DB = $this->tdb;
        $dbman = $this->tdb->get_manager();

        $tablename = 'test_table_search';
        $table = new xmldb_table($tablename);

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('high', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_field('low', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('course', XMLDB_INDEX_UNIQUE, array('course'));
        $table->add_index('high', XMLDB_INDEX_NOTUNIQUE, array('high'), array('full_text_search'));
        $table->add_index('low', XMLDB_INDEX_NOTUNIQUE, array('low'), array('full_text_search'));

        $dbman->create_table($table);

        $this->assertTrue($dbman->table_exists($table));
        $this->assertTrue($dbman->field_exists($table, 'high'));
        $this->assertTrue($dbman->field_exists($table, 'low'));

        $highindex = new xmldb_index('high', XMLDB_INDEX_NOTUNIQUE, array('high'), array('full_text_search'));
        $dbman->drop_index($table, $highindex);
        $this->assertFalse($dbman->index_exists($table, $highindex));

        $dbman->drop_field($table, new xmldb_field('high', XMLDB_TYPE_TEXT));
        $this->assertFalse($dbman->field_exists($table, 'high'));

        $dbman->drop_table($table);
    }

    public function test_rebuild_fts_indexes() {
        $DB = $this->tdb;
        $dbman = $this->tdb->get_manager();
        $prefix = $DB->get_prefix();

        $tablename = 'test_table_search';
        $dbman->install_from_xmldb_file(__DIR__ . '/fixtures/xmldb_search_table.xml');
        $table = new xmldb_table($tablename);
        $fieldhigh = new xmldb_field('high', XMLDB_TYPE_TEXT, null, null, null, null);
        $fieldlow = new xmldb_field('low', XMLDB_TYPE_TEXT, null, null, null, null);
        $indexhigh = new xmldb_index('high', XMLDB_INDEX_NOTUNIQUE, array('high'), array('full_text_search'));
        $indexlow = new xmldb_index('low', XMLDB_INDEX_NOTUNIQUE, array('low'), array('full_text_search'));

        $schema = $this->load_schema(__DIR__ . '/fixtures/xmldb_search_table.xml');

        $result = $dbman->fts_rebuild_indexes($schema);
        $this->assertTrue($dbman->index_exists($table, $indexhigh));
        $this->assertTrue($dbman->index_exists($table, $indexlow));
        $this->assertCount(2, $result);
        $this->assertSame($prefix . $tablename, $result[0]->table);
        $this->assertSame('high', $result[0]->column);
        $this->assertNull($result[0]->error);
        $this->assertNull($result[0]->debuginfo);
        $this->assertTrue($result[0]->success);
        $this->assertSame($prefix . $tablename, $result[1]->table);
        $this->assertSame('low', $result[1]->column);
        $this->assertNull($result[1]->error);
        $this->assertNull($result[1]->debuginfo);
        $this->assertTrue($result[1]->success);

        $this->assertTrue($dbman->index_exists($table, $indexhigh));
        $this->assertTrue($dbman->index_exists($table, $indexlow));
        $dbman->drop_index($table, $indexlow);
        $this->assertTrue($dbman->index_exists($table, $indexhigh));
        $this->assertFalse($dbman->index_exists($table, $indexlow));
        $result = $dbman->fts_rebuild_indexes($schema);
        $this->assertTrue($dbman->index_exists($table, $indexhigh));
        $this->assertTrue($dbman->index_exists($table, $indexlow));
        $this->assertCount(2, $result);
        $this->assertSame($prefix . $tablename, $result[0]->table);
        $this->assertSame('high', $result[0]->column);
        $this->assertNull($result[0]->error);
        $this->assertNull($result[0]->debuginfo);
        $this->assertTrue($result[0]->success);
        $this->assertSame($prefix . $tablename, $result[1]->table);
        $this->assertSame('low', $result[1]->column);
        $this->assertNull($result[1]->error);
        $this->assertNull($result[1]->debuginfo);
        $this->assertTrue($result[1]->success);

        $dbman->drop_table(new xmldb_table($tablename));
    }

    /**
     * Load db schema from text file.
     *
     * @param $file
     * @return xmldb_structure
     */
    protected function load_schema($file) {
        global $CFG;
        $this->tdb->get_manager(); // Load ddl libraries.

        $schema = new xmldb_structure('export');
        $schema->setVersion($CFG->version);
        $xmldb_file = new xmldb_file($file);
        $xmldb_file->loadXMLStructure();
        $structure = $xmldb_file->getStructure();
        $tables = $structure->getTables();
        foreach ($tables as $table) {
            $table->setPrevious(null);
            $table->setNext(null);
            $schema->addTable($table);
        }

        return $schema;
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
}
