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
 * @author Russell England <russell.england@catalyst-eu.net>
 * @package totara
 * @subpackage completionimpot
 */

defined('MOODLE_INTERNAL') || die;

// TCI = Totara Completion Import.
define('TCI_SOURCE_EXTERNAL', 0);
define('TCI_SOURCE_UPLOAD', 1);

define('TCI_CSV_DELIMITER', '"'); // Default for fgetcsv() although the naming in fgetcsv is the wrong way around IMHO.
define('TCI_CSV_SEPARATOR', 'comma'); // Default for fgetcsv() although the naming in fgetcsv is the wrong way around IMHO.
define('TCI_CSV_DATE_FORMAT', 'Y-m-d'); // Default date format.
define('TCI_CSV_ENCODING', 'UTF8'); // Default file encoding.

/**
 * From 9.0. On upgrade, setting was copied from overrideactivecertification setting, value 0.
 * Imported completion records should be written directly to history, regardless of the state the user is in.
 */
const COMPLETION_IMPORT_TO_HISTORY = 0;

/**
 * From 9.0.
 * Imported completion records should mark the user complete if the user is currently incomplete, otherwise
 * they go into history.
 */
const COMPLETION_IMPORT_COMPLETE_INCOMPLETE = 2;

/**
 * From 9.0. On upgrade, setting was copied from overrideactivecertification setting, value 1.
 * Imported completion records should mark the user complete if the import completion date is newer than the
 * user's current completion date, otherwise they go into history.
 */
const COMPLETION_IMPORT_OVERRIDE_IF_NEWER = 1;

/**
 * Returns a 3 character prefix for a temporary file name
 *
 * @param string $importname
 * @return string 3 character prefix
 */
function get_tempprefix($importname) {
    $prefix = array(
        'course' => 'cou',
        'certification'  => 'cer'
    );
    return $prefix[$importname];
}

/**
 * Returns an array of column names for the specific import
 *
 * @param string $importname
 * @return array column names
 */
function get_columnnames($importname) {
    $columns = array();
    $columns['course'] = array(
        'username',
        'courseshortname',
        'courseidnumber',
        'completiondate',
        'grade'
    );
    $columns['certification'] = array(
        'username',
        'certificationshortname',
        'certificationidnumber',
        'completiondate',
        'duedate',
    );
    return $columns[$importname];
}

/**
 * Returns an array of evidence custom fields.
 *
 * @return array custom field names
 */
function get_evidence_customfields() {
    global $DB;

    $customfields = array();
    $rs = $DB->get_records('dp_plan_evidence_info_field', null, 'sortorder', 'id, shortname, datatype');
    foreach ($rs as $record) {
        if ($record->datatype == 'file' || $record->datatype == 'multiselect') {
            // Don't allow file or multiselect custom fields.
            continue;
        }
        if ($record->datatype == 'datetime' && $record->shortname == str_replace(' ', '', get_string('evidencedatecompletedshort', 'totara_plan'))) {
            // We don't want to include a completion date custom field as this is taken from the completiondate field in the upload.
            continue;
        }

        $customfields[$record->id] = 'customfield_' . $record->shortname;
    }
    return $customfields;
}

/**
 * Returns the import table name for a specific import
 *
 * @param string $importname
 * @return string tablename
 */
function get_tablename($importname) {
    $tablenames = array(
        'course' => 'totara_compl_import_course',
        'certification' => 'totara_compl_import_cert'
    );
    return $tablenames[$importname];
}

/**
 * Returns the SQL to compare the shortname if not empty or idnumber if shortname is empty
 *
 * @deprecated since Totara 12
 * @param string $relatedtable eg: "{course}" if a table or 'c' if its an alias
 * @param string $importtable eg: "{totara_compl_import_course}" or "i"
 * @param string $shortnamefield courseshortname or certificationshortname
 * @param string $idnumberfield courseidnumber or certificationidnumber
 * @return string Where condition
 */
function get_shortnameoridnumber($relatedtable, $importtable, $shortnamefield, $idnumberfield) {
    global $DB;

    debugging(__FUNCTION__ . ' was deprecated in Totara 12. There is now a resolved reference to the course/cert on the import record', DEBUG_DEVELOPER);

    $notemptyshortname = $DB->sql_isnotempty($importtable, "{$importtable}.{$shortnamefield}", true, false);
    $notemptyidnumber = $DB->sql_isnotempty($importtable, "{$importtable}.{$idnumberfield}", true, false);
    $emptyshortname = $DB->sql_isempty($importtable, "{$importtable}.{$shortnamefield}", true, false);
    $emptyidnumber = $DB->sql_isempty($importtable, "{$importtable}.{$idnumberfield}", true, false);
    $shortnameoridnumber = "
        ({$notemptyshortname} AND {$notemptyidnumber}
            AND {$relatedtable}.shortname = {$importtable}.{$shortnamefield}
            AND {$relatedtable}.idnumber = {$importtable}.{$idnumberfield})
        OR ({$notemptyshortname} AND {$emptyidnumber}
            AND {$relatedtable}.shortname = {$importtable}.{$shortnamefield})
        OR ({$emptyshortname} AND {$notemptyidnumber}
            AND {$relatedtable}.idnumber = {$importtable}.{$idnumberfield})
        ";
    return $shortnameoridnumber;
}

/**
 * Returns the standard filter for the import table and related parameters
 *
 * @global object $USER
 * @param int $importtime time() of the import
 * @param string $alias alias to use
 * @return array array($sql, $params)
 */
function get_importsqlwhere($importtime, $alias = 'i.') {
    global $USER;
    $sql = "WHERE {$alias}importuserid = :userid
            AND {$alias}timecreated = :timecreated
            AND {$alias}importerror = 0
            AND {$alias}timeupdated = 0
            AND {$alias}importevidence = 0 ";
    $params = array('userid' => $USER->id, 'timecreated' => $importtime);
    return array($sql, $params);
}

/**
 * Gets the config value, sets the value if it doesn't exist
 *
 * @param string $pluginname name of plugin
 * @param string $configname name of config value
 * @param mixed $default config value
 * @return mixed either the current config value or the default
 */
function get_default_config($pluginname, $configname, $default) {
    $configvalue = get_config($pluginname, $configname);
    if ($configvalue == null) {
        $configvalue = $default;
        set_config($configname, $configvalue, $pluginname);
    }
    return $configvalue;
}

/**
 * Sanity check on data imported from the csv file
 *
 * @global object $DB
 * @param string $importname name of import
 * @param int $importtime time of this import
 */
