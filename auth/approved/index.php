<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 *
 * @package auth_approved
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');
require_once($CFG->libdir . '/adminlib.php');

$sid = optional_param('sid', 0, PARAM_INT);
$debug = optional_param('debug', 0, PARAM_BOOL);
$bulkaction = optional_param('bulkaction', '', PARAM_ALPHANUMEXT);
$bulktime = optional_param('bulktime', 0, PARAM_INT);

$syscontext = context_system::instance();
admin_externalpage_setup('authapprovedpending', '', null, '', array('pagelayout'=>'report'));

$config = (new rb_config())->set_sid($sid)->set_nocache(true);
$report = reportbuilder::create_embedded('auth_approved_pending_requests', $config);

$bulkactions = \auth_approved\bulk::get_actions_menu();
if ($bulkaction and isset($bulkactions[$bulkaction])) {
    require_sesskey();
    \auth_approved\bulk::execute_action($bulkaction, $report, $bulktime);
    // We should not get here, but if we do let's go back to the report.
    redirect($report->get_current_url());
}

/** @var totara_reportbuilder_renderer|core_renderer $output */
$output = $PAGE->get_renderer('totara_reportbuilder');

$PAGE->set_button($report->edit_button());

echo $output->header();

// Generate the report HTML and debug info - this also caches counts in an optimal way.
list($reporthtml, $debughtml) = $output->report_html($report, $debug);
echo $debughtml;

$strheading = get_string('reportpending', 'auth_approved');
$heading = $strheading . ': ' . $output->result_count_info($report);
echo $output->heading($heading);

$report->display_search();
$report->display_sidebar_search();

echo $report->display_saved_search_options();

echo $reporthtml;

if ($report->get_filtered_count() and $bulkactions) {
    $bulkform = new \auth_approved\form\bulk_actions($report->get_current_url(), array('actions' => $bulkactions, 'count' => $report->get_filtered_count()));
    $bulkform->display();
}

echo $output->footer();
