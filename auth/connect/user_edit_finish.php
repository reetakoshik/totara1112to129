<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package auth_connect
 */

require(__DIR__ . '/../../config.php');

$serveruserid = required_param('serveruserid', PARAM_INT);
$clientidnumber = required_param('clientidnumber', PARAM_ALPHANUM);

require_login();

if (!is_enabled_auth('connect')) {
    redirect(new moodle_url('/'));
}

$server = $DB->get_record('auth_connect_servers', array('clientidnumber' => $clientidnumber, 'status' => \auth_connect\util::SERVER_STATUS_OK));
if (!$server) {
    redirect(new moodle_url('/'));
}

$connectuser = $DB->get_record('auth_connect_users', array('serverid' => $server->id, 'serveruserid' => $serveruserid));
if (!$connectuser) {
    redirect(new moodle_url('/'));
}

$user = $DB->get_record('user', array('deleted' => 0, 'auth' => 'connect', 'id' => $connectuser->userid));
if (!$user) {
    redirect(new moodle_url('/'));
}

$success = \auth_connect\util::sync_user($user->id);

$url = new moodle_url('/user/profile.php', array('id' => $user->id));
$message = '';
$messagetype = \core\output\notification::NOTIFY_SUCCESS;

if (!$success) {
    $message = get_string('errorprofiledit', 'auth_connect');
    $messagetype = \core\output\notification::NOTIFY_ERROR;
}

redirect($url, $message, null, $messagetype);

