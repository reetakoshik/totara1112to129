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
 * This file attempts to start SSO by redirecting to Totara Connect server.
 */

use \auth_connect\util;

require(__DIR__ . '/../../config.php');

$serverid = optional_param('serverid', 0, PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/auth/connect/sso_start.php', array('serverid' => $serverid));
$PAGE->set_pagelayout('login');
$PAGE->set_cacheable(false);

if (!is_enabled_auth('connect')) {
    redirect($CFG->wwwroot . '/');
}

if (!$serverid) {
    // This should not happen, the old fallback page was not the best idea.
    util::log_sso_attempt_error("Server id is missing, cannot start SSO");
    redirect($CFG->wwwroot . '/');
}

if (isloggedin() and !isguestuser()) {
    util::log_sso_attempt_error("User {$USER->id} is already logged in, cannot attempt SSO");
    redirect($CFG->wwwroot . '/');
}

$server = $DB->get_record('auth_connect_servers', array('id' => $serverid, 'status' => util::SERVER_STATUS_OK));
if (!$server) {
    util::log_sso_attempt_error('Invalid serverid specified for SSO start');
    // Hacking attempt, no need for error message.
    redirect($CFG->wwwroot . '/');
}

$ssourl = util::create_sso_request($server);
if (!$ssourl) {
    // Strange error, tell them to restart browser, method create_sso_request should have already logged the error.
    util::sso_error_page('ssoerrorgeneral', get_login_url());
}

unset($SESSION->loginerrormsg);
unset($SESSION->has_timed_out);

redirect($ssourl);
