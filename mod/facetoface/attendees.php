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
 * @author Francois Marier <francois@catalyst.net.nz>
 * @author Aaron Barnes <aaronb@catalyst.net.nz>
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package totara
 * @subpackage facetoface
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');
require_once($CFG->libdir.'/totaratablelib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');

/**
 * Load and validate base data
 */
// Face-to-face session ID
$s = optional_param('s', 0, PARAM_INT);
// Take attendance
$takeattendance    = optional_param('takeattendance', false, PARAM_BOOL);
// Cancel request
$cancelform        = optional_param('cancelform', false, PARAM_BOOL);
// Action being performed, a proper default will be set shortly.
$action            = optional_param('action', '', PARAM_ALPHA);
// Only return content
$onlycontent        = optional_param('onlycontent', false, PARAM_BOOL);
// Export download.
$download = optional_param('download', '', PARAM_ALPHA);
// If approval requests have been updated, show a success message.
$approved = optional_param('approved', 0, PARAM_INT);

$backtoallsessions = optional_param('backtoallsessions', 1, PARAM_BOOL);

// Report support.
$format = optional_param('format','',PARAM_TEXT);
$sid = optional_param('sid', '0', PARAM_INT);
$debug = optional_param('debug', 0, PARAM_INT);

// If there's no sessionid specified.
if (!$s) {
    $syscontext = context_system::instance();
    require_login();

    $syscontext = context_system::instance();
    if (!has_capability('mod/facetoface:viewallsessions', $syscontext)) {
        // They can't view the sessionreport, essentially this makes s a required param.
        // As its not been set, throw the same error required_param would.
        print_error('missingparam', '', '', 's');
    }

    $PAGE->set_context($syscontext);
    $PAGE->set_url('/mod/facetoface/attendees.php');

    echo $OUTPUT->header();
    $url = new moodle_url('/mod/facetoface/eventreport.php');
    echo $OUTPUT->heading(get_string('selectaneventheading', 'rb_source_facetoface_sessions'));
    echo html_writer::tag('p', html_writer::link($url, get_string('selectanevent', 'rb_source_facetoface_sessions')));
    echo $OUTPUT->footer();
    exit;
}

list($session, $facetoface, $course, $cm, $context) = facetoface_get_env_session($s);

if ($action == 'approvalrequired') {
    // Allow managers to be able to approve staff without being enrolled in the course.
    require_login();
} else {
    require_login($course, false, $cm);
}

if ($action === '') {
    // We're performing the default action.
    if (!empty($session->cancelledstatus)) {
        // The session is cancelled, default action is to show cancellations.
        $action = 'cancellations';
    } else {
        // The default is to show attendees.
        $action = 'attendees';
    }
}

// Setup urls
$baseurl = new moodle_url('/mod/facetoface/attendees.php', array('s' => $session->id));

/**
 * Capability checks to see if the current user can view this page
 *
 * This page is a bit of a special case in this respect as there are four uses for this page.
 *
 * 1) Viewing attendee list
 *   - Requires mod/facetoface:viewattendees capability in the course
 *
 * 2) Viewing cancellation list
 *   - Requires mod/facetoface:viewcancellations capability in the course
 *
 * 3) Taking attendance
 *   - Requires mod/facetoface:takeattendance capabilities in the course
 *
 * 4) A manager approving his staff's booking requests
 *   - Manager does not neccesarily have any capabilities in this course
 *   - Show only attendees who are also the manager's staff
 *   - Show only staff awaiting approval
 *   - Show any staff who have cancelled
 *   - Shouldn't throw an error if there are previously declined attendees
 *
 * 5) A user with the specified role in the session to approve the pending requests
 *  - The user with the role does not neccesarily have any capabilities in this course
 *  - Show all users with pending requests for the session
 *  - Do not show any other tabs
 *
 * 6) A sitewide or actitivity level Approver
 *  - The approver does not neccesarily have any capabilities in this course
 *  - Show all users with pending requests for the session
 *  - Do not show any other tabs
 */
// Allowed actions are actions the user has permissions to do
$allowed_actions = array();
// Available actions are actions that have a point. e.g. view the cancellations page whhen there are no cancellations is not an "available" action, but it maybe be an "allowed" action
$available_actions = array();

$PAGE->set_context($context);
$PAGE->set_url('/mod/facetoface/attendees.php', array('s' => $s));

// Actions the user can perform
$has_attendees = facetoface_get_num_attendees($s);

if (has_capability('mod/facetoface:viewattendees', $context)) {
    $allowed_actions[] = 'attendees';
    $allowed_actions[] = 'waitlist';
    $allowed_actions[] = 'addattendees';

    if (empty($session->cancelledstatus)) {
        $available_actions[] = 'attendees';
    }

    if (facetoface_get_users_by_status($s, MDL_F2F_STATUS_WAITLISTED)) {
        $available_actions[] = 'waitlist';
    }
}

if (has_capability('mod/facetoface:viewcancellations', $context)) {
    $allowed_actions[] = 'cancellations';

    if (!empty($session->cancelledstatus) || facetoface_get_users_by_status($s, MDL_F2F_STATUS_USER_CANCELLED)) {
        $available_actions[] = 'cancellations';
    }
}

if (has_capability('mod/facetoface:takeattendance', $context)) {
    $allowed_actions[] = 'takeattendance';
    $allowed_actions[] = 'messageusers';

    if ($has_attendees && $session->mintimestart && facetoface_has_session_started($session, time())) {
        $available_actions[] = 'takeattendance';
    }

    if (in_array('attendees', $available_actions) || in_array('cancellations', $available_actions) || in_array('waitlist', $available_actions)) {
        $available_actions[] = 'messageusers';
    }
}

$includeattendeesnote = (has_any_capability(array('mod/facetoface:viewattendeesnote', 'mod/facetoface:manageattendeesnote'), $context));

$attendees = array();
$cancellations = array();
$requests = array();

