<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
 * Copyright (C) 2013 Davo Smith, Synergy Learning
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
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @author  Larry Zoumas  <zoumas@gmail.com>
 * @author  Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

/**
 * Allocate or reserve spaces for your team.
 */

use mod_facetoface\reservations;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');

$sid = required_param('s', PARAM_INT);
$backtoallsessions = optional_param('backtoallsessions', 1, PARAM_BOOL);
$backtosession = optional_param('backtosession', null, PARAM_ALPHA);
$managerid = optional_param('managerid', null, PARAM_INT);

$seminarevent = new \mod_facetoface\seminar_event($sid);
$seminar = new \mod_facetoface\seminar($seminarevent->get_facetoface());
$course = $DB->get_record('course', array('id' => $seminar->get_course()), '*', MUST_EXIST);
$cm = $seminar->get_coursemodule();
$context = context_module::instance($cm->id);

$url = new moodle_url('/mod/facetoface/reservations/allocate.php', array('s' => $seminarevent->get_id(), 'backtoallsessions' => $backtoallsessions));
if ($backtosession) {
    $url->param('backtosession', $backtosession);
}
if ($managerid) {
    $url->param('managerid', $managerid);
}
$PAGE->set_url($url);

require_login($course, false, $cm);

// Handle cancel.
if ($backtoallsessions) {
    $redir = new moodle_url('/mod/facetoface/view.php', array('id' => $cm->id));
} else if ($backtosession) {
    $redir = new moodle_url('/mod/facetoface/attendees/view.php', array('s' => $seminarevent->get_id(), 'backtoallsessions' => 1,
        'action' => $backtosession));
} else {
    $redir = new moodle_url('/course/view.php', array('id' => $course->id));
}
if (optional_param('cancel', false, PARAM_BOOL)) {
    redirect($redir);
}

$manager = $USER;
$session = facetoface_get_session($seminarevent->get_id());
$reserveinfo = reservations::can_reserve_or_allocate($seminar, array($session), $context, $manager->id);
if ($reserveinfo['allocate'] === false) { // Current user does not have permission to do the requested action for themselves.
    print_error('nopermissionreserve', 'mod_facetoface'); // Not allowed to reserve/allocate spaces.
}

if ($seminarevent->is_sessions()) {
    $signupcount = facetoface_get_num_attendees($seminarevent->get_id(), \mod_facetoface\signup\state\booked::get_code());
} else {
    $signupcount = facetoface_get_num_attendees($seminarevent->get_id(), \mod_facetoface\signup\state\waitlisted::get_code());
}
$capacityleft = max(0, $seminarevent->get_capacity() - $signupcount);
if (!$seminarevent->get_allowoverbook()) {
    $reserveinfo = reservations::limit_info_to_capacity_left($seminarevent, $reserveinfo, $capacityleft);
}
$reserveinfo = reservations::limit_info_by_session_date($seminarevent, $reserveinfo);

$output = $PAGE->get_renderer('mod_facetoface');
$output->setcontext($context);

$preform = '';
$form = '';
// If 'allocate' - show a list of team members you could allocate + options about whether you should allocate into previous
// reservations or allocate new spaces (yes/no for allocate from reserved spaces, with a count, if there are any)
$team = reservations::get_staff_to_allocate($seminar, $seminarevent);

