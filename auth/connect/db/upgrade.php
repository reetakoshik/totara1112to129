<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package auth_connect
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_auth_connect_upgrade($oldversion) {
    global $DB, $CFG;

    $dbman = $DB->get_manager();

    // Totara 10 upgrade line.

    if ($oldversion < 2016110200) {
        // Define table auth_connect_ids to be created.
        $table = new xmldb_table('auth_connect_ids');

        // Adding fields to table auth_connect_ids.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('serverid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('tablename', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
        $table->add_field('remoteid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('localid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table auth_connect_ids.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('serverid', XMLDB_KEY_FOREIGN, array('serverid'), 'auth_connect_servers', array('id'));

        // Adding indexes to table auth_connect_ids.
        $table->add_index('serverid-tablename-remoteid', XMLDB_INDEX_UNIQUE, array('serverid', 'tablename', 'remoteid'));
        $table->add_index('tablename-localid', XMLDB_INDEX_UNIQUE, array('tablename', 'localid'));

        // Conditionally launch create table for auth_connect_ids.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2016110200, 'auth', 'connect');
    }

    if ($oldversion < 2016110201) {
        \core\task\manager::queue_adhoc_task(new \auth_connect\task\handshake_adhoc_task());

        upgrade_plugin_savepoint(true, 2016110201, 'auth', 'connect');
    }

    return true;
}