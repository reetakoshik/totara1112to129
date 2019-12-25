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
 * @author Francois Marier <francois@catalyst.net.nz>
 * @author Aaron Barnes <aaronb@catalyst.net.nz>
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Prepare exit as session id is missed.
 */
function process_no_sessionid(string $page = 'view') {
    global $PAGE, $OUTPUT;

    require_login();

    $syscontext = context_system::instance();
    if (!has_capability('mod/facetoface:viewallsessions', $syscontext)) {
        // They can't view the sessionreport, essentially this makes s a required param.
        // As its not been set, throw the same error required_param would.
        print_error('missingparam', '', '', 's');
    }

    $PAGE->set_context($syscontext);
    $PAGE->set_url("/mod/facetoface/attendees/{$page}.php");

    echo $OUTPUT->header();
    $url = new moodle_url('/mod/facetoface/reports/events.php');
    echo $OUTPUT->heading(get_string('selectaneventheading', 'rb_source_facetoface_sessions'));
    echo html_writer::tag('p', html_writer::link($url, get_string('selectanevent', 'rb_source_facetoface_sessions')));
    echo $OUTPUT->footer();
}

/**
 * Get allowed actions are actions the user has permissions to do
 * Get available actions are actions that have a point.
 * e.g. view the cancellations page when there are no cancellations is not an "available" action,
 * but it maybe be an "allowed" action
 */
