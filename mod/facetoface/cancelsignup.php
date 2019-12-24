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
 * @package modules
 * @subpackage facetoface
 */

require_once '../../config.php';
require_once 'lib.php';

$s  = required_param('s', PARAM_INT); // facetoface session ID
$confirm           = optional_param('confirm', false, PARAM_BOOL);
$backtoallsessions = optional_param('backtoallsessions', 0, PARAM_BOOL);

list($session, $facetoface, $course, $cm, $context) = facetoface_get_env_session($s);
if (!$session->allowcancellations) {
    print_error('error:cancellationsnotallowed', 'facetoface');
}

require_login($course, false, $cm);
require_capability('mod/facetoface:view', $context);

$userisinwaitlist = facetoface_is_user_on_waitlist($session, $USER->id);
$pagetitle = format_string($facetoface->name);

$PAGE->set_url('/mod/facetoface/cancelsignup.php', array('s' => $s, 'backtoallsessions' => $backtoallsessions, 'confirm' => $confirm));
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

if ($backtoallsessions) {
    $returnurl = new moodle_url('/mod/facetoface/view.php', array('f' => $facetoface->id));
} else {
    $returnurl = new moodle_url('/course/view.php', array('id' => $course->id));
}

// Add booking information.
$session->bookedsession = null;
if ($booked = facetoface_get_user_submissions($facetoface->id,
    $USER->id, MDL_F2F_STATUS_REQUESTED, MDL_F2F_STATUS_BOOKED, $session->id)) {
    $session->bookedsession = reset($booked);
}

$viewattendees = has_capability('mod/facetoface:viewattendees', $context);
$multisessionid = ($facetoface->multiplesessions ? $session->id : null);
$signedup = facetoface_check_signup($facetoface->id, $multisessionid);

if (!$signedup) {
    print_error('notsignedup', 'facetoface', $returnurl);
}

if (!facetoface_allow_user_cancellation($session)) {
    print_error('notallowedtocancel', 'facetoface', $returnurl);
}

$cancellation_note = facetoface_get_attendee($s, $USER->id);
$cancellation_note->id = $cancellation_note->submissionid;
customfield_load_data($cancellation_note, 'facetofacecancellation', 'facetoface_cancellation');

$mform = new \mod_facetoface\form\cancelsignup(null, compact('s', 'backtoallsessions', 'cancellation_note', 'userisinwaitlist'));
if ($mform->is_cancelled()) {
    redirect($returnurl);
}

if ($fromform = $mform->get_data()) { // Form submitted.

    if (empty($fromform->submitbutton)) {
        print_error('error:unknownbuttonclicked', 'facetoface', $returnurl);
    }

    $forcecancel = false;
    $timenow = time();
    $bookedsession = facetoface_get_user_submissions($facetoface->id, $USER->id, MDL_F2F_STATUS_WAITLISTED, MDL_F2F_STATUS_WAITLISTED, $session->id);
    if (!empty($bookedsession) && facetoface_has_session_started($session, $timenow)) {
        $forcecancel = true;
    }

    $errorstr = '';
    if (facetoface_user_cancel($session, false, $forcecancel, $errorstr, '')) {
        $signup = facetoface_get_attendee($session->id, $USER->id);
        $fromform->id = $signup->submissionid;
        customfield_save_data($fromform, 'facetofacecancellation', 'facetoface_cancellation');

        $strmessage = $userisinwaitlist ? 'waitlistcancelled' : 'bookingcancelled';
        $message = get_string($strmessage, 'facetoface');

        if ($session->mintimestart) {
            // Users in waitlist should not receive a cancellation email.
            if ($userisinwaitlist === false) {
                $error = facetoface_send_cancellation_notice($facetoface, $session, $USER->id);
                if (empty($error)) {
                    if ($session->mintimestart && isset($facetoface->cancellationinstrmngr) && !empty($facetoface->cancellationstrmngr)) {
                        $message .= html_writer::empty_tag('br') . html_writer::empty_tag('br') . get_string('cancellationsentmgr', 'facetoface');
                    } else {
                        $msg = ($CFG->facetoface_notificationdisable ? 'cancellationnotsent' : 'cancellationsent');
                        $message .= html_writer::empty_tag('br') . html_writer::empty_tag('br') . get_string($msg, 'facetoface');
                    }
                } else {
                    print_error($error, 'facetoface');
                }
            }
        }

        totara_set_notification($message, $returnurl, array('class' => 'notifysuccess'));
    }
    else {
        print_error($errorstr);
    }

    redirect($returnurl);
}
echo $OUTPUT->header();

$strheading = $userisinwaitlist ? 'cancelwaitlistfor' : 'cancelbookingfor';
$heading = get_string($strheading, 'facetoface', $facetoface->name);

echo $OUTPUT->box_start();
echo $OUTPUT->heading($heading);

echo facetoface_print_session($session, $viewattendees);
$mform->display();

echo $OUTPUT->box_end();
echo $OUTPUT->footer($course);
