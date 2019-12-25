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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_job
 */

namespace totara_job;

use Horde\Socket\Client\Exception;
use totara_job\event\job_assignment_viewed;
use totara_job\event\job_assignment_updated;

defined('MOODLE_INTERNAL') || die();

/**
 * Class job_assignment.
 *
 * @property-read int id                      automatic (set when job assignment is created)
 * @property-read int userid                  required (can only be set when ja is created, cannot be changed)
 * @property-read string fullname             optional (if empty, will return a default name)
 * @property-read string shortname            optional
 * @property-read string idnumber             required (must be unique for each user)
 * @property-read string description          dynamic (calculated from actual description)
 * @property-read string description_editor   dynamic (calculated from actual description)
 * @property-read int timecreated             automatic (set when job assignment is created)
 * @property-read int timemodified            automatic (set when job assignment is updated)
 * @property-read int usermodified            automatic (set when job assignment is updated)
 * @property-read int positionid              optional
 * @property-read int positionassignmentdate  automatic (set when positionid changes, including when positionid is empty)
 * @property-read int organisationid          optional
 * @property-read int startdate               optional
 * @property-read int enddate                 optional
 * @property-read int managerid               dynamic (if assigned, uses 1 db call to retrieve from manager job assignment)
 * @property-read int managerjaid             optional
 * @property-read string managerjapath        automatic (set when managerjaid changes, including when managerjaid is empty)
 * @property-read int teamleaderid            dynamic (if assigned, uses 1 db call to retrieve from manager's manager job assignment)
 * @property-read int tempmanagerid           dynamic (if assigned, uses 1 db call to retrieve from temp manager job assignment)
 * @property-read int tempmanagerjaid         optional
 * @property-read int tempmanagerexpirydate   optional
 * @property-read int appraiserid             optional
 * @property-read int sortorder               automatic (set when job assignment is created, modified by functions)
 * @property-read int totarasync              optional (defaults to zero, should be set to 1 if updates via HR Import are desired)
 * @property-read int synctimemodified        optional (defaults to zero, represents the last time this record was updated in the external source)
 *
 * @package totara_job
 */
class job_assignment {

    /**
     * Job assignment id.
     *
     * @var int
     */
    private $id;

    /**
     * User id
     *
     * @var int
     */
    private $userid;

    /**
     * Job assignment fullname. Use __get if accessing within this class to get the default string.
     *
     * @var string
     */
    private $fullname;

    /**
     * Job assignment shortname.
     *
     * @var string
     */
    private $shortname;

    /**
     * Job assignment idnumber.
     *
     * @var string
     */
    private $idnumber;

    /**
     * Job assignment description.
     *
     * @var string
     */
    private $description = '';

    /**
     * Time job assignment was created.
     *
     * @var int
     */
    private $timecreated;

    /**
     * Time job assignment was modified.
     *
     * @var int
     */
    private $timemodified;

    /**
     * User who last modified this job assignment.
     *
     * @var int
     */
    private $usermodified;

    /**
     * Position id.
     *
     * @var int
     */
    private $positionid = null;

    /**
     * Date/time that the user was assigned to the position.
     *
     * @var int
     */
    private $positionassignmentdate = null;

    /**
     * Organisation id.
     *
     * @var int
     */
    private $organisationid = null;

    /**
     * Time that this job assignment starts - this is an information field, but can be used in audience rules.
     *
     * @var int
     */
    private $startdate = null;

    /**
     * Time that this position is valid until.
     *
     * @var int
     */
    private $enddate = null;

    /**
     * The job assignment of the user who is manager of this job assignment.
     *
     * @var int|null
     */
    private $managerjaid = null;

    /**
     * Job assignment path above this user (this job assignment, manger's job assignment, manager's manager's job assignment, ... in reverse order).
     *
     * @var string
     */
    private $managerjapath;

    /**
     * The job assignment of the user who is temp manager of this job assignment.
     *
     * @var int|null
     */
    private $tempmanagerjaid = null;

    /**
     * The expiry date / time of the temp manager.
     *
     * @var int|null
     */
    private $tempmanagerexpirydate = null;

    /**
     * Appraiser id.
     *
     * @var int
     */
    private $appraiserid = null;

    /**
     * Sort order - starts at 1
     *
     * @var int
     */
    private $sortorder;

    /**
     * Whether or not this can be updated via HR Import. 1 means that it can.
     *
     * @var int
     */
    private $totarasync = 0;

    /**
     * The last time this record was updated in the external source. This value should either come
     * directly from the HR Import data, representing how old that data was,
     * or else specify the time of import if no external value was provided.
     *
     * @var int
     */
    private $synctimemodified = 0;

    /**
     * Create instance of a job_assignment.
     *
     * @param \stdClass $record as returned by get_record('job_assignment', ...)
     */
    private function __construct($record) {
        $this->apply_record_data($record);
    }

    /**
     * Set database data.
     *
     * @param \stdClass $record
     */
    private function apply_record_data($record) {
        $this->id = $record->id;
        $this->userid = $record->userid;
        $this->idnumber = $record->idnumber;
        $this->timecreated = $record->timecreated;
        $this->timemodified = $record->timemodified;
        $this->usermodified = $record->usermodified;
        $this->managerjapath = $record->managerjapath;
        $this->sortorder = $record->sortorder;
        $this->positionassignmentdate = $record->positionassignmentdate;

        if (isset($record->fullname) && $record->fullname !== "") {
            $this->fullname = $record->fullname;
        } else {
            $this->fullname = null;
        }
        if (isset($record->shortname) && $record->shortname !== "") {
            $this->shortname = $record->shortname;
        } else {
            $this->shortname = null;
        }
        if (isset($record->idnumber) && $record->idnumber !== "") {
            $this->idnumber = $record->idnumber;
        } else {
            $this->idnumber = null;
        }
        if (!empty($record->description)) {
            $this->description = $record->description;
        } else {
            $this->description = '';
        }
        if (!empty($record->positionid)) {
            $this->positionid = $record->positionid;
        } else {
            $this->positionid = null;
        }
        if (!empty($record->organisationid)) {
            $this->organisationid = $record->organisationid;
        } else {
            $this->organisationid = null;
        }
        if (!empty($record->startdate)) {
            $this->startdate = $record->startdate;
        } else {
            $this->startdate = null;
        }
        if (!empty($record->enddate)) {
            $this->enddate = $record->enddate;
        } else {
            $this->enddate = null;
        }
        if (!empty($record->managerjaid)) {
            $this->managerjaid = $record->managerjaid;
        } else {
            $this->managerjaid = null;
        }
        if (!empty($record->tempmanagerjaid)) {
            $this->tempmanagerjaid = $record->tempmanagerjaid;
        } else {
            $this->tempmanagerjaid = null;
        }
        if (!empty($record->tempmanagerexpirydate)) {
            $this->tempmanagerexpirydate = $record->tempmanagerexpirydate;
        } else {
            $this->tempmanagerexpirydate = null;
        }
        if (!empty($record->appraiserid)) {
            $this->appraiserid = $record->appraiserid;
        } else {
            $this->appraiserid = null;
        }
        if (!empty($record->totarasync)) {
            $this->totarasync = $record->totarasync;
        }
        if (!empty($record->synctimemodified)) {
            $this->synctimemodified = $record->synctimemodified;
        }
    }

