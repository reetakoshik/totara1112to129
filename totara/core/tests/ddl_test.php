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
}
