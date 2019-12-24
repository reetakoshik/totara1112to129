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

util::validate_sso_possible();

$server = $DB->get_record('auth_connect_servers', array('id' => $serverid));

if (!$server) {
    $servers = $DB->get_records('auth_connect_servers', array('status' => util::SERVER_STATUS_OK));
    if (!$servers) {
        $SESSION->authconnectssofailed = 1;
        redirect(get_login_url());
    }
    echo $OUTPUT->header();
    echo '<div class="buttons">';
    foreach ($servers as $server) {
        $url = new moodle_url('/auth/connect/sso_start.php', array('serverid' => $server->id));
        echo $OUTPUT->single_button($url, format_string($server->servername));
    }
    echo '</div>';
    echo $OUTPUT->footer();
    die;
}

if (!$server or $server->status != util::SERVER_STATUS_OK) {
    $SESSION->loginerrormsg = get_string('ssologinfailed', 'auth_connect');
    $SESSION->authconnectssofailed = 1;
    redirect(get_login_url());
}

$ssourl = util::create_sso_request($server);
if (!$ssourl) {
    $SESSION->authconnectssofailed = 1;
    redirect(get_login_url());
}

unset($SESSION->loginerrormsg);
unset($SESSION->authconnectssofailed);
unset($SESSION->has_timed_out);

redirect($ssourl);
