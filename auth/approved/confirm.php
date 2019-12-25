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

// No need for cookies here, we only mark the request as confirmed.
define('NO_MOODLE_COOKIES', true);

require(__DIR__ . '/../../config.php');

$token = optional_param('token', '', PARAM_ALPHANUM);

$PAGE->set_url('/auth/approved/confirm.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('login');

if (!is_enabled_auth('approved') or empty($CFG->registerauth) or $CFG->registerauth !== 'approved') {
    print_error('plugindisabled', 'auth_approved');
}

ignore_user_abort(true); // Make sure we do not get interrupted!

$PAGE->set_title(get_string('emailconfirm', 'auth_approved'));
echo $OUTPUT->header();

list($success, $message, $continuebutton) = \auth_approved\request::confirm_request($token);
$class = $success ? 'notifysuccess' : 'notifyproblem';
echo $OUTPUT->notification($message, $class);

if ($continuebutton) {
    echo $OUTPUT->render($continuebutton);
}

echo $OUTPUT->footer();
die;

