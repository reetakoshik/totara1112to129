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
 * @author Maria Torres <maria.torres@totaralms.com>
 * @package mod_facetoface
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

$userid    = required_param('userid', PARAM_INT); // Facetoface cancellation user ID.
$sessionid = required_param('s', PARAM_INT); // Facetoface session ID.

$url = new moodle_url('/mod/facetoface/attendees/edit_usercancellation_notes', array('userid' => $userid, 'sessionid' => $sessionid));
$returnurlparams = array('s' => $sessionid, 'backtoallsessions' => 1);
$returnurl = new moodle_url('/mod/facetoface/attendees/cancellations.php', $returnurlparams);

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
customfield_load_data($attendeenote, 'facetofacecancellation', 'facetoface_cancellation');

$mform = new \mod_facetoface\form\cancellation_note(null, array('s' => $sessionid, 'userid' => $userid, 'attendeenote' => $attendeenote));
$mform->set_data($attendeenote);

if ($mform->is_cancelled()) {
    redirect($returnurl);
}

if ($fromform = $mform->get_data()) {
    // Save the custom fields.
    customfield_save_data($fromform, 'facetofacecancellation', 'facetoface_cancellation');
    // Redirect.
    redirect($returnurl);
}

$pagetitle = format_string($facetoface->name);

$PAGE->set_title(format_string($facetoface->name, true, array('context' => $context)));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $mform->display();
echo $OUTPUT->footer();
