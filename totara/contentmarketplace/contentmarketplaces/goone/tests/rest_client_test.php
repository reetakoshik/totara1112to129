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

use contentmarketplace_goone\invalid_token_exception;
use contentmarketplace_goone\rest_client;
use contentmarketplace_goone\rest_client_timeout_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Test rest_client class
 *
 * @group totara_contentmarketplace
 */
class contentmarketplace_goone_rest_client_testcase extends basic_testcase {

    public function test_get() {
        $curl = $this->createMock(\curl::class);
        $curl->method('get')->willReturn('{"test": "example"}');
        $curl->method('get_info')->willReturn([
            'url' => 'https://api.host/endpoint/function',
            'http_code' => 200,
            'content_type' => 'application/json'
        ]);
        $curl->errno = CURLE_OK;

        $client = new rest_client("https://api.host/endpoint", $curl);
        $response = $client->get("function");
        $this->assertEquals((object)["test" => "example"], $response);
    }

    public function test_500() {
        $curl = $this->createMock(\curl::class);
        $curl->method('get')->willReturn('{"message": "No route found for: GET /example"}');
        $curl->method('get_info')->willReturn([
            'url' => 'https://api.host/endpoint/function',
            'http_code' => 500,
            'content_type' => 'application/json'
        ]);
        $curl->errno = CURLE_OK;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Unexpected response from GO1 API. Received 500 No route found for: GET /example (Called URL https://api.host/endpoint/function)");
        $client = new rest_client("https://api.host/endpoint", $curl);
        $client->get("function");
    }

    public function test_401() {
        $curl = $this->createMock(curl::class);
        $curl->method('get')->willReturn('{"message": "No route found for: GET /example"}');
        $curl->method('get_info')->willReturn([
            'url' => 'https://api.host/endpoint/function',
            'http_code' => 401,
            'content_type' => 'application/json'
        ]);
        $curl->errno = CURLE_OK;

        $this->expectException(invalid_token_exception::class);
        $this->expectExceptionMessage("There is an authentication problem when connecting to the GO1 servers. The GO1 content marketplace will need to be set up again. (Received 401 response when calling GO1 API (Called URL https://api.host/endpoint/function))");
        $client = new rest_client("https://api.host/endpoint", $curl);
        $client->get("function");
    }

    public function test_curl_timeout() {
        $curl = $this->createMock(curl::class);
        $curl->method('get')->willReturn('test');
        $curl->method('get_info')->willReturn([
            'url' => 'https://api.host/endpoint/function',
        ]);
        $curl->errno = CURLE_OPERATION_TIMEOUTED;

        $this->expectException(rest_client_timeout_exception::class);
        $this->expectExceptionMessage("Encountered CURLE_OPERATION_TIMEOUTED when calling GO1 API (Called URL https://api.host/endpoint/function)");
        $client = new rest_client("https://api.host/endpoint", $curl);
        $client->get("function");
    }

    public function test_unknown_error() {
        $curl = $this->createMock(curl::class);
        $curl->method('get')->willReturn('test');
        $curl->method('get_info')->willReturn([
            'url' => 'https://api.host/endpoint/function',
        ]);
        $curl->errno = CURLE_SSL_CONNECT_ERROR;
        $curl->error = 'test_error';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Error calling GO1 API: test_error (35) (Called URL https://api.host/endpoint/function)");
        $client = new rest_client("https://api.host/endpoint", $curl);
        $client->get("function");
    }

    public function test_null_response() {
        $curl = $this->createMock(curl::class);
        $curl->method('get')->willReturn('null');
        $curl->method('get_info')->willReturn([
            'url' => 'https://api.host/endpoint/function',
            'http_code' => 200,
            'content_type' => 'application/json'
        ]);
        $curl->errno = CURLE_OK;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Empty response returned from GO1 API (Called URL https://api.host/endpoint/function)");
        $client = new rest_client("https://api.host/endpoint", $curl);
        $client->get("function");
    }

    public function test_bad_json_response() {
        $curl = $this->createMock(curl::class);
        $curl->method('get')->willReturn('bad_json');
        $curl->method('get_info')->willReturn([
            'url' => 'https://api.host/endpoint/function',
            'http_code' => 200,
            'content_type' => 'application/json'
        ]);
        $curl->errno = CURLE_OK;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("JSON error parsing response from GO1 API: Syntax error (Called URL https://api.host/endpoint/function). Response: bad_json");
        $client = new rest_client("https://api.host/endpoint", $curl);
        $client->get("function");
    }

    /**
     * @dataProvider content_type_provider
     */
    public function test_is_content_type($contenttype, $expected) {
        $result = rest_client::is_content_type_json($contenttype);
        $this->assertSame($expected, $result);
    }

    public function content_type_provider() {
        return [
            ['application/json', true],
            ['application/json; charset=UTF-8', true],
            ['applicaiton/zip', false],
            ['', false],
        ];
    }


}
