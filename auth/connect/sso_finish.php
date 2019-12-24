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

$result = required_param('result', PARAM_ALPHANUM);
$clientidnumber = optional_param('clientidnumber', '', PARAM_ALPHANUM);
$requesttoken = optional_param('requesttoken', '', PARAM_ALPHANUM);
$ssotoken = optional_param('ssotoken', '', PARAM_ALPHANUM);

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/');

util::validate_sso_possible();

$server = $DB->get_record('auth_connect_servers', array('clientidnumber' => $clientidnumber));

if (!$server or $server->status != util::SERVER_STATUS_OK) {
    $SESSION->loginerrormsg = get_string('ssologinfailed', 'auth_connect');
    $SESSION->authconnectssofailed = 1;
    redirect(get_login_url());
}

$request = $DB->get_record('auth_connect_sso_requests', array('serverid' => $server->id, 'requesttoken' => $requesttoken));

if (!$request) {
    $SESSION->loginerrormsg = get_string('ssologinfailed', 'auth_connect');
    $SESSION->authconnectssofailed = 1;
    redirect(get_login_url());
}

// The token is used only once, delete it immediately after it is validated.
$DB->delete_records('auth_connect_sso_requests', array('id' => $request->id));

if ($request->sid !== session_id() or time() - $request->timecreated > util::REQUEST_LOGIN_TIMEOUT) {
    $SESSION->loginerrormsg = get_string('ssologinfailed', 'auth_connect');
    $SESSION->authconnectssofailed = 1;
    redirect(get_login_url());
}


if ($result !== 'success') {
    $SESSION->loginerrormsg = get_string('ssologinfailed', 'auth_connect');
    $SESSION->authconnectssofailed = 1;
    redirect(get_login_url());
}

util::finish_sso($server, $ssotoken);
