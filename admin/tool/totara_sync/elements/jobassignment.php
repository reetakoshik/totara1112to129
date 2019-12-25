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

require_once($CFG->dirroot.'/admin/tool/totara_sync/elements/classes/element.class.php');

class totara_sync_element_jobassignment extends totara_sync_element {

    public function __construct() {
        $this->syncweighting = 100;
        parent::__construct();
    }

    public function get_name() {
        return 'jobassignment';
    }

    public function has_config() {
        return true;
    }

    /**
     * @param MoodleQuickForm $mform
     */
    public function config_form(&$mform) {

        $mform->addElement('selectyesno', 'sourceallrecords', get_string('sourceallrecords', 'tool_totara_sync'));
        $mform->addElement('static', 'sourceallrecordsdesc', '', get_string('sourceallrecordsdesc', 'tool_totara_sync'));

        // Check that we've never linked on job assignment id number before.
        $previouslylinkedonjobassignmentidnumber = get_config('totara_sync_element_jobassignment', 'previouslylinkedonjobassignmentidnumber');
        if (empty($previouslylinkedonjobassignmentidnumber)) {
            $mform->addElement('selectyesno', 'updateidnumbers', get_string('updateidnumbers', 'tool_totara_sync'));
            $mform->setDefault('updateidnumbers', true);
            $mform->addElement('static', 'updateidnumbersdesc', '', get_string('updateidnumbersdesc', 'tool_totara_sync'));
        }

        $mform->addElement('header', 'crud', get_string('allowedactions', 'tool_totara_sync'));
        $mform->addElement('checkbox', 'allow_create', get_string('create', 'tool_totara_sync'));
        $mform->setDefault('allow_create', 1);
        $mform->addElement('checkbox', 'allow_update', get_string('update', 'tool_totara_sync'));
        $mform->setDefault('allow_update', 1);
        $mform->addElement('checkbox', 'allow_delete', get_string('delete', 'tool_totara_sync'));
        $mform->setDefault('allow_delete', 1);
        $mform->setExpanded('crud');
    }

    /**
     * To save data entered into fields created by the config_form method.
     *
     * @param stdClass $data
     */
    public function config_save($data) {
        if (isset($data->csvsaveemptyfields)) {
            $this->set_config('csvsaveemptyfields', !empty($data->csvsaveemptyfields));
        }

        $previouslylinkedonjobassignmentidnumber = get_config('totara_sync_element_jobassignment', 'previouslylinkedonjobassignmentidnumber');
        if (!empty($previouslylinkedonjobassignmentidnumber)) {
            // If we've previously linked on job assignment id number then ignore the form data.
            $data->updateidnumbers = 0;
        }
        $this->set_config('updateidnumbers', !empty($data->updateidnumbers));

        $this->set_config('sourceallrecords', $data->sourceallrecords);
        $this->set_config('allow_create', !empty($data->allow_create));
        $this->set_config('allow_update', !empty($data->allow_update));
        $this->set_config('allow_delete', !empty($data->allow_delete));
    }

