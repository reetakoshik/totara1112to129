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

    if ($oldversion < 2018101900) {

        $table = new xmldb_table('facetoface_notification_tpl');
        $field = new xmldb_field('title', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $dbman->change_field_type($table, $field);

        $table = new xmldb_table('facetoface_notification');
        $index = new xmldb_index('title', XMLDB_INDEX_NOTUNIQUE, array('title'));
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        $field = new xmldb_field('title', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $dbman->change_field_precision($table, $field);

        upgrade_mod_savepoint(true, 2018101900, 'facetoface');
    }

    // Multiple signups upgrade part 1 of 3.
    if ($oldversion < 2018102700) {

        // First set the activity default settings to maintain previous behaviour.
        $oldmulti = (bool) get_config(null, 'facetoface_multiplesessions');
        set_config('facetoface_multisignup_enable', $oldmulti);

        $restrictions = $oldmulti ? '' : 'multisignuprestrict_partially,multisignuprestrict_noshow';
        set_config('facetoface_multisignup_restrict', $restrictions);

        $maximum = $oldmulti ? 0 : 2;
        set_config('facetoface_multisignup_maximum', $maximum);

        set_config('facetoface_waitlistautoclean', 0); // Disable to maintain previous behaviour.

        // Then Create the columns for the restrictions on multiple signups.
        $table = new xmldb_table('facetoface');
        $fields = [];

        // multisignupfully - only fully attended users can signup for another event
        $fields[] = new xmldb_field('multisignupfully', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        // multisignuppartly - only partially attended users can signup for another event
        $fields[] = new xmldb_field('multisignuppartly', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        // multisignupnoshow - only users marked as no shows can signup for another event
        $fields[] = new xmldb_field('multisignupnoshow', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        // multisignupmaximum - the maximum amount of event a user can signup for.
        $fields[] = new xmldb_field('multisignupmaximum', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // waitlistautoclean - Whether to clean the waitlist for an event after it has begun.
        $fields[] = new xmldb_field('waitlistautoclean', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_mod_savepoint(true, 2018102700, 'facetoface');
    }

    // Multiple signups upgrade part 2 of 3.
    if ($oldversion < 2018102800) {
        // Create the default notification template for waitlistautoclean.
        $title = get_string('setting:defaultwaitlistautocleansubjectdefault', 'facetoface');
        if (\core_text::strlen($title) > 255) {
            $title = \core_text::substr($title, 0, 255);
        }

        $body = text_to_html(get_string('setting:defaultwaitlistautocleanmessagedefault', 'facetoface'));

        if (!$DB->record_exists('facetoface_notification_tpl', ['reference' => 'waitlistautoclean'])) {
            $tpl_waitlistautoclean = new stdClass();
            $tpl_waitlistautoclean->status = 1;
            $tpl_waitlistautoclean->reference = 'waitlistautoclean';
            $tpl_waitlistautoclean->title = $title;
            $tpl_waitlistautoclean->body = $body;
            $tpl_waitlistautoclean->ccmanager = 0;
            $templateid = $DB->insert_record('facetoface_notification_tpl', $tpl_waitlistautoclean);
        } else {
            $templateid = $DB->get_field('facetoface_notification_tpl', 'id', ['reference' => 'waitlistautoclean']);
        }

        // Now add the new template to existing seminars.
        // NOTE: We don't normally want to do this, but it's safe to do
        //       here since it's disabled by default and they wont send
        //       unless someone turns on the setting.
        $conditiontype = 524288; // Constant MDL_F2F_CONDITION_WAITLIST_AUTOCLEAN.
        $sql = 'SELECT f.*
                  FROM {facetoface} f
             LEFT JOIN {facetoface_notification} fn
                    ON fn.facetofaceid = f.id
                   AND fn.conditiontype = :ctype
             WHERE fn.id IS NULL';
        $f2fs = $DB->get_records_sql($sql, ['ctype' => $conditiontype]);

        $data = new stdClass();
        $data->type = 4; // MDL_F2F_NOTIFICATION_AUTO.
        $data->conditiontype = $conditiontype;
        $data->booked = 0;
        $data->waitlisted = 0;
        $data->cancelled = 0;
        $data->requested = 0;
        $data->issent = 0;
        $data->status = 0; // Disable for existing seminars.
        $data->templateid = $templateid;
        $data->ccmanager = 0;
        $data->title = $title;
        $data->body = $body;

        foreach ($f2fs as $f2f) {
            $notification = clone($data);
            $notification->facetofaceid = $f2f->id;
            $notification->courseid = $f2f->course;

            $DB->insert_record('facetoface_notification', $notification);
        }

        upgrade_mod_savepoint(true, 2018102800, 'facetoface');
    }

    // Multiple signups upgrade part 3 of 3.
    if ($oldversion < 2018102900) {
        // Just to be safe, set maximum to 1 if multisignups is disabled.
        $DB->execute('UPDATE {facetoface}
                         SET multisignupmaximum = 1
                       WHERE multiplesessions = 0');

        // Quick change to the settings for the amount dropdown.
        $enabled = (bool) get_config(null, 'facetoface_multiplesessions');

        $amount = $enabled ? 0 : 1;
        set_config('facetoface_multisignupamount', $amount);

        // Now we have finally reached the final stage of multisignup upgrades.
        // Unset the old setting, and the two new ones merged here.
        unset_config('facetoface_multiplesessions');
        unset_config('facetoface_multisignup_enable');
        unset_config('facetoface_multisignup_maximum');

        upgrade_mod_savepoint(true, 2018102900, 'facetoface');
    }

    if ($oldversion < 2018112201) {
        // Remove facetoface_fromaddress config as we use noreply address only, see TL-13943.
        unset_config('facetoface_fromaddress');
        upgrade_mod_savepoint(true, 2018112201, 'facetoface');
    }

    // Update the template's title for seminar's trainer confirmation.
    if ($oldversion < 2018112202) {
        // By default: [eventperiod] => [starttime]-[finishtime], [sessiondate]
        $default = array(
            "trainerconfirm" => array(
                "old" =>  "Seminar trainer confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]",
                "new" => "Seminar trainer confirmation: [facetofacename], [eventperiod]"
            ),
            "rolerequest" => array(
                "old" => "Seminar booking role request: [facetofacename], [starttime]-[finishtime], [sessiondate]",
                "new" => "Seminar booking role request: [facetofacename], [eventperiod]",
            ),
            "request" => array(
                "old" => "Seminar booking request: [facetofacename], [starttime]-[finishtime], [sessiondate]",
                "new" => "Seminar booking request: [facetofacename], [eventperiod]"
            ),
            "adminrequest" => array(
                "old" => "Seminar booking admin request: [facetofacename], [starttime]-[finishtime], [sessiondate]",
                "new" => "Seminar booking admin request: [facetofacename], [eventperiod]"
            )
        );

        $references = array("trainerconfirm", "rolerequest", "request", "adminrequest");
        list($sqlin, $params) = $DB->get_in_or_equal($references);

        $sql = "SELECT * FROM {facetoface_notification_tpl} WHERE reference {$sqlin}";
        $records = $DB->get_records_sql($sql, $params);
        foreach ($records as $record) {
            if (isset($default[$record->reference])) {
                $title = $default[$record->reference];
                // Only updating the title if it is the same, with the old default, otherwise,
                // leave it be, as user already modified it.
                if ($title['old'] === $record->title) {
                    $record->title = $title['new'];
                    $DB->update_record("facetoface_notification_tpl", $record);
                }
            }
        }

        upgrade_mod_savepoint(true, 2018112202, 'facetoface');
    }

    if ($oldversion < 2018112205) {
        // Update all job assignments to NULL where were deleted from user job assignments.
        $sql = "UPDATE {facetoface_signups}
                   SET jobassignmentid = NULL
                 WHERE jobassignmentid NOT IN (
                       SELECT id
                         FROM {job_assignment}
                 )";
        $DB->execute($sql);

        upgrade_mod_savepoint(true, 2018112205, 'facetoface');
    }

    if ($oldversion < 2018112207) {
        // Fix grades if necessary
        facetoface_upgradelib_fixup_seminar_grades();

        upgrade_mod_savepoint(true, 2018112207, 'facetoface');
    }

    if ($oldversion < 2018112208) {
        // Create the default notification template for undercapacity.
        $title = get_string('setting:defaultundercapacitysubjectdefault', 'facetoface');
        if (\core_text::strlen($title) > 255) {
            $title = \core_text::substr($title, 0, 255);
        }

        $body = text_to_html(get_string('setting:defaultundercapacitymessagedefault', 'facetoface'));

        if (!$DB->record_exists('facetoface_notification_tpl', ['reference' => 'undercapacity'])) {
            $tpl_undercapacity = new stdClass();
            $tpl_undercapacity->status = 1;
            $tpl_undercapacity->reference = 'undercapacity';
            $tpl_undercapacity->title = $title;
            $tpl_undercapacity->body = $body;
            $tpl_undercapacity->ccmanager = 0;
            $templateid = $DB->insert_record('facetoface_notification_tpl', $tpl_undercapacity);
        } else {
            $templateid = $DB->get_field('facetoface_notification_tpl', 'id', ['reference' => 'undercapacity']);
        }

        // Now add the new template to existing seminars.
        // NOTE: We don't normally want to do this, but it's safe to do
        //       here since this is replacing an existing non-template notification.
        $conditiontype = 1048576; // Constant MDL_F2F_CONDITION_SESSION_UNDER_CAPACITY.
        $sql = 'SELECT f.*
                  FROM {facetoface} f
             LEFT JOIN {facetoface_notification} fn
                    ON fn.facetofaceid = f.id
                   AND fn.conditiontype = :ctype
             WHERE fn.id IS NULL';
        $f2fs = $DB->get_records_sql($sql, ['ctype' => $conditiontype]);

        $data = new stdClass();
        $data->type = 4; // MDL_F2F_NOTIFICATION_AUTO.
        $data->conditiontype = $conditiontype;
        $data->booked = 0;
        $data->waitlisted = 0;
        $data->cancelled = 0;
        $data->requested = 0;
        $data->issent = 0;
        $data->status = 1; // Replacing a hard-coded template
        $data->templateid = $templateid;
        $data->ccmanager = 0;
        $data->title = $title;
        $data->body = $body;

        foreach ($f2fs as $f2f) {
            $notification = clone($data);
            $notification->facetofaceid = $f2f->id;
            $notification->courseid = $f2f->course;

            $DB->insert_record('facetoface_notification', $notification);
        }

        upgrade_mod_savepoint(true, 2018112208, 'facetoface');
    }

    // Remove orphaned session roles product of deleting a user.
    if ($oldversion < 2018112209) {
        $sql = "DELETE FROM {facetoface_session_roles} WHERE userid IN (SELECT id FROM {user} WHERE deleted = 1)";
        $DB->execute($sql);

        upgrade_mod_savepoint(true, 2018112209, 'facetoface');
    }

    if ($oldversion < 2018112210) {
        // Reset managerid if it is incorrectly set.
        $sql = 'UPDATE {facetoface_signups}
                   SET managerid = NULL
                 WHERE managerid = 0
                   AND id IN (
                    SELECT DISTINCT signupid
                      FROM {facetoface_signups_status}
                     WHERE superceded = 0 AND (statuscode = 40 OR statuscode = 45)
                   )';
        $DB->execute($sql);

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2018112210, 'facetoface');
    }

    return true;
}