function import_data_checks($importname, $importtime) {
    global $DB, $CFG;

    // First up apply case insensitive matching if required.
    totara_completionimport_apply_case_insensitive_mapping($importname, $importtime);

    // Find and set reference to the course/cert records that actually exist.
    // Must be done before import data checks is actually run.
    totara_completionimport_resolve_references($importname, $importtime);

    list($sqlwhere, $stdparams) = get_importsqlwhere($importtime, '');

    $shortnamefield = $importname . 'shortname';
    $idnumberfield = $importname . 'idnumber';

    $tablename = get_tablename($importname);
    $columnnames = get_columnnames($importname);
    $pluginname = 'totara_completionimport_' . $importname;
    $csvdateformat = get_default_config($pluginname, 'csvdateformat', TCI_CSV_DATE_FORMAT);

    if (in_array('username', $columnnames)) {
        // Blank User names.
        $params = array_merge($stdparams, array('errorstring' => 'blankusername;'));
        $sql = "UPDATE {{$tablename}}
                SET importerrormsg = " . $DB->sql_concat('importerrormsg', ':errorstring') . "
                {$sqlwhere}
                AND " . $DB->sql_isempty($tablename, 'username', true, false);
        $DB->execute($sql, $params);

        // Missing User names.
        // Reference to mnethostid in subquery allows us to benefit from an index on user table.
        // This tool does not support importing historic records from networked sites
        // so local site id alway used.
        $params = array_merge($stdparams,
            array('errorstring' => 'usernamenotfound;', 'mnetlocalhostid' => $CFG->mnet_localhost_id));
        $sql = "UPDATE {{$tablename}}
                SET importerrormsg = " . $DB->sql_concat('importerrormsg', ':errorstring') . "
                {$sqlwhere}
                AND " . $DB->sql_isnotempty($tablename, 'username', true, false) . "
                AND NOT EXISTS (SELECT {user}.id FROM {user}
                WHERE {user}.username = {{$tablename}}.username AND {user}.mnethostid = :mnetlocalhostid)";
        $DB->execute($sql, $params);
    }

    if (in_array('completiondate', $columnnames)) {
        // Blank completion date.
        $params = array_merge($stdparams, array('errorstring' => 'blankcompletiondate;'));
        $sql = "UPDATE {{$tablename}}
                SET importerrormsg = " . $DB->sql_concat('importerrormsg', ':errorstring') . "
                {$sqlwhere}
                AND " . $DB->sql_isempty($tablename, 'completiondate', true, false);
        $DB->execute($sql, $params);

        // Check for invalid completion date.
        if (!empty($csvdateformat)) {
            // There is a date format so check it.
            $sql = "SELECT id, completiondate
                    FROM {{$tablename}}
                    {$sqlwhere}
                    AND " . $DB->sql_isnotempty($tablename, 'completiondate', true, false);

            $timecompleteds = $DB->get_recordset_sql($sql, $stdparams);
            if ($timecompleteds->valid()) {
                foreach ($timecompleteds as $timecompleted) {
                    if (!totara_completionimport_validate_date($csvdateformat, $timecompleted->completiondate)) {
                        $sql = "UPDATE {{$tablename}}
                                SET importerrormsg = " . $DB->sql_concat('importerrormsg', ':errorstring') . "
                                WHERE id = :importid";
                        $DB->execute($sql, array('errorstring' => 'invalidcompletiondate;', 'importid' => $timecompleted->id));
                    }
                }
            }
            $timecompleteds->close();
        }
    }

    if (in_array('grade', $columnnames)) {
        // Assuming the grade is mandatory, so check for blank grade.
        $params = array_merge($stdparams, array('errorstring' => 'blankgrade;'));
        $sql = "UPDATE {{$tablename}}
                SET importerrormsg = " . $DB->sql_concat('importerrormsg', ':errorstring') . "
                {$sqlwhere}
                AND " . $DB->sql_isempty($tablename, 'grade', true, false);
        $DB->execute($sql, $params);
    }

    // Duplicates.
    if (in_array($importname . 'username', $columnnames) && in_array($shortnamefield, $columnnames)
            && in_array($idnumberfield, $columnnames)) {
        $sql = "SELECT " . $DB->sql_concat('username', $shortnamefield, $idnumberfield) . " AS uniqueid,
                    username,
                    {$shortnamefield},
                    {$idnumberfield},
                    COUNT(*) AS count
                FROM {{$tablename}}
                {$sqlwhere}
                GROUP BY username, {$shortnamefield}, {$idnumberfield}
                HAVING COUNT(*) > 1";
        $duplicategroups = $DB->get_recordset_sql($sql, $stdparams);
        if ($duplicategroups->valid()) {
            foreach ($duplicategroups as $duplicategroup) {
                // Keep the first record, consider the others as duplicates.
                $sql = "SELECT id
                        FROM {{$tablename}}
                        {$sqlwhere}
                        AND username = :username
                        AND {$shortnamefield} = :shortname
                        AND {$idnumberfield} = :idnumber
                        ORDER BY id";
                $params = array(
                        'username' => $duplicategroup->username,
                        'shortname' => $duplicategroup->$shortnamefield,
                        'idnumber' => $duplicategroup->$idnumberfield
                    );
                $params = array_merge($stdparams, $params);
                $keepid = $DB->get_field_sql($sql, $params, IGNORE_MULTIPLE);

                $params['keepid'] = $keepid;
                $params['errorstring'] = 'duplicate;';
                $sql = "UPDATE {{$tablename}}
                        SET importerrormsg = " . $DB->sql_concat('importerrormsg', ':errorstring') . "
                        {$sqlwhere}
                        AND id <> :keepid
                        AND username = :username
                        AND {$shortnamefield} = :shortname
                        AND {$idnumberfield} = :idnumber";
                $DB->execute($sql, $params);
            }
        }
        $duplicategroups->close();
    }

    // Unique ID numbers.
    if (in_array($shortnamefield, $columnnames) && in_array($idnumberfield, $columnnames)) {
        // I 'think' the count has to be included in the select even though we only need having count().
        $notemptyidnumber = $DB->sql_isnotempty($tablename, "{{$tablename}}.{$idnumberfield}", true, false);
        $shortimportname = sql_collation($shortnamefield);
        $sql = "SELECT u.{$idnumberfield}, COUNT(*) AS shortnamecount
                FROM (SELECT DISTINCT {$shortimportname}, {$idnumberfield}
                        FROM {{$tablename}}
                        {$sqlwhere}
                        AND {$notemptyidnumber}) u
                GROUP BY u.{$idnumberfield}
                HAVING COUNT(*) > 1";
        $idnumbers = $DB->get_records_sql($sql, $stdparams);
        $idnumberlist = array_keys($idnumbers);

        if (count($idnumberlist)) {
            foreach ($idnumberlist as $i => $idnumber) {
                list($idsql, $idparams) = $DB->get_in_or_equal($idnumber, SQL_PARAMS_NAMED, 'param');
                $params = array_merge($stdparams, $idparams);
                $where = "{$sqlwhere} AND {$idnumberfield} {$idsql}";
                $params['errorstring'] = 'duplicateidnumber;';
                $sql = "UPDATE {{$tablename}}
                    SET importerrormsg = " . $DB->sql_concat('importerrormsg', ':errorstring') . "
                    {$where}";
                $DB->execute($sql, $params);
            }
        }
    }

    if (in_array($shortnamefield, $columnnames) && in_array($idnumberfield, $columnnames)) {
        // Blank shortname and id number.
        $params = array_merge($stdparams, array('errorstring' => $importname . 'blankrefs;'));
        $sql = "UPDATE {{$tablename}}
                SET importerrormsg = " . $DB->sql_concat('importerrormsg', ':errorstring') . "
                {$sqlwhere}
                AND " . $DB->sql_isempty($tablename, $shortnamefield, true, false) . "
                AND " . $DB->sql_isempty($tablename, $idnumberfield, true, false);
        $DB->execute($sql, $params);

        if (in_array($importname, array('course'))) {
            // Course exists but there is no manual enrol record.
            $params = array('enrolname' => 'manual', 'errorstring' => 'nomanualenrol;');
            $params = array_merge($stdparams, $params);
            $sql = "UPDATE {{$tablename}}
                    SET importerrormsg = " . $DB->sql_concat('importerrormsg', ':errorstring') . "
                    {$sqlwhere}
                    AND courseid IS NOT NULL
                    AND NOT EXISTS (SELECT {enrol}.id
                                FROM {enrol}
                                WHERE {enrol}.enrol = :enrolname
                                AND {enrol}.courseid = courseid)";
            $DB->execute($sql, $params);
        }
    }

    // Set import error so we ignore any records that have an error message from above.
    $params = array_merge($stdparams, array('importerror' => 1));
    $sql = "UPDATE {{$tablename}}
            SET importerror = :importerror
            {$sqlwhere}
            AND " . $DB->sql_isnotempty($tablename, 'importerrormsg', true, true); // Note text = true.
    $DB->execute($sql, $params);
}

/**
 * Applies case insensitive matching.
 *
 * Please note this function does not do what you expect.
 * When enabled, course short names will be matched case insensitively.
 *
 *      1. If there are two or more courses with shortnames that use different case but have matching idnumbers then the name of the existing course will be matched.
 *      2. If the inital match fails, the shortname for the duplicate records with matching idnumbers will be used.
 *
 * @param string $importname
 * @param int $importtime
 */
function totara_completionimport_apply_case_insensitive_mapping($importname, $importtime) {
    global $DB;

    $pluginname = 'totara_completionimport_' . $importname;
    $forcecaseinsensitive = get_default_config($pluginname, 'forcecaseinsensitive' . $importname, false);
    if (!$forcecaseinsensitive) {
        return;
    }

    list($sqlwhere, $stdparams) = get_importsqlwhere($importtime, '');

    $shortnamefield = $importname . 'shortname';
    $idnumberfield = $importname . 'idnumber';

    $tablename = get_tablename($importname);
    $columnnames = get_columnnames($importname);

    // Unique ID numbers.
    if (in_array($shortnamefield, $columnnames) && in_array($idnumberfield, $columnnames)) {
        // I 'think' the count has to be included in the select even though we only need having count().
        $notemptyidnumber = $DB->sql_isnotempty($tablename, "{{$tablename}}.{$idnumberfield}", true, false);
        $shortimportname = sql_collation($shortnamefield);
        $sql = "SELECT u.{$idnumberfield}, COUNT(*) AS shortnamecount
                  FROM (
                        SELECT DISTINCT {$shortimportname}, {$idnumberfield}
                          FROM {{$tablename}}
                               {$sqlwhere} AND
                               {$notemptyidnumber}
                       ) u
              GROUP BY u.{$idnumberfield}
                HAVING COUNT(*) > 1";
        $idnumbers = $DB->get_records_sql($sql, $stdparams);
        $idnumberlist = array_keys($idnumbers);

        if (count($idnumberlist)) {
            foreach ($idnumberlist as $idnumber) {

                $idnumber_param = $DB->get_unique_param();
                $params = array_merge($stdparams, [$idnumber_param => $idnumber]);
                $where = "{$sqlwhere} AND {$idnumberfield} = :$idnumber_param";

                // First try to find the course/certification shortname in course/prog table.
                $tblname = $importname == 'course' ? $importname : 'prog';
                $idnumber_like = $DB->sql_like('idnumber', ':' . $idnumber_param, false, false);
                $sql = "SELECT shortname 
                          FROM {{$tblname}} 
                         WHERE {$idnumber_like}";
                $record = $DB->get_record_sql($sql, [$idnumber_param => $idnumber], IGNORE_MULTIPLE);
                if ($record) {
                    $value = $record->shortname;
                } else {
                    // No records exists, match the shortname from the first record.
                    $sql = "SELECT {$shortnamefield} 
                              FROM {{$tablename}} 
                                   {$where}";
                    $record = $DB->get_record_sql($sql, $params, IGNORE_MULTIPLE);
                    $value = $record->{$shortnamefield};
                }
                $update = "UPDATE {{$tablename}}
                              SET {$shortnamefield} = :{$shortnamefield}
                                  {$where}";
                $whereparams = array_merge($params, array($shortnamefield => $value));
                $DB->execute($update, $whereparams);
            }
        }
    }
}

/**
 * Works out if import records relate to actual courses/certifications, and updates them with a reference ID if they do.
 *
 * @param string $importname Either 'course' or 'certification'
 * @param int $importtime
 */
