<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package auth_connect
 */

/**
 * This file completes the SSO with Totara Connect server.
 */

use \auth_connect\util;

require(__DIR__ . '/../../config.php');

ignore_user_abort(true);
core_php_time_limit::raise(60);

$result = optional_param('result', '', PARAM_ALPHANUM);
$clientidnumber = optional_param('clientidnumber', '', PARAM_ALPHANUM);
$requesttoken = optional_param('requesttoken', '', PARAM_ALPHANUM);
$ssotoken = optional_param('ssotoken', '', PARAM_ALPHANUM);

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/auth/connect/sso_finish.php');
$PAGE->set_pagelayout('login');
$PAGE->set_cacheable(false);

if (!is_enabled_auth('connect')) {
    redirect($CFG->wwwroot . '/');
}

if ($clientidnumber === '' or $requesttoken === '') {
    // Some hacking attempt, ignore it.
    redirect($CFG->wwwroot . '/');
}

$server = $DB->get_record('auth_connect_servers', array('clientidnumber' => $clientidnumber, 'status' => util::SERVER_STATUS_OK));
if (!$server) {
    util::log_sso_attempt_error("Invalid client idnumber on SSO finish page");
    // We should never get here, likely hacking attempt, no error page necessary.
    redirect($CFG->wwwroot . '/');
}

$request = $DB->get_record('auth_connect_sso_requests', array('serverid' => $server->id, 'requesttoken' => $requesttoken));
if (!$request) {
    util::log_sso_attempt_error("Invalid SSO request token on SSO finish page or user just restarted browser that reopens tabs");
    // We should never get here, likely hacking attempt, no error page necessary.
    redirect($CFG->wwwroot . '/');
}

// The token is used only once, delete it immediately after it is validated.
$DB->delete_records('auth_connect_sso_requests', array('id' => $request->id));

if (isloggedin() and !isguestuser()) {
    // Hopefully users remembers what they did, so no error page needed here.
    util::log_sso_attempt_error("User {$USER->id} somehow logged in during the processing of SSO request");
    redirect($CFG->wwwroot . '/', get_string('ssoerroralreadyloggedin', 'auth_connect'), null, \core\output\notification::NOTIFY_ERROR);
}

if ($request->sid !== session_id()) {
    util::log_sso_attempt_error("Session id changed during processing of SSO request");
    util::sso_error_page('ssoerrorgeneral', get_login_url());
}

if (time() - $request->timecreated > util::REQUEST_LOGIN_TIMEOUT) {
    util::log_sso_attempt_error("SSO attempt exceeded allocated time frame");
    util::sso_error_page('ssoerrorlogintimeout', get_login_url());
}

if ($result !== 'success') {
    if ($result !== 'cancel') {
        util::log_sso_attempt_error("User failed to log in to SSO server - {$result}");
    }
    util::sso_error_page('ssoerrorloginfailure', get_login_url());
}

util::finish_sso($server, $ssotoken);
