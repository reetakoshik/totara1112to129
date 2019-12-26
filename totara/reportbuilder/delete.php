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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_reportbuilder
 */

/**
 * Page for report cloning
 */

define('REPORTBUIDLER_MANAGE_REPORTS_PAGE', true);
define('REPORT_BUILDER_IGNORE_PAGE_PARAMETERS', true); // We are setting up report here, do not accept source params.

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

$id = required_param('id', PARAM_INT);
$returnurl = optional_param('returnurl', '/', PARAM_LOCALURL);

$rawreport = $DB->get_record('report_builder', array('id' => $id), '*', MUST_EXIST);

$adminpage = $rawreport->embedded ? 'rbmanageembeddedreports' : 'rbmanagereports';
admin_externalpage_setup($adminpage);

$output = $PAGE->get_renderer('totara_reportbuilder');

$config = (new rb_config())->set_nocache(true);
$report = reportbuilder::create($id, $config, false); // No access control for managing of reports here.

$type = $report->embedded ? 'reload' : 'delete';

$currentdata = [
    'id' => $id,
    'returnurl' => $returnurl,
];
$params = [
    'type' => $type,
];
$form = new \totara_reportbuilder\form\delete_report_form($currentdata, $params);

if ($form->is_cancelled()) {
    redirect($CFG->wwwroot . $returnurl);
} else if ($data = $form->get_data()) {
    // Delete report, then redirect.

    if (reportbuilder_delete_report($id)) {
        \totara_reportbuilder\event\report_deleted::create_from_report($report, $report->embedded)->trigger();
        totara_set_notification(get_string($type . 'report', 'totara_reportbuilder'), $CFG->wwwroot . $returnurl, array('class' => 'notifysuccess'));

    } else {
        totara_set_notification(get_string('no' . $type . 'report', 'totara_reportbuilder'), $CFG->wwwroot . $returnurl);
    }
}

echo $output->header();
echo $output->heading(get_string('confirm' . $type . 'report', 'totara_reportbuilder'));
echo $output->confirm_delete($report);

echo $form->render();

echo $output->footer();