function totara_completionimport_resolve_references($importname, $importtime) {
    global $DB;

    // Don't just trust it!
    $importname = ($importname === 'course') ? 'course' : 'certification';

    $tablename = get_tablename($importname);
    list($timewhere, $timeparams) = get_importsqlwhere($importtime);
    list($timewhereraw, $timeparamsraw) = get_importsqlwhere($importtime, '');

    if ($importname === 'course') {
        $ref_field = 'courseid';
        $rs = $DB->get_recordset('course', null, '', 'id, idnumber, shortname');
    } else {
        $ref_field = 'certificationid';
        $rs = $DB->get_recordset_select('prog', 'certifid IS NOT NULL', null, '', 'id, idnumber, shortname');
    }

    $map_idnumber = [];
    $map_shortname = [];
    foreach ($rs as $ref) {

        // Trim the shortname and idnumber. We are also trimming the imported shortname and idnumber for the matching.
        $ref->shortname = trim($ref->shortname);
        $ref->idnumber = trim($ref->idnumber);

        if (!empty($ref->idnumber)) {
            $map_idnumber[$ref->idnumber] = $ref;
        }
        // Shortname must be set for a course or certification.
        $map_shortname[$ref->shortname] = $ref;
    }
    $rs->close();

    $sql = "SELECT i.{$importname}shortname AS shortname, i.{$importname}idnumber AS idnumber, COUNT(i.id) AS instancecount
              FROM {{$tablename}} i
                   {$timewhere}
          GROUP BY {$importname}shortname, {$importname}idnumber";
    $rs = $DB->get_recordset_sql($sql, $timeparams);

    foreach ($rs as $importrow) {

        $idnumber = trim($importrow->idnumber);
        $shortname = trim($importrow->shortname);

        if ($idnumber === '' && $shortname === '') {
            // Both are empty, no possible match.
            continue;
        }

        if ($shortname != '' && $idnumber != '' && isset($map_shortname[$shortname]) && $map_shortname[$shortname]->idnumber === $idnumber) {
            // Perfect match! Shortname and idnumber both set and match.
            $sql = "UPDATE {{$tablename}}
                       SET {$ref_field} = :ref
                           {$timewhereraw} AND {$importname}idnumber = :idnumber AND {$importname}shortname = :shortname";
            $params = [
                'ref' => $map_shortname[$shortname]->id,
                'idnumber' => $importrow->idnumber,
                'shortname' => $importrow->shortname,
            ];
            $DB->execute($sql, $timeparamsraw + $params);
        } else if ($shortname != '' && $idnumber == '' && isset($map_shortname[$shortname])) {
            // Shortname set, idnumber not set, and shortname matches.
            $sql = "UPDATE {{$tablename}}
                       SET {$ref_field} = :ref
                        {$timewhereraw} AND ({$importname}idnumber IS NULL OR {$importname}idnumber = '') AND {$importname}shortname = :shortname";
            $params = [
                'ref' => $map_shortname[$shortname]->id,
                'shortname' => $importrow->shortname,
            ];
            $DB->execute($sql, $timeparamsraw + $params);
        } else if ($shortname == '' && $idnumber != '' && isset($map_idnumber[$idnumber])) {
            // Shortname not set, idnumber set, and idnumber matches.
            $sql = "UPDATE {{$tablename}}
                       SET {$ref_field} = :ref
                           {$timewhereraw} AND ({$importname}shortname IS NULL OR {$importname}shortname = '') AND {$importname}idnumber = :idnumber";
            $params = [
                'ref' => $map_idnumber[$idnumber]->id,
                'idnumber' => $importrow->idnumber,
            ];
            $DB->execute($sql, $timeparamsraw + $params);
        }
    }
    $rs->close();
    unset($map_idnumber, $map_shortname); // Ensure we give back this memory very explicitly.
}

/**
 * Generic function for creating evidence from mismatched courses / certifications.
 *
 * @global object $DB
 * @global object $USER
 * @param string $importname name of import
 * @param int $importtime time of import
 */
function create_evidence($importname, $importtime) {
    global $DB;

    list($sqlwhere, $params) = get_importsqlwhere($importtime);

    $tablename = get_tablename($importname);
    $shortnamefield = $importname . 'shortname';
    $idnumberfield = $importname . 'idnumber';

    if ($importname == 'course') {
        // Add any missing courses to other training (evidence).
        $sql = "SELECT i.id as importid, u.id userid, i.{$shortnamefield}, i.{$idnumberfield}, i.completiondateparsed, i.grade, i.customfields
                FROM {{$tablename}} i
                JOIN {user} u ON u.username = i.username
                {$sqlwhere}
                  AND i.courseid IS NULL";
    } else if ($importname == 'certification') {
        // Add any missing certifications to other training (evidence).
        $sql = "SELECT i.id as importid, u.id userid, i.{$shortnamefield},  i.{$idnumberfield}, i.completiondateparsed, i.customfields
                FROM {{$tablename}} i
                JOIN {user} u ON u.username = i.username
                {$sqlwhere}
                AND i.certificationid IS NULL";
    }


    $pluginname = 'totara_completionimport_' . $importname;
    $evidencetype = get_default_config($pluginname, 'evidencetype', null);

    $evidencefields = array(
        'evidencedatefield' => get_default_config($pluginname, 'evidencedatefield', null),
        'evidencedescriptionfield' => get_default_config($pluginname, 'evidencedescriptionfield', null)
    );

    $csvdateformat = get_default_config($pluginname, 'csvdateformat', TCI_CSV_DATE_FORMAT);

    $evidences = $DB->get_recordset_sql($sql, $params);

    // Insert the evidence data.
    foreach ($evidences as $evidence) {
        create_evidence_item($evidence, $evidencetype, $csvdateformat, $tablename, $shortnamefield, $idnumberfield, $importname, $evidencefields);
    }

    $evidences->close();
}

/**
 * Processor for insert batch iterator
 *
 * @global object $USER
 * @global object $DB
 * @param object $item record object
 * @param int $evidencetype default evidence type
 * @param string $csvdateformat csv date format - obsolete, unused
 * @param string $tablename name of import table
 * @param string $shortnamefield name of short name field, either certificationshortname or courseshortname
 * @param string $idnumberfield name of id number, either certificationidnumber or courseidnumber
 * @param string $importname 'course' or 'completion'
 * @param array  $evidencefields field mappings
 * @return object $data record to insert
 */
function create_evidence_item($item, $evidencetype, $csvdateformat, $tablename, $shortnamefield, $idnumberfield, $importname, array $evidencefields) {
    global $USER, $DB;

    // Create an evidence name.
    $itemname = '';
    if (!empty($item->$shortnamefield)) {
        $itemname = get_string('evidence_shortname_' . $importname, 'totara_completionimport', $item->$shortnamefield);
    } else if (!empty($item->$idnumberfield)) {
        $itemname = get_string('evidence_idnumber_' . $importname, 'totara_completionimport', $item->$idnumberfield);
    }

    // Completion time.
    $timecompleted = null;
    $timestamp = $item->completiondateparsed;
    if (!empty($timestamp)) {
        $timecompleted = $timestamp;
    }

    // Auto create a description.
    // This description will be used if a description custom field exists and if data is nor set in the upload.
    $description = '';
    foreach ($item as $field => $value) {
        if (!in_array($field, array('userid', 'customfields'))) {
            $description .= html_writer::tag('p', get_string('evidence_' . $field, 'totara_completionimport', $value));
        }
    }

    // Add the evidence record.
    $data = new stdClass();
    $data->name = $itemname;
    $data->evidencetypeid = $evidencetype;
    $data->timemodified = time();
    $data->userid = $item->userid;
    $data->timecreated = $data->timemodified;
    $data->usermodified = $USER->id;
    $data->readonly = 1;

    $evidenceid = $DB->insert_record('dp_plan_evidence', $data, true);

    // Add the evidence custom fields.
    $customfields = $DB->get_records('dp_plan_evidence_info_field');
    $uploadedcustomfields = unserialize($item->customfields);

    // Create object to store the new custom field data.
    $newcustomfields = new stdClass();
    $newcustomfields->id = $evidenceid;

    // Loop through all custom fields.
    foreach ($customfields as $cf) {

        $datafield = 'customfield_' . $cf->shortname;
        $datavalue = null;

        // We are now going to add the custom field data using the below criteria,
        // 1. If the custom field exists in the upload, add the data.
        // 2. If the custom field exists in the upload, but its value is empty, add the custom field default data.
        // 3. If the custom field does not exists in the upload, use special case to handle description and datecompleted.

        // The custom field is present in the import and it's value is not empty.
        // Add the custom field data.
        if (isset($uploadedcustomfields['customfield_' . $cf->shortname]) && $uploadedcustomfields['customfield_' . $cf->shortname] != '') {
            switch ($cf->datatype) {
                case 'datetime':
                    $datecompleted = totara_date_parse_from_format($csvdateformat, $uploadedcustomfields['customfield_' . $cf->shortname]);
                    $datavalue = empty($datecompleted) ? null : $datecompleted;
                    break;
                case 'url':
                    $datavalue = array('url' => $uploadedcustomfields['customfield_' . $cf->shortname]);
                    break;
                default:
                    $datavalue = $uploadedcustomfields['customfield_' . $cf->shortname];
            }
        }

        // The custom field is present in the import but it's value is empty.
        // Add the custom fields default data.
        if (isset($uploadedcustomfields['customfield_' . $cf->shortname]) && $uploadedcustomfields['customfield_' . $cf->shortname] == '') {
            switch ($cf->datatype) {
                case 'datetime':
                    $datavalue = empty($cf->defaultdata) ? null : $cf->defaultdata;
                    break;
                case 'url':
                    $datavalue = array(
                        'url' => $cf->defaultdata,
                        'text' => $cf->param1,
                        'target' => $cf->param2
                    );
                    break;
                default:
                    $datavalue = $cf->defaultdata;
            }
        }

        // The custom field is not present in the import.
        // If description or datecompleted fields, add the auto-generated description and upload course completiondate data.
        if (!isset($uploadedcustomfields['customfield_' . $cf->shortname])) {
            // Description field of type textarea.
            if ($cf->shortname == $evidencefields['evidencedescriptionfield'] && $cf->datatype == 'textarea') {
                $datavalue = $description;
            }
            // Datecompleted field of type datetime.
            if ($cf->shortname == $evidencefields['evidencedatefield'] && $cf->datatype == 'datetime') {
                $datavalue = $timecompleted;
            }
        }

        $newcustomfields->$datafield  = $datavalue;
    }

    // Add the custom fields.
    if ($customfields) {
        customfield_save_data($newcustomfields, 'evidence', 'dp_plan_evidence', true);
    }

    // Mark upload as competed.
    $update = new stdClass();
    $update->id = $item->importid;
    $update->timeupdated = time();
    $update->importevidence = 1;
    $update->evidenceid = $evidenceid;
    $DB->update_record($tablename, $update, true);

    return;
}

