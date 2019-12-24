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
 * @author Piers Harding <piers@catalyst.net.nz>
 * @package totara
 * @subpackage message
 */

/**
 * Displays collaborative features for the current user
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot.'/totara/reportbuilder/lib.php');

// Initialise jquery requirements.
require_once($CFG->dirroot.'/totara/core/js/lib/setup.php');

require_login();

global $USER;

$sid = optional_param('sid', '0', PARAM_INT);
$format = optional_param('format', '',PARAM_TEXT); //export format
$debug  = optional_param('debug', 0, PARAM_INT);

// Default to current user.
$id = $USER->id;

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/totara/message/tasks.php');
$PAGE->set_pagelayout('noblocks');

$strheading = get_string('tasks', 'totara_message');

$shortname = 'tasks';
$data = array(
    'userid' => $id,
);
if (!$report = reportbuilder_get_embedded_report($shortname, $data, false, $sid)) {
    print_error('error:couldnotgenerateembeddedreport', 'totara_reportbuilder');
}

$report->defaultsortcolumn = 'message_values_sent';
$report->defaultsortorder = 3;

$logurl = $PAGE->url->out_as_local_url();
if ($format!='') {
    $report->export_data($format);
    die;
}

\totara_reportbuilder\event\report_viewed::create_from_report($report)->trigger();

$report->include_js();
$PAGE->requires->js_init_call('M.totara_message.init');

///
/// Display the page.
///
$PAGE->navbar->add($strheading);
$PAGE->set_title($strheading);
$PAGE->set_button($report->edit_button());
$PAGE->set_heading($strheading);

/** @var totara_reportbuilder_renderer $output */
$output = $PAGE->get_renderer('totara_reportbuilder');

echo $output->header();

// This must be done after the header and before any other use of the report.
list($reporthtml, $debughtml) = $output->report_html($report, $debug);
echo $debughtml;

echo $output->heading($strheading, 2);

$report->display_restrictions();

// Display heading including filtering stats.
$countfiltered = $report->get_filtered_count();
if ($report->can_display_total_count()) {
    $resultstr = 'recordsshown';
    $a = new stdClass();
    $a->countfiltered = $countfiltered;
    $a->countall = $report->get_full_count();
} else{
    $resultstr = 'recordsall';
    $a = $countfiltered;
}
echo $output->heading(get_string($resultstr, 'totara_message', $a), 3);

if (empty($report->description)) {
    $report->description = get_string('task_description', 'totara_message');
}

echo $output->print_description($report->description, $report->_id);

$report->display_search();
$report->display_sidebar_search();

// Print saved search buttons if appropriate.
echo $report->display_saved_search_options();

$PAGE->requires->string_for_js('reviewitems', 'block_totara_alerts');
$PAGE->requires->js_init_call('M.totara_message.dismiss_input_toggle');

echo $output->showhide_button($report->_id, $report->shortname);
echo html_writer::start_tag('form', array('id' => 'totara_messages', 'name' => 'totara_messages',
        'action' => new moodle_url('/totara/message/action.php'),  'method' => 'post'));
echo $reporthtml;
if ($countfiltered > 0) {
    totara_message_action_button('dismiss');
    totara_message_action_button('accept');
    totara_message_action_button('reject');

    $out = $output->box_start('generalbox', 'totara_message_actions');
    $out .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'returnto', 'value' => $FULLME));
    $dismisslink = html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'dismiss', 'id' => 'totara-dismiss',
            'disabled' => 'true', 'value' => get_string('dismiss', 'totara_message'))) .
            html_writer::tag('noscript', get_string('noscript', 'totara_message'));
    $out .= get_string('withselected', 'totara_message');
    $out .= $dismisslink;
    $out .= $output->box_end();
    echo $out;
    totara_message_checkbox_all_none();
}
echo html_writer::end_tag('form');

// Export button.
$output->export_select($report, $sid);

echo $output->footer();
