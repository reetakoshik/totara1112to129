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
$action            = optional_param('action', 'attendees', PARAM_ALPHA);
// Only return content
$onlycontent       = optional_param('onlycontent', false, PARAM_BOOL);
// Export download.
$download = optional_param('download', '', PARAM_ALPHA);
// Back to all sessions.
$backtoallsessions = optional_param('backtoallsessions', 1, PARAM_BOOL);

// Report support.
$format = optional_param('format','',PARAM_TEXT);
$sid = optional_param('sid', '0', PARAM_INT);
$debug = optional_param('debug', 0, PARAM_INT);

// If there's no sessionid specified.
if (!$s) {
    process_no_sessionid('view');
    exit;
}

list($session, $facetoface, $course, $cm, $context) = facetoface_get_env_session($s);
$seminarevent = new \mod_facetoface\seminar_event($s);
$seminar = new \mod_facetoface\seminar($seminarevent->get_facetoface());

require_login($course, false, $cm);

// Setup urls
$baseurl = new moodle_url('/mod/facetoface/attendees/view.php', array('s' => $seminarevent->get_id()));

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

$actionallowed = in_array($action, $allowed_actions);

/**
 * Handle actions
 */
$show_table = false;
$heading_message = '';
$params = array('sessionid' => $s);
$cols = array();
$actions = array();
$exports = array();

if ($actionallowed) {
    $heading = get_string('attendees', 'facetoface');
    // Check if any dates are set
    if (!$seminarevent->get_mintimestart()) {
        $heading_message = get_string('sessionnoattendeesaswaitlist', 'facetoface');
    }
    // Get list of actions
    if ($seminarevent->get_cancelledstatus() == 0) {
        if (has_capability('mod/facetoface:addattendees', $context)) {
            $actions['add']    = get_string('addattendees', 'mod_facetoface');
            $actions['bulkaddfile']  = get_string('addattendeesviafileupload', 'mod_facetoface');
            $actions['bulkaddinput'] = get_string('addattendeesviaidlist', 'mod_facetoface');
        }
        if (has_capability('mod/facetoface:removeattendees', $context)) {
            $actions['remove'] = get_string('removeattendees', 'mod_facetoface');
        }
    }
    // Verify global restrictions and process report early before any output is done (required for export).
    $shortname = 'facetoface_sessions';
    $reportrecord = $DB->get_record('report_builder', array('shortname' => $shortname));
    $globalrestrictionset = rb_global_restriction_set::create_from_page_parameters($reportrecord);

    $attendancestatuses = array(\mod_facetoface\signup\state\booked::get_code(), \mod_facetoface\signup\state\fully_attended::get_code(), \mod_facetoface\signup\state\not_set::get_code(),
        \mod_facetoface\signup\state\no_show::get_code(), \mod_facetoface\signup\state\partially_attended::get_code());

    $config = (new rb_config())
        ->set_embeddata(['sessionid' => $s, 'status' => $attendancestatuses])
        ->set_sid($sid)
        ->set_global_restriction_set($globalrestrictionset);
    if (!$report = reportbuilder::create_embedded($shortname, $config)) {
        print_error('error:couldnotgenerateembeddedreport', 'totara_reportbuilder');
    }
    $report->set_baseurl($baseurl);

    if ($format != '') {
        $report->export_data($format);
        die;
    }

    $report->include_js();
    $PAGE->set_button($report->edit_button());

    // We will show embedded report.
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
    // Get list of attendees
    if (!$download) {
        //output overbooked notifications
        $numattendees = facetoface_get_num_attendees($seminarevent->get_id());
        $overbooked = ($numattendees > $seminarevent->get_capacity());
        if ($overbooked) {
            $overbookedmessage = get_string('capacityoverbookedlong', 'facetoface', array('current' => $numattendees, 'maximum' => $seminarevent->get_capacity()));
            echo $OUTPUT->notification($overbookedmessage, 'notifynotice');
        }
        //output the section heading
        echo $OUTPUT->heading($heading);
    }

    $report->display_restrictions();

    // Actions menu.
    if ($actions) {
        echo $OUTPUT->container_start('actions last');
        // Action selector
        echo html_writer::label(get_string('attendeeactions', 'mod_facetoface'), 'menuf2f-actions', true, array('class' => 'sr-only'));
        echo html_writer::select($actions, 'f2f-actions', '', array('' => get_string('actions')));
        echo $OUTPUT->container_end();
    }

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

    // Session downloadable sign in sheet.
    if ($seminarevent->is_sessions() && has_capability('mod/facetoface:exportsessionsigninsheet', $context)) {
        $downloadsheetattendees = facetoface_get_attendees($seminarevent->get_id(), $attendancestatuses);
        if (!empty($downloadsheetattendees)) {
            // We need the dates, and we only want to show this option if there are one or more dates.
            $formurl = new moodle_url('/mod/facetoface/reports/signinsheet.php');
            $signinform = new \mod_facetoface\form\signin($formurl, $session);
            echo html_writer::start_div('f2fdownloadsigninsheet');
            $signinform->display();
            echo html_writer::end_div();
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