$staff = null;
if ($facetoface->approvaltype == APPROVAL_MANAGER || $facetoface->approvaltype == APPROVAL_ADMIN) {
    $managersql = "1=0";
    $sqlparams = array();

    // Use job assignment API: This can fail with large amount of users managed by current user.
    $staffids = \totara_job\job_assignment::get_staff_userids($USER->id);
    if (!empty($staffids)) {
        list($staffsql, $sqlparams) = $DB->get_in_or_equal($staffids, SQL_PARAMS_NAMED);
        $managersql = "fs.userid $staffsql";
    }

    $selectjobassignmentsignupglobal = get_config(null, 'facetoface_selectjobassignmentonsignupglobal');
    if (!empty($selectjobassignmentsignupglobal) && !empty($facetoface->selectjobassignmentonsignup)) {
        // Prioritise selecteded job assignment
        $managersql = "(selectedmanagerja.userid = :selectedmanid OR (selectedmanagerja.userid IS NULL AND $managersql))";
        $sqlparams['selectedmanid'] = $USER->id;

        if (!empty($CFG->enabletempmanagers)) {
            $managersql = "(selectedtempmanagerja.userid = :selectedtempmanid
                            OR (selectedtempmanagerja.userid IS NULL AND $managersql))";
            $sqlparams['selectedtempmanid'] = $USER->id;
        }
    }

    $managerselect = get_config(null, 'facetoface_managerselect');
    if ($managerselect) {
        // Prioritise selected manager.
        $managersql = "((fs.managerid = :manid) OR (fs.managerid IS NULL AND $managersql))";
        $sqlparams['manid'] = $USER->id;
    }

    // Check if the user is manager of a job assignment selected by staff signed up to this session.
    $requestssql = "SELECT DISTINCT fs.userid
                      FROM {facetoface_signups} fs
                      JOIN {facetoface_signups_status} fss
                        ON (fss.signupid = fs.id AND fss.superceded = 0)
                      LEFT JOIN {job_assignment} selectedja
                        ON fs.jobassignmentid = selectedja.id
                      LEFT JOIN {job_assignment} selectedmanagerja
                        ON selectedmanagerja.id = selectedja.managerjaid
                      LEFT JOIN {job_assignment} selectedtempmanagerja
                        ON (selectedtempmanagerja.id = selectedja.tempmanagerjaid AND selectedja.tempmanagerexpirydate > :now)
                     WHERE fs.sessionid = :sessionid
                       AND {$managersql}
                       AND fss.statuscode = :status";
    $sqlparams = array_merge($sqlparams, array('sessionid' => $session->id, 'status' => MDL_F2F_STATUS_REQUESTED ,'now' => time()));
    $staff = $DB->get_fieldset_sql($requestssql, $sqlparams);
}

if ($facetoface->approvaltype == APPROVAL_ROLE) {
    $sessionroles = facetoface_get_trainers($session->id, $facetoface->approvalrole);
    if (!empty($sessionroles)) {
        foreach ($sessionroles as $user) {
            if ($user->id == $USER->id) {
                // The current user is one of the role approvers.
                $allowed_actions[] = 'approvalrequired';
                $available_actions[] = 'approvalrequired';
                // Set everyone as their staff.
                $staff = array_keys(facetoface_get_requests($session->id));
                break;
            }
        }
    }
}

$admin_requests = array();
if ($facetoface->approvaltype == APPROVAL_ADMIN) {
    if (facetoface_is_adminapprover($USER->id, $facetoface)) {
        // The current user is one of the admin approvers.
        $allowed_actions[] = 'approvalrequired';
        $available_actions[] = 'approvalrequired';
        // Set everyone in the second step as their staff.
        $requestssql = "SELECT fs.userid
                          FROM {facetoface_signups} fs
                          JOIN {facetoface_signups_status} fss
                            ON fss.signupid = fs.id AND fss.superceded = 0
                         WHERE fs.sessionid = :sessionid
                           AND (fss.statuscode = :statusadm OR fss.statuscode = :statusman)";
        $params = array('sessionid' => $session->id, 'statusadm' => MDL_F2F_STATUS_REQUESTEDADMIN,
                'statusman' => MDL_F2F_STATUS_REQUESTED);
        $adminreqs = $DB->get_fieldset_sql($requestssql, $params);
        if (isset($staff)) {
            $staff = array_merge($staff, $adminreqs); // Display both just in case they are managers & approvers.
        }
        $staff = $adminreqs;
    }
}

$canapproveanyrequest = has_capability('mod/facetoface:approveanyrequest', $context);
if ($canapproveanyrequest || !empty($staff)) {
    // Check if any staff have requests awaiting approval.
    $get_requests = $facetoface->approvaltype == APPROVAL_ADMIN ? facetoface_get_adminrequests($session->id) : facetoface_get_requests($session->id);

    if ($get_requests || !empty($admin_requests)) {
        // Calculate which requesting users are relevant to the viewer.
        $requests = ($canapproveanyrequest ? $get_requests : array_intersect_key($get_requests, array_flip($staff)));
        if ($requests) {
            $allowed_actions[] = 'approvalrequired';
            $available_actions[] = 'approvalrequired';
        }
    }
}

// Check if we are NOT already showing attendees and the user has staff.
// If this is true then we need to show attendees but limit it to just those attendees that are also staff.
if (!in_array('attendees', $allowed_actions) && !empty($staff)) {
    // Check if any staff are attending.
    if ($session->mintimestart) {
        $get_attendees = facetoface_get_attendees($session->id, array(MDL_F2F_STATUS_BOOKED, MDL_F2F_STATUS_NO_SHOW,
            MDL_F2F_STATUS_PARTIALLY_ATTENDED, MDL_F2F_STATUS_FULLY_ATTENDED));
    } else {
        $get_attendees = facetoface_get_attendees($session->id, array(MDL_F2F_STATUS_WAITLISTED, MDL_F2F_STATUS_BOOKED, MDL_F2F_STATUS_NO_SHOW,
            MDL_F2F_STATUS_PARTIALLY_ATTENDED, MDL_F2F_STATUS_FULLY_ATTENDED));
    }
    if ($get_attendees) {
        // Calculate which attendees are relevant to the viewer.
        $attendees = array_intersect_key($get_attendees, array_flip($staff));

        if ($attendees) {
            $allowed_actions[] = 'attendees';
            $available_actions[] = 'attendees';
        }
    }
}

// Check if we are NOT already showing cancellations and the user has has staff.
// If this is true then we still need to show cancellations but limit it to just those cancellations that are also staff.
if (!in_array('cancellations', $allowed_actions) && !empty($staff)) {
    // Check if any staff have cancelled.
    $get_cancellations = facetoface_get_cancellations($session->id);
    if ($get_cancellations) {
        // Calculate which cancelled users are relevant to the viewer.
        $cancellations = array_intersect_key($get_cancellations, array_flip($staff));

        if ($cancellations) {
            $allowed_actions[] = 'cancellations';
            $available_actions[] = 'cancellations';
        }
    }
}

$goback = true;
$can_view_session = !empty($allowed_actions);
if (!$can_view_session) {
    // If no allowed actions so far, check if this was user/manager who has just approved staff requests (approved == 1).
    if ($action == 'approvalrequired' && $approved == '1') {
        // If so, do not redirect, just display notify message.
        // Hide "Go back" link for case user does not have any capabilities to see facetoface/course.
        $goback = false;
    } else {
        $return = new moodle_url('/mod/facetoface/view.php', array('f' => $facetoface->id));
        redirect($return);
        die();
    }
}
// $allowed_actions is already set, so we can now know if the current action is allowed.
$actionallowed = in_array($action, $allowed_actions);

/***************************************************************************
 * Handle actions
 */
$show_table = false;
$heading_message = '';
$params = array('sessionid' => $s);
$cols = array();
$actions = array();
$exports = array();

