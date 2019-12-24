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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @author Aaron Barnes <aaron.barnes@totaralms.com>
 * @author Francois Marier <francois@catalyst.net.nz>
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package mod_facetoface
 */
defined('MOODLE_INTERNAL') || die();

// TODO: These includes are VERY wrong, lib.php must include as little as possible! Solution is to create locallib.php and move most of the stuff there.

require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->dirroot.'/grade/lib.php');
require_once($CFG->dirroot.'/lib/adminlib.php');
require_once($CFG->dirroot . '/user/selector/lib.php');
require_once $CFG->dirroot.'/mod/facetoface/messaginglib.php';
require_once $CFG->dirroot.'/mod/facetoface/notification/lib.php';
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->dirroot . '/mod/facetoface/room/lib.php');
require_once($CFG->dirroot . '/mod/facetoface/asset/lib.php');

/**
 * Definitions for setting notification types
 */
/**
 * Utility definitions
 */
define('MDL_F2F_NONE',          0);
define('MDL_F2F_TEXT',          2);
define('MDL_F2F_BOTH',          3);
define('MDL_F2F_INVITE',        4);
define('MDL_F2F_CANCEL',        8);

/**
 * Definitions for use in forms
 */
define('MDL_F2F_INVITE_BOTH',        7);     // Send a copy of both 4+1+2
define('MDL_F2F_INVITE_TEXT',        6);     // Send just a plain email 4+2
define('MDL_F2F_INVITE_ICAL',        5);     // Send just a combined text/ical message 4+1
define('MDL_F2F_CANCEL_BOTH',        11);    // Send a copy of both 8+2+1
define('MDL_F2F_CANCEL_TEXT',        10);    // Send just a plan email 8+2
define('MDL_F2F_CANCEL_ICAL',        9);     // Send just a combined text/ical message 8+1

// Custom field related constants
define('CUSTOMFIELD_DELIMITER', '##SEPARATOR##');
define('CUSTOMFIELD_TYPE_TEXT',        0);
define('CUSTOMFIELD_TYPE_SELECT',      1);
define('CUSTOMFIELD_TYPE_MULTISELECT', 2);

// Custom field reserved shortnames.
define('CUSTOMFIELD_BUILDING', 'building');
define('CUSTOMFIELD_LOCATION', 'location');
define('CUSTOMFIELD_CANCELNOTE', 'cancellationnote');
define('CUSTOMFIELD_SIGNUPNOTE', 'signupnote');

// Calendar-related constants
if (!defined('CALENDAR_MAX_NAME_LENGTH')) {
    // Admins may override this in config.php if necessary.
    define('CALENDAR_MAX_NAME_LENGTH', 256);
}
define('F2F_CAL_NONE',      0);
define('F2F_CAL_COURSE',    1);
define('F2F_CAL_SITE',      2);

// Signup status codes (remember to update $MDL_F2F_STATUS)
define('MDL_F2F_STATUS_USER_CANCELLED',     10);
define('MDL_F2F_STATUS_SESSION_CANCELLED',  20);
define('MDL_F2F_STATUS_DECLINED',           30);
define('MDL_F2F_STATUS_REQUESTED',          40);
define('MDL_F2F_STATUS_REQUESTEDADMIN',     45); // For 2 step approval.
define('MDL_F2F_STATUS_APPROVED',           50);
define('MDL_F2F_STATUS_WAITLISTED',         60);
define('MDL_F2F_STATUS_BOOKED',             70);
define('MDL_F2F_STATUS_NO_SHOW',            80);
define('MDL_F2F_STATUS_PARTIALLY_ATTENDED', 90);
define('MDL_F2F_STATUS_FULLY_ATTENDED',     100);
define('MDL_F2F_STATUS_NOT_SET',            110);

// Define bulk attendance options
define('MDL_F2F_SELECT_ALL', 10);
define('MDL_F2F_SELECT_NONE', 20);
define('MDL_F2F_SELECT_SET', 30);
define('MDL_F2F_SELECT_NOT_SET', 40);

// Define allow cancellation option.
define('MDL_F2F_ALLOW_CANCELLATION_NEVER', 0);
define('MDL_F2F_ALLOW_CANCELLATION_ANY_TIME', 1);
define('MDL_F2F_ALLOW_CANCELLATION_CUT_OFF', 2);

define('APPROVAL_NONE', 0);
define('APPROVAL_SELF', 1);
define('APPROVAL_ROLE', 2);
define('APPROVAL_MANAGER', 4);
define('APPROVAL_ADMIN', 8);

// Define events displayed on course page settings
define('MDL_F2F_MAX_EVENTS_ON_COURSE', 18);
define('MDL_F2F_DEFAULT_EVENTS_ON_COURSE', 6);

// This array must match the status codes above, and the values
// must equal the end of the constant name but in lower case
global $MDL_F2F_STATUS, $F2F_SELECT_OPTIONS, $F2F_CUSTOMFIELD_RESERVED;
$MDL_F2F_STATUS = array(
    MDL_F2F_STATUS_USER_CANCELLED       => 'user_cancelled',
    MDL_F2F_STATUS_SESSION_CANCELLED    => 'session_cancelled',
    MDL_F2F_STATUS_DECLINED             => 'declined',
    MDL_F2F_STATUS_REQUESTED            => 'requested',
    MDL_F2F_STATUS_REQUESTEDADMIN       => 'requestedadmin',
    MDL_F2F_STATUS_APPROVED             => 'approved',
    MDL_F2F_STATUS_WAITLISTED           => 'waitlisted',
    MDL_F2F_STATUS_BOOKED               => 'booked',
    MDL_F2F_STATUS_NO_SHOW              => 'no_show',
    MDL_F2F_STATUS_PARTIALLY_ATTENDED   => 'partially_attended',
    MDL_F2F_STATUS_FULLY_ATTENDED       => 'fully_attended',
    MDL_F2F_STATUS_NOT_SET              => 'not_set'
);

$F2F_SELECT_OPTIONS = array(
    MDL_F2F_SELECT_NONE    => get_string('selectnoneop', 'facetoface'),
    MDL_F2F_SELECT_ALL     => get_string('selectallop', 'facetoface'),
    MDL_F2F_SELECT_SET     => get_string('selectsetop', 'facetoface'),
    MDL_F2F_SELECT_NOT_SET => get_string('selectnotsetop', 'facetoface')
);

// Define custom field array for reserved shortnames.
$F2F_CUSTOMFIELD_RESERVED = [
    'facetofaceroom' => ['text' => CUSTOMFIELD_BUILDING, 'location' => CUSTOMFIELD_LOCATION],
    'facetofacesignup' => ['text' => CUSTOMFIELD_SIGNUPNOTE],
    'facetofacecancellation' => ['text' => CUSTOMFIELD_CANCELNOTE]
];

/**
 * Returns the human readable code for a face-to-face status
 *
 * @param int $statuscode One of the MDL_F2F_STATUS* constants
 * @return string Human readable code
 */
function facetoface_get_status($statuscode) {
    global $MDL_F2F_STATUS;
    // Check code exists
    if (!isset($MDL_F2F_STATUS[$statuscode])) {
        print_error('F2F status code does not exist: '.$statuscode);
    }

    // Get code
    $string = $MDL_F2F_STATUS[$statuscode];

    // Check to make sure the status array looks to be up-to-date
    if (constant('MDL_F2F_STATUS_'.strtoupper($string)) != $statuscode) {
        print_error('F2F status code array does not appear to be up-to-date: '.$statuscode);
    }

    return $string;
}

/**
 * Obtains the automatic completion state for this face to face activity based on any conditions
 * in face to face settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not. (If no conditions, then return
 *   value depends on comparison type)
 */
function facetoface_get_completion_state($course, $cm, $userid, $type) {
    global $CFG, $DB;

    require_once($CFG->libdir . '/completionlib.php');

    $result = $type;

    // Get face to face.
    $sql = "SELECT f.*, cm.completion, cm.completionview
              FROM {facetoface} f
        INNER JOIN {course_modules} cm
                ON cm.instance = f.id
               AND cm.course = f.course
        INNER JOIN {modules} m
                ON m.id = cm.module
             WHERE m.name='facetoface'
               AND f.id = :fid";
    $params = array('fid' => $cm->instance);
    if (!$facetoface = $DB->get_record_sql($sql, $params)) {
        print_error('cannotfindfacetoface');
    }

    // Only check for existence of tracks and return false if completionstatusrequired.
    // This means that if only view is required we don't end up with a false state.
    if ($facetoface->completionstatusrequired) {
        $completionstatusrequired = json_decode($facetoface->completionstatusrequired, true);
        if (!empty($completionstatusrequired)) {
            list($insql, $inparams) = $DB->get_in_or_equal(array_keys($completionstatusrequired));
            // Get user's latest face to face status.
            $sql = "SELECT f2fss.id AS signupstatusid, f2fss.statuscode, f2fsd.timefinish, f2fs.archived
                FROM {facetoface_sessions} f2fses
                LEFT JOIN {facetoface_signups} f2fs ON (f2fs.sessionid = f2fses.id)
                LEFT JOIN {facetoface_signups_status} f2fss ON (f2fss.signupid = f2fs.id AND f2fss.superceded = 0)
                LEFT JOIN {facetoface_sessions_dates} f2fsd ON (f2fsd.sessionid = f2fses.id)
                WHERE f2fses.facetoface = ? AND f2fs.userid = ?
                  AND f2fss.statuscode $insql
                ORDER BY f2fsd.timefinish DESC";
            $params = array_merge(array($facetoface->id, $userid), $inparams);
            $status = $DB->get_record_sql($sql, $params, IGNORE_MULTIPLE);
            $newstate = false;
            if ($status && !$status->archived) {
                $newstate = true;
                // Tell completion_criteria_activity::review exact time of completion, otherwise it will use time of review run.
                $cm->timecompleted = $status->timefinish;
            }
            $result = completion_info::aggregate_completion_states($type, $result, $newstate);
        }
    }
    return $result;
}

/**
 * Sets activity completion state
 *
 * @param stdClass $facetoface object
 * @param int $userid User ID
 * @param int $completionstate Completion state
 */
function facetoface_set_completion($facetoface, $userid, $completionstate = COMPLETION_COMPLETE) {
    $course = new stdClass();
    $course->id = $facetoface->course;
    $completion = new completion_info($course);

    // Check if completion is enabled site-wide, or for the course
    if (!$completion->is_enabled()) {
        return;
    }

    $cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $facetoface->course);
    if (empty($cm) || !$completion->is_enabled($cm)) {
            return;
    }

    $completion->update_state($cm, $completionstate, $userid);
    $completion->invalidatecache($facetoface->course, $userid, true);
}

/**
 * Returns the effective cost of a session depending on the presence
 * or absence of a discount code.
 *
 * @param class $sessiondata contains the discountcost and normalcost
 */
