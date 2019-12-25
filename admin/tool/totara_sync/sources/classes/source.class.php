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

global $CFG;
require_once($CFG->dirroot.'/admin/tool/totara_sync/lib.php');

abstract class totara_sync_source {
    protected $config;
    protected $fields;

    /**
     * The temp table name to be used for holding data from external source
     * Set this in the child class constructor
     */
    public $temptablename;

    /**
     * Directory root for all elements
     * @var string
     */
    public $filesdir;

    /**
     * @var totara_sync_element
     */
    protected $element;

    abstract function has_config();

    /**
     * Hook for adding source plugin-specific config form elements
     */
    abstract function config_form(&$mform);

    /**
     * Hook for saving source plugin-specific data
     */
    abstract function config_save($data);

    /**
     * Implementation of data import to the sync table
     *
     * @return sync table name (without prefix), e.g totara_sync_org
     * @throws totara_sync_exception if error
     */
    abstract function get_sync_table();

    /**
     * Define and create temp table necessary for element syncing
     */
    abstract function prepare_temp_table($clone = false);

    /**
     * Returns the name of the element this source applies to
     */
    abstract function get_element_name();

    /**
     * Returns whether the source uses files (e.g CSV) for syncing or not (e.g LDAP)
     *
     * @return boolean
     */
    abstract function uses_files();

    /**
     * Returns the source file location (used if uses_files returns true)
     *
     * @return string
     */
    abstract function get_filepath();


    /**
     * Remember to call parent::__construct() in child classes
     */
    function __construct() {
        $this->config = get_config($this->get_name());
        if (empty($this->config->delimiter)) {
            $this->config->delimiter = ',';
        }

        try {
            $this->filesdir = rtrim($this->get_element()->get_filesdir(), '/');
        } catch (totara_sync_exception $e) {
            // Third party code may be assigning an element after the parent::construct().
            $this->filesdir = rtrim(get_config('totara_sync', 'filesdir'), '/');
        }

        // Ensure child class specified temptablename
        if (!isset($this->temptablename)) {
            throw new totara_sync_exception($this->get_element_name, 'setup', 'error',
                'Programming error - source class for ' . $this->get_name() .
                ' needs to specify temptablename in constructor');
        }
    }

    /**
     * Gets the class name of the element source
     *
     * @return string the child class name
     */
    function get_name() {
        return get_class($this);
    }

    /**
     * Method for setting source plugin config settings
     */
    function set_config($name, $value) {
        if (set_config($name, $value, $this->get_name())) {
            if (!is_object($this->config)) {
                $this->config = get_config($this->get_name());
            } else {
                $this->config->{$name} = $value;
            }
        }

        return true;
    }

    /**
     * Method for getting source plugin config settings
     */
    function get_config($name) {
        return get_config($this->get_name(), $name);
    }
    /**
     * Add source sync log entries to the sync database with this method
     */
    function addlog($info, $type='info', $action='') {
        totara_sync_log($this->get_element_name(), $info, $type, $action);
    }

