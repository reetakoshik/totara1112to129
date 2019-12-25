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

class totara_sync_source_jobassignment_csv extends totara_sync_source_jobassignment {
    use \tool_totara_sync\internal\source\csv_trait;

    public function get_filepath() {
        $path = '/csv/ready/jobassignment.csv';
        $pathos = $this->get_canonical_filesdir($path);
        return $pathos;
    }

    public function config_form(&$mform) {
        $this->config_form_add_csv_details($mform);
        parent::config_form($mform);
    }

    public function config_save($data) {
        $this->config_save_csv_file_details($data);
        parent::config_save($data);
    }

    public function import_data($temptable) {
        global $CFG, $DB;

        $file = $this->open_csv_file();

        // Map CSV fields.
        $fields = fgetcsv($file, 0, $this->config->delimiter);
        $fieldmappings = array();
        foreach ($this->fields as $f) {
            if (!$this->is_importing_field($f)) {
                continue;
            }
            if (!empty($this->config->{'fieldmapping_'.$f})) {
                $fieldmappings[$this->config->{'fieldmapping_'.$f}] = $f;
            }
        }

        // Throw an exception if fields contain invalid characters.
        foreach ($fields as $field) {
            $invalidchars = preg_replace('/[ ?!A-Za-z0-9_-]/i', '', $field);
            if (strlen($invalidchars)) {
                $errorvar = new stdClass();
                $errorvar->invalidchars = $invalidchars[0];
                $errorvar->delimiter = $this->config->delimiter;
                throw new totara_sync_exception($this->get_element_name(), 'mapfields', 'csvnotvalidinvalidchars', $errorvar);
            }
        }

        // Ensure necessary mapped fields are present in the CSV.
        foreach ($fieldmappings as $m => $f) {
            if (!in_array($m, $fields)) {
                if ($f == $m) {
                    throw new totara_sync_exception($this->get_element_name(), 'mapfields', 'csvnotvalidmissingfieldx', $f);
                } else {
                    throw new totara_sync_exception($this->get_element_name(), 'mapfields', 'csvnotvalidmissingfieldxmappingx', (object)array('field' => $f, 'mapping' => $m));
                }
            }
        }
        // Finally, perform CSV to db field mapping.
        foreach ($fields as $i => $f) {
            if (in_array($f, array_keys($fieldmappings))) {
                $fields[$i] = $fieldmappings[$f];
            }
        }

        // Check field integrity for general fields.
        foreach ($this->fields as $f) {
            if (!$this->is_importing_field($f) || in_array($f, $fieldmappings)) {
                // Disabled or mapped fields can be ignored.
                continue;
            }
            if (!in_array($f, $fields)) {
                throw new totara_sync_exception($this->get_element_name(), 'importdata', 'csvnotvalidmissingfieldx', $f);
            }
        }

        // Populate temp sync table from CSV.
        $datarows = array();    // Holds csv row data.
        $rowcount = 0;
        $fieldcount = new stdClass();
        $fieldcount->headercount = count($fields);
        $fieldcount->rownum = 0;
        $csvdateformat = (isset($CFG->csvdateformat)) ? $CFG->csvdateformat : get_string('csvdateformatdefault', 'totara_core');
        $element = new totara_sync_element_jobassignment();
        $saveemptyfields = !empty($element->config->csvsaveemptyfields);
        $notnullfields = array('idnumber' => 1, 'useridnumber' => 1, 'timemodified' => 1, 'deleted' => 1);

        $temptable_columns = $DB->get_columns($temptable);
        while ($csvrow = fgetcsv($file, 0, $this->config->delimiter)) {
            $fieldcount->rownum++;
            // Skip empty rows
            if (is_array($csvrow) && current($csvrow) === null) {
                $fieldcount->fieldcount = 0;
                $fieldcount->delimiter = $this->config->delimiter;
                $this->addlog(get_string('fieldcountmismatch', 'tool_totara_sync', $fieldcount), 'error', 'populatesynctablecsv');
                unset($fieldcount->delimiter);
                continue;
            }
            $fieldcount->fieldcount = count($csvrow);
            if ($fieldcount->fieldcount !== $fieldcount->headercount) {
                $fieldcount->delimiter = $this->config->delimiter;
                $this->addlog(get_string('fieldcountmismatch', 'tool_totara_sync', $fieldcount), 'error', 'populatesynctablecsv');
                unset($fieldcount->delimiter);
                continue;
            }

            $csvrow = array_combine($fields, $csvrow);
            $csvrow = $this->clean_fields($csvrow);

            // Set up a db row.
            $dbrow = array();

            // General fields.
            foreach ($this->fields as $f) {
                if ($this->is_importing_field($f)) {
                    if (!$saveemptyfields and ($csvrow[$f] === '') and !isset($notnullfields[$f])) {
                        $dbrow[$f] = null;
                    } else {
                        $dbrow[$f] = $csvrow[$f];
                    }
                }
            }

            if (empty($csvrow['timemodified'])) {
                $dbrow['timemodified'] = 0;
            } else {
                // Try to parse the contents - if parse fails assume a unix timestamp and leave unchanged.
                $parsed_date = totara_date_parse_from_format($csvdateformat, trim($csvrow['timemodified']), true);
                if ($parsed_date) {
                    $dbrow['timemodified'] = $parsed_date;
                }
            }

            $datefields = array('startdate', 'enddate');
            foreach ($datefields as $datefield) {
                if (!empty($csvrow[$datefield])) {
                    // Try to parse the contents - if parse fails assume a unix timestamp and leave unchanged.
                    $parsed_date = totara_date_parse_from_format($csvdateformat, trim($csvrow[$datefield]), true);
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
                        totara_sync_log($this->get_element_name(), $msg, 'warn', 'updatejobassignments', false);

                        // Set date to null. We don't want to unset as this will stop the Assignment being added.
                        $dbrow[$datefield] = null;
                    }
                }
            }

            $datarows[] = $dbrow;
            $rowcount++;

            if ($rowcount >= TOTARA_SYNC_DBROWS) {
                $this->check_length_limit($datarows, $temptable_columns, $fieldmappings, $this->get_element_name());
                // Bulk insert.
                try {
                    totara_sync_bulk_insert($temptable, $datarows);
                } catch (dml_exception $e) {
                    error_log($e->getMessage()."\n".$e->debuginfo);
                    throw new totara_sync_exception($this->get_element_name(), 'populatesynctablecsv', 'couldnotimportallrecords', $e->getMessage());
                }

                $rowcount = 0;
                unset($datarows);
                $datarows = array();

                gc_collect_cycles();
            }
        }  // while

