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
 * @author     Aaron Wells <aaronw@catalyst.net.nz>
 * @package totara
 * @subpackage totara_plan
 *
 */

/**
 * Displays collaborative features for the current user
 *
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/totara/reportbuilder/lib.php');
require_once($CFG->dirroot.'/totara/plan/lib.php');
require_once($CFG->dirroot . '/totara/program/lib.php');

require_login();

if (totara_feature_disabled('recordoflearning')) {
    print_error('error:recordoflearningdisabled', 'totara_plan');
}

// Check if programs are enabled.
check_program_enabled();

$programid = optional_param('programid', null, PARAM_INT);
$history = optional_param('history', false, PARAM_BOOL);
$userid     = optional_param('userid', $USER->id, PARAM_INT); // Which user to show.
$sid = optional_param('sid', '0', PARAM_INT);
$format = optional_param('format','', PARAM_TEXT); // Export format.
$rolstatus = optional_param('status', 'all', PARAM_ALPHANUM);
$debug  = optional_param('debug', 0, PARAM_INT);
// Set status.
if (!in_array($rolstatus, array('active','completed','all'))) {
    $rolstatus = 'all';
}
// Set user.
if (!$user = $DB->get_record('user', array('id' => $userid))) {
    print_error('error:usernotfound', 'totara_plan');
}
// Set program.
if (!empty($programid) && (!$program = $DB->get_record('prog', array('id' => $programid), 'fullname'))) {
    print_error(get_string('programnotfound', 'totara_plan'));
}

$context = context_system::instance();

$pageparams = array(
    'userid' => $userid,
    'status' => $rolstatus
);
if ($programid) {
    $pageparams['programid'] = $programid;
}
if ($history) {
    $pageparams['history'] = $history;
}
if ($format) {
    $pageparams['format'] = $format;
}
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/totara/plan/record/programs.php', $pageparams));
$PAGE->set_pagelayout('report');

/** @var totara_reportbuilder_renderer $renderer */
$renderer = $PAGE->get_renderer('totara_reportbuilder');

if ($USER->id != $userid) {
    $strheading = get_string('recordoflearningforname', 'totara_core', fullname($user, true));
} else {
    $strheading = get_string('recordoflearning', 'totara_core');
}
// Get subheading name for display.
$strsubheading = get_string($rolstatus.'programssubhead', 'totara_plan');

$shortname = 'plan_programs';
$data = array(
    'userid' => $userid,
    'exceptionstatus' => 0,
);
if ($rolstatus !== 'all') {
    $data['rolstatus'] = $rolstatus;
}
if ($history) {
    $shortname = 'plan_programs_completion_history';
    if (!empty($programid)) {
        $data['programid'] = $programid;
        $strsubheading = get_string('programscompletionhistoryforsubhead', 'totara_plan', $program->fullname);
    } else {
        $strsubheading = get_string('programscompletionhistorysubhead', 'totara_plan');
    }
}
// Set report.
if (!$report = reportbuilder_get_embedded_report($shortname, $data, false, $sid)) {
    print_error('error:couldnotgenerateembeddedreport', 'totara_reportbuilder');
}

$logurl = $PAGE->url->out_as_local_url();
if ($format != '') {
    $report->export_data($format);
    die;
}

\totara_reportbuilder\event\report_viewed::create_from_report($report)->trigger();

$report->include_js();

// Display the page.
$ownplan = $USER->id == $userid;
$usertype = ($ownplan) ? 'learner' : 'manager';
if ($usertype == 'manager') {
    if (totara_feature_visible('myteam')) {
        $menuitem = 'myteam';
        $url = new moodle_url('/my/teammembers.php');
        $PAGE->navbar->add(get_string('team', 'totara_core'), $url);
    } else {
        $menuitem = null;
        $url = null;
    }
} else {
    $menuitem = null;
    $url = null;
}
$PAGE->navbar->add($strheading, new moodle_url('/totara/plan/record/index.php', array('userid' => $userid)));
$PAGE->navbar->add($strsubheading);
$PAGE->set_title($strheading);
$PAGE->set_button($report->edit_button());
$PAGE->set_heading(format_string($SITE->fullname));

$menuitem = ($ownplan) ? 'recordoflearning' : 'myteam';
$PAGE->set_totara_menu_selected($menuitem);
dp_display_plans_menu($userid, 0, $usertype, 'programs', $rolstatus);

echo $OUTPUT->header();

// This must be done after the header and before any other use of the report.
list($reporthtml, $debughtml) = $renderer->report_html($report, $debug);
echo $debughtml;

echo $OUTPUT->container_start('', 'dp-plan-content');
echo $OUTPUT->heading($strheading.' : '.$strsubheading);

$currenttab = 'programs';
dp_print_rol_tabs($rolstatus, $currenttab, $userid);

$report->display_restrictions();

$heading = $renderer->result_count_info($report);
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

echo $OUTPUT->container_end();
echo $OUTPUT->footer();
