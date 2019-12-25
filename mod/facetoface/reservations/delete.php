<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author  Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');

$sid = required_param('s', PARAM_INT);
$managerid = optional_param('managerid', 0, PARAM_INT);
$confirm = optional_param('confirm', false, PARAM_BOOL);

$seminarevent = new \mod_facetoface\seminar_event($sid);
$seminar = $seminarevent->get_seminar();
$course = $DB->get_record('course', array('id' => $seminar->get_course()), '*', MUST_EXIST);
$cm = $seminar->get_coursemodule();
$context = context_module::instance($cm->id);

$url = new moodle_url('/mod/facetoface/reservations/delete.php', ['s' => $sid, 'confirm' => $confirm, 'managerid' => $managerid, 'sesskey' => sesskey()]);
$PAGE->set_url($url);

require_login($course, false, $cm);
require_capability('mod/facetoface:managereservations', $context);

if ($confirm) {
    // Delete reservations to free up space in session.
    if (confirm_sesskey()) {
        try {
            $signups = \mod_facetoface\reservations::delete($seminarevent, $managerid);
            \mod_facetoface\signup_helper::update_attendees($seminarevent);

            $message = get_string('managerreservationdeleted', 'mod_facetoface');
            $url = new moodle_url('/mod/facetoface/view.php', ['f' => $seminarevent->get_facetoface()]);
            totara_set_notification($message, $url, ['class' => 'notifysuccess']);
        } catch (moodle_exception $e) {
            $message = get_string('managerreservationdeletionfailed', 'mod_facetoface');
            $url = new moodle_url('/mod/facetoface/reservations/manage.php', ['s' => $sid]);
            totara_set_notification($message, $url, ['class' => 'notifyproblem']);
        }
    }
}

$PAGE->set_title(get_string('deletereservation', 'mod_facetoface'));
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('deletereservation', 'mod_facetoface'));

$confirmurl = new moodle_url('/mod/facetoface/reservations/delete.php', ['s' => $sid, 'confirm' => true, 'managerid' => $managerid, 'sesskey' => sesskey()]);
$cancelurl  = new moodle_url('/mod/facetoface/reservations/manage.php', ['s' => $sid]);

$manager = $DB->get_record('user', ['id' => $managerid]);
$managername = fullname($manager);

echo $OUTPUT->confirm(get_string('deletereservationconfirm', 'mod_facetoface', $managername), $confirmurl, $cancelurl);
echo $OUTPUT->footer();
