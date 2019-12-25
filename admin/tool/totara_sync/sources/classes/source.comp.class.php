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

use tool_totara_sync\internal\hierarchy\customfield;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/admin/tool/totara_sync/sources/classes/source.class.php');

abstract class totara_sync_source_comp extends totara_sync_source {
    use \tool_totara_sync\internal\hierarchy\customfield_processor_trait;

    /**
     * Fields to be imported.
     *
     * @var string[]
     */
    protected $fields;

    public function __construct() {
        global $CFG;
        require_once($CFG->dirroot . '/admin/tool/totara_sync/elements/comp.php');
        require_once($CFG->dirroot . '/totara/hierarchy/prefix/competency/lib.php');

        $this->temptablename = 'totara_sync_comp';
        $this->element = new totara_sync_element_comp();
        parent::__construct();

        $this->fields = [
            'idnumber',
            'fullname',
            'shortname',
            'deleted',
            'description',
            'frameworkidnumber',
            'parentidnumber',
            'typeidnumber',
            'timemodified',
            'aggregationmethod',
        ];

        $this->hierarchy_customfields = customfield::get_all(new competency());
    }

    /**
     * Implement in child classes
     *
     * Populate the temp table to be used by the sync element
     *
     * @return boolean true on success
     * @throws totara_sync_exception
     */
    abstract public function import_data($temptable);

    /**
     * @return string
     */
    public function get_element_name() {
        return 'comp';
    }

    /**
     * @return bool True if configuration is required.
     */
    public function has_config() {
        return true;
    }

    /**
     * Override in child classes
     */
    public function uses_files() {
        return true;
    }

    /**
     * Override in child classes
     */
    public function get_filepath() {}

    /**
     * Add relevant form elements to the config form.
     *
     * @param $mform
     */
    public function config_form(&$mform) {
        // Fields to import.
        $mform->addElement('header', 'importheader', get_string('importfields', 'tool_totara_sync'));
        $mform->setExpanded('importheader');

        foreach ($this->fields as $f) {
            $name = 'import_'.$f;
            if (in_array($f, ['idnumber', 'fullname', 'frameworkidnumber', 'timemodified', 'aggregationmethod'])) {
                $mform->addElement('hidden', $name, '1');
                $mform->setType($name, PARAM_INT);
            } else if ($f == 'deleted') {
                $mform->addElement('hidden', $name, $this->config->$name);
                $mform->setType($name, PARAM_INT);
            } else {
                $mform->addElement('checkbox', $name, get_string($f, 'tool_totara_sync'));
            }
        }

        foreach ($this->hierarchy_customfields as $customfield) {
            $mform->addElement('checkbox', $customfield->get_import_setting_name(), $customfield->get_title());
        }

        $mform->addElement('header', 'dbfieldmappings', get_string('fieldmappings', 'tool_totara_sync'));
        $mform->setExpanded('dbfieldmappings');

        foreach ($this->fields as $f) {
            $mform->addElement('text', "fieldmapping_{$f}", $f);
            $mform->setType("fieldmapping_{$f}", PARAM_TEXT);
        }

        foreach ($this->hierarchy_customfields as $customfield) {
            $mform->addElement('text', $customfield->get_fieldmapping_setting_name(), $customfield->get_shortname_with_type());
            $mform->setType($customfield->get_fieldmapping_setting_name(), PARAM_TEXT);
        }
    }

    /**
     * Save data from the config form.
     *
     * @param $data
     */
    public function config_save($data) {
        foreach ($this->fields as $f) {
            $this->set_config('import_'.$f, !empty($data->{'import_'.$f}));
        }
        foreach ($this->hierarchy_customfields as $customfield) {
            $this->set_config($customfield->get_import_setting_name(), !empty($data->{$customfield->get_import_setting_name()}));
        }

        foreach ($this->fields as $f) {
            $this->set_config("fieldmapping_{$f}", trim($data->{'fieldmapping_'.$f}));
        }
        foreach ($this->hierarchy_customfields as $customfield) {
            $this->set_config($customfield->get_fieldmapping_setting_name(), $data->{$customfield->get_fieldmapping_setting_name()});
        }
    }

