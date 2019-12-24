<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Keelin Devenney <keelin@learningpool.com>
 * @package mod_facetoface
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

$s = required_param('s', PARAM_INT); // facetoface session ID
$backtoallsessions = optional_param('backtoallsessions', 1, PARAM_BOOL);

list($session, $facetoface, $course, $cm, $context) = facetoface_get_env_session($s);

require_login($course, false, $cm);
require_capability('mod/facetoface:editevents', $context);

$PAGE->set_url('/mod/facetoface/events/cancel.php', array('s' => $s, 'backtoallsessions' => $backtoallsessions));
$PAGE->set_title($facetoface->name);
$PAGE->set_heading($course->fullname);

if ($backtoallsessions) {
    $returnurl = new moodle_url('/mod/facetoface/view.php', array('f' => $facetoface->id));
} else {
    $returnurl = new moodle_url('/course/view.php', array('id' => $course->id));
}

if (facetoface_has_session_started($session, time())) {
    // How did they get here? There should not be any link in UI to this page.
    redirect($returnurl);
}
if ($session->cancelledstatus != 0) {
    // How did they get here? There should not be any link in UI to this page.
    redirect($returnurl);
}

// NOTE: $session->allowcancellations has no relation to this script.

$mform = new \mod_facetoface\form\cancelsession(null, compact('backtoallsessions', 'session'));
if ($mform->is_cancelled()) {
    redirect($returnurl);
}

if ($fromform = $mform->get_data()) {
    // This may take a long time...
    ignore_user_abort(true);
    if (!facetoface_cancel_session($session, $fromform)) {
        print_error('error:couldnotcancelsession', 'facetoface', $returnurl);
    }

    $message = get_string('bookingsessioncancelled', 'facetoface');
    totara_set_notification($message, $returnurl, array('class' => 'notifysuccess'));
}

echo $OUTPUT->header();
echo $OUTPUT->box_start();
echo $OUTPUT->heading(get_string('cancelingsession', 'facetoface', $facetoface->name));

echo facetoface_print_session($session, true);

$mform->display();

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
