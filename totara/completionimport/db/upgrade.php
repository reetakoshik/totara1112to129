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
 * @author Rusell England <russell.england@catalyst-eu.net>
 * @package totara
 * @subpackage completionimport
 */

/**
 * Local database upgrade script
 *
 * @param   integer $oldversion Current (pre-upgrade) local db version timestamp
 * @return  boolean $result
 */
function xmldb_totara_completionimport_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // Totara 10 branching line.

    if ($oldversion < 2016122800) {

        // Define index totacompimpocour_evi_ix (unique) to be dropped from totara_compl_import_course.
        $table = new xmldb_table('totara_compl_import_course');
        $index = new xmldb_index('totacompimpocour_evi_ix', XMLDB_INDEX_UNIQUE, array('evidenceid'));

        // Conditionally launch drop index totacompimpocour_evi_ix.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index totacompimpocert_evi_ix (unique) to be dropped from totara_compl_import_cert.
        $table = new xmldb_table('totara_compl_import_cert');
        $index = new xmldb_index('totacompimpocert_evi_ix', XMLDB_INDEX_UNIQUE, array('evidenceid'));

        // Conditionally launch drop index totacompimpocert_evi_ix.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Add non-unique index to each table.

        // Define index totacompimpocour_evi_ix (not unique) to be added to totara_compl_import_course.
        $table = new xmldb_table('totara_compl_import_course');
        $index = new xmldb_index('totacompimpocour_evi_ix', XMLDB_INDEX_NOTUNIQUE, array('evidenceid'));

        // Conditionally launch add index totacompimpocour_evi_ix.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index totacompimpocert_evi_ix (not unique) to be added to totara_compl_import_cert.
        $table = new xmldb_table('totara_compl_import_cert');
        $index = new xmldb_index('totacompimpocert_evi_ix', XMLDB_INDEX_NOTUNIQUE, array('evidenceid'));

        // Conditionally launch add index totacompimpocert_evi_ix.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Completionimport savepoint reached.
        upgrade_plugin_savepoint(true, 2016122800, 'totara', 'completionimport');
    }

    if ($oldversion < 2018052200) {

        // Define field courseid to be added to totara_compl_import_course.
        $table = new xmldb_table('totara_compl_import_course');
        $field = new xmldb_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'id');

        // Conditionally launch add field courseid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Completionimport savepoint reached.
        upgrade_plugin_savepoint(true, 2018052200, 'totara', 'completionimport');
    }

    if ($oldversion < 2018052201) {

        // Define field certificationid to be added to totara_compl_import_cert.
        $table = new xmldb_table('totara_compl_import_cert');
        $field = new xmldb_field('certificationid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'id');

        // Conditionally launch add field certificationid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Completionimport savepoint reached.
        upgrade_plugin_savepoint(true, 2018052201, 'totara', 'completionimport');
    }

    return true;
}
