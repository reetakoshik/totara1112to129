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
 * @author Ciaran Irvine <ciaran.irvine@totaralms.com>
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package totara
 * @subpackage appraisal
 */

global $CFG;
require_once($CFG->dirroot.'/totara/core/lib/assign/lib.php');
require_once($CFG->dirroot.'/totara/appraisal/lib.php');
require_once($CFG->dirroot.'/totara/job/classes/job_assignment.php');

use totara_job\job_assignment;


class totara_assign_appraisal extends totara_assign_core {
    protected static $module = 'appraisal';

    private $job_assignment_id_cache = [];

    public function store_user_assignments($newusers = null, $processor = null) {
        // Define a processor function to format the data for appraisals.
        $processor = function($record, $modulekey, $moduleinstanceid) {
            $todb = new stdClass();
            $todb->$modulekey = $moduleinstanceid;
            $todb->userid = $record->id;
            $todb->status = appraisal::STATUS_ACTIVE;
            return $todb;
        };

        parent::store_user_assignments($newusers, $processor);
    }

    /**
     * Automatically link job assignments for all assigned appraisees.
     *
     * @param array|null $appraisee_ids  If passed in, only appraisees for these ids
     * are handled. If any id does not belong to a current appraisee, it is ignored.
     * @param bool $notifymanager if true sends a notification to the appraisee's
     *        manager about the appraisal.
     */
    public function store_job_assignments(array $appraisee_ids = null, $notifymanager=true) {
        /** @var appraisal $appraisal */
        $appraisal = $this->moduleinstance;

        if (!appraisal::can_auto_link_job_assignments()) {
            return;
        }

        $appraisee_ids = $appraisee_ids ?? $this->get_current_appraisee_ids();
        foreach ($appraisee_ids as $appraisee_id) {
            $assignment = appraisal_user_assignment::get_user($appraisal->id, $appraisee_id);
            // Ignore if user is not assigned to the appraisal.
            if (!empty($assignment->userid)) {
                // Call with param=false, so no job will be linked if user has multiple jobs.
                $assignment->with_auto_job_assignment(false, $notifymanager);
            }
        }
    }

    /**
     * Create appraisal role assignment records for all appraisees associated
     * with the current appraisal.
     *
     * @return array $removedroleassignments list of removed role_assignments
     */
    public function store_role_assignments() {
        $appraiseeids = $this->get_current_appraisee_ids();

        global $DB;
        $transaction = $DB->start_delegated_transaction();

        try {
            $allassignments = array_reduce(
                $appraiseeids,

                function (array $accumulated, $id) {
                    return array_merge(
                        $accumulated, $this->role_assignments_for_appraisee($id)
                    );
                },

                []
            );

            $removedroleassignments = $this->update_role_assignments($allassignments);
            $transaction->allow_commit();
            return $removedroleassignments;
        }
        catch (Exception $e) {
            $transaction->rollback($e);
            return array();
        }
    }

    /**
     * Returns the user ids for the current appraisees.
     *
     * @return array list of integer appraisee user ids.
     */
    public function get_current_appraisee_ids() {
        $appraiseeids = [];
        $appraisees = $this->get_current_users();
        foreach ($appraisees as $appraisee) {
            $appraiseeids[] = $appraisee->id;
        }
        $appraisees->close();

        return $appraiseeids;
    }

    /**
     * Create appraisal role assignment records for the appraisal for the given
     * appraisee.
     *
     * @param int $user appraisee id.
     */
    public function store_role_assignment_for_appraisee($user) {
        global $DB;
        $transaction = $DB->start_delegated_transaction();

        try {
            $assignments = $this->role_assignments_for_appraisee($user);
            $this->update_role_assignments($assignments);

            $transaction->allow_commit();
        }
        catch (Exception $e) {
            $transaction->rollback($e);
        }
    }