if ($action == 'attendees' && $actionallowed) {
    $heading = get_string('attendees', 'facetoface');

    // Check if any dates are set
    if (!$session->mintimestart) {
        $heading_message = get_string('sessionnoattendeesaswaitlist', 'facetoface');
    }

    // Get list of actions
    if (empty($session->cancelledstatus)) {
        if (in_array('addattendees', $allowed_actions)) {
            $actions['add']    = get_string('addattendees', 'facetoface');
            $actions['bulkaddfile']  = get_string('addattendeesviafileupload', 'facetoface');
            $actions['bulkaddinput'] = get_string('addattendeesviaidlist', 'facetoface');
            if (has_capability('mod/facetoface:removeattendees', $context)) {
                $actions['remove'] = get_string('removeattendees', 'facetoface');
            }
        }
    }

    // Verify global restrictions and process report early before any output is done (required for export).
    $shortname = 'facetoface_sessions';
    $reportrecord = $DB->get_record('report_builder', array('shortname' => $shortname));
    $globalrestrictionset = rb_global_restriction_set::create_from_page_parameters($reportrecord);

    $attendancestatuses = array(MDL_F2F_STATUS_BOOKED, MDL_F2F_STATUS_FULLY_ATTENDED, MDL_F2F_STATUS_NOT_SET,
        MDL_F2F_STATUS_NO_SHOW, MDL_F2F_STATUS_PARTIALLY_ATTENDED);
    if (!$report = reportbuilder_get_embedded_report($shortname, array('sessionid' => $s, 'status' => $attendancestatuses),
            false, $sid, $globalrestrictionset)) {
        print_error('error:couldnotgenerateembeddedreport', 'totara_reportbuilder');
    }

    if ($format != '') {
        $report->export_data($format);
        die;
    }

    $report->include_js();
    $PAGE->set_button($report->edit_button());

    // We will show embedded report.
    $show_table = true;
}

if ($action == 'waitlist' && $actionallowed) {
    $heading = get_string('wait-list', 'facetoface');

    $params['status'] = MDL_F2F_STATUS_WAITLISTED;
    $cols = array(
        array('user', 'namelink'),
        array('user', 'email'),
    );

    $lotteryenabled = get_config(null, 'facetoface_lotteryenabled');

    $actions['confirmattendees'] = get_string('confirmattendees', 'facetoface');
    $actions['cancelattendees'] = get_string('cancelattendees', 'facetoface');
    if ($lotteryenabled) {
        $actions['playlottery'] = get_string('playlottery', 'facetoface');
    }

    $show_table = true;
}

if ($action == 'cancellations' && $actionallowed) {
    $heading = get_string('cancellations', 'facetoface');

    // Get list of actions
    $exports = array(
        'exportxls'     => get_string('exportxls', 'totara_reportbuilder'),
        'exportods'     => get_string('exportods', 'totara_reportbuilder'),
        'exportcsv'     => get_string('exportcsv', 'totara_reportbuilder')
    );

    $params['status'] = MDL_F2F_STATUS_USER_CANCELLED;
    $cols = array(
        array('user', 'idnumber'),
        array('user', 'namelink'),
        array('session', 'cancellationdate'),
        array('session', 'cancellationtype'),
        array('session', 'cancellationreason'),
    );

    $show_table = true;
}

if ($action == 'takeattendance' && $actionallowed) {
    $heading = get_string('takeattendance', 'facetoface');

    // Get list of actions
    $exports = array(
        'exportxls'                 => get_string('exportxls', 'totara_reportbuilder'),
        'exportods'                 => get_string('exportods', 'totara_reportbuilder'),
        'exportcsv'                 => get_string('exportcsv', 'totara_reportbuilder')
    );

    $params['statusgte'] = MDL_F2F_STATUS_BOOKED;
    $cols = array(
        array('status', 'select'),
        array('user', 'namelink'),
        array('status', 'set'),
    );

    $show_table = true;
}

/**
 * Handle submitted data
 */
if ($form = data_submitted()) {
    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad', 'error');
    }

    $return = new moodle_url('/mod/facetoface/attendees.php', array('s' => $s));

    if ($cancelform) {
        redirect($return);
        die();
    }

    // Approve requests
    if ($action == 'approvalrequired' && !empty($form->requests) && $actionallowed) {
        $return->params(array('action' => 'approvalrequired'));
        // Site admin is allowing to approve user request.
        if (!$canapproveanyrequest) {
            // Leave the users which are required to approve and remove the rest.
            $form->requests = array_intersect_key($form->requests, array_flip($staff));
        }
        if (($result = facetoface_approve_requests($form)) === true) {
            $return->params(array('approved' => '1'));
        } else {
            $output = html_writer::start_tag('ul');
            foreach ($result as $attendeeid => $errmsg) {
                $output .= html_writer::tag('li', get_string('error:'.$errmsg, 'facetoface', $attendeeid));
            }
            $output .= html_writer::end_tag('ul');
            totara_set_notification($output);
        }
        redirect($return);
        die();
    }

    // Take attendance.
    if ($action == 'takeattendance' && $actionallowed && $takeattendance) {
        if (facetoface_take_attendance($form)) {
            // Trigger take attendance update event.
            \mod_facetoface\event\attendance_updated::create_from_session($session, $context)->trigger();
            totara_set_notification(get_string('updateattendeessuccessful', 'facetoface'), $return,
                    array('class' => 'notifysuccess'));
        }
        totara_set_notification(get_string('error:takeattendance', 'facetoface'), $return, array('class' => 'notifyproblem'));
    }

    // Send messages
    if ($action == 'messageusers' && $actionallowed) {
        $formurl = clone($baseurl);
        $formurl->param('action', 'messageusers');

        $mform = new \mod_facetoface\form\attendees_message($formurl, array('s' => $s));

        // Check form validates
        if ($mform->is_cancelled()) {
            redirect($baseurl);
        } else if ($data = $mform->get_data()) {
            // Get recipients list
            $recipients = array();
            if (!empty($data->recipient_group)) {
                foreach ($data->recipient_group as $key => $value) {
                    if (!$value) {
                        continue;
                    }
                    $recipients = $recipients + facetoface_get_users_by_status($s, $key, 'u.id, u.*, su.jobassignmentid');
                }
            }

            // Get indivdual recipients
            if (empty($recipients) && !empty($data->recipients_selected)) {
                // Strip , prefix
                $data->recipients_selected = substr($data->recipients_selected, 1);
                $recipients = explode(',', $data->recipients_selected);
                list($insql, $params) = $DB->get_in_or_equal($recipients);
                $recipients = $DB->get_records_sql('SELECT * FROM {user} WHERE id ' . $insql, $params);
                if (!$recipients) {
                    $recipients = array();
                }
            }

            // Send messages.
            $facetofaceuser = \mod_facetoface\facetoface_user::get_facetoface_user();

            $emailcount = 0;
            $emailerrors = 0;
            foreach ($recipients as $recipient) {
                $body = $data->body['text'];
                $bodyplain = html_to_text($body);

                if (email_to_user($recipient, $facetofaceuser, $data->subject, $bodyplain, $body) === true) {
                    $emailcount += 1;

                    // Are sending to managers
                    if (empty($data->cc_managers)) {
                        continue;
                    }

                    // User have a manager assigned for the job assignment they signedup with (or all managers otherwise).
                    $managers = array();
                    if (!empty($recipient->jobassignmentid)) {
                        $ja = \totara_job\job_assignment::get_with_id($recipient->jobassignmentid);
                        if (!empty($ja->managerid)) {
                            $managers[] = $ja->managerid;
                        }
                    } else {
                        $managers = \totara_job\job_assignment::get_all_manager_userids($recipient->id);
                    }
                    if (!empty($managers)) {
                        // Append to message.
                        $body = get_string('messagesenttostaffmember', 'facetoface', fullname($recipient))."\n\n".$data->body['text'];
                        $bodyplain = html_to_text($body);

                        foreach ($managers as $managerid) {
                            $manager = \core_user::get_user($managerid, '*', MUST_EXIST);
                            if (email_to_user($manager, $facetofaceuser, $data->subject, $bodyplain, $body) === true) {
                                $emailcount += 1;
                            }
                        }
                    }
                } else {
                    $emailerrors += 1;
                }
            }

            if ($emailcount) {
                if (!empty($data->cc_managers)) {
                    $message = get_string('xmessagessenttoattendeesandmanagers', 'facetoface', $emailcount);
                } else {
                    $message = get_string('xmessagessenttoattendees', 'facetoface', $emailcount);
                }

                totara_set_notification($message, $return, array('class' => 'notifysuccess'));
            }

            if ($emailerrors) {
                $message = get_string('xmessagesfailed', 'facetoface', $emailerrors);
                totara_set_notification($message);
            }

            redirect($return);
            die();
        }
    }
}


