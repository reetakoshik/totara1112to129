<?php
/*
 * This file is part of Totara LMS
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
 * @package totara_completionimport
 */

namespace totara_completionimport;

defined('MOODLE_INTERNAL') || die();

/**
 * Class csv_import
 * @package totara_completionimport
 *
 * Contains methods used for the processing of csv data when importing completions.
 */
class csv_import {

    /**
     * Performs all actions necessary to import data from a csv to course or certification
     * completion data within Totara.
     *
     * This does not handle files. The contents of the csv file must have been loaded into a string.
     *
     * Pre-processing, such as converting to UTF-8, is done by methods called within this function.
     *
     * @param string $content containing full content of csv file.
     * @param string $importname either course or certification.
     * @param int $importtime timestamp of import time.
     * @return array of any error strings.
     */
    public static function import($content, $importname, $importtime) {
        global $CFG, $DB;
        require_once($CFG->libdir . '/csvlib.class.php');
        require_once($CFG->dirroot . '/totara/completionimport/lib.php');

        // Increase memory limit.
        raise_memory_limit(MEMORY_EXTRA);

        // Stop time outs, this might take a while.
        \core_php_time_limit::raise(0);

        // Get any evidence custom fields.
        $customfields = get_evidence_customfields();
        $pluginname = 'totara_completionimport_' . $importname;
        $tablename = get_tablename($importname);

        // The names of delimiter and separator have been swapped in these Totara params. We'll start using
        // names that match up with the class/function APIs at this point.
        $csvenclosure = get_default_config($pluginname, 'csvdelimiter', TCI_CSV_DELIMITER);
        $csvdelimiter = get_default_config($pluginname, 'csvseparator', TCI_CSV_SEPARATOR);
        $csvencoding = get_default_config($pluginname, 'csvencoding', TCI_CSV_ENCODING);
        $csvdateformat = get_default_config($pluginname, 'csvdateformat', TCI_CSV_DATE_FORMAT);

        $iid = \csv_import_reader::get_new_iid('completionimport');
        $csvimport = new \csv_import_reader($iid, 'completionimport');
        $csvimport->load_csv_content($content, $csvencoding, $csvdelimiter, null, $csvenclosure);

        $allcolumns = $csvimport->get_columns();

        $columnerrors = self::validate_columns($allcolumns, $importname);
        if ($columnerrors) {
            return $columnerrors;
        }

        $csvimport->init();
        $import = array();
        // For backwards compatibility, rowcount is number 1 for columns and then data rows begin below that at row 2.
        $rownumber = 2;
        while ($item = $csvimport->next()) {
            $import[] = self::new_row_object($item, $rownumber, $allcolumns, $importtime, $csvdateformat, $customfields);
            $rownumber++;
        }
        $DB->insert_records_via_batch($tablename, $import);
        import_data_checks($importname, $importtime);

        // Start transaction, we are dealing with live data now...
        $transaction = $DB->start_delegated_transaction();

        // Put into evidence any courses / certifications not found.
        create_evidence($importname, $importtime);

        // Run the specific course enrolment / certification assignment.
        $functionname = 'import_' . $importname;
        $errors = $functionname($importname, $importtime);

        // End the transaction.
        $transaction->allow_commit();

        // Purge the progress caches to ensure course and program progress is re-calcuated
        \totara_program\progress\program_progress_cache::purge_progressinfo_caches();
        \completion_info::purge_progress_caches();

        return $errors;
    }

    /**
     * Ensures no unexpected columns are present and that no required columns are missing.
     *
     * @param array $columns the names of columns within a csv file.
     * @param string $importname either course or certification.
     * @return array of any error strings.
     * @throws \coding_exception
     */
    private static function validate_columns($columns, $importname) {
        global $CFG;
        require_once($CFG->dirroot . '/totara/completionimport/lib.php');

        $errors = array();
        $requiredcolumns = get_columnnames($importname);
        // Get any evidence custom fields.
        $customfields = get_evidence_customfields();
        $allcolumns = array_merge(get_columnnames($importname), $customfields);

        if (!empty($columns)) {
            foreach ($columns as $column) {
                if (!in_array($column, $allcolumns)) {
                    $field = new \stdClass();
                    $field->columnname = $column;
                    $errors[] = get_string('unknowncolumn', 'totara_completionimport', $field);
                }
            }
        }

        // Check for required fields.
        foreach ($requiredcolumns as $columnname) {
            if (empty($columns) or !in_array($columnname, $columns)) {
                $field = new \stdClass();
                $field->columnname = $columnname;
                $errors[] = get_string('missingrequiredcolumn', 'totara_completionimport', $field);
            }
        }

        return $errors;
    }

    /**
     * Takes an array of data for a row from the csv file and returns a record that is ready to be
     * added to a totara_compl_import_ database table. It has not been added to the database yet.
     *
     * Assumes validation of columns has already taken place.
     *
     * Custom field column names must be in the $customfields array or they will be treated as normal columns.
     *
     * @param array $item data in the csv row.
     * @param int $rownumber.
     * @param array $allcolumns all columns being imported.
     * @param int $importtime timestamp of import time.
     * @param string $csvdateformat.
     * @param array $customfields - shortnames of custom fields being used..
     * @return \stdClass containg data for a new completion import record (not yet saved to the database).
     */
    private static function new_row_object($item, $rownumber, $allcolumns, $importtime, $csvdateformat, $customfields = array()) {
        global $USER;

        $rowobject = new \stdClass();

        if (count($item) !== count($allcolumns)) {
            $rowobject->importerror = 1;
            $rowobject->importerrormsg = 'fieldcountmismatch;';
        } else {
            $rowobject->importerror = 0;
            $rowobject->importerrormsg = '';
        }

        $customfielddata = array();
        foreach ($item as $key => $value) {
            if (empty($allcolumns[$key])) {
                // Likely due to a 'fieldcountmismatch' error.
                break;
            }

            $column = $allcolumns[$key];

            if (!empty($customfields) and in_array($column, $customfields)) {
                $customfielddata[$column] = $value;
            } else {
                $rowobject->{$column} = $value;
            }
        }

        $rowobject->timecreated = $importtime;
        $rowobject->timeupdated = 0;
        $rowobject->importuserid = $USER->id;
        $rowobject->rownumber = $rownumber;
        $rowobject->completiondateparsed = totara_date_parse_from_format($csvdateformat, $rowobject->completiondate);

        if (!empty($customfielddata)) {
            $rowobject->customfields = serialize($customfielddata);
        }

        return $rowobject;
    }
}