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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package totara
 * @subpackage my
 */

require_once(dirname(dirname(__FILE__)).'/config.php');
require_once($CFG->dirroot.'/totara/reportbuilder/lib.php');

require_login();

$userid = optional_param('userid', $USER->id, PARAM_INT); // Which user to show.
$sid = optional_param('sid', '0', PARAM_INT);
$format = optional_param('format','', PARAM_TEXT); // Export format.
$edit = optional_param('edit', -1, PARAM_BOOL);
$debug = optional_param('debug', 0, PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/my/bookings.php', array('userid' => $userid, 'format' => $format)));
$PAGE->set_totara_menu_selected('\totara_core\totara\menu\mybookings');
$PAGE->set_pagelayout('standard');
$PAGE->set_pagetype('my-bookings');

if (!$user = $DB->get_record('user', array('id' => $userid))) {
    print_error('error:usernotfound', 'totara_core');
}

/** @var totara_reportbuilder_renderer $renderer */
$renderer = $PAGE->get_renderer('totara_reportbuilder');

if ($USER->id != $userid) {
    $strheading = get_string('bookingsfor', 'totara_core').fullname($user, true);
    if (totara_feature_visible('myteam')) {
        $menuitem = 'myteam';
        $url = new moodle_url('/my/teammembers.php');
        $PAGE->navbar->add(get_string('team', 'totara_core'), $url);
    } else {
        $menuitem = null;
        $url = null;
    }
} else {
    // Own bookings.
    $strheading = get_string('myfuturebookings', 'totara_core');
    $menuitem = null;
    $url = null;
}

$shortname = 'bookings';
$data = array(
    'userid' => $userid,
);
$config = (new rb_config())->set_sid($sid)->set_embeddata($data);
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

$fullname = $report->fullname;
$pagetitle = format_string(get_string('report', 'totara_core').': '.$fullname);

$PAGE->set_title($pagetitle);
$PAGE->set_heading(format_string($SITE->fullname));
$PAGE->navbar->add($strheading);

if (!isset($USER->editing)) {
    $USER->editing = 0;
}
$editbutton = '';
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

$PAGE->set_button($report->edit_button().$editbutton);

echo $OUTPUT->header();

$currenttab = "futurebookings";
include('booking_tabs.php');

// This must be done after the header and before any other use of the report.
list($reporthtml, $debughtml) = $renderer->report_html($report, $debug);
echo $debughtml;

$report->display_restrictions();

$heading = $strheading . ': ' . $renderer->result_count_info($report);
echo $OUTPUT->heading($heading);

print $renderer->print_description($report->description, $report->_id);

$report->display_search();
$report->display_sidebar_search();

// Print saved search buttons if appropriate.
echo $report->display_saved_search_options();

echo html_writer::empty_tag('br');

echo $renderer->showhide_button($report->_id, $report->shortname);

echo $reporthtml;

// Export button.
$renderer->export_select($report, $sid);

echo $OUTPUT->footer();