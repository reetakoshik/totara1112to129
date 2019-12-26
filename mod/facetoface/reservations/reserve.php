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
$seminar = $seminarevent->get_seminar();
$course = $DB->get_record('course', array('id' => $seminar->get_course()), '*', MUST_EXIST);
$cm = $seminar->get_coursemodule();
$context = context_module::instance($cm->id);

$url = new moodle_url('/mod/facetoface/reservations/reserve.php', array('s' => $seminarevent->get_id(), 'backtoallsessions' => $backtoallsessions));
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
    $redir = new moodle_url('/mod/facetoface/attendees/view.php', array('s' => $seminarevent->get_id(), 'backtoallsessions' => 1));
} else {
    $redir = new moodle_url('/course/view.php', array('id' => $course->id));
}
if (optional_param('cancel', false, PARAM_BOOL)) {
    redirect($redir);
}

// Gather info about the number of reservations / allocations the manager has/can make.
if (!$managerid || $managerid == $USER->id) { // Can only reserve for other users, not allocate.
    $manager = $USER;
} else {
    $manager = $DB->get_record('user', array('id' => $managerid), '*', MUST_EXIST);
}
$session = facetoface_get_session($seminarevent->get_id());
$reserveinfo = reservations::can_reserve_or_allocate($seminar, array($session), $context, $manager->id);
if ($reserveinfo['reserve'] === false) { // Current user does not have permission to do the requested action for themselves.
    if (empty($reserveinfo['reserveother'])) { // Not able to reserve spaces for other users either.
        print_error('nopermissionreserve', 'mod_facetoface'); // Not allowed to reserve/allocate spaces.
    }
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

/**
 * @var mod_facetoface_renderer $output
 */
$output = $PAGE->get_renderer('mod_facetoface');
$output->setcontext($context);

$preform = '';
$form = '';
if ($reserveinfo['reservepastdeadline']) {
    $form = $output->notification(get_string('reservepastdeadline', 'mod_facetoface', $seminar->get_reservedays()));
} else {

    // Handle reserve form submission.
    if (optional_param('submit', false, PARAM_BOOL)) {
        require_sesskey();
        $reserve = required_param('reserve', PARAM_INT);
        $reserve = max(0, min($reserve, $reserveinfo['maxreserve'][$seminarevent->get_id()]));

        $diff = $reserve - $reserveinfo['reserved'][$seminarevent->get_id()];
        if ($diff > 0) {
            $toadd = $diff;
            $book = min($capacityleft, $toadd); // Book any reservations for which there is capacity left ...
            $waitlist = $toadd - $book; // ... and add the rest to the waiting list.
            reservations::add($seminarevent, $manager->id, $book, $waitlist);
        } else if ($diff < 0) {
            reservations::remove($seminarevent, $manager->id, -$diff, ($USER->id != $manager->id));
        }

        redirect($redir);
    } else if (optional_param('cancelreservation', false, PARAM_BOOL)) {
        require_sesskey();
        reservations::remove($seminarevent, $manager->id, 1, ($USER->id != $manager->id));
        redirect($redir);
    }

    $managers = array();
    if ($reserveinfo['reserveother']) {
        // Form to select which manager to reserve spaces for.
        $managers = facetoface_get_manager_list();
        $preform .= html_writer::input_hidden_params($PAGE->url);
        $preform .= html_writer::select($managers, 'managerid', $manager->id).' ';
        $preform .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'selectmanager',
                                                         'value' => get_string('selectmanager', 'mod_facetoface')));
        $preform = html_writer::tag('form', $preform, array('action' => $PAGE->url->out_omit_querystring(), 'method' => 'post'));
        $preform .= html_writer::empty_tag('br');
        $preform .= html_writer::empty_tag('br');
    }

    if (empty($reserveinfo['reserve'])) {
        $form = html_writer::tag('p', get_string('reservenopermissionother', 'mod_facetoface'));
    }
    // Generate the reserve form.
    else if (empty($reserveinfo['maxreserve'][$seminarevent->get_id()])) {
        // No spaces left that the manager can reserve.
        if ($manager->id == $USER->id && !$reserveinfo['reserve']) {
            $form = ''; // Can only reserve for others, not for self - wait the user to select a manager.
        } else if ($capacityleft == 0) {
            $form = html_writer::tag('p', get_string('reservenocapacity', 'mod_facetoface'));
        } else if ($manager->id == $USER->id) {
            $form = html_writer::tag('p', get_string('reserveallallocated', 'mod_facetoface'));
        } else {
            $form = html_writer::tag('p', get_string('reserveallallocatedother', 'mod_facetoface'));
        }

    } else {
        $reserveopts = range(1, $reserveinfo['maxreserve'][$seminarevent->get_id()]);
        $reserveopts = array(0 => get_string('noreservations', 'mod_facetoface')) + array_combine($reserveopts, $reserveopts);
        $waitliststart = $capacityleft + $reserveinfo['reserved'][$seminarevent->get_id()];
        foreach ($reserveopts as $key => $value) {
            if ($key > $waitliststart) {
                $reserveopts[$key] .= '*';
            }
        }
        if ($manager->id == $USER->id) {
            $form .= html_writer::tag('p', get_string('reserveintro', 'mod_facetoface'));
        } else {
            $form .= html_writer::tag('p', get_string('reserveintroother', 'mod_facetoface', $managers[$manager->id]));
        }
        $form .= html_writer::tag('label', get_string('reserve', 'mod_facetoface'), array('for' => 'reserve'));
        $form .= html_writer::select($reserveopts, 'reserve', $reserveinfo['reserved'][$seminarevent->get_id()], null, array('id' => 'reserve'));
        $form .= html_writer::empty_tag('br');
        if ($reserveinfo['maxreserve'][$seminarevent->get_id()] > $waitliststart) {
            $form .= ' '.get_string('reservecapacitywarning', 'mod_facetoface', $capacityleft);
            $form .= html_writer::empty_tag('br');
        }
        $form .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'submit', 'value' => get_string('update')));
        $form .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'cancel', 'value' => get_string('cancel')));
    }
}

// Get a list of reservations/allocations made by this manager in other sessions for this facetoface.
$otherreservations = reservations::get_others($seminarevent, $manager->id);

// Wrap the form elements in a 'form' tag and add the required page params.
$baseurl = new moodle_url($PAGE->url, array('sesskey' => sesskey()));
$form .= html_writer::input_hidden_params($baseurl);
$form = html_writer::tag('form', $form, array('action' => $baseurl->out_omit_querystring(), 'method' => 'POST'));

$title = get_string('reserve', 'mod_facetoface');
$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $output->header();
echo $output->heading(format_string($seminar->get_name()));
echo $output->render_seminar_event($seminarevent, false);
echo $preform;
echo $form;
echo $output->other_reservations($otherreservations, $manager);
echo $output->footer();