    /**
     * Create a new job_assignment from the given data and save it to the db.
     *
     * Processes description_editor, so you don't have to.
     *
     * @param array|\stdClass $data key/values for a new record (keys must include userid, idnumber)
     * @return job_assignment
     */
    public static function create($data) {
        global $CFG, $DB, $TEXTAREA_OPTIONS, $USER;

        if (!is_array($data)) {
            $data = (array)$data;
        }

        // Verify the specified data is sufficient.
        if (empty($data['userid'])) {
            throw new exception('User id is required when creating new job assignment');
        }
        if (!isset($data['idnumber']) || $data['idnumber'] === "") {
            throw new exception('ID Number is required when creating new job assignment');
        }
        if (empty($CFG->totara_job_allowmultiplejobs) && $DB->record_exists('job_assignment', ['userid' => $data['userid']])) {
            throw new exception('Attempting to create multiple job assignments for user');
        }
        foreach ($data as $key => $value) {
            if (!in_array($key, array('userid', 'fullname', 'shortname', 'idnumber', 'description', 'description_editor',
                                      'positionid', 'organisationid', 'startdate', 'enddate', 'managerjaid',
                                      'tempmanagerjaid', 'tempmanagerexpirydate', 'appraiserid', 'totarasync', 'synctimemodified'))) {
                throw new exception('Invalid field specified when creating new job assignment');
            }
        }
        if (!empty($data['tempmanagerjaid']) XOR !empty($data['tempmanagerexpirydate'])) {
            throw new exception('Temporary manager and expiry date must either both be provided or both be empty in job_assignment::create');
        }

        $record = (object)$data;

        // Check that the short name is unique for this user.
        if ($DB->record_exists('job_assignment', array('userid' => $record->userid, 'idnumber' => $record->idnumber))) {
            throw new Exception('Tried to create job assignment idnumber which is not unique for this user');
        }

        // Remove the description_editor and save it for later.
        if (property_exists($record, 'description_editor')) {
            $descriptioneditor = $record->description_editor;
            unset($record->description_editor);
        } else {
            $descriptioneditor = false;
        }

        $now = time();
        $record->timecreated = $now;
        $record->timemodified = $now;
        $record->usermodified = $USER->id;
        $record->positionassignmentdate = $now;

        // Put the job_assignment at the end of the list.
        $maxsortorder = $DB->get_field('job_assignment', 'MAX(sortorder)', array('userid' => $record->userid), IGNORE_MISSING);
        if ($maxsortorder === false) {
            $record->sortorder = 1;
        } else {
            $record->sortorder = $maxsortorder + 1;
        }

        $transaction = $DB->start_delegated_transaction();
        try {
            // Create the record and find the new job_assignment id.
            $record->id = $DB->insert_record('job_assignment', $record);

            // Save the extra data which needed a record id before it could be saved.
            // Make sure to save all extrafields data into $record.
            $extrafields = new \stdClass();
            $extrafields->id = $record->id;

            if (empty($record->managerjaid)) {
                $record->managerjaid = null;
            }
            $extrafields->managerjapath = self::calculate_managerjapath($record->id, $record->managerjaid);
            $record->managerjapath = $extrafields->managerjapath;

            if (!empty($descriptioneditor)) {
                $extrafields->description_editor = $descriptioneditor;
                file_postupdate_standard_editor($extrafields, 'description', $TEXTAREA_OPTIONS, $TEXTAREA_OPTIONS['context'],
                    'totara_job', 'job_assignment', $extrafields->id);
                $record->description = $extrafields->description;
            }

            $DB->update_record('job_assignment', $extrafields);

            // Fetch from database to get correct data types and defaults.
            $record = $DB->get_record('job_assignment', array('id' => $record->id), '*', MUST_EXIST);

            // Record is identical to the data in the database, so we can create the object from it.
            $jobassignment = new job_assignment($record);

            $jobassignment->updated_manager(null);
            $jobassignment->updated_temporary_manager(null, null);

            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
        }

        $event = job_assignment_updated::create(
            array(
                'objectid' => $jobassignment->id,
                'context' => \context_system::instance(),
                'relateduserid' => $jobassignment->userid,
                'other' => array(
                    'oldmanagerjaid' => null,
                    'oldmanagerjapath' => $jobassignment->managerjapath,
                    'oldpositionid' => null,
                    'oldorganisationid' => null,
                ),
            )
        );
        $event->trigger();

        // Return the job_assignment object.
        return $jobassignment;
    }

    /**
     * Create a default job assignment for the specified user. Is saved to the db.
     *
     * @param $userid
     * @param array|\stdClass $data key/values for a new record
     * @return job_assignment
     */
    public static function create_default($userid, $data = array()) {
        global $DB;

        if (!is_array($data)) {
            $data = (array)$data;
        }

        if (isset($data['userid']) && $data['userid'] != $userid) {
            throw new exception("Mismatched userids specified when creating default job assignment");
        }

        if (empty($data['userid'])) {
            $data['userid'] = $userid;
        }
        if (!array_key_exists('idnumber', $data)) {
            $data['idnumber'] = 1;
            while ($DB->record_exists('job_assignment', array('userid' => $data['userid'], 'idnumber' => $data['idnumber']))) {
                $data['idnumber']++;
            }
        }

        return self::create($data);
    }

    /**
     * Calculate the manager job assignment path for the given job assignment.
     *
     * Path is: ".../manager's manager's jaid/manager's jaid/user's jaid".
     *
     * This function will throw an exception if a reference loop is created!
     *
     * @param int $id of the job assignment
     * @param int|null $managerjaid
     * @return string
     */
    private static function calculate_managerjapath($id, $managerjaid = null) {
        if (!empty($managerjaid)) {
            $managerja = job_assignment::get_with_id($managerjaid);
            $path = $managerja->managerjapath . '/';

            // Check that the parent managerjapath doesn't contain the same id as we're adding to it.
            if (strpos($path, '/' . $id . '/') !== false) {
                throw new exception("Tried to create a manager path loop in job_assignment::calculate_managerjapath");
            }
        } else {
            $path = '/';
        }
        $path .= $id;
        return $path;
    }

    /**
     * Allow read access to properties
     *
     * Processes description_editor (when you ask for description), so you don't have to.
     *
     * @param string $name
     * @return mixed type depends on field accessed
     */
    public function __get($name) {
        global $DB, $TEXTAREA_OPTIONS;

        if ($name === 'description') {
            $description = file_rewrite_pluginfile_urls($this->description, 'pluginfile.php', $TEXTAREA_OPTIONS['context']->id,
                'totara_job', 'job_assignment', $this->id);
            return $description;

        } else if ($name === 'description_editor') {
            $obj = new \stdClass();
            $obj->descriptionformat = FORMAT_HTML;
            $obj->description = $this->description;
            file_prepare_standard_editor($obj, 'description', $TEXTAREA_OPTIONS, $TEXTAREA_OPTIONS['context'],
                'totara_job', 'job_assignment', $this->id);
            return $obj->description_editor;

        } else if ($name === 'managerid') {
            if ($this->managerjaid) {
                $sql = "SELECT managerja.userid
                          FROM {job_assignment} managerja
                         WHERE managerja.id = :managerjaid";
                $result = $DB->get_field_sql($sql, array('managerjaid' => $this->managerjaid));
                if ($result) {
                    return $result;
                }
            }
            return null;

        } else if ($name === 'teamleaderid') {
            if ($this->managerjaid) {
                $sql = "SELECT teamleadja.userid
                          FROM {job_assignment} managerja
                          JOIN {job_assignment} teamleadja ON managerja.managerjaid = teamleadja.id
                         WHERE managerja.id = :managerjaid";
                $result = $DB->get_field_sql($sql, array('managerjaid' => $this->managerjaid));
                if ($result) {
                    return $result;
                }
            }
            return null;

        } else if ($name === 'tempmanagerid') {
            if ($this->tempmanagerjaid) {
                $sql = "SELECT tempmanagerja.userid
                          FROM {job_assignment} tempmanagerja
                         WHERE tempmanagerja.id = :tempmanagerjaid";
                $result = $DB->get_field_sql($sql, array('tempmanagerjaid' => $this->tempmanagerjaid));
                if ($result) {
                    return $result;
                }
            }
            return null;

        } else if ($name === 'fullname') {
            if (!isset($this->fullname) || $this->fullname === "") {
                return get_string('jobassignmentdefaultfullname', 'totara_job', $this->idnumber);
            } else {
                return $this->fullname;
            }

        } else if (in_array($name, array('id', 'userid', 'shortname', 'idnumber', 'timecreated', 'timemodified', 'usermodified',
                                         'positionid', 'positionassignmentdate', 'organisationid', 'startdate', 'enddate',
                                         'managerjaid', 'managerjapath', 'tempmanagerjaid', 'tempmanagerexpirydate',
                                         'appraiserid', 'sortorder', 'totarasync', 'synctimemodified'))) {
            return $this->$name;

        } else {
            throw new exception("Tried to get job assignment property that cannot be retrieved (not allowed or doesn't exist).");
        }
    }

