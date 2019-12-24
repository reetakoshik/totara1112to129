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
 * @package totara_dashboard
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/totara/dashboard/lib.php');
require_once($CFG->dirroot . '/totara/dashboard/dashboard_forms.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/totara/core/js/lib/setup.php');

$action = optional_param('action', null, PARAM_ALPHANUMEXT);
$id = 0;
if ($action != 'new') {
    $id = required_param('id', PARAM_INT);
}

admin_externalpage_setup('totaradashboard', '', array('id' => $id), new moodle_url('/totara/dashboard/edit.php'));

// Check Totara Dashboard is enable.
totara_dashboard::check_feature_enabled();

$dashboard = new totara_dashboard($id);

$returnurl = new moodle_url('/totara/dashboard/manage.php');

$mform = new totara_dashboard_edit_form(null, array('dashboard' => $dashboard->get_for_form()));

if ($mform->is_cancelled()) {
    redirect($returnurl);
}
if ($fromform = $mform->get_data()) {
    if (empty($fromform->submitbutton)) {
        totara_set_notification(get_string('error:unknownbuttonclicked', 'totara_dashboard'), $returnurl);
    }

    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad', 'error');
    }

    $dashboard->set_from_form($fromform)->save();

    totara_set_notification(get_string('dashboardsaved', 'totara_dashboard'), $returnurl, array('class' => 'notifysuccess'));
}

if ($id == 0) {
    $heading = get_string('createdashboard', 'totara_dashboard');
    $name = get_string('createdashboard', 'totara_dashboard');
} else {
    $heading = get_string('editdashboard', 'totara_dashboard');
    $name = $dashboard->name;
}

// Set up JS.
local_js(array(
        TOTARA_JS_UI,
        TOTARA_JS_ICON_PREVIEW,
        TOTARA_JS_DIALOG,
        TOTARA_JS_TREEVIEW
        ));

// Assigned audiences.
$cohorts = implode(',', $dashboard->get_cohorts());

$PAGE->requires->strings_for_js(array('assignedcohorts'), 'totara_dashboard');
$jsmodule = array(
        'name' => 'totara_cohortdialog',
        'fullpath' => '/totara/dashboard/dialog/cohort.js',
        'requires' => array('json'));
$args = array('args'=>'{"selected":"' . $cohorts . '",'.
        '"COHORT_ASSN_VALUE_ENROLLED":' . COHORT_ASSN_VALUE_ENROLLED . '}');
$PAGE->requires->js_init_call('M.totara_dashboardcohort.init', $args, true, $jsmodule);
unset($cohorts);

$title = $PAGE->title . ': ' . $heading;
$PAGE->set_title($title);
$PAGE->set_heading($heading);
$PAGE->navbar->add($name);

$output = $PAGE->get_renderer('totara_dashboard');

echo $output->header();
echo $output->heading(get_string('managedashboards', 'totara_dashboard'));
$mform->display();
echo $output->footer();
