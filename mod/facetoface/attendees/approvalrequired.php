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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');
require_once($CFG->dirroot.'/mod/facetoface/attendees/lib.php');
require_once($CFG->libdir.'/totaratablelib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');

use \mod_facetoface\signup;
use \mod_facetoface\signup\state\{booked, waitlisted, requestedadmin, declined};

/**
 * Load and validate base data
 */
// Face-to-face session ID
$s = optional_param('s', 0, PARAM_INT);

// Cancel request
$cancelform = optional_param('cancelform', false, PARAM_BOOL);

// Action being performed, a proper default will be set shortly.
// Require for attendees.js
$action = optional_param('action', 'approvalrequired', PARAM_ALPHA);

// Only return content
$onlycontent = optional_param('onlycontent', false, PARAM_BOOL);

// Export download.
$download = optional_param('download', '', PARAM_ALPHA);

// If approval requests have been updated, show a success message.
$approved = optional_param('approved', 0, PARAM_INT);

// Back to all sessions.
$backtoallsessions = optional_param('backtoallsessions', 1, PARAM_BOOL);

// If there's no sessionid specified.
if (!$s) {
    process_no_sessionid('approvalrequired');
    exit;
}

list($session, $facetoface, $course, $cm, $context) = facetoface_get_env_session($s);
$seminarevent = new \mod_facetoface\seminar_event($s);
$seminar = new \mod_facetoface\seminar($seminarevent->get_facetoface());

// Allow managers to be able to approve staff without being enrolled in the course.
require_login();

// Setup urls
$baseurl = new moodle_url('/mod/facetoface/attendees/approvalrequired.php', array('s' => $seminarevent->get_id()));

$PAGE->set_context($context);
$PAGE->set_url($baseurl);
$PAGE->set_cm($cm);

list($allowed_actions, $available_actions, $staff, $admin_requests, $canapproveanyrequest, $cancellations, $requests, $attendees) = get_allowed_available_actions($seminar, $seminarevent, $context, $session);
$includeattendeesnote = (has_any_capability(array('mod/facetoface:viewattendeesnote', 'mod/facetoface:manageattendeesnote'), $context));

