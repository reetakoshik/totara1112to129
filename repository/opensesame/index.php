<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package repository_opensesame
 */

require(__DIR__ . '/../../config.php');
require_once("$CFG->dirroot/lib/adminlib.php");
require_once("$CFG->dirroot/repository/lib.php");
require_once("$CFG->dirroot/totara/reportbuilder/lib.php");

$sid = optional_param('sid', '0', PARAM_INT);
$format = optional_param('format', '', PARAM_ALPHANUMEXT);
$debug = optional_param('debug', 0, PARAM_INT);

admin_externalpage_setup('opensesamereport', '', null, '', array('pagelayout'=>'report'));

$opensesame = core_plugin_manager::instance()->get_plugin_info('repository_opensesame');
if (!$opensesame->is_enabled()) {
    redirect(new moodle_url('/repository/opensesame/register.php'));
}

$config = (new rb_config())->set_sid($sid);
$report = reportbuilder::create_embedded('opensesame', $config);
$PAGE->set_button($report->edit_button());

if (!empty($format)) {
    $report->export_data($format);
    die;
}

/** @var totara_reportbuilder_renderer $output */
$output = $PAGE->get_renderer('totara_reportbuilder');
echo $output->header();

$report->display_restrictions();

$strheading = get_string('embeddedreportname', 'rb_source_opensesame');
$heading = $strheading . ': ' . $output->result_count_info($report);
echo $output->heading($heading);
// This must be done after the header and before any other use of the report.
list($reporthtml, $debughtml) = $output->report_html($report, $debug);
echo $debughtml;

$report->display_search();
$report->display_sidebar_search();

echo $report->display_saved_search_options();

echo $reporthtml;
$output->export_select($report, $sid);

echo $output->footer();
