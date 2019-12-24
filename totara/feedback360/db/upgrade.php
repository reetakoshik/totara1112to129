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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara
 * @subpackage feedback360
 */

require_once($CFG->dirroot.'/totara/feedback360/db/upgradelib.php');

/**
 * Local database upgrade script
 *
 * @param   integer $oldversion Current (pre-upgrade) local db version timestamp
 * @return  boolean $result
 */
function xmldb_totara_feedback360_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // Totara 10 branching line.

    if ($oldversion < 2016123000) {

        // Define field requestertoken to be added to feedback360_resp_assignment.
        $table = new xmldb_table('feedback360_resp_assignment');
        $field = new xmldb_field('requestertoken', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null, 'feedback360emailassignmentid');

        // Conditionally launch add field requestertoken.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // We now need to add tokens to each record in feedback360_resp_assignment.
        $time = time();
        $respassignments = $DB->get_records('feedback360_resp_assignment');
        foreach ($respassignments as $respassignment) {
            $stringtohash = 'requester' . $time . random_string() . get_site_identifier();
            $hash = sha1($stringtohash);

            $respassignment->requestertoken = $hash;
            $DB->update_record('feedback360_resp_assignment', $respassignment);
        }

        // Feedback360 savepoint reached.
        upgrade_plugin_savepoint(true, 2016123000, 'totara', 'feedback360');
    }

    // Set default scheduled tasks correctly.
    if ($oldversion < 2017042800) {

        $task = '\totara_feedback360\task\cleanup_task';
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

        // Main savepoint reached.
        upgrade_plugin_savepoint(true, 2017042800, 'totara', 'feedback360');
    }

    if ($oldversion < 2017081000) {
        // Define field selfevaluation to be added to feedback360.
        $table = new xmldb_table('feedback360');
        $field = new xmldb_field('selfevaluation', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1');

        // Conditionally launch add field selfevaluation.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

            // We now need to set selfevaluation to 0 (SELF_EVALUATION_DISABLED) to maintain existing behaviour.
            $DB->execute("UPDATE {feedback360} SET selfevaluation = 0");
        }

        // Main savepoint reached.
        upgrade_plugin_savepoint(true, 2017081000, 'totara', 'feedback360');
    }

    // TL-16443 Make all multichoice questions use int for param1.
    if ($oldversion < 2017110700) {

        totara_feedback360_upgrade_fix_inconsistent_multichoice_param1();

        // Main savepoint reached.
        upgrade_plugin_savepoint(true, 2017110700, 'totara', 'feedback360');
    }

    return true;
}