function facetoface_cost($userid, $sessionid, $sessiondata) {
    global $CFG,$DB;

    $count = $DB->count_records_sql("SELECT COUNT(*)
                               FROM {facetoface_signups} su,
                                    {facetoface_sessions} se
                              WHERE su.sessionid = ?
                                AND su.userid = ?
                                AND su.discountcode IS NOT NULL
                                AND su.sessionid = se.id", array($sessionid, $userid));
    if ($count > 0) {
        return format_string($sessiondata->discountcost);
    } else {
        return format_string($sessiondata->normalcost);
    }
}

/**
 * Turn undefined manager messages into empty strings and deal with checkboxes
 */
function facetoface_fix_settings($facetoface) {

    if (empty($facetoface->completionstatusrequired)) {
        $facetoface->completionstatusrequired = null;
    }
    if (empty($facetoface->reservecancel)) {
        $facetoface->reservecanceldays = 0;
    }
    if (empty($facetoface->emailmanagerconfirmation)) {
        $facetoface->confirmationinstrmngr = null;
    }
    if (empty($facetoface->emailmanagerreminder)) {
        $facetoface->reminderinstrmngr = null;
    }
    if (empty($facetoface->emailmanagercancellation)) {
        $facetoface->cancellationinstrmngr = null;
    }
    if (empty($facetoface->usercalentry)) {
        $facetoface->usercalentry = 0;
    }
    if (empty($facetoface->thirdpartywaitlist)) {
        $facetoface->thirdpartywaitlist = 0;
    }
    if (!empty($facetoface->shortname)) {
        // This needs to match the actual database field size in mod/facetoface/db/install.xml file.
        $facetoface->shortname = core_text::substr($facetoface->shortname, 0, 32);
    }
    if (empty($facetoface->declareinterest)) {
        $facetoface->declareinterest = 0;
    }
    if (empty($facetoface->interestonlyiffull) || !$facetoface->declareinterest) {
        $facetoface->interestonlyiffull = 0;
    }
    if (empty($facetoface->selectjobassignmentonsignup) || !$facetoface->selectjobassignmentonsignup) {
        $facetoface->selectjobassignmentonsignup = 0;
    }
    if (empty($facetoface->forceselectjobassignment) || !$facetoface->forceselectjobassignment) {
        $facetoface->forceselectjobassignment = 0;
    }
}

/**
 * Given a facetoface object from the edit form this function
 * transforms the approvaloptions data into the database friendly format.
 *
 * @param object $facetoface
 */
function facetoface_approval_settings($facetoface) {

    if ($facetoface->approvaloptions == 'approval_none') {
        $facetoface->approvaltype = APPROVAL_NONE;
    } else if ($facetoface->approvaloptions == 'approval_self') {
        $facetoface->approvaltype = APPROVAL_SELF;
    } else if (preg_match('/approval_role_/', $facetoface->approvaloptions)) {
        $split = explode('_', $facetoface->approvaloptions);
        $facetoface->approvaltype = APPROVAL_ROLE;
        $facetoface->approvalrole = $split[2];
    } else if ($facetoface->approvaloptions == 'approval_manager') {
        $facetoface->approvaltype = APPROVAL_MANAGER;
    } else if ($facetoface->approvaloptions == 'approval_admin') {
        $facetoface->approvaltype = APPROVAL_ADMIN;
        $selected = empty($facetoface->selectedapprovers) ? array() : explode(',', $facetoface->selectedapprovers);
        $facetoface->approvaladmins = implode(',', $selected);
    }

    if (isset($facetoface->approval_termsandconds)) {
        $facetoface->approvalterms = s($facetoface->approval_termsandconds);
    }
}

/**
 * Given an object containing all the necessary data, (defined by the
 * form in mod.html) this function will create a new instance and
 * return the id number of the new instance.
 */
function facetoface_add_instance($facetoface) {
    global $DB;
    $facetoface->timemodified = time();

    facetoface_approval_settings($facetoface);
    facetoface_fix_settings($facetoface);
    if ($facetoface->id = $DB->insert_record('facetoface', $facetoface)) {
        facetoface_grade_item_update($facetoface);
    }

    //update any calendar entries
    if ($sessions = facetoface_get_sessions($facetoface->id)) {
        foreach ($sessions as $session) {
            facetoface_update_calendar_entries($session, $facetoface);
        }
    }

    list($defaultnotifications, $missingtemplates) = facetoface_get_default_notifications($facetoface->id);

    // Create default notifications for activity.
    foreach ($defaultnotifications as $notification) {
        $notification->save();
    }

    if (!empty($missingtemplates)) {
        $message = get_string('error:notificationtemplatemissing', 'facetoface') . html_writer::empty_tag('br');

        // Loop through error items and create a message to send.
        foreach ($missingtemplates as $template) {
            $missingtemplate = get_string('template'.$template, 'facetoface');
            $message .= $missingtemplate . html_writer::empty_tag('br');
        }

        totara_set_notification($message);
    }

    return $facetoface->id;
}

/**
 * Given an object containing all the necessary data, (defined by the
 * form in mod.html) this function will update an existing instance
 * with new data.
 */
function facetoface_update_instance($facetoface, $instanceflag = true) {
    global $DB, $USER;

    if ($instanceflag) {
        $facetoface->id = $facetoface->instance;
    }

    $previousapproval = $DB->get_field('facetoface', 'approvaltype', array('id' => $facetoface->id));
    facetoface_approval_settings($facetoface);

    facetoface_fix_settings($facetoface);
    if ($return = $DB->update_record('facetoface', $facetoface)) {
        facetoface_grade_item_update($facetoface);

        //Get time.
        $now = time();

        // Update any calendar entries
        if ($sessions = facetoface_get_sessions($facetoface->id)) {
            foreach ($sessions as $session) {
                facetoface_update_calendar_entries($session, $facetoface);
                // If manager changed from approval required to not
                if ($facetoface->approvaltype != $previousapproval) {
                    switch ($facetoface->approvaltype) {
                        case APPROVAL_NONE:
                        case APPROVAL_SELF:
                            // Approve any pending requests.
                            $status = array(MDL_F2F_STATUS_REQUESTED, MDL_F2F_STATUS_REQUESTEDADMIN);
                            $pending = facetoface_get_attendees($session->id, $status);
                            // Sort pending attendees by singup datetime ASC.
                            core_collator::asort_objects_by_property($pending, 'timecreated', core_collator::SORT_NUMERIC);
                            // Get number of attendees which are already booked.
                            $booked = count(facetoface_get_attendees($session->id, array(MDL_F2F_STATUS_BOOKED)));
                            foreach ($pending as $attendee) {
                                // If we reached session capacity change the status code from MDL_F2F_STATUS_REQUESTED to MDL_F2F_STATUS_WAITLISTED.
                                if ($session->allowoverbook || $session->capacity > $booked) {
                                    $statuscode = MDL_F2F_STATUS_BOOKED;
                                    $iswaitlisted = 0;
                                } else {
                                    $statuscode = MDL_F2F_STATUS_WAITLISTED;
                                    $iswaitlisted = 1;
                                }
                                if (facetoface_update_signup_status($attendee->submissionid, $statuscode, $USER->id, $attendee->grade)) {
                                    $booked++;
                                    // Don't send confirmation notice if the session is in the past.
                                    if (!facetoface_has_session_started($session, $now)) {
                                        // Send confirmation email that an user is booked/waitlisted and cc to user's manager if exists.
                                        facetoface_send_confirmation_notice($facetoface, $session, $attendee->id, 0, $iswaitlisted);
                                    }
                                }
                            }
                            break;
                        case APPROVAL_MANAGER:
                            // Approve any 2 step requests in the second step, since they already have manager approval.
                            $pending = facetoface_get_attendees($session->id, array(MDL_F2F_STATUS_REQUESTEDADMIN));
                            // Sort pending attendees by singup datetime ASC.
                            core_collator::asort_objects_by_property($pending, 'timecreated', core_collator::SORT_NUMERIC);
                            // Get number of attendees which are already booked.
                            $booked = count(facetoface_get_attendees($session->id, array(MDL_F2F_STATUS_BOOKED)));
                            foreach ($pending as $attendee) {
                                // If we reached session capacity change the status code from MDL_F2F_STATUS_REQUESTED to MDL_F2F_STATUS_WAITLISTED.
                                if ($session->allowoverbook || $session->capacity > $booked) {
                                    $statuscode = MDL_F2F_STATUS_BOOKED;
                                    $iswaitlisted = 0;
                                } else {
                                    $statuscode = MDL_F2F_STATUS_WAITLISTED;
                                    $iswaitlisted = 1;
                                }
                                if (facetoface_update_signup_status($attendee->submissionid, $statuscode, $USER->id, $attendee->grade)) {
                                    $booked++;
                                    // Don't send confirmation notice if the session is in the past.
                                    if (!facetoface_has_session_started($session, $now)) {
                                        // Send confirmation email that an user is booked/waitlisted and cc to user's manager if exists.
                                        facetoface_send_confirmation_notice($facetoface, $session, $attendee->id, 0, $iswaitlisted);
                                    }
                                }
                            }

                            // Now that we have approved any requests that already have manager approval, we want to resend request notifications.
                            // Note: There is no break here on purpose, we can just re-use the role approval case to resend the notifications.
                        case APPROVAL_ROLE:
                            // Resend notifications for any pending requests.
                            $status = array(MDL_F2F_STATUS_REQUESTED, MDL_F2F_STATUS_REQUESTEDADMIN);
                            $pending = facetoface_get_attendees($session->id, $status);

                            // Don't re-send request notifications if the session is in the past.
                            if (!facetoface_has_session_started($session, $now)) {
                                foreach ($pending as $attendee) {
                                    facetoface_send_rolerequest_notice($facetoface, $session, $attendee->id);
                                }
                            }
                            break;
                        case APPROVAL_ADMIN:
                            // Resend notifications for any pending requests.
                            $status = array(MDL_F2F_STATUS_REQUESTED, MDL_F2F_STATUS_REQUESTEDADMIN);
                            $pending = facetoface_get_attendees($session->id, $status);

                            // Don't re-send request notifications if the session is in the past.
                            if (!facetoface_has_session_started($session, $now)) {
                                foreach ($pending as $attendee) {
                                    facetoface_send_adminrequest_notice($facetoface, $session, $attendee->id);
                                }
                            }
                            break;
                    }
                }
            }
        }
    }
    return $return;
}

/**
 * Given an ID of an instance of this module, this function will
 * permanently delete the instance and any data that depends on it.
 */
function facetoface_delete_instance($id) {
    global $DB;

    if (!$facetoface = $DB->get_record('facetoface', array('id' => $id))) {
        return false;
    }

    $result = true;

    $transaction = $DB->start_delegated_transaction();

    // Get sessions for the facetoface to delete.
    $sessions = facetoface_get_sessions($id);
    foreach ($sessions as $session) {
        facetoface_delete_session($session);
    }

    $DB->delete_records('facetoface_interest', array('facetoface' => $facetoface->id));

    // Notifications.
    $DB->delete_records('facetoface_notification', array('facetofaceid' => $facetoface->id));

    $DB->delete_records('facetoface_sessions', array('facetoface' => $facetoface->id));

    $DB->delete_records('facetoface', array('id' => $facetoface->id));

    $DB->delete_records('event', array('modulename' => 'facetoface', 'instance' => $facetoface->id));

    facetoface_grade_item_delete($facetoface);

    $transaction->allow_commit();

    return $result;
}

/**
 * Prepare the user data to go into the database.
 */
function facetoface_cleanup_session_data($session) {

    // Only numbers allowed here
    $session->capacity = preg_replace('/[^\d]/', '', $session->capacity);
    $MAX_CAPACITY = 100000;
    if ($session->capacity < 1) {
        $session->capacity = 1;
    }
    elseif ($session->capacity > $MAX_CAPACITY) {
        $session->capacity = $MAX_CAPACITY;
    }

    return $session;
}

/**
 * Create a new entry in the facetoface_sessions table
 */
function facetoface_add_session($session, $sessiondates) {
    global $DB;

    $session->timemodified = $session->timecreated = time();
    $session = facetoface_cleanup_session_data($session);

    $session->id = $DB->insert_record('facetoface_sessions', $session);

    facetoface_save_dates($session->id, $sessiondates);

    return $session->id;
}

/**
 * Modify an entry in the facetoface_sessions table
 */
function facetoface_update_session($session, $sessiondates) {
    global $DB;

    $session->timemodified = time();
    $session = facetoface_cleanup_session_data($session);

    $DB->update_record('facetoface_sessions', $session);
    facetoface_save_dates($session, $sessiondates);

    return $session->id;
}

/**
 * Update seminar session dates in the database without overwriting them
 *
 * @param \stdClass|int $session Facetoface session object or id
 * @param array|null $dates Array of new session dates or null
 */
function facetoface_save_dates($session, array $dates = null) {
    global $DB;

    if (is_null($dates)) {
        $dates = [];
    }

    if (is_object($session)) {
        if (!isset($session->id)) {
            throw new coding_exception('Seminar session object supposed to have an id');
        }

        $session = $session->id;
    }

    $olddates = $DB->get_records('facetoface_sessions_dates', ['sessionid' => $session]);

    // "Key by" date id.
    $olddates = array_combine(array_column($olddates, 'id'), $olddates);

    // Cloning dates to prevent messing with original data. $dates = unserialize(serialize($dates)) will also work.
    $dates = array_map(function ($date) { return clone $date; }, $dates);

    // Filtering dates: throwing out dates that haven't changed and
    // throwing out old dates which present in the new dates array therefore
    // leaving a list of dates to safely remove from the database.
    // Also it is important to note that we have to unset all the dates
    // from a new dates array with the ID which is not in the old dates array
    // and != 0 (not a new date) to prevent users from messing with the input
    // and other seminar dates since we rely on the date id came from a form.
    $dates = array_filter($dates, function($date) use (&$olddates) {
        // Comparing dates yoo-hoo.
        // Some backwards compatibility.
        $date->id = isset($date->id) ? $date->id : 0;

        if (isset($olddates[$date->id])) {
            $old = $olddates[$date->id];
            unset($olddates[$date->id]);
            $room = isset($date->roomid) ? $date->roomid : 0;
            if ($old->sessiontimezone == $date->sessiontimezone &&
                $old->timestart == $date->timestart &&
                $old->timefinish == $date->timefinish &&
                $old->roomid == $room) {
                    $date->assetids = (isset($date->assetids) && is_array($date->assetids)) ? $date->assetids : [];
                    facetoface_sync_assets($date->id, array_unique($date->assetids));
                    return false;
            }
        } elseif ($date->id != 0) {
            return false;
        }

        return true;
    });

    // 1. Remove old dates + assets.
    foreach ($olddatesids = array_keys($olddates) as $id) {
        facetoface_sync_assets($id, []);
    }
    $DB->delete_records_list('facetoface_sessions_dates', 'id', $olddatesids);

    // 2. Update or create.
    foreach ($dates as $date) {
        $assets = isset($date->assetids) ? $date->assetids : [];
        unset($date->assetids);

        if ($date->id > 0) {
            $DB->update_record('facetoface_sessions_dates', $date);
        } else {
            $date->sessionid = $session;
            $date->id = $DB->insert_record('facetoface_sessions_dates', $date);
        }

        facetoface_sync_assets($date->id, array_unique($assets));
    }
}

/**
 * Sync the list of assets for a given seminar event date
 *
 * @param integer $date Seminar date Id
 * @param array $assets List of asset Ids
 * @return bool
 */
function facetoface_sync_assets($date, array $assets = []) {
    global $DB;

    if (empty($assets)) {
        return $DB->delete_records('facetoface_asset_dates', ['sessionsdateid' => $date]);
    }

    $oldassets = $DB->get_fieldset_select('facetoface_asset_dates', 'assetid', 'sessionsdateid = :date_id', ['date_id' => $date]);

    // WIPE THEM AND RECREATE if certain conditions have been met.
    if ((count($assets) == count($oldassets)) && empty(array_diff($assets, $oldassets))) {
        return true;
    }

    $res = $DB->delete_records('facetoface_asset_dates', ['sessionsdateid' => $date]);

    foreach ($assets as $asset) {
        $res &= $DB->insert_record('facetoface_asset_dates', (object) [
            'sessionsdateid' => $date,
            'assetid' => intval($asset)
        ],false);
    }

    return !!$res;
}

/**
 * A function to check if the dates in a session have been changed.
 *
 * @param array $olddates   The dates the session used to be set to
 * @param array $newdates   The dates the session is now set to
 *
 * @return boolean
 */
function facetoface_session_dates_check($olddates, $newdates) {
    // Dates have changed if the amount of dates has changed.
    if (count($olddates) != count($newdates)) {
        return true;
    }

    // Anonymous function used to compare dates to be sorted in an identical way.
    $cmpfunction = function($date1, $date2) {
        // Order by session start time.
        if(($order = strcmp($date1->timestart, $date2->timestart)) === 0) {
            // If start time is the same, ordering by finishtime.
            if (($order = strcmp($date1->timefinish, $date2->timefinish)) === 0) {
                // Just to be on a safe side, if the start and finish dates are the same let's also order by timezone.
                $order = strcmp($date1->sessiontimezone, $date2->sessiontimezone);
            }
        }

        return $order;
    };

    // Sort the old and new dates in a similar way.
    usort($olddates, $cmpfunction);
    usort($newdates, $cmpfunction);

    $dateschanged = false;

    for($i = 0; $i < count($olddates); $i++) {
        if ($olddates[$i]->timestart != $newdates[$i]->timestart ||
            $olddates[$i]->timefinish != $newdates[$i]->timefinish ||
            $olddates[$i]->sessiontimezone != $newdates[$i]->sessiontimezone ||
            $olddates[$i]->roomid != $newdates[$i]->roomid && $newdates[$i]->roomid != 0) {
            $dateschanged = true;
            break;
        }
    }

    return $dateschanged;
}

/**
 * Update site/course and user calendar entries.
 *
 * @param object $session
 * @param object $facetoface, optional
 * @return bool
 */
function facetoface_update_calendar_entries($session, $facetoface = null) {
    global $USER, $DB;

    // Do not re-create calendars as they already removed from cancelled session.
    if ((bool)$session->cancelledstatus) {
        return true;
    }

    if (empty($facetoface)) {
        $facetoface = $DB->get_record('facetoface', array('id' => $session->facetoface));
    }

    // Remove from all calendars.
    facetoface_delete_user_calendar_events($session, 'booking');
    facetoface_delete_user_calendar_events($session, 'session');
    facetoface_remove_session_from_calendar($session, $facetoface->course);
    facetoface_remove_session_from_calendar($session, SITEID);

    if (empty($facetoface->showoncalendar) && empty($facetoface->usercalentry)) {
        return true;
    }

    // Add to NEW calendartype.
    if ($facetoface->usercalentry) {
        // Get ALL enrolled/booked users.
        $users  = facetoface_get_attendees($session->id);
        if (!in_array($USER->id, $users)) {
            facetoface_add_session_to_calendar($session, $facetoface, 'user', $USER->id, 'session');
        }

        foreach ($users as $user) {
            $eventtype = $user->statuscode == MDL_F2F_STATUS_BOOKED ? 'booking' : 'session';
            facetoface_add_session_to_calendar($session, $facetoface, 'user', $user->id, $eventtype);
        }
    }

    if ($facetoface->showoncalendar == F2F_CAL_COURSE) {
        facetoface_add_session_to_calendar($session, $facetoface, 'course');
    } else if ($facetoface->showoncalendar == F2F_CAL_SITE) {
        facetoface_add_session_to_calendar($session, $facetoface, 'site');
    }

    return true;
}

/**
 * Update attendee list status' on booking size change
 */
function facetoface_update_attendees($session) {
    global $USER, $DB;

    // Check that the session has not started. We do not want to update the attendees list after the session has started.
    // We check this first to save queries.
    $timenow = time();

    if (facetoface_has_session_started($session, $timenow)) {
        // The session has started, no updating the attendees list.
        return $session->id;
    }

    // Get facetoface
    $facetoface = $DB->get_record('facetoface', array('id' => $session->facetoface));

    // Get course
    $course = $DB->get_record('course', array('id' => $facetoface->course));

    // Update user status'
    $users = facetoface_get_attendees($session->id, array(MDL_F2F_STATUS_BOOKED, MDL_F2F_STATUS_WAITLISTED), true);
    core_collator::asort_objects_by_property($users, 'timesignedup', core_collator::SORT_NUMERIC);

    if ($users) {
        // No/deleted session dates
        if (empty($session->sessiondates)) {

            // Convert any bookings to waitlists
            foreach ($users as $user) {
                if ($user->statuscode == MDL_F2F_STATUS_BOOKED) {

                    if (!$user->id) {
                        // Cope with reserved spaces.
                        facetoface_update_signup_status($user->signupid, MDL_F2F_STATUS_WAITLISTED, $USER->id);
                    } else if (!facetoface_user_signup($session, $facetoface, $course, $user->discountcode, $user->notificationtype, MDL_F2F_STATUS_WAITLISTED, $user->id, true, null)) {
                        // rollback_sql();
                        return false;
                    }
                }
            }

        // Session dates exist
        } else {
            // Convert earliest signed up users to booked, and make the rest waitlisted
            $capacity = $session->capacity;

            // Count number of booked users
            $booked = 0;
            foreach ($users as $user) {
                if ($user->statuscode == MDL_F2F_STATUS_BOOKED) {
                    $booked++;
                }
            }

            // If booked less than capacity, book some new users
            $facetoface_allowwaitlisteveryone = get_config(null, 'facetoface_allowwaitlisteveryone');
            if ($booked < $capacity && (!$session->waitlisteveryone || empty($facetoface_allowwaitlisteveryone))) {

                // Get the no reply user object so this can be used
                // as the from email address further down the process.
                $fromuser = core_user::get_noreply_user();

                foreach ($users as $user) {
                    if ($booked >= $capacity) {
                        break;
                    }

                    if ($user->statuscode == MDL_F2F_STATUS_WAITLISTED) {
                        if (!$user->id) {
                            // Cope with reserved spaces.
                            facetoface_update_signup_status($user->signupid, MDL_F2F_STATUS_BOOKED, $USER->id);
                        } else if (!facetoface_user_signup($session, $facetoface, $course, $user->discountcode, $user->notificationtype, MDL_F2F_STATUS_BOOKED, $user->id, true, $fromuser)) {
                            // rollback_sql();
                            return false;
                        }
                        $booked++;
                    }
                }
            }
        }
    }

    return $session->id;
}

/**
 * Return an array of all facetoface activities in the current course
 */
function facetoface_get_facetoface_menu() {
    global $CFG, $DB;
    if ($facetofaces = $DB->get_records_sql("SELECT f.id, c.shortname, f.name
                                            FROM {course} c, {facetoface} f
                                            WHERE c.id = f.course
                                            ORDER BY c.shortname, f.name")) {
        $i=1;
        foreach ($facetofaces as $facetoface) {
            $f = $facetoface->id;
            $facetofacemenu[$f] = $facetoface->shortname.' --- '.$facetoface->name;
            $i++;
        }
        return $facetofacemenu;
    } else {
        return '';
    }
}

/**
 * Cancel entry from the facetoface_sessions table
 *
 * @param stdClass $session Object from facetoface_get_session()
 * @param stdClass $fromform data from mod_facetoface_cancelsession_form
 * @return bool
 */
function facetoface_cancel_session($session, $fromform) {
    global $DB, $USER;

    if (facetoface_has_session_started($session, time())) {
        // Session can be cancelled only before it starts.
        return false;
    }
    $sessionobj = $session;

    // Let's get the real DB record, we will need it later for event.
    $session = $DB->get_record('facetoface_sessions', array('id' => $sessionobj->id), '*', MUST_EXIST);
    if ($session->cancelledstatus != 0) {
        // Already cancelled!
        return false;
    }

    $facetoface = $DB->get_record('facetoface', array('id' => $session->facetoface), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('facetoface', $facetoface->id);
    $context = context_module::instance($cm->id);

    // List of users affected by cancellation.
    $notifyusers = array();
    $notifytrainers = array();

    // Use transactions here, we need to make sure that all DB updates happen together.
    $trans = $DB->start_delegated_transaction();

    // Save the custom fields.
    if ($fromform) {
        $fromform->id = $session->id;
        customfield_save_data($fromform, 'facetofacesessioncancel', 'facetoface_sessioncancel');
    }

    // Update field cancelledstatus.
    $session->cancelledstatus = '1';
    $sessionobj->cancelledstatus = $session->cancelledstatus;
    $DB->set_field('facetoface_sessions', 'cancelledstatus', $session->cancelledstatus, array('id' => $session->id));

    // Unlink rooms, orphaned custom rooms are deleted from cleanup task.
    $DB->set_field('facetoface_sessions_dates', 'roomid', 0, array('sessionid' => $session->id));

    // Unlink assets, orphaned custom assets are deleted from cleanup task.
    $dateids = $DB->get_fieldset_select('facetoface_sessions_dates', 'id', "sessionid = :sessionid", array('sessionid' => $session->id));
    foreach($dateids as $dateid) {
        $DB->delete_records('facetoface_asset_dates', array('sessionsdateid' => $dateid));
    }

    // Remove entries from the calendars.
    facetoface_remove_all_calendar_entries($session);

    // Change all user sign-up statuses, the only exceptions are previously cancelled users and declined users.
    $sql = "SELECT DISTINCT s.userid, s.id as signupid, ss.statuscode as signupstatus 
              FROM {facetoface_signups} s
              JOIN {facetoface_signups_status} ss ON ss.signupid = s.id
             WHERE s.sessionid = :sessionid AND
                   ss.superceded = 0 AND
                   ss.statuscode <> :statususercanceled AND
                   ss.statuscode <> :statususerdeclined";
    $params = array(
        'sessionid' => $session->id,
        'statususercanceled' => MDL_F2F_STATUS_USER_CANCELLED,
        'statususerdeclined' => MDL_F2F_STATUS_DECLINED
    );
    $signedupusers = $DB->get_recordset_sql($sql, $params);
    foreach ($signedupusers as $user) {
        // We record this change as being triggered by the current user.
        facetoface_update_signup_status($user->signupid, MDL_F2F_STATUS_SESSION_CANCELLED, $USER->id);
        $notifyusers[$user->userid] = $user;
    }
    $signedupusers->close();

    // All necessary DB updates are finished, let's commit.
    $trans->allow_commit();

    \mod_facetoface\event\session_cancelled::create_from_session($session, $context)->trigger();

    // Notify trainers assigned to the session too.
    $sql = "SELECT DISTINCT sr.userid
              FROM {facetoface_session_roles} sr
              JOIN {user} u ON (u.id = sr.userid)
             WHERE sr.sessionid = :sessionid AND u.deleted = 0";
    $trainers = $DB->get_recordset_sql($sql, array('sessionid' => $session->id));
    foreach ($trainers as $trainer) {
        $notifytrainers[$trainer->userid] = $trainer;
    }
    $trainers->close();

    // Notify affected users.
    foreach ($notifyusers as $id => $user) {
        // Check if the user is waitlisted we should not attach an iCal.
        $invite = $user->signupstatus != MDL_F2F_STATUS_WAITLISTED;
        facetoface_send_cancellation_notice($facetoface, $session, $id, MDL_F2F_CONDITION_SESSION_CANCELLATION, $invite);
    }

    // Notify affected trainers.
    foreach ($notifytrainers as $id => $trainer) {
        facetoface_send_cancellation_notice($facetoface, $session, $id, MDL_F2F_CONDITION_SESSION_CANCELLATION);
    }
    // Notify managers who had reservations.
    facetoface_notify_reserved_session_deleted($facetoface, $session);

    return true;
}

/**
 * Delete entry from the facetoface_sessions table along with all
 * related details in other tables
 *
 * @param object $session Record from facetoface_sessions
 */
function facetoface_delete_session($session) {
    global $DB;

    $facetoface = $DB->get_record('facetoface', array('id' => $session->facetoface), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $facetoface->course, false, MUST_EXIST);
    $context = context_module::instance($cm->id);

    // Get session status and if it is over, do not send any cancellation notifications, see below.
    $sessionover = $session->mintimestart && facetoface_has_session_started($session, time());

    // Cancel user signups (and notify users)
    $signedupusers = $DB->get_records_sql(
        "
            SELECT DISTINCT
                userid
            FROM
                {facetoface_signups} s
            LEFT JOIN
                {facetoface_signups_status} ss
             ON ss.signupid = s.id
            WHERE
                s.sessionid = ?
            AND ss.superceded = 0
            AND ss.statuscode >= ?
        ", array($session->id, MDL_F2F_STATUS_REQUESTED));

    if ($signedupusers and count($signedupusers) > 0) {
        foreach ($signedupusers as $user) {
            if (facetoface_user_cancel($session, $user->userid, true)) {
                if (!$sessionover) {
                    facetoface_send_cancellation_notice($facetoface, $session, $user->userid);
                }
            } else {
                return false; // Cannot rollback since we notified users already
            }
        }
    }

    // Send cancellations for trainers assigned to the session.
    $trainers = $DB->get_records("facetoface_session_roles", array("sessionid" => $session->id));
    if ($trainers and count($trainers) > 0) {
        foreach ($trainers as $trainer) {
            if (!$sessionover) {
                facetoface_send_cancellation_notice($facetoface, $session, $trainer->userid, MDL_F2F_CONDITION_TRAINER_SESSION_CANCELLATION);
            }
        }
    }

    // Notify managers who had reservations.
    if (!$sessionover) {
        facetoface_notify_reserved_session_deleted($facetoface, $session);
    }

    $transaction = $DB->start_delegated_transaction();

    // Remove entries from the teacher calendars
    $select = $DB->sql_like('description', ':attendess');
    $select .= " AND modulename = 'facetoface' AND eventtype = 'facetofacesession' AND instance = :facetofaceid";
    $params = array('attendess' => "%attendees.php?s={$session->id}%", 'facetofaceid' => $facetoface->id);
    $DB->delete_records_select('event', $select, $params);
    if ($facetoface->showoncalendar == F2F_CAL_COURSE) {
        // Remove entry from course calendar
        facetoface_remove_session_from_calendar($session, $facetoface->course);
    } else if ($facetoface->showoncalendar == F2F_CAL_SITE) {
        // Remove entry from site-wide calendar
        facetoface_remove_session_from_calendar($session, SITEID);
    }

    // Delete links to assets and delete freshly orphaned custom assets because there is little chance they would be reused.
    $dateids = $DB->get_fieldset_select('facetoface_sessions_dates', 'id', "sessionid = :sessionid", array('sessionid' => $session->id));
    foreach($dateids as $dateid) {
        $sql = "SELECT fa.id
                  FROM {facetoface_asset} fa
                  JOIN {facetoface_asset_dates} fad ON (fad.assetid = fa.id)
                 WHERE fa.custom = 1 AND sessionsdateid = :sessionsdateid";
        $customassetids = $DB->get_fieldset_sql($sql, array('sessionsdateid' => $dateid));
        $DB->delete_records('facetoface_asset_dates', array('sessionsdateid' => $dateid));
        foreach ($customassetids as $assetid) {
            if (!$DB->record_exists('facetoface_asset_dates', array('assetid' => $assetid))) {
                facetoface_delete_asset($assetid);
            }
        }
    }

    // Delete links to rooms and delete freshly orphaned custom rooms because there is little chance they would be reused.
    $sql = "SELECT fr.id
              FROM {facetoface_room} fr
              JOIN {facetoface_sessions_dates} fsd ON (fsd.roomid = fr.id)
             WHERE fr.custom = 1 AND sessionid = :sessionid";
    $customroomids = $DB->get_fieldset_sql($sql, array('sessionid' => $session->id));
    $DB->set_field('facetoface_sessions_dates', 'roomid', 0, array('sessionid' => $session->id));
    foreach ($customroomids as $roomid) {
        if (!$DB->record_exists('facetoface_sessions_dates', array('roomid' => $roomid))) {
            facetoface_delete_room($roomid);
        }
    }

    // Delete session details
    $DB->delete_records('facetoface_sessions_dates', array('sessionid' => $session->id));
    $DB->delete_records('facetoface_session_roles', array('sessionid' => $session->id));

    // Get session data to delete.
    $sessiondataids = $DB->get_fieldset_select(
        'facetoface_session_info_data',
        'id',
        "facetofacesessionid = :facetofacesessionid",
        array('facetofacesessionid' => $session->id));

    if (!empty($sessiondataids)) {
        list($sqlin, $inparams) = $DB->get_in_or_equal($sessiondataids);
        $DB->delete_records_select('facetoface_session_info_data_param', "dataid {$sqlin}", $inparams);
        $DB->delete_records_select('facetoface_session_info_data', "id {$sqlin}", $inparams);
    }

    $sessioncancelparams = array('sessionid' => $session->id);
    $sessioncancelids = $DB->get_fieldset_select(
        'facetoface_sessioncancel_info_data',
        'id',
        "facetofacesessioncancelid = :sessionid",
        $sessioncancelparams
    );
    if (!empty($sessioncancelids)) {
        list($sqlin, $inparams) = $DB->get_in_or_equal($sessioncancelids);
        $DB->delete_records_select('facetoface_sessioncancel_info_data_param', "dataid $sqlin", $inparams);
        $DB->delete_records_select('facetoface_sessioncancel_info_data', "id {$sqlin}", $inparams);
    }

    // Delete signups and related data.
    facetoface_delete_signups_for_session((int)$session->id);

    // Notifications.
    $DB->delete_records('facetoface_notification_sent', array('sessionid' => $session->id));
    $DB->delete_records('facetoface_notification_hist', array('sessionid' => $session->id));

     // Delete files embedded in details text.
    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'mod_facetoface', 'session', $session->id);

    $DB->delete_records('facetoface_sessions', array('id' => $session->id));

    $transaction->allow_commit();

    return true;
}

/**
 * Delete all signups and related data for given session id.
 *
 * @param int $sessionid
 */
function facetoface_delete_signups_for_session(int $sessionid) {
    global $DB;

    $signupids = $DB->get_fieldset_select(
        'facetoface_signups',
        'id',
        'sessionid = :sessionid',
        ['sessionid' => $sessionid]
    );
    facetoface_delete_signups($signupids);
}

/**
 * Delete signups and related data.
 *
 * @param array $signupids
 */
function facetoface_delete_signups(array $signupids) {
    global $DB;

    if (empty($signupids)) {
        return;
    }

    list($signupsqlin, $signupinparams) = $DB->get_in_or_equal($signupids);

    // Get all associated signup customfield data to delete.
    $signupinfoids = $DB->get_fieldset_select(
        'facetoface_signup_info_data',
        'id',
        "facetofacesignupid {$signupsqlin}",
        $signupinparams
    );

    if (!empty($signupinfoids)) {
        list($sqlin, $inparams) = $DB->get_in_or_equal($signupinfoids);
        $DB->delete_records_select('facetoface_signup_info_data_param', "dataid {$sqlin}", $inparams);
        $DB->delete_records_select('facetoface_signup_info_data', "id {$sqlin}", $inparams);
    }

    // Get all associated cancellation customfield data to delete.
    $cancellationids = $DB->get_fieldset_select(
        'facetoface_cancellation_info_data',
        'id',
        "facetofacecancellationid {$signupsqlin}",
        $signupinparams
    );

    if (!empty($cancellationids)) {
        list($sqlin, $inparams) = $DB->get_in_or_equal($cancellationids);
        $DB->delete_records_select('facetoface_cancellation_info_data_param', "dataid {$sqlin}", $inparams);
        $DB->delete_records_select('facetoface_cancellation_info_data', "id {$sqlin}", $inparams);
    }

    $DB->delete_records_select('facetoface_signups_status', "signupid {$signupsqlin}", $signupinparams);
    $DB->delete_records_select('facetoface_signups', "id {$signupsqlin}", $signupinparams);
}

/**
 * Notify managers that a session they had reserved spaces on has been deleted.
 *
 * @param object $facetoface
 * @param object $session
 */
function facetoface_notify_reserved_session_deleted($facetoface, $session) {
    global $CFG;

    $attendees = facetoface_get_attendees($session->id, array(MDL_F2F_STATUS_BOOKED), true);
    $reservedids = array();
    foreach ($attendees as $attendee) {
        if ($attendee->bookedby) {
            if (!$attendee->id) {
                // Managers can already get booking cancellation notices - just adding reserve cancellation notices.
                $reservedids[] = $attendee->bookedby;
            }
        }
    }
    if (!$reservedids) {
        return;
    }
    $reservedids = array_unique($reservedids);

    $ccmanager = !empty($facetoface->ccmanager);
    $facetoface->ccmanager = false; // Never Cc the manager's manager (that would just be too much).

    // Notify all managers that have reserved spaces for their team.
    $params = array(
        'facetofaceid'  => $facetoface->id,
        'type'          => MDL_F2F_NOTIFICATION_AUTO,
        'conditiontype' => MDL_F2F_CONDITION_RESERVATION_CANCELLED
    );

    $includeical = empty($CFG->facetoface_disableicalcancel);
    foreach ($reservedids as $reservedid) {
        facetoface_send_notice($facetoface, $session, $reservedid, $params, $includeical ? MDL_F2F_BOTH : MDL_F2F_TEXT, MDL_F2F_CANCEL);
    }

    $facetoface->ccmanager = $ccmanager;
}

/**
 * Find any reservations that are too close to the start of the session and delete them.
 */
function facetoface_remove_reservations_after_deadline($testing) {
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
        $signupids = array();
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
            $signupids[] = $signup->id;
        }
        $signups->close();
        $DB->delete_records_list('facetoface_signups_status', 'signupid', $signupids);
        $DB->delete_records_list('facetoface_signups', 'id', $signupids);

        // Send notifications if enabled.
        $notificationdisable = get_config(null, 'facetoface_notificationdisable');
        if (empty($notificationdisable)) {
            $notifyparams = array(
                'type' => MDL_F2F_NOTIFICATION_AUTO,
                'conditiontype' => MDL_F2F_CONDITION_RESERVATION_ALL_CANCELLED,
            );
            foreach ($tonotify as $facetofaceid => $sessions) {
                $facetoface = $DB->get_record('facetoface', array('id' => $facetofaceid));
                $notifyparams['facetofaceid'] = $facetoface->id;
                foreach ($sessions as $sessionid => $managers) {
                    $session = facetoface_get_session($sessionid);
                    foreach ($managers as $managerid) {
                        facetoface_send_notice($facetoface, $session, $managerid, $notifyparams);
                    }
                }
            }
        }
    }
}

/**
 * Determine if user can or not cancel his/her booking.
 *
 * @param stdClass $session Session object like facetoface_get_sessions.
 * @return bool True if cancellation is allowed, false otherwise.
 */
function facetoface_allow_user_cancellation($session) {
    $timenow = time();

    // If cancellations are not allowed, nothing else to check here.
    if ($session->allowcancellations == MDL_F2F_ALLOW_CANCELLATION_NEVER) {
        return false;
    }

    // If no bookedsession set, something went wrong here, return false.
    if (!property_exists($session, 'bookedsession')) {
        return false;
    }

    // If wait-listed, let them cancel.
    if (!$session->mintimestart) {
        return true;
    }

    // If session has started or the user is not booked, no point in cancelling.
    if ($session->mintimestart <= $timenow || !$session->bookedsession) {
        return false;
    }

    // If the attendance has been marked for the user, then do not let him cancel.
    $attendancecode = array(MDL_F2F_STATUS_NO_SHOW, MDL_F2F_STATUS_PARTIALLY_ATTENDED, MDL_F2F_STATUS_FULLY_ATTENDED);
    if ($session->bookedsession && in_array($session->bookedsession->statuscode, $attendancecode)) {
        return false;
    }

    // If the user has been booked but he is in the waitlist, then he can cancel at any time.
    if ($session->bookedsession && $session->bookedsession->statuscode == MDL_F2F_STATUS_WAITLISTED) {
        return true;
    }

    // If cancellations are allowed at any time or until cut-off is reached, make the necessary checks.
    if ($session->allowcancellations == MDL_F2F_ALLOW_CANCELLATION_ANY_TIME) {
        return true;
    } else if ($session->allowcancellations == MDL_F2F_ALLOW_CANCELLATION_CUT_OFF) {
        // Check if we are in the range of the cancellation cut-off period.
        if ($timenow <= $session->mintimestart - $session->cancellationcutoff) {
            return true;
        } else {
            return false;
        }
    }
}

/**
 * Send out email notifications for all sessions that are under capacity at the cut-off.
 */
function facetoface_notify_under_capacity() {
    global $CFG, $DB;

    $conditions = array('component' => 'mod_facetoface', 'classname' => '\mod_facetoface\task\send_notifications_task');
    $lastcron = $DB->get_field('task_scheduled', 'lastruntime', $conditions);
    $roles = $CFG->facetoface_session_rolesnotify;
    $time = time();

    // If there are no recipients, don't bother.
    if (empty($roles)) {
        return;
    }

    $params = array(
        'lastcron' => $lastcron,
        'now'      => $time
    );

    $sql = "SELECT s.*, minstart FROM {facetoface_sessions} s
            INNER JOIN (
                SELECT s.id as sessid, MIN(timestart) AS minstart
                FROM {facetoface_sessions} s
                INNER JOIN {facetoface_sessions_dates} d ON s.id = d.sessionid
                GROUP BY s.id
            ) dates ON dates.sessid = s.id
            WHERE mincapacity > 0 
              AND (minstart - cutoff) < :now 
              AND (minstart - cutoff) >= :lastcron 
              AND s.cancelledstatus = 0";

    $tocheck = $DB->get_records_sql($sql, $params);

    foreach ($tocheck as $session) {
        $booked = facetoface_get_num_attendees($session->id, MDL_F2F_STATUS_APPROVED);
        if ($booked >= $session->mincapacity) {
            continue;
        }

        // No cutoff period means the notify bookings email checkbox was not checked.
        if (empty($session->cutoff)) {
            continue;
        }

        // We've found a session that has not reached the minimum bookings by the cut-off - time to send out emails.
        $facetoface = $DB->get_record('facetoface', array('id' => $session->facetoface));
        $cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $facetoface->course, false);
        $url = new moodle_url('/mod/facetoface/view.php', array('id' => $cm->id));
        $link = $url->out(false);

        // Get the start time of the first date for this session.
        if (!$session->minstart) {
            $starttime = get_string('nostarttime', 'facetoface');
        } else {
            $starttime = userdate($session->minstart, get_string('strftimedatetime'));
        }

        $info = (object)array(
            'name' => format_string($facetoface->name),
            'starttime' => $starttime,
            'capacity' => $session->capacity,
            'mincapacity' => $session->mincapacity,
            'booked' => $booked,
            'link' => $link,
        );

        $eventdata = (object)array(
            'userfrom' => \mod_facetoface\facetoface_user::get_facetoface_user(),
            'subject' => get_string('sessionundercapacity', 'facetoface', format_string($facetoface->name)),
            'fullmessage' => get_string('sessionundercapacity_body', 'facetoface', $info),
            'msgtype' => TOTARA_MSG_TYPE_FACE2FACE,
            'msgstatus' => TOTARA_MSG_STATUS_NOTOK,
            'urgency' => TOTARA_MSG_URGENCY_NORMAL,
            'sendmail' => TOTARA_MSG_EMAIL_YES,
        );

        if (CLI_SCRIPT && !PHPUNIT_TEST) {
            mtrace("Facetoface '{$info->name}' in course {$facetoface->course} is under minimum bookings - {$info->booked}/{$info->capacity} (min capacity {$info->mincapacity}) - emailing session roles.");
        }

        // Get all the users who need to receive the under capacity warning.
        if (!$cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $facetoface->course)) {
            print_error('error:incorrectcoursemodule', 'facetoface');
        }
        $modcontext = context_module::instance($cm->id);

        // Note: remove the true to limit to users with the roles within the module.
        $recipients = get_role_users(explode(',', $roles), $modcontext, true, 'u.*');

        // And send them the notification.
        foreach ($recipients as $recipient) {
            // At this point, to prevent the object $eventdata added up data, it needed to be cloned and reset after sent
            /** @var stdClass $shadowcopy */
            $shadowcopy = clone $eventdata;
            $shadowcopy->userto = $recipient;
            tm_alert_send($shadowcopy);
            unset($shadowcopy);
        }
    }
}

/**
 * Cancel all pending requests for a given session.
 * Primarily for use with the close_registrations task
 *
 * @param stdClass $session - A database record from facetoface_session
 */
function facetoface_cancel_pending_requests($session) {
    global $DB, $USER;

    // Find any pending requests for the given session.
    $requestsql = "SELECT fss.*, fs.userid as recipient
                     FROM {facetoface_signups} fs
               INNER JOIN {facetoface_signups_status} fss
                       ON fss.signupid = fs.id AND fss.superceded = 0
                    WHERE fs.sessionid = :sess
                      AND (statuscode = :req OR statuscode = :adreq)";
    $requestparams = array('req' => MDL_F2F_STATUS_REQUESTED, 'adreq' => MDL_F2F_STATUS_REQUESTEDADMIN);

    $f2fs = array();

    $requestparams['sess'] = $session->id;
    $pendingrequests = $DB->get_records_sql($requestsql, $requestparams);

    // Loop through all the pending requests, cancel them, and send a notification to the user.
    if (!empty($pendingrequests)) {
        if (!isset($f2fs[$session->facetoface])) {
            $f2fs[$session->facetoface] = $DB->get_record('facetoface', array('id' => $session->facetoface), '*', MUST_EXIST);
        }

        foreach ($pendingrequests as $pending) {
            // Mark the request as declined so they can no longer be approved.
            facetoface_update_signup_status($pending->signupid, MDL_F2F_STATUS_DECLINED, $USER->id);

            // Send a registration expiration message to the user (and their manager).
            facetoface_send_registration_closure_notice($f2fs[$session->facetoface], $session, $pending->recipient);
        }
    }
}

/**
 * Send out email notifications for all sessions where registration period has ended.
 */
function facetoface_notify_registration_ended() {
    global $CFG, $DB;

    if (empty($CFG->facetoface_session_rolesnotify)) {
        return;
    }

    $conditions = array('component' => 'mod_facetoface', 'classname' => '\mod_facetoface\task\send_notifications_task');
    $lastcron = $DB->get_field('task_scheduled', 'lastruntime', $conditions);
    $time = time();
    $params = array(
        'lastcron' => $lastcron,
        'now'      => $time
    );

    $sql = "SELECT s.*, minstart
            FROM {facetoface_sessions} s
                INNER JOIN (
                    SELECT s.id AS sessid, MIN(timestart) AS minstart
                    FROM {facetoface_sessions} s
                    INNER JOIN {facetoface_sessions_dates} d ON s.id = d.sessionid
                    GROUP BY s.id
                ) dates ON dates.sessid = s.id
            WHERE registrationtimefinish < :now
            AND registrationtimefinish >= :lastcron
            AND registrationtimefinish != 0";

    $tocheck = $DB->get_recordset_sql($sql, $params);

    foreach ($tocheck as $session) {
        $notification = new \facetoface_notification((array)$session, false);
        $notification->send_notification_registration_expired($session);
    }
}

/**
 * Returns true if the session has started, that is if one of the
 * session dates is in the past.
 *
 * @param class $session record from the facetoface_sessions table
 * @param integer $timenow current time
 */
function facetoface_has_session_started($session, $timenow) {
    if (!isset($session->sessiondates)) {
        debugging('Please update your call to facetoface_has_session_started to ensure session dates are sent', DEBUG_DEVELOPER);
        $session->sessiondates = facetoface_get_session_dates($session->id);
    }

    // Check that a date has actually been set.
    if (empty($session->sessiondates)) {
        return false;
    }

    foreach ($session->sessiondates as $date) {
        if ($date->timestart < $timenow) {
            return true;
        }
    }
    return false;
}

/**
 * Returns true if the session has started and has not yet finished.
 *
 * @param class $session record from the facetoface_sessions table
 * @param integer $timenow current time
 */
function facetoface_is_session_in_progress($session, $timenow) {
    if (empty($session->sessiondates)) {
        return false;
    }
    $startedsessions = totara_search_for_value($session->sessiondates, 'timestart', TOTARA_SEARCH_OP_LESS_THAN, $timenow);
    $unfinishedsessions = totara_search_for_value($session->sessiondates, 'timefinish', TOTARA_SEARCH_OP_GREATER_THAN, $timenow);
    if (!empty($startedsessions) && !empty($unfinishedsessions)) {
        return true;
    }
    return false;
}

/**
 * Returns true if the session is over.
 *
 * @param class $session record from the facetoface_sessions table
 * @param integer $timenow current time
 *
 * @return bool
 */
function facetoface_is_session_over($session, $timenow) {
    if (empty($session->sessiondates)) {
        return false;
    }
    $startedsessions = totara_search_for_value($session->sessiondates, 'timestart', TOTARA_SEARCH_OP_LESS_THAN, $timenow);
    $unfinishedsessions = totara_search_for_value($session->sessiondates, 'timefinish', TOTARA_SEARCH_OP_GREATER_THAN, $timenow);
    if (!empty($startedsessions) && empty($unfinishedsessions)) {
        return true;
    }
    return false;
}