    /**
     * Prepares the temporary table for storing data in and performs import.
     *
     * @return string
     */
    public function get_sync_table() {

        try {
            $temptable = $this->prepare_temp_table();
        } catch (dml_exception $e) {
            throw new totara_sync_exception($this->get_element_name(), 'importdata',
                'temptableprepfail', $e->getMessage());
        }

        $this->import_data($temptable->getName());

        return $temptable->getName();
    }

    /**
     * Define and create the temporary table necessary for element syncing
     * @param boolean $clone add _clone to the table name?
     */
    public function prepare_temp_table($clone = false) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/lib/ddllib.php');

        /// Instantiate table
        $tablename = $this->temptablename;
        if ($clone) {
            $tablename .= '_clone';
        }
        $dbman = $DB->get_manager();
        $table = new xmldb_table($tablename);

        /// Add fields
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('idnumber', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
        $table->add_field('fullname', XMLDB_TYPE_CHAR, '1024');
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '100');
        $table->add_field('description', XMLDB_TYPE_TEXT, 'medium');
        $table->add_field('frameworkidnumber', XMLDB_TYPE_CHAR, '100');
        $table->add_field('parentidnumber', XMLDB_TYPE_CHAR, '100');
        $table->add_field('typeidnumber', XMLDB_TYPE_CHAR, '100');
        $table->add_field('customfields', XMLDB_TYPE_TEXT, 'big');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null);
        $table->add_field('aggregationmethod', XMLDB_TYPE_INTEGER, '10');

        // These values are set via hidden fields when creating or editing competencies. They are essentially fixed values.
        $table->add_field('proficiencyexpected', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 1);
        $table->add_field('evidencecount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);

        if (!empty($this->config->import_deleted)) {
            $table->add_field('deleted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        }
        
        /// Add keys
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        /// Add indexes
        $table->add_index('idnumber', XMLDB_INDEX_NOTUNIQUE, ['idnumber']);
        $table->add_index('frameworkidnumber', XMLDB_INDEX_NOTUNIQUE, ['frameworkidnumber']);
        $table->add_index('parentidnumber', XMLDB_INDEX_NOTUNIQUE, ['parentidnumber']);
        $table->add_index('typeidnumber', XMLDB_INDEX_NOTUNIQUE, ['typeidnumber']);

        /// Create and truncate the table
        $dbman->create_temp_table($table, false, false);
        $DB->execute("TRUNCATE TABLE {{$tablename}}");

        return $table;
    }

    /**
     * Takes import data and convers to the relevant constant for competency aggregation.
     *
     * @param string $fromsource Value given in import data.
     * @return int|null Int would be the constant. Null if there was no value or if it couldn't be linked to
     * a constant.
     */
    protected function parse_aggregationmethod($fromsource) {
        global $COMP_AGGREGATION;

        $result = null;

        if (is_number($fromsource)) {
            // We expect it will be one of the integers representing the aggregation method.
            $number = (int) $fromsource;
            if (in_array($number, $COMP_AGGREGATION)) {
                $result = $number;
            }
        } else {
            // We also allow for the English names for the values as used in code.
            // We cannot use language strings as these can change.
            $word = core_text::strtoupper($fromsource);
            if (isset($COMP_AGGREGATION[$word])) {
                $result = $COMP_AGGREGATION[$word];
            }
        }

        return $result;
    }

    /**
     * Validates configuration settings for this source.
     *
     * @param array $data Data submitted via the moodle form.
     * @param array $files Files submitted via the moodle form.
     * @return string[] Containing errors found during validation.
     */
    public function validate_settings($data, $files = []) {
        $errors = parent::validate_settings($data, $files);

        if (empty($data['import_typeidnumber'])) {
            foreach ($this->hierarchy_customfields as $customfield) {
                if (!empty($data[$customfield->get_import_setting_name()])) {
                    $errors['import_typeidnumber'] = get_string('hierarchycustomfieldneedstypeid', 'tool_totara_sync');
                    break;
                }
            }
        }

        return $errors;
    }
}