    /**
     * Create, update and delete live data according to the imported data.
     *
     * @return bool True if completed successfully.
     * @throws totara_sync_exception
     */
    public function sync() {
        global $DB;

        $this->addlog(get_string('syncstarted', 'tool_totara_sync'), 'info', "sync");
        if (!$synctable = $this->get_source_sync_table()) {
            throw new totara_sync_exception($this->get_name() . "sync", $this->get_name() . "sync", 'couldnotgetsourcetable');
        }

        // Make sure that if you've linked on job assignment id number in the past, you can't link on first job assignment now.
        $previouslylinkedonjobassignmentidnumber = get_config('totara_sync_element_jobassignment', 'previouslylinkedonjobassignmentidnumber');
        if (!empty($this->config->updateidnumbers) && !empty($previouslylinkedonjobassignmentidnumber)) {
            $this->addlog(get_string('previouslylinkedmismatch', 'tool_totara_sync'), 'error', 'sync');
            return false;
        }

        // Initialise to safe defaults if settings not present.
        if (!isset($this->config->sourceallrecords)) {
            $this->config->sourceallrecords = 0;
        }
        if (!isset($this->config->allow_create)) {
            $this->config->allow_create = 0;
        }
        if (!isset($this->config->allow_update)) {
            $this->config->allow_update = 0;
        }
        if (!isset($this->config->allow_delete)) {
            $this->config->allow_delete = 0;
        }

        // Do any delete actions first so that existing records
        // that reference them are identified as invalid.
        if ($this->config->allow_delete) {
            if ($this->config->sourceallrecords) {
                $sql = "SELECT ja.id, ja.idnumber, u.idnumber AS useridnumber
                          FROM {job_assignment} ja
                     LEFT JOIN {user} u
                            ON ja.userid = u.id
                     LEFT JOIN {" . $synctable . "} s
                            ON ja.idnumber = s.idnumber
                           AND u.idnumber != ''
                           AND u.idnumber IS NOT NULL
                           AND u.idnumber = s.useridnumber
                         WHERE s.idnumber IS NULL
                           AND ja.totarasync = 1";
            } else {
                $sql = "SELECT ja.id, s.idnumber, s.useridnumber
                          FROM {job_assignment} ja
                     LEFT JOIN {user} u
                            ON ja.userid = u.id
                     LEFT JOIN {" . $synctable . "} s
                            ON ja.idnumber = s.idnumber
                           AND u.idnumber != ''
                           AND u.idnumber IS NOT NULL
                           AND u.idnumber = s.useridnumber
                         WHERE s.deleted = 1
                           AND ja.totarasync = 1";
            }

            $deleterecords = $DB->get_recordset_sql($sql);
            foreach($deleterecords as $deleterecord) {
                $deletejobassignment = \totara_job\job_assignment::get_with_id($deleterecord->id, false);
                if ($deletejobassignment) {
                    \totara_job\job_assignment::delete($deletejobassignment);
                    $this->addlog(get_string('deletedjobassignmentx', 'tool_totara_sync', $deleterecord), 'info', 'delete');
                }
            }
            $deleterecords->close();
        }

        if ($this->get_source()->is_importing_field('deleted')) {
            $DB->delete_records($synctable, array('deleted' => 1));
        }

        // Prior to the sanity check, there are some cases to remove.

        // Remove any that are missing idnumber or useridnumber
        // as there'll be little else we can do with them.
        $this->remove_entries_missing_required_fields($synctable);

        // Remove based on timemodified, there's nothing wrong with these having been in the sync table,
        // we just consider them to have already been done.
        $this->remove_entries_based_on_timemodified($synctable);

        // Perform the sanity check. Then delete entries from the sync table that are invalid.
        $idstodelete = $this->check_sanity($synctable);
        $idtodelete_sets = array_chunk($idstodelete, $DB->get_max_in_params());
        foreach ($idtodelete_sets as $idtodelete_set) {
            list($deletesql, $deleteparams) = $DB->get_in_or_equal($idtodelete_set);
            $DB->delete_records_select($synctable, "id $deletesql", $deleteparams);
        }

        // Reattempt will hold arrays of values to retry.
        // This is for manager assignments where the manager's job has not yet been created
        // or a loop that may actually be cut once the sync is complete.
        $reattempt = array();

        $sql = $this->build_syncdata_select_query($synctable);
        $rs = $DB->get_recordset_sql($sql);

        foreach ($rs as $record) {
            $newvalues = (array)$record;

            if (isset($newvalues['managerid'])) {
                $error = $this->resolve_managerjaid($newvalues);
                if ($error === 'managerjaxnotexistjobassignment' or $error === 'managerxhasnojobassignment') {
                    // This is the error for when a manager's job assignment doesn't exist.
                    // It might once we retry.
                    $reattempt[] = $newvalues;
                    continue;
                } else if (!empty($error)) {
                    $this->addlog(get_string($error, 'tool_totara_sync', (object)$newvalues), 'error', 'create/update');
                    continue;
                }

            }

            // $newvalues gets modified internally to take out HR Import only stuff, so we copy
            // to keep anything we need to log.
            $copyvalues = $newvalues;
            $error = $this->create_or_update_with_values($newvalues);
            if ($error === 'managementloop') {
                $reattempt[] = $copyvalues;
            } else if (!empty($error)) {
                $this->addlog(get_string($error, 'tool_totara_sync', (object)$copyvalues), 'error', 'create/update');
            }
            // If $error was empty, it was expected to have been successful.
        }

        $rs->close();

        // Retry those that failed due to missing manager job assignments.
        do {
            $precount = count($reattempt);
            $reattempt = array_filter($reattempt, array($this, 'callback_retry_failures_due_to_manager'));

            // If the remaining elements in reattempt has reduced, then we are still successfully processing
            // some of these records.
        } while ((count($reattempt) > 0) and count($reattempt) < $precount);

        // And now record the errors of any that we could not successfully process.
        foreach($reattempt as $newvalues) {
            if (isset($newvalues['managerid'])) {
                $error = $this->resolve_managerjaid($newvalues);
                if ($error) {
                    $this->addlog(get_string($error, 'tool_totara_sync', (object)$newvalues), 'error', 'create/update');
                    continue;
                }
            }
            $copyvalues = $newvalues;
            $error = $this->create_or_update_with_values($newvalues);
            if (!empty($error)) {
                $this->addlog(get_string($error, 'tool_totara_sync', (object)$copyvalues), 'error', 'create/update');
            }
        }

        $this->get_source()->drop_table();
        $this->addlog(get_string('syncfinished', 'tool_totara_sync'), 'info', 'sync');

        if (empty($this->config->updateidnumbers)) {
            // A sync finished and it was set to link job assignments using idnumber. Never again link by first record.
            set_config('previouslylinkedonjobassignmentidnumber', true, 'totara_sync_element_jobassignment');
        }

        return true;
    }

