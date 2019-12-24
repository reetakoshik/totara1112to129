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
 * @package totara_program
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/totara/program/lib.php');
require_once($CFG->dirroot.'/totara/certification/lib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

$debug = optional_param('debug', 0, PARAM_INT);
$programid = required_param('programid', PARAM_INT);

// Check capabilities.
ajax_require_login();

$program = new program($programid);
require_capability('totara/program:configureassignments', $program->get_context());
$program->check_enabled();
$iscertif = $program->is_certif();

$PAGE->set_url('/totara/program/assignment/duedates_report.php', array('program' => $programid));
$PAGE->set_context(context_system::instance());
/** @var totara_reportbuilder_renderer $renderer */
$renderer = $PAGE->get_renderer('totara_reportbuilder');

if (!empty($program->certifid)) {
    $reportrecord = $DB->get_record('report_builder', array('shortname' => 'cert_assignment_duedates'));
    $globalrestrictionset = rb_global_restriction_set::create_from_page_parameters($reportrecord);
    if (!$report = reportbuilder_get_embedded_report('cert_assignment_duedates', null, false, 0, $globalrestrictionset)) {
        print_error('error:couldnotgenerateembeddedreport', 'totara_reportbuilder');
    }
} else {
    $reportrecord = $DB->get_record('report_builder', array('shortname' => 'program_assignment_duedates'));
    $globalrestrictionset = rb_global_restriction_set::create_from_page_parameters($reportrecord);
    if (!$report = reportbuilder_get_embedded_report('program_assignment_duedates', null, false, 0, $globalrestrictionset)) {
        print_error('error:couldnotgenerateembeddedreport', 'totara_reportbuilder');
    }
}

// This must be done after the header and before any other use of the report.
list($reporthtml, $debughtml) = $renderer->report_html($report, $debug);
echo $debughtml;

$heading = get_string('actualduedates', 'totara_program');
echo $renderer->heading($heading);

$report->display_search();
$report->display_sidebar_search();

echo $reporthtml;
