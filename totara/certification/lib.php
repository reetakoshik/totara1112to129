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
 * @author Jon Sharp <jonathans@catalyst-eu.net>
 * @package totara
 * @subpackage certification
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once($CFG->dirroot.'/totara/program/program_assignments.class.php'); // For assignment category literals.
require_once($CFG->dirroot.'/totara/program/program.class.php'); // For program status constants.

// Certification types (learning component types).
define('CERTIFTYPE_PROGRAM', 1);
define('CERTIFTYPE_COURSE', 2);
define('CERTIFTYPE_COMPETENCY', 3);
global $CERTIFTYPE;
$CERTIFTYPE = array(
    'type_notset',
    'type_program',
    'type_course',
    'type_competency',
);

// Certification completion status, status column in certif_completion.
define('CERTIFSTATUS_UNSET', 0);
define('CERTIFSTATUS_ASSIGNED', 1);
define('CERTIFSTATUS_INPROGRESS', 2);
define('CERTIFSTATUS_COMPLETED', 3);
define('CERTIFSTATUS_EXPIRED', 4);

global $CERTIFSTATUS;
$CERTIFSTATUS = array(
    CERTIFSTATUS_UNSET => 'status_unset',
    CERTIFSTATUS_ASSIGNED => 'status_notcertified',
    CERTIFSTATUS_INPROGRESS => 'status_inprogress',
    CERTIFSTATUS_COMPLETED => 'status_certified',
    CERTIFSTATUS_EXPIRED => 'status_expired',
);

// Certification completion state, not stored in db, describes the state that a user's completion data is in.
define('CERTIFCOMPLETIONSTATE_INVALID', 0);
define('CERTIFCOMPLETIONSTATE_ASSIGNED', 1);
define('CERTIFCOMPLETIONSTATE_CERTIFIED', 2);
define('CERTIFCOMPLETIONSTATE_WINDOWOPEN', 3);
define('CERTIFCOMPLETIONSTATE_EXPIRED', 4);

global $CERTIFCOMPLETIONSTATE;
$CERTIFCOMPLETIONSTATE = array(
    CERTIFCOMPLETIONSTATE_INVALID => 'stateinvalid',
    CERTIFCOMPLETIONSTATE_ASSIGNED => 'stateassigned',
    CERTIFCOMPLETIONSTATE_CERTIFIED => 'statecertified',
    CERTIFCOMPLETIONSTATE_WINDOWOPEN => 'statewindowopen',
    CERTIFCOMPLETIONSTATE_EXPIRED => 'stateexpired',
);

// Renewal status column in course.
define('CERTIFRENEWALSTATUS_NOTDUE', 0);
define('CERTIFRENEWALSTATUS_DUE', 1);
define('CERTIFRENEWALSTATUS_EXPIRED', 2);

global $CERTIFRENEWALSTATUS;
$CERTIFRENEWALSTATUS = array(
    CERTIFRENEWALSTATUS_NOTDUE => 'renewalstatus_notdue',
    CERTIFRENEWALSTATUS_DUE => 'renewalstatus_dueforrenewal',
    CERTIFRENEWALSTATUS_EXPIRED => 'renewalstatus_expired',
);

// When the re-certifcation completion statuses.
define('CERTIFRECERT_UNSET', 0);
define('CERTIFRECERT_COMPLETION', 1);
define('CERTIFRECERT_EXPIRY', 2);
define('CERTIFRECERT_FIXED', 3);

global $CERTIFRECERT;
$CERTIFRECERT = array(
    CERTIFRECERT_UNSET => 'unset',
    CERTIFRECERT_COMPLETION => get_string('editdetailsrccmpl', 'totara_certification'),
    CERTIFRECERT_EXPIRY => get_string('editdetailsrcexp', 'totara_certification'),
    CERTIFRECERT_FIXED => get_string('editdetailsrcfixed', 'totara_certification'),
);

// Certifcation path constants.
define('CERTIFPATH_UNSET', 0);
define('CERTIFPATH_STD', 1);
define('CERTIFPATH_CERT', 1);
define('CERTIFPATH_RECERT', 2);

global $CERTIFPATH;
$CERTIFPATH = array(
    CERTIFPATH_UNSET => 'unset',
    CERTIFPATH_CERT => 'certification',
    CERTIFPATH_RECERT => 'recertification',
);

global $CERTIFPATHSUF;
$CERTIFPATHSUF = array(
    CERTIFPATH_UNSET => '_',
    CERTIFPATH_CERT => '_ce',
    CERTIFPATH_RECERT => '_rc',
);

class certification_event_handler {

    /**
     * User is assigned to a program event handler
     *
     * @param \totara_program\event\program_assigned $event
     */
    public static function assigned(\totara_program\event\program_assigned $event) {
        global $DB;

        $programid = $event->objectid;
        $userid = $event->userid;
        $prog = $DB->get_record('prog', array('id' => $programid));

        if ($prog->certifid) {
            certif_create_completion($programid, $userid);
        }
    }

    /**
     * Program completion event handler
     *
     * @param \totara_program\event\program_completed $event
     */
    public static function completed(\totara_program\event\program_completed $event) {
        global $DB;

        if (!empty($event->other['certifid'])) {
            complete_certification_stage($event->other['certifid'], $event->userid);

            certif_write_completion_log($event->objectid, $event->userid, 'User became certified');
        }
    }

    /**
     * Handler triggered when certification settings are changed, creates log which will show up on all users' transaction logs.
     *
     * @param \totara_certification\event\certification_updated $event
     */
    public static function certification_updated(\totara_certification\event\certification_updated $event) {
        global $DB;

        // Write to the certification completion log. Don't provide userid, so that it shows on all users' transaction lists.
        $cert = $DB->get_record('certif', array('id' => $event->get_instance()->certifid));
        $minimumactiveperiod = '';
        $recertification = '';
        switch ($cert->recertifydatetype) {
            case CERTIFRECERT_COMPLETION:
                $recertification = 'Use certification completion date';
                break;
            case CERTIFRECERT_EXPIRY:
                $recertification = 'Use certification expiry date';
                break;
            case CERTIFRECERT_FIXED:
                $recertification = 'Use fixed expiry date';
                $minimumactiveperiod = '<li>Minimum active period: ' . $cert->minimumactiveperiod . '</li>';
                break;
        }
        $description = 'Certification settings changed<br>' .
            '<ul><li>Recertification date: ' . $recertification . '</li>' .
            '<li>Active period: ' . $cert->activeperiod . '</li>' .
            $minimumactiveperiod .
            '<li>Window period: ' . $cert->windowperiod . '</li></ul>';

        prog_log_completion(
            $event->objectid,
            null,
            $description
        );
    }
}

// Stages functions.

/**
 * Updates a course & users's certif_completion record's status to 'in progress'
 * Can be called multiple times without a problem as will only overwrite appropriate statuses
 *
 * called from completion/completion_completion.php on first acces of course (also by cron with user being cron
 * user (eg admin) - why?)
 *
 * @param int $courseid
 * @param int $userid
 * @return boolean (false if not a course&user)
 */
function inprogress_certification_stage($courseid, $userid) {
    global $DB;

    $sql = "SELECT DISTINCT cfc.id, cfc.status, cfc.renewalstatus, cfc.certifid
              FROM {certif_completion} cfc
        INNER JOIN {prog} p
                ON p.certifid = cfc.certifid
        INNER JOIN {prog_user_assignment} pua
                ON pua.programid = p.id
               AND pua.userid = cfc.userid
        INNER JOIN {prog_courseset} pcs
                ON pcs.programid = p.id
               AND pcs.certifpath = cfc.certifpath
        INNER JOIN {prog_courseset_course} pcsc
                ON pcsc.coursesetid = pcs.id
             WHERE cfc.userid = :uid
               AND pcsc.courseid = :cid";
    $params = array('uid' => $userid, 'cid' => $courseid);

    $completion_records = $DB->get_records_sql($sql, $params);

    $count = count($completion_records);
    if ($count == 0) {
        // If 0 then this course & user is not in an assigned certification.
        return false;
    }

    foreach ($completion_records as $comprec) {
        // Change only from specific states as function can be called at any time (whenever course is viewed)
        // from unset, assigned, expired - any time
        // from completed when renewal status is dueforrenewal.
        if ($comprec->status < CERTIFSTATUS_INPROGRESS
            || $comprec->status == CERTIFSTATUS_EXPIRED
            || ($comprec->status == CERTIFSTATUS_COMPLETED && $comprec->renewalstatus == CERTIFRENEWALSTATUS_DUE)) {
            $todb = new StdClass();
            $todb->id = $comprec->id;
            $todb->status = CERTIFSTATUS_INPROGRESS;
            $todb->timemodified = time();

            $DB->update_record('certif_completion', $todb);
        }
    }

    \totara_program\progress\program_progress_cache::mark_user_cache_stale($userid);

    return true;
}

/**
 * Could come from assign processing (When user has prior completion of courses in program etc,
 * or when user completes program etc)
 *
 * @param integer certificationid
 * @param integer userid
 * @return boolean
 */
function complete_certification_stage($certificationid, $userid) {
    global $DB;

    // Set for recertification - dates etc.
    write_certif_completion($certificationid, $userid, CERTIFPATH_RECERT);

    // Set course renewal status to not due.
    $courseids = array();
    $courses = find_courses_for_certif($certificationid, 'c.id, c.fullname');
    foreach ($courses as $course) {
        $courseids[] = $course->id;
    }
    set_course_renewalstatus($courseids, $userid, CERTIFRENEWALSTATUS_NOTDUE);

    return true;
}

/**
 * Triggered by the cron, gets all certifications that have the
 * re-certify window due to be open and perform actions
 *
 * @return int Count of certification completion records
 */
function recertify_window_opens_stage() {
    global $DB, $CFG;

    // Find any users who have reached this point.
    $sql = "SELECT cfc.id as uniqueid, u.*, cf.id as certifid, cfc.userid, p.id as progid, cfc.timewindowopens as windowopens
            FROM {certif_completion} cfc
            JOIN {certif} cf
              ON cf.id = cfc.certifid
            JOIN {prog} p
              ON p.certifid = cf.id
            JOIN {user} u
              ON u.id = cfc.userid
           WHERE EXISTS (SELECT 1
                           FROM {prog_user_assignment} pua
                          WHERE pua.userid = u.id
                            AND pua.programid = p.id
                            AND pua.exceptionstatus <> :raised
                            AND pua.exceptionstatus <> :dismissed
                        )
             AND cfc.timewindowopens < :window
             AND cfc.status = :stat
             AND cfc.renewalstatus = :renstat
             AND u.deleted = 0
             AND u.suspended = 0";

    $params = array(
        'raised' =>  PROGRAM_EXCEPTION_RAISED,
        'dismissed' => PROGRAM_EXCEPTION_DISMISSED,
        'window' => time(),
        'stat' => CERTIFSTATUS_COMPLETED,
        'renstat' => CERTIFRENEWALSTATUS_NOTDUE
    );

    $results = $DB->get_records_sql($sql, $params);

    require_once($CFG->dirroot.'/course/lib.php'); // Archive_course_activities().
    // For each certification & user.
    foreach ($results as $user) {
        // Archive completion.
        copy_certif_completion_to_hist($user->certifid, $user->id);

        $courses = find_courses_for_certif($user->certifid, 'c.id, c.fullname');

        // Set the renewal status of the certification/program to due for renewal.
        $DB->set_field('certif_completion', 'renewalstatus', CERTIFRENEWALSTATUS_DUE,
            array('certifid' => $user->certifid, 'userid' => $user->id));

        certif_write_completion_log($user->progid, $user->id,
            'Window opened, current certification completion archived, certif_completion updated (step 1 of 2)'
        );

        // Reset course_completions, course_module_completions, program_completion records.
        reset_certifcomponent_completions($user, $courses);

        // Get the messages for the certification, using the message manager cache.
        $messagesmanager = prog_messages_manager::get_program_messages_manager($user->progid);
        $messages = $messagesmanager->get_messages();

        foreach ($messages as $message) {
            if ($message->messagetype == MESSAGETYPE_RECERT_WINDOWOPEN) {
                // This function checks prog_messagelog for existing record. If it exists, the message is not sent.
                $message->send_message($user);
            }
        }

        // Recalculate program completion, in case the user already meets the criteria. Unlikely, but could happen due to
        // something like f2f sessions. Also, create the first non-zero course set group completion record, with timedue.
        $program = new program($user->progid);
        prog_update_completion($user->id, $program);
    }

    return count($results);
}

/**
 * Triggered by the cron, run actions needed when a certification's
 * re-certify window is about to close
 *
 * @return int Count of certification completion records
 */
function recertify_window_abouttoclose_stage() {
    global $DB, $CFG;

    // Need these when called from cron.
    require_once($CFG->dirroot . '/totara/program/program_messages.class.php');
    require_once($CFG->dirroot . '/totara/program/program_message.class.php');
    require_once($CFG->dirroot . '/totara/program/program.class.php');

    // See if there are any programs & users where:
    // now > (timeexpires - offset-for-that-certif/prog)
    // now < timeexpires (to minimise number of send attempts).

    list($statussql, $statusparams) = $DB->get_in_or_equal(array(CERTIFSTATUS_COMPLETED, CERTIFSTATUS_INPROGRESS));

    $uniqueid = $DB->sql_concat('cfc.id', "'_'", 'pm.id');
    $sql = "SELECT {$uniqueid} as uniqueid, u.*, p.id as progid, pm.id as pmid
            FROM {certif_completion} cfc
            JOIN {certif} cf on cf.id = cfc.certifid
            JOIN {prog} p ON p.certifid = cf.id
            JOIN {prog_message} pm ON pm.programid = p.id
            JOIN {user} u ON u.id = cfc.userid
            WHERE cfc.status {$statussql}
                  AND cfc.renewalstatus = ?
                  AND ? > (cfc.timeexpires - pm.triggertime)
                  AND ? < cfc.timeexpires
                  AND pm.messagetype = ?
                  AND u.deleted = 0
                  AND u.suspended = 0";

    $now = time();
    $params = array_merge($statusparams, array(CERTIFRENEWALSTATUS_DUE, $now, $now, MESSAGETYPE_RECERT_WINDOWDUECLOSE));
    $results = $DB->get_records_sql($sql, $params);

    foreach ($results as $user) {
        // Get the messages for the certification, using the message manager cache.
        $messagesmanager = prog_messages_manager::get_program_messages_manager($user->progid);
        $messages = $messagesmanager->get_messages();

        foreach ($messages as $message) {
            if ($message->id == $user->pmid) {
                // This function checks prog_messagelog for existing record. If it exists, the message is not sent.
                $message->send_message($user);
            }
        }
    }

    return count($results);
}

/**
 * Triggered by cron, run actions to expire a certification stage
 *
 * @return int Count of certification completion records
 */
function recertify_expires_stage() {
    global $DB;

    // Find any users who have reached this point.
    list($statussql, $statusparams) = $DB->get_in_or_equal(array(CERTIFSTATUS_COMPLETED, CERTIFSTATUS_INPROGRESS));
    $sql = "SELECT cfc.id as uniqueid, u.*, cf.id as certifid, p.id as progid
            FROM {certif_completion} cfc
            JOIN {certif} cf ON cf.id = cfc.certifid
            JOIN {prog} p ON p.certifid = cf.id
            JOIN {user} u ON u.id = cfc.userid
            WHERE ? > cfc.timeexpires
                AND cfc.renewalstatus = ?
                AND cfc.status {$statussql}
                AND u.deleted = 0
                AND u.suspended = 0";

    $params = array_merge(array(time(), CERTIFRENEWALSTATUS_DUE), $statusparams);
    $results = $DB->get_records_sql($sql, $params);

    foreach ($results as $user) {
        // Set the renewal status of the certification to Expired.
        // Assign the user back to the original certification path. This means the content of their certification
        // will change to show the original set of courses.
        write_certif_completion($user->certifid, $user->id, CERTIFPATH_CERT, CERTIFRENEWALSTATUS_EXPIRED);

        // For each course in the certification, set the renewal status to Expired.
        $courseids = array();
        $courses = find_courses_for_certif($user->certifid, 'c.id, c.fullname');
        foreach ($courses as $course) {
            $courseids[] = $course->id;
        }

        set_course_renewalstatus($courseids, $user->id, CERTIFRENEWALSTATUS_EXPIRED);

        // Get the messages for the certification, using the message manager cache.
        $messagesmanager = prog_messages_manager::get_program_messages_manager($user->progid);
        $messages = $messagesmanager->get_messages();

        foreach ($messages as $message) {
            if ($message->messagetype == MESSAGETYPE_RECERT_FAILRECERT) {
                // This function checks prog_messagelog for existing record. If it exists, the message is not sent.
                $message->send_message($user);
            }
        }

        certif_write_completion_log($user->progid, $user->id,
            'Certification expired, changed to primary certification path'
        );

        // Recalculate program completion, in case the user already meets the criteria. Possible since we are switching paths
        // and the user may already have progress in the recert path. Also, create the first non-zero course set group
        // completion record, with timedue.
        $program = new program($user->progid);
        prog_update_completion($user->id, $program);
    }

    return count($results);
}

/**
 * Triggered by cron, this function should check for missing certif_completion records. If it finds any missing then
 * they should be created, and missing dependent prog_completion (course set > 0) records should also be created.
 *
 * @return int Count of certif_completion records created. Greater than 0 indicates that a problem was discovered and repaired.
 */
function certification_fix_missing_certif_completions() {
    global $DB;

    // Look for prog_completion (course set = 0) records without matching certif_completion records.
    $sql = "SELECT DISTINCT p.id AS progid, p.certifid, pc.userid
              FROM {prog_completion} pc
              JOIN {prog} p ON pc.programid = p.id
              JOIN {prog_user_assignment} pua ON pua.programid = p.id AND pua.userid = pc.userid
         LEFT JOIN {certif_completion} cc ON cc.certifid = p.certifid AND cc.userid = pc.userid
             WHERE pc.coursesetid = 0
               AND p.certifid IS NOT NULL
               AND cc.id IS NULL";
    $missingcertifs = $DB->get_recordset_sql($sql);

    $missingcount = 0;
    $programs = array();
    foreach ($missingcertifs as $missingcertif) {
        $missingcount++;

        // Create the missing certif_completion record.
        write_certif_completion($missingcertif->certifid, $missingcertif->userid);

        // Create missing prog_completion (course set != 0) records for completed course sets.
        if (!isset($programs[$missingcertif->progid])) {
            // Cache the program objects.
            $programs[$missingcertif->progid] = new program($missingcertif->progid);
        }
        prog_update_completion($missingcertif->userid, $programs[$missingcertif->progid]);
    }

    $missingcertifs->close();

    return $missingcount;
}

/**
 * Get time of last completed certification course set.
 *
 * If no matching record is found then this function returns null.
 *
 * @param integer $certificationid
 * @param integer $userid
 * @param int $path null if courses in both paths should be considered, else CERTIFPATH_CERT or CERTIFPATH_RECERT
 * @return integer|null
 */