    /**
     * Determines the appraisal role assignments for the given appraisee. This
     * updates user assignments in the database with job assignment timestamps
     * but leaves transaction management to the caller.
     *
     * @param int $appraiseeid appraisee id.
     *
     * @return array a list comprising a single \appraisal_assignments role
     *         object assignment or an empty list if there is nothing to update.
     */
    private function role_assignments_for_appraisee($appraiseeid) {
        $appraisal = $this->moduleinstance;
        $assignment = appraisal_user_assignment::get_user(
            $appraisal->id, $appraiseeid
        );
        $roleassignments = appraisal_assignments::from(
            $assignment->id
        )->with_user(
            appraisal::ROLE_LEARNER, $appraiseeid
        );

        $jobassignmentid = $assignment->jobassignmentid;
        if (empty($jobassignmentid)) {
            // Even if no job assignments have been linked to the appraisal, the
            // fact that there is an appraisee means there must be at least one
            // entry for the role assignments. Otherwise the appraisee will not
            // be able to see the appraisal let alone select a job assignment!
            return [$roleassignments];
        }

        $job = job_assignment::get_with_id($jobassignmentid, false);
        if (empty($job)) {
            // This situation is possible if the job assignment was deleted in
            // interim and things were not cleaned up.
            $assignment->remove_job_assignment();
            return [$roleassignments];
        }
        else {
            $joblastmodified = $job->timemodified;
            $assignmentjobtimestamp = $assignment->jobassignmentlastmodified;
            if (!empty($assignmentjobtimestamp)
                && $assignmentjobtimestamp === $joblastmodified
            ) {
                // Implies the parent job assignment has not changed => manager,
                // etc have not changed => no roles need to reassigned.
                return [];
            }

            if (empty($assignmentjobtimestamp)
                || $assignmentjobtimestamp !== $joblastmodified
            ) {
                $assignment->with_job_assignment_record_time($joblastmodified);
            }
        }

        $finalassignments = array_reduce(
            $appraisal->get_roles_involved(),

            function (\appraisal_assignments $assignments, $role) use ($job) {
                return $assignments->with_role_from_job($role, $job);
            },

            $roleassignments
        );

        return [$finalassignments];
    }

    /**
     * Updates the appraisal role assignment records. Note this leaves database
     * transaction management to the caller.
     *
     * @param array $allassignments list of \appraisal_assignments.
     * @return array $removedroleassignmentss list of removed role_assignments
     */
    private function update_role_assignments(array $allassignments) {
        global $DB;

        if (empty($allassignments)) {
            return array();
        }

        $auditlogs = array_reduce(
            $allassignments,

            function (array $accumulated, \appraisal_assignments $assigned) {
                return array_merge($accumulated, $this->audit_logs($assigned));
            },

            []
        );

        $toupdate = [];
        $toinsert = [];
        $removedroleassignments = [];
        foreach ($allassignments as $assignments) {
            $assignid = $assignments->id();
            $current = \appraisal_assignments::from_db($assignid);
            list($ins, $upd, $rem) = $this->update_roles($assignments, $current);

            $toinsert = array_merge($toinsert, $ins);
            $toupdate = array_merge($toupdate, $upd);
            $removedroleassignments = array_merge($removedroleassignments, $rem);
        }

        $table = 'appraisal_role_assignment';
        foreach ($toupdate as $obj) {
            $DB->update_record($table, $obj, true);
        }

        $DB->insert_records_via_batch($table, $toinsert);
        $DB->insert_records_via_batch('appraisal_role_changes', $auditlogs);

        return $removedroleassignments;
    }

    /**
     * Creates/updates role assignments for an appraisee.
     *
     * @param \appraisal_assignments $newassignments new assignments.
     * @param \appraisal_assignments $existingassignments current assignments.
     */
    private function update_roles(
        \appraisal_assignments $newassignments,
        \appraisal_assignments $existingassignments
    ) {
        $toinsert = [];
        $toupdate = [];
        $toremove = [];
        $appraisalroles = [
            appraisal::ROLE_LEARNER,
            appraisal::ROLE_MANAGER,
            appraisal::ROLE_TEAM_LEAD,
            appraisal::ROLE_APPRAISER
        ];

        foreach ($appraisalroles as $role) {
            $newuser = $newassignments->user_with_role($role);
            $existinguser = $existingassignments->user_with_role($role);

            if (empty($existinguser->id)) {
                // This means there is no record in the database but one needs
                // to inserted even if the userid is 0 (ie a userid for the role
                // had been removed).
                $toinsert[] = $newuser;
            }
            else {
                if ($newuser->userid != $existinguser->userid) {
                    if ($newuser->userid == 0) {
                        $toremove[] = $existinguser;
                    }

                    // This means there is an existing database record, but it has
                    // to be updated with the new details.
                    $newuser->id = $existinguser->id;
                    $toupdate[] = $newuser;
                }
            }
        }

        return [$toinsert, $toupdate, $toremove];
    }

