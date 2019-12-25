<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Michael Dunstan <michael.dunstan@androgogic.com>
 * @package contentmarketplace_goone
 */

namespace contentmarketplace_goone;

defined('MOODLE_INTERNAL') || die();

final class oauth {

    const ENDPOINT = 'https://auth.go1.com';

    /** @var config_storage */
    public $config = null;

    /** @var rest_client $client */
    public $client = null;

    /**
     * oauth constructor.
     *
     * @param config_storage $config
     * @param \curl|null $curl
     */
    public function __construct(config_storage $config, $curl = null) {
        $this->config = $config;
        $this->client = new rest_client(self::ENDPOINT, $curl);
    }

    /**
     * @param \moodle_url $redirect_uri
     * @param array $state
     * @return \moodle_url
     */
    public static function get_authorize_url($redirect_uri, $state) {
        $url = self::ENDPOINT . '/oauth/authorize';
        if (defined('BEHAT_SITE_RUNNING') || defined('GO1_MOCK_API_ENDPOINTS')) {
            $url = '/totara/contentmarketplace/contentmarketplaces/goone/tests/behat/fixtures/oauth/authorize.php';
        }
        return new \moodle_url($url, array(
            "new_client" => "Totara",
            "client_id" => "totara",
            "response_type" => "code",
            "scope" => "account.read lo.read lo.write portal.read portal.write",
            "redirect_uri" => $redirect_uri,
            "state" => base64_encode(json_encode($state)),
        ));
    }

    /**
     * @param string $client_id
     * @param string $client_secret
     * @param string $code
     * @param \moodle_url $redirect_uri
     */
    public function token_setup($client_id, $client_secret, $code, \moodle_url $redirect_uri) {
        $this->config->set('oauth_client_id', $client_id);
        $this->config->set('oauth_client_secret', $client_secret);
        $params = array(
            "client_id" => $client_id,
            "client_secret" => $client_secret,
            "code" => $code,
            "redirect_uri" => $redirect_uri->out(false),
            "grant_type" => "authorization_code",
        );
        $token = $this->client->post('oauth/token', $params);
        $this->config->set('oauth_access_token', $token->access_token);
        $this->config->set('oauth_refresh_token', $token->refresh_token);
    }

    /**
     * Refreshes the token and stores the new token.
     */
    public function token_refresh() {
        $params = array(
            "client_id" => $this->config->get('oauth_client_id'),
            "client_secret" => $this->config->get('oauth_client_secret'),
            "refresh_token" => $this->config->get('oauth_refresh_token'),
            "grant_type" => "refresh_token",
        );
        $token = $this->client->post('oauth/token', $params);
        $this->config->set('oauth_access_token', $token->access_token);
        $this->config->set('oauth_refresh_token', $token->refresh_token);
    }

    /**
     * Move all the config from session storage over to db storage.
     * Use this after OAuth authorization has been successfully completed
     * and the admin user has asked to save the configuration.
     */
    public static function move_config_from_session_to_db() {
        $sessionstorage = new config_session_storage();
        $dbstorage = new config_db_storage();
        $dbstorage->copy($sessionstorage);
        $sessionstorage->clear();
    }

    /**
     * @return bool
     */
    public static function have_config_in_session() {
        $sessionstorage = new config_session_storage();
        return $sessionstorage->exists();
    }

}