/**
 * Get all of the dates for a given session
 */
function facetoface_get_session_dates($sessionid) {
    global $DB;

    $ret = array();
    $assetid = $DB->sql_group_concat($DB->sql_cast_2char('fad.assetid'), ',');
    $sql = "
        SELECT fsd.id, fsd.sessionid, fsd.sessiontimezone, fsd.timestart, fsd.timefinish, fsd.roomid, {$assetid} AS assetids
          FROM {facetoface_sessions_dates} fsd
          LEFT JOIN {facetoface_asset_dates} fad ON (fad.sessionsdateid = fsd.id)
         WHERE fsd.sessionid = :sessionid
         GROUP BY fsd.id, fsd.sessionid, fsd.sessiontimezone, fsd.timestart, fsd.timefinish, fsd.roomid
         ORDER BY timestart";
    if ($dates = $DB->get_records_sql($sql, array('sessionid' => $sessionid))) {
        $i = 0;
        foreach ($dates as $date) {
            $ret[$i++] = $date;
        }
    }

    return $ret;
}

/**
 * Get a record from the facetoface_sessions table
 *
 * @param integer $sessionid ID of the session
 */
function facetoface_get_session($sessionid) {
    global $DB;

    $sql = "SELECT s.*, m.cntdates, m.mintimestart, m.maxtimefinish
              FROM {facetoface_sessions} s
         LEFT JOIN (
                SELECT sessionid, COUNT(*) AS cntdates, MIN(timestart) AS mintimestart, MAX(timefinish) AS maxtimefinish
                  FROM {facetoface_sessions_dates}
              GROUP BY sessionid
              ) m ON m.sessionid = s.id
             WHERE s.id = ?
          ORDER BY m.mintimestart, m.maxtimefinish";

    $session = $DB->get_record_sql($sql, array($sessionid));

    if ($session) {
        $session->sessiondates = facetoface_get_session_dates($sessionid);
    }

    return $session;
}

/**
 * Get all records from facetoface_sessions for a given facetoface activity and location
 *
 * @param integer $facetofaceid ID of the activity
 * @param string $location location filter (optional). @deprecated 9.0 No longer used by internal code.
 * @param integer $roomid Room id filter (optional).
 */
function facetoface_get_sessions($facetofaceid, $location = '', $roomid = 0) {
    global $DB;

    if (!empty($location)) {
        debugging('The $location parameter in facetoface_get_sessions has been deprecated. Use Room location custom field.',
            DEBUG_DEVELOPER);
    }

    $locationjoin = '';
    $locationwhere = '';
    $locationparams = array();
    if (!empty($location)) {
        $locationsql = $DB->sql_like('rid.data', ':location', false);
        $locationparams['location'] = $location;
        $locationjoin = "
            INNER JOIN {facetoface_room_info_data} rid ON (rid.facetofaceroomid = r.id)
            INNER JOIN {facetoface_room_info_field} rif ON (rid.fieldid = rif.id AND rif.shortname = 'location')
            ";
        $locationwhere = "AND $locationsql";
    }

    $roomwhere = '';
    $roomparams = array();
    if (!empty($roomid)) {
        $roomwhere = "AND s.id IN (
             SELECT sd.sessionid
               FROM {facetoface_sessions_dates} sd
              WHERE sd.roomid = :roomid)";
        $roomparams['roomid'] = $roomid;
    }

    $sessions = $DB->get_records_sql(
            "SELECT s.*, m.mintimestart, m.maxtimefinish, m.cntdates
            FROM {facetoface_sessions} s
            LEFT JOIN (
                SELECT fsd.sessionid, COUNT(fsd.id) AS cntdates, MIN(fsd.timestart) AS mintimestart, MAX(fsd.timefinish) AS maxtimefinish
                FROM {facetoface_sessions_dates} fsd
                $locationjoin
                WHERE (1=1)
                $locationwhere
                GROUP BY fsd.sessionid
            ) m ON m.sessionid = s.id
            WHERE s.facetoface = :facetoface
            $roomwhere
            ORDER BY m.mintimestart, m.maxtimefinish",
            array_merge(array('facetoface' => $facetofaceid), $roomparams, $locationparams));

    if ($sessions) {
        foreach ($sessions as $key => $value) {
            $sessions[$key]->sessiondates = facetoface_get_session_dates($value->id);
        }
    }
    return $sessions;
}

/**
 * Get a grade for the given user from the gradebook.
 *
 * @param integer $userid        ID of the user
 * @param integer $courseid      ID of the course
 * @param integer $facetofaceid  ID of the Face-to-face activity
 *
 * @return object String grade and the time that it was graded
 */
function facetoface_get_grade($userid, $courseid, $facetofaceid) {

    $ret = new stdClass();
    $ret->grade = 0;
    $ret->dategraded = 0;

    $grading_info = grade_get_grades($courseid, 'mod', 'facetoface', $facetofaceid, $userid);
    if (!empty($grading_info->items)) {
        $ret->grade = $grading_info->items[0]->grades[$userid]->str_grade;
        $ret->dategraded = $grading_info->items[0]->grades[$userid]->dategraded;
    }

    return $ret;
}

/**
 * Get list of users attending a given session
 *
 * @access public
 * @param integer Session ID
 * @param array $status Array of statuses to include
 * @param bool $includereserved optional - if true, then include 'reserved' spaces (note this will change the array index
 *                                to signupid instead of the user id, to prevent duplicates)
 * @return array
 */
function facetoface_get_attendees($sessionid, $status = array(MDL_F2F_STATUS_BOOKED, MDL_F2F_STATUS_WAITLISTED), $includereserved = false) {
    global $DB;

    list($statussql, $statusparams) = $DB->get_in_or_equal($status);

    // Find the reservation details (and LEFT JOIN with the {user}, as that will be 0 for reservations).
    $reservedfields = '';
    $userjoin = 'JOIN';
    if ($includereserved) {
        $bookedbyusernamefields = get_all_user_name_fields(true, 'bb', null, 'bookedby');
        $reservedfields = 'su.id AS signupid, '.$bookedbyusernamefields.', bb.id AS bookedby, ';
        $userjoin = 'LEFT JOIN {user} bb ON bb.id = su.bookedby
                     LEFT JOIN';
    }

    // Get all name fields, and user identity fields.
    $usernamefields = get_all_user_name_fields(true, 'u').get_extra_user_fields_sql(true, 'u', '', get_all_user_name_fields());

    $sql = "
        SELECT
            {$reservedfields}
            u.id,
            u.idnumber,
            su.id AS submissionid,
            {$usernamefields},
            u.email,
            s.discountcost,
            su.discountcode,
            su.notificationtype,
            f.id AS facetofaceid,
            f.course,
            ss.grade,
            ss.statuscode,
            u.deleted,
            u.suspended,
            (
                SELECT MAX(timecreated)
                FROM {facetoface_signups_status} ss2
                WHERE ss2.signupid = ss.signupid AND ss2.statuscode IN (?, ?)
            ) as timesignedup,
            ss.timecreated,
            ja.id AS jobassignmentid,
            ja.fullname AS jobassignmentname
        FROM
            {facetoface} f
        JOIN
            {facetoface_sessions} s
         ON s.facetoface = f.id
        JOIN
            {facetoface_signups} su
         ON s.id = su.sessionid
        JOIN
            {facetoface_signups_status} ss
         ON su.id = ss.signupid
   LEFT JOIN
            {job_assignment} ja
         ON ja.id = su.jobassignmentid
       {$userjoin}
            {user} u
         ON u.id = su.userid
        WHERE
            s.id = ?
        AND ss.statuscode {$statussql}
        AND ss.superceded != 1
        ORDER BY u.firstname, u.lastname ASC";

    $params = array_merge(array(MDL_F2F_STATUS_BOOKED, MDL_F2F_STATUS_WAITLISTED, $sessionid), $statusparams);

    $records = $DB->get_records_sql($sql, $params);

    return $records;
}

/**
 * Get a single attendee of a session
 *
 * @access public
 * @param integer Session ID
 * @param integer User ID
 * @return false|object
 */
function facetoface_get_attendee($sessionid, $userid) {
    global $DB;

    $usernamefields = get_all_user_name_fields(true, 'u');
    $record = $DB->get_record_sql("
        SELECT
            u.id,
            su.id AS submissionid,
            {$usernamefields},
            u.email,
            s.discountcost,
            su.discountcode,
            su.notificationtype,
            f.id AS facetofaceid,
            f.course,
            ss.grade,
            ss.statuscode
        FROM
            {facetoface} f
        JOIN
            {facetoface_sessions} s
         ON s.facetoface = f.id
        JOIN
            {facetoface_signups} su
         ON s.id = su.sessionid
        JOIN
            {facetoface_signups_status} ss
         ON su.id = ss.signupid
        JOIN
            {user} u
         ON u.id = su.userid
        WHERE
            s.id = ?
        AND ss.superceded != 1
        AND u.id = ?
    ", array($sessionid, $userid));

    if (!$record) {
        return false;
    }

    return $record;
}

/**
 * Return all user fields to include in exports
 *
 * @param bool $reset If true the user fields static cache is reset
 */
function facetoface_get_userfields(bool $reset = false) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/user/lib.php');

    static $userfields = null;
    if ($userfields === null || $reset) {
        $userfields = array();

        $fieldnames = array('firstname', 'lastname', 'email', 'city',
                            'idnumber', 'institution', 'department', 'address');
        if (!empty($CFG->facetoface_export_userprofilefields)) {
            $fieldnames = array_map('trim', explode(',', $CFG->facetoface_export_userprofilefields));
        }

        $allowed_fields = user_get_default_fields();
        // Only the fields in the user table will work. Custom fields are dealt with separately.
        $allowed_fields = array_diff($allowed_fields, ['profileimageurlsmall', 'profileimageurlsmall', 'customfields', 'groups', 'roles', 'preferences', 'enrolledcourses']);
        $fieldnames = array_intersect($fieldnames, $allowed_fields);

        foreach ($fieldnames as $shortname) {
            if (get_string_manager()->string_exists($shortname, 'moodle')) {
                $userfields[$shortname] = get_string($shortname);
            } else {
                $userfields[$shortname] = $shortname;
            }
        }

        // Add custom fields.
        if (!empty($CFG->facetoface_export_customprofilefields)) {
            $customfields = array_map('trim', explode(',', $CFG->facetoface_export_customprofilefields));
            list($insql, $params) = $DB->get_in_or_equal($customfields);
            $sql = 'SELECT '.$DB->sql_concat("'customfield_'", 'f.shortname').' AS shortname, f.name
                FROM {user_info_field} f
                JOIN {user_info_category} c ON f.categoryid = c.id
                WHERE f.shortname '.$insql.'
                ORDER BY c.sortorder, f.sortorder';

            $customfields = $DB->get_records_sql_menu($sql, $params);
            if (!empty($customfields)) {
                $userfields = array_merge($userfields, $customfields);
            }
        }
    }

    return $userfields;
}

/**
 * Download the list of users attending at least one of the sessions
 * for a given facetoface activity
 *
 * @param string $facetofacename Seminar name
 * @param integer $facetofaceid Seminar ID
 * @param string $unused Previously it was $location but that was deprecated in 9 and removed in 11.
 * @param string $format Download format
 *
 * @return null
 */
function facetoface_download_attendance($facetofacename, $facetofaceid, $unused = null, $format) {
    global $CFG, $DB;

    $timenow = time();
    $timeformat = str_replace(' ', '_', get_string('strftimedate', 'langconfig'));
    $downloadfilename = clean_filename($facetofacename.'_'.userdate($timenow, $timeformat));

    $dateformat = 0;
    if ('ods' === $format) {
        // OpenDocument format (ISO/IEC 26300)
        require_once($CFG->dirroot.'/lib/odslib.class.php');
        $downloadfilename .= '.ods';
        $workbook = new MoodleODSWorkbook('-');
    } else {
        // Excel format
        require_once($CFG->dirroot.'/lib/excellib.class.php');
        $downloadfilename .= '.xls';
        $workbook = new MoodleExcelWorkbook('-');
        $dateformat = $workbook->add_format();
        $dateformat->set_num_format(MoodleExcelWorkbook::NUMBER_FORMAT_STANDARD_DATE);
    }

    $workbook->send($downloadfilename);
    $worksheet = $workbook->add_worksheet('attendance');
    $courseid = $DB->get_field('facetoface', 'course', array('id' => $facetofaceid));
    $coursecontext = context_course::instance($courseid);
    facetoface_write_worksheet_header($worksheet, $coursecontext);
    facetoface_write_activity_attendance($worksheet, $coursecontext, 1, $facetofaceid, null, '', '', $dateformat);
    $workbook->close();
    exit;
}

/**
 * Add the appropriate column headers to the given worksheet
 *
 * @param object $worksheet  The worksheet to modify (passed by reference)
 * @param object $context the course context of the facetoface instance
 * @returns integer The index of the next column
 */
function facetoface_write_worksheet_header(&$worksheet, $context) {
    $pos=0;
    $customfields = customfield_get_fields_definition('facetoface_session', array('hidden' => 0));
    foreach ($customfields as $field) {
        if (!empty($field->showinsummary)) {
            $worksheet->write_string(0, $pos++, $field->fullname);
        }
    }
    $worksheet->write_string(0, $pos++, get_string('sessionstartdateshort', 'facetoface'));
    $worksheet->write_string(0, $pos++, get_string('sessionfinishdateshort', 'facetoface'));
    $worksheet->write_string(0, $pos++, get_string('room', 'facetoface'));
    $worksheet->write_string(0, $pos++, get_string('timestart', 'facetoface'));
    $worksheet->write_string(0, $pos++, get_string('timefinish', 'facetoface'));
    $worksheet->write_string(0, $pos++, get_string('duration', 'facetoface'));
    $worksheet->write_string(0, $pos++, get_string('status', 'facetoface'));

    if ($trainerroles = facetoface_get_trainer_roles($context)) {
        foreach ($trainerroles as $role) {
            $worksheet->write_string(0, $pos++, get_string('role').': '.$role->localname);
        }
    }

    $userfields = facetoface_get_userfields();
    foreach ($userfields as $shortname => $fullname) {
        $worksheet->write_string(0, $pos++, $fullname);
    }

    $selectjobassignmentonsignupglobal = get_config(null, 'facetoface_selectjobassignmentonsignupglobal');
    if (!empty($selectjobassignmentonsignupglobal)) {
        $worksheet->write_string(0, $pos++, get_string('selectedjobassignment', 'mod_facetoface'));
    }

    $worksheet->write_string(0, $pos++, get_string('attendance', 'facetoface'));
    $worksheet->write_string(0, $pos++, get_string('datesignedup', 'facetoface'));

    return $pos;
}

/**
 * Write in the worksheet the given facetoface attendance information
 *
 * This function includes lots of custom SQL because it's otherwise
 * way too slow.
 *
 * @param object  $worksheet    Currently open worksheet
 * @param object  $coursecontext context of the course containing this f2f activity
 * @param integer $startingrow  Index of the starting row (usually 1)
 * @param integer $facetofaceid ID of the facetoface activity
 * @param string  $unused       Previously $location it was deprecated in Totara 9 and removed in Totara 11.
 * @param string  $coursename   Name of the course (optional)
 * @param string  $activityname Name of the facetoface activity (optional)
 * @param object  $dateformat   Use to write out dates in the spreadsheet
 * @returns integer Index of the last row written
 */
function facetoface_write_activity_attendance(&$worksheet, $coursecontext, $startingrow, $facetofaceid, $unused = null,
                                              $coursename, $activityname, $dateformat) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/user/lib.php');

    $trainerroles = facetoface_get_trainer_roles($coursecontext);

    // The user fields we fetch need to be broken down into those coming from the user table
    // and those coming from custom fields so that we can validate them correctly.
    $userfields = facetoface_get_userfields();
    $customfieldshortnames = array_filter(array_keys($userfields), function($value) {
        return strpos($value, 'customfield_') === 0;
    });
    $usertablefields = array_diff(array_keys($userfields), $customfieldshortnames);

    $customsessionfields = customfield_get_fields_definition('facetoface_session', array('hidden' => 0));
    $timenow = time();
    $i = $startingrow;

    $course = new stdClass();
    $course->id = $coursecontext->instanceid;

    // Fast version of "facetoface_get_attendees()" for all sessions
    $sessionsignups = array();
    $signupsql = "
        SELECT
            su.id AS submissionid,
            s.id AS sessionid,
            u.*,
            f.course AS courseid,
            f.selectjobassignmentonsignup,
            ss.grade,
            sign.timecreated,
            su.jobassignmentid
        FROM
            {facetoface} f
        JOIN
            {facetoface_sessions} s
         ON s.facetoface = f.id
        JOIN
            {facetoface_signups} su
         ON s.id = su.sessionid
        JOIN
            {facetoface_signups_status} ss
         ON su.id = ss.signupid
        LEFT JOIN
            (
            SELECT
                ss.signupid,
                MAX(ss.timecreated) AS timecreated
            FROM
                {facetoface_signups_status} ss
            INNER JOIN
                {facetoface_signups} s
             ON s.id = ss.signupid
            INNER JOIN
                {facetoface_sessions} se
             ON s.sessionid = se.id
            AND se.facetoface = $facetofaceid
            WHERE
                ss.statuscode IN (:booked,:waitlisted)
            GROUP BY
                ss.signupid
            ) sign
         ON su.id = sign.signupid
        JOIN
            {user} u
            ON u.id = su.userid
        WHERE
            f.id = :fid
        AND ss.superceded != 1
        AND ss.statuscode >= :waitlisted2
        ORDER BY
            s.id, u.firstname, u.lastname";
    $signupparams =  array(
        'booked' => MDL_F2F_STATUS_BOOKED,
        'waitlisted' => MDL_F2F_STATUS_WAITLISTED,
        'fid' => $facetofaceid,
        'waitlisted2' => MDL_F2F_STATUS_WAITLISTED
    );
    $signups = $DB->get_records_sql($signupsql, $signupparams);

    if ($signups) {
        // Get all grades at once
        $userids = array();
        foreach ($signups as $signup) {
            if ($signup->id > 0) {
                $userids[] = $signup->id;
            }
        }

        $usercustomfields = explode(',', $CFG->facetoface_export_customprofilefields);

        // Figure out which custom fields will need date/time formatting later on.
        $formatdate = array('firstaccess', 'lastaccess', 'lastlogin', 'currentlogin');
        list($cf_sql, $cf_param) = $DB->get_in_or_equal($usercustomfields);
        $sql = "SELECT " . $DB->sql_concat("'customfield_'", 'shortname') . " AS shortname
                FROM {user_info_field}
                WHERE shortname {$cf_sql}
                AND datatype = 'datetime'";
        $usercustomformats = $DB->get_records_sql($sql, $cf_param);

        $formatdate = array_merge($formatdate, array_keys($usercustomformats));

        foreach ($signups as $signup) {
            $userid = $signup->id;

            if (!empty($CFG->facetoface_export_customprofilefields)) {
                $customuserfields = facetoface_get_user_customfields($userid,
                    array_map('trim', $usercustomfields));
                foreach ($customuserfields as $fieldname => $value) {
                    if (!isset($signup->$fieldname)) {
                        $signup->$fieldname = $value;
                    }
                }
            }

            $sessionsignups[$signup->sessionid][$signup->id] = $signup;
        }
    }

    $sql = "SELECT d.id as dateid, s.id, s.capacity, d.timestart, d.timefinish, d.roomid,
                   d.sessiontimezone, s.cancelledstatus, s.registrationtimestart, s.registrationtimefinish
              FROM {facetoface_sessions} s
              JOIN {facetoface_sessions_dates} d ON s.id = d.sessionid
             WHERE s.facetoface = :fid AND d.sessionid = s.id
          ORDER BY d.timestart";

    $sessions = $DB->get_records_sql($sql, array_merge(array('fid' => $facetofaceid)));

    $i = $i - 1; // will be incremented BEFORE each row is written

    $displaytimezones = get_config(null, 'facetoface_displaysessiontimezones');

    foreach ($sessions as $session) {
        $sessionstartdate = false;
        $sessionenddate = false;
        $starttime   = get_string('wait-listed', 'facetoface');
        $finishtime  = get_string('wait-listed', 'facetoface');
        $status      = get_string('wait-listed', 'facetoface');

        $sessiontrainers = facetoface_get_trainers($session->id);

        if ($session->timestart) {
            // Display only the first date
            $sessionobj = facetoface_format_session_times($session->timestart, $session->timefinish, $session->sessiontimezone);
            $sessiontimezone = !empty($displaytimezones) ? $sessionobj->timezone : '';
            $starttime = $sessionobj->starttime . ' ' . $sessiontimezone;
            $finishtime = $sessionobj->endtime . ' ' . $sessiontimezone;

            if (method_exists($worksheet, 'write_date')) {
                // Needs the patch in MDL-20781
                $sessionstartdate = (int)$session->timestart;
                $sessionenddate = (int)$session->timefinish;
            } else {
                $sessionstartdate = $sessionobj->startdate;
                $sessionenddate = $sessionobj->enddate;
            }

            if ($session->timestart < $timenow) {
                $status = get_string('sessionover', 'facetoface');
            } else {
                $signupcount = 0;
                if (!empty($sessionsignups[$session->id])) {
                    $signupcount = count($sessionsignups[$session->id]);
                }

                // Before making any status changes, check mod_facetoface_renderer::session_status_table_cell first.
                if (!empty($session->cancelledstatus)) {
                    $status = get_string('bookingsessioncancelled', 'facetoface');
                } else if ($signupcount >= $session->capacity) {
                    $status = get_string('bookingfull', 'facetoface');
                } else if (!empty($session->registrationtimestart) && $session->registrationtimestart > $timenow) {
                    $status = get_string('registrationnotopen', 'facetoface');
                } else if (!empty($session->registrationtimefinish) && $timenow > $session->registrationtimefinish) {
                    $status = get_string('registrationclosed', 'facetoface');
                } else {
                    $status = get_string('bookingopen', 'facetoface');
                }
            }
        }

        $room = facetoface_get_room($session->roomid);
        $roomstring = '';
        if (!empty($room)) {
            $roomstring = facetoface_room_to_string($room);
        }

        if (!empty($sessionsignups[$session->id])) {
            foreach ($sessionsignups[$session->id] as $attendee) {
                $i++; $j=0;
                // Custom fields.
                $customfieldsdata = customfield_get_data($session, 'facetoface_session', 'facetofacesession', false);
                foreach ($customsessionfields as $customfield) {
                    if (empty($customfield->showinsummary)) {
                        continue; // Skip.
                    }
                    if (array_key_exists($customfield->shortname, $customfieldsdata)) {
                        $data = format_string($customfieldsdata[$customfield->shortname]);
                    } else {
                        $data = '-';
                    }
                    $worksheet->write_string($i, $j++, $data);
                }

                if (empty($sessionstartdate)) {
                    $worksheet->write_string($i, $j++, $status); // Session start date.
                    $worksheet->write_string($i, $j++, $status); // Session end date.
                }
                else {
                    if (method_exists($worksheet, 'write_date')) {
                        $worksheet->write_date($i, $j++, $sessionstartdate, $dateformat);
                        $worksheet->write_date($i, $j++, $sessionenddate, $dateformat);
                    }
                    else {
                        $worksheet->write_string($i, $j++, $sessionstartdate);
                        $worksheet->write_string($i, $j++, $sessionenddate);
                    }
                }

                $worksheet->write_string($i, $j++, $roomstring);
                $worksheet->write_string($i, $j++, $starttime);
                $worksheet->write_string($i, $j++, $finishtime);
                $worksheet->write_string($i, $j++, format_time((int)$session->timestart - (int)$session->timefinish));
                $worksheet->write_string($i, $j++, $status);

                if ($trainerroles) {
                    foreach (array_keys($trainerroles) as $roleid) {
                        if (!empty($sessiontrainers[$roleid])) {
                            $trainers = array();
                            foreach ($sessiontrainers[$roleid] as $trainer) {
                                $trainers[] = fullname($trainer);
                            }

                            $trainers = implode(', ', $trainers);
                        }
                        else {
                            $trainers = '-';
                        }

                        $worksheet->write_string($i, $j++, $trainers);
                    }
                }

                // Filter out the attendee's information that the exporting user is not
                // allowed to see, based on permissions and config settings.
                // Other properties of $attendee will be used later, but this determines
                // which $userfields we'll show.
                $user = user_get_user_details($attendee, $course, $usertablefields);

                foreach ($userfields as $shortname => $fullname) {
                    $value = '-';
                    if (!empty($user[$shortname])) {
                        $value = $user[$shortname];
                    } else if (in_array($shortname, $customfieldshortnames) && !empty($attendee->{$shortname})) {
                        $value = $attendee->{$shortname};
                    }

                    if (in_array($shortname, $formatdate)) {
                        if (method_exists($worksheet, 'write_date')) {
                            $worksheet->write_date($i, $j++, (int)$value, $dateformat);
                        } else {
                            $worksheet->write_string($i, $j++, userdate($value, get_string('strftimedate', 'langconfig')));
                        }
                    } else {
                        $worksheet->write_string($i,$j++,$value);
                    }
                }

                $selectjobassignmentonsignupglobal = get_config(null, 'facetoface_selectjobassignmentonsignupglobal');
                $selectjobassignmentonsignupsession = $sessionsignups[$attendee->sessionid][$attendee->id]->selectjobassignmentonsignup;
                if (!empty($selectjobassignmentonsignupglobal) && !empty($selectjobassignmentonsignupsession)) {
                    if (!empty($attendee->jobassignmentid)) {
                        $jobassignment = \totara_job\job_assignment::get_with_id($attendee->jobassignmentid);
                        if ($jobassignment == null || $jobassignment->userid != $attendee->id) {
                            // Error!!!
                        }
                        $label = position::job_position_label($jobassignment);
                    } else {
                        $label = '';
                    }
                    $worksheet->write_string($i, $j++, $label);
                }
                $worksheet->write_string($i,$j++,$attendee->grade);

                if (method_exists($worksheet,'write_date')) {
                    $worksheet->write_date($i, $j++, (int)$attendee->timecreated, $dateformat);
                } else {
                    $signupdate = userdate($attendee->timecreated, get_string('strftimedatetime', 'langconfig'));
                    if (empty($signupdate)) {
                        $signupdate = '-';
                    }
                    $worksheet->write_string($i,$j++, $signupdate);
                }

                if (!empty($coursename)) {
                    $worksheet->write_string($i, $j++, $coursename);
                }
                if (!empty($activityname)) {
                    $worksheet->write_string($i, $j++, $activityname);
                }
            }
        }
        else {
            // No one is signed-up, so let's just print the basic info.
            $i++; $j=0;

            // Custom fields.
            $customfieldsdata = customfield_get_data($session, 'facetoface_session', 'facetofacesession', false);
            foreach ($customsessionfields as $customfield) {
                if (empty($customfield->showinsummary)) {
                    continue;
                }

                if (array_key_exists($customfield->shortname, $customfieldsdata)) {
                    $data = format_string($customfieldsdata[$customfield->shortname]);
                } else {
                    $data = '-';
                }

                $worksheet->write_string($i, $j++, $data);
            }

            if (empty($sessionstartdate)) {
                $worksheet->write_string($i, $j++, $status); // Session start date.
                $worksheet->write_string($i, $j++, $status); // Session end date.
            } else {
                if (method_exists($worksheet, 'write_date')) {
                    $worksheet->write_date($i, $j++, $sessionstartdate, $dateformat);
                    $worksheet->write_date($i, $j++, $sessionenddate, $dateformat);
                }
                else {
                    $worksheet->write_string($i, $j++, $sessionstartdate);
                    $worksheet->write_string($i, $j++, $sessionenddate);
                }
            }

            $worksheet->write_string($i, $j++, $roomstring);
            $worksheet->write_string($i, $j++, $starttime);
            $worksheet->write_string($i, $j++, $finishtime);
            $worksheet->write_string($i, $j++, format_time((int)$session->timestart - (int)$session->timefinish));
            $worksheet->write_string($i, $j++, $status);

            if ($trainerroles) {
                foreach (array_keys($trainerroles) as $roleid) {
                    if (!empty($sessiontrainers[$roleid])) {
                        $trainers = array();
                        foreach ($sessiontrainers[$roleid] as $trainer) {
                            $trainers[] = fullname($trainer);
                        }

                        $trainers = implode(', ', $trainers);
                    }
                    else {
                        $trainers = '-';
                    }

                    $worksheet->write_string($i, $j++, $trainers);
                }
            }

            foreach ($userfields as $unused) {
                $worksheet->write_string($i,$j++,'-');
            }
            // Grade/attendance
            $worksheet->write_string($i,$j++,'-');
            // Date signed up
            $worksheet->write_string($i,$j++,'-');

            if (!empty($coursename)) {
                $worksheet->write_string($i, $j++, $coursename);
            }
            if (!empty($activityname)) {
                $worksheet->write_string($i, $j++, $activityname);
            }
        }
    }

    return $i;
}

/**
 * Return an object with all values for a user's custom fields.
 *
 * This is about 15 times faster than the custom field API.
 *
 * @param array $fieldstoinclude Limit the fields returned/cached to these ones (optional)
 */
function facetoface_get_user_customfields($userid, $fieldstoinclude=null) {
    global $CFG, $DB;

    // Cache all lookup
    static $customfields = null;
    if (null == $customfields) {
        $customfields = array();
    }

    if (!empty($customfields[$userid])) {
        return $customfields[$userid];
    }

    $ret = new stdClass();

    $sql = 'SELECT '.$DB->sql_concat("'customfield_'", 'uif.shortname').' AS shortname, id.data
              FROM {user_info_field} uif
              JOIN {user_info_data} id ON id.fieldid = uif.id
              JOIN {user_info_category} c ON uif.categoryid = c.id
              WHERE id.userid = ? ';
    $params = array($userid);
    if (!empty($fieldstoinclude)) {
        list($insql, $inparams) = $DB->get_in_or_equal($fieldstoinclude);
        $sql .= ' AND uif.shortname '.$insql;
        $params = array_merge($params, $inparams);
    }
    $sql .= ' ORDER BY c.sortorder, uif.sortorder';

    $customfields = $DB->get_records_sql($sql, $params);
    foreach ($customfields as $field) {
        $fieldname = $field->shortname;
        $ret->$fieldname = $field->data;
    }

    $customfields[$userid] = $ret;
    return $ret;
}


