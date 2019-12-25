<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 *
 * @package auth_approved
 */

require(__DIR__ . '/../../config.php');

$requestid = required_param('requestid', PARAM_INT);
$reportid = optional_param('reportid', 0, PARAM_INT);

$syscontext = context_system::instance();
$PAGE->set_url('/auth/approved/edit.php');
$PAGE->set_context($syscontext);
$PAGE->set_pagelayout('report');

\navigation_node::override_active_url(\auth_approved\util::get_report_url($reportid), true);
$PAGE->navbar->add(get_string('edit'));

require_login();
require_capability('auth/approved:approve', $syscontext);

if (!is_enabled_auth('approved')) {
    print_error('plugindisabled', 'auth_approved');
}

$request = $DB->get_record('auth_approved_request', array('id' => $requestid), '*', MUST_EXIST);

if ($request->status != \auth_approved\request::STATUS_PENDING) {
    // This should nto happen, so no need for errors!
    redirect(\auth_approved\util::get_report_url($reportid));
}

\auth_approved\util::init_job_assignment_fields();

$currentdata = \auth_approved\request::decode_signup_form_data($request);
$options = [];
if (!empty($currentdata->managerjaid)) {
    $title = auth_approved\util::get_manager_job_assignment_option($currentdata->managerjaid);
    if ($title) {
        $options[$currentdata->managerjaid] = $title;
    }
}

$form = new \auth_approved\form\signup(null, array(
    'requestid' => $requestid,
    'stage' => \auth_approved\request::STAGE_APPROVAL,
    'managerjaoptions' => $options
));
$form->set_data($currentdata);

if ($form->is_cancelled()) {
    redirect(\auth_approved\util::get_report_url($reportid));
}
if ($data = $form->get_data()) {
    \auth_approved\request::update_request($data);
    redirect(\auth_approved\util::get_report_url($reportid));
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();