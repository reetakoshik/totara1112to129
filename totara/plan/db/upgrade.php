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
 * @author Ciaran Irvine <ciaran.irvine@totaralms.com>
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package totara
 * @subpackage plan
 */

require_once($CFG->dirroot.'/totara/plan/db/upgradelib.php');

/**
 * Local database upgrade script
 *
 * @param   integer $oldversion Current (pre-upgrade) local db version timestamp
 * @return  boolean $result
 */
function xmldb_totara_plan_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // Totara 10 branching line.

    // TL-14290 duedate in dp_plan_program_assign must not be -1, instead use 0.
    if ($oldversion < 2017050500) {
        totara_plan_upgrade_fix_invalid_program_duedates();

        upgrade_plugin_savepoint(true, 2017050500, 'totara', 'plan');
    }

    if ($oldversion < 2017051800) {
        // Rename columns types to type 'plan'.
        reportbuilder_rename_data('columns', 'dp_course', 'course_completion', 'statusandapproval', 'plan', 'statusandapproval');
        reportbuilder_rename_data('columns', 'dp_course', 'course', 'status', 'plan', 'coursestatus');

        upgrade_plugin_savepoint(true, 2017051800, 'totara', 'plan');
    }

    if ($oldversion < 2017070600) {

        // Add a timecreated field.
        $table = new xmldb_table('dp_plan_objective');
        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add a timemodified field.
        $table = new xmldb_table('dp_plan_objective');
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2017070600, 'totara', 'plan');
    }

    if ($oldversion < 2017112000) {
        // Update the indexes on the dp_plan_evidence_info_data table.
        $table = new xmldb_table('dp_plan_evidence_info_data');

        // Define new index to be added.
        $index = new xmldb_index('dpplanevidinfodata_fieevi_uix', XMLDB_INDEX_UNIQUE, array('fieldid', 'evidenceid'));
        // Conditionally launch to add index.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2017112000, 'totara', 'plan');
    }

    if ($oldversion < 2018052300) {
        // Clean up orphaned files from any previously deleted evidence.
        totara_plan_upgrade_clean_deleted_evidence_files();

        upgrade_plugin_savepoint(true, 2018052300, 'totara', 'plan');
    }

    if ($oldversion < 2018112201) {
        // Rename columns types to type 'plan'.
        reportbuilder_rename_data('columns', 'dp_course', 'course_completion', 'status', 'plan', 'courseprogress');
        reportbuilder_rename_data('filters', 'dp_course', 'course_completion', 'status', 'plan', 'courseprogress');

        upgrade_plugin_savepoint(true, 2018112201, 'totara', 'plan');
    }

    return true;
}
