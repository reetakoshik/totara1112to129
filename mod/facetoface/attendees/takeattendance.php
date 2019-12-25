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

use mod_facetoface\signup_helper;

/**
 * Load and validate base data
 */
// Face-to-face session ID
$s = optional_param('s', 0, PARAM_INT);

// Take attendance
$takeattendance = optional_param('takeattendance', false, PARAM_BOOL);

// Cancel request
$cancelform = optional_param('cancelform', false, PARAM_BOOL);

// Action being performed, a proper default will be set shortly.
// Require for attendees.js
$action = optional_param('action', 'takeattendance', PARAM_ALPHA);

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
    process_no_sessionid('takeattendance');
    exit;
}

list($session, $facetoface, $course, $cm, $context) = facetoface_get_env_session($s);
$seminarevent = new \mod_facetoface\seminar_event($s);
$seminar = new \mod_facetoface\seminar($seminarevent->get_facetoface());

require_login($course, false, $cm);

// Setup urls
$baseurl = new moodle_url('/mod/facetoface/attendees/takeattendance.php', array('s' => $seminarevent->get_id()));

$PAGE->set_context($context);
$PAGE->set_url($baseurl);

list($allowed_actions, $available_actions, $staff, $admin_requests, $canapproveanyrequest, $cancellations, $requests, $attendees) = get_allowed_available_actions($seminar, $seminarevent, $context, $session);
$includeattendeesnote = (has_any_capability(array('mod/facetoface:viewattendeesnote', 'mod/facetoface:manageattendeesnote'), $context));

$can_view_session = !empty($allowed_actions);
if (!$can_view_session) {
    // If no allowed actions so far.
    $return = new moodle_url('/mod/facetoface/view.php', array('f' => $seminar->get_id()));
    redirect($return);
    die();
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

if ($actionallowed) {
    $heading = get_string('takeattendance', 'facetoface');

    // Get list of actions
    $exports = array(
        'exportxls' => get_string('exportxls', 'totara_reportbuilder'),
        'exportods' => get_string('exportods', 'totara_reportbuilder'),
        'exportcsv' => get_string('exportcsv', 'totara_reportbuilder')
    );

    $params['statusgte'] = \mod_facetoface\signup\state\booked::get_code();
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
if ($formdata = data_submitted()) {
    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad', 'error');
    }

    if ($cancelform) {
        redirect($baseurl);
    }

    // Take attendance.
    if ($actionallowed && $takeattendance) {
        // Check the attendance data matches the expected seminar event.
        if ($formdata->s != $seminarevent->get_id()) {
            print_error('Mismatched attendance data handed through form submission.');
        }

        // Pre-process form data.
        $check = [];
        $items = [];
        foreach ($formdata as $key => $item) {
            $keyparts = explode('_', $key);

            /**
             *
             * If the user has been selected on the attendance form then there will be
             * an item in formdata called "check_submissionid_X" => 110.
             *
             * if ($keyparts[0] == 'check' && $keyparts[1] == 'submissionid' && $item == 110) {
             *     $check[$keyparts[2]] = $item;
             * }
             *
             * // Only process items that have been selected.
             * $attendance = array_intersect_key($items, $check);
             */

            /**
             * Every user on the form should have an entry in the formdata called
             * "submissionid_X" => $attendance value, but they should only be updated
             * if they also have a "Check_submissionid_X" record.
             */
            if ($keyparts[0] == 'submissionid') {
                if ($item == 110) {
                    continue; // Attendance value not set.
                }

                $items[$keyparts[1]] = (int)$item;
            }
        }

        if (signup_helper::process_attendance($seminarevent, $items)) {
            // Trigger take attendance update event.
            \mod_facetoface\event\attendance_updated::create_from_session($session, $context)->trigger();
            totara_set_notification(get_string('updateattendeessuccessful', 'facetoface'), $baseurl, array('class' => 'notifysuccess'));
        }

        totara_set_notification(get_string('error:takeattendance', 'facetoface'), $baseurl, array('class' => 'notifyproblem'));
    }
}

/**
 * Print page header
 */
if (!$onlycontent) {
    process_attendees_js($action, $seminar, $seminarevent);
    \mod_facetoface\event\attendees_viewed::create_from_session($session, $context, $action)->trigger();
    $PAGE->set_cm($cm);
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
    }
    require_once($CFG->dirroot.'/mod/facetoface/attendees/tabs.php'); // If needed include tabs
    echo $OUTPUT->container_start('f2f-attendees-table');
}

/**
 * Print attendees (if user able to view)
 */
