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
 * @subpackage totara_core
 */

function xmldb_totara_core_install() {
    global $CFG, $DB, $SITE;
    require_once(__DIR__ . '/upgradelib.php');

    // switch to new default theme in totara 9.0
    set_config('theme', 'basis');

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes
    $systemcontext = context_system::instance();
    // add coursetype and icon fields to course table

    $table = new xmldb_table('course');

    $field = new xmldb_field('coursetype');
    if (!$dbman->field_exists($table, $field)) {
        $field->set_attributes(XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', null);
        $dbman->add_field($table, $field);
    }

    $field = new xmldb_field('icon');
    if (!$dbman->field_exists($table, $field)) {
        $field->set_attributes(XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $dbman->add_field($table, $field);
    }

    // Create totara roles.
    $staffmanagerrole    = create_role('', 'staffmanager', '', 'staffmanager');
    // Following roles are not created any more since Totara 8.0 - assessor, regionalmanager, regionaltrainer.

    $newroles = array($staffmanagerrole);

    foreach ($DB->get_records('role') as $role) {
        // Add allow* defaults related to all new roles.
        foreach (array('assign', 'override', 'switch') as $type) {
            $function = 'allow_'.$type;
            $allows = get_default_role_archetype_allows($type, $role->archetype);
            foreach ($allows as $allowid) {
                if (!in_array($allowid, $newroles) and !in_array($role->id, $newroles)) {
                    // Add only entries related to new roles!
                    continue;
                }
                $function($role->id, $allowid);
            }
        }

        if (in_array($role->id, $newroles)) {
            // Set context levels for all new roles.
            set_role_contextlevels($role->id, get_default_contextlevels($role->shortname));

            // Reset existing permissions for all new roles.
            $defaultcaps = get_default_capabilities($role->archetype);
            foreach($defaultcaps as $cap => $permission) {
                assign_capability($cap, $permission, $role->id, $systemcontext->id);
            }
        }
    }

    // Reset legacy custom roles names for standard roles,
    // we want to use the lang strings from now on.
    $resetnames = array('manager', 'coursecreator', 'editingteacher', 'teacher', 'student', 'guest', 'user');
    foreach ($resetnames as $shortname) {
        if ($old_role = $DB->get_record('role', array('shortname' => $shortname))) {
            $new_role = new stdClass();
            $new_role->id = $old_role->id;
            $new_role->name = '';
            $new_role->description = '';

            $DB->update_record('role', $new_role);
        }
    }

    $systemcontext->mark_dirty();

    // Set up frontpage.
    set_config('frontpage', '');
    set_config('frontpageloggedin', '');

    // Turn completion on in Totara when upgrading from Moodle.
    set_config('enablecompletion', 1);
    set_config('enablecompletion', 1, 'moodlecourse');
    set_config('completionstartonenrol', 1, 'moodlecourse');

    // Add completionstartonenrol column to course table.
    $table = new xmldb_table('course');

    // Define field completionstartonenrol to be added to course.
    $field = new xmldb_field('completionstartonenrol', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

    // Conditionally launch add field completionstartonenrol.
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    // Add RPL column to course_completions table
    $table = new xmldb_table('course_completions');

    // Define field rpl to be added to course_completions
    $field = new xmldb_field('rpl', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'reaggregate');

    // Conditionally launch add field rpl
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    // Define field rplgrade to be added to course_completions
    $field = new xmldb_field('rplgrade', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null, 'rpl');

    // Conditionally launch add field rpl
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    // Add RPL column to course_completion_crit_compl table
    $table = new xmldb_table('course_completion_crit_compl');

    // Define field rpl to be added to course_completion_crit_compl
    $field = new xmldb_field('rpl', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'unenroled');

    // Conditionally launch add field rpl
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    // Define fields status and renewalstatus to be added to course_completions.
    $table = new xmldb_table('course_completions');
    $field = new xmldb_field('status', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');

    // Conditionally launch add field status.
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    $field = new xmldb_field('renewalstatus', XMLDB_TYPE_INTEGER, '2', null, null, null, '0');

    // Conditionally launch add field renewalstatus.
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    rebuild_course_cache($SITE->id, true);

    // readd totara specific course completion changes for anyone
    // upgrading from moodle 2.2.2+
    require_once($CFG->dirroot . '/totara/core/db/utils.php');
    totara_readd_course_completion_changes();

    // remove any references to "complete on unenrolment" critiera type
    // these could exist in an upgrade from moodle 2.2 but the criteria
    // was never implemented and is no longer in totara
    $DB->delete_records('course_completion_criteria', array('criteriatype' => 3));

    // Disable editing execpaths by default for security.
    set_config('preventexecpath', '1');
    // Then provide default values to prevent them appearing on the upgradesettings page.
    set_config('geoipfile', $CFG->dataroot . 'geoip/GeoLiteCity.dat');
    set_config('location', '', 'enrol_flatfile');
    set_config('filter_tex_pathlatex', '/usr/bin/latex');
    set_config('filter_tex_pathdvips', '/usr/bin/dvips');
    set_config('filter_tex_pathconvert', '/usr/bin/convert');
    set_config('pathtodu', '');
    set_config('pathtoclam', '');
    set_config('aspellpath', '');
    set_config('pathtodot', '');
    set_config('quarantinedir', '');
    set_config('backup_auto_destination', '', 'backup');
    set_config('gspath', '/usr/bin/gs', 'assignfeedback_editpdf');
    set_config('exporttofilesystempath', '', 'reportbuilder');
    set_config('pathlatex', '/usr/bin/latex', 'filter_tex');
    set_config('pathdvips', '/usr/bin/dvips', 'filter_tex');
    set_config('pathconvert', '/usr/bin/convert', 'filter_tex');
    set_config('pathmimetex', '', 'filter_tex');

    // Alter Moodle tables during migration to Totara.

    // Add extra 'user' table fields for totara sync.
    $table = new xmldb_table('user');
    $field = new xmldb_field('totarasync');
    $field->set_attributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', null);
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }
    $index = new xmldb_index('totarasync');
    $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('totarasync'));
    if (!$dbman->index_exists($table, $index)) {
        $dbman->add_index($table, $index);
    }

    // Define field completionprogressonview to be added to course.
    $table = new xmldb_table('course');
    $field = new xmldb_field('completionprogressonview', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 0, 'enablecompletion');

    // Conditionally launch add field completionprogressonview.
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    $field = new xmldb_field('audiencevisible', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, 2);

    // Conditionally launch add field audiencevisible to course table.
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    // Define field invalidatecache to be added to course_completions.
    $table = new xmldb_table('course_completions');
    $field = new xmldb_field('invalidatecache', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');

    // Conditionally launch add field invalidatecache.
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    // Add timecompleted for module completion.
    $table = new xmldb_table('course_modules_completion');
    $field = new xmldb_field('timecompleted', XMLDB_TYPE_INTEGER, '10');

    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    // Define field reaggregate to be added to course_modules_completion.
    $table = new xmldb_table('course_modules_completion');
    $field = new xmldb_field('reaggregate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timecompleted');

    // Conditionally launch add field reaggregate.
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    // Make sure we run the MSSQL fixes when upgrading from Moodle.
    if (!during_initial_install()) {
        totara_core_fix_old_upgraded_mssql();
    }

    // Remove private token column because all tokens were always supposed to be private.
    $table = new xmldb_table('external_tokens');
    $field = new xmldb_field('privatetoken', XMLDB_TYPE_CHAR, '64', null, null, null, null);
    if ($dbman->field_exists($table, $field)) {
        $dbman->drop_field($table, $field);
    }

    totara_core_upgrade_delete_moodle_plugins();

    // Remove settings for deleted Totara features.
    unset_config('allowedemaildomains');

    // Tweak backup/restore stuff.
    totara_core_migrate_bogus_course_backup_areas();
    set_config('backup_auto_shortname', get_config('backup', 'backup_shortname'), 'backup');
    set_config('backup_shortname', null, 'backup');

    // Increase course fullname field to 1333 characters.
    $table = new xmldb_table('course');
    $field = new xmldb_field('fullname', XMLDB_TYPE_CHAR, '1333', null, XMLDB_NOTNULL, null);
    $dbman->change_field_precision($table, $field);

    return true;
}
