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
 * @subpackage totara_sync
 */

require_once($CFG->dirroot.'/admin/tool/totara_sync/sources/classes/source.user.class.php');
require_once($CFG->dirroot.'/admin/tool/totara_sync/lib.php');
require_once($CFG->dirroot.'/admin/tool/totara_sync/elements/user.php');

class totara_sync_source_user_csv extends totara_sync_source_user {

    function get_filepath() {
        $path = '/csv/ready/user.csv';
        $pathos = $this->get_canonical_filesdir($path);
        return $pathos;
    }

    function config_form(&$mform) {
        global $CFG;

        $this->config->import_idnumber = "1";
        $this->config->import_username = "1";
        $this->config->import_timemodified = "1";
        if (!empty($this->element->config->allow_create)) {
            $this->config->import_firstname = "1";
            $this->config->import_lastname = "1";
        }
        if (empty($this->element->config->allowduplicatedemails)) {
            $this->config->import_email = "1";
        }
        $this->config->import_deleted = empty($this->element->config->sourceallrecords) ? "1" : "0";

        // Add some source file details
        $mform->addElement('header', 'fileheader', get_string('filedetails', 'tool_totara_sync'));
        $mform->setExpanded('fileheader');

        try {
            if ($this->get_element()->get_fileaccess() == FILE_ACCESS_DIRECTORY) {
                $mform->addElement('static', 'nameandloc', get_string('nameandloc', 'tool_totara_sync'),
                    \html_writer::tag('strong', $this->get_filepath()));
            } else {
                $link = "{$CFG->wwwroot}/admin/tool/totara_sync/admin/uploadsourcefiles.php";
                $mform->addElement('static', 'uploadfilelink', get_string('uploadfilelink', 'tool_totara_sync', $link));
            }
        } catch (\totara_sync_exception $e) {
            $mform->addElement('static', 'configurefileaccess', '', get_string('configurefileaccess', 'tool_totara_sync'));
        }

        $encodings = core_text::get_encodings();
        $mform->addElement('select', 'csvuserencoding', get_string('csvencoding', 'tool_totara_sync'), $encodings);
        $mform->setType('csvuserencoding', PARAM_ALPHANUMEXT);
        $default = $this->get_config('csvuserencoding');
        $default = (!empty($default) ? $default : 'UTF-8');
        $mform->setDefault('csvuserencoding', $default);

        $delimiteroptions = array(
            ',' => get_string('comma', 'tool_totara_sync'),
            ';' => get_string('semicolon', 'tool_totara_sync'),
            ':' => get_string('colon', 'tool_totara_sync'),
            '\t' => get_string('tab', 'tool_totara_sync'),
            '|' => get_string('pipe', 'tool_totara_sync')
        );

        $mform->addElement('select', 'delimiter', get_string('delimiter', 'tool_totara_sync'), $delimiteroptions);
        $default = $this->config->delimiter;
        if (empty($default)) {
            $default = ',';
        }
        $mform->setDefault('delimiter', $default);

        parent::config_form($mform);
    }

    function config_save($data) {
        // Make sure we use a tab character for the delimiter, if a tab is selected.
        $this->set_config('delimiter', $data->{'delimiter'} == '\t' ? "\t" : $data->{'delimiter'});
        $this->set_config('csvuserencoding', $data->{'csvuserencoding'});

        parent::config_save($data);
    }

