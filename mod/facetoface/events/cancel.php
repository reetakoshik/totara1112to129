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

use \mod_facetoface\seminar_event;

$s = required_param('s', PARAM_INT); // facetoface session ID
$backtoallsessions = optional_param('backtoallsessions', 1, PARAM_BOOL);

$seminarevent = new \mod_facetoface\seminar_event($s);
$seminar = $seminarevent->get_seminar();
$course = $DB->get_record('course', ['id' => $seminar->get_course()]);
$cm = $seminar->get_coursemodule();
$context =  context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/facetoface:editevents', $context);

$PAGE->set_url('/mod/facetoface/events/cancel.php', array('s' => $s, 'backtoallsessions' => $backtoallsessions));
$PAGE->set_title($seminar->get_name());
$PAGE->set_heading($course->fullname);

if ($backtoallsessions) {
    $returnurl = new moodle_url('/mod/facetoface/view.php', array('f' => $seminarevent->get_facetoface()));
} else {
    $returnurl = new moodle_url('/course/view.php', array('id' => $course->id));
}

if ($seminarevent->is_started()) {
    // How did they get here? There should not be any link in UI to this page.
    redirect($returnurl);
}
if ($seminarevent->get_cancelledstatus() != 0) {
    // How did they get here? There should not be any link in UI to this page.
    redirect($returnurl);
}

$mform = new \mod_facetoface\form\cancelsession(null, [
    'backtoallsessions' => $backtoallsessions,
    'seminarevent' => $seminarevent
]);
if ($mform->is_cancelled()) {
    redirect($returnurl);
}

if ($fromform = $mform->get_data()) {
    // This may take a long time...
    ignore_user_abort(true);

    $seminarevent = new seminar_event($s);
    if ($seminarevent->cancel()) {
        // Save the custom fields.
        if ($fromform) {
            $fromform->id = $seminarevent->get_id();
            customfield_save_data($fromform, 'facetofacesessioncancel', 'facetoface_sessioncancel');
        }
    } else {
        print_error('error:couldnotcancelsession', 'facetoface', $returnurl);
    }

    $message = get_string('bookingsessioncancelled', 'facetoface');
    totara_set_notification($message, $returnurl, array('class' => 'notifysuccess'));
}

echo $OUTPUT->header();
echo $OUTPUT->box_start();
echo $OUTPUT->heading(get_string('cancelingsession', 'facetoface', $seminar->get_name()));

/**
 * @var mod_facetoface_renderer $seminarrenderer
 */
$seminarrenderer = $PAGE->get_renderer('mod_facetoface');
echo $seminarrenderer->render_seminar_event($seminarevent, true);


$mform->display();

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
