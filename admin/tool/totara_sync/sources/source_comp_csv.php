<?php
/*
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

require_once($CFG->dirroot.'/admin/tool/totara_sync/sources/classes/source.comp.class.php');
require_once($CFG->dirroot.'/admin/tool/totara_sync/lib.php');

/**
 * Class totara_sync_source_comp_csv
 *
 * Manages importing of data from a csv file in order to create competencies.
 *
 * This will handle saving the data to a temporary table. Updating of competencies is done by the competency element.
 */
class totara_sync_source_comp_csv extends totara_sync_source_comp {
    use \tool_totara_sync\internal\source\csv_trait;

    /**
     * Get the path to the csv file that has been uploaded for import.
     *
     * @return string
     */
    public function get_filepath() {
        $path = '/csv/ready/comp.csv';
        $pathos = $this->get_canonical_filesdir($path);
        return $pathos;
    }

    /**
     * Add form elements specific to competencies.
     *
     * @param $mform
     */
    public function config_form(&$mform) {

        $this->config->import_idnumber = "1";
        $this->config->import_fullname = "1";
        $this->config->import_frameworkidnumber = "1";
        $this->config->import_timemodified = "1";
        $this->config->import_deleted = empty($this->element->config->sourceallrecords) ? "1" : "0";

        $this->config_form_add_csv_details($mform);
        parent::config_form($mform);
    }

    /**
     * Save data from the form elements added by config_form.
     *
     * @param $data
     */
    public function config_save($data) {
        $this->config_save_csv_file_details($data);
        parent::config_save($data);
    }