$goback = true;
$can_view_session = !empty($allowed_actions);
if (!$can_view_session) {
    // If no allowed actions so far, check if this was user/manager who has just approved staff requests (approved == 1).
    if ($approved == 1) {
        // If so, do not redirect, just display notify message.
        // Hide "Go back" link for case if a user does not have any capabilities to see facetoface/course.
        $goback = false;
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

/**
 * Handle submitted data
 */
if ($form = data_submitted()) {
    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad', 'error');
    }

    if ($cancelform) {
        redirect($baseurl);
    }

    // Approve requests
    if (!empty($form->requests) && $actionallowed) {
        // Site admin is allowing to approve user request.
        if (!$canapproveanyrequest) {
            // Leave the users which are required to approve and remove the rest.
            $form->requests = array_intersect_key($form->requests, array_flip($staff));
        }

        $errors = [];
        foreach ($form->requests as $uid => $value) {
            $signup = signup::create($uid, $seminarevent);

            switch ($value) {
                case 1: // Decline.
                    if ($signup->can_switch(declined::class)) {
                        $signup->switch_state(declined::class);
                        \mod_facetoface\notice_sender::decline($signup);
                    } else {
                        $failures = $signup->get_failures(declined::class);
                        $errors[$uid] = current($failures);
                    }
                    break;
                case 2: // Approve.
                    if ($signup->can_switch(booked::class, waitlisted::class, requestedadmin::class)) {
                        $signup->switch_state(booked::class, waitlisted::class, requestedadmin::class);
                    } else {
                        $failures = $signup->get_failures(booked::class, waitlisted::class, requestedadmin::class);
                        $errors[$uid] = current($failures);
                    }
                    break;
                case 0:
                default:
                    continue 2;
            }
        }

        if (empty($errors)) {
            $baseurl->params(array('approved' => '1'));
        } else {
            $output = html_writer::start_tag('ul');
            foreach ($errors as $attendeeid => $errmsg) {
                $output .= html_writer::tag('li', $errmsg);
            }
            $output .= html_writer::end_tag('ul');
            totara_set_notification($output);
        }
        redirect($baseurl);
    }
}

/**
 * Print page header
 */
if (!$onlycontent) {
    process_attendees_js($action, $seminar, $seminarevent);
    \mod_facetoface\event\attendees_viewed::create_from_session($seminarevent->to_record(), $context, $action)->trigger();
    $PAGE->set_heading($course->fullname);
    echo $OUTPUT->header();
}

/**
 * Print page content
 */
if (!$onlycontent && !$download) {
    echo $OUTPUT->box_start();

    echo $OUTPUT->heading(format_string($seminar->get_name()));
    if ($can_view_session) {
        /**
         * @var mod_facetoface_renderer $seminarrenderer
         */
        $seminarrenderer = $PAGE->get_renderer('mod_facetoface');
        echo $seminarrenderer->render_seminar_event($seminarevent, true, false, true);

        // Print customfields.
        $customfields = customfield_get_data($seminarevent->to_record(), 'facetoface_sessioncancel', 'facetofacesessioncancel');
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
    require_once($CFG->dirroot.'/mod/facetoface/attendees/tabs.php'); // If needed include tabs
    echo $OUTPUT->container_start('f2f-attendees-table');
}

/**
 * Print attendees (if user able to view)
 */
$pix = new pix_icon('t/edit', get_string('edit'));
if ($show_table) {
    if (!$download) {
        $numattendees = facetoface_get_num_attendees($seminarevent->get_id());
        $overbooked = ($numattendees > $seminarevent->get_capacity());
        //output the section heading
        echo $OUTPUT->heading($heading);
    }

    // Actions menu.
    if (has_any_capability(array('mod/facetoface:addattendees', 'mod/facetoface:removeattendees'), $context)) {
        if ($actions) {
            echo $OUTPUT->container_start('actions last');
            // Action selector
            echo html_writer::label(get_string('attendeeactions', 'mod_facetoface'), 'menuf2f-actions', true, array('class' => 'sr-only'));
            echo html_writer::select($actions, 'f2f-actions', '', array('' => get_string('actions')));
            echo $OUTPUT->container_end();
        }
    }

    if (empty($rows)) {
        if ($seminar->is_approval_required()) {
            if (count($requests) == 1) {
                echo $OUTPUT->notification(get_string('nosignedupusersonerequest', 'facetoface'));
            } else {
                echo $OUTPUT->notification(get_string('nosignedupusersnumrequests', 'facetoface', count($requests)));
            }
        } else {
            echo $OUTPUT->notification(get_string('nosignedupusers', 'facetoface'));
        }
    } else {
        if (!$download) {
            echo html_writer::tag('div', '', array('class' => 'hide', 'id' => 'noticeupdate'));
        }

        $table = new totara_table('facetoface-attendees');
        $actionurl = clone($baseurl);
        $actionurl->params(['sesskey' => sesskey(), 'onlycontent' => true, 'action' => $action]);
        $table->define_baseurl($actionurl);
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

        $showjobassignments = (!empty($selectjobassignmentonsignupglobal) && $seminar->get_selectjobassignmentonsignup() != 0);

        if ($showjobassignments) {
            $headers[] = get_string('selectedjobassignment', 'mod_facetoface');
            $columns[] = 'jobassignment';
        }

        // Additional approval columns for the attendees tab.
        if ($seminar->get_approvaltype() == \mod_facetoface\seminar::APPROVAL_ROLE) {
            $rolenames = role_fix_names(get_all_roles());
            $headers[] = get_string('approverrolename', 'mod_facetoface');
            $columns[] = 'approverrolename';
        }

        if ($seminar->get_approvaltype() > \mod_facetoface\seminar::APPROVAL_SELF) {
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

        if ($includeattendeesnote) {
            $headers[] = get_string('attendeenote', 'facetoface');
            $columns[] = 'usernote';
        }

        if (!$download) {
            $table->define_columns($columns);
            $table->define_headers($headers);
            $table->setup();
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
                            's' => $seminarevent->get_id(),
                            'managerid' => $attendee->bookedby,
                            'backtosession' => $action,
                            'cancelreservation' => 1,
                            'sesskey' => sesskey(),
                        );
                        $cancelurl = new moodle_url('/mod/facetoface/reservations/reserve.php', $params);
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

            $data[] = userdate($attendee->timesignedup, get_string('strftimedatetime'));

            if ($showjobassignments) {
                if (!empty($attendee->jobassignmentid)) {
                    $jobassignment = \totara_job\job_assignment::get_with_id($attendee->jobassignmentid);
                    $label = position::job_position_label($jobassignment);
                } else {
                    $label = '';
                }

                $url = new moodle_url('/mod/facetoface/attendees/ajax/job_assignment.php', array('s' => $seminarevent->get_id(), 'id' => $attendee->id));
                $icon = $OUTPUT->action_icon($url, $pix, null, array('class' => 'action-icon attendee-edit-job-assignment pull-right'));
                $jobassign = html_writer::span($label, 'jobassign'.$attendee->id, array('id' => 'jobassign'.$attendee->id));

                if ($canchangesignedupjobassignment) {
                    $data[] = $icon . $jobassign;
                } else {
                    $data[] = $jobassign;
                }
            }

            // Additional approval columns for the attendees tab.
            if ($seminar->get_approvaltype() == \mod_facetoface\seminar::APPROVAL_ROLE) {
                $data[] = $rolenames[$seminar->get_approvalrole()]->localname;
            }

            if ($seminar->get_approvaltype() > \mod_facetoface\seminar::APPROVAL_SELF) {
                list($approver, $approval_time) = \mod_facetoface\approver::get_required($seminar, $attendee);
                $data[] = $approver;
                $data[] = $approval_time;
            }

            if (!$hidecost) {
                $data[] = facetoface_cost($attendee->id, $seminarevent->get_id(), $session);
                if (!$hidediscount) {
                    $data[] = $attendee->discountcode;
                }
            }

            $state = \mod_facetoface\signup\state\state::from_code($attendee->statuscode);
            $data[] = str_replace(' ', '&nbsp;', $state::get_string());
            $icon = '';
            if (has_capability('mod/facetoface:manageattendeesnote', $context)) {
                $url = new moodle_url('/mod/facetoface/attendees/ajax/signup_notes.php', array('s' => $seminarevent->get_id(), 'userid' => $attendee->id, 'sesskey' => sesskey()));
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

    if (has_any_capability(array('mod/facetoface:addattendees', 'mod/facetoface:removeattendees', 'mod/facetoface:takeattendance'), $context)) {
        if ($exports) {
            echo $OUTPUT->container_start('actions last');
            // Action selector.
            echo html_writer::label(get_string('attendeeactions', 'mod_facetoface'), 'menuf2f-actions', true, array('class' => 'sr-only'));
            echo html_writer::select($exports, 'f2f-actions', '', array('' => get_string('export', 'totara_reportbuilder')));
            echo $OUTPUT->container_end();
        }
    }
}

/**
 * Print unapproved requests (if user able to view)
 */
if ($approved == 1) {
    echo $OUTPUT->notification(get_string('attendancerequestsupdated', 'facetoface'), 'notifysuccess');
}

echo html_writer::empty_tag('br', array('id' => 'unapproved'));
$numattendees = facetoface_get_num_attendees($seminarevent->get_id());
$numwaiting = count($requests);
$availablespaces = $seminarevent->get_capacity() - $numattendees;
$allowoverbook = $seminarevent->get_allowoverbook();
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

echo html_writer::start_tag('form', array('action' => $baseurl, 'method' => 'post'));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => $USER->sesskey));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 's', 'value' => $s));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'action', 'value' => 'approvalrequired'));