        $this->check_length_limit($datarows, $temptable_columns, $fieldmappings, $this->get_element_name());
        // Insert remaining rows.
        try {
            totara_sync_bulk_insert($temptable, $datarows);
        } catch (dml_exception $e) {
            error_log($e->getMessage()."\n".$e->debuginfo);
            throw new totara_sync_exception($this->get_element_name(), 'populatesynctablecsv', 'couldnotimportallrecords', $e->getMessage());
        }
        unset($fieldmappings);

        $this->close_csv_file($file);

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
        return $this->get_common_csv_notifications();
    }

    /**
     * Cleans values for import. Excludes custom fields, which should not be part of the input array.
     *
     * @param string[] $row with field name as key (after mapping) and value provided for the given field.
     * @return string[] Same structure as input but with cleaned values.
     */
    private function clean_fields($row) {
        $cleaned = [];
        foreach($row as $key => $value) {
            switch($key) {
                case 'idnumber':
                case 'useridnumber':
                case 'timemodified':
                case 'fullname':
                case 'startdate':
                case 'enddate':
                case 'orgidnumber':
                case 'posidnumber':
                case 'manageridnumber':
                case 'appraiseridnumber':
                case 'managerjobassignmentidnumber':
                    $cleaned[$key] = clean_param(trim($value), PARAM_TEXT);
                    break;
                case 'deleted':
                    $cleaned[$key] = clean_param(trim($value), PARAM_INT);
                    break;
                default:
                    // This is not an available field to be synced, don't include.
            }
        }

        return $cleaned;
    }
}
