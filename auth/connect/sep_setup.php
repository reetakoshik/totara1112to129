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
 * Service End Point setup script.
 *
 * This script is utilised by a connecting server when provided with this clients details
 * in order to finalise the connect between the server and the client.
 */

define('NO_MOODLE_COOKIES', true); // Session not used here.
define('AJAX_SCRIPT', true);       // Eliminates redirects and adds json header.

use \auth_connect\util;
use \totara_core\jsend;

require(__DIR__ . '/../../config.php');
jsend::init_output(); // Override exception handler and setup other things.

$setupsecret = optional_param('setupsecret', '', PARAM_ALPHANUM);

if (!util::verify_setup_secret($setupsecret)) {
    jsend::send_error('invalid setup secret');
}

$minapiversion = required_param('minapiversion', PARAM_INT);
$maxapiversion = required_param('maxapiversion', PARAM_INT);
$apiversion = util::select_api_version($minapiversion, $maxapiversion);
if ($apiversion < 1) {
    jsend::send_error('api version not supported');
}

$server = new stdClass();
$server->serveridnumber = required_param('serveridnumber', PARAM_ALPHANUM);
$server->serversecret   = required_param('serversecret', PARAM_ALPHANUM);
$server->serverurl      = required_param('serverurl', PARAM_URL);
$server->servername     = required_param('servername', PARAM_RAW);
$server->clientidnumber = required_param('clientidnumber', PARAM_ALPHANUM);
$server->clientsecret   = required_param('clientsecret', PARAM_ALPHANUM);
$server->apiversion     = $apiversion;
$server->servercomment  = '';
$server->status         = util::SERVER_STATUS_OK;
$server->timemodified   = $server->timecreated = time();

if ($DB->record_exists('auth_connect_servers', array('serveridnumber' => $server->serveridnumber))) {
    jsend::send_error('dupplicate server idnumber');
}
if ($DB->record_exists('auth_connect_servers', array('clientidnumber' => $server->clientidnumber))) {
    jsend::send_error('dupplicate client idnumber');
}

// Set the API version, this tests connection to server too.
$data = array(
    'serveridnumber' => $server->serveridnumber,
    'serversecret'   => $server->serversecret,
    'service'        => 'update_api_version',
    'apiversion'     => $server->apiversion,
    'clienttype'     => 'totaralms',
);
$url = $server->serverurl . '/totara/connect/sep.php';
$result = jsend::request($url, $data);

if ($result['status'] !== 'success') {
    jsend::send_result($result);
}

// Everything is fine!
$server->id = $DB->insert_record('auth_connect_servers', $server);

util::cancel_registration();

jsend::send_success(array());
