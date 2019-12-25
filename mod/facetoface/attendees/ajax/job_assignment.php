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
 * @author Andrew Hancox <andrewdchancox@googlemail.com>
 * @package totara
 * @subpackage facetoface
 */

define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

$userid    = required_param('id', PARAM_INT); // Facetoface signup user ID.
$sessionid = required_param('s', PARAM_INT); // Facetoface session ID.

list($session, $facetoface, $course, $cm, $context) = facetoface_get_env_session($sessionid);

// Check essential permissions.
require_course_login($course, true, $cm);
require_capability('mod/facetoface:changesignedupjobassignment', $context);

$jobassignments = \totara_job\job_assignment::get_all($userid);

$usernamefields = get_all_user_name_fields(true, 'u');

$params = array('userid' => $userid);
$sql = "SELECT u.id, fs.id as signupid, fs.jobassignmentid, $usernamefields
        FROM {user} u
        LEFT JOIN {facetoface_signups} fs ON u.id = fs.userid AND fs.sessionid = $sessionid
        WHERE u.id = :userid";
$user = $DB->get_record_sql($sql, $params);

$formparams = array(
    'jobassignments' => $jobassignments,
    'selectedjaid' => $user->jobassignmentid,
    'fullname' => fullname($user),
    'userid' => $userid,
    'sessionid' => $sessionid
);

$mform = new \mod_facetoface\form\attendee_job_assignment(null, $formparams);

if ($fromform = $mform->get_data()) {

    if (!confirm_sesskey()) {
        echo json_encode(array('result' => 'error', 'error' => get_string('confirmsesskeybad', 'error')));
        die();
    }
    if (empty($fromform->submitbutton)) {
        echo json_encode(array('result' => 'error', 'error' => get_string('error:unknownbuttonclicked', 'totara_core')));
        die();
    }

    try {
        $jobassignmentid = $fromform->selectjobassign;
        $jobassignment = \totara_job\job_assignment::get_with_id($jobassignmentid);

        $todb = new stdClass();
        $todb->id = $user->signupid;
        $todb->jobassignmentid = $jobassignment->id;
        $DB->update_record('facetoface_signups', $todb);
    } catch (Exception $e) {
        echo json_encode(array('result' => 'error', 'error' => $e->getMessage()));
        die();
    }

    $event = \mod_facetoface\event\attendee_job_assignment_updated::create(
        array(
            'objectid' => $user->signupid,
            'context' => $context,
            'other' => array(
                'sessionid'  => $session->id,
                'attendeeid' => $user->id,
            )
        )
    );
    $event->trigger();

    $label = position::job_position_label($jobassignment);

    echo json_encode(array('result' => 'success', 'id' => $userid, 'jobassignmentdisplayname' => $label));
} else {
    // This should be json_encoded, but for now we need to use html content
    // type to not break $.get().
    header('Content-type: text/html; charset=utf-8');
    echo $mform->display();
}
