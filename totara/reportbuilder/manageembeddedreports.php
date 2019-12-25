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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_reportbuilder
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

$debug  = optional_param('debug', 0, PARAM_INT);
$sid = optional_param('sid', '0', PARAM_INT);
$format = optional_param('format', '', PARAM_TEXT); // export format

$context = context_system::instance();
$PAGE->set_context($context);

$pageparams = [
    'format' => $format,
    'debug' => $debug,
];

// Generate any missing embedded reports when we load this page.
reportbuilder::generate_embedded_reports();

$config = (new rb_config())->set_sid($sid)->set_embeddata($pageparams);
if (!$report = reportbuilder::create_embedded('manage_embedded_reports', $config)) {
    print_error('error:couldnotgenerateembeddedreport', 'totara_reportbuilder');
}

$url = new moodle_url('/totara/reportbuilder/manageembeddedreports.php', $pageparams);
admin_externalpage_setup('rbmanageembeddedreports', '', null, $url);

$PAGE->set_button($report->edit_button() . $PAGE->button);

/** @var totara_reportbuilder_renderer $renderer */
$renderer = $PAGE->get_renderer('totara_reportbuilder');

if ($format != '') {
    $report->export_data($format);
    die;
}

\totara_reportbuilder\event\report_viewed::create_from_report($report)->trigger();

$report->include_js();

echo $OUTPUT->header();

// This must be done after the header and before any other use of the report.
list($reporthtml, $debughtml) = $renderer->report_html($report, $debug);
echo $debughtml;

echo $OUTPUT->heading(get_string('manageembeddedreports','totara_reportbuilder'));

$report->display_restrictions();

$heading = $renderer->result_count_info($report);

echo $OUTPUT->heading($heading, 3);
echo $renderer->print_description($report->description, $report->_id);

$report->display_search();

// Print saved search buttons if appropriate.
echo $report->display_saved_search_options();
echo $reporthtml;

// Export button.
$renderer->export_select($report, $sid);

echo $OUTPUT->footer();
