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
 * @author David Curry <david.curry@totaralearning.com>
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package mod_facetoface
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

use mod_facetoface\signup_helper;
use mod_facetoface\signup;
use mod_facetoface\seminar;

$s = required_param('s', PARAM_INT); // {facetoface_sessions}.id
$backtoallsessions = optional_param('backtoallsessions', 0, PARAM_BOOL);

$seminarevent = new \mod_facetoface\seminar_event($s);
$seminar = $seminarevent->get_seminar();
$course = $DB->get_record('course', ['id' => $seminar->get_course()], '*', MUST_EXIST);
$cm = $seminar->get_coursemodule();
$context = context_module::instance($cm->id);

if (!totara_course_is_viewable($course->id)) {
    redirect(new moodle_url('/course/view.php', ['id' => $course->id]));
}

$signup = signup::create($USER->id, $seminarevent);

if (isguestuser()) {
    redirect(new moodle_url('/course/view.php', ['id' => $course->id]),
        get_string('error:cannotsignuptoeventasguest', 'facetoface'));
}

if (!empty($seminarevent->get_cancelledstatus())) {
    redirect(new moodle_url('/course/view.php', ['id' => $course->id]),
        get_string('error:cannotsignupforacancelledevent', 'facetoface'));
}

if ($CFG->enableavailability) {
    if (!get_fast_modinfo($cm->course)->get_cm($cm->id)->available) {
        redirect(new moodle_url('/course/view.php', ['id' => $course->id]));
        die;
    }
}

/** @var enrol_totara_facetoface_plugin $enrol */
$enrol = enrol_get_plugin('totara_facetoface');
if (in_array($s, array_keys($enrol->get_enrolable_sessions($course->id)))) {
    // F2f direct enrolment is enabled for this session.
    require_login();
} else {
    // F2f direct enrolment is not enabled here, the user must have the ability to sign up for sessions
    // in this f2f as normal.
    require_login($course, false, $cm);
    require_capability('mod/facetoface:view', $context);
}

if ($backtoallsessions) {
    $returnurl = new moodle_url('/mod/facetoface/view.php', ['f' => $seminar->get_id()]);
} else {
    $returnurl = new moodle_url('/course/view.php', ['id' => $course->id]);
}

// This is not strictly required for signup (more correctly it is checked in actor_has_role), but leaving it for early
// indication of the issue.
$trainerroles = facetoface_get_trainer_roles(context_course::instance($course->id));
$trainers     = facetoface_get_trainers($seminarevent->get_id());
if ($seminar->get_approvaltype() == seminar::APPROVAL_ROLE) {
    if (!$trainerroles || !$trainers) {
        totara_set_notification(get_string('error:missingrequiredrole', 'facetoface'), $returnurl);
    }
}

$PAGE->set_cm($cm);
$PAGE->set_url('/mod/facetoface/signup.php', ['s' => $s, 'backtoallsessions' => $backtoallsessions]);
$PAGE->set_title(format_string($seminar->get_name()));
$PAGE->set_heading($course->fullname);

$params = [
    'signup' => $signup,
    'backtoallsessions' => $backtoallsessions,
];
$mform = new \mod_facetoface\form\signup(null, $params, 'post', '', ['name' => 'signupform']);

local_js([TOTARA_JS_DIALOG, TOTARA_JS_TREEVIEW]);

$PAGE->requires->strings_for_js(['selectmanager'], 'mod_facetoface');
$jsmodule = [
        'name' => 'facetoface_managerselect',
        'fullpath' => '/mod/facetoface/js/manager.js',
        'requires' => ['json']
];
$selected_manager = dialog_display_currently_selected(get_string('currentmanager', 'mod_facetoface'), 'manager');
$args = [
    'userid' => $USER->id,
    'fid' => $seminar->get_id(),
    'manager' => $selected_manager,
    'sesskey' => sesskey()
];
$PAGE->requires->js_init_call('M.facetoface_managerselect.init', $args, false, $jsmodule);

