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

require(__DIR__ . '/../../config.php');

$userid = required_param('userid', PARAM_INT);

require_login();

if (!is_enabled_auth('connect')) {
    redirect(new moodle_url('/'));
}

$user = $DB->get_record('user', array('id' => $userid, 'deleted' => 0, 'auth' => 'connect'));
if (!$user) {
    redirect(new moodle_url('/user/profile.php', array('id' => $userid)));
}

$connectuser = $DB->get_record('auth_connect_users', array('userid' => $user->id));
if (!$connectuser) {
    redirect(new moodle_url('/user/profile.php', array('id' => $user->id)));
}

$server = $DB->get_record('auth_connect_servers', array('id' => $connectuser->serverid), '*', MUST_EXIST);

redirect(new moodle_url($server->serverurl . '/user/edit.php', array('id' => $connectuser->serveruserid, 'returnto' => 'tc_' . $server->clientidnumber)));
