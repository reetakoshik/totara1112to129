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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @author Piers Harding <piers@catalyst.net.nz>
 * @package totara
 * @subpackage reportbuilder
 */

require_once(dirname(dirname(__FILE__)).'/config.php');
require_once($CFG->dirroot.'/totara/reportbuilder/lib.php');

$format = optional_param('format','', PARAM_TEXT); // export format
$debug = optional_param('debug', 0, PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('noblocks');
$PAGE->set_totara_menu_selected('\totara_coursecatalog\totara\menu\courses');
$PAGE->set_url('/course/find.php');

if ($CFG->forcelogin) {
    require_login();
}

/** @var totara_reportbuilder_renderer $renderer */
$renderer = $PAGE->get_renderer('totara_reportbuilder');
$strheading = get_string('searchcourses', 'totara_core');
$shortname = 'findcourses';
$sid = optional_param('sid', '0', PARAM_INT);

$config = (new rb_config())->set_sid($sid);
if (!$report = reportbuilder::create_embedded($shortname, $config)) {
    print_error('error:couldnotgenerateembeddedreport', 'totara_reportbuilder');
}

$logurl = $PAGE->url->out_as_local_url();
if ($format != '') {
    $report->export_data($format);
    die;
}

\totara_reportbuilder\event\report_viewed::create_from_report($report)->trigger();

$report->include_js();

$fullname = format_string($report->fullname);
$pagetitle = format_string(get_string('report','totara_core').': '.$fullname);

$PAGE->set_pagelayout('admin');
$PAGE->navbar->add($fullname, new moodle_url("/course/find.php"));
$PAGE->navbar->add(get_string('search'));
$PAGE->set_title($pagetitle);
$PAGE->set_button($report->edit_button());
$PAGE->set_heading($fullname);
echo $OUTPUT->header();

// This must be done after the header and before any other use of the report.
list($reporthtml, $debughtml) = $renderer->report_html($report, $debug);
echo $debughtml;

$report->display_restrictions();

$heading = $strheading . ': ' . $renderer->result_count_info($report);
echo $OUTPUT->heading($heading);

echo $renderer->print_description($report->description, $report->_id);

$report->display_search();
$report->display_sidebar_search();

// Print saved search buttons if appropriate.
echo $report->display_saved_search_options();
echo $renderer->showhide_button($report->_id, $report->shortname);
echo $reporthtml;

// Export button.
$renderer->export_select($report, $sid);

echo $OUTPUT->footer();