/**
 * Add a record to the facetoface submissions table and sends out an
 * email confirmation
 *
 * @param object $session record from the facetoface_sessions table
 * @param object $facetoface record from the facetoface table
 * @param object $course record from the course table
 * @param string $discountcode code entered by the user
 * @param integer $notificationtype type of notifications to send to user
 * @see {{MDL_F2F_INVITE}}
 * @param integer $statuscode Status code to set
 * @param integer|bool $userid user to signup
 * @param bool $notifyuser whether or not to send an email confirmation
 * @param object $fromuser User object describing who the email is from.
 * @param \totara_job\job_assignment $jobassignment object containing the selected job assignment
 * @param int $managerid Manager id selected by user
 * @return bool
 */
function facetoface_user_signup($session, $facetoface, $course, $discountcode,
                                $notificationtype, $statuscode, $userid = false,
                                $notifyuser = true, $fromuser = null, $jobassignment = null, $managerid = null) {

    global $DB, $USER;

    // Get user id
    if (!$userid) {
        $userid = $USER->id;
    }

    $timenow = time();

    // Check to see if a signup already exists
    if ($existingsignup = $DB->get_record('facetoface_signups', array('sessionid' => $session->id, 'userid' => $userid))) {
        $usersignup = $existingsignup;
    } else {
        // Otherwise, prepare a signup object
        $usersignup = new stdClass();
        $usersignup->sessionid = $session->id;
        $usersignup->userid = $userid;
    }

    $usersignup->bookedby = $userid == $USER->id ? 0 : $USER->id;
    $usersignup->mailedreminder = 0;
    $usersignup->notificationtype = $notificationtype;

    // If the selected job assignment information hasn't been supplied then we need to try to default it
    // we won't throw errors if it's not present as all we can do is throw exceptions that won't be handled and may break cron
    // in theory the only routes here that don't go through facetoface_user_import handle reservations which handled by a manager
    // or come from a wait list and so a job assignment should always be available.
    $selectjobassignmentonsignupglobal = get_config(null, 'facetoface_selectjobassignmentonsignupglobal');
    $jobassignmentrequired = !empty($selectjobassignmentonsignupglobal) && !empty($facetoface->selectjobassignmentonsignup);

    if ($jobassignmentrequired) {
        if ($jobassignment === null) {
            $jobassignment = \totara_job\job_assignment::get_first($userid, false);
        }

        if (!empty($jobassignment)) {
            $usersignup->jobassignmentid = $jobassignment->id;
        }
    }

    // If no job assignment is wanted by the face to face or none is provided then record all info as null.
    if (!$jobassignmentrequired || empty($jobassignment)) {
        $usersignup->jobassignmentid = null;
    }

    $managerselect = get_config(null, 'facetoface_managerselect');
    if ($managerselect && !empty($managerid)) {
        $usersignup->managerid = $managerid;
    }

    $usersignup->discountcode = trim(strtoupper($discountcode));
    if (empty($usersignup->discountcode)) {
        $usersignup->discountcode = null;
    }

    // Update/insert the signup record
    if (!empty($usersignup->id)) {
        $success = $DB->update_record('facetoface_signups', $usersignup);
    } else {
        $usersignup->id = $DB->insert_record('facetoface_signups', $usersignup);
        $success = (bool)$usersignup->id;
    }

    if (!$success) {
        print_error('error:couldnotupdatef2frecord', 'facetoface');
    }

    // Work out which status to use

    // If approval not required or self approval enabled.
    if (!facetoface_approval_required($facetoface)) {
        $new_status = $statuscode;
    } else {
        // Manager approval is required.
        // Before we can accept the new status we need to check if the user must first be approved by there manager,
        // Get current status (if any) so that we can decide what to do.
        $currentstatus =  $DB->get_field('facetoface_signups_status', 'statuscode', array('signupid' => $usersignup->id, 'superceded' => 0));
        if (empty($currentstatus)) {
            // If they don't already have a status then they must require approval.
            $currentstatus = MDL_F2F_STATUS_REQUESTED;
        }
        // The following statuses have already received approval.
        $alreadyapproved = array(
            MDL_F2F_STATUS_APPROVED, // The user has just been approved, yay.
            MDL_F2F_STATUS_WAITLISTED, // The user is waitlisted - they must have been approved already.
            MDL_F2F_STATUS_BOOKED // The user is booked - they must have been approved already.
        );
        // The following statuses still need to seek approval.
        $mustapprove = array(
            MDL_F2F_STATUS_REQUESTED, // They are currently waiting for approval already.
            MDL_F2F_STATUS_USER_CANCELLED, // They cancelled and are coming back again - seek approval once more.
            MDL_F2F_STATUS_SESSION_CANCELLED, // They session was cancelled but we are back? seek approval to be sure.
            MDL_F2F_STATUS_DECLINED, // Persistent learner, won't take no for an answer
        );
        if (in_array($currentstatus, $alreadyapproved)) {
            // The user is an already approved state - no need to seek there approval again.
            // We will use the given status.
            $new_status = $statuscode;
        } else if (in_array($currentstatus, $mustapprove)) {
            // The user is not in an approved state, in which case they must be sent through for approval.
            $new_status = MDL_F2F_STATUS_REQUESTED;
        } else {
            // Hmm this is a little worrying I wonder what they are doing.
            // As we don't know let us throw a debugging notice and take a safe path.
            // This may lead to notifications.
            debugging('Unexpected status encountered when updated users facetoface signup', DEBUG_DEVELOPER);
            $new_status = MDL_F2F_STATUS_REQUESTED;
        }
    }

    // Update status.
    if (!facetoface_update_signup_status($usersignup->id, $new_status, $USER->id)) {
        print_error('error:f2ffailedupdatestatus', 'facetoface');
    }

    // Add to user calendar -- if facetoface usercalentry is set to true
    if ($facetoface->usercalentry && in_array($new_status, array(MDL_F2F_STATUS_BOOKED, MDL_F2F_STATUS_WAITLISTED))) {
        facetoface_add_session_to_calendar($session, $facetoface, 'user', $userid, 'booking');
    }

    // If session has already started, do not send a notification
    if (facetoface_has_session_started($session, $timenow)) {
        $notifyuser = false;
    }

    // Send notification.
    $notifytype = ((int)$notificationtype == MDL_F2F_NONE ? false : true);
    $session->notifyuser = $notifyuser && $notifytype;

    switch ($new_status) {
        case MDL_F2F_STATUS_BOOKED:
            $error = facetoface_send_confirmation_notice($facetoface, $session, $userid, $notificationtype, false, $fromuser);
            break;

        case MDL_F2F_STATUS_WAITLISTED:
            $error = facetoface_send_confirmation_notice($facetoface, $session, $userid, $notificationtype, true);
            break;

        case MDL_F2F_STATUS_REQUESTED:
            if ($facetoface->approvaltype == APPROVAL_ROLE) {
                // Send the booking requested message to the user.
                $error = facetoface_send_rolerequest_notice($facetoface, $session, $userid);
            } else if ($facetoface->approvaltype == APPROVAL_ADMIN) {
                // Send the booking requested message to the user.
                $error = facetoface_send_adminrequest_notice($facetoface, $session, $userid);
            } else {
                $error = facetoface_send_request_notice($facetoface, $session, $userid);
            }
            break;
    }

    if (!empty($error)) {
        if ($error == 'userdoesnotexist') {
            print_error($error, 'facetoface');
        } else {
            // Don't fail if email isn't sent, just display a warning
            debugging(get_string($error, 'facetoface'), DEBUG_NORMAL);
        }
    }

    if ($session->notifyuser) {
        if (!$DB->update_record('facetoface_signups', $usersignup)) {
            print_error('error:couldnotupdatef2frecord', 'facetoface');
        }
    }

    // Update course completion.
    if (in_array($new_status, array(MDL_F2F_STATUS_BOOKED, MDL_F2F_STATUS_WAITLISTED))) {

        $completion = new completion_info($course);
        if ($completion->is_enabled()) {

            $ccdetails = array(
                'course' => $course->id,
                'userid' => $userid,
            );

            $cc = new completion_completion($ccdetails);
            $cc->mark_inprogress($timenow);
        }
    }

    facetoface_withdraw_interest($facetoface, $userid);

    // Add log entry.
    $cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id);
    $context = context_module::instance($cm->id);
    \mod_facetoface\event\session_signup::create_from_instance($usersignup, $context)->trigger();

    return true;
}

/**
 * Update the signup status of a particular signup
 *
 * @param integer $signupid ID of the signup to be updated
 * @param integer $statuscode Status code to be updated to
 * @param integer $createdby User ID of the user causing the status update
 * @param int $grade Grade
 * @param bool $usetransaction Set to true if database transactions are to be used
 *
 * @returns integer ID of newly created signup status, or false
 *
 */
function facetoface_update_signup_status($signupid, $statuscode, $createdby, $grade=NULL) {
    global $DB;
    $timenow = time();

    $signupstatus = new stdClass();
    $signupstatus->signupid = $signupid;
    $signupstatus->statuscode = $statuscode;
    $signupstatus->createdby = $createdby;
    $signupstatus->timecreated = $timenow;
    $signupstatus->grade = $grade;
    $signupstatus->superceded = 0;
    $signupstatus->mailed = 0;

    if ($statusid = $DB->insert_record('facetoface_signups_status', $signupstatus)) {
        // mark any previous signup_statuses as superceded
        $where = "signupid = ? AND ( superceded = 0 OR superceded IS NULL ) AND id != ?";
        $whereparams = array($signupid, $statusid);
        $DB->set_field_select('facetoface_signups_status', 'superceded', 1, $where, $whereparams);

        // Check for completions.
        $sql = "SELECT f2f.id, f2f.course, f2fs.userid
                FROM {facetoface_signups} f2fs
                    LEFT JOIN {facetoface_sessions} f2fses ON (f2fses.id = f2fs.sessionid)
                    LEFT JOIN {facetoface} f2f ON (f2f.id = f2fses.facetoface)
                WHERE f2fs.id = ?";

        $status = $DB->get_record_sql($sql, array($signupid));
        facetoface_set_completion($status, $status->userid, COMPLETION_UNKNOWN);

        // Get course module so we can get context for event.
        $signupsql = "SELECT f2fsignup.*, cm.id as cmid
            FROM {facetoface_signups} f2fsignup
            JOIN {facetoface_sessions} f2fsess
                ON f2fsignup.sessionid = f2fsess.id
            JOIN {course_modules} cm
                ON cm.instance = f2fsess.facetoface
            JOIN {modules} m
                ON m.id = cm.module AND m.name = 'facetoface'
            WHERE f2fsignup.id = :signupid";

        $signup = $DB->get_record_sql($signupsql, array('signupid' => $signupid));
        $context = context_module::instance($signup->cmid);
        $signupstatus->id = $statusid;

        unset($signup->cmid);

        \mod_facetoface\event\signup_status_updated::create_from_signup($signupstatus, $context, $signup)->trigger();

        return $statusid;
    } else {
        return false;
    }
}

/**
 * Cancel a user who signed up earlier
 *
 * @param stdClass $session    Record from the facetoface_sessions table
 * @param integer $userid      ID of the user to remove from the session
 * @param bool $forcecancel    Forces cancellation of sessions that have already occurred
 * @param string $errorstr     Passed by reference. For setting error string in calling function
 * @param string $cancelreason Optional justification for cancelling the signup
 */
function facetoface_user_cancel($session, $userid=false, $forcecancel=false, &$errorstr=null, $cancelreason='') {
    if (!$userid) {
        global $USER;
        $userid = $USER->id;
    }

    // if $forcecancel is set, cancel session even if already occurred
    // used by facetotoface_delete_session()
    if (!$forcecancel) {
        $timenow = time();
        // don't allow user to cancel a session that has already occurred
        if (facetoface_has_session_started($session, $timenow)) {
            $errorstr = get_string('error:eventoccurred', 'facetoface');
            return false;
        }
    }

    if (facetoface_user_cancel_submission($session->id, $userid, $cancelreason)) {
        facetoface_remove_session_from_calendar($session, 0, $userid);

        facetoface_update_attendees($session);

        return true;
    }

    $errorstr = get_string('error:cancelbooking', 'facetoface');
    return false;
}


/**
 * Returns true if the user has registered for a session in the given
 * facetoface activity
 *
 * @global class $USER used to get the current userid
 * @param int $facetofaceid
 * @param int $sessionid session id if facetoface allows multiple sessions
 * @returns integer The session id that we signed up for, false otherwise
 */
function facetoface_check_signup($facetofaceid, $sessionid = null) {

    global $USER;

    if ($submissions = facetoface_get_user_submissions($facetofaceid, $USER->id, MDL_F2F_STATUS_REQUESTED, MDL_F2F_STATUS_FULLY_ATTENDED, $sessionid)) {
        return reset($submissions)->sessionid;
    } else {
        return false;
    }
}

/**
 * Mark the fact that the user attended the facetoface session by
 * giving that user a grade of 100
 *
 * @param array $data array containing the sessionid under the 's' key
 *                    and every submission ID to mark as attended
 *                    under the 'submissionid_XXXX' keys where XXXX is
 *                     the ID of the signup
 */
function facetoface_take_attendance($data) {
    global $USER, $DB;

    $sessionid = $data->s;

    // Load session
    if (!$session = facetoface_get_session($sessionid)) {
        error_log('F2F: Could not load facetoface session');
        return false;
    }

    // Check facetoface has finished
    if ($session->mintimestart && !facetoface_has_session_started($session, time())) {
        error_log('F2F: Can not take attendance for a session that has not yet started');
        return false;
    }

    // Record the selected attendees from the user interface - the other attendees will need their grades set
    // to zero, to indicate non attendance, but only the ticked attendees come through from the web interface.
    // Hence the need for a diff
    $selectedsubmissionids = array();

    // FIXME: This is not very efficient, we should do the grade
    // query outside of the loop to get all submissions for a
    // given Face-to-face ID, then call
    // facetoface_grade_item_update with an array of grade
    // objects.
    foreach ($data as $key => $value) {

        $submissionidcheck = substr($key, 0, 13);
        if ($submissionidcheck == 'submissionid_') {
            $submissionid = substr($key, 13);
            $selectedsubmissionids[$submissionid]=$submissionid;

            if (!$DB->record_exists('facetoface_signups', array('id' => $submissionid, 'sessionid' => $session->id))) {
                // The data is inconsistent, hacker?
                error_log("F2F: could not mark signup id '$submissionid' because it does not match session id $session->id");
                continue;
            }

            // Update status
            switch ($value) {

                case MDL_F2F_STATUS_NO_SHOW:
                    $grade = 0;
                    break;

                case MDL_F2F_STATUS_PARTIALLY_ATTENDED:
                    $grade = 50;
                    break;

                case MDL_F2F_STATUS_FULLY_ATTENDED:
                    $grade = 100;
                    break;

                default:
                    // This use has not had attendance set
                    // Jump to the next item in the foreach loop
                    continue 2;
            }

            facetoface_update_signup_status($submissionid, $value, $USER->id, $grade);

            if (!facetoface_take_individual_attendance($submissionid, $grade)) {
                error_log("F2F: could not mark '$submissionid' as ".$value);
                return false;
            }
        }
    }

    return true;
}

/**
 * Mark users' booking requests as declined or approved
 *
 * @param array $data array containing the sessionid under the 's' key
 *                    and an array of request approval/denies
 * @return bool|string[] returns false if there are issues loading the
 *                  data needed to process requests or an array of errors if there
 *                  are issues with the attendee or the request doesn't exist.
 */
function facetoface_approve_requests($data) {
    global $USER, $DB;

    // Check request data
    if (empty($data->requests) || !is_array($data->requests)) {
        error_log('F2F: No request data supplied');
        return false;
    }

    $sessionid = $data->s;

    list($session, $facetoface, $course, $cm, $context) = facetoface_get_env_session($sessionid);

    $approved = array();
    $rejected = array();
    $errors = array();

    // Loop through requests
    foreach ($data->requests as $key => $value) {

        // Check key/value
        if (!is_numeric($key) || !is_numeric($value)) {
            continue;
        }

        // Load user submission
        if (!$attendee = facetoface_get_attendee($sessionid, $key)) {
            $errors[$attendee->id] = 'approverinactive';
            continue;
        }

        // Double-check request exists and not already approved or declined.
        $params = array(
            'signupid' => $attendee->submissionid,
            'superceded' => 0
        );
        if ($signupstatus = $DB->get_record('facetoface_signups_status', $params)) {
            if ($signupstatus->statuscode == MDL_F2F_STATUS_REQUESTED || $signupstatus->statuscode == MDL_F2F_STATUS_REQUESTEDADMIN) {
                $currentstatus = $signupstatus->statuscode;
            } else {
                $errors[$attendee->id] = 'approvalinvalidstatus';
                continue;
            }
        } else {
            $errors[$attendee->id] = 'approvalinvalidstatus';
            continue;
        }

        // Update status
        switch ($value) {

            // Decline
            case 1:
                facetoface_update_signup_status(
                        $attendee->submissionid,
                        MDL_F2F_STATUS_DECLINED,
                        $USER->id
                );

                // Declined users.
                $rejected[$attendee->id] = $attendee->id;

                // Send a decline notice to the user.
                facetoface_send_decline_notice($facetoface, $session, $attendee->id);
                break;

            // Approve
            case 2:
                if ($facetoface->approvaltype == APPROVAL_ADMIN && !facetoface_is_adminapprover($USER->id, $facetoface)) {
                    // Non-admin approves - now need admin approval
                    // The user is on the first step of the 2 step approval process.
                    facetoface_update_signup_status(
                        $attendee->submissionid,
                        MDL_F2F_STATUS_REQUESTEDADMIN,
                        $USER->id
                    );
                } else {
                    // Check if there is capacity
                    if (facetoface_session_has_capacity($session, $context)) {
                        $facetoface_allowwaitlisteveryone = get_config(null, 'facetoface_allowwaitlisteveryone');
                        if (!empty($facetoface_allowwaitlisteveryone) && $session->waitlisteveryone) {
                            // If waitlist everyone is set then send all users to waitlist.
                            $status = MDL_F2F_STATUS_WAITLISTED;
                        } else if ($session->cntdates) {
                            // If there is a session date/time set then user is booked.
                            $status = MDL_F2F_STATUS_BOOKED;
                        } else {
                            // If not then they are waitlisted.
                            $status = MDL_F2F_STATUS_WAITLISTED;
                        }
                    } else {
                        if ($session->allowoverbook) {
                            $status = MDL_F2F_STATUS_WAITLISTED;
                        } else {
                            $url = new moodle_url('/mod/facetoface/attendees.php',
                                array('s' => $sessionid, 'action' => 'approvalrequired'));
                            totara_set_notification(get_string('error:cannotapprovefull', 'facetoface'), $url);
                        }
                    }

                    facetoface_update_signup_status(
                        $attendee->submissionid,
                        MDL_F2F_STATUS_APPROVED,
                        $USER->id
                    );

                    // Signup user
                    if (!facetoface_user_signup(
                            $session,
                            $facetoface,
                            $course,
                            $attendee->discountcode,
                            $attendee->notificationtype,
                            $status,
                            $attendee->id,
                            true,
                            null
                        )) {
                        break;
                    }

                    // Approved users.
                    $approved[$attendee->id] = $attendee->id;
                    break;
                }
            case 0:
            default:
                // Change nothing
                break;
        }
    }

    // Trigger events for approving or declining request in this session
    if (!empty($approved)) {
        $data = array('sessionid' => $session->id, 'userids' => implode(', ', $approved));
        \mod_facetoface\event\booking_requests_approved::create_from_data($data, $context)->trigger();
    }
    if (!empty($rejected)) {
        $data = array('sessionid' => $session->id, 'userids' => implode(', ', $rejected));
        \mod_facetoface\event\booking_requests_rejected::create_from_data($data, $context)->trigger();
    }

    if (!empty($errors)) {
        return $errors;
    }

    return true;
}

/*
 * Set the grading for an individual submission, to either 0 or 100 to indicate attendance
 * @param $submissionid The id of the submission in the database
 * @param $grading Grade to set
 */
function facetoface_take_individual_attendance($submissionid, $grading) {
    global $USER, $CFG, $DB;

    $timenow = time();

    $record = $DB->get_record_sql("SELECT f.*, s.userid
                                FROM {facetoface_signups} s
                                JOIN {facetoface_sessions} fs ON s.sessionid = fs.id
                                JOIN {facetoface} f ON f.id = fs.facetoface
                                JOIN {course_modules} cm ON cm.instance = f.id
                                JOIN {modules} m ON m.id = cm.module
                                WHERE s.id = ? AND m.name='facetoface'",
                            array($submissionid));

    $grade = new stdclass();
    $grade->userid = $record->userid;
    $grade->rawgrade = $grading;
    $grade->rawgrademin = 0;
    $grade->rawgrademax = 100;
    $grade->timecreated = $timenow;
    $grade->timemodified = $timenow;
    $grade->usermodified = $USER->id;

    return facetoface_grade_item_update($record, $grade);
}

/**
 * Used in many places to obtain properly-formatted session date and time info
 *
 * @param int $start a start time Unix timestamp
 * @param int $end an end time Unix timestamp
 * @param string $tz a session timezone
 * @return object Formatted date, start time, end time and timezone info
 */
function facetoface_format_session_times($start, $end, $tz) {

    $displaytimezones = get_config(null, 'facetoface_displaysessiontimezones');

    $formattedsession = new stdClass();
    if (empty($tz) or empty($displaytimezones)) {
        $targetTZ = core_date::get_user_timezone();
    } else {
        $targetTZ = core_date::get_user_timezone($tz);
    }

    $formattedsession->startdate = userdate($start, get_string('strftimedate', 'langconfig'), $targetTZ);
    $formattedsession->starttime = userdate($start, get_string('strftimetime', 'langconfig'), $targetTZ);
    $formattedsession->enddate = userdate($end, get_string('strftimedate', 'langconfig'), $targetTZ);
    $formattedsession->endtime = userdate($end, get_string('strftimetime', 'langconfig'), $targetTZ);
    if (empty($displaytimezones)) {
        $formattedsession->timezone = '';
    } else {
        $formattedsession->timezone = core_date::get_localised_timezone($targetTZ);
    }
    return $formattedsession;
}
/**
 * Called when viewing course page.
 *
 * @param cm_info $coursemodule
 */
function facetoface_cm_info_view(cm_info $coursemodule) {
    global $USER, $DB, $PAGE;
    $output = '';

    if (!($facetoface = $DB->get_record('facetoface', array('id' => $coursemodule->instance)))) {
        return null;
    }

    $coursemodule->set_name($facetoface->name);

    $contextmodule = context_module::instance($coursemodule->id);
    if (!has_capability('mod/facetoface:view', $contextmodule)) {
        return null; // Not allowed to view this activity.
    }
    // Can view attendees.
    $viewattendees = has_capability('mod/facetoface:viewattendees', $contextmodule);
    $editevents = has_capability('mod/facetoface:editevents', $contextmodule);
    // Can see "view all sessions" link even if activity is hidden/currently unavailable.
    $iseditor = has_any_capability(array('mod/facetoface:viewattendees', 'mod/facetoface:editevents',
                                        'mod/facetoface:addattendees', 'mod/facetoface:addattendees',
                                        'mod/facetoface:takeattendance'), $contextmodule);
    // Other variables that will be required by calls further down to print_session_list_table.
    $displaytimezones = get_config(null, 'facetoface_displaysessiontimezones');
    $reserveinfo = array();

    $timenow = time();

    $strviewallsessions = get_string('viewallsessions', 'facetoface');
    $sessions_url = new moodle_url('/mod/facetoface/view.php', array('f' => $facetoface->id));
    $htmlviewallsessions = html_writer::link($sessions_url, $strviewallsessions, array('class' => 'f2fsessionlinks f2fviewallsessions', 'title' => $strviewallsessions));

    $alreadydeclaredinterest = facetoface_user_declared_interest($facetoface);
    $declareinterest_enable = $alreadydeclaredinterest || facetoface_activity_can_declare_interest($facetoface);
    $declareinterest_label = $alreadydeclaredinterest ? get_string('declareinterestwithdraw', 'facetoface') : get_string('declareinterest', 'facetoface');
    $declareinterest_url = new moodle_url('/mod/facetoface/interest.php', array('f' => $facetoface->id));
    $declareinterest_link = html_writer::link($declareinterest_url, $declareinterest_label, array('class' => 'f2fsessionlinks f2fviewallsessions', 'title' => $declareinterest_label));

    // User has signedup for the instance.
    if (facetoface_has_unarchived_signups($facetoface->id, $USER->id)) {
        $submissions = facetoface_get_user_submissions($facetoface->id, $USER->id);
        if (!$facetoface->multiplesessions) {
            // First submission only.
            $submissions = array(array_shift($submissions));
        }

        $sessions = array();
        foreach ($submissions as $submission) {
            if ($session = facetoface_get_session($submission->sessionid)) {

                if (facetoface_is_session_over($session, $timenow)) {
                    continue;
                }

                $session->bookedsession = $submission;
                $sessions[$session->id] = $session;
            }
        }

        // If the user can sign up for multiple events, we should show all upcoming events in this seminar.
        // Otherwise it doesn't make sense to do so because the user has already signedup for the instance.
        if ($facetoface->multiplesessions) {
            $allsessions = facetoface_get_sessions($facetoface->id);
            $numberofeventstodisplay = isset($facetoface->display) ? (int)$facetoface->display : 0;
            $index = 0;
            foreach ($allsessions as $id => $session) {
                if (array_key_exists($id, $sessions)) {
                    continue;
                }
                // Don't show events that are over.
                if (facetoface_is_session_over($session, $timenow)) {
                    continue;
                }

                // Displaying the seminar's event base on the config ($facetoface->display) within seminar setting.
                // Break the loop, if the number of events ($index) reaches to the number from config ($numberofeventstodisplay)
                if ($index == $numberofeventstodisplay) {
                    break;
                }
                $sessions[$session->id] = $session;
                $index++;
            }
        }

        if (!empty($facetoface->managerreserve)) {
            // Include information about reservations when drawing the list of sessions.
            $reserveinfo = facetoface_can_reserve_or_allocate($facetoface, $sessions, $contextmodule);
        }

        /** @var mod_facetoface_renderer $f2frenderer */
        $f2frenderer = $PAGE->get_renderer('mod_facetoface');
        $f2frenderer->setcontext($contextmodule);
        $output .= $f2frenderer->print_session_list_table($sessions, $viewattendees, $editevents,
            $displaytimezones, $reserveinfo, $PAGE->url, true, false);

        // Add "view all sessions" row to table.
        $output .= $htmlviewallsessions;

        if ($declareinterest_enable) {
            $output .= $declareinterest_link;
        }
    } else if ($sessions = facetoface_get_sessions($facetoface->id)) {
        if ($facetoface->display > 0) {
            foreach($sessions as $id => $session) {
                if (facetoface_is_session_over($session, $timenow)) {
                    // We only want upcoming sessions (or those with no date set).
                    // For now, we've cut down the sessions to loop through to just those displayed.
                    // Todo: we need a version of facetoface_get_sessions that will return only upcoming in the first place.
                    unset($sessions[$id]);
                }
            }

            // Limit number of sessions display. $sessions is in order of start time.
            $displaysessions = array_slice($sessions, 0, $facetoface->display, true);

            if (!empty($facetoface->managerreserve)) {
                // Include information about reservations when drawing the list of sessions.
                $reserveinfo = facetoface_can_reserve_or_allocate($facetoface, $displaysessions, $contextmodule);
            }

            /** @var mod_facetoface_renderer $f2frenderer */
            $f2frenderer = $PAGE->get_renderer('mod_facetoface');
            $f2frenderer->setcontext($contextmodule);
            $output .= $f2frenderer->print_session_list_table($displaysessions, $viewattendees, $editevents,
                $displaytimezones, $reserveinfo, $PAGE->url, true, false);

            $output .= ($iseditor || ($coursemodule->visible && $coursemodule->available)) ? $htmlviewallsessions : $strviewallsessions;

            if (($iseditor || ($coursemodule->visible && $coursemodule->available)) && $declareinterest_enable) {
                $output .= $declareinterest_link;
            }
        } else {
            // Show only name if session display is set to zero.
            $content = html_writer::tag('span', $htmlviewallsessions, array('class' => 'f2fsessionnotice f2factivityname'));
            $coursemodule->set_content($content);
            return;
        }
    } else if (has_capability('mod/facetoface:viewemptyactivities', $contextmodule)) {
        $content = html_writer::tag('span', $htmlviewallsessions, array('class' => 'f2fsessionnotice f2factivityname'));
        $coursemodule->set_content($content);
        return;
    } else {
        // Nothing to display to this user.
        $coursemodule->set_content('');
        return;
    }

    $coursemodule->set_content($output);
}

/**
 * Determine if sign-ups to this session should place users on the
 * waitlist or book them.
 *
 * @param object $session A session object
 * @return bool True if the sign-up should be by waitlist, false otherwise.
 */
function facetoface_is_signup_by_waitlist($session) {
    // Users will be waitlisted if the session date is unknown.
    if (empty($session->mintimestart)) {
        return true;
    }

    if (!$cm = get_coursemodule_from_instance('facetoface', $session->facetoface)) {
        print_error('error:incorrectcoursemoduleid', 'facetoface');
    }
    $context = context_module::instance($cm->id);
    // Get waitlisteveryone setting for the session.
    $facetoface_allowwaitlisteveryone = get_config(null, 'facetoface_allowwaitlisteveryone');
    $waitlisteveryone = !empty($facetoface_allowwaitlisteveryone) && $session->waitlisteveryone;
    // If user has capability to overbook?
    $overbook = has_capability('mod/facetoface:signupwaitlist', $context);
    if ($waitlisteveryone || facetoface_get_num_attendees($session->id) >= $session->capacity) {
        return ($overbook ? false : true);
    }

    return false;
}