/**
 * Import the course completion data
 *
 * 1. Gets records from the import table that have no errors or haven't gone to evidence
 * 2. Bulk enrol users - used enrol_cohort_sync() in /enrol/cohort/locallib.php as a reference
 * 3. Course completion stuff copied from process_course_completion_crit_compl()
 *    and process_course_completions() both in /backup/moodle2/restore_stepslib.php
 *
 * Note that the course_completed event is not triggered, because it would cause this function to take even longer to
 * run (while the user waits), and we assume that some other cron task will pick up on the fact that the records have
 * changed and do what needs to be done (such as process program completion).
 *
 * @global object $DB
 * @global object $CFG
 * @param string $importname name of import
 * @param int $importtime time of import
 * @return array
 */
function import_course($importname, $importtime) {
    global $DB, $CFG, $USER;

    require_once($CFG->libdir . '/enrollib.php'); // Used for enroling users on courses.

    $errors = array();
    $updateids = array();
    $users = array();
    $enrolledusers = array();
    $completions = array();
    $stats = array();
    $deletedcompletions = array();
    $completion_history = array();
    $historicalduplicate = array();
    $historicalrecordindb = array();
    $coursecompletionlogs = array();

    $pluginname = 'totara_completionimport_' . $importname;
    $overridecurrentcompletion = get_default_config($pluginname, 'overrideactive' . $importname, false);

    list($sqlwhere, $params) = get_importsqlwhere($importtime);
    $params['enrolname'] = 'manual';

    $tablename = get_tablename($importname);
    $sql = "SELECT i.id as importid,
                    i.completiondateparsed,
                    i.grade,
                    c.id as courseid,
                    u.id as userid,
                    e.id as enrolid,
                    ue.id as userenrolid,
                    ue.status as userenrolstatus,
                    cc.id as coursecompletionid,
                    cc.timestarted,
                    cc.timeenrolled,
                    cc.timecompleted as currenttimecompleted
            FROM {{$tablename}} i
            JOIN {user} u ON u.username = i.username
            JOIN {course} c ON c.id = i.courseid
            JOIN {enrol} e ON e.courseid = c.id AND e.enrol = :enrolname
            LEFT JOIN {user_enrolments} ue ON (ue.enrolid = e.id AND ue.userid = u.id)
            LEFT JOIN {course_completions} cc ON cc.userid = u.id AND cc.course = c.id
            {$sqlwhere}
            ORDER BY courseid, userid, completiondateparsed DESC, grade DESC";

    $courses = $DB->get_recordset_sql($sql, $params);
    if ($courses->valid()) {
        $plugin = enrol_get_plugin('manual');
        $timestart = $importtime;
        $timeend = 0;
        $enrolcount = 1;
        $enrolid = 0;
        $currentuser = 0;
        $currentcourse = 0;

        foreach ($courses as $course) {
            if (empty($enrolid) || ($enrolid != $course->enrolid) || (($enrolcount % BATCH_INSERT_MAX_ROW_COUNT) == 0)) {
                // Delete any existing course completions we are overriding.
                if (!empty($deletedcompletions)) {
                    $DB->delete_records_list('course_completions', 'id', $deletedcompletions);
                    $deletedcompletions = array();
                }

                if (!empty($completions)) {
                    // Batch import completions.
                    $DB->insert_records_via_batch('course_completions', $completions);
                    $completions = array();
                }

                if (!empty($stats)) {
                    // Batch import block_totara_stats.
                    $DB->insert_records_via_batch('block_totara_stats', $stats);
                    $stats = array();
                }

                if (!empty($completion_history)) {
                    // Batch import completions.
                    $DB->insert_records_via_batch('course_completion_history', $completion_history);
                    $completion_history = array();
                }

                // New enrol record or reached the next batch insert.
                if (!empty($users)) {
                    // Batch enrol users.
                    $instance = $DB->get_record('enrol', array('id' => $enrolid));
                    $plugin->enrol_user_bulk($instance, $users, $instance->roleid, $timestart, $timeend);
                    $enrolcount = 0;
                    $users = array();
                }

                if (!empty($updateids)) {
                    // Update the timeupdated.
                    list($insql, $params) = $DB->get_in_or_equal($updateids, SQL_PARAMS_NAMED, 'param');
                    $params['timeupdated'] = $importtime;
                    $sql = "UPDATE {{$tablename}}
                            SET timeupdated = :timeupdated
                            WHERE id {$insql}";
                    $DB->execute($sql, $params);
                    unset($updateids);
                    $updateids = array();
                }

                if (!empty($historicalduplicate)) {
                    // Update records as duplicated.
                    update_errors_import($historicalduplicate, 'duplicate;', $tablename);
                    $historicalduplicate = array();
                }

                if (!empty($historicalrecordindb)) {
                    // Update records as already in db.
                    update_errors_import($historicalrecordindb, 'completiondatesame;', $tablename);
                    $historicalrecordindb = array();
                }

                if (!empty($coursecompletionlogs)) {
                    $DB->insert_records_via_batch('course_completion_log', $coursecompletionlogs);
                    $coursecompletionlogs = array();
                }

                // Reset enrol instance after enroling the users.
                $enrolid = $course->enrolid;
                $instance = $DB->get_record('enrol', array('id' => $enrolid));
            }

            $timecompleted = null;
            $timestamp = $course->completiondateparsed;
            if (!empty($timestamp)) {
                $timecompleted = $timestamp;
            }

            $timeenrolled = $course->timeenrolled;
            $timestarted = $course->timestarted;

            if (empty($course->userenrolid) || ($course->userenrolstatus == ENROL_USER_SUSPENDED)) {
                // User isn't already enrolled or has been suspended, so add them to the enrol list.
                $user = new stdClass();
                $user->userid = $course->userid;
                $user->courseid = $course->courseid;
                // Only add users if they have not been marked already to be enrolled.
                if (!array_key_exists($user->userid, $enrolledusers) || !in_array($user->courseid, $enrolledusers[$user->userid])) {
                    $users[] = $user;
                    if (array_key_exists($user->userid, $enrolledusers)) {
                        array_push($enrolledusers[$user->userid], $user->courseid);
                    } else {
                        $enrolledusers[$user->userid] = array($user->courseid);
                    }
                }
                $timeenrolled = $timecompleted;
                $timestarted = $timecompleted;
            } else if (!empty($timecompleted)) {
                // Best guess at enrollment times.
                if (($timeenrolled > $timecompleted) || (empty($timeenrolled))) {
                    $timeenrolled = $timecompleted;
                }
                if (($timestarted > $timecompleted) || (empty($timestarted))) {
                    $timestarted = $timecompleted;
                }
            }
            // Create completion record.
            $completion = new stdClass();
            $completion->rpl = get_string('rpl', 'totara_completionimport', $course->grade);
            $completion->rplgrade = $course->grade;
            $completion->status = COMPLETION_STATUS_COMPLETEVIARPL;
            $completion->timeenrolled = $timeenrolled;
            $completion->timestarted = $timestarted;
            $completion->timecompleted = $timecompleted;
            $completion->reaggregate = 0;
            $completion->userid = $course->userid;
            $completion->course = $course->courseid;

            // Create block_totara_stats records
            $stat = new stdClass();
            $stat->userid = $course->userid;
            $stat->timestamp = time();
            $stat->eventtype = STATS_EVENT_COURSE_COMPLETE;
            $stat->data = '';
            $stat->data2 = $course->courseid;

            $priorkey = "{$completion->userid}_{$completion->course}";
            $historyrecord = null;

            // Now that records have been ordered we know that every time we enter here it's a new completion record.
            if ($course->userid != $currentuser || $course->courseid != $currentcourse) {
                // User or course has changed or they are empty. Update the current user and course.
                $currentuser = $course->userid;
                $currentcourse = $course->courseid;
                if (empty($course->coursecompletionid)) {
                    $completions[$priorkey] = $completion; // Completion should be the first record
                    $stats[$priorkey] = $stat;
                    $coursecompletionlogs[] = \core_completion\helper::make_log_record($completion->course, $completion->userid,
                        \core_completion\helper::get_course_completion_log_description($completion,
                            "Current completion created during import due to no existing record"), $USER->id);
                } else if ($completion->timecompleted >= $course->currenttimecompleted && $overridecurrentcompletion) {
                    $deletedcompletions[] = $course->coursecompletionid;
                    $completions[$priorkey] = $completion;
                    $stats[$priorkey] = $stat;
                    $coursecompletionlogs[] = \core_completion\helper::make_log_record($completion->course, $completion->userid,
                        \core_completion\helper::get_course_completion_log_description($completion,
                            "Current completion deleted and created during import due to newer or same time completed and override setting enabled"), $USER->id);
                } else if ($completion->timecompleted != $course->currenttimecompleted) {
                    // As long as the timecompleted doesn't match the currenttimecompleted put it in history.
                    $historyrecord = $completion;
                } else {
                    $coursecompletionlogs[] = \core_completion\helper::make_log_record($completion->course, $completion->userid,
                        \core_completion\helper::get_course_completion_log_description($completion,
                            "Record not processed during import due to existing current completion with the same time completed"), $USER->id);
                }
            } else {
                $historyrecord = $completion;
            }

            // Save historical records.
            if (!is_null($historyrecord)) {
                $priorhistorykey = "{$historyrecord->course}_{$historyrecord->userid}_{$historyrecord->timecompleted}";
                $history = new StdClass();
                $history->courseid = $historyrecord->course;
                $history->userid = $historyrecord->userid;
                $history->timecompleted = $historyrecord->timecompleted;
                $history->grade = $historyrecord->rplgrade;
                if (!array_key_exists($priorhistorykey, $completion_history)) {
                    $params = array(
                        'courseid' => $history->courseid,
                        'userid' => $history->userid,
                        'timecompleted' => $history->timecompleted
                    );
                    if (!$DB->record_exists('course_completion_history', $params)) {
                        $completion_history[$priorhistorykey] = $history;
                        $coursecompletionlogs[] = \core_completion\helper::make_log_record($completion->course, $completion->userid,
                            \core_completion\helper::get_course_completion_history_log_description($history,
                                "History created during import due to no existing matching record"), $USER->id);
                    } else {
                        $historicalrecordindb[] = $course->importid;
                        $history->id = 'None';
                        $coursecompletionlogs[] = \core_completion\helper::make_log_record($completion->course, $completion->userid,
                            \core_completion\helper::get_course_completion_history_log_description($history,
                                "History not created during import due to existing history with the same time completed"), $USER->id);
                    }
                } else {
                    $historicalduplicate[] =  $course->importid;
                    $history->id = 'None';
                    $coursecompletionlogs[] = \core_completion\helper::make_log_record($completion->course, $completion->userid,
                        \core_completion\helper::get_course_completion_history_log_description($history,
                            "History not created during import due to another import record with the same time completed"), $USER->id);
                }
            }

            $updateids[] = $course->importid;
            $enrolcount++;
        }
    }
    $courses->close();
    // Delete any existing course completions we are overriding.
    if (!empty($deletedcompletions)) {
        $DB->delete_records_list('course_completions', 'id', $deletedcompletions);
        $deletedcompletions = array();
    }

    if (!empty($completions)) {
        // Batch import completions.
        $DB->insert_records_via_batch('course_completions', $completions);
        $completions = array();
    }

    if (!empty($stats)) {
        // Batch import block_totara_stats.
        $DB->insert_records_via_batch('block_totara_stats', $stats);
        $stats = array();
    }

    if (!empty($completion_history)) {
        // Batch import completions.
        $DB->insert_records_via_batch('course_completion_history', $completion_history);
        $completion_history = array();
    }

    // Add any remaining records.
    if (!empty($users)) {
        // Batch enrol users.
        $plugin->enrol_user_bulk($instance, $users, $instance->roleid, $timestart, $timeend);
        $enrolcount = 0;
        $users = array();
    }

    if (!empty($updateids)) {
        // Update the timeupdated.
        list($insql, $params) = $DB->get_in_or_equal($updateids, SQL_PARAMS_NAMED, 'param');
        $params['timeupdated'] = $importtime;
        $sql = "UPDATE {{$tablename}}
                SET timeupdated = :timeupdated
                WHERE id {$insql}";
        $DB->execute($sql, $params);
        $updateids = array();
    }

    if (!empty($historicalduplicate)) {
        // Update records as duplicated.
        update_errors_import($historicalduplicate, 'duplicate;', $tablename);
        $historicalduplicate = array();
    }

    if (!empty($historicalrecordindb)) {
        // Update records as already in db.
        update_errors_import($historicalrecordindb, 'completiondatesame;', $tablename);
        $historicalrecordindb = array();
    }

    if (!empty($coursecompletionlogs)) {
        $DB->insert_records_via_batch('course_completion_log', $coursecompletionlogs);
        $coursecompletionlogs = array();
    }

    return $errors;
}

