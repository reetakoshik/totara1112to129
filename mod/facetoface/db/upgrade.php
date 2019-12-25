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
 * @package mod_facetoface
 */

require_once($CFG->dirroot.'/mod/facetoface/db/upgradelib.php');

// This file keeps track of upgrades to
// the facetoface module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

/**
 * Local database upgrade script
 *
 * @param   int $oldversion Current (pre-upgrade) local db version timestamp
 * @return  boolean always true
 */
function xmldb_facetoface_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // Totara 10 branching line.

    if ($oldversion < 2016110200) {

        // Remove seminar notifications for removed seminars.
        // Regression T-14050.
        $sql = "DELETE FROM {facetoface_notification} WHERE facetofaceid NOT IN (SELECT id FROM {facetoface})";
        $DB->execute($sql);

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2016110200, 'facetoface');
    }

    if ($oldversion < 2016110900) {

        $table = new xmldb_table('facetoface_notification_tpl');
        $field = new xmldb_field('ccmanager', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'body');
        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $templates = $DB->get_records('facetoface_notification_tpl', null, '', 'id, reference');
        $transaction = $DB->start_delegated_transaction();
        $references = array('confirmation', 'cancellation', 'reminder', 'request', 'adminrequest', 'registrationclosure');
        foreach ($templates as $template) {
            $todb = new stdClass();
            $todb->id = $template->id;
            $todb->ccmanager = (in_array($template->reference, $references) ? 1 : 0);
            $DB->update_record('facetoface_notification_tpl', $todb);
        }
        $transaction->allow_commit();

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2016110900, 'facetoface');
    }

    if ($oldversion < 2016122101) {
        // Adding "Below is the message that was sent to learner:" to the end of prefix text for existing notifications.
        // This will upgrade only non-changed text in comparison to original v9 manager prefix.
        facetoface_upgradelib_managerprefix_clarification();

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2016122101, 'facetoface');
    }

    if ($oldversion < 2017052200) {
        // Updating registrationtimestart and registrationtimefinish with null values to 0.
        $sql = 'UPDATE {facetoface_sessions}
                SET registrationtimestart = 0
                WHERE registrationtimestart IS NULL';
        $DB->execute($sql);

        $sql = 'UPDATE {facetoface_sessions}
                SET registrationtimefinish = 0
                WHERE registrationtimefinish IS NULL';
        $DB->execute($sql);

        // Changing the default of field registrationtimestart on table facetoface_sessions to 0.
        $table = new xmldb_table('facetoface_sessions');
        $field = new xmldb_field('registrationtimestart', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'sendcapacityemail');

        // Launch change of default for field registrationtimestart.
        $dbman->change_field_default($table, $field);
        $dbman->change_field_notnull($table, $field);

        // Changing the default of field registrationtimefinish on table facetoface_sessions to 0.
        $field = new xmldb_field('registrationtimefinish', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'registrationtimestart');

        // Launch change of default for field registrationtimefinish.
        $dbman->change_field_default($table, $field);
        $dbman->change_field_notnull($table, $field);

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2017052200, 'facetoface');
    }

    if ($oldversion < 2017062900) {
        $table = new xmldb_table('facetoface_sessions_dates');

        // Adding unique indexes to timestart and timefinish. It is required as reports during event grouping rely
        // on timestart and timefinish to get their timezone.
        $index = new xmldb_index('facesessdate_sessta_ix', XMLDB_INDEX_UNIQUE, array('sessionid', 'timestart'));
        // Conditionally launch add index sessionid, timestart
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $index = new xmldb_index('facesessdate_sesfin_ix', XMLDB_INDEX_UNIQUE, array('sessionid', 'timefinish'));
        // Conditionally launch add index sessionid, timefinish
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2017062900, 'facetoface');
    }

    if ($oldversion < 2017092501) {
        // Define index mailed (not unique) to be dropped form assign_grades.
        $table = new xmldb_table('facetoface_notification_tpl');
        $index = new xmldb_index('title', XMLDB_INDEX_UNIQUE, array('title'));

        // Conditionally launch drop unique index title.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }
        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2017092501, 'facetoface');
    }

    if ($oldversion < 2017103100) {
        facetoface_upgradelib_calendar_events_for_sessiondates();

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2017103100, 'facetoface');
    }

    if ($oldversion < 2017112000) {
        // Update the indexes on the facetoface_asset_info_data table.
        $table = new xmldb_table('facetoface_asset_info_data');

        // Define new index to be added.
        $index = new xmldb_index('faceasseinfodata_fiefac_uix', XMLDB_INDEX_UNIQUE, array('fieldid', 'facetofaceassetid'));
        // Conditionally launch to add index.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_mod_savepoint(true, 2017112000, 'facetoface');
    }

    if ($oldversion < 2017112001) {
        // Update the indexes on the facetoface_cancellation_info_data table.
        $table = new xmldb_table('facetoface_cancellation_info_data');

        // Define new index to be added.
        $index = new xmldb_index('faceasseinfodata_fiefac_uix', XMLDB_INDEX_UNIQUE, array('fieldid', 'facetofacecancellationid'));
        // Conditionally launch to add index.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_mod_savepoint(true, 2017112001, 'facetoface');
    }

    if ($oldversion < 2017112002) {
        // Update the indexes on the facetoface_room_info_data table.
        $table = new xmldb_table('facetoface_room_info_data');

        // Define new index to be added.
        $index = new xmldb_index('faceroominfodata_fiefac_uix', XMLDB_INDEX_UNIQUE, array('fieldid', 'facetofaceroomid'));
        // Conditionally launch to add index.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_mod_savepoint(true, 2017112002, 'facetoface');
    }

    if ($oldversion < 2017112003) {
        // Update the indexes on the facetoface_session_info_data table.
        $table = new xmldb_table('facetoface_session_info_data');

        // Define new index to be added.
        $index = new xmldb_index('facesessinfodata_fiefac_uix', XMLDB_INDEX_UNIQUE, array('fieldid', 'facetofacesessionid'));
        // Conditionally launch to add index.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_mod_savepoint(true, 2017112003, 'facetoface');
    }

    if ($oldversion < 2017112004) {
        // Update the indexes on the facetoface_sessioncancel_info_data table.
        $table = new xmldb_table('facetoface_sessioncancel_info_data');

        // Define new index to be added.
        $index = new xmldb_index('facesecainfodata_fiefac_uix', XMLDB_INDEX_UNIQUE, array('fieldid', 'facetofacesessioncancelid'));
        // Conditionally launch to add index.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_mod_savepoint(true, 2017112004, 'facetoface');
    }

    if ($oldversion < 2017112005) {
        // Update the indexes on the facetoface_signup_info_data table.
        $table = new xmldb_table('facetoface_signup_info_data');

        // Define new index to be added.
        $index = new xmldb_index('facesigninfodata_fiefac_uix', XMLDB_INDEX_UNIQUE, array('fieldid', 'facetofacesignupid'));
        // Conditionally launch to add index.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_mod_savepoint(true, 2017112005, 'facetoface');
    }

    if ($oldversion < 2018022600) {
        // Remove invalid plugin version introduced by wrong upgrade steps in TL-15995.
        set_config('version', null, 'totara_facetoface');
        upgrade_mod_savepoint(true, 2018022600, 'facetoface');
    }

    return true;
}
