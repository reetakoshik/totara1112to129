<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @package totara_reportbuilder
 */

/**
 * Rename reportbuilder columns. Using the $type param to constrain the renaming to a single
 * type is recommended to avoid renaming columns unintentionally.
 *
 * @param array $values     An array with data formatted like array($oldname => $newname)
 * @param string $type      The type constraint, e.g. 'user'
 */
function totara_reportbuilder_migrate_column_names($values, $type = '') {
    global $DB;

    $typesql = '';
    $params = array();
    if (!empty($type)) {
        $typesql = ' AND type = :type';
        $params['type'] = $type;
    }

    foreach ($values as $oldname => $newname) {
        $sql = "UPDATE {report_builder_columns}
                   SET value = :newname
                 WHERE value = :oldname
                       {$typesql}";
        $params['newname'] = $newname;
        $params['oldname'] = $oldname;

        $DB->execute($sql, $params);
    }

    return true;
}

/**
 * Map old position columns to the new job_assignment columns.
 *
 * @param array $values     An array of the values we are updating the type of
 * @param string $oldtype   The oldtype
 * @param string $newtype
 */
function totara_reportbuilder_migrate_column_types($values, $oldtype, $newtype) {
    global $DB;

    // If there is nothing to migrate just return.
    if (empty($values)) {
        return true;
    }

    list($insql, $params) = $DB->get_in_or_equal($values, SQL_PARAMS_NAMED);
    $sql = "UPDATE {report_builder_columns}
               SET type = :newtype
             WHERE type = :oldtype
               AND value {$insql}";
    $params['newtype'] = $newtype;
    $params['oldtype'] = $oldtype;

    return $DB->execute($sql, $params);
}

/**
 * Rename reportbuilder filters. Using the $type param to constrain the renaming to a single
 * type is recommended to avoid renaming filters unintentionally.
 *
 * @param array $values     An array with data formatted like array($oldname => $newname)
 * @param string $type      The type constraint, e.g. 'user'
 */
function totara_reportbuilder_migrate_filter_names($values, $type = '') {
    global $DB;

    // If there is nothing to migrate just return.
    if (empty($values)) {
        return true;
    }

    $typesql = '';
    $params = array();
    if (!empty($type)) {
        $typesql = 'AND type = :type';
        $params['type'] = $type;
    }

    foreach ($values as $oldname => $newname) {
        $sql = "UPDATE {report_builder_filters}
                   SET value = :newname
                 WHERE value = :oldname
                       {$typesql}";
        $params['newname'] = $newname;
        $params['oldname'] = $oldname;

        $DB->execute($sql, $params);
    }

    return true;
}

/**
 * Map old position filters to the new job_assignment columns.
 */
function totara_reportbuilder_migrate_filter_types($values, $oldtype, $newtype) {
    global $DB;

    // If there is nothing to migrate just return.
    if (empty($values)) {
        return true;
    }

    list($insql, $params) = $DB->get_in_or_equal($values, SQL_PARAMS_NAMED);
    $sql = "UPDATE {report_builder_filters}
               SET type = :newtype
             WHERE type = :oldtype
               AND value {$insql}";
    $params['newtype'] = $newtype;
    $params['oldtype'] = $oldtype;

    return $DB->execute($sql, $params);
}

/**
 * Update the filters in any saved searches, generally used after migrating filter types.
 *
 * NOTE: This is a generic function suitable for general use
 * when migrating saved search data for any filter. This should
 * be used instead of {@link totara_reportbuilder_migrate_saved_search_filters()} which was specific to the 2.9 -> 9.0
 * multiple jobs migration.
 *
 * @param string $source Name of the source or '*' to update all sources
 * @param string $oldtype The type of the item to change
 * @param string $oldvalue The value of the item to change
 * @param string $newtype The new type of the item
 * @param string $newvalue The new value of the item
 * @return boolean True if data was updated, false otherwise.
 *
 */
function totara_reportbuilder_migrate_saved_searches($source, $oldtype, $oldvalue, $newtype, $newvalue) {
    global $DB;

    $savedsearchesupdated = false;

    if ($source == '*') {
        $sourcesql = '';
        $params = array();
    } else {
        $sourcesql = ' WHERE rb.source = :source';
        $params = array('source' => $source);
    }

    // Get all saved searches for specified source.
    $sql = "SELECT rbs.* FROM {report_builder_saved} rbs
        JOIN {report_builder} rb
        ON rb.id = rbs.reportid
        {$sourcesql}";
    $savedsearches = $DB->get_records_sql($sql, $params);

    // Loop through them all and json_decode
    foreach ($savedsearches as $saved) {
        if (empty($saved->search)) {
            continue;
        }

        $search = unserialize($saved->search);

        if (!is_array($search)) {
            continue;
        }

        // Check for any filters that will need to be updated.
        $update = false;
        foreach ($search as $oldkey => $info) {
            list($type, $value) = explode('-', $oldkey);

            if ($type == $oldtype && $value == $oldvalue) {
                $update = true;

                $newkey = "{$newtype}-{$newvalue}";
                $search[$newkey] = $info;
                unset($search[$oldkey]);
            }
        }

        if ($update) {
            // Re encode and update the database.
            $todb = new \stdClass;
            $todb->id = $saved->id;
            $todb->search = serialize($search);
            $DB->update_record('report_builder_saved', $todb);
            $savedsearchesupdated = true;
        }
    }

    return $savedsearchesupdated;
}

