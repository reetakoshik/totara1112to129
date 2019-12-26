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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package mod_facetoface
 */

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');
require_once($CFG->dirroot.'/totara/core/searchlib.php');
require_once($CFG->dirroot.'/totara/core/utils.php');

use mod_facetoface\seminar_event;

define('MAX_USERS_PER_PAGE', 1000);

$s              = required_param('s', PARAM_INT); // Facetoface session ID.
$searchtext     = optional_param('searchtext', '', PARAM_USERNAME);
$clearsearch    = optional_param('clearsearch', 0, PARAM_BOOL);
$next           = optional_param('next', false, PARAM_BOOL);
$cancel         = optional_param('cancel', false, PARAM_BOOL);
$interested     = optional_param('interested', false, PARAM_BOOL); // Declare interest.
$listid         = optional_param('listid', uniqid('f2f'), PARAM_ALPHANUM); // Session key to list of users to add.
$currenturl     = new moodle_url('/mod/facetoface/attendees/list/remove.php', array('s' => $s, 'listid' => $listid));
$action = 'remove';
$attendees = array();
$notification = '';

$seminarevent = new \mod_facetoface\seminar_event($s);
$seminar = $seminarevent->get_seminar();
$course = $DB->get_record('course', ['id' => $seminar->get_course()]);
$cm = $seminar->get_coursemodule();
$context =  context_module::instance($cm->id);

$seminarevent = new seminar_event($s);
$seminareventid = $seminarevent->get_id();

// Check essential permissions
require_login($course, false, $cm);
require_capability('mod/facetoface:addattendees', $context);

$PAGE->set_context($context);
$PAGE->set_url('/mod/facetoface/attendees/list/remove.php', array('s' => $s, 'listid' => $listid));
$PAGE->set_cm($cm);
$PAGE->set_pagelayout('standard');

$list = new \mod_facetoface\bulk_list($listid, $currenturl, $action);

if ($frm = data_submitted()) {
    require_sesskey();
    if (!empty($frm->removeselect)) {
        $userstoremove = array();
        foreach ($frm->removeselect as $removeuser) {
            if (!$removeuser = clean_param($removeuser, PARAM_INT)) {
                continue; // invalid userid
            }
            $userstoremove[] = $removeuser;
        }

        $list->set_user_ids($userstoremove);
    }
    // Continue button.
    if ($next) {
        if (empty($userstoremove)) {
            $notification = get_string('pleaseselectusers', 'mod_facetoface');
        } else {
            // Redirect to confirmation.
            redirect(new moodle_url('/mod/facetoface/attendees/list/removeconfirm.php',
                                array('s' => $s,
                                      'listid' => $listid)));
            return;
        }
    } else if ($clearsearch) {
        $searchtext = '';
    }
}


if ($cancel) {
    $list->clean();
    redirect(new moodle_url('/mod/facetoface/attendees/view.php', array('s' => $s, 'backtoallsessions' => 1)));
    return;
}

// By default, don't display the waitlist.
$waitlist = 0;
// If the date and time of the session is not known attendees are waitlisted automatically
// until a date and time is applied to the session and they are displayed in the attendees list
// rather than the waitlist. So, only enable the waitlist tab when the date and time for the
// session is known and there are attendees with the waitlist status.

$hassessions = $seminarevent->is_sessions();
if ($hassessions) {
    $waitlistcount = count(facetoface_get_attendees($seminareventid,[\mod_facetoface\signup\state\waitlisted::get_code()]));
    if ($waitlistcount > 0) {
        $waitlist = 1;
    }
}

// Setup attendees array
if ($hassessions) {
    $attendees = facetoface_get_attendees($seminareventid, array(\mod_facetoface\signup\state\booked::get_code(), \mod_facetoface\signup\state\no_show::get_code(),
        \mod_facetoface\signup\state\partially_attended::get_code(), \mod_facetoface\signup\state\fully_attended::get_code()));
} else {
    $attendees = facetoface_get_attendees($seminareventid, array(\mod_facetoface\signup\state\waitlisted::get_code(), \mod_facetoface\signup\state\booked::get_code(), \mod_facetoface\signup\state\no_show::get_code(),
        \mod_facetoface\signup\state\partially_attended::get_code(), \mod_facetoface\signup\state\fully_attended::get_code()));
}