$headings = array();
$headings[] = get_string('name');
$headings[] = get_string('timerequested', 'facetoface');
if ($includeattendeesnote) {
    // The user has to hold specific permissions to view this.
    $headings[] = get_string('attendeenote', 'facetoface');
}

// Additional approval columns for the approval tab.
if ($seminar->get_approvaltype() == \mod_facetoface\seminar::APPROVAL_MANAGER || $seminar->get_approvaltype() == \mod_facetoface\seminar::APPROVAL_ADMIN) {
    $headings[] = get_string('header:managername', 'facetoface');
    if ($seminar->get_approvaltype() == \mod_facetoface\seminar::APPROVAL_ADMIN) {
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
        $url = new moodle_url('/mod/facetoface/attendees/ajax/signup_notes.php', array('s' => $seminarevent->get_id(), 'userid' => $attendee->id));
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
    if ($seminar->get_approvaltype() == \mod_facetoface\seminar::APPROVAL_MANAGER || $seminar->get_approvaltype() == \mod_facetoface\seminar::APPROVAL_ADMIN) {
        $managers = facetoface_get_session_managers($attendee->id, $seminarevent->get_id());
        $managernames = array();
        $state = '';
        $time = '';
        foreach ($managers as $manager) {
            $managernames[] =  $manager->fullname;
        }
        if ($seminar->get_approvaltype() == \mod_facetoface\seminar::APPROVAL_ADMIN) {
            switch ($attendee->statuscode) {
                case \mod_facetoface\signup\state\requested::get_code():
                    $state = get_string('none', 'mod_facetoface');
                    $time = '';
                    break;
                case \mod_facetoface\signup\state\requestedadmin::get_code():
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
        if ($seminar->get_approvaltype() == \mod_facetoface\seminar::APPROVAL_ADMIN) {
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

// Hide "Go back" link for case if a user does not have any capabilities to see facetoface/course.
if ($goback) {
    // Go back.
    if ($backtoallsessions) {
        $url = new moodle_url('/mod/facetoface/view.php', array('f' => $seminar->get_id()));
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