    /**
     * Returns the audit trails to be created for a given set of appraisal role
     * assignments.
     *
     * @param \appraisal_assignments $assignments the new appraisal assignments.
     *
     * @return array a list of \stdClass objects whose fields mirror the column
     *         names in the appraisal_role_changes table.
     */
    private function audit_logs(
        \appraisal_assignments $assignments
    ) {
        global $DB;

        $sql = '
            SELECT
                   id                        AS id,
                   appraisaluserassignmentid AS assignid,
                   userid                    AS originaluser,
                   appraisalrole             AS role
              FROM {appraisal_role_assignment}
             WHERE appraisaluserassignmentid = :appraiseeassignmentid
               AND (
                       (appraisalrole = :learnerrole   AND userid != :learner)
                    OR (appraisalrole = :managerrole   AND userid != :manager)
                    OR (appraisalrole = :leadrole      AND userid != :lead)
                    OR (appraisalrole = :appraiserrole AND userid != :appraiser)
               )
        ';

        $placeholders = [
            appraisal::ROLE_LEARNER => 'learner',
            appraisal::ROLE_MANAGER => 'manager',
            appraisal::ROLE_TEAM_LEAD => 'lead',
            appraisal::ROLE_APPRAISER => 'appraiser'
        ];

        $users = $assignments->users();
        $params = ['appraiseeassignmentid' => $assignments->id()];
        foreach ($users as $role => $user) {
            $placeholder = $placeholders[$role];
            $params[$placeholder] = $user->userid;
            $params[$placeholder . 'role'] = $role;
        }

        return array_map(
            function (\stdClass $existing) use ($users) {
                $role = $existing->role;

                $audit = new \stdClass();
                $audit->userassignmentid = $existing->assignid;
                $audit->originaluserid = $existing->originaluser;
                $audit->newuserid = $users[$role]->userid;
                $audit->role = $role;
                $audit->timecreated = time();

                return $audit;
            },

            $DB->get_records_sql($sql, $params)
        );
    }

    /**
     * Determines which role assignments *will be* missing for an appraisal. It
     * is just a comparison of current job assignment roles against *expected*
     * appraisal roles. It does not compare against the *actual* assigned roles;
     * that may NOT have been updated yet because it requires a cron run.
     *
     * @return \stdClass comprising the following fields:
     *         - int $appraiseecount: no of appraisees for this appraisal.
     *         - array $roles: mapping of appraisee ids to missing roles or an
     *           empty mapping if nothing is missing.
     */
    public function missing_role_assignments() {
        $appraiseeids = $this->get_current_appraisee_ids();

        $allmissing = new \stdClass();
        $allmissing->appraiseecount = count($appraiseeids);
        $allmissing->roles = [];
        $allmissing->nojobselected = [];

        if (empty($appraiseeids)) {
            return $allmissing;
        }

        return array_reduce(
            $appraiseeids,

            function (\stdClass $missing, $appraisee) {
                $roles = $this->missing_role_assignments_for_appraisee(
                    $appraisee
                );

                if (!empty($roles)) {
                    $missing->roles[$appraisee] = $roles;

                    if (!$this->get_job_assignment_id($appraisee)) {
                        $missing->nojobselected[] = $appraisee;
                    }
                }

                return $missing;
            },

            $allmissing
        );
    }

    /**
     * Determines which role assignments are missing for the given appraisee for
     * the current appraisal. Note this is just a check of the associated job
     * roles against the expected appraisal roles.
     *
     * @param int $appraiseeid appraisee id.
     *
     * @return array list of the missing roles (an appraisal::ROLE_XYZ constant)
     *         or an empty array if nothing is missing.
     */
    private function missing_role_assignments_for_appraisee($appraiseeid) {
        $appraisal = $this->moduleinstance;
        $requiredroles = $appraisal->get_roles_involved();

        $jobassignmentid = $this->get_job_assignment_id($appraiseeid);
        $job = empty($jobassignmentid)
               ? null
               : job_assignment::get_with_id($jobassignmentid, false);
        if (empty($job)) {
            // Even if no job assignments have been linked to the appraisal, the
            // fact that there is an appraisee means there is already a learner
            // role assignment. So the learner role does not need to be listed.
            return array_filter(
                $requiredroles,

                function ($role) {
                    return $role != appraisal::ROLE_LEARNER;
                }
            );
        }

        return appraisal_assignments::missing_appraisal_roles_from_job(
            $requiredroles, $job
        );
    }

