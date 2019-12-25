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
$PAGE->set_url('/auth/approved/message.php');
$PAGE->set_context($syscontext);
$PAGE->set_pagelayout('report');

\navigation_node::override_active_url(\auth_approved\util::get_report_url($reportid), true);
$PAGE->navbar->add(get_string('message', 'auth_approved'));

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

$returnurl = \auth_approved\util::get_report_url($reportid);

$currentdata = array('requestid' => $requestid, 'reportid' => $reportid, 'email' => $request->email);
$form = new \auth_approved\form\message($currentdata);

if ($form->is_cancelled()) {
    redirect($returnurl);
}
if ($data = $form->get_data()) {
    ignore_user_abort(true); // Make sure we do not get interrupted!

    $success = \auth_approved\request::send_message($requestid, $data->messagesubject, $data->messagebody);
    if ($success) {
        totara_set_notification(get_string('successmessage', 'auth_approved', $request->email), $returnurl, array('class' => 'notifysuccess'));
    } else {
        totara_set_notification(get_string('errormessage', 'auth_approved', $request->email), $returnurl);
    }
}

echo $OUTPUT->header();
echo $form->render();
echo $OUTPUT->footer();
