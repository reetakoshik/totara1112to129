<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Yuliya Bozhko <yuliya.bozhko@totaralearning.com>
 * @package mod_lti
 */

/**
 * Function for Totara specific DB changes to core Moodle plugins.
 *
 * Put code here rather than in db/upgrade.php if you need to change core
 * Moodle database schema for Totara-specific changes.
 *
 * This is executed during EVERY upgrade. Make sure your code can be
 * re-executed EVERY upgrade without problems.
 *
 * You need to increment the upstream plugin version by .01 to get
 * this code executed!
 *
 * Do not use savepoints in this code!
 *
 * @param string $version the plugin version
 */
function xmldb_lti_totara_postupgrade($version) {
    global $DB;

    $dbman = $DB->get_manager();

    // Define table lti_submission_history to be created.
    $table = new xmldb_table('lti_submission_history');

    // Adding fields to table lti_submission_history.
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('ltiid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('launchid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

    // Adding keys to table lti_submission_history.
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
    $table->add_key('ltiid', XMLDB_KEY_FOREIGN, array('ltiid'), 'lti', array('id'));
    $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

    // Adding indexes to table lti_submission_history.
    $table->add_index('ltiid-userid', XMLDB_INDEX_NOTUNIQUE, array('ltiid', 'userid'));

    // Conditionally launch create table for lti_submission_history.
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }
}
