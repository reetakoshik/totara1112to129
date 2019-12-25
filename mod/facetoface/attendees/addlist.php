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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package mod_facetoface
 */
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once($CFG->dirroot . '/mod/facetoface/attendees/forms.php');

// Face-to-face session ID
$s = required_param('s', PARAM_INT);
$listid = optional_param('listid', uniqid('f2f'), PARAM_ALPHANUM);
$currenturl = new moodle_url('/mod/facetoface/attendees/addlist.php', array('s' => $s, 'listid' => $listid));

list($session, $facetoface, $course, $cm, $context) = facetoface_get_env_session($s);

// Check capability
require_login($course, false, $cm);
require_capability('mod/facetoface:addattendees', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/facetoface/attendees/addlist.php', array('s' => $s, 'listid' => $listid)));
$PAGE->set_cm($cm);
$PAGE->set_pagelayout('standard');

$list = new \mod_facetoface\bulk_list($listid, $currenturl, 'addlist');

$mform = new facetoface_bulkadd_input_form(null, array('s' => $s, 'listid' => $listid));

$mform->set_data($list->get_form_data());
if ($mform->is_cancelled()) {
    $list->clean();
    redirect(new moodle_url('/mod/facetoface/attendees.php', array('s' => $s, 'backtoallsessions' => 1)));
}

// Check if data submitted
if ($data = $mform->get_data()) {

    // Handle data.
    $rawinput = $data->csvinput;

    // Replace commas with newlines and remove carriage returns.
    $rawinput = str_replace(array("\r\n", "\r", ","), "\n", $rawinput);

    $addusers = clean_param($rawinput, PARAM_NOTAGS);
    $addusers = explode("\n", $addusers);
    $addusers = array_map('trim', $addusers);
    $addusers = array_filter($addusers);

    // Validate list and fetch users.
    switch($data->idfield) {
        case 'idnumber':
            $field = 'idnumber';
            $errstr = 'error:idnumbernotfound';
            break;
        case 'email':
            $field = 'email';
            $errstr = 'error:emailnotfound';
            break;
        case 'username':
            $field = 'username';
            $errstr = 'error:usernamenotfound';
            break;
        default:
            print_error(get_string('error:unknownuserfield', 'facetoface'));
    }

    // Validate every user.
    $notfound = array();
    $userstoadd = array();
    $validationerrors = array();
    foreach ($addusers as $value) {
        $user = $DB->get_record('user', array($field => $value));
        if (!$user) {
            $notfound[] = $value;
            continue;
        }
        $userstoadd[] = $user->id;
        $validationerror = facetoface_validate_user_import($user, $context, $facetoface, $session, $data->ignoreconflicts);
        if (!empty($validationerror)) {
            $validationerrors[] = $validationerror;
        }
    }

    // Check for data.
    if (empty($addusers)) {
        totara_set_notification(get_string('error:nodatasupplied', 'facetoface'), null, array('class' => 'notifyproblem'));
    } else if (!empty($notfound)) {
        $notfoundlist = implode(', ', $notfound);
        totara_set_notification(get_string($errstr, 'facetoface', $notfoundlist), null, array('class' => 'notifyproblem'));
    } else if (!empty($validationerrors)) {
        $validationerrorcount = count($validationerrors);
        $validationnotification = get_string('xerrorsencounteredduringimport', 'facetoface', $validationerrorcount);
        $validationnotification .= ' '. html_writer::link('#', get_string('viewresults', 'facetoface'), array('id' => 'viewbulkresults', 'class' => 'viewbulkresults'));
        $list->set_validaton_results($validationerrors);
        totara_set_notification($validationnotification, null, array('class' => 'notifyproblem'));
    } else {
        $list->set_user_ids($userstoadd);
        $list->set_form_data($data);
        redirect(new moodle_url('/mod/facetoface/attendees/addconfirm.php', array('s' => $s, 'listid' => $listid, 'ignoreconflicts' => $data->ignoreconflicts)));
    }
}

local_js(array(TOTARA_JS_DIALOG));
$PAGE->requires->js_call_amd('mod_facetoface/attendees_addremove', 'init', array(array('s' => $s, 'listid' => $listid)));

$PAGE->set_title(format_string($facetoface->name));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('addattendeestep1', 'facetoface'));
echo facetoface_print_session($session, false, false, true, true);

$mform->display();

echo $OUTPUT->footer();