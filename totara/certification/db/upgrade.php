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
 * @author Jon Sharp <jon.sharp@catalyst-eu.net>
 * @package totara
 * @subpackage certification
 */

// Certification db upgrades.

require_once($CFG->dirroot.'/totara/certification/db/upgradelib.php');

/**
 * Certification database upgrade script
 *
 * @param   integer $oldversion Current (pre-upgrade)
 * @return  boolean $result
 */
function xmldb_totara_certification_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // Totara 10 branching line.

    // TL-12606 Recalculate non-zero course set group completion records.
    if ($oldversion < 2017020700) {

        totara_certification_upgrade_non_zero_prog_completions();

        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2017020700, 'totara', 'certification');
    }

    // TL-16521 Reset messages that must have been sent before the related event, which means they must not have been
    // correctly reset when the recertification window opened (due to bug fixed in TL-10979).
    if ($oldversion < 2017121100) {

        totara_certification_upgrade_reset_messages();

        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2017121100, 'totara', 'certification');
    }

    if ($oldversion < 2018112000) {
        $table = new xmldb_table('certif_completion');
        $field = new xmldb_field('baselinetimeexpires', XMLDB_TYPE_INTEGER, '10', null, false, null);

        // Create new field for default expiry in the completion table.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

            $sql = 'UPDATE {certif_completion} SET baselinetimeexpires = timeexpires WHERE timeexpires IS NOT NULL';
            $DB->execute($sql);
        }

        $table = new xmldb_table('certif_completion_history');
        $field = new xmldb_field('baselinetimeexpires', XMLDB_TYPE_INTEGER, '10', null, false, null);

        // Create new field for default expiry in the completion history table.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

            $sql = 'UPDATE {certif_completion_history} SET baselinetimeexpires = timeexpires WHERE timeexpires IS NOT NULL';
            $DB->execute($sql);
        }

        // Savepoint reached
        upgrade_plugin_savepoint(true, 2018112000, 'totara', 'certification');
    }

    return true;
}
