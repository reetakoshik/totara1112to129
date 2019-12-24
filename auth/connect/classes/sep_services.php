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

namespace auth_connect;

/**
 * Class sep_services (Service End Point services) provides the implementation for all web service calls
 * from Totara Connect servers.
 *
 * NOTE: developers must sanitise all $parameters before use!
 *
 * @package auth_connect
 */
class sep_services {
    /**
     * Is this a valid request server from this client to given server?
     * @param \stdClass $server
     * @param array $parameters: ('requesttoken' => string)
     * @return array JSend result, no data
     */
    public static function validate_sso_request_token($server, array $parameters) {
        global $DB;

        if (empty($parameters['requesttoken'])) {
            return array(
                'status' => 'fail',
                'data' => array(
                    'requesttoken' => 'missing sso request token',
                ),
            );
        }
        $parameters['requesttoken'] = clean_param($parameters['requesttoken'], PARAM_ALPHANUM);
        if (strlen($parameters['requesttoken']) !== 40) {
            return array(
                'status' => 'fail',
                'data' => array(
                    'ssotoken' => 'invalid request token format',
                ),
            );
        }

        $request = $DB->get_record('auth_connect_sso_requests',
            array('serverid' => $server->id, 'requesttoken' => $parameters['requesttoken']));

        if (!$request or time() - $request->timecreated > util::REQUEST_LOGIN_TIMEOUT) {
            if ($request) {
                $DB->delete_records('auth_connect_sso_requests', array('id' => $request->id));
            }
            return array(
                'status' => 'error',
                'message' => 'sso request timed out',
            );
        }

        return array(
            'status' => 'success',
            'data' => array(),
        );
    }

    /**
     * Is client SSO session still active?
     * This is used when deciding if server session should be timed out.
     *
     * @param \stdClass $server
     * @param array $parameters: ('ssotoken' => string)
     * @return array JSend result data: ('active' => bool)
     */
    public static function is_sso_user_active($server, array $parameters) {
        global $DB, $CFG;

        if (!isset($parameters['ssotoken'])) {
            return array(
                'status' => 'fail',
                'data' => array(
                    'ssotoken' => 'missing sso token',
                ),
            );
        }
        $parameters['ssotoken'] = clean_param($parameters['ssotoken'], PARAM_ALPHANUM);
        if (strlen($parameters['ssotoken']) !== 40) {
            return array(
                'status' => 'fail',
                'data' => array(
                    'ssotoken' => 'invalid sso token format',
                ),
            );
        }

        $sql = "SELECT s.*, ss.userid AS localuserid
                  FROM {sessions} s
                  JOIN {auth_connect_sso_sessions} ss ON (ss.sid = s.sid)
                 WHERE ss.ssotoken = :ssotoken AND ss.serverid = :serverid AND s.state = 0";
        $params = array('ssotoken' => $parameters['ssotoken'], 'serverid' => $server->id);
        $session = $DB->get_record_sql($sql, $params);

        if (!$session) {
            return array(
                'status' => 'success',
                'data' => array('active' => false),
            );
        }

        if ($session->userid != $session->localuserid) {
            // This should not happen - somebody must have messed up $USER global.
            error_log('totara connect SSO user ids are out of sync, terminating client session');
            \core\session\manager::kill_session($session->sid);
            $DB->delete_records('auth_connect_sso_sessions', array('sid' => $session->sid));

            return array(
                'status' => 'success',
                'data' => array('active' => false),
            );
        }

        if (!empty($CFG->sessiontimeout) and ($session->timemodified < time() - $CFG->sessiontimeout)) {
            // Force the client session timeout,
            // do not verify if any other auth plugin wants to extend the timeout- this is our SSO session.
            \core\session\manager::kill_session($session->sid);
            $DB->delete_records('auth_connect_sso_sessions', array('sid' => $session->sid));

            return array(
                'status' => 'success',
                'data' => array('active' => false),
            );
        }

        return array(
            'status' => 'success',
            'data' => array('active' => true),
        );
    }

    /**
     * Kill client user session established via SSO.
     * @param \stdClass $server
     * @param array $parameters: ('ssotoken' => string)
     * @return array JSend result, no data
     */
    public static function kill_sso_user($server, array $parameters) {
        global $DB;

        if (!isset($parameters['ssotoken'])) {
            return array(
                'status' => 'fail',
                'data' => array(
                    'ssotoken' => 'missing sso token',
                ),
            );
        }
        $parameters['ssotoken'] = clean_param($parameters['ssotoken'], PARAM_ALPHANUM);
        if (strlen($parameters['ssotoken']) !== 40) {
            return array(
                'status' => 'fail',
                'data' => array(
                    'ssotoken' => 'invalid sso token format',
                ),
            );
        }

        // Do not throw any errors here because this is called in case of any
        // other session problems on server.

        $ssosession = $DB->get_record('auth_connect_sso_sessions',
            array('ssotoken' => $parameters['ssotoken'], 'serverid' => $server->id));

        if ($ssosession) {
            $DB->delete_records('auth_connect_sso_sessions', array('id' => $ssosession->id));
            \core\session\manager::kill_session($ssosession->sid);
        }

        return array(
            'status' => 'success',
            'data' => array(),
        );
    }
}
