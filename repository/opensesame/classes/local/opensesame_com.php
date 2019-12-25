<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @package repository_opensesame
 */

namespace repository_opensesame\local;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("$CFG->dirroot/mod/lti/OAuth.php");
require_once("$CFG->dirroot/lib/filelib.php");

use moodle\mod\lti\OAuthConsumer;
use moodle\mod\lti\OAuthRequest;
use moodle\mod\lti\OAuthSignatureMethod_HMAC_SHA1;

/**
 * Utility class for communication with OpenSesame server.
 */
class opensesame_com {
    /**
     * Returns curl init options for communication with OpenSesame server.
     * @return array
     */
    public static function get_curl_options() {
        $options = array();
        $options['CURLOPT_SSL_VERIFYPEER'] = true;
        $options['CURLOPT_CONNECTTIMEOUT'] = 20;
        $options['CURLOPT_FOLLOWLOCATION'] = 1;
        $options['CURLOPT_MAXREDIRS'] = 5;
        $options['CURLOPT_RETURNTRANSFER'] = true;
        $options['CURLOPT_NOBODY'] = false;

        return $options;
    }

    /**
     * Normalise http://labs.omniti.com/labs/jsend request result.
     *
     * @param string $result json encoded result
     * @return array JSend encoded result
     */
    protected static function normalise_result($result) {
        if ($result) {
            $result = json_decode($result, true);
        }
        if (empty($result['status'])) {
            $result = array('status' => 'error', 'message' => get_string('erroropensesameconnection', 'repository_opensesame'));
        }
        return $result;
    }

    public static function get_user_info($user) {
        $config = get_config('repository_opensesame');

        $data = array(
            'FirstName' => $user->firstname,
            'LastName' => $user->lastname,
            'Email' => $user->email,
            'UserId' => sha1($user->username . '_' . $user->mnethostid . '_' . get_site_identifier()),
        );

        if ($config->tenanttype === 'Demo') {
            if (strpos($data['Email'], '+') === false) {
                $data['Email'] = str_replace('@', '+' . $config->tenantid . '@', $data['Email']);
            }
        }

        return $data;
    }

    /**
     * Provision user.
     *
     * @param \stdClass $user
     * @return array JSend encoded result
     */
    public static function provision_user($user) {
        $config = get_config('repository_opensesame');

        $url = "https://www.opensesame.com/api/Totara/{$config->tenantid}/provision_user";
        $parameters = self::get_user_info($user);

        $tenant = new OAuthConsumer($config->tenantkey, $config->tenantsecret);
        $request = OAuthRequest::from_consumer_and_token($tenant, null, 'POST', $url, $parameters);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $tenant, null);

        $options = self::get_curl_options();
        $curl = new \curl();

        $result = $curl->post($request->get_normalized_http_url(), $request->to_postdata(), $options);

        $result = static::normalise_result($result);

        if ($result['status'] === 'success') {
            // Add params to result to make the code simpler.
            foreach ($parameters as $k => $v) {
                $result['data'][$k] = $v;
            }
        }

        if (empty($result['data']['UserLaunchUrl'])) {
            $result = array();
            $result['status'] = 'error';
            $result['message'] = 'OpenSesame API error';
        }

        return $result;
    }

    /**
     * Get OAuth request for content fetching.
     * @param $type
     * @return OAuthRequest
     */
    public static function get_fetch_content_request($type) {
        $config = get_config('repository_opensesame');

        $url = "https://www.opensesame.com/api/Totara/{$config->tenantid}/fetch_content";
        $parameters = array('SyncType' => $type);

        $tenant = new OAuthConsumer($config->tenantkey, $config->tenantsecret);
        $request = OAuthRequest::from_consumer_and_token($tenant, null, 'POST', $url, $parameters);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $tenant, null);

        $tenant = new OAuthConsumer($config->tenantkey, $config->tenantsecret);
        $request = OAuthRequest::from_consumer_and_token($tenant, null, 'POST', $url, $parameters);
        $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $tenant, null);

        return $request;
    }

    /**
     * Get catalogue certificate secret.
     * @return string
     */
    public static function get_catalogue_certificate() {
        $config = get_config('repository_opensesame');
        $secret = sha1(session_id() . $config->tenantkey . $config->tenantsecret . get_site_identifier());
        return $secret;
    }

    /**
     * Is this a valid certificate?
     * @param string $certificate
     * @return bool
     */
    public static function verify_catalogue_certificate($certificate) {
        $secret = self::get_catalogue_certificate();
        return ($secret === $certificate);
    }

    /**
     * Returns callback url that accepts.
     *
     * NOTE: default URL can be overridden with REPOSITORY_OPENSESAME_CALLBACK_URL.
     *
     * @return string url
     */
    public static function get_catalogue_callback_url() {
        global $CFG;
        return $CFG->wwwroot . '/repository/opensesame/totaracallback.php';
    }

    /**
     * Returns all tenant types.
     * @return string[]
     */
    public static function get_tenant_types() {
        $options = array();
        $options['Demo'] = get_string('tenanttypedemo', 'repository_opensesame');
        $options['Prod'] = get_string('tenanttypeprod', 'repository_opensesame');
        return $options;
    }
}