    /**
     * Returns whether property is one where __get() will return a value.
     *
     * @param $name
     * @return bool
     */
    public function __isset($name) {
        // Build array of the properties where a value is returned by __get().
        $getproperties = array('id', 'userid', 'shortname', 'idnumber', 'timecreated', 'timemodified', 'usermodified',
            'positionid', 'positionassignmentdate', 'organisationid', 'startdate', 'enddate',
            'managerjaid', 'managerjapath', 'tempmanagerjaid', 'tempmanagerexpirydate',
            'appraiserid', 'sortorder', 'totarasync', 'synctimemodified');
        $getproperties[] = 'description';
        $getproperties[] = 'description_editor';
        $getproperties[] = 'managerid';
        $getproperties[] = 'teamleaderid';
        $getproperties[] = 'tempmanagerid';
        $getproperties[] = 'fullname';

        return in_array($name, $getproperties);
    }

    /**
     * Returns a standard class containing the readable properties of the object, usable in forms
     *
     * Processes description_editor, so you don't have to.
     *
     * @return \stdClass with all public properties
     */
    public function get_data() {
        $data = new \stdClass();

        $data->id                     = $this->id;
        $data->userid                 = $this->userid;
        $data->fullname               = $this->__get('fullname');
        $data->shortname              = $this->shortname;
        $data->idnumber               = $this->idnumber;
        $data->description_editor     = $this->__get('description_editor');
        $data->timecreated            = $this->timecreated;
        $data->timemodified           = $this->timemodified;
        $data->usermodified           = $this->usermodified;
        $data->positionid             = $this->positionid;
        $data->positionassignmentdate = $this->positionassignmentdate;
        $data->organisationid         = $this->organisationid;
        $data->startdate              = $this->startdate;
        $data->enddate                = $this->enddate;
        $data->managerid              = $this->managerid;
        $data->managerjaid            = $this->managerjaid;
        $data->managerjapath          = $this->managerjapath;
        $data->teamleaderid           = $this->teamleaderid;
        $data->tempmanagerid          = $this->tempmanagerid;
        $data->tempmanagerjaid        = $this->tempmanagerjaid;
        $data->tempmanagerexpirydate  = $this->tempmanagerexpirydate;
        $data->appraiserid            = $this->appraiserid;
        $data->sortorder              = $this->sortorder;
        $data->totarasync             = $this->totarasync;
        $data->synctimemodified       = $this->synctimemodified;

        return $data;
    }

    /**
     * Updates current job_assignment properties.
     *
     * Processes description_editor, so you don't have to.
     *
     * Note that changing userid is not allowed (a job assignment can't be moved from one user to another).
     *
     * @param array|\stdClass $data key/value pairs that need to be updated in this job_assignment
     */
    public function update($data) {
        if (!is_array($data)) {
            $data = (array)$data;
        }
        if (empty($data)) {
            return;
        }
        foreach ($data as $key => $value) {
            if (!in_array($key, array('fullname', 'shortname', 'idnumber', 'description', 'description_editor', 'positionid',
                                      'organisationid', 'startdate', 'enddate', 'managerjaid',
                                      'tempmanagerjaid', 'tempmanagerexpirydate', 'appraiserid', 'totarasync', 'synctimemodified'))) {
                throw new exception("Invalid field specified when updating job_assignment (not allowed or doesn't exist).");
            }
        }

        $this->update_internal($data);
    }

    /**
     * Updates current job_assignment properties. Used internally - no restrictions on fields.
     *
     * Processes description_editor, so you don't have to.
     *
     * @param array $data key => value pairs that need to be updated in this job_assignment
     */
    private function update_internal(array $data) {
        global $DB, $TEXTAREA_OPTIONS, $USER;

        // If a new idnumber is supplied.
        if (array_key_exists('idnumber', $data)) {
            if (is_null($data['idnumber']) || $data['idnumber'] === "") {
                // Null and empty string are not allow (but "0" is, so not checking "empty").
                throw new Exception('Tried to update job assignment idnumber to an empty value which is not allowed');
            }

            // Check that it is unique for this user.
            if ($data['idnumber'] != $this->idnumber &&
                $DB->record_exists('job_assignment', array('userid' => $this->userid, 'idnumber' => $data['idnumber']))) {
                throw new Exception('Tried to update job assignment to an idnumber which is not unique for this user');
            }
        }

        if (!empty($data['tempmanagerjaid']) XOR !empty($data['tempmanagerexpirydate'])) {
            throw new exception('Temporary manager and expiry date must either both be provided or both be empty in job_assignment::update_internal');
        }

        // Create a record ready for $DB->update_record.
        $record = new \stdClass();
        $record->id = $this->id;

        // Process the given data.
        foreach ($data as $key => $value) {
            $record->$key = $value;
        }

        // Process the description_editor.
        if (property_exists($record, 'description_editor')) {
            file_postupdate_standard_editor($record, 'description', $TEXTAREA_OPTIONS, $TEXTAREA_OPTIONS['context'],
                'totara_job', 'job_assignment', $this->id);
        }

        $now = time();
        $record->timemodified = $now;
        $record->usermodified = $USER->id;

        if (property_exists($record, 'positionid') && $record->positionid != $this->positionid) {
            $record->positionassignmentdate = $now;
        }

        if (property_exists($record, 'managerjaid')) {
            $record->managerjapath = self::calculate_managerjapath($record->id, $record->managerjaid);
        }

        $oldmanagerjaid = $this->managerjaid;
        $oldmanagerjapath = $this->managerjapath;
        $oldtempmanagerjaid = $this->tempmanagerjaid;
        $oldtempmanagerexpirydate = $this->tempmanagerexpirydate;
        $oldpositionid = $this->positionid;
        $oldorganisationid = $this->organisationid;

        $currentdata = (object)(array)($this);
        $transaction = $DB->start_delegated_transaction();
        try {
            // Update the database.
            $DB->update_record('job_assignment', $record);

            // Fetch from database to get correct data types and the most recent data.
            $record = $DB->get_record('job_assignment', array('id' => $record->id), '*', MUST_EXIST);
            $this->apply_record_data($record);

            // Update related users.
            $this->updated_manager($oldmanagerjaid);
            $this->updated_temporary_manager($oldtempmanagerjaid, $oldtempmanagerexpirydate);

            $transaction->allow_commit();
        } catch (Exception $e) {
            $this->apply_record_data($currentdata);
            $transaction->rollback($e);
        }
        unset($currentdata);

        $event = job_assignment_updated::create(
            array(
                'objectid' => $this->id,
                'context' => \context_system::instance(),
                'relateduserid' => $this->userid,
                'other' => array(
                    'oldmanagerjaid' => $oldmanagerjaid,
                    'oldmanagerjapath' => $oldmanagerjapath,
                    'oldpositionid' => $oldpositionid,
                    'oldorganisationid' => $oldorganisationid,
                ),
            )
        );
        $event->trigger();
    }

    /**
     * Call when the manager might have changed. Will update the role assignments of the manager
     * and the manager paths of all (management) children.
     *
     * Figures out if any change has actually occurred and does nothing if there is no change.
     *
     * @param int $oldmanagerjaid
     */
    private function updated_manager($oldmanagerjaid) {
        if (empty($oldmanagerjaid) && empty($this->managerjaid)) {
            // No manager before or after.
            return;
        }

        if ($oldmanagerjaid == $this->managerjaid) {
            // No change to job assignments.
            return;
        }

        // The manager job assignment has changed, so update the role assignments. It's possible that the manager hasn't
        // changed, but that's checked inside the function.
        $this->update_manager_role_assignments($oldmanagerjaid, $this->managerjaid);

        // Update the manager paths. These have to be changed if the job assignment changes, regardless of whether they
        // belong to the same manager or not.
        $this->update_descendant_manager_paths();
    }

