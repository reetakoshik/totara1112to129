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

/**
 * Local database upgrade script
 *
 * @param   integer $oldversion Current (pre-upgrade) local db version timestamp
 * @return  boolean $result
 */
function xmldb_totara_core_upgrade($oldversion) {
    global $CFG, $DB;
    require_once(__DIR__ . '/upgradelib.php');

    $dbman = $DB->get_manager();

    // Totara 10 branching line.

    if ($oldversion < 2017030800) {
        require_once($CFG->dirroot . '/totara/program/db/upgradelib.php');
        require_once($CFG->dirroot . '/totara/certification/db/upgradelib.php');

        // Create the timecreated column.
        $table = new xmldb_table('prog_completion');
        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, 10, false, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

            // Now clone the timestarted data into the timecreated field.
            $DB->execute("UPDATE {prog_completion} SET timecreated = timestarted");

            // Make sure the non zero upgrade has run prior to fix time started.
            totara_certification_upgrade_non_zero_prog_completions();

            // Attempt to recalculate the timestarted field.
            totara_program_fix_timestarted();
        }

        upgrade_plugin_savepoint(true, 2017030800, 'totara', 'core');
    }

    if ($oldversion < 2017040900) {
        // Remove private token column because all tokens were always supposed to be private.
        $table = new xmldb_table('external_tokens');
        $field = new xmldb_field('privatetoken', XMLDB_TYPE_CHAR, '64', null, null, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2017040900, 'totara', 'core');
    }

    if ($oldversion < 2017041904) {
        totara_core_upgrade_delete_moodle_plugins();
        upgrade_plugin_savepoint(true, 2017041904, 'totara', 'core');
    }

    // Set default scheduled tasks correctly.
    if ($oldversion < 2017042801) {

        $task = '\totara_core\task\tool_totara_sync_task';
        // If schecdule is * 0 * * * change to 0 0 * * *
        $incorrectschedule = array(
            'minute' => '*',
            'hour' => '0',
            'day' => '*',
            'month' => '*',
            'dayofweek' => '*'
        );
        $newschedule = $incorrectschedule;
        $newschedule['minute'] = '0';

        totara_upgrade_default_schedule($task, $incorrectschedule, $newschedule);

        // Main savepoint reached.
        upgrade_plugin_savepoint(true, 2017042801, 'totara', 'core');
    }

    // We removed the gauth plugin in Totara 10, 9.10, 2.9.22, 2.7.30, and 2.6.47.
    // The Google OpenID 2.0 API was deprecated May 2014, and shut down April 2015.
    // https://developers.google.com/identity/sign-in/auth-migration
    if ($oldversion < 2017072000) {

        if (file_exists($CFG->dirroot . '/auth/gauth/version.php')) {
            // This should not happen, this is not a standard distribution!
            // Nothing to do here.
        } else if (!get_config('auth_gauth', 'version')) {
            // Not installed. Weird but fine.
            // Nothing to do here.
        } else if ($DB->record_exists('user', array('auth' => 'gauth', 'deleted' => 0))) {
            // We need to remove the gauth plugin from the list of enabled plugins, if it has been enabled.
            $enabledauth = $DB->get_record('config', ['name' => 'auth'], '*', IGNORE_MISSING);
            if (!empty($enabledauth) && strpos($enabledauth->value, 'gauth')) {
                $auths = explode(',', $enabledauth->value);
                $auths = array_unique($auths);
                if (($key = array_search('gauth', $auths)) !== false) {
                    unset($auths[$key]);
                    set_config('auth', implode(',', $auths));
                }
            }
            // Note that if any users were created via gauth they won't have successfully logged in in the past 2 years.
            // Consequently we are going to leave their auth set to gauth.
            // They won't be able to log in, the admin will need to change their auth to manual.

            // Additionally all settings associated with the gauth plugin have been left in place just
            // in case anyone has fixed this plugin themselves, in which case they can put the files back
            // and simply re-enable the plugin after uprgade and everything will continue to work just fine.
        } else {
            // It is installed, and it is not used.
            // We can run the full uninstall_plugin for this, yay!
            uninstall_plugin('auth', 'gauth');
        }

        upgrade_plugin_savepoint(true, 2017072000, 'totara', 'core');
    }

    if ($oldversion < 2017082302) {

        // Define field totarasync to be added to job_assignment.
        $table = new xmldb_table('job_assignment');
        $field = new xmldb_field('totarasync', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'sortorder');

        // Conditionally launch add field totarasync.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

            // If we've just added this field, we'll be setting it to 1 for all job assignments
            // belonging to users who have the totarasync field on their user records set to 1.
            $ids = $DB->get_fieldset_select('user', 'id', 'totarasync = 1');
            $idsets = array_chunk($ids, $DB->get_max_in_params());
            foreach ($idsets as $idset) {
                list($insql, $inparams) = $DB->get_in_or_equal($idset);
                $DB->set_field_select('job_assignment', 'totarasync', 1, 'userid '. $insql, $inparams);
            }
        }

        // Core savepoint reached.
        upgrade_plugin_savepoint(true, 2017082302, 'totara', 'core');
    }

    if ($oldversion < 2017090600) {

        // Define field synctimemodified to be added to job_assignment.
        $table = new xmldb_table('job_assignment');
        $field = new xmldb_field('synctimemodified', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, '0', 'totarasync');

        // Conditionally launch add field synctimemodified.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Core savepoint reached.
        upgrade_plugin_savepoint(true, 2017090600, 'totara', 'core');
    }

    if ($oldversion < 2017112700) {
        // Update the indexes on the course_info_data table.
        $table = new xmldb_table('course_info_data');

        // Define new index to be added.
        $index = new xmldb_index('courinfodata_cou_ix', XMLDB_INDEX_NOTUNIQUE, array('courseid'));
        // Conditionally launch to add index.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2017112700, 'totara', 'core');
    }

    if ($oldversion < 2017112701) {
        // Update the indexes on the course_info_data table.
        $table = new xmldb_table('course_info_data');

        // Define new index to be added.
        $index = new xmldb_index('courinfodata_fiecou_uix', XMLDB_INDEX_UNIQUE, array('fieldid', 'courseid'));
        // Conditionally launch to add index.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2017112701, 'totara', 'core');
    }

    if ($oldversion < 2017112702) {
        // Update the indexes on the user_info_data table.
        $table = new xmldb_table('user_info_data');

        // Define new index to be added.
        $index = new xmldb_index('userinfodata_fie_ix', XMLDB_INDEX_NOTUNIQUE, array('fieldid'));
        // Conditionally launch to add index.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2017112702, 'totara', 'core');
    }

    if ($oldversion < 2017112703) {
        // Update the indexes on the user_info_data table.
        $table = new xmldb_table('user_info_data');

        // Define new index to be added.
        $index = new xmldb_index('userinfodata_use_ix', XMLDB_INDEX_NOTUNIQUE, array('userid'));
        // Conditionally launch to add index.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2017112703, 'totara', 'core');
    }

    if ($oldversion < 2017122201) {
        // Enable registration, only wa to disable it is via config.php,
        // admins will be asked to select the site type during upgrade
        // and they will be briefed about the data sending to Totara server.
        set_config('registrationenabled', 1);

        upgrade_plugin_savepoint(true, 2017122201, 'totara', 'core');
    }

    if ($oldversion < 2018021300) {

        // Define table persistent_login to be created.
        $table = new xmldb_table('persistent_login');

        // Adding fields to table persistent_login.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('cookie', XMLDB_TYPE_CHAR, '128', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timeautologin', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('useragent', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('sid', XMLDB_TYPE_CHAR, '128', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lastaccess', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('lastip', XMLDB_TYPE_CHAR, '45', null, null, null, null);

        // Adding keys to table persistent_login.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

        // Adding indexes to table persistent_login.
        $table->add_index('cookie', XMLDB_INDEX_UNIQUE, array('cookie'));
        $table->add_index('sid', XMLDB_INDEX_UNIQUE, array('sid'));

        // Conditionally launch create table for persistent_login.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Core savepoint reached.
        upgrade_plugin_savepoint(true, 2018021300, 'totara', 'core');
    }

    if ($oldversion < 2018030501) {
        totara_core_migrate_bogus_course_backup_areas();

        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2018030501, 'totara', 'core');
    }

    if ($oldversion < 2018030502) {
        // Migrate renamed setting.
        set_config('backup_auto_shortname', get_config('backup', 'backup_shortname'), 'backup');
        set_config('backup_shortname', null, 'backup');

        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2018030502, 'totara', 'core');
    }

    if ($oldversion < 2018030503) {

        // Define table backup_trusted_files to be created.
        $table = new xmldb_table('backup_trusted_files');

        // Adding fields to table backup_trusted_files.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('contenthash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
        $table->add_field('filesize', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('backupid', XMLDB_TYPE_CHAR, '32', null, null, null, null);
        $table->add_field('timeadded', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table backup_trusted_files.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

        // Adding indexes to table backup_trusted_files.
        $table->add_index('contenthash', XMLDB_INDEX_UNIQUE, array('contenthash'));

        // Conditionally launch create table for backup_trusted_files.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Core savepoint reached.
        upgrade_plugin_savepoint(true, 2018030503, 'totara', 'core');
    }

    if ($oldversion < 2018031202) {
        // Increase course fullname field to 1333 characters.
        $table = new xmldb_table('course');
        $field = new xmldb_field('fullname', XMLDB_TYPE_CHAR, '1333', null, XMLDB_NOTNULL, null);

        $dbman->change_field_precision($table, $field);

        // Core savepoint reached.
        upgrade_plugin_savepoint(true, 2018031202, 'totara', 'core');
    }

    return true;
}
