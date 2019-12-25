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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_facetoface
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

$sid = optional_param('sid', '0', PARAM_INT);
$format = optional_param('format', '', PARAM_TEXT);
$debug = optional_param('debug', 0, PARAM_INT);

$url = new moodle_url('/mod/facetoface/reports/events.php', array('format' => $format, 'debug' => $debug));
admin_externalpage_setup('modfacetofaceeventreport', '', null, $url);

// Verify global restrictions.
$reportrecord = $DB->get_record('report_builder', array('shortname' => 'facetoface_events'));
$globalrestrictionset = rb_global_restriction_set::create_from_page_parameters($reportrecord);

$config = (new rb_config())->set_sid($sid)->set_global_restriction_set($globalrestrictionset);
if (!$report = reportbuilder::create_embedded('facetoface_events', $config)) {
    print_error('error:couldnotgenerateembeddedreport', 'totara_reportbuilder');
}

if ($format != '') {
    $report->export_data($format);
    die;
}

$PAGE->set_button($report->edit_button() . $PAGE->button);

/** @var totara_reportbuilder_renderer $renderer */
$renderer = $PAGE->get_renderer('totara_reportbuilder');
echo $renderer->header();

// This must be done after the header and before any other use of the report.
list($reporthtml, $debughtml) = $renderer->report_html($report, $debug);
echo $debughtml;

$facetofacerenderer = $PAGE->get_renderer('mod_facetoface');
echo $facetofacerenderer->reports_management_tabs('facetofaceeventreport');

$report->display_restrictions();

$heading = get_string('eventreportcnt', 'mod_facetoface', $renderer->result_count_info($report));
echo $renderer->heading($heading);

echo $renderer->print_description($report->description, $report->_id);

$report->include_js();

$report->display_search();
$report->display_sidebar_search();

// Print saved search buttons if appropriate.
echo $report->display_saved_search_options();

echo $renderer->showhide_button($report->_id, $report->shortname);

echo $reporthtml;

$renderer->export_select($report->_id, 0);

echo $OUTPUT->footer();