    /**
     * Given an array containing data from an HR Import row. This will add the manager's job assignment id if there is
     * one, under the array key 'managerjaid'. If there's not, an error corresponding to a lang string will be returned.
     *
     * This assumes that we do want to add a manager here. This will return an error if no manager is supposed
     * to be added, so check if we want to use this method before calling it.
     *
     * @param array $newvalues Passed by reference and modified. Contains the data for an HR Import row.
     * @return string which is empty if managerjaid was added successfully, otherwise and the key for a lang string
     *   that describes the relevant error.
     */
    private function resolve_managerjaid(&$newvalues) {
        // If the 'managerjobassignmentidnumber' key is not present, we aren't importing this field.
        // We don't use isset because it could be null.
        if (!array_key_exists('managerjobassignmentidnumber', $newvalues)) {

            $managerjobassignment = \totara_job\job_assignment::get_first($newvalues['managerid'], false);
            if (!isset($managerjobassignment)) {
                return 'managerxhasnojobassignment';
            } else {
                $newvalues['managerjaid'] = $managerjobassignment->id;
                return '';
            }

        } else if (($newvalues['managerjobassignmentidnumber'] === null)
            or ($newvalues['managerjobassignmentidnumber'] === '')) {

            return 'emptymanagerjobassignmentidnumber';
        }

        $managerjobassignment = \totara_job\job_assignment::get_with_idnumber(
            $newvalues['managerid'], $newvalues['managerjobassignmentidnumber'], false);

        if (isset($managerjobassignment)) {
            $newvalues['managerjaid'] = $managerjobassignment->id;
            return '';
        } else {
            return 'managerjaxnotexistjobassignment';
        }
    }

    /**
     * For reattempting any failures from a previous run through sync data.
     * Intended to be used as a callback for array_filter.
     *
     * @param $newvalues
     * @return bool True if the record must be retried (this will keep it in the array when used
     *  with array_filter().
     */
    private function callback_retry_failures_due_to_manager($newvalues) {
        if (isset($newvalues['managerid'])) {
            $error = $this->resolve_managerjaid($newvalues);
            if ($error === 'managerjaxnotexistjobassignment' or $error === 'managerxhasnojobassignment') {
                // Return true to keep the value in the array.
                return true;
            } else if (!empty($error)) {
                $this->addlog(get_string($error, 'tool_totara_sync', (object)$newvalues), 'error', 'create/update');
                return false;
            }
        }

        // $newvalues gets modified internally to take out HR Import only stuff, so we copy
        // to keep anything we need to log.
        $copyvalues = $newvalues;
        $error = $this->create_or_update_with_values($newvalues);
        if ($error === 'managementloop') {
            return true;
        } else if (!empty($error)) {
            $this->addlog(get_string($error, 'tool_totara_sync', (object)$copyvalues), 'error', 'create/update');
            return false;
        }
        // If $error was empty, that should have been successful.

        // Return false to remove from array now that it has been processed.
        return false;
    }

