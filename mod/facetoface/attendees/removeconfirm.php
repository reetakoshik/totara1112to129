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
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');
require_once($CFG->dirroot.'/mod/facetoface/attendees/forms.php');

// The number of users that should be shown per page.
define('USERS_PER_PAGE', 50);

$s      = required_param('s', PARAM_INT); // facetoface session ID
$listid = required_param('listid', PARAM_ALPHANUM); // Session key to list of users to add.
$page   = optional_param('page', 0, PARAM_INT); // Current page number.

list($session, $facetoface, $course, $cm, $context) = facetoface_get_env_session($s);

// Check essential permissions.
require_login($course, false, $cm);
require_capability('mod/facetoface:removeattendees', $context);

$currenturl = new moodle_url('/mod/facetoface/attendees/removeconfirm.php', array('s' => $s, 'listid' => $listid, 'page' => $page));
$PAGE->set_context($context);
$PAGE->set_url($currenturl);
$PAGE->set_cm($cm);
$PAGE->set_pagelayout('standard');

$list = new \mod_facetoface\bulk_list($listid);

// Selected users.
$userlist = $list->get_user_ids();
if (empty($userlist)) {
    totara_set_notification(get_string('updateattendeesunsuccessful', 'facetoface'),
            new moodle_url('/mod/facetoface/attendees.php', array('s' => $s, 'backtoallsessions' => 1)));
}

$mform = new removeconfirm_form(null, [
    's' => $s,
    'listid' => $listid,
    'enablecustomfields' => !$list->has_user_data(),
    'is_notification_active' => facetoface_is_notification_active(MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION,
        $facetoface, true)
]);

$returnurl = new moodle_url('/mod/facetoface/attendees.php', array('s' => $s, 'backtoallsessions' => 1));
if ($mform->is_cancelled()) {
    $list->clean();
    redirect($returnurl);
}

// Get users waiting approval to add to the "already attending" list as we might want to remove them as well.
$waitingapproval = facetoface_get_requests($session->id);

if ($fromform = $mform->get_data()) {
    if (empty($fromform->submitbutton)) {
        print_error('error:unknownbuttonclicked', 'facetoface', $list->get_returnurl());
    }

    if (empty($_SESSION['f2f-bulk-results'])) {
        $_SESSION['f2f-bulk-results'] = array();
    }

    $removed  = array();
    $errors = array();
    // Original booked attendees plus those awaiting approval
    if ($session->cntdates) {
        $original = facetoface_get_attendees($session->id, array(MDL_F2F_STATUS_BOOKED, MDL_F2F_STATUS_NO_SHOW,
            MDL_F2F_STATUS_PARTIALLY_ATTENDED, MDL_F2F_STATUS_FULLY_ATTENDED));
    } else {
        $original = facetoface_get_attendees($session->id, array(MDL_F2F_STATUS_WAITLISTED, MDL_F2F_STATUS_BOOKED, MDL_F2F_STATUS_NO_SHOW,
            MDL_F2F_STATUS_PARTIALLY_ATTENDED, MDL_F2F_STATUS_FULLY_ATTENDED));
    }

    // Add those awaiting approval
    foreach ($waitingapproval as $waiting) {
        if (!isset($original[$waiting->id])) {
            $original[$waiting->id] = $waiting;
        }
    }

    // Removing old attendees.
    // Check if we need to remove anyone.
    $users = $mform->get_user_list($userlist);
    $attendeestoremove = array_intersect_key($original, $users);
    if (!empty($attendeestoremove)) {
        $clonefromform = serialize($fromform);
        foreach ($attendeestoremove as $attendee) {
            $result = array();
            $result['idnumber'] = $attendee->idnumber;
            $result['name'] = fullname($attendee);

            if (facetoface_user_cancel($session, $attendee->id, true, $cancelerr)) {
                // Notify the user of the cancellation if the session hasn't started yet
                $timenow = time();
                $notifyuser = !empty($fromform->notifyuser);
                $notifymanager = !empty($fromform->notifymanager);

                if (($notifyuser || $notifymanager) and !facetoface_has_session_started($session, $timenow)) {
                    $facetoface->ccmanager = $notifymanager;
                    $session->notifyuser = $notifyuser;
                    facetoface_send_cancellation_notice($facetoface, $session, $attendee->id);
                }

                // Store customfields.
                $signupstatus = facetoface_get_attendee($session->id, $attendee->id);
                $customdata = $list->has_user_data() ? (object)$list->get_user_data($attendee->id) : $fromform;
                $customdata->id = $signupstatus->submissionid;
                customfield_save_data($customdata, 'facetofacecancellation', 'facetoface_cancellation');
                // Values of multi-select are changing after edit_save_data func.
                $fromform = unserialize($clonefromform);

                $result['result'] = get_string('removedsuccessfully', 'facetoface');
                $removed[] = $result;
            } else {
                $result['result'] = $cancelerr;
                $errors[] = $result;
            }
        }
    }

    // Log that users were edited.
    if (count($removed) > 0 || count($errors) > 0) {
        \mod_facetoface\event\attendees_updated::create_from_session($session, $context)->trigger();
    }
    $_SESSION['f2f-bulk-results'][$session->id] = array($removed, $errors);

    facetoface_set_bulk_result_notification(array($removed, $errors), 'bulkremove');

    $list->clean();
    redirect($returnurl);
}

$PAGE->set_title(format_string($facetoface->name));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('removeattendeestep2', 'facetoface'));
echo facetoface_print_session($session, false, false, true, true);

// Table.
$f2frenderer = $PAGE->get_renderer('mod_facetoface');
$f2frenderer->setcontext($context);

$users = $mform->get_user_list($userlist, $page, USERS_PER_PAGE);
$paging = new paging_bar(count($userlist), $page, USERS_PER_PAGE, $currenturl);

echo $f2frenderer->render($paging);
echo $f2frenderer->print_userlist_table($users);
echo $f2frenderer->render($paging);

$link = html_writer::link($list->get_returnurl(), get_string('changeselectedusers', 'facetoface'), [
    'class'=>'btn btn-default'
]);
echo html_writer::div($link,'form-group');

$mform->display();
echo $OUTPUT->footer();