function get_allowed_available_actions(\mod_facetoface\seminar $seminar, \mod_facetoface\seminar_event $seminarevent, $context, $session) {
    global $USER, $DB, $CFG;
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

    $allowed_actions = [];
    $available_actions = [];

    // Actions the user can perform
    $has_attendees = facetoface_get_num_attendees($seminarevent->get_id());
    $sendmessagecapability = has_all_capabilities(
        [
            'moodle/site:sendmessage',
            'moodle/course:bulkmessaging',
            'mod/facetoface:viewattendees'
        ],
        $context
    );

    if (has_capability('mod/facetoface:viewattendees', $context)) {
        $allowed_actions[] = 'attendees';
        $allowed_actions[] = 'waitlist';
        $allowed_actions[] = 'addattendees';

        if (empty($seminarevent->get_cancelledstatus())) {
            $available_actions[] = 'attendees';
        }

        if (facetoface_get_users_by_status($seminarevent->get_id(), \mod_facetoface\signup\state\waitlisted::get_code())) {
            $available_actions[] = 'waitlist';
        }
    }

    if (has_capability('mod/facetoface:viewcancellations', $context)) {
        $allowed_actions[] = 'cancellations';

        if (!empty($seminarevent->get_cancelledstatus()) || facetoface_get_users_by_status($seminarevent->get_id(), \mod_facetoface\signup\state\user_cancelled::get_code())) {
            $available_actions[] = 'cancellations';
        }
    }

    if (has_capability('mod/facetoface:takeattendance', $context)) {
        $allowed_actions[] = 'takeattendance';

        if ($has_attendees && $session->mintimestart && facetoface_has_session_started($session, time())) {
            $available_actions[] = 'takeattendance';
        }
    }

    $attendees = array();
    $cancellations = array();
    $requests = array();

    $staff = null;
    if ($seminar->get_approvaltype() == \mod_facetoface\seminar::APPROVAL_MANAGER || $seminar->get_approvaltype() == \mod_facetoface\seminar::APPROVAL_ADMIN) {
        $managersql = "1=0";
        $sqlparams = array();

        // Use job assignment API: This can fail with large amount of users managed by current user.
        $staffids = \totara_job\job_assignment::get_staff_userids($USER->id);
        if (!empty($staffids)) {
            list($staffsql, $sqlparams) = $DB->get_in_or_equal($staffids, SQL_PARAMS_NAMED);
            $managersql = "fs.userid $staffsql";
        }

        $selectjobassignmentsignupglobal = get_config(null, 'facetoface_selectjobassignmentonsignupglobal');
        if (!empty($selectjobassignmentsignupglobal) && !empty($seminar->get_selectjobassignmentonsignup())) {
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
        $sqlparams = array_merge($sqlparams, array('sessionid' => $seminarevent->get_id(), 'status' => \mod_facetoface\signup\state\requested::get_code() ,'now' => time()));
        $staff = $DB->get_fieldset_sql($requestssql, $sqlparams);
    }

    if ($seminar->get_approvaltype() == \mod_facetoface\seminar::APPROVAL_ROLE) {
        $sessionroles = facetoface_get_trainers($seminarevent->get_id(), $seminar->get_approvalrole());
        if (!empty($sessionroles)) {
            foreach ($sessionroles as $user) {
                if ($user->id == $USER->id) {
                    // The current user is one of the role approvers.
                    $allowed_actions[] = 'approvalrequired';
                    $available_actions[] = 'approvalrequired';
                    // Set everyone as their staff.
                    $staff = array_keys(facetoface_get_requests($seminarevent->get_id()));
                    break;
                }
            }
        }
    }

    $admin_requests = array();
    if ($seminar->get_approvaltype() == \mod_facetoface\seminar::APPROVAL_ADMIN) {
        if (facetoface_is_adminapprover($USER->id, $seminar->get_properties())) {
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
            $params = array('sessionid' => $seminarevent->get_id(), 'statusadm' => \mod_facetoface\signup\state\requestedadmin::get_code(), 'statusman' => \mod_facetoface\signup\state\requested::get_code());
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
        $get_requests = $seminar->get_approvaltype() == \mod_facetoface\seminar::APPROVAL_ADMIN ? facetoface_get_adminrequests($seminarevent->get_id()) : facetoface_get_requests($seminarevent->get_id());

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
            $get_attendees = facetoface_get_attendees($session->id, array(\mod_facetoface\signup\state\booked::get_code(), \mod_facetoface\signup\state\no_show::get_code(),
                \mod_facetoface\signup\state\partially_attended::get_code(), \mod_facetoface\signup\state\fully_attended::get_code()));
        } else {
            $get_attendees = facetoface_get_attendees($session->id, array(\mod_facetoface\signup\state\waitlisted::get_code(), \mod_facetoface\signup\state\booked::get_code(), \mod_facetoface\signup\state\no_show::get_code(),
                \mod_facetoface\signup\state\partially_attended::get_code(), \mod_facetoface\signup\state\fully_attended::get_code()));
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
        $get_cancellations = facetoface_get_cancellations($seminarevent->get_id());
        if ($get_cancellations) {
            // Calculate which cancelled users are relevant to the viewer.
            $cancellations = array_intersect_key($get_cancellations, array_flip($staff));

            if ($cancellations) {
                $allowed_actions[] = 'cancellations';
                $available_actions[] = 'cancellations';
            }
        }
    }

    if ((in_array('attendees', $available_actions) ||
            in_array('cancellations', $available_actions) ||
            in_array('waitlist', $available_actions) ||
            in_array('takeattendance', $available_actions)) &&
        $sendmessagecapability) {
        $allowed_actions[] = 'messageusers';
        $available_actions[] = 'messageusers';
    }

    return [$allowed_actions, $available_actions, $staff, $admin_requests, $canapproveanyrequest, $cancellations, $requests, $attendees];
}

function process_attendees_js($action, \mod_facetoface\seminar $seminar, \mod_facetoface\seminar_event $seminar_event) {
    global $PAGE;

    $pagetitle = format_string($seminar->get_name());

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

    $jsmodule = array(
        'name' => 'totara_f2f_attendees',
        'fullpath' => '/mod/facetoface/js/attendees.js',
        'requires' => array('json', 'totara_core'));

    $args = array('args' => '{"sessionid":'.$seminar_event->get_id().','.
        '"action":'.$json_action.','.
        '"sesskey":"'.sesskey().'",'.
        '"selectall":'.MDL_F2F_SELECT_ALL.','.
        '"selectnone":'.MDL_F2F_SELECT_NONE.','.
        '"selectset":"'.MDL_F2F_SELECT_SET.'",'.
        '"selectnotset":"'.MDL_F2F_SELECT_NOT_SET.'",'.
        '"courseid":"'.$seminar->get_course().'",'.
        '"facetofaceid":"'.$seminar->get_id().'",'.
        '"notsetop":"'.\mod_facetoface\signup\state\not_set::get_code().'",'.
        '"approvalreqd":"'.$seminar->is_approval_required().'"}');

    $PAGE->requires->js_init_call('M.totara_f2f_attendees.init', $args, false, $jsmodule);
    $PAGE->set_url("/mod/facetoface/attendees/{$action}.php", array('s' => $seminar_event->get_id()));
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title($pagetitle);
}

function process_messaging_js($action, \mod_facetoface\seminar $seminar, \mod_facetoface\seminar_event $seminar_event) {
    global $PAGE;

    $pagetitle = format_string($seminar->get_name());

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
    $args = array('args' => '{"sessionid":'.$seminar_event->get_id().','.
        '"action":'.$json_action.','.
        '"sesskey":"'.sesskey().'",'.
        '"approvalreqd":"' . $seminar->is_approval_required() . '"}');

    $PAGE->requires->strings_for_js(array('editmessagerecipientsindividually', 'existingrecipients', 'potentialrecipients'), 'facetoface');
    $PAGE->requires->string_for_js('update', 'moodle');

    $jsmodule = array(
        'name' => 'totara_f2f_attendees_message',
        'fullpath' => '/mod/facetoface/js/attendees_messaging.js',
        'requires' => array('json', 'totara_core'));

    $PAGE->requires->js_init_call('M.totara_f2f_attendees_messaging.init', $args, false, $jsmodule);
    $PAGE->set_url("/mod/facetoface/attendees/{$action}.php", array('s' => $seminar_event->get_id()));
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title($pagetitle);
}