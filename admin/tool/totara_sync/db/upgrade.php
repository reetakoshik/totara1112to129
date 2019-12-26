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

require_once($CFG->dirroot . '/admin/tool/totara_sync/db/upgradelib.php');

/**
 * DB upgrades for Totara Sync
 */

function xmldb_tool_totara_sync_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // Totara 10 branching line.

    // TL-12312 Rename the setting which controls whether an import has previously linked on job assignment id number and
    // make sure that linkjobassignmentidnumber is enabled if it has previously linked on job assignment id number.
    if ($oldversion < 2016122300) {
        tool_totara_sync_upgrade_link_job_assignment_mismatch();

        upgrade_plugin_savepoint(true, 2016122300, 'tool', 'totara_sync');
    }

    // Set default for new 'sourceallrecords' setting for Organisations and Positions.
    if ($oldversion < 2017060800) {
        // Set source all records to 1 to preserve current behaviour in upgrades.
        set_config('sourceallrecords', '1', 'totara_sync_element_pos');
        set_config('sourceallrecords', '1', 'totara_sync_element_org');

        upgrade_plugin_savepoint(true, 2017060800, 'tool', 'totara_sync');
    }

    if ($oldversion < 2017081600) {
        $previouslylinkedonjobassignmentidnumber = get_config('totara_sync_element_user', 'previouslylinkedonjobassignmentidnumber');
        set_config('previouslylinkedonjobassignmentidnumber', $previouslylinkedonjobassignmentidnumber, 'totara_sync_element_jobassignment');
        $linkjobassignmentidnumber = get_config('totara_sync_element_user', 'linkjobassignmentidnumber');
        set_config('updateidnumbers', !$linkjobassignmentidnumber, 'totara_sync_element_jobassignment');
        unset_config('previouslylinkedonjobassignmentidnumber', 'totara_sync_element_user');
        unset_config('linkjobassignmentidnumber', 'totara_sync_element_user');

        upgrade_plugin_savepoint(true, 2017081600, 'tool', 'totara_sync');
    }

    if ($oldversion < 2017090500) {
        // Unset all Job Assignment settings from the user sources.
        unset_config('fieldmapping_jobassignmentenddate', 'totara_sync_source_user_csv');
        unset_config('fieldmapping_jobassignmentfullname', 'totara_sync_source_user_csv');
        unset_config('fieldmapping_jobassignmentidnumber', 'totara_sync_source_user_csv');
        unset_config('fieldmapping_jobassignmentstartdate', 'totara_sync_source_user_csv');
        unset_config('fieldmapping_manageridnumber', 'totara_sync_source_user_csv');
        unset_config('fieldmapping_managerjobassignmentidnumber', 'totara_sync_source_user_csv');
        unset_config('fieldmapping_orgidnumber', 'totara_sync_source_user_csv');
        unset_config('fieldmapping_posidnumber', 'totara_sync_source_user_csv');
        unset_config('fieldmapping_appraiseridnumber', 'totara_sync_source_user_csv');

        unset_config('import_jobassignmentenddate', 'totara_sync_source_user_csv');
        unset_config('import_jobassignmentfullname', 'totara_sync_source_user_csv');
        unset_config('import_jobassignmentidnumber', 'totara_sync_source_user_csv');
        unset_config('import_jobassignmentstartdate', 'totara_sync_source_user_csv');
        unset_config('import_manageridnumber', 'totara_sync_source_user_csv');
        unset_config('import_managerjobassignmentidnumber', 'totara_sync_source_user_csv');
        unset_config('import_orgidnumber', 'totara_sync_source_user_csv');
        unset_config('import_posidnumber', 'totara_sync_source_user_csv');
        unset_config('import_appraiseridnumber', 'totara_sync_source_user_csv');

        unset_config('fieldmapping_jobassignmentenddate', 'totara_sync_source_user_database');
        unset_config('fieldmapping_jobassignmentfullname', 'totara_sync_source_user_database');
        unset_config('fieldmapping_jobassignmentidnumber', 'totara_sync_source_user_database');
        unset_config('fieldmapping_jobassignmentstartdate', 'totara_sync_source_user_database');
        unset_config('fieldmapping_manageridnumber', 'totara_sync_source_user_database');
        unset_config('fieldmapping_managerjobassignmentidnumber', 'totara_sync_source_user_database');
        unset_config('fieldmapping_orgidnumber', 'totara_sync_source_user_database');
        unset_config('fieldmapping_posidnumber', 'totara_sync_source_user_database');
        unset_config('fieldmapping_appraiseridnumber', 'totara_sync_source_user_database');

        unset_config('import_jobassignmentenddate', 'totara_sync_source_user_database');
        unset_config('import_jobassignmentfullname', 'totara_sync_source_user_database');
        unset_config('import_jobassignmentidnumber', 'totara_sync_source_user_database');
        unset_config('import_jobassignmentstartdate', 'totara_sync_source_user_database');
        unset_config('import_manageridnumber', 'totara_sync_source_user_database');
        unset_config('import_managerjobassignmentidnumber', 'totara_sync_source_user_database');
        unset_config('import_orgidnumber', 'totara_sync_source_user_database');
        unset_config('import_posidnumber', 'totara_sync_source_user_database');
        unset_config('import_appraiseridnumber', 'totara_sync_source_user_database');

        upgrade_plugin_savepoint(true, 2017090500, 'tool', 'totara_sync');
    }


    if ($oldversion < 2017102701) {

        // Get all current user profile fields to check against.
        $profilefields = $DB->get_records_menu('user_info_field', array(), '', 'id, shortname');

        // Common like SQL.
        $namelikesql = $DB->sql_like('name', ':name');
        $pluginlikesql = $DB->sql_like('plugin', ':plugin');

        // Get all import_customfield_* entries in the config plugins table.
        $sql = "SELECT * FROM {config_plugins} WHERE $pluginlikesql AND $namelikesql";
        $params = array('plugin' => 'totara_sync_source_user_%', 'name' => 'import_customfield_%');
        $import_records = $DB->get_records_sql($sql, $params);

        $invalid = array();
        foreach ($import_records as $record) {
            $shortname = substr($record->name, 19); // Trim import_customfield_
            if (!in_array($shortname, $profilefields)) {
                $invalid[] = $record;
            }
        }

        // We also have to deal with mapping fields.
        // fieldmapping_customfield_*
        $sql = "SELECT * FROM {config_plugins} WHERE $pluginlikesql AND $namelikesql";
        $params = array('plugin' => 'totara_sync_source_user_%', 'name' => 'fieldmapping_customfield_%');

        // Get records
        $fieldmapping_records = $DB->get_records_sql($sql, $params);
        foreach ($fieldmapping_records as $record) {
            $shortname = substr($record->name, 25); // Trim fieldmapping_customfield_
            if (!in_array($shortname, $profilefields)) {
                $invalid[] = $record;
            }
        }

        // Remove invalid records and we can't do any updating reliably,
        // these records are orphaned settings and might cause issues.
        foreach ($invalid as $setting) {
            unset_config($setting->name, $setting->plugin);
        }

        upgrade_plugin_savepoint(true, 2017102701, 'tool', 'totara_sync');
    }

    if ($oldversion < 2018082200) {
        // For custom field import settings, create new settings with the format:
        // fieldmapping_customfield_{typeid}_{shortname} or
        // import_customfield_{typeid}_{shortname}

        // We can continue to use this SQL to fetch the config settings we need.
        $sql = 'SELECT * 
                  FROM {config_plugins}
                 WHERE ' . $DB->sql_like('plugin', ':plugin') . '
                   AND ' . $DB->sql_like('name', ':name');

        // Sort position custom fields by shortname to typeids. This prevents a performance blowout
        // by either looping through or querying the database each time later.
        $poscustomfields = $DB->get_records('pos_type_info_field');
        $posbyshortname = [];
        foreach ($poscustomfields as $poscustomfield) {
            if ($poscustomfield->datatype === 'file') {
                continue;
            }

            if (!isset($posbyshortname[$poscustomfield->shortname])) {
                $posbyshortname[$poscustomfield->shortname] = [];
            }

            $posbyshortname[$poscustomfield->shortname][] = $poscustomfield->typeid;
        }


        // Pos import_ settings

        $records = $DB->get_records_sql(
            $sql,
            ['plugin' => 'totara_sync_source_pos_%', 'name' => 'import_customfield_%']
        );
        foreach ($records as $record) {
            $pieces = explode('_', $record->name);
            if (count($pieces) !== 3) {
                // Prior to this upgrade step, we expect settings to be like import_customfield_{shortname} = 3 pieces.
                // If we're here, this seems to be a setting that has already been upgraded, or otherwise
                // is not what we expect. Avoid doing anything with it.
                continue;
            }
            $shortname = $pieces[2];
            if (isset($posbyshortname[$shortname]) && is_array($posbyshortname[$shortname])) {
                foreach ($posbyshortname[$shortname] as $typeid) {
                    $newsettingname = 'import_customfield_' . $typeid . '_' . $shortname;
                    set_config($newsettingname, $record->value, $record->plugin);
                }
            }
            unset_config($record->name, $record->plugin);
        }
        unset($newsettingname);
        unset($shortname);
        unset($records);


        // Pos fieldmapping_ settings

        $records = $DB->get_records_sql(
            $sql,
            ['plugin' => 'totara_sync_source_pos_%', 'name' => 'fieldmapping_customfield_%']
        );
        foreach ($records as $record) {
            $pieces = explode('_', $record->name);
            if (count($pieces) !== 3) {
                // Prior to this upgrade step, we expect settings to be like fieldmapping_customfield_{shortname} = 3 pieces.
                // If we're here, this seems to be a setting that has already been upgraded, or otherwise
                // is not what we expect. Avoid doing anything with it.
                continue;
            }
            $shortname = $pieces[2];
            if (isset($posbyshortname[$shortname]) && is_array($posbyshortname[$shortname])) {
                foreach ($posbyshortname[$shortname] as $typeid) {
                    $newsettingname = 'fieldmapping_customfield_' . $typeid . '_' . $shortname;
                    set_config($newsettingname, $record->value, $record->plugin);
                }
            }
            unset_config($record->name, $record->plugin);
        }
        unset($newsettingname);
        unset($shortname);
        unset($records);

        // We're unsetting once they're not needed to prevent spillover if the wrong variable
        // is used later.
        unset($poscustomfields);
        unset($posbyshortname);

        $orgcustomfields = $DB->get_records('org_type_info_field');
        $orgbyshortname = [];
        foreach ($orgcustomfields as $orgcustomfield) {
            if ($orgcustomfield->datatype === 'file') {
                continue;
            }

            if (!isset($orgbyshortname[$orgcustomfield->shortname])) {
                $orgbyshortname[$orgcustomfield->shortname] = [];
            }

            $orgbyshortname[$orgcustomfield->shortname][] = $orgcustomfield->typeid;
        }


        // Org import_ settings

        $records = $DB->get_records_sql(
            $sql,
            ['plugin' => 'totara_sync_source_org_%', 'name' => 'import_customfield_%']
        );
        foreach ($records as $record) {
            $pieces = explode('_', $record->name);
            if (count($pieces) !== 3) {
                // Prior to this upgrade step, we expect settings to be like import_customfield_{shortname} = 3 pieces.
                // If we're here, this seems to be a setting that has already been upgraded, or otherwise
                // is not what we expect. Avoid doing anything with it.
                continue;
            }
            $shortname = $pieces[2];
            if (isset($orgbyshortname[$shortname]) && is_array($orgbyshortname[$shortname])) {
                foreach ($orgbyshortname[$shortname] as $typeid) {
                    $newsettingname = 'import_customfield_' . $typeid . '_' . $shortname;
                    set_config($newsettingname, $record->value, $record->plugin);
                }
            }
            unset_config($record->name, $record->plugin);
        }
        unset($newsettingname);
        unset($shortname);
        unset($records);


        // Org fieldmapping_ settings

        $records = $DB->get_records_sql(
            $sql,
            ['plugin' => 'totara_sync_source_org_%', 'name' => 'fieldmapping_customfield_%']
        );
        foreach ($records as $record) {
            $pieces = explode('_', $record->name);
            if (count($pieces) !== 3) {
                // Prior to this upgrade step, we expect settings to be like fieldmapping_customfield_{shortname} = 3 pieces.
                // If we're here, this seems to be a setting that has already been upgraded, or otherwise
                // is not what we expect. Avoid doing anything with it.
                continue;
            }
            $shortname = $pieces[2];
            if (isset($orgbyshortname[$shortname]) && is_array($orgbyshortname[$shortname])) {
                foreach ($orgbyshortname[$shortname] as $typeid) {
                    $newsettingname = 'fieldmapping_customfield_' . $typeid . '_' . $shortname;
                    set_config($newsettingname, $record->value, $record->plugin);
                }
            }
            unset_config($record->name, $record->plugin);
        }
        unset($newsettingname);
        unset($shortname);
        unset($records);

        upgrade_plugin_savepoint(true, 2018082200, 'tool', 'totara_sync');
    }

    return true;
}