    /**
     * Checks whether a record needs to be created or updated.
     * Checks whether that action has been permitted and then if so, performs it.
     *
     * @param array $newvalues Values to apply to new or updated record.
     * @return string Errors if any, or empty string if successful.
     */
    private function create_or_update_with_values($newvalues) {
        // The id for the synctable should be ignored.
        unset($newvalues['id']);
        $newvalues = $this->filter_null_values($newvalues);

        if (empty($this->config->updateidnumbers)) {
            $jobassignment = \totara_job\job_assignment::get_with_idnumber($newvalues['userid'], $newvalues['idnumber'], false);
        } else {
            $jobassignment = \totara_job\job_assignment::get_first($newvalues['userid'], false);
        }

        if (empty($jobassignment)) {
            if ($this->config->allow_create) {
                $newvalues['totarasync'] = 1;
                $error = $this->check_single_record_validity($newvalues);
                if ($error) {
                    return $error;
                } else {
                    $copyvalues = $newvalues;
                    $this->unset_sync_only_values($newvalues);
                    \totara_job\job_assignment::create($newvalues);
                    $this->addlog(get_string('createdjobassignmentx', 'tool_totara_sync', $copyvalues), 'info', 'create');
                }
            }
        } else {

            if ($this->config->allow_update) {
                if (empty($jobassignment->totarasync)) {
                    return 'jobassignmentsyncdisabled';
                }

                $error = $this->check_single_record_validity($newvalues, $jobassignment);
                if ($error) {
                    return $error;
                } else {
                    $copyvalues = $newvalues;
                    unset($newvalues['userid']);
                    if (empty($this->config->updateidnumbers)) {
                        unset($newvalues['idnumber']);
                    }
                    $this->unset_sync_only_values($newvalues);
                    $jobassignment->update($newvalues);
                    $this->addlog(get_string('updatedjobassignmentx', 'tool_totara_sync', $copyvalues), 'info', 'update');
                }
            }
        }

        return '';
    }

    /**
     * Removes values that should not be present when creating a job assignment.
     * Additional values may need to be removed for updating, e.g. userid should not change
     * in a job assignment.
     *
     * @param array $newvalues from a sync table record (plus any joins).
     */
    private function unset_sync_only_values(&$newvalues) {
        unset($newvalues['useridnumber']);
        unset($newvalues['orgidnumber']);
        unset($newvalues['posidnumber']);
        unset($newvalues['managerid']);
        unset($newvalues['manageridnumber']);
        unset($newvalues['managerjobassignmentidnumber']);
        unset($newvalues['appraiseridnumber']);
    }

    /**
     * Null values mean that a value should not be changed. So this returns an array with those
     * removed.
     *
     * @param array $newvalues Not passed by reference. Use the returned array.
     * @return array with null values removed.
     */
    private function filter_null_values($newvalues) {
        if (isset($newvalues['manageridnumber']) and $newvalues['manageridnumber'] === "") {
            $newvalues['managerjaid'] = "";
        }
        if (isset($newvalues['orgidnumber']) and $newvalues['orgidnumber'] === "") {
            $newvalues['organisationid'] = "";
        }
        if (isset($newvalues['posidnumber']) and $newvalues['posidnumber'] === "") {
            $newvalues['positionid'] = "";
        }
        if (isset($newvalues['appraiseridnumber']) and $newvalues['appraiseridnumber'] === "") {
            $newvalues['appraiserid'] = "";
        }

        $filteredvalues = array();
        foreach ($newvalues as $key => $value) {
            if (isset($value)) {
                $filteredvalues[$key] = $value;
            }
        }

        return $filteredvalues;
    }

    /**
     * Remove any entries missing idnumber or useridnumber as these are required.
     * If any had to be removed, a log entry is added.
     *
     * @param string $table Name of sync table
     */
    private function remove_entries_missing_required_fields($table) {
        global $DB;

        $sql = "SELECT s.id
                  FROM {" . $table . "} s
                 WHERE s.idnumber IS NULL
                    OR s.idnumber = ''
                    OR s.useridnumber IS NULL
                    OR s.useridnumber = ''";
        $missingrequiredfields = $DB->get_fieldset_sql($sql);

        if (!empty($missingrequiredfields)) {
            $this->addlog(get_string('missingrequiredfieldjobassignment', 'tool_totara_sync'), 'error', 'checksanity');

            $idtodelete_sets = array_chunk($missingrequiredfields, $DB->get_max_in_params());
            foreach ($idtodelete_sets as $idtodelete_set) {
                list($deletesql, $deleteparams) = $DB->get_in_or_equal($idtodelete_set);
                $DB->delete_records_select($table, "id $deletesql", $deleteparams);
            }
        }
    }