/**
 * Assign users to certifications and complete them
 *
 * Doesn't seem to be a bulk function for this so inserting directly into the tables
 *
 * @global object $DB
 * @global object $CFG
 * @param string $importname name of import
 * @param int $importtime time of import
 * @return array of errors if any
 */
function import_certification($importname, $importtime) {
    global $DB, $CFG, $USER;
    require_once($CFG->dirroot . '/totara/program/program.class.php');

    if ($importname !== 'certification') {
        throw new moodle_exception('error:wrongimportname', 'totara_completionimport', '', $importname);
    }

    $importaction = get_default_config('totara_completionimport_certification', 'importactioncertification', COMPLETION_IMPORT_TO_HISTORY);

    list($importsqlwhere, $importsqlparams) = get_importsqlwhere($importtime);

    // First find all programs that have a user who is in the import but who isn't yet assigned.
    $sql = "SELECT DISTINCT p.id
              FROM {totara_compl_import_cert} i
              JOIN {user} u ON u.username = i.username
              JOIN {prog} p ON p.id = i.certificationid
             {$importsqlwhere}
               AND NOT EXISTS (SELECT pa.id FROM {prog_user_assignment} pa
                                WHERE pa.programid = p.id AND pa.userid = u.id)
               AND NOT EXISTS (SELECT pfa.id FROM {prog_future_user_assignment} pfa
                                WHERE pfa.programid = p.id AND pfa.userid = u.id)";
    $programstoupdate = $DB->get_fieldset_sql($sql, $importsqlparams);

    // Then add the individual program assignment records.
    $sql = "SELECT DISTINCT p.id AS programid,
                   ".ASSIGNTYPE_INDIVIDUAL." AS assignmenttype,
                   u.id AS assignmenttypeid,
                   0 AS includechildren,
                   ".COMPLETION_TIME_NOT_SET." AS completiontime,
                   ".COMPLETION_EVENT_NONE." AS completionevent,
                   0 AS completioninstance
              FROM {totara_compl_import_cert} i
              JOIN {user} u ON u.username = i.username
              JOIN {prog} p ON p.id = i.certificationid
             {$importsqlwhere}
               AND NOT EXISTS (SELECT pa.id FROM {prog_user_assignment} pa
                                WHERE pa.programid = p.id AND pa.userid = u.id)
               AND NOT EXISTS (SELECT pfa.id FROM {prog_future_user_assignment} pfa
                                WHERE pfa.programid = p.id AND pfa.userid = u.id)";
    $assignments = $DB->get_recordset_sql($sql, $importsqlparams);
    $DB->insert_records_via_batch('prog_assignment', $assignments);
    $assignments->close();

    // Lastly, update the program user assignments, to create the user assignment records.
    foreach ($programstoupdate as $programid) {
        $program = new program($programid);
        $program->update_learner_assignments(true);
    }

    // Now get the records to import. If one user/cert combination has multiple records, the most recent will be first.
    $params = array_merge(array('assignmenttype' => ASSIGNTYPE_INDIVIDUAL), $importsqlparams);
    $sql = "SELECT DISTINCT i.id as importid,
                    i.completiondateparsed AS importcompletiondate,
                    i.duedate AS importduedate,
                    p.id AS progid,
                    c.id AS certid,
                    c.recertifydatetype,
                    c.activeperiod,
                    c.minimumactiveperiod,
                    c.windowperiod,
                    cc.id AS ccid,
                    cc.certifpath AS currentcertifpath,
                    cc.status AS currentstatus,
                    cc.renewalstatus AS currentrenewalstatus,
                    cc.timewindowopens AS currenttimewindowopens,
                    cc.timeexpires AS currenttimeexpires,
                    cc.baselinetimeexpires AS currentbaselinetimeexpires,
                    cc.timecompleted AS currenttimecompleted,
                    pc.id AS pcid,
                    pc.status AS currentprogstatus,
                    pc.timedue AS currenttimedue,
                    pc.timecompleted AS currentprogtimecompleted,
                    u.id AS userid,
                    pa.id AS assignmentid,
                    pua.id AS puaid,
                    pfa.id AS pfaid
            FROM {totara_compl_import_cert} i
            JOIN {prog} p ON p.id = i.certificationid
            JOIN {certif} c ON c.id = p.certifid
            JOIN {user} u ON u.username = i.username
            LEFT JOIN {prog_assignment} pa ON pa.programid = p.id
            LEFT JOIN {prog_user_assignment} pua ON pua.assignmentid = pa.id AND pua.userid = u.id AND pua.programid = p.id
            LEFT JOIN {prog_future_user_assignment} pfa ON pfa.assignmentid = pa.id AND pfa.userid = u.id AND pfa.programid = p.id
            LEFT JOIN {certif_completion} cc ON cc.certifid = c.id AND cc.userid = u.id
            LEFT JOIN {prog_completion} pc ON pc.programid = p.id AND pc.userid = u.id AND pc.coursesetid = 0
            {$importsqlwhere}
            AND ((pa.assignmenttype = :assignmenttype AND pa.assignmenttypeid = u.id)
              OR (pfa.userid = u.id AND pfa.assignmentid IS NOT NULL)
              OR (pua.userid = u.id AND pua.assignmentid IS NOT NULL))
            ORDER BY progid, userid, importcompletiondate DESC";
    $recordstoprocess = $DB->get_recordset_sql($sql, $params);

    // If there are no records to process, return.
    if (!$recordstoprocess->valid()) {
        $recordstoprocess->close();
        return array();
    }

    // Used if one of the records can't be imported (only because the expiry date was a duplicate).
    $errorsql = "UPDATE {totara_compl_import_cert}
                    SET importerrormsg = " . $DB->sql_concat('importerrormsg', ':errorstring') . ",
                        importerror = :importerror
                        WHERE id = :importid";

    $batchdeletecertcompletion = array();
    $batchinsertcertcompletion = array();
    $batchdeletecertcompletionhistory = array();
    $batchinsertcertcompletionhistory = array();
    $batchdeleteprogcompletion = array();
    $batchinsertprogcompletion = array();
    $batchprogcompletionlog = array();
    $batchupdateimport = array();

    $countbatch = 0;
    $lastuserid = -1;
    $lastprogid = -1;
    $lasttimecompleted = -123;
    $csvdateformat = get_default_config('totara_completionimport_certification', 'csvdateformat', TCI_CSV_DATE_FORMAT);

    $nextrecordtoprocess = $recordstoprocess->current();
    do {
        // Get the current record for processing and move the record set pointer forward.
        $recordtoprocess = $nextrecordtoprocess;
        $recordstoprocess->next();
        $islastrecordtoprocess = !$recordstoprocess->valid();
        $nextrecordtoprocess = $islastrecordtoprocess ? false : $recordstoprocess->current();

        $countbatch++;

        // Set up some shortcuts.
        $userid = $recordtoprocess->userid;
        $progid = $recordtoprocess->progid;
        $certid = $recordtoprocess->certid;
        $sameasprevious = ($progid == $lastprogid && $userid == $lastuserid);

        // Create basic completion records in "certified" state, which can be altered for specific purposes later.
        $certcompletion = new stdClass();
        $certcompletion->certifid = $certid;
        $certcompletion->userid = $userid;
        $certcompletion->certifpath = CERTIFPATH_RECERT;
        $certcompletion->status = CERTIFSTATUS_COMPLETED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletion->timemodified = $importtime;

        $progcompletion = new stdClass();
        $progcompletion->programid = $progid;
        $progcompletion->userid = $userid;
        $progcompletion->status = STATUS_PROGRAM_COMPLETE;
        $progcompletion->timestarted = $importtime;

        // Calculate completion times.
        $timecompleted = $recordtoprocess->importcompletiondate;
        $importtimedue = totara_date_parse_from_format($csvdateformat, $recordtoprocess->importduedate);
        //TL-17804: Use baselinetimeexpires instead of timeexpires so we don't get unexpected shifts in recertification
        //windows when granting extensions
        $base = get_certiftimebase($recordtoprocess->recertifydatetype, $recordtoprocess->currentbaselinetimeexpires, $timecompleted, $importtimedue,
            $recordtoprocess->activeperiod, $recordtoprocess->minimumactiveperiod, $recordtoprocess->windowperiod);
        $certcompletion->timeexpires = get_timeexpires($base, $recordtoprocess->activeperiod);
        $certcompletion->baselinetimeexpires = $certcompletion->timeexpires;
        $certcompletion->timewindowopens = get_timewindowopens($certcompletion->timeexpires, $recordtoprocess->windowperiod);
        $certcompletion->timecompleted = $timecompleted;
        $progcompletion->timedue = $certcompletion->timeexpires;
        $progcompletion->timecompleted = $timecompleted;

        // Figure out which action should be performed with the record.
        $action = 'notset';
        if ($sameasprevious && $certcompletion->timecompleted == $lasttimecompleted) {
            // This record is a duplicate of one already imported (based on timecompleted). It must have the same expiry
            // date too. We can't import this record.
            $params = array('errorstring' => 'errorskippedduplicate;', 'importerror' => 1, 'importid' => $recordtoprocess->importid);
            $DB->execute($errorsql, $params);
            $action = 'skip';

        } else if ($sameasprevious) {
            // We've already seen a record for this user and certification. This one MUST have an older expiry date (see sql).
            $action = 'addtohistory';

        } else if ($importaction == COMPLETION_IMPORT_TO_HISTORY) {
            // Just stick it in history. Note that this doesn't fix up missing prog or cert completion records.
            // They should be handled by normal certification code (as opposed to completion upload).
            $action = 'addtohistory';

        } else if (empty($recordtoprocess->ccid) && empty($recordtoprocess->pcid)) {
            // Both records are missing. Create them and certify the user using the uploaded record.
            $action = 'createcertandprog';

        } else if (empty($recordtoprocess->ccid)) {
            // The certif_completion record is missing. Create it and make sure the prog_completion record is updated.
            $action = 'createcertupdateprog';

        } else if (empty($recordtoprocess->pcid)) {
            // The prog_completion record is missing, so create it. It's possible that the certif_completion record contains
            // some useful information (imagine a user certified then prog_completion disappeared), so archive it before
            // certifying the user.
            $action = 'createprogarchiveandupdatecert';

        } else {
            // From here on, we've got existing completion records to deal with, and import action could
            // only be COMPLETE_INCOMPLETE or OVERRIDE_IF_NEWER.

            $currentcertcompletion = new stdClass();
            $currentcertcompletion->status = $recordtoprocess->currentstatus;
            $currentcertcompletion->renewalstatus = $recordtoprocess->currentrenewalstatus;
            $currentcertcompletion->certifpath = $recordtoprocess->currentcertifpath;
            $currentcertcompletion->timecompleted = $recordtoprocess->currenttimecompleted;
            $currentcertcompletion->timewindowopens = $recordtoprocess->currenttimewindowopens;
            $currentcertcompletion->timeexpires = $recordtoprocess->currenttimeexpires;
            $currentcertcompletion->baselinetimeexpires = $recordtoprocess->currentbaselinetimeexpires;
            $currentprogcompletion = new stdClass();
            $currentprogcompletion->status = $recordtoprocess->currentprogstatus;
            $currentprogcompletion->timecompleted = $recordtoprocess->currentprogtimecompleted;
            $currentprogcompletion->timedue = $recordtoprocess->currenttimedue;
            $haserrors = certif_get_completion_errors($currentcertcompletion, $currentprogcompletion);

            if ($haserrors) {
                // In either import action, we want to save the current completion record in history (in case the
                // data has some meaning) and certify the user. Then they will have a valid current record.
                $action = 'archivecurrentandcertifyuser';
                // Recalculate the expiry date, because the date used to calculate it earlier might have been faulty.
                $base = get_certiftimebase($recordtoprocess->recertifydatetype, 0, $timecompleted, $importtimedue,
                    $recordtoprocess->activeperiod, $recordtoprocess->minimumactiveperiod, $recordtoprocess->windowperiod);
                $certcompletion->timeexpires = get_timeexpires($base, $recordtoprocess->activeperiod);
                $certcompletion->baselinetimeexpires = $certcompletion->timeexpires;
                $certcompletion->timewindowopens = get_timewindowopens($certcompletion->timeexpires, $recordtoprocess->windowperiod);
                $progcompletion->timedue = $certcompletion->timeexpires;

            } else {
                $currentstate = certif_get_completion_state($currentcertcompletion);

                switch ($currentstate) {
                    case CERTIFCOMPLETIONSTATE_ASSIGNED:
                        // The user is not certified. Just put the new completion record into current, marking the user
                        // as certified. Both possible import actions indicate the import should cause certification.
                        $action = 'certifyuser';
                        break;

                    case CERTIFCOMPLETIONSTATE_CERTIFIED:
                        if ($importaction == COMPLETION_IMPORT_COMPLETE_INCOMPLETE) {
                            // The user already has a completion date, so just record this in history.
                            $action = 'addtohistory';

                        } else { // Action must be OVERRIDE_IF_NEWER.

                            if ($certcompletion->timecompleted > $recordtoprocess->currenttimecompleted) {
                                // The new completion expires after the current completion expires. We need to archive the
                                // current completion before updating it with the new completion data.
                                $action = 'archivecurrentandcertifyuser';

                            } else {
                                // The new completion expires before the current completion expires. Just put it in history.
                                $action = 'addtohistory';
                            }
                        }
                        break;

                    case CERTIFCOMPLETIONSTATE_WINDOWOPEN:
                        if ($importaction == COMPLETION_IMPORT_COMPLETE_INCOMPLETE) {
                            // The user already has a completion date, so just record this in history.
                            $action = 'addtohistory';

                        } else { // Action must be OVERRIDE_IF_NEWER.

                            if ($certcompletion->timecompleted > $recordtoprocess->currenttimecompleted) {
                                // The new completion expires after the current completion expires. The current completion
                                // record was was already copied in history, so just update the current completion record,
                                // marking the user as certified again.
                                $action = 'certifyuser';

                            } else {
                                // The new completion expires before the current completion expires. Just put it in history.
                                $action = 'addtohistory';
                            }
                        }
                        break;

                    case CERTIFCOMPLETIONSTATE_EXPIRED:
                        // The user is not certified. Just put the new completion record into current, marking the user
                        // as certified. Both possible import actions indicate the import should cause certification.
                        $action = 'certifyuser';
                        break;

                    // Case CERTIFCOMPLETIONSTATE_INVALID is not possible, because $haserrors would be true.
                }
            }
        }

        switch ($action) {
            case 'createcertandprog':
                $batchinsertcertcompletion[] = $certcompletion;
                $batchinsertprogcompletion[] = $progcompletion;
                $batchprogcompletionlog[] = completionimport_create_prog_completion_log_record($progid, $userid, $USER->id,
                    certif_calculate_completion_description($certcompletion, $progcompletion,
                        'Cert and prog completion records created during import, user certified'));
                break;

            case 'createcertupdateprog':
                $batchinsertcertcompletion[] = $certcompletion;
                $batchdeleteprogcompletion[] = $recordtoprocess->pcid;
                $batchinsertprogcompletion[] = $progcompletion;
                $batchprogcompletionlog[] = completionimport_create_prog_completion_log_record($progid, $userid, $USER->id,
                    certif_calculate_completion_description($certcompletion, $progcompletion,
                        'Cert completion record created during import, user certified'));
                break;

            case 'createprogarchiveandupdatecert':
                $matchinghistoryid = $DB->get_field('certif_completion_history', 'id',
                    array(
                        'certifid' => $certid,
                        'userid' => $userid,
                        'timecompleted' => $recordtoprocess->currenttimecompleted,
                        'timeexpires' => $recordtoprocess->currenttimeexpires
                    ));
                if ($matchinghistoryid) {
                    $batchdeletecertcompletionhistory[] = $matchinghistoryid;
                    $batchprogcompletionlog[] = completionimport_create_prog_completion_log_record($progid, $userid, $USER->id,
                        "Completion history deleted during import<br><ul><li>ID: {$matchinghistoryid}</li></ul>");
                }
                $certcompletionhistory = new stdClass(); // Note: The order of these fields must match $certcompletion above!
                $certcompletionhistory->certifid = $certid;
                $certcompletionhistory->userid = $userid;
                $certcompletionhistory->certifpath = $recordtoprocess->currentcertifpath;
                $certcompletionhistory->status = $recordtoprocess->currentstatus;
                $certcompletionhistory->renewalstatus = $recordtoprocess->currentrenewalstatus;
                $certcompletionhistory->timemodified = $importtime;
                $certcompletionhistory->timeexpires = $recordtoprocess->currenttimeexpires;
                $certcompletionhistory->baselinetimeexpires = $recordtoprocess->currentbaselinetimeexpires;
                $certcompletionhistory->timewindowopens = $recordtoprocess->currenttimewindowopens;
                $certcompletionhistory->timecompleted = $recordtoprocess->currenttimecompleted;
                $certcompletionhistory->unassigned = 0;
                $batchinsertcertcompletionhistory[] = $certcompletionhistory;
                $batchprogcompletionlog[] = completionimport_create_prog_completion_log_record($progid, $userid, $USER->id,
                    certif_calculate_completion_history_description($certcompletionhistory,
                        'Completion archived during import'));
                $batchdeletecertcompletion[] = $recordtoprocess->ccid;
                $batchinsertcertcompletion[] = $certcompletion;
                $batchinsertprogcompletion[] = $progcompletion;
                $batchprogcompletionlog[] = completionimport_create_prog_completion_log_record($progid, $userid, $USER->id,
                    certif_calculate_completion_description($certcompletion, $progcompletion,
                        'Prog completion record created during import, user certified'));
                break;

            case 'addtohistory':
                // Recalculate expiry date, ignoring the previous expiry date.
                $base = get_certiftimebase($recordtoprocess->recertifydatetype, 0, $timecompleted, $importtimedue,
                    $recordtoprocess->activeperiod, $recordtoprocess->minimumactiveperiod, $recordtoprocess->windowperiod);
                $certcompletion->timeexpires = get_timeexpires($base, $recordtoprocess->activeperiod);
                $certcompletion->baselinetimeexpires = $certcompletion->timeexpires;
                $certcompletion->timewindowopens = get_timewindowopens($certcompletion->timeexpires, $recordtoprocess->windowperiod);
                $progcompletion->timedue = $certcompletion->timeexpires;
                $matchinghistoryid = $DB->get_field('certif_completion_history', 'id',
                    array(
                        'certifid' => $certid,
                        'userid' => $userid,
                        'timecompleted' => $certcompletion->timecompleted,
                        'timeexpires' => $certcompletion->timeexpires
                    ));
                if ($matchinghistoryid) {
                    $batchdeletecertcompletionhistory[] = $matchinghistoryid;
                    $batchprogcompletionlog[] = completionimport_create_prog_completion_log_record($progid, $userid, $USER->id,
                        "Completion history deleted during import<br><ul><li>ID: {$matchinghistoryid}</li></ul>");
                }
                $certcompletion->unassigned = 0;
                $batchinsertcertcompletionhistory[] = $certcompletion;
                $batchprogcompletionlog[] = completionimport_create_prog_completion_log_record($progid, $userid, $USER->id,
                    certif_calculate_completion_history_description($certcompletion,
                        'Uploaded completion added to history during import'));
                break;

            case 'archivecurrentandcertifyuser':
                $matchinghistoryid = $DB->get_field('certif_completion_history', 'id',
                    array(
                        'certifid' => $certid,
                        'userid' => $userid,
                        'timecompleted' => $recordtoprocess->currenttimecompleted,
                        'timeexpires' => $recordtoprocess->currenttimeexpires
                    ));
                if ($matchinghistoryid) {
                    $batchdeletecertcompletionhistory[] = $matchinghistoryid;
                    $batchprogcompletionlog[] = completionimport_create_prog_completion_log_record($progid, $userid, $USER->id,
                        "Completion history deleted during import<br><ul><li>ID: {$matchinghistoryid}</li></ul>");
                }
                $certcompletionhistory = new stdClass(); // Note: The order of these fields must match $certcompletion above!
                $certcompletionhistory->certifid = $certid;
                $certcompletionhistory->userid = $userid;
                $certcompletionhistory->certifpath = $recordtoprocess->currentcertifpath;
                $certcompletionhistory->status = $recordtoprocess->currentstatus;
                $certcompletionhistory->renewalstatus = $recordtoprocess->currentrenewalstatus;
                $certcompletionhistory->timemodified = $importtime;
                $certcompletionhistory->timeexpires = $recordtoprocess->currenttimeexpires;
                $certcompletionhistory->baselinetimeexpires = $recordtoprocess->currentbaselinetimeexpires;
                $certcompletionhistory->timewindowopens = $recordtoprocess->currenttimewindowopens;
                $certcompletionhistory->timecompleted = $recordtoprocess->currenttimecompleted;
                $certcompletionhistory->unassigned = 0;
                $batchinsertcertcompletionhistory[] = $certcompletionhistory;
                $batchprogcompletionlog[] = completionimport_create_prog_completion_log_record($progid, $userid, $USER->id,
                    certif_calculate_completion_history_description($certcompletionhistory,
                        'Completion archived during import'));
                // Note: Break is missing here to prevent code duplication.

            case 'certifyuser':
                $batchdeletecertcompletion[] = $recordtoprocess->ccid;
                $batchinsertcertcompletion[] = $certcompletion;
                $batchdeleteprogcompletion[] = $recordtoprocess->pcid;
                $batchinsertprogcompletion[] = $progcompletion;
                $batchprogcompletionlog[] = completionimport_create_prog_completion_log_record($progid, $userid, $USER->id,
                    certif_calculate_completion_description($certcompletion, $progcompletion,
                        'User certified during import'));
                break;

            case 'skip':
                break;

            case 'notset':
            default:
                throw new moodle_exception('error:actionnotdefined', 'totara_completionimport', '', $action);
                break;
        }

        // Mark the import record as being processed.
        $batchupdateimport[] = $recordtoprocess->importid;

        // Flush db changes to disk. Done on full batch and last record.
        if ($countbatch >= BATCH_INSERT_MAX_ROW_COUNT || $islastrecordtoprocess) {

            if (!empty($batchdeletecertcompletion)) {
                $DB->delete_records_list('certif_completion', 'id', $batchdeletecertcompletion);
                unset($batchdeletecertcompletion);
                $batchdeletecertcompletion = array();
            }

            if (!empty($batchinsertcertcompletion)) {
                $DB->insert_records_via_batch('certif_completion', $batchinsertcertcompletion);
                unset($batchinsertcertcompletion);
                $batchinsertcertcompletion = array();
            }

            if (!empty($batchdeletecertcompletionhistory)) {
                $DB->delete_records_list('certif_completion_history', 'id', $batchdeletecertcompletionhistory);
                unset($batchdeletecertcompletionhistory);
                $batchdeletecertcompletionhistory = array();
            }

            if (!empty($batchinsertcertcompletionhistory)) {
                $DB->insert_records_via_batch('certif_completion_history', $batchinsertcertcompletionhistory);
                unset($batchinsertcertcompletionhistory);
                $batchinsertcertcompletionhistory = array();
            }

            if (!empty($batchdeleteprogcompletion)) {
                $DB->delete_records_list('prog_completion', 'id', $batchdeleteprogcompletion);
                unset($batchdeleteprogcompletion);
                $batchdeleteprogcompletion = array();
            }

            if (!empty($batchinsertprogcompletion)) {
                $DB->insert_records_via_batch('prog_completion', $batchinsertprogcompletion);
                unset($batchinsertprogcompletion);
                $batchinsertprogcompletion = array();
            }

            if (!empty($batchprogcompletionlog)) {
                $DB->insert_records_via_batch('prog_completion_log', $batchprogcompletionlog);
                unset($batchprogcompletionlog);
                $batchprogcompletionlog = array();
            }

            if (!empty($batchupdateimport)) {
                // Update the timeupdated in the import table.
                list($updateinsql, $params) = $DB->get_in_or_equal($batchupdateimport, SQL_PARAMS_NAMED, 'param');
                $params['timeupdated'] = $importtime;
                $sql = "UPDATE {totara_compl_import_cert}
                           SET timeupdated = :timeupdated
                         WHERE id {$updateinsql}";
                $DB->execute($sql, $params);
                unset($batchupdateimport);
                $batchupdateimport = array();
            }
        }

        $lastuserid = $userid;
        $lastprogid = $progid;
        $lasttimecompleted = $certcompletion->timecompleted;

        // Keep going until we've processed the last record.
    } while (!$islastrecordtoprocess);

    $recordstoprocess->close();

    return array();
}