/**
 * Print page header
 */
if (!$onlycontent) {
    local_js(
        array(
            TOTARA_JS_DIALOG,
            TOTARA_JS_TREEVIEW
        )
    );

    $PAGE->requires->string_for_js('save', 'admin');
    $PAGE->requires->string_for_js('cancel', 'moodle');
    $PAGE->requires->strings_for_js(
        array('uploadfile', 'addremoveattendees', 'approvalreqd', 'areyousureconfirmwaitlist',
            'addattendeesviaidlist', 'submitcsvtext', 'bulkaddattendeesresults', 'addattendeesviafileupload',
            'bulkaddattendeesresults', 'wait-list', 'cancellations', 'approvalreqd', 'takeattendance',
            'updateattendeessuccessful', 'updateattendeesunsuccessful', 'waitlistselectoneormoreusers',
            'confirmlotteryheader', 'confirmlotterybody', 'updatewaitlist', 'close'),
        'facetoface'
    );

    $json_action = json_encode($action);
    $args = array('args' => '{"sessionid":'.$session->id.','.
        '"action":'.$json_action.','.
        '"sesskey":"'.sesskey().'",'.
        '"approvalreqd":"'.facetoface_approval_required($facetoface).'"}');

    $jsmodule = array(
        'name' => 'totara_f2f_attendees',
        'fullpath' => '/mod/facetoface/js/attendees.js',
        'requires' => array('json', 'totara_core'));

    if ($action == 'messageusers') {
        $PAGE->requires->strings_for_js(array('editmessagerecipientsindividually', 'existingrecipients', 'potentialrecipients'), 'facetoface');
        $PAGE->requires->string_for_js('update', 'moodle');

        $jsmodule = array(
            'name' => 'totara_f2f_attendees_message',
            'fullpath' => '/mod/facetoface/js/attendees_messaging.js',
            'requires' => array('json', 'totara_core'));

        $PAGE->requires->js_init_call('M.totara_f2f_attendees_messaging.init', $args, false, $jsmodule);
    } else {
        $jsmodule = array(
            'name' => 'totara_f2f_attendees',
            'fullpath' => '/mod/facetoface/js/attendees.js',
            'requires' => array('json', 'totara_core'));

        $args = array('args' => '{"sessionid":'.$session->id.','.
            '"action":'.$json_action.','.
            '"sesskey":"'.sesskey().'",'.
            '"selectall":'.MDL_F2F_SELECT_ALL.','.
            '"selectnone":'.MDL_F2F_SELECT_NONE.','.
            '"selectset":"'.MDL_F2F_SELECT_SET.'",'.
            '"selectnotset":"'.MDL_F2F_SELECT_NOT_SET.'",'.
            '"courseid":"'.$course->id.'",'.
            '"facetofaceid":"'.$facetoface->id.'",'.
            '"notsetop":"'.MDL_F2F_STATUS_NOT_SET.'",'.
            '"approvalreqd":"'.facetoface_approval_required($facetoface).'"}');

        $PAGE->requires->js_init_call('M.totara_f2f_attendees.init', $args, false, $jsmodule);
    }

    \mod_facetoface\event\attendees_viewed::create_from_session($session, $context, $action)->trigger();

    $pagetitle = format_string($facetoface->name);

    $PAGE->set_url('/mod/facetoface/attendees.php', array('s' => $s));
    $PAGE->set_cm($cm);
    $PAGE->set_pagelayout('standard');

    $PAGE->set_title($pagetitle);
    $PAGE->set_heading($course->fullname);

    echo $OUTPUT->header();
}

/**
 * Print page content
 */

if (!$onlycontent && !$download) {
    echo $OUTPUT->box_start();
    echo $OUTPUT->heading(format_string($facetoface->name));

    if ($can_view_session) {
        echo facetoface_print_session($session, true, false, true, true);

        // Print customfields.
        $customfields = customfield_get_data($session, 'facetoface_sessioncancel', 'facetofacesessioncancel');

        if (!empty($customfields)) {

            $output = html_writer::start_tag('dl', array('class' => 'f2f'));

            foreach ($customfields as $cftitle => $cfvalue) {
                $output .= html_writer::tag('dt', str_replace(' ', '&nbsp;', $cftitle));
                $output .= html_writer::tag('dd', $cfvalue);
            }

            $output .= html_writer::end_tag('dl');

            echo $output;
        }
    }

    include('attendee_tabs.php'); // If needed include tabs

    echo $OUTPUT->container_start('f2f-attendees-table');
}

/**
 * Print attendees (if user able to view)
 */