    /**
     * Given old and new managers, remove old manager role and add new manager role. Works with temp managers as well.
     *
     * Figures out if any change has actually occurred and does nothing if there is no change.
     *
     * @param int $oldmanagerjaid
     * @param int $newmanagerjaid
     */
    private function update_manager_role_assignments($oldmanagerjaid, $newmanagerjaid) {
        global $CFG;

        if (empty($oldmanagerjaid) && empty($newmanagerjaid)) {
            // No managers involved at all, so nothing to do.
            return;
        }

        if ($oldmanagerjaid == $newmanagerjaid) {
            // The manager job assignment hasn't changed.
            return;
        }

        if (!empty($oldmanagerjaid)) {
            $oldmanagerja = self::get_with_id($oldmanagerjaid);
        }

        if (!empty($newmanagerjaid)) {
            $newmanagerja = self::get_with_id($newmanagerjaid);
        }

        if (!empty($oldmanagerja) && !empty($newmanagerja) && $oldmanagerja->userid == $newmanagerja->userid) {
            // The job assignment has changed, but they both belong to the same manager.
            return;
        }

        $context = \context_user::instance($this->userid, IGNORE_MISSING);
        if (!$context) {
            // Without a context we can't unassign. This could have happend during deletion of the user.
            return;
        }
        $roleid = $CFG->managerroleid;

        // Delete role assignment if there was an old manager, because it was removed.
        if (!empty($oldmanagerja)) {
            if (!self::is_managing($oldmanagerja->userid, $this->userid)) {
                // There is no other job assignment where the old manager is still managing the user, so unassign.
                role_unassign($roleid, $oldmanagerja->userid, $context->id);
            }
        }

        // Create role assignment if there is a current/new manager, because it was just added.
        if (!empty($newmanagerja)) {
            // Function role_assign is safe to call if the user is already assigned, so no need to check.
            role_assign($roleid, $newmanagerja->userid, $context->id);
        }
    }

    /**
     * Updates the manager job assignment paths of all management subordinates.
     *
     * Assumes that a change has occurred (check before calling) and that the manager's ja path has been updated.
     *
     * What's actually happening: This function was called because this user's manager changed. We need to update all
     * the manager ja paths for all job assignments of which this job assignment is manager, and below. The set of users
     * can't have changed due to this update - the same set of users will be managed after the update, but their manager
     * path needs to be updated to include the new manager's manager (and above). The manager's ja path (above) must
     * have already been recalculated before calling this function.
     */
    private function update_descendant_manager_paths() {
        global $DB;

        $newjapath = $this->managerjapath;

        $length_sql = $DB->sql_length("'/{$this->id}/'");
        $position_sql = $DB->sql_position("'/{$this->id}/'", 'managerjapath');
        $substr_sql = $DB->sql_substr('managerjapath', "$position_sql + $length_sql");

        $managerjapath = $DB->sql_concat("'{$newjapath}/'", $substr_sql);
        $now = time();
        $like = $DB->sql_like('managerjapath', ':likethisid');
        $sql = "UPDATE {job_assignment}
                   SET managerjapath = {$managerjapath},
                       timemodified = :now
                 WHERE $like";
        $params = array('likethisid' => "%/{$this->id}/%", 'now' => $now);

        if (!$DB->execute($sql, $params)) {
            throw new exception('job_assignment::update_descendant_manager_paths: Could not update manager path of child items in manager hierarchy');
        }
    }

    /**
     * Call when the temp manager might have changed. Will update the role assignments of the temp manager
     * and trigger all messages that happen as the result of a change of temporary manager (including date change).
     *
     * Figures out if any change has actually occurred and does nothing if there is no change.
     *
     * Note that if the temporary manager stays the same but the job assignment is changed, this does not trigger new messages.
     *
     * @param int|null $oldtempmanagerjaid
     * @param int|null $oldtempmanagerexpirydate
     */
    private function updated_temporary_manager($oldtempmanagerjaid, $oldtempmanagerexpirydate) {
        global $CFG, $DB, $USER;

        if (empty($oldtempmanagerjaid) && empty($this->tempmanagerjaid)) {
            // No temporary manager before or after.
            return;
        }

        if ($oldtempmanagerexpirydate == $this->tempmanagerexpirydate && $oldtempmanagerjaid == $this->tempmanagerjaid) {
            // Nothing at all has changed.
            return;
        }

        if ($oldtempmanagerjaid != $this->tempmanagerjaid) {
            // Only need to update roles if the temp manager job assignment changed. It's possible that the manager
            // hasn't changed, but that's checked inside the function.
            $this->update_manager_role_assignments($oldtempmanagerjaid, $this->tempmanagerjaid);
        }

        if (empty($this->tempmanagerjaid)) {
            // No new temporary manager, just temp manager was removed, so nothing to do.
            return;
        }

        // Set up some stuff.
        $user = $DB->get_record('user', array('id' => $this->userid));
        $newtempmanager = $DB->get_record('user', array('id' => $this->tempmanagerid));
        $realmanager = $this->managerjaid ? $DB->get_record('user', array('id' => $this->managerid)) : null;

        require_once($CFG->dirroot.'/totara/message/messagelib.php');
        $msg = new \stdClass();
        $msg->userfrom = $USER;
        $msg->msgstatus = TOTARA_MSG_STATUS_OK;
        $msg->contexturl = $CFG->wwwroot.'/totara/job/jobassignment.php?jobassignmentid='.$this->id;
        $msg->contexturlname = get_string('xpositions', 'totara_job', fullname($user));
        $msgparams = (object)array(
            'staffmember' => fullname($user),
            'tempmanager' => fullname($newtempmanager),
            'expirytime' => userdate($this->tempmanagerexpirydate, get_string('strftimedatefulllong', 'langconfig')),
            'url' => $msg->contexturl
        );

        if (!empty($oldtempmanagerjaid) && self::get_with_id($oldtempmanagerjaid)->userid == $newtempmanager->id) {
            // The temporary manager hasn't changed.

            if ($oldtempmanagerexpirydate != $this->tempmanagerexpirydate) {
                // Only the expiry time has changed, so send out expiry update notifications.

                // Notify staff member.
                $msg->userto = $user;
                $msg->subject = get_string('tempmanagerexpiryupdatemsgstaffsubject', 'totara_job', $msgparams);
                $msg->fullmessage = get_string('tempmanagerexpiryupdatemsgstaff', 'totara_job', $msgparams);
                $msg->fullmessagehtml = get_string('tempmanagerexpiryupdatemsgstaff', 'totara_job', $msgparams);
                tm_alert_send($msg);

                // Notify real manager.
                if (!empty($realmanager)) {
                    $msg->userto = $realmanager;
                    $msg->subject = get_string('tempmanagerexpiryupdatemsgmgrsubject', 'totara_job', $msgparams);
                    $msg->fullmessage = get_string('tempmanagerexpiryupdatemsgmgr', 'totara_job', $msgparams);
                    $msg->fullmessagehtml = get_string('tempmanagerexpiryupdatemsgmgr', 'totara_job', $msgparams);
                    $msg->roleid = $CFG->managerroleid;
                    tm_alert_send($msg);
                }

                // Notify temp manager.
                $msg->userto = $newtempmanager;
                $msg->subject = get_string('tempmanagerexpiryupdatemsgtmpmgrsubject', 'totara_job', $msgparams);
                $msg->fullmessage = get_string('tempmanagerexpiryupdatemsgtmpmgr', 'totara_job', $msgparams);
                $msg->fullmessagehtml = get_string('tempmanagerexpiryupdatemsgtmpmgr', 'totara_job', $msgparams);
                $msg->roleid = $CFG->managerroleid;
                tm_alert_send($msg);
            } else {
                // The time hasn't changed either. Must have been a change to job assignment, with the same temp manager.
                return;
            }
        } else {
            // The temporary manager has changed, so send temporary manager assignment notifications.

            // Notify staff member.
            $msg->userto = $user;
            $msg->subject = get_string('tempmanagerassignmsgstaffsubject', 'totara_job', $msgparams);
            $msg->fullmessage = get_string('tempmanagerassignmsgstaff', 'totara_job', $msgparams);
            $msg->fullmessagehtml = get_string('tempmanagerassignmsgstaff', 'totara_job', $msgparams);
            tm_alert_send($msg);

            // Notify real manager.
            if (!empty($realmanager)) {
                $msg->userto = $realmanager;
                $msg->subject = get_string('tempmanagerassignmsgmgrsubject', 'totara_job', $msgparams);
                $msg->fullmessage = get_string('tempmanagerassignmsgmgr', 'totara_job', $msgparams);
                $msg->fullmessagehtml = get_string('tempmanagerassignmsgmgr', 'totara_job', $msgparams);
                $msg->roleid = $CFG->managerroleid;
                tm_alert_send($msg);
            }

            // Notify temp manager.
            $msg->userto = $newtempmanager;
            $msg->subject = get_string('tempmanagerassignmsgtmpmgrsubject', 'totara_job', $msgparams);
            $msg->fullmessage = get_string('tempmanagerassignmsgtmpmgr', 'totara_job', $msgparams);
            $msg->fullmessagehtml = get_string('tempmanagerassignmsgtmpmgr', 'totara_job', $msgparams);
            $msg->roleid = $CFG->managerroleid;
            tm_alert_send($msg);
        }
    }