    function import_data($temptable) {
        global $CFG, $DB, $OUTPUT;

        $fileaccess = $this->get_element()->get_fileaccess();
        if ($fileaccess == FILE_ACCESS_DIRECTORY) {
            if (!$this->filesdir) {
                throw new totara_sync_exception($this->get_element_name(), 'populatesynctablecsv', 'nofilesdir');
            }
            $filepath = $this->get_filepath();
            if (!file_exists($filepath)) {
                throw new totara_sync_exception($this->get_element_name(), 'populatesynctablecsv', 'nofiletosync', $filepath, null, 'warn');
            }
            $filemd5 = md5_file($filepath);
            while (true) {
                // Ensure file is not currently being written to
                sleep(2);
                $newmd5 = md5_file($filepath);
                if ($filemd5 != $newmd5) {
                    $filemd5 = $newmd5;
                } else {
                    break;
                }
            }

            // See if file is readable
            if (!$file = is_readable($filepath)) {
                throw new totara_sync_exception($this->get_element_name(), 'populatesynctablecsv', 'cannotreadx', $filepath);
            }

            // Move file to store folder
            $storedir = $this->filesdir.'/csv/store';
            if (!totara_sync_make_dirs($storedir)) {
                throw new totara_sync_exception($this->get_element_name(), 'populatesynctablecsv', 'cannotcreatedirx', $storedir);
            }

            $storefilepath = $storedir . '/' . time() . '.' . basename($filepath);

            rename($filepath, $storefilepath);
        } else if ($fileaccess == FILE_ACCESS_UPLOAD) {
            $fs = get_file_storage();
            $systemcontext = context_system::instance();
            $fieldid = get_config('totara_sync', 'sync_user_itemid');

            // Check the file exist
            if (!$fs->file_exists($systemcontext->id, 'totara_sync', 'user', $fieldid, '/', '')) {
                throw new totara_sync_exception($this->get_element_name(), 'populatesynctablecsv', 'nofileuploaded', $this->get_element_name(), null, 'warn');
            }

            // Get the file
            $fsfiles = $fs->get_area_files($systemcontext->id, 'totara_sync', 'user', $fieldid, 'id DESC', false);
            $fsfile = reset($fsfiles);

            // Set up the temp dir
            $tempdir = $CFG->tempdir . '/totarasync/csv';
            check_dir_exists($tempdir, true, true);

            // Create temporary file (so we know the filepath)
            $fsfile->copy_content_to($tempdir.'/user.php');
            $itemid = $fsfile->get_itemid();
            $fs->delete_area_files($systemcontext->id, 'totara_sync', 'user', $itemid);
            $storefilepath = $tempdir.'/user.php';

        }

        $encoding = $this->get_config('csvuserencoding');
        $storefilepath = totara_sync_clean_csvfile($storefilepath, $encoding, $fileaccess, $this->get_element_name());

        // Open file from store for processing
        if (!$file = fopen($storefilepath, 'r')) {
            throw new totara_sync_exception($this->get_element_name(), 'populatesynctablecsv', 'cannotopenx', $storefilepath);
        }

        // Map CSV fields.
        $fields = fgetcsv($file, 0, $this->config->delimiter);
        $fieldmappings = array();
        foreach ($this->fields as $f) {
            // Prevent allow_delete == SUSPEND_USERS and suspended column being set
            // at the same time.
            if ($f == 'suspended' && $this->element->config->allow_delete == totara_sync_element_user::SUSPEND_USERS) {
                unset($this->config->import_suspended);
            }

            if (empty($this->config->{'import_'.$f})) {
                continue;
            }
            if (!empty($this->config->{'fieldmapping_'.$f})) {
                $fieldmappings[$this->config->{'fieldmapping_'.$f}] = $f;
            }
        }

        foreach (array_keys($this->customfields) as $f) {
            if (empty($this->config->{'import_'.$f})) {
                continue;
            }
            if (!empty($this->config->{'fieldmapping_'.$f})) {
                $fieldmappings[$this->config->{'fieldmapping_'.$f}] = $f;
            }
        }

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

        // Ensure necessary mapped fields are present in the CSV
        foreach ($fieldmappings as $m => $f) {
            if (!in_array($m, $fields)) {
                if ($f == $m) {
                    throw new totara_sync_exception($this->get_element_name(), 'mapfields', 'csvnotvalidmissingfieldx', $f);
                } else {
                    throw new totara_sync_exception($this->get_element_name(), 'mapfields', 'csvnotvalidmissingfieldxmappingx', (object)array('field' => $f, 'mapping' => $m));
                }
            }
        }
        // Finally, perform CSV to db field mapping
        foreach ($fields as $i => $f) {
            if (in_array($f, array_keys($fieldmappings))) {
                $fields[$i] = $fieldmappings[$f];
            }
        }

        // Check field integrity for general fields.
        foreach ($this->fields as $f) {
            if (empty($this->config->{'import_'.$f}) || in_array($f, $fieldmappings)) {
                // Disabled or mapped fields can be ignored
                continue;
            }
            if (!in_array($f, $fields)) {
                throw new totara_sync_exception($this->get_element_name(), 'importdata', 'csvnotvalidmissingfieldx', $f);
            }
        }

        // Check field integrity for custom fields.
        foreach ($this->customfields as $cf => $name) {
            if (empty($this->config->{'import_'. $cf}) || in_array($cf, $fieldmappings)) {
                // Disabled or mapped fields can be ignored.
                continue;
            }
            if (!in_array($cf, $fields)) {
                throw new totara_sync_exception($this->get_element_name(), 'importdata', 'csvnotvalidmissingfieldx', $cf);
            }
        }

        // Populate temp sync table from CSV
        $now = time();
        $datarows = array();    // holds csv row data
        $dbpersist = TOTARA_SYNC_DBROWS;  // # of rows to insert into db at a time
        $rowcount = 0;
        $fieldcount = new stdClass();
        $fieldcount->headercount = count($fields);
        $fieldcount->rownum = 0;
        $csvdateformat = (isset($CFG->csvdateformat)) ? $CFG->csvdateformat : get_string('csvdateformatdefault', 'totara_core');
        $badtimezones = false;

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

            $csvrow = array_combine($fields, $csvrow);  // nice associative array ;)

            // Set up a db row
            $dbrow = array();

            // General fields
            foreach ($this->fields as $f) {
                if (!empty($this->config->{'import_'.$f})) {
                    $dbrow[$f] = $csvrow[$f];
                }
            }

            $dbrow = $this->clean_fields($dbrow);

            if (empty($csvrow['timemodified'])) {
                $dbrow['timemodified'] = 0;
            } else {
                // Try to parse the contents - if parse fails assume a unix timestamp and leave unchanged
                $parsed_date = totara_date_parse_from_format($csvdateformat, trim($csvrow['timemodified']), true);
                if ($parsed_date) {
                    $dbrow['timemodified'] = $parsed_date;
                }
            }

            // Deleted doesn't support ignore empty fields, empty defaults to zero.
            if (isset($dbrow['deleted'])) {
                $dbrow['deleted'] = empty($dbrow['deleted']) ? 0 : 1;
            }

            if (isset($dbrow['suspended'])) {
                if ($dbrow['suspended'] === '' && !$csvsaveemptyfields)  {
                    $dbrow['suspended'] = null;
                } else {
                    $dbrow['suspended'] = empty($dbrow['suspended']) ? 0 : 1;
                }
            }

            if (isset($dbrow['emailstop'])) {
                // If email stop is empty then set it to null.
                // (if an empty string is inserted into an integer field in the DB is will become 0).
                if ($dbrow['emailstop'] === '' && !$csvsaveemptyfields) {
                    $dbrow['emailstop'] = null;
                } else {
                    // If we are saving empty field this can come empty from source and will become zero.
                    $dbrow['emailstop'] = empty($dbrow['emailstop']) ? 0 : 1;
                }
            }

            if (isset($dbrow['timezone'])) {
                if (!empty($dbrow['timezone'])) {
                    $timezone = core_date::normalise_timezone($dbrow['timezone']);
                    if ($timezone != '99' and $timezone !== $dbrow['timezone']) {
                        // Unsupported timezone, output message at end of process
                        $badtimezones = true;
                    }
                    $dbrow['timezone'] = $timezone;
                } else if ($dbrow['timezone'] === '' && $csvsaveemptyfields) {
                    $dbrow['timezone'] = '99';
                } else {
                    $dbrow['timezone'] = null;
                }
            }

            // Custom fields are special - needs to be json-encoded
            if (!empty($this->customfields)) {
                $cfield_data = array();
                foreach (array_keys($this->customfields) as $cf) {
                    if (!empty($this->config->{'import_'.$cf})) {
                        // Get shortname and check if we need to do field type processing
                        $value = trim($csvrow[$cf]);
                        if ($value === '') {
                            if (!$csvsaveemptyfields) {
                                // Empty means skip, don't import.
                                continue;
                            }
                        } else if (isset($value)) {
                            $shortname = str_replace("customfield_", "", $cf);
                            $datatype = $DB->get_field('user_info_field', 'datatype', array('shortname' => $shortname));
                            switch ($datatype) {
                                case 'datetime':
                                    // Try to parse the contents - if parse fails assume a unix timestamp and leave unchanged.
                                    $parsed_date = totara_date_parse_from_format($csvdateformat, $value, true);
                                    if ($parsed_date) {
                                        $value = $parsed_date;
                                    } elseif (empty($value) || !is_numeric($value)) {
                                        // Don't try to put a value if the field has been left empty, is 0 or not numeric.
                                        continue 2;
                                    }
                                    break;
                                case 'date':
                                    // Try to parse the contents - if parse fails assume a unix timestamp and leave unchanged.
                                    $parsed_date = totara_date_parse_from_format($csvdateformat, $value, true, 'UTC');
                                    if ($parsed_date) {
                                        $value = $parsed_date;
                                    } elseif (empty($value) || !is_numeric($value)) {
                                        // Don't try to put a value if the field has been left empty, is 0 or not numeric.
                                        continue 2;
                                    }
                                    break;
                                case 'checkbox':
                                    $value = $value == '0' ? 0 : 1;
                                    break;
                                default:
                                    break;
                            }
                        }

                        $cfield_data[$cf] = $value;
                        unset($dbrow[$cf]);
                    }
                }
                $dbrow['customfields'] = json_encode($cfield_data);
                unset($cfield_data);
            }

            // We have dealt with all specific cases so all (non required) remaining empty
            // fields should be changed to null if we are not saving empty fields.
            foreach ($this->fields as $f) {
                if (isset($dbrow[$f]) && $dbrow[$f] === '' && !$csvsaveemptyfields && !in_array($f, $this->required_fields)) {
                    $dbrow[$f] = null;
                }
            }

            $datarows[] = $dbrow;
            $rowcount++;

            if ($rowcount >= $dbpersist) {
                $this->check_length_limit($datarows, $DB->get_columns($temptable), $fieldmappings, 'user');
                // Bulk insert
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

        $this->check_length_limit($datarows, $DB->get_columns($temptable), $fieldmappings, 'user');
        // Insert remaining rows
        try {
            totara_sync_bulk_insert($temptable, $datarows);
        } catch (dml_exception $e) {
            error_log($e->getMessage()."\n".$e->debuginfo);
            throw new totara_sync_exception($this->get_element_name(), 'populatesynctablecsv', 'couldnotimportallrecords', $e->getMessage());
        }
        unset($fieldmappings);

        fclose($file);
        if ($badtimezones) {
            $OUTPUT->notification(get_string('badusertimezonemessage', 'tool_totara_timezonefix'), 'notifynotice');
        }
        // Done, clean up the file(s)
        if ($fileaccess == FILE_ACCESS_UPLOAD) {
            unlink($storefilepath); // don't store this file in temp
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
        global $OUTPUT;

        $notifications = $this->get_common_csv_notifications();

        // Show a notification about delete suspending/unsuspending users
        if (isset($this->element->config->allow_delete) && $this->element->config->allow_delete == totara_sync_element_user::SUSPEND_USERS) {
            $suspenddelete = get_string('suspendcolumndisabled', 'tool_totara_sync');
            $notifications .= $OUTPUT->notification($suspenddelete, \core\output\notification::NOTIFY_WARNING);
        }

        return $notifications;
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
            switch ($key) {
                case 'idnumber':
                case 'timemodified':
                case 'username':
                case 'firstname':
                case 'lastname':
                case 'firstnamephonetic':
                case 'lastnamephonetic':
                case 'middlename':
                case 'alternatename':
                case 'email':
                case 'emailstop':
                case 'city':
                case 'country':
                case 'timezone':
                case 'lang':
                case 'url':
                case 'institution':
                case 'department':
                case 'phone1':
                case 'phone2':
                case 'address':
                case 'auth':
                case 'deleted':
                case 'suspended':
                    $cleaned[$key] = clean_param(trim($value), PARAM_TEXT);
                    break;
                case 'description':
                case 'password':
                    $cleaned[$key] = clean_param(trim($value), PARAM_RAW);
                    break;
                default:
                    // This is not an available field to be synced, don't include.
            }
        }

        return $cleaned;
    }
}