if (empty($team->potential) && empty($team->current)) {
    $form .= html_writer::tag('p', get_string('allocatenoteam', 'mod_facetoface'));
} else {
    $replacereservations = optional_param('replacereservations', true, PARAM_BOOL);
    if ($reserveinfo['reservepastdeadline']) {
        $replaceallocations = false;
    } else {
        $replaceallocations = optional_param('replaceallocations', true, PARAM_BOOL);
    }
    $error = null;

    if (optional_param('add', false, PARAM_BOOL)) {
        // Allocating users to spaces.
        require_sesskey();

        $spaces = $reserveinfo['allocate'][$seminarevent->get_id()];
        if (!$replacereservations) {
            $spaces -= $reserveinfo['reserved'][$seminarevent->get_id()];
        }
        $spaces = max(0, $spaces);
        $newallocations = optional_param_array('allocation', array(), PARAM_INT);
        $newallocations = array_intersect($newallocations, array_keys($team->potential));
        if (count($newallocations) > $spaces) {
            // No spaces left.
            if (!$replacereservations && $reserveinfo['reserved'][$seminarevent->get_id()]) {

                $error = get_string('allocationfull_noreserve', 'mod_facetoface', $spaces);
            } else {
                $error = get_string('allocationfull_reserve', 'mod_facetoface', $spaces);
            }
        } else {
            // Allocate the spaces.
            if ($replacereservations) {
                $newallocations = reservations::replace($seminarevent, $USER->id, $newallocations);
            }
            $errors = reservations::allocate_spaces($seminarevent, $USER->id, $newallocations);
            $message = "";
            $notifytype = \core\output\notification::NOTIFY_INFO;
            if ($errors) {
                $message = \html_writer::alist($errors);
                $notifytype = \core\output\notification::NOTIFY_ERROR;
            }
            redirect($redir, $message, null, $notifytype);
        }

    } else if (optional_param('remove', false, PARAM_BOOL)) {
        require_sesskey();

        $removeallocations = optional_param_array('deallocation', array(), PARAM_INT);
        $removeallocations = array_intersect($removeallocations, array_keys($team->current));
        $removeallocations = array_diff($removeallocations, array_keys($team->cannotunallocate));

        reservations::remove_allocations($seminarevent, $seminar, $removeallocations, $replaceallocations);
        redirect($redir);
    }

    if ($error) {
        $form .= $output->notification($error);
    }
    $form .= $output->session_user_selector($team, $seminarevent->to_record(), $reserveinfo);

    $yesno = array(1 => get_string('yes'), 0 => get_string('no'));

    if (!empty($reserveinfo['reserved'][$seminarevent->get_id()])) {
        $form .= html_writer::tag('label', get_string('replacereservations', 'mod_facetoface'),
            array('for' => 'replacereservations'));
        $form .= ' ('.$reserveinfo['reserved'][$seminarevent->get_id()].') ';
        $form .= html_writer::select($yesno, 'replacereservations', (int)$replacereservations, null,
            array('id' => 'replaceresrvations'));
        $form .= html_writer::empty_tag('br');
    }

    if (!empty($reserveinfo['allocated'][$seminarevent->get_id()]) && !$reserveinfo['reservepastdeadline']) {
        $form .= html_writer::tag('label', get_string('replaceallocations', 'mod_facetoface'), array('for' => 'replaceallocations'));
        $form .= html_writer::select($yesno, 'replaceallocations', (int)$replaceallocations, null, array('id' => 'replaceallocations'));
        $form .= html_writer::empty_tag('br');
    }
}

// Get a list of reservations/allocations made by this manager in other sessions for this facetoface.
$otherreservations = reservations::get_others($seminarevent, $manager->id);

// Wrap the form elements in a 'form' tag and add the required page params.
$baseurl = new moodle_url($PAGE->url, array('sesskey' => sesskey()));
$form .= html_writer::input_hidden_params($baseurl);
$form = html_writer::tag('form', $form, array('action' => $baseurl->out_omit_querystring(), 'method' => 'POST'));

$title = get_string('allocate', 'mod_facetoface');
$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $output->header();

echo $output->heading(format_string($seminar->get_name()));
/**
 * @var mod_facetoface_renderer $seminarrenderer
 */
$seminarrenderer = $PAGE->get_renderer('mod_facetoface');
echo $seminarrenderer->render_seminar_event($seminarevent, false);
echo $preform;
echo $form;
echo $output->other_reservations($otherreservations, $manager);
echo $output->footer();
