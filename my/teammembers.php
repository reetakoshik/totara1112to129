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
 * @package totara
 * @subpackage my
 */

/* Displays information for the current user's team */

require_once(dirname(dirname(__FILE__)).'/config.php');
require_once($CFG->libdir.'/blocklib.php');
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->dirroot.'/tag/lib.php');
require_once($CFG->dirroot.'/totara/reportbuilder/lib.php');
require_once($CFG->dirroot.'/totara/plan/lib.php');

require_login();
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_pagetype('my-teammembers');
$PAGE->set_url(new moodle_url('/my/teammembers.php'));

if (totara_feature_disabled('myteam')) {
    redirect(new moodle_url('/'));
}

$edit = optional_param('edit', -1, PARAM_BOOL);
$sid = optional_param('sid', '0', PARAM_INT);
$format = optional_param('format', '', PARAM_TEXT); // Export format.
$debug  = optional_param('debug', 0, PARAM_INT);

/* Define the "Team Members" embedded report */
$strheading = get_string('teammembers', 'totara_core');

$shortname = 'team_members';

// Verify global restrictions.
$reportrecord = $DB->get_record('report_builder', array('shortname' => $shortname));
$globalrestrictionset = rb_global_restriction_set::create_from_page_parameters($reportrecord);

$config = (new rb_config())->set_sid($sid)->set_global_restriction_set($globalrestrictionset);
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

/* End of defining the report */

$PAGE->navbar->add(get_string('team', 'totara_core'));
$PAGE->navbar->add($strheading);

$editbutton = '';
if (!isset($USER->editing)) {
    $USER->editing = 0;
}
if ($PAGE->user_allowed_editing()) {
    $editbutton .= $OUTPUT->edit_button($PAGE->url);
    if ($edit == 1 && confirm_sesskey()) {
        $USER->editing = 1;
        $url = new moodle_url($PAGE->url, array('notifyeditingon' => 1));
        redirect($url);
    } else if ($edit == 0 && confirm_sesskey()) {
        $USER->editing = 0;
        redirect($PAGE->url);
    }
} else {
    $USER->editing = 0;
}

$PAGE->set_totara_menu_selected('\totara_core\totara\menu\myteam');
$PAGE->set_title($strheading);
$PAGE->set_heading(format_string($SITE->fullname));
$PAGE->set_button($report->edit_button().$editbutton);

/** @var totara_reportbuilder_renderer $renderer */
$renderer = $PAGE->get_renderer('totara_reportbuilder');
echo $OUTPUT->header();

// This must be done after the header and before any other use of the report.
list($reporthtml, $debughtml) = $renderer->report_html($report, $debug);
echo $debughtml;

// Plan page content.
echo $OUTPUT->container_start('', 'my-teammembers-content');

$report->display_restrictions();

$heading = $strheading . ': ' . $renderer->result_count_info($report);
echo $OUTPUT->heading($heading);

echo $renderer->print_description($report->description, $report->_id);

echo html_writer::tag('p', get_string('teammembers_text', 'totara_core'));

$report->display_search();
$report->display_sidebar_search();

// Print saved search buttons if appropriate.
echo $report->display_saved_search_options();
echo $reporthtml;

// Export button.
$renderer->export_select($report, $sid);

echo $OUTPUT->container_end();
echo $OUTPUT->footer();
