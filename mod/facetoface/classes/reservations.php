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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface;

use \context_course;
use mod_facetoface\exception\signup_exception;
use \moodle_exception;


final class reservations {

    /**
     * Add the number of reservations requested (it is assumed that all capacity checks have
     * already been done by this point, so no extra checking is performed).
     *
     * @param seminar_event $seminarevent the reservations are for
     * @param int $bookedby the user making the reservations
     * @param int $number how many reservations to make
     * @param int $waitlisted how many reservations to add to the waitlist (not included in $number)
     */
    public static function add(seminar_event $seminarevent, $bookedby, $number, $waitlisted) {
        for ($i=0; $i<($number+$waitlisted); $i++) {
            $signup = signup::create(0, $seminarevent);
            $signup->set_bookedby($bookedby);
            signup_helper::signup($signup);
        }
        signup_helper::update_attendees($seminarevent);
    }

    /**
     * Get a count of the number of spaces reserved by each manager for a given session.
     *
     * @param seminar_event $seminarevent
     * @return array Array of reservations
     */
    public static function get(seminar_event $seminarevent) {
        global $DB;

        $userfields =  get_all_user_name_fields(true, 'u');

        $params = ['bookedby' => 0, 'userid' => 0, 'sessionid' => $seminarevent->get_id()];
        $sql = "
             SELECT bookedby, COUNT(fs.id) as reservedspaces, sessionid, {$userfields}
               FROM {facetoface_signups} fs
               JOIN {user} u ON fs.bookedby = u.id
              WHERE bookedby != :bookedby
                AND userid    = :userid
                AND sessionid = :sessionid
           GROUP BY bookedby, sessionid, {$userfields}";

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Returns the details of all the other reservations made in the current face to face
     * by the given manager
     *
     * @param seminar_event $seminarevent
     * @param int $managerid
     * @return object[]
     */
    public static function get_others(seminar_event $seminarevent, $managerid) {
        global $DB;

        $usernamefields = get_all_user_name_fields(true, 'u');
        // Get a list of all the bookings the manager has made (not including the current session).
        $sql = "SELECT su.id, s.id AS sessionid, u.id AS userid, {$usernamefields}
              FROM {facetoface_signups} su
              JOIN {facetoface_sessions} s ON s.id = su.sessionid
              JOIN {facetoface_signups_status} sus ON sus.signupid = su.id AND sus.superceded = 0
                                                   AND sus.statuscode > :cancelled
              LEFT JOIN {user} u ON u.id = su.userid
             WHERE su.bookedby = :managerid AND su.sessionid <> :sessionid AND s.facetoface = :facetofaceid
             ORDER BY s.id";

        $params = array('managerid' => $managerid, 'sessionid' => $seminarevent->get_id(), 'facetofaceid' => $seminarevent->get_facetoface(),
            'cancelled' => \mod_facetoface\signup\state\user_cancelled::get_code());
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Remove the (up to) the given number of reservations originally made by the given user.

     * @param seminar_event $seminarevent to remove the reservations from
     * @param int $bookedby the user who made the original reservations
     * @param int $number the number of reservations to remove
     * @param bool $sendnotification
     */
    public static function remove(seminar_event $seminarevent, $bookedby, $number, $sendnotification = false) {
        global $DB;

        $sql = 'SELECT su.id
              FROM {facetoface_signups} su
              JOIN {facetoface_signups_status} sus ON sus.signupid = su.id AND sus.superceded = 0
             WHERE su.sessionid = :sessionid AND su.userid = 0 AND su.bookedby = :bookedby
             ORDER BY sus.statuscode ASC, id DESC';
        // Start by deleting low-status reservations (cancelled, waitlisted), then order by most recently booked.
        $params = array('sessionid' => $seminarevent->get_id(), 'bookedby' => $bookedby);

        $reservations = $DB->get_records_sql($sql, $params, 0, $number);
        $removecount = count($reservations);
        foreach ($reservations as $reservation) {
            $signup = new signup($reservation->id);
            $signup->delete();
        }

        if ($removecount && $sendnotification) {
            $params = array(
                'facetofaceid' => $seminarevent->get_facetoface(),
                'type' => MDL_F2F_NOTIFICATION_AUTO,
                'conditiontype' => MDL_F2F_CONDITION_RESERVATION_CANCELLED,
            );
            $facetoface = $DB->get_record('facetoface', ['id' => $seminarevent->get_facetoface()]);
            $session = facetoface_get_session($seminarevent->get_id());
            facetoface_send_notice($facetoface, $session, $bookedby, $params);
        }

        signup_helper::update_attendees($seminarevent);
    }

    /**
     * Delete reservations for a given session and manager
     *
     * @param seminar_event $seminarevent
     * @param int $managerid
     * @return bool True if dng of the reservations succeeded
     */
    public static function delete(seminar_event $seminarevent, int $managerid) {
        global $DB;

        $params = ['userid' => 0, 'sessionid' => $seminarevent->get_id(), 'bookedby' => $managerid];
        $transaction = $DB->start_delegated_transaction();
        $signups = signup_list::from_conditions($params);
        $signups->delete();
        $transaction->allow_commit();

        return true;
    }

    /**
     * Replace the manager reservations for this session with allocations for the given userids.
     * The list of userids still to be allocated will be returned.
     * Note: There are no checks made to see if the given users have already booked on a session, etc. -
     * it is assumed that any such checks have been completed before calling this function.
     *
     * @param seminar_event $seminarevent
     * @param int $bookedby
     * @param int[] $userids
     * @throws moodle_exception
     * @return int[]
     */
    public static function replace(seminar_event $seminarevent, int $bookedby, array $userids) {
        global $DB, $CFG;

        $courseid = $seminarevent->get_seminar()->get_course();

        $sql = 'SELECT su.id, sus.statuscode, su.discountcode, su.notificationtype
              FROM {facetoface_signups} su
              JOIN {facetoface_signups_status} sus ON sus.signupid = su.id AND sus.superceded = 0
             WHERE su.sessionid = :sessionid AND su.userid = 0 AND su.bookedby = :bookedby
             ORDER BY sus.statuscode DESC, id DESC';
        // Prioritise allocating high-status reservations (booked) over lower-status reservations (waitinglist)
        $params = array('sessionid' => $seminarevent->get_id(), 'bookedby' => $bookedby);
        $reservations = $DB->get_records_sql($sql, $params, 0, count($userids));

        foreach ($reservations as $reservation) {
            $userid = array_shift($userids);
            // Make sure that the user is enroled in the course
            $context = context_course::instance($courseid);
            if (!is_enrolled($context, $userid)) {
                if (!enrol_try_internal_enrol($courseid, $userid, $CFG->learnerroleid, time())) {
                    throw new moodle_exception('unabletoenrol', 'mod_facetoface');
                }
            }

            $signup = new signup($reservation->id);
            $signup->delete();

            $signup = signup::create($userid, $seminarevent, $reservation->notificationtype);
            $signup->set_skipapproval();
            if (!empty($reservation->discountcode)) {
                $signup->set_discountcode($reservation->discountcode);
            }
            $signup->set_bookedby($bookedby);

            if (signup_helper::can_signup($signup)) {
                signup_helper::signup($signup);
            }
        }

        return $userids;
    }

    /**
     * Returns details of whether or not the user can reserve or allocate spaces for their team.
     * Note - an exception is throw if the managerid is set to another user and the current user is missing the
     * 'reserveother' capability
     *
     * @param seminar $seminar
     * @param object[] $sessions
     * @param context $context
     * @param int $managerid optional defaults to current user
     * @throws moodle_exception
     * @return array with values 'allocate' - array how many spare allocations there are, per sesion + 'all'
     *                                        (false if not able to allocate)
     *                           'allocated' - array how many spaces have been allocated by this manager, per session + 'all'
     *                           'maxallocate' - the maximum number of spaces this manager could allocate, per session + 'all'
     *                           'reserve' - array how many spare reservations there are, per session + 'all'
     *                                       (false if not able to reserve)
     *                           'reserved' - array how many spaces have been reserved by this manager, per session + 'all'
     *                           'maxreserve' - array the maximum number of spaces this manager could still allocate, per session + 'all'
     *                           'reservedeadline' - any sessions that start after this date are able to reserve places
     *                           'reservecancel' - any sessions that before this date will have all reservations deleted
     */
    public static function can_reserve_or_allocate(seminar $seminar, $sessions, $context, $managerid = null) {
        global $USER;

        $reserveother = has_capability('mod/facetoface:reserveother', $context);
        if (!$managerid || $managerid == $USER->id) {
            $managerid = $USER->id;
        } else {
            if (!$reserveother) {
                throw new moodle_exception('cannotreserveother', 'mod_facetoface');
            }
        }

        $ret = array(
            'allocate' => false, 'allocated' => array('all' => 0), 'maxallocate' => array('all' => 0),
            'reserve' => false, 'reserved' => array('all' => 0), 'maxreserve' => array('all' => 0),
            'reservedeadline' => 0, 'reservecancel' => 0, 'reserveother' => false
        );
        if (!$seminar->get_managerreserve()) {
            return $ret; // Manager reservations disabled for this activity.
        }

        $ret['reserveother'] = $reserveother;
        $ret['reservedeadline'] = time() + ($seminar->get_reservedays() * DAYSECS);
        $ret['reservecancel'] = time() + ($seminar->get_reservecanceldays() * DAYSECS);

        if (!has_capability('mod/facetoface:reservespace', $context, $managerid)) {
            return $ret; // Manager is not allowed to reserve/allocate any spaces.
        }

        if (!\totara_job\job_assignment::has_staff($managerid)) {
            return $ret; // No staff to allocate spaces to.
        }

        // Allowed to make allocations / reservations - gather some details about the spaces remaining.
        $allocations = self::count_allocations($seminar, $managerid);
        $reservations = self::count($seminar, $managerid);
        foreach ($sessions as $session) {
            if (!isset($allocations[$session->id])) {
                $allocations[$session->id] = 0;
            }
            if (!isset($reservations[$session->id])) {
                $reservations[$session->id] = 0;
            }
        }
        $ret['allocate'] = array();
        $ret['allocated'] = $allocations;
        $ret['maxallocate'] = array();
        $ret['reserve'] = array();
        $ret['reserved'] = $reservations;
        $ret['maxreserve'] = array();

        foreach ($allocations as $sid => $allocation) {
            $reservation = isset($reservations[$sid]) ? $reservations[$sid] : 0;
            // Max allocation = overall max - allocations for other sessions - reservations for other sessions.
            $ret['maxallocate'][$sid] = $seminar->get_maxmanagerreserves() - ($allocations['all'] - $allocation);
            $ret['maxallocate'][$sid] -= ($reservations['all'] - $reservation);
            $ret['allocate'][$sid] = $ret['maxallocate'][$sid] - $allocation; // Number left to allocate.

            // Max reservations = overall max - allocations (all) - reservations for other sessions
            $ret['maxreserve'][$sid] = $seminar->get_maxmanagerreserves() - $allocations['all'];
            $ret['maxreserve'][$sid] -= ($reservations['all'] - $reservation);
            $ret['reserve'][$sid] = $ret['maxreserve'][$sid] - $reservation; // Number left to reserve.

            // Make sure no values are < 0 (e.g. if the allocation limit has changed).
            $ret['maxallocate'][$sid] = max(0, $ret['maxallocate'][$sid]);
            $ret['allocate'][$sid] = max(0, $ret['allocate'][$sid]);
            $ret['maxreserve'][$sid] = max(0, $ret['maxreserve'][$sid]);
            $ret['reserve'][$sid] = max(0, $ret['reserve'][$sid]);
        }

        return $ret;
    }

    /**
     * Get a list of staff who can be allocated / deallocated + reasons why other users cannot be allocated.
     *
     * @param seminar $seminar
     * @param seminar_event $seminarevent
     * @param int $managerid optional
     * @return object containing potential - list of users who could be allocated
     *                           current - list of users who are already allocated
     *                           othersession - users allocated to another sesssion
     *                           cannotunallocate - users who cannot be unallocated (also listed in 'current')
     */
    public static function get_staff_to_allocate(seminar $seminar, seminar_event $seminarevent , $managerid = null) {
        global $DB, $USER;

        if (!$managerid) {
            $managerid = $USER->id;
        }

        $ret = (object)array('potential' => array(), 'current' => array(), 'othersession' => array(), 'cannotunallocate' => array());
        $staff = \totara_job\job_assignment::get_staff_userids($managerid);
        if (empty($staff)) {
            return $ret;
        }

        // Get facetoface "multiple signups per session" setting.
        $multiplesignups = $seminar->get_multiplesessions();

        list($usql, $params) = $DB->get_in_or_equal($staff, SQL_PARAMS_NAMED);
        // Get list of signed-ups that already exist for these users.
        $uidchar = $DB->sql_cast_2char('u.id');
        $sessionidchar = $DB->sql_cast_2char('su.sessionid');
        $sql = 'SELECT CASE
                   WHEN su.sessionid IS NULL THEN '. $uidchar .'
                   ELSE '. $DB->sql_concat($uidchar, "'_'", $sessionidchar) . ' END
                   AS uniqueid , u.*, su.sessionid, su.bookedby, b.firstname AS bookedbyfirstname, b.lastname AS bookedbylastname,
                   su.statuscode
              FROM {user} u
              LEFT JOIN (
                  SELECT xsu.sessionid, xsu.bookedby, xsu.userid, sus.statuscode
                    FROM {facetoface_signups} xsu
                    JOIN {facetoface_signups_status} sus ON sus.signupid = xsu.id AND sus.superceded = 0
                    JOIN {facetoface_sessions} s ON s.id = xsu.sessionid
                   WHERE s.facetoface = :facetofaceid AND sus.statuscode > :status
              ) su ON su.userid = u.id
              LEFT JOIN {user} b ON b.id = su.bookedby
             WHERE u.id ' . $usql . '
          ORDER BY u.lastname ASC, u.firstname ASC';

        $params['facetofaceid'] = $seminar->get_id();
        // Statuses greater than declined to handle cases where people change their mind.
        $params['status'] = \mod_facetoface\signup\state\declined::get_code();
        $users = $DB->get_records_sql($sql, $params);

        foreach ($staff as $member) {
            // Get the signups for the user in this activity.
            $usersignups = totara_search_for_value($users, 'id', TOTARA_SEARCH_OP_EQUAL, $member);

            // Get signup for this user in this session (if exists).
            $usersignupsession = totara_search_for_value($usersignups, 'sessionid', TOTARA_SEARCH_OP_EQUAL, $seminarevent->get_id());

            // Remove current sign-up for session from $usersignups.
            if (!empty($usersignupsession)) {
                $usersignupsession = reset($usersignupsession);
                unset($usersignups[$usersignupsession->uniqueid]);
            }

            // Loop through all user sessions except the current session $session.
            foreach ($usersignups as $user) {
                // If sessionid is null, nothing to do here.
                if ($user->sessionid === null) {
                    continue;
                }

                self::user_can_be_unallocated($user, $managerid);
                $ret->othersession[$user->id] = $user;
            }

            // If the user doesn't have a sign-up for this session check if we can put him in the potential list.
            // Otherwise, verify if the user can or cannot be unallocated.
            if (empty($usersignupsession)) {
                // Multiple sign-ups on OR user has not sign-ups for other sessions in the facetoface.
                $currentuser = reset($usersignups);
                if ($multiplesignups) {
                    $ret->potential[$member] = $currentuser;
                } else if (array_key_exists($currentuser->id, $ret->othersession) === false) {
                    $ret->potential[$member] = $currentuser;
                }
            } else {
                if (!self::user_can_be_unallocated($usersignupsession, $managerid)) {
                    $ret->cannotunallocate[$member] = $usersignupsession;
                }
                $ret->current[$member] = $usersignupsession;
            }
        }

        return $ret;
    }

    /**
     * Allocate spaces to all the users specified.
     *
     * @param seminar_event $seminarevent
     * @param int $bookedby
     * @param int[] $userids
     * @return string[] errors
     */
    public static function allocate_spaces(seminar_event $seminarevent, int $bookedby, array $userids): array {
        global $CFG;

        $courseid = $seminarevent->get_seminar()->get_course();
        $errors = [];

        foreach ($userids as $userid) {
            // Make sure that the user is enroled in the course
            $context = \context_course::instance($courseid);
            if (!is_enrolled($context, $userid)) {
                if (!enrol_try_internal_enrol($courseid, $userid, $CFG->learnerroleid, time())) {
                    $errors[] = get_string('unabletoenrol', 'mod_facetoface');
                    continue;
                }
            }

            $signup = signup::create($userid, $seminarevent);
            $signup->set_bookedby($bookedby);
            $signup->set_skipapproval();
            if (signup_helper::can_signup($signup)) {
                signup_helper::signup($signup);
            } else {
                $failures = signup_helper::get_failures($signup);
                if ($failures) {
                    $errors[] = current($failures);
                }
            }
        }
        return $errors;
    }

    /**
     * Remove the given allocations and, optionally, convert them back into reservations.
     *
     * @param seminar_event $seminarevent
     * @param seminar $seminar
     * @param int[] $userids
     * @param bool $converttoreservations if true, convert allocations to reservations, if false, just cancel
     * @param int $managerid optional defaults to current user
     */
    public static function remove_allocations(seminar_event $seminarevent, seminar $seminar, $userids, $converttoreservations, $managerid = null) {
        global $DB, $USER;

        $session = facetoface_get_session($seminarevent->get_id());

        if (!$managerid) {
            $managerid = $USER->id;
        }

        $seminarevent = new seminar_event((int)$session->id);

        foreach ($userids as $userid) {
            $transaction = $DB->start_delegated_transaction();
            $userisinwaitlist = facetoface_is_user_on_waitlist($session, $userid);

            $signup = signup::create($userid, $seminarevent);
            signup_helper::user_cancel($signup);

            if ($converttoreservations) {
                // Add one reservation.
                $book = 1;
                $waitlist = 0;
                if ($userisinwaitlist) {
                    $book = 0;
                    $waitlist = 1;
                }
                try {
                    self::add($seminarevent, $managerid, $book, $waitlist);
                } catch (signup_exception $e) {
                    // We cannot create reservation anymore, but we can live with that.
                }
            }
            $transaction->allow_commit();

            // Send notification.
            if (!empty($session->sessiondates) && $userisinwaitlist === false) {
                notice_sender::signup_cancellation(signup::create($userid, $seminarevent));
            }
        }
    }

    /**
     * Count how many spaces the current user has reserved in the given face to face instance.

     * @param seminar $seminar
     * @param int $managerid
     * @return array 'all' => total count; sessionid => session count
     */
    public static function count(seminar $seminar, $managerid) {
        global $DB;
        static $reservations = array();

        if (!isset($reservations[$seminar->get_id()])) {
            $sql = 'SELECT s.id, COUNT(*) AS reservecount
                  FROM {facetoface_sessions} s
                  JOIN {facetoface_signups} su ON su.sessionid = s.id
                 WHERE s.facetoface = :facetofaceid AND su.bookedby = :userid AND su.userid = 0
                 GROUP BY s.id';
            $params = array('facetofaceid' => $seminar->get_id(), 'userid' => $managerid);
            $reservations[$seminar->get_id()] = $DB->get_records_sql_menu($sql, $params);
            $reservations[$seminar->get_id()]['all'] = array_sum($reservations[$seminar->get_id()]);
        }

        return $reservations[$seminar->get_id()];
    }

    /**
     * Count how many allocations the current user has made in the given face to face instance.

     * @param seminar $seminar
     * @param int $managerid
     * @return array 'all' => total count; sessionid => session count
     */
    public static function count_allocations(seminar $seminar, $managerid) {
        global $DB;
        static $allocations = array();

        if (!isset($allocations[$seminar->get_id()])) {
            $sql = 'SELECT s.id, COUNT(*) AS allocatecount
                  FROM {facetoface_sessions} s
                  JOIN {facetoface_signups} su ON su.sessionid = s.id
                  JOIN {facetoface_signups_status} sus ON sus.signupid = su.id AND sus.superceded = 0
                                                       AND sus.statuscode > :cancelled
                 WHERE s.facetoface = :facetofaceid AND su.bookedby = :userid AND su.userid <> 0
                 GROUP BY s.id';

            $params = array('facetofaceid' => $seminar->get_id(), 'userid' => $managerid, 'cancelled' => \mod_facetoface\signup\state\user_cancelled::get_code());
            $allocations[$seminar->get_id()] = $DB->get_records_sql_menu($sql, $params);
            $allocations[$seminar->get_id()]['all'] = array_sum($allocations[$seminar->get_id()]);
        }

        return $allocations[$seminar->get_id()];
    }

    /**
     * Find any reservations that are too close to the start of the session and delete them.
     *
     * @param bool $testing testing mode for send_notifications task
     */
    public static function remove_after_deadline($testing) {
        global $DB;
        $sql = "SELECT DISTINCT su.id, s.id AS sessionid, f.id AS facetofaceid, su.bookedby
                  FROM {facetoface} f
                  JOIN {facetoface_sessions} s ON s.facetoface = f.id
                  JOIN {facetoface_sessions_dates} sd ON sd.sessionid = s.id
                  JOIN {facetoface_signups} su ON su.sessionid = s.id AND su.userid = 0
                  JOIN {facetoface_signups_status} sus ON sus.signupid = su.id AND sus.superceded = 0
                 WHERE f.reservecanceldays > 0 AND sd.timestart < (:timenow + (f.reservecanceldays * :daysecs))";
        $params = array('timenow' => time(), 'daysecs' => DAYSECS);
        $signups = $DB->get_recordset_sql($sql, $params);

        if ($signups) {
            $tonotify = array();
            if (!$testing) {
                mtrace('Removing unconfirmed face to face reservations for sessions that will be starting soon');
            }
            foreach ($signups as $signup) {
                if (!$testing) {
                    mtrace("- signupid: {$signup->id}, sessionid: {$signup->sessionid}, facetofaceid: {$signup->facetofaceid}");
                }
                if (!isset($tonotify[$signup->facetofaceid])) {
                    $tonotify[$signup->facetofaceid] = array();
                }
                if (!isset($tonotify[$signup->facetofaceid][$signup->sessionid])) {
                    $tonotify[$signup->facetofaceid][$signup->sessionid] = array();
                }
                $tonotify[$signup->facetofaceid][$signup->sessionid][$signup->bookedby] = $signup->bookedby;

                $signupinstance = new signup($signup->id);
                $signupinstance->delete();
            }
            $signups->close();

            // Send notifications if enabled.
            $notificationdisable = get_config(null, 'facetoface_notificationdisable');
            if (empty($notificationdisable)) {
                $notifyparams = array(
                    'type' => MDL_F2F_NOTIFICATION_AUTO,
                    'conditiontype' => MDL_F2F_CONDITION_RESERVATION_ALL_CANCELLED,
                );
                foreach ($tonotify as $facetofaceid => $sessions) {
                    $seminar = new seminar($facetofaceid);
                    $notifyparams['facetofaceid'] = $seminar->get_id();
                    foreach ($sessions as $sessionid => $managers) {
                        $session = facetoface_get_session($sessionid);
                        foreach ($managers as $managerid) {
                            facetoface_send_notice($seminar->get_properties(), $session, $managerid, $notifyparams);
                        }
                    }
                }
            }
        }
    }

    /**
     * Given a user, determine if he can be unallocated from the list.
     * If he/she cannot be unallocated, add the reason why.
     *
     * This function is used by facetoface_get_staff_to_allocate.
     *
     * @param object $user A user object that must contain id, bookedby and status code
     * @param int $managerid The user's manager ID.
     * @return bool True if the user can be unallocated, false otherwise.
     */
    public static function user_can_be_unallocated(&$user, $managerid) {
        // Booked by someone else or self booking - cannot be unbooked.
        if ($user->bookedby != $managerid) {
            $user->cannotremove = ($user->bookedby == 0) ? 'selfbooked' : 'otherbookedby';
            return false;
        } else if ($user->statuscode && $user->statuscode > \mod_facetoface\signup\state\booked::get_code()) {
            $user->cannotremove = 'attendancetaken'; // Attendance taken - cannot be unbooked.
            return false;
        }

        return true;
    }

    /**
     * Given the number of spaces the manager has reserved / allocated (from 'can_reserve_or_allocate')
     * and the overall remaining capacity of the particular session, work out how many spaces they can
     * actually reserve/allocate for this session.
     *
     * @param seminar_event $seminarevent
     * @param array $reserveinfo
     * @param int $capacityleft
     * @return array - see facetoface_can_reserve_or_allocate for details
     */
    public static function limit_info_to_capacity_left(seminar_event $seminarevent, $reserveinfo, $capacityleft) {
        if (!empty($reserveinfo['reserve'])) {
            if ($reserveinfo['reserve'][$seminarevent->get_id()] > $capacityleft) {
                $reserveinfo['reserve'][$seminarevent->get_id()] = $capacityleft;
                $reserveinfo['maxreserve'][$seminarevent->get_id()] = $reserveinfo['reserve'][$seminarevent->get_id()] + $reserveinfo['reserved'][$seminarevent->get_id()];
            }
        }
        return $reserveinfo;
    }

    /**
     * Given the session details, determines if reservations are still allowed, or if the deadline has now passed.
     *
     * @param seminar_event $seminarevent
     * @param array $reserveinfo
     * @return array - see facetoface_can_reserve_or_allocate for details, but adds two new values:
     *                  'reservepastdeadline' - true if the deadline for adding new reservations has passed
     *                  'reservepastcancel' - true if all existing reservations should be cancelled
     */
    public static function limit_info_by_session_date(seminar_event $seminarevent, $reserveinfo) {
        $reserveinfo['reservepastdeadline'] = false;
        $reserveinfo['reservepastcancel'] = false;
        $session = facetoface_get_session($seminarevent->get_id());
        if ($session->mintimestart) {
            $firstdate = reset($session->sessiondates);
            if (!isset($reserveinfo['reservedeadline']) || $firstdate->timestart <= $reserveinfo['reservedeadline']) {
                $reserveinfo['reservepastdeadline'] = true;
            }
            if (!isset($reserveinfo['reservecancel']) || $firstdate->timestart <= $reserveinfo['reservecancel']) {
                $reserveinfo['reservepastcancel'] = true;
            }
        }

        return $reserveinfo;
    }
}
