<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');

$sid = required_param('s', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$managerid = optional_param('managerid', 0, PARAM_INT);
$confirm = optional_param('confirm', false, PARAM_BOOL);

list($session, $facetoface, $course, $cm, $context) = facetoface_get_env_session($sid);

$url = new moodle_url('/mod/facetoface/managereservations.php', array('s' => $session->id));
$PAGE->set_url($url);

require_login($course, false, $cm);
require_capability('mod/facetoface:managereservations', $context);

if ($action == 'delete') {
    if ($confirm) {
        // Delete reservations to free up space in session.
        if (confirm_sesskey()) {
            $result = facetoface_delete_reservations($session->id, $managerid);
            $result = $result && facetoface_update_attendees($session);

            if ($result) {
                $message = get_string('managerreservationdeleted', 'mod_facetoface');
                $url = new moodle_url('/mod/facetoface/view.php', array('f' => $facetoface->id));
                totara_set_notification($message, $url, array('class' => 'notifysuccess'));
            } else {
                $message = get_string('managerreservationdeletionfailed', 'mod_facetoface');
                totara_set_notification($message, null);
            }
        }
    } else {
        $PAGE->set_title(get_string('deletereservation', 'mod_facetoface'));
        $PAGE->set_heading($course->fullname);
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('deletereservation', 'mod_facetoface'));

        $confirmurl = new moodle_url('/mod/facetoface/managereservations.php',
            array('s' => $sid, 'action' => 'delete', 'confirm' => true, 'managerid' => $managerid));
        $cancelurl = new moodle_url('/mod/facetoface/managereservations.php', array('s' => $sid));

        $manager = $DB->get_record('user', array('id' => $managerid));
        $managername = fullname($manager);

        echo $OUTPUT->confirm(get_string('deletereservationconfirm', 'mod_facetoface', $managername), $confirmurl, $cancelurl);
        echo $OUTPUT->footer();
        die;
    }
}

$title = get_string('managereservations', 'mod_facetoface');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$output = $PAGE->get_renderer('mod_facetoface');
$output->setcontext($context);

echo $output->header();
echo $output->heading(format_string($facetoface->name));
echo facetoface_print_session($session, false);

$reservations = facetoface_get_session_reservations($session->id);

echo $output->print_reservation_management_table($reservations);

$backurl = new moodle_url('/mod/facetoface/view.php', array('id' => $cm->id));

echo $output->single_button($backurl, get_string('goback', 'mod_facetoface'), 'get');
echo $output->footer();
