<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

class totara_core_no_oracle_support_testcase extends advanced_testcase {
    public function test_table_size() {
        global $DB;

        $this->resetAfterTest();
        $this->preventResetByRollback();

        $dbman = $DB->get_manager();

        $longname = 'test_long_table_name_forty_characters_xx';
        $this->assertSame(40, strlen($longname));

        $shortname = 'test_short';

        $table = new xmldb_table($longname);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('name', XMLDB_TYPE_CHAR, '30', null, null, null, 'Moodle');
        $table->add_field('secondname', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, null);
        $table->add_field('thirdname', XMLDB_TYPE_CHAR, '30', null, null, null, '');
        $table->add_field('intro', XMLDB_TYPE_TEXT, 'medium', null, XMLDB_NOTNULL, null, null);
        $table->add_field('avatar', XMLDB_TYPE_BINARY, 'medium', null, null, null, null);
        $table->add_field('grade', XMLDB_TYPE_NUMBER, '20,10', null, null, null);
        $table->add_field('gradefloat', XMLDB_TYPE_FLOAT, '20,0', null, null, null, null);
        $table->add_field('percentfloat', XMLDB_TYPE_FLOAT, '5,2', null, null, null, 99.9);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('course', XMLDB_KEY_FOREIGN_UNIQUE, array('course'), 'test_table0', array('course'));
        $table->setComment("This is a test'n drop table. You can drop it safely");

        $this->assertFalse($dbman->table_exists($table));
        $dbman->create_table($table);
        $this->assertTrue($dbman->table_exists($table));
        $dbman->rename_table($table, $shortname);
        $this->assertFalse($dbman->table_exists($table));
        $this->assertTrue($dbman->table_exists($shortname));
        $dbman->drop_table(new xmldb_table($shortname));
        $this->assertFalse($dbman->table_exists($shortname));

        $table = new xmldb_table($longname . 'a');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('name', XMLDB_TYPE_CHAR, '30', null, null, null, 'Moodle');
        $table->add_field('secondname', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, null);
        $table->add_field('thirdname', XMLDB_TYPE_CHAR, '30', null, null, null, '');
        $table->add_field('intro', XMLDB_TYPE_TEXT, 'medium', null, XMLDB_NOTNULL, null, null);
        $table->add_field('avatar', XMLDB_TYPE_BINARY, 'medium', null, null, null, null);
        $table->add_field('grade', XMLDB_TYPE_NUMBER, '20,10', null, null, null);
        $table->add_field('gradefloat', XMLDB_TYPE_FLOAT, '20,0', null, null, null, null);
        $table->add_field('percentfloat', XMLDB_TYPE_FLOAT, '5,2', null, null, null, 99.9);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('course', XMLDB_KEY_FOREIGN_UNIQUE, array('course'), 'test_table0', array('course'));
        $table->setComment("This is a test'n drop table. You can drop it safely");

        $this->assertFalse($dbman->table_exists($table));
        try {
            $dbman->create_table($table);
            $this->fail('coding_exception expected for tables names over 40 chars');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $max = xmldb_table::NAME_MAX_LENGTH;
            $this->assertEquals("Coding error detected, it must be fixed by a programmer: Invalid table name {test_long_table_name_forty_characters_xxa}: name is too long. Limit is {$max} chars.", $e->getMessage());
        }

    }
}
