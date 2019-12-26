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
 * @author Russell England <russell.england@catalyst-eu.net>
 * @package totara
 * @subpackage totara_plan
 */
/**
 * Displays certifications for the current user
 *
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot.'/totara/reportbuilder/lib.php');
require_once($CFG->dirroot . '/totara/completionimport/lib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/totara/program/lib.php');

$importname = optional_param('importname', 'course', PARAM_ALPHA);
$timecreated = optional_param('timecreated', null, PARAM_INT);
$importuserid = optional_param('importuserid', null, PARAM_INT);
$format = optional_param('format', '', PARAM_TEXT);
$sid = optional_param('sid', '0', PARAM_INT);
$debug = optional_param('debug', 0, PARAM_INT);
$clearfilters = optional_param('clearfilters', 0, PARAM_INT);

$pageparams = array('importname' => $importname, 'clearfilters' => $clearfilters);
if (!empty($importuserid)) {
    $pageparams['importuserid'] = $importuserid;
}
if (!empty($timecreated)) {
    $pageparams['timecreated'] = $timecreated;
}

require_login();

// Check if certifications are enabled.
if ($importname === 'certification') {
    check_certification_enabled();
}

$context = context_system::instance();
$PAGE->set_context($context);

$shortname = 'completionimport_' . $importname;

// Verify global restrictions.
$reportrecord = $DB->get_record('report_builder', array('shortname' => $shortname));
$globalrestrictionset = rb_global_restriction_set::create_from_page_parameters($reportrecord);

$config = (new rb_config())
    ->set_sid($sid)
    ->set_embeddata($pageparams)
    ->set_global_restriction_set($globalrestrictionset);
if (!$report = reportbuilder::create_embedded($shortname, $config)) {
    print_error('error:couldnotgenerateembeddedreport', 'totara_reportbuilder');
}

$pageheading = get_string('pluginname', 'totara_completionimport');
$PAGE->set_heading(format_string($pageheading));
$PAGE->set_title(format_string($pageheading));
$PAGE->set_pagelayout('noblocks');
$PAGE->set_button($report->edit_button());
$pageparams['format'] = $format;
$pageparams['debug'] = $debug;
unset($pageparams['clearfilters']);
$url = new moodle_url('/totara/completionimport/viewreport.php', $pageparams);
admin_externalpage_setup('totara_completionimport_' . $importname, '', null, $url);

/** @var totara_reportbuilder_renderer $renderer */
$renderer = $PAGE->get_renderer('totara_reportbuilder');

if ($format != '') {
    $report->export_data($format);
    die;
}

$report->include_js();

echo $OUTPUT->header();

// This must be done after the header and before any other use of the report.
list($reporthtml, $debughtml) = $renderer->report_html($report, $debug);

if (!empty($importuserid) || !empty($timecreated)) {
    $clearembeddedparams = get_string('viewingwithembeddedfilters', 'totara_completionimport') . ':<br>';
    if (!empty($importuserid)) {
        $importuser = $DB->get_record('user', array('id' => $importuserid), '*', MUST_EXIST);
        $clearembeddedparams .= get_string('importedby', 'totara_completionimport') . ': ' . fullname($importuser) . '<br>';
    }
    if (!empty($timecreated)) {
        $clearembeddedparams .= get_string('timeuploaded', 'totara_completionimport') . ': ';
        $clearembeddedparams .= userdate($timecreated, get_string('strfdateattime', 'langconfig')) . '<br>';
    }
    unset($pageparams['importuserid']);
    unset($pageparams['timecreated']);
    $showalluploadsurl = new moodle_url('/totara/completionimport/viewreport.php', $pageparams);
    $clearembeddedparams .= $OUTPUT->action_link($showalluploadsurl, get_string('clearembeddedfilters', 'totara_completionimport'));
    echo $OUTPUT->notification($clearembeddedparams, 'notifymessage');
}

// Standard report stuff.
echo $OUTPUT->container_start('', 'completion_import');

$report->display_restrictions();

$heading = $renderer->result_count_info($report);
echo $OUTPUT->heading($heading);
echo $debughtml;
echo $renderer->print_description($report->description, $report->_id);

$report->display_search();
$report->display_sidebar_search();

echo $renderer->showhide_button($report->_id, $report->shortname);
echo $reporthtml;

// Export button.
$renderer->export_select($report, $sid);

echo $OUTPUT->container_end();

echo $OUTPUT->footer();