if ($mform->is_cancelled()) {
    redirect($returnurl);
}
if ($fromform = $mform->get_data()) {
    if (empty($fromform->submitbutton)) {
        print_error('error:unknownbuttonclicked', 'facetoface', $returnurl);
    }

    if (!is_enrolled($context, $USER)) {
        // Check for and attempt to enrol via the totara_facetoface enrolment plugin.
        $enrolments = enrol_get_plugins(true);
        $instances = enrol_get_instances($course->id, true);
        foreach ($instances as $instance) {
            if ($instance->enrol === 'totara_facetoface') {
                $data = clone($fromform);
                $data->sid = [$seminarevent->get_id()];
                $enrolments[$instance->enrol]->enrol_totara_facetoface($instance, $data, $course, $returnurl);
                // We expect enrol module to take all required sign up action and redirect, so it should never return.
                debugging("Seminar direct enrolment should never return to signup page");
                exit();

            }
        }
    }

    $signup->set_notificationtype($fromform->notificationtype);
    $signup->set_discountcode($fromform->discountcode);

    $managerselect = get_config(null, 'facetoface_managerselect');
    if ($managerselect && isset($fromform->managerid) && !empty($fromform->managerid)) {
        $signup->set_managerid($fromform->managerid);
    }

    $f2fselectedjobassignmentelemid = 'selectedjobassignment_' . $seminar->get_id();
    if (property_exists($fromform, $f2fselectedjobassignmentelemid)) {
        $signup->set_jobassignmentid($fromform->$f2fselectedjobassignmentelemid);
    }

    if (signup_helper::can_signup($signup)) {
        signup_helper::signup($signup);

        // Custom fields.
        $fromform->id = $signup->get_id();
        customfield_save_data($fromform, 'facetofacesignup', 'facetoface_signup');

        // Notification.
        $state = $signup->get_state();
        $message = $state->get_message();
        $cssclass = 'notifymessage';

        // There may be lots of factors that will prevent confirmation message to appear at user mailbox
        // but in most cases this will be true:
        if ($state instanceof signup\state\booked) {
            $cssclass = 'notifysuccess';
            if (!$signup->get_skipusernotification() && $fromform->notificationtype != MDL_F2F_NONE) {
                $message .= html_writer::empty_tag('br') . html_writer::empty_tag('br') .
                    get_string('confirmationsent', 'facetoface');
            }
        }
    } else {
        // Note - We can't use the renderer_signup_failures() function here, but this is the same.
        $failures = signup_helper::get_failures($signup);
        reset($failures);
        $message = current($failures);

        $cssclass = 'notifyerror';
    }

    totara_set_notification($message, $returnurl, ['class' => $cssclass]);
}

echo $OUTPUT->header();
echo $OUTPUT->box_start();

// Choose header depending on resulting state: waitlist or booked.
$heading = get_string('signupfor', 'facetoface', $seminar->get_name());
$currentstate = $signup->get_state();
if ($currentstate instanceof signup\state\booked ||
    $currentstate instanceof signup\state\requested ||
    $currentstate instanceof signup\state\waitlisted) {
    $heading = $seminar->get_name();
}
if (!$currentstate->can_switch(signup\state\booked::class) &&
    $currentstate->can_switch(signup\state\waitlisted::class)) {
    $heading = get_string('waitlistfor', 'facetoface', $seminar->get_name());
}
echo $OUTPUT->heading(format_string($heading));

/**
 * @var mod_facetoface_renderer $seminarrenderer
 */
$seminarrenderer = $PAGE->get_renderer('mod_facetoface');
$signedup = !$signup->get_state()->is_not_happening();
$viewattendees = has_capability('mod/facetoface:viewattendees', $context);
echo $seminarrenderer->render_seminar_event($seminarevent, $viewattendees, false, $signedup);

// Cancellation links
if ($currentstate->can_switch(signup\state\user_cancelled::class)) {
    $canceltext = get_string('cancelbooking', 'facetoface');
    if ($currentstate instanceof signup\state\waitlisted) {
        $canceltext = get_string('cancelwaitlist', 'facetoface');
    }
    echo html_writer::link(new moodle_url('cancelsignup.php', ['s' => $seminarevent->get_id(), 'backtoallsessions' => $backtoallsessions]), $canceltext, ['title' => $canceltext]);
    echo ' &ndash; ';
}

if ($viewattendees) {
    echo html_writer::link(new moodle_url('attendees/view.php', ['s' => $seminarevent->get_id(), 'backtoallsessions' => $backtoallsessions]), get_string('seeattendees', 'facetoface'), ['title' => get_string('seeattendees', 'facetoface')]);
}

if (signup_helper::can_signup($signup)) {
    $mform->display();
} else if ($currentstate instanceof signup\state\not_set
    || $currentstate instanceof signup\state\user_cancelled
    || $currentstate instanceof signup\state\declined
    ) {
    // Display message only if user is not signed up:
    echo $seminarrenderer->render_signup_failures(signup_helper::get_failures($signup));
}

echo html_writer::empty_tag('br') . html_writer::link($returnurl, get_string('goback', 'facetoface'), ['title' => get_string('goback', 'facetoface')]);

echo $OUTPUT->box_end();
echo $OUTPUT->footer($course);
