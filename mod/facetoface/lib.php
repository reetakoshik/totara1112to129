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

use mod_facetoface\room;

defined('MOODLE_INTERNAL') || die();

use mod_facetoface\seminar;
use mod_facetoface\signup;
use mod_facetoface\signup_helper;
use mod_facetoface\seminar_event;
use mod_facetoface\notice_sender;

require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->dirroot.'/grade/lib.php');
require_once($CFG->dirroot.'/lib/adminlib.php');
require_once($CFG->dirroot . '/user/selector/lib.php');
require_once $CFG->dirroot.'/mod/facetoface/messaginglib.php';
require_once $CFG->dirroot.'/mod/facetoface/notification/lib.php';
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->dirroot . '/mod/facetoface/room/lib.php');
require_once($CFG->dirroot . '/mod/facetoface/asset/lib.php');
require_once($CFG->dirroot . '/mod/facetoface/libdeprecated.php');

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

define('F2F_CAL_NONE',      0);
define('F2F_CAL_COURSE',    1);
define('F2F_CAL_SITE',      2);

// Define bulk attendance options
define('MDL_F2F_SELECT_ALL', 10);
define('MDL_F2F_SELECT_NONE', 20);
define('MDL_F2F_SELECT_SET', 30);
define('MDL_F2F_SELECT_NOT_SET', 40);

// Define events displayed on course page settings
define('MDL_F2F_MAX_EVENTS_ON_COURSE', 18);
define('MDL_F2F_DEFAULT_EVENTS_ON_COURSE', 6);

global $F2F_SELECT_OPTIONS;
$F2F_SELECT_OPTIONS = array(
    MDL_F2F_SELECT_NONE    => get_string('selectnoneop', 'facetoface'),
    MDL_F2F_SELECT_ALL     => get_string('selectallop', 'facetoface'),
    MDL_F2F_SELECT_SET     => get_string('selectsetop', 'facetoface'),
    MDL_F2F_SELECT_NOT_SET => get_string('selectnotsetop', 'facetoface')
);

// Define custom field array for reserved shortnames.
global $F2F_CUSTOMFIELD_RESERVED;
$F2F_CUSTOMFIELD_RESERVED = [
    'facetofaceroom' => ['text' => CUSTOMFIELD_BUILDING, 'location' => CUSTOMFIELD_LOCATION],
    'facetofacesignup' => ['text' => CUSTOMFIELD_SIGNUPNOTE],
    'facetofacecancellation' => ['text' => CUSTOMFIELD_CANCELNOTE]
];

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
 * Given an object containing all the necessary data, (defined by the
 * form in mod.html) this function will create a new instance and
 * return the id number of the new instance.
 */
