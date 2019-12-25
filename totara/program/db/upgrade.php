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
 * @package totara
 * @subpackage program
 */

/**
 * Local db upgrades for Totara Core
 */

require_once($CFG->dirroot.'/totara/core/db/utils.php');
require_once($CFG->dirroot.'/totara/program/db/upgradelib.php');


/**
 * Local database upgrade script
 *
 * @param   integer $oldversion Current (pre-upgrade) local db version timestamp
 * @return  boolean $result
 */
function xmldb_totara_program_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // Totara 10 branching line.

    if ($oldversion < 2016110900) {
        $table = new xmldb_table('prog_message');
        $field = new xmldb_field('managersubject', XMLDB_TYPE_CHAR, '255', null, false, null, "");
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Main savepoint reached.
        upgrade_plugin_savepoint(true, 2016110900, 'totara', 'program');
    }

    // Set default scheduled tasks correctly.
    if ($oldversion < 2017042800) {

        // Task \totara_program\task\clean_enrolment_plugins_task.
        $task = '\totara_program\task\clean_enrolment_plugins_task';
        // If schecdule is * 2 * * * change to 0 2 * * *
        $incorrectschedule = array(
            'minute' => '*',
            'hour' => '2',
            'day' => '*',
            'month' => '*',
            'dayofweek' => '*'
        );
        $newschedule = $incorrectschedule;
        $newschedule['minute'] = '0';

        totara_upgrade_default_schedule($task, $incorrectschedule, $newschedule);

        // Task \totara_program\task\completions_task.
        $task = '\totara_program\task\completions_task';
        // If schecdule is * 2 * * * change to 0 2 * * *
        $incorrectschedule = array(
            'minute' => '*',
            'hour' => '2',
            'day' => '*',
            'month' => '*',
            'dayofweek' => '*'
        );
        $newschedule = $incorrectschedule;
        $newschedule['minute'] = '0';

        totara_upgrade_default_schedule($task, $incorrectschedule, $newschedule);

        // Task \totara_program\task\copy_recurring_courses_task.
        $task = '\totara_program\task\copy_recurring_courses_task';
        // If schecdule is * 2 * * * change to 0 2 * * *
        $incorrectschedule = array(
            'minute' => '*',
            'hour' => '2',
            'day' => '*',
            'month' => '*',
            'dayofweek' => '*'
        );
        $newschedule = $incorrectschedule;
        $newschedule['minute'] = '0';

        totara_upgrade_default_schedule($task, $incorrectschedule, $newschedule);

        // Task \totara_program\task\recurrence_history_task.
        $task = '\totara_program\task\recurrence_history_task';
        // If schecdule is * 2 * * * change to 0 2 * * *
        $incorrectschedule = array(
            'minute' => '*',
            'hour' => '2',
            'day' => '*',
            'month' => '*',
            'dayofweek' => '*'
        );
        $newschedule = $incorrectschedule;
        $newschedule['minute'] = '0';

        totara_upgrade_default_schedule($task, $incorrectschedule, $newschedule);

        // Task \totara_program\task\recurrence_task.
        $task = '\totara_program\task\recurrence_task';
        // If schecdule is * 1 * * * change to 0 1 * * *
        $incorrectschedule = array(
            'minute' => '*',
            'hour' => '1',
            'day' => '*',
            'month' => '*',
            'dayofweek' => '*'
        );
        $newschedule = $incorrectschedule;
        $newschedule['minute'] = '0';

        totara_upgrade_default_schedule($task, $incorrectschedule, $newschedule);

        // Task \totara_program\task\switch_recurring_courses_task.
        $task = '\totara_program\task\switch_recurring_courses_task';
        // If schecdule is * 2 * * * change to 0 2 * * *
        $incorrectschedule = array(
            'minute' => '*',
            'hour' => '2',
            'day' => '*',
            'month' => '*',
            'dayofweek' => '*'
        );
        $newschedule = $incorrectschedule;
        $newschedule['minute'] = '0';

        totara_upgrade_default_schedule($task, $incorrectschedule, $newschedule);

        // Task \totara_program\task\user_assignments_task.
        $task = '\totara_program\task\user_assignments_task';
        // If schecdule is * 2 * * * change to 0 2 * * *
        $incorrectschedule = array(
            'minute' => '*',
            'hour' => '2',
            'day' => '*',
            'month' => '*',
            'dayofweek' => '*'
        );
        $newschedule = $incorrectschedule;
        $newschedule['minute'] = '0';

        totara_upgrade_default_schedule($task, $incorrectschedule, $newschedule);

        // Main savepoint reached.
        upgrade_plugin_savepoint(true, 2017042800, 'totara', 'program');
    }

    if ($oldversion < 2017112000) {
        // Update the indexes on the prog_info_data table.
        $table = new xmldb_table('prog_info_data');

        // Define new index to be added.
        $index = new xmldb_index('proginfodata_fiepro_uix', XMLDB_INDEX_UNIQUE, array('fieldid', 'programid'));
        // Conditionally launch to add index.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2017112000, 'totara', 'program');
    }

    // Does part of the fix from TL-6372 again as on certain execution paths it could be missed.
    if ($oldversion < 2018010800) {
        // Get IDs of empty coursesets so we can delete them.
        $emptycoursesets = $DB->get_fieldset_sql('SELECT cs.id 
                                                      FROM {prog_courseset} cs 
                                                      LEFT JOIN {prog_courseset_course} csc 
                                                        ON cs.id = csc.coursesetid 
                                                      WHERE csc.coursesetid IS NULL GROUP BY cs.id');

        if (!empty($emptycoursesets)) {
            list($insql, $inparams) = $DB->get_in_or_equal($emptycoursesets);
            $DB->delete_records_select('prog_courseset', "id {$insql}", $inparams);
        }

        // Main savepoint reached.
        upgrade_plugin_savepoint(true, 2018010800, 'totara', 'program');
    }

    if ($oldversion < 2018031501) {

        // We need to fix a regression from TL-15995. See TL-16826 for details.
        // The wrong plugin name was used, 'totara_totara_program'. We need to remove it.
        set_config('version', null, 'totara_totara_program');

        upgrade_plugin_savepoint(true, 2018031501, 'totara', 'program');
    }

    if ($oldversion < 2018112202) {
        // Enable legacy program assignments by default on upgrade
        set_config('enablelegacyprogramassignments', 1);

        upgrade_plugin_savepoint(true, 2018112202, 'totara', 'program');
    }

    if ($oldversion < 2018112203) {
        // Remove records from prog_completion, prog_completion_history
        // and prog_compeltion_log if the program has been deleted
        $completionsql = 'DELETE FROM {prog_completion}
                    WHERE programid NOT IN
                    (SELECT id FROM {prog})';

        $completionhistsql = 'DELETE from {prog_completion_history}
                    WHERE programid NOT IN
                    (SELECT id FROM {prog})';

        $completionlogsql = 'DELETE FROM {prog_completion_log}
                    WHERE programid NOT IN
                    (SELECT id FROM {prog})';

        $DB->execute($completionsql);
        $DB->execute($completionhistsql);
        $DB->execute($completionlogsql);

        upgrade_plugin_savepoint(true, 2018112203, 'totara', 'program');
    }

    if ($oldversion < 2018112204) {
        totara_program_remove_orphaned_courseset_completions();

        upgrade_plugin_savepoint(true, 2018112204, 'totara', 'program');
    }

    return true;
}