$pix = new pix_icon('t/edit', get_string('edit'));
if ($show_table) {
    // Get list of attendees
    $rows = facetoface_get_attendees(
        $seminarevent->get_id(),
        [\mod_facetoface\signup\state\booked::get_code(), \mod_facetoface\signup\state\no_show::get_code(), \mod_facetoface\signup\state\partially_attended::get_code(), \mod_facetoface\signup\state\fully_attended::get_code()]
    );

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
        if ($actionallowed && !$download) {
            $formurl = clone($baseurl);
            $formurl->param('takeattendance', '1');
            echo html_writer::start_tag('form', array('action' => $formurl, 'method' => 'post', 'id' => 'attendanceform'));
            echo html_writer::tag('p', get_string('attendanceinstructions', 'facetoface'));
            echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => $USER->sesskey));
            echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 's', 'value' => $s));
            // Prepare status options array.
            $statusoptions = \mod_facetoface\attendees_list_helper::get_status();
        }

        if (!$download) {
            echo html_writer::tag('div', '', array('class' => 'hide', 'id' => 'noticeupdate'));
        }

        $table = new totara_table('facetoface-attendees');
        $actionurl = clone($baseurl);
        $actionurl->params(['sesskey' => sesskey(), 'onlycontent' => true, 'action' => $action, 'takeattendance' => '1']);
        $table->define_baseurl($actionurl);
        $table->set_attribute('class', 'generalbox mod-facetoface-attendees '.$action);

        $exportfilename = $action;

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

        if ($actionallowed && !$download) {
            $chooseoption = html_writer::label(get_string('select','mod_facetoface'), 'menubulk_select', false);
            $selectlist = html_writer::select($F2F_SELECT_OPTIONS, 'bulk_select', '', false, [ 'id' => 'menubulk_select' ]);
            array_unshift($headers, $chooseoption . $selectlist);
            array_unshift($columns, 'selectedusers');
            $headers[] = get_string('currentstatus', 'facetoface');
            $columns[] = 'currentstatus';
        } else {
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
        }

        if (!$download) {
            $table->define_columns($columns);
            $table->define_headers($headers);
            $table->setup();
            if ($actionallowed) {
                $renderer = $PAGE->get_renderer('mod_facetoface');
                $renderer->setcontext($context);
                $table->add_toolbar_content($renderer->display_bulk_actions_picker(), 'left' , 'top', 1);
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

                if (!$download) {
                    $url = new moodle_url('/mod/facetoface/attendees/ajax/job_assignment.php', array('s' => $session->id, 'id' => $attendee->id));
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

            if ($actionallowed) {
                $optionid = 'submissionid_' . $attendee->submissionid;
                $checkoptionid = 'check_submissionid_' . $attendee->submissionid;

                // Show current status.
                if ($attendee->statuscode == \mod_facetoface\signup\state\booked::get_code()) {
                    $attendee->statuscode = (string) \mod_facetoface\signup\state\not_set::get_code();
                }

                if (!$download) {
                    $status = $attendee->statuscode;
                    $checkbox_label = get_string('takeattendance_tick', 'mod_facetoface', htmlspecialchars(fullname($attendee)));
                    $checkbox = html_writer::checkbox($checkoptionid, $status, false, '', array(
                        'class' => 'selectedcheckboxes',
                        'aria-label' => $checkbox_label,
                        'data-selectid' => 'menusubmissionid_' . $attendee->submissionid
                    ));
                    array_unshift($data, $checkbox);
                    $select_label = get_string('takeattendance_label', 'mod_facetoface', htmlspecialchars(fullname($attendee)));
                    $select = html_writer::select($statusoptions, $optionid, $status, false, [ 'aria-label' => $select_label ]);
                    $data[] = $select;
                } else {
                    if (!$hidecost) {
                        $data[] = facetoface_cost($attendee->id, $seminarevent->get_id(), $session);
                        if (!$hidediscount) {
                            $data[] = $attendee->discountcode;
                        }
                    }
                    $state = \mod_facetoface\signup\state\state::from_code($attendee->statuscode);
                    $data[] = $state::get_string();
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
        if ($actionallowed) {
            // Changes checker
            $PAGE->requires->yui_module('moodle-core-formchangechecker', 'M.core_formchangechecker.init', array(
                    array(
                        'formid' => 'attendanceform'
                    )
                )
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

// Go back.
if ($backtoallsessions) {
    $url = new moodle_url('/mod/facetoface/view.php', array('f' => $seminar->get_id()));
} else {
    $url = new moodle_url('/course/view.php', array('id' => $course->id));
}
echo html_writer::link($url, get_string('goback', 'facetoface')) . html_writer::end_tag('p');

/**
 * Print page footer
 */
if (!$onlycontent) {
    echo $OUTPUT->container_end();
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer($course);
}
