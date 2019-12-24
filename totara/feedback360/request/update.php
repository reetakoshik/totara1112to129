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
 * @author David Curry <david.curry@totaralms.com>
 * @package totara
 * @subpackage totara_feedback360
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/totara/feedback360/lib.php');

ajax_require_login();
feedback360::check_feature_enabled();

$users = required_param('users', PARAM_SEQUENCE);
$userformid = required_param('userform', PARAM_INT);

$PAGE->set_context(context_system::instance());

$renderer = $PAGE->get_renderer('totara_feedback360');
$out = '';

$out .= html_writer::start_tag('div', array('id' => 'system_assignments', 'class' => 'replacement_box'));

$userform = $DB->get_record('feedback360_user_assignment', array('id' => $userformid), '*', MUST_EXIST);
$feedback360 = $DB->get_record('feedback360', array('id' => $userform->feedback360id), '*', MUST_EXIST);

$usercontext = context_user::instance($userform->userid);
$systemcontext = context_system::instance();

// Check user has permission to request feedback.
if ($USER->id == $userform->userid) {
    // This is the user editing their own feedback.
    require_capability('totara/feedback360:manageownfeedback360', $systemcontext);
} else if (\totara_job\job_assignment::is_managing($USER->id, $userform->userid) || is_siteadmin()) {
    // This is the manager editing their staff members feedback.
    require_capability('totara/feedback360:managestafffeedback', $usercontext);
} else {
    print_error('error:accessdenied', 'totara_feedback');
}

foreach (explode(',', trim($users, ',')) as $userid) {
    $user = $DB->get_record('user', array('id' => $userid));
    $resp_params = array('userid' => $userid, 'feedback360userassignmentid' => $userformid);
    $resp = $DB->get_record('feedback360_resp_assignment', $resp_params);

    $out .= $renderer->system_user_record($user, $userformid, $resp, $feedback360->anonymous);
}

$out .= html_writer::end_tag('div');

echo "DONE{$out}";
exit();
