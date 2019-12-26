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
 * @author David Curry <david.curry@totaralms.com>
 * @package totara
 * @subpackage program
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/program/program_assignments.class.php'); // Needed for ASSIGNTYPE_XXX constants.
require_once($CFG->dirroot . '/totara/program/lib.php');

class totara_program_observer {

    /**
     * Handler function called when a program_unassigned event is triggered
     *
     * @param \totara_program\event\program_unassigned $event
     * @return bool Success status
     */
    public static function unassigned(\totara_program\event\program_unassigned $event) {
        global $DB;

        $programid = $event->objectid;
        $userid = $event->userid;

        try {
            $messagesmanager = prog_messages_manager::get_program_messages_manager($programid);
            $program = new program($programid);

            $user = $DB->get_record('user', array('id' => $userid));
            if (empty($user) || $user->suspended) {
                return true; // Do not send to invalid or suspended users.
            }

            $isviewable = $program->is_viewable($user);
            $messages = $messagesmanager->get_messages();
        } catch (ProgramException $e) {
            return true;
        }

        // Send notifications to user and (optionally) the user's manager.
        foreach ($messages as $message) {
            if ($message->messagetype == MESSAGETYPE_UNENROLMENT) {
                if ($user && $isviewable) {
                    $message->send_message($user);
                }
            }
        }

        return true;
    }

    /**
     * Handler function called when a program_completed event is triggered
     *
     * @param \totara_program\event\program_completed $event
     * @return bool Success status
     */
    public static function completed(\totara_program\event\program_completed $event) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/totara/plan/lib.php');

        $programid = $event->objectid;
        $userid = $event->userid;

        try {
            $messagesmanager = prog_messages_manager::get_program_messages_manager($programid);
            $program = new program($programid);
            $user = $DB->get_record('user', array('id' => $userid));
            $isviewable = $program->is_viewable($user);
            $messages = $messagesmanager->get_messages();
        } catch (ProgramException $e) {
            return true;
        }

        // Send notification to user.
        foreach ($messages as $message) {
            if ($message->messagetype == MESSAGETYPE_PROGRAM_COMPLETED) {
                if ($user && $isviewable) {
                    $message->send_message($user);
                }
            }
        }

        // Auto plan completion hook.
        dp_plan_item_updated($userid, 'program', $programid);