/**
 * Takes the values and puts them into a record which can be written to the program completion log.
 *
 * @internal
 * @param $programid
 * @param $userid
 * @param $changeuserid
 * @param $description
 * @return stdClass
 */
function completionimport_create_prog_completion_log_record($programid, $userid, $changeuserid, $description) {
    $record = new stdClass();
    $record->programid = $programid;
    $record->userid = $userid;
    $record->changeuserid = $changeuserid;
    $record->description = $description;
    $record->timemodified = time();
    return $record;
}

/**
 * Returns a list of possible date formats
 * Based on the list at http://en.wikipedia.org/wiki/Date_format_by_country
 *
 * @return array
 */
function get_dateformats() {
    $separators = array('-', '/', '.', ' ');
    $endians = array('yyyy~mm~dd', 'yy~mm~dd', 'dd~mm~yyyy', 'dd~mm~yy', 'mm~dd~yyyy', 'mm~dd~yy');
    $formats = array();
    foreach ($endians as $endian) {
        foreach ($separators as $separator) {
            $display = str_replace( '~', $separator, $endian);
            $format = str_replace('yyyy', 'Y', $display);
            $format = str_replace('yy', 'y', $format); // Don't think 2 digit years should be allowed.
            $format = str_replace('mm', 'm', $format);
            $format = str_replace('dd', 'd', $format);
            $formats[$format] = $display;
        }
    }
    return $formats;
}

