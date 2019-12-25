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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara
 * @subpackage totara_appraisal
 */

require_once($CFG->dirroot.'/totara/appraisal/db/upgradelib.php');

/**
 * Local database upgrade script
 *
 * @param   integer $oldversion Current (pre-upgrade) local db version timestamp
 * @return  boolean $result
 */
function xmldb_totara_appraisal_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // Totara 10 branching line.

    // Set default scheduled tasks correctly.
    if ($oldversion < 2017042800) {

        $task = '\totara_appraisal\task\cleanup_task';
        // If schecdule is * 3 * * * change to 0 3 * * *
        $incorrectschedule = array(
            'minute' => '*',
            'hour' => '3',
            'day' => '*',
            'month' => '*',
            'dayofweek' => '*'
        );
        $newschedule = $incorrectschedule;
        $newschedule['minute'] = '0';

        totara_upgrade_default_schedule($task, $incorrectschedule, $newschedule);

        // Appraisal savepoint reached.
        upgrade_plugin_savepoint(true, 2017042800, 'totara', 'appraisal');
    }

    // TL-15900 Update team leaders in dynamic appraisals.
    if ($oldversion < 2017083000) {

        totara_appraisal_upgrade_update_team_leaders();

        // Appraisal savepoint reached.
        upgrade_plugin_savepoint(true, 2017083000, 'totara', 'appraisal');
    }

    // TL-16443 Make all multichoice questions use int for param1.
    if ($oldversion < 2017110700) {

        totara_appraisal_upgrade_fix_inconsistent_multichoice_param1();

        // Main savepoint reached.
        upgrade_plugin_savepoint(true, 2017110700, 'totara', 'appraisal');
    }

    // TL-17131 Appraisal snapshots not deleted when user is deleted.
    if ($oldversion < 2018022701) {

        totara_appraisal_remove_orphaned_snapshots();

        // Main savepoint reached.
        upgrade_plugin_savepoint(true, 2018022701, 'totara', 'appraisal');
    }

    if ($oldversion < 2018022704) {

        // Define field usercompleted to be added to appraisal_stage_data.
        $table = new xmldb_table('appraisal_stage_data');
        $field = new xmldb_field('usercompleted', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timecompleted');

        // Conditionally launch add field usercompleted.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field realusercompleted to be added to appraisal_stage_data.
        $table = new xmldb_table('appraisal_stage_data');
        $field = new xmldb_field('realusercompleted', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'usercompleted');

        // Conditionally launch add field realusercompleted.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Appraisal savepoint reached.
        upgrade_plugin_savepoint(true, 2018022704, 'totara', 'appraisal');
    }

    return true;
}