    /**
     * Remove entries that do not need to be updated as per the current rules on timemodified value
     * of the imported record and the current live data.
     *
     * @param string $table Name of sync table
     */
    private function remove_entries_based_on_timemodified($table) {
        global $DB;

        // Remove those with matching timemodified.
        $where = "EXISTS (
                    SELECT 1
                        FROM {job_assignment} ja
                    WHERE ja.idnumber = {{$table}}.idnumber
                        AND ja.synctimemodified = {{$table}}.timemodified
                )
                AND idnumber IS NOT NULL
                AND idnumber != ''
                AND timemodified != 0";

        // No need for logging here. At present, this is simply expected behaviour for
        // when the record should supposedly already match the data in this entry.
        $DB->delete_records_select($table, $where);
    }

    /**
     * Check sync data for validity prior to getting the record for create and update.
     * This is intended to perform fast bulk checks.
     * See the check_single_record_validity() method for more particular checks that are done
     * per record.
     * This is expected to be run after records marked for deletion have been removed.
     * Invalid records will trigger an entry in the log table, but records themselves
     * must be removed externally.
     *
     * @param string $table Name of the sync table
     * @return array of ids which failed the sanity check
     */
    private function check_sanity($table) {
        global $DB;

        $idstodelete = array();

        if ($this->get_source()->is_importing_field('posidnumber')) {
            // Invalid pos
            $sql = "SELECT s.id, s.idnumber, s.useridnumber, s.posidnumber
                  FROM {" . $table . "} s
             LEFT JOIN {pos} p
                    ON s.posidnumber=p.idnumber
                 WHERE s.posidnumber IS NOT NULL
                   AND s.posidnumber != ''
                   AND p.idnumber IS NULL";
            $invalidposrecords = $DB->get_recordset_sql($sql);
            foreach ($invalidposrecords as $invalidposrecord) {
                $this->addlog(get_string('posxnotexistjobassignment', 'tool_totara_sync', $invalidposrecord), 'error', 'checksanity');
                $idstodelete[] = $invalidposrecord->id;
            }
            $invalidposrecords->close();
        }

        if ($this->get_source()->is_importing_field('orgidnumber')) {
            // Invalid org
            $sql = "SELECT s.id, s.idnumber, s.useridnumber, s.orgidnumber
                  FROM {" . $table . "} s
             LEFT JOIN {org} o
                    ON s.orgidnumber=o.idnumber
                 WHERE s.orgidnumber IS NOT NULL
                   AND s.orgidnumber != ''
                   AND o.idnumber IS NULL";
            $invalidorgrecords = $DB->get_recordset_sql($sql);
            foreach ($invalidorgrecords as $invalidorgrecord) {
                $this->addlog(get_string('orgxnotexistjobassignment', 'tool_totara_sync', $invalidorgrecord), 'error', 'checksanity');
                $idstodelete[] = $invalidorgrecord->id;
            }
            $invalidorgrecords->close();
        }

        if ($this->get_source()->is_importing_field('manageridnumber')) {

            $sql = "SELECT id, idnumber, useridnumber
                      FROM {" . $table . "} s
                     WHERE useridnumber = manageridnumber
                       AND manageridnumber IS NOT NULL
                       AND manageridnumber != ''";
            $selfmanagers = $DB->get_recordset_sql($sql);
            foreach ($selfmanagers as $selfmanager) {
                $this->addlog(get_string('selfassignedmanagerjobassignment', 'tool_totara_sync', $selfmanager), 'error', 'checksanity');
                $idstodelete[] = $selfmanager->id;
            }
            $selfmanagers->close();

            // Invalid manager. Checking manageridnumber only so far as managerjobassignmentid may be added during import.
            $sql = "SELECT s.id, s.idnumber, s.useridnumber, s.manageridnumber
                  FROM {" . $table . "} s
             LEFT JOIN {user} u
                    ON s.manageridnumber=u.idnumber
                 WHERE s.manageridnumber IS NOT NULL
                   AND s.manageridnumber != ''
                   AND u.idnumber IS NULL";
            $invalidmanagerrecords = $DB->get_recordset_sql($sql);
            foreach ($invalidmanagerrecords as $invalidmanagerrecord) {
                $this->addlog(get_string('managerxnotexistjobassignment', 'tool_totara_sync', $invalidmanagerrecord), 'error', 'checksanity');
                $idstodelete[] = $invalidmanagerrecord->id;
            }
            $invalidmanagerrecords->close();
        }

        if ($this->get_source()->is_importing_field('appraiseridnumber')) {

            $sql = "SELECT id, idnumber, useridnumber
                      FROM {" . $table . "} s
                     WHERE useridnumber = appraiseridnumber
                       AND appraiseridnumber IS NOT NULL
                       AND appraiseridnumber != ''";
            $selfappraisers = $DB->get_recordset_sql($sql);
            foreach ($selfappraisers as $selfappraiser) {
                $this->addlog(get_string('selfassignedappraiserjobassignment', 'tool_totara_sync', $selfappraiser), 'error', 'checksanity');
                $idstodelete[] = $selfappraiser->id;
            }
            $selfappraisers->close();

            // Invalid appraiser
            $sql = "SELECT s.id, s.idnumber, s.useridnumber, s.appraiseridnumber
                  FROM {" . $table . "} s
             LEFT JOIN {user} u
                    ON s.appraiseridnumber=u.idnumber
                 WHERE s.appraiseridnumber IS NOT NULL
                   AND s.appraiseridnumber != ''
                   AND u.idnumber IS NULL";
            $invalidappraiserrecords = $DB->get_recordset_sql($sql);
            foreach ($invalidappraiserrecords as $invalidappraiserrecord) {
                $this->addlog(get_string('appraiserxnotexistjobassignment', 'tool_totara_sync', $invalidappraiserrecord), 'error', 'checksanity');
                $idstodelete[] = $invalidappraiserrecord->id;
            }
            $invalidappraiserrecords->close();
        }

        $sql = "SELECT s.idnumber, s.useridnumber
                  FROM {" . $table . "} s
                  GROUP BY s.idnumber, s.useridnumber
                  HAVING COUNT(*) > 1;
                  ";
        $multiples = $DB->get_recordset_sql($sql);
        foreach($multiples as $multiple) {
            $this->addlog(get_string('duplicateentriesjobassignment', 'tool_totara_sync', $multiple), 'error', 'checksanity');

            // Somewhat inefficient. The most efficient way would be having the above sql in a subquery
            // and then getting the ids outside of that. But that is not possible in mysql
            // (it doesn't allow using the table again in the subquery).
            // Another alternative is using using a clone of the table,
            // but suddenly, we have to manage 2 sync tables.
            $idstodelete = array_merge($idstodelete,
            $DB->get_fieldset_select($table, 'id', "idnumber = ? AND useridnumber = ?",
                array($multiple->idnumber, $multiple->useridnumber))
            );
        }
        $multiples->close();

        // Check that the job assignment useridnumber corresponds to a valid user idnumber.
        $sql = "SELECT s.id, s.idnumber, s.useridnumber
                  FROM {" . $table . "} s
             LEFT JOIN {user} u ON s.useridnumber = u.idnumber
                 WHERE u.id IS NULL";
        $nomatchingusers = $DB->get_recordset_sql($sql);

        foreach($nomatchingusers as $nomatchinguser) {
            $this->addlog(get_string('unabletomatchuseridnumber', 'tool_totara_sync', $nomatchinguser), 'error', 'checksanity');
            $idstodelete[] = $nomatchinguser->id;
        }

        $nomatchingusers->close();

        return $idstodelete;
    }

    /**
     * Check the validity of a record prior to create/update.
     *
     * At this point we can afford to perform more particular checks compared to the
     * check_sanity() method.
     *
     * @param array $newvalues Contains values to be updated
     * @param \totara_job\job_assignment|bool $jobassignment If a job assignment exists,
     *   it should be supplied here.
     * @return string Empty if record is valid, otherwise includes an error
     */
    private function check_single_record_validity($newvalues, $jobassignment = false) {
        global $CFG;

        if ($jobassignment) {
            // Add current values where new value is null.
            if (!isset($newvalues['startdate'])) $newvalues['startdate'] = $jobassignment->startdate;
            if (!isset($newvalues['enddate'])) $newvalues['enddate'] = $jobassignment->enddate;
            if (!isset($newvalues['managerjaid'])) $newvalues['managerjaid'] = $jobassignment->managerjaid;
        }

        // Check start and end dates.
        if (!empty($newvalues['startdate']) and !empty($newvalues['enddate'])) {
            if ($newvalues['startdate'] > $newvalues['enddate']) {
                return 'startafterendjobassignment';
            }
        }

        if ($jobassignment and !empty($newvalues['managerjaid'])) {
            $managerja = \totara_job\job_assignment::get_with_id($newvalues['managerjaid']);
            $path = $managerja->managerjapath . '/';

            // Check that the parent managerjapath doesn't contain the same id as we're adding to it.
            if (strpos($path, '/' . $jobassignment->id . '/') !== false) {
                 return 'managementloop';
            }
        }

        if ($jobassignment and ($jobassignment->idnumber !== $newvalues['idnumber'])) {
            // This should only happen if updateidnumber is on. Any other scenario should have
            // been stopped by now, but we're keeping it general rather than checking that setting.
            $existingjobassignment = \totara_job\job_assignment::get_with_idnumber($newvalues['userid'], $newvalues['idnumber'], false);
            if ($existingjobassignment) {
                return 'willcreateduplicatejobidnumber';
            }
        }

        if (empty($CFG->totara_job_allowmultiplejobs) and empty($jobassignment)) {
            // If we're at this point, multiple jobs are not allowed and we are creating a new job assignment.
            if (\totara_job\job_assignment::get_first($newvalues['userid'], false)) {
                return 'multiplejobsdisablednocreate';
            }
            // You'll see that we only accounted for this setting when creating job assignments. When updating,
            // there are already multiple jobs for one reason or another, and we don't know which is supposed
            // to be the user's only job. Sort order is not meant to tell us this.
        }

        return '';
    }

    /**
     * Creates an SQL statement to get data for creation and updating of records from the sync table,
     * joining in associated values from other tables where necessary, e.g. gets the id from the
     * user table given a user idnumber.
     *
     * @param string $table Sync table name
     * @return string SQL for retrieving records that will be created/updated
     */
    private function build_syncdata_select_query($table) {
        $select = array("SELECT s.id, s.idnumber, u.id AS userid, s.useridnumber, s.timemodified AS synctimemodified");
        $from = array("FROM {" . $table . "} s
                 JOIN {user} u
                   ON s.useridnumber=u.idnumber
        ");

        if ($this->get_source()->is_importing_field('fullname')) {
            $select[] = "s.fullname";
        }
        if ($this->get_source()->is_importing_field('startdate')) {
            $select[] = "s.startdate";
        }
        if ($this->get_source()->is_importing_field('enddate')) {
            $select[] = "s.enddate";
        }
        if ($this->get_source()->is_importing_field('orgidnumber')) {
            $select[] = "o.id as organisationid, s.orgidnumber";
            $from[] = "LEFT JOIN {org} o
                              ON s.orgidnumber=o.idnumber
                             AND s.orgidnumber IS NOT NULL
                             AND s.orgidnumber != ''";
        }
        if ($this->get_source()->is_importing_field('posidnumber')) {
            $select[] = "p.id as positionid, s.posidnumber";
            $from[] = "LEFT JOIN {pos} p
                              ON s.posidnumber=p.idnumber
                             AND s.posidnumber IS NOT NULL
                             AND s.posidnumber != ''";
        }
        if ($this->get_source()->is_importing_field('manageridnumber')) {
            // We want to get user id of manager, but we won't go looking for
            // the manager job assignment id yet as that may not have been created.
            // We also still fetch the manageridnumber for logging purposes.
            $select[] = "m.id as managerid, s.manageridnumber";
            if ($this->get_source()->is_importing_field('managerjobassignmentidnumber')) {
                $select[] = "s.managerjobassignmentidnumber";
            }
            $from[] = "LEFT JOIN {user} m
                              ON s.manageridnumber=m.idnumber
                             AND s.manageridnumber IS NOT NULL
                             AND s.manageridnumber != ''";
        }
        if ($this->get_source()->is_importing_field('appraiseridnumber')) {
            $select[] = "a.id as appraiserid, s.appraiseridnumber";
            $from[] = "LEFT JOIN {user} a
                              ON s.appraiseridnumber=a.idnumber
                             AND s.appraiseridnumber IS NOT NULL
                             AND s.appraiseridnumber != ''";
        }

        return implode(", ", $select) . "\n" . implode(" \n", $from);
    }
}
