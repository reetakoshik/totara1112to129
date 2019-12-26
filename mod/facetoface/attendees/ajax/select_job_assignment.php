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

$userid = required_param('id', PARAM_INT); // User ID.
$listid = required_param('listid', PARAM_ALPHANUM); // Session key to list of users to add.
$s = required_param('s', PARAM_INT); // Facetoface session ID.

list($session, $facetoface, $course, $cm, $context) = facetoface_get_env_session($s);

// Check essential permissions.
require_course_login($course, true, $cm);
// Person that want to set job assignment, must be able to add attendees to session.
require_capability('mod/facetoface:addattendees', $context);

if (empty($facetoface->selectjobassignmentonsignup)) {
    echo json_encode(array('result' => 'error', 'error' => get_string('error:jobassignementsonsignupdisabled', 'facetoface')));
    die();
}

$jobassignments = \totara_job\job_assignment::get_all($userid);

$usernamefields = get_all_user_name_fields(true, 'u');

$params = array('userid' => $userid);
$user = $DB->get_record('user', array('id' => $userid));

$list = new \mod_facetoface\bulk_list($listid);
$userlist = $list->get_user_ids();
if (empty($userlist) || !  in_array($userid, $userlist)) {
    echo json_encode(array('result' => 'error', 'error' => get_string('updateattendeesunsuccessful', 'facetoface')));
    die();
}

// Selected job assignment.
$jobassignmentid = 0;
$userdata = $list->get_user_data($userid);
if (!empty($userdata['jobassignmentid'])) {
    $jobassignmentid = $userdata['jobassignmentid'];
}

$formparams = array(
    'jobassignments' => $jobassignments,
    'selectedjaid' => $jobassignmentid,
    'fullname' => fullname($user),
    'userid' => $userid,
    'sessionid' => $session->id,
    'listid' => $listid
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

        // Store jobassignmentid in user list.
        $userdata = $list->get_user_data($userid);
        $list->set_user_data(array_merge($userdata, ['jobassignmentid' => $jobassignment->id]), $userid);
    } catch (Exception $e) {
        echo json_encode(array('result' => 'error', 'error' => $e->getMessage()));
        die();
    }

    $label = position::job_position_label($jobassignment);

    echo json_encode(array('result' => 'success', 'id' => $userid, 'jobassignmentdisplayname' => $label));
} else {
    // This should be json_encoded, but for now we need to use html content
    // type to not break $.get().
    header('Content-type: text/html; charset=utf-8');
    echo $mform->display();
}
