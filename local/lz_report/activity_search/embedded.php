<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * program completion report
 *
 * @package    local_packages
 * @copyright  2014 onwards Dennis Dobrenko <dennis.dobrenko@kineo.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Loading the library.
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

$format = optional_param('format', '', PARAM_TEXT); // Export format.
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('noblocks');
$PAGE->set_url('/local/lz_report/activity_search/embedded.php');

if ($CFG->forcelogin) {
    require_login();
}

$renderer = $PAGE->get_renderer('totara_reportbuilder');
$strheading = get_string('sourcetitle', 'rb_source_activities');
$shortname = 'activities';
$sid = optional_param('sid', '0', PARAM_INT);
$debug  = optional_param('debug', 0, PARAM_INT);

if (!$report = reportbuilder_get_embedded_report($shortname, null, false, $sid)) {
    print_error('error:couldnotgenerateembeddedreport', 'totara_reportbuilder');
}

$logurl = $PAGE->url->out_as_local_url();
if ($format != '') {
    $report->export_data($format);
    die;
}


$report->include_js();

$fullname = format_string($report->fullname);
$pagetitle = format_string(get_string('report', 'totara_core') . ': ' . $fullname);

$PAGE->set_pagelayout('noblocks');
$PAGE->set_title($pagetitle);
$PAGE->set_button($report->edit_button());
$PAGE->set_heading($fullname);
echo $OUTPUT->header();

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
echo $OUTPUT->heading(get_string($resultstr, 'totara_message', $a), 3);

echo $renderer->print_description($report->description, $report->_id);

$report->display_sidebar_search();

if ($debug) {
    $report->debug($debug);
}

echo "<div id='activitysearch'>";
$report->display_search();

// Print saved search buttons if appropriate.
echo $report->display_saved_search_options();

if ($countfiltered > 0) {
    echo $renderer->showhide_button($report->_id, $report->shortname);
    $report->display_table();
    // Export button.
    $renderer->export_select($report->_id, $sid);
}

echo "</div>";
echo $OUTPUT->footer();

