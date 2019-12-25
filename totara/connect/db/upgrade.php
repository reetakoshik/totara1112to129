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
 * @package totara_connect
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Totara Connect server plugin upgrade.
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool always true
 */
function xmldb_totara_connect_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // Totara 10 branching line.


    // Totara 10 ugprade line.

    if ($oldversion < 2016110200) {

        // Define field syncjobs to be added to totara_connect_clients.
        $table = new xmldb_table('totara_connect_clients');
        $field = new xmldb_field('syncjobs', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'addnewcourses');

        // Conditionally launch add field syncjobs.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2016110200, 'totara', 'connect');
    }

    if ($oldversion < 2016110201) {

        // Define table totara_connect_pos_frameworks to be created.
        $table = new xmldb_table('totara_connect_client_pos_frameworks');

        // Adding fields to table totara_connect_pos_frameworks.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('clientid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('fid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table totara_connect_pos_frameworks.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('clientid', XMLDB_KEY_FOREIGN, array('clientid'), 'totara_connect_clients', array('id'));
        $table->add_key('fid', XMLDB_KEY_FOREIGN, array('fid'), 'pos_framework', array('id'));

        // Adding indexes to table totara_connect_pos_frameworks.
        $table->add_index('clientid-fid', XMLDB_INDEX_UNIQUE, array('clientid', 'fid'));

        // Conditionally launch create table for totara_connect_pos_frameworks.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2016110201, 'totara', 'connect');
    }

    if ($oldversion < 2016110202) {

        // Define table totara_connect_org_frameworks to be created.
        $table = new xmldb_table('totara_connect_client_org_frameworks');

        // Adding fields to table totara_connect_org_frameworks.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('clientid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('fid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table totara_connect_org_frameworks.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('clientid', XMLDB_KEY_FOREIGN, array('clientid'), 'totara_connect_clients', array('id'));
        $table->add_key('fid', XMLDB_KEY_FOREIGN, array('fid'), 'org_framework', array('id'));

        // Adding indexes to table totara_connect_org_frameworks.
        $table->add_index('clientid-fid', XMLDB_INDEX_UNIQUE, array('clientid', 'fid'));

        // Conditionally launch create table for totara_connect_org_frameworks.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2016110202, 'totara', 'connect');
    }

    if ($oldversion < 2016110203) {

        // Define field syncprofilefields to be added to totara_connect_clients.
        $table = new xmldb_table('totara_connect_clients');
        $field = new xmldb_field('syncprofilefields', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'cohortid');

        // Conditionally launch add field syncjobs.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2016110203, 'totara', 'connect');
    }

    if ($oldversion < 2017021700) {

        // Define field allowpluginsepservices to be added to totara_connect_clients.
        $table = new xmldb_table('totara_connect_clients');
        $field = new xmldb_field('allowpluginsepservices', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'syncjobs');

        // Conditionally launch add field allowpluginsepservices.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2017021700, 'totara', 'connect');
    }

    return true;
}
