<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Rob tyler <rob.tyler@totaralearning.com>
 * @package core_admin
 */

require_once('../config.php');

// If the legacy report should be used include it instead of this page.
if (!empty($CFG->uselegacybrowselistofusersreport)) {
    include(__DIR__ . "/user_legacy.php");
    die;
}

require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

// Reportbuilder basic arguments
$debug = optional_param('debug', false, PARAM_BOOL); // Debug reportbuilder.
$sid = optional_param('sid', '0', PARAM_INT);
$format = optional_param('format', '', PARAM_TEXT); // Export format.

admin_externalpage_setup('editusers');

// Before any actions make sure user may actually access this report.
$reportshortname = 'system_browse_users';
$reportrecord = $DB->get_record('report_builder', array('shortname' => $reportshortname));
$globalrestrictionset = rb_global_restriction_set::create_from_page_parameters($reportrecord);
$config = (new rb_config())->set_global_restriction_set($globalrestrictionset);
$report = reportbuilder::create_embedded($reportshortname, $config);
if (!$report) {
    print_error('error:couldnotgenerateembeddedreport', 'totara_reportbuilder');
}

if ($format != '') {
    $report->export_data($format);
    exit();
}

\totara_reportbuilder\event\report_viewed::create_from_report($report)->trigger();

$report->include_js();

$PAGE->set_title($report->fullname);
$PAGE->set_button($report->edit_button() . $PAGE->button);

echo $OUTPUT->header();

/** @var totara_reportbuilder_renderer $renderer */
$renderer = $PAGE->get_renderer('totara_reportbuilder');

list($reporthtml, $debughtml) = $renderer->report_html($report, $debug);
echo $debughtml;

$a = $renderer->result_count_info($report);
echo $OUTPUT->heading(get_string('userreportheading', 'totara_reportbuilder', $a));

$report->display_restrictions();

echo $renderer->print_description($report->description, $report->_id);

$report->display_search();
$report->display_sidebar_search();

if (has_capability('moodle/user:create', context_system::instance())) {
    $url = new moodle_url('/user/editadvanced.php', array('id' => -1, 'returnto' => 'allusers'));
    echo $OUTPUT->single_button($url, get_string('addnewuser'), 'get');

    echo $reporthtml;

    $url = new moodle_url('/user/editadvanced.php', array('id' => -1, 'returnto' => 'allusers'));
    echo $OUTPUT->single_button($url, get_string('addnewuser'), 'get');
} else {
    echo $reporthtml;
}

// Spreadsheet export. No need to check capability. They should see the same data as in the report.
$renderer->export_select($report, $sid);

echo $OUTPUT->footer();