$userlist = $list->get_user_ids();
if (!empty($userlist)) {
    // Add selected users to remove attendees list.
    list($userlist_sql, $userlist_params) = $DB->get_in_or_equal($userlist);
    $userstoadd = $DB->get_records_sql("SELECT u.*, ss.statuscode
                                    FROM {user} u
                                    LEFT JOIN {facetoface_signups} su
                                      ON u.id = su.userid
                                     AND su.sessionid = {$seminareventid}
                                    LEFT JOIN {facetoface_signups_status} ss
                                      ON su.id = ss.signupid
                                     AND ss.superceded != 1
                                   WHERE u.id {$userlist_sql}", $userlist_params);
}

// Get users waiting approval to add to the "already attending" list as we do not want to add them again
$waitingapproval = facetoface_get_requests($seminareventid);

// Add the waiting-approval users - we don't want to add them again
foreach ($waitingapproval as $waiting) {
    if (!isset($attendees[$waiting->id])) {
        $attendees[$waiting->id] = $waiting;
    }
}

// Apply search terms.
$searchtext = trim($searchtext);
if (($searchtext !== '') && !empty($attendees)) {
    // Display only valid users in attendees search. This may block removing deleted/suspended/unconfirmed users from session.
    $where = "username <> 'guest' AND deleted = 0 AND suspended = 0 AND confirmed = 1";
    $params = array();

    list($attendee_sql, $attendee_params) = $DB->get_in_or_equal(array_keys($attendees), SQL_PARAMS_QM, 'param');
    $where .= ' AND u.id ' . $attendee_sql;
    $params = array_merge($params, $attendee_params);

    $fields = get_all_user_name_fields();
    $fields[] = 'email';
    $fields[] = 'idnumber';
    $fields[] = 'username';
    $keywords = totara_search_parse_keywords($searchtext);
    list($searchwhere, $searchparams) = totara_search_get_keyword_where_clause($keywords, $fields);

    $where .= ' AND ' . $searchwhere;
    $params = array_merge($params, $searchparams);

    $usernamefields = get_all_user_name_fields(true, 'u');

    // This starts with a comma, as there may be no extra fields.
    $useridentityfields = get_extra_user_fields_sql(true, 'u', '', get_all_user_name_fields());

    $attendees = $DB->get_records_sql("
        SELECT u.id, {$usernamefields} {$useridentityfields}, u.email, ss.statuscode
        FROM {user} u
        LEFT JOIN {facetoface_signups} su
          ON u.id = su.userid
         AND su.sessionid = {$seminareventid}
        LEFT JOIN {facetoface_signups_status} ss
          ON su.id = ss.signupid
         AND ss.superceded != 1
       WHERE {$where}
       ORDER BY u.lastname ASC, u.firstname ASC", $params);

}

$availableusers = $attendees;
$usercount = count($availableusers);

$PAGE->requires->js_call_amd('mod_facetoface/attendees_addremove', 'init', array(array('s' => $s, 'listid' => $listid)));

$PAGE->set_title(format_string($seminarevent->get_seminar()->get_name()));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('removeattendeestep1', 'facetoface'));

if (!empty($notification)) {
    echo $OUTPUT->notification($notification, 'notifynotice');
}

/**
 * @var mod_facetoface_renderer $seminarrenderer
 */
$seminarrenderer = $PAGE->get_renderer('mod_facetoface');
echo $seminarrenderer->render_seminar_event($seminarevent, false, false, true);

// Configure selector form.
$strusertochange = get_string('userstoremove', 'facetoface');
$stravailableusers = get_string('currentattendees', 'facetoface');
$nointerestsearch = true;
$strlarrow = get_string('remove');
$strrarrow = get_string('undo', 'facetoface');
require_once('addremove_html.php');

echo $OUTPUT->footer();