    /**
     * @param int $appraiseeid
     * @return int|null
     */
    private function get_job_assignment_id($appraiseeid) {
        if (!isset($this->job_assignment_id_cache[$appraiseeid])) {
            $appraisal = $this->moduleinstance;
            $assignment = appraisal_user_assignment::get_user(
                $appraisal->id, $appraiseeid
            );
            $this->job_assignment_id_cache[$appraiseeid] = $assignment->jobassignmentid;
        }
        return $this->job_assignment_id_cache[$appraiseeid];
    }

    /**
     * Determines which *actual* role assignments have changed for the current
     * appraisal. Note if the cron job does not run, there could be *no* role
     * changes.
     *
     * @return array mapping of appraisee ids to changes or an empty mapping if
     *         nothing is missing. Each change is a \stdClass with these fields:
     *         - role: changed role.
     *         - original: user originally assigned to the role.
     *         - current: user currently assigned to the role.
     */
    public function changed_role_assignments() {
        $changed = [];

        foreach ($this->get_current_appraisee_ids() as $appraisee) {
            $roles = $this->changed_role_assignments_for_appraisee($appraisee);
            if (!empty($roles)) {
                $changed[$appraisee] = $roles;
            }
        }

        return $changed;
    }

    /**
     * Determines which role assignments have changed for a given appraisee for
     * the current appraisal.
     *
     * @param int $appraiseeid appraisee id.
     *
     * @return array a list of \stdClass objects with these fields:
     *         - originaluserid: user originally assigned to the role.
     *         - newuserid: user currently assigned to the role.
     *         - role: changed role.
     */
    private function changed_role_assignments_for_appraisee($appraiseeid) {
        global $DB;

        $sql = '
            SELECT
                   t.originaluserid,
                   t.newuserid,
                   t.role
              FROM {appraisal_role_changes} t
             WHERE userassignmentid = :appraiseeassignmentid
               AND t.timecreated = (
                   SELECT MAX(ti.timecreated)
                     FROM {appraisal_role_changes} ti
                    WHERE ti.role = t.role
               )
        ';

        $appraisal = $this->moduleinstance;
        $assignment = appraisal_user_assignment::get_user(
            $appraisal->id, $appraiseeid
        );
        $params = ['appraiseeassignmentid' => $assignment->id];

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Delete all of this appraisal's assignments
     *
     * @access public
     * @return void
     */
    public function delete_user_assignments() {
        $this->delete_role_assignments();

        parent::delete_user_assignments();
    }

    /**
     * Delete this appraisal's role assignments
     *
     * @access public
     * @return void
     */
    private function delete_role_assignments() {
        global $DB;

        $sqlstagedata =
            "DELETE FROM {appraisal_stage_data}
              WHERE appraisalroleassignmentid IN
                    (SELECT ara.id
                       FROM {appraisal_role_assignment} ara
                       JOIN {appraisal_user_assignment} aua
                         ON ara.appraisaluserassignmentid = aua.id
                      WHERE aua.appraisalid = {$this->moduleinstanceid})";
        $DB->execute($sqlstagedata);

        $sqlroleassignment =
            "DELETE FROM {appraisal_role_assignment}
              WHERE appraisaluserassignmentid IN
                    (SELECT id
                       FROM {appraisal_user_assignment}
                      WHERE appraisalid = {$this->moduleinstanceid})";
        $DB->execute($sqlroleassignment);
    }

    /**
     * Determine if the appraisal can have assignments added or removed.
     *
     * @return bool
     */
    public function is_locked() {
        return appraisal::is_closed($this->moduleinstanceid);;
    }

    /**
     * Determines if assigned users have been stored in the user_assignement table, via store_user_assignments.
     *
     * @return bool whether or not users have been stored in the user_assignments table.
     */
    public function assignments_are_stored() {
        return ($this->moduleinstance->status != appraisal::STATUS_DRAFT);
    }

    /**
     * Over ride in module code to add specific module related search queries.
     *
     * @param  $useralias       string  The alias of the user table.
     * @param  $joinalias       string  The alias of the joined table.
     * @param  $liveassignments boolean Flags which $joinalias table is being used.
     * @return string  The additional where statement.
     */
    public function get_user_extra_search_where_sql($useralias, $joinalias, $liveassignments) {
        $sql = "";
        $params = array();

        if ($liveassignments) {
            // Extra search where clause on the user_assignments table.
            $sql = "{$joinalias}.status <> ?";
            $params = array(appraisal::STATUS_CLOSED);
        }

        return array($sql, $params);
    }

}

/**
 * Holds role assignments for an appraisal with a given appraisee. This is meant
 * for totara_assign_appraisal's internal use only.
 */
class appraisal_assignments {
    /**
     *  @var array mapping of \job_assignment fields to roles.
     */
    private static $job_assignment_roles = [
        appraisal::ROLE_LEARNER => 'userid',
        appraisal::ROLE_MANAGER => 'managerid',
        appraisal::ROLE_TEAM_LEAD => 'teamleaderid',
        appraisal::ROLE_APPRAISER => 'appraiserid'
    ];

