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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package totara
 * @subpackage reportbuilder
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot . '/totara/core/lib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

require_login();

// Get params
$id = required_param('id', PARAM_INT); //ID
$confirm = optional_param('confirm', '', PARAM_INT); // Delete confirmation hash

$PAGE->set_context(context_user::instance($USER->id));
$PAGE->set_url('/totara/reportbuilder/deletescheduled.php', array('id' => $id));
$PAGE->set_totara_menu_selected('\totara_core\totara\menu\myreports');

if (!$scheduledreport = $DB->get_record('report_builder_schedule', array('id' => $id))) {
    print_error('error:invalidreportscheduleid', 'totara_reportbuilder');
}

if (!reportbuilder::is_capable($scheduledreport->reportid)) {
    print_error('nopermission', 'totara_reportbuilder');
}
if ($scheduledreport->userid != $USER->id) {
    require_capability('totara/reportbuilder:managescheduledreports', context_system::instance());
}

$reportname = $DB->get_field('report_builder', 'fullname', array('id' => $scheduledreport->reportid));

$returnurl = new moodle_url('/my/reports.php');
$deleteurl = new moodle_url('/totara/reportbuilder/deletescheduled.php', array('id' => $scheduledreport->id, 'confirm' => '1', 'sesskey' => $USER->sesskey));

if ($confirm == 1) {
    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad', 'error');
    } else {
        $select = "scheduleid = ?";
        $DB->delete_records_select('report_builder_schedule_email_audience', $select, array($scheduledreport->id));
        $DB->delete_records_select('report_builder_schedule_email_systemuser', $select, array($scheduledreport->id));
        $DB->delete_records_select('report_builder_schedule_email_external', $select, array($scheduledreport->id));
        $DB->delete_records('report_builder_schedule', array('id' => $scheduledreport->id));
        \totara_reportbuilder\event\scheduled_report_deleted::create_from_schedule($scheduledreport)->trigger();
        $report = reportbuilder::create($scheduledreport->reportid, null, true);

        // @deprecated : Triggering of "\totara_reportbuilder\event\report_updated" event for deletion of scheduled
        // reports has been deprecated. Use "\totara_reportbuilder\event\scheduled_report_deleted" instead
        // Left here to allow for clients with custom event observers on this event
        \totara_reportbuilder\event\report_updated::create_from_report($report, 'scheduled')->trigger();

        totara_set_notification(get_string('deletedscheduledreport', 'totara_reportbuilder', format_string($reportname)),
                                $returnurl, array('class' => 'notifysuccess'));
    }
}
/// Display page
$PAGE->set_title(get_string('deletescheduledreport', 'totara_reportbuilder'));
$PAGE->set_heading(format_string($SITE->fullname));
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('deletescheduledreport', 'totara_reportbuilder'));
if (!$confirm) {
    echo $OUTPUT->confirm(get_string('deletecheckschedulereport', 'totara_reportbuilder', format_string($reportname)), $deleteurl, $returnurl);

    echo $OUTPUT->footer();
    exit;
}

echo $OUTPUT->footer();
