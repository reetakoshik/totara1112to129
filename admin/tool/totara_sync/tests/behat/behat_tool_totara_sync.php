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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @author Rob tyler <rob.tyler@totaralearning.com>
 * @package tool_totara_sync
 * @copyright 2015 Totara Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException;
use Behat\Gherkin\Node\TableNode as TableNode;
use Behat\Behat\Tester\Exception\PendingException as PendingException;

class behat_tool_totara_sync extends behat_base {

    /**
     * Toggle the state of an HR Import element.
     *
     * @Given /^I "(Enable|Disable)" the "([^"]*)" HR Import element$/
     */
    public function i_the_hr_import_element($state, $element) {
        \behat_hooks::set_step_readonly(false);
        $xpath = "//table[@id='elements']//descendant::text()[contains(.,'{$element}')]//ancestor::tr//a[@title='{$state}']";
        $exception = new ElementNotFoundException($this->getSession(), 'Could not find state switch for the given HR Import element');
        $node = $this->find('xpath', $xpath, $exception);
        if ($node) {
            $node->click();
        }
    }


    /**
     * Creates a table with the given data for use as an External Database in HR Import.
     *
     * @Given /^the following "([^"]*)" HR Import database source exists:$/
     */
    public function theFollowingHRImportDatabaseSourceExists($element, TableNode $datatable) {
        \behat_hooks::set_step_readonly(false);
        global $CFG;

        require_once($CFG->dirroot . '/admin/tool/totara_sync/lib.php');
        require_once($CFG->dirroot . '/admin/tool/totara_sync/sources/databaselib.php');

        switch ($element) {
            case 'organisation':
                $short_element = 'org';
                break;
            case 'position':
                $short_element = 'pos';
                break;
            case 'competency':
                $short_element = 'comp';
                break;
            case 'user':
            case 'jobassignment':
                $short_element = $element;
                break;
            default:
                throw new PendingException("'{$element}' is not a valid HR Import element name.");
        }

        $dbconfig = array();

        // Determine the database connection settings we need to use.
        // This is based on code doing a similar thing for PHPunit.
        if (defined('TEST_SYNC_DB_TYPE') ||
            defined('TEST_SYNC_DB_HOST') ||
            defined('TEST_SYNC_DB_PORT') ||
            defined('TEST_SYNC_DB_NAME') ||
            defined('TEST_SYNC_DB_USER') ||
            defined('TEST_SYNC_DB_PASS') ||
            defined('TEST_SYNC_DB_TABLE')) {
            $dbconfig['dbtype'] = defined('TEST_SYNC_DB_TYPE') ? TEST_SYNC_DB_TYPE : '';
            $dbconfig['dbhost'] = defined('TEST_SYNC_DB_HOST') ? TEST_SYNC_DB_HOST : '';
            $dbconfig['dbport'] = defined('TEST_SYNC_DB_PORT') ? TEST_SYNC_DB_PORT : '';
            $dbconfig['dbname'] = defined('TEST_SYNC_DB_NAME') ? TEST_SYNC_DB_NAME : '';
            $dbconfig['dbuser'] = defined('TEST_SYNC_DB_USER') ? TEST_SYNC_DB_USER : '';
            $dbconfig['dbpass'] = defined('TEST_SYNC_DB_PASS') ? TEST_SYNC_DB_PASS : '';
            $dbconfig['dbtable'] = defined('TEST_SYNC_DB_TABLE') ? TEST_SYNC_DB_TABLE . '_' . $short_element : '';
        } else {
            $dbconfig['dbtype'] = $CFG->dbtype;
            $dbconfig['dbhost'] = $CFG->dbhost;
            $dbconfig['dbport'] = !empty($CFG->dboptions['dbport']) ? $CFG->dboptions['dbport'] : '';
            $dbconfig['dbname'] = $CFG->dbname;
            $dbconfig['dbuser'] = $CFG->dbuser;
            $dbconfig['dbpass'] = !empty($CFG->dbpass) ? $CFG->dbpass : '';
            // I know it's the old name, it's just to keep the tables together...
            $dbconfig['dbtable'] = $CFG->behat_prefix . "totara_sync_source_" . $short_element;
        }

        // Check all the important settings have a value so we can connect to the database.
        foreach ($dbconfig as $setting => $value) {
            if (empty($value) && ($setting != 'dbport' && $setting != 'dbpass')) {
                throw new PendingException("HR Import database test configuration '{$setting}' could not be determined ('').");
            }
        }

        // Attempt to connect to the database and throw an error if we can't.
        $dbconnection = setup_sync_DB($dbconfig['dbtype'], $dbconfig['dbhost'], $dbconfig['dbname'], $dbconfig['dbuser'], $dbconfig['dbpass'], array('dbport' => $dbconfig['dbport']));
        if (!$dbconnection) {
            throw new PendingException("Cannot connect to HR Import database. Please check credentials.");
        }

        // Start the creation of the new database table.
        $dbman = $dbconnection->get_manager();
        $table = new xmldb_table($dbconfig['dbtable']);

        // Drop table first, if it exists.
        if ($dbman->table_exists($dbconfig['dbtable'])) {
            $dbman->drop_table($table, $dbconfig['dbtable']);
        }

        // Add the fields that are common to each element.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('idnumber', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);

        // Add the fields that are specific to each element.
        switch ($element) {
            case 'user':
                // Define a list of required fields so we can check the source data against them.
                $required_fields = array ('idnumber', 'username', 'deleted', 'firstname', 'lastname', 'timemodified');

                // Define the default columns.
                $table->add_field('username', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
                $table->add_field('deleted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
                $table->add_field('firstname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
                $table->add_field('lastname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
                $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);

                // Define additional optional columns.
                $table->add_field('email', XMLDB_TYPE_CHAR, '100');
                $table->add_field('password', XMLDB_TYPE_CHAR, '32');
                $table->add_field('suspended', XMLDB_TYPE_INTEGER, '1');
                $table->add_field('auth', XMLDB_TYPE_CHAR, '20');
                $table->add_field('manageridnumber', XMLDB_TYPE_CHAR, '100');
                $table->add_field('appraiseridnumber', XMLDB_TYPE_CHAR, '100');

                // Define positions, jobs and org additional optional columns.
                $table->add_field('jobassignmentidnumber', XMLDB_TYPE_CHAR, '100');
                $table->add_field('jobassignmentfullname', XMLDB_TYPE_CHAR, '100');
                $table->add_field('jobassignmentstartdate', XMLDB_TYPE_CHAR, '100');
                $table->add_field('jobassignmentenddate', XMLDB_TYPE_CHAR, '100');
                $table->add_field('orgidnumber', XMLDB_TYPE_CHAR, '100');
                $table->add_field('posidnumber', XMLDB_TYPE_CHAR, '100');

                break;

            case 'organisation':
            case 'position':
            case 'competency':
                // Define a list of required fields so we can check the source data against them.
                $required_fields = array ('idnumber', 'fullname', 'frameworkidnumber', 'timemodified');

                // Define the default columns.
                $table->add_field('fullname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
                $table->add_field('deleted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
                $table->add_field('frameworkidnumber', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
                $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);

                // Define the optional additional columns.
                $table->add_field('shortname', XMLDB_TYPE_CHAR, '100');
                $table->add_field('description', XMLDB_TYPE_TEXT);
                $table->add_field('parentidnumber', XMLDB_TYPE_CHAR, '100');
                $table->add_field('typeidnumber', XMLDB_TYPE_CHAR, '100');

                if ($element === 'competency') {
                    $table->add_field('aggregationmethod', XMLDB_TYPE_CHAR, '100');
                }

                break;

            case 'jobassignment':
                // Define a list of required fields so we can check the source data against them.
                $required_fields = array ('idnumber', 'useridnumber', 'timemodified', 'deleted');

                // Define the default columns.
                $table->add_field('useridnumber', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
                $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
                $table->add_field('deleted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

                // Define the optional additional columns.
                $table->add_field('fullname', XMLDB_TYPE_CHAR, '100');
                $table->add_field('startdate', XMLDB_TYPE_INTEGER, '10');
                $table->add_field('enddate', XMLDB_TYPE_INTEGER, '10');
                $table->add_field('orgidnumber', XMLDB_TYPE_CHAR, '100');
                $table->add_field('posidnumber', XMLDB_TYPE_CHAR, '100');
                $table->add_field('manageridnumber', XMLDB_TYPE_CHAR, '100');
                $table->add_field('appraiseridnumber', XMLDB_TYPE_CHAR, '100');

                break;

            default:
        }

        /// Add primary key and indexes.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('idnumber', XMLDB_INDEX_NOTUNIQUE, array('idnumber'));

        /// Create the table.
        $dbman->create_table($table, false, false);

        // Get the field names of the data we want to insert.
        $data = $datatable->getRows();
        $fields = array_shift($data);
        $fields = array_unique($fields);

        // Check all the required fields are present.
        foreach ($required_fields as $field) {
            if (!in_array($field, $fields)) {
                throw new PendingException("'{$field}' is a mandatory column and is missing.");
            }
        }

        // Loop through the data and insert it into the database.
        foreach ($data as $row_number => $row_data) {
            $dbdata = array ();

            foreach ($fields as $index => $field) {

                if ($row_data[$index] === 'null') {
                    $row_data[$index] = null;
                }

                if ($row_data[$index] === '' && in_array($field, $required_fields)) {
                    throw new PendingException("'{$field}' in row '{$row_number}' is mandatory and must have a value.");
                } else {
                    $dbdata[$field] = $row_data[$index];
                }
            }

            $dbconnection->insert_record($dbconfig['dbtable'], $dbdata);
        }

        // Store the database settings so they don't have to be applied to the source settings page.
        foreach ($dbconfig as $setting => $value) {
            set_config('database_' . $setting, $value, "totara_sync_source_{$short_element}_database");
        }

        // Set the import fields so they don't have to be applied.
        foreach ($fields as $setting) {
            set_config('import_' . $setting, 1, "totara_sync_source_{$short_element}_database");
        }
    }
}