/**
 * Displays import results and a link to view the import errors
 *
 * @global object $OUTPUT
 * @global object $DB
 * @global object $USER
 * @param string $importname name of import
 * @param int $importtime time of import
 */
function display_report_link($importname, $importtime) {
    global $OUTPUT, $DB, $USER;

    $tablename = get_tablename($importname);

    $sql = "SELECT COUNT(*) AS totalrows,
            COALESCE(SUM(importerror), 0) AS totalerrors,
            COALESCE(SUM(importevidence), 0) AS totalevidence
            FROM {{$tablename}}
            WHERE timecreated = :timecreated
            AND importuserid = :userid";
    $totals = $DB->get_record_sql($sql, array('timecreated' => $importtime, 'userid' => $USER->id));

    echo $OUTPUT->heading(get_string('importresults', 'totara_completionimport'));
    if ($totals->totalrows) {
        echo html_writer::tag('p', get_string('importerrors', 'totara_completionimport', $totals->totalerrors));
        echo html_writer::tag('p', get_string('importevidence', 'totara_completionimport', $totals->totalevidence));
        echo html_writer::tag('p', get_string('import' . $importname, 'totara_completionimport',
                $totals->totalrows - $totals->totalerrors - $totals->totalevidence));
        echo html_writer::tag('p', get_string('importtotal', 'totara_completionimport', $totals->totalrows));

        $viewurl = new moodle_url('/totara/completionimport/viewreport.php',
                array('importname' => $importname, 'timecreated' => $importtime, 'importuserid' => $USER->id, 'clearfilters' => 1));
        $viewlink = html_writer::link($viewurl, format_string(get_string('report_' . $importname, 'totara_completionimport')));
        echo html_writer::tag('p', $viewlink);
    } else {
        echo html_writer::tag('p', get_string('importnone', 'totara_completionimport'));
    }

}