/**
 * Determine if a user is in the waitlist of a session.
 *
 * @param object $session A session object
 * @param int $userid The user ID
 * @return bool True if the user is on waitlist, false otherwise.
 */
function facetoface_is_user_on_waitlist($session, $userid = null) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    $sql = "SELECT 1
            FROM {facetoface_signups} su
            JOIN {facetoface_signups_status} ss ON su.id = ss.signupid
            WHERE su.sessionid = ?
              AND ss.superceded != 1
              AND su.userid = ?
              AND ss.statuscode = ?";

    return $DB->record_exists_sql($sql, array($session->id, $userid, MDL_F2F_STATUS_WAITLISTED));
}

/**
 * Update grades by firing grade_updated event
 *
 * @param object $facetoface null means all facetoface activities
 * @param int $userid specific user only, 0 mean all (not used here)
 * @param bool $nullifnone If a single user is specified and $nullifnone is true, a grade item with a null rawgrade will be inserted
 */
function facetoface_update_grades($facetoface=null, $userid=0, $nullifnone = true) {
    global $DB;

    if (($facetoface != null) && $userid && $nullifnone) {
        $grade = new stdClass();
        $grade->userid   = $userid;
        $grade->rawgrade = null;
        facetoface_grade_item_update($facetoface, $grade);
    } else if ($facetoface != null) {
        facetoface_grade_item_update($facetoface);
    } else {
        $sql = "SELECT f.*, cm.idnumber as cmidnumber
                  FROM {facetoface} f
                  JOIN {course_modules} cm ON cm.instance = f.id
                  JOIN {modules} m ON m.id = cm.module
                 WHERE m.name='facetoface'";
        if ($rs = $DB->get_recordset_sql($sql)) {
            foreach ($rs as $facetoface) {
                facetoface_grade_item_update($facetoface);
            }
            $rs->close();
        }
    }
    return true;
}

/**
 * Create grade item for given Face-to-face session
 *
 * @param int facetoface  Face-to-face activity (not the session) to grade
 * @param mixed grades    grades objects or 'reset' (means reset grades in gradebook)
 * @return int 0 if ok, error code otherwise
 */
function facetoface_grade_item_update($facetoface, $grades=NULL) {
    global $CFG, $DB;

    if (!isset($facetoface->cmidnumber)) {

        $sql = "SELECT cm.idnumber as cmidnumber
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module
                 WHERE m.name='facetoface' AND cm.instance = ?";
        $facetoface->cmidnumber = $DB->get_field_sql($sql, array($facetoface->id));
    }

    $params = array('itemname' => $facetoface->name,
                    'idnumber' => $facetoface->cmidnumber);

    $params['gradetype'] = GRADE_TYPE_VALUE;
    $params['grademin']  = 0;
    $params['gradepass'] = 100;
    $params['grademax']  = 100;

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = NULL;
    }

    $retcode = grade_update('mod/facetoface', $facetoface->course, 'mod', 'facetoface',
                            $facetoface->id, 0, $grades, $params);
    return ($retcode === GRADE_UPDATE_OK);
}

/**
 * Delete grade item for given facetoface
 *
 * @param object $facetoface object
 * @return object facetoface
 */
function facetoface_grade_item_delete($facetoface) {
    $retcode = grade_update('mod/facetoface', $facetoface->course, 'mod', 'facetoface',
                            $facetoface->id, 0, NULL, array('deleted' => 1));
    return ($retcode === GRADE_UPDATE_OK);
}

/**
 * Return number of attendees signed up to a facetoface session
 *
 * @param integer $session_id
 * @param integer $status MDL_F2F_STATUS_* constant (optional)
 * @param string $comp SQL comparison operator.
 *
 * @return integer
 */
function facetoface_get_num_attendees($session_id, $status = MDL_F2F_STATUS_BOOKED, $comp = '>=') {
    global $DB;

    $sql = 'SELECT COUNT(ss.id)
              FROM {facetoface_signups} su
              JOIN {facetoface_signups_status} ss ON su.id = ss.signupid
             WHERE sessionid = ?
               AND ss.superceded = 0
               AND ss.statuscode ' . $comp . ' ?';

    // For the session, pick signups that haven't been superceded.
    return (int)$DB->count_records_sql($sql, array($session_id, $status));
}

/**
 * Return all of a users' submissions to a facetoface
 *
 * @param integer $facetofaceid
 * @param integer $userid
 * @param boolean $includecancellations
 * @param integer $minimumstatus Minimum status level to return
 * @param integer $maximumstatus Maximum status level to return
 * @param integer $sessionid Session id
 * @return array submissions | false No submissions
 */
function facetoface_get_user_submissions($facetofaceid, $userid, $minimumstatus=MDL_F2F_STATUS_REQUESTED, $maximumstatus=MDL_F2F_STATUS_FULLY_ATTENDED, $sessionid = null) {
    global $DB;

    $whereclause = "s.facetoface = ? AND su.userid = ? AND ss.superceded != 1
            AND ss.statuscode >= ? AND ss.statuscode <= ? AND s.cancelledstatus != 1";
    $whereparams = array($facetofaceid, $userid, $minimumstatus, $maximumstatus);

    if (!empty($sessionid)) {
        $whereclause .= " AND s.id = ? ";
        $whereparams[] = $sessionid;
    }

    //TODO fix mailedconfirmation, timegraded, timecancelled, etc
    return $DB->get_records_sql("
        SELECT
            su.id,
            su.userid,
            su.notificationtype,
            su.discountcode,
            su.managerid,
            su.jobassignmentid,
            s.facetoface,
            s.id as sessionid,
            s.cancelledstatus,
            s.timemodified,
            ss.timecreated,
            ss.timecreated as timegraded,
            ss.statuscode,
            0 as timecancelled,
            0 as mailedconfirmation
        FROM
            {facetoface_sessions} s
        JOIN
            {facetoface_signups} su
         ON su.sessionid = s.id
        JOIN
            {facetoface_signups_status} ss
         ON su.id = ss.signupid
        WHERE
            {$whereclause}
        ORDER BY
            s.timecreated
    ", $whereparams);
}

/**
 * Cancel users' submission to a facetoface session
 *
 * @param integer $sessionid   ID of the facetoface_sessions record
 * @param integer $userid      ID of the user record
 * @param string $cancelreason Short justification for cancelling the signup
 * @return boolean success
 */
function facetoface_user_cancel_submission($sessionid, $userid, $cancelreason='') {
    global $DB, $USER;

    if (!$session = facetoface_get_session($sessionid)) {
        debugging("Could not load Face-to-face session with ID: {$sessionid}", DEBUG_DEVELOPER);
        return false;
    }
    if (!$facetoface = $DB->get_record('facetoface', array('id' => $session->facetoface))) {
        debugging("Could not load Face-to-face instance with ID: {$session->facetoface}", DEBUG_DEVELOPER);
        return false;
    }

    if (!$signup = $DB->get_record('facetoface_signups', array('sessionid' => $sessionid, 'userid' => $userid))) {
        debugging("No user with ID: {$userid} has signed-up for the session ID: {$sessionid}.", DEBUG_DEVELOPER);
        return true; // not signed up, nothing to do
    }

    // If user status already changed to cancelled.
    if (facetoface_is_signup_cancelled((int)$signup->id)) {
        if (defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
            debugging('User status already changed to cancelled.', DEBUG_DEVELOPER);
        }
        return true;
    }

    if ($result = facetoface_update_signup_status($signup->id, MDL_F2F_STATUS_USER_CANCELLED, $USER->id)) {
        // Save cancellation note if cancellation note exists to keep the function working as before.
        if (!empty($cancelreason)) {
            $params = array('shortname' => 'cancellationnote', 'datatype' => 'text');
            if ($cancelfieldid = $DB->get_field('facetoface_cancellation_info_field', 'id', $params)) {
                $canceldataparams = array('fieldid' => $cancelfieldid, 'facetofacecancellationid' => $signup->id);
                if ($DB->record_exists('facetoface_cancellation_info_data', $canceldataparams)) {
                    $DB->set_field('facetoface_cancellation_info_data', 'data', $cancelreason, $canceldataparams);
                } else {
                    $todb = new stdClass();
                    $todb->data = $cancelreason;
                    $todb->fieldid = $cancelfieldid;
                    $todb->facetofacecancellationid = $signup->id;
                    $DB->insert_record('facetoface_cancellation_info_data', $todb);
                }
            }
        }
    }

    return $result;
}

/**
 * Find out if signup for the given id is cancelled.
 *
 * @param int $signupid
 * @return bool
 */
function facetoface_is_signup_cancelled(int $signupid): bool {
    global $DB;

    $sql = "signupid = ? AND superceded = ? AND statuscode = ?";
    $params = [$signupid, 0, MDL_F2F_STATUS_USER_CANCELLED];

    return $DB->record_exists_select('facetoface_signups_status', $sql, $params);
}

/**
 * A list of actions in the logs that indicate view activity for participants
 */
function facetoface_get_view_actions() {
    return array('view', 'view all');
}

/**
 * A list of actions in the logs that indicate post activity for participants
 */
function facetoface_get_post_actions() {
    return array('cancel booking', 'signup');
}

/**
 * Return a small object with summary information about what a user
 * has done with a given particular instance of this module (for user
 * activity reports.)
 *
 * $return->time = the time they did it
 * $return->info = a short text description
 */
function facetoface_user_outline($course, $user, $mod, $facetoface) {

    $result = new stdClass;

    $grade = facetoface_get_grade($user->id, $course->id, $facetoface->id);
    if ($grade->grade > 0) {
        $result = new stdClass;
        $result->info = get_string('grade') . ': ' . $grade->grade;
        $result->time = $grade->dategraded;
    }
    elseif ($submissions = facetoface_get_user_submissions($facetoface->id, $user->id)) {
        if ($facetoface->multiplesessions && (count($submissions) > 1) ) {
            $result->info = get_string('usersignedupmultiple', 'facetoface', count($submissions));
            $result->time = 0;
            foreach ($submissions as $submission) {
                if ($submission->timecreated > $result->time) {
                    $result->time = $submission->timecreated;
                }
            }
        } else {
            $result->info = get_string('usersignedup', 'facetoface');
            $result->time = reset($submissions)->timecreated;
        }
    }
    else {
        $result->info = get_string('usernotsignedup', 'facetoface');
    }

    return $result;
}

/**
 * Print a detailed representation of what a user has done with a
 * given particular instance of this module (for user activity
 * reports).
 */
function facetoface_user_complete($course, $user, $mod, $facetoface) {

    $grade = facetoface_get_grade($user->id, $course->id, $facetoface->id);

    if ($submissions = facetoface_get_user_submissions($facetoface->id, $user->id, MDL_F2F_STATUS_USER_CANCELLED, MDL_F2F_STATUS_FULLY_ATTENDED)) {
        print get_string('grade').': '.$grade->grade . html_writer::empty_tag('br');
        if ($grade->dategraded > 0) {
            $timegraded = trim(userdate($grade->dategraded, get_string('strftimedatetime')));
            print '('.format_string($timegraded).')'. html_writer::empty_tag('br');
        }
        echo html_writer::empty_tag('br');

        foreach ($submissions as $submission) {
            $timesignedup = trim(userdate($submission->timecreated, get_string('strftimedatetime')));
            print get_string('usersignedupon', 'facetoface', format_string($timesignedup)) . html_writer::empty_tag('br');

            if ($submission->timecancelled > 0) {
                $timecancelled = userdate($submission->timecancelled, get_string('strftimedatetime'));
                print get_string('usercancelledon', 'facetoface', format_string($timecancelled)) . html_writer::empty_tag('br');
            }
        }
    }
    else {
        print get_string('usernotsignedup', 'facetoface');
    }

    return true;
}

/**
 * Add a link to the session to the courses calendar.
 *
 * @param stdclass $session      Output from {@see facetoface_get_session} function
 * @param stdclass $eventname    Name to display for this event
 * @param string   $calendartype Which calendar to add the event to (user, course, site)
 * @param int      $userid       Optional param for user calendars
 * @param string   $eventtype    Optional param for user calendar (booking/session)
 */
function facetoface_add_session_to_calendar($session, $facetoface, $calendartype = 'none', $userid = 0, $eventtype = 'session') {
    global $CFG, $DB;

    if (empty($session->mintimestart)) {
        return true; //date unkown, can't add to calendar
    }

    if (empty($facetoface->showoncalendar) && empty($facetoface->usercalentry)) {
        return true; //facetoface calendar settings prevent calendar
    }

    $description = '';
    if (!empty($facetoface->description)) {
        $description .= html_writer::tag('p', clean_param($facetoface->description, PARAM_CLEANHTML));
    }
    $description .= facetoface_print_session($session, false, true, true);
    $linkurl = new moodle_url('/mod/facetoface/signup.php', array('s' => $session->id));
    $linktext = get_string('signupforthissession', 'facetoface');

    if ($calendartype == 'site' && $facetoface->showoncalendar == F2F_CAL_SITE) {
        $courseid = SITEID;
        $description .= html_writer::link($linkurl, $linktext);
    } else if ($calendartype == 'course' && $facetoface->showoncalendar == F2F_CAL_COURSE) {
        $courseid = $facetoface->course;
        $description .= html_writer::link($linkurl, $linktext);
    } else if ($calendartype == 'user' && $facetoface->usercalentry) {
        $courseid = 0;
        $urlvar = ($eventtype == 'session') ? 'attendees' : 'signup';
        $linkurl = $CFG->wwwroot . "/mod/facetoface/" . $urlvar . ".php?s=$session->id";
        $description .= get_string("calendareventdescription{$eventtype}", 'facetoface', $linkurl);
    } else {
        return true;
    }

    $shortname = $facetoface->shortname;
    if (empty($shortname)) {
        $shortname = shorten_text($facetoface->name, CALENDAR_MAX_NAME_LENGTH);
    }

    // Remove all calendar events related to current session and user before adding new event to avoid duplication.
    facetoface_remove_session_from_calendar($session, $courseid, $userid);

    $result = true;
    foreach ($session->sessiondates as $date) {
        $newevent = new stdClass();
        $newevent->name = $shortname;
        $newevent->description = $description;
        $newevent->format = FORMAT_HTML;
        $newevent->courseid = $courseid;
        $newevent->groupid = 0;
        $newevent->userid = $userid;
        $newevent->uuid = "{$session->id}";
        $newevent->instance = $session->facetoface;
        $newevent->modulename = 'facetoface';
        $newevent->eventtype = "facetoface{$eventtype}";
        $newevent->timestart = $date->timestart;
        $newevent->timeduration = $date->timefinish - $date->timestart;
        $newevent->visible = 1;
        $newevent->timemodified = time();

        $result = $result && $DB->insert_record('event', $newevent);
    }

    return $result;
}

/**
 * Remove all entries in the course calendar which relate to this session.
 *
 * @param class $session    Record from the facetoface_sessions table
 * @param integer $userid   ID of the user
 */
function facetoface_remove_session_from_calendar($session, $courseid = 0, $userid = 0) {
    global $DB;

    $params = array($session->facetoface, $userid, $courseid, $session->id);

    return $DB->delete_records_select('event', "modulename = 'facetoface' AND
                                                instance = ? AND
                                                userid = ? AND
                                                courseid = ? AND
                                                uuid = ?", $params);
}

/**
 * Remove all entries in the course calendar which relate to this session.
 *
 * Note: the user/course ID is nominally an integer but it is not right for the
 * code to assume its value will always > 0. This is why default values for the
 * parameters are null, NOT 0. In other words, if a caller passes in a non null
 * user ID, then the assumption is the caller wants to remove calendar entries
 * for that specific userid. It is this contract that works around a problem in
 * `facetoface_remove_session_from_calendar` - where a course/user ID is always
 * used even if it is 0.
 *
 * @param \stdClass $session record from the facetoface_sessions table.
 * @param integer $courseid identifies the specific course whose calendar entry
 *        is to be removed. If null, it is ignored.
 * @param integer $userid identifies the specific user whose calendar entry is
 *        to be removed. If null, it is ignored.
 *
 * @return boolean true if the removal succeeded.
 */
function facetoface_remove_all_calendar_entries($session, $courseid = null, $userid = null) {
    global $DB;

    $initial = new \stdClass();
    $initial->whereClause = "modulename = 'facetoface'";
    $initial->params = array();

    $fragments = array(
        array('instance', $session->facetoface),
        array('uuid',     $session->id),
        array('courseid', $courseid),
        array('userid',   $userid)
    );

    $final = array_reduce($fragments,
        function (\stdClass $accumulated, array $fragment) {

            list($field, $value) = $fragment;
            if (is_null($value)) {
                return $accumulated;
            }

            $accumulated->whereClause = sprintf('%s AND %s = ?', $accumulated->whereClause, $field);
            $accumulated->params[] = $value;

            return $accumulated;
        },

        $initial
    );

    return $DB->delete_records_select('event', $final->whereClause, $final->params);
}

/**
 * Update the date/time of events in the Moodle Calendar when a
 * session's dates are changed.
 *
 * @param class  $session    Record from the facetoface_sessions table
 * @param string $eventtype  Type of the event (booking or session)
 */
function facetoface_update_user_calendar_events($session, $eventtype) {
    global $DB;

    $facetoface = $DB->get_record('facetoface', array('id' => $session->facetoface));

    if (empty($facetoface->usercalentry) || $facetoface->usercalentry == 0) {
        return true;
    }

    $users = facetoface_delete_user_calendar_events($session, $eventtype);

    // Add this session to these users' calendar
    foreach ($users as $user) {
        facetoface_add_session_to_calendar($session, $facetoface, 'user', $user->userid, $eventtype);
    }
    return true;
}

/**
 *Delete all user level calendar events for a face to face session
 *
 * @param class     $session    Record from the facetoface_sessions table
 * @param string    $eventtype  Type of the event (booking or session)
 */
function facetoface_delete_user_calendar_events($session, $eventtype) {
    global $DB;

    // Without uuid(sessionid) param, this function deletes all events(seminar with multiple events) except the last one,
    // meaning the last event (running the update calendar) will delete all previous events created just now.
    // Usercase: Seminar has 2 events and attendee signed to the 1st event.
    $whereclause = "modulename = ? AND
                    eventtype = ? AND
                    instance = ? AND
                    uuid = ?";

    $whereparams = array('facetoface', "facetoface{$eventtype}", $session->facetoface, $session->id);

    if ('session' == $eventtype) {
        $likestr = "%attendees.php?s={$session->id}%";
        $like = $DB->sql_like('description', '?');
        $whereclause .= " AND $like";

        $whereparams[] = $likestr;
    }

    //users calendar
    $users = $DB->get_records_sql("SELECT DISTINCT userid
        FROM {event}
        WHERE $whereclause", $whereparams);

    if ($users && count($users) > 0) {
        // Delete the existing events
        $DB->delete_records_select('event', $whereclause, $whereparams);
    }

    return $users;
}

/**
 * Confirm that a session has free space for a user
 *
 * @param class  $session Record from the facetoface_sessions table
 * @param object $context (optional) A context object (record from context table)
 * @param int    $userid (optional)
 * @return bool True if user can be added to session
 **/
function facetoface_session_has_capacity($session, $context = false, $status = MDL_F2F_STATUS_BOOKED, $userid = 0) {
    global $USER;
    if (empty($session)) {
        return false;
    }

    if (!$userid) {
        $userid = $USER->id;
    }

    $signupcount = facetoface_get_num_attendees($session->id, $status);

    if ($signupcount >= $session->capacity) {
        // if session is full, check if overbooking is allowed for this user
        if (!$context || !has_capability('mod/facetoface:signupwaitlist', $context, $userid)) {
            return false;
        }
    }

    return true;
}

/**
 * Print the list of a sessions
 *
 * @param integer $courseid
 * @param object $facetoface
 * @param object $sessions
 *
 * @return string html sting
 */
function facetoface_print_session_list($courseid, $facetoface, $sessions) {
    global $USER, $OUTPUT, $PAGE;

    $timenow = time();
    $output = '';

    $cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $courseid, false, MUST_EXIST);
    $context = context_module::instance($cm->id);

    /** @var mod_facetoface_renderer $f2f_renderer */
    $f2f_renderer = $PAGE->get_renderer('mod_facetoface');
    $f2f_renderer->setcontext($context);

    $viewattendees = has_capability('mod/facetoface:viewattendees', $context);
    $editevents = has_capability('mod/facetoface:editevents', $context);

    $bookedsession = null;
    $submissions = facetoface_get_user_submissions($facetoface->id, $USER->id);
    if (!$facetoface->multiplesessions) {
        $submission = array_shift($submissions);
        $bookedsession = $submission;
    }

    $upcomingarray = array();
    $previousarray = array();

    if ($sessions) {
        foreach ($sessions as $session) {
            $sessiondata = $session;
            if ($facetoface->multiplesessions) {
                $submission = facetoface_get_user_submissions($facetoface->id, $USER->id,
                    MDL_F2F_STATUS_REQUESTED, MDL_F2F_STATUS_FULLY_ATTENDED, $session->id);
                $bookedsession = array_shift($submission);
            }
            $sessiondata->bookedsession = $bookedsession;

            // Is session waitlisted
            if (!$session->cntdates ) {
                $upcomingarray[] = $sessiondata;
            } else {
                // Only sessions that are over should go to the previous session section.
                if (facetoface_is_session_over($session, $timenow)) {
                    $previousarray[] = $sessiondata;
                } else {
                    // Session is in progress or has not yet started.
                    // Normal scheduled session.
                    $upcomingarray[] = $sessiondata;
                }
            }
        }
    }

    $displaytimezones = get_config(null, 'facetoface_displaysessiontimezones');

    if ($editevents) {
        $output .= html_writer::link(
            new moodle_url('events/add.php', array('f' => $facetoface->id, 'backtoallsessions' => 1)), get_string('addsession', 'facetoface'),
            array('class' => 'btn btn-default')
        );
    }

    // Upcoming sessions
    $output .= $OUTPUT->heading(get_string('upcomingsessions', 'facetoface'), 3);
    if (empty($upcomingarray)) {
        print_string('noupcoming', 'facetoface');
    } else {
        $reserveinfo = array();
        if (!empty($facetoface->managerreserve)) {
            // Include information about reservations when drawing the list of sessions.
            $reserveinfo = facetoface_can_reserve_or_allocate($facetoface, $sessions, $context);
            $output .= html_writer::tag('p', get_string('lastreservation', 'mod_facetoface', $facetoface));
        }

        $sessionlist = $f2f_renderer->print_session_list_table(
            $upcomingarray, $viewattendees, $editevents, $displaytimezones, $reserveinfo, $PAGE->url
        );
        $output .= html_writer::div($sessionlist, 'upcomingsessionlist');
    }

    // Previous sessions
    if (!empty($previousarray)) {
        $output .= $OUTPUT->heading(get_string('previoussessions', 'facetoface'), 3);
        $sessionlist = $f2f_renderer->print_session_list_table(
            $previousarray, $viewattendees, $editevents, $displaytimezones, [], $PAGE->url
        );
        $output .= html_writer::div($sessionlist, 'previoussessionlist');
    }

    return $output;
}

/**
 * Print the details of a session for calendar
 *
 * @param object $event         Record from calendar event
 * @return string|null html markup when return is true
 */
function facetoface_print_calendar_session($event) {
    global $DB, $CFG, $USER;

    $session = facetoface_get_session($event->uuid);
    $facetoface = $DB->get_record('facetoface', array('id' => $session->facetoface));

    if (empty($facetoface->showoncalendar) && empty($facetoface->usercalentry)) {
        return '';
    }

    $output = facetoface_print_session($session, false, true);
    $users = facetoface_get_attendees($session->id);

    if ($facetoface->usercalentry && array_key_exists($USER->id, $users)) {
        // Better way is to get an user status and display it.
        $linkurl = $CFG->wwwroot . "/mod/facetoface/signup.php?s=$session->id";
        $output .= get_string("calendareventdescriptionbooking", 'facetoface', $linkurl);
    } else  if ($facetoface->showoncalendar == F2F_CAL_SITE || $facetoface->showoncalendar == F2F_CAL_COURSE) {
        // If the user has not signed up before.
        if (!facetoface_has_unarchived_signups($session->facetoface, $USER->id)
            || $facetoface->multiplesessions == '1') {
            $linkurl = new moodle_url('/mod/facetoface/signup.php', array('s' => $session->id));
            $linktext = get_string('signupforthissession', 'facetoface');
            $output .= html_writer::link($linkurl, $linktext);
        }
    } else {
        $output = '';
    }

    return $output;
}

/**
 * Print the details of a session
 *
 * @param object $session         Record from facetoface_sessions
 * @param boolean $showcapacity   Show the capacity (true) or only the seats available (false)
 * @param boolean $calendaroutput Whether the output should be formatted for a calendar event
 * @param boolean $return         Whether to return (true) the html or print it directly (true)
 * @param boolean $hidesignup     Hide any messages relating to signing up
 * @param string  $class          Custom css class for dl
 * @return string|null html markup when return is true
 */
function facetoface_print_session($session, $showcapacity, $calendaroutput=false, $return=true, $hidesignup=false, $class='f2f') {
    global $DB, $PAGE, $USER;

    $output = html_writer::start_tag('dl', array('class' => $class));

    // Print customfields.
    $customfields = customfield_get_data($session, 'facetoface_session', 'facetofacesession', true, array('extended' => true));
    if (!empty($customfields)) {
        foreach ($customfields as $cftitle => $cfvalue) {
            $output .= html_writer::tag('dt', str_replace(' ', '&nbsp;', $cftitle));
            $output .= html_writer::tag('dd', $cfvalue);
        }
    }

    $displaytimezones = get_config(null, 'facetoface_displaysessiontimezones');

    $rooms = facetoface_get_session_rooms($session->id);

    $strdatetime = str_replace(' ', '&nbsp;', get_string('sessiondatetime', 'facetoface'));
    if ($session->mintimestart) {
        foreach ($session->sessiondates as $date) {
            $output .= html_writer::empty_tag('br');

            $sessionobj = facetoface_format_session_times($date->timestart, $date->timefinish, $date->sessiontimezone);
            if ($sessionobj->startdate == $sessionobj->enddate) {
                $html = $sessionobj->startdate . ', ';
            } else {
                $html = $sessionobj->startdate . ' - ' . $sessionobj->enddate . ', ';
            }

            $sessiontimezonestr = !empty($displaytimezones) ? $sessionobj->timezone : '';
            $html .= $sessionobj->starttime . ' - ' . $sessionobj->endtime . ' ' . $sessiontimezonestr;

            $output .= html_writer::tag('dt', $strdatetime);
            $output .= html_writer::tag('dd', $html);

            $output .= html_writer::tag('dt', get_string('duration', 'facetoface'));
            $output .= html_writer::tag('dd', format_time((int)$date->timestart - (int)$date->timefinish));

            if (!$date->roomid or !isset($rooms[$date->roomid])) {
                continue;
            }
            // Display room information
            $room = $rooms[$date->roomid];
            $roomstring = facetoface_room_html($room, $PAGE->url);
            $systemcontext = context_system::instance();
            $descriptionhtml = file_rewrite_pluginfile_urls($room->description, 'pluginfile.php', $systemcontext->id, 'mod_facetoface', 'room', $room->id);
            $roomstring .= format_text($descriptionhtml, FORMAT_HTML);
            $output .= html_writer::tag('dt', get_string('room', 'facetoface'));
            $output .= html_writer::tag('dd', html_writer::tag('span', $roomstring, array('class' => 'roomdescription')));
        }

        $output .= html_writer::empty_tag('br');
    } else {
        $output .= html_writer::tag('dt', $strdatetime);
        $output .= html_writer::tag('dd', html_writer::tag('em', get_string('wait-listed', 'facetoface')));
    }

    $signupcount = facetoface_get_num_attendees($session->id);
    $placesleft = $session->capacity - $signupcount;

    if ($showcapacity) {
        $output .= html_writer::tag('dt', get_string('maxbookings', 'facetoface'));

        if ($session->allowoverbook) {
            $output .= html_writer::tag('dd', get_string('capacityallowoverbook', 'facetoface', $session->capacity));
        } else {
            $output .= html_writer::tag('dd', $session->capacity);
        }
    } else if (!$calendaroutput) {
        $output .= html_writer::tag('dt', get_string('seatsavailable', 'facetoface'));
        $output .= html_writer::tag('dd', max(0, $placesleft));
    }

    // Display requires approval notification
    $facetoface = $DB->get_record('facetoface', array('id' => $session->facetoface));

    // Display job assignments.
    if (get_config(null, 'facetoface_selectjobassignmentonsignupglobal') &&
        ($facetoface->selectjobassignmentonsignup || $facetoface->forceselectjobassignment)) {
        if (isset($session->bookedsession->jobassignmentid) && $session->bookedsession->jobassignmentid) {
            $jobassignment = \totara_job\job_assignment::get_with_id(
                $session->bookedsession->jobassignmentid,
                false
            );

            if (null == $jobassignment) {
                // If the job assignment does not exist, we should let the user know that
                // the job assignment might have been deleted by the site admin, and the
                // reference is not updated yet.
                $fullname = get_string("missingjobassignment", "mod_facetoface");
            } else {
                $fullname = $jobassignment->fullname;
            }

            $output .= html_writer::empty_tag('br');
            $output .= html_writer::tag('dt', get_string('jobassignment', 'facetoface'));
            $output .= html_writer::tag('dd', $fullname);
            $output .= html_writer::empty_tag('br');
        }
    }

    // Display waitlist notification
    if (!$hidesignup && $session->allowoverbook && $placesleft < 1) {
        $output .= html_writer::tag('dd', get_string('userwillbewaitlisted', 'facetoface'));
    }

    // Display managers.
    if ($facetoface->approvaltype != APPROVAL_NONE && $facetoface->approvaltype != APPROVAL_SELF) {
        $approver = facetoface_get_approvaltype_string($facetoface->approvaltype, $facetoface->approvalrole);
        $output .= html_writer::tag('dt', get_string('approvalrequiredby', 'facetoface'));
        $output .= html_writer::tag('dd', $approver);

        if (isset($session->bookedsession->managerid) && $session->bookedsession->managerid) {
            $manager = core_user::get_user($session->bookedsession->managerid);
            $manager_url = new moodle_url('/user/view.php', array('id' => $manager->id));
            $output .= html_writer::tag('dt', get_string('managername', 'facetoface'));
            $output .= html_writer::tag('dd', html_writer::link($manager_url, fullname($manager)));
        } else {
            if (!isset($session->managerids)) {
                $session->managerids   = \totara_job\job_assignment::get_all_manager_userids($USER->id);
            }
            if (!empty($session->managerids)) {
                $managers = array();
                foreach ($session->managerids as $managerid) {
                    $manager = core_user::get_user($managerid);
                    $manager_url = new moodle_url('/user/view.php', array('id' => $manager->id));
                    $managers[] = html_writer::link($manager_url, fullname($manager));
                }
                $output .= html_writer::tag('dt', get_string('managername', 'facetoface'));
                $output .= html_writer::tag('dd', implode(', ', $managers));
            }
        }
    }
    // Display trainers.
    if (!isset($session->trainerroles)) {
        $session->trainerroles = facetoface_get_trainer_roles(context_course::instance($facetoface->course));
    }
    if (!isset($session->trainers)) {
        $session->trainers = facetoface_get_trainers($session->id);
    }
    foreach ((array)$session->trainerroles as $role => $rolename) {

        if (empty($session->trainers[$role])) {
            continue;
        }

        $trainer_names = array();
        $rolename = $rolename->localname;
        foreach ($session->trainers[$role] as $trainer) {
            $trainer_url = new moodle_url('/user/view.php', array('id' => $trainer->id));
            $trainer_names[] = html_writer::link($trainer_url, fullname($trainer));
        }
        $output .= html_writer::tag('dt', $rolename);
        $output .= html_writer::tag('dd', implode(', ', $trainer_names));
    }

    if (!get_config(null, 'facetoface_hidecost') && !empty($session->normalcost)) {
        $output .= html_writer::tag('dt', get_string('normalcost', 'facetoface'));
        $output .= html_writer::tag('dd', format_string($session->normalcost));

        if (!get_config(null, 'facetoface_hidediscount') && !empty($session->discountcost)) {
            $output .= html_writer::tag('dt', get_string('discountcost', 'facetoface'));
            $output .= html_writer::tag('dd', format_string($session->discountcost));
        }
    }

    if (!empty($session->details)) {
        if ($cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $facetoface->course)) {
            $context = context_module::instance($cm->id);
            $session->details = file_rewrite_pluginfile_urls($session->details, 'pluginfile.php', $context->id, 'mod_facetoface', 'session', $session->id);
            $session->details = format_text($session->details, FORMAT_HTML);
        }
        $details = format_text($session->details, FORMAT_HTML);
        $output .= html_writer::tag('dt', get_string('details', 'facetoface'));
        $output .= html_writer::tag('dd', $details);
    }

    $output .= html_writer::end_tag('dl');

    if ($return) {
        return $output;
    }

    echo $output;
}

/**
 * Return the approval type of a facetoface as a human readable string
 *
 * @param int approvaltype  The $facetoface->approvaltype value
 * @param int approvalrole  The $facetoface->approvalrole value, only required for role approval
 */
function facetoface_get_approvaltype_string($approvaltype, $approvalrole = null) {
    switch ($approvaltype) {
        case APPROVAL_NONE:
            return get_string('approval_none', 'mod_facetoface');
        case APPROVAL_SELF:
            return get_string('approval_self', 'mod_facetoface');
        case APPROVAL_ROLE:
            $rolenames = role_fix_names(get_all_roles());
            return $rolenames[$approvalrole]->localname;
        case APPROVAL_MANAGER:
            return get_string('approval_manager', 'mod_facetoface');
        case APPROVAL_ADMIN:
            return get_string('approval_admin', 'mod_facetoface');
        default:
            print_error('error:unrecognisedapprovaltype', 'mod_facetoface');
    }
}

/**
 * Is user approver for seminar activity
 * @param int $userid
 * @param stdClass $facetoface
 * @return bool true if user is system approver or activity approver
 */
function facetoface_is_adminapprover ($userid, stdClass $facetoface) {
    $sysapprovers = get_users_from_config(get_config(null, 'facetoface_adminapprovers'), 'mod/facetoface:approveanyrequest');
    $systemapprover = false;

    foreach ($sysapprovers as $sysapprover) {
        if ($sysapprover->id == $userid) {
            $systemapprover = true;
        }
    }

    $activityapprover = in_array($userid, explode(',', $facetoface->approvaladmins));

    $admins = array_keys(get_admins());
    if ($systemapprover || $activityapprover || in_array($userid, $admins)) {
        return true;
    }
    return false;
}

/**
 * Update the value of a customfield for the given session/notice.
 *
 * @param integer $field    ID of a record from the facetoface_session_field table
 * @param string  $data       Value for that custom field
 * @param integer $otherid    ID of a record from the facetoface_(sessions|notice) table
 * @param string  $table      'session' or 'notice' (part of the table name)
 * @returns true if it succeeded, false otherwise
 */
function facetoface_save_customfield_value($field, $data, $otherid, $table) {
    global $DB;

    $dbdata = null;
    if (is_array($data)) {
        // Get param1.
        $param1 = json_decode($field->param1);
        $values = array();
        foreach ($param1 as $key => $option) {
            $option->default = $data[$key];
            $values[md5($option->option)] = $option;
        }

        $dbdata = json_encode($values);
    }
    else {
        $dbdata = trim($data);
    }

    $newrecord = new stdClass();
    $newrecord->data = $dbdata;

    $fieldname = "{$table}id";
    if ($record = $DB->get_record("facetoface_{$table}_data", array('fieldid' => $field->id, $fieldname => $otherid))) {
        if (empty($dbdata)) {
            // Clear out the existing value
            return $DB->delete_records("facetoface_{$table}_data", array('id' => $record->id));
        }

        $newrecord->id = $record->id;
        return $DB->update_record("facetoface_{$table}_data", $newrecord);
    }
    else {
        if (empty($dbdata)) {
            return true; // no need to store empty values
        }

        $newrecord->fieldid = $field->id;
        $newrecord->$fieldname = $otherid;
        return $DB->insert_record("facetoface_{$table}_data", $newrecord);
    }
}

/**
 * Add the customfield names-values for the given session/notice to the object passed.
 *
 * @param stdClass  $object   Object to add the customfield
 * @param object  $field    A record from the facetoface_session_field table
 * @param integer $otherid  ID of a record from the facetoface_(sessions|notice) table
 * @param string  $table    'session' or 'notice' (part of the table name)
 */
function facetoface_get_customfield_value(&$object, $field, $otherid, $table) {
    global $DB;

    if ($record = $DB->get_record("facetoface_{$table}_data", array('fieldid' => $field->id, "{$table}id" => $otherid))) {
        if (!empty($record->data)) {
            if ('multiselect' == $field->datatype) {
                $data = json_decode($record->data, true);
                $index = 0;
                foreach ($data as $key => $item) {
                    $fieldname = "customfield_$field->shortname[$index]";
                    $object->$fieldname =  $item['default'];
                    $index++;
                }
            } else {
                $fieldname = "customfield_$field->shortname";
                $object->$fieldname =  $record->data;
            }
        }
    }
}

/**
 * Return the values stored for all custom fields in the given session.
 *
 * @param integer $sessionid  ID of facetoface_sessions record
 * @returns array Indexed by field shortnames
 */
function facetoface_get_customfielddata($sessionid) {

    $out = [];
    $item = (object)['id' => $sessionid];
    $out['sess'] = customfield_get_data($item, 'facetoface_session', 'facetofacesession', false);

    // A session can have more than one room if there are more than one date in the session and different
    // rooms are used on different dates
    $rooms = facetoface_get_session_rooms($sessionid);
    $out['room'] = array();
    foreach ($rooms as $room) {
        $out['room'] = array_merge_recursive($out['room'], customfield_get_data($room, 'facetoface_room', 'facetofaceroom', false));
    }

    // We want rooms values to be in 1 comma separated string
    foreach ($out['room'] as $key => $vals) {
        if (is_array($vals)) {
            $out['room'][$key] = implode(', ', $vals);
        }
    }
    return $out;
}

function facetoface_update_trainers($facetoface, $session, $form) {
    global $DB;

    // If we recieved bad data
    if (!is_array($form)) {
        return false;
    }

    // Load current trainers
    $current_trainers = facetoface_get_trainers($session->id);
    // To collect trainers
    $new_trainers = array();
    $old_trainers = array();

    $transaction = $DB->start_delegated_transaction();

    // Loop through form data and add any new trainers
    foreach ($form as $roleid => $trainers) {

        // Loop through trainers in this role
        foreach ($trainers as $trainer) {

            if (!$trainer) {
                continue;
            }

            // If the trainer doesn't exist already, create it
            if (!isset($current_trainers[$roleid][$trainer])) {

                $newtrainer = new stdClass();
                $newtrainer->userid = $trainer;
                $newtrainer->roleid = $roleid;
                $newtrainer->sessionid = $session->id;
                $new_trainers[] = $newtrainer;

                if (!$DB->insert_record('facetoface_session_roles', $newtrainer)) {
                    print_error('error:couldnotaddtrainer', 'facetoface');
                    $transaction->force_transaction_rollback();
                    return false;
                }
            } else {
                unset($current_trainers[$roleid][$trainer]);
            }
        }
    }

    // Loop through what is left of old trainers, and remove
    // (as they have been deselected)
    if ($current_trainers) {
        foreach ($current_trainers as $roleid => $trainers) {
            // If no trainers left
            if (empty($trainers)) {
                continue;
            }

            // Delete any remaining trainers
            foreach ($trainers as $trainer) {
                $old_trainers[] = $trainer;
                if (!$DB->delete_records('facetoface_session_roles', array('sessionid' => $session->id, 'roleid' => $roleid, 'userid' => $trainer->id))) {
                    print_error('error:couldnotdeletetrainer', 'facetoface');
                    $transaction->force_transaction_rollback();
                    return false;
                }
            }
        }
    }

    $transaction->allow_commit();

    // Send a confirmation notice to new trainer
    foreach ($new_trainers as $i => $trainer) {
        facetoface_send_trainer_confirmation_notice($facetoface, $session, $trainer->userid);
    }

    // Send an unassignment notice to old trainer
    foreach ($old_trainers as $i => $trainer) {
        facetoface_send_trainer_session_unassignment_notice($facetoface, $session, $trainer->id);
    }

    return true;
}


/**
 * Return array of trainer roles configured for face-to-face
 * @param $context context of the facetoface activity
 * @return  array
 */
function facetoface_get_trainer_roles($context) {
    global $CFG, $DB;

    // Check that roles have been selected
    if (empty($CFG->facetoface_session_roles)) {
        return false;
    }

    // Parse roles
    $cleanroles = clean_param($CFG->facetoface_session_roles, PARAM_SEQUENCE);
    list($rolesql, $params) = $DB->get_in_or_equal(explode(',', $cleanroles));

    // Load role names
    $rolenames = $DB->get_records_sql("
        SELECT
            r.id,
            r.name
        FROM
            {role} r
        WHERE
            r.id {$rolesql}
        AND r.id <> 0
    ", $params);

    // Return roles and names
    if (!$rolenames) {
        return array();
    }

    $rolenames = role_fix_names($rolenames, $context);

    return $rolenames;
}


/**
 * Get all trainers associated with a session, optionally
 * restricted to a certain roleid
 *
 * If a roleid is not specified, will return a multi-dimensional
 * array keyed by roleids, with an array of the chosen roles
 * for each role
 *
 * @param   integer     $sessionid
 * @param   integer     $roleid (optional)
 * @return  array
 */
function facetoface_get_trainers($sessionid, $roleid = null) {
    global $CFG, $DB;

    $usernamefields = get_all_user_name_fields(true, 'u');
    $sql = "
        SELECT
            u.id,
            {$usernamefields},
            r.roleid
        FROM
            {facetoface_session_roles} r
        LEFT JOIN
            {user} u
         ON u.id = r.userid
        WHERE
            r.sessionid = ?
        ";
    $params = array($sessionid);

    if ($roleid) {
        $sql .= "AND r.roleid = ?";
        $params[] = $roleid;
    }

    $rs = $DB->get_recordset_sql($sql , $params);
    $return = array();
    foreach ($rs as $record) {
        // Create new array for this role
        if (!isset($return[$record->roleid])) {
            $return[$record->roleid] = array();
        }
        $return[$record->roleid][$record->id] = $record;
    }
    $rs->close();

    // If we are only after one roleid
    if ($roleid) {
        if (empty($return[$roleid])) {
            return false;
        }
        return $return[$roleid];
    }

    // If we are after all roles
    if (empty($return)) {
        return false;
    }

    return $return;
}

/**
 * Determines whether an activity requires the user to have a manager (either for
 * manager approval or to send notices to the manager)
 *
 * @param  object $facetoface A database fieldset object for the facetoface activity
 * @return boolean whether a person needs a manager to sign up for that activity
 */
function facetoface_manager_needed($facetoface){
    return $facetoface->approvaltype == APPROVAL_MANAGER || $facetoface->approvaltype == APPROVAL_ADMIN;
}

/**
 * Determines whether an activity requires the user to recieve approval before signup.
 *
 * @param  object $facetoface A database fieldset object for the facetoface activity
 * @return boolean whether a person needs someones approval to sign up
 */
function facetoface_approval_required($facetoface) {
    return $facetoface->approvaltype == APPROVAL_MANAGER
        || $facetoface->approvaltype == APPROVAL_ROLE
        || $facetoface->approvaltype == APPROVAL_ADMIN;
}

/**
 * Get session cancellations
 *
 * @access  public
 * @param   integer $sessionid
 * @return  array
 */
function facetoface_get_cancellations($sessionid) {
    global $CFG, $DB;

    $usernamefields = get_all_user_name_fields(true, 'u');

    $cancelledstatus = array(MDL_F2F_STATUS_USER_CANCELLED, MDL_F2F_STATUS_SESSION_CANCELLED);
    list($cancelledinsql, $cancelledinparams) = $DB->get_in_or_equal($cancelledstatus);

    $instatus = array(MDL_F2F_STATUS_BOOKED, MDL_F2F_STATUS_WAITLISTED, MDL_F2F_STATUS_REQUESTED);
    list($insql, $inparams) = $DB->get_in_or_equal($instatus);
    // Nasty SQL follows:
    // Load currently cancelled users,
    // include most recent booked/waitlisted time also
    $sql = "
            SELECT
                u.id,
                su.id AS submissionid,
                {$usernamefields},
                su.jobassignmentid,
                MAX(ss.timecreated) AS timesignedup,
                c.timecreated AS timecancelled,
                c.statuscode
            FROM
                {facetoface_signups} su
            JOIN
                {user} u
             ON u.id = su.userid
            JOIN
                {facetoface_signups_status} c
             ON su.id = c.signupid
            AND c.statuscode $cancelledinsql
            AND c.superceded = 0
            LEFT JOIN
                {facetoface_signups_status} ss
             ON su.id = ss.signupid
             AND ss.statuscode $insql
             AND ss.superceded = 1
            WHERE
                su.sessionid = ?
            GROUP BY
                su.id,
                u.id,
                {$usernamefields},
                c.timecreated,
                su.jobassignmentid,
                c.statuscode,
                c.id
            ORDER BY
                {$usernamefields},
                c.timecreated
    ";
    $params = array_merge($cancelledinparams, $inparams);
    $params[] = $sessionid;
    return $DB->get_records_sql($sql, $params);
}


/**
 * Get session unapproved requests
 *
 * @access  public
 * @param   integer $sessionid
 * @return  array|false
 */
function facetoface_get_requests($sessionid) {
    $usernamefields = get_all_user_name_fields(true, 'u');

    $select = "u.id, su.id AS signupid, {$usernamefields}, u.email,
        ss.statuscode, ss.timecreated AS timerequested";

    return facetoface_get_users_by_status($sessionid, MDL_F2F_STATUS_REQUESTED, $select);
}

/**
 * Similar to facetoface_get_requests except this returns 2stage requests in:
 * Stage One - pending manager approval
 * Stage Two - pending admin approval
 *
 * @access  public
 * @param   integer $sessionid
 * @return  array|false
 */
function facetoface_get_adminrequests($sessionid) {
    $usernamefields = get_all_user_name_fields(true, 'u');

    $select = "u.id, su.id AS signupid, {$usernamefields}, u.email,
        ss.statuscode, ss.timecreated AS timerequested";

    $status = array(MDL_F2F_STATUS_REQUESTED, MDL_F2F_STATUS_REQUESTEDADMIN);
    return facetoface_get_users_by_status($sessionid, $status, $select);
}

/**
 * Get session attendees by status
 *
 * @access  public
 * @param   integer $sessionid
 * @param   mixed   $status     Integer or array of integers
 * @param   string  $select     SELECT clause
 * @param   bool    $includereserved   optional - include 'reserved' users (note this will change the array index
 *                              to be the signupid, to avoid duplicate id problems).
 * @return  array|false
 */
function facetoface_get_users_by_status($sessionid, $status, $select = '', $includereserved = false) {
    global $DB;

    // If no select SQL supplied, use default
    $usernamefields = get_all_user_name_fields(true, 'u');
    if (!$select) {
        $select = "u.id, su.id AS signupid, {$usernamefields}, ss.timecreated, u.email";
        if ($includereserved) {
            $select = "su.id, {$usernamefields}, ss.timecreated, u.email";
        }
    }
    $userjoin = 'JOIN';
    if ($includereserved) {
        $userjoin = 'LEFT JOIN';
    }

    // Make string from array of statuses
    if (is_array($status)) {
        list($insql, $params) = $DB->get_in_or_equal($status, SQL_PARAMS_NAMED);
        $statussql = "ss.statuscode {$insql}";
    } else {
        $statussql = 'ss.statuscode = :status';
        $params = array('status' => $status);
    }

    $sql = "
        SELECT {$select}
          FROM {facetoface_signups} su
          JOIN {facetoface_signups_status} ss ON su.id = ss.signupid
     $userjoin {user} u ON u.id = su.userid
         WHERE su.sessionid = :sid
           AND ss.superceded != 1
           AND {$statussql}
      ORDER BY {$usernamefields}, ss.timecreated
    ";
    $params['sid'] = $sessionid;

    return $DB->get_records_sql($sql, $params);
}


/**
 * Returns all other caps used in module
 * @return array
 */
function facetoface_get_extra_capabilities() {
    return array('moodle/site:viewfullnames');
}


/**
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function facetoface_supports($feature) {
    switch($feature) {
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_ARCHIVE_COMPLETION:      return true;
        case FEATURE_COMPLETION_HAS_RULES:    return true;
        case FEATURE_COMPLETION_TIME_IN_TIMECOMPLETED: return true;

        default: return null;
    }
}

class facetoface_event_handler {

    /**
     * Event that is triggered when a user is deleted.
     *
     * Cancels a user from any future sessions when they are deleted
     * this is to make sure deleted users aren't using space in sessions
     * when there is limited capacity.
     *
     * @param \core\event\user_deleted $event
     */
    public static function user_deleted(\core\event\user_deleted $event) {
        global $DB;

        $userid = $event->objectid;
        if ($signups = $DB->get_records('facetoface_signups', array('userid' => $userid))) {
            foreach ($signups as $signup) {
                $session = facetoface_get_session($signup->sessionid);
                // Using $null, null fails because of passing by reference.
                facetoface_user_cancel($session, $userid, false, $null, get_string('userdeletedcancel', 'facetoface'));
            }
        }
        return true;
    }

    /**
     * Event that is triggered when a user is suspended.
     *
     * Cancels a user from any future sessions when they are suspended
     * this is to make sure suspended users aren't using space in sessions
     * when there is limited capacity.
     *
     * @param \totara_core\event\user_suspended $event
     */
    public static function user_suspended(\totara_core\event\user_suspended $event) {
        global $DB;

        $userid = $event->objectid;
        $timenow = time();

        if ($signups = $DB->get_records('facetoface_signups', array('userid' => $userid))) {
            foreach ($signups as $signup) {
                $session = facetoface_get_session($signup->sessionid);
                if (!empty($session->sessiondates) && facetoface_has_session_started($session, $timenow) && facetoface_is_session_in_progress($session, $timenow)) {
                    // Session in progress.
                    $safetocancel = false;
                } else if (!empty($session->sessiondates)  && facetoface_has_session_started($session, $timenow)) {
                    // Session is over, don't remove user's records.
                    $safetocancel = false;
                } else if (facetoface_is_user_on_waitlist($session, $userid)) {
                    // Session is wait-listed.
                    $safetocancel = true;
                } else {
                    // Booking open.
                    $safetocancel = true;
                }

                if ($safetocancel) {
                    // Using $null, null fails because of passing by reference.
                    facetoface_user_cancel($session, $userid, false, $null, get_string('usersuspendedcancel', 'facetoface'));
                }
            }
        }
        return true;
    }

    /**
     * Event that is triggered when a user is unenrolled from a course
     *
     * Cancels a user from any future sessions when they are unenrolled from a course,
     * this is to make sure unenrolled users aren't using space in sessions
     * when there is limited capacity
     *
     * @param \core\event\user_enrolment_deleted $event
     * @return true if no errors were encountered
     */
    public static function user_unenrolled(\core\event\user_enrolment_deleted $event) {
        global $DB;

        if (!$event->other['userenrolment']['lastenrol']) {
            // The user has another enrolment record for this course, so don't remove the f2f session.
            return true;
        }

        $uid = $event->relateduserid;
        $cid = $event->courseid;

        // Get all the facetofaces associated with the course.
        $f2fs = $DB->get_fieldset_select('facetoface', 'id', 'course = :cid', array('cid' => $cid));

        if (!empty($f2fs)) {
            // Get all the sessions for the facetofaces.
            list($insql, $inparams) = $DB->get_in_or_equal($f2fs);
            $sql = "SELECT id FROM {facetoface_sessions} WHERE facetoface {$insql}";
            $sessids = $DB->get_fieldset_sql($sql, $inparams);
            $strvar = new stdClass();
            $strvar->coursename = $DB->get_field('course', 'fullname', array('id' => $cid));

            foreach ($sessids as $sessid) {
                // Check if user is enrolled on any sessions in the future.
                if ($user = facetoface_get_attendee($sessid, $uid)) {
                    if (empty($strvar->username)) {
                        $strvar->username = fullname($user);
                    }

                    // And cancel them.
                    $sess = facetoface_get_session($sessid); // Get the proper session object, complete with dates.
                    facetoface_user_cancel($sess, $uid, false, $null, get_string('cancellationreasoncourseunenrollment', 'mod_facetoface', $strvar));
                }
            }
        }

        return true;
    }
}

/**
 * Called when displaying facetoface Task to check
 * capacity of the session.
 *
 * @param array Message data for a facetoface task
 * @return bool True if there is capacity in the session
 */
function facetoface_task_check_capacity($data) {
    $session = $data['session'];
    // Get session from database in case it has been updated
    $session = facetoface_get_session($session->id);
    if (!$session) {
        return false;
    }
    $facetoface = $data['facetoface'];

    if (!$cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $facetoface->course)) {
        print_error('error:incorrectcoursemodule', 'facetoface');
    }
    $contextmodule = context_module::instance($cm->id);

    return (facetoface_session_has_capacity($session, $contextmodule) || $session->allowoverbook);
}

