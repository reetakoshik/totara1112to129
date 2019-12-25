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
 * @package totara_connect
 */

use \totara_connect\util;

require(__DIR__ . '/../../config.php');

$clientidnumber = required_param('clientidnumber', PARAM_ALPHANUM);
$requesttoken = required_param('requesttoken', PARAM_ALPHANUM);
$action = optional_param('action', '', PARAM_ALPHA);

$clientparams = array('clientidnumber' => $clientidnumber, 'status' => util::CLIENT_STATUS_OK);
$client = $DB->get_record('totara_connect_clients', $clientparams);

if (!$client) {
    throw new moodle_exception('invalidclientidnumber', 'totara_connect');
}
if (empty($CFG->enableconnectserver)) {
    throw new moodle_exception('errorservernotenabled', 'totara_connect');
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/totara/connect/sso_request.php', array('clientidnumber' => $clientidnumber, 'requesttoken' => $requesttoken));
$PAGE->set_pagelayout('login');

if ($action === 'cancel') {
    require_sesskey();
    unset($SESSION->totaraconnectssostarted);
    unset($SESSION->wantsurl);
    $params = array('clientidnumber' => $clientidnumber, 'requesttoken' => $requesttoken, 'result' => 'cancel');
    redirect(new moodle_url(util::get_sso_finish_url($client), $params));
}

if (!isloggedin() or isguestuser()) {
    // At the moment we cannot detect if the session on SSO server just timed out or user logged out from client.
    unset($SESSION->has_timed_out);

    $SESSION->totaraconnectssostarted = array('clientidnumber' => $clientidnumber, 'requesttoken' => $requesttoken);
    $SESSION->wantsurl = $PAGE->url->out(false);
    redirect(get_login_url());
}

// Clear the session flags.
unset($SESSION->totaraconnectssostarted);
unset($SESSION->wantsurl);

// Make sure user is not logged-in-as!
if (\core\session\manager::is_loggedinas()) {
    if ($action === 'logout') {
        require_sesskey();
        require_logout();
        $SESSION->totaraconnectssostarted = array('clientidnumber' => $clientidnumber, 'requesttoken' => $requesttoken);
        $SESSION->wantsurl = $PAGE->url->out(false);
        redirect(get_login_url());
    } else {
        // Bad luck, they need to logout first or cancel.
        echo $OUTPUT->header();
        $message = get_string('warningloginas', 'totara_connect');
        $logouturl = new moodle_url($PAGE->url, array('action' => 'logout', 'sesskey' => sesskey()));
        $cancelurl = new moodle_url($PAGE->url, array('action' => 'cancel', 'sesskey' => sesskey()));
        echo $OUTPUT->confirm($message, $logouturl, $cancelurl);
        echo $OUTPUT->footer();
        die;
    }
}

// User is logged in, let's do the SSO magic!

if (!util::validate_sso_request_token($client, $requesttoken)) {
    $params = array('clientidnumber' => $clientidnumber, 'requesttoken' => $requesttoken, 'result' => 'error');
    redirect(new moodle_url(util::get_sso_finish_url($client), $params));
}

$session = util::create_sso_session($client);

if (!$session) {
    $params = array('clientidnumber' => $clientidnumber, 'requesttoken' => $requesttoken, 'result' => 'error');
    redirect(new moodle_url(util::get_sso_finish_url($client), $params));
}

$params = array('clientidnumber' => $clientidnumber, 'requesttoken' => $requesttoken, 'ssotoken' => $session->ssotoken, 'result' => 'success');
redirect(new moodle_url(util::get_sso_finish_url($client), $params));
