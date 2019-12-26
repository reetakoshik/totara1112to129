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
 * @package totara_dashboard
 */

/**
 * Local database upgrade script
 *
 * @param   integer $oldversion Current (pre-upgrade) local db version timestamp
 * @return  boolean $result
 */
function xmldb_totara_dashboard_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // Totara 10 branching line.

    if ($oldversion < 2017010400) {

        $sqlike = $DB->sql_like('pagetypepattern', ':pagetypepattern');
        $param = array('pagetypepattern' => 'my-totara-dashboard-%');

        $sql = "SELECT DISTINCT pagetypepattern
                       FROM {block_instances}
                      WHERE $sqlike";

        $blockinsts = $DB->get_records_sql($sql, $param);

        foreach ($blockinsts as $blockinst) {

            list($my, $totara, $dashboard, $id) = explode('-', $blockinst->pagetypepattern);

            if (!$DB->record_exists('totara_dashboard', array('id' => $id))) {
                if ($blocks = $DB->get_records('block_instances', array('pagetypepattern' => 'my-totara-dashboard-' . $id))) {
                    foreach ($blocks as $instance) {

                        if ($block = block_instance($instance->blockname, $instance)) {
                            $block->instance_delete();
                        }

                        context_helper::delete_instance(CONTEXT_BLOCK, $instance->id);

                        $DB->delete_records('block_positions', array('blockinstanceid' => $instance->id));
                        $DB->delete_records('block_instances', array('id' => $instance->id));
                        $DB->delete_records_list('user_preferences', 'name', array('block'.$instance->id.'hidden','docked_block_instance_'.$instance->id));
                    }
                }
            }
        }

        upgrade_plugin_savepoint(true, 2017010400, 'totara', 'dashboard');
    }

    if ($oldversion < 2017111400) {

        // Increase max length of dashboard name field to 1333 characters.
        $table = new xmldb_table('totara_dashboard');
        $field = new xmldb_field('name', XMLDB_TYPE_CHAR, '1333', null, XMLDB_NOTNULL, null);
        $index = new xmldb_index('name', XMLDB_INDEX_NOTUNIQUE, ['name']);

        // Drop index if exists.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }
        // Adjust name field size.
        $dbman->change_field_precision($table, $field);

        upgrade_plugin_savepoint(true, 2017111400, 'totara', 'dashboard');
    }

    if ($oldversion < 2018050800) {
        // All dashboard blocks have been added to the wrong pagetype.
        // Previously they were my-totara-dashboard-x, they are now totara-dashboard-x
        // First up, take care of all basic dashboard blocks per dashboard. This will perform the best.

        $rs = $DB->get_recordset('totara_dashboard', [], '', 'id');
        foreach ($rs as $dashboard) {
            // Update the block instances and positions for each dashboard.
            $oldkey = 'my-totara-dashboard-' . $dashboard->id;
            $newkey = 'totara-dashboard-' . $dashboard->id;
            $DB->set_field('block_instances', 'pagetypepattern', $newkey, ['pagetypepattern' => $oldkey]);
            $DB->set_field('block_positions', 'pagetype', $newkey, ['pagetype' => $oldkey]);
        }
        $rs->close();

        upgrade_plugin_savepoint(true, 2018050800, 'totara', 'dashboard');
    }

    if ($oldversion < 2018050801) {
        // All dashboard blocks have been added to the wrong pagetype.
        // Previously they were my-totara-dashboard-x, they are now totara-dashboard-x
        // Now deal with situations where the user has managed to move the block within the space.

        // There should be none of these, but still, be very aware of it!
        $rs = $DB->get_recordset_select(
            'block_instances',
            $DB->sql_like('pagetypepattern', ':key'),
            ['key' => 'my-totara-dashboard-%'],
            'id',
            'id,pagetypepattern'
        );
        foreach ($rs as $row) {
            $oldkey = $row->pagetypepattern;
            $newkey = substr($row->pagetypepattern, 3);
            $DB->set_field('block_instances', 'pagetypepattern', $newkey, ['pagetypepattern' => $oldkey]);
        }
        $rs->close();

        $rs = $DB->get_recordset_select(
            'block_positions',
            $DB->sql_like('pagetype', ':key'),
            ['key' => 'my-totara-dashboard-%'],
            'id',
            'id,pagetype'
        );
        foreach ($rs as $row) {
            $oldkey = $row->pagetypepattern;
            $newkey = substr($row->pagetypepattern, 3);
            $DB->set_field('block_positions', 'pagetype', $newkey, ['pagetype' => $oldkey]);
        }
        $rs->close();

        upgrade_plugin_savepoint(true, 2018050801, 'totara', 'dashboard');
    }

    return true;
}
