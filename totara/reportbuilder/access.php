<?php // $Id$
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

define('REPORTBUIDLER_MANAGE_REPORTS_PAGE', true);
define('REPORT_BUILDER_IGNORE_PAGE_PARAMETERS', true); // We are setting up report here, do not accept source params.

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/report_forms.php');

$id = required_param('id', PARAM_INT); // report builder id

admin_externalpage_setup('rbmanagereports');

$output = $PAGE->get_renderer('totara_reportbuilder');

$returnurl = new moodle_url('/totara/reportbuilder/access.php', array('id' => $id));

$config = (new rb_config())->set_nocache(true);
$report = reportbuilder::create($id, $config, false); // No access control for managing of reports here.

// form definition
$mform = new report_builder_edit_access_form(null, compact('id', 'report'));

// form results check
if ($mform->is_cancelled()) {
    redirect($returnurl);
}
if ($fromform = $mform->get_data()) {

    if (empty($fromform->submitbutton)) {
        totara_set_notification(get_string('error:unknownbuttonclicked', 'totara_reportbuilder'), $returnurl);
    }
    reportbuilder_set_status($id);
    reportbuilder_update_access($id, $fromform);

    $config = (new rb_config())->set_nocache(true);
    $report = reportbuilder::create($id, $config, false); // No access control for managing of reports here.

    \totara_reportbuilder\event\report_updated::create_from_report($report, 'access')->trigger();
    totara_set_notification(get_string('reportupdated', 'totara_reportbuilder'), $returnurl, array('class' => 'notifysuccess'));
}

echo $output->header();

echo $output->container_start('reportbuilder-navlinks');
echo $output->view_all_reports_link($report->embedded) . ' | ';
echo $output->view_report_link($report->report_url());
echo $output->container_end();

echo $output->heading(get_string('editreport', 'totara_reportbuilder', format_string($report->fullname)));

if ($report->get_cache_status() > 0) {
    echo $output->cache_pending_notification($id);
}

$currenttab = 'access';
require('tabs.php');

// display the form
$mform->display();

echo $output->footer();


/**
 * Update the report settings table with new access settings
 *
 * @param integer $reportid ID of the report to update
 * @param object $fromform Moodle form object containing new access settings
 *
 * @return boolean True if the settings could be successfully updated
 */
function reportbuilder_update_access($reportid, $fromform) {

    global $DB;

    $transaction = $DB->start_delegated_transaction();

    // first check if there are any access restrictions at all
    $accessenabled = isset($fromform->accessenabled) ? $fromform->accessenabled : REPORT_BUILDER_ACCESS_MODE_NONE;
    // update access enabled setting
    $todb = new stdClass();
    $todb->id = $reportid;
    $todb->accessmode = $accessenabled;
    $todb->timemodified = time();
    $DB->update_record('report_builder', $todb);

    // Loop round classes from rb\access namespace.
    $plugins = reportbuilder::get_all_access_plugins();
    foreach ($plugins as $obj) {
        $obj->form_process($reportid, $fromform);
    }
    $transaction->allow_commit();

    return true;
}
