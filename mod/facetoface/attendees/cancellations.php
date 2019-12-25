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

/**
 * Load and validate base data
 */
// Face-to-face session ID
$s = optional_param('s', 0, PARAM_INT);

// Action being performed, a proper default will be set shortly.
// Require for attendees.js
$action = optional_param('action', 'cancellations', PARAM_ALPHA);

// Only return content
$onlycontent = optional_param('onlycontent', false, PARAM_BOOL);

// Export download.
$download = optional_param('download', '', PARAM_ALPHA);

// Back to all sessions.
$backtoallsessions = optional_param('backtoallsessions', 1, PARAM_BOOL);

// If there's no sessionid specified.
if (!$s) {
    process_no_sessionid('cancellations');
    exit;
}

list($session, $facetoface, $course, $cm, $context) = facetoface_get_env_session($s);
$seminarevent = new \mod_facetoface\seminar_event($s);
$seminar = new \mod_facetoface\seminar($seminarevent->get_facetoface());

require_login($course, false, $cm);

// Setup urls
$baseurl = new moodle_url('/mod/facetoface/attendees/cancellations.php', array('s' => $seminarevent->get_id()));

$PAGE->set_context($context);
$PAGE->set_url($baseurl);

list($allowed_actions, $available_actions, $staff, $admin_requests, $canapproveanyrequest, $cancellations, $requests, $attendees) = get_allowed_available_actions($seminar, $seminarevent, $context, $session);

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
    $heading = get_string('cancellations', 'facetoface');

    // Get list of actions
    $exports = array(
        'exportxls' => get_string('exportxls', 'totara_reportbuilder'),
        'exportods' => get_string('exportods', 'totara_reportbuilder'),
        'exportcsv' => get_string('exportcsv', 'totara_reportbuilder')
    );

    $params['status'] = \mod_facetoface\signup\state\user_cancelled::get_code();
    $cols = array(
        array('user', 'idnumber'),
        array('user', 'namelink'),
        array('session', 'cancellationdate'),
        array('session', 'cancellationtype'),
        array('session', 'cancellationreason'),
    );

    $show_table = true;
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
    require_once($CFG->dirroot.'/mod/facetoface/attendees/tabs.php'); // If needed include tabs
    echo $OUTPUT->container_start('f2f-attendees-table');
}

/**
 * Print attendees (if user able to view)
 */
$pix = new pix_icon('t/edit', get_string('edit'));
if ($show_table) {
    // Get list of attendees
    if ($cancellations) {
        $rows = $cancellations;
    } else {
        if ($seminarevent->get_cancelledstatus() == 0) {
            $rows = facetoface_get_cancellations($seminarevent->get_id());
        } else {
            $rows = facetoface_get_attendees($seminarevent->get_id(), array(
                \mod_facetoface\signup\state\booked::get_code(),
                \mod_facetoface\signup\state\no_show::get_code(),
                \mod_facetoface\signup\state\partially_attended::get_code(),
                \mod_facetoface\signup\state\fully_attended::get_code(),
                \mod_facetoface\signup\state\user_cancelled::get_code(),
                \mod_facetoface\signup\state\event_cancelled::get_code()
            ));
        }
    }

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
            echo $OUTPUT->notification(get_string('nocancellations', 'facetoface'));
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

        $headers[] = get_string('timecancelled', 'facetoface');
        $columns[] = 'timecancelled';
        $headers[] = get_string('canceltype', 'facetoface');
        $columns[] = 'cancellationtype';
        $headers[] = get_string('cancelreason', 'facetoface');
        $columns[] = 'cancellationreason';

        if (!$download) {
            $table->define_columns($columns);
            $table->define_headers($headers);
            $table->setup();
        }
        $cancancelreservations = has_capability('mod/facetoface:reserveother', $context);
        $canchangesignedupjobassignment = has_capability('mod/facetoface:changesignedupjobassignment', $context);

        foreach ($rows as $attendee) {
            if (!empty($attendee->deleted)) {
                continue;
            }
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
                    $url = new moodle_url('/mod/facetoface/attendees/ajax/job_assignment.php', array('s' => $seminarevent->get_id(), 'id' => $attendee->id));
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

            $timecancelled = isset($attendee->timecancelled) ? $attendee->timecancelled : $attendee->timecreated;
            $data[] = userdate($timecancelled, get_string('strftimedatetime'));
            if ($attendee->statuscode == \mod_facetoface\signup\state\user_cancelled::get_code()) {
                $data[] = get_string('usercancelled', 'facetoface');
            } else if ($attendee->statuscode == \mod_facetoface\signup\state\event_cancelled::get_code()) {
                $data[] = get_string('sessioncancelled', 'facetoface');
            } else {
                // Who knows!
                debugging('Unexpected cancellation type encountered.', DEBUG_DEVELOPER);
                $data[] = get_string('usercancelled', 'facetoface');
            }

            $icon = '';
            if (has_capability('mod/facetoface:manageattendeesnote', $context)) {
                $url = new moodle_url('/mod/facetoface/attendees/ajax/usercancellation_notes.php',
                    array('s' => $seminarevent->get_id(), 'userid' => $attendee->id));
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