/**
 * Returns the temporary path for for the temporary file - creates the directory if it doesn't exist
 *
 * @global object $CFG
 * @global object $OUTPUT
 * @return boolean|string false if fails or full name of path
 */
function get_temppath() {
    global $CFG, $OUTPUT;
    // Create the temporary path if it doesn't already exist.
    $temppath = $CFG->dataroot . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'totara_completionimport';
    if (!file_exists($temppath)) {
        if (!mkdir($temppath, $CFG->directorypermissions, true)) {
            echo $OUTPUT->notification(get_string('cannotcreatetemppath', 'totara_completionimport', $temppath), 'notifyproblem');
            return false;
        }
    }
    $temppath .= DIRECTORY_SEPARATOR;
    return $temppath;
}

/**
 * Returns the config data for the upload form
 *
 * Each upload form has its own set of data
 *
 * @param int $filesource Method of upload, either upload via form or external directory
 * @param type $importname
 * @return stdClass $data
 */
function get_config_data($filesource, $importname) {
    global $DB;

    $pluginname = 'totara_completionimport_' . $importname;
    $data = new stdClass();
    $data->filesource = $filesource;
    $data->sourcefile = get_config($pluginname, 'sourcefile');
    $data->evidencetype = get_default_config($pluginname, 'evidencetype', null);
    $data->csvdateformat = get_default_config($pluginname, 'csvdateformat', TCI_CSV_DATE_FORMAT);
    $data->csvdelimiter = get_default_config($pluginname, 'csvdelimiter', TCI_CSV_DELIMITER);
    $data->csvseparator = get_default_config($pluginname, 'csvseparator', TCI_CSV_SEPARATOR);
    $data->csvencoding = get_default_config($pluginname, 'csvencoding', TCI_CSV_ENCODING);
    if ($importname == 'certification') {
        $data->importactioncertification = get_default_config($pluginname, 'importactioncertification', COMPLETION_IMPORT_TO_HISTORY);
    } else {
        $overridesetting = 'overrideactive' . $importname;
        $data->$overridesetting = get_default_config($pluginname, 'overrideactive' . $importname, 0);
    }
    $forcecaseinsensitive = 'forcecaseinsensitive' . $importname;
    $data->$forcecaseinsensitive = get_default_config($pluginname, 'forcecaseinsensitive' . $importname, 0);

    // Evidence custom field for date completed.
    $data->evidencedatefield = get_default_config($pluginname, 'evidencedatefield', null);
    if (is_null($data->evidencedatefield)) {
        $params = array(
            'shortname' => get_string('evidencedatecompletedshort', 'totara_plan'),
            'datatype' => 'datetime',
            'hidden' => 0
        );
        $data->evidencedatefield = $DB->get_field('dp_plan_evidence_info_field', 'shortname', $params);
    }

    // Evidence custom field for the description.
    $data->evidencedescriptionfield = get_default_config($pluginname, 'evidencedescriptionfield', null);
    if (is_null($data->evidencedescriptionfield)) {
        $params = array(
            'shortname' => get_string('evidencedescriptionshort', 'totara_plan'),
            'datatype' => 'textarea',
            'hidden' => 0
        );
        $data->evidencedescriptionfield = $DB->get_field('dp_plan_evidence_info_field', 'shortname', $params);
    }

    return $data;
}

/**
 * Saves the data from the upload form
 *
 * @param object $data
 * @param string $importname name of import
 */
function set_config_data($data, $importname) {
    $pluginname = 'totara_completionimport_' . $importname;
    set_config('evidencetype', $data->evidencetype, $pluginname);
    set_config('evidencedatefield', $data->evidencedatefield, $pluginname);
    set_config('evidencedescriptionfield', $data->evidencedescriptionfield, $pluginname);

    if ($data->filesource == TCI_SOURCE_EXTERNAL) {
        set_config('sourcefile', $data->sourcefile, $pluginname);
    }
    set_config('csvdateformat', $data->csvdateformat, $pluginname);
    set_config('csvdelimiter', $data->csvdelimiter, $pluginname);
    set_config('csvseparator', $data->csvseparator, $pluginname);
    set_config('csvencoding', $data->csvencoding, $pluginname);
    if ($importname == 'certification') {
        set_config('importactioncertification', $data->importactioncertification, $pluginname);
    } else {
        $overridesetting = 'overrideactive' . $importname;
        set_config('overrideactive' . $importname, $data->$overridesetting, $pluginname);
    }
    $forcecaseinsensitive = 'forcecaseinsensitive' . $importname;
    set_config('forcecaseinsensitive' . $importname, $data->$forcecaseinsensitive, $pluginname);
}

/**
 * Moves the external source file to the temporary directory
 *
 * @global object $OUTPUT
 * @param string $filename source file
 * @param string $tempfilename destination file
 * @return boolean true if successful, false if fails
 */
function move_sourcefile($filename, $tempfilename) {
    global $OUTPUT, $CFG;

    if (empty($CFG->completionimportdir)) {
        echo $OUTPUT->notification(get_string('sourcefile_noconfig', 'totara_completionimport'), 'notifyproblem');
        return false;
    } else if (strpos($filename, $CFG->completionimportdir) !== 0) {
        echo $OUTPUT->notification(get_string('sourcefile_beginwith', 'totara_completionimport',
            $CFG->completionimportdir), 'notifyproblem');
        return false;
    }

    // Check if file is accessible.
    $handle = false;
    if (!is_readable($filename)) {
        echo $OUTPUT->notification(get_string('unreadablefile', 'totara_completionimport', $filename), 'notifyproblem');
        return false;
    } else if (!$handle = fopen($filename, 'r')) {
        echo $OUTPUT->notification(get_string('erroropeningfile', 'totara_completionimport', $filename), 'notifyproblem');
        return false;
    } else if (!flock($handle, LOCK_EX | LOCK_NB)) {
        echo $OUTPUT->notification(get_string('fileisinuse', 'totara_completionimport', $filename), 'notifyproblem');
        fclose($handle);
        return false;
    }
    // Don't need the handle any more so close it.
    fclose($handle);

    if (PHPUNIT_TEST) {
        // If this is a unit test, we won't actually move any files.
        return true;
    }

    if (!rename($filename, $tempfilename)) {
        $a = new stdClass();
        $a->fromfile = $filename;
        $a->tofile = $tempfilename;
        echo $OUTPUT->notification(get_string('cannotmovefiles', 'totara_completionimport', $a), 'notifyproblem');
        return false;
    }

    return true;
}

/**
 * Deletes the import data from the import table
 *
 * @param string $importname name of import
 */
function reset_import($importname) {
    global $DB, $OUTPUT, $USER;
    $tablename = get_tablename($importname);
    if ($DB->delete_records($tablename, array('importuserid' => $USER->id))) {
        echo $OUTPUT->notification(get_string('resetcomplete', 'totara_completionimport', $importname), 'notifysuccess');
    } else {
        echo $OUTPUT->notification(get_string('resetfailed', 'totara_completionimport', $importname), 'notifyproblem');
    }
}

/**
 * Update errors ocurred in the historic import.
 *
 * @param array $records Array of ids that need to be updated with the error message
 * @param string $errormessage message for the error ocurred
 * @param string $tablename Name of the import table
 * @return bool result of the update operation
 */
function update_errors_import($records, $errormessage, $tablename) {
    global $DB;

    if (empty($records)) {
        return false;
    }

    list($insql, $params) = $DB->get_in_or_equal($records, SQL_PARAMS_NAMED, 'param');
    $params['errorstring'] = $errormessage;
    $params['importerror'] = 1;
    $sql = "UPDATE {{$tablename}}
            SET importerrormsg = " . $DB->sql_concat('importerrormsg', ':errorstring') . ",
                importerror = :importerror
            WHERE id {$insql}";
    return $DB->execute($sql, $params);
}

/**
 * Checks that a supplied formatted date is valid based on a given format.
 *
 * Note that this will allow numbers without leading zeros or otherwise less digits.
 * So for DD/MM/YYYY, either 31/5/2016 or 31/05/2016 will be seen as valid by this
 * function. These should also be converted to their expected times by totara_date_parse_from_format.
 *
 * For a format with a 4-digit year such as DD/MM/YYYY, 31/05/16 will often also be returned as valid
 * by this function, but it will be considered to be 31/05/0016, and totara_date_parse_from_format may
 * return a timestamp representing that also. It may simply be returned as invalid if the
 * system cannot handle such a timestamp.  E.g. 32-bit systems will not be able to handle a large, negative
 * timestamp.
 *
 * @param string $csvdateformat - format allowed by Totara in completion import, such as d/m/Y.
 * @param string $completiondate - formatted date, such as 31/05/2016.
 * @return bool - true if date is valid, false if not.
 */
function totara_completionimport_validate_date($csvdateformat, $completiondate) {
    $dateArray = date_parse_from_format($csvdateformat, $completiondate);

    if (!is_array($dateArray) or !empty($dateArray['error_count'])) {
        return false;
    }

    if ($dateArray['is_localtime']) {
        return false;
    }

    // A four digit year has been specified but not provided.
    if (preg_match('/Y/', $csvdateformat) && $dateArray['year'] < 1000) {
        return false;
    }

    if ($dateArray['month'] > 12) {
        return false;
    }

    $calendar = \core_calendar\type_factory::get_calendar_instance();
    $daysinmonth = $calendar->get_num_days_in_month($dateArray['year'], $dateArray['month']);
    if ($dateArray['day'] > $daysinmonth) {
        return false;
    }

    return true;
}
