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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package totara
 * @subpackage totara_sync
 */

require_once($CFG->dirroot.'/admin/tool/totara_sync/sources/classes/source.org.class.php');
require_once($CFG->dirroot.'/admin/tool/totara_sync/lib.php');
require_once($CFG->dirroot.'/totara/core/js/lib/setup.php');
require_once($CFG->dirroot.'/admin/tool/totara_sync/sources/databaselib.php');

class totara_sync_source_org_database extends totara_sync_source_org {

    function config_form(&$mform) {
        global $PAGE, $OUTPUT;

        $this->config->import_idnumber = "1";
        $this->config->import_fullname = "1";
        $this->config->import_frameworkidnumber = "1";
        $this->config->import_timemodified = "1";
        $this->config->import_deleted = empty($this->element->config->sourceallrecords) ? "1" : "0";

        $db_table = isset($this->config->{'database_dbtable'}) ? $this->config->{'database_dbtable'} : false;

        if (!$db_table) {
            $mform->addElement('html', html_writer::tag('p',get_string('dbconnectiondetails', 'tool_totara_sync')));
        }

        $db_options = get_installed_db_drivers();

        // Database details
        $mform->addElement('select', 'database_dbtype', get_string('dbtype', 'tool_totara_sync'), $db_options);
        $mform->addElement('text', 'database_dbname', get_string('dbname', 'tool_totara_sync'));
        $mform->addRule('database_dbname', get_string('err_required', 'form'), 'required');
        $mform->setType('database_dbname', PARAM_RAW); // There is no safe cleaning of connection strings.
        $mform->addElement('text', 'database_dbhost', get_string('dbhost', 'tool_totara_sync'));
        $mform->setType('database_dbhost', PARAM_HOST);
        $mform->addElement('text', 'database_dbuser', get_string('dbuser', 'tool_totara_sync'));
        $mform->addRule('database_dbuser', get_string('err_required', 'form'), 'required');
        $mform->setType('database_dbuser', PARAM_ALPHANUMEXT);
        $mform->addElement('password', 'database_dbpass', get_string('dbpass', 'tool_totara_sync'));
        $mform->setType('database_dbpass', PARAM_RAW);
        $mform->addElement('text', 'database_dbport', get_string('dbport', 'tool_totara_sync'));
        $mform->setType('database_dbport', PARAM_INT);

        // Table name
        $mform->addElement('text', 'database_dbtable', get_string('dbtable', 'tool_totara_sync'));
        $mform->addRule('database_dbtable', get_string('err_required', 'form'), 'required');
        $mform->setType('database_dbtable', PARAM_ALPHANUMEXT);

        $mform->addElement('button', 'database_dbtest', get_string('dbtestconnection', 'tool_totara_sync'));

        //Javascript include
        local_js(array(TOTARA_JS_DIALOG));

        $PAGE->requires->strings_for_js(array('dbtestconnectsuccess', 'dbtestconnectfail'), 'tool_totara_sync');

        $jsmodule = array(
                'name' => 'totara_syncdatabaseconnect',
                'fullpath' => '/admin/tool/totara_sync/sources/sync_database.js',
                'requires' => array('json', 'totara_core'));

        $PAGE->requires->js_init_call('M.totara_syncdatabaseconnect.init', null, false, $jsmodule);

        parent::config_form($mform);
    }

    function config_save($data) {
        //Check database connection when saving
        try {
            setup_sync_DB($data->{'database_dbtype'}, $data->{'database_dbhost'}, $data->{'database_dbname'},
                $data->{'database_dbuser'}, $data->{'database_dbpass'}, array('dbport' => $data->{'database_dbport'}));
        } catch (Exception $e) {
            totara_set_notification(get_string('cannotconnectdbsettings', 'tool_totara_sync'), qualified_me());
        }

        $this->set_config('database_dbtype', $data->{'database_dbtype'});
        $this->set_config('database_dbname', $data->{'database_dbname'});
        $this->set_config('database_dbhost', $data->{'database_dbhost'});
        $this->set_config('database_dbuser', $data->{'database_dbuser'});
        $this->set_config('database_dbpass', $data->{'database_dbpass'});
        $this->set_config('database_dbport', $data->{'database_dbport'});
        $this->set_config('database_dbtable', $data->{'database_dbtable'});

        parent::config_save($data);
    }

    function import_data($temptable) {
        global $DB; // Careful using this in here as we have 2 database connections

        // Get database config
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
        }

        // Get list of fields to be imported
        $fields = array();
        foreach ($this->fields as $f) {
            if (!empty($this->config->{'import_'.$f})) {
                $fields[] = $f;
            }
        }

        // Sort out field mappings
        $fieldmappings = array();
        foreach ($fields as $i => $f) {
            if (empty($this->config->{'fieldmapping_'.$f})) {
                $fieldmappings[$f] = $f;
            } else {
                $fieldmappings[$f] = $this->config->{'fieldmapping_'.$f};
            }
        }

        // Finally, perform externaldb to totara db field mapping
        foreach ($fields as $i => $f) {
            if (in_array($f, array_keys($fieldmappings))) {
                $fields[$i] = $fieldmappings[$f];
            }
        }

        // Custom fields are made unique as it is permitted to have one column for customfields
        // with the same shortname for example (possible if each field has a different type).
        $fields = array_merge(
            $fields,
            $this->get_unique_mapped_customfields()
        );

        // Check the table exists in the database.
        try {
            $database_connection->get_record_sql("SELECT 1 FROM $db_table", null, IGNORE_MULTIPLE);
        } catch (Exception $e) {
            $this->addlog(get_string('dbmissingtablex', 'tool_totara_sync', $db_table), 'error', 'importdata');
            return false;
        }

        // Check that all fields exists in database.
        $missingcolumns = array();
        foreach ($fields as $f) {
            try {
                $database_connection->get_field_sql("SELECT $f from $db_table", array(), IGNORE_MULTIPLE);
            } catch (Exception $e) {
                $missingcolumns[] = $f;
            }
        }
        if (!empty($missingcolumns)) {
            $missingcolumnsstr = implode(', ', $missingcolumns);
            $this->addlog(get_string('dbmissingcolumnx', 'tool_totara_sync', $missingcolumnsstr), 'error', 'importdata');
            return false;
        }

        unset($fieldmappings);

        ///
        /// Populate temp sync table from remote database
        ///
        $datarows = array();  // holds rows of data
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

            if (empty($extdbrow['timemodified'])) {
                $dbrow['timemodified'] = 0;
            } else {
                //try to parse the contents - if parse fails assume a unix timestamp and leave unchanged
                $parsed_date = totara_date_parse_from_format(
                    $this->get_csv_date_format(),
                    trim($extdbrow['timemodified']),
                    true
                );
                if ($parsed_date) {
                    $dbrow['timemodified'] = $parsed_date;
                }
            }
            // Custom fields are special - needs to be json-encoded
            if (!empty($this->hierarchy_customfields)) {
                $dbrow['customfields'] = $this->get_customfield_json($extdbrow);
                foreach ($this->hierarchy_customfields as $hierarchy_customfield) {
                    if ($this->is_importing_customfield($hierarchy_customfield)) {
                        unset($dbrow[$hierarchy_customfield->get_default_fieldname()]);
                    }
                }
            }

            $datarows[] = $dbrow;
            $rowcount++;

            if ($rowcount >= TOTARA_SYNC_DBROWS) {
                // bulk insert
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

        // Insert remaining rows
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

    /**
     * @return bool False as database sources do not use files.
     */
    function uses_files() {
        return false;
    }
}
