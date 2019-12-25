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

use contentmarketplace_goone\oauth_rest_client;
use contentmarketplace_goone\oauth;
use contentmarketplace_goone\mock_config_storage;
use contentmarketplace_goone\mock_playback_curl;

defined('MOODLE_INTERNAL') || die();

/**
 * Test oauth_rest_client class
 *
 * @group totara_contentmarketplace
 */
class contentmarketplace_goone_oauth_rest_client_testcase extends basic_testcase {

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        require_once(__DIR__ . '/fixtures/mock_config_storage.php');
        require_once(__DIR__ . '/fixtures/mock_playback_curl.php');
    }

    public function test_get() {
        $curl = new mock_playback_curl($this);
        $curl->record(
            'https://api.host/endpoint/function',
            [
                'HEADER' => 0,
                'HTTPHEADER' => [
                    'Authorization: Bearer --OAUTH_ACCESS_TOKEN--',
                    'Accept: application/json',
                ],
                'FRESH_CONNECT' => true,
                'RETURNTRANSFER' => true,
                'FORBID_REUSE' => true,
                'SSL_VERIFYPEER' => true,
                'SSL_VERIFYHOST' => 2,
                'CONNECTTIMEOUT' => 0,
                'TIMEOUT' => 20,
                'CURLOPT_HTTPGET' => 1,
            ],
            [
                'url' => 'https://api.host/endpoint/function',
                'http_code' => 200,
                'content_type' => 'application/json',
            ],
            '{"test": "example"}'
        );

        $oauth = new oauth(new mock_config_storage());
        $client = new oauth_rest_client("https://api.host/endpoint", $oauth, $curl);

        $response = $client->get("function");
        $this->assertEquals((object)["test" => "example"], $response);
    }

    public function test_get_with_token_refresh() {


        $curl = new mock_playback_curl($this);
        $curl->record(
            'https://api.host/endpoint/function',
            [
                'HEADER' => 0,
                'HTTPHEADER' => [
                    'Authorization: Bearer --INVALID_TOKEN--',
                    'Accept: application/json',
                ],
                'FRESH_CONNECT' => true,
                'RETURNTRANSFER' => true,
                'FORBID_REUSE' => true,
                'SSL_VERIFYPEER' => true,
                'SSL_VERIFYHOST' => 2,
                'CONNECTTIMEOUT' => 0,
                'TIMEOUT' => 20,
                'CURLOPT_HTTPGET' => 1,
            ],
            [
                'url' => 'https://api.host/endpoint/function',
                'http_code' => 401,
                'content_type' => 'application/json',
            ],
            '{"message": "Invalid token"}'
        );

        $curl->record(
            'https://auth.go1.com/oauth/token',
            [
                'HEADER' => 0,
                'HTTPHEADER' => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
                'FRESH_CONNECT' => true,
                'RETURNTRANSFER' => true,
                'FORBID_REUSE' => true,
                'SSL_VERIFYPEER' => true,
                'SSL_VERIFYHOST' => 2,
                'CONNECTTIMEOUT' => 0,
                'TIMEOUT' => 20,
                'CURLOPT_POST' => 1,
                'CURLOPT_POSTFIELDS' => '{' .
                    '"client_id":"--OAUTH_CLIENT_ID--",' .
                    '"client_secret":"--OAUTH_CLIENT_SECRET--",' .
                    '"refresh_token":"--OAUTH_REFRESH_TOKEN--",' .
                    '"grant_type":"refresh_token"' .
                    '}',
            ],
            [
                'url' => 'https://auth.go1.com/oauth/token',
                'http_code' => 200,
                'content_type' => 'application/json',
            ],
            '{' .
            '    "token_type": "Bearer",' .
            '    "expires_in": 2678400,' .
            '    "access_token": "--REFRESHED_OAUTH_ACCESS_TOKEN--",' .
            '    "refresh_token": "--OAUTH_REFRESH_TOKEN--"' .
            '}'
        );

        $curl->record(
            'https://api.host/endpoint/function',
            [
                'HEADER' => 0,
                'HTTPHEADER' => [
                    'Authorization: Bearer --REFRESHED_OAUTH_ACCESS_TOKEN--',
                    'Accept: application/json',
                ],
                'FRESH_CONNECT' => true,
                'RETURNTRANSFER' => true,
                'FORBID_REUSE' => true,
                'SSL_VERIFYPEER' => true,
                'SSL_VERIFYHOST' => 2,
                'CONNECTTIMEOUT' => 0,
                'TIMEOUT' => 20,
                'CURLOPT_HTTPGET' => 1,
            ],
            [
                'url' => 'https://api.host/endpoint/function',
                'http_code' => 200,
                'content_type' => 'application/json',
            ],
            '{"test": "example"}'
        );

        $config = new mock_config_storage([
            'oauth_access_token' => '--INVALID_TOKEN--',
        ]);
        $oauth = new oauth($config, $curl);
        $client = new oauth_rest_client("https://api.host/endpoint", $oauth, $curl);

        $response = $client->get("function");
        $this->assertEquals((object)["test" => "example"], $response);
    }


}
