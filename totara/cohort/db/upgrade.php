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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package totara
 * @subpackage cohort
 */

require_once($CFG->dirroot . '/totara/cohort/db/upgradelib.php');

/**
 * DB upgrades for Totara dynamic cohorts
 */
function xmldb_totara_cohort_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // Totara 10 branching line.

    if ($oldversion < 2017030300) {
        totara_cohort_migrate_rules('learning', 'programcompletionduration', 'learning', 'programcompletiondurationassigned');

        // Main savepoint reached.
        upgrade_plugin_savepoint(true, 2017030300, 'totara', 'cohort');
    }

    // Set default scheduled tasks correctly.
    if ($oldversion < 2017042800) {

        $task = '\totara_cohort\task\cleanup_task';
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
        upgrade_plugin_savepoint(true, 2017042800, 'totara', 'cohort');
    }

    return true;
}