function facetoface_add_instance($facetoface) {
    global $DB;

    $facetoface->timemodified = time();

    if ($facetoface->id = $DB->insert_record('facetoface', $facetoface)) {
        facetoface_grade_item_update($facetoface);
    }

    //update any calendar entries
    $seminar = new \mod_facetoface\seminar($facetoface->id);
    $seminarevents = \mod_facetoface\seminar_event_list::from_seminar($seminar);
    foreach ($seminarevents as $seminarevent) {
        \mod_facetoface\calendar::update_entries($seminarevent);
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
 * @param stdClass $facetoface
 * @param mod_facetoface_mod_form $mform
 */
function facetoface_update_instance($facetoface, $mform = null) {
    global $DB;

    $facetoface->id = $facetoface->instance;
    $previousapproval = $DB->get_field('facetoface', 'approvaltype', array('id' => $facetoface->id));

    if (!$DB->update_record('facetoface', $facetoface)) {
        return false;
    }

    facetoface_grade_item_update($facetoface);

        //Get time.
        $now = time();


    $seminar = new seminar($facetoface->id);

    foreach ($seminar->get_events() as $seminarevent) {
        /**
         * @var seminar_event $seminarevent
         */
        \mod_facetoface\calendar::update_entries($seminarevent);

        // If manager changed from approval required to not
        if ($facetoface->approvaltype != $previousapproval) {
            $status = [signup\state\requested::get_code(), signup\state\requestedadmin::get_code()];
            $pending = facetoface_get_attendees($seminarevent->get_id(), $status);
            core_collator::asort_objects_by_property($pending, 'timecreated', core_collator::SORT_NUMERIC);

            foreach ($pending as $attendee) {
                $signup = new signup($attendee->submissionid);
                $signup->set_actorid($signup->get_userid());
                $state = $signup->get_state();
                if ($state->can_switch(signup\state\booked::class, signup\state\waitlisted::class)) {
                    $signup->switch_state(signup\state\booked::class, signup\state\waitlisted::class);
                } else if (!$seminarevent->is_started()) {
                    // Requested state for "Manager approval" and "Role approval" will not change state,
                    // however it needs messages to be resent:
                    if ($facetoface->approvaltype == seminar::APPROVAL_MANAGER) {
                        notice_sender::request_manager($signup);
                    } else if ($facetoface->approvaltype == seminar::APPROVAL_ROLE) {
                        notice_sender::request_role($signup);
                    }
                }
            }
        }
    }
    return true;
}

/**
 * Given an ID of an instance of this module, this function will
 * permanently delete the instance and any data that depends on it.
 */
function facetoface_delete_instance($id) {
    global $DB;

    $seminar = new \mod_facetoface\seminar($id);
    if (!$seminar->exists()) {
        return false;
    }

    $result = true;
    $transaction = $DB->start_delegated_transaction();

    $seminar->delete();

    $transaction->allow_commit();
    return $result;
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
        ", array($session->id, \mod_facetoface\signup\state\requested::get_code()));

    $seminarevent = new seminar_event($session->id);

    if ($signedupusers and count($signedupusers) > 0) {
        foreach ($signedupusers as $user) {
            $signup = signup::create($user->userid, $seminarevent);
            if (signup_helper::can_user_cancel($signup)) {
                signup_helper::user_cancel($signup);
                if (!$sessionover) {
                    notice_sender::event_cancellation($user->userid, $seminarevent);
                }
            }
        }
    }

    // Send cancellations for trainers assigned to the session.
    $trainers = $DB->get_records("facetoface_session_roles", array("sessionid" => $session->id));
    if ($trainers and count($trainers) > 0) {
        foreach ($trainers as $trainer) {
            if (!$sessionover) {
                notice_sender::event_cancellation($trainer->userid, $seminarevent);
            }
        }
    }

    // Notify managers who had reservations.
    if (!$sessionover) {
        facetoface_notify_reserved_session_deleted($facetoface, $session);
    }

    $transaction = $DB->start_delegated_transaction();

    // Remove entries from the teacher calendars.
    // Deleting records before refactoring attendees/view.php page
    $select = $DB->sql_like('description', ':attendess');
    $select .= " AND modulename = 'facetoface' AND eventtype = 'facetofacesession' AND instance = :facetofaceid";
    $params = array('attendess' => "%attendees.php?s={$session->id}%", 'facetofaceid' => $facetoface->id);
    $DB->delete_records_select('event', $select, $params);
    // Remove entries from the teacher calendars.
    // Deleting records after refactoring attendees/view.php page
    $select = $DB->sql_like('description', ':attendess');
    $select .= " AND modulename = 'facetoface' AND eventtype = 'facetofacesession' AND instance = :facetofaceid";
    $params = array('attendess' => "%view.php?s={$session->id}%", 'facetofaceid' => $facetoface->id);
    $DB->delete_records_select('event', $select, $params);

    $seminarevent = new \mod_facetoface\seminar_event($session->id);
    if ($facetoface->showoncalendar == F2F_CAL_COURSE) {
        // Remove entry from course calendar
        \mod_facetoface\calendar::remove_seminar_event($seminarevent, $facetoface->course);
    } else if ($facetoface->showoncalendar == F2F_CAL_SITE) {
        // Remove entry from site-wide calendar
        \mod_facetoface\calendar::remove_seminar_event($seminarevent, SITEID);
    }

    // Delete links to assets and delete freshly orphaned custom assets because there is little chance they would be reused.
    $dateids = $DB->get_fieldset_select('facetoface_sessions_dates', 'id', "sessionid = :sessionid", array('sessionid' => $session->id));
    foreach ($dateids as $dateid) {
        $sql = "SELECT fa.id
                  FROM {facetoface_asset} fa
                  JOIN {facetoface_asset_dates} fad ON (fad.assetid = fa.id)
                 WHERE fa.custom = 1 AND sessionsdateid = :sessionsdateid";
        $customassetids = $DB->get_fieldset_sql($sql, array('sessionsdateid' => $dateid));
        $DB->delete_records('facetoface_asset_dates', array('sessionsdateid' => $dateid));
        foreach ($customassetids as $assetid) {
            if (!$DB->record_exists('facetoface_asset_dates', array('assetid' => $assetid))) {
                $asset = new \mod_facetoface\asset($assetid);
                $asset->delete();
            }
        }
    }

    // Delete links to rooms and delete freshly orphaned custom rooms because there is little chance they would be reused.
    $sql = "SELECT DISTINCT fr.id
              FROM {facetoface_room} fr
              JOIN {facetoface_sessions_dates} fsd ON (fsd.roomid = fr.id)
             WHERE fr.custom = 1 AND sessionid = :sessionid";
    $customroomids = $DB->get_fieldset_sql($sql, array('sessionid' => $session->id));
    $DB->set_field('facetoface_sessions_dates', 'roomid', 0, array('sessionid' => $session->id));
    foreach ($customroomids as $roomid) {
        if (!$DB->record_exists('facetoface_sessions_dates', array('roomid' => $roomid))) {
            $room = new room($roomid);
            $room->delete();
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
    $signups = \mod_facetoface\signup_list::from_conditions(['sessionid' => (int)$session->id]);
    $signups->delete();

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
 * Notify managers that a session they had reserved spaces on has been deleted.
 *
 * @param object $facetoface
 * @param object $session
 */
function facetoface_notify_reserved_session_deleted($facetoface, $session) {
    global $CFG;

    $attendees = facetoface_get_attendees($session->id, array(\mod_facetoface\signup\state\booked::get_code()), true);
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
 * Determine if user can or not cancel his/her booking.
 *
 * @param stdClass $session Session object like facetoface_get_sessions.
 * @return bool True if cancellation is allowed, false otherwise.
 */
function facetoface_allow_user_cancellation($session) {
    $timenow = time();

    // If cancellations are not allowed, nothing else to check here.
    if ($session->allowcancellations == \mod_facetoface\seminar_event::ALLOW_CANCELLATION_NEVER) {
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
    $attendancecode = array(\mod_facetoface\signup\state\no_show::get_code(), \mod_facetoface\signup\state\partially_attended::get_code(), \mod_facetoface\signup\state\fully_attended::get_code());
    if ($session->bookedsession && in_array($session->bookedsession->statuscode, $attendancecode)) {
        return false;
    }

    // If the user has been booked but he is in the waitlist, then he can cancel at any time.
    if ($session->bookedsession && $session->bookedsession->statuscode == \mod_facetoface\signup\state\waitlisted::get_code()) {
        return true;
    }

    // If cancellations are allowed at any time or until cut-off is reached, make the necessary checks.
    if ($session->allowcancellations == \mod_facetoface\seminar_event::ALLOW_CANCELLATION_ANY_TIME) {
        return true;
    } else if ($session->allowcancellations == \mod_facetoface\seminar_event::ALLOW_CANCELLATION_CUT_OFF) {
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
        'now1'      => $time,
        'now2'      => $time
    );

    // Only load events that are in the future.
    $sql = "SELECT s.*, minstart FROM {facetoface_sessions} s
            INNER JOIN (
                SELECT s.id as sessid, MIN(timestart) AS minstart
                FROM {facetoface_sessions} s
                INNER JOIN {facetoface_sessions_dates} d ON s.id = d.sessionid
                WHERE timestart >= :now1
                GROUP BY s.id
            ) dates ON dates.sessid = s.id
            WHERE mincapacity > 0 
              AND (minstart - cutoff) < :now2 
              AND (minstart - cutoff) >= :lastcron 
              AND s.cancelledstatus = 0";

    $tocheck = $DB->get_recordset_sql($sql, $params);

    foreach ($tocheck as $session) {
        $notification = new \facetoface_notification((array)$session, false);
        $notification->send_notification_session_under_capacity($session);
    }
    $tocheck->close();
}

/**
 * Cancel all pending requests for a given session.
 * Primarily for use with the close_registrations task
 *
 * @param stdClass $session - A database record from facetoface_session
 */
function facetoface_cancel_pending_requests($session) {
    global $DB;

    // Find any pending requests for the given session.
    $requestsql = "SELECT fss.*, fs.userid as recipient
                     FROM {facetoface_signups} fs
               INNER JOIN {facetoface_signups_status} fss
                       ON fss.signupid = fs.id AND fss.superceded = 0
                    WHERE fs.sessionid = :sess
                      AND (statuscode = :req OR statuscode = :adreq)";
    $requestparams = array('req' => \mod_facetoface\signup\state\requested::get_code(), 'adreq' => \mod_facetoface\signup\state\requestedadmin::get_code());

    $f2fs = array();

    $requestparams['sess'] = $session->id;
    $pendingrequests = $DB->get_records_sql($requestsql, $requestparams);

    // Loop through all the pending requests, cancel them, and send a notification to the user.
    if (!empty($pendingrequests)) {
        if (!isset($f2fs[$session->facetoface])) {
            $f2fs[$session->facetoface] = $DB->get_record('facetoface', array('id' => $session->facetoface), '*', MUST_EXIST);
        }

        $errors = [];
        foreach ($pendingrequests as $pending) {
            // Mark the request as declined so they can no longer be approved.
            $signup = new signup($pending->signupid);
            if ($signup->can_switch(\mod_facetoface\signup\state\declined::class)) {
                $signup->switch_state(\mod_facetoface\signup\state\declined::class);
            } else {
                $failures = $signup->get_failures( \mod_facetoface\signup\state\declined::class);
                $errors[$pending->recipient] = current($failures);
            }
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
        'now1'      => $time,
        'now2'      => $time
    );

    // Only load events that are in the future.
    $sql = "SELECT s.*, minstart
            FROM {facetoface_sessions} s
                INNER JOIN (
                    SELECT s.id AS sessid, MIN(timestart) AS minstart
                    FROM {facetoface_sessions} s
                    INNER JOIN {facetoface_sessions_dates} d ON s.id = d.sessionid
                    WHERE timestart >= :now1
                    GROUP BY s.id
                ) dates ON dates.sessid = s.id
            WHERE registrationtimefinish < :now2
            AND registrationtimefinish >= :lastcron
            AND registrationtimefinish != 0";

    $tocheck = $DB->get_recordset_sql($sql, $params);

    foreach ($tocheck as $session) {
        $notification = new \facetoface_notification((array)$session, false);
        $notification->send_notification_registration_expired($session);
    }
    $tocheck->close();
}

/**
 * Returns true if the session has started, that is if one of the
 * session dates is in the past.
 *
 * This function is going to be deprecated. Use seminar_event::is_started() instead
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
 * @deprcated since Totara 12.0
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
 * @param string $unsed previously location filter (optional). @deprecated 9.0 No longer used by internal code.
 * @param integer $roomid Room id filter (optional).
 */
function facetoface_get_sessions($facetofaceid, $unused = null, $roomid = 0) {
    global $DB;

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
                WHERE (1=1)
                GROUP BY fsd.sessionid
            ) m ON m.sessionid = s.id
            WHERE s.facetoface = :facetoface
            $roomwhere
            ORDER BY m.mintimestart, m.maxtimefinish",
            array_merge(array('facetoface' => $facetofaceid), $roomparams));

    if ($sessions) {
        foreach ($sessions as $key => $value) {
            $sessions[$key]->sessiondates = facetoface_get_session_dates($value->id);
        }
    }
    return $sessions;
}

/**
 * Get all records from facetoface_sessions for a given facetoface activity limited by timestart
 *
 * @param integer $facetofaceid ID of the activity
 * @param integer $roomid Room id filter (optional).
 */
function facetoface_get_sessions_where_timestart($facetofaceid, $roomid = 0) {
    global $DB;

    $allpreviousevents = optional_param('allpreviousevents', false, PARAM_BOOL);

    $timeperiodwhere = '';
    $timeperiodparam = array();
    $timeperiod = (int)get_config(null, 'facetoface_previouseventstimeperiod');
    if (!$allpreviousevents && $timeperiod > 0) {
        $timefrom = time() - ($timeperiod * DAYSECS);
        $timeperiodwhere = ' AND (m.mintimestart > :timefrom OR m.mintimestart IS NULL) ';
        $timeperiodparam = array('timefrom' => $timefrom);
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
    $params = array_merge($timeperiodparam, $roomparams);
    $params = array_merge(array('facetoface' => $facetofaceid), $params);
    $sessions = $DB->get_records_sql(
        "SELECT s.*, m.mintimestart, m.maxtimefinish, m.cntdates
            FROM {facetoface_sessions} s
            LEFT JOIN (
                SELECT fsd.sessionid, COUNT(fsd.id) AS cntdates, MIN(fsd.timestart) AS mintimestart, MAX(fsd.timefinish) AS maxtimefinish
                FROM {facetoface_sessions_dates} fsd
                WHERE (1=1)
                GROUP BY fsd.sessionid
            ) m ON m.sessionid = s.id
            WHERE s.facetoface = :facetoface
            $timeperiodwhere
            $roomwhere
            ORDER BY m.mintimestart, m.maxtimefinish",
        $params);

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
function facetoface_get_attendees($sessionid, $status = [], $includereserved = false) {
    global $DB;

    if (empty($status)) {
        $status = [\mod_facetoface\signup\state\booked::get_code(), \mod_facetoface\signup\state\waitlisted::get_code()];
    }

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

    $params = array_merge(array(\mod_facetoface\signup\state\booked::get_code(), \mod_facetoface\signup\state\waitlisted::get_code(), $sessionid), $statusparams);

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
        SELECT su.id AS submissionid, s.id AS sessionid, u.*, f.course AS courseid, f.selectjobassignmentonsignup,
            ss.grade, sign.timecreated, su.jobassignmentid
        FROM {facetoface} f
        JOIN {facetoface_sessions} s ON s.facetoface = f.id
        JOIN {facetoface_signups} su ON s.id = su.sessionid
        JOIN {facetoface_signups_status} ss ON su.id = ss.signupid
        JOIN {user} u ON u.id = su.userid AND u.deleted = 0
        LEFT JOIN (
            SELECT ss.signupid, MAX(ss.timecreated) AS timecreated
            FROM {facetoface_signups_status} ss
            INNER JOIN {facetoface_signups} s ON s.id = ss.signupid
            INNER JOIN {facetoface_sessions} se ON s.sessionid = se.id AND se.facetoface = $facetofaceid
            WHERE ss.statuscode IN (:booked,:waitlisted)
            GROUP BY ss.signupid
        ) sign ON su.id = sign.signupid
        WHERE f.id = :fid AND ss.superceded != 1 AND ss.statuscode >= :waitlisted2
        ORDER BY s.id, u.firstname, u.lastname";
    $signupparams =  array(
        'booked' => \mod_facetoface\signup\state\booked::get_code(),
        'waitlisted' => \mod_facetoface\signup\state\waitlisted::get_code(),
        'fid' => $facetofaceid,
        'waitlisted2' => \mod_facetoface\signup\state\waitlisted::get_code()
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
        if (null == $session->roomid) {
            $session->roomid = 0;
        }

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

        $room = new \mod_facetoface\room($session->roomid);
        $roomstring = '';
        if ($room->exists()) {
            $roomstring = (string)$room;
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
    $seminar = new \mod_facetoface\seminar($coursemodule->instance);

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

    $interest = \mod_facetoface\interest::from_seminar($seminar);
    $alreadydeclaredinterest = $interest->is_user_declared();
    $declareinterest_enable = $alreadydeclaredinterest || $interest->can_user_declare();
    $declareinterest_label = $alreadydeclaredinterest ? get_string('declareinterestwithdraw', 'facetoface') : get_string('declareinterest', 'facetoface');
    $declareinterest_url = new moodle_url('/mod/facetoface/interest.php', array('f' => $facetoface->id));
    $declareinterest_link = html_writer::link($declareinterest_url, $declareinterest_label, array('class' => 'f2fsessionlinks f2fviewallsessions', 'title' => $declareinterest_label));

    // User has signedup for the instance.
    if ($seminar->has_unarchived_signups()) {
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

            // If state restrictions are enabled and not met, only display the current signup.
            $checkremaining = true;
            $restrictions = $seminar->get_multisignup_states();
            if (!empty($restrictions)) {
                foreach ($submissions as $signupdata) {
                    $signup = new signup($signupdata->id);
                    $state = $signup->get_state();
                    $code = $state::get_code();
                    if (empty($restrictions[$code])) {
                        // We have a sign-up who's current state is not matching restrictions.
                        // Display that sign-up and nothing else.
                        // $sessions[$session->id] = $session; // This should already be there (see above) just skip the next bit.
                        $checkremaining = false;
                    }
                }
            }

            $maximum = $seminar->get_multisignup_maximum();
            if ($checkremaining && (empty($maximum) || count($submissions) < $maximum)) {
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
        }

        if (!empty($facetoface->managerreserve)) {
            // Include information about reservations when drawing the list of sessions.
            $reserveinfo = \mod_facetoface\reservations::can_reserve_or_allocate($seminar, $sessions, $contextmodule);
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
                    unset($sessions[$id]);
                }
            }

            // Limit number of sessions display. $sessions is in order of start time.
            $displaysessions = array_slice($sessions, 0, $facetoface->display, true);

            if (!empty($facetoface->managerreserve)) {
                // Include information about reservations when drawing the list of sessions.
                $reserveinfo = \mod_facetoface\reservations::can_reserve_or_allocate($seminar, $displaysessions, $contextmodule);
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

    return $DB->record_exists_sql($sql, array($session->id, $userid, \mod_facetoface\signup\state\waitlisted::get_code()));
}

/**
 * Update grades by firing grade_updated event
 *
 * @param object $facetoface null means all facetoface activities
 * @param int $userid specific user only, 0 mean all (not used here)
 * @param bool $defaultifnone If a single user is specified and $defaultifnone is true, a grade item with a default rawgrade will be inserted
 *                            the default rawgrade will be recalculated based on $facetoface->eventgradingmethod.
 */
function facetoface_update_grades($facetoface=null, $userid=0, $defaultifnone = true) {
    global $DB;

    if (($facetoface != null) && $userid && $defaultifnone) {
        $grade = new stdClass();
        $grade->userid   = $userid;
        $grade->rawgrade = \mod_facetoface\signup_helper::compute_final_grade($facetoface, $userid);
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
 * Return number of attendees signed up to a facetoface session
 *
 * @param integer $session_id
 * @param integer $status (optional), default is '70' (booked)
 * @param string $comp SQL comparison operator.
 *
 * @return integer
 */
function facetoface_get_num_attendees($session_id, $status = null, $comp = '>=') {
    global $DB;

    if (is_null($status)) {
        $status = \mod_facetoface\signup\state\booked::get_code();
    }

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
 * @param integer $minimumstatus Minimum status level to return, default is '40' (requested)
 * @param integer $maximumstatus Maximum status level to return, default is '100' (fully_attended)
 * @param integer $sessionid Session id
 * @return array submissions | false No submissions
 */
function facetoface_get_user_submissions($facetofaceid, $userid, $minimumstatus = null, $maximumstatus = null, $sessionid = null) {
    global $DB;

    if (is_null($minimumstatus)) {
        $minimumstatus = \mod_facetoface\signup\state\requested::get_code();
    }
    if (is_null($maximumstatus)) {
        $maximumstatus = \mod_facetoface\signup\state\fully_attended::get_code();
    }

    $whereclause = "s.facetoface = ? AND su.userid = ? AND ss.superceded != 1
            AND ss.statuscode >= ? AND ss.statuscode <= ? AND s.cancelledstatus != 1";
    $whereparams = array($facetofaceid, $userid, $minimumstatus, $maximumstatus);

    if (!empty($sessionid)) {
        $whereclause .= " AND s.id = ? ";
        $whereparams[] = $sessionid;
    }

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

    if ($submissions = facetoface_get_user_submissions($facetoface->id, $user->id, \mod_facetoface\signup\state\user_cancelled::get_code(), \mod_facetoface\signup\state\fully_attended::get_code())) {
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
 * Confirm that a session has free space for a user
 *
 * @param class  $session Record from the facetoface_sessions table
 * @param object $context (optional) A context object (record from context table)
 * @param int    $status (optional), default is '70' (booked)
 * @param int    $userid (optional)
 * @return bool True if user can be added to session
 **/
function facetoface_session_has_capacity($session, $context = false, $status = null, $userid = 0) {
    global $USER;
    if (empty($session)) {
        return false;
    }
    if (is_null($status)) {
        $status = \mod_facetoface\signup\state\booked::get_code();
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
 * Return the approval type of a facetoface as a human readable string
 *
 * @param int approvaltype  The $facetoface->approvaltype value
 * @param int approvalrole  The $facetoface->approvalrole value, only required for role approval
 */
function facetoface_get_approvaltype_string($approvaltype, $approvalrole = null) {
    switch ($approvaltype) {
        case \mod_facetoface\seminar::APPROVAL_NONE:
            return get_string('approval_none', 'mod_facetoface');
        case \mod_facetoface\seminar::APPROVAL_SELF:
            return get_string('approval_self', 'mod_facetoface');
        case \mod_facetoface\seminar::APPROVAL_ROLE:
            $rolenames = role_fix_names(get_all_roles());
            return $rolenames[$approvalrole]->localname;
        case \mod_facetoface\seminar::APPROVAL_MANAGER:
            return get_string('approval_manager', 'mod_facetoface');
        case \mod_facetoface\seminar::APPROVAL_ADMIN:
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
    $rooms = \mod_facetoface\room_list::get_event_rooms($sessionid);
    $out['room'] = array();
    foreach ($rooms as $room) {
        /**
         * @var \mod_facetoface\room $room
         */
        $out['room'] = array_merge_recursive($out['room'], customfield_get_data($room->to_record(), 'facetoface_room', 'facetofaceroom', false));
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

    $seminarevent = new seminar_event($session->id);

    // Send a confirmation notice to new trainer
    foreach ($new_trainers as $i => $trainer) {
        \mod_facetoface\notice_sender::trainer_confirmation($trainer->userid, $seminarevent);
    }

    // Send an unassignment notice to old trainer
    foreach ($old_trainers as $i => $trainer) {
        \mod_facetoface\notice_sender::event_trainer_unassigned($trainer->id, $seminarevent);
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
 * Get session cancellations
 *
 * @access  public
 * @param   integer $sessionid
 * @return  array
 */
function facetoface_get_cancellations($sessionid) {
    global $CFG, $DB;

    $usernamefields = get_all_user_name_fields(true, 'u');

    $cancelledstatus = array(\mod_facetoface\signup\state\user_cancelled::get_code(), \mod_facetoface\signup\state\event_cancelled::get_code());
    list($cancelledinsql, $cancelledinparams) = $DB->get_in_or_equal($cancelledstatus);

    $instatus = array(\mod_facetoface\signup\state\booked::get_code(), \mod_facetoface\signup\state\waitlisted::get_code(), \mod_facetoface\signup\state\requested::get_code());
    list($insql, $inparams) = $DB->get_in_or_equal($instatus);
    // Nasty SQL follows:
    // Load currently cancelled users,
    // include most recent booked/waitlisted time also
    $sql = "
            SELECT
                u.id,
                u.deleted,
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
                u.deleted,
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

    return facetoface_get_users_by_status($sessionid, \mod_facetoface\signup\state\requested::get_code(), $select);
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

    $status = array(\mod_facetoface\signup\state\requested::get_code(), \mod_facetoface\signup\state\requestedadmin::get_code());
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
        $params[]  = \mod_facetoface\signup\state\waitlisted::get_code();
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
 * Build user roles in conflict message, used when saving an event.
 *
 * @param stdClass[] $users_in_conflict Array of users in conflict.
 * @return string Message
 */
function facetoface_build_user_roles_in_conflict_message($users_in_conflict) {
    if (empty($users_in_conflict)) {
        return '';
    }

    foreach ($users_in_conflict as $user) {
        if (property_exists($user, "name")) {
            // Indicating that the $user was already had the attribute 'name' built.
            $users[] = $user->name;
            continue;
        }
        $users[] = fullname($user);
    }
    $details = new stdClass();
    $details->users = implode('; ', $users);
    $details->userscount = count($users_in_conflict);

    return format_text(get_string('userschedulingconflictdetected_body', 'facetoface', $details));
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
 * @deprecated since Totara 12.0

 * If the unassigned user belongs to a course with an upcoming
 * face-to-face session and they are signed-up to attend, cancel
 * the sign-up (and trigger notification).
 */
function facetoface_eventhandler_role_unassigned($ra) {
    global $CFG, $USER, $DB;

    debugging('facetoface_eventhandler_role_unassigned() function has been deprecated as unused', DEBUG_DEVELOPER);

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
                        $seminarevent = new seminar_event($session->id);

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
                        $signup = signup::create($ra->userid, $seminarevent);
                        if (signup_helper::can_user_cancel($signup)) {
                            signup_helper::user_cancel($signup);
                            notice_sender::signup_cancellation(signup::create($ra->userid, $seminarevent));
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
        $facetofacenode->add(get_string('declareinterestreport', 'facetoface'), new moodle_url('/mod/facetoface/reports/interests.php', array('facetofaceid' => $PAGE->cm->instance)), navigation_node::TYPE_SETTING);
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

    $fieldsall = \mod_facetoface\calendar::get_customfield_filters();

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

    $seminarevents = \mod_facetoface\seminar_event_list::get_all();
    foreach ($seminarevents as $seminarevent) {
        \mod_facetoface\calendar::update_entries($seminarevent);
    }
}

/**
 * Confirms waitlisted users from an array as booked on a session
 * @param int    $sessionid  ID of the session to use
 * @param array  $userids    Array of user ids to confirm
 * @return string[] failures or empty array
 */
function facetoface_confirm_attendees($sessionid, $userids) {
    global $DB;

    $errors = [];
    $seminarevent = new \mod_facetoface\seminar_event($sessionid);
    foreach ($userids as $userid) {
        $signup = signup::create($userid, $seminarevent);
        if ($signup->get_state() instanceof \mod_facetoface\signup\state\not_set) {
            continue;
        }

        if ($signup->can_switch(\mod_facetoface\signup\state\booked::class)) {
            $signup->switch_state(\mod_facetoface\signup\state\booked::class);
            $conditions = array('sessionid' => $sessionid, 'userid' => $userid);
            $existingsignup = $DB->get_record('facetoface_signups', $conditions, '*', MUST_EXIST);
            notice_sender::confirm_booking(new signup($existingsignup->id), $existingsignup->notificationtype);
        } else {
            $failures = $signup->get_failures(\mod_facetoface\signup\state\booked::class);
            if (!empty($failures)) {
                $errors[$signup->get_userid()] = current($failures);
            }
        }
    }
    return $errors;
}

/**
 * Cancels waitlisted users from an array on a session
 * @param int    $sessionid  ID of the session to use
 * @param array  $userids    Array of user ids to cancel
 */
function facetoface_cancel_attendees($sessionid, $userids) {

    $seminarevent = new \mod_facetoface\seminar_event($sessionid);
    foreach ($userids as $userid) {
        $signup = signup::create($userid, $seminarevent);
        if ($signup->get_state() instanceof \mod_facetoface\signup\state\not_set) {
            continue;
        }
        if ($signup->can_switch(\mod_facetoface\signup\state\user_cancelled::class)) {
            $signup->switch_state(\mod_facetoface\signup\state\user_cancelled::class);
        }
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
 * Returns detailed information about booking conflicts for the passed users
 *
 * @param array $dates Array of dates defining time periods
 * @param array $users Array of user objects that will be checked for booking conflicts
 * @param string $extrawhere SQL fragment to be added to the where clause in facetoface_get_sessions_within
 * @param array $extraparams Paramaters used by the $extrawhere To be used in facetoface_get_sessions_within
 * @param bool $objreturn Pass this as true if u want an object to be returned
 * @return array The booking conflicts.
 */
function facetoface_get_booking_conflicts(array $dates, array $users, string $extrawhere,
                                          array $extraparams, bool $objreturn = false) {
    $bookingconflicts = array();
    foreach ($users as $user) {
        if ($availability = facetoface_get_sessions_within($dates, $user->id, $extrawhere, $extraparams)) {
            $data = array(
                'idnumber' => $user->idnumber,
                'name' => fullname($user),
                'result' => facetoface_get_session_involvement($user, $availability),
            );

            if ($objreturn) {
                $data = (object) $data;
            }

            $bookingconflicts[] = $data;
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
                if ($field == 'idnumber') {
                    $displayfields[] = s($user->{$field});
                } else {
                    $displayfields[] = $user->{$field};
                }
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