    /**
     * Remove the job_assignment from the database. The object is destroyed as well.
     *
     * @param job_assignment $jobassignment the job_assignment to delete
     */
    public static function delete(job_assignment &$jobassignment) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        try {
            // Remove the user from job assignments where they are manager or temp manager.
            $staffjas = self::get_staff($jobassignment->userid, $jobassignment->id);
            foreach ($staffjas as $staffja) {
                if ($staffja->managerjaid == $jobassignment->id) {
                    $staffja->update_internal(array('managerjaid' => null));
                } else if ($staffja->tempmanagerjaid == $jobassignment->id) {
                    $staffja->update_internal(array('tempmanagerjaid' => null, 'tempmanagerexpirydate' => null));
                }
            }

            // Delete the record.
            $DB->delete_records('job_assignment', array('id' => $jobassignment->id));

            if ($jobassignment->managerjaid) {
                // Now that job record is deleted, we can accurately update the role assignments for this user's staff.
                $jobassignment->update_manager_role_assignments($jobassignment->managerjaid, null);
            }

            if ($jobassignment->tempmanagerjaid) {
                // Now that job record is deleted, we can accurately update the role assignments for this user's temporary staff.
                $jobassignment->update_manager_role_assignments($jobassignment->tempmanagerjaid, null);
            }

            // Fix the sort order of the other job assignments.
            $followers = $DB->get_records_select(
                'job_assignment',
                'sortorder > :sortorder AND userid = :userid',
                array(
                    'sortorder' => $jobassignment->sortorder,
                    'userid' => $jobassignment->userid
                ),
                'sortorder ASC'
            );
            foreach ($followers as $followrec) {
                $follower = new job_assignment($followrec);
                $follower->update_internal(array('sortorder' => ($followrec->sortorder - 1)));
            }

            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
        }

        //\totara_job\event\job_assignment_deleted::create_from_instance($this)->trigger();

