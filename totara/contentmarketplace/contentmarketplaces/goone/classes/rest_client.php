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

global $CFG;
require_once($CFG->libdir . '/filelib.php');

class rest_client {

    /** @var string */
    private $endpoint = null;

    /** @var \curl $curl */
    private $curl = null;

    /**
     * rest_client constructor.
     *
     * @param string $endpoint
     * @param \curl|null $curl
     */
    public function __construct(string $endpoint, $curl = null) {
        $this->endpoint = $endpoint;

        if (isset($curl)) {
            $this->curl = $curl;
        } elseif (defined('BEHAT_SITE_RUNNING')) {
            $this->curl = self::get_mock_curl();
        } else {
            $this->curl = new \curl();
        }
    }

    /**
     * @return mock_curl
     */
    private static function get_mock_curl() {
        global $CFG;
        require_once($CFG->dirroot . '/totara/contentmarketplace/contentmarketplaces/goone/tests/fixtures/mock_curl.php');
        return new mock_curl();
    }

    /**
     * @param string $resourcename
     * @param array $params
     * @param array $headers
     * @param array $options
     * @return null|string|\stdClass
     */
    public function get(string $resourcename, array $params = [], array $headers = [], array $options = []) {
        list($url, $options) = $this->prepare_request($resourcename, $params, $headers, $options);
        $response = $this->curl->get($url, [], $options);
        return $this->parse_response($response);
    }

    /**
     * @param string $resourcename
     * @param array $params
     * @param array $headers
     * @param array $options
     * @return null|string|\stdClass
     */
    public function post(string $resourcename, array $params = [], array $headers = [], array $options = []) {
        $iscontenttypeheader = function($header) {
            return \core_text::strpos(\core_text::strtolower($header), 'content-type: ') === 0;
        };
        if (count(array_filter($headers, $iscontenttypeheader)) === 0) {
            $headers[] = 'Content-Type: application/json';
        }

        list($url, $options) = $this->prepare_request($resourcename, [], $headers, $options);
        $response = $this->curl->post($url, json_encode($params), $options);
        return $this->parse_response($response);
    }

    /**
     * @param string $resourcename
     * @param array $params
     * @param array $headers
     * @param array $options
     * @return null|string|\stdClass
     */
    public function put(string $resourcename, array $params = [], array $headers = [], array $options = []) {
        $iscontenttypeheader = function($header) {
            return \core_text::strpos(\core_text::strtolower($header), 'content-type: ') === 0;
        };
        if (count(array_filter($headers, $iscontenttypeheader)) === 0) {
            $headers[] = 'Content-Type: application/json';
        }

        list($url, $options) = $this->prepare_request($resourcename, [], $headers, $options);
        // Avoiding use of curl->put() as that uses depreciated API and requires using files as input
        // which is not needed here. Instead use curl->post() and update the request method to suit.
        $options['CUSTOMREQUEST'] = "PUT";
        $response = $this->curl->post($url, json_encode($params), $options);
        return $this->parse_response($response);
    }

    /**
     * @param string $resourcename
     * @param array $params
     * @return string
     */
    private function build_url(string $resourcename, array $params = []) {
        $url = $this->endpoint . '/' . $resourcename;

        $params = array_filter($params, function($value) {
            return isset($value) && $value !== '';
        });
        if (!empty($params)) {
            $url .= '?' . http_build_query($params, '', '&');
        }

        return $url;
    }

    /**
     * @param string $resourcename
     * @param array $params
     * @param array $headers
     * @param array $options
     * @return array
     */
    private function prepare_request(string $resourcename, array $params, array $headers, array $options) {
        $url = $this->build_url($resourcename, $params);
        $isacceptheader = function($header) {
            return \core_text::strpos(\core_text::strtolower($header), 'accept: ') === 0;
        };
        if (count(array_filter($headers, $isacceptheader)) === 0) {
            $headers[] = 'Accept: application/json';
        }

        $options['HEADER'] = 0;
        $options['HTTPHEADER'] = $headers;
        $options['FRESH_CONNECT'] = true;
        $options['RETURNTRANSFER'] = true;
        $options['FORBID_REUSE'] = true;
        $options['SSL_VERIFYPEER'] = true;
        $options['SSL_VERIFYHOST'] = 2;
        $options['CONNECTTIMEOUT'] = 0;
        $options['TIMEOUT'] = 20;
        return [$url, $options];
    }

    /**
     * @param string $response
     * @return null|string|\stdClass
     */
    private function parse_response($response) {
        $info = $this->curl->get_info();
        $url = $info['url'];

        if ($this->curl->errno !== CURLE_OK) {
            if ($this->curl->errno == CURLE_OPERATION_TIMEOUTED) {
                throw new rest_client_timeout_exception($url);
            } else {
                $error = empty($this->curl->error) ? $response : "{$this->curl->error} ({$this->curl->errno})";
                throw new \Exception("Error calling GO1 API: " . $error . " (Called URL $url)");
            }
        }

        if ($info['http_code'] == 200) {
            if (self::is_content_type_json($info['content_type'])) {
                $data = json_decode($response);
                if (json_last_error() == 0) {
                    if (empty($data) or !is_object($data)) {
                        throw new \Exception("Empty response returned from GO1 API (Called URL $url)");
                    }
                    return $data;
                } else {
                    $error = json_last_error_msg();
                    throw new \Exception("JSON error parsing response from GO1 API: $error (Called URL $url). Response: $response");
                }
            } else {
                return $response;
            }
        } else if ($info['http_code'] == 204) {
            // No content
            return null;
        } else if ($info['http_code'] == 401) {
            throw new invalid_token_exception($url);
        } else {
            $message = "Unexpected response from GO1 API. Received " . $info['http_code'];
            if ($info['content_type'] === 'application/json') {
                $data = json_decode($response);
                if (json_last_error() == 0 and isset($data->message)) {
                    $message .= " $data->message";
                } else {
                    $message .= " $response";
                }
            }
            $message .= " (Called URL $url)";
            throw new \Exception($message);
        }
    }

    /**
     * @param string $contenttype
     * @return bool
     */
    public static function is_content_type_json(string $contenttype) {
        $contenttype = \core_text::strtolower($contenttype);
        if ($contenttype === 'application/json' || $contenttype === 'application/json; charset=utf-8') {
            return true;
        } else {
            return false;
        }
    }
}
