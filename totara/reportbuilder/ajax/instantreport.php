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
 * @author Nathan Lewis <nathan.lewis@totaralms.com>
 * @package totara
 * @subpackage reportbuilder
 */

/**
 * Page for returning report table for AJAX call
 *
 * NOTE: this is cloned in /blocks/totara_report_table/ajax_instantreport.php
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

// Send the correct headers.
send_headers('text/html; charset=utf-8', false);

require_sesskey();

$id = required_param('id', PARAM_INT);
$debug = optional_param('debug', 0, PARAM_INT);
$searched = optional_param_array('submitgroup', array(), PARAM_ALPHANUM);
$sid =  optional_param('sid', 0, PARAM_INT);

$PAGE->set_context(context_system::instance());

// Verify global restrictions.
$reportrecord = $DB->get_record('report_builder', array('id' => $id), '*', MUST_EXIST);
$globalrestrictionset = rb_global_restriction_set::create_from_page_parameters($reportrecord);

// Create the report object. Includes embedded report capability checks.
$config = (new rb_config())->set_sid($sid)->set_global_restriction_set($globalrestrictionset);
$report = reportbuilder::create($id, $config);

// Decide if require_login should be executed.
if ($report->needs_require_login()) {
    require_login();
}

// Checks that the report is one that is returned by get_permitted_reports.
if (!reportbuilder::is_capable($id)) {
    print_error('nopermission', 'totara_reportbuilder');
}

if (!empty($report->embeddedurl)) {
    $PAGE->set_url($report->embeddedurl);
} else {
    $PAGE->set_url('/totara/reportbuilder/report.php', array('id' => $id));
}

$PAGE->set_pagelayout('noblocks');

\totara_reportbuilder\event\report_viewed::create_from_report($report)->trigger();

/** @var totara_reportbuilder_renderer $output */
$output = $PAGE->get_renderer('totara_reportbuilder');

// This must be done after the header and before any other use of the report.
list($reporthtml, $debughtml) = $output->report_html($report, $debug);
echo $debughtml;

// Construct the output which consists of a report, header and (eventually) sidebar filter counts.
// We put the data in a container so that jquery can search inside it.
echo html_writer::start_div('instantreportcontainer');

// Show report results.
echo $reporthtml;
$report->display_sidebar_search();

// Display heading including filtering stats.
echo $output->result_count_info($report);

// Close the container.
echo html_writer::end_div();