/**
 * Get first session that occurs at least partly during time periods
 *
 * @access  public
 * @param   array   $times          Array of dates defining time periods
 * @param   integer $userid         Limit sessions to those affecting a user (optional)
 * @param   string  $extrawhere     Custom WHERE additions (optional)
 * @return  array|stdClass
 *
 */
function facetoface_get_sessions_within($times, $userid = null, $extrawhere = '', $extraparams = array()) {
    global $DB;

    $params = array();
    $select = "
             SELECT d.id,
                    c.id AS courseid,
                    c.fullname AS coursename,
                    f.name,
                    f.id AS f2fid,
                    s.id AS sessionid,
                    d.sessiontimezone,
                    d.timestart,
                    d.timefinish
    ";

    $source = "
              FROM {facetoface_sessions_dates} d
        INNER JOIN {facetoface_sessions} s ON s.id = d.sessionid
        INNER JOIN {facetoface} f ON f.id = s.facetoface
        INNER JOIN {course} c ON f.course = c.id
    ";

    $twhere = array();
    foreach ($times as $time) {
        $twhere[] = 'd.timefinish > ? AND d.timestart < ?';
        $params = array_merge($params, array($time->timestart, $time->timefinish));
    }

    if ($times) {
        $where = 'WHERE ((' . implode(') OR (', $twhere) . '))';
    } else {
        // No times were given, so we can't supply sessions within any times. Return an empty array.
        return array();
    }

    // If userid supplied, only return sessions they are waitlisted, booked or attendees, or
    // have been assigned a role in
    if ($userid) {
        $select .= ", ss.statuscode, sr.roleid";

        $source .= "
            LEFT JOIN {facetoface_signups} su
                   ON su.sessionid = s.id AND su.userid = {$userid}
            LEFT JOIN {facetoface_signups_status} ss
                   ON su.id = ss.signupid AND ss.superceded != 1
            LEFT JOIN {facetoface_session_roles} sr
                   ON sr.sessionid = s.id AND sr.userid = {$userid}
        ";

        $where .= ' AND ((ss.id IS NOT NULL AND ss.statuscode >= ?) OR sr.id IS NOT NULL)';
        $params[]  = MDL_F2F_STATUS_WAITLISTED;
    }

    // Ignoring cancelled sessions.
    $where .= ' AND s.cancelledstatus = ?';
    $params[]  = 0;

    $params = array_merge($params, $extraparams);
    $sessions = $DB->get_record_sql($select.$source.$where.$extrawhere, $params, IGNORE_MULTIPLE);

    return $sessions;
}


/**
 * Takes result of get_sessions_within and produces message about existing attendance.
 *
 * This function returns the strings:
 * - error:userassignedsessionconflictsameday
 * - error:userassignedsessionconflictsamedayselfsignup
 * - error:userbookedsessionconflictsameday
 * - error:userbookedsessionconflictsamedayselfsignup
 * - error:userassignedsessionconflictmultiday
 * - error:userassignedsessionconflictmultidayselfsignup
 * - error:userbookedsessionconflictmultiday
 * - error:userbookedsessionconflictmultidayselfsignup
 *
 * @access  public
 * @param   object  $user     User this $info relates to
 * @param   object  $info     Single result from facetoface_get_sessions_within()
 * @return  string
 */
function facetoface_get_session_involvement($user, $info) {
    global $USER;

    // Data to pass to lang string
    $data = new stdClass();

    // Session time data
    $data->timestart = userdate($info->timestart, get_string('strftimetime'), $info->sessiontimezone);
    $data->timefinish = userdate($info->timefinish, get_string('strftimetime'), $info->sessiontimezone);
    $data->datestart = userdate($info->timestart, get_string('strftimedate'), $info->sessiontimezone);
    $data->datefinish = userdate($info->timefinish, get_string('strftimedate'), $info->sessiontimezone);
    $data->datetimestart = userdate($info->timestart, get_string('strftimedatetime'), $info->sessiontimezone);
    $data->datetimefinish = userdate($info->timefinish, get_string('strftimedatetime'), $info->sessiontimezone);

    // Session name/link
    $data->session = html_writer::link(new moodle_url('/mod/facetoface/view.php', array('f' => $info->f2fid)), format_string($info->name));

    // User's participation
    if (!empty($info->roleid)) {
        // Load roles (and cache)
        static $roles;
        if (!isset($roles)) {
            $context = context_course::instance($info->courseid);
            $roles = role_get_names($context);
        }

        // Check if role exists
        if (!isset($roles[$info->roleid])) {
            print_error('error:rolenotfound');
        }

        $data->participation = format_string($roles[$info->roleid]->localname);
        $strkey = "error:userassigned";
    } else {
        $strkey = "error:userbooked";
    }

    // Check if start/finish on the same day
    $strkey .= "sessionconflict";

    if ($data->datestart == $data->datefinish) {
        $strkey .= "sameday";
    } else {
        $strkey .= "multiday";
    }

    if ($user->id == $USER->id) {
        $strkey .= "selfsignup";
    }

    $data->fullname = fullname($user);

    return get_string($strkey, 'facetoface', $data);
}


/**
 * Import user and signup to session
 *
 * @access  public
 * @param   object  $course             Record from the course table
 * @param   object  $facetoface         Record from the facetoface table
 * @param   object  $session            Session to signup user to
 * @param   mixed   $userid             User to signup (normally int)
 * @param   array   $params             Optional suppressemail, ignoreconflicts, bulkaddsource, discountcode, notificationtype, autoenrol
 *          boolean $suppressemail      Suppress notifications flag
 *          boolean $ignoreconflicts    Ignore booking conflicts flag
 *          string  $bulkaddsource      Flag to indicate if $userid is actually another field
 *          string  $discountcode       Optional A user may specify a discount code
 *          integer $notificationtype   Optional A user may choose the type of notifications they will receive
 *          boolean $autoenrol          Optional If user not enrolled on the course then enrols them manually (default true)
 * @return  array
 */