    /**
     * Import data from the csv file and store in the temporary table.
     *
     * @param $temptable
     * @return bool
     */
    public function import_data($temptable) {
        global $DB;

        $file = $this->open_csv_file();

        // Map CSV fields.
        $fields = fgetcsv($file, 0, $this->config->delimiter);
        $fieldmappings = [];
        foreach ($this->fields as $f) {
            if (empty($this->config->{'import_'.$f})) {
                continue;
            }
            if (empty($this->config->{'fieldmapping_'.$f})) {
                $fieldmappings[$f] = $f;
            } else {
                $fieldmappings[$this->config->{'fieldmapping_'.$f}] = $f;
            }
        }

        $customfields = $this->get_mapped_customfields();

        // Check field integrity for custom fields.
        if ($missingcustomfields = array_diff($customfields, $fields)) {
            foreach($missingcustomfields as $missingcustomfield) {
                // This will stop iterating on the first one,
                // but it's a start if we want to log all missing fields in the future.
                throw new \totara_sync_exception($this->get_element_name(), 'importdata', 'csvnotvalidmissingfieldx', $missingcustomfield);
            }
        }

        $fieldmappings = array_merge($fieldmappings, $customfields);

        // Throw an exception if fields contain invalid characters
        foreach ($fields as $field) {
            $invalidchars = preg_replace('/[ ?!A-Za-z0-9_-]/i', '', $field);
            if (strlen($invalidchars)) {
                $errorvar = new stdClass();
                $errorvar->invalidchars = $invalidchars[0];
                $errorvar->delimiter = $this->config->delimiter;
                throw new totara_sync_exception($this->get_element_name(), 'mapfields', 'csvnotvalidinvalidchars', $errorvar);
            }
        }

        // Ensure necessary fields are present
        foreach ($fieldmappings as $field => $mapping) {
            if (!in_array($field, $fields) && !in_array($mapping, $customfields)) {
                // typeidnumber field can be optional if no custom fields specified
                if (($field == 'typeidnumber') && empty($customfields)) {
                    continue;
                }

                if ($field == $mapping) {
                    throw new totara_sync_exception(
                        $this->get_element_name(),
                        'mapfields',
                        'csvnotvalidmissingfieldx',
                        $field
                    );
                } else {
                    throw new totara_sync_exception(
                        $this->get_element_name(),
                        'mapfields',
                        'csvnotvalidmissingfieldxmappingx',
                        (object)['field' => $field, 'mapping' => $mapping]
                    );
                }
            }
        }
        // Finally, perform CSV to db field mapping
        foreach ($fields as $index => $field) {
            if (in_array($field, array_keys($fieldmappings))) {
                $fields[$index] = $fieldmappings[$field];
            }
        }

        // Populate temp sync table from CSV
        $now = time();
        $datarows = [];    // holds csv row data
        $dbpersist = TOTARA_SYNC_DBROWS;  // # of rows to insert into db at a time
        $rowcount = 0;
        $fieldcount = new stdClass();
        $fieldcount->headercount = count($fields);
        $fieldcount->rownum = 0;

        // Convert setting into a boolean.
        $csvsaveemptyfields = isset($this->element->config->csvsaveemptyfields) && $this->element->config->csvsaveemptyfields == 1;

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
            $csvrow = array_combine($fields, $csvrow);  // nice associative array

            // Set up a db row
            $row = [];

            // General fields
            foreach ($this->fields as $field) {
                if (!empty($this->config->{'import_'.$field})) {
                    $row[$field] = $csvrow[$field];
                }
            }

            $row = $this->clean_fields($row);

            // Empty string from file
            if (isset($row['parentidnumber']) && $row['parentidnumber'] === '') {
                if ($csvsaveemptyfields) {
                    // Saving empty fields (erase data).
                    $row['parentidnumber'] = '';
                } else {
                    // Not saving (set to null and the element will get the existing value).
                    $row['parentidnumber'] = null;
                }
            }

            if (isset($row['frameworkidnumber']) && $row['frameworkidnumber'] === '') {
                if ($csvsaveemptyfields) {
                    // Saving empty fields,
                    // We cannot have an empty framework id number but will be stopped in the element.
                    $row['frameworkidnumber'] = '';
                } else{
                    $row['frameworkidnumber'] = null;
                }
            }

            if ($this->config->{'import_typeidnumber'} == '0') {
                unset($row['typeidnumber']);
            } else {
                $row['typeidnumber'] = !empty($row['typeidnumber']) ? $row['typeidnumber'] : '';
            }

            if (empty($row['timemodified'])) {
                $row['timemodified'] = $now; // This should probably be 0, but it causes repeated sync_item calls to parents.
            } else {
                // Try to parse the contents - if parse fails assume a unix timestamp and leave unchanged
                $parsed_date = totara_date_parse_from_format(
                    $this->get_csv_date_format(),
                    trim($csvrow['timemodified']),
                    true
                );
                if ($parsed_date) {
                    $row['timemodified'] = $parsed_date;
                }
            }

            if ($row['aggregationmethod'] === '' && !$csvsaveemptyfields) {
                // Empty because we don't want to update anything is fine.
                $row['aggregationmethod'] = null;
            } else {
                $aggregationmethod = $this->parse_aggregationmethod($row['aggregationmethod']);
                // This field must be valid (including non-empty).
                if (!isset($aggregationmethod)) {
                    $this->addlog(
                        get_string('unrecognisedaggregrationmethod','tool_totara_sync', $row['aggregationmethod']),
                        'error',
                        'populatesynctablecsv'
                    );
                }
                $row['aggregationmethod'] = $aggregationmethod;
            }

            // Unset fields we are not saving since they are empty
            if (!$csvsaveemptyfields) {
                foreach ($row as $key => $value) {
                    if ($value === '') {
                        $row[$key] = null;
                    }
                }
            }

            if (!empty($this->hierarchy_customfields)) {
                $row['customfields'] = $this->get_customfield_json($csvrow, $csvsaveemptyfields);
                foreach ($this->hierarchy_customfields as $hierarchy_customfield) {
                    if ($this->is_importing_customfield($hierarchy_customfield)) {
                        unset($row[$hierarchy_customfield->get_default_fieldname()]);
                    }
                }
            }

            $datarows[] = $row;
            $rowcount++;

            if ($rowcount >= $dbpersist) {
                $this->check_length_limit($datarows, $DB->get_columns($temptable), $fieldmappings, 'comp');
                // Bulk insert
                try {
                    totara_sync_bulk_insert($temptable, $datarows);
                } catch (dml_exception $e) {
                    throw new totara_sync_exception($this->get_element_name(), 'populatesynctablecsv', 'couldnotimportallrecords', $e->getMessage());
                }

                $rowcount = 0;
                unset($datarows);
                $datarows = [];

                gc_collect_cycles();
            }
        }

        $this->check_length_limit($datarows, $DB->get_columns($temptable), $fieldmappings, 'comp');
        // Insert remaining rows
        try {
            totara_sync_bulk_insert($temptable, $datarows);
        } catch (dml_exception $e) {
            throw new totara_sync_exception($this->get_element_name(), 'populatesynctablecsv', 'couldnotimportallrecords', $e->getMessage());
        }
        unset($fieldmappings);

        $this->close_csv_file($file);

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
                case 'fullname':
                case 'shortname':
                case 'parentidnumber':
                case 'typeidnumber':
                case 'frameworkidnumber':
                case 'timemodified':
                case 'aggregationmethod':
                    $cleaned[$key] = clean_param(trim($value), PARAM_TEXT);
                    break;
                case 'deleted':
                    $cleaned[$key] = clean_param(trim($value), PARAM_INT);
                    break;
                case 'description':
                    $cleaned[$key] = clean_param(trim($value), PARAM_RAW);
                    break;
                default:
                    throw new totara_sync_exception($this->get_element_name(), 'importdata', 'nocleaninginstruction');
            }
        }

        return $cleaned;
    }
}
