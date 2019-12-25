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
 * @author Jonathan Newman <jonathan.newman@catalyst.net.nz>
 * @author Ciaran Irvine <ciaran.irvine@totaralms.com>
 * @package totara
 * @subpackage totara_core
 */

require_once($CFG->dirroot.'/totara/hierarchy/db/upgradelib.php');

/**
 * Database upgrade script
 *
 * @param   integer $oldversion Current (pre-upgrade) local db version timestamp
 */
function xmldb_totara_hierarchy_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // Totara 10 branching line.

    if ($oldversion < 2016120100) {
        // There was a bug whereby sort orders could end up with duplicates, and gaps.
        // Although this will fix itself after the user gets the first error, we don't want them to get
        // any errors so we will fix the sortorders of all hierarchy custom fields now, during upgrade.
        // The function isn't particularly well performing, however we don't expect to encounter sites
        // with thousands of custom fields per type, as such we will raise memory as a caution as proceed.
        raise_memory_limit(MEMORY_HUGE);

        totara_hierarchy_upgrade_fix_customfield_sortorder('comp_type'); // Competencies.
        totara_hierarchy_upgrade_fix_customfield_sortorder('goal_type'); // Company goals.
        totara_hierarchy_upgrade_fix_customfield_sortorder('goal_user'); // User goals.
        totara_hierarchy_upgrade_fix_customfield_sortorder('org_type'); // Organisations.
        totara_hierarchy_upgrade_fix_customfield_sortorder('pos_type'); // Positions.

        upgrade_plugin_savepoint(true, 2016120100, 'totara', 'hierarchy');
    }

    if ($oldversion < 2017072700) {
        // The timeproficient field will be a manually set completion date/time for competencies.
        $field = new xmldb_field('timeproficient', XMLDB_TYPE_INTEGER, '18', null, null, null, null, 'proficiency');

        // Add timeproficient to comp_record table.
        $table = new xmldb_table('comp_record');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add timeproficient to comp_record_history table.
        $table = new xmldb_table('comp_record_history');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2017072700, 'totara', 'hierarchy');
    }

    if ($oldversion < 2017112700) {
        // Update the indexes on the comp_type_info_data table.
        $table = new xmldb_table('comp_type_info_data');

        // Define new index to be added.
        $index = new xmldb_index('comptypeinfodata_fiecom_uix', XMLDB_INDEX_UNIQUE, array('fieldid', 'competencyid'));
        // Conditionally launch to add index.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2017112700, 'totara', 'hierarchy');
    }

    if ($oldversion < 2017112701) {
        // Update the indexes on the goal_type_info_data table.
        $table = new xmldb_table('goal_type_info_data');

        // Define new index to be added.
        $index = new xmldb_index('goaltypeinfodata_fiegoa_uix', XMLDB_INDEX_UNIQUE, array('fieldid', 'goalid'));
        // Conditionally launch to add index.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2017112701, 'totara', 'hierarchy');
    }

    if ($oldversion < 2017112702) {
        // Update the indexes on the goal_user_info_data table.
        $table = new xmldb_table('goal_user_info_data');

        // Define new index to be added.
        $index = new xmldb_index('goaluserinfodata_fiegoa_uix', XMLDB_INDEX_UNIQUE, array('fieldid', 'goal_userid'));
        // Conditionally launch to add index.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2017112702, 'totara', 'hierarchy');
    }

    if ($oldversion < 2017112703) {
        // Update the indexes on the org_type_info_data table.
        $table = new xmldb_table('org_type_info_data');

        // Define new index to be added.
        $index = new xmldb_index('orgtypeinfodata_fieorg_uix', XMLDB_INDEX_UNIQUE, array('fieldid', 'organisationid'));
        // Conditionally launch to add index.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2017112703, 'totara', 'hierarchy');
    }

    if ($oldversion < 2017112704) {
        // Update the indexes on the org_type_info_data table.
        $table = new xmldb_table('org_type_info_data');

        // Define index for fieldid to be dropped. We are doing this because there two indexes have been incorrectly
        // added to the field. We will replace it with a new index later.
        $index = new xmldb_index('orgtypeinfodata_fie2_ix', XMLDB_INDEX_NOTUNIQUE, array('fieldid'));
        // Conditionally launch to drop the index.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define new index to be added.
        $index = new xmldb_index('orgtypeinfodata_fie_ix', XMLDB_INDEX_NOTUNIQUE, array('fieldid'));
        // Conditionally launch to add index.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2017112704, 'totara', 'hierarchy');
    }

    if ($oldversion < 2017112705) {
        // Update the indexes on the pos_type_info_data table.
        $table = new xmldb_table('pos_type_info_data');

        // Define new index to be added.
        $index = new xmldb_index('postypeinfodata_fiepos_uix', XMLDB_INDEX_UNIQUE, array('fieldid', 'positionid'));
        // Conditionally launch to add index.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2017112705, 'totara', 'hierarchy');
    }

    if ($oldversion < 2018031600) {
        // change the type of defaultid in competency scales from a smallint to a bigint since it contains an id record.
        $field = new xmldb_field('defaultid', XMLDB_TYPE_INTEGER, 10, null, null, null, null, 'usermodified');
        $table = new xmldb_table('comp_scale');

        // First remove any index on the field.
        $index = new xmldb_index('compscal_def_ix', XMLDB_INDEX_NOTUNIQUE, ['defaultid']);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Change the field type to a larger int.
        $dbman->change_field_precision($table, $field);

        // Recreate or add the index.
        $dbman->add_index($table, $index);

        upgrade_plugin_savepoint(true, 2018031600, 'totara', 'hierarchy');
    }

    if ($oldversion < 2018031700) {
        // change the type of defaultid in goal scales from a smallint to a bigint since it contains an id record.
        $field = new xmldb_field('defaultid', XMLDB_TYPE_INTEGER, 10, null, null, null, null, 'usermodified');
        $table = new xmldb_table('goal_scale');

        // First remove any index on the field.
        $index = new xmldb_index('goalscal_def_ix', XMLDB_INDEX_NOTUNIQUE, ['defaultid']);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Change the field type to a larger int.
        $dbman->change_field_precision($table, $field);

        // Recreate or add the index.
        $dbman->add_index($table, $index);

        upgrade_plugin_savepoint(true, 2018031700, 'totara', 'hierarchy');
    }

    if ($oldversion < 2018090300) {

        // Define field totarasync to be added to comp.
        $table = new xmldb_table('comp');
        $field = new xmldb_field('totarasync', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'sortthread');

        // Conditionally launch add field totarasync.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Hierarchy savepoint reached.
        upgrade_plugin_savepoint(true, 2018090300, 'totara', 'hierarchy');
    }

    if ($oldversion < 2018112201) {
        totara_hierarchy_upgrade_user_assignment_extrainfo();

        // Hierarchy savepoint reached.
        upgrade_plugin_savepoint(true, 2018112201, 'totara', 'hierarchy');
    }

    return true;
}
