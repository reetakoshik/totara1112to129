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

use auth_approved\request;

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$requestid = required_param('requestid', PARAM_INT);
$reportid = optional_param('reportid', 0, PARAM_INT);

$syscontext = context_system::instance();
$PAGE->set_url('/auth/approved/approve.php');
$PAGE->set_context($syscontext);
$PAGE->set_pagelayout('report');

\navigation_node::override_active_url(\auth_approved\util::get_report_url($reportid), true);
$PAGE->navbar->add(get_string('approve', 'auth_approved'));

admin_externalpage_setup('authapprovedpending', '', null, '', array('pagelayout'=>'report'));

if (!is_enabled_auth('approved')) {
    print_error('plugindisabled', 'auth_approved');
}

$returnurl = \auth_approved\util::get_report_url($reportid);

$request = $DB->get_record('auth_approved_request', array('id' => $requestid));
if (!$request) {
    redirect($returnurl);
}
if ($request->status == request::STATUS_APPROVED) {
    // Nothing to do.
    redirect($returnurl);
}
if ($request->status == request::STATUS_REJECTED) {
    // Somebody managed to reject it in the meantime.
    totara_set_notification(get_string('errorprocessedinterim', 'auth_approved', $request->email), $returnurl);
    die;
}

/** @var core_renderer $OUTPUT */

// Verify if we are ready to approve before asking about approval.
$data = request::decode_signup_form_data($request);
$errors = request::validate_signup_form_data($data, request::STAGE_APPROVAL);
if ($errors) {
    $errorlist = array_reduce(
        array_unique($errors),

        function ($str, $error) {
            $listentry = "<li>$error</li>";
            return "$str\n$listentry";
        },

        ''
    );

    $errormsg = sprintf(
        '%s<br/><ul>%s</ul>',
        get_string('errorvalidation', 'auth_approved', $request->email),
        $errorlist
    );

    echo $OUTPUT->header();
    echo $OUTPUT->notification($errormsg);
    $editurl = new moodle_url('/auth/approved/edit.php', array('requestid' => $requestid, 'reportid' => $reportid));
    $button = new single_button($editurl, get_string('edit'), 'post');
    $button->class = 'continuebutton';
    echo $OUTPUT->render($button);
    echo $OUTPUT->footer();
    die;
}

$currentdata = array('requestid' => $requestid, 'reportid' => $reportid);
$form = new \auth_approved\form\approve($currentdata);

if ($form->is_cancelled()) {
    redirect($returnurl);
}
if ($data = $form->get_data()) {
    ignore_user_abort(true); // Make sure we do not get interrupted!

    $success = \auth_approved\request::approve_request($data->requestid, $data->custommessage, false);
    if ($success) {
        totara_set_notification(get_string('successapprove', 'auth_approved', $request->email), $returnurl, array('class' => 'notifysuccess'));
    } else {
        totara_set_notification(get_string('errorapprove', 'auth_approved', $request->email), $returnurl);
    }
}

echo $OUTPUT->header();
echo $form->render();
echo $OUTPUT->footer();
