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
require_once($CFG->libdir . '/adminlib.php');

$id = required_param('id', PARAM_INT);

admin_externalpage_setup('authconnectservers');

$server = $DB->get_record('auth_connect_servers', array('id' => $id), '*', MUST_EXIST);

require_sesskey();

// This may take a long time.
core_php_time_limit::raise(60 * 60);
raise_memory_limit(MEMORY_HUGE);

// Do not close the session for now because we need it for Totara notification.
//\core\session\manager::write_close();
//ignore_user_abort(true);

$result = true;
$result = \auth_connect\util::update_api_version($server) && $result;
$result = \auth_connect\util::sync_positions($server) && $result;
$result = \auth_connect\util::sync_organisations($server) && $result;
$result = \auth_connect\util::sync_users($server) && $result;
$result = \auth_connect\util::sync_user_collections($server) && $result;

if ($result) {
    totara_set_notification(get_string('serversynced', 'auth_connect'), new moodle_url('/auth/connect/index.php'), array('class'=>'notifysuccess'));
} else {
    totara_set_notification(get_string('serversyncerror', 'auth_connect'), new moodle_url('/auth/connect/index.php'));
}