        return true;
    }

    /**
     * Handler function called when a courseset_completed event is triggered
     *
     * @param \totara_program\event\program_courseset_completed $event
     * @return bool Success status
     */
    public static function courseset_completed(\totara_program\event\program_courseset_completed $event) {
        global $DB;

        $programid = $event->objectid;
        $userid = $event->userid;
        $coursesetid = $event->other['coursesetid'];

        try {
            $messagesmanager = prog_messages_manager::get_program_messages_manager($programid);
            $messages = $messagesmanager->get_messages();
        } catch (ProgramException $e) {
            return true;
        }

        // Send notification to user.
        foreach ($messages as $message) {
            if ($message->messagetype == MESSAGETYPE_COURSESET_COMPLETED) {
                if ($user = $DB->get_record('user', array('id' => $userid))) {
                    $message->send_message($user, null, array('coursesetid' => $coursesetid));
                }
            }
        }

        return true;
    }

    /**
     * Event that is triggered when a user is confirmed.
     * This checks for and updates any program/certification assignments for the user before they login.
     *
     * Future assignments are created as usual and handled by the login event as usual,
     * since the confirmation doesn't necessarily happen on login.
     *
     *
     * @param \core\event\user_confirmed $event
     *
     */
    public static function user_confirmed(\core\event\user_confirmed $event) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/totara/program/program_assignments.class.php');
        require_once($CFG->dirroot . '/totara/certification/lib.php');

        $userid = $event->relateduserid;
        $now = time();

        $progassignpos = array();
        $progassignorg = array();
        $progassignman = array();
        $allowpos = get_config('totara_job', 'allowsignupposition');
        $alloworg = get_config('totara_job', 'allowsignuporganisation');
        $allowman = get_config('totara_job', 'allowsignupmanager');
        if (!empty($allowpos) || !empty($alloworg) || !empty($allowman)) {
            $jobsql = "SELECT ja.*, p.path as ppath, o.path as opath
                         FROM {job_assignment} ja
                    LEFT JOIN {pos} p
                           ON ja.positionid = p.id
                    LEFT JOIN {org} o
                           ON ja.organisationid = o.id
                        WHERE ja.userid = :uid
                          AND ja.sortorder = 1";
            $job = $DB->get_record_sql($jobsql, array('uid' => $userid));

            if (!empty($allowpos) && !empty($job)) {
                $possql = "
                    SELECT pa.*
                      FROM {prog_assignment} pa
                INNER JOIN {pos} p
                        ON pa.assignmenttypeid = p.id
                 LEFT JOIN {prog_user_assignment} pua
                        ON pua.assignmentid = pa.id
                       AND pua.userid = :uid
                     WHERE pa.assignmenttype = " . ASSIGNTYPE_POSITION . "
                       AND pua.id IS NULL
                       AND ( p.id = :pid
                             OR
                             ( pa.includechildren = 1
                               AND
                               :ppath LIKE " . $DB->sql_concat('p.path', "'/%'") . "
                             )
                           )";
                $posparams = array('uid' => $userid, 'pid' => $job->positionid, 'ppath' => $job->ppath . '/');
                $progassignpos = $DB->get_records_sql($possql, $posparams);
            }

            if (!empty($alloworg) && !empty($job)) {
                $orgsql = "
                    SELECT pa.*
                      FROM {prog_assignment} pa
                INNER JOIN {org} o
                        ON pa.assignmenttypeid = o.id
                 LEFT JOIN {prog_user_assignment} pua
                        ON pua.assignmentid = pa.id
                       AND pua.userid = :uid
                     WHERE pa.assignmenttype = " . ASSIGNTYPE_ORGANISATION . "
                       AND pua.id IS NULL
                       AND ( o.id = :oid
                             OR
                             ( pa.includechildren = 1
                               AND
                               :opath LIKE " . $DB->sql_concat('o.path', "'/%'") . "
                             )
                           )";
                $orgparams = array('uid' => $userid, 'oid' => $job->organisationid, 'opath' => $job->opath . '/');
                $progassignorg = $DB->get_records_sql($orgsql, $orgparams);
            }

            if (!empty($allowman) && !empty($job)) {
                $mansql = "
                    SELECT pa.*
                      FROM {prog_assignment} pa
                INNER JOIN {job_assignment} ja
                        ON pa.assignmenttypeid = ja.id
                 LEFT JOIN {prog_user_assignment} pua
                        ON pua.assignmentid = pa.id
                       AND pua.userid = :uid
                     WHERE pa.assignmenttype = " . ASSIGNTYPE_MANAGERJA . "
                       AND pua.id IS NULL
                       AND ( ja.id = :mjaid
                             OR
                             ( pa.includechildren = 1
                               AND
                               :mjapath LIKE " . $DB->sql_concat('ja.managerjapath', "'/%'") . "
                             )
                           )";
                $manparams = array('uid' => $userid, 'mjaid' => $job->managerjaid, 'mjapath' => $job->managerjapath . '/');
                $progassignman = $DB->get_records_sql($mansql, $manparams);
            }
        }

        // Now check for audience assignments.
        $audsql = 'SELECT pa.*
                     FROM {prog_assignment} pa
                LEFT JOIN {prog_user_assignment} pua
                       ON pua.assignmentid = pa.id
                      AND pua.userid = :uid
                    WHERE pa.assignmenttype = ' . ASSIGNTYPE_COHORT . '
                      AND pua.id IS NULL
                      AND EXISTS ( SELECT 1
                                     FROM {cohort_members} cm
                                    WHERE cm.cohortid = pa.assignmenttypeid
                                      AND cm.userid = :cuid
                                  )';
        $audparams = array('uid' => $userid, 'cuid' => $userid);
        $progassignaud = $DB->get_records_sql($audsql, $audparams);

        $programs = array();
        $progassignments = array_merge($progassignpos, $progassignorg, $progassignman, $progassignaud);
        foreach ($progassignments as $progassign) {
            $assigndata = array();

            if (empty($programs[$progassign->programid])) {
                $program = new program($progassign->programid);
                $programs[$program->id] = $program;
                $assigndata['needscompletionrecord'] = true;
            } else {
                $program = $programs[$progassign->programid];
                $assigndata['needscompletionrecord'] = false;
            }
            $context = context_program::instance($program->id);

            // Check the program is available before creating any assignments.
            if ((empty($program->availablefrom) || $program->availablefrom < $now) &&
                (empty($program->availableuntil) || $program->availableuntil > $now)) {

                // Calculate the timedue for the program assignment.
                $assigndata['timedue'] = $program->make_timedue($userid, $progassign, false);

                // Check for exceptions, we can assume there aren't any dismissed ones at this point.
                if ($program->update_exceptions($userid, $progassign, $assigndata['timedue'])) {
                    $assigndata['exceptions'] = PROGRAM_EXCEPTION_RAISED;
                } else {
                    $assigndata['exceptions'] = PROGRAM_EXCEPTION_NONE;
                }

                // Assign the user.
                $program->assign_learners_bulk(array($userid => $assigndata), $progassign);
                if (!empty($program->certifid)) {
                    // Should be happening on a program_assigned event handler, but we need to do this to make sure that it happens before the completion update.
                    // There shouldn't be any issues calling it twice, since just returns straight away if the record exists.
                    certif_create_completion($program->id, $userid);
                }

                // Create future assignment records, user_confirmation happens before login_completion so this should
                // be caught by the login event and run through the regular code.
                if ($progassign->completionevent == COMPLETION_EVENT_FIRST_LOGIN && $assigndata['timedue'] === false) {
                    $program->create_future_assignments_bulk($program->id, array($userid), $progassign->id);

                    $eventdata = array('objectid' => $program->id, 'context' => $context, 'userid' => $userid);
                    $event = \totara_program\event\program_future_assigned::create($eventdata);
                    $event->trigger();
                }

                // Finally trigger a program assignment event.
                $eventdata = array('objectid' => $program->id, 'context' => $context, 'userid' => $userid);
                $event = \totara_program\event\program_assigned::create($eventdata);
                $event->trigger();

                // For each program (not assignment) update the user completion.
                if ($assigndata['needscompletionrecord']) {
                    // It is unlikely they have any progress at this point but it creates the courseset records.
                    prog_update_completion($userid, $program);
                }
            }
        }
    }

    /**
     * Event that is triggered when a user is deleted.
     *
     * Cancels a user from any programs they are associated with, tables to clear are
     * prog_assignment
     * prog_future_user_assignment
     * prog_user_assignment
     * prog_exception
     * prog_extension
     * prog_messagelog
     *
     * @param \core\event\user_deleted $event
     *
     */
    public static function user_deleted(\core\event\user_deleted $event) {
        global $DB;

        $userid = $event->objectid;

        // We don't want to send messages or anything so just wipe the records from the DB.
        $transaction = $DB->start_delegated_transaction();

        // Delete all the individual assignments for the user.
        $DB->delete_records('prog_assignment', array('assignmenttype' => ASSIGNTYPE_INDIVIDUAL, 'assignmenttypeid' => $userid));

        // Delete any future assignments for the user.
        $DB->delete_records('prog_future_user_assignment', array('userid' => $userid));

        // Delete all the program user assignments for the user.
        $DB->delete_records('prog_user_assignment', array('userid' => $userid));

        // Archive or keep prog_completion records, the same as if the user is being unassigned.
        $progcompletions = prog_load_all_completions($userid);
        foreach ($progcompletions as $progcompletion) {
            prog_conditionally_delete_completion($progcompletion->programid, $userid);
        }

        // Archive or delete certif_completion records, the same as if the user is being unassigned.
        $completions = certif_load_all_completions($userid);
        foreach ($completions as $completion) {
            certif_conditionally_delete_completion($completion['progcompletion']->programid, $userid);
        }

        // Delete all the program exceptions for the user.
        $DB->delete_records('prog_exception', array('userid' => $userid));

        // Delete all the program extensions for the user.
        $DB->delete_records('prog_extension', array('userid' => $userid));

        // Delete all the program message logs for the user.
        $DB->delete_records('prog_messagelog', array('userid' => $userid));

        $transaction->allow_commit();
    }

    /*
     * This function is to cope with program assignments set up
     * with completion deadlines 'from first login' where the
     * user had not yet logged in.
     *
     * @param \core\event\user_loggedin $event
     * @return boolean True if all the update_learner_assignments() succeeded or there was nothing to do
     */
    public static function assignments_firstlogin(\core\event\user_loggedin $event) {
        global $CFG, $DB, $USER;

        if ($USER->firstaccess != $USER->currentlogin) {
            // This is not the first login.
            return true;
        }

        require_once($CFG->dirroot . '/totara/program/lib.php');

        prog_assignments_firstlogin($DB->get_record('user', array('id' => $event->objectid)));

        return true;
    }

    /**
     * This function is to clean up any references to courses within
     * programs when they are deleted. Any coursesets that become empty
     * due to this are also deleted.
     *
     * @param \core\event\course_deleted $event
     * @return boolean True if all references to the course are deleted correctly
     */
    public static function course_deleted(\core\event\course_deleted $event) {
        global $DB;

        $courseid = $event->objectid;

        // Get coursesets where the course will be removed.
        $sql = 'SELECT cs.id, cs.programid
                  FROM {prog_courseset} cs
                  JOIN {prog_courseset_course} c
                    ON cs.id = c.coursesetid
                 WHERE c.courseid = :courseid
              GROUP BY cs.id, cs.programid';
        $affectedcoursesets = $DB->get_records_sql($sql, array('courseid' => $courseid));

        foreach($affectedcoursesets as $affectedcourseset) {
            $content = new prog_content($affectedcourseset->programid);
            /** @var course_set $courseset */
            $courseset = $content->get_courseset_by_id($affectedcourseset->id);

            // There is a delete_course function for prog_content, but this affects all the course from all
            // coursesets in the program, but it also expects sortorder as a param to identify the courseset,
            // and sortorder will change.
            $courseset->delete_course($courseid);

            $coursesetcourses = $courseset->get_courses();
            if (empty($coursesetcourses)) {
                // The method delete_set takes the sortorder value, which may have changed
                // after deleting the course from a previous courseset.
                $content->delete_courseset_by_id($affectedcourseset->id);
            }
            $content->save_content();
        }

        return true;
    }

    /**
     * This function is triggered when the members of a cohort are (or might have been) updated.
     * It needs to mark all related programs and certifications for deferred update. The prog and cert
     * users will then be updated the next time the deferred program assignments scheduled task runs.
     *
     * @param \core\event\base $event Can be various events but objectid must be a cohort.
     * @return boolean True if successful
     */
    public static function cohort_members_updated(\core\event\base $event) {
        global $DB;
        $cohortid = $event->objectid;

        $sql = "UPDATE {prog} SET assignmentsdeferred = 1
                 WHERE id IN (SELECT programid
                                FROM {prog_assignment}
                               WHERE assignmenttype = :assignmenttypecohort
                                 AND assignmenttypeid = :cohortid)";
        $DB->execute($sql, array('assignmenttypecohort' => ASSIGNTYPE_COHORT, 'cohortid' => $cohortid));

        return true;
    }

    /**
     * This function is triggered when a user's job assignment is updated. Their manager, position or organisation may
     * have changed, in which case we mark the programs and certifications which contain both the new and old
     * management hierarchy, position and organisation for deferred update.
     *
     * @param \totara_job\event\job_assignment_updated $event
     * @return boolean True if successful
     */
    public static function job_assignment_updated(\totara_job\event\job_assignment_updated $event) {
        global $DB;

        $newjobassignment = \totara_job\job_assignment::get_with_id($event->objectid);
        if ($newjobassignment->userid != $event->relateduserid) {
            throw new Exception('Job assignment userid does not match relateduserid in totara_program_observer::job_assignment_updated');
        }

        if ($newjobassignment->managerjaid != $event->other['oldmanagerjaid']) {
            $directmanagerjaidstoprocess = array();
            $indirectmanagerjaidstoprocess = array();

            if ($newjobassignment->managerjaid) {
                $directmanagerjaidstoprocess[] = $newjobassignment->managerjaid;
                $path = explode('/', substr($newjobassignment->managerjapath, 1));
                $countpath = count($path);
                if ($countpath > 2) {
                    // Don't include the user or their manager here.
                    $indirectmanagerjaidstoprocess = array_merge($indirectmanagerjaidstoprocess, array_slice($path, 0, $countpath - 2));
                }
            }

            if ($event->other['oldmanagerjaid']) {
                $directmanagerjaidstoprocess[] = $event->other['oldmanagerjaid'];
                $path = explode('/', substr($event->other['oldmanagerjapath'], 1));
                $countpath = count($path);
                if ($countpath > 2) {
                    // Don't include the user or their manager here.
                    $indirectmanagerjaidstoprocess = array_merge($indirectmanagerjaidstoprocess, array_slice($path, 0, $countpath - 2));
                }
            }

            if (!empty($directmanagerjaidstoprocess) || !empty($indirectmanagerjaidstoprocess)) {
                $params = array('assignmenttypemanager' => ASSIGNTYPE_MANAGERJA);
                $managersql = "";

                if (!empty($directmanagerjaidstoprocess)) {
                    list($directinsql, $directparams) = $DB->get_in_or_equal($directmanagerjaidstoprocess, SQL_PARAMS_NAMED);
                    $managersql .= "assignmenttypeid " . $directinsql;
                    $params = array_merge($params, $directparams);
                }

                if (!empty($indirectmanagerjaidstoprocess)) {
                    if (!empty($managersql)) {
                        $managersql .= " OR ";
                    }

                    list($indirectinsql, $indirectparams) = $DB->get_in_or_equal($indirectmanagerjaidstoprocess, SQL_PARAMS_NAMED);
                    $managersql .= "assignmenttypeid {$indirectinsql} AND includechildren = 1";
                    $params = array_merge($params, $indirectparams);
                }

                $sql = "UPDATE {prog} SET assignmentsdeferred = 1
                         WHERE id IN (SELECT programid
                                        FROM {prog_assignment}
                                       WHERE assignmenttype = :assignmenttypemanager
                                         AND ($managersql))";
                $DB->execute($sql, $params);
            }
        }

        if ($newjobassignment->positionid != $event->other['oldpositionid']) {
            $positionstoprocess = array();

            if ($newjobassignment->positionid) {
                $positionstoprocess[] = $newjobassignment->positionid;
            }

            if ($event->other['oldpositionid']) {
                $positionstoprocess[] = $event->other['oldpositionid'];
            }

            if (!empty($positionstoprocess)) {
                list($insql, $params) = $DB->get_in_or_equal($positionstoprocess, SQL_PARAMS_NAMED);
                $sql = "UPDATE {prog} SET assignmentsdeferred = 1
                         WHERE id IN (SELECT programid
                                        FROM {prog_assignment}
                                       WHERE assignmenttype = :assignmenttypeposition
                                         AND assignmenttypeid {$insql})";
                $params['assignmenttypeposition'] = ASSIGNTYPE_POSITION;
                $DB->execute($sql, $params);

                // Now do the same check for programs where includechildren is set.
                $sql = "SELECT path
                          FROM {pos}
                         WHERE id {$insql}";
                unset($params['assignmenttypeposition']);
                $pospaths = $DB->get_records_sql($sql, $params);
                $posparents = array();
                foreach($pospaths as $pospath) {
                    $patharray = explode('/', $pospath->path);
                    $posparents = array_merge($posparents, $patharray);
                }
                $posparents = array_unique($posparents);
                $posparents = array_filter($posparents);

                if (!empty($posparents)) {
                    list($insql, $params) = $DB->get_in_or_equal($posparents, SQL_PARAMS_NAMED);
                    $sql = "UPDATE {prog} SET assignmentsdeferred = 1
                             WHERE id IN (SELECT programid
                                        FROM {prog_assignment}
                                       WHERE assignmenttype = :assignmenttypeposition
                                         AND includechildren = 1
                                         AND assignmenttypeid {$insql})";
                    $params['assignmenttypeposition'] = ASSIGNTYPE_POSITION;
                    $DB->execute($sql, $params);
                }
            }
        }

        if ($newjobassignment->organisationid != $event->other['oldorganisationid']) {
            $organisationstoprocess = array();

            if ($newjobassignment->organisationid) {
                $organisationstoprocess[] = $newjobassignment->organisationid;
            }

            if ($event->other['oldorganisationid']) {
                $organisationstoprocess[] = $event->other['oldorganisationid'];
            }

            if (!empty($organisationstoprocess)) {
                list($insql, $params) = $DB->get_in_or_equal($organisationstoprocess, SQL_PARAMS_NAMED);
                $sql = "UPDATE {prog} SET assignmentsdeferred = 1
                         WHERE id IN (SELECT programid
                                        FROM {prog_assignment}
                                       WHERE assignmenttype = :assignmenttypeorganisation
                                         AND assignmenttypeid {$insql})";
                $params['assignmenttypeorganisation'] = ASSIGNTYPE_ORGANISATION;
                $DB->execute($sql, $params);

                // Now do the same check for programs where includechildren is set.
                $sql = "SELECT path
                          FROM {org}
                         WHERE id {$insql}";
                unset($params['assignmenttypeorganisation']);
                $orgpaths = $DB->get_records_sql($sql, $params);
                $orgparents = array();
                foreach($orgpaths as $orgpath) {
                    $patharray = explode('/', $orgpath->path);
                    $orgparents = array_merge($orgparents, $patharray);
                }
                $orgparents = array_unique($orgparents);
                $orgparents = array_filter($orgparents);

                if (!empty($orgparents)) {
                    list($insql, $params) = $DB->get_in_or_equal($orgparents, SQL_PARAMS_NAMED);
                    $sql = "UPDATE {prog} SET assignmentsdeferred = 1
                             WHERE id IN (SELECT programid
                                        FROM {prog_assignment}
                                       WHERE assignmenttype = :assignmenttypeorganisation
                                         AND includechildren = 1
                                         AND assignmenttypeid {$insql})";
                    $params['assignmenttypeorganisation'] = ASSIGNTYPE_ORGANISATION;
                    $DB->execute($sql, $params);
                }
            }
        }

        return true;
    }

    /**
     * Handler function called when a course_in_progress event is triggered
     * This marks any relevant programs as started for the user.
     *
     * @param \core\event\course_in_progress $event
     * @return bool Success status
     */
    public static function course_in_progress(\core\event\course_in_progress $event) {
        global $DB;

        $userid = $event->relateduserid;
        $courseid = $event->courseid;

        $sql = "SELECT pc.id, pc.programid
                FROM {prog_courseset} pc
                JOIN {prog_courseset_course} pcc ON pcc.coursesetid = pc.id AND pcc.courseid = :cid
                WHERE EXISTS (
                    SELECT pua.id
                    FROM {prog_user_assignment} pua
                    JOIN {prog_completion} comp ON comp.userid = pua.userid
                        AND comp.programid = pua.programid
                        AND comp.coursesetid = 0
                        AND comp.timecompleted = 0
                    WHERE pua.programid = pc.programid
                    AND pua.userid = :uid)";
        $params = array('uid' => $userid, 'cid' => $courseid);

        $coursesets = $DB->get_records_sql($sql, $params);
        foreach ($coursesets as $courseset) {
            $params = array();
            $params['coursesetid'] = $courseset->id;
            $params['userid'] = $userid;
            $params['programid'] = $courseset->programid;
            $params['timestarted'] = 0;

            // Check the program courseset is available, by getting the program completion.
            if ($cscomp = $DB->get_record('prog_completion', $params)) {
                $cscomp->timestarted = time();
                $DB->update_record('prog_completion', $cscomp);

                // Check the program completion (courseset 0) record.
                $params['coursesetid'] = 0;
                if ($progcomp = $DB->get_record('prog_completion', $params)) {
                    $progcomp->timestarted = time();
                    $DB->update_record('prog_completion', $progcomp);
                }
            }
        }

        \totara_program\progress\program_progress_cache::mark_user_cache_stale($userid);

        return true;
    }
}