$pix = new pix_icon('t/edit', get_string('edit'));
if ($show_table) {
    // Get list of attendees

    switch ($action) {
        case 'cancellations':
            if ($cancellations) {
                $rows = $cancellations;
            } else {
                if (empty($session->cancelledstatus)) {
                    $rows = facetoface_get_cancellations($session->id);
                } else {
                    $rows = facetoface_get_attendees($session->id, array(
                        MDL_F2F_STATUS_BOOKED,
                        MDL_F2F_STATUS_NO_SHOW,
                        MDL_F2F_STATUS_PARTIALLY_ATTENDED,
                        MDL_F2F_STATUS_FULLY_ATTENDED,
                        MDL_F2F_STATUS_USER_CANCELLED,
                        MDL_F2F_STATUS_SESSION_CANCELLED
                    ));
                }
            }
            break;

        case 'waitlist':
            $rows = facetoface_get_attendees($session->id, array(MDL_F2F_STATUS_WAITLISTED));
            break;

        case 'takeattendance':
            $rows = facetoface_get_attendees($session->id, array(MDL_F2F_STATUS_BOOKED, MDL_F2F_STATUS_NO_SHOW,
                MDL_F2F_STATUS_PARTIALLY_ATTENDED, MDL_F2F_STATUS_FULLY_ATTENDED));
            break;
    }

    if (!$download) {
        //output any notifications
        if (isset($result_message)) {
            echo $result_message;
        } else {
            $numattendees = facetoface_get_num_attendees($session->id);
            $overbooked = ($numattendees > $session->capacity);
            if (($action == 'attendees') && $overbooked) {
                $overbookedmessage = get_string('capacityoverbookedlong', 'facetoface', array('current' => $numattendees, 'maximum' => $session->capacity));
                echo $OUTPUT->notification($overbookedmessage, 'notifynotice');
            }
        }

        //output the section heading
        echo $OUTPUT->heading($heading);
    }

    if ($action == 'attendees') {
        $report->display_restrictions();
    }

    // Actions menu.
    if (has_any_capability(array('mod/facetoface:addattendees', 'mod/facetoface:removeattendees'), $context)) {
        if ($actions) {
            echo $OUTPUT->container_start('actions last');
            // Action selector
            echo html_writer::label(get_string('attendeeactions', 'mod_facetoface'), 'menuf2f-actions', true, array('class' => 'sr-only'));
            echo html_writer::select($actions, 'f2f-actions', '', array('' => get_string('actions')));
            if ($action == 'waitlist') {
                echo $OUTPUT->help_icon('f2f-waitlist-actions', 'mod_facetoface');
            }
            echo $OUTPUT->container_end();
        }
    }

    if ($action == 'attendees') {

        /** @var totara_reportbuilder_renderer $output */
        $output = $PAGE->get_renderer('totara_reportbuilder');
        // This must be done after the header and before any other use of the report.
        list($reporthtml, $debughtml) = $output->report_html($report, $debug);
        echo $debughtml;

        $report->display_search();
        $report->display_sidebar_search();

        // Print saved search buttons if appropriate.
        echo $report->display_saved_search_options();

        echo $reporthtml;
        $output->export_select($report, $sid);

    } else if (empty($rows)) {
        if (facetoface_approval_required($facetoface)) {
            if (count($requests) == 1) {
                echo $OUTPUT->notification(get_string('nosignedupusersonerequest', 'facetoface'));
            } else {
                echo $OUTPUT->notification(get_string('nosignedupusersnumrequests', 'facetoface', count($requests)));
            }
        } else if ($action == 'cancellations') {
            echo $OUTPUT->notification(get_string('nocancellations', 'facetoface'));
        } else {
            echo $OUTPUT->notification(get_string('nosignedupusers', 'facetoface'));
        }
    } else {
        if (($action == 'takeattendance') && $actionallowed && !$download) {

            $attendees_url = new moodle_url('attendees.php', array('s' => $s, 'takeattendance' => '1', 'action' => 'takeattendance'));
            echo html_writer::start_tag('form', array('action' => $attendees_url, 'method' => 'post', 'id' => 'attendanceform'));
            echo html_writer::tag('p', get_string('attendanceinstructions', 'facetoface'));
            echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => $USER->sesskey));
            echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 's', 'value' => $s));

            // Prepare status options array.
            $statusoptions = facetoface_get_attendance_status();
        }

        if (!$download) {
            echo html_writer::tag('div', '', array('class' => 'hide', 'id' => 'noticeupdate'));
        }

        $table = new totara_table('facetoface-attendees');
        $baseurl = new moodle_url('/mod/facetoface/attendees.php', array('s' => $session->id, 'sesskey' => sesskey(), 'onlycontent' => true));
        if ($action) {
            $baseurl->param('action', $action);
        }
        $table->define_baseurl($baseurl);
        $table->set_attribute('class', 'generalbox mod-facetoface-attendees '.$action);

        $exportfilename = isset($action) ? $action : 'attendees';

        $headers = array();
        $columns = array();
        $export_rows = array();

        $headers[] = get_string('name');
        $columns[] = 'name';
        $headers[] = get_string('timesignedup', 'facetoface');
        $columns[] = 'timesignedup';

        $hidecost = get_config(null, 'facetoface_hidecost');
        $hidediscount = get_config(NULL, 'facetoface_hidediscount');
        $selectjobassignmentonsignupglobal = get_config(null, 'facetoface_selectjobassignmentonsignupglobal');

        $showjobassignments = !empty($selectjobassignmentonsignupglobal) && !empty($facetoface->selectjobassignmentonsignup);
        if ($showjobassignments) {
            $headers[] = get_string('selectedjobassignment', 'mod_facetoface');
            $columns[] = 'jobassignment';
        }

        if ($action == 'takeattendance' && $actionallowed && !$download) {
            $chooseoption = get_string('select','facetoface');
            $selectlist = html_writer::select($F2F_SELECT_OPTIONS, 'bulk_select', '', false);
            array_unshift($headers, $chooseoption . $selectlist);
            array_unshift($columns, 'selectedusers');
            $headers[] = get_string('currentstatus', 'facetoface');
            $columns[] = 'currentstatus';
        } else if ($action == 'cancellations') {
            $headers[] = get_string('timecancelled', 'facetoface');
            $columns[] = 'timecancelled';
            $headers[] = get_string('canceltype', 'facetoface');
            $columns[] = 'cancellationtype';
            $headers[] = get_string('cancelreason', 'facetoface');
            $columns[] = 'cancellationreason';
        } else {
            // Additional approval columns for the attendees tab.
            if ($facetoface->approvaltype == APPROVAL_ROLE) {
                $rolenames = role_fix_names(get_all_roles());
                $headers[] = get_string('approverrolename', 'mod_facetoface');
                $columns[] = 'approverrolename';
            }

            if ($facetoface->approvaltype > APPROVAL_SELF) {
                // Display approval columns for anything except none and self approval.
                $headers[] = get_string('approvername', 'mod_facetoface');
                $columns[] = 'approvername';
                $headers[] = get_string('approvaltime', 'mod_facetoface');
                $columns[] = 'approvaltime';
            }

            if (!$hidecost) {
                $headers[] = get_string('cost', 'facetoface');
                $columns[] = 'cost';
                if (!$hidediscount) {
                    $headers[] = get_string('discountcode', 'facetoface');
                    $columns[] = 'discountcode';
                }
            }

            $headers[] = get_string('attendance', 'facetoface');
            $columns[] = 'attendance';

            if ($action != 'takeattendance') {
                if ($includeattendeesnote) {

                    $headers[] = get_string('attendeenote', 'facetoface');
                    $columns[] = 'usernote';
                }
            }

            if ($action == 'waitlist' && !$download) {
                $headers[] = html_writer::tag('a', get_string('all'), array('href' => '#', 'class' => 'selectall'))
                            . '/'
                            . html_writer::tag('a', get_string('none'), array('href' => '#', 'class' => 'selectnone'));
                $columns[] = 'actions';
            }

        }
        if (!$download) {
            $table->define_columns($columns);
            $table->define_headers($headers);
            $table->setup();
            if ($action == 'takeattendance' && $actionallowed) {
                $table->add_toolbar_content(facetoface_display_bulk_actions_picker(), 'left' , 'top', 1);
            }
        }
        $cancancelreservations = has_capability('mod/facetoface:reserveother', $context);
        $canchangesignedupjobassignment = has_capability('mod/facetoface:changesignedupjobassignment', $context);

        foreach ($rows as $attendee) {
            $data = array();
            // Add the name of the manager who made the booking after the user's name.
            $managername = null;
            if (!empty($attendee->bookedby)) {
                $managerurl = new moodle_url('/user/view.php', array('id' => $attendee->bookedby));
                $manager = (object)array('firstname' => $attendee->bookedbyfirstname, 'lastname' => $attendee->bookedbylastname);
                $managername = fullname($manager);
                if (!$download) {
                    $managername = html_writer::link($managerurl, $managername);
                }
            }
            if ($attendee->id) {
                $attendeename = fullname($attendee);
                if (!$download) {
                    $attendeeurl = new moodle_url('/user/view.php', array('id' => $attendee->id, 'course' => $course->id));
                    $attendeename = html_writer::link($attendeeurl, $attendeename);
                }
                if ($managername) {
                    $strinfo = (object)array('attendeename' => $attendeename, 'managername' => $managername);
                    $attendeename = get_string('namewithmanager', 'mod_facetoface', $strinfo);
                }
                $data[] = $attendeename;
            } else {
                // Reserved space - display 'Reserved' + the name of the person who booked it.
                $cancelicon = '';
                if (!$download && $attendee->bookedby) {
                    if ($cancancelreservations) {
                        $params = array(
                            's' => $session->id,
                            'managerid' => $attendee->bookedby,
                            'action' => 'reserve',
                            'backtosession' => $action,
                            'cancelreservation' => 1,
                            'sesskey' => sesskey(),
                        );
                        $cancelurl = new moodle_url('/mod/facetoface/reserve.php', $params);
                        $cancelicon = $OUTPUT->pix_icon('t/delete', get_string('cancelreservation', 'mod_facetoface'));
                        $cancelicon = ' '.html_writer::link($cancelurl, $cancelicon);
                    }
                }
                if ($managername) {
                    $reserved = get_string('reservedby', 'mod_facetoface', $managername);
                } else {
                    $reserved = get_string('reserved', 'mod_facetoface');
                }
                $data[] = $reserved.$cancelicon;
            }

            // If event was cancelled before attendance was approved by a manager, then timesignedup may be empty.
            if (empty($attendee->timesignedup)) {
                $data[] = '';
            } else {
                $data[] = userdate($attendee->timesignedup, get_string('strftimedatetime'));
            }

            if ($showjobassignments) {
                if (!empty($attendee->jobassignmentid)) {
                    $jobassignment = \totara_job\job_assignment::get_with_id($attendee->jobassignmentid);
                    $label = position::job_position_label($jobassignment);
                } else {
                    $label = '';
                }

                if (!$download) {
                    $url = new moodle_url('/mod/facetoface/attendee_job_assignment.php', array('s' => $session->id, 'id' => $attendee->id));
                    $icon = $OUTPUT->action_icon($url, $pix, null, array('class' => 'action-icon attendee-edit-job-assignment pull-right'));
                    $jobassign = html_writer::span($label, 'jobassign' . $attendee->id, array('id' => 'jobassign' . $attendee->id));

                    if ($canchangesignedupjobassignment) {
                        $data[] = $icon . $jobassign;
                    } else {
                        $data[] = $jobassign;
                    }
                } else {
                    $data[] = $label;
                }
            }

            if ($action == 'takeattendance' && $actionallowed) {
                $optionid = 'submissionid_' . $attendee->submissionid;
                $checkoptionid = 'check_submissionid_' . $attendee->submissionid;

                // Show current status.
                if ($attendee->statuscode == MDL_F2F_STATUS_BOOKED) {
                    $attendee->statuscode = (string) MDL_F2F_STATUS_NOT_SET;
                }

                if (!$download) {
                    $status = $attendee->statuscode;
                    $checkbox = html_writer::checkbox($checkoptionid, $status, false, '', array(
                        'class' => 'selectedcheckboxes',
                        'data-selectid' => 'menusubmissionid_' . $attendee->submissionid
                    ));
                    array_unshift($data, $checkbox);
                    $select = html_writer::select($statusoptions, $optionid, $status, false);
                    $data[] = $select;
                } else {
                    if (!$hidecost) {
                        $data[] = facetoface_cost($attendee->id, $session->id, $session);
                        if (!$hidediscount) {
                            $data[] = $attendee->discountcode;
                        }
                    }

                    $data[] = get_string('status_' . facetoface_get_status($attendee->statuscode), 'facetoface');
                }
            } else if ($action == 'cancellations') {
                $timecancelled = isset($attendee->timecancelled) ? $attendee->timecancelled : $attendee->timecreated;
                $data[] = userdate($timecancelled, get_string('strftimedatetime'));
                if ($attendee->statuscode == MDL_F2F_STATUS_USER_CANCELLED) {
                    $data[] = get_string('usercancelled', 'facetoface');
                } else if ($attendee->statuscode == MDL_F2F_STATUS_SESSION_CANCELLED) {
                    $data[] = get_string('sessioncancelled', 'facetoface');
                } else {
                    // Who knows!
                    debugging('Unexpected cancellation type encountered.', DEBUG_DEVELOPER);
                    $data[] = get_string('usercancelled', 'facetoface');
                }

                $icon = '';
                if (has_capability('mod/facetoface:manageattendeesnote', $context)) {
                    $url = new moodle_url('/mod/facetoface/cancellation_note.php',
                        array('s' => $session->id, 'userid' => $attendee->id, 'sesskey' => sesskey()));
                    $showpix = new pix_icon('/t/preview', get_string('showcancelreason', 'facetoface'));
                    $icon = $OUTPUT->action_icon($url, $showpix, null, array('class' => 'action-icon attendee-cancellation-note pull-right'));
                }

                $cancelstatus = new stdClass();
                $cancelstatus->id = $attendee->submissionid;
                $cancellationnote = customfield_get_data($cancelstatus, 'facetoface_cancellation', 'facetofacecancellation', false);
                // Verify 'cancellation note' custom filed is not deleted.
                $cancellationnotetext = isset($cancellationnote['cancellationnote']) ? $cancellationnote['cancellationnote'] : '';
                if (!$download) {
                    $data[] = $icon . html_writer::span($cancellationnotetext, 'cancellationnote' . $attendee->id, array('id' => 'cancellationnote' . $attendee->id));
                } else {
                    $data[] = $cancellationnotetext;
                }
            } else {
                // To get the right approver & approval time we will need to get the approved status record.
                $sql = 'SELECT fss.id, fss.signupid, fs.userid, fss.createdby, fss.timecreated
                          FROM {facetoface_signups} fs
                          JOIN {facetoface_signups_status} fss
                            ON fss.signupid = fs.id
                         WHERE fs.id = :sid
                           AND fs.userid = :uid
                           AND fss.statuscode = :status
                      ORDER BY fss.timecreated DESC';
                $params = array('sid' => $attendee->submissionid, 'uid' => $attendee->id, 'status' => MDL_F2F_STATUS_APPROVED); // TODO - in 2 step should this be manager or admin?

                $apprecords = $DB->get_records_sql($sql, $params);
                $apprecord = array_shift($apprecords);

                // Additional approval columns for the attendees tab.
                if ($facetoface->approvaltype == APPROVAL_ROLE) {
                    $data[] = $rolenames[$facetoface->approvalrole]->localname;
                }

                if ($facetoface->approvaltype > APPROVAL_SELF) {
                    // It is possible for a seminar to start from a "no approval
                    // needed" type to become a "manager approved" seminar even
                    // after people have signed up. When this occurs, learners
                    // will not be picked up by the SQL statement above - simply
                    // because no approval record need to be created when they
                    // were waitlisted or booked. Hence the check here.
                    $approver = isset($apprecord->createdby) ? fullname($DB->get_record('user', array('id'=>$apprecord->createdby))) : '';
                    $approval_time = isset($apprecord->timecreated) ? userdate($apprecord->timecreated) : '';

                    $data[] = $approver;
                    $data[] = $approval_time;
                }

                if (!$hidecost) {
                    $data[] = facetoface_cost($attendee->id, $session->id, $session);
                    if (!$hidediscount) {
                        $data[] = $attendee->discountcode;
                    }
                }

                $data[] = str_replace(' ', '&nbsp;', get_string('status_'.facetoface_get_status($attendee->statuscode), 'facetoface'));
                $icon = '';
                if (has_capability('mod/facetoface:manageattendeesnote', $context)) {
                    $url = new moodle_url('/mod/facetoface/attendee_note.php', array('s' => $session->id, 'userid' => $attendee->id, 'sesskey' => sesskey()));
                    $showpix = new pix_icon('/t/preview', get_string('showattendeesnote', 'facetoface'));
                    $icon = $OUTPUT->action_icon($url, $showpix, null, array('class' => 'action-icon attendee-add-note pull-right'));
                }
                if ($includeattendeesnote) {
                    // Get signup note.
                    $signupstatus = new stdClass();
                    $signupstatus->id = $attendee->submissionid;
                    $signupnote = customfield_get_data($signupstatus, 'facetoface_signup', 'facetofacesignup', false);
                    // Currently it is possible to delete signupnote custom field easly so we must check if cf is exists.
                    $signupnotetext = isset($signupnote['signupnote']) ? $signupnote['signupnote'] : '';
                    if (!$download) {
                        $data[] = $icon . html_writer::span($signupnotetext, 'note' . $attendee->id, array('id' => 'usernote' . $attendee->id));
                    } else {
                        $data[] = $signupnotetext;
                    }
                }
            }

            if ($action == 'waitlist' && !$download) {
                $d = html_writer::empty_tag('input', array('type' => 'checkbox', 'value' => $attendee->id, 'name' => 'userid'));
                $data[] = $d;
            }

            if (!$download) {
                $table->add_data($data);
            } else {
                $export_rows[] = $data;
            }
        }
        if (!$download) {
            $table->finish_html();
        } else {
            switch ($download) {
                case 'ods':
                    facetoface_download_ods($headers, $export_rows, $exportfilename);
                    break;
                case 'xls':
                    facetoface_download_xls($headers, $export_rows, $exportfilename);
                    break;
                case 'csv':
                    facetoface_download_csv($headers, $export_rows, $exportfilename);
                    break;
            }
        }
    }

    // Session downloadable sign in sheet.
    if (($action === 'attendees') && $session->cntdates && has_capability('mod/facetoface:exportsessionsigninsheet', $context)) {
        $downloadsheetattendees = facetoface_get_attendees($session->id, array(MDL_F2F_STATUS_BOOKED, MDL_F2F_STATUS_FULLY_ATTENDED, MDL_F2F_STATUS_NOT_SET,
            MDL_F2F_STATUS_NO_SHOW, MDL_F2F_STATUS_PARTIALLY_ATTENDED));
        if (!empty($downloadsheetattendees)) {
            // We need the dates, and we only want to show this option if there are one or more dates.
            $action = new moodle_url('/mod/facetoface/signinsheet.php');
            $signinform = new \mod_facetoface\form\signin($action, $session);
            echo html_writer::start_div('f2fdownloadsigninsheet');
            $signinform->display();
            echo html_writer::end_div();
        }
    }

    if (has_any_capability(array('mod/facetoface:addattendees', 'mod/facetoface:removeattendees', 'mod/facetoface:takeattendance'), $context)) {
        if ($action == 'takeattendance' && $actionallowed) {
            // Changes checker
            $PAGE->requires->yui_module('moodle-core-formchangechecker',
                'M.core_formchangechecker.init',
                array(array(
                    'formid' => 'attendanceform'
                ))
            );
            $PAGE->requires->string_for_js('changesmadereallygoaway', 'moodle');

            // Save and cancel buttons.
            echo html_writer::start_tag('p');
            echo html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('saveattendance', 'facetoface')));
            echo '&nbsp;' . html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'cancelform', 'value' => get_string('cancelattendance', 'facetoface')));
            echo html_writer::end_tag('p') . html_writer::end_tag('form');
        }

        if ($exports) {
            echo $OUTPUT->container_start('actions last');
            // Action selector.
            echo html_writer::label(get_string('attendeeactions', 'mod_facetoface'), 'menuf2f-actions', true, array('class' => 'sr-only'));
            echo html_writer::select($exports, 'f2f-actions', '', array('' => get_string('export', 'totara_reportbuilder')));
            echo $OUTPUT->container_end();
        }
    }
}