function facetoface_user_import($course, $facetoface, $session, $userid, $params = array()) {
    global $DB, $CFG, $USER;

    $result = array();
    $result['idnumber'] = $userid;

    $suppressemail    = (isset($params['suppressemail'])    ? $params['suppressemail']    : false);
    $ignoreconflicts  = (isset($params['ignoreconflicts'])  ? $params['ignoreconflicts']  : false);
    $bulkaddsource    = (isset($params['bulkaddsource'])    ? $params['bulkaddsource']    : 'bulkaddsourceuserid');
    $discountcode     = (isset($params['discountcode'])     ? $params['discountcode']     : '');
    $notificationtype = (isset($params['notificationtype']) ? $params['notificationtype'] : MDL_F2F_BOTH);
    $autoenrol        = (isset($params['autoenrol'])        ? $params['autoenrol']        : true);

    if (isset($params['approvalreqd'])) {
        // Overwrite default behaviour as bulkadd_* is requested
        $facetoface->approvaltype = $params['approvalreqd'];
    }
    // Comes from "Suppress notifications to manager about added and removed attendees" as 0 value.
    if (isset($params['ccmanager'])) {
        $facetoface->ccmanager = $params['ccmanager'];
    } else {
        // Do not set any value here, value is set in facetoface_notification->ccmanager table.
    }

    // Check parameters.
    if ($bulkaddsource == 'bulkaddsourceuserid') {
        if (!is_int($userid) && !ctype_digit($userid)) {
            $result['name'] = '';
            $result['result'] = get_string('error:userimportuseridnotanint', 'facetoface', $userid);
            return $result;
        }
    }

    // Get user.
    switch ($bulkaddsource) {
        case 'bulkaddsourceuserid':
            $user = $DB->get_record('user', array('id' => $userid));
            break;
        case 'bulkaddsourceidnumber':
            $user = $DB->get_record('user', array('idnumber' => $userid));
            break;
        case 'bulkaddsourceusername':
            $user = $DB->get_record('user', array('username' => $userid));
            break;
    }
    if (!$user) {
        $result['name'] = '';
        $a = array('fieldname' => get_string($bulkaddsource, 'facetoface'), 'value' => $userid);
        $result['result'] = get_string('userdoesnotexist', 'facetoface', $a);
        return $result;
    }

    // Lets get the user ID number as the result output for the id.
    $result['idnumber'] = $user->idnumber;

    $result['name'] = fullname($user);

    if (isguestuser($user)) {
        $a = array('fieldname' => get_string($bulkaddsource, 'facetoface'), 'value' => $userid);
        $result['result'] = get_string('cannotsignupguest', 'facetoface', $a);
        return $result;
    }

    // Make sure that the user is enroled in the course.
    $cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
    if (!is_enrolled($context, $user) && $autoenrol) {

        $defaultlearnerrole = $DB->get_record('role', array('id' => $CFG->learnerroleid));

        if (!enrol_try_internal_enrol($course->id, $user->id, $defaultlearnerrole->id, time())) {
            $result['result'] = get_string('error:enrolmentfailed', 'facetoface', fullname($user));
            return $result;
        }
    }

    $facetoface_allowwaitlisteveryone = get_config(null, 'facetoface_allowwaitlisteveryone');
    if ($session->waitlisteveryone && !empty($facetoface_allowwaitlisteveryone)) {
        $status = MDL_F2F_STATUS_WAITLISTED;
    } else if (!facetoface_session_has_capacity($session, $context)) {
        if ($session->allowoverbook) {
            $status = MDL_F2F_STATUS_WAITLISTED;
        }
    }

    // Check if we are waitlisting or booking
    if ($session->mintimestart) {
        if (!isset($status)) {
            $status = MDL_F2F_STATUS_BOOKED;
        }
    } else {
        $status = MDL_F2F_STATUS_WAITLISTED;
    }

    $jobassignment = null;

    $selectjobassignmentonsignupglobal = get_config(null, 'facetoface_selectjobassignmentonsignupglobal');
    if (!empty($selectjobassignmentonsignupglobal) && !empty($facetoface->selectjobassignmentonsignup)) {

        if (!empty($params['jobassignmentid'])) {
            $jobassignment = \totara_job\job_assignment::get_with_id($params['jobassignmentid']);
            if ($jobassignment->userid != $userid) {
                // There was an error!
            }
        } else {
            $jobassignment = \totara_job\job_assignment::get_first($userid, false);
        }

        // If we still don't have a job assignment and it's mandated then error.
        if (!$jobassignment && !empty($facetoface->forceselectjobassignment)) {
            $result['result'] = get_string('error:nojobassignmentselected', 'facetoface');
            $result['nogoodpos'] = true;
            return $result;
        }
    }

    $managerselect = get_config(null, 'facetoface_managerselect');
    $managerid = null;
    if ($managerselect && isset($params['managerselect'])) {
        $managerid = $params['managerselect'];
    }

    // Do general validation checks for user import to a session.
    $error = facetoface_validate_user_import($user, $context, $facetoface, $session, $ignoreconflicts);
    if (!empty($error)) {
        return $error;
    }

    // Finally attempt to enrol
    if (!facetoface_user_signup(
        $session,
        $facetoface,
        $course,
        $discountcode,
        $notificationtype,
        $status,
        $user->id,
        !$suppressemail,
        null,
        $jobassignment,
        $managerid)) {
            $result['result'] = get_string('error:addattendee', 'facetoface', fullname($user));
            return $result;
    }

    $result['result'] = true;
    return $result;
}

/**
 * Sets totara_set_notification message describing bulk import results
 * @param array $results
 * @param string $type
 */
function facetoface_set_bulk_result_notification($results, $type = 'bulkadd') {
    $added          = $results[0];
    $errors         = $results[1];
    $result_message = '';

    $dialogid = 'f2f-import-results';
    $noticeclass = 'notifysuccess';
    // Generate messages
    if ($errors) {
        $noticeclass = 'notifyproblem';
        $result_message .= get_string($type.'attendeeserror', 'facetoface') . ' - ';

        if (count($errors) == 1 && is_string($errors[0])) {
            $result_message .= $errors[0];
        } else {
            $result_message .= get_string('xerrorsencounteredduringimport', 'facetoface', count($errors));
            $result_message .= ' <a href="#" class="'.$dialogid.'">('.get_string('viewresults', 'facetoface').')</a>';
        }
    } else if ($added) {
        $result_message .= get_string($type.'attendeessuccess', 'facetoface') . ' - ';
        if ($type == 'bulkremove') {
            $result_message .= get_string('successfullyremovedxattendees', 'facetoface', count($added));
        } else {
            $result_message .= get_string('successfullyaddededitedxattendees', 'facetoface', count($added));
        }
        $result_message .= ' <a href="#" class="'.$dialogid.'">('.get_string('viewresults', 'facetoface').')</a>';
    }

    if ($result_message != '') {
        totara_set_notification($result_message, null, array('class' => $noticeclass));
    }
}
/**
 * Return message describing bulk import results
 *
 * @access  public
 * @param   array       $results
 * @param   string      $type
 * @return  string
 */
function facetoface_generate_bulk_result_notice($results, $type = 'bulkadd') {
    $added          = $results[0];
    $errors         = $results[1];
    $result_message = '';

    $dialogid = 'f2f-import-results';
    $noticeclass = ($added) ? 'addedattendees' : 'noaddedattendees';
    // Generate messages
    if ($errors) {
        $result_message .= '<div class="' . $noticeclass . ' notifyproblem">';
        $result_message .= get_string($type.'attendeeserror', 'facetoface') . ' - ';

        if (count($errors) == 1 && is_string($errors[0])) {
            $result_message .= $errors[0];
        } else {
            $result_message .= get_string('xerrorsencounteredduringimport', 'facetoface', count($errors));
            $result_message .= ' <a href="#" id="'.$dialogid.'">('.get_string('viewresults', 'facetoface').')</a>';
        }
        $result_message .= '</div>';
    }
    if ($added) {
        $result_message .= '<div class="' . $noticeclass . ' notifysuccess">';
        $result_message .= get_string($type.'attendeessuccess', 'facetoface') . ' - ';
        $result_message .= get_string('successfullyaddededitedxattendees', 'facetoface', count($added));
        $result_message .= ' <a href="#" id="'.$dialogid.'">('.get_string('viewresults', 'facetoface').')</a>';
        $result_message .= '</div>';
    }

    return $result_message;
}

/**
 * Kohl's KW - WP06A - Google calendar integration
 *
 * If the unassigned user belongs to a course with an upcoming
 * face-to-face session and they are signed-up to attend, cancel
 * the sign-up (and trigger notification).
 */
function facetoface_eventhandler_role_unassigned($ra) {
    global $CFG, $USER, $DB;

    $now = time();

    $ctx = context::instance_by_id($ra->contextid);
    if ($ctx->contextlevel == CONTEXT_COURSE) {
        // get all face-to-face activites in the course
        $activities = $DB->get_records('facetoface', array('course' => $ctx->instanceid));
        if ($activities) {
            foreach ($activities as $facetoface) {
                // get all upcoming sessions for each face-to-face
                $sql = "SELECT s.id
                        FROM {facetoface_sessions} s
                        LEFT JOIN {facetoface_sessions_dates} d ON s.id = d.sessionid
                        WHERE
                            s.facetoface = ? AND d.sessionid = s.id AND
                            (d.timestart IS NULL OR d.timestart > ?)
                        ORDER BY d.timestart
                ";

                if ($sessions = $DB->get_records_sql($sql, array($facetoface->id, $now))) {
                    $cancelreason = "Unenrolled from course";
                    foreach ($sessions as $sessiondata) {
                        $session = facetoface_get_session($sessiondata->id); // load dates etc.

                        // remove trainer session assignments for user (if any exist)
                        if ($trainers = facetoface_get_trainers($session->id)) {
                            foreach ($trainers as $role_id => $users) {
                                foreach ($users as $user_id => $trainer) {
                                    if ($trainer->id == $ra->userid) {
                                        $form = $trainers;
                                        unset($form[$role_id][$user_id]); // remove trainer
                                        facetoface_update_trainers($session->id, $form);
                                        break;
                                    }
                                }
                            }
                        }

                        // cancel learner signup for user (if any exist)
                        $errorstr = '';
                        if (facetoface_user_cancel($session, $ra->userid, true, $errorstr, $cancelreason)) {
                            facetoface_send_cancellation_notice($facetoface, $session, $ra->userid);
                        }
                    }
                }
            }
        }
    } else if ($ctx->contextlevel == CONTEXT_PROGRAM) {
        // nothing to do (probably)
    }

    return true;
}

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settings The settings navigation object
 * @param navigation_node $facetofacenode The node to add module settings to
 */
function facetoface_extend_settings_navigation(settings_navigation $settings, navigation_node $facetofacenode) {
    global $PAGE, $DB;

    $mode = optional_param('mode', '', PARAM_ALPHA);
    $hook = optional_param('hook', 'ALL', PARAM_CLEAN);

    $context = context_module::instance($PAGE->cm->id);
    if (has_capability('moodle/course:manageactivities', $context)) {
        $facetofacenode->add(get_string('notifications', 'facetoface'), new moodle_url('/mod/facetoface/notification/index.php', array('update' => $PAGE->cm->id)), navigation_node::TYPE_SETTING);
    }

    $facetoface = $DB->get_record('facetoface', array('id' => $PAGE->cm->instance), '*', MUST_EXIST);
    if ($facetoface->declareinterest && has_capability('mod/facetoface:viewinterestreport', $context)) {
        $facetofacenode->add(get_string('declareinterestreport', 'facetoface'), new moodle_url('/mod/facetoface/interestreport.php', array('facetofaceid' => $PAGE->cm->instance)), navigation_node::TYPE_SETTING);
    }
}


// Download functions for attendees tables
/** Download data in ODS format
  *
  * @param array $fields Array of column headings
  * @param string $datarows Array of data to populate table with
  * @param string $file Name of file for exportig
  * @return Returns the ODS file
 */
function facetoface_download_ods($fields, $datarows, $file=null) {
    global $CFG, $DB;

    require_once("$CFG->libdir/odslib.class.php");
    $filename = clean_filename($file . '.ods');

    header("Content-Type: application/download\n");
    header("Content-Disposition: attachment; filename=$filename");
    header("Expires: 0");
    header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
    header("Pragma: public");

    $workbook = new MoodleODSWorkbook('-');
    $workbook->send($filename);

    $worksheet = array();

    $worksheet[0] = $workbook->add_worksheet('');
    $row = 0;
    $col = 0;

    foreach ($fields as $field) {
        $worksheet[0]->write($row, $col, strip_tags($field));
        $col++;
    }
    $row++;

    $numfields = count($fields);

    foreach ($datarows as $record) {
        for($col=0; $col<$numfields; $col++) {
            if (isset($record[$col])) {
                $worksheet[0]->write($row, $col, html_entity_decode($record[$col], ENT_COMPAT, 'UTF-8'));
            }
        }
        $row++;
    }

    $workbook->close();
    die;
}


/** Download data in XLS format
  *
  * @param array $fields Array of column headings
  * @param string $datarows Array of data to populate table with
  * @param string $file Name of file for exportig
  * @return Returns the Excel file
  */
function facetoface_download_xls($fields, $datarows, $file=null) {
    global $CFG, $DB;

    require_once($CFG->libdir . '/excellib.class.php');

    $filename = clean_filename($file . '.xls');

    header("Content-Type: application/download\n");
    header("Content-Disposition: attachment; filename=$filename");
    header("Expires: 0");
    header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
    header("Pragma: public");

    $workbook = new MoodleExcelWorkbook('-');
    $workbook->send($filename);

    $worksheet = array();

    $worksheet[0] = $workbook->add_worksheet('');
    $row = 0;
    $col = 0;

    foreach ($fields as $field) {
        $worksheet[0]->write($row, $col, strip_tags($field));
        $col++;
    }
    $row++;

    $numfields = count($fields);

    foreach ($datarows as $record) {
        for ($col=0; $col<$numfields; $col++) {
            $worksheet[0]->write($row, $col, html_entity_decode($record[$col], ENT_COMPAT, 'UTF-8'));
        }
        $row++;
    }

    $workbook->close();
    die;
}


/** Download data in CSV format
  *
  * @param array $fields Array of column headings
  * @param string $datarows Array of data to populate table with
  * @param string $file Name of file for exportig
  * @return Returns the CSV file
  */
function facetoface_download_csv($fields, $datarows, $file=null) {
    global $CFG;

    require_once($CFG->libdir . '/csvlib.class.php');

    $csvexport = new csv_export_writer();
    $csvexport->set_filename($file);
    $csvexport->add_data($fields);

    $numfields = count($fields);
    foreach ($datarows as $record) {
        $row = array();
        for ($j = 0; $j < $numfields; $j++) {
            $row[] = (isset($record[$j]) ? $record[$j] : '');
        }
        $csvexport->add_data($row);
    }

    $csvexport->download_file();
    die;
}

/**
 * Main calendar hook for filtering f2f events (if necessary)
 *
 * @param array $events from the events table
 * @uses $SESSION->calendarfacetofacefilter - contains an assoc array of filter fieldids and vals
 *
 * @return void
 */
function facetoface_filter_calendar_events(&$events) {
    global $SESSION;
    if (empty($SESSION->calendarfacetofacefilter)) {
        return;
    }
    $filters = $SESSION->calendarfacetofacefilter;
    foreach ($events as $eid => $event) {
        $event = new calendar_event($event);
        if ($event->modulename != 'facetoface') {
            continue;
        }

        $cfield_vals = facetoface_get_customfielddata($event->uuid);

        foreach ($filters as $type => $filter) {
            foreach ($filter as $shortname => $fval) {
                if (empty($fval) || $fval == 'all') {  // ignore empty filters
                    continue;
                }
                if (empty($cfield_vals[$type][$shortname])) {
                    // no reason comparing empty values :D
                    unset($events[$eid]);
                    break;
                }
                $filterval = core_text::strtolower($fval);
                $fielddval = core_text::strtolower($cfield_vals[$type][$shortname]);
                if (core_text::strpos($fielddval, $filterval) === false) {
                    unset($events[$eid]);
                    break;
                }
            }
        }
    }
}

/**
 * Main calendar hook for settinging f2f calendar filters
 *
 * @uses $SESSION->calendarfacetofacefilter - initialises assoc array of filter fieldids and vals
 *
 * @return void
 */
function facetoface_calendar_set_filter() {
    global $SESSION;

    $fieldsall = facetoface_get_customfield_filters();

    $SESSION->calendarfacetofacefilter = array();
    foreach ($fieldsall as $type => $fields) {
        if (!isset($SESSION->calendarfacetofacefilter[$type])) {
            $SESSION->calendarfacetofacefilter[$type] = array();
        }
        foreach ($fields as $field) {
            $fieldname = "field_{$type}_{$field->shortname}";
            $SESSION->calendarfacetofacefilter[$type][$field->shortname] = optional_param($fieldname, '', PARAM_TEXT);
        }
    }
}

/**
 * Get custom field filters that are currently selected in facetoface settings
 *
 * @return array Array of objects if any filter is found, empty array otherwise
 */
function facetoface_get_customfield_filters() {
    global $DB;

    $sessfields = array();
    $roomfields = array();
    $allsearchfields = get_config(null, 'facetoface_calendarfilters');
    if ($allsearchfields) {
        $customfieldids = array('sess' => array(), 'room' => array());
        $allsearchfields = explode(',', $allsearchfields);

        foreach ($allsearchfields as $filterkey) {
            // Customfields are prefixed with room_ and sess_ strings
            // @see settings.php refer to facetoface_calendarfilters setting for details.
            if (strpos($filterkey, 'sess_') === 0) {
                $customfieldids['sess'][] = explode('_', $filterkey)[1];
            }
            if (strpos($filterkey, 'room_') === 0) {
                $customfieldids['room'][] = explode('_', $filterkey)[1];
            }
        }
        if (!empty($customfieldids['sess'])) {
            list($cfids, $cfparams) = $DB->get_in_or_equal($customfieldids['sess']);
            $sql = "SELECT * FROM {facetoface_session_info_field} WHERE id $cfids";
            $sessfields = $DB->get_records_sql($sql, $cfparams);
        }
        if (!empty($customfieldids['room'])) {
            list($cfids, $cfparams) = $DB->get_in_or_equal($customfieldids['room']);
            $sql = "SELECT * FROM {facetoface_room_info_field} WHERE id $cfids";
            $roomfields = $DB->get_records_sql($sql, $cfparams);
        }
    }

    return array('sess' => $sessfields, 'room' => $roomfields);
}

/**
 * Serves the facetoface and sessions details.
 *
 * @param stdClass $course course object
 * @param cm_info $cm course module object
 * @param context $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function facetoface_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $DB;

    if ($context->contextlevel == CONTEXT_SYSTEM && ($filearea === 'room' || $filearea === 'asset')) {
        // NOTE: we do not know where is the room and asset description visible,
        //       this means we cannot do any strict access control, bad luck.
        $fs = get_file_storage();
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_facetoface/$filearea/$relativepath";
        if (!$file = $fs->get_file_by_hash(sha1($fullpath))) {
            return false;
        }
        // This function will stop code.
        send_stored_file($file, 360, 0, true, $options);
    }

    $sessionid = (int)array_shift($args);
    if (!$DB->get_record('facetoface_sessions', array('id' => $sessionid, 'facetoface' => $cm->instance))) {
        return false;
    }

    $fileinstance = function() use($context, $filearea, $args, $sessionid) {
        $fs = get_file_storage();
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_facetoface/$filearea/$sessionid/$relativepath";
        if (!$file = $fs->get_file_by_hash(sha1($fullpath))) {
            return false;
        }
        return $file;
    };

    if ($context->contextlevel != CONTEXT_MODULE || $filearea !== 'session') {
        return false;
    }

    // NOTE: we do not know where is the session details text displayed,
    //       this means we cannot do any strict access control, bad luck.
    $storedfile = $fileinstance();
    send_stored_file($storedfile, 360, 0, true, $options);
}

/**
 * Removes grades and resets completion
 *
 * @global object $CFG
 * @global object $DB
 * @param int $userid
 * @param int $courseid
 * @return boolean
 */
function facetoface_archive_completion($userid, $courseid, $windowopens = NULL) {
    global $DB, $CFG;

    require_once($CFG->libdir . '/completionlib.php');

    if (!isset($windowopens)) {
        $windowopens = time();
    }

    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    $completion = new completion_info($course);

    // All facetoface sessions with this course and user.
    $sql = "SELECT f.*
            FROM {facetoface} f
            WHERE f.course = :courseid
            AND EXISTS (SELECT su.id
                        FROM {facetoface_sessions} s
                        JOIN {facetoface_signups} su ON su.sessionid = s.id AND su.userid = :userid
                        WHERE s.facetoface = f.id)";
    $facetofaces = $DB->get_records_sql($sql, array('courseid' => $courseid, 'userid' => $userid));
    foreach ($facetofaces as $facetoface) {
        // Add an archive flag.
        $params = array('facetofaceid' => $facetoface->id, 'userid' => $userid, 'archived' => 1, 'archived2' => 1, 'windowopens' => $windowopens);
        $sql = "UPDATE {facetoface_signups}
                SET archived = :archived
                WHERE userid = :userid
                AND archived <> :archived2
                AND EXISTS (SELECT s.id, MAX(sd.timefinish) as maxfinishtime
                            FROM {facetoface_sessions} s
                            LEFT JOIN {facetoface_sessions_dates} sd ON s.id = sd.sessionid
                            WHERE s.id = {facetoface_signups}.sessionid
                            AND s.facetoface = :facetofaceid
                            AND sd.id IS NOT NULL
                            GROUP BY s.id
                            HAVING MAX(sd.timefinish) <= :windowopens)";
        // NOTE: Timefinish can be, at most, the date/time that the course/cert was completed. In the windowopens check, we
        // do <= rather than < because windowopens may be equal to timefinish when the cert active period is equal to the window
        // period. Luckily, window period cannot be more than the active period, so the window cannot open before timefinish.
        $DB->execute($sql, $params);

        // Reset the grades.
        facetoface_update_grades($facetoface, $userid, true);

        // Set completion to incomplete.
        // Reset viewed.
        $course_module = get_coursemodule_from_instance('facetoface', $facetoface->id, $courseid);
        $completion->set_module_viewed_reset($course_module, $userid);
        // And reset completion, in case viewed is not a required condition.
        $completion->update_state($course_module, COMPLETION_INCOMPLETE, $userid);
        $completion->invalidatecache($courseid, $userid, true);
    }
}

/**
 * Get attendance status
 */
function facetoface_get_attendance_status() {
    global $MDL_F2F_STATUS;

    // Look for status fully_attended, partially_attended and no_show.
    $statusoptions = array();
    foreach ($MDL_F2F_STATUS as $key => $value) {
        if ($key <= MDL_F2F_STATUS_BOOKED) {
            continue;
        }
        $statusoptions[$key] = get_string('status_' . $value, 'facetoface');
    }

    return array_reverse($statusoptions, true);
}
/**
 * Displays a bulk actions selector
 */
function facetoface_display_bulk_actions_picker() {
    global $OUTPUT;

    $status_options = facetoface_get_attendance_status();
    unset($status_options[MDL_F2F_STATUS_NOT_SET]);
    $out = $OUTPUT->container_start('facetoface-bulk-actions-picker');
    $select = html_writer::select($status_options, 'bulkattendanceop', '',
        array('' => get_string('bulkactions', 'facetoface')), array('class' => 'bulkactions'));
    $label = get_string('mark_selected_as', 'facetoface');
    $error = get_string('selectoptionbefore', 'facetoface');
    $hidenlabel = html_writer::tag('span', $error, array('id' => 'selectoptionbefore', 'class' => 'hide error'));
    $out .= $label;
    $out .= $select;
    $out .= $hidenlabel;
    $out .= $OUTPUT->container_end();

    return $out;
}

/**
 * Count how many spaces the current user has reserved in the given face to face instance.
 * @param object $facetoface
 * @param int $managerid
 * @return array 'all' => total count; sessionid => session count
 */
function facetoface_count_reservations($facetoface, $managerid) {
    global $DB;
    static $reservations = array();

    if (!isset($reservations[$facetoface->id])) {
        $sql = 'SELECT s.id, COUNT(*) AS reservecount
                  FROM {facetoface_sessions} s
                  JOIN {facetoface_signups} su ON su.sessionid = s.id
                 WHERE s.facetoface = :facetofaceid AND su.bookedby = :userid AND su.userid = 0
                 GROUP BY s.id';
        $params = array('facetofaceid' => $facetoface->id, 'userid' => $managerid);
        $reservations[$facetoface->id] = $DB->get_records_sql_menu($sql, $params);
        $reservations[$facetoface->id]['all'] = array_sum($reservations[$facetoface->id]);
    }

    return $reservations[$facetoface->id];
}

/**
 * Count how many allocations the current user has made in the given face to face instance.
 * @param object $facetoface
 * @param int $managerid
 * @return array 'all' => total count; sessionid => session count
 */
function facetoface_count_allocations($facetoface, $managerid) {
    global $DB;
    static $allocations = array();

    if (!isset($allocations[$facetoface->id])) {
        $sql = 'SELECT s.id, COUNT(*) AS allocatecount
                  FROM {facetoface_sessions} s
                  JOIN {facetoface_signups} su ON su.sessionid = s.id
                  JOIN {facetoface_signups_status} sus ON sus.signupid = su.id AND sus.superceded = 0
                                                       AND sus.statuscode > :cancelled
                 WHERE s.facetoface = :facetofaceid AND su.bookedby = :userid AND su.userid <> 0
                 GROUP BY s.id';
        $params = array('facetofaceid' => $facetoface->id, 'userid' => $managerid, 'cancelled' => MDL_F2F_STATUS_USER_CANCELLED);
        $allocations[$facetoface->id] = $DB->get_records_sql_menu($sql, $params);
        $allocations[$facetoface->id]['all'] = array_sum($allocations[$facetoface->id]);
    }

    return $allocations[$facetoface->id];
}

/**
 * Returns details of whether or not the user can reserve or allocate spaces for their team.
 * Note - an exception is throw if the managerid is set to another user and the current user is missing the
 * 'reserveother' capability
 *
 * @param object $facetoface
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
function facetoface_can_reserve_or_allocate($facetoface, $sessions, $context, $managerid = null) {
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
    if (!$facetoface->managerreserve) {
        return $ret; // Manager reservations disabled for this activity.
    }

    $ret['reserveother'] = $reserveother;
    $ret['reservedeadline'] = time() + ($facetoface->reservedays * DAYSECS);
    $ret['reservecancel'] = time() + ($facetoface->reservecanceldays * DAYSECS);

    if (!has_capability('mod/facetoface:reservespace', $context, $managerid)) {
        return $ret; // Manager is not allowed to reserve/allocate any spaces.
    }

    if (!\totara_job\job_assignment::has_staff($managerid)) {
        return $ret; // No staff to allocate spaces to.
    }

    // Allowed to make allocations / reservations - gather some details about the spaces remaining.
    $allocations = facetoface_count_allocations($facetoface, $managerid);
    $reservations = facetoface_count_reservations($facetoface, $managerid);
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
        $ret['maxallocate'][$sid] = $facetoface->maxmanagerreserves - ($allocations['all'] - $allocation);
        $ret['maxallocate'][$sid] -= ($reservations['all'] - $reservation);
        $ret['allocate'][$sid] = $ret['maxallocate'][$sid] - $allocation; // Number left to allocate.

        // Max reservations = overall max - allocations (all) - reservations for other sessions
        $ret['maxreserve'][$sid] = $facetoface->maxmanagerreserves - $allocations['all'];
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
 * Given the number of spaces the manager has reserved / allocated (from 'can_reserve_or_allocate')
 * and the overall remaining capacity of the particular session, work out how many spaces they can
 * actually reserve/allocate for this session.
 *
 * @param int $sessionid
 * @param array $reserveinfo
 * @param int $capacityleft
 * @return array - see facetoface_can_reserve_or_allocate for details
 */
function facetoface_limit_reserveinfo_to_capacity_left($sessionid, $reserveinfo, $capacityleft) {
    if (!empty($reserveinfo['reserve'])) {
        if ($reserveinfo['reserve'][$sessionid] > $capacityleft) {
            $reserveinfo['reserve'][$sessionid] = $capacityleft;
            $reserveinfo['maxreserve'][$sessionid] = $reserveinfo['reserve'][$sessionid] + $reserveinfo['reserved'][$sessionid];
        }
    }
    return $reserveinfo;
}

/**
 * Given the session details, determines if reservations are still allowed, or if the deadline has now passed.
 *
 * @param array $reserveinfo
 * @param object $session
 * @return array - see facetoface_can_reserve_or_allocate for details, but adds two new values:
 *                  'reservepastdeadline' - true if the deadline for adding new reservations has passed
 *                  'reservepastcancel' - true if all existing reservations should be cancelled
 */
