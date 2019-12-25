<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author Andrew Bell <andrewb@learningpool.com>
 * @author Ryan Lynch <ryanlynch@learningpool.com>
 * @author Barry McKay <barry@learningpool.com>
 *
 * @package auth_approved
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_auth_approved_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2018112201) {

        // Define field extradata to be added to auth_approved_request.
        $table = new xmldb_table('auth_approved_request');
        $field = new xmldb_field('extradata', XMLDB_TYPE_TEXT, null, null, null, null, null, 'timeresolved');

        // Conditionally launch add field extradata.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Approved savepoint reached.
        upgrade_plugin_savepoint(true, 2018112201, 'auth', 'approved');
    }

    return true;
}
