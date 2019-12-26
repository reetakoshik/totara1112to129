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


    if ($oldversion < 2018031501) {
        $deletedauths = array('fc', 'imap', 'nntp', 'none', 'pam', 'pop3');
        foreach ($deletedauths as $auth) {
            if ($DB->record_exists('user', array('auth' => $auth, 'deleted' => 0))) {
                // Keep the auth plugin settings,
                // admins will have to uninstall this manually.
                continue;
            }
            uninstall_plugin('auth', $auth);
        }

        uninstall_plugin('tool', 'innodb');

        // Core savepoint reached.
        upgrade_plugin_savepoint(true, 2018031501, 'totara', 'core');
    }

    if ($oldversion < 2018032600) {
        // Increase course fullname field to 1333 characters.
        $table = new xmldb_table('course');
        $field = new xmldb_field('fullname', XMLDB_TYPE_CHAR, '1333', null, XMLDB_NOTNULL, null);

        $dbman->change_field_precision($table, $field);

        // Core savepoint reached.
        upgrade_plugin_savepoint(true, 2018032600, 'totara', 'core');
    }

    if ($oldversion < 2018071000) {
        // Remove docroot setting if it matches previous default.
        if (get_config('core', 'docroot') == 'http://docs.moodle.org') {
            set_config('docroot', '');
        }

        // Core savepoint reached.
        upgrade_plugin_savepoint(true, 2018071000, 'totara', 'core');
    }

    if ($oldversion < 2018082000) {
        // Moodle changed their default from http to https so we replace that as well
        if (get_config('core', 'docroot') == 'https://docs.moodle.org') {
            set_config('docroot', '');
        }

        // Core savepoint reached.
        upgrade_plugin_savepoint(true, 2018082000, 'totara', 'core');
    }

    if ($oldversion < 2018082500) {
        // Moodle introduced the settings 'test_password' and 'test_serializer' for the redis cache store.
        // We set it to an empty string if the setting it not yet set
        if (get_config('cachestore_redis', 'test_password') === false) {
            set_config('test_password', '', 'cachestore_redis');
        }
        // We set it to the default php serializer if the setting it not yet set.
        if (get_config('cachestore_redis', 'test_serializer') === false) {
            set_config('test_serializer', 1, 'cachestore_redis');
        }

        // Core savepoint reached.
        upgrade_plugin_savepoint(true, 2018082500, 'totara', 'core');
    }

    if ($oldversion < 2018091100) {
        // Update the indexes on the course_info_data table.
        $table = new xmldb_table('course_completion_criteria');

        // Define new index to be added.
        $index = new xmldb_index('moduleinstance', XMLDB_INDEX_NOTUNIQUE, array('moduleinstance'));
        // Conditionally launch to add index.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Core savepoint reached.
        upgrade_plugin_savepoint(true, 2018091100, 'totara', 'core');
    }

    if ($oldversion < 2018092100) {
        // Increase course_request fullname column to match the fullname column in the "course" table.
        $table = new xmldb_table('course_request');

        $field = new xmldb_field('fullname', XMLDB_TYPE_CHAR, '1333', null, XMLDB_NOTNULL, null);
        $dbman->change_field_precision($table, $field);

        upgrade_plugin_savepoint(true, 2018092100, 'totara', 'core');
    }

    if ($oldversion < 2018092101) {
        // Increase course_request shortname column to match the shortname column in the "course" table.
        $table = new xmldb_table('course_request');
        $field = new xmldb_field('shortname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $index = new xmldb_index('shortname', XMLDB_INDEX_NOTUNIQUE, array('shortname'));

        // Conditionally launch drop index name to amend the field precision.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }
        // Change the field precision.
        $dbman->change_field_precision($table, $field);
        // Add back our 'shortname' index after the table has been amended.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2018092101, 'totara', 'core');
    }

    if ($oldversion < 2018092600) {
        // Removing cachestore plugin incompatible with PHP7.
        if (!file_exists($CFG->dirroot . '/cache/stores/memcache/settings.php')) {
            unset_all_config_for_plugin('cachestore_memcache');
        }

        // Core savepoint reached.
        upgrade_plugin_savepoint(true, 2018092600, 'totara', 'core');
    }

    if ($oldversion < 2018100100) {
        // Upgrade the old frontpage block bits.
        totara_core_migrate_frontpage_display();

        // Core savepoint reached.
        upgrade_plugin_savepoint(true, 2018100100, 'totara', 'core');
    }

    if ($oldversion < 2018100101) {
        // Clean up the frontpage settings.
        unset_config('frontpage', 'core');
        unset_config('frontpageloggedin', 'core');
        unset_config('courseprogress', 'core');
        unset_config('maxcategorydepth', 'core');

        // Core savepoint reached.
        upgrade_plugin_savepoint(true, 2018100101, 'totara', 'core');
    }

    if ($oldversion < 2018102600) {
        // Define table quickaccess_preferences to be created.
        $table = new xmldb_table('quickaccess_preferences');

        // Adding fields to table quickaccess_preferences.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('value', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table quickaccess_preferences.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table quickaccess_preferences.
        $table->add_index('quickaccesspref_user_uix', XMLDB_INDEX_NOTUNIQUE, array('userid'));
        $table->add_index('quickaccesspref_usenam_uix', XMLDB_INDEX_UNIQUE, array('userid', 'name'));

        // Conditionally launch create table for quickaccess_preferences.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Core savepoint reached.
        upgrade_plugin_savepoint(true, 2018102600, 'totara', 'core');
    }

    if ($oldversion < 2018111200) {
        // Clean up the old coursetagging setting
        unset_config('coursetagging', 'moodlecourse');

        // Core savepoint reached.
        upgrade_plugin_savepoint(true, 2018111200, 'totara', 'core');
    }

    if ($oldversion < 2018112201) {
        // Add 'course_navigation' block to all existing courses.
        totara_core_add_course_navigation();

        // Core savepoint reached.
        upgrade_plugin_savepoint(true, 2018112201, 'totara', 'core');
    }

    if ($oldversion < 2018112202) {
        // Add missing class names for custom main menu items.
        $DB->set_field_select('totara_navigation', 'classname', '\totara_core\totara\menu\item', "custom = 1 AND url <> ''");
        $DB->set_field_select('totara_navigation', 'classname', '\totara_core\totara\menu\container', "custom = 1 AND url = ''");

        // Switch to one show flag for both custom and default items.
        $DB->set_field('totara_navigation', 'visibility', '1', array('visibility' => '2'));

        // Migrate to new item for grid catalog, old mixed class is gone.
        $DB->delete_records('totara_navigation', array('classname' => '\totara_catalog\totara\menu\catalog'));

        // Core savepoint reached.
        upgrade_plugin_savepoint(true, 2018112202, 'totara', 'core');
    }

    if ($oldversion < 2018112304) {
        $duration = get_config('moodlecourse', 'courseduration');
        if ($duration !== false) {
            // adjust the default course duration.
            if ($duration <= 0) {
                // if it is 0, set it back to 365 days, the internal default duration.
                $duration = YEARSECS;
            } else if ($duration < HOURSECS) {
                // if it is less than an hour, set it to an hour.
                $duration = HOURSECS;
            }
            set_config('courseduration', $duration, 'moodlecourse');
        }
    }

    if ($oldversion < 2018112306) {
        if (get_config('moodlecourse', 'courseenddateenabled') === false) {
            set_config('courseenddateenabled', 1, 'moodlecourse');
        }

        // Core savepoint reached.
        upgrade_plugin_savepoint(true, 2018112306, 'totara', 'core');
    }

    if ($oldversion < 2018112307) {
        totara_core_upgrade_course_defaultimage_config();
        totara_core_upgrade_course_images();
        upgrade_plugin_savepoint(true, 2018112307, 'totara', 'core');
    }

    if ($oldversion < 2018112309) {

        // Define index status (not unique) to be added to course_completions.
        $table = new xmldb_table('course_completions');
        $index = new xmldb_index('status', XMLDB_INDEX_NOTUNIQUE, array('status'));

        // Conditionally launch add index status.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Core savepoint reached.
        upgrade_plugin_savepoint(true, 2018112309, 'totara', 'core');
    }

    // This code moved here from lib/db/upgrade.php because it was excluded from
    // Totara 12 during the merge from Moodle 3.3.9. This code and comment should
    // be removed from here if a merge from a Moodle version higher than 3.6.4
    // were to occur, effectively moving this back into Moodle core upgrade.php
    if ($oldversion < 2018112310) {
        // Conditionally add field requireconfirmation to oauth2_issuer.
        $table = new xmldb_table('oauth2_issuer');
        $field = new xmldb_field('requireconfirmation', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'sortorder');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2018112310, 'totara', 'core');
    }

    if ($oldversion < 2018112312) {
        // Delete any orphaned course completions records that may exist as a result of course deletion race condition.
        $DB->execute('DELETE FROM {course_completions} WHERE course NOT IN (SELECT id FROM {course})');

        upgrade_plugin_savepoint(true, 2018112312, 'totara', 'core');
    }

    if ($oldversion < 2018112314) {
        // Correct tags with encoded HTML entities.
        totara_core_core_tag_upgrade_tags();

        upgrade_plugin_savepoint(true, 2018112314, 'totara', 'core');
    }

    if ($oldversion < 2018112317) {
        // Delete any un-created drag-and-drop SCORM modules (where instance = 0).
        $mod_scorm = $DB->get_record('modules', array('name' => 'scorm'), 'id');
        $DB->delete_records('course_modules', array('module' => $mod_scorm->id, 'instance' => 0));

        upgrade_plugin_savepoint(true, 2018112317, 'totara', 'core');
    }

    return true;
}
