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
 * @package totara
 * @subpackage reportbuilder
 */

/**
 * Page for displaying user generated reports
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');

$format = optional_param('format', '', PARAM_ALPHANUM);
$id = required_param('id', PARAM_INT);
$sid = optional_param('sid', '0', PARAM_INT);
$debug = optional_param('debug', 0, PARAM_INT);

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/totara/reportbuilder/report.php', array('id' => $id));
$PAGE->set_totara_menu_selected('\totara_core\totara\menu\myreports');
$PAGE->set_pagelayout('noblocks');

// We can rely on the report builder record existing here as there is no way to get directly to report.php.
$reportrecord = $DB->get_record('report_builder', array('id' => $id), '*', MUST_EXIST);

// Embedded reports can only be viewed through their embedded url.
if ($reportrecord->embedded) {
    print_error('cannotviewembedded', 'totara_reportbuilder');
}

// Verify global restrictions.
$globalrestrictionset = rb_global_restriction_set::create_from_page_parameters($reportrecord);

// New report object.
$config = new rb_config();
$config->set_sid($sid)->set_global_restriction_set($globalrestrictionset);
$report = reportbuilder::create($id, $config, true);

$report->handle_pre_display_actions();

if ($format != '') {
    $report->export_data($format);
    die;
}

\totara_reportbuilder\event\report_viewed::create_from_report($report)->trigger();

$PAGE->requires->string_for_js('reviewitems', 'block_totara_alerts');
$report->include_js();

$fullname = format_string($report->fullname, true, ['context' => $context]);
$pagetitle = get_string('report', 'totara_reportbuilder').': '.$fullname;

$PAGE->set_title($pagetitle);
$PAGE->set_button($report->edit_button());
$PAGE->navbar->add(get_string('reports', 'totara_core'), new moodle_url('/my/reports.php'));
$PAGE->navbar->add($fullname);
$PAGE->set_heading(format_string($SITE->fullname));

/** @var totara_reportbuilder_renderer $output */
$output = $PAGE->get_renderer('totara_reportbuilder');

echo $output->header();

if ($report->has_disabled_filters()) {
    global $OUTPUT;
    echo $OUTPUT->notification(get_string('filterdisabledwarning', 'totara_reportbuilder'), 'warning');
}

// This must be done after the header and before any other use of the report.
list($tablehtml, $debughtml) = $output->report_html($report, $debug);

$report->display_redirect_link();
$report->display_restrictions();

// Display heading including filtering stats.
$heading = $fullname . ': ' . $output->result_count_info($report);
echo $output->heading($heading);
echo $debughtml;

// print report description if set
echo $output->print_description($report->description, $report->_id);

// print filters
$report->display_search();
$report->display_sidebar_search();

// print saved search buttons if appropriate
echo $report->display_saved_search_options();

// Show results.
echo $output->showhide_button($report->_id, $report->shortname);
echo $tablehtml;


// Export button.
$output->export_select($report, $sid);

echo $output->footer();
