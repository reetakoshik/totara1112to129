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
 * Service End Point.
 *
 * This is the public API, the sep_services class is internal implementation.
 *
 * The API version support can be implemented:
 *  - either here by mapping the $service to different methods in sep_services
 *  - or the methods in sep-services can do it too
 *  - or it can be a mix
 */

define('NO_MOODLE_COOKIES', true); // Session not used here.
define('AJAX_SCRIPT', true);       // Eliminates redirects and adds json header.

use \auth_connect\util;
use \totara_core\jsend;

require(__DIR__ . '/../../config.php');
jsend::init_output(); // Override exception handler and setup other things.

// Prevent GET parameters here, because we do not want them in apache logs.
$parameters = data_submitted();
if (empty($parameters->clientsecret) or empty($parameters->clientidnumber) or empty($parameters->service)) {
    jsend::send_error('invalid parameters');
}

$clientsecret   = clean_param($parameters->clientsecret, PARAM_ALPHANUM);
$clientidnumber = clean_param($parameters->clientidnumber, PARAM_ALPHANUM);
$service        = clean_param($parameters->service, PARAM_ALPHANUMEXT);
if (isset($parameters->component)) {
    $component = clean_param($parameters->component, PARAM_COMPONENT);
} else {
    $component = '';
}

unset($parameters->clientsecret);
unset($parameters->clientidnumber);
unset($parameters->service);
unset($parameters->component);

$server = $DB->get_record('auth_connect_servers', array('clientsecret' => $clientsecret, 'clientidnumber' => $clientidnumber));

if (!$server) {
    jsend::send_error('invalid client secret or idnumber');
}

if (!is_enabled_auth('connect')) {
    jsend::send_error('connect client not enabled');
}

if ($server->status != util::SERVER_STATUS_OK) {
    jsend::send_error('connect server is not active');
}

if ($server->apiversion < util::MIN_API_VERSION or $server->apiversion > util::MAX_API_VERSION) {
    jsend::send_error('unsupported api version');
}

if ($component === 'auth_connect' or $component === '') {
    $class = 'auth_connect\\sep_services';

} else {
    // NOTE: the plugin methods are intended for 3rd party plugins and customisations.
    if (!get_config('auth_connect', 'allowpluginsepservices')) {
        jsend::send_error('invalid server service component: ' . $component);
    }
    // Make sure we have valid component.
    list($type, $plugin) = core_component::normalize_component($component);
    $plugins = core_component::get_plugin_list($type);
    if (!isset($plugins[$plugin])) {
        jsend::send_error('invalid server service component: ' . $component);
    }
    unset($plugins);
    $component = $type . '_' . $plugin;
    $class = $component . '\\sep_services_client';
}

if (!method_exists($class, $service)) {
    jsend::send_error('invalid client service name: ' . $service);
}

// The service methods must do all parameter cleaning and validation.
// For now the methods are responsible for API versions too.
$result = $class::$service($server, (array)$parameters);
jsend::send_result($result);