        // Lose the object, so that it can't be used again.
        $jobassignment = null;
    }

    /**
     * Get a job_assignment for a user using a idnumber.
     *
     * @param int $userid
     * @param string $idnumber
     * @param bool $mustexist Default true causes a db exception, if false then null is returned when there is no record
     * @return null|job_assignment
     */
    public static function get_with_idnumber($userid, $idnumber, $mustexist = true) {
        global $DB;

        $strictness = $mustexist ? MUST_EXIST : IGNORE_MISSING;

        $record = $DB->get_record('job_assignment', array('userid' => $userid, 'idnumber' => $idnumber), '*', $strictness);

        if ($record) {
            return new job_assignment($record);
        } else {
            return null;
        }
    }

    /**
     * Get a job_assignment from the db using the id.
     *
     * Note that, where applicable, the caller should check that the record returned matches the user they are working with.
     * E.g. "if ($jobassignment->userid != $workinguserid) { throw exception }"
     *
     * @param int $id
     * @param bool $mustexist Default true causes a db exception, if false then null is returned when there is no record
     * @return null|job_assignment
     */
    public static function get_with_id($id, $mustexist = true) {
        global $DB;

        $strictness = $mustexist ? MUST_EXIST : IGNORE_MISSING;

        $record = $DB->get_record('job_assignment', array('id' => $id), '*', $strictness);

        if ($record) {
            return new job_assignment($record);
        } else {
            return null;
        }
    }

    /**
     * Get all job assignments for the given user.
     *
     * Results are sorted and indexed by sortorder.
     *
     * @param int $userid
     * @param bool $managerreqd If true then only job assignments which have a manager or temp manager will be returned
     * @return job_assignment[] indexed by sortorder
     */
    public static function get_all($userid, $managerreqd = false) {
        global $CFG, $DB;

        $sql = "SELECT * FROM {job_assignment} WHERE userid = :userid";
        $params = array('userid' => $userid);

        if ($managerreqd) {
            if (!empty($CFG->enabletempmanagers)) {
                $sql .= " AND (managerjaid IS NOT NULL OR
                               (tempmanagerjaid IS NOT NULL AND tempmanagerexpirydate > :now))";
                $params['now'] = time();
            } else {
                $sql .= " AND managerjaid IS NOT NULL";
            }
        }

        $sql .= ' ORDER BY sortorder ASC';

        $records = $DB->get_records_sql($sql, $params);

        $jobassignments = array();
        foreach ($records as $record) {
            $jobassignments[$record->sortorder] = new job_assignment($record);
        }
        // Just to be doubly sure!
        ksort($jobassignments);

        return $jobassignments;
    }

    /**
     * Get all job assignments where the given field has the given value.
     *
     * The following fields are valid: 'appraiserid', 'positionid', 'organisationid'.
     *
     * @param string $field
     * @param mixed $value
     * @return job_assignment[] indexed by job assignment id
     */
    public static function get_all_by_criteria($field, $value) {
        global $DB;

        if (!in_array($field, array('fullname', 'shortname', 'positionid', 'organisationid', 'appraiserid'))) {
            throw new exception("Invalid field specified in job_assignment::get_all_by_criteria");
        }

        $sql = "SELECT * FROM {job_assignment} WHERE {$field} = :value ORDER BY id";
        $params = array('value' => $value);

        $records = $DB->get_records_sql($sql, $params);

        $jobassignments = array();
        foreach ($records as $record) {
            $jobassignments[$record->id] = new job_assignment($record);
        }

        return $jobassignments;
    }

    /**
     * Updates to null all job assignments which have the matching value in the specified field.
     *
     * Use this function when a position, organisation or appraiser is deleted.
     *
     * @param string $field
     * @param mixed $matchingvalue
     */
    public static function update_to_empty_by_criteria($field, $matchingvalue) {
        global $DB, $USER;

        if (!in_array($field, array('positionid', 'organisationid', 'appraiserid'))) {
            throw new exception("Invalid field specified in job_assignment::update_to_empty_by_criteria");
        }

        // Save a list of the record ids and old values we are about to update.
        $fields = 'id, userid, managerjaid, managerjapath, positionid, organisationid';
        $updatedrecords = $DB->get_records('job_assignment', array($field => $matchingvalue), '', $fields);

        if ($field === 'position') {
            $positionassignmentdatesql = "positionassignmentdate = :now2,";
        } else {
            $positionassignmentdatesql = "";
        }

        // Do the update.
        $now = time();
        $sql = "UPDATE {job_assignment}
                   SET {$field} = NULL,
                       {$positionassignmentdatesql}
                       timemodified = :now1,
                       usermodified = :usermodified
                 WHERE {$field} = :matchingvalue";
        $DB->execute($sql, array(
            'matchingvalue' => $matchingvalue,
            'now1' => $now,
            'now2' => $now,
            'usermodified' => $USER->id
        ));

        // Trigger events individually. Unless we decide to exclude events when doing bulk updates, in which case don't do this.
        foreach ($updatedrecords as $record) {
            $event = job_assignment_updated::create(
                array(
                    'objectid' => $record->id,
                    'context' => \context_system::instance(),
                    'relateduserid' => $record->userid,
                    'other' => array(
                        'oldmanagerjaid' => $record->managerjaid,
                        'oldmanagerjapath' => $record->managerjapath,
                        'oldpositionid' => $record->positionid,
                        'oldorganisationid' => $record->organisationid,
                    ),
                )
            );
            $event->trigger();
        }
    }

    /**
     * Get the first job_assignment for a user.
     *
     * @param int $userid
     * @param bool $mustexist Default true causes a db exception, if false then null is returned when there is no record
     * @return null|job_assignment
     */
    public static function get_first($userid, $mustexist = true) {
        global $DB;

        $params = array('userid' => $userid);
        $records = $DB->get_records('job_assignment', $params, 'sortorder ASC', '*', 0, 1);

        if (empty($records)) {
            if ($mustexist) {
                throw new \dml_missing_record_exception('job_assignment', '', $params);
            } else {
                return null;
            }
        } else {
            $record = reset($records);
            return new job_assignment($record);
        }
    }

    /**
     * Get all job assignments that are managed by the given user.
     *
     * If a staff has more than one job assignment relating to the manager, they will all be returned (may not be unique users).
     *
     * Note that mismatch between $managerid and $managerjaid will not be detected by this function and will cause empty results.
     *
     * @param int $managerid
     * @param int $managerjaid if specified, only staff related to this job asssignment
     * @param bool $includetempstaff if true (default) include staff where the manager is a temporary manager
     * @return job_assignment[] indexed by job assignment id
     */
    public static function get_staff($managerid, $managerjaid = null, $includetempstaff = true) {
        // When we want a function to get staff including indirect, add param $includeindirect here and
        // create function get_direct_and_indirect_staff. Do the same with get_staff_userids().
        return self::get_direct_staff($managerid, $managerjaid, $includetempstaff);
    }

    /**
     * Get all job assignments that are directly managed by the given user.
     *
     * If a staff has more than one job assignment relating to the manager, they will all be returned (may not be unique users).
     *
     * Note that mismatch between $managerid and $managerjaid will not be detected by this function and will cause empty results.
     *
     * @param int $managerid
     * @param int $managerjaid if specified, only staff related to this job asssignment
     * @param bool $includetempstaff if true (default) include staff where the manager is a temporary manager
     * @return job_assignment[] indexed by job assignment id
     */
    private static function get_direct_staff($managerid, $managerjaid = null, $includetempstaff = true) {
        global $CFG, $DB;

        $sql = "SELECT staffja.*
                  FROM {job_assignment} managerja
                  JOIN {job_assignment} staffja ON staffja.managerjaid = managerja.id
                 WHERE managerja.userid = :managerid";
        $params = array('managerid' => $managerid);

        if ($managerjaid) {
            $sql .= " AND managerja.id = :managerjaid";
            $params['managerjaid'] = $managerjaid;
        }

        if ($includetempstaff && !empty($CFG->enabletempmanagers)) {
            // Note that UNION will remove duplicate job assignments, but there could be duplicate users.
            $sql .= " UNION
                     SELECT staffja.*
                       FROM {job_assignment} tempmanagerja
                       JOIN {job_assignment} staffja ON staffja.tempmanagerjaid = tempmanagerja.id
                      WHERE tempmanagerja.userid = :tempmanagerid
                        AND staffja.tempmanagerexpirydate > :now";
            $params['tempmanagerid'] = $managerid;
            $params['now'] = time();

            if ($managerjaid) {
                $sql .= " AND tempmanagerja.id = :tempmanagerjaid";
                $params['tempmanagerjaid'] = $managerjaid;
            }
        }

        $records = $DB->get_records_sql($sql, $params);

        $jobassignments = array();
        foreach ($records as $record) {
            $jobassignments[$record->id] = new job_assignment($record);
        }

        return $jobassignments;
    }

    /**
     * Get all staff user ids that are managed by the given manager.
     *
     * Unlike get_staff, the list of userids will be unique. So some users could have more than one matching job assignment.
     *
     * Note that mismatch between $managerid and $managerjaid will not be detected by this function and will cause empty results.
     *
     * @param int $managerid
     * @param int $managerjaid if specified, only staff related to this job asssignment
     * @param bool $includetempstaff if true (default) include staff where the manager is a temporary manager
     * @return array of ints
     */
    public static function get_staff_userids($managerid, $managerjaid = null, $includetempstaff = true) {
        // When we want a function to get staff including indirect, add param $includeindirect here and
        // create function get_direct_and_indirect_staff_userids. Do the same with get_staff().
        return self::get_direct_staff_userids($managerid, $managerjaid, $includetempstaff);
    }

    /**
     * Get all staff user ids that are directly managed by the given manager.
     *
     * Unlike get_staff, the list of userids will be unique. So some users could have more than one matching job assignment.
     *
     * Note that mismatch between $managerid and $managerjaid will not be detected by this function and will cause empty results.
     *
     * @param int $managerid
     * @param int $managerjaid if specified, only staff related to this job asssignment
     * @param bool $includetempstaff if true (default) include staff where the manager is a temporary manager
     * @return array of ints
     */
    public static function get_direct_staff_userids($managerid, $managerjaid = null, $includetempstaff = true) {
        global $CFG, $DB;

        $sql = "SELECT staffja.userid
                  FROM {job_assignment} managerja
                  JOIN {job_assignment} staffja ON staffja.managerjaid = managerja.id
                 WHERE managerja.userid = :managerid";
        $params = array('managerid' => $managerid);

        if ($managerjaid) {
            $sql .= " AND managerja.id = :managerjaid";
            $params['managerjaid'] = $managerjaid;
        }

        if ($includetempstaff && !empty($CFG->enabletempmanagers)) {
            // Note that UNION will remove duplicate userids.
            $sql .= " UNION
                     SELECT staffja.userid
                       FROM {job_assignment} tempmanagerja
                       JOIN {job_assignment} staffja ON staffja.tempmanagerjaid = tempmanagerja.id
                      WHERE tempmanagerja.userid = :tempmanagerid
                        AND staffja.tempmanagerexpirydate > :now";
            $params['tempmanagerid'] = $managerid;
            $params['now'] = time();

            if ($managerjaid) {
                $sql .= " AND tempmanagerja.id = :tempmanagerjaid";
                $params['tempmanagerjaid'] = $managerjaid;
            }
        }

        return $DB->get_fieldset_sql($sql, $params);
    }

    /**
     * Get all manager userids for a given staff memeber.
     *
     * @param int $userid
     * @param int $userjaid if specified, only managers related to this job asssignment are checked
     * @param bool $includetemp
     * @return array()
     */
    public static function get_all_manager_userids($userid, $userjaid = null, $includetemp = true) {
        global $DB;

        $sql = "SELECT uja.id, mja.userid AS manager, tja.userid AS tempmanager
                  FROM {job_assignment} uja
             LEFT JOIN {job_assignment} mja
                    ON uja.managerjaid = mja.id
             LEFT JOIN {job_assignment} tja
                    ON uja.tempmanagerjaid = tja.id
                   AND uja.tempmanagerexpirydate > :now
                 WHERE uja.userid = :userid";
        $params = array('now' => time(), 'userid' => $userid);

        if (!empty($userjaid)) {
            $sql .= " AND uja.id = :userjaid";
            $params['userjaid'] = $userjaid;
        }

        // We need consistent results for tests.
        $sql .= "ORDER BY uja.id ASC";

        $records = $DB->get_records_sql($sql, $params);

        $managers = array();
        foreach ($records as $record) {
            if ($includetemp && !empty($record->tempmanager)) {
                $managers[$record->tempmanager] = $record->tempmanager;
            }

            if (!empty($record->manager)) {
                $managers[$record->manager] = $record->manager;
            }
        }

        return $managers;
    }

    /**
     * Check if a user is manager of another user.
     *
     * @param int $managerid user ID of a potential manager to check
     * @param int $staffid user ID of the staff
     * @param int $staffjaid if specified, only managers related to this job asssignment are checked
     * @param bool $includetempstaff if true (default) include job assignments where the manager is a temporary manager
     * @return bool true if user $staffid is managed by user $managerid
     **/
    public static function is_managing($managerid, $staffid, $staffjaid = null, $includetempstaff = true) {
        global $CFG, $DB;

        // Manager.
        $sql = "SELECT 1
                  FROM {job_assignment} managerja
                  JOIN {job_assignment} staffja ON staffja.managerjaid = managerja.id
                 WHERE staffja.userid = :staffid
                   AND managerja.userid = :managerid";
        $params = array('staffid' => $staffid, 'managerid' => $managerid);

        if (!empty($staffjaid)) {
            $sql .= " AND staffja.id = :staffjaid";
            $params['staffjaid'] = $staffjaid;
        }

        if ($DB->record_exists_sql($sql, $params)) {
            return true;
        }

        // Temporary manager.
        if ($includetempstaff && !empty($CFG->enabletempmanagers)) {
            $sql = "SELECT 1
                      FROM {job_assignment} tempmanagerja
                      JOIN {job_assignment} staffja ON staffja.tempmanagerjaid = tempmanagerja.id
                     WHERE staffja.userid = :staffid
                       AND tempmanagerja.userid = :tempmanagerja
                       AND staffja.tempmanagerexpirydate > :now";
            $params = array('staffid' => $staffid, 'tempmanagerja' => $managerid, 'now' => time());

            if (!empty($staffjaid)) {
                $sql .= " AND staffja.id = :staffjaid";
                $params['staffjaid'] = $staffjaid;
            }

            if ($DB->record_exists_sql($sql, $params)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a user is the manager for any users.
     *
     * Note that mismatch between $managerid and $managerjaid will not be detected by this function and will cause empty results.
     *
     * @param int $managerid user ID of a potential manager to check
     * @param int $managerjaid if specified, only staff related to this job asssignment are checked
     * @param bool $includetempstaff if true (default) include job assignments where the manager is a temporary manager
     * @return bool true if user $managerid is the manager for any other users.
     **/
    public static function has_staff($managerid, $managerjaid = null, $includetempstaff = true) {
        global $CFG, $DB;

        // Manager.
        $sql = "SELECT 1
                  FROM {job_assignment} staffja
                  JOIN {job_assignment} managerja ON staffja.managerjaid = managerja.id
                 WHERE managerja.userid = :managerid";
        $params = array('managerid' => $managerid);

        if ($managerjaid) {
            $sql .= " AND managerja.id = :managerjaid";
            $params['managerjaid'] = $managerjaid;
        }

        if ($DB->record_exists_sql($sql,    $params)) {
            return true;
        }

        // Temporary manager.
        if ($includetempstaff && !empty($CFG->enabletempmanagers)) {
            $sql = "SELECT 1
                      FROM {job_assignment} staffja
                      JOIN {job_assignment} tempmanagerja ON staffja.tempmanagerjaid = tempmanagerja.id
                     WHERE tempmanagerja.userid = :tempmanagerid
                       AND staffja.tempmanagerexpirydate > :now";
            $params = array('tempmanagerid' => $managerid, 'now' => time());

            if ($managerjaid) {
                $sql .= " AND tempmanagerja.id = :tempmanagerjaid";
                $params['tempmanagerjaid'] = $managerjaid;
            }

            if ($DB->record_exists_sql($sql, $params)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a specified user has any manager(s).
     *
     * @param int $userid user ID of a user to check for managers.
     * @param int $userjaid if specified, only managers related to this job asssignment are checked
     * @param bool $includetempstaff if true (default) include job assignments where the manager is a temporary manager.
     * @return bool true if user $userid has a manager assigned in any of their job assignments.
     **/
    public static function has_manager($userid, $userjaid = null, $includetempstaff = true) {
        global $DB;

        $sql = "SELECT uja.id, mja.userid as manager, tja.userid as tempman
                  FROM {job_assignment} uja
             LEFT JOIN {job_assignment} mja
                    ON uja.managerjaid = mja.id
             LEFT JOIN {job_assignment} tja
                    ON uja.tempmanagerjaid = tja.id
                   AND uja.tempmanagerexpirydate > :now
                 WHERE uja.userid = :userid";
        $params = array('now' => time(), 'userid' => $userid);

        if (!empty($userjaid)) {
            $sql .= " AND uja.id = :userjaid";
            $params['userjaid'] = $userjaid;
        }

        $records = $DB->get_records_sql($sql, $params);
        foreach ($records as $record) {
            if (!empty($record->manager) || ($includetempstaff && !empty($record->tempman))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets number of staff users linked to a job assignment.
     *
     * @param  int $jobassignmentid
     * @return int Number of staff users linked to the job assignment
     */
    public static function get_count_managed_users($jobassignmentid) {
        global $DB;

        return $DB->count_records('job_assignment', array('managerjaid' => $jobassignmentid));
    }

    /**
     * Gets number of temp staff users linked to a job assignment.
     *
     * @param  int $jobassignmentid
     * @return int Number of temp staff users linked to the job assignment
     */
    public static function get_count_temp_managed_users($jobassignmentid) {
        global $DB;

        return $DB->count_records('job_assignment', array('tempmanagerjaid' => $jobassignmentid));
    }

    /**
     * Run by cron to automatically remove temporary managers when they expire.
     * Also removes temporary managers who are not currently managers when tempmanagerrestrictselection is turned on.
     * Also removes all temporary managers if enabletempmanagers is turned off.
     */
    public static function update_temporary_managers() {
        global $CFG, $DB;

        if (empty($CFG->enabletempmanagers)) {
            // Unassign all current temporary managers.

            $rs = $DB->get_recordset_select('job_assignment', 'tempmanagerjaid IS NOT NULL');

            if ($rs) {
                mtrace('Removing obsolete temporary managers...');
                foreach ($rs as $record) {
                    $jobassignment = new job_assignment($record);
                    $jobassignment->update_internal(array('tempmanagerjaid' => null, 'tempmanagerexpirydate' => null));
                }
                mtrace('Done removing obsolete temporary managers.');
            }

            $rs->close();

            return;
        }

        if (!empty($CFG->tempmanagerrestrictselection)) {
            // Ensure only users that are currently managers are assigned as temporary managers.
            // We need this check for scenarios where tempmanagerrestrictselection was previously disabled or
            // when a temporary manager has their last non-temporary staff member removed.

            $sql = "SELECT staffja.*
                      FROM {job_assignment} staffja
                      JOIN {job_assignment} tempmanagerja ON staffja.tempmanagerjaid = tempmanagerja.id
                      JOIN {job_assignment} alltempmanagersjas ON tempmanagerja.userid = alltempmanagersjas.userid
                 LEFT JOIN {job_assignment} allotherstaffja ON allotherstaffja.managerjaid = alltempmanagersjas.id
                     WHERE allotherstaffja.managerjaid IS NULL";
            $rs = $DB->get_recordset_sql($sql);

            if ($rs) {
                mtrace('Removing non-manager temporary managers...');
                foreach ($rs as $record) {
                    $jobassignment = new job_assignment($record);
                    $jobassignment->update_internal(array('tempmanagerjaid' => null, 'tempmanagerexpirydate' => null));
                }
                mtrace('Done removing non-manager temporary managers.');
            }

            $rs->close();
        }

        // Remove expired temporary managers.
        $rs = $DB->get_recordset_select('job_assignment', 'tempmanagerexpirydate < :now', array('now' => time()));

        if ($rs) {
            mtrace('Removing expired temporary managers...');
            foreach ($rs as $record) {
                $jobassignment = new job_assignment($record);
                $jobassignment->update_internal(array('tempmanagerjaid' => null, 'tempmanagerexpirydate' => null));
            }
            mtrace('Done removing expired temporary managers.');
        }

        $rs->close();
    }

    /**
     * Swaps the positions of two job_assignments.
     *
     * Note that they MUST belong to the same user - this will be checked!
     *
     * @param int $jobassignmentid1 id of first job_assignment to swap order
     * @param int $jobassignmentid2 id of second job_assignment to swap order
     */
    public static function swap_order($jobassignmentid1, $jobassignmentid2) {
        $jobassignment1 = self::get_with_id($jobassignmentid1);
        $jobassignment2 = self::get_with_id($jobassignmentid2);

        self::swap_order_internal($jobassignment1, $jobassignment2);
    }

    /**
     * Swaps the positions of two job_assignments.
     *
     * Note that they MUST belong to the same user - this will be checked!
     *
     * @param job_assignment $jobassignment1 first job_assignment to swap order
     * @param job_assignment $jobassignment2 second job_assignment to swap order
     */
    private static function swap_order_internal(job_assignment $jobassignment1, job_assignment $jobassignment2) {
        global $DB;

        if ($jobassignment1->userid != $jobassignment2->userid) {
            throw new exception('Cannot swap order of two job assignments belonging to different users.');
        }

        $sortorder1 = $jobassignment1->sortorder;
        $sortorder2 = $jobassignment2->sortorder;

        $transaction = $DB->start_delegated_transaction();
        try {
            // Only need to isolate the sortorder for job_assignment 1. Don't use update_internal because it triggers events.
            $record = new \stdClass;
            $record->id = $jobassignment1->id;
            $record->sortorder = -$jobassignment1->sortorder;
            $DB->update_record('job_assignment', $record);

            // Now swap them to the real values.
            $jobassignment2->update_internal(array('sortorder' => $sortorder1));
            $jobassignment1->update_internal(array('sortorder' => $sortorder2));

            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
        }
    }

    /**
     * Move this job_assignment up in the sort order.
     *
     * Note that only the database will be updated, so if the related job_assignments are already loaded in
     * memory then they will be invalid.
     */
    public static function move_up($jobassignmentid) {
        global $DB;

        $targetja = self::get_with_id($jobassignmentid);

        $otherjarecord = $DB->get_record('job_assignment',
            array('userid' => $targetja->userid, 'sortorder' => $targetja->sortorder - 1), '*', IGNORE_MISSING);

        if (empty($otherjarecord)) {
            throw new exception('Tried to move the first job assignment up.');
        }

        $otherja = new job_assignment($otherjarecord);

        self::swap_order_internal($targetja, $otherja);
    }

    /**
     * Move this job_assignment down in the sort order.
     *
     * Note that only the database will be updated, so if the related job_assignments are already loaded in
     * memory then they will be invalid.
     */
    public static function move_down($jobassignmentid) {
        global $DB;

        $targetja = self::get_with_id($jobassignmentid);

        $otherjarecord = $DB->get_record('job_assignment',
            array('userid' => $targetja->userid, 'sortorder' => $targetja->sortorder + 1), '*', IGNORE_MISSING);

        if (empty($otherjarecord)) {
            throw new exception('Tried to move the last job assignment down.');
        }

        $otherja = new job_assignment($otherjarecord);

        self::swap_order_internal($targetja, $otherja);
    }

    /**
     * Resorts all of users users job assignments.
     *
     * @throws \moodle_exception If the given list of JobAssignmentID's is not complete.
     * @param int $userid
     * @param array $jobassignmentids An array of jobassignids in the order they should be sorted to.
     * @return job_assignment[]
     */
    public static function resort_all($userid, $jobassignmentids) {
        global $DB;

        // Create an array of job assignments, where the key is the jaid, and the value is the sort order.
        $map = array();
        $i = 1;
        foreach ($jobassignmentids as $jobassignment) {
            $map[$jobassignment] = $i;
            $i++;
        }
        $currentassignments = self::get_all($userid);

        if (count($map) !== count($currentassignments)) {
            throw new \moodle_exception('error', 'error', '', null, 'Incomplete job list in submit data.');
        }

        // First up check we have all of the users job assignments here.
        foreach ($currentassignments as $jobassignment) {
            if (!array_key_exists($jobassignment->id, $map)) {
                throw new \moodle_exception('error', 'error', '', null, 'Incorrect job list in submit data.');
            }
        }

        // We do this in a transaction - its all or nothing.
        // Make sure the sortorder is unique.
        // To do this we set it to its negative self. Easy! but inefficient!
        $transaction = $DB->start_delegated_transaction();
        foreach ($currentassignments as $jobassignment) {
            if ($jobassignment->sortorder != $map[$jobassignment->id]) {
                // It is going to change, we need to isolate its sortorder number.
                // We update the database directly as update_internal does heaps of stuff, including events.
                $record = new \stdClass;
                $record->id = $jobassignment->id;
                $record->sortorder = -$map[$jobassignment->id];
                $DB->update_record('job_assignment', $record);
            }
        }

        // Now we can set it to its proper sortorder value.
        // We iterate again, exactly as we did before hand.
        foreach ($currentassignments as $jobassignment) {
            if ($jobassignment->sortorder != $map[$jobassignment->id]) {
                $jobassignment->update_internal(['sortorder' => $map[$jobassignment->id]]);
            }
            $map[$jobassignment->id] = $jobassignment;
        }
        $transaction->allow_commit();

        return $map;
    }

    /**
     * Exports this job_assignment instance as a context object suitable for use in a template.
     *
     * The main difference between this function and get_data is that this function returns only
     * data that is for display, and has formatted those parts that need formatting.
     *
     * @param \renderer_base $output
     * @param integer $courseid     The optional course id param for the view url.
     * @return \stdClass
     */
    public function export_for_template(\renderer_base $output, $courseid = null) {

        // Lets re-use the get_data method. Its a great starting point.
        $data = new \stdClass();

        $data->id                     = $this->id;
        $data->userid                 = $this->userid;
        $data->fullname               = format_string($this->__get('fullname'));
        $data->shortname              = format_string($this->shortname);
        $data->idnumber               = $this->idnumber;
        $data->description            = $this->__get('description');
        $data->usermodified           = $this->usermodified;
        $data->positionid             = $this->positionid;
        $data->organisationid         = $this->organisationid;
        $data->managerid              = $this->managerid;
        $data->managerjaid            = $this->managerjaid;
        $data->managerjapath          = $this->managerjapath;
        $data->tempmanagerid          = $this->tempmanagerid;
        $data->tempmanagerjaid        = $this->tempmanagerjaid;
        $data->appraiserid            = $this->appraiserid;
        $data->sortorder              = $this->sortorder;

        $urlparams = array('jobassignmentid' => $this->id);
        if (!empty($courseid)) {
            $urlparams['course'] = $courseid;
        }
        $editurl = new \moodle_url('/totara/job/jobassignment.php', $urlparams);
        $data->editurl = $editurl->out(false);

        $dates = ['startdate', 'enddate', 'positionassignmentdate', 'tempmanagerexpirydate', 'timecreated', 'timemodified'];
        foreach ($dates as $date) {
            if (!empty($this->$date)) {
                $data->$date = userdate($this->$date);
            } else {
                $data->$date = '';
            }
        }

        // Return our context data object.
        return $data;
    }
}
