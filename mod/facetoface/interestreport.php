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
 * @author Andrew Hancox <andrewdchancox@googlemail.com>
 * @package totara
 * @subpackage facetoface
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');
require_once($CFG->dirroot . '/totara/program/lib.php');

$debug = optional_param('debug', 0, PARAM_INT);
$facetofaceid = optional_param('facetofaceid', 0, PARAM_INT);

if (!$facetofaceid) {
    $url = new moodle_url('/mod/facetoface/interestreport.php');
    $PAGE->set_url($url);
    $PAGE->set_context(context_system::instance());
    $PAGE->set_heading(format_string($SITE->fullname));
    $PAGE->set_title(get_string('declareinterestreport', 'mod_facetoface'));
    echo $OUTPUT->header();
    echo $OUTPUT->container(get_string('gettointerestreport', 'mod_facetoface', $url->out()));
    echo $OUTPUT->footer();
    exit;
}

$facetoface = $DB->get_record('facetoface', array('id' => $facetofaceid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $facetoface->course), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id, false, MUST_EXIST);
$context = context_module::instance($cm->id);
$PAGE->set_context($context);

$format = optional_param('format', '', PARAM_TEXT); // Export format.
$PAGE->set_pagelayout('standard');
$PAGE->set_cm($cm);
$url = new moodle_url('/mod/facetoface/interestreport.php', array('facetofaceid' => $facetofaceid));
$PAGE->set_url($url);
require_login();

// Verify global restrictions.
$shortname = 'facetoface_interest';
$reportrecord = $DB->get_record('report_builder', array('shortname' => $shortname));
$globalrestrictionset = rb_global_restriction_set::create_from_page_parameters($reportrecord);

if (!$report = reportbuilder_get_embedded_report($shortname, array('facetofaceid' => $facetofaceid), false, 0,
        $globalrestrictionset)) {
    print_error('error:couldnotgenerateembeddedreport', 'totara_reportbuilder');
}

$logurl = $PAGE->url->out_as_local_url();

\mod_facetoface\event\interest_report_viewed::create_from_facetoface($facetoface, $context)->trigger();

if ($format != '') {
    $report->export_data($format);
    die;
}

$report->include_js();

/** @var totara_reportbuilder_renderer $renderer */
$renderer = $PAGE->get_renderer('totara_reportbuilder');

$strheading = get_string('declareinterestreport', 'mod_facetoface') . ' - ' . $facetoface->name;

$PAGE->set_title($strheading);
$PAGE->set_button($report->edit_button());
$PAGE->set_heading($strheading);

echo $OUTPUT->header();

// This must be done after the header and before any other use of the report.
list($reporthtml, $debughtml) = $renderer->report_html($report, $debug);
echo $debughtml;

$report->display_restrictions();

echo $OUTPUT->heading($strheading);
echo $renderer->print_description($report->description, $report->_id);

$report->display_search();
$report->display_sidebar_search();
echo $reporthtml;

$renderer->export_select($report->_id, 0);

echo $OUTPUT->footer();