    /**
     * @var int appraisal appraisee assignment id.
     */
    private $appraiseeassignmentid = null;

    /**
     * @var array current user/role assignments.
     */
    private $assignments = [];

    /**
     * Indicates appraisal roles that are missing from the given job assignment.
     *
     * @param array $required required appraisal roles.
     * @param \job_assignment $job reference job assignment.
     *
     * @return array list of the missing roles (an appraisal::ROLE_XYZ constant)
     *         or an empty array if nothing is missing.
     */
    public static function missing_appraisal_roles_from_job(
        array $required, job_assignment $job
    ) {
        return array_reduce(
            $required,

            function (array $accumulated, $role) use ($job) {
                $field = self::$job_assignment_roles[$role];
                return !empty($job->$field)
                       ? $accumulated
                       : array_merge($accumulated, [$role]);
            },

            []
        );
    }

    /**
     * Loads in the role assignments for the given user assignment id.
     *
     * @param int $userassignmentid user assignment id.
     *
     * @return array list of \appraisal_assignments objects
     */
    public static function from_db($userassignmentid) {
        global $DB;

        $records = $DB->get_records(
            'appraisal_role_assignment',
            array('appraisaluserassignmentid' => $userassignmentid)
        );

        return new \appraisal_assignments($userassignmentid, $records);
    }

    /**
     * Creates a new blank assignment object,
     *
     * @param int $appraiseeassignmentid appraisee assignment id.
     *
     * @return \appraisal_assignments the new instance.
     */
    public static function from($userassignmentid) {
        return new \appraisal_assignments($userassignmentid, []);
    }

    /**
     * Constructor.
     *
     * @param int $appraiseeassignmentid appraisee assignment id.
     * @param array $roleassigments initial role assigmentes
     */
    private function __construct(
        $appraiseeassignmentid,
        array $roleassignments
    ) {
        $this->appraiseeassignmentid = $appraiseeassignmentid;

        foreach (array_keys(self::$job_assignment_roles) as $role) {
            $user = new stdClass();

            $user->id = null;
            $user->appraisaluserassignmentid = $appraiseeassignmentid;
            $user->userid = 0;
            $user->appraisalrole = $role;
            $user->activepageid = null;
            $user->timecreated = time();

            $this->assignments[$role] = $user;
        }

        if (empty($roleassignments)) {
            return;
        }

        foreach ($roleassignments as $user) {
            if (!empty($user)) {
                $existing = $this->assignments[$user->appraisalrole];

                $existing->id = $user->id;
                $existing->userid = $user->userid;
                $existing->activepageid = $user->activepageid;
                $existing->timecreated = $user->timecreated;
            }
        }
    }

    /**
     * Returns the appraisee assignment id.
     *
     * @return int appraisee assignment id.
     */
    public function id() {
        return $this->appraiseeassignmentid;
    }

    /**
     * Sets the assigned user from a job assignment for the given role.
     *
     * @param int $role one of the appraisal::ROLE_XYZ constants.
     * @param \job_assignment $job reference job assignment.
     *
     * @return \appraisal_assignments the updated role assignment.
     */
    public function with_role_from_job($role, job_assignment $job) {
        $field = self::$job_assignment_roles[$role];
        return $this->with_user($role, $job->$field);
    }

    /**
     * Sets the assigned user for the given role.
     *
     * @param int $role one of the appraisal::ROLE_XYZ constants.
     * @param int $user assigned user.
     *
     * @return \appraisal_assignments the updated role assignment.
     */
    public function with_user($role, $user) {
        $this->assignments[$role]->userid = empty($user) ? 0 : $user;
        return $this;
    }

    /**
     * Returns the assigned user for the given role.
     *
     * @param int $role one of the appraisal::ROLE_XYZ constants.
     *
     * @return \stdClass object with fields corresponding to the columns in
     *         the appraisal_role_assignment table.
     */
    public function user_with_role($role) {
        return $this->assignments[$role];
    }

    /**
     * Returns a mapping of roles to assigned users. Note this includes empty
     * assignments.
     *
     * @return array a mapping of roles to assigned users.
     */
    public function users() {
        return $this->assignments;
    }
}
