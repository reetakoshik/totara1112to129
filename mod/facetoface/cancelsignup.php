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

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

use \mod_facetoface\signup as signup;
use \mod_facetoface\signup_helper as signup_helper;
use \mod_facetoface\signup\state\booked;
use \mod_facetoface\signup\state\waitlisted;
use \mod_facetoface\signup\state\user_cancelled;

$s  = required_param('s', PARAM_INT); // facetoface session ID
$confirm           = optional_param('confirm', false, PARAM_BOOL);
$backtoallsessions = optional_param('backtoallsessions', 0, PARAM_BOOL);

$seminarevent = new \mod_facetoface\seminar_event($s);
$seminar = $seminarevent->get_seminar();
if (!$course = $DB->get_record('course', ['id' => $seminar->get_course()])) {
    print_error('error:incorrectcourseid', 'facetoface');
}
$cm = $seminar->get_coursemodule();
$context = context_module::instance($cm->id);

if (!$signup = signup::create($USER->id, $seminarevent)) {
    throw new coding_exception("No user with ID: {$USER->id} has signed-up for the Seminar event ID: {$seminarevent->get_id()}.");
}

// Check user's eligibility to cancel.
$currentstate = $signup->get_state();
if (!$currentstate->can_switch(signup\state\user_cancelled::class)) {
    print_error('error:cancellationsnotallowed', 'facetoface');
}

// User might have an ability to render the settings_navigation, and with settings_navigation, it
// requires the url of a page, therefore. PAGE should set url here first.
$PAGE->set_url('/mod/facetoface/cancelsignup.php', array('s' => $s, 'backtoallsessions' => $backtoallsessions, 'confirm' => $confirm));

require_login($course, false, $cm);
require_capability('mod/facetoface:view', $context);

$currentstate = $signup->get_state();
$userisinwaitlist = $currentstate instanceof waitlisted;
$pagetitle = format_string($seminar->get_name());

$seminarrenderer = $PAGE->get_renderer('mod_facetoface');

$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

if ($backtoallsessions) {
    $returnurl = new moodle_url('/mod/facetoface/view.php', array('f' => $seminar->get_id()));
} else {
    $returnurl = new moodle_url('/course/view.php', array('id' => $course->id));
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

    // Attempt to switch the signup state.
    if (signup_helper::can_user_cancel($signup)) {
        signup_helper::user_cancel($signup);
        // Update cancellation custom fields.
        $fromform->id = $signup->get_id();
        customfield_save_data($fromform, 'facetofacecancellation', 'facetoface_cancellation');

        // Page notification box.
        $message = $userisinwaitlist ? get_string('waitlistcancelled', 'facetoface')
            : get_string('bookingcancelled', 'facetoface');
        if ($userisinwaitlist === false) {
            $error = \mod_facetoface\notice_sender::signup_cancellation($signup);
            if (empty($error)) {
                $minstart = $seminarevent->get_mintimestart();
                if ($minstart) {
                    $message .= html_writer::empty_tag('br') . html_writer::empty_tag('br') . get_string('cancellationsentmgr', 'facetoface');
                } else {
                    $msg = ($CFG->facetoface_notificationdisable ? 'cancellationnotsent' : 'cancellationsent');
                    $message .= html_writer::empty_tag('br') . html_writer::empty_tag('br') . get_string($msg, 'facetoface');
                }
            } else {
                print_error($error, 'facetoface');
            }
        }

        totara_set_notification($message, $returnurl, array('class' => 'notifysuccess'));
    } else {
        $failures = $signup->get_failures(user_cancelled::class);
        throw new coding_exception("Could not cancel user signup.", implode("\n", $failures));
    }
}

echo $OUTPUT->header();

$strheading = $userisinwaitlist ? 'cancelwaitlistfor' : 'cancelbookingfor';
$heading = get_string($strheading, 'facetoface', $seminar->get_name());

echo $OUTPUT->box_start();
echo $OUTPUT->heading($heading);

$viewattendees = has_capability('mod/facetoface:viewattendees', $context);
echo $seminarrenderer->render_seminar_event($seminarevent, $viewattendees);
$mform->display();

echo $OUTPUT->box_end();
echo $OUTPUT->footer($course);