function facetoface_limit_reserveinfo_by_session_date($reserveinfo, $session) {
    $reserveinfo['reservepastdeadline'] = false;
    $reserveinfo['reservepastcancel'] = false;
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

/**
 * Add the number of reservations requested (it is assumed that all capacity checks have
 * already been done by this point, so no extra checking is performed).
 *
 * @param object $session the session the reservations are for
 * @param int $bookedby the user making the reservations
 * @param int $number how many reservations to make
 * @param int $waitlisted how many reservations to add to the waitlist (not included in $number)
 */
function facetoface_add_reservations($session, $bookedby, $number, $waitlisted) {
    global $DB;

    $usersignup = (object)array(
        'sessionid' => $session->id,
        'userid' => 0,
        'notificationtype' => MDL_F2F_NOTIFICATION_AUTO,
        'archived' => 0,
        'bookedby' => $bookedby,
    );

    for ($i=0; $i<($number+$waitlisted); $i++) {
        $usersignup->id = $DB->insert_record('facetoface_signups', $usersignup);
        if ($session->mintimestart && ($i < $number)) {
            $status = MDL_F2F_STATUS_BOOKED;
        } else {
            $status = MDL_F2F_STATUS_WAITLISTED;
        }
        facetoface_update_signup_status($usersignup->id, $status, $bookedby);
    }

    facetoface_update_attendees($session);
}

/**
 * Remove the (up to) the given number of reservations originally made by the given user.
 *
 * @param object $facetoface
 * @param object $session the session to remove the reservations from
 * @param int $bookedby the user who made the original reservations
 * @param int $number the number of reservations to remove
 * @param bool $sendnotification
 */
function facetoface_remove_reservations($facetoface, $session, $bookedby, $number, $sendnotification = false) {
    global $DB;

    $sql = 'SELECT su.id
              FROM {facetoface_signups} su
              JOIN {facetoface_signups_status} sus ON sus.signupid = su.id AND sus.superceded = 0
             WHERE su.sessionid = :sessionid AND su.userid = 0 AND su.bookedby = :bookedby
             ORDER BY sus.statuscode ASC, id DESC';
    // Start by deleting low-status reservations (cancelled, waitlisted), then order by most recently booked.
    $params = array('sessionid' => $session->id, 'bookedby' => $bookedby);

    $reservations = $DB->get_records_sql($sql, $params, 0, $number);
    $removecount = count($reservations);
    foreach ($reservations as $reservation) {
        $DB->delete_records('facetoface_signups_status', array('signupid' => $reservation->id));
        $DB->delete_records('facetoface_signups', array('id' => $reservation->id));
    }

    if ($removecount && $sendnotification) {
        $params = array(
            'facetofaceid' => $facetoface->id,
            'type' => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_RESERVATION_CANCELLED,
        );
        facetoface_send_notice($facetoface, $session, $bookedby, $params);
    }

    facetoface_update_attendees($session);
}

/**
 * Replace the manager reservations for this session with allocations for the given userids.
 * The list of userids still to be allocated will be returned.
 * Note: There are no checks made to see if the given users have already booked on a session, etc. -
 * it is assumed that any such checks have been completed before calling this function.
 *
 * @param object $session
 * @param object $facetoface
 * @param object $course
 * @param int $bookedby
 * @param int[] $userids
 * @throws moodle_exception
 * @return int[]
 */
function facetoface_replace_reservations($session, $facetoface, $course, $bookedby, $userids) {
    global $DB, $CFG;

    $facetoface->approvalreqd = false; // Make sure they are directly signed-up.

    $sql = 'SELECT su.id, sus.statuscode, su.discountcode, su.notificationtype
              FROM {facetoface_signups} su
              JOIN {facetoface_signups_status} sus ON sus.signupid = su.id AND sus.superceded = 0
             WHERE su.sessionid = :sessionid AND su.userid = 0 AND su.bookedby = :bookedby
             ORDER BY sus.statuscode DESC, id DESC';
    // Prioritise allocating high-status reservations (booked) over lower-status reservations (waitinglist)
    $params = array('sessionid' => $session->id, 'bookedby' => $bookedby);
    $reservations = $DB->get_records_sql($sql, $params, 0, count($userids));

    foreach ($reservations as $reservation) {
        $userid = array_shift($userids);
        // Make sure that the user is enroled in the course
        $context = context_course::instance($course->id);
        if (!is_enrolled($context, $userid)) {
            $defaultlearnerrole = $DB->get_record('role', array('id' => $CFG->learnerroleid));
            if (!enrol_try_internal_enrol($course->id, $userid, $defaultlearnerrole->id, time())) {
                throw new moodle_exception('unabletoenrol', 'mod_facetoface');
            }
        }

        if ($oldbooking = $DB->get_record('facetoface_signups', array('sessionid' => $session->id, 'userid' => $userid))) {
            // This could happen if a user booked themselves, then cancelled and are now being allocated by their manager.

            // Delete the reservation completely.
            $DB->delete_records('facetoface_signups_status', array('signupid' => $reservation->id));
            $DB->delete_records('facetoface_signups', array('id' => $reservation->id));

            // Update the bookedby field.
            $DB->set_field('facetoface_signups', 'bookedby', $bookedby, array('id' => $oldbooking->id));

        } else {
            // Switch the booking over to the given user.
            $upd = (object)array(
                'id' => $reservation->id,
                'userid' => $userid,
                'sessionid' => $session->id,
            );
            $DB->update_record('facetoface_signups', $upd);
        }

        // Make sure the status is set and the correct notification messages are sent.
        facetoface_user_signup($session, $facetoface, $course, $reservation->discountcode, $reservation->notificationtype,
                               $reservation->statuscode, $userid);
    }

    return $userids;
}

/**
 * Allocate spaces to all the users specified.
 * Note: there are no checks done against the user's allocation limit.
 *
 * @param object $session
 * @param object $facetoface
 * @param object $course
 * @param int $bookedby
 * @param int[] $userids
 * @param int $capacityleft how much (non-waitlist) space there is left on the session
 * @throws moodle_exception
 */
function facetoface_allocate_spaces($session, $facetoface, $course, $bookedby, $userids, $capacityleft) {
    global $DB, $CFG;

    $facetoface->approvaltype = APPROVAL_NONE; // Make sure they are directly signed-up.

    foreach ($userids as $userid) {
        // Make sure that the user is enroled in the course
        $context = context_course::instance($course->id);
        if (!is_enrolled($context, $userid)) {
            $defaultlearnerrole = $DB->get_record('role', array('id' => $CFG->learnerroleid));
            if (!enrol_try_internal_enrol($course->id, $userid, $defaultlearnerrole->id, time())) {
                throw new moodle_exception('unabletoenrol', 'mod_facetoface');
            }
        }

        $status = MDL_F2F_STATUS_BOOKED;
        if ($capacityleft <= 0) {
            $status = MDL_F2F_STATUS_WAITLISTED;
        }

        // Make sure the status is set and the correct notification messages are sent.
        if (facetoface_user_signup($session, $facetoface, $course, null, MDL_F2F_BOTH, $status, $userid)) {
            $DB->set_field('facetoface_signups', 'bookedby', $bookedby, array('sessionid' => $session->id, 'userid' => $userid));
        }
        $capacityleft--;
    }
}

/**
 * Remove the given allocations and, optionally, convert them back into reservations.
 *
 * @param object $session
 * @param object $facetoface
 * @param object $course
 * @param int[] $userids
 * @param bool $converttoreservations if true, convert allocations to reservations, if false, just cancel
 * @param int $managerid optional defaults to current user
 */
function facetoface_remove_allocations($session, $facetoface, $course, $userids, $converttoreservations, $managerid = null) {
    global $DB, $USER;

    if (!$managerid) {
        $managerid = $USER->id;
    }

    foreach ($userids as $userid) {
        $transaction = $DB->start_delegated_transaction();
        $userisinwaitlist = facetoface_is_user_on_waitlist($session, $userid);

        facetoface_user_cancel($session, $userid);

        if ($converttoreservations) {
            // Add one reservation.
            $book = 1;
            $waitlist = 0;
            if ($userisinwaitlist) {
                $book = 0;
                $waitlist = 1;
            }
            facetoface_add_reservations($session, $managerid, $book, $waitlist);
        }
        $transaction->allow_commit();

        // Send notification.
        if (!empty($session->sessiondates) && $userisinwaitlist === false) {
            facetoface_send_cancellation_notice($facetoface, $session, $userid);
        }
    }
}

/**
 * Get a list of staff who can be allocated / deallocated + reasons why other users cannot be allocated.
 *
 * @param object $facetoface
 * @param object $session
 * @param int $managerid optional
 * @return object containing potential - list of users who could be allocated
 *                           current - list of users who are already allocated
 *                           othersession - users allocated to another sesssion
 *                           cannotunallocate - users who cannot be unallocated (also listed in 'current')
 */
function facetoface_get_staff_to_allocate($facetoface, $session, $managerid = null) {
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
    $multiplesignups = $facetoface->multiplesessions;

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

    $params['facetofaceid'] = $facetoface->id;
    // Statuses greater than declined to handle cases where people change their mind.
    $params['status'] = MDL_F2F_STATUS_DECLINED;
    $users = $DB->get_records_sql($sql, $params);

    foreach ($staff as $member) {
        // Get the signups for the user in this activity.
        $usersignups = totara_search_for_value($users, 'id', TOTARA_SEARCH_OP_EQUAL, $member);

        // Get signup for this user in this session (if exists).
        $usersignupsession = totara_search_for_value($usersignups, 'sessionid', TOTARA_SEARCH_OP_EQUAL, $session->id);

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

            facetoface_user_can_be_unallocated($user, $managerid);
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
            if (!facetoface_user_can_be_unallocated($usersignupsession, $managerid)) {
                $ret->cannotunallocate[$member] = $usersignupsession;
            }
            $ret->current[$member] = $usersignupsession;
        }
    }

    return $ret;
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
function facetoface_user_can_be_unallocated(&$user, $managerid) {
    // Booked by someone else or self booking - cannot be unbooked.
    if ($user->bookedby != $managerid) {
        $user->cannotremove = ($user->bookedby == 0) ? 'selfbooked' : 'otherbookedby';
        return false;
    } else if ($user->statuscode && $user->statuscode > MDL_F2F_STATUS_BOOKED) {
        $user->cannotremove = 'attendancetaken'; // Attendance taken - cannot be unbooked.
        return false;
    }

    return true;
}

/**
 *
 * @param int   $userid     The user whose manager we are looking for
 * @param int   $sessionid  The session where the manager is assigned
 * @param int   $jobassignmentid The job when users are allowed to select their secondary jobs !!! "Seconardy" jobs ???
 * @return array of object   The user object (including fullname) of the user assigned as the learners managers
 */
function facetoface_get_session_managers($userid, $sessionid, $jobassignmentid = null) {
    global $DB;

    $managerselect = get_config(null, 'facetoface_managerselect');
    $selectjobassignmentonsignupglobal = get_config(null, 'facetoface_selectjobassignmentonsignupglobal');
    $signup = $DB->get_record('facetoface_signups', array('userid' => $userid, 'sessionid' => $sessionid));

    $managers = array();

    if ($managerselect && !empty($signup->managerid)) {
        // Check if they selected a manager for their signup.
        $manager = $DB->get_record('user', array('id' => $signup->managerid));
        $managers[] = $manager;
    } else if ($selectjobassignmentonsignupglobal && !empty($jobassignmentid)) {
        // The job assignment could not be found here, because the system admin might had deleted
        // the job assignment record, but did not update the seminar signup record here.

        // This could mean that, seminar system is not able to notify this user's manager here.
        // However, when deleting the job assignment of a user, this could indicate that this
        // user is no longer being managed by the same manager anymore. Unless, deleting job
        // assignment is an accident.
        $ja = \totara_job\job_assignment::get_with_id($jobassignmentid, false);
        if (null != $ja && $ja->managerid) {
            $managers[] = $DB->get_record('user', array('id' => $ja->managerid));
        }
    } else {
        $managerids = \totara_job\job_assignment::get_all_manager_userids($userid);
        if (!empty($managerids)) {
            list($mansql, $manparams) = $DB->get_in_or_equal($managerids, SQL_PARAMS_NAMED);
            $managers = $DB->get_records_select('user', "id $mansql", $manparams);
        }
    }

    foreach ($managers as &$manager) {
        $manager->fullname = fullname($manager);
    }

    return $managers;
}

/**
 * Get a full list of all managers on the system.
 *
 * @return array
 */
function facetoface_get_manager_list() {
    global $CFG, $DB;

    $ret = array();

    $usernamefields = get_all_user_name_fields(true, 'u');
    $sql = "SELECT DISTINCT u.id, {$usernamefields}
              FROM {job_assignment} staffja
              JOIN {job_assignment} managerja ON staffja.managerjaid = managerja.id
              JOIN {user} u ON u.id = managerja.userid
             ORDER BY u.lastname, u.firstname";
    $managers = $DB->get_records_sql($sql);
    foreach ($managers as $manager) {
        $ret[$manager->id] = fullname($manager);
    }

    if (!empty($CFG->enabletempmanagers)) {
        $sql = "SELECT DISTINCT u.id, {$usernamefields}
                  FROM {job_assignment} staffja
                  JOIN {job_assignment} tempmanagerja ON staffja.tempmanagerjaid = tempmanagerja.id
                  JOIN {user} u ON u.id = tempmanagerja.userid
                 ORDER BY u.lastname, u.firstname";
        $params = array(time());
        $tempmanagers = $DB->get_records_sql($sql, $params);
        foreach ($tempmanagers as $tempmanager) {
            $ret[$tempmanager->id] = fullname($tempmanager);
        }
    }

    return $ret;
}

/**
 * Returns the details of all the other reservations made in the current face to face
 * by the given manager
 *
 * @param object $facetoface
 * @param object $session
 * @param int $managerid
 * @return object[]
 */
function facetoface_get_other_reservations($facetoface, $session, $managerid) {
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
    $params = array('managerid' => $managerid, 'sessionid' => $session->id, 'facetofaceid' => $facetoface->id,
                    'cancelled' => MDL_F2F_STATUS_USER_CANCELLED);

    return $DB->get_records_sql($sql, $params);
}

/**
 * Determines whether the user has already expressed interest in this activity.
 *
 * @param  object $facetoface A database fieldset object for the facetoface activity
 * @param  object $userid     Default to current user if null
 * @return boolean whether a person needs a manager to sign up for that activity
 */
function facetoface_user_declared_interest($facetoface, $userid = null) {
    global $DB, $USER;

    if (is_null($userid)) {
        $userid = $USER->id;
    }

    return $DB->record_exists('facetoface_interest', array('facetoface' => $facetoface->id, 'userid' => $userid));
}

/**
 * Determines whether the user can declare interest in the activity.
 *
 * @param  object $facetoface A database fieldset object for the facetoface activity
 * @param  object $userid     Default to current user if null
 * @return boolean whether a person needs a manager to sign up for that activity
 */
function facetoface_activity_can_declare_interest($facetoface, $userid = null) {
    global $DB, $USER;

    // "Declare interest" must be turned on for the activity.
    if (!$facetoface->declareinterest) {
        return false;
    }

    // Check that the user has no existing signup.
    if (is_null($userid)) {
        $userid = $USER->id;
    }

    // If user already declared interest, cannot declare again.
    if (facetoface_user_declared_interest($facetoface, $userid)) {
        return false;
    }

    // If "only when full" is turned on, allow only when all sessions are fully booked.
    if ($facetoface->interestonlyiffull) {
        // If user signed - no declare interest.
        $submission = facetoface_get_user_submissions($facetoface->id, $userid,
                MDL_F2F_STATUS_REQUESTED, MDL_F2F_STATUS_FULLY_ATTENDED);
        if (!empty($submission)) {
            return false;
        }

        $now = time();
        $sql = "
            SELECT DISTINCT fs.id
            FROM {facetoface_sessions} fs
                INNER JOIN {facetoface_sessions_dates} fsd ON (fsd.sessionid = fs.id)
            WHERE fsd.timestart > :now
                AND fs.facetoface = :facetoface
        ";

        $sessions = $DB->get_records_sql($sql, array('now' => $now, 'facetoface' => $facetoface->id));
        foreach ($sessions as $sessionrec) {
            $session = facetoface_get_session($sessionrec->id);

            if (facetoface_can_user_signup($session, $userid, $now)) {
                return false;
            }
        }
    }
    return true;
}

/**
 * Check if user can signup to current event.
 * @param stdClass $session object (must be prepared by see::facetoface_get_session())
 * @param int $userid user id
 * @param int $time time of check (by default now)
 */
function facetoface_can_user_signup($session, $userid, $time = 0) {
    if (!$time) {
        $time = time();
    }
    if (!empty($session->cancelledstatus)) {
        return false;
    }

    if (facetoface_has_session_started($session, $time)) {
        return false;
    }

    $submission = facetoface_get_user_submissions($session->facetoface, $userid,
                        MDL_F2F_STATUS_REQUESTED, MDL_F2F_STATUS_FULLY_ATTENDED, $session->id);
    if (!empty($submission)) {
        return false;
    }

    if (!$cm = get_coursemodule_from_instance('facetoface', $session->facetoface)) {
        print_error('error:incorrectcoursemodule', 'facetoface');
    }
    $contextmodule = context_module::instance($cm->id);

    if (!$session->allowoverbook && !facetoface_session_has_capacity($session, $contextmodule, MDL_F2F_STATUS_BOOKED, $userid)) {
        return false;
    }

    if (!empty($session->registrationtimestart) && $session->registrationtimestart > $time) {
        return false;
    }

    if (!empty($session->registrationtimefinish) && $session->registrationtimefinish < $time) {
        return false;
    }
    return true;
}

/**
 * Check if the user has any signups that don't have any of the following
 *     not being archived
 *     cancelled by user
 *     declined
 *     session cancelled
 *     status not set
 * @param int $facetofaceid
 * @param int $userid
 * @return bool
 */
function facetoface_has_unarchived_signups($facetofaceid, $userid) {
    global $DB;

    $sql  = "SELECT 1 FROM {facetoface_signups} ftf_sign
               JOIN {facetoface_sessions} sess
                    ON sess.facetoface = :facetofaceid
               JOIN {facetoface_signups_status} sign_stat
                    ON sign_stat.signupid = ftf_sign.id
                    AND sign_stat.superceded <> 1
              WHERE ftf_sign.userid = :userid
                AND ftf_sign.sessionid = sess.id
                AND ftf_sign.archived <> 1
                AND sign_stat.statuscode > :statusdeclined
                AND sign_stat.statuscode <> :statusnotset";

    $params = array('facetofaceid' => $facetofaceid,
        'userid' => $userid,
        'statusdeclined' => MDL_F2F_STATUS_DECLINED,
        'statusnotset' => MDL_F2F_STATUS_NOT_SET);

    // Check if user is already signed up to a session in the facetoface and it has not been archived.
    return $DB->record_exists_sql($sql, $params);
}

/**
 * Declares interest in a facetoface activity for a user.
 * Assume we have already checked that no existing decleration exists
 * And all the necessary permissions
 *
 * @param  object $facetoface A database fieldset object for the facetoface activity
 * @param  string $reason     Reason provided by user
 * @param  object $userid     Default to current user if null
 * @return bool|int           Result of the insert
 */
function facetoface_declare_interest($facetoface, $reason = '', $userid = null) {
    global $DB, $USER;

    if (is_null($userid)) {
        $userid = $USER->id;
    }

    $toinsert = (object)array(
        'facetoface' => $facetoface->id,
        'userid' => $userid,
        'timedeclared' => time(),
        'reason' => $reason,
    );

    return $DB->insert_record('facetoface_interest', $toinsert);
}

/**
 * Withdraws interest from a facetoface activity for a user.
 * @param  object $facetoface A database fieldset object for the facetoface activity
 * @param  int    $userid     Default to current user if null
 * @return boolean            Success
 */
function facetoface_withdraw_interest($facetoface, $userid = null) {
    global $DB, $USER;

    if (is_null($userid)) {
        $userid = $USER->id;
    }

    return $DB->delete_records('facetoface_interest', array('facetoface' => $facetoface->id, 'userid' => $userid));
}

/**
 * Called after each config setting update.
 */
function facetoface_displaysessiontimezones_updated() {
    global $DB;

    $sessions = $DB->get_recordset('facetoface_sessions');
    foreach ($sessions as $s) {
        $session = facetoface_get_session($s->id);
        facetoface_update_calendar_entries($session);
    }
    $sessions->close();
}

/**
 * Cancels waitlisted users from an array as booked on a session
 * @param int    $sessionid  ID of the session to use
 * @param array  $userids    Array of user ids to confirm
 */
function facetoface_confirm_attendees($sessionid, $userids) {
    global $DB, $USER;

    $session = facetoface_get_session($sessionid);
    $facetoface = $DB->get_record('facetoface', array('id'=>$session->facetoface));

    foreach ($userids as $userid) {
        $conditions = array('sessionid' => $sessionid, 'userid' => $userid);
        $existingsignup = $DB->get_record('facetoface_signups', $conditions, '*', MUST_EXIST);
        facetoface_update_signup_status($existingsignup->id, MDL_F2F_STATUS_BOOKED, $USER->id);
        facetoface_send_confirmation_notice($facetoface, $session, $userid, $existingsignup->notificationtype, false);
    }
}

/**
 * Cancels waitlisted users from an array on a session
 * @param int    $sessionid  ID of the session to use
 * @param array  $userids    Array of user ids to cancel
 */
function facetoface_cancel_attendees($sessionid, $userids) {
    global $DB, $USER;

    foreach ($userids as $userid) {
        $params = array('sessionid' => $sessionid, 'userid' => $userid);
        $existingsignup = $DB->get_record('facetoface_signups', $params, '*', MUST_EXIST);
        facetoface_update_signup_status($existingsignup->id, MDL_F2F_STATUS_USER_CANCELLED, $USER->id);
    }
}

/**
 * Randomly books waitlisted users on to a session
 * @param int $sessionid  ID of the session to use
 */
function facetoface_waitlist_randomly_confirm_users($sessionid, $userids) {
    $session = facetoface_get_session($sessionid);
    $signupcount = facetoface_get_num_attendees($sessionid);

    $numtoconfirm = $session->capacity - $signupcount;

    if (count($userids) <= $session->capacity) {
        $winners = $userids;
    } else {
        $winners = array_rand(array_flip($userids), $numtoconfirm);

        if ($numtoconfirm == 1) {
            $winners = array($winners);
        }
    }

    facetoface_confirm_attendees($sessionid, $winners);

    return $winners;
}

function facetoface_get_user_current_status($sessionid, $userid) {
    global $DB;

    $sql = "
        SELECT ss.*
          FROM {facetoface_signups} su
          JOIN {facetoface_signups_status} ss ON su.id = ss.signupid
         WHERE su.sessionid = ?
           AND su.userid = ?
           AND ss.superceded = 0";

    return $DB->get_record_sql($sql, array($sessionid, $userid));

}

/**
 * Get a count of the number of spaces reserved by each manager
 * for a given session.
 *
 * @param int $sessionid
 *
 * @return array Array of reservations
 */
function facetoface_get_session_reservations($sessionid) {
    global $DB;

    $userfields =  get_all_user_name_fields(true, 'u');

    $reservation_sql = "SELECT bookedby, COUNT(fs.id) as reservedspaces, sessionid, {$userfields}
        FROM {facetoface_signups} fs
        JOIN {user} u ON fs.bookedby = u.id
        WHERE bookedby != :bookedby
        AND userid = :userid
        AND sessionid = :sessionid
        GROUP BY
        bookedby, sessionid, {$userfields}";

    $reservation_params = array('bookedby' => 0, 'userid' => 0, 'sessionid' => $sessionid);

    $reservations = $DB->get_records_sql($reservation_sql, $reservation_params);

    return $reservations;
}

/**
 * Delete reservations for a given session and manager
 *
 * @param int $sessionid
 * @param int $managerid
 *
 * @return bool True if dng of the reservations succeeded
 */
function facetoface_delete_reservations($sessionid, $managerid) {
    global $DB;

    $signups = $DB->get_records_sql('SELECT id FROM {facetoface_signups} WHERE userid = 0 AND sessionid = :sessionid AND bookedby = :managerid',
        array('sessionid' => $sessionid, 'managerid' => $managerid));

    $transaction = $DB->start_delegated_transaction();
    $result = true;

    if ($signups) {
        list($signupwhere, $signupparams) = $DB->get_in_or_equal(array_keys($signups));
        // Delete signup status records.
        $result = $DB->delete_records_select('facetoface_signups_status', 'signupid ' . $signupwhere
            , $signupparams);
    }

    // Delete signups.
    $result = $result && $DB->delete_records('facetoface_signups',
            array('userid' => 0, 'sessionid' => $sessionid, 'bookedby' => $managerid));

    $transaction->allow_commit();

    return $result;
}

/**
 * Get facetoface session related instances commonly used in the code
 * Will stop code execution and display error if wrong id supplied
 * @param int $sessionid sessionid
 *
 * @return array($session, $facetoface, $course, $cm, $context)
 */
function facetoface_get_env_session($sessionid) {
    global $DB;
    if (!$session = facetoface_get_session($sessionid)) {
        print_error('error:incorrectcoursemodulesession', 'facetoface');
    }
    if (!$facetoface = $DB->get_record('facetoface', array('id' => $session->facetoface))) {
        print_error('error:incorrectfacetofaceid', 'facetoface');
    }
    if (!$course = $DB->get_record('course', array('id' => $facetoface->course))) {
        print_error('error:coursemisconfigured', 'facetoface');
    }
    if (!$cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id)) {
        print_error('error:incorrectcoursemodule', 'facetoface');
    }
    $context = context_module::instance($cm->id);

    return array($session, $facetoface, $course, $cm, $context);
}

/**
 * Output for a removable approver in the facetoface mod_form.
 *
 * @param int       $user           The user object for the approver being displayed
 * @param boolean   $activity       Whether the approver is activity level or site level
 * @return string                   The html output for the approver
 */
function facetoface_display_approver($user, $activity = false) {
    global $PAGE;

    $renderer = $PAGE->get_renderer('mod_facetoface');

    $uniqueid = "facetoface_approver_{$user->id}";
    if ($activity) {
        $classname = 'activity_approver';
        $delete = $renderer->action_icon('', new pix_icon('/t/delete', get_string('remove')), null,
                        array('class' => 'activity_approver_del', 'id' => $user->id));
        $content = get_string('approval_activityapprover', 'mod_facetoface', fullname($user)) . ' ' . $delete;
    } else {
        $classname = 'system_approver';
        $content = get_string('approval_siteapprover', 'mod_facetoface', fullname($user));
    }

    return html_writer::tag('div', $content, array('id' => $uniqueid, 'class' => $classname));
}

function facetoface_validate_user_import($user, $context, $facetoface, $session, $ignoreconflicts = null) {
    global $USER;


    if ($user->deleted) {
        $result['name'] = fullname($user);
        $result['result'] = get_string('error:userdeleted', 'facetoface', fullname($user));
        return $result;
    }

    if ($user->suspended) {
        $result['name'] = fullname($user);
        $result['result'] = get_string('error:usersuspended', 'facetoface', fullname($user));
        return $result;
    }

    $result = array(
        'idnumber' => $user->idnumber,
        'name' =>fullname($user)
    );

    // Check if they are already signed up
    $minimumstatus = ($session->mintimestart) ? MDL_F2F_STATUS_BOOKED : MDL_F2F_STATUS_REQUESTED;
    // If multiple sessions are allowed then just check against this session
    // Otherwise check against all sessions
    $multisessionid = ($facetoface->multiplesessions ? $session->id : null);
    if (facetoface_get_user_submissions($facetoface->id, $user->id, $minimumstatus, MDL_F2F_STATUS_FULLY_ATTENDED, $multisessionid)
        && empty($facetoface->multiplesessions) && facetoface_has_unarchived_signups($facetoface->id, $user->id)) {
        if ($user->id == $USER->id) {
            $result['result'] = get_string('error:addalreadysignedupattendeeaddself', 'facetoface');
        } else {
            $result['result'] = get_string('error:addalreadysignedupattendee', 'facetoface');
        }
        return $result;
    }

    $facetoface_allowwaitlisteveryone = get_config(null, 'facetoface_allowwaitlisteveryone');
    if (empty($session->waitlisteveryone) && empty($facetoface_allowwaitlisteveryone)
        && !facetoface_session_has_capacity($session, $context) && empty($session->allowoverbook)) {
            $result['result'] = get_string('full', 'facetoface');
            return $result;
    }

    // Check if there are any date conflicts
    if (empty($ignoreconflicts)) {
        $dates = facetoface_get_session_dates($session->id);
        if ($availability = facetoface_get_sessions_within($dates, $user->id)) {
            $result['result'] = facetoface_get_session_involvement($user, $availability);
            $result['conflict'] = true;
            return $result;
        }
    }

    // No errors, so just return an empty array.
    return array();
}

/**
 * Returns detailed information about booking conflicts for the passed users
 *
 * @param array $dates Array of dates defining time periods
 * @param array $users Array of user objects that will be checked for booking conflicts
 * @param string $extrawhere SQL fragment to be added to the where clause in facetoface_get_sessions_within
 * @param array $extraparams Paramaters used by the $extrawhere To be used in facetoface_get_sessions_within
 * @return array The booking conflicts.
 */
function facetoface_get_booking_conflicts(array $dates, array $users, string $extrawhere, array $extraparams) {
    $bookingconflicts = array();
    foreach ($users as $user) {
        if ($availability = facetoface_get_sessions_within($dates, $user->id, $extrawhere, $extraparams)) {
            $bookingconflicts[] = array(
                'idnumber' => $user->idnumber,
                'name' => fullname($user),
                'result' => facetoface_get_session_involvement($user, $availability),
            );
        }
    }
    return $bookingconflicts;
}

/**
 * Returns a users name for selection in a seminar.
 *
 * This function allows for viewing user identity information as configured for the site.
 *
 * Taken from \user_selector_base::output_user
 * At some point this needs to be converted to a proper user selector.
 *
 * @param stdClass $user
 * @param array|null $extrafields Extra fields to display next to the users name, if null the user identity fields are used.
 * @param bool $fullnameoverride Passed through to the fullname function as the override arg.
 * @return string
 */
function facetoface_output_user_for_selection(stdClass $user, array $extrafields = null, $fullnameoverride = false) {
    global $CFG, $PAGE;

    $out = fullname($user, $fullnameoverride);
    if ($extrafields === null) {
        $extrafields = [];
        if (!empty($CFG->showuseridentity) && has_capability('moodle/site:viewuseridentity', $PAGE->context)) {
            $extrafields = explode(',', $CFG->showuseridentity);
        }
    }
    if ($extrafields) {
        $displayfields = array();
        foreach ($extrafields as $field) {
            if (!empty($user->{$field})) {
                $displayfields[] = $user->{$field};
            }
        }
        // This little bit of hardcoding is pretty bad, but its consistent with how Seminar was working and as this
        // change was made right before release we wanted to keep it consistent.
        if (!empty($displayfields)) {
            $out .= ', ' . implode(', ', $displayfields);
        }
    }
    return $out;
}