/**
 * Update the filters in any saved searches, generally used after migrating filter types.
 *
 * NOTE: this function contains code specific to the migration
 * from 2.9 to 9.0 for multiple jobs. DO NOT USE this function
 * for generic saved search migrations, use
 * {@link totara_reportbuilder_migrate_saved_searches()} instead.
 */
function totara_reportbuilder_migrate_saved_search_filters($values, $oldtype, $newtype) {
    global $DB;

    // If there is nothing to migrate just return.
    if (empty($values)) {
        return true;
    }

    // Get all saved searches.
    $savedsearches = $DB->get_records('report_builder_saved');

    // Loop through them all and json_decode
    foreach ($savedsearches as $saved) {
        if (empty($saved)) {
            continue;
        }

        $search = unserialize($saved->search);

        if (!is_array($search)) {
            continue;
        }

        // Check for any filters that will need to be updated.
        $update = false;
        foreach ($search as $oldkey => $info) {
            list($type, $value) = explode('-', $oldkey);

            // NOTE: This isn't quite as generic as the other functions.
            $value = $value == 'posstartdate' ? 'startdate' : $value;
            $value = $value == 'posenddate' ? 'enddate' : $value;

            if ($type == $oldtype && in_array($value, array_keys($values))) {
                $update = true;

                if ($values[$value] == 'allpositions' || $values[$value] == 'allorganisations') {
                    if (isset($info['recursive']) && !isset($info['children'])) {
                        $info['children'] = $info['recursive'];
                        unset($info['recursive']);
                    } else {
                        $info['children'] = isset($info['children']) ? $info['children'] : 0;
                    }
                    $info['operator'] = isset($info['operator']) ? $info['operator'] : 1;
                }

                $newkey = "{$newtype}-{$values[$value]}";
                $search[$newkey] = $info;
                unset($search[$oldkey]);
            }
        }

        if ($update) {
            // Re encode and update the database.
            $saved->search = serialize($search);
            $DB->update_record('report_builder_saved', $saved);
        }
    }

    return true;
}

/**
 * Map reports default sort columns the to new job_assignment columns.
 */
function totara_reportbuilder_migrate_default_sort_columns($values, $oldtype, $newtype) {
    global $DB;

    // If there is nothing to migrate just return.
    if (empty($values)) {
        return true;
    }

    foreach ($values as $sort) {
        $sql = "UPDATE {report_builder}
                   SET defaultsortcolumn = :newsort
                 WHERE defaultsortcolumn = :oldsort";
        $params = array(
            'oldsort' => $oldtype . '_' . $sort,
            'newsort' => $newtype . '_' . $sort
        );

        $DB->execute($sql, $params);
    }

    return true;
}

/**
 * Scheduled reports belonging to a user are now deleted when the user gets deleted
 */
function totara_reportbuilder_delete_scheduled_reports() {
    global $DB;

    // Get the reports created by deleted user/s.
    $sql = "SELECT rbs.id
                  FROM {report_builder_schedule} rbs
                  JOIN {user} u ON u.id = rbs.userid
                 WHERE u.deleted = 1";
    $reports = $DB->get_records_sql($sql);
    // Delete all scheduled reports created by deleted user/s.
    foreach ($reports as $report) {
        $DB->delete_records('report_builder_schedule_email_audience',   array('scheduleid' => $report->id));
        $DB->delete_records('report_builder_schedule_email_systemuser', array('scheduleid' => $report->id));
        $DB->delete_records('report_builder_schedule_email_external',   array('scheduleid' => $report->id));
        $DB->delete_records('report_builder_schedule', array('id' => $report->id));
    }

    // Get deleted user/s.
    $sql = "SELECT DISTINCT rbses.userid
                  FROM {report_builder_schedule_email_systemuser} rbses
                  JOIN {user} u ON u.id = rbses.userid
                 WHERE u.deleted = 1";
    $reports = $DB->get_fieldset_sql($sql);
    if ($reports) {
        list($sqlin, $sqlparm) = $DB->get_in_or_equal($reports);
        // Remove deleted user/s from scheduled reports.
        $DB->execute("DELETE FROM {report_builder_schedule_email_systemuser} WHERE userid $sqlin", $sqlparm);
    }

    // Get deleted audience/s.
    $sql = "SELECT DISTINCT rbsea.cohortid
                  FROM {report_builder_schedule_email_audience} rbsea
                 WHERE NOT EXISTS (
                           SELECT 1 FROM {cohort} ch WHERE rbsea.cohortid = ch.id
               )";
    $cohorts = $DB->get_fieldset_sql($sql);
    if ($cohorts) {
        list($sqlin, $sqlparm) = $DB->get_in_or_equal($cohorts);
        // Remove deleted audience/s from scheduled reports.
        $DB->execute("DELETE FROM {report_builder_schedule_email_audience} WHERE cohortid $sqlin", $sqlparm);
    }

    return true;
}

/**
 * Populate the "usermodified" column introduced with the new scheduled report
 * report source implementation.
 */
function totara_reportbuilder_populate_scheduled_reports_usermodified() {
    global $DB;

    $table = 'report_builder_schedule';
    $records = $DB->get_records($table, null, '', 'id,userid,usermodified');
    foreach ($records as $record) {
        $record->usermodified = $record->userid;
        $DB->update_record($table, $record);
    }
}