    /**
     * drop the temporary source table (if applicable)
     *
     * @return true
     * @throws dml_exception if error
     */
    function drop_table() {
        global $DB;

        if (empty($this->temptablename)) {
            // no temptable
            return true;
        }

        $dbman = $DB->get_manager(); // We are going to use database_manager services

        $table = new xmldb_table($this->temptablename);
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table); // And drop it
        }

        // drop any clones
        $table = new xmldb_table($this->temptablename . '_clone');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table); // And drop it
        }

        return true;
    }

    /**
     * Create clone of temp table because MySQL cannot reference temp
     * table twice in a query
     *
     * @return mixed Returns false if failed or the name of temporary table if successful
     */
    function get_sync_table_clone() {
        global $DB;

        try {
            $temptable_clone = $this->prepare_temp_table(true);
        } catch (dml_exception $e) {
            throw new totara_sync_exception($this->get_element_name(), 'importdata',
                'temptableprepfail', $e->getMessage());
        }

        // Can't reuse $this->import_data($temptable) because the CSV file gets renamed,
        // so it fails when calling again
        //to be cross-database compliant especially for MSSQL we need to use the $temptable column names
        $fields = $temptable_clone->getFields();
        $fieldnames = array();
        foreach ($fields as $field) {
            if ($field->getName() != 'id') {
                $fieldnames[] = $field->getName();
            }
        }
        $fieldlist = implode(",", $fieldnames);
        $sql = "INSERT INTO {{$temptable_clone->getName()}} ($fieldlist) SELECT $fieldlist FROM {{$this->temptablename}}";
        $DB->execute($sql);

        return $temptable_clone->getName();
    }

    /**
     * Return OS formatted path to sync files
     *
     * @param string $path path to file in filesdir (optional)
     * @return string
     */
    function get_canonical_filesdir($path = '') {
        // Make canonical name if possible.
        $realdir = realpath($this->filesdir);
        if ($realdir != false) {
            // Canonize rest of name when we sure that path is recognized by OS.
            $realdir .= str_replace('/', DIRECTORY_SEPARATOR, $path);
        } else {
            // Leave as is.
            $realdir = $this->filesdir . $path;
        }
        return $realdir;
    }

    /**
     * Check if length limit for a field is exceeded
     *
     * @param array $datarows contains all rows from the CSV file
     * @param array $columnsinfo contains the metadata of the fields to import from the CSV file
     * @param array $fieldmappings contains mapped fields from the CSV file
     * @param string $source source type (user, org, pos)
     */
    function check_length_limit(&$datarows, $columnsinfo, $fieldmappings, $source) {
        foreach ($datarows as $i => $datarow) {
            foreach ($datarow as $name => $value) {
                if ((($columnsinfo[$name]->type == 'varchar' || $columnsinfo[$name]->type == 'nvarchar') &&
                        core_text::strlen($value)) && (core_text::strlen($value) > $columnsinfo[$name]->max_length) &&
                        $columnsinfo[$name]->max_length != -1) {
                    $field = in_array($name, $fieldmappings) ? array_search($name, $fieldmappings) : $name;
                    // Prepare value to display in error message and for totara_sync_log table.
                    if (core_text::strlen($value) > 75) {
                        $value = core_text::substr($value, 0, 75) . ' ...';
                    }
                    $this->addlog(get_string('lengthlimitexceeded', 'tool_totara_sync', (object)array('idnumber' => $datarow['idnumber'], 'field' => $field,
                        'value' => $value, 'length' => $columnsinfo[$name]->max_length, 'source' => $source)), 'error', 'populatesynctablecsv');
                }
            }
        }
    }

    public function is_importing_field($fieldname) {
        return !empty($this->config->{"import_" . $fieldname});
    }

    /**
     * Generate common CSV source information and notifications.
     *
     * @return string HTML output.
     */
    protected function get_common_csv_notifications() {
        global $OUTPUT;

        // Display file example
        $fieldmappings = array();
        foreach ($this->fields as $field) {
            if (!empty($this->config->{'fieldmapping_' . $field})) {
                $fieldmappings[$field] = $this->config->{'fieldmapping_' . $field};
            }
        }

        $filestruct = array();
        foreach ($this->fields as $field) {
            if (!empty($this->config->{'import_' . $field})) {
                $filestruct[] = !empty($fieldmappings[$field]) ? $fieldmappings[$field] : $field;
            }
        }
        $filestruct = array_merge($filestruct, array_unique($this->get_mapped_customfields()));

        // Each value is surrounded by quotes when displaying the structure for a file.
        array_walk($filestruct, function(&$value) {
            $value = '"' . $value . '"';
        });

        $info = get_string('csvimportfilestructinfo', 'tool_totara_sync', implode($this->config->delimiter, $filestruct));
        $notifications = html_writer::tag('div', $info, ['class' => 'informationbox']);

        // Empty field info.
        $langstring = !empty($this->element->config->csvsaveemptyfields) ? 'csvemptysettingdeleteinfo' : 'csvemptysettingkeepinfo';
        $notifications .= $OUTPUT->notification(get_string($langstring, 'tool_totara_sync'), \core\output\notification::NOTIFY_WARNING);

        return $notifications;
    }

    /**
     * Generate common database source information and notifications.
     *
     * @return string HTML output.
     */
    protected function get_common_db_notifications() {
        global $OUTPUT;

        // Display required db table columns
        $fieldmappings = array();
        foreach ($this->fields as $field) {
            if (!empty($this->config->{'fieldmapping_' . $field})) {
                $fieldmappings[$field] = $this->config->{'fieldmapping_' . $field};
            }
        }

        $dbstruct = array();
        foreach ($this->fields as $field) {
            if (!empty($this->config->{'import_' . $field})) {
                $dbstruct[] = !empty($fieldmappings[$field]) ? $fieldmappings[$field] : $field;
            }
        }

        $dbstruct = array_merge($dbstruct, array_unique($this->get_mapped_customfields()));

        $dbstruct = implode(', ', $dbstruct);
        $description = get_string('tablemustincludexdb', 'tool_totara_sync') . \html_writer::empty_tag('br') . $dbstruct;
        $notifications = html_writer::tag('div', $description, ['class' => 'informationbox']);

        // Empty or null field info.
        $info = get_string('databaseemptynullinfo', 'tool_totara_sync');
        $notifications .= $OUTPUT->notification($info, \core\output\notification::NOTIFY_WARNING);

        return $notifications;
    }

    /**
     * @return array of customfields with structure ['identifier' => 'mapped field name']
     */
    protected function get_mapped_customfields() {
        $mappedfields = [];

        if (isset($this->customfields)) {
            foreach ($this->customfields as $key => $field) {
                if (empty($this->config->{'import_' . $key})) {
                    continue;
                }
                if (empty($this->config->{'fieldmapping_' . $key})) {
                    $mappedfields[$key] = 'customfield_' . $field;
                } else {
                    $mappedfields[$key] = $this->config->{'fieldmapping_' . $key};
                }
            }
        }

        return $mappedfields;
    }

    /**
     * @return string with the intended format for dates in csv files on this site.
     */
    protected function get_csv_date_format() {
        global $CFG;

        return $CFG->csvdateformat ?? get_string('csvdateformatdefault', 'totara_core');
    }

    /**
     * Validates configuration settings for this source.
     *
     * @param array $data Data submitted via the moodle form.
     * @param array $files Files submitted via the moodle form.
     * @return string[] Containing errors found during validation.
     */
    public function validate_settings($data, $files = []) {
        return [];
    }

    /**
     * @return totara_sync_element
     */
    public function get_element() {
        if (isset($this->element)) {
            return $this->element;
        }

        throw new totara_sync_exception($this->get_element_name(), 'settings', 'noassociatedelement');
    }
}