function certif_get_content_completion_time($certificationid, $userid, $path = null) {
    global $DB;

    // Get maximum completion date of the applicable coursesets.
    $sql = "SELECT MAX(pc.timecompleted) AS timecompleted
              FROM {prog_completion} pc
              JOIN {prog} prog ON pc.programid = prog.id
              JOIN {prog_courseset} pcs ON pcs.id = pc.coursesetid
             WHERE prog.certifid = :certifid AND pc.userid = :userid";
    $params = array('certifid' => $certificationid, 'userid' => $userid);
    if ($path) {
        $sql .= " AND pcs.certifpath = :path";
        $params['path'] = $path;
    }
    $coursesetcompletion = $DB->get_record_sql($sql, $params);

    if (!$coursesetcompletion) {
        return 0;
    }
    return $coursesetcompletion->timecompleted;
}

/**
 * Create/update certif_completion record at start of path (assign or complete stages)
 *
 * @param integer $certificationid
 * @param integer $userid
 * @param integer $certificationpath
 * @param integer $renewalstatus
 */
function write_certif_completion($certificationid, $userid, $certificationpath = CERTIFPATH_CERT,
                                                            $renewalstatus = CERTIFRENEWALSTATUS_NOTDUE) {
    global $DB;

    $certification = $DB->get_record('certif', array('id' => $certificationid));
    if (!$certification) {
        print_error('error:incorrectcertifid', 'totara_certification', null, $certificationid);
    }

    $certificationcompletion = $DB->get_record('certif_completion', array('certifid' => $certificationid, 'userid' => $userid));

    $now = time();

    // Create certification completion record.
    $todb = new StdClass();
    $todb->certifid = $certificationid;
    $todb->userid = $userid;
    $todb->renewalstatus = $renewalstatus;
    $todb->certifpath = $certificationpath;
    if ($certificationpath == CERTIFPATH_RECERT) { // The user has just certified, so their new path is recert.
        $todb->status = CERTIFSTATUS_COMPLETED;
        $lastcompleted = certif_get_content_completion_time($certificationid, $userid, $certificationcompletion->certifpath);
        // If no courses completed, maintain default behaviour.
        if (!$lastcompleted) {
            $lastcompleted = time();
        }
        // See get_certiftimebase to see how the base time used for calculating re-certification is calculated.
        //
        // Prior learning:
        // Normally when the program completion event is called (and hence this function) we just need to record the current
        // date-time and calculate the new expiry etc.
        // However with prior learning, where courses may have been completed before being added to a program,
        // the preferred date is the date of the last course. As there is currently no way to differentiate between a user/program
        // which is prior learning and not, we have to do this check for all program completions - rather than just using
        // the current time.
        // Note: the completion date in prog_completion will still be 'now' - not the last course-completion date so will
        // differ from certification completion.
        $programid = $DB->get_field('prog', 'id', array('certifid' => $certificationid));

        $timedue = $DB->get_field('prog_completion', 'timedue',
            array('programid' => $programid, 'userid' => $userid, 'coursesetid' => 0));

        //TL-17804: Use baselinetimeexpires instead of timeexpires so we don't get unexpected shifts in recertification
        //windows when granting extensions
        $base = get_certiftimebase($certification->recertifydatetype, $certificationcompletion->baselinetimeexpires,
            $lastcompleted, $timedue, $certification->activeperiod, $certification->minimumactiveperiod,
            $certification->windowperiod);

        $todb->timeexpires = get_timeexpires($base, $certification->activeperiod);
        $todb->timewindowopens = get_timewindowopens($todb->timeexpires, $certification->windowperiod);
        $todb->timecompleted = $lastcompleted;
        $todb->baselinetimeexpires = $todb->timeexpires; //TL-17804: Copy expiry to default expiry field to preserve it in case of extensions

        // Put the new timeexpires into the timedue field in the prog_completion, so that it will be there if the cert expires.
        $DB->set_field('prog_completion', 'timedue', $todb->timeexpires,
            array('programid' => $programid, 'userid' => $userid, 'coursesetid' => 0));
    } else { // This is a new certification assignment or the user's certification has just expired, so their new path in cert.
        if ($renewalstatus == CERTIFRENEWALSTATUS_EXPIRED) {
            $todb->status =  CERTIFSTATUS_EXPIRED;
        } else {
            $todb->status =  CERTIFSTATUS_ASSIGNED;
        }
        // Window/expires not relevant for CERTIFPATH_CERT as should be doing in program 'due' time.
        $todb->timewindowopens = 0;
        $todb->timeexpires = 0;
        $todb->timecompleted = 0;
        $todb->baselinetimeexpires = 0;
    }

    $todb->timemodified = $now;

    if ($certificationcompletion) {
        $todb->id = $certificationcompletion->id;
        $DB->update_record('certif_completion', $todb);
    } else {
        $id = $DB->insert_record('certif_completion', $todb);
    }
}

/**
 * Copy a certif_completion record to certif_completion_history
 *
 * @param integer $certificationid
 * @param integer $userid
 * @param bool $unassigned
 * @return boolean
 */
function copy_certif_completion_to_hist($certificationid, $userid, $unassigned = false) {
    global $DB;

    $certificationcompletion = $DB->get_record('certif_completion', array('certifid' => $certificationid, 'userid' => $userid));

    if (!$certificationcompletion) {
        print_error('error:incorrectid', 'totara_certification');
    }

    $certificationcompletion->timemodified = time();
    $certificationcompletion->unassigned = $unassigned;
    $completionhistory = $DB->get_record('certif_completion_history',
            array(
                'certifid'      => $certificationid,
                'userid'        => $userid,
                'timeexpires'   => $certificationcompletion->timeexpires,
                'timecompleted' => $certificationcompletion->timecompleted));

    if ($completionhistory) {
        $certificationcompletion->id = $completionhistory->id;
        $DB->update_record('certif_completion_history', $certificationcompletion);

        certif_write_completion_history_log($certificationcompletion->id,
            'Certification completion copied over existing completion history');
    } else {
        unset($certificationcompletion->id);
        $newchid = $DB->insert_record('certif_completion_history', $certificationcompletion);

        certif_write_completion_history_log($newchid,
            'Certification completion copied to new completion history');
    }

    return true;
}

/**
 * Create prog and cert completion records, if they don't already exist. New records will be in the "assigned" state.
 * If the user has an applicable existing history record then it will be restored.
 *
 * This function is safe to call if the completion records already exist.
 *
 * Note: This function should be used to create both the prog_completion and certif_completion records. It can create
 * a certif_completion record for an existing prog_completion record, but this should only occur when re-assigning a
 * user to a certification they were in previously (where the certif_completion record was moved to history and the
 * prog_completion record was left). Never create a prog_completion records outside this function - it will just lead
 * to invalid data.
 *
 * @param $programid
 * @param $userid
 * @param string $message If provided, will be added to the start of the log message.
 */
function certif_create_completion($programid, $userid, $message = '') {
    global $DB;

    $now = time();

    // Check that this is actually a certification.
    $program = $DB->get_record('prog', array('id' => $programid));
    if (empty($program->certifid)) {
        print_error('error:missingcertifid', 'totara_certification');
    }

    // Check if the prog_completion record already exists. If not, we will need to create it.
    $progcompletion = $DB->get_record('prog_completion',
        array('programid' => $program->id, 'userid' => $userid, 'coursesetid' => 0));
    if (empty($progcompletion)) {
        $progcompletion = new stdClass();
        $progcompletion->programid = $programid;
        $progcompletion->userid = $userid;
        $progcompletion->coursesetid = 0;
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timestarted = 0;
        $progcompletion->timecreated = $now;
        $progcompletion->timedue = COMPLETION_TIME_NOT_SET;
        $progcompletion->timecompleted = 0;

        $createprogcompletion = true;
    } else {
        $createprogcompletion = false;
    }

    // Check that the certif_completion record doesn't already exist.
    $existingcertcompletion = $DB->get_record('certif_completion', array('certifid' => $program->certifid, 'userid' => $userid));
    if (!empty($existingcertcompletion)) {
        // If the record already exists then we don't need to create it.
        // If the matching prog_completion record does not exist then we'll create it before returning.
        if ($createprogcompletion) {
            // Check the state of the current certif_completion record.
            $existingstate = certif_get_completion_state($existingcertcompletion);

            // Change the new prog_completion record to match the existing certif_completion record.
            switch ($existingstate) {
                case CERTIFCOMPLETIONSTATE_INVALID:
                case CERTIFCOMPLETIONSTATE_ASSIGNED:
                    // Leave the prog_completion as incomplete, no timedue or timecompleted.
                    break;
                case CERTIFCOMPLETIONSTATE_CERTIFIED:
                    $progcompletion->status = STATUS_PROGRAM_COMPLETE;
                    $progcompletion->timecompleted = $existingcertcompletion->timecompleted;
                    $progcompletion->timedue = $existingcertcompletion->timeexpires;
                    break;
                case CERTIFCOMPLETIONSTATE_WINDOWOPEN:
                    $progcompletion->timedue = $existingcertcompletion->timeexpires;
                    break;
                case CERTIFCOMPLETIONSTATE_EXPIRED:
                    // Guess the time due using other history records.
                    $duesql = "SELECT MAX(timeexpires)
                                 FROM {certif_completion_history}
                                WHERE userid = :uid
                                  AND certifid = :cid
                                  AND timeexpires < :now";
                    $dueparams = array(
                        'uid' => $userid,
                        'cid' => $program->certifid,
                        'now' => $now
                    );
                    // This can't fail, because we know we've got at least one history record, but could end up invalid.
                    $progcompletion->timedue = $DB->get_field_sql($duesql, $dueparams);
                    break;
            }

            // Since the certif_completion already exists, we can't use certif_write_completion (it can only create two records
            // or update two records). Instead, we just use insert_record. This has the advantage of ignoring any validation
            // problems that might occur since we didn't check if the certif_completion record was valid.
            $DB->insert_record('prog_completion', $progcompletion);

            // Log it manually.
            certif_write_completion_log($programid, $userid,
                $message . 'Created missing prog_completion record for existing certif_completion');
        }
        return;
    }

    // Check to see if a certification completion history record exists which is marked as "unassigned".
    $sql = "SELECT *
              FROM {certif_completion_history}
             WHERE certifid = :certifid
               AND userid = :userid
               AND unassigned = 1
          ORDER BY timeexpires DESC";
    $params = array('certifid' => $program->certifid, 'userid' => $userid);
    $history = $DB->get_record_sql($sql, $params, IGNORE_MULTIPLE); // Just the latest.

    if (!empty($history)) {
        // We need to restore from history.
        $historyid = $history->id;

        // Check the state of the history record.
        $historystate = certif_get_completion_state($history);

        // Change the (new or existing) prog_completion record to match the restored certif_completion record.
        switch ($historystate) {
            case CERTIFCOMPLETIONSTATE_INVALID:
                // Shouldn't be possible. Make no changes.
                break;
            case CERTIFCOMPLETIONSTATE_ASSIGNED:
                $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
                $progcompletion->timecompleted = 0;
                // Keep the timedue if the prog_completion already exists.
                if ($createprogcompletion) {
                    $progcompletion->timedue = COMPLETION_TIME_NOT_SET;
                }
                break;
            case CERTIFCOMPLETIONSTATE_CERTIFIED:
                $progcompletion->status = STATUS_PROGRAM_COMPLETE;
                $progcompletion->timecompleted = $history->timecompleted;
                $progcompletion->timedue = $history->timeexpires;
                break;
            case CERTIFCOMPLETIONSTATE_WINDOWOPEN:
                $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
                $progcompletion->timecompleted = 0;
                $progcompletion->timedue = $history->timeexpires;
                break;
            case CERTIFCOMPLETIONSTATE_EXPIRED:
                $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
                $progcompletion->timecompleted = 0;
                if ($progcompletion->timedue <= 0) {
                    // Guess the time due using other history records.
                    $duesql = "SELECT MAX(timeexpires)
                                     FROM {certif_completion_history}
                                    WHERE userid = :uid
                                      AND certifid = :cid
                                      AND timeexpires < :now";
                    $dueparams = array(
                        'uid' => $userid,
                        'cid' => $program->certifid,
                        'now' => $now
                    );
                    // This can't fail, because we know we've got at least one history record, but could end up zero/invalid.
                    $progcompletion->timedue = $DB->get_field_sql($duesql, $dueparams);
                }
                break;
        }

        // Create the new record from the history record.
        $newcompletion = clone($history);
        $newcompletion->timemodified = $now;
        unset($newcompletion->id);
        unset($newcompletion->unassigned);

        $message .= 'Restored certif_completion history into current';

        // Manually process the records, rather than using certif_write_completion, to avoid validation problems.
        if ($createprogcompletion) {
            $DB->insert_record('prog_completion', $progcompletion);
            $message .= ' and created new prog_completion';
        } else {
            $DB->update_record('prog_completion', $progcompletion);
            $message .= ' for existing prog_completion';
        }
        $DB->insert_record('certif_completion', $newcompletion);

        // Log it manually.
        certif_write_completion_log($programid, $userid, $message);

        // Wipe all the user's unassigned flags since they're assigned now.
        $sql = "UPDATE {certif_completion_history}
                           SET unassigned = 0
                         WHERE userid = :uid
                           AND certifid = :cid";
        $params = array('uid' => $userid, 'cid' => $program->certifid);
        $DB->execute($sql, $params);

        // Delete the history record if it will be made again in the future. Done last, in case the previous step fails somehow.
        if ($historystate != CERTIFCOMPLETIONSTATE_WINDOWOPEN) {
            // The history record will be created when the window opens, so delete the existing history record.
            certif_delete_completion_history($historyid, 'Deleted certification completion history record during reassignment');
        }

    } else {
        // There is no history record suitable for reassignment, so set it up as a new, incomplete completion.

        // Construct the new record.
        $certcompletion = new stdClass();
        $certcompletion->certifid = $program->certifid;
        $certcompletion->userid = $userid;
        $certcompletion->certifpath = CERTIFPATH_CERT;
        $certcompletion->status = CERTIFSTATUS_ASSIGNED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletion->timecompleted = 0;
        $certcompletion->timewindowopens = 0;
        $certcompletion->timeexpires = 0;
        $certcompletion->baselinetimeexpires = 0;
        $certcompletion->timemodified = $now;

        if (!$createprogcompletion) {
            // Change the existing prog_completion record to match the new certif_completion record.
            $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
            $progcompletion->timecompleted = 0;
            // Don't change timedue in case it is already set.
        }

        $message .= 'Created new certif_completion';

        // Manually process the records, rather than using certif_write_completion, to avoid validation problems.
        if ($createprogcompletion) {
            $DB->insert_record('prog_completion', $progcompletion);
            $message .= ' and new prog_completion';
        } else {
            $DB->update_record('prog_completion', $progcompletion);
            $message .= ' for existing prog_completion';
        }
        $DB->insert_record('certif_completion', $certcompletion);

        // Log it manually.
        certif_write_completion_log($programid, $userid, $message);
    }
}

/**
 * Set course renewal status
 *
 * @param array $courseids
 * @param integer $userid
 * @param integer $renewalstatus
 */
function set_course_renewalstatus($courseids, $userid, $renewalstatus) {
    global $DB;

    if (!empty($courseids)) {
        list($coursesql, $courseparams) = $DB->get_in_or_equal($courseids);
        $sql = "UPDATE {course_completions}
                SET renewalstatus = ?
                WHERE userid = ? AND course {$coursesql}";

        $params = array_merge(array($renewalstatus, $userid), $courseparams);
        $DB->execute($sql, $params);
    }
}

/**
 * Checks if re-certification window is open
 * Note: does not check if expired as want user to do anytime after open
 *
 * @param integer $certificationid
 * @param integer $userid
 * @return boolean
 */
function certif_iswindowopen($certificationid, $userid) {
    global $DB;

    $timewindowopens = $DB->get_field('certif_completion', 'timewindowopens',
                                          array('certifid' => $certificationid, 'userid' =>  $userid));
    $now = time();
    if ($timewindowopens && $now > $timewindowopens) {
        return true;
    }
    return false;
}

/**
 * Find if a course exists in a certification
 *
 * @param integer $courseid
 * @param string $fields
 * @return array
 */
function find_certif_from_course($courseid, $fields='cf.id') {
    global $DB;

    // If course is in 2 coursesets - eg in cert and recert paths, then 2 records will be returned
    // for a certification, so use DISTINCT.
    $sql = "SELECT DISTINCT $fields
            FROM {course} c
            JOIN {prog_courseset_course} pcc on pcc.courseid = c.id
            JOIN {prog_courseset} pc on pc.id = pcc.coursesetid
            JOIN {prog} p on p.id = pc.programid
            JOIN {certif} cf on cf.id = p.certifid
            WHERE c.id = ?";

    $certificationrecords = $DB->get_records_sql($sql, array($courseid));

    return $certificationrecords;
}

/**
 * Find all courses associated with a certification
 *
 * @param integer $certifid
 * @param string $fields
 * @param int $path null if courses in both paths are required, else CERTIFPATH_CERT or CERTIFPATH_RECERT
 * @return array
 */
function find_courses_for_certif($certifid, $fields='c.id, c.fullname', $path = null) {
    global $DB;

    $sql = "SELECT DISTINCT $fields
            FROM {certif} cf
            JOIN {prog} p on p.certifid = cf.id
            JOIN {prog_courseset} pc on pc.programid = p.id
            JOIN {prog_courseset_course} pcc on pcc.coursesetid = pc.id
            JOIN {course} c on c.id = pcc.courseid
            WHERE cf.id = ? ";
    $params = array($certifid);

    if ($path != null) {
        $sql .= " AND pc.certifpath = ?";
        $params[] = $path;
    }

    $certificationrecords = $DB->get_records_sql($sql, $params);

    return $certificationrecords;
}

/**
 * Send message defined in program_message.class.php to user
 * and also to manager if specified in settings
 *
 * @param integer $userid
 * @param integer $progid
 * @param integer $msgtype
 */
