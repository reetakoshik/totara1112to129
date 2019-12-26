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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

use \mod_facetoface\signup\state\waitlisted;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');
require_once($CFG->dirroot.'/mod/facetoface/attendees/lib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');

/**
 * Load and validate base data
 */
// Face-to-face session ID
$s = optional_param('s', 0, PARAM_INT);
// Action being performed, a proper default will be set shortly.
// Require for attendees.js
$action = optional_param('action', 'waitlist', PARAM_ALPHA);
// Back to all sessions.
$backtoallsessions = optional_param('backtoallsessions', 1, PARAM_BOOL);
// Report support.
$format = optional_param('format','',PARAM_TEXT);
$sid = optional_param('sid', '0', PARAM_INT);
$debug = optional_param('debug', 0, PARAM_INT);

// If there's no sessionid specified.
if (!$s) {
    process_no_sessionid('waitlist');
    exit;
}

list($session, $facetoface, $course, $cm, $context) = facetoface_get_env_session($s);
$seminarevent = new \mod_facetoface\seminar_event($s);
$seminar = $seminarevent->get_seminar();

require_login($course, false, $cm);
/**
 * Print page header
 */
// Setup urls
$baseurl = new moodle_url('/mod/facetoface/attendees/waitlist.php', array('s' => $seminarevent->get_id()));
$PAGE->set_context($context);
$PAGE->set_url($baseurl);

list($allowed_actions, $available_actions, $staff, $admin_requests, $canapproveanyrequest, $cancellations, $requests, $attendees)
    = get_allowed_available_actions($seminar, $seminarevent, $context, $session);

// $allowed_actions is already set, so we can now know if the current action is allowed.
if (!in_array($action, $allowed_actions)) {
    // If no allowed actions so far.
    $return = new moodle_url('/mod/facetoface/view.php', array('f' => $seminar->get_id()));
    redirect($return);
}

$exports = array();
$pagetitle = format_string($seminar->get_name());
$PAGE->set_pagelayout('standard');
$PAGE->set_title($pagetitle);
$PAGE->set_cm($cm);
$PAGE->set_heading($course->fullname);

process_attendees_js($action, $seminar, $seminarevent);

$shortname = 'facetoface_waitlist';
$attendancestatuses = array(waitlisted::get_code());
// Verify global restrictions and process report early before any output is done (required for export).
$reportrecord = $DB->get_record('report_builder', array('shortname' => $shortname));
$globalrestrictionset = rb_global_restriction_set::create_from_page_parameters($reportrecord);
$config = (new rb_config())
    ->set_embeddata(['sessionid' => $s, 'status' => $attendancestatuses])
    ->set_sid($sid)
    ->set_global_restriction_set($globalrestrictionset);
if (!$report = reportbuilder::create_embedded($shortname, $config)) {
    print_error('error:couldnotgenerateembeddedreport', 'totara_reportbuilder');
}
if ($format != '') {
    $report->export_data($format);
    die;
}
$report->include_js();
$PAGE->set_button($report->edit_button());

/**
 * Print page content
 */
echo $OUTPUT->header();
echo $OUTPUT->box_start();
echo $OUTPUT->heading($pagetitle);

/** @var mod_facetoface_renderer $seminarrenderer */
$seminarrenderer = $PAGE->get_renderer('mod_facetoface');
echo $seminarrenderer->render_seminar_event($seminarevent, true, false, true);

require_once($CFG->dirroot.'/mod/facetoface/attendees/tabs.php'); // If needed include tabs
echo $OUTPUT->container_start('f2f-attendees-table');

/**
 * Print attendees (if user able to view)
 */
//attendees_helper::is_overbooked($seminarevent);
// Output the section heading.
echo $OUTPUT->heading(get_string('wait-list', 'mod_facetoface'));

$report->set_baseurl($baseurl);
$report->display_restrictions();
// Actions menu.
$actions = [];
if (has_capability('mod/facetoface:addattendees', $context)) {
    $actions['confirmattendees'] = get_string('confirmattendees', 'mod_facetoface');
}
if (has_capability('mod/facetoface:removeattendees', $context)) {
    $actions['cancelattendees']  = get_string('cancelattendees',  'mod_facetoface');
}
if (has_capability('mod/facetoface:addattendees', $context) && get_config(null, 'facetoface_lotteryenabled')) {
    $actions['playlottery'] = get_string('playlottery', 'mod_facetoface');
}
if (!empty($actions)) {
    $options = ['all' => get_string('all'), 'none' => get_string('none')];
    echo $OUTPUT->container_start('actions last');
    // Action selector
    echo html_writer::label(get_string('attendeeactions', 'mod_facetoface'), 'menuf2f-actions', true,
        array('class' => 'sr-only'));
    echo html_writer::select($options, 'f2f-select', '', ['' => get_string('selectwithdot', 'mod_facetoface')]);
    echo html_writer::select($actions, 'f2f-actions', '', array('' => get_string('actions')));
    echo $OUTPUT->help_icon('f2f-waitlist-actions', 'mod_facetoface');
    echo $OUTPUT->container_end();
}

$output = $PAGE->get_renderer('totara_reportbuilder');
// This must be done after the header and before any other use of the report.
list($reporthtml, $debughtml) = $output->report_html($report, $debug);
echo $debughtml;

// Print saved search buttons if appropriate.
$report->display_saved_search_options();
$report->display_search();
$report->display_sidebar_search();
echo $reporthtml;
$output->export_select($report, $sid);

if (has_any_capability(array('mod/facetoface:addattendees', 'mod/facetoface:removeattendees', 'mod/facetoface:takeattendance'), $context)) {
    if ($exports) {
        echo $OUTPUT->container_start('actions last');
        // Action selector.
        echo html_writer::label(get_string('attendeeactions', 'mod_facetoface'), 'menuf2f-actions', true, array('class' => 'sr-only'));
        echo html_writer::select($exports, 'f2f-actions', '', array('' => get_string('export', 'totara_reportbuilder')));
        echo $OUTPUT->container_end();
    }
}

// Go back.
if ($backtoallsessions) {
    $url = new moodle_url('/mod/facetoface/view.php', array('f' => $seminar->get_id()));
} else {
    $url = new moodle_url('/course/view.php', array('id' => $course->id));
}
echo html_writer::link($url, get_string('goback', 'mod_facetoface')) . html_writer::end_tag('p');
/**
 * Print page footer
 */
echo $OUTPUT->container_end();
echo $OUTPUT->box_end();
echo $OUTPUT->footer($course);

\mod_facetoface\event\attendees_viewed::create_from_session($session, $context, $action)->trigger();