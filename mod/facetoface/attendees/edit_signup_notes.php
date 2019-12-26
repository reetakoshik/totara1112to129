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
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @package mod_facetoface
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

$userid    = required_param('userid', PARAM_INT); // Facetoface signup user ID.
$sessionid = required_param('s', PARAM_INT); // Facetoface session ID.
$return = optional_param('return', 'view', PARAM_ALPHA);

$url = new moodle_url('/mod/facetoface/attendees/edit_signup_notes.php', array('userid' => $userid, 'sessionid' => $sessionid));
$returnurl = new moodle_url("/mod/facetoface/attendees/{$return}.php", array('s' => $sessionid, 'backtoallsessions' => 1));

require_sesskey();

list($session, $facetoface, $course, $cm, $context) = facetoface_get_env_session($sessionid);
// Check essential permissions.
$PAGE->set_url($url);
require_login($course, true, $cm);
require_capability('mod/facetoface:manageattendeesnote', $context);

$attendeenote = facetoface_get_attendee($sessionid, $userid);
$attendeenote->userid = $attendeenote->id;
$attendeenote->id = $attendeenote->submissionid;
$attendeenote->sessionid = $sessionid;
customfield_load_data($attendeenote, 'facetofacesignup', 'facetoface_signup');

$params = [
    's' => $sessionid,
    'userid' => $userid,
    'return' => $return,
    'attendeenote' => $attendeenote
];
$mform = new \mod_facetoface\form\attendee_note(null, $params);
$mform->set_data($attendeenote);

if ($mform->is_cancelled()) {
    redirect($returnurl);
}

if ($fromform = $mform->get_data()) {
    // Save the custom fields.
    customfield_save_data($fromform, 'facetofacesignup', 'facetoface_signup');
    // Trigger the event.
    \mod_facetoface\event\attendee_note_updated::create_from_instance($attendeenote, $context)->trigger();
    // Redirect.
    redirect($returnurl);
}

$pagetitle = format_string($facetoface->name);

$PAGE->set_title(format_string($facetoface->name, true, array('context' => $context)));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $mform->display();
echo $OUTPUT->footer();
