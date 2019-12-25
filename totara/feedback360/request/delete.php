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
 * @author David Curry <david.curry@totaralms.com>
 * @package totara
 * @subpackage totara_feedback360
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/totara/feedback360/lib.php');

require_login();
feedback360::check_feature_enabled();

$respid = required_param('respid', PARAM_INT); // The id for a resp_assignment record.
$email = optional_param('email', '', PARAM_EMAIL); // The email field to check against.

// Confirmation hash.
$delete = optional_param('del', '', PARAM_ALPHANUM);

// Set up some variables.
$strdelrequest = get_string('removerequest', 'totara_feedback360');


if (!$resp_assignment = $DB->get_record('feedback360_resp_assignment', array('id' => $respid))) {
    print_error('error:invalidparams');
}

$user_assignment = $DB->get_record('feedback360_user_assignment', array('id' => $resp_assignment->feedback360userassignmentid));

$feedback360 = $DB->get_record('feedback360', array('id' => $user_assignment->feedback360id), '*', MUST_EXIST);
if ($feedback360->anonymous) {
    print_error('error:deletefromanon', 'totara_feedback360');
}

$email_assignment = null;
if (!empty($resp_assignment->feedback360emailassignmentid)) {
    $email_assignment = $DB->get_record('feedback360_email_assignment', array('id' => $resp_assignment->feedback360emailassignmentid));

    if ($email_assignment->email != $email) {
        // There is something wrong here, these should always match.
        print_error('error:invalidparams');
    }
}

$usercontext = context_user::instance($user_assignment->userid);
$systemcontext = context_system::instance();

// Check user has permission to request feedback.
if ($USER->id == $user_assignment->userid) {
    require_capability('totara/feedback360:manageownfeedback360', $systemcontext);
} else if (\totara_job\job_assignment::is_managing($USER->id, $user_assignment->userid) || is_siteadmin()) {
    require_capability('totara/feedback360:managestafffeedback', $usercontext);
} else {
    print_error('error:accessdenied', 'totara_feedback360');
}

$returnurl = new moodle_url('/totara/feedback360/request.php',
        array('action' => 'users', 'userid' => $user_assignment->userid, 'formid' => $user_assignment->id));

// Set up the page.
$urlparams = array('respid' => $respid);
$PAGE->set_url(new moodle_url('/totara/feedback360/request/delete.php'), $urlparams);
$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('admin');
$PAGE->set_totara_menu_selected('\totara_appraisal\totara\menu\appraisal');
$PAGE->set_title($strdelrequest);
$PAGE->set_heading($strdelrequest);

if ($delete && !empty($resp_assignment)) {
    require_sesskey();

    // Delete.
    if ($delete != md5($resp_assignment->timeassigned)) {
        print_error('error:requestdeletefailure', 'totara_feedback360');
    }

    if (isset($resp_assignment->feedback360emailassignmentid)) {
        // Delete email.
        $DB->delete_records('feedback360_email_assignment', array('id' => $resp_assignment->feedback360emailassignmentid));
    }

    // Then delete the assignment.
    $DB->delete_records('feedback360_resp_assignment', array('id' => $resp_assignment->id));

    \totara_feedback360\event\request_deleted::create_from_instance($resp_assignment, $user_assignment->userid, $email)->trigger();

    totara_set_notification(get_string('feedback360requestdeleted', 'totara_feedback360'), $returnurl,
            array('class' => 'notifysuccess'));
} else {
    // Display confirmation page.
    echo $OUTPUT->header();
    $delete_params = array('respid' => $respid, 'email' => $email,
        'del' => md5($resp_assignment->timeassigned), 'sesskey' => sesskey());

    $deleteurl = new moodle_url('/totara/feedback360/request/delete.php', $delete_params);
    if (!empty($email)) {
        $username = $email;
    } else {
        $username = fullname($DB->get_record('user', array('id' => $resp_assignment->userid)));
    }

    echo $OUTPUT->confirm(get_string('removerequestconfirm', 'totara_feedback360', $username), $deleteurl, $returnurl);

    echo $OUTPUT->footer();
}