if ($action == 'messageusers' && $actionallowed) {
    $OUTPUT->heading(get_string('messageusers', 'facetoface'));

    $formurl = clone($baseurl);
    $formurl->param('action', 'messageusers');

    $mform = new \mod_facetoface\form\attendees_message($formurl, array('s' => $s));
    $mform->display();
}

/**
 * Print unapproved requests (if user able to view)
 */
if ($action == 'approvalrequired') {

    if ($approved == 1) {
        echo $OUTPUT->notification(get_string('attendancerequestsupdated', 'facetoface'), 'notifysuccess');
    }

    echo html_writer::empty_tag('br', array('id' => 'unapproved'));
    $numattendees = facetoface_get_num_attendees($session->id);
    $numwaiting = count($requests);
    $availablespaces = $session->capacity - $numattendees;
    $allowoverbook = $session->allowoverbook;
    $canoverbook = has_capability('mod/facetoface:signupwaitlist', $context);

    // Are there more users waiting than spaces available?
    // Note this does not apply to people with overbook capability (see facetoface_session_has_capacity).
    if (!$canoverbook && ($numwaiting > $availablespaces)) {
        $stringmodifier = ($availablespaces > 0) ? 'over' : 'no';
        $stringidentifier = ($allowoverbook) ? "approval{$stringmodifier}capacitywaitlist" : "approval{$stringmodifier}capacity";
        $overcapacitymessage = get_string($stringidentifier, 'facetoface', array('waiting' => $numwaiting, 'available' => $availablespaces));
        echo $OUTPUT->notification($overcapacitymessage, 'notifynotice');
    }
    // If they cannot overbook and no spaces are available, disable the ability to approve more requests.
    $approvaldisabled = array();
    if (!$canoverbook && ($availablespaces <= 0 && !$allowoverbook)) {
         $approvaldisabled['disabled'] = 'disabled';
    }
    $actionurl = clone($baseurl);

    echo html_writer::start_tag('form', array('action' => $actionurl, 'method' => 'post'));
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => $USER->sesskey));
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 's', 'value' => $s));
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'action', 'value' => 'approvalrequired'));

    unset($actionurl);

    $headings = array();
    $headings[] = get_string('name');
    $headings[] = get_string('timerequested', 'facetoface');
    if ($includeattendeesnote) {
        // The user has to hold specific permissions to view this.
        $headings[] = get_string('attendeenote', 'facetoface');
    }

    // Additional approval columns for the approval tab.
    if ($facetoface->approvaltype == APPROVAL_MANAGER || $facetoface->approvaltype == APPROVAL_ADMIN) {
        $headings[] = get_string('header:managername', 'facetoface');
        if ($facetoface->approvaltype == APPROVAL_ADMIN) {
            $headings[] = get_string('header:approvalstate', 'facetoface');
            $headings[] = get_string('header:approvaltime', 'facetoface');
        }
    }

    $headings[] = get_string('decidelater', 'facetoface');
    $headings[] = get_string('decline', 'facetoface');
    $headings[] = get_string('approve', 'facetoface');

    $table = new html_table();
    $table->summary = get_string('requeststablesummary', 'facetoface');
    $table->head = $headings;
    $table->align = array('left', 'center', 'center', 'center', 'center', 'center');

    foreach ($requests as $attendee) {
        $data = array();
        $attendee_link = new moodle_url('/user/view.php', array('id' => $attendee->id, 'course' => $course->id));
        $data[] = html_writer::link($attendee_link, format_string(fullname($attendee)));
        $data[] = userdate($attendee->timerequested, get_string('strftimedatetime'));

        $icon = '';
        if (has_capability('mod/facetoface:manageattendeesnote', $context)) {
            $url = new moodle_url('/mod/facetoface/attendee_note.php', array('s' => $session->id, 'userid' => $attendee->id, 'sesskey' => sesskey()));
            $icon = $OUTPUT->action_icon($url, $pix, null, array('class' => 'action-icon attendee-add-note pull-right'));
        }
        if ($includeattendeesnote) {
            // Get signup note.
            $signupstatus = new stdClass();
            $signupstatus->id = $attendee->signupid;
            $signupnote = customfield_get_data($signupstatus, 'facetoface_signup', 'facetofacesignup', false);
            // Currently it is possible to delete signupnote custom field easly so we must check if cf is exists.
            $signupnotetext = isset($signupnote['signupnote']) ? $signupnote['signupnote'] : '';

            $note = html_writer::span($signupnotetext, 'note' . $attendee->id, array('id' => 'usernote' . $attendee->id));
            $data[] = $icon . $note;
        }

        // Additional approval columns for the approval tab.
        if ($facetoface->approvaltype == APPROVAL_MANAGER || $facetoface->approvaltype == APPROVAL_ADMIN) {
            $managers = facetoface_get_session_managers($attendee->id, $session->id);
            $managernames = array();
            $state = '';
            $time = '';
            foreach ($managers as $manager) {
                $managernames[] =  $manager->fullname;
            }
            if ($facetoface->approvaltype == APPROVAL_ADMIN) {
                switch ($attendee->statuscode) {
                    case MDL_F2F_STATUS_REQUESTED:
                        $state = get_string('none', 'mod_facetoface');
                        $time = '';
                        break;
                    case MDL_F2F_STATUS_REQUESTEDADMIN:
                        $state = get_string('approved', 'mod_facetoface');
                        $time = userdate($attendee->timerequested);
                        break;
                    default:
                        print_error('error:invalidstatus', 'mod_facetoface');
                        break;
                }
            }
            $managernamestr = implode(', ', $managernames);
            $data[] = html_writer::span($managernamestr, 'managername' . $attendee->id, array('id' => 'managername' . $attendee->id));
            if ($facetoface->approvaltype == APPROVAL_ADMIN) {
                $data[] = html_writer::span($state, 'approvalstate' . $attendee->id, array('id' => 'approvalstate' . $attendee->id));
                $data[] = html_writer::span($time, 'approvaltime' . $attendee->id, array('id' => 'approvaltime' . $attendee->id));
            }
        }

        $id = 'requests_' . $attendee->id . '_noaction';
        $label = html_writer::label(get_string('decideuserlater', 'mod_facetoface', format_string(fullname($attendee))), $id, '', array('class' => 'sr-only'));
        $radio = html_writer::empty_tag('input', array_merge($approvaldisabled, array('type' => 'radio', 'name' => 'requests['.$attendee->id.']', 'value' => '0', 'checked' => 'checked', 'id' => $id)));
        $data[] = $label . $radio;

        $id = 'requests_' . $attendee->id . '_decline';
        $label = html_writer::label(get_string('declineuserevent', 'mod_facetoface', format_string(fullname($attendee))), $id, '', array('class' => 'sr-only'));
        $radio = html_writer::empty_tag('input',array_merge($approvaldisabled, array('type' => 'radio', 'name' => 'requests['.$attendee->id.']', 'value' => '1', 'id' => $id)));
        $data[] = $label . $radio;

        $id = 'requests_' . $attendee->id . '_approve';
        $label = html_writer::label(get_string('approveuserevent', 'mod_facetoface', format_string(fullname($attendee))), $id, '', array('class' => 'sr-only'));
        $radio = html_writer::empty_tag('input', array_merge($approvaldisabled, array('type' => 'radio', 'name' => 'requests['.$attendee->id.']', 'value' => '2', 'id' => $id)));
        $data[] = $label . $radio;
        $table->data[] = $data;
    }

    if (!empty($table->data)) {
        echo html_writer::table($table);
        echo html_writer::tag('p', html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('updaterequests', 'facetoface'))));
    } else {
        echo html_writer::start_span();
        echo html_writer::tag('p', get_string('nopendingapprovals', 'facetoface'));
        echo html_writer::end_span();
    }

    echo html_writer::end_tag('form');
}

// Hide "Go back" link for case user does not have any capabilities to see facetoface/course.
if ($goback) {
    // Go back.
    if ($backtoallsessions) {
        $url = new moodle_url('/mod/facetoface/view.php', array('f' => $facetoface->id));
    } else {
        $url = new moodle_url('/course/view.php', array('id' => $course->id));
    }
    echo html_writer::link($url, get_string('goback', 'facetoface')) . html_writer::end_tag('p');
}

/**
 * Print page footer
 */
if (!$onlycontent) {
    echo $OUTPUT->container_end();
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer($course);
}