function send_certif_message($progid, $userid, $msgtype) {
    global $DB;

    $user = $DB->get_record('user', array('id' => $userid));
    $messagesmanager = prog_messages_manager::get_program_messages_manager($progid);
    $messages = $messagesmanager->get_messages();

    $params = array('contextlevel' => CONTEXT_PROGRAM, 'progid' => $progid, 'userid' => $userid);

    // Take into account the visiblity of the certification before sending messages.
    list($visibilitysql, $visibilityparams) = totara_visibility_where(null, 'p.id', 'p.visible',
        'p.audiencevisible', 'p', 'certification');
    $params = array_merge($params, $visibilityparams);

    $now = time();
    $certif = $DB->get_record_sql("SELECT cc.*
                                   FROM {certif_completion} cc
                                   JOIN {prog} p ON p.certifid = cc.certifid
                                   JOIN {context} ctx ON p.id = ctx.instanceid AND contextlevel =:contextlevel
                                   WHERE p.id =:progid AND cc.userid =:userid AND {$visibilitysql}", $params);
    // If messagetype set up for this program, send notifications to user and the user's manager (if set on message).
    foreach ($messages as $message) {
        if ($message->messagetype == $msgtype) {
            if ($msgtype == MESSAGETYPE_RECERT_WINDOWDUECLOSE) {
                // ONLY send the ones that are due.
                if($now > ($certif->timeexpires - $message->triggertime) && $now < $certif->timeexpires) {
                    $sent = $DB->get_records('prog_messagelog', array('messageid' => $message->id, 'userid' => $userid));
                    // DON'T send them more than once.
                    if(empty($sent)) {
                        $message->send_message($user);
                    }
                }
            } else {
               $message->send_message($user); // Prog_eventbased_message.send_message() program_message.class.php.
               // This function checks prog_messagelog for existing record, checking messageid and userid (and coursesetid(=0))
               // messageid is id of message in prog_message (ie for this prog and message type)
               // if exists, the message is not sent.
            }
        }
    }
}

/**
 * Get current certifpath of user for given certification
 *
 * @param integer $certificationid ID of certification to check
 * @param integer $userid User Id to find certification path for
 * @return integer Current path of given user on certification
 */
function get_certification_path_user($certificationid, $userid) {
    global $DB;

    if (empty($certificationid)) {
        // This is not a certification.
        return CERTIFPATH_STD;
    }

    $certifpath = $DB->get_field('certif_completion', 'certifpath', array('certifid' => $certificationid, 'userid' => $userid));

    if ($certifpath) {
        return $certifpath;
    } else {
        // Check if the user has a prog_completion (course set = 0) record.
        $programid = $DB->get_field('prog', 'id', array('certifid' => $certificationid));
        $sql = "SELECT pc.id
                  FROM {prog_completion} pc
                  JOIN {prog_user_assignment} pua
                    ON pua.userid = pc.userid AND pua.programid = pc.programid
                 WHERE pc.programid = :programid AND pc.userid = :userid AND pc.coursesetid = :coursesetid";
        if (!$DB->record_exists_sql($sql, array('programid' => $programid, 'userid' => $userid, 'coursesetid' => 0))) {
            // There is no prog_completion, so they are not assigned to this certification, thus not on any path.
            return CERTIFPATH_UNSET;
        }

        // There is a prog_completion record but no matching certif_completion record, so create it.
        certif_create_completion($programid, $userid);

        // Create missing prog_completion (course set != 0) records for completed course sets.
        $programid = $DB->get_field('prog', 'id', array('certifid' => $certificationid));
        $program = new program($programid);
        prog_update_completion($userid, $program);

        debugging("!WARNING! certif_completion record was missing for user id {$userid}, certification id {$certificationid}.\n" .
                  "This indicates that a problem occurred during user assignment. The missing record has been created.\n" .
                  "Contact Totara support staff if this problem persists. !WARNING!");
        return CERTIFPATH_CERT;
    }
}

/**
 * Read from formdata and get the certifpath based on matching a given fromfield and value
 *   [certifpath_rc] => 2
 *   [setprefixes_rc] =>
 *   [contenttype_rc] => 1
 *   [addcontent_rc] => Add
 *   field = 'addcontent' and fieldvalue = 'Add' would return 2
 * @param StdClass $formdata
 * @return int CERTIFPATH constant
 */
function get_certification_path_field($formdata, $field, $fieldvalue) {
    foreach (array('_ce', '_rc') as $suffix) {
        if (!isset($formdata->{$field.$suffix})
            || empty($formdata->{$field.$suffix})
            || $formdata->{$field.$suffix} != $fieldvalue) {
            continue;
        } else {
            return $formdata->{'certifpath'.$suffix};
        }
    }
    return null;
}

/**
 * A list of certifications that match a search
 *
 * @uses $DB, $USER
 * @param array $searchterms Words to search for in an array
 * @param string $sort Sort sql
 * @param int $page The results page to return
 * @param int $recordsperpage Number of search results per page
 * @param int $totalcount Passed in by reference. Total count so we can calculate number of pages
 * @param string $whereclause Addition where clause
 * @param array $whereparams Parameters needed for $whereclause
 * @return object {@link $COURSE} records
 */
// TODO: Fix this function to work in Moodle 2 way
// See lib/datalib.php -> get_courses_search for example.
function certif_get_certifications_search($searchterms, $sort='fullname ASC', $page=0, $recordsperpage=50, &$totalcount,
                                                                                                $whereclause, $whereparams) {
    global $DB, $USER;

    $regexp    = $DB->sql_regex(true);
    $notregexp = $DB->sql_regex(false);

    $fullnamesearch = '';
    $summarysearch = '';
    $idnumbersearch = '';
    $shortnamesearch = '';

    $fullnamesearchparams = array();
    $summarysearchparams = array();
    $idnumbersearchparams = array();
    $shortnamesearchparams = array();
    $params = array();

    foreach ($searchterms as $searchterm) {
        if ($fullnamesearch) {
            $fullnamesearch .= ' AND ';
        }
        if ($summarysearch) {
            $summarysearch .= ' AND ';
        }
        if ($idnumbersearch) {
            $idnumbersearch .= ' AND ';
        }
        if ($shortnamesearch) {
            $shortnamesearch .= ' AND ';
        }

        if (substr($searchterm, 0, 1) == '+') {
            $searchterm      = substr($searchterm, 1);
            $summarysearch  .= " summary $regexp '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
            $fullnamesearch .= " fullname $regexp '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
            $idnumbersearch  .= " idnumber $regexp '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
            $shortnamesearch  .= " shortname $regexp '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
        } else if (substr($searchterm, 0, 1) == "-") {
            $searchterm      = substr($searchterm, 1);
            $summarysearch  .= " summary $notregexp '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
            $fullnamesearch .= " fullname $notregexp '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
            $idnumbersearch .= " idnumber $notregexp '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
            $shortnamesearch .= " shortname $notregexp '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
        } else {
            $summarysearch .= $DB->sql_like('summary', '?', false, true, false) . ' ';
            $summarysearchparams[] = '%' . $searchterm . '%';

            $fullnamesearch .= $DB->sql_like('fullname', '?', false, true, false) . ' ';
            $fullnamesearchparams[] = '%' . $searchterm . '%';

            $idnumbersearch .= $DB->sql_like('idnumber', '?', false, true, false) . ' ';
            $idnumbersearchparams[] = '%' . $searchterm . '%';

            $shortnamesearch .= $DB->sql_like('shortname', '?', false, true, false) . ' ';
            $shortnamesearchparams[] = '%' . $searchterm . '%';
        }
    }

    // If search terms supplied, include in where.
    if (count($searchterms)) {
        $where = "
            WHERE (( $fullnamesearch ) OR ( $summarysearch ) OR ( $idnumbersearch ) OR ( $shortnamesearch ))
            AND category > 0
        ";
        $params = array_merge($params, $fullnamesearchparams, $summarysearchparams, $idnumbersearchparams, $shortnamesearchparams);
    } else {
        // Otherwise return everything.
        $where = " WHERE category > 0 ";
    }

    // Add any additional sql supplied to where clause.
    if ($whereclause) {
        $where .= " AND {$whereclause}";
        $params = array_merge($params, $whereparams);
    }

    // See also certif_get_certifications_page.
    $sql = "SELECT cf.id, cf.learningcomptype
             ,p.id as pid,p.fullname,p.visible,p.category,p.icon,p.available,p.availablefrom,p.availableuntil
            ,ctx.id AS ctxid, ctx.path AS ctxpath
            ,ctx.depth AS ctxdepth, ctx.contextlevel AS ctxlevel
            FROM {certif} cf
            JOIN {prog} p ON p.certifid = cf.id AND cf.learningcomptype=".CERTIFTYPE_PROGRAM."
            JOIN {context} ctx ON (p.id = ctx.instanceid AND ctx.contextlevel = ".CONTEXT_PROGRAM.")
            $where
            ORDER BY " . $sort;

    $certifications = array();

    $limitfrom = $page * $recordsperpage;
    $limitto   = $limitfrom + $recordsperpage;
    $c = 0; // Counts how many visible certifications we've seen.

    $rs = $DB->get_recordset_sql($sql, $params);

    foreach ($rs as $certification) {
        if (!is_siteadmin($USER->id)) {
            // Check if this certification is not available, if it's not then deny access.
            if ($certification->available == 0) {
                continue;
            }

            $now = time();

            // Check if the certificationme isn't accessible yet.
            if ($certification->availablefrom > 0 && $certification->availablefrom > $now) {
                continue;
            }

            // Check if the certificationme isn't accessible anymore.
            if ($certification->availableuntil > 0 && $certification->availableuntil < $now) {
                continue;
            }
        }

        if ($certification->visible || has_capability('totara/certification:viewhiddencertifications',
                                                                    program_get_context($certification->pid))) {
            // Don't exit this loop till the end
            // we need to count all the visible courses
            // to update $totalcount.
            if ($c >= $limitfrom && $c < $limitto) {
                $certifications[] = $certification;
            }
            $c++;
        }
    }

    $rs->close();

    // Update total count for pass-by-reference variable.
    $totalcount = $c;
    return $certifications;
}

/**
 * Returns list of certifications, for whole site, or category
 * (This is the counterpart to get_courses_page in /lib/datalib.php)
 *
 * Similar to certif_get_certifications, but allows paging
 * @param int $categoryid
 * @param string $sort
 * @param string $fields
 * @param int $totalcount
 * @param int $limitfrom
 * @param int $limitnum
 *
 * @return object list of visible certifications
 */
function certif_get_certifications_page($categoryid="all", $sort="sortorder ASC",
                          $fields="p.id as pid,p.sortorder,p.shortname,p.fullname,p.summary,p.visible",
                          &$totalcount, $limitfrom="", $limitnum="") {

    global $DB;

    $params = array(CONTEXT_PROGRAM);
    $categoryselect = "";
    if ($categoryid != "all" && is_numeric($categoryid)) {
        $categoryselect = " WHERE p.category = ? ";
        $params[] = $categoryid;
    }

    // Pull out all certification-programs matching the category.
    $visiblecertifications = array();

    // Add audience visibility setting.
    list($visibilitysql, $visibilityparams) = totara_visibility_where(null, 'p.id', 'p.visible',
        'p.audiencevisible', 'p', 'certification');
    $params = array_merge($params, $visibilityparams);

    $certifselect = "SELECT $fields, 'certification' AS listtype,
                          ctx.id AS ctxid, ctx.path AS ctxpath,
                          ctx.depth AS ctxdepth, ctx.contextlevel AS ctxlevel
                     FROM {certif} cf
                     JOIN {prog} p ON (p.certifid = cf.id)
                     JOIN {context} ctx ON (p.id = ctx.instanceid AND ctx.contextlevel = ?)
                     {$categoryselect} AND {$visibilitysql}
                     ORDER BY {$sort}";

    $rs = $DB->get_recordset_sql($certifselect, $params);

    $totalcount = 0;

    if (!$limitfrom) {
        $limitfrom = 0;
    }

    // Iteration will have to be done inside loop to keep track of the limitfrom and limitnum.
    foreach ($rs as $certification) {
        $totalcount++;
        if ($totalcount > $limitfrom && (!$limitnum or count($visiblecertifications) < $limitnum)) {
            $visiblecertifications [] = $certification;
        }
    }

    $rs->close();

    return $visiblecertifications;
}

/**
 * Get progress bar for ROL etc
 *
 * @param integer $certificationcompletionid
 * @return string Markup for producing a progress bar
 */
function certification_progress($certificationcompletionid) {
    global $DB, $PAGE;

    $certificationcompletion = $DB->get_record('certif_completion', array('id' => $certificationcompletionid),
                                                'status, renewalstatus');

    if ($certificationcompletion->status == CERTIFSTATUS_INPROGRESS) {
        // In progress.
        $overall_progress = 50;
    } else if ($certificationcompletion->status == CERTIFSTATUS_COMPLETED
                    && $certificationcompletion->renewalstatus != CERTIFRENEWALSTATUS_DUE) {
        // Completed and not due for renewal.
        $overall_progress = 100;
    } else {
        // Assume its assigned & due or overdue.
        $overall_progress = 0;
    }

    $tooltipstr = 'DEFAULTTOOLTIP';

    // Get relevant progress bar and return for display.
    $renderer = $PAGE->get_renderer('totara_core');
    return $renderer->progressbar($overall_progress, 'medium', false, $tooltipstr);
}

/**
 * (This is the counterpart to print_courses in /course/lib.php)
 *
 * Prints non-editing view of certifs in a category
 *
 * @global $CFG
 * @global $USER
 * @param int|object $category
 */
function certif_print_certifications($category) {
    // Category is 0 (for all certifications) or an object.
    global $OUTPUT, $USER;

    $fields = "cf.id,cf.learningcomptype,p.sortorder,p.shortname,p.fullname,p.summary,p.visible,
               p.available,p.availablefrom,p.availableuntil,p.icon,p.certifid,p.id as pid";

    if (!is_object($category) && $category==0) {
        $categories = get_child_categories(0);  // Parent = 0  ie top-level categories only.
        if (is_array($categories) && count($categories) == 1) {
            $category = array_shift($categories);
            $certifications = certif_get_certifications($category->id, 'p.sortorder ASC', $fields);
        } else {
            $certifications = certif_get_certifications('all', 'p.sortorder ASC', $fields);
        }
        unset($categories);
    } else {
        $certifications = certif_get_certifications($category->id, 'p.sortorder ASC', $fields);
    }

    if ($certifications) {
        foreach ($certifications as $certification) {
            certif_print_certification($certification);
        }
    } else {
        echo $OUTPUT->heading(get_string('nocertifications', 'totara_certification'));
        $context = context_system::instance();
        if (has_capability('totara/certification:createcertification', $context)) {
            $options = array();
            $options['category'] = $category->id;
            echo html_writer::start_tag('div', array('class' => 'addcertificationbutton'));
            echo $OUTPUT->single_button(new moodle_url('/totara/certification/add.php', $options), get_string("addnewcertification", 'totara_certification'), 'get');
            echo html_writer::end_tag('div');
        }
    }
}

/**
 * Print a description of a certification, suitable for browsing in a list.
 * (This is the counterpart to print_course in /course/lib.php)
 *
 * @param object $certification the certification object.
 * @param string $highlightterms (optional) some search terms that should be highlighted in the display.
 */
function certif_print_certification($certification, $highlightterms = '') {
    global $PAGE, $CERTIFTYPE;

    $accessible = false;
    if (prog_is_accessible($certification)) {
        $accessible = true;
    }

    if (isset($certification->context)) {
        $context = $certification->context;
    } else {
        $context = context_program::instance($certification->pid);
    }

    // Object for all info required by renderer.
    $data = new stdClass();

    $data->accessible = $accessible;
    $data->visible = $certification->visible;
    $data->icon = (empty($certification->icon)) ? 'default' : $certification->icon;
    $data->progid = $certification->pid;
    $data->certifid = $certification->id;
    $data->learningcomptypestr = get_string($CERTIFTYPE[$certification->learningcomptype], 'totara_certification');
    $data->fullname = $certification->fullname;
    $data->summary = file_rewrite_pluginfile_urls($certification->summary, 'pluginfile.php',
        context_program::instance($certification->pid)->id, 'totara_program', 'summary', 0);
    $data->highlightterms = $highlightterms;

    $renderer = $PAGE->get_renderer('totara_certification');
    echo $renderer->print_certification($data);
}

/**
 * Returns list of certifications, for whole site, or category
 * (This is the counterpart to get_courses in /lib/datalib.php)
 */
function certif_get_certifications($categoryid="all", $sort="cf.sortorder ASC", $fields="cf.*") {
    global $CFG, $DB;

    $params = array('contextlevel' => CONTEXT_PROGRAM);
    if ($categoryid != "all" && is_numeric($categoryid)) {
        $categoryselect = "WHERE p.category = :category";
        $params['category'] = $categoryid;
    } else {
        $categoryselect = "";
    }

    if (empty($sort)) {
        $sortstatement = "";
    } else {
        $sortstatement = "ORDER BY $sort";
    }

    // Add audience visibility setting.
    list($visibilityjoinsql, $visibilityjoinparams) = totara_visibility_join(null, 'certification', 'p');
    $params = array_merge($params, $visibilityjoinparams);

    // Get context data for preload.
    $ctxfields = context_helper::get_preload_record_columns_sql('ctx');
    $ctxjoin = "LEFT JOIN {context} ctx ON (ctx.instanceid = p.id AND ctx.contextlevel = :contextlevel)";
    $params['contextlevel'] = CONTEXT_PROGRAM;

    // Pull out all certifications matching the category
    // the program join effectively removes programs which
    // are not certification-programs.
    $certifications = $DB->get_records_sql("SELECT {$fields}, {$ctxfields}, visibilityjoin.isvisibletouser
                        FROM {certif} cf
                        JOIN {prog} p ON (p.certifid = cf.id)
                             {$visibilityjoinsql}
                             {$ctxjoin}
                        {$categoryselect}
                        {$sortstatement}", $params
                    );

    // Remove certifications that aren't visible.
    foreach ($certifications as $id => $cert) {
        if ($cert->isvisibletouser) {
            unset($cert->isvisibletouser);
        } else {
            context_helper::preload_from_record($cert);
            $context = context_program::instance($id);
            if (has_capability('totara/certification:viewhiddencertifications', $context) ||
                !empty($CFG->audiencevisibility) && has_capability('totara/coursecatalog:manageaudiencevisibility', $context)) {
                unset($cert->isvisibletouser);
            } else {
                unset($certifications[$id]);
            }
        }
    }

    return $certifications;
}

/**
 * Reset the component records of the certification so user can take the certification-program again
 *
 * @param StdClass $certifcompletion
 * @param array $courses
 */
function reset_certifcomponent_completions($certifcompletion, $courses=null) {
    global $DB;

    $certificationid = $certifcompletion->certifid;
    $userid = $certifcompletion->userid;

    $transaction = $DB->start_delegated_transaction();

    // Program completion.
    // If the coursesetid is 0 then its a program completion record otherwise its a courseset completion.
    $prog = $DB->get_record('prog', array('certifid' => $certificationid));

    // Set program completion main record first.
    if ($pcp = $DB->get_record('prog_completion', array('programid' => $prog->id, 'userid' => $userid, 'coursesetid' => 0))) {
        $pcp->status = STATUS_PROGRAM_INCOMPLETE;
        // Don't set timestarted, as this reflects when the user was first assigned.
        // Don't set timedue, as this was set when the user certified.
        $pcp->timecompleted = 0;
        $DB->update_record('prog_completion', $pcp);
    } else {
        print_error('error:missingprogcompletion', 'totara_certification', '', $certifcompletion);
    }

    certif_write_completion_log($prog->id, $userid,
        'Window opened, prog_completion updated, course and activity completion will be archived (step 2 of 2)'
    );

    // This clears both courseset paths as could end up having to
    // do certification path if recertification expires.
    // Note: historic import does not have prog_completion records where coursesetid is not 0.
    $sql = "DELETE FROM {prog_completion}
        WHERE programid = ?
            AND userid = ?
            AND coursesetid <> 0";
    $DB->execute($sql, array($prog->id, $userid));

    // Course_completions (get list of courses if not done in calling function).
    // Note: course_completion.renewalstatus is set to due at this point - would need to add that flag to cc processing
    // if not deleting record?
    if ($courses == null) {
        $courses = find_courses_for_certif($certificationid, 'c.id'); // All paths.
    }
    $courseids = array_keys($courses);

    foreach ($courseids as $courseid) {
        // Call course/lib.php functions.
        archive_course_completion($userid, $courseid);
        archive_course_activities($userid, $courseid, $certifcompletion->windowopens);
    }

    // Remove mesages for prog&user so we can resend them.
    certif_delete_messagelog($prog->id, $userid, MESSAGETYPE_PROGRAM_COMPLETED);
    certif_delete_messagelog($prog->id, $userid, MESSAGETYPE_PROGRAM_DUE);
    certif_delete_messagelog($prog->id, $userid, MESSAGETYPE_PROGRAM_OVERDUE);
    certif_delete_messagelog($prog->id, $userid, MESSAGETYPE_COURSESET_DUE);
    certif_delete_messagelog($prog->id, $userid, MESSAGETYPE_COURSESET_OVERDUE);
    certif_delete_messagelog($prog->id, $userid, MESSAGETYPE_COURSESET_COMPLETED);
    certif_delete_messagelog($prog->id, $userid, MESSAGETYPE_RECERT_WINDOWOPEN);
    certif_delete_messagelog($prog->id, $userid, MESSAGETYPE_RECERT_WINDOWDUECLOSE);
    certif_delete_messagelog($prog->id, $userid, MESSAGETYPE_RECERT_FAILRECERT);
    certif_delete_messagelog($prog->id, $userid, MESSAGETYPE_LEARNER_FOLLOWUP);

    $transaction->allow_commit();
}

/**
 * Delete certification records
 *
 * @param integer $learningcomptype
 * @param integer $certifid
 */
function certif_delete($learningcomptype, $certifid) {
    global $DB, $CERTIFTYPE;

    $transaction = $DB->start_delegated_transaction();

    $DB->delete_records('certif', array('id' => $certifid));
    $DB->delete_records('certif_completion', array('certifid' => $certifid));
    $DB->delete_records('certif_completion_history', array('certifid' => $certifid));

    $transaction->allow_commit();
}

/**
 * Deletes selected records in the message log so a repeat message can be sent if required,
 * (send_message() will suppress otherwise)
 *
 * @param integer $progid
 * @param integer $userid
 * @param integer $messagetype
 */
function certif_delete_messagelog($progid, $userid, $messagetype) {
    global $DB;

    // Get all the messages that match the given params.
    $params = array(
        'uid' => $userid,
        'pid' => $progid,
        'mtype' => $messagetype,
    );
    $sql = "SELECT DISTINCT pml.id
            FROM {prog_messagelog} pml
            JOIN {prog_message} pm ON pm.id = pml.messageid AND pm.programid = :pid AND pm.messagetype = :mtype
            WHERE pml.userid = :uid";
    $messages = $DB->get_recordset_sql($sql, $params);

    // Put them into an array of ids for the sql statement.
    $todelete = array();
    foreach ($messages as $message) {
        $todelete[] = $message->id;
    }
    $messages->close();

    // And delete them.
    if (!empty($todelete)) {
        list($deletesql, $deleteparams) = $DB->get_in_or_equal($todelete, SQL_PARAMS_NAMED, 'd', true);
        $DB->delete_records_select('prog_messagelog', 'id ' . $deletesql, $deleteparams);
    }
}

/**
 * Get the time the re-certification is estimated from:
 * - if using CERTIFRECERT_COMPLETION then the actual time of completion.
 * - if using CERTIFRECERT_EXPIRY then timeexpires, timedue or timecompleted (the first that is set),
 * - if using CERTIFRECERT_FIXED then based on timeexpires, timedue or timecompleted (the first that is set) and
 *   bumped forward repeatedly by active period until at least minimum active period into the future.
 * The new time expires will be calculated as one active period after the base.
 *
 * @param integer $recertifydatetype
 * @param integer $timeexpires
 * @param integer $timecompleted
 * @param integer $timedue
 * @param string $activeperiod
 * @param string $minimumactiveperiod
 * @param string $windowperiod
 * @return integer
 */
function get_certiftimebase($recertifydatetype, $timeexpires, $timecompleted, $timedue, $activeperiod, $minimumactiveperiod,
                            $windowperiod) {
    if ($recertifydatetype == CERTIFRECERT_COMPLETION) {
        return $timecompleted;

    } else if ($recertifydatetype == CERTIFRECERT_EXPIRY) {
        if ($timeexpires > 0 and $timecompleted > $timeexpires) { // Overdue for recertification.
            return $timecompleted;

        } else if ($timeexpires > 0 and get_timewindowopens($timeexpires, $windowperiod) > $timecompleted) {
            // Recertified before the current window has opened, base is one active period before the time expires.
            return strtotime('-' . $activeperiod, $timeexpires);

        } else if ($timeexpires > 0) { // Recertified on time.
            return $timeexpires;

        } else if ($timedue > 0 and $timecompleted > $timedue) { // Overdue for primary certification.
            return $timecompleted;

        } else if ($timedue > 0) { // Certified on time.
            return $timedue;

        } else { // Primary certification, no due date set.
            return $timecompleted;
        }

    } else if ($recertifydatetype == CERTIFRECERT_FIXED) {
        if ($timeexpires > 0) { // Recertifying.
            $base = $timeexpires;

        } else if ($timedue > 0) { // Primary certification, assignment due date set.
            $base = $timedue;

        } else { // Primary certification, no assignment due date set.
            $base = $timecompleted;
        }
        if (strtotime($activeperiod, 0) <= 0) {
            // Invalid active period. Stop now, because the following code would cause an infinite loop.
            print_error('error:nullactiveperiod', 'totara_certification');
        }
        // First, if the base is too far in the future, move it back (only usually occurs with primary certification).
        while (strtotime('-' . $minimumactiveperiod, $base) >= $timecompleted) {
            $base = strtotime('-' . $activeperiod, $base);
        }
        // Then, if the base is too far in the past, move it forward (can occur with near primary certification or very overdue).
        while (strtotime($activeperiod, $base) < strtotime($minimumactiveperiod, $timecompleted)) {
            $base = strtotime($activeperiod, $base);
        }
        return $base;
    }
}

/**
 * Work out the certification expiry time.
 *
 * @param integer $base (from get_certiftimebase())
 * @param string $activeperiod (relative time string)
 * @return integer
 */
function get_timeexpires($base, $activeperiod) {
    if (empty($activeperiod)) {
        print_error('error:nullactiveperiod', 'totara_certification');
    }
    return strtotime($activeperiod, $base);
}


/**
 * Work out the window open time
 *
 * @param integer $timeexpires
 * @param string $windowperiod (relative time string)
 * @return integer
 */
function get_timewindowopens($timeexpires, $windowperiod) {
    if (empty($windowperiod)) {
        print_error('error:nullwindowperiod', 'totara_certification');
    }
    return strtotime('-'.$windowperiod, $timeexpires);
}

/**
 * Can the current user delete certifications in this category?
 *
 * @param int $categoryid
 * @return boolean
 */
function certif_can_delete_certifications($categoryid) {
    global $DB;

    $context = context_coursecat::instance($categoryid);
    $sql = context_helper::get_preload_record_columns_sql('ctx');
    $programcontexts = $DB->get_records_sql('SELECT ctx.instanceid AS progid, ' .
                    $sql . ' FROM {context} ctx ' .
                    'JOIN {prog} p ON ctx.instanceid = p.id ' .
                    'WHERE ctx.path like :pathmask AND ctx.contextlevel = :programlevel AND p.certifid IS NOT NULL',
                    array('pathmask' => $context->path. '/%', 'programlevel' => CONTEXT_PROGRAM));
    foreach ($programcontexts as $ctxrecord) {
        context_helper::preload_from_record($ctxrecord);
        $programcontext = context_program::instance($ctxrecord->progid);
        if (!has_capability('totara/certification:deletecertification', $programcontext)) {
            return false;
        }
    }

    return true;
}

/**
 * Returns true if the category has certifications in it
 * (count does not include child categories)
 *
 * @param coursecat $category
 * @return bool
 */
function certif_has_certifications($category) {
    global $DB;
    return $DB->record_exists_sql("SELECT 1 FROM {prog} WHERE category = :category AND certifid IS NOT NULL",
                    array('category' => $category->id));
}

/**
 * Processes completion data submitted by an admin - transforms it to look like certification and program completion
 * records, suitable for use in $DB->update_record() or being checked by certif_get_completion_state().
 *
 * Note that the prog_completion and certif_completion records must already exist in the database (matching the
 * user and program id supplied), and their record ids will be included in the returned data. Creating new completion
 * records should be achieved automatically by assigning a user to a certification, not manually in a form.
 *
 * @param object $submitted contains the data submitted by the form
 * @return array(object $certcompletion, object $progcompletion) compatible with the corresponding database records
 */
function certif_process_submitted_edit_completion($submitted) {
    global $DB;

    // Get existing records ids (double-checks that everything is valid).
    $sql = "SELECT cc.id AS ccid, cc.certifid, pc.id AS pcid
              FROM {certif_completion} cc
              JOIN {prog} prog
                ON cc.certifid = prog.certifid
              JOIN {prog_completion} pc
                ON pc.programid = prog.id AND cc.userid = pc.userid AND pc.coursesetid = 0
             WHERE pc.programid = :programid AND cc.userid = :userid";
    $params = array('programid' => $submitted->id, 'userid' => $submitted->userid);
    $existingrecords = $DB->get_record_sql($sql, $params);

    if (empty($existingrecords)) {
        print_error(get_string('error:impossibledatasubmitted', 'totara_program'));
    }

    $now = time();

    $certcompletion = new stdClass();
    $certcompletion->id = $existingrecords->ccid;
    $certcompletion->certifid = $existingrecords->certifid;
    $certcompletion->userid = $submitted->userid;
    $certcompletion->status = $submitted->status;
    $certcompletion->renewalstatus = $submitted->renewalstatus;
    $certcompletion->certifpath = $submitted->certifpath;
    $certcompletion->timecompleted = $submitted->timecompleted;
    $certcompletion->timewindowopens = $submitted->timewindowopens;
    $certcompletion->timeexpires = $submitted->timeexpires;
    $certcompletion->baselinetimeexpires = $submitted->baselinetimeexpires;
    $certcompletion->timemodified = $now;

    $progcompletion = new stdClass();
    $progcompletion->id = $existingrecords->pcid;
    $progcompletion->programid = $submitted->id;
    $progcompletion->userid = $submitted->userid;
    $progcompletion->status = $submitted->progstatus;
    // Fix stupid timedue should be -1 for not set problem.
    $progcompletion->timedue = ($submitted->timeduenotset === 'yes') ? COMPLETION_TIME_NOT_SET : $submitted->timedue;
    $progcompletion->timecompleted = $submitted->progtimecompleted;
    $progcompletion->timemodified = $now;

    return array($certcompletion, $progcompletion);
}

/**
 * Processes completion history data submitted by an admin - transforms it to look like certification completion
 * record, suitable for use in $DB->update_record() or being checked by certif_get_completion_state().
 *
 * @param object $submitted contains the data submitted by the form
 * @return object $certcompletion compatible with the corresponding database record
 */
function certif_process_submitted_edit_completion_history($submitted) {
    global $DB;

    if ($submitted->chid) {
        // Get existing record id (double-checks that everything is valid).
        $sql = "SELECT cch.certifid
                  FROM {certif_completion_history} cch
                  JOIN {prog} prog
                    ON cch.certifid = prog.certifid
                 WHERE cch.id = :chid AND prog.id = :programid AND cch.userid = :userid";
        $params = array('chid' => $submitted->chid, 'programid' => $submitted->id, 'userid' => $submitted->userid);
        $certifid = $DB->get_field_sql($sql, $params);

        if (empty($certifid)) {
            print_error(get_string('error:impossibledatasubmitted', 'totara_program'));
        }
    } else {
        $certifid = $DB->get_field('prog', 'certifid', array('id' => $submitted->id));
    }

    $now = time();

    $certcompletion = new stdClass();
    $certcompletion->id = $submitted->chid;
    $certcompletion->certifid = $certifid;
    $certcompletion->userid = $submitted->userid;
    $certcompletion->status = $submitted->status;
    $certcompletion->renewalstatus = $submitted->renewalstatus;
    $certcompletion->certifpath = $submitted->certifpath;
    $certcompletion->timecompleted = $submitted->timecompleted;
    $certcompletion->timewindowopens = $submitted->timewindowopens;
    $certcompletion->timeexpires = $submitted->timeexpires;
    $certcompletion->baselinetimeexpires = $submitted->baselinetimeexpires;
    $certcompletion->timemodified = $now;
    $certcompletion->unassigned = $submitted->unassigned;

    return $certcompletion;
}

/**
 * Given the new and old state of a record and the new certification completion record itself, determines what
 * results the user can expect to see and what actions will occur when cron next processes the record.
 *
 * @param int $originalstate CERTIFCOMPLETIONSTATE_XXXX
 * @param int $newstate CERTIFCOMPLETIONSTATE_XXX
 * @param object $newcertcompletion like a record in certif_completion (not all fields are required)
 * @return array(array $userresults, array $cronresults)
 */
function certif_get_completion_change_consequences($originalstate, $newstate, $newcertcompletion) {
    $userresults = array();
    $cronresults = array();

    switch ($originalstate) {
        case CERTIFCOMPLETIONSTATE_INVALID:
            switch ($newstate) {
                case CERTIFCOMPLETIONSTATE_ASSIGNED:
                    break;
                case CERTIFCOMPLETIONSTATE_CERTIFIED:
                    break;
                case CERTIFCOMPLETIONSTATE_WINDOWOPEN:
                    break;
                case CERTIFCOMPLETIONSTATE_EXPIRED:
                    break;
            }
            break;
        case CERTIFCOMPLETIONSTATE_ASSIGNED:
            switch ($newstate) {
                case CERTIFCOMPLETIONSTATE_ASSIGNED:
                    // No change.
                    break;
                case CERTIFCOMPLETIONSTATE_CERTIFIED:
                    $userresults[] = 'completionchangeuserpathcerttorecert';
                    $userresults[] = 'completionchangeusernotdue';
                    break;
                case CERTIFCOMPLETIONSTATE_WINDOWOPEN:
                    $userresults[] = 'completionchangeuserpathcerttorecert';
                    $userresults[] = 'completionchangeusercoursesnotreset';
                    $userresults[] = 'completionchangeusercompletionnotarchived';
                    break;
                case CERTIFCOMPLETIONSTATE_EXPIRED:
                    $userresults[] = 'completionchangeusercoursesnotreset';
                    $userresults[] = 'completionchangeusercompletionnotarchived';
                    break;
            }
            break;
        case CERTIFCOMPLETIONSTATE_CERTIFIED:
            switch ($newstate) {
                case CERTIFCOMPLETIONSTATE_ASSIGNED:
                    $userresults[] = 'completionchangeuserpathrecerttocert';
                    $userresults[] = 'completionchangeuserdue';
                    $userresults[] = 'completionchangeuserenableextensions';
                    $userresults[] = 'completionchangeusercoursesetreset';
                    break;
                case CERTIFCOMPLETIONSTATE_CERTIFIED:
                    // No change.
                    break;
                case CERTIFCOMPLETIONSTATE_WINDOWOPEN:
                    $userresults[] = 'completionchangeusercoursesnotreset';
                    $userresults[] = 'completionchangeusercompletionnotarchived';
                    $userresults[] = 'completionchangeuserdue';
                    $userresults[] = 'completionchangeusercoursesetreset';
                    break;
                case CERTIFCOMPLETIONSTATE_EXPIRED:
                    $userresults[] = 'completionchangeuserpathrecerttocert';
                    $userresults[] = 'completionchangeusercoursesnotreset';
                    $userresults[] = 'completionchangeusercompletionnotarchived';
                    $userresults[] = 'completionchangeuserdue';
                    $userresults[] = 'completionchangeusercoursesetreset';
                    break;
            }
            break;
        case CERTIFCOMPLETIONSTATE_WINDOWOPEN:
            switch ($newstate) {
                case CERTIFCOMPLETIONSTATE_ASSIGNED:
                    $userresults[] = 'completionchangeuserpathrecerttocert';
                    $userresults[] = 'completionchangeuserenableextensions';
                    break;
                case CERTIFCOMPLETIONSTATE_CERTIFIED:
                    $userresults[] = 'completionchangeusernotdue';
                    $userresults[] = 'completionchangeusercoursesreset';
                    $userresults[] = 'completionchangeusercompletionarchived';
                    break;
                case CERTIFCOMPLETIONSTATE_WINDOWOPEN:
                    // No change.
                    break;
                case CERTIFCOMPLETIONSTATE_EXPIRED:
                    $userresults[] = 'completionchangeuserpathrecerttocert';
                    break;
            }
            break;
        case CERTIFCOMPLETIONSTATE_EXPIRED:
            switch ($newstate) {
                case CERTIFCOMPLETIONSTATE_ASSIGNED:
                    $userresults[] = 'completionchangeuserenableextensions';
                    break;
                case CERTIFCOMPLETIONSTATE_CERTIFIED:
                    $userresults[] = 'completionchangeusernotdue';
                    $userresults[] = 'completionchangeusercoursesreset';
                    $userresults[] = 'completionchangeusercompletionarchived';
                    $userresults[] = 'completionchangeuserpathcerttorecert';
                    break;
                case CERTIFCOMPLETIONSTATE_WINDOWOPEN:
                    $userresults[] = 'completionchangeuserpathcerttorecert';
                    break;
                case CERTIFCOMPLETIONSTATE_EXPIRED:
                    // No change.
                    break;
            }
            break;
    }

    $now = time();

    switch ($newstate) {
        case CERTIFCOMPLETIONSTATE_ASSIGNED:
            break;
        case CERTIFCOMPLETIONSTATE_CERTIFIED:
            if ($newcertcompletion->timewindowopens < $now) {
                $cronresults[] = 'completionchangecronwindowopen';
            }
            if ($newcertcompletion->timeexpires < $now) {
                $cronresults[] = 'completionchangecronexpire';
            }
            break;
        case CERTIFCOMPLETIONSTATE_WINDOWOPEN:
            if ($newcertcompletion->timeexpires < $now) {
                $cronresults[] = 'completionchangecronexpire';
            }
            break;
        case CERTIFCOMPLETIONSTATE_EXPIRED:
            break;
    }

    return array($userresults, $cronresults);
}

/**
 * Get the state of a user's certification completion record.
 *
 * When an inconsistent state is detected, this function assumes that the status and renewalstatus fields
 * are correct, and reports problems with other fields relative to these two. It is possible that the
 * problem (or solution to the problem) is that the status or renewalstatus are incorrect, and the other
 * fields are correct, but it's not possible to distinguish between the two scenarios.
 *
 * @param stdClass $certcompletion as stored in the certif_completion or certif_completion_history table
 * @return int state is one of CERTIFCOMPLETIONSTATE_XXX
 */
function certif_get_completion_state($certcompletion) {
    $state = CERTIFCOMPLETIONSTATE_INVALID;

    // First find the basic state based on certification status and renewalstatus.
    switch ($certcompletion->status) {
        case CERTIFSTATUS_UNSET:
            $state = CERTIFCOMPLETIONSTATE_INVALID;
            break;
        case CERTIFSTATUS_ASSIGNED:
            $state = CERTIFCOMPLETIONSTATE_ASSIGNED;
            break;
        case CERTIFSTATUS_INPROGRESS:
            if ($certcompletion->renewalstatus == CERTIFRENEWALSTATUS_EXPIRED) {
                $state = CERTIFCOMPLETIONSTATE_EXPIRED;
            } else if ($certcompletion->renewalstatus == CERTIFRENEWALSTATUS_DUE) {
                $state = CERTIFCOMPLETIONSTATE_WINDOWOPEN;
            } else { // Not due.
                $state = CERTIFCOMPLETIONSTATE_ASSIGNED;
            }
            break;
        case CERTIFSTATUS_COMPLETED:
            if ($certcompletion->renewalstatus == CERTIFRENEWALSTATUS_DUE) {
                $state = CERTIFCOMPLETIONSTATE_WINDOWOPEN;
            } else {
                // Not due. Expired does not result in INVALID state here, because we want to identify the specific problems later.
                $state = CERTIFCOMPLETIONSTATE_CERTIFIED;
            }
            break;
        case CERTIFSTATUS_EXPIRED:
            $state = CERTIFCOMPLETIONSTATE_EXPIRED;
            break;
    }

    return $state;
}

/**
 * Get the errors associated with a user's certification completion records.
 *
 * This function assumes that the state returned by certif_get_completion_state is correct. It is possible that
 * the problem (or solution to the problem) is that the status or renewalstatus are incorrect, and the other
 * fields are correct, but it's not possible to distinguish between the two scenarios.
 *
 * @param stdClass $certcompletion as stored in the certif_completion or certif_completion_history table
 * @param stdClass $progcompletion as stored in the prog_completion table, or null if checking a history record
 * @return array errors describes any problems (error key => form field)
 */
function certif_get_completion_errors($certcompletion, $progcompletion) {
    global $DB;

    $errors = array();
    $state = certif_get_completion_state($certcompletion);

    switch ($state) {
        case CERTIFCOMPLETIONSTATE_INVALID:
            // The status was invalid, so we can't say anything about the validity of the other fields.
            $errors['error:completionstatusunset'] = 'state';
            break;
        case CERTIFCOMPLETIONSTATE_ASSIGNED:
            if ($certcompletion->renewalstatus != CERTIFRENEWALSTATUS_NOTDUE) {
                $errors['error:stateassigned-renewalstatusincorrect'] = 'renewalstatus';
            }
            if ($certcompletion->certifpath != CERTIFPATH_CERT) {
                $errors['error:stateassigned-pathincorrect'] = 'certifpath';
            }
            if ($certcompletion->timecompleted != 0) {
                $errors['error:stateassigned-timecompletednotempty'] = 'timecompleted';
            }
            if ($certcompletion->timewindowopens != 0) {
                $errors['error:stateassigned-timewindowopensnotempty'] = 'timewindowopens';
            }
            if ($certcompletion->timeexpires != 0) {
                $errors['error:stateassigned-timeexpiresnotempty'] = 'timeexpires';
            }
            if ($certcompletion->baselinetimeexpires != 0) {
                $errors['error:stateassigned-baselinetimeexpiresnotempty'] = 'baselinetimeexpires';
            }
            if ($progcompletion) {
                if ($progcompletion->status != STATUS_PROGRAM_INCOMPLETE) {
                    $errors['error:stateassigned-progstatusincorrect'] = 'progstatus';
                }
                if ($progcompletion->timecompleted != 0) {
                    $errors['error:stateassigned-progtimecompletednotempty'] = 'progtimecompleted';
                }
                if ($progcompletion->timedue == COMPLETION_TIME_UNKNOWN) {
                    $errors['error:stateassigned-timedueunknown'] = 'timedue';
                }
            }
            break;
        case CERTIFCOMPLETIONSTATE_CERTIFIED:
            if ($certcompletion->renewalstatus != CERTIFRENEWALSTATUS_NOTDUE) {
                $errors['error:statecertified-renewalstatusincorrect'] = 'renewalstatus';
            }
            if ($certcompletion->certifpath != CERTIFPATH_RECERT) {
                $errors['error:statecertified-pathincorrect'] = 'certifpath';
            }
            if ($certcompletion->timecompleted <= 0) {
                $errors['error:statecertified-timecompletedempty'] = 'timecompleted';
            }
            if ($certcompletion->timewindowopens <= 0) {
                $errors['error:statecertified-timewindowopensempty'] = 'timewindowopens';
            }
            if ($certcompletion->timewindowopens < $certcompletion->timecompleted) {
                $errors['error:statecertified-timewindowopenstimecompletednotordered'] = 'timewindowopens';
            }
            if ($certcompletion->timeexpires <= 0) {
                $errors['error:statecertified-timeexpiresempty'] = 'timeexpires';
            }
            if ($certcompletion->baselinetimeexpires <= 0) {
                $errors['error:statecertified-baselinetimeexpiresempty'] = 'baselinetimeexpires';
            }
            if ($certcompletion->timeexpires < $certcompletion->timewindowopens) {
                $errors['error:statecertified-timeexpirestimewindowopensnotordered'] = 'timeexpires';
            }
            if ($certcompletion->baselinetimeexpires < $certcompletion->timewindowopens) {
                $errors['error:statecertified-baselinetimeexpirestimewindowopensnotordered'] = 'baselinetimeexpires';
            }
            if ($progcompletion) {
                if (userdate($certcompletion->timecompleted, get_string('strftimedateshort', 'langconfig')) !=
                    userdate($progcompletion->timecompleted, get_string('strftimedateshort', 'langconfig'))) {
                    // Same day, although may be different time.
                    // !!! TL-8341: This is pretty weird. We should investigate, see if it is natural
                    //     and requried, or change it so that they are always identical !!!
                    $errors['error:statecertified-certprogtimecompleteddifferent'] = 'progtimecompleted';
                }
                if ($certcompletion->timeexpires != $progcompletion->timedue) {
                    $errors['error:statecertified-timeexpirestimeduedifferent'] = 'timedue';
                }
                if ($progcompletion->timedue <= 0) {
                    $errors['error:statecertified-timedueempty'] = 'timedue';
                }
                if ($progcompletion->status != STATUS_PROGRAM_COMPLETE) {
                    $errors['error:statecertified-progstatusincorrect'] = 'progstatus';
                }
                if ($progcompletion->timecompleted <= 0) {
                    $errors['error:statecertified-progtimecompletedempty'] = 'progtimecompleted';
                }
            }
            break;
        case CERTIFCOMPLETIONSTATE_WINDOWOPEN:
            if ($certcompletion->renewalstatus != CERTIFRENEWALSTATUS_DUE) {
                $errors['error:statewindowopen-renewalstatusincorrect'] = 'renewalstatus';
            }
            if ($certcompletion->certifpath != CERTIFPATH_RECERT) {
                $errors['error:statewindowopen-pathincorrect'] = 'certifpath';
            }
            if ($certcompletion->timecompleted <= 0) {
                $errors['error:statewindowopen-timecompletedempty'] = 'timecompleted';
            }
            if ($certcompletion->timewindowopens <= 0) {
                $errors['error:statewindowopen-timewindowopensempty'] = 'timewindowopens';
            }
            if ($certcompletion->timewindowopens < $certcompletion->timecompleted) {
                $errors['error:statewindowopen-timewindowopenstimecompletednotordered'] = 'timewindowopens';
            }
            if ($certcompletion->timeexpires <= 0) {
                $errors['error:statewindowopen-timeexpiresempty'] = 'timeexpires';
            }
            if ($certcompletion->baselinetimeexpires <= 0) {
                $errors['error:statewindowopen-baselinetimeexpiresempty'] = 'baselinetimeexpires';
            }
            if ($certcompletion->timeexpires < $certcompletion->timewindowopens) {
                $errors['error:statewindowopen-timeexpirestimewindowopensnotordered'] = 'timeexpires';
            }
            if ($certcompletion->baselinetimeexpires < $certcompletion->timewindowopens) {
                $errors['error:statewindowopen-baselinetimeexpirestimewindowopensnotordered'] = 'baselinetimeexpires';
            }
            if ($progcompletion) {
                if ($certcompletion->timeexpires != $progcompletion->timedue) {
                    $errors['error:statewindowopen-timeexpirestimeduedifferent'] = 'timedue';
                }
                if ($progcompletion->timedue <= 0) {
                    $errors['error:statewindowopen-timedueempty'] = 'timedue';
                }
                if ($progcompletion->status != STATUS_PROGRAM_INCOMPLETE) {
                    $errors['error:statewindowopen-progstatusincorrect'] = 'progstatus';
                }
                if ($progcompletion->timecompleted != 0) {
                    $errors['error:statewindowopen-progtimecompletednotempty'] = 'progtimecompleted';
                }
            }
            break;
        case CERTIFCOMPLETIONSTATE_EXPIRED:
            if ($certcompletion->renewalstatus != CERTIFRENEWALSTATUS_EXPIRED) {
                $errors['error:stateexpired-renewalstatusincorrect'] = 'renewalstatus';
            }
            if ($certcompletion->certifpath != CERTIFPATH_CERT) {
                $errors['error:stateexpired-pathincorrect'] = 'certifpath';
            }
            if ($certcompletion->timecompleted != 0) {
                $errors['error:stateexpired-timecompletednotempty'] = 'timecompleted';
            }
            if ($certcompletion->timewindowopens != 0) {
                $errors['error:stateexpired-timewindowopensnotempty'] = 'timewindowopens';
            }
            if ($certcompletion->timeexpires != 0) {
                $errors['error:stateexpired-timeexpiresnotempty'] = 'timeexpires';
            }
            if ($certcompletion->baselinetimeexpires != 0) {
                $errors['error:stateexpired-baselinetimeexpiresnotempty'] = 'baselinetimeexpires';
            }
            if ($progcompletion) {
                if ($progcompletion->timedue <= 0) {
                    $errors['error:stateexpired-timedueempty'] = 'timedue';
                }
                if ($progcompletion->status != STATUS_PROGRAM_INCOMPLETE) {
                    $errors['error:stateexpired-progstatusincorrect'] = 'progstatus';
                }
                if ($progcompletion->timecompleted != 0) {
                    $errors['error:stateexpired-progtimecompletednotempty'] = 'progtimecompleted';
                }
            }
            break;
    }


    // Check for impossible program statuses. This will override the warnings above, but indicates a major problem.
    if ($progcompletion &&
        $progcompletion->status != STATUS_PROGRAM_INCOMPLETE &&
        $progcompletion->status != STATUS_PROGRAM_COMPLETE) {
        $errors['error:progstatusinvalid'] = 'progstatus';
    }

    // Unique constraint tests for history records.
    if (empty($progcompletion)) {
        // History records need to have unique pair of completion and expiry date for a given cert/user.
        $sql = "SELECT *
                  FROM {certif_completion_history} cch
                 WHERE certifid = :certifid AND userid = :userid AND timecompleted = :timecompleted AND timeexpires = :timeexpires";
        $params = array('certifid' => $certcompletion->certifid, 'userid' => $certcompletion->userid,
            'timecompleted' => $certcompletion->timecompleted, 'timeexpires' => $certcompletion->timeexpires);
        if (!empty($certcompletion->id)) {
            // When update, exclude this record from the check.
            $sql .= " AND id <> :id";
            $params['id'] = $certcompletion->id;
        }
        $otherexists = $DB->record_exists_sql($sql, $params);
        if ($otherexists) {
            $errors['error:completionhistorydatesnotunique'] = 'timecompleted';
        }

        // History records can only be marked unassigned if the user is not currently assigned, and there should only be 1.
        if (!empty($certcompletion->unassigned)) {
            // This history record is marked unassigned. Check if the user is currently assigned or has another unassigned
            // history record.
            $sql = "SELECT 1
                      FROM {certif_completion} cc
                     WHERE cc.certifid = :certifid1 AND cc.userid = :userid1
                     UNION
                    SELECT 2
                      FROM {certif_completion_history} cch
                     WHERE cch.certifid = :certifid2 AND cch.userid = :userid2 AND cch.unassigned = 1";
            $params = array('certifid1' => $certcompletion->certifid, 'userid1' => $certcompletion->userid,
                'certifid2' => $certcompletion->certifid, 'userid2' => $certcompletion->userid,
                'timeexpires' => $certcompletion->timeexpires);
            if (!empty($certcompletion->id)) {
                // When update, exclude this record from the check.
                $sql .= " AND cch.id <> :id";
                $params['id'] = $certcompletion->id;
            }
            $otherexists = $DB->record_exists_sql($sql, $params);
            if ($otherexists) {
                $errors['error:invalidunassignedhist'] = 'unassigned';
            }
        }
    }

    return $errors;
}

/**
 * Convert the errors returned by certif_get_completion_errors into errors that can be used for form validation.
 *
 * @param array $errors as returned by certif_get_completion_errors
 * @return array of form validation errors
 */
function certif_get_completion_form_errors($errors) {
    $formerrors = array();
    foreach ($errors as $stringkey => $formkey) {
        if (isset($formerrors[$formkey])) {
            $formerrors[$formkey] .= '<br>' . get_string($stringkey, 'totara_certification');
        } else {
            $formerrors[$formkey] = get_string($stringkey, 'totara_certification');
        }
    }
    return $formerrors;
}

/**
 * Given a set of errors, calculate a unique problem key (just sort and concatenate errors).
 *
 * @param array $errors as returned by certif_get_completion_state
 * @return string
 */
function certif_get_completion_error_problemkey($errors) {
    if (empty($errors)) {
        return '';
    }

    $errorkeys = array_keys($errors);
    sort($errorkeys);
    return implode('|', $errorkeys);
}

/**
 * Given a problem key returned by certif_get_completion_error_problemkey, return any known explanation or solutions, in html format.
 *
 * @param string $problemkey as returned by certif_get_completion_error_problemkey
 * @param int $programid if provided (non-0), url should only fix problems for this program
 * @param int $userid if provided (non-0), url should only fix problems for this user
 * @param bool $returntoeditor true if you want to return to the certification editor for this user/cert, default false for checker
 * @return string html formatted, possibly including url links to activate known fixes
 */
function certif_get_completion_error_solution($problemkey, $programid = 0, $userid = 0, $returntoeditor = false) {
    if (empty($problemkey)) {
        return '';
    }

    $params = array(
        'progorcert' => 'certification',
        'progid' => $programid,
        'userid' => $userid,
        'returntoeditor' => $returntoeditor,
        'sesskey' => sesskey()
    );
    $baseurl = new moodle_url('/totara/program/check_completion.php', $params);

    switch ($problemkey) {
        case 'error:statewindowopen-timeexpirestimeduedifferent':
            $url = clone($baseurl);
            $url->param('fixkey', 'fixcertwindowopenduedatedifferentmistachexpiry');
            $html = get_string('error:info_fixduedatemismatchexpiry', 'totara_certification') . '<br>' .
                html_writer::link($url, get_string('clicktofixcompletions', 'totara_program'));
            break;
        case 'error:statewindowopen-timedueempty|error:statewindowopen-timeexpirestimeduedifferent':
            $url = clone($baseurl);
            $url->param('fixkey', 'fixcertwindowopenduedateempty');
            $html = get_string('error:info_fixduedate', 'totara_certification') . '<br>' .
                html_writer::link($url, get_string('clicktofixcompletions', 'totara_program'));
            break;
        case 'error:statecertified-timeexpirestimeduedifferent':
            $url = clone($baseurl);
            $url->param('fixkey', 'fixcertcertifiedduedatedifferentmistachexpiry');
            $html = get_string('error:info_fixduedatemismatchexpiry', 'totara_certification') . '<br>' .
                html_writer::link($url, get_string('clicktofixcompletions', 'totara_program'));
            break;
        case 'error:statecertified-timedueempty|error:statecertified-timeexpirestimeduedifferent':
            $url = clone($baseurl);
            $url->param('fixkey', 'fixcertcertifiedduedateempty');
            $html = get_string('error:info_fixduedate', 'totara_certification') . '<br>' .
                html_writer::link($url, get_string('clicktofixcompletions', 'totara_program'));
            break;
        case 'error:statewindowopen-progstatusincorrect|error:statewindowopen-progtimecompletednotempty':
            $url1 = clone($baseurl);
            $url1->param('fixkey', 'fixcertwindowopenreopen');
            $html = get_string('error:info_fixwindowreopen', 'totara_certification') . '<br>' .
                html_writer::link($url1, get_string('clicktofixcompletions', 'totara_program'));
            $url2 = clone($baseurl);
            $url2->param('fixkey', 'fixcertwindowopenprogstatusreset');
            $html .= '<br>' . get_string('error:info_fixprogstatusreset', 'totara_certification') . '<br>' .
                html_writer::link($url2, get_string('clicktofixcompletions', 'totara_program'));
            break;
        case 'error:stateexpired-progstatusincorrect|error:stateexpired-progtimecompletednotempty':
            $url = clone($baseurl);
            $url->param('fixkey', 'fixcertexpiredprogstatusreset');
            $html = get_string('error:info_fixprogstatusreset', 'totara_certification') . '<br>' .
                html_writer::link($url, get_string('clicktofixcompletions', 'totara_program'));
            break;
        case 'error:statewindowopen-progstatusincorrect|error:statewindowopen-progtimecompletednotempty|error:statewindowopen-timeexpirestimeduedifferent':
            $html = get_string('error:info_fixcombination', 'totara_certification') . '<br>';
            $url1 = clone($baseurl);
            $url1->param('fixkey', 'fixcert001mismatchexpiry');
            $html .= get_string('error:info_fixduedatemismatchexpiry', 'totara_certification') . '<br>' .
                html_writer::link($url1, get_string('clicktofixcompletions', 'totara_program'));
            break;
        case 'error:statewindowopen-progstatusincorrect|error:statewindowopen-progtimecompletednotempty|error:statecertified-timedueempty|error:statewindowopen-timeexpirestimeduedifferent':
            $html = get_string('error:info_fixcombination', 'totara_certification') . '<br>';
            $url1 = clone($baseurl);
            $url1->param('fixkey', 'fixcert002');
            $html .= get_string('error:info_fixduedate', 'totara_certification') . '<br>' .
                html_writer::link($url1, get_string('clicktofixcompletions', 'totara_program'));
            break;
        case 'error:statecertified-certprogtimecompleteddifferent|error:statecertified-progstatusincorrect|error:statecertified-progtimecompletedempty':
            $url1 = clone($baseurl);
            $url1->param('fixkey', 'fixcertifiedprogincomplete');
            $html = get_string('error:info_fixcertifiedprogincomplete', 'totara_certification') . '<br>' .
                html_writer::link($url1, get_string('clicktofixcompletions', 'totara_program'));
            break;
        case 'error:stateassigned-timedueunknown':
            $url = clone($baseurl);
            $url->param('fixkey', 'fixassignedtimedueunknown');
            $html = get_string('error:info_fixtimedueunknown', 'totara_program') . '<br>' .
                html_writer::link($url, get_string('clicktofixcompletions', 'totara_program'));
            break;
        case 'error:statecertified-certprogtimecompleteddifferent':
            $url1 = clone($baseurl);
            $url1->param('fixkey', 'fixcertcompletiondate');
            $html = get_string('error:info_fixprogcompletiondatematchpart1', 'totara_certification') . '<br>' .
                html_writer::link($url1, get_string('clicktofixcompletions', 'totara_program'));
            $url2 = clone($baseurl);
            $url2->param('fixkey', 'fixprogcompletiondate');
            $html .= '<br>' . get_string('error:info_fixprogcompletiondatematchpart2', 'totara_certification') . '<br>' .
                html_writer::link($url2, get_string('clicktofixcompletions', 'totara_program'));
            break;
        case 'error:stateassigned-progstatusincorrect|error:stateassigned-progtimecompletednotempty':
            $url = clone($baseurl);
            $url->param('fixkey', 'fixprogincomplete');
            $html = get_string('error:info_fixprogincomplete', 'totara_certification') . '<br>' .
                html_writer::link($url, get_string('clicktofixcompletions', 'totara_program'));
            break;
        case 'error:stateexpired-timedueempty':
            $url = clone($baseurl);
            $url->param('fixkey', 'fixexpiredmissingtimedue');
            $html = get_string('error:info_fixexpiredmissingtimedue', 'totara_certification') . '<br>' .
                html_writer::link($url, get_string('clicktofixcompletions', 'totara_program'));
            break;
        case 'error:missingcompletion':
            $url = clone($baseurl);
            $url->param('fixkey', 'fixmissingcompletionrecords');
            $html = get_string('error:info_fixmissingcompletion', 'totara_certification') . '<br>' .
                html_writer::link($url, get_string('clicktofixcompletions', 'totara_program'));
            break;
        case 'error:unassignedcertifcompletion':
            $url = clone($baseurl);
            $url->param('fixkey', 'fixunassignedcertifcompletionrecords');
            $html = get_string('error:info_fixunassignedcertifcompletionrecord', 'totara_certification') . '<br>' .
                html_writer::link($url, get_string('clicktofixcompletions', 'totara_program'));
            break;
        case 'error:orphanedexception':
            $url1 = clone($baseurl);
            $url1->param('fixkey', 'fixorphanedexceptionassign');
            $html = get_string('error:info_fixorphanedexceptionassign', 'totara_program') . '<br>' .
                html_writer::link($url1, get_string('clicktofixcompletions', 'totara_program'));
            $url2 = clone($baseurl);
            $url2->param('fixkey', 'fixorphanedexceptionrecalculate');
            $html .= '<br>' . get_string('error:info_fixorphanedexceptionrecalculate', 'totara_program') . '<br>' .
                html_writer::link($url2, get_string('clicktofixcompletions', 'totara_program'));
            break;
        default:
            $html = get_string('error:info_unknowncombination', 'totara_program');
            break;
    }

    return $html;
}

/**
 * Applies the specified fix to certification completion (and matching program completion) records.
 *
 * @param string $fixkey the key for the specific fix to be applied (see switch in code)
 * @param int $programid if provided (non-0), only fix problems for this program
 * @param int $userid if provided (non-0), only fix problems for this user
 */
function certif_fix_completions($fixkey, $programid = 0, $userid = 0) {
    global $DB;

    // Creating missing completion records is handled in a separate function, just to keep things tidy.
    if ($fixkey == 'fixmissingcompletionrecords') {
        certif_fix_missing_completions($programid, $userid);
        return;
    }

    // Deleting unassigned certif completion records is handled in a separate function, just to keep things tidy.
    if ($fixkey == 'fixunassignedcertifcompletionrecords') {
        certif_fix_unassigned_certif_completions($programid, $userid);
        return;
    }

    // Resolving orphaned exceptions is handled in a separate function, just to keep things tidy.
    if ($fixkey == 'fixorphanedexceptionassign') {
        prog_fix_orphaned_exceptions_assign($programid, $userid, 'certification');
        return;
    }
    if ($fixkey == 'fixorphanedexceptionrecalculate') {
        prog_fix_orphaned_exceptions_recalculate($programid, $userid, 'certification');
        return;
    }

    // Get all completion records, applying the specified filters.
    $sql = "SELECT cc.*, pc.id AS pcid, pc.programid, pc.status AS progstatus, pc.timestarted AS progtimestarted,
                   pc.timedue AS progtimedue, pc.timecompleted AS progtimecompleted
              FROM {certif_completion} cc
              JOIN {prog} prog
                ON cc.certifid = prog.certifid
              JOIN {prog_completion} pc
                ON pc.programid = prog.id AND cc.userid = pc.userid AND pc.coursesetid = 0
             WHERE 1=1";
    $params = array();
    if ($programid) {
        $sql .= " AND pc.programid = :programid";
        $params['programid'] = $programid;
    }
    if ($userid) {
        $sql .= " AND cc.userid = :userid";
        $params['userid'] = $userid;
    }

    $rs = $DB->get_recordset_sql($sql, $params);

    foreach ($rs as $record) {
        // Separate out the fields into two records.
        $certcompletion = new stdClass();
        $certcompletion->id = $record->id;
        $certcompletion->certifid = $record->certifid;
        $certcompletion->userid = $record->userid;
        $certcompletion->certifpath = $record->certifpath;
        $certcompletion->status = $record->status;
        $certcompletion->renewalstatus = $record->renewalstatus;
        $certcompletion->timewindowopens = $record->timewindowopens;
        $certcompletion->timeexpires = $record->timeexpires;
        $certcompletion->baselinetimeexpires = $record->baselinetimeexpires;
        $certcompletion->timecompleted = $record->timecompleted;

        $progcompletion = new stdClass();
        $progcompletion->id = $record->pcid;
        $progcompletion->programid = $record->programid;
        $progcompletion->userid = $record->userid;
        $progcompletion->status = $record->progstatus;
        $progcompletion->timestarted = $record->progtimestarted;
        $progcompletion->timedue = $record->progtimedue;
        $progcompletion->timecompleted = $record->progtimecompleted;

        // Check for errors.
        $state = certif_get_completion_state($certcompletion);
        $errors = certif_get_completion_errors($certcompletion, $progcompletion);

        // Nothing wrong, so skip this record.
        if (empty($errors) && $state != CERTIFCOMPLETIONSTATE_INVALID) {
            continue;
        }

        $problemkey = certif_get_completion_error_problemkey($errors);
        $result = "";
        $ignoreproblem = "";

        // Only fix if this is an exact match for the specified problem.
        switch ($fixkey) {
            case 'fixcertwindowopenduedatedifferentmismatchexpiry':
                if ($problemkey == 'error:statewindowopen-timeexpirestimeduedifferent') {
                    $result = certif_fix_extension_didnt_update_due_date($certcompletion, $progcompletion);
                }
                break;
            case 'fixcertwindowopenduedateempty':
                if ($problemkey == 'error:statewindowopen-timedueempty|error:statewindowopen-timeexpirestimeduedifferent') {
                    $result = certif_fix_completion_expiry_to_due_date($certcompletion, $progcompletion);
                }
                break;
            case 'fixcertcertifiedduedatedifferentmismatchexpiry':
                if ($problemkey == 'error:statecertified-timeexpirestimeduedifferent') {
                    $result = certif_fix_extension_didnt_update_due_date($certcompletion, $progcompletion);
                }
                break;
            case 'fixcertcertifiedduedateempty':
                if ($problemkey == 'error:statecertified-timedueempty|error:statecertified-timeexpirestimeduedifferent') {
                    $result = certif_fix_completion_expiry_to_due_date($certcompletion, $progcompletion);
                }
                break;
            case 'fixcertwindowopenreopen':
                if ($problemkey == 'error:statewindowopen-progstatusincorrect|error:statewindowopen-progtimecompletednotempty') {
                    $result = certif_fix_completion_window_reopen($certcompletion, $progcompletion);
                }
                break;
            case 'fixcertwindowopenprogstatusreset':
                if ($problemkey == 'error:statewindowopen-progstatusincorrect|error:statewindowopen-progtimecompletednotempty') {
                    $result = certif_fix_completion_prog_status_reset($certcompletion, $progcompletion);
                }
                break;
            case 'fixcertexpiredprogstatusreset':
                if ($problemkey == 'error:stateexpired-progstatusincorrect|error:stateexpired-progtimecompletednotempty') {
                    $result = certif_fix_completion_prog_status_reset($certcompletion, $progcompletion);
                }
                break;
            case 'fixcert001mismatchexpiry':
                if ($problemkey == 'error:statewindowopen-progstatusincorrect|error:statewindowopen-progtimecompletednotempty|error:statewindowopen-timeexpirestimeduedifferent') {
                    $result = certif_fix_extension_didnt_update_due_date($certcompletion, $progcompletion);
                    $ignoreproblem = 'error:statewindowopen-progstatusincorrect|error:statewindowopen-progtimecompletednotempty';
                }
                break;
            case 'fixcert002':
                if ($problemkey == 'error:statewindowopen-progstatusincorrect|error:statewindowopen-progtimecompletednotempty|error:statecertified-timedueempty|error:statewindowopen-timeexpirestimeduedifferent') {
                    $result = certif_fix_completion_expiry_to_due_date($certcompletion, $progcompletion);
                    $ignoreproblem = 'error:statewindowopen-progstatusincorrect|error:statewindowopen-progtimecompletednotempty';
                }
                break;
            case 'fixcertifiedprogincomplete':
                if ($problemkey == 'error:statecertified-certprogtimecompleteddifferent|error:statecertified-progstatusincorrect|error:statecertified-progtimecompletedempty') {
                    $result = certif_fix_completion_prog_status_set_complete($certcompletion, $progcompletion);
                }
                break;
            case 'fixcertcompletiondate':
                if ($problemkey == 'error:statecertified-certprogtimecompleteddifferent') {
                    $result = certif_fix_cert_completion_date($certcompletion, $progcompletion);
                }
                break;
            case 'fixassignedtimedueunknown':
                if ($problemkey == 'error:stateassigned-timedueunknown') {
                    $result = certif_fix_prog_timedue($certcompletion, $progcompletion);
                }
                break;
            case 'fixprogcompletiondate':
                if ($problemkey == 'error:statecertified-certprogtimecompleteddifferent') {
                    $result = certif_fix_prog_completion_date($certcompletion, $progcompletion);
                }
                break;
            case 'fixprogincomplete':
                if ($problemkey == 'error:stateassigned-progstatusincorrect|error:stateassigned-progtimecompletednotempty') {
                    $result = certif_fix_completion_prog_incomplete($certcompletion, $progcompletion);
                }
                break;
            case 'fixexpiredmissingtimedue':
                if ($problemkey == 'error:stateexpired-timedueempty') {
                    $result = certif_fix_expired_missing_timedue($certcompletion, $progcompletion);
                    $stillhasproblems = certif_get_completion_errors($certcompletion, $progcompletion);
                    $stillhasproblemskey = certif_get_completion_error_problemkey($stillhasproblems);
                    if ($stillhasproblemskey == $problemkey) {
                        // The problem was not fixed because there was no available history expiry date. $result contains an explanation.
                        $ignoreproblem = $problemkey;
                    }
                }
                break;
        }

        // Nothing happened, so no need to update or log.
        if (empty($result)) {
            continue;
        }

        certif_write_completion($certcompletion, $progcompletion, $result, $ignoreproblem);
    }

    $rs->close();
}

/**
 * Creates missing program completion records, limited by the specified filters.
 *
 * @param int $programid if provided (non-0), only fix problems for this program
 * @param int $userid if provided (non-0), only fix problems for this user
 */
function certif_fix_missing_completions($programid = 0, $userid = 0) {
    $missingcompletionsrs = certif_find_missing_completions($programid, $userid);

    $message = 'Automated fix \'certif_fix_missing_completions\' was applied<br>';

    foreach ($missingcompletionsrs as $missingcompletion) {
        certif_create_completion($missingcompletion->programid, $missingcompletion->userid, $message);
    }

    $missingcompletionsrs->close();
}

/**
 * Deletes prog_completion records of users who are not assigned but have incomplete completion records, limited by the specified filters.
 *
 * @param int $programid if provided (non-0), only fix problems for this program
 * @param int $userid if provided (non-0), only fix problems for this user
 */
function certif_fix_unassigned_certif_completions($programid = 0, $userid = 0) {
    $unassignedcertifcompletionsrs = certif_find_unassigned_certif_completions($programid, $userid);

    $message = 'Automated fix \'certif_fix_unassigned_certif_completions\' was applied<br>';

    foreach ($unassignedcertifcompletionsrs as $unassignedcertifcompletion) {
        certif_conditionally_delete_completion($unassignedcertifcompletion->programid, $unassignedcertifcompletion->userid, $message);
    }

    $unassignedcertifcompletionsrs->close();
}

/**
 * Insert or update certif_completion and prog_completion records. Checks are performed to ensure that the data
 * is valid before it can be written to the db.
 *
 * NOTE: $ignoreproblemkey should only be used by certif_fix_completions!!! If specified, the records will be
 *       written to the db even if the records have the specified problem, and only that exact problem, or
 *       no problem at all, otherwise the update will not occur.
 *
 * @param stdClass $certcompletion A certif_completion record to be saved, including 'id' if this is an update.
 * @param stdClass $progcompletion A prog_completion record to be saved, including 'id' if this is an update.
 * @param string $message If provided, will override the default program completion log message.
 * @param mixed $ignoreproblemkey String returned by certif_get_completion_error_problemkey which can be ignored.
 * @return True if the records were successfully created or updated.
 */
function certif_write_completion($certcompletion, $progcompletion, $message = '', $ignoreproblemkey = false) {
    global $DB;

    // Decide if this is an insert or update.
    $isinsert = true;
    if (!empty($certcompletion->id) && !empty($progcompletion->id)) {
        $isinsert = false;
    } else if (!empty($certcompletion->id) || !empty($progcompletion->id)) {
        print_error(get_string('error:updatinginvalidcompletionrecords', 'totara_certification'));
    }

    // Ensure the cert and prog records match each other and the database records.
    if ($isinsert) {
        $sql = "SELECT prog.id, prog.certifid, cc.id AS ccid, pc.id AS pcid
                  FROM {prog} prog
             LEFT JOIN {certif_completion} cc
                    ON cc.certifid = prog.certifid AND cc.userid = :ccuserid
             LEFT JOIN {prog_completion} pc
                    ON pc.programid = prog.id AND pc.userid = :pcuserid AND pc.coursesetid = 0
                 WHERE prog.id = :programid";
        $params = array('programid' => $progcompletion->programid,
            'ccuserid' => $certcompletion->userid, 'pcuserid' => $progcompletion->userid);
        $prog = $DB->get_record_sql($sql, $params);
        if (empty($prog) || !empty($prog->ccid) || !empty($prog->pcid) ||
            $certcompletion->certifid != $prog->certifid || $certcompletion->userid != $progcompletion->userid) {
            print_error(get_string('error:updatinginvalidcompletionrecords', 'totara_certification'));
        };

        if (empty($message)) {
            $message = "Completion record created";
        }
    } else {
        $sql = "SELECT cc.id
                  FROM {certif_completion} cc
                  JOIN {prog} prog
                    ON cc.certifid = prog.certifid
                  JOIN {prog_completion} pc
                    ON pc.programid = prog.id AND cc.userid = pc.userid AND pc.coursesetid = 0
                 WHERE cc.id = :ccid AND pc.id = :pcid
                   AND cc.userid = :ccuserid AND pc.userid = :pcuserid
                   AND cc.certifid = :cccertifid AND pc.programid = :pcprogramid";
        $params = array('ccid' => $certcompletion->id, 'pcid' => $progcompletion->id,
            'ccuserid' => $certcompletion->userid, 'pcuserid' => $progcompletion->userid,
            'cccertifid' => $certcompletion->certifid, 'pcprogramid' => $progcompletion->programid);
        if (!$DB->record_exists_sql($sql, $params)) {
            print_error(get_string('error:updatinginvalidcompletionrecords', 'totara_certification'));
        };
    }

    // Before applying the changes, verify that the new records are in a valid state.
    $state = certif_get_completion_state($certcompletion);
    $errors = certif_get_completion_errors($certcompletion, $progcompletion);

    if (!empty($errors)) {
        $problemkey = certif_get_completion_error_problemkey($errors);
    } else {
        $problemkey = "noproblems";
    }

    if (empty($errors) && $state != CERTIFCOMPLETIONSTATE_INVALID || $problemkey === $ignoreproblemkey) {
        if ($isinsert) {
            $DB->insert_record('certif_completion', $certcompletion);
            $DB->insert_record('prog_completion', $progcompletion);
        } else {
            $DB->update_record('certif_completion', $certcompletion);
            $DB->update_record('prog_completion', $progcompletion);
        }

        certif_write_completion_log($progcompletion->programid, $progcompletion->userid, $message);

        return true;
    } else {
        // Some error was detected, and it wasn't specified in $ignoreproblemkey.
        prog_log_completion($progcompletion->programid, $progcompletion->userid,
            'An attempt was made to write changes, but the data was invalid. Message of caller was:<br>' . $message);
        return false;
    }
}

/**
 * Create or update certif_completion_history record. Checks are performed to ensure that the data
 * is valid before it can be written to the db.
 *
 * @param stdClass $certcomplhistory A certif_completion_history record to be saved, including 'id' if this is an update.
 * @param string $message If provided, will override the default program completion log message.
 * @return True if the records were successfully created or updated.
 */
function certif_write_completion_history($certcomplhistory, $message = '') {
    global $DB;

    // Decide if this is an insert or update.
    $isinsert = true;
    if (!empty($certcomplhistory->id)) {
        $isinsert = false;
    }

    // Ensure the history record matches the database records.
    if ($isinsert) {
        $historyexists = $DB->record_exists('certif_completion_history', array(
            'certifid' => $certcomplhistory->certifid,
            'userid' => $certcomplhistory->userid,
            'timeexpires' => $certcomplhistory->timeexpires,
            'timecompleted' => $certcomplhistory->timecompleted
        ));
        if ($historyexists) {
            print_error(get_string('error:updatinginvalidcompletionhistoryrecord', 'totara_certification'));
        };
        if (empty($message)) {
            $message = "Completion history created";
        }
    } else {
        $sql = "SELECT cc.id
                  FROM {certif_completion_history} cc
                 WHERE cc.id = :ccid AND cc.userid = :userid AND cc.certifid = :certifid";
        $params = array('ccid' => $certcomplhistory->id, 'userid' => $certcomplhistory->userid,
            'certifid' => $certcomplhistory->certifid);
        if (!$DB->record_exists_sql($sql, $params)) {
            print_error(get_string('error:updatinginvalidcompletionhistoryrecord', 'totara_certification'));
        };
    }

    $state = certif_get_completion_state($certcomplhistory);
    $errors = certif_get_completion_errors($certcomplhistory, null);

    if (empty($errors) && $state != CERTIFCOMPLETIONSTATE_INVALID) {
        if ($isinsert) {
            $newchid = $DB->insert_record('certif_completion_history', $certcomplhistory);

            certif_write_completion_history_log($newchid, $message);
        } else {
            $DB->update_record('certif_completion_history', $certcomplhistory);

            certif_write_completion_history_log($certcomplhistory->id, $message);
        }

        return true;
    } else {
        return false;
    }
}

/**
 * Fixes program completion records that should have the same due date as the corresponding certification completion expiry date.
 *
 * This should only be used when $programcompletion->timedue is empty! Otherwise, use certif_fix_extension_didnt_update_due_date.
 *
 * @param stdClass $certcompletion a record from certif_completion to be fixed
 * @param stdClass $progcompletion a corresponding record from prog_completion to be fixed
 * @return string message for transaction log
 */
function certif_fix_completion_expiry_to_due_date(&$certcompletion, &$progcompletion) {
    if ($progcompletion->timedue > 0) {
        throw new coding_exception("Tried to apply certif_fix_completion_expiry_to_due_date to a record which has a program time due");
    }

    $progcompletion->timedue = $certcompletion->timeexpires;

    return 'Automated fix \'certif_fix_completion_due_date\' was applied<br>' .
        '<ul><li>\'Expiry date\' was copied to \'Due date\'</li></ul>';
}

/**
 * Fixes the problem where extensions were granted and applied to the certification expiry date, but the due date was unchanged.
 *
 * This should only be used when $programcompletion->timedue is NOT empty! Otherwise, use certif_fix_completion_expiry_to_due_date.
 *
 * @param stdClass $certcompletion a record from certif_completion to be fixed
 * @param stdClass $progcompletion a corresponding record from prog_completion to be fixed
 * @return string message for transaction log
 */
function certif_fix_extension_didnt_update_due_date(&$certcompletion, &$progcompletion) {
    if ($progcompletion->timedue <= 0) {
        throw new coding_exception("Tried to apply certif_fix_extension_didnt_update_due_date to a record which has an empty program time due");
    }

    $certcompletion->baselinetimeexpires = $progcompletion->timedue;
    $progcompletion->timedue = $certcompletion->timeexpires;

    return 'Automated fix \'certif_fix_extension_didnt_update_due_date\' was applied<br>' .
        '<ul><li>\'Due date was\' was copied to \'Baseline expiry date\'</li></ul>
         <ul><li>\'Expiry date\' was copied to \'Due date\'</li></ul>';
}

/**
 * Fixes program completion records that are complete when the certification window is open. This fix works by
 * moving the certification status backwards, from window open to before window opens. The result is that the
 * certification window opening will be triggered again, this time setting the program status to incomplete and
 * removing the program completion date.
 *
 * Note: Certification status is set to completed in case it was inprogress, because inprogress is not a valid
 *       state before the window has opened.
 *
 * NOTE: This will also cause course progress to be reset when the window opens!
 *
 * @param stdClass $certcompletion a record from certif_completion to be fixed
 * @param stdClass $progcompletion a corresponding record from prog_completion to be fixed
 * @return string message for transaction log
 */
function certif_fix_completion_window_reopen(&$certcompletion, &$progcompletion) {
    if ($certcompletion->status == CERTIFSTATUS_INPROGRESS) {
        $certcompletion->status = CERTIFSTATUS_COMPLETED;
    }
    $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
    $progcompletion->timecompleted = $certcompletion->timecompleted;

    return 'Automated fix \'certif_fix_completion_window_reopen\' was applied<br>' .
        '<ul><li>\'Certification status\' was set to \'Certified\'</li>
        <li>\'Renewal status\' was set to \'Not due for renewal\'</li>
        <li>\'Completion date\' was copied to \'Program completion date\'</li></ul>';
}

/**
 * Fixes program completion records that are complete when the certification window is open. This fix works by
 * changing the program status to incomplete and erasing the program completion date.
 *
 * NOTE: This will NOT cause course progress to be reset, so should only be used if course progress was reset
 * correctly previously!
 *
 * @param stdClass $certcompletion a record from certif_completion to be fixed
 * @param stdClass $progcompletion a corresponding record from prog_completion to be fixed
 * @return string message for transaction log
 */
function certif_fix_completion_prog_status_reset(&$certcompletion, &$progcompletion) {
    $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
    $progcompletion->timecompleted = 0;

    return 'Automated fix \'certif_fix_completion_prog_status_reset\' was applied<br>' .
        '<ul><li>\'Program status\' was set to \'Program incomplete\'</li>
        <li>\'Program completion date\' was set to ' . prog_format_log_date($progcompletion->timecompleted) . '</li></ul>';
}

/**
 * Fixes program completion records that are incomplete when the certification is complete. This fix works by
 * changing the program status to complete and setting the program completion date to the certification completion date.
 *
 * NOTE: This could potentially cause course progress to be reset, so should only be used if course progress was NOT reset
 * correctly previously (which is most likely the case)!
 *
 * @param stdClass $certcompletion a record from certif_completion to be fixed
 * @param stdClass $progcompletion a corresponding record from prog_completion to be fixed
 * @return string message for transaction log
 */
function certif_fix_completion_prog_status_set_complete(&$certcompletion, &$progcompletion) {
    $progcompletion->status = STATUS_PROGRAM_COMPLETE;
    $progcompletion->timecompleted = $certcompletion->timecompleted;

    return 'Automated fix \'certif_fix_completion_prog_status_set_complete\' was applied<br>' .
        '<ul><li>\'Program status\' was set to \'Program complete\'</li>
        <li>\'Program completion date\' was set to ' . prog_format_log_date($progcompletion->timecompleted) . '</li></ul>';
}

/**
 * Fixes program completion records that are complete when the certification is incomplete. This fix works by
 * changing the program status to incomplete and setting the program completion date to 0.
 *
 * NOTE: This should only be used if there are no history records that could be restored.
 *
 * @param stdClass $certcompletion a record from certif_completion to be fixed
 * @param stdClass $progcompletion a corresponding record from prog_completion to be fixed
 * @return string message for transaction log
 */
function certif_fix_completion_prog_incomplete(&$certcompletion, &$progcompletion) {
    $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
    $progcompletion->timecompleted = 0;

    return 'Automated fix \'certif_fix_completion_prog_incomplete\' was applied<br>' .
        '<ul><li>\'Program status\' was set to \'Program incomplete\'</li>
        <li>\'Program completion date\' was set to ' . prog_format_log_date($progcompletion->timecompleted) . '</li></ul>';
}

/**
 * Copies program completion date over certification completion date. If using "completion" method, recalculate
 * window open and expiry dates.
 *
 * @param stdClass $certcompletion a record from certif_completion to be fixed
 * @param stdClass $progcompletion a corresponding record from prog_completion to be fixed
 * @return string message for transaction log
 */
function certif_fix_cert_completion_date(&$certcompletion, &$progcompletion) {
    global $DB;

    $certification = $DB->get_record('certif', array('id' => $certcompletion->certifid));

    $certcompletion->timecompleted = $progcompletion->timecompleted;

    if ($certification->recertifydatetype == CERTIFRECERT_COMPLETION) {
        $certcompletion->timeexpires = get_timeexpires($certcompletion->timecompleted, $certification->activeperiod);
        $certcompletion->baselinetimeexpires = $certcompletion->timeexpires;
        $certcompletion->timewindowopens = get_timewindowopens($certcompletion->timeexpires, $certification->windowperiod);
        $progcompletion->timedue = $certcompletion->timeexpires;

        return 'Automated fix \'certif_fix_cert_completion_date\' was applied using current certification settings<br>
            <ul><li>\'Certification completion date\' was set to ' . prog_format_log_date($certcompletion->timecompleted) . '</li>
            <li>\'Certification window open date\' was set to ' . prog_format_log_date($certcompletion->timewindowopens) . '</li>
            <li>\'Certification expiry date\' was set to ' . prog_format_log_date($certcompletion->timeexpires) . '</li>
            <li>\'Certification baseline expiry date\' was set to ' . prog_format_log_date($certcompletion->baselinetimeexpires) . '</li>
            <li>\'Program due date\' was set to ' . prog_format_log_date($progcompletion->timedue) . '</li></ul>';
    } else {
        return 'Automated fix \'certif_fix_cert_completion_date\' was applied<br>
            <ul><li>\'Certification completion date\' was set to ' . prog_format_log_date($certcompletion->timecompleted) . '</li></ul>';
    }
}

/**
 * Set the timedue to COMPLETION_TIME_NOT_SET
 *
 * @param stdClass $certcompletion a record from certif_completion to be fixed
 * @param stdClass $progcompletion a corresponding record from prog_completion to be fixed
 * @return string message for transaction log
 */
function certif_fix_prog_timedue(&$certcompletion, &$progcompletion) {
    $progcompletion->timedue = COMPLETION_TIME_NOT_SET;

    return 'Automated fix \'certif_fix_prog_timedue\' was applied<br>
        <ul><li>\'Program due date\' was set to ' . COMPLETION_TIME_NOT_SET . '</li></ul>';
}

/**
 * Copies certification completion date over program completion date.
 *
 * @param stdClass $certcompletion a record from certif_completion to be fixed
 * @param stdClass $progcompletion a corresponding record from prog_completion to be fixed
 * @return string message for transaction log
 */
function certif_fix_prog_completion_date(&$certcompletion, &$progcompletion) {
    $progcompletion->timecompleted = $certcompletion->timecompleted;

    return 'Automated fix \'certif_fix_prog_completion_date\' was applied<br>
        <ul><li>\'Program completion date\' was set to ' . prog_format_log_date($progcompletion->timecompleted) . '</li></ul>';
}

/**
 * Sets the program due date to the latest history expiry date before the current date. This is effectively the same as
 * what certif_create_completion would do when reassigning a user and no date is available in the prog_completion record.
 *
 * @param stdClass $certcompletion a record from certif_completion to be fixed
 * @param stdClass $progcompletion a corresponding record from prog_completion to be fixed
 * @return string message for transaction log
 */
function certif_fix_expired_missing_timedue(&$certcompletion, &$progcompletion) {
    global $DB;

    $duesql = "SELECT MAX(timeexpires)
                 FROM {certif_completion_history}
                WHERE userid = :userid
                  AND certifid = :certifid
                  AND timeexpires < :now";
    $dueparams = array(
        'userid' => $certcompletion->userid,
        'certifid' => $certcompletion->certifid,
        'now' => time(),
    );
    $maxtimeexpires = $DB->get_field_sql($duesql, $dueparams);

    if ($maxtimeexpires > 0) {
        $progcompletion->timedue = $maxtimeexpires;
        return 'Automated fix \'certif_fix_expired_missing_timedue\' was applied<br>
            <ul><li>\'Program due date\' was set to ' . $maxtimeexpires . '</li></ul>';
    } else {
        return 'Automated fix \'certif_fix_expired_missing_timedue\' was not applied because no history record existed with
                an expiry date before the current date. Either create an appropriate history record and apply the fix again,
                or manually set the due date.';
    }
}

/**
 * Load the completion records for a user and certification (using program id) from the db.
 *
 * This loads the two records in a single query, rather than doing it in 3 queries as is usually needed.
 *
 * Note that if $mustexist is false and returns array(false, false), it is still possible that one or the
 * other of the two records exists, but not both.
 *
 * @param int $programid
 * @param int $userid
 * @param bool $mustexist If records are missing, default true causes an error, false returns array(false, false)
 * @return array (certif_completion, prog_completion) pair of matching records
 */
function certif_load_completion($programid, $userid, $mustexist = true) {
    global $DB;

    $sql = "SELECT cc.*, pc.id AS pcid, pc.programid, pc.coursesetid, pc.status AS progstatus, pc.timestarted AS progtimestarted,
                   pc.timedue, pc.timecreated AS progtimecreated, pc.timecompleted AS progtimecompleted, pc.organisationid, pc.positionid
              FROM {certif_completion} cc
              JOIN {prog} prog
                ON cc.certifid = prog.certifid
              JOIN {prog_completion} pc
                ON pc.programid = prog.id AND cc.userid = pc.userid AND pc.coursesetid = 0
             WHERE pc.programid = :programid AND cc.userid = :userid";
    $params = array('programid' => $programid, 'userid' => $userid);
    $record = $DB->get_record_sql($sql , $params);

    if (empty($record)) {
        if ($mustexist) {
            $a = array('programid' => $programid, 'userid' => $userid);
            print_error(get_string('error:cannotloadcompletionrecords', 'totara_certification', $a));
        } else {
            return array(false, false);
        }
    }

    $certcompletion = new stdClass();
    $certcompletion->id = $record->id;
    $certcompletion->certifid = $record->certifid;
    $certcompletion->userid = $record->userid;
    $certcompletion->certifpath = $record->certifpath;
    $certcompletion->status = $record->status;
    $certcompletion->renewalstatus = $record->renewalstatus;
    $certcompletion->timewindowopens = $record->timewindowopens;
    $certcompletion->timeexpires = $record->timeexpires;
    $certcompletion->baselinetimeexpires = $record->baselinetimeexpires;
    $certcompletion->timecompleted = $record->timecompleted;
    $certcompletion->timemodified = $record->timemodified;

    $progcompletion = new stdClass();
    $progcompletion->id = $record->pcid;
    $progcompletion->programid = $record->programid;
    $progcompletion->coursesetid = $record->coursesetid;
    $progcompletion->userid = $record->userid;
    $progcompletion->status = $record->progstatus;
    $progcompletion->timestarted = $record->progtimestarted;
    $progcompletion->timecreated = $record->progtimecreated;
    $progcompletion->timedue = $record->timedue;
    $progcompletion->timecompleted = $record->progtimecompleted;
    $progcompletion->organisationid = $record->organisationid;
    $progcompletion->positionid = $record->positionid;

    return array($certcompletion, $progcompletion);
}

/**
 * Load all certif_completion records out of the db, with their matching prog_completions. Excludes programs.
 *
 * Use this function to make sure you get the correct records.
 *
 * @param int $userid
 * @return array(stdClass)
 */
function certif_load_all_completions($userid) {
    global $DB;

    $sql = "SELECT cc.*, pc.id AS pcid, pc.programid, pc.coursesetid, pc.status AS progstatus, pc.timestarted AS progtimestarted,
                   pc.timedue, pc.timecreated AS progtimecreated, pc.timecompleted AS progtimecompleted, pc.organisationid, pc.positionid
              FROM {certif_completion} cc
              JOIN {prog} prog
                ON cc.certifid = prog.certifid
              JOIN {prog_completion} pc
                ON pc.programid = prog.id AND cc.userid = pc.userid AND pc.coursesetid = 0
             WHERE cc.userid = :userid";
    $params = array('userid' => $userid);
    $records = $DB->get_records_sql($sql, $params);

    $results = array();

    foreach ($records as $record) {
        $certcompletion = new stdClass();
        $certcompletion->id = $record->id;
        $certcompletion->certifid = $record->certifid;
        $certcompletion->userid = $record->userid;
        $certcompletion->certifpath = $record->certifpath;
        $certcompletion->status = $record->status;
        $certcompletion->renewalstatus = $record->renewalstatus;
        $certcompletion->timewindowopens = $record->timewindowopens;
        $certcompletion->timeexpires = $record->timeexpires;
        $certcompletion->baselinetimeexpires = $record->baselinetimeexpires;
        $certcompletion->timecompleted = $record->timecompleted;
        $certcompletion->timemodified = $record->timemodified;

        $progcompletion = new stdClass();
        $progcompletion->id = $record->pcid;
        $progcompletion->programid = $record->programid;
        $progcompletion->coursesetid = $record->coursesetid;
        $progcompletion->userid = $record->userid;
        $progcompletion->status = $record->progstatus;
        $progcompletion->timestarted = $record->progtimestarted;
        $progcompletion->timecreated = $record->progtimecreated;
        $progcompletion->timedue = $record->timedue;
        $progcompletion->timecompleted = $record->progtimecompleted;
        $progcompletion->organisationid = $record->organisationid;
        $progcompletion->positionid = $record->positionid;

        $results[] = array('certcompletion' => $certcompletion, 'progcompletion' => $progcompletion);
    }

    return $results;
}

/**
 * Write a log message (in the program completion log) when a certification completion has been added or edited.
 *
 * @param int $programid
 * @param int $userid
 * @param string $message If provided, will be added at the start of the log message (instead of "Completion record edited")
 * @param null $changeuserid ID of the user who triggered the event, or 0 to indicate cron or no user, assumes $USER->id if null.
 */
function certif_write_completion_log($programid, $userid, $message = '', $changeuserid = null) {
    list($certcompletion, $progcompletion) = certif_load_completion($programid, $userid);

    $description = certif_calculate_completion_description($certcompletion, $progcompletion, $message);

    prog_log_completion(
        $programid,
        $userid,
        $description,
        $changeuserid
    );
}

/**
 * Calculate the description string for a certification completion log message.
 *
 * @param stdClass $certcompletion
 * @param stdClass $progcompletion
 * @param string $message If provided, will be added at the start of the log message (instead of "Completion record edited")
 * @return string
 */
function certif_calculate_completion_description($certcompletion, $progcompletion, $message = '') {
    global $CERTIFSTATUS, $CERTIFRENEWALSTATUS, $CERTIFPATH;

    $progstatus = 'Invalid';
    switch ($progcompletion->status) {
        case STATUS_PROGRAM_INCOMPLETE:
            $progstatus = 'Not complete';
            break;
        case STATUS_PROGRAM_COMPLETE:
            $progstatus = 'Complete';
            break;
    }

    if (empty($message)) {
        $message = 'Completion record edited';
    }

    $description = $message . '<br>' .
        '<ul><li>Status: ' . $CERTIFSTATUS[$certcompletion->status] . ' (' . $certcompletion->status . ')</li>' .
        '<li>Renewal status: ' . $CERTIFRENEWALSTATUS[$certcompletion->renewalstatus] . ' (' . $certcompletion->renewalstatus . ')</li>' .
        '<li>Certification path: ' . $CERTIFPATH[$certcompletion->certifpath] . ' (' . $certcompletion->certifpath . ')</li>' .
        '<li>Time started: ' . prog_format_log_date($progcompletion->timestarted) . '</li>' .
        '<li>Due date: ' . prog_format_log_date($progcompletion->timedue) . '</li>' .
        '<li>Completion date: ' . prog_format_log_date($certcompletion->timecompleted) . '</li>' .
        '<li>Window open date: ' . prog_format_log_date($certcompletion->timewindowopens) . '</li>' .
        '<li>Expiry date: ' . prog_format_log_date($certcompletion->timeexpires) . '</li>' .
        '<li>Baseline expiry date: ' . prog_format_log_date($certcompletion->baselinetimeexpires) . '</li>' .
        '<li>Program status: ' . $progstatus . ' (' . $progcompletion->status . ')</li>' .
        '<li>Program completion date: ' . prog_format_log_date($progcompletion->timecompleted) . '</li></ul>';

    return $description;
}

/**
 * Write a log message (in the program completion log) when a certification completion history has been added or edited.
 *
 * @param int $chid
 * @param string $message If provided, will be added at the start of the log message (instead of "Completion history record edited")
 * @param null $changeuserid ID of the user who triggered the event, or 0 to indicate cron or no user, assumes $USER->id if null.
 */
function certif_write_completion_history_log($chid, $message = '', $changeuserid = null) {
    global $DB;

    $sql = "SELECT cch.*, prog.id AS programid
              FROM {certif_completion_history} cch
              JOIN {prog} prog ON cch.certifid = prog.certifid
             WHERE cch.id = :chid";
    $certcomplhistory = $DB->get_record_sql($sql, array('chid' => $chid));

    $description = certif_calculate_completion_history_description($certcomplhistory, $message);

    prog_log_completion(
        $certcomplhistory->programid,
        $certcomplhistory->userid,
        $description,
        $changeuserid
    );
}

/**
 * Calculate the description string for a certification completion history log message.
 *
 * @param stdClass $certcomplhistory
 * @param string $message If provided, will be added at the start of the log message (instead of "Completion history edited")
 * @return string
 */
function certif_calculate_completion_history_description($certcomplhistory, $message = '') {
    global $CERTIFSTATUS, $CERTIFRENEWALSTATUS, $CERTIFPATH;

    $id = isset($certcomplhistory->id) ? $certcomplhistory->id : 'Unknown - new history record';

    $unassigned = $certcomplhistory->unassigned ? "Yes" : "No";

    if (empty($message)) {
        $message = 'Completion history edited';
    }

    $description = $message . '<br>' .
        '<ul><li>ID: ' . $id . '</li>' .
        '<li>Status: ' . $CERTIFSTATUS[$certcomplhistory->status] . ' (' . $certcomplhistory->status . ')</li>' .
        '<li>Renewal status: ' . $CERTIFRENEWALSTATUS[$certcomplhistory->renewalstatus] . ' (' . $certcomplhistory->renewalstatus . ')</li>' .
        '<li>Certification path: ' . $CERTIFPATH[$certcomplhistory->certifpath] . ' (' . $certcomplhistory->certifpath . ')</li>' .
        '<li>Completion date: ' . prog_format_log_date($certcomplhistory->timecompleted) . '</li>' .
        '<li>Window open date: ' . prog_format_log_date($certcomplhistory->timewindowopens) . '</li>' .
        '<li>Expiry date: ' . prog_format_log_date($certcomplhistory->timeexpires) . '</li>' .
        '<li>Baseline expiry date: ' . prog_format_log_date($certcomplhistory->baselinetimeexpires) . '</li>' .
        '<li>Unassigned: ' . $unassigned . '</li></ul>';

    return $description;
}

/**
 * Delete a certification completion record, logging it in the prog completion log.
 *
 * @param $chid
 * @param string $message If provided, will override the default program completion log message.
 */
function certif_delete_completion_history($chid, $message = '') {
    global $DB;

    $sql = "SELECT cch.userid, prog.id AS programid
              FROM {certif_completion_history} cch
              JOIN {prog} prog ON cch.certifid = prog.certifid
             WHERE cch.id = :chid";
    $info = $DB->get_record_sql($sql, array('chid' => $chid));
    $DB->delete_records('certif_completion_history', array('id' => $chid));

    if (empty($message)) {
        $message = 'Completion history deleted';
    }

    $description = $message . '<br>' .
        '<ul><li>ID: ' . $chid . '</li></ul>';

    // Record the change in the program completion log.
    prog_log_completion(
        $info->programid,
        $info->userid,
        $description
    );
}

/**
 * Find all the completion records which have problems.
 *
 * The results are designed to be used by totara_program_renderer::get_completion_checker_results
 *
 * Each stdClass item in $fulllist is indexed by the problemkey and contains:
 *  ->problem string short explaination of what the problem is (obtained from the problemkey)
 *  ->userfullname string the full name of the affected user
 *  ->programname string the full name of the certification
 *  ->editcompletionurl moodle_url a link to the completion editor for this user/cert
 *
 * Each stdClass item in $aggregatelist is indexed by the problemkey and contains:
 *  ->problem string short explaination of what the problem is (obtained from the problemkey)
 *  ->category string describing the general type of problem (Files, Consistentcy, etc)
 *  ->solution string long explanation and any info about consequences, how the problem can be manually fixed and/or links to automated fixes etc
 *  ->count int how many records (given the specified filters) are affected by this problem
 *
 * $totalcount reports the total number of records that are checked given the specified filters.
 *
 * @param int $programid
 * @param int $userid
 * @return array(array $fulllist, array $aggregatelist, int $totalcount)
 */
function certif_get_all_completions_with_errors($programid = 0, $userid = 0) {
    global $DB;

    $aggregatelist = array();
    $fulllist = array();
    $totalcount = 0;

    // Check existing completion records for inconsistency errors.
    $sql = "SELECT cc.userid, pc.programid, cc.status, cc.renewalstatus, cc.timecompleted, cc.timewindowopens, prog.fullname,
                   cc.timeexpires, cc.baselinetimeexpires, cc.certifpath, pc.status AS progstatus, pc.timecompleted AS progtimecompleted, pc.timedue
              FROM {certif_completion} cc
              JOIN {prog} prog ON prog.certifid = cc.certifid
              JOIN {prog_completion} pc ON pc.programid = prog.id AND pc.userid = cc.userid AND pc.coursesetid = 0";
    $params = array();
    if (!empty($userid)) {
        $sql .= " AND pc.userid = :userid";
        $params['userid'] = $userid;
    }
    if (!empty($programid)) {
        $sql .= " AND pc.programid = :programid";
        $params['programid'] = $programid;
    }
    $allcompletionsrs = $DB->get_recordset_sql($sql, $params);

    foreach ($allcompletionsrs as $record) {
        $certcompletion = new stdClass();
        $certcompletion->status = $record->status;
        $certcompletion->renewalstatus = $record->renewalstatus;
        $certcompletion->certifpath = $record->certifpath;
        $certcompletion->timecompleted = $record->timecompleted;
        $certcompletion->timewindowopens = $record->timewindowopens;
        $certcompletion->timeexpires = $record->timeexpires;
        $certcompletion->baselinetimeexpires = $record->baselinetimeexpires;

        $progcompletion = new stdClass();
        $progcompletion->status = $record->progstatus;
        $progcompletion->timecompleted = $record->progtimecompleted;
        $progcompletion->timedue = $record->timedue;

        $errors = certif_get_completion_errors($certcompletion, $progcompletion);

        if (!empty($errors)) {
            // Aggregate this combination of errors.
            $problemkey = certif_get_completion_error_problemkey($errors);
            // If the problem key doesn't exist in the aggregate list already then create it.
            if (!isset($aggregatelist[$problemkey])) {
                $newaggregate = new stdClass();
                $newaggregate->count = 0;

                $errorstrings = array();
                foreach ($errors as $errorkey => $errorfield) {
                    $errorstrings[] = get_string($errorkey, 'totara_certification');
                }
                $newaggregate->problem = implode('<br>', $errorstrings);

                $newaggregate->category = get_string('problemcategoryconsistency', 'totara_program');

                // Solution is designed to fix all records affected by this problem with the given filters.
                $newaggregate->solution = certif_get_completion_error_solution($problemkey, $programid, $userid);

                $aggregatelist[$problemkey] = $newaggregate;
            }
            $aggregatelist[$problemkey]->count++;

            $affected = new stdClass();
            $affected->problem = $aggregatelist[$problemkey]->problem;
            $affected->userfullname = fullname($DB->get_record('user', array('id' => $record->userid)));
            $affected->programname = format_string($record->fullname);
            $affected->editcompletionurl = new moodle_url('/totara/certification/edit_completion.php',
                array('id' => $record->programid, 'userid' => $record->userid));
            $affectedkey = $record->programid . '-' . $record->userid;

            $fulllist[$affectedkey] = $affected;
        }
        $totalcount++;
    }

    $allcompletionsrs->close();

    // Check for missing completion records.
    $missingcompletionsrs = certif_find_missing_completions($programid, $userid);

    $problemkey = 'error:missingcompletion';
    foreach ($missingcompletionsrs as $missingcompletion) {
        $totalcount++;

        // If the problem key doesn't exist in the aggregate list already then create it.
        if (!isset($aggregatelist[$problemkey])) {
            $newaggregate = new stdClass();
            $newaggregate->count = 0;

            $newaggregate->problem = get_string($problemkey, 'totara_certification');

            $newaggregate->category = get_string('problemcategoryfiles', 'totara_program');

            // Solution is designed to fix all records affected by this problem with the given filters.
            $newaggregate->solution = certif_get_completion_error_solution($problemkey, $programid, $userid);

            $aggregatelist[$problemkey] = $newaggregate;
        }
        $aggregatelist[$problemkey]->count++;

        $affected = new stdClass();
        $affected->problem = $aggregatelist[$problemkey]->problem;
        $affected->userfullname = fullname($DB->get_record('user', array('id' => $missingcompletion->userid)));
        $affected->programname = format_string($missingcompletion->fullname);
        $affected->editcompletionurl = new moodle_url('/totara/certification/edit_completion.php',
            array('id' => $missingcompletion->programid, 'userid' => $missingcompletion->userid));
        $affectedkey = $missingcompletion->programid . '-' . $missingcompletion->userid;

        $fulllist[$affectedkey] = $affected;
    }

    $missingcompletionsrs->close();

    // Check for unassigned certif_completion records.
    $unassignedcertifcompletionsrs = certif_find_unassigned_certif_completions($programid, $userid);

    $problemkey = 'error:unassignedcertifcompletion';
    foreach ($unassignedcertifcompletionsrs as $unassignedcertifcompletion) {
        // Don't increment total count, because these have already been counted earlier.

        // If the problem key doesn't exist in the aggregate list already then create it.
        if (!isset($aggregatelist[$problemkey])) {
            $newaggregate = new stdClass();
            $newaggregate->count = 0;

            $newaggregate->problem = get_string($problemkey, 'totara_certification');

            $newaggregate->category = get_string('problemcategoryfiles', 'totara_program');

            // Solution is designed to fix all records affected by this problem with the given filters.
            $newaggregate->solution = certif_get_completion_error_solution($problemkey, $programid, $userid);

            $aggregatelist[$problemkey] = $newaggregate;
        }
        $aggregatelist[$problemkey]->count++;

        $affected = new stdClass();
        $affected->problem = $aggregatelist[$problemkey]->problem;
        $affected->userfullname = fullname($DB->get_record('user', array('id' => $unassignedcertifcompletion->userid)));
        $affected->programname = format_string($unassignedcertifcompletion->fullname);
        $affected->editcompletionurl = new moodle_url('/totara/certification/edit_completion.php',
            array('id' => $unassignedcertifcompletion->programid, 'userid' => $unassignedcertifcompletion->userid));
        $affectedkey = $unassignedcertifcompletion->programid . '-' . $unassignedcertifcompletion->userid;

        $fulllist[$affectedkey] = $affected;
    }

    $unassignedcertifcompletionsrs->close();

    // Check for orphaned exceptions.
    $orphanedexceptionrs = prog_find_orphaned_exceptions($programid, $userid, 'certification');

    $problemkey = 'error:orphanedexception';
    foreach ($orphanedexceptionrs as $orphanedexception) {
        // If the problem key doesn't exist in the aggregate list already then create it.
        if (!isset($aggregatelist[$problemkey])) {
            $newaggregate = new stdClass();
            $newaggregate->count = 0;

            $newaggregate->problem = get_string($problemkey, 'totara_program');

            $newaggregate->category = get_string('problemcategoryexceptions', 'totara_program');

            // Solution is designed to fix all records affected by this problem with the given filters.
            $newaggregate->solution = certif_get_completion_error_solution($problemkey, $programid, $userid);

            $aggregatelist[$problemkey] = $newaggregate;
        }
        $aggregatelist[$problemkey]->count++;

        $affected = new stdClass();
        $affected->problem = $aggregatelist[$problemkey]->problem;
        $affected->userfullname = fullname($DB->get_record('user', array('id' => $orphanedexception->userid)));
        $affected->programname = format_string($orphanedexception->fullname);
        $affected->editcompletionurl = new moodle_url('/totara/certification/edit_completion.php',
            array('id' => $orphanedexception->programid, 'userid' => $orphanedexception->userid));
        $affectedkey = $orphanedexception->programid . '-' . $orphanedexception->userid;

        $fulllist[$affectedkey] = $affected;
    }

    $orphanedexceptionrs->close();

    return array($fulllist, $aggregatelist, $totalcount);
}

/**
 * Returns a recordset containing all user who are assigned to certs but are missing a completion record (one or both).
 * These records should exist!
 *
 * @param int $programid if provided (non-0), only fix problems for this cert
 * @param int $userid if provided (non-0), only fix problems for this user
 * @return moodle_recordset containing programid, userid and cert's fullname
 */
function certif_find_missing_completions($programid = 0, $userid = 0) {
    global $DB;

    $params = array();
    $where = "";

    if (!empty($userid)) {
        $where .= " AND pua.userid = :userid";
        $params['userid'] = $userid;
    }

    if (!empty($programid)) {
        $where .= " AND pua.programid = :programid";
        $params['programid'] = $programid;
    }

    $sql = "SELECT pua.programid, pua.userid, p.fullname
              FROM {prog_user_assignment} pua
              JOIN {prog} p ON p.id = pua.programid AND p.certifid IS NOT NULL
         LEFT JOIN {prog_completion} pc ON pc.programid = pua.programid AND pc.userid = pua.userid AND pc.coursesetid = 0
         LEFT JOIN {certif_completion} cc ON cc.certifid = p.certifid AND cc.userid = pua.userid
             WHERE (pc.id IS NULL OR cc.id IS NULL) {$where}";

    return $DB->get_recordset_sql($sql, $params);
}

/**
 * Returns a recordset containing all user who are not assigned and have a certif_completion record (in any state).
 * These records shouldn't exist!
 *
 * @param int $programid if provided (non-0), only fix problems for this cert
 * @param int $userid if provided (non-0), only fix problems for this user
 * @return moodle_recordset containing programid, userid and program's fullname
 */
function certif_find_unassigned_certif_completions($programid = 0, $userid = 0) {
    global $DB;

    $params = array();
    $where = "";

    if (!empty($userid)) {
        $where .= " AND cc.userid = :userid";
        $params['userid'] = $userid;
    }

    if (!empty($programid)) {
        $where .= " AND p.id = :programid";
        $params['programid'] = $programid;
    }

    $sql = "SELECT p.id AS programid, cc.userid, p.fullname
              FROM {certif_completion} cc
              JOIN {prog} p ON p.certifid = cc.certifid
         LEFT JOIN {prog_user_assignment} pua ON pua.programid = p.id AND pua.userid = cc.userid
             WHERE pua.id IS NULL {$where}";

    return $DB->get_recordset_sql($sql, $params);
}

/**
 * Delete's a user's current certification completion record, only if the user is no longer required to complete the certification.
 *
 * History records are created if appropriate. The program completion record will be kept if appropriate.
 *
 * This should be called after a user has been removed from a cert (or in future, with modifications, a cert is removed from
 * a user's learning plan).
 *
 * This function will perform the correct actions regardless of whether or not both completion records exist.
 *
 * @param int $programid
 * @param int $userid
 * @param string $message If provided, will be added to the completion log.
 */
function certif_conditionally_delete_completion($programid, $userid, $message = '') {
    global $DB;

    $program = new program($programid);

    // Don't remove any records if the user is still assigned to the certification.
    if ($program->assigned_to_users_required_learning($userid)) {
        return;
    }

    // If certifications could be added to learning plans then we would perform that check here, and add tests for it.

    $sql = "SELECT cc.*
              FROM {certif_completion} cc
              JOIN {prog} p ON p.certifid = cc.certifid
             WHERE cc.userid = :userid AND p.id = :programid";
    $certcompletion = $DB->get_record_sql($sql, array('userid' => $userid, 'programid' => $programid));

    if (!empty($certcompletion)) {
        $certstate = certif_get_completion_state($certcompletion);
        if ($certstate == CERTIFCOMPLETIONSTATE_ASSIGNED) {
            $description = $message . 'Current cert_completion deleted (not moved to history due to no progress)';
        } else {
            copy_certif_completion_to_hist($certcompletion->certifid, $userid, true);
            $description = $message . 'Current cert_completion moved to history';
        }
        certif_delete_completion($programid, $userid, $description);
    } else {
        prog_log_completion($programid, $userid, 'Tried to delete cert_completion record but it didn\'t exist');
    }

    $progcompletion = prog_load_completion($programid, $userid, false);

    if (!empty($progcompletion)) {
        // We should be using prog_is_complete() here, to maintain backwards compatibility and protect against future changes,
        // but we'll save one db query by using the data we've already got.
        if (!isset($certstate) && $progcompletion->status != STATUS_PROGRAM_COMPLETE || $certstate == CERTIFCOMPLETIONSTATE_ASSIGNED) {
            prog_delete_completion($programid, $userid, 'Matching prog_completion deleted');
        } else {
            prog_log_completion($programid, $userid, 'Matching prog_completion retained to allow restoration to previous state');
        }

    } else {
        prog_log_completion($programid, $userid, 'Tried to delete matching prog_completion but it didn\'t exist');
    }
}

/**
 * Delete a certification completion record, logging it in the prog completion log.
 *
 * Normally you should use certif_conditionally_delete_completion, which will decide what to do with the records.
 *
 * !!! Only use this function if you're absolutely sure that the record needs to be deleted. !!!
 * !!! This function should only be used by certif_conditionally_delete_completion and by    !!!
 * !!! the certification completion editor.                                                  !!!
 *
 * This function does NOT touch the matching prog_completion record!
 *
 * @param $programid
 * @param $userid
 * @param string $message If provided, will override the default program completion log message.
 */
function certif_delete_completion($programid, $userid, $message = '') {
    global $DB;

    $sql = "SELECT cc.id
              FROM {certif_completion} cc
              JOIN {prog} p ON cc.certifid = p.certifid
             WHERE p.id = :programid AND cc.userid = :userid";
    $info = $DB->get_record_sql($sql, array('programid' => $programid, 'userid' => $userid));
    $DB->delete_records('certif_completion', array('id' => $info->id));

    if (empty($message)) {
        $message = 'Current completion deleted';
    }

    // Record the change in the program completion log.
    prog_log_completion(
        $programid,
        $userid,
        $message
    );
}
