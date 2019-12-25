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
 * @author Maria Torres <maria.torres@totaralms.com>
 * @package totara
 * @subpackage reportbuilder
 */

/**
 * Page containing list of saved searches for this report
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');
require_once($CFG->dirroot . '/totara/core/utils.php');
require_once('report_forms.php');

require_login();
if (isguestuser()) {
    redirect(get_login_url());
}

// This is the custom half ajax Totara page, we MUST send some headers here at least...
send_headers('text/html', true);

$id = optional_param('id', null, PARAM_INT); // Id for report.
$sid = optional_param('sid', null, PARAM_INT); // Id for saved search.
$action = optional_param('action', 'show', PARAM_ALPHANUMEXT); // Action to be executed.
$confirm = optional_param('confirm', false, PARAM_BOOL); // Confirm delete.
$returnurl = new moodle_url('/totara/reportbuilder/savedsearches.php', array('id' => $id));;

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/totara/reportbuilder/savedsearches.php', array('id' => $id, 'sid' => $sid));
$PAGE->set_totara_menu_selected('\totara_core\totara\menu\myreports');

$output = $PAGE->get_renderer('totara_reportbuilder');

$config = (new rb_config())->set_sid($sid);
$report = reportbuilder::create($id, $config);

// Get info about the saved search we are dealing with.
if ($sid) {
    $conditions = array('id' => $sid, 'reportid' => $id, 'userid' => $USER->id);
    $search = $DB->get_record('report_builder_saved', $conditions, '*');
    if (!$search) {
        print_error('error:invalidsavedsearchid', 'totara_reportbuilder');
    }
}

if (!reportbuilder::is_capable($id)) {
    print_error('nopermission', 'totara_reportbuilder');
}

$pagetitle = format_string(get_string('savesearch', 'totara_reportbuilder') . ': ' . $report->fullname);
$PAGE->set_title($pagetitle);

if ($action === 'delete') {
    if (!$sid) {
        redirect($returnurl);
    }
    if ($confirm) {
        require_sesskey();
        $transaction = $DB->start_delegated_transaction();
        $select = "scheduleid IN (SELECT s.id FROM {report_builder_schedule} s WHERE s.savedsearchid = ?)";
        $DB->delete_records_select('report_builder_schedule_email_audience', $select, array($sid));
        $DB->delete_records_select('report_builder_schedule_email_systemuser', $select, array($sid));
        $DB->delete_records_select('report_builder_schedule_email_external', $select, array($sid));
        $DB->delete_records('report_builder_schedule', array('savedsearchid' => $sid));
        $DB->delete_records('report_builder_saved', array('id' => $sid));
        $transaction->allow_commit();
        redirect($returnurl);
    }

    echo $output->heading(get_string('savedsearches', 'totara_reportbuilder'), 1);

    // Is this saved search being used in any scheduled reports?
    if ($scheduledreports = $DB->get_records('report_builder_schedule', array('savedsearchid' => $sid))) {
        $table = new html_table();
        $table->id = 'scheduled_reports';
        $table->attributes['class'] = 'generaltable';
        $headers = array();
        $headers[] = get_string('format', 'totara_reportbuilder');
        $headers[] = get_string('schedule', 'totara_reportbuilder');
        $headers[] = get_string('createdby', 'totara_reportbuilder');
        $table->head = $headers;

        foreach ($scheduledreports as $sched) {
            $cells = array();

            // Format column.
            $format = \totara_core\tabexport_writer::normalise_format($sched->format);
            $allformats = \totara_core\tabexport_writer::get_export_classes();
            if (isset($allformats[$format])) {
                $classname = $allformats[$format];
                $sched->format = $classname::get_export_option_name();
            } else {
                $sched->format = get_string('error');
            }
            $cells[] = new html_table_cell($sched->format);

            // Schedule column.
            if (isset($sched->frequency) && isset($sched->schedule)) {
                $schedule = new scheduler($sched, array('nextevent' => 'nextreport'));
                $formatted = $schedule->get_formatted();
            } else {
                $formatted = get_string('schedulenotset', 'totara_reportbuilder');
            }
            $sched->schedule = $formatted;

            $cells[] = new html_table_cell($sched->schedule);

            // Created by column.
            $createdby = $sched->userid == $USER->id ? get_string('you', 'totara_reportbuilder') : '';
            $cells[] = new html_table_cell($createdby);

            $row = new html_table_row($cells);
            $table->data[] = $row;
        }

        $langstr = count($scheduledreports) == 1 ? 'savedsearchinscheduleddelete' : 'savedsearchinscheduleddeleteplural';
        $message = html_writer::tag('p', get_string($langstr, 'totara_reportbuilder', count($scheduledreports)));
        $message .= get_string('savedsearchinscheduleddeletereportname', 'totara_reportbuilder', format_string($report->fullname));
        $message .= html_writer::empty_tag('br');
        $message .= get_string('savedsearchinscheduleddeletesearchname', 'totara_reportbuilder', format_string($search->name));

        $message .= $OUTPUT->render($table);
    } else {
        $message = get_string('savedsearchconfirmdelete', 'totara_reportbuilder', format_string($search->name));
    }

    // Prompt to delete.
    $params = array('id' => $id, 'sid' => $sid, 'action' => 'delete', 'confirm' => 'true', 'sesskey' => $USER->sesskey);
    $confirmurl = new moodle_url('/totara/reportbuilder/savedsearches.php', $params);
    echo $output->confirm($message, $confirmurl, $returnurl);
    die;
}

if ($action === 'edit') {
    if (!$sid) {
        redirect($returnurl);
    }

    $data = clone($search);
    $data->sid = $data->id;
    $data->id = $data->reportid;
    $data->action = 'edit';

    $mform = new report_builder_save_form(null, array('report' => $report, 'data' => $data));

    if ($data = $mform->get_data()) {
        $todb = new stdClass();
        $todb->id = $data->sid;
        $todb->name = $data->name;
        $todb->ispublic = $data->ispublic;
        $todb->timemodified = time();
        $DB->update_record('report_builder_saved', $todb);
        redirect($returnurl);
    }

    echo $output->heading(get_string('savedsearches', 'totara_reportbuilder'), 1);
    $mform->display();
    die;
}

// Show users searches.
echo $output->heading(get_string('savedsearches', 'totara_reportbuilder'), 1);

$searches = $DB->get_records('report_builder_saved', array('userid' => $USER->id, 'reportid' => $id));
if (!empty($searches)) {
    echo $output->saved_searches_table($searches, $report);
} else {
    echo html_writer::tag('p', get_string('error:nosavedsearches', 'totara_reportbuilder'));
}
