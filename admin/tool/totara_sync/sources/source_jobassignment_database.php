<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package tool_totara_sync
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/admin/tool/totara_sync/sources/classes/source.jobassignment.class.php');
require_once($CFG->dirroot.'/admin/tool/totara_sync/lib.php');
require_once($CFG->dirroot.'/admin/tool/totara_sync/sources/databaselib.php');

class totara_sync_source_jobassignment_database extends totara_sync_source_jobassignment {
    use \tool_totara_sync\internal\source\database_trait;

    public function config_form(&$mform) {
        $this->config_form_add_database_details($mform);
        parent::config_form($mform);
    }

    public function config_save($data) {
        $this->config_save_database_details($data);
        parent::config_save($data);
    }

    /**
     * @return bool False as database sources do not use files.
     */
    public function uses_files() {
        return false;
    }

    public function import_data($temptable) {
        global $DB;

        // Get database config.
        $dbtype = $this->config->{'database_dbtype'};
        $dbname = $this->config->{'database_dbname'};
        $dbhost = $this->config->{'database_dbhost'};
        $dbuser = $this->config->{'database_dbuser'};
        $dbpass = $this->config->{'database_dbpass'};
        $dbport = $this->config->{'database_dbport'};
        $db_table = $this->config->{'database_dbtable'};

        try {
            $database_connection = setup_sync_DB($dbtype, $dbhost, $dbname, $dbuser, $dbpass, array('dbport' => $dbport));
        } catch (Exception $e) {
            $this->addlog(get_string('databaseconnectfail', 'tool_totara_sync'), 'error', 'importdata');
            return false;
        }

        // Get list of fields to be imported.
        $fields = array();
        foreach ($this->fields as $f) {
            if (!empty($this->config->{'import_'.$f})) {
                $fields[] = $f;
            }
        }

        // Sort out field mappings.
        $fieldmappings = array();
        foreach ($fields as $i => $f) {
            if (empty($this->config->{'fieldmapping_'.$f})) {
                $fieldmappings[$f] = $f;
            } else {
                $fieldmappings[$f] = $this->config->{'fieldmapping_'.$f};
            }
        }

        // Finally, perform externaldb to totara db field mapping.
        foreach ($fields as $i => $f) {
            if (in_array($f, array_keys($fieldmappings))) {
                $fields[$i] = $fieldmappings[$f];
            }
        }

        // Check that all fields exists in database.
        foreach ($fields as $field) {
            try {
                $database_connection->get_field_sql("SELECT $field from $db_table", array(), IGNORE_MULTIPLE);
            } catch (Exception $e) {
                $this->addlog(get_string('dbmissingcolumnx', 'tool_totara_sync', $field), 'error', 'importdata');
                return false;
            }
        }

        unset($fieldmappings);

        ///
        /// Populate temp sync table from remote database.
        ///
        $datarows = array();
        $rowcount = 0;

        $columns = implode(', ', $fields);
        $fetch_sql = 'SELECT ' . $columns . ' FROM ' . $db_table;
        $data = $database_connection->get_recordset_sql($fetch_sql);

        foreach ($data as $row) {
            // Setup a db row
            $extdbrow = array_combine($fields, (array)$row);
            $dbrow = array();

            foreach ($this->fields as $field) {
                if (!empty($this->config->{'import_'.$field})) {
                    if (!empty($this->config->{'fieldmapping_'.$field})) {
                        $dbrow[$field] = $extdbrow[$this->config->{'fieldmapping_'.$field}];
                    } else {
                        $dbrow[$field] = $extdbrow[$field];
                    }
                }
            }

            // Treat nulls in the 'deleted' database column as not deleted.
            if (!empty($this->config->import_deleted)) {
                $dbrow['deleted'] = empty($dbrow['deleted']) ? 0 : $dbrow['deleted'];
            }

            // Optional date fields.
            $datefields = array('startdate', 'enddate');
            $database_dateformat = get_config('totara_sync_source_jobassignment_database', 'database_dateformat');
            foreach ($datefields as $datefield) {
                if (!empty($extdbrow[$datefield])) {
                    // Try to parse the contents - if parse fails assume a unix timestamp and leave unchanged.
                    $parsed_date = totara_date_parse_from_format($database_dateformat, trim($extdbrow[$datefield]), true);
                    if ($parsed_date) {
                        $dbrow[$datefield] = $parsed_date;
                    } elseif (!is_numeric($dbrow[$datefield])) {
                        // Bad date format.
                        if (empty($dbrow['idnumber']) or empty($dbrow['useridnumber'])) {
                            $msg = get_string('invaliddateformatforfield', 'tool_totara_sync', $datefield);
                        } else {
                            $msg = get_string('invaliddateformatjobassignment', 'tool_totara_sync',
                                array('field' => $datefield, 'idnumber' => $dbrow['idnumber'], 'useridnumber' => $dbrow['useridnumber']));
                        }
                        totara_sync_log($this->get_element_name(), $msg, 'warn', 'populatesynctabledb', false);

                        // Set date to null. We don't want to unset as this will stop the Assignment being added.
                        $dbrow[$datefield] = null;
                    }
                }
            }

            if (empty($extdbrow['timemodified'])) {
                $dbrow['timemodified'] = 0;
            } else {
                // Try to parse the contents - if parse fails assume a unix timestamp and leave unchanged.
                $parsed_date = totara_date_parse_from_format($database_dateformat, trim($extdbrow['timemodified']), true);
                if ($parsed_date) {
                    $dbrow['timemodified'] = $parsed_date;
                }
            }

            $datarows[] = $dbrow;
            $rowcount++;

            if ($rowcount >= TOTARA_SYNC_DBROWS) {
                // Bulk insert.
                if (!totara_sync_bulk_insert($temptable, $datarows)) {
                    $this->addlog(get_string('couldnotimportallrecords', 'tool_totara_sync'), 'error', 'populatesynctabledb');
                    return false;
                }

                $rowcount = 0;
                unset($datarows);
                $datarows = array();

                gc_collect_cycles();
            }
        }

        // Insert remaining rows.
        if (!totara_sync_bulk_insert($temptable, $datarows)) {
            $this->addlog(get_string('couldnotimportallrecords', 'tool_totara_sync'), 'error', 'populatesynctabledb');
            return false;
        }

        // Update temporary table stats once import is done.
        $DB->update_temp_table_stats();

        return true;
    }

    /**
     * Get any notifications that should be displayed for the element source.
     *
     * @return string Notifications HTML.
     */
    public function get_notifications() {
        return $this->get_common_db_notifications();
    }

}