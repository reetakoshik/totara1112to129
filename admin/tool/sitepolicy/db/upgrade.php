<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Courteney Brownie <courteney.brownie@totaralearning.com>
 * @package tool_sitepolicy
 */

/**
 * Upgrade script for tool_sitepolicy.
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the plugin.
 *
 * @param int $oldversion
 * @return bool always true
 */
function xmldb_tool_sitepolicy_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Totara 11 branching line.

    if ($oldversion < 2018050800) {
        // Add format fields for policytext and whatsnew.
        $table = new xmldb_table('tool_sitepolicy_localised_policy');
        $field = new xmldb_field('policytextformat', XMLDB_TYPE_INTEGER, '2', null, null, null, '1', 'policytext');

        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            $DB->execute("UPDATE {tool_sitepolicy_localised_policy} SET policytextformat = 2");
        }

        $field = new xmldb_field('whatsnewformat', XMLDB_TYPE_INTEGER, '2', null, null, null, '1', 'whatsnew');
        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            $DB->execute("UPDATE {tool_sitepolicy_localised_policy} SET whatsnewformat = 2");
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2018050800, 'tool', 'sitepolicy');
    }

    if ($oldversion < 2018082800) {
        global $DB;

        // Update report filter type currentversion => policytitle and reset default value.
        $DB->execute("UPDATE {report_builder_filters} SET
                value = 'policytitle',
                defaultvalue = NULL
            WHERE type = 'primarypolicy' AND value = 'currentversion'");

        // Select all reports with 'policytitle' filter present and then check whether the version filter is present.
        $reports = $DB->get_records_sql("SELECT DISTINCT(reportid) FROM {report_builder_filters}
            WHERE type = 'primarypolicy' AND value = 'policytitle'");

        foreach (array_keys($reports) as $report) {
            // Check whether a report has 'primarypolicy' 'versionnumber' filter
            if (!$DB->get_record('report_builder_filters', [
                'type' => 'primarypolicy',
                'value' => 'versionnumber',
                'reportid' => $report,
            ], 'id')) {
                // It does, need to add version filter.

                // 1. Get primary policy sort order.
                $order = $DB->get_field('report_builder_filters', 'sortorder', [
                    'type' => 'primarypolicy',
                    'value' => 'policytitle',
                    'reportid' => $report,
                ], '*');

                // 2. Bump all the sort order order.
                // Both $report and $order are coming from the database a few lines above and safe to use in
                // the query as is.
                $DB->execute("UPDATE {report_builder_filters}
                                   SET sortorder = sortorder + 1
                                   WHERE reportid = {$report} AND sortorder > {$order}");

                // Insert new filter after the old filter.
                $filter = (object) [
                    'reportid' => $report,
                    'type' => 'primarypolicy',
                    'value' => 'versionnumber',
                    'sortorder' => $order + 1,
                    'advanced' => 0,
                    'filtername' => '',
                    'customname' => 0,
                    'region' => '0',
                    'defaultvalue' => null,
                ];

                $DB->insert_record('report_builder_filters', $filter);
            }
        }

        // Point of noreturn.
        upgrade_plugin_savepoint(true, 2018082800, 'tool', 'sitepolicy');
    }

    return true;
}
