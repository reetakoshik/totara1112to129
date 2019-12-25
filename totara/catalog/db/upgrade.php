<?php
/*
 * This file is part of Totara LMS
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

/**
 * Local database upgrade script
 *
 * @param   integer $oldversion Current (pre-upgrade) local db version timestamp
 * @return  boolean $result
 */
function xmldb_totara_catalog_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2018112202) {
        // Define table catalog_search_metadata to be created.
        $table = new xmldb_table('catalog_search_metadata');

        // Adding fields to table catalog_search_metadata.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('value', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('instanceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('pluginname', XMLDB_TYPE_CHAR, '20', null, null, null, null);
        $table->add_field('plugintype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '18', null, null, null, null);

        // Adding keys to table catalog_search_metadata.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table catalog_search_metadata.
        $table->add_index('unique_component_instance', XMLDB_INDEX_UNIQUE, array('instanceid', 'pluginname', 'plugintype'));
        $table->add_index('component_instance', XMLDB_INDEX_NOTUNIQUE, array('instanceid'));
        $table->add_index('plugin_name', XMLDB_INDEX_NOTUNIQUE, array('pluginname'));
        $table->add_index('plugin_type', XMLDB_INDEX_NOTUNIQUE, array('plugintype'));

        // Conditionally launch create table for catalog_search_metadata.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2018112202, 'totara', 'catalog');
    }

    if ($oldversion < 2018112203) {
        // Delete any orphaned certification records.
        $ids = $DB->get_records_sql("select c.id from {catalog} c left join {prog} p on c.objectid = p.id where c.objecttype='certification' and p.id is null");
        $batches = array_chunk($ids, $DB->get_max_in_params(), true);
        foreach ($batches as $batch) {
            $DB->delete_records_list('catalog', 'id', array_keys($batch));
        }

        upgrade_plugin_savepoint(true, 2018112203, 'totara', 'catalog');
    }

    return true;
}